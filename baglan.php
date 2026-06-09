<?php
date_default_timezone_set('Europe/Istanbul');
$host = 'localhost';
$dbname = 'kariyerlen'; 
$username = 'root'; 
$password = '';  

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die(json_encode(['durum' => 'hata', 'mesaj' => 'Veritabanı bağlantı hatası: ' . $e->getMessage()]));
}
?>