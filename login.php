<?php
// link the files
require_once 'includes/config_session.inc.php';
require_once 'includes/signup_view.inc.php';
require_once 'includes/login_view.inc.php';

// Store redirect URL if provided
if (isset($_GET['redirect'])) {
    $redirect_url = $_GET['redirect'];
    // Append any additional query parameters
    if (isset($_GET['book'])) {
        $redirect_url .= '?book=' . urlencode($_GET['book']);
    }
    $_SESSION['redirect_after_login'] = $redirect_url;
}

// If user is already logged in, redirect to appropriate page
if (isset($_SESSION["user_id"])) {
    if (isset($_SESSION['redirect_after_login'])) {
        $redirect = $_SESSION['redirect_after_login'];
        unset($_SESSION['redirect_after_login']);
        header("Location: " . $redirect);
    } else {
        header("Location: index.php");
    }
    exit();
}

// Pass rate limit status to JavaScript
$pmaLockoutSeconds = $_SESSION['rate_limit_retry_after'] ?? 0;
$pmaLockoutEmail = $_SESSION['login_email'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login | Phambili Ma-Africa</title>
    <link rel="icon" type="image/png" sizes="32x32" href="images/PM-Logo.jpg">

    <!-- Open Graph Meta Tags for Social Media Preview -->
    <meta property="og:title" content="Login | Phambili Ma-Africa">
    <meta property="og:description" content="Login to your Phambili Ma-Africa account to manage bookings and services.">
    <meta property="og:image" content="images/PM-LogoB.png">
    <meta property="og:url" content="https://phambili-ma-africa.com/login.php">
    <meta property="og:type" content="website">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Login | Phambili Ma-Africa">
    <meta name="twitter:description" content="Login to your Phambili Ma-Africa account to manage bookings and services.">
    <meta name="twitter:image" content="images/PM-LogoB.png">
    
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="css/login.css" />

</head>

<body>
    <?php if (isset($_SESSION["user_id"])): ?>
        <!-- User is logged in - redirect to index.php -->
        <script>window.location.href = 'index.php';</script>
    <?php else: ?>
        <!-- User is not logged in - show authentication forms -->
        <div class="auth-container">
            <div class="auth-card">


                <!-- Tabs -->
                <div class="auth-tabs">
                    <div class="auth-tab active" data-tab="login">Login</div>
                    <div class="auth-tab" data-tab="register">Register</div>
                </div>

                <!-- PHP Error/Success Messages -->
                <?php
                // Consolidate all PHP messages into single notifications
                $phpMessage = null;
                $messageType = 'error';
                $rateLimitSeconds = 0;
                $lockedOutEmail   = '';

                // Check for login errors
                if (isset($_SESSION['errors_login'])) {
                    $phpMessage = implode('<br>', $_SESSION['errors_login']);
                    $messageType = 'error';
                    $targetTab = 'login';
                    if (isset($_SESSION['rate_limit_retry_after'])) {
                        $rateLimitSeconds = (int)$_SESSION['rate_limit_retry_after'];
                        unset($_SESSION['rate_limit_retry_after']);
                    }
                    // Capture email for JS BEFORE the session is cleared
                    if (isset($_SESSION['login_email'])) {
                        $lockedOutEmail = $_SESSION['login_email'];
                    }
                    unset($_SESSION['errors_login']);
                }
                // Check for signup errors (only if no login errors)
                elseif (isset($_SESSION['errors_signup'])) {
                    $phpMessage = implode('<br>', $_SESSION['errors_signup']);
                    $messageType = 'error';
                    $targetTab = 'register';
                    unset($_SESSION['errors_signup']);
                }
                // Check for login success
                elseif (isset($_GET["login"]) && $_GET["login"] === "success") {
                    $phpMessage = 'Login successful! Welcome back.';
                    $messageType = 'success';
                    $targetTab = 'login';
                }
                // Check for signup success
                elseif (isset($_GET["signup"]) && $_GET["signup"] === "success") {
                    $phpMessage = 'Registration successful! Please login.';
                    $messageType = 'success';
                    $targetTab = 'register';
                }
                
                // Display the consolidated message if exists
                if ($phpMessage): ?>
                    <div id="php-notification" class="auth-<?php echo $messageType === 'success' ? 'success' : 'error-message'; ?>" 
                         style="display: none !important;" data-target-tab="<?php echo htmlspecialchars($targetTab); ?>">
                        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        <?php echo $phpMessage; ?>
                    </div>
                <?php endif; ?>


                <!-- Login Form -->
                <div id="login" class="auth-tab-content active">

                    <form action="includes/login.inc.php" method="post" class="auth-form">
                        <?php csrfField(); ?>
                        <div class="auth-form-group">
                            <label for="login-email" class="auth-label">Email Address</label>
                            <input type="email" id="login-email" name="email" class="auth-input" placeholder="your@email.com" required value="<?php echo isset($_SESSION['login_email']) ? htmlspecialchars($_SESSION['login_email']) : ''; ?>">
                        </div>
                        <div class="auth-form-group">
                            <label for="login-password" class="auth-label">Password</label>
                            <div class="input-with-icon">
                                <input type="password" id="login-password" name="password" class="auth-input" placeholder="••••••••" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('login-password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <!-- Remember me - its own row -->
                        <div class="auth-remember">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember">Remember me</label>
                        </div>
                        <!-- Forgot password + lockout timer on the same row -->
                        <div class="auth-forgot-row">
                            <a href="password_reset.php" class="auth-forgot">Forgot password?</a>
                            <!-- Always in DOM; JS toggles visibility via localStorage -->
                            <span class="rate-limit-countdown" id="rateLimitCountdown" style="display:none">
                                <i class="fas fa-lock"></i>
                                Locked: <span id="rateLimitTimer"></span>
                            </span>
                        </div>
                        <button type="submit" class="auth-btn">
                            <span>Login</span>
                            <div class="spinner"></div>
                        </button>

                        <div class="auth-home-link">
                            <a href="index.php">
                                <i class="fas fa-home"></i> Back to Home
                            </a>
                        </div>

                        <!-- Processing Overlay -->
                        <div id="loginOverlay" class="form-processing-overlay" style="display: none;">
                            <div class="processing-content">
                                <div class="processing-spinner"></div>
                                <p class="processing-text">Signing you in...</p>
                                <p class="processing-subtext">Please wait a moment</p>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Register Form -->
                <div id="register" class="auth-tab-content">
                    <!-- Step 1: Registration Form -->
                    <div id="registerStep1" class="auth-step active">
                        <form id="registerForm" class="auth-form">
                            <div class="auth-form-group">
                                <label for="register-fullname" class="auth-label">Full Name</label>
                                <input type="text" id="register-fullname" name="fullName" class="auth-input" placeholder="John Doe" required>
                            </div>
                            <div class="auth-form-group">
                                <label for="register-email" class="auth-label">Email Address</label>
                                <input type="email" id="register-email" name="email" class="auth-input" placeholder="your@email.com" required>
                            </div>
                            <div class="auth-form-group">
                                <label for="register-password" class="auth-label">Password</label>
                                <div class="input-with-icon">
                                    <input type="password" id="register-password" name="password" class="auth-input" placeholder="Create a strong password" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('register-password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <!-- Password Strength Bar -->
                                <div class="password-strength">
                                    <div class="password-strength-fill" id="registerStrengthFill"></div>
                                </div>
                                <!-- Password Requirements -->
                                <div class="password-requirements-box">
                                    <strong>Password must include:</strong>
                                    <ul>
                                        <li id="reg-req-len">At least 8 characters</li>
                                        <li id="reg-req-upper">One uppercase letter</li>
                                        <li id="reg-req-lower">One lowercase letter</li>
                                        <li id="reg-req-number">One number</li>
                                        <li id="reg-req-special">One special character (!@#$%^&*)</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="auth-form-group">
                                <label for="register-confirm-password" class="auth-label">Confirm Password</label>
                                <div class="input-with-icon">
                                    <input type="password" id="register-confirm-password" name="confirmPassword" class="auth-input" placeholder="Re-enter your password" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('register-confirm-password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="auth-terms">
                                By registering, you agree to our <a href="#" target="_blank">Terms of Service</a> and <a href="#" target="_blank">Privacy Policy</a>
                            </div>
                            <button type="submit" class="auth-btn" id="registerBtn">
                                <span>Create Account</span>
                                <div class="spinner"></div>
                            </button>

                            <!-- Processing Overlay -->
                            <div id="registerStep1Overlay" class="form-processing-overlay" style="display: none;">
                                <div class="processing-content">
                                    <div class="processing-spinner"></div>
                                    <p class="processing-text">Creating your account...</p>
                                    <p class="processing-subtext">Please wait a moment</p>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Step 2: Verify OTP -->
                    <div id="registerStep2" class="auth-step" style="display: none;">
                        <div class="auth-form" id="otpForm">
                            <p style="text-align: center; color: #7f8c8d; margin-bottom: 1.5rem;">
                                Enter the 6-digit code sent to your email:
                            </p>
                            <p style="text-align: center; color: #e74c3c; font-size: 0.9rem; margin-bottom: 1rem;">
                                Code expires in: <span id="otpCountdown">10:00</span>
                            </p>
                            <div class="otp-inputs">
                                <input type="text" class="otp-input" maxlength="1" data-index="0">
                                <input type="text" class="otp-input" maxlength="1" data-index="1">
                                <input type="text" class="otp-input" maxlength="1" data-index="2">
                                <input type="text" class="otp-input" maxlength="1" data-index="3">
                                <input type="text" class="otp-input" maxlength="1" data-index="4">
                                <input type="text" class="otp-input" maxlength="1" data-index="5">
                            </div>
                            <div id="otpMessage" class="otp-message"></div>
                            <button class="otp-verify-btn" id="verifyOtpBtn" onclick="verifyOTP()">Verify</button>
                            <button class="otp-resend-btn" id="resendOtpBtn" onclick="resendOTP()">Resend Code</button>
                            <button class="auth-btn" onclick="goToRegisterStep(1)" style="background: transparent; color: #7f8c8d; border: 1px solid #e0e6ed; margin-top: 0.5rem;">Back</button>

                            <!-- Processing Overlay -->
                            <div id="registerStep2Overlay" class="form-processing-overlay" style="display: none;">
                                <div class="processing-content">
                                    <div class="processing-spinner"></div>
                                    <p class="processing-text">Verifying your code...</p>
                                    <p class="processing-subtext">Please wait a moment</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="auth-footer">
                    2023 Phambili Ma-Africa Cleaning Services. All rights reserved.
                </div>
            </div>
        </div>

    <?php endif;
    // Clear stored login email after displaying the form (cleanup for next page load)
    if (isset($_SESSION['login_email'])) {
        unset($_SESSION['login_email']);
    }
    ?>

    <script>
        // Lockout data from PHP — used to seed localStorage on the first rate-limit hit
        var pmaLockoutSeconds = <?php echo (int)($rateLimitSeconds ?? 0); ?>;
        var pmaLockoutEmail   = <?php echo json_encode($lockedOutEmail ?? ''); ?>;
    </script>

    <script>
        // Simple tab switching functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.auth-tab');
            const tabContents = document.querySelectorAll('.auth-tab-content');

            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const targetTab = this.getAttribute('data-tab');
                    
                    // Remove active class from all tabs and contents
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    // Add active class to clicked tab and corresponding content
                    this.classList.add('active');
                    document.getElementById(targetTab).classList.add('active');
                    
                    // Show/hide PHP error messages based on active tab
                    showTabSpecificErrors(targetTab);
                });
            });

            // Function to show tab-specific PHP errors
            function showTabSpecificErrors(activeTab) {
                const phpNotification = document.getElementById('php-notification');
                
                if (phpNotification) {
                    const targetTab = phpNotification.getAttribute('data-target-tab');
                    
                    // Only show the PHP notification if it belongs to the active tab
                    if (targetTab === activeTab) {
                        phpNotification.style.display = 'block';
                        
                        // Auto-hide after 5 seconds like JavaScript errors
                        setTimeout(() => {
                            phpNotification.style.display = 'none';
                        }, 5000);
                    } else {
                        phpNotification.style.display = 'none';
                    }
                }
            }
            
            // Initialize: show errors for the default active tab (login)
            showTabSpecificErrors('login');

            // Password toggle functionality
            window.togglePassword = function(inputId) {
                const input = document.getElementById(inputId);
                const icon = input.nextElementSibling.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            };

            // Password strength checker for registration
            const regPassword = document.getElementById('register-password');
            const strengthFill = document.getElementById('registerStrengthFill');
            if (regPassword && strengthFill) {
                const requirements = {
                    len: document.getElementById('reg-req-len'),
                    upper: document.getElementById('reg-req-upper'),
                    lower: document.getElementById('reg-req-lower'),
                    number: document.getElementById('reg-req-number'),
                    special: document.getElementById('reg-req-special')
                };
                
                regPassword.addEventListener('input', function() {
                    const password = this.value;
                    let score = 0;
                    
                    const hasLen = password.length >= 8;
                    const hasUpper = /[A-Z]/.test(password);
                    const hasLower = /[a-z]/.test(password);
                    const hasNumber = /[0-9]/.test(password);
                    const hasSpecial = /[!@#$%^&*]/.test(password);
                    
                    if (requirements.len) requirements.len.classList.toggle('met', hasLen);
                    if (requirements.upper) requirements.upper.classList.toggle('met', hasUpper);
                    if (requirements.lower) requirements.lower.classList.toggle('met', hasLower);
                    if (requirements.number) requirements.number.classList.toggle('met', hasNumber);
                    if (requirements.special) requirements.special.classList.toggle('met', hasSpecial);
                    
                    if (hasLen) score++;
                    if (hasUpper) score++;
                    if (hasLower) score++;
                    if (hasNumber) score++;
                    if (hasSpecial) score++;
                    
                    strengthFill.className = 'password-strength-fill';
                    if (score <= 2) strengthFill.classList.add('weak');
                    else if (score <= 4) strengthFill.classList.add('medium');
                    else strengthFill.classList.add('strong');
                });
            }

            // Full Name validation - only allow letters, spaces, hyphens, and apostrophes
            const fullNameInput = document.getElementById('register-fullname');
            if (fullNameInput) {
                fullNameInput.addEventListener('input', function() {
                    // Remove any characters that aren't letters, spaces, hyphens, or apostrophes
                    this.value = this.value.replace(/[^a-zA-Z\s\-']/g, '');
                    
                    // Clear error if user is typing valid characters
                    if (this.value.match(/^[a-zA-Z\s\-']+$/)) {
                        this.classList.remove('error');
                    }
                });

                fullNameInput.addEventListener('blur', function() {
                    // Validate on blur
                    if (!this.value.trim()) {
                        this.classList.add('error');
                        showError('Full name is required');
                    } else if (!this.value.match(/^[a-zA-Z\s\-']+$/)) {
                        this.classList.add('error');
                        showError('Full name can only contain letters, spaces, hyphens, and apostrophes');
                    } else if (this.value.trim().length < 2) {
                        this.classList.add('error');
                        showError('Full name must be at least 2 characters long');
                    } else {
                        this.classList.remove('error');
                    }
                });
            }

            // Email validation
            const emailInputs = document.querySelectorAll('input[type="email"]');
            emailInputs.forEach(input => {
                input.addEventListener('blur', function() {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!this.value.trim()) {
                        this.classList.add('error');
                        showError('Email is required');
                    } else if (!emailRegex.test(this.value)) {
                        this.classList.add('error');
                        showError('Please enter a valid email address');
                    } else {
                        this.classList.remove('error');
                    }
                });
            });

            // Password validation
            const passwordInputs = document.querySelectorAll('input[name="password"]');
            passwordInputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (!this.value.trim()) {
                        this.classList.add('error');
                        showError('Password is required');
                    } else if (this.value.length < 8) {
                        this.classList.add('error');
                        showError('Password must be at least 8 characters long');
                    } else {
                        this.classList.remove('error');
                    }
                });
            });

            // Confirm password validation
            const confirmPasswordInput = document.getElementById('register-confirm-password');
            const passwordInput = document.getElementById('register-password');
            
            if (confirmPasswordInput && passwordInput) {
                confirmPasswordInput.addEventListener('blur', function() {
                    if (!this.value.trim()) {
                        this.classList.add('error');
                        showError('Please confirm your password');
                    } else if (this.value !== passwordInput.value) {
                        this.classList.add('error');
                        showError('Passwords do not match');
                    } else {
                        this.classList.remove('error');
                    }
                });
            }

            // Form submission validation
            const forms = document.querySelectorAll('.auth-form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    // For login form: completely bypass JavaScript to ensure rate limiting works
                    if (this.action.includes('login.inc.php')) {
                        // Show processing overlay
                        const overlay = document.getElementById('loginOverlay');
                        if (overlay) overlay.style.display = 'flex';

                        const button = this.querySelector('.auth-btn');
                        button.classList.add('loading');
                        button.disabled = true;

                        // Completely bypass any JavaScript interference
                        return;
                    }

                    // For register form: validate client-side before OTP
                    let isValid = true;
                    const errors = [];

                    // Validate all required fields
                    const requiredInputs = this.querySelectorAll('input[required]');
                    requiredInputs.forEach(input => {
                        if (!input.value.trim()) {
                            input.classList.add('error');
                            isValid = false;
                            const label = input.closest('.auth-form-group')?.querySelector('.auth-label')?.textContent || input.name;
                            errors.push(label + ' is required');
                        }
                    });

                    // Specific validations for register form
                    if (this.querySelector('#register-fullname')) {
                        const fullName = document.getElementById('register-fullname');
                        if (!fullName.value.match(/^[a-zA-Z\s\-']+$/) || fullName.value.trim().length < 2) {
                            fullName.classList.add('error');
                            isValid = false;
                            errors.push('Full name must be at least 2 characters and contain only letters');
                        }
                    }

                    // Email validation
                    const emailInput = this.querySelector('input[type="email"]');
                    if (emailInput && !emailInput.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                        emailInput.classList.add('error');
                        isValid = false;
                        errors.push('Please enter a valid email address');
                    }

                    // Password validation
                    const passwordInput = this.querySelector('input[name="password"]');
                    if (passwordInput && passwordInput.value.length < 8) {
                        passwordInput.classList.add('error');
                        isValid = false;
                        errors.push('Password must be at least 8 characters long');
                    }

                    // Confirm password validation for register form
                    const confirmPwd = document.getElementById('register-confirm-password');
                    const regPassword = document.getElementById('register-password');
                    if (confirmPwd && regPassword) {
                        if (confirmPwd.value !== regPassword.value) {
                            confirmPwd.classList.add('error');
                            isValid = false;
                            errors.push('Passwords do not match');
                        }
                    }

                    // If validation failed, show errors and stop
                    if (!isValid) {
                        e.preventDefault();
                        showError(errors.join('<br>'));
                        return;
                    }

                    // Show loading for register form
                    const button = this.querySelector('.auth-btn');
                    button.classList.add('loading');
                    button.disabled = true;
                });
            });

            // Error display function
            function showError(message) {
                // Remove existing error messages
                const existingErrors = document.querySelectorAll('.validation-error');
                existingErrors.forEach(error => error.remove());

                // Create and show new error message
                const errorDiv = document.createElement('div');
                errorDiv.className = 'validation-error';
                errorDiv.style.cssText = `
                    background: #e74c3c;
                    color: white;
                    padding: 1rem;
                    border-radius: 8px;
                    margin-bottom: 1rem;
                    text-align: center;
                    animation: fadeIn 0.3s ease-out;
                `;
                errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
                
                // Insert at the beginning of the active tab content
                const activeTab = document.querySelector('.auth-tab-content.active');
                if (activeTab) {
                    activeTab.insertBefore(errorDiv, activeTab.firstChild);
                    
                    // Auto-remove after 5 seconds
                    setTimeout(() => {
                        errorDiv.remove();
                    }, 5000);
                }
            }

            // Clear errors when user starts typing
            const inputs = document.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    this.classList.remove('error');
                });
            });

            // Handle Registration Form Submission - Send OTP
            const registerForm = document.getElementById('registerForm');
            if (registerForm) {
                registerForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    // Validate form
                    let isValid = true;
                    const errors = [];
                    
                    const fullName = document.getElementById('register-fullname');
                    const email = document.getElementById('register-email');
                    const password = document.getElementById('register-password');
                    const confirmPassword = document.getElementById('register-confirm-password');
                    
                    if (!fullName.value.trim()) {
                        fullName.classList.add('error');
                        isValid = false;
                        errors.push('Full name is required');
                    }
                    
                    if (!email.value.trim() || !email.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                        email.classList.add('error');
                        isValid = false;
                        errors.push('Valid email is required');
                    }
                    
                    if (password.value.length < 8) {
                        password.classList.add('error');
                        isValid = false;
                        errors.push('Password must be at least 8 characters');
                    }
                    
                    if (password.value !== confirmPassword.value) {
                        confirmPassword.classList.add('error');
                        isValid = false;
                        errors.push('Passwords do not match');
                    }
                    
                    if (!isValid) {
                        showError(errors.join('<br>'));
                        return;
                    }
                    
                    // Store registration data
                    registrationData = {
                        fullName: fullName.value,
                        email: email.value,
                        password: password.value
                    };
                    
                    // Send OTP
                    const btn = document.getElementById('registerBtn');
                    btn.classList.add('loading');
                    btn.disabled = true;
                    // Show processing overlay
                    const overlay = document.getElementById('registerStep1Overlay');
                    if (overlay) overlay.style.display = 'flex';
                    
                    try {
                        // Step 1: Request OTP generation from server
                        const response = await fetch('api/otp_actions.php?action=send-otp', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                email: email.value,
                                otp_type: 'email_verification'
                            })
                        });

                        const result = await response.json();

                        if (result.success && result.otp_code) {
                            // Step 2: Send OTP email using EmailJS
                            const emailSent = await sendOTPEmailJS(result.email, result.otp_code, result.otp_type);

                            if (emailSent) {
                                // Go to OTP verification step
                                goToRegisterStep(2);
                            } else {
                                showError('Failed to send OTP email. Please try again.');
                            }
                        } else {
                            showError(result.message);
                        }
                    } catch (error) {
                        console.error('OTP Error:', error);
                        showError('Failed to send OTP. Please try again.');
                    } finally {
                        btn.classList.remove('loading');
                        btn.disabled = false;
                        if (overlay) overlay.style.display = 'none';
                    }
                });
            }

            // OTP Input Handling
            const otpInputs = document.querySelectorAll('.otp-input');
            otpInputs.forEach((input, index) => {
                input.addEventListener('input', function(e) {
                    this.value = this.value.replace(/[^0-9]/g, '');
                    if (this.value.length === 1 && index < otpInputs.length - 1) otpInputs[index + 1].focus();
                });
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && this.value === '' && index > 0) otpInputs[index - 1].focus();
                });
                input.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pastedData = e.clipboardData.getData('text').slice(0, 6);
                    pastedData.split('').forEach((char, i) => {
                        if (otpInputs[i] && /[0-9]/.test(char)) otpInputs[i].value = char;
                    });
                });
            });

            // ── Rate-limit countdown (persists across refreshes via localStorage) ──
            (function () {
                var EXPIRY_KEY = 'pma_lockoutExpiry';
                var EMAIL_KEY  = 'pma_lockoutEmail';

                var badge      = document.getElementById('rateLimitCountdown');
                var display    = document.getElementById('rateLimitTimer');
                var loginBtn   = document.querySelector('#login .auth-btn[type="submit"]');
                var emailInput = document.getElementById('login-email');
                var passInput  = document.getElementById('login-password');

                // Fresh lockout from PHP — seed localStorage with absolute expiry timestamp
                if (pmaLockoutSeconds > 0) {
                    localStorage.setItem(EXPIRY_KEY, Date.now() + pmaLockoutSeconds * 1000);
                    if (pmaLockoutEmail) localStorage.setItem(EMAIL_KEY, pmaLockoutEmail);
                }

                // Read stored expiry (works on refresh because it persists in localStorage)
                var stored   = localStorage.getItem(EXPIRY_KEY);
                var expiryTs = stored ? parseInt(stored, 10) : 0;

                // If already expired, clean up and exit
                if (!expiryTs || expiryTs <= Date.now()) {
                    localStorage.removeItem(EXPIRY_KEY);
                    localStorage.removeItem(EMAIL_KEY);
                    return;
                }

                // ── Active lockout — apply UI restrictions ───────────────────
                var storedEmail = localStorage.getItem(EMAIL_KEY) || '';

                // Pre-fill email and lock both inputs
                if (emailInput && storedEmail) {
                    emailInput.value    = storedEmail;
                    emailInput.readOnly = true;
                    emailInput.classList.add('locked-input');
                }
                if (passInput) {
                    passInput.readOnly = true;
                    passInput.classList.add('locked-input');
                }

                // Disable submit button
                if (loginBtn) {
                    loginBtn.disabled = true;
                    loginBtn.title    = 'Too many attempts — wait for the timer to expire.';
                }

                // Show the badge
                if (badge) badge.style.display = 'inline-flex';

                function fmt(s) {
                    var m   = Math.floor(s / 60);
                    var sec = s % 60;
                    return (m < 10 ? '0' : '') + m + ':' + (sec < 10 ? '0' : '') + sec;
                }

                function unlock() {
                    localStorage.removeItem(EXPIRY_KEY);
                    localStorage.removeItem(EMAIL_KEY);
                    if (badge)      { badge.style.display = 'none'; }
                    if (loginBtn)   { loginBtn.disabled = false; loginBtn.title = ''; }
                    if (emailInput) { emailInput.readOnly = false; emailInput.classList.remove('locked-input'); }
                    if (passInput)  { passInput.readOnly  = false; passInput.classList.remove('locked-input'); }
                }

                function tick() {
                    var remaining = Math.ceil((expiryTs - Date.now()) / 1000);
                    if (remaining <= 0) {
                        clearInterval(timer);
                        unlock();
                    } else {
                        if (display) display.textContent = fmt(remaining);
                    }
                }

                tick(); // render immediately on load
                var timer = setInterval(tick, 1000);
            })();
            // ────────────────────────────────────────────────────────────────

        });

        // Global OTP functions (outside DOMContentLoaded for onclick handlers)
        let registrationData = null;
        let countdownInterval = null;
        let remainingTime = 600; // 10 minutes in seconds

        function goToRegisterStep(step) {
            // Stop countdown if leaving step 2
            if (countdownInterval) {
                clearInterval(countdownInterval);
                countdownInterval = null;
            }

            document.querySelectorAll('#register .auth-step').forEach(s => s.style.display = 'none');
            document.getElementById('registerStep' + step).style.display = 'block';

            if (step === 2) {
                const otpInputs = document.querySelectorAll('.otp-input');
                otpInputs.forEach(input => input.value = '');
                otpInputs[0].focus();
                document.getElementById('otpMessage').textContent = '';

                // Start countdown timer
                remainingTime = 600; // 10 minutes
                startCountdown();
            }
        }

        function startCountdown() {
            const countdownElement = document.getElementById('otpCountdown');
            if (!countdownElement) return;
            
            if (countdownInterval) clearInterval(countdownInterval);
            updateCountdownDisplay();
            
            countdownInterval = setInterval(function() {
                remainingTime--;
                updateCountdownDisplay();
                if (remainingTime <= 0) {
                    clearInterval(countdownInterval);
                    countdownInterval = null;
                    countdownElement.textContent = '00:00';
                    document.getElementById('otpMessage').textContent = 'Code expired. Please request a new code.';
                    document.getElementById('otpMessage').style.color = '#e74c3c';
                }
            }, 1000);
        }

        function updateCountdownDisplay() {
            const countdownElement = document.getElementById('otpCountdown');
            if (!countdownElement) return;
            const minutes = Math.floor(remainingTime / 60);
            const seconds = remainingTime % 60;
            countdownElement.textContent = (minutes < 10 ? '0' : '') + minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
        }

        async function verifyOTP() {
            const otpInputs = document.querySelectorAll('.otp-input');
            let otpCode = '';
            otpInputs.forEach(input => otpCode += input.value);
            
            if (otpCode.length !== 6) {
                document.getElementById('otpMessage').textContent = 'Please enter the complete 6-digit code';
                document.getElementById('otpMessage').style.color = '#e74c3c';
                return;
            }
            
            const btn = document.getElementById('verifyOtpBtn');
            btn.classList.add('loading');
            btn.disabled = true;
            
            // Show processing overlay
            const overlay = document.getElementById('registerStep2Overlay');
            if (overlay) overlay.style.display = 'flex';
            
            try {
                const response = await fetch('api/otp_actions.php?action=verify-otp', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        email: registrationData.email,
                        otp_code: otpCode,
                        otp_type: 'email_verification'
                    })
                });
                
                const responseText = await response.text();
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseErr) {
                    console.error('Server returned non-JSON:', responseText);
                    document.getElementById('otpMessage').textContent = 'Server error. Check console for details.';
                    document.getElementById('otpMessage').style.color = '#e74c3c';
                    if (overlay) overlay.style.display = 'none';
                    return;
                }
                
                if (result.success) {
                    await createAccount();
                } else {
                    document.getElementById('otpMessage').textContent = result.message;
                    document.getElementById('otpMessage').style.color = '#e74c3c';
                    if (overlay) overlay.style.display = 'none';
                }
            } catch (error) {
                console.error('Network or fetch error:', error);
                document.getElementById('otpMessage').textContent = 'Network error. Please try again.';
                document.getElementById('otpMessage').style.color = '#e74c3c';
                if (overlay) overlay.style.display = 'none';
            } finally {
                btn.classList.remove('loading');
                btn.disabled = false;
            }
        }

        async function createAccount() {
            // Update overlay text for account creation
            const overlay = document.getElementById('registerStep2Overlay');
            if (overlay) {
                overlay.querySelector('.processing-text').textContent = 'Creating your account...';
                overlay.style.display = 'flex';
            }
            
            try {
                const response = await fetch('includes/signup.inc.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        fullName: registrationData.fullName,
                        email: registrationData.email,
                        password: registrationData.password,
                        confirmPassword: registrationData.password
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    window.location.href = 'index.php';
                } else {
                    document.getElementById('otpMessage').textContent = result.message || 'Failed to create account. Please try again.';
                    document.getElementById('otpMessage').style.color = '#e74c3c';
                    if (overlay) overlay.style.display = 'none';
                }
            } catch (error) {
                document.getElementById('otpMessage').textContent = 'Failed to create account. Please try again.';
                document.getElementById('otpMessage').style.color = '#e74c3c';
                if (overlay) overlay.style.display = 'none';
            }
        }

        async function resendOTP() {
            const btn = document.getElementById('resendOtpBtn');
            btn.classList.add('loading');
            btn.disabled = true;

            try {
                // Step 1: Request new OTP from server
                const response = await fetch('api/otp_actions.php?action=send-otp', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        email: registrationData.email,
                        otp_type: 'email_verification'
                    })
                });

                const result = await response.json();

                if (result.success && result.otp_code) {
                    // Step 2: Send OTP email using EmailJS
                    const emailSent = await sendOTPEmailJS(result.email, result.otp_code, result.otp_type);

                    if (emailSent) {
                        document.getElementById('otpMessage').textContent = 'OTP sent successfully!';
                        document.getElementById('otpMessage').style.color = '#27ae60';
                        remainingTime = 600;
                        startCountdown();
                    } else {
                        document.getElementById('otpMessage').textContent = 'Failed to send OTP email. Please try again.';
                        document.getElementById('otpMessage').style.color = '#e74c3c';
                    }
                } else {
                    document.getElementById('otpMessage').textContent = result.message;
                    document.getElementById('otpMessage').style.color = '#e74c3c';
                }
            } catch (error) {
                console.error('Resend OTP Error:', error);
                document.getElementById('otpMessage').textContent = 'Failed to resend OTP. Please try again.';
                document.getElementById('otpMessage').style.color = '#e74c3c';
            } finally {
                btn.classList.remove('loading');
                btn.disabled = false;
            }
        }

        // EmailJS function to send OTP email
        async function sendOTPEmailJS(email, otpCode, otpType) {
            if (!window.CONFIG || !window.CONFIG.EMAILJS) {
                console.error('EmailJS configuration not found');
                return false;
            }

            const { SERVICE_ID, FALLBACK_SERVICE_ID, TEMPLATE_ID, PUBLIC_KEY } = window.CONFIG.EMAILJS;
            console.log('Primary Service ID:', SERVICE_ID);
            console.log('Fallback Service ID:', FALLBACK_SERVICE_ID);

            if (!SERVICE_ID || !TEMPLATE_ID || !PUBLIC_KEY ||
                SERVICE_ID === 'YOUR_EMAILJS_SERVICE_ID' ||
                TEMPLATE_ID === 'YOUR_EMAILJS_TEMPLATE_ID' ||
                PUBLIC_KEY === 'YOUR_EMAILJS_PUBLIC_KEY') {
                console.error('EmailJS not configured. Please add your EmailJS credentials to config.js');
                // Fallback: simulate success for testing
                alert('EmailJS not configured. OTP for ' + email + ': ' + otpCode);
                return true;
            }

            // Determine email subject and message based on OTP type
            let subject, messageTitle, messageBody;
            switch (otpType) {
                case 'email_verification':
                    subject = 'Verify Your Email - Phambili Ma-Africa';
                    messageTitle = 'Verify Your Email Address';
                    messageBody = 'Thank you for registering with Phambili Ma-Africa! Please use the code below to verify your email address.';
                    break;
                case 'password_reset':
                    subject = 'Password Reset Code - Phambili Ma-Africa';
                    messageTitle = 'Reset Your Password';
                    messageBody = 'We received a request to reset your password. Use the code below to proceed.';
                    break;
                default:
                    subject = 'Verification Code - Phambili Ma-Africa';
                    messageTitle = 'Verification Code';
                    messageBody = 'Your verification code is below.';
            }

            const templateParams = {
                to_email: email,
                to_name: email.split('@')[0],
                from_name: 'Phambili Ma-Africa',
                subject: subject,
                message_title: messageTitle,
                message_body: messageBody,
                otp_code: otpCode,
                expiry_time: '10 minutes',
                reply_to: 'noreply@phambilimaafrica.co.za'
            };

            // Try primary service first, then fallback
            const services = [SERVICE_ID];
            if (window.CONFIG.EMAILJS.FALLBACK_SERVICE_ID) {
                services.push(window.CONFIG.EMAILJS.FALLBACK_SERVICE_ID);
            }

            for (let i = 0; i < services.length; i++) {
                const serviceId = services[i];
                try {
                    console.log(`Trying EmailJS service: ${serviceId}`);
                    const result = await emailjs.send(serviceId, TEMPLATE_ID, templateParams);
                    console.log('EmailJS Success:', result);
                    return true;
                } catch (error) {
                    console.error(`EmailJS Error with ${serviceId}:`, error);
                    // If this is the last service, return false
                    if (i === services.length - 1) {
                        return false;
                    }
                    // Otherwise continue to try fallback
                }
            }
            return false;
        }

    </script>

    <!-- EmailJS SDK for sending OTP emails -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
    <script src="js/config.js?v=<?php echo time(); ?>"></script>
    <script src="js/form-validation.js"></script>
    <script>
        // Initialize EmailJS with your public key
        (function() {
            if (window.CONFIG && window.CONFIG.EMAILJS && window.CONFIG.EMAILJS.PUBLIC_KEY) {
                emailjs.init(window.CONFIG.EMAILJS.PUBLIC_KEY);
                console.log('EmailJS Config:', window.CONFIG.EMAILJS);
            }
        })();
    </script>
</body>

</html>
