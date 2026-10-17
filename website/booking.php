<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Book Your Cleaning Service | Phambili Ma-Africa</title>
    <link rel="icon" type="image/png" sizes="32x32" href="images/PM-Logo.jpg">
    
    <!-- Open Graph Meta Tags for Social Media Preview -->
    <meta property="og:title" content="Our Booking | Phambili Ma-Africa">
    <meta property="og:description" content="Book professional cleaning services with Phambili Ma-Africa. Easy online booking for home and business.">
    <meta property="og:image" content="images/PM-Logo.jpg">
    <meta property="og:url" content="https://phambili-ma-africa.com/booking.php">
    <meta property="og:type" content="website">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Our Booking | Phambili Ma-Africa">
    <meta name="twitter:description" content="Book professional cleaning services with Phambili Ma-Africa. Easy online booking for home and business.">
    <meta name="twitter:image" content="iimages/PM-Logo.jpg">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./css/style.css">
    <link rel="stylesheet" href="./css/responsiveness.css">
    <link rel="stylesheet" href="./css/social-media.css">
</head>

<body">
    <!-- Updated Header Section -->
    <header class="header">
        <!-- Hamburger Menu Button -->
        <button class="hamburger-btn" id="hamburger-btn" aria-label="Toggle menu">
            <img src="images/menu.png" alt="Close">
        </button>

        <!-- Logo -->
        <div class="logo">
            <a href="index.php">
                <img src="images/PM-LogoB.png" alt="Phambili Ma-Africa Logo">
            </a>
        </div>

        <!-- Icons (Right) -->
        <div class="header-icons">
            <div class="notification-bell-customer" data-auth="role:customer">
                <button class="bell-btn-customer" onclick="toggleCustomerNotificationDropdown()">
                    <i class="fas fa-bell"></i>
                </button>
                <div class="notification-dropdown-customer" id="notificationDropdownCustomer">
                    <div class="notification-dropdown-header">
                        <h4>Notifications</h4>
                        <button class="mark-all-read-btn" onclick="markAllCustomerNotificationsRead()">Mark all read</button>
                    </div>
                    <div class="notification-dropdown-content">
                        <div class="no-notifications">
                            <i class="fas fa-bell-slash"></i>
                            <p>No new notifications</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="user-profile-dropdown" id="user-profile-dropdown" data-auth="in">
                    <button class="icon-link user-profile-btn" aria-label="User Profile" id="user-profile-btn">
                        <img src="images/user.png" alt="User" class="icon" id="user-icon">
                    </button>
                    <div class="profile-dropdown-menu" id="profile-dropdown-menu">
                        <div data-auth="role:staff,admin,main_admin,super_admin">
                            <div class="profile-dropdown-header">
                                <i class="fas fa-user-shield admin-icon"></i>
                                <div class="profile-info">
                                    <span class="profile-name" data-user-name>Administrator</span>
                                    <span class="profile-role">Admin</span>
                                </div>
                            </div>
                            <div class="profile-dropdown-links">
                                <a href="#" class="dropdown-link admin-app-link">
                                    <i class="fas fa-tachometer-alt"></i>
                                    Admin Dashboard
                                </a>
                                <a href="#" class="dropdown-link logout-link" data-logout>
                                    <i class="fas fa-sign-out-alt"></i>
                                    Log Out
                                </a>
                            </div>
                        </div>
                        <div data-auth="role:customer">
                            <div class="profile-dropdown-header">
                                <i class="fas fa-user customer-icon"></i>
                                <div class="profile-info">
                                    <span class="profile-name" data-user-name>Customer</span>
                                    <span class="profile-role">Customer</span>
                                </div>
                            </div>
                            <div class="profile-dropdown-links">
                                <a href="bookings.php" class="dropdown-link">
                                    <i class="fas fa-calendar-check"></i>
                                    Bookings
                                </a>
                                <a href="profile.php" class="dropdown-link">
                                    <i class="fas fa-user"></i>
                                    Profile
                                </a>
                                <a href="#" class="dropdown-link logout-link" data-logout>
                                    <i class="fas fa-sign-out-alt"></i>
                                    Log Out
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Guest: Icon redirects to login page -->
                <a href="login.php" class="icon-link" aria-label="Login/Register" data-auth="out">
                    <img src="images/user.png" alt="Login" class="icon">
                </a>
            <button class="cart-btn" id="cart-btn" aria-label="Shopping Cart">
                <img src="images/shopping-bag.png" alt="" class="icon">
                <span class="cart-badge" data-auth="in">0</span>
            </button>
        </div>
    </header>

    <!-- Overlay (must be outside header but before sidebars) -->
    <div class="menu-overlay" id="menu-overlay"></div>

    <!-- Navigation Sidebar (Left) -->
    <nav class="main-nav" id="main-nav">
        <div class="nav-header">
            <div class="nav-logo">
                <img src="images/PM-LogoB.png" alt="Phambili Ma-Africa Logo">
            </div>
            <button class="close-nav-btn" id="close-nav-btn" aria-label="Close menu">
                <svg viewBox="0 0 24 24" width="30" height="30">
                    <path fill="currentColor"
                        d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                </svg>
            </button>
        </div>
        <ul class="nav-list">
            <li class="nav-item"><a href="index.php" class="nav-link">Home</a></li>
            <li class="nav-item"><a href="about.php" class="nav-link">About</a></li>
            <li class="nav-item"><a href="contact.php" class="nav-link">Contact</a></li>
            <li class="nav-item"><a href="services.php" class="nav-link">Services</a></li>
            <li class="nav-item"><a href="products.php" class="nav-link">Products</a></li>

            <!-- Dynamic navigation based on role -->
                <li class="nav-item" data-auth="role:staff,admin,main_admin,super_admin">
                    <a href="#" class="nav-link admin-nav-link admin-app-link">
                        Admin Dashboard
                    </a>
                </li>
                <li class="nav-item" data-auth="role:staff,admin,main_admin,super_admin">
                    <a href="#" class="nav-link" data-logout> Log Out</a>
                </li>
                <li class="nav-item" data-auth="role:customer">
                    <a href="bookings.php" class="nav-link">
                        Bookings
                    </a>
                </li>
                <li class="nav-item" data-auth="role:customer">
                    <a href="profile.php" class="nav-link">
                        Profile
                    </a>
                </li>
                <li class="nav-item" data-auth="role:customer">
                    <a href="#" class="nav-link" data-logout> Log Out</a>
                </li>
                <li class="nav-item" data-auth="out">
                    <a href="login.php" class="nav-link"> Log In</a>
                </li>

            <li class="nav-item">
                <a href="booking.php" class="nav-link book-now-btn active">
                    Book Now
                </a>
            </li>
        </ul>
        <div class="nav-footer">
            <div class="social-links">
                <a href="https://www.facebook.com/people/Phambili-Ma-Africa/100063507009282/" target="_blank"
                    aria-label="Facebook">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                        class="bi bi-facebook" viewBox="0 0 16 16">
                        <path
                            d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951" />
                    </svg>
                </a>
                <a href="https://www.instagram.com/phambilimaafrica?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw=="
                    target="_blank" aria-label="Instagram">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                        class="bi bi-instagram" viewBox="0 0 16 16">
                        <path
                            d="M8 0C5.829 0 5.556.01 4.703.048 3.85.088 3.269.222 2.76.42a3.9 3.9 0 0 0-1.417.923A3.9 3.9 0 0 0 .42 2.76C.222 3.268.087 3.85.048 4.7.01 5.555 0 5.827 0 8.001c0 2.172.01 2.444.048 3.297.04.852.174 1.433.372 1.942.205.526.478.972.923 1.417.444.445.89.719 1.416.923.51.198 1.09.333 1.942.372C5.555 15.99 5.827 16 8 16s2.444-.01 3.298-.048c.851-.04 1.434-.174 1.943-.372a3.9 3.9 0 0 0 1.416-.923c.445-.445.718-.891.923-1.417.197-.509.332-1.09.372-1.942C15.99 10.445 16 10.173 16 8s-.01-2.445-.048-3.299c-.04-.851-.175-1.433-.372-1.941a3.9 3.9 0 0 0-.923-1.417A3.9 3.9 0 0 0 13.24.42c-.51-.198-1.092-.333-1.943-.372C10.443.01 10.172 0 7.998 0zm-.717 1.442h.718c2.136 0 2.389.007 3.232.046.78.035 1.204.166 1.486.275.373.145.64.319.92.599s.453.546.598.92c.11.281.24.705.275 1.485.039.843.047 1.096.047 3.231s-.008 2.389-.047 3.232c-.035.78-.166 1.203-.275 1.485a2.5 2.5 0 0 1-.599.919c-.28.28-.546.453-.92.598-.28.11-.704.24-1.485.276-.843.038-1.096.047-3.232.047s-2.39-.009-3.233-.047c-.78-.036-1.203-.166-1.485-.276a2.5 2.5 0 0 1-.92-.598 2.5 2.5 0 0 1-.6-.92c-.109-.281-.24-.705-.275-1.485-.038-.843-.046-1.096-.046-3.233s.008-2.388.046-3.231c.036-.78.166-1.204.276-1.486.145-.373.319-.64.599-.92s.546-.453.92-.598c.282-.11.705-.24 1.485-.276.738-.034 1.024-.044 2.515-.045zm4.988 1.328a.96.96 0 1 0 0 1.92.96.96 0 0 0 0-1.92m-4.27 1.122a4.109 4.109 0 1 0 0 8.217 4.109 4.109 0 0 0 0-8.217m0 1.441a2.667 2.667 0 1 1 0 5.334 2.667 2.667 0 0 1 0-5.334" />
                    </svg>
                </a>
                <a href="https://www.linkedin.com/in/phambili-ma-africa-8256a4151" target="_blank"
                    aria-label="LinkedIn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                        class="bi bi-linkedin" viewBox="0 0 16 16">
                        <path
                            d="M0 1.146C0 .513.526 0 1.175 0h13.65C15.474 0 16 .513 16 1.146v13.708c0 .633-.526 1.146-1.175 1.146H1.175C.526 16 0 15.487 0 14.854zm4.943 12.248V6.169H2.542v7.225zm-1.2-8.212c.837 0 1.358-.554 1.358-1.248-.015-.709-.52-1.248-1.342-1.248S2.4 3.226 2.4 3.934c0 .694.521 1.248 1.327 1.248zm4.908 8.212V9.359c0-.216.016-.432.08-.586.173-.431.568-.878 1.232-.878.869 0 1.216.662 1.216 1.634v3.865h2.401V9.25c0-2.22-1.184-3.252-2.764-3.252-1.274 0-1.845.7-2.165 1.193v.025h-.016l.016-.025V6.169h-2.4c.03.678 0 7.225 0 7.225z" />
                    </svg>
                </a>
                <a href="https://www.tiktok.com/@user7297605414365" target="_blank" aria-label="TikTok">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                        class="bi bi-tiktok" viewBox="0 0 16 16">
                        <path
                            d="M9 0h1.98c.144.715.54 1.617 1.235 2.512C12.895 3.389 13.797 4 15 4v2c-1.753 0-3.07-.814-4-1.829V11a5 5 0 1 1-5-5v2a3 3 0 1 0 3 3z" />
                    </svg>
                </a>
            </div>
            <p class="copyright">&copy; <span id="current-year"></span> Phambili Ma-Africa. All rights reserved.</p>
        </div>
    </nav>

    <!-- Cart Sidebar (Right) -->
    <aside class="cart-sidebar" id="cart-sidebar">
        <div class="cart-header">
            <h3 class="cart-title">Your Shopping Cart</h3>
            <button class="close-cart-btn" id="close-cart-btn" aria-label="Close cart">
                <svg viewBox="0 0 24 24" width="30" height="30">
                    <path fill="currentColor"
                        d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                </svg>
            </button>
        </div>
        <div class="cart-items" id="cart-items">
            <div class="empty-cart">
                <svg viewBox="0 0 24 24" width="48" height="48">
                    <path fill="currentColor"
                        d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z" />
                </svg>
                <p>Your cart is empty</p>
            </div>
        </div>
        <div class="cart-footer">
            <div class="cart-summary">
                <div class="cart-subtotal">
                    <span>Subtotal</span>
                    <span class="cart-total" id="cart-total">R0.00</span>
                </div>
                <p class="cart-shipping">Shipping & taxes calculated at checkout</p>
            </div>
            <a href="#checkout.php" class="checkout-btn" id="checkout-btn">Proceed to Checkout</a>
            <div class="cart-payment-methods">
                <svg viewBox="0 0 38 24" width="38" height="24">
                    <path fill="#5A6988"
                        d="M35 0H3C1.3 0 0 1.3 0 3v18c0 1.7 1.4 3 3 3h32c1.7 0 3-1.3 3-3V3c0-1.7-1.4-3-3-3z" />
                    <path fill="#fff" d="M35 1c1.1 0 2 .9 2 2v18c0 1.1-.9 2-2 2H3c-1.1 0-2-.9-2-2V3c0-1.1.9-2 2-2h32" />
                    <path fill="#172B85"
                        d="M8.6 12c0-1.7 1.3-3 3-3h5.3c1.7 0 3 1.3 3 3s-1.3 3-3 3h-5.3c-1.7 0-3-1.3-3-3z" />
                    <path fill="#FF5F00" d="M30.9 16h-6.3v-8h6.3v8z" />
                    <path fill="#EB001B"
                        d="M25.1 12c0-2.2 1.1-4.1 2.8-5.2-1.2-1.1-2.8-1.8-4.5-1.8-3.1 0-5.7 2.5-5.7 5.7s2.5 5.7 5.7 5.7c1.7 0 3.3-.7 4.5-1.8-1.7-1.1-2.8-3-2.8-5.2z" />
                    <path fill="#F79E1B"
                        d="M37 12c0 2.2-1.1 4.1-2.8 5.2 1.2 1.1 2.8 1.8 4.5 1.8 3.1 0 5.7-2.5 5.7-5.7s-2.5-5.7-5.7-5.7c-1.7 0-3.3.7-4.5 1.8 1.7 1.1 2.8 3 2.8 5.2z" />
                </svg>
                <span>Secure Payment</span>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="booking-main">
        <!-- Booking Header Section -->
        <section class="booking-header">
            <h1>Book Your Professional Cleaning Service</h1>
            <p>Transform your space with our expert cleaning services. Get a free quote in just a few simple steps.</p>
        </section>

                    <!-- Success Message (shown by JS after submit) -->
                    <div class="booking-success" id="booking-success" style="display: none;">
                        <i class="fas fa-check-circle"></i>
                        <h3>Booking Request Submitted!</h3>
                        <p id="booking-success-text">Thank you for choosing Phambili Ma-Africa! We'll contact you within 24 hours to schedule your virtual consultation.</p>
                        <a href="booking.php" class="new-booking-btn">
                            <i class="fas fa-plus"></i> Book Another Service
                        </a>
                    </div>
                    <!-- Error Message -->
                    <div class="booking-error" id="booking-error" style="display: none;">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Booking Error</h3>
                        <p id="booking-error-text"></p>
                    </div>

                    <!-- Multi-Step Booking Form -->
                    <div class="multi-step-booking">
                        <!-- Progress Bar -->
                        <div class="progress-container">
                            <div class="progress-bar">
                                <div class="progress-fill" id="progress-fill"></div>
                            </div>
                            <div class="progress-steps" id="progress-steps">
                                <div class="progress-step active" data-step="1">
                                    <span class="step-number">1</span>
                                    <span class="step-title">Service</span>
                                </div>
                                <div class="progress-step" data-step="2">
                                    <span class="step-number">2</span>
                                    <span class="step-title">Schedule</span>
                                </div>
                                <div class="progress-step" data-step="3">
                                    <span class="step-number">3</span>
                                    <span class="step-title">Location</span>
                                </div>
                                <div class="progress-step" data-step="4">
                                    <span class="step-number">4</span>
                                    <span class="step-title">Contact</span>
                                </div>
                                <div class="progress-step" data-step="5">
                                    <span class="step-number">5</span>
                                    <span class="step-title">Review</span>
                                </div>
                            </div>
                        </div>

                        <!-- Welcome Message -->
                        <div class="welcome-message">
                            <div class="welcome-header">
                                <i class="fas fa-comments"></i>
                                <h3>Hi there,</h3>
                            </div>
                            <div class="welcome-content">
                                <p>Thank you for connecting through our online booking system!</p>
                                <p>After you have submitted this, you will receive confirmation where you will
                                    select a date & time for a virtual consultation to discuss your needs, view
                                    your space, provide you a quote, and get you booked in with us!</p>
                                <div class="signature">
                                    <p><strong>Thank you,</strong></p>
                                    <p class="company-name">Phambili Ma-Africa - Trading Enterprise</p>
                                </div>
                            </div>
                        </div>

                        <!-- Multi-Step Form -->
                        <form id="multi-step-form" class="multi-step-form">
                            <!-- Step 1: Service Details -->
                            <div class="form-step active" data-step="1">
                                <div class="step-header">
                                    <h3><i class="fas fa-concierge-bell"></i> Service Details</h3>
                                    <p>Choose your cleaning service and property type</p>
                                </div>
                                
                                <div class="form-group unified-group">
                                    <label for="service-type">
                                        <i class="fas fa-concierge-bell"></i>
                                        Service Type*
                                    </label>
                                    <select id="service-type" name="service-type" required class="unified-input">
                                        <option value="" disabled selected>Choose a cleaning service</option>
                                    </select>
                                    <small class="form-hint service-duration-hint">Select a service to see estimated duration</small>
                                    <span class="field-error" id="service-type-error"></span>
                                </div>

                                <div class="form-row unified-row">
                                    <div class="form-group unified-group">
                                        <label for="property-type">
                                            <i class="fas fa-home"></i>
                                            Property Type*
                                        </label>
                                        <select id="property-type" name="property-type" required class="unified-input">
                                            <option value="" disabled selected>Property type</option>
                                            <option value="house">House</option>
                                            <option value="apartment">Apartment</option>
                                            <option value="office">Office</option>
                                            <option value="commercial">Commercial Space</option>
                                        </select>
                                        <span class="field-error" id="property-type-error"></span>
                                    </div>

                                    <div class="form-group unified-group">
                                        <label for="cleaning-frequency">
                                            <i class="fas fa-calendar-week"></i>
                                            Service Frequency
                                        </label>
                                        <select id="cleaning-frequency" name="cleaning-frequency" class="unified-input">
                                            <option value="">Service frequency</option>
                                            <option value="once">One-time</option>
                                            <option value="weekly">Weekly</option>
                                            <option value="biweekly">Bi-weekly</option>
                                            <option value="monthly">Monthly</option>
                                        </select>
                                        <span class="field-error" id="cleaning-frequency-error"></span>
                                    </div>
                                </div>

                                <div class="step-actions">
                                    <button type="button" class="next-btn" onclick="nextStep(2)">
                                        <i class="fas fa-arrow-right"></i> Next Step
                                    </button>
                                </div>
                            </div>

                            <!-- Step 2: Schedule -->
                            <div class="form-step" data-step="2">
                                <div class="step-header">
                                    <h3><i class="fas fa-calendar-day"></i> Schedule</h3>
                                    <p>Select your preferred date and time</p>
                                </div>
                                
                                <div class="form-row unified-row">
                                    <div class="form-group unified-group">
                                        <label for="booking-date">
                                            <i class="fas fa-calendar-day"></i>
                                            Preferred Date*
                                        </label>
                                        <input type="date" id="booking-date" name="booking-date" required class="unified-input">
                                        <div id="calendar-container"></div>
                                        <div id="date-hint" class="date-hint" style="display: none;">
                                            <i class="fas fa-info-circle"></i>
                                            <span id="date-hint-text"></span>
                                        </div>
                                        <span class="field-error" id="booking-date-error"></span>
                                    </div>

                                    <div class="form-group unified-group">
                                        <label for="booking-time">
                                            <i class="fas fa-clock"></i>
                                            Preferred Time*
                                        </label>
                                        <select id="booking-time" name="booking-time" required class="unified-input">
                                            <option value="" disabled selected>Select time</option>
                                            <option value="08:00">08:00 AM</option>
                                            <option value="09:00">09:00 AM</option>
                                            <option value="10:00">10:00 AM</option>
                                            <option value="11:00">11:00 AM</option>
                                            <option value="12:00">12:00 PM</option>
                                            <option value="13:00">01:00 PM</option>
                                            <option value="14:00">02:00 PM</option>
                                            <option value="15:00">03:00 PM</option>
                                            <option value="16:00">04:00 PM</option>
                                        </select>
                                        <span class="field-error" id="booking-time-error"></span>
                                    </div>
                                </div>

                                <div class="step-actions">
                                    <button type="button" class="prev-btn" onclick="previousStep(1)">
                                        <i class="fas fa-arrow-left"></i> Previous
                                    </button>
                                    <button type="button" class="next-btn" onclick="nextStep(3)">
                                        <i class="fas fa-arrow-right"></i> Next Step
                                    </button>
                                </div>
                            </div>

                            <!-- Step 3: Location -->
                            <div class="form-step" data-step="3">
                                <div class="step-header">
                                    <h3><i class="fas fa-map-marker-alt"></i> Location</h3>
                                    <p>Enter your service address</p>
                                </div>
                                
                                <div class="form-group unified-group">
                                    <label for="address-street">
                                        <i class="fas fa-map-marker-alt"></i>
                                        Street Address*
                                    </label>
                                    <input type="text" id="address-street" name="address-street"
                                        placeholder="Enter your complete street address" required class="unified-input"
                                        value="">
                                    <span class="field-error" id="address-street-error"></span>
                                </div>

                                <div class="form-row unified-row">
                                    <div class="form-group unified-group">
                                        <label for="address-city">
                                            <i class="fas fa-city"></i>
                                            City*
                                        </label>
                                        <input type="text" id="address-city" name="address-city" placeholder="City" required
                                        class="unified-input"
                                        value="">
                                        <span class="field-error" id="address-city-error"></span>
                                    </div>

                                    <div class="form-group unified-group">
                                        <label for="address-state">
                                            <i class="fas fa-map"></i>
                                            State*
                                        </label>
                                        <input type="text" id="address-state" name="address-state" placeholder="State" required
                                        class="unified-input"
                                        value="">
                                        <span class="field-error" id="address-state-error"></span>
                                    </div>

                                    <div class="form-group unified-group">
                                        <label for="address-postal-code">
                                            <i class="fas fa-mail-bulk"></i>
                                            Postal Code*
                                        </label>
                                        <input type="text" id="address-postal-code" name="address-postal-code"
                                        placeholder="1234" required class="unified-input"
                                        value="">
                                        <div class="postal-format-hint">4 digits only</div>
                                        <span class="field-error" id="address-postal-code-error"></span>
                                    </div>
                                </div>

                                <div class="step-actions">
                                    <button type="button" class="prev-btn" onclick="previousStep(2)">
                                        <i class="fas fa-arrow-left"></i> Previous
                                    </button>
                                    <button type="button" class="next-btn" onclick="nextStep(4)">
                                        <i class="fas fa-arrow-right"></i> Next Step
                                    </button>
                                </div>
                            </div>

                            <!-- Step 4: Contact Information -->
                            <div class="form-step" data-step="4">
                                <div class="step-header">
                                    <h3><i class="fas fa-user"></i> Contact Information</h3>
                                    <p>Your personal details</p>
                                </div>
                                
                                <div class="form-row unified-row">
                                    <div class="form-group unified-group">
                                        <label for="customer-name">
                                            <i class="fas fa-user"></i>
                                            Full Name*
                                        </label>
                                        <input type="text" id="customer-name" name="customer-name" placeholder="Your full name"
                                            required class="unified-input"
                                            value="">
                                        <div class="validation-hint">Only letters, spaces, hyphens, and apostrophes allowed</div>
                                        <span class="field-error" id="customer-name-error"></span>
                                    </div>

                                    <div class="form-group unified-group">
                                        <label for="customer-email">
                                            <i class="fas fa-envelope"></i>
                                            Email Address*
                                        </label>
                                        <input type="email" id="customer-email" name="customer-email"
                                            placeholder="your.email@example.com" required class="unified-input"
                                            value="">
                                        <span class="field-error" id="customer-email-error"></span>
                                    </div>

                                    <div class="form-group unified-group">
                                        <label for="customer-phone">
                                            <i class="fas fa-phone"></i>
                                            Phone Number*
                                        </label>
                                        <input type="tel" id="customer-phone" name="customer-phone" placeholder="012 345 6789"
                                            required class="unified-input"
                                            value="">
                                        <div class="phone-format-hint">10 digits (South African format)</div>
                                        <span class="field-error" id="customer-phone-error"></span>
                                    </div>
                                </div>

                                <div class="step-actions">
                                    <button type="button" class="prev-btn" onclick="previousStep(3)">
                                        <i class="fas fa-arrow-left"></i> Previous
                                    </button>
                                    <button type="button" class="next-btn" onclick="nextStep(5)">
                                        <i class="fas fa-arrow-right"></i> Next Step
                                    </button>
                                </div>
                            </div>

                            <!-- Step 5: Review & Submit -->
                            <div class="form-step" data-step="5">
                                <div class="step-header">
                                    <h3><i class="fas fa-check-circle"></i> Review & Submit</h3>
                                    <p>Please review your booking details</p>
                                </div>
                                
                                <div class="review-summary" id="review-summary">
                                    <!-- Summary will be populated by JavaScript -->
                                </div>

                                <div class="form-group unified-group">
                                    <label for="special-requests">
                                        <i class="fas fa-edit"></i>
                                        Special Instructions
                                    </label>
                                    <textarea id="special-requests" name="special-requests"
                                        placeholder="Any specific requirements, access instructions, areas of focus, or additional information that will help us provide best service..."
                                        class="unified-textarea"></textarea>
                                </div>

                                <div class="step-actions">
                                    <button type="button" class="prev-btn" onclick="previousStep(4)">
                                        <i class="fas fa-arrow-left"></i> Previous
                                    </button>
                                    <button type="submit" class="submit-btn unified-submit" id="submit-btn">
                                        <i class="fas fa-paper-plane"></i>
                                        <span class="btn-text">Submit Booking Request</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="why-choose">
                        <h2>Why Choose Phambili Ma-Africa?</h2>
                        <div class="benefits-grid">
                            <div class="benefit">
                                <i class="fas fa-shield-alt"></i>
                                <h3>Fully Insured & Certified</h3>
                                <p>Our cleaning professionals are fully insured, certified, and background-checked for your peace of mind.</p>
                            </div>
                            <div class="benefit">
                                <i class="fas fa-leaf"></i>
                                <h3>Eco-Friendly Products</h3>
                                <p>We use environmentally friendly cleaning products that are safe for your family and pets.</p>
                            </div>
                            <div class="benefit">
                                <i class="fas fa-star"></i>
                                <h3>Satisfaction Guarantee</h3>
                                <p>100% satisfaction guaranteed. If you're not happy, we'll come back and make it right.</p>
                            </div>
                        </div>
                    </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="footer-container">
            <!-- Company Info -->
            <div class="footer-section company-info">
                <div class="footer-logo">
                    <a href="index.php">
                        <img src="images/PM-LogoB.png" alt="Phambili Ma-Africa Logo" loading="lazy">
                    </a>
                </div>
                
                <p class="footer-motto">Making spaces shine since 2019</p>

                <div class="social-links">
                    <a href="https://www.facebook.com/people/Phambili-Ma-Africa/100063507009282/" target="_blank" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.instagram.com/phambilimaafrica" target="_blank" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="https://www.linkedin.com/in/phambili-ma-africa-8256a4151" target="_blank" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    <a href="https://www.tiktok.com/@user7297605414365" target="_blank" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="footer-section quick-links">
                <h3 class="footer-heading">Quick Links</h3>
                <ul class="quick-links-list">
                    <li><a href="index.php"><i class="fas fa-chevron-right"></i> Home</a></li>
                    <li><a href="about.php"><i class="fas fa-chevron-right"></i> About Us</a></li>
                    <li><a href="contact.php"><i class="fas fa-chevron-right"></i> Contact</a></li>
                    <li><a href="services.php"><i class="fas fa-chevron-right"></i> Services</a></li>
                    <li><a href="products.php"><i class="fas fa-chevron-right"></i> Products</a></li>
                </ul>
            </div>

            <!-- Services -->
            <div class="footer-section services">
                <h3 class="footer-heading">Our Services</h3>
                <div class="footer-services-grid">
                    <ul>
                        <li><a href="services.php"> Standard & Deep Cleaning</a></li>
                        <li><a href="services.php"> Office Cleaning</a></li>
                        <li><a href="services.php"> Window Cleaning</a></li>
                        <li><a href="services.php"> Carpet Cleaning</a></li>
                    </ul>
                    <ul>
                        <li><a href="products.php"> Cleaning Products</a></li>
                        <li><a href="services.php"> Pest Control</a></li>
                        <li><a href="services.php"> Fumigation</a></li>
                        <li><a href="services.php"> Disinfecting</a></li>
                    </ul>
                </div>
            </div>

            <!-- Contact Info -->
            <div class="footer-section contact-info">
                <h3 class="footer-heading">Contact Us</h3>
                <ul class="contact-details">
                    <li> 2676 Isikhwama Street, Phiri Extension, Phiri, Soweto, 1818</li>
                    <li><a href="tel:+27726625951">+27 72 662 5951</a></li>
                    <li><a href="tel:+27609707444">+27 60 970 7444</a></li>
                    <li><a href="mailto:info@phambilimaafrica.co.za">info@phambilimaafrica.co.za</a></li>
                    <li> Open 24 hours</li>
                </ul>
            </div>
        </div>

        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <div class="footer-bottom-container">
                <p>&copy; 2023 Phambili Ma-Africa Cleaning Services. All rights reserved. Reg. No: 2023/123456/07</p>
                <div class="legal-links">
                    <a href="privacy.php">Privacy Policy</a>
                    <a href="terms.php">Terms of Service</a>
                </div>
            </div>
        </div>

        <!-- Back to Top Button -->
        <button class="back-to-top" aria-label="Back to top">
            <i class="fas fa-arrow-up"></i>
        </button>
    </footer>

    <script src="./js/script.js"></script>
    <script src="./js/booking-calendar.js"></script>
    <script src="./js/validation.js"></script>
<script>
// Multi-Step Form JavaScript
let currentStep = 1;
const totalSteps = 5;

function updateProgress() {
    const progressFill = document.getElementById('progress-fill');
    const progressSteps = document.querySelectorAll('.progress-step');
    
    // Update progress bar
    const progressPercentage = ((currentStep - 1) / (totalSteps - 1)) * 100;
    if (progressFill) {
        progressFill.style.width = progressPercentage + '%';
    }
    
    // Update step indicators
    progressSteps.forEach((step, index) => {
        const stepNumber = index + 1;
        if (stepNumber <= currentStep) {
            step.classList.add('active');
            if (stepNumber < currentStep) {
                step.classList.add('completed');
            } else {
                step.classList.remove('completed');
            }
        } else {
            step.classList.remove('active', 'completed');
        }
    });
}

function showStep(stepNumber) {
    const formSteps = document.querySelectorAll('.form-step');
    
    formSteps.forEach((step, index) => {
        if (index + 1 === stepNumber) {
            step.classList.add('active');
            step.style.display = 'block';
        } else {
            step.classList.remove('active');
            step.style.display = 'none';
        }
    });
    
    currentStep = stepNumber;
    updateProgress();
    updateReviewSummary();
}

function nextStep(stepNumber) {
    if (validateCurrentStep()) {
        showStep(stepNumber);
        // Smooth scroll to top of form
        document.querySelector('.multi-step-booking').scrollIntoView({ 
            behavior: 'smooth', 
            block: 'start' 
        });
    }
}

function previousStep(stepNumber) {
    showStep(stepNumber);
    // Smooth scroll to top of form
    document.querySelector('.multi-step-booking').scrollIntoView({ 
        behavior: 'smooth', 
        block: 'start' 
    });
}

// Show field error message
function showFieldError(fieldId, message) {
    const field = document.getElementById(fieldId);
    const errorElement = document.getElementById(`${fieldId}-error`);
    if (field) field.classList.add('error');
    if (errorElement) errorElement.textContent = message;
}

// Clear field error message
function clearFieldError(fieldId) {
    const field = document.getElementById(fieldId);
    const errorElement = document.getElementById(`${fieldId}-error`);
    if (field) field.classList.remove('error');
    if (errorElement) errorElement.textContent = '';
}

// Validate email format
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Validate phone number (SA format)
function validatePhone(phone) {
    const phoneRegex = /^0\d{9}$/;
    return phoneRegex.test(phone.replace(/\s/g, ''));
}

// Validate postal code (4 digits)
function validatePostal(code) {
    const postalRegex = /^\d{4}$/;
    return postalRegex.test(code);
}

// Real-time field validation
function validateField(fieldId) {
    const field = document.getElementById(fieldId);
    if (!field) return true;
    
    const value = field.value.trim();
    let isValid = true;
    let errorMessage = '';
    
    // Required field check
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        errorMessage = 'This field is required';
    } else if (value) {
        // Field-specific validation
        switch(fieldId) {
            case 'customer-email':
                if (!validateEmail(value)) {
                    isValid = false;
                    errorMessage = 'Please enter a valid email address';
                }
                break;
            case 'customer-phone':
                if (!validatePhone(value)) {
                    isValid = false;
                    errorMessage = 'Enter a valid 10-digit SA phone number starting with 0';
                }
                break;
            case 'address-postal-code':
                if (!validatePostal(value)) {
                    isValid = false;
                    errorMessage = 'Postal code must be exactly 4 digits';
                }
                break;
            case 'booking-date':
                const selectedDate = new Date(value);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                if (selectedDate < today) {
                    isValid = false;
                    errorMessage = 'Booking date cannot be in the past';
                }
                break;
        }
    }
    
    if (isValid) {
        clearFieldError(fieldId);
    } else {
        showFieldError(fieldId, errorMessage);
    }
    
    return isValid;
}

// Validate all fields in current step
function validateCurrentStep() {
    const currentStepElement = document.querySelector(`.form-step[data-step="${currentStep}"]`);
    const requiredFields = currentStepElement.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!validateField(field.id)) {
            isValid = false;
        }
    });
    
    // Additional validation for specific steps
    if (currentStep === 3) {
        if (!validateField('address-postal-code')) isValid = false;
    }
    
    if (currentStep === 4) {
        if (!validateField('customer-email')) isValid = false;
        if (!validateField('customer-phone')) isValid = false;
    }
    
    // Show general error if validation failed
    if (!isValid) {
        const existingError = currentStepElement.querySelector('.step-error');
        if (!existingError) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'step-error';
            errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Please correct the errors below before proceeding.';
            currentStepElement.insertBefore(errorDiv, currentStepElement.firstChild);
            
            setTimeout(() => {
                errorDiv.remove();
            }, 5000);
        }
    }
    
    return isValid;
}

function updateReviewSummary() {
    if (currentStep !== 5) return;
    
    const reviewSummary = document.getElementById('review-summary');
    
    const serviceType = document.getElementById('service-type');
    const propertyType = document.getElementById('property-type');
    const cleaningFrequency = document.getElementById('cleaning-frequency');
    const bookingDate = document.getElementById('booking-date');
    const bookingTime = document.getElementById('booking-time');
    const addressStreet = document.getElementById('address-street');
    const addressCity = document.getElementById('address-city');
    const addressState = document.getElementById('address-state');
    const addressPostal = document.getElementById('address-postal-code');
    const customerName = document.getElementById('customer-name');
    const customerEmail = document.getElementById('customer-email');
    const customerPhone = document.getElementById('customer-phone');
    const specialRequests = document.getElementById('special-requests');
    
    const serviceText = serviceType.options[serviceType.selectedIndex]?.text || 'Not selected';
    const propertyText = propertyType.options[propertyType.selectedIndex]?.text || 'Not selected';
    const frequencyText = cleaningFrequency.options[cleaningFrequency.selectedIndex]?.text || 'Not selected';
    const timeText = bookingTime.options[bookingTime.selectedIndex]?.text || 'Not selected';
    
    const fullAddress = `${addressStreet.value || 'Not provided'}, ${addressCity.value || 'Not provided'}, ${addressState.value || 'Not provided'} ${addressPostal.value || 'Not provided'}`;
    
    reviewSummary.innerHTML = `
        <div class="review-section">
            <h4><i class="fas fa-concierge-bell"></i> Service Details</h4>
            <div class="review-item">
                <span class="review-label">Service:</span>
                <span class="review-value">${serviceText}</span>
            </div>
            <div class="review-item">
                <span class="review-label">Property Type:</span>
                <span class="review-value">${propertyText}</span>
            </div>
            <div class="review-item">
                <span class="review-label">Frequency:</span>
                <span class="review-value">${frequencyText}</span>
            </div>
        </div>
        
        <div class="review-section">
            <h4><i class="fas fa-calendar-day"></i> Schedule</h4>
            <div class="review-item">
                <span class="review-label">Date:</span>
                <span class="review-value">${bookingDate.value || 'Not selected'}</span>
            </div>
            <div class="review-item">
                <span class="review-label">Time:</span>
                <span class="review-value">${timeText}</span>
            </div>
        </div>
        
        <div class="review-section">
            <h4><i class="fas fa-map-marker-alt"></i> Location</h4>
            <div class="review-item">
                <span class="review-label">Address:</span>
                <span class="review-value">${fullAddress}</span>
            </div>
        </div>
        
        <div class="review-section">
            <h4><i class="fas fa-user"></i> Contact Information</h4>
            <div class="review-item">
                <span class="review-label">Name:</span>
                <span class="review-value">${customerName.value || 'Not provided'}</span>
            </div>
            <div class="review-item">
                <span class="review-label">Email:</span>
                <span class="review-value">${customerEmail.value || 'Not provided'}</span>
            </div>
            <div class="review-item">
                <span class="review-label">Phone:</span>
                <span class="review-value">${customerPhone.value || 'Not provided'}</span>
            </div>
        </div>
        
        ${specialRequests.value ? `
        <div class="review-section">
            <h4><i class="fas fa-edit"></i> Special Instructions</h4>
            <div class="review-item">
                <span class="review-value">${specialRequests.value}</span>
            </div>
        </div>
        ` : ''}
    `;
}

// Initialize form on page load
document.addEventListener('DOMContentLoaded', function() {
    // Hide all steps except the first one
    const formSteps = document.querySelectorAll('.form-step');
    formSteps.forEach((step, index) => {
        if (index === 0) {
            step.style.display = 'block';
        } else {
            step.style.display = 'none';
        }
    });
    
    // Initialize progress
    updateProgress();
    
    // Add real-time validation on blur (when user leaves field)
    const inputs = document.querySelectorAll('.unified-input, .unified-textarea');
    inputs.forEach(input => {
        // Validate on blur (when user leaves the field)
        input.addEventListener('blur', function() {
            validateField(this.id);
        });
        
        // Clear error as soon as user starts typing
        input.addEventListener('input', function() {
            if (this.classList.contains('error')) {
                validateField(this.id);
            }
        });
    });
    
    // Phone number input filtering - only allow digits, max 10 chars
    const phoneInput = document.getElementById('customer-phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            // Remove all non-digit characters
            let value = this.value.replace(/\D/g, '');
            // Limit to 10 digits
            if (value.length > 10) {
                value = value.substring(0, 10);
            }
            this.value = value;
            
            // Validate immediately
            if (value.length === 10) {
                if (!validatePhone(value)) {
                    showFieldError('customer-phone', 'Phone must start with 0 followed by 9 digits');
                } else {
                    clearFieldError('customer-phone');
                }
            }
        });
        
        // Prevent paste of non-digits
        phoneInput.addEventListener('paste', function(e) {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData).getData('text');
            const digitsOnly = pasted.replace(/\D/g, '').substring(0, 10);
            this.value = digitsOnly;
        });
    }
    
    // Date validation for 12pm restriction
    const dateInput = document.getElementById('booking-date');
    const timeInput = document.getElementById('booking-time');
    
    function checkSameDayBooking() {
        const selectedDate = dateInput ? dateInput.value : '';
        if (!selectedDate) return;
        
        const today = new Date().toISOString().split('T')[0];
        const currentHour = new Date().getHours();
        
        if (selectedDate === today && currentHour >= 12) {
            // Show immediate error
            showFieldError('booking-date', 'Same-day bookings must be made before 12:00 PM. Please select tomorrow or later.');
            // Clear the date
            dateInput.value = '';
            // Show alert
            alert('Same-day bookings must be made before 12:00 PM. Please select tomorrow or later.');
        }
    }
    
    if (dateInput) {
        dateInput.addEventListener('change', checkSameDayBooking);
        dateInput.addEventListener('blur', checkSameDayBooking);
    }
    
    // Duplicate booking check when date changes
    if (dateInput && typeof serviceType !== 'undefined') {
        dateInput.addEventListener('change', function() {
            checkDuplicateBooking();
        });
    }
});

// Check for duplicate booking — implemented in the page's module
// script (pmaCheckDuplicateBooking) which queries Firestore directly.
function checkDuplicateBooking() {
    const serviceId = document.getElementById('service-type')?.value;
    const date = document.getElementById('booking-date')?.value;
    if (!serviceId || !date || !window.pmaCheckDuplicateBooking) return;
    window.pmaCheckDuplicateBooking(serviceId, date).then((dup) => {
        if (dup) {
            showFieldError('booking-date', 'You have already booked this service for ' + date + '. Please select a different date or service.');
            alert('You have already booked this service for ' + date + '. Please select a different date or service.');
        }
    }).catch((e) => console.error('Error checking duplicate:', e));
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .form-step {
        display: none;
        animation: fadeIn 0.5s ease;
    }
    
    .form-step.active {
        display: block;
    }
    
    .unified-input.error,
    .unified-textarea.error {
        border-color: #e74c3c !important;
        box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1) !important;
    }
    
    .field-error {
        display: block;
        color: #e74c3c;
        font-size: 0.85rem;
        margin-top: 5px;
        min-height: 18px;
        font-weight: 500;
    }
    
    .step-error {
        background: #fee;
        border: 1px solid #fcc;
        color: #c33;
        padding: 12px 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        animation: slideDown 0.3s ease;
        font-size: 0.95rem;
    }
    
    .review-section {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 15px;
        border: 1px solid #e9ecef;
    }
    
    .review-section h4 {
        color: #2c3e50;
        margin-bottom: 15px;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .review-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #e9ecef;
    }
    
    .review-item:last-child {
        border-bottom: none;
    }
    
    .review-label {
        font-weight: 600;
        color: #7f8c8d;
        min-width: 120px;
    }
    
    .review-value {
        color: #2c3e50;
        text-align: right;
        flex: 1;
    }
`;
document.head.appendChild(style);
</script>
    <script src="./js/config.js"></script>
    <script type="module" src="./js/firebase/ui.js"></script>
    <script type="module" src="./js/firebase/notifications.js"></script>
    <script type="module">
        import { watchServices, createRequest, hasDuplicateRequest } from './js/firebase/data.js';
        import { requireAuth } from './js/firebase/auth.js';

        const form = document.getElementById('multi-step-form');
        const successBox = document.getElementById('booking-success');
        const errorBox = document.getElementById('booking-error');
        const serviceSelect = document.getElementById('service-type');
        const dateInput = document.getElementById('booking-date');

        const authed = await requireAuth('booking.php');
        if (!authed) throw new Error('redirected to login');
        const { user, profile } = authed;

        // Pre-fill contact + address fields from the Firestore profile.
        if (profile) {
            const addr = profile.address || {};
            const fill = (id, v) => { const el = document.getElementById(id); if (el && v) el.value = v; };
            fill('customer-name', `${profile.firstName || ''} ${profile.lastName || ''}`.trim());
            fill('customer-email', profile.email || user.email);
            fill('customer-phone', profile.phone);
            if (typeof addr === 'object') {
                fill('address-street', addr.street);
                fill('address-city', addr.city);
                fill('address-state', addr.state);
                fill('address-postal-code', addr.postal);
            } else {
                fill('address-street', addr);
            }
        }

        // Date bounds: today → +90 days.
        const today = new Date();
        const max = new Date(Date.now() + 90 * 864e5);
        dateInput.min = today.toISOString().split('T')[0];
        dateInput.max = max.toISOString().split('T')[0];

        // Populate the service dropdown from Firestore.
        let services = [];
        watchServices((list) => {
            services = list.filter((s) => s.Is_Available);
            const current = serviceSelect.value;
            serviceSelect.innerHTML = '<option value="" disabled selected>Choose a cleaning service</option>' +
                services.map((s) =>
                    `<option value="${s.ID}">${s.Name}${s.Duration ? ` (${s.Duration} min)` : ''}</option>`
                ).join('');
            if (current) serviceSelect.value = current;
            // Preselect via ?service=<id>
            const pre = new URLSearchParams(location.search).get('service');
            if (pre && services.some((s) => s.ID === pre)) serviceSelect.value = pre;
        });

        // Exposed to the page's existing checkDuplicateBooking() helper.
        window.pmaCheckDuplicateBooking = (serviceId, date) =>
            hasDuplicateRequest(user.uid, serviceId, date);

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('submit-btn');
            btn.disabled = true;
            errorBox.style.display = 'none';
            try {
                const v = (id) => document.getElementById(id)?.value.trim() || '';
                const svc = services.find((s) => s.ID === v('service-type')) || {};
                const street = v('address-street'), city = v('address-city'),
                      state = v('address-state'), postal = v('address-postal-code');
                const req = await createRequest(profile, {
                    serviceId: svc.ID,
                    serviceName: svc.Name || '',
                    customerName: v('customer-name'),
                    customerPhone: v('customer-phone'),
                    customerEmail: v('customer-email'),
                    requestedDate: v('booking-date'),
                    requestedTime: v('booking-time'),
                    siteAddress: [street, city, state, postal].filter(Boolean).join(', '),
                    addressStreet: street, addressCity: city, addressState: state, addressPostal: postal,
                    propertyType: v('property-type'),
                    cleaningFrequency: v('cleaning-frequency'),
                    specialInstructions: v('special-requests'),
                    quoteRequested: true,
                });
                document.getElementById('booking-success-text').textContent =
                    `Thank you for choosing Phambili Ma-Africa! Your reference is ${req.refNumber}. We'll contact you within 24 hours to schedule your virtual consultation.`;
                form.closest('.multi-step-booking').style.display = 'none';
                document.querySelector('.why-choose').style.display = 'none';
                successBox.style.display = '';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } catch (err) {
                console.error(err);
                document.getElementById('booking-error-text').textContent =
                    'Something went wrong submitting your booking. Please try again.';
                errorBox.style.display = '';
                errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } finally {
                btn.disabled = false;
            }
        });
    </script>
</body>
</html>
