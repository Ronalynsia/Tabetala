<?php
session_start();

require_once __DIR__ . "/../config/config.php";

$db = new Database();
$conn = $db->connect();

$error = "";

// If form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user["password"])) {
            // Save session
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["username"] = $user["username"];

            header("Location: ../dashboard.php"); // ✅ Correct path
            exit();
        } else {
            $error = "❌ Invalid username or password!";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Sign In</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-screen w-screen flex flex-col items-center justify-center bg-cover bg-center relative" style="background-image: url('bg system.jpg');">

  <!-- Overlay -->
  <div class="absolute inset-0 bg-black bg-opacity-40"></div>

  <!-- System Title -->
  <h1 class="relative z-10 text-3xl font-bold text-white mb-6 drop-shadow-lg text-center">
    TabeTalá Management System
  </h1>

  <!-- Sign-in Card -->
  <div class="relative bg-white p-8 rounded-xl shadow-md w-full max-w-md z-10">
    <h2 class="text-xl font-semibold mb-6 text-center">Sign In</h2>

    <?php if ($error): ?>
      <p class="mb-4 text-red-600 text-center font-medium"><?= $error ?></p>
    <?php endif; ?>

    <form action="" method="POST" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Username</label>
        <input type="text" name="username" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400" required />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Password</label>
        <input type="password" name="password" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400" required />
      </div>
      <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700">
        Sign In
      </button>
    </form>

    <!-- Sign Up -->
    <p class="text-sm text-center text-gray-600 mt-4">
      Don’t have an account?
      <a href="signup.php" class="text-blue-600 hover:underline">Sign Up</a>
    </p>

    <!-- Request Lab -->
    <p class="text-sm text-center text-gray-600 mt-2">
      Want to request a lab?  
      <a href="request_lab.php" class="text-blue-600 hover:underline">Request Lab</a>
    </p>
  </div>

</body>
</html>
