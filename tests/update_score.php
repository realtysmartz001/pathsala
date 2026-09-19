<?php
include '../includes/db_connect.php';

if (isset($_POST['id']) && isset($_POST['score'])) {
    $id = intval($_POST['id']);
    $score = intval($_POST['score']);

    $stmt = $conn->prepare("UPDATE test_results SET score = ? WHERE id = ?");
    $stmt->bind_param("ii", $score, $id);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }
}
?>