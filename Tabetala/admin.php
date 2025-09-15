<?php
session_start();
require_once __DIR__ . '/config/config.php';

// --- Ensure $conn
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

// --- Flash messages
$success = $_SESSION['profile_success'] ?? null;
$errors  = $_SESSION['profile_errors'] ?? null;
unset($_SESSION['profile_success'], $_SESSION['profile_errors']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Profile Account</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<header class="w-full bg-white border-b px-6 py-4 shadow flex items-center justify-between">
  <h1 class="text-2xl font-bold text-blue-900">TabeTalá</h1>
  <div>
    <a href="logout.php" class="px-4 py-2 bg-red-500 text-white rounded">Logout</a>
  </div>
</header>

<div class="flex min-h-screen">
  <aside class="w-64 bg-blue-900 text-white p-6">
    <ul class="space-y-2">
      <li><a href="dashboard.php" class="block px-2 py-2 rounded hover:bg-blue-800">Dashboard</a></li>
      <li><a href="Occupancy.php" class="block px-2 py-2 rounded hover:bg-blue-800">Occupancy Monitoring</a></li>
      <li><a href="Access.php" class="block px-2 py-2 rounded hover:bg-blue-800">Access Control</a></li>
      <li><a href="Equipment.php" class="block px-2 py-2 rounded hover:bg-blue-800">Equipment Status</a></li>
      <li><a href="Reports.php" class="block px-2 py-2 rounded hover:bg-blue-800">Reports</a></li>
      <li><a href="Settings.php" class="block px-2 py-2 rounded hover:bg-blue-800">Settings</a></li>
    </ul>
  </aside>

  <main class="flex-1 p-10 space-y-10">
    <h2 class="text-3xl font-bold mb-6">Profile Account</h2>

    <?php if ($success): ?>
      <div class="p-4 bg-green-100 text-green-800 rounded"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
      <div class="p-4 bg-red-100 text-red-800 rounded">
        <ul class="list-disc pl-5">
          <?php foreach ($errors as $err): ?>
            <li><?= htmlspecialchars($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <!-- Profile Picture -->
    <div class="bg-white p-6 rounded shadow">
      <h3 class="text-xl font-bold mb-4">Profile Picture</h3>
      <div class="flex items-center gap-6">
        <img src="<?= htmlspecialchars($user['profile_pic'] ?? 'default.png') ?>" alt="Profile" class="w-24 h-24 rounded-full border">
        <form action="update_profile.php" method="POST" enctype="multipart/form-data" class="space-y-2">
          <input type="hidden" name="action" value="update_picture">
          <input type="file" name="profile_pic" class="block">
          <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Update Picture</button>
        </form>
      </div>
    </div>

    <!-- Profile Info -->
    <div class="bg-white p-6 rounded shadow">
      <h3 class="text-xl font-bold mb-4">Profile Information</h3>
      <form action="update_profile.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <input type="hidden" name="action" value="update_info">
        <input type="hidden" name="user_id" value="<?= (int)($user['id']) ?>">

        <div>
          <label class="block text-sm font-medium">First Name</label>
          <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" class="mt-1 w-full px-4 py-2 border rounded">
        </div>
        <div>
          <label class="block text-sm font-medium">Last Name</label>
          <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" class="mt-1 w-full px-4 py-2 border rounded">
        </div>
        <div>
          <label class="block text-sm font-medium">Username</label>
          <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" class="mt-1 w-full px-4 py-2 border rounded">
        </div>
        <div>
          <label class="block text-sm font-medium">Email</label>
          <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="mt-1 w-full px-4 py-2 border rounded">
        </div>
        <div>
          <label class="block text-sm font-medium">Role</label>
          <input type="text" value="<?= htmlspecialchars($user['role']) ?>" class="mt-1 w-full px-4 py-2 border rounded bg-gray-100" readonly>
        </div>
        <div class="col-span-2">
          <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded">Update Information</button>
        </div>
      </form>
    </div>

    <!-- Change Password -->
    <div class="bg-white p-6 rounded shadow">
      <h3 class="text-xl font-bold mb-4">Change Password</h3>
      <form action="update_profile.php" method="POST" class="space-y-4 max-w-md">
        <input type="hidden" name="action" value="change_password">
        <input type="hidden" name="user_id" value="<?= (int)($user['id']) ?>">

        <div>
          <label class="block text-sm font-medium">Current Password</label>
          <input type="password" name="current_password" class="mt-1 w-full px-4 py-2 border rounded">
        </div>
        <div>
          <label class="block text-sm font-medium">New Password</label>
          <input type="password" name="new_password" class="mt-1 w-full px-4 py-2 border rounded">
        </div>
        <div>
          <label class="block text-sm font-medium">Confirm New Password</label>
          <input type="password" name="confirm_password" class="mt-1 w-full px-4 py-2 border rounded">
        </div>
        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded">Change Password</button>
      </form>
    </div>

  </main>
</div>
</body>
</html>
