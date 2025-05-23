<?php
header('Content-Type: application/json');

$id = $_POST['id'] ?? '';

if (!$id) { echo json_encode(['success'=>false]); exit; }

$conn = new mysqli("localhost","root","","my",3307);
if ($conn->connect_error) { echo json_encode(['success'=>false]); exit; }

$stmt = $conn->prepare("DELETE FROM appointment WHERE id = ?");
$stmt->bind_param("i", $id);
$ok   = $stmt->execute();

echo json_encode(['success'=> $ok]);
