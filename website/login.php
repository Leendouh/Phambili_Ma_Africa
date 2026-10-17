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
    <div class="auth-container">
        <div class="auth-card">

            <!-- Tabs -->
            <div class="auth-tabs">
                <div class="auth-tab active" data-tab="login">Login</div>
                <div class="auth-tab" data-tab="register">Register</div>
            </div>

            <!-- Login Form -->
            <div id="login" class="auth-tab-content active">

                <form id="loginForm" class="auth-form">
                    <div class="auth-form-group">
                        <label for="login-email" class="auth-label">Email Address</label>
                        <input type="email" id="login-email" name="email" class="auth-input" placeholder="your@email.com" required>
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
                    <!-- Forgot password + lockout countdown -->
                    <div class="auth-forgot-row">
                        <a href="password_reset.php" class="auth-forgot">Forgot password?</a>
                        <span id="rateLimitCountdown" class="rate-limit-countdown" style="display:none;">
                            <i class="fas fa-lock"></i> <span id="rateLimitTime">5:00</span>
                        </span>
                    </div>
                    <button type="submit" class="auth-btn" id="loginBtn">
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

                <!-- Step 2: Email Verification Notice -->
                <div id="registerStep2" class="auth-step" style="display: none;">
                    <div class="auth-form">
                        <p style="text-align: center; margin-bottom: 1rem;">
                            <i class="fas fa-envelope-circle-check" style="font-size: 3rem; color: #27ae60;"></i>
                        </p>
                        <p style="text-align: center; color: #2c3e50; font-weight: 600; margin-bottom: 0.5rem;">
                            Verify your email address
                        </p>
                        <p style="text-align: center; color: #7f8c8d; margin-bottom: 1.5rem;">
                            We've sent a verification link to <strong id="verifyEmailAddress"></strong>.
                            Please check your inbox (and spam folder), click the link, then log in.
                        </p>
                        <div id="verifyMessage" class="otp-message"></div>
                        <button class="otp-verify-btn" id="resendVerifyBtn">Resend Email</button>
                        <button class="auth-btn" id="goToLoginBtn" style="margin-top: 0.5rem;">Go to Login</button>

                        <!-- Processing Overlay -->
                        <div id="registerStep2Overlay" class="form-processing-overlay" style="display: none;">
                            <div class="processing-content">
                                <div class="processing-spinner"></div>
                                <p class="processing-text">Sending verification email...</p>
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

    <script src="js/config.js"></script>
    <script>
        // Simple tab switching functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.auth-tab');
            const tabContents = document.querySelectorAll('.auth-tab-content');

            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const targetTab = this.getAttribute('data-tab');

                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));

                    this.classList.add('active');
                    document.getElementById(targetTab).classList.add('active');
                });
            });

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
                    this.value = this.value.replace(/[^a-zA-Z\s\-']/g, '');

                    if (this.value.match(/^[a-zA-Z\s\-']+$/)) {
                        this.classList.remove('error');
                    }
                });
            }

            // Clear errors when user starts typing
            document.querySelectorAll('input').forEach(input => {
                input.addEventListener('input', function() {
                    this.classList.remove('error');
                });
            });
        });

        // Error display helper (shared by module + inline code)
        function showError(message) {
            document.querySelectorAll('.validation-error').forEach(error => error.remove());

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

            const activeTab = document.querySelector('.auth-tab-content.active');
            if (activeTab) {
                activeTab.insertBefore(errorDiv, activeTab.firstChild);
                setTimeout(() => errorDiv.remove(), 5000);
            }
        }

        function showSuccess(message) {
            document.querySelectorAll('.validation-error').forEach(error => error.remove());

            const div = document.createElement('div');
            div.className = 'validation-error';
            div.style.cssText = `
                background: #27ae60;
                color: white;
                padding: 1rem;
                border-radius: 8px;
                margin-bottom: 1rem;
                text-align: center;
                animation: fadeIn 0.3s ease-out;
            `;
            div.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;

            const activeTab = document.querySelector('.auth-tab-content.active');
            if (activeTab) {
                activeTab.insertBefore(div, activeTab.firstChild);
            }
        }

        function goToRegisterStep(step) {
            document.querySelectorAll('#register .auth-step').forEach(s => s.style.display = 'none');
            document.getElementById('registerStep' + step).style.display = 'block';
        }
    </script>

    <script type="module">
        import {
            pmaSignIn,
            pmaRegister,
            redirectForRole,
            redirectIfSignedIn,
            whenAuthReady,
            authErrorMessage,
        } from './js/firebase/auth.js';
        import {
            sendEmailVerification,
            signOut,
            updatePassword,
        } from 'https://www.gstatic.com/firebasejs/13.0.0/firebase-auth.js';
        import { doc, getDoc, updateDoc } from 'https://www.gstatic.com/firebasejs/13.0.0/firebase-firestore.js';
        import { auth, db } from './js/firebase/app.js';

        // If already signed in, leave this page (honouring ?redirect=).
        // Preserve the redirect target for after login.
        const params = new URLSearchParams(window.location.search);
        const redirectParam = params.get('redirect');
        if (redirectParam) {
            let target = redirectParam;
            if (params.get('book')) target += '?book=' + encodeURIComponent(params.get('book'));
            sessionStorage.setItem('pma_redirect_after_login', target);
        }
        /* -------- First-login gate (staff/employee generated passwords) ----- */
        let gateProfile = null;

        function showFirstLoginGate(profile) {
            gateProfile = profile || gateProfile;
            const gate = document.getElementById('firstLoginGate');
            gate.style.display = 'flex';
        }

        document.getElementById('firstLoginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const msg = document.getElementById('firstLoginMessage');
            const pw = document.getElementById('fl-new-password').value;
            const confirm = document.getElementById('fl-confirm-password').value;
            const fail = (t) => {
                msg.textContent = t;
                msg.className = 'otp-message error';
                msg.style.display = 'block';
            };
            msg.style.display = 'none';

            if (pw !== confirm) {
                fail('Passwords do not match.');
                return;
            }
            if (pw.length < 8 || !/[A-Z]/.test(pw) || !/[a-z]/.test(pw) ||
                !/[0-9]/.test(pw) || !/[!@#$%^&*]/.test(pw)) {
                fail('Password must be 8+ characters and include an uppercase letter, a lowercase letter, a number and a special character (!@#$%^&*).');
                return;
            }

            const btn = document.getElementById('firstLoginBtn');
            btn.disabled = true;
            try {
                await updatePassword(auth.currentUser, pw);
                await updateDoc(doc(db, 'users', auth.currentUser.uid), { firstLogin: false });
                // Clear the admins doc flag too when this is an admin account.
                await getDoc(doc(db, 'admins', auth.currentUser.uid)).then((a) =>
                    a.exists() ? updateDoc(doc(db, 'admins', auth.currentUser.uid), { firstLogin: false }) : null
                ).catch(() => null);
                if (gateProfile) gateProfile.firstLogin = false;
                redirectForRole(gateProfile, 'index.php');
            } catch (error) {
                fail(authErrorMessage(error));
                btn.disabled = false;
            }
        });

        // If already signed in, leave this page (honouring ?redirect=) —
        // unless the account is still holding a generated password, in
        // which case the reset gate takes over this page instead.
        const { user: readyUser, profile: readyProfile } = await whenAuthReady();
        if (readyUser && readyProfile?.firstLogin) {
            showFirstLoginGate(readyProfile);
        } else {
            redirectIfSignedIn('index.php');
        }

        /* ---------------- Login lockout (6 attempts → 5 min) -------------- */
        const MAX_ATTEMPTS = 6;
        const LOCK_MS = 5 * 60 * 1000;
        const ATTEMPTS_KEY = 'pma_login_attempts';
        const LOCK_KEY = 'pma_login_lock_until';
        let lockTimer = null;

        function lockEndsAt() {
            return parseInt(localStorage.getItem(LOCK_KEY) || '0', 10);
        }

        function clearLockout() {
            localStorage.removeItem(LOCK_KEY);
            localStorage.removeItem(ATTEMPTS_KEY);
            if (lockTimer) { clearInterval(lockTimer); lockTimer = null; }
            document.getElementById('rateLimitCountdown').style.display = 'none';
            ['login-email', 'login-password', 'remember', 'loginBtn'].forEach((id) => {
                const el = document.getElementById(id);
                if (el) { el.disabled = false; el.classList.remove('locked-input'); }
            });
        }

        function startLockout(untilTs) {
            const remaining = untilTs - Date.now();
            if (remaining <= 0) { clearLockout(); return; }
            ['login-email', 'login-password', 'remember', 'loginBtn'].forEach((id) => {
                const el = document.getElementById(id);
                if (el) { el.disabled = true; el.classList.add('locked-input'); }
            });
            const pill = document.getElementById('rateLimitCountdown');
            const timeEl = document.getElementById('rateLimitTime');
            const tick = () => {
                const left = untilTs - Date.now();
                if (left <= 0) {
                    clearLockout();
                    document.querySelectorAll('.validation-error').forEach(el => el.remove());
                    return;
                }
                const m = Math.floor(left / 60000);
                const s = String(Math.floor((left % 60000) / 1000)).padStart(2, '0');
                timeEl.textContent = `${m}:${s}`;
                pill.style.display = 'inline-flex';
            };
            tick();
            lockTimer = setInterval(tick, 1000);
            showError(`Too many failed attempts. Login locked — try again in ${Math.ceil(remaining / 60000)} minutes.`);
        }

        function recordFailedAttempt() {
            if (Date.now() < lockEndsAt()) return; // already locked
            document.getElementById('login-password').value = '';
            const attempts = parseInt(localStorage.getItem(ATTEMPTS_KEY) || '0', 10) + 1;
            localStorage.setItem(ATTEMPTS_KEY, String(attempts));
            const left = MAX_ATTEMPTS - attempts;
            if (left <= 0) {
                localStorage.setItem(LOCK_KEY, String(Date.now() + LOCK_MS));
                startLockout(Date.now() + LOCK_MS);
            } else {
                showError(`Incorrect email or password. ${left} attempt${left === 1 ? '' : 's'} left before a 5-minute lockout.`);
            }
        }

        // Resume an active lockout after a page refresh.
        if (lockEndsAt() > Date.now()) startLockout(lockEndsAt());

        /* ------------------------------- Login ------------------------------ */
        const loginForm = document.getElementById('loginForm');
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const email = document.getElementById('login-email').value.trim();
            const password = document.getElementById('login-password').value;
            const remember = document.getElementById('remember').checked;

            if (!email || !password) {
                showError('Please fill in all fields');
                return;
            }

            const btn = document.getElementById('loginBtn');
            const overlay = document.getElementById('loginOverlay');
            btn.classList.add('loading');
            btn.disabled = true;
            if (overlay) overlay.style.display = 'flex';

            try {
                const { user, profile } = await pmaSignIn(email, password, remember);
                localStorage.removeItem(ATTEMPTS_KEY);

                // Match the old OTP-gated flow: unverified customers must
                // verify their email before using the site. Staff are exempt.
                const role = (profile?.role || 'customer').toLowerCase();
                if (role === 'customer' && !user.emailVerified) {
                    await signOut(auth);
                    if (overlay) overlay.style.display = 'none';
                    showError('Please verify your email address before logging in. Check your inbox for the verification link.');
                    return;
                }

                // Staff/employee accounts created by an admin must set their
                // own password before anything else.
                if (profile?.firstLogin) {
                    if (overlay) overlay.style.display = 'none';
                    showFirstLoginGate(profile);
                    return;
                }

                redirectForRole(profile, 'index.php');
            } catch (error) {
                console.error('Login error:', error);
                if (overlay) overlay.style.display = 'none';
                const code = error && error.code ? error.code : '';
                if (code === 'auth/too-many-requests') {
                    // Firebase's own rate limit kicked in — match it.
                    document.getElementById('login-password').value = '';
                    localStorage.setItem(LOCK_KEY, String(Date.now() + LOCK_MS));
                    startLockout(Date.now() + LOCK_MS);
                } else if (['auth/invalid-credential', 'auth/wrong-password',
                            'auth/user-not-found', 'auth/invalid-login-credentials'].includes(code)) {
                    recordFailedAttempt();
                } else {
                    showError(authErrorMessage(error));
                }
            } finally {
                btn.classList.remove('loading');
                if (Date.now() >= lockEndsAt()) btn.disabled = false;
            }
        });

        /* ------------------------------ Register ----------------------------- */
        let pendingUser = null; // Firebase user awaiting email verification

        const registerForm = document.getElementById('registerForm');
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const fullName = document.getElementById('register-fullname').value.trim();
            const email = document.getElementById('register-email').value.trim();
            const password = document.getElementById('register-password').value;
            const confirmPassword = document.getElementById('register-confirm-password').value;

            const errors = [];
            if (!fullName.match(/^[a-zA-Z\s\-']+$/) || fullName.length < 2) {
                errors.push('Full name must be at least 2 characters and contain only letters');
            }
            if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                errors.push('Please enter a valid email address');
            }
            if (password.length < 8) {
                errors.push('Password must be at least 8 characters long');
            }
            if (password !== confirmPassword) {
                errors.push('Passwords do not match');
            }
            if (errors.length) {
                showError(errors.join('<br>'));
                return;
            }

            const btn = document.getElementById('registerBtn');
            const overlay = document.getElementById('registerStep1Overlay');
            btn.classList.add('loading');
            btn.disabled = true;
            if (overlay) overlay.style.display = 'flex';

            try {
                pendingUser = await pmaRegister({ fullName, email, password });
                document.getElementById('verifyEmailAddress').textContent = email;
                if (overlay) overlay.style.display = 'none';
                goToRegisterStep(2);
            } catch (error) {
                console.error('Register error:', error);
                if (overlay) overlay.style.display = 'none';
                showError(authErrorMessage(error));
            } finally {
                btn.classList.remove('loading');
                btn.disabled = false;
            }
        });

        // Resend verification email
        document.getElementById('resendVerifyBtn').addEventListener('click', async () => {
            const overlay = document.getElementById('registerStep2Overlay');
            const msg = document.getElementById('verifyMessage');
            if (overlay) overlay.style.display = 'flex';
            try {
                if (pendingUser) {
                    await sendEmailVerification(pendingUser);
                }
                msg.textContent = 'Verification email sent! Check your inbox.';
                msg.style.color = '#27ae60';
            } catch (error) {
                msg.textContent = authErrorMessage(error);
                msg.style.color = '#e74c3c';
            } finally {
                if (overlay) overlay.style.display = 'none';
            }
        });

        // Go to login tab (sign out the unverified pending account first)
        document.getElementById('goToLoginBtn').addEventListener('click', async () => {
            try {
                await signOut(auth);
            } catch (e) { /* ignore */ }
            pendingUser = null;
            document.querySelector('.auth-tab[data-tab="login"]').click();
            showSuccess('Account created! Please verify your email, then log in.');
        });
    </script>

    <!-- First-Login Forced Password Reset (staff/employee accounts created
         by an admin — mirrors the admin dashboard overlay) -->
    <div id="firstLoginGate" style="display:none; position:fixed; inset:0; z-index:9999;
        background:rgba(10,37,64,0.75); align-items:center; justify-content:center; padding:16px;">
        <div class="auth-card" style="max-width:420px; width:100%;">
            <div class="auth-header" style="text-align:center; margin-bottom:18px;">
                <h2 style="margin:0 0 8px;">Set Your Password</h2>
                <p style="margin:0; font-size:14px; color:#666;">
                    An administrator created this account with a temporary password.
                    Choose your own password to continue.
                </p>
            </div>
            <div id="firstLoginMessage" class="otp-message" style="display:none;"></div>
            <form id="firstLoginForm" class="auth-form">
                <div class="auth-form-group">
                    <label for="fl-new-password" class="auth-label">New Password</label>
                    <div class="input-with-icon">
                        <input type="password" id="fl-new-password" class="auth-input"
                            placeholder="8+ chars, upper, lower, number, special" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('fl-new-password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="auth-form-group">
                    <label for="fl-confirm-password" class="auth-label">Confirm Password</label>
                    <div class="input-with-icon">
                        <input type="password" id="fl-confirm-password" class="auth-input"
                            placeholder="Repeat your password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('fl-confirm-password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" id="firstLoginBtn" class="auth-btn">Save Password &amp; Continue</button>
            </form>
        </div>
    </div>
</body>

</html>
