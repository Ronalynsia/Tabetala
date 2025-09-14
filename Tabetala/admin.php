<?php
session_start();
require_once __DIR__ . '/config/config.php';

// --- Ensure $conn is available
$conn = null;
if (class_exists('Database')) {
    try {
        $db  = new Database();
        $conn = $db->connect();
    } catch (Exception $e) {
        die("Database connection error: " . $e->getMessage());
    }
} elseif (isset($conn) && $conn instanceof PDO) {
    // already connected
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

// --- Fetch user safely
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([":id" => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Query error: " . $e->getMessage());
}

if (!$user) {
    session_destroy();
    header("Location: auth/signin.php");
    exit;
}

// Flash messages from update_profile.php
$success = $_SESSION['profile_success'] ?? null;
$errors  = $_SESSION['profile_errors'] ?? null;
unset($_SESSION['profile_success'], $_SESSION['profile_errors']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Profile</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
  function showTab(tab) {
    document.querySelectorAll(".tab-pane").forEach(el => el.classList.add("hidden"));
    document.getElementById(tab).classList.remove("hidden");

    document.querySelectorAll(".tab-link").forEach(el =>
      el.classList.remove("border-b-2","border-blue-600","text-blue-600","font-semibold"));
    document.getElementById(tab + "-tab")
      .classList.add("border-b-2","border-blue-600","text-blue-600","font-semibold");
  }
</script>
</head>
<body class="bg-gray-100 font-sans">

<header class="w-full bg-white border-b px-6 py-4 shadow flex items-center justify-between">
  <h1 class="text-2xl font-bold text-blue-900">TabeTalá</h1>
  <div class="relative">
    <button id="userMenuButton"
            class="inline-flex items-center px-4 py-2 bg-white border rounded text-sm">
      <?= htmlspecialchars($user['username'] ?? '') ?> ▾
    </button>
    <div id="userDropdown"
         class="hidden origin-top-right absolute right-0 mt-2 w-40 bg-white border rounded shadow">
      <a href="admin.php" class="block px-4 py-2 hover:bg-gray-100">Profile</a>
      <a href="logout.php" class="block px-4 py-2 hover:bg-gray-100">Log Out</a>
    </div>
  </div>
</header>

<div class="flex">
  <!-- Sidebar -->
  <aside class="w-64 bg-blue-900 text-white min-h-screen p-6">
    <ul class="space-y-2">
      <li><a href="dashboard.php" class="block px-3 py-2 rounded hover:bg-blue-800">Dashboard</a></li>
      <li><a href="Occupancy.php" class="block px-3 py-2 rounded hover:bg-blue-800">Occupancy Monitoring</a></li>
      <li><a href="Access.php" class="block px-3 py-2 rounded hover:bg-blue-800">Access Control</a></li>
      <li><a href="Equipment.php" class="block px-3 py-2 rounded hover:bg-blue-800">Equipment Status</a></li>
      <li><a href="Reports.php" class="block px-3 py-2 rounded hover:bg-blue-800">Reports</a></li>
      <li><a href="Settings.php" class="block px-3 py-2 rounded hover:bg-blue-800">Settings</a></li>
    </ul>
  </aside>

  <!-- Main Profile Content -->
  <main class="flex-1 p-6">
    <h2 class="text-3xl font-bold mb-6">Admin Profile</h2>

    <?php if ($success): ?>
      <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
        <?= htmlspecialchars($success) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($errors) && is_array($errors)): ?>
      <div class="mb-4 p-4 bg-red-100 text-red-800 rounded">
        <ul class="list-disc pl-5">
          <?php foreach ($errors as $err): ?>
            <li><?= htmlspecialchars($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow flex">
      <!-- Left Profile Card -->
      <div class="w-1/3 border-r p-6 text-center">
        <img src="<?= htmlspecialchars($user['avatar'] ?? 'assets/avatar.png') ?>"
             class="mx-auto w-32 h-32 rounded-full border mb-4" alt="Avatar">
        <h2 class="font-bold text-lg uppercase">
          <?= htmlspecialchars(($user['last_name'] ?? '') . ", " . ($user['first_name'] ?? '')) ?>
        </h2>
        <p class="text-blue-600 font-semibold"><?= htmlspecialchars($user['username'] ?? '') ?></p>
        <p class="text-gray-600 uppercase text-sm mt-1"><?= htmlspecialchars($user['role'] ?? '') ?></p>
      </div>

      <!-- Right Profile Tabs -->
      <div class="w-2/3 p-6">
        <div class="border-b mb-6 flex justify-center space-x-10">
          <button id="user-info-tab" onclick="showTab('user-info')"
                  class="tab-link border-b-2 border-blue-600 text-blue-600 font-semibold pb-2">
            User Account Info
          </button>
          <button id="change-avatar-tab" onclick="showTab('change-avatar')"
                  class="tab-link pb-2">Change Avatar</button>
          <button id="change-password-tab" onclick="showTab('change-password')"
                  class="tab-link pb-2">Change Password</button>
        </div>

        <!-- User Info Tab -->
        <div id="user-info" class="tab-pane">
          <form action="update_profile.php" method="POST" class="space-y-4">
            <input type="hidden" name="user_id" value="<?= (int)($user['id'] ?? 0) ?>">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm">First Name</label>
                <input type="text" name="first_name"
                       value="<?= htmlspecialchars($user['first_name'] ?? '') ?>"
                       class="w-full border rounded px-3 py-2">
              </div>
              <div>
                <label class="block text-sm">Last Name</label>
                <input type="text" name="last_name"
                       value="<?= htmlspecialchars($user['last_name'] ?? '') ?>"
                       class="w-full border rounded px-3 py-2">
              </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm">Username</label>
                <input type="text" name="username"
                       value="<?= htmlspecialchars($user['username'] ?? '') ?>"
                       class="w-full border rounded px-3 py-2">
              </div>
              <div>
                <label class="block text-sm">Email</label>
                <input type="email" name="email"
                       value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                       class="w-full border rounded px-3 py-2">
              </div>
            </div>
            <div>
              <label class="block text-sm">Role</label>
              <input type="text"
                     value="<?= htmlspecialchars($user['role'] ?? '') ?>"
                     class="w-full border rounded px-3 py-2 bg-gray-100" readonly>
            </div>
            <div class="flex gap-3">
              <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded">Save Changes</button>
              <button type="reset" class="bg-gray-300 px-4 py-2 rounded">Cancel</button>
            </div>
          </form>
        </div>

        <!-- Change Avatar Tab -->
        <div id="change-avatar" class="tab-pane hidden">
          <form action="update_avatar.php" method="POST" enctype="multipart/form-data" class="space-y-4">
            <div>
              <label class="block text-sm">Upload New Avatar</label>
              <input type="file" name="avatar" accept="image/*"
                     class="w-full border rounded px-3 py-2">
            </div>
            <div class="flex gap-3">
              <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Upload</button>
              <button type="reset" class="bg-gray-300 px-4 py-2 rounded">Cancel</button>
            </div>
          </form>
        </div>

        <!-- Change Password Tab -->
        <div id="change-password" class="tab-pane hidden">
          <form action="update_password.php" method="POST" class="space-y-4">
            <div>
              <label class="block text-sm">Current Password</label>
              <input type="password" name="current_password"
                     class="w-full border rounded px-3 py-2">
            </div>
            <div>
              <label class="block text-sm">New Password</label>
              <input type="password" name="new_password"
                     class="w-full border rounded px-3 py-2">
            </div>
            <div>
              <label class="block text-sm">Confirm Password</label>
              <input type="password" name="confirm_password"
                     class="w-full border rounded px-3 py-2">
            </div>
            <div class="flex gap-3">
              <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Change Password</button>
              <button type="reset" class="bg-gray-300 px-4 py-2 rounded">Cancel</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </main>
</div>

<script>
document.getElementById('userMenuButton').addEventListener('click', function() {
  document.getElementById('userDropdown').classList.toggle('hidden');
});
</script>

</body>
</html>
