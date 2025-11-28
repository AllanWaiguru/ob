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
        }
        
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
            display: none;
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
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.html">
                <i class="fas fa-book me-2"></i>
                Digital Occurrence Book
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.html">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="report.php">Report Incident</a>
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
                    <?php
                    // Database connection
                    $servername = "localhost";
                    $username = "root"; // Change as needed
                    $password = ""; // Change as needed
                    $dbname = "ob";

                    // Create connection
                    $conn = new mysqli($servername, $username, $password, $dbname);

                    // Check connection
                    if ($conn->connect_error) {
                        die("Connection failed: " . $conn->connect_error);
                    }

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
                                if (move_uploaded_file($_FILES["incident_image"]["tmp_name"], $target_file)) {
                                    $image_path = $target_file;
                                }
                            }
                        }
                        
                        // Insert into database
                        $sql = "INSERT INTO incidents (incident_type, description, location_name, latitude, longitude, incident_date, reporter_name, reporter_contact, image_path)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("sssddssss", $incident_type, $description, $location_name, $latitude, $longitude, $incident_date, $reporter_name, $reporter_contact, $image_path);
                        
                        if ($stmt->execute()) {
                            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <strong>Success!</strong> Incident report has been submitted successfully.
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                  </div>';
                        } else {
                            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <strong>Error!</strong> Failed to submit incident report. Please try again.
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                  </div>';
                        }
                        
                        $stmt->close();
                    }
                    
                    $conn->close();
                    ?>
                    
                    <form action="report.php" method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="incident_type" class="form-label required">Incident Type</label>
                                    <select class="form-select" id="incident_type" name="incident_type" required>
                                        <option value="">Select incident type</option>
                                        <option value="Theft">Theft</option>
                                        <option value="Accident">Accident</option>
                                        <option value="Security Breach">Security Breach</option>
                                        <option value="Vandalism">Vandalism</option>
                                        <option value="Harassment">Harassment</option>
                                        <option value="Fire">Fire</option>
                                        <option value="Medical Emergency">Medical Emergency</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="incident_date" class="form-label required">Incident Date & Time</label>
                                    <input type="datetime-local" class="form-control" id="incident_date" name="incident_date" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label required">Incident Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4" placeholder="Provide detailed description of the incident..." required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="location_name" class="form-label required">Location Name/Address</label>
                            <input type="text" class="form-control" id="location_name" name="location_name" placeholder="e.g., Main Building, Room 205" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Pin Incident Location</label>
                            <div id="map"></div>
                            <div class="mt-2">
                                <button type="button" id="getLocation" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-location-arrow me-1"></i> Use My Current Location
                                </button>
                            </div>
                            <input type="hidden" id="latitude" name="latitude">
                            <input type="hidden" id="longitude" name="longitude">
                        </div>
                        
                        <div class="mb-3">
                            <label for="incident_image" class="form-label">Upload Image Evidence</label>
                            <input type="file" class="form-control" id="incident_image" name="incident_image" accept="image/*">
                            <div class="form-text">Upload an image related to the incident (optional)</div>
                            <img id="imagePreview" class="image-preview" src="#" alt="Image preview">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="reporter_name" class="form-label required">Your Name</label>
                                    <input type="text" class="form-control" id="reporter_name" name="reporter_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="reporter_contact" class="form-label required">Contact Information</label>
                                    <input type="text" class="form-control" id="reporter_contact" name="reporter_contact" placeholder="Email or phone number" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <a href="index.html" class="btn btn-outline-secondary me-md-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
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
                        <li><a href="login.php" class="text-light">Login</a></li>
                        <li><a href="report.php" class="text-light">Report Incident</a></li>
                        <li><a href="index.html" class="text-light">Home</a></li>
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
        var map = L.map('map').setView([40.7128, -74.0060], 13); // Default to New York
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
            
            document.getElementById('latitude').value = e.latlng.lat;
            document.getElementById('longitude').value = e.latlng.lng;
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
                    
                    document.getElementById('latitude').value = lat;
                    document.getElementById('longitude').value = lng;
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
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('imagePreview');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });
        
        // Set current date/time as default
        document.getElementById('incident_date').value = new Date().toISOString().slice(0, 16);
    </script>
</body>
</html>