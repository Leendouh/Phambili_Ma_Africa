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
                <p>Enter your email to receive a reset link</p>
            </div>

            <!-- Step 1: Enter Email -->
            <div id="step1" class="auth-step active">
                <div id="step1Message" class="otp-message"></div>
                <form id="emailForm" class="auth-form">
                    <div class="auth-form-group">
                        <label for="reset-email" class="auth-label">Email Address</label>
                        <input type="email" id="reset-email" name="email" class="auth-input" placeholder="your@email.com" required>
                    </div>
                    <button type="submit" class="auth-btn" id="sendResetBtn">
                        <span>Send Reset Link</span>
                        <div class="spinner"></div>
                    </button>

                    <!-- Enhanced Processing Overlay -->
                    <div id="step1Overlay" class="form-processing-overlay" style="display: none;">
                        <div class="processing-content">
                            <div class="processing-spinner"></div>
                            <p class="processing-text">Sending reset link...</p>
                            <p class="processing-subtext">Please wait a moment</p>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Step 2: Check your email -->
            <div id="step2" class="auth-step" style="display: none;">
                <div class="auth-form">
                    <p style="text-align: center; margin-bottom: 1rem;">
                        <i class="fas fa-envelope-circle-check" style="font-size: 3rem; color: #27ae60;"></i>
                    </p>
                    <p style="text-align: center; color: #2c3e50; font-weight: 600; margin-bottom: 0.5rem;">
                        Check your email
                    </p>
                    <p style="text-align: center; color: #7f8c8d; margin-bottom: 1.5rem;">
                        We've sent a password reset link to <strong id="resetEmailAddress"></strong>.
                        Click the link in the email to choose a new password.
                    </p>
                    <div id="step2Message" class="otp-message"></div>
                    <button class="otp-verify-btn" id="resendResetBtn">Resend Email</button>
                    <a href="login.php" class="auth-btn" style="display: block; text-align: center; text-decoration: none; margin-top: 0.5rem;">Back to Login</a>

                    <!-- Enhanced Processing Overlay -->
                    <div id="step2Overlay" class="form-processing-overlay" style="display: none;">
                        <div class="processing-content">
                            <div class="processing-spinner"></div>
                            <p class="processing-text">Resending email...</p>
                            <p class="processing-subtext">Please wait a moment</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="auth-footer">
                <a href="login.php" style="color: var(--primary-dark); text-decoration: none;">Back to Login</a>
            </div>
        </div>
    </div>

    <script src="js/config.js"></script>
    <script>
        function goToStep(step) {
            document.querySelectorAll('.auth-step').forEach(s => s.style.display = 'none');
            document.getElementById('step' + step).style.display = 'block';
        }
    </script>

    <script type="module">
        import { pmaSendPasswordReset, redirectIfSignedIn, authErrorMessage } from './js/firebase/auth.js';

        redirectIfSignedIn('index.php');

        let resetEmail = null;

        const emailForm = document.getElementById('emailForm');
        emailForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const email = document.getElementById('reset-email').value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!email || !emailRegex.test(email)) {
                document.getElementById('step1Message').textContent = 'Please enter a valid email address';
                document.getElementById('step1Message').style.color = '#e74c3c';
                return;
            }

            resetEmail = email;

            const btn = document.getElementById('sendResetBtn');
            const overlay = document.getElementById('step1Overlay');
            btn.classList.add('loading');
            btn.disabled = true;
            if (overlay) overlay.style.display = 'flex';
            document.getElementById('step1Message').textContent = '';

            try {
                await pmaSendPasswordReset(email);
                document.getElementById('resetEmailAddress').textContent = email;
                goToStep(2);
            } catch (error) {
                // Don't reveal whether the account exists — show success anyway
                // unless it's clearly a client-side problem.
                if (error.code === 'auth/invalid-email' || error.code === 'auth/too-many-requests') {
                    document.getElementById('step1Message').textContent = authErrorMessage(error);
                    document.getElementById('step1Message').style.color = '#e74c3c';
                } else {
                    document.getElementById('resetEmailAddress').textContent = email;
                    goToStep(2);
                }
            } finally {
                btn.classList.remove('loading');
                btn.disabled = false;
                if (overlay) overlay.style.display = 'none';
            }
        });

        document.getElementById('resendResetBtn').addEventListener('click', async () => {
            const overlay = document.getElementById('step2Overlay');
            const msg = document.getElementById('step2Message');
            if (overlay) overlay.style.display = 'flex';
            try {
                await pmaSendPasswordReset(resetEmail);
                msg.textContent = 'Reset email sent again! Check your inbox.';
                msg.style.color = '#27ae60';
            } catch (error) {
                msg.textContent = authErrorMessage(error);
                msg.style.color = '#e74c3c';
            } finally {
                if (overlay) overlay.style.display = 'none';
            }
        });
    </script>
</body>

</html>
