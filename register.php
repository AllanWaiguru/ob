<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Digital Occurrence Book</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .register-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 1000px;
            margin: 20px auto;
        }
        
        .register-left {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .register-right {
            padding: 50px;
        }
        
        .btn-primary {
            background-color: var(--secondary-color);
            border: none;
            padding: 12px 30px;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
        }
        
        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        
        .password-strength {
            height: 5px;
            margin-top: 5px;
            border-radius: 5px;
        }
        
        .strength-weak { background-color: #e74c3c; width: 25%; }
        .strength-fair { background-color: #f39c12; width: 50%; }
        .strength-good { background-color: #3498db; width: 75%; }
        .strength-strong { background-color: #2ecc71; width: 100%; }
        
        .login-link {
            color: var(--secondary-color);
            text-decoration: none;
        }
        
        .login-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <div class="row g-0">
                <!-- Left Side - Information -->
                <div class="col-lg-6 register-left">
                    <div class="text-center mb-4">
                        <i class="fas fa-book fa-3x mb-3"></i>
                        <h2 class="fw-bold">Digital Occurrence Book</h2>
                    </div>
                    <h4 class="fw-bold mb-4">Create Your Account</h4>
                    <p class="mb-4">Join our secure incident reporting system to efficiently manage and track occurrences in your organization.</p>
                    
                    <div class="features-list">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-shield-alt me-3 fa-lg"></i>
                            <span>Secure role-based access</span>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-file-export me-3 fa-lg"></i>
                            <span>Comprehensive reporting</span>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-chart-bar me-3 fa-lg"></i>
                            <span>Analytics dashboard</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-mobile-alt me-3 fa-lg"></i>
                            <span>Mobile responsive</span>
                        </div>
                    </div>
                </div>
                
                <!-- Right Side - Registration Form -->
                <div class="col-lg-6 register-right">
                    <div class="text-center mb-4">
                        <h3 class="fw-bold text-primary">Create Account</h3>
                        <p class="text-muted">Fill in your details to register</p>
                    </div>
                    
                    <?php
                    // Database connection
                    $servername = "localhost";
                    $username = "root";
                    $password = "";
                    $dbname = "ob";

                    // Create connection
                    $conn = new mysqli($servername, $username, $password, $dbname);

                    // Check connection
                    if ($conn->connect_error) {
                        die("Connection failed: " . $conn->connect_error);
                    }

                    // Process form submission
                    if ($_SERVER["REQUEST_METHOD"] == "POST") {
                        // Get form data
                        $full_name = trim($_POST['full_name']);
                        $username = trim($_POST['username']);
                        $email = trim($_POST['email']);
                        $password = $_POST['password'];
                        $confirm_password = $_POST['confirm_password'];
                        $user_type = $_POST['user_type'];
                        $department = trim($_POST['department']);
                        $phone = trim($_POST['phone']);
                        
                        // Initialize error array
                        $errors = [];
                        
                        // Validate inputs
                        if (empty($full_name)) {
                            $errors[] = "Full name is required.";
                        }
                        
                        if (empty($username)) {
                            $errors[] = "Username is required.";
                        } elseif (strlen($username) < 3) {
                            $errors[] = "Username must be at least 3 characters long.";
                        }
                        
                        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $errors[] = "Valid email is required.";
                        }
                        
                        if (empty($password)) {
                            $errors[] = "Password is required.";
                        } elseif (strlen($password) < 8) {
                            $errors[] = "Password must be at least 8 characters long.";
                        }
                        
                        if ($password !== $confirm_password) {
                            $errors[] = "Passwords do not match.";
                        }
                        
                        // Check if username or email already exists
                        $check_sql = "SELECT id FROM users WHERE username = ? OR email = ?";
                        $check_stmt = $conn->prepare($check_sql);
                        $check_stmt->bind_param("ss", $username, $email);
                        $check_stmt->execute();
                        $check_result = $check_stmt->get_result();
                        
                        if ($check_result->num_rows > 0) {
                            $errors[] = "Username or email already exists.";
                        }
                        $check_stmt->close();
                        
                        // If no errors, insert user
                        if (empty($errors)) {
                            // Hash password
                            $password_hash = password_hash($password, PASSWORD_DEFAULT);
                            
                            // Insert into database
                            $sql = "INSERT INTO users (username, email, password_hash, user_type, full_name, department, phone) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?)";
                            
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("sssssss", $username, $email, $password_hash, $user_type, $full_name, $department, $phone);
                            
                            if ($stmt->execute()) {
                                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <strong>Success!</strong> Your account has been created successfully.
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                      </div>';
                                
                                // Clear form
                                $_POST = array();
                            } else {
                                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="fas fa-exclamation-circle me-2"></i>
                                        <strong>Error!</strong> Failed to create account. Please try again.
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                      </div>';
                            }
                            
                            $stmt->close();
                        } else {
                            // Display errors
                            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Please fix the following errors:</strong>
                                    <ul class="mb-0">';
                            foreach ($errors as $error) {
                                echo '<li>' . htmlspecialchars($error) . '</li>';
                            }
                            echo '</ul>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                  </div>';
                        }
                    }
                    
                    $conn->close();
                    ?>
                    
                    <form action="register.php" method="POST" id="registrationForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                           value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" 
                                           required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                           required>
                                    <div class="form-text" id="usernameFeedback"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                   required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <div class="password-strength" id="passwordStrength"></div>
                                    <div class="form-text">Minimum 8 characters</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <div class="form-text" id="confirmFeedback"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="user_type" class="form-label">Account Type <span class="text-danger">*</span></label>
                                    <select class="form-select" id="user_type" name="user_type" required>
                                        <option value="user" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'user') ? 'selected' : ''; ?>>User</option>
                                        <option value="admin" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'admin') ? 'selected' : ''; ?>>Administrator</option>
                                    </select>
                                    <div class="form-text">Admins have full system access</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="department" class="form-label">Department</label>
                                    <input type="text" class="form-control" id="department" name="department" 
                                           value="<?php echo isset($_POST['department']) ? htmlspecialchars($_POST['department']) : ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                        </div>
                        
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-user-plus me-2"></i>Create Account
                            </button>
                        </div>
                        
                        <div class="text-center">
                            <p class="mb-0">Already have an account? 
                                <a href="login.php" class="login-link">Sign in here</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrength');
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/\d/)) strength++;
            if (password.match(/[^a-zA-Z\d]/)) strength++;
            
            strengthBar.className = 'password-strength';
            if (password.length === 0) {
                strengthBar.style.width = '0';
            } else if (strength === 1) {
                strengthBar.classList.add('strength-weak');
            } else if (strength === 2) {
                strengthBar.classList.add('strength-fair');
            } else if (strength === 3) {
                strengthBar.classList.add('strength-good');
            } else if (strength === 4) {
                strengthBar.classList.add('strength-strong');
            }
        });
        
        // Password confirmation check
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirm = this.value;
            const feedback = document.getElementById('confirmFeedback');
            
            if (confirm.length === 0) {
                feedback.textContent = '';
                this.classList.remove('is-invalid', 'is-valid');
            } else if (password === confirm) {
                feedback.textContent = 'Passwords match';
                feedback.className = 'form-text text-success';
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                feedback.textContent = 'Passwords do not match';
                feedback.className = 'form-text text-danger';
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        });
        
        // Username availability check (basic)
        document.getElementById('username').addEventListener('blur', function() {
            const username = this.value;
            const feedback = document.getElementById('usernameFeedback');
            
            if (username.length < 3) {
                feedback.textContent = 'Username must be at least 3 characters';
                feedback.className = 'form-text text-danger';
                this.classList.add('is-invalid');
            } else {
                feedback.textContent = 'Username available';
                feedback.className = 'form-text text-success';
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
        });
        
        // Form validation
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Please make sure your passwords match.');
                return false;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long.');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>