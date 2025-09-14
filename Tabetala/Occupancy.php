<?php
session_start();

// ✅ Restrict access if not logged in
if (!isset($_SESSION["username"])) {
  header("Location: auth/signin.php");
  exit();
}

// ✅ Sample lab data (temporary array, replace later with DB)
$labs = [
  "Lab 1" => [
    "location" => "2nd Floor, Building A",
    "status" => "Vacant",
    "equipment" => [
      ["model" => "Dell Inspiron", "brand" => "Dell", "category" => "Laptop", "quantity" => 15],
      ["model" => "Logitech K120", "brand" => "Logitech", "category" => "Keyboard", "quantity" => 20]
    ]
  ],
  "Lab 2" => [
    "location" => "Ground Floor, Building B",
    "status" => "Occupied",
    "equipment" => [
      ["model" => "HP Elitebook", "brand" => "HP", "category" => "Laptop", "quantity" => 10],
      ["model" => "A4Tech OP-720", "brand" => "A4Tech", "category" => "Mouse", "quantity" => 25]
    ]
  ]
];

// ✅ Render Header
function renderHeader() {
  ?>
  <header class="w-full bg-white border-b border-gray-300 flex items-center justify-between px-6 py-4 shadow">
    <h1 class="text-2xl font-bold text-blue-900">TabeTalá</h1>
    <div class="flex items-center space-x-4">
      <!-- Notifications -->
      <div class="relative">
        <button id="notifButton" class="relative p-2 rounded-full hover:bg-gray-100">🔔
          <span class="absolute top-0 right-0 inline-block w-2 h-2 bg-red-600 rounded-full"></span>
        </button>
        <div id="notifDropdown" class="origin-top-right absolute right-0 mt-2 w-72 rounded-md shadow-lg bg-white hidden">
          <div class="py-2">
            <a href="notifications.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Go to Notifications Page</a>
            <div class="border-t my-1"></div>
            <p class="px-4 py-2 text-sm text-gray-600">⚠️ Lab 1: Keyboard missing</p>
            <p class="px-4 py-2 text-sm text-gray-600">📝 Request: Access for new staff</p>
            <p class="px-4 py-2 text-sm text-gray-600">⚠️ Lab 2: Mouse missing</p>
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

      <!-- User Menu -->
      <div class="relative inline-block text-left">
        <button id="userMenuButton" type="button"
          class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
          <?= htmlspecialchars($_SESSION["username"]) ?> ▾
        </button>
        <div id="userDropdown"
          class="origin-top-right absolute right-0 mt-2 w-36 rounded-md shadow-lg bg-white hidden">
          <div class="py-1">
            <a href="admin.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
            <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Log Out</a>
          </div>
        </div>
      </div>
    </div>
  </header>
  <?php
}

// ✅ Render Sidebar
function renderSidebar() {
  ?>
  <aside class="w-64 bg-blue-900 text-white p-6 border-r border-blue-700">
    <ul class="space-y-2 text-lg font-medium">
      <li><a href="dashboard.php" class="block px-2 py-2 rounded hover:bg-blue-800">Dashboard</a></li>
      <li><a href="occupancy.php" class="block px-2 py-2 rounded bg-blue-800">Occupancy Monitoring</a></li>
      <li><a href="access.php" class="block px-2 py-2 rounded hover:bg-blue-800">Access Control</a></li>
      <li><a href="equipment.php" class="block px-2 py-2 rounded hover:bg-blue-800">Equipment Status</a></li>
      <li><a href="lab_requests_list.php" class="nav-link block px-2 py-2 rounded hover:bg-blue-800">Request Lab</a></li>
      <li><a href="reports.php" class="block px-2 py-2 rounded hover:bg-blue-800">Reports</a></li>
      <li><a href="settings.php" class="block px-2 py-2 rounded hover:bg-blue-800">Settings</a></li>
    </ul>
  </aside>
  <?php
}

// ✅ Render Lab Cards
function renderLabs($labs) {
  ?>
  <main class="flex-1 p-10">
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-3xl font-bold">Occupancy Monitoring</h2>
      <button onclick="addNewLab()" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">+ Add New Room</button>
    </div>
    <div id="labContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($labs as $labName => $lab): ?>
        <?php
        $color = $lab['status'] === "Vacant" ? "border-green-500" : "border-red-500";
        $statusColor = $lab['status'] === "Vacant" ? "text-green-700" : "text-red-700";
        ?>
        <div class="bg-white shadow rounded-lg p-6 border-t-4 <?= $color ?>">
          <h3 class="text-xl font-semibold mb-2"><?= $labName ?></h3>
          <p class="text-gray-600">📍 Location: <?= $lab['location'] ?></p>
          <p class="font-semibold <?= $statusColor ?> mt-2">Status: <?= $lab['status'] ?></p>
          <div class="flex gap-2 mt-4">
            <button class="px-3 py-1 bg-gray-600 text-white rounded hover:bg-blue-700"
              onclick="openModal('<?= $labName ?>')">View</button>
            <button class="px-3 py-1 bg-gray-500 text-white rounded hover:bg-yellow-600"
              onclick="editLab('<?= $labName ?>')">Edit</button>
            <button class="px-3 py-1 bg-gray-600 text-white rounded hover:bg-red-700"
              onclick="deleteLab('<?= $labName ?>')">Delete</button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </main>
  <?php
}

// ✅ Render Modal
function renderModal() {
  ?>
  <div id="equipmentModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg w-3/4 max-w-5xl p-6">
      <div class="flex justify-between items-center border-b pb-3">
        <h2 id="modalTitle" class="text-xl font-bold">Lab Equipment</h2>
        <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">✖</button>
      </div>
      <div class="mt-4 overflow-x-auto">
        <table class="min-w-full border border-gray-300 text-sm text-left">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-4 py-2 border">Model</th>
              <th class="px-4 py-2 border">Brand</th>
              <th class="px-4 py-2 border">Category</th>
              <th class="px-4 py-2 border">Quantity</th>
            </tr>
          </thead>
          <tbody id="equipmentTable"></tbody>
        </table>
      </div>
      <div class="flex justify-end mt-4">
        <button onclick="closeModal()" class="bg-gray-400 text-white px-4 py-2 rounded hover:bg-gray-500">Close</button>
      </div>
    </div>
  </div>
  <?php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Occupancy Monitoring</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">

  <?php renderHeader(); ?>

  <div class="flex min-h-screen">
    <?php renderSidebar(); ?>
    <?php renderLabs($labs); ?>
  </div>

  <?php renderModal(); ?>

<script>
let labs = <?php echo json_encode($labs); ?>;

// ✅ View Modal
function openModal(labName) {
  document.getElementById("modalTitle").innerText = labName + " - Equipment";
  const table = document.getElementById("equipmentTable");
  table.innerHTML = "";
  labs[labName].equipment.forEach(item => {
    table.innerHTML += `
      <tr>
        <td class="px-4 py-2 border">${item.model}</td>
        <td class="px-4 py-2 border">${item.brand}</td>
        <td class="px-4 py-2 border">${item.category}</td>
        <td class="px-4 py-2 border">${item.quantity}</td>
      </tr>`;
  });
  document.getElementById("equipmentModal").classList.remove("hidden");
  document.getElementById("equipmentModal").classList.add("flex");
}
function closeModal() {
  document.getElementById("equipmentModal").classList.add("hidden");
  document.getElementById("equipmentModal").classList.remove("flex");
}

// ✅ Edit Lab (Prompt only, no alerts)
function editLab(labName) {
  let newName = prompt("Enter new lab name:", labName);
  if (!newName || newName.trim() === "") return;

  let newLocation = prompt("Enter new location:", labs[labName].location);
  if (!newLocation || newLocation.trim() === "") return;

  let newStatus = prompt("Enter new status (Vacant/Occupied):", labs[labName].status);
  if (!newStatus || newStatus.trim() === "") return;

  labs[newName] = {
    ...labs[labName],
    location: newLocation,
    status: newStatus
  };

  if (newName !== labName) {
    delete labs[labName];
  }

  prompt("Lab updated successfully. Press OK to continue.", newName);
  location.reload();
}

// ✅ Delete Lab (Prompt only, no alerts)
function deleteLab(labName) {
  let answer = prompt("To delete '" + labName + "', type DELETE:");
  if (answer && answer.trim().toUpperCase() === "DELETE") {
    delete labs[labName];
    prompt("Lab deleted. Press OK to continue.", labName);
    location.reload();
  } else {
    prompt("Deletion cancelled. Press OK to continue.", labName);
  }
}

// ✅ Add New Lab (Prompt only, no alerts)
function addNewLab() {
  let name = prompt("Enter lab name:");
  if (!name || labs[name]) {
    prompt("Invalid or existing lab name. Press OK to continue.", "");
    return;
  }

  let location = prompt("Enter lab location:");
  if (!location || location.trim() === "") location = "Not set";

  let status = prompt("Enter status (Vacant/Occupied):", "Vacant");
  if (!status || status.trim() === "") status = "Vacant";

  labs[name] = {
    location: location,
    status: status,
    equipment: []
  };

  prompt("New lab added. Press OK to continue.", name);
  location.reload();
}



// ✅ Search
const searchIndex = [
  { name: "Dashboard", link: "dashboard.php" },
  { name: "Occupancy Monitoring", link: "occupancy.php" },
  { name: "Access Control", link: "access.php" },
  { name: "Equipment Status", link: "equipment.php" },
  { name: "Reports", link: "reports.php" },
  { name: "Settings", link: "settings.php" },
  { name: "Notifications", link: "notifications.php" }
];
function searchNavigation() {
  const input = document.getElementById("searchInput").value.toLowerCase();
  const resultsBox = document.getElementById("searchResults");
  resultsBox.innerHTML = "";
  resultsBox.classList.add("hidden");
  if (input) {
    let matches = searchIndex.filter(item => item.name.toLowerCase().includes(input));
    if (matches.length > 0) {
      resultsBox.innerHTML = matches.map(item =>
        `<a href="${item.link}" class="block px-4 py-2 hover:bg-gray-100">🔗 ${item.name}</a>`
      ).join("");
      resultsBox.classList.remove("hidden");
    }
  }
}

// ✅ Dropdowns
document.getElementById("userMenuButton").addEventListener("click", () => {
  document.getElementById("userDropdown").classList.toggle("hidden");
});
document.getElementById("notifButton").addEventListener("click", () => {
  document.getElementById("notifDropdown").classList.toggle("hidden");
});
</script>

</body>
</html>
