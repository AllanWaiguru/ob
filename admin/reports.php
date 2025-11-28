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

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$reporter_filter = isset($_GET['reporter']) ? $_GET['reporter'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query with filters
$sql = "SELECT i.*, u.department, u.phone as reporter_phone, u.email as reporter_email 
        FROM incidents i 
        LEFT JOIN users u ON i.reporter_name = u.full_name 
        WHERE 1=1";
$params = array();
$types = "";

// Add status filter
if (!empty($status_filter) && $status_filter != 'all') {
    $sql .= " AND i.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Add type filter
if (!empty($type_filter) && $type_filter != 'all') {
    $sql .= " AND i.incident_type = ?";
    $params[] = $type_filter;
    $types .= "s";
}

// Add reporter filter
if (!empty($reporter_filter) && $reporter_filter != 'all') {
    $sql .= " AND i.reporter_name = ?";
    $params[] = $reporter_filter;
    $types .= "s";
}

// Add date filters
if (!empty($date_from)) {
    $sql .= " AND DATE(i.incident_date) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $sql .= " AND DATE(i.incident_date) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

// Add search filter
if (!empty($search)) {
    $sql .= " AND (i.incident_type LIKE ? OR i.description LIKE ? OR i.location_name LIKE ? OR i.reporter_name LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ssss";
}

$sql .= " ORDER BY i.created_at DESC";

// Prepare and execute query
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Get all reports for map (without filters for complete map view)
$map_sql = "SELECT id, incident_type, description, location_name, latitude, longitude, status, created_at 
            FROM incidents 
            WHERE latitude IS NOT NULL AND longitude IS NOT NULL";
$map_result = $conn->query($map_sql);

$reports_for_map = [];
while ($report = $map_result->fetch_assoc()) {
    $reports_for_map[] = $report;
}

// Get unique values for filters
$statuses_sql = "SELECT DISTINCT status FROM incidents ORDER BY status";
$statuses_result = $conn->query($statuses_sql);

$types_sql = "SELECT DISTINCT incident_type FROM incidents ORDER BY incident_type";
$types_result = $conn->query($types_sql);

$reporters_sql = "SELECT DISTINCT reporter_name FROM incidents ORDER BY reporter_name";
$reporters_result = $conn->query($reporters_sql);

// Handle bulk actions
if (isset($_POST['bulk_action']) && isset($_POST['selected_reports'])) {
    $selected_reports = $_POST['selected_reports'];
    $bulk_action = $_POST['bulk_action'];
    
    if (!empty($selected_reports)) {
        $placeholders = implode(',', array_fill(0, count($selected_reports), '?'));
        $bulk_sql = "UPDATE incidents SET status = ?, updated_at = NOW() WHERE id IN ($placeholders)";
        $bulk_stmt = $conn->prepare($bulk_sql);
        
        $bulk_params = array_merge([$bulk_action], $selected_reports);
        $bulk_types = str_repeat('i', count($selected_reports));
        $bulk_types = 's' . $bulk_types;
        
        $bulk_stmt->bind_param($bulk_types, ...$bulk_params);
        
        if ($bulk_stmt->execute()) {
            $bulk_message = "Successfully updated " . count($selected_reports) . " report(s)";
            $message_type = "success";
        } else {
            $bulk_message = "Error updating reports";
            $message_type = "danger";
        }
        
        $bulk_stmt->close();
    }
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reports - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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
        
        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .map-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        #reportsMap {
            height: 500px;
            border-radius: 10px;
            border: 1px solid #dee2e6;
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
        
        .bulk-actions {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid var(--secondary-color);
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }
        
        .action-buttons .btn {
            margin-right: 5px;
            margin-bottom: 5px;
        }
        
        .report-image {
            max-width: 60px;
            max-height: 60px;
            border-radius: 5px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .report-image:hover {
            transform: scale(1.1);
        }
        
        .export-buttons .btn {
            margin-left: 5px;
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box .form-control {
            padding-left: 40px;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        .map-controls {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1000;
            background: white;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        .map-legend {
            position: absolute;
            bottom: 10px;
            left: 10px;
            z-index: 1000;
            background: white;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .legend-color {
            width: 15px;
            height: 15px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .legend-reported { background-color: #fff3cd; border: 1px solid #856404; }
        .legend-in_progress { background-color: #cce7ff; border: 1px solid #004085; }
        .legend-resolved { background-color: #d4edda; border: 1px solid #155724; }
        
        .custom-div-icon {
            background: transparent;
            border: none;
        }
        
        .map-popup {
            min-width: 250px;
        }
        
        .map-popup h6 {
            color: var(--primary-color);
            margin-bottom: 8px;
        }
        
        .leaflet-popup-content {
            margin: 15px;
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
                            <a href="d.php" class="nav-link">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li>
                            <a href="reports.php" class="nav-link active">
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

            <!-- Main Content -->
            <div class="col-lg-10 main-content">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="display-6 fw-bold">Manage Incident Reports</h1>
                            <p class="lead mb-0">View, filter, and manage all incident reports in the system</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="export-buttons">
                                <a href="export_reports.php" class="btn btn-light">
                                    <i class="fas fa-download me-2"></i>Export
                                </a>
                                <a href="d.php" class="btn btn-outline-light">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (isset($bulk_message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                        <?php echo $bulk_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Incident Reports Map -->
                <div class="map-container">
                    <h5 class="mb-4">
                        <i class="fas fa-map-marked-alt me-2 text-primary"></i>
                        Incident Locations Map
                    </h5>
                    <div id="reportsMap"></div>
                    <div class="map-legend">
                        <h6 class="mb-2">Status Legend</h6>
                        <div class="legend-item">
                            <div class="legend-color legend-reported"></div>
                            <small>Reported</small>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color legend-in_progress"></div>
                            <small>In Progress</small>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color legend-resolved"></div>
                            <small>Resolved</small>
                        </div>
                    </div>
                    <div class="map-controls">
                        <button class="btn btn-sm btn-outline-primary" id="fitBounds">
                            <i class="fas fa-expand me-1"></i>View All
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" id="refreshMap">
                            <i class="fas fa-sync-alt me-1"></i>Refresh
                        </button>
                    </div>
                </div>

                <!-- Filters -->
                <div class="filter-card">
                    <h5 class="mb-4">
                        <i class="fas fa-filter me-2 text-primary"></i>
                        Filter Reports
                    </h5>
                    <form method="GET" action="reports.php">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Statuses</option>
                                    <?php 
                                    $statuses_result->data_seek(0); // Reset pointer
                                    while($status = $statuses_result->fetch_assoc()): ?>
                                        <option value="<?php echo $status['status']; ?>" 
                                                <?php echo $status_filter == $status['status'] ? 'selected' : ''; ?>>
                                            <?php echo ucwords(str_replace('_', ' ', $status['status'])); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Incident Type</label>
                                <select class="form-select" name="type">
                                    <option value="all" <?php echo $type_filter == 'all' ? 'selected' : ''; ?>>All Types</option>
                                    <?php 
                                    $types_result->data_seek(0); // Reset pointer
                                    while($type = $types_result->fetch_assoc()): ?>
                                        <option value="<?php echo $type['incident_type']; ?>" 
                                                <?php echo $type_filter == $type['incident_type'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type['incident_type']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Reporter</label>
                                <select class="form-select" name="reporter">
                                    <option value="all" <?php echo $reporter_filter == 'all' ? 'selected' : ''; ?>>All Reporters</option>
                                    <?php 
                                    $reporters_result->data_seek(0); // Reset pointer
                                    while($reporter = $reporters_result->fetch_assoc()): ?>
                                        <option value="<?php echo $reporter['reporter_name']; ?>" 
                                                <?php echo $reporter_filter == $reporter['reporter_name'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($reporter['reporter_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Search</label>
                                <div class="search-box">
                                    <i class="fas fa-search"></i>
                                    <input type="text" class="form-control" name="search" placeholder="Search reports..." value="<?php echo htmlspecialchars($search); ?>">
                                </div>
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
                                <a href="reports.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Clear Filters
                                </a>
                                <span class="text-muted ms-3">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Showing <?php echo $result->num_rows; ?> report(s)
                                </span>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Bulk Actions -->
                <form method="POST" action="reports.php" id="bulkForm">
                    <div class="bulk-actions">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="selectAll">
                                    <label class="form-check-label fw-bold" for="selectAll">
                                        Select All Reports
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 text-end">
                                <div class="input-group" style="max-width: 400px; margin-left: auto;">
                                    <select class="form-select" name="bulk_action" required>
                                        <option value="">Bulk Actions</option>
                                        <option value="reported">Mark as Reported</option>
                                        <option value="in_progress">Mark as In Progress</option>
                                        <option value="resolved">Mark as Resolved</option>
                                    </select>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-play me-2"></i>Apply
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reports Table -->
                    <div class="table-container">
                        <div class="table-responsive">
                            <table class="table table-hover" id="reportsTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" class="form-check-input" id="selectAllHeader">
                                        </th>
                                        <th>ID</th>
                                        <th>Type</th>
                                        <th>Description</th>
                                        <th>Location</th>
                                        <th>Reporter</th>
                                        <th>Incident Date</th>
                                        <th>Status</th>
                                        <th>Image</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
<tbody>
    <?php if ($result->num_rows > 0): ?>
        <?php while($report = $result->fetch_assoc()): ?>
            <tr class="clickable-row" data-href="view_report.php?id=<?php echo $report['id']; ?>">
                <td onclick="event.stopPropagation();">
                    <input type="checkbox" class="form-check-input report-checkbox" name="selected_reports[]" value="<?php echo $report['id']; ?>">
                </td>
                <td>
                    <strong>#<?php echo $report['id']; ?></strong>
                </td>
                <td>
                    <strong><?php echo htmlspecialchars($report['incident_type']); ?></strong>
                </td>
                <td>
                    <div class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($report['description']); ?>">
                        <?php echo htmlspecialchars($report['description']); ?>
                    </div>
                </td>
                <td>
                    <small><?php echo htmlspecialchars($report['location_name']); ?></small>
                    <?php if ($report['latitude'] && $report['longitude']): ?>
                        <br><small class="text-muted">📍 Coordinates</small>
                    <?php endif; ?>
                </td>
                <td>
                    <div>
                        <strong><?php echo htmlspecialchars($report['reporter_name']); ?></strong>
                        <?php if ($report['department']): ?>
                            <br><small class="text-muted"><?php echo htmlspecialchars($report['department']); ?></small>
                        <?php endif; ?>
                    </div>
                </td>
                <td>
                    <small>
                        <?php echo date('M j, Y', strtotime($report['incident_date'])); ?><br>
                        <span class="text-muted"><?php echo date('g:i A', strtotime($report['incident_date'])); ?></span>
                    </small>
                </td>
                <td>
                    <span class="status-badge badge-<?php echo $report['status']; ?>">
                        <?php echo ucwords(str_replace('_', ' ', $report['status'])); ?>
                    </span>
                </td>
                <td onclick="event.stopPropagation();">
                    <?php if (!empty($report['image_path']) && file_exists($report['image_path'])): ?>
                        <img src="<?php echo $report['image_path']; ?>" 
                             alt="Incident Image" 
                             class="report-image"
                             data-bs-toggle="modal" 
                             data-bs-target="#imageModal"
                             data-image="<?php echo $report['image_path']; ?>">
                    <?php else: ?>
                        <span class="text-muted">No image</span>
                    <?php endif; ?>
                </td>
                <td onclick="event.stopPropagation();">
                    <div class="action-buttons">
                        <a href="view_report.php?id=<?php echo $report['id']; ?>" class="btn btn-sm btn-outline-primary" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="edit_report.php?id=<?php echo $report['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-danger" title="Delete" onclick="event.stopPropagation(); confirmDelete(<?php echo $report['id']; ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="10" class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No reports found</h5>
                <p class="text-muted">
                    <?php 
                    if ($status_filter || $type_filter || $reporter_filter || $date_from || $date_to || $search) {
                        echo "No reports match your current filters. Try adjusting your search criteria.";
                    } else {
                        echo "No incident reports have been submitted yet.";
                    }
                    ?>
                </p>
                <?php if ($status_filter || $type_filter || $reporter_filter || $date_from || $date_to || $search): ?>
                    <a href="reports.php" class="btn btn-outline-primary">
                        <i class="fas fa-times me-2"></i>Clear Filters
                    </a>
                <?php endif; ?>
            </td>
        </tr>
    <?php endif; ?>
</tbody>
                            </table>
                        </div>
                    </div>
                </form>

                <!-- Pagination -->
                <?php if ($result->num_rows > 0): ?>
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
                    <img id="modalImage" src="" alt="Incident Image" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this incident report? This action cannot be undone.</p>
                    <p class="text-muted small">All report data including images and location information will be permanently removed.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="deleteConfirm" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Delete Report
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Initialize Leaflet Map
        function initializeMap() {
            // Default coordinates (you can set your organization's default location)
            const defaultLat = 40.7128;
            const defaultLng = -74.0060;
            const defaultZoom = 13;
            
            var map = L.map('reportsMap').setView([defaultLat, defaultLng], defaultZoom);
            
            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 18
            }).addTo(map);

            // Create feature group for markers
            var markers = L.featureGroup().addTo(map);

            // Define icon colors based on status
            function getStatusIcon(status) {
                const iconColors = {
                    'reported': 'orange',
                    'in_progress': 'blue',
                    'resolved': 'green'
                };
                
                const color = iconColors[status] || 'gray';
                
                return L.divIcon({
                    className: 'custom-div-icon',
                    html: `<div style="background-color: ${color}; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"></div>`,
                    iconSize: [16, 16],
                    iconAnchor: [8, 8]
                });
            }

            // Add reports to map
            <?php foreach ($reports_for_map as $report): ?>
                <?php if ($report['latitude'] && $report['longitude']): ?>
                    var marker = L.marker([<?php echo $report['latitude']; ?>, <?php echo $report['longitude']; ?>], {
                        icon: getStatusIcon('<?php echo $report['status']; ?>')
                    }).addTo(markers);
                    
                    marker.bindPopup(`
                        <div class="map-popup">
                            <h6><strong><?php echo htmlspecialchars($report['incident_type']); ?></strong></h6>
                            <p class="mb-1"><small><?php echo htmlspecialchars(substr($report['description'], 0, 100)); ?>...</small></p>
                            <p class="mb-1"><strong>Location:</strong> <?php echo htmlspecialchars($report['location_name']); ?></p>
                            <p class="mb-1"><strong>Status:</strong> <span class="status-badge badge-<?php echo $report['status']; ?>"><?php echo ucwords(str_replace('_', ' ', $report['status'])); ?></span></p>
                            <p class="mb-2"><strong>Reported:</strong> <?php echo date('M j, Y', strtotime($report['created_at'])); ?></p>
                            <div class="text-center">
                                <a href="view_report.php?id=<?php echo $report['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </a>
                            </div>
                        </div>
                    `);
                <?php endif; ?>
            <?php endforeach; ?>

            // Fit map to show all markers
            if (markers.getLayers().length > 0) {
                map.fitBounds(markers.getBounds().pad(0.1));
            } else {
                // If no markers, show default location with message
                L.marker([defaultLat, defaultLng]).addTo(map)
                    .bindPopup('No incident locations available')
                    .openPopup();
            }

            return map;
        }

        // Initialize the map when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            var map = initializeMap();

            // Map controls
            document.getElementById('fitBounds').addEventListener('click', function() {
                var markers = Object.values(map._layers).filter(layer => layer instanceof L.Marker);
                if (markers.length > 0) {
                    var group = new L.featureGroup(markers);
                    map.fitBounds(group.getBounds().pad(0.1));
                }
            });

            document.getElementById('refreshMap').addEventListener('click', function() {
                location.reload();
            });
        });

        // Initialize DataTable
        $(document).ready(function() {
            $('#reportsTable').DataTable({
                pageLength: 25,
                responsive: true,
                order: [[1, 'desc']],
                language: {
                    search: "Search reports:",
                    lengthMenu: "Show _MENU_ reports per page"
                }
            });
        });

        // Image modal functionality
        const imageModal = document.getElementById('imageModal');
        if (imageModal) {
            imageModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const imageSrc = button.getAttribute('data-image');
                const modalImage = document.getElementById('modalImage');
                modalImage.src = imageSrc;
            });
        }

        // Bulk selection functionality
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.report-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        document.getElementById('selectAllHeader').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.report-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            document.getElementById('selectAll').checked = this.checked;
        });

        // Delete confirmation
        function confirmDelete(reportId) {
            const deleteConfirm = document.getElementById('deleteConfirm');
            deleteConfirm.href = `delete_report.php?id=${reportId}`;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        // Form validation for bulk actions
        document.getElementById('bulkForm').addEventListener('submit', function(e) {
            const selectedReports = document.querySelectorAll('.report-checkbox:checked');
            const bulkAction = document.querySelector('select[name="bulk_action"]');
            
            if (selectedReports.length === 0) {
                e.preventDefault();
                alert('Please select at least one report to perform bulk action.');
                return;
            }
            
            if (!bulkAction.value) {
                e.preventDefault();
                alert('Please select a bulk action to perform.');
                return;
            }
        });

        // Auto-close alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        // Make table rows clickable
document.addEventListener('DOMContentLoaded', function() {
    const clickableRows = document.querySelectorAll('.clickable-row');
    
    clickableRows.forEach(row => {
        row.addEventListener('click', function(e) {
            // Don't redirect if clicking on checkboxes, action buttons, or images
            if (e.target.tagName === 'INPUT' || 
                e.target.tagName === 'BUTTON' || 
                e.target.tagName === 'A' ||
                e.target.closest('button') || 
                e.target.closest('a') ||
                e.target.classList.contains('report-image')) {
                return;
            }
            
            const href = this.getAttribute('data-href');
            if (href) {
                window.location.href = href;
            }
        });
    });
});
    </script>
</body>
</html>