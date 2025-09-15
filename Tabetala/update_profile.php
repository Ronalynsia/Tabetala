<?php
session_start();
require_once __DIR__ . '/config/config.php';

// DB connect
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

$action = $_POST['action'] ?? '';
$user_id = (int) ($_POST['user_id'] ?? 0);
$errors = [];

if ($action === 'info') {
    $first = trim($_POST['first_name']);
    $last = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);

    if (!$first || !$last || !$email || !$username) {
        $errors[] = "All fields are required.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, email=?, username=? WHERE id=?");
        $stmt->execute([$first, $last, $email, $username, $user_id]);
        $_SESSION['profile_success'] = "Profile updated successfully.";
    } else {
        $_SESSION['profile_errors'] = $errors;
    }

} elseif ($action === 'avatar') {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
        $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $filename = "avatar_" . $user_id . "." . strtolower($ext);
        $target = __DIR__ . "/uploads/" . $filename;

        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target)) {
            $stmt = $conn->prepare("UPDATE users SET profile_picture=? WHERE id=?");
            $stmt->execute([$filename, $user_id]);
            $_SESSION['profile_success'] = "Avatar updated successfully.";
        } else {
            $errors[] = "Failed to upload image.";
        }
    } else {
        $errors[] = "No image selected.";
    }

    if ($errors) $_SESSION['profile_errors'] = $errors;

} elseif ($action === 'password') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$current || !$new || !$confirm) {
        $errors[] = "All password fields are required.";
    } elseif ($new !== $confirm) {
        $errors[] = "Passwords do not match.";
    } else {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id=?");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && password_verify($current, $row['password'])) {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->execute([$hash, $user_id]);
            $_SESSION['profile_success'] = "Password updated successfully.";
        } else {
            $errors[] = "Current password is incorrect.";
        }
    }

    if ($errors) $_SESSION['profile_errors'] = $errors;
}

header("Location: admin.php");
exit;
