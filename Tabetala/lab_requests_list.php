<?php
session_start();
require_once __DIR__ . "/config/config.php";

// ✅ Only allow logged-in users
if (!isset($_SESSION["user_id"])) {
    header("Location: auth/signin.php");
    exit();
}

$username = $_SESSION["username"];

// ✅ Database connection
$db = new Database();
$conn = $db->connect();

// ✅ Fetch lab requests
try {
    $stmt = $conn->query("SELECT * FROM lab_requests ORDER BY id DESC");
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error loading requests: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TabeTalá Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .nav-section { display: none; }
    .nav-section.active { display: block; }
    #searchResults { max-height: 200px; overflow-y: auto; }
  </style>
</head>
<body class="bg-gray-100 font-sans">

<!-- Top Header -->
<header class="w-full bg-white border-b border-gray-300 flex items-center justify-between px-6 py-4 shadow">
  <h1 class="text-2xl font-bold text-blue-900">TabeTalá</h1>
  
  <div class="flex items-center space-x-4">
    <!-- Notification Icon -->
    <div class="relative">
      <button id="notifButton" class="relative p-2 rounded-full hover:bg-gray-100">
        <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 00-5-5.917V4a1 1 0 10-2 0v1.083A6 6 0 006 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
        </svg>
        <span class="absolute top-0 right-0 inline-block w-2 h-2 bg-red-600 rounded-full"></span>
      </button>
      <div id="notifDropdown" class="origin-top-right absolute right-0 mt-2 w-72 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 hidden">
        <div class="py-2">
          <a href="notifications.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Go to Notifications Page</a>
          <div class="border-t my-1"></div>
          <p class="px-4 py-2 text-sm text-gray-600">⚠️ Lab 1: Keyboard missing</p>
          <p class="px-4 py-2 text-sm text-gray-600">📝 Request: Access for new staff</p>
          <p class="px-4 py-2 text-sm text-gray-600">⚠️ Lab 2: Mouse missing</p>
        </div>
      </div>
    </div>

    <!-- Search Bar -->
    <div class="relative">
      <input 
        type="text" 
        id="searchInput" 
        placeholder="Search..." 
        class="px-4 py-2 border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
        onkeyup="searchNavigation()" 
      />
      <div id="searchResults" class="absolute right-0 mt-1 w-56 bg-white border rounded-md shadow-lg hidden"></div>
    </div>

    <!-- Admin Dropdown -->
    <div class="relative">
      <button id="userMenuButton" type="button" class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
        <?php echo htmlspecialchars($username); ?> ▾
      </button>
      <div id="userDropdown" class="origin-top-right absolute right-0 mt-2 w-36 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 hidden">
        <div class="py-1">
          <a href="admin.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
          <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Log Out</a>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- Sidebar + Main Layout -->
<div class="flex min-h-screen">
  <!-- Sidebar -->
<!-- Sidebar -->
<aside class="w-64 bg-blue-900 text-white p-6 border-r border-blue-700">
  <ul class="space-y-2 text-lg font-medium">
    <li><a href="dashboard.php" class="nav-link block px-2 py-2 rounded hover:bg-blue-800">Dashboard</a></li>
    <li><a href="Occupancy.php" class="nav-link block px-2 py-2 rounded hover:bg-blue-800">Occupancy Monitoring</a></li>
    <li><a href="Access.php" class="nav-link block px-2 py-2 rounded hover:bg-blue-800">Access Control</a></li>
    <li><a href="Equipment.php" class="nav-link block px-2 py-2 rounded hover:bg-blue-800">Equipment Status</a></li>
   <li><a href="lab_requests_list.php" class="nav-link block px-2 py-2 rounded hover:bg-blue-800">Request Lab</a></li> <!-- ✅ Added -->
 <li><a href="Reports.php" class="nav-link block px-2 py-2 rounded hover:bg-blue-800">Reports</a></li>
    <li><a href="Settings.php" class="nav-link block px-2 py-2 rounded hover:bg-blue-800">Settings</a></li>
  </ul>
</aside>
  <!-- Main Content -->
  <main class="flex-1 p-8">
    <h2 class="text-3xl font-bold mb-6">📋 Lab Requests List</h2>

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
      <table class="min-w-full border-collapse">
        <thead>
          <tr class="bg-blue-600 text-white text-left">
            <th class="px-4 py-2 border">#</th>
            <th class="px-4 py-2 border">Full Name</th>
            <th class="px-4 py-2 border">Address</th>
            <th class="px-4 py-2 border">Organization</th>
            <th class="px-4 py-2 border">Lab</th>
            <th class="px-4 py-2 border">Date</th>
            <th class="px-4 py-2 border">Time</th>
            <th class="px-4 py-2 border">Reason</th>
            <th class="px-4 py-2 border">Submitted At</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($requests): ?>
            <?php foreach ($requests as $row): ?>
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-2 border text-center"><?= htmlspecialchars($row['id']) ?></td>
                <td class="px-4 py-2 border"><?= htmlspecialchars($row['fullname']) ?></td>
                <td class="px-4 py-2 border"><?= htmlspecialchars($row['address']) ?></td>
                <td class="px-4 py-2 border"><?= htmlspecialchars($row['organization']) ?></td>
                <td class="px-4 py-2 border"><?= htmlspecialchars($row['lab']) ?></td>
                <td class="px-4 py-2 border"><?= htmlspecialchars($row['date']) ?></td>
                <td class="px-4 py-2 border"><?= htmlspecialchars($row['time']) ?></td>
                <td class="px-4 py-2 border"><?= htmlspecialchars($row['reason']) ?></td>
                <td class="px-4 py-2 border"><?= $row['created_at'] ?? '-' ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="9" class="px-4 py-4 text-center text-gray-500">
                No lab requests found.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>

</body>
</html>
