<?php
session_start(); // Memulai session di awal

// Koneksi ke database MySQL
$connection = mysqli_connect('localhost', 'root', 'root', 'laundry');
if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
}

// Fungsi untuk menjalankan query
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

// Cek jika session tidak ada, arahkan kembali ke halaman login
if (!isset($_SESSION['user_id'])) {
    header("Location: users/login.php");
    exit();
}

// Pastikan koneksi ke database untuk PDO sudah benar
include 'database/db.php'; // Pastikan path ke db.php benar

// Cek apakah koneksi berhasil
if (!$pdo) {
    die("Koneksi ke database gagal.");
}

// Mengambil data pengguna berdasarkan user_id yang tersimpan di session
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User not found.";
    exit();
}

// Tentukan gambar profil default jika pengguna belum mengunggah gambar
$profilePicture = $user['profile_picture'] ? $user['profile_picture'] : 'https://via.placeholder.com/80';

// Mengambil daftar laundry dari database
$laundrys = send_query("SELECT id, name, address, description, ST_X(coordinate) AS lat, ST_Y(coordinate) AS lng FROM laundrys");

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Laundry</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
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
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .sidebar.hidden {
            width: 0;
            overflow: hidden;
            padding: 0;
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

        .sidebar .profile-section h5 {
            margin: 0;
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

        /* Geser map agar tidak tertutup sidebar */
        #map {
            height: 100vh;
            width: calc(100% - 310px);
            /* Adjust width for a slight gap between sidebar and map */
            margin-left: 290px;
            /* Matches the sidebar width for consistent spacing */
            transition: width 0.3s ease, margin-left 0.3s ease;
            /* Smooth transition */
        }

        /* Geser overlay card agar tidak tumpang tindih dengan sidebar */
        .overlay-card {
            position: absolute;
            top: 80px;
            bottom: 20px;
            /* Sama dengan jarak atas sidebar */
            left: 320px;
            /* Tempatkan di sebelah sidebar */
            z-index: 1000;
            width: 400px;
            /* Lebar tetap untuk card overlay */
            max-height: calc(100vh - 40px);
            background-color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            transition: left 0.3s ease;
            /* Tambahkan transisi untuk perubahan halus */
        }


        .overlay-header {
            flex-shrink: 0;
        }

        .overlay-list {
            flex-grow: 1;
            overflow-y: auto;
            margin: 15px 0;
        }

        .overlay-footer {
            flex-shrink: 0;
        }

        .list-group-item {
            border: none;
            padding: 20px;
            margin-bottom: 12px;
            background-color: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .list-group-item .fw-bold {
            font-size: 1.2em;
            color: #343a40;
        }

        .list-group-item p {
            margin: 0;
            font-size: 1em;
            color: #6c757d;
        }

        .navbar-profile-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
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

        #map.full-width {
            width: 100% !important;
            margin-left: 0 !important;
        }

        .overlay-card.shifted-left {
            left: 60px !important;
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
                <a class="nav-link" href="dashboard.php">
                    Home
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="read.php">
                    Daftar Laundry
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="users/profile.php">
                    Profile
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="users/logout.php">
                    Logout
                </a>
            </li>
        </ul>
    </div>

    <div id="map"></div>

    <div class="overlay-card p-3">
        <div class="overlay-header">
            <h4 class="text-center">Daftar Laundry</h4>
        </div>

        <div class="overlay-list">
            <ul class="list-group list-group-flush">
                <?php foreach ($laundrys as $laundry): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center" data-id="<?= $laundry['id']; ?>">
                        <div>
                            <div class="fw-bold"><?= htmlspecialchars($laundry['name']); ?></div>
                            <p><small>Alamat: <?= htmlspecialchars($laundry["address"]); ?></small></p>
                        </div>
                        <div class="dropdown ms-auto">
                            <button class="btn btn-link text-dark" type="button" data-bs-toggle="dropdown" aria-expanded="false" onclick="event.stopPropagation();">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" data-bs-auto-close="outside">
                                <li>
                                    <a href="detail.php?id=<?= $laundry['id']; ?>" class="dropdown-item">
                                        <i class="bi bi-eye"></i> Detail
                                    </a>
                                </li>
                                <li>
                                    <a href="update.php?id=<?= $laundry['id']; ?>" class="dropdown-item">
                                        <i class="bi bi-pencil-square"></i> Update
                                    </a>
                                </li>
                                <li>
                                    <a href="delete.php?id=<?= $laundry['id']; ?>" class="dropdown-item text-danger">
                                        <i class="bi bi-trash"></i> Delete
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="overlay-footer d-flex justify-content-between">
            <a href="daftar_laundry.php" class="btn btn-success">Detail All Laundry</a>
            <a href="create.html" class="btn btn-primary">Add New Laundry</a>
        </div>
    </div>

    <script>
        var map = L.map('map', {
            center: [-0.05927462695943814, 109.35214737998321], // Adjust coordinates as needed
            zoom: 12
        });

        // Add OpenStreetMap layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Data for laundry markers
        var markerData = {};
        var markers = [];

        <?php foreach ($laundrys as $laundry): ?>
            var latLng = [<?= $laundry['lat']; ?>, <?= $laundry['lng']; ?>];
            var marker = L.marker(latLng).addTo(map).bindPopup('<b><?= htmlspecialchars($laundry['name']); ?></b><br>Alamat: <?= htmlspecialchars($laundry['address']); ?>');
            markerData[<?= $laundry['id']; ?>] = marker;
            markers.push(marker);
        <?php endforeach; ?>

        // Event listener for list item clicks to focus on marker
        document.querySelectorAll('.list-group-item').forEach(function(item) {
            item.addEventListener('click', function(event) {
                var laundryId = this.getAttribute('data-id');
                if (markerData[laundryId]) {
                    map.flyTo(markerData[laundryId].getLatLng(), 15, {
                        animate: true,
                        duration: 0.5
                    });
                    markerData[laundryId].openPopup();
                }
            });
        });

        // function toggleSidebar() {
        //     const sidebar = document.getElementById('sidebar');
        //     const content = document.getElementById('content');
        //     sidebar.classList.toggle('hidden');
        //     content.classList.toggle('shifted');
        // }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mapElement = document.getElementById('map');
            const overlayCard = document.querySelector('.overlay-card');

            // Toggle sidebar visibility
            sidebar.classList.toggle('hidden');

            // Adjust map and overlay card based on sidebar visibility
            if (sidebar.classList.contains('hidden')) {
                mapElement.classList.add('full-width');
                overlayCard.classList.add('shifted-left');
            } else {
                mapElement.classList.remove('full-width');
                overlayCard.classList.remove('shifted-left');
            }

            // Ensure map is resized to fill the available space
            setTimeout(() => {
                map.invalidateSize();
            }, 300); // Wait for sidebar transition to complete
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
</body>

</html>