-- phpMyAdmin SQL Dump
-- version 5.1.1deb5ubuntu1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 01, 2025 at 05:33 AM
-- Server version: 8.0.43-0ubuntu0.22.04.1
-- PHP Version: 8.1.2-1ubuntu2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `isp`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `billgeneratemonthly` ()  BEGIN
    DECLARE total_count INT DEFAULT 0;
    DECLARE active INT DEFAULT 0;
    DECLARE inactive INT DEFAULT 0;
    DECLARE free INT DEFAULT 0;
    DECLARE discontinue INT DEFAULT 0;
    DECLARE billgenerate DECIMAL(10,2) DEFAULT 0;
    DECLARE total_discount DECIMAL(10,2) DEFAULT 0;
    DECLARE total_connection_fee DECIMAL(10,2) DEFAULT 0;
    DECLARE total_opening_balance DECIMAL(10,2) DEFAULT 0;

    -- Step 1: Check if a record for the current month exists in monthly_bill_making_check
    IF NOT EXISTS (
        SELECT 1 
        FROM monthly_bill_making_check 
        WHERE month_year = DATE_FORMAT(CURRENT_DATE, '%b-%Y')
    ) THEN
    
    
    -- insert previous due logs into the "tbl_previous_due" table
        INSERT INTO tbl_previous_due (tbl_agent_id, previous_due_amount)
        SELECT 
            cb.agid,
            cb.dueadvance
        FROM 
            customer_billing AS cb 
        JOIN 
            tbl_agent AS a ON cb.agid = a.ag_id 
        WHERE 
        	cb.dueadvance > 0
            AND a.ag_status = 1
            AND a.deleted_at IS NULL 
          --  AND MONTH(cb.generate_at) = MONTH(CURRENT_DATE) 
          --  AND YEAR(cb.generate_at) = YEAR(CURRENT_DATE)
            AND NOT EXISTS (
                        SELECT 1 
                        FROM tbl_previous_due pd
                        WHERE pd.tbl_agent_id = cb.agid
                          AND MONTH(pd.updated_at) = MONTH(CURRENT_DATE)
                          AND YEAR(pd.updated_at) = YEAR(CURRENT_DATE)
                    );

        -- Step 2: Update the customer_billing table, only if not updated in the current month
        UPDATE customer_billing AS cb 
        JOIN tbl_agent AS a ON cb.agid = a.ag_id 
        SET cb.totalgenerate = cb.totalgenerate + cb.monthlybill, 
            cb.dueadvance = cb.dueadvance + cb.monthlybill, 
            cb.generate_at = CURRENT_DATE 
        WHERE a.ag_status = 1 
            AND a.deleted_at IS NULL 
            AND ((MONTH(cb.generate_at) != MONTH(CURRENT_DATE)) 
            OR (YEAR(cb.generate_at) != YEAR(CURRENT_DATE)));

        -- Step 3: Update the tbl_agent table for active agents
        UPDATE tbl_agent
        SET 
            pay_status = 1,
            due_status = 0,
            bill_status = 0
        WHERE 
            ag_status = 1
            AND deleted_at IS NULL;

        -- Step 4: Gather summary data from tbl_agent
        SELECT 
            COUNT(*) AS total_count,
            SUM(CASE WHEN ag_status = 1 THEN 1 ELSE 0 END) AS active,
            SUM(CASE WHEN ag_status = 0 THEN 1 ELSE 0 END) AS inactive,
            SUM(CASE WHEN ag_status = 3 THEN 1 ELSE 0 END) AS free,
            SUM(CASE WHEN ag_status = 2 THEN 1 ELSE 0 END) AS discontinue,
            SUM(CASE WHEN ag_status = 1 THEN taka ELSE 0 END) AS billgenerate
        INTO 
            total_count, active, inactive, free, discontinue, billgenerate
        FROM 
            tbl_agent
        WHERE 
            deleted_at IS NULL;

        -- Step 5: Retrieve previous month's totals for discount, connection fee, and opening balance
        SET total_discount = (SELECT SUM(amount) FROM bonus WHERE YEAR(date) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH) AND MONTH(date) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH));
         IF total_discount IS NULL THEN
            SET total_discount = 0;
        END IF;
        SET total_connection_fee = (SELECT SUM(acc_amount) FROM tbl_account WHERE acc_type = 4 AND YEAR(entry_date) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH) AND MONTH(entry_date) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH));
        IF total_connection_fee IS NULL THEN
            SET total_connection_fee = 0;
        END IF;
        SET total_opening_balance = (SELECT SUM(acc_amount) FROM tbl_account WHERE acc_type = 5 AND YEAR(entry_date) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH) AND MONTH(entry_date) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH));
         IF total_opening_balance IS NULL THEN
            SET total_opening_balance = 0;
        END IF;


        -- Step 6: Insert summary data into monthly_bill_making_check, including discount, connection fee, and opening balance
        INSERT INTO monthly_bill_making_check 
            (month_year, tbillgenerate, tcustomer, tactivec, tinactivec, tdiscontinuec, tfreec, tdiscount, tconnectionfee, topening)
        VALUES 
            (DATE_FORMAT(CURRENT_DATE, '%b-%Y'), billgenerate, total_count, active, inactive, discontinue, free, total_discount, total_connection_fee, total_opening_balance);

       -- Step 7: Insert into tbl_due_logs for each agent
        INSERT INTO tbl_due_logs (agid, month_bill, due, generate_date, created_at, updated_at)
        SELECT 
            cb.agid,
            cb.monthlybill,
            cb.dueadvance,
            CURRENT_DATE,
            NOW(),
            NOW()
        FROM 
            customer_billing cb
        JOIN 
            tbl_agent a ON cb.agid = a.ag_id
        WHERE 
            a.ag_status = 1 AND a.deleted_at IS NULL;

    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `billUpdate` (IN `customerId` INT, IN `tag` VARCHAR(20), IN `amount` DECIMAL(10,2), IN `requestId` INT, IN `type` VARCHAR(50), IN `entry_by` INT(10))  BEGIN
    DECLARE preamount INT DEFAULT 0;
    DECLARE pretaka DECIMAL(10, 2);

    -- Set default value for 'type' if NULL
    SET type = IFNULL(type, 'plus');

    -- billpay tag processing
    IF tag = 'billpay' THEN
        UPDATE tbl_agent
        SET bill_status = 1
        WHERE ag_id = customerId;

        INSERT INTO tbl_account (cus_id,agent_id, acc_amount, acc_description, acc_type, entry_date, entry_by)
        SELECT cus_id,customerId, amount, 'Payment for bill', 3, NOW(), entry_by
        FROM tbl_agent
        WHERE ag_id = customerId;
        UPDATE customer_billing
        SET 
            totalpaid = totalpaid + amount,
            dueadvance = dueadvance - amount
        WHERE agid = customerId;

    -- paybilldeleted tag processing
    ELSEIF tag = 'paybilldeleted' THEN
        DELETE FROM tbl_account WHERE acc_id = requestId;

        UPDATE customer_billing
        SET 
            totalpaid = totalpaid - amount,
            dueadvance = dueadvance + amount
        WHERE agid = customerId;

    -- paybillupdate tag processing
    ELSEIF tag = 'paybillupdate' THEN
        SELECT acc_amount INTO preamount FROM tbl_account WHERE acc_id = requestId;
        
        UPDATE tbl_account
        SET 
            acc_amount = amount,
            last_update = NOW()
        WHERE acc_id = requestId;

        UPDATE customer_billing
        SET 
            totalpaid = (totalpaid + amount)- preamount,
            dueadvance = (dueadvance + amount)-preamount
        WHERE agid = customerId;

    -- discount tag processing
    ELSEIF tag = 'discount' THEN
        INSERT INTO bonus (ag_id, amount, description, entryby)
        VALUES (customerId, amount, 'Discount', entry_by);

        UPDATE customer_billing
        SET 
            dueadvance = dueadvance - amount,
            totaldiscount = totaldiscount + amount
        WHERE agid = customerId;

    -- previousdue tag processing
    ELSEIF tag = 'previousdue' THEN
        UPDATE customer_billing
        SET 
            dueadvance = (dueadvance+amount)-previousdue,
             previousdue = amount
        WHERE agid = customerId;

   -- Effected Update processing
    ELSEIF tag = 'effectedUpdate' THEN
        -- Step 2: Update the customer_billing table, only if not updated in the current month
        UPDATE customer_billing AS cb 
        JOIN tbl_agent AS a ON cb.agid = a.ag_id 
        SET cb.totalgenerate = cb.totalgenerate + cb.monthlybill, 
            cb.dueadvance = cb.dueadvance + cb.monthlybill, 
            cb.generate_at = CURRENT_DATE 
        WHERE a.ag_id = customerId;
        
          -- Step 2: Insert updated data into tbl_due_logs for the given agent_id
        INSERT INTO tbl_due_logs (agid, month_bill, due, generate_date, created_at, updated_at)
        SELECT 
        cb.agid, 
        cb.monthlybill, 
        cb.dueadvance, 
        CURRENT_DATE, 
        NOW(), 
        NOW()
        FROM customer_billing cb
        WHERE cb.agid = customerId;

    -- package tag processing
    ELSEIF tag = 'package' THEN
          -- Get current taka from tbl_agent
        SELECT taka INTO pretaka FROM tbl_agent WHERE ag_id = customerId;

        -- Update tbl_agent based on the type
        UPDATE tbl_agent  SET  taka = amount  WHERE ag_id = customerId;

        -- Update customer_billing based on the type
        UPDATE customer_billing
        SET 
            monthlybill = amount,
            totalgenerate = CASE 
                WHEN type = 'change' THEN (totalgenerate + amount) - pretaka 
                ELSE totalgenerate 
            END,
            dueadvance = CASE 
                WHEN type = 'change' THEN (dueadvance + amount) - pretaka 
                ELSE dueadvance 
            END
        WHERE agid = customerId;
    END IF;

    UPDATE tbl_agent
    SET pay_status = 0
    WHERE ag_id = customerId 
      AND (SELECT dueadvance FROM customer_billing WHERE agid = customerId) < 1;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `clear_activity_log` (IN `days` INT)  BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM daily_activity_log_check
        WHERE id = 1 AND CAST(last_delete AS DATE) = CURDATE()
    ) THEN
		-- CLEAR ACTIVITY LOG DATA
        DELETE FROM activity_logs
        WHERE created_at < NOW() - INTERVAL days DAY;

		-- UPDATE DAILY ACTIVITY LOG CHECK
        UPDATE daily_activity_log_check
        SET last_delete = NOW()
        WHERE id = 1;
        
        
        IF NOT EXISTS (SELECT 1 FROM daily_activity_log_check WHERE id = 1)
        THEN 
        	-- INSERT ROW
        	INSERT INTO daily_activity_log_check (id, last_delete)
			VALUES (1, NOW());
        ELSE
            -- UPDATE DAILY ACTIVITY LOG CHECK
            UPDATE daily_activity_log_check
            SET last_delete = NOW()
            WHERE id = 1;
        END IF;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `empty_database` ()  BEGIN
-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;
    
-- First clear the entire customer_billing table
TRUNCATE TABLE activity_logs;
TRUNCATE TABLE attendance;
TRUNCATE TABLE bonus;
TRUNCATE TABLE business_targets;
TRUNCATE TABLE customer_billing;
TRUNCATE TABLE customer_return;
TRUNCATE TABLE daily_activity_log_check;
TRUNCATE TABLE delete_tbl_agent_log;
TRUNCATE TABLE items;
TRUNCATE TABLE monthly_bill_making_check;
TRUNCATE TABLE products;
TRUNCATE TABLE product_categories;
TRUNCATE TABLE product_model;
TRUNCATE TABLE purchases;
TRUNCATE TABLE sales;
TRUNCATE TABLE stock;
TRUNCATE TABLE stock_marketing_enable;
TRUNCATE TABLE suppliers;
TRUNCATE TABLE supplier_return;
TRUNCATE TABLE tbl_account;
TRUNCATE TABLE tbl_accounts_head;
TRUNCATE TABLE tbl_agent;
TRUNCATE TABLE tbl_agent_activity;
TRUNCATE TABLE tbl_bill_amount_change;
TRUNCATE TABLE tbl_complains;
TRUNCATE TABLE tbl_complains_new_user;
TRUNCATE TABLE tbl_complain_templates;
TRUNCATE TABLE tbl_due_logs;
TRUNCATE TABLE tbl_due_opening_amount_and_con_charge;
TRUNCATE TABLE tbl_employee;
TRUNCATE TABLE tbl_employee_transaction;
TRUNCATE TABLE tbl_marketing_agent;
TRUNCATE TABLE tbl_notice;
TRUNCATE TABLE tbl_package;
TRUNCATE TABLE tbl_previous_due;
TRUNCATE TABLE tbl_remarks;
TRUNCATE TABLE tbl_service;
TRUNCATE TABLE tbl_stock;
TRUNCATE TABLE tbl_stock_category;
TRUNCATE TABLE tbl_stock_item;
TRUNCATE TABLE tbl_zone;
TRUNCATE TABLE _useraccess;
TRUNCATE TABLE _createuser;
TRUNCATE TABLE sms;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;


-- clear sms configuration from settings
UPDATE tbl_setting SET value = "aaaaa" WHERE id IN(1,2,8,9);
UPDATE tbl_setting SET value = "inactive" WHERE field = "billGenerate";
UPDATE tbl_setting SET value = "" WHERE field = "address";
UPDATE tbl_setting SET value = "" WHERE field = "mobile";
UPDATE tbl_setting SET value = "" WHERE field = "email";

-- insert sms template
INSERT INTO `sms` (`id`, `smshead`, `smsbody`, `status`, `update_date`) VALUES
(1, 'zone_wise_sms', 'Dear {CUSTOMER_NAME},\r\nYour customer id: {CUSTOMER_ID}, Username: {IP_ADDRESS}, Due: {DUE_AMOUNT} Tk', 1, '2025-07-22 05:05:54'),
(2, 'single_due_sms', 'Dear {CUSTOMER_NAME},\r\nCustomer ID: {CUSTOMER_ID}, Username: {IP_ADDRESS}, Package: {PACKAGE_NAME}, Current Due: {DUE_AMOUNT} Tk\r\nThank you for being with us.\r\n', 2, '2025-07-22 05:06:09'),
(3, 'paid_sms', 'Dear {CUSTOMER_NAME},  \r\nYour bill has been successfully paid. Paid Amount: {PAID_AMOUNT}, Due Amount: {DUE_AMOUNT}  \r\nThank you for being with us.\r\n', 3, '2025-07-22 05:05:21'),
(4, 'new_customer_sms', 'Dear {CUSTOMER_NAME}, ID: {CUSTOMER_ID}, Username:{IP_ADDRESS}, Package:{PACKAGE_NAME}, Monthly Bill:{MONTHLY_BILL}', 4, '2025-07-17 09:00:33'),
(5, 'general_sms', 'Eid Mubarak, {CUSTOMER_NAME}! \r\nMay this Eid bring joy, peace, and blessings to your life.\r\n\r\nThank you for being with us.  \r\nWishing you happiness always!', 5, '2025-07-21 08:08:48'),
(6, 'inactive', 'Dear {CUSTOMER_NAME}, ID: {CUSTOMER_ID}  \nYour connection will be disconnected tomorrow due to unpaid bill of {DUE_AMOUNT} Tk.  \nPlease pay today to avoid interruption.  \nThank you.\n', 6, '2025-07-22 05:25:54');


INSERT INTO _createuser (FullName,UserName, Password, Email, Status)
VALUES ('BSD', 'bsd', 'c4ca4238a0b923820dcc509a6f75849b', 'bsd@gmail.com', 1);


INSERT INTO _useraccess (UserId, UserType, MenuPermission, WorkPermission, EntryBy, EntryDate, UpdateBy, LastUpdate)
VALUES (1, 'SA', 'a:0:{}', 'a:4:{i:0;s:4:"view";i:1;s:3:"add";i:2;s:4:"edit";i:3;s:6:"delete";}', NULL, NOW(), NULL, NOW());

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `generate_monthly_salary` (IN `salary_month` DATE)  BEGIN

    DECLARE done INT DEFAULT FALSE;
    DECLARE emp_id INT;

    DECLARE emp_cursor CURSOR FOR 
        SELECT id FROM tbl_employee;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN emp_cursor;

    read_loop: LOOP

        FETCH emp_cursor INTO emp_id;
        IF done THEN
            LEAVE read_loop;
        END IF;

        -- যদি একই মাসের salary আগেই generate করা থাকে, তাহলে skip করে দিবে
        IF NOT EXISTS (
            SELECT 1 FROM tbl_employee_transaction
            WHERE employee_id = emp_id AND received_due = 0 AND created_at = salary_month
        ) THEN

            INSERT INTO tbl_employee_transaction (
                employee_id,
                salary_amount,
                conveyance,
                received_amount,
                received_due,
                punishment,
                accounts_id,
                created_at
            )
            VALUES (
                emp_id,
                (SELECT salary_amount FROM tbl_employee WHERE id = emp_id),
                0,
                0,
                0,
                0,
                0,
                salary_month
            );

        END IF;

    END LOOP;

    CLOSE emp_cursor;

END$$

--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `function_bill_update` (`customerId` INT, `tag` VARCHAR(20), `amount` DECIMAL(10,2), `requestId` INT, `type` VARCHAR(50), `entry_by` INT) RETURNS INT BEGIN
    DECLARE exit_code INT DEFAULT 1;

    -- Call the billUpdate procedure
    CALL billUpdate(customerId, tag, amount, requestId, type,entry_by);

    -- Optionally set a return value
    RETURN exit_code; -- You can customize this to return a status code or similar if needed
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` bigint NOT NULL,
  `created_by` bigint DEFAULT NULL COMMENT '_createuser.UserId ',
  `action_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '1=login\r\n2=create\r\n3=update\r\n4=delete\r\n',
  `action_related` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '1=login 2=agent 3=payment	',
  `target_table` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'table_name',
  `target_id` bigint DEFAULT NULL COMMENT 'table_row_id',
  `acc_type` int DEFAULT NULL COMMENT '1=Expense 2=Other income 3=bill Collection 4=connection charge 5= Opening Income',
  `acc_amount` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `old_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `new_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `device_info` text COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `created_by`, `action_type`, `action_related`, `target_table`, `target_id`, `acc_type`, `acc_amount`, `description`, `old_data`, `new_data`, `ip_address`, `device_info`, `created_at`, `deleted_at`) VALUES
(384, 2, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.91.26', 'OS: Windows 10 | Browser: Edge 138.0.0.0', '2025-07-22 15:18:36', NULL),
(385, 2, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.91.26', 'OS: Windows 10 | Browser: Edge 138.0.0.0', '2025-07-23 11:16:39', NULL),
(386, 1, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.91.26', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-07-24 13:11:26', NULL),
(387, 1, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.204.210.86', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-07-26 10:01:46', NULL),
(388, 1, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.204.210.86', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-07-26 14:59:06', NULL),
(389, 1, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.204.210.86', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-07-26 17:06:59', NULL),
(390, 1, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.204.210.86', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-07-27 09:47:13', NULL),
(391, 1, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.19', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-07-28 11:31:40', NULL),
(392, 2, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.19', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-07-29 10:43:03', NULL),
(393, 1, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.19', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-07-29 11:52:50', NULL),
(394, 2, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.19', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-07-29 12:04:37', NULL),
(395, 1, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.19', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-07-29 14:21:36', NULL),
(396, 2, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.19', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-07-29 15:23:42', NULL),
(397, 2, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.19', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-07-29 18:02:45', NULL),
(398, 2, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.19', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-07-30 10:22:09', NULL),
(399, 2, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.19', 'OS: Windows 10 | Browser: Edge 138.0.0.0', '2025-07-30 10:38:36', NULL),
(400, 2, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.19', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-07-30 12:41:02', NULL),
(401, 2, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.19', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-07-30 15:07:21', NULL),
(402, 1, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.19', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-07-30 16:50:12', NULL),
(403, 1, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.19', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-07-30 17:24:12', NULL),
(404, 1, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.19', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-07-31 10:06:38', NULL),
(405, 2, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.19', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-07-31 11:19:48', NULL),
(406, 2, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.19', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-08-02 10:06:49', NULL),
(407, 2, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.19', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-08-02 15:19:57', NULL),
(408, 2, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.19', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-08-02 16:13:58', NULL),
(409, 1, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.19', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-08-03 16:00:13', NULL),
(410, 2, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.19', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-08-03 17:27:35', NULL),
(411, 2, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.19', 'OS: Windows 10 | Browser: Edge 138.0.0.0', '2025-08-03 17:35:43', NULL),
(412, 1, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.19', 'OS: Linux | Browser: Chrome 138.0.0.0', '2025-08-04 14:10:06', NULL),
(413, 2, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.19', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-08-04 14:34:19', NULL),
(414, 2, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.19', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-08-04 15:29:41', NULL),
(415, 2, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.19', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-08-04 16:52:25', NULL),
(416, 2, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.19', 'OS: Windows 10 | Browser: Edge 138.0.0.0', '2025-08-06 12:37:40', NULL),
(417, 2, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.19', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-08-06 13:16:36', NULL),
(418, 2, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.17', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-08-06 16:22:19', NULL),
(419, 1, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.17', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-08-07 10:51:42', NULL),
(420, 2, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.17', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-08-07 16:59:00', NULL),
(421, 2, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.17', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-08-09 13:14:15', NULL),
(422, 2, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.17', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-08-09 14:18:20', NULL),
(423, 2, '2', '3', '', 0, 3, 410, 'A payment of 410 Taka was received for customer CUS00941.', NULL, NULL, NULL, NULL, '2025-08-09 14:26:20', NULL),
(424, 2, '2', '3', '', 0, 3, 20, 'A payment of 20 Taka was received for customer CUS00941.', NULL, NULL, NULL, NULL, '2025-08-09 14:26:39', NULL),
(425, 2, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.17', 'OS: Windows 10 | Browser: Edge 139.0.0.0', '2025-08-09 17:07:39', NULL),
(426, 2, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.17', 'OS: Windows 10 | Browser: Edge 139.0.0.0', '2025-08-10 10:54:43', NULL),
(427, 2, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.17', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-08-10 12:24:15', NULL),
(428, 2, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.17', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-08-10 16:14:17', NULL),
(429, 2, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.17', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-08-10 18:26:05', NULL),
(430, 1, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.17', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-08-11 10:23:52', NULL),
(431, 2, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.17', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-08-11 11:42:16', NULL),
(432, 2, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.17', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-08-11 15:49:43', NULL),
(433, 2, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.17', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-08-11 18:31:11', NULL),
(434, 1, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.20', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-08-11 18:54:16', NULL),
(435, 1, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.20', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-08-11 18:56:11', NULL),
(436, 1, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.20', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-08-12 11:03:39', NULL),
(437, 1, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.20', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-08-12 11:16:08', NULL),
(438, 1, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '106.0.54.226', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-08-12 13:04:54', NULL),
(439, 1, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.20', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-08-12 15:23:16', NULL),
(440, 1, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.20', 'OS: Windows 10 | Browser: Edge 139.0.0.0', '2025-08-12 16:24:05', NULL),
(441, 1, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.20', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-08-12 19:12:13', NULL),
(442, 1, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.20', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-08-13 10:25:23', NULL),
(443, 2, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.20', 'OS: Windows 10 | Browser: Edge 139.0.0.0', '2025-08-13 15:50:37', NULL),
(444, 1, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.20', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-08-13 15:54:44', NULL),
(445, 2, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.20', 'OS: Windows 10 | Browser: Edge 139.0.0.0', '2025-08-13 17:21:22', NULL),
(446, 1, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.20', 'OS: Windows 10 | Browser: Chrome 139.0.0.0', '2025-08-17 14:29:44', NULL),
(447, 1, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.20', 'OS: Windows 10 | Browser: Chrome 138.0.0.0', '2025-08-17 15:07:57', NULL),
(448, 1, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.20', 'OS: Windows 10 | Browser: Chrome 139.0.0.0', '2025-08-18 14:16:54', NULL),
(449, 1, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.20', 'OS: Windows 10 | Browser: Chrome 139.0.0.0', '2025-08-18 16:40:28', NULL),
(450, 1, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '220.158.207.33', 'OS: Windows 10 | Browser: Edge 139.0.0.0', '2025-08-18 17:20:50', NULL),
(451, 1, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.230.107.1', 'OS: Linux | Browser: Chrome 138.0.0.0', '2025-08-18 17:26:38', NULL),
(452, 1, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.21', 'OS: Linux | Browser: Chrome 139.0.0.0', '2025-08-18 17:30:17', NULL),
(453, 1, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.61.240.163', 'OS: iOS | Browser: Safari', '2025-08-18 17:35:14', NULL),
(454, 1, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '220.158.207.33', 'OS: Windows 10 | Browser: Edge 139.0.0.0', '2025-08-18 17:35:41', NULL),
(455, 1, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '103.111.90.21', 'OS: Windows 10 | Browser: Edge 139.0.0.0', '2025-08-19 04:01:56', NULL),
(456, 1, '1', '1', NULL, NULL, NULL, NULL, 'BSD Admin Logged In Successfully.', NULL, NULL, '220.158.207.33', 'OS: Windows 10 | Browser: Edge 139.0.0.0', '2025-08-21 13:46:05', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int NOT NULL,
  `employee_id` int NOT NULL,
  `attendance_date` date DEFAULT NULL,
  `in_time` time DEFAULT NULL,
  `out_time` time DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bonus`
--

CREATE TABLE `bonus` (
  `id` int NOT NULL,
  `ag_id` int NOT NULL,
  `amount` int NOT NULL DEFAULT '0',
  `description` mediumtext COLLATE utf8mb4_unicode_ci,
  `entryby` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bonus`
--

INSERT INTO `bonus` (`id`, `ag_id`, `amount`, `description`, `entryby`, `date`, `updated_at`) VALUES
(1, 1, 100, 'Discount', '2', '2024-12-17 00:00:00', '2024-12-17 11:20:55'),
(2, 5, 200, 'Discount', '2', '2025-02-03 00:00:00', '2025-02-03 09:48:10'),
(3, 4, 50, 'Discount', '2', '2025-02-18 00:00:00', '2025-02-18 04:33:14'),
(4, 4, 100, 'Discount', '2', '2025-02-18 00:00:00', '2025-02-18 04:33:42'),
(5, 4, 200, 'Discount', '2', '2025-02-23 00:00:00', '2025-02-23 05:37:19'),
(6, 1, 200, 'Discount', '2', '2025-03-02 00:00:00', '2025-03-02 07:11:08'),
(7, 6, 10, 'Discount', '2', '2025-04-18 00:00:00', '2025-04-17 20:05:54'),
(8, 953, 10, 'Discount', '2', '2025-04-18 00:00:00', '2025-04-17 20:06:08'),
(9, 1019, 10, 'Discount', '2', '2025-04-21 00:00:00', '2025-04-20 19:21:34'),
(10, 954, 200, 'Discount', '129', '2025-04-21 00:00:00', '2025-04-21 10:35:20'),
(11, 1024, 10, 'Discount', '2', '2025-04-22 00:00:00', '2025-04-22 13:40:13'),
(12, 1016, 10, 'Discount', '2', '2025-04-22 00:00:00', '2025-04-22 13:40:49'),
(13, 1025, 500, 'Discount', '2', '2025-04-23 00:00:00', '2025-04-23 09:17:15'),
(14, 1018, 525, 'Discount', '129', '2025-04-30 00:00:00', '2025-04-30 11:00:04'),
(15, 951, 50, 'Discount', '2', '2025-05-13 00:00:00', '2025-05-13 05:04:16'),
(16, 953, 70, 'Discount', '2', '2025-05-13 00:00:00', '2025-05-13 08:10:07'),
(17, 952, 50, 'Discount', '2', '2025-05-13 00:00:00', '2025-05-13 10:39:30'),
(18, 955, 70, 'Discount', '2', '2025-05-13 00:00:00', '2025-05-13 10:41:21'),
(19, 1026, 30, 'Discount', '2', '2025-05-13 00:00:00', '2025-05-13 11:03:52'),
(20, 934, 50, 'Discount', '2', '2025-05-14 00:00:00', '2025-05-14 06:42:40'),
(21, 934, 70, 'Discount', '2', '2025-05-14 00:00:00', '2025-05-14 06:51:46'),
(22, 934, 100, 'Discount', '2', '2025-05-14 00:00:00', '2025-05-14 06:56:03'),
(23, 934, 500, 'Discount', '2', '2025-05-14 00:00:00', '2025-05-14 07:02:40'),
(24, 934, 100, 'Discount', '2', '2025-05-14 00:00:00', '2025-05-14 07:03:55'),
(25, 934, 50, 'Discount', '2', '2025-05-14 00:00:00', '2025-05-14 10:17:33'),
(26, 955, 100, 'Discount', '2', '2025-05-15 00:00:00', '2025-05-15 05:14:18'),
(27, 1029, 500, 'Discount', '2', '2025-05-15 00:00:00', '2025-05-15 05:30:41'),
(28, 2, 58, 'Discount', '2', '2025-05-21 00:00:00', '2025-05-21 11:40:47'),
(29, 952, 100, 'Discount', '2', '2025-06-01 00:00:00', '2025-06-01 11:09:19'),
(30, 1211, 100, 'Discount', '2', '2025-06-01 00:00:00', '2025-06-01 12:22:07'),
(31, 1019, 700, 'Discount', '188', '2025-06-18 00:00:00', '2025-06-18 16:42:06'),
(32, 954, 17000, 'Discount', '188', '2025-06-18 00:00:00', '2025-06-18 16:42:24'),
(33, 1016, 100, 'Discount', '288', '2025-06-29 00:00:00', '2025-06-29 12:00:19'),
(34, 951, 300, 'Discount', '2', '2025-07-01 00:00:00', '2025-07-01 04:14:08'),
(35, 953, 100, 'Discount', '188', '2025-07-02 00:00:00', '2025-07-02 09:41:14'),
(36, 1030, 100, 'Discount', '288', '2025-07-05 00:00:00', '2025-07-05 10:10:11');

-- --------------------------------------------------------

--
-- Table structure for table `business_targets`
--

CREATE TABLE `business_targets` (
  `id` int NOT NULL,
  `target_name` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `january_target` int DEFAULT '0',
  `february_target` int DEFAULT '0',
  `march_target` int DEFAULT '0',
  `april_target` int DEFAULT '0',
  `may_target` int DEFAULT '0',
  `june_target` int DEFAULT '0',
  `july_target` int DEFAULT '0',
  `august_target` int DEFAULT '0',
  `september_target` int DEFAULT '0',
  `october_target` int DEFAULT '0',
  `november_target` int DEFAULT '0',
  `december_target` int DEFAULT '0',
  `year_target` int DEFAULT '0',
  `year` int NOT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_by` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `business_targets`
--

INSERT INTO `business_targets` (`id`, `target_name`, `type`, `january_target`, `february_target`, `march_target`, `april_target`, `may_target`, `june_target`, `july_target`, `august_target`, `september_target`, `october_target`, `november_target`, `december_target`, `year_target`, `year`, `created_by`, `created_at`, `updated_by`, `updated_at`, `deleted_at`) VALUES
(1, 'Buniess Target', '1', 25000, 25000, 25000, 25000, 25000, 25000, 25000, 25000, 25000, 25000, 5434534, 500120, 900, 2025, 2, '2025-01-12 05:47:50', 2, '2025-01-12 05:47:50', '2025-01-12 11:23:51'),
(2, 'Buniess Target', '1', 25000, 25000, 25000, 25000, 25000, 25000, 25000, 25000, 25000, 25000, 25000, 25000, 900000, 2025, 2, '2025-01-12 05:49:17', 2, '2025-01-12 05:49:17', '2025-01-12 11:24:38'),
(3, 'Buniess Target', '2', 999, 25, 50, 20, 25, 25, 25, 25, 25, 25, 25, 25, 200, 2025, 2, '2025-01-12 05:50:55', 188, '2025-01-12 05:50:55', NULL),
(4, 'Buniess Target', '1', 25000, 25000, 25000, 25000, 25000, 25000, 25000, 25000, 25000, 25000, 25000, 25000, 90000, 2025, 2, '2025-01-12 05:51:54', 2, '2025-01-12 05:51:54', NULL),
(5, 'Buniess Target', '1', 25000, 25000, 25000, 25000, 25000, 25000, 25000, 25000, 25000, 25000, 25000, 25000, 90000, 2025, 2, '2025-01-12 05:54:02', 2, '2025-01-12 05:54:02', NULL),
(6, 'Buniess Target', '1', 2500, 25000, 5000, 25000, 25000, 25000, 25000, 25000, 25000, 25000, 25000, 25000, 900000, 2025, 2, '2025-01-12 05:55:38', 2, '2025-01-12 05:55:38', NULL),
(7, 'Buniess Target', '3', 50, 45, 40, 35, 30, 25, 20, 15, 15, 15, 15, 10, 5, 2025, 2, '2025-01-12 10:13:59', NULL, '2025-01-12 10:13:59', NULL),
(8, 'Buniess Target', '3', 50, 45, 40, 35, 30, 30, 25, 25, 20, 20, 15, 15, 5, 2025, 2, '2025-01-12 10:14:52', NULL, '2025-01-12 10:14:52', NULL),
(9, 'Bill Collection ', '1', 50000, 50000, 50000, 50000, 50000, 50000, 50000, 50000, 50000, 50000, 50000, 50000, 2000000, 2025, 2, '2025-02-03 10:18:20', NULL, '2025-02-03 10:18:20', '2025-05-18 04:22:48'),
(10, 'Raisul', '2', 30, 25, 20, 20, 20, 20, 20, 20, 20, 25, 25, 30, 300000, 2025, 2, '2025-04-08 11:31:26', 2, '2025-04-08 11:31:26', '2025-05-20 11:46:45'),
(11, 'Chack bill', '1', 15, 25, 15, 25, 15, 25, 15, 25, 15, 25, 15, 25, 2025, 2025, 145, '2025-04-21 18:21:05', NULL, '2025-04-21 18:21:05', '2025-04-21 18:22:10');

-- --------------------------------------------------------

--
-- Table structure for table `customer_billing`
--

CREATE TABLE `customer_billing` (
  `id` int NOT NULL,
  `agid` int DEFAULT NULL,
  `cusid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `monthlybill` int DEFAULT '0',
  `totalgenerate` int DEFAULT '0',
  `totalpaid` int DEFAULT '0',
  `totaldiscount` int DEFAULT '0',
  `previousdue` int NOT NULL DEFAULT '0' COMMENT 'Only Add Balance (not previous due)',
  `dueadvance` int DEFAULT '0',
  `generate_at` date DEFAULT NULL,
  `update_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customer_billing`
--

INSERT INTO `customer_billing` (`id`, `agid`, `cusid`, `monthlybill`, `totalgenerate`, `totalpaid`, `totaldiscount`, `previousdue`, `dueadvance`, `generate_at`, `update_at`) VALUES
(1, 1, 'CUS0001', 1000, 6153, 3900, 300, 3, 3150, '2025-05-17', '2025-06-14 11:36:47'),
(2, 2, 'CUS0002', 1000, 6000, 7000, 58, 0, 0, '2025-05-28', '2025-06-28 11:45:14'),
(3, 3, 'CUS0003', 1000, 5000, 4000, 0, 0, 0, '2025-04-01', '2025-05-21 10:39:36'),
(4, 4, 'CUS0004', 1000, 5000, 5650, 350, 0, 0, '2025-04-01', '2025-05-26 07:29:28'),
(5, 5, 'CUS0005', 500, 5000, 5800, 200, 0, 0, '2025-04-01', '2025-05-15 04:20:30'),
(6, 6, 'CUS0006', 500, 2000, 2500, 10, 0, 0, '2025-04-01', '2025-05-15 04:20:30'),
(7, 7, 'CUS0007', 500, 3000, 3500, 0, 0, 0, '2025-04-01', '2025-05-15 04:20:30'),
(8, 934, 'CUS00885', 500, 1000, 1000, 50, 0, 0, '2025-05-15', '2025-05-15 04:40:05'),
(9, 941, 'CUS00941', 500, 4500, 4520, 0, 0, 480, '2025-08-02', '2025-08-09 08:26:39'),
(10, 942, 'CUS00942', 500, 1500, 2000, 0, 0, 0, '2025-04-01', '2025-05-15 04:20:30'),
(11, 944, 'CUS00944', 500, 1000, 500, 0, 0, 1000, '2025-08-02', '2025-08-02 04:06:49'),
(12, 945, 'CUS00945', 500, 500, 500, 0, 0, 500, '2025-04-01', '2025-05-15 04:20:30'),
(13, 946, 'CUS00946', 500, 500, 500, 0, 0, 500, '2025-04-01', '2025-05-15 04:20:30'),
(14, 949, 'CUS00949', 500, 500, 500, 0, 0, 500, '2025-04-01', '2025-05-15 04:20:30'),
(15, 950, 'CUS00950', 500, 500, 500, 0, 0, 500, '2025-04-01', '2025-05-15 04:20:30'),
(16, 951, 'CUS00951', 500, 4000, 3800, 350, 0, 450, '2025-08-02', '2025-08-02 04:06:49'),
(17, 952, 'CUS00952', 500, 4000, 3350, 150, 0, 1000, '2025-08-02', '2025-08-02 04:06:49'),
(18, 953, 'CUS00953', 500, 3500, 4188, 180, -718, 500, '2025-08-02', '2025-08-02 04:06:49'),
(19, 954, 'CUS00954', 1700, 13600, 10000, 17200, 500, -11900, '2025-08-02', '2025-08-02 04:06:49'),
(20, 955, 'CUS00955', 500, 3500, 2930, 170, 0, 500, '2025-08-02', '2025-08-02 04:06:49'),
(21, 956, 'CUS00956', 1918171503, 0, 0, 0, 0, 1918171503, '2025-04-01', '2025-05-15 04:20:30'),
(22, 957, 'CUS00957', 1910060770, 0, 0, 0, 0, 1910060770, '2025-04-01', '2025-05-15 04:20:30'),
(23, 958, 'CUS00958', 1711101038, 0, 0, 0, 0, 1711101038, '2025-04-01', '2025-05-15 04:20:30'),
(24, 959, 'CUS00959', 1913982480, 0, 0, 0, 0, 1913982480, '2025-04-01', '2025-05-15 04:20:30'),
(25, 960, 'CUS00960', 1953831537, 0, 0, 0, 0, 1953831537, '2025-04-01', '2025-05-15 04:20:30'),
(26, 961, 'CUS00961', 1846105444, 0, 0, 0, 0, 1846105444, '2025-04-01', '2025-05-15 04:20:30'),
(27, 962, 'CUS00962', 1870253554, 0, 0, 0, 0, 1870253554, '2025-04-01', '2025-05-15 04:20:30'),
(28, 963, 'CUS00963', 1764758482, 0, 0, 0, 0, 1764758482, '2025-04-01', '2025-05-15 04:20:30'),
(29, 964, 'CUS00964', 1717260319, 0, 0, 0, 0, 1717260319, '2025-04-01', '2025-05-15 04:20:30'),
(30, 965, 'CUS00965', 1913320049, 0, 0, 0, 0, 1913320049, '2025-04-01', '2025-05-15 04:20:30'),
(31, 966, 'CUS00966', 1326873037, 0, 0, 0, 0, 1326873037, '2025-04-01', '2025-05-15 04:20:30'),
(32, 967, 'CUS00967', 1910142226, 0, 0, 0, 0, 1910142226, '2025-04-01', '2025-05-15 04:20:30'),
(33, 968, 'CUS00968', 1715055584, 0, 0, 0, 0, 1715055584, '2025-04-01', '2025-05-15 04:20:30'),
(34, 969, 'CUS00969', 1866524963, 0, 0, 0, 0, 1866524963, '2025-04-01', '2025-05-15 04:20:30'),
(35, 970, 'CUS00970', 1733948120, 0, 0, 0, 0, 1733948120, '2025-04-01', '2025-05-15 04:20:30'),
(36, 971, 'CUS00971', 1932099074, 0, 0, 0, 0, 1932099074, '2025-04-01', '2025-05-15 04:20:30'),
(37, 972, 'CUS00972', 1727722999, 0, 0, 0, 0, 1727722999, '2025-04-01', '2025-05-15 04:20:30'),
(38, 973, 'CUS00973', 1954700731, 0, 0, 0, 0, 1954700731, '2025-04-01', '2025-05-15 04:20:30'),
(39, 974, 'CUS00974', 1990810609, 0, 0, 0, 0, 1990810609, '2025-04-01', '2025-05-15 04:20:30'),
(40, 975, 'CUS00975', 1825726776, 0, 0, 0, 0, 1825726776, '2025-04-01', '2025-05-15 04:20:30'),
(41, 976, 'CUS00976', 1999445144, 0, 0, 0, 0, 1999445144, '2025-04-01', '2025-05-15 04:20:30'),
(42, 977, 'CUS00977', 1764612438, 0, 0, 0, 0, 1764612438, '2025-04-01', '2025-05-15 04:20:30'),
(43, 978, 'CUS00978', 1829275779, 0, 0, 0, 0, 1829275779, '2025-04-01', '2025-05-15 04:20:30'),
(44, 979, 'CUS00979', 1608775688, 0, 0, 0, 0, 1608775688, '2025-04-01', '2025-05-15 04:20:30'),
(45, 980, 'CUS00980', 1713042869, 0, 0, 0, 0, 1713042869, '2025-04-01', '2025-05-15 04:20:30'),
(46, 981, 'CUS00981', 1713532915, 0, 0, 0, 0, 1713532915, '2025-04-01', '2025-05-15 04:20:30'),
(47, 982, 'CUS00982', 1866680113, 0, 0, 0, 0, 1866680113, '2025-04-01', '2025-05-15 04:20:30'),
(48, 983, 'CUS00983', 1952051912, 0, 0, 0, 0, 1952051912, '2025-04-01', '2025-05-15 04:20:30'),
(49, 984, 'CUS00984', 1999306658, 0, 0, 0, 0, 1999306658, '2025-04-01', '2025-05-15 04:20:30'),
(50, 985, 'CUS00985', 1791931311, 0, 0, 0, 0, 1791931311, '2025-04-01', '2025-05-15 04:20:30'),
(51, 986, 'CUS00986', 1317796708, 0, 0, 0, 0, 1317796708, '2025-04-01', '2025-05-15 04:20:30'),
(52, 987, 'CUS00987', 1786768322, 0, 0, 0, 0, 1786768322, '2025-04-01', '2025-05-15 04:20:30'),
(53, 988, 'CUS00988', 1917014204, 0, 0, 0, 0, 1917014204, '2025-04-01', '2025-05-15 04:20:30'),
(54, 989, 'CUS00989', 1841117362, 0, 0, 0, 0, 1841117362, '2025-04-01', '2025-05-15 04:20:30'),
(55, 990, 'CUS00990', 1923750254, 0, 0, 0, 0, 1923750254, '2025-04-01', '2025-05-15 04:20:30'),
(56, 991, 'CUS00991', 1774476451, 0, 0, 0, 0, 1774476451, '2025-04-01', '2025-05-15 04:20:30'),
(57, 992, 'CUS00992', 1877938464, 0, 0, 0, 0, 1877938464, '2025-04-01', '2025-05-15 04:20:30'),
(58, 993, 'CUS00993', 1790586568, 0, 0, 0, 0, 1790586568, '2025-04-01', '2025-05-15 04:20:30'),
(59, 994, 'CUS00994', 1935474652, 0, 0, 0, 0, 1935474652, '2025-04-01', '2025-05-15 04:20:30'),
(60, 995, 'CUS00995', 1728301274, 0, 0, 0, 0, 1728301274, '2025-04-01', '2025-05-15 04:20:30'),
(61, 996, 'CUS00996', 1979481739, 0, 0, 0, 0, 1979481739, '2025-04-01', '2025-05-15 04:20:30'),
(62, 997, 'CUS00997', 1711006476, 0, 0, 0, 0, 1711006476, '2025-04-01', '2025-05-15 04:20:30'),
(63, 998, 'CUS00998', 1952208750, 0, 0, 0, 0, 1952208750, '2025-04-01', '2025-05-15 04:20:30'),
(64, 999, 'CUS00999', 1310888508, 0, 0, 0, 0, 1310888508, '2025-04-01', '2025-05-15 04:20:30'),
(65, 1000, 'CUS01000', 1714650240, 0, 0, 0, 0, 1714650240, '2025-04-01', '2025-05-15 04:20:30'),
(66, 1001, 'CUS01001', 1925760560, 0, 0, 0, 0, 1925760560, '2025-04-01', '2025-05-15 04:20:30'),
(67, 1002, 'CUS01002', 1754830423, 0, 0, 0, 0, 1754830423, '2025-04-01', '2025-05-15 04:20:30'),
(68, 1003, 'CUS01003', 1967365488, 0, 0, 0, 0, 1967365488, '2025-04-01', '2025-05-15 04:20:30'),
(69, 1004, 'CUS01004', 1722119493, 0, 0, 0, 0, 1722119493, '2025-04-01', '2025-05-15 04:20:30'),
(70, 1005, 'CUS01005', 1715820658, 0, 0, 0, 0, 1715820658, '2025-04-01', '2025-05-15 04:20:30'),
(71, 1006, 'CUS01006', 1674061685, 0, 0, 0, 0, 1674061685, '2025-04-01', '2025-05-15 04:20:30'),
(72, 1007, 'CUS01007', 1734821305, 0, 0, 0, 0, 1734821305, '2025-04-01', '2025-05-15 04:20:30'),
(73, 1008, 'CUS01008', 1986561662, 0, 0, 0, 0, 1986561662, '2025-04-01', '2025-05-15 04:20:30'),
(74, 1009, 'CUS01009', 1916270073, 0, 0, 0, 0, 1916270073, '2025-04-01', '2025-05-15 04:20:30'),
(75, 1010, 'CUS01010', 1830307070, 0, 0, 0, 0, 1830307070, '2025-04-01', '2025-05-15 04:20:30'),
(76, 1011, 'CUS01011', 1715603755, 0, 0, 0, 0, 1715603755, '2025-04-01', '2025-05-15 04:20:30'),
(77, 1012, 'CUS01012', 1711709414, 0, 0, 0, 0, 1711709414, '2025-04-01', '2025-05-15 04:20:30'),
(78, 1013, 'CUS01013', 1611318815, 0, 0, 0, 0, 1611318815, '2025-04-01', '2025-05-15 04:20:30'),
(79, 1014, 'CUS01014', 1714531165, 0, 0, 0, 0, 1714531165, '2025-04-01', '2025-05-15 04:20:30'),
(80, 1015, 'CUS01015', 1954900046, 0, 0, 0, 0, 1954900046, '2025-04-01', '2025-05-15 04:20:30'),
(81, 1016, 'CUS00957', 500, 3000, 5100, 110, 0, -2200, '2025-08-02', '2025-08-02 04:06:49'),
(82, 1017, 'CUS01017', 500, 3000, 2500, 0, 0, 500, '2025-08-02', '2025-08-02 04:06:49'),
(83, 1018, 'CUS01018', 500, 2500, 1725, 525, 0, 800, '2025-08-02', '2025-08-02 04:06:49'),
(84, 1019, 'CUS01019', 500, 3000, 1490, 710, 0, 1300, '2025-08-02', '2025-08-02 04:06:49'),
(85, 1020, 'CUS01021', 500, 2500, 2500, 0, 0, 500, '2025-08-02', '2025-08-02 04:06:49'),
(86, 1021, 'CUS01021', 500, 2500, 1500, 0, 0, 1000, '2025-08-02', '2025-08-02 04:06:49'),
(87, 1022, 'CUS01022', 1700, 0, 1700, 0, 0, 0, '2025-04-01', '2025-05-15 04:20:30'),
(88, 1023, 'CUS01023', 1700, 8500, 5100, 0, 0, 3400, '2025-08-02', '2025-08-02 04:06:49'),
(89, 1024, 'CUS01024', 500, 1000, 500, 10, 0, 500, '2025-04-01', '2025-05-15 04:20:30'),
(90, 1025, 'CUS01025', 500, 3000, 9000, 500, 0, -5500, '2025-08-02', '2025-08-02 04:06:49'),
(91, 1026, 'CUS01026', 500, 2500, 1970, 30, 0, 1000, '2025-08-02', '2025-08-02 04:06:49'),
(92, 1027, 'CUS01027', 10, 50, 520, 0, 0, -470, '2025-08-02', '2025-08-02 04:06:49'),
(93, 1028, 'CUS01028', 1700, 0, 0, 0, 0, 0, '2025-04-01', '2025-05-15 04:20:30'),
(94, 1029, 'CUS01029', 500, 10000, 9000, 500, 0, 1000, '2025-08-02', '2025-08-02 04:06:49'),
(95, 1030, 'CUS01030', 500, 2000, 1400, 100, 0, 500, '2025-08-02', '2025-08-02 04:06:49'),
(96, 1031, 'CUS01031', 500, 2000, 1000, 0, 0, 1000, '2025-08-02', '2025-08-02 04:06:49'),
(97, 1032, 'CUS01032', 500, 2000, 1000, 0, 0, 1000, '2025-08-02', '2025-08-02 04:06:49'),
(98, 1033, 'CUS01033', 600, 1800, 600, 0, 0, 1200, '2025-08-02', '2025-08-02 04:06:49'),
(99, 1034, 'CUS01034', 500, 1500, 500, 0, 0, 1000, '2025-08-02', '2025-08-02 04:06:49'),
(100, 1035, 'CUS01035', 500, 2000, 500, 0, 0, 1500, '2025-08-02', '2025-08-02 04:06:49'),
(101, 1036, 'CUS01036', 500, 1600, 1100, 0, 0, 500, '2025-08-02', '2025-08-02 04:06:49'),
(102, 1037, 'CUS01037', 500, 1700, 700, 0, 0, 1000, '2025-08-02', '2025-08-02 04:06:49'),
(103, 1038, 'CUS01037', 600, 0, 0, 0, 0, 0, '2024-01-01', '2025-05-21 17:39:19'),
(104, 1039, 'CUS01039', 899, 2697, 0, 0, 0, 2697, '2025-08-02', '2025-08-02 04:06:49'),
(105, 1040, 'CUS01040', 10, 0, 0, 0, 0, 0, '2024-01-01', '2025-05-24 15:48:26'),
(274, 1209, 'CUS01041', 500, 1500, 500, 0, 0, 1000, '2025-08-02', '2025-08-02 04:06:49'),
(275, 1210, 'CUS01210', 700, 2100, -30, 0, 0, 2130, '2025-08-02', '2025-08-02 04:06:49'),
(276, 1211, 'CUS01211', 500, 2000, 900, 100, 0, 1000, '2025-08-02', '2025-08-02 04:06:49'),
(277, 1212, 'CUS01213', 1000, 0, 0, 0, 0, 0, '2024-01-01', '2025-06-01 04:00:14'),
(278, 1213, 'CUS01214', 1000, 0, 0, 0, 0, 0, '2024-01-01', '2025-06-01 04:00:33'),
(279, 1214, 'CUS01212', 500, 1000, 0, 0, 0, 1000, '2025-08-02', '2025-08-02 04:06:49'),
(280, 1215, 'CUS01215', 700, 1400, 500, 0, 0, 900, '2025-08-02', '2025-08-02 04:06:49'),
(281, 1216, 'CUS01216', 700, 1400, 0, 0, 0, 1400, '2025-08-02', '2025-08-02 04:06:49'),
(282, 1217, 'CUS01217', 600, 1800, 600, 0, 0, 1200, '2025-08-02', '2025-08-02 04:06:49'),
(283, 1218, 'CUS01218', 500, 0, 0, 0, 0, 0, '2024-01-01', '2025-06-23 17:10:04'),
(284, 1219, 'CUS01220', 600, 600, 0, 0, 0, 600, '2025-06-25', '2025-06-25 09:56:14'),
(285, 1220, 'CUS01221', 600, 1200, 0, 0, 0, 1200, '2025-08-02', '2025-08-02 04:06:49'),
(286, 1221, 'CUS01222', 600, 1800, 500, 0, 0, 1300, '2025-08-02', '2025-08-02 04:06:49'),
(287, 1222, 'CUS01223', 600, 0, 0, 0, 0, 0, '2024-01-01', '2025-06-25 10:47:53'),
(288, 1223, 'CUS01224', 600, 0, 0, 0, 0, 0, '2024-01-01', '2025-06-25 10:48:50'),
(289, 1224, 'CUS01225', 600, 0, 0, 0, 0, 0, '2024-01-01', '2025-06-25 10:54:18'),
(290, 1225, 'CUS01225', 500, 0, 0, 0, 0, 0, '2024-01-01', '2025-06-25 10:59:29'),
(291, 1226, 'CUS01226', 500, 1000, 600, 0, 600, 1000, '2025-08-02', '2025-08-02 04:06:49'),
(292, 1227, 'CUS01228', 600, 0, 0, 0, 0, 0, '2024-01-01', '2025-06-25 11:19:36'),
(293, 1228, 'CUS01229', 600, 1200, 0, 0, 0, 1200, '2025-08-02', '2025-08-02 04:06:49'),
(294, 1229, 'CUS01229', 600, 1800, 500, 0, 0, 1300, '2025-08-02', '2025-08-02 04:06:49'),
(295, 1230, 'CUS01231', 600, 1200, 0, 0, 0, 1200, '2025-08-02', '2025-08-02 04:06:49'),
(296, 1231, 'CUS01231', 525, 1575, 0, 0, 0, 1575, '2025-08-02', '2025-08-02 04:06:49'),
(297, 1232, 'CUS01232', 600, 1875, 600, 0, 0, 1275, '2025-08-02', '2025-08-02 04:06:49'),
(298, 1233, 'CUS01233', 525, 1050, 0, 0, 0, 1050, '2025-08-02', '2025-08-02 04:06:49'),
(299, 1234, 'CUS01234', 525, 525, 0, 0, 0, 525, '2025-07-01', '2025-06-30 18:28:13'),
(300, 1235, 'CUS01235', 800, 1600, 0, 0, 0, 1600, '2025-08-02', '2025-08-02 04:06:49'),
(301, 1236, 'CUS01236', 500, 0, 0, 0, 0, 0, '2024-01-01', '2025-07-04 20:21:21'),
(302, 1237, 'CUS01236', 700, 1400, 0, 0, 0, 1400, '2025-08-02', '2025-08-02 04:06:49'),
(303, 1238, 'CUS01238', 500, 0, 0, 0, 0, 0, '2024-01-01', '2025-07-21 05:33:50'),
(304, 1239, 'CUS01238', 500, 0, 0, 0, 0, 0, '2024-01-01', '2025-07-21 05:34:36'),
(307, 1242, 'CUS01242', 500, 0, 0, 0, 0, 0, '2024-01-01', '2025-07-21 06:04:32'),
(308, 1243, 'CUS01243', 500, 1000, 883, 0, 0, 117, '2025-08-02', '2025-08-02 04:06:49'),
(309, 1244, 'CUS01244', 500, 1000, 0, 0, 0, 1000, '2025-08-02', '2025-08-02 04:06:49');

-- --------------------------------------------------------

--
-- Table structure for table `customer_return`
--

CREATE TABLE `customer_return` (
  `return_id` int NOT NULL,
  `product_id` int NOT NULL,
  `customer_id` int NOT NULL,
  `batch_id` varchar(11) COLLATE utf8mb4_general_ci NOT NULL,
  `model_id` text COLLATE utf8mb4_general_ci NOT NULL,
  `stock_id` int NOT NULL,
  `qty_return` int NOT NULL,
  `return_reason` text COLLATE utf8mb4_general_ci,
  `return_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_by` int DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_return`
--

INSERT INTO `customer_return` (`return_id`, `product_id`, `customer_id`, `batch_id`, `model_id`, `stock_id`, `qty_return`, `return_reason`, `return_date`, `created_at`, `created_by`, `updated_at`, `updated_by`, `deleted_at`) VALUES
(1, 2, 5, 'batch_53235', '[\"1\"]', 1, 1, 'Testing Purpose', '2025-02-02 00:00:00', '2025-02-02 00:48:19', 2, '2025-02-02 06:48:19', NULL, NULL),
(2, 2, 5, 'batch_53235', '[\"1\"]', 1, 1, 'Details', '2025-02-04 00:00:00', '2025-02-03 04:15:30', 2, '2025-02-03 10:15:30', NULL, NULL),
(3, 2, 5, 'batch_53235', '[\"1\"]', 1, 1, 'Details', '2025-02-04 00:00:00', '2025-02-03 04:15:36', 2, '2025-02-03 10:15:36', NULL, NULL),
(4, 5, 941, 'batch_66927', '[\"3\"]', 2, 1, 'dfdghdfh', '2025-02-04 00:00:00', '2025-02-03 04:15:54', 2, '2025-02-03 10:15:54', NULL, NULL),
(5, 5, 941, 'batch_66927', '[\"3\"]', 2, 1, '', '2025-02-03 00:00:00', '2025-02-03 04:29:35', 2, '2025-02-03 10:29:35', NULL, NULL),
(6, 2, 942, 'batch_53235', '[\"5\"]', 1, 1, 'Testing', '2025-02-24 00:00:00', '2025-02-23 22:29:16', 2, '2025-02-24 04:29:16', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `daily_activity_log_check`
--

CREATE TABLE `daily_activity_log_check` (
  `id` int NOT NULL,
  `last_delete` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_activity_log_check`
--

INSERT INTO `daily_activity_log_check` (`id`, `last_delete`) VALUES
(1, '2025-08-21');

-- --------------------------------------------------------

--
-- Table structure for table `delete_tbl_agent_log`
--

CREATE TABLE `delete_tbl_agent_log` (
  `id` int NOT NULL,
  `ag_id` int DEFAULT NULL,
  `cus_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '192.168.0.1',
  `type` int DEFAULT NULL,
  `queue_password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ag_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ag_mobile_no` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ag_office_address` mediumtext COLLATE utf8mb4_unicode_ci,
  `mikrotik_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mb` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '0',
  `ag_status` tinyint(1) DEFAULT '0',
  `int_mb` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `taka` int DEFAULT '0',
  `connect_charge` int DEFAULT '0',
  `hold_money_status` tinyint(1) DEFAULT '0',
  `inactive_date` date DEFAULT NULL,
  `ag_email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `regular_mobile` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `blood_group` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `occupation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `national_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mac_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pay_status` tinyint(1) DEFAULT '0',
  `due_status` tinyint(1) DEFAULT '0',
  `bill_status` tinyint(1) DEFAULT '0',
  `payment_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `road` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `house` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `thana` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mikrotik_disconnect` tinyint(1) DEFAULT '0',
  `bill_date` date DEFAULT NULL,
  `bill_cat` int DEFAULT NULL,
  `sms_sent` tinyint(1) DEFAULT '0',
  `billing_person_id` int DEFAULT NULL,
  `zone` int DEFAULT NULL,
  `entry_by` int DEFAULT NULL,
  `entry_date` date DEFAULT NULL,
  `connection_date` date DEFAULT NULL,
  `update_by` int DEFAULT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleter_ip` varchar(11) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleter_user_id` int DEFAULT NULL,
  `deleting_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `id` int NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `node_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`id`, `name`, `description`, `node_id`) VALUES
(1, 'dfhdf', 'dhfh', 6),
(2, 'Router', 'dsgsg', 15),
(3, 'Wire', '12 m', 15),
(4, 'uno', 'uno', 4),
(5, 'Olt 1', '', 11),
(6, '', '', 14),
(7, 'Switch', '16 Port 10/100', 11),
(8, 'fdsf', 'dsfdsf', 28),
(9, 'ytuxru6', 'zeruy7ru7', 35),
(10, 'test', 'dfd', 36),
(11, 'ssss', 'ssssssssss', 39),
(12, 'bb', 'bb', 40),
(13, 'cc', '', 40);

-- --------------------------------------------------------

--
-- Table structure for table `mikrotik_rule`
--

CREATE TABLE `mikrotik_rule` (
  `id` int NOT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `day` int NOT NULL,
  `action` date NOT NULL,
  `implement` int NOT NULL,
  `created_by` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `mikrotik_rule`
--

INSERT INTO `mikrotik_rule` (`id`, `role`, `day`, `action`, `implement`, `created_by`) VALUES
(1, 'disconnect', 0, '2020-04-02', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `mikrotik_user`
--

CREATE TABLE `mikrotik_user` (
  `id` int NOT NULL,
  `user_id` int DEFAULT '0',
  `mik_username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mik_password` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mik_ip` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mik_port` int NOT NULL DEFAULT '8728',
  `status` int NOT NULL DEFAULT '1',
  `entry_by` varchar(11) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `update_by` varchar(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entry_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `mikrotik_user`
--

INSERT INTO `mikrotik_user` (`id`, `user_id`, `mik_username`, `mik_password`, `mik_ip`, `mik_port`, `status`, `entry_by`, `update_by`, `entry_date`) VALUES
(1, 0, 'bsdsoft', '##45**', '103.59.38.89', 7934, 0, NULL, NULL, '2025-04-29'),
(3, 0, 'rasell', 'rasel5833', '103.', 872, 0, NULL, NULL, '2025-05-15'),
(5, 0, 'admin', 'Ramna1217', '118.179.23.110', 8728, 0, NULL, NULL, '2025-05-17'),
(17, 0, 'sagor36', 'sagor36', '139.162.60.189', 1144, 0, NULL, NULL, '2025-06-26'),
(18, 0, 'sagor36', 'sagor36', '139.162.60.189:1144', 9000, 0, NULL, NULL, '2025-06-26'),
(20, 0, '', '', '', 0, 0, NULL, NULL, '2025-06-26'),
(21, 0, 'sadf', 'sdfsf', '103.110.555', 8728, 0, NULL, NULL, '2025-06-26'),
(22, 0, 'mizan', '1122', '', 8728, 0, NULL, NULL, '2025-06-26'),
(23, 0, '', '', '', 2025, 0, NULL, NULL, '2025-06-30'),
(24, 0, 'dfyyfyf', 'amzadkjjopoj', '103.110.96.156', 8728, 0, NULL, NULL, '2025-07-03'),
(25, 0, 'sss', 'uuu', '103.110.96.156', 8728, 0, NULL, NULL, '2025-07-05');

-- --------------------------------------------------------

--
-- Table structure for table `monthly_bill_making_check`
--

CREATE TABLE `monthly_bill_making_check` (
  `id` int NOT NULL,
  `month_year` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tbillgenerate` int NOT NULL DEFAULT '0' COMMENT 'Current Month',
  `tcustomer` int NOT NULL DEFAULT '0' COMMENT 'Previous Month',
  `tactivec` int NOT NULL DEFAULT '0' COMMENT 'Previous Month',
  `tinactivec` int NOT NULL DEFAULT '0' COMMENT 'Previous Month',
  `tdiscontinuec` int NOT NULL DEFAULT '0' COMMENT 'Previous Month',
  `tfreec` int NOT NULL DEFAULT '0' COMMENT 'Previous Month',
  `tdue` int NOT NULL DEFAULT '0' COMMENT 'Previous Month',
  `tpaid` int NOT NULL DEFAULT '0' COMMENT 'Previous Month',
  `tdiscount` int NOT NULL DEFAULT '0' COMMENT 'Previous Month',
  `tbillchange` int NOT NULL DEFAULT '0' COMMENT 'Current Month',
  `tconnectionfee` int NOT NULL DEFAULT '0' COMMENT 'Previous Month	',
  `topening` int NOT NULL DEFAULT '0' COMMENT 'Previous Month	'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `monthly_bill_making_check`
--

INSERT INTO `monthly_bill_making_check` (`id`, `month_year`, `tbillgenerate`, `tcustomer`, `tactivec`, `tinactivec`, `tdiscontinuec`, `tfreec`, `tdue`, `tpaid`, `tdiscount`, `tbillchange`, `tconnectionfee`, `topening`) VALUES
(1, 'Dec-2024', 11200, 17, 17, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(2, 'Jan-2025', 16200, 28, 25, 3, 0, 0, 0, 0, 100, 0, 4200, 1700),
(3, 'Feb-2025', 18200, 32, 29, 3, 0, 0, 0, 0, 0, 0, 1500, 1200),
(4, 'Mar-2025', 19900, 43, 30, 13, 0, 0, 0, 0, 550, 0, 1200, 0),
(30, 'May-2025', 19610, 49, 33, 15, 1, 0, 0, 0, 1275, 0, 2000, 2000),
(16, 'April-2025', 19610, 49, 33, 15, 1, 0, 0, 0, 1275, 0, 2000, 2000),
(31, 'Jun-2025', 25309, 57, 43, 12, 2, 0, 0, 0, 1798, 0, 1500, 30),
(32, 'Jul-2025', 29884, 64, 51, 10, 2, 1, 0, 0, 18000, 0, 3000, 1925),
(33, 'Aug-2025', 39259, 78, 66, 10, 1, 1, 0, 0, 500, 0, 3133, 2177);

-- --------------------------------------------------------

--
-- Table structure for table `nodes`
--

CREATE TABLE `nodes` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` int DEFAULT NULL,
  `parent_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `entry_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `nodes`
--

INSERT INTO `nodes` (`id`, `name`, `description`, `value`, `parent_id`, `user_id`, `entry_date`, `created_at`) VALUES
(4, 'Root', NULL, NULL, NULL, NULL, NULL, '2025-01-22 06:33:44'),
(11, 'Microtik', 'dfsd', NULL, 4, NULL, NULL, '2025-02-03 10:08:24'),
(12, 'Swicth', 'gsdgs', NULL, 11, NULL, NULL, '2025-02-03 10:09:19'),
(13, 'Switch2', 'sdgd', NULL, 11, NULL, NULL, '2025-02-03 10:09:34'),
(14, 'Onu', 'sdgds', NULL, 13, NULL, NULL, '2025-02-03 10:09:45'),
(15, 'AL AMIN (01666666665)', 'gertger', NULL, 14, 942, NULL, '2025-02-03 10:10:00'),
(17, 'Dummy  customer', 'af', NULL, 11, NULL, NULL, '2025-02-03 10:26:45'),
(18, 'Dummy  supplier', '', NULL, 12, NULL, NULL, '2025-02-03 10:26:50'),
(19, 'Dummy  supplier', '', NULL, 12, NULL, NULL, '2025-02-03 10:26:56'),
(21, 'Dummy  supplier', '', NULL, 14, NULL, NULL, '2025-02-03 10:27:14'),
(22, 'AL AMIn -bsd (01624171572)', '', NULL, 19, 5, NULL, '2025-02-05 07:05:55'),
(23, 'Bayazid', '', NULL, 4, NULL, NULL, '2025-02-11 12:11:52'),
(24, 'Bayazid2', '', NULL, 23, NULL, NULL, '2025-02-11 12:12:02'),
(25, 'Bayazid', '', NULL, 24, NULL, NULL, '2025-02-11 12:12:15'),
(26, 'Test_BSD (0122222222222)', 'test', NULL, 14, 7, NULL, '2025-03-02 07:21:12'),
(27, 'Khilkhet', 'xcvsdv', NULL, 25, NULL, NULL, '2025-03-02 07:22:26'),
(28, 'Mikrotik', 'office', NULL, 4, NULL, NULL, '2025-04-26 15:00:28'),
(30, 'Olt 1', '', NULL, 11, NULL, NULL, '2025-05-01 13:54:33'),
(31, '', '', NULL, 25, NULL, NULL, '2025-05-18 07:05:09'),
(33, 'ghty', 'htdyxrftuy', NULL, 11, NULL, NULL, '2025-07-01 09:29:24'),
(34, 'tertww', 'w4tawe4ty', NULL, 33, NULL, NULL, '2025-07-01 09:29:33'),
(35, 'Ashik-BSD (017111191444)', 'rfutrfui', NULL, 34, 1211, NULL, '2025-07-01 09:29:41'),
(36, 'Pritom Sarker (01956845494)', 'test', NULL, 4, 1235, NULL, '2025-07-05 06:47:27'),
(37, 'Rakib Khan (01617701032)', 'test', NULL, 4, 1234, NULL, '2025-07-05 06:47:43'),
(38, 'anamul (01877177477)', 'test', NULL, 4, 1233, NULL, '2025-07-05 06:59:40'),
(39, 'Alaudding66 (5566644)', 'test', NULL, 4, 1231, NULL, '2025-07-05 07:00:00'),
(40, 'aaa', 'aaa', NULL, 18, NULL, NULL, '2025-08-13 09:54:10'),
(41, 'mohammod', 'test', NULL, 31, NULL, NULL, '2025-08-13 09:55:04'),
(42, 'Pritom Sarker (01956845494)', '', NULL, 40, 1235, NULL, '2025-08-13 09:55:19'),
(43, 'Alaudding66 (5566644)', '', NULL, 40, 1231, NULL, '2025-08-13 09:55:32'),
(44, 'zurihul-hb5 (01955922099)', '', NULL, 40, 1226, NULL, '2025-08-13 09:55:40'),
(45, 'ok (01711604346)', '', NULL, 40, 1216, NULL, '2025-08-13 09:55:50'),
(46, 'Robin Ahmed (01705515549)', '', NULL, 40, 1033, NULL, '2025-08-13 09:56:01');

-- --------------------------------------------------------

--
-- Table structure for table `onu_overview`
--

CREATE TABLE `onu_overview` (
  `id` int NOT NULL,
  `olt_ip` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `interface_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `oper_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vendor_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `serial_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uptime` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `onu_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `onu_overview`
--

INSERT INTO `onu_overview` (`id`, `olt_ip`, `interface_name`, `oper_status`, `vendor_id`, `serial_number`, `uptime`, `onu_status`, `created_at`, `last_updated`) VALUES
(1, '172.35.156.14', 'EPON0/1:1', 'Down', '----', 'E0:E8:E6:DE:46:B1', '0d 0h 0m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(2, '172.35.156.14', 'EPON0/1:2', 'Connected', 'XPON', '00:D5:9E:42:60:E2', '26d 2h 5m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(3, '172.35.156.14', 'EPON0/1:3', 'Down', '----', '00:D3:9E:8A:C2:16', '0d 0h 0m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(4, '172.35.156.14', 'EPON0/1:4', 'Connected', 'EPON', '00:D3:9E:8A:CE:4C', '26d 11h 1m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(5, '172.35.156.14', 'EPON0/1:5', 'Connected', 'VSOL', '4C:D7:C8:BD:CC:9C', '25d 3h 58m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(6, '172.35.156.14', 'EPON0/1:6', 'Down', '----', '70:A8:E3:E3:5C:D3', '0d 0h 0m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(7, '172.35.156.14', 'EPON0/1:7', 'Connected', 'HWTC', '00:D5:9E:8A:68:B2', '26d 11h 9m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(8, '172.35.156.14', 'EPON0/1:8', 'Down', '----', '70:A5:6A:0B:4A:D8', '0d 0h 0m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(9, '172.35.156.14', 'EPON0/1:9', 'Connected', 'BDCM', 'AC:12:8E:35:03:70', '26d 11h 32m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(10, '172.35.156.14', 'EPON0/1:10', 'Connected', 'BDCM', 'AC:12:8E:90:D4:C8', '17d 12h 5m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(11, '172.35.156.14', 'EPON0/1:11', 'Connected', 'SMA ', '00:D3:9E:8B:6B:72', '25d 12h 35m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(12, '172.35.156.14', 'EPON0/1:12', 'Connected', 'BDCM', 'AC:12:8E:34:EC:F0', '25d 3h 58m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(13, '172.35.156.14', 'EPON0/1:13', 'Connected', 'BDCM', 'AC:12:8E:1B:3A:20', '25d 12h 24m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(14, '172.35.156.14', 'EPON0/1:14', 'Down', 'XDBC', 'A2:4D:07:21:29:90', '13d 13h 29m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(15, '172.35.156.14', 'EPON0/1:15', 'Connected', 'VSOL', '4C:D7:C8:DF:4C:AA', '25d 3h 58m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(16, '172.35.156.14', 'EPON0/1:16', 'Connected', 'XPON', 'A2:7E:10:09:57:40', '25d 17h 19m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(17, '172.35.156.14', 'EPON0/1:17', 'Connected', 'MONU', '00:6D:61:60:BF:B0', '25d 18h 0m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(18, '172.35.156.14', 'EPON0/2:1', 'Down', '----', 'A2:7E:11:20:18:10', '0d 0h 0m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(19, '172.35.156.14', 'EPON0/2:2', 'Connected', 'VSOL', '6C:68:A4:32:6D:80', '25d 20h 41m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(20, '172.35.156.14', 'EPON0/2:3', 'Connected', 'XPON', 'A2:8D:02:09:6F:00', '25d 20h 41m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(21, '172.35.156.14', 'EPON0/2:4', 'Connected', 'HWTC', '80:D4:A5:62:60:AF', '25d 20h 41m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(22, '172.35.156.14', 'EPON0/2:5', 'Down', '----', 'A8:BF:3C:07:03:A2', '0d 0h 0m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(23, '172.35.156.14', 'EPON0/2:6', 'Connected', 'CDT ', '70:A5:6A:E2:EE:C4', '25d 20h 41m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(24, '172.35.156.14', 'EPON0/2:7', 'Connected', 'HWTC', '78:F5:57:21:58:77', '26d 2h 22m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(25, '172.35.156.14', 'EPON0/2:8', 'Down', '----', 'F8:98:B9:5D:E9:3E', '0d 0h 0m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(26, '172.35.156.14', 'EPON0/2:9', 'Connected', 'HWTC', '74:88:2A:55:FF:45', '0d 0h 0m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(27, '172.35.156.14', 'EPON0/2:10', 'Connected', 'XPON', 'A2:8D:12:29:B9:20', '25d 20h 41m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(28, '172.35.156.14', 'EPON0/2:11', 'Down', '----', 'AC:12:8E:1A:3D:E0', '0d 0h 0m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(29, '172.35.156.14', 'EPON0/2:12', 'Connected', '----', 'C0:7E:40:B0:9E:02', '0d 0h 0m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(30, '172.35.156.14', 'EPON0/2:13', 'Connected', 'BDCM', 'AC:12:8E:1B:45:80', '26d 12h 53m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(31, '172.35.156.14', 'EPON0/2:14', 'Connected', 'XPON', 'A2:7E:09:22:66:B0', '24d 1h 10m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(32, '172.35.156.14', 'EPON0/2:15', 'Down', 'BDCM', 'BC:60:6B:E8:D2:00', '26d 7h 29m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(33, '172.35.156.14', 'EPON0/2:16', 'Connected', 'BDCM', 'AC:12:8E:35:03:80', '24d 1h 41m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(34, '172.35.156.14', 'EPON0/2:17', 'Connected', 'HWTC', 'A0:8C:A3:4C:76:8C', '25d 20h 41m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(35, '172.35.156.14', 'EPON0/2:18', 'Connected', 'BDCM', 'AC:12:8E:35:07:80', '24d 14h 44m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(36, '172.35.156.14', 'EPON0/2:19', 'Connected', 'BDCM', 'AC:12:8E:1A:A1:00', '26d 12h 16m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(37, '172.35.156.14', 'EPON0/2:20', 'Down', '----', 'A2:5E:03:22:FF:30', '0d 0h 0m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(38, '172.35.156.14', 'EPON0/2:21', 'Connected', 'BDCM', 'AC:12:8E:90:D0:6D', '3d 16h 27m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(39, '172.35.156.14', 'EPON0/2:22', 'Connected', 'HWTC', '4C:F9:B4:42:B5:BD', '25d 20h 41m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(40, '172.35.156.14', 'EPON0/2:23', 'Connected', 'CDTC', '50:5B:1D:B3:85:60', '26d 13h 42m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(41, '172.35.156.14', 'EPON0/2:24', 'Connected', 'HWTC', 'D4:F9:A1:D1:D6:46', '26d 11h 12m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(42, '172.35.156.14', 'EPON0/2:25', 'Connected', 'XDBC', 'A2:4E:09:21:E0:50', '23d 11h 58m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(43, '172.35.156.14', 'EPON0/2:26', 'Connected', 'XPON', 'A2:8D:07:05:8B:20', '25d 3h 57m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(44, '172.35.156.14', 'EPON0/2:27', 'Connected', 'CDTC', '50:5B:1D:B3:19:58', '25d 15h 33m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(45, '172.35.156.14', 'EPON0/2:28', 'Down', 'CDTC', '50:5B:1D:B2:6A:E2', '26d 1h 1m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(46, '172.35.156.14', 'EPON0/2:29', 'Connected', 'SMA ', 'A0:7E:10:25:17:D0', '26d 8h 23m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(47, '172.35.156.14', 'EPON0/2:30', 'Connected', 'XDBC', 'A2:4F:05:23:1F:90', '24d 20h 49m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(48, '172.35.156.14', 'EPON0/2:31', 'Down', 'CDT ', '70:A5:6A:0B:4B:10', '26d 13h 30m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(49, '172.35.156.14', 'EPON0/2:32', 'Connected', 'BDCM', 'AC:12:8E:35:01:20', '26d 11h 47m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(50, '172.35.156.14', 'EPON0/2:33', 'Connected', 'VSOL', '4C:D7:C8:BE:3B:DC', '25d 20h 41m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(51, '172.35.156.14', 'EPON0/2:34', 'Down', 'VSOL', '4C:D7:C8:DC:35:6E', '2d 1h 34m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(52, '172.35.156.14', 'EPON0/3:1', 'Connected', 'VSOL', '6C:68:A4:E8:F5:7B', '25d 3h 58m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(53, '172.35.156.14', 'EPON0/3:2', 'Down', '----', 'D0:3E:5C:3F:6D:2A', '0d 0h 0m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(54, '172.35.156.14', 'EPON0/3:3', 'Connected', 'BDCM', 'AC:12:8E:4E:35:30', '26d 9h 0m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(55, '172.35.156.14', 'EPON0/3:4', 'Connected', 'DBCE', 'A2:3E:06:16:2C:50', '25d 3h 57m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(56, '172.35.156.14', 'EPON0/3:5', 'Connected', 'VSOL', '4C:D7:C8:DF:4A:C2', '25d 3h 58m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(57, '172.35.156.14', 'EPON0/4:1', 'Down', '----', 'A2:6C:12:17:5E:F0', '0d 0h 0m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(58, '172.35.156.14', 'EPON0/4:2', 'Connected', 'VSOL', '1C:EF:03:AE:6A:05', '3d 19h 45m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(59, '172.35.156.14', 'EPON0/4:3', 'Connected', 'CDT ', '70:A5:6A:0A:E9:AA', '0d 0h 0m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(60, '172.35.156.14', 'EPON0/4:4', 'Connected', 'BDCM', 'AC:12:8E:4E:42:E0', '25d 20h 41m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(61, '172.35.156.14', 'EPON0/4:5', 'Connected', 'BDCM', 'AC:12:8E:90:D0:A7', '12d 21h 10m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(62, '172.35.156.14', 'EPON0/4:6', 'Connected', 'CDTC', '50:5B:1D:B2:6B:5C', '26d 10h 7m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(63, '172.35.156.14', 'EPON0/4:7', 'Connected', 'HWTC', '54:51:1B:AB:A0:45', '25d 20h 41m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(64, '172.35.156.14', 'EPON0/4:8', 'Connected', 'CDT ', '70:A5:6A:0A:E9:AE', '25d 20h 41m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(65, '172.35.156.14', 'EPON0/4:9', 'Connected', 'HWTC', 'A2:6E:05:26:24:D0', '25d 20h 41m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(66, '172.35.156.14', 'EPON0/4:10', 'Connected', 'XDBC', 'A2:4E:01:14:22:D0', '25d 20h 41m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(67, '172.35.156.14', 'EPON0/4:11', 'Down', 'CDT ', '70:A5:6A:79:CD:60', '4d 0h 55m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(68, '172.35.156.14', 'EPON0/4:12', 'Connected', 'VSOL', '6C:68:A4:70:D5:9A', '25d 20h 41m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(69, '172.35.156.14', 'EPON0/4:13', 'Connected', 'VSOL', '6C:68:A4:46:3B:64', '25d 20h 41m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(70, '172.35.156.14', 'EPON0/4:14', 'Connected', 'HWTC', '18:09:B9:3A:26:A6', '25d 20h 41m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(71, '172.35.156.14', 'EPON0/4:15', 'Connected', 'HWTC', '00:D5:9E:78:6D:F0', '25d 21h 7m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(72, '172.35.156.14', 'EPON0/4:16', 'Connected', 'TPLG', '24:2F:D0:1B:F9:CA', '25d 20h 41m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(73, '172.35.156.14', 'EPON0/4:17', 'Connected', 'XPON', 'A2:8D:02:10:39:30', '25d 20h 41m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(74, '172.35.156.14', 'EPON0/4:18', 'Down', 'DBCG', '4C:AE:1C:19:1C:32', '21d 13h 18m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(75, '172.35.156.14', 'EPON0/4:19', 'Down', '----', 'A2:8D:07:05:E1:70', '0d 0h 0m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(76, '172.35.156.14', 'EPON0/4:20', 'Connected', 'HWTC', '00:D5:9E:74:F4:64', '25d 20h 41m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(77, '172.35.156.14', 'EPON0/4:21', 'Connected', 'HWTC', '90:17:AC:B4:79:70', '25d 20h 41m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(78, '172.35.156.14', 'EPON0/4:22', 'Connected', 'SMA ', '00:D3:9F:75:02:E2', '26d 10h 34m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(79, '172.35.156.14', 'EPON0/4:23', 'Connected', 'XDBC', 'A2:4E:03:19:E0:B0', '25d 20h 41m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(80, '172.35.156.14', 'EPON0/4:24', 'Connected', 'VSOL', '70:B6:4F:62:1E:B0', '25d 20h 41m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(81, '172.35.156.14', 'EPON0/4:25', 'Connected', 'SMA ', '00:D3:9E:8B:6D:EE', '25d 20h 41m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(82, '172.35.156.14', 'EPON0/5:1', 'Connected', 'ZTE ', 'A0:94:6A:00:8B:9E', '26d 1h 47m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(83, '172.35.156.14', 'EPON0/6:1', 'Down', 'SMA ', '00:D3:9E:8B:70:9A', '23d 10h 44m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(84, '172.35.156.14', 'EPON0/6:2', 'Connected', 'VSOL', '6C:68:A4:62:2F:90', '25d 3h 58m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(85, '172.35.156.14', 'EPON0/7:1', 'Connected', 'XPON', 'A2:8C:11:22:0A:80', '26d 12h 27m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(86, '172.35.156.14', 'EPON0/7:2', 'Connected', 'VSOL', '4C:D7:C8:A2:0D:7C', '24d 14h 44m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38'),
(87, '172.35.156.14', 'EPON0/7:3', 'Connected', 'HWTC', '4C:F9:A1:64:CB:B5', '24d 14h 44m', NULL, '2025-09-01 04:44:38', '2025-09-01 05:33:38');

-- --------------------------------------------------------

--
-- Table structure for table `onu_status`
--

CREATE TABLE `onu_status` (
  `id` int NOT NULL,
  `olt_ip` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `interface_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `serial` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `distance` int DEFAULT NULL,
  `tx_power` float DEFAULT NULL,
  `rx_power` float DEFAULT NULL,
  `download_bytes` bigint DEFAULT NULL,
  `upload_bytes` bigint DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `onu_status`
--

INSERT INTO `onu_status` (`id`, `olt_ip`, `interface_name`, `serial`, `distance`, `tx_power`, `rx_power`, `download_bytes`, `upload_bytes`, `last_updated`) VALUES
(1, '172.35.156.14', 'EPON0/1:1', 'E0:E8:E6:DE:46:B1', 0, NULL, NULL, 0, 0, '2025-09-01 05:33:42'),
(2, '172.35.156.14', 'EPON0/1:2', '00:D5:9E:42:60:E2', 225, 2, -12.2, 387071364171, 48119858777, '2025-09-01 05:33:42'),
(3, '172.35.156.14', 'EPON0/1:3', '00:D3:9E:8A:C2:16', 0, NULL, NULL, 0, 0, '2025-09-01 05:33:42'),
(4, '172.35.156.14', 'EPON0/1:4', '00:D3:9E:8A:CE:4C', 2497, 2.5, -13.6, 63797966, 59007544, '2025-09-01 05:33:42'),
(5, '172.35.156.14', 'EPON0/1:5', '4C:D7:C8:BD:CC:9C', 288, 2.4, -13.4, 42723457469, 5599034826, '2025-09-01 05:33:42'),
(6, '172.35.156.14', 'EPON0/1:6', '70:A8:E3:E3:5C:D3', 0, NULL, NULL, 0, 0, '2025-09-01 05:33:42'),
(7, '172.35.156.14', 'EPON0/1:7', '00:D5:9E:8A:68:B2', 436, 2.3, -19.3, 153859193588, 7123646115, '2025-09-01 05:33:42'),
(8, '172.35.156.14', 'EPON0/1:8', '70:A5:6A:0B:4A:D8', 0, NULL, NULL, 0, 0, '2025-09-01 05:33:42'),
(9, '172.35.156.14', 'EPON0/1:9', 'AC:12:8E:35:03:70', 329, 1.9, -15.2, 95533232042, 5389262277, '2025-09-01 05:33:42'),
(10, '172.35.156.14', 'EPON0/1:10', 'AC:12:8E:90:D4:C8', 390, 2.2, -14.6, 69464154641, 23095046524, '2025-09-01 05:33:42'),
(11, '172.35.156.14', 'EPON0/1:11', '00:D3:9E:8B:6B:72', 321, 2.5, -13.7, 15180377395, 1500222945, '2025-09-01 05:33:42'),
(12, '172.35.156.14', 'EPON0/1:12', 'AC:12:8E:34:EC:F0', 369, 2, -15.1, 101995266023, 8583324836, '2025-09-01 05:33:42'),
(13, '172.35.156.14', 'EPON0/1:13', 'AC:12:8E:1B:3A:20', 398, 1.7, -14.6, 71710803477, 3952552082, '2025-09-01 05:33:42'),
(14, '172.35.156.14', 'EPON0/1:14', 'A2:4D:07:21:29:90', 0, NULL, NULL, 115158302372, 8096100239, '2025-09-01 05:33:42'),
(15, '172.35.156.14', 'EPON0/1:15', '4C:D7:C8:DF:4C:AA', 444, 2.5, -19.7, 102220480054, 14974005941, '2025-09-01 05:33:42'),
(16, '172.35.156.14', 'EPON0/1:16', 'A2:7E:10:09:57:40', 984, 2, -18.2, 226682865627, 22237135986, '2025-09-01 05:33:42'),
(17, '172.35.156.14', 'EPON0/1:17', '00:6D:61:60:BF:B0', 535, 1.5, -19.7, 42868737192, 4082718321, '2025-09-01 05:33:42'),
(18, '172.35.156.14', 'EPON0/2:1', 'A2:7E:11:20:18:10', 0, NULL, NULL, 0, 0, '2025-09-01 05:33:42'),
(19, '172.35.156.14', 'EPON0/2:2', '6C:68:A4:32:6D:80', 1512, 2.2, -17.9, 293271922928, 19176535460, '2025-09-01 05:33:42'),
(20, '172.35.156.14', 'EPON0/2:3', 'A2:8D:02:09:6F:00', 1550, 2.2, -16.2, 243752913283, 12506176509, '2025-09-01 05:33:42'),
(21, '172.35.156.14', 'EPON0/2:4', '80:D4:A5:62:60:AF', 1477, 2, -22.6, 609298860568, 39202588024, '2025-09-01 05:33:42'),
(22, '172.35.156.14', 'EPON0/2:5', 'A8:BF:3C:07:03:A2', 0, NULL, NULL, 0, 0, '2025-09-01 05:33:42'),
(23, '172.35.156.14', 'EPON0/2:6', '70:A5:6A:E2:EE:C4', 1889, 1.9, -16.3, 913000904324, 68224550785, '2025-09-01 05:33:42'),
(24, '172.35.156.14', 'EPON0/2:7', '78:F5:57:21:58:77', 1105, 2.2, -18.9, 313138068430, 13264079632, '2025-09-01 05:33:42'),
(25, '172.35.156.14', 'EPON0/2:8', 'F8:98:B9:5D:E9:3E', 0, NULL, NULL, 0, 0, '2025-09-01 05:33:42'),
(26, '172.35.156.14', 'EPON0/2:9', '74:88:2A:55:FF:45', 1451, 2.3, -9.4, 0, 0, '2025-09-01 05:33:42'),
(27, '172.35.156.14', 'EPON0/2:10', 'A2:8D:12:29:B9:20', 1485, 2, -14.3, 58045174757, 5981111500, '2025-09-01 05:33:42'),
(28, '172.35.156.14', 'EPON0/2:11', 'AC:12:8E:1A:3D:E0', 0, NULL, NULL, 0, 0, '2025-09-01 05:33:42'),
(29, '172.35.156.14', 'EPON0/2:12', 'C0:7E:40:B0:9E:02', 1425, 2.2, -17.7, 262710127639, 15427544967, '2025-09-01 05:33:42'),
(30, '172.35.156.14', 'EPON0/2:13', 'AC:12:8E:1B:45:80', 3274, 1.9, -12.4, 320337751118, 19231842469, '2025-09-01 05:33:42'),
(31, '172.35.156.14', 'EPON0/2:14', 'A2:7E:09:22:66:B0', 1568, 2, -15.8, 442434607512, 38623233285, '2025-09-01 05:33:42'),
(32, '172.35.156.14', 'EPON0/2:15', 'BC:60:6B:E8:D2:00', 0, NULL, NULL, 346802041661, 29879063132, '2025-09-01 05:33:42'),
(33, '172.35.156.14', 'EPON0/2:16', 'AC:12:8E:35:03:80', 1548, 1.9, -17.5, 193933949071, 12794090947, '2025-09-01 05:33:42'),
(34, '172.35.156.14', 'EPON0/2:17', 'A0:8C:A3:4C:76:8C', 1084, 2.2, -18.6, 329114656072, 25254016326, '2025-09-01 05:33:42'),
(35, '172.35.156.14', 'EPON0/2:18', 'AC:12:8E:35:07:80', 4872, 2.1, -22.2, 90569755829, 37354748425, '2025-09-01 05:33:42'),
(36, '172.35.156.14', 'EPON0/2:19', 'AC:12:8E:1A:A1:00', 3518, 1.9, -12.1, 406791493488, 23441202611, '2025-09-01 05:33:42'),
(37, '172.35.156.14', 'EPON0/2:20', 'A2:5E:03:22:FF:30', 0, NULL, NULL, 0, 0, '2025-09-01 05:33:42'),
(38, '172.35.156.14', 'EPON0/2:21', 'AC:12:8E:90:D0:6D', 3784, 2.4, -23.4, 98952408426, 9069720391, '2025-09-01 05:33:42'),
(39, '172.35.156.14', 'EPON0/2:22', '4C:F9:B4:42:B5:BD', 1845, 2.4, -15.9, 88770655189, 6205711222, '2025-09-01 05:33:42'),
(40, '172.35.156.14', 'EPON0/2:23', '50:5B:1D:B3:85:60', 1696, 1.7, -14.7, 122156018252, 11255353020, '2025-09-01 05:33:42'),
(41, '172.35.156.14', 'EPON0/2:24', 'D4:F9:A1:D1:D6:46', 1560, 2, -7.3, 286593093012, 24099289372, '2025-09-01 05:33:42'),
(42, '172.35.156.14', 'EPON0/2:25', 'A2:4E:09:21:E0:50', 1401, 2.2, -19.2, 311995481152, 44280532005, '2025-09-01 05:33:42'),
(43, '172.35.156.14', 'EPON0/2:26', 'A2:8D:07:05:8B:20', 4324, 1.9, -21.9, 303800709239, 10654300917, '2025-09-01 05:33:42'),
(44, '172.35.156.14', 'EPON0/2:27', '50:5B:1D:B3:19:58', 4684, 1.9, -21.5, 316027551026, 23352322612, '2025-09-01 05:33:42'),
(45, '172.35.156.14', 'EPON0/2:28', '50:5B:1D:B2:6A:E2', 0, NULL, NULL, 35862853479, 19316399824, '2025-09-01 05:33:42'),
(46, '172.35.156.14', 'EPON0/2:29', 'A0:7E:10:25:17:D0', 5734, 2, -19.2, 138985303324, 16774473325, '2025-09-01 05:33:42'),
(47, '172.35.156.14', 'EPON0/2:30', 'A2:4F:05:23:1F:90', 3137, 1.8, -15.7, 326641530352, 22782374164, '2025-09-01 05:33:42'),
(48, '172.35.156.14', 'EPON0/2:31', '70:A5:6A:0B:4B:10', 0, NULL, NULL, 546141480194, 15253203437, '2025-09-01 05:33:42'),
(49, '172.35.156.14', 'EPON0/2:32', 'AC:12:8E:35:01:20', 4907, 1.9, -20.8, 392216552566, 15582466057, '2025-09-01 05:33:42'),
(50, '172.35.156.14', 'EPON0/2:33', '4C:D7:C8:BE:3B:DC', 1945, 1.7, -15.7, 180266680372, 39466722855, '2025-09-01 05:33:42'),
(51, '172.35.156.14', 'EPON0/2:34', '4C:D7:C8:DC:35:6E', 0, NULL, NULL, 4760118, 5115784, '2025-09-01 05:33:42'),
(52, '172.35.156.14', 'EPON0/3:1', '6C:68:A4:E8:F5:7B', 976, 2.5, -16.7, 93498662550, 13938723278, '2025-09-01 05:33:42'),
(53, '172.35.156.14', 'EPON0/3:2', 'D0:3E:5C:3F:6D:2A', 0, NULL, NULL, 0, 0, '2025-09-01 05:33:42'),
(54, '172.35.156.14', 'EPON0/3:3', 'AC:12:8E:4E:35:30', 1075, 2, -12.8, 332422971544, 72437427057, '2025-09-01 05:33:42'),
(55, '172.35.156.14', 'EPON0/3:4', 'A2:3E:06:16:2C:50', 1021, 2, -13.6, 145032626176, 35947832915, '2025-09-01 05:33:42'),
(56, '172.35.156.14', 'EPON0/3:5', '4C:D7:C8:DF:4A:C2', 872, 2.9, -13.8, 81408988668, 5031968395, '2025-09-01 05:33:42'),
(57, '172.35.156.14', 'EPON0/4:1', 'A2:6C:12:17:5E:F0', 0, NULL, NULL, 0, 0, '2025-09-01 05:33:42'),
(58, '172.35.156.14', 'EPON0/4:2', '1C:EF:03:AE:6A:05', 3994, 2.3, -7, 593681389765, 54212605546, '2025-09-01 05:33:42'),
(59, '172.35.156.14', 'EPON0/4:3', '70:A5:6A:0A:E9:AA', 4740, 1.4, -12.8, 305881312075, 13796725659, '2025-09-01 05:33:42'),
(60, '172.35.156.14', 'EPON0/4:4', 'AC:12:8E:4E:42:E0', 4924, 1.8, -8.4, 778032361677, 51681510702, '2025-09-01 05:33:42'),
(61, '172.35.156.14', 'EPON0/4:5', 'AC:12:8E:90:D0:A7', 1767, 2.2, -14.6, 528285421112, 17396055544, '2025-09-01 05:33:42'),
(62, '172.35.156.14', 'EPON0/4:6', '50:5B:1D:B2:6B:5C', 1164, 1.9, -11.3, 297239286143, 31027739732, '2025-09-01 05:33:42'),
(63, '172.35.156.14', 'EPON0/4:7', '54:51:1B:AB:A0:45', 3974, 2.3, -12.3, 221207363923, 21030763907, '2025-09-01 05:33:42'),
(64, '172.35.156.14', 'EPON0/4:8', '70:A5:6A:0A:E9:AE', 4718, 1.5, -16.3, 399649627116, 23177389786, '2025-09-01 05:33:42'),
(65, '172.35.156.14', 'EPON0/4:9', 'A2:6E:05:26:24:D0', 3989, 2.4, -15, 350442450373, 32382943226, '2025-09-01 05:33:42'),
(66, '172.35.156.14', 'EPON0/4:10', 'A2:4E:01:14:22:D0', 4310, 2.3, -8.3, 411900459489, 18345431301, '2025-09-01 05:33:42'),
(67, '172.35.156.14', 'EPON0/4:11', '70:A5:6A:79:CD:60', 0, NULL, NULL, 5757844, 6164766, '2025-09-01 05:33:42'),
(68, '172.35.156.14', 'EPON0/4:12', '6C:68:A4:70:D5:9A', 4396, 1.5, -15.8, 190699563309, 6705969958, '2025-09-01 05:33:42'),
(69, '172.35.156.14', 'EPON0/4:13', '6C:68:A4:46:3B:64', 4329, 2.2, -9, 325325031453, 13269563334, '2025-09-01 05:33:42'),
(70, '172.35.156.14', 'EPON0/4:14', '18:09:B9:3A:26:A6', 4250, 2.1, -27.9, 459986570539, 30662117680, '2025-09-01 05:33:42'),
(71, '172.35.156.14', 'EPON0/4:15', '00:D5:9E:78:6D:F0', 4256, 2.1, -12.8, 224241958178, 24083096901, '2025-09-01 05:33:42'),
(72, '172.35.156.14', 'EPON0/4:16', '24:2F:D0:1B:F9:CA', 4787, 1.7, -16.5, 404174149311, 27816859936, '2025-09-01 05:33:42'),
(73, '172.35.156.14', 'EPON0/4:17', 'A2:8D:02:10:39:30', 2816, 2.2, -20.6, 936381130206, 32512064289, '2025-09-01 05:33:42'),
(74, '172.35.156.14', 'EPON0/4:18', '4C:AE:1C:19:1C:32', 0, NULL, NULL, 211548530411, 20509510756, '2025-09-01 05:33:42'),
(75, '172.35.156.14', 'EPON0/4:19', 'A2:8D:07:05:E1:70', 0, NULL, NULL, 0, 0, '2025-09-01 05:33:42'),
(76, '172.35.156.14', 'EPON0/4:20', '00:D5:9E:74:F4:64', 2787, 1.9, -22, 368871582045, 20845551477, '2025-09-01 05:33:42'),
(77, '172.35.156.14', 'EPON0/4:21', '90:17:AC:B4:79:70', 2695, 2, -19.8, 345907146882, 19234263494, '2025-09-01 05:33:42'),
(78, '172.35.156.14', 'EPON0/4:22', '00:D3:9F:75:02:E2', 1798, 2.6, -15.7, 324124929697, 23738859389, '2025-09-01 05:33:42'),
(79, '172.35.156.14', 'EPON0/4:23', 'A2:4E:03:19:E0:B0', 4386, 2.2, -8.7, 443887986810, 39304790155, '2025-09-01 05:33:42'),
(80, '172.35.156.14', 'EPON0/4:24', '70:B6:4F:62:1E:B0', 4245, 1.9, -21.6, 392831750387, 30780347475, '2025-09-01 05:33:42'),
(81, '172.35.156.14', 'EPON0/4:25', '00:D3:9E:8B:6D:EE', 619, 2.6, -14.2, 36851838855, 3024610632, '2025-09-01 05:33:42'),
(82, '172.35.156.14', 'EPON0/5:1', 'A0:94:6A:00:8B:9E', 914, 2.1, -4.3, 917740137034, 31136230225, '2025-09-01 05:33:42'),
(83, '172.35.156.14', 'EPON0/6:1', '00:D3:9E:8B:70:9A', 0, NULL, NULL, 318506482517, 14669233837, '2025-09-01 05:33:42'),
(84, '172.35.156.14', 'EPON0/6:2', '6C:68:A4:62:2F:90', 831, 2.5, -9.8, 586082920029, 26903159116, '2025-09-01 05:33:42'),
(85, '172.35.156.14', 'EPON0/7:1', 'A2:8C:11:22:0A:80', 833, 1.9, -13.6, 419949999681, 23737460828, '2025-09-01 05:33:42'),
(86, '172.35.156.14', 'EPON0/7:2', '4C:D7:C8:A2:0D:7C', 805, 2.7, -8.7, 375754622589, 45804402827, '2025-09-01 05:33:42'),
(87, '172.35.156.14', 'EPON0/7:3', '4C:F9:A1:64:CB:B5', 593, 2.2, -7.1, 84164477749, 14466626679, '2025-09-01 05:33:42');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int NOT NULL,
  `product_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `sku` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `unit_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `category_id` int NOT NULL,
  `created_by` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_by` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `product_name`, `sku`, `unit_type`, `category_id`, `created_by`, `created_at`, `updated_by`, `updated_at`, `deleted_at`) VALUES
(1, 'TP-LINK Router', 'TP-0001', '1', 1, '2', '2025-01-05 15:48:15', NULL, '2025-02-01 18:18:24', '2025-02-01'),
(2, 'Switch', 'sw-0001', '1', 1, '2', '2025-01-05 15:48:29', '2', '2025-02-24 10:16:10', NULL),
(5, 'Router', 'ON-0001', '1', 1, '2', '2025-02-03 16:12:51', NULL, NULL, NULL),
(6, 'meter', 'gjhbjl', '2', 1, '2', '2025-04-19 12:56:25', NULL, NULL, NULL),
(7, 'tex', '1236', '3', 1, '2', '2025-05-21 11:49:40', NULL, NULL, NULL),
(8, 'ofc', 'no', '2', 1, '188', '2025-06-24 18:26:35', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `category_id` int NOT NULL,
  `category_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `created_by` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_by` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_by` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_categories`
--

INSERT INTO `product_categories` (`category_id`, `category_name`, `description`, `created_by`, `created_at`, `updated_by`, `updated_at`, `deleted_by`) VALUES
(1, 'Router', 'Router tplink', '2', '2025-01-05 15:47:34', '1', '2025-07-02 12:58:05', NULL),
(2, 'Switch', 'Switch', '2', '2025-01-05 15:48:01', NULL, NULL, NULL),
(3, 'Switch123', 'tree', '2', '2025-01-23 17:36:25', '2', '2025-01-23 17:36:32', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_model`
--

CREATE TABLE `product_model` (
  `id` int NOT NULL,
  `purchase_id` int NOT NULL,
  `product_id` int DEFAULT NULL,
  `batch_id` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `model_no` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `serial_no` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `expire_date` date NOT NULL,
  `sold` int NOT NULL DEFAULT '0',
  `returned` int NOT NULL DEFAULT '0' COMMENT '0=false,1=true',
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_by` int DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_model`
--

INSERT INTO `product_model` (`id`, `purchase_id`, `product_id`, `batch_id`, `model_no`, `serial_no`, `expire_date`, `sold`, `returned`, `created_by`, `created_at`, `updated_by`, `updated_at`, `deleted_at`) VALUES
(1, 1, 2, 'batch_53235', 'model-001', 'sl-00001', '2025-02-02', 1, 0, 2, '2025-02-02 06:41:50', 2, '2025-02-03 11:43:04', NULL),
(2, 2, 5, 'batch_66927', 'model-012', 'sl-0023', '2025-02-28', 0, 1, 2, '2025-02-03 10:14:05', 2, '2025-02-03 10:16:29', NULL),
(3, 2, 5, 'batch_66927', 'model-013', 'sl-0024', '2025-02-28', 0, 1, 2, '2025-02-03 10:14:05', 2, '2025-02-03 10:52:03', NULL),
(4, 2, 5, 'batch_66927', 'model-014', 'sl-0025', '2025-02-28', 0, 1, 2, '2025-02-03 10:14:05', 2, '2025-02-03 10:52:03', NULL),
(5, 3, 2, 'batch_53235', 'mf-01', 'sf-01', '2025-02-28', 1, 0, 2, '2025-02-24 04:15:50', 155, '2025-04-22 17:19:36', NULL),
(6, 3, 2, 'batch_53235', 'mf-02', 'sf-02', '2025-02-28', 1, 0, 2, '2025-02-24 04:15:50', 2, '2025-02-24 04:27:53', NULL),
(7, 3, 2, 'batch_53235', 'mf-03', 'sf-03', '2025-02-28', 1, 0, 2, '2025-02-24 04:15:50', 2, '2025-02-24 04:28:20', NULL),
(8, 4, 2, 'batch_53235', 'Cudy', '1', '2025-06-30', 0, 0, 2, '2025-06-02 05:21:40', NULL, '2025-06-02 05:21:40', NULL),
(9, 4, 2, 'batch_53235', 'Cudy', '1', '2025-06-03', 1, 0, 2, '2025-06-02 05:21:40', 188, '2025-06-21 13:26:19', NULL),
(10, 4, 2, 'batch_53235', 'Cudy', '1', '2025-06-03', 0, 0, 2, '2025-06-02 05:21:40', NULL, '2025-06-02 05:21:40', NULL),
(11, 4, 2, 'batch_53235', 'Cudy', '1', '2025-06-17', 1, 0, 2, '2025-06-02 05:21:40', 188, '2025-06-21 13:26:19', NULL),
(12, 4, 2, 'batch_53235', 'Cudy', '1', '2025-06-05', 0, 0, 2, '2025-06-02 05:21:40', NULL, '2025-06-02 05:21:40', NULL),
(13, 4, 2, 'batch_53235', 'Cudy', '1', '2025-06-05', 0, 0, 2, '2025-06-02 05:21:40', NULL, '2025-06-02 05:21:40', NULL),
(14, 4, 2, 'batch_53235', 'Cudy', '1', '2025-06-04', 0, 0, 2, '2025-06-02 05:21:40', NULL, '2025-06-02 05:21:40', NULL),
(15, 4, 2, 'batch_53235', 'Cudy', '1', '2025-06-03', 0, 0, 2, '2025-06-02 05:21:40', NULL, '2025-06-02 05:21:40', NULL),
(16, 4, 2, 'batch_53235', 'Cudy', '1', '2025-06-03', 0, 0, 2, '2025-06-02 05:21:40', NULL, '2025-06-02 05:21:40', NULL),
(17, 4, 2, 'batch_53235', 'Cudy', '1', '2025-06-03', 0, 0, 2, '2025-06-02 05:21:40', NULL, '2025-06-02 05:21:40', NULL),
(18, 5, 7, 'batch_85210', '222', '123', '2025-06-01', 1, 0, 188, '2025-06-21 13:03:11', 188, '2025-06-21 13:25:19', NULL),
(19, 5, 7, 'batch_85210', '333', '124', '2025-06-01', 0, 0, 188, '2025-06-21 13:03:11', 188, '2025-06-21 13:25:19', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `purchase_id` int NOT NULL,
  `product_id` int NOT NULL,
  `supplier_id` int NOT NULL,
  `quantity` int NOT NULL,
  `return_qty` int NOT NULL DEFAULT '0',
  `purchase_date` date NOT NULL,
  `created_by` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_by` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchases`
--

INSERT INTO `purchases` (`purchase_id`, `product_id`, `supplier_id`, `quantity`, `return_qty`, `purchase_date`, `created_by`, `created_at`, `updated_by`, `updated_at`, `deleted_at`) VALUES
(1, 2, 1, 1, 0, '2025-02-02', '2', '2025-02-02 12:41:50', NULL, NULL, NULL),
(2, 5, 1, 3, 3, '2025-02-03', '2', '2025-02-03 16:14:05', '2', '2025-02-03 10:52:03', NULL),
(3, 2, 1, 3, 0, '2025-02-28', '2', '2025-02-24 10:15:50', NULL, NULL, NULL),
(4, 2, 1, 10, 0, '2025-06-02', '2', '2025-06-02 11:21:40', NULL, NULL, NULL),
(5, 7, 1, 2, 0, '2025-06-14', '188', '2025-06-21 19:03:11', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `sale_id` int NOT NULL,
  `product_id` int NOT NULL,
  `model_id` text COLLATE utf8mb4_general_ci NOT NULL,
  `batch_id` varchar(11) COLLATE utf8mb4_general_ci NOT NULL,
  `customer_id` int NOT NULL,
  `quantity` int NOT NULL,
  `sale_date` date NOT NULL,
  `stock_id` int NOT NULL,
  `return_qty` int NOT NULL DEFAULT '0',
  `created_by` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_by` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`sale_id`, `product_id`, `model_id`, `batch_id`, `customer_id`, `quantity`, `sale_date`, `stock_id`, `return_qty`, `created_by`, `created_at`, `updated_by`, `updated_at`, `deleted_at`) VALUES
(1, 2, '[\"1\"]', 'batch_53235', 5, 1, '2025-02-02', 1, 1, '2', '2025-02-02 06:47:42', '2', '2025-02-02 06:48:19', NULL),
(2, 5, '[\"3\"]', 'batch_66927', 941, 1, '2025-02-03', 2, 1, '2', '2025-02-03 10:14:57', '2', '2025-02-03 10:15:54', NULL),
(3, 2, '[\"1\"]', 'batch_53235', 2, 1, '2025-02-03', 1, 0, '2', '2025-02-03 11:43:04', NULL, NULL, NULL),
(4, 2, '[\"5\",\"6\"]', 'batch_53235', 942, 2, '2025-02-24', 1, 1, '2', '2025-02-24 04:27:53', '2', '2025-02-24 04:29:16', NULL),
(5, 2, '[\"7\"]', 'batch_53235', 3, 1, '2025-02-24', 1, 0, '2', '2025-02-24 04:28:20', NULL, NULL, NULL),
(6, 2, '[\"5\"]', 'batch_53235', 5, 1, '2025-04-22', 1, 0, '155', '2025-04-22 17:19:36', NULL, NULL, NULL),
(7, 7, '[\"18\"]', 'batch_85210', 953, 1, '2025-06-09', 3, 0, '188', '2025-06-21 13:04:13', '188', '2025-06-21 13:25:19', NULL),
(8, 2, '[\"9\",\"11\"]', 'batch_53235', 937, 2, '2025-06-18', 1, 0, '188', '2025-06-21 13:26:19', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sms`
--

CREATE TABLE `sms` (
  `id` int NOT NULL,
  `smshead` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `smsbody` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `status` int NOT NULL,
  `update_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `sms`
--

INSERT INTO `sms` (`id`, `smshead`, `smsbody`, `status`, `update_date`) VALUES
(1, 'active', 'Dear {CUSTOMER_NAME},\r\nYour customer id: {CUSTOMER_ID}, Username: {IP_ADDRESS}, Due: {DUE_AMOUNT} Tk', 1, '2025-07-30 09:11:08'),
(2, 'single_due_sms', 'Dear {CUSTOMER_NAME},\r\nCustomer ID: {CUSTOMER_ID}, Username: {IP_ADDRESS}, Package: {PACKAGE_NAME}, Current Due: {DUE_AMOUNT} Tk\r\nThank you for being with us.\r\n', 2, '2025-07-22 05:06:09'),
(3, 'paid_sms', 'Dear {CUSTOMER_NAME},  \r\nYour bill has been successfully paid. Paid Amount: {PAID_AMOUNT}, Due Amount: {DUE_AMOUNT}  \r\nThank you for being with us.\r\n', 3, '2025-07-22 05:05:21'),
(4, 'new_customer_sms', 'Dear {CUSTOMER_NAME}, ID: {CUSTOMER_ID}, Username:{IP_ADDRESS}, Package:{PACKAGE_NAME}, Monthly Bill:{MONTHLY_BILL}', 4, '2025-07-17 09:00:33'),
(5, 'general_sms', 'Eid Mubarak, {CUSTOMER_NAME}! \r\nMay this Eid bring joy, peace, and blessings to your life.\r\n\r\nThank you for being with us.  \r\nWishing you happiness always!', 5, '2025-07-21 08:08:48'),
(6, 'inactive', 'Dear {CUSTOMER_NAME}, ID: {CUSTOMER_ID}  \r\nYour connection will be disconnected tomorrow due to unpaid bill of {DUE_AMOUNT} Tk.  \r\nPlease pay today to avoid interruption.  \r\nThank you.\r\n', 6, '2025-08-07 10:59:09'),
(8, 'inactive', 'Dear  {CUSTOMER_NAME}, \r\nYour Advance Payment of  {ADVANCE_AMOUNT} Taka has been received. \r\nThank you for being with us.', 7, '2025-08-11 09:50:16'),
(9, 'inactive', 'Dear {CUSTOMER_NAME},  \r\nWe have received your complaint and our team will address it shortly.  \r\nThank you for your patience.', 8, '2025-08-13 09:51:50'),
(10, 'inactive', 'Dear {EMPLOYEE_NAME}, \r\nNew complaint ({COMPLAIN_TYPE}) assigned.\r\nCustomer Name: {CUSTOMER_NAME}, Customer Phone: {CUSTOMER_PHONE}. \r\nPlease take action.', 9, '2025-08-13 09:51:44');

-- --------------------------------------------------------

--
-- Table structure for table `stock`
--

CREATE TABLE `stock` (
  `stock_id` int NOT NULL,
  `product_id` int NOT NULL,
  `current_stock` int NOT NULL DEFAULT '0',
  `batch_id` varchar(11) COLLATE utf8mb4_general_ci NOT NULL,
  `supplier_id` int NOT NULL,
  `created_by` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_by` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock`
--

INSERT INTO `stock` (`stock_id`, `product_id`, `current_stock`, `batch_id`, `supplier_id`, `created_by`, `created_at`, `updated_by`, `updated_at`, `deleted_at`) VALUES
(1, 2, 8, 'batch_53235', 1, '2', '2025-02-02 06:41:50', '188', '2025-06-21 13:26:19', NULL),
(2, 5, 0, 'batch_66927', 1, '2', '2025-02-03 10:14:05', '2', '2025-02-03 10:52:03', NULL),
(3, 7, 1, 'batch_85210', 1, '188', '2025-06-21 13:03:11', '188', '2025-06-21 13:25:19', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `stock_marketing_enable`
--

CREATE TABLE `stock_marketing_enable` (
  `id` int NOT NULL,
  `stock` int NOT NULL,
  `marketing` int NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `supplier_id` int NOT NULL,
  `supplier_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `contact_info` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_by` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_by` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`supplier_id`, `supplier_name`, `contact_info`, `address`, `created_by`, `created_at`, `updated_by`, `updated_at`, `deleted_at`) VALUES
(1, 'BDCOM Online Ltd.', '+88 09666 333 666', '	JL Bhaban (5th floor), House # 1, Road # 1, Gulshan Avenue, Gulshan-1, Dhaka-1212, Bangladesh', '2', '2024-12-26 14:07:50', '2', '2025-06-26 17:05:27', '2025-06-26'),
(2, 'AmberIT Limited', '09611123123', 'Navana Tower (7th floor) 45 Gulshan South C/A, Circle 1, Dhaka – 1212, Bangladesh', '2', '2024-12-26 14:08:38', NULL, '2025-01-27 17:43:08', '2025-01-27'),
(3, 'Rajib', '01888888888', 'TzjakKkzvz', '282', '2025-06-26 17:05:47', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `supplier_return`
--

CREATE TABLE `supplier_return` (
  `return_id` int NOT NULL,
  `product_id` int NOT NULL,
  `purchase_id` int NOT NULL,
  `supplier_id` int NOT NULL,
  `quantity_returned` int NOT NULL,
  `model_id` text COLLATE utf8mb4_general_ci NOT NULL,
  `return_reason` text COLLATE utf8mb4_general_ci,
  `return_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_by` int DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplier_return`
--

INSERT INTO `supplier_return` (`return_id`, `product_id`, `purchase_id`, `supplier_id`, `quantity_returned`, `model_id`, `return_reason`, `return_date`, `created_by`, `created_at`, `updated_by`, `updated_at`, `deleted_at`) VALUES
(1, 5, 2, 1, 1, '[\"2\"]', 'eryery', '2025-02-03 00:00:00', 2, '2025-02-03 10:16:29', NULL, '2025-02-03 10:16:29', '0000-00-00 00:00:00'),
(2, 5, 2, 1, 2, '[\"3\",\"4\"]', 'xcbdfd', '2025-02-03 00:00:00', 2, '2025-02-03 10:52:03', NULL, '2025-02-03 10:52:03', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_account`
--

CREATE TABLE `tbl_account` (
  `acc_id` int NOT NULL,
  `cus_id` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agent_id` int DEFAULT NULL,
  `acc_head` int DEFAULT NULL COMMENT ' 2222=employee expense',
  `acc_sub_head` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `acc_amount` int DEFAULT '0',
  `pay_amount` int DEFAULT '0',
  `acc_description` mediumtext COLLATE utf8mb4_unicode_ci,
  `acc_type` int DEFAULT NULL COMMENT '1=Expense 2=Other income 3=bill Collection 4=connection charge 5= Opening Income',
  `entry_by` int DEFAULT NULL,
  `entry_date` date DEFAULT NULL,
  `update_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_account`
--

INSERT INTO `tbl_account` (`acc_id`, `cus_id`, `agent_id`, `acc_head`, `acc_sub_head`, `acc_amount`, `pay_amount`, `acc_description`, `acc_type`, `entry_by`, `entry_date`, `update_by`, `created_at`, `last_update`) VALUES
(1, 'CUS0001', 1, NULL, NULL, 500, 0, 'Connection Charge', 4, 2, '2024-12-17', 2, '2024-12-17 10:42:40', '2024-12-17 10:42:40'),
(2, 'CUS0001', 1, NULL, NULL, 300, 0, 'Opening and Running Month amount Payment', 5, 2, '2024-12-17', 2, '2024-12-17 10:42:40', '2024-12-17 10:42:40'),
(4, 'CUS0002', 2, NULL, NULL, 200, 0, 'Opening and Running Month amount Payment', 5, 2, '2024-12-17', 2, '2024-12-17 10:46:06', '2024-12-17 10:46:06'),
(5, 'CUS0003', 3, NULL, NULL, 500, 0, 'Connection Charge', 4, 2, '2024-12-17', 2, '2024-12-17 10:48:08', '2024-12-17 10:48:08'),
(6, 'CUS0003', 3, NULL, NULL, 500, 0, 'Opening and Running Month amount Payment', 5, 2, '2024-12-17', 2, '2024-12-17 10:48:08', '2024-12-17 10:48:08'),
(7, 'CUS0004', 4, NULL, NULL, 500, 0, 'Connection Charge', 4, 2, '2024-12-17', 2, '2024-12-17 10:50:40', '2024-12-17 10:50:40'),
(8, 'CUS0004', 4, NULL, NULL, 200, 0, 'Opening and Running Month amount Payment', 5, 2, '2024-12-17', 2, '2024-12-17 10:50:40', '2024-12-17 10:50:40'),
(9, 'CUS0005', 5, NULL, NULL, 500, 0, 'Connection Charge', 4, 2, '2024-12-17', 2, '2024-12-17 11:11:34', '2024-12-17 11:11:34'),
(10, 'CUS0005', 5, NULL, NULL, 700, 0, 'Payment for bill', 3, 2, '2024-12-17', NULL, '2024-12-17 11:11:34', '2024-12-17 11:11:34'),
(11, 'CUS0005', 5, NULL, NULL, 300, 0, 'Payment for bill', 3, 2, '2024-12-17', NULL, '2024-12-17 11:19:22', '2024-12-17 11:19:22'),
(12, 'CUS0001', 1, NULL, NULL, 1000, 0, 'Payment for bill', 3, 2, '2024-12-17', NULL, '2024-12-17 11:20:55', '2024-12-17 11:20:55'),
(14, 'CUS0004', 4, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2024-12-17', NULL, '2024-12-17 11:51:34', '2024-12-17 11:51:34'),
(15, 'CUS0006', 6, NULL, NULL, 300, 0, 'Opening and Running Month amount Payment', 5, 2, '2024-12-17', 2, '2024-12-17 12:16:29', '2024-12-17 12:16:29'),
(16, 'CUS0007', 7, NULL, NULL, 700, 0, 'Connection Charge', 4, 2, '2024-12-18', 2, '2024-12-18 05:51:54', '2024-12-18 05:51:54'),
(17, 'CUS0007', 7, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2024-12-18', NULL, '2024-12-18 05:51:54', '2024-12-18 05:51:54'),
(18, 'CUS0004', 4, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2024-12-21', NULL, '2024-12-21 11:31:13', '2024-12-21 11:31:13'),
(19, 'CUS0003', 3, NULL, NULL, 1000, 0, 'Payment for bill', 3, 2, '2024-12-21', NULL, '2024-12-21 11:31:18', '2024-12-21 11:31:18'),
(20, 'CUS0002', 2, NULL, NULL, 1000, 0, 'Payment for bill', 3, 2, '2024-12-21', NULL, '2024-12-21 11:31:22', '2024-12-21 11:31:22'),
(25, 'CUS00941', 941, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-01-12', NULL, '2025-01-12 11:45:28', '2025-01-12 11:45:28'),
(27, 'CUS0004', 4, NULL, NULL, 1000, 0, 'Payment for bill', 3, 2, '2025-01-12', NULL, '2025-01-12 11:46:10', '2025-01-12 11:46:10'),
(28, 'CUS00942', 942, NULL, NULL, 500, 0, 'Connection Charge', 4, 2, '2025-01-15', 2, '2025-01-15 11:36:39', '2025-01-15 11:36:39'),
(29, 'CUS00942', 942, NULL, NULL, 200, 0, 'Opening and Running Month amount Payment', 5, 2, '2025-01-15', 2, '2025-01-15 11:36:39', '2025-01-15 11:36:39'),
(30, 'CUS00944', 944, NULL, NULL, 500, 0, 'Connection Charge', 4, 2, '2025-01-21', 2, '2025-01-21 12:18:29', '2025-01-21 12:18:29'),
(31, 'CUS00944', 944, NULL, NULL, 500, 0, 'Opening and Running Month amount Payment', 5, 2, '2025-01-21', 2, '2025-01-21 12:18:29', '2025-01-21 12:18:29'),
(32, 'CUS00945', 945, NULL, NULL, 500, 0, 'Connection Charge', 4, 2, '2025-01-21', 2, '2025-01-21 12:20:02', '2025-01-21 12:20:02'),
(33, 'CUS00945', 945, NULL, NULL, 500, 0, 'Opening and Running Month amount Payment', 5, 2, '2025-01-21', 2, '2025-01-21 12:20:02', '2025-01-21 12:20:02'),
(34, NULL, NULL, 1, '2', 1000, 0, 'dfgdf', 1, 2, '2025-01-28', 2, '2025-01-28 08:50:43', '2025-01-28 08:50:43'),
(35, NULL, NULL, 1, '2', 232, 0, 'dsfg', 1, 2, '2025-01-30', 2, '2025-01-30 11:19:44', '2025-01-30 11:19:44'),
(36, NULL, NULL, 1, '2', 65, 0, '', 2, 2, '2025-01-30', 2, '2025-01-30 12:45:47', '2025-01-30 12:45:47'),
(37, NULL, NULL, 1, '2', 123456, 0, '1000', 2, 2, '2025-01-30', 2, '2025-01-30 12:46:07', '2025-01-30 12:46:07'),
(38, NULL, NULL, 7, '6', 1000, 0, 'dsfsg', 2, 2, '2025-01-30', 2, '2025-01-30 13:00:04', '2025-05-23 01:58:01'),
(39, 'CUS00946', 946, NULL, NULL, 500, 0, 'Connection Charge', 4, 2, '2025-02-01', 2, '2025-02-01 05:34:36', '2025-02-01 05:34:36'),
(40, 'CUS00946', 946, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-02-01', NULL, '2025-02-01 05:34:36', '2025-02-01 05:34:36'),
(41, 'CUS00945', 945, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-02-01', NULL, '2025-02-01 11:26:42', '2025-02-01 11:26:42'),
(42, 'CUS00944', 944, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-02-01', NULL, '2025-02-01 11:27:12', '2025-02-01 11:27:12'),
(43, 'CUS00942', 942, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-02-01', NULL, '2025-02-01 11:51:23', '2025-02-01 11:51:23'),
(45, NULL, NULL, 1, '4', 1000, 0, 'CAT-6', 2, 2, '2025-02-01', 2, '2025-02-01 11:53:40', '2025-05-23 01:58:15'),
(46, NULL, NULL, 8, '4', 500, 0, 'CAT-6', 1, 2, '2025-02-01', 2, '2025-02-01 11:57:36', '2025-05-23 01:58:34'),
(47, 'CUS0007', 7, NULL, NULL, 1000, 0, 'Payment for bill', 3, 2, '2025-02-02', NULL, '2025-02-02 16:45:01', '2025-02-02 16:45:01'),
(49, 'CUS0006', 6, NULL, NULL, 1000, 0, 'Payment for bill', 3, 2, '2025-02-02', NULL, '2025-02-02 17:14:23', '2025-02-02 17:14:23'),
(50, 'CUS00949', 949, NULL, NULL, 100, 0, 'Connection Charge', 4, 2, '2025-02-03', 2, '2025-02-03 06:53:18', '2025-02-03 06:53:18'),
(51, 'CUS00949', 949, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-02-03', NULL, '2025-02-03 06:53:18', '2025-02-03 06:53:18'),
(52, 'CUS00950', 950, NULL, NULL, 100, 0, 'Connection Charge', 4, 2, '2025-02-03', 2, '2025-02-03 06:54:27', '2025-02-03 06:54:27'),
(53, 'CUS00950', 950, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-02-03', NULL, '2025-02-03 06:54:27', '2025-02-03 06:54:27'),
(54, 'CUS0005', 5, NULL, NULL, 1500, 0, 'Payment for bill', 3, 2, '2025-02-03', NULL, '2025-02-03 09:48:10', '2025-02-03 09:48:10'),
(55, NULL, NULL, 1, '4', 200, 0, 'Cat- 6  Cable', 2, 2, '2025-02-03', 2, '2025-02-03 09:50:01', '2025-02-03 09:50:01'),
(56, NULL, NULL, 1, '2', 1000, 0, 'CAT-6', 2, 2, '2025-02-03', 2, '2025-02-03 11:03:53', '2025-02-03 11:03:53'),
(57, 'CUS0005', 5, NULL, NULL, 300, 0, 'Payment for bill', 3, 2, '2025-02-04', NULL, '2025-02-04 09:49:48', '2025-02-04 09:49:48'),
(58, 'CUS00951', 951, NULL, NULL, 300, 0, 'Payment for bill', 3, 2, '2025-02-11', NULL, '2025-02-11 12:18:37', '2025-02-11 12:18:37'),
(59, 'CUS0001', 1, NULL, NULL, 1900, 0, 'Payment for bill', 3, 2, '2025-02-11', NULL, '2025-02-11 12:19:45', '2025-02-11 12:19:45'),
(60, 'CUS00952', 952, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-02-11', NULL, '2025-02-11 12:22:56', '2025-02-11 12:22:56'),
(61, 'CUS0002', 2, NULL, NULL, 2000, 0, 'Payment for bill', 3, 2, '2025-02-11', NULL, '2025-02-11 16:52:44', '2025-02-11 16:52:44'),
(62, 'CUS00953', 953, NULL, NULL, 0, 0, 'Opening and Running Month amount Payment', 5, 2, '2025-02-17', 2, '2025-02-17 10:39:29', '2025-02-17 10:39:29'),
(63, 'CUS0004', 4, NULL, NULL, 50, 0, 'Payment for bill', 3, 2, '2025-02-18', NULL, '2025-02-18 04:33:42', '2025-02-18 04:33:42'),
(64, 'CUS0003', 3, NULL, NULL, 1500, 0, 'Payment for bill', 3, 2, '2025-02-18', NULL, '2025-02-18 17:31:31', '2025-02-18 17:31:31'),
(65, 'CUS0003', 3, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-02-18', NULL, '2025-02-18 17:32:04', '2025-02-18 17:32:04'),
(66, 'CUS0004', 4, NULL, NULL, 800, 0, 'Payment for bill', 3, 2, '2025-02-23', NULL, '2025-02-23 05:37:19', '2025-02-23 05:37:19'),
(67, 'CUS00951', 951, NULL, NULL, 100, 0, 'Payment for bill', 3, 2, '2025-02-24', NULL, '2025-02-24 04:20:35', '2025-02-24 04:20:35'),
(69, 'CUS00954', 954, NULL, NULL, 500, 0, 'Connection Charge', 4, 2, '2025-02-24', 2, '2025-02-24 04:22:52', '2025-02-24 04:22:52'),
(70, 'CUS00954', 954, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-02-24', NULL, '2025-02-24 04:22:52', '2025-02-24 04:22:52'),
(71, 'CUS00951', 951, NULL, NULL, 90, 0, 'Payment for bill', 3, 2, '2025-02-24', NULL, '2025-02-24 09:51:52', '2025-02-24 09:51:52'),
(72, 'CUS00954', 954, NULL, NULL, 1200, 0, 'Payment for bill', 3, 2, '2025-02-24', NULL, '2025-02-24 09:52:41', '2025-02-24 09:52:41'),
(73, 'CUS00955', 955, NULL, NULL, 0, 0, 'Opening and Running Month amount Payment', 5, 2, '2025-02-25', 2, '2025-02-25 11:07:16', '2025-02-25 11:07:16'),
(74, 'CUS0002', 2, NULL, NULL, 800, 0, 'Payment for bill', 3, 2, '2025-03-02', NULL, '2025-03-02 05:33:26', '2025-03-02 05:33:26'),
(75, 'CUS0001', 1, NULL, NULL, 1000, 0, 'Payment for bill', 3, 2, '2025-03-02', NULL, '2025-03-02 07:11:08', '2025-03-02 07:11:08'),
(76, 'CUS0004', 4, NULL, NULL, 800, 0, 'Payment for bill', 3, 2, '2025-03-04', NULL, '2025-03-04 04:06:25', '2025-03-04 04:06:25'),
(77, 'CUS0002', 2, NULL, NULL, 200, 0, 'Payment for bill', 3, 2, '2025-03-04', NULL, '2025-03-04 04:14:24', '2025-03-04 04:14:24'),
(78, 'CUS01017', 1017, NULL, NULL, 30, 0, 'Opening and Running Month amount Payment', 5, 2, '2025-03-08', 2, '2025-03-08 17:21:49', '2025-03-08 17:21:49'),
(79, 'CUS00951', 951, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-03-16', NULL, '2025-03-16 06:33:33', '2025-03-16 06:33:33'),
(80, 'CUS00954', 954, NULL, NULL, 1700, 0, 'Payment for bill', 3, 2, '2025-03-20', NULL, '2025-03-20 06:20:54', '2025-03-20 06:20:54'),
(81, 'CUS00955', 955, NULL, NULL, 200, 0, 'Payment for bill', 3, 2, '2025-03-24', NULL, '2025-03-24 06:35:54', '2025-03-24 06:35:54'),
(82, 'CUS0002', 2, NULL, NULL, 1000, 0, 'Payment for bill', 3, 2, '2025-04-08', NULL, '2025-04-08 07:22:50', '2025-04-08 07:22:50'),
(83, 'CUS00951', 951, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-04-12', NULL, '2025-04-12 08:34:36', '2025-04-12 08:34:36'),
(84, 'CUS0004', 4, NULL, NULL, 1000, 0, 'Payment for bill', 3, 2, '2025-04-12', NULL, '2025-04-12 08:35:04', '2025-04-12 08:35:04'),
(85, 'CUS0005', 5, NULL, NULL, 2000, 0, 'Payment for bill', 3, 2, '2025-04-13', NULL, '2025-04-13 06:59:44', '2025-04-13 06:59:44'),
(86, 'CUS0006', 6, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-04-17', NULL, '2025-04-17 11:18:56', '2025-04-17 11:18:56'),
(88, 'CUS0007', 7, NULL, NULL, 1000, 0, 'Payment for bill', 3, 2, '2025-04-18', NULL, '2025-04-17 20:05:27', '2025-04-17 20:05:27'),
(90, 'CUS0006', 6, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-04-18', NULL, '2025-04-17 20:05:54', '2025-04-17 20:05:54'),
(91, 'CUS00953', 953, NULL, NULL, 1000, 0, 'Payment for bill', 3, 2, '2025-04-18', NULL, '2025-04-17 20:06:08', '2025-04-17 20:06:08'),
(92, 'CUS01018', 1018, NULL, NULL, 500, 0, 'Opening and Running Month amount Payment', 5, 2, '2025-04-19', 2, '2025-04-19 05:22:14', '2025-04-19 05:22:14'),
(93, 'CUS00941', 941, NULL, NULL, 1000, 0, 'Payment for bill', 3, 2, '2025-04-19', NULL, '2025-04-19 08:10:49', '2025-04-19 08:10:49'),
(94, 'CUS01019', 1019, NULL, NULL, 490, 0, 'Payment for bill', 3, 2, '2025-04-21', NULL, '2025-04-20 19:21:33', '2025-04-20 19:21:33'),
(95, 'CUS00942', 942, NULL, NULL, 1000, 0, 'Payment for bill', 3, 2, '2025-04-21', NULL, '2025-04-21 05:24:13', '2025-04-21 05:24:13'),
(96, 'CUS00952', 952, NULL, NULL, 1000, 0, 'Payment for bill', 3, 2, '2025-04-21', NULL, '2025-04-21 05:24:55', '2025-04-21 05:24:55'),
(97, 'CUS00954', 954, NULL, NULL, 700, 0, 'Payment for bill', 3, 129, '2025-04-21', NULL, '2025-04-21 10:35:20', '2025-04-21 10:35:20'),
(98, 'CUS01021', 1021, NULL, NULL, 500, 0, 'Connection Charge', 4, 145, '2025-04-22', 145, '2025-04-21 18:32:30', '2025-04-21 18:32:30'),
(99, 'CUS01021', 1021, NULL, NULL, 0, 0, 'Opening and Running Month amount Payment', 5, 145, '2025-04-22', 145, '2025-04-21 18:32:30', '2025-04-21 18:32:30'),
(101, 'CUS01022', 1022, NULL, NULL, 1000, 0, 'Connection Charge', 4, 148, '2025-04-22', 148, '2025-04-22 09:44:20', '2025-04-22 09:44:20'),
(102, 'CUS01022', 1022, NULL, NULL, 0, 0, 'Opening and Running Month amount Payment', 5, 148, '2025-04-22', 148, '2025-04-22 09:44:20', '2025-04-22 09:44:20'),
(103, 'CUS01023', 1023, NULL, NULL, 500, 0, 'Connection Charge', 4, 149, '2025-04-22', 149, '2025-04-22 09:46:28', '2025-04-22 09:46:28'),
(104, 'CUS01023', 1023, NULL, NULL, 500, 0, 'Opening and Running Month amount Payment', 5, 149, '2025-04-22', 149, '2025-04-22 09:46:28', '2025-04-22 09:46:28'),
(105, 'CUS01024', 1024, NULL, NULL, 35, 0, 'Payment for bill', 3, 145, '2025-04-22', NULL, '2025-04-22 13:39:18', '2025-04-22 13:39:18'),
(106, 'CUS01024', 1024, NULL, NULL, 465, 0, 'Payment for bill', 3, 2, '2025-04-22', NULL, '2025-04-22 13:40:13', '2025-04-22 13:40:13'),
(107, 'CUS01017', 1017, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-04-22', NULL, '2025-04-22 13:40:34', '2025-04-22 13:40:34'),
(108, 'CUS00956', 1016, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-04-22', NULL, '2025-04-22 13:40:49', '2025-04-22 13:40:49'),
(109, 'CUS00954', 954, NULL, NULL, 800, 0, 'Payment for bill', 3, 2, '2025-04-23', NULL, '2025-04-23 04:52:06', '2025-04-23 04:52:06'),
(110, 'CUS00955', 955, NULL, NULL, 100, 0, 'Payment for bill', 3, 2, '2025-04-23', NULL, '2025-04-23 05:25:57', '2025-04-23 05:25:57'),
(111, 'CUS01025', 1025, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-04-23', NULL, '2025-04-23 09:17:15', '2025-04-23 09:17:15'),
(112, 'CUS01026', 1026, NULL, NULL, 500, 0, 'Opening and Running Month amount Payment', 5, 2, '2025-04-23', 2, '2025-04-23 09:19:16', '2025-04-23 09:19:16'),
(113, 'CUS01027', 1027, NULL, NULL, 500, 0, 'Opening and Running Month amount Payment', 5, 168, '2025-04-26', 168, '2025-04-26 09:45:45', '2025-04-26 09:45:45'),
(114, 'CUS0005', 5, NULL, NULL, 1000, 0, 'Payment for bill', 3, 174, '2025-04-27', NULL, '2025-04-27 07:05:33', '2025-04-27 07:05:33'),
(115, 'CUS0006', 6, NULL, NULL, 500, 0, 'Payment for bill', 3, 129, '2025-04-28', NULL, '2025-04-28 10:23:05', '2025-04-28 10:23:05'),
(116, 'CUS0007', 7, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-04-29', NULL, '2025-04-29 04:37:26', '2025-04-29 04:37:26'),
(118, 'CUS01019', 1019, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-04-29', NULL, '2025-04-29 05:26:00', '2025-04-29 05:26:00'),
(119, 'CUS01026', 1026, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-04-29', NULL, '2025-04-29 06:07:36', '2025-04-29 06:07:36'),
(121, 'CUS0007', 7, NULL, NULL, 500, 0, 'Payment for bill', 3, 129, '2025-05-03', NULL, '2025-05-03 09:03:18', '2025-05-03 09:03:18'),
(122, 'CUS01028', 1028, NULL, NULL, 0, 0, 'Opening and Running Month amount Payment', 5, 2, '2025-05-03', 2, '2025-05-03 17:33:18', '2025-05-03 17:33:18'),
(124, 'CUS01022', 1022, NULL, NULL, 1700, 0, 'Payment for bill', 3, 129, '2025-05-04', NULL, '2025-05-04 11:01:32', '2025-05-04 11:01:32'),
(125, 'CUS01020', 1020, NULL, NULL, 1000, 0, 'Payment for bill', 3, 129, '2025-05-04', NULL, '2025-05-04 11:01:51', '2025-05-04 11:01:51'),
(126, 'CUS01029', 1029, NULL, NULL, 0, 0, 'Opening and Running Month amount Payment', 5, 2, '2025-05-06', 2, '2025-05-05 18:57:31', '2025-05-05 18:57:31'),
(127, 'CUS01027', 1027, NULL, NULL, 10, 0, 'Payment for bill', 3, 2, '2025-05-06', NULL, '2025-05-06 05:23:32', '2025-05-06 05:23:32'),
(128, 'CUS01025', 1025, NULL, NULL, 1000, 0, 'Payment for bill', 3, 2, '2025-05-06', NULL, '2025-05-06 05:23:40', '2025-05-06 05:23:40'),
(129, 'CUS00941', 941, NULL, NULL, 1000, 0, 'Payment for bill', 3, 2, '2025-05-06', NULL, '2025-05-06 10:19:13', '2025-05-06 10:19:13'),
(130, 'CUS00942', 942, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-05-06', NULL, '2025-05-06 10:19:42', '2025-05-06 10:19:42'),
(131, NULL, NULL, 1, '6', 1000, 0, 'test - cable sell', 2, 2, '2025-05-06', 2, '2025-05-06 10:28:48', '2025-05-06 10:28:48'),
(133, 'CUS00951', 951, NULL, NULL, 1000, 0, 'Payment for bill', 3, 2, '2025-05-13', NULL, '2025-05-13 05:04:16', '2025-05-13 05:04:16'),
(134, 'CUS00952', 952, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-05-13', NULL, '2025-05-13 07:32:30', '2025-05-13 07:32:30'),
(135, 'CUS00953', 953, NULL, NULL, 1000, 0, 'Payment for bill', 3, 2, '2025-05-13', NULL, '2025-05-13 08:10:07', '2025-05-13 08:10:07'),
(136, 'CUS00954', 954, NULL, NULL, 3400, 0, 'Payment for bill', 3, 2, '2025-05-13', NULL, '2025-05-13 08:12:44', '2025-05-13 08:12:44'),
(137, 'CUS00952', 952, NULL, NULL, 450, 0, 'Payment for bill', 3, 2, '2025-05-13', NULL, '2025-05-13 10:39:30', '2025-05-13 10:39:30'),
(138, 'CUS00955', 955, NULL, NULL, 630, 0, 'Payment for bill', 3, 2, '2025-05-13', NULL, '2025-05-13 10:41:21', '2025-05-13 10:41:21'),
(139, 'CUS01026', 1026, NULL, NULL, 470, 0, 'Payment for bill', 3, 2, '2025-05-13', NULL, '2025-05-13 11:03:52', '2025-05-13 11:03:52'),
(147, 'CUS01029', 1029, NULL, NULL, 500, 0, 'Payment for previous due bill', 3, 2, '2025-05-14', NULL, '2025-05-14 08:14:38', '2025-05-14 08:14:38'),
(148, 'CUS00885', 934, NULL, NULL, 100, 0, 'Payment for bill', 3, 2, '2025-05-14', NULL, '2025-05-14 10:14:21', '2025-05-14 10:14:21'),
(149, 'CUS00885', 934, NULL, NULL, 200, 0, 'Payment for bill', 3, 2, '2025-05-14', NULL, '2025-05-14 10:17:33', '2025-05-14 10:17:33'),
(150, 'CUS00885', 934, NULL, NULL, 200, 0, 'Payment for bill', 3, 2, '2025-05-14', NULL, '2025-05-14 10:18:30', '2025-05-14 10:18:30'),
(151, 'CUS01029', 1029, NULL, NULL, 600, 0, 'Payment for bill', 3, 2, '2025-05-15', NULL, '2025-05-15 04:38:48', '2025-05-15 04:38:48'),
(152, 'CUS00954', 954, NULL, NULL, 1700, 0, 'Payment for bill', 3, 2, '2025-05-15', NULL, '2025-05-15 04:39:12', '2025-05-15 04:39:12'),
(153, 'CUS00885', 934, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-05-15', NULL, '2025-05-15 04:40:05', '2025-05-15 04:40:05'),
(155, 'CUS00951', 951, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-05-15', NULL, '2025-05-15 04:43:19', '2025-05-15 04:43:19'),
(156, 'CUS00952', 952, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-05-15', NULL, '2025-05-15 04:44:35', '2025-05-15 04:44:35'),
(157, 'CUS00953', 953, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-05-15', NULL, '2025-05-15 04:45:21', '2025-05-15 04:45:21'),
(158, 'CUS01023', 1023, NULL, NULL, 1700, 0, 'Payment for bill', 3, 2, '2025-05-15', NULL, '2025-05-15 04:46:00', '2025-05-15 04:46:00'),
(159, 'CUS00955', 955, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-05-15', NULL, '2025-05-15 05:14:18', '2025-05-15 05:14:18'),
(160, 'CUS00956', 1016, NULL, NULL, 600, 0, 'Payment for bill', 3, 2, '2025-05-15', NULL, '2025-05-15 05:16:45', '2025-05-15 05:16:45'),
(161, 'CUS01029', 1029, NULL, NULL, 7500, 0, 'Payment for previous due bill', 3, 2, '2025-05-15', NULL, '2025-05-15 05:30:41', '2025-05-15 05:30:41'),
(162, 'CUS01017', 1017, NULL, NULL, 300, 0, 'Payment for bill', 3, 2, '2025-05-15', NULL, '2025-05-15 07:00:05', '2025-05-15 07:00:05'),
(163, 'CUS01017', 1017, NULL, NULL, 200, 0, 'Payment for bill', 3, 2, '2025-05-15', NULL, '2025-05-15 08:55:05', '2025-05-15 08:55:05'),
(165, 'CUS01031', 1031, NULL, NULL, 0, 0, 'Opening and Running Month amount Payment', 5, 2, '2025-05-17', 2, '2025-05-17 08:23:03', '2025-05-17 08:23:03'),
(166, 'CUS01032', 1032, NULL, NULL, 0, 0, 'Opening and Running Month amount Payment', 5, 2, '2025-05-17', 2, '2025-05-17 08:55:42', '2025-05-17 08:55:42'),
(168, 'CUS01020', 1020, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-05-18', NULL, '2025-05-18 05:18:23', '2025-05-18 05:18:23'),
(169, 'CUS01021', 1021, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-05-18', NULL, '2025-05-18 06:41:07', '2025-05-18 06:41:07'),
(170, 'CUS01030', 1030, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-05-18', NULL, '2025-05-18 06:49:45', '2025-05-18 06:49:45'),
(171, 'CUS01033', 1033, NULL, NULL, 0, 0, 'Opening and Running Month amount Payment', 5, 228, '2025-05-18', 228, '2025-05-18 06:57:31', '2025-05-18 06:57:31'),
(172, 'CUS01019', 1019, NULL, NULL, 500, 0, 'Payment for bill', 3, 129, '2025-05-19', NULL, '2025-05-19 05:34:19', '2025-05-19 05:34:19'),
(173, 'CUS01025', 1025, NULL, NULL, 500, 0, 'Payment for bill', 3, 129, '2025-05-19', NULL, '2025-05-19 05:38:48', '2025-05-19 05:38:48'),
(174, 'CUS01032', 1032, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-05-19', NULL, '2025-05-19 09:12:30', '2025-05-19 09:12:30'),
(175, 'CUS01031', 1031, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-05-19', NULL, '2025-05-19 09:12:34', '2025-05-19 09:12:34'),
(176, 'CUS01023', 1023, NULL, NULL, 1700, 0, 'Payment for previous due bill', 3, 2, '2025-05-19', NULL, '2025-05-19 10:47:14', '2025-05-19 10:47:14'),
(177, 'CUS01021', 1021, NULL, NULL, 500, 0, 'Payment for previous due bill', 3, 2, '2025-05-19', NULL, '2025-05-19 10:48:16', '2025-05-19 10:48:16'),
(178, 'CUS01034', 1034, NULL, NULL, 0, 0, 'Opening and Running Month amount Payment', 5, 2, '2025-05-19', 2, '2025-05-19 12:08:56', '2025-05-19 12:08:56'),
(179, 'CUS00955', 955, NULL, NULL, 500, 0, 'Payment for previous due bill', 3, 2, '2025-05-19', NULL, '2025-05-19 12:15:42', '2025-05-19 12:15:42'),
(180, 'CUS01035', 1035, NULL, NULL, 1500, 0, 'Connection Charge', 4, 2, '2025-05-19', 2, '2025-05-19 12:21:48', '2025-05-19 12:21:48'),
(181, 'CUS01035', 1035, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-05-19', NULL, '2025-05-19 12:21:48', '2025-05-19 12:21:48'),
(182, 'CUS01026', 1026, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-05-20', NULL, '2025-05-20 06:05:31', '2025-05-20 06:05:31'),
(183, 'CUS01027', 1027, NULL, NULL, 10, 0, 'Payment for bill', 3, 2, '2025-05-20', NULL, '2025-05-20 08:56:36', '2025-05-20 08:56:36'),
(184, 'CUS01037', 1037, NULL, NULL, 200, 0, 'Payment for previous due bill', 3, 2, '2025-05-20', NULL, '2025-05-20 08:59:01', '2025-05-20 08:59:01'),
(186, 'CUS01036', 1036, NULL, NULL, 100, 0, 'Payment for previous due bill', 3, 2, '2025-05-20', NULL, '2025-05-20 11:25:33', '2025-05-20 11:25:33'),
(188, 'CUS0003', 3, NULL, NULL, 1000, 0, 'Payment for bill', 3, 2, '2025-05-21', NULL, '2025-05-21 10:39:36', '2025-05-21 10:39:36'),
(189, 'CUS0002', 2, NULL, NULL, 1000, 0, 'Payment for bill', 3, 2, '2025-05-21', NULL, '2025-05-21 11:40:47', '2025-05-21 11:40:47'),
(190, 'CUS01037', 1038, NULL, NULL, 0, 0, 'Opening and Running Month amount Payment', 5, 2, '2025-05-21', 2, '2025-05-21 17:39:19', '2025-05-21 17:39:19'),
(191, NULL, NULL, 3, '4', 10330, 0, '', 1, 2, '2025-05-21', 2, '2025-05-21 17:48:13', '2025-05-21 17:48:13'),
(192, 'CUS01017', 1017, NULL, NULL, 500, 0, 'Payment for previous due bill', 3, 2, '2025-05-21', NULL, '2025-05-21 17:49:46', '2025-05-21 17:49:46'),
(193, 'CUS00956', 1016, NULL, NULL, 500, 0, 'Payment for previous due bill', 3, 2, '2025-05-22', NULL, '2025-05-22 04:57:32', '2025-05-22 04:57:32'),
(195, 'CUS01039', 1039, NULL, NULL, 0, 0, 'Opening and Running Month amount Payment', 5, 2, '2025-05-22', 2, '2025-05-22 17:59:39', '2025-05-22 17:59:39'),
(196, 'CUS01041', 1209, NULL, NULL, 0, 0, 'Opening and Running Month amount Payment', 5, 2, '2025-05-25', 2, '2025-05-25 08:15:45', '2025-05-25 08:15:45'),
(197, 'CUS0004', 4, NULL, NULL, 1000, 0, 'Payment for bill', 3, 258, '2025-05-26', NULL, '2025-05-26 07:29:28', '2025-05-26 07:29:28'),
(199, 'CUS00953', 953, NULL, NULL, 788, 0, 'Payment for previous due bill', 3, 2, '2025-05-26', NULL, '2025-05-26 11:39:48', '2025-05-26 11:39:48'),
(200, 'CUS00951', 951, NULL, NULL, 50, 0, 'Payment for previous due bill', 3, 2, '2025-05-28', NULL, '2025-05-28 07:15:15', '2025-05-28 07:15:15'),
(202, 'CUS0002', 2, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-05-28', NULL, '2025-05-28 11:38:40', '2025-05-28 11:38:40'),
(208, 'CUS00955', 955, NULL, NULL, 700, 0, 'Payment for bill', 3, 2, '2025-06-01', NULL, '2025-06-01 06:38:15', '2025-06-01 06:38:15'),
(209, 'CUS00952', 952, NULL, NULL, 400, 0, 'Payment for bill', 3, 2, '2025-06-01', NULL, '2025-06-01 11:09:19', '2025-06-01 11:09:19'),
(212, 'CUS01041', 1209, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-06-02', NULL, '2025-06-02 04:34:44', '2025-06-02 04:34:44'),
(213, NULL, NULL, 1, '6', 0, 0, 'Lunch Bill ', 1, 2, '2025-06-02', 188, '2025-06-02 05:14:15', '2025-06-18 18:48:22'),
(219, 'CUS01212', 1214, NULL, NULL, 500, 0, 'Connection Charge', 4, 258, '2025-06-10', 258, '2025-06-09 20:01:02', '2025-06-09 20:01:02'),
(220, 'CUS01212', 1214, NULL, NULL, 0, 0, 'Opening and Running Month amount Payment', 5, 258, '2025-06-10', 258, '2025-06-09 20:01:02', '2025-06-09 20:01:02'),
(221, 'CUS01215', 1215, NULL, NULL, 500, 0, 'Connection Charge', 4, 258, '2025-06-10', 258, '2025-06-09 20:05:03', '2025-06-09 20:05:03'),
(222, 'CUS01215', 1215, NULL, NULL, 700, 0, 'Opening and Running Month amount Payment', 5, 258, '2025-06-10', 258, '2025-06-09 20:05:03', '2025-06-09 20:05:03'),
(223, 'CUS01215', 1215, NULL, NULL, 500, 0, 'Advance Payment', 3, 258, '2025-06-10', 258, '2025-06-09 20:05:37', '2025-06-09 20:05:37'),
(224, 'CUS01216', 1216, NULL, NULL, 500, 0, 'Connection Charge', 4, 258, '2025-06-10', 258, '2025-06-09 20:08:19', '2025-06-09 20:08:19'),
(225, 'CUS01216', 1216, NULL, NULL, 700, 0, 'Opening and Running Month amount Payment', 5, 258, '2025-06-10', 258, '2025-06-09 20:08:19', '2025-06-09 20:08:19'),
(227, NULL, 0, 0, NULL, 20000, 0, 'Company give payment to Employee', 22, 2, '2025-06-17', 2, '2025-06-17 10:57:57', '2025-06-17 10:57:57'),
(228, NULL, 0, 0, NULL, 1000, 0, 'Company give payment to Employee', 22, 2, '2025-06-17', 2, '2025-06-17 10:58:03', '2025-06-17 10:58:03'),
(231, 'CUS01017', 1017, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-06-19', NULL, '2025-06-19 10:13:26', '2025-06-19 10:13:26'),
(232, 'CUS01211', 1211, NULL, NULL, 900, 0, 'Payment for bill', 3, 2, '2025-06-19', NULL, '2025-06-19 15:16:23', '2025-06-19 15:16:23'),
(235, 'CUS01026', 1026, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-06-22', NULL, '2025-06-22 11:21:08', '2025-06-22 11:21:08'),
(236, 'CUS01217', 1217, NULL, NULL, 600, 0, 'Payment for bill', 3, 2, '2025-06-22', NULL, '2025-06-22 11:21:20', '2025-06-22 11:21:20'),
(237, 'CUS00953', 953, NULL, NULL, 500, 0, 'Payment for bill', 3, 258, '2025-06-22', NULL, '2025-06-22 13:26:56', '2025-06-22 13:26:56'),
(239, 'CUS01218', 1218, NULL, NULL, 500, 0, 'Connection Charge', 4, 1, '2025-06-23', 1, '2025-06-23 17:10:04', '2025-06-23 17:10:04'),
(240, 'CUS01218', 1218, NULL, NULL, 0, 0, 'Opening and Running Month amount Payment', 5, 1, '2025-06-23', 1, '2025-06-23 17:10:04', '2025-06-23 17:10:04'),
(241, 'CUS01020', 1020, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-06-24', NULL, '2025-06-24 08:01:39', '2025-06-24 08:01:39'),
(242, 'CUS01036', 1036, NULL, NULL, 700, 0, 'Payment for bill', 3, 2, '2025-06-24', NULL, '2025-06-24 10:50:24', '2025-06-24 10:50:24'),
(243, 'CUS01021', 1021, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-06-24', NULL, '2025-06-24 10:57:36', '2025-06-24 10:57:36'),
(244, 'CUS01018', 1018, NULL, NULL, 1525, 0, 'Payment for bill', 3, 2, '2025-06-24', NULL, '2025-06-24 12:09:39', '2025-06-24 12:09:39'),
(245, 'CUS01023', 1023, NULL, NULL, 1700, 0, 'Payment for bill', 3, 188, '2025-06-24', NULL, '2025-06-24 12:24:56', '2025-06-24 12:24:56'),
(246, NULL, 0, 0, NULL, 5000, 0, 'Company give payment to Employee', 22, 188, '2025-06-24', 188, '2025-06-24 12:34:36', '2025-06-24 12:34:36'),
(247, 'CUS01025', 1025, NULL, NULL, 7000, 0, 'Payment for bill', 3, 2, '2025-06-24', NULL, '2025-06-24 17:39:11', '2025-06-24 17:39:11'),
(248, 'CUS01225', 1225, NULL, NULL, 0, 0, 'Opening and Running Month amount Payment', 5, 2, '2025-06-25', 2, '2025-06-25 10:59:29', '2025-06-25 10:59:29'),
(249, 'CUS01226', 1226, NULL, NULL, 0, 0, 'Opening and Running Month amount Payment', 5, 2, '2025-06-25', 2, '2025-06-25 11:01:38', '2025-06-25 11:01:38'),
(250, 'CUS01226', 1226, NULL, NULL, 600, 0, 'Payment for bill', 3, 2, '2025-06-25', NULL, '2025-06-25 11:02:37', '2025-06-25 11:02:37'),
(251, 'CUS00941', 941, NULL, NULL, 1700, 0, 'Payment for bill', 3, 129, '2025-06-26', NULL, '2025-06-26 05:18:35', '2025-06-26 05:18:35'),
(252, 'CUS00951', 951, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-06-26', NULL, '2025-06-26 07:00:17', '2025-06-26 07:00:17'),
(253, 'CUS01221', 1221, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-06-26', NULL, '2025-06-26 07:15:18', '2025-06-26 07:15:18'),
(254, 'CUS01229', 1229, NULL, NULL, 1000, 0, 'Connection Charge', 4, 2, '2025-06-26', 2, '2025-06-26 09:49:43', '2025-06-26 09:49:43'),
(255, 'CUS01229', 1229, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-06-26', NULL, '2025-06-26 09:49:43', '2025-06-26 09:49:43'),
(256, 'CUS01027', 1027, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-06-26', NULL, '2025-06-26 11:08:32', '2025-06-26 11:08:32'),
(257, 'CUS01029', 1029, NULL, NULL, 400, 0, 'Payment for bill', 3, 2, '2025-06-26', NULL, '2025-06-26 11:14:50', '2025-06-26 11:14:50'),
(258, 'CUS01030', 1030, NULL, NULL, 500, 0, 'Payment for bill', 3, 1, '2025-06-28', NULL, '2025-06-28 06:10:11', '2025-06-28 06:10:11'),
(259, 'CUS01031', 1031, NULL, NULL, 500, 0, 'Payment for bill', 3, 2, '2025-06-28', NULL, '2025-06-28 06:22:03', '2025-06-28 06:22:03'),
(260, 'CUS01032', 1032, NULL, NULL, 500, 0, 'Payment for bill', 3, 1, '2025-06-28', NULL, '2025-06-28 06:54:46', '2025-06-28 06:54:46'),
(261, 'CUS01037', 1037, NULL, NULL, 500, 0, 'Payment for bill', 3, 290, '2025-06-28', NULL, '2025-06-28 07:45:02', '2025-06-28 07:45:02'),
(262, 'CUS01033', 1033, NULL, NULL, 600, 0, 'Payment for bill', 3, 290, '2025-06-28', NULL, '2025-06-28 07:57:03', '2025-06-28 07:57:03'),
(263, 'CUS01232', 1232, NULL, NULL, 0, 0, 'Opening and Running Month amount Payment', 5, 2, '2025-06-28', 2, '2025-06-28 09:22:19', '2025-06-28 09:22:19'),
(264, 'CUS0002', 2, NULL, NULL, 1000, 0, 'Payment for bill', 3, 1, '2025-06-28', NULL, '2025-06-28 11:45:14', '2025-06-28 11:45:14'),
(265, 'CUS01233', 1233, NULL, NULL, 0, 0, 'Opening and Running Month amount Payment', 5, 1, '2025-06-28', 1, '2025-06-28 17:06:15', '2025-06-28 17:06:15'),
(266, 'CUS00951', 951, NULL, NULL, 10, 0, 'Advance Payment', 3, 2, '2025-06-29', 2, '2025-06-29 09:44:46', '2025-06-29 09:44:46'),
(267, NULL, NULL, 3, NULL, 50, 0, '', 1, 2, '2025-06-29', 2, '2025-06-29 11:14:03', '2025-06-29 11:17:49'),
(268, 'CUS00941', 941, NULL, NULL, 500, 0, 'Payment for bill', 3, 288, '2025-06-29', NULL, '2025-06-29 11:47:38', '2025-06-29 11:47:38'),
(269, 'CUS00956', 1016, NULL, NULL, 500, 0, 'Payment for bill', 3, 288, '2025-06-29', NULL, '2025-06-29 12:00:19', '2025-06-29 12:00:19'),
(270, NULL, NULL, 5, '', 500, 0, '', 1, 2, '2025-06-30', 2, '2025-06-30 07:01:58', '2025-06-30 07:01:58'),
(271, 'CUS01034', 1034, NULL, NULL, 500, 0, 'Payment for bill', 3, 288, '2025-06-30', NULL, '2025-06-30 11:12:15', '2025-06-30 11:12:15'),
(272, 'CUS01234', 1234, NULL, NULL, 525, 0, 'Opening and Running Month amount Payment', 5, 1, '2025-06-30', 1, '2025-06-30 11:28:33', '2025-06-30 11:28:33'),
(274, 'CUS00951', 951, NULL, NULL, 200, 0, 'Payment for bill', 3, 2, '2025-07-01', NULL, '2025-07-01 04:14:08', '2025-07-01 04:14:08'),
(276, 'CUS00953', 953, NULL, NULL, 400, 0, 'Payment for bill', 3, 188, '2025-07-02', NULL, '2025-07-02 09:41:14', '2025-07-02 09:41:14'),
(277, 'CUS01235', 1235, NULL, NULL, 2000, 0, 'Connection Charge', 4, 1, '2025-07-02', 1, '2025-07-02 11:25:14', '2025-07-02 11:25:14'),
(278, 'CUS01235', 1235, NULL, NULL, 500, 0, 'Opening and Running Month amount Payment', 5, 1, '2025-07-02', 1, '2025-07-02 11:25:14', '2025-07-02 11:25:14'),
(279, 'CUS01017', 1017, NULL, NULL, 500, 0, 'Payment for bill', 3, 1, '2025-07-02', NULL, '2025-07-02 13:09:47', '2025-07-02 13:09:47'),
(280, 'CUS00955', 955, NULL, NULL, 300, 0, 'Payment for bill', 3, 188, '2025-07-03', NULL, '2025-07-03 06:12:14', '2025-07-03 06:12:14'),
(281, 'CUS00956', 1016, NULL, NULL, 3000, 0, 'Payment for bill', 3, 288, '2025-07-03', NULL, '2025-07-03 06:53:26', '2025-07-03 06:53:26'),
(282, 'CUS01036', 1036, NULL, NULL, 300, 0, 'Payment for bill', 3, 288, '2025-07-03', NULL, '2025-07-03 07:12:44', '2025-07-03 07:12:44'),
(283, 'CUS01236', 1236, NULL, NULL, 500, 0, 'Connection Charge', 4, 258, '2025-07-05', 258, '2025-07-04 20:21:21', '2025-07-04 20:21:21'),
(284, 'CUS01236', 1236, NULL, NULL, 0, 0, 'Opening and Running Month amount Payment', 5, 258, '2025-07-05', 258, '2025-07-04 20:21:21', '2025-07-04 20:21:21'),
(285, 'CUS01030', 1030, NULL, NULL, 400, 0, 'Payment for bill', 3, 288, '2025-07-05', NULL, '2025-07-05 10:10:11', '2025-07-05 10:10:11'),
(286, NULL, NULL, 3, '4', 600, 0, '', 1, 259, '2025-07-05', 259, '2025-07-05 12:43:37', '2025-07-05 12:43:37'),
(287, 'CUS00951', 951, NULL, NULL, 50, 0, 'Advance Payment', 3, 2, '2025-07-15', 2, '2025-07-17 10:10:37', '2025-07-17 10:10:37'),
(288, 'CUS01018', 1018, NULL, NULL, 200, 0, 'Payment for bill', 3, 2, '2025-07-21', NULL, '2025-07-21 04:10:23', '2025-07-21 04:10:23'),
(289, 'CUS01020', 1020, NULL, NULL, 100, 0, 'Payment for bill', 3, 2, '2025-07-21', NULL, '2025-07-21 04:12:01', '2025-07-21 04:12:01'),
(290, 'CUS01020', 1020, NULL, NULL, 400, 0, 'Payment for bill', 3, 2, '2025-07-21', NULL, '2025-07-21 04:16:41', '2025-07-21 04:16:41'),
(291, 'CUS01232', 1232, NULL, NULL, 600, 0, 'Payment for bill', 3, 2, '2025-07-21', NULL, '2025-07-21 04:20:03', '2025-07-21 04:20:03'),
(292, 'CUS01238', 1238, NULL, NULL, 0, 0, 'Opening and Running Month amount Payment', 5, 2, '2025-07-21', 2, '2025-07-21 05:33:50', '2025-07-21 05:33:50'),
(293, 'CUS01238', 1239, NULL, NULL, 0, 0, 'Opening and Running Month amount Payment', 5, 2, '2025-07-21', 2, '2025-07-21 05:34:36', '2025-07-21 05:34:36'),
(294, 'CUS01240', 1240, NULL, NULL, 0, 0, 'Opening and Running Month amount Payment', 5, 2, '2025-07-21', 2, '2025-07-21 05:35:38', '2025-07-21 05:35:38'),
(295, 'CUS01240', 1240, NULL, NULL, 333, 0, 'Connection Charge', 4, 2, '2025-07-21', 2, '2025-07-21 05:48:38', '2025-07-21 05:48:38'),
(296, 'CUS01242', 1242, NULL, NULL, 777, 0, 'Opening and Running Month amount Payment', 5, 2, '2025-07-21', 2, '2025-07-21 06:04:32', '2025-07-21 06:04:32'),
(297, 'CUS01243', 1243, NULL, NULL, 300, 0, 'Connection Charge', 4, 2, '2025-07-21', 2, '2025-07-21 06:07:44', '2025-07-21 06:07:44'),
(298, 'CUS01243', 1243, NULL, NULL, 883, 0, 'Payment for bill', 3, 2, '2025-07-21', NULL, '2025-07-21 06:07:44', '2025-07-21 06:07:44'),
(300, NULL, NULL, NULL, NULL, 500, 0, 'Opening and Running Month amount Payment', 5, 2, '2025-07-21', 2, '2025-07-21 06:10:37', '2025-07-21 06:10:37'),
(301, NULL, NULL, NULL, NULL, 400, 0, 'Opening and Running Month amount Payment', 5, 2, '2025-07-21', 2, '2025-07-21 06:11:12', '2025-07-21 06:11:12'),
(311, 'CUS00941', 941, NULL, NULL, 10, 0, 'Payment for bill', 3, 2, '2025-07-22', NULL, '2025-07-22 03:18:52', '2025-07-22 03:18:52'),
(312, 'CUS00941', 941, NULL, NULL, 20, 0, 'Payment for bill', 3, 2, '2025-07-22', NULL, '2025-07-22 03:19:58', '2025-07-22 03:19:58'),
(313, 'CUS00941', 941, NULL, NULL, 10, 0, 'Payment for bill', 3, 2, '2025-07-22', NULL, '2025-07-22 03:20:37', '2025-07-22 03:20:37'),
(314, 'CUS00941', 941, NULL, NULL, 10, 0, 'Payment for bill', 3, 2, '2025-07-22', NULL, '2025-07-22 03:21:45', '2025-07-22 03:21:45'),
(315, 'CUS00941', 941, NULL, NULL, 10, 0, 'Payment for bill', 3, 2, '2025-07-22', NULL, '2025-07-22 03:22:51', '2025-07-22 03:22:51'),
(316, 'CUS00941', 941, NULL, NULL, 10, 0, 'Payment for bill', 3, 2, '2025-07-22', NULL, '2025-07-22 03:23:38', '2025-07-22 03:23:38'),
(317, 'CUS00941', 941, NULL, NULL, 10, 0, 'Payment for bill', 3, 2, '2025-07-22', NULL, '2025-07-22 03:24:04', '2025-07-22 03:24:04'),
(318, 'CUS00941', 941, NULL, NULL, 10, 0, 'Payment for bill', 3, 2, '2025-07-22', NULL, '2025-07-22 03:24:55', '2025-07-22 03:24:55'),
(328, NULL, NULL, 2222, NULL, 200, 0, 'Company give payment to Employee', 1, 1, '2025-07-27', 1, '2025-07-27 04:26:41', '2025-07-27 04:30:09'),
(330, NULL, NULL, 2222, NULL, 6000, 0, 'Company give payment to Employee', 1, 2, '2025-07-29', 2, '2025-07-29 05:09:14', '2025-07-29 05:09:14'),
(331, NULL, NULL, 2222, NULL, 500, 0, 'Company give payment to Employee', 1, 2, '2025-07-29', 2, '2025-07-29 05:18:14', '2025-07-29 05:18:14'),
(333, NULL, NULL, 2222, NULL, 200, 0, 'Company give payment to Employee', 1, 1, '2025-07-29', 1, '2025-07-29 06:55:57', '2025-07-29 06:55:57'),
(334, NULL, NULL, 2222, NULL, 600, 0, 'Company give payment to Employee', 1, 1, '2025-07-29', 1, '2025-07-29 08:30:31', '2025-07-29 08:30:31'),
(335, NULL, NULL, 2222, NULL, 200, 0, 'Company give payment to Employee', 1, 1, '2025-07-29', 1, '2025-07-29 08:30:57', '2025-07-29 08:30:57'),
(336, NULL, NULL, 2222, NULL, 20000, 0, 'Company give payment to Employee', 1, 1, '2025-07-29', 1, '2025-07-29 08:31:16', '2025-07-29 08:31:16'),
(337, NULL, NULL, 2222, NULL, 200, 0, 'Company give payment to Employee', 1, 1, '2025-07-30', 1, '2025-07-30 11:24:51', '2025-07-30 11:24:51'),
(338, 'CUS00941', 941, NULL, NULL, 410, 0, 'Payment for bill', 3, 2, '2025-08-09', NULL, '2025-08-09 08:26:20', '2025-08-09 08:26:20'),
(339, 'CUS00941', 941, NULL, NULL, 20, 0, 'Payment for bill', 3, 2, '2025-08-09', NULL, '2025-08-09 08:26:39', '2025-08-09 08:26:39');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_accounts_head`
--

CREATE TABLE `tbl_accounts_head` (
  `acc_id` int NOT NULL,
  `parent_id` int DEFAULT NULL,
  `acc_name` varchar(100) NOT NULL,
  `acc_type` int NOT NULL COMMENT '1=expence',
  `acc_desc` text NOT NULL,
  `level` int NOT NULL DEFAULT '1' COMMENT '1=parent,2=child',
  `acc_status` int NOT NULL,
  `entry_by` int NOT NULL,
  `entry_date` date NOT NULL,
  `update_by` int NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `tbl_accounts_head`
--

INSERT INTO `tbl_accounts_head` (`acc_id`, `parent_id`, `acc_name`, `acc_type`, `acc_desc`, `level`, `acc_status`, `entry_by`, `entry_date`, `update_by`, `last_update`) VALUES
(1, NULL, 'sub-router', 1, 'Cat- 6  Cable', 1, 0, 188, '2025-06-21', 188, '2025-06-21 12:48:55'),
(3, NULL, 'Onu', 1, 'Optical Fiber Wire Buy', 1, 1, 2, '2024-12-28', 2, '2024-12-28 05:22:18'),
(4, 1, 'PART', 1, 'CAT-6', 2, 0, 2, '2025-02-01', 188, '2025-06-21 12:49:29'),
(5, NULL, 'Food Allowance', 1, 'Staff Food Excenses', 1, 1, 2, '2025-03-20', 2, '2025-03-20 04:43:15'),
(6, 3, 'cable-6', 1, 'CAT-6', 2, 0, 2, '2025-02-03', 2, '2025-02-03 11:31:12'),
(7, NULL, 'Salary', 1, 'Staff Salary', 1, 1, 2, '2025-03-20', 2, '2025-03-20 04:43:36'),
(8, NULL, 'Maintaince', 1, 'Repair and Maintaince', 1, 1, 2, '2025-03-20', 2, '2025-03-20 04:44:11'),
(9, NULL, 'travel cost', 1, 'by employees', 1, 1, 2, '2025-06-26', 2, '2025-06-26 10:01:56');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_agent`
--

CREATE TABLE `tbl_agent` (
  `ag_id` int NOT NULL,
  `cus_id` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ag_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ip` varchar(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `queue_password` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `type` int NOT NULL DEFAULT '1',
  `mikrotik_id` int NOT NULL DEFAULT '1',
  `mikrotik_disconnect` int NOT NULL DEFAULT '5',
  `taka` int NOT NULL DEFAULT '0',
  `mb` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '''0mbps''',
  `int_mb` int NOT NULL DEFAULT '0',
  `ag_status` int NOT NULL DEFAULT '1' COMMENT '0=inactive, 1=active, 2=free, 3=discontinue',
  `ag_mobile_no` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `regular_mobile` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ag_office_address` mediumtext COLLATE utf8mb4_general_ci,
  `zone` int DEFAULT NULL,
  `sub_zone` int DEFAULT NULL,
  `destination` int DEFAULT NULL,
  `pay_status` int NOT NULL DEFAULT '0' COMMENT '0=paid,1=unpaid',
  `ag_email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `national_id` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nationalidphoto` mediumtext COLLATE utf8mb4_general_ci,
  `gender` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `onumac` mediumtext COLLATE utf8mb4_general_ci,
  `fibercode` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `connectiontype` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `agent_type` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `due_status` int NOT NULL DEFAULT '0',
  `bill_status` int NOT NULL DEFAULT '0' COMMENT '1=paid,0=unpaid',
  `payment_type` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bill_date` int NOT NULL DEFAULT '5',
  `remark` mediumtext COLLATE utf8mb4_general_ci,
  `inactive_date` date DEFAULT NULL,
  `billing_person_id` int DEFAULT '1',
  `entry_by` int NOT NULL DEFAULT '1',
  `update_by` int NOT NULL DEFAULT '1',
  `entry_date` date DEFAULT NULL,
  `connection_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_agent`
--

INSERT INTO `tbl_agent` (`ag_id`, `cus_id`, `ag_name`, `ip`, `queue_password`, `type`, `mikrotik_id`, `mikrotik_disconnect`, `taka`, `mb`, `int_mb`, `ag_status`, `ag_mobile_no`, `regular_mobile`, `ag_office_address`, `zone`, `sub_zone`, `destination`, `pay_status`, `ag_email`, `national_id`, `nationalidphoto`, `gender`, `onumac`, `fibercode`, `connectiontype`, `agent_type`, `due_status`, `bill_status`, `payment_type`, `bill_date`, `remark`, `inactive_date`, `billing_person_id`, `entry_by`, `update_by`, `entry_date`, `connection_date`, `created_at`, `last_update`, `deleted_at`) VALUES
(2, 'CUS0002', 'Ashik-BSD', 'k-mirajdish', '2468', 1, 1, 1, 1000, '20MB', 20, 1, '01765843744', '', 'Dhaka', 21, 0, 0, 0, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 1, NULL, 5, '', NULL, 1, 2, 1, '2025-05-01', '2024-12-17', '2024-12-17 10:46:06', '2025-06-28 20:21:25', '2025-06-28 20:21:06'),
(4, 'CUS0004', 'Toriqul-BSD', 'k-sumonmollik', '2468', 1, 1, 1, 1000, '20MB', 6, 1, '01303863702', '', '', 11, 58, 0, 0, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 1, NULL, 5, '', NULL, 1, 2, 1, '2025-04-01', '2024-12-17', '2024-12-17 10:50:40', '2025-05-29 15:35:28', '2025-05-29 15:35:05'),
(7, 'CUS0007', 'Test_BSD', 'k-tuli', '2468', 1, 1, 1, 500, '15Mb@1500TK', 4, 1, '0122222222222', '', '', 30, NULL, NULL, 0, 'test@gmail.com', '', NULL, 'Male', '', '', 'Home', 'Cat 5', 0, 1, NULL, 5, '', NULL, 1, 2, 1, '2024-12-01', '2024-12-18', '2024-12-18 05:51:54', '2025-06-28 07:38:59', '2025-05-04 04:40:05'),
(305, 's334', 'rabbi', 's334rabbi', '', 1, 1, 0, 500, 'silvar', 0, 1, '01925411793', '', 'buiya bari', 29, 50, 0, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 2, 2, 1, '2025-07-14', '2025-05-16', '2025-07-14 05:00:44', '2025-08-02 04:06:49', NULL),
(306, 's335', 'banwdhan', 's335banwdhan', '', 1, 1, 0, 700, 'Gold new', 0, 1, '00000', '', 'supar shop', 29, 53, 0, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 2, 2, 1, '2025-07-14', '2025-05-07', '2025-07-14 05:00:44', '2025-08-02 04:06:49', NULL),
(307, 's336', 'rasel', 's336rasel', '', 1, 1, 0, 600, 'Silvar new', 0, 1, '01704114385', '', 'police line', 29, 63, 0, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 2, 2, 1, '2025-07-14', '2025-03-16', '2025-07-14 05:00:44', '2025-08-02 04:06:49', NULL),
(308, 's337', 'amin', 's337amin', '', 1, 1, 0, 600, 'silver ', 0, 1, '01616391660', '', 'police line', 29, 63, 0, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 2, 2, 1, '2025-07-14', '2025-02-05', '2025-07-14 05:00:44', '2025-08-02 04:06:49', NULL),
(309, 's338', 'mamun', 's338mamun', '', 1, 1, 0, 600, 'Silvar new', 0, 1, '00000', '', 'gas office ', 29, 46, 0, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 2, 2, 1, '2025-07-14', '2025-02-05', '2025-07-14 05:00:44', '2025-07-20 06:26:44', NULL),
(321, 's219', 'alom', 's66alom', NULL, 1, 1, 13, 600, 'Silvar new', 0, 1, '0', '', 'mirvari', 28, 41, NULL, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 2, 2, 1, '2025-07-01', '2025-06-04', '2025-07-20 06:31:04', '2025-08-02 04:06:49', NULL),
(322, 's322', 'raju', 's261raju', NULL, 1, 1, 13, 600, 'Silvar new', 0, 1, '0194412927', '', 'gass office', 29, 65, 0, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 2, 2, 1, '2025-07-01', '2025-07-11', '2025-07-20 08:08:09', '2025-08-02 04:06:49', NULL),
(323, 's323', 'sattar', 's232sattar', NULL, 1, 1, 12, 600, 'Silvar new', 0, 1, '01611131571', '', 'gass ofice', 29, 65, 0, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 2, 2, 1, '2025-07-01', '2025-07-10', '2025-07-20 08:11:27', '2025-08-02 04:06:49', NULL),
(324, 's324', 'sabbir', 's339sabbir', NULL, 1, 1, 13, 600, 'Silvar new', 0, 1, '0', '', 'police', 29, 63, 0, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 2, 2, 1, '2025-07-01', '2025-07-12', '2025-07-20 08:14:05', '2025-08-02 04:06:49', NULL),
(325, 's325', 'shamim', 's195shamim', NULL, 1, 1, 13, 600, 'Silvar new', 0, 1, '0', '', 'bas stand', 30, 37, 0, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 2, 2, 1, '2025-07-01', '2025-05-10', '2025-07-20 08:56:18', '2025-08-02 04:06:49', NULL),
(326, 's326', 'reza', 's340reza', NULL, 1, 1, 13, 800, 'Gold', 0, 1, '01712532657', '', 'mirbari', 28, 41, NULL, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 2, 2, 1, '2025-07-01', '2025-07-12', '2025-07-20 09:00:58', '2025-08-02 04:06:49', NULL),
(327, 's327', 'faruk', 's341faruk', NULL, 1, 1, 13, 600, 'Silvar new', 0, 1, '01951777987', '', 'mirbari', 28, 41, NULL, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 2, 2, 1, '2025-07-01', '2025-07-13', '2025-07-20 09:08:12', '2025-08-02 04:06:49', NULL),
(328, 's328', 'monir', 's106monir', NULL, 1, 1, 13, 600, 'Silvar new', 0, 1, '01728595372', '', 'bolainoga', 28, 40, 0, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 2, 2, 1, '2025-07-01', '2025-05-20', '2025-07-20 09:25:32', '2025-08-02 04:06:49', NULL),
(329, 's329', 'rony', 's158rony', NULL, 1, 1, 13, 600, 'Silvar new', 0, 1, '01776732620', '', 'police line', 29, 48, 0, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 2, 2, 1, '2025-07-01', '2025-07-20', '2025-07-20 09:27:40', '2025-08-02 04:06:49', NULL),
(330, 's330', 'shamim', 's224shamim', NULL, 1, 1, 13, 600, 'Silvar new', 0, 1, '0', '', 'bast', 30, 37, NULL, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 2, 2, 1, '2025-07-01', '2025-07-09', '2025-07-20 09:31:16', '2025-08-02 04:06:49', NULL),
(331, 's331', 'masud', 's266masud', NULL, 1, 1, 13, 600, 'Silvar new', 0, 1, '01985866422', '', 'gass', 29, 65, NULL, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 2, 2, 1, '2025-07-01', '2025-06-12', '2025-07-20 09:33:01', '2025-08-02 04:06:49', NULL),
(332, 's332', 'sultana', 's245sultana', NULL, 1, 1, 13, 600, 'Silvar new', 0, 1, '0', '', 'bolainowga', 28, 40, NULL, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 2, 2, 1, '2025-07-01', '2023-07-21', '2025-07-21 01:01:41', '2025-08-02 04:06:49', NULL),
(934, 'CUS00885', 'Juthi', 'k-juthi', '2468', 1, 1, 1, 500, '15Mb@1500TK', 5, 0, '01369854723', '', '', 30, 0, 0, 0, '', '', NULL, 'Female', '', '', 'Home', 'Optical Fiber', 0, 1, NULL, 5, '', '2025-05-01', 1, 2, 1, '2025-05-01', '2024-12-22', '2024-12-22 03:57:04', '2025-06-28 07:38:59', '2025-05-22 02:52:05'),
(935, 'CUS00935', 'shiplu', '01333112772', '2468', 1, 1, 5, 500, '15MB', 0, 1, '01981663771', '', '', 18, NULL, 0, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 1, 235, 1, '2025-05-01', '2024-12-22', '2024-12-22 05:23:20', '2025-05-29 15:36:14', '2025-05-29 15:36:05'),
(936, 'CUS00936', 'k-sohan', 'k-sohan', '2468', 1, 1, 5, 500, '15Mb@1500TK', 5, 1, '1', '', '', 13, 0, 0, 0, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 1, 2, 1, '2025-05-01', '2024-12-22', '2024-12-22 05:29:33', '2025-06-28 07:38:59', '2025-05-29 15:35:05'),
(937, 'CUS00936', 'k-imrangomosta', 'k-imrangomosta', '2468', 1, 1, 5, 500, '15Mb@1500TK', 4, 1, '1', '', '', 9, 50, 53, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 1, 2, 1, '2025-02-01', '2024-12-28', '2024-12-22 05:34:39', '2025-06-28 07:38:59', NULL),
(938, 'CUS00938', '2468', 'ksd-mohasin', '2468', 1, 1, 5, 500, '15Mb@1500TK', 4, 0, '1', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 1, 1, 1, '2024-12-01', '2024-12-22', '2024-12-22 05:35:21', '2025-06-28 07:38:59', '2025-05-22 02:52:05'),
(939, 'CUS00938', '2468', 'k-rakibstor', '2468', 1, 1, 5, 600, '15MB', 15, 1, '1', '', '', 13, NULL, 0, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 1, 2, 1, '2025-05-01', '2024-12-22', '2024-12-22 05:36:11', '2025-08-02 04:06:49', NULL),
(940, 'CUS00938', '2468', 'p-dream3', '2468', 1, 1, 5, 500, '15Mb@1500TK', 4, 1, '1', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 1, 1, 1, '2024-12-01', '2024-12-22', '2024-12-22 05:39:18', '2025-06-28 07:38:59', '2025-02-03 06:55:02'),
(941, 'CUS00941', 'Babul', 'k-tuli', NULL, 1, 1, 1, 500, '15Mb@1500TK', 5, 1, '01888888888', '', 'dhaka', 30, 0, 0, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 1, NULL, 5, '', NULL, 2, 2, 1, '2025-06-01', '2024-12-22', '2024-12-22 06:17:23', '2025-08-09 08:26:20', NULL),
(942, 'CUS00942', 'AL AMIN', 'k-tuli', '2468', 1, 1, 14, 500, '15Mb@1500TK', 4, 1, '01666666665', '', '', 30, NULL, NULL, 0, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 1, NULL, 5, '', NULL, 1, 2, 1, '2025-01-01', '2025-01-15', '2025-01-15 11:36:39', '2025-06-28 07:38:59', '2025-04-27 10:39:04'),
(943, 'CUS00943', 'k-highschool2', 'k-highschool2', '2468', 1, 1, 5, 500, '15Mb@1500TK', 4, 1, '1', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 1, 1, 1, '2025-01-01', '2025-01-20', '2025-01-20 04:22:52', '2025-06-28 07:38:59', '2025-02-03 06:56:02'),
(944, 'CUS00944', 'Babul', 'tcv2tapos', '2468', 1, 1, 1, 500, '15Mb@1500TK', 4, 1, '01888888888', '', '', 30, NULL, NULL, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 111, 2, 1, '2025-01-01', '2025-01-21', '2025-01-21 12:18:29', '2025-08-02 04:06:49', NULL),
(945, 'CUS00945', 'AL AMIN', 'k-tanvir', '2468', 1, 1, 1, 500, '15Mb@1500TK', 4, 1, '01624171572', '', 'bsd', 10, 14, 15, 0, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 1, NULL, 5, '', NULL, 1, 2, 1, '2025-01-01', '2025-01-21', '2025-01-21 12:20:02', '2025-06-28 07:38:59', '2025-02-03 06:55:02'),
(946, 'CUS00946', 'Babul', 'k-sumonmollik', '2468', 1, 1, 1, 500, '15Mb@1500TK', 4, 1, '01624171572', 'Sit quia in volupta', '', 10, 14, 15, 0, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 1, NULL, 5, '', NULL, 1, 2, 1, '2025-02-01', '2025-02-01', '2025-02-01 05:34:36', '2025-06-28 07:38:59', '2025-02-03 06:55:02'),
(947, 'CUS00947', 'k-rubel', 'k-rubel', '2468', 1, 1, 5, 500, '15Mb@1500TK', 4, 1, '1', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 1, 1, 1, '2025-02-01', '2025-02-01', '2025-02-01 07:10:23', '2025-06-28 07:38:59', '2025-02-03 06:55:02'),
(948, 'CUS00948', 'k-uparisad', 'k-uparisad', '2468', 1, 1, 5, 500, '15Mb@1500TK', 4, 1, '1', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 1, 1, 1, '2025-02-01', '2025-02-02', '2025-02-02 06:45:08', '2025-06-28 07:38:59', '2025-02-03 06:55:02'),
(949, 'CUS00949', 'alamin hossen', 'k-alamin', '111111', 1, 1, 1, 500, '15Mb@1500TK', 4, 1, '0123456789', '312', 'Nikunja', 10, 14, 15, 0, 'alamin@exaple.com', '1242355345', NULL, 'Male', 'dawd', 'gerg', 'Home', 'Optical Fiber', 0, 1, NULL, 5, '1', NULL, 1, 2, 1, '2025-02-01', '2025-02-03', '2025-02-03 06:53:18', '2025-06-28 07:38:59', '2025-02-03 06:55:02'),
(950, 'CUS00950', 'alamin hossen', 'k-alamin', '111111', 1, 1, 1, 500, '15Mb@1500TK', 4, 1, '0123456789', '312', 'Nikunja', 10, 14, 15, 0, 'alamin@exaple.com', '1242355345', NULL, 'Male', 'dawd', 'gerg', 'Home', 'Optical Fiber', 0, 1, NULL, 5, '1', NULL, 1, 2, 1, '2025-02-01', '2025-02-03', '2025-02-03 06:54:27', '2025-06-28 07:38:59', '2025-02-03 06:55:02'),
(951, 'CUS00951', 'bayazid', '100.2.14', '44385430', 1, 1, 10, 500, '15MB', 0, 1, '098266537346', '01765637835', '', 11, 58, NULL, 1, 'ffs@gmail.com', '', NULL, 'Male', '', '', 'Home', 'Cat 5', 0, 0, NULL, 5, '', NULL, 111, 2, 1, '2025-02-01', '2025-02-11', '2025-02-11 12:18:37', '2025-08-02 04:06:49', NULL),
(952, 'CUS00952', 'bayazid', '100.2.17', '44385430', 1, 1, 10, 500, '15MB', 0, 1, '01914725119', '01914725119', '30/4 Sher Shah Suri Road Mohammadpur Dhaka', 23, 49, 0, 1, '', '', NULL, 'Male', '30/4 Sher Shah Suri Road Mohammadpur Dhaka', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 120, 2, 1, '2025-02-01', '2025-02-11', '2025-02-11 12:22:56', '2025-08-02 04:06:49', NULL),
(953, 'CUS00953', 'Md Arman Hossain', 'arman', 'arman', 1, 1, 15, 500, '15MB', 0, 1, '01760727537', '', '37/3 a Hazi Osman Goni Road ,Bongshal ,Dhaka', 28, NULL, NULL, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 117, 2, 1, '2025-02-01', '2025-02-17', '2025-02-17 10:39:29', '2025-08-02 04:06:49', NULL),
(954, 'CUS00954', 'AL AMIN', 'k-sdhgf', '1234', 1, 1, 8, 1700, '10MB', 12, 1, '01624171572', '', 'Dhaka', 10, 14, 15, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 107, 2, 1, '2025-02-01', '2025-02-24', '2025-02-24 04:22:52', '2025-06-30 18:28:13', NULL),
(955, 'CUS00955', 'Mr bulbul', 'Bulbul@jbl', '123456', 1, 1, 12, 500, '15MB', 0, 1, '01710444209', '', 'Mullapara,psot,kacasara,jamalpur sadar .\r\nNear at Ttc', 13, NULL, NULL, 1, 'bulbulemt@gmail.com', '', NULL, 'Male', 'Mullapara,psot,kacasara,jamalpur sadar .', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 107, 2, 1, '2025-02-01', '2025-02-25', '2025-02-25 11:07:16', '2025-08-02 04:06:49', NULL),
(956, 'CUS00956', 'NDMT250', 'Fatema', '16-Mar-2022', 1, 1, 2147483647, 1700, '10MB', 12, 0, '500', '500', '0', NULL, NULL, NULL, 0, '500', '500', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:46', '2025-05-19 12:53:31', NULL),
(957, 'CUS00957', 'NDMT187', 'Arman', '28-Oct-2022', 1, 1, 2147483647, 1700, '10MB', 12, 0, '500', '499', '0', NULL, NULL, NULL, 0, '500', '499', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:46', '2025-05-19 12:53:31', NULL),
(958, 'CUS00958', 'NDMT163', 'Anowar', '01-Sep-2023', 1, 1, 2147483647, 1700, '10MB', 12, 0, '500', '0', '0', NULL, NULL, NULL, 0, '500', '0', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:46', '2025-05-19 12:53:31', NULL),
(959, 'CUS00959', 'NDMT277', 'Shawon', '29-Nov-2022', 1, 1, 2147483647, 1700, '10MB', 12, 0, '500', '500', '0', NULL, NULL, NULL, 0, '500', '500', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:46', '2025-05-19 12:53:31', NULL),
(960, 'CUS00960', 'NDMT202', 'Afrid', '16-May-2024', 1, 1, 2147483647, 1700, '10MB', 12, 0, '500', '500', '0', NULL, NULL, NULL, 0, '500', '500', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:47', '2025-05-19 12:53:31', NULL),
(961, 'CUS00961', 'NDMT318', 'Sabbir', '21-Oct-2022', 1, 1, 2147483647, 1700, '10MB', 12, 0, '500', '500', '0', NULL, NULL, NULL, 0, '500', '500', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:47', '2025-05-19 12:53:31', NULL),
(962, 'CUS00962', 'NDMT302', 'Hasem', '04-May-2024', 1, 1, 2147483647, 1700, '10MB', 12, 0, '500', '500', '0', NULL, NULL, NULL, 0, '500', '500', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:47', '2025-05-19 12:53:31', NULL),
(963, 'CUS00963', 'NDMT203', 'Jerin', '19-Nov-2022', 1, 1, 2147483647, 1700, '10MB', 12, 0, '500', '500', '0', NULL, NULL, NULL, 0, '500', '500', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:47', '2025-05-19 12:53:31', NULL),
(964, 'CUS00964', 'NDMT145', 'Taskin', '18-Mar-2022', 1, 1, 2147483647, 1700, '10MB', 12, 0, '500', '500', '0', NULL, NULL, NULL, 0, '500', '500', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:47', '2025-05-19 12:53:31', NULL),
(965, 'CUS00965', 'NDMT241', 'Anis', '08-Feb-2022', 1, 1, 2147483647, 1700, '10MB', 12, 0, '500', '499', '0', NULL, NULL, NULL, 0, '500', '499', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:47', '2025-05-19 12:53:31', NULL),
(966, 'CUS00966', 'NDMT280', 'Shamim', '09-Jun-2022', 1, 1, 2147483647, 1326873037, '20MB', 0, 0, '500', '500', '0', NULL, NULL, NULL, 0, '500', '500', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:48', '2025-05-29 04:22:12', '2025-02-25 12:18:02'),
(967, 'CUS00967', 'NDMT220', 'Rasel', '01-Jan-2022', 1, 1, 2147483647, 1910142226, '20MB', 0, 0, '500', '500', '0', NULL, NULL, NULL, 0, '0', '1000', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:48', '2025-05-29 04:22:12', '2025-02-25 12:18:02'),
(968, 'CUS00968', 'NDMT159', 'Shopon', '01-Feb-2024', 1, 1, 2147483647, 1715055584, '20MB', 0, 0, '500', '500', '0', NULL, NULL, NULL, 0, '500', '500', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:48', '2025-05-29 04:22:12', '2025-02-25 12:18:02'),
(969, 'CUS00969', 'NDMT267', 'Alamin', '06-Nov-2022', 1, 1, 2147483647, 1866524963, '20MB', 0, 0, '500', '500', '0', NULL, NULL, NULL, 0, '500', '500', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:48', '2025-05-29 04:22:12', '2025-02-25 12:18:02'),
(970, 'CUS00970', 'NDMT350', 'Manik', '09-Jun-2022', 1, 1, 2147483647, 1733948120, '20MB', 0, 0, '400', '400', '0', NULL, NULL, NULL, 0, '400', '400', NULL, 'Unuser_2', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:48', '2025-05-29 04:22:12', '2025-02-25 12:18:02'),
(971, 'CUS00971', 'NDMT157', 'Ahsan', '07-Jul-2022', 1, 1, 2147483647, 1932099074, '20MB', 0, 0, '500', '500', '0', NULL, NULL, NULL, 0, '500', '500', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:49', '2025-05-29 04:22:12', '2025-02-25 12:18:02'),
(972, 'CUS00972', 'NDMT175', 'Safayat', '01-Sep-2022', 1, 1, 2147483647, 1727722999, '20MB', 0, 0, '500', '500', '0', NULL, NULL, NULL, 0, '500', '500', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:49', '2025-05-29 04:22:12', '2025-02-25 12:18:02'),
(973, 'CUS00973', 'NDMT339', 'Sohel', '08-Oct-2023', 1, 1, 2147483647, 1954700731, '20MB', 0, 0, '1000', '2000', '0', NULL, NULL, NULL, 0, '2000', '1000', NULL, '15MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:49', '2025-05-29 04:22:12', '2025-02-25 12:18:02'),
(974, 'CUS00974', 'NDMT268', 'Nirmol', '21-Jan-2023', 1, 1, 2147483647, 1990810609, '20MB', 0, 0, '500', '500', '0', NULL, NULL, NULL, 0, '500', '500', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:49', '2025-05-29 04:22:12', '2025-02-25 12:18:02'),
(975, 'CUS00975', 'NDMT405', 'Samir', '01-Sep-2023', 1, 1, 2147483647, 1825726776, '20MB', 0, 0, '500', '500', '0', NULL, NULL, NULL, 0, '500', '500', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:49', '2025-05-29 04:22:12', '2025-02-25 12:18:02'),
(976, 'CUS00976', 'NDMT413', 'Rubel', '03-Mar-2024', 1, 1, 2147483647, 1999445144, '20MB', 0, 0, '500', '501', '0', NULL, NULL, NULL, 0, '500', '501', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:49', '2025-05-29 04:22:12', '2025-02-25 12:10:02'),
(977, 'CUS00977', 'NDMT234', 'Amirul', '01-Feb-2022', 1, 1, 2147483647, 1764612438, '20MB', 0, 0, '500', '500', '0', NULL, NULL, NULL, 0, '500', '500', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:50', '2025-05-29 04:22:12', '2025-02-25 12:10:02'),
(978, 'CUS00978', 'NDMT180', 'Rimon', '05-Feb-2025', 1, 1, 2147483647, 1829275779, '20MB', 0, 0, '500', '1000', '0', NULL, NULL, NULL, 0, '1000', '-500', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:50', '2025-05-29 04:22:12', '2025-02-25 12:12:02'),
(979, 'CUS00979', 'NDMT104', 'Shiplu', '24-Sep-2022', 1, 1, 2147483647, 1608775688, '20MB', 0, 0, '500', '500', '0', NULL, NULL, NULL, 0, '500', '500', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:50', '2025-05-29 04:22:12', '2025-02-25 12:12:02'),
(980, 'CUS00980', 'NDMT254', 'Liton', '07-Apr-2022', 1, 1, 2147483647, 1713042869, '20MB', 0, 0, '1000', '1000', '0', NULL, NULL, NULL, 0, '1000', '1000', NULL, '15MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:50', '2025-05-29 04:22:12', '2025-02-25 12:12:02'),
(981, 'CUS00981', 'NDMT109', 'Mobarok', '27-Sep-2022', 1, 1, 2147483647, 1713532915, '20MB', 0, 0, '500', '500', '0', NULL, NULL, NULL, 0, '500', '500', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:50', '2025-05-29 04:22:12', '2025-02-25 12:12:02'),
(982, 'CUS00982', 'NDMT325', 'Joy', '05-Dec-2021', 1, 1, 2147483647, 1866680113, '20MB', 0, 0, '500', '500', '0', NULL, NULL, NULL, 0, '500', '500', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:50', '2025-05-29 04:22:12', '2025-02-25 12:12:02'),
(983, 'CUS00983', 'NDMT329', 'Akram', '03-Jan-2022', 1, 1, 2147483647, 1952051912, '20MB', 0, 0, '500', '500', '0', NULL, NULL, NULL, 0, '500', '500', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:51', '2025-05-29 04:22:12', '2025-02-25 12:12:02'),
(984, 'CUS00984', 'NDMT352', 'Rased', '26-Jun-2022', 1, 1, 2147483647, 1999306658, '20MB', 0, 0, '800', '800', '0', NULL, NULL, NULL, 0, '800', '800', NULL, '10MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:51', '2025-05-29 04:22:12', '2025-02-25 12:11:02'),
(985, 'CUS00985', 'NDMT252', 'Pappu', '22-Mar-2023', 1, 1, 2147483647, 1791931311, '20MB', 0, 0, '500', '500', '0', NULL, NULL, NULL, 0, '500', '500', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:51', '2025-05-29 04:22:12', '2025-02-25 12:10:02'),
(986, 'CUS00986', 'NDMT149', 'Sabbir', '16-Apr-2022', 1, 1, 2147483647, 1317796708, '20MB', 0, 0, '500', '500', '0', NULL, NULL, NULL, 0, '500', '500', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:51', '2025-05-29 04:22:12', '2025-02-25 12:09:02'),
(987, 'CUS00987', 'NDMT124', 'Saimon', '26-Jun-2022', 1, 1, 2147483647, 1786768322, '20MB', 0, 0, '500', '500', '0', NULL, NULL, NULL, 0, '0', '1000', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:51', '2025-05-29 04:22:12', '2025-02-25 12:09:02'),
(988, 'CUS00988', 'NDMT364', 'Shovon', '30-Mar-2023', 1, 1, 2147483647, 1917014204, '20MB', 0, 0, '500', '500', '0', NULL, NULL, NULL, 0, '0', '1000', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:52', '2025-05-29 04:22:12', '2025-02-25 12:09:02'),
(989, 'CUS00989', 'NDMT106', 'Ferdus', '25-Sep-2022', 1, 1, 2147483647, 1841117362, '20MB', 0, 0, '500', '500', '0', NULL, NULL, NULL, 0, '500', '500', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:52', '2025-05-29 04:22:12', '2025-02-25 12:09:02'),
(990, 'CUS00990', 'NDMT205', 'Sahed', '26-Dec-2021', 1, 1, 2147483647, 1923750254, '20MB', 0, 0, '500', '500', '0', NULL, NULL, NULL, 0, '0', '1000', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:52', '2025-05-29 04:22:12', '2025-02-25 12:09:02'),
(991, 'CUS00991', 'NDMT139', 'Midul', '12-Feb-2025', 1, 1, 2147483647, 1774476451, '20MB', 0, 0, '500', '500', '0', NULL, NULL, NULL, 0, '500', '500', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:52', '2025-05-29 04:22:12', '2025-02-25 12:09:02'),
(992, 'CUS00992', 'NDMT141', 'Shawon', '01-Mar-2022', 1, 1, 2147483647, 1877938464, '20MB', 0, 0, '500', '499', '0', NULL, NULL, NULL, 0, '500', '499', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:52', '2025-05-29 04:22:12', '2025-02-25 12:09:02'),
(993, 'CUS00993', 'NDMT144', 'Durjoy', '05-Mar-2022', 1, 1, 2147483647, 1790586568, '20MB', 0, 0, '500', '500', '0', NULL, NULL, NULL, 0, '500', '500', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:52', '2025-05-29 04:22:12', '2025-02-25 12:09:02'),
(994, 'CUS00994', 'NDMT378', 'Amir', '22-Nov-2022', 1, 1, 2147483647, 1935474652, '20MB', 0, 0, '400', '400', '0', NULL, NULL, NULL, 0, '400', '400', NULL, 'Unuser_2', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:53', '2025-05-29 04:22:12', '2025-02-25 12:09:02'),
(995, 'CUS00995', 'NDMT185', 'Anowar', '17-Oct-2022', 1, 1, 2147483647, 1728301274, '20MB', 0, 0, '500', '499', '0', NULL, NULL, NULL, 0, '500', '499', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:53', '2025-05-29 04:22:12', '2025-02-25 12:09:02'),
(996, 'CUS00996', 'NDMT186', 'Bablu', '14-Oct-2022', 1, 1, 2147483647, 1979481739, '20MB', 0, 0, '500', '1000', '0', NULL, NULL, NULL, 0, '500', '1000', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:53', '2025-05-29 04:22:12', '2025-02-25 12:02:02'),
(997, 'CUS00997', 'NDMT184', 'Surjo Banu', '11-Oct-2022', 1, 1, 2147483647, 1711006476, '20MB', 0, 0, '500', '500', '0', NULL, NULL, NULL, 0, '500', '500', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:53', '2025-05-29 04:22:12', '2025-02-25 12:03:02'),
(998, 'CUS00998', 'NDMT367', 'Belal', '06-Oct-2022', 1, 1, 2147483647, 1952208750, '20MB', 0, 0, '500', '499', '0', NULL, NULL, NULL, 0, '500', '499', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:53', '2025-05-29 04:22:12', '2025-02-25 12:03:02'),
(999, 'CUS00999', 'NDMT273', 'Mamun', '05-Oct-2022', 1, 1, 2147483647, 1310888508, '20MB', 0, 0, '500', '499', '0', NULL, NULL, NULL, 0, '500', '499', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:54', '2025-05-29 04:22:12', '2025-02-25 12:03:02'),
(1000, 'CUS01000', 'NDMT255', 'Soleman', '10-Jun-2022', 1, 1, 2147483647, 1714650240, '20MB', 0, 0, '1000', '1000', '0', NULL, NULL, NULL, 0, '1000', '1000', NULL, '15MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:54', '2025-05-29 04:22:12', '2025-02-25 12:03:02'),
(1001, 'CUS01001', 'NDMT208', 'Sohel', '15-Mar-2023', 1, 1, 2147483647, 1925760560, '20MB', 0, 0, '500', '500', '0', NULL, NULL, NULL, 0, '500', '500', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:54', '2025-05-29 04:22:12', '2025-02-25 12:03:02'),
(1002, 'CUS01002', 'NDMT191', 'Mamun', '29-Oct-2022', 1, 1, 2147483647, 1754830423, '20MB', 0, 0, '500', '500', '0', NULL, NULL, NULL, 1, '500', '500', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 1, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:54', '2025-05-29 04:22:12', '2025-02-25 12:03:02'),
(1003, 'CUS01003', 'NDMT248', 'Masum', '10-Jun-2022', 1, 1, 2147483647, 1967365488, '20MB', 0, 0, '500', '499', '0', NULL, NULL, NULL, 1, '500', '499', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 1, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:54', '2025-05-29 04:22:12', '2025-02-25 12:03:02'),
(1004, 'CUS01004', 'NDMT137', 'Masum', '29-Jan-2022', 1, 1, 2147483647, 1722119493, '20MB', 0, 0, '500', '500', '0', NULL, NULL, NULL, 1, '500', '500', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 1, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:54', '2025-05-29 04:22:12', '2025-02-25 12:03:02'),
(1005, 'CUS01005', 'NDMT257', 'Fardin', '01-Oct-2022', 1, 1, 2147483647, 1715820658, '20MB', 0, 0, '1000', '1000', '0', NULL, NULL, NULL, 1, '1000', '1000', NULL, '15MB', NULL, NULL, NULL, NULL, 0, 1, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:55', '2025-05-29 04:22:12', '2025-02-25 12:02:02'),
(1006, 'CUS01006', 'NDMT215', 'Imon', '26-Dec-2021', 1, 1, 2147483647, 1674061685, '20MB', 0, 0, '500', '500', '0', NULL, NULL, NULL, 1, '500', '500', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 1, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:55', '2025-05-29 04:22:12', '2025-02-25 12:00:02'),
(1007, 'CUS01007', 'NDMT161', 'Mokbul', '24-Feb-2025', 1, 1, 2147483647, 1734821305, '20MB', 0, 0, '500', '1200', '0', NULL, NULL, NULL, 1, '1000', '-1000', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 1, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:55', '2025-05-29 04:22:12', '2025-02-25 12:00:02'),
(1008, 'CUS01008', 'NDMT155', 'Rubel', '15-Mar-2022', 1, 1, 2147483647, 1986561662, '20MB', 0, 0, '500', '1000', '0', NULL, NULL, NULL, 1, '1000', '500', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 1, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:55', '2025-05-29 04:22:12', '2025-02-25 12:00:02'),
(1009, 'CUS01009', 'NDMT284', 'Junayed', '01-Feb-2023', 1, 1, 2147483647, 1916270073, '20MB', 0, 0, '500', '500', '0', NULL, NULL, NULL, 1, '500', '500', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 1, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:55', '2025-05-29 04:22:12', '2025-02-25 12:00:02'),
(1010, 'CUS01010', 'NDMT158', 'Washim', '27-Jul-2022', 1, 1, 2147483647, 1830307070, '20MB', 0, 0, '500', '500', '0', NULL, NULL, NULL, 1, '500', '500', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 1, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:56', '2025-05-29 04:22:12', '2025-02-25 12:01:02'),
(1011, 'CUS01011', 'NDMT119', 'Amin', '17-Oct-2022', 1, 1, 2147483647, 1715603755, '20MB', 0, 0, '500', '500', '0', NULL, NULL, NULL, 1, '500', '500', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 1, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:56', '2025-05-29 04:22:12', '2025-02-25 12:01:02'),
(1012, 'CUS01012', 'NDMT316', 'Shahalam', '05-Oct-2022', 1, 1, 2147483647, 1711709414, '20MB', 0, 0, '500', '500', '0', NULL, NULL, NULL, 1, '500', '500', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 1, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:56', '2025-05-29 04:22:12', '2025-02-25 12:01:02'),
(1013, 'CUS01013', 'NDMT160', 'Amzad', '28-Jul-2022', 1, 1, 2147483647, 1611318815, '20MB', 0, 0, '400', '400', '0', NULL, NULL, NULL, 1, '400', '400', NULL, 'Unuser_2', NULL, NULL, NULL, NULL, 0, 1, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:56', '2025-05-29 04:22:12', '2025-02-25 12:02:02'),
(1014, 'CUS01014', 'NDMT219', 'Dipu', '01-Aug-2024', 1, 1, 2147483647, 1714531165, '20MB', 0, 0, '500', '500', '0', NULL, NULL, NULL, 1, '500', '500', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 1, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:56', '2025-05-29 04:22:12', '2025-02-25 12:02:02'),
(1015, 'CUS01015', 'NDMT311', 'Hanif', '09-Sep-2022', 1, 1, 2147483647, 1954900046, '20MB', 0, 0, '500', '500', '0', NULL, NULL, NULL, 1, '500', '500', NULL, '5MB', NULL, NULL, NULL, NULL, 0, 1, NULL, 5, NULL, NULL, 2, 2, 1, NULL, NULL, '2025-02-25 11:58:56', '2025-05-29 04:22:12', '2025-02-25 12:02:02'),
(1016, 'CUS00956', 'k-arnicable', 'k-arnicable', '2468', 1, 1, 5, 500, '30MB', 4, 1, '1', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 1, 1, 1, '2025-03-01', '2025-03-08', '2025-03-08 04:41:31', '2025-08-02 04:06:49', NULL),
(1017, 'CUS01017', 'Polash miah', 'Polash', NULL, 1, 1, 20, 500, '15MB', 0, 1, '01872736050', '', '', 10, 14, 0, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 1, 2, 1, '2025-03-01', '2025-03-08', '2025-03-08 17:21:49', '2025-08-02 04:06:49', NULL),
(1018, 'CUS01018', 'Mahabub Islam', 'test@1', '4321', 1, 13, 30, 500, '10Mbps_Package', 10, 1, '01916117392', '', '', 9, 50, 53, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 1, 2, 1, '2025-04-01', '2025-04-19', '2025-04-19 05:22:14', '2025-08-02 04:06:49', NULL),
(1019, 'CUS01019', 'naimul islam', 'jony@alom52', '52', 1, 13, 10, 500, '10Mbps_Package', 10, 1, '01889800580', '', '', 9, 50, 53, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 1, 2, 1, '2025-04-01', '2025-04-21', '2025-04-20 19:19:50', '2025-06-30 18:28:13', NULL),
(1020, 'CUS01020', 'alamin', 'alamin', '4321', 1, 13, 5, 500, '15MB', 0, 1, '1', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 1, 1, 1, '2025-04-01', '2025-04-21', '2025-04-21 05:08:21', '2025-08-02 04:06:49', NULL),
(1021, 'CUS01021', 'Sujon ali', 'Sujon-61', NULL, 1, 1, 15, 500, '10Mbps_Package', 10, 1, '01316426561', '01312487076', 'ECB cottar', 45, NULL, NULL, 1, 'sujonali0131@gmail.com', '', NULL, 'Male', '', '', 'Home', 'Cat 5', 0, 0, NULL, 5, '', NULL, 145, 145, 1, '2025-04-01', '2025-04-22', '2025-04-21 18:32:30', '2025-06-30 18:28:13', NULL),
(1022, 'CUS01022', 'ALisa', 'ali2008ctg', NULL, 1, 1, 10, 1700, '10MB', 10, 2, '01881540890', '', 'South Patenga, Duria Para\r\n41 No Ward', 18, 0, 0, 0, '', '', NULL, 'Male', 'South Patenga, Duria Para', '', 'Home', 'Optical Fiber', 0, 1, NULL, 5, '', NULL, 126, 2, 1, '2025-04-01', '2025-04-22', '2025-04-22 09:44:20', '2025-07-20 09:32:00', NULL),
(1023, 'CUS01023', 'Nur m', 'Nur', NULL, 1, 1, 1, 1700, '10MB', 12, 1, '01959646582', '', 'Nur', 12, 0, 0, 1, '', '', NULL, 'Male', '', '22', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 149, 149, 1, '2025-04-01', '2025-04-22', '2025-04-22 09:46:28', '2025-06-30 18:28:13', NULL),
(1024, 'CUS01024', 'sujon ali', 'Sujon-1', NULL, 1, 1, 28, 500, '15MB', 15, 3, '0131', '0171', '', 73, 0, 0, 1, '', '', NULL, 'Male', '', '', 'Home', 'Cat 5', 0, 0, NULL, 5, '', NULL, 145, 2, 1, '2025-06-01', '2025-04-22', '2025-04-22 13:39:18', '2025-06-02 05:08:12', NULL),
(1025, 'CUS01025', 'ashiik mollik', 'test245', NULL, 1, 1, 10, 500, '15Mb@1500TK', 5, 1, '01765843744', '01300531799', '', 10, 14, 15, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 137, 2, 1, '2025-05-01', '2025-04-23', '2025-04-23 09:16:43', '2025-06-30 18:28:13', NULL),
(1026, 'CUS01026', 'tari', 'tari453', NULL, 1, 1, 10, 500, '15Mb@1500TK', 5, 1, '01765843744', '01300531799', '', 29, 0, 0, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 140, 2, 1, '2025-04-01', '2025-03-01', '2025-04-23 09:19:16', '2025-06-30 18:28:13', NULL),
(1027, 'CUS01027', 'Biddut Ahmed', 'nym.bid', '123456', 1, 9, 5, 10, 'Silver Package', 0, 1, '01633365456', '', '', 75, NULL, 0, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 1, 168, 1, '2025-04-01', '2025-04-26', '2025-04-26 09:45:45', '2025-06-30 18:28:13', NULL),
(1028, 'CUS01028', 'Gjjbvb', '56778756', NULL, 1, 1, 30, 1700, '10MB', 12, 0, '0166886689999999', '', '', 10, 14, 0, 0, '', '', NULL, 'Male', '', '', 'Home', 'Cat 5', 0, 0, NULL, 5, '', NULL, 1, 2, 1, '2025-05-01', '2025-05-03', '2025-05-03 17:33:18', '2025-05-28 07:09:52', '2025-05-28 07:09:05'),
(1029, 'CUS01029', 'Ishti', 'himel@bazar', NULL, 1, 1, 6, 500, '10Mbps_Package', 10, 1, '018282828388', '', '', 18, NULL, NULL, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 1, 2, 1, '2025-05-01', '2025-05-06', '2025-05-05 18:57:31', '2025-06-30 18:28:13', NULL),
(1030, 'CUS01030', 'mollik', 'test122', NULL, 1, 1, 10, 500, '15Mb@1500TK', 5, 1, '01765843744', '', '', 19, 51, 0, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 121, 2, 1, '2025-05-01', '2025-05-17', '2025-05-17 06:27:54', '2025-08-02 04:06:49', NULL),
(1031, 'CUS01031', 'test222', 'fsadfdsf', NULL, 1, 1, 10, 500, '15Mb@1500TK', 5, 1, '01765843744', '', '', 28, NULL, 0, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 137, 2, 1, '2025-05-01', '2025-05-17', '2025-05-17 08:23:03', '2025-06-30 18:28:13', NULL),
(1032, 'CUS01032', 'test12', 'soiiuh', NULL, 1, 1, 10, 500, '15Mb@1500TK', 5, 1, '01765843744', '', '', 11, 58, 0, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 137, 2, 1, '2025-05-01', '2025-05-17', '2025-05-17 08:55:42', '2025-06-30 18:28:13', NULL),
(1033, 'CUS01033', 'Robin Ahmed ', 'Robin', 'Robin251', 1, 9, 1, 600, 'Silver Package', 0, 1, '01705515549', '', 'Koroitoli ', 20, NULL, NULL, 1, 'robin323279@gmail.com', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 228, 228, 1, '2025-05-01', '2025-05-18', '2025-05-18 06:57:31', '2025-06-30 18:28:13', NULL),
(1034, 'CUS01034', 'demo1', 'demo1', NULL, 1, 1, 5, 500, '15MB', 0, 1, '01700000600', '', '', 9, 50, 53, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 1, 2, 1, '2025-05-01', '2025-05-19', '2025-05-19 12:08:56', '2025-06-30 18:28:13', NULL),
(1035, 'CUS01035', 'Zaaa', '12345', NULL, 1, 1, 30, 500, '15MB', 0, 1, '01740559447', '', 'Bhairab Bazar', 29, NULL, NULL, 1, '', '', NULL, 'Male', 'Bhairab Bazar', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 1, 2, 1, '2025-05-01', '2025-05-19', '2025-05-19 12:21:48', '2025-06-01 03:50:48', NULL),
(1036, 'CUS01036', 'tariqul', 'tariqul@', '1234', 1, 1, 10, 500, '10mbps', 10, 1, '8801913226994', '8801913226994', 'Keshabpur', NULL, NULL, NULL, 1, '', '', NULL, 'male', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, '2025-05-19', '2025-05-19', '2025-05-20 08:29:19', '2025-08-02 04:06:49', NULL),
(1037, 'CUS01037', 'Sadi', 'Shadi@', '1234', 1, 1, 10, 500, '10mbps', 10, 1, '8801745176868', '8801745176868', 'Mongolkot', NULL, NULL, NULL, 1, '', '', NULL, 'male', NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 2, 2, 1, '2025-05-20', '2025-05-20', '2025-05-20 08:29:19', '2025-06-30 18:28:13', NULL),
(1038, 'CUS01037', 'Abul Hasan Tushar', 'bdnet@tushar1131', NULL, 1, 1, 14, 600, '15MB', 15, 1, '01404133034', '', 'Block # C, House #- 14-1, Ganda\r\nSavar', 9, 50, 53, 0, 'bdonlinenetwork@gmail.com', '', NULL, 'Male', 'Block # C, House #- 14-1, Ganda', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 122, 2, 1, '2025-05-01', '2025-05-21', '2025-05-21 17:39:19', '2025-05-21 17:56:20', '2025-05-21 17:56:05'),
(1039, 'CUS01039', 'Home ', 'EIKBAL', NULL, 1, 1, 20, 899, '20MB', 20, 1, '01978203245', '', '', 35, NULL, NULL, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 127, 2, 1, '2025-05-01', '2025-05-22', '2025-05-22 17:59:39', '2025-06-01 03:50:48', NULL),
(1209, 'CUS01041', 'Malu', 'nxbn@nxbn.com', NULL, 1, 1, 10, 500, '500Tk Package', 500, 1, '01555555555', '', '', 61, NULL, NULL, 1, '', '', NULL, 'Male', '', '', 'Home', 'Cat 5', 0, 0, NULL, 5, '', NULL, 234, 2, 1, '2025-05-01', '2025-05-25', '2025-05-25 08:15:45', '2025-06-30 18:28:13', NULL),
(1210, 'CUS01210', 'xyz', 'xyz', NULL, 1, 1, 30, 700, '20MB', 20, 1, '01634691798', '', 'Kekania', 11, 58, NULL, 1, 'sheikhhanjala@gmail.com', '', NULL, 'Male', 'Kekania', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 138, 2, 1, '2025-05-01', '2025-05-28', '2025-05-28 09:53:19', '2025-06-01 03:50:48', NULL),
(1211, 'CUS01211', 'Ashik-BSD', 'ashik2', NULL, 1, 1, 10, 500, '15Mb@1500TK', 5, 1, '017111191444', '', 'Dc road', 29, NULL, NULL, 1, 'tse@gmail.com', '', NULL, 'Male', 'Dc road', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 133, 2, 1, '2025-05-01', '2025-05-28', '2025-05-28 11:42:45', '2025-06-30 18:28:13', NULL),
(1214, 'CUS01212', 'Zaaa', '12122', NULL, 1, 1, 30, 500, '10Mbps_Package', 10, 1, '01711604346', '', 'Bhairab Bazar', 28, NULL, NULL, 1, 'md.zakirhossain.bd.1985@gmail.com', '', NULL, 'Male', 'Bhairab Bazar', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 134, 258, 1, '2025-06-01', '2025-06-10', '2025-06-09 20:01:02', '2025-06-30 18:28:13', NULL),
(1215, 'CUS01215', 'demo1', '5555', NULL, 1, 1, 30, 700, '20MB', 20, 1, '01711604346', '', 'Bhairab Bazar', 28, NULL, NULL, 1, 'md.zakirhossain.bd.1985@gmail.com', '', NULL, 'Male', 'Bhairab Bazar', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 1, 258, 1, '2025-06-01', '2025-06-10', '2025-06-09 20:05:03', '2025-06-30 18:28:13', NULL),
(1216, 'CUS01216', 'ok', '4444', NULL, 1, 1, 30, 700, '20MB', 20, 1, '01711604346', '', 'Bhairab Bazar', 29, NULL, NULL, 1, 'md.zakirhossain.bd.1985@gmail.com', '', NULL, 'Male', 'Bhairab Bazar', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 1, 258, 1, '2025-06-01', '2025-06-10', '2025-06-09 20:08:19', '2025-06-30 18:28:13', NULL),
(1217, 'CUS01217', 'ayet', 'khan', NULL, 1, 1, 22, 600, '15MB', 15, 1, '00000000000', '44242442424', '', 70, NULL, NULL, 1, '', '', NULL, 'Female', '', '', 'Home', 'Cat 5', 0, 0, NULL, 5, '', NULL, 106, 1, 1, '2025-06-01', '2025-06-22', '2025-06-22 10:17:07', '2025-06-30 18:28:13', NULL),
(1218, 'CUS01218', 'সুমন শেখ ', 'ARB-d06', NULL, 1, 1, 0, 500, '15Mb@1500TK', 5, 1, '01300223344', '01700445522', 'মধুরচর-৪ কমল শিকদারের পাশে ', 77, 0, 0, 0, '', '', NULL, 'Male', '', '0171 m550-m589', 'Home', 'Optical Fiber', 0, 0, NULL, 5, 'gfxhb', NULL, 1, 1, 1, '2025-06-01', '2025-06-23', '2025-06-23 17:10:04', '2025-06-28 07:38:59', '2025-06-23 17:14:06'),
(1219, 'CUS01219', 'sahid', 'sahid', '1919', 1, 13, 5, 600, '5Mb@500TK', 5, 1, '1', '', '', 9, 50, 0, 0, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 1, 2, 1, '2025-06-01', '2025-06-25', '2025-06-25 09:55:06', '2025-06-25 10:53:46', '2025-06-25 10:53:06'),
(1220, 'CUS01220', 'argo', 'argo', '1919', 1, 13, 5, 600, '5Mb@500TK', 5, 1, '1', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 1, 1, 1, '2025-06-01', '2025-06-25', '2025-06-25 10:05:25', '2025-06-30 18:28:13', NULL),
(1221, 'CUS01221', 'barak-maruf', 'barak-maruf', '1919', 1, 13, 5, 600, '5Mb@500TK', 5, 1, '1', '', '', 10, 63, 0, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 1, 2, 1, '2025-06-01', '2025-06-25', '2025-06-25 10:07:20', '2025-06-30 18:28:13', NULL),
(1222, 'CUS01222', 'sahid', 'sahid', '1919', 1, 15, 5, 600, '5Mb@500TK', 5, 1, '1', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 1, 1, 1, '2025-06-01', '2025-06-25', '2025-06-25 10:47:53', '2025-06-25 10:53:40', '2025-06-25 10:53:06'),
(1223, 'CUS01223', 'argo', 'argo', '1919', 1, 15, 5, 600, '5Mb@500TK', 5, 1, '1', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 1, 1, 1, '2025-06-01', '2025-06-25', '2025-06-25 10:48:50', '2025-06-25 11:00:19', '2025-06-25 11:00:06'),
(1226, 'CUS01226', 'zurihul-hb5', 'zurihul-hb6', NULL, 1, 1, 25, 500, '10MB', 10, 1, '01955922099', '', '', 30, 0, 0, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 137, 2, 1, '2025-06-01', '2025-06-25', '2025-06-25 11:01:38', '2025-06-30 18:28:13', NULL),
(1227, 'CUS01227', 'sahid', 'sahid', '1919', 1, 16, 5, 600, '5Mb@500TK', 5, 1, '1', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 1, 1, 1, '2025-06-01', '2025-06-25', '2025-06-25 11:19:36', '2025-06-25 11:20:12', '2025-06-25 11:20:06'),
(1228, 'CUS01228', 'argo', 'argo', '1919', 1, 16, 5, 600, '5Mb@500TK', 5, 1, '1', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 1, 1, 1, '2025-06-01', '2025-06-25', '2025-06-25 11:44:24', '2025-06-30 18:28:13', NULL),
(1229, 'CUS01229', 'jakir', 'j01jakir', NULL, 1, 1, 12, 600, '15MB', 15, 1, '01912420566', '01712348764', 'kuppar pollis line mur netrakona', 10, 63, NULL, 1, 'ssnsbd.info@gmail.com', '235467655', NULL, 'Male', '123456789', 'oo92', 'Home', 'Optical Fiber', 0, 0, NULL, 5, 'newline', NULL, 129, 2, 1, '2025-06-01', '2025-06-26', '2025-06-26 09:49:43', '2025-06-30 18:28:13', NULL),
(1230, 'CUS01230', 'sahid', 'sahid', '1919', 1, 21, 5, 600, '5Mb@500TK', 5, 1, '1', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 5, NULL, NULL, 1, 1, 1, '2025-06-01', '2025-06-26', '2025-06-26 11:07:03', '2025-06-30 18:28:13', NULL),
(1231, 'CUS01231', 'Alaudding66', 'asdfgga', 'adsaf', 1, 21, 5, 525, '15Mb@1500TK', 15, 1, '5566644', '', '', 13, NULL, NULL, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 1, 2, 1, '2025-06-01', '2025-06-28', '2025-06-28 09:17:15', '2025-06-30 18:28:13', NULL),
(1232, 'CUS01232', 'Shobuj Alom', 'Shobuj', 'asdfs', 1, 21, 10, 600, '15Mb@1500TK', 15, 1, '89896655', '', '', 10, 63, 0, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 2, 2, 1, '2025-06-01', '2025-06-28', '2025-06-28 09:22:19', '2025-08-02 04:06:49', NULL),
(1233, 'CUS01233', 'anamul', '31190', '5057', 1, 21, 30, 525, '15Mb@1500TK', 15, 1, '01877177477', '', '', 32, NULL, NULL, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 139, 1, 1, '2025-06-01', '2025-06-28', '2025-06-28 17:06:15', '2025-06-30 18:28:13', NULL),
(1235, 'CUS01235', 'Pritom Sarker', 'GT.pritom', NULL, 1, 1, 1, 800, '30MB', 30, 1, '01956845494', '01949656555', '649,North Ibrahimpur,Kafrul,Dhaka', 9, 50, 54, 1, '', '', NULL, 'Male', '', '5494', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 1, 2, 1, '2025-07-01', '2025-07-02', '2025-07-02 11:25:14', '2025-08-02 04:06:49', NULL),
(1237, 'CUS01236', 'ddl', 'gdl', NULL, 1, 1, 0, 700, '20MB', 20, 1, '018855112233', '', '', 10, 63, NULL, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 135, 2, 1, '2025-07-17', '2025-07-17', '2025-07-17 10:55:15', '2025-08-02 04:06:49', NULL),
(1243, 'CUS01243', 'gggg', 'asdfdsa', NULL, 1, 1, 0, 500, '10MB', 10, 1, '45545', '', '', 13, NULL, NULL, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 135, 2, 1, '2025-07-21', '2025-07-21', '2025-07-21 06:07:44', '2025-08-02 04:06:49', NULL),
(1244, 'CUS01244', 'hhhh', 'asdfasdf', NULL, 1, 1, 0, 500, '10MB', 10, 1, '4456', '', '', 12, NULL, 0, 1, '', '', NULL, 'Male', '', '', 'Home', 'Optical Fiber', 0, 0, NULL, 5, '', NULL, 129, 2, 1, '2025-07-21', '2025-07-21', '2025-07-21 06:08:55', '2025-08-02 04:06:49', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_agent_activity`
--

CREATE TABLE `tbl_agent_activity` (
  `id` int NOT NULL,
  `agent_id` int NOT NULL,
  `inactive_date` date NOT NULL,
  `active_date` date DEFAULT NULL,
  `previous_due` int DEFAULT NULL,
  `inactive_ammount` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` date NOT NULL,
  `last_update` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_bill_amount_change`
--

CREATE TABLE `tbl_bill_amount_change` (
  `bill_amount_id` int NOT NULL,
  `agent_id` int NOT NULL,
  `bill_amount` int NOT NULL,
  `previous_bill_amount` int NOT NULL,
  `dueTillEdit` int NOT NULL,
  `created_at` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_complains`
--

CREATE TABLE `tbl_complains` (
  `id` int NOT NULL,
  `details` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `complain_type` int DEFAULT NULL,
  `customer_id` int NOT NULL,
  `status` tinyint(1) NOT NULL COMMENT '1= Pending, 2=processing, 3=Solved, 4=Not Solved',
  `solve_by` int NOT NULL,
  `sub_solve_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entry_by` int NOT NULL,
  `update_by` int NOT NULL,
  `complain_date` datetime NOT NULL,
  `solve_date` datetime NOT NULL,
  `entry_date` date NOT NULL,
  `priority` int NOT NULL COMMENT '1=high,2=medium,3=low	',
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_complains`
--

INSERT INTO `tbl_complains` (`id`, `details`, `note`, `complain_type`, `customer_id`, `status`, `solve_by`, `sub_solve_by`, `entry_by`, `update_by`, `complain_date`, `solve_date`, `entry_date`, `priority`, `deleted_at`) VALUES
(1, 'Net slow Dwon', 'Very Slow', 0, 1, 3, 6, '6', 2, 236, '2024-12-19 11:30:00', '2025-05-20 16:36:22', '2024-12-19', 2, NULL),
(2, 'Fake complaints from Android App', 'App check', 1, 2, 3, 7, '7', 2, 259, '2025-01-26 14:42:00', '2025-07-05 18:47:12', '2024-12-23', 2, NULL),
(3, 'Checking android application', 'Double check', 1, 1, 3, 1, '1', 2, 2, '2024-12-23 17:41:10', '0000-00-00 00:00:00', '2024-12-23', 1, NULL),
(4, 'Test Android app', 'Test', 1, 1, 3, 1, '1,2', 2, 2, '2024-12-23 17:44:39', '0000-00-00 00:00:00', '2024-12-23', 2, NULL),
(5, 'fhfh', 'jtdjtfg', 1, 2, 3, 1, '2', 2, 2, '2025-01-26 14:16:00', '2025-01-26 14:30:24', '2025-01-26', 2, NULL),
(6, 'ytuyrtu', 'rturtuyituteyu', 1, 1, 3, 1, '2,3', 2, 2, '2025-02-03 17:31:38', '0000-00-00 00:00:00', '2025-02-03', 2, NULL),
(7, '', '', 1, 5, 3, 3, '2,3', 2, 2, '2025-04-18 01:08:11', '0000-00-00 00:00:00', '2025-04-18', 1, NULL),
(8, '', 'car e problem', 1, 3, 1, 1, '2', 2, 2, '2025-04-22 12:33:29', '0000-00-00 00:00:00', '2025-04-22', 1, NULL),
(9, 'eee', 'eee', 1, 1, 1, 1, '2', 129, 129, '2025-04-24 11:49:38', '0000-00-00 00:00:00', '2025-04-24', 2, NULL),
(10, 'fbddj', 'bfjdje', 3, 1029, 3, 6, '6', 2, 2, '2025-05-19 15:11:43', '0000-00-00 00:00:00', '2025-05-19', 1, NULL),
(11, '', '', 2, 1, 3, 6, '6', 235, 235, '2025-05-19 17:01:15', '0000-00-00 00:00:00', '2025-05-19', 1, NULL),
(12, 'amar net slow', 'problem solved', 2, 1, 3, 6, '6', 2, 2, '2025-05-20 17:29:20', '0000-00-00 00:00:00', '2025-05-20', 1, NULL),
(13, '', '', 2, 936, 3, 7, '7', 2, 2, '2025-06-02 11:18:55', '0000-00-00 00:00:00', '2025-06-02', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_complains_new_user`
--

CREATE TABLE `tbl_complains_new_user` (
  `id` int NOT NULL,
  `details` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_address` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_mobile` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint NOT NULL COMMENT '1= Pending, Reviewed=2, Solved=3, 4=Not Solved',
  `solve_by` int NOT NULL,
  `entry_by` int NOT NULL,
  `update_by` int NOT NULL,
  `complain_date` date NOT NULL,
  `solve_date` date NOT NULL,
  `entry_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_complain_templates`
--

CREATE TABLE `tbl_complain_templates` (
  `id` int NOT NULL,
  `template` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_complain_templates`
--

INSERT INTO `tbl_complain_templates` (`id`, `template`, `deleted_at`) VALUES
(1, 'Cable Cutting', NULL),
(2, 'net slow', NULL),
(3, 'Matikata Bazar', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_due_logs`
--

CREATE TABLE `tbl_due_logs` (
  `id` int NOT NULL,
  `agid` int DEFAULT NULL,
  `month_bill` int DEFAULT '0',
  `due` int DEFAULT '0',
  `generate_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_due_logs`
--

INSERT INTO `tbl_due_logs` (`id`, `agid`, `month_bill`, `due`, `generate_date`, `created_at`, `updated_at`) VALUES
(1, 1, 1000, 1000, '2024-12-17', '2024-12-17 10:54:07', '2024-12-17 10:54:07'),
(2, 2, 1000, 1000, '2024-12-17', '2024-12-17 10:54:07', '2024-12-17 10:54:07'),
(3, 3, 1000, 1000, '2024-12-17', '2024-12-17 10:54:07', '2024-12-17 10:54:07'),
(4, 4, 500, 500, '2024-12-17', '2024-12-17 10:54:07', '2024-12-17 10:54:07'),
(8, 5, 1000, 300, '2024-12-17', '2024-12-17 11:11:34', '2024-12-17 11:11:34'),
(9, 7, 500, 0, '2024-12-18', '2024-12-18 05:51:54', '2024-12-18 05:51:54'),
(10, 934, 500, 0, '2024-12-22', '2024-12-22 03:57:04', '2024-12-22 03:57:04'),
(11, 1, 1000, 900, '2025-01-02', '2025-01-02 03:38:32', '2025-01-02 03:38:32'),
(12, 2, 1000, 1000, '2025-01-02', '2025-01-02 03:38:32', '2025-01-02 03:38:32'),
(13, 3, 1000, 1000, '2025-01-02', '2025-01-02 03:38:32', '2025-01-02 03:38:32'),
(14, 4, 1000, 1000, '2025-01-02', '2025-01-02 03:38:32', '2025-01-02 03:38:32'),
(15, 5, 1000, 1000, '2025-01-02', '2025-01-02 03:38:32', '2025-01-02 03:38:32'),
(16, 6, 500, 500, '2025-01-02', '2025-01-02 03:38:32', '2025-01-02 03:38:32'),
(17, 7, 500, 500, '2025-01-02', '2025-01-02 03:38:32', '2025-01-02 03:38:32'),
(18, 934, 500, 500, '2025-01-02', '2025-01-02 03:38:32', '2025-01-02 03:38:32'),
(19, 941, 500, 500, '2025-01-02', '2025-01-02 03:38:32', '2025-01-02 03:38:32'),
(20, 1, 1000, 1900, '2025-02-01', '2025-02-01 03:52:11', '2025-02-01 03:52:11'),
(21, 2, 1000, 2000, '2025-02-01', '2025-02-01 03:52:11', '2025-02-01 03:52:11'),
(22, 3, 1000, 2000, '2025-02-01', '2025-02-01 03:52:11', '2025-02-01 03:52:11'),
(23, 4, 1000, 1000, '2025-02-01', '2025-02-01 03:52:11', '2025-02-01 03:52:11'),
(24, 5, 1000, 2000, '2025-02-01', '2025-02-01 03:52:11', '2025-02-01 03:52:11'),
(25, 6, 500, 1000, '2025-02-01', '2025-02-01 03:52:11', '2025-02-01 03:52:11'),
(26, 7, 500, 1000, '2025-02-01', '2025-02-01 03:52:11', '2025-02-01 03:52:11'),
(27, 934, 500, 500, '2025-02-01', '2025-02-01 03:52:11', '2025-02-01 03:52:11'),
(28, 941, 500, 500, '2025-02-01', '2025-02-01 03:52:11', '2025-02-01 03:52:11'),
(29, 942, 500, 500, '2025-02-01', '2025-02-01 03:52:11', '2025-02-01 03:52:11'),
(30, 944, 500, 500, '2025-02-01', '2025-02-01 03:52:11', '2025-02-01 03:52:11'),
(31, 945, 500, 500, '2025-02-01', '2025-02-01 03:52:11', '2025-02-01 03:52:11'),
(35, 946, 500, 0, '2025-02-01', '2025-02-01 05:34:36', '2025-02-01 05:34:36'),
(36, 949, 500, 0, '2025-02-03', '2025-02-03 06:53:18', '2025-02-03 06:53:18'),
(37, 950, 500, 0, '2025-02-03', '2025-02-03 06:54:27', '2025-02-03 06:54:27'),
(38, 951, 500, 200, '2025-02-11', '2025-02-11 12:18:37', '2025-02-11 12:18:37'),
(39, 952, 500, 0, '2025-02-11', '2025-02-11 12:22:56', '2025-02-11 12:22:56'),
(40, 954, 1700, 1200, '2025-02-24', '2025-02-24 04:22:52', '2025-02-24 04:22:52'),
(41, 1, 1000, 1000, '2025-03-01', '2025-03-01 04:40:58', '2025-03-01 04:40:58'),
(42, 2, 1000, 1000, '2025-03-01', '2025-03-01 04:40:58', '2025-03-01 04:40:58'),
(43, 3, 1000, 1000, '2025-03-01', '2025-03-01 04:40:58', '2025-03-01 04:40:58'),
(44, 4, 1000, 800, '2025-03-01', '2025-03-01 04:40:58', '2025-03-01 04:40:58'),
(45, 5, 1000, 1000, '2025-03-01', '2025-03-01 04:40:58', '2025-03-01 04:40:58'),
(46, 6, 500, 500, '2025-03-01', '2025-03-01 04:40:58', '2025-03-01 04:40:58'),
(47, 7, 500, 500, '2025-03-01', '2025-03-01 04:40:58', '2025-03-01 04:40:58'),
(48, 934, 500, 500, '2025-03-01', '2025-03-01 04:40:58', '2025-03-01 04:40:58'),
(49, 941, 500, 500, '2025-03-01', '2025-03-01 04:40:58', '2025-03-01 04:40:58'),
(50, 942, 500, 500, '2025-03-01', '2025-03-01 04:40:58', '2025-03-01 04:40:58'),
(51, 951, 500, 500, '2025-03-01', '2025-03-01 04:40:58', '2025-03-01 04:40:58'),
(52, 952, 500, 500, '2025-03-01', '2025-03-01 04:40:58', '2025-03-01 04:40:58'),
(53, 953, 500, 500, '2025-03-01', '2025-03-01 04:40:58', '2025-03-01 04:40:58'),
(54, 954, 1700, 1700, '2025-03-01', '2025-03-01 04:40:58', '2025-03-01 04:40:58'),
(55, 955, 500, 500, '2025-03-01', '2025-03-01 04:40:58', '2025-03-01 04:40:58'),
(56, 2, 1000, 1000, '2025-04-03', '2025-04-03 17:59:07', '2025-04-03 17:59:07'),
(57, 3, 1000, 2000, '2025-04-03', '2025-04-03 17:59:07', '2025-04-03 17:59:07'),
(58, 4, 1000, 1000, '2025-04-03', '2025-04-03 17:59:07', '2025-04-03 17:59:07'),
(59, 5, 1000, 2000, '2025-04-03', '2025-04-03 17:59:07', '2025-04-03 17:59:07'),
(60, 6, 500, 1000, '2025-04-03', '2025-04-03 17:59:07', '2025-04-03 17:59:07'),
(61, 7, 500, 1000, '2025-04-03', '2025-04-03 17:59:07', '2025-04-03 17:59:07'),
(62, 934, 500, 1000, '2025-04-03', '2025-04-03 17:59:07', '2025-04-03 17:59:07'),
(63, 941, 500, 1000, '2025-04-03', '2025-04-03 17:59:07', '2025-04-03 17:59:07'),
(64, 942, 500, 1000, '2025-04-03', '2025-04-03 17:59:07', '2025-04-03 17:59:07'),
(65, 951, 500, 500, '2025-04-03', '2025-04-03 17:59:07', '2025-04-03 17:59:07'),
(66, 952, 500, 1000, '2025-04-03', '2025-04-03 17:59:07', '2025-04-03 17:59:07'),
(67, 953, 500, 1000, '2025-04-03', '2025-04-03 17:59:07', '2025-04-03 17:59:07'),
(68, 954, 1700, 1700, '2025-04-03', '2025-04-03 17:59:07', '2025-04-03 17:59:07'),
(69, 955, 500, 800, '2025-04-03', '2025-04-03 17:59:07', '2025-04-03 17:59:07'),
(70, 1016, 500, 500, '2025-04-03', '2025-04-03 17:59:07', '2025-04-03 17:59:07'),
(71, 1017, 500, 500, '2025-04-03', '2025-04-03 17:59:07', '2025-04-03 17:59:07'),
(72, 1019, 500, 500, '2025-04-21', '2025-04-20 19:19:50', '2025-04-20 19:19:50'),
(73, 1024, 500, 465, '2025-04-22', '2025-04-22 13:39:18', '2025-04-22 13:39:18'),
(74, 1025, 500, 500, '2025-04-23', '2025-04-23 09:16:43', '2025-04-23 09:16:43'),
(75, 7, 500, 500, '2025-05-01', '2025-05-01 09:19:21', '2025-05-01 09:19:21'),
(76, 934, 500, 500, '2025-05-01', '2025-05-01 09:19:21', '2025-05-01 09:19:21'),
(77, 941, 500, 1000, '2025-05-01', '2025-05-01 09:19:21', '2025-05-01 09:19:21'),
(78, 951, 500, 1000, '2025-05-01', '2025-05-01 09:19:21', '2025-05-01 09:19:21'),
(79, 952, 500, 1000, '2025-05-01', '2025-05-01 09:19:21', '2025-05-01 09:19:21'),
(80, 953, 500, 1000, '2025-05-01', '2025-05-01 09:19:21', '2025-05-01 09:19:21'),
(81, 954, 1700, 3400, '2025-05-01', '2025-05-01 09:19:21', '2025-05-01 09:19:21'),
(82, 955, 500, 1000, '2025-05-01', '2025-05-01 09:19:21', '2025-05-01 09:19:21'),
(83, 1016, 500, 1000, '2025-05-01', '2025-05-01 09:19:21', '2025-05-01 09:19:21'),
(84, 1017, 500, 1000, '2025-05-01', '2025-05-01 09:19:21', '2025-05-01 09:19:21'),
(85, 1018, 500, -25, '2025-05-01', '2025-05-01 09:19:21', '2025-05-01 09:19:21'),
(86, 1019, 500, 500, '2025-05-01', '2025-05-01 09:19:21', '2025-05-01 09:19:21'),
(87, 1020, 500, 1000, '2025-05-01', '2025-05-01 09:19:21', '2025-05-01 09:19:21'),
(88, 1021, 500, 1000, '2025-05-01', '2025-05-01 09:19:21', '2025-05-01 09:19:21'),
(89, 1023, 1700, 3400, '2025-05-01', '2025-05-01 09:19:21', '2025-05-01 09:19:21'),
(90, 1024, 500, 1000, '2025-05-01', '2025-05-01 09:19:21', '2025-05-01 09:19:21'),
(91, 1025, 500, 1000, '2025-05-01', '2025-05-01 09:19:21', '2025-05-01 09:19:21'),
(92, 1026, 500, 500, '2025-05-01', '2025-05-01 09:19:21', '2025-05-01 09:19:21'),
(93, 1027, 10, 10, '2025-05-01', '2025-05-01 09:19:21', '2025-05-01 09:19:21'),
(94, 934, 500, 0, '2025-05-13', '2025-05-13 11:24:21', '2025-05-13 11:24:21'),
(95, 941, 500, 0, '2025-05-13', '2025-05-13 11:24:21', '2025-05-13 11:24:21'),
(96, 951, 500, -50, '2025-05-13', '2025-05-13 11:24:21', '2025-05-13 11:24:21'),
(97, 952, 500, 0, '2025-05-13', '2025-05-13 11:24:21', '2025-05-13 11:24:21'),
(98, 953, 500, -70, '2025-05-13', '2025-05-13 11:24:21', '2025-05-13 11:24:21'),
(99, 954, 1700, 0, '2025-05-13', '2025-05-13 11:24:21', '2025-05-13 11:24:21'),
(100, 955, 500, 300, '2025-05-13', '2025-05-13 11:24:21', '2025-05-13 11:24:21'),
(101, 1016, 500, 1000, '2025-05-13', '2025-05-13 11:24:21', '2025-05-13 11:24:21'),
(102, 1017, 500, 1000, '2025-05-13', '2025-05-13 11:24:21', '2025-05-13 11:24:21'),
(103, 1018, 500, -25, '2025-05-13', '2025-05-13 11:24:21', '2025-05-13 11:24:21'),
(104, 1019, 500, 500, '2025-05-13', '2025-05-13 11:24:21', '2025-05-13 11:24:21'),
(105, 1020, 500, 0, '2025-05-13', '2025-05-13 11:24:21', '2025-05-13 11:24:21'),
(106, 1021, 500, 1000, '2025-05-13', '2025-05-13 11:24:21', '2025-05-13 11:24:21'),
(107, 1023, 1700, 3400, '2025-05-13', '2025-05-13 11:24:21', '2025-05-13 11:24:21'),
(108, 1025, 500, 0, '2025-05-13', '2025-05-13 11:24:21', '2025-05-13 11:24:21'),
(109, 1026, 500, 0, '2025-05-13', '2025-05-13 11:24:21', '2025-05-13 11:24:21'),
(110, 1027, 10, 0, '2025-05-13', '2025-05-13 11:24:21', '2025-05-13 11:24:21'),
(111, 1029, 500, 500, '2025-05-13', '2025-05-13 11:24:21', '2025-05-13 11:24:21'),
(125, 934, 500, 0, '2025-05-13', '2025-05-13 11:31:09', '2025-05-13 11:31:09'),
(126, 941, 500, 0, '2025-05-13', '2025-05-13 11:31:09', '2025-05-13 11:31:09'),
(127, 951, 500, -50, '2025-05-13', '2025-05-13 11:31:09', '2025-05-13 11:31:09'),
(128, 952, 500, 0, '2025-05-13', '2025-05-13 11:31:09', '2025-05-13 11:31:09'),
(129, 953, 500, -70, '2025-05-13', '2025-05-13 11:31:09', '2025-05-13 11:31:09'),
(130, 954, 1700, 0, '2025-05-13', '2025-05-13 11:31:09', '2025-05-13 11:31:09'),
(131, 955, 500, 300, '2025-05-13', '2025-05-13 11:31:09', '2025-05-13 11:31:09'),
(132, 1016, 500, 1000, '2025-05-13', '2025-05-13 11:31:09', '2025-05-13 11:31:09'),
(133, 1017, 500, 1000, '2025-05-13', '2025-05-13 11:31:09', '2025-05-13 11:31:09'),
(134, 1018, 500, -25, '2025-05-13', '2025-05-13 11:31:09', '2025-05-13 11:31:09'),
(135, 1019, 500, 500, '2025-05-13', '2025-05-13 11:31:09', '2025-05-13 11:31:09'),
(136, 1020, 500, 0, '2025-05-13', '2025-05-13 11:31:09', '2025-05-13 11:31:09'),
(137, 1021, 500, 1000, '2025-05-13', '2025-05-13 11:31:09', '2025-05-13 11:31:09'),
(138, 1023, 1700, 3400, '2025-05-13', '2025-05-13 11:31:09', '2025-05-13 11:31:09'),
(139, 1025, 500, 0, '2025-05-13', '2025-05-13 11:31:09', '2025-05-13 11:31:09'),
(140, 1026, 500, 0, '2025-05-13', '2025-05-13 11:31:09', '2025-05-13 11:31:09'),
(141, 1027, 10, 0, '2025-05-13', '2025-05-13 11:31:09', '2025-05-13 11:31:09'),
(142, 1029, 500, 1000, '2025-05-13', '2025-05-13 11:31:09', '2025-05-13 11:31:09'),
(156, 934, 500, 0, '2025-05-13', '2025-05-13 11:36:43', '2025-05-13 11:36:43'),
(157, 941, 500, 0, '2025-05-13', '2025-05-13 11:36:43', '2025-05-13 11:36:43'),
(158, 951, 500, -50, '2025-05-13', '2025-05-13 11:36:43', '2025-05-13 11:36:43'),
(159, 952, 500, 0, '2025-05-13', '2025-05-13 11:36:43', '2025-05-13 11:36:43'),
(160, 953, 500, -70, '2025-05-13', '2025-05-13 11:36:43', '2025-05-13 11:36:43'),
(161, 954, 1700, 0, '2025-05-13', '2025-05-13 11:36:43', '2025-05-13 11:36:43'),
(162, 955, 500, 300, '2025-05-13', '2025-05-13 11:36:43', '2025-05-13 11:36:43'),
(163, 1016, 500, 1000, '2025-05-13', '2025-05-13 11:36:43', '2025-05-13 11:36:43'),
(164, 1017, 500, 1000, '2025-05-13', '2025-05-13 11:36:43', '2025-05-13 11:36:43'),
(165, 1018, 500, -25, '2025-05-13', '2025-05-13 11:36:43', '2025-05-13 11:36:43'),
(166, 1019, 500, 500, '2025-05-13', '2025-05-13 11:36:43', '2025-05-13 11:36:43'),
(167, 1020, 500, 0, '2025-05-13', '2025-05-13 11:36:43', '2025-05-13 11:36:43'),
(168, 1021, 500, 1000, '2025-05-13', '2025-05-13 11:36:43', '2025-05-13 11:36:43'),
(169, 1023, 1700, 3400, '2025-05-13', '2025-05-13 11:36:43', '2025-05-13 11:36:43'),
(170, 1025, 500, 0, '2025-05-13', '2025-05-13 11:36:43', '2025-05-13 11:36:43'),
(171, 1026, 500, 0, '2025-05-13', '2025-05-13 11:36:43', '2025-05-13 11:36:43'),
(172, 1027, 10, 0, '2025-05-13', '2025-05-13 11:36:43', '2025-05-13 11:36:43'),
(173, 1029, 500, 1000, '2025-05-13', '2025-05-13 11:36:43', '2025-05-13 11:36:43'),
(187, 934, 500, 0, '2025-05-13', '2025-05-13 11:37:23', '2025-05-13 11:37:23'),
(188, 941, 500, 0, '2025-05-13', '2025-05-13 11:37:23', '2025-05-13 11:37:23'),
(189, 951, 500, -50, '2025-05-13', '2025-05-13 11:37:23', '2025-05-13 11:37:23'),
(190, 952, 500, 0, '2025-05-13', '2025-05-13 11:37:23', '2025-05-13 11:37:23'),
(191, 953, 500, -70, '2025-05-13', '2025-05-13 11:37:23', '2025-05-13 11:37:23'),
(192, 954, 1700, 0, '2025-05-13', '2025-05-13 11:37:23', '2025-05-13 11:37:23'),
(193, 955, 500, 300, '2025-05-13', '2025-05-13 11:37:23', '2025-05-13 11:37:23'),
(194, 1016, 500, 1000, '2025-05-13', '2025-05-13 11:37:23', '2025-05-13 11:37:23'),
(195, 1017, 500, 1000, '2025-05-13', '2025-05-13 11:37:23', '2025-05-13 11:37:23'),
(196, 1018, 500, -25, '2025-05-13', '2025-05-13 11:37:23', '2025-05-13 11:37:23'),
(197, 1019, 500, 500, '2025-05-13', '2025-05-13 11:37:23', '2025-05-13 11:37:23'),
(198, 1020, 500, 0, '2025-05-13', '2025-05-13 11:37:23', '2025-05-13 11:37:23'),
(199, 1021, 500, 1000, '2025-05-13', '2025-05-13 11:37:23', '2025-05-13 11:37:23'),
(200, 1023, 1700, 3400, '2025-05-13', '2025-05-13 11:37:23', '2025-05-13 11:37:23'),
(201, 1025, 500, 0, '2025-05-13', '2025-05-13 11:37:23', '2025-05-13 11:37:23'),
(202, 1026, 500, 0, '2025-05-13', '2025-05-13 11:37:23', '2025-05-13 11:37:23'),
(203, 1027, 10, 0, '2025-05-13', '2025-05-13 11:37:23', '2025-05-13 11:37:23'),
(204, 1029, 500, 500, '2025-05-13', '2025-05-13 11:37:23', '2025-05-13 11:37:23'),
(218, 934, 500, 0, '2025-05-13', '2025-05-13 11:38:05', '2025-05-13 11:38:05'),
(219, 941, 500, 0, '2025-05-13', '2025-05-13 11:38:05', '2025-05-13 11:38:05'),
(220, 951, 500, -50, '2025-05-13', '2025-05-13 11:38:05', '2025-05-13 11:38:05'),
(221, 952, 500, 0, '2025-05-13', '2025-05-13 11:38:05', '2025-05-13 11:38:05'),
(222, 953, 500, -70, '2025-05-13', '2025-05-13 11:38:05', '2025-05-13 11:38:05'),
(223, 954, 1700, 0, '2025-05-13', '2025-05-13 11:38:05', '2025-05-13 11:38:05'),
(224, 955, 500, 300, '2025-05-13', '2025-05-13 11:38:05', '2025-05-13 11:38:05'),
(225, 1016, 500, 1000, '2025-05-13', '2025-05-13 11:38:05', '2025-05-13 11:38:05'),
(226, 1017, 500, 1000, '2025-05-13', '2025-05-13 11:38:05', '2025-05-13 11:38:05'),
(227, 1018, 500, -25, '2025-05-13', '2025-05-13 11:38:05', '2025-05-13 11:38:05'),
(228, 1019, 500, 500, '2025-05-13', '2025-05-13 11:38:05', '2025-05-13 11:38:05'),
(229, 1020, 500, 0, '2025-05-13', '2025-05-13 11:38:05', '2025-05-13 11:38:05'),
(230, 1021, 500, 1000, '2025-05-13', '2025-05-13 11:38:05', '2025-05-13 11:38:05'),
(231, 1023, 1700, 3400, '2025-05-13', '2025-05-13 11:38:05', '2025-05-13 11:38:05'),
(232, 1025, 500, 0, '2025-05-13', '2025-05-13 11:38:05', '2025-05-13 11:38:05'),
(233, 1026, 500, 0, '2025-05-13', '2025-05-13 11:38:05', '2025-05-13 11:38:05'),
(234, 1027, 10, 0, '2025-05-13', '2025-05-13 11:38:05', '2025-05-13 11:38:05'),
(235, 1029, 500, 500, '2025-05-13', '2025-05-13 11:38:05', '2025-05-13 11:38:05'),
(236, 934, 500, 0, '2025-05-14', '2025-05-14 04:12:05', '2025-05-14 04:12:05'),
(237, 941, 500, 0, '2025-05-14', '2025-05-14 04:12:05', '2025-05-14 04:12:05'),
(238, 951, 500, -50, '2025-05-14', '2025-05-14 04:12:05', '2025-05-14 04:12:05'),
(239, 952, 500, 0, '2025-05-14', '2025-05-14 04:12:05', '2025-05-14 04:12:05'),
(240, 953, 500, -70, '2025-05-14', '2025-05-14 04:12:05', '2025-05-14 04:12:05'),
(241, 954, 1700, 0, '2025-05-14', '2025-05-14 04:12:05', '2025-05-14 04:12:05'),
(242, 955, 500, 300, '2025-05-14', '2025-05-14 04:12:05', '2025-05-14 04:12:05'),
(243, 1016, 500, 1000, '2025-05-14', '2025-05-14 04:12:05', '2025-05-14 04:12:05'),
(244, 1017, 500, 1000, '2025-05-14', '2025-05-14 04:12:05', '2025-05-14 04:12:05'),
(245, 1018, 500, -25, '2025-05-14', '2025-05-14 04:12:05', '2025-05-14 04:12:05'),
(246, 1019, 500, 500, '2025-05-14', '2025-05-14 04:12:05', '2025-05-14 04:12:05'),
(247, 1020, 500, 0, '2025-05-14', '2025-05-14 04:12:05', '2025-05-14 04:12:05'),
(248, 1021, 500, 1000, '2025-05-14', '2025-05-14 04:12:05', '2025-05-14 04:12:05'),
(249, 1023, 1700, 3400, '2025-05-14', '2025-05-14 04:12:05', '2025-05-14 04:12:05'),
(250, 1025, 500, 0, '2025-05-14', '2025-05-14 04:12:05', '2025-05-14 04:12:05'),
(251, 1026, 500, 0, '2025-05-14', '2025-05-14 04:12:05', '2025-05-14 04:12:05'),
(252, 1027, 10, 0, '2025-05-14', '2025-05-14 04:12:05', '2025-05-14 04:12:05'),
(253, 1029, 500, 500, '2025-05-14', '2025-05-14 04:12:05', '2025-05-14 04:12:05'),
(267, 934, 500, 0, '2025-05-14', '2025-05-14 04:12:57', '2025-05-14 04:12:57'),
(268, 941, 500, 0, '2025-05-14', '2025-05-14 04:12:57', '2025-05-14 04:12:57'),
(269, 951, 500, -50, '2025-05-14', '2025-05-14 04:12:57', '2025-05-14 04:12:57'),
(270, 952, 500, 0, '2025-05-14', '2025-05-14 04:12:57', '2025-05-14 04:12:57'),
(271, 953, 500, -70, '2025-05-14', '2025-05-14 04:12:57', '2025-05-14 04:12:57'),
(272, 954, 1700, 0, '2025-05-14', '2025-05-14 04:12:57', '2025-05-14 04:12:57'),
(273, 955, 500, 300, '2025-05-14', '2025-05-14 04:12:57', '2025-05-14 04:12:57'),
(274, 1016, 500, 1000, '2025-05-14', '2025-05-14 04:12:57', '2025-05-14 04:12:57'),
(275, 1017, 500, 1000, '2025-05-14', '2025-05-14 04:12:57', '2025-05-14 04:12:57'),
(276, 1018, 500, -25, '2025-05-14', '2025-05-14 04:12:57', '2025-05-14 04:12:57'),
(277, 1019, 500, 500, '2025-05-14', '2025-05-14 04:12:57', '2025-05-14 04:12:57'),
(278, 1020, 500, 0, '2025-05-14', '2025-05-14 04:12:57', '2025-05-14 04:12:57'),
(279, 1021, 500, 1000, '2025-05-14', '2025-05-14 04:12:57', '2025-05-14 04:12:57'),
(280, 1023, 1700, 3400, '2025-05-14', '2025-05-14 04:12:57', '2025-05-14 04:12:57'),
(281, 1025, 500, 0, '2025-05-14', '2025-05-14 04:12:57', '2025-05-14 04:12:57'),
(282, 1026, 500, 0, '2025-05-14', '2025-05-14 04:12:57', '2025-05-14 04:12:57'),
(283, 1027, 10, 0, '2025-05-14', '2025-05-14 04:12:57', '2025-05-14 04:12:57'),
(284, 1029, 500, 500, '2025-05-14', '2025-05-14 04:12:57', '2025-05-14 04:12:57'),
(298, 934, 500, 0, '2025-05-14', '2025-05-14 04:15:24', '2025-05-14 04:15:24'),
(299, 941, 500, 0, '2025-05-14', '2025-05-14 04:15:24', '2025-05-14 04:15:24'),
(300, 951, 500, -50, '2025-05-14', '2025-05-14 04:15:24', '2025-05-14 04:15:24'),
(301, 952, 500, 0, '2025-05-14', '2025-05-14 04:15:24', '2025-05-14 04:15:24'),
(302, 953, 500, -70, '2025-05-14', '2025-05-14 04:15:24', '2025-05-14 04:15:24'),
(303, 954, 1700, 0, '2025-05-14', '2025-05-14 04:15:24', '2025-05-14 04:15:24'),
(304, 955, 500, 300, '2025-05-14', '2025-05-14 04:15:24', '2025-05-14 04:15:24'),
(305, 1016, 500, 1000, '2025-05-14', '2025-05-14 04:15:24', '2025-05-14 04:15:24'),
(306, 1017, 500, 1000, '2025-05-14', '2025-05-14 04:15:24', '2025-05-14 04:15:24'),
(307, 1018, 500, -25, '2025-05-14', '2025-05-14 04:15:24', '2025-05-14 04:15:24'),
(308, 1019, 500, 500, '2025-05-14', '2025-05-14 04:15:24', '2025-05-14 04:15:24'),
(309, 1020, 500, 0, '2025-05-14', '2025-05-14 04:15:24', '2025-05-14 04:15:24'),
(310, 1021, 500, 1000, '2025-05-14', '2025-05-14 04:15:24', '2025-05-14 04:15:24'),
(311, 1023, 1700, 3400, '2025-05-14', '2025-05-14 04:15:24', '2025-05-14 04:15:24'),
(312, 1025, 500, 0, '2025-05-14', '2025-05-14 04:15:24', '2025-05-14 04:15:24'),
(313, 1026, 500, 0, '2025-05-14', '2025-05-14 04:15:24', '2025-05-14 04:15:24'),
(314, 1027, 10, 0, '2025-05-14', '2025-05-14 04:15:24', '2025-05-14 04:15:24'),
(315, 1029, 500, 500, '2025-05-14', '2025-05-14 04:15:24', '2025-05-14 04:15:24'),
(329, 934, 500, 0, '2025-05-14', '2025-05-14 04:17:24', '2025-05-14 04:17:24'),
(330, 941, 500, 0, '2025-05-14', '2025-05-14 04:17:24', '2025-05-14 04:17:24'),
(331, 951, 500, -50, '2025-05-14', '2025-05-14 04:17:24', '2025-05-14 04:17:24'),
(332, 952, 500, 0, '2025-05-14', '2025-05-14 04:17:24', '2025-05-14 04:17:24'),
(333, 953, 500, -70, '2025-05-14', '2025-05-14 04:17:24', '2025-05-14 04:17:24'),
(334, 954, 1700, 0, '2025-05-14', '2025-05-14 04:17:24', '2025-05-14 04:17:24'),
(335, 955, 500, 300, '2025-05-14', '2025-05-14 04:17:24', '2025-05-14 04:17:24'),
(336, 1016, 500, 1000, '2025-05-14', '2025-05-14 04:17:24', '2025-05-14 04:17:24'),
(337, 1017, 500, 1000, '2025-05-14', '2025-05-14 04:17:24', '2025-05-14 04:17:24'),
(338, 1018, 500, -25, '2025-05-14', '2025-05-14 04:17:24', '2025-05-14 04:17:24'),
(339, 1019, 500, 500, '2025-05-14', '2025-05-14 04:17:24', '2025-05-14 04:17:24'),
(340, 1020, 500, 0, '2025-05-14', '2025-05-14 04:17:24', '2025-05-14 04:17:24'),
(341, 1021, 500, 1000, '2025-05-14', '2025-05-14 04:17:24', '2025-05-14 04:17:24'),
(342, 1023, 1700, 3400, '2025-05-14', '2025-05-14 04:17:24', '2025-05-14 04:17:24'),
(343, 1025, 500, 0, '2025-05-14', '2025-05-14 04:17:24', '2025-05-14 04:17:24'),
(344, 1026, 500, 0, '2025-05-14', '2025-05-14 04:17:24', '2025-05-14 04:17:24'),
(345, 1027, 10, 0, '2025-05-14', '2025-05-14 04:17:24', '2025-05-14 04:17:24'),
(346, 1029, 500, 500, '2025-05-14', '2025-05-14 04:17:24', '2025-05-14 04:17:24'),
(360, 934, 500, 0, '2025-05-14', '2025-05-14 04:20:56', '2025-05-14 04:20:56'),
(361, 941, 500, 0, '2025-05-14', '2025-05-14 04:20:56', '2025-05-14 04:20:56'),
(362, 951, 500, -50, '2025-05-14', '2025-05-14 04:20:56', '2025-05-14 04:20:56'),
(363, 952, 500, 0, '2025-05-14', '2025-05-14 04:20:56', '2025-05-14 04:20:56'),
(364, 953, 500, -70, '2025-05-14', '2025-05-14 04:20:56', '2025-05-14 04:20:56'),
(365, 954, 1700, 0, '2025-05-14', '2025-05-14 04:20:56', '2025-05-14 04:20:56'),
(366, 955, 500, 300, '2025-05-14', '2025-05-14 04:20:56', '2025-05-14 04:20:56'),
(367, 1016, 500, 1000, '2025-05-14', '2025-05-14 04:20:56', '2025-05-14 04:20:56'),
(368, 1017, 500, 1000, '2025-05-14', '2025-05-14 04:20:56', '2025-05-14 04:20:56'),
(369, 1018, 500, -25, '2025-05-14', '2025-05-14 04:20:56', '2025-05-14 04:20:56'),
(370, 1019, 500, 500, '2025-05-14', '2025-05-14 04:20:56', '2025-05-14 04:20:56'),
(371, 1020, 500, 0, '2025-05-14', '2025-05-14 04:20:56', '2025-05-14 04:20:56'),
(372, 1021, 500, 1000, '2025-05-14', '2025-05-14 04:20:56', '2025-05-14 04:20:56'),
(373, 1023, 1700, 3400, '2025-05-14', '2025-05-14 04:20:56', '2025-05-14 04:20:56'),
(374, 1025, 500, 0, '2025-05-14', '2025-05-14 04:20:56', '2025-05-14 04:20:56'),
(375, 1026, 500, 0, '2025-05-14', '2025-05-14 04:20:56', '2025-05-14 04:20:56'),
(376, 1027, 10, 0, '2025-05-14', '2025-05-14 04:20:56', '2025-05-14 04:20:56'),
(377, 1029, 500, 500, '2025-05-14', '2025-05-14 04:20:56', '2025-05-14 04:20:56'),
(391, 934, 500, 0, '2025-05-14', '2025-05-14 04:26:53', '2025-05-14 04:26:53'),
(392, 941, 500, 0, '2025-05-14', '2025-05-14 04:26:53', '2025-05-14 04:26:53'),
(393, 951, 500, -50, '2025-05-14', '2025-05-14 04:26:53', '2025-05-14 04:26:53'),
(394, 952, 500, 0, '2025-05-14', '2025-05-14 04:26:53', '2025-05-14 04:26:53'),
(395, 953, 500, -70, '2025-05-14', '2025-05-14 04:26:53', '2025-05-14 04:26:53'),
(396, 954, 1700, 0, '2025-05-14', '2025-05-14 04:26:53', '2025-05-14 04:26:53'),
(397, 955, 500, 300, '2025-05-14', '2025-05-14 04:26:53', '2025-05-14 04:26:53'),
(398, 1016, 500, 1000, '2025-05-14', '2025-05-14 04:26:53', '2025-05-14 04:26:53'),
(399, 1017, 500, 1000, '2025-05-14', '2025-05-14 04:26:53', '2025-05-14 04:26:53'),
(400, 1018, 500, -25, '2025-05-14', '2025-05-14 04:26:53', '2025-05-14 04:26:53'),
(401, 1019, 500, 500, '2025-05-14', '2025-05-14 04:26:53', '2025-05-14 04:26:53'),
(402, 1020, 500, 0, '2025-05-14', '2025-05-14 04:26:53', '2025-05-14 04:26:53'),
(403, 1021, 500, 1000, '2025-05-14', '2025-05-14 04:26:53', '2025-05-14 04:26:53'),
(404, 1023, 1700, 3400, '2025-05-14', '2025-05-14 04:26:53', '2025-05-14 04:26:53'),
(405, 1025, 500, 0, '2025-05-14', '2025-05-14 04:26:53', '2025-05-14 04:26:53'),
(406, 1026, 500, 0, '2025-05-14', '2025-05-14 04:26:53', '2025-05-14 04:26:53'),
(407, 1027, 10, 0, '2025-05-14', '2025-05-14 04:26:53', '2025-05-14 04:26:53'),
(408, 1029, 500, 500, '2025-05-14', '2025-05-14 04:26:53', '2025-05-14 04:26:53'),
(422, 934, 500, 0, '2025-05-14', '2025-05-14 04:29:44', '2025-05-14 04:29:44'),
(423, 941, 500, 0, '2025-05-14', '2025-05-14 04:29:44', '2025-05-14 04:29:44'),
(424, 951, 500, -50, '2025-05-14', '2025-05-14 04:29:44', '2025-05-14 04:29:44'),
(425, 952, 500, 0, '2025-05-14', '2025-05-14 04:29:44', '2025-05-14 04:29:44'),
(426, 953, 500, -70, '2025-05-14', '2025-05-14 04:29:44', '2025-05-14 04:29:44'),
(427, 954, 1700, 0, '2025-05-14', '2025-05-14 04:29:44', '2025-05-14 04:29:44'),
(428, 955, 500, 300, '2025-05-14', '2025-05-14 04:29:44', '2025-05-14 04:29:44'),
(429, 1016, 500, 1000, '2025-05-14', '2025-05-14 04:29:44', '2025-05-14 04:29:44'),
(430, 1017, 500, 1000, '2025-05-14', '2025-05-14 04:29:44', '2025-05-14 04:29:44'),
(431, 1018, 500, -25, '2025-05-14', '2025-05-14 04:29:44', '2025-05-14 04:29:44'),
(432, 1019, 500, 500, '2025-05-14', '2025-05-14 04:29:44', '2025-05-14 04:29:44'),
(433, 1020, 500, 0, '2025-05-14', '2025-05-14 04:29:44', '2025-05-14 04:29:44'),
(434, 1021, 500, 1000, '2025-05-14', '2025-05-14 04:29:44', '2025-05-14 04:29:44'),
(435, 1023, 1700, 3400, '2025-05-14', '2025-05-14 04:29:44', '2025-05-14 04:29:44'),
(436, 1025, 500, 0, '2025-05-14', '2025-05-14 04:29:44', '2025-05-14 04:29:44'),
(437, 1026, 500, 0, '2025-05-14', '2025-05-14 04:29:44', '2025-05-14 04:29:44'),
(438, 1027, 10, 0, '2025-05-14', '2025-05-14 04:29:44', '2025-05-14 04:29:44'),
(439, 1029, 500, 500, '2025-05-14', '2025-05-14 04:29:44', '2025-05-14 04:29:44'),
(453, 934, 500, 0, '2025-05-14', '2025-05-14 04:34:07', '2025-05-14 04:34:07'),
(454, 941, 500, 0, '2025-05-14', '2025-05-14 04:34:07', '2025-05-14 04:34:07'),
(455, 951, 500, -50, '2025-05-14', '2025-05-14 04:34:07', '2025-05-14 04:34:07'),
(456, 952, 500, 0, '2025-05-14', '2025-05-14 04:34:07', '2025-05-14 04:34:07'),
(457, 953, 500, -70, '2025-05-14', '2025-05-14 04:34:07', '2025-05-14 04:34:07'),
(458, 954, 1700, 0, '2025-05-14', '2025-05-14 04:34:07', '2025-05-14 04:34:07'),
(459, 955, 500, 300, '2025-05-14', '2025-05-14 04:34:07', '2025-05-14 04:34:07'),
(460, 1016, 500, 1000, '2025-05-14', '2025-05-14 04:34:07', '2025-05-14 04:34:07'),
(461, 1017, 500, 1000, '2025-05-14', '2025-05-14 04:34:07', '2025-05-14 04:34:07'),
(462, 1018, 500, -25, '2025-05-14', '2025-05-14 04:34:07', '2025-05-14 04:34:07'),
(463, 1019, 500, 500, '2025-05-14', '2025-05-14 04:34:07', '2025-05-14 04:34:07'),
(464, 1020, 500, 0, '2025-05-14', '2025-05-14 04:34:07', '2025-05-14 04:34:07'),
(465, 1021, 500, 1000, '2025-05-14', '2025-05-14 04:34:07', '2025-05-14 04:34:07'),
(466, 1023, 1700, 3400, '2025-05-14', '2025-05-14 04:34:07', '2025-05-14 04:34:07'),
(467, 1025, 500, 0, '2025-05-14', '2025-05-14 04:34:07', '2025-05-14 04:34:07'),
(468, 1026, 500, 0, '2025-05-14', '2025-05-14 04:34:07', '2025-05-14 04:34:07'),
(469, 1027, 10, 0, '2025-05-14', '2025-05-14 04:34:07', '2025-05-14 04:34:07'),
(470, 1029, 500, 500, '2025-05-14', '2025-05-14 04:34:07', '2025-05-14 04:34:07'),
(484, 934, 500, 0, '2025-05-14', '2025-05-14 04:37:33', '2025-05-14 04:37:33'),
(485, 941, 500, 0, '2025-05-14', '2025-05-14 04:37:33', '2025-05-14 04:37:33'),
(486, 951, 500, -50, '2025-05-14', '2025-05-14 04:37:33', '2025-05-14 04:37:33'),
(487, 952, 500, 0, '2025-05-14', '2025-05-14 04:37:33', '2025-05-14 04:37:33'),
(488, 953, 500, -70, '2025-05-14', '2025-05-14 04:37:33', '2025-05-14 04:37:33'),
(489, 954, 1700, 0, '2025-05-14', '2025-05-14 04:37:33', '2025-05-14 04:37:33'),
(490, 955, 500, 300, '2025-05-14', '2025-05-14 04:37:33', '2025-05-14 04:37:33'),
(491, 1016, 500, 1000, '2025-05-14', '2025-05-14 04:37:33', '2025-05-14 04:37:33'),
(492, 1017, 500, 1000, '2025-05-14', '2025-05-14 04:37:33', '2025-05-14 04:37:33'),
(493, 1018, 500, -25, '2025-05-14', '2025-05-14 04:37:33', '2025-05-14 04:37:33'),
(494, 1019, 500, 500, '2025-05-14', '2025-05-14 04:37:33', '2025-05-14 04:37:33'),
(495, 1020, 500, 0, '2025-05-14', '2025-05-14 04:37:33', '2025-05-14 04:37:33'),
(496, 1021, 500, 1000, '2025-05-14', '2025-05-14 04:37:33', '2025-05-14 04:37:33'),
(497, 1023, 1700, 3400, '2025-05-14', '2025-05-14 04:37:33', '2025-05-14 04:37:33'),
(498, 1025, 500, 0, '2025-05-14', '2025-05-14 04:37:33', '2025-05-14 04:37:33'),
(499, 1026, 500, 0, '2025-05-14', '2025-05-14 04:37:33', '2025-05-14 04:37:33'),
(500, 1027, 10, 0, '2025-05-14', '2025-05-14 04:37:33', '2025-05-14 04:37:33'),
(501, 1029, 500, 500, '2025-05-14', '2025-05-14 04:37:33', '2025-05-14 04:37:33'),
(515, 934, 500, 0, '2025-05-14', '2025-05-14 04:52:29', '2025-05-14 04:52:29'),
(516, 941, 500, 0, '2025-05-14', '2025-05-14 04:52:29', '2025-05-14 04:52:29'),
(517, 951, 500, -50, '2025-05-14', '2025-05-14 04:52:29', '2025-05-14 04:52:29'),
(518, 952, 500, 0, '2025-05-14', '2025-05-14 04:52:29', '2025-05-14 04:52:29'),
(519, 953, 500, -70, '2025-05-14', '2025-05-14 04:52:29', '2025-05-14 04:52:29'),
(520, 954, 1700, 0, '2025-05-14', '2025-05-14 04:52:29', '2025-05-14 04:52:29'),
(521, 955, 500, 300, '2025-05-14', '2025-05-14 04:52:29', '2025-05-14 04:52:29'),
(522, 1016, 500, 1000, '2025-05-14', '2025-05-14 04:52:29', '2025-05-14 04:52:29'),
(523, 1017, 500, 1000, '2025-05-14', '2025-05-14 04:52:29', '2025-05-14 04:52:29'),
(524, 1018, 500, -25, '2025-05-14', '2025-05-14 04:52:29', '2025-05-14 04:52:29'),
(525, 1019, 500, 500, '2025-05-14', '2025-05-14 04:52:29', '2025-05-14 04:52:29'),
(526, 1020, 500, 0, '2025-05-14', '2025-05-14 04:52:29', '2025-05-14 04:52:29'),
(527, 1021, 500, 1000, '2025-05-14', '2025-05-14 04:52:29', '2025-05-14 04:52:29'),
(528, 1023, 1700, 3400, '2025-05-14', '2025-05-14 04:52:29', '2025-05-14 04:52:29'),
(529, 1025, 500, 0, '2025-05-14', '2025-05-14 04:52:29', '2025-05-14 04:52:29'),
(530, 1026, 500, 0, '2025-05-14', '2025-05-14 04:52:29', '2025-05-14 04:52:29'),
(531, 1027, 10, 0, '2025-05-14', '2025-05-14 04:52:29', '2025-05-14 04:52:29'),
(532, 1029, 500, 500, '2025-05-14', '2025-05-14 04:52:29', '2025-05-14 04:52:29'),
(546, 934, 500, 0, '2025-05-14', '2025-05-14 04:59:36', '2025-05-14 04:59:36'),
(547, 941, 500, 0, '2025-05-14', '2025-05-14 04:59:36', '2025-05-14 04:59:36'),
(548, 951, 500, -50, '2025-05-14', '2025-05-14 04:59:36', '2025-05-14 04:59:36'),
(549, 952, 500, 0, '2025-05-14', '2025-05-14 04:59:36', '2025-05-14 04:59:36'),
(550, 953, 500, -70, '2025-05-14', '2025-05-14 04:59:36', '2025-05-14 04:59:36'),
(551, 954, 1700, 0, '2025-05-14', '2025-05-14 04:59:36', '2025-05-14 04:59:36'),
(552, 955, 500, 300, '2025-05-14', '2025-05-14 04:59:36', '2025-05-14 04:59:36'),
(553, 1016, 500, 1000, '2025-05-14', '2025-05-14 04:59:36', '2025-05-14 04:59:36'),
(554, 1017, 500, 1000, '2025-05-14', '2025-05-14 04:59:36', '2025-05-14 04:59:36'),
(555, 1018, 500, -25, '2025-05-14', '2025-05-14 04:59:36', '2025-05-14 04:59:36'),
(556, 1019, 500, 500, '2025-05-14', '2025-05-14 04:59:36', '2025-05-14 04:59:36'),
(557, 1020, 500, 0, '2025-05-14', '2025-05-14 04:59:36', '2025-05-14 04:59:36'),
(558, 1021, 500, 1000, '2025-05-14', '2025-05-14 04:59:36', '2025-05-14 04:59:36'),
(559, 1023, 1700, 3400, '2025-05-14', '2025-05-14 04:59:36', '2025-05-14 04:59:36'),
(560, 1025, 500, 0, '2025-05-14', '2025-05-14 04:59:36', '2025-05-14 04:59:36'),
(561, 1026, 500, 0, '2025-05-14', '2025-05-14 04:59:36', '2025-05-14 04:59:36'),
(562, 1027, 10, 0, '2025-05-14', '2025-05-14 04:59:36', '2025-05-14 04:59:36'),
(563, 1029, 500, 500, '2025-05-14', '2025-05-14 04:59:36', '2025-05-14 04:59:36'),
(577, 934, 500, 0, '2025-05-14', '2025-05-14 05:00:33', '2025-05-14 05:00:33'),
(578, 941, 500, 0, '2025-05-14', '2025-05-14 05:00:33', '2025-05-14 05:00:33'),
(579, 951, 500, -50, '2025-05-14', '2025-05-14 05:00:33', '2025-05-14 05:00:33'),
(580, 952, 500, 0, '2025-05-14', '2025-05-14 05:00:33', '2025-05-14 05:00:33'),
(581, 953, 500, -70, '2025-05-14', '2025-05-14 05:00:33', '2025-05-14 05:00:33'),
(582, 954, 1700, 0, '2025-05-14', '2025-05-14 05:00:33', '2025-05-14 05:00:33'),
(583, 955, 500, 300, '2025-05-14', '2025-05-14 05:00:33', '2025-05-14 05:00:33'),
(584, 1016, 500, 1000, '2025-05-14', '2025-05-14 05:00:33', '2025-05-14 05:00:33'),
(585, 1017, 500, 1000, '2025-05-14', '2025-05-14 05:00:33', '2025-05-14 05:00:33'),
(586, 1018, 500, -25, '2025-05-14', '2025-05-14 05:00:33', '2025-05-14 05:00:33'),
(587, 1019, 500, 500, '2025-05-14', '2025-05-14 05:00:33', '2025-05-14 05:00:33'),
(588, 1020, 500, 0, '2025-05-14', '2025-05-14 05:00:33', '2025-05-14 05:00:33'),
(589, 1021, 500, 1000, '2025-05-14', '2025-05-14 05:00:33', '2025-05-14 05:00:33'),
(590, 1023, 1700, 3400, '2025-05-14', '2025-05-14 05:00:33', '2025-05-14 05:00:33'),
(591, 1025, 500, 0, '2025-05-14', '2025-05-14 05:00:33', '2025-05-14 05:00:33'),
(592, 1026, 500, 0, '2025-05-14', '2025-05-14 05:00:33', '2025-05-14 05:00:33'),
(593, 1027, 10, 0, '2025-05-14', '2025-05-14 05:00:33', '2025-05-14 05:00:33'),
(594, 1029, 500, 500, '2025-05-14', '2025-05-14 05:00:33', '2025-05-14 05:00:33'),
(608, 934, 500, 500, '2025-05-14', '2025-05-14 05:53:14', '2025-05-14 05:53:14'),
(609, 941, 500, 0, '2025-05-14', '2025-05-14 05:53:14', '2025-05-14 05:53:14'),
(610, 951, 500, -50, '2025-05-14', '2025-05-14 05:53:14', '2025-05-14 05:53:14'),
(611, 952, 500, 0, '2025-05-14', '2025-05-14 05:53:14', '2025-05-14 05:53:14'),
(612, 953, 500, -70, '2025-05-14', '2025-05-14 05:53:14', '2025-05-14 05:53:14'),
(613, 954, 1700, 0, '2025-05-14', '2025-05-14 05:53:14', '2025-05-14 05:53:14'),
(614, 955, 500, 300, '2025-05-14', '2025-05-14 05:53:14', '2025-05-14 05:53:14'),
(615, 1016, 500, 1000, '2025-05-14', '2025-05-14 05:53:14', '2025-05-14 05:53:14'),
(616, 1017, 500, 1000, '2025-05-14', '2025-05-14 05:53:14', '2025-05-14 05:53:14'),
(617, 1018, 500, -25, '2025-05-14', '2025-05-14 05:53:14', '2025-05-14 05:53:14'),
(618, 1019, 500, 500, '2025-05-14', '2025-05-14 05:53:14', '2025-05-14 05:53:14'),
(619, 1020, 500, 0, '2025-05-14', '2025-05-14 05:53:14', '2025-05-14 05:53:14'),
(620, 1021, 500, 1000, '2025-05-14', '2025-05-14 05:53:14', '2025-05-14 05:53:14'),
(621, 1023, 1700, 3400, '2025-05-14', '2025-05-14 05:53:14', '2025-05-14 05:53:14'),
(622, 1025, 500, 0, '2025-05-14', '2025-05-14 05:53:14', '2025-05-14 05:53:14'),
(623, 1026, 500, 0, '2025-05-14', '2025-05-14 05:53:14', '2025-05-14 05:53:14'),
(624, 1027, 10, 0, '2025-05-14', '2025-05-14 05:53:14', '2025-05-14 05:53:14'),
(625, 1029, 500, 500, '2025-05-14', '2025-05-14 05:53:14', '2025-05-14 05:53:14'),
(639, 934, 500, 500, '2025-05-14', '2025-05-14 05:54:23', '2025-05-14 05:54:23'),
(640, 941, 500, 0, '2025-05-14', '2025-05-14 05:54:23', '2025-05-14 05:54:23'),
(641, 951, 500, -50, '2025-05-14', '2025-05-14 05:54:23', '2025-05-14 05:54:23'),
(642, 952, 500, 0, '2025-05-14', '2025-05-14 05:54:23', '2025-05-14 05:54:23'),
(643, 953, 500, -70, '2025-05-14', '2025-05-14 05:54:23', '2025-05-14 05:54:23'),
(644, 954, 1700, 0, '2025-05-14', '2025-05-14 05:54:23', '2025-05-14 05:54:23'),
(645, 955, 500, 300, '2025-05-14', '2025-05-14 05:54:23', '2025-05-14 05:54:23'),
(646, 1016, 500, 1000, '2025-05-14', '2025-05-14 05:54:23', '2025-05-14 05:54:23'),
(647, 1017, 500, 1000, '2025-05-14', '2025-05-14 05:54:23', '2025-05-14 05:54:23'),
(648, 1018, 500, -25, '2025-05-14', '2025-05-14 05:54:23', '2025-05-14 05:54:23'),
(649, 1019, 500, 500, '2025-05-14', '2025-05-14 05:54:23', '2025-05-14 05:54:23'),
(650, 1020, 500, 0, '2025-05-14', '2025-05-14 05:54:23', '2025-05-14 05:54:23'),
(651, 1021, 500, 1000, '2025-05-14', '2025-05-14 05:54:23', '2025-05-14 05:54:23'),
(652, 1023, 1700, 3400, '2025-05-14', '2025-05-14 05:54:23', '2025-05-14 05:54:23'),
(653, 1025, 500, 0, '2025-05-14', '2025-05-14 05:54:23', '2025-05-14 05:54:23'),
(654, 1026, 500, 0, '2025-05-14', '2025-05-14 05:54:23', '2025-05-14 05:54:23'),
(655, 1027, 10, 0, '2025-05-14', '2025-05-14 05:54:23', '2025-05-14 05:54:23'),
(656, 1029, 500, 500, '2025-05-14', '2025-05-14 05:54:23', '2025-05-14 05:54:23'),
(670, 934, 500, 500, '2025-05-14', '2025-05-14 05:55:09', '2025-05-14 05:55:09'),
(671, 941, 500, 0, '2025-05-14', '2025-05-14 05:55:09', '2025-05-14 05:55:09'),
(672, 951, 500, -50, '2025-05-14', '2025-05-14 05:55:09', '2025-05-14 05:55:09'),
(673, 952, 500, 0, '2025-05-14', '2025-05-14 05:55:09', '2025-05-14 05:55:09'),
(674, 953, 500, -70, '2025-05-14', '2025-05-14 05:55:09', '2025-05-14 05:55:09'),
(675, 954, 1700, 0, '2025-05-14', '2025-05-14 05:55:09', '2025-05-14 05:55:09'),
(676, 955, 500, 300, '2025-05-14', '2025-05-14 05:55:09', '2025-05-14 05:55:09'),
(677, 1016, 500, 1000, '2025-05-14', '2025-05-14 05:55:09', '2025-05-14 05:55:09'),
(678, 1017, 500, 1000, '2025-05-14', '2025-05-14 05:55:09', '2025-05-14 05:55:09'),
(679, 1018, 500, -25, '2025-05-14', '2025-05-14 05:55:09', '2025-05-14 05:55:09'),
(680, 1019, 500, 500, '2025-05-14', '2025-05-14 05:55:09', '2025-05-14 05:55:09'),
(681, 1020, 500, 0, '2025-05-14', '2025-05-14 05:55:09', '2025-05-14 05:55:09'),
(682, 1021, 500, 1000, '2025-05-14', '2025-05-14 05:55:09', '2025-05-14 05:55:09'),
(683, 1023, 1700, 3400, '2025-05-14', '2025-05-14 05:55:09', '2025-05-14 05:55:09'),
(684, 1025, 500, 0, '2025-05-14', '2025-05-14 05:55:09', '2025-05-14 05:55:09'),
(685, 1026, 500, 0, '2025-05-14', '2025-05-14 05:55:09', '2025-05-14 05:55:09'),
(686, 1027, 10, 0, '2025-05-14', '2025-05-14 05:55:09', '2025-05-14 05:55:09'),
(687, 1029, 500, 500, '2025-05-14', '2025-05-14 05:55:09', '2025-05-14 05:55:09'),
(701, 934, 500, 0, '2025-05-14', '2025-05-14 08:01:10', '2025-05-14 08:01:10'),
(702, 941, 500, 0, '2025-05-14', '2025-05-14 08:01:10', '2025-05-14 08:01:10'),
(703, 951, 500, -50, '2025-05-14', '2025-05-14 08:01:10', '2025-05-14 08:01:10'),
(704, 952, 500, 0, '2025-05-14', '2025-05-14 08:01:10', '2025-05-14 08:01:10'),
(705, 953, 500, -70, '2025-05-14', '2025-05-14 08:01:10', '2025-05-14 08:01:10'),
(706, 954, 1700, 0, '2025-05-14', '2025-05-14 08:01:10', '2025-05-14 08:01:10'),
(707, 955, 500, 300, '2025-05-14', '2025-05-14 08:01:10', '2025-05-14 08:01:10'),
(708, 1016, 500, 1000, '2025-05-14', '2025-05-14 08:01:10', '2025-05-14 08:01:10'),
(709, 1017, 500, 1000, '2025-05-14', '2025-05-14 08:01:10', '2025-05-14 08:01:10'),
(710, 1018, 500, -25, '2025-05-14', '2025-05-14 08:01:10', '2025-05-14 08:01:10'),
(711, 1019, 500, 500, '2025-05-14', '2025-05-14 08:01:10', '2025-05-14 08:01:10'),
(712, 1020, 500, 0, '2025-05-14', '2025-05-14 08:01:10', '2025-05-14 08:01:10'),
(713, 1021, 500, 1000, '2025-05-14', '2025-05-14 08:01:10', '2025-05-14 08:01:10'),
(714, 1023, 1700, 3400, '2025-05-14', '2025-05-14 08:01:10', '2025-05-14 08:01:10'),
(715, 1025, 500, 0, '2025-05-14', '2025-05-14 08:01:10', '2025-05-14 08:01:10'),
(716, 1026, 500, 0, '2025-05-14', '2025-05-14 08:01:10', '2025-05-14 08:01:10'),
(717, 1027, 10, 0, '2025-05-14', '2025-05-14 08:01:10', '2025-05-14 08:01:10'),
(718, 1029, 500, 500, '2025-05-14', '2025-05-14 08:01:10', '2025-05-14 08:01:10'),
(732, 934, 500, 500, '2025-05-14', '2025-05-14 10:13:58', '2025-05-14 10:13:58'),
(733, 941, 500, 0, '2025-05-14', '2025-05-14 10:13:58', '2025-05-14 10:13:58'),
(734, 951, 500, -50, '2025-05-14', '2025-05-14 10:13:58', '2025-05-14 10:13:58'),
(735, 952, 500, 0, '2025-05-14', '2025-05-14 10:13:58', '2025-05-14 10:13:58'),
(736, 953, 500, -70, '2025-05-14', '2025-05-14 10:13:58', '2025-05-14 10:13:58'),
(737, 954, 1700, 0, '2025-05-14', '2025-05-14 10:13:58', '2025-05-14 10:13:58'),
(738, 955, 500, 300, '2025-05-14', '2025-05-14 10:13:58', '2025-05-14 10:13:58'),
(739, 1016, 500, 1000, '2025-05-14', '2025-05-14 10:13:58', '2025-05-14 10:13:58'),
(740, 1017, 500, 1000, '2025-05-14', '2025-05-14 10:13:58', '2025-05-14 10:13:58'),
(741, 1018, 500, -25, '2025-05-14', '2025-05-14 10:13:58', '2025-05-14 10:13:58'),
(742, 1019, 500, 500, '2025-05-14', '2025-05-14 10:13:58', '2025-05-14 10:13:58'),
(743, 1020, 500, 0, '2025-05-14', '2025-05-14 10:13:58', '2025-05-14 10:13:58'),
(744, 1021, 500, 1000, '2025-05-14', '2025-05-14 10:13:58', '2025-05-14 10:13:58'),
(745, 1023, 1700, 3400, '2025-05-14', '2025-05-14 10:13:58', '2025-05-14 10:13:58'),
(746, 1025, 500, 0, '2025-05-14', '2025-05-14 10:13:58', '2025-05-14 10:13:58'),
(747, 1026, 500, 0, '2025-05-14', '2025-05-14 10:13:58', '2025-05-14 10:13:58'),
(748, 1027, 10, 0, '2025-05-14', '2025-05-14 10:13:58', '2025-05-14 10:13:58'),
(749, 1029, 500, 500, '2025-05-14', '2025-05-14 10:13:58', '2025-05-14 10:13:58'),
(750, 934, 500, 500, '2025-05-15', '2025-05-15 04:21:02', '2025-05-15 04:21:02'),
(751, 941, 500, 500, '2025-05-15', '2025-05-15 04:21:02', '2025-05-15 04:21:02'),
(752, 951, 500, 500, '2025-05-15', '2025-05-15 04:21:02', '2025-05-15 04:21:02'),
(753, 952, 500, 500, '2025-05-15', '2025-05-15 04:21:02', '2025-05-15 04:21:02'),
(754, 953, 500, 500, '2025-05-15', '2025-05-15 04:21:02', '2025-05-15 04:21:02'),
(755, 954, 1700, 1700, '2025-05-15', '2025-05-15 04:21:02', '2025-05-15 04:21:02'),
(756, 955, 500, 500, '2025-05-15', '2025-05-15 04:21:02', '2025-05-15 04:21:02'),
(757, 1016, 500, 500, '2025-05-15', '2025-05-15 04:21:02', '2025-05-15 04:21:02'),
(758, 1017, 500, 500, '2025-05-15', '2025-05-15 04:21:02', '2025-05-15 04:21:02'),
(759, 1018, 500, 500, '2025-05-15', '2025-05-15 04:21:02', '2025-05-15 04:21:02'),
(760, 1019, 500, 500, '2025-05-15', '2025-05-15 04:21:02', '2025-05-15 04:21:02'),
(761, 1020, 500, 500, '2025-05-15', '2025-05-15 04:21:02', '2025-05-15 04:21:02'),
(762, 1021, 500, 500, '2025-05-15', '2025-05-15 04:21:02', '2025-05-15 04:21:02'),
(763, 1023, 1700, 1700, '2025-05-15', '2025-05-15 04:21:02', '2025-05-15 04:21:02'),
(764, 1025, 500, 500, '2025-05-15', '2025-05-15 04:21:02', '2025-05-15 04:21:02'),
(765, 1026, 500, 500, '2025-05-15', '2025-05-15 04:21:02', '2025-05-15 04:21:02'),
(766, 1027, 10, 10, '2025-05-15', '2025-05-15 04:21:02', '2025-05-15 04:21:02'),
(767, 1029, 500, 500, '2025-05-15', '2025-05-15 04:21:02', '2025-05-15 04:21:02'),
(768, 1030, 500, 500, '2025-05-17', '2025-05-17 06:27:54', '2025-05-17 06:27:54'),
(769, 1, 1000, 2000, '2025-05-17', '2025-05-17 08:28:42', '2025-05-17 08:28:42'),
(770, 1, 1000, 3000, '2025-05-17', '2025-05-17 08:30:43', '2025-05-17 08:30:43'),
(771, 1031, 500, 500, '2025-05-17', '2025-05-17 08:53:12', '2025-05-17 08:53:12'),
(772, 1032, 500, 500, '2025-05-17', '2025-05-17 08:56:15', '2025-05-17 08:56:15'),
(773, 1035, 500, 0, '2025-05-19', '2025-05-19 12:21:48', '2025-05-19 12:21:48'),
(774, 1040, 10, 0, '2025-05-24', '2025-05-24 15:48:26', '2025-05-24 15:48:26'),
(775, 1, 1000, 0, '2025-05-28', '2025-05-28 11:37:07', '2025-05-28 11:37:07'),
(776, 2, 1000, 1000, '2025-05-28', '2025-05-28 11:37:44', '2025-05-28 11:37:44'),
(777, 1211, 500, 500, '2025-05-28', '2025-05-28 11:42:45', '2025-05-28 11:42:45'),
(778, 941, 500, 500, '2025-06-01', '2025-06-01 03:50:48', '2025-06-01 03:50:48'),
(779, 951, 500, 500, '2025-06-01', '2025-06-01 03:50:48', '2025-06-01 03:50:48'),
(780, 952, 500, 500, '2025-06-01', '2025-06-01 03:50:48', '2025-06-01 03:50:48'),
(781, 953, 500, 500, '2025-06-01', '2025-06-01 03:50:48', '2025-06-01 03:50:48'),
(782, 954, 1700, 1700, '2025-06-01', '2025-06-01 03:50:48', '2025-06-01 03:50:48'),
(783, 955, 500, 500, '2025-06-01', '2025-06-01 03:50:48', '2025-06-01 03:50:48'),
(784, 1016, 500, 400, '2025-06-01', '2025-06-01 03:50:48', '2025-06-01 03:50:48'),
(785, 1017, 500, 500, '2025-06-01', '2025-06-01 03:50:48', '2025-06-01 03:50:48'),
(786, 1018, 500, 500, '2025-06-01', '2025-06-01 03:50:48', '2025-06-01 03:50:48'),
(787, 1019, 500, 500, '2025-06-01', '2025-06-01 03:50:48', '2025-06-01 03:50:48'),
(788, 1020, 500, 500, '2025-06-01', '2025-06-01 03:50:48', '2025-06-01 03:50:48'),
(789, 1021, 500, 500, '2025-06-01', '2025-06-01 03:50:48', '2025-06-01 03:50:48'),
(790, 1023, 1700, 1700, '2025-06-01', '2025-06-01 03:50:48', '2025-06-01 03:50:48'),
(791, 1025, 500, 500, '2025-06-01', '2025-06-01 03:50:48', '2025-06-01 03:50:48'),
(792, 1026, 500, 500, '2025-06-01', '2025-06-01 03:50:48', '2025-06-01 03:50:48'),
(793, 1027, 10, 10, '2025-06-01', '2025-06-01 03:50:48', '2025-06-01 03:50:48'),
(794, 1029, 500, 400, '2025-06-01', '2025-06-01 03:50:48', '2025-06-01 03:50:48'),
(795, 1030, 500, 500, '2025-06-01', '2025-06-01 03:50:48', '2025-06-01 03:50:48'),
(796, 1031, 500, 500, '2025-06-01', '2025-06-01 03:50:48', '2025-06-01 03:50:48'),
(797, 1032, 500, 500, '2025-06-01', '2025-06-01 03:50:48', '2025-06-01 03:50:48'),
(798, 1033, 600, 600, '2025-06-01', '2025-06-01 03:50:48', '2025-06-01 03:50:48'),
(799, 1034, 500, 500, '2025-06-01', '2025-06-01 03:50:48', '2025-06-01 03:50:48'),
(800, 1035, 500, 500, '2025-06-01', '2025-06-01 03:50:48', '2025-06-01 03:50:48'),
(801, 1036, 500, 500, '2025-06-01', '2025-06-01 03:50:48', '2025-06-01 03:50:48'),
(802, 1037, 500, 500, '2025-06-01', '2025-06-01 03:50:48', '2025-06-01 03:50:48'),
(803, 1039, 899, 899, '2025-06-01', '2025-06-01 03:50:48', '2025-06-01 03:50:48'),
(804, 1209, 500, 500, '2025-06-01', '2025-06-01 03:50:48', '2025-06-01 03:50:48'),
(805, 1210, 700, 700, '2025-06-01', '2025-06-01 03:50:48', '2025-06-01 03:50:48'),
(806, 1211, 500, 500, '2025-06-01', '2025-06-01 03:50:48', '2025-06-01 03:50:48'),
(807, 1217, 600, 600, '2025-06-22', '2025-06-22 10:17:07', '2025-06-22 10:17:07'),
(808, 1219, 600, 600, '2025-06-25', '2025-06-25 09:56:14', '2025-06-25 09:56:14'),
(809, 1221, 600, 600, '2025-06-25', '2025-06-25 10:08:52', '2025-06-25 10:08:52'),
(810, 1229, 600, 100, '2025-06-26', '2025-06-26 09:49:43', '2025-06-26 09:49:43'),
(811, 1231, 525, 525, '2025-06-28', '2025-06-28 09:17:15', '2025-06-28 09:17:15'),
(812, 1232, 600, 675, '2025-06-28', '2025-06-28 09:23:45', '2025-06-28 09:23:45'),
(813, 941, 500, 500, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(814, 951, 500, 500, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(815, 952, 500, 500, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(816, 953, 500, 500, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(817, 954, 1700, -13600, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(818, 955, 500, 300, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(819, 1016, 500, 300, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(820, 1017, 500, 500, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(821, 1018, 500, 500, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(822, 1019, 500, 300, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(823, 1020, 500, 500, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(824, 1021, 500, 500, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(825, 1023, 1700, 1700, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(826, 1025, 500, -6000, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(827, 1026, 500, 500, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(828, 1027, 10, -480, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(829, 1029, 500, 500, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(830, 1030, 500, 500, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(831, 1031, 500, 500, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(832, 1032, 500, 500, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(833, 1033, 600, 600, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(834, 1034, 500, 500, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(835, 1035, 500, 1000, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(836, 1036, 500, 300, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(837, 1037, 500, 500, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(838, 1039, 899, 1798, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(839, 1209, 500, 500, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(840, 1210, 700, 1430, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(841, 1211, 500, 500, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(842, 1214, 500, 500, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(843, 1215, 700, 200, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(844, 1216, 700, 700, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(845, 1217, 600, 600, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(846, 1220, 600, 600, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(847, 1221, 600, 700, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(848, 1226, 500, 500, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(849, 1228, 600, 600, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(850, 1229, 600, 700, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(851, 1230, 600, 600, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(852, 1231, 525, 1050, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(853, 1232, 600, 1275, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(854, 1233, 525, 525, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(855, 1234, 525, 525, '2025-07-01', '2025-06-30 18:28:13', '2025-06-30 18:28:13'),
(856, 1237, 700, 700, '2025-07-17', '2025-07-17 10:55:15', '2025-07-17 10:55:15'),
(857, 1235, 800, 800, '2025-07-20', '2025-07-20 11:27:06', '2025-07-20 11:27:06'),
(858, 1243, 500, -383, '2025-07-21', '2025-07-21 06:07:44', '2025-07-21 06:07:44'),
(859, 1244, 500, 600, '2025-07-21', '2025-07-21 06:14:18', '2025-07-21 06:14:18'),
(860, 1244, 500, -300, '2025-07-21', '2025-07-21 06:24:42', '2025-07-21 06:24:42'),
(861, 1244, 500, 0, '2025-07-21', '2025-07-21 06:25:58', '2025-07-21 06:25:58'),
(862, 1244, 500, 0, '2025-07-21', '2025-07-21 06:36:11', '2025-07-21 06:36:11'),
(863, 941, 500, 910, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(864, 944, 500, 1000, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(865, 951, 500, 450, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(866, 952, 500, 1000, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(867, 953, 500, 500, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(868, 954, 1700, -11900, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(869, 955, 500, 500, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(870, 1016, 500, -2200, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(871, 1017, 500, 500, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(872, 1018, 500, 800, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(873, 1019, 500, 1300, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(874, 1020, 500, 500, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(875, 1021, 500, 1000, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(876, 1023, 1700, 3400, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(877, 1025, 500, -5500, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(878, 1026, 500, 1000, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(879, 1027, 10, -470, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(880, 1029, 500, 1000, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(881, 1030, 500, 500, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(882, 1031, 500, 1000, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(883, 1032, 500, 1000, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(884, 1033, 600, 1200, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(885, 1034, 500, 1000, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(886, 1035, 500, 1500, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(887, 1036, 500, 500, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(888, 1037, 500, 1000, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49');
INSERT INTO `tbl_due_logs` (`id`, `agid`, `month_bill`, `due`, `generate_date`, `created_at`, `updated_at`) VALUES
(889, 1039, 899, 2697, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(890, 1209, 500, 1000, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(891, 1210, 700, 2130, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(892, 1211, 500, 1000, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(893, 1214, 500, 1000, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(894, 1215, 700, 900, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(895, 1216, 700, 1400, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(896, 1217, 600, 1200, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(897, 1220, 600, 1200, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(898, 1221, 600, 1300, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(899, 1226, 500, 1000, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(900, 1228, 600, 1200, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(901, 1229, 600, 1300, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(902, 1230, 600, 1200, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(903, 1231, 525, 1575, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(904, 1232, 600, 1275, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(905, 1233, 525, 1050, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(906, 1235, 800, 1600, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(907, 1237, 700, 1400, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(908, 1243, 500, 117, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49'),
(909, 1244, 500, 1000, '2025-08-02', '2025-08-02 04:06:49', '2025-08-02 04:06:49');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_due_opening_amount_and_con_charge`
--

CREATE TABLE `tbl_due_opening_amount_and_con_charge` (
  `id` int NOT NULL,
  `tbl_agent_id` int NOT NULL,
  `connection_charge_due` int NOT NULL,
  `running_month_due` int NOT NULL,
  `entry_by` int NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_employee`
--

CREATE TABLE `tbl_employee` (
  `id` int NOT NULL,
  `employee_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `salary_amount` double(10,2) DEFAULT '0.00',
  `employee_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `employee_mobile_no` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `employee_address` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `employee_email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `employee_national_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `designation` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `joining_date` date NOT NULL,
  `employee_status` int NOT NULL,
  `entry_by` int NOT NULL,
  `entry_date` date NOT NULL,
  `update_by` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPACT;

--
-- Dumping data for table `tbl_employee`
--

INSERT INTO `tbl_employee` (`id`, `employee_id`, `salary_amount`, `employee_name`, `employee_mobile_no`, `employee_address`, `employee_email`, `employee_national_id`, `designation`, `joining_date`, `employee_status`, `entry_by`, `entry_date`, `update_by`) VALUES
(7, 'EMPL00001', 200.00, 'Uzzal', '01111111111', 'fdsfds', 'ashik@gmail.com', 'fasff', 'fadsfdf', '2025-05-01', 1, 2, '2025-05-28', 1),
(10, 'EMPL00008', 50000.00, 'ashik mollik', '01765843744', 'dfafds', 'ashik@gmail.com', '3534534', 'Software Engineer', '0000-00-00', 1, 1, '2025-07-26', 0),
(11, 'EMPL00011', 30000.00, 'tariqul', '01111111111', '', 'ashik@gmail.com', '3534534', 'fadsfdf', '2025-07-26', 1, 1, '2025-07-26', 1),
(12, 'EMPL00012', 5000.00, 'Sadi', '01856622145', '', 'sadi5545@gmail.com', '5444555644', 'any', '2025-07-29', 1, 2, '2025-07-29', 0);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_employee_transaction`
--

CREATE TABLE `tbl_employee_transaction` (
  `id` int NOT NULL,
  `employee_id` int DEFAULT NULL,
  `salary_amount` float(10,2) DEFAULT '0.00',
  `conveyance` float(10,2) NOT NULL DEFAULT '0.00',
  `received_amount` float(10,2) NOT NULL DEFAULT '0.00',
  `received_due` int NOT NULL DEFAULT '0' COMMENT 'employee_salary_receive=1, employee_salary_due=0',
  `punishment` float(10,2) NOT NULL DEFAULT '0.00',
  `accounts_id` int DEFAULT NULL,
  `created_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `tbl_employee_transaction`
--

INSERT INTO `tbl_employee_transaction` (`id`, `employee_id`, `salary_amount`, `conveyance`, `received_amount`, `received_due`, `punishment`, `accounts_id`, `created_at`) VALUES
(1, 7, 200.00, 0.00, 0.00, 0, 0.00, 0, '2025-07-27'),
(2, 10, 50000.00, 0.00, 0.00, 0, 0.00, 0, '2025-07-27'),
(3, 11, 30000.00, 0.00, 0.00, 0, 0.00, 0, '2025-07-27'),
(7, 7, 0.00, 0.00, 200.00, 1, 0.00, 328, '2025-07-27'),
(9, 7, 200.00, 0.00, 0.00, 0, 0.00, 0, '2025-07-28'),
(10, 10, 50000.00, 0.00, 0.00, 0, 0.00, 0, '2025-07-28'),
(11, 11, 30000.00, 0.00, 0.00, 0, 0.00, 0, '2025-07-28'),
(12, 7, 200.00, 0.00, 0.00, 0, 0.00, 0, '2025-07-29'),
(13, 10, 50000.00, 0.00, 0.00, 0, 0.00, 0, '2025-07-29'),
(14, 11, 30000.00, 0.00, 0.00, 0, 0.00, 0, '2025-07-29'),
(15, 12, 5000.00, 0.00, 0.00, 0, 0.00, 0, '2025-07-29'),
(16, 12, 5000.00, 0.00, 0.00, 0, 0.00, 0, '2025-06-29'),
(17, 12, 100.00, 0.00, 6000.00, 1, 0.00, 330, '2025-07-29'),
(18, 12, 0.00, 0.00, 500.00, 1, 150.00, 331, '2025-07-29'),
(19, 7, 200.00, 0.00, 0.00, 0, 0.00, 0, '0000-00-00'),
(20, 10, 50000.00, 0.00, 0.00, 0, 0.00, 0, '0000-00-00'),
(21, 11, 30000.00, 0.00, 0.00, 0, 0.00, 0, '0000-00-00'),
(22, 12, 5000.00, 0.00, 0.00, 0, 0.00, 0, '0000-00-00'),
(24, 0, 0.00, 0.00, 200.00, 0, 0.00, 333, '2025-07-29'),
(25, 0, 0.00, 0.00, 0.00, 0, 0.00, 0, '2025-07-29'),
(26, 7, 0.00, 0.00, 600.00, 0, 0.00, 334, '2025-07-29'),
(27, 10, 100.00, 100.00, 200.00, 0, 0.00, 335, '2025-07-29'),
(28, 11, 0.00, 0.00, 20000.00, 0, 0.00, 336, '2025-07-29'),
(29, 11, 100.00, 100.00, 0.00, 0, 0.00, 0, '2025-07-29'),
(30, 7, 200.00, 0.00, 0.00, 0, 0.00, 0, '2025-07-30'),
(31, 10, 50000.00, 0.00, 0.00, 0, 0.00, 0, '2025-07-30'),
(32, 11, 30000.00, 0.00, 0.00, 0, 0.00, 0, '2025-07-30'),
(33, 12, 5000.00, 0.00, 0.00, 0, 0.00, 0, '2025-07-30'),
(34, 7, 0.00, 0.00, 200.00, 0, 0.00, 337, '2025-07-30'),
(35, 7, 200.00, 0.00, 0.00, 0, 0.00, 0, '2025-07-31'),
(36, 10, 50000.00, 0.00, 0.00, 0, 0.00, 0, '2025-07-31'),
(37, 11, 30000.00, 0.00, 0.00, 0, 0.00, 0, '2025-07-31'),
(38, 12, 5000.00, 0.00, 0.00, 0, 0.00, 0, '2025-07-31'),
(39, 7, 200.00, 0.00, 0.00, 0, 0.00, 0, '2025-08-11'),
(40, 10, 50000.00, 0.00, 0.00, 0, 0.00, 0, '2025-08-11'),
(41, 11, 30000.00, 0.00, 0.00, 0, 0.00, 0, '2025-08-11'),
(42, 12, 5000.00, 0.00, 0.00, 0, 0.00, 0, '2025-08-11');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_marketing_agent`
--

CREATE TABLE `tbl_marketing_agent` (
  `ag_id` int NOT NULL,
  `ag_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ag_mobile_no` varchar(110) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ag_address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ag_email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `zone` int NOT NULL,
  `contact_date` date NOT NULL,
  `type` varchar(111) COLLATE utf8mb4_unicode_ci NOT NULL,
  `service` varchar(11) COLLATE utf8mb4_unicode_ci NOT NULL,
  `p_isp` varchar(155) COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` varchar(155) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_by` int NOT NULL,
  `entry_by` int NOT NULL,
  `entry_date` date NOT NULL,
  `update_by` int NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ag_status` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_notice`
--

CREATE TABLE `tbl_notice` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int DEFAULT '0',
  `trail_date` date DEFAULT NULL,
  `next_disconnected_date` date DEFAULT NULL,
  `disconnected_message` text COLLATE utf8mb4_unicode_ci,
  `free_customer_limit` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_package`
--

CREATE TABLE `tbl_package` (
  `package_id` int NOT NULL,
  `package_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `net_speed` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bill_amount` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` int NOT NULL DEFAULT '1',
  `mikrotik_id` int NOT NULL DEFAULT '1',
  `created_by` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_package`
--

INSERT INTO `tbl_package` (`package_id`, `package_name`, `net_speed`, `bill_amount`, `type`, `mikrotik_id`, `created_by`) VALUES
(1, 'Standard', '15Mb@1500TK', '525', 1, 21, ''),
(3, 'High', '10MB', '500', 1, 1, ''),
(4, '15MB', '15MB', '600', 1, 1, ''),
(5, '20MB', '20MB', '700', 1, 1, ''),
(6, '30MB', '30MB', '800', 1, 1, ''),
(8, '10Mbps_Package', '10Mbps_Package', '500', 1, 13, ''),
(9, 'Silver Package', 'Silver Package', '10', 1, 9, ''),
(11, 'Rana940', 'Rana', '200', 1, 1, ''),
(12, '10Mb-Pack', '500Tk Package', '500', 1, 10, ''),
(13, '10Mb@1000TK', '10Mb@1000TK', '1000', 1, 1, ''),
(14, 'STARLINK', 'JPN10MB', '608', 1, 1, ''),
(15, 'Tista', '200mbps', '2000', 1, 1, ''),
(16, '5Mb@500TK', '5Mb@500TK', '600', 1, 13, ''),
(17, 'Asd', '5Mb@500TK', '500', 1, 21, ''),
(18, 'Rocky', '5Mb@500TK', '500', 1, 21, ''),
(19, 'Bhairab', '5Mb@500TK', '500', 1, 24, '');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_previous_due`
--

CREATE TABLE `tbl_previous_due` (
  `id` int NOT NULL,
  `tbl_agent_id` int NOT NULL,
  `previous_due_amount` int NOT NULL,
  `update_by` tinyint NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_previous_due`
--

INSERT INTO `tbl_previous_due` (`id`, `tbl_agent_id`, `previous_due_amount`, `update_by`, `updated_at`) VALUES
(208, 934, 0, 0, '2025-05-15 04:21:02'),
(209, 941, 0, 0, '2025-05-15 04:21:02'),
(210, 951, -50, 0, '2025-05-15 04:21:02'),
(211, 952, 0, 0, '2025-05-15 04:21:02'),
(212, 953, -70, 0, '2025-05-15 04:21:02'),
(213, 954, 0, 0, '2025-05-15 04:21:02'),
(214, 955, 500, 0, '2025-05-15 04:21:02'),
(215, 1016, 500, 0, '2025-05-15 04:21:02'),
(216, 1017, 500, 0, '2025-05-15 04:21:02'),
(217, 1018, -25, 0, '2025-05-15 04:21:02'),
(218, 1019, 500, 0, '2025-05-15 04:21:02'),
(219, 1020, 0, 0, '2025-05-15 04:21:02'),
(220, 1021, 500, 0, '2025-05-15 04:21:02'),
(221, 1023, 1700, 0, '2025-05-15 04:21:02'),
(222, 1025, 0, 0, '2025-05-15 04:21:02'),
(223, 1026, 0, 0, '2025-05-15 04:21:02'),
(224, 1027, 0, 0, '2025-05-15 04:21:02'),
(225, 1029, 7500, 0, '2025-05-15 04:21:02'),
(226, 1036, 100, 0, '2025-05-20 08:29:19'),
(227, 1037, 200, 0, '2025-05-20 08:29:19'),
(228, 1, 50, 0, '2025-05-20 10:39:50'),
(229, 1041, 500, 0, '2025-05-25 06:33:25'),
(230, 1043, 1000, 0, '2025-05-25 06:33:25'),
(231, 1035, 500, 0, '2025-06-30 18:28:13'),
(232, 1039, 899, 0, '2025-06-30 18:28:13'),
(233, 1210, 730, 0, '2025-06-30 18:28:13'),
(234, 1221, 100, 0, '2025-06-30 18:28:13'),
(235, 1229, 100, 0, '2025-06-30 18:28:13'),
(236, 1231, 525, 0, '2025-06-30 18:28:13'),
(237, 1232, 675, 0, '2025-06-30 18:28:13'),
(238, 941, 410, 0, '2025-08-02 04:06:49'),
(239, 944, 500, 0, '2025-08-02 04:06:49'),
(240, 952, 500, 0, '2025-08-02 04:06:49'),
(241, 1018, 300, 0, '2025-08-02 04:06:49'),
(242, 1019, 800, 0, '2025-08-02 04:06:49'),
(243, 1021, 500, 0, '2025-08-02 04:06:49'),
(244, 1023, 1700, 0, '2025-08-02 04:06:49'),
(245, 1026, 500, 0, '2025-08-02 04:06:49'),
(246, 1029, 500, 0, '2025-08-02 04:06:49'),
(247, 1031, 500, 0, '2025-08-02 04:06:49'),
(248, 1032, 500, 0, '2025-08-02 04:06:49'),
(249, 1033, 600, 0, '2025-08-02 04:06:49'),
(250, 1034, 500, 0, '2025-08-02 04:06:49'),
(251, 1035, 1000, 0, '2025-08-02 04:06:49'),
(252, 1037, 500, 0, '2025-08-02 04:06:49'),
(253, 1039, 1798, 0, '2025-08-02 04:06:49'),
(254, 1209, 500, 0, '2025-08-02 04:06:49'),
(255, 1210, 1430, 0, '2025-08-02 04:06:49'),
(256, 1211, 500, 0, '2025-08-02 04:06:49'),
(257, 1214, 500, 0, '2025-08-02 04:06:49'),
(258, 1215, 200, 0, '2025-08-02 04:06:49'),
(259, 1216, 700, 0, '2025-08-02 04:06:49'),
(260, 1217, 600, 0, '2025-08-02 04:06:49'),
(261, 1220, 600, 0, '2025-08-02 04:06:49'),
(262, 1221, 700, 0, '2025-08-02 04:06:49'),
(263, 1226, 500, 0, '2025-08-02 04:06:49'),
(264, 1228, 600, 0, '2025-08-02 04:06:49'),
(265, 1229, 700, 0, '2025-08-02 04:06:49'),
(266, 1230, 600, 0, '2025-08-02 04:06:49'),
(267, 1231, 1050, 0, '2025-08-02 04:06:49'),
(268, 1232, 675, 0, '2025-08-02 04:06:49'),
(269, 1233, 525, 0, '2025-08-02 04:06:49'),
(270, 1235, 800, 0, '2025-08-02 04:06:49'),
(271, 1237, 700, 0, '2025-08-02 04:06:49'),
(272, 1244, 500, 0, '2025-08-02 04:06:49');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_remarks`
--

CREATE TABLE `tbl_remarks` (
  `id` int NOT NULL,
  `ag_id` int NOT NULL,
  `remarks` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` date NOT NULL,
  `entry_by` int NOT NULL,
  `updated_by` int NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_service`
--

CREATE TABLE `tbl_service` (
  `s_id` int NOT NULL,
  `s_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `s_desc` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `s_status` int NOT NULL,
  `entry_by` int NOT NULL,
  `entry_date` date NOT NULL,
  `update_by` int NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_setting`
--

CREATE TABLE `tbl_setting` (
  `id` int NOT NULL,
  `field` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `other_parameter` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_setting`
--

INSERT INTO `tbl_setting` (`id`, `field`, `value`, `description`, `other_parameter`, `last_update`) VALUES
(1, 'sms', 'sdfdsafasd', 'SMS panel user name .', 'user', '2025-07-20 09:32:38'),
(2, 'sms', 'asdfsafdsaf', 'SMS Panel apikey', 'pass', '2025-07-20 09:32:38'),
(3, 'invoice', '3', 'PDF of 3 per page or 1 per page as invoice from  Bill Collection page.', '', '2018-03-10 06:18:58'),
(5, 'excel', 'BSD  BD', 'Company Name for Excell sheet', 'name', '2025-06-18 11:35:49'),
(6, 'excel', 'Title', 'Company Title for Excel file', 'title', '2018-02-24 01:21:07'),
(7, 'excel', 'Keyword', 'Company keyword for Excel file', 'keyword', '2018-02-24 01:21:07'),
(8, 'sms', 'asdfsaf', 'SMS Panel Sender', 'sender', '2025-07-20 09:32:38'),
(9, 'sms', '01', 'SMS Support Number', 'support_num', '2023-09-02 10:33:59'),
(10, 'sms', 'The  Pvt.Ltd', 'SMS Panel Company', 'company_name', '2023-09-02 10:48:01'),
(11, 'logo', 'assets/images/bsd/logo.png', 'company logo', 'logo', '2025-05-19 06:38:14'),
(12, 'sms', '', 'SMS Panel password', 'password', '2025-07-20 09:16:33'),
(13, 'billGenerate', 'active', 'Bill Generate Status if Active then Bill Generate will be enable', 'billGenerate', '2025-07-31 05:20:26'),
(14, 'mikrotikDisconCronJob', 'inactive', 'If Active then mikrotik Secret Disconnect will perform by cron job', 'mikrotikDisconCronJob', '2025-08-07 10:59:09'),
(15, 'cronSmsSend', '1', 'The sms will send before selected days', 'cronSmsSend', '2025-08-04 10:54:44'),
(16, 'MikDisconBillStatus', 'inc_par_paid', 'bill_status column of tbl_agent', 'MikDisconBillStatus', '2025-08-02 10:33:33'),
(22, 'address', 'dhaka, bangladesh', 'Company Address', 'address', '2025-08-18 08:55:36'),
(23, 'mobile', '018815456669', 'Mobile Number', 'mobile', '2025-08-18 09:11:45'),
(24, 'email', 'dev@gmail.com', 'Email Address', 'email', '2025-08-18 09:16:33');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_stock`
--

CREATE TABLE `tbl_stock` (
  `id` int NOT NULL,
  `item_id` int NOT NULL,
  `item_qty` int NOT NULL,
  `added_by` int NOT NULL,
  `description` varchar(155) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entry_date` date NOT NULL,
  `status` int NOT NULL,
  `ag_id` int NOT NULL,
  `updated_by` int NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_stock_category`
--

CREATE TABLE `tbl_stock_category` (
  `cat_id` int NOT NULL,
  `cat_name` varchar(211) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(211) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int NOT NULL,
  `updated_by` int DEFAULT NULL,
  `entry_date` datetime NOT NULL,
  `status` int DEFAULT NULL,
  `last_update` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_stock_item`
--

CREATE TABLE `tbl_stock_item` (
  `item_id` int NOT NULL,
  `item_name` varchar(111) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_qty` int NOT NULL,
  `item_price` int NOT NULL,
  `created_by` int NOT NULL,
  `cat_id` int NOT NULL,
  `unit` varchar(155) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_zone`
--

CREATE TABLE `tbl_zone` (
  `zone_id` int NOT NULL,
  `parent_id` int DEFAULT NULL,
  `zone_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `level` int NOT NULL DEFAULT '1' COMMENT '1 zone 2 sub zone 3 destination',
  `created_by` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_zone`
--

INSERT INTO `tbl_zone` (`zone_id`, `parent_id`, `zone_name`, `level`, `created_by`) VALUES
(9, NULL, 'Chandangar', 1, 1),
(10, NULL, 'Shyamal saya', 1, 1),
(11, NULL, 'Chaudhari Nagar', 1, 1),
(12, NULL, 'Muzaffar Nagar', 1, 1),
(13, NULL, 'Buyjeet Nagar', 1, 1),
(14, 21, 'Alahi Mrket', 2, 1),
(15, 14, 'New Alahi Market', 3, 1),
(16, 14, 'Kaca Bazar', 3, 1),
(18, NULL, 'Didar Society', 1, 1),
(19, NULL, 'Kala Bagan', 1, 1),
(20, NULL, 'School Rood', 1, 1),
(21, NULL, 'Kashinathpur Bazar', 1, 1),
(23, NULL, 'Hi School', 1, 1),
(24, NULL, 'Babu Para', 1, 1),
(25, NULL, 'Daibatix', 1, 1),
(26, NULL, 'Vanga Dalan', 1, 1),
(27, NULL, 'Satia Kula', 1, 1),
(28, NULL, 'Borat', 1, 1),
(29, NULL, 'Saya Nirr', 1, 1),
(30, NULL, 'Grlls School Rood', 1, 1),
(31, NULL, 'Tall Potti', 1, 1),
(32, NULL, 'Adrakpur', 1, 1),
(33, NULL, 'Possim Para', 1, 1),
(34, NULL, 'Polli Biddut Font', 1, 1),
(35, NULL, 'Polli Biddut', 1, 1),
(36, NULL, 'Ful Bagan', 1, 1),
(37, NULL, 'Sky Lark Rood', 1, 1),
(38, NULL, 'Singer Market', 1, 1),
(39, NULL, 'Khilkhet', 1, 1),
(40, NULL, 'sdfsdf', 1, 2),
(41, NULL, 'wertertre', 1, 2),
(42, NULL, 'gfhfh', 1, 2),
(43, NULL, 'dfgdg', 1, 2),
(44, NULL, 'sdfsdfsf', 1, 2),
(45, NULL, 'xx', 1, 2),
(46, NULL, 'dfgfdgd', 1, 2),
(47, NULL, 'dfgfdg', 1, 2),
(48, NULL, 'erfer', 1, 2),
(49, 23, '44 test', 2, 2),
(50, 9, 'dftgdfgd', 2, 2),
(51, 19, 'sdfsdf', 2, 2),
(52, 14, '234s', 3, 2),
(53, 50, 'dsfgdfgfdg', 3, 2),
(54, 50, '444', 3, 2),
(55, 50, '123121', 3, 2),
(56, 14, '123121', 3, 2),
(57, NULL, 'gjhjg', 1, 2),
(58, 11, 'hgjghj', 2, 2),
(59, 14, 'sd1', 3, 2),
(60, 51, 'House no - 47,Nikunja-2,Road-17,Dhaka', 3, 1),
(61, NULL, 'Nikunja', 1, 2),
(62, NULL, '456347', 1, 2),
(63, 10, '3134', 2, 2),
(64, 49, '5846786894689869689', 3, 2),
(65, 49, 'Block-c/ House12/B', 3, 2),
(66, 14, 'Block-c/ House12/B', 3, 2),
(67, 14, 'd1', 3, 2),
(68, 14, 'd1', 3, 2),
(69, NULL, 'Mirour', 1, 2),
(70, NULL, 'Mirour', 1, 2),
(73, NULL, 'Nikunja', 1, 2),
(75, NULL, 'Daudpur', 1, 168),
(76, NULL, 'Polashgour ', 1, 2),
(77, NULL, 'ashuganj', 1, 2),
(78, NULL, 'Bhairab', 1, 258),
(79, 78, 'Bhairab', 2, 258),
(80, 79, 'Bhairab', 3, 258);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_account`
-- (See below for the actual view)
--
CREATE TABLE `vw_account` (
`acc_id` int
,`cus_id` varchar(25)
,`agent_id` int
,`acc_head` int
,`acc_amount` int
,`pay_amount` int
,`acc_description` mediumtext
,`acc_type` int
,`entry_by` int
,`entry_date` date
,`update_by` int
,`last_update` timestamp
,`FullName` varchar(100)
,`UserName` varchar(100)
,`acc_name` varchar(100)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_agent`
-- (See below for the actual view)
--
CREATE TABLE `vw_agent` (
`ag_id` int
,`cus_id` varchar(20)
,`ag_name` varchar(100)
,`ip` varchar(36)
,`queue_password` varchar(255)
,`type` int
,`mikrotik_id` int
,`mikrotik_disconnect` int
,`taka` int
,`mb` varchar(50)
,`int_mb` int
,`ag_status` int
,`ag_mobile_no` varchar(20)
,`regular_mobile` varchar(20)
,`ag_office_address` mediumtext
,`zone` int
,`sub_zone` int
,`destination` int
,`pay_status` int
,`ag_email` varchar(100)
,`national_id` varchar(255)
,`nationalidphoto` mediumtext
,`gender` varchar(50)
,`onumac` mediumtext
,`fibercode` varchar(255)
,`connectiontype` varchar(255)
,`agent_type` varchar(255)
,`due_status` int
,`bill_status` int
,`payment_type` varchar(20)
,`bill_date` int
,`remark` mediumtext
,`inactive_date` date
,`billing_person_id` int
,`entry_by` int
,`update_by` int
,`entry_date` date
,`connection_date` date
,`created_at` timestamp
,`last_update` timestamp
,`deleted_at` timestamp
,`diff_month` bigint
,`in_diff_month` bigint
,`connectiondate` varchar(10)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_all_income`
-- (See below for the actual view)
--
CREATE TABLE `vw_all_income` (
`entry_date` date
,`amount` decimal(32,0)
,`flag` varchar(4)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_bill_amount_change`
-- (See below for the actual view)
--
CREATE TABLE `vw_bill_amount_change` (
`bill_amount_id` int
,`agent_id` int
,`bill_amount` int
,`previous_bill_amount` int
,`dueTillEdit` int
,`created_at` date
,`bill_change_diff_month` bigint
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_bill_collection`
-- (See below for the actual view)
--
CREATE TABLE `vw_bill_collection` (
`entry_date` date
,`amount` decimal(32,0)
,`flag` varchar(4)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_comb_income`
-- (See below for the actual view)
--
CREATE TABLE `vw_comb_income` (
`entry_date` date
,`comb_amount` text
,`comb_flag` text
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_marketing_agent`
-- (See below for the actual view)
--
CREATE TABLE `vw_marketing_agent` (
`ag_id` int
,`ag_name` varchar(100)
,`ag_mobile_no` varchar(110)
,`ag_address` varchar(255)
,`ag_email` varchar(100)
,`zone` int
,`contact_date` date
,`type` varchar(111)
,`service` varchar(11)
,`p_isp` varchar(155)
,`details` varchar(155)
,`contact_by` int
,`entry_by` int
,`UserId` int
,`FullName` varchar(100)
,`zone_name` varchar(255)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_others`
-- (See below for the actual view)
--
CREATE TABLE `vw_others` (
`entry_date` date
,`amount` decimal(32,0)
,`flag` varchar(6)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_user_info`
-- (See below for the actual view)
--
CREATE TABLE `vw_user_info` (
`UserId` int
,`FullName` varchar(100)
,`UserName` varchar(100)
,`Password` varchar(32)
,`Email` varchar(100)
,`MobileNo` varchar(20)
,`NationalId` int
,`Address` longtext
,`companyName` varchar(255)
,`PhotoPath` longtext
,`Status` int
,`UserType` varchar(20)
,`UserAccessId` int
,`MenuPermission` varchar(255)
,`WorkPermission` varchar(200)
,`EntryBy` int
,`EntryDate` datetime
,`UpdateBy` int
,`LastUpdate` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `_createuser`
--

CREATE TABLE `_createuser` (
  `UserId` int NOT NULL,
  `FullName` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `UserName` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Password` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `companyName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `MobileNo` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `NationalId` int DEFAULT NULL,
  `Address` longtext COLLATE utf8mb4_unicode_ci,
  `PhotoPath` longtext COLLATE utf8mb4_unicode_ci,
  `Status` int DEFAULT NULL,
  `EntryBy` int DEFAULT NULL,
  `EntryDate` datetime DEFAULT NULL,
  `UpdateBy` int DEFAULT NULL,
  `LastUpdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `_createuser`
--

INSERT INTO `_createuser` (`UserId`, `FullName`, `UserName`, `Password`, `Email`, `companyName`, `MobileNo`, `NationalId`, `Address`, `PhotoPath`, `Status`, `EntryBy`, `EntryDate`, `UpdateBy`, `LastUpdate`) VALUES
(1, 'BSD Admin', 'bsd', 'c4ca4238a0b923820dcc509a6f75849b', 'bsd@gmail.com', '', '01 ', 123456, 'Dhaka', 'assets/images/Cursive Name DP  Mazhar - 2160x1512 - Vendotic.jpg', 1, 1, '2025-02-03 00:00:00', 2, '2025-06-19 04:18:27'),
(2, 'BSD Admin', 'bsdadmin', '8b1ea6db161830e29d6db3e8456d6baa', 'bsd@gmail.com', '', '01716895494', 1, 'Gangail,Mantala,Madhabpur,Habigonj,Sylhet. ', 'assets/images/IMG-20250527-WA0005.jpg', 1, 1, '2025-06-04 00:00:00', 2, '2025-06-28 06:57:50'),
(106, 'sajibkar', 'sajibkar', '87255698c7ad146f5dbf56335a5e278b', 'shimaonlinebd@gmail.com', '', '01618620631', NULL, 'Dhaka', 'asset/img/default.jpg', 1, 1, '2024-08-29 04:36:35', 1, '2024-08-29 04:36:35'),
(111, 'alamin', 'alamin13', '4a7d1ed414474e4033ac29ccb8653d9b', 'admin@admin.com', '', '01884221854', 678877, 'Dhaka', 'assets/images/default.jpg', 1, 2, '2025-04-29 00:00:00', 2, '2025-04-29 02:45:22'),
(121, 'alamin', 'sadi', 'e10adc3949ba59abbe56e057f20f883e', 'admin@admin.com', '', '01884221854', 678877, 'hgfgj', 'assets/images/default.jpg', 1, 2, '2025-02-03 00:00:00', 2, '2025-02-03 03:28:54'),
(122, 'alamin', 'alamin12', '202cb962ac59075b964b07152d234b70', 'admin@admin.com', '', '01884221854', 678877, 'Dhaka', 'assets/images/default.jpg', 1, 2, '2025-02-03 00:00:00', 2, '2025-02-03 03:36:29'),
(124, 'Sohan', 'sohannet', 'c6b4ec62fc78ac15c998ab5332e7272f', 'redwoann@gmail.com', '', '01317299697', 2147483647, 'Tongi Bonmala ', 'assets/images/default.jpg', 1, 2, '2025-03-23 00:00:00', 2, '2025-03-23 03:20:32'),
(126, 'Smart IT Solutions BD', 'SmartIT', '4911d4c3e379c5f99c7ebe8b33e93409', 'info.mahabub201@gmail.com', '', '01916117392', 0, '', 'assets/images/469144389_2606709942867914_8277489640088119557_n.jpg', 1, 2, '2025-04-19 00:00:00', 2, '2025-04-18 23:33:51'),
(127, 'masud', 'masud', '24c7d3d566b5709c67d58df6316e52ea', 'faruque.masud@gmail.com', '', '01724110936', NULL, 'Nikunja-2,Khilkhet-1229', NULL, 1, 1, '2025-04-19 00:00:00', 1, '2025-04-19 05:06:32'),
(129, 'Abir Hasan Piash', 'noob_abir', '9776f6a222a22c30d956bfbc06e45ec7', 'abirhasanpiash2000@gmail.com', '', '01690271650', NULL, 'Gangail', NULL, 1, 1, '2025-04-19 00:00:00', 1, '2025-04-19 05:18:13'),
(131, 'alamin', 'alamin1278', '2c2859c4ba903f5cb6e4053d779f1a41', 'admin@gmail.com', '', '01624171572', NULL, 'Aligonj', NULL, 1, 1, '2025-04-19 00:00:00', 1, '2025-04-19 05:38:17'),
(132, 'Mostakim Ahmad', 'elevation', '12b041011bbc9d72bc65c136ac814515', 'mostakim.parsonal@gmail.com', '', '01959538727', NULL, 'Srirampur Collage Gate, Raipura Sadar, Narsingdi-1630', NULL, 1, 1, '2025-04-19 00:00:00', 1, '2025-04-19 05:41:50'),
(133, 'Md', 'DEMO', 'a27a158098093fab9fbb30bf047db2ef', 'iqbal54dilpur@gmail.com', '', '01831933454', NULL, 'Feni', NULL, 1, 1, '2025-04-20 00:00:00', 1, '2025-04-20 00:02:06'),
(134, 'bayazid', 'bayazid', '729aea651eeeb8a96db0a5578c50e7bf', 'bayazidbsd@gmail.com', '', '01794300623', NULL, 'dfghj', NULL, 1, 1, '2025-04-20 00:00:00', 1, '2025-04-20 02:05:25'),
(135, 'Md faruk khan', '108068milon', '09046219514de5574c3e5f03d45e7f7a', 'nayeemmollah2025@gmail.com', '', '01935248863', NULL, '0.0.0.0', NULL, 1, 1, '2025-04-20 00:00:00', 1, '2025-04-20 04:29:11'),
(136, 'Md faruk khan', 'farukkhan63', '563976f44f365091f4c86d93961c597d', 'nayeemmollah2025@gmail.com', '', '01935248863', NULL, '0.0.0.0', NULL, 1, 1, '2025-04-20 00:00:00', 1, '2025-04-20 04:30:41'),
(137, 'Faruk', 'faruk', '4dd83d9bdec156a2a01da573674760b2', 'faruk@gmail.com', '', '+880 1935-248863', NULL, 'Gazipur Konabari', NULL, 1, 1, '2025-04-20 00:00:00', 1, '2025-04-20 04:42:01'),
(138, 'majharul islam', 'cyberworld', '8f58d1b3f1dc30c9178ed9b9ad011114', 'admin@gmail.com', '', '01618499312', NULL, 'Dhaka, Bangladesh', NULL, 1, 1, '2025-04-20 00:00:00', 1, '2025-04-20 10:00:53'),
(139, 'juthi', 'bsd.juthi', '6e9720a6fd3088d33be6baca1bf31e18', 'ferdoushijuthi.bsd@gmail.com', '', '01764200301', NULL, 'nikunjo 2', NULL, 1, 1, '2025-04-21 00:00:00', 1, '2025-04-20 22:50:46'),
(141, 'Md Nurul Hasan', 'orange', '838075d18e878ed0ce100bdd32b5f425', 'mdhasanctg7533@gmai.com', '', '01889773333', NULL, 'Khatungonj Amir Market Chittagong Bangladesh', NULL, 1, 1, '2025-04-21 00:00:00', 1, '2025-04-21 03:41:37'),
(142, 'Md Rimon Hossan ', 'Rimon', '56c3f66f889e5b90a21e367a090dea1d', 'goldencitynetwork1712@gmail.com', '', '01771722333', NULL, 'Gazipura Mollah bari rod', NULL, 1, 1, '2025-04-21 00:00:00', 1, '2025-04-21 09:03:54'),
(143, 'Md. Shakib Ahmed Sabbir', 'sabbir', 'd15fccf6d06c7c75d49d56758f5e3a42', 'sabbirsarker101@gmail.com', '', '01601580888', NULL, 'Fulbaria, Kaliakoir, Gazipur', NULL, 1, 1, '2025-04-21 00:00:00', 1, '2025-04-21 09:38:02'),
(144, 'Md bablu', 'bablu.net', '8738c285dc757fcafc657a18199df1f6', 'mdbablu2270@gmail.com', '', '01882095843', NULL, 'Mymensingh ', NULL, 1, 1, '2025-04-21 00:00:00', 1, '2025-04-21 10:44:16'),
(145, 'Sujon ali', 'Sujon ali', 'cd84f6872f775bc46b6448ac80378d4f', 'sujonali0131@gmail.com', '', '01316426561', NULL, 'ECB chattar matikata area', NULL, 1, 1, '2025-04-21 00:00:00', 1, '2025-04-21 11:49:03'),
(146, 'MS_Network', 'MS_NETWORK', 'c7e975db3628cecedbef20f7c66bed36', 'sobuzhossain440@gmail.com', '', '01849960440', 0, 'Agrabad ,hazi Yesin ali lane chittagong', NULL, 1, 1, '2025-04-22 00:00:00', 129, '2025-04-22 02:15:47'),
(147, 'SuperWiFi', 'SuperWiFi', '3bd07616b85ecb946b1cf5586cac6390', 'Superwifi880@gmail.com', '', '01810084901', NULL, 'S Hoq Tower/Eklashpur/Begumgonj/Noakhali', NULL, 1, 1, '2025-04-22 00:00:00', 1, '2025-04-22 02:56:38'),
(148, 'MOHAMMAD AYUB ALI', 'ali2008ctg', 'e2626013eb3cfc61c166fd10298c9d26', 'ali2008ctg@yahoo.com', '', '01817245751', NULL, 'South Patenga, Duria Para', NULL, 1, 1, '2025-04-22 00:00:00', 1, '2025-04-22 03:09:06'),
(149, 'Emon', 'Check', '726e61653f4a52e26031ab9dab443178', 'checking@gmail.com', '', '01606092217', NULL, 'Mirpur-10', NULL, 1, 1, '2025-04-22 00:00:00', 1, '2025-04-22 03:38:38'),
(150, 'Dr. Robi', 'tfispd', '65e876092244009dadd2706f4d2c2d80', 'robiulkuet@gmail.com', '', '01712964500', NULL, 'Dhaka', NULL, 1, 1, '2025-04-22 00:00:00', 1, '2025-04-22 04:13:28'),
(151, 'Alamin Hridoy', 'fwbd', 'c2184aa75c6c08df78c7574a4b109eb3', 'friendswifi01@gmail.com', '', '01770659777', NULL, 'Eliotgonj-3519, Daudkandi, Cumilla', NULL, 1, 1, '2025-04-22 00:00:00', 1, '2025-04-22 06:05:02'),
(152, 'Rakib', 'Pathargata', '383dbe9d95e5447a706ff3193c422ce3', 'mrakibhossen834@gmail.com', '', '01303336209', NULL, 'Pathargata', NULL, 1, 1, '2025-04-22 00:00:00', 1, '2025-04-22 06:30:51'),
(153, 'Rakib', 'rakib1234', '24a3ba108ebb1007fba2a253e9c00a53', 'mrakibhossen834@gmail.com', '', '01303336209', NULL, 'Pathargata', NULL, 1, 1, '2025-04-22 00:00:00', 1, '2025-04-22 06:33:30'),
(154, 'Rakib', 'Rakib', 'ea025b921643f8113d7edb5916680a5c', 'mrakibhossen834@gmail.com', '', '01303336209', NULL, 'Rakib', NULL, 1, 1, '2025-04-22 00:00:00', 1, '2025-04-22 07:05:44'),
(155, 'Alamin Hridoy', 'alamin794', '33ce37b363ca6a96db813243182d92d9', 'friendswifi01@gmail.com', '', '01920824542', NULL, 'Eliotgonj-3519, Daudkandi, Cumilla', NULL, 1, 1, '2025-04-22 00:00:00', 1, '2025-04-22 11:13:59'),
(156, 'Mohammed', 'Khan', '9ff9db911e1dcb263c4a87b6856a8bf1', 'mohammedkhan2017@gmail.com', '', '01788316902', NULL, 'N.ganj', NULL, 1, 1, '2025-04-22 00:00:00', 1, '2025-04-22 11:19:39'),
(157, 'Md Abir Hossain', 'abir2024', 'aab8f65f2161fed0224ea91346d87cdd', 'julhasabir0000@gmail.com', '', '01777376594', NULL, 'Narayanganj 1400', NULL, 1, 1, '2025-04-23 00:00:00', 1, '2025-04-23 00:20:58'),
(158, 'Md Ajijul Islam akash', 'Ajijul', '6fa130ac5d7377cda1bc0d39b789892a', 'mdajijulislam7020@gmail.com', '', '01872831223', NULL, 'Kuril bisworoad, vatara, sawra Sahara', NULL, 1, 1, '2025-04-23 00:00:00', 1, '2025-04-23 02:37:09'),
(159, 'Forhad Hossain', 'F@orhaD25', '28ad9dba0a121c8295679bb90214e7bc', 'forhademu@gmail.com', '', '01674146166', NULL, 'House-6,Road-2,Block-Bsection 14, Tinshed colony Dhaka cant.', NULL, 1, 1, '2025-04-23 00:00:00', 1, '2025-04-23 07:40:49'),
(160, 'Abdullah Al Jobair', 'jubair', '18f8b05f2e971eb4c846819b0e01b4b3', 'jubairk52@gmail.com', '', '01686663396', NULL, 'Mirpur 14 B, Tinshed Colony, Dhaka-1206', NULL, 1, 1, '2025-04-23 00:00:00', 1, '2025-04-23 07:49:56'),
(161, 'Abdullah Al Jobair', 'jubairkhan', '88fb42d4a65bf1f471142a5b99d10b54', 'jubairk52@gmail.com', '', '01686663396', NULL, 'Mirpur 14 B, Tinshed Colony, Dhaka-1206', NULL, 1, 1, '2025-04-23 00:00:00', 1, '2025-04-23 07:53:49'),
(162, 'Rasel', 'rasel', '2dca61a0afce522596b60b85908d9904', 'raselkhanraselkhan5833@gmail.com', '', '01914005833', NULL, 'auch para', NULL, 1, 1, '2025-04-24 00:00:00', 1, '2025-04-23 22:34:30'),
(163, 'Delwar Hossain ', 'Mashumchy', '27a65d26599315a47d6ac17bcece5ddd', 'mashumchy@gmail.com', '', '01726839153', NULL, 'Gasbari, Kanaighat, Sylhet ', NULL, 1, 1, '2025-04-24 00:00:00', 1, '2025-04-23 23:07:29'),
(164, 'Md Morsed Alom', 'Morsed', '8acea949bc52b1481046357cb40e0a48', 'bdtipsboss@gmail.com', '', '01768168777', NULL, 'Bhola,barisal', NULL, 1, 1, '2025-04-24 00:00:00', 1, '2025-04-24 00:33:30'),
(165, 'kayesh rony', 'rony', '659a1cee3ed4ed21759803c8173910d2', 'peoplesbd@gmail.com', '', '01811523357', 0, '', 'assets/images/default.jpg', 1, 2, '2025-04-24 00:00:00', 165, '2025-04-24 08:08:53'),
(166, 'R', 'Robiul97', '27ab2ab018340e5fbaccba57b43ccf12', 'robirobiul430@gmail.com', '', '01792927197', NULL, 'N/A', NULL, 1, 1, '2025-04-26 00:00:00', 1, '2025-04-25 22:35:48'),
(167, 'Tanvir Ahmed', 'fibercloud', '589dc886523346646b221ae1b9c261df', 'tanvir.bit07@gmail.com', '', '01911421657', NULL, '34,Maniknager, Dhaka-1203', NULL, 1, 1, '2025-04-26 00:00:00', 1, '2025-04-26 00:35:11'),
(168, 'Abdul Qader', 'Biddut', 'c5b54c604d2cc4d14ef87a7a648251a3', 'ba.biddut2014@gmail.com', '', '01633365456', 0, 'Khilgong, Dhaka', NULL, 1, 1, '2025-04-26 00:00:00', 168, '2025-04-26 02:48:39'),
(169, 'MD AKBOR HOSSAIN', 'akborhossain', 'fe79152f40d4f80b8fc8314cce6dd29b', 'akborpowernet@gmail.com', '', '01670171817', NULL, 'mirpur', NULL, 1, 1, '2025-04-26 00:00:00', 1, '2025-04-26 04:04:55'),
(170, 'kazi shamim', 'kazishamim008', '9ae2175c1494f9e4a91ade2ed96ece09', 'aponislam008@gmail.com', '', '01511208290', NULL, '239 , chadmari road ,Gopalganj', NULL, 1, 1, '2025-04-26 00:00:00', 1, '2025-04-26 07:31:10'),
(171, 'kazi shamim', 'kazishamim', '207aed35e65ecec9a57384512d55617e', 'aponislam008@gmail.com', '', '01511208290', NULL, '239 , chadmari road ,Gopalganj', NULL, 1, 1, '2025-04-26 00:00:00', 1, '2025-04-26 07:38:02'),
(172, 'zsnet', 'zsnet', '8bfc6af2081eb8b8430da5e43c383990', 'zsnet2012@gmail.com', '', '01842220903', NULL, '60/E/1,Purana Paltan', NULL, 1, 1, '2025-04-26 00:00:00', 1, '2025-04-26 08:38:24'),
(173, 'Rakib islam', 'rakib020825', '7239d957521abf3672026355adfc1d02', 'rakib020825@gmail.com', '', '01767020825', 0, 'Foridpur,dhaka', NULL, 1, 1, '2025-04-27 00:00:00', 173, '2025-04-27 00:14:25'),
(174, 'Rayhan ', 'rayhan', '28bd0391bd3decd6637a456c5db77580', 'touhedrayhan16@gmail.com', '', '01742240235', NULL, '210.GARDEN TOWER. UPOSHOR.SYLHET ', NULL, 1, 1, '2025-04-27 00:00:00', 1, '2025-04-27 01:02:25'),
(175, 'Tonmoy ahmed ', 'Tas0071', 'a1830ea27d69160cd0213c7a6a11d074', 'tonmoyaco@gmail.com', '', '01635774356', NULL, 'Nandipara 6 no road ', NULL, 1, 1, '2025-04-27 00:00:00', 1, '2025-04-27 04:36:37'),
(176, 'Rasel Hossain ', 'Rasel09', '048e554cee6346b76ffd55ee8156d9fd', 'raselhossain5010@gmail.com', '', '01773720726', NULL, 'Raikali Teen Matha Bazar', NULL, 1, 1, '2025-04-27 00:00:00', 1, '2025-04-27 04:52:03'),
(177, 'KM', 'kmsaddam', 'd07687775e1de7e5e60b1ade9904d36f', 'kmsaddam2@gmail.com', '', '01830794873', NULL, 'Boyra Khulna', NULL, 1, 1, '2025-04-28 00:00:00', 1, '2025-04-27 23:04:21'),
(178, 'Sumon Howlader', 'isadahmed123', '42d3dd149d15cc12b4eed05f15acc9fe', 'isadahmed123@gmail.com', '', '01870060112', NULL, 'Delpara', NULL, 1, 1, '2025-04-28 00:00:00', 1, '2025-04-28 00:37:45'),
(179, 'Md Mahedi Hasan', 'mahedi', 'd6b5e043b559b73707637314ae1b7a33', 'mh44571890@gmail.com', '', '01787874470', NULL, 'Rajsahi', NULL, 1, 1, '2025-04-28 00:00:00', 1, '2025-04-28 00:59:58'),
(180, 'Md Shahidullah', 'SMN Bogabari', '0d024fd4cc1fa105e70c5d1cda983e67', 'shahidpan4@gmail.com', '', '01720181037', NULL, 'Pandhua, Senwalia, Ashulia, Savar, Dhaka.', NULL, 1, 1, '2025-04-28 00:00:00', 1, '2025-04-28 04:01:15'),
(181, 'masud', 'masudbsd', 'ee587949415d05deb2a5e878894d2896', 'faruque.masud@gmail.com', '', '01959919802', NULL, 'Nikunja-2,Khilkhet-1229', NULL, 1, 1, '2025-04-29 00:00:00', 1, '2025-04-28 22:47:17'),
(182, 'Nh Tuhin', 'fcnbd@lxp', 'ddf9922a40d5c8867a7ce7b99da53e5b', 'fcnbhawanigonj@gmail.com', '', '01927292791', NULL, 'Bhwaniganj  Chawrasta, Lakshmipur ', NULL, 1, 1, '2025-04-29 00:00:00', 1, '2025-04-29 03:00:14'),
(183, 'MD Shamiul Hasan', 'Shamiul@360', '8094fea2dd555f5c157a626e848bd918', 'pirgachhaonlin@gmail.com', '', '01890011622', NULL, 'Pirgachha, Rangpur,', NULL, 1, 1, '2025-04-30 00:00:00', 1, '2025-04-29 13:06:49'),
(184, 'sojal mia', 'sojal90', 'e818ba1fe598b6a553e8364701496bc3', 'hossianm644@gmail.com', '', '01602551209', NULL, 'shagardi bazar monohardi narsingdi bangladesh', NULL, 1, 1, '2025-04-30 00:00:00', 1, '2025-04-30 03:23:37'),
(185, 'SHAIKH ARIFUL ISLAM', ' bsd ', '179968a43c70ffe7e812071b0b3a1c77', 'arifislam6808@gmail.com', '', '01786668503', NULL, 'Baruipara,Chaksree Bazar,Rampal,Bagerhat.', NULL, 1, 1, '2025-04-30 00:00:00', 1, '2025-04-30 04:00:04'),
(186, 'Masud Rana', 'masudrana ', '7416c76e5d7ec97c7f648ba4c3a315da', 'mrmdmasudrana1@gmail.com', '', '01684700087', NULL, 'kunia targash, National Unaversity, Gazipur susur, gazipur.', NULL, 1, 1, '2025-05-01 00:00:00', 1, '2025-05-01 05:44:47'),
(187, 'Md Nadim Biswas', 'Kingdom', '42c3733d2215e8e4dd6a87797defd13f', 'networkkingdom9@gmail.com', '', '01625886139', NULL, '143/B J, N Shaha Road Lalbagh Dhaka 1211', NULL, 1, 1, '2025-05-03 00:00:00', 1, '2025-05-03 04:26:49'),
(188, 'Md. Imtiaz Rakib', 'Imtiaz', '70c68359f8894a4845ea921d0d8dab5b', 'rakibbsd55@gmail.com', '', '01648912624', NULL, 'Nikonjo-2', NULL, 1, 1, '2025-05-03 00:00:00', 1, '2025-05-03 04:44:28'),
(189, 'Md Helaluzzama ', 'netin56', '5ac8fd534613f9e708f82f7c9adf09fc', 'netinonlinebd@gmail.com', '', '01712508056', NULL, 'Barishal sador ', NULL, 1, 1, '2025-05-04 00:00:00', 1, '2025-05-04 05:20:45'),
(190, 'ashik mollk', 'ashik', '8b1ea6db161830e29d6db3e8456d6baa', 'ashik@gmail.com', 'BSD', '01765843743', NULL, 'Sit enim odit facere', NULL, 1, 1, '2025-05-04 00:00:00', 1, '2025-06-25 11:17:48'),
(191, 'Tanvir Ahmed', 'tanvir', '7eb4013cf33e606f8c2d69dcc3092f11', 'tanvir.bit07@gmail.com', 'Agni System ltd', '01911421657', NULL, '34,Maniknager, Dhaka-1203', NULL, 1, 1, '2025-05-05 00:00:00', 1, '2025-05-04 23:49:32'),
(192, 'mahmudhasan', 'hasan', 'dbf9e7aa1f9bd5dab2f129840189c595', 'mahmudhasan5868@gmail.com', 'netpai cabol networe', '+88 01828383916', NULL, 'comiila/monohorgong', NULL, 1, 1, '2025-05-05 00:00:00', 1, '2025-05-05 00:39:53'),
(193, 'Sunit Das', 'vertexit', 'f6a5c2a147c489f637a46b4a7b5f0508', 'vertexit@hotmail.com', 'Vertex IT', '01711255124', NULL, 'Munshiganj ', NULL, 1, 1, '2025-05-05 00:00:00', 1, '2025-05-05 01:13:29'),
(194, 'MD NUR NOBI', 'flash', '947233bdf78628c315e2290de1129e01', 'nurnobebd@gmail.com', 'Flash Technology', '01612821212', NULL, 'East BoxNagar, Sarulia, Demra', NULL, 1, 1, '2025-05-06 00:00:00', 1, '2025-05-05 12:05:12'),
(195, 'MD ROMAN KHAN', 'Moremedia ', '8e254eddcb137634df2573304cfc8514', 'moremedia0070@gmail.com', 'Moremedia isp', '01917777870', NULL, '1817', NULL, 1, 1, '2025-05-06 00:00:00', 1, '2025-05-06 05:03:15'),
(196, 'parvez', 'parvez', 'd2af349d482e41412bd1e8305213bef0', 'parvezrahman9696@gmail.com', 'badda zone', '01628856735', NULL, 'badda', NULL, 1, 1, '2025-05-07 00:00:00', 1, '2025-05-07 03:11:20'),
(197, 'Mohammad Imran ', 'Imran@7676', '0aefe18bfc6947385a7e24a39ed72c64', 'abid98094@gmail.com', 'Icc cummunation ltd bhujpur ', '01864944725', NULL, 'Chittagong fatikchhari Bhujpur ', NULL, 1, 1, '2025-05-07 00:00:00', 1, '2025-05-07 04:09:12'),
(198, 'Ryan Ahmed', 'ryan', 'ea547db26457861aada3f71a31d03460', 'engrryanahmed@gmail.com', 'FASTNET BD', '01799515417', NULL, 'House:03 (1st floor), Road: 16, Sector: 11, Dhaka 1230', NULL, 1, 1, '2025-05-07 00:00:00', 1, '2025-05-07 05:38:11'),
(199, 'Babu', 'Babu', '5ca39263df1e754f6588ff60cf7effad', 'babuamjadhossain@gmail.com', 'InCity Internet ', '01814461088', NULL, 'West Nasirabad Pahartali ', NULL, 1, 1, '2025-05-07 00:00:00', 1, '2025-05-07 05:45:04'),
(200, 'Bb', 'zxc', 'acfa198eba836c74765e1b78a6abda99', 'b@gmail.com', 'Bh', '01794300623', NULL, 'Pabna', NULL, 1, 1, '2025-05-08 00:00:00', 1, '2025-05-07 21:41:43'),
(201, 'MD IMRAN HOSSAIN', 'imran554', '8cb7e69143ae3df42d6bae22d5592144', 'sanvirohoman@gmail.com', 'Digital ISP', '01646926554', NULL, 'CHITTAGONG ', NULL, 1, 1, '2025-05-08 00:00:00', 1, '2025-05-07 23:27:56'),
(202, 'Siyam', 'Siyam321', '1d0f7e017c6e9952dbec37e7700034c9', 'siyamboss131@gmail.com', 'Vaivai', '01954414446', NULL, 'SIYAM', NULL, 1, 1, '2025-05-08 00:00:00', 1, '2025-05-08 03:13:54'),
(203, 'Imran Khan', 'grand.internet', '656785ebebeb63cbee5b1b8fa035ffe3', 'git.internet.tangail@gmail.com', 'Grand Internet Technology', '01716835259', NULL, 'Tangail', NULL, 1, 1, '2025-05-08 00:00:00', 1, '2025-05-08 03:48:37'),
(204, 'Md rubel ahmed', 'Rubel1', 'ac778b87f2b19bf744bfdd1bbaa87a7f', 'ra13099@gmail.com', 'Triangle service ', '01763177504', NULL, 'Moulobubazar', NULL, 1, 1, '2025-05-08 00:00:00', 1, '2025-05-08 04:11:43'),
(205, 'Bappy Hossein', 'bappy@321', '7efa67f288ccccf16ede7a0df1c5266e', 'bappyhossein6@gmail.com', 'Net Service', '01735337307', NULL, 'House-4, Lane-2, Block-J', NULL, 1, 1, '2025-05-10 00:00:00', 1, '2025-05-09 21:48:15'),
(206, 'Bappy Hossein', 'bappy@3210', '27b86c51a752fcbbdbdcfceb8111e755', 'bappyhossein6@gmail.com', 'ShuddhoJibon', '01880182900', NULL, 'House-4, Lane-2, Block-J', NULL, 1, 1, '2025-05-10 00:00:00', 1, '2025-05-09 21:52:00'),
(207, 'Md.Raju Mia', 'Ap001@jhonline', '4d48d66dbc5ec86e6bd82f8995a3897a', 'rr6648563@gmail.com', 'Bondhuonline', '01826496842', NULL, 'Amishaapra sonaimuri noakhali', NULL, 1, 1, '2025-05-10 00:00:00', 1, '2025-05-10 04:47:29'),
(208, 'Mehadi hasan', 'Ap002@jhonline', 'fa4d27170367c0f6f167167a4e79cd50', 'rr6648563@gmail.com', 'Bondhuonline ', '01855326643', NULL, 'Amishaapra sonaimuri noakhali', NULL, 1, 1, '2025-05-10 00:00:00', 1, '2025-05-10 04:51:03'),
(209, 'Dokhin Tripura', 'dokhin', '86c87bee4047f74f4d8a36c3b3acbd5f', 'dokhintripura@gmail.com', 'YES NET', '01876645612', NULL, 'BONORUPA,RANGAMATI', NULL, 1, 1, '2025-05-10 00:00:00', 1, '2025-05-10 05:50:38'),
(210, 'MD SAKIB', 'sksoftdev', 'b5a7a2557fb26351e1da41d5ec877889', 'sksoftdev1@gmail.com', 'SKSOFTDEV', '01704451085', NULL, 'BANGLADESH', NULL, 1, 1, '2025-05-10 00:00:00', 1, '2025-05-10 11:20:25'),
(211, 'MD GOLAP UDDIN ', 'Golap ', '4008cb028d9c556f56592a8b43ef93b3', 'mmdgolap602@gmail.com', 'Polli Gram Wi-Fi Zone ', '01975687097', NULL, 'Momingonj Bazar Gabtala, Kochakata, Kurigram ', NULL, 1, 1, '2025-05-11 00:00:00', 1, '2025-05-11 00:09:19'),
(212, 'RJib', 'rajib', '827ccb0eea8a706c4c34a16891f84e7b', 'shofek6050@gmail.com', '', '01909376050', 0, 'Bazzar', 'assets/images/default.jpg', 1, 2, '2025-05-11 00:00:00', 2, '2025-05-11 00:10:08'),
(213, 'Monir Talukder', 'saba@1199', '871bf4bf73651becc5399aec4d72b88e', 'mt349015@gmail.com', 'Saba Communication', '01814407151', NULL, 'agrabad', NULL, 1, 1, '2025-05-11 00:00:00', 1, '2025-05-11 00:10:47'),
(214, 'sumon', 'sumon', '11bd456dc22242779324d0dfdec3c9f3', 'rafsunsumon@gmail.com', 'infinity online', '01673620764', NULL, 'turag', NULL, 1, 1, '2025-05-11 00:00:00', 1, '2025-05-11 00:23:17'),
(215, 'Md shimul', 'Ap1671@Bonline', 'c7b29c6d889d88c44f8f508c696930b8', 'rr6648563@gmail.com', 'Bondhuonline', '01813174172', NULL, 'Amishaapra sonaimuri noakhali', NULL, 1, 1, '2025-05-11 00:00:00', 1, '2025-05-11 04:14:57'),
(216, 'Shahin Ahmed', 'shahin321', 'fd4fc8ebb40153c47c3ed9133fb89191', 'tonmoya4@gmail.com', 'Roz Net', '01857703111', NULL, 'Dhaka ', NULL, 1, 1, '2025-05-11 00:00:00', 1, '2025-05-11 04:42:50'),
(217, 'Md Mijanur Rahman', 'Md mijan', '094ca1d8a4f9741cf51c654d4d9a1154', 'sanyelectroproduct@gmail.com', 'MO cable tv Network ', '01933 307404', NULL, 'Khulna Bangladesh ', NULL, 1, 1, '2025-05-11 00:00:00', 1, '2025-05-11 05:29:29'),
(218, 'Md Helaluzzama ', 'Netin', '7b63a2b9bee4e9769305706b451539b0', 'netinonlinebd@gmail.com', 'Netin Online ', '01712508056', NULL, 'Kashippur Bazar, Barishal Sador Barishal', NULL, 1, 1, '2025-05-11 00:00:00', 1, '2025-05-11 05:51:54'),
(219, 'Suvo Saha', 'Suvo', '7dc0f1163b992d2963ecdc69d6adedf7', 'shuvosaha548@gmail.com', 'LinkTech Internet', '01686054660', NULL, 'Mirpur06, Dhaka.', NULL, 1, 1, '2025-05-12 00:00:00', 1, '2025-05-12 05:21:35'),
(220, 'Md Abdullah ', 'Abdullah', '4f83a327843c338162c36fa69b5ec64d', 'Mdhridoyabdulla@gmail.com', 'Spark online ', '01984347574', NULL, 'mdhridoyabdulla@gmail.com', NULL, 1, 1, '2025-05-12 00:00:00', 1, '2025-05-12 07:12:49'),
(221, 'Shahjahan Kabir ', 'kabir', '769a41e53d796cadff47f743f7eb9661', 'sh.kabir43@gmail.com', 'Breeze Online ', '01712681036', NULL, '222, East Kafrul ', NULL, 1, 1, '2025-05-12 00:00:00', 1, '2025-05-12 07:44:56'),
(222, 'sami', 'bsd1', '81dc9bdb52d04dc20036dbd8313ed055', 'hello@gmail.com', '', '01845741513', 0, '', 'assets/images/default.jpg', 1, 2, '2025-05-13 00:00:00', 2, '2025-05-13 00:07:39'),
(223, 'MD shofiqul Islam  Sumon', 'Shafinshan', '492bb45e42074f15c09ce2867b216405', 'allamainternetservice767@gmail.com', 'Allahma Network service', '01615537774', NULL, 'House 20', NULL, 1, 1, '2025-05-13 00:00:00', 1, '2025-05-13 00:37:20'),
(224, 'Bijoy', 'Bijoy', '26d2e5af544fab3ec3f2631ae272149f', 'mdbijoysarker478@gmail.com', 'KSB NETWORK', '01904489106', NULL, 'NOWJOR', NULL, 1, 1, '2025-05-13 00:00:00', 1, '2025-05-13 11:03:46'),
(225, 'md sobuj sheikh sheikh', 'sobuj', 'b3f6016f9876f6a500b85076b4e820a5', 'sovorkhan.sk@gmail.com', 'Sabur Traders', '01971955028', NULL, '251, Sarkar Bari Road, Arichpur, Tongi, Gazipur', NULL, 1, 1, '2025-05-14 00:00:00', 1, '2025-05-14 11:31:04'),
(226, 'Sany Shikder', 'Sany', 'dda4624611c34a973365360b9dc18257', 'sanyshikder23@gmail.com', 'Bluebird broadband', '01881901965', NULL, 'Sany', NULL, 1, 1, '2025-05-16 00:00:00', 1, '2025-05-16 12:32:11'),
(227, 'Konok', 'Konok', 'b49b186a13ad10bd9c4fbb1911308973', 'kabirhossainbd35@gmail.com', 'Dhalapara online', '01712091109', NULL, 'Ghatail', NULL, 1, 1, '2025-05-17 00:00:00', 1, '2025-05-17 06:41:10'),
(228, 'Md Abul hossain Rabbi', 'Robin', '06185ff50fc0c190726dc38cb48d8c8e', 'bondhocablenetwork@gmail.com', 'Bondhu Cable Network ', '01705515549', NULL, 'Villege: Hotapara,  district : Gazipur', NULL, 1, 1, '2025-05-17 00:00:00', 1, '2025-05-17 09:20:35'),
(229, 'Hafiz ', 'Nivan', '9256657c3479affe5f8dd4739d23b657', 'sanvi2001@gmail.com', 'Rainbow enterprise ', '01961926943', NULL, 'Munjitpur satkhira ', NULL, 1, 1, '2025-05-18 00:00:00', 1, '2025-05-18 05:09:43'),
(230, 'josim uddin', 'josbd', '96009b520b5815a229f7b3c4b3ec088b', 'josimbd@gmail.com', 'jupiter online', '01782776655', NULL, '240 mirhajirbag jatrabari dhaka', NULL, 1, 1, '2025-05-18 00:00:00', 1, '2025-05-18 05:10:21'),
(231, 'Sahriro suman', 'sahrior', 'ea2199ad4bc25c0b38c5242bd38f473b', 'sahrior@gmail.com', 'Speed Online', '01712739621', NULL, '29/B, South kazla, Nayanagor,Jatrabari, Dhaka', NULL, 1, 1, '2025-05-18 00:00:00', 1, '2025-05-18 05:10:23'),
(232, 'Thauhidul Islam', 'Samin2016', '560fab99365ccf77b4e1bf1b645ce875', 'iimshobuj@gmail.com', 'Samins Network', '01847034655', NULL, 'Samin\'s Network, Chapra Mosque Samin\'s Network, Dhaka 1230, Bangladesh', NULL, 1, 1, '2025-05-18 00:00:00', 1, '2025-05-18 05:10:29'),
(233, 'Md.Roish molla', 'adminroish', '312a9ea43101bd0ab20f015d903360cd', 'roisuctn@gmail.com', 'RR Communication ', '01737786739', NULL, 'Darogervira Batiaghata khulna', NULL, 1, 1, '2025-05-18 00:00:00', 1, '2025-05-18 05:15:40'),
(234, 'Rab newaz', 'rnewaz', '06ccdc3d64887da1ad46b7c45f3ed9a0', 'genfox007@gmail.com', 'Mutuni communication ', '01670992322', NULL, '1086/1, ibrahimpur, dhaka cannt,  dhaka 1206', NULL, 1, 1, '2025-05-18 00:00:00', 1, '2025-05-18 05:23:34'),
(235, 'Dipongkor Roy', 'plexuscloud', 'ca6eb38d4323a566c5f6b0217ee89b19', 'dipongkor@plexuscloud.com.bd', 'PLEXUS CLOUD', '01958615678', NULL, 'Bonosree ', NULL, 1, 1, '2025-05-18 00:00:00', 1, '2025-05-18 05:56:10'),
(236, 'Adib Raiyan Priyo', 'TufanOnline', '1515a21e76f9dcc07019f65a49c39aac', 'adib@tufanonline.com.bd', 'Tufan Online', '01920123112', NULL, 'Pallabi, Mirpur', NULL, 1, 1, '2025-05-18 00:00:00', 1, '2025-05-18 06:36:20'),
(237, 'Rajowan Ahmed', 'Sara Associate', 'cea313f57016aac12e7763ee7ef46b75', 'rajowan97@gmail.com', 'Sara Associate', '01906989396', NULL, '13/7, Nadda, Gulshan, Dhaka-1212', NULL, 1, 1, '2025-05-18 00:00:00', 1, '2025-05-18 06:43:33'),
(238, 'sharifuzzaman', 'Sharif', '39ffabf815f147dc5bafd0315b69efdc', 'sharifuzzaman067@gmail.com', 'Ashkona online ', '01992609500', NULL, '57 ashkona dokhinkhan dhaka1230', NULL, 1, 1, '2025-05-18 00:00:00', 1, '2025-05-18 07:10:16'),
(239, 'Rahim Bapary arif', 'Icc', '6d2a02ca661043f218e5d0f3755c8b46', 'rahimbaparyarif@gmail.com', 'FASTLINK ', '01843893970', NULL, 'Mirpur 10', NULL, 1, 1, '2025-05-18 00:00:00', 1, '2025-05-18 10:05:48'),
(240, 'Md Zahirul Islam', 'zahirul4434', '0d5c7e36a619ba03d2dfc3dd3dd36209', 'zahirul4434@gmail.com', 'City Net', '01767755569', NULL, 'Bangshal, Dhaka', NULL, 1, 1, '2025-05-18 00:00:00', 1, '2025-05-18 12:52:46'),
(241, 'mr khan', 'khan987654321', '574da0c73e3bb7f632278d08e90b30f6', 'coongoalty1@gmail.com', 'nishan', '01712848665', NULL, 'Lalbag', NULL, 1, 1, '2025-05-18 00:00:00', 1, '2025-05-18 15:56:05'),
(242, 'MUSHFIQUL HOQUE CHIWDHURY ', 'Shuvo_', 'ac65f4371f8c088d2fc50d90a8fb5efe', 'itzshuv93@gmail.com', 'SVC INTERNET', '01841260896', NULL, 'Chandgaon, bohoddarhut, chattogram ', NULL, 1, 1, '2025-05-19 00:00:00', 1, '2025-05-19 05:49:35'),
(243, 'MUSHFIQUL HOQUE CHIWDHURY ', 'Itzshuv93', '8500b4f988e382ac4956ae2566c42b7c', 'itzshuv93@gmail.com', 'SVC INTERNET ', '01841260896', NULL, 'Bohoddarhut farider para, Chandgaon ', NULL, 1, 1, '2025-05-19 00:00:00', 1, '2025-05-19 05:50:58'),
(244, 'Md. Babul Miah', 'babul', '9a56fd4d8a93433cbfe9181d368f16da', 'babulahmedsagar@gmail.com', 'Nayapara Fiber Online ', '01714238910', NULL, 'Nayapara Bazar Sreepur Gazipur ', NULL, 1, 1, '2025-05-19 00:00:00', 1, '2025-05-19 06:29:45'),
(245, 'Anwar', 'Anwar', '912792fc49e1d78eec79ac1daeac9c7c', 'anwarifrah@yahoo.com', 'Rangunia Online', '01815483942', NULL, 'Kodala, Rangunia, Chittagong', NULL, 1, 1, '2025-05-19 00:00:00', 1, '2025-05-19 07:42:23'),
(246, 'Ripon Ahmed', 'mbn@smcnet', '1778558272c35c8de92f5a5ee56d89a7', 'ra105482@gmail.com', 'S M C networks', '01932588420', NULL, 'Mukkhopur teishal mymensingh', NULL, 1, 1, '2025-05-19 00:00:00', 1, '2025-05-19 10:21:11'),
(247, 'Mehedi hasan rabby', 'Mehedi hasan', 'f331ec92a9c08efd37b1376a2be64972', 'rabbybhuya65@gmail.com', 'Brother Internet Network ', '01730903940', NULL, 'North merundi, Harirampur,  Manikganj ', NULL, 1, 1, '2025-05-20 00:00:00', 1, '2025-05-20 06:38:01'),
(248, 'Subrato', 'Msubrato', 'd59e24775916bd5f5f4ebbce46742d6c', 'subrato.realnetsylhet@gmail.com', 'RealNet', 'Mukherjee', NULL, 'Sylhet', NULL, 1, 1, '2025-05-20 00:00:00', 1, '2025-05-20 07:04:35'),
(249, 'Md Liton Islam', 'liton', 'b10c1448f06900921686af3077555378', 'liton.niter.du@gmail.com', 'FibreNet Technologies ', '01521744214', NULL, 'Ranirbandar, Chirirbandar, Dinajpur ', NULL, 1, 1, '2025-05-20 00:00:00', 1, '2025-05-20 09:48:53'),
(250, 'Shahed Aziz ', 'shahed89', '72d73353a85fababa8ca2ad90cc160ce', 'shahedaziz89@gmail.com', 'HR Networks ', '01677947589', NULL, 'Feni', NULL, 1, 1, '2025-05-21 00:00:00', 1, '2025-05-20 20:20:36'),
(251, 'Mohammad Ali', 'Ali', '3a80fdcb1ef2c77d1894e766b89a04b6', 'aliduranta01@gmail.com', 'alisha network', '01676724747', NULL, 'Dattapara house billding tongi cheragali.', NULL, 1, 1, '2025-05-21 00:00:00', 1, '2025-05-21 05:43:17'),
(252, 'Khan', 'khan@123', '09fb3b764c3f767d06355d59bbfed075', 'coongoalty2@gmail.com', 'Khan textile', '01712848656', NULL, 'Dhak, motijhil', NULL, 1, 1, '2025-05-21 00:00:00', 1, '2025-05-21 05:49:08'),
(253, 'ABUL HASAN TUSHAR', 'bdnet', '88e2e464f2f3411f21baf63591e59764', 'bdnet@gmail.com', '', '014041330322', 0, 'Block # C, House #- 14-1, Ganda\r\nSavar', 'assets/images/default.jpg', 1, 2, '2025-05-22 00:00:00', 253, '2025-05-22 06:20:48'),
(254, 'Nasir', 'Nasir', '81dc9bdb52d04dc20036dbd8313ed055', 'nationalcable91@gmail.com', '', '01978203245', 0, '', 'assets/images/default.jpg', 1, 2, '2025-05-23 00:00:00', 2, '2025-05-22 18:02:47'),
(255, 'Ap1122', 'Ap1122', '09f03da14cd7fd2e4377116dbf2cf543', 'rr6648563@gmail.com', 'Bondhuonline ', '01813380250', NULL, 'Amishaapra sonaimuri noakhali', NULL, 1, 1, '2025-05-23 00:00:00', 1, '2025-05-23 14:42:01'),
(256, '.', '.', '81dc9bdb52d04dc20036dbd8313ed055', '', '', '', 0, '', 'assets/images/default.jpg', 1, 2, '2025-05-23 00:00:00', 2, '2025-05-23 15:03:03'),
(257, 'Bipul', '07', 'e58440eee1b5b774bbbbdbb4cdd15602', 'ahamedbipul9@gmail.com', '07', '01764501443', NULL, 'Dhaka', NULL, 1, 1, '2025-05-25 00:00:00', 1, '2025-05-25 05:26:34'),
(258, 'Zakir', 'Zakir', '827ccb0eea8a706c4c34a16891f84e7b', 'md.zakir@gmail.com', '', '01740559447', 0, '', 'assets/images/default.jpg', 1, 2, '2025-05-26 00:00:00', 2, '2025-05-26 07:21:35'),
(259, 'Md Nasirul Islam', 'nasir-bsd', '827ccb0eea8a706c4c34a16891f84e7b', 'nasirul.tsales@gmail.com', 'pixel net.bd', '01955922099', 0, 'alir more,north badda,dhaka-1212', NULL, 1, 1, '2025-07-02 00:00:00', 2, '2025-07-02 11:07:11'),
(260, 'Romjan ', 'Romjan ', 'e25547a0bdafccb9e0e475936ab3ed3f', 'RomjanRana2024@gmail.com', 'Mango', '01865120940', NULL, '.', NULL, 1, 1, '2025-05-28 00:00:00', 1, '2025-05-28 16:08:29'),
(261, 'Amanullah Amanullah', 'aman1', '2f30bef4b41e5ddf6e87822a147573de', 'amantechbd@gmail.com', 'Anaya Tech Limited', '01614008889', NULL, '1st Floor, Sheikh Gofur Plaza, House-4, Road S.S. Shah Road, Bandar , Narayanganj, Bangladesh', NULL, 1, 1, '2025-06-01 00:00:00', 1, '2025-06-01 11:37:11'),
(262, 'tariqul Islam', 'tariqul12', '81dc9bdb52d04dc20036dbd8313ed055', 'tariqul.bsd@gmail.com', '', '01303863702', 0, '', 'assets/images/default.jpg', 1, 2, '2025-06-02 00:00:00', 262, '2025-06-02 10:52:03'),
(263, 'Rana islam', 'Ranaislam', '949fd7a2bb5913c960bbb402f65c1896', 'RomjanRana2007@gmail.com', 'Internet ', '01944934048', NULL, '0.0.0.0', NULL, 1, 1, '2025-06-08 00:00:00', 1, '2025-06-08 07:06:52'),
(264, 'Test', 'test', '794f1666335d4aafd3849d14e44b3b4e', 'test@gmail.com', 'Test', '0188888888', NULL, 'test', NULL, 1, 1, '2025-06-08 00:00:00', 1, '2025-06-08 15:59:16'),
(265, 'Test', 'test2', '722e428518fdb1900122d23922eae07e', 'test2@gmail.com', 'Test', '01863117631', NULL, 'test', NULL, 1, 1, '2025-06-08 00:00:00', 1, '2025-06-08 16:09:37'),
(266, 'tretre', 'tttttttt', 'cf92c6c89ca214e207ce30f059ed8dee', 'trsdff@gmail.com', 'sdffwsdf', '018888888', NULL, 'sdfwd', NULL, 1, 1, '2025-06-08 00:00:00', 1, '2025-06-08 16:16:45'),
(267, 'Md Ashraful Islam Razib', '1', '202cb962ac59075b964b07152d234b70', 'ashrafulislam593@gmail.com', 'BSD', '01716623370', 0, 'Kacharipara, Dewanganj, Jamalpur', NULL, 1, 1, '2025-06-18 00:00:00', 188, '2025-06-18 16:46:02'),
(268, 'Md Ashraful Islam Razib', 'wifi zone', '0757590a38186f246e0ae6c76a0a5aaa', 'ashrafulislam593@gmail.com', 'Razib wifi', '01716623370', NULL, 'Kacharipara, Dewanganj, Jamalpur', NULL, 1, 1, '2025-06-19 00:00:00', 1, '2025-06-19 03:33:20'),
(269, 'tariqul Islam', 'tariqul', '4f95a19e56f2dce351a10d783dc93787', 'tariqul.bsd@gmail.com', 'Tariqul', '01303863702', NULL, 'Nikonjo 2', NULL, 1, 1, '2025-06-19 00:00:00', 1, '2025-06-19 04:12:25'),
(270, 'ashik mollk', 'bs', '9b2539fdc1a4a728eb77d7d7263a84da', 'ashik@gmail.com', 'BSD', '01765843844', NULL, '6 no. Taj market, Paridash roud, Banglabaza, Dhaka-1100', NULL, 1, 1, '2025-06-19 00:00:00', 1, '2025-06-19 04:15:36'),
(271, 'iqbal', 'iqbal', '440e274720fea91a4b4f0a58dead80ed', 'iqbalhossin20266@gmail.com', 'Friends online ', '01735030080', NULL, 'Madaripur ', NULL, 1, 1, '2025-06-19 00:00:00', 1, '2025-06-19 09:06:25'),
(272, 'Moniruzzaman ', 'admin', '3ba79c5ae07cc82e1103eae62f4916f8', 'msscablenet@gmail.com', 'Mss cable network ', '01718294838', NULL, 'Sylhet', NULL, 1, 1, '2025-06-19 00:00:00', 1, '2025-06-19 11:14:53'),
(273, 'Moniruzzaman ', 'user', 'e10adc3949ba59abbe56e057f20f883e', 'msscablenet@gmail.com', 'bsb', '01718294838', 2147483647, 'Sylhet', 'assets/images/4484303.png', 1, 1, '2025-06-21 00:00:00', 188, '2025-06-21 13:07:29'),
(274, 'Jewel Hossain Shahin', 'jewelhossain', 'd3696fdf2038070e151fa4013853180b', 'mdshahink00@gmail.com', 'Raw Network', '01625473676', NULL, 'Gazipur Board Bazar ', NULL, 1, 1, '2025-06-22 00:00:00', 1, '2025-06-21 18:01:10'),
(275, 'Nayeem ahmed', 'Shadu71', '97eccb357351071975e94506026611b3', 'nayeemahmed7575@gmail.com', 'Shadu network ', '01303791309', NULL, 'Gorgoria masterbari, sreepur,gazipur', NULL, 1, 1, '2025-06-22 00:00:00', 1, '2025-06-21 19:06:35'),
(276, 'Saker ali', 'saker_sutrapur', 'ee5270b59ec4732fb1ca8943b3f07302', 'zcommunicationdhaka@gmail.com', 'Z Communication ', '01915555255', NULL, '68 Hrishikesh Dash Road Sutrapur Dhaka 1100', NULL, 1, 1, '2025-06-23 00:00:00', 1, '2025-06-23 13:08:15'),
(277, 'juan perez', 'juan', 'ae3e95932dc18278970063f63971fdc7', 'juanperez@gmail.com', 'telecable', '12345678901', NULL, 'zapopan 342', NULL, 1, 1, '2025-06-23 00:00:00', 1, '2025-06-23 17:30:21'),
(278, 'Ishtiaq Himel', 'ISHTIAQ', '13c9e1fd447066f6ca686031efc93cc7', 'ishtiaqahmhimel@gmail.com', 'SUPERLINK', '01755627530', NULL, 'BEANIBAZAR SYLHET', NULL, 1, 1, '2025-06-24 00:00:00', 1, '2025-06-24 07:33:28'),
(279, 'Ebrahim Khalil', 'bas', '3fecf2b51ca8cac7cae04486263e6560', 'ebrahim.bd.net@gmail.com', 'Pallli Broadband', '01753904830', NULL, 'Vullarhat,kaliganj,lalmonirat', NULL, 1, 1, '2025-06-24 00:00:00', 1, '2025-06-24 16:50:20'),
(280, 'Ebrahim Khalil', 'aruhi', '3cf3fe953410d2c42efcd1c5d2d2d0a6', 'ebrahim.bd.net@gmail.com', 'Pallli Broadband', '01753904830', NULL, 'Vullarhat,kaliganj,lalmonirat', NULL, 1, 1, '2025-06-24 00:00:00', 1, '2025-06-24 16:52:46'),
(281, 'Ebrahim Khalil', 'palli', '4df74069c5cb93a875ac74f5024e2a01', 'ebrahim.bd.net@gmail.com', 'Pallli Broadband', '01753904830', NULL, 'Vullarhat,kaliganj,lalmonirat', NULL, 1, 1, '2025-06-24 00:00:00', 1, '2025-06-24 17:28:39'),
(282, 'RIZVE KHONDOKAR ', 'Rizve', '9de6bca3405988cf0b0654f132d44b60', 'rizve3683@gmail.com', 'RHR ', '01717134195', NULL, 'Patdhari', NULL, 1, 1, '2025-06-26 00:00:00', 1, '2025-06-26 09:03:29'),
(283, 'md jakir', 'shoshe', '12418802e4fd745b91b6a7363eb731cd', 'ssnsbd.info@gmail.com', 'shah sultan network system', 'hosen', NULL, 'kuppar pollis line mur netrakona', NULL, 1, 1, '2025-06-26 00:00:00', 1, '2025-06-26 09:34:37'),
(284, 'Rajib ', 'Ahmed', '827ccb0eea8a706c4c34a16891f84e7b', 'rajibahmed5356@gmail.com', '', '01777925356', 0, 'Patdhari,boyalia bazar,Ullapara, Sirajganj\r\nPatdhari,boyalia bazar,Ullapara, Sirajganj', 'assets/images/default.jpg', 1, 282, '2025-06-26 00:00:00', 282, '2025-06-26 11:07:42'),
(288, 'BSD Demo', 'bsdit', '8b1ea6db161830e29d6db3e8456d6baa', 'bsddemo55@gmail.com', '', '01716895565444', 1, 'Gangail,Mantala,Madhabpur,Habigonj,Sylhet. ', 'assets/images/IMG-20250527-WA0005.jpg', 1, 1, '2025-06-28 00:00:00', 2, '2025-06-28 07:03:25'),
(290, 'sajib', 'sajib', 'e10adc3949ba59abbe56e057f20f883e', 'sajibbd076@gmail.com', '', '01914479776', 0, '', 'assets/images/default.jpg', 1, 1, '2025-06-28 00:00:00', 1, '2025-06-28 07:27:23'),
(291, 'Md sujon mahmud ', 'Ss', 'a387cbf24480edf94e9c922a8610075a', 'sujonsscable@gmail.com', 'Ssinternet', '01729120220', NULL, 'Bogua ', NULL, 1, 1, '2025-07-02 00:00:00', 1, '2025-07-01 23:34:43'),
(292, 'Rezayon Sharif ', '12345', '4158be7d8a29a3840d0956d423bdbcdd', 'sharifhawlader506070@gmail.com', 'CT Broadband Network ', '01942458567', NULL, 'Gazipur ', NULL, 1, 1, '2025-07-03 00:00:00', 1, '2025-07-03 09:00:05'),
(293, 'jhg', 'MNSBDASDHKL', '582087f3c6d887e65c97af406c4dd1fc', 'kiron@cd2020.com', 'kjhoasdadh', '0140000000', NULL, 'sbdkjaBSDJ', NULL, 1, 1, '2025-07-03 00:00:00', 1, '2025-07-03 14:23:34'),
(294, 'Mohammed Forkan ', 'Mforkanullah', '1040d59df778ce6f1d3f148942471eec', 'visanaq@gmail.com', 'Fast Support ', '01818514849', NULL, 'Dhaka', NULL, 1, 1, '2025-07-05 00:00:00', 1, '2025-07-05 07:04:11'),
(297, 'defalut User', 'defalutuser', 'c4ca4238a0b923820dcc509a6f75849b', 'defalut55@gmail.com', '', '01716895565444', 0, '', 'assets/images/default.jpg', 1, 2, '2025-07-17 00:00:00', 2, '2025-07-17 05:45:06');

-- --------------------------------------------------------

--
-- Table structure for table `_useraccess`
--

CREATE TABLE `_useraccess` (
  `UserAccessId` int NOT NULL,
  `UserId` int NOT NULL,
  `UserType` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `MenuPermission` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'a:0:{}',
  `WorkPermission` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT 'a:2:{i:0;s:4:"view";i:1;s:3:"add";}',
  `EntryBy` int DEFAULT NULL,
  `EntryDate` datetime DEFAULT NULL,
  `UpdateBy` int DEFAULT NULL,
  `LastUpdate` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `_useraccess`
--

INSERT INTO `_useraccess` (`UserAccessId`, `UserId`, `UserType`, `MenuPermission`, `WorkPermission`, `EntryBy`, `EntryDate`, `UpdateBy`, `LastUpdate`) VALUES
(1, 1, 'SA', 'a:0:{}', 'a:4:{i:0;s:4:\"view\";i:1;s:3:\"add\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}', 1, '2025-02-03 00:00:00', 2, '2025-02-03 05:50:35'),
(2, 2, 'SA', 'a:45:{i:0;s:9:\"user_view\";i:1;s:11:\"user_create\";i:2;s:9:\"user_edit\";i:3;s:12:\"package_view\";i:4;s:9:\"zone_view\";i:5;s:12:\"subzone_view\";i:6;s:16:\"destination_view\";i:7;s:13:\"customer_view\";i:8;s:15:\"customer_create\";i:9;s:13:\"customer_edit\";i:10;s:15:\"cu', 'a:4:{i:0;s:4:\"view\";i:1;s:3:\"add\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}', 1, '2025-06-04 00:00:00', 2, '2025-06-04 11:34:33'),
(105, 105, 'SA', 'a:52:{i:0;s:15:\"total_bandwidth\";i:1;s:27:\"show_change_payment_history\";i:2;s:21:\"print_payment_history\";i:3;s:12:\"export_excel\";i:4;s:17:\"print_client_list\";i:5;s:17:\"print_client_bill\";i:6;s:7:\"invoice\";i:7;s:10:\"usercreate\";i:8;s:9:\"add_agent\";i:9;s:9:', 'a:4:{i:0;s:3:\"add\";i:1;s:4:\"view\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}', 1, '2023-09-03 01:45:08', 1, '2023-09-03 07:45:08'),
(106, 106, 'EO', 'a:52:{i:0;s:15:\"total_bandwidth\";i:1;s:27:\"show_change_payment_history\";i:2;s:21:\"print_payment_history\";i:3;s:12:\"export_excel\";i:4;s:17:\"print_client_list\";i:5;s:17:\"print_client_bill\";i:6;s:7:\"invoice\";i:7;s:10:\"usercreate\";i:8;s:9:\"add_agent\";i:9;s:9:', 'a:4:{i:0;s:3:\"add\";i:1;s:4:\"view\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}', 1, '2024-08-29 04:36:35', 1, '2024-10-23 11:02:06'),
(107, 107, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2024-10-21 00:00:00', 2, '2024-10-21 10:29:24'),
(111, 111, 'EO', 'a:18:{i:0;s:12:\"package_view\";i:1;s:9:\"zone_view\";i:2;s:12:\"subzone_view\";i:3;s:16:\"destination_view\";i:4;s:13:\"customer_view\";i:5;s:13:\"customer_edit\";i:6;s:15:\"customer_ledger\";i:7;s:19:\"billcollection_view\";i:8;s:8:\"all_paid\";i:9;s:12:\"previous_due\";i:', 'a:2:{i:0;s:4:\"view\";i:1;s:4:\"edit\";}', 2, '2025-04-29 00:00:00', 2, '2025-04-29 13:35:05'),
(112, 112, 'SA', 'a:3:{i:0;s:13:\"customer_view\";i:1;s:15:\"customer_create\";i:2;s:12:\"view_diagram\";}', 'a:1:{i:0;s:4:\"view\";}', 2, '2025-03-20 00:00:00', 2, '2025-03-20 06:26:04'),
(113, 113, 'EO', 'a:9:{i:0;s:9:\"user_view\";i:1;s:11:\"user_create\";i:2;s:9:\"user_edit\";i:3;s:9:\"zone_view\";i:4;s:12:\"subzone_view\";i:5;s:16:\"destination_view\";i:6;s:13:\"customer_view\";i:7;s:15:\"customer_create\";i:8;s:12:\"view_diagram\";}', 'a:4:{i:0;s:4:\"view\";i:1;s:3:\"add\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}', 2, '2025-02-19 00:00:00', 113, '2025-02-19 11:00:29'),
(114, 114, 'SA', 'a:3:{i:0;s:13:\"customer_view\";i:1;s:15:\"customer_create\";i:2;s:12:\"view_diagram\";}', 'a:1:{i:0;s:4:\"view\";}', 2, '2025-02-03 00:00:00', 2, '2025-02-03 05:54:58'),
(115, 115, 'EO', 'a:6:{i:0;s:13:\"customer_view\";i:1;s:15:\"customer_create\";i:2;s:12:\"view_diagram\";i:3;s:16:\"product_category\";i:4;s:7:\"product\";i:5;s:8:\"supplier\";}', 'a:1:{i:0;s:4:\"view\";}', 2, '2025-02-26 00:00:00', 2, '2025-02-26 10:45:28'),
(116, 116, 'SA', 'a:3:{i:0;s:13:\"customer_view\";i:1;s:15:\"customer_create\";i:2;s:12:\"view_diagram\";}', 'a:1:{i:0;s:4:\"view\";}', 2, '2025-02-03 00:00:00', 2, '2025-02-03 06:03:04'),
(117, 117, 'SA', 'a:3:{i:0;s:13:\"customer_view\";i:1;s:15:\"customer_create\";i:2;s:12:\"view_diagram\";}', 'a:1:{i:0;s:4:\"view\";}', 2, '2025-02-03 00:00:00', 2, '2025-02-03 07:00:29'),
(118, 118, 'SA', 'a:3:{i:0;s:13:\"customer_view\";i:1;s:15:\"customer_create\";i:2;s:12:\"view_diagram\";}', 'a:1:{i:0;s:4:\"view\";}', 2, '2025-02-03 00:00:00', 2, '2025-02-03 07:05:02'),
(119, 119, 'SA', 'a:3:{i:0;s:13:\"customer_view\";i:1;s:15:\"customer_create\";i:2;s:12:\"view_diagram\";}', 'a:1:{i:0;s:4:\"view\";}', 2, '2025-02-03 00:00:00', 2, '2025-02-03 07:07:05'),
(120, 120, 'SA', 'a:3:{i:0;s:13:\"customer_view\";i:1;s:15:\"customer_create\";i:2;s:12:\"view_diagram\";}', 'a:1:{i:0;s:4:\"view\";}', 2, '2025-02-03 00:00:00', 2, '2025-02-03 08:25:31'),
(121, 121, 'EO', 'a:17:{i:0;s:9:\"user_view\";i:1;s:11:\"user_create\";i:2;s:9:\"user_edit\";i:3;s:12:\"package_view\";i:4;s:12:\"package_view\";i:5;s:9:\"zone_view\";i:6;s:12:\"subzone_view\";i:7;s:16:\"destination_view\";i:8;s:13:\"customer_view\";i:9;s:15:\"customer_create\";i:10;s:13:\"vie', 'a:4:{i:0;s:4:\"view\";i:1;s:3:\"add\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}', 2, '2025-02-03 00:00:00', 2, '2025-02-03 09:28:54'),
(122, 122, 'EO', 'a:8:{i:0;s:12:\"package_view\";i:1;s:12:\"package_view\";i:2;s:9:\"zone_view\";i:3;s:12:\"subzone_view\";i:4;s:16:\"destination_view\";i:5;s:13:\"customer_view\";i:6;s:15:\"customer_create\";i:7;s:12:\"view_diagram\";}', 'a:4:{i:0;s:4:\"view\";i:1;s:3:\"add\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}', 2, '2025-02-03 00:00:00', 2, '2025-02-03 09:36:29'),
(123, 123, 'SA', 'a:0:{}', 'a:0:{}', 2, '2025-02-06 00:00:00', 2, '2025-02-06 16:54:31'),
(124, 124, 'EO', 'a:9:{i:0;s:9:\"user_view\";i:1;s:12:\"package_view\";i:2;s:9:\"zone_view\";i:3;s:13:\"customer_view\";i:4;s:15:\"customer_create\";i:5;s:13:\"customer_edit\";i:6;s:13:\"view_complain\";i:7;s:12:\"add_complain\";i:8;s:13:\"edit_complain\";}', 'a:2:{i:0;s:4:\"view\";i:1;s:4:\"edit\";}', 2, '2025-03-23 00:00:00', 2, '2025-03-23 09:20:32'),
(125, 125, 'EO', 'a:7:{i:0;s:19:\"mikrotik_connection\";i:1;s:22:\"mikrotik_online_secret\";i:2;s:19:\"mikrotik_all_secret\";i:3;s:26:\"mikrotik_unmatching_secret\";i:4;s:19:\"billcollection_view\";i:5;s:8:\"all_paid\";i:6;s:12:\"previous_due\";}', 'a:4:{i:0;s:4:\"view\";i:1;s:3:\"add\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}', 2, '2025-04-17 00:00:00', 125, '2025-04-17 06:51:05'),
(126, 126, 'SA', 'a:26:{i:0;s:9:\"user_view\";i:1;s:11:\"user_create\";i:2;s:9:\"user_edit\";i:3;s:12:\"package_view\";i:4;s:9:\"zone_view\";i:5;s:12:\"subzone_view\";i:6;s:16:\"destination_view\";i:7;s:13:\"customer_view\";i:8;s:15:\"customer_create\";i:9;s:13:\"customer_edit\";i:10;s:15:\"cu', 'a:4:{i:0;s:4:\"view\";i:1;s:3:\"add\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}', 2, '2025-04-19 00:00:00', 2, '2025-04-19 05:33:51'),
(127, 127, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-19 00:00:00', 1, '2025-04-19 11:35:51'),
(128, 128, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-19 00:00:00', 1, '2025-04-19 11:35:54'),
(129, 129, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-19 00:00:00', 1, '2025-04-19 11:35:56'),
(130, 130, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-19 00:00:00', 1, '2025-04-19 11:36:00'),
(131, 131, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-19 00:00:00', 1, '2025-04-19 11:38:17'),
(132, 132, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-19 00:00:00', 1, '2025-04-19 11:41:50'),
(133, 133, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-20 00:00:00', 1, '2025-04-20 06:02:06'),
(134, 134, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-20 00:00:00', 1, '2025-04-20 08:05:25'),
(135, 135, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-20 00:00:00', 1, '2025-04-20 10:29:11'),
(136, 136, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-20 00:00:00', 1, '2025-04-20 10:30:41'),
(137, 137, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-20 00:00:00', 1, '2025-04-20 10:42:01'),
(138, 138, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-20 00:00:00', 1, '2025-04-20 16:00:53'),
(139, 139, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-21 00:00:00', 1, '2025-04-21 04:50:46'),
(140, 140, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-21 00:00:00', 1, '2025-04-21 05:22:48'),
(141, 141, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-21 00:00:00', 1, '2025-04-21 09:41:37'),
(142, 142, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-21 00:00:00', 1, '2025-04-21 15:03:54'),
(143, 143, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-21 00:00:00', 1, '2025-04-21 15:38:02'),
(144, 144, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-21 00:00:00', 1, '2025-04-21 16:44:16'),
(145, 145, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-21 00:00:00', 1, '2025-04-21 17:49:03'),
(146, 146, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-22 00:00:00', 129, '2025-04-22 08:15:47'),
(147, 147, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-22 00:00:00', 1, '2025-04-22 08:56:38'),
(148, 148, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-22 00:00:00', 1, '2025-04-22 09:09:06'),
(149, 149, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-22 00:00:00', 1, '2025-04-22 09:38:38'),
(150, 150, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-22 00:00:00', 1, '2025-04-22 10:13:28'),
(151, 151, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-22 00:00:00', 1, '2025-04-22 12:05:02'),
(152, 152, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-22 00:00:00', 1, '2025-04-22 12:30:51'),
(153, 153, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-22 00:00:00', 1, '2025-04-22 12:33:30'),
(154, 154, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-22 00:00:00', 1, '2025-04-22 13:05:44'),
(155, 155, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-22 00:00:00', 1, '2025-04-22 17:13:59'),
(156, 156, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-22 00:00:00', 1, '2025-04-22 17:19:39'),
(157, 157, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-23 00:00:00', 1, '2025-04-23 06:20:58'),
(158, 158, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-23 00:00:00', 1, '2025-04-23 08:37:09'),
(159, 159, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-23 00:00:00', 1, '2025-04-23 13:40:49'),
(160, 160, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-23 00:00:00', 1, '2025-04-23 13:49:56'),
(161, 161, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-23 00:00:00', 1, '2025-04-23 13:53:49'),
(162, 162, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-24 00:00:00', 1, '2025-04-24 04:34:30'),
(163, 163, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-24 00:00:00', 1, '2025-04-24 05:07:29'),
(164, 164, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-24 00:00:00', 1, '2025-04-24 06:33:30'),
(165, 165, 'SA', 'a:45:{i:0;s:9:\"user_view\";i:1;s:11:\"user_create\";i:2;s:9:\"user_edit\";i:3;s:12:\"package_view\";i:4;s:9:\"zone_view\";i:5;s:12:\"subzone_view\";i:6;s:16:\"destination_view\";i:7;s:13:\"customer_view\";i:8;s:15:\"customer_create\";i:9;s:13:\"customer_edit\";i:10;s:15:\"cu', 'a:4:{i:0;s:4:\"view\";i:1;s:3:\"add\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}', 2, '2025-04-24 00:00:00', 165, '2025-04-24 14:08:53'),
(166, 166, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-26 00:00:00', 1, '2025-04-26 04:35:48'),
(167, 167, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-26 00:00:00', 1, '2025-04-26 06:35:11'),
(168, 168, 'SA', 'a:45:{i:0;s:9:\"user_view\";i:1;s:11:\"user_create\";i:2;s:9:\"user_edit\";i:3;s:12:\"package_view\";i:4;s:9:\"zone_view\";i:5;s:12:\"subzone_view\";i:6;s:16:\"destination_view\";i:7;s:13:\"customer_view\";i:8;s:15:\"customer_create\";i:9;s:13:\"customer_edit\";i:10;s:15:\"cu', 'a:4:{i:0;s:4:\"view\";i:1;s:3:\"add\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}', 1, '2025-04-26 00:00:00', 168, '2025-04-26 08:48:39'),
(169, 169, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-26 00:00:00', 1, '2025-04-26 10:04:55'),
(170, 170, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-26 00:00:00', 1, '2025-04-26 13:31:10'),
(171, 171, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-26 00:00:00', 1, '2025-04-26 13:38:02'),
(172, 172, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-26 00:00:00', 1, '2025-04-26 14:38:24'),
(173, 173, 'SA', 'a:45:{i:0;s:9:\"user_view\";i:1;s:11:\"user_create\";i:2;s:9:\"user_edit\";i:3;s:12:\"package_view\";i:4;s:9:\"zone_view\";i:5;s:12:\"subzone_view\";i:6;s:16:\"destination_view\";i:7;s:13:\"customer_view\";i:8;s:15:\"customer_create\";i:9;s:13:\"customer_edit\";i:10;s:15:\"cu', 'a:4:{i:0;s:4:\"view\";i:1;s:3:\"add\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}', 1, '2025-04-27 00:00:00', 173, '2025-04-27 06:14:25'),
(174, 174, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-27 00:00:00', 1, '2025-04-27 07:02:25'),
(175, 175, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-27 00:00:00', 1, '2025-04-27 10:36:37'),
(176, 176, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-27 00:00:00', 1, '2025-04-27 10:52:03'),
(177, 177, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-28 00:00:00', 1, '2025-04-28 05:04:21'),
(178, 178, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-28 00:00:00', 1, '2025-04-28 06:37:45'),
(179, 179, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-28 00:00:00', 1, '2025-04-28 06:59:58'),
(180, 180, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-28 00:00:00', 1, '2025-04-28 10:01:15'),
(181, 181, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-29 00:00:00', 1, '2025-04-29 04:47:17'),
(182, 182, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-29 00:00:00', 1, '2025-04-29 09:00:14'),
(183, 183, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-30 00:00:00', 1, '2025-04-29 19:06:49'),
(184, 184, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-30 00:00:00', 1, '2025-04-30 09:23:37'),
(185, 185, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-04-30 00:00:00', 1, '2025-04-30 10:00:04'),
(186, 186, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-01 00:00:00', 1, '2025-05-01 11:44:47'),
(187, 187, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-03 00:00:00', 1, '2025-05-03 10:26:49'),
(188, 188, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-03 00:00:00', 1, '2025-05-03 10:44:28'),
(189, 189, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-04 00:00:00', 1, '2025-05-04 11:20:45'),
(190, 190, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-04 00:00:00', 1, '2025-05-04 11:31:51'),
(191, 191, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-05 00:00:00', 1, '2025-05-05 05:49:32'),
(192, 192, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-05 00:00:00', 1, '2025-05-05 06:39:53'),
(193, 193, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-05 00:00:00', 1, '2025-05-05 07:13:29'),
(194, 194, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-06 00:00:00', 1, '2025-05-05 18:05:12'),
(195, 195, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-15 00:00:00', 1, '2025-05-15 05:01:33'),
(196, 226, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-16 00:00:00', 1, '2025-05-16 12:32:11'),
(197, 227, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-17 00:00:00', 1, '2025-05-17 06:41:10'),
(198, 228, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-17 00:00:00', 1, '2025-05-17 09:20:35'),
(199, 229, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-18 00:00:00', 1, '2025-05-18 05:09:43'),
(200, 230, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-18 00:00:00', 1, '2025-05-18 05:10:21'),
(201, 231, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-18 00:00:00', 1, '2025-05-18 05:10:23'),
(202, 232, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-18 00:00:00', 1, '2025-05-18 05:10:29'),
(203, 233, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-18 00:00:00', 1, '2025-05-18 05:15:40'),
(204, 234, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-18 00:00:00', 1, '2025-05-18 05:23:34'),
(205, 235, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-18 00:00:00', 1, '2025-05-18 05:56:10'),
(206, 236, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-18 00:00:00', 1, '2025-05-18 06:36:20'),
(207, 237, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-18 00:00:00', 1, '2025-05-18 06:43:33'),
(208, 238, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-18 00:00:00', 1, '2025-05-18 07:10:16'),
(209, 239, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-18 00:00:00', 1, '2025-05-18 10:05:48'),
(210, 240, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-18 00:00:00', 1, '2025-05-18 12:52:46'),
(211, 241, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-18 00:00:00', 1, '2025-05-18 15:56:05'),
(212, 242, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-19 00:00:00', 1, '2025-05-19 05:49:35'),
(213, 243, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-19 00:00:00', 1, '2025-05-19 05:50:58'),
(214, 244, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-19 00:00:00', 1, '2025-05-19 06:29:45'),
(215, 245, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-19 00:00:00', 1, '2025-05-19 07:42:23'),
(216, 246, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-19 00:00:00', 1, '2025-05-19 10:21:11'),
(217, 247, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-20 00:00:00', 1, '2025-05-20 06:38:01'),
(218, 248, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-20 00:00:00', 1, '2025-05-20 07:04:35'),
(219, 249, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-20 00:00:00', 1, '2025-05-20 09:48:53'),
(220, 250, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-21 00:00:00', 1, '2025-05-20 20:20:36'),
(221, 251, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-21 00:00:00', 1, '2025-05-21 05:43:17'),
(222, 252, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-21 00:00:00', 1, '2025-05-21 05:49:08'),
(223, 253, 'SA', 'a:45:{i:0;s:9:\"user_view\";i:1;s:11:\"user_create\";i:2;s:9:\"user_edit\";i:3;s:12:\"package_view\";i:4;s:9:\"zone_view\";i:5;s:12:\"subzone_view\";i:6;s:16:\"destination_view\";i:7;s:13:\"customer_view\";i:8;s:15:\"customer_create\";i:9;s:13:\"customer_edit\";i:10;s:15:\"cu', 'a:4:{i:0;s:4:\"view\";i:1;s:3:\"add\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}', 2, '2025-05-22 00:00:00', 253, '2025-05-22 06:59:36'),
(224, 254, 'EO', 'a:3:{i:0;s:13:\"customer_view\";i:1;s:15:\"customer_create\";i:2;s:12:\"view_diagram\";}', 'a:1:{i:0;s:4:\"view\";}', 2, '2025-05-23 00:00:00', 2, '2025-05-22 18:02:47'),
(225, 255, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-23 00:00:00', 1, '2025-05-23 14:42:01'),
(226, 256, 'SA', 'a:4:{i:0;s:12:\"package_view\";i:1;s:13:\"customer_view\";i:2;s:15:\"customer_create\";i:3;s:12:\"view_diagram\";}', 'a:1:{i:0;s:4:\"view\";}', 2, '2025-05-23 00:00:00', 2, '2025-05-23 14:55:47'),
(227, 257, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-25 00:00:00', 1, '2025-05-25 05:26:34'),
(228, 258, 'SA', 'a:46:{i:0;s:9:\"user_view\";i:1;s:11:\"user_create\";i:2;s:9:\"user_edit\";i:3;s:12:\"package_view\";i:4;s:12:\"package_view\";i:5;s:9:\"zone_view\";i:6;s:12:\"subzone_view\";i:7;s:16:\"destination_view\";i:8;s:13:\"customer_view\";i:9;s:15:\"customer_create\";i:10;s:13:\"cus', 'a:1:{i:0;s:4:\"view\";}', 2, '2025-05-26 00:00:00', 2, '2025-05-26 07:21:35'),
(229, 259, 'SA', 'a:45:{i:0;s:9:\"user_view\";i:1;s:11:\"user_create\";i:2;s:9:\"user_edit\";i:3;s:12:\"package_view\";i:4;s:9:\"zone_view\";i:5;s:12:\"subzone_view\";i:6;s:16:\"destination_view\";i:7;s:13:\"customer_view\";i:8;s:15:\"customer_create\";i:9;s:13:\"customer_edit\";i:10;s:15:\"cu', 'a:4:{i:0;s:4:\"view\";i:1;s:3:\"add\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}', 1, '2025-07-02 00:00:00', 2, '2025-07-02 11:07:11'),
(230, 260, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-05-28 00:00:00', 1, '2025-05-28 16:08:29'),
(231, 261, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-06-01 00:00:00', 1, '2025-06-01 11:37:11'),
(232, 262, 'SA', 'a:3:{i:0;s:9:\"user_view\";i:1;s:11:\"user_create\";i:2;s:9:\"user_edit\";}', 'a:4:{i:0;s:4:\"view\";i:1;s:3:\"add\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}', 2, '2025-06-02 00:00:00', 262, '2025-06-02 10:52:03'),
(233, 263, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-06-08 00:00:00', 1, '2025-06-08 07:06:52'),
(234, 264, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-06-08 00:00:00', 1, '2025-06-08 15:59:16'),
(235, 265, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-06-08 00:00:00', 1, '2025-06-08 16:09:37'),
(236, 266, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-06-08 00:00:00', 1, '2025-06-08 16:16:45'),
(237, 267, 'SA', 'a:7:{i:0;s:9:\"user_view\";i:1;s:11:\"user_create\";i:2;s:9:\"user_edit\";i:3;s:12:\"package_view\";i:4;s:9:\"zone_view\";i:5;s:12:\"subzone_view\";i:6;s:16:\"destination_view\";}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-06-18 00:00:00', 188, '2025-06-18 16:46:02'),
(238, 268, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-06-19 00:00:00', 1, '2025-06-19 03:33:20'),
(239, 269, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-06-19 00:00:00', 1, '2025-06-19 04:12:25'),
(240, 270, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-06-19 00:00:00', 1, '2025-06-19 04:15:36'),
(241, 271, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-06-19 00:00:00', 1, '2025-06-19 09:06:25'),
(242, 272, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-06-19 00:00:00', 1, '2025-06-19 11:14:53'),
(243, 273, 'SA', 'a:1:{i:0;s:13:\"customer_view\";}', 'a:4:{i:0;s:4:\"view\";i:1;s:3:\"add\";i:2;s:4:\"edit\";i:3;s:6:\"delete\";}', 1, '2025-06-21 00:00:00', 188, '2025-06-21 12:29:46'),
(244, 274, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-06-22 00:00:00', 1, '2025-06-21 18:01:10'),
(245, 275, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-06-22 00:00:00', 1, '2025-06-21 19:06:35'),
(246, 276, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-06-23 00:00:00', 1, '2025-06-23 13:08:15'),
(247, 277, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-06-23 00:00:00', 1, '2025-06-23 17:30:21'),
(248, 278, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-06-24 00:00:00', 1, '2025-06-24 07:33:28'),
(249, 279, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-06-24 00:00:00', 1, '2025-06-24 16:50:20'),
(250, 280, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-06-24 00:00:00', 1, '2025-06-24 16:52:46'),
(251, 281, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-06-24 00:00:00', 1, '2025-06-24 17:28:39'),
(252, 282, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-06-26 00:00:00', 1, '2025-06-26 08:56:43'),
(253, 283, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-06-26 00:00:00', 1, '2025-06-26 09:34:37'),
(254, 288, 'SA', 'a:45:{i:0;s:9:\"user_view\";i:1;s:11:\"user_create\";i:2;s:9:\"user_edit\";i:3;s:12:\"package_view\";i:4;s:9:\"zone_view\";i:5;s:12:\"subzone_view\";i:6;s:16:\"destination_view\";i:7;s:13:\"customer_view\";i:8;s:15:\"customer_create\";i:9;s:13:\"customer_edit\";i:10;s:15:\"cu', 'a:3:{i:0;s:4:\"view\";i:1;s:3:\"add\";i:2;s:4:\"edit\";}', 282, '2025-06-28 00:00:00', 2, '2025-06-28 07:08:30'),
(255, 290, 'SA', 'a:9:{i:0;s:9:\"user_view\";i:1;s:11:\"user_create\";i:2;s:9:\"user_edit\";i:3;s:12:\"package_view\";i:4;s:9:\"zone_view\";i:5;s:12:\"subzone_view\";i:6;s:16:\"destination_view\";i:7;s:19:\"billcollection_view\";i:8;s:12:\"previous_due\";}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-06-28 00:00:00', 1, '2025-06-28 07:31:05'),
(256, 291, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-07-02 00:00:00', 1, '2025-07-01 23:34:43'),
(257, 292, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-07-03 00:00:00', 1, '2025-07-03 09:00:05'),
(258, 293, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-07-03 00:00:00', 1, '2025-07-03 14:23:34'),
(259, 294, 'SA', 'a:0:{}', 'a:2:{i:0;s:4:\"view\";i:1;s:3:\"add\";}', 1, '2025-07-05 00:00:00', 1, '2025-07-05 07:04:11'),
(262, 297, 'SA', 'a:1:{i:0;s:9:\"user_view\";}', 'a:0:{}', 2, '2025-07-17 00:00:00', 2, '2025-07-17 05:46:00');

-- --------------------------------------------------------

--
-- Structure for view `vw_account`
--
DROP TABLE IF EXISTS `vw_account`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_account`  AS SELECT `tbl_account`.`acc_id` AS `acc_id`, `tbl_account`.`cus_id` AS `cus_id`, `tbl_account`.`agent_id` AS `agent_id`, `tbl_account`.`acc_head` AS `acc_head`, `tbl_account`.`acc_amount` AS `acc_amount`, `tbl_account`.`pay_amount` AS `pay_amount`, `tbl_account`.`acc_description` AS `acc_description`, `tbl_account`.`acc_type` AS `acc_type`, `tbl_account`.`entry_by` AS `entry_by`, `tbl_account`.`entry_date` AS `entry_date`, `tbl_account`.`update_by` AS `update_by`, `tbl_account`.`last_update` AS `last_update`, `_createuser`.`FullName` AS `FullName`, `_createuser`.`UserName` AS `UserName`, `tbl_accounts_head`.`acc_name` AS `acc_name` FROM ((`_createuser` left join `tbl_account` on((`_createuser`.`UserId` = `tbl_account`.`entry_by`))) left join `tbl_accounts_head` on((`tbl_accounts_head`.`acc_id` = `tbl_account`.`acc_head`))) ;

-- --------------------------------------------------------

--
-- Structure for view `vw_agent`
--
DROP TABLE IF EXISTS `vw_agent`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_agent`  AS SELECT `tbl_agent`.`ag_id` AS `ag_id`, `tbl_agent`.`cus_id` AS `cus_id`, `tbl_agent`.`ag_name` AS `ag_name`, `tbl_agent`.`ip` AS `ip`, `tbl_agent`.`queue_password` AS `queue_password`, `tbl_agent`.`type` AS `type`, `tbl_agent`.`mikrotik_id` AS `mikrotik_id`, `tbl_agent`.`mikrotik_disconnect` AS `mikrotik_disconnect`, `tbl_agent`.`taka` AS `taka`, `tbl_agent`.`mb` AS `mb`, `tbl_agent`.`int_mb` AS `int_mb`, `tbl_agent`.`ag_status` AS `ag_status`, `tbl_agent`.`ag_mobile_no` AS `ag_mobile_no`, `tbl_agent`.`regular_mobile` AS `regular_mobile`, `tbl_agent`.`ag_office_address` AS `ag_office_address`, `tbl_agent`.`zone` AS `zone`, `tbl_agent`.`sub_zone` AS `sub_zone`, `tbl_agent`.`destination` AS `destination`, `tbl_agent`.`pay_status` AS `pay_status`, `tbl_agent`.`ag_email` AS `ag_email`, `tbl_agent`.`national_id` AS `national_id`, `tbl_agent`.`nationalidphoto` AS `nationalidphoto`, `tbl_agent`.`gender` AS `gender`, `tbl_agent`.`onumac` AS `onumac`, `tbl_agent`.`fibercode` AS `fibercode`, `tbl_agent`.`connectiontype` AS `connectiontype`, `tbl_agent`.`agent_type` AS `agent_type`, `tbl_agent`.`due_status` AS `due_status`, `tbl_agent`.`bill_status` AS `bill_status`, `tbl_agent`.`payment_type` AS `payment_type`, `tbl_agent`.`bill_date` AS `bill_date`, `tbl_agent`.`remark` AS `remark`, `tbl_agent`.`inactive_date` AS `inactive_date`, `tbl_agent`.`billing_person_id` AS `billing_person_id`, `tbl_agent`.`entry_by` AS `entry_by`, `tbl_agent`.`update_by` AS `update_by`, `tbl_agent`.`entry_date` AS `entry_date`, `tbl_agent`.`connection_date` AS `connection_date`, `tbl_agent`.`created_at` AS `created_at`, `tbl_agent`.`last_update` AS `last_update`, `tbl_agent`.`deleted_at` AS `deleted_at`, timestampdiff(MONTH,`tbl_agent`.`entry_date`,curdate()) AS `diff_month`, timestampdiff(MONTH,`tbl_agent`.`entry_date`,`tbl_agent`.`inactive_date`) AS `in_diff_month`, date_format(`tbl_agent`.`connection_date`,'%d/%m/%Y') AS `connectiondate` FROM (`tbl_agent` left join `tbl_zone` on((`tbl_agent`.`zone` = `tbl_zone`.`zone_id`))) ;

-- --------------------------------------------------------

--
-- Structure for view `vw_all_income`
--
DROP TABLE IF EXISTS `vw_all_income`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_all_income`  AS SELECT `vw_bill_collection`.`entry_date` AS `entry_date`, `vw_bill_collection`.`amount` AS `amount`, `vw_bill_collection`.`flag` AS `flag` FROM `vw_bill_collection` ;

-- --------------------------------------------------------

--
-- Structure for view `vw_bill_amount_change`
--
DROP TABLE IF EXISTS `vw_bill_amount_change`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_bill_amount_change`  AS SELECT `tbl_bill_amount_change`.`bill_amount_id` AS `bill_amount_id`, `tbl_bill_amount_change`.`agent_id` AS `agent_id`, `tbl_bill_amount_change`.`bill_amount` AS `bill_amount`, `tbl_bill_amount_change`.`previous_bill_amount` AS `previous_bill_amount`, `tbl_bill_amount_change`.`dueTillEdit` AS `dueTillEdit`, `tbl_bill_amount_change`.`created_at` AS `created_at`, timestampdiff(MONTH,`tbl_bill_amount_change`.`created_at`,curdate()) AS `bill_change_diff_month` FROM `tbl_bill_amount_change` ;

-- --------------------------------------------------------

--
-- Structure for view `vw_bill_collection`
--
DROP TABLE IF EXISTS `vw_bill_collection`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_bill_collection`  AS SELECT `g`.`entry_date` AS `entry_date`, sum(`g`.`acc_amount`) AS `amount`, 'bill' AS `flag` FROM `tbl_account` AS `g` WHERE (`g`.`acc_type` = '3') GROUP BY `g`.`entry_date` ORDER BY `g`.`entry_date` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `vw_comb_income`
--
DROP TABLE IF EXISTS `vw_comb_income`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_comb_income`  AS SELECT `vw_all_income`.`entry_date` AS `entry_date`, group_concat(`vw_all_income`.`amount` separator '@') AS `comb_amount`, group_concat(`vw_all_income`.`flag` separator '@') AS `comb_flag` FROM `vw_all_income` GROUP BY `vw_all_income`.`entry_date` ;

-- --------------------------------------------------------

--
-- Structure for view `vw_marketing_agent`
--
DROP TABLE IF EXISTS `vw_marketing_agent`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_marketing_agent`  AS SELECT `tbl_marketing_agent`.`ag_id` AS `ag_id`, `tbl_marketing_agent`.`ag_name` AS `ag_name`, `tbl_marketing_agent`.`ag_mobile_no` AS `ag_mobile_no`, `tbl_marketing_agent`.`ag_address` AS `ag_address`, `tbl_marketing_agent`.`ag_email` AS `ag_email`, `tbl_marketing_agent`.`zone` AS `zone`, `tbl_marketing_agent`.`contact_date` AS `contact_date`, `tbl_marketing_agent`.`type` AS `type`, `tbl_marketing_agent`.`service` AS `service`, `tbl_marketing_agent`.`p_isp` AS `p_isp`, `tbl_marketing_agent`.`details` AS `details`, `tbl_marketing_agent`.`contact_by` AS `contact_by`, `tbl_marketing_agent`.`entry_by` AS `entry_by`, `_createuser`.`UserId` AS `UserId`, `_createuser`.`FullName` AS `FullName`, `tbl_zone`.`zone_name` AS `zone_name` FROM ((`tbl_marketing_agent` left join `_createuser` on((`tbl_marketing_agent`.`contact_by` = `_createuser`.`UserId`))) left join `tbl_zone` on((`tbl_marketing_agent`.`zone` = `tbl_zone`.`zone_id`))) ;

-- --------------------------------------------------------

--
-- Structure for view `vw_others`
--
DROP TABLE IF EXISTS `vw_others`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_others`  AS SELECT `g`.`entry_date` AS `entry_date`, sum(`g`.`acc_amount`) AS `amount`, 'others' AS `flag` FROM `tbl_account` AS `g` WHERE (`g`.`acc_type` = '2') GROUP BY `g`.`entry_date` ORDER BY `g`.`entry_date` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `vw_user_info`
--
DROP TABLE IF EXISTS `vw_user_info`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_user_info`  AS SELECT `_createuser`.`UserId` AS `UserId`, `_createuser`.`FullName` AS `FullName`, `_createuser`.`UserName` AS `UserName`, `_createuser`.`Password` AS `Password`, `_createuser`.`Email` AS `Email`, `_createuser`.`MobileNo` AS `MobileNo`, `_createuser`.`NationalId` AS `NationalId`, `_createuser`.`Address` AS `Address`, `_createuser`.`companyName` AS `companyName`, `_createuser`.`PhotoPath` AS `PhotoPath`, `_createuser`.`Status` AS `Status`, `_useraccess`.`UserType` AS `UserType`, `_useraccess`.`UserAccessId` AS `UserAccessId`, `_useraccess`.`MenuPermission` AS `MenuPermission`, `_useraccess`.`WorkPermission` AS `WorkPermission`, `_createuser`.`EntryBy` AS `EntryBy`, `_createuser`.`EntryDate` AS `EntryDate`, `_createuser`.`UpdateBy` AS `UpdateBy`, `_createuser`.`LastUpdate` AS `LastUpdate` FROM (`_createuser` left join `_useraccess` on((`_createuser`.`UserId` = `_useraccess`.`UserId`))) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bonus`
--
ALTER TABLE `bonus`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `business_targets`
--
ALTER TABLE `business_targets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customer_billing`
--
ALTER TABLE `customer_billing`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customer_return`
--
ALTER TABLE `customer_return`
  ADD PRIMARY KEY (`return_id`);

--
-- Indexes for table `daily_activity_log_check`
--
ALTER TABLE `daily_activity_log_check`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `delete_tbl_agent_log`
--
ALTER TABLE `delete_tbl_agent_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mikrotik_rule`
--
ALTER TABLE `mikrotik_rule`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mikrotik_user`
--
ALTER TABLE `mikrotik_user`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `monthly_bill_making_check`
--
ALTER TABLE `monthly_bill_making_check`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD UNIQUE KEY `month_year` (`month_year`);

--
-- Indexes for table `nodes`
--
ALTER TABLE `nodes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `onu_overview`
--
ALTER TABLE `onu_overview`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_onu` (`olt_ip`,`interface_name`),
  ADD KEY `idx_olt_ip` (`olt_ip`),
  ADD KEY `idx_interface` (`interface_name`),
  ADD KEY `idx_status` (`oper_status`),
  ADD KEY `idx_last_updated` (`last_updated`);

--
-- Indexes for table `onu_status`
--
ALTER TABLE `onu_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `olt_interface_unique` (`olt_ip`,`interface_name`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `product_model`
--
ALTER TABLE `product_model`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`purchase_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`sale_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `sms`
--
ALTER TABLE `sms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stock`
--
ALTER TABLE `stock`
  ADD PRIMARY KEY (`stock_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `stock_marketing_enable`
--
ALTER TABLE `stock_marketing_enable`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplier_id`);

--
-- Indexes for table `supplier_return`
--
ALTER TABLE `supplier_return`
  ADD PRIMARY KEY (`return_id`);

--
-- Indexes for table `tbl_account`
--
ALTER TABLE `tbl_account`
  ADD PRIMARY KEY (`acc_id`);

--
-- Indexes for table `tbl_accounts_head`
--
ALTER TABLE `tbl_accounts_head`
  ADD PRIMARY KEY (`acc_id`);

--
-- Indexes for table `tbl_agent`
--
ALTER TABLE `tbl_agent`
  ADD PRIMARY KEY (`ag_id`);

--
-- Indexes for table `tbl_agent_activity`
--
ALTER TABLE `tbl_agent_activity`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_bill_amount_change`
--
ALTER TABLE `tbl_bill_amount_change`
  ADD PRIMARY KEY (`bill_amount_id`);

--
-- Indexes for table `tbl_complains`
--
ALTER TABLE `tbl_complains`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_complains_new_user`
--
ALTER TABLE `tbl_complains_new_user`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_complain_templates`
--
ALTER TABLE `tbl_complain_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_due_logs`
--
ALTER TABLE `tbl_due_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_due_opening_amount_and_con_charge`
--
ALTER TABLE `tbl_due_opening_amount_and_con_charge`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_employee`
--
ALTER TABLE `tbl_employee`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_employee_transaction`
--
ALTER TABLE `tbl_employee_transaction`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_marketing_agent`
--
ALTER TABLE `tbl_marketing_agent`
  ADD PRIMARY KEY (`ag_id`);

--
-- Indexes for table `tbl_notice`
--
ALTER TABLE `tbl_notice`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- Indexes for table `tbl_package`
--
ALTER TABLE `tbl_package`
  ADD PRIMARY KEY (`package_id`);

--
-- Indexes for table `tbl_previous_due`
--
ALTER TABLE `tbl_previous_due`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_remarks`
--
ALTER TABLE `tbl_remarks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_service`
--
ALTER TABLE `tbl_service`
  ADD PRIMARY KEY (`s_id`);

--
-- Indexes for table `tbl_setting`
--
ALTER TABLE `tbl_setting`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_stock`
--
ALTER TABLE `tbl_stock`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_stock_category`
--
ALTER TABLE `tbl_stock_category`
  ADD PRIMARY KEY (`cat_id`);

--
-- Indexes for table `tbl_stock_item`
--
ALTER TABLE `tbl_stock_item`
  ADD PRIMARY KEY (`item_id`);

--
-- Indexes for table `tbl_zone`
--
ALTER TABLE `tbl_zone`
  ADD PRIMARY KEY (`zone_id`);

--
-- Indexes for table `_createuser`
--
ALTER TABLE `_createuser`
  ADD PRIMARY KEY (`UserId`);

--
-- Indexes for table `_useraccess`
--
ALTER TABLE `_useraccess`
  ADD PRIMARY KEY (`UserAccessId`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bonus`
--
ALTER TABLE `bonus`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `business_targets`
--
ALTER TABLE `business_targets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `customer_billing`
--
ALTER TABLE `customer_billing`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=310;

--
-- AUTO_INCREMENT for table `customer_return`
--
ALTER TABLE `customer_return`
  MODIFY `return_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `daily_activity_log_check`
--
ALTER TABLE `daily_activity_log_check`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `delete_tbl_agent_log`
--
ALTER TABLE `delete_tbl_agent_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `mikrotik_rule`
--
ALTER TABLE `mikrotik_rule`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `mikrotik_user`
--
ALTER TABLE `mikrotik_user`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `monthly_bill_making_check`
--
ALTER TABLE `monthly_bill_making_check`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `nodes`
--
ALTER TABLE `nodes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `onu_overview`
--
ALTER TABLE `onu_overview`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `onu_status`
--
ALTER TABLE `onu_status`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4351;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `category_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `product_model`
--
ALTER TABLE `product_model`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `purchase_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `sale_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `sms`
--
ALTER TABLE `sms`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `stock`
--
ALTER TABLE `stock`
  MODIFY `stock_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `stock_marketing_enable`
--
ALTER TABLE `stock_marketing_enable`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplier_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `supplier_return`
--
ALTER TABLE `supplier_return`
  MODIFY `return_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tbl_account`
--
ALTER TABLE `tbl_account`
  MODIFY `acc_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=340;

--
-- AUTO_INCREMENT for table `tbl_accounts_head`
--
ALTER TABLE `tbl_accounts_head`
  MODIFY `acc_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `tbl_agent`
--
ALTER TABLE `tbl_agent`
  MODIFY `ag_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1245;

--
-- AUTO_INCREMENT for table `tbl_agent_activity`
--
ALTER TABLE `tbl_agent_activity`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_bill_amount_change`
--
ALTER TABLE `tbl_bill_amount_change`
  MODIFY `bill_amount_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_complains`
--
ALTER TABLE `tbl_complains`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `tbl_complains_new_user`
--
ALTER TABLE `tbl_complains_new_user`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_complain_templates`
--
ALTER TABLE `tbl_complain_templates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbl_due_logs`
--
ALTER TABLE `tbl_due_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=910;

--
-- AUTO_INCREMENT for table `tbl_due_opening_amount_and_con_charge`
--
ALTER TABLE `tbl_due_opening_amount_and_con_charge`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_employee`
--
ALTER TABLE `tbl_employee`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `tbl_employee_transaction`
--
ALTER TABLE `tbl_employee_transaction`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `tbl_marketing_agent`
--
ALTER TABLE `tbl_marketing_agent`
  MODIFY `ag_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_notice`
--
ALTER TABLE `tbl_notice`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_package`
--
ALTER TABLE `tbl_package`
  MODIFY `package_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `tbl_previous_due`
--
ALTER TABLE `tbl_previous_due`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=273;

--
-- AUTO_INCREMENT for table `tbl_remarks`
--
ALTER TABLE `tbl_remarks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_service`
--
ALTER TABLE `tbl_service`
  MODIFY `s_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_setting`
--
ALTER TABLE `tbl_setting`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `tbl_stock`
--
ALTER TABLE `tbl_stock`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_stock_category`
--
ALTER TABLE `tbl_stock_category`
  MODIFY `cat_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_stock_item`
--
ALTER TABLE `tbl_stock_item`
  MODIFY `item_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_zone`
--
ALTER TABLE `tbl_zone`
  MODIFY `zone_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `_createuser`
--
ALTER TABLE `_createuser`
  MODIFY `UserId` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=298;

--
-- AUTO_INCREMENT for table `_useraccess`
--
ALTER TABLE `_useraccess`
  MODIFY `UserAccessId` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=263;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `nodes`
--
ALTER TABLE `nodes`
  ADD CONSTRAINT `nodes_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `nodes` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`category_id`);

--
-- Constraints for table `purchases`
--
ALTER TABLE `purchases`
  ADD CONSTRAINT `purchases_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `purchases_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`);

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `stock`
--
ALTER TABLE `stock`
  ADD CONSTRAINT `stock_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
