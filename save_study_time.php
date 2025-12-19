<?php
// save_study_time.php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}
include 'db.php';

$user_id = $_SESSION['user_id'];
$study_name = trim($_POST['study_name'] ?? 'General Study');
$minutes = intval($_POST['minutes'] ?? 0);
$today = date('Y-m-d');

if ($minutes > 0) {
    try {
        // Check if already exists
        $stmt = $pdo->prepare("SELECT id, minutes FROM study_logs WHERE user_id=? AND study_date=? AND study_name=?");
        $stmt->execute([$user_id, $today, $study_name]);
        $existing = $stmt->fetch();
        if ($existing) {
            $newMinutes = $existing['minutes'] + $minutes;
            $upd = $pdo->prepare("UPDATE study_logs SET minutes=? WHERE id=?");
            $upd->execute([$newMinutes, $existing['id']]);
        } else {
            $ins = $pdo->prepare("INSERT INTO study_logs (user_id, study_date, study_name, minutes) VALUES (?, ?, ?, ?)");
            $ins->execute([$user_id, $today, $study_name, $minutes]);
        }
        echo json_encode(['status' => 'success', 'message' => 'Study time saved']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid minutes']);
}
?>
