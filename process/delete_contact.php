<?php
// delete_contact.php
include '../includes/db_connect.php';

if (isset($_POST['id'])) {
    $id = $_POST['id'];

    $stmt = $conn->prepare("DELETE FROM contact_form WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: ../admin/admin_contact.php");
    } else {
        echo "Error deleting record.";
    }
}
?>
