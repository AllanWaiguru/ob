<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
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

// Get all stations with their coordinates
$stations_sql = "SELECT * FROM police_stations WHERE is_active = 1 ORDER BY station_name";
$stations_result = $conn->query($stations_sql);

$stations = [];
$stations_for_map = [];

while ($station = $stations_result->fetch_assoc()) {
    $stations[] = $station;
    if ($station['latitude'] && $station['longitude']) {
        $stations_for_map[] = $station;
    }
}

// Get station statistics
$stats_sql = "SELECT 
    COUNT(*) as total_stations,
    COUNT(DISTINCT city) as cities_covered,
    COUNT(DISTINCT district) as districts_covered,
    AVG(jurisdiction_radius) as avg_jurisdiction
    FROM police_stations 
    WHERE is_active = 1";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Police Stations - Digital Occurrence Book</title>
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
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border-top: 4px solid var(--secondary-color);
            height: 100%;
            transition: transform 0.3s;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
            color: var(--primary-color);
        }
        
        .map-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        #stationsMap {
            height: 500px;
            border-radius: 10px;
            border: 1px solid #dee2e6;
        }
        
        .station-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
            border-left: 4px solid var(--secondary-color);
            transition: all 0.3s;
            height: 100%;
        }
        
        .station-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 25px rgba(0,0,0,0.15);
        }
        
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
        
        .jurisdiction-badge {
            background: linear-gradient(135deg, var(--success-color), #27ae60);
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
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
            border: 2px solid white;
        }
        
        .station-marker {
            background: var(--secondary-color);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .filter-section {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        }
        
        .station-info-window {
            min-width: 250px;
        }
        
        .station-info-window h6 {
            color: var(--primary-color);
            margin-bottom: 8px;
        }
        
        .distance-info {
            background: #e8f4fd;
            border-left: 4px solid var(--secondary-color);
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
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
            <?php endif; ?>

            <!-- Main Content -->
            <div class="<?php echo $_SESSION['user_type'] === 'admin' ? 'col-lg-10' : 'col-12'; ?> main-content">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="display-6 fw-bold">Police Stations Network</h1>
                            <p class="lead mb-0">View all police stations and their coverage areas</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <?php if ($_SESSION['user_type'] === 'admin'): ?>
                                <a href="stations.php?action=create" class="btn btn-light">
                                    <i class="fas fa-plus me-2"></i>Add New Station
                                </a>
                            <?php endif; ?>
                            <a href="d.php" class="btn btn-outline-light">
                                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="stats-card">
                            <div class="stats-number"><?php echo $stats['total_stations']; ?></div>
                            <div class="text-muted">Total Stations</div>
                            <i class="fas fa-building fa-2x mt-3 text-primary"></i>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stats-card">
                            <div class="stats-number"><?php echo $stats['cities_covered']; ?></div>
                            <div class="text-muted">Cities Covered</div>
                            <i class="fas fa-city fa-2x mt-3 text-success"></i>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stats-card">
                            <div class="stats-number"><?php echo $stats['districts_covered']; ?></div>
                            <div class="text-muted">Districts Covered</div>
                            <i class="fas fa-map fa-2x mt-3 text-warning"></i>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stats-card">
                            <div class="stats-number"><?php echo number_format($stats['avg_jurisdiction'], 1); ?> km</div>
                            <div class="text-muted">Avg Jurisdiction</div>
                            <i class="fas fa-expand fa-2x mt-3 text-info"></i>
                        </div>
                    </div>
                </div>

                <!-- Stations Map -->
                <div class="map-container">
                    <h5 class="mb-4">
                        <i class="fas fa-map-marked-alt me-2 text-primary"></i>
                        Police Stations Network Map
                    </h5>
                    <div id="stationsMap"></div>
                    <div class="map-legend">
                        <h6 class="mb-2">Map Legend</h6>
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #3498db;"></div>
                            <small>Police Station</small>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: rgba(52, 152, 219, 0.2); border: 1px dashed #3498db;"></div>
                            <small>Jurisdiction Area</small>
                        </div>
                    </div>
                    <div class="map-controls">
                        <button class="btn btn-sm btn-outline-primary" id="fitBounds">
                            <i class="fas fa-expand me-1"></i>View All
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" id="toggleJurisdiction">
                            <i class="fas fa-circle me-1"></i>Toggle Areas
                        </button>
                        <?php if ($_SESSION['user_type'] === 'admin'): ?>
                            <a href="stations.php?action=create" class="btn btn-sm btn-success">
                                <i class="fas fa-plus me-1"></i>Add Station
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="filter-section">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="mb-0">
                                <i class="fas fa-filter me-2 text-primary"></i>
                                Filter Stations
                            </h5>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Search by station name, city, or district..." id="searchStations">
                                <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stations Grid -->
                <div class="row" id="stationsGrid">
                    <?php if (count($stations) > 0): ?>
                        <?php foreach($stations as $station): ?>
                            <div class="col-lg-4 col-md-6 mb-4 station-item" 
                                 data-name="<?php echo strtolower(htmlspecialchars($station['station_name'])); ?>"
                                 data-city="<?php echo strtolower(htmlspecialchars($station['city'])); ?>"
                                 data-district="<?php echo strtolower(htmlspecialchars($station['district'])); ?>">
                                <div class="station-card">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($station['station_name']); ?></h6>
                                            <small class="text-muted">Code: <?php echo htmlspecialchars($station['station_code']); ?></small>
                                        </div>
                                        <span class="jurisdiction-badge">
                                            <?php echo $station['jurisdiction_radius']; ?> km
                                        </span>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <p class="text-muted small mb-2">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo htmlspecialchars($station['address']); ?>
                                        </p>
                                        <p class="text-muted small mb-0">
                                            <i class="fas fa-city me-1"></i>
                                            <?php echo htmlspecialchars($station['city']); ?>, 
                                            <?php echo htmlspecialchars($station['district']); ?>
                                        </p>
                                    </div>
                                    
                                    <div class="row g-2 mb-3">
                                        <?php if ($station['contact_number']): ?>
                                        <div class="col-12">
                                            <small class="text-muted">
                                                <i class="fas fa-phone me-1"></i>
                                                <?php echo htmlspecialchars($station['contact_number']); ?>
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($station['email']): ?>
                                        <div class="col-12">
                                            <small class="text-muted">
                                                <i class="fas fa-envelope me-1"></i>
                                                <?php echo htmlspecialchars($station['email']); ?>
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($station['officer_in_charge']): ?>
                                        <div class="col-12">
                                            <small class="text-muted">
                                                <i class="fas fa-user-shield me-1"></i>
                                                <?php echo htmlspecialchars($station['officer_in_charge']); ?>
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-map-pin me-1"></i>
                                            <?php echo $station['latitude'] ? number_format($station['latitude'], 4) : 'N/A'; ?>, 
                                            <?php echo $station['longitude'] ? number_format($station['longitude'], 4) : 'N/A'; ?>
                                        </small>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-outline-primary view-on-map" 
                                                    data-lat="<?php echo $station['latitude']; ?>" 
                                                    data-lng="<?php echo $station['longitude']; ?>"
                                                    data-name="<?php echo htmlspecialchars($station['station_name']); ?>">
                                                <i class="fas fa-map-marker-alt"></i>
                                            </button>
                                            <?php if ($_SESSION['user_type'] === 'admin'): ?>
                                                <a href="stations.php?action=edit&id=<?php echo $station['id']; ?>" 
                                                   class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="text-center py-5">
                                <i class="fas fa-building fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No Police Stations Found</h5>
                                <p class="text-muted">There are no police stations registered in the system yet.</p>
                                <?php if ($_SESSION['user_type'] === 'admin'): ?>
                                    <a href="stations.php?action=create" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Register First Station
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Initialize Leaflet Map
        let map;
        let markers = [];
        let circles = [];
        let showJurisdiction = true;

        function initMap() {
            // Default coordinates
            const defaultLat = 40.7128;
            const defaultLng = -74.0060;
            const defaultZoom = 10;
            
            map = L.map('stationsMap').setView([defaultLat, defaultLng], defaultZoom);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 18
            }).addTo(map);

            // Add stations to map
            <?php foreach($stations_for_map as $station): ?>
                addStationToMap(
                    <?php echo $station['latitude']; ?>, 
                    <?php echo $station['longitude']; ?>, 
                    '<?php echo addslashes($station['station_name']); ?>',
                    '<?php echo addslashes($station['station_code']); ?>',
                    '<?php echo addslashes($station['address']); ?>',
                    '<?php echo addslashes($station['city']); ?>',
                    '<?php echo addslashes($station['contact_number']); ?>',
                    <?php echo $station['jurisdiction_radius']; ?>
                );
            <?php endforeach; ?>

            // Fit map to show all markers
            if (markers.length > 0) {
                const group = new L.featureGroup(markers);
                map.fitBounds(group.getBounds().pad(0.1));
            }
        }

        function addStationToMap(lat, lng, name, code, address, city, contact, radius) {
            // Create custom icon
            const stationIcon = L.divIcon({
                className: 'custom-div-icon',
                html: `<div style="background-color: #3498db; width: 12px; height: 12px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"></div>`,
                iconSize: [18, 18],
                iconAnchor: [9, 9]
            });

            // Add marker
            const marker = L.marker([lat, lng], { icon: stationIcon }).addTo(map);
            markers.push(marker);

            // Add jurisdiction circle
            const circle = L.circle([lat, lng], {
                color: '#3498db',
                fillColor: '#3498db',
                fillOpacity: 0.1,
                weight: 2,
                dashArray: '5, 5',
                radius: radius * 1000 // Convert km to meters
            }).addTo(map);
            circles.push(circle);

            // Create popup content
            const popupContent = `
                <div class="station-info-window">
                    <h6>${name}</h6>
                    <p class="mb-1"><strong>Code:</strong> ${code}</p>
                    <p class="mb-1"><strong>Address:</strong> ${address}, ${city}</p>
                    ${contact ? `<p class="mb-1"><strong>Contact:</strong> ${contact}</p>` : ''}
                    <div class="distance-info">
                        <small><strong>Jurisdiction:</strong> ${radius} km radius</small>
                    </div>
                </div>
            `;

            marker.bindPopup(popupContent);

            // Add click event to center map on station
            marker.on('click', function() {
                map.setView([lat, lng], 14);
            });
        }

        // Map controls
        document.getElementById('fitBounds').addEventListener('click', function() {
            if (markers.length > 0) {
                const group = new L.featureGroup(markers);
                map.fitBounds(group.getBounds().pad(0.1));
            }
        });

        document.getElementById('toggleJurisdiction').addEventListener('click', function() {
            showJurisdiction = !showJurisdiction;
            circles.forEach(circle => {
                if (showJurisdiction) {
                    map.addLayer(circle);
                } else {
                    map.removeLayer(circle);
                }
            });
            this.innerHTML = showJurisdiction ? 
                '<i class="fas fa-circle me-1"></i>Hide Areas' : 
                '<i class="fas fa-circle me-1"></i>Show Areas';
        });

        // Search functionality
        document.getElementById('searchStations').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const stationItems = document.querySelectorAll('.station-item');
            
            stationItems.forEach(item => {
                const stationName = item.getAttribute('data-name');
                const stationCity = item.getAttribute('data-city');
                const stationDistrict = item.getAttribute('data-district');
                
                if (stationName.includes(searchTerm) || 
                    stationCity.includes(searchTerm) || 
                    stationDistrict.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        document.getElementById('clearSearch').addEventListener('click', function() {
            document.getElementById('searchStations').value = '';
            const stationItems = document.querySelectorAll('.station-item');
            stationItems.forEach(item => {
                item.style.display = 'block';
            });
        });

        // View on map functionality
        document.querySelectorAll('.view-on-map').forEach(button => {
            button.addEventListener('click', function() {
                const lat = parseFloat(this.getAttribute('data-lat'));
                const lng = parseFloat(this.getAttribute('data-lng'));
                const name = this.getAttribute('data-name');
                
                if (lat && lng) {
                    map.setView([lat, lng], 15);
                    
                    // Find and open the corresponding marker's popup
                    markers.forEach(marker => {
                        const markerLat = marker.getLatLng().lat;
                        const markerLng = marker.getLatLng().lng;
                        
                        if (Math.abs(markerLat - lat) < 0.0001 && Math.abs(markerLng - lng) < 0.0001) {
                            marker.openPopup();
                        }
                    });
                }
            });
        });

        // Initialize map when page loads
        document.addEventListener('DOMContentLoaded', initMap);
    </script>
</body>
</html>