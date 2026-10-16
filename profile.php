<?php
// Start session and include database connection
require_once 'includes/config_session.inc.php';
require_once 'includes/dbh.inc.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'customer';
$user_name = $_SESSION['user_name'] ?? 'User';
$user_email = $_SESSION['user_email'] ?? '';

// Fetch customer details from database
try {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE ID = ?");
    $stmt->execute([$user_id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching customer: " . $e->getMessage());
    $customer = null;
}

// Fetch user's bookings
try {
    $stmt = $pdo->prepare("
        SELECT b.*, s.Name as Service_Name, s.Image_URL as Service_Image
        FROM bookings b
        LEFT JOIN services s ON b.Service_ID = s.ID
        WHERE b.Customer_ID = ?
        ORDER BY b.Created_At DESC
    ");
    $stmt->execute([$user_id]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching bookings: " . $e->getMessage());
    $bookings = [];
}

// Include notification functions
require_once 'includes/notifications_model.inc.php';

// Fetch unread notifications for this customer
$notifications = get_notifications($pdo, $user_id, 'customer', true, 5);
$unreadCount = get_unread_notification_count($pdo, $user_id, 'customer');

// Fetch all notifications for history
$allNotifications = get_notifications($pdo, $user_id, 'customer', false, 20);

// Set default values if customer data not found
$phone = $customer['Phone'] ?? '';
$address = $customer['Address'] ?? '';
$created_at = $customer['Created_At'] ?? date('Y-m-d');

// Format join date
$join_date = date('F Y', strtotime($created_at));

// Message variables (like admin dashboard)
$profileMessage = '';
$profileError = '';
$passwordMessage = '';
$passwordError = '';
$addressMessage = '';
$addressError = '';

// Parse address into components for display/edit (stored as: Street, City, State, Postal)
$address_parts = array_map('trim', explode(',', $address));
$address_street = $address_parts[0] ?? '';
$address_city = $address_parts[1] ?? '';
$address_state = $address_parts[2] ?? '';
$address_zip = $address_parts[3] ?? '';

// Handle Profile Update directly (like admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $new_phone = trim($_POST['phone'] ?? '');

    // Validation
    if (empty($fullname)) {
        $profileError = "Full name is required.";
    } elseif (strlen($fullname) < 2) {
        $profileError = "Full name must be at least 2 characters.";
    } elseif (!preg_match('/^[a-zA-Z\s\-\']+$/', $fullname)) {
        $profileError = "Full name can only contain letters, spaces, hyphens, and apostrophes.";
    } elseif (empty($email)) {
        $profileError = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $profileError = "Please enter a valid email address (e.g., name@example.com).";
    } elseif (strlen($email) > 254) {
        $profileError = "Email address is too long.";
    } elseif (empty($new_phone)) {
        $profileError = "Phone number is required.";
    } else {
        // Strict phone validation - exactly 10 digits, only numbers, South African format
        $clean_phone = preg_replace('/\D/', '', $new_phone);
        
        if (!ctype_digit($clean_phone)) {
            $profileError = "Phone number must contain only digits (0-9), no letters or special characters.";
        } elseif (strlen($clean_phone) !== 10) {
            $profileError = "Phone number must be exactly 10 digits.";
        } elseif (!preg_match('/^0[0-9]{9}$/', $clean_phone)) {
            $profileError = "Phone number must start with 0 followed by 9 digits (e.g., 0821234567).";
        } else {
            // Check if email already exists for another user
            try {
                $stmt = $pdo->prepare("SELECT ID FROM customers WHERE Email = ? AND ID != ?");
                $stmt->execute([$email, $user_id]);
                if ($stmt->fetch()) {
                    $profileError = "Email address is already in use by another account.";
                } else {
                    // Update customer with cleaned phone
                    $stmt = $pdo->prepare("
                        UPDATE customers 
                        SET Full_Name = ?, Email = ?, Phone = ?, updatedAt = NOW()
                        WHERE ID = ?
                    ");
                    $stmt->execute([$fullname, $email, $clean_phone, $user_id]);

                    // Update session
                    $_SESSION['user_name'] = $fullname;
                    $_SESSION['user_email'] = $email;
                    $user_name = $fullname;
                    $user_email = $email;
                    $phone = $clean_phone;

                    $profileMessage = "Profile updated successfully!";
                }
            } catch (PDOException $e) {
                error_log("Profile update error: " . $e->getMessage());
                $profileError = "Failed to update profile. Please try again.";
            }
        }
    }
}

// Handle Address Update directly
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_address'])) {
    $new_street = trim($_POST['address_street'] ?? '');
    $new_city = trim($_POST['address_city'] ?? '');
    $new_state = trim($_POST['address_state'] ?? '');
    $new_zip = trim($_POST['address_zip'] ?? '');

    // Validation for all address fields
    if (empty($new_street)) {
        $addressError = "Street address is required.";
    } elseif (empty($new_city)) {
        $addressError = "City is required.";
    } elseif (empty($new_state)) {
        $addressError = "State/Province is required.";
    } elseif (empty($new_zip)) {
        $addressError = "Postal code is required.";
    } else {
        // Postal code validation (4 digits)
        $postal = preg_replace('/\D/', '', $new_zip);
        if (!ctype_digit($postal) || strlen($postal) !== 4) {
            $addressError = "Postal code must be exactly 4 digits (e.g., 2000).";
        } else {
            // Combine into single Address field format: Street, City, State, Postal
            $new_address = implode(', ', array_filter([$new_street, $new_city, $new_state, $postal]));

            try {
                $stmt = $pdo->prepare("
                    UPDATE customers 
                    SET Address = ?, updatedAt = NOW()
                    WHERE ID = ?
                ");
                $stmt->execute([$new_address, $user_id]);

                $address = $new_address;
                $address_street = $new_street;
                $address_city = $new_city;
                $address_state = $new_state;
                $address_zip = $postal;

                $addressMessage = "Address updated successfully!";
            } catch (PDOException $e) {
                error_log("Address update error: " . $e->getMessage());
                $addressError = "Failed to update address. Please try again.";
            }
        }
    }
}

// Handle Password Change directly
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($current_password)) {
        $passwordError = "Current password is required.";
    } elseif (empty($new_password)) {
        $passwordError = "New password is required.";
    } elseif (strlen($new_password) < 8) {
        $passwordError = "New password must be at least 8 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $passwordError = "New passwords do not match.";
    } else {
        try {
            // Verify current password
            $stmt = $pdo->prepare("SELECT Password FROM customers WHERE ID = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($current_password, $user['Password'])) {
                $passwordError = "Current password is incorrect.";
            } else {
                // Hash and update new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE customers SET Password = ? WHERE ID = ?");
                $stmt->execute([$hashed_password, $user_id]);

                // Delete all remember me tokens for this user (security measure)
                require_once 'includes/login_model.inc.php';
                delete_all_remember_me_tokens($pdo, $user_id, 'customer');

                $passwordMessage = "Password changed successfully!";
            }
        } catch (PDOException $e) {
            error_log("Password change error: " . $e->getMessage());
            $passwordError = "Failed to change password. Please try again.";
        }
    }
}

// Edit modes
$profileEditMode = isset($_GET['edit']) && $_GET['edit'] === 'profile';
$addressEditMode = isset($_GET['edit']) && $_GET['edit'] === 'address';
// Get cart count from session or localStorage (will be handled by JS)
$cart_count = 0;

// Check user login status
$is_logged_in = isset($_SESSION['user_id']);
$is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
$is_customer = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'customer';
$user_email = $_SESSION['user_email'] ?? '';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Profile | Phambili Ma-Africa</title>
    <link rel="icon" type="image/png" sizes="32x32" href="images/PM-Logo.jpg">

    <!-- Open Graph Meta Tags for Social Media Preview -->
    <meta property="og:title" content="My Profile | Phambili Ma-Africa">
    <meta property="og:description" content="Manage your profile and account settings at Phambili Ma-Africa.">
    <meta property="og:image" content="images/PM-Logo.jpg">
    <meta property="og:url" content="https://phambili-ma-africa.com/profile.php">
    <meta property="og:type" content="website">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="My Profile | Phambili Ma-Africa">
    <meta name="twitter:description" content="Manage your profile and account settings at Phambili Ma-Africa.">
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
        /* Smooth scrolling for anchor links */
        html {
            scroll-behavior: smooth;
        }
        /* Prevent scroll jump glitch on page load with hash */
        body {
            overflow-x: hidden;
        }
    </style>
</head>

<body class="profile-page" data-is-logged-in="<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>">
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
            <?php if ($is_customer && $user_id): ?>
            <!-- Notification Bell -->
            <div class="notification-bell-customer">
                <button class="bell-btn-customer" onclick="toggleCustomerNotificationDropdown()">
                    <i class="fas fa-bell"></i>
                    <?php if ($unreadCount > 0): ?>
                        <span class="notification-badge-customer"><?php echo $unreadCount; ?></span>
                    <?php endif; ?>
                </button>
                <div class="notification-dropdown-customer" id="notificationDropdownCustomer">
                    <div class="notification-dropdown-header">
                        <h4>Notifications</h4>
                        <button class="mark-all-read-btn" onclick="markAllCustomerNotificationsRead()">Mark all read</button>
                    </div>
                    <div class="notification-dropdown-content">
                        <?php if (empty($notifications)): ?>
                            <div class="no-notifications">
                                <i class="fas fa-bell-slash"></i>
                                <p>No new notifications</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($notifications as $notif): ?>
                                <div class="notification-dropdown-item unread" data-id="<?php echo $notif['ID']; ?>">
                                    <div class="notif-content">
                                        <strong><?php echo htmlspecialchars($notif['title']); ?></strong>
                                        <p><?php echo htmlspecialchars($notif['message']); ?></p>
                                        <small><?php echo date('M j, Y g:i A', strtotime($notif['created_at'])); ?></small>
                                    </div>
                                    <button class="notif-close" onclick="markCustomerNotificationRead(<?php echo $notif['ID']; ?>)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($is_logged_in): ?>
                <!-- User Profile Dropdown (shown when logged in) -->
                <div class="user-profile-dropdown" id="user-profile-dropdown">
                    <button class="icon-link user-profile-btn" aria-label="User Profile" id="user-profile-btn">
                        <img src="images/user.png" alt="User" class="icon" id="user-icon">
                    </button>
                    <div class="profile-dropdown-menu" id="profile-dropdown-menu">
                        <?php if ($is_admin): ?>
                            <!-- Admin Profile Dropdown -->
                            <div class="profile-dropdown-header">
                                <i class="fas fa-user-shield admin-icon"></i>
                                <div class="profile-info">
                                    <span class="profile-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Administrator'); ?></span>
                                    <span class="profile-role">Admin</span>
                                </div>
                            </div>
                            <div class="profile-dropdown-links">
                                <a href="admin_dashboard.php" class="dropdown-link">
                                    <i class="fas fa-tachometer-alt"></i>
                                    Admin Dashboard
                                </a>
                                <a href="includes/logout.inc.php" class="dropdown-link logout-link">
                                    <i class="fas fa-sign-out-alt"></i>
                                    Log Out
                                </a>
                            </div>
                        <?php elseif ($is_customer): ?>
                            <!-- Customer Profile Dropdown -->
                            <div class="profile-dropdown-header">
                                <i class="fas fa-user customer-icon"></i>
                                <div class="profile-info">
                                    <span class="profile-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Customer'); ?></span>
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
                                <a href="includes/logout.inc.php" class="dropdown-link logout-link">
                                    <i class="fas fa-sign-out-alt"></i>
                                    Log Out
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Guest: Icon redirects to login page -->
                <a href="login.php" class="icon-link" aria-label="Login/Register">
                    <img src="images/user.png" alt="Login" class="icon">
                </a>
            <?php endif; ?>
            <button class="cart-btn" id="cart-btn" aria-label="Shopping Cart">
                <img src="images/shopping-bag.png" alt="" class="icon">
                <?php if (isset($_SESSION["user_id"])): ?>
                    <span class="cart-badge">0</span>
                <?php endif; ?>
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
            <?php if ($is_admin): ?>
                <!-- Admin sees Admin Dashboard link instead of Login -->
                <li class="nav-item">
                    <a href="admin_dashboard.php" class="nav-link admin-nav-link">
                        Admin Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="includes/logout.inc.php" class="nav-link"> Log Out</a>
                </li>
            <?php elseif ($is_customer): ?>
                <!-- Customer sees Profile and Logout -->
                <li class="nav-item">
                    <a href="bookings.php" class="nav-link">
                        Bookings
                    </a>
                </li>
                <li class="nav-item">
                    <a href="profile.php" class="nav-link">
                        Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a href="includes/logout.inc.php" class="nav-link"> Log Out</a>
                </li>
            <?php else: ?>
                <!-- Guest sees Login -->
                <li class="nav-item">
                    <a href="login.php" class="nav-link"> Log In</a>
                </li>
            <?php endif; ?>

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

    <!-- Overlay -->
    <div class="menu-overlay" id="menu-overlay"></div>

    <!-- Profile Main Content -->
    <main class="profile-main">
        <div class="profile-container">

            <!-- Messages -->
            <?php if ($profileMessage): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($profileMessage); ?>
                </div>
            <?php endif; ?>

            <?php if ($profileError): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($profileError); ?>
                </div>
            <?php endif; ?>

            <?php if ($addressMessage): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($addressMessage); ?>
                </div>
            <?php endif; ?>

            <?php if ($addressError): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($addressError); ?>
                </div>
            <?php endif; ?>

            <!-- Notifications -->
            <?php if (!empty($notifications)): ?>
                <div class="notification-section">
                    <div class="notification-header">
                        <h3><i class="fas fa-bell"></i> Notifications (<?php echo $unreadCount; ?>)</h3>
                        <a href="#" class="mark-all-read-btn" onclick="markAllAsRead(event)">Mark all as read</a>
                    </div>
                    <?php foreach ($notifications as $notif): ?>
                        <div class="notification-item unread" data-id="<?php echo $notif['ID']; ?>">
                            <div class="notification-content">
                                <strong><?php echo htmlspecialchars($notif['title']); ?></strong>
                                <p><?php echo htmlspecialchars($notif['message']); ?></p>
                                <small class="notification-time"><?php echo date('M j, Y g:i A', strtotime($notif['created_at'])); ?></small>
                            </div>
                            <button class="notification-close" onclick="markAsRead(<?php echo $notif['ID']; ?>)">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($passwordMessage): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($passwordMessage); ?>
                </div>
            <?php endif; ?>

            <?php if ($passwordError): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($passwordError); ?>
                </div>
            <?php endif; ?>

            <!-- Profile Header -->
            <div class="profile-header">
                <div class="profile-avatar">
                    <div class="avatar-circle">
                        <span class="avatar-initials"><?php echo strtoupper(substr($user_name, 0, 1)); ?></span>
                    </div>
                </div>
                <div class="profile-info-header">
                    <h1 class="profile-name"><?php echo htmlspecialchars($user_name); ?></h1>
                    <p class="profile-email"><?php echo htmlspecialchars($user_email); ?></p>
                    <span class="profile-role-badge"><?php echo ucfirst($user_role); ?></span>
                </div>
            </div>
            <!-- Notification History Section -->
            <section class="profile-section" id="notification-history">
                <div class="section-header">
                    <h2><i class="fas fa-bell"></i> Notification History</h2>
                </div>
                <div class="section-content">
                    <div class="notifications-history">
                        <?php if (empty($allNotifications)): ?>
                            <div class="empty-state">
                                <i class="fas fa-bell-slash empty-icon"></i>
                                <h3>No Notifications</h3>
                                <p>You don't have any notifications yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="notifications-list">
                                <?php foreach ($allNotifications as $notif): ?>
                                    <div class="notification-history-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>">
                                        <div class="notification-history-content">
                                            <strong><?php echo htmlspecialchars($notif['title']); ?></strong>
                                            <p><?php echo htmlspecialchars($notif['message']); ?></p>
                                            <div class="notification-meta">
                                                <span class="notification-time"><?php echo date('M j, Y g:i A', strtotime($notif['created_at'])); ?></span>
                                                <span class="notification-type"><?php echo htmlspecialchars($notif['type']); ?></span>
                                            </div>
                                        </div>
                                        <?php if (!$notif['is_read']): ?>
                                            <button class="mark-read-btn" onclick="markAsRead(<?php echo $notif['ID']; ?>)">
                                                <i class="fas fa-check"></i> Mark as Read
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </section>
            <br>
            <!-- Profile Sections -->
            <div class="profile-sections">

                <!-- Personal Information Section -->
                <section class="profile-section" id="personal-info">
                    <div class="section-header">
                        <h2><i class="fas fa-user"></i> Personal Information</h2>
                        <?php if (!$profileEditMode): ?>
                            <a href="?edit=profile#personal-info" class="edit-section-btn">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="section-content">
                        <?php if ($profileEditMode): ?>
                            <!-- Edit Mode -->
                            <form method="POST" action="profile.php#personal-info">
                                <div class="info-grid">
                                    <div class="info-item">
                                        <label>Full Name</label>
                                        <input type="text" class="edit-input" name="fullname"
                                            value="<?php echo htmlspecialchars($user_name); ?>" required>
                                    </div>
                                    <div class="info-item">
                                        <label>Email Address</label>
                                        <input type="email" class="edit-input" name="email"
                                            value="<?php echo htmlspecialchars($user_email); ?>" required>
                                    </div>
                                    <div class="info-item">
                                        <label>Phone Number</label>
                                        <input type="tel" class="edit-input" name="phone" id="profile-phone"
                                            value="<?php echo htmlspecialchars($phone); ?>"
                                            placeholder="Enter phone number" maxlength="10">
                                    </div>
                                    <div class="info-item">
                                        <label>Member Since</label>
                                        <p><?php echo htmlspecialchars($join_date); ?></p>
                                    </div>
                                </div>
                                <div class="section-actions">
                                    <a href="profile.php#personal-info" class="btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn-primary" name="update_profile">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <!-- View Mode -->
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>Full Name</label>
                                    <p><?php echo htmlspecialchars($user_name); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Email Address</label>
                                    <p><?php echo htmlspecialchars($user_email); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Phone Number</label>
                                    <p><?php echo $phone ? htmlspecialchars($phone) : 'Not set'; ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Member Since</label>
                                    <p><?php echo htmlspecialchars($join_date); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Address Section -->
                <section class="profile-section" id="address-info">
                    <div class="section-header">
                        <h2><i class="fas fa-map-marker-alt"></i> Address Information</h2>
                        <?php if (!$addressEditMode): ?>
                            <a href="?edit=address#address-info" class="edit-section-btn">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="section-content">
                        <?php if ($addressEditMode): ?>
                            <!-- Edit Mode -->
                            <form method="POST" action="profile.php#address-info">
                                <div class="info-grid">
                                    <div class="info-item full-width">
                                        <label>Street Address</label>
                                        <input type="text" class="edit-input" name="address_street"
                                            value="<?php echo htmlspecialchars($address_street); ?>"
                                            placeholder="Enter street address">
                                    </div>
                                    <div class="info-item">
                                        <label>City</label>
                                        <input type="text" class="edit-input" name="address_city"
                                            value="<?php echo htmlspecialchars($address_city); ?>"
                                            placeholder="City">
                                    </div>
                                    <div class="info-item">
                                        <label>State/Province</label>
                                        <input type="text" class="edit-input" name="address_state"
                                            value="<?php echo htmlspecialchars($address_state); ?>"
                                            placeholder="State">
                                    </div>
                                    <div class="info-item">
                                        <label>Postal Code</label>
                                        <input type="text" class="edit-input" name="address_zip"
                                            value="<?php echo htmlspecialchars($address_zip); ?>"
                                            placeholder="Postal Code">
                                    </div>
                                </div>
                                <div class="section-actions">
                                    <a href="profile.php#address-info" class="btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn-primary" name="update_address">
                                        <i class="fas fa-save"></i> Save Address
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <!-- View Mode -->
                            <div class="info-grid">
                                <div class="info-item full-width">
                                    <label>Street Address</label>
                                    <p><?php echo $address_street ? htmlspecialchars($address_street) : 'Not set'; ?></p>
                                </div>
                                <div class="info-item">
                                    <label>City</label>
                                    <p><?php echo $address_city ? htmlspecialchars($address_city) : 'Not set'; ?></p>
                                </div>
                                <div class="info-item">
                                    <label>State/Province</label>
                                    <p><?php echo $address_state ? htmlspecialchars($address_state) : 'Not set'; ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Postal Code</label>
                                    <p><?php echo $address_zip ? htmlspecialchars($address_zip) : 'Not set'; ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Account Settings Section -->
                <section class="profile-section" id="account-settings">
                    <div class="section-header">
                        <h2><i class="fas fa-cog"></i> Account Settings</h2>
                    </div>
                    <div class="section-content">
                        <!-- Password Change Form (Always visible, like admin style) -->
                        <div class="password-section">
                            <h4 style="margin-bottom: 1.5rem; color: var(--text-color);">
                                <i class="fas fa-key" style="color: var(--primary-color);"></i> Change Password
                            </h4>

                            <?php if ($passwordMessage): ?>
                                <div class="alert alert-success" style="margin-bottom: 1rem;">
                                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($passwordMessage); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($passwordError): ?>
                                <div class="alert alert-error" style="margin-bottom: 1rem;">
                                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($passwordError); ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="profile.php" class="password-form">
                                <div class="info-grid">
                                    <div class="info-item">
                                        <label>Current Password</label>
                                        <div class="input-with-icon">
                                            <input type="password" name="current_password" required>
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <label>New Password</label>
                                        <div class="input-with-icon">
                                            <input type="password" name="new_password" required minlength="8">
                                        </div>
                                        <small style="color: var(--text-light); font-size: 0.85rem;">Minimum 8 characters</small>
                                    </div>
                                    <div class="info-item">
                                        <label>Confirm New Password</label>
                                        <div class="input-with-icon">
                                            <input type="password" name="confirm_password" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="section-actions" style="justify-content: flex-start; margin-top: 1.5rem;">
                                    <button type="submit" class="btn-primary" name="change_password">
                                        <i class="fas fa-save"></i> Update Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>

                <!-- Recent Bookings Section -->
                <section class="profile-section" id="booking-history">
                    <div class="section-header">
                        <h2><i class="fas fa-calendar-check"></i> Recent Bookings</h2>
                        <a href="bookings.php" class="view-all-link">View All</a>
                    </div>
                    <div class="section-content">
                        <div class="booking-history">
                            <?php if (empty($bookings)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-calendar-check empty-icon"></i>
                                    <h3>No Bookings Yet</h3>
                                    <p>You haven't made any bookings yet. Start by booking our cleaning services!</p>
                                    <a href="services.php" class="btn-primary">
                                        <i class="fas fa-calendar-plus"></i> Book Services
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="bookings-list">
                                    <?php foreach (array_slice($bookings, 0, 3) as $booking): ?>
                                        <div class="booking-card status-<?php echo strtolower($booking['Status'] ?? 'requested'); ?>">
                                            <div class="booking-card-header">
                                                <div class="booking-service-info">
                                                    <h4 class="booking-service-name">
                                                        <?php echo htmlspecialchars($booking['Service_Name'] ?? 'Service Booking'); ?>
                                                    </h4>
                                                    <span class="booking-date">
                                                        <i class="fas fa-calendar"></i>
                                                        <?php echo date('F j, Y', strtotime($booking['Date'])); ?>
                                                    </span>
                                                </div>
                                                <span class="booking-status status-<?php echo strtolower($booking['Status'] ?? 'requested'); ?>">
                                                    <?php echo ucfirst($booking['Status'] ?? 'Requested'); ?>
                                                </span>
                                            </div>
                                            <div class="booking-footer">
                                                <span class="booking-type">
                                                    <i class="fas fa-file-invoice-dollar"></i> Quotation
                                                </span>
                                                <div class="booking-actions">
                                                    <?php if (!empty($booking['Quoted_Amount']) && $booking['Quoted_Amount'] > 0): ?>
                                                        <a href="api/generate_quote_pdf.php?id=<?php echo $booking['ID']; ?>" target="_blank" class="btn-outline-small">
                                                            <i class="fas fa-file-pdf"></i> Download
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="bookings.php?view=<?php echo $booking['ID']; ?>" class="btn-outline-small">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>



            </div>
        </div>
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
    <script src="./js/form-validation.js"></script>
    <script>
        // Customer notification dropdown functionality
        function toggleCustomerNotificationDropdown() {
            const dropdown = document.getElementById('notificationDropdownCustomer');
            if (dropdown) {
                dropdown.classList.toggle('active');
            }
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const notificationBell = document.querySelector('.notification-bell-customer');
            if (notificationBell && !notificationBell.contains(event.target)) {
                const dropdown = document.getElementById('notificationDropdownCustomer');
                if (dropdown) {
                    dropdown.classList.remove('active');
                }
            }
        });

        // Mark individual notification as read (for dropdown)
        async function markCustomerNotificationRead(notificationId) {
            try {
                const response = await fetch('api/notification_actions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'mark_read', notification_id: notificationId })
                });
                const result = await response.json();
                if (result.success) {
                    const notifItem = document.querySelector(`[data-id="${notificationId}"]`);
                    if (notifItem) {
                        notifItem.style.opacity = '0';
                        setTimeout(() => notifItem.remove(), 300);
                    }
                    const badge = document.querySelector('.notification-badge-customer');
                    if (badge) {
                        const currentCount = parseInt(badge.textContent);
                        if (currentCount > 1) {
                            badge.textContent = currentCount - 1;
                        } else {
                            badge.remove();
                        }
                    }
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        // Mark all notifications as read (for dropdown)
        async function markAllCustomerNotificationsRead() {
            try {
                const response = await fetch('api/notification_actions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'mark_all_read' })
                });
                const result = await response.json();
                if (result.success) {
                    const dropdownContent = document.querySelector('.notification-dropdown-customer .notification-dropdown-content');
                    if (dropdownContent) {
                        dropdownContent.innerHTML = '<div class="no-notifications"><i class="fas fa-bell-slash"></i><p>No new notifications</p></div>';
                    }
                    const badge = document.querySelector('.notification-badge-customer');
                    if (badge) {
                        badge.remove();
                    }
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        // Mark individual notification as read (for page notifications)
        function markAsRead(notificationId) {
            fetch('api/notification_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'mark_read',
                        notification_id: notificationId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const notifItem = document.querySelector(`[data-id="${notificationId}"]`);
                        if (notifItem) {
                            notifItem.style.opacity = '0';
                            setTimeout(() => notifItem.remove(), 300);
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Mark all notifications as read
        function markAllAsRead(event) {
            event.preventDefault();
            fetch('api/notification_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'mark_all_read'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const notifSection = document.querySelector('.notification-section');
                        if (notifSection) {
                            notifSection.style.opacity = '0';
                            setTimeout(() => notifSection.remove(), 300);
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        // Phone number input validation - only digits, max 10 chars
        document.addEventListener('DOMContentLoaded', function() {
            const phoneInput = document.getElementById('profile-phone');
            if (phoneInput) {
                phoneInput.addEventListener('input', function(e) {
                    // Remove all non-digit characters
                    let value = this.value.replace(/\D/g, '');
                    // Limit to 10 digits
                    if (value.length > 10) {
                        value = value.substring(0, 10);
                    }
                    this.value = value;
                });
                
                // Prevent paste of non-digits
                phoneInput.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pasted = (e.clipboardData || window.clipboardData).getData('text');
                    const digitsOnly = pasted.replace(/\D/g, '').substring(0, 10);
                    this.value = digitsOnly;
                });
            }
            
            // Smooth scroll to anchor section without glitch
            if (window.location.hash) {
                const targetId = window.location.hash.substring(1);
                const targetElement = document.getElementById(targetId);
                if (targetElement) {
                    // Small delay to ensure page is fully rendered
                    setTimeout(function() {
                        targetElement.scrollIntoView({ 
                            behavior: 'smooth', 
                            block: 'start' 
                        });
                        // Add highlight effect to show which section was targeted
                        targetElement.style.transition = 'box-shadow 0.5s ease';
                        targetElement.style.boxShadow = '0 0 20px rgba(13, 110, 253, 0.3)';
                        setTimeout(function() {
                            targetElement.style.boxShadow = '';
                        }, 2000);
                    }, 100);
                }
            }
        });
    </script>
</body>

</html>
