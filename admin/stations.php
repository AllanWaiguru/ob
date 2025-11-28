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

// Create stations table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS police_stations (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    station_name VARCHAR(255) NOT NULL,
    station_code VARCHAR(50) UNIQUE NOT NULL,
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    district VARCHAR(100) NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    contact_number VARCHAR(20),
    email VARCHAR(100),
    officer_in_charge VARCHAR(255),
    jurisdiction_radius DECIMAL(8,2) DEFAULT 5.00,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (!$conn->query($create_table_sql)) {
    die("Error creating table: " . $conn->error);
}

// Handle form submission for creating new station
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_station'])) {
    $station_name = trim($_POST['station_name']);
    $station_code = trim($_POST['station_code']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $district = trim($_POST['district']);
    $latitude = floatval($_POST['latitude']);
    $longitude = floatval($_POST['longitude']);
    $contact_number = trim($_POST['contact_number']);
    $email = trim($_POST['email']);
    $officer_in_charge = trim($_POST['officer_in_charge']);
    $jurisdiction_radius = floatval($_POST['jurisdiction_radius']);
    
    // Validate required fields
    if (empty($station_name) || empty($station_code) || empty($address) || 
        empty($city) || empty($district) || empty($latitude) || empty($longitude)) {
        $error = "Please fill in all required fields.";
    } else {
        // Check if station code already exists
        $check_sql = "SELECT id FROM police_stations WHERE station_code = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $station_code);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Station code already exists. Please use a unique station code.";
        } else {
            // Insert new station
            $insert_sql = "INSERT INTO police_stations 
                          (station_name, station_code, address, city, district, latitude, longitude, 
                           contact_number, email, officer_in_charge, jurisdiction_radius) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sssssddsssd", 
                $station_name, $station_code, $address, $city, $district, 
                $latitude, $longitude, $contact_number, $email, $officer_in_charge, $jurisdiction_radius
            );
            
            if ($insert_stmt->execute()) {
                $success = "Police station registered successfully!";
                // Clear form fields
                $_POST = array();
            } else {
                $error = "Error registering station: " . $conn->error;
            }
            
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
}

// Get all stations for listing
$stations_sql = "SELECT * FROM police_stations ORDER BY created_at DESC";
$stations_result = $conn->query($stations_sql);

$conn->close();

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Police Stations - Digital Occurrence Book</title>
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
        
        .form-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .map-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        #stationMap {
            height: 400px;
            border-radius: 10px;
            border: 1px solid #dee2e6;
        }
        
        .coordinates-display {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
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
        
        .admin-badge {
            background: linear-gradient(135deg, var(--warning-color), #e67e22);
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .station-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
            border-left: 4px solid var(--secondary-color);
        }
        
        .action-buttons .btn {
            margin-right: 5px;
            margin-bottom: 5px;
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
        
        .location-marker {
            background: var(--accent-color);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
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
                            <a href="stations.php" class="nav-link active">
                                <i class="fas fa-building"></i> Police Stations
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
                            <h1 class="display-6 fw-bold">
                                <?php echo $action === 'create' ? 'Register New Police Station' : 'Police Stations Management'; ?>
                            </h1>
                            <p class="lead mb-0">
                                <?php echo $action === 'create' 
                                    ? 'Add new police station with location coordinates' 
                                    : 'Manage all police stations in the system'; ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <?php if ($action === 'create'): ?>
                                <a href="stations.php" class="btn btn-light">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Stations
                                </a>
                            <?php else: ?>
                                <a href="stations.php?action=create" class="btn btn-light">
                                    <i class="fas fa-plus me-2"></i>Register New Station
                                </a>
                                <a href="d.php" class="btn btn-outline-light">
                                    <i class="fas fa-home me-2"></i>Dashboard
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Display Messages -->
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($action === 'create'): ?>
                    <!-- Create Station Form -->
                    <div class="form-card">
                        <form method="POST" action="stations.php?action=create" id="stationForm">
                            <div class="row">
                                <!-- Basic Information -->
                                <div class="col-lg-6">
                                    <h5 class="mb-4">
                                        <i class="fas fa-info-circle me-2 text-primary"></i>
                                        Station Information
                                    </h5>
                                    
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Station Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="station_name" 
                                                   value="<?php echo htmlspecialchars($_POST['station_name'] ?? ''); ?>" 
                                                   required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Station Code <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="station_code" 
                                                   value="<?php echo htmlspecialchars($_POST['station_code'] ?? ''); ?>" 
                                                   required>
                                            <small class="text-muted">Unique code for the station (e.g., PS001)</small>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Address <span class="text-danger">*</span></label>
                                            <textarea class="form-control" name="address" rows="2" required><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">City <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="city" 
                                                   value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>" 
                                                   required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">District <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="district" 
                                                   value="<?php echo htmlspecialchars($_POST['district'] ?? ''); ?>" 
                                                   required>
                                        </div>
                                    </div>

                                    <h5 class="mt-5 mb-4">
                                        <i class="fas fa-phone me-2 text-primary"></i>
                                        Contact Information
                                    </h5>
                                    
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Contact Number</label>
                                            <input type="tel" class="form-control" name="contact_number" 
                                                   value="<?php echo htmlspecialchars($_POST['contact_number'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Email Address</label>
                                            <input type="email" class="form-control" name="email" 
                                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Officer in Charge</label>
                                            <input type="text" class="form-control" name="officer_in_charge" 
                                                   value="<?php echo htmlspecialchars($_POST['officer_in_charge'] ?? ''); ?>">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Jurisdiction Radius (km) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" name="jurisdiction_radius" 
                                                   value="<?php echo htmlspecialchars($_POST['jurisdiction_radius'] ?? '5.00'); ?>" 
                                                   step="0.1" min="1" max="50" required>
                                            <small class="text-muted">Radius in kilometers for automatic incident assignment</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Map and Coordinates -->
                                <div class="col-lg-6">
                                    <h5 class="mb-4">
                                        <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                                        Location Coordinates
                                    </h5>
                                    
                                    <div class="map-container">
                                        <div id="stationMap"></div>
                                        <div class="map-controls">
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="useCurrentLocation">
                                                <i class="fas fa-location-arrow me-1"></i>My Location
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="clearMarker">
                                                <i class="fas fa-times me-1"></i>Clear
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="coordinates-display">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Latitude <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control" id="latitude" name="latitude" 
                                                       step="any" required readonly
                                                       value="<?php echo htmlspecialchars($_POST['latitude'] ?? ''); ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Longitude <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control" id="longitude" name="longitude" 
                                                       step="any" required readonly
                                                       value="<?php echo htmlspecialchars($_POST['longitude'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <small class="text-muted">
                                                <i class="fas fa-info-circle me-1"></i>
                                                Click on the map to set station location or use "My Location" button
                                            </small>
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <div class="alert alert-info">
                                            <h6><i class="fas fa-lightbulb me-2"></i>How this helps:</h6>
                                            <p class="mb-0 small">
                                                When incidents are reported, the system will automatically detect the nearest police station 
                                                based on these coordinates and assign the incident for faster response.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-12 text-center">
                                    <button type="submit" name="create_station" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save me-2"></i>Register Station
                                    </button>
                                    <a href="stations.php" class="btn btn-outline-secondary btn-lg ms-2">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>

                <?php else: ?>
                    <!-- Stations List -->
                    <div class="form-card">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="mb-0">
                                <i class="fas fa-building me-2 text-primary"></i>
                                All Police Stations
                            </h5>
                            <div class="d-flex">
                                <input type="text" class="form-control me-2" placeholder="Search stations..." id="searchStations">
                                <a href="stations.php?action=create" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Add Station
                                </a>
                            </div>
                        </div>

                        <?php if ($stations_result->num_rows > 0): ?>
                            <div class="row" id="stationsList">
                                <?php while($station = $stations_result->fetch_assoc()): ?>
                                    <div class="col-lg-6 mb-4">
                                        <div class="station-card">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($station['station_name']); ?></h6>
                                                    <small class="text-muted">Code: <?php echo htmlspecialchars($station['station_code']); ?></small>
                                                </div>
                                                <span class="badge <?php echo $station['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                                    <?php echo $station['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </div>
                                            
                                            <p class="text-muted small mb-2">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                <?php echo htmlspecialchars($station['address']); ?>, 
                                                <?php echo htmlspecialchars($station['city']); ?>
                                            </p>
                                            
                                            <div class="row g-2 mb-3">
                                                <div class="col-6">
                                                    <small class="text-muted">
                                                        <i class="fas fa-phone me-1"></i>
                                                        <?php echo $station['contact_number'] ? htmlspecialchars($station['contact_number']) : 'N/A'; ?>
                                                    </small>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted">
                                                        <i class="fas fa-user me-1"></i>
                                                        <?php echo $station['officer_in_charge'] ? htmlspecialchars($station['officer_in_charge']) : 'N/A'; ?>
                                                    </small>
                                                </div>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    Jurisdiction: <?php echo $station['jurisdiction_radius']; ?> km
                                                </small>
                                                <div class="action-buttons">
                                                    <a href="stations.php?action=edit&id=<?php echo $station['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-outline-danger" 
                                                            onclick="confirmDelete(<?php echo $station['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-building fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No Police Stations Registered</h5>
                                <p class="text-muted">Get started by registering your first police station.</p>
                                <a href="stations.php?action=create" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Register First Station
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        <?php if ($action === 'create'): ?>
        // Initialize Leaflet Map for station registration
        let map, marker;

        function initMap() {
            // Default coordinates (center of your city/region)
            const defaultLat = 40.7128;
            const defaultLng = -74.0060;
            
            map = L.map('stationMap').setView([defaultLat, defaultLng], 13);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 18
            }).addTo(map);

            // Add click event to map
            map.on('click', function(e) {
                setMarker(e.latlng.lat, e.latlng.lng);
            });

            // Try to get user's current location
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const userLat = position.coords.latitude;
                        const userLng = position.coords.longitude;
                        map.setView([userLat, userLng], 15);
                    },
                    function(error) {
                        console.log('Geolocation error:', error);
                    }
                );
            }
        }

        function setMarker(lat, lng) {
            // Remove existing marker
            if (marker) {
                map.removeLayer(marker);
            }
            
            // Add new marker
            marker = L.marker([lat, lng]).addTo(map)
                .bindPopup('Station Location<br>Lat: ' + lat.toFixed(6) + '<br>Lng: ' + lng.toFixed(6))
                .openPopup();
            
            // Update form fields
            document.getElementById('latitude').value = lat.toFixed(6);
            document.getElementById('longitude').value = lng.toFixed(6);
        }

        // Use current location button
        document.getElementById('useCurrentLocation').addEventListener('click', function() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        setMarker(lat, lng);
                        map.setView([lat, lng], 15);
                    },
                    function(error) {
                        alert('Unable to get your location. Please enable location services.');
                    }
                );
            } else {
                alert('Geolocation is not supported by this browser.');
            }
        });

        // Clear marker button
        document.getElementById('clearMarker').addEventListener('click', function() {
            if (marker) {
                map.removeLayer(marker);
                marker = null;
            }
            document.getElementById('latitude').value = '';
            document.getElementById('longitude').value = '';
        });

        // Form validation
        document.getElementById('stationForm').addEventListener('submit', function(e) {
            const lat = document.getElementById('latitude').value;
            const lng = document.getElementById('longitude').value;
            
            if (!lat || !lng) {
                e.preventDefault();
                alert('Please set the station location on the map.');
                return false;
            }
        });

        // Initialize map when page loads
        document.addEventListener('DOMContentLoaded', initMap);
        <?php endif; ?>

        // Stations list search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchStations');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const stations = document.querySelectorAll('.station-card');
                    
                    stations.forEach(station => {
                        const stationText = station.textContent.toLowerCase();
                        if (stationText.includes(searchTerm)) {
                            station.parentElement.style.display = 'block';
                        } else {
                            station.parentElement.style.display = 'none';
                        }
                    });
                });
            }
        });

        // Delete confirmation
        function confirmDelete(stationId) {
            if (confirm('Are you sure you want to delete this police station? This action cannot be undone.')) {
                window.location.href = 'delete_station.php?id=' + stationId;
            }
        }
    </script>
</body>
</html>