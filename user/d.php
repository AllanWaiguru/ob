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

// Get user's incidents
$user_id = $_SESSION['user_id'];
$incidents_sql = "SELECT * FROM incidents WHERE reporter_name = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($incidents_sql);
$stmt->bind_param("s", $_SESSION['full_name']);
$stmt->execute();
$incidents_result = $stmt->get_result();

// Count incidents by status
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'reported' THEN 1 ELSE 0 END) as reported,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
    FROM incidents WHERE reporter_name = ?";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("s", $_SESSION['full_name']);
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
    <title>User Dashboard - Digital Occurrence Book</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            border-top: 4px solid var(--secondary-color);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card.reported { border-top-color: var(--warning-color); }
        .stat-card.in-progress { border-top-color: var(--secondary-color); }
        .stat-card.resolved { border-top-color: var(--success-color); }
        .stat-card.total { border-top-color: var(--primary-color); }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .incident-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            border-left: 4px solid var(--secondary-color);
        }
        
        .incident-card.reported { border-left-color: var(--warning-color); }
        .incident-card.in_progress { border-left-color: var(--secondary-color); }
        .incident-card.resolved { border-left-color: var(--success-color); }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .badge-reported { background-color: #fff3cd; color: #856404; }
        .badge-in_progress { background-color: #cce7ff; color: #004085; }
        .badge-resolved { background-color: #d4edda; color: #155724; }
        
        .btn-primary {
            background-color: var(--secondary-color);
            border: none;
            padding: 10px 20px;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
        }
        
        .user-welcome {
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #dee2e6;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
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
                            <a href="user_dashboard.php" class="nav-link active">
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
                            <a href="profile.php" class="nav-link">
                                <i class="fas fa-user"></i> Profile
                            </a>
                        </li>
                    </ul>
                    
                    <div class="user-welcome">
                        <small>Welcome back,</small>
                        <div class="fw-bold"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                        <small class="text-muted"><?php echo ucfirst($_SESSION['user_type']); ?></small>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-10 main-content">
                <!-- Dashboard Header -->
                <div class="dashboard-header">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="display-6 fw-bold">User Dashboard</h1>
                            <p class="lead mb-0">Manage and track your incident reports</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="report.php" class="btn btn-light btn-lg">
                                <i class="fas fa-plus-circle me-2"></i>Report New Incident
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-5">
                    <div class="col-md-3 mb-4">
                        <div class="stat-card total">
                            <i class="fas fa-file-alt fa-2x text-primary"></i>
                            <div class="stat-number"><?php echo $stats['total']; ?></div>
                            <div class="text-muted">Total Reports</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="stat-card reported">
                            <i class="fas fa-clock fa-2x text-warning"></i>
                            <div class="stat-number"><?php echo $stats['reported']; ?></div>
                            <div class="text-muted">Reported</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="stat-card in-progress">
                            <i class="fas fa-sync-alt fa-2x text-info"></i>
                            <div class="stat-number"><?php echo $stats['in_progress']; ?></div>
                            <div class="text-muted">In Progress</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="stat-card resolved">
                            <i class="fas fa-check-circle fa-2x text-success"></i>
                            <div class="stat-number"><?php echo $stats['resolved']; ?></div>
                            <div class="text-muted">Resolved</div>
                        </div>
                    </div>
                </div>

                <!-- Recent Incidents -->
                <div class="row">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h3>Recent Incident Reports</h3>
                            <a href="my_reports.php" class="btn btn-outline-primary">View All Reports</a>
                        </div>

                        <?php if ($incidents_result->num_rows > 0): ?>
                            <div class="incident-list">
                                <?php while($incident = $incidents_result->fetch_assoc()): ?>
                                    <div class="incident-card <?php echo $incident['status']; ?>">
                                        <div class="row align-items-center">
                                            <div class="col-md-8">
                                                <div class="d-flex align-items-center mb-2">
                                                    <h5 class="mb-0 me-3"><?php echo htmlspecialchars($incident['incident_type']); ?></h5>
                                                    <span class="status-badge badge-<?php echo $incident['status']; ?>">
                                                        <?php 
                                                        $status_text = str_replace('_', ' ', $incident['status']);
                                                        echo ucwords($status_text); 
                                                        ?>
                                                    </span>
                                                </div>
                                                <p class="text-muted mb-2">
                                                    <i class="fas fa-map-marker-alt me-1"></i>
                                                    <?php echo htmlspecialchars($incident['location_name']); ?>
                                                </p>
                                                <p class="mb-2"><?php echo htmlspecialchars(substr($incident['description'], 0, 150)); ?>...</p>
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    Reported on <?php echo date('M j, Y g:i A', strtotime($incident['created_at'])); ?>
                                                </small>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <div class="btn-group">
                                                    <a href="view_report.php?id=<?php echo $incident['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-eye me-1"></i>View
                                                    </a>
                                                    <?php if ($incident['status'] == 'reported'): ?>
                                                        <a href="edit_report.php?id=<?php echo $incident['id']; ?>" class="btn btn-outline-secondary btn-sm">
                                                            <i class="fas fa-edit me-1"></i>Edit
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-file-alt"></i>
                                <h4>No Incident Reports Yet</h4>
                                <p class="text-muted">You haven't submitted any incident reports yet.</p>
                                <a href="report.php" class="btn btn-primary">
                                    <i class="fas fa-plus-circle me-2"></i>Report Your First Incident
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mt-5">
                    <div class="col-12">
                        <h4 class="mb-4">Quick Actions</h4>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="card text-center h-100">
                                    <div class="card-body">
                                        <i class="fas fa-plus-circle fa-3x text-primary mb-3"></i>
                                        <h5>Report Incident</h5>
                                        <p class="text-muted">Submit a new incident report</p>
                                        <a href="report.php" class="btn btn-primary">Get Started</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card text-center h-100">
                                    <div class="card-body">
                                        <i class="fas fa-download fa-3x text-success mb-3"></i>
                                        <h5>Export Reports</h5>
                                        <p class="text-muted">Download your reports in PDF format</p>
                                        <a href="export_reports.php" class="btn btn-success">Export</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card text-center h-100">
                                    <div class="card-body">
                                        <i class="fas fa-chart-bar fa-3x text-info mb-3"></i>
                                        <h5>View Analytics</h5>
                                        <p class="text-muted">See statistics and trends</p>
                                        <a href="analytics.php" class="btn btn-info">View Analytics</a>
                                    </div>
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
        // Simple dashboard interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-dismiss alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
            
            // Add smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    document.querySelector(this.getAttribute('href')).scrollIntoView({
                        behavior: 'smooth'
                    });
                });
            });
        });
    </script>
</body>
</html>