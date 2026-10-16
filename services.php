<?php
// Start session and include database connection
require_once 'includes/config_session.inc.php';
require_once 'includes/dbh.inc.php';

// Get user data if logged in
$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? '';
$user_role = $_SESSION['user_role'] ?? '';

// Check user login status
$is_logged_in = isset($_SESSION['user_id']);
$is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
$is_customer = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'customer';

// Fetch notifications for customer
$notifications = [];
$unreadCount = 0;
if ($is_customer && $user_id) {
    require_once 'includes/notifications_model.inc.php';
    $notifications = get_notifications($pdo, $user_id, 'customer', true, 5);
    $unreadCount = get_unread_notification_count($pdo, $user_id, 'customer');
}

// Fetch user profile data for form pre-population
$user_email = '';
$user_phone = '';
$user_address_street = '';
$user_address_city = '';
$user_address_state = '';
$user_address_postal = '';

if ($user_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE ID = ?");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user_data) {
            $user_email = $user_data['Email'] ?? '';
            $user_phone = $user_data['Phone'] ?? '';

            // Parse address from single field (comma-separated: Street, City, State, Postal)
            $address = $user_data['Address'] ?? '';
            if ($address) {
                $address_parts = array_map('trim', explode(',', $address));
                $user_address_street = $address_parts[0] ?? '';
                $user_address_city = $address_parts[1] ?? '';
                $user_address_state = $address_parts[2] ?? '';
                $user_address_postal = $address_parts[3] ?? '';

                // Handle old format where state and postal might be separated by space instead of comma
                if (empty($user_address_postal) && !empty($user_address_state)) {
                    $state_parts = preg_split('/\s+/', $user_address_state);
                    if (count($state_parts) >= 2) {
                        $user_address_state = $state_parts[0];
                        $user_address_postal = end($state_parts);
                    }
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Error fetching user data: " . $e->getMessage());
    }
}

// Fetch all services including unavailable ones
try {
    $stmt = $pdo->prepare("
        SELECT * FROM services 
        ORDER BY Category, Name
    ");
    $stmt->execute();
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching services: " . $e->getMessage());
    $services = [];
}

// Get unique categories for filter
$categories = [];
foreach ($services as $service) {
    $cat = $service['Category'] ?? 'General';
    if (!in_array($cat, $categories)) {
        $categories[] = $cat;
    }
}

// Handle search and filters
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? 'all';
$sort = $_GET['sort'] ?? 'default';

// Filter services
$filtered_services = $services;
if (!empty($search)) {
    $filtered_services = array_filter($filtered_services, function ($s) use ($search) {
        return stripos($s['Name'], $search) !== false ||
            stripos($s['Description'], $search) !== false;
    });
}

if ($category_filter !== 'all') {
    $filtered_services = array_filter($filtered_services, function ($s) use ($category_filter) {
        return ($s['Category'] ?? 'General') === $category_filter;
    });
}

// Sort services
switch ($sort) {
    case 'name':
        usort($filtered_services, function ($a, $b) {
            return strcmp($a['Name'], $b['Name']);
        });
        break;
    case 'duration':
        usort($filtered_services, function ($a, $b) {
            return ($a['Duration'] ?? 0) - ($b['Duration'] ?? 0);
        });
        break;
}

// Handle booking form submission
$bookingMessage = '';
$bookingError = '';
$selected_service = null;

if (isset($_GET['book']) && is_numeric($_GET['book'])) {
    $service_id = (int)$_GET['book'];
    // Find the selected service
    foreach ($services as $service) {
        if ($service['ID'] == $service_id) {
            $selected_service = $service;
            break;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_quotation'])) {
    $service_id = (int)($_POST['service_id'] ?? 0);
    $property_type = trim($_POST['property_type'] ?? '');
    $cleaning_frequency = trim($_POST['cleaning_frequency'] ?? '');
    $booking_date = trim($_POST['booking_date'] ?? '');
    $booking_time = trim($_POST['booking_time'] ?? '');
    $address_street = trim($_POST['address_street'] ?? '');
    $address_city = trim($_POST['address_city'] ?? '');
    $address_state = trim($_POST['address_state'] ?? '');
    $address_postal = trim($_POST['address_postal'] ?? '');
    $special_requests = trim($_POST['special_requests'] ?? '');
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $customer_email = trim($_POST['customer_email'] ?? '');

    // Validation
    $errors = [];
    if (empty($property_type)) $errors[] = "Property type is required.";
    if (empty($cleaning_frequency)) $errors[] = "Service frequency is required.";
    if (empty($booking_date)) $errors[] = "Preferred date is required.";
    if (empty($booking_time)) $errors[] = "Preferred time is required.";
    // Strict validation for all required fields
    if (empty($address_street) || trim($address_street) === '') {
        $errors[] = "Street address is required.";
    }
    if (empty($address_city) || trim($address_city) === '') {
        $errors[] = "City is required.";
    }
    if (empty($address_state) || trim($address_state) === '') {
        $errors[] = "State/Province is required.";
    }
    if (empty($address_postal) || trim($address_postal) === '') {
        $errors[] = "Postal code is required.";
    }
    if (empty($customer_name) || trim($customer_name) === '') {
        $errors[] = "Full name is required.";
    }
    if (empty($customer_phone)) {
        $errors[] = "Phone number is required.";
    }
    if (empty($customer_email)) {
        $errors[] = "Email address is required.";
    }

    // Strict email validation
    if (!empty($customer_email)) {
        if (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address (e.g., name@example.com).";
        } elseif (strlen($customer_email) > 254) {
            $errors[] = "Email address is too long.";
        }
    }

    // Strict phone validation - exactly 10 digits, only numbers, South African format
    if (!empty($customer_phone)) {
        // Remove all non-digit characters
        $phone = preg_replace('/\D/', '', $customer_phone);

        if (!ctype_digit($phone)) {
            $errors[] = "Phone number must contain only digits (0-9), no letters or special characters.";
        } elseif (strlen($phone) !== 10) {
            $errors[] = "Phone number must be exactly 10 digits.";
        } elseif (!preg_match('/^0[0-9]{9}$/', $phone)) {
            $errors[] = "Phone number must start with 0 followed by 9 digits (e.g., 0821234567).";
        }
    }

    // Postal code validation (4 digits)
    if (!empty($address_postal)) {
        $postal = preg_replace('/\D/', '', $address_postal);
        if (!ctype_digit($postal) || strlen($postal) !== 4) {
            $errors[] = "Postal code must be exactly 4 digits (e.g., 2000).";
        }
    }

    // Date/Time validation
    if (empty($errors)) {
        $today = date('Y-m-d');
        $current_time = date('H:i');
        $current_hour = (int)date('H');
        $booking_timestamp = strtotime($booking_date . ' ' . $booking_time);
        $current_timestamp = time();

        // Check if booking date is in the past
        if ($booking_date < $today) {
            $errors[] = "Cannot book for past dates. Please select today or a future date.";
        }

        // Check if booking time has already passed
        if ($booking_date === $today && $booking_time <= $current_time) {
            $errors[] = "Cannot book for past times. Please select a future time.";
        }

        // Same-day booking restriction: must book before 12 PM
        if ($booking_date === $today && $current_hour >= 12) {
            $tomorrow = date('Y-m-d', strtotime('+1 day'));
            $errors[] = "Same-day bookings must be made before 12:00 PM. Please select {$tomorrow} or later.";
        }
    }

    if (empty($errors)) {
        try {
            // Check for duplicate booking (same customer, same service, same day)
            $check_stmt = $pdo->prepare("
                SELECT COUNT(*) FROM bookings 
                WHERE Customer_ID = ? AND Service_ID = ? AND Date = ?
            ");
            $check_stmt->execute([$user_id, $service_id, $booking_date]);
            $existing_count = $check_stmt->fetchColumn();

            if ($existing_count > 0) {
                $_SESSION['booking_error'] = "You have already booked this service for " . date('F j, Y', strtotime($booking_date)) . ". Please select a different date or service.";
                // Re-select the service to show form again with errors
                foreach ($services as $s) {
                    if ($s['ID'] == $service_id) {
                        $selected_service = $s;
                        break;
                    }
                }
            } else {
                // Format address
                $formatted_address = implode(', ', array_filter([$address_street, $address_city, $address_state, $address_postal]));

                // Create booking
                $stmt = $pdo->prepare("
                    INSERT INTO bookings 
                    (Customer_ID, Service_ID, Date, Time, Status, Address, Special_Instructions, Property_Type, Cleaning_Frequency, Duration, created_at) 
                    VALUES (?, ?, ?, ?, 'requested', ?, ?, ?, ?, ?, NOW())
                ");

                // Get duration from service
                $duration = 60; // default
                foreach ($services as $s) {
                    if ($s['ID'] == $service_id) {
                        $duration = $s['Duration'] ?? 60;
                        break;
                    }
                }

                $success = $stmt->execute([
                    $user_id,
                    $service_id,
                    $booking_date,
                    $booking_time,
                    $formatted_address,
                    $special_requests,
                    $property_type,
                    $cleaning_frequency,
                    $duration
                ]);

                if ($success) {
                    // Update customer profile with phone and address from booking
                    if ($user_id) {
                        try {
                            // Clean phone number - digits only
                            $clean_phone = preg_replace('/\D/', '', $customer_phone);
                            // Format address as comma-separated string
                            $profile_address = trim($address_street . ', ' .
                                $address_city . ', ' .
                                $address_state . ', ' .
                                $address_postal);

                            $update_stmt = $pdo->prepare("
                                UPDATE customers 
                                SET Phone = :phone, Address = :address, updatedAt = NOW()
                                WHERE ID = :id
                            ");
                            $update_stmt->execute([
                                ':phone' => $clean_phone,
                                ':address' => $profile_address,
                                ':id' => $user_id
                            ]);
                        } catch (PDOException $e) {
                            // Log error but don't fail booking if profile update fails
                            error_log("Failed to update customer profile after booking in services.php: " . $e->getMessage());
                        }
                    }

                    // Store success message in session for display after redirect
                    $_SESSION['booking_message'] = "Your quotation request has been submitted successfully! We will contact you within 24 hours. Redirecting to your bookings...";
                    $_SESSION['booking_status'] = 'success';

                    // Redirect to services page to show message, then redirect to bookings
                    header("Location: services.php?booked=1");
                    exit();
                } else {
                    $bookingError = "Failed to submit request. Please try again.";
                }
            }
        } catch (PDOException $e) {
            error_log("Booking creation error: " . $e->getMessage());
            $bookingError = "Failed to submit request. Please try again.";
        }
    } else {
        $bookingError = implode(" ", $errors);
        // Re-select the service to show form again with errors
        foreach ($services as $s) {
            if ($s['ID'] == $service_id) {
                $selected_service = $s;
                break;
            }
        }
    }
}

// Check for session messages from successful booking
if (isset($_SESSION['booking_message'])) {
    $bookingMessage = $_SESSION['booking_message'];
    unset($_SESSION['booking_message']);
}
if (isset($_SESSION['booking_status'])) {
    $bookingStatus = $_SESSION['booking_status'];
    unset($_SESSION['booking_status']);
}
if (isset($_SESSION['booking_error'])) {
    $bookingError = $_SESSION['booking_error'];
    unset($_SESSION['booking_error']);
}

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
    <title>Our Services | Phambili Ma-Africa</title>
    <link rel="icon" type="image/png" sizes="32x32" href="images/PM-Logo.jpg">

    <!-- Open Graph Meta Tags for Social Media Preview -->
    <meta property="og:title" content="Our Services | Phambili Ma-Africa">
    <meta property="og:description" content="Explore our professional cleaning services at Phambili Ma-Africa. Home and business cleaning solutions.">
    <meta property="og:image" content="images/PM-Logo.jpg">
    <meta property="og:url" content="https://phambili-ma-africa.com/services.php">
    <meta property="og:type" content="website">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Our Services | Phambili Ma-Africa">
    <meta name="twitter:description" content="Explore our professional cleaning services at Phambili Ma-Africa. Home and business cleaning solutions.">
    <meta name="twitter:image" content="images/PM-Logo.jpg">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./css/style.css">
    <link rel="stylesheet" href="./css/responsiveness.css">
    <link rel="stylesheet" href="./css/social-media.css">
</head>

<body class="services-page" data-is-logged-in="<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>">
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

    <main id="main-content">
        <!-- Services Header Section -->
        <section class="services-banner">
            <h1 id="services-title">Our Services</h1>
            <p>Discover the range of professional cleaning solutions we offer to make your space spotless.</p>
        </section>

        <section class="section" aria-labelledby="services-title">
            <div class="services-container">

                <!-- Messages -->
                <?php if ($bookingMessage): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($bookingMessage); ?>
                    </div>
                <?php endif; ?>

                <!-- Enhanced Services Controls -->
                <div class="services-controls">
                    <form method="GET" action="services.php" class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Search services by name or description..."
                            value="<?php echo htmlspecialchars($search); ?>" aria-label="Search services">
                    </form>

                    <div class="filters-container">
                        <form method="GET" action="services.php" class="filter-form">
                            <div class="filter-group">
                                <label for="category" class="filter-label">Category</label>
                                <select name="category" id="category" onchange="this.form.submit()">
                                    <option value="all">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>"
                                            <?php echo $category_filter === $cat ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label for="sort" class="filter-label">Sort By</label>
                                <select name="sort" id="sort" onchange="this.form.submit()">
                                    <option value="default" <?php echo $sort === 'default' ? 'selected' : ''; ?>>Featured</option>
                                    <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name A-Z</option>
                                    <option value="duration" <?php echo $sort === 'duration' ? 'selected' : ''; ?>>Duration</option>
                                </select>
                            </div>

                            <?php if ($search): ?>
                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <div class="services-results-info">
                    <div class="my-bookings-link">
                        <a href="bookings.php" class="btn-bookings">View Bookings →</a>
                    </div>
                    <p id="resultsCount"><?php echo count($filtered_services); ?> service<?php echo count($filtered_services) !== 1 ? 's' : ''; ?> available</p>
                    <div class="results-stats">
                        <span class="stat-item">
                            <i class="fas fa-sync-alt"></i>
                            <span id="lastUpdated">Live data</span>
                        </span>
                    </div>
                </div>

                <!-- Dynamic Services Grid -->
                <div class="services-grid" id="servicesGrid">
                    <?php if (empty($filtered_services)): ?>
                        <div class="no-results" id="noResults" style="display: block;">
                            <div class="no-results-content">
                                <i class="fas fa-search"></i>
                                <h3>No Services Found</h3>
                                <p>We couldn't find any services matching your criteria. Try adjusting your search or filters.</p>
                                <a href="services.php" class="btn btn-primary">
                                    <i class="fas fa-refresh"></i> Reset Filters
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($filtered_services as $service):
                            $duration = $service['Duration'] ?? 60;
                            $duration_text = $duration >= 60 ? floor($duration / 60) . 'h ' . ($duration % 60) . 'm' : $duration . ' min';
                            $badge_class = '';
                            if (strtolower($service['Name'] ?? '') === 'deep cleaning') $badge_class = 'popular';
                            elseif (strtolower($service['Name'] ?? '') === 'office cleaning') $badge_class = 'trending';
                        ?>
                            <div class="service-card <?php echo ($service['Is_Available'] ?? 1) == 0 ? 'service-unavailable' : ''; ?>">
                                <?php if (($service['Is_Available'] ?? 1) == 0): ?>
                                    <span class="service-badge unavailable">Unavailable</span>
                                <?php elseif ($badge_class): ?>
                                    <span class="service-badge <?php echo $badge_class; ?>"><?php echo ucfirst($badge_class); ?></span>
                                <?php endif; ?>
                                <div class="service-image-container">
                                    <?php if (!empty($service['Image_URL'])): ?>
                                        <img src="<?php echo htmlspecialchars($service['Image_URL']); ?>"
                                            alt="<?php echo htmlspecialchars($service['Name']); ?>">
                                        <div class="image-placeholder" style="display: none;">
                                            <i class="fas fa-broom"></i>
                                        </div>
                                    <?php else: ?>
                                        <div class="service-image-placeholder">
                                            <i class="fas fa-broom"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="service-content">
                                    <h3><?php echo htmlspecialchars($service['Name']); ?></h3>
                                    <p><?php echo htmlspecialchars($service['Description'] ?? 'Professional cleaning service'); ?></p>

                                    <div class="service-features">
                                        <span class="feature-tag">
                                            <i class="fas fa-clock"></i> <?php echo $duration_text; ?>
                                        </span>
                                        <span class="feature-tag">
                                            <i class="fas fa-star"></i> Professional
                                        </span>
                                        <span class="feature-tag">
                                            <i class="fas fa-shield-alt"></i> Insured
                                        </span>
                                    </div>

                                    <div class="service-footer">
                                        <div class="price-container">
                                            <span class="price-note">Custom Quote</span>
                                        </div>
                                        <?php if (($service['Is_Available'] ?? 1) == 0): ?>
                                            <span class="service-cta-btn service-unavailable-btn">
                                                <i class="fas fa-ban"></i> Currently Unavailable
                                            </span>
                                        <?php elseif ($user_id): ?>
                                            <a href="services.php?book=<?php echo $service['ID']; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $category_filter !== 'all' ? '&category=' . urlencode($category_filter) : ''; ?>"
                                                class="service-cta-btn">
                                                Book Now
                                            </a>
                                        <?php else: ?>
                                            <a href="javascript:void(0)" onclick="handleBookNow(<?php echo $service['ID']; ?>)"
                                                class="service-cta-btn">
                                                Book Now
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Enhanced Service Booking Popup -->
                <?php if ($selected_service && $user_id): ?>
                    <div id="service-popup" class="service-popup active" role="dialog" aria-modal="true">
                        <div class="popup-overlay"></div>
                        <div class="popup-container">
                            <a href="services.php<?php echo $search ? '?search=' . urlencode($search) : ''; ?><?php echo $category_filter !== 'all' ? ($search ? '&' : '?') . 'category=' . urlencode($category_filter) : ''; ?>"
                                class="popup-close" aria-label="Close popup">
                                <i class="fas fa-times"></i>
                            </a>

                            <div class="popup-content">
                                <!-- Left Side - Service Image & Info -->
                                <div class="popup-image">
                                    <div class="popup-image-content">
                                        <?php if (!empty($selected_service['Image_URL'])): ?>
                                            <img src="<?php echo htmlspecialchars($selected_service['Image_URL']); ?>"
                                                alt="<?php echo htmlspecialchars($selected_service['Name']); ?>">
                                        <?php else: ?>
                                            <div class="image-placeholder">
                                                <i class="fas fa-concierge-bell"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="popup-image-info">
                                            <h3><?php echo htmlspecialchars($selected_service['Name']); ?></h3>
                                            <div class="service-features">
                                                <span class="feature-tag">
                                                    <i class="fas fa-clock"></i>
                                                    <?php
                                                    $duration = $selected_service['Duration'] ?? 60;
                                                    echo $duration >= 60 ? floor($duration / 60) . 'h ' . ($duration % 60) . 'm' : $duration . ' min';
                                                    ?>
                                                </span>
                                                <span class="feature-tag">
                                                    <i class="fas fa-star"></i> Professional Service
                                                </span>
                                                <span class="feature-tag">
                                                    <i class="fas fa-shield-alt"></i> Insured & Certified
                                                </span>
                                            </div>
                                        </div>
                                        <!-- Service Description Below Image -->
                                        <div class="popup-description-container">
                                            <div class="description-header">
                                                <i class="fas fa-info-circle"></i>
                                                <h4>Service Description</h4>
                                            </div>
                                            <p class="popup-description">
                                                <?php echo htmlspecialchars($selected_service['Description'] ?? 'Professional cleaning service tailored to your needs. Our experienced team uses eco-friendly products and advanced techniques to deliver exceptional results every time.'); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Side - Booking Form -->
                                <div class="popup-details">
                                    <div class="popup-header">
                                        <h2>Request Quotation</h2>

                                        <!-- Welcome Message at the top of the form -->
                                        <div class="welcome-message">
                                            <div class="welcome-header">
                                                <i class="fas fa-comments"></i>
                                                <h3>Hi there,</h3>
                                            </div>
                                            <div class="welcome-content">
                                                <p>Thank you for connecting through our online booking system!</p>
                                                <p>After you have submitted this, you will receive confirmation where you will select a date & time for a virtual consultation to discuss your needs, view your space, provide you a quote, and get you booked in with us!</p>
                                                <div class="signature">
                                                    <p><strong>Thank you,</strong></p>
                                                    <p class="company-name">Phambili Ma-Africa - Trading Enterprise</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <form method="POST" action="services.php" id="service-booking-form" class="booking-form">
                                        <input type="hidden" name="service_id" value="<?php echo $selected_service['ID']; ?>">

                                        <!-- Service Details Section -->
                                        <div class="form-section">
                                            <h3 class="form-section-title">
                                                <i class="fas fa-clipboard-list"></i> Service Details
                                            </h3>
                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label for="property_type">Property Type</label>
                                                    <select id="property_type" name="property_type" required>
                                                        <option value="" disabled selected>Select property type</option>
                                                        <option value="house">House</option>
                                                        <option value="apartment">Apartment</option>
                                                        <option value="office">Office</option>a
                                                        <option value="commercial">Commercial Space</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="cleaning_frequency">Service Frequency</label>
                                                    <select id="cleaning_frequency" name="cleaning_frequency" required>
                                                        <option value="" disabled selected>Select frequency</option>
                                                        <option value="once">One-time</option>
                                                        <option value="weekly">Weekly</option>
                                                        <option value="biweekly">Bi-weekly</option>
                                                        <option value="monthly">Monthly</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Schedule Section -->
                                        <div class="form-section">
                                            <h3 class="form-section-title">
                                                <i class="fas fa-calendar-alt"></i> Schedule
                                            </h3>
                                            <div class="date-time-group">
                                                <div class="form-group">
                                                    <label for="booking_date">Preferred Date</label>
                                                    <input type="date" id="booking_date" name="booking_date"
                                                        min="<?php echo date('Y-m-d'); ?>" required>
                                                    <input type="hidden" id="service_id" name="service_id" value="<?php echo $selected_service['ID']; ?>">
                                                    <div id="service-calendar-container"></div>
                                                </div>
                                                <div class="form-group">
                                                    <label for="booking_time">Preferred Time</label>
                                                    <select id="booking_time" name="booking_time" required>
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
                                                        <option value="17:00">05:00 PM</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Address Section -->
                                        <div class="form-section">
                                            <h3 class="form-section-title">
                                                <i class="fas fa-map-marker-alt"></i> Service Location
                                            </h3>
                                            <div class="form-group">
                                                <label for="address_street">Street Address</label>
                                                <input type="text" id="address_street" name="address_street"
                                                    value="<?php echo htmlspecialchars($user_address_street); ?>" placeholder="Enter your street address" required>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label for="address_city">City</label>
                                                    <input type="text" id="address_city" name="address_city"
                                                        value="<?php echo htmlspecialchars($user_address_city); ?>" placeholder="City" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="address_state">State</label>
                                                    <input type="text" id="address_state" name="address_state"
                                                        value="<?php echo htmlspecialchars($user_address_state); ?>" placeholder="State" required>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="address_postal">Postal Code</label>
                                                <input type="text" id="address_postal" name="address_postal"
                                                    value="<?php echo htmlspecialchars($user_address_postal); ?>" placeholder="Postal Code" required>
                                            </div>
                                        </div>

                                        <!-- Additional Information -->
                                        <div class="form-section">
                                            <h3 class="form-section-title">
                                                <i class="fas fa-edit"></i> Additional Information
                                            </h3>
                                            <div class="form-group">
                                                <label for="special_requests">Special Requirements</label>
                                                <textarea id="special_requests" name="special_requests"
                                                    placeholder="Any specific requirements, access instructions, or additional information that will help us provide better service..."></textarea>
                                            </div>
                                        </div>

                                        <!-- Contact Information -->
                                        <div class="form-section">
                                            <h3 class="form-section-title">
                                                <i class="fas fa-user"></i> Your Information
                                            </h3>
                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label for="customer_name">Full Name</label>
                                                    <input type="text" id="customer_name" name="customer_name"
                                                        value="<?php echo htmlspecialchars($user_name); ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="customer_phone">Phone Number</label>
                                                    <input type="tel" id="customer_phone" name="customer_phone"
                                                        value="<?php echo htmlspecialchars($user_phone); ?>" required>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="customer_email">Email Address</label>
                                                <input type="email" id="customer_email" name="customer_email"
                                                    value="<?php echo htmlspecialchars($user_email); ?>" required>
                                            </div>
                                        </div>

                                        <!-- Form Actions -->
                                        <div class="form-actions">
                                            <a href="services.php<?php echo $search ? '?search=' . urlencode($search) : ''; ?><?php echo $category_filter !== 'all' ? ($search ? '&' : '?') . 'category=' . urlencode($category_filter) : ''; ?>"
                                                class="btn-cancel">Cancel</a>
                                            <button type="submit" name="request_quotation" class="submit-btn">
                                                <i class="fas fa-paper-plane"></i> Request Quotation
                                            </button>
                                        </div>
                                        <p class="form-note">
                                            <i class="fas fa-info-circle"></i>
                                            You'll receive a custom quotation within 24 hours. No payment required at this stage.
                                        </p>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Social Media Section -->
    <section class="social-media-section">
        <div class="container">
            <div class="social-media-content">
                <div class="social-media-header">
                    <h2>Connect With Us</h2>
                    <p>Follow us on social media for cleaning tips, updates, and special offers!</p>
                </div>

                <div class="social-media-grid">
                    <a href="https://www.facebook.com/people/Phambili-Ma-Africa/100063507009282/" target="_blank" class="social-card facebook">
                        <div class="social-icon">
                            <i class="fab fa-facebook-f"></i>
                        </div>
                        <div class="social-info">
                            <h3>Facebook</h3>
                            <p>Like our page for updates and cleaning tips</p>
                            <span class="social-handle">@PhambiliMaAfrica</span>
                        </div>
                        <div class="social-arrow">
                            <i class="fas fa-external-link-alt"></i>
                        </div>
                    </a>

                    <a href="https://www.instagram.com/phambilimaafrica" target="_blank" class="social-card instagram">
                        <div class="social-icon">
                            <i class="fab fa-instagram"></i>
                        </div>
                        <div class="social-info">
                            <h3>Instagram</h3>
                            <p>See our work in action and before/after photos</p>
                            <span class="social-handle">@phambilimaafrica</span>
                        </div>
                        <div class="social-arrow">
                            <i class="fas fa-external-link-alt"></i>
                        </div>
                    </a>

                    <a href="https://www.linkedin.com/in/phambili-ma-africa-8256a4151" target="_blank" class="social-card linkedin">
                        <div class="social-icon">
                            <i class="fab fa-linkedin-in"></i>
                        </div>
                        <div class="social-info">
                            <h3>LinkedIn</h3>
                            <p>Connect with us professionally</p>
                            <span class="social-handle">Phambili Ma-Africa</span>
                        </div>
                        <div class="social-arrow">
                            <i class="fas fa-external-link-alt"></i>
                        </div>
                    </a>

                    <a href="https://www.tiktok.com/@user7297605414365" target="_blank" class="social-card tiktok">
                        <div class="social-icon">
                            <i class="fab fa-tiktok"></i>
                        </div>
                        <div class="social-info">
                            <h3>TikTok</h3>
                            <p>Watch quick cleaning tips and tricks</p>
                            <span class="social-handle">@user7297605414365</span>
                        </div>
                        <div class="social-arrow">
                            <i class="fas fa-external-link-alt"></i>
                        </div>
                    </a>
                </div>

                <div class="social-media-cta">
                    <p><strong>Follow us for exclusive offers and cleaning insights!</strong></p>
                </div>
            </div>
        </div>
    </section>

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
    <script src="./js/booking-calendar.js"></script>
    <script>
        // Handle Book Now button click for non-logged-in users
        function handleBookNow(serviceId) {
            // Check if user is logged in
            if (!<?php echo $is_logged_in ? 'true' : 'false'; ?>) {
                showNotification('Please sign in to book services', 'error');
                // Redirect to login page after a short delay
                setTimeout(() => {
                    window.location.href = 'login.php?redirect=services.php&book=' + serviceId;
                }, 1500);
                return;
            }
        }

        // Show notification function
        function showNotification(message, type = 'success') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = 'notification ' + type;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <span>${message}</span>
            `;

            // Add to page
            document.body.appendChild(notification);

            // Show notification
            setTimeout(() => {
                notification.classList.add('show');
            }, 10);

            // Hide and remove after 3 seconds
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.remove();
                }, 400);
            }, 3000);
        }

        // Check if user just booked a service and redirect to bookings after showing message
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('booked') === '1') {
                // Show success message
                setTimeout(() => {
                    window.location.href = 'bookings.php';
                }, 3000);
            }

            // Check for booking error and show as notification
            <?php if ($bookingError): ?>
                showNotification('<?php echo htmlspecialchars($bookingError); ?>', 'error');
            <?php endif; ?>
        });

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

        // Mark individual notification as read
        async function markCustomerNotificationRead(notificationId) {
            try {
                const response = await fetch('api/notification_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'mark_read',
                        notification_id: notificationId
                    })
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

        // Mark all notifications as read
        async function markAllCustomerNotificationsRead() {
            try {
                const response = await fetch('api/notification_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'mark_all_read'
                    })
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

        // Real-time booking form validation
        document.addEventListener('DOMContentLoaded', function() {
            const bookingDateInput = document.getElementById('booking_date');
            const bookingTimeSelect = document.getElementById('booking_time');
            const form = document.getElementById('booking-form');

            // Phone number input filtering - only allow digits, max 10 chars
            const phoneInput = document.getElementById('customer_phone');
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

            if (bookingDateInput && bookingTimeSelect) {
                // Get current date/time
                const now = new Date();
                const today = now.toISOString().split('T')[0];
                const currentHour = now.getHours();

                // Set minimum date based on current time
                // If after 12 PM, minimum date is tomorrow
                if (currentHour >= 12) {
                    const tomorrow = new Date(now);
                    tomorrow.setDate(tomorrow.getDate() + 1);
                    bookingDateInput.min = tomorrow.toISOString().split('T')[0];
                } else {
                    bookingDateInput.min = today;
                }

                // Validate date selection
                bookingDateInput.addEventListener('change', function() {
                    validateBookingDateTime();
                });

                // Validate time selection
                bookingTimeSelect.addEventListener('change', function() {
                    validateBookingDateTime();
                });

                function validateBookingDateTime() {
                    const selectedDate = bookingDateInput.value;
                    const selectedTime = bookingTimeSelect.value;

                    // Remove existing error messages
                    const existingError = document.getElementById('realtime-validation-error');
                    if (existingError) {
                        existingError.remove();
                    }

                    if (!selectedDate) return;

                    const now = new Date();
                    const today = now.toISOString().split('T')[0];
                    const currentHour = now.getHours();
                    const currentTime = now.getHours() * 60 + now.getMinutes();

                    let errorMessage = '';

                    // Check if today is selected after 12 PM
                    if (selectedDate === today && currentHour >= 12) {
                        errorMessage = 'Same-day bookings must be made before 12:00 PM. Please select tomorrow or a later date.';
                    }

                    // Check if selected time has already passed today
                    if (selectedDate === today && selectedTime) {
                        const [hours, minutes] = selectedTime.split(':').map(Number);
                        const selectedTimeMinutes = hours * 60 + minutes;
                        if (selectedTimeMinutes <= currentTime) {
                            errorMessage = 'Cannot book for past times. Please select a future time.';
                        }
                    }

                    if (errorMessage) {
                        // Show error message
                        const errorDiv = document.createElement('div');
                        errorDiv.id = 'realtime-validation-error';
                        errorDiv.className = 'alert alert-error';
                        errorDiv.style.position = 'relative';
                        errorDiv.style.marginBottom = '1rem';
                        errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + errorMessage;

                        const formContent = form.querySelector('.form-content');
                        if (formContent) {
                            formContent.insertBefore(errorDiv, formContent.firstChild);
                        }

                        // Clear invalid selections
                        if (selectedDate === today && currentHour >= 12) {
                            bookingDateInput.value = '';
                        }
                    }
                }

                // Filter time options based on selected date
                function updateTimeOptions() {
                    const selectedDate = bookingDateInput.value;
                    if (!selectedDate) return;

                    const now = new Date();
                    const today = now.toISOString().split('T')[0];
                    const currentHour = now.getHours();
                    const currentTime = now.getHours() * 60 + now.getMinutes();

                    const options = bookingTimeSelect.querySelectorAll('option:not([value=""])');

                    options.forEach(option => {
                        const timeValue = option.value;
                        const [hours, minutes] = timeValue.split(':').map(Number);
                        const optionTimeMinutes = hours * 60 + minutes;

                        if (selectedDate === today) {
                            // Disable past times for today
                            if (optionTimeMinutes <= currentTime) {
                                option.disabled = true;
                                option.style.color = '#999';
                            } else {
                                option.disabled = false;
                                option.style.color = '';
                            }
                        } else {
                            // Enable all options for future dates
                            option.disabled = false;
                            option.style.color = '';
                        }
                    });
                }

                bookingDateInput.addEventListener('change', updateTimeOptions);
                updateTimeOptions(); // Initial call
            }
        });
    </script>
</body>

</html>
