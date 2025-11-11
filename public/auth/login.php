<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Dental Clinic Management System</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Enhanced Login Page Styles */
        body {
            background: linear-gradient(135deg, #054A91 0%, #3E7CB1 100%);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated background elements */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="white" opacity="0.1"/><circle cx="20" cy="20" r="0.5" fill="white" opacity="0.05"/><circle cx="80" cy="30" r="0.8" fill="white" opacity="0.08"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
            z-index: 0;
        }

        .auth-wrapper {
            position: relative;
            z-index: 1;
            padding: 2rem 1rem;
        }

        .auth-box {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 24px;
            box-shadow: 
                0 32px 64px rgba(0, 0, 0, 0.15),
                0 16px 32px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            padding: 3rem 2.5rem;
            width: 100%;
            max-width: 420px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .auth-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #054A91, #f17300, #054A91);
            background-size: 200% 100%;
            animation: shimmer 3s ease-in-out infinite;
        }

        @keyframes shimmer {
            0%, 100% { background-position: -200% 0; }
            50% { background-position: 200% 0; }
        }

        .auth-box:hover {
            transform: translateY(-4px);
            box-shadow: 
                0 40px 80px rgba(0, 0, 0, 0.2),
                0 20px 40px rgba(0, 0, 0, 0.15),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .auth-header .logo {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #054A91, #3E7CB1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 8px 24px rgba(5, 74, 145, 0.3);
        }

        .auth-header .logo i {
            color: white;
            font-size: 1.5rem;
        }

        .auth-header h1 {
            color: #1e293b;
            margin-bottom: 0.5rem;
            font-size: 1.875rem;
            font-weight: 700;
            letter-spacing: -0.025em;
        }

        .auth-header h2 {
            color: #64748b;
            margin: 0;
            font-size: 0.875rem;
            font-weight: 400;
            opacity: 0.8;
        }

        /* Enhanced Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #374151;
            font-weight: 500;
            font-size: 0.875rem;
            transition: color 0.2s ease;
        }

        .form-group label .required {
            color: #ef4444;
            margin-left: 2px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 0.875rem;
            transition: color 0.2s ease;
            z-index: 2;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.875rem 1rem;
            padding-left: 2.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 0.875rem;
            font-family: inherit;
            transition: all 0.2s ease;
            background-color: white;
            position: relative;
        }

        .form-group select {
            padding-left: 2.75rem;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #054A91;
            box-shadow: 0 0 0 3px rgba(5, 74, 145, 0.1);
            transform: translateY(-1px);
        }

        .form-group input:focus + .input-icon,
        .form-group select:focus + .input-icon,
        .form-group.focused .input-icon {
            color: #054A91;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 0.25rem;
            transition: all 0.2s ease;
            z-index: 2;
        }

        .password-toggle:hover {
            color: #054A91;
            background-color: rgba(5, 74, 145, 0.1);
        }

        /* Validation Styles */
        .form-group.error input,
        .form-group.error select {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }

        .form-group.error .input-icon {
            color: #ef4444;
        }

        .form-group.success input,
        .form-group.success select {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .form-group.success .input-icon {
            color: #10b981;
        }

        .error-message {
            display: block;
            margin-top: 0.5rem;
            color: #ef4444;
            font-size: 0.75rem;
            opacity: 0;
            transform: translateY(-4px);
            transition: all 0.2s ease;
        }

        .form-group.error .error-message {
            opacity: 1;
            transform: translateY(0);
        }

        .success-message {
            display: block;
            margin-top: 0.5rem;
            color: #10b981;
            font-size: 0.75rem;
            opacity: 0;
            transform: translateY(-4px);
            transition: all 0.2s ease;
        }

        .form-group.success .success-message {
            opacity: 1;
            transform: translateY(0);
        }

        /* Enhanced Button */
        .btn-login {
            width: 100%;
            padding: 0.875rem 1.5rem;
            background: linear-gradient(135deg, #054A91, #3E7CB1);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(5, 74, 145, 0.4);
            margin-top: 0.5rem;
        }

        .btn-login:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(5, 74, 145, 0.5);
        }

        .btn-login:active:not(:disabled) {
            transform: translateY(0);
        }

        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .btn-loading {
            position: relative;
            color: transparent !important;
        }

        .btn-loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 16px;
            height: 16px;
            margin: -8px 0 0 -8px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Enhanced Alert */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            border: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            opacity: 0;
            transform: translateY(-8px);
            transition: all 0.3s ease;
        }

        .alert.show {
            opacity: 1;
            transform: translateY(0);
        }

        .alert-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .alert-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .alert-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        .alert-info {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        /* Auth Link */
        .auth-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
        }

        .auth-link p {
            color: #6b7280;
            font-size: 0.875rem;
            margin: 0;
        }

        .auth-link a {
            color: #054A91;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
            position: relative;
        }

        .auth-link a:hover {
            color: #3E7CB1;
        }

        .auth-link a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, #054A91, #3E7CB1);
            transition: width 0.2s ease;
        }

        .auth-link a:hover::after {
            width: 100%;
        }

        /* Email Verification Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            animation: modalFadeIn 0.3s ease;
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            max-width: 450px;
            width: 90%;
            position: relative;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from { 
                opacity: 0;
                transform: translateY(-20px) scale(0.95);
            }
            to { 
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .modal-header .icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }

        .modal-header .icon i {
            color: white;
            font-size: 1.5rem;
        }

        .modal-header h3 {
            color: #1e293b;
            margin: 0 0 0.5rem 0;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .modal-header p {
            color: #64748b;
            margin: 0;
            font-size: 0.875rem;
            line-height: 1.5;
        }

        .modal-body {
            margin-bottom: 1.5rem;
        }

        .verification-info {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .verification-info p {
            margin: 0;
            color: #92400e;
            font-size: 0.875rem;
            line-height: 1.5;
        }

        .modal-buttons {
            display: flex;
            gap: 0.75rem;
            flex-direction: column;
        }

        .btn-modal {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-modal-primary {
            background: linear-gradient(135deg, #054A91, #3E7CB1);
            color: white;
        }

        .btn-modal-primary:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(5, 74, 145, 0.3);
        }

        .btn-modal-secondary {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }

        .btn-modal-secondary:hover:not(:disabled) {
            background: #e2e8f0;
            color: #475569;
        }

        .btn-modal:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .close-modal {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            font-size: 1.25rem;
            color: #9ca3af;
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 0.25rem;
            transition: all 0.2s ease;
        }

        .close-modal:hover {
            color: #374151;
            background: #f3f4f6;
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            .auth-box {
                margin: 1rem;
                padding: 2rem 1.5rem;
                border-radius: 16px;
            }

            .auth-header h1 {
                font-size: 1.5rem;
            }

            .form-group input,
            .form-group select {
                padding: 0.75rem 1rem;
                padding-left: 2.5rem;
                font-size: 0.875rem;
            }
        }

        /* Accessibility Enhancements */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        /* Focus styles for keyboard navigation */
        .btn-login:focus-visible {
            outline: 2px solid #054A91;
            outline-offset: 2px;
        }

        .form-group input:focus-visible,
        .form-group select:focus-visible {
            outline: 2px solid #054A91;
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="auth-wrapper">
            <div class="auth-box">
                <div class="auth-header">
                    <div class="logo">
                        <i class="fas fa-tooth" aria-hidden="true"></i>
                    </div>
                    <h1>Welcome Back</h1>
                    <h2>Sign in to your account to continue</h2>
                </div>
                
                <div id="alert" class="alert" style="display: none;" role="alert" aria-live="polite"></div>
                
                <form id="loginForm" novalidate>
                    <div class="form-group">
                        <label for="email">
                            Email Address
                            <span class="required" aria-label="required">*</span>
                        </label>
                        <div class="input-wrapper">
                            <i class="input-icon fas fa-envelope" aria-hidden="true"></i>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                required 
                                autocomplete="email"
                                placeholder="Enter your email address"
                                aria-describedby="email-error"
                            >
                        </div>
                        <span class="error-message" id="email-error" role="alert"></span>
                        <span class="success-message" id="email-success"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">
                            Password
                            <span class="required" aria-label="required">*</span>
                        </label>
                        <div class="input-wrapper">
                            <i class="input-icon fas fa-lock" aria-hidden="true"></i>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                required 
                                autocomplete="current-password"
                                placeholder="Enter your password"
                                aria-describedby="password-error"
                            >
                            <button 
                                type="button" 
                                class="password-toggle" 
                                id="togglePassword"
                                aria-label="Toggle password visibility"
                                tabindex="0"
                            >
                                <i class="fas fa-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                        <span class="error-message" id="password-error" role="alert"></span>
                        <span class="success-message" id="password-success"></span>
                    </div>
                    
                    <button type="submit" class="btn-login" id="loginButton">
                        <span id="buttonText">Sign In</span>
                    </button>
                </form>
                
                <div class="auth-link">
                    <p>Don't have an account? <a href="register.php">Create account</a></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- OTP Verification Modal -->
    <div id="otpVerificationModal" class="modal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeOTPModal()">
                <i class="fas fa-times"></i>
            </button>
            
            <div class="modal-header">
                <div class="icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>OTP Verification Required</h3>
                <p>Enter the 6-digit OTP sent to your email to complete login.</p>
            </div>
            
            <div class="modal-body">
                <div class="verification-info">
                    <p><strong>Email:</strong> <span id="otpUserEmail"></span></p>
                    <p>Please check your email for the OTP code. It will expire in 5 minutes.</p>
                </div>
                
                <div class="form-group">
                    <label for="otpCode">Enter 6-Digit OTP</label>
                    <input 
                        type="text" 
                        id="otpCode" 
                        class="code-input"
                        placeholder="000000"
                        maxlength="6"
                        pattern="\d{6}"
                        style="width: 100%; padding: 15px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 24px; text-align: center; letter-spacing: 8px; font-family: monospace; font-weight: bold; color: #054A91;"
                    >
                    <div id="otpValidationMessage" style="margin-top: 10px; font-size: 14px; text-align: center;"></div>
                </div>
            </div>
            
            <div class="modal-buttons">
                <button class="btn-modal btn-modal-primary" id="verifyOTPBtn">
                    <i class="fas fa-check"></i>
                    <span>Verify OTP</span>
                </button>
                <button class="btn-modal btn-modal-secondary" id="resendOTPBtn">
                    <i class="fas fa-paper-plane"></i>
                    <span>Resend OTP</span>
                </button>
                <button class="btn-modal btn-modal-secondary" onclick="closeOTPModal()">
                    <i class="fas fa-times"></i>
                    Cancel
                </button>
            </div>
        </div>
    </div>
    
    <!-- Email Verification Modal -->
    <div id="emailVerificationModal" class="modal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeEmailModal()">
                <i class="fas fa-times"></i>
            </button>
            
            <div class="modal-header">
                <div class="icon">
                    <i class="fas fa-envelope-open"></i>
                </div>
                <h3>Email Verification Required</h3>
                <p>Your email address needs to be verified before you can access your account.</p>
            </div>
            
            <div class="modal-body">
                <div class="verification-info">
                    <p><strong>Email:</strong> <span id="userEmail"></span></p>
                    <p>A verification code has been sent to your email address. Please check your email and click the button below to enter the verification code.</p>
                </div>
            </div>
            
            <div class="modal-buttons">
                <button class="btn-modal btn-modal-primary" id="goToVerificationBtn">
                    <i class="fas fa-key"></i>
                    <span>Enter Verification Code</span>
                </button>
                <button class="btn-modal btn-modal-secondary" id="resendVerificationBtn">
                    <i class="fas fa-paper-plane"></i>
                    <span>Resend Email</span>
                </button>
                <button class="btn-modal btn-modal-secondary" onclick="closeEmailModal()">
                    <i class="fas fa-times"></i>
                    Cancel
                </button>
            </div>
        </div>
    </div>
    
    <script>
        // Enhanced Login Form Manager
        class LoginFormManager {
            constructor() {
                // Prevent multiple instances
                if (window.loginFormManager) {
                    console.warn('LoginFormManager already initialized');
                    return window.loginFormManager;
                }
                
                this.form = document.getElementById('loginForm');
                this.alertContainer = document.getElementById('alert');
                this.loginButton = document.getElementById('loginButton');
                this.buttonText = document.getElementById('buttonText');
                this.togglePasswordBtn = document.getElementById('togglePassword');
                
                this.isSubmitting = false;
                this.validationRules = {
                    email: {
                        required: true,
                        pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
                        message: 'Please enter a valid email address'
                    },
                    password: {
                        required: true,
                        minLength: 6,
                        message: 'Password must be at least 6 characters long'
                    }
                };
                
                this.init();
                
                // Store instance globally to prevent duplicates
                window.loginFormManager = this;
            }
            
            init() {
                this.setupEventListeners();
                this.setupPasswordToggle();
                this.setupAccessibility();
                this.setupFormValidation();
                this.addAnimationStyles();
            }
            
            setupEventListeners() {
                // Form submission
                this.form.addEventListener('submit', this.handleSubmit.bind(this));
                
                // Real-time validation
                Object.keys(this.validationRules).forEach(fieldName => {
                    const field = document.getElementById(fieldName);
                    if (field) {
                        field.addEventListener('blur', () => this.validateField(fieldName));
                        field.addEventListener('input', () => this.clearFieldError(fieldName));
                        field.addEventListener('focus', () => this.handleFieldFocus(fieldName));
                    }
                });
                
                // Enhanced keyboard navigation
                this.form.addEventListener('keydown', this.handleKeyboardNavigation.bind(this));
            }
            
            setupPasswordToggle() {
                if (this.togglePasswordBtn) {
                    this.togglePasswordBtn.addEventListener('click', this.togglePasswordVisibility.bind(this));
                    this.togglePasswordBtn.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            this.togglePasswordVisibility();
                        }
                    });
                }
            }
            
            setupAccessibility() {
                // Add proper ARIA labels and descriptions
                const fields = ['email', 'password'];
                fields.forEach(fieldName => {
                    const field = document.getElementById(fieldName);
                    const errorElement = document.getElementById(`${fieldName}-error`);
                    if (field && errorElement) {
                        field.setAttribute('aria-describedby', `${fieldName}-error`);
                    }
                });
            }
            
            setupFormValidation() {
                // Disable HTML5 validation in favor of custom validation
                this.form.setAttribute('novalidate', 'true');
            }
            
            addAnimationStyles() {
                // Add shake animation keyframes if not already present
                if (!document.querySelector('#shake-keyframes')) {
                    const style = document.createElement('style');
                    style.id = 'shake-keyframes';
                    style.textContent = `
                        @keyframes shake {
                            0%, 100% { transform: translateX(0); }
                            25% { transform: translateX(-4px); }
                            75% { transform: translateX(4px); }
                        }
                    `;
                    document.head.appendChild(style);
                }
            }
            
            handleKeyboardNavigation(e) {
                // Enhanced keyboard navigation for better accessibility
                if (e.key === 'Enter' && e.target.tagName !== 'BUTTON') {
                    const formElements = Array.from(this.form.querySelectorAll('input, select, button'));
                    const currentIndex = formElements.indexOf(e.target);
                    
                    if (currentIndex < formElements.length - 1) {
                        e.preventDefault();
                        formElements[currentIndex + 1].focus();
                    }
                }
            }
            
            togglePasswordVisibility() {
                const passwordField = document.getElementById('password');
                const icon = this.togglePasswordBtn.querySelector('i');
                
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                    this.togglePasswordBtn.setAttribute('aria-label', 'Hide password');
                } else {
                    passwordField.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                    this.togglePasswordBtn.setAttribute('aria-label', 'Show password');
                }
                
                // Add subtle animation
                this.togglePasswordBtn.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.togglePasswordBtn.style.transform = 'scale(1)';
                }, 150);
            }
            
            handleFieldFocus(fieldName) {
                const formGroup = document.getElementById(fieldName).closest('.form-group');
                formGroup.classList.add('focused');
                
                // Remove focus class when field loses focus
                const field = document.getElementById(fieldName);
                const removeFocus = () => {
                    formGroup.classList.remove('focused');
                    field.removeEventListener('blur', removeFocus);
                };
                field.addEventListener('blur', removeFocus);
            }
            
            validateField(fieldName) {
                const field = document.getElementById(fieldName);
                const value = field.value.trim();
                const rules = this.validationRules[fieldName];
                const formGroup = field.closest('.form-group');
                const errorElement = document.getElementById(`${fieldName}-error`);
                const successElement = document.getElementById(`${fieldName}-success`);
                
                // Clear previous states
                formGroup.classList.remove('error', 'success');
                errorElement.textContent = '';
                successElement.textContent = '';
                
                // Validate required fields
                if (rules.required && !value) {
                    this.setFieldError(formGroup, errorElement, rules.message);
                    return false;
                }
                
                // Validate email pattern
                if (fieldName === 'email' && value && !rules.pattern.test(value)) {
                    this.setFieldError(formGroup, errorElement, rules.message);
                    return false;
                }
                
                // Validate password length
                if (fieldName === 'password' && value && value.length < rules.minLength) {
                    this.setFieldError(formGroup, errorElement, rules.message);
                    return false;
                }
                
                // Field is valid
                if (value) {
                    this.setFieldSuccess(formGroup, successElement, this.getSuccessMessage(fieldName));
                }
                
                return true;
            }
            
            setFieldError(formGroup, errorElement, message) {
                formGroup.classList.add('error');
                errorElement.textContent = message;
                
                // Add shake animation
                formGroup.style.animation = 'shake 0.5s ease-in-out';
                setTimeout(() => {
                    formGroup.style.animation = '';
                }, 500);
            }
            
            setFieldSuccess(formGroup, successElement, message) {
                formGroup.classList.add('success');
                successElement.textContent = message;
            }
            
            clearFieldError(fieldName) {
                const field = document.getElementById(fieldName);
                const formGroup = field.closest('.form-group');
                const errorElement = document.getElementById(`${fieldName}-error`);
                
                if (formGroup.classList.contains('error')) {
                    formGroup.classList.remove('error');
                    errorElement.textContent = '';
                }
            }
            
            getSuccessMessage(fieldName) {
                const messages = {
                    email: 'Valid email address',
                    password: 'Password meets requirements'
                };
                return messages[fieldName] || 'Valid';
            }
            
            validateForm() {
                let isValid = true;
                
                Object.keys(this.validationRules).forEach(fieldName => {
                    if (!this.validateField(fieldName)) {
                        isValid = false;
                    }
                });
                
                return isValid;
            }
            
            async handleSubmit(e) {
                e.preventDefault();
                
                if (this.isSubmitting) {
                    console.warn('Form submission already in progress, ignoring duplicate attempt');
                    return;
                }
                
                // Clear previous alerts
                this.hideAlert();
                
                // Validate form
                if (!this.validateForm()) {
                    this.showAlert('Please correct the errors above', 'danger');
                    return;
                }
                
                this.isSubmitting = true;
                this.setLoadingState(true);
                
                try {
                    const formData = new FormData(this.form);
                    
                    // Log the request for debugging
                    console.log('Making login request to:', '../api/auth.php?action=login');
                    console.log('Form data:', Object.fromEntries(formData));
                    console.log('Request timestamp:', new Date().toISOString());
                    
                    const response = await fetch('../api/auth.php?action=login', {
                        method: 'POST',
                        body: formData
                    });
                    
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    const responseText = await response.text();
                    console.log('Raw response:', responseText);
                    
                    let data;
                    try {
                        data = JSON.parse(responseText);
                    } catch (parseError) {
                        console.error('JSON parse error:', parseError);
                        throw new Error('Invalid JSON response from server');
                    }
                    
                    console.log('Login response:', data);
                    
                    console.log('🔍 Checking response data...');
                    console.log('data.success:', data.success);
                    console.log('data.email_verification_required:', data.email_verification_required);
                    console.log('data.verification_sent:', data.verification_sent);
                    console.log('Complete data object:', JSON.stringify(data, null, 2));
                    
                    if (data.success) {
                        console.log('✅ Login successful path');
                        this.showAlert('Login successful! Redirecting...', 'success');
                        
                        // Add success animation
                        this.form.style.transform = 'scale(0.98)';
                        this.form.style.opacity = '0.8';
                        
                        setTimeout(() => {
                            if (data.redirect) {
                                window.location.href = data.redirect;
                            } else {
                                console.error('No redirect URL provided');
                                this.showAlert('Login successful but no redirect URL found. Please contact administrator.', 'warning');
                                this.isSubmitting = false;
                                this.setLoadingState(false);
                            }
                        }, 1500);
                    } else if (data.otp_verification_required) {
                        console.log('🔐 OTP verification required path');
                        
                        // Clear loading state for OTP flow
                        this.isSubmitting = false;
                        this.setLoadingState(false);
                        
                        // Show OTP verification interface
                        this.showOTPVerificationModal(data.user_email);
                        
                    } else if (data.email_verification_required) {
                        console.log('📧 Email verification required path');
                        console.log('verification_sent:', data.verification_sent);
                        console.log('About to call setLoadingState(false)...');
                        
                        // Clear loading state immediately for verification flow
                        this.isSubmitting = false;
                        this.setLoadingState(false);
                        console.log('✅ Loading state cleared');
                        
                        // Handle email verification requirement
                        if (data.verification_sent) {
                            console.log('✅ Verification sent - will show notifications and redirect');
                            
                            // Show initial notification about email being sent
                            this.showAlert('📧 Sending verification email...', 'info');
                            
                            // After a short delay, show success message
                            setTimeout(() => {
                                this.showAlert(`✅ Verification email sent to ${data.user_email}! Redirecting to verification page...`, 'success');
                                
                                // Then redirect after showing the success message
                                setTimeout(() => {
                                    console.log('🔄 Redirecting to verification page...');
                                    const verificationUrl = `../auth/verify-email-code.php?email=${encodeURIComponent(data.user_email)}`;
                                    console.log('Redirect URL:', verificationUrl);
                                    window.location.href = verificationUrl;
                                }, 2500); // 2.5 seconds to read the success message
                                
                            }, 1000); // 1 second delay before showing success
                            
                        } else {
                            console.log('❌ Verification not sent - showing modal');
                            // Show email verification modal for manual resend
                            this.showEmailVerificationModal(data.user_email, data.user_id);
                        }
                    } else {
                        console.log('❌ Login failed path');
                        this.showAlert(data.message || 'Login failed. Please try again.', 'danger');
                        this.isSubmitting = false;
                        this.setLoadingState(false);
                    }
                } catch (error) {
                    console.error('Login error:', error);
                    console.error('Error details:', {
                        name: error.name,
                        message: error.message,
                        stack: error.stack
                    });
                    
                    let errorMessage = 'Network error. Please check your connection and try again.';
                    
                    if (error.message.includes('Failed to fetch')) {
                        errorMessage = 'Could not connect to server. Please check if XAMPP is running and try again.';
                    } else if (error.message.includes('404')) {
                        errorMessage = 'Login service not found. Please contact administrator.';
                    } else if (error.message.includes('Invalid JSON')) {
                        errorMessage = 'Server returned invalid response. Please try again.';
                    }
                    
                    this.showAlert(errorMessage, 'danger');
                    this.isSubmitting = false;
                    this.setLoadingState(false);
                }
            }
            
            setLoadingState(loading) {
                if (loading) {
                    this.loginButton.disabled = true;
                    this.loginButton.classList.add('btn-loading');
                    this.buttonText.textContent = 'Signing In...';
                } else {
                    this.loginButton.disabled = false;
                    this.loginButton.classList.remove('btn-loading');
                    this.buttonText.textContent = 'Sign In';
                }
            }
            
            showAlert(message, type) {
                this.alertContainer.innerHTML = `
                    <i class="fas fa-${this.getAlertIcon(type)}" aria-hidden="true"></i>
                    <span>${message}</span>
                `;
                this.alertContainer.className = `alert alert-${type} show`;
                this.alertContainer.style.display = 'flex';
                this.alertContainer.setAttribute('aria-live', 'polite');
                
                // Auto-hide non-success alerts after 5 seconds
                if (type !== 'success') {
                    setTimeout(() => {
                        this.hideAlert();
                    }, 5000);
                }
            }
            
            hideAlert() {
                this.alertContainer.classList.remove('show');
                setTimeout(() => {
                    this.alertContainer.style.display = 'none';
                }, 300);
            }
            
            getAlertIcon(type) {
                const icons = {
                    success: 'check-circle',
                    danger: 'exclamation-circle',
                    warning: 'exclamation-triangle',
                    info: 'info-circle'
                };
                return icons[type] || 'info-circle';
            }
            
            showEmailVerificationModal(email, userId) {
                const modal = document.getElementById('emailVerificationModal');
                const emailSpan = document.getElementById('userEmail');
                const resendBtn = document.getElementById('resendVerificationBtn');
                const goToVerificationBtn = document.getElementById('goToVerificationBtn');
                
                emailSpan.textContent = email;
                modal.classList.add('show');
                
                // Store user data for resending verification
                resendBtn.dataset.email = email;
                resendBtn.dataset.userId = userId;
                
                // Setup button click handlers
                resendBtn.onclick = () => this.resendVerificationEmail(email, userId);
                goToVerificationBtn.onclick = () => {
                    const verificationUrl = `../auth/verify-email-code.php?email=${encodeURIComponent(email)}`;
                    window.location.href = verificationUrl;
                };
            }
            
            async resendVerificationEmail(email, userId) {
                const resendBtn = document.getElementById('resendVerificationBtn');
                const originalText = resendBtn.innerHTML;
                
                // Set loading state
                resendBtn.disabled = true;
                resendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
                
                try {
                    const formData = new FormData();
                    formData.append('email', email);
                    formData.append('user_id', userId);
                    
                    const response = await fetch('../api/auth.php?action=sendEmailVerification', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.showAlert('Verification email sent successfully! Please check your email.', 'success');
                        
                        // Update button to show success
                        resendBtn.innerHTML = '<i class="fas fa-check"></i> Email Sent!';
                        resendBtn.style.background = 'linear-gradient(135deg, #10b981, #059669)';
                        
                        // If verification link is provided (development mode), show it
                        if (data.verification_link) {
                            console.log('Verification link (development):', data.verification_link);
                            
                            // For development, auto-verify after 3 seconds
                            setTimeout(() => {
                                window.open(data.verification_link, '_blank');
                            }, 1000);
                        }
                        
                        // Close modal after 3 seconds
                        setTimeout(() => {
                            this.closeEmailModal();
                        }, 3000);
                        
                    } else {
                        this.showAlert(data.message || 'Failed to send verification email', 'danger');
                        
                        // Reset button
                        resendBtn.disabled = false;
                        resendBtn.innerHTML = originalText;
                    }
                    
                } catch (error) {
                    console.error('Resend verification error:', error);
                    this.showAlert('Network error. Please try again.', 'danger');
                    
                    // Reset button
                    resendBtn.disabled = false;
                    resendBtn.innerHTML = originalText;
                }
            }
            
            showOTPVerificationModal(email) {
                console.log('🔔 showOTPVerificationModal called with email:', email);
                const modal = document.getElementById('otpVerificationModal');
                const emailSpan = document.getElementById('otpUserEmail');
                const otpCodeInput = document.getElementById('otpCode');
                const verifyBtn = document.getElementById('verifyOTPBtn');
                const resendBtn = document.getElementById('resendOTPBtn');
                
                if (!modal) {
                    console.error('❌ OTP modal element not found!');
                    return;
                }
                
                console.log('✅ Setting up OTP modal...');
                emailSpan.textContent = email;
                modal.classList.add('show');
                console.log('✅ Modal should now be visible');
                
                // Focus on OTP input
                setTimeout(() => {
                    otpCodeInput.focus();
                    console.log('✅ OTP input focused');
                }, 300);
                
                // Store email for verification
                verifyBtn.dataset.email = email;
                resendBtn.dataset.email = email;
                
                // Setup OTP input formatting
                otpCodeInput.addEventListener('input', (e) => {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 6) {
                        value = value.substring(0, 6);
                    }
                    e.target.value = value;
                    
                    // Auto-verify when 6 digits entered
                    if (value.length === 6) {
                        setTimeout(() => {
                            this.verifyOTPCode(email, value);
                        }, 500);
                    }
                });
                
                // Setup button click handlers
                verifyBtn.onclick = () => {
                    const otp = otpCodeInput.value.trim();
                    if (otp.length === 6) {
                        this.verifyOTPCode(email, otp);
                    } else {
                        this.showAlert('Please enter a valid 6-digit OTP', 'warning');
                    }
                };
                
                resendBtn.onclick = () => this.resendOTP(email);
                
                this.showAlert('🔐 OTP sent to your email! Please check your inbox.', 'info');
            }
            
            async verifyOTPCode(email, otp) {
                const verifyBtn = document.getElementById('verifyOTPBtn');
                const originalText = verifyBtn.innerHTML;
                
                // Set loading state
                verifyBtn.disabled = true;
                verifyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
                
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
                        this.showAlert('✅ OTP verified! Redirecting to dashboard...', 'success');
                        
                        // Update button to show success
                        verifyBtn.innerHTML = '<i class="fas fa-check"></i> Verified!';
                        verifyBtn.style.background = 'linear-gradient(135deg, #10b981, #059669)';
                        
                        // Close modal and redirect
                        setTimeout(() => {
                            this.closeOTPModal();
                            if (data.redirect) {
                                window.location.href = data.redirect;
                            } else {
                                this.showAlert('Login successful but no redirect URL found.', 'warning');
                            }
                        }, 1500);
                        
                    } else {
                        this.showAlert(data.message || 'Invalid OTP. Please try again.', 'danger');
                        
                        // Highlight OTP input as error
                        const otpInput = document.getElementById('otpCode');
                        otpInput.style.borderColor = '#ef4444';
                        otpInput.style.backgroundColor = '#fef2f2';
                        
                        // Clear OTP input
                        otpInput.value = '';
                        otpInput.focus();
                        
                        // Reset input styling after 3 seconds
                        setTimeout(() => {
                            otpInput.style.borderColor = '#e2e8f0';
                            otpInput.style.backgroundColor = 'white';
                        }, 3000);
                        
                        // Reset button
                        verifyBtn.disabled = false;
                        verifyBtn.innerHTML = originalText;
                        verifyBtn.style.background = '';
                    }
                    
                } catch (error) {
                    console.error('OTP verification error:', error);
                    this.showAlert('Network error. Please try again.', 'danger');
                    
                    // Reset button
                    verifyBtn.disabled = false;
                    verifyBtn.innerHTML = originalText;
                    verifyBtn.style.background = '';
                }
            }
            
            async resendOTP(email) {
                const resendBtn = document.getElementById('resendOTPBtn');
                const originalText = resendBtn.innerHTML;
                
                // Set loading state
                resendBtn.disabled = true;
                resendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
                
                try {
                    // Use dedicated resend OTP endpoint instead of full login
                    const formData = new FormData();
                    formData.append('email', email);
                    
                    const response = await fetch('../api/auth.php?action=resendLoginOTP', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.showAlert('✅ New OTP sent to your email!', 'success');
                        
                        // Update button to show success
                        resendBtn.innerHTML = '<i class="fas fa-check"></i> OTP Sent!';
                        resendBtn.style.background = 'linear-gradient(135deg, #10b981, #059669)';
                        
                        // Reset button after 3 seconds
                        setTimeout(() => {
                            resendBtn.disabled = false;
                            resendBtn.innerHTML = originalText;
                            resendBtn.style.background = '';
                        }, 3000);
                        
                    } else if (data.rate_limited) {
                        this.showAlert(data.message || 'Please wait before requesting another OTP', 'warning');
                        
                        // Show countdown if wait time is provided
                        if (data.wait_seconds) {
                            let waitTime = data.wait_seconds;
                            resendBtn.innerHTML = `<i class="fas fa-clock"></i> Wait ${waitTime}s`;
                            
                            const countdown = setInterval(() => {
                                waitTime--;
                                if (waitTime <= 0) {
                                    clearInterval(countdown);
                                    resendBtn.disabled = false;
                                    resendBtn.innerHTML = originalText;
                                } else {
                                    resendBtn.innerHTML = `<i class="fas fa-clock"></i> Wait ${waitTime}s`;
                                }
                            }, 1000);
                        } else {
                            resendBtn.disabled = false;
                            resendBtn.innerHTML = originalText;
                        }
                        
                    } else {
                        this.showAlert(data.message || 'Failed to resend OTP. Please try again.', 'danger');
                        
                        // Reset button
                        resendBtn.disabled = false;
                        resendBtn.innerHTML = originalText;
                    }
                    
                } catch (error) {
                    console.error('Resend OTP error:', error);
                    this.showAlert('Network error. Please try again.', 'danger');
                    
                    // Reset button
                    resendBtn.disabled = false;
                    resendBtn.innerHTML = originalText;
                }
            }
            
            closeOTPModal() {
                const modal = document.getElementById('otpVerificationModal');
                modal.classList.remove('show');
                
                // Reset form elements
                const otpInput = document.getElementById('otpCode');
                const verifyBtn = document.getElementById('verifyOTPBtn');
                const resendBtn = document.getElementById('resendOTPBtn');
                
                otpInput.value = '';
                otpInput.style.borderColor = '#e2e8f0';
                otpInput.style.backgroundColor = 'white';
                
                verifyBtn.disabled = false;
                verifyBtn.innerHTML = '<i class="fas fa-check"></i> <span>Verify OTP</span>';
                verifyBtn.style.background = '';
                
                resendBtn.disabled = false;
                resendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> <span>Resend OTP</span>';
                resendBtn.style.background = '';
            }
            
            closeEmailModal() {
                const modal = document.getElementById('emailVerificationModal');
                modal.classList.remove('show');
                
                // Reset button state
                const resendBtn = document.getElementById('resendVerificationBtn');
                resendBtn.disabled = false;
                resendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> <span>Send Verification Email</span>';
                resendBtn.style.background = '';
            }
        }
        
        // Initialize the form manager when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            try {
                new LoginFormManager();
                window.loginFormManagerInitialized = true;
                console.log('LoginFormManager initialized successfully');
            } catch (error) {
                console.error('LoginFormManager initialization failed:', error);
                window.loginFormManagerInitialized = false;
            }
        });
        
        // Global function for modal close button
        function closeOTPModal() {
            const modal = document.getElementById('otpVerificationModal');
            modal.classList.remove('show');
            
            // Reset form elements
            const otpInput = document.getElementById('otpCode');
            const verifyBtn = document.getElementById('verifyOTPBtn');
            const resendBtn = document.getElementById('resendOTPBtn');
            
            if (otpInput) {
                otpInput.value = '';
                otpInput.style.borderColor = '#e2e8f0';
                otpInput.style.backgroundColor = 'white';
            }
            
            if (verifyBtn) {
                verifyBtn.disabled = false;
                verifyBtn.innerHTML = '<i class="fas fa-check"></i> <span>Verify OTP</span>';
                verifyBtn.style.background = '';
            }
            
            if (resendBtn) {
                resendBtn.disabled = false;
                resendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> <span>Resend OTP</span>';
                resendBtn.style.background = '';
            }
        }
        
        // Global function for modal close button
        function closeEmailModal() {
            const modal = document.getElementById('emailVerificationModal');
            modal.classList.remove('show');
            
            // Reset button state
            const resendBtn = document.getElementById('resendVerificationBtn');
            resendBtn.disabled = false;
            resendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> <span>Send Verification Email</span>';
            resendBtn.style.background = '';
        }
        
        // Close modal when clicking outside
        document.addEventListener('click', (e) => {
            const emailModal = document.getElementById('emailVerificationModal');
            const otpModal = document.getElementById('otpVerificationModal');
            
            if (e.target === emailModal) {
                closeEmailModal();
            }
            
            if (e.target === otpModal) {
                closeOTPModal();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const emailModal = document.getElementById('emailVerificationModal');
                const otpModal = document.getElementById('otpVerificationModal');
                
                if (emailModal && emailModal.classList.contains('show')) {
                    closeEmailModal();
                }
                
                if (otpModal && otpModal.classList.contains('show')) {
                    closeOTPModal();
                }
            }
        });
        
        // Fallback for older browsers or if class initialization fails
        window.addEventListener('load', () => {
            // Check if LoginFormManager was successfully initialized
            const formManagerExists = window.loginFormManagerInitialized || false;
            
            if (!formManagerExists && !document.querySelector('.form-group.focused') && !document.querySelector('.form-group.error')) {
                console.log('Initializing fallback login functionality...');
                
                // Basic form submission fallback
                const form = document.getElementById('loginForm');
                if (form && !form.hasEventListener) {
                    form.hasEventListener = true;
                    form.addEventListener('submit', async function(e) {
                        e.preventDefault();
                        
                        const submitButton = form.querySelector('button[type="submit"]');
                        const alertContainer = document.getElementById('alert');
                        
                        // Simple loading state
                        submitButton.disabled = true;
                        submitButton.textContent = 'Signing In...';
                        
                        try {
                            const formData = new FormData(form);
                            const response = await fetch('../api/auth.php?action=login', {
                                method: 'POST',
                                body: formData
                            });
                            
                            const data = await response.json();
                            
                            if (data.success) {
                                alertContainer.innerHTML = '<i class="fas fa-check-circle"></i> Login successful! Redirecting...';
                                alertContainer.className = 'alert alert-success show';
                                alertContainer.style.display = 'flex';
                                
                                setTimeout(() => {
                                    if (data.redirect) {
                                        window.location.href = data.redirect;
                                    }
                                }, 1500);
                            } else {
                                alertContainer.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + (data.message || 'Login failed');
                                alertContainer.className = 'alert alert-danger show';
                                alertContainer.style.display = 'flex';
                                
                                submitButton.disabled = false;
                                submitButton.textContent = 'Sign In';
                            }
                        } catch (error) {
                            console.error('Login error:', error);
                            alertContainer.innerHTML = '<i class="fas fa-exclamation-circle"></i> Network error. Please try again.';
                            alertContainer.className = 'alert alert-danger show';
                            alertContainer.style.display = 'flex';
                            
                            submitButton.disabled = false;
                            submitButton.textContent = 'Sign In';
                        }
                    });
                }
            }
        });
        
        // Service Worker for offline functionality (optional enhancement)
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(() => {
                    // Service worker registration failed - not critical
                });
            });
        }
    </script>
</body>
</html>
