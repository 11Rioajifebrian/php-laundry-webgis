<?php
// Koneksi database
$servername = "localhost";
$username = "root"; // sesuaikan dengan username database Anda
$password = "root"; // sesuaikan dengan password database Anda
$dbname = "laundry"; // sesuaikan dengan nama database Anda

// Membuat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Memeriksa koneksi
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Mengecek apakah ada ID yang dikirim melalui URL untuk mengedit data
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Menyiapkan dan mengeksekusi query untuk mendapatkan data lokasi berdasarkan ID
    $stmt = $conn->prepare("SELECT * FROM laundrys WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $location = $result->fetch_assoc();

    if (!$location) {
        echo "Data tidak ditemukan.";
        exit;
    }

    // Mengecek apakah form telah dikirim untuk memperbarui data
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['name'];
        $address = $_POST['address'];
        $description = $_POST['description'];
        $latitude = $_POST['latitude'];
        $longitude = $_POST['longitude'];

        // Menyiapkan dan mengeksekusi query untuk memperbarui data
        $update_stmt = $conn->prepare("UPDATE laundrys SET name = ?, address = ?, description = ?, coordinate = ST_GeomFromText('POINT($latitude $longitude)', 4326) WHERE id = ?");
        $update_stmt->bind_param("sssi", $name, $address, $description, $id);

        if ($update_stmt->execute()) {
            echo "Data berhasil diperbarui!";
            // Redirect ke halaman lain setelah pembaruan
            header("Location: read.php");
            exit;
        } else {
            echo "Gagal memperbarui data: " . $conn->error;
        }
    }
} else {
    echo "ID tidak ditemukan.";
    exit;
}

// Menyiapkan nilai latitude dan longitude untuk fallback
$latitude = !empty($location['latitude']) ? $location['latitude'] : -0.05927462695943814; // Latitude default
$longitude = !empty($location['longitude']) ? $location['longitude'] : 109.35214737998321; // Longitude default
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Laundry Location</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

    <style>
        html,
        body {
            height: 100%;
            margin: 0;
        }

        #map {
            height: 100vh;
            width: 100vw;
        }

        .card {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 1000;
            width: 400px;
            padding: 20px;
            bottom: 20px;
        }

        #description {
            height: 100px;
            resize: none;
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Update Laundry Location</h5>
            <form action="" method="post">
                <div class="form-group">
                    <label for="name">Laundry Name</label>
                    <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($location['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" class="form-control" value="<?php echo htmlspecialchars($location['address']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" required><?php echo htmlspecialchars($location['description']); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="latitude">Latitude</label>
                    <input type="text" id="latitude" name="latitude" class="form-control" value="<?php echo htmlspecialchars($latitude); ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="longitude">Longitude</label>
                    <input type="text" id="longitude" name="longitude" class="form-control" value="<?php echo htmlspecialchars($longitude); ?>" readonly>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Update</button>
                <a href="read.php" class="btn btn-link btn-block">View Locations</a>
            </form>
        </div>
    </div>

    <div id="map"></div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var map = L.map('map', {
                center: [<?php echo $latitude; ?>, <?php echo $longitude; ?>],
                zoom: 13,
                zoomControl: false
            });

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(map);

            var marker = L.marker([<?php echo $latitude; ?>, <?php echo $longitude; ?>]).addTo(map);

            map.on('click', function(e) {
                if (marker) {
                    map.removeLayer(marker);
                }
                marker = L.marker(e.latlng).addTo(map);

                document.getElementById('latitude').value = e.latlng.lat;
                document.getElementById('longitude').value = e.latlng.lng;
            });
        });
    </script>
</body>

</html>