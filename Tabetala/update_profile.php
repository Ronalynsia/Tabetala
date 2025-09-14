<?php
session_start();
require_once __DIR__ . '/config/config.php';

// --- Get $conn (same approach as admin.php)
$conn = null;
if (class_exists('Database')) {
    $db = new Database();
    $conn = $db->connect();
} elseif (isset($conn) && $conn instanceof PDO) {
    // already set by config
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

// Collect and validate
$user_id   = (int) ($_POST['user_id'] ?? 0);
$first_name = trim($_POST['first_name'] ?? '');
$last_name  = trim($_POST['last_name'] ?? '');
$username   = trim($_POST['username'] ?? '');
$email      = trim($_POST['email'] ?? '');
$password   = $_POST['password'] ?? '';

$errors = [];

// Basic validation
if ($username === '') $errors[] = "Username is required.";
if ($email === '') $errors[] = "Email is required.";
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email address.";

// Ensure the user can only update their own profile
if ($user_id !== $logged_id) {
    $errors[] = "Invalid user ID.";
}

if (!empty($errors)) {
    $_SESSION['profile_errors'] = $errors;
    header("Location: admin.php");
    exit;
}

// Check username uniqueness (exclude current user)
$stmt = $conn->prepare("SELECT id FROM users WHERE username = :username AND id != :id LIMIT 1");
$stmt->execute([":username" => $username, ":id" => $logged_id]);
if ($stmt->fetch()) {
    $_SESSION['profile_errors'] = ["Username already taken."];
    header("Location: admin.php");
    exit;
}

// Build update
$params = [
    ':first_name' => $first_name,
    ':last_name'  => $last_name,
    ':username'   => $username,
    ':email'      => $email,
    ':id'         => $logged_id
];

$sql = "UPDATE users SET first_name = :first_name, last_name = :last_name, username = :username, email = :email";

// If password provided, hash and update
if (!empty($password)) {
    // NOTE: we hash the password. If your signin currently compares plaintext, you must update signin.php to use password_verify().
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
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
