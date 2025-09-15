<?php
session_start();
require_once __DIR__ . '/config/config.php'; // adjust if needed

// --- Ensure $conn is available (supports two patterns: Database class or $conn from config)
$conn = null;
if (class_exists('Database')) {
    try {
        $db = new Database();
        $conn = $db->connect();
    } catch (Exception $e) {
        die("Database connection error: " . $e->getMessage());
    }
} elseif (isset($conn) && $conn instanceof PDO) {
    // config.php already gave us $conn
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

// Grab any flash messages set by update_profile.php
$success = $_SESSION['profile_success'] ?? null;
$errors  = $_SESSION['profile_errors'] ?? null;
unset($_SESSION['profile_success'], $_SESSION['profile_errors']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Admin Profile</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>#searchResults { max-height: 200px; overflow-y: auto; }</style>
</head>
<body class="bg-gray-100 font-sans">

<header class="w-full bg-white border-b px-6 py-4 shadow flex items-center justify-between">
  <h1 class="text-2xl font-bold text-blue-900">TabeTalá</h1>
  <div class="flex items-center gap-4">
    <div class="relative">
      <button id="userMenuButton" class="inline-flex items-center px-4 py-2 bg-white border rounded text-sm">
        <?= htmlspecialchars($user['username'] ?? '') ?> ▾
      </button>
      <div id="userDropdown" class="hidden origin-top-right absolute right-0 mt-2 w-40 bg-white border rounded shadow">
        <a href="admin.php" class="block px-4 py-2 hover:bg-gray-100">Profile</a>
        <a href="logout.php" class="block px-4 py-2 hover:bg-gray-100">Log Out</a>
      </div>
    </div>
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

  <main class="flex-1 p-10">
    <h2 class="text-3xl font-bold mb-6">Admin Profile</h2>

    <?php if ($success): ?>
      <div class="mb-4 p-4 bg-green-100 text-green-800 rounded"><?= htmlspecialchars($success) ?></div>
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

    <div class="bg-white p-8 rounded-xl shadow-md">
      <form action="update_profile.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Hidden id for update -->
        <input type="hidden" name="user_id" value="<?= (int)($user['id'] ?? 0) ?>">

        <div>
          <label class="block text-sm font-medium text-gray-700">First Name</label>
          <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" class="mt-1 w-full px-4 py-2 border rounded">
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Last Name</label>
          <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" class="mt-1 w-full px-4 py-2 border rounded">
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Username</label>
          <input type="text" name="username" value="<?= htmlspecialchars($user['username'] ?? '') ?>" class="mt-1 w-full px-4 py-2 border rounded">
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Email</label>
          <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" class="mt-1 w-full px-4 py-2 border rounded">
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Role</label>
          <input type="text" value="<?= htmlspecialchars($user['role'] ?? '') ?>" class="mt-1 w-full px-4 py-2 border rounded bg-gray-100" readonly>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">New Password</label>
          <input type="password" name="password" placeholder="Leave blank to keep current" class="mt-1 w-full px-4 py-2 border rounded">
        </div>

        <div class="col-span-2">
          <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded">Update Profile</button>
        </div>
      </form>
    </div>
  </main>
</div>

<script>
  document.getElementById('userMenuButton').addEventListener('click', function(e) {
    document.getElementById('userDropdown').classList.toggle('hidden');
  });
</script>

</body>
</html>
