<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
session_name('kariyer_admin');
    session_start();

function sendJson($data) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

if(!isset($_SESSION['kullaniciID']) || $_SESSION['krolID'] != 3) {
    sendJson(['hata' => 'Yetkisiz işlem']);
}
require '../baglan.php';

if(isset($_POST['islem'])) {
    $islem = $_POST['islem'];
} elseif(isset($_GET['islem'])) {
    $islem = $_GET['islem'];
} else {
    sendJson(['hata' => 'İşlem parametresi bulunamadı. Lütfen formu kontrol edin.']);
}

if($islem == 'editor_resim_yukle') {
    $upload_dir = "../img/blog/icerik/";
    if (!file_exists($upload_dir)) {
        @mkdir($upload_dir, 0777, true);
    }
    
    $dosya = isset($_FILES['upload']) ? $_FILES['upload'] : (isset($_FILES['file']) ? $_FILES['file'] : null);
    
    if($dosya && $dosya['error'] == 0) {
        $uzanti = strtolower(pathinfo($dosya['name'], PATHINFO_EXTENSION));
        $yeni_ad = "icerik_" . time() . "_" . rand(1000,9999) . "." . $uzanti;
        
        if(@move_uploaded_file($dosya['tmp_name'], $upload_dir . $yeni_ad)) {
            $fileUrl = '/kariyer/img/blog/icerik/' . $yeni_ad;
            sendJson([
                'location' => $fileUrl, // TinyMCE için
                'url' => $fileUrl       // CKEditor 5 için
            ]);
        } else {
            http_response_code(500);
            sendJson(['error' => 'Resim yüklenemedi']);
        }
    } else {
        http_response_code(400);
        sendJson(['error' => 'Geçersiz dosya']);
    }
}
elseif($islem == 'blog_getir') {
    $id = intval($_POST['id']);
    $sorgu = $db->prepare("SELECT * FROM blog WHERE id = ?");
    $sorgu->execute([$id]);
    $blog = $sorgu->fetch(PDO::FETCH_ASSOC);
    
    if($blog) {
        sendJson($blog);
    } else {
        sendJson(['hata' => 'Blog bulunamadı']);
    }
}
elseif($islem == 'blog_sil') {
    $id = intval($_POST['id']);
    
    // Resim varsa sil
    $sorgu = $db->prepare("SELECT resim FROM blog WHERE id = ?");
    $sorgu->execute([$id]);
    $resim = $sorgu->fetchColumn();
    
    if($resim && file_exists("../" . $resim)) {
        @unlink("../" . $resim);
    }
    
    $sil_sorgu = $db->prepare("DELETE FROM blog WHERE id = ?");
    if($sil_sorgu->execute([$id])) {
        sendJson(['durum' => 'basarili']);
    } else {
        sendJson(['hata' => 'Silme başarısız']);
    }
}
elseif($islem == 'blog_sirala') {
    $order = isset($_POST['order']) ? json_decode($_POST['order'], true) : [];
    if(is_array($order) && count($order) > 0) {
        $total = count($order);
        $sorgu = $db->prepare("UPDATE blog SET sira=? WHERE id=?");
        foreach($order as $index => $id) {
            // The first element in the array is at the top, so it gets the highest 'sira' value
            $puan = $total - $index;
            $sorgu->execute([$puan, intval($id)]);
        }
        sendJson(['durum' => 'basarili']);
    } else {
        sendJson(['hata' => 'Geçersiz sıralama verisi']);
    }
}
elseif($islem == 'blog_kaydet') {
    $id = isset($_POST['blog_id']) ? intval($_POST['blog_id']) : 0;
    $sira = isset($_POST['sira']) ? intval($_POST['sira']) : 0;
    $baslik = isset($_POST['baslik']) ? trim($_POST['baslik']) : '';
    $kategori = isset($_POST['kategori']) ? trim($_POST['kategori']) : '';
    $tarih = isset($_POST['tarih']) ? $_POST['tarih'] : '';
    $ozet = isset($_POST['ozet']) ? trim($_POST['ozet']) : '';
    $icerik = isset($_POST['icerik']) ? trim($_POST['icerik']) : '';
    $meta_title = isset($_POST['meta_title']) ? trim($_POST['meta_title']) : '';
    $meta_desc = isset($_POST['meta_description']) ? trim($_POST['meta_description']) : '';
    $meta_key = isset($_POST['meta_keywords']) ? trim($_POST['meta_keywords']) : '';
    
    // Klasör yoksa oluştur
    $upload_dir = "../img/blog/";
    if (!file_exists($upload_dir)) {
        @mkdir($upload_dir, 0777, true);
    }
    
    $resim_yolu = null;
    if(isset($_FILES['resim']) && $_FILES['resim']['error'] == 0) {
        $uzanti = pathinfo($_FILES['resim']['name'], PATHINFO_EXTENSION);
        $yeni_ad = "blog_" . time() . "_" . rand(1000,9999) . "." . $uzanti;
        
        if(@move_uploaded_file($_FILES['resim']['tmp_name'], $upload_dir . $yeni_ad)) {
            $resim_yolu = "img/blog/" . $yeni_ad;
        } else {
            sendJson(['hata' => 'Resim yüklenemedi']);
        }
    }
    
    try {
        if($id > 0) {
            // Update
            if($resim_yolu) {
                // Eski resmi bul ve sil
                $sorgu = $db->prepare("SELECT resim FROM blog WHERE id = ?");
                $sorgu->execute([$id]);
                $eski = $sorgu->fetchColumn();
                if($eski && file_exists("../" . $eski)) {
                    @unlink("../" . $eski);
                }
                
                $sorgu = $db->prepare("UPDATE blog SET sira=?, baslik=?, kategori=?, tarih=?, resim=?, ozet=?, icerik=?, meta_title=?, meta_description=?, meta_keywords=? WHERE id=?");
                $sorgu->execute([$sira, $baslik, $kategori, $tarih, $resim_yolu, $ozet, $icerik, $meta_title, $meta_desc, $meta_key, $id]);
            } else {
                $sorgu = $db->prepare("UPDATE blog SET sira=?, baslik=?, kategori=?, tarih=?, ozet=?, icerik=?, meta_title=?, meta_description=?, meta_keywords=? WHERE id=?");
                $sorgu->execute([$sira, $baslik, $kategori, $tarih, $ozet, $icerik, $meta_title, $meta_desc, $meta_key, $id]);
            }
        } else {
            // Insert
            $sorgu = $db->prepare("INSERT INTO blog (sira, baslik, kategori, tarih, resim, ozet, icerik, meta_title, meta_description, meta_keywords) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $sorgu->execute([$sira, $baslik, $kategori, $tarih, $resim_yolu, $ozet, $icerik, $meta_title, $meta_desc, $meta_key]);
        }
        
        sendJson(['durum' => 'basarili']);
    } catch(PDOException $e) {
        sendJson(['hata' => 'Veritabanı hatası: ' . $e->getMessage()]);
    }
} else {
    sendJson(['hata' => 'Bilinmeyen işlem veya sunucu isteği reddetti. İşlem: ' . $islem]);
}
