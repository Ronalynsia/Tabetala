<?php
require_once  "config/config.php";

// Initialize database
$db = new Database();
$conn = $db->connect();

if ($conn) {
    // Kapag connected, redirect sa sign in page
   header("Location: auth/signin.php");
exit();

} else {
    // Kung may error sa DB, ipakita na lang
    echo "<h2>⚠️ Failed to Connect Database.</h2>";
}
?>
