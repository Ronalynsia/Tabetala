<?php
session_start();
require_once __DIR__ . '/config/config.php';

// ✅ DB connect
$conn = null;
if (class_exists('Database')) {
    $db = new Database();
    $conn = $db->connect();
} elseif (isset($conn) && $conn instanceof PDO) {
    // already connected
} elseif (isset($pdo) && $pdo instanceof PDO) {
    $conn = $pdo;
} else {
    die("Database connection not found.");
}

// ✅ Require login
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/signin.php");
    exit;
}
$user_id = (int) $_SESSION['user_id'];

// ✅ Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id=:id LIMIT 1");
$stmt->execute([":id"=>$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: auth/signin.php");
    exit;
}

// ✅ Flash messages
$success = $_SESSION['profile_success'] ?? null;
$errors  = $_SESSION['profile_errors'] ?? null;
unset($_SESSION['profile_success'], $_SESSION['profile_errors']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Profile</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <style>
    .profile-section { background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 25px; box-shadow: 0 2px 6px rgba(0,0,0,0.1);}
    .profile-pic { width: 120px; height: 120px; object-fit: cover; border-radius: 50%; border: 3px solid #ddd;}
    .section-title { font-weight: bold; margin-bottom: 15px; color: #333;}
  </style>
</head>
<body class="bg-light">
<div class="container py-5">
  <h3 class="mb-4">👤 Profile Account</h3>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if ($errors): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errors) ?></div>
  <?php endif; ?>

  <form action="update_profile.php" method="POST" enctype="multipart/form-data">

    <!-- Profile Picture -->
    <div class="profile-section text-center">
      <div class="section-title">Profile Picture</div>
      <img src="<?= $user['avatar'] ? 'uploads/'.$user['avatar'] : 'uploads/default.png' ?>" 
           alt="Profile" class="profile-pic mb-3">
      <div>
        <input type="file" name="avatar" class="form-control w-50 mx-auto">
      </div>
    </div>

    <!-- User Info -->
    <div class="profile-section">
      <div class="section-title">User Information</div>
      <div class="row g-3">
        <div class="col-md-6">
          <label>Username</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled>
        </div>
        <div class="col-md-6">
          <label>Complete Name</label>
          <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($user['name']) ?>">
        </div>
        <div class="col-md-6">
          <label>Email</label>
          <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user['email']) ?>">
        </div>
        <div class="col-md-6">
          <label>Mobile Number</label>
          <input type="text" class="form-control" name="mobile" value="<?= htmlspecialchars($user['mobile']) ?>">
        </div>
        <div class="col-md-6">
          <label>Academic Program</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($user['program']) ?>" disabled>
        </div>
        <div class="col-md-6">
          <label>Group</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($user['role']) ?>" disabled>
        </div>
      </div>
    </div>

    <!-- Change Password -->
    <div class="profile-section">
      <div class="section-title">Change Password</div>
      <div class="row g-3">
        <div class="col-md-4">
          <label>Current Password</label>
          <input type="password" class="form-control" name="current_password">
        </div>
        <div class="col-md-4">
          <label>New Password</label>
          <input type="password" class="form-control" name="new_password">
        </div>
        <div class="col-md-4">
          <label>Re-type New Password</label>
          <input type="password" class="form-control" name="confirm_password">
        </div>
      </div>
    </div>

    <div class="text-end">
      <button type="submit" class="btn btn-success">💾 Save Changes</button>
      <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
    </div>
  </form>
</div>
</body>
</html>
