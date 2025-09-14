<?php
session_start();

// ✅ Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: auth/signin.php");
    exit();
}

$username = $_SESSION["username"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Access Control</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">

  <!-- 🔹 Header -->
  <header class="w-full bg-white border-b border-gray-300 flex items-center justify-between px-6 py-4 shadow">
    <h1 class="text-2xl font-bold text-blue-900">TabeTalá</h1>

    <div class="flex items-center gap-4">
      <!-- Notifications -->
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
          </div>
        </div>
      </div>

      <!-- Search -->
      <div class="relative">
        <input type="text" id="searchInput" placeholder="Search..."
          class="px-4 py-2 border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
          onkeyup="searchNavigation()" />
        <div id="searchResults" class="absolute right-0 mt-1 w-56 bg-white border rounded-md shadow-lg hidden"></div>
      </div>

      <!-- User Dropdown -->
      <div class="relative inline-block text-left">
        <button id="userMenuButton" type="button"
          class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
          <?php echo htmlspecialchars($username); ?> ▾
        </button>
        <div id="userDropdown"
          class="origin-top-right absolute right-0 mt-2 w-36 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 hidden">
          <div class="py-1">
            <a href="admin.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
            <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Log Out</a>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- 🔹 Layout -->
  <div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-blue-900 text-white p-6 border-r border-blue-700">
      <ul class="space-y-2 text-lg font-medium">
        <li><a href="dashboard.php" class="block px-2 py-2 rounded hover:bg-blue-800">Dashboard</a></li>
        <li><a href="Occupancy.php" class="block px-2 py-2 rounded hover:bg-blue-800">Occupancy Monitoring</a></li>
        <li><a href="Access.php" class="block px-2 py-2 rounded bg-blue-800">Access Control</a></li>
        <li><a href="Equipment.php" class="block px-2 py-2 rounded hover:bg-blue-800">Equipment Status</a></li>
        <li><a href="lab_requests_list.php" class="block px-2 py-2 rounded hover:bg-blue-800">Lab Requests</a></li>
        <li><a href="Reports.php" class="block px-2 py-2 rounded hover:bg-blue-800">Reports</a></li>
        <li><a href="Settings.php" class="block px-2 py-2 rounded hover:bg-blue-800">Settings</a></li>
      </ul>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-8">
      <h2 class="text-2xl font-bold mb-6 border-b pb-2">User Access List</h2>

      <!-- Access Logs Table -->
      <div class="overflow-x-auto bg-white shadow rounded-lg p-4 mb-8">
        <table class="w-full text-left border-collapse">
          <thead class="bg-gray-200">
            <tr>
              <th class="border px-4 py-2">Name</th>
              <th class="border px-4 py-2">Role</th>
              <th class="border px-4 py-2">Lab</th>
              <th class="border px-4 py-2">Date</th>
              <th class="border px-4 py-2">In Time</th>
              <th class="border px-4 py-2">Out Time</th>
              <th class="border px-4 py-2">Access Method</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td colspan="7" class="text-center py-6 text-gray-500">
                No access records yet.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Register New Access -->
      <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-xl font-semibold mb-4">Register New Biometric Access</h3>
        <form class="space-y-4">
          <div>
            <label class="block font-medium">Full Name</label>
            <input type="text" class="w-full border px-4 py-2 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400">
          </div>
          <div>
            <label class="block font-medium">Role</label>
            <select class="w-full border px-4 py-2 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400">
              <option>Admin</option>
              <option>Staff</option>
              <option>Professor</option>
            </select>
          </div>
          <div>
            <label class="block font-medium">Register Fingerprint</label>
            <button type="button" class="w-full bg-gray-200 px-4 py-2 rounded-md hover:bg-gray-300">
              Scan Fingerprint
            </button>
          </div>
          <div>
            <label class="block font-medium">PIN / Password</label>
            <input type="password" class="w-full border px-4 py-2 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400">
          </div>
          <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700">
            Register Access
          </button>
        </form>
      </div>
    </main>
  </div>

<script>
  // 🔎 Search Index
  const searchIndex = [
    { name: "Dashboard", link: "dashboard.php" },
    { name: "Occupancy Monitoring", link: "Occupancy.php" },
    { name: "Access Control", link: "Access.php" },
    { name: "Equipment Status", link: "Equipment.php" },
    { name: "Lab Requests", link: "lab_requests_list.php" },
    { name: "Reports", link: "Reports.php" },
    { name: "Settings", link: "Settings.php" }
  ];

  function searchNavigation() {
    const input = document.getElementById("searchInput").value.toLowerCase();
    const resultsBox = document.getElementById("searchResults");
    resultsBox.innerHTML = "";
    resultsBox.classList.add("hidden");

    if (input) {
      let matches = searchIndex.filter(item =>
        item.name.toLowerCase().includes(input)
      );

      if (matches.length > 0) {
        resultsBox.innerHTML = matches
          .map(item => `<a href="${item.link}" class="block px-4 py-2 hover:bg-gray-100">🔗 ${item.name}</a>`)
          .join("");
        resultsBox.classList.remove("hidden");
      }
    }
  }

  // Dropdowns
  document.getElementById("userMenuButton").addEventListener("click", () => {
    document.getElementById("userDropdown").classList.toggle("hidden");
  });

  document.getElementById("notifButton").addEventListener("click", () => {
    document.getElementById("notifDropdown").classList.toggle("hidden");
  });

  window.addEventListener("click", function(e) {
    if (!document.getElementById("userMenuButton").contains(e.target)) {
      document.getElementById("userDropdown").classList.add("hidden");
    }
    if (!document.getElementById("notifButton").contains(e.target)) {
      document.getElementById("notifDropdown").classList.add("hidden");
    }
    if (!document.getElementById("searchInput").contains(e.target)) {
      document.getElementById("searchResults").classList.add("hidden");
    }
  });
</script>

</body>
</html>
