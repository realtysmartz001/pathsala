<?php
include '../includes/db_connect.php';

if (isset($_GET['code'])) {
    $code = $_GET['code'];

    $stmt = $conn->prepare("SELECT is_approved FROM users WHERE verification_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        echo json_encode([
            'is_approved' => (int)$user['is_approved']
        ]);
    } else {
        echo json_encode(['is_approved' => -1]);
    }

    $stmt->close();
}
$conn->close();
?>
