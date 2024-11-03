<?php
session_start(); // Memulai session di awal

// Koneksi ke database MySQL
$connection = mysqli_connect('localhost', 'root', 'root', 'laundry');
if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
}

// Cek jika session tidak ada, arahkan kembali ke halaman login
if (!isset($_SESSION['user_id'])) {
    header("Location: users/login.php");
    exit();
}

// Cek jika ada parameter 'id' di URL
if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // Mengambil id dari URL dan memastikan itu adalah integer

    // Query untuk menghapus laundry
    $query = "DELETE FROM laundrys WHERE id = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id);

    if (mysqli_stmt_execute($stmt)) {
        // Redirect ke halaman daftar laundry setelah berhasil dihapus
        header("Location: read.php?message=Laundry berhasil dihapus.");
        exit();
    } else {
        echo "Error deleting record: " . mysqli_error($connection);
    }
    mysqli_stmt_close($stmt);
} else {
    echo "ID laundry tidak ditemukan.";
}

// Menutup koneksi
mysqli_close($connection);
