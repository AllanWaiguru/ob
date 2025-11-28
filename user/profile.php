<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

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

// Get user data
$user_sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

// Get user statistics
$stats_sql = "SELECT 
    COUNT(*) as total_reports,
    SUM(CASE WHEN status = 'reported' THEN 1 ELSE 0 END) as reported,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
    MAX(created_at) as last_report
    FROM incidents WHERE user_id = ?";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $_SESSION['user_id']);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();

$stmt->close();
$stats_stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Digital Occurrence Book</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }
        
        .navbar {
            background-color: var(--primary-color);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .sidebar {
            background: var(--primary-color);
            color: white;
            min-height: calc(100vh - 56px);
            padding: 0;
            box-shadow: 3px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar .nav-link {
            color: #ecf0f1;
            padding: 15px 20px;
            border-left: 4px solid transparent;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: rgba(255,255,255,0.1);
            border-left-color: var(--secondary-color);
            color: white;
        }
        
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }
        
        .main-content {
            padding: 30px;
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 40px;
            border-radius: 15px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            transform: translate(100px, -100px);
        }
        
        .profile-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 150px;
            height: 150px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            transform: translate(-50px, 50px);
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 4px solid white;
            margin-bottom: 20px;
        }
        
        .profile-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border: 1px solid #e9ecef;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
            color: var(--primary-color);
        }
        
        .btn-primary {
            background-color: var(--secondary-color);
            border: none;
            padding: 10px 25px;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
        }
        
        .btn-outline-primary {
            border-color: var(--secondary-color);
            color: var(--secondary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .info-item {
            padding: 15px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .info-value {
            color: #6c757d;
        }
        
        .badge-role {
            background: linear-gradient(135deg, var(--secondary-color), #2980b9);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        .activity-timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .activity-timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e9ecef;
        }
        
        .activity-item {
            position: relative;
            margin-bottom: 25px;
        }
        
        .activity-item::before {
            content: '';
            position: absolute;
            left: -23px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--secondary-color);
            border: 2px solid white;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            background: var(--secondary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-right: 15px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="user_dashboard.php">
                <i class="fas fa-book me-2"></i>
                Digital Occurrence Book
            </a>
            <div class="d-flex align-items-center">
                <div class="dropdown">
                    <button class="btn btn-outline-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-2"></i><?php echo htmlspecialchars($_SESSION['full_name']); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-2 sidebar">
                <div class="d-flex flex-column flex-shrink-0 p-3">
                    <ul class="nav nav-pills flex-column mb-auto">
                        <li class="nav-item">
                            <a href="user_dashboard.php" class="nav-link">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li>
                            <a href="report.php" class="nav-link">
                                <i class="fas fa-plus-circle"></i> Report Incident
                            </a>
                        </li>
                        <li>
                            <a href="my_reports.php" class="nav-link">
                                <i class="fas fa-list"></i> My Reports
                            </a>
                        </li>
                        <li>
                            <a href="profile.php" class="nav-link active">
                                <i class="fas fa-user"></i> Profile
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-10 main-content">
                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center">
                                <div class="profile-avatar">
                                    <i class="fas fa-user fa-3x text-white"></i>
                                </div>
                                <div class="ms-4">
                                    <h1 class="display-6 fw-bold mb-2"><?php echo htmlspecialchars($user['full_name']); ?></h1>
                                    <span class="badge-role">
                                        <i class="fas fa-shield-alt me-1"></i>
                                        <?php echo ucfirst($user['user_type']); ?>
                                    </span>
                                    <p class="mb-0 mt-2 opacity-75">
                                        <i class="fas fa-envelope me-1"></i>
                                        <?php echo htmlspecialchars($user['email']); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="edit_profile.php" class="btn btn-light btn-lg">
                                <i class="fas fa-edit me-2"></i>Edit Profile
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-5">
                    <div class="col-md-3 mb-4">
                        <div class="stat-card">
                            <i class="fas fa-file-alt fa-2x text-primary"></i>
                            <div class="stat-number"><?php echo $stats['total_reports'] ?? 0; ?></div>
                            <div class="text-muted">Total Reports</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="stat-card">
                            <i class="fas fa-clock fa-2x text-warning"></i>
                            <div class="stat-number"><?php echo $stats['reported'] ?? 0; ?></div>
                            <div class="text-muted">Pending</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="stat-card">
                            <i class="fas fa-sync-alt fa-2x text-info"></i>
                            <div class="stat-number"><?php echo $stats['in_progress'] ?? 0; ?></div>
                            <div class="text-muted">In Progress</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="stat-card">
                            <i class="fas fa-check-circle fa-2x text-success"></i>
                            <div class="stat-number"><?php echo $stats['resolved'] ?? 0; ?></div>
                            <div class="text-muted">Resolved</div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Personal Information -->
                    <div class="col-lg-6 mb-4">
                        <div class="profile-card">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4 class="mb-0">
                                    <i class="fas fa-id-card me-2 text-primary"></i>
                                    Personal Information
                                </h4>
                                <a href="edit_profile.php" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-edit me-1"></i>Edit
                                </a>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Full Name</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['full_name']); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Username</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['username']); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Email Address</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Phone Number</div>
                                <div class="info-value">
                                    <?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : '<span class="text-muted">Not provided</span>'; ?>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Department</div>
                                <div class="info-value">
                                    <?php echo !empty($user['department']) ? htmlspecialchars($user['department']) : '<span class="text-muted">Not specified</span>'; ?>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Account Type</div>
                                <div class="info-value">
                                    <span class="badge bg-primary"><?php echo ucfirst($user['user_type']); ?></span>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Member Since</div>
                                <div class="info-value">
                                    <i class="fas fa-calendar me-1 text-muted"></i>
                                    <?php echo date('F j, Y', strtotime($user['created_at'])); ?>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Last Updated</div>
                                <div class="info-value">
                                    <i class="fas fa-clock me-1 text-muted"></i>
                                    <?php echo date('F j, Y g:i A', strtotime($user['updated_at'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Account Security -->
                    <div class="col-lg-6 mb-4">
                        <div class="profile-card">
                            <h4 class="mb-4">
                                <i class="fas fa-shield-alt me-2 text-success"></i>
                                Account Security
                            </h4>
                            
                            <div class="info-item">
                                <div class="info-label">Password</div>
                                <div class="info-value">
                                    <span class="text-muted">••••••••</span>
                                    <a href="change_password.php" class="btn btn-outline-secondary btn-sm ms-3">
                                        <i class="fas fa-key me-1"></i>Change Password
                                    </a>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Account Status</div>
                                <div class="info-value">
                                    <?php if ($user['is_active']): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle me-1"></i>Active
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">
                                            <i class="fas fa-times-circle me-1"></i>Inactive
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Two-Factor Authentication</div>
                                <div class="info-value">
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-times-circle me-1"></i>Disabled
                                    </span>
                                    <a href="#" class="btn btn-outline-secondary btn-sm ms-3">
                                        <i class="fas fa-cog me-1"></i>Enable 2FA
                                    </a>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Login Sessions</div>
                                <div class="info-value">
                                    <a href="login_sessions.php" class="btn btn-outline-info btn-sm">
                                        <i class="fas fa-desktop me-1"></i>Manage Sessions
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <div class="profile-card">
                            <h4 class="mb-4">
                                <i class="fas fa-history me-2 text-info"></i>
                                Recent Activity
                            </h4>
                            
                            <div class="activity-timeline">
                                <?php if ($stats['last_report']): ?>
                                    <div class="activity-item">
                                        <div class="d-flex align-items-start">
                                            <div class="activity-icon">
                                                <i class="fas fa-file-export"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-1">Last Report Submitted</h6>
                                                <p class="mb-1 text-muted">You submitted an incident report</p>
                                                <small class="text-muted">
                                                    <?php echo date('M j, Y g:i A', strtotime($stats['last_report'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="activity-item">
                                    <div class="d-flex align-items-start">
                                        <div class="activity-icon">
                                            <i class="fas fa-user-edit"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">Profile Updated</h6>
                                            <p class="mb-1 text-muted">Your profile information was updated</p>
                                            <small class="text-muted">
                                                <?php echo date('M j, Y g:i A', strtotime($user['updated_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="activity-item">
                                    <div class="d-flex align-items-start">
                                        <div class="activity-icon">
                                            <i class="fas fa-user-plus"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">Account Created</h6>
                                            <p class="mb-1 text-muted">You joined the Digital Occurrence Book</p>
                                            <small class="text-muted">
                                                <?php echo date('M j, Y g:i A', strtotime($user['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row">
                    <div class="col-12">
                        <div class="profile-card">
                            <h4 class="mb-4">
                                <i class="fas fa-rocket me-2 text-warning"></i>
                                Quick Actions
                            </h4>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <a href="report.php" class="btn btn-primary w-100 h-100 py-3">
                                        <i class="fas fa-plus-circle fa-2x mb-2"></i><br>
                                        New Report
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="my_reports.php" class="btn btn-outline-primary w-100 h-100 py-3">
                                        <i class="fas fa-list fa-2x mb-2"></i><br>
                                        View Reports
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="export_reports.php" class="btn btn-outline-success w-100 h-100 py-3">
                                        <i class="fas fa-download fa-2x mb-2"></i><br>
                                        Export Data
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="change_password.php" class="btn btn-outline-warning w-100 h-100 py-3">
                                        <i class="fas fa-key fa-2x mb-2"></i><br>
                                        Change Password
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple animations and interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Add smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // Add loading states for buttons
            document.querySelectorAll('a.btn').forEach(button => {
                button.addEventListener('click', function() {
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Loading...';
                    this.disabled = true;
                    
                    // Reset after 3 seconds (in case of error)
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.disabled = false;
                    }, 3000);
                });
            });
        });

        // Simple password strength checker (for change password page)
        function checkPasswordStrength(password) {
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/\d/)) strength++;
            if (password.match(/[^a-zA-Z\d]/)) strength++;
            return strength;
        }
    </script>
</body>
</html>