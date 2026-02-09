<?php
// reset_exports.php - Reset all leads to unexported status
require_once 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($conn->query("UPDATE trustpilot_leads SET is_exported = 0")) {
        echo json_encode(['success' => true, 'message' => 'Export history cleared successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
