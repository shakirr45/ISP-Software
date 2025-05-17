<?php
     include("dbconfig_robotisp.php");
    
    if($_SERVER['REQUEST_METHOD']=='POST'){
        
              $userName = $_POST['userName'];
            
              $password = $_POST['password'];

              $hash_storage = md5($password);

        
              $check_duplicate_data = "SELECT UserName, Password FROM vw_user_info WHERE UserName = '".$userName."' AND  Password = '".$hash_storage."'";
              $result = mysqli_query($con, $check_duplicate_data);
              $count = mysqli_num_rows($result);
        
              if($count > 0){
                //   echo "Successfull"."<br>";
                  $all_user_query = "SELECT * FROM vw_user_info WHERE UserName = '$userName'";
                  $result_all = mysqli_query($con, $all_user_query);
                  if (mysqli_num_rows($result_all) > 0) {
                    // output data of each row
                    $rows = array();
                    while($row = mysqli_fetch_assoc($result_all)) {
                        // echo "id : ".$row["id"]."<br>";
                        // echo "Name : ".$row["name"]."<br>";
                        // echo "phone : ".$row["phone"]."<br>";
                        $rows[] = $row;
                    }
                    echo json_encode($rows);
                }
        
              }else{
                  echo "Not Found Any Account ! Enter your correct Phone Number & Password";
              }
        
            //   echo $name;
            //   echo $email;
            //   echo $password;
          }
?>