<?php
// Start connection
$conn = new mysqli("localhost", "root", "", "my", 3307);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the necessary GET parameters are set
if (isset($_GET['appointment_id']) && isset($_GET['update_status'])) {
    $appointment_id = $_GET['appointment_id'];
    $update_status = $_GET['update_status'];

    // Validate status to make sure it is either 'Undone' or 'Done'
    if ($update_status !== 'Undone' && $update_status !== 'Done') {
        die("Invalid status value.");
    }

    // Prepare the SQL query to update the status of the appointment
    $stmt = $conn->prepare("UPDATE appointment SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $update_status, $appointment_id); // 's' for string, 'i' for integer

    // Execute the statement and check for success
    if ($stmt->execute()) {
        echo "Status updated successfully!";
    } else {
        echo "Error updating status: " . $stmt->error;
    }

    // Close the statement
    $stmt->close();
} else {
    echo "Appointment ID or status not provided.";
}

// Close the connection
$conn->close();
?>

