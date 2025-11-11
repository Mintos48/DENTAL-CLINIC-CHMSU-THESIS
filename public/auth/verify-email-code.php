<?php
require_once dirname(__DIR__, 2) . '/src/config/session.php';
require_once dirname(__DIR__, 2) . '/src/config/database.php';

// Load environment variables
$envFile = dirname(__DIR__, 2) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

// Get the email and code from URL parameters
$email = $_GET['email'] ?? '';
$codeFromUrl = $_GET['code'] ?? '';
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputCode = trim($_POST['verification_code'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if (empty($inputCode) || empty($email)) {
        $message = 'Please enter the 6-digit verification code.';
        $messageType = 'error';
    } else if (!preg_match('/^\d{6}$/', $inputCode)) {
        $message = 'Verification code must be exactly 6 digits.';
        $messageType = 'error';
    } else {
        try {
            $db = Database::getConnection();
            
            // Check if the code matches and hasn't expired
            $stmt = $db->prepare("
                SELECT id, verification_code_expires_at 
                FROM users 
                WHERE email = ? 
                AND verification_code = ? 
                AND email_verified = 0
            ");
            $stmt->bind_param('ss', $email, $inputCode);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if (!$user) {
                $message = 'Invalid verification code. Please check the code or request a new one.';
                $messageType = 'error';
            } else {
                // Check if code has expired
                $expiresAt = new DateTime($user['verification_code_expires_at']);
                $now = new DateTime();
                
                if ($now > $expiresAt) {
                    $message = 'Verification code has expired. Please request a new one.';
                    $messageType = 'error';
                } else {
                    // Verify the user and get full user data for login
                    $userDataStmt = $db->prepare("
                        SELECT id, name, email, role, branch_id
                        FROM users 
                        WHERE id = ?
                    ");
                    $userDataStmt->bind_param('i', $user['id']);
                    $userDataStmt->execute();
                    $userData = $userDataStmt->get_result()->fetch_assoc();
                    
                    // Update verification status
                    $updateStmt = $db->prepare("
                        UPDATE users 
                        SET email_verified = 1, 
                            verification_code = NULL, 
                            verification_code_expires_at = NULL,
                            updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $updateStmt->bind_param('i', $user['id']);
                    $updateStmt->execute();
                    
                    // Automatically log the user in after successful verification
                    require_once '../../src/config/session.php';
                    createSession($userData['id'], $userData['branch_id'] ?? null, $userData['role'], $userData['email'], $userData['name']);
                    
                    $message = 'Email verified successfully! Logging you in and redirecting to dashboard...';
                    $messageType = 'success';
                    
                    // Determine redirect URL based on role
                    $redirectUrl = '../dashboard/clinic-listing.php'; // Default for patients
                    if ($userData['role'] === 'staff') {
                        $redirectUrl = '../dashboard/staff-dashboard.php';
                    } elseif ($userData['role'] === 'admin') {
                        $redirectUrl = '../dashboard/admin-dashboard.php';
                    }
                    
                    // Redirect to dashboard after 2 seconds
                    header("refresh:2;url=" . $redirectUrl);
                }
            }
        } catch (Exception $e) {
            error_log("Verification error: " . $e->getMessage());
            $message = 'An error occurred during verification. Please try again.';
            $messageType = 'error';
        }
    }
}

// Check if we can get the verification code from URL parameters or database
$latestCode = $codeFromUrl; // Use code from URL first

if (empty($latestCode) && !empty($email)) {
    // Get the actual verification code from the database (most reliable source)
    try {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT verification_code, verification_code_expires_at 
            FROM users 
            WHERE email = ? 
            AND email_verified = 0
            AND verification_code IS NOT NULL
        ");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user && !empty($user['verification_code'])) {
            // Check if code hasn't expired
            $expiresAt = new DateTime($user['verification_code_expires_at']);
            $now = new DateTime();
            
            if ($now <= $expiresAt) {
                $latestCode = $user['verification_code'];
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching verification code: " . $e->getMessage());
    }
    
    // Fallback: find the latest verification code from the emails folder if database lookup failed
    if (empty($latestCode)) {
        $emailsDir = dirname(__DIR__, 2) . '/emails';
        if (is_dir($emailsDir)) {
            $emailFiles = glob($emailsDir . '/*.html');
            if (!empty($emailFiles)) {
                // Sort by modification time, get latest
                usort($emailFiles, function($a, $b) {
                    return filemtime($b) - filemtime($a);
                });
                
                // Look for email file matching this email address
                $safeEmail = str_replace(['@', '.'], ['_at_', '_'], $email);
                foreach ($emailFiles as $file) {
                    if (strpos(basename($file), $safeEmail) !== false) {
                        $content = file_get_contents($file);
                        
                        // Extract verification code from email content
                        if (preg_match('/<strong>(\d{6})<\/strong>/i', $content, $matches)) {
                            $latestCode = $matches[1];
                            break;
                        } elseif (preg_match('/(\d{6})/', $content, $matches)) {
                            $latestCode = $matches[1];
                            break;
                        }
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - Dental Clinic Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .verification-container {
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

        .code-input {
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

        .code-input:focus {
            outline: none;
            border-color: #054A91;
            box-shadow: 0 0 0 3px rgba(5, 74, 145, 0.1);
        }

        .code-input.valid {
            border-color: #28a745;
            background-color: #f8fff9;
        }

        .code-input.invalid {
            border-color: #dc3545;
            background-color: #fff8f8;
        }

        .validation-message {
            font-size: 14px;
            margin-top: 8px;
            padding: 8px;
            border-radius: 5px;
            text-align: center;
        }

        .validation-message.valid {
            color: #28a745;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
        }

        .validation-message.invalid {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
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

        .verify-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(5, 74, 145, 0.3);
        }

        .verify-btn:active {
            transform: translateY(0);
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

        .help-text {
            font-size: 14px;
            color: #666;
            margin-top: 20px;
            line-height: 1.5;
        }

        .resend-link {
            color: #054A91;
            text-decoration: none;
            font-weight: 600;
        }

        .resend-link:hover {
            text-decoration: underline;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #666;
            text-decoration: none;
            font-size: 14px;
        }

        .back-link:hover {
            color: #054A91;
        }

        .fallback-code {
            background: #fff3cd;
            border: 2px solid #ffeaa7;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            color: #856404;
        }

        .fallback-code strong {
            font-size: 24px;
            font-family: monospace;
            letter-spacing: 4px;
            display: block;
            margin-top: 10px;
            color: #054A91;
        }

        @media (max-width: 480px) {
            .verification-container {
                padding: 30px 20px;
            }
            
            .code-input {
                font-size: 20px;
                letter-spacing: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <div class="logo">🦷</div>
        <h1>Email Verification</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($messageType !== 'success'): ?>
            <div class="description">
                Enter the 6-digit verification code sent to your email address to complete your registration.
            </div>

            <?php if (!empty($email)): ?>
                <div class="email-display">
                    📧 <?php echo htmlspecialchars($email); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($latestCode)): ?>
                <div class="fallback-code">
                    <?php if (!empty($codeFromUrl)): ?>
                        <div>✉️ <strong>Verification code from email link detected</strong></div>
                        <div style="font-size: 14px; margin-top: 10px;">
                            Please enter the verification code to confirm it matches the one from your email.
                        </div>
                    <?php else: ?>
                        <div>🔍 <strong>Current verification code found</strong></div>
                        <div style="font-size: 14px; margin-top: 10px;">
                            This is your current valid verification code. Please enter it to verify your email.
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Hidden field to store the expected code for validation -->
                <input type="hidden" id="expected_code" value="<?php echo htmlspecialchars($latestCode); ?>">
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                
                <div class="form-group">
                    <label for="verification_code">Verification Code</label>
                    <input 
                        type="text" 
                        id="verification_code" 
                        name="verification_code" 
                        class="code-input"
                        placeholder="000000"
                        maxlength="6"
                        pattern="\d{6}"
                        required
                        autofocus
                        autocomplete="off"
                    >
                    <div id="validation_message" class="validation-message" style="display: none;"></div>
                </div>

                <button type="submit" class="verify-btn">
                    ✅ Verify Email Address
                </button>
            </form>

            <div class="help-text">
                <p><strong>Code not working?</strong></p>
                <ul style="text-align: left; margin: 10px 0;">
                    <li>Make sure you entered all 6 digits correctly</li>
                    <li>Use the most recent verification code from your email</li>
                    <li>Check your spam/junk folder for the latest email</li>
                    <li>The code expires in 15 minutes from when it was sent</li>
                    <li>If you have multiple emails, use the code from the newest one</li>
                </ul>
                <p>
                    Need a new code? 
                    <a href="<?php echo ($_ENV['BASE_URL'] ?? 'http://localhost:8080/dental-clinic-chmsu-thesis'); ?>/public/auth/login.php" class="resend-link">
                        Request New Code
                    </a>
                </p>
            </div>
        <?php endif; ?>

        <a href="<?php echo ($_ENV['BASE_URL'] ?? 'http://localhost:8080/dental-clinic-chmsu-thesis'); ?>/public/auth/login.php" class="back-link">
            ← Back to Login
        </a>
    </div>

    <script>
        // Wait for DOM to be fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM Content Loaded - checking for verification elements...');
            
            const verificationInput = document.getElementById('verification_code');
            const validationMessage = document.getElementById('validation_message');
            const expectedCodeElement = document.getElementById('expected_code');
            const expectedCode = expectedCodeElement ? expectedCodeElement.value : null;

            console.log('Elements found:', {
                verificationInput: !!verificationInput,
                validationMessage: !!validationMessage,
                expectedCodeElement: !!expectedCodeElement,
                expectedCode: expectedCode
            });

            // Check if required elements exist - but only proceed if verification form is shown
            if (!verificationInput || !validationMessage) {
                console.log('Verification form elements not found - this is normal if form is not displayed (e.g., after successful verification)');
                return;
            }

            // Auto-format code input (digits only)
            verificationInput.addEventListener('input', function(e) {
                try {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 6) {
                        value = value.substring(0, 6);
                    }
                    e.target.value = value;

                    // Real-time validation if we have an expected code
                    if (expectedCode && value.length === 6) {
                        validateCode(value);
                    } else if (expectedCode && value.length > 0) {
                        // Show partial match feedback
                        const isPartialMatch = expectedCode.startsWith(value);
                        showValidation(isPartialMatch, `${value.length}/6 digits entered...`);
                    } else {
                        hideValidation();
                    }
                } catch (error) {
                    console.error('Error in input handler:', error);
                }
            });

            function validateCode(inputCode) {
                try {
                    if (expectedCode && inputCode === expectedCode) {
                        showValidation(true, '✅ Code matches! Click "Verify Email Address" to continue.');
                        verificationInput.classList.add('valid');
                        verificationInput.classList.remove('invalid');
                    } else if (expectedCode) {
                        showValidation(false, '❌ Code does not match. Please check your email for the correct 6-digit code.');
                        verificationInput.classList.add('invalid');
                        verificationInput.classList.remove('valid');
                    }
                } catch (error) {
                    console.error('Error in validateCode:', error);
                }
            }

            function showValidation(isValid, message) {
                try {
                    if (!validationMessage) return;
                    validationMessage.textContent = message;
                    validationMessage.className = `validation-message ${isValid ? 'valid' : 'invalid'}`;
                    validationMessage.style.display = 'block';
                } catch (error) {
                    console.error('Error in showValidation:', error);
                }
            }

            function hideValidation() {
                try {
                    if (!validationMessage) return;
                    validationMessage.style.display = 'none';
                    if (verificationInput) {
                        verificationInput.classList.remove('valid', 'invalid');
                    }
                } catch (error) {
                    console.error('Error in hideValidation:', error);
                }
            }

            // Show hint if expected code exists
            if (expectedCode) {
                console.log('Expected verification code detected. Real-time validation enabled.');
                console.log('Expected code:', expectedCode);
            }

            // Add debugging information for development (can be removed in production)
            if (typeof window !== 'undefined' && window.location.hostname === 'localhost') {
                const debugInfo = document.createElement('div');
                debugInfo.style.cssText = 'position: fixed; bottom: 10px; right: 10px; background: #f8f9fa; border: 1px solid #ddd; padding: 10px; font-size: 12px; border-radius: 5px; z-index: 9999; max-width: 300px;';
                debugInfo.innerHTML = `
                    <strong>🐛 Debug Info (localhost only):</strong><br>
                    Expected Code: ${expectedCode || 'Not available'}<br>
                    Source: ${expectedCode ? (window.location.search.includes('code=') ? 'URL Parameter' : 'Database/Email File') : 'None'}<br>
                    <em>This appears only on localhost for debugging</em>
                `;
                document.body.appendChild(debugInfo);
            }

        }); // End of DOMContentLoaded event listener
    </script>
</body>
</html>