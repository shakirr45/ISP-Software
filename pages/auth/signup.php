  <?php
    if (isset($_POST['submit'])) {
        $usernameCount = $obj->details_by_cond("_createuser", "UserName='" . $_POST['username'] . "'");
        if ($usernameCount > 0) {
            // If username already exists, display an error message
            echo '<script>window.location="?page=signup";</script>';
            exit;
        }

        $username = $_POST['username'];
        $number = rand(100, 999); // random 3-digit number
        $password = $username . $number;
        $name = $_POST['fullname'];
        $mobile = "88" . strval($_POST['phone']);

        $form_user = array(
            'FullName' => $_POST['fullname'],
            'UserName' => $_POST['username'],
            'Password' => MD5($password),
            'Email' => $_POST['email'],
            'companyName' => $_POST['companyName'],
            'MobileNo' => $_POST['phone'],
            'Address' => $_POST['address'],
            'Status' => 1,
            'EntryBy' => 1,
            'EntryDate' => date('Y-m-d'),
            'UpdateBy' => 1
        );
        $sms = $obj->sendsms($mobile, "Dear $name, UR UN:$username,Pass:$password.Demo:https://newisp.bsdbd.xyz/?page=login .Sales:01759080279,01959919805.www.bsdbd.com.Thank you,BSD");
        if ($sms) {
            $lastId = $obj->insertData('_createuser', $form_user);

            $form_access = array(
                'UserId' => $lastId,
                'UserType' => "SA",
                'EntryBy' => 1,
                'EntryDate' => date('Y-m-d'),
                'UpdateBy' => 1
            );
            $lastId = $obj->insertData('_useraccess', $form_access);
        }
        echo ' <script>window.location="?page=login"; </script>';
        exit;
    }
    ?>
  <style>
  body,
      html {
          height: 100%;
          position: relative;
          overflow-y: auto !important;
      }
      .signup-container {
          max-width: 650px;
          margin: 0 auto;
          padding: 2rem;
          height: auto;
          max-height: none;
          overflow-y: visible;
      }

      .card {
          border-radius: 1rem;
          box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
          overflow: hidden;
      }

      .card-header {
          background-color: #0d6efd;
          color: white;
          padding: 1.5rem;
      }

      .logo {
          max-height: 50px;
      }

      .form-section {
          padding: 2rem;
      }

      .btn-primary {
          padding: 0.75rem 2rem;
          font-weight: 600;
      }

      @media (max-width: 768px) {
          .signup-container {
              padding: 1rem;
             
          }

          .form-section {
              padding: 1.5rem;
          }
      }
  </style>
  <div class="signup-container my-3">
      <div class="card">
          <div class="card-header text-center">
              <h2 class="h4">Create Your Account</h2>
              <div class="d-flex justify-content-center align-items-center">
                  <div class="auth-brand text-center text-lg-start">
                      <a href="" class="logo logo-dark text-center">
                          <span class="logo-lg">
                              <img src="./assets/images/bsd/logo.png" alt="logo" class="logo-lg" height="80px" width="80px">
                          </span>
                      </a>
                  </div>

              </div>
              <p class="mt-2 mb-0 text-warning-800" style="font-size: 20px;">Create your account to access the demo – your username and password will be sent via SMS.</p>
          </div>

          <div class="form-section">
              <h2 class="h4 mb-4">Personal Information</h2>
              <form action="" method="POST">
                  <div class="row">
                      <div class="col-md-6 mb-3">
                          <label for="fullName" class="form-label">Full Name*</label>
                          <input type="text" class="form-control" placeholder="Enter Full Name" name="fullname" required>
                      </div>
                      <div class="col-md-6 mb-3">
                          <label for="phone" class="form-label">Phone Number*</label>
                          <input type="tel" class="form-control" placeholder="Enter Phone Number" name="phone" required>
                      </div>
                  </div>
                  <div class="mb-3">
                      <label for="email" class="form-label">Email Address*</label>
                      <input type="email" class="form-control" placeholder="Enter Email" name="email" required>
                  </div>
                  <div class="mb-3">
                      <label for="Company Name" class="form-label">Company Name*</label>
                      <input type="Company Name" class="form-control" placeholder="Enter Company Name" name="companyName" required>
                  </div>
                  <div class="mb-3">
                      <label for="address" class="form-label">Address*</label>
                      <input type="text" class="form-control" placeholder="Enter Address" name="address" required>
                  </div>
                  <div class="mb-3">
                      <label for="username" class="form-label">Preferred Username*</label>
                      <input type="text" class="form-control" placeholder="Enter Username" name="username" required>
                  </div>
                  <div class="d-grid">
                      <button type="submit" name="submit" class="btn btn-primary">Create Account</button>
                  </div>

                  <div class="text-center mt-3">
                      Already have an account? <a href="?page=login" class="font-weight-bold text-danger text-decoration-underline">Log In</a>
                  </div>
              </form>
          </div>
      </div>
  </div>