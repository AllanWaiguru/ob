<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
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

// Get dashboard statistics
$stats_sql = "SELECT 
    COUNT(*) as total_reports,
    SUM(CASE WHEN status = 'reported' THEN 1 ELSE 0 END) as reported,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
    COUNT(DISTINCT reporter_name) as unique_reporters,
    COUNT(DISTINCT incident_type) as incident_types
    FROM incidents";

$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Get recent incidents
$recent_sql = "SELECT i.*, u.department, u.phone 
               FROM incidents i 
               LEFT JOIN users u ON i.reporter_name = u.full_name 
               ORDER BY i.created_at DESC 
               LIMIT 5";
$recent_result = $conn->query($recent_sql);

// Get incident types distribution
$types_sql = "SELECT incident_type, COUNT(*) as count 
              FROM incidents 
              GROUP BY incident_type 
              ORDER BY count DESC 
              LIMIT 6";
$types_result = $conn->query($types_sql);

// Get monthly report counts for chart
$monthly_sql = "SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    COUNT(*) as count
    FROM incidents 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
    LIMIT 6";
$monthly_result = $conn->query($monthly_sql);

$monthly_data = [];
while ($row = $monthly_result->fetch_assoc()) {
    $monthly_data[] = $row;
}
$monthly_data = array_reverse($monthly_data); // Oldest to newest

// Get users statistics
$users_sql = "SELECT 
    COUNT(*) as total_users,
    SUM(CASE WHEN user_type = 'admin' THEN 1 ELSE 0 END) as admins,
    SUM(CASE WHEN user_type = 'user' THEN 1 ELSE 0 END) as regular_users,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users
    FROM users";
$users_result = $conn->query($users_sql);
$users_stats = $users_result->fetch_assoc();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Digital Occurrence Book</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --info-color: #17a2b8;
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
            border-radius: 15px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-header::before {
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
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            border-top: 4px solid var(--secondary-color);
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card.total { border-top-color: var(--primary-color); }
        .stat-card.reported { border-top-color: var(--warning-color); }
        .stat-card.progress { border-top-color: var(--info-color); }
        .stat-card.resolved { border-top-color: var(--success-color); }
        .stat-card.users { border-top-color: #9b59b6; }
        .stat-card.types { border-top-color: #1abc9c; }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 1.5rem;
        }
        
        .stat-icon.total { background: rgba(44, 62, 80, 0.1); color: var(--primary-color); }
        .stat-icon.reported { background: rgba(243, 156, 18, 0.1); color: var(--warning-color); }
        .stat-icon.progress { background: rgba(23, 162, 184, 0.1); color: var(--info-color); }
        .stat-icon.resolved { background: rgba(46, 204, 113, 0.1); color: var(--success-color); }
        .stat-icon.users { background: rgba(155, 89, 182, 0.1); color: #9b59b6; }
        .stat-icon.types { background: rgba(26, 188, 156, 0.1); color: #1abc9c; }
        
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            height: 100%;
        }
        
        .recent-incidents {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .incident-item {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            transition: background-color 0.2s;
        }
        
        .incident-item:hover {
            background-color: #f8f9fa;
        }
        
        .incident-item:last-child {
            border-bottom: none;
        }
        
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
        
        .admin-badge {
            background: linear-gradient(135deg, var(--warning-color), #e67e22);
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .quick-actions .card {
            transition: transform 0.2s;
            border: none;
            border-radius: 15px;
            overflow: hidden;
        }
        
        .quick-actions .card:hover {
            transform: translateY(-3px);
        }
        
        .quick-actions .card-body {
            padding: 25px;
            text-align: center;
        }
        
        .system-health {
            background: linear-gradient(135deg, var(--success-color), #27ae60);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="d.php">
                <i class="fas fa-book me-2"></i>
                Digital Occurrence Book <span class="admin-badge ms-2">Admin</span>
            </a>
            <div class="d-flex align-items-center">
                <span class="navbar-text me-3">
                    <i class="fas fa-user-shield me-1"></i>
                    <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                </span>
                <div class="dropdown">
                    <button class="btn btn-outline-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                        <i class="fas fa-cog me-2"></i>Settings
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="../profile.php"><i class="fas fa-user me-2"></i>My Profile</a></li>
                        <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cogs me-2"></i>System Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="stations.php"><i class="fas fa-sign-out-alt me-2"></i>Add Station</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="v.php"><i class="fas fa-sign-out-alt me-2"></i>View stations</a></li>
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
                            <a href="d.php" class="nav-link active">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li>
                            <a href="reports.php" class="nav-link">
                                <i class="fas fa-list"></i> All Reports
                            </a>
                        </li>
                        <li>
                            <a href="users.php" class="nav-link">
                                <i class="fas fa-users"></i> User Management
                            </a>
                        </li>
                        <li>
                            <a href="analytics.php" class="nav-link">
                                <i class="fas fa-chart-bar"></i> Analytics
                            </a>
                        </li>
                        <li>
                            <a href="settings.php" class="nav-link">
                                <i class="fas fa-cogs"></i> System Settings
                            </a>
                        </li>
                        <li>
                            <a href="backup.php" class="nav-link">
                                <i class="fas fa-database"></i> Backup & Restore
                            </a>
                        </li>
                        <li>
                            <a href="logs.php" class="nav-link">
                                <i class="fas fa-clipboard-list"></i> System Logs
                            </a>
                        </li>
                    </ul>
                    
                    <div class="mt-4 p-3" style="background: rgba(255,255,255,0.1); border-radius: 10px;">
                        <small class="text-muted">System Status</small>
                        <div class="d-flex align-items-center mt-2">
                            <div class="system-health badge rounded-pill px-3">
                                <i class="fas fa-check-circle me-1"></i> Operational
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-10 main-content">
                <!-- Dashboard Header -->
                <div class="dashboard-header">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="display-6 fw-bold">Admin Dashboard</h1>
                            <p class="lead mb-0">Monitor and manage the Digital Occurrence Book system</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="btn-group">
                                <a href="reports.php" class="btn btn-light">
                                    <i class="fas fa-list me-2"></i>View All Reports
                                </a>
                                <a href="users.php" class="btn btn-outline-light">
                                    <i class="fas fa-users me-2"></i>Manage Users
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-5">
                    <div class="col-md-4 col-lg-2 mb-4">
                        <div class="stat-card total">
                            <div class="stat-icon total">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="stat-number"><?php echo $stats['total_reports']; ?></div>
                            <div class="text-muted">Total Reports</div>
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-2 mb-4">
                        <div class="stat-card reported">
                            <div class="stat-icon reported">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-number"><?php echo $stats['reported']; ?></div>
                            <div class="text-muted">Reported</div>
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-2 mb-4">
                        <div class="stat-card progress">
                            <div class="stat-icon progress">
                                <i class="fas fa-sync-alt"></i>
                            </div>
                            <div class="stat-number"><?php echo $stats['in_progress']; ?></div>
                            <div class="text-muted">In Progress</div>
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-2 mb-4">
                        <div class="stat-card resolved">
                            <div class="stat-icon resolved">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-number"><?php echo $stats['resolved']; ?></div>
                            <div class="text-muted">Resolved</div>
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-2 mb-4">
                        <div class="stat-card users">
                            <div class="stat-icon users">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-number"><?php echo $stats['unique_reporters']; ?></div>
                            <div class="text-muted">Reporters</div>
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-2 mb-4">
                        <div class="stat-card types">
                            <div class="stat-icon types">
                                <i class="fas fa-tags"></i>
                            </div>
                            <div class="stat-number"><?php echo $stats['incident_types']; ?></div>
                            <div class="text-muted">Incident Types</div>
                        </div>
                    </div>
                </div>

                <div class="row mb-5">
                    <!-- Charts Column -->
                    <div class="col-lg-8">
                        <div class="row">
                            <!-- Monthly Reports Chart -->
                            <div class="col-md-6 mb-4">
                                <div class="chart-container">
                                    <h5 class="mb-4">
                                        <i class="fas fa-chart-line me-2 text-primary"></i>
                                        Reports Trend (6 Months)
                                    </h5>
                                    <canvas id="monthlyChart" height="250"></canvas>
                                </div>
                            </div>
                            
                            <!-- Incident Types Chart -->
                            <div class="col-md-6 mb-4">
                                <div class="chart-container">
                                    <h5 class="mb-4">
                                        <i class="fas fa-chart-pie me-2 text-success"></i>
                                        Incident Types
                                    </h5>
                                    <canvas id="typesChart" height="250"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="row">
                            <div class="col-12">
                                <div class="chart-container">
                                    <h5 class="mb-4">
                                        <i class="fas fa-bolt me-2 text-warning"></i>
                                        Quick Actions
                                    </h5>
                                    <div class="row quick-actions">
                                        <div class="col-md-3 mb-3">
                                            <div class="card bg-primary text-white">
                                                <div class="card-body">
                                                    <i class="fas fa-search fa-2x mb-3"></i>
                                                    <h6>Review Reports</h6>
                                                    <small>Check pending incidents</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <div class="card bg-success text-white">
                                                <div class="card-body">
                                                    <i class="fas fa-user-plus fa-2x mb-3"></i>
                                                    <h6>Add User</h6>
                                                    <small>Create new account</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <div class="card bg-info text-white">
                                                <div class="card-body">
                                                    <i class="fas fa-download fa-2x mb-3"></i>
                                                    <h6>Export Data</h6>
                                                    <small>Generate reports</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <div class="card bg-warning text-white">
                                                <div class="card-body">
                                                    <i class="fas fa-cog fa-2x mb-3"></i>
                                                    <h6>Settings</h6>
                                                    <small>System configuration</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Incidents & User Stats -->
                    <div class="col-lg-4">
                        <!-- Recent Incidents -->
                        <div class="recent-incidents mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="mb-0">
                                    <i class="fas fa-history me-2 text-primary"></i>
                                    Recent Incidents
                                </h5>
                                <a href="reports.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            
                            <div class="incident-list">
                                <?php if ($recent_result->num_rows > 0): ?>
                                    <?php while($incident = $recent_result->fetch_assoc()): ?>
                                        <div class="incident-item">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="mb-0"><?php echo htmlspecialchars($incident['incident_type']); ?></h6>
                                                <span class="status-badge badge-<?php echo $incident['status']; ?>">
                                                    <?php echo ucwords(str_replace('_', ' ', $incident['status'])); ?>
                                                </span>
                                            </div>
                                            <p class="text-muted small mb-2">
                                                <?php echo htmlspecialchars(substr($incident['description'], 0, 80)); ?>...
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($incident['reporter_name']); ?>
                                                </small>
                                                <small class="text-muted">
                                                    <?php echo date('M j, g:i A', strtotime($incident['created_at'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-2x mb-3"></i>
                                        <p>No incidents reported yet</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- User Statistics -->
                        <div class="chart-container">
                            <h5 class="mb-4">
                                <i class="fas fa-users me-2 text-info"></i>
                                User Statistics
                            </h5>
                            <div class="row text-center">
                                <div class="col-6 mb-3">
                                    <div class="stat-number text-primary"><?php echo $users_stats['total_users']; ?></div>
                                    <small class="text-muted">Total Users</small>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="stat-number text-success"><?php echo $users_stats['active_users']; ?></div>
                                    <small class="text-muted">Active Users</small>
                                </div>
                                <div class="col-6">
                                    <div class="stat-number text-info"><?php echo $users_stats['admins']; ?></div>
                                    <small class="text-muted">Administrators</small>
                                </div>
                                <div class="col-6">
                                    <div class="stat-number text-warning"><?php echo $users_stats['regular_users']; ?></div>
                                    <small class="text-muted">Regular Users</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Monthly Reports Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyChart = new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: [<?php echo implode(',', array_map(function($item) { 
                    return "'" . date('M Y', strtotime($item['month'] . '-01')) . "'"; 
                }, $monthly_data)); ?>],
                datasets: [{
                    label: 'Reports',
                    data: [<?php echo implode(',', array_column($monthly_data, 'count')); ?>],
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Incident Types Chart
        const typesCtx = document.getElementById('typesChart').getContext('2d');
        const typesChart = new Chart(typesCtx, {
            type: 'doughnut',
            data: {
                labels: [<?php 
                    $types_data = [];
                    while ($type = $types_result->fetch_assoc()) {
                        $types_data[] = "'" . $type['incident_type'] . "'";
                    }
                    echo implode(',', $types_data);
                ?>],
                datasets: [{
                    data: [<?php 
                    $types_result->data_seek(0);
                    $counts = [];
                    while ($type = $types_result->fetch_assoc()) {
                        $counts[] = $type['count'];
                    }
                    echo implode(',', $counts);
                    ?>],
                    backgroundColor: [
                        '#3498db', '#2ecc71', '#e74c3c', '#f39c12', 
                        '#9b59b6', '#1abc9c', '#34495e', '#d35400'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 15
                        }
                    }
                },
                cutout: '60%'
            }
        });

        // Real-time updates (simulated)
        function updateStats() {
            // In a real application, this would fetch fresh data from the server
            console.log('Updating dashboard statistics...');
        }

        // Update every 30 seconds
        setInterval(updateStats, 30000);

        // Add some interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Add click effects to stat cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach(card => {
                card.addEventListener('click', function() {
                    this.style.transform = 'translateY(-5px) scale(1.02)';
                    setTimeout(() => {
                        this.style.transform = 'translateY(-5px)';
                    }, 150);
                });
            });

            // Auto-refresh recent incidents
            setInterval(() => {
                // This would typically make an AJAX call to refresh the recent incidents
                console.log('Refreshing recent incidents...');
            }, 60000);
        });
    </script>
</body>
</html>