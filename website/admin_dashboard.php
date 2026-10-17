<!DOCTYPE html>
<!-- Admin Dashboard - Firebase edition.
     Same UI as the old PHP/MySQL page; all data now comes from Firestore
     (requests / services / products / users / admins / notifications) and
     Firebase Auth replaces the PHP admin session. -->
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
    <script src="./js/config.js"></script>
    <style>
        #authGate {
            position: fixed; inset: 0; z-index: 100000;
            background: var(--secondary-color, #f4f6fb);
            display: flex; align-items: center; justify-content: center;
            flex-direction: column; gap: 1rem;
        }
        #authGate .gate-spinner {
            width: 46px; height: 46px; border-radius: 50%;
            border: 4px solid rgba(102,126,234,.25);
            border-top-color: #667eea;
            animation: gateSpin .8s linear infinite;
        }
        @keyframes gateSpin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <!-- Shown until Firebase Auth confirms this is a staff user -->
    <div id="authGate">
        <div class="gate-spinner"></div>
        <p style="color:var(--text-light,#888);font-weight:600;">Loading admin dashboard…</p>
    </div>

    <!-- First Login Password Reset Modal (shown when admins.firstLogin is true) -->
    <div class="first-login-overlay" id="firstLoginOverlay" style="display:none;">
        <div class="first-login-modal">
            <div class="first-login-header">
                <i class="bi bi-shield-lock"></i>
                <h2>Security First: Set New Password</h2>
                <p>Welcome, <span id="firstLoginName">Admin</span>! For security reasons, please set a new password for your admin account.</p>
            </div>
            <div class="first-login-body">
                <div class="first-login-error" id="firstLoginError" style="display:none;">
                    <i class="bi bi-exclamation-circle"></i> <span id="firstLoginErrorText"></span>
                </div>
                <div class="first-login-success" id="firstLoginSuccess" style="display:none;">
                    <i class="bi bi-check-circle-fill"></i>
                    <h3>Password Updated!</h3>
                    <p>Your password has been set successfully. Loading dashboard...</p>
                </div>
                <form id="firstLoginForm">
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
                                <li id="req-special">One special character (!@#$%^&amp;*)</li>
                            </ul>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" name="confirm_password" id="confirmPassword" class="form-input" required minlength="8" placeholder="Confirm your password">
                    </div>
                    <button type="submit" name="first_login_reset" class="btn btn-primary" style="width:100%;">
                        <i class="bi bi-lock"></i> Update Password &amp; Continue
                    </button>
                </form>
            </div>
        </div>
    </div>

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
                        <a href="#" class="nav-link" data-section="dashboard">
                            <i class="bi bi-speedometer2"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="nav-link" data-section="bookings">
                            <i class="bi bi-calendar-check"></i>
                            <span>Bookings</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="nav-link" data-section="services">
                            <i class="bi bi-briefcase"></i>
                            <span>Services</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="nav-link" data-section="products">
                            <i class="bi bi-box-seam"></i>
                            <span>Products</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="nav-link" data-section="customers">
                            <i class="bi bi-people"></i>
                            <span>Customers</span>
                        </a>
                    </li>
                    <li id="navEmployees">
                        <a href="#" class="nav-link" data-section="employees">
                            <i class="bi bi-person-badge"></i>
                            <span>Employees</span>
                        </a>
                    </li>
                    <li id="navAdmins" style="display:none;">
                        <a href="#" class="nav-link" data-section="admins">
                            <i class="bi bi-shield-lock"></i>
                            <span>Admin Management</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="nav-link" data-section="admin-profile">
                            <i class="bi bi-person-circle"></i>
                            <span>My Profile</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="sidebar-footer">
                <a href="#" class="logout-btn" id="logoutBtn">
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
                            <span class="notification-badge" id="notificationBadge" style="display:none;">0</span>
                        </button>
                        <div class="notification-dropdown" id="notificationDropdown">
                            <div class="notification-dropdown-header">
                                <h4>Notifications</h4>
                                <button class="mark-all-read-btn" onclick="markAllAdminNotificationsRead()">Mark all read</button>
                            </div>
                            <div class="notification-dropdown-content" id="notificationDropdownContent">
                                <div class="no-notifications">
                                    <i class="bi bi-bell-slash"></i>
                                    <p>No new notifications</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="user-info" onclick="switchSection('admin-profile')" style="cursor:pointer;">
                        <div class="user-avatar">
                            <i class="bi bi-person"></i>
                        </div>
                        <span id="headerUserName">Admin</span>
                    </div>
                </div>
            </header>

            <!-- Content Sections -->
            <div class="content-sections">

                <!-- ==================== DASHBOARD SECTION ==================== -->
                <div class="content-section" id="section-dashboard">
                    <!-- Stats Grid -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon bookings">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                            <div class="stat-info">
                                <h3 id="statTotalBookings">0</h3>
                                <p>Total Bookings</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon pending">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <div class="stat-info">
                                <h3 id="statPendingContact">0</h3>
                                <p>Pending Contact</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon services">
                                <i class="bi bi-briefcase"></i>
                            </div>
                            <div class="stat-info">
                                <h3 id="statTotalServices">0</h3>
                                <p>Services</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon products">
                                <i class="bi bi-box-seam"></i>
                            </div>
                            <div class="stat-info">
                                <h3 id="statTotalProducts">0</h3>
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
                            <div class="card-body" id="recentBookingsBody">
                                <div class="empty-state">
                                    <i class="bi bi-calendar-x"></i>
                                    <p>No bookings yet</p>
                                </div>
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
                                        <a href="#" class="action-btn" data-section="admins" id="quickActionAdmins" style="display:none;">
                                            <i class="bi bi-shield-lock"></i>
                                            <span>Admin Management</span>
                                        </a>
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
                <div class="content-section" id="section-bookings">
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
                                <h3 id="statTodayRequests">0</h3>
                                <p>Today's Requests</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon pending">
                                <i class="bi bi-telephone"></i>
                            </div>
                            <div class="stat-info">
                                <h3 id="statNeedContact">0</h3>
                                <p>Need Contact</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon services">
                                <i class="bi bi-cash"></i>
                            </div>
                            <div class="stat-info">
                                <h3 id="statNeedQuote">0</h3>
                                <p>Need Quote</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon revenue">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <div class="stat-info">
                                <h3 id="statUpcoming">0</h3>
                                <p>Upcoming</p>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="filters">
                        <form class="d-flex" id="bookingFilterForm" style="display:flex;gap:0.5rem;flex-wrap:wrap;align-items:center;" onsubmit="return false;">
                            <select class="search-input" name="status" id="statusFilter">
                                <option value="">All Status</option>
                                <option value="requested">Requested</option>
                                <option value="contacted">Contacted</option>
                                <option value="in_progress">In Progress</option>
                                <option value="quoted">Quoted</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="expired">Expired</option>
                            </select>
                            <input type="text" class="search-input" name="search" id="searchFilter" placeholder="Search bookings, customers, services..." style="flex:1;min-width:200px;">
                            <select class="search-input" name="service_id" id="serviceFilter">
                                <option value="">All Services</option>
                            </select>
                            <input type="date" class="search-input" name="date_from" id="dateFromFilter" placeholder="From">
                            <input type="date" class="search-input" name="date_to" id="dateToFilter" placeholder="To">
                            <button type="button" class="btn btn-secondary" onclick="clearFilters()">Clear</button>
                        </form>
                    </div>

                    <!-- Bookings Grid -->
                    <div id="bookingsContainer">
                        <div class="empty-state" id="bookingsEmpty" style="display:none;">
                            <i class="bi bi-calendar-x"></i>
                            <p>No bookings found</p>
                            <span class="subtitle">Bookings will appear here when customers submit requests</span>
                        </div>
                        <div class="bookings-grid" id="bookingsGrid"></div>
                        <div class="pagination" id="bookingsPagination" style="display:none;"></div>
                    </div>
                </div>

                <!-- ==================== SERVICES SECTION ==================== -->
                <div class="content-section" id="section-services">
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
                                <h3 id="statServicesTotal">0</h3>
                                <p>Total Services</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon revenue">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <div class="stat-info">
                                <h3 id="statServicesAvailable">0</h3>
                                <p>Available</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon pending">
                                <i class="bi bi-pause-circle"></i>
                            </div>
                            <div class="stat-info">
                                <h3 id="statServicesUnavailable">0</h3>
                                <p>Unavailable</p>
                            </div>
                        </div>
                    </div>

                    <!-- Services Grid -->
                    <div class="empty-state" id="servicesEmpty" style="display:none;">
                        <i class="bi bi-briefcase"></i>
                        <p>No services yet</p>
                        <span class="subtitle">Add your first service to get started</span>
                    </div>
                    <div class="cards-grid" id="servicesGrid"></div>
                </div>

                <!-- ==================== PRODUCTS SECTION ==================== -->
                <div class="content-section" id="section-products">
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
                                <h3 id="statProductsTotal">0</h3>
                                <p>Total Products</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon revenue">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <div class="stat-info">
                                <h3 id="statProductsAvailable">0</h3>
                                <p>Available</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon pending">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                            <div class="stat-info">
                                <h3 id="statProductsLowStock">0</h3>
                                <p>Low Stock</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon services">
                                <i class="bi bi-tags"></i>
                            </div>
                            <div class="stat-info">
                                <h3 id="statProductsCategories">0</h3>
                                <p>Categories</p>
                            </div>
                        </div>
                    </div>

                    <!-- Products Grid -->
                    <div class="empty-state" id="productsEmpty" style="display:none;">
                        <i class="bi bi-box-seam"></i>
                        <p>No products yet</p>
                        <span class="subtitle">Add your first product to get started</span>
                    </div>
                    <div class="cards-grid" id="productsGrid"></div>
                </div>

                <!-- ==================== CUSTOMERS SECTION ==================== -->
                <div class="content-section" id="section-customers">
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
                                <h3 id="statCustomersTotal">0</h3>
                                <p>Total Customers</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon revenue">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                            <div class="stat-info">
                                <h3 id="statCustomersNew">0</h3>
                                <p>New (30 days)</p>
                            </div>
                        </div>
                    </div>

                    <!-- Search -->
                    <div class="filters">
                        <input type="text" class="search-input" id="customerSearch" placeholder="Search customers by name, email, or phone...">
                    </div>

                    <!-- Customers Table -->
                    <div class="empty-state" id="customersEmpty" style="display:none;">
                        <i class="bi bi-people"></i>
                        <p>No customers yet</p>
                        <span class="subtitle">Customers will appear here when they register or make bookings</span>
                    </div>
                    <div class="table-container" id="customersTable" style="display:none;">
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
                            <tbody id="customersBody"></tbody>
                        </table>
                    </div>
                </div>

                <!-- ==================== EMPLOYEES SECTION ==================== -->
                <div class="content-section" id="section-employees">
                    <div class="section-header">
                        <h2>Employees</h2>
                        <button class="btn btn-primary" onclick="openEmployeeModal()">
                            <i class="bi bi-plus-lg"></i> Add Employee
                        </button>
                    </div>

                    <!-- Employee Stats -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon bookings">
                                <i class="bi bi-person-badge"></i>
                            </div>
                            <div class="stat-info">
                                <h3 id="statEmployeesTotal">0</h3>
                                <p>Total Employees</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon revenue">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <div class="stat-info">
                                <h3 id="statEmployeesAvailable">0</h3>
                                <p>Available</p>
                            </div>
                        </div>
                    </div>

                    <!-- Employees Table -->
                    <div class="empty-state" id="employeesEmpty" style="display:none;">
                        <i class="bi bi-person-badge"></i>
                        <p>No employees yet</p>
                        <span class="subtitle">Add employees so they can be assigned to bookings</span>
                    </div>
                    <div class="table-container" id="employeesTable" style="display:none;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="employeesBody"></tbody>
                        </table>
                    </div>
                </div>

                <!-- ==================== ADMINS SECTION ==================== -->
                <div class="content-section" id="section-admins" style="display:none;">
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
                                <h3 id="statAdminsTotal">0</h3>
                                <p>Total Admins</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon revenue">
                                <i class="bi bi-person-check"></i>
                            </div>
                            <div class="stat-info">
                                <h3 id="statAdminsMain">0</h3>
                                <p>Main Admins</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon pending">
                                <i class="bi bi-person"></i>
                            </div>
                            <div class="stat-info">
                                <h3 id="statAdminsSub">0</h3>
                                <p>Sub-Admins</p>
                            </div>
                        </div>
                    </div>

                    <!-- Admins Table -->
                    <div class="empty-state" id="adminsEmpty" style="display:none;">
                        <i class="bi bi-shield-lock"></i>
                        <p>No admins found</p>
                    </div>
                    <div class="table-container" id="adminsTable" style="display:none;">
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
                            <tbody id="adminsBody"></tbody>
                        </table>
                    </div>
                </div>

                <!-- ==================== PROFILE SECTION ==================== -->
                <div class="content-section" id="section-admin-profile">
                    <div class="section-header">
                        <h2>My Profile</h2>
                        <button class="btn btn-primary" id="editProfileBtn" onclick="setProfileEditMode(true)">
                            <i class="bi bi-pencil"></i> Edit Profile
                        </button>
                    </div>

                    <div id="profileAlert" style="display:none;margin-bottom:1.5rem;"></div>

                    <div class="content-row">
                        <!-- Profile Info Card -->
                        <div class="card" style="flex:1;">
                            <div class="card-header">
                                <h3><i class="bi bi-person-circle"></i> Profile Information</h3>
                            </div>
                            <div class="card-body" id="profileCardBody"></div>
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
                                        <p>Last changed: <span id="profileLastChanged">Unknown</span></p>
                                    </div>
                                    <button class="btn btn-secondary" onclick="openModal('changePasswordModal')">
                                        <i class="bi bi-key"></i> Change Password
                                    </button>
                                </div>
                                <div class="security-item" style="margin-top:1.5rem; padding-top:1.5rem; border-top:1px solid var(--border-color);">
                                    <div class="security-info">
                                        <h4>Account Status</h4>
                                        <p id="profileAccountStatus"><span style="color:var(--success);">Active</span></p>
                                    </div>
                                </div>
                                <div class="security-item" style="margin-top:1.5rem; padding-top:1.5rem; border-top:1px solid var(--border-color);">
                                    <div class="security-info">
                                        <h4>Session</h4>
                                        <p>Manage your active session</p>
                                    </div>
                                    <a href="#" class="btn btn-danger" id="profileLogoutBtn">
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

    <!-- Assign Employee Modal -->
    <div class="modal" id="assignModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="bi bi-person-badge"></i> Assign Employee</h2>
                <button class="modal-close" onclick="closeModal('assignModal')">&times;</button>
            </div>
            <form id="assignForm">
                <input type="hidden" id="assignBookingId">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Available Employee <span class="text-danger">*</span></label>
                        <select class="form-input" id="assignEmployeeId" required></select>
                        <small style="color:var(--text-light);">The employee will see this job on their mobile app and can start/complete it there.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('assignModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Assign</button>
                </div>
            </form>
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
                        <small style="color:var(--text-light);">Stored in the service record — keep under ~700 KB.</small>
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
                        <small style="color:var(--text-light);">Stored in the product record — keep under ~700 KB.</small>
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

    <!-- Employee Modal (Add only) -->
    <div class="modal" id="employeeModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="employeeModalTitle"><i class="bi bi-person-badge"></i> Add Employee</h2>
                <button class="modal-close" onclick="closeModal('employeeModal')">&times;</button>
            </div>
            <form id="employeeForm">
                <input type="hidden" id="employeeGeneratedPassword">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-input" id="employeeName" required>
                    </div>
                    <div class="form-group">
                        <label>Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-input" id="employeeEmail" required>
                    </div>
                    <div class="form-group">
                        <label>Phone <span class="text-danger">*</span></label>
                        <input type="tel" class="form-input" id="employeePhone" required>
                    </div>
                    <div id="employeePasswordDisplaySection" style="display:none; margin-top:1.5rem; padding:1.5rem; background:var(--success-light); border:2px solid var(--success); border-radius:var(--border-radius);">
                        <div style="text-align:center; margin-bottom:1rem;">
                            <i class="bi bi-check-circle-fill" style="font-size:2rem; color:var(--success);"></i>
                            <h3 style="margin:0.5rem 0; color:var(--success);">Password Generated!</h3>
                            <p style="margin:0; color:var(--text-light); font-size:0.9rem;">Copy this password and share it with the new employee</p>
                        </div>
                        <div style="display:flex; gap:0.5rem; margin-bottom:1rem;">
                            <input type="text" id="employeeDisplayPassword" class="form-input" style="text-align:center; font-family:monospace; font-size:1.1rem; font-weight:600; letter-spacing:1px;" readonly>
                            <button type="button" class="btn btn-primary" onclick="copyEmployeePassword()" style="white-space:nowrap;">
                                <i class="bi bi-clipboard"></i> Copy
                            </button>
                        </div>
                        <div id="employeeCopyFeedback" style="text-align:center; color:var(--success); font-weight:500; display:none;">
                            <i class="bi bi-check"></i> Password copied to clipboard!
                        </div>
                    </div>
                </div>
                <div class="modal-footer" id="employeeModalFooter">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('employeeModal')">Cancel</button>
                    <button type="button" id="generateEmployeePasswordBtn" class="btn btn-warning" onclick="generateEmployeePassword()">
                        <i class="bi bi-key"></i> Generate Password
                    </button>
                    <button type="submit" id="saveEmployeeBtn" class="btn btn-primary" style="display:none;">
                        <i class="bi bi-check-lg"></i> Save Employee
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
                        <input type="password" class="form-input" id="newPasswordChange" minlength="8" required>
                        <small style="color:var(--text-light);">Minimum 8 characters</small>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-input" id="confirmPasswordChange" required>
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
    <script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
    <script type="module">
        import { whenAuthReady, pmaSignOut, pmaChangePassword, authErrorMessage } from './js/firebase/auth.js';
        import {
            watchAllRequests, updateRequest,
            watchServices, createService, updateService, setServiceAvailability, deleteService as deleteServiceDoc,
            watchProducts, createProduct, updateProduct, setProductAvailability, deleteProduct as deleteProductDoc,
            watchCustomers, watchEmployees, setEmployeeAvailability, updateUserProfile,
            getAdminDoc, createAdminDoc, updateAdminDoc, deleteAdminDoc, watchAdmins,
            watchStaffNotifications, addNotification, markNotificationRead, markAllNotificationsRead
        } from './js/firebase/data.js';
        import { downloadQuotePdf } from './js/firebase/quotePdf.js';
        import { auth, db } from './js/firebase/app.js';
        import { initializeApp } from 'https://www.gstatic.com/firebasejs/13.0.0/firebase-app.js';
        import { getAuth, createUserWithEmailAndPassword, updatePassword, signOut as fbSignOut } from 'https://www.gstatic.com/firebasejs/13.0.0/firebase-auth.js';
        import { collection, getDocs, doc, setDoc } from 'https://www.gstatic.com/firebasejs/13.0.0/firebase-firestore.js';

        // ==================== STATE ====================
        let currentUser = null;
        let currentAdmin = null;
        let isMainAdmin = false;
        let bookings = [];      // mapped to legacy field names (Status = legacy key)
        let services = [];
        let products = [];
        let customers = [];
        let employees = [];
        let admins = [];
        let notifications = [];
        let profileEditMode = new URLSearchParams(location.search).get('edit_profile') === '1';
        let currentPage = 1;
        const PAGE_SIZE = 10;
        const expiryHandled = new Set();
        let emailJsReady = false;

        // Firestore status -> the lowercase key the old PHP page used for CSS/labels
        const FS_TO_LEGACY = {
            'Pending': 'requested',
            'Contacted': 'contacted',
            'Quoted': 'quoted',
            'Confirmed': 'confirmed',
            'Approved': 'in_progress',
            'In Progress': 'in_progress',
            'Completed': 'completed',
            'Rejected': 'cancelled',
            'Cancelled': 'cancelled',
            'Expired': 'expired'
        };
        const LEGACY_TO_FS = {
            'requested': 'Pending',
            'contacted': 'Contacted',
            'in_progress': 'In Progress',
            'quoted': 'Quoted',
            'confirmed': 'Confirmed',
            'completed': 'Completed',
            'cancelled': 'Cancelled',
            'expired': 'Expired'
        };
        const statusLabels = {
            'requested': 'Requested',
            'contacted': 'Contacted',
            'in_progress': 'In Progress',
            'quoted': 'Quoted',
            'confirmed': 'Confirmed',
            'completed': 'Completed',
            'cancelled': 'Cancelled',
            'expired': 'Expired'
        };

        // ==================== HELPERS ====================
        const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
        const fmtMoney = (n) => 'R ' + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const fmtDate = (d) => d ? new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '';
        const fmtDateLong = (d) => d ? new Date(d).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) : 'N/A';
        const fmtDateTime = (d) => d ? new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }) : '';
        const fmtTime = (t) => {
            if (!t) return '';
            try { return new Date('2000-01-01T' + t).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }); }
            catch (e) { return t; }
        };

        function fileToDataUrl(file) {
            return new Promise((resolve, reject) => {
                if (!file || !file.size) return resolve('');
                if (file.size > 800 * 1024) return reject(new Error('Image is too large — please use a file under 800 KB.'));
                const reader = new FileReader();
                reader.onload = () => resolve(reader.result);
                reader.onerror = () => reject(new Error('Could not read image file'));
                reader.readAsDataURL(file);
            });
        }

        function initEmailJs() {
            if (emailJsReady || !window.emailjs || !window.CONFIG?.EMAILJS?.PUBLIC_KEY) return;
            try { window.emailjs.init(window.CONFIG.EMAILJS.PUBLIC_KEY); emailJsReady = true; } catch (e) {}
        }

        async function sendCustomerEmail(toEmail, toName, subject, message) {
            initEmailJs();
            if (!emailJsReady) return;
            try {
                await window.emailjs.send(
                    window.CONFIG.EMAILJS.SERVICE_ID,
                    window.CONFIG.EMAILJS.CONTACT_TEMPLATE_ID,
                    { to_email: toEmail, to_name: toName, subject, message, reply_to: window.CONFIG.SUPPORT_EMAIL }
                );
            } catch (e) {
                console.warn('EmailJS send failed (notification still created):', e);
            }
        }

        // ==================== SECTION NAVIGATION ====================
        const sectionTitles = {
            dashboard: 'Dashboard',
            bookings: 'Manage Bookings',
            services: 'Manage Services',
            products: 'Manage Products',
            customers: 'Customer Management',
            employees: 'Employees',
            admins: 'Admin Management',
            'admin-profile': 'My Profile'
        };

        function switchSection(section) {
            document.querySelectorAll('.content-section').forEach(s => {
                s.classList.remove('active');
                if (s.id !== 'section-admins') s.style.display = '';
            });
            const target = document.getElementById('section-' + section);
            if (target) {
                target.classList.add('active');
                if (target.id === 'section-admins' && isMainAdmin) target.style.display = '';
            }
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.toggle('active', link.dataset.section === section);
            });
            document.getElementById('sectionTitle').textContent = sectionTitles[section] || 'Dashboard';
            const url = new URL(window.location);
            url.searchParams.set('section', section);
            url.searchParams.delete('page');
            url.searchParams.delete('status');
            url.searchParams.delete('search');
            history.pushState({}, '', url);
        }

        // ==================== MODAL / TOAST (unchanged helpers) ====================
        function openModal(id) { document.getElementById(id).classList.add('active'); }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }

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
            toast.innerHTML = `${icons[type] || icons.success}<span>${esc(message)}</span>`;
            document.getElementById('toastContainer').appendChild(toast);
            setTimeout(() => {
                toast.style.animation = 'toastSlideOut 0.3s ease forwards';
                setTimeout(() => toast.remove(), 300);
            }, 4000);
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
            if (checkDate.getTime() === today.getTime()) return 'Today';
            if (checkDate.getTime() === yesterday.getTime()) return 'Yesterday';
            if (diffDays === 2) return 'Day before yesterday';
            if (diffDays <= 7) return date.toLocaleDateString('en-US', { weekday: 'long' });
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }

        // ==================== RENDER: DASHBOARD ====================
        function renderDashboard() {
            document.getElementById('statTotalBookings').textContent = bookings.length;
            document.getElementById('statPendingContact').textContent = bookings.filter(b => b.Status === 'requested').length;
            document.getElementById('statTotalServices').textContent = services.length;
            document.getElementById('statTotalProducts').textContent = products.length;

            const body = document.getElementById('recentBookingsBody');
            if (!bookings.length) {
                body.innerHTML = `<div class="empty-state"><i class="bi bi-calendar-x"></i><p>No bookings yet</p></div>`;
                return;
            }
            body.innerHTML = bookings.slice(0, 5).map(b => `
                <div class="booking-item">
                    <div class="booking-avatar">${esc((b.Customer_Name || '?').charAt(0).toUpperCase())}</div>
                    <div class="booking-info">
                        <h4>${esc(b.Customer_Name)}</h4>
                        <p>${esc(b.Service_Name)} - ${getRelativeDate(b.created_at)}</p>
                    </div>
                    <div class="booking-meta">
                        <span class="booking-status ${b.Status}">${statusLabels[b.Status] || b.Status}</span>
                    </div>
                </div>
            `).join('');
        }

        // ==================== RENDER: BOOKINGS ====================
        function getFilteredBookings() {
            const status = document.getElementById('statusFilter').value;
            const search = (document.getElementById('searchFilter').value || '').toLowerCase();
            const serviceId = document.getElementById('serviceFilter').value;
            const dateFrom = document.getElementById('dateFromFilter').value;
            const dateTo = document.getElementById('dateToFilter').value;

            return bookings.filter(b => {
                if (status && b.Status !== status) return false;
                if (serviceId && b.Service_ID !== serviceId) return false;
                if (dateFrom && b.Date < dateFrom) return false;
                if (dateTo && b.Date > dateTo) return false;
                if (search) {
                    const hay = `${b.Customer_Name} ${b.Customer_Email} ${b.Service_Name} ${b.Address} ${b.refNumber}`.toLowerCase();
                    if (!hay.includes(search)) return false;
                }
                return true;
            });
        }

        function renderBookingStats() {
            const today = new Date().toISOString().slice(0, 10);
            document.getElementById('statTodayRequests').textContent =
                bookings.filter(b => (b.created_at || '').slice(0, 10) === today).length;
            document.getElementById('statNeedContact').textContent =
                bookings.filter(b => b.Status === 'requested').length;
            document.getElementById('statNeedQuote').textContent =
                bookings.filter(b => b.Status === 'contacted').length;
            document.getElementById('statUpcoming').textContent =
                bookings.filter(b => ['confirmed', 'in_progress'].includes(b.Status) && b.Date >= today).length;
        }

        function bookingActionButtons(b) {
            const id = `'${b.ID}'`;
            const amount = b.Quoted_Amount ?? 'null';
            let html = `<button class="btn btn-sm btn-secondary" onclick="event.stopPropagation(); viewBooking(${id})"><i class="bi bi-eye"></i> View</button>`;
            if (b.Status === 'requested') {
                html += `<button class="btn btn-sm btn-primary" onclick="event.stopPropagation(); quickAction(${id}, 'contact')"><i class="bi bi-telephone"></i> Contact</button>`;
                html += `<button class="btn btn-sm btn-info" onclick="event.stopPropagation(); openAssignModal(${id})"><i class="bi bi-person-badge"></i> Assign</button>`;
            } else if (b.Status === 'contacted') {
                html += `<button class="btn btn-sm btn-success" onclick="event.stopPropagation(); openQuoteModal(${id}, ${amount})"><i class="bi bi-cash"></i> Quote</button>`;
                html += `<button class="btn btn-sm btn-info" onclick="event.stopPropagation(); openAssignModal(${id})"><i class="bi bi-person-badge"></i> Assign</button>`;
            } else if (b.Status === 'in_progress') {
                if (!b.Quoted_Amount) {
                    html += `<button class="btn btn-sm btn-success" onclick="event.stopPropagation(); openQuoteModal(${id}, ${amount})"><i class="bi bi-cash"></i> Quote</button>`;
                }
                html += `<button class="btn btn-sm btn-secondary" onclick="event.stopPropagation(); updateStatus(${id}, 'completed')"><i class="bi bi-check-circle"></i> Complete</button>`;
            } else if (b.Status === 'quoted') {
                html += `<button class="btn btn-sm btn-info" onclick="event.stopPropagation(); viewQuoteDetailsById(${id})"><i class="bi bi-eye"></i> View Quote</button>`;
                html += `<button class="btn btn-sm btn-success" onclick="event.stopPropagation(); openQuoteModal(${id}, ${amount})"><i class="bi bi-pencil"></i> Update Quote</button>`;
            } else if (b.Status === 'confirmed') {
                html += `<button class="btn btn-sm btn-info" onclick="event.stopPropagation(); openAssignModal(${id})"><i class="bi bi-person-badge"></i> Assign</button>`;
                html += `<button class="btn btn-sm btn-secondary" onclick="event.stopPropagation(); updateStatus(${id}, 'completed')"><i class="bi bi-check-circle"></i> Complete</button>`;
            }
            return html;
        }

        function renderBookings() {
            renderBookingStats();
            const filtered = getFilteredBookings();
            const grid = document.getElementById('bookingsGrid');
            const empty = document.getElementById('bookingsEmpty');
            const pagination = document.getElementById('bookingsPagination');

            if (!filtered.length) {
                grid.innerHTML = '';
                empty.style.display = '';
                pagination.style.display = 'none';
                return;
            }
            empty.style.display = 'none';

            const totalPages = Math.ceil(filtered.length / PAGE_SIZE);
            if (currentPage > totalPages) currentPage = totalPages;
            const pageItems = filtered.slice((currentPage - 1) * PAGE_SIZE, currentPage * PAGE_SIZE);

            grid.innerHTML = pageItems.map(b => `
                <div class="booking-card" onclick="viewBooking('${b.ID}')">
                    <div class="card-header">
                        <span class="service-name">${esc(b.Service_Name)}</span>
                        <span class="booking-status ${b.Status}">${statusLabels[b.Status] || b.Status}</span>
                    </div>
                    <div class="card-body">
                        <div class="customer-row">
                            <div class="customer-avatar-small">${esc((b.Customer_Name || '?').charAt(0).toUpperCase())}</div>
                            <div class="customer-details">
                                <strong>${esc(b.Customer_Name)}</strong>
                                <span class="customer-contact-small">${esc(b.Customer_Email)}</span>
                            </div>
                        </div>
                        <div class="details-grid">
                            <div class="detail-item"><i class="bi bi-calendar-check"></i> ${fmtDate(b.Date)}</div>
                            <div class="detail-item"><i class="bi bi-clock"></i> ${fmtTime(b.Time)}</div>
                            <div class="detail-item"><i class="bi bi-geo-alt"></i> ${esc(b.Address && b.Address.length > 25 ? b.Address.slice(0, 25) + '...' : b.Address)}</div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.5rem;">
                            ${b.Quoted_Amount
                                ? `<div class="quote-info-compact"><span class="quote-amount">${fmtMoney(b.Quoted_Amount)}</span></div>`
                                : `<div class="quote-info-compact"><span class="quote-pending">Quote pending</span></div>`}
                            <div class="booking-created-date" style="font-size: 0.75rem; color: var(--text-light);">
                                <i class="bi bi-calendar-plus"></i> ${getRelativeDate(b.created_at)}
                            </div>
                        </div>
                        ${b.employeeName ? `<div style="font-size:0.8rem;color:var(--info);margin-top:0.25rem;"><i class="bi bi-person-badge"></i> ${esc(b.employeeName)}</div>` : ''}
                        <div class="card-actions">${bookingActionButtons(b)}</div>
                    </div>
                </div>
            `).join('');

            if (totalPages > 1) {
                pagination.style.display = '';
                let ph = currentPage > 1
                    ? `<a href="#" onclick="goPage(${currentPage - 1}); return false;">&laquo; Prev</a>`
                    : `<span class="disabled">&laquo; Prev</span>`;
                for (let i = 1; i <= totalPages; i++) {
                    ph += i === currentPage
                        ? `<span class="current">${i}</span>`
                        : `<a href="#" onclick="goPage(${i}); return false;">${i}</a>`;
                }
                ph += currentPage < totalPages
                    ? `<a href="#" onclick="goPage(${currentPage + 1}); return false;">Next &raquo;</a>`
                    : `<span class="disabled">Next &raquo;</span>`;
                pagination.innerHTML = ph;
            } else {
                pagination.style.display = 'none';
            }
        }

        function goPage(n) { currentPage = n; renderBookings(); }

        function clearFilters() {
            document.getElementById('statusFilter').value = '';
            document.getElementById('searchFilter').value = '';
            document.getElementById('serviceFilter').value = '';
            document.getElementById('dateFromFilter').value = '';
            document.getElementById('dateToFilter').value = '';
            currentPage = 1;
            renderBookings();
        }

        // ==================== BOOKING ACTIONS ====================
        async function contactCustomer(bookingId, customerEmail) {
            ModalDialog.confirm(`Send contact notification to customer (${customerEmail})?`, async () => {
                try {
                    const b = bookings.find(x => x.ID === bookingId);
                    await updateRequest(bookingId, { status: 'Contacted' });
                    if (b) {
                        await addNotification({
                            recipientId: b.Customer_ID,
                            title: 'Booking Update',
                            message: `We have contacted you regarding your ${b.Service_Name} booking ${b.refNumber || ''}.`,
                            requestId: bookingId
                        });
                        await sendCustomerEmail(b.Customer_Email, b.Customer_Name, `Your booking ${b.refNumber || ''}`,
                            `Hi ${b.Customer_Name}, we have contacted you regarding your ${b.Service_Name} booking on ${fmtDate(b.Date)}.`);
                    }
                    showToast('Contact notification sent to customer!');
                } catch (error) {
                    console.error(error);
                    showToast('Error contacting customer', 'danger');
                }
            }, { title: 'Send Contact Notification', type: 'info' });
        }

        function downloadQuotePDF(bookingId) {
            const b = bookings.find(x => x.ID === bookingId);
            if (b) downloadQuotePdf(b);
        }

        function viewQuoteDetails(id, breakdown, validityDays, notes, amount) {
            document.getElementById('viewQuoteBreakdown').textContent = breakdown || 'No breakdown provided';
            document.getElementById('viewQuoteValidity').textContent = validityDays ? validityDays + ' days' : '7 days';
            document.getElementById('viewQuoteNotes').textContent = notes || 'No notes';
            document.getElementById('viewQuoteAmount').textContent = fmtMoney(amount);
            openModal('viewQuoteModal');
        }

        function viewQuoteDetailsById(id) {
            const b = bookings.find(x => x.ID === id);
            if (!b) return;
            viewQuoteDetails(id, b.quote_breakdown, b.quote_validity_days, b.quote_notes, b.Quoted_Amount);
        }

        async function quickAction(id, action) {
            if (action === 'contact') {
                const b = bookings.find(x => x.ID === id);
                if (b) contactCustomer(id, b.Customer_Email);
                return;
            }
            if (action === 'in_progress') {
                await setRequestStatus(id, 'In Progress', 'Your booking is now in progress.');
            }
        }

        async function setRequestStatus(id, fsStatus, customerMessage) {
            try {
                await updateRequest(id, { status: fsStatus });
                const b = bookings.find(x => x.ID === id);
                if (b && customerMessage) {
                    await addNotification({
                        recipientId: b.Customer_ID,
                        title: 'Booking Update',
                        message: `${customerMessage} (${b.refNumber || b.Service_Name})`,
                        requestId: id
                    });
                }
                showToast(`Booking updated to ${fsStatus}`);
            } catch (error) {
                console.error(error);
                showToast('Error updating status', 'danger');
            }
        }

        async function updateStatus(id, status) {
            const fsStatus = LEGACY_TO_FS[status] || status;
            await setRequestStatus(id, fsStatus, fsStatus === 'Completed' ? 'Your booking has been completed. Thank you!' : `Your booking status is now ${fsStatus}.`);
        }

        function openQuoteModal(id, currentAmount) {
            document.getElementById('quoteBookingId').value = id;
            document.getElementById('quotedAmount').value = currentAmount || '';
            openModal('quoteModal');
        }

        function openAssignModal(id) {
            const available = employees.filter(e => e.Is_Available);
            const sel = document.getElementById('assignEmployeeId');
            if (!available.length) {
                showToast('No available employees — add or activate one on the Employees tab first.', 'warning');
                return;
            }
            sel.innerHTML = available.map(e => `<option value="${e.ID}">${esc(e.Full_Name)} (${esc(e.Email)})</option>`).join('');
            document.getElementById('assignBookingId').value = id;
            openModal('assignModal');
        }

        function exportBookings() {
            const date = new Date().toISOString().split('T')[0];
            const list = getFilteredBookings();
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
                            <th>Ref</th>
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
            list.forEach(b => {
                html += `
                    <tr>
                        <td>${esc(b.refNumber || b.ID)}</td>
                        <td>${esc(b.Service_Name || '')}</td>
                        <td>${esc(b.Customer_Name || '')}</td>
                        <td>${esc(b.Customer_Email || '')}</td>
                        <td>${esc(b.Customer_Phone || '')}</td>
                        <td>${esc(b.Date || '')}</td>
                        <td>${esc(b.Time || '')}</td>
                        <td><span class="status status-${b.Status}">${statusLabels[b.Status] || b.Status}</span></td>
                        <td>${esc(b.Address || '')}</td>
                        <td>${esc(b.Special_Instructions || '')}</td>
                        <td>${b.Quoted_Amount ? fmtMoney(b.Quoted_Amount) : ''}</td>
                    </tr>
                `;
            });
            html += `</table></body></html>`;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(html);
            printWindow.document.close();
            printWindow.print();
        }

        // ==================== BOOKING DETAILS MODAL ====================
        function viewBooking(id) {
            const booking = bookings.find(b => b.ID == id);
            if (!booking) return;

            const status = booking.Status;
            const canQuote = ['requested', 'contacted', 'in_progress'].includes(status);
            const canContact = status === 'requested';
            const canAssign = ['requested', 'contacted', 'confirmed'].includes(status);
            const hasQuote = booking.Quoted_Amount && booking.Quoted_Amount > 0;

            const badgeClass = status === 'requested' ? 'badge-warning' : status === 'confirmed' ? 'badge-success' : status === 'cancelled' ? 'badge-danger' : 'badge-info';

            const detailsHtml = `
                <div class="booking-details-grid">
                    <div style="background: var(--secondary-color); padding: 1.5rem; border-radius: var(--border-radius);">
                        <h4 style="margin-bottom: 1rem; color: var(--text-light); display: flex; align-items: center; gap: 0.5rem;">
                            <i class="bi bi-briefcase"></i> Service Information
                        </h4>
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <i class="bi bi-tag" style="color: var(--primary-color); width: 20px;"></i>
                                <div>
                                    <span style="color: var(--text-light); font-size: 0.85rem;">Service</span>
                                    <p style="margin: 0; font-weight: 500;">${esc(booking.Service_Name)}</p>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <i class="bi bi-clock" style="color: var(--primary-color); width: 20px;"></i>
                                <div>
                                    <span style="color: var(--text-light); font-size: 0.85rem;">Duration</span>
                                    <p style="margin: 0; font-weight: 500;">${booking.Service_Duration || 'N/A'} minutes</p>
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
                                    <p style="margin: 0; font-weight: 500;">${fmtTime(booking.Time)}</p>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <i class="bi bi-info-circle" style="color: var(--primary-color); width: 20px;"></i>
                                <div>
                                    <span style="color: var(--text-light); font-size: 0.85rem;">Status</span>
                                    <p style="margin: 0;">
                                        <span class="badge ${badgeClass}" style="text-transform: capitalize;">${statusLabels[status] || booking._status}</span>
                                    </p>
                                </div>
                            </div>
                            ${hasQuote ? `
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <i class="bi bi-cash" style="color: var(--success); width: 20px;"></i>
                                <div>
                                    <span style="color: var(--text-light); font-size: 0.85rem;">Quote</span>
                                    <p style="margin: 0; font-weight: 500; color: var(--success);">${fmtMoney(booking.Quoted_Amount)}</p>
                                </div>
                            </div>` : ''}
                            ${booking.employeeName ? `
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <i class="bi bi-person-badge" style="color: var(--primary-color); width: 20px;"></i>
                                <div>
                                    <span style="color: var(--text-light); font-size: 0.85rem;">Assigned To</span>
                                    <p style="margin: 0; font-weight: 500;">${esc(booking.employeeName)}</p>
                                </div>
                            </div>` : ''}
                        </div>
                    </div>

                    <div style="background: var(--secondary-color); padding: 1.5rem; border-radius: var(--border-radius);">
                        <h4 style="margin-bottom: 1rem; color: var(--text-light); display: flex; align-items: center; gap: 0.5rem;">
                            <i class="bi bi-person"></i> Customer Information
                        </h4>
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <i class="bi bi-person-circle" style="color: var(--primary-color); width: 20px;"></i>
                                <div>
                                    <span style="color: var(--text-light); font-size: 0.85rem;">Name</span>
                                    <p style="margin: 0; font-weight: 500;">${esc(booking.Customer_Name)}</p>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <i class="bi bi-envelope" style="color: var(--primary-color); width: 20px;"></i>
                                <div>
                                    <span style="color: var(--text-light); font-size: 0.85rem;">Email</span>
                                    <p style="margin: 0; font-weight: 500;">${esc(booking.Customer_Email)}</p>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <i class="bi bi-telephone" style="color: var(--primary-color); width: 20px;"></i>
                                <div>
                                    <span style="color: var(--text-light); font-size: 0.85rem;">Phone</span>
                                    <p style="margin: 0; font-weight: 500;">${esc(booking.Customer_Phone || 'N/A')}</p>
                                </div>
                            </div>
                            <div style="display: flex; align-items: start; gap: 0.75rem;">
                                <i class="bi bi-geo-alt" style="color: var(--primary-color); width: 20px; margin-top: 2px;"></i>
                                <div>
                                    <span style="color: var(--text-light); font-size: 0.85rem;">Address</span>
                                    <p style="margin: 0; font-weight: 500;">${esc(booking.Address)}</p>
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
                    <p style="margin: 0; padding: 1rem; background: white; border-radius: 8px; border-left: 4px solid var(--primary-color);">${esc(booking.Special_Instructions)}</p>
                </div>` : ''}

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
                                <p style="margin: 0; font-weight: 500;">${esc(booking.Property_Type)}</p>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <i class="bi bi-aspect-ratio" style="color: var(--primary-color); width: 20px;"></i>
                            <div>
                                <span style="color: var(--text-light); font-size: 0.85rem;">Size</span>
                                <p style="margin: 0; font-weight: 500;">${esc(booking.Property_Size || 'N/A')}</p>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <i class="bi bi-arrow-repeat" style="color: var(--primary-color); width: 20px;"></i>
                            <div>
                                <span style="color: var(--text-light); font-size: 0.85rem;">Frequency</span>
                                <p style="margin: 0; font-weight: 500;">${esc(booking.Cleaning_Frequency || 'N/A')}</p>
                            </div>
                        </div>
                    </div>
                </div>` : ''}
            `;

            let actionsHtml = '<div style="display:flex;gap:0.5rem;flex-wrap:wrap;">';
            if (canContact) {
                actionsHtml += `<button type="button" class="btn btn-primary btn-sm" onclick="contactCustomer('${booking.ID}', '${booking.Customer_Email}')"><i class="bi bi-telephone"></i> Contact Customer</button>`;
            }
            if (canQuote) {
                actionsHtml += `<button type="button" class="btn btn-success btn-sm" onclick="closeModal('bookingModal'); openQuoteModal('${booking.ID}', ${booking.Quoted_Amount || 'null'})"><i class="bi bi-cash"></i> ${hasQuote ? 'Update Quote' : 'Provide Quote'}</button>`;
            }
            if (canAssign) {
                actionsHtml += `<button type="button" class="btn btn-info btn-sm" onclick="closeModal('bookingModal'); openAssignModal('${booking.ID}')"><i class="bi bi-person-badge"></i> Assign Employee</button>`;
            }
            if (hasQuote) {
                actionsHtml += `<button type="button" class="btn btn-info btn-sm" onclick="downloadQuotePDF('${booking.ID}')"><i class="bi bi-file-pdf"></i> Download PDF</button>`;
            }
            actionsHtml += `<button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('bookingModal')">Close</button></div>`;

            document.getElementById('bookingDetails').innerHTML = detailsHtml;
            document.getElementById('bookingActions').innerHTML = actionsHtml;
            openModal('bookingModal');
        }

        // ==================== EXPIRY SWEEP (replaces expireQuotes/expirePastBookings) ====================
        async function runExpirySweep() {
            const now = new Date();
            const todayStr = now.toISOString().slice(0, 10);
            for (const b of bookings) {
                if (expiryHandled.has(b.ID)) continue;
                // Quote expiry: Quoted + quoteSentDate + validity days passed
                if (b._status === 'Quoted' && b.quoteSentDate) {
                    const sent = new Date(b.quoteSentDate);
                    const valid = Number(b.quote_validity_days) || 7;
                    const expiry = new Date(sent.getTime() + valid * 86400000);
                    if (expiry < now) {
                        expiryHandled.add(b.ID);
                        try {
                            await updateRequest(b.ID, { status: 'Expired' });
                            await addNotification({
                                recipientId: b.Customer_ID,
                                title: 'Quote Expired',
                                message: `Your quotation for ${b.Service_Name} (${b.refNumber || ''}) has expired.`,
                                requestId: b.ID
                            });
                        } catch (e) { console.warn('Quote expiry failed', e); }
                    }
                }
                // Past-booking expiry: requested/contacted bookings whose date has passed
                if (['Pending', 'Contacted'].includes(b._status) && b.Date && b.Date < todayStr) {
                    expiryHandled.add(b.ID);
                    try {
                        await updateRequest(b.ID, { status: 'Expired' });
                        await addNotification({
                            recipientId: b.Customer_ID,
                            title: 'Booking Expired',
                            message: `Your ${b.Service_Name} booking for ${fmtDate(b.Date)} has expired.`,
                            requestId: b.ID
                        });
                    } catch (e) { console.warn('Booking expiry failed', e); }
                }
            }
        }

        // ==================== RENDER: SERVICES ====================
        function renderServices() {
            const available = services.filter(s => s.Is_Available).length;
            document.getElementById('statServicesTotal').textContent = services.length;
            document.getElementById('statServicesAvailable').textContent = available;
            document.getElementById('statServicesUnavailable').textContent = services.length - available;
            document.getElementById('statTotalServices').textContent = services.length;

            const grid = document.getElementById('servicesGrid');
            const empty = document.getElementById('servicesEmpty');
            if (!services.length) {
                grid.innerHTML = '';
                empty.style.display = '';
                return;
            }
            empty.style.display = 'none';
            grid.innerHTML = services.map(s => `
                <div class="item-card">
                    <div class="item-image">
                        ${s.Image_URL
                            ? `<img src="${esc(s.Image_URL)}" alt="${esc(s.Name)}">`
                            : `<div class="placeholder"><i class="bi bi-briefcase"></i></div>`}
                        <span class="item-status ${s.Is_Available ? 'available' : 'unavailable'}">
                            ${s.Is_Available ? 'Available' : 'Unavailable'}
                        </span>
                    </div>
                    <div class="item-content">
                        <h4>${esc(s.Name)}</h4>
                        <p>${esc(s.Description || 'No description')}</p>
                        <div class="item-meta">
                            <span class="badge" style="background:var(--info-light);color:var(--info);padding:0.25rem 0.75rem;border-radius:20px;font-size:0.75rem;font-weight:600;">
                                ${esc(s.Category || 'Uncategorized')}
                            </span>
                            <span class="item-duration"><i class="bi bi-clock"></i> ${s.Duration} min</span>
                        </div>
                        <div class="item-actions">
                            <button class="btn btn-sm btn-primary" onclick="editService('${s.ID}')">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <button class="btn btn-sm ${s.Is_Available ? 'btn-secondary' : 'btn-success'}"
                                    onclick="toggleServiceAvailability('${s.ID}', ${s.Is_Available ? 0 : 1})">
                                <i class="bi bi-toggle-${s.Is_Available ? 'on' : 'off'}"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteService('${s.ID}')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');

            // Keep the booking service filter in sync
            const sf = document.getElementById('serviceFilter');
            const prev = sf.value;
            sf.innerHTML = '<option value="">All Services</option>' +
                services.map(s => `<option value="${s.ID}">${esc(s.Name)}</option>`).join('');
            sf.value = prev;
        }

        // ==================== SERVICES ACTIONS ====================
        function editService(id) {
            const service = services.find(s => s.ID == id);
            if (!service) return;
            document.getElementById('editServiceId').value = service.ID;
            document.getElementById('editServiceName').value = service.Name;
            document.getElementById('editServiceDescription').value = service.Description || '';
            document.getElementById('editServiceDuration').value = service.Duration;
            document.getElementById('editServiceCategory').value = service.Category || '';
            document.getElementById('editServiceIsAvailable').checked = !!service.Is_Available;
            const previewDiv = document.getElementById('editServiceImagePreview');
            previewDiv.innerHTML = service.Image_URL
                ? `<img src="${esc(service.Image_URL)}" style="max-height:120px;border-radius:8px;">`
                : '<p style="color:var(--text-light)">No image uploaded</p>';
            openModal('editServiceModal');
        }

        async function toggleServiceAvailability(id, isAvailable) {
            ModalDialog.confirm(`Are you sure you want to ${isAvailable ? 'activate' : 'deactivate'} this service?`, async () => {
                try {
                    await setServiceAvailability(id, isAvailable);
                    showToast(`Service ${isAvailable ? 'activated' : 'deactivated'} successfully!`);
                } catch (error) {
                    console.error(error);
                    showToast('Error toggling availability', 'danger');
                }
            }, { title: 'Confirm Action', type: 'warning' });
        }

        async function deleteService(id) {
            ModalDialog.confirm('Are you sure you want to delete this service? This action cannot be undone.', async () => {
                try {
                    await deleteServiceDoc(id);
                    showToast('Service deleted successfully!');
                } catch (error) {
                    console.error(error);
                    showToast('Error deleting service', 'danger');
                }
            }, { title: 'Delete Service', type: 'danger' });
        }

        // ==================== RENDER: PRODUCTS ====================
        function renderProducts() {
            const available = products.filter(p => p.Is_Available).length;
            const lowStock = products.filter(p => p.Stock_Quantity <= 10 && p.Stock_Quantity > 0).length;
            const categories = new Set(products.map(p => p.Category)).size;
            document.getElementById('statProductsTotal').textContent = products.length;
            document.getElementById('statProductsAvailable').textContent = available;
            document.getElementById('statProductsLowStock').textContent = lowStock;
            document.getElementById('statProductsCategories').textContent = categories;
            document.getElementById('statTotalProducts').textContent = products.length;

            const grid = document.getElementById('productsGrid');
            const empty = document.getElementById('productsEmpty');
            if (!products.length) {
                grid.innerHTML = '';
                empty.style.display = '';
                return;
            }
            empty.style.display = 'none';
            grid.innerHTML = products.map(p => {
                const isLowStock = p.Stock_Quantity <= 10 && p.Stock_Quantity > 0;
                const isOutOfStock = p.Stock_Quantity == 0;
                return `
                <div class="item-card" ${isLowStock ? 'style="border:2px solid var(--warning);"' : ''}>
                    <div class="item-image">
                        ${p.Image_URL
                            ? `<img src="${esc(p.Image_URL)}" alt="${esc(p.Name)}">`
                            : `<div class="placeholder"><i class="bi bi-box-seam"></i></div>`}
                        <span class="item-status ${(p.Is_Available && !isOutOfStock) ? 'available' : 'unavailable'}">
                            ${(p.Is_Available && !isOutOfStock) ? 'Available' : 'Unavailable'}
                        </span>
                    </div>
                    <div class="item-content">
                        <h4>${esc(p.Name)}</h4>
                        <p>${esc(p.Description || 'No description')}</p>
                        <div class="item-meta">
                            <span class="item-price">${fmtMoney(p.Price)}</span>
                            <span class="item-stock" style="color:${isLowStock ? 'var(--warning)' : (isOutOfStock ? 'var(--danger)' : 'var(--text-light)')};font-weight:600;">
                                Stock: ${p.Stock_Quantity}
                            </span>
                        </div>
                        <div class="item-actions">
                            <button class="btn btn-sm btn-primary" onclick="editProduct('${p.ID}')">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <button class="btn btn-sm ${p.Is_Available ? 'btn-secondary' : 'btn-success'}"
                                    onclick="toggleProductAvailability('${p.ID}', ${p.Is_Available ? 0 : 1})">
                                <i class="bi bi-toggle-${p.Is_Available ? 'on' : 'off'}"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteProduct('${p.ID}')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>`;
            }).join('');
        }

        // ==================== PRODUCTS ACTIONS ====================
        function editProduct(id) {
            const product = products.find(p => p.ID == id);
            if (!product) return;
            document.getElementById('editProductId').value = product.ID;
            document.getElementById('editProductName').value = product.Name;
            document.getElementById('editProductDescription').value = product.Description || '';
            document.getElementById('editProductPrice').value = product.Price;
            document.getElementById('editProductStock').value = product.Stock_Quantity;
            document.getElementById('editProductCategory').value = product.Category || '';
            document.getElementById('editProductIsAvailable').checked = !!product.Is_Available;
            const previewDiv = document.getElementById('editProductImagePreview');
            previewDiv.innerHTML = product.Image_URL
                ? `<img src="${esc(product.Image_URL)}" style="max-height:120px;border-radius:8px;">`
                : '<p style="color:var(--text-light)">No image uploaded</p>';
            openModal('editProductModal');
        }

        async function toggleProductAvailability(id, isAvailable) {
            ModalDialog.confirm(`Are you sure you want to ${isAvailable ? 'activate' : 'deactivate'} this product?`, async () => {
                try {
                    await setProductAvailability(id, isAvailable);
                    showToast(`Product ${isAvailable ? 'activated' : 'deactivated'} successfully!`);
                } catch (error) {
                    console.error(error);
                    showToast('Error toggling availability', 'danger');
                }
            }, { title: 'Confirm Action', type: 'warning' });
        }

        async function deleteProduct(id) {
            ModalDialog.confirm('Are you sure you want to delete this product? This action cannot be undone.', async () => {
                try {
                    await deleteProductDoc(id);
                    showToast('Product deleted successfully!');
                } catch (error) {
                    console.error(error);
                    showToast('Error deleting product', 'danger');
                }
            }, { title: 'Delete Product', type: 'danger' });
        }

        // ==================== RENDER: CUSTOMERS ====================
        function renderCustomers() {
            const cutoff = Date.now() - 30 * 86400000;
            const newCount = customers.filter(c => c.createdAt && new Date(c.createdAt).getTime() > cutoff).length;
            document.getElementById('statCustomersTotal').textContent = customers.length;
            document.getElementById('statCustomersNew').textContent = newCount;

            const table = document.getElementById('customersTable');
            const empty = document.getElementById('customersEmpty');
            if (!customers.length) {
                table.style.display = 'none';
                empty.style.display = '';
                return;
            }
            empty.style.display = 'none';
            table.style.display = '';
            document.getElementById('customersBody').innerHTML = customers.map(c => `
                <tr>
                    <td>${esc(c.Full_Name)}</td>
                    <td>${esc(c.Email)}</td>
                    <td>${esc(c.Phone || 'N/A')}</td>
                    <td>${esc(c.Address || 'N/A')}</td>
                    <td>${fmtDate(c.createdAt)}</td>
                </tr>
            `).join('');
        }

        // ==================== RENDER: EMPLOYEES ====================
        function renderEmployees() {
            const available = employees.filter(e => e.Is_Available).length;
            document.getElementById('statEmployeesTotal').textContent = employees.length;
            document.getElementById('statEmployeesAvailable').textContent = available;

            const table = document.getElementById('employeesTable');
            const empty = document.getElementById('employeesEmpty');
            if (!employees.length) {
                table.style.display = 'none';
                empty.style.display = '';
                return;
            }
            empty.style.display = 'none';
            table.style.display = '';
            document.getElementById('employeesBody').innerHTML = employees.map(e => `
                <tr>
                    <td>${esc(e.Full_Name)}</td>
                    <td>${esc(e.Email)}</td>
                    <td>${esc(e.Phone || 'N/A')}</td>
                    <td><span class="badge ${e.Is_Available ? 'badge-success' : 'badge-secondary'}">${e.Is_Available ? 'Available' : 'Unavailable'}</span></td>
                    <td>${fmtDate(e.createdAt)}</td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-sm ${e.Is_Available ? 'btn-secondary' : 'btn-success'}"
                                    onclick="toggleEmployeeAvailability('${e.ID}', ${e.Is_Available ? 0 : 1})">
                                <i class="bi bi-toggle-${e.Is_Available ? 'on' : 'off'}"></i> ${e.Is_Available ? 'Deactivate' : 'Activate'}
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        async function toggleEmployeeAvailability(id, isAvailable) {
            ModalDialog.confirm(`Are you sure you want to ${isAvailable ? 'activate' : 'deactivate'} this employee?`, async () => {
                try {
                    await setEmployeeAvailability(id, isAvailable);
                    showToast(`Employee ${isAvailable ? 'activated' : 'deactivated'} successfully!`);
                } catch (error) {
                    console.error(error);
                    showToast('Error updating employee', 'danger');
                }
            }, { title: 'Confirm Action', type: 'warning' });
        }

        // ==================== SECONDARY-APP USER CREATION ====================
        // Creates a Firebase Auth account without signing the admin out:
        // a second app instance holds the new user's session, which we then
        // sign out and discard.
        async function createAuthUser(email, password) {
            const secondary = initializeApp(window.CONFIG.FIREBASE, 'secondary-' + Date.now());
            const secondaryAuth = getAuth(secondary);
            const cred = await createUserWithEmailAndPassword(secondaryAuth, email, password);
            const uid = cred.user.uid;
            await fbSignOut(secondaryAuth);
            return uid;
        }

        function generateRandomPassword() {
            const length = 12;
            const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
            let password = "";
            for (let i = 0; i < length; i++) {
                password += charset.charAt(Math.floor(Math.random() * charset.length));
            }
            return password;
        }

        function splitName(fullName) {
            const parts = (fullName || '').trim().split(/\s+/);
            return { firstName: parts[0] || '', lastName: parts.slice(1).join(' ') };
        }

        // ==================== EMPLOYEE MODAL ====================
        function openEmployeeModal() {
            document.getElementById('employeeForm').reset();
            document.getElementById('employeeGeneratedPassword').value = '';
            document.getElementById('employeeDisplayPassword').value = '';
            document.getElementById('employeePasswordDisplaySection').style.display = 'none';
            document.getElementById('generateEmployeePasswordBtn').style.display = 'inline-flex';
            document.getElementById('saveEmployeeBtn').style.display = 'none';
            document.getElementById('employeeCopyFeedback').style.display = 'none';
            openModal('employeeModal');
        }

        function generateEmployeePassword() {
            const name = document.getElementById('employeeName').value.trim();
            const email = document.getElementById('employeeEmail').value.trim();
            const phone = document.getElementById('employeePhone').value.trim();
            if (!name || !email || !phone) {
                showToast('Please fill in all fields before generating password', 'danger');
                return;
            }
            const password = generateRandomPassword();
            document.getElementById('employeeGeneratedPassword').value = password;
            document.getElementById('employeeDisplayPassword').value = password;
            document.getElementById('employeePasswordDisplaySection').style.display = 'block';
            document.getElementById('generateEmployeePasswordBtn').style.display = 'none';
            document.getElementById('saveEmployeeBtn').style.display = 'inline-flex';
            copyEmployeePassword();
        }

        function copyEmployeePassword() {
            const passwordInput = document.getElementById('employeeDisplayPassword');
            passwordInput.select();
            passwordInput.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(passwordInput.value).then(() => {
                document.getElementById('employeeCopyFeedback').style.display = 'block';
                setTimeout(() => { document.getElementById('employeeCopyFeedback').style.display = 'none'; }, 3000);
            }).catch(() => {
                showToast('Failed to copy password. Please copy manually.', 'warning');
            });
        }

        // ==================== RENDER: ADMINS ====================
        function renderAdmins() {
            if (!isMainAdmin) return;
            const main = admins.filter(a => a.Role === 'main_admin').length;
            const sub = admins.filter(a => a.Role === 'sub_admin').length;
            document.getElementById('statAdminsTotal').textContent = admins.length;
            document.getElementById('statAdminsMain').textContent = main;
            document.getElementById('statAdminsSub').textContent = sub;

            const table = document.getElementById('adminsTable');
            const empty = document.getElementById('adminsEmpty');
            if (!admins.length) {
                table.style.display = 'none';
                empty.style.display = '';
                return;
            }
            empty.style.display = 'none';
            table.style.display = '';
            document.getElementById('adminsBody').innerHTML = admins.map(a => `
                <tr>
                    <td>${esc(a.Full_Name)}</td>
                    <td>${esc(a.Email)}</td>
                    <td>${esc(a.Phone || 'N/A')}</td>
                    <td>
                        <span class="badge ${a.Role === 'main_admin' ? 'badge-primary' : 'badge-secondary'}">
                            ${a.Role === 'main_admin' ? 'Main Admin' : 'Sub-Admin'}
                        </span>
                    </td>
                    <td>${fmtDate(a.createdAt)}</td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-primary" onclick="editAdmin('${a.ID}')">
                                <i class="bi bi-pencil"></i>
                            </button>
                            ${a.ID !== currentAdmin.ID ? `
                            <button class="btn btn-sm btn-danger" onclick="deleteAdmin('${a.ID}')">
                                <i class="bi bi-trash"></i>
                            </button>` : ''}
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        // ==================== ADMIN ACTIONS ====================
        function generateAdminPassword() {
            const name = document.getElementById('adminName').value.trim();
            const email = document.getElementById('adminEmail').value.trim();
            const phone = document.getElementById('adminPhone').value.trim();
            const role = document.getElementById('adminRole').value;
            if (!name || !email || !phone || !role) {
                showToast('Please fill in all fields before generating password', 'danger');
                return;
            }
            const password = generateRandomPassword();
            document.getElementById('generatedPassword').value = password;
            document.getElementById('displayGeneratedPassword').value = password;
            document.getElementById('passwordDisplaySection').style.display = 'block';
            document.getElementById('generatePasswordBtn').style.display = 'none';
            document.getElementById('saveAdminBtn').style.display = 'inline-flex';
            copyGeneratedPassword();
        }

        function copyGeneratedPassword() {
            const passwordInput = document.getElementById('displayGeneratedPassword');
            passwordInput.select();
            passwordInput.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(passwordInput.value).then(() => {
                document.getElementById('copyFeedback').style.display = 'block';
                setTimeout(() => { document.getElementById('copyFeedback').style.display = 'none'; }, 3000);
            }).catch(() => {
                showToast('Failed to copy password. Please copy manually.', 'warning');
            });
        }

        function editAdmin(id) {
            const admin = admins.find(a => a.ID == id);
            if (!admin) return;
            document.getElementById('adminModalTitle').textContent = 'Edit Admin';
            document.getElementById('adminId').value = admin.ID;
            document.getElementById('adminName').value = admin.Full_Name || '';
            document.getElementById('adminEmail').value = admin.Email;
            document.getElementById('adminEmail').disabled = true;
            document.getElementById('adminPhone').value = admin.Phone || '';
            document.getElementById('adminRole').value = admin.Role;
            document.getElementById('passwordDisplaySection').style.display = 'none';
            document.getElementById('generatePasswordBtn').style.display = 'none';
            document.getElementById('saveAdminBtn').style.display = 'inline-flex';
            document.getElementById('saveAdminBtn').innerHTML = '<i class="bi bi-check-lg"></i> Update Admin';
            openModal('adminModal');
        }

        function openAdminModal() {
            document.getElementById('adminModalTitle').textContent = 'Add New Admin';
            document.getElementById('adminForm').reset();
            document.getElementById('adminEmail').disabled = false;
            document.getElementById('adminId').value = '';
            document.getElementById('generatedPassword').value = '';
            document.getElementById('displayGeneratedPassword').value = '';
            document.getElementById('passwordDisplaySection').style.display = 'none';
            document.getElementById('generatePasswordBtn').style.display = 'inline-flex';
            document.getElementById('saveAdminBtn').style.display = 'none';
            document.getElementById('saveAdminBtn').innerHTML = '<i class="bi bi-check-lg"></i> Save Admin';
            document.getElementById('copyFeedback').style.display = 'none';
            openModal('adminModal');
        }

        async function deleteAdmin(id) {
            ModalDialog.confirm('Are you sure you want to delete this admin? This removes their admin access (their login becomes a customer account).', async () => {
                try {
                    await deleteAdminDoc(id);
                    // Demote the matching users doc so staff rules no longer apply.
                    await updateUserProfile(id, { role: 'customer' });
                    showToast('Admin deleted successfully!');
                } catch (error) {
                    console.error(error);
                    showToast('Error deleting admin', 'danger');
                }
            }, { title: 'Delete Admin', type: 'danger' });
        }

        // ==================== RENDER: PROFILE ====================
        function setProfileEditMode(on) {
            profileEditMode = on;
            const url = new URL(window.location);
            if (on) url.searchParams.set('edit_profile', '1');
            else url.searchParams.delete('edit_profile');
            history.pushState({}, '', url);
            renderProfile();
        }

        function renderProfile() {
            document.getElementById('editProfileBtn').style.display = profileEditMode ? 'none' : '';
            document.getElementById('profileLastChanged').textContent =
                currentAdmin?.updatedAt ? fmtDate(currentAdmin.updatedAt) : 'Unknown';
            document.getElementById('profileAccountStatus').innerHTML = currentAdmin?.first_login
                ? '<span style="color:var(--warning);">First login - password reset required</span>'
                : '<span style="color:var(--success);">Active</span>';

            const body = document.getElementById('profileCardBody');
            if (profileEditMode) {
                body.innerHTML = `
                    <form id="profileEditForm">
                        <div class="form-group">
                            <label>Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-input" value="${esc(currentAdmin?.Full_Name || '')}" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" class="form-input" value="${esc(currentAdmin?.Email || '')}" disabled style="background:var(--secondary-color);">
                            <small style="color:var(--text-light);">Email cannot be changed</small>
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="tel" name="phone" class="form-input" value="${esc(currentAdmin?.Phone || '')}" maxlength="10" placeholder="e.g., 0821234567">
                            <small style="color:var(--text-light);">10 digits (e.g., 0821234567)</small>
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <input type="text" class="form-input" value="${currentAdmin?.Role === 'main_admin' ? 'Main Admin' : 'Sub-Admin'}" disabled style="background:var(--secondary-color);">
                        </div>
                        <div class="form-group">
                            <label>Member Since</label>
                            <input type="text" class="form-input" value="${fmtDateLong(currentAdmin?.createdAt)}" disabled style="background:var(--secondary-color);">
                        </div>
                        <div class="form-actions" style="margin-top:1.5rem;">
                            <button type="button" class="btn btn-secondary" onclick="setProfileEditMode(false)">Cancel</button>
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Save Changes
                            </button>
                        </div>
                    </form>
                `;
                document.getElementById('profileEditForm').addEventListener('submit', saveProfile);
            } else {
                body.innerHTML = `
                    <div class="profile-display">
                        <div class="info-item">
                            <label>Full Name</label>
                            <p>${esc(currentAdmin?.Full_Name || 'N/A')}</p>
                        </div>
                        <div class="info-item">
                            <label>Email</label>
                            <p>${esc(currentAdmin?.Email || 'N/A')}</p>
                        </div>
                        <div class="info-item">
                            <label>Phone</label>
                            <p>${esc(currentAdmin?.Phone || 'Not set')}</p>
                        </div>
                        <div class="info-item">
                            <label>Role</label>
                            <p><span class="badge ${currentAdmin?.Role === 'main_admin' ? 'badge-primary' : 'badge-secondary'}">${currentAdmin?.Role === 'main_admin' ? 'Main Admin' : 'Sub-Admin'}</span></p>
                        </div>
                        <div class="info-item">
                            <label>Member Since</label>
                            <p>${fmtDateLong(currentAdmin?.createdAt)}</p>
                        </div>
                        <div class="info-item">
                            <label>Last Updated</label>
                            <p>${fmtDateLong(currentAdmin?.updatedAt)}</p>
                        </div>
                    </div>
                `;
            }
        }

        async function saveProfile(e) {
            e.preventDefault();
            const fullName = e.target.full_name.value.trim();
            const phone = e.target.phone.value.trim();
            const alertBox = document.getElementById('profileAlert');

            const showProfileMsg = (text, isError) => {
                alertBox.style.display = '';
                alertBox.innerHTML = `<div class="alert alert-${isError ? 'error' : 'success'}"><i class="bi bi-${isError ? 'exclamation' : 'check'}-circle"></i> ${esc(text)}</div>`;
            };

            if (fullName.length < 2) return showProfileMsg('Full name must be at least 2 characters.', true);
            if (!/^[a-zA-Z\s\-']+$/.test(fullName)) return showProfileMsg("Full name can only contain letters, spaces, hyphens, and apostrophes.", true);
            const cleanPhone = phone.replace(/\D/g, '');
            if (!/^0[0-9]{9}$/.test(cleanPhone)) return showProfileMsg('Phone number must be exactly 10 digits starting with 0 (e.g., 0821234567).', true);

            try {
                await updateAdminDoc(currentAdmin.ID, { fullName, phone: cleanPhone });
                const { firstName, lastName } = splitName(fullName);
                await updateUserProfile(currentAdmin.ID, { firstName, lastName, phone: cleanPhone });
                currentAdmin.Full_Name = fullName;
                currentAdmin.Phone = cleanPhone;
                document.getElementById('headerUserName').textContent = fullName;
                showProfileMsg('Profile updated successfully!', false);
                profileEditMode = false;
                renderProfile();
            } catch (error) {
                console.error(error);
                showProfileMsg('Error updating profile. Please try again.', true);
            }
        }

        // ==================== NOTIFICATIONS ====================
        function renderNotifications() {
            const badge = document.getElementById('notificationBadge');
            const unread = notifications.filter(n => !n.is_read).length;
            if (unread > 0) {
                badge.style.display = '';
                badge.textContent = unread;
            } else {
                badge.style.display = 'none';
            }

            const content = document.getElementById('notificationDropdownContent');
            if (!notifications.length) {
                content.innerHTML = `<div class="no-notifications"><i class="bi bi-bell-slash"></i><p>No new notifications</p></div>`;
                return;
            }
            content.innerHTML = notifications.map(n => `
                <div class="notification-dropdown-item ${n.is_read ? '' : 'unread'}" data-id="${n.ID}">
                    <div class="notif-content">
                        <strong>${esc(n.title)}</strong>
                        <p>${esc(n.message)}</p>
                        <small>${fmtDateTime(n.created_at)}</small>
                    </div>
                    <button class="notif-close" onclick="markAdminNotificationRead('${n.ID}')">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            `).join('');
        }

        function toggleNotificationDropdown() {
            document.getElementById('notificationDropdown').classList.toggle('active');
        }

        async function markAdminNotificationRead(notificationId) {
            try {
                await markNotificationRead(notificationId);
            } catch (error) {
                console.error('Error:', error);
            }
        }

        async function markAllAdminNotificationsRead() {
            try {
                await markAllNotificationsRead(notifications.filter(n => !n.is_read).map(n => n.ID));
            } catch (error) {
                console.error('Error:', error);
            }
        }

        // ==================== FORM WIRING ====================
        function wireForms() {
            // Quote form → Firestore
            document.getElementById('quoteForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                const id = document.getElementById('quoteBookingId').value;
                const data = {
                    status: 'Quoted',
                    quotedAmount: Number(document.getElementById('quotedAmount').value),
                    quoteBreakdown: document.getElementById('quoteBreakdown').value,
                    quoteValidityDays: Number(document.getElementById('quoteValidity').value) || 7,
                    quoteNotes: document.getElementById('quoteNotes').value,
                    quoteSentDate: new Date().toISOString()
                };
                try {
                    await updateRequest(id, data);
                    const b = bookings.find(x => x.ID === id);
                    if (b) {
                        await addNotification({
                            recipientId: b.Customer_ID,
                            title: 'Quotation Ready',
                            message: `Your quotation for ${b.Service_Name} is ready: ${fmtMoney(data.quotedAmount)}.`,
                            requestId: id
                        });
                    }
                    showToast('Quotation provided successfully!');
                    closeModal('quoteModal');
                } catch (error) {
                    console.error(error);
                    showToast('Error providing quotation', 'danger');
                }
            });

            // Assign employee form
            document.getElementById('assignForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                const id = document.getElementById('assignBookingId').value;
                const employeeId = document.getElementById('assignEmployeeId').value;
                const emp = employees.find(x => x.ID === employeeId);
                try {
                    await updateRequest(id, {
                        status: 'Approved',
                        employeeId,
                        employeeName: emp?.Full_Name || '',
                        staffId: currentUser.uid
                    });
                    const b = bookings.find(x => x.ID === id);
                    if (b) {
                        await addNotification({
                            recipientId: employeeId,
                            title: 'New Job Assigned',
                            message: `You have been assigned: ${b.Service_Name} on ${fmtDate(b.Date)} at ${fmtTime(b.Time)}.`,
                            requestId: id
                        });
                        await addNotification({
                            recipientId: b.Customer_ID,
                            title: 'Employee Assigned',
                            message: `${emp?.Full_Name || 'A team member'} has been assigned to your ${b.Service_Name} booking.`,
                            requestId: id
                        });
                    }
                    showToast(`Assigned to ${emp?.Full_Name || 'employee'} — job is now Approved.`);
                    closeModal('assignModal');
                } catch (error) {
                    console.error(error);
                    showToast('Error assigning employee', 'danger');
                }
            });

            // Add service
            document.getElementById('addServiceForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                const fd = new FormData(e.target);
                try {
                    const imageUrl = await fileToDataUrl(fd.get('image'));
                    await createService({
                        Name: fd.get('Name'),
                        Description: fd.get('Description'),
                        Duration: fd.get('Duration'),
                        Category: fd.get('Category'),
                        Is_Available: fd.get('Is_Available') === '1',
                        Image_URL: imageUrl
                    });
                    showToast('Service created successfully!');
                    closeModal('addServiceModal');
                    e.target.reset();
                } catch (error) {
                    console.error(error);
                    showToast(error.message || 'Error creating service', 'danger');
                }
            });

            // Edit service
            document.getElementById('editServiceForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                const fd = new FormData(e.target);
                const id = fd.get('id');
                try {
                    const file = fd.get('image');
                    const existing = services.find(s => s.ID === id);
                    const imageUrl = file && file.size ? await fileToDataUrl(file) : (existing?.Image_URL || '');
                    await updateService(id, {
                        Name: fd.get('Name'),
                        Description: fd.get('Description'),
                        Duration: fd.get('Duration'),
                        Category: fd.get('Category'),
                        Is_Available: fd.get('Is_Available') === '1',
                        Image_URL: imageUrl
                    });
                    showToast('Service updated successfully!');
                    closeModal('editServiceModal');
                } catch (error) {
                    console.error(error);
                    showToast(error.message || 'Error updating service', 'danger');
                }
            });

            // Add product
            document.getElementById('addProductForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                const fd = new FormData(e.target);
                try {
                    const imageUrl = await fileToDataUrl(fd.get('image'));
                    await createProduct({
                        Name: fd.get('Name'),
                        Description: fd.get('Description'),
                        Price: fd.get('Price'),
                        Stock_Quantity: fd.get('Stock_Quantity'),
                        Category: fd.get('Category'),
                        Is_Available: fd.get('Is_Available') === '1',
                        Image_URL: imageUrl
                    });
                    showToast('Product created successfully!');
                    closeModal('addProductModal');
                    e.target.reset();
                } catch (error) {
                    console.error(error);
                    showToast(error.message || 'Error creating product', 'danger');
                }
            });

            // Edit product
            document.getElementById('editProductForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                const fd = new FormData(e.target);
                const id = fd.get('id');
                try {
                    const file = fd.get('image');
                    const existing = products.find(p => p.ID === id);
                    const imageUrl = file && file.size ? await fileToDataUrl(file) : (existing?.Image_URL || '');
                    await updateProduct(id, {
                        Name: fd.get('Name'),
                        Description: fd.get('Description'),
                        Price: fd.get('Price'),
                        Stock_Quantity: fd.get('Stock_Quantity'),
                        Category: fd.get('Category'),
                        Is_Available: fd.get('Is_Available') === '1',
                        Image_URL: imageUrl
                    });
                    showToast('Product updated successfully!');
                    closeModal('editProductModal');
                } catch (error) {
                    console.error(error);
                    showToast(error.message || 'Error updating product', 'danger');
                }
            });

            // Change password → Firebase Auth
            document.getElementById('changePasswordForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                const currentPassword = document.getElementById('currentPassword').value;
                const newPassword = document.getElementById('newPasswordChange').value;
                const confirmPassword = document.getElementById('confirmPasswordChange').value;
                if (newPassword !== confirmPassword) {
                    showToast('Passwords do not match', 'danger');
                    return;
                }
                if (newPassword.length < 8) {
                    showToast('Password must be at least 8 characters long', 'danger');
                    return;
                }
                try {
                    await pmaChangePassword(currentPassword, newPassword);
                    showToast('Password changed successfully!');
                    closeModal('changePasswordModal');
                    e.target.reset();
                } catch (error) {
                    showToast(authErrorMessage(error), 'danger');
                }
            });

            // Admin add/edit
            document.getElementById('adminForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                const adminId = document.getElementById('adminId').value;
                const data = {
                    Full_Name: document.getElementById('adminName').value.trim(),
                    Email: document.getElementById('adminEmail').value.trim(),
                    Phone: document.getElementById('adminPhone').value.trim(),
                    Role: document.getElementById('adminRole').value
                };
                try {
                    if (adminId) {
                        await updateAdminDoc(adminId, {
                            fullName: data.Full_Name,
                            phone: data.Phone,
                            adminRole: data.Role
                        });
                        const { firstName, lastName } = splitName(data.Full_Name);
                        await updateUserProfile(adminId, { firstName, lastName, phone: data.Phone });
                        showToast('Admin updated successfully!');
                    } else {
                        const password = document.getElementById('generatedPassword').value;
                        if (!password) {
                            showToast('Generate a password first', 'warning');
                            return;
                        }
                        const uid = await createAuthUser(data.Email, password);
                        const { firstName, lastName } = splitName(data.Full_Name);
                        await setDoc(doc(db, 'users', uid), {
                            role: 'staff',
                            firstName,
                            lastName,
                            email: data.Email,
                            phone: data.Phone,
                            isAvailable: false,
                            firstLogin: true,
                            dateRegistered: new Date().toISOString()
                        });
                        await createAdminDoc(uid, {
                            ...data,
                            first_login: true,
                            createdBy: currentUser.uid
                        });
                        showToast('Admin created — share the generated password with them.');
                    }
                    closeModal('adminModal');
                } catch (error) {
                    console.error(error);
                    showToast(authErrorMessage(error) || 'Error saving admin', 'danger');
                }
            });

            // Employee add
            document.getElementById('employeeForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                const password = document.getElementById('employeeGeneratedPassword').value;
                if (!password) {
                    showToast('Generate a password first', 'warning');
                    return;
                }
                const name = document.getElementById('employeeName').value.trim();
                const email = document.getElementById('employeeEmail').value.trim();
                const phone = document.getElementById('employeePhone').value.trim();
                try {
                    const uid = await createAuthUser(email, password);
                    const { firstName, lastName } = splitName(name);
                    await setDoc(doc(db, 'users', uid), {
                        role: 'employee',
                        firstName,
                        lastName,
                        email,
                        phone,
                        isAvailable: true,
                        firstLogin: true,
                        dateRegistered: new Date().toISOString()
                    });
                    showToast('Employee created — share the generated password with them.');
                    closeModal('employeeModal');
                } catch (error) {
                    console.error(error);
                    showToast(authErrorMessage(error) || 'Error creating employee', 'danger');
                }
            });
        }

        // ==================== FIRST-LOGIN PASSWORD RESET ====================
        function wireFirstLogin() {
            const overlay = document.getElementById('firstLoginOverlay');
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

            newPass.addEventListener('input', function () { checkStrength(this.value); });

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const errBox = document.getElementById('firstLoginError');
                const errText = document.getElementById('firstLoginErrorText');
                errBox.style.display = 'none';
                if (newPass.value !== confirmPass.value) {
                    errText.textContent = 'Passwords do not match.';
                    errBox.style.display = '';
                    return;
                }
                if (!checkStrength(newPass.value)) {
                    errText.textContent = 'Please meet all password requirements.';
                    errBox.style.display = '';
                    return;
                }
                try {
                    await updatePassword(auth.currentUser, newPass.value);
                    await updateAdminDoc(currentAdmin.ID, { firstLogin: false });
                    await updateUserProfile(currentUser.uid, { firstLogin: false });
                    currentAdmin.first_login = false;
                    document.getElementById('firstLoginSuccess').style.display = '';
                    form.style.display = 'none';
                    setTimeout(() => { overlay.style.display = 'none'; renderProfile(); }, 1500);
                } catch (error) {
                    errText.textContent = authErrorMessage(error);
                    errBox.style.display = '';
                }
            });
        }

        // ==================== STATIC WIRING ====================
        function wireStatic() {
            const sidebarOverlay = document.querySelector('.sidebar-overlay');

            // Nav link clicks
            document.querySelectorAll('.nav-link[data-section]').forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    switchSection(link.dataset.section);
                    document.querySelector('.sidebar').classList.remove('mobile-open');
                    if (sidebarOverlay) sidebarOverlay.classList.remove('active');
                });
            });

            // Quick action / view-all clicks
            document.querySelectorAll('[data-section]').forEach(el => {
                if (el.classList.contains('nav-link')) return;
                el.addEventListener('click', (e) => {
                    e.preventDefault();
                    switchSection(el.dataset.section);
                });
            });

            // Sidebar mobile toggle
            const sidebarToggle = document.getElementById('sidebarToggle');
            const headerToggle = document.getElementById('headerToggle');
            const sidebar = document.getElementById('sidebar');
            function toggleSidebar() {
                sidebar.classList.toggle('mobile-open');
                if (sidebarOverlay) sidebarOverlay.classList.toggle('active');
            }
            if (sidebarToggle) sidebarToggle.addEventListener('click', toggleSidebar);
            if (headerToggle) headerToggle.addEventListener('click', toggleSidebar);
            if (sidebarOverlay) sidebarOverlay.addEventListener('click', toggleSidebar);

            // Close modal on backdrop click
            document.querySelectorAll('.modal').forEach(modal => {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) modal.classList.remove('active');
                });
            });

            // Toast animations
            const toastStyles = document.createElement('style');
            toastStyles.textContent = `
                @keyframes toastSlideIn { from { opacity: 0; transform: translateX(100%); } to { opacity: 1; transform: translateX(0); } }
                @keyframes toastSlideOut { from { opacity: 1; transform: translateX(0); } to { opacity: 0; transform: translateX(100%); } }
            `;
            document.head.appendChild(toastStyles);

            // Booking filters — live client-side (replaces GET submit + reload)
            const bookingFilterForm = document.getElementById('bookingFilterForm');
            if (bookingFilterForm) {
                bookingFilterForm.querySelectorAll('input, select').forEach(input => {
                    const eventType = (input.type === 'text' || input.type === 'search') ? 'input' : 'change';
                    input.addEventListener(eventType, function () {
                        clearTimeout(this.filterTimeout);
                        this.filterTimeout = setTimeout(() => {
                            currentPage = 1;
                            renderBookings();
                        }, 300);
                    });
                });
            }

            // Customer search (row hiding, same as before)
            document.getElementById('customerSearch')?.addEventListener('input', function () {
                const searchTerm = this.value.toLowerCase();
                const rows = document.querySelectorAll('#customersBody tr');
                rows.forEach(row => {
                    row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? '' : 'none';
                });
            });

            // Logout
            const doLogout = (e) => { e.preventDefault(); pmaSignOut(); };
            document.getElementById('logoutBtn').addEventListener('click', doLogout);
            document.getElementById('profileLogoutBtn').addEventListener('click', doLogout);

            // Close notification dropdown when clicking outside
            document.addEventListener('click', function (event) {
                const notificationBell = document.querySelector('.notification-bell');
                if (notificationBell && !notificationBell.contains(event.target)) {
                    document.getElementById('notificationDropdown').classList.remove('active');
                }
            });
        }

        // ==================== INIT ====================
        async function init() {
            const { user, profile } = await whenAuthReady();
            if (!user) {
                window.location.href = 'login.php?redirect=' + encodeURIComponent('admin_dashboard.php');
                return;
            }
            if ((profile?.role || '').toLowerCase() !== 'staff') {
                window.location.href = 'index.php';
                return;
            }
            currentUser = user;

            // Load or bootstrap the admins/{uid} record (mirrors old MySQL `admins` row)
            currentAdmin = await getAdminDoc(user.uid);
            if (!currentAdmin) {
                const snap = await getDocs(collection(db, 'admins'));
                const role = snap.empty ? 'main_admin' : 'sub_admin';
                const name = `${profile?.firstName || ''} ${profile?.lastName || ''}`.trim() || user.email;
                await createAdminDoc(user.uid, {
                    Full_Name: name,
                    Email: user.email,
                    Phone: profile?.phone || '',
                    Role: role,
                    first_login: false,
                    createdBy: null
                });
                currentAdmin = await getAdminDoc(user.uid);
            }
            if (currentAdmin.isActive === false) {
                alert('This admin account has been deactivated.');
                await pmaSignOut();
                return;
            }
            isMainAdmin = currentAdmin.Role === 'main_admin';

            document.getElementById('headerUserName').textContent = currentAdmin.Full_Name || 'Admin';

            if (isMainAdmin) {
                document.getElementById('navAdmins').style.display = '';
                document.getElementById('section-admins').style.display = '';
                document.getElementById('quickActionAdmins').style.display = '';
                watchAdmins((list) => { admins = list; renderAdmins(); });
            }

            // First-login forced password reset
            if (currentAdmin.first_login) {
                document.getElementById('firstLoginName').textContent = currentAdmin.Full_Name || currentAdmin.Email;
                document.getElementById('firstLoginOverlay').style.display = '';
                wireFirstLogin();
            }

            // Live Firestore subscriptions
            watchAllRequests((list) => {
                bookings = list.map(r => {
                    const svc = services.find(s => s.ID === r.Service_ID);
                    return {
                        ...r,
                        _status: r.Status,
                        Status: FS_TO_LEGACY[r.Status] || 'requested',
                        Service_Duration: svc?.Duration ?? null
                    };
                });
                renderDashboard();
                renderBookings();
                runExpirySweep();
            });
            watchServices((list) => {
                services = list;
                // re-map bookings now that durations are known
                bookings.forEach(b => {
                    const svc = services.find(s => s.ID === b.Service_ID);
                    b.Service_Duration = svc?.Duration ?? null;
                });
                renderServices();
                renderDashboard();
                renderBookings();
            });
            watchProducts((list) => { products = list; renderProducts(); renderDashboard(); });
            watchCustomers((list) => { customers = list; renderCustomers(); });
            watchEmployees((list) => { employees = list; renderEmployees(); });
            watchStaffNotifications(user.uid, (items) => { notifications = items; renderNotifications(); });

            wireStatic();
            wireForms();
            renderProfile();

            // Restore the requested section (?section=..., ?edit_profile=1)
            const params = new URLSearchParams(location.search);
            const section = params.get('section') || 'dashboard';
            switchSection(section);
            if (params.get('edit_profile') === '1') setProfileEditMode(true);

            document.getElementById('authGate').remove();
        }

        // Expose handlers used by inline onclick attributes
        Object.assign(window, {
            switchSection, clearFilters, goPage, openModal, closeModal, showToast,
            viewBooking, contactCustomer, downloadQuotePDF, viewQuoteDetails, viewQuoteDetailsById,
            quickAction, updateStatus, openQuoteModal, openAssignModal, exportBookings,
            editService, toggleServiceAvailability, deleteService,
            editProduct, toggleProductAvailability, deleteProduct,
            toggleEmployeeAvailability, openEmployeeModal, generateEmployeePassword, copyEmployeePassword,
            editAdmin, openAdminModal, deleteAdmin, generateAdminPassword, copyGeneratedPassword,
            setProfileEditMode,
            toggleNotificationDropdown, markAdminNotificationRead, markAllAdminNotificationsRead
        });

        init().catch((e) => {
            console.error('Admin dashboard init failed:', e);
            document.getElementById('authGate').innerHTML =
                '<p style="color:var(--danger,#dc2626);font-weight:600;">Failed to load the admin dashboard.<br>' +
                esc(e.message || e) + '</p>';
        });
    </script>
</body>
</html>
