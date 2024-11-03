<?php
session_start(); // Memulai session di awal

include '../database/db.php'; // Sertakan koneksi database

// Cek jika pengguna telah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect ke halaman login jika belum login
    exit();
}

// Ambil ID pengguna dari session
$userId = $_SESSION['user_id'];

// Query untuk mengambil data pengguna
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    // Jika pengguna tidak ditemukan, arahkan kembali ke dashboard
    header("Location: dashboard.php");
    exit();
}
$profilePicture = $user['profile_picture'] ? $user['profile_picture'] : 'https://via.placeholder.com/80';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <!-- Link ke CSS Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <style>
        body {
            background-color: #f1f5f9;
            display: flex;
            align-items: flex-start;
            justify-content: flex-start;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            transition: padding-left 0.3s ease;
        }

        /* Style untuk sidebar */
        .sidebar {
            position: fixed;
            top: 20px;
            left: 20px;
            height: calc(100vh - 40px);
            width: 250px;
            background-color: #343a40;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            color: white;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .sidebar.hidden {
            width: 0;
            padding: 0;
            overflow: hidden;
        }

        .sidebar .profile-section {
            text-align: center;
            margin-bottom: 20px;
        }

        .sidebar .profile-section img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
        }

        .sidebar .nav-link {
            color: white;
            padding: 10px;
            width: 100%;
            text-align: left;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sidebar .nav-link.active {
            background-color: #495057;
            border-radius: 5px;
        }

        .sidebar .nav-link:hover {
            background-color: #495057;
            border-radius: 5px;
        }

        /* Flex container for main content */
        .content {
            flex: 1;
            /* Allow content to take up remaining space */
            display: flex;
            justify-content: center;
            /* Center content horizontally */
            align-items: center;
            /* Center content vertically */
            margin-left: 250px;
            /* Default margin when sidebar is open */
            transition: margin-left 0.3s ease;
            /* Smooth transition for margin */
        }

        .content.hidden {
            margin-left: 0;
            /* Full width when sidebar is hidden */
        }

        /* Full-width card */
        .card {
            border-radius: 12px;
            box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.1);
            background: linear-gradient(135deg, #ffffff, #f8f9fa);
            padding: 20px;
            width: 100%;
            /* Make the card responsive */
            max-width: 500px;
            /* Set a max width for the card */
            margin: 20px;
            /* Margin around the card */
            transition: margin-left 0.3s ease;
            /* Smooth transition */
        }

        /* Adjust image size in the card */
        .card img {
            border-radius: 50%;
            object-fit: cover;
            height: 120px;
            width: 120px;
            margin: 20px auto;
        }

        /* Tombol toggle sidebar */
        .toggle-btn {
            position: fixed;
            top: 40px;
            left: 40px;
            z-index: 1000;
            border: none;
            background-color: #343a40;
            color: white;
            cursor: pointer;
            border-radius: 5px;
            padding: 10px 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>

<body>
    <!-- Tombol untuk toggle sidebar -->
    <button class="toggle-btn" onclick="toggleSidebar()">☰</button>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <!-- Profile Section -->
        <div class="profile-section">
            <img src="<?php echo htmlspecialchars($profilePicture); ?>" alt="Profile Image">
            <h5><?php echo htmlspecialchars($user['name']); ?></h5>
            <p><?php echo htmlspecialchars($user['email']); ?></p>
        </div>

        <!-- Navigation Links -->
        <ul class="nav flex-column w-100">
            <li class="nav-item">
                <a class="nav-link" href="../dashboard.php">
                    Home
                    <i class="bi bi-house-door"></i>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../read.php">
                    Daftar Laundry
                    <i class="bi bi-list-ul"></i>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="profile.php">
                    Profile
                    <i class="bi bi-person"></i>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="logout.php">
                    Logout
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </li>
        </ul>
    </div>

    <div class="content" id="main-content">
        <div class="card">
            <img src="<?= htmlspecialchars($user['profile_picture'] ?: 'path/to/default-profile-picture.png'); ?>" alt="Profile Picture">
            <div class="card-body" style="text-align:center;">
                <h2 class="card-title"><?= htmlspecialchars($user['name']); ?></h2>
                <p class="card-text"><strong>Username:</strong> <?= htmlspecialchars($user['username']); ?></p>
                <p class="card-text"><strong>Email:</strong> <?= htmlspecialchars($user['email']); ?></p>
                <p class="card-text"><strong>Phone Number:</strong> <?= htmlspecialchars($user['phone_number']); ?></p>
                <p class="card-text"><strong>Birthdate:</strong> <?= htmlspecialchars($user['birthdate']); ?></p>
                <p class="card-text"><strong>Gender:</strong> <?= htmlspecialchars($user['gender']); ?></p>
                <a href="edit_profile.php" class="btn btn-primary">Edit Profile</a>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('hidden');

            const mainContent = document.getElementById('main-content');
            const content = document.getElementById('main-content');
            content.classList.toggle('hidden');

            if (sidebar.classList.contains('hidden')) {
                sidebar.style.width = '0';
                sidebar.style.padding = '0';
            } else {
                sidebar.style.width = '250px';
                sidebar.style.padding = '20px';
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-F9RfvvZPc5mHTPABM5WPCbFN+vUE56tJ8j/m1B1z1ofz3y8vMZj09hKX1MyIxTUG" crossorigin="anonymous"></script>
</body>

</html>