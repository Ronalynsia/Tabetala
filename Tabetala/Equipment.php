<?php
session_start();
require_once __DIR__ . '/config/config.php'; // ✅ adjust path if needed

// Create database connection
$db = new Database();
$conn = $db->connect();

// Fetch equipment data
$stmt = $conn->query("SELECT * FROM equipment");
$equipments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Equipment Status</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">

  <!-- Top Header -->
  <header class="w-full bg-white border-b border-gray-300 flex items-center justify-between px-6 py-4 shadow">
    <h1 class="text-2xl font-bold text-blue-900">TabeTalá</h1>
    <div class="flex items-center gap-4">
      <!-- Notification -->
      <div class="relative">
        <button id="notifButton" class="relative p-2 rounded-full hover:bg-gray-100">
          <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" 
              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 
                 6 0 00-5-5.917V4a1 1 0 10-2 0v1.083A6 6 
                 0 006 11v3.159c0 .538-.214 1.055-.595 
                 1.436L4 17h5m6 0v1a3 3 0 11-6 
                 0v-1m6 0H9"></path>
          </svg>
          <span class="absolute top-0 right-0 inline-block w-2 h-2 bg-red-600 rounded-full"></span>
        </button>
      </div>

      <!-- Search Bar -->
      <div class="relative">
        <input type="text" id="searchInput" placeholder="Search..." 
          class="px-4 py-2 border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" />
      </div>

      <!-- User Dropdown -->
      <div class="relative inline-block text-left">
        <button id="userMenuButton" type="button" 
          class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm 
                 px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
          <?= htmlspecialchars($_SESSION['username']) ?> ▾
        </button>
      </div>
    </div>
  </header>

  <!-- Layout Wrapper -->
  <div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-blue-900 text-white p-6 border-r border-blue-700 min-h-screen">
      <ul class="space-y-2 text-lg font-medium">
        <li><a href="dashboard.php" class="block px-2 py-2 rounded hover:bg-blue-800">Dashboard</a></li>
        <li><a href="occupancy.php" class="block px-2 py-2 rounded hover:bg-blue-800">Occupancy Monitoring</a></li>
        <li><a href="Access.php" class="block px-2 py-2 rounded hover:bg-blue-800">Access Control</a></li>
        <li><a href="Equipment.php" class="block px-2 py-2 rounded bg-blue-800">Equipment Status</a></li>
          <li><a href="lab_requests_list.php" class="nav-link block px-2 py-2 rounded hover:bg-blue-800">Request Lab</a></li> <!-- ✅ Added -->
        <li><a href="reports.php" class="block px-2 py-2 rounded hover:bg-blue-800">Reports</a></li>
        <li><a href="settings.php" class="block px-2 py-2 rounded hover:bg-blue-800">Settings</a></li>
      </ul>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-8">
      <h1 class="text-2xl font-bold mb-6">Equipment Status</h1>
      <div class="overflow-x-auto">
        <table class="w-full border border-gray-300 bg-white shadow-md rounded-lg">
          <thead class="bg-gray-300 text-black">
            <tr>
              <th class="px-4 py-2 border">Lab Name</th>
              <th class="px-4 py-2 border">Image</th>
              <th class="px-4 py-2 border">Quantity</th>
              <th class="px-4 py-2 border">Brand</th>
              <th class="px-4 py-2 border">Model</th>
              <th class="px-4 py-2 border">Category</th>
              <th class="px-4 py-2 border">Status</th>
              <th class="px-4 py-2 border">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($equipments): ?>
              <?php foreach ($equipments as $eq): ?>
                <tr class="text-center">
                  <td class="border px-4 py-2"><?= htmlspecialchars($eq['lab']) ?></td>
                  <td class="border px-4 py-2">
                    <img src="<?= htmlspecialchars($eq['image']) ?>" alt="Equipment" class="mx-auto w-12 h-12">
                  </td>
                  <td class="border px-4 py-2"><?= htmlspecialchars($eq['quantity']) ?></td>
                  <td class="border px-4 py-2"><?= htmlspecialchars($eq['brand']) ?></td>
                  <td class="border px-4 py-2"><?= htmlspecialchars($eq['model']) ?></td>
                  <td class="border px-4 py-2"><?= htmlspecialchars($eq['category']) ?></td>
                  <td class="border px-4 py-2 font-semibold 
                      <?= $eq['status'] === 'Available' ? 'text-green-600' : 'text-red-600' ?>">
                      <?= htmlspecialchars($eq['status']) ?>
                  </td>
                  <td class="border px-4 py-2">
                    <button 
                      onclick="viewItem(
                        '<?= addslashes($eq['lab']) ?>',
                        '<?= addslashes($eq['brand']) ?>',
                        '<?= addslashes($eq['model']) ?>',
                        '<?= addslashes($eq['category']) ?>',
                        '<?= addslashes($eq['quantity']) ?>',
                        '<?= addslashes($eq['status']) ?>',
                        '<?= addslashes($eq['image']) ?>',
                        '<?= addslashes($eq['description']) ?>'
                      )" 
                      class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">
                      View
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="8" class="text-center text-gray-600 py-4">No equipment found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>

  <!-- View Item Modal -->
  <div id="viewModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center">
    <div class="bg-white w-96 p-6 rounded-lg shadow-lg relative">
      <button onclick="closeModal()" class="absolute top-3 right-3 text-gray-600 hover:text-black">✖</button>
      <h2 class="text-xl font-bold mb-4">Equipment Details</h2>
      <img id="itemImage" src="" alt="Item Image" class="w-32 h-32 object-cover mx-auto mb-4">
      <p><strong>Lab:</strong> <span id="itemLab"></span></p>
      <p><strong>Brand:</strong> <span id="itemBrand"></span></p>
      <p><strong>Model:</strong> <span id="itemModel"></span></p>
      <p><strong>Category:</strong> <span id="itemCategory"></span></p>
      <p><strong>Quantity:</strong> <span id="itemQuantity"></span></p>
      <p><strong>Status:</strong> <span id="itemStatus"></span></p>
      <p class="mt-2"><strong>Description:</strong></p>
      <p id="itemDescription" class="text-gray-700"></p>
    </div>
  </div>

  <script>
    function viewItem(lab, brand, model, category, quantity, status, image, description) {
      document.getElementById("itemLab").innerText = lab;
      document.getElementById("itemBrand").innerText = brand;
      document.getElementById("itemModel").innerText = model;
      document.getElementById("itemCategory").innerText = category;
      document.getElementById("itemQuantity").innerText = quantity;
      document.getElementById("itemStatus").innerText = status;
      document.getElementById("itemImage").src = image;
      document.getElementById("itemDescription").innerText = description;
      document.getElementById("viewModal").classList.remove("hidden");
      document.getElementById("viewModal").classList.add("flex");
    }
    function closeModal() {
      document.getElementById("viewModal").classList.add("hidden");
    }
  </script>
</body>
</html>
