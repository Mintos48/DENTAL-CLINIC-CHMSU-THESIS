<?php
session_start();
require_once '../../src/config/database.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: ../dashboard/admin-dashboard.php');
            break;
        case 'staff':
            header('Location: ../dashboard/staff-dashboard.php');
            break;
        case 'patient':
            header('Location: ../dashboard/patient-dashboard.php');
            break;
    }
    exit();
}

$error_message = '';
$success_message = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = Database::getConnection();
        
        // Get form data
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $date_of_birth = $_POST['date_of_birth'] ?? '';
        $gender = $_POST['gender'] ?? '';
        $branch_id = (int)($_POST['branch_id'] ?? 0);
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validation
        $errors = [];
        
        if (empty($name)) $errors[] = "Full name is required";
        if (empty($email)) $errors[] = "Email is required";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
        if (empty($phone)) $errors[] = "Phone number is required";
        if (empty($date_of_birth)) $errors[] = "Date of birth is required";
        if (empty($gender)) $errors[] = "Gender is required";
        if ($branch_id === 0) $errors[] = "Please select a branch";
        if (empty($password)) $errors[] = "Password is required";
        if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters";
        if ($password !== $confirm_password) $errors[] = "Passwords do not match";
        
        // Check if email already exists
        if (empty($errors)) {
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors[] = "Email already registered";
            }
            $stmt->close();
        }
        
        // Validate branch exists
        if (empty($errors) && $branch_id > 0) {
            $stmt = $db->prepare("SELECT id FROM branches WHERE id = ? AND status = 'active'");
            $stmt->bind_param("i", $branch_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                $errors[] = "Invalid branch selected";
            }
            $stmt->close();
        }
        
        if (!empty($errors)) {
            $error_message = implode('<br>', $errors);
        } else {
            // Create user account
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("
                INSERT INTO users (name, email, phone, address, date_of_birth, gender, branch_id, password, role, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'patient', 'active')
            ");
            
            $stmt->bind_param("ssssssis", $name, $email, $phone, $address, $date_of_birth, $gender, $branch_id, $hashed_password);
            
            if ($stmt->execute()) {
                $user_id = $db->insert_id;
                
                // Log the registration
                $log_stmt = $db->prepare("INSERT INTO system_logs (user_id, action, description, ip_address) VALUES (?, 'register', 'New patient account created', ?)");
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                $log_stmt->bind_param("is", $user_id, $ip_address);
                $log_stmt->execute();
                $log_stmt->close();
                
                $success_message = "Registration successful! You can now log in.";
                
                // Auto-login the user
                $_SESSION['user_id'] = $user_id;
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = 'patient';
                $_SESSION['branch_id'] = $branch_id;
                
                // Redirect to patient dashboard after a short delay
                header("refresh:2;url=../dashboard/patient-dashboard.php");
            } else {
                $error_message = "Registration failed. Please try again.";
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        $error_message = "System error. Please try again later.";
        error_log("Registration error: " . $e->getMessage());
    }
}

// Get available branches for the form
try {
    $db = Database::getConnection();
    $branches_result = $db->query("SELECT id, name, location FROM branches WHERE status = 'active' ORDER BY name");
    $branches = $branches_result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $branches = [];
    error_log("Error fetching branches: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Dental Clinic Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Custom Color Palette */
        :root {
            --primary-blue: #054A91;
            --secondary-blue: #3E7CB1;
            --light-blue: #81A4CD;
            --very-light-blue: #DBE4EE;
            --orange-accent: #f17300;
            --white: #ffffff;
            --success: #22c55e;
            --error: #ef4444;
            --warning: #f59e0b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            background: linear-gradient(135deg, 
                var(--primary-blue) 0%, 
                #1a5aa3 50%, 
                var(--primary-blue) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .auth-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 
                0 20px 40px rgba(5, 74, 145, 0.15),
                0 10px 20px rgba(0, 0, 0, 0.1);
            padding: 2.5rem;
            width: 100%;
            max-width: 900px;
            position: relative;
            overflow: hidden;
        }

        .auth-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-blue);
            border-radius: 20px 20px 0 0;
        }

        .header-section {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .logo-container {
            background: var(--primary-blue);
            width: 70px;
            height: 70px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 20px rgba(5, 74, 145, 0.3);
        }

        .logo-container i {
            font-size: 1.8rem;
            color: white;
        }

        .header-section h1 {
            color: var(--primary-blue);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .header-section h2 {
            color: var(--orange-accent);
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .header-section p {
            color: var(--primary-blue);
            opacity: 0.7;
            font-size: 0.95rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert.success {
            background: rgba(34, 197, 94, 0.1);
            color: var(--success);
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .alert.error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem 2rem;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            color: var(--primary-blue);
            font-weight: 600;
            font-size: 0.9rem;
        }

        .form-group label i {
            color: var(--primary-blue);
            opacity: 0.7;
            width: 14px;
            text-align: center;
        }

        .input-wrapper {
            position: relative;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.9rem 0.9rem 0.9rem 2.5rem;
            border: 1.5px solid rgba(5, 74, 145, 0.2);
            border-radius: 10px;
            font-size: 0.95rem;
            background: rgba(255, 255, 255, 0.9);
            color: var(--primary-blue);
            transition: all 0.3s ease;
        }

        .input-icon {
            position: absolute;
            left: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-blue);
            opacity: 0.6;
            font-size: 0.9rem;
            width: 14px;
            text-align: center;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(5, 74, 145, 0.15);
            background: white;
        }

        .form-group:focus-within .input-icon {
            color: var(--primary-blue);
            opacity: 1;
        }

        .form-group input:hover:not(:focus),
        .form-group select:hover:not(:focus) {
            border-color: var(--primary-blue);
            opacity: 0.8;
        }

        .password-toggle {
            position: absolute;
            right: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--primary-blue);
            opacity: 0.6;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .password-toggle:hover {
            opacity: 1;
        }

        .terms-section {
            margin: 2rem 0;
        }

        .terms-checkbox {
            display: flex;
            gap: 0.8rem;
            align-items: flex-start;
            cursor: pointer;
        }

        .terms-checkbox input[type="checkbox"] {
            width: 16px;
            height: 16px;
            margin: 0;
            accent-color: var(--primary-blue);
        }

        .terms-text {
            flex: 1;
            font-size: 0.85rem;
            line-height: 1.5;
            color: var(--primary-blue);
            opacity: 0.8;
        }

        .terms-text a {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 600;
        }

        .terms-text a:hover {
            text-decoration: underline;
        }

        .btn {
            width: 100%;
            padding: 1rem 2rem;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: var(--primary-blue);
            color: white;
            box-shadow: 0 6px 20px rgba(5, 74, 145, 0.3);
        }

        .btn-primary:hover:not(:disabled) {
            background: #043a7a;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(5, 74, 145, 0.4);
        }

        .btn-primary:disabled {
            background: rgba(5, 74, 145, 0.4);
            color: rgba(255, 255, 255, 0.7);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .auth-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(5, 74, 145, 0.2);
        }

        .auth-link p {
            margin: 0;
            color: var(--primary-blue);
            opacity: 0.8;
        }

        .auth-link a {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 600;
        }

        .auth-link a:hover {
            color: #043a7a;
        }

        .validation-message {
            font-size: 0.8rem;
            margin-top: 0.3rem;
            min-height: 1rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .validation-message.show {
            opacity: 1;
        }

        .validation-message.error {
            color: var(--error);
        }

        .validation-message.success {
            color: var(--success);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .auth-container {
                margin: 0.5rem;
                padding: 2rem 1.5rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .header-section h1 {
                font-size: 1.6rem;
            }

            .form-group input,
            .form-group select {
                font-size: 16px; /* Prevent zoom on iOS */
            }
        }

        @media (max-width: 480px) {
            .auth-container {
                padding: 1.5rem 1rem;
            }

            .logo-container {
                width: 60px;
                height: 60px;
            }

            .header-section h1 {
                font-size: 1.4rem;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="header-section">
            <div class="logo-container">
                <i class="fas fa-user-plus"></i>
            </div>
            <h1>Dental Clinic Management</h1>
            <h2>Create Your Account</h2>
            <p>Join our comprehensive dental care network</p>
        </div>

        <?php if ($error_message): ?>
            <div class="alert error">
                <i class="fas fa-exclamation-triangle"></i>
                <div><?php echo $error_message; ?></div>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert success">
                <i class="fas fa-check-circle"></i>
                <div><?php echo $success_message; ?> Redirecting to your dashboard...</div>
            </div>
        <?php endif; ?>

        <form method="POST" id="registerForm" novalidate>
            <div class="form-grid">
                <!-- Full Name -->
                <div class="form-group">
                    <label for="name">
                        <i class="fas fa-user"></i> Full Name *
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" id="name" name="name" required
                               placeholder="Enter your full name"
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>
                    <div class="validation-message" id="name-validation"></div>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email Address *
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" required
                               placeholder="Enter your email address"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    <div class="validation-message" id="email-validation"></div>
                </div>

                <!-- Phone -->
                <div class="form-group">
                    <label for="phone">
                        <i class="fas fa-phone"></i> Phone Number *
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-phone input-icon"></i>
                        <input type="tel" id="phone" name="phone" required
                               placeholder="Enter your phone number"
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>
                    <div class="validation-message" id="phone-validation"></div>
                </div>

                <!-- Date of Birth -->
                <div class="form-group">
                    <label for="date_of_birth">
                        <i class="fas fa-calendar-alt"></i> Date of Birth *
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-calendar-alt input-icon"></i>
                        <input type="date" id="date_of_birth" name="date_of_birth" required
                               value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
                    </div>
                    <div class="validation-message" id="dob-validation"></div>
                </div>

                <!-- Gender -->
                <div class="form-group">
                    <label for="gender">
                        <i class="fas fa-venus-mars"></i> Gender *
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-venus-mars input-icon"></i>
                        <select id="gender" name="gender" required>
                            <option value="">Select your gender</option>
                            <option value="male" <?php echo (($_POST['gender'] ?? '') === 'male') ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo (($_POST['gender'] ?? '') === 'female') ? 'selected' : ''; ?>>Female</option>
                            <option value="other" <?php echo (($_POST['gender'] ?? '') === 'other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="validation-message" id="gender-validation"></div>
                </div>

                <!-- Branch Selection -->
                <div class="form-group">
                    <label for="branch_id">
                        <i class="fas fa-building"></i> Preferred Branch *
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-building input-icon"></i>
                        <select id="branch_id" name="branch_id" required>
                            <option value="">Select a branch</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?php echo $branch['id']; ?>" 
                                        <?php echo (($_POST['branch_id'] ?? '') == $branch['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($branch['name'] . ' - ' . $branch['location']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="validation-message" id="branch-validation"></div>
                </div>

                <!-- Address -->
                <div class="form-group full-width">
                    <label for="address">
                        <i class="fas fa-map-marker-alt"></i> Address
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-map-marker-alt input-icon"></i>
                        <textarea id="address" name="address" rows="2"
                                  placeholder="Enter your complete address"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                    </div>
                    <div class="validation-message" id="address-validation"></div>
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password *
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" required
                               placeholder="Create a strong password">
                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                            <i class="fas fa-eye" id="password-icon"></i>
                        </button>
                    </div>
                    <div class="validation-message" id="password-validation"></div>
                </div>

                <!-- Confirm Password -->
                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i> Confirm Password *
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="confirm_password" name="confirm_password" required
                               placeholder="Confirm your password">
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye" id="confirm_password-icon"></i>
                        </button>
                    </div>
                    <div class="validation-message" id="confirm-password-validation"></div>
                </div>
            </div>

            <!-- Terms and Conditions -->
            <div class="terms-section">
                <label class="terms-checkbox">
                    <input type="checkbox" id="terms" name="terms" required>
                    <div class="terms-text">
                        I agree to the <a href="#" target="_blank">Terms of Service</a> and 
                        <a href="#" target="_blank">Privacy Policy</a>. I understand that my 
                        information will be used to provide dental care services.
                    </div>
                </label>
                <div class="validation-message" id="terms-validation"></div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary" id="registerBtn">
                <span class="btn-text">
                    <i class="fas fa-user-plus"></i> Create My Account
                </span>
                <span class="spinner" style="display: none;"></span>
            </button>
        </form>

        <!-- Login Link -->
        <div class="auth-link">
            <p>
                Already have an account? 
                <a href="login.php">
                    <i class="fas fa-sign-in-alt"></i> Sign In Here
                </a>
            </p>
        </div>
    </div>

    <script>
        // Password visibility toggle
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + '-icon');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('registerBtn');
            const btnText = submitBtn.querySelector('.btn-text');
            const spinner = submitBtn.querySelector('.spinner');
            
            // Show loading state
            submitBtn.disabled = true;
            btnText.style.display = 'none';
            spinner.style.display = 'inline-block';
            
            // Basic client-side validation
            let isValid = true;
            const requiredFields = ['name', 'email', 'phone', 'date_of_birth', 'gender', 'branch_id', 'password', 'confirm_password'];
            
            requiredFields.forEach(fieldName => {
                const field = document.getElementById(fieldName);
                const validation = document.getElementById(fieldName.replace('_', '-') + '-validation');
                
                if (!field.value.trim()) {
                    validation.textContent = 'This field is required';
                    validation.classList.add('show', 'error');
                    isValid = false;
                } else {
                    validation.classList.remove('show', 'error');
                }
            });
            
            // Password validation
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const passwordValidation = document.getElementById('password-validation');
            const confirmPasswordValidation = document.getElementById('confirm-password-validation');
            
            if (password.length < 8) {
                passwordValidation.textContent = 'Password must be at least 8 characters';
                passwordValidation.classList.add('show', 'error');
                isValid = false;
            }
            
            if (password !== confirmPassword) {
                confirmPasswordValidation.textContent = 'Passwords do not match';
                confirmPasswordValidation.classList.add('show', 'error');
                isValid = false;
            }
            
            // Terms validation
            const terms = document.getElementById('terms');
            const termsValidation = document.getElementById('terms-validation');
            
            if (!terms.checked) {
                termsValidation.textContent = 'You must agree to the terms and conditions';
                termsValidation.classList.add('show', 'error');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                // Reset button state
                submitBtn.disabled = false;
                btnText.style.display = 'flex';
                spinner.style.display = 'none';
            }
        });

        // Real-time validation
        document.querySelectorAll('input, select').forEach(field => {
            field.addEventListener('blur', function() {
                const validation = document.getElementById(this.id.replace('_', '-') + '-validation');
                
                if (this.hasAttribute('required') && !this.value.trim()) {
                    validation.textContent = 'This field is required';
                    validation.classList.add('show', 'error');
                } else {
                    validation.classList.remove('show', 'error');
                    
                    // Email validation
                    if (this.type === 'email' && this.value) {
                        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (!emailRegex.test(this.value)) {
                            validation.textContent = 'Please enter a valid email address';
                            validation.classList.add('show', 'error');
                        }
                    }
                }
            });
        });

        // Password match validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const validation = document.getElementById('confirm-password-validation');
            
            if (this.value && this.value !== password) {
                validation.textContent = 'Passwords do not match';
                validation.classList.add('show', 'error');
            } else {
                validation.classList.remove('show', 'error');
            }
        });
    </script>
</body>
</html>
