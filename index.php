<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Occurrence Book</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 100px 0;
            margin-bottom: 50px;
        }
        
        .feature-card {
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            border-radius: 10px;
            overflow: hidden;
            height: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .card-icon {
            font-size: 3rem;
            color: var(--secondary-color);
            margin-bottom: 20px;
        }
        
        .action-buttons {
            margin: 50px 0;
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
        
        footer {
            background-color: var(--primary-color);
            color: white;
            padding: 30px 0;
            margin-top: 50px;
        }
        
        .section-title {
            position: relative;
            margin-bottom: 40px;
            text-align: center;
        }
        
        .section-title:after {
            content: '';
            display: block;
            width: 80px;
            height: 3px;
            background-color: var(--secondary-color);
            margin: 15px auto;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-book me-2"></i>
                Digital Occurrence Book
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="report.php">Report Incident</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold">Digital Occurrence Book System</h1>
                    <p class="lead">Streamline incident reporting and management with our secure digital platform. Replace paper-based logs with an efficient, searchable system.</p>
                    <div class="mt-4">
                        <a href="login.php" class="btn btn-light btn-lg me-3">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </a>
                        <a href="report.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-plus-circle me-2"></i>Report Incident
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <img src="https://cdn-icons-png.flaticon.com/512/3050/3050151.png" alt="Digital Book" class="img-fluid" style="max-height: 300px;">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5">
        <div class="container">
            <h2 class="section-title">System Features</h2>
            <div class="row g-4">
                <!-- Card 1 -->
                <div class="col-md-6 col-lg-4">
                    <div class="card feature-card shadow-sm">
                        <div class="card-body p-4 text-center">
                            <div class="card-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h4 class="card-title">Secure Access</h4>
                            <p class="card-text">Role-based authentication ensures only authorized personnel can access sensitive information.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Card 2 -->
                <div class="col-md-6 col-lg-4">
                    <div class="card feature-card shadow-sm">
                        <div class="card-body p-4 text-center">
                            <div class="card-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            <h4 class="card-title">Quick Search</h4>
                            <p class="card-text">Find records instantly with our powerful search functionality across all data fields.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Card 3 -->
                <div class="col-md-6 col-lg-4">
                    <div class="card feature-card shadow-sm">
                        <div class="card-body p-4 text-center">
                            <div class="card-icon">
                                <i class="fas fa-file-export"></i>
                            </div>
                            <h4 class="card-title">Export Reports</h4>
                            <p class="card-text">Generate and export comprehensive reports in multiple formats for analysis and sharing.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Card 4 -->
                <div class="col-md-6 col-lg-4">
                    <div class="card feature-card shadow-sm">
                        <div class="card-body p-4 text-center">
                            <div class="card-icon">
                                <i class="fas fa-bell"></i>
                            </div>
                            <h4 class="card-title">Notifications</h4>
                            <p class="card-text">Get real-time alerts for new incidents, updates, or pending actions that require attention.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Card 5 -->
                <div class="col-md-6 col-lg-4">
                    <div class="card feature-card shadow-sm">
                        <div class="card-body p-4 text-center">
                            <div class="card-icon">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <h4 class="card-title">Analytics Dashboard</h4>
                            <p class="card-text">Visualize incident trends and patterns with interactive charts and statistics.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Card 6 -->
                <div class="col-md-6 col-lg-4">
                    <div class="card feature-card shadow-sm">
                        <div class="card-body p-4 text-center">
                            <div class="card-icon">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <h4 class="card-title">Mobile Responsive</h4>
                            <p class="card-text">Access the system from any device - desktop, tablet, or mobile - with full functionality.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Action Buttons Section -->
    <section class="action-buttons">
        <div class="container text-center">
            <h2 class="section-title">Quick Actions</h2>
            <div class="row justify-content-center">
                <div class="col-md-5 mb-3">
                    <a href="login.php" class="btn btn-primary btn-lg w-100 py-3">
                        <i class="fas fa-sign-in-alt me-2"></i>Login to System
                    </a>
                    <p class="mt-2 text-muted">Access the full digital occurrence book system</p>
                </div>
                <div class="col-md-5 mb-3">
                    <a href="report.php" class="btn btn-danger btn-lg w-100 py-3">
                        <i class="fas fa-plus-circle me-2"></i>Report New Incident
                    </a>
                    <p class="mt-2 text-muted">Submit a new incident report directly</p>
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
                        <li><a href="#features" class="text-light">Features</a></li>
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
</body>
</html>