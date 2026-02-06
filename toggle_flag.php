<?php
// toggle_flag.php - AJAX endpoint for toggling flag status
header("Content-Type: application/json");
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $lead_id = (int)$data['lead_id'];
    $new_status = (int)$data['is_flagged'];
    
    $stmt = $conn->prepare("UPDATE trustpilot_leads SET is_flagged = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_status, $lead_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'is_flagged' => $new_status]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    $stmt->close();
}
$conn->close();
?>
