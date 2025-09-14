<?php
session_start();
require_once __DIR__ . "/config/config.php";

// ✅ Only allow logged-in users
if (!isset($_SESSION["user_id"])) {
    header("Location: auth/signin.php");
    exit();
}
$username = $_SESSION["username"];

// Connect to database
$db = new Database();
$conn = $db->connect();

$columns = [];
$data = [];

// Handle report generation request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['reportType'] ?? '';
    $date = $_POST['filterDate'] ?? '';

    if ($type === "missing") {
        $columns = ["Lab", "Equipment", "Quantity", "Status"];
        $sql = "SELECT lab, name AS equipment, quantity, status 
                FROM equipment 
                WHERE status = 'Missing'";
        $stmt = $conn->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($type === "attendance") {
        $columns = ["Name", "Role", "Date", "Status"];
        $sql = "SELECT name, role, DATE(date) AS date, status FROM attendance_logs";
        if (!empty($date)) {
            $sql .= " WHERE DATE(date) = :date";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":date", $date);
            $stmt->execute();
        } else {
            $stmt = $conn->query($sql);
        }
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($type === "access") {
        $columns = ["Name", "Role", "Access Method", "Status"];
        $sql = "SELECT name, role, access_method, status 
                FROM access_logs 
                WHERE status LIKE '%Unauthorized%'";
        $stmt = $conn->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Reports - TabeTalá</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">
  <style>
    #searchResults { max-height: 200px; overflow-y: auto; }
  </style>
</head>
<body class="bg-gray-100 font-sans">

<!-- ✅ Top Header copied from dashboard.php -->
<header class="w-full bg-white border-b border-gray-300 flex items-center justify-between px-6 py-4 shadow">
  <h1 class="text-2xl font-bold text-blue-900">TabeTalá</h1>

  <div class="flex items-center space-x-4">
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

    <!-- User Dropdown -->
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

<!-- Layout -->
<div class="flex min-h-screen">
  <!-- Sidebar -->
  <aside class="w-64 bg-blue-900 text-white p-6 border-r border-blue-700">
    <ul class="space-y-2 text-lg font-medium">
      <li><a href="dashboard.php" class="block px-2 py-2 rounded hover:bg-blue-800">Dashboard</a></li>
      <li><a href="occupancy.php" class="block px-2 py-2 rounded hover:bg-blue-800">Occupancy Monitoring</a></li>
      <li><a href="access.php" class="block px-2 py-2 rounded hover:bg-blue-800">Access Control</a></li>
      <li><a href="equipment.php" class="block px-2 py-2 rounded hover:bg-blue-800">Equipment Status</a></li>
      <li><a href="lab_requests_list.php" class="block px-2 py-2 rounded hover:bg-blue-800">Request Lab</a></li>
      <li><a href="reports.php" class="block px-2 py-2 rounded bg-blue-800">Reports</a></li>
      <li><a href="settings.php" class="block px-2 py-2 rounded hover:bg-blue-800">Settings</a></li>
    </ul>
  </aside>

  <!-- Main Content -->
  <main class="flex-1 p-6">
    <h1 class="text-2xl font-bold mb-6">Reports</h1>

    <div class="bg-white p-6 rounded-lg shadow mb-6">
      <h2 class="text-xl font-semibold mb-4">Create Report</h2>
      
      <form method="POST" class="flex items-center space-x-4 mb-4">
        <select name="reportType" class="px-4 py-2 border rounded-md">
          <option value="">Select Report Type</option>
          <option value="missing">Missing Equipment</option>
          <option value="attendance">Attendance Logs</option>
          <option value="access">Access Logs (Unauthorized)</option>
        </select>
        <input type="date" name="filterDate" class="px-4 py-2 border rounded-md" />
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
          Generate
        </button>
      </form>

      <!-- Report Table -->
      <div class="overflow-x-auto">
        <table id="reportTable" class="w-full border-collapse border border-gray-300">
          <thead class="bg-gray-200">
            <tr>
              <?php if (!empty($columns)) : ?>
                <?php foreach ($columns as $col) : ?>
                  <th class="border px-4 py-2 text-left"><?= htmlspecialchars($col) ?></th>
                <?php endforeach; ?>
              <?php else: ?>
                <th class="border px-4 py-2 text-left">Column 1</th>
                <th class="border px-4 py-2 text-left">Column 2</th>
                <th class="border px-4 py-2 text-left">Column 3</th>
                <th class="border px-4 py-2 text-left">Column 4</th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($data)) : ?>
              <?php foreach ($data as $row) : ?>
                <tr>
                  <?php foreach ($row as $cell) : ?>
                    <td class="border px-4 py-2"><?= htmlspecialchars($cell) ?></td>
                  <?php endforeach; ?>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="4" class="border px-4 py-2 text-center text-gray-500">No data found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
<script>
  // 🔎 Same search index as dashboard
  const searchIndex = [
    { name: "Dashboard", link: "dashboard.php" },
    { name: "Occupancy Monitoring", link: "occupancy.php" },
    { name: "Access Control", link: "access.php" },
    { name: "Equipment Status", link: "equipment.php" },
    { name: "Reports", link: "reports.php" },
    { name: "Settings", link: "settings.php" },
    { name: "Notifications", link: "notifications.php" },
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

  // DataTables
  $(document).ready(function () {
    $('#reportTable').DataTable({
      dom: 'Bfrtip',
      buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
      paging: false,
      searching: false,
      info: false
    });
  });
</script>
</body>
</html>
