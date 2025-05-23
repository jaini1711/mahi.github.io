<?php
session_start();
$conn = new mysqli("localhost", "root", "", "my", 3307);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Hospital ID get karte hain URL parameter se (e.g. book_appointment.php?hospital_id=1)
$hospital_id = isset($_GET['hospital_id']) ? intval($_GET['hospital_id']) : 0;

$hospital_name = "";
if ($hospital_id) {
    $stmt_hosp = $conn->prepare("SELECT name FROM hospitals WHERE id = ?");
    $stmt_hosp->bind_param("i", $hospital_id);
    $stmt_hosp->execute();
    $stmt_hosp->bind_result($hospital_name);
    $stmt_hosp->fetch();
    $stmt_hosp->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit_appointment"])) {
    $name = trim($_POST["name"]);
    $gender = $_POST["gender"];
    $age = $_POST["age"];
    $contact = trim($_POST["contact"]);
    $appointment_date = $_POST["appointment_date"];
    $appointment_type = $_POST["appointment_type"];

    // Simple validation
    if (!empty($name) && !empty($gender) && is_numeric($age) && preg_match('/^[0-9]{10}$/', $contact) && !empty($appointment_date) && !empty($appointment_type) && $hospital_id) {
        $stmt = $conn->prepare("INSERT INTO appointment (hospital_id, name, gender, age, contact_no, appointment_date, appointment_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Undone')");
        $stmt->bind_param("ississs", $hospital_id, $name, $gender, $age, $contact, $appointment_date, $appointment_type);
        if ($stmt->execute()) {
            $_SESSION['name'] = $name;
            $_SESSION['contact'] = $contact;
            $_SESSION['age'] = $age;
            $_SESSION['gender'] = $gender;
            $_SESSION['appointment_date'] = $appointment_date;
            $_SESSION['appointment_type'] = $appointment_type;
            $_SESSION['hospital_name'] = $hospital_name;
            header("Location: success.php");
            exit;
        } else {
            $error_msg = "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_msg = "Please fill all fields correctly and ensure a hospital is selected.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Book Appointment - <?php echo htmlspecialchars($hospital_name ?: 'Hospital'); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(to right, #c9d6ff, #e2e2e2);
            margin: 0; padding: 0;
        }
        .container {
            background-color: #fff;
            width: 90%;
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .hospital-info {
            background: #f0f4ff;
            border-left: 5px solid #007BFF;
            padding: 10px 15px;
            margin-bottom: 25px;
            font-size: 18px;
            font-weight: 600;
            text-align: center;
            color: #004085;
            border-radius: 4px;
        }
        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        input[type=text], input[type=number], input[type=date], select {
            width: 100%;
            padding: 10px;
            font-size: 15px;
            margin-bottom: 15px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        .row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
        }
        .row > div {
            flex: 1 1 48%;
        }
        .radio-group {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        .radio-group label {
            font-weight: normal;
            font-size: 15px;
        }
        .submit-btn {
            width: 100%;
            padding: 14px;
            font-size: 16px;
            background-color: #007BFF;
            border: none;
            color: white;
            border-radius: 6px;
            cursor: pointer;
        }
        .submit-btn:hover {
            background-color: #0056b3;
        }
        .error {
            color: red;
            font-size: 14px;
            margin-bottom: 15px;
            text-align: center;
        }
        @media (max-width: 600px) {
            .row > div {
                flex: 1 1 100%;
            }
        }
    </style>
    <script>
        function validateForm() {
            let valid = true;
            document.getElementById("name_error").innerText = "";
            document.getElementById("contact_error").innerText = "";
            document.getElementById("age_error").innerText = "";

            let name = document.forms["appointForm"]["name"].value.trim();
            let contact = document.forms["appointForm"]["contact"].value.trim();
            let age = document.forms["appointForm"]["age"].value;

            if (name === "") {
                document.getElementById("name_error").innerText = "Name is required.";
                valid = false;
            }
            if (!/^\d{10}$/.test(contact)) {
                document.getElementById("contact_error").innerText = "Enter valid 10-digit contact number.";
                valid = false;
            }
            if (!age || age <= 0) {
                document.getElementById("age_error").innerText = "Enter valid age.";
                valid = false;
            }

            return valid;
        }
    </script>
</head>
<body>
    <div class="container">
        <h2>Book Appointment</h2>

        <?php if ($hospital_id && $hospital_name): ?>
            <div class="hospital-info">
                You have selected Hospital: <strong><?php echo htmlspecialchars($hospital_name); ?></strong>
            </div>
        <?php elseif ($hospital_id): ?>
            <div class="hospital-info" style="color:red;">
                Hospital not found for ID: <?php echo htmlspecialchars($hospital_id); ?>
            </div>
        <?php else: ?>
            <div class="hospital-info" style="color:red;">
                No hospital selected. Please select a hospital first.
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="error"><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <form name="appointForm" method="POST" onsubmit="return validateForm();">
            <label for="name">Name:</label>
            <input type="text" name="name" required>
            <div id="name_error" class="error"></div>

            <div class="row">
                <div>
                    <label for="gender">Gender:</label>
                    <select name="gender" required>
                        <option value="">-- Select Gender --</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div>
                    <label for="age">Age:</label>
                    <input type="number" name="age" min="1" required>
                    <div id="age_error" class="error"></div>
                </div>
            </div>

            <label for="contact">Contact No.:</label>
            <input type="text" name="contact" maxlength="10" required>
            <div id="contact_error" class="error"></div>

            <label for="appointment_date">Appointment Date:</label>
            <input type="date" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>">

            <label>Appointment Type:</label>
            <div class="radio-group">
                <label><input type="radio" name="appointment_type" value="Online" required> Online</label>
                <label><input type="radio" name="appointment_type" value="Offline" required> Offline</label>
            </div>

            <input type="submit" name="submit_appointment" value="Book Appointment" class="submit-btn">
        </form>
    </div>
</body>
</html>

