<?php
session_start(); // Start the session

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: users/login.php"); // Redirect to login page if not logged in
    exit();
}

include '../database/db.php'; // Include database connection

// Fetch user data from database
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User not found.";
    exit();
}

$usernameExists = false; // Variable to check username status

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get data from form
    $username = $_POST['username'];
    $email = $_POST['email'];
    $name = $_POST['name'];
    $phone_number = $_POST['phone_number'];
    $birthdate = $_POST['birthdate'];
    $gender = $_POST['gender'];
    $profile_picture = $user['profile_picture']; // Default to current profile picture

    // Check if a new profile picture is uploaded
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profile_picture']['tmp_name'];
        $fileName = $_FILES['profile_picture']['name'];
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

        // Set file path for image upload
        $newFileName = uniqid() . '.' . $fileExtension;
        $uploadFileDir = '../uploads/profile_pictures/';
        $dest_path = $uploadFileDir . $newFileName;

        // Move file to server folder
        if (move_uploaded_file($fileTmpPath, $dest_path)) {
            $profile_picture = $dest_path; // Update profile picture path
        }
    }

    // Check if username already exists in database
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
    $stmt->execute([$username, $_SESSION['user_id']]);
    $usernameExists = $stmt->fetchColumn();

    if (!$usernameExists) {
        // Prepare and execute statement to update user data
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, name = ?, phone_number = ?, birthdate = ?, gender = ?, profile_picture = ? WHERE id = ?");
        $stmt->execute([$username, $email, $name, $phone_number, $birthdate, $gender, $profile_picture, $_SESSION['user_id']]);

        header("Location: ../dashboard.php"); // Redirect to dashboard
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <style>
        body {
            background-color: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        .card {
            width: 100%;
            max-width: 480px;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.1);
            background: linear-gradient(135deg, #ffffff, #f8f9fa);
            margin: 20px;
        }

        .form-label {
            font-weight: 600;
            color: #495057;
        }

        .btn-primary {
            background-color: #0d6efd;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
        }

        .card h2 {
            margin-bottom: 1rem;
            text-align: center;
        }

        .text-center a {
            color: #0d6efd;
            font-weight: 500;
        }

        .text-center a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="card-body">
            <h2>Edit Profile</h2>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" id="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="phone_number" class="form-label">Phone Number</label>
                    <input type="text" name="phone_number" class="form-control" id="phone_number" value="<?php echo htmlspecialchars($user['phone_number']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="birthdate" class="form-label">Birthdate</label>
                    <input type="date" name="birthdate" class="form-control" id="birthdate" value="<?php echo htmlspecialchars($user['birthdate']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="gender" class="form-label">Gender</label>
                    <select name="gender" class="form-select" id="gender" required>
                        <option value="Male" <?php echo ($user['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo ($user['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo ($user['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="profile_picture" class="form-label">Profile Picture</label>
                    <input type="file" name="profile_picture" class="form-control" id="profile_picture" accept="image/*">
                </div>
                <button type="submit" class="btn btn-primary w-100">Update Profile</button>
            </form>
            <p class="text-center mt-3">
                <a href="../dashboard.php">Cancel</a>
            </p>
        </div>
    </div>

    <!-- Modal Notification Username Already Exists -->
    <?php if ($usernameExists): ?>
        <div class="modal fade" id="usernameExistsModal" tabindex="-1" aria-labelledby="usernameExistsModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="usernameExistsModalLabel">Notification</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Username already exists. Please choose a different username.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Link to Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-F9RfvvZPc5mHTPABM5WPCbFN+vUE56tJ8j/m1B1z1ofz3y8vMZj09hKX1MyIxTUG" crossorigin="anonymous"></script>
    <script>
        // Show modal if username already exists
        <?php if ($usernameExists): ?>
            var usernameExistsModal = new bootstrap.Modal(document.getElementById('usernameExistsModal'));
            usernameExistsModal.show();
        <?php endif; ?>
    </script>
</body>

</html>