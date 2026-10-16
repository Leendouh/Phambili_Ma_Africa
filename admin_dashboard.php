<?php
// Admin Dashboard - Consolidated single-page admin panel
require_once 'includes/dbh.inc.php';
require_once 'includes/booking_contr.inc.php';
require_once 'includes/service_contr.inc.php';
require_once 'includes/product_contr.inc.php';
require_once 'includes/config_session.inc.php';
require_once 'includes/admin_dashboard.inc.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Include notification functions
require_once 'includes/notifications_model.inc.php';

// Fetch notifications for admin
$adminId = $_SESSION['admin_id'];
$adminNotifications = get_notifications($pdo, $adminId, 'admin', true, 5);
$adminUnreadCount = get_unread_notification_count($pdo, $adminId, 'admin');

// Helper function to get relative date like WhatsApp
function getRelativeDate($dateString) {
    $date = new DateTime($dateString);
    $today = new DateTime('today');
    $yesterday = new DateTime('yesterday');
    $diff = $today->diff($date);

    // Reset times to compare dates only
    $date->setTime(0, 0, 0);
    $today->setTime(0, 0, 0);
    $yesterday->setTime(0, 0, 0);

    if ($date == $today) {
        return 'Today';
    } elseif ($date == $yesterday) {
        return 'Yesterday';
    } elseif ($diff->days == 2) {
        return 'Day before yesterday';
    } elseif ($diff->days <= 7) {
        return $date->format('l'); // Day name (Monday, Tuesday, etc.)
    } else {
        return $date->format('M d, Y'); // Full date for older bookings
    }
}

// ========== BOOKINGS DATA ==========

// Auto-expire quotes and past bookings
expireQuotes($pdo);
expirePastBookings($pdo);

// Check for upcoming expirations and send notifications
checkUpcomingExpirations($pdo);

$filters = [
    'status' => $_GET['status'] ?? null,
    'search' => $_GET['search'] ?? null,
    'service_id' => $_GET['service_id'] ?? null,
    'date_from' => $_GET['date_from'] ?? null,
    'date_to' => $_GET['date_to'] ?? null,
    'page' => $_GET['page'] ?? 1,
    'limit' => 10
];

$bookingsResult = getAllBookingsController($pdo, $filters);
$bookings = $bookingsResult['success'] ? $bookingsResult['bookings'] : [];
$totalPages = $bookingsResult['totalPages'] ?? 1;
$currentPage = $bookingsResult['currentPage'] ?? 1;
$totalBookings = $bookingsResult['totalBookings'] ?? 0;

$statsResult = getBookingStatsController($pdo);
$bookingStats = $statsResult['success'] ? $statsResult['stats'] : [];

$statusColors = [
    'requested' => 'requested',
    'contacted' => 'contacted',
    'in_progress' => 'in_progress',
    'quoted' => 'quoted',
    'confirmed' => 'confirmed',
    'completed' => 'completed',
    'cancelled' => 'cancelled',
    'expired' => 'expired'
];

$statusLabels = [
    'requested' => 'Requested',
    'contacted' => 'Contacted',
    'in_progress' => 'In Progress',
    'quoted' => 'Quoted',
    'confirmed' => 'Confirmed',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
    'expired' => 'Expired'
];

// ========== SERVICES DATA ==========
$servicesResult = getServicesController($pdo);
$services = $servicesResult['success'] ? $servicesResult['services'] : [];

// ========== PRODUCTS DATA ==========
$productsResult = getProductsController($pdo);
$products = $productsResult['success'] ? $productsResult['products'] : [];

$productStatsResult = getProductStatsController($pdo);
$productStats = $productStatsResult['success'] ? $productStatsResult['stats'] : [];

// ========== DASHBOARD STATS ==========
$totalServices = count($services);
$availableServices = count(array_filter($services, fn($s) => $s['Is_Available'] == 1));
$totalProducts = count($products);
$availableProducts = count(array_filter($products, fn($p) => $p['Is_Available'] == 1));

// ========== CURRENT ADMIN DATA ==========
$currentAdminId = $_SESSION['admin_id'];
$stmt = $pdo->prepare("SELECT * FROM admins WHERE ID = :id");
$stmt->execute([':id' => $currentAdminId]);
$currentAdmin = $stmt->fetch(PDO::FETCH_ASSOC);

// ========== FIRST LOGIN PASSWORD RESET ==========
$needsPasswordReset = false;
$resetError = '';
$resetSuccess = false;

if ($currentAdmin && $currentAdmin['first_login'] == 1) {
    $needsPasswordReset = true;
}

// Handle first login password reset
if ($needsPasswordReset && $_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['first_login_reset'])) {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate password
    if (strlen($newPassword) < 8) {
        $resetError = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/[A-Z]/', $newPassword)) {
        $resetError = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match('/[a-z]/', $newPassword)) {
        $resetError = "Password must contain at least one lowercase letter.";
    } elseif (!preg_match('/[0-9]/', $newPassword)) {
        $resetError = "Password must contain at least one number.";
    } elseif (!preg_match('/[!@#$%^&*]/', $newPassword)) {
        $resetError = "Password must contain at least one special character (!@#$%^&*).";
    } elseif ($newPassword !== $confirmPassword) {
        $resetError = "Passwords do not match.";
    } else {
        try {
            // Update password and set first_login to 0
            require_once 'includes/login_model.inc.php';
            update_admin_password($pdo, $currentAdminId, $newPassword);
            
            // Refresh admin data
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE ID = :id");
            $stmt->execute([':id' => $currentAdminId]);
            $currentAdmin = $stmt->fetch(PDO::FETCH_ASSOC);
            $needsPasswordReset = false;
            $resetSuccess = true;
        } catch (Exception $e) {
            $resetError = "Error updating password. Please try again.";
        }
    }
}

// ========== PROFILE UPDATE HANDLING ==========
$profileMessage = '';
$profileError = '';
$profileEditMode = isset($_GET['edit_profile']) && $_GET['edit_profile'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    // Validation
    if (empty($fullName)) {
        $profileError = "Full name is required.";
    } elseif (strlen($fullName) < 2) {
        $profileError = "Full name must be at least 2 characters.";
    } elseif (!preg_match('/^[a-zA-Z\s\-\']+$/', $fullName)) {
        $profileError = "Full name can only contain letters, spaces, hyphens, and apostrophes.";
    } elseif (empty($phone)) {
        $profileError = "Phone number is required.";
    } else {
        // Strict phone validation - exactly 10 digits, only numbers, South African format
        $clean_phone = preg_replace('/\D/', '', $phone);
        
        if (!ctype_digit($clean_phone)) {
            $profileError = "Phone number must contain only digits (0-9), no letters or special characters.";
        } elseif (strlen($clean_phone) !== 10) {
            $profileError = "Phone number must be exactly 10 digits.";
        } elseif (!preg_match('/^0[0-9]{9}$/', $clean_phone)) {
            $profileError = "Phone number must start with 0 followed by 9 digits (e.g., 0821234567).";
        } else {
            try {
                $sql = "UPDATE admins SET Full_Name = :full_name, Phone = :phone, updatedAt = NOW() WHERE ID = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':full_name' => $fullName,
                    ':phone' => $clean_phone,
                    ':id' => $currentAdminId
                ]);
                
                // Refresh admin data
                $stmt = $pdo->prepare("SELECT * FROM admins WHERE ID = :id");
                $stmt->execute([':id' => $currentAdminId]);
                $currentAdmin = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Update session
                $_SESSION["admin_full_name"] = htmlspecialchars($fullName);
                
                $profileMessage = "Profile updated successfully!";
                $profileEditMode = false;
            } catch (PDOException $e) {
                $profileError = "Error updating profile. Please try again.";
            }
        }
    }
}

// ========== CUSTOMERS DATA ==========
$stmt = $pdo->query("SELECT ID, Full_Name, Email, Phone, Address, createdAt FROM customers ORDER BY createdAt DESC");
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalCustomers = count($customers);

// ========== ADMINS DATA (for main_admin only) ==========
$admins = [];
$totalAdmins = 0;
if ($currentAdmin && $currentAdmin['Role'] === 'main_admin') {
    $stmt = $pdo->query("SELECT ID, Full_Name, Email, Phone, Role, createdAt FROM admins ORDER BY createdAt DESC");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalAdmins = count($admins);
}

// Determine active section from URL param
$activeSection = $_GET['section'] ?? 'dashboard';
$validSections = ['dashboard', 'bookings', 'services', 'products', 'customers', 'admins', 'admin-profile', 'profile'];
if (!in_array($activeSection, $validSections)) {
    $activeSection = 'dashboard';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Phambili Ma-Africa</title>
    <link rel="icon" type="image/png" sizes="32x32" href="images/PM-Logo.jpg">
    
    <!-- Open Graph Meta Tags for Social Media Preview -->
    <meta property="og:title" content="Admin Dashboard - Phambili Ma-Africa">
    <meta property="og:description" content="Admin dashboard for managing Phambili Ma-Africa cleaning services.">
    <meta property="og:image" content="images/PM-Logo.jpg">
    <meta property="og:url" content="https://phambili-ma-africa.com/admin_dashboard.php">
    <meta property="og:type" content="website">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Admin Dashboard - Phambili Ma-Africa">
    <meta name="twitter:description" content="Admin dashboard for managing Phambili Ma-Africa cleaning services.">
    <meta name="twitter:image" content="images/PM-Logo.jpg">

    <link rel="stylesheet" href="css/admin_dashboard.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <?php if ($needsPasswordReset): ?>
    <!-- First Login Password Reset Modal -->
    <div class="first-login-overlay">
        <div class="first-login-modal">
            <div class="first-login-header">
                <i class="bi bi-shield-lock"></i>
                <h2>Security First: Set New Password</h2>
                <p>Welcome, <?php echo htmlspecialchars($currentAdmin['Full_Name'] ?: $currentAdmin['Email']); ?>! For security reasons, please set a new password for your admin account.</p>
            </div>
            <div class="first-login-body">
                <?php if ($resetError): ?>
                <div class="first-login-error">
                    <i class="bi bi-exclamation-circle"></i> <?php echo $resetError; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($resetSuccess): ?>
                <div class="first-login-success">
                    <i class="bi bi-check-circle-fill"></i>
                    <h3>Password Updated!</h3>
                    <p>Your password has been set successfully. Redirecting to dashboard...</p>
                    <script>setTimeout(() => location.reload(), 1500);</script>
                </div>
                <?php else: ?>
                <form method="post" id="firstLoginForm">
                    <div class="form-group">
                        <label>New Password <span class="text-danger">*</span></label>
                        <input type="password" name="new_password" id="newPassword" class="form-input" required minlength="8" placeholder="Create a strong password">
                        <div class="password-strength-bar">
                            <div class="password-strength-fill" id="strengthFill"></div>
                        </div>
                        <div class="password-requirements">
                            <strong>Password must include:</strong>
                            <ul>
                                <li id="req-len">At least 8 characters</li>
                                <li id="req-upper">One uppercase letter</li>
                                <li id="req-lower">One lowercase letter</li>
                                <li id="req-number">One number</li>
                                <li id="req-special">One special character (!@#$%^&*)</li>
                            </ul>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" name="confirm_password" id="confirmPassword" class="form-input" required minlength="8" placeholder="Confirm your password">
                    </div>
                    <button type="submit" name="first_login_reset" class="btn btn-primary" style="width:100%;">
                        <i class="bi bi-lock"></i> Update Password & Continue
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
    (function() {
        const newPass = document.getElementById('newPassword');
        const confirmPass = document.getElementById('confirmPassword');
        const strengthFill = document.getElementById('strengthFill');
        const form = document.getElementById('firstLoginForm');
        
        const requirements = {
            len: document.getElementById('req-len'),
            upper: document.getElementById('req-upper'),
            lower: document.getElementById('req-lower'),
            number: document.getElementById('req-number'),
            special: document.getElementById('req-special')
        };
        
        function checkStrength(password) {
            let score = 0;
            const hasLen = password.length >= 8;
            const hasUpper = /[A-Z]/.test(password);
            const hasLower = /[a-z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSpecial = /[!@#$%^&*]/.test(password);
            
            requirements.len.classList.toggle('met', hasLen);
            requirements.upper.classList.toggle('met', hasUpper);
            requirements.lower.classList.toggle('met', hasLower);
            requirements.number.classList.toggle('met', hasNumber);
            requirements.special.classList.toggle('met', hasSpecial);
            
            if (hasLen) score++;
            if (hasUpper) score++;
            if (hasLower) score++;
            if (hasNumber) score++;
            if (hasSpecial) score++;
            
            strengthFill.className = 'password-strength-fill';
            if (score <= 2) strengthFill.classList.add('weak');
            else if (score <= 4) strengthFill.classList.add('medium');
            else strengthFill.classList.add('strong');
            
            return score === 5;
        }
        
        newPass.addEventListener('input', function() {
            checkStrength(this.value);
        });
        
        form.addEventListener('submit', function(e) {
            if (newPass.value !== confirmPass.value) {
                e.preventDefault();
                ModalDialog.alert('Passwords do not match!', { title: 'Error', type: 'danger' });
                return;
            }
            if (!checkStrength(newPass.value)) {
                e.preventDefault();
                ModalDialog.alert('Please meet all password requirements.', { title: 'Error', type: 'danger' });
                return;
            }
        });
    })();
    </script>
    <?php endif; ?>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
               <a href="index.php" class="logo-link">
                    <img src="images/PM-LogoB.png" alt="Phambili Ma-Africa Logo" class="sidebar-logo">
                </a>
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
            </div>

            <div class="home-link-container">
                <a href="index.php" class="home-link">
                    <i class="bi bi-house"></i>
                    <span>Back to Website</span>
                </a>
            </div>

            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="#" class="nav-link <?php echo $activeSection === 'dashboard' ? 'active' : ''; ?>" data-section="dashboard">
                            <i class="bi bi-speedometer2"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="nav-link <?php echo $activeSection === 'bookings' ? 'active' : ''; ?>" data-section="bookings">
                            <i class="bi bi-calendar-check"></i>
                            <span>Bookings</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="nav-link <?php echo $activeSection === 'services' ? 'active' : ''; ?>" data-section="services">
                            <i class="bi bi-briefcase"></i>
                            <span>Services</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="nav-link <?php echo $activeSection === 'products' ? 'active' : ''; ?>" data-section="products">
                            <i class="bi bi-box-seam"></i>
                            <span>Products</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="nav-link <?php echo $activeSection === 'customers' ? 'active' : ''; ?>" data-section="customers">
                            <i class="bi bi-people"></i>
                            <span>Customers</span>
                        </a>
                    </li>
                    <?php if ($currentAdmin && $currentAdmin['Role'] === 'main_admin'): ?>
                    <li>
                        <a href="#" class="nav-link <?php echo $activeSection === 'admins' ? 'active' : ''; ?>" data-section="admins">
                            <i class="bi bi-shield-lock"></i>
                            <span>Admin Management</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li>
                        <a href="#" class="nav-link <?php echo $activeSection === 'admin-profile' ? 'active' : ''; ?>" data-section="admin-profile">
                            <i class="bi bi-person-circle"></i>
                            <span>My Profile</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="sidebar-footer">
                <a href="includes/logout.inc.php" class="logout-btn">
                    <i class="bi bi-box-arrow-left"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>
        
        <!-- Sidebar Overlay for Mobile -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <button class="header-toggle" id="headerToggle">
                        <i class="bi bi-list"></i>
                    </button>
                    <h1 id="sectionTitle">Dashboard</h1>
                </div>
                <div class="header-right">
                    <!-- Notification Bell -->
                    <div class="notification-bell">
                        <button class="bell-btn" onclick="toggleNotificationDropdown()">
                            <i class="bi bi-bell"></i>
                            <?php if ($adminUnreadCount > 0): ?>
                                <span class="notification-badge"><?php echo $adminUnreadCount; ?></span>
                            <?php endif; ?>
                        </button>
                        <div class="notification-dropdown" id="notificationDropdown">
                            <div class="notification-dropdown-header">
                                <h4>Notifications</h4>
                                <button class="mark-all-read-btn" onclick="markAllAdminNotificationsRead()">Mark all read</button>
                            </div>
                            <div class="notification-dropdown-content">
                                <?php if (empty($adminNotifications)): ?>
                                    <div class="no-notifications">
                                        <i class="bi bi-bell-slash"></i>
                                        <p>No new notifications</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($adminNotifications as $notif): ?>
                                        <div class="notification-dropdown-item unread" data-id="<?php echo $notif['ID']; ?>">
                                            <div class="notif-content">
                                                <strong><?php echo htmlspecialchars($notif['title']); ?></strong>
                                                <p><?php echo htmlspecialchars($notif['message']); ?></p>
                                                <small><?php echo date('M j, Y g:i A', strtotime($notif['created_at'])); ?></small>
                                            </div>
                                            <button class="notif-close" onclick="markAdminNotificationRead(<?php echo $notif['ID']; ?>)">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="user-info" onclick="switchSection('admin-profile')" style="cursor:pointer;">
                        <div class="user-avatar">
                            <i class="bi bi-person"></i>
                        </div>
                        <span><?php echo htmlspecialchars($currentAdmin['Full_Name'] ?? 'Admin'); ?></span>
                    </div>
                </div>
            </header>

            <!-- Content Sections -->
            <div class="content-sections">

                <!-- ==================== DASHBOARD SECTION ==================== -->
                <div class="content-section <?php echo $activeSection === 'dashboard' ? 'active' : ''; ?>" id="section-dashboard">
                    <!-- Stats Grid -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon bookings">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $totalBookings; ?></h3>
                                <p>Total Bookings</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon pending">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $bookingStats['pending_contact'] ?? 0; ?></h3>
                                <p>Pending Contact</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon services">
                                <i class="bi bi-briefcase"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $totalServices; ?></h3>
                                <p>Services</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon products">
                                <i class="bi bi-box-seam"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $totalProducts; ?></h3>
                                <p>Products</p>
                            </div>
                        </div>
                    </div>

                    <div class="content-row">
                        <!-- Recent Bookings -->
                        <div class="card recent-bookings">
                            <div class="card-header">
                                <h3><i class="bi bi-calendar-check"></i> Recent Bookings</h3>
                                <a href="#" class="view-all" data-section="bookings">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($bookings)): ?>
                                    <div class="empty-state">
                                        <i class="bi bi-calendar-x"></i>
                                        <p>No bookings yet</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach (array_slice($bookings, 0, 5) as $booking): ?>
                                    <div class="booking-item">
                                        <div class="booking-avatar">
                                            <?php echo strtoupper(substr($booking['Customer_Name'], 0, 1)); ?>
                                        </div>
                                        <div class="booking-info">
                                            <h4><?php echo htmlspecialchars($booking['Customer_Name']); ?></h4>
                                            <p><?php echo htmlspecialchars($booking['Service_Name']); ?> - <?php echo getRelativeDate($booking['created_at']); ?></p>
                                        </div>
                                        <div class="booking-meta">
                                            <span class="booking-status <?php echo $statusColors[$booking['Status']] ?? ''; ?>">
                                                <?php echo $statusLabels[$booking['Status']] ?? $booking['Status']; ?>
                                            </span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="card">
                            <div class="card-header">
                                <h3><i class="bi bi-lightning"></i> Quick Actions</h3>
                            </div>
                            <div class="card-body">
                                <div class="action-buttons">
                                    <div class="action-row">
                                        <a href="#" class="action-btn" data-section="bookings">
                                            <i class="bi bi-calendar-check"></i>
                                            <span>Manage Bookings</span>
                                        </a>
                                        <a href="#" class="action-btn" data-section="services">
                                            <i class="bi bi-briefcase"></i>
                                            <span>Manage Services</span>
                                        </a>
                                        <a href="#" class="action-btn" data-section="products">
                                            <i class="bi bi-box-seam"></i>
                                            <span>Manage Products</span>
                                        </a>
                                    </div>
                                    <div class="action-row">
                                        <a href="#" class="action-btn" data-section="customers">
                                            <i class="bi bi-people"></i>
                                            <span>Customers</span>
                                        </a>
                                        <?php if ($currentAdmin && $currentAdmin['Role'] === 'main_admin'): ?>
                                        <a href="#" class="action-btn" data-section="admins">
                                            <i class="bi bi-shield-lock"></i>
                                            <span>Admin Management</span>
                                        </a>
                                        <?php else: ?>
                                        <a href="#" class="action-btn" data-section="admin-profile">
                                            <i class="bi bi-person-circle"></i>
                                            <span>My Profile</span>
                                        </a>
                                        <?php endif; ?>
                                        <a href="#" class="action-btn" data-section="admin-profile">
                                            <i class="bi bi-person-circle"></i>
                                            <span>My Profile</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ==================== BOOKINGS SECTION ==================== -->
                <div class="content-section <?php echo $activeSection === 'bookings' ? 'active' : ''; ?>" id="section-bookings">
                    <div class="section-header">
                        <h2>Manage Bookings</h2>
                        <button class="btn btn-primary" onclick="exportBookings()">
                            <i class="bi bi-download"></i> Export
                        </button>
                    </div>

                    <!-- Booking Stats -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon bookings">
                                <i class="bi bi-calendar-plus"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $bookingStats['today_requests'] ?? 0; ?></h3>
                                <p>Today's Requests</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon pending">
                                <i class="bi bi-telephone"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $bookingStats['pending_contact'] ?? 0; ?></h3>
                                <p>Need Contact</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon services">
                                <i class="bi bi-cash"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $bookingStats['pending_quotation'] ?? 0; ?></h3>
                                <p>Need Quote</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon revenue">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $bookingStats['upcoming_bookings'] ?? 0; ?></h3>
                                <p>Upcoming</p>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="filters">
                        <form class="d-flex" method="GET" id="bookingFilterForm" style="display:flex;gap:0.5rem;flex-wrap:wrap;align-items:center;">
                            <input type="hidden" name="section" value="bookings">
                            <select class="search-input" name="status" id="statusFilter">
                                <option value="">All Status</option>
                                <option value="requested" <?php echo ($filters['status'] ?? '') === 'requested' ? 'selected' : ''; ?>>Requested</option>
                                <option value="contacted" <?php echo ($filters['status'] ?? '') === 'contacted' ? 'selected' : ''; ?>>Contacted</option>
                                <option value="in_progress" <?php echo ($filters['status'] ?? '') === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="quoted" <?php echo ($filters['status'] ?? '') === 'quoted' ? 'selected' : ''; ?>>Quoted</option>
                                <option value="confirmed" <?php echo ($filters['status'] ?? '') === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="completed" <?php echo ($filters['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo ($filters['status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                <option value="expired" <?php echo ($filters['status'] ?? '') === 'expired' ? 'selected' : ''; ?>>Expired</option>
                            </select>
                            <input type="text" class="search-input" name="search" placeholder="Search bookings, customers, services..." value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>" style="flex:1;min-width:200px;">
                            <select class="search-input" name="service_id">
                                <option value="">All Services</option>
                                <?php foreach ($services as $service): ?>
                                    <option value="<?php echo $service['ID']; ?>" <?php echo ($filters['service_id'] ?? '') == $service['ID'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($service['Name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="date" class="search-input" name="date_from" placeholder="From" value="<?php echo htmlspecialchars($filters['date_from'] ?? ''); ?>">
                            <input type="date" class="search-input" name="date_to" placeholder="To" value="<?php echo htmlspecialchars($filters['date_to'] ?? ''); ?>">
                            <button type="button" class="btn btn-secondary" onclick="clearFilters()">Clear</button>
                        </form>
                    </div>

                    <!-- Bookings Grid -->
                    <?php if (empty($bookings)): ?>
                        <div class="empty-state">
                            <i class="bi bi-calendar-x"></i>
                            <p>No bookings found</p>
                            <span class="subtitle">Bookings will appear here when customers submit requests</span>
                        </div>
                    <?php else: ?>
                    <div class="bookings-grid">
                        <?php foreach ($bookings as $booking): ?>
                        <div class="booking-card" onclick="viewBooking(<?php echo $booking['ID']; ?>)">
                            <div class="card-header">
                                <span class="service-name"><?php echo htmlspecialchars($booking['Service_Name']); ?></span>
                                <span class="booking-status <?php echo $statusColors[$booking['Status']] ?? ''; ?>">
                                    <?php echo $statusLabels[$booking['Status']] ?? $booking['Status']; ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="customer-row">
                                    <div class="customer-avatar-small">
                                        <?php echo strtoupper(substr($booking['Customer_Name'], 0, 1)); ?>
                                    </div>
                                    <div class="customer-details">
                                        <strong><?php echo htmlspecialchars($booking['Customer_Name']); ?></strong>
                                        <span class="customer-contact-small"><?php echo htmlspecialchars($booking['Customer_Email']); ?></span>
                                    </div>
                                </div>
                                <div class="details-grid">
                                    <div class="detail-item"><i class="bi bi-calendar-check"></i> <?php echo date('M d, Y', strtotime($booking['Date'])); ?></div>
                                    <div class="detail-item"><i class="bi bi-clock"></i> <?php echo date('g:i A', strtotime($booking['Time'])); ?></div>
                                    <div class="detail-item"><i class="bi bi-geo-alt"></i> <?php echo strlen($booking['Address']) > 25 ? substr(htmlspecialchars($booking['Address']), 0, 25) . '...' : htmlspecialchars($booking['Address']); ?></div>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.5rem;">
                                    <?php if ($booking['Quoted_Amount']): ?>
                                    <div class="quote-info-compact">
                                        <span class="quote-amount">R <?php echo number_format($booking['Quoted_Amount'], 2); ?></span>
                                    </div>
                                    <?php else: ?>
                                    <div class="quote-info-compact">
                                        <span class="quote-pending">Quote pending</span>
                                    </div>
                                    <?php endif; ?>
                                    <div class="booking-created-date" style="font-size: 0.75rem; color: var(--text-light);">
                                        <i class="bi bi-calendar-plus"></i> <?php echo getRelativeDate($booking['created_at']); ?>
                                    </div>
                                </div>
                                <div class="card-actions">
                                    <button class="btn btn-sm btn-secondary" onclick="event.stopPropagation(); viewBooking(<?php echo $booking['ID']; ?>)">
                                        <i class="bi bi-eye"></i> View
                                    </button>
                                    <?php if ($booking['Status'] === 'requested'): ?>
                                        <button class="btn btn-sm btn-primary" onclick="event.stopPropagation(); quickAction(<?php echo $booking['ID']; ?>, 'contact')">
                                            <i class="bi bi-telephone"></i> Contact
                                        </button>
                                    <?php elseif ($booking['Status'] === 'contacted'): ?>
                                        <button class="btn btn-sm btn-primary" onclick="event.stopPropagation(); quickAction(<?php echo $booking['ID']; ?>, 'in_progress')">
                                            <i class="bi bi-arrow-right"></i> In Progress
                                        </button>
                                        <button class="btn btn-sm btn-success" onclick="event.stopPropagation(); openQuoteModal(<?php echo $booking['ID']; ?>, <?php echo $booking['Quoted_Amount'] ?? 'null'; ?>)">
                                            <i class="bi bi-cash"></i> Quote
                                        </button>
                                    <?php elseif ($booking['Status'] === 'in_progress'): ?>
                                        <button class="btn btn-sm btn-success" onclick="event.stopPropagation(); openQuoteModal(<?php echo $booking['ID']; ?>, <?php echo $booking['Quoted_Amount'] ?? 'null'; ?>)">
                                            <i class="bi bi-cash"></i> Quote
                                        </button>
                                    <?php elseif ($booking['Status'] === 'quoted'): ?>
                                        <button class="btn btn-sm btn-info" onclick="event.stopPropagation(); viewQuoteDetails(<?php echo $booking['ID']; ?>, <?php echo htmlspecialchars(json_encode($booking['quote_breakdown'] ?? '')); ?>, <?php echo $booking['quote_validity_days'] ?? 7; ?>, <?php echo htmlspecialchars(json_encode($booking['quote_notes'] ?? '')); ?>, <?php echo $booking['Quoted_Amount'] ?? 0; ?>)">
                                            <i class="bi bi-eye"></i> View Quote
                                        </button>
                                        <button class="btn btn-sm btn-success" onclick="event.stopPropagation(); openQuoteModal(<?php echo $booking['ID']; ?>, <?php echo $booking['Quoted_Amount'] ?? 'null'; ?>)">
                                            <i class="bi bi-pencil"></i> Update Quote
                                        </button>
                                    <?php elseif ($booking['Status'] === 'confirmed'): ?>
                                        <button class="btn btn-sm btn-secondary" onclick="event.stopPropagation(); updateStatus(<?php echo $booking['ID']; ?>, 'completed')">
                                            <i class="bi bi-check-circle"></i> Complete
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($currentPage > 1): ?>
                            <a href="?section=bookings&page=<?php echo $currentPage - 1; ?>&status=<?php echo $filters['status'] ?? 'all'; ?>&search=<?php echo urlencode($filters['search'] ?? ''); ?>">&laquo; Prev</a>
                        <?php else: ?>
                            <span class="disabled">&laquo; Prev</span>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == $currentPage): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?section=bookings&page=<?php echo $i; ?>&status=<?php echo $filters['status'] ?? 'all'; ?>&search=<?php echo urlencode($filters['search'] ?? ''); ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        <?php if ($currentPage < $totalPages): ?>
                            <a href="?section=bookings&page=<?php echo $currentPage + 1; ?>&status=<?php echo $filters['status'] ?? 'all'; ?>&search=<?php echo urlencode($filters['search'] ?? ''); ?>">Next &raquo;</a>
                        <?php else: ?>
                            <span class="disabled">Next &raquo;</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- ==================== SERVICES SECTION ==================== -->
                <div class="content-section <?php echo $activeSection === 'services' ? 'active' : ''; ?>" id="section-services">
                    <div class="section-header">
                        <h2>Manage Services</h2>
                        <button class="btn btn-primary" onclick="openModal('addServiceModal')">
                            <i class="bi bi-plus-lg"></i> Add New Service
                        </button>
                    </div>

                    <!-- Service Stats -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon services">
                                <i class="bi bi-briefcase"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $totalServices; ?></h3>
                                <p>Total Services</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon revenue">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $availableServices; ?></h3>
                                <p>Available</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon pending">
                                <i class="bi bi-pause-circle"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $totalServices - $availableServices; ?></h3>
                                <p>Unavailable</p>
                            </div>
                        </div>
                    </div>

                    <!-- Services Grid -->
                    <?php if (empty($services)): ?>
                        <div class="empty-state">
                            <i class="bi bi-briefcase"></i>
                            <p>No services yet</p>
                            <span class="subtitle">Add your first service to get started</span>
                        </div>
                    <?php else: ?>
                    <div class="cards-grid">
                        <?php foreach ($services as $service): ?>
                        <div class="item-card">
                            <div class="item-image">
                                <?php if (!empty($service['Image_URL'])): ?>
                                    <img src="<?php echo htmlspecialchars($service['Image_URL']); ?>" alt="<?php echo htmlspecialchars($service['Name']); ?>">
                                <?php else: ?>
                                    <div class="placeholder"><i class="bi bi-briefcase"></i></div>
                                <?php endif; ?>
                                <span class="item-status <?php echo $service['Is_Available'] ? 'available' : 'unavailable'; ?>">
                                    <?php echo $service['Is_Available'] ? 'Available' : 'Unavailable'; ?>
                                </span>
                            </div>
                            <div class="item-content">
                                <h4><?php echo htmlspecialchars($service['Name']); ?></h4>
                                <p><?php echo htmlspecialchars($service['Description'] ?? 'No description'); ?></p>
                                <div class="item-meta">
                                    <span class="badge" style="background:var(--info-light);color:var(--info);padding:0.25rem 0.75rem;border-radius:20px;font-size:0.75rem;font-weight:600;">
                                        <?php echo htmlspecialchars($service['Category'] ?? 'Uncategorized'); ?>
                                    </span>
                                    <span class="item-duration"><i class="bi bi-clock"></i> <?php echo $service['Duration']; ?> min</span>
                                </div>
                                <div class="item-actions">
                                    <button class="btn btn-sm btn-primary" onclick="editService(<?php echo $service['ID']; ?>)">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    <button class="btn btn-sm <?php echo $service['Is_Available'] ? 'btn-secondary' : 'btn-success'; ?>" 
                                            onclick="toggleServiceAvailability(<?php echo $service['ID']; ?>, <?php echo $service['Is_Available'] ? 0 : 1; ?>)">
                                        <i class="bi bi-toggle-<?php echo $service['Is_Available'] ? 'on' : 'off'; ?>"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteService(<?php echo $service['ID']; ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- ==================== PRODUCTS SECTION ==================== -->
                <div class="content-section <?php echo $activeSection === 'products' ? 'active' : ''; ?>" id="section-products">
                    <div class="section-header">
                        <h2>Manage Products</h2>
                        <button class="btn btn-primary" onclick="openModal('addProductModal')">
                            <i class="bi bi-plus-lg"></i> Add New Product
                        </button>
                    </div>

                    <!-- Product Stats -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon products">
                                <i class="bi bi-box-seam"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $productStats['total'] ?? 0; ?></h3>
                                <p>Total Products</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon revenue">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $productStats['available'] ?? 0; ?></h3>
                                <p>Available</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon pending">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $productStats['low_stock_count'] ?? 0; ?></h3>
                                <p>Low Stock</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon services">
                                <i class="bi bi-tags"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $productStats['categories_count'] ?? 0; ?></h3>
                                <p>Categories</p>
                            </div>
                        </div>
                    </div>

                    <!-- Products Grid -->
                    <?php if (empty($products)): ?>
                        <div class="empty-state">
                            <i class="bi bi-box-seam"></i>
                            <p>No products yet</p>
                            <span class="subtitle">Add your first product to get started</span>
                        </div>
                    <?php else: ?>
                    <div class="cards-grid">
                        <?php foreach ($products as $product):
                            $isLowStock = $product['Stock_Quantity'] <= 10 && $product['Stock_Quantity'] > 0;
                            $isOutOfStock = $product['Stock_Quantity'] == 0;
                        ?>
                        <div class="item-card" <?php if ($isLowStock): ?>style="border:2px solid var(--warning);"<?php endif; ?>>
                            <div class="item-image">
                                <?php if (!empty($product['Image_URL'])): ?>
                                    <img src="<?php echo htmlspecialchars($product['Image_URL']); ?>" alt="<?php echo htmlspecialchars($product['Name']); ?>">
                                <?php else: ?>
                                    <div class="placeholder"><i class="bi bi-box-seam"></i></div>
                                <?php endif; ?>
                                <span class="item-status <?php echo ($product['Is_Available'] && !$isOutOfStock) ? 'available' : 'unavailable'; ?>">
                                    <?php echo ($product['Is_Available'] && !$isOutOfStock) ? 'Available' : 'Unavailable'; ?>
                                </span>
                            </div>
                            <div class="item-content">
                                <h4><?php echo htmlspecialchars($product['Name']); ?></h4>
                                <p><?php echo htmlspecialchars($product['Description'] ?? 'No description'); ?></p>
                                <div class="item-meta">
                                    <span class="item-price">R <?php echo number_format($product['Price'], 2); ?></span>
                                    <span class="item-stock" style="color:<?php echo $isLowStock ? 'var(--warning)' : ($isOutOfStock ? 'var(--danger)' : 'var(--text-light)'); ?>;font-weight:600;">
                                        Stock: <?php echo $product['Stock_Quantity']; ?>
                                    </span>
                                </div>
                                <div class="item-actions">
                                    <button class="btn btn-sm btn-primary" onclick="editProduct(<?php echo $product['ID']; ?>)">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    <button class="btn btn-sm <?php echo $product['Is_Available'] ? 'btn-secondary' : 'btn-success'; ?>" 
                                            onclick="toggleProductAvailability(<?php echo $product['ID']; ?>, <?php echo $product['Is_Available'] ? 0 : 1; ?>)">
                                        <i class="bi bi-toggle-<?php echo $product['Is_Available'] ? 'on' : 'off'; ?>"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteProduct(<?php echo $product['ID']; ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- ==================== CUSTOMERS SECTION ==================== -->
                <div class="content-section <?php echo $activeSection === 'customers' ? 'active' : ''; ?>" id="section-customers">
                    <div class="section-header">
                        <h2>Customers</h2>
                    </div>

                    <!-- Customer Stats -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon bookings">
                                <i class="bi bi-people"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $totalCustomers; ?></h3>
                                <p>Total Customers</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon revenue">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo count(array_filter($customers, fn($c) => strtotime($c['createdAt']) > strtotime('-30 days'))); ?></h3>
                                <p>New (30 days)</p>
                            </div>
                        </div>
                    </div>

                    <!-- Search -->
                    <div class="filters">
                        <input type="text" class="search-input" id="customerSearch" placeholder="Search customers by name, email, or phone...">
                    </div>

                    <!-- Customers Table -->
                    <?php if (empty($customers)): ?>
                        <div class="empty-state">
                            <i class="bi bi-people"></i>
                            <p>No customers yet</p>
                            <span class="subtitle">Customers will appear here when they register or make bookings</span>
                        </div>
                    <?php else: ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Address</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($customer['Full_Name']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['Email']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['Phone'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($customer['Address'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($customer['createdAt'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- ==================== ADMINS SECTION ==================== -->
                <?php if ($currentAdmin && $currentAdmin['Role'] === 'main_admin'): ?>
                <div class="content-section <?php echo $activeSection === 'admins' ? 'active' : ''; ?>" id="section-admins">
                    <div class="section-header">
                        <h2>Admin Management</h2>
                        <button class="btn btn-primary" onclick="openAdminModal()">
                            <i class="bi bi-plus-lg"></i> Add Admin
                        </button>
                    </div>

                    <!-- Admin Stats -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon bookings">
                                <i class="bi bi-shield-lock"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $totalAdmins; ?></h3>
                                <p>Total Admins</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon revenue">
                                <i class="bi bi-person-check"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo count(array_filter($admins, fn($a) => $a['Role'] === 'main_admin')); ?></h3>
                                <p>Main Admins</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon pending">
                                <i class="bi bi-person"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo count(array_filter($admins, fn($a) => $a['Role'] === 'sub_admin')); ?></h3>
                                <p>Sub-Admins</p>
                            </div>
                        </div>
                    </div>

                    <!-- Admins Table -->
                    <?php if (empty($admins)): ?>
                        <div class="empty-state">
                            <i class="bi bi-shield-lock"></i>
                            <p>No admins found</p>
                        </div>
                    <?php else: ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($admins as $admin): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($admin['Full_Name']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['Email']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['Phone'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge <?php echo $admin['Role'] === 'main_admin' ? 'badge-primary' : 'badge-secondary'; ?>">
                                            <?php echo $admin['Role'] === 'main_admin' ? 'Main Admin' : 'Sub-Admin'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($admin['createdAt'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-primary" onclick="editAdmin(<?php echo $admin['ID']; ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <?php if ($admin['ID'] != $currentAdminId): ?>
                                            <button class="btn btn-sm btn-danger" onclick="deleteAdmin(<?php echo $admin['ID']; ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- ==================== PROFILE SECTION ==================== -->
                <div class="content-section <?php echo $activeSection === 'admin-profile' ? 'active' : ''; ?>" id="section-admin-profile">
                    <div class="section-header">
                        <h2>My Profile</h2>
                        <?php if (!$profileEditMode): ?>
                        <a href="?section=admin-profile&edit_profile=1" class="btn btn-primary">
                            <i class="bi bi-pencil"></i> Edit Profile
                        </a>
                        <?php endif; ?>
                    </div>

                    <?php if ($profileMessage): ?>
                    <div class="alert alert-success" style="margin-bottom:1.5rem;">
                        <i class="bi bi-check-circle"></i> <?php echo $profileMessage; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($profileError): ?>
                    <div class="alert alert-error" style="margin-bottom:1.5rem;">
                        <i class="bi bi-exclamation-circle"></i> <?php echo $profileError; ?>
                    </div>
                    <?php endif; ?>

                    <div class="content-row">
                        <!-- Profile Info Card -->
                        <div class="card" style="flex:1;">
                            <div class="card-header">
                                <h3><i class="bi bi-person-circle"></i> Profile Information</h3>
                            </div>
                            <div class="card-body">
                                <?php if ($profileEditMode): ?>
                                <!-- Edit Mode Form -->
                                <form method="post" action="?section=admin-profile">
                                    <div class="form-group">
                                        <label>Full Name <span class="text-danger">*</span></label>
                                        <input type="text" name="full_name" class="form-input" value="<?php echo htmlspecialchars($currentAdmin['Full_Name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" class="form-input" value="<?php echo htmlspecialchars($currentAdmin['Email'] ?? ''); ?>" disabled style="background:var(--secondary-color);">
                                        <small style="color:var(--text-light);">Email cannot be changed</small>
                                    </div>
                                    <div class="form-group">
                                        <label>Phone</label>
                                        <input type="tel" name="phone" class="form-input" value="<?php echo htmlspecialchars($currentAdmin['Phone'] ?? ''); ?>" maxlength="10" placeholder="e.g., 0821234567">
                                        <small style="color:var(--text-light);">10 digits (e.g., 0821234567)</small>
                                    </div>
                                    <div class="form-group">
                                        <label>Role</label>
                                        <input type="text" class="form-input" value="<?php echo $currentAdmin['Role'] === 'main_admin' ? 'Main Admin' : 'Sub-Admin'; ?>" disabled style="background:var(--secondary-color);">
                                    </div>
                                    <div class="form-group">
                                        <label>Member Since</label>
                                        <input type="text" class="form-input" value="<?php echo isset($currentAdmin['createdAt']) ? date('F d, Y', strtotime($currentAdmin['createdAt'])) : 'N/A'; ?>" disabled style="background:var(--secondary-color);">
                                    </div>
                                    <div class="form-actions" style="margin-top:1.5rem;">
                                        <a href="?section=admin-profile" class="btn btn-secondary">Cancel</a>
                                        <button type="submit" name="update_profile" class="btn btn-primary">
                                            <i class="bi bi-check-lg"></i> Save Changes
                                        </button>
                                    </div>
                                </form>
                                <?php else: ?>
                                <!-- View Mode -->
                                <div class="profile-display">
                                    <div class="info-item">
                                        <label>Full Name</label>
                                        <p><?php echo htmlspecialchars($currentAdmin['Full_Name'] ?? 'N/A'); ?></p>
                                    </div>
                                    <div class="info-item">
                                        <label>Email</label>
                                        <p><?php echo htmlspecialchars($currentAdmin['Email'] ?? 'N/A'); ?></p>
                                    </div>
                                    <div class="info-item">
                                        <label>Phone</label>
                                        <p><?php echo htmlspecialchars($currentAdmin['Phone'] ?? 'Not set'); ?></p>
                                    </div>
                                    <div class="info-item">
                                        <label>Role</label>
                                        <p><span class="badge <?php echo $currentAdmin['Role'] === 'main_admin' ? 'badge-primary' : 'badge-secondary'; ?>"><?php echo $currentAdmin['Role'] === 'main_admin' ? 'Main Admin' : 'Sub-Admin'; ?></span></p>
                                    </div>
                                    <div class="info-item">
                                        <label>Member Since</label>
                                        <p><?php echo isset($currentAdmin['createdAt']) ? date('F d, Y', strtotime($currentAdmin['createdAt'])) : 'N/A'; ?></p>
                                    </div>
                                    <div class="info-item">
                                        <label>Last Updated</label>
                                        <p><?php echo isset($currentAdmin['updatedAt']) ? date('F d, Y', strtotime($currentAdmin['updatedAt'])) : 'N/A'; ?></p>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Security Card -->
                        <div class="card" style="flex:1;">
                            <div class="card-header">
                                <h3><i class="bi bi-shield-check"></i> Security</h3>
                            </div>
                            <div class="card-body">
                                <div class="security-item">
                                    <div class="security-info">
                                        <h4>Password</h4>
                                        <p>Last changed: <?php echo isset($currentAdmin['updatedAt']) ? date('M d, Y', strtotime($currentAdmin['updatedAt'])) : 'Unknown'; ?></p>
                                    </div>
                                    <button class="btn btn-secondary" onclick="openModal('changePasswordModal')">
                                        <i class="bi bi-key"></i> Change Password
                                    </button>
                                </div>
                                <div class="security-item" style="margin-top:1.5rem; padding-top:1.5rem; border-top:1px solid var(--border-color);">
                                    <div class="security-info">
                                        <h4>Account Status</h4>
                                        <p><?php echo $currentAdmin['first_login'] ? '<span style="color:var(--warning);">First login - password reset required</span>' : '<span style="color:var(--success);">Active</span>'; ?></p>
                                    </div>
                                </div>
                                <div class="security-item" style="margin-top:1.5rem; padding-top:1.5rem; border-top:1px solid var(--border-color);">
                                    <div class="security-info">
                                        <h4>Session</h4>
                                        <p>Manage your active session</p>
                                    </div>
                                    <a href="includes/logout.inc.php" class="btn btn-danger">
                                        <i class="bi bi-box-arrow-right"></i> Logout
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- end content-sections -->
        
        <!-- Admin Footer -->
        <footer class="admin-footer">
            <div class="admin-footer-container">
                <p>RELM TECHNOLOGIES DEVELOPERS</p>
                <div class="admin-footer-links">
                    <a href="privacy.php">Privacy Policy</a>
                    <a href="terms.php">Terms of Service</a>
                </div>
            </div>
        </footer>
        </main>
    </div>

    <!-- ==================== MODALS ==================== -->

    <!-- Booking Details Modal -->
    <div class="modal" id="bookingModal">
        <div class="modal-content" style="max-width:750px;">
            <div class="modal-header">
                <h2><i class="bi bi-calendar-check"></i> Booking Details</h2>
                <button class="modal-close" onclick="closeModal('bookingModal')">&times;</button>
            </div>
            <div class="modal-body" id="bookingDetails"></div>
            <div class="modal-footer" id="bookingActions"></div>
        </div>
    </div>

    <!-- Quote Modal -->
    <div class="modal" id="quoteModal">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg, var(--success), #2ecc71);">
                <h2><i class="bi bi-cash"></i> Provide Quotation</h2>
                <button class="modal-close" onclick="closeModal('quoteModal')">&times;</button>
            </div>
            <form id="quoteForm">
                <input type="hidden" id="quoteBookingId">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Quoted Amount (R) <span class="text-danger">*</span></label>
                        <input type="number" class="form-input" id="quotedAmount" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Quote Breakdown</label>
                        <textarea class="form-textarea" id="quoteBreakdown" rows="3" placeholder="Itemized breakdown of costs..."></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Quote Validity (Days)</label>
                            <input type="number" class="form-input" id="quoteValidity" value="7" min="1">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea class="form-textarea" id="quoteNotes" rows="2" placeholder="Additional notes for the customer..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('quoteModal')">Cancel</button>
                    <button type="submit" class="btn btn-success">Provide Quote</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Quote Modal -->
    <div class="modal" id="viewQuoteModal">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg, #3498db, #2980b9);">
                <h2><i class="bi bi-file-earmark-text"></i> Quote Details</h2>
                <button class="modal-close" onclick="closeModal('viewQuoteModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Quoted Amount</label>
                    <div id="viewQuoteAmount" style="font-size: 1.5rem; font-weight: bold; color: var(--success);"></div>
                </div>
                <div class="form-group">
                    <label>Quote Breakdown</label>
                    <div id="viewQuoteBreakdown" class="form-textarea" style="background: #f8f9fa; white-space: pre-wrap;"></div>
                </div>
                <div class="form-group">
                    <label>Quote Validity</label>
                    <div id="viewQuoteValidity" style="font-weight: 500;"></div>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <div id="viewQuoteNotes" class="form-textarea" style="background: #f8f9fa; white-space: pre-wrap;"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('viewQuoteModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Add Service Modal -->
    <div class="modal" id="addServiceModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="bi bi-plus-lg"></i> Add New Service</h2>
                <button class="modal-close" onclick="closeModal('addServiceModal')">&times;</button>
            </div>
            <form id="addServiceForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Service Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-input" name="Name" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea class="form-textarea" name="Description" rows="3"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Duration (minutes) <span class="text-danger">*</span></label>
                            <input type="number" class="form-input" name="Duration" min="1" required>
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <input type="text" class="form-input" name="Category" list="serviceCategoryList">
                            <datalist id="serviceCategoryList">
                                <option value="Residential Cleaning">
                                <option value="Commercial Cleaning">
                                <option value="Industrial Cleaning">
                                <option value="Specialized Services">
                                <option value="Maintenance">
                            </datalist>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Service Image</label>
                        <input type="file" class="form-input" name="image" accept="image/*">
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="Is_Available" value="1" checked>
                        <label>Available for booking</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addServiceModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Service</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Service Modal -->
    <div class="modal" id="editServiceModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="bi bi-pencil"></i> Edit Service</h2>
                <button class="modal-close" onclick="closeModal('editServiceModal')">&times;</button>
            </div>
            <form id="editServiceForm" enctype="multipart/form-data">
                <input type="hidden" name="id" id="editServiceId">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Service Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-input" name="Name" id="editServiceName" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea class="form-textarea" name="Description" id="editServiceDescription" rows="3"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Duration (minutes) <span class="text-danger">*</span></label>
                            <input type="number" class="form-input" name="Duration" id="editServiceDuration" min="1" required>
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <input type="text" class="form-input" name="Category" id="editServiceCategory">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Current Image</label>
                        <div id="editServiceImagePreview" class="mb-2"></div>
                        <input type="file" class="form-input" name="image" accept="image/*">
                        <small style="color:var(--text-light);">Leave empty to keep current image</small>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="Is_Available" id="editServiceIsAvailable" value="1">
                        <label>Available for booking</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editServiceModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Service</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal" id="addProductModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="bi bi-plus-lg"></i> Add New Product</h2>
                <button class="modal-close" onclick="closeModal('addProductModal')">&times;</button>
            </div>
            <form id="addProductForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Product Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-input" name="Name" required>
                        </div>
                        <div class="form-group">
                            <label>Price (R) <span class="text-danger">*</span></label>
                            <input type="number" class="form-input" name="Price" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea class="form-textarea" name="Description" rows="3"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Stock Quantity <span class="text-danger">*</span></label>
                            <input type="number" class="form-input" name="Stock_Quantity" min="0" value="0" required>
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <input type="text" class="form-input" name="Category" list="productCategoryList">
                            <datalist id="productCategoryList">
                                <option value="Cleaning Supplies">
                                <option value="Equipment">
                                <option value="Safety Gear">
                                <option value="Accessories">
                            </datalist>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Product Image</label>
                        <input type="file" class="form-input" name="image" accept="image/*">
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="Is_Available" value="1" checked>
                        <label>Available for purchase</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addProductModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal" id="editProductModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="bi bi-pencil"></i> Edit Product</h2>
                <button class="modal-close" onclick="closeModal('editProductModal')">&times;</button>
            </div>
            <form id="editProductForm" enctype="multipart/form-data">
                <input type="hidden" name="id" id="editProductId">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Product Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-input" name="Name" id="editProductName" required>
                        </div>
                        <div class="form-group">
                            <label>Price (R) <span class="text-danger">*</span></label>
                            <input type="number" class="form-input" name="Price" id="editProductPrice" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea class="form-textarea" name="Description" id="editProductDescription" rows="3"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Stock Quantity <span class="text-danger">*</span></label>
                            <input type="number" class="form-input" name="Stock_Quantity" id="editProductStock" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <input type="text" class="form-input" name="Category" id="editProductCategory">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Current Image</label>
                        <div id="editProductImagePreview" class="mb-2"></div>
                        <input type="file" class="form-input" name="image" accept="image/*">
                        <small style="color:var(--text-light);">Leave empty to keep current image</small>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="Is_Available" id="editProductIsAvailable" value="1">
                        <label>Available for purchase</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editProductModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Admin Modal (Add/Edit) -->
    <div class="modal" id="adminModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="adminModalTitle"><i class="bi bi-shield-plus"></i> Add Admin</h2>
                <button class="modal-close" onclick="closeModal('adminModal')">&times;</button>
            </div>
            <form id="adminForm">
                <input type="hidden" id="adminId">
                <input type="hidden" id="generatedPassword">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-input" id="adminName" required>
                    </div>
                    <div class="form-group">
                        <label>Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-input" id="adminEmail" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone <span class="text-danger">*</span></label>
                            <input type="tel" class="form-input" id="adminPhone" required>
                        </div>
                        <div class="form-group">
                            <label>Role <span class="text-danger">*</span></label>
                            <select class="form-input" id="adminRole" required>
                                <option value="sub_admin">Sub-Admin</option>
                                <option value="main_admin">Main Admin</option>
                            </select>
                        </div>
                    </div>
                    <!-- Password Display Section (hidden initially) -->
                    <div id="passwordDisplaySection" style="display:none; margin-top:1.5rem; padding:1.5rem; background:var(--success-light); border:2px solid var(--success); border-radius:var(--border-radius);">
                        <div style="text-align:center; margin-bottom:1rem;">
                            <i class="bi bi-check-circle-fill" style="font-size:2rem; color:var(--success);"></i>
                            <h3 style="margin:0.5rem 0; color:var(--success);">Password Generated!</h3>
                            <p style="margin:0; color:var(--text-light); font-size:0.9rem;">Copy this password and share it with the new admin</p>
                        </div>
                        <div style="display:flex; gap:0.5rem; margin-bottom:1rem;">
                            <input type="text" id="displayGeneratedPassword" class="form-input" style="text-align:center; font-family:monospace; font-size:1.1rem; font-weight:600; letter-spacing:1px;" readonly>
                            <button type="button" class="btn btn-primary" onclick="copyGeneratedPassword()" style="white-space:nowrap;">
                                <i class="bi bi-clipboard"></i> Copy
                            </button>
                        </div>
                        <div id="copyFeedback" style="text-align:center; color:var(--success); font-weight:500; display:none;">
                            <i class="bi bi-check"></i> Password copied to clipboard!
                        </div>
                    </div>
                </div>
                <div class="modal-footer" id="adminModalFooter">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('adminModal')">Cancel</button>
                    <button type="button" id="generatePasswordBtn" class="btn btn-warning" onclick="generateAdminPassword()">
                        <i class="bi bi-key"></i> Generate Password
                    </button>
                    <button type="submit" id="saveAdminBtn" class="btn btn-primary" style="display:none;">
                        <i class="bi bi-check-lg"></i> Save Admin
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal" id="changePasswordModal">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg, var(--warning), #f39c12);">
                <h2><i class="bi bi-key"></i> Change Password</h2>
                <button class="modal-close" onclick="closeModal('changePasswordModal')">&times;</button>
            </div>
            <form id="changePasswordForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Current Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-input" id="currentPassword" required>
                    </div>
                    <div class="form-group">
                        <label>New Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-input" id="newPassword" minlength="8" required>
                        <small style="color:var(--text-light);">Minimum 8 characters</small>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-input" id="confirmPassword" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('changePasswordModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Change Password</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer" style="position:fixed;top:20px;right:20px;z-index:99999;"></div>

    <script src="./js/modal.js"></script>
    <script>
        // ========== DATA ==========
        const bookings = <?php echo json_encode($bookings); ?>;
        const services = <?php echo json_encode($services); ?>;
        const products = <?php echo json_encode($products); ?>;
        const customers = <?php echo json_encode($customers); ?>;
        const admins = <?php echo json_encode($admins); ?>;

        // ========== SECTION NAVIGATION ==========
        const sectionTitles = {
            dashboard: 'Dashboard',
            bookings: 'Manage Bookings',
            services: 'Manage Services',
            products: 'Manage Products',
            customers: 'Customer Management',
            admins: 'Admin Management',
            'admin-profile': 'My Profile'
        };

        function switchSection(section) {
            // Hide all sections
            document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
            // Show target section
            const target = document.getElementById('section-' + section);
            if (target) target.classList.add('active');
            // Update nav links
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.toggle('active', link.dataset.section === section);
            });
            // Update header title
            document.getElementById('sectionTitle').textContent = sectionTitles[section] || 'Dashboard';
            // Update URL without reload
            const url = new URL(window.location);
            url.searchParams.set('section', section);
            url.searchParams.delete('page');
            url.searchParams.delete('status');
            url.searchParams.delete('search');
            history.pushState({}, '', url);
        }

        // Nav link clicks
        document.querySelectorAll('.nav-link[data-section]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                switchSection(link.dataset.section);
                // Close sidebar after navigation
                document.querySelector('.sidebar').classList.remove('mobile-open');
                if (sidebarOverlay) {
                    sidebarOverlay.classList.remove('active');
                }
            });
        });

        // ========== REAL-TIME BOOKING FILTERING ==========
        const bookingFilterForm = document.getElementById('bookingFilterForm');
        if (bookingFilterForm) {
            // Add event listeners to all filter inputs for real-time filtering
            const filterInputs = bookingFilterForm.querySelectorAll('input, select');
            filterInputs.forEach(input => {
                // Use 'input' for text fields and 'change' for selects/dates
                const eventType = (input.type === 'text' || input.type === 'search') ? 'input' : 'change';
                input.addEventListener(eventType, function() {
                    // Debounce to avoid too many requests
                    clearTimeout(this.filterTimeout);
                    this.filterTimeout = setTimeout(() => {
                        bookingFilterForm.submit();
                    }, 300);
                });
            });
        }

        // Clear filters function
        function clearFilters() {
            const form = document.getElementById('bookingFilterForm');
            if (form) {
                // Reset all inputs except section
                form.querySelectorAll('input:not([name="section"]), select').forEach(input => {
                    input.value = '';
                });
                form.submit();
            }
        }

        // Quick action / view-all clicks
        document.querySelectorAll('[data-section]').forEach(el => {
            if (el.classList.contains('nav-link')) return; // already handled
            el.addEventListener('click', (e) => {
                e.preventDefault();
                switchSection(el.dataset.section);
            });
        });

        // Sidebar mobile toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        const headerToggle = document.getElementById('headerToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.querySelector('.sidebar-overlay');
        
        function toggleSidebar() {
            sidebar.classList.toggle('mobile-open');
            if (sidebarOverlay) {
                sidebarOverlay.classList.toggle('active');
            }
        }
        
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', toggleSidebar);
        }
        
        if (headerToggle) {
            headerToggle.addEventListener('click', toggleSidebar);
        }
        
        // Close sidebar when clicking overlay
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', toggleSidebar);
        }

        // ========== MODAL HELPERS ==========
        function openModal(id) {
            document.getElementById(id).classList.add('active');
        }
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        // Close modal on backdrop click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) modal.classList.remove('active');
            });
        });

        // ========== TOAST ==========
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            const icons = {
                success: '<i class="fas fa-check-circle"></i>',
                danger: '<i class="fas fa-exclamation-circle"></i>',
                warning: '<i class="fas fa-exclamation-triangle"></i>',
                info: '<i class="fas fa-info-circle"></i>'
            };
            
            const colors = {
                success: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
                danger: 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)',
                warning: 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
                info: 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)'
            };
            
            toast.style.cssText = `
                padding: 1rem 1.5rem; border-radius: 12px; color: #fff; margin-bottom: 0.75rem;
                background: ${colors[type] || colors.success};
                box-shadow: 0 10px 25px rgba(0,0,0,0.2); display: flex; align-items: center; gap: 0.75rem;
                animation: toastSlideIn 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55); font-weight: 600;
                min-width: 300px; max-width: 400px;
            `;
            toast.innerHTML = `${icons[type] || icons.success}<span>${message}</span>`;
            document.getElementById('toastContainer').appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'toastSlideOut 0.3s ease forwards';
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }
        
        // Add toast animations
        const toastStyles = document.createElement('style');
        toastStyles.textContent = `
            @keyframes toastSlideIn {
                from {
                    opacity: 0;
                    transform: translateX(100%);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }
            
            @keyframes toastSlideOut {
                from {
                    opacity: 1;
                    transform: translateX(0);
                }
                to {
                    opacity: 0;
                    transform: translateX(100%);
                }
            }
        `;
        document.head.appendChild(toastStyles);

        // ========== BOOKINGS FUNCTIONS ==========
        function getStatusColor(status) {
            const colors = {
                'requested': 'var(--warning)',
                'contacted': 'var(--info)',
                'in_progress': 'var(--primary-color)',
                'quoted': 'var(--text-light)',
                'confirmed': 'var(--success)',
                'completed': '#2c3e50',
                'cancelled': 'var(--danger)'
            };
            return colors[status] || 'var(--text-light)';
        }

        function getRelativeDate(dateString) {
            const date = new Date(dateString);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);

            const checkDate = new Date(date);
            checkDate.setHours(0, 0, 0, 0);

            const diffDays = Math.floor((today - checkDate) / (1000 * 60 * 60 * 24));

            if (checkDate.getTime() === today.getTime()) {
                return 'Today';
            } else if (checkDate.getTime() === yesterday.getTime()) {
                return 'Yesterday';
            } else if (diffDays === 2) {
                return 'Day before yesterday';
            } else if (diffDays <= 7) {
                return date.toLocaleDateString('en-US', { weekday: 'long' });
            } else {
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            }
        }

        function viewBooking(id) {
            const booking = bookings.find(b => b.ID == id);
            if (!booking) return;

            const status = booking.Status;
            const canQuote = status === 'requested' || status === 'contacted' || status === 'in_progress';
            const canContact = status === 'requested';
            const hasQuote = booking.Quoted_Amount && booking.Quoted_Amount > 0;

            const detailsHtml = `
                <div class="booking-details-grid">
                    <!-- Service Information Card -->
                    <div style="background: var(--secondary-color); padding: 1.5rem; border-radius: var(--border-radius);">
                        <h4 style="margin-bottom: 1rem; color: var(--text-light); display: flex; align-items: center; gap: 0.5rem;">
                            <i class="bi bi-briefcase"></i> Service Information
                        </h4>
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <i class="bi bi-tag" style="color: var(--primary-color); width: 20px;"></i>
                                <div>
                                    <span style="color: var(--text-light); font-size: 0.85rem;">Service</span>
                                    <p style="margin: 0; font-weight: 500;">${booking.Service_Name}</p>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <i class="bi bi-clock" style="color: var(--primary-color); width: 20px;"></i>
                                <div>
                                    <span style="color: var(--text-light); font-size: 0.85rem;">Duration</span>
                                    <p style="margin: 0; font-weight: 500;">${booking.Service_Duration} minutes</p>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <i class="bi bi-calendar-plus" style="color: var(--primary-color); width: 20px;"></i>
                                <div>
                                    <span style="color: var(--text-light); font-size: 0.85rem;">Booked</span>
                                    <p style="margin: 0; font-weight: 500;">${getRelativeDate(booking.created_at)}</p>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <i class="bi bi-calendar-check" style="color: var(--primary-color); width: 20px;"></i>
                                <div>
                                    <span style="color: var(--text-light); font-size: 0.85rem;">Scheduled</span>
                                    <p style="margin: 0; font-weight: 500;">${new Date(booking.Date).toLocaleDateString()}</p>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <i class="bi bi-clock-history" style="color: var(--primary-color); width: 20px;"></i>
                                <div>
                                    <span style="color: var(--text-light); font-size: 0.85rem;">Time</span>
                                    <p style="margin: 0; font-weight: 500;">${new Date('2000-01-01T' + booking.Time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</p>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <i class="bi bi-info-circle" style="color: var(--primary-color); width: 20px;"></i>
                                <div>
                                    <span style="color: var(--text-light); font-size: 0.85rem;">Status</span>
                                    <p style="margin: 0;">
                                        <span class="badge ${status === 'requested' ? 'badge-warning' : status === 'confirmed' ? 'badge-success' : status === 'cancelled' ? 'badge-danger' : 'badge-info'}" style="text-transform: capitalize;">${booking.Status}</span>
                                    </p>
                                </div>
                            </div>
                            ${hasQuote ? `
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <i class="bi bi-cash" style="color: var(--success); width: 20px;"></i>
                                <div>
                                    <span style="color: var(--text-light); font-size: 0.85rem;">Quote</span>
                                    <p style="margin: 0; font-weight: 500; color: var(--success);">R ${parseFloat(booking.Quoted_Amount).toFixed(2)}</p>
                                </div>
                            </div>
                            ` : ''}
                        </div>
                    </div>

                    <!-- Customer Information Card -->
                    <div style="background: var(--secondary-color); padding: 1.5rem; border-radius: var(--border-radius);">
                        <h4 style="margin-bottom: 1rem; color: var(--text-light); display: flex; align-items: center; gap: 0.5rem;">
                            <i class="bi bi-person"></i> Customer Information
                        </h4>
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <i class="bi bi-person-circle" style="color: var(--primary-color); width: 20px;"></i>
                                <div>
                                    <span style="color: var(--text-light); font-size: 0.85rem;">Name</span>
                                    <p style="margin: 0; font-weight: 500;">${booking.Customer_Name}</p>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <i class="bi bi-envelope" style="color: var(--primary-color); width: 20px;"></i>
                                <div>
                                    <span style="color: var(--text-light); font-size: 0.85rem;">Email</span>
                                    <p style="margin: 0; font-weight: 500;">${booking.Customer_Email}</p>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <i class="bi bi-telephone" style="color: var(--primary-color); width: 20px;"></i>
                                <div>
                                    <span style="color: var(--text-light); font-size: 0.85rem;">Phone</span>
                                    <p style="margin: 0; font-weight: 500;">${booking.Customer_Phone || 'N/A'}</p>
                                </div>
                            </div>
                            <div style="display: flex; align-items: start; gap: 0.75rem;">
                                <i class="bi bi-geo-alt" style="color: var(--primary-color); width: 20px; margin-top: 2px;"></i>
                                <div>
                                    <span style="color: var(--text-light); font-size: 0.85rem;">Address</span>
                                    <p style="margin: 0; font-weight: 500;">${booking.Address}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                ${booking.Special_Instructions ? `
                <div style="margin-top: 1.5rem; background: var(--secondary-color); padding: 1.5rem; border-radius: var(--border-radius);">
                    <h4 style="margin-bottom: 0.75rem; color: var(--text-light); display: flex; align-items: center; gap: 0.5rem;">
                        <i class="bi bi-chat-text"></i> Special Instructions
                    </h4>
                    <p style="margin: 0; padding: 1rem; background: white; border-radius: 8px; border-left: 4px solid var(--primary-color);">${booking.Special_Instructions}</p>
                </div>
                ` : ''}

                ${booking.Property_Type ? `
                <div style="margin-top: 1.5rem; background: var(--secondary-color); padding: 1.5rem; border-radius: var(--border-radius);">
                    <h4 style="margin-bottom: 1rem; color: var(--text-light); display: flex; align-items: center; gap: 0.5rem;">
                        <i class="bi bi-house"></i> Property Details
                    </h4>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <i class="bi bi-building" style="color: var(--primary-color); width: 20px;"></i>
                            <div>
                                <span style="color: var(--text-light); font-size: 0.85rem;">Type</span>
                                <p style="margin: 0; font-weight: 500;">${booking.Property_Type}</p>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <i class="bi bi-aspect-ratio" style="color: var(--primary-color); width: 20px;"></i>
                            <div>
                                <span style="color: var(--text-light); font-size: 0.85rem;">Size</span>
                                <p style="margin: 0; font-weight: 500;">${booking.Property_Size || 'N/A'}</p>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <i class="bi bi-arrow-repeat" style="color: var(--primary-color); width: 20px;"></i>
                            <div>
                                <span style="color: var(--text-light); font-size: 0.85rem;">Frequency</span>
                                <p style="margin: 0; font-weight: 500;">${booking.Cleaning_Frequency || 'N/A'}</p>
                            </div>
                        </div>
                    </div>
                </div>
                ` : ''}
            `;

            let actionsHtml = '<div style="display:flex;gap:0.5rem;flex-wrap:wrap;">';
            if (canContact) {
                actionsHtml += `<button type="button" class="btn btn-primary btn-sm" onclick="contactCustomer(${booking.ID}, '${booking.Customer_Email}')"><i class="bi bi-telephone"></i> Contact Customer</button>`;
            }
            if (canQuote) {
                actionsHtml += `<button type="button" class="btn btn-success btn-sm" onclick="closeModal('bookingModal'); openQuoteModal(${booking.ID}, ${booking.Quoted_Amount || 'null'})"><i class="bi bi-cash"></i> ${hasQuote ? 'Update Quote' : 'Provide Quote'}</button>`;
            }
            if (hasQuote) {
                actionsHtml += `<button type="button" class="btn btn-info btn-sm" onclick="downloadQuotePDF(${booking.ID})"><i class="bi bi-file-pdf"></i> Download PDF</button>`;
            }
            actionsHtml += `<button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('bookingModal')">Close</button></div>`;

            document.getElementById('bookingDetails').innerHTML = detailsHtml;
            document.getElementById('bookingActions').innerHTML = actionsHtml;
            openModal('bookingModal');
        }

        async function contactCustomer(bookingId, customerEmail) {
            ModalDialog.confirm(`Send contact notification to customer (${customerEmail})?`, async () => {
                try {
                    const response = await fetch('api/admin_actions.php?action=contact-customer', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ booking_id: bookingId })
                    });
                    const result = await response.json();
                    if (result.success) {
                        showToast('Contact notification sent to customer!');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast(result.message || 'Error sending notification', 'danger');
                    }
                } catch (error) {
                    showToast('Error contacting customer', 'danger');
                }
            }, { title: 'Send Contact Notification', type: 'info' });
        }

        function downloadQuotePDF(bookingId) {
            window.open('api/generate_quote_pdf.php?id=' + bookingId, '_blank');
        }

        function viewQuoteDetails(id, breakdown, validityDays, notes, amount) {
            document.getElementById('viewQuoteBreakdown').textContent = breakdown || 'No breakdown provided';
            document.getElementById('viewQuoteValidity').textContent = validityDays ? validityDays + ' days' : '7 days';
            document.getElementById('viewQuoteNotes').textContent = notes || 'No notes';
            document.getElementById('viewQuoteAmount').textContent = 'R ' + parseFloat(amount || 0).toFixed(2);
            openModal('viewQuoteModal');
        }

        async function quickAction(id, action) {
            const endpoints = {
                'contact': 'mark-contacted',
                'in_progress': 'move-in-progress'
            };
            try {
                const response = await fetch(`api/bookings.php?path=${endpoints[action]}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                const result = await response.json();
                if (result.success) { showToast(result.message); setTimeout(() => location.reload(), 1000); }
                else { showToast(result.message, 'danger'); }
            } catch (error) { showToast('Error performing action', 'danger'); }
        }

        async function updateStatus(id, status) {
            try {
                const response = await fetch(`api/bookings.php?id=${id}&path=status`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ status })
                });
                const result = await response.json();
                if (result.success) { showToast(result.message); setTimeout(() => location.reload(), 1000); }
                else { showToast(result.message, 'danger'); }
            } catch (error) { showToast('Error updating status', 'danger'); }
        }

        function openQuoteModal(id, currentAmount) {
            document.getElementById('quoteBookingId').value = id;
            document.getElementById('quotedAmount').value = currentAmount || '';
            openModal('quoteModal');
        }

        document.getElementById('quoteForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const data = {
                id: document.getElementById('quoteBookingId').value,
                quoted_amount: document.getElementById('quotedAmount').value,
                quote_breakdown: document.getElementById('quoteBreakdown').value,
                quote_validity_days: document.getElementById('quoteValidity').value,
                quote_notes: document.getElementById('quoteNotes').value
            };
            try {
                const response = await fetch('api/bookings.php?path=provide-quotation', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (result.success) { showToast(result.message); closeModal('quoteModal'); setTimeout(() => location.reload(), 1000); }
                else { showToast(result.message, 'danger'); }
            } catch (error) { showToast('Error providing quotation', 'danger'); }
        });

        function exportBookings() {
            const date = new Date().toISOString().split('T')[0];
            let html = `
                <html>
                <head>
                    <title>Bookings Report - ${date}</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        h1 { color: #333; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
                        th { background: #667eea; color: white; }
                        tr:nth-child(even) { background: #f9f9f9; }
                        .status { padding: 3px 8px; border-radius: 4px; font-size: 11px; }
                        .status-requested { background: #e0f2fe; color: #0369a1; }
                        .status-contacted { background: #fef3c7; color: #b45309; }
                        .status-in_progress { background: #dbeafe; color: #1d4ed8; }
                        .status-quoted { background: #f3e8ff; color: #7c3aed; }
                        .status-confirmed { background: #d1fae5; color: #059669; }
                        .status-completed { background: #dcfce7; color: #16a34a; }
                        .status-cancelled { background: #fee2e2; color: #dc2626; }
                    </style>
                </head>
                <body>
                    <h1>Phambili Ma-Africa - Bookings Report</h1>
                    <p>Generated: ${new Date().toLocaleString()}</p>
                    <table>
                        <tr>
                            <th>ID</th>
                            <th>Service</th>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Address</th>
                            <th>Instructions</th>
                            <th>Amount</th>
                        </tr>
            `;
            
            bookings.forEach(b => {
                html += `
                    <tr>
                        <td>${b.ID}</td>
                        <td>${b.Service_Name || ''}</td>
                        <td>${b.Customer_Name || ''}</td>
                        <td>${b.Customer_Email || ''}</td>
                        <td>${b.Customer_Phone || ''}</td>
                        <td>${b.Date || ''}</td>
                        <td>${b.Time || ''}</td>
                        <td><span class="status status-${b.Status}">${b.Status || ''}</span></td>
                        <td>${b.Address || ''}</td>
                        <td>${b.Special_Instructions || ''}</td>
                        <td>${b.Quoted_Amount ? 'R ' + parseFloat(b.Quoted_Amount).toFixed(2) : ''}</td>
                    </tr>
                `;
            });
            
            html += `
                    </table>
                </body>
                </html>
            `;
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(html);
            printWindow.document.close();
            printWindow.print();
        }

        // ========== SERVICES FUNCTIONS ==========
        document.getElementById('addServiceForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            try {
                const response = await fetch('api/services.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) { showToast('Service created successfully!'); setTimeout(() => location.reload(), 1000); }
                else { showToast(result.message, 'danger'); }
            } catch (error) { showToast('Error creating service', 'danger'); }
        });

        function editService(id) {
            const service = services.find(s => s.ID == id);
            if (!service) return;
            document.getElementById('editServiceId').value = service.ID;
            document.getElementById('editServiceName').value = service.Name;
            document.getElementById('editServiceDescription').value = service.Description || '';
            document.getElementById('editServiceDuration').value = service.Duration;
            document.getElementById('editServiceCategory').value = service.Category || '';
            document.getElementById('editServiceIsAvailable').checked = service.Is_Available == 1;
            const previewDiv = document.getElementById('editServiceImagePreview');
            previewDiv.innerHTML = service.Image_URL
                ? `<img src="${service.Image_URL}" style="max-height:120px;border-radius:8px;">`
                : '<p style="color:var(--text-light)">No image uploaded</p>';
            openModal('editServiceModal');
        }

        document.getElementById('editServiceForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const id = formData.get('id');
            formData.append('_method', 'PUT');
            try {
                const response = await fetch(`api/services.php?id=${id}`, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) { showToast('Service updated successfully!'); setTimeout(() => location.reload(), 1000); }
                else { showToast(result.message, 'danger'); }
            } catch (error) { showToast('Error updating service', 'danger'); }
        });

        async function toggleServiceAvailability(id, isAvailable) {
            ModalDialog.confirm(`Are you sure you want to ${isAvailable ? 'activate' : 'deactivate'} this service?`, async () => {
                try {
                    const response = await fetch('api/services.php?path=toggle-availability', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id, is_available: isAvailable })
                    });
                    const result = await response.json();
                    if (result.success) { showToast(`Service ${isAvailable ? 'activated' : 'deactivated'} successfully!`); setTimeout(() => location.reload(), 1000); }
                    else { showToast(result.message, 'danger'); }
                } catch (error) { showToast('Error toggling availability', 'danger'); }
            }, { title: 'Confirm Action', type: 'warning' });
        }

        async function deleteService(id) {
            ModalDialog.confirm('Are you sure you want to delete this service? This action cannot be undone.', async () => {
                try {
                    const response = await fetch(`api/services.php?id=${id}`, { method: 'DELETE' });
                    const result = await response.json();
                    if (result.success) { showToast('Service deleted successfully!'); setTimeout(() => location.reload(), 1000); }
                    else { showToast(result.message, 'danger'); }
                } catch (error) { showToast('Error deleting service', 'danger'); }
            }, { title: 'Delete Service', type: 'danger' });
        }

        // ========== PRODUCTS FUNCTIONS ==========
        document.getElementById('addProductForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            try {
                const response = await fetch('api/products.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) { showToast('Product created successfully!'); setTimeout(() => location.reload(), 1000); }
                else { showToast(result.message, 'danger'); }
            } catch (error) { showToast('Error creating product', 'danger'); }
        });

        function editProduct(id) {
            const product = products.find(p => p.ID == id);
            if (!product) return;
            document.getElementById('editProductId').value = product.ID;
            document.getElementById('editProductName').value = product.Name;
            document.getElementById('editProductDescription').value = product.Description || '';
            document.getElementById('editProductPrice').value = product.Price;
            document.getElementById('editProductStock').value = product.Stock_Quantity;
            document.getElementById('editProductCategory').value = product.Category || '';
            document.getElementById('editProductIsAvailable').checked = product.Is_Available == 1;
            const previewDiv = document.getElementById('editProductImagePreview');
            previewDiv.innerHTML = product.Image_URL
                ? `<img src="${product.Image_URL}" style="max-height:120px;border-radius:8px;">`
                : '<p style="color:var(--text-light)">No image uploaded</p>';
            openModal('editProductModal');
        }

        document.getElementById('editProductForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const id = formData.get('id');
            formData.append('_method', 'PUT');
            try {
                const response = await fetch(`api/products.php?id=${id}`, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) { showToast('Product updated successfully!'); setTimeout(() => location.reload(), 1000); }
                else { showToast(result.message, 'danger'); }
            } catch (error) { showToast('Error updating product', 'danger'); }
        });

        async function toggleProductAvailability(id, isAvailable) {
            ModalDialog.confirm(`Are you sure you want to ${isAvailable ? 'activate' : 'deactivate'} this product?`, async () => {
                try {
                    const response = await fetch('api/products.php?path=toggle-availability', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id, is_available: isAvailable })
                    });
                    const result = await response.json();
                    if (result.success) { showToast(result.message); setTimeout(() => location.reload(), 1000); }
                    else { showToast(result.message, 'danger'); }
                } catch (error) { showToast('Error toggling availability', 'danger'); }
            }, { title: 'Confirm Action', type: 'warning' });
        }

        async function deleteProduct(id) {
            ModalDialog.confirm('Are you sure you want to delete this product? This action cannot be undone.', async () => {
                try {
                    const response = await fetch(`api/products.php?id=${id}`, { method: 'DELETE' });
                    const result = await response.json();
                    if (result.success) { showToast('Product deleted successfully!'); setTimeout(() => location.reload(), 1000); }
                    else { showToast(result.message, 'danger'); }
                } catch (error) { showToast('Error deleting product', 'danger'); }
            }, { title: 'Delete Product', type: 'danger' });
        }

        // ========== CUSTOMERS FUNCTIONS (Display Only) ==========
        // Customer search
        document.getElementById('customerSearch')?.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#section-customers table tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // ========== PASSWORD CHANGE ==========
        document.getElementById('changePasswordForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (newPassword !== confirmPassword) {
                showToast('Passwords do not match', 'danger');
                return;
            }
            
            if (newPassword.length < 8) {
                showToast('Password must be at least 8 characters long', 'danger');
                return;
            }
            
            try {
                const response = await fetch('api/admin_profile.php?action=change_password', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        current_password: currentPassword,
                        new_password: newPassword
                    })
                });
                const result = await response.json();
                if (result.success) { 
                    showToast('Password changed successfully!'); 
                    closeModal('changePasswordModal'); 
                    e.target.reset();
                } else { 
                    showToast(result.message, 'danger'); 
                }
            } catch (error) { 
                showToast('Error changing password', 'danger'); 
            }
        });

        // ========== ADMIN FUNCTIONS ==========
        // Generate random password
        function generateRandomPassword() {
            const length = 12;
            const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
            let password = "";
            for (let i = 0; i < length; i++) {
                password += charset.charAt(Math.floor(Math.random() * charset.length));
            }
            return password;
        }

        // Generate password button handler
        function generateAdminPassword() {
            // Validate all fields first
            const name = document.getElementById('adminName').value.trim();
            const email = document.getElementById('adminEmail').value.trim();
            const phone = document.getElementById('adminPhone').value.trim();
            const role = document.getElementById('adminRole').value;

            if (!name || !email || !phone || !role) {
                showToast('Please fill in all fields before generating password', 'danger');
                return;
            }

            // Generate password
            const password = generateRandomPassword();
            document.getElementById('generatedPassword').value = password;
            document.getElementById('displayGeneratedPassword').value = password;

            // Show password display section
            document.getElementById('passwordDisplaySection').style.display = 'block';

            // Switch buttons
            document.getElementById('generatePasswordBtn').style.display = 'none';
            document.getElementById('saveAdminBtn').style.display = 'inline-flex';

            // Auto copy to clipboard
            copyGeneratedPassword();
        }

        // Copy generated password to clipboard
        function copyGeneratedPassword() {
            const passwordInput = document.getElementById('displayGeneratedPassword');
            passwordInput.select();
            passwordInput.setSelectionRange(0, 99999); // For mobile
            navigator.clipboard.writeText(passwordInput.value).then(() => {
                document.getElementById('copyFeedback').style.display = 'block';
                setTimeout(() => {
                    document.getElementById('copyFeedback').style.display = 'none';
                }, 3000);
            }).catch(() => {
                showToast('Failed to copy password. Please copy manually.', 'warning');
            });
        }

        // Admin form submit handler
        document.getElementById('adminForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const adminId = document.getElementById('adminId').value;
            const generatedPassword = document.getElementById('generatedPassword').value;

            const data = {
                Full_Name: document.getElementById('adminName').value,
                Email: document.getElementById('adminEmail').value,
                Phone: document.getElementById('adminPhone').value,
                Role: document.getElementById('adminRole').value,
                Password: generatedPassword || undefined
            };

            try {
                const method = adminId ? 'PUT' : 'POST';
                const url = adminId ? `api/admin_management.php?id=${adminId}` : 'api/admin_management.php';

                const response = await fetch(url, {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    const pwdMsg = result.temp_password ? ` Temporary password: ${result.temp_password}` : '';
                    showToast(result.message + pwdMsg);
                    closeModal('adminModal');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(result.message, 'danger');
                }
            } catch (error) {
                showToast('Error saving admin', 'danger');
            }
        });

        async function loadAdminData(adminId) {
            try {
                const response = await fetch(`api/admin_management.php?id=${adminId}`);
                const result = await response.json();

                if (result.success && result.admin) {
                    const admin = result.admin;
                    document.getElementById('adminId').value = admin.ID;
                    document.getElementById('adminName').value = admin.Full_Name || '';
                    document.getElementById('adminEmail').value = admin.Email;
                    document.getElementById('adminPhone').value = admin.Phone || '';
                    document.getElementById('adminRole').value = admin.Role;
                    // Hide password generation for edit mode
                    document.getElementById('passwordDisplaySection').style.display = 'none';
                    document.getElementById('generatePasswordBtn').style.display = 'none';
                    document.getElementById('saveAdminBtn').style.display = 'inline-flex';
                    document.getElementById('saveAdminBtn').innerHTML = '<i class="bi bi-check-lg"></i> Update Admin';
                } else {
                    showToast('Error loading admin data', 'danger');
                    closeModal('adminModal');
                }
            } catch (error) {
                showToast('Error loading admin data', 'danger');
            }
        }

        function editAdmin(id) {
            document.getElementById('adminModalTitle').textContent = 'Edit Admin';
            loadAdminData(id);
            openModal('adminModal');
        }

        function openAdminModal() {
            document.getElementById('adminModalTitle').textContent = 'Add New Admin';
            document.getElementById('adminForm').reset();
            document.getElementById('adminId').value = '';
            document.getElementById('generatedPassword').value = '';
            document.getElementById('displayGeneratedPassword').value = '';
            // Reset to initial state
            document.getElementById('passwordDisplaySection').style.display = 'none';
            document.getElementById('generatePasswordBtn').style.display = 'inline-flex';
            document.getElementById('saveAdminBtn').style.display = 'none';
            document.getElementById('saveAdminBtn').innerHTML = '<i class="bi bi-check-lg"></i> Save Admin';
            document.getElementById('copyFeedback').style.display = 'none';
            openModal('adminModal');
        }

        async function deleteAdmin(id) {
            ModalDialog.confirm('Are you sure you want to delete this admin? This action cannot be undone.', async () => {
                try {
                    const response = await fetch(`api/admin_management.php?id=${id}`, { method: 'DELETE' });
                    const result = await response.json();

                    if (result.success) {
                        showToast('Admin deleted successfully!');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast(result.message, 'danger');
                    }
                } catch (error) {
                    showToast('Error deleting admin', 'danger');
                }
            }, { title: 'Delete Admin', type: 'danger' });
        }

        // Notification dropdown functionality
        function toggleNotificationDropdown() {
            const dropdown = document.getElementById('notificationDropdown');
            dropdown.classList.toggle('active');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const notificationBell = document.querySelector('.notification-bell');
            if (!notificationBell.contains(event.target)) {
                document.getElementById('notificationDropdown').classList.remove('active');
            }
        });

        // Mark individual notification as read
        async function markAdminNotificationRead(notificationId) {
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
                    // Update badge count
                    const badge = document.querySelector('.notification-badge');
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
        async function markAllAdminNotificationsRead() {
            try {
                const response = await fetch('api/notification_actions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'mark_all_read' })
                });
                const result = await response.json();
                if (result.success) {
                    const dropdownContent = document.querySelector('.notification-dropdown-content');
                    dropdownContent.innerHTML = '<div class="no-notifications"><i class="bi bi-bell-slash"></i><p>No new notifications</p></div>';
                    const badge = document.querySelector('.notification-badge');
                    if (badge) badge.remove();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
    </script>
</body>
</html>
