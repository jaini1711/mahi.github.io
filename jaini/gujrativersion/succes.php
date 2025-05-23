<?php
session_start();

$conn = new mysqli("localhost", "root", "", "my", 3307);
if ($conn->connect_error) {
    die("કનેક્શન નિષ્ફળ થયું: " . $conn->connect_error);
}

$undone_result = $conn->query("SELECT COUNT(*) AS total_undone FROM appointment WHERE status='Undone'");
$undone_count = 0;
if ($undone_result && $row = $undone_result->fetch_assoc()) {
    $undone_count = $row['total_undone'];
}

$name = $_SESSION['name'] ?? 'N/A';
$contact = $_SESSION['contact'] ?? 'N/A';
$age = $_SESSION['age'] ?? 'N/A';
$gender = $_SESSION['gender'] ?? 'N/A';
$appointment_date = $_SESSION['appointment_date'] ?? 'N/A';
$appointment_type = $_SESSION['appointment_type'] ?? 'N/A';

unset($_SESSION['name'], $_SESSION['contact'], $_SESSION['age'], $_SESSION['gender'], $_SESSION['appointment_date'], $_SESSION['appointment_type']);
?>
<!DOCTYPE html>
<html lang="gu">
<head>
    <meta charset="UTF-8" />
    <title>નિયુક્તિ પુષ્ટિ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #e0f7fa, #ffffff);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .box {
            background: #ffffff;
            padding: 30px 40px;
            border-radius: 15px;
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 500px;
        }

        h2 {
            color: #28a745;
            font-size: 24px;
            text-align: center;
            margin-bottom: 25px;
        }

        .info {
            text-align: left;
            font-size: 17px;
            margin: 10px 0;
            color: #333;
        }

        .info strong {
            color: #000;
        }

        .buttons {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            margin-top: 30px;
        }

        .button {
            background: #007BFF;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 6px;
            margin-top: 10px;
            transition: background 0.3s ease;
            text-align: center;
            flex: 1 1 45%;
        }

        .button:hover {
            background: #0056b3;
        }

        .home-button {
            background: #28a745;
        }

        .home-button:hover {
            background: #1e7e34;
        }

        @media (max-width: 480px) {
            .box {
                padding: 25px;
            }

            h2 {
                font-size: 20px;
            }

            .info {
                font-size: 15px;
            }

            .button {
                font-size: 15px;
                padding: 8px 14px;
            }
        }
    </style>
</head>
<body>

<div class="box">
    <h2>✅ નિયુક્તિ સફળતાપૂર્વક બુક થઈ ગઈ!</h2>

    <p class="info">👤 નામ: <strong><?php echo htmlspecialchars($name); ?></strong></p>
    <p class="info">📞 સંપર્ક: <strong><?php echo htmlspecialchars($contact); ?></strong></p>
    <p class="info">🎂 ઉંમર: <strong><?php echo htmlspecialchars($age); ?></strong></p>
    <p class="info">🚻 લિંગ: <strong><?php echo htmlspecialchars($gender); ?></strong></p>
    <p class="info">📅 નિયુક્તિ તારીખ: <strong><?php echo htmlspecialchars($appointment_date); ?></strong></p>
    <p class="info">💬 નિયુક્તિ પ્રકાર: <strong><?php echo htmlspecialchars($appointment_type); ?></strong></p>
    <p class="info">🕐 બાકી રહેલા નિયુક્તિઓ કુલ: <strong><?php echo $undone_count; ?></strong></p>

    <div class="buttons">
        <a class="button" href="index.php">📅 બીજી નિયુક્તિ બુક કરો</a>
        <a class="button home-button" href="main.html">🏠 હોમ પેજ પર જાઓ</a>
    </div>
</div>

</body>
</html>
