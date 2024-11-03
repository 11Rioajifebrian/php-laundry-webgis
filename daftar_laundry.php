<?php
session_start(); // Memulai session

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: users/login.php"); // Jika belum login, redirect ke halaman login
    exit();
}
$connection = mysqli_connect('localhost', 'root', 'root', 'laundry');
if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
}
function send_query($query)
{
    global $connection;
    $result = mysqli_query($connection, $query);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

// Mengambil data pengguna dari database
include 'database/db.php'; // Perbarui path ke db.php

// Cek apakah koneksi berhasil
if (!$pdo) {
    die("Koneksi ke database gagal.");
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User not found.";
    exit();
}

// Tentukan gambar profil default jika pengguna belum mengunggah gambar
$profilePicture = $user['profile_picture'] ? $user['profile_picture'] : 'https://via.placeholder.com/80';

// Mengambil jumlah laundry yang tersedia
$laundryQuery = $pdo->query("SELECT COUNT(*) AS total FROM laundrys"); // Ganti dengan nama tabel yang sesuai
$laundryCount = $laundryQuery->fetch(PDO::FETCH_ASSOC)['total'];

// Mengambil lokasi laundry dari database
$laundryLocationsQuery = $pdo->query("SELECT name, ST_AsText(coordinate) AS coordinate FROM laundrys"); // Ganti dengan nama tabel yang sesuai
$laundryLocations = $laundryLocationsQuery->fetchAll(PDO::FETCH_ASSOC);

// Mengatur zona waktu ke Indonesia Bagian Barat (WIB)
date_default_timezone_set('Asia/Jakarta');
// Mendapatkan bulan dan tahun saat ini
$month = date('m');
$year = date('Y');
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

$laundrys = send_query("SELECT id, name, address, description, ST_X(coordinate) AS lat, ST_Y(coordinate) AS lng FROM laundrys");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- Link ke CSS Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }

        .content {
            padding: 20px;
        }

        .card {
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
            /* Menghilangkan border default */
            border-radius: 10px;
            /* Rounded corners */
        }

        .card:hover {
            transform: translateY(-5px);
            /* Efek mengangkat card saat hover */
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            /* Bayangan lebih dalam */
        }

        .card h5 {
            font-size: 1.25rem;
            /* Ukuran font judul */
            margin-top: 10px;
            margin-bottom: 15px;
            /* Jarak bawah yang lebih besar */
            font-weight: bold;
        }

        .card-text {
            color: #6c757d;
            /* Warna teks yang lebih netral */
        }

        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            /* Warna saat hover */
            border-color: #545b62;
            /* Border saat hover */
        }

        .mb-4 {
            margin-bottom: 1.5rem;
            /* Jarak bawah yang lebih besar untuk elemen mb-4 */
        }

        h2 {
            color: #007bff;
            /* Warna biru untuk judul */
            font-weight: 700;
            /* Berat font judul */
        }

        /* Responsif untuk layout card */
        @media (max-width: 768px) {
            .card {
                margin-bottom: 20px;
                /* Tambah margin bawah untuk card di mobile */
            }
        }
    </style>
</head>

<body>
    <div class="content" id="content">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mt-4">
                <h2>Daftar Laundry</h2>
                <a href="javascript:history.back()" class="btn btn-secondary">Kembali</a>
            </div>
            <div class="row mt-4">
                <?php foreach ($laundrys as $laundry): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm"> <!-- Menambahkan shadow ke card -->
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($laundry['name']); ?></h5>
                                <p class="card-text">
                                    <strong>Alamat:</strong> <?php echo htmlspecialchars($laundry['address']); ?><br>
                                    <strong>Deskripsi:</strong> <?php echo htmlspecialchars($laundry['description']); ?>
                                </p>
                                <a href="detail.php?id=<?php echo $laundry['id']; ?>" class="btn btn-primary">
                                    Lihat Detail
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        // JavaScript dan Leaflet JS seperti sebelumnya
        // ...
    </script>

    <!-- Link ke JS Google Maps -->
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap" async defer></script>
    <!-- Link ke JS Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-wXN2qS7uXyHP0LZtT5I7dR8/jd7sJXNXc5QjtBB+QfHhVazcvG6eEtx+9GgG9U8H" crossorigin="anonymous"></script>
</body>

</html>