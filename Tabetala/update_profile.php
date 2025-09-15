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
    die("Database connection not found. Check config.php");
}

// Require login
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/signin.php");
    exit;
}
$user_id = (int) $_SESSION['user_id'];

$action = $_POST['action'] ?? null;
$errors = [];
$success = null;

try {
    if ($action === "info") {
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $mobile    = trim($_POST['mobile'] ?? '');

        if (!$full_name || !$email) {
            $errors[] = "Name and email are required.";
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name=:n, email=:e, mobile=:m WHERE id=:id");
            $stmt->execute([":n"=>$full_name, ":e"=>$email, ":m"=>$mobile, ":id"=>$user_id]);
            $success = "Profile updated successfully.";
        }
    }

    elseif ($action === "avatar") {
        if (!empty($_FILES['profile_picture']['name'])) {
            $file = $_FILES['profile_picture'];
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif'];
            if (!in_array($ext,$allowed)) {
                $errors[] = "Invalid file type.";
            } else {
                $newName = "user_" . $user_id . "_" . time() . "." . $ext;
                $target = __DIR__ . "/uploads/" . $newName;
                if (move_uploaded_file($file['tmp_name'],$target)) {
                    // optional: delete old avatar
                    $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id=:id");
                    $stmt->execute([":id"=>$user_id]);
                    $old = $stmt->fetchColumn();
                    if ($old && file_exists(__DIR__."/uploads/".$old)) {
                        unlink(__DIR__."/uploads/".$old);
                    }
                    $stmt = $conn->prepare("UPDATE users SET profile_picture=:p WHERE id=:id");
                    $stmt->execute([":p"=>$newName, ":id"=>$user_id]);
                    $success = "Profile picture updated.";
                } else {
                    $errors[] = "Failed to upload image.";
                }
            }
        } else {
            $errors[] = "No file uploaded.";
        }
    }

    elseif ($action === "password") {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!$current || !$new || !$confirm) {
            $errors[] = "All password fields are required.";
        } elseif ($new !== $confirm) {
            $errors[] = "New passwords do not match.";
        } else {
            $stmt = $conn->prepare("SELECT password FROM users WHERE id=:id");
            $stmt->execute([":id"=>$user_id]);
            $hash = $stmt->fetchColumn();
            if (!$hash || !password_verify($current,$hash)) {
                $errors[] = "Current password is incorrect.";
            } else {
                $newHash = password_hash($new, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password=:p WHERE id=:id");
                $stmt->execute([":p"=>$newHash, ":id"=>$user_id]);
                $success = "Password changed successfully.";
            }
        }
    }

} catch (Exception $e) {
    $errors[] = "Error: ".$e->getMessage();
}

$_SESSION['profile_errors'] = $errors;
$_SESSION['profile_success'] = $success;
header("Location: profile.php");
exit;
