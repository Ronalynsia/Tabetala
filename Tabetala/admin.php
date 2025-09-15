<?php
session_start();
require_once __DIR__ . '/config/config.php';

// --- Ensure DB connection
$conn = null;
if (class_exists('Database')) {
    $db = new Database();
    $conn = $db->connect();
} elseif (isset($conn) && $conn instanceof PDO) {
    // already from config
} elseif (isset($pdo) && $pdo instanceof PDO) {
    $conn = $pdo;
} else {
    die("Database connection not found. Check config.php");
}

// --- Require login
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/signin.php");
    exit;
}
$user_id = (int) $_SESSION['user_id'];

// --- Fetch user
$stmt = $conn->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
$stmt->execute([":id" => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: auth/signin.php");
    exit;
}

// Grab flash messages
$success = $_SESSION['profile_success'] ?? null;
$errors  = $_SESSION['profile_errors'] ?? null;
unset($_SESSION['profile_success'], $_SESSION['profile_errors']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Profile Account</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .tab-content { display: none; }
    .tab-content.active { display: block; }
    .tab-btn.active { border-bottom: 2px solid #06b6d4; color:#06b6d4; }
  </style>
</head>
<body class="bg-gray-100 p-6 font-sans">

<div class="bg-white rounded shadow p-6 max-w-3xl mx-auto">
  <h2 class="text-xl font-bold mb-4">PROFILE ACCOUNT</h2>

  <!-- Messages -->
  <?php if ($success): ?>
    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if (!empty($errors) && is_array($errors)): ?>
    <div class="mb-4 p-3 bg-red-100 text-red-800 rounded">
      <ul class="list-disc pl-5">
        <?php foreach ($errors as $err): ?>
          <li><?= htmlspecialchars($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <!-- Tabs -->
  <div class="flex border-b mb-6">
    <button class="tab-btn px-4 py-2 active" data-tab="info">User Info</button>
    <button class="tab-btn px-4 py-2" data-tab="avatar">Change Avatar</button>
    <button class="tab-btn px-4 py-2" data-tab="password">Change Password</button>
  </div>

  <!-- User Info -->
  <div id="info" class="tab-content active">
    <form action="update_profile.php" method="POST" class="space-y-4">
      <input type="hidden" name="action" value="info">
      <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">

      <div>
        <label class="block text-sm font-medium">Full Name</label>
        <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" class="mt-1 w-full border px-3 py-2 rounded">
      </div>

      <div>
        <label class="block text-sm font-medium">Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" class="mt-1 w-full border px-3 py-2 rounded">
      </div>

      <div>
        <label class="block text-sm font-medium">Mobile</label>
        <input type="text" name="mobile" value="<?= htmlspecialchars($user['mobile'] ?? '') ?>" class="mt-1 w-full border px-3 py-2 rounded">
      </div>

      <button type="submit" class="bg-teal-500 text-white px-4 py-2 rounded">Save</button>
    </form>
  </div>

  <!-- Avatar -->
  <div id="avatar" class="tab-content">
    <form action="update_profile.php" method="POST" enctype="multipart/form-data" class="space-y-4 text-center">
      <input type="hidden" name="action" value="avatar">
      <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">

      <div>
        <?php if (!empty($user['profile_picture'])): ?>
          <img id="avatarPreview" src="uploads/<?= htmlspecialchars($user['profile_picture']) ?>" class="mx-auto w-32 h-32 rounded-full object-cover border">
        <?php else: ?>
          <img id="avatarPreview" src="assets/default-avatar.png" class="mx-auto w-32 h-32 rounded-full object-cover border">
        <?php endif; ?>
      </div>
      <input type="file" name="profile_picture" accept="image/*" onchange="previewAvatar(this)">
      <button type="submit" class="bg-teal-500 text-white px-4 py-2 rounded">Upload</button>
    </form>
  </div>

  <!-- Password -->
  <div id="password" class="tab-content">
    <form action="update_profile.php" method="POST" class="space-y-4">
      <input type="hidden" name="action" value="password">
      <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">

      <div>
        <label class="block text-sm font-medium">Current Password</label>
        <input type="password" name="current_password" class="mt-1 w-full border px-3 py-2 rounded">
      </div>
      <div>
        <label class="block text-sm font-medium">New Password</label>
        <input type="password" name="new_password" class="mt-1 w-full border px-3 py-2 rounded">
      </div>
      <div>
        <label class="block text-sm font-medium">Confirm New Password</label>
        <input type="password" name="confirm_password" class="mt-1 w-full border px-3 py-2 rounded">
      </div>
      <button type="submit" class="bg-teal-500 text-white px-4 py-2 rounded">Change Password</button>
    </form>
  </div>
</div>

<script>
  document.querySelectorAll('.tab-btn').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
      document.querySelectorAll('.tab-content').forEach(tc=>tc.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById(btn.dataset.tab).classList.add('active');
    });
  });

  function previewAvatar(input){
    const file = input.files[0];
    if(file){
      const reader = new FileReader();
      reader.onload = e=>{
        document.getElementById('avatarPreview').src = e.target.result;
      }
      reader.readAsDataURL(file);
    }
  }
</script>
</body>
</html>
