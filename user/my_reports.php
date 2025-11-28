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

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query with filters
$sql = "SELECT * FROM incidents WHERE reporter_name = ?";
$params = array($_SESSION['full_name']);
$types = "s";

// Add status filter
if (!empty($status_filter) && $status_filter != 'all') {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Add type filter
if (!empty($type_filter) && $type_filter != 'all') {
    $sql .= " AND incident_type = ?";
    $params[] = $type_filter;
    $types .= "s";
}

// Add date filters
if (!empty($date_from)) {
    $sql .= " AND DATE(incident_date) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $sql .= " AND DATE(incident_date) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$sql .= " ORDER BY created_at DESC";

// Prepare and execute query
$stmt = $conn->prepare($sql);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Get unique incident types for filter dropdown
$types_sql = "SELECT DISTINCT incident_type FROM incidents WHERE reporter_name = ? ORDER BY incident_type";
$types_stmt = $conn->prepare($types_sql);
$types_stmt->bind_param("s", $_SESSION['full_name']);
$types_stmt->execute();
$types_result = $types_stmt->get_result();

$incident_types = array();
while ($row = $types_result->fetch_assoc()) {
    $incident_types[] = $row['incident_type'];
}

$types_stmt->close();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reports - Digital Occurrence Book</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
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
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .filter-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .report-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            border-left: 4px solid var(--secondary-color);
            transition: transform 0.2s;
        }
        
        .report-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }
        
        .report-card.reported { border-left-color: var(--warning-color); }
        .report-card.in_progress { border-left-color: var(--secondary-color); }
        .report-card.resolved { border-left-color: var(--success-color); }
        
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
        
        .action-buttons .btn {
            margin-right: 5px;
            margin-bottom: 5px;
        }
        
        .report-image {
            max-width: 100px;
            max-height: 100px;
            border-radius: 5px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .report-image:hover {
            transform: scale(1.05);
        }
        
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            margin: 10px 0;
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
                            <a href="my_reports.php" class="nav-link active">
                                <i class="fas fa-list"></i> My Reports
                            </a>
                        </li>
                        <li>
                            <a href="profile.php" class="nav-link">
                                <i class="fas fa-user"></i> Profile
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-10 main-content">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="display-6 fw-bold">My Incident Reports</h1>
                            <p class="lead mb-0">View and manage all your submitted incident reports</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="report.php" class="btn btn-light btn-lg">
                                <i class="fas fa-plus-circle me-2"></i>New Report
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <i class="fas fa-file-alt fa-2x text-primary"></i>
                            <div class="stats-number"><?php echo $result->num_rows; ?></div>
                            <div class="text-muted">Total Reports</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <i class="fas fa-clock fa-2x text-warning"></i>
                            <div class="stats-number">
                                <?php 
                                $reported_count = 0;
                                $result->data_seek(0); // Reset pointer
                                while ($row = $result->fetch_assoc()) {
                                    if ($row['status'] == 'reported') $reported_count++;
                                }
                                $result->data_seek(0); // Reset pointer again
                                echo $reported_count; 
                                ?>
                            </div>
                            <div class="text-muted">Reported</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <i class="fas fa-sync-alt fa-2x text-info"></i>
                            <div class="stats-number">
                                <?php 
                                $in_progress_count = 0;
                                $result->data_seek(0);
                                while ($row = $result->fetch_assoc()) {
                                    if ($row['status'] == 'in_progress') $in_progress_count++;
                                }
                                $result->data_seek(0);
                                echo $in_progress_count; 
                                ?>
                            </div>
                            <div class="text-muted">In Progress</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <i class="fas fa-check-circle fa-2x text-success"></i>
                            <div class="stats-number">
                                <?php 
                                $resolved_count = 0;
                                $result->data_seek(0);
                                while ($row = $result->fetch_assoc()) {
                                    if ($row['status'] == 'resolved') $resolved_count++;
                                }
                                $result->data_seek(0);
                                echo $resolved_count; 
                                ?>
                            </div>
                            <div class="text-muted">Resolved</div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="filter-card">
                    <h5 class="mb-3"><i class="fas fa-filter me-2"></i>Filter Reports</h5>
                    <form method="GET" action="my_reports.php">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Statuses</option>
                                    <option value="reported" <?php echo $status_filter == 'reported' ? 'selected' : ''; ?>>Reported</option>
                                    <option value="in_progress" <?php echo $status_filter == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="resolved" <?php echo $status_filter == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Incident Type</label>
                                <select class="form-select" name="type">
                                    <option value="all" <?php echo $type_filter == 'all' ? 'selected' : ''; ?>>All Types</option>
                                    <?php foreach ($incident_types as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type); ?>" 
                                                <?php echo $type_filter == $type ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date From</label>
                                <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date To</label>
                                <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-2"></i>Apply Filters
                                </button>
                                <a href="my_reports.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Clear Filters
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Reports List -->
                <div class="row">
                    <div class="col-12">
                        <?php if ($result->num_rows > 0): ?>
                            <div class="reports-list">
                                <?php while($report = $result->fetch_assoc()): ?>
                                    <div class="report-card <?php echo $report['status']; ?>">
                                        <div class="row align-items-center">
                                            <div class="col-md-1 text-center">
                                                <?php if (!empty($report['image_path']) && file_exists($report['image_path'])): ?>
                                                    <img src="<?php echo $report['image_path']; ?>" 
                                                         alt="Incident Image" 
                                                         class="report-image"
                                                         data-bs-toggle="modal" 
                                                         data-bs-target="#imageModal"
                                                         data-image="<?php echo $report['image_path']; ?>">
                                                <?php else: ?>
                                                    <i class="fas fa-image fa-2x text-muted"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-7">
                                                <div class="d-flex align-items-center mb-2">
                                                    <h5 class="mb-0 me-3"><?php echo htmlspecialchars($report['incident_type']); ?></h5>
                                                    <span class="status-badge badge-<?php echo $report['status']; ?>">
                                                        <?php 
                                                        $status_text = str_replace('_', ' ', $report['status']);
                                                        echo ucwords($status_text); 
                                                        ?>
                                                    </span>
                                                </div>
                                                <p class="text-muted mb-2">
                                                    <i class="fas fa-map-marker-alt me-1"></i>
                                                    <?php echo htmlspecialchars($report['location_name']); ?>
                                                    <?php if ($report['latitude'] && $report['longitude']): ?>
                                                        <small class="text-muted ms-2">
                                                            (<?php echo number_format($report['latitude'], 6); ?>, <?php echo number_format($report['longitude'], 6); ?>)
                                                        </small>
                                                    <?php endif; ?>
                                                </p>
                                                <p class="mb-2"><?php echo htmlspecialchars(substr($report['description'], 0, 200)); ?><?php echo strlen($report['description']) > 200 ? '...' : ''; ?></p>
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    Incident: <?php echo date('M j, Y g:i A', strtotime($report['incident_date'])); ?> |
                                                    Reported: <?php echo date('M j, Y g:i A', strtotime($report['created_at'])); ?>
                                                </small>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <div class="action-buttons">
                                                    <a href="view_report.php?id=<?php echo $report['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-eye me-1"></i>View
                                                    </a>
                                                    <?php if ($report['status'] == 'reported'): ?>
                                                        <a href="edit_report.php?id=<?php echo $report['id']; ?>" class="btn btn-outline-secondary btn-sm">
                                                            <i class="fas fa-edit me-1"></i>Edit
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="export_report.php?id=<?php echo $report['id']; ?>" class="btn btn-outline-success btn-sm">
                                                        <i class="fas fa-download me-1"></i>Export
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-file-alt"></i>
                                <h4>No Reports Found</h4>
                                <p class="text-muted">
                                    <?php 
                                    if ($status_filter || $type_filter || $date_from || $date_to) {
                                        echo "No reports match your current filters. Try adjusting your search criteria.";
                                    } else {
                                        echo "You haven't submitted any incident reports yet.";
                                    }
                                    ?>
                                </p>
                                <?php if (!$status_filter && !$type_filter && !$date_from && !$date_to): ?>
                                    <a href="report.php" class="btn btn-primary">
                                        <i class="fas fa-plus-circle me-2"></i>Report Your First Incident
                                    </a>
                                <?php else: ?>
                                    <a href="my_reports.php" class="btn btn-outline-primary">
                                        <i class="fas fa-times me-2"></i>Clear Filters
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Pagination (if needed in future) -->
                <?php if ($result->num_rows > 10): ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <nav>
                            <ul class="pagination justify-content-center">
                                <li class="page-item disabled">
                                    <a class="page-link" href="#">Previous</a>
                                </li>
                                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item">
                                    <a class="page-link" href="#">Next</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
                <?php endif; ?>
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
                    <img id="modalImage" src="" alt="Incident Image" class="img-fluid">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        // Image modal functionality
        const imageModal = document.getElementById('imageModal');
        imageModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const imageSrc = button.getAttribute('data-image');
            const modalImage = document.getElementById('modalImage');
            modalImage.src = imageSrc;
        });

        // Auto-dismiss alerts
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });

            // Initialize DataTable if we have many records
            <?php if ($result->num_rows > 5): ?>
                $('.reports-list').DataTable({
                    pageLength: 10,
                    responsive: true,
                    order: [[0, 'desc']]
                });
            <?php endif; ?>
        });

        // Export functionality
        function exportReports(format) {
            // Get current filter parameters
            const params = new URLSearchParams(window.location.search);
            params.set('export', format);
            
            window.location.href = 'export_reports.php?' + params.toString();
        }
    </script>
</body>
</html>