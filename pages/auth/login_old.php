<?php include './pages/auth/auth.php'; ?>

<div class="auth-fluid-pages pb-0">

    <div class="row auth-fluid">
        <!--Auth fluid left content -->
        <div class="col-3 auth-fluid-form-box p-3">
            <div class="align-items-center d-flex mt-5">
                <div class="p-3">
                    <div class="row">
                        <div class="col-md-12">
                            <h3 class="text-danger">
                                <?php if ($days_remaining <= 10 && $days_remaining >= 0)
                                    echo $messageNotice;
                                if ($amethod == "")
                                    echo 'You lost your connection! Please pay for reactive again.'; ?>
                            </h3>
                            <h4><?php echo $obj->notificationShow(); ?></h4>
                        </div>
                    </div>

                    <!-- Logo -->
                    <div class="auth-brand text-center text-lg-start">
                        <div class="auth-brand">
                            <a href="" class="logo logo-dark text-center">
                                <span class="logo-lg">
                                    <img src="assets/images/logo-dark.png" alt="" height="22">
                                </span>
                            </a>

                            <a href="" class="logo logo-light text-center">
                                <span class="logo-lg">
                                    <img src="assets/images/logo-light.png" alt="" height="22">
                                </span>
                            </a>
                        </div>
                    </div>

                    <!-- title-->
                    <h4 class="mt-0 ">Sign In</h4>
                    <p class="mb-4  text-muted">Enter your Username and Password to access account.</p>

                    <!-- form -->
                    <form action="" method="<?php echo $amethod ?>">
                        <div class="mb-3">
                            <label for="username" class="form-label">User Name</label>
                            <input class="form-control" type="<?php echo $etype ?>" id="username" name="username"
                                required="" placeholder="Enter your username">
                        </div>
                        <div class="mb-3">
                            <!-- <a href="auth-recoverpw-2.php" class="text-muted float-end"><small>Forgot your password?</small></a> -->
                            <label for="password" class="form-label ">Password</label>
                            <div class="input-group input-group-merge">
                                <input type="<?php echo $ptype ?>" id="password" name="password" class="form-control"
                                    placeholder="Enter your password">
                                <div class="input-group-text" data-password="false">
                                    <span class="password-eye"></span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="<?php echo $rtype ?>" class="form-check-input" id="checkbox-signin"
                                    name="remember">
                                <label class="form-check-label " for="checkbox-signin">Remember me</label>
                            </div>
                        </div>
                        <div class="text-center d-grid">
                            <button class="btn btn-primary" type="submit" <?php echo $battr ?> name="login"
                                value="<?php echo $etype ?>">Log In </button>
                        </div>
                    </form>
                    <!-- end form-->
                </div>
            </div>
        </div>
        <!-- end auth-fluid-form-box-->

        <!-- Auth fluid right content -->
        <div class="text-center col-9 m-0 p-0" style="height: 100vh; overflow: scroll;">
            <!-- Header -->
            <header class="text-center py-2">
                <div class="container position-relative">
                    <!-- Decorative Circle -->
                    <div
                        style="position: absolute; top: -30px; right: -50px; width: 150px; height: 150px; background: rgba(255, 255, 255, 0.1); border-radius: 50%; z-index: 1;">
                    </div>
                    <div
                        style="position: absolute; bottom: -30px; left: -50px; width: 200px; height: 200px; background: rgba(255, 255, 255, 0.1); border-radius: 50%; z-index: 1;">
                    </div>

                    <!-- Header Content -->
                    <h1 style="font-weight: bold; font-size: 30px; z-index: 2; position: relative; text-transform: uppercase;"
                        class="text-white">ISP Software</h1>
                    <p style="font-size: 1rem; margin-top: 10px; z-index: 2; position: relative;">Innovative solutions
                        for seamless internet service</p>
                </div>
            </header>

            <!-- Main Content -->
            <main class="container my-4">
                <!-- About Us Section -->
                <section id="about-us" class="text-center mb-5 py-5"
                    style="background: linear-gradient(135deg, #f3f8ff, #e8f1fc); border-radius: 10px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);">
                    <div class="container">
                        <h2 class="section-title" style="font-weight: bold; color: #0056b3; margin-bottom: 20px;">About
                            Us</h2>
                        <p class="lead" style="font-size: 1.2rem; color: #333;">We are a leading provider of ISP
                            software
                            solutions, helping businesses streamline their internet services with cutting-edge
                            technology.</p>
                        <p style="font-size: 1rem; color: #555;">Our mission is to empower internet service providers
                            with tools
                            that enhance efficiency, improve customer experience, and scale operations. With years of
                            expertise
                            in the ISP industry, we understand your needs and deliver tailored solutions.</p>
                        <div class="row justify-content-center mt-4">
                            <div class="col-md-4">
                                <div class="card border-0 shadow-sm p-3"
                                    style="background-color: #ffffff; border-radius: 10px; min-height: 150px;">
                                    <h5 class="card-title" style="font-weight: bold; color: #0056b3;">Our Vision</h5>
                                    <p class="card-text" style="color: #666;">To be the trusted technology partner for
                                        ISPs
                                        worldwide, driving innovation and growth in the digital era.</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-0 shadow-sm p-3"
                                    style="background-color: #ffffff; border-radius: 10px; min-height: 150px;">
                                    <h5 class="card-title" style="font-weight: bold; color: #0056b3;">Our Values</h5>
                                    <p class="card-text" style="color: #666;">Customer focus, innovation, and integrity
                                        are at
                                        the core of everything we do.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>


                <!-- Support Section -->
                <section id="support" class="support-section text-center">
                    <div class="container">
                        <h2 class="section-title" style="font-weight: bold;">We're Here to Help</h2>
                        <p class="mb-4">Our support team is available 24/7 to assist you with any issues or queries.</p>
                        <div class="row g-4 justify-content-center">
                            <!-- Email Support -->
                            <div class="col-md-4">
                                <div class="card p-4 text-center" style="min-height: 170px;">
                                    <div class="icon mb-3">
                                        <i class="bi bi-envelope-fill" style="font-size: 2rem; color: #0056b3;"></i>
                                    </div>
                                    <h5 class="card-title" style="font-weight: bold; color: #0056b3;">Email Support</h5>
                                    <p class="card-text">Send us an email for detailed assistance.</p>
                                    <p><strong>Email:</strong> <a href="mailto:support@yourcompany.com"
                                            class="text-primary">support@yourcompany.com</a></p>
                                </div>
                            </div>
                            <!-- Phone Support -->
                            <div class="col-md-4">
                                <div class="card p-4 text-center" style="min-height: 170px;">
                                    <div class="icon mb-3">
                                        <i class="bi bi-telephone-fill" style="font-size: 2rem; color: #0056b3;"></i>
                                    </div>
                                    <h5 class="card-title" style="font-weight: bold; color: #0056b3;">Phone Support</h5>
                                    <p class="card-text">Call us for immediate help from our experts.</p>
                                    <p><strong>Phone:</strong> +123-456-7890</p>
                                </div>
                            </div>
                            <!-- address -->
                            <div class="col-md-4">
                                <div class="card p-4 text-center" style="min-height: 245px;">
                                    <div class="icon mb-3">
                                        <i class="bi bi-geo-alt-fill" style="font-size: 2rem; color: #0056b3;"></i>
                                    </div>
                                    <h5 class="card-title" style="font-weight: bold; color: #0056b3;">Our Address</h5>
                                    <p class="card-text" style="color: #555;">123 Main Street, Suite 456<br>Tech City,
                                        Country
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>


                <!-- Products Section -->
                <section id="products" class="my-5">
                    <h2 class="section-title text-center" style="font-weight: bold;">Our Other Products</h2>
                    <div class="row g-3 mt-1">
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="card-title" style="font-size: 1rem; font-weight: bold; color: #0056b3;">Network Manager</h5>
                                    <p class="card-text">Monitor, optimize, and manage your network seamlessly with our
                                        advanced
                                        tools.</p>
                                    <a href="#" class="btn btn-primary">Visit Website</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="card-title" style="font-size: 1rem; font-weight: bold; color: #0056b3;">Billing System</h5>
                                    <p class="card-text">Automate your billing process and provide customers with
                                        transparent,
                                        hassle-free invoicing.</p>
                                    <a href="#" class="btn btn-primary">Visit Website</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="card-title" style="font-size: 1rem; font-weight: bold; color: #0056b3;">Customer Portal</h5>
                                    <p class="card-text">Empower your customers with self-service options for account
                                        management
                                        and support.</p>
                                    <a href="#" class="btn btn-primary">Visit Website</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </main>

            <!-- Footer -->
            <footer class="bg-dark text-white text-center py-3" style="width: 100%;">
                <p>&copy; 2024 ISP Software. All Rights Reserved.</p>
                <p>123 Main Street, Suite 456, Tech City, Country</p>
                <p>Email: <a href="mailto:info@yourcompany.com" class="text-light">info@yourcompany.com</a> | Phone:
                    +123-456-7890</p>
            </footer>
        </div>
    </div>
    <!-- end auth-fluid-->

    <!-- Authentication js -->
    <script src="assets/js/pages/authentication.init.js"></script>

</div>