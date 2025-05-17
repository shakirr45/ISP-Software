<?php
require_once __DIR__ . '/vendor/autoload.php';
use Mpdf\Mpdf;

try {
	// Data Retrieval
	require(realpath(__DIR__ . '/../../services/Model.php'));
	$obj = new Model();
	$token = isset($_GET['token']) ? $_GET['token'] : NULL;

	$account = $obj->details_by_cond('tbl_account', "acc_id='$token'");

	// Date format
	$datetime = new DateTime($account['entry_date']);
	$month = $datetime->format('F');
	$year = $datetime->format('Y');

	$uniqueReceiptNumber = date('YmdHis') . '-' . mt_rand(10, 99);

	$agentDetails = $obj->details_by_cond('tbl_agent', "ag_id='$account[agent_id]'");

	$html = '
    <html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            h1 { text-align: center; font-size: 18px; background: #333; color: #fff; padding: 5px 0; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
            th { background: #f4f4f4; }
            .logo { text-align: left; margin-bottom: 20px; }
            .total { text-align: right; font-weight: bold; }
            .signature { margin-top: 150px; text-align: right; padding-right: 50px;}
        </style>
    </head>
    <body>
        <header>
            <h1>RECEIPT</h1>
        </header>

        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; margin-top: 50px">
            <tr>
                <td style="width: 50%; vertical-align: top; border: none;">
                    <table style="width: 80%; border-collapse: collapse;">
                        <tr>
                            <th style="border: 1px solid #ddd; padding: 8px;">Invoice #</th>
                            <td style="border: 1px solid #ddd; padding: 8px;">' . $uniqueReceiptNumber . '</td>
                        </tr>
                        <tr>
                            <th style="border: 1px solid #ddd; padding: 8px;">Date</th>
                            <td style="border: 1px solid #ddd; padding: 8px;">' . date("Y-m-d") . '</td>
                        </tr>
                    </table>
                </td>
                <td style="width: 50%; vertical-align: top; border: none;">
                    <table style="width: 80%; border-collapse: collapse;">
                        <tr>
                            <th style="border: 1px solid #ddd; padding: 5px;">Customer Id</th>
                            <td style="border: 1px solid #ddd; padding: 5px;">' . $agentDetails["cus_id"] . '</td>
                        </tr>
                        <tr>
                            <th style="border: 1px solid #ddd; padding: 5px;">Name</th>
                            <td style="border: 1px solid #ddd; padding: 5px;">' . $agentDetails["ag_name"] . '</td>
                        </tr>
                        <tr>
                            <th style="border: 1px solid #ddd; padding: 5px;">Email</th>
                            <td style="border: 1px solid #ddd; padding: 5px;">' . (!empty($agentDetails["ag_email"]) ? $agentDetails["ag_email"] : "N/A") . '</td>
                        </tr>
                        <tr>
                            <th style="border: 1px solid #ddd; padding: 5px;">Address</th>
                            <td style="border: 1px solid #ddd; padding: 5px;">' . (!empty($agentDetails["ag_office_address"]) ? $agentDetails["ag_office_address"] : "N/A") . '</td>
                        </tr>
                        <tr>
                            <th style="border: 1px solid #ddd; padding: 5px;">Mobile</th>
                            <td style="border: 1px solid #ddd; padding: 5px;">' . (!empty($agentDetails["ag_mobile_no"]) ? $agentDetails["ag_mobile_no"] : "N/A") . '</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; margin-top: 30px">
            <thead>
                <tr>
                    <th>SL</th>
                    <th>Description</th>
                    <th>Month</th>
                    <th>Speed</th>
                    <th>Bill Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>' . $account["acc_description"] . '</td>
                    <td>' . $month . ' ' . $year . '</td>
                    <td>' . ($agentDetails["mb"] ?? "N/A") . '</td>
                    <td>' . $account["acc_amount"] . '</td>
                </tr>
            </tbody>
        </table>

        <table style="margin-top: 50px">
            <tr>
                <th class="total">Total</th>
                <td>' . $account["acc_amount"] . '</td>
            </tr>
        </table>


		 <div class="signature">
            <strong style="border-top: 1px solid #000;">Authorized Signature</strong>
        </div>
    </body>
    </html>
    ';

	// mPDF Initialization
	$mpdf = new Mpdf(['orientation' => 'P', 'format' => 'A4']);
	$mpdf->WriteHTML($html);

	// Adding Footer Text
	$mpdf->SetHTMLFooter('
        <div style="text-align: center; font-weight: bold; font-size: 12px; padding: 10px;">
            Thank you for your business
        </div>
    ');

	$mpdf->Output();
} catch (\Mpdf\MpdfException $e) {
	echo "PDF Generation Error: " . $e->getMessage();
}
exit;
