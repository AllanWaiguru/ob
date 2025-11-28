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

// Handle export request
if (isset($_POST['export'])) {
    $export_type = $_POST['export_type'];
    $format = $_POST['format'];
    $date_from = $_POST['date_from'];
    $date_to = $_POST['date_to'];
    $status = $_POST['status'];
    $incident_type = $_POST['incident_type'];
    
    // Build query based on filters
    $sql = "SELECT i.*, u.department, u.phone as reporter_phone, u.email as reporter_email 
            FROM incidents i 
            LEFT JOIN users u ON i.reporter_name = u.full_name 
            WHERE 1=1";
    
    $params = [];
    $types = "";
    
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
    
    if (!empty($status) && $status != 'all') {
        $sql .= " AND i.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    if (!empty($incident_type) && $incident_type != 'all') {
        $sql .= " AND i.incident_type = ?";
        $params[] = $incident_type;
        $types .= "s";
    }
    
    // Add ordering
    $sql .= " ORDER BY i.created_at DESC";
    
    // Prepare and execute query
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Generate filename
    $filename = "incident_reports_" . date('Y-m-d_H-i-s');
    
    if ($format == 'csv') {
        exportToCSV($result, $filename);
    } elseif ($format == 'excel') {
        exportToExcel($result, $filename);
    } elseif ($format == 'pdf') {
        exportToPDF($result, $filename, $date_from, $date_to);
    }
    
    $stmt->close();
}

function exportToCSV($result, $filename) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
    
    // CSV headers
    $headers = [
        'ID', 'Incident Type', 'Description', 'Location', 
        'Latitude', 'Longitude', 'Incident Date', 'Reporter Name',
        'Reporter Contact', 'Department', 'Status', 'Created At', 'Updated At'
    ];
    fputcsv($output, $headers);
    
    // Data rows
    while ($row = $result->fetch_assoc()) {
        $data = [
            $row['id'],
            $row['incident_type'],
            $row['description'],
            $row['location_name'],
            $row['latitude'],
            $row['longitude'],
            $row['incident_date'],
            $row['reporter_name'],
            $row['reporter_contact'],
            $row['department'],
            ucwords(str_replace('_', ' ', $row['status'])),
            $row['created_at'],
            $row['updated_at']
        ];
        fputcsv($output, $data);
    }
    
    fclose($output);
    exit;
}

function exportToExcel($result, $filename) {
    require_once '../vendor/autoload.php'; // If using PhpSpreadsheet
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
    
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Headers
    $headers = ['ID', 'Incident Type', 'Description', 'Location', 'Latitude', 'Longitude', 
                'Incident Date', 'Reporter Name', 'Reporter Contact', 'Department', 'Status', 
                'Created At', 'Updated At'];
    
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '1', $header);
        $sheet->getStyle($col . '1')->getFont()->setBold(true);
        $col++;
    }
    
    // Data
    $row = 2;
    while ($data = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $row, $data['id']);
        $sheet->setCellValue('B' . $row, $data['incident_type']);
        $sheet->setCellValue('C' . $row, $data['description']);
        $sheet->setCellValue('D' . $row, $data['location_name']);
        $sheet->setCellValue('E' . $row, $data['latitude']);
        $sheet->setCellValue('F' . $row, $data['longitude']);
        $sheet->setCellValue('G' . $row, $data['incident_date']);
        $sheet->setCellValue('H' . $row, $data['reporter_name']);
        $sheet->setCellValue('I' . $row, $data['reporter_contact']);
        $sheet->setCellValue('J' . $row, $data['department']);
        $sheet->setCellValue('K' . $row, ucwords(str_replace('_', ' ', $data['status'])));
        $sheet->setCellValue('L' . $row, $data['created_at']);
        $sheet->setCellValue('M' . $row, $data['updated_at']);
        $row++;
    }
    
    // Auto size columns
    foreach (range('A', 'M') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }
    
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

function exportToPDF($result, $filename, $date_from, $date_to) {
    // Simple PDF generation (for complex PDFs, use libraries like TCPDF or Dompdf)
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
    
    $pdf_content = "INCIDENT REPORTS EXPORT\n";
    $pdf_content .= "Generated on: " . date('Y-m-d H:i:s') . "\n";
    
    if (!empty($date_from) || !empty($date_to)) {
        $pdf_content .= "Date Range: " . ($date_from ?? 'Start') . " to " . ($date_to ?? 'End') . "\n";
    }
    
    $pdf_content .= "\n" . str_repeat("-", 80) . "\n\n";
    
    while ($row = $result->fetch_assoc()) {
        $pdf_content .= "ID: " . $row['id'] . "\n";
        $pdf_content .= "Type: " . $row['incident_type'] . "\n";
        $pdf_content .= "Description: " . substr($row['description'], 0, 100) . "...\n";
        $pdf_content .= "Location: " . $row['location_name'] . "\n";
        $pdf_content .= "Reporter: " . $row['reporter_name'] . "\n";
        $pdf_content .= "Status: " . ucwords(str_replace('_', ' ', $row['status'])) . "\n";
        $pdf_content .= "Incident Date: " . $row['incident_date'] . "\n";
        $pdf_content .= str_repeat("-", 40) . "\n\n";
    }
    
    // For proper PDF generation, you would use a library like:
    // $pdf = new TCPDF();
    // $pdf->AddPage();
    // $pdf->Write(0, $pdf_content);
    // $pdf->Output($filename . '.pdf', 'D');
    
    // For now, output as text (replace with proper PDF library)
    echo $pdf_content;
    exit;
}

// Get filter options for the form
$statuses_sql = "SELECT DISTINCT status FROM incidents ORDER BY status";
$statuses_result = $conn->query($statuses_sql);

$types_sql = "SELECT DISTINCT incident_type FROM incidents ORDER BY incident_type";
$types_result = $conn->query($types_sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Reports - Digital Occurrence Book</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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
        
        .export-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .format-option {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            height: 100%;
        }
        
        .format-option:hover {
            border-color: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .format-option.selected {
            border-color: var(--secondary-color);
            background-color: rgba(52, 152, 219, 0.05);
        }
        
        .format-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: var(--secondary-color);
        }
        
        .btn-primary {
            background-color: var(--secondary-color);
            border: none;
            padding: 12px 30px;
            font-weight: 600;
            font-size: 1.1rem;
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
        
        .info-box {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-left: 4px solid var(--info-color);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        .export-preview {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
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
                            <a href="export_reports.php" class="nav-link active">
                                <i class="fas fa-download"></i> Export Data
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
                            <h1 class="display-6 fw-bold">Export Incident Reports</h1>
                            <p class="lead mb-0">Generate and download reports in various formats</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="reports.php" class="btn btn-light">
                                <i class="fas fa-arrow-left me-2"></i>Back to Reports
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Export Form -->
                <div class="export-card">
                    <form method="POST" action="export_reports.php" id="exportForm">
                        <div class="info-box">
                            <h6><i class="fas fa-info-circle me-2 text-info"></i>Export Information</h6>
                            <p class="mb-0">Select your export preferences and filters to generate customized reports. You can export all data or apply specific filters.</p>
                        </div>

                        <!-- Export Type -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="mb-3">
                                    <i class="fas fa-filter me-2 text-primary"></i>
                                    Export Type
                                </h5>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="format-option" onclick="selectExportType('all')" id="option-all">
                                            <div class="format-icon">
                                                <i class="fas fa-database"></i>
                                            </div>
                                            <h6>All Data</h6>
                                            <p class="text-muted small mb-0">Export complete incident database</p>
                                            <input type="radio" name="export_type" value="all" checked class="d-none">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="format-option" onclick="selectExportType('filtered')" id="option-filtered">
                                            <div class="format-icon">
                                                <i class="fas fa-sliders-h"></i>
                                            </div>
                                            <h6>Filtered Data</h6>
                                            <p class="text-muted small mb-0">Apply specific filters before export</p>
                                            <input type="radio" name="export_type" value="filtered" class="d-none">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="format-option" onclick="selectExportType('summary')" id="option-summary">
                                            <div class="format-icon">
                                                <i class="fas fa-chart-bar"></i>
                                            </div>
                                            <h6>Summary Report</h6>
                                            <p class="text-muted small mb-0">Statistical overview and analytics</p>
                                            <input type="radio" name="export_type" value="summary" class="d-none">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filters Section -->
                        <div class="row mb-4" id="filters-section">
                            <div class="col-12">
                                <h5 class="mb-3">
                                    <i class="fas fa-filter me-2 text-primary"></i>
                                    Filter Options
                                </h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Date From</label>
                                        <input type="date" class="form-control" name="date_from">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Date To</label>
                                        <input type="date" class="form-control" name="date_to">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" name="status">
                                            <option value="all">All Statuses</option>
                                            <?php while($status = $statuses_result->fetch_assoc()): ?>
                                                <option value="<?php echo $status['status']; ?>">
                                                    <?php echo ucwords(str_replace('_', ' ', $status['status'])); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Incident Type</label>
                                        <select class="form-select" name="incident_type">
                                            <option value="all">All Types</option>
                                            <?php while($type = $types_result->fetch_assoc()): ?>
                                                <option value="<?php echo $type['incident_type']; ?>">
                                                    <?php echo htmlspecialchars($type['incident_type']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Export Format -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="mb-3">
                                    <i class="fas fa-file-export me-2 text-success"></i>
                                    Export Format
                                </h5>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="format-option" onclick="selectFormat('csv')" id="format-csv">
                                            <div class="format-icon">
                                                <i class="fas fa-file-csv"></i>
                                            </div>
                                            <h6>CSV Format</h6>
                                            <p class="text-muted small mb-0">Comma-separated values, Excel compatible</p>
                                            <input type="radio" name="format" value="csv" checked class="d-none">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="format-option" onclick="selectFormat('excel')" id="format-excel">
                                            <div class="format-icon">
                                                <i class="fas fa-file-excel"></i>
                                            </div>
                                            <h6>Excel Format</h6>
                                            <p class="text-muted small mb-0">Native Excel format (.xlsx)</p>
                                            <input type="radio" name="format" value="excel" class="d-none">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="format-option" onclick="selectFormat('pdf')" id="format-pdf">
                                            <div class="format-icon">
                                                <i class="fas fa-file-pdf"></i>
                                            </div>
                                            <h6>PDF Format</h6>
                                            <p class="text-muted small mb-0">Portable Document Format</p>
                                            <input type="radio" name="format" value="pdf" class="d-none">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Export Button -->
                        <div class="row">
                            <div class="col-12 text-center">
                                <button type="submit" name="export" class="btn btn-primary btn-lg">
                                    <i class="fas fa-download me-2"></i>Generate Export
                                </button>
                                <a href="reports.php" class="btn btn-outline-secondary btn-lg ms-2">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                            </div>
                        </div>

                        <!-- Preview Section -->
                        <div class="export-preview" id="preview-section" style="display: none;">
                            <h6 class="mb-3">
                                <i class="fas fa-eye me-2 text-info"></i>
                                Export Preview
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Type</th>
                                            <th>Location</th>
                                            <th>Reporter</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody id="preview-content">
                                        <!-- Preview content will be loaded here -->
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-muted small mt-2" id="preview-info">
                                Preview shows first 5 records. Full export will include all data.
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Export History -->
                <div class="export-card">
                    <h5 class="mb-4">
                        <i class="fas fa-history me-2 text-warning"></i>
                        Recent Exports
                    </h5>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Export history feature coming soon. Your downloads will be logged here.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Format selection
        function selectFormat(format) {
            document.querySelectorAll('.format-option').forEach(option => {
                option.classList.remove('selected');
            });
            document.getElementById('format-' + format).classList.add('selected');
            document.querySelector('input[name="format"][value="' + format + '"]').checked = true;
        }

        // Export type selection
        function selectExportType(type) {
            document.querySelectorAll('.format-option').forEach(option => {
                option.classList.remove('selected');
            });
            document.getElementById('option-' + type).classList.add('selected');
            document.querySelector('input[name="export_type"][value="' + type + '"]').checked = true;

            // Show/hide filters based on selection
            const filtersSection = document.getElementById('filters-section');
            if (type === 'filtered') {
                filtersSection.style.display = 'block';
            } else {
                filtersSection.style.display = 'none';
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            selectFormat('csv');
            selectExportType('all');
            
            // Initialize date pickers
            flatpickr('input[type="date"]', {
                dateFormat: 'Y-m-d',
                allowInput: true
            });
        });

        // Form validation
        document.getElementById('exportForm').addEventListener('submit', function(e) {
            const dateFrom = document.querySelector('input[name="date_from"]').value;
            const dateTo = document.querySelector('input[name="date_to"]').value;
            
            if (dateFrom && dateTo && new Date(dateFrom) > new Date(dateTo)) {
                e.preventDefault();
                alert('Date From cannot be after Date To');
                return false;
            }
            
            // Show loading state
            const submitBtn = document.querySelector('button[name="export"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generating...';
            submitBtn.disabled = true;
        });

        // Preview functionality (simplified)
        function generatePreview() {
            // In a real implementation, this would make an AJAX call to get preview data
            const previewSection = document.getElementById('preview-section');
            previewSection.style.display = 'block';
            
            // Simulate preview data
            const previewContent = document.getElementById('preview-content');
            previewContent.innerHTML = `
                <tr>
                    <td>#123</td>
                    <td>Theft</td>
                    <td>Main Street</td>
                    <td>John Doe</td>
                    <td><span class="badge bg-warning">Reported</span></td>
                    <td>2024-01-15</td>
                </tr>
                <tr>
                    <td>#124</td>
                    <td>Assault</td>
                    <td>Park Avenue</td>
                    <td>Jane Smith</td>
                    <td><span class="badge bg-primary">In Progress</span></td>
                    <td>2024-01-14</td>
                </tr>
            `;
        }
    </script>
</body>
</html>