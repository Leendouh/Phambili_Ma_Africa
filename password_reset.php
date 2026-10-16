<?php
require_once 'includes/config_session.inc.php';

// If user is already logged in, redirect to appropriate page
if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reset Password | Phambili Ma-Africa</title>
    <link rel="icon" type="image/png" sizes="32x32" href="images/PM-Logo.jpg">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="css/login.css" />
    <link rel="stylesheet" href="css/social-media.css" />

</head>

<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h2>Reset Password</h2>
                <p>Enter your email to receive a verification code</p>
            </div>

            <!-- Step 1: Enter Email -->
            <div id="step1" class="auth-step active">
                <div id="step1Message" class="otp-message"></div>
                <form id="emailForm" class="auth-form">
                    <div class="auth-form-group">
                        <label for="reset-email" class="auth-label">Email Address</label>
                        <input type="email" id="reset-email" name="email" class="auth-input" placeholder="your@email.com" required>
                    </div>
                    <button type="submit" class="auth-btn" id="sendOtpBtn">
                        <span>Send Verification Code</span>
                        <div class="spinner"></div>
                    </button>

                    <!-- Enhanced Processing Overlay -->
                    <div id="step1Overlay" class="form-processing-overlay" style="display: none;">
                        <div class="processing-content">
                            <div class="processing-spinner"></div>
                            <p class="processing-text">Checking your email...</p>
                            <p class="processing-subtext">Please wait a moment</p>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Step 2: Verify OTP -->
            <div id="step2" class="auth-step" style="display: none;">
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
                    <button class="auth-btn" onclick="goToStep(1)" style="background: transparent; color: #7f8c8d; border: 1px solid #e0e6ed; margin-top: 0.5rem;">Back</button>

                    <!-- Enhanced Processing Overlay -->
                    <div id="step2Overlay" class="form-processing-overlay" style="display: none;">
                        <div class="processing-content">
                            <div class="processing-spinner"></div>
                            <p class="processing-text">Verifying your code...</p>
                            <p class="processing-subtext">Please wait a moment</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 3: New Password -->
            <div id="step3" class="auth-step" style="display: none;">
                <form id="passwordForm" class="auth-form">
                    <div class="auth-form-group">
                        <label for="new-password" class="auth-label">New Password</label>
                        <div class="input-with-icon">
                            <input type="password" id="new-password" name="newPassword" class="auth-input" placeholder="Create a strong password" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('new-password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <!-- Password Strength Bar -->
                        <div class="password-strength">
                            <div class="password-strength-fill" id="resetStrengthFill"></div>
                        </div>
                        <!-- Password Requirements -->
                        <div class="password-requirements-box">
                            <strong>Password must include:</strong>
                            <ul>
                                <li id="reset-req-len">At least 8 characters</li>
                                <li id="reset-req-upper">One uppercase letter</li>
                                <li id="reset-req-lower">One lowercase letter</li>
                                <li id="reset-req-number">One number</li>
                                <li id="reset-req-special">One special character (!@#$%^&*)</li>
                            </ul>
                        </div>
                    </div>
                    <div class="auth-form-group">
                        <label for="confirm-password" class="auth-label">Confirm Password</label>
                        <div class="input-with-icon">
                            <input type="password" id="confirm-password" name="confirmPassword" class="auth-input" placeholder="Re-enter your password" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm-password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="auth-btn" id="resetPasswordBtn">
                        <span>Reset Password</span>
                        <div class="spinner"></div>
                    </button>
                    <button type="button" class="auth-btn" onclick="goToStep(2)" style="background: transparent; color: #7f8c8d; border: 1px solid #e0e6ed; margin-top: 0.5rem;">Back</button>

                    <!-- Enhanced Processing Overlay -->
                    <div id="processingOverlay" class="form-processing-overlay" style="display: none;">
                        <div class="processing-content">
                            <div class="processing-spinner"></div>
                            <p class="processing-text">Resetting your password...</p>
                            <p class="processing-subtext">Please wait a moment</p>
                        </div>
                    </div>
                </form>
            </div>

            <div class="auth-footer">
                <a href="login.php" style="color: var(--primary-dark); text-decoration: none;">Back to Login</a>
            </div>
        </div>
    </div>

    <script>
        let resetEmail = null;
        let countdownInterval = null;
        let remainingTime = 600; // 10 minutes in seconds

        function goToStep(step) {
            // Stop countdown if leaving step 2
            if (countdownInterval) {
                clearInterval(countdownInterval);
                countdownInterval = null;
            }

            document.querySelectorAll('.auth-step').forEach(s => s.style.display = 'none');
            document.getElementById('step' + step).style.display = 'block';

            if (step === 2) {
                const otpInputs = document.querySelectorAll('.otp-input');
                otpInputs.forEach(input => input.value = '');
                otpInputs[0].focus();
                document.getElementById('otpMessage').textContent = '';

                // Start countdown timer
                remainingTime = 900; // 15 minutes
                startCountdown();
            }

            if (step === 1) {
                document.getElementById('step1Message').textContent = '';
            }
        }

        function startCountdown() {
            const countdownElement = document.getElementById('otpCountdown');
            if (!countdownElement) return;

            updateCountdownDisplay();

            countdownInterval = setInterval(function() {
                remainingTime--;
                updateCountdownDisplay();

                if (remainingTime <= 0) {
                    clearInterval(countdownInterval);
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
            countdownElement.textContent =
                (minutes < 10 ? '0' : '') + minutes + ':' +
                (seconds < 10 ? '0' : '') + seconds;
        }

        function showError(message) {
            const existingErrors = document.querySelectorAll('.validation-error');
            existingErrors.forEach(error => error.remove());

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

            const activeStep = document.querySelector('.auth-step[style*="block"]');
            if (activeStep) {
                activeStep.insertBefore(errorDiv, activeStep.firstChild);

                setTimeout(() => {
                    errorDiv.remove();
                }, 5000);
            }
        }

        // Global functions for onclick handlers
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
            const overlay = document.getElementById('step2Overlay');
            const otpFormInputs = document.getElementById('otpForm').querySelectorAll('input, button');

            btn.classList.add('loading');
            btn.disabled = true;
            otpFormInputs.forEach(el => el.disabled = true);
            if (overlay) overlay.style.display = 'flex';

            try {
                const response = await fetch('api/otp_actions.php?action=verify-otp', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        email: resetEmail,
                        otp_code: otpCode,
                        otp_type: 'password_reset'
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
                    return;
                }

                if (result.success) {
                    if (overlay) {
                        overlay.querySelector('.processing-text').textContent = 'Code verified!';
                        overlay.querySelector('.processing-subtext').textContent = 'Preparing your password reset...';
                        overlay.querySelector('.processing-spinner').style.borderTopColor = '#28a745';
                    }
                    setTimeout(() => {
                        goToStep(3);
                    }, 800);
                } else {
                    document.getElementById('otpMessage').textContent = result.message;
                    document.getElementById('otpMessage').style.color = '#e74c3c';
                }
            } catch (error) {
                console.error('Network or fetch error:', error);
                document.getElementById('otpMessage').textContent = 'Network error. Please try again.';
                document.getElementById('otpMessage').style.color = '#e74c3c';
            } finally {
                if (!btn.classList.contains('success')) {
                    btn.classList.remove('loading');
                    btn.disabled = false;
                    otpFormInputs.forEach(el => el.disabled = false);
                    if (overlay) overlay.style.display = 'none';
                }
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
                        email: resetEmail,
                        otp_type: 'password_reset'
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

        document.addEventListener('DOMContentLoaded', function() {
            // Step 1: Send OTP
            const emailForm = document.getElementById('emailForm');
            emailForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const email = document.getElementById('reset-email').value;
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                
                if (!email || !emailRegex.test(email)) {
                    document.getElementById('step1Message').textContent = 'Please enter a valid email address';
                    document.getElementById('step1Message').style.color = '#e74c3c';
                    return;
                }
                
                resetEmail = email;

                const btn = document.getElementById('sendOtpBtn');
                const overlay = document.getElementById('step1Overlay');
                const formInputs = emailForm.querySelectorAll('input, button');

                btn.classList.add('loading');
                btn.disabled = true;
                formInputs.forEach(el => el.disabled = true);
                if (overlay) overlay.style.display = 'flex';

                document.getElementById('step1Message').textContent = '';

                try {
                    // Step 1: Request OTP generation from server
                    const response = await fetch('api/otp_actions.php?action=send-otp', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            email: email,
                            otp_type: 'password_reset'
                        })
                    });

                    const result = await response.json();

                    console.log('OTP Response:', result); // Debug logging

                    if (result.success && result.otp_code) {
                        // Step 2: Send OTP email using EmailJS
                        const emailSent = await sendOTPEmailJS(result.email, result.otp_code, result.otp_type);

                        if (emailSent) {
                            if (overlay) {
                                overlay.querySelector('.processing-text').textContent = 'Code sent!';
                                overlay.querySelector('.processing-subtext').textContent = 'Redirecting to verification...';
                                overlay.querySelector('.processing-spinner').style.borderTopColor = '#28a745';
                            }
                            document.getElementById('step1Message').textContent = 'OTP sent successfully!';
                            document.getElementById('step1Message').style.color = '#27ae60';
                            setTimeout(() => {
                                goToStep(2);
                            }, 1200);
                        } else {
                            document.getElementById('step1Message').textContent = 'Failed to send OTP email. Please try again.';
                            document.getElementById('step1Message').style.color = '#e74c3c';
                        }
                    } else {
                        document.getElementById('step1Message').textContent = result.message;
                        document.getElementById('step1Message').style.color = '#e74c3c';
                    }
                } catch (error) {
                    console.error('Error sending OTP:', error);
                    document.getElementById('step1Message').textContent = 'Failed to send OTP. Please try again.';
                    document.getElementById('step1Message').style.color = '#e74c3c';
                } finally {
                    if (overlay) overlay.style.display = 'none';
                    btn.classList.remove('loading');
                    btn.disabled = false;
                    formInputs.forEach(el => el.disabled = false);
                }
            });

            // OTP Input Handling
            const otpInputs = document.querySelectorAll('.otp-input');
            otpInputs.forEach((input, index) => {
                input.addEventListener('input', function(e) {
                    this.value = this.value.replace(/[^0-9]/g, '');
                    if (this.value.length === 1 && index < otpInputs.length - 1) {
                        otpInputs[index + 1].focus();
                    }
                });
                
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && this.value === '' && index > 0) {
                        otpInputs[index - 1].focus();
                    }
                });
                
                input.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pastedData = e.clipboardData.getData('text').slice(0, 6);
                    pastedData.split('').forEach((char, i) => {
                        if (otpInputs[i] && /[0-9]/.test(char)) {
                            otpInputs[i].value = char;
                        }
                    });
                });
            });

            // Step 3: Reset Password
            const passwordForm = document.getElementById('passwordForm');
            passwordForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                const newPassword = document.getElementById('new-password').value;
                const confirmPassword = document.getElementById('confirm-password').value;

                if (newPassword.length < 8) {
                    showError('Password must be at least 8 characters long');
                    return;
                }

                if (newPassword !== confirmPassword) {
                    showError('Passwords do not match');
                    return;
                }

                const btn = document.getElementById('resetPasswordBtn');
                const overlay = document.getElementById('processingOverlay');
                const formInputs = passwordForm.querySelectorAll('input, button');

                // Show enhanced loading state
                btn.classList.add('loading');
                btn.disabled = true;
                formInputs.forEach(el => el.disabled = true);
                if (overlay) overlay.style.display = 'flex';

                try {
                    const response = await fetch('api/otp_actions.php?action=reset-password', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            email: resetEmail,
                            new_password: newPassword
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        // Success: update overlay text then redirect
                        if (overlay) {
                            overlay.querySelector('.processing-text').textContent = 'Password reset successful!';
                            overlay.querySelector('.processing-subtext').textContent = 'Redirecting you to login...';
                            overlay.querySelector('.processing-spinner').style.borderTopColor = '#28a745';
                        }
                        btn.classList.remove('loading');
                        btn.classList.add('success');
                        setTimeout(() => {
                            window.location.href = 'login.php';
                        }, 1500);
                    } else {
                        showError(result.message);
                    }
                } catch (error) {
                    showError('Failed to reset password. Please try again.');
                } finally {
                    if (!btn.classList.contains('success')) {
                        btn.classList.remove('loading');
                        btn.disabled = false;
                        formInputs.forEach(el => el.disabled = false);
                        if (overlay) overlay.style.display = 'none';
                    }
                }
            });

            // Password strength checker for reset password
            const resetPassword = document.getElementById('new-password');
            const resetStrengthFill = document.getElementById('resetStrengthFill');
            if (resetPassword && resetStrengthFill) {
                const requirements = {
                    len: document.getElementById('reset-req-len'),
                    upper: document.getElementById('reset-req-upper'),
                    lower: document.getElementById('reset-req-lower'),
                    number: document.getElementById('reset-req-number'),
                    special: document.getElementById('reset-req-special')
                };

                resetPassword.addEventListener('input', function() {
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

                    resetStrengthFill.className = 'password-strength-fill';
                    if (score <= 2) resetStrengthFill.classList.add('weak');
                    else if (score <= 4) resetStrengthFill.classList.add('medium');
                    else resetStrengthFill.classList.add('strong');
                });
            }

            // Password toggle
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

        });
    </script>

    <!-- EmailJS SDK for sending OTP emails -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
    <script src="js/config.js?v=<?php echo time(); ?>"></script>
    <script>
        // Initialize EmailJS with your public key
        (function() {
            if (window.CONFIG && window.CONFIG.EMAILJS && window.CONFIG.EMAILJS.PUBLIC_KEY) {
                emailjs.init(window.CONFIG.EMAILJS.PUBLIC_KEY);
                console.log('EmailJS Config:', window.CONFIG.EMAILJS);
            }
        })();

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
                reply_to: 'noreply@phambili-ma-africa.co.za'
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
</body>
</html>
