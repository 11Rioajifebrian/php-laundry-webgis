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
                background-color: #EEEDEB;
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

            /* Mengatur margin konten utama */
            .content {
                margin-left: 280px;
                padding: 20px;
                transition: margin-left 0.3s ease;
            }

            .content.shifted {
                margin-left: 0;
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

            /* Style untuk card */
            .card {
                margin-bottom: 20px;
                border-radius: 15px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            }

            /* Card jumlah laundry */
            .card-laundry {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 20px;
            }

            /* Style untuk kalender */
            .calendar {
                display: grid;
                grid-template-columns: repeat(7, 1fr);
                gap: 10px;
                text-align: center;
                margin-top: 10px;
            }

            .calendar .day {
                padding: 15px;
                border: 1px solid #ccc;
                border-radius: 5px;
                background-color: #f0f0f0;
                transition: background-color 0.3s;
            }

            .calendar .day:hover {
                background-color: #d1e7dd;
            }

            .calendar .header {
                font-weight: bold;
                background-color: #007bff;
                color: white;
                padding: 10px;
                border-radius: 5px;
            }

            .calendar .today {
                background-color: #ffc107;
                color: black;
                font-weight: bold;
            }

            /* Style untuk peta */
            #map {
                height: 400px;
                width: 100%;
                border-radius: 15px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            }

            /* Responsive adjustments */
            @media (max-width: 768px) {
                .content {
                    margin-left: 0;
                }
            }

            .card h5 {
                margin-top: 10px;
                margin-left: 10px;
            }

            .view-link {
                position: relative;
                display: inline-block;
                padding: 10px;
                margin-top: 5px;
                transition: background-color 0.3s;
                float: right;
            }

            .view-link span {
                margin-left: 5px;
            }
        </style>
    </head>

    <body>
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
                    <a class="nav-link active" href="dashboard.php">
                        Home
                        <i class="bi bi-house-door"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="read.php">
                        Daftar Laundry
                        <i class="bi bi-list-ul"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="users/profile.php">
                        Profile
                        <i class="bi bi-person"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="users/logout.php">
                        Logout
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Konten Utama -->
        <div class="content" id="content">
            <h2>Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h2>

            <div class="row">
                <!-- Card Jumlah Laundry Tersedia Kiri -->
                <div class="col-md-6">
                    <a href="daftar_laundry.php" class="text-decoration-none">
                        <div class="card card-laundry text-white bg-primary mb-4">
                            <div class="card-body">
                                <h5 class="card-title">Jumlah Laundry Tersedia</h5>
                                <p class="card-text" style="text-align: center;"><?php echo htmlspecialchars($laundryCount); ?> laundry</p>
                            </div>
                        </div>
                    </a>

                    <!-- Kalender Bulan Sekarang -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary">
                            <h5 class="card-title" style="color:white;">Kalender Bulan <?php echo date('F Y'); ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="calendar">
                                <?php
                                // Nama hari dalam seminggu
                                $daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                                foreach ($daysOfWeek as $day) {
                                    echo '<div class="header">' . $day . '</div>';
                                }

                                // Menampilkan hari kosong sebelum tanggal pertama
                                $firstDayOfMonth = strtotime("$year-$month-01");
                                $daysInWeek = date('w', $firstDayOfMonth);
                                for ($i = 0; $i < $daysInWeek; $i++) {
                                    echo '<div class="day"></div>';
                                }

                                // Menampilkan hari dalam bulan
                                for ($day = 1; $day <= $daysInMonth; $day++) {
                                    $today = date('d');
                                    if ($day == $today && $month == date('m') && $year == date('Y')) {
                                        echo '<div class="day today">' . $day . '</div>';
                                    } else {
                                        echo '<div class="day">' . $day . '</div>';
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Peta Laundry di Samping Kalender -->
                <div class="col-md-6">

                    <div class="card mb-4">
                        <h5>Lokasi Laundry</h5>
                        <div class="card-body">
                            <div id="map"></div>
                            <a href="read.php" class="view-link">
                                View &#8594;
                            </a>
                        </div>
                    </div>
                    <div class="card mt-3"> <!-- mt-3 untuk margin top -->
                        <div class="card-body">
                            <h5 style="margin-left: 15px;">Jam</h5>
                            <h2 id="current-time" style="display: inline; margin-left: 20px;"></h2>
                            <span style="margin-left: 10px;"> |</span> <!-- Pemisah antara jam dan tanggal -->
                            <span id="current-date" style="display: inline;"></span> <!-- Menambahkan elemen untuk menampilkan tanggal -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function toggleSidebar() {
                const sidebar = document.getElementById('sidebar');
                const content = document.getElementById('content');
                sidebar.classList.toggle('hidden');
                content.classList.toggle('shifted');
            }

            var map = L.map('map', {
                center: [-0.05927462695943814, 109.35214737998321],
                zoom: 13,
                zoomControl: false
            });

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(map);

            L.control.zoom({
                position: 'topright'
            }).addTo(map);

            var markers = [];
            <?php foreach ($laundrys as $laundry): ?>
                var marker = L.marker([<?= $laundry['lat']; ?>, <?= $laundry['lng']; ?>])
                    .addTo(map)
                    .bindPopup('<b><?= htmlspecialchars($laundry['name']); ?></b><br><?= htmlspecialchars($laundry['address']); ?><br><?= htmlspecialchars($laundry['description']); ?>');
                markers.push(marker);
            <?php endforeach; ?>

            // Fungsi untuk menampilkan hari, tanggal, dan jam saat ini
            function updateTime() {
                var now = new Date();

                // Format jam dan menit
                var hours = now.getHours().toString().padStart(2, '0');
                var minutes = now.getMinutes().toString().padStart(2, '0');
                var seconds = now.getSeconds().toString().padStart(2, '0');

                // Mendapatkan nama hari dan tanggal
                var options = {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                };
                var dateString = now.toLocaleDateString('id-ID', options); // Menggunakan format Indonesia

                // Mengupdate konten
                document.getElementById('current-time').textContent = hours + ':' + minutes + ':' + seconds;
                document.getElementById('current-date').textContent = dateString; // Menampilkan tanggal
            }

            // Memperbarui jam setiap detik
            setInterval(updateTime, 1000);
            updateTime();
        </script>

        <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap" async defer></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-wXN2qS7uXyHP0LZtT5I7dR8/jd7sJXNXc5QjtBB+QfHhVazcvG6eEtx+9GgG9U8H" crossorigin="anonymous"></script>
    </body>

    </html>