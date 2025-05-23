<?php
session_start();

$conn = new mysqli("localhost", "root", "", "my", 3307);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT id, name FROM hospitals";
$result = $conn->query($sql);

if (isset($_GET['hospital_id'])) {
    $hospital_id = intval($_GET['hospital_id']);
    $_SESSION['hospital_id'] = $hospital_id;
    header("Location: index.php?hospital_id=" . $hospital_id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Select Hospital</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f0f8ff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px 10px;
        }
        .hospital-btn {
            background-color: #007BFF;
            color: white;
            border: none;
            padding: 15px 20px;
            font-size: 18px;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: 90%;
            max-width: 600px;
            text-align: center;
            margin: 10px 0;
            text-decoration: none;
            display: block;
            user-select: none;
        }
        .hospital-btn:hover, .hospital-btn:focus {
            background-color: #0056b3;
            outline: none;
        }
        p.no-hospitals {
            font-size: 18px;
            color: #555;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<?php
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo '<a href="?hospital_id=' . $row['id'] . '" class="hospital-btn" tabindex="0">' . htmlspecialchars($row['name']) . '</a>';
    }
} else {
    echo '<p class="no-hospitals">No hospitals found.</p>';
}
?>

</body>
</html>
