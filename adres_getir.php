<?php
require 'baglan.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $islem = $_GET['islem'] ?? '';

    if ($islem == 'ilce') {
        $il_id = $_GET['il_id'] ?? 0;
        
        $sorgu = $db->prepare("SELECT * FROM ilce WHERE ilID = ? ORDER BY ilceisim ASC");
        $sorgu->execute([$il_id]);
        $ilceler = $sorgu->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($ilceler);
    } 
    elseif ($islem == 'mahalle') {
        $ilce_id = $_GET['ilce_id'] ?? 0;
        
        $sorgu = $db->prepare("SELECT * FROM mahalle WHERE milceID = ? ORDER BY mahalleisim ASC");
        $sorgu->execute([$ilce_id]);
        $mahalleler = $sorgu->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($mahalleler);
    }
} catch (PDOException $e) {
    echo json_encode(["hata" => $e->getMessage()]);
}
?>