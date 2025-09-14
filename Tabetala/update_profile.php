<?php
session_start();
require_once __DIR__ . '/config/config.php';

// --- Ensure $conn is available
$conn = null;
if (class_exists('Database')) {
    $db = new Database();
    $conn = $db->connect();
} elseif (isset($conn) && $conn instanceof PDO) {
    // already available
} elseif (isset($pdo) && $pdo instanceof PDO) {
    $conn = $pdo;
} else {
    die("Database connection not found. Check config.php");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int) $_POST['user_id'];
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $username   = trim($_POST['username']);
    $email      = trim($_POST['email']);
    $password   = $_POST['password'];

    $errors = [];
    $profile_picture = null;

    // --- Handle Image Upload
    if (!empty($_FILES['profile_picture']['name'])) {
        $targetDir = __DIR__ . "/uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = time() . "_" . basename($_FILES["profile_picture"]["name"]);
        $targetFile = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        // Validate file type
        $allowed = ["jpg","jpeg","png","gif"];
        if (in_array($fileType, $allowed)) {
            if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $targetFile)) {
                $profile_picture = $fileName;
            } else {
                $errors[] = "Error uploading profile picture.";
            }
        } else {
            $errors[] = "Only JPG, JPEG, PNG, and GIF files are allowed.";
        }
    }

    if (empty($errors)) {
        // --- Build SQL dynamically
        $sql = "UPDATE users SET first_name = :first_name, last_name = :last_name, username = :username, email = :email";
        $params = [
            ":first_name" => $first_name,
            ":last_name"  => $last_name,
            ":username"   => $username,
            ":email"      => $email,
            ":id"         => $user_id
        ];

        if (!empty($password)) {
            $sql .= ", password = :password";
            $params[":password"] = password_hash($password, PASSWORD_DEFAULT);
        }

        if ($profile_picture) {
            $sql .= ", profile_picture = :profile_picture";
            $params[":profile_picture"] = $profile_picture;
        }

        $sql .= " WHERE id = :id";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        $_SESSION['profile_success'] = "Profile updated successfully!";
    } else {
        $_SESSION['profile_errors'] = $errors;
    }

    header("Location: admin.php");
    exit;
}
?>
