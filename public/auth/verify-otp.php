<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification - Dental Clinic Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #054A91 0%, #3E7CB1 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .otp-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .logo {
            font-size: 3rem;
            margin-bottom: 20px;
        }

        h1 {
            color: #054A91;
            margin-bottom: 30px;
            font-size: 24px;
            font-weight: 600;
        }

        .description {
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
            line-height: 1.6;
        }

        .email-display {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 30px;
            font-weight: 600;
            color: #054A91;
            word-break: break-all;
        }

        .form-group {
            margin-bottom: 25px;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        .otp-input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 24px;
            text-align: center;
            letter-spacing: 8px;
            font-family: monospace;
            font-weight: bold;
            color: #054A91;
            transition: border-color 0.3s ease;
        }

        .otp-input:focus {
            outline: none;
            border-color: #054A91;
            box-shadow: 0 0 0 3px rgba(5, 74, 145, 0.1);
        }

        .otp-input.valid {
            border-color: #28a745;
            background-color: #f8fff9;
        }

        .otp-input.invalid {
            border-color: #dc3545;
            background-color: #fff8f8;
        }

        .verify-btn {
            width: 100%;
            background: linear-gradient(135deg, #054A91, #3E7CB1);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .verify-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(5, 74, 145, 0.3);
        }

        .verify-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .message.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .message.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .message.info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }

        .help-text {
            font-size: 14px;
            color: #666;
            margin-top: 20px;
            line-height: 1.5;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #054A91;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .resend-link {
            color: #054A91;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
        }

        .resend-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .otp-container {
                padding: 30px 20px;
            }
            
            .otp-input {
                font-size: 20px;
                letter-spacing: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="otp-container">
        <div class="logo">🦷</div>
        <h1>OTP Verification</h1>
        
        <div id="message" style="display: none;"></div>

        <div class="description">
            Enter the 6-digit OTP sent to your email address to complete your login.
        </div>

        <div class="email-display">
            📧 <span id="userEmail"><?php echo htmlspecialchars($_GET['email'] ?? ''); ?></span>
        </div>

        <form id="otpForm">
            <input type="hidden" id="email" value="<?php echo htmlspecialchars($_GET['email'] ?? ''); ?>">
            
            <div class="form-group">
                <label for="otp">Enter 6-Digit OTP</label>
                <input 
                    type="text" 
                    id="otp" 
                    name="otp" 
                    class="otp-input"
                    placeholder="000000"
                    maxlength="6"
                    pattern="\d{6}"
                    required
                    autofocus
                    autocomplete="off"
                >
            </div>

            <button type="submit" class="verify-btn" id="verifyBtn">
                🔐 Verify OTP & Login
            </button>
        </form>

        <div class="help-text">
            <p><strong>OTP not working?</strong></p>
            <ul style="text-align: left; margin: 10px 0;">
                <li>Make sure you entered all 6 digits correctly</li>
                <li>Check your spam/junk folder</li>
                <li>The OTP expires in 5 minutes</li>
            </ul>
            <p>
                Need a new OTP? 
                <a href="#" class="resend-link" id="resendLink">Request New OTP</a>
            </p>
        </div>

        <a href="login.php" class="back-link">← Back to Login</a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const otpForm = document.getElementById('otpForm');
            const otpInput = document.getElementById('otp');
            const verifyBtn = document.getElementById('verifyBtn');
            const messageDiv = document.getElementById('message');
            const resendLink = document.getElementById('resendLink');
            const emailInput = document.getElementById('email');

            // Auto-format OTP input
            otpInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 6) {
                    value = value.substring(0, 6);
                }
                e.target.value = value;

                // Remove validation classes
                e.target.classList.remove('valid', 'invalid');

                // Auto-submit when 6 digits entered
                if (value.length === 6) {
                    setTimeout(() => {
                        verifyOTP();
                    }, 500);
                }
            });

            // Form submission
            otpForm.addEventListener('submit', function(e) {
                e.preventDefault();
                verifyOTP();
            });

            // Resend OTP
            resendLink.addEventListener('click', function(e) {
                e.preventDefault();
                resendOTP();
            });

            async function verifyOTP() {
                const otp = otpInput.value.trim();
                const email = emailInput.value;

                if (otp.length !== 6) {
                    showMessage('Please enter a valid 6-digit OTP', 'error');
                    return;
                }

                // Set loading state
                verifyBtn.disabled = true;
                verifyBtn.textContent = 'Verifying...';

                try {
                    const formData = new FormData();
                    formData.append('email', email);
                    formData.append('otp', otp);

                    const response = await fetch('../api/auth.php?action=verifyLoginOTP', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        showMessage('✅ OTP verified! Redirecting to dashboard...', 'success');
                        otpInput.classList.add('valid');
                        verifyBtn.textContent = '✅ Verified!';

                        setTimeout(() => {
                            if (data.redirect) {
                                window.location.href = data.redirect;
                            } else {
                                window.location.href = '../dashboard/clinic-listing.php';
                            }
                        }, 1500);

                    } else {
                        showMessage(data.message || 'Invalid OTP. Please try again.', 'error');
                        otpInput.classList.add('invalid');
                        otpInput.value = '';
                        otpInput.focus();

                        // Reset button
                        verifyBtn.disabled = false;
                        verifyBtn.textContent = '🔐 Verify OTP & Login';

                        // Remove invalid class after 3 seconds
                        setTimeout(() => {
                            otpInput.classList.remove('invalid');
                        }, 3000);
                    }

                } catch (error) {
                    console.error('OTP verification error:', error);
                    showMessage('Network error. Please try again.', 'error');

                    // Reset button
                    verifyBtn.disabled = false;
                    verifyBtn.textContent = '🔐 Verify OTP & Login';
                }
            }

            async function resendOTP() {
                const email = emailInput.value;
                
                if (!email) {
                    showMessage('Email not found. Please go back to login.', 'error');
                    return;
                }

                // Set loading state
                resendLink.textContent = 'Sending...';
                resendLink.style.pointerEvents = 'none';

                try {
                    showMessage('📧 Sending new OTP...', 'info');

                    // Redirect to login page to trigger new OTP
                    window.location.href = `login.php?resend_otp=1&email=${encodeURIComponent(email)}`;

                } catch (error) {
                    console.error('Resend OTP error:', error);
                    showMessage('Failed to resend OTP. Please try again.', 'error');

                    // Reset link
                    resendLink.textContent = 'Request New OTP';
                    resendLink.style.pointerEvents = 'auto';
                }
            }

            function showMessage(text, type) {
                messageDiv.textContent = text;
                messageDiv.className = `message ${type}`;
                messageDiv.style.display = 'block';

                // Auto-hide after 5 seconds for non-success messages
                if (type !== 'success') {
                    setTimeout(() => {
                        messageDiv.style.display = 'none';
                    }, 5000);
                }
            }

            // Check if email is provided
            if (!emailInput.value) {
                showMessage('Email parameter missing. Please go back to login.', 'error');
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 3000);
            }
        });
    </script>
</body>
</html>