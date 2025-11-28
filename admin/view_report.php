<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

// Check if ID parameter is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: reports.php");
    exit();
}

$report_id = intval($_GET['id']);

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

// Get report details - removed u.position since it doesn't exist
$sql = "SELECT i.*, u.department, u.phone as reporter_phone, u.email as reporter_email
        FROM incidents i 
        LEFT JOIN users u ON i.reporter_name = u.full_name 
        WHERE i.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $report_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Report not found
    header("Location: reports.php");
    exit();
}

$report = $result->fetch_assoc();
$stmt->close();

// Get similar incidents for context
$similar_sql = "SELECT id, incident_type, location_name, incident_date, status 
                FROM incidents 
                WHERE incident_type = ? AND id != ? 
                ORDER BY incident_date DESC 
                LIMIT 5";
$similar_stmt = $conn->prepare($similar_sql);
$similar_stmt->bind_param("si", $report['incident_type'], $report_id);
$similar_stmt->execute();
$similar_result = $similar_stmt->get_result();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Report #<?php echo $report['id']; ?> - Digital Occurrence Book</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
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
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .info-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .map-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        #reportMap {
            height: 400px;
            border-radius: 10px;
            border: 1px solid #dee2e6;
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
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
        
        .detail-item {
            border-bottom: 1px solid #e9ecef;
            padding: 15px 0;
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .detail-value {
            color: #555;
        }
        
        .report-image {
            max-width: 100%;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .image-thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .image-thumbnail:hover {
            transform: scale(1.05);
        }
        
        .similar-incident {
            border-left: 4px solid var(--secondary-color);
            padding-left: 15px;
            margin-bottom: 15px;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--secondary-color);
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -23px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--secondary-color);
            border: 3px solid white;
        }
        
        .action-buttons .btn {
            margin-right: 10px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="d.php">
                <i class="fas fa-book me-2"></i>
                Digital Occurrence Book 
                <?php if ($_SESSION['user_type'] === 'admin'): ?>
                    <span class="admin-badge ms-2">Admin</span>
                <?php endif; ?>
            </a>
            <div class="d-flex align-items-center">
                <span class="navbar-text me-3">
                    <i class="fas fa-user me-1"></i>
                    <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                </span>
                <div class="dropdown">
                    <button class="btn btn-outline-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                        <i class="fas fa-cog me-2"></i>Settings
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="../profile.php"><i class="fas fa-user me-2"></i>My Profile</a></li>
                        <?php if ($_SESSION['user_type'] === 'admin'): ?>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cogs me-2"></i>System Settings</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php if ($_SESSION['user_type'] === 'admin'): ?>
            <div class="col-lg-2 sidebar">
                <div class="d-flex flex-column flex-shrink-0 p-3">
                    <ul class="nav nav-pills flex-column mb-auto">
                        <li class="nav-item">
                            <a href="d.php" class="nav-link">
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
                    </ul>
                </div>
            </div>
            <?php endif; ?>

            <!-- Main Content -->
            <div class="<?php echo $_SESSION['user_type'] === 'admin' ? 'col-lg-10' : 'col-12'; ?> main-content">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="display-6 fw-bold">Incident Report #<?php echo $report['id']; ?></h1>
                            <p class="lead mb-0">Detailed view of the incident report</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="action-buttons">
                                <a href="reports.php" class="btn btn-light">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Reports
                                </a>
                                  <a href="charge.php" class="btn btn-light">
                                    <i class="fas fa-arrow-left me-2"></i>generate charges
                                </a>
                                <?php if ($_SESSION['user_type'] === 'admin'): ?>
                                    <a href="edit_report.php?id=<?php echo $report['id']; ?>" class="btn btn-outline-light">
                                        <i class="fas fa-edit me-2"></i>Edit Report
                                    </a>
                                <?php endif; ?>
                                <button class="btn btn-outline-light" onclick="window.print()">
                                    <i class="fas fa-print me-2"></i>Print
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Left Column - Report Details -->
                    <div class="col-lg-8">
                        <!-- Basic Information -->
                        <div class="info-card">
                            <h5 class="mb-4">
                                <i class="fas fa-info-circle me-2 text-primary"></i>
                                Incident Information
                            </h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="detail-item">
                                        <div class="detail-label">Incident Type</div>
                                        <div class="detail-value">
                                            <strong><?php echo htmlspecialchars($report['incident_type']); ?></strong>
                                        </div>
                                    </div>
                                    
                                    <div class="detail-item">
                                        <div class="detail-label">Location</div>
                                        <div class="detail-value">
                                            <?php echo htmlspecialchars($report['location_name']); ?>
                                            <?php if ($report['latitude'] && $report['longitude']): ?>
                                                <br><small class="text-muted">Coordinates: <?php echo $report['latitude']; ?>, <?php echo $report['longitude']; ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="detail-item">
                                        <div class="detail-label">Incident Date & Time</div>
                                        <div class="detail-value">
                                            <?php echo date('F j, Y', strtotime($report['incident_date'])); ?>
                                            <br>
                                            <small class="text-muted"><?php echo date('g:i A', strtotime($report['incident_date'])); ?></small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="detail-item">
                                        <div class="detail-label">Current Status</div>
                                        <div class="detail-value">
                                            <span class="status-badge badge-<?php echo $report['status']; ?>">
                                                <?php echo ucwords(str_replace('_', ' ', $report['status'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="detail-item">
                                        <div class="detail-label">Reported On</div>
                                        <div class="detail-value">
                                            <?php echo date('F j, Y', strtotime($report['created_at'])); ?>
                                            <br>
                                            <small class="text-muted"><?php echo date('g:i A', strtotime($report['created_at'])); ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="detail-item">
                                        <div class="detail-label">Last Updated</div>
                                        <div class="detail-value">
                                            <?php echo date('F j, Y', strtotime($report['updated_at'])); ?>
                                            <br>
                                            <small class="text-muted"><?php echo date('g:i A', strtotime($report['updated_at'])); ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="info-card">
                            <h5 class="mb-4">
                                <i class="fas fa-align-left me-2 text-primary"></i>
                                Incident Description
                            </h5>
                            <div class="detail-value">
                                <?php echo nl2br(htmlspecialchars($report['description'])); ?>
                            </div>
                        </div>

                        <!-- Location Map -->
                        <?php if ($report['latitude'] && $report['longitude']): ?>
                        <div class="map-container">
                            <h5 class="mb-4">
                                <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                                Incident Location
                            </h5>
                            <div id="reportMap"></div>
                        </div>
                        <?php endif; ?>

                        <!-- Images -->
                        <?php if (!empty($report['image_path']) && file_exists($report['image_path'])): ?>
                        <div class="info-card">
                            <h5 class="mb-4">
                                <i class="fas fa-camera me-2 text-primary"></i>
                                Incident Images
                            </h5>
                            <div class="row">
                                <div class="col-12">
                                    <img src="<?php echo $report['image_path']; ?>" 
                                         alt="Incident Image" 
                                         class="report-image"
                                         data-bs-toggle="modal" 
                                         data-bs-target="#imageModal">
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Right Column - Reporter Info & Similar Incidents -->
                    <div class="col-lg-4">
                        <!-- Reporter Information -->
                        <div class="info-card">
                            <h5 class="mb-4">
                                <i class="fas fa-user me-2 text-primary"></i>
                                Reporter Information
                            </h5>
                            <div class="detail-item">
                                <div class="detail-label">Full Name</div>
                                <div class="detail-value">
                                    <strong><?php echo htmlspecialchars($report['reporter_name']); ?></strong>
                                </div>
                            </div>
                            
                            <?php if (!empty($report['department'])): ?>
                            <div class="detail-item">
                                <div class="detail-label">Department</div>
                                <div class="detail-value"><?php echo htmlspecialchars($report['department']); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($report['reporter_contact'])): ?>
                            <div class="detail-item">
                                <div class="detail-label">Contact Number</div>
                                <div class="detail-value"><?php echo htmlspecialchars($report['reporter_contact']); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($report['reporter_email'])): ?>
                            <div class="detail-item">
                                <div class="detail-label">Email Address</div>
                                <div class="detail-value">
                                    <a href="mailto:<?php echo htmlspecialchars($report['reporter_email']); ?>">
                                        <?php echo htmlspecialchars($report['reporter_email']); ?>
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($report['user_id'])): ?>
                            <div class="detail-item">
                                <div class="detail-label">User ID</div>
                                <div class="detail-value"><?php echo htmlspecialchars($report['user_id']); ?></div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Report Timeline -->
                        <div class="info-card">
                            <h5 class="mb-4">
                                <i class="fas fa-history me-2 text-primary"></i>
                                Report Timeline
                            </h5>
                            <div class="timeline">
                                <div class="timeline-item">
                                    <div class="detail-label">Report Created</div>
                                    <div class="detail-value">
                                        <?php echo date('M j, Y g:i A', strtotime($report['created_at'])); ?>
                                    </div>
                                </div>
                                
                                <?php if ($report['created_at'] != $report['updated_at']): ?>
                                <div class="timeline-item">
                                    <div class="detail-label">Last Updated</div>
                                    <div class="detail-value">
                                        <?php echo date('M j, Y g:i A', strtotime($report['updated_at'])); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="timeline-item">
                                    <div class="detail-label">Current Status</div>
                                    <div class="detail-value">
                                        <span class="status-badge badge-<?php echo $report['status']; ?>">
                                            <?php echo ucwords(str_replace('_', ' ', $report['status'])); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Similar Incidents -->
                        <?php if ($similar_result->num_rows > 0): ?>
                        <div class="info-card">
                            <h5 class="mb-4">
                                <i class="fas fa-exclamation-triangle me-2 text-primary"></i>
                                Similar Incidents
                            </h5>
                            <?php while($similar = $similar_result->fetch_assoc()): ?>
                                <div class="similar-incident">
                                    <div class="detail-label">
                                        <a href="view_report.php?id=<?php echo $similar['id']; ?>" class="text-decoration-none">
                                            Report #<?php echo $similar['id']; ?>
                                        </a>
                                    </div>
                                    <div class="detail-value">
                                        <small>
                                            <?php echo date('M j, Y', strtotime($similar['incident_date'])); ?>
                                            <br>
                                            <span class="status-badge badge-<?php echo $similar['status']; ?>">
                                                <?php echo ucwords(str_replace('_', ' ', $similar['status'])); ?>
                                            </span>
                                        </small>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Incident Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="<?php echo $report['image_path']; ?>" alt="Incident Image" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Initialize Leaflet Map for single report
        <?php if ($report['latitude'] && $report['longitude']): ?>
        document.addEventListener('DOMContentLoaded', function() {
            var map = L.map('reportMap').setView([<?php echo $report['latitude']; ?>, <?php echo $report['longitude']; ?>], 16);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 18
            }).addTo(map);

            // Define icon color based on status
            function getStatusIcon(status) {
                const iconColors = {
                    'reported': 'orange',
                    'in_progress': 'blue',
                    'resolved': 'green'
                };
                
                const color = iconColors[status] || 'gray';
                
                return L.divIcon({
                    className: 'custom-div-icon',
                    html: `<div style="background-color: ${color}; width: 16px; height: 16px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"></div>`,
                    iconSize: [22, 22],
                    iconAnchor: [11, 11]
                });
            }

            // Add marker for the incident
            var marker = L.marker([<?php echo $report['latitude']; ?>, <?php echo $report['longitude']; ?>], {
                icon: getStatusIcon('<?php echo $report['status']; ?>')
            }).addTo(map);
            
            marker.bindPopup(`
                <div class="map-popup">
                    <h6><strong><?php echo htmlspecialchars($report['incident_type']); ?></strong></h6>
                    <p class="mb-1"><strong>Location:</strong> <?php echo htmlspecialchars($report['location_name']); ?></p>
                    <p class="mb-1"><strong>Status:</strong> <span class="status-badge badge-<?php echo $report['status']; ?>"><?php echo ucwords(str_replace('_', ' ', $report['status'])); ?></span></p>
                    <p class="mb-1"><strong>Date:</strong> <?php echo date('M j, Y', strtotime($report['incident_date'])); ?></p>
                </div>
            `).openPopup();
        });
        <?php endif; ?>

        // Print functionality
        function printReport() {
            window.print();
        }

        // Auto-close alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Image modal functionality
        const imageModal = document.getElementById('imageModal');
        if (imageModal) {
            imageModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const imageSrc = button ? button.getAttribute('data-image') : '<?php echo $report['image_path']; ?>';
                const modalImage = document.getElementById('modalImage');
                modalImage.src = imageSrc;
            });
        }
    </script>
</body>
</html>