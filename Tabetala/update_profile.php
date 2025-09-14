<?php
session_start();
require_once __DIR__ . '/config/config.php';

// --- DB Connection
$conn = null;
if (class_exists('Database')) {
    $db = new Database();
    $conn = $db->connect();
} elseif (isset($conn) && $conn instanceof PDO) {
    // already set
} elseif (isset($pdo) && $pdo instanceof PDO) {
    $conn = $pdo;
} else {
    die("DB connection not found.");
}

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/signin.php");
    exit;
}

$logged_id = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin.php");
    exit;
}

// Collect inputs
$user_id    = (int) ($_POST['user_id'] ?? 0);
$first_name = trim($_POST['first_name'] ?? '');
$last_name  = trim($_POST['last_name'] ?? '');
$username   = trim($_POST['username'] ?? '');
$email      = trim($_POST['email'] ?? '');
$current_pw = $_POST['current_password'] ?? '';
$new_pw     = $_POST['new_password'] ?? '';
$confirm_pw = $_POST['confirm_password'] ?? '';

$errors = [];

// Basic validation
if ($username === '') $errors[] = "Username is required.";
if ($email === '') $errors[] = "Email is required.";
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email address.";
if ($user_id !== $logged_id) $errors[] = "Invalid user ID.";

// Fetch current user
$stmt = $conn->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
$stmt->execute([":id" => $logged_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $errors[] = "User not found.";
}

// Handle profile picture upload
$profile_picture = $user['profile_picture'] ?? null;
if (!empty($_FILES['profile_picture']['name'])) {
    $file = $_FILES['profile_picture'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png'];

    if (!in_array($ext, $allowed)) {
        $errors[] = "Only JPG and PNG files allowed.";
    } elseif ($file['size'] > 2 * 1024 * 1024) {
        $errors[] = "File size must be under 2MB.";
    } else {
        $newName = "user_" . $logged_id . "_" . time() . "." . $ext;
        $uploadPath = __DIR__ . "/uploads/" . $newName;

        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            $profile_picture = "uploads/" . $newName;
        } else {
            $errors[] = "Failed to upload profile picture.";
        }
    }
}

// Handle password change
$password_hash = null;
if ($current_pw || $new_pw || $confirm_pw) {
    if (empty($current_pw)) {
        $errors[] = "Current password is required.";
    } elseif (!password_verify($current_pw, $user['password'])) {
        $errors[] = "Current password is incorrect.";
    } elseif (empty($new_pw) || empty($confirm_pw)) {
        $errors[] = "New and confirm password fields are required.";
    } elseif ($new_pw !== $confirm_pw) {
        $errors[] = "New password and confirm password do not match.";
    } else {
        $password_hash = password_hash($new_pw, PASSWORD_DEFAULT);
    }
}

// Stop if errors
if (!empty($errors)) {
    $_SESSION['profile_errors'] = $errors;
    header("Location: admin.php");
    exit;
}

// Check username uniqueness
$stmt = $conn->prepare("SELECT id FROM users WHERE username = :username AND id != :id LIMIT 1");
$stmt->execute([":username" => $username, ":id" => $logged_id]);
if ($stmt->fetch()) {
    $_SESSION['profile_errors'] = ["Username already taken."];
    header("Location: admin.php");
    exit;
}

// Build update query
$params = [
    ':first_name' => $first_name,
    ':last_name'  => $last_name,
    ':username'   => $username,
    ':email'      => $email,
    ':id'         => $logged_id
];

$sql = "UPDATE users SET first_name = :first_name, last_name = :last_name, username = :username, email = :email";

if ($profile_picture) {
    $sql .= ", profile_picture = :profile_picture";
    $params[':profile_picture'] = $profile_picture;
}

if ($password_hash) {
    $sql .= ", password = :password";
    $params[':password'] = $password_hash;
}

$sql .= " WHERE id = :id";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $_SESSION['profile_success'] = "Profile updated successfully.";
} catch (PDOException $e) {
    $_SESSION['profile_errors'] = ["Database error: " . $e->getMessage()];
}

header("Location: admin.php");
exit;
