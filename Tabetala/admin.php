<?php
session_start();
require_once __DIR__ . '/config/config.php';

// ✅ Connect to DB
$conn = null;
if (class_exists('Database')) {
    $db = new Database();
    $conn = $db->connect();
} elseif (isset($conn) && $conn instanceof PDO) {
    // already
} elseif (isset($pdo) && $pdo instanceof PDO) {
    $conn = $pdo;
} else {
    die("Database connection not found.");
}

if (!isset($_POST['action']) || !isset($_POST['user_id'])) {
    header("Location: admin_profile.php");
    exit;
}

$action  = $_POST['action'];
$user_id = (int) $_POST['user_id'];
$errors  = [];

// ✅ Update Avatar
if ($action === "avatar") {
    if (!empty($_FILES['avatar']['name'])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $fileName = time() . "_" . basename($_FILES["avatar"]["name"]);
        $targetFile = $targetDir . $fileName;

        if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $targetFile)) {
            $stmt = $conn->prepare("UPDATE users SET avatar=:avatar WHERE id=:id");
            $stmt->execute([":avatar"=>$targetFile, ":id"=>$user_id]);
            $_SESSION['profile_success'] = "Profile picture updated.";
        } else {
            $errors[] = "Failed to upload image.";
        }
    } else {
        $errors[] = "No file selected.";
    }
}

// ✅ Update Info
if ($action === "info") {
    $first = trim($_POST['first_name']);
    $last  = trim($_POST['last_name']);
    $usern = trim($_POST['username']);
    $email = trim($_POST['email']);

    if ($first=="" || $last=="" || $usern=="" || $email=="") {
        $errors[] = "All fields are required.";
    }

    if (!$errors) {
        $stmt = $conn->prepare("UPDATE users SET first_name=:f,last_name=:l,username=:u,email=:e WHERE id=:id");
        $stmt->execute([":f"=>$first,":l"=>$last,":u"=>$usern,":e"=>$email,":id"=>$user_id]);
        $_SESSION['profile_success'] = "Profile information updated.";
    }
}

// ✅ Change Password
if ($action === "password") {
    $current = $_POST['current_password'];
    $new     = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    $stmt = $conn->prepare("SELECT password FROM users WHERE id=:id");
    $stmt->execute([":id"=>$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !password_verify($current, $row['password'])) {
        $errors[] = "Current password is incorrect.";
    } elseif ($new !== $confirm) {
        $errors[] = "Passwords do not match.";
    } elseif (strlen($new) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password=:p WHERE id=:id");
        $stmt->execute([":p"=>$hash,":id"=>$user_id]);
        $_SESSION['profile_success'] = "Password updated successfully.";
    }
}

if ($errors) {
    $_SESSION['profile_errors'] = $errors;
}
header("Location: admin_profile.php");
exit;
