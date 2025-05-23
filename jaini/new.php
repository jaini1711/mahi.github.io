<?php
session_start(); // Start the session

$conn = new mysqli("localhost", "root", "", "my", 3307);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit_appointment"])) {
    $name = $_POST["name"];
    $gender = $_POST["gender"];
    $age = $_POST["age"];
    $contact = $_POST["contact"];
    $appointment_date = $_POST["appointment_date"];
    $appointment_type = $_POST["appointment_type"];

    if (
        !empty($name) && !empty($gender) && is_numeric($age) &&
        preg_match('/^[0-9]{10}$/', $contact) && !empty($appointment_date) && !empty($appointment_type)
    ) {
        $sql = "INSERT INTO appointment (name, gender, age, contact_no, appointment_date, appointment_type, status)
                VALUES ('$name', '$gender', $age, '$contact', '$appointment_date', '$appointment_type', 'Undone')";
        
        if ($conn->query($sql) === TRUE) {
            // Store user details in session for success page
            $_SESSION['name'] = $name;
            $_SESSION['contact'] = $contact;
            $_SESSION['age'] = $age;
            $_SESSION['gender'] = $gender;
            $_SESSION['appointment_date'] = $appointment_date;
            $_SESSION['appointment_type'] = $appointment_type;

            // Redirect to the success page
            header("Location: success.php");
            exit;
        } else {
            echo "Error: " . $conn->error;
        }
    }
}
?>