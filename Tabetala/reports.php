<?php
session_start();
require_once __DIR__ . "/config/config.php";

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
        $sql = "SELECT name, role, DATE(date) AS date, status 
                FROM attendance_logs";
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
</head>
<body class="bg-gray-100 font-sans">

  <!-- Header -->
  <header class="w-full bg-white border-b border-gray-300 flex items-center justify-between px-6 py-4 shadow">
    <h1 class="text-2xl font-bold text-blue-900">TabeTalá</h1>
    <div class="flex items-center gap-4">
      <button class="p-2 rounded-full hover:bg-gray-100">🔔</button>
      <div class="relative inline-block text-left">
        <button class="px-4 py-2 bg-white border rounded-md">Admin ▾</button>
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
       <li><a href="lab_requests_list.php" class="nav-link block px-2 py-2 rounded hover:bg-blue-800">Request Lab</a></li> <!-- ✅ Added -->

        <li><a href="reports.php" class="block px-2 py-2 rounded bg-blue-800">Reports</a></li>
        <li><a href="settings.php" class="block px-2 py-2 rounded hover:bg-blue-800">Settings</a></li>
      </ul>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-6">
      <h1 class="text-2xl font-bold mb-6">Reports</h1>

      <!-- Form -->
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
