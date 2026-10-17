<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>About | Phambili Ma-Africa</title>
    <link rel="icon" type="image/png" sizes="32x32" href="images/PM-Logo.jpg">
    
    <!-- Open Graph Meta Tags for Social Media Preview -->
    <meta property="og:title" content="About | Phambili Ma-Africa">
    <meta property="og:description" content="Learn more about Phambili Ma-Africa - Professional cleaning services for your home and business.">
    <meta property="og:image" content="images/PM-Logo.jpg">
    <meta property="og:url" content="https://phambili-ma-africa.com/about.php">
    <meta property="og:type" content="website">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="About | Phambili Ma-Africa">
    <meta name="twitter:description" content="Learn more about Phambili Ma-Africa - Professional cleaning services for your home and business.">
    <meta name="twitter:image" content="images/PM-Logo.jpg">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./css/style.css">
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
            <!-- In your navigation menu -->
            <ul class="nav-list">
                <li class="nav-item"><a href="index.php" class="nav-link">Home</a></li>
                <li class="nav-item"><a href="about.php" class="nav-link"> About</a></li>
                <li class="nav-item"><a href="contact.php" class="nav-link">Contact</a></li>
                <li class="nav-item"><a href="services.php" class="nav-link"> Services</a></li>
                <li class="nav-item"><a href="products.php" class="nav-link"> Products</a></li>

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
                    <a href="booking.php" class="nav-link book-now-btn ">
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
                                d="M0 1.146C0 .513.526 0 1.175 0h13.65C15.474 0 16 .513 16 1.146v13.708c0 .633-.526 1.146-1.175 1.146H1.175C.526 16 0 15.487 0 14.854V1.146zm4.943 12.248V6.169H2.542v7.225zm-1.2-8.212c.837 0 1.358-.554 1.358-1.248-.015-.709-.52-1.248-1.342-1.248S2.4 3.226 2.4 3.934c0 .694.521 1.248 1.327 1.248zm4.908 8.212V9.359c0-.216.016-.432.08-.586.173-.431.568-.878 1.232-.878.869 0 1.216.662 1.216 1.634v3.865h2.401V9.25c0-2.22-1.184-3.252-2.764-3.252-1.274 0-1.845.7-2.165 1.193v.025h-.016l.016-.025V6.169h-2.4c.03.678 0 7.225 0 7.225z" />
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
        <main class="about-main">
            <!-- About Header Section -->
            <section class="about-header">
                <h1>About Phambili Ma-Africa</h1>
                <p>Discover our story, mission, and commitment to excellence in professional cleaning services</p>
            </section>

            <!-- About Section -->
            <section class="about-section">
                <div class="container">
                    <div class="about-content">
                        <!-- Left-Side: Image + Highlights + Quote + CTAs -->
                        <div class="about-left-column">
                            <div class="about-image">
                                <img src="./images/about.jpg" alt="Business Owner of Phambili Ma-Africa">
                                <div class="image-overlay">
                                    <div class="overlay-content">
                                        <i class="fas fa-quote-left"></i>
                                        <p>"Building communities through quality service"</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Info Bar under picture - Horizontal layout -->
                            <div class="scattered-highlights">
                                <!-- Highlights under picture -->
                                <div class="highlight-card trusted">
                                    <i class="fa fa-check-circle"></i>
                                    <span>Trusted</span>
                                </div>
                                <div class="highlight-card eco">
                                    <i class="fa fa-leaf"></i>
                                    <span>Eco-Friendly</span>
                                </div>
                                <div class="highlight-card experienced">
                                    <i class="fa fa-users"></i>
                                    <span>Experienced Staff</span>
                                </div>
                                <div class="highlight-card insured">
                                    <i class="fa fa-shield-alt"></i>
                                    <span>Insured</span>
                                </div>
                            </div>
                        </div>

                        <!-- Right-Side Message -->
                        <div class="about-message">
                            <h1>Meet the Owner & Founder</h1>
                            <h3>Nkosinathi Ngema</h3>
                            <p class="date-author">
                                <i class="fas fa-calendar"></i> 13th November 2019 
                                <span class="separator">|</span>
                                <i class="fas fa-user"></i> phambilimaafrica
                            </p>

                            <div class="story-content">
                                <p><strong>Phambili Ma-Africa Trading Enterprise</strong> is the result of vision, creativity, and the
                                    passion of young entrepreneurs. Established in 2007, we have built a strong reputation for 
                                    quality and efficiency, consistently delivering beyond our customers' expectations.</p>

                                <p>Welcome to Phambili Ma-Africa! We believe in contributing to the growth of a more equitable South
                                    African economy. That's why we encourage you to consider supporting a township business like ours.</p>

                                <p>We're not just any business; we're a product of the township-born and raised in the heart of it all.
                                    What sets us apart is our unwavering commitment to our work. We're driven by a hunger to build 
                                    and expand, yet we remain small enough to offer a personal touch to everything we do.</p>
                            </div>

                            <!-- Mission statement under paragraph -->
                            <blockquote class="about-quote scattered-quote">
                                <i class="fas fa-quote-left"></i>
                                "Our mission is to deliver spotless results with a personal touch, every time."
                                <i class="fas fa-quote-right"></i>
                            </blockquote>

                            <!-- CTA buttons under paragraph -->
                            <div class="cta-buttons scattered-cta">
                                <a href="contact.php" class="about-cta-btn primary">
                                    <i class="fas fa-phone"></i>
                                    Get in touch
                                </a>
                                <a href="services.php" class="about-cta-btn secondary">
                                    <i class="fas fa-broom"></i>
                                    Our Services
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Mission Statement -->
                    <div class="mission-section">
                        <div class="mission-content">
                            <h2>Our Mission & Values</h2>
                            <div class="mission-grid">
                                <div class="mission-item">
                                    <div class="mission-icon">
                                        <i class="fas fa-bullseye"></i>
                                    </div>
                                    <h3>Excellence</h3>
                                    <p>We strive for perfection in every cleaning task, ensuring your space is spotless and welcoming.</p>
                                </div>
                                <div class="mission-item">
                                    <div class="mission-icon">
                                        <i class="fas fa-heart"></i>
                                    </div>
                                    <h3>Community</h3>
                                    <p>We're committed to supporting our local community and contributing to economic growth in Soweto.</p>
                                </div>
                                <div class="mission-item">
                                    <div class="mission-icon">
                                        <i class="fas fa-leaf"></i>
                                    </div>
                                    <h3>Sustainability</h3>
                                    <p>We use eco-friendly products and sustainable practices to protect our environment and your health.</p>
                                </div>
                                <div class="mission-item">
                                    <div class="mission-icon">
                                        <i class="fas fa-handshake"></i>
                                    </div>
                                    <h3>Integrity</h3>
                                    <p>We operate with honesty, transparency, and respect for our clients and community.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Company Stats -->
                    <div class="stats-section">
                        <h2>Our Achievements</h2>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-home"></i>
                                </div>
                                <div class="stat-info">
                                    <span class="stat-number">1000+</span>
                                    <span class="stat-label">Properties Cleaned</span>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stat-info">
                                    <span class="stat-number">25+</span>
                                    <span class="stat-label">Professional Staff</span>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-star"></i>
                                </div>
                                <div class="stat-info">
                                    <span class="stat-number">4.9</span>
                                    <span class="stat-label">Average Rating</span>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-award"></i>
                                </div>
                                <div class="stat-info">
                                    <span class="stat-number">15+</span>
                                    <span class="stat-label">Years in Business</span>
                                </div>
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
                <p>RELM TECHNOLOGIES DEVELOPERS</p>
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
        <script src="./js/config.js"></script>
    <script type="module" src="./js/firebase/ui.js"></script>
    <script type="module" src="./js/firebase/notifications.js"></script>
</html>
