<?php
// Prevent AuthController API handler from running
define('SKIP_AUTH_API_HANDLER', true);

require_once '../../src/controllers/AuthController.php';

$controller = new AuthController();
$result = $controller->verifyEmail();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - Dental Clinic Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #054A91;
            --secondary-blue: #3E7CB1;
            --success: #22c55e;
            --error: #ef4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, var(--primary-blue) 0%, #1a5aa3 50%, var(--primary-blue) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .verification-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            padding: 3rem 2rem;
            max-width: 650px;
            width: 100%;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .verification-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-blue);
            border-radius: 20px 20px 0 0;
        }

        .status-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            font-size: 2rem;
            color: white;
        }

        .status-icon.success {
            background: linear-gradient(135deg, var(--success), #16a34a);
            animation: successPulse 1.5s ease-in-out;
        }

        .status-icon.error {
            background: linear-gradient(135deg, var(--error), #dc2626);
            animation: errorShake 0.5s ease-in-out;
        }

        @keyframes successPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        @keyframes errorShake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        h1 {
            color: var(--primary-blue);
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .message {
            color: #64748b;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .message.success {
            color: var(--success);
        }

        .message.error {
            color: var(--error);
        }

        .actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--primary-blue);
            color: white;
        }

        .btn-primary:hover {
            background: #043a7a;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(5, 74, 145, 0.3);
        }

        .btn-secondary {
            background: #f1f5f9;
            color: var(--primary-blue);
            border: 1px solid #e2e8f0;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
        }

        .verification-details {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            font-size: 0.9rem;
            text-align: left;
            line-height: 1.6;
        }

        .verification-details strong {
            color: var(--primary-blue);
        }

        .verification-details em {
            color: #64748b;
            font-style: italic;
        }

        .support-info {
            background: #fffbeb;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
            font-size: 0.85rem;
            text-align: center;
        }

        .support-info strong {
            color: #92400e;
        }

        @media (max-width: 480px) {
            .verification-container {
                padding: 2rem 1.5rem;
            }

            h1 {
                font-size: 1.5rem;
            }

            .actions {
                flex-direction: column;
                align-items: center;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <?php if ($result['success']): ?>
            <div class="status-icon success">
                <i class="fas fa-check"></i>
            </div>
            <h1>Email Verified Successfully!</h1>
            <div class="message success">
                <?php echo htmlspecialchars($result['message']); ?>
            </div>
            
            <?php if (isset($result['user_name'])): ?>
                <div class="verification-details">
                    <strong>Welcome, <?php echo htmlspecialchars($result['user_name']); ?>!</strong><br>
                    Your account is now fully activated and ready to use.
                </div>
            <?php endif; ?>

            <div class="actions">
                <a href="login.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i>
                    Login to Your Account
                </a>
            </div>
        <?php else: ?>
            <div class="status-icon error">
                <i class="fas fa-times"></i>
            </div>
            <h1>Email Verification Failed</h1>
            <div class="message error">
                <?php echo htmlspecialchars($result['message']); ?>
            </div>
            
            <div class="verification-details">
                <strong>What went wrong?</strong><br><br>
                
                The email verification link you clicked may have one of the following issues:<br><br>
                
                • <strong>Expired Link:</strong> Verification links are only valid for a limited time for security purposes.<br>
                • <strong>Already Used:</strong> This verification link may have already been used to verify your email address.<br>
                • <strong>Invalid Token:</strong> The verification token in the link may be corrupted or invalid.<br>
                • <strong>Account Issues:</strong> Your account may have been deactivated or removed.<br><br>
                
                <strong>What can you do next?</strong><br><br>
                
                1. <strong>Try logging in:</strong> If your email was already verified, you can proceed directly to login.<br>
                2. <strong>Request new verification:</strong> Go to the login page and attempt to login. If your email still needs verification, you'll be prompted to send a new verification email.<br>
                3. <strong>Create new account:</strong> If you continue having issues, you may need to register a new account.<br>
                4. <strong>Contact support:</strong> If problems persist, please contact our technical support team for assistance.<br><br>
                
                <em>For security reasons, we cannot provide specific details about why verification failed.</em>
            </div>

            <div class="support-info">
                <strong>Need Help?</strong><br>
                If you continue experiencing issues, please contact our support team:<br>
                Email: support@dentalclinic.com | Phone: (555) 123-4567
            </div>

            <div class="actions">
                <a href="login.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i>
                    Back to Login
                </a>
                <a href="register.php" class="btn btn-secondary">
                    <i class="fas fa-user-plus"></i>
                    Create New Account
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-redirect to login page after successful verification
        <?php if ($result['success']): ?>
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 5000);
        <?php endif; ?>
    </script>
</body>
</html>