<?php
$mysqli = new mysqli("localhost", "root", "", "my", 3307);
if ($mysqli->connect_errno) {
    die("Failed to connect to MySQL: " . $mysqli->connect_error);
}

$hospital_id = isset($_GET['hospital_id']) ? intval($_GET['hospital_id']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verify hospital login
    if (isset($_POST['verify_login'])) {
        $id = intval($_POST['hospital_id']);
        $pass = $_POST['password'];

        $stmt = $mysqli->prepare("SELECT * FROM hospitals WHERE id = ? AND password = ?");
        $stmt->bind_param("is", $id, $pass);
        $stmt->execute();
        $result = $stmt->get_result();

        echo json_encode(["success" => $result->num_rows > 0]);
        exit;
    }

    // Add new appointment
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $stmt = $mysqli->prepare("INSERT INTO appointment (NAME, gender, age, contact_no, appointment_date, appointment_type, STATUS, payment, hospital_id)
                                  VALUES (?, ?, ?, ?, ?, ?, 'undone', 'Unpaid', ?)");
        $stmt->bind_param("ssisssi", $_POST['name'], $_POST['gender'], $_POST['age'], $_POST['contact_no'], $_POST['appointment_date'], $_POST['appointment_type'], $hospital_id);
        $stmt->execute();

        $id = $stmt->insert_id;
        echo json_encode([
            "success" => true,
            "id" => $id,
            "name" => $_POST['name'],
            "gender" => $_POST['gender'],
            "age" => $_POST['age'],
            "contact_no" => $_POST['contact_no'],
            "appointment_date" => $_POST['appointment_date'],
            "appointment_type" => $_POST['appointment_type'],
            "status" => 'undone',
            "payment" => 'Unpaid'
        ]);
        exit;
    }

    // Toggle status (done/undone)
    if ($_POST['action'] === 'toggle_status') {
        $id = intval($_POST['id']);
        $result = $mysqli->query("SELECT STATUS FROM appointment WHERE id=$id LIMIT 1");

        if ($result && $row = $result->fetch_assoc()) {
            $newStatus = ($row['STATUS'] === 'done') ? 'undone' : 'done';
            $update = $mysqli->prepare("UPDATE appointment SET STATUS=? WHERE id=?");
            $update->bind_param("si", $newStatus, $id);
            $update->execute();
            echo json_encode(["success" => true, "newStatus" => $newStatus]);
            exit;
        }
        echo json_encode(["success" => false]);
        exit;
    }

    // Toggle payment (Paid/Unpaid)
    if ($_POST['action'] === 'toggle_payment') {
        $id = intval($_POST['id']);
        $result = $mysqli->query("SELECT payment FROM appointment WHERE id=$id LIMIT 1");

        if ($result && $row = $result->fetch_assoc()) {
            $newPayment = ($row['payment'] === 'Paid') ? 'Unpaid' : 'Paid';
            $update = $mysqli->prepare("UPDATE appointment SET payment=? WHERE id=?");
            $update->bind_param("si", $newPayment, $id);
            $update->execute();
            echo json_encode(["success" => true, "newPayment" => $newPayment]);
            exit;
        }
        echo json_encode(["success" => false]);
        exit;
    }
}

$appointments = $hospital_id > 0
    ? $mysqli->query("SELECT * FROM appointment WHERE hospital_id = $hospital_id ORDER BY id DESC")
    : false;

$done_count = $hospital_id > 0
    ? $mysqli->query("SELECT COUNT(*) AS c FROM appointment WHERE STATUS = 'done' AND hospital_id = $hospital_id")->fetch_assoc()['c']
    : 0;

$undone_count = $hospital_id > 0
    ? $mysqli->query("SELECT COUNT(*) AS c FROM appointment WHERE STATUS = 'undone' AND hospital_id = $hospital_id")->fetch_assoc()['c']
    : 0;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Hospital Appointments</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        input, select, button { padding: 6px; margin: 5px; }
        .undone { background: green; color: white; cursor: pointer; }
        .done { background: red; color: white; cursor: pointer; }
        .unpaid { background: gray; color: white; cursor: pointer; }
        .paid { background: blue; color: white; cursor: pointer; }
        #hospital-popup {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex; justify-content: center; align-items: center;
        }
        #hospital-popup form {
            background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px black;
        }
    </style>
</head>
<body>

<?php if (!$hospital_id): ?>
<div id="hospital-popup">
    <form onsubmit="submitHospitalID(event)">
        <h3>Login to Hospital System</h3>
        <input type="number" id="hospital-id" required placeholder="Hospital ID"><br>
        <input type="password" id="hospital-pass" required placeholder="Password"><br><br>
        <button type="submit">Login</button>
    </form>
</div>
<script>
function submitHospitalID(e) {
    e.preventDefault();
    const id = document.getElementById("hospital-id").value;
    const pass = document.getElementById("hospital-pass").value;

    fetch("", {
        method: "POST",
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ verify_login: true, hospital_id: id, password: pass })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.href = "?hospital_id=" + id;
        } else {
            alert("Invalid ID or Password");
        }
    });
}
</script>
<?php else: ?>

<h2>Appointments for Hospital ID: <?= htmlspecialchars($hospital_id) ?></h2>
<p><b>Done:</b> <span id="done-count"><?= $done_count ?></span> |
   <b>Undone:</b> <span id="undone-count"><?= $undone_count ?></span></p>

<form id="appointment-form">
    <input name="name" placeholder="Name" required>
    <input name="gender" placeholder="Gender" required>
    <input name="age" type="number" placeholder="Age" required>
    <input name="contact_no" placeholder="Contact" required>
    <input name="appointment_date" type="date" required>
    <select name="appointment_type" required>
        <option value="Online">Online</option>
        <option value="Offline">Offline</option>
    </select>
    <button type="submit">Add Appointment</button>
</form>

<table>
    <thead>
        <tr>
            <th>ID</th><th>Name</th><th>Gender</th><th>Age</th><th>Contact</th><th>Date</th><th>Type</th><th>Status</th><th>Payment</th>
        </tr>
    </thead>
    <tbody id="appointment-body">
        <?php if ($appointments): while($row = $appointments->fetch_assoc()): ?>
        <tr data-id="<?= $row['id'] ?>">
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['NAME']) ?></td>
            <td><?= htmlspecialchars($row['gender']) ?></td>
            <td><?= $row['age'] ?></td>
            <td><?= htmlspecialchars($row['contact_no']) ?></td>
            <td><?= $row['appointment_date'] ?></td>
            <td><?= htmlspecialchars($row['appointment_type']) ?></td>
            <td><button class="status-btn <?= $row['STATUS'] ?>" data-id="<?= $row['id'] ?>"><?= ucfirst($row['STATUS']) ?></button></td>
            <td><button class="payment-btn <?= strtolower($row['payment']) ?>" data-id="<?= $row['id'] ?>"><?= ucfirst($row['payment']) ?></button></td>
        </tr>
        <?php endwhile; endif; ?>
    </tbody>
</table>

<script>
document.getElementById("appointment-form").addEventListener("submit", function(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    formData.append("action", "add");

    fetch("?hospital_id=<?= $hospital_id ?>", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const row = document.createElement("tr");
            row.setAttribute("data-id", data.id);
            row.innerHTML = `
                <td>${data.id}</td>
                <td>${data.name}</td>
                <td>${data.gender}</td>
                <td>${data.age}</td>
                <td>${data.contact_no}</td>
                <td>${data.appointment_date}</td>
                <td>${data.appointment_type}</td>
                <td><button class="status-btn undone" data-id="${data.id}">Undone</button></td>
                <td><button class="payment-btn unpaid" data-id="${data.id}">Unpaid</button></td>
            `;
            document.getElementById("appointment-body").prepend(row);
            form.reset();

            // Update counts
            let undone = document.getElementById("undone-count");
            undone.textContent = parseInt(undone.textContent) + 1;
        }
    });
});

// Toggle status
document.body.addEventListener("click", function(e) {
    if (e.target.classList.contains("status-btn")) {
        const id = e.target.dataset.id;
        fetch("?hospital_id=<?= $hospital_id ?>", {
            method: "POST",
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: "toggle_status", id })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                e.target.className = "status-btn " + data.newStatus;
                e.target.textContent = data.newStatus.charAt(0).toUpperCase() + data.newStatus.slice(1);

                let done = document.getElementById("done-count");
                let undone = document.getElementById("undone-count");
                if (data.newStatus === "done") {
                    done.textContent = parseInt(done.textContent) + 1;
                    undone.textContent = parseInt(undone.textContent) - 1;
                } else {
                    done.textContent = parseInt(done.textContent) - 1;
                    undone.textContent = parseInt(undone.textContent) + 1;
                }
            }
        });
    }

    // Toggle payment
    if (e.target.classList.contains("payment-btn")) {
        const id = e.target.dataset.id;
        fetch("?hospital_id=<?= $hospital_id ?>", {
            method: "POST",
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: "toggle_payment", id })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                e.target.className = "payment-btn " + data.newPayment.toLowerCase();
                e.target.textContent = data.newPayment;
            }
        });
    }
});
</script>
<?php endif; ?>
</body>
</html>