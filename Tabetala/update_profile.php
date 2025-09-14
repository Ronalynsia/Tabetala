<?php
session_start();
require_once __DIR__ . '/config/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/signin.php");
    exit;
}

$db = new Database();
$conn = $db->connect();
$user_id = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: profile.php");
    exit;
}

$form_type = $_POST['form_type'] ?? '';

try {
    if ($form_type === 'info') {
        // Update user info
        $stmt = $conn->prepare("UPDATE users SET first_name = :fn, last_name = :ln, username = :un, email = :em WHERE id = :id");
        $stmt->execute([
            ':fn' => trim($_POST['first_name']),
            ':ln' => trim($_POST['last_name']),
            ':un' => trim($_POST['username']),
            ':em' => trim($_POST['email']),
            ':id' => $user_id
        ]);
        $_SESSION['profile_success'] = "Info updated successfully.";

    } elseif ($form_type === 'avatar') {
        // Handle file upload
        if (!empty($_FILES['profile_picture']['name'])) {
            $file = $_FILES['profile_picture'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png'])) {
                $filename = "user_" . $user_id . "." . $ext;
                $target = __DIR__ . "/uploads/" . $filename;
                move_uploaded_file($file['tmp_name'], $target);

                $stmt = $conn->prepare("UPDATE users SET profile_picture = :pic WHERE id = :id");
                $stmt->execute([':pic' => "uploads/" . $filename, ':id' => $user_id]);
                $_SESSION['profile_success'] = "Avatar updated successfully.";
            }
        }

    } elseif ($form_type === 'password') {
        // Password update
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $stmt = $conn->prepare("SELECT password FROM users WHERE id = :id");
        $stmt->execute([':id' => $user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || !password_verify($current, $row['password'])) {
            $_SESSION['profile_errors'] = ["Current password is incorrect."];
        } elseif ($new !== $confirm) {
            $_SESSION['profile_errors'] = ["New passwords do not match."];
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = :pw WHERE id = :id");
            $stmt->execute([':pw' => $hash, ':id' => $user_id]);
            $_SESSION['profile_success'] = "Password updated successfully.";
        }
    }
} catch (PDOException $e) {
    $_SESSION['profile_errors'] = ["Database error: " . $e->getMessage()];
}

header("Location: profile.php");
exit;
