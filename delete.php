<?php
session_start();
include 'db.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
    // fetch image name
    if ($stmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?")) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->bind_result($img);
        if ($stmt->fetch()) {
            if (!empty($img)) {
                $path = 'uploads/' . $img;
                if (is_file($path)) { @unlink($path); }
            }
        }
        $stmt->close();
    }

    if ($stmt = $conn->prepare("DELETE FROM users WHERE id = ?")) {
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Record deleted successfully'];
        } else {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Error deleting record'];
        }
        $stmt->close();
    }
}
header("Location: index.php");
?>
