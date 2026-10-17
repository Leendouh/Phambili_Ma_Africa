<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Bookings | Phambili Ma-Africa</title>
    <link rel="icon" type="image/png" sizes="32x32" href="images/PM-Logo.jpg">

    <!-- Open Graph Meta Tags for Social Media Preview -->
    <meta property="og:title" content="My Bookings | Phambili Ma-Africa">
    <meta property="og:description" content="View and manage your cleaning service bookings with Phambili Ma-Africa.">
    <meta property="og:image" content="images/PM-Logo.jpg">
    <meta property="og:url" content="https://phambili-ma-africa.com/bookings.php">
    <meta property="og:type" content="website">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="My Bookings | Phambili Ma-Africa">
    <meta name="twitter:description" content="View and manage your cleaning service bookings with Phambili Ma-Africa.">
    <meta name="twitter:image" content="images/PM-Logo.jpg">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./css/style.css">
    <link rel="stylesheet" href="./css/responsiveness.css">
    <link rel="stylesheet" href="./css/profile.css">
    <link rel="stylesheet" href="./css/social-media.css">
    <style>
        .bookings-page {
            background: linear-gradient(135deg, #f8fafc 0%, #e6f7ff 100%);
        }
    </style>
</head>

<body class="bookings-page"">
    <header class="header"><button class="hamburger-btn" id="hamburger-btn" aria-label="Toggle menu"><img src="images/menu.png" alt="Close"></button>
        <div class="logo"><a href="index.php"><img src="images/PM-LogoB.png" alt="Phambili Ma-Africa Logo"></a></div>
        <div class="header-icons"><div class="notification-bell-customer" data-auth="role:customer">
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
                </div><div class="user-profile-dropdown" id="user-profile-dropdown" data-auth="in">
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
                </a><button class="cart-btn" id="cart-btn" aria-label="Shopping Cart"><img src="images/shopping-bag.png" alt="" class="icon"><span class="cart-badge">0</span></button></div>
    </header>
    <div class="menu-overlay" id="menu-overlay"></div>
    <nav class="main-nav" id="main-nav">
        <div class="nav-header">
            <div class="nav-logo"><img src="images/PM-LogoB.png" alt="Phambili Ma-Africa Logo"></div><button class="close-nav-btn" id="close-nav-btn" aria-label="Close menu"><svg viewBox="0 0 24 24" width="30" height="30">
                    <path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                </svg></button>
        </div>
        <ul class="nav-list">
            <li class="nav-item"><a href="index.php" class="nav-link">Home</a></li>
            <li class="nav-item"><a href="about.php" class="nav-link">About</a></li>
            <li class="nav-item"><a href="contact.php" class="nav-link">Contact</a></li>
            <li class="nav-item"><a href="services.php" class="nav-link">Services</a></li>
            <li class="nav-item"><a href="products.php" class="nav-link">Products</a></li>
            <li class="nav-item" data-auth="role:staff,admin,main_admin,super_admin"><a href="#" class="nav-link admin-nav-link admin-app-link">Admin Dashboard</a></li>
            <li class="nav-item" data-auth="role:staff,admin,main_admin,super_admin"><a href="#" class="nav-link" data-logout>Log Out</a></li>
            <li class="nav-item" data-auth="role:customer"><a href="bookings.php" class="nav-link">Bookings</a></li>
            <li class="nav-item" data-auth="role:customer"><a href="profile.php" class="nav-link">Profile</a></li>
            <li class="nav-item" data-auth="role:customer"><a href="#" class="nav-link" data-logout>Log Out</a></li>
            <li class="nav-item" data-auth="out"><a href="login.php" class="nav-link">Log In</a></li>
            <li class="nav-item"><a href="booking.php" class="nav-link book-now-btn">Book Now</a></li>
        </ul>
        <div class="nav-footer">
            <div class="social-links"><a href="https://www.facebook.com/people/Phambili-Ma-Africa/100063507009282/" target="_blank" aria-label="Facebook"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-facebook" viewBox="0 0 16 16">
                        <path d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951" />
                    </svg></a><a href="https://www.instagram.com/phambilimaafrica?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw==" target="_blank" aria-label="Instagram"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-instagram" viewBox="0 0 16 16">
                        <path d="M8 0C5.829 0 5.556.01 4.703.048 3.85.088 3.269.222 2.76.42a3.9 3.9 0 0 0-1.417.923A3.9 3.9 0 0 0 .42 2.76C.222 3.268.087 3.85.048 4.7.01 5.555 0 5.827 0 8.001c0 2.172.01 2.444.048 3.297.04.852.174 1.433.372 1.942.205.526.478.972.923 1.417.444.445.89.719 1.416.923.51.198 1.09.333 1.942.372C5.555 15.99 5.827 16 8 16s2.444-.01 3.298-.048c.851-.04 1.434-.174 1.943-.372a3.9 3.9 0 0 0 1.416-.923c.445-.445.718-.891.923-1.417.197-.509.332-1.09.372-1.942C15.99 10.445 16 10.173 16 8s-.01-2.445-.048-3.299c-.04-.851-.175-1.433-.372-1.941a3.9 3.9 0 0 0-.923-1.417A3.9 3.9 0 0 0 13.24.42c-.51-.198-1.092-.333-1.943-.372C10.443.01 10.172 0 7.998 0zm-.717 1.442h.718c2.136 0 2.389.007 3.232.046.78.035 1.204.166 1.486.275.373.145.64.319.92.599s.453.546.598.92c.11.281.24.705.275 1.485.039.843.047 1.096.047 3.231s-.008 2.389-.047 3.232c-.035.78-.166 1.203-.275 1.485a2.5 2.5 0 0 1-.599.919c-.28.28-.546.453-.92.598-.28.11-.704.24-1.485.276-.843.038-1.096.047-3.232.047s-2.39-.009-3.233-.047c-.78-.036-1.203-.166-1.485-.276a2.5 2.5 0 0 1-.92-.598 2.5 2.5 0 0 1-.6-.92c-.109-.281-.24-.705-.275-1.485-.038-.843-.046-1.096-.046-3.233s.008-2.388.046-3.231c.036-.78.166-1.204.276-1.486.145-.373.319-.64.599-.92s.546-.453.92-.598c.282-.11.705-.24 1.485-.276.738-.034 1.024-.044 2.515-.045zm4.988 1.328a.96.96 0 1 0 0 1.92.96.96 0 0 0 0-1.92m-4.27 1.122a4.109 4.109 0 1 0 0 8.217 4.109 4.109 0 0 0 0-8.217m0 1.441a2.667 2.667 0 1 1 0 5.334 2.667 2.667 0 0 1 0-5.334" />
                    </svg></a><a href="https://www.linkedin.com/in/phambili-ma-africa-8256a4151" target="_blank" aria-label="LinkedIn"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-linkedin" viewBox="0 0 16 16">
                        <path d="M0 1.146C0 .513.526 0 1.175 0h13.65C15.474 0 16 .513 16 1.146v13.708c0 .633-.526 1.146-1.175 1.146H1.175C.526 16 0 15.487 0 14.854V1.146zm4.943 12.248V6.169H2.542v7.225zm-1.2-8.212c.837 0 1.358-.554 1.358-1.248-.015-.709-.52-1.248-1.342-1.248S2.4 3.226 2.4 3.934c0 .694.521 1.248 1.327 1.248zm4.908 8.212V9.359c0-.216.016-.432.08-.586.173-.431.568-.878 1.232-.878.869 0 1.216.662 1.216 1.634v3.865h2.401V9.25c0-2.22-1.184-3.252-2.764-3.252-1.274 0-1.845.7-2.165 1.193v.025h-.016l.016-.025V6.169h-2.4c.03.678 0 7.225 0 7.225z" />
                    </svg></a><a href="https://www.tiktok.com/@user7297605414365" target="_blank" aria-label="TikTok"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-tiktok" viewBox="0 0 16 16">
                        <path d="M9 0h1.98c.144.715.54 1.617 1.235 2.512C12.895 3.389 13.797 4 15 4v2c-1.753 0-3.07-.814-4-1.829V11a5 5 0 1 1-5-5v2a3 3 0 1 0 3 3z" />
                    </svg></a></div>
            <p class="copyright">&copy; <span id="current-year"></span> Phambili Ma-Africa. All rights reserved.</p>
        </div>
    </nav>
    <aside class="cart-sidebar" id="cart-sidebar">
        <div class="cart-header">
            <h3 class="cart-title">Your Shopping Cart</h3><button class="close-cart-btn" id="close-cart-btn" aria-label="Close cart"><svg viewBox="0 0 24 24" width="30" height="30">
                    <path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                </svg></button>
        </div>
        <div class="cart-items" id="cart-items">
            <div class="empty-cart"><svg viewBox="0 0 24 24" width="48" height="48">
                    <path fill="currentColor" d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z" />
                </svg>
                <p>Your cart is empty</p>
            </div>
        </div>
        <div class="cart-footer">
            <div class="cart-summary">
                <div class="cart-subtotal"><span>Subtotal</span><span class="cart-total" id="cart-total">R0.00</span></div>
                <p class="cart-shipping">Shipping & taxes calculated at checkout</p>
            </div><a href="#checkout.php" class="checkout-btn" id="checkout-btn">Proceed to Checkout</a>
            <div class="cart-payment-methods"><svg viewBox="0 0 38 24" width="38" height="24">
                    <path fill="#5A6988" d="M35 0H3C1.3 0 0 1.3 0 3v18c0 1.7 1.4 3 3 3h32c1.7 0 3-1.3 3-3V3c0-1.7-1.4-3-3-3z" />
                    <path fill="#fff" d="M35 1c1.1 0 2 .9 2 2v18c0 1.1-.9 2-2 2H3c-1.1 0-2-.9-2-2V3c0-1.1.9-2 2-2h32" />
                    <path fill="#172B85" d="M8.6 12c0-1.7 1.3-3 3-3h5.3c1.7 0 3 1.3 3 3s-1.3 3-3 3h-5.3c-1.7 0-3-1.3-3-3z" />
                    <path fill="#FF5F00" d="M30.9 16h-6.3v-8h6.3v8z" />
                    <path fill="#EB001B" d="M25.1 12c0-2.2 1.1-4.1 2.8-5.2-1.2-1.1-2.8-1.8-4.5-1.8-3.1 0-5.7 2.5-5.7 5.7s2.5 5.7 5.7 5.7c1.7 0 3.3-.7 4.5-1.8-1.7-1.1-2.8-3-2.8-5.2z" />
                    <path fill="#F79E1B" d="M37 12c0 2.2-1.1 4.1-2.8 5.2 1.2 1.1 2.8 1.8 4.5 1.8 3.1 0 5.7-2.5 5.7-5.7s-2.5-5.7-5.7-5.7c-1.7 0-3.3.7-4.5 1.8 1.7 1.1 2.8 3 2.8 5.2z" />
                </svg><span>Secure Payment</span></div>
        </div>
    </aside>
    <div class="menu-overlay" id="menu-overlay"></div>
    <main class="profile-main">
        <div class="profile-container"><div id="pageMessages"></div><div class="profile-header">
                <div class="profile-avatar">
                    <div class="avatar-circle"><i class="fas fa-calendar-check" style="font-size: 2rem;"></i></div>
                </div>
                <div class="profile-info-header">
                    <h1 class="profile-name">Bookings</h1>
                    <p class="profile-email">View and manage your service bookings</p><span class="profile-role-badge" id="bookingCountBadge">0 Total</span>
                </div>
            </div>
            <div class="profile-section">
                <div class="section-content" style="padding: 1rem;">
                    <div class="booking-filters" id="bookingFilters">
                        <a href="bookings.php?status=all" class="filter-btn" data-status="all"><i class="fas fa-list"></i> All <span class="filter-count" data-count="all">0</span></a>
                        <a href="bookings.php?status=requested" class="filter-btn" data-status="requested"><i class="fas fa-clock"></i> Requested <span class="filter-count" data-count="requested">0</span></a>
                        <a href="bookings.php?status=confirmed" class="filter-btn" data-status="confirmed"><i class="fas fa-check"></i> Confirmed <span class="filter-count" data-count="confirmed">0</span></a>
                        <a href="bookings.php?status=completed" class="filter-btn" data-status="completed"><i class="fas fa-check-circle"></i> Completed <span class="filter-count" data-count="completed">0</span></a>
                        <a href="bookings.php?status=cancelled" class="filter-btn" data-status="cancelled"><i class="fas fa-times-circle"></i> Cancelled <span class="filter-count" data-count="cancelled">0</span></a>
                        <a href="bookings.php?status=expired" class="filter-btn" data-status="expired"><i class="fas fa-clock"></i> Expired <span class="filter-count" data-count="expired">0</span></a>
                    </div>
                </div>
            </div>
            <div class="profile-section" id="booking-history">
                <div class="section-header">
                    <h2><i class="fas fa-list"></i> <span id="filterTitle">All Bookings</span></h2><a href="services.php" class="btn-bookings">Book More Services →</a>
                </div>
                <div class="section-content">
                    <div class="booking-history" id="bookingHistory">
                        <div class="empty-state"><i class="fas fa-spinner fa-spin empty-icon"></i>
                            <h3>Loading bookings...</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <div id="bookingModal" class="booking-modal" style="display: none;">
        <div class="modal-content" style="background: linear-gradient(180deg, #f8f9fa 0%, #e9ecef 100%) !important; max-width: 750px !important; width: 100% !important; border-radius: 24px !important; box-shadow: 0 32px 64px rgba(0,0,0,0.4) !important;">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; color: white !important; padding: 2rem !important; border-radius: 24px 24px 0 0 !important;">
                <div class="modal-header-content">
                    <div class="modal-icon" style="background: rgba(255,255,255,0.2) !important; width: 60px !important; height: 60px !important; border-radius: 16px !important; display: flex !important; align-items: center !important; justify-content: center !important; font-size: 1.5rem !important; color: white !important;"><i class="fas fa-file-invoice-dollar"></i></div>
                    <div class="modal-title">
                        <h2 style="color: white !important; font-size: 1.5rem ; margin: 0 !important;">Quotation Details</h2> <span class="modal-subtitle" style="color: rgba(255,255,255,0.9) !important; font-size: 0.9rem !important;">Booking #<span id="modal-booking-number"></span></span>
                    </div>
                </div><button type="button" class="close-btn" onclick="closeBookingModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <div class="status-banner" id="status-banner"><i class="fas fa-info-circle" id="status-icon"></i><span id="status-text">Status: Requested</span></div>
                <div class="service-card" id="modal-service-card">
                    <div class="service-icon"><i class="fas fa-broom"></i></div>
                    <div class="service-details">
                        <h3 id="modal-service-name">Service Name</h3>
                        <div class="service-tags"><span class="tag" id="modal-property-tag"><i class="fas fa-home"></i> <span id="modal-property-display">House</span></span><span class="tag" id="modal-frequency-tag"><i class="fas fa-sync"></i> <span id="modal-frequency-display">Weekly</span></span></div>
                    </div>
                </div>
                <div class="info-grid">
                    <div class="info-card">
                        <div class="info-icon"><i class="fas fa-calendar-alt"></i></div>
                        <div class="info-content"><label>Date</label><span id="modal-date-display">-</span></div>
                    </div>
                    <div class="info-card">
                        <div class="info-icon"><i class="fas fa-clock"></i></div>
                        <div class="info-content"><label>Time</label><span id="modal-time-display">-</span></div>
                    </div>
                </div>
                <form id="edit-booking-form"><input type="hidden" id="modal-booking-id" name="booking_id">
                    <div class="section-header-edit"><i class="fas fa-edit"></i><span>Edit Details</span></div>
                    <div class="form-row">
                        <div class="form-group"><label class="input-label"><i class="fas fa-calendar"></i> Date</label>
                            <div class="input-wrapper"><input type="date" id="modal-date" name="booking_date" required></div><span class="edit-hint"><i class="fas fa-lightbulb"></i> Select preferred date</span>
                        </div>
                        <div class="form-group"><label class="input-label"><i class="fas fa-clock"></i> Time</label>
                            <div class="input-wrapper"><select id="modal-time" name="booking_time" required>
                                    <option value="08:00">08:00 AM</option>
                                    <option value="09:00">09:00 AM</option>
                                    <option value="10:00">10:00 AM</option>
                                    <option value="11:00">11:00 AM</option>
                                    <option value="12:00">12:00 PM</option>
                                    <option value="13:00">01:00 PM</option>
                                    <option value="14:00">02:00 PM</option>
                                    <option value="15:00">03:00 PM</option>
                                    <option value="16:00">04:00 PM</option>
                                </select></div><span class="edit-hint"><i class="fas fa-lightbulb"></i> Select preferred time</span>
                        </div>
                    </div>
                    <div class="form-group full-width"><label class="input-label"><i class="fas fa-map-marker-alt"></i> Address</label>
                        <div class="input-wrapper textarea-wrapper"><textarea id="modal-address" name="address" rows="3" required></textarea></div><span class="edit-hint"><i class="fas fa-lightbulb"></i> Your service location</span>
                    </div>
                    <div class="form-group full-width"><label class="input-label"><i class="fas fa-comment-alt"></i> Special Instructions <span class="optional">(Optional)</span></label>
                        <div class="input-wrapper textarea-wrapper"><textarea id="modal-instructions" name="special_instructions" rows="2" placeholder="Any special requests or notes..."></textarea></div><span class="edit-hint"><i class="fas fa-lightbulb"></i> Add any special requirements</span>
                    </div>
                    <div class="admin-notice" id="admin-notice" style="display: none;">
                        <div class="notice-icon"><i class="fas fa-lock"></i></div>
                        <div class="notice-content"><strong>Editing Locked</strong><span>This quotation has been processed by our team. Please contact us for any changes.</span></div>
                    </div>
                    <div class="modal-actions" id="modal-actions"><button type="submit" name="update_booking" class="btn-save" id="btn-save" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; padding: 1rem 1.5rem; border-radius: 12px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 0.75rem; box-shadow: 0 4px 15px rgba(40,167,69,0.4);">
                            <div class="btn-icon"><i class="fas fa-save"></i></div>
                            <div class="btn-text"><span class="btn-main-text">Save Changes</span><span class="btn-sub-text">Update your quotation</span></div>
                        </button><button type="button" class="btn-close-modal" onclick="closeBookingModal()" style="background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%); color: #424242; border: 2px solid #bdbdbd; padding: 1rem 1.5rem; border-radius: 12px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 0.75rem;">
                            <div class="btn-icon"><i class="fas fa-times"></i></div>
                            <div class="btn-text"><span class="btn-main-text">Close</span><span class="btn-sub-text">Discard changes</span></div>
                        </button></div>
                </form>
            </div>
        </div>
    </div>
    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-section company-info">
                <div class="footer-logo"><a href="index.php"><img src="images/PM-LogoB.png" alt="Phambili Ma-Africa Logo" loading="lazy"></a></div>
                <p class="footer-motto">Making spaces shine since 2019</p>
                <div class="social-links"><a href="https://www.facebook.com/people/Phambili-Ma-Africa/100063507009282/" target="_blank" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a><a href="https://www.instagram.com/phambilimaafrica" target="_blank" aria-label="Instagram"><i class="fab fa-instagram"></i></a><a href="https://www.linkedin.com/in/phambili-ma-africa-8256a4151" target="_blank" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a><a href="https://www.tiktok.com/@user7297605414365" target="_blank" aria-label="TikTok"><i class="fab fa-tiktok"></i></a></div>
            </div>
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
            <div class="footer-section services">
                <h3 class="footer-heading">Our Services</h3>
                <div class="footer-services-grid">
                    <ul>
                        <li><a href="services.php">Standard & Deep Cleaning</a></li>
                        <li><a href="services.php">Office Cleaning</a></li>
                        <li><a href="services.php">Window Cleaning</a></li>
                        <li><a href="services.php">Carpet Cleaning</a></li>
                    </ul>
                    <ul>
                        <li><a href="products.php">Cleaning Products</a></li>
                        <li><a href="services.php">Pest Control</a></li>
                        <li><a href="services.php">Fumigation</a></li>
                        <li><a href="services.php">Disinfecting</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-section contact-info">
                <h3 class="footer-heading">Contact Us</h3>
                <ul class="contact-details">
                    <li>2676 Isikhwama Street, Phiri Extension, Phiri, Soweto, 1818</li>
                    <li><a href="tel:+27726625951">+27 72 662 5951</a></li>
                    <li><a href="tel:+27609707444">+27 60 970 7444</a></li>
                    <li><a href="mailto:info@phambilimaafrica.co.za">info@phambilimaafrica.co.za</a></li>
                    <li>Open 24 hours</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="footer-bottom-container">
                <p>RELM TECHNOLOGIES DEVELOPERS</p>
                <div class="legal-links"><a href="privacy.php">Privacy Policy</a><a href="terms.php">Terms of Service</a></div>
            </div>
        </div><button class="back-to-top" aria-label="Back to top"><i class="fas fa-arrow-up"></i></button>
    </footer>
    <script src="./js/script.js"></script>
    <script src="./js/config.js"></script>
    <script type="module" src="./js/firebase/ui.js"></script>
    <script type="module" src="./js/firebase/notifications.js"></script>
    <script type="module">
        import { watchMyRequests, getServices, updateRequest } from './js/firebase/data.js';
        import { requireAuth } from './js/firebase/auth.js';
        import { showMessage } from './js/firebase/ui.js';
        import { downloadQuotePdf } from './js/firebase/quotePdf.js';

        const authed = await requireAuth('bookings.php');
        if (!authed) throw new Error('redirected to login');
        const { user } = authed;

        const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        const list = document.getElementById('bookingHistory');
        const badge = document.getElementById('bookingCountBadge');
        const filterTitle = document.getElementById('filterTitle');
        const messages = document.getElementById('pageMessages');
        const modal = document.getElementById('bookingModal');
        const editForm = document.getElementById('edit-booking-form');

        // Firestore status -> website filter slug / css class.
        const SLUG = {
            'Pending': 'requested', 'Requested': 'requested', 'Contacted': 'contacted',
            'Quoted': 'quoted', 'Confirmed': 'confirmed', 'Approved': 'confirmed',
            'In Progress': 'confirmed', 'Completed': 'completed',
            'Cancelled': 'cancelled', 'Rejected': 'cancelled', 'Expired': 'expired',
        };
        const FILTER_BUCKETS = {
            requested: ['requested', 'contacted', 'quoted'],
            confirmed: ['confirmed'],
            completed: ['completed'],
            cancelled: ['cancelled'],
            expired: ['expired'],
        };
        const EDITABLE = ['requested', 'contacted'];

        let allRequests = [];
        let serviceImages = {};
        let currentFilter = new URLSearchParams(location.search).get('status') || 'all';

        const relativeDate = iso => {
            if (!iso) return '';
            const d = new Date(iso), now = new Date();
            const days = Math.floor((now - d) / 864e5);
            if (days <= 0) return 'Today';
            if (days === 1) return 'Yesterday';
            if (days < 30) return `${days} days ago`;
            return d.toLocaleDateString('en-ZA', { year: 'numeric', month: 'long', day: 'numeric' });
        };
        const fmtDate = d => d ? new Date(d).toLocaleDateString('en-ZA', { year: 'numeric', month: 'long', day: 'numeric' }) : '—';
        const fmtTime = t => {
            if (!t) return '—';
            const [h, m] = t.split(':').map(Number);
            const ampm = h >= 12 ? 'PM' : 'AM';
            return `${((h + 11) % 12) + 1}:${String(m).padStart(2, '0')} ${ampm}`;
        };
        const cap = s => s ? s[0].toUpperCase() + s.slice(1) : '';

        function cardHtml(b) {
            const slug = SLUG[b.Status] || 'requested';
            const img = serviceImages[b.Service_ID];
            return `
            <div class="booking-card status-${slug}">
                <div class="booking-card-content">
                    ${img ? `<div class="booking-image"><img src="${esc(img)}" alt="${esc(b.serviceName)}"></div>` : ''}
                    <div class="booking-details-main">
                        <div class="booking-card-header">
                            <div class="booking-service-info">
                                <h4 class="booking-service-name">${esc(b.serviceName || 'Service Booking')}</h4>
                                <div class="booking-meta">
                                    <span class="booking-date"><i class="fas fa-calendar-plus"></i> ${relativeDate(b.dateCreated)}</span>
                                    <span class="booking-date"><i class="fas fa-calendar-check"></i> ${fmtDate(b.Date)}</span>
                                    <span class="booking-time"><i class="fas fa-clock"></i> ${fmtTime(b.Time)}</span>
                                </div>
                            </div>
                            <span class="booking-status status-${slug}">${esc(b.Status)}</span>
                        </div>
                        ${b.Notes ? `<div class="booking-notes"><i class="fas fa-sticky-note"></i> ${esc(b.Notes)}</div>` : ''}
                        <div class="booking-footer"><span class="booking-type"><i class="fas fa-file-invoice-dollar"></i> Quotation</span>
                            <div class="booking-actions">
                                ${b.Quoted_Amount > 0 ? `<button type="button" class="btn-outline-small" data-action="pdf" data-id="${b.ID}"><i class="fas fa-file-pdf"></i> Download</button>` : ''}
                                <button type="button" class="btn-outline-small" data-action="view" data-id="${b.ID}"><i class="fas fa-eye"></i> View & Edit</button>
                                ${slug === 'quoted'
                                    ? `<button type="button" class="btn-approve-small" data-action="approve" data-id="${b.ID}"><i class="fas fa-check"></i> Approve</button>
                                       <button type="button" class="btn-cancel-small" data-action="decline" data-id="${b.ID}"><i class="fas fa-times"></i> Decline</button>`
                                    : (slug === 'requested' || slug === 'confirmed')
                                        ? `<button type="button" class="btn-cancel-small" data-action="cancel" data-id="${b.ID}"><i class="fas fa-times"></i> Cancel</button>`
                                        : ''}
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;
        }

        function render() {
            const counts = { all: allRequests.length, requested: 0, confirmed: 0, completed: 0, cancelled: 0, expired: 0 };
            allRequests.forEach(b => {
                const slug = SLUG[b.Status] || 'requested';
                Object.keys(FILTER_BUCKETS).forEach(k => {
                    if (FILTER_BUCKETS[k].includes(slug)) counts[k]++;
                });
            });
            document.querySelectorAll('[data-count]').forEach(el => { el.textContent = counts[el.dataset.count] ?? 0; });
            badge.textContent = `${counts.all} Total`;
            filterTitle.textContent = currentFilter === 'all' ? 'All Bookings' : `${cap(currentFilter)} Bookings`;
            document.querySelectorAll('#bookingFilters .filter-btn').forEach(a =>
                a.classList.toggle('active', a.dataset.status === currentFilter));

            const show = currentFilter === 'all' ? allRequests
                : allRequests.filter(b => FILTER_BUCKETS[currentFilter]?.includes(SLUG[b.Status] || 'requested'));
            list.innerHTML = show.length
                ? `<div class="bookings-list">${show.map(cardHtml).join('')}</div>`
                : `<div class="empty-state"><i class="fas fa-calendar-check empty-icon"></i>
                        <h3>No Bookings Found</h3>
                        <p>${currentFilter === 'all'
                            ? "You haven't made any bookings yet. Start by booking our cleaning services!"
                            : `No ${cap(currentFilter)} bookings found.`}</p>
                        <a href="services.php" class="btn-primary"><i class="fas fa-calendar-plus"></i> Book Services</a>
                    </div>`;
        }

        document.getElementById('bookingFilters').addEventListener('click', e => {
            const a = e.target.closest('.filter-btn');
            if (!a) return;
            e.preventDefault();
            currentFilter = a.dataset.status;
            history.replaceState(null, '', `bookings.php?status=${currentFilter}`);
            render();
        });

        /* ------------------------- Modal ------------------------- */
        function openModal(b) {
            const slug = SLUG[b.Status] || 'requested';
            document.getElementById('modal-booking-id').value = b.ID;
            document.getElementById('modal-booking-number').textContent = b.refNumber || b.ID;
            document.getElementById('modal-service-name').textContent = b.serviceName || 'Service';
            document.getElementById('modal-property-display').textContent = cap(b.Property_Type || 'house');
            document.getElementById('modal-frequency-display').textContent = cap(b.Cleaning_Frequency || 'once');
            document.getElementById('modal-date-display').textContent = fmtDate(b.Date);
            document.getElementById('modal-time-display').textContent = fmtTime(b.Time);
            document.getElementById('status-text').textContent = `Status: ${b.Status}`;
            document.getElementById('modal-date').value = b.Date || '';
            document.getElementById('modal-time').value = b.Time || '';
            document.getElementById('modal-address').value = b.Address || '';
            document.getElementById('modal-instructions').value = b.Special_Instructions || '';
            const locked = !EDITABLE.includes(slug);
            document.getElementById('admin-notice').style.display = locked ? '' : 'none';
            document.getElementById('btn-save').style.display = locked ? 'none' : '';
            modal.style.display = '';
        }
        function closeModal() { modal.style.display = 'none'; }
        window.closeBookingModal = closeModal;

        editForm.addEventListener('submit', async e => {
            e.preventDefault();
            const id = document.getElementById('modal-booking-id').value;
            try {
                await updateRequest(id, {
                    requestedDate: document.getElementById('modal-date').value,
                    requestedTime: document.getElementById('modal-time').value,
                    siteAddress: document.getElementById('modal-address').value,
                    specialInstructions: document.getElementById('modal-instructions').value,
                });
                closeModal();
                showMessage('Booking updated successfully.', 'success', messages);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } catch (err) {
                console.error(err);
                showMessage('Could not update the booking. Please try again.', 'error', messages);
            }
        });

        /* -------------------- Card actions (delegated) -------------------- */
        list.addEventListener('click', async e => {
            const btn = e.target.closest('[data-action]');
            if (!btn) return;
            const b = allRequests.find(x => x.ID === btn.dataset.id);
            if (!b) return;
            const action = btn.dataset.action;
            try {
                if (action === 'view') {
                    openModal(b);
                } else if (action === 'pdf') {
                    await downloadQuotePdf(b);
                } else if (action === 'approve') {
                    if (confirm('Do you want to approve this quote? The service will be scheduled.')) {
                        await updateRequest(b.ID, { status: 'Confirmed' });
                        showMessage(`Quote ${b.refNumber || b.ID} approved.`, 'success', messages);
                    }
                } else if (action === 'decline' || action === 'cancel') {
                    const msg = action === 'decline'
                        ? 'Decline this quote?'
                        : 'Cancel this booking?';
                    if (confirm(msg)) {
                        await updateRequest(b.ID, { status: 'Cancelled' });
                        showMessage(`Booking ${b.refNumber || b.ID} ${action === 'decline' ? 'declined' : 'cancelled'}.`, 'info', messages);
                    }
                }
            } catch (err) {
                console.error(err);
                showMessage('That action failed. Please try again.', 'error', messages);
            }
        });

        getServices().then(svcs => {
            svcs.forEach(s => { if (s.Image_URL) serviceImages[s.ID] = s.Image_URL; });
            render();
        }).catch(console.error);

        watchMyRequests(user.uid, requests => {
            allRequests = requests;
            render();
            // Honour profile.php "View" links: bookings.php?view=<id>
            const viewId = new URLSearchParams(location.search).get('view');
            const target = viewId && allRequests.find(x => x.ID === viewId);
            if (target) openModal(target);
        });
    </script>
</body>

</html>
