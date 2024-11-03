<?php
$connection = mysqli_connect('localhost', 'root', 'root', 'laundry');

if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
}

$name = $_POST['name'];
$address = $_POST['address'];
$description = $_POST['description'];
$latitude = $_POST['latitude'];
$longitude = $_POST['longitude'];

$query = "
    INSERT INTO laundrys (name, address, description, coordinate)
    VALUES ('$name','$address','$description', ST_GeomFromText('POINT($latitude $longitude)', 4326))
";

// Jalankan query dan cek hasilnya
if (mysqli_query($connection, $query)) {
    // Redirect ke halaman read.php jika insert berhasil
    header("Location: read.php");
    exit();
} else {
    echo "Error: " . $query . "<br>" . mysqli_error($connection);
}

// Tutup koneksi
mysqli_close($connection);
