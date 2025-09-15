<?php
// admin.php
session_start();
require_once __DIR__ . '/config/config.php';

// ---------- DB connection (robust, supports Database class or $conn/$pdo from config)
$conn = null;
if (class_exists('Database')) {
    try {
        $db = new Database();
        $conn = $db->connect();
    } catch (Exception $e) {
        die("Database connection error: " . $e->getMessage());
    }
} elseif (isset($conn) && $conn instanceof PDO) {
    // already provided by config
} elseif (isset($pdo) && $pdo instanceof PDO) {
    $conn = $pdo;
} else {
    die("Database connection not found. Check config.php");
}

// ---------- Require login
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/signin.php");
    exit;
}
$user_id = (int) $_SESSION['user_id'];

// ---------- If form submitted, handle actions (POST -> Redirect -> GET)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $errors = [];
    $success = null;

    try {
        if ($action === 'info') {
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name  = trim($_POST['last_name'] ?? '');
            $username   = trim($_POST['username'] ?? '');
            $email      = trim($_POST['email'] ?? '');
            $mobile     = trim($_POST['mobile'] ?? '');

            // basic validation
            if ($first_name === '' || $last_name === '' || $username === '' || $email === '') {
                $errors[] = "First name, last name, username and email are required.";
            } else {
                // optional: check uniqueness of username/email (simple example)
                $stmt = $conn->prepare("SELECT id FROM users WHERE username = :u AND id != :id LIMIT 1");
                $stmt->execute([":u"=>$username, ":id"=>$user_id]);
                if ($stmt->fetch()) $errors[] = "Username already in use.";

                $stmt = $conn->prepare("SELECT id FROM users WHERE email = :e AND id != :id LIMIT 1");
                $stmt->execute([":e"=>$email, ":id"=>$user_id]);
                if ($stmt->fetch()) $errors[] = "Email already in use.";
            }

            if (empty($errors)) {
                $stmt = $conn->prepare("UPDATE users SET first_name = :fn, last_name = :ln, username = :u, email = :e, mobile = :m WHERE id = :id");
                $stmt->execute([
                    ":fn"=>$first_name,
                    ":ln"=>$last_name,
                    ":u"=>$username,
                    ":e"=>$email,
                    ":m"=>$mobile,
                    ":id"=>$user_id
                ]);
                $success = "Profile information updated.";
            }
        }

        elseif ($action === 'avatar') {
            if (!isset($_FILES['profile_picture'])) {
                $errors[] = "No file uploaded.";
            } else {
                $file = $_FILES['profile_picture'];
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $errors[] = "Error uploading file (code {$file['error']}).";
                } else {
                    // validate image
                    $tmp = $file['tmp_name'];
                    $imginfo = @getimagesize($tmp);
                    if ($imginfo === false) {
                        $errors[] = "Uploaded file is not a valid image.";
                    } else {
                        $maxBytes = 2 * 1024 * 1024; // 2MB limit
                        if ($file['size'] > $maxBytes) {
                            $errors[] = "Image too large. Max 2MB.";
                        } else {
                            $allowed = ['jpg','jpeg','png','gif'];
                            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                            if (!in_array($ext, $allowed)) {
                                $errors[] = "Allowed file types: jpg, jpeg, png, gif.";
                            } else {
                                // ensure uploads dir exists
                                $uploadDir = __DIR__ . '/uploads';
                                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                                $newName = "user_{$user_id}_" . time() . "." . $ext;
                                $target = $uploadDir . '/' . $newName;

                                if (move_uploaded_file($tmp, $target)) {
                                    // delete old avatar if exists
                                    $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = :id LIMIT 1");
                                    $stmt->execute([":id"=>$user_id]);
                                    $old = $stmt->fetchColumn();
                                    if ($old) {
                                        $oldPath = $uploadDir . '/' . $old;
                                        if (is_file($oldPath)) @unlink($oldPath);
                                    }

                                    // update db
                                    $stmt = $conn->prepare("UPDATE users SET profile_picture = :p WHERE id = :id");
                                    $stmt->execute([":p"=>$newName, ":id"=>$user_id]);
                                    $success = "Avatar updated successfully.";
                                } else {
                                    $errors[] = "Failed to move uploaded file.";
                                }
                            }
                        }
                    }
                }
            }
        }

        elseif ($action === 'password') {
            $current = $_POST['current_password'] ?? '';
            $new     = $_POST['new_password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';

            if ($current === '' || $new === '' || $confirm === '') {
                $errors[] = "All password fields are required.";
            } elseif ($new !== $confirm) {
                $errors[] = "New passwords do not match.";
            } else {
                // fetch existing hash
                $stmt = $conn->prepare("SELECT password FROM users WHERE id = :id LIMIT 1");
                $stmt->execute([":id"=>$user_id]);
                $hash = $stmt->fetchColumn();
                if (!$hash || !password_verify($current, $hash)) {
                    $errors[] = "Current password is incorrect.";
                } else {
                    $newHash = password_hash($new, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET password = :p WHERE id = :id");
                    $stmt->execute([":p"=>$newHash, ":id"=>$user_id]);
                    $success = "Password changed successfully.";
                }
            }
        } else {
            $errors[] = "Unknown action.";
        }
    } catch (Exception $ex) {
        $errors[] = "Server error: " . $ex->getMessage();
    }

    // flash and redirect to avoid re-submit
    $_SESSION['profile_errors'] = $errors;
    $_SESSION['profile_success'] = $success;
    header("Location: " . basename(__FILE__));
    exit;
}

// ---------- On GET: fetch user (fresh) and show page
$stmt = $conn->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
$stmt->execute([":id"=>$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    session_destroy();
    header("Location: auth/signin.php");
    exit;
}

// Grab flash messages
$success = $_SESSION['profile_success'] ?? null;
$errors  = $_SESSION['profile_errors'] ?? null;
unset($_SESSION['profile_success'], $_SESSION['profile_errors']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin Profile</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .tab-content { display:none; }
    .tab-content.active { display:block; }
    .tab-btn.active { border-bottom:2px solid #2563eb; color:#2563eb; }
    /* small helper for image box */
    .img-box { width:128px; height:128px; object-fit:cover; border-radius:9999px; border:1px solid #e5e7eb; }
  </style>
</head>
<body class="bg-gray-100 font-sans">

<header class="w-full bg-white border-b px-6 py-4 shadow flex items-center justify-between">
  <h1 class="text-2xl font-bold text-blue-900">TabeTalá</h1>
  <div class="flex items-center gap-4">
    <div class="text-sm"><?= htmlspecialchars($user['username'] ?? '') ?></div>
    <a href="logout.php" class="text-sm text-red-600">Log Out</a>
  </div>
</header>

<div class="flex min-h-screen">
  <aside class="w-64 bg-blue-900 text-white p-6">
    <ul class="space-y-2">
      <li><a href="dashboard.php" class="block px-2 py-2 rounded hover:bg-blue-800">Dashboard</a></li>
      <li><a href="Occupancy.php" class="block px-2 py-2 rounded hover:bg-blue-800">Occupancy Monitoring</a></li>
      <li><a href="Access.php" class="block px-2 py-2 rounded hover:bg-blue-800">Access Control</a></li>
      <li><a href="Equipment.php" class="block px-2 py-2 rounded hover:bg-blue-800">Equipment Status</a></li>
      <li><a href="Reports.php" class="block px-2 py-2 rounded hover:bg-blue-800">Reports</a></li>
      <li><a href="Settings.php" class="block px-2 py-2 rounded hover:bg-blue-800">Settings</a></li>
    </ul>
  </aside>

  <main class="flex-1 p-10">
    <h2 class="text-3xl font-bold mb-6">Profile Account</h2>

    <?php if ($success): ?>
      <div class="mb-4 p-4 bg-green-100 text-green-800 rounded"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if (!empty($errors) && is_array($errors)): ?>
      <div class="mb-4 p-4 bg-red-100 text-red-800 rounded">
        <ul class="list-disc pl-5">
          <?php foreach ($errors as $err): ?>
            <li><?= htmlspecialchars($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="bg-white p-8 rounded-xl shadow-md">
      <!-- Tabs -->
      <div class="flex border-b mb-6">
        <button class="tab-btn px-4 py-2 active" data-tab="info">User Account Info</button>
        <button class="tab-btn px-4 py-2" data-tab="avatar">Change Avatar</button>
        <button class="tab-btn px-4 py-2" data-tab="password">Change Password</button>
      </div>

      <!-- Tab: Info -->
      <div id="info" class="tab-content active">
        <form action="<?= htmlspecialchars(basename(__FILE__)) ?>" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <input type="hidden" name="action" value="info">
          <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">

          <div>
            <label class="block text-sm font-medium text-gray-700">First Name</label>
            <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" class="mt-1 w-full px-4 py-2 border rounded">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Last Name</label>
            <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" class="mt-1 w-full px-4 py-2 border rounded">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Username</label>
            <input type="text" name="username" value="<?= htmlspecialchars($user['username'] ?? '') ?>" class="mt-1 w-full px-4 py-2 border rounded">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" class="mt-1 w-full px-4 py-2 border rounded">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Mobile Number</label>
            <input type="text" name="mobile" value="<?= htmlspecialchars($user['mobile'] ?? '') ?>" class="mt-1 w-full px-4 py-2 border rounded">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Academic Program</label>
            <input type="text" value="<?= htmlspecialchars($user['program'] ?? '') ?>" class="mt-1 w-full px-4 py-2 border rounded bg-gray-100" readonly>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Group</label>
            <input type="text" value="<?= htmlspecialchars($user['group'] ?? '') ?>" class="mt-1 w-full px-4 py-2 border rounded bg-gray-100" readonly>
          </div>

          <div class="col-span-2">
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded">Save Changes</button>
          </div>
        </form>
      </div>

      <!-- Tab: Avatar -->
      <div id="avatar" class="tab-content">
        <form action="<?= htmlspecialchars(basename(__FILE__)) ?>" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <input type="hidden" name="action" value="avatar">
          <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">

          <div class="flex flex-col items-start gap-4">
            <?php if (!empty($user['profile_picture']) && is_file(__DIR__.'/uploads/'.$user['profile_picture'])): ?>
              <img id="previewImg" src="uploads/<?= htmlspecialchars($user['profile_picture']) ?>" alt="avatar" class="img-box">
            <?php else: ?>
              <img id="previewImg" src="assets/default-avatar.png" alt="avatar" class="img-box">
            <?php endif; ?>

            <div>
              <input type="file" name="profile_picture" accept="image/*" onchange="previewFile(this)">
            </div>

            <p class="text-sm text-red-500">NOTE! Attached image thumbnail preview is supported in modern browsers.</p>

            <div>
              <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded">Upload</button>
            </div>
          </div>

          <div class="col-span-1">
            <p class="text-sm text-gray-600">Current avatar will be replaced. Max size 2MB. Allowed types: jpg, jpeg, png, gif.</p>
          </div>
        </form>
      </div>

      <!-- Tab: Password -->
      <div id="password" class="tab-content">
        <form action="<?= htmlspecialchars(basename(__FILE__)) ?>" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <input type="hidden" name="action" value="password">
          <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700">Current Password</label>
            <input type="password" name="current_password" class="mt-1 w-full px-4 py-2 border rounded">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">New Password</label>
            <input type="password" name="new_password" class="mt-1 w-full px-4 py-2 border rounded">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Re-type New Password</label>
            <input type="password" name="confirm_password" class="mt-1 w-full px-4 py-2 border rounded">
          </div>

          <div class="md:col-span-2">
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded">Change Password</button>
          </div>
        </form>
      </div>

    </div>
  </main>
</div>

<script>
  // Tabs
  document.querySelectorAll('.tab-btn').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
      document.querySelectorAll('.tab-content').forEach(tc=>tc.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById(btn.dataset.tab).classList.add('active');
    });
  });

  // Avatar preview for file input
  function previewFile(input) {
    const file = input.files && input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('previewImg').src = e.target.result;
    };
    reader.readAsDataURL(file);
  }
</script>

</body>
</html>
