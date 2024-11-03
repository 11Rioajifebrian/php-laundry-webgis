<?php
include '../database/db.php'; // Sertakan koneksi database

$usernameExists = false; // Variabel untuk mengecek status username

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Mengambil data dari form
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $name = $_POST['name'];
    $phone_number = $_POST['phone_number'];
    $birthdate = $_POST['birthdate'];
    $gender = $_POST['gender'];
    $profile_picture = null;

    // Cek jika file gambar diunggah
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profile_picture']['tmp_name'];
        $fileName = $_FILES['profile_picture']['name'];
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

        // Tentukan path penyimpanan gambar
        $newFileName = uniqid() . '.' . $fileExtension;
        $uploadFileDir = '../uploads/profile_pictures/';
        $dest_path = $uploadFileDir . $newFileName;

        // Pindahkan file ke folder server
        if (move_uploaded_file($fileTmpPath, $dest_path)) {
            $profile_picture = $dest_path;
        }
    }

    // Cek apakah username sudah ada di database
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $usernameExists = $stmt->fetchColumn();

    if (!$usernameExists) {
        // Persiapkan dan eksekusi statement untuk menyimpan data pengguna
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, name, phone_number, birthdate, gender, profile_picture) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $email, $password, $name, $phone_number, $birthdate, $gender, $profile_picture]);

        // Ambil ID pengguna yang baru terdaftar
        $userId = $pdo->lastInsertId();

        // Simpan ID pengguna ke dalam session
        session_start();
        $_SESSION['user_id'] = $userId;

        header("Location: ../dashboard.php"); // Redirect ke dashboard
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <!-- Link ke CSS Bootstrap -->
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
            background-image: url('../uploads/background/bg.jpg');
            /* Replace with your image path */
            background-size: cover;
            /* Cover the entire screen */
            background-position: center;
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
            <h2>Register</h2>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" id="username" placeholder="Username" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" id="email" placeholder="Email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" id="password" placeholder="Password" required>
                </div>
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" id="name" placeholder="Name" required>
                </div>
                <div class="mb-3">
                    <label for="phone_number" class="form-label">Phone Number</label>
                    <input type="text" name="phone_number" class="form-control" id="phone_number" placeholder="Phone Number" required>
                </div>
                <div class="mb-3">
                    <label for="birthdate" class="form-label">Birthdate</label>
                    <input type="date" name="birthdate" class="form-control" id="birthdate" required>
                </div>
                <div class="mb-3">
                    <label for="gender" class="form-label">Gender</label>
                    <select name="gender" class="form-select" id="gender" required>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="profile_picture" class="form-label">Profile Picture</label>
                    <input type="file" name="profile_picture" class="form-control" id="profile_picture" accept="image/*">
                </div>
                <button type="submit" class="btn btn-primary w-100">Register</button>
            </form>
            <p class="text-center mt-3">
                Already have an account? <a href="login.php">Login here</a>
            </p>
        </div>
    </div>

    <!-- Modal Notifikasi Username Sudah Terdaftar -->
    <?php if ($usernameExists): ?>
        <div class="modal fade" id="usernameExistsModal" tabindex="-1" aria-labelledby="usernameExistsModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="usernameExistsModalLabel">Notification</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Username sudah terdaftar. Silakan gunakan username lain.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Link ke JavaScript Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-F9RfvvZPc5mHTPABM5WPCbFN+vUE56tJ8j/m1B1z1ofz3y8vMZj09hKX1MyIxTUG" crossorigin="anonymous"></script>
    <script>
        // Tampilkan modal jika username sudah terdaftar
        <?php if ($usernameExists): ?>
            var usernameExistsModal = new bootstrap.Modal(document.getElementById('usernameExistsModal'));
            usernameExistsModal.show();
        <?php endif; ?>
    </script>
</body>

</html>