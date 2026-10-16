<?php
require_once 'includes/config_session.inc.php';
require_once 'includes/dbh.inc.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'customer';
$user_name = $_SESSION['user_name'] ?? 'User';
function getRelativeDate($dateString)
{
    $date = new DateTime($dateString);
    $today = new DateTime('today');
    $yesterday = new DateTime('yesterday');
    $diff = $today->diff($date);
    $date->setTime(0, 0, 0);
    $today->setTime(0, 0, 0);
    $yesterday->setTime(0, 0, 0);
    if ($date == $today) return 'Today';
    elseif ($date == $yesterday) return 'Yesterday';
    elseif ($diff->days == 2) return 'Day before yesterday';
    elseif ($diff->days <= 7) return $date->format('l');
    else return $date->format('M d, Y');
}
try {
    $stmt = $pdo->prepare("SELECT b.*, s.Name as Service_Name, s.Description as Service_Description, s.Image_URL as Service_Image FROM bookings b LEFT JOIN services s ON b.Service_ID = s.ID WHERE b.Customer_ID = ? ORDER BY b.created_at DESC, b.Date DESC");
    $stmt->execute([$user_id]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching bookings: " . $e->getMessage());
    $bookings = [];
}
$filter_status = $_GET['status'] ?? 'all';
$filtered_bookings = $bookings;
if ($filter_status !== 'all') {
    $filtered_bookings = array_filter($bookings, function ($booking) use ($filter_status) {
        return strtolower($booking['Status'] ?? 'requested') === $filter_status;
    });
}
$status_counts = ['all' => count($bookings), 'requested' => 0, 'contacted' => 0, 'in_progress' => 0, 'quoted' => 0, 'confirmed' => 0, 'completed' => 0, 'cancelled' => 0, 'expired' => 0];
foreach ($bookings as $booking) {
    $status = strtolower($booking['Status'] ?? 'requested');
    if (isset($status_counts[$status])) $status_counts[$status]++;
}
$bookingMessage = '';
$bookingError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['booking_id'])) {
    $action = $_POST['action'];
    $booking_id = (int)$_POST['booking_id'];
    if ($action === 'cancel' || $action === 'decline') {
        try {
            $stmt = $pdo->prepare("SELECT Status FROM bookings WHERE ID = ? AND Customer_ID = ?");
            $stmt->execute([$booking_id, $user_id]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($booking) {
                $status = strtolower(trim($booking['Status']));
                if ($action === 'cancel' && ($status === 'requested' || $status === 'confirmed')) {
                    $stmt = $pdo->prepare("UPDATE bookings SET Status = 'cancelled' WHERE ID = ?");
                    $stmt->execute([$booking_id]);
                    require_once 'includes/notifications_model.inc.php';
                    $admins_query = "SELECT ID FROM admins";
                    $admins_stmt = $pdo->prepare($admins_query);
                    $admins_stmt->execute();
                    $admins = $admins_stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($admins as $admin) {
                        create_notification($pdo, $admin['ID'], 'admin', 'Booking Cancelled', "Booking #{$booking_id} has been cancelled by the customer.", 'booking');
                    }
                    echo "Booking cancelled successfully";
                    exit();
                } elseif ($action === 'decline' && $status === 'quoted') {
                    $stmt = $pdo->prepare("UPDATE bookings SET Status = 'cancelled' WHERE ID = ?");
                    $stmt->execute([$booking_id]);
                    echo "Quote declined successfully";
                    exit();
                } else {
                    echo "This booking cannot be cancelled";
                    exit();
                }
            } else {
                echo "Booking not found";
                exit();
            }
        } catch (PDOException $e) {
            error_log("Booking action error: " . $e->getMessage());
            echo "Database error";
            exit();
        }
    }
}
$view_booking_id = isset($_GET['view']) ? (int)$_GET['view'] : 0;
$view_booking = null;
if ($view_booking_id > 0) {
    foreach ($bookings as $booking) {
        if ($booking['ID'] == $view_booking_id) {
            $view_booking = $booking;
            break;
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_booking']) && isset($_POST['booking_id'])) {
    $booking_id = (int)$_POST['booking_id'];
    $new_date = trim($_POST['booking_date'] ?? '');
    $new_time = trim($_POST['booking_time'] ?? '');
    $new_address = trim($_POST['address'] ?? '');
    $new_instructions = trim($_POST['special_instructions'] ?? '');
    $is_ajax = isset($_POST['ajax']) && $_POST['ajax'] === 'true';
    try {
        $check_stmt = $pdo->prepare("SELECT * FROM bookings WHERE ID = ? AND Customer_ID = ? AND Status = 'requested'");
        $check_stmt->execute([$booking_id, $user_id]);
        $booking = $check_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$booking) {
            $error = "Quotation not found or cannot be edited. Only 'requested' quotations can be edited.";
            if ($is_ajax) {
                echo json_encode(['success' => false, 'message' => $error]);
                exit();
            }
            $bookingError = $error;
        } elseif (empty($new_date) || empty($new_time)) {
            $error = "Date and time are required.";
            if ($is_ajax) {
                echo json_encode(['success' => false, 'message' => $error]);
                exit();
            }
            $bookingError = $error;
        } else {
            $booking_timestamp = strtotime($new_date . ' ' . $new_time);
            $now = time();
            $current_hour = (int)date('H');
            if ($booking_timestamp < $now) {
                $error = "Cannot book for past dates or times.";
                if ($is_ajax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    exit();
                }
                $bookingError = $error;
            } elseif ($new_date === date('Y-m-d') && $current_hour >= 12) {
                $error = "Same-day bookings must be made before 12:00 PM.";
                if ($is_ajax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    exit();
                }
                $bookingError = $error;
            } else {
                $update_stmt = $pdo->prepare("UPDATE bookings SET Date = ?, Time = ?, Address = ?, Special_Instructions = ? WHERE ID = ?");
                $update_stmt->execute([$new_date, $new_time, $new_address, $new_instructions, $booking_id]);
                if ($is_ajax) {
                    echo json_encode(['success' => true, 'message' => 'Quotation updated successfully']);
                    exit();
                }
                $bookingMessage = "Quotation updated successfully.";
            }
        }
    } catch (PDOException $e) {
        error_log("Update booking error: " . $e->getMessage());
        $bookingError = "Database error: " . $e->getMessage();
    }
}
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $booking_id = (int)$_GET['cancel'];
    try {
        $stmt = $pdo->prepare("SELECT Status FROM bookings WHERE ID = ? AND Customer_ID = ?");
        $stmt->execute([$booking_id, $user_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($booking) {
            $status = strtolower($booking['Status']);
            if ($status === 'requested' || $status === 'confirmed') {
                $stmt = $pdo->prepare("UPDATE bookings SET Status = 'cancelled' WHERE ID = ?");
                $stmt->execute([$booking_id]);
                $bookingMessage = "Booking cancelled successfully.";
                require_once 'includes/notifications_model.inc.php';
                $admins_query = "SELECT ID FROM admins";
                $admins_stmt = $pdo->prepare($admins_query);
                $admins_stmt->execute();
                $admins = $admins_stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($admins as $admin) {
                    create_notification($pdo, $admin['ID'], 'admin', 'Booking Cancelled', "Booking #{$booking_id} has been cancelled by the customer.", 'booking');
                }
                $stmt = $pdo->prepare("SELECT b.*, s.Name as Service_Name, s.Description as Service_Description, s.Image_URL as Service_Image FROM bookings b LEFT JOIN services s ON b.Service_ID = s.ID WHERE b.Customer_ID = ? ORDER BY b.Booking_Date DESC, b.Created_At DESC");
                $stmt->execute([$user_id]);
                $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $status_counts = ['all' => count($bookings), 'requested' => 0, 'contacted' => 0, 'in_progress' => 0, 'quoted' => 0, 'confirmed' => 0, 'completed' => 0, 'cancelled' => 0, 'expired' => 0];
                foreach ($bookings as $b) {
                    $s = strtolower($b['Status'] ?? 'requested');
                    if (isset($status_counts[$s])) $status_counts[$s]++;
                }
                if ($filter_status !== 'all') {
                    $filtered_bookings = array_filter($bookings, function ($b) use ($filter_status) {
                        return strtolower($b['Status'] ?? 'requested') === $filter_status;
                    });
                }
            } else {
                $bookingError = "This booking cannot be cancelled.";
            }
        } else {
            $bookingError = "Booking not found.";
        }
    } catch (PDOException $e) {
        error_log("Cancel booking error: " . $e->getMessage());
    }
}
if (isset($_GET['approve']) && is_numeric($_GET['approve'])) {
    $booking_id = (int)$_GET['approve'];
    try {
        $stmt = $pdo->prepare("SELECT Status FROM bookings WHERE ID = ? AND Customer_ID = ?");
        $stmt->execute([$booking_id, $user_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($booking && strtolower($booking['Status']) === 'quoted') {
            $stmt = $pdo->prepare("UPDATE bookings SET Status = 'confirmed' WHERE ID = ?");
            $stmt->execute([$booking_id]);
            $bookingMessage = "Quote approved! Your booking is now confirmed.";
            require_once 'includes/notifications_model.inc.php';
            $admins_query = "SELECT ID FROM admins";
            $admins_stmt = $pdo->prepare($admins_query);
            $admins_stmt->execute();
            $admins = $admins_stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($admins as $admin) {
                create_notification($pdo, $admin['ID'], 'admin', 'Booking Confirmed', "Booking #{$booking_id} has been confirmed by the customer.", 'booking');
            }
            $stmt = $pdo->prepare("SELECT b.*, s.Name as Service_Name, s.Description as Service_Description, s.Image_URL as Service_Image FROM bookings b LEFT JOIN services s ON b.Service_ID = s.ID WHERE b.Customer_ID = ? ORDER BY b.Booking_Date DESC, b.Created_At DESC");
            $stmt->execute([$user_id]);
            $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $status_counts = ['all' => count($bookings), 'requested' => 0, 'contacted' => 0, 'in_progress' => 0, 'quoted' => 0, 'confirmed' => 0, 'completed' => 0, 'cancelled' => 0, 'expired' => 0];
            foreach ($bookings as $b) {
                $s = strtolower($b['Status'] ?? 'requested');
                if (isset($status_counts[$s])) $status_counts[$s]++;
            }
            if ($filter_status !== 'all') {
                $filtered_bookings = array_filter($bookings, function ($b) use ($filter_status) {
                    return strtolower($b['Status'] ?? 'requested') === $filter_status;
                });
            }
        } else {
            $bookingError = "This quote cannot be approved.";
        }
    } catch (PDOException $e) {
        error_log("Approve quote error: " . $e->getMessage());
    }
}
if (isset($_GET['decline']) && is_numeric($_GET['decline'])) {
    $booking_id = (int)$_GET['decline'];
    try {
        $stmt = $pdo->prepare("SELECT Status FROM bookings WHERE ID = ? AND Customer_ID = ?");
        $stmt->execute([$booking_id, $user_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($booking && strtolower($booking['Status']) === 'quoted') {
            $stmt = $pdo->prepare("UPDATE bookings SET Status = 'cancelled' WHERE ID = ?");
            $stmt->execute([$booking_id]);
            $bookingMessage = "Quote declined.";
            require_once 'includes/notifications_model.inc.php';
            $admins_query = "SELECT ID FROM admins";
            $admins_stmt = $pdo->prepare($admins_query);
            $admins_stmt->execute();
            $admins = $admins_stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($admins as $admin) {
                create_notification($pdo, $admin['ID'], 'admin', 'Quote Declined', "The customer has declined the quote for booking #{$booking_id}.", 'booking');
            }
            $stmt = $pdo->prepare("SELECT b.*, s.Name as Service_Name, s.Description as Service_Description, s.Image_URL as Service_Image FROM bookings b LEFT JOIN services s ON b.Service_ID = s.ID WHERE b.Customer_ID = ? ORDER BY b.Booking_Date DESC, b.Created_At DESC");
            $stmt->execute([$user_id]);
            $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $status_counts = ['all' => count($bookings), 'requested' => 0, 'contacted' => 0, 'in_progress' => 0, 'quoted' => 0, 'confirmed' => 0, 'completed' => 0, 'cancelled' => 0, 'expired' => 0];
            foreach ($bookings as $b) {
                $s = strtolower($b['Status'] ?? 'requested');
                if (isset($status_counts[$s])) $status_counts[$s]++;
            }
            if ($filter_status !== 'all') {
                $filtered_bookings = array_filter($bookings, function ($b) use ($filter_status) {
                    return strtolower($b['Status'] ?? 'requested') === $filter_status;
                });
            }
        } else {
            $bookingError = "This quote cannot be declined.";
        }
    } catch (PDOException $e) {
        error_log("Decline quote error: " . $e->getMessage());
    }
}
$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? '';
$user_role = $_SESSION['user_role'] ?? '';
$is_logged_in = isset($_SESSION['user_id']);
$is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
$is_customer = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'customer';
$user_email = $_SESSION['user_email'] ?? '';
$notifications = [];
$unreadCount = 0;
if ($is_customer && $user_id) {
    require_once 'includes/notifications_model.inc.php';
    $notifications = get_notifications($pdo, $user_id, 'customer', true, 5);
    $unreadCount = get_unread_notification_count($pdo, $user_id, 'customer');
}
?>
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

<body class="bookings-page" data-is-logged-in="<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>">
    <header class="header"><button class="hamburger-btn" id="hamburger-btn" aria-label="Toggle menu"><img src="images/menu.png" alt="Close"></button>
        <div class="logo"><a href="index.php"><img src="images/PM-LogoB.png" alt="Phambili Ma-Africa Logo"></a></div>
        <div class="header-icons"><?php if ($is_customer && $user_id): ?><div class="notification-bell-customer"><button class="bell-btn-customer" onclick="toggleCustomerNotificationDropdown()"><i class="fas fa-bell"></i><?php if ($unreadCount > 0): ?><span class="notification-badge-customer"><?php echo $unreadCount; ?></span><?php endif; ?></button>
                    <div class="notification-dropdown-customer" id="notificationDropdownCustomer">
                        <div class="notification-dropdown-header">
                            <h4>Notifications</h4><button class="mark-all-read-btn" onclick="markAllCustomerNotificationsRead()">Mark all read</button>
                        </div>
                        <div class="notification-dropdown-content"><?php if (empty($notifications)): ?><div class="no-notifications"><i class="fas fa-bell-slash"></i>
                                    <p>No new notifications</p>
                                </div><?php else: ?><?php foreach ($notifications as $notif): ?><div class="notification-dropdown-item unread" data-id="<?php echo $notif['ID']; ?>">
                                    <div class="notif-content"><strong><?php echo htmlspecialchars($notif['title']); ?></strong>
                                        <p><?php echo htmlspecialchars($notif['message']); ?></p><small><?php echo date('M j, Y g:i A', strtotime($notif['created_at'])); ?></small>
                                    </div><button class="notif-close" onclick="markCustomerNotificationRead(<?php echo $notif['ID']; ?>)"><i class="fas fa-times"></i></button>
                                </div><?php endforeach; ?><?php endif; ?></div>
                    </div>
                </div><?php endif; ?><?php if ($is_logged_in): ?><div class="user-profile-dropdown" id="user-profile-dropdown"><button class="icon-link user-profile-btn" aria-label="User Profile" id="user-profile-btn"><img src="images/user.png" alt="User" class="icon" id="user-icon"></button>
                    <div class="profile-dropdown-menu" id="profile-dropdown-menu"><?php if ($is_admin): ?><div class="profile-dropdown-header"><i class="fas fa-user-shield admin-icon"></i>
                                <div class="profile-info"><span class="profile-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Administrator'); ?></span><span class="profile-role">Admin</span></div>
                            </div>
                            <div class="profile-dropdown-links"><a href="admin_dashboard.php" class="dropdown-link"><i class="fas fa-tachometer-alt"></i>Admin Dashboard</a><a href="includes/logout.inc.php" class="dropdown-link logout-link"><i class="fas fa-sign-out-alt"></i>Log Out</a></div><?php elseif ($is_customer): ?><div class="profile-dropdown-header"><i class="fas fa-user customer-icon"></i>
                                <div class="profile-info"><span class="profile-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Customer'); ?></span><span class="profile-role">Customer</span></div>
                            </div>
                            <div class="profile-dropdown-links"><a href="bookings.php" class="dropdown-link"><i class="fas fa-calendar-check"></i>Bookings</a><a href="profile.php" class="dropdown-link"><i class="fas fa-user"></i>Profile</a><a href="includes/logout.inc.php" class="dropdown-link logout-link"><i class="fas fa-sign-out-alt"></i>Log Out</a></div><?php endif; ?>
                    </div>
                </div><?php else: ?><a href="login.php" class="icon-link" aria-label="Login/Register"><img src="images/user.png" alt="Login" class="icon"></a><?php endif; ?><button class="cart-btn" id="cart-btn" aria-label="Shopping Cart"><img src="images/shopping-bag.png" alt="" class="icon"><span class="cart-badge">0</span></button></div>
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
            <li class="nav-item"><a href="products.php" class="nav-link">Products</a></li><?php if ($is_admin): ?><li class="nav-item"><a href="admin_dashboard.php" class="nav-link admin-nav-link">Admin Dashboard</a></li>
                <li class="nav-item"><a href="includes/logout.inc.php" class="nav-link">Log Out</a></li><?php elseif ($is_customer): ?><li class="nav-item"><a href="bookings.php" class="nav-link">Bookings</a></li>
                <li class="nav-item"><a href="profile.php" class="nav-link">Profile</a></li>
                <li class="nav-item"><a href="includes/logout.inc.php" class="nav-link">Log Out</a></li><?php else: ?><li class="nav-item"><a href="login.php" class="nav-link">Log In</a></li><?php endif; ?><li class="nav-item"><a href="booking.php" class="nav-link book-now-btn">Book Now</a></li>
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
        <div class="profile-container"><?php if ($bookingMessage): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($bookingMessage); ?></div><?php endif; ?><?php if ($bookingError): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($bookingError); ?></div><?php endif; ?><div class="profile-header">
                <div class="profile-avatar">
                    <div class="avatar-circle"><i class="fas fa-calendar-check" style="font-size: 2rem;"></i></div>
                </div>
                <div class="profile-info-header">
                    <h1 class="profile-name">Bookings</h1>
                    <p class="profile-email">View and manage your service bookings</p><span class="profile-role-badge"><?php echo count($bookings); ?> Total</span>
                </div>
            </div>
            <div class="profile-section">
                <div class="section-content" style="padding: 1rem;">
                    <div class="booking-filters"><a href="bookings.php?status=all" class="filter-btn <?php echo $filter_status === 'all' ? 'active' : ''; ?>"><i class="fas fa-list"></i> All <span class="filter-count"><?php echo $status_counts['all']; ?></span></a><a href="bookings.php?status=requested" class="filter-btn <?php echo $filter_status === 'requested' ? 'active' : ''; ?>"><i class="fas fa-clock"></i> Requested <span class="filter-count"><?php echo $status_counts['requested']; ?></span></a><a href="bookings.php?status=confirmed" class="filter-btn <?php echo $filter_status === 'confirmed' ? 'active' : ''; ?>"><i class="fas fa-check"></i> Confirmed <span class="filter-count"><?php echo $status_counts['confirmed']; ?></span></a><a href="bookings.php?status=completed" class="filter-btn <?php echo $filter_status === 'completed' ? 'active' : ''; ?>"><i class="fas fa-check-circle"></i> Completed <span class="filter-count"><?php echo $status_counts['completed']; ?></span></a><a href="bookings.php?status=cancelled" class="filter-btn <?php echo $filter_status === 'cancelled' ? 'active' : ''; ?>"><i class="fas fa-times-circle"></i> Cancelled <span class="filter-count"><?php echo $status_counts['cancelled']; ?></span></a><a href="bookings.php?status=expired" class="filter-btn <?php echo $filter_status === 'expired' ? 'active' : ''; ?>"><i class="fas fa-clock"></i> Expired <span class="filter-count"><?php echo $status_counts['expired']; ?></span></a></div>
                </div>
            </div>
            <div class="profile-section" id="booking-history">
                <div class="section-header">
                    <h2><i class="fas fa-list"></i> <?php echo $filter_status === 'all' ? 'All Bookings' : ucfirst($filter_status) . ' Bookings'; ?></h2><a href="services.php" class="btn-bookings">Book More Services →</a>
                </div>
                <div class="section-content">
                    <div class="booking-history"><?php if (empty($filtered_bookings)): ?><div class="empty-state"><i class="fas fa-calendar-check empty-icon"></i>
                                <h3>No Bookings Found</h3>
                                <p><?php if ($filter_status === 'all'): ?>You haven't made any bookings yet. Start by booking our cleaning services!<?php else: ?>No <?php echo ucfirst($filter_status); ?> bookings found.<?php endif; ?></p><a href="services.php" class="btn-primary"><i class="fas fa-calendar-plus"></i> Book Services</a>
                            </div><?php else: ?><div class="bookings-list"><?php foreach ($filtered_bookings as $booking): $status = strtolower($booking['Status'] ?? 'requested');
                                                                                $status_class = 'status-' . $status;
                                                                                $service_name = $booking['Service_Name'] ?? 'Service Booking';
                                                                                $booking_date = getRelativeDate($booking['created_at']);
                                                                                $booking_time = date('g:i A', strtotime($booking['Time'] ?? '09:00:00'));
                                                                                $scheduled_date = date('F j, Y', strtotime($booking['Date'])); ?><div class="booking-card <?php echo $status_class; ?>">
                                        <div class="booking-card-content"><?php if (!empty($booking['Service_Image'])): ?><div class="booking-image"><img src="<?php echo htmlspecialchars($booking['Service_Image']); ?>" alt="<?php echo htmlspecialchars($service_name); ?>"></div><?php endif; ?><div class="booking-details-main">
                                                <div class="booking-card-header">
                                                    <div class="booking-service-info">
                                                        <h4 class="booking-service-name"><?php echo htmlspecialchars($service_name); ?></h4>
                                                        <div class="booking-meta"><span class="booking-date"><i class="fas fa-calendar-plus"></i> <?php echo $booking_date; ?></span><span class="booking-date"><i class="fas fa-calendar-check"></i> <?php echo $scheduled_date; ?></span><span class="booking-time"><i class="fas fa-clock"></i> <?php echo $booking_time; ?></span></div>
                                                    </div><span class="booking-status <?php echo $status_class; ?>"><?php echo ucfirst($status); ?></span>
                                                </div><?php if (!empty($booking['Notes'])): ?><div class="booking-notes"><i class="fas fa-sticky-note"></i> <?php echo htmlspecialchars($booking['Notes']); ?></div><?php endif; ?><div class="booking-footer"><span class="booking-type"><i class="fas fa-file-invoice-dollar"></i> Quotation</span>
                                                    <div class="booking-actions"><?php if (!empty($booking['Quoted_Amount']) && $booking['Quoted_Amount'] > 0): ?><a href="api/generate_quote_pdf.php?id=<?php echo $booking['ID']; ?>" target="_blank" class="btn-outline-small"><i class="fas fa-file-pdf"></i> Download</a><?php endif; ?><button type="button" class="btn-outline-small" onclick='openBookingModal(<?php echo $booking["ID"]; ?>, <?php echo json_encode($service_name); ?>, "<?php echo $booking["Date"]; ?>", "<?php echo $booking["Time"]; ?>", <?php echo json_encode($booking["Address"] ?? ""); ?>, <?php echo json_encode($booking["Special_Instructions"] ?? ""); ?>, <?php echo json_encode($status); ?>, <?php echo json_encode($booking["Property_Type"] ?? ""); ?>, <?php echo json_encode($booking["Cleaning_Frequency"] ?? ""); ?>, <?php echo $booking["Quoted_Amount"] ?? "null"; ?>, <?php echo json_encode($booking["Service_Image"] ?? ""); ?>)'><i class="fas fa-eye"></i> View & Edit</button><?php if ($status === 'quoted'): ?><a href="bookings.php?approve=<?php echo $booking['ID']; ?>&status=<?php echo $filter_status; ?>" class="btn-approve-small" onclick="return confirm('Do you want to approve this quote? The service will be scheduled.');"><i class="fas fa-check"></i> Approve</a><a href="javascript:void(0)" class="btn-cancel-small" onclick="handleBookingAction('decline', <?php echo $booking['ID']; ?>, this)"><i class="fas fa-times"></i> Decline</a><?php elseif ($status === 'requested' || $status === 'confirmed'): ?><a href="javascript:void(0)" class="btn-cancel-small" onclick="handleBookingAction('cancel', <?php echo $booking['ID']; ?>, this)"><i class="fas fa-times"></i> Cancel</a><?php endif; ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div><?php endforeach; ?></div><?php endif; ?></div>
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
                <form id="edit-booking-form" method="POST" action=""><input type="hidden" id="modal-booking-id" name="booking_id"><input type="hidden" name="ajax" value="true">
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
    <script>
        function toggleCustomerNotificationDropdown() {
            const d = document.getElementById('notificationDropdownCustomer');
            if (d) d.classList.toggle('active');
        }
        document.addEventListener('click', function(e) {
            const b = document.querySelector('.notification-bell-customer');
            if (b && !b.contains(e.target)) {
                const d = document.getElementById('notificationDropdownCustomer');
                if (d) d.classList.remove('active');
            }
        });
        async function markCustomerNotificationRead(id) {
            try {
                const r = await fetch('api/notification_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'mark_read',
                        notification_id: id
                    })
                });
                const result = await r.json();
                if (result.success) {
                    const item = document.querySelector('[data-id="' + id + '"]');
                    if (item) {
                        item.style.opacity = '0';
                        setTimeout(() => item.remove(), 300);
                    }
                    const badge = document.querySelector('.notification-badge-customer');
                    if (badge) {
                        const c = parseInt(badge.textContent);
                        if (c > 1) badge.textContent = c - 1;
                        else badge.remove();
                    }
                }
            } catch (e) {
                console.error('Error:', e);
            }
        }
        async function markAllCustomerNotificationsRead() {
            try {
                const r = await fetch('api/notification_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'mark_all_read'
                    })
                });
                const result = await r.json();
                if (result.success) {
                    const content = document.querySelector('.notification-dropdown-customer .notification-dropdown-content');
                    if (content) {
                        content.innerHTML = '<div class="no-notifications"><i class="fas fa-bell-slash"></i><p>No new notifications</p></div>';
                    }
                    const badge = document.querySelector('.notification-badge-customer');
                    if (badge) badge.remove();
                }
            } catch (e) {
                console.error('Error:', e);
            }
        }
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                alert.style.transition = 'all 0.3s ease';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        });

        function openBookingModal(id, serviceName, date, time, address, instructions, status, propertyType, frequency, quotedAmount, serviceImage) {
            console.log('Opening modal for booking:', id, serviceName, 'Quote:', quotedAmount, 'Image:', serviceImage);

            function setText(id, value) {
                const el = document.getElementById(id);
                if (el) el.textContent = value;
                else console.warn('Element not found:', id);
            }

            function setValue(id, value) {
                const el = document.getElementById(id);
                if (el) el.value = value;
                else console.warn('Element not found:', id);
            }
            setValue('modal-booking-id', id);
            setText('modal-service-name', serviceName);
            setText('status-text', 'Status: ' + status.charAt(0).toUpperCase() + status.slice(1));
            const serviceCard = document.getElementById('modal-service-card');
            if (serviceImage && serviceCard) {
                serviceCard.style.backgroundImage = "url('" + serviceImage + "')";
                serviceCard.style.backgroundSize = 'cover';
                serviceCard.style.backgroundPosition = 'center';
                serviceCard.style.backgroundRepeat = 'no-repeat';
            } else if (serviceCard) {
                serviceCard.style.backgroundImage = 'none';
            }
            const statusIcon = document.getElementById('status-icon');
            if (statusIcon) statusIcon.className = 'fas fa-info-circle status-icon-' + status;
            setText('modal-property-display', propertyType || 'N/A');
            setText('modal-frequency-display', frequency || 'One-time');
            setText('modal-date-display', date);
            setText('modal-time-display', time.substring(0, 5));
            setValue('modal-date', date);
            setValue('modal-time', time.substring(0, 5));
            setValue('modal-address', address);
            setValue('modal-instructions', instructions || '');
            setText('modal-booking-number', id);
            const form = document.getElementById('edit-booking-form');
            if (form) form.action = 'bookings.php?edit=' + id;
            const canEdit = status === 'requested';
            const inputs = document.querySelectorAll('#edit-booking-form input, #edit-booking-form select, #edit-booking-form textarea');
            const saveBtn = document.getElementById('btn-save');
            const adminNotice = document.getElementById('admin-notice');
            if (canEdit) {
                inputs.forEach(input => input.disabled = false);
                saveBtn.style.display = 'inline-flex';
                adminNotice.style.display = 'none';
                const now = new Date();
                const currentHour = now.getHours();
                const dateInput = document.getElementById('modal-date');
                if (currentHour >= 12) {
                    const tomorrow = new Date(now);
                    tomorrow.setDate(tomorrow.getDate() + 1);
                    dateInput.min = tomorrow.toISOString().split('T')[0];
                } else {
                    dateInput.min = now.toISOString().split('T')[0];
                }
                updateTimeOptions();
            } else {
                inputs.forEach(input => input.disabled = true);
                saveBtn.style.display = 'none';
                adminNotice.style.display = 'flex';
            }
            document.getElementById('bookingModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
            const modalActions = document.getElementById('modal-actions');
            if (quotedAmount && quotedAmount > 0) {
                if (!document.getElementById('download-pdf-btn')) {
                    const pdfBtn = document.createElement('button');
                    pdfBtn.id = 'download-pdf-btn';
                    pdfBtn.type = 'button';
                    pdfBtn.className = 'btn-close-modal';
                    pdfBtn.style.cssText = 'background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); color: white; border: none; padding: 1rem 1.5rem; border-radius: 12px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 0.75rem;';
                    pdfBtn.innerHTML = '<div class="btn-icon"><i class="fas fa-file-pdf"></i></div><div class="btn-text"><span class="btn-main-text">Download Quote</span><span class="btn-sub-text">View as PDF</span></div>';
                    pdfBtn.onclick = function() {
                        window.open('api/generate_quote_pdf.php?id=' + id, '_blank');
                    };
                    const closeBtn = modalActions.querySelector('.btn-close-modal');
                    if (closeBtn) modalActions.insertBefore(pdfBtn, closeBtn);
                }
            } else {
                const existingPdfBtn = document.getElementById('download-pdf-btn');
                if (existingPdfBtn) existingPdfBtn.remove();
            }
        }

        function closeBookingModal() {
            document.getElementById('bookingModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        async function handleBookingAction(action, bookingId, element) {
            const confirmMessage = action === 'cancel' ? 'Are you sure you want to cancel this booking?' : 'Are you sure you want to decline this quote?';
            if (!confirm(confirmMessage)) return;
            try {
                const formData = new FormData();
                formData.append('action', action);
                formData.append('booking_id', bookingId);
                const response = await fetch('bookings.php', {
                    method: 'POST',
                    body: formData
                });
                const text = await response.text();
                if (text.includes('cancelled successfully') || text.includes('declined successfully')) {
                    showNotification(action === 'cancel' ? 'Booking cancelled successfully' : 'Quote declined successfully', 'success');
                    const card = element.closest('.booking-card');
                    if (card) {
                        card.style.opacity = '0';
                        setTimeout(() => card.remove(), 300);
                    }
                    updateFilterCounts();
                } else {
                    showNotification('Failed to ' + action + ' booking', 'error');
                }
            } catch (error) {
                console.error('Error handling booking action:', error);
                showNotification('Failed to ' + action + ' booking', 'error');
            }
        }

        function updateFilterCounts() {
            const counts = document.querySelectorAll('.filter-count');
            counts.forEach(count => {
                const current = parseInt(count.textContent);
                if (current > 0) {
                    count.textContent = current - 1;
                }
            });
        }

        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = 'notification ' + type;
            notification.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i><span>' + message + '</span>';
            document.body.appendChild(notification);
            setTimeout(() => {
                notification.classList.add('show');
            }, 10);
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.remove();
                }, 400);
            }, 3000);
        }

        function updateTimeOptions() {
            const dateInput = document.getElementById('modal-date');
            const timeSelect = document.getElementById('modal-time');
            const selectedDate = dateInput.value;
            if (!selectedDate) return;
            const now = new Date();
            const today = now.toISOString().split('T')[0];
            const currentTime = now.getHours() * 60 + now.getMinutes();
            const options = timeSelect.querySelectorAll('option');
            options.forEach(option => {
                const timeValue = option.value;
                const [hours, minutes] = timeValue.split(':').map(Number);
                const optionTimeMinutes = hours * 60 + minutes;
                if (selectedDate === today) {
                    option.disabled = optionTimeMinutes <= currentTime;
                } else {
                    option.disabled = false;
                }
            });
        }
        document.addEventListener('DOMContentLoaded', function() {
            const modalDate = document.getElementById('modal-date');
            if (modalDate) {
                modalDate.addEventListener('change', updateTimeOptions);
            }
            const bookingModal = document.getElementById('bookingModal');
            if (bookingModal) {
                bookingModal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closeBookingModal();
                    }
                });
            }
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeBookingModal();
                }
            });
        });
        <?php if ($view_booking): ?>
            document.addEventListener('DOMContentLoaded', function() {
                openBookingModal(<?php echo $view_booking['ID']; ?>, <?php echo json_encode($view_booking['Service_Name'] ?? 'Service'); ?>, '<?php echo $view_booking['Date']; ?>', '<?php echo $view_booking['Time']; ?>', <?php echo json_encode($view_booking['Address'] ?? ''); ?>, <?php echo json_encode($view_booking['Special_Instructions'] ?? ''); ?>, <?php echo json_encode(strtolower($view_booking['Status'] ?? 'requested')); ?>, <?php echo json_encode($view_booking['Property_Type'] ?? ''); ?>, <?php echo json_encode($view_booking['Cleaning_Frequency'] ?? ''); ?>, <?php echo $view_booking['Quoted_Amount'] ?? 'null'; ?>);
            });
        <?php endif; ?>
    </script>
</body>

</html>
