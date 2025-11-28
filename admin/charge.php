<?php
// Database configuration and classes
class Database {
    private $host = "localhost";
    private $db_name = "ob";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

// Incident class
class Incident {
    private $conn;
    private $table_name = "incidents";

    public $id;
    public $incident_type;
    public $description;
    public $location_name;
    public $latitude;
    public $longitude;
    public $incident_date;
    public $reporter_name;
    public $reporter_contact;
    public $user_id;
    public $status;
    public $image_path;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create incident
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET incident_type=:incident_type, description=:description, 
                    location_name=:location_name, latitude=:latitude, longitude=:longitude,
                    incident_date=:incident_date, reporter_name=:reporter_name, 
                    reporter_contact=:reporter_contact, user_id=:user_id, 
                    status=:status, image_path=:image_path";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->incident_type = htmlspecialchars(strip_tags($this->incident_type));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->location_name = htmlspecialchars(strip_tags($this->location_name));
        $this->reporter_name = htmlspecialchars(strip_tags($this->reporter_name));

        // Bind parameters
        $stmt->bindParam(":incident_type", $this->incident_type);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":location_name", $this->location_name);
        $stmt->bindParam(":latitude", $this->latitude);
        $stmt->bindParam(":longitude", $this->longitude);
        $stmt->bindParam(":incident_date", $this->incident_date);
        $stmt->bindParam(":reporter_name", $this->reporter_name);
        $stmt->bindParam(":reporter_contact", $this->reporter_contact);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":image_path", $this->image_path);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Read all incidents
    public function read() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Read single incident
    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->incident_type = $row['incident_type'];
            $this->description = $row['description'];
            $this->location_name = $row['location_name'];
            $this->latitude = $row['latitude'];
            $this->longitude = $row['longitude'];
            $this->incident_date = $row['incident_date'];
            $this->reporter_name = $row['reporter_name'];
            $this->reporter_contact = $row['reporter_contact'];
            $this->user_id = $row['user_id'];
            $this->status = $row['status'];
            $this->image_path = $row['image_path'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }

    // Get incidents for dropdown (for charge sheets)
    public function getIncidentsForDropdown() {
        $query = "SELECT id, incident_type, location_name, incident_date FROM " . $this->table_name . " ORDER BY incident_date DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}

// ChargeSheet class
class ChargeSheet {
    private $conn;
    private $table_name = "charge_sheets";

    public $id;
    public $incident_id;
    public $charge_sheet_number;
    public $date_issued;
    public $issuing_officer;
    public $suspect_name;
    public $suspect_address;
    public $suspect_id_number;
    public $suspect_phone;
    public $charges;
    public $facts_of_case;
    public $evidence;
    public $witnesses;
    public $bail_conditions;
    public $court_date;
    public $court_name;
    public $status;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create charge sheet
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET incident_id=:incident_id, charge_sheet_number=:charge_sheet_number, 
                    date_issued=:date_issued, issuing_officer=:issuing_officer, 
                    suspect_name=:suspect_name, suspect_address=:suspect_address, 
                    suspect_id_number=:suspect_id_number, suspect_phone=:suspect_phone, 
                    charges=:charges, facts_of_case=:facts_of_case, evidence=:evidence, 
                    witnesses=:witnesses, bail_conditions=:bail_conditions, 
                    court_date=:court_date, court_name=:court_name, status=:status";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->incident_id = htmlspecialchars(strip_tags($this->incident_id));
        $this->charge_sheet_number = htmlspecialchars(strip_tags($this->charge_sheet_number));
        $this->issuing_officer = htmlspecialchars(strip_tags($this->issuing_officer));
        $this->suspect_name = htmlspecialchars(strip_tags($this->suspect_name));
        $this->suspect_address = htmlspecialchars(strip_tags($this->suspect_address));
        $this->suspect_id_number = htmlspecialchars(strip_tags($this->suspect_id_number));

        // Bind parameters
        $stmt->bindParam(":incident_id", $this->incident_id);
        $stmt->bindParam(":charge_sheet_number", $this->charge_sheet_number);
        $stmt->bindParam(":date_issued", $this->date_issued);
        $stmt->bindParam(":issuing_officer", $this->issuing_officer);
        $stmt->bindParam(":suspect_name", $this->suspect_name);
        $stmt->bindParam(":suspect_address", $this->suspect_address);
        $stmt->bindParam(":suspect_id_number", $this->suspect_id_number);
        $stmt->bindParam(":suspect_phone", $this->suspect_phone);
        $stmt->bindParam(":charges", $this->charges);
        $stmt->bindParam(":facts_of_case", $this->facts_of_case);
        $stmt->bindParam(":evidence", $this->evidence);
        $stmt->bindParam(":witnesses", $this->witnesses);
        $stmt->bindParam(":bail_conditions", $this->bail_conditions);
        $stmt->bindParam(":court_date", $this->court_date);
        $stmt->bindParam(":court_name", $this->court_name);
        $stmt->bindParam(":status", $this->status);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Read all charge sheets with incident details
    public function readWithIncidents() {
        $query = "SELECT cs.*, i.incident_type, i.location_name, i.incident_date 
                  FROM " . $this->table_name . " cs
                  LEFT JOIN incidents i ON cs.incident_id = i.id
                  ORDER BY cs.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Read single charge sheet with incident details
    public function readOneWithIncident() {
        $query = "SELECT cs.*, i.incident_type, i.description, i.location_name, i.incident_date, i.reporter_name, i.latitude, i.longitude
                  FROM " . $this->table_name . " cs
                  LEFT JOIN incidents i ON cs.incident_id = i.id
                  WHERE cs.id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Update charge sheet
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET incident_id=:incident_id, charge_sheet_number=:charge_sheet_number, 
                    date_issued=:date_issued, issuing_officer=:issuing_officer, 
                    suspect_name=:suspect_name, suspect_address=:suspect_address, 
                    suspect_id_number=:suspect_id_number, suspect_phone=:suspect_phone, 
                    charges=:charges, facts_of_case=:facts_of_case, evidence=:evidence, 
                    witnesses=:witnesses, bail_conditions=:bail_conditions, 
                    court_date=:court_date, court_name=:court_name, status=:status
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->incident_id = htmlspecialchars(strip_tags($this->incident_id));
        $this->charge_sheet_number = htmlspecialchars(strip_tags($this->charge_sheet_number));
        $this->issuing_officer = htmlspecialchars(strip_tags($this->issuing_officer));
        $this->suspect_name = htmlspecialchars(strip_tags($this->suspect_name));

        // Bind parameters
        $stmt->bindParam(":incident_id", $this->incident_id);
        $stmt->bindParam(":charge_sheet_number", $this->charge_sheet_number);
        $stmt->bindParam(":date_issued", $this->date_issued);
        $stmt->bindParam(":issuing_officer", $this->issuing_officer);
        $stmt->bindParam(":suspect_name", $this->suspect_name);
        $stmt->bindParam(":suspect_address", $this->suspect_address);
        $stmt->bindParam(":suspect_id_number", $this->suspect_id_number);
        $stmt->bindParam(":suspect_phone", $this->suspect_phone);
        $stmt->bindParam(":charges", $this->charges);
        $stmt->bindParam(":facts_of_case", $this->facts_of_case);
        $stmt->bindParam(":evidence", $this->evidence);
        $stmt->bindParam(":witnesses", $this->witnesses);
        $stmt->bindParam(":bail_conditions", $this->bail_conditions);
        $stmt->bindParam(":court_date", $this->court_date);
        $stmt->bindParam(":court_name", $this->court_name);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Delete charge sheet
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}

// HTML Interface
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incident & Charge Sheet Management System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f4f4f4; }
        .container { max-width: 1400px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        button:hover { background: #0056b3; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #545b62; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .table th { background: #f8f9fa; }
        .alert { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .tabs { margin-bottom: 20px; }
        .tab { display: inline-block; padding: 10px 20px; background: #e9ecef; cursor: pointer; margin-right: 5px; }
        .tab.active { background: #007bff; color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .card { border: 1px solid #ddd; padding: 20px; margin: 10px 0; border-radius: 5px; background: #f9f9f9; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .detail-view { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .detail-section { margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .detail-section h3 { color: #007bff; margin-bottom: 15px; }
        .detail-row { display: grid; grid-template-columns: 200px 1fr; margin-bottom: 10px; }
        .detail-label { font-weight: bold; color: #555; }
        .detail-value { color: #333; }
        .text-area-value { white-space: pre-wrap; background: #f8f9fa; padding: 10px; border-radius: 4px; border: 1px solid #e9ecef; }
        @media (max-width: 768px) {
            .grid { grid-template-columns: 1fr; }
            .detail-row { grid-template-columns: 1fr; }
        }
        /* Print Styles */
        @media print {
            body * {
                visibility: hidden;
            }
            .print-section, .print-section * {
                visibility: visible;
            }
            .print-section {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                background: white;
            }
            .no-print {
                display: none !important;
            }
            .detail-view {
                box-shadow: none;
                margin: 0;
                padding: 0;
            }
            .detail-section {
                page-break-inside: avoid;
            }
        }

        .print-header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .print-header h1 {
            margin: 0;
            font-size: 24px;
        }

        .print-header h2 {
            margin: 5px 0;
            font-size: 18px;
            color: #666;
        }

        .print-watermark {
            position: absolute;
            opacity: 0.1;
            font-size: 100px;
            transform: rotate(-45deg);
            top: 40%;
            left: 10%;
            z-index: -1;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Incident & Charge Sheet Management System</h1>
        
        <div class="tabs">
            <div class="tab active" onclick="showTab('incidents')">Incidents</div>
            <div class="tab" onclick="showTab('create-incident')">Create Incident</div>
            <div class="tab" onclick="showTab('charge-sheets')">Charge Sheets</div>
            <div class="tab" onclick="showTab('create-charge-sheet')">Create Charge Sheet</div>
        </div>

        <?php
        // Initialize database
        $database = new Database();
        $db = $database->getConnection();
        $incident = new Incident($db);
        $chargeSheet = new ChargeSheet($db);

        $message = "";
        $message_type = "";

        // Handle Incident Creation
        if($_POST && isset($_POST['create_incident'])) {
            $incident->incident_type = $_POST['incident_type'];
            $incident->description = $_POST['description'];
            $incident->location_name = $_POST['location_name'];
            $incident->latitude = $_POST['latitude'];
            $incident->longitude = $_POST['longitude'];
            $incident->incident_date = $_POST['incident_date'];
            $incident->reporter_name = $_POST['reporter_name'];
            $incident->reporter_contact = $_POST['reporter_contact'];
            $incident->user_id = $_POST['user_id'];
            $incident->status = $_POST['status'];
            $incident->image_path = $_POST['image_path'];

            if($incident->create()) {
                $message = "Incident created successfully!";
                $message_type = "success";
            } else {
                $message = "Unable to create incident.";
                $message_type = "error";
            }
        }

        // Handle Charge Sheet Creation
        if($_POST && isset($_POST['create_charge_sheet'])) {
            $chargeSheet->incident_id = $_POST['incident_id'];
            $chargeSheet->charge_sheet_number = $_POST['charge_sheet_number'];
            $chargeSheet->date_issued = $_POST['date_issued'];
            $chargeSheet->issuing_officer = $_POST['issuing_officer'];
            $chargeSheet->suspect_name = $_POST['suspect_name'];
            $chargeSheet->suspect_address = $_POST['suspect_address'];
            $chargeSheet->suspect_id_number = $_POST['suspect_id_number'];
            $chargeSheet->suspect_phone = $_POST['suspect_phone'];
            $chargeSheet->charges = $_POST['charges'];
            $chargeSheet->facts_of_case = $_POST['facts_of_case'];
            $chargeSheet->evidence = $_POST['evidence'];
            $chargeSheet->witnesses = $_POST['witnesses'];
            $chargeSheet->bail_conditions = $_POST['bail_conditions'];
            $chargeSheet->court_date = $_POST['court_date'];
            $chargeSheet->court_name = $_POST['court_name'];
            $chargeSheet->status = $_POST['status'];

            if($chargeSheet->create()) {
                $message = "Charge sheet created successfully!";
                $message_type = "success";
            } else {
                $message = "Unable to create charge sheet.";
                $message_type = "error";
            }
        }

        // Handle deletions
        if(isset($_GET['delete_charge_sheet_id'])) {
            $chargeSheet->id = $_GET['delete_charge_sheet_id'];
            if($chargeSheet->delete()) {
                $message = "Charge sheet deleted successfully!";
                $message_type = "success";
            } else {
                $message = "Unable to delete charge sheet.";
                $message_type = "error";
            }
        }

        // Show message
        if($message) {
            echo "<div class='alert $message_type'>$message</div>";
        }

        // View Single Incident
        if(isset($_GET['view_incident_id'])) {
            $incident->id = $_GET['view_incident_id'];
            if($incident->readOne()) {
                echo "<div class='detail-view'>";
                echo "<h2>Incident Details - ID: {$incident->id}</h2>";
                echo "<div style='margin-top: 20px;'>";
                echo "<button onclick=\"window.open('?print_incident_id={$incident->id}', '_blank')\" style='padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 5px;'>Print Incident Report</button>";
                echo "<button onclick='window.history.back()' style='padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 5px;'>Back to List</button>";
                echo "</div>";
                
                echo "<div class='detail-section'>";
                echo "<h3>Basic Information</h3>";
                echo "<div class='detail-row'><div class='detail-label'>Incident Type:</div><div class='detail-value'>{$incident->incident_type}</div></div>";
                echo "<div class='detail-row'><div class='detail-label'>Incident Date:</div><div class='detail-value'>{$incident->incident_date}</div></div>";
                echo "<div class='detail-row'><div class='detail-label'>Status:</div><div class='detail-value'>{$incident->status}</div></div>";
                echo "</div>";

                echo "<div class='detail-section'>";
                echo "<h3>Location Details</h3>";
                echo "<div class='detail-row'><div class='detail-label'>Location Name:</div><div class='detail-value'>{$incident->location_name}</div></div>";
                if($incident->latitude && $incident->longitude) {
                    echo "<div class='detail-row'><div class='detail-label'>Coordinates:</div><div class='detail-value'>{$incident->latitude}, {$incident->longitude}</div></div>";
                }
                echo "</div>";

                echo "<div class='detail-section'>";
                echo "<h3>Description</h3>";
                echo "<div class='text-area-value'>{$incident->description}</div>";
                echo "</div>";

                echo "<div class='detail-section'>";
                echo "<h3>Reporter Information</h3>";
                echo "<div class='detail-row'><div class='detail-label'>Reporter Name:</div><div class='detail-value'>{$incident->reporter_name}</div></div>";
                echo "<div class='detail-row'><div class='detail-label'>Reporter Contact:</div><div class='detail-value'>{$incident->reporter_contact}</div></div>";
                echo "</div>";

                echo "<div class='detail-section'>";
                echo "<h3>System Information</h3>";
                echo "<div class='detail-row'><div class='detail-label'>User ID:</div><div class='detail-value'>{$incident->user_id}</div></div>";
                if($incident->image_path) {
                    echo "<div class='detail-row'><div class='detail-label'>Image Path:</div><div class='detail-value'>{$incident->image_path}</div></div>";
                }
                echo "<div class='detail-row'><div class='detail-label'>Created At:</div><div class='detail-value'>{$incident->created_at}</div></div>";
                echo "<div class='detail-row'><div class='detail-label'>Updated At:</div><div class='detail-value'>{$incident->updated_at}</div></div>";
                echo "</div>";

                echo "</div>";
                exit();
            }
        }

        // View Single Charge Sheet
        if(isset($_GET['view_charge_sheet_id'])) {
            $chargeSheet->id = $_GET['view_charge_sheet_id'];
            $chargeSheetData = $chargeSheet->readOneWithIncident();
            
            if($chargeSheetData) {
                echo "<div class='detail-view'>";
                echo "<h2>Charge Sheet Details - {$chargeSheetData['charge_sheet_number']}</h2>";
                echo "<div style='margin-top: 20px;'>";
                echo "<button onclick=\"window.open('?print_charge_sheet_id={$chargeSheetData['id']}', '_blank')\" style='padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 5px;'>Print Charge Sheet</button>";
                echo "<button onclick='window.history.back()' style='padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 5px;'>Back to List</button>";
                echo "</div>";
                
                echo "<div class='detail-section'>";
                echo "<h3>Charge Sheet Information</h3>";
                echo "<div class='detail-row'><div class='detail-label'>Charge Sheet Number:</div><div class='detail-value'>{$chargeSheetData['charge_sheet_number']}</div></div>";
                echo "<div class='detail-row'><div class='detail-label'>Date Issued:</div><div class='detail-value'>{$chargeSheetData['date_issued']}</div></div>";
                echo "<div class='detail-row'><div class='detail-label'>Issuing Officer:</div><div class='detail-value'>{$chargeSheetData['issuing_officer']}</div></div>";
                echo "<div class='detail-row'><div class='detail-label'>Status:</div><div class='detail-value'>{$chargeSheetData['status']}</div></div>";
                echo "</div>";

                echo "<div class='detail-section'>";
                echo "<h3>Related Incident</h3>";
                echo "<div class='detail-row'><div class='detail-label'>Incident Type:</div><div class='detail-value'>{$chargeSheetData['incident_type']}</div></div>";
                echo "<div class='detail-row'><div class='detail-label'>Location:</div><div class='detail-value'>{$chargeSheetData['location_name']}</div></div>";
                echo "<div class='detail-row'><div class='detail-label'>Incident Date:</div><div class='detail-value'>{$chargeSheetData['incident_date']}</div></div>";
                echo "<div class='detail-row'><div class='detail-label'>Reporter:</div><div class='detail-value'>{$chargeSheetData['reporter_name']}</div></div>";
                echo "</div>";

                echo "<div class='detail-section'>";
                echo "<h3>Suspect Information</h3>";
                echo "<div class='detail-row'><div class='detail-label'>Suspect Name:</div><div class='detail-value'>{$chargeSheetData['suspect_name']}</div></div>";
                echo "<div class='detail-row'><div class='detail-label'>ID Number:</div><div class='detail-value'>{$chargeSheetData['suspect_id_number']}</div></div>";
                echo "<div class='detail-row'><div class='detail-label'>Phone:</div><div class='detail-value'>{$chargeSheetData['suspect_phone']}</div></div>";
                echo "<div class='detail-row'><div class='detail-label'>Address:</div><div class='detail-value text-area-value'>{$chargeSheetData['suspect_address']}</div></div>";
                echo "</div>";

                echo "<div class='detail-section'>";
                echo "<h3>Charges & Facts</h3>";
                echo "<div class='detail-row'><div class='detail-label'>Charges:</div><div class='detail-value text-area-value'>{$chargeSheetData['charges']}</div></div>";
                echo "<div class='detail-row'><div class='detail-label'>Facts of Case:</div><div class='detail-value text-area-value'>{$chargeSheetData['facts_of_case']}</div></div>";
                echo "</div>";

                echo "<div class='detail-section'>";
                echo "<h3>Evidence & Witnesses</h3>";
                echo "<div class='detail-row'><div class='detail-label'>Evidence:</div><div class='detail-value text-area-value'>{$chargeSheetData['evidence']}</div></div>";
                echo "<div class='detail-row'><div class='detail-label'>Witnesses:</div><div class='detail-value text-area-value'>{$chargeSheetData['witnesses']}</div></div>";
                echo "</div>";

                echo "<div class='detail-section'>";
                echo "<h3>Court & Bail Information</h3>";
                echo "<div class='detail-row'><div class='detail-label'>Bail Conditions:</div><div class='detail-value text-area-value'>{$chargeSheetData['bail_conditions']}</div></div>";
                echo "<div class='detail-row'><div class='detail-label'>Court Name:</div><div class='detail-value'>{$chargeSheetData['court_name']}</div></div>";
                echo "<div class='detail-row'><div class='detail-label'>Court Date:</div><div class='detail-value'>{$chargeSheetData['court_date']}</div></div>";
                echo "</div>";

                echo "<div class='detail-section'>";
                echo "<h3>System Information</h3>";
                echo "<div class='detail-row'><div class='detail-label'>Created At:</div><div class='detail-value'>{$chargeSheetData['created_at']}</div></div>";
                echo "<div class='detail-row'><div class='detail-label'>Updated At:</div><div class='detail-value'>{$chargeSheetData['updated_at']}</div></div>";
                echo "</div>";

                echo "</div>";
                exit();
            }
        }

        // Print Incident Report
        if(isset($_GET['print_incident_id'])) {
            $incident->id = $_GET['print_incident_id'];
            if($incident->readOne()) {
                echo "<!DOCTYPE html>
                <html lang='en'>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <title>Incident Report - {$incident->id}</title>
                    <style>
                        body { 
                            font-family: Arial, sans-serif; 
                            margin: 20px; 
                            background: white;
                            color: black;
                        }
                        .print-section { 
                            max-width: 800px; 
                            margin: 0 auto; 
                        }
                        .print-header { 
                            text-align: center; 
                            border-bottom: 3px double #000; 
                            padding-bottom: 20px; 
                            margin-bottom: 30px; 
                        }
                        .print-header h1 { 
                            margin: 0; 
                            font-size: 28px; 
                            color: #000;
                        }
                        .print-header h2 { 
                            margin: 5px 0; 
                            font-size: 20px; 
                            color: #333; 
                        }
                        .print-header h3 { 
                            margin: 10px 0; 
                            font-size: 16px; 
                            color: #666; 
                        }
                        .detail-section { 
                            margin-bottom: 25px; 
                            padding-bottom: 15px; 
                            border-bottom: 1px solid #ccc; 
                            page-break-inside: avoid;
                        }
                        .detail-section h3 { 
                            color: #000; 
                            margin-bottom: 15px; 
                            font-size: 18px;
                            border-bottom: 1px solid #000;
                            padding-bottom: 5px;
                        }
                        .detail-row { 
                            display: grid; 
                            grid-template-columns: 200px 1fr; 
                            margin-bottom: 8px; 
                        }
                        .detail-label { 
                            font-weight: bold; 
                            color: #000; 
                        }
                        .detail-value { 
                            color: #000; 
                        }
                        .text-area-value { 
                            white-space: pre-wrap; 
                            background: #f9f9f9; 
                            padding: 10px; 
                            border-radius: 4px; 
                            border: 1px solid #ddd; 
                            margin-top: 5px;
                        }
                        .footer {
                            margin-top: 50px;
                            text-align: center;
                            font-size: 12px;
                            color: #666;
                        }
                        @media print {
                            body { margin: 0; }
                            .print-section { max-width: 100%; }
                            .no-print { display: none; }
                            .detail-section { page-break-inside: avoid; }
                        }
                        .watermark {
                            position: fixed;
                            top: 50%;
                            left: 50%;
                            transform: translate(-50%, -50%) rotate(-45deg);
                            font-size: 80px;
                            color: rgba(0,0,0,0.1);
                            z-index: -1;
                            pointer-events: none;
                        }
                        .status-badge {
                            display: inline-block;
                            padding: 4px 12px;
                            border-radius: 20px;
                            font-size: 12px;
                            font-weight: bold;
                            text-transform: uppercase;
                        }
                        .status-reported { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
                        .status-under_investigation { background: #cce7ff; color: #004085; border: 1px solid #b3d7ff; }
                        .status-resolved { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
                        .status-closed { background: #e2e3e5; color: #383d41; border: 1px solid #d6d8db; }
                    </style>
                </head>
                <body>
                    <div class='watermark'>INCIDENT REPORT</div>
                    <div class='print-section'>
                        <div class='print-header'>
                            <h1>INCIDENT REPORT</h1>
                            <h2>Incident ID: {$incident->id}</h2>
                            <h3>Report Generated: " . date('Y-m-d H:i:s') . "</h3>
                        </div>";

                // Basic Information
                echo "<div class='detail-section'>";
                echo "<h3>INCIDENT BASIC INFORMATION</h3>";
                echo "<div class='detail-row'><div class='detail-label'>Incident ID:</div><div class='detail-value'>{$incident->id}</div></div>";
                echo "<div class='detail-row'><div class='detail-label'>Incident Type:</div><div class='detail-value'>{$incident->incident_type}</div></div>";
                echo "<div class='detail-row'><div class='detail-label'>Incident Date & Time:</div><div class='detail-value'>{$incident->incident_date}</div></div>";
                
                $status_class = 'status-' . $incident->status;
                $status_display = ucwords(str_replace('_', ' ', $incident->status));
                echo "<div class='detail-row'><div class='detail-label'>Status:</div><div class='detail-value'><span class='status-badge {$status_class}'>{$status_display}</span></div></div>";
                echo "</div>";

                // Location Details
                echo "<div class='detail-section'>";
                echo "<h3>LOCATION DETAILS</h3>";
                echo "<div class='detail-row'><div class='detail-label'>Location Name:</div><div class='detail-value'>{$incident->location_name}</div></div>";
                if($incident->latitude && $incident->longitude) {
                    echo "<div class='detail-row'><div class='detail-label'>Coordinates:</div><div class='detail-value'>{$incident->latitude}, {$incident->longitude}</div></div>";
                    echo "<div class='detail-row'><div class='detail-label'>Google Maps:</div><div class='detail-value'><a href='https://maps.google.com/?q={$incident->latitude},{$incident->longitude}' target='_blank'>View on Google Maps</a></div></div>";
                }
                echo "</div>";

                // Incident Description
                echo "<div class='detail-section'>";
                echo "<h3>INCIDENT DESCRIPTION</h3>";
                echo "<div class='text-area-value'>{$incident->description}</div>";
                echo "</div>";

                // Reporter Information
                echo "<div class='detail-section'>";
                echo "<h3>REPORTER INFORMATION</h3>";
                echo "<div class='detail-row'><div class='detail-label'>Reporter Name:</div><div class='detail-value'>{$incident->reporter_name}</div></div>";
                echo "<div class='detail-row'><div class='detail-label'>Contact Information:</div><div class='detail-value'>{$incident->reporter_contact}</div></div>";
                echo "</div>";

                // Additional Information
                echo "<div class='detail-section'>";
                echo "<h3>ADDITIONAL INFORMATION</h3>";
                echo "<div class='detail-row'><div class='detail-label'>Assigned User ID:</div><div class='detail-value'>{$incident->user_id}</div></div>";
                if($incident->image_path) {
                    echo "<div class='detail-row'><div class='detail-label'>Image Evidence:</div><div class='detail-value'>{$incident->image_path}</div></div>";
                }
                echo "</div>";

                // System Information
                echo "<div class='detail-section'>";
                echo "<h3>SYSTEM INFORMATION</h3>";
                echo "<div class='detail-row'><div class='detail-label'>Report Created:</div><div class='detail-value'>{$incident->created_at}</div></div>";
                echo "<div class='detail-row'><div class='detail-label'>Last Updated:</div><div class='detail-value'>{$incident->updated_at}</div></div>";
                echo "</div>";

                // Notes Section
                echo "<div class='detail-section'>";
                echo "<h3>OFFICIAL NOTES</h3>";
                echo "<div style='min-height: 100px; border: 1px dashed #ccc; padding: 10px; margin-top: 10px;'>";
                echo "<p><em>Official notes and comments area:</em></p>";
                echo "</div>";
                echo "</div>";

                // Footer
                echo "<div class='footer'>";
                echo "<p>This document was generated automatically from the Incident Management System</p>";
                echo "<p>Confidential - For official use only</p>";
                echo "</div>";

                // Print controls
                echo "<div class='no-print' style='text-align: center; margin-top: 20px;'>";
                echo "<button onclick='window.print()' style='padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 5px;'>Print Report</button>";
                echo "<button onclick='window.close()' style='padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 5px;'>Close Window</button>";
                echo "</div>";

                echo "</div>
                    <script>
                        // Auto-print when page loads
                        window.onload = function() {
                            window.print();
                        };
                    </script>
                </body>
                </html>";
                exit();
            }
        }

        // Print Charge Sheet
        if(isset($_GET['print_charge_sheet_id'])) {
            $chargeSheet->id = $_GET['print_charge_sheet_id'];
            $chargeSheetData = $chargeSheet->readOneWithIncident();
            
            if($chargeSheetData) {
                echo "<!DOCTYPE html>
                <html lang='en'>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <title>Charge Sheet - {$chargeSheetData['charge_sheet_number']}</title>
                    <style>
                        body { 
                            font-family: Arial, sans-serif; 
                            margin: 20px; 
                            background: white;
                            color: black;
                        }
                        .print-section { 
                            max-width: 800px; 
                            margin: 0 auto; 
                        }
                        .print-header { 
                            text-align: center; 
                            border-bottom: 3px double #000; 
                            padding-bottom: 20px; 
                            margin-bottom: 30px; 
                        }
                        .print-header h1 { 
                            margin: 0; 
                            font-size: 28px; 
                            color: #000;
                        }
                        .print-header h2 { 
                            margin: 5px 0; 
                            font-size: 20px; 
                            color: #333; 
                        }
                        .print-header h3 { 
                            margin: 10px 0; 
                            font-size: 16px; 
                            color: #666; 
                        }
                        .detail-section { 
                            margin-bottom: 25px; 
                            padding-bottom: 15px; 
                            border-bottom: 1px solid #ccc; 
                            page-break-inside: avoid;
                        }
                        .detail-section h3 { 
                            color: #000; 
                            margin-bottom: 15px; 
                            font-size: 18px;
                            border-bottom: 1px solid #000;
                            padding-bottom: 5px;
                        }
                        .detail-row { 
                            display: grid; 
                            grid-template-columns: 200px 1fr; 
                            margin-bottom: 8px; 
                        }
                        .detail-label { 
                            font-weight: bold; 
                            color: #000; 
                        }
                        .detail-value { 
                            color: #000; 
                        }
                        .text-area-value { 
                            white-space: pre-wrap; 
                            background: #f9f9f9; 
                            padding: 10px; 
                            border-radius: 4px; 
                            border: 1px solid #ddd; 
                            margin-top: 5px;
                        }
                        .signature-section {
                            margin-top: 50px;
                            page-break-inside: avoid;
                        }
                        .signature-line {
                            border-top: 1px solid #000;
                            width: 300px;
                            margin-top: 40px;
                        }
                        .footer {
                            margin-top: 50px;
                            text-align: center;
                            font-size: 12px;
                            color: #666;
                        }
                        @media print {
                            body { margin: 0; }
                            .print-section { max-width: 100%; }
                            .no-print { display: none; }
                            .detail-section { page-break-inside: avoid; }
                        }
                        .watermark {
                            position: fixed;
                            top: 50%;
                            left: 50%;
                            transform: translate(-50%, -50%) rotate(-45deg);
                            font-size: 80px;
                            color: rgba(0,0,0,0.1);
                            z-index: -1;
                            pointer-events: none;
                        }
                    </style>
                </head>
                <body>
                    <div class='watermark'>CHARGE SHEET</div>
                    <div class='print-section'>
                        <div class='print-header'>
                            <h1>CHARGE SHEET</h1>
                            <h2>Charge Sheet Number: {$chargeSheetData['charge_sheet_number']}</h2>
                            <h3>Issued Date: {$chargeSheetData['date_issued']}</h3>
                        </div>";

                // Charge Sheet Information
                echo "<div class='detail-section'>";
                echo "<h3>CHARGE SHEET DETAILS</h3>";
                echo "<div class='detail-row'><div class='detail-label'>Charge Sheet Number:</div><div class='detail-value'>{$chargeSheetData['charge_sheet_number']}</div></div>";
                echo "<div class='detail-row'><div class='detail-label'>Date Issued:</div><div class='detail-value'>{$chargeSheetData['date_issued']}</div></div>";
                echo "<div class='detail-row'><div class='detail-label'>Issuing Officer:</div><div class='detail-value'>{$chargeSheetData['issuing_officer']}</div></div>";
                echo "<div class='detail-row'><div class='detail-label'>Status:</div><div class='detail-value'>{$chargeSheetData['status']}</div></div>";
                echo "</div>";

                // Related Incident
                echo "<div class='detail-section'>";
                echo "<h3>RELATED INCIDENT</h3>";
                echo "<div class='detail-row'><div class='detail-label'>Incident Type:</div><div class='detail-value'>{$chargeSheetData['incident_type']}</div></div>";
                echo "<div class='detail-row'><div class='detail-label'>Location:</div><div class='detail-value'>{$chargeSheetData['location_name']}</div></div>";
                echo "<div class='detail-row'><div class='detail-label'>Incident Date:</div><div class='detail-value'>{$chargeSheetData['incident_date']}</div></div>";
                echo "</div>";

                // Suspect Information
                echo "<div class='detail-section'>";
                echo "<h3>SUSPECT INFORMATION</h3>";
                echo "<div class='detail-row'><div class='detail-label'>Full Name:</div><div class='detail-value'>{$chargeSheetData['suspect_name']}</div></div>";
                echo "<div class='detail-row'><div class='detail-label'>ID Number:</div><div class='detail-value'>{$chargeSheetData['suspect_id_number']}</div></div>";
                echo "<div class='detail-row'><div class='detail-label'>Phone Number:</div><div class='detail-value'>{$chargeSheetData['suspect_phone']}</div></div>";
                echo "<div class='detail-row'><div class='detail-label'>Address:</div><div class='detail-value'>{$chargeSheetData['suspect_address']}</div></div>";
                echo "</div>";

                // Charges & Facts
                echo "<div class='detail-section'>";
                echo "<h3>CHARGES</h3>";
                echo "<div class='text-area-value'><strong>{$chargeSheetData['charges']}</strong></div>";
                echo "</div>";

                echo "<div class='detail-section'>";
                echo "<h3>FACTS OF THE CASE</h3>";
                echo "<div class='text-area-value'>{$chargeSheetData['facts_of_case']}</div>";
                echo "</div>";

                // Evidence & Witnesses
                if($chargeSheetData['evidence']) {
                    echo "<div class='detail-section'>";
                    echo "<h3>EVIDENCE</h3>";
                    echo "<div class='text-area-value'>{$chargeSheetData['evidence']}</div>";
                    echo "</div>";
                }

                if($chargeSheetData['witnesses']) {
                    echo "<div class='detail-section'>";
                    echo "<h3>WITNESSES</h3>";
                    echo "<div class='text-area-value'>{$chargeSheetData['witnesses']}</div>";
                    echo "</div>";
                }

                // Court & Bail Information
                if($chargeSheetData['bail_conditions']) {
                    echo "<div class='detail-section'>";
                    echo "<h3>BAIL CONDITIONS</h3>";
                    echo "<div class='text-area-value'>{$chargeSheetData['bail_conditions']}</div>";
                    echo "</div>";
                }

                if($chargeSheetData['court_name'] || $chargeSheetData['court_date']) {
                    echo "<div class='detail-section'>";
                    echo "<h3>COURT INFORMATION</h3>";
                    if($chargeSheetData['court_name']) {
                        echo "<div class='detail-row'><div class='detail-label'>Court Name:</div><div class='detail-value'>{$chargeSheetData['court_name']}</div></div>";
                    }
                    if($chargeSheetData['court_date']) {
                        echo "<div class='detail-row'><div class='detail-label'>Court Date:</div><div class='detail-value'>{$chargeSheetData['court_date']}</div></div>";
                    }
                    echo "</div>";
                }

                // Signatures
                echo "<div class='signature-section'>";
                echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 50px;'>";
                echo "<div>";
                echo "<div class='signature-line'></div>";
                echo "<div style='margin-top: 5px;'>Issuing Officer Signature</div>";
                echo "<div style='margin-top: 20px;'><strong>{$chargeSheetData['issuing_officer']}</strong></div>";
                echo "</div>";
                echo "<div>";
                echo "<div class='signature-line'></div>";
                echo "<div style='margin-top: 5px;'>Suspect Signature</div>";
                echo "<div style='margin-top: 20px;'><strong>{$chargeSheetData['suspect_name']}</strong></div>";
                echo "</div>";
                echo "</div>";
                echo "</div>";

                // Footer
                echo "<div class='footer'>";
                echo "<p>Generated on: " . date('Y-m-d H:i:s') . "</p>";
                echo "<p>This is an official charge sheet document</p>";
                echo "</div>";

                // Print button for the print view
                echo "<div class='no-print' style='text-align: center; margin-top: 20px;'>";
                echo "<button onclick='window.print()' style='padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;'>Print Document</button>";
                echo " &nbsp; ";
                echo "<button onclick='window.close()' style='padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;'>Close Window</button>";
                echo "</div>";

                echo "</div>
                    <script>
                        // Auto-print when page loads
                        window.onload = function() {
                            window.print();
                        };
                    </script>
                </body>
                </html>";
                exit();
            }
        }
        ?>

        <!-- Incidents List -->
        <div id="incidents" class="tab-content active">
            <h2>All Incidents</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Location</th>
                        <th>Date</th>
                        <th>Reporter</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $incident->read();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        extract($row);
                        echo "<tr>";
                        echo "<td>{$id}</td>";
                        echo "<td>{$incident_type}</td>";
                        echo "<td>" . substr($description, 0, 50) . "...</td>";
                        echo "<td>{$location_name}</td>";
                        echo "<td>{$incident_date}</td>";
                        echo "<td>{$reporter_name}</td>";
                        echo "<td>{$status}</td>";
                        echo "<td>";
                        echo "<a href='?view_incident_id={$id}'>View Complete</a> | ";
                        echo "<a href='?print_incident_id={$id}' target='_blank'>Print</a> | ";
                        echo "<a href='?create_charge_for={$id}'>Create Charge Sheet</a>";
                        echo "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Create Incident Form -->
        <div id="create-incident" class="tab-content">
            <h2>Create New Incident</h2>
            <form method="POST" action="">
                <input type="hidden" name="create_incident" value="1">
                
                <div class="grid">
                    <div class="form-group">
                        <label>Incident Type:</label>
                        <select name="incident_type" required>
                            <option value="">Select Type</option>
                            <option value="Theft">Theft</option>
                            <option value="Assault">Assault</option>
                            <option value="Burglary">Burglary</option>
                            <option value="Drug Offense">Drug Offense</option>
                            <option value="Traffic Violation">Traffic Violation</option>
                            <option value="Vandalism">Vandalism</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Incident Date:</label>
                        <input type="datetime-local" name="incident_date" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Description:</label>
                    <textarea name="description" rows="4" required placeholder="Detailed description of the incident..."></textarea>
                </div>

                <div class="grid">
                    <div class="form-group">
                        <label>Location Name:</label>
                        <input type="text" name="location_name" required placeholder="e.g., Central Mall, Main Street">
                    </div>

                    <div class="form-group">
                        <label>Coordinates (Latitude, Longitude):</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <input type="text" name="latitude" placeholder="Latitude">
                            <input type="text" name="longitude" placeholder="Longitude">
                        </div>
                    </div>
                </div>

                <div class="grid">
                    <div class="form-group">
                        <label>Reporter Name:</label>
                        <input type="text" name="reporter_name" required>
                    </div>

                    <div class="form-group">
                        <label>Reporter Contact:</label>
                        <input type="text" name="reporter_contact" required>
                    </div>
                </div>

                <div class="grid">
                    <div class="form-group">
                        <label>User ID:</label>
                        <input type="text" name="user_id" value="1">
                    </div>

                    <div class="form-group">
                        <label>Status:</label>
                        <select name="status" required>
                            <option value="reported">Reported</option>
                            <option value="under_investigation">Under Investigation</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Image Path (optional):</label>
                    <input type="text" name="image_path" placeholder="Path to incident photos">
                </div>

                <button type="submit">Create Incident</button>
            </form>
        </div>

        <!-- Charge Sheets List -->
        <div id="charge-sheets" class="tab-content">
            <h2>All Charge Sheets</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Charge Sheet No.</th>
                        <th>Incident</th>
                        <th>Suspect Name</th>
                        <th>Date Issued</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $chargeSheet->readWithIncidents();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        extract($row);
                        echo "<tr>";
                        echo "<td>{$id}</td>";
                        echo "<td>{$charge_sheet_number}</td>";
                        echo "<td>{$incident_type} - {$location_name}</td>";
                        echo "<td>{$suspect_name}</td>";
                        echo "<td>{$date_issued}</td>";
                        echo "<td>{$status}</td>";
                        echo "<td>";
                        echo "<a href='?view_charge_sheet_id={$id}'>View Complete</a> | ";
                        echo "<a href='?print_charge_sheet_id={$id}' target='_blank'>Print</a> | ";
                        echo "<a href='?edit_charge_sheet_id={$id}'>Edit</a> | ";
                        echo "<a href='?delete_charge_sheet_id={$id}' onclick='return confirm(\"Are you sure?\")'>Delete</a>";
                        echo "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Create Charge Sheet Form -->
        <div id="create-charge-sheet" class="tab-content">
            <h2>Create New Charge Sheet</h2>
            <form method="POST" action="">
                <input type="hidden" name="create_charge_sheet" value="1">
                
                <div class="form-group">
                    <label>Select Incident:</label>
                    <select name="incident_id" required>
                        <option value="">Select Incident</option>
                        <?php
                        $incidents = $incident->getIncidentsForDropdown();
                        while ($row = $incidents->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value='{$row['id']}'>{$row['id']} - {$row['incident_type']} - {$row['location_name']} ({$row['incident_date']})</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="grid">
                    <div class="form-group">
                        <label>Charge Sheet Number:</label>
                        <input type="text" name="charge_sheet_number" required placeholder="e.g., CS20240001">
                    </div>

                    <div class="form-group">
                        <label>Date Issued:</label>
                        <input type="date" name="date_issued" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Issuing Officer:</label>
                    <input type="text" name="issuing_officer" required>
                </div>

                <div class="grid">
                    <div class="form-group">
                        <label>Suspect Name:</label>
                        <input type="text" name="suspect_name" required>
                    </div>

                    <div class="form-group">
                        <label>Suspect ID Number:</label>
                        <input type="text" name="suspect_id_number" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Suspect Address:</label>
                    <textarea name="suspect_address" required></textarea>
                </div>

                <div class="grid">
                    <div class="form-group">
                        <label>Suspect Phone:</label>
                        <input type="text" name="suspect_phone">
                    </div>

                    <div class="form-group">
                        <label>Court Date:</label>
                        <input type="date" name="court_date">
                    </div>
                </div>

                <div class="form-group">
                    <label>Charges:</label>
                    <textarea name="charges" required placeholder="List all charges separated by commas"></textarea>
                </div>

                <div class="form-group">
                    <label>Facts of Case:</label>
                    <textarea name="facts_of_case" required rows="4"></textarea>
                </div>

                <div class="form-group">
                    <label>Evidence:</label>
                    <textarea name="evidence" rows="3" placeholder="List all evidence"></textarea>
                </div>

                <div class="form-group">
                    <label>Witnesses:</label>
                    <textarea name="witnesses" rows="3" placeholder="List witnesses with contact information"></textarea>
                </div>

                <div class="form-group">
                    <label>Bail Conditions:</label>
                    <textarea name="bail_conditions" rows="3" placeholder="Bail conditions if applicable"></textarea>
                </div>

                <div class="grid">
                    <div class="form-group">
                        <label>Court Name:</label>
                        <input type="text" name="court_name" placeholder="e.g., District Court">
                    </div>

                    <div class="form-group">
                        <label>Status:</label>
                        <select name="status" required>
                            <option value="pending">Pending</option>
                            <option value="filed">Filed</option>
                            <option value="hearing">Hearing</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                </div>

                <button type="submit">Create Charge Sheet</button>
            </form>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });

            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }

        // Auto-fill date and time for incident
        document.addEventListener('DOMContentLoaded', function() {
            const now = new Date();
            const localDateTime = now.toISOString().slice(0, 16);
            document.querySelector('input[name="incident_date"]').value = localDateTime;
            
            // Auto-fill today's date for charge sheet
            const today = new Date().toISOString().split('T')[0];
            document.querySelector('input[name="date_issued"]').value = today;
        });
    </script>
</body>
</html>