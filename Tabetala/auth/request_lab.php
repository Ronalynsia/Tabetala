<?php
session_start();
require_once __DIR__ . "/../config/config.php";

$db = new Database();
$conn = $db->connect();

$success = "";
$error = "";

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname     = trim($_POST['fullname'] ?? '');
    $address      = trim($_POST['address'] ?? '');
    $organization = trim($_POST['organization'] ?? '');
    $lab          = trim($_POST['lab'] ?? '');
    $date         = trim($_POST['date'] ?? '');
    $time         = trim($_POST['time'] ?? '');
    $reason       = trim($_POST['reason'] ?? '');

    if ($fullname && $address && $organization && $lab && $date && $time && $reason) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO lab_requests 
                (fullname, address, organization, lab, date, time, reason) 
                VALUES (:fullname, :address, :organization, :lab, :date, :time, :reason)
            ");
            $stmt->execute([
                ':fullname'     => $fullname,
                ':address'      => $address,
                ':organization' => $organization,
                ':lab'          => $lab,
                ':date' => $date,
                ':time' => $time,
                ':reason'       => $reason
            ]);

            $success = "✅ Your lab request has been submitted!";
        } catch (Exception $e) {
            $error = "❌ Failed to submit request: " . $e->getMessage();
        }
    } else {
        $error = "⚠️ Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Request Lab</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-screen w-screen flex flex-col items-center justify-center bg-cover bg-center relative" style="background-image: url('psu.png');">

  <!-- Overlay -->
  <div class="absolute inset-0 bg-black bg-opacity-50"></div>

  <!-- Title -->
  <h1 class="relative z-10 text-3xl font-bold text-white mb-6 drop-shadow-lg text-center">
    TabeTalá Management System
  </h1>

  <!-- Card -->
  <div class="relative bg-white p-8 rounded-xl shadow-md w-full max-w-lg z-10">

    <h2 class="text-xl font-semibold mb-6 text-center">Lab Request Form</h2>

    <?php if ($success): ?>
      <div class="mb-4 p-3 rounded bg-green-100 text-green-800 text-sm"><?= $success ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="mb-4 p-3 rounded bg-red-100 text-red-800 text-sm"><?= $error ?></div>
    <?php endif; ?>

    <form action="" method="POST" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Full Name</label>
        <input type="text" name="fullname" class="w-full px-4 py-2 border rounded-md" required>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Address</label>
        <input type="text" name="address" class="w-full px-4 py-2 border rounded-md" required>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Organization / School / Company</label>
        <input type="text" name="organization" class="w-full px-4 py-2 border rounded-md" required>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Lab Requested</label>
        <select name="lab" class="w-full px-4 py-2 border rounded-md" required>
          <option value="">-- Select a Lab --</option>
          <option value="Computer Lab">Computer Lab</option>
          <option value="Electronics Lab">Electronics Lab</option>
          <option value="Networking Lab">Networking Lab</option>
        </select>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Date</label>
          <input type="date" name="date" class="w-full px-4 py-2 border rounded-md" required>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Time</label>
          <input type="time" name="time" class="w-full px-4 py-2 border rounded-md" required>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Reason</label>
        <textarea name="reason" rows="3" class="w-full px-4 py-2 border rounded-md" required></textarea>
      </div>

      <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700">
        Submit Request
      </button>
    </form>

    <p class="text-sm text-center text-gray-600 mt-4">
      Back to 
      <a href="signin.php" class="text-blue-600 hover:underline">Sign In</a>
    </p>
  </div>

</body>
</html>
