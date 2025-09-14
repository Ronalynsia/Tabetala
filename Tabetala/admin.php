<!-- profile.php -->
<?php
session_start();
require_once __DIR__ . '/config/config.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: auth/signin.php");
  exit;
}

$user_id = (int) $_SESSION['user_id'];
$db = new Database();
$conn = $db->connect();

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
$stmt->execute([":id" => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Profile Settings</title>
  <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-6">
  <div class="max-w-3xl mx-auto bg-white rounded-lg shadow p-6" x-data="{ tab: 'info' }">
    <!-- Tabs -->
    <div class="flex border-b mb-6">
      <button @click="tab = 'info'" :class="tab==='info' ? 'border-b-2 border-teal-500 text-teal-600' : 'text-gray-600'" class="flex-1 py-2">User Account Info</button>
      <button @click="tab = 'avatar'" :class="tab==='avatar' ? 'border-b-2 border-teal-500 text-teal-600' : 'text-gray-600'" class="flex-1 py-2">Change Avatar</button>
      <button @click="tab = 'password'" :class="tab==='password' ? 'border-b-2 border-teal-500 text-teal-600' : 'text-gray-600'" class="flex-1 py-2">Change Password</button>
    </div>

    <!-- User Info -->
    <div x-show="tab === 'info'">
      <form action="update_profile.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input type="hidden" name="form_type" value="info">
        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">

        <div>
          <label class="block text-sm font-medium">First Name</label>
          <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" class="w-full border rounded p-2">
        </div>

        <div>
          <label class="block text-sm font-medium">Last Name</label>
          <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" class="w-full border rounded p-2">
        </div>

        <div>
          <label class="block text-sm font-medium">Username</label>
          <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" class="w-full border rounded p-2">
        </div>

        <div>
          <label class="block text-sm font-medium">Email</label>
          <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="w-full border rounded p-2">
        </div>

        <div class="col-span-2 mt-4">
          <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Update Info</button>
        </div>
      </form>
    </div>

    <!-- Change Avatar -->
    <div x-show="tab === 'avatar'" x-cloak>
      <form action="update_profile.php" method="POST" enctype="multipart/form-data" class="flex flex-col items-center gap-4">
        <input type="hidden" name="form_type" value="avatar">
        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">

        <img src="<?= htmlspecialchars($user['profile_picture'] ?? 'uploads/default.png') ?>" alt="Avatar" class="w-32 h-32 rounded-full object-cover border">
        <input type="file" name="profile_picture" accept="image/*" class="text-sm">

        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Update Avatar</button>
      </form>
    </div>

    <!-- Change Password -->
    <div x-show="tab === 'password'" x-cloak>
      <form action="update_profile.php" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input type="hidden" name="form_type" value="password">
        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">

        <div>
          <label class="block text-sm font-medium">Current Password</label>
          <input type="password" name="current_password" class="w-full border rounded p-2">
        </div>

        <div>
          <label class="block text-sm font-medium">New Password</label>
          <input type="password" name="new_password" class="w-full border rounded p-2">
        </div>

        <div>
          <label class="block text-sm font-medium">Confirm Password</label>
          <input type="password" name="confirm_password" class="w-full border rounded p-2">
        </div>

        <div class="col-span-3 mt-4">
          <button type="submit" class="bg-yellow-600 text-white px-4 py-2 rounded">Update Password</button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
