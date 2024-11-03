<?php
include '../database/db.php'; // Sertakan koneksi database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    // Mencari pengguna berdasarkan email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Logika untuk mengirim email dengan tautan reset password
        // Ini hanya contoh, Anda perlu mengganti dengan logika pengiriman email yang sesuai
        $resetToken = bin2hex(random_bytes(50)); // Buat token reset yang unik
        $resetUrl = "https://yourdomain.com/reset_password.php?token=" . $resetToken; // Ganti dengan URL reset Anda

        // Simpan token reset di database untuk validasi nanti (tambahkan kolom di tabel Anda jika perlu)
        // $stmt = $pdo->prepare("UPDATE users SET reset_token = ? WHERE email = ?");
        // $stmt->execute([$resetToken, $email]);

        // Kirim email
        // mail($email, "Reset Password", "Click here to reset your password: $resetUrl");

        echo "<div class='alert alert-success text-center'>Check your email for a link to reset your password!</div>";
    } else {
        echo "<div class='alert alert-danger text-center'>Email not found!</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forget Password</title>
    <!-- Link ke CSS Bootstrap dan Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="card shadow" style="width: 25rem;">
            <div class="card-body">
                <h2 class="text-center mb-4">Forget Password</h2>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" id="email" placeholder="Email" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
                </form>
                <p class="text-center mt-3">
                    Remembered your password? <a href="login.php">Login here</a>
                </p>
            </div>
        </div>
    </div>

    <!-- Link ke JavaScript Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>