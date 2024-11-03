<?php
include '../database/db.php'; // Sertakan koneksi database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Mencari pengguna berdasarkan email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Memeriksa password
    if ($user && password_verify($password, $user['password'])) {
        session_start();
        $_SESSION['user_id'] = $user['id']; // Simpan ID pengguna ke dalam session

        // Set cookie untuk "Remember Me" jika dipilih
        if (isset($_POST['remember'])) {
            setcookie("user_id", $user['id'], time() + (86400 * 30), "/"); // Cookie 30 hari
        }

        header("Location: ../dashboard.php"); // Redirect ke dashboard
        exit();
    } else {
        echo "<div class='alert alert-danger text-center'>Invalid email or password!</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <!-- Link ke CSS Bootstrap dan Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.5.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        /* Set the body background image */
        body {
            background-image: url('../uploads/background/bg.jpg');
            /* Replace with your image path */
            background-size: cover;
            /* Cover the entire screen */
            background-position: center;
            /* Center the image */
            position: relative;
            /* Needed for overlay positioning */
            height: 100vh;
            /* Full height */
        }

        .container {
            position: relative;
            /* Ensure container is positioned relative to the overlay */
            z-index: 1;
            /* Bring the container above the overlay */
        }
    </style>
</head>

<body>
    <div class="overlay"></div> <!-- Overlay for background effect -->

    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="card shadow" style="width: 25rem;">
            <div class="card-body">
                <h2 class="text-center mb-4">Login</h2>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" class="form-control" id="email" placeholder="Email" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" class="form-control" id="password" placeholder="Password" required>
                            <span class="input-group-text" onclick="togglePassword()" style="cursor: pointer;">
                                <i class="bi bi-eye" id="togglePasswordIcon"></i>
                            </span>
                        </div>
                    </div>
                    <div class="mb-3 d-flex justify-content-between align-items-center">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                        <a href="forget_password.php" class="text-decoration-none">Forget Password?</a>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
                <p class="text-center mt-3">
                    Don't have an account? <a href="register.php">Register here</a>
                </p>
            </div>
        </div>
    </div>

    <!-- Link ke JavaScript Bootstrap dan JavaScript Lainnya -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-F9RfvvZPc5mHTPABM5WPCbFN+vUE56tJ8j/m1B1z1ofz3y8vMZj09hKX1MyIxTUG" crossorigin="anonymous"></script>
    <script>
        function togglePassword() {
            const passwordField = document.getElementById("password");
            const togglePasswordIcon = document.getElementById("togglePasswordIcon");

            if (passwordField.type === "password") {
                passwordField.type = "text";
                togglePasswordIcon.classList.remove("bi-eye");
                togglePasswordIcon.classList.add("bi-eye-slash");
            } else {
                passwordField.type = "password";
                togglePasswordIcon.classList.remove("bi-eye-slash");
                togglePasswordIcon.classList.add("bi-eye");
            }
        }
    </script>
</body>

</html>