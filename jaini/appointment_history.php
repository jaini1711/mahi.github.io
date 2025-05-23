<?php
/********* DB CONNECT *********/
$conn = new mysqli("localhost", "root", "", "my", 3307);
if ($conn->connect_error) { die("DB error: " . $conn->connect_error); }

$phone = $_POST['phone'] ?? '';
$rows  = [];

if ($phone) {
    $stmt = $conn->prepare("SELECT * FROM appointment WHERE contact_no = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Appointment History</title>
<style>
  body{font-family:Arial,Helvetica,sans-serif;padding:20px}
  table{width:100%;border-collapse:collapse;margin-top:20px}
  th,td{border:1px solid #aaa;padding:8px;text-align:center}
  th{background:#007bff;color:#fff}
  button.cancel{background:#dc3545;color:#fff;border:none;padding:6px 10px;border-radius:4px;cursor:pointer}
</style>
</head>
<body>
<h2>Appointments for <?= htmlspecialchars($phone) ?></h2>

<table id="histTable">
 <thead>
  <tr>
    <th>Name</th><th>Gender</th><th>Age</th><th>Contact</th>
    <th>Date</th><th>Type</th><th>Cancel</th>
  </tr>
 </thead>
 <tbody>
<?php if ($rows): ?>
  <?php foreach ($rows as $r): ?>
    <tr data-id="<?= $r['id'] ?>">
      <td><?= htmlspecialchars($r['name']) ?></td>
      <td><?= htmlspecialchars($r['gender']) ?></td>
      <td><?= htmlspecialchars($r['age']) ?></td>
      <td><?= htmlspecialchars($r['contact_no']) ?></td>
      <td><?= htmlspecialchars($r['appointment_date']) ?></td>
      <td><?= htmlspecialchars($r['appointment_type']) ?></td>
      <td><button class="cancel">❌ Cancel</button></td>
    </tr>
  <?php endforeach; ?>
<?php else: ?>
  <tr><td colspan="7">No appointments found.</td></tr>
<?php endif; ?>
 </tbody>
</table>

<script>
/* Attach event delegation for dynamic rows */
document.getElementById('histTable').addEventListener('click', async e => {
  if (!e.target.classList.contains('cancel')) return;

  const row   = e.target.closest('tr');
  const id    = row.dataset.id;

  if (!confirm('Really cancel this appointment?')) return;

  const res = await fetch('cancel_appointment.php', {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: `id=${id}`
  });
  const data = await res.json();

  if (data.success) {
      row.remove();
      alert('Appointment cancelled.');
  } else {
      alert('Error cancelling appointment.');
  }
});
</script>
</body>
</html>
