<?php
session_start();
require_once __DIR__ . '/config/config.php';

// --- DB connect
$conn = null;
if (class_exists('Database')) {
    $db = new Database();
    $conn = $db->connect();
} elseif (isset($conn) && $conn instanceof PDO) {
    // already set
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

// --- Flash
$success = $_SESSION['profile_success'] ?? null;
$errors  = $_SESSION['profile_errors'] ?? null;
unset($_SESSION['profile_success'], $_SESSION['profile_errors']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin Profile</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .tab-content { display:none; }
    .tab-content.active { display:block; }
    .tab-btn.active { border-bottom:2px solid #2563eb; color:#2563eb; }
  </style>
</head>
<body class="bg-gray-100 font-sans">

<header class="w-full bg-white border-b px-6 py-4 shadow flex items-center justify-between">
  <h1 class="text-2xl font-bold text-blue-900">TabeTalá</h1>
  <div class="flex items-center gap-4">
    <span class="font-semibold"><?= htmlspecialchars($user['username']) ?></span>
    <a href="logout.php" class="text-sm text-red-600">Log Out</a>
  </div>
</header>

<div class="max-w-4xl mx-auto mt-8 bg-white p-6 rounded shadow">
  <h2 class="text-xl font-bold mb-4">Profile Account</h2>

  <!-- Flash -->
  <?php if ($success): ?>
    <div class="mb-4 p-3 bg-green-100 text-green-700 rounded"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if (!empty($errors)): ?>
    <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
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
        <label class="block text-sm">First Name</label>
        <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" class="w-full border rounded px-3 py-2">
      </div>
      <div>
        <label class="block text-sm">Last Name</label>
        <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" class="w-full border rounded px-3 py-2">
      </div>
      <div>
        <label class="block text-sm">Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="w-full border rounded px-3 py-2">
      </div>
      <div>
        <label class="block text-sm">Username</label>
        <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" class="w-full border rounded px-3 py-2">
      </div>

      <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Save Changes</button>
    </form>
  </div>

  <!-- Change Avatar -->
  <div id="avatar" class="tab-content">
    <form action="update_profile.php" method="POST" enctype="multipart/form-data" class="space-y-4">
      <input type="hidden" name="action" value="avatar">
      <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">

      <div class="flex flex-col items-center">
        <img id="avatarPreview"
             src="<?= $user['profile_picture'] ? 'uploads/' . htmlspecialchars($user['profile_picture']) : 'assets/default-avatar.png' ?>"
             class="w-32 h-32 rounded-full object-cover border mb-2">
        <input type="file" name="profile_picture" accept="image/*" onchange="previewAvatar(this)">
      </div>
      <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Update Avatar</button>
    </form>
  </div>

  <!-- Change Password -->
  <div id="password" class="tab-content">
    <form action="update_profile.php" method="POST" class="space-y-4">
      <input type="hidden" name="action" value="password">
      <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">

      <div>
        <label class="block text-sm">Current Password</label>
        <input type="password" name="current_password" class="w-full border rounded px-3 py-2">
      </div>
      <div>
        <label class="block text-sm">New Password</label>
        <input type="password" name="new_password" class="w-full border rounded px-3 py-2">
      </div>
      <div>
        <label class="block text-sm">Confirm Password</label>
        <input type="password" name="confirm_password" class="w-full border rounded px-3 py-2">
      </div>
      <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Change Password</button>
    </form>
  </div>
</div>

<script>
  // Tabs
  document.querySelectorAll('.tab-btn').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
      document.querySelectorAll('.tab-content').forEach(tc=>tc.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById(btn.dataset.tab).classList.add('active');
    });
  });

  // Avatar preview
  function previewAvatar(input){
    if(input.files && input.files[0]){<?php
session_start();
require_once __DIR__ . '/config/config.php';

// --- DB connect
$conn = null;
if (class_exists('Database')) {
    $db = new Database();
    $conn = $db->connect();
} elseif (isset($conn) && $conn instanceof PDO) {
    // already set
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

// --- Flash
$success = $_SESSION['profile_success'] ?? null;
$errors  = $_SESSION['profile_errors'] ?? null;
unset($_SESSION['profile_success'], $_SESSION['profile_errors']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin Profile</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .tab-content { display:none; }
    .tab-content.active { display:block; }
    .tab-btn.active { border-bottom:2px solid #2563eb; color:#2563eb; }
  </style>
</head>
<body class="bg-gray-100 font-sans">

<header class="w-full bg-white border-b px-6 py-4 shadow flex items-center justify-between">
  <h1 class="text-2xl font-bold text-blue-900">TabeTalá</h1>
  <div class="flex items-center gap-4">
    <span class="font-semibold"><?= htmlspecialchars($user['username']) ?></span>
    <a href="logout.php" class="text-sm text-red-600">Log Out</a>
  </div>
</header>

<div class="max-w-4xl mx-auto mt-8 bg-white p-6 rounded shadow">
  <h2 class="text-xl font-bold mb-4">Profile Account</h2>

  <!-- Flash -->
  <?php if ($success): ?>
    <div class="mb-4 p-3 bg-green-100 text-green-700 rounded"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if (!empty($errors)): ?>
    <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
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
        <label class="block text-sm">First Name</label>
        <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" class="w-full border rounded px-3 py-2">
      </div>
      <div>
        <label class="block text-sm">Last Name</label>
        <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" class="w-full border rounded px-3 py-2">
      </div>
      <div>
        <label class="block text-sm">Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="w-full border rounded px-3 py-2">
      </div>
      <div>
        <label class="block text-sm">Username</label>
        <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" class="w-full border rounded px-3 py-2">
      </div>

      <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Save Changes</button>
    </form>
  </div>

  <!-- Change Avatar -->
  <div id="avatar" class="tab-content">
    <form action="update_profile.php" method="POST" enctype="multipart/form-data" class="space-y-4">
      <input type="hidden" name="action" value="avatar">
      <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">

      <div class="flex flex-col items-center">
        <img id="avatarPreview"
             src="<?= $user['profile_picture'] ? 'uploads/' . htmlspecialchars($user['profile_picture']) : 'assets/default-avatar.png' ?>"
             class="w-32 h-32 rounded-full object-cover border mb-2">
        <input type="file" name="profile_picture" accept="image/*" onchange="previewAvatar(this)">
      </div>
      <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Update Avatar</button>
    </form>
  </div>

  <!-- Change Password -->
  <div id="password" class="tab-content">
    <form action="update_profile.php" method="POST" class="space-y-4">
      <input type="hidden" name="action" value="password">
      <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">

      <div>
        <label class="block text-sm">Current Password</label>
        <input type="password" name="current_password" class="w-full border rounded px-3 py-2">
      </div>
      <div>
        <label class="block text-sm">New Password</label>
        <input type="password" name="new_password" class="w-full border rounded px-3 py-2">
      </div>
      <div>
        <label class="block text-sm">Confirm Password</label>
        <input type="password" name="confirm_password" class="w-full border rounded px-3 py-2">
      </div>
      <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Change Password</button>
    </form>
  </div>
</div>

<script>
  // Tabs
  document.querySelectorAll('.tab-btn').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
      document.querySelectorAll('.tab-content').forEach(tc=>tc.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById(btn.dataset.tab).classList.add('active');
    });
  });

  // Avatar preview
  function previewAvatar(input){
    if(input.files && input.files[0]){
      const reader = new FileReader();
      reader.onload = e=>{
        document.getElementById('avatarPreview').src = e.target.result;
      };
      reader.readAsDataURL(input.files[0]);
    }
  }
</script>
</body>
</html>

      const reader = new FileReader();
      reader.onload = e=>{
        document.getElementById('avatarPreview').src = e.target.result;
      };
      reader.readAsDataURL(input.files[0]);
    }
  }
</script>
</body>
</html>
