<?php
session_start();
require_once __DIR__ . "/config/config.php";

$db = new Database();
$conn = $db->connect();

// Default values
$systemName = "Laboratory Management System";
$timezone   = "Asia/Manila";
$theme      = "light";
$message    = "";

// ✅ Load settings from DB
$stmt = $conn->query("SELECT * FROM settings LIMIT 1");
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $systemName = $row['system_name'];
    $timezone   = $row['timezone'];
    $theme      = $row['theme'];
}

// ✅ Save settings if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $systemName = $_POST["systemName"];
    $timezone   = $_POST["timezone"];
    $theme      = $_POST["theme"];

    // Update or insert settings (single row only)
    $stmt = $conn->prepare("REPLACE INTO settings (id, system_name, timezone, theme) VALUES (1, :systemName, :timezone, :theme)");
    $stmt->execute([
        ":systemName" => $systemName,
        ":timezone"   => $timezone,
        ":theme"      => $theme
    ]);

    $message = "✅ Settings saved successfully!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo htmlspecialchars($systemName); ?> - Settings</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="font-sans <?php echo ($theme === 'dark') ? 'bg-gray-900 text-white' : 'bg-gray-100 text-black'; ?>">

  <!-- Top Header -->
  <header class="w-full bg-white border-b border-gray-300 flex items-center justify-between px-6 py-4 shadow">
    <h1 class="text-2xl font-bold text-blue-900"><?php echo htmlspecialchars($systemName); ?></h1>

    <!-- User Dropdown -->
    <div class="relative inline-block text-left">
      <button id="userMenuButton" type="button" 
        class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
        Admin ▾
      </button>
      <div id="userDropdown" 
        class="origin-top-right absolute right-0 mt-2 w-36 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 hidden">
        <div class="py-1">
          <a href="admin.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
          <a href="signin.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Log Out</a>
        </div>
      </div>
    </div>
  </header>

  <!-- Sidebar + Main -->
  <div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-blue-900 text-white p-6 border-r border-blue-700">
      <ul class="space-y-2 text-lg font-medium">
        <li><a href="dashboard.php" class="block px-2 py-2 rounded hover:bg-blue-800">Dashboard</a></li>
        <li><a href="occupancy.php" class="block px-2 py-2 rounded hover:bg-blue-800">Occupancy Monitoring</a></li>
        <li><a href="access.php" class="block px-2 py-2 rounded hover:bg-blue-800">Access Control</a></li>
        <li><a href="equipment.php" class="block px-2 py-2 rounded hover:bg-blue-800">Equipment Status</a></li>
        <li><a href="lab_requests_list.php" class="nav-link block px-2 py-2 rounded hover:bg-blue-800">Request Lab</a></li> <!-- ✅ Added -->

        <li><a href="reports.php" class="block px-2 py-2 rounded hover:bg-blue-800">Reports</a></li>
        <li><a href="settings.php" class="block px-2 py-2 rounded bg-blue-800">Settings</a></li>
      </ul>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-6 space-y-6">
      <h2 class="text-2xl font-bold text-blue-900 mb-6">⚙️ Settings</h2>

      <div class="bg-white shadow rounded-lg p-6">
        <?php if (!empty($message)): ?>
          <p class="mb-4 text-green-600 font-semibold"><?php echo $message; ?></p>
        <?php endif; ?>

        <form method="POST" action="">
          <!-- System Name -->
          <label class="block mb-2">System Name</label>
          <input name="systemName" type="text" 
                 class="w-full border rounded px-3 py-2 mb-4"
                 value="<?php echo htmlspecialchars($systemName); ?>">

          <!-- Timezone -->
          <label class="block mb-2">Timezone</label>
          <select name="timezone" class="w-full border rounded px-3 py-2 mb-4">
            <option value="Asia/Manila" <?php if ($timezone=="Asia/Manila") echo "selected"; ?>>Asia/Manila</option>
            <option value="UTC" <?php if ($timezone=="UTC") echo "selected"; ?>>UTC</option>
            <option value="America/New_York" <?php if ($timezone=="America/New_York") echo "selected"; ?>>America/New_York</option>
          </select>

          <!-- Theme -->
          <label class="block mb-2">Theme</label>
          <select name="theme" class="w-full border rounded px-3 py-2 mb-4">
            <option value="light" <?php if ($theme=="light") echo "selected"; ?>>Light</option>
            <option value="dark" <?php if ($theme=="dark") echo "selected"; ?>>Dark</option>
          </select>

          <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Save</button>
        </form>
      </div>
    </main>
  </div>

  <script>
    document.getElementById("userMenuButton").addEventListener("click", () => {
      document.getElementById("userDropdown").classList.toggle("hidden");
    });
  </script>
</body>
</html>
