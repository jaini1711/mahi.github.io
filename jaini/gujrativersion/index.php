<?php
session_start();

// MySQL Database connection
$conn = new mysqli("localhost", "root", "", "my", 3307);
if ($conn->connect_error) {
    die("કનેક્શન નિષ્ફળ: " . $conn->connect_error);
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit_appointment"])) {
    $name = trim($_POST["name"]);
    $gender = $_POST["gender"];
    $age = intval($_POST["age"]);
    $contact = trim($_POST["contact"]);
    $appointment_date = $_POST["appointment_date"];
    $appointment_type = $_POST["appointment_type"];

    // Validate inputs
    if (
        empty($name) || 
        !in_array($gender, ['Male', 'Female']) || 
        $age <= 0 || 
        !preg_match('/^\d{10}$/', $contact) || 
        empty($appointment_date) || 
        !in_array($appointment_type, ['Online', 'Offline'])
    ) {
        $error = "બધા ફીલ્ડસ યોગ્ય રીતે ભરો.";
    } else {
        $stmt = $conn->prepare("INSERT INTO appointment (name, gender, age, contact_no, appointment_date, appointment_type, status) VALUES (?, ?, ?, ?, ?, ?, 'Undone')");
        $stmt->bind_param("ssisss", $name, $gender, $age, $contact, $appointment_date, $appointment_type);

        if ($stmt->execute()) {
            $_SESSION = [
                'name' => $name,
                'contact' => $contact,
                'age' => $age,
                'gender' => $gender,
                'appointment_date' => $appointment_date,
                'appointment_type' => $appointment_type
            ];
            header("Location:succes.php");
            exit;
        } else {
            $error = "ડેટાબેઝ ભૂલ: " . $stmt->error;
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="gu">
<head>
    <meta charset="UTF-8">
    <title>મુલાકાત બુક કરો</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: Arial;
            background: linear-gradient(to right, #c9d6ff, #e2e2e2);
            padding: 0; margin: 0;
        }
        .container {
            background: white;
            max-width: 700px;
            margin: 50px auto;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            font-size: 26px;
            margin-bottom: 25px;
        }
        label {
            font-weight: bold;
            margin-top: 10px;
            display: block;
        }
        input[type="text"],
        input[type="number"],
        input[type="date"],
        select {
            width: 100%;
            padding: 10px;
            margin-bottom: 12px;
            font-size: 15px;
        }
        .row {
            display: flex;
            gap: 15px;
        }
        .row > div {
            flex: 1;
        }
        .radio-group {
            margin-top: 5px;
        }
        .radio-group label {
            margin-right: 20px;
        }
        .submit-btn {
            width: 100%;
            padding: 12px;
            background: #007BFF;
            color: white;
            border: none;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
            margin-top: 10px;
        }
        .submit-btn:hover {
            background: #0056b3;
        }
        .error {
            color: red;
            text-align: center;
            margin-bottom: 15px;
        }
    </style>
    <script>
        function validateForm() {
            let name = document.forms["appointForm"]["name"].value.trim();
            let contact = document.forms["appointForm"]["contact"].value.trim();
            let age = document.forms["appointForm"]["age"].value;
            let valid = true;

            document.getElementById("name_error").innerText = "";
            document.getElementById("contact_error").innerText = "";
            document.getElementById("age_error").innerText = "";

            if (name === "") {
                document.getElementById("name_error").innerText = "નામ ફરજિયાત છે.";
                valid = false;
            }
            if (!/^\d{10}$/.test(contact)) {
                document.getElementById("contact_error").innerText = "માન્ય મોબાઇલ નંબર નાખો.";
                valid = false;
            }
            if (!age || age <= 0) {
                document.getElementById("age_error").innerText = "ઉંમર યોગ્ય નાખો.";
                valid = false;
            }
            return valid;
        }
    </script>
</head>
<body>
    <div class="container">
        <h2>સ્માર્ટ મુલાકાત બુકિંગ</h2>

        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form name="appointForm" method="POST" onsubmit="return validateForm();">
            <label>નામ:</label>
            <input type="text" name="name" value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
            <div id="name_error" class="error"></div>

            <div class="row">
                <div>
                    <label>લિંગ:</label>
                    <select name="gender" required>
                        <option value="">પસંદ કરો</option>
                        <option value="Male" <?= (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'selected' : '' ?>>પુરુષ</option>
                        <option value="Female" <?= (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'selected' : '' ?>>સ્ત્રી</option>
                    </select>
                </div>
                <div>
                    <label>ઉંમર:</label>
                    <input type="number" name="age" value="<?= isset($_POST['age']) ? htmlspecialchars($_POST['age']) : '' ?>">
                    <div id="age_error" class="error"></div>
                </div>
            </div>

            <label>મોબાઇલ નંબર:</label>
            <input type="text" name="contact" value="<?= isset($_POST['contact']) ? htmlspecialchars($_POST['contact']) : '' ?>">
            <div id="contact_error" class="error"></div>

            <label>મુલાકાત તારીખ:</label>
            <input type="date" name="appointment_date" required value="<?= isset($_POST['appointment_date']) ? htmlspecialchars($_POST['appointment_date']) : '' ?>">

            <label>મુલાકાત પ્રકાર:</label>
            <div class="radio-group">
                <label><input type="radio" name="appointment_type" value="Online" required <?= (isset($_POST['appointment_type']) && $_POST['appointment_type'] == 'Online') ? 'checked' : '' ?>> ઑનલાઇન</label>
                <label><input type="radio" name="appointment_type" value="Offline" required <?= (isset($_POST['appointment_type']) && $_POST['appointment_type'] == 'Offline') ? 'checked' : '' ?>> ઑફલાઇન</label>
            </div>

            <input type="submit" name="submit_appointment" value="મુલાકાત બુક કરો" class="submit-btn">
        </form>
    </div>
</body>
</html>
