<?php
$conn = new mysqli("localhost", "root", "", "my", 3307);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if 'appointment_id' and 'update_status' are set in the URL
if (isset($_GET['appointment_id']) && isset($_GET['update_status'])) {
    $appointment_id = intval($_GET['appointment_id']); // Sanitize to ensure it's an integer
    $update_status = $_GET['update_status'];

    // Validate that 'update_status' is either 'Undone' or 'Done'
    if ($update_status !== 'Undone' && $update_status !== 'Done') {
        die("Invalid status value.");
    }

    // Prepare the update query using a prepared statement
    $stmt = $conn->prepare("UPDATE appointment SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $update_status, $appointment_id); // 's' for string, 'i' for integer

    if ($stmt->execute()) {
        // Redirect to the record page after a successful update
        header("Location: record.php?status_update=success");
        exit;
    } else {
        echo "Error updating status: " . $stmt->error;
    }

    $stmt->close();
}

// Fetch all records from the appointment table
$sql = "SELECT * FROM appointment";
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Records</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 80%;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
        }
        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            text-align: center;
            border: 1px solid #ddd;
        }
        th {
            background-color: #007BFF;
            color: white;
        }
        td {
            background-color: #f9f9f9;
        }
        .button {
            background-color: #007BFF;
            color: white;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .button:hover {
            background-color: #0056b3;
        }
        .status {
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Appointment Records</h2>

    <?php if ($result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Gender</th>
                    <th>Age</th>
                    <th>Contact</th>
                    <th>Appointment Date</th>
                    <th>Appointment Type</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <!-- Debugging output: Check if 'name' exists in the row -->
                        <td><?php 
                            if (isset($row['name']) && !empty($row['name'])) {
                                echo htmlspecialchars($row['name']); 
                            } else {
                                echo "Name not available";
                            }
                        ?></td>
                        <td><?php echo htmlspecialchars($row['gender']); ?></td>
                        <td><?php echo htmlspecialchars($row['age']); ?></td>
                        <td><?php echo htmlspecialchars($row['contact_no']); ?></td>
                        <td><?php echo htmlspecialchars($row['appointment_date']); ?></td>
                        <td><?php echo htmlspecialchars($row['appointment_type']); ?></td>
                        <td class="status">
                            <?php
                                // Check if 'status' exists and display it, else show 'NA'
                                $status = isset($row['status']) ? $row['status'] : 'NA';
                                echo htmlspecialchars($status);
                            ?>
                        </td>
                        <td>
                            <!-- Action button to toggle status -->
                            <?php if ($status == 'Undone'): ?>
                                <a href="record.php?appointment_id=<?php echo $row['id']; ?>&update_status=<?php echo $status; ?>" class="button">
                                    Mark as Done
                                </a>
                            <?php else: ?>
                                <a href="record.php?appointment_id=<?php echo $row['id']; ?>&update_status=<?php echo $status; ?>" class="button">
                                    Mark as Undone
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No records found.</p>
    <?php endif; ?>

</div>

</body>
</html>

<?php
$conn->close();
?>


