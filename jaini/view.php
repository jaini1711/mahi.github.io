<?php
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'my';
$port = 3307;

$conn = new mysqli($host, $user, $password, $database, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$contact_no = $_GET['contact_no'] ?? '';
$otp = $_GET['otp'] ?? '';

$contact_no_safe = $conn->real_escape_string($contact_no);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_id'])) {
    $delete_id = intval($_POST['delete_id']);
    $conn->query("DELETE FROM appointment WHERE id = $delete_id");
    header("Location: " . $_SERVER['PHP_SELF'] . "?contact_no=$contact_no&otp=$otp");
    exit();
}

function formatTime($minutes) {
    if ($minutes >= 60) {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return $mins > 0 ? "$hours hr $mins min" : "$hours hr";
    }
    return "$minutes min";
}

// Get all undone appointment IDs ordered by id ascending
$undone_sql = "SELECT id FROM appointment WHERE STATUS = 'undone' ORDER BY id ASC";
$undone_result = $conn->query($undone_sql);
$undone_ids = [];
while ($r = $undone_result->fetch_assoc()) {
    $undone_ids[] = $r['id'];
}

// Fetch user's appointments joined with hospital name
$sql = "SELECT a.*, h.name AS hospital_name
        FROM appointment a
        LEFT JOIN hospitals h ON a.hospital_id = h.id
        WHERE a.contact_no = '$contact_no_safe'
        ORDER BY a.id ASC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Appointment Details</title>
    <style>
        body {
            background-color: #f0f9ff;
            font-family: Arial, sans-serif;
            padding: 40px;
            text-align: center;
        }
        table {
            margin: 0 auto;
            border-collapse: collapse;
            width: 90%;
        }
        th, td {
            border: 1px solid #cce0ff;
            padding: 12px 18px;
            font-size: 16px;
        }
        th {
            background-color: #4da6ff;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2faff;
        }
        .cancel-btn {
            background-color: red;
            color: white;
            border: none;
            padding: 6px 14px;
            cursor: pointer;
            border-radius: 5px;
        }
        #deleteModal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.4);
            z-index: 9999;
        }
        .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            width: 300px;
            margin: 150px auto;
            text-align: center;
            box-shadow: 0 0 10px rgba(0,0,0,0.25);
        }
        .modal-content button {
            margin: 10px 5px;
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            font-weight: bold;
        }
        .confirm-btn {
            background-color: red;
            color: white;
        }
        .cancel-modal-btn {
            background-color: gray;
            color: white;
        }
    </style>
</head>
<body>

<h2>Appointments for Contact: <?php echo htmlspecialchars($contact_no); ?></h2>

<?php
if ($result->num_rows > 0) {
    echo "<table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Gender</th>
                <th>Age</th>
                <th>Contact No</th>
                <th>Appointment Date</th>
                <th>Appointment Type</th>
                <th>Hospital Name</th>
                <th>Estimated Wait Time</th>
                <th>Remaining Undone</th>
                <th>Cancel</th>
            </tr>";
    
    foreach ($result as $row) {
        $name = $row['NAME'] ?? 'Unknown';

        $pos = array_search($row['id'], $undone_ids);

        if ($pos !== false) {
            $remaining_undone = $pos;
            $wait_minutes = $remaining_undone * 5;
            $wait_display = formatTime($wait_minutes);
        } else {
            $remaining_undone = "-";
            $wait_display = "-";
        }

        $hospital_name = htmlspecialchars($row['hospital_name'] ?? 'N/A');

        echo "<tr>
                <td>{$row['id']}</td>
                <td>$name</td>
                <td>{$row['gender']}</td>
                <td>{$row['age']}</td>
                <td>{$row['contact_no']}</td>
                <td>{$row['appointment_date']}</td>
                <td>{$row['appointment_type']}</td>
                <td>$hospital_name</td>
                <td>$wait_display</td>
                <td>$remaining_undone</td>
                <td>
                    <form onsubmit=\"openModal({$row['id']}); return false;\">
                        <button class='cancel-btn' type='submit'>Cancel</button>
                    </form>
                </td>
              </tr>";
    }

    echo "</table>";
} else {
    echo "<p>No appointments found for this number.</p>";
}
$conn->close();
?>

<!-- Delete Confirmation Modal -->
<div id="deleteModal">
    <div class="modal-content">
        <p>Are you sure you want to cancel this appointment?</p>
        <form id="confirmDeleteForm" method="POST">
            <input type="hidden" name="delete_id" id="deleteIdInput" />
            <button type="submit" class="confirm-btn">Yes, Cancel</button>
            <button type="button" onclick="closeModal()" class="cancel-modal-btn">No</button>
        </form>
    </div>
</div>

<script>
    function openModal(id) {
        document.getElementById('deleteIdInput').value = id;
        document.getElementById('deleteModal').style.display = 'block';
    }
    function closeModal() {
        document.getElementById('deleteModal').style.display = 'none';
    }
</script>

</body>
</html>
