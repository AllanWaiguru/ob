<?php
session_start();

// Store user info for goodbye message before destroying session
$user_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'User';
$user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : '';

// Destroy all session data
session_unset();
session_destroy();

// Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logged Out - Digital Occurrence Book</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --success-color: #2ecc71;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .logout-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 50px;
            text-align: center;
            max-width: 500px;
            width: 90%;
            margin: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .logout-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }
        
        .logout-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--secondary-color), #2980b9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            color: white;
            font-size: 2rem;
            box-shadow: 0 10px 20px rgba(52, 152, 219, 0.3);
        }
        
        .success-animation {
            animation: bounce 1s ease-in-out;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }
        
        .btn-primary {
            background-color: var(--secondary-color);
            border: none;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .user-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            border-left: 4px solid var(--secondary-color);
        }
        
        .security-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            font-size: 0.9rem;
        }
        
        .countdown {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 10px;
        }
        
        .features-list {
            text-align: left;
            margin: 25px 0;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            padding: 8px 0;
        }
        
        .feature-item i {
            color: var(--success-color);
            margin-right: 10px;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="logout-icon success-animation">
            <i class="fas fa-check"></i>
        </div>
        
        <h1 class="display-6 fw-bold text-primary mb-3">Successfully Logged Out</h1>
        
        <div class="user-info">
            <i class="fas fa-user-check text-success me-2"></i>
            <strong>Goodbye, <?php echo htmlspecialchars($user_name); ?>!</strong>
            <?php if ($user_type): ?>
                <br><small class="text-muted"><?php echo ucfirst($user_type); ?> Account</small>
            <?php endif; ?>
        </div>
        
        <p class="text-muted mb-4">
            You have been successfully logged out of the Digital Occurrence Book system.
            Your session has been securely terminated.
        </p>
        
        <div class="security-notice">
            <i class="fas fa-shield-alt text-warning me-2"></i>
            <strong>Security Notice:</strong> For your security, please close your browser if you're on a shared computer.
        </div>
        
        <div class="features-list">
            <h6 class="mb-3 text-center">Why you'll love coming back:</h6>
            <div class="feature-item">
                <i class="fas fa-bolt"></i>
                <span>Quick incident reporting</span>
            </div>
            <div class="feature-item">
                <i class="fas fa-chart-line"></i>
                <span>Real-time report tracking</span>
            </div>
            <div class="feature-item">
                <i class="fas fa-mobile-alt"></i>
                <span>Mobile-friendly interface</span>
            </div>
            <div class="feature-item">
                <i class="fas fa-lock"></i>
                <span>Secure data protection</span>
            </div>
        </div>
        
        <div class="d-grid gap-2">
            <a href="../login.php" class="btn btn-primary">
                <i class="fas fa-sign-in-alt me-2"></i>Sign In Again
            </a>
            <a href="../index.php" class="btn btn-outline-secondary">
                <i class="fas fa-home me-2"></i>Back to Homepage
            </a>
        </div>
        
        <div class="countdown">
            <i class="fas fa-info-circle me-1"></i>
            Redirecting to login page in <span id="countdown">10</span> seconds...
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Countdown timer for automatic redirect
        let countdown = 10;
        const countdownElement = document.getElementById('countdown');
        
        const timer = setInterval(function() {
            countdown--;
            countdownElement.textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(timer);
                window.location.href = '../login.php';
            }
        }, 1000);
        
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effects to buttons
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                
                button.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
            
            // Prevent immediate redirect if user is interacting
            document.addEventListener('mousemove', function() {
                if (countdown < 3) {
                    countdown = 3; // Reset to 3 seconds if user is active
                    countdownElement.textContent = countdown;
                }
            });
            
            // Keyboard interaction also resets timer
            document.addEventListener('keydown', function() {
                if (countdown < 3) {
                    countdown = 3;
                    countdownElement.textContent = countdown;
                }
            });
        });
    </script>
</body>
</html>