<?php
session_start();
require_once __DIR__ . '/config/config.php';

// ✅ DB connect
$conn = (new Database())->connect();
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header("Location: auth/signin.php");
    exit;
}

try {
    // Update name, email, mobile
    if (!empty($_POST['name']) && !empty($_POST['email'])) {
        $stmt = $conn->prepare("UPDATE users SET name=:name, email=:email, mobile=:mobile WHERE id=:id");
        $stmt->execute([
            ":name"   => $_POST['name'],
            ":email"  => $_POST['email'],
            ":mobile" => $_POST['mobile'],
            ":id"     => $user_id
        ]);
    }

    // Update avatar
    if (!empty($_FILES['avatar']['name'])) {
        $file = $_FILES['avatar'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = "avatar_".$user_id.".".$ext;
        move_uploaded_file($file['tmp_name'], "uploads/".$filename);

        $stmt = $conn->prepare("UPDATE users SET avatar=:avatar WHERE id=:id");
        $stmt->execute([":avatar"=>$filename, ":id"=>$user_id]);
    }

    // Change password
    if (!empty($_POST['current_password']) && !empty($_POST['new_password'])) {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id=:id");
        $stmt->execute([":id"=>$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($_POST['current_password'], $user['password'])) {
            if ($_POST['new_password'] === $_POST['confirm_password']) {
                $new_pass = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE users SET password=:password WHERE id=:id");
                $stmt->execute([":password"=>$new_pass, ":id"=>$user_id]);
            } else {
                $_SESSION['profile_errors'] = "Passwords do not match!";
                header("Location: profile.php");
                exit;
            }
        } else {
            $_SESSION['profile_errors'] = "Current password is incorrect!";
            header("Location: profile.php");
            exit;
        }
    }

    $_SESSION['profile_success'] = "Profile updated successfully!";
} catch (Exception $e) {
    $_SESSION['profile_errors'] = "Error: ".$e->getMessage();
}

header("Location: profile.php");
exit;
