<?php
session_start();

// Check if user is logged in, redirect to login if not
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

// Get user details from database
$user_sql = "SELECT full_name, email, phone FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $_SESSION['user_id']);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
$user_stmt->close();

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $incident_type = $_POST['incident_type'];
    $description = $_POST['description'];
    $location_name = $_POST['location_name'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $incident_date = $_POST['incident_date'];
    $reporter_name = $_POST['reporter_name'];
    $reporter_contact = $_POST['reporter_contact'];
    
    // Handle image upload
    $image_path = null;
    if (isset($_FILES['incident_image']) && $_FILES['incident_image']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $imageFileType = strtolower(pathinfo($_FILES["incident_image"]["name"], PATHINFO_EXTENSION));
        $filename = uniqid() . '.' . $imageFileType;
        $target_file = $target_dir . $filename;
        
        // Check if image file is an actual image
        $check = getimagesize($_FILES["incident_image"]["tmp_name"]);
        if ($check !== false) {
            // Check file size (max 5MB)
            if ($_FILES["incident_image"]["size"] > 5000000) {
                $upload_error = "Sorry, your file is too large. Maximum size is 5MB.";
            } elseif (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                $upload_error = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            } else {
                if (move_uploaded_file($_FILES["incident_image"]["tmp_name"], $target_file)) {
                    $image_path = $target_file;
                } else {
                    $upload_error = "Sorry, there was an error uploading your file.";
                }
            }
        } else {
            $upload_error = "File is not an image.";
        }
    }
    
    // Insert into database
    $sql = "INSERT INTO incidents (incident_type, description, location_name, latitude, longitude, incident_date, reporter_name, reporter_contact, image_path, user_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssddssssi", $incident_type, $description, $location_name, $latitude, $longitude, $incident_date, $reporter_name, $reporter_contact, $image_path, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $success_message = "Incident report has been submitted successfully.";
        // Clear form data after successful submission
        $_POST = array();
    } else {
        $error_message = "Failed to submit incident report. Please try again.";
    }
    
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Incident - Digital Occurrence Book</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
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
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 60px 0;
            margin-bottom: 30px;
        }
        
        .form-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
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
        
        .btn-danger {
            background-color: var(--accent-color);
            border: none;
            padding: 12px 30px;
            font-weight: 600;
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
        }
        
        #map {
            height: 400px;
            border-radius: 8px;
            margin-top: 10px;
            border: 1px solid #dee2e6;
        }
        
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
            display: none;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        footer {
            background-color: var(--primary-color);
            color: white;
            padding: 30px 0;
            margin-top: 50px;
        }
        
        .required:after {
            content: " *";
            color: red;
        }
        
        .user-info-badge {
            background-color: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 5px;
            padding: 10px 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="user_dashboard.php">
                <i class="fas fa-book me-2"></i>
                Digital Occurrence Book
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="user_dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="report.php">
                            <i class="fas fa-plus-circle me-1"></i>Report Incident
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my_reports.php">
                            <i class="fas fa-list me-1"></i>My Reports
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <h1 class="display-4 fw-bold">Report an Incident</h1>
                    <p class="lead">Submit detailed information about any incident with location and image evidence.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Report Form -->
    <section class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="form-container">
                    <!-- User Info Badge -->
                    <div class="user-info-badge">
                        <i class="fas fa-user-check text-primary me-2"></i>
                        <strong>Logged in as:</strong> <?php echo htmlspecialchars($_SESSION['full_name']); ?> 
                        (<?php echo ucfirst($_SESSION['user_type']); ?>)
                    </div>

                    <?php
                    // Display success message
                    if (isset($success_message)) {
                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Success!</strong> ' . $success_message . '
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                              </div>';
                    }
                    
                    // Display error message
                    if (isset($error_message)) {
                        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <strong>Error!</strong> ' . $error_message . '
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                              </div>';
                    }
                    
                    // Display upload error
                    if (isset($upload_error)) {
                        echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Warning!</strong> ' . $upload_error . '
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                              </div>';
                    }
                    ?>
                    
                    <form action="report.php" method="POST" enctype="multipart/form-data" id="incidentForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="incident_type" class="form-label required">Incident Type</label>
                                    <select class="form-select" id="incident_type" name="incident_type" required>
                                        <option value="">Select incident type</option>
                                        <option value="Theft" <?php echo (isset($_POST['incident_type']) && $_POST['incident_type'] == 'Theft') ? 'selected' : ''; ?>>Theft</option>
                                        <option value="Accident" <?php echo (isset($_POST['incident_type']) && $_POST['incident_type'] == 'Accident') ? 'selected' : ''; ?>>Accident</option>
                                        <option value="Security Breach" <?php echo (isset($_POST['incident_type']) && $_POST['incident_type'] == 'Security Breach') ? 'selected' : ''; ?>>Security Breach</option>
                                        <option value="Vandalism" <?php echo (isset($_POST['incident_type']) && $_POST['incident_type'] == 'Vandalism') ? 'selected' : ''; ?>>Vandalism</option>
                                        <option value="Harassment" <?php echo (isset($_POST['incident_type']) && $_POST['incident_type'] == 'Harassment') ? 'selected' : ''; ?>>Harassment</option>
                                        <option value="Fire" <?php echo (isset($_POST['incident_type']) && $_POST['incident_type'] == 'Fire') ? 'selected' : ''; ?>>Fire</option>
                                        <option value="Medical Emergency" <?php echo (isset($_POST['incident_type']) && $_POST['incident_type'] == 'Medical Emergency') ? 'selected' : ''; ?>>Medical Emergency</option>
                                        <option value="Other" <?php echo (isset($_POST['incident_type']) && $_POST['incident_type'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="incident_date" class="form-label required">Incident Date & Time</label>
                                    <input type="datetime-local" class="form-control" id="incident_date" name="incident_date" 
                                           value="<?php echo isset($_POST['incident_date']) ? $_POST['incident_date'] : date('Y-m-d\TH:i'); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label required">Incident Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4" 
                                      placeholder="Provide detailed description of the incident..." required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="location_name" class="form-label required">Location Name/Address</label>
                            <input type="text" class="form-control" id="location_name" name="location_name" 
                                   placeholder="e.g., Main Building, Room 205" 
                                   value="<?php echo isset($_POST['location_name']) ? htmlspecialchars($_POST['location_name']) : ''; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label required">Pin Incident Location</label>
                            <div id="map"></div>
                            <div class="mt-2">
                                <button type="button" id="getLocation" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-location-arrow me-1"></i> Use My Current Location
                                </button>
                                <small class="text-muted ms-2">Click on the map to set incident location</small>
                            </div>
                            <input type="hidden" id="latitude" name="latitude" value="<?php echo isset($_POST['latitude']) ? $_POST['latitude'] : ''; ?>">
                            <input type="hidden" id="longitude" name="longitude" value="<?php echo isset($_POST['longitude']) ? $_POST['longitude'] : ''; ?>">
                            <div class="form-text" id="locationFeedback">
                                <?php if (isset($_POST['latitude']) && isset($_POST['longitude'])): ?>
                                    <i class="fas fa-check text-success me-1"></i>Location set
                                <?php else: ?>
                                    <i class="fas fa-exclamation-triangle text-warning me-1"></i>Please set the incident location on the map
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="incident_image" class="form-label">Upload Image Evidence</label>
                            <input type="file" class="form-control" id="incident_image" name="incident_image" accept="image/*">
                            <div class="form-text">Upload an image related to the incident (optional, max 5MB, JPG, PNG, GIF)</div>
                            <img id="imagePreview" class="image-preview" src="#" alt="Image preview">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="reporter_name" class="form-label required">Your Name</label>
                                    <input type="text" class="form-control" id="reporter_name" name="reporter_name" 
                                           value="<?php echo isset($_POST['reporter_name']) ? htmlspecialchars($_POST['reporter_name']) : htmlspecialchars($user['full_name']); ?>" 
                                           required readonly>
                                    <div class="form-text">Auto-filled from your profile</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="reporter_contact" class="form-label required">Contact Information</label>
                                    <input type="text" class="form-control" id="reporter_contact" name="reporter_contact" 
                                           placeholder="Email or phone number" 
                                           value="<?php echo isset($_POST['reporter_contact']) ? htmlspecialchars($_POST['reporter_contact']) : htmlspecialchars($user['email']); ?>" 
                                           required>
                                    <div class="form-text">We'll use this to contact you about this report</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <a href="user_dashboard.php" class="btn btn-outline-secondary me-md-2">
                                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-paper-plane me-2"></i>Submit Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-book me-2"></i>Digital Occurrence Book</h5>
                    <p>Streamlining incident reporting and management for organizations.</p>
                </div>
                <div class="col-md-3">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="user_dashboard.php" class="text-light">Dashboard</a></li>
                        <li><a href="report.php" class="text-light">Report Incident</a></li>
                        <li><a href="my_reports.php" class="text-light">My Reports</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Contact</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-envelope me-2"></i> support@occurrencebook.com</li>
                        <li><i class="fas fa-phone me-2"></i> +1 (555) 123-4567</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4 bg-light">
            <div class="text-center">
                <p>&copy; 2023 Digital Occurrence Book System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Initialize map
        var map = L.map('map').setView([40.7128, -74.0060], 13);
        var marker = null;
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        
        // Add click event to map for placing marker
        map.on('click', function(e) {
            if (marker) {
                map.removeLayer(marker);
            }
            
            marker = L.marker(e.latlng).addTo(map)
                .bindPopup('Incident Location')
                .openPopup();
            
            document.getElementById('latitude').value = e.latlng.lat.toFixed(6);
            document.getElementById('longitude').value = e.latlng.lng.toFixed(6);
            
            // Update location feedback
            document.getElementById('locationFeedback').innerHTML = '<i class="fas fa-check text-success me-1"></i>Location set: ' + 
                e.latlng.lat.toFixed(6) + ', ' + e.latlng.lng.toFixed(6);
        });
        
        // Get current location button
        document.getElementById('getLocation').addEventListener('click', function() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    var lat = position.coords.latitude;
                    var lng = position.coords.longitude;
                    
                    map.setView([lat, lng], 16);
                    
                    if (marker) {
                        map.removeLayer(marker);
                    }
                    
                    marker = L.marker([lat, lng]).addTo(map)
                        .bindPopup('Your Current Location')
                        .openPopup();
                    
                    document.getElementById('latitude').value = lat.toFixed(6);
                    document.getElementById('longitude').value = lng.toFixed(6);
                    
                    // Update location feedback
                    document.getElementById('locationFeedback').innerHTML = '<i class="fas fa-check text-success me-1"></i>Location set: ' + 
                        lat.toFixed(6) + ', ' + lng.toFixed(6);
                }, function(error) {
                    alert('Error getting location: ' + error.message);
                });
            } else {
                alert('Geolocation is not supported by this browser.');
            }
        });
        
        // Image preview
        document.getElementById('incident_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Check file size (5MB max)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('imagePreview');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });
        
        // Form validation
        document.getElementById('incidentForm').addEventListener('submit', function(e) {
            const latitude = document.getElementById('latitude').value;
            const longitude = document.getElementById('longitude').value;
            
            if (!latitude || !longitude) {
                e.preventDefault();
                alert('Please set the incident location by clicking on the map or using your current location.');
                return false;
            }
            
            // Disable submit button to prevent double submission
            document.getElementById('submitBtn').disabled = true;
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
            
            return true;
        });
        
        // Initialize existing location if available
        const existingLat = document.getElementById('latitude').value;
        const existingLng = document.getElementById('longitude').value;
        if (existingLat && existingLng) {
            const lat = parseFloat(existingLat);
            const lng = parseFloat(existingLng);
            map.setView([lat, lng], 16);
            marker = L.marker([lat, lng]).addTo(map)
                .bindPopup('Incident Location')
                .openPopup();
        }
    </script>
</body>
</html>