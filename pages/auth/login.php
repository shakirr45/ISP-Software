<?php include './pages/auth/auth.php'; ?>
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: system-ui, -apple-system, sans-serif;
    }

    @keyframes gradientAnimation {
        0% {
            background-position: 0% 50%;
        }

        50% {
            background-position: 100% 50%;
        }

        100% {
            background-position: 0% 50%;
        }
    }

    @keyframes cardGradient {
        0% {
            background-position: 0% 0%;
        }

        50% {
            background-position: 100% 100%;
        }

        100% {
            background-position: 0% 0%;
        }
    }

    body {
        min-height: 100vh;
        background: linear-gradient(-45deg,
                #f0f9ff,
                #e0f2fe,
                #dbeafe,
                #eff6ff,
                #f8fafc);
        animation: gradientAnimation 5s ease infinite;
        color: #1e293b;
    }

    .logo-lg {
        padding-bottom: 40px;
        width: 100px;
        height: 140px;
    }

    /* Default styles for larger screens */
    .login-container {
        display: flex;
        min-height: 100vh;
        margin-left: 25%;
        /* Leave space for the static login section */
        /*padding: 2rem;*/

    }

    .login-section {
        position: fixed;
        /* Fixed for larger screens */
        background-color:#0C2E50;
        top: 0;
        left: 0;
        width: 25%;
        /* Adjust width as needed */
        height: 100vh;
        /* Full height of the viewport */
        /*background-image: linear-gradient(147deg, rgb(56, 195, 116) 0%, #000000 74%);*/
        padding: 2rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        /* Add subtle shadow for elevation */
        z-index: 1000;
        /* Ensure it stays on top of other content */
        overflow-y: auto;
        /* Allow scrolling within the section if content overflows */
    }

    .login-section::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg,
                rgba(255, 255, 255, 0.1),
                rgba(59, 130, 246, 0.05),
                rgba(99, 102, 241, 0.05));
        background-size: 200% 200%;
        animation: cardGradient 10s ease infinite;
        z-index: -1;
    }


    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        color: rgb(255, 255, 255);
    }

    .form-group input {

        padding: 0.75rem 1rem;
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid rgba(148, 163, 184, 0.2);
        border-radius: 0.5rem;
        color: #1e293b;
        transition: all 0.3s ease;
    }

    .form-group input:focus {
        outline: none;
        border-color: rgb(49, 208, 97);
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
    }

    .remember-me {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin: 1rem 0;
        color: rgb(255, 255, 255);
    }

    /* Style the checkbox */
    .remember-me input[type="checkbox"] {
        appearance: none;
        width: 20px;
        height: 20px;
        border: 2px solid #ccc;
        border-radius: 4px;
        background-color: white;
        cursor: pointer;
        transition: background 0.3s ease, border-color 0.3s ease;
    }

    /* When the checkbox is checked, apply green gradient */
    .remember-me input[type="checkbox"]:checked {
        background: linear-gradient(to right, #34d399, #10b981);
        border-color: #10b981;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
    }

    .remember-me input[type="checkbox"]:checked::after {
        content: "✔";
        font-size: 16px;
        display: inline-block;
        color: white;
        font-weight: bold;
    }

    .btn-login {
        height: 45px;
        width: 100%;
        padding: 0.75rem;
        background: rgb(1, 50, 32);
        /*background: linear-gradient(159deg, rgba(1, 50, 32, 1) 0%, rgba(57, 255, 20, 1) 100%);*/
        /*background-size: 200% 200%;*/
        /*animation: gradientAnimation 6s ease infinite;*/
        color: #F8F8F8;
        border: none;
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-align: center;
    }

    .btn-login:hover {
        opacity: 0.9;
        transform: translateY(-1px);
    }

    .content-section {
        position: relative;
        border: 1px solid rgba(0, 0, 0, 0.2);
        /* Slightly lighter border */
        border-radius: 30px;
        box-shadow: 0 10px 10px rgba(0, 0, 0, 0.03);
        background: rgba(255, 255, 255, 0.2);
        /* Semi-transparent white background */
        backdrop-filter: blur(10px);
        /* Adds the blur effect */
        -webkit-backdrop-filter: blur(10px);
        /* For Safari support */
        padding: 1rem;
        /* Add padding for content inside */
    }

    .content-section::before {
        content: "";
        position: relative;

    }

    .header {
        text-align: center;

    }

    .gradient-text {
        background: rgb(1, 50, 32);
        background: linear-gradient(159deg, rgba(1, 50, 32, 1) 0%, rgba(57, 255, 20, 1) 100%);
        background-size: 200% 200%;
        animation: gradientAnimation 6s ease infinite;
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }

    .cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
        max-width: 1200px;
        margin: 0 auto;
    }

    .card-wrapper {

        text-align: left;
        position: relative;
        padding-top: 1rem;
        width: 100%;
        height: 80%;
    }

    /* For smaller screens (mobile devices) */
    @media screen and (max-width: 767px) {
        .card-wrapper {
            padding: 1rem;
            /* Adjust padding for smaller screens */

        }
    }


    .card {
        position: relative;
        padding: 1.5rem;
        background-image: linear-gradient(147deg, rgb(56, 195, 116) 0%, #000000 74%);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(148, 163, 184, 0.2);
        border-radius: 1rem;
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .card::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg,
                rgba(255, 255, 255, 0.1),
                rgba(59, 130, 246, 0.05),
                rgba(99, 102, 241, 0.05));
        background-size: 200% 200%;
        animation: cardGradient 8s ease infinite;
        z-index: -1;
    }

    .card:hover {
        transform: translateY(-2px);
        background-image: linear-gradient(147deg, rgb(82, 154, 103) 0%, #000000 74%);
        color: #F8F8F8;
    }

    .card-badge {
        position: absolute;
        top: 0;
        right: -0.25rem;
        padding: 0.25rem 0.75rem;
        background: rgb(1, 50, 32);
        background: linear-gradient(159deg, rgba(1, 50, 32, 1) 0%, rgba(57, 255, 20, 1) 100%);
        background-size: 200% 200%;
        animation: gradientAnimation 6s ease infinite;
        border-radius: 1rem;
        font-size: 0.75rem;
        color: #F8F8F8;
        z-index: 10;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }


    .feature-list {
        list-style: none;
        margin-top: 1rem;
    }

    .feature-list li {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: rgb(221, 241, 220);
        margin-bottom: 0.5rem;
    }


    .feature-list li svg {
        color: rgb(33, 218, 80);
        transition: color 0.3s ease;
    }

    .card:hover .feature-list li svg {
        color: rgb(0, 255, 30);
    }

    .logo {
        height: 40px;
        margin-bottom: 2rem;
    }

    .c-t {
        margin-bottom: 1rem;
        font-size: 1.25rem;
        color: white
    }

    .card-description {
        color: rgb(255, 255, 255);
        margin-bottom: 1rem;
    }

    .learn-more-btn {
        display: inline-block;
        padding: 0.5rem 1rem;
        margin-top: 1rem;
        background: rgb(1, 50, 32);
        background: linear-gradient(159deg, rgba(1, 50, 32, 1) 0%, rgba(57, 255, 20, 1) 100%);
        background-size: 200% 200%;
        animation: gradientAnimation 6s ease infinite;
        color: #F8F8F8;
        border: none;
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        font-size: 0.875rem;
        text-align: center;
    }

    .learn-more-btn:hover {
        opacity: 0.9;
        transform: translateY(-1px);
    }

    /* Auth Brand (Logo) */
    .auth-brand {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-bottom: 2rem;
    }

    .auth-brand img {
        max-width: 80%;
        /* Adjust size as needed */
        height: auto;
        display: block;
        margin: 0 auto;
    }

    /* Form Styling */
    form {
        display: flex;
        flex-direction: column;
        align-items: left;
        /* gap: 0.2rem; Adds space between form elements */
    }

    .form-group {
        width: 120%;
        /* Full width within the container */
        max-width: 300px;
        /* Limit the input field width */
        text-align: left;
        /* Align label text */
    }

    input[type="text"],
    input[type="password"] {
        height: 45px;
        width: 100%;
        /* Full width of the form-group */
        padding: 0.75rem;
        font-size: 1rem;
        border: 1px solid rgba(148, 163, 184, 0.5);
        border-radius: 8px;
        background-color: rgba(255, 255, 255, 0.9);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    button.btn-login {
        padding: 0.75rem 1.5rem;
        font-size: 1rem;
        color: white;
        background-color: #38c374;
        /* Matches the theme */
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    button.btn-login:hover {
        background-color: #2fa95d;
    }

    .services-section {
        padding: 4rem 2rem;
        position: relative;
        overflow: hidden;
        position: relative;
        border: 1px solid rgba(0, 0, 0, 0.2);
        /* Slightly lighter border */
        border-radius: 30px;
        box-shadow: 0 10px 10px rgba(0, 0, 0, 0.2);
        background: rgba(255, 255, 255, 0.2);
        /* Semi-transparent white background */
        backdrop-filter: blur(10px);
        /* Adds the blur effect */
        -webkit-backdrop-filter: blur(10px);
        /* For Safari support */

    }

    .services-section::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: -1;
    }

    .services-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        /* Ensures 4 columns */
        gap: 2rem;
        max-width: 1200px;
        margin: 2rem auto 0;
    }

    .service-card {
        height: 90%;
        background-color: #166d3b;
        background-image: linear-gradient(147deg, rgb(56, 195, 116) 0%, #000000 74%);
        color: #F8F8F8;
        padding: 2rem;
        border-radius: 1rem;
        border: 1px solid rgba(148, 163, 184, 0.2);
        transition: all 0.3s ease;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .service-card::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;

        background-color: #166d3b;
        background-image: linear-gradient(147deg, rgb(56, 195, 116) 0%, #000000 74%);
        background-size: 200% 200%;
        animation: cardGradient 8s ease infinite;
        z-index: -1;
    }

    .service-card:hover {
        transform: translateY(-5px);
        background: rgba(255, 255, 255, 0.9);
        box-shadow: 0 4px 20px rgba(148, 163, 184, 0.1);
    }

    .service-icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgb(1, 50, 32);
        background: linear-gradient(159deg, rgba(1, 50, 32, 1) 0%, rgba(57, 255, 20, 1) 100%);
        background-size: 200% 200%;
        animation: gradientAnimation 6s ease infinite;
        border-radius: 1rem;
        color: #F8F8F8;
    }

    .service-card h3 {
        color: rgb(255, 255, 255);
        font-size: 1.25rem;
        margin-bottom: 1rem;
    }

    .service-card p {
        color: #F8F8F8;
        line-height: 1.5;
    }

    @media (max-width: 1024px) {
        .services-grid {
            grid-template-columns: repeat(2, 1fr);
            /* 2 cards per row on medium screens */
        }
    }

    @media (max-width: 767px) {
        .services-grid {
            grid-template-columns: 1fr;
            /* 1 card per row on small screens */
        }
    }

    .main-content {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .content-wrapper {
        max-width: 800px;
        margin: 0 auto;
        padding: 2rem;
        backdrop-filter: blur(10px);
        border-radius: 1rem;
        background: linear-gradient(45deg,
                rgb(22, 34, 24),

                rgb(0, 0, 0),
                rgb(20, 21, 20),
                rgb(108, 119, 110)),
            rgb(127, 151, 133);

        background-size: 400% 400%;
        animation: gradientAnimation 20s ease infinite;
        color: #1e293b;
    }

    /* Media Query for screen widths between 767px and 1451px */
    @media screen and (min-width: 767px) and (max-width: 1451px) {
        .login-container {
            margin-left: 0;
            /* Remove the space for static section */
            padding-top: 2rem;
            flex-direction: column;
            /* Stack items vertically */
            align-items: center;
            /* Center horizontally */
        }

        .login-section {
            position: relative;
            /* Make it part of the normal document flow */
            width: 90%;
            /* Adjust width for smaller screens */
            max-width: 400px;
            /* Maintain a max-width */
            margin: 0 auto;
            /* Center it */
            padding: 2rem;
            height: auto;
            /* Let it adjust based on content */
            border-right: none;
            /* Remove border for smaller widths */
            border-bottom: 1px solid rgba(148, 163, 184, 0.2);
            /* Add bottom border */
            overflow-y: visible;
            /* Content visible without internal scrolling */
            border-radius: 20px;
            box-shadow: 0px 10px 10px 10px rgba(0, 0, 0, 0.1);
            background-color: #166d3b;
            background-image: linear-gradient(147deg, rgb(56, 195, 116) 0%, #000000 74%);
        }
    }

    /* Media Query for screen widths below 767px */
    @media screen and (max-width: 767px) {
        .login-container {
            margin-left: 0;
            /* No static space on small screens */
            display: flex;
            flex-direction: column;
            /* Stack items vertically */
            align-items: center;
            /* Center content */
        }

        .login-section {
            position: relative;
            /* Normal flow */
            width: 100%;
            /* Full width for smaller screens */
            max-width: none;
            /* Remove max-width constraint */
            padding: 1rem;
            /* Adjust padding */
            border-right: none;
            /* Remove border for small screens */
            border-bottom: 1px solid rgba(148, 163, 184, 0.2);
            /* Add bottom border */
            height: auto;
            /* Allow height to adjust naturally */
            overflow-y: visible;
            /* Ensure content is visible */
            border-radius: 20px;
            background-image: linear-gradient(147deg, rgb(56, 195, 116) 0%, #000000 74%);
        }

        .services-section {
            padding: 3rem 1rem;
            /* Adjust padding for services */
        }

        .services-grid {
            grid-template-columns: 1fr;
            /* Single column layout */
            gap: 1.5rem;
            /* Add spacing between grid items */
        }
    }

    .main-content {
        width: 100%;
        /* Ensure content takes full width */
    }

    .content-wrapper {
        padding: 1rem;
        /* Add spacing for content */
    }
</style>
<div class="login-container">
    <!-- Login Section -->
    <section class="login-section">
        <div class="row">
            <div class="col-md-12">
                <h3 class="text-danger">
                    <?php
                    if ($days_remaining <= 10 && $days_remaining >= 0)
                        echo $messageNotice;
                    if ($amethod == "")
                        echo 'You lost your connection! Please pay for reactive again.';
                    ?>
                </h3>
                <h4><?php echo $obj->notificationShow(); ?></h4>
            </div>
        </div>
        <!-- Logo -->
        <div class="auth-brand text-center text-lg-start">
            <a href="" class="logo logo-dark text-center">
                <span class="logo-lg">
                    <img src="./assets/images/bsd/logo.png" alt="logo" class="logo-lg">
                </span>
            </a>
        </div>
        <br>

        <h6 style="text-align:center; color:white">Sign In</h6>
        <p style="color: white; text-align:center; margin-bottom: 2rem">
            Enter your credentials to access your account
        </p>
        <form action="" method="<?php echo $amethod ?>">
            <div class="icon-field mb-16">
                <label class="form-label  text-white" for="password">User Name</label>

                <span class="icon top-50 translate-middle-y">
                    <iconify-icon icon="mdi:user"></iconify-icon>
                </span>
                <input
                    type="<?php echo $etype ?>"
                    id="username"
                    name="username"
                    placeholder="Enter your username" />
            </div>
            <div class="icon-field mb-16">
                <label class="form-label  text-white" for="password">Password</label>

                <span class="icon top-50 translate-middle-y">
                    <iconify-icon icon="mdi:user"></iconify-icon>
                </span>
                <input
                    type="<?php echo $ptype ?>"
                    id="password"
                    name="password"
                    placeholder="Enter your password" />
            </div>
            <div class="remember-me">
                <input type="<?php echo $rtype ?>"
                    id="remember"
                    name="remember">
                <label for="remember">Remember me</label>
            </div>
            <button type="submit" class="btn-login" <?php echo $battr ?> name="login" value="<?php echo $etype ?>">Sign In</button>
             <div class="text-center mt-3 text-white">
                don't have an account?
                <a href="?page=signup" class="font-weight-bold text-danger text-decoration-underline">Create Account</a>
            </div>
        </form>
    </section>
    <br><br>
    <section class="p-0 m-0">
        <img src="./assets/images/login-8.png" alt="New ISP" class="img-fluid">
    </section>

    <div class="main-content" hidden>
        <!-- Content Section -->
        <section class="content-section">
            <div class="header">
                <h4 style="font-size: 2.5rem; margin-bottom: 1rem; color: #1e293b">
                    Enterprise Solutions for
                    <span class="gradient-text">Modern ISPs</span>
                </h4>
                <p style="color: #64748b">
                    Comprehensive network solutions for modern enterprises
                </p>
            </div>
            <div class="content-wrapper">
                <div class="content-text">
                    <p
                        style="color: white; line-height: 1.7; margin-bottom: 1.5rem">
                        Our enterprise solutions provide cutting-edge technology and
                        comprehensive support for Internet Service Providers. With
                        advanced network management tools, automated billing systems,
                        and dedicated customer support, we help you deliver exceptional
                        service to your customers.
                    </p>
                    <p style="color: white; line-height: 1.7">
                        From infrastructure management to security solutions, our
                        platform offers everything you need to run and grow your ISP
                        business efficiently.
                    </p>
                </div>
            </div>
        </section>
        <br><br>
        <!-- Products Section -->
        <section class="content-section">
            <div class="header">
                <h4 style="font-size: 2.5rem; margin-bottom: 1rem; color: #1e293b">
                    Our <span class="gradient-text">Products</span>
                </h4>
                <p style="color: #64748b">
                    Powerful tools designed for scalable network management
                </p>
            </div>

            <div class="cards-grid">
                <!-- Card 1 -->
                <div class="card-wrapper">

                    <span class="card-badge">Featured</span>


                    <div class="card">
                        <h6 class="c-t">Network Manager Pro</h6>
                        <p class="card-description">
                            Enterprise-grade network monitoring and optimization suite
                        </p>
                        <ul class="feature-list" style="align-items: center;">
                            <li>
                                <svg
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                                Real-time monitoring
                            </li>
                            <li>
                                <svg
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                                AI-powered optimization
                            </li>
                            <li>
                                <svg
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                                Advanced analytics
                            </li>
                        </ul>
                        <button class="learn-more-btn">Learn More</button>
                    </div>
                </div>

                <!-- Card 2 -->
                <div class="card-wrapper">
                    <span class="card-badge">Featured</span>
                    <div class="card">
                        <h6 class="c-t">Billing System</h6>
                        <p class="card-description">
                            Automated billing and payment processing solution for your business.
                        </p>
                        <ul class="feature-list">
                            <li>
                                <svg
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                                Automated invoicing
                            </li>
                            <li>
                                <svg
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                                Payment tracking
                            </li>
                            <li>
                                <svg
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                                Revenue analytics
                            </li>
                        </ul>
                        <button class="learn-more-btn">Learn More</button>
                    </div>
                </div>

                <!-- Card 3 -->
                <div class="card-wrapper">
                    <span class="card-badge">Featured</span>
                    <div class="card">
                        <h6 class="c-t">Customer Portal</h6>
                        <p class="card-description">
                            Self-service platform for customer management and smooth service delivery.
                        </p>
                        <ul class="feature-list">
                            <li>
                                <svg
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                                Account management
                            </li>
                            <li>
                                <svg
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                                Usage tracking
                            </li>
                            <li>
                                <svg
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                                Support ticketing
                            </li>
                        </ul>
                        <button class="learn-more-btn">Learn More</button>
                    </div>
                </div>
            </div>
        </section>


        <br>
        <br>
        <!-- Services Section -->
        <div>
            <section class="services-section">
                <div class="header">
                    <h4 style="font-size: 2.25rem; margin-bottom: 1rem; color: #1e293b">
                        Our <span class="gradient-text">Services</span>
                    </h4>
                    <p style="color: #64748b">
                        Comprehensive solutions for modern internet service providers
                    </p>
                </div>

                <div class="services-grid">
                    <div class="service-card">
                        <div class="service-icon">
                            <svg
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round">
                                <path
                                    d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                                <line x1="3" y1="6" x2="21" y2="6"></line>
                                <path d="M16 10a4 4 0 0 1-8 0"></path>
                            </svg>
                        </div>
                        <h6 style="color: white;">Network Infrastructure</h6>
                        <p>
                            Build and maintain robust network infrastructure with our
                            advanced solutions
                        </p>
                    </div>

                    <div class="service-card">
                        <div class="service-icon">
                            <svg
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="16" x2="12" y2="12"></line>
                                <line x1="12" y1="8" x2="12.01" y2="8"></line>
                            </svg>
                        </div>
                        <h6 style="color: white;">24/7 Support</h6>
                        <p>Round-the-clock technical support and maintenance services</p>
                    </div>

                    <div class="service-card">
                        <div class="service-icon">
                            <svg
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round">
                                <path
                                    d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                <polyline points="7.5 4.21 12 6.81 16.5 4.21"></polyline>
                                <polyline points="7.5 19.79 7.5 14.6 3 12"></polyline>
                                <polyline points="21 12 16.5 14.6 16.5 19.79"></polyline>
                                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                <line x1="12" y1="22.08" x2="12" y2="12"></line>
                            </svg>
                        </div>
                        <h6 style="color: white;">Network Security</h6>
                        <p>
                            Advanced security solutions to protect your network and
                            customers
                        </p>
                    </div>

                    <div class="service-card">
                        <div class="service-icon">
                            <svg
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round">
                                <path
                                    d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                        </div>
                        <h6 style="color:white">Email Solutions</h6>
                        <p>Enterprise email hosting and management services</p>
                    </div>
                </div>
            </section>
        </div>
        <br>
        <br>


        <br>
        <br>
    </div>
</div>
</section>