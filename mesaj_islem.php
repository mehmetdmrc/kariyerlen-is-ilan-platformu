<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// 1. DÜZELTME: PHP'nin saat dilimini zorunlu olarak Türkiye (İstanbul) yapıyoruz
date_default_timezone_set('Europe/Istanbul');

// Veritabanı bağlantını dahil et
require 'baglan.php';

// Çıktının JSON olacağını belirtiyoruz (JS'nin hata vermemesi için çok önemli)
header('Content-Type: application/json');

// Kullanıcı girişi yapılmamışsa işlemi durdur
if(!isset($_SESSION['kullaniciID'])) {
    echo json_encode(['hata' => 'Oturum süresi dolmuş. Lütfen tekrar giriş yapın.']);
    exit;
}

$islem = $_POST['islem'] ?? '';
$benim_id = $_SESSION['kullaniciID'];

try {
    // ================= MESAJLARI ÇEKME =================
    if ($islem == 'mesajlari_getir') {
        $milanID = $_POST['ilan_id'] ?? 0;
        $diger_kullanici = $_POST['alici_id'] ?? 0;

        // Karşı tarafın bana gönderdiği mesajları okundu (1) olarak işaretle
        $guncelle = $db->prepare("UPDATE mesaj SET okundu = 1 WHERE milanID = ? AND gonderenID = ? AND aliciID = ?");
        $guncelle->execute([$milanID, $diger_kullanici, $benim_id]);

        // İki kişi arasındaki sohbeti tarihe göre sıralayıp çek
        $sorgu = $db->prepare("SELECT * FROM mesaj WHERE milanID = ? AND ((gonderenID = ? AND aliciID = ?) OR (gonderenID = ? AND aliciID = ?)) ORDER BY tarih ASC");
        $sorgu->execute([$milanID, $benim_id, $diger_kullanici, $diger_kullanici, $benim_id]);
        $mesajlar = $sorgu->fetchAll(PDO::FETCH_ASSOC);

        $sonuclar = [];
        foreach($mesajlar as $m) {
            $sonuclar[] = [
                'id' => $m['mesajID'], // SİLME İŞLEMİ İÇİN EKLENDİ
                'benim_mesajim' => ($m['gonderenID'] == $benim_id),
                'metin' => htmlspecialchars($m['mesajmetni']),
                'saat' => date("H:i", strtotime($m['tarih'])),
                'okundu' => $m['okundu']
            ];
        }
        echo json_encode(['durum' => 'basarili', 'mesajlar' => $sonuclar]);
        exit;
    }
    
    // ================= MESAJ GÖNDERME =================
    elseif ($islem == 'mesaj_gonder') {
        $milanID = $_POST['ilan_id'] ?? 0;
        $aliciID = $_POST['alici_id'] ?? 0;
        $metin = trim($_POST['metin'] ?? '');
        
        // 2. DÜZELTME: SQL'in NOW() komutu yerine Türkiye saatini tam olarak alıyoruz
        $turkiye_saati = date('Y-m-d H:i:s');

        if(!empty($metin)) {
            // NOW() yerine $turkiye_saati değişkenini veritabanına yolluyoruz
            $ekle = $db->prepare("INSERT INTO mesaj (gonderenID, aliciID, milanID, mesajmetni, tarih, okundu) VALUES (?, ?, ?, ?, ?, 0)");
            $ekle->execute([$benim_id, $aliciID, $milanID, $metin, $turkiye_saati]);
            
            echo json_encode(['durum' => 'basarili']);
        } else {
            echo json_encode(['hata' => 'Boş mesaj gönderilemez.']);
        }
        exit;
    } 
    
    // ================= 4. İŞLEM: ANLIK BİLDİRİM KONTROLÜ =================
    elseif ($islem == 'bildirim_kontrol') {
        // 1. Genel toplam okunmamış mesaj sayısı
        $sorgu_toplam = $db->prepare("SELECT COUNT(*) FROM mesaj WHERE aliciID = ? AND okundu = 0");
        $sorgu_toplam->execute([$benim_id]);
        $toplam_okunmamis = $sorgu_toplam->fetchColumn();

        // 2. İşverenler için: Hangi ilandan, hangi adaydan kaç mesaj geldiğini detaylı grupla
        $sorgu_detay = $db->prepare("SELECT milanID, gonderenID, COUNT(*) as sayi FROM mesaj WHERE aliciID = ? AND okundu = 0 GROUP BY milanID, gonderenID");
        $sorgu_detay->execute([$benim_id]);
        $detaylar = $sorgu_detay->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'durum' => 'basarili',
            'toplam' => $toplam_okunmamis,
            'detaylar' => $detaylar
        ]);
        exit;
    }
    
   // ================= 5. İŞLEM: SOHBETİ KOMPLE SİL =================
    elseif ($islem == 'sohbeti_sil') {
        $milanID = $_POST['ilan_id'] ?? 0;
        $karsi_taraf_id = $_POST['karsi_taraf_id'] ?? 0;

        if ($milanID > 0 && $karsi_taraf_id > 0) {
            // İki kişi arasındaki bu ilana ait TÜM sohbet geçmişini siler
            $sil = $db->prepare("DELETE FROM mesaj WHERE milanID = ? AND ((gonderenID = ? AND aliciID = ?) OR (gonderenID = ? AND aliciID = ?))");
            $sil->execute([$milanID, $benim_id, $karsi_taraf_id, $karsi_taraf_id, $benim_id]);

            echo json_encode(['durum' => 'basarili']);
        } else {
            echo json_encode(['hata' => 'Geçersiz işlem.']);
        }
        exit;
    }
    
    // ================= 3. İŞLEM: MESAJ LİSTESİNİ (KONUŞMALARI) GETİR =================
    elseif ($islem == 'mesaj_listesini_getir') {
        // Kullanıcının (İş arayan veya İşveren fark etmez) dahil olduğu tüm benzersiz ilan konuşmalarını getir
        $sorgu = $db->prepare("
            SELECT 
                m.milanID, 
                i.baslik, 
                v.firmaadi,
                CASE 
                    WHEN m.gonderenID = ? THEN m.aliciID 
                    ELSE m.gonderenID 
                END as karsi_taraf_id,
                MAX(m.tarih) as son_tarih,
                (SELECT mesajmetni FROM mesaj WHERE milanID = m.milanID AND ((gonderenID = m.gonderenID AND aliciID = m.aliciID) OR (gonderenID = m.aliciID AND aliciID = m.gonderenID)) ORDER BY tarih DESC LIMIT 1) as son_mesaj,
                (SELECT COUNT(*) FROM mesaj WHERE milanID = m.milanID AND aliciID = ? AND okundu = 0) as okunmamis_sayisi
            FROM mesaj m
            JOIN ilan i ON m.milanID = i.ilanID
            JOIN isveren v ON i.iisverenID = v.isverenID
            WHERE m.gonderenID = ? OR m.aliciID = ?
            GROUP BY m.milanID
            ORDER BY son_tarih DESC
        ");
        $sorgu->execute([$benim_id, $benim_id, $benim_id, $benim_id]);
        $konusmalar = $sorgu->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['durum' => 'basarili', 'list' => $konusmalar]);
        exit;
    }
    
    else {
        echo json_encode(['hata' => 'Geçersiz işlem türü.']);
        exit;
    }

} catch (PDOException $e) {
    echo json_encode(['hata' => 'Veritabanı Hatası: ' . $e->getMessage()]);
    exit;
}
?>