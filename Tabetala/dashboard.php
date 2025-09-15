<?php
session_start();

// ✅ Only allow logged-in users
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
  <title>TabeTalá Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .nav-section { display: none; }
    .nav-section.active { display: block; }
    #searchResults { max-height: 200px; overflow-y: auto; }
  </style>

  <!-- FullCalendar -->
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
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
  <aside class="w-64 bg-blue-900 text-white p-6 border-r border-blue-700">
    <ul class="space-y-2 text-lg font-medium">
      <li><a href="dashboard.php" class="nav-link block px-2 py-2 rounded hover:bg-blue-800">Dashboard</a></li>
      <li><a href="Occupancy.php" class="nav-link block px-2 py-2 rounded hover:bg-blue-800">Occupancy Monitoring</a></li>
      <li><a href="Access.php" class="nav-link block px-2 py-2 rounded hover:bg-blue-800">Access Control</a></li>
      <li><a href="Equipment.php" class="nav-link block px-2 py-2 rounded hover:bg-blue-800">Equipment Status</a></li>
      <li><a href="lab_requests_list.php" class="nav-link block px-2 py-2 rounded hover:bg-blue-800">Request Lab</a></li>
      <li><a href="Reports.php" class="nav-link block px-2 py-2 rounded hover:bg-blue-800">Reports</a></li>
      <li><a href="Settings.php" class="nav-link block px-2 py-2 rounded hover:bg-blue-800">Settings</a></li>
    </ul>
  </aside>

  <!-- Main Content -->
  <main class="flex-1 p-10">
    
    <h2 class="text-3xl font-bold mb-6">Dashboard Overview</h2>
    <div class="grid grid-cols-4 gap-6 mb-10">
      <div class="bg-white rounded-xl p-6 shadow text-center border">
        <p class="text-3xl font-bold">25</p>
        <p class="text-sm text-gray-500">Current Occupancy</p>
      </div>
      <div class="bg-white rounded-xl p-6 shadow text-center border">
        <p class="text-3xl font-bold">8</p>
        <p class="text-sm text-gray-500">Available Labs</p>
      </div>
      <div class="bg-white rounded-xl p-6 shadow text-center border">
        <p class="text-3xl font-bold">15</p>
        <p class="text-sm text-gray-500">Entries Today</p>
      </div>
      <div class="bg-white rounded-xl p-6 shadow text-center border">
        <p class="text-3xl font-bold text-red-500">2</p>
        <p class="text-sm text-gray-500">Unauthorized Access</p>
      </div>
    </div>

    <!-- Calendar Section (Right Side Only) -->
    <div class="flex justify-end">
      <div class="bg-white p-6 rounded-xl shadow border w-full max-w-lg">
        <h3 class="text-xl font-semibold mb-4">Calendar</h3>
        <div id="calendar"></div>
      </div>
    </div>
  </main>
</div>

<script>
  // 🔎 Global Search Index
  const searchIndex = [
    { name: "Dashboard", link: "dashboard.php" },
    { name: "Occupancy Monitoring", link: "Occupancy.php" },
    { name: "Access Control", link: "Access.php" },
    { name: "Equipment Status", link: "Equipment.php" },
    { name: "Reports", link: "Reports.php" },
    { name: "Settings", link: "Settings.php" },
    { name: "Notifications", link: "notifications.php" },
    { name: "Lab 1", link: "Occupancy.php#lab1" },
    { name: "Lab 2", link: "Occupancy.php#lab2" },
    { name: "Lab 3", link: "Occupancy.php#lab3" },
    { name: "Unauthorized Access", link: "Reports.php#unauthorized" },
    { name: "Available Labs", link: "Occupancy.php" },
    { name: "Current Occupancy", link: "dashboard.php" }
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
          .map(item => <a href="${item.link}" class="block px-4 py-2 hover:bg-gray-100">🔗 ${item.name}</a>)
          .join("");
        resultsBox.classList.remove("hidden");
      }
    }
  }

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

  // 📅 FullCalendar
  document.addEventListener('DOMContentLoaded', function () {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      events: [
        { title: 'Lab 1 Maintenance', start: '2025-09-15' },
        { title: 'Unauthorized Access Reported', start: '2025-09-12' },
        { title: 'System Check', start: '2025-09-18' }
      ]
    });
    calendar.render();
  });
</script>

</body>
</html>