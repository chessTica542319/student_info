<?php
// Protect this page - require authentication
include 'auth.php';

include 'db.php';

// Check if an ID was passed in the URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Prepare the delete statement
    $sql = "DELETE FROM student WHERE id = ?";

    try {
        $stmt = $conn->prepare($sql);
        // Bind the ID parameter ("i" means integer)
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            // Redirect back to dashboard on success
            header("Location: dashboard.php");
            exit();
        }
        $stmt->close();
    } catch (Exception $e) {
        die("Error deleting record: " . $e->getMessage());
    }
} else {
    // If no ID is provided, just send them back to dashboard
    header("Location: dashboard.php");
    exit();
}
?>