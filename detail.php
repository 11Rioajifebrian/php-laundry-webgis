<?php
session_start();

// Koneksi ke database MySQL
$connection = mysqli_connect('localhost', 'root', 'root', 'laundry');
if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
}

if (!isset($_SESSION['user_id'])) {
    header("Location: users/login.php");
    exit();
}

// Ambil ID laundry dari URL
$laundry_id = $_GET['id'] ?? null;

// Pastikan ID laundry valid
if (!$laundry_id) {
    echo "ID Laundry tidak ditemukan.";
    exit();
}

// Query untuk mengambil data detail laundry berdasarkan ID
$query = "SELECT id, name, address, description, ST_X(coordinate) AS lat, ST_Y(coordinate) AS lng FROM laundrys WHERE id = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param("i", $laundry_id);
$stmt->execute();
$result = $stmt->get_result();
$laundry = $result->fetch_assoc();

if (!$laundry) {
    echo "Laundry tidak ditemukan.";
    exit();
}

// Tutup koneksi setelah pengambilan data
$stmt->close();
mysqli_close($connection);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Laundry - <?php echo htmlspecialchars($laundry['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body style="background-color: #f8f9fa;">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-primary text-white text-center">
                        <h2 class="mb-0">Detail Laundry</h2>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title text-center display-6 mb-3"><?php echo htmlspecialchars($laundry['name']); ?></h5>
                        <p class="card-text">
                            <strong>Alamat:</strong> <?php echo htmlspecialchars($laundry['address']); ?>
                        </p>
                        <p class="card-text">
                            <strong>Deskripsi:</strong> <?php echo htmlspecialchars($laundry['description']); ?>
                        </p>
                        <p class="card-text">
                            <strong>Koordinat:</strong> <?php echo htmlspecialchars($laundry['lat']) . ", " . htmlspecialchars($laundry['lng']); ?>
                        </p>
                        <a href="javascript:history.back()" class="btn btn-secondary mt-3 w-100">Kembali ke Daftar Laundry</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js"></script>
</body>

</html>