<?php
include '../includes/db_user.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    $stmt = $conn->prepare("SELECT edit_allowed FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user) {
        $newValue = ($user['edit_allowed'] == 1) ? 0 : 1;
        $msg = ($newValue == 1) ? 'edit_allowed' : 'edit_removed';

        $stmt2 = $conn->prepare("UPDATE users SET edit_allowed = ? WHERE id = ?");
        $stmt2->bind_param("ii", $newValue, $id);
        $stmt2->execute();
        $stmt2->close();

        header("Location: ../admin/admin_user.php?msg=$msg");
        exit();
    } else {
        header("Location: ../admin/admin_user.php?msg=invalid");
        exit();
    }
} else {
    header("Location: ../admin/admin_user.php?msg=invalid");
    exit();
}
?>
