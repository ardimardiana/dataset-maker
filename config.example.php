<?php
// config.php

// 1. Definisikan Konstanta Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'DB_NAME');
define('DB_USER', 'DB_USER'); // Sesuaikan dengan user database Anda
define('DB_PASS', 'DB_PASS'); // Sesuaikan dengan password database Anda
define('GOD_MODE', 'GOD_MODE');

// Anda bisa menambahkan konstanta global lainnya di sini nanti
// Contoh:
define('APP_NAME', 'Diarization RTTM Editor');
define('BASE_URL', 'http://localhost/diarization/');
define('MAX_UPLOAD_SIZE', 5000000); // contoh untuk 5MB

// 2. Koneksi ke Database menggunakan Konstanta
try {
    // Perhatikan penggunaan titik (.) untuk menyambung string dan konstanta
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    
    // Aktifkan mode exception untuk error PDO (standar PHP 8)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Setting fetch mode ke object atau associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}
?>