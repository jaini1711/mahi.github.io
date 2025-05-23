<?php
// Database connection
$mysqli = new mysqli("localhost", "root", "", "my", 3307); // DB name 'my' aur port 3307

// Insert new appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $stmt = $mysqli->prepare("INSERT INTO appointment (NAME, gender, age, contact_no, appointment_date, appointment_type, STATUS, payment)
                              VALUES (?, ?, ?, ?, ?, ?, 'undone', 'Unpaid')");
    $stmt->bind_param("ssisss", $_POST['name'], $_POST['gender'], $_POST['age'], $_POST['contact_no'], $_POST['appointment_date'], $_POST['appointment_type']);
    $stmt->execute();
    echo json_encode(["success" => true, "id" => $stmt->insert_id]);
    exit;
}

// Toggle status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    $id = intval($_POST['id']);
    $result = $mysqli->query("SELECT STATUS FROM appointment WHERE id = $id");
    $row = $result->fetch_assoc();
    $new_status = ($row['STATUS'] === 'done') ? 'undone' : 'done';
    $mysqli->query("UPDATE appointment SET STATUS = '$new_status' WHERE id = $id");

    // Get new counts
    $done = $mysqli->query("SELECT COUNT(*) AS c FROM appointment WHERE STATUS = 'done'")->fetch_assoc()['c'];
    $undone = $mysqli->query("SELECT COUNT(*) AS c FROM appointment WHERE STATUS = 'undone'")->fetch_assoc()['c'];

    echo json_encode(["new_status" => $new_status, "done" => $done, "undone" => $undone]);
    exit;
}

// Toggle payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_payment') {
    $id = intval($_POST['id']);
    $result = $mysqli->query("SELECT payment FROM appointment WHERE id = $id");
    $row = $result->fetch_assoc();
    $new_payment = ($row['payment'] === 'Paid') ? 'Unpaid' : 'Paid';
    $mysqli->query("UPDATE appointment SET payment = '$new_payment' WHERE id = $id");

    echo json_encode(["new_payment" => $new_payment]);
    exit;
}

// Fetch appointments
$appointments = $mysqli->query("SELECT * FROM appointment ORDER BY id DESC");
$done_count = $mysqli->query("SELECT COUNT(*) AS c FROM appointment WHERE STATUS = 'done'")->fetch_assoc()['c'];
$undone_count = $mysqli->query("SELECT COUNT(*) AS c FROM appointment WHERE STATUS = 'undone'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Appointments</title>
<style>
    body { font-family: Arial; padding: 20px; }
    table { border-collapse: collapse; width: 100%; margin-top: 20px; }
    th, td { padding: 8px; border: 1px solid #ccc; text-align: center; }
    button { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; }
    .done { background-color: red; color: white; }
    .undone { background-color: green; color: white; }
    .paid { background-color: blue; color: white; }
    .unpaid { background-color: gray; color: white; }
    input, select { margin: 5px; padding: 5px; }
</style>
</head>
<body>
<h1>Appointments</h1>
<p>
    <strong>Done:</strong> <span id="done-count"><?= $done_count ?></span> |
    <strong>Undone:</strong> <span id="undone-count"><?= $undone_count ?></span>
</p>

<form id="appointment-form">
    <input type="text" name="name" placeholder="Name" required />
    <input type="text" name="gender" placeholder="Gender" required />
    <input type="number" name="age" placeholder="Age" required />
    <input type="text" name="contact_no" placeholder="Contact No" required />
    <input type="date" name="appointment_date" required />
    <select name="appointment_type" required>
        <option value="Online">Online</option>
        <option value="Offline">Offline</option>
    </select>
    <button type="submit">Add Appointment</button>
</form>

<table id="appointment-table">
    <thead>
        <tr>
            <th>ID</th><th>Name</th><th>Gender</th><th>Age</th><th>Contact No</th><th>Appointment Date</th><th>Type</th><th>Status</th><th>Payment</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $appointments->fetch_assoc()): ?>
        <tr data-id="<?= $row['id'] ?>">
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['NAME']) ?></td>
            <td><?= htmlspecialchars($row['gender']) ?></td>
            <td><?= $row['age'] ?></td>
            <td><?= htmlspecialchars($row['contact_no']) ?></td>
            <td><?= $row['appointment_date'] ?></td>
            <td><?= htmlspecialchars($row['appointment_type']) ?></td>
            <td>
                <button class="<?= $row['STATUS'] === 'done' ? 'done' : 'undone' ?>"
                        onclick="toggleStatus(<?= $row['id'] ?>, this)">
                    <?= ucfirst($row['STATUS']) ?>
                </button>
            </td>
            <td>
                <button class="<?= strtolower($row['payment']) === 'paid' ? 'paid' : 'unpaid' ?>"
                        onclick="togglePayment(<?= $row['id'] ?>, this)">
                    <?= ucfirst($row['payment']) ?>
                </button>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<script>
document.getElementById("appointment-form").addEventListener("submit", function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append("action", "add");

    fetch("", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(response => {
        if (response.success) {
            const table = document.querySelector("#appointment-table tbody");
            const data = Object.fromEntries(formData.entries());
            const row = document.createElement("tr");
            row.setAttribute("data-id", response.id);
            row.innerHTML = `
                <td>${response.id}</td>
                <td>${data.name}</td>
                <td>${data.gender}</td>
                <td>${data.age}</td>
                <td>${data.contact_no}</td>
                <td>${data.appointment_date}</td>
                <td>${data.appointment_type}</td>
                <td>
                    <button class="undone" onclick="toggleStatus(${response.id}, this)">Undone</button>
                </td>
                <td>
                    <button class="unpaid" onclick="togglePayment(${response.id}, this)">Unpaid</button>
                </td>
            `;
            table.prepend(row);

            // Update counts
            const undoneCountElem = document.getElementById("undone-count");
            undoneCountElem.innerText = parseInt(undoneCountElem.innerText) + 1;

            this.reset();
        }
    });
});

function toggleStatus(id, button) {
    const formData = new FormData();
    formData.append("action", "toggle_status");
    formData.append("id", id);

    fetch("", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        button.className = data.new_status === "done" ? "done" : "undone";
        button.innerText = data.new_status.charAt(0).toUpperCase() + data.new_status.slice(1);
        document.getElementById("done-count").innerText = data.done;
        document.getElementById("undone-count").innerText = data.undone;
    });
}

function togglePayment(id, button) {
    const formData = new FormData();
    formData.append("action", "toggle_payment");
    formData.append("id", id);

    fetch("", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        button.className = data.new_payment.toLowerCase() === "paid" ? "paid" : "unpaid";
        button.innerText = data.new_payment;
    });
}
</script>
</body>
</html>
