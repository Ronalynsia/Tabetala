<?php
session_start();
require_once __DIR__ . "/../config/config.php";

// Initialize database
$db = new Database();
$conn = $db->connect();

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = trim($_POST["firstname"]);
    $lastname = trim($_POST["lastname"]);
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    if ($password !== $confirm_password) {
        $error = "❌ Passwords do not match!";
    } else {
        // Hash password for security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert into database
        try {
            $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, username, email, password) 
                                    VALUES (:firstname, :lastname, :username, :email, :password)");
            $stmt->bindParam(":firstname", $firstname);
            $stmt->bindParam(":lastname", $lastname);
            $stmt->bindParam(":username", $username);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":password", $hashed_password);

            if ($stmt->execute()) {
                $success = "✅ Account created successfully! You can now sign in.";
            } else {
                $error = "⚠️ Failed to create account. Try again.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Sign Up</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-screen w-screen flex flex-col items-center justify-center bg-cover bg-center relative" style="background-image: url('../bg system.jpg');">

  <!-- System Title -->
  <h1 class="relative z-10 text-3xl font-bold text-white mb-6 drop-shadow-lg text-center">
    TabeTalá Management System
  </h1>

  <!-- Sign Up Card -->
  <div class="bg-white p-8 rounded-xl shadow-md w-full max-w-md relative z-10">
    <h2 class="text-xl font-semibold mb-6 text-center">Create Account</h2>

    <?php if ($error): ?>
      <p class="mb-4 text-red-600 text-center font-medium"><?= $error ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
      <p class="mb-4 text-green-600 text-center font-medium"><?= $success ?></p>
    <?php endif; ?>

    <form action="" method="POST" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">First Name</label>
        <input type="text" name="firstname" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400" required />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Last Name</label>
        <input type="text" name="lastname" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400" required />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Username</label>
        <input type="text" name="username" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400" required />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Email</label>
        <input type="email" name="email" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400" required />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Password</label>
        <input type="password" name="password" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400" required />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
        <input type="password" name="confirm_password" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400" required />
      </div>
      <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700">
        Sign Up
      </button>
    </form>

    <p class="text-sm text-center text-gray-600 mt-4">
      Already have an account?
      <a href="signin.php" class="text-blue-600 hover:underline">Sign In</a>
    </p>
  </div>

</body>
</html>
