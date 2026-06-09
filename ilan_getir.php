<?php
ob_start();
if(!isset($_SESSION)) { session_start(); }
error_reporting(0);
try {
    require 'baglan.php';
    header('Content-Type: application/json; charset=utf-8');

    $ilan_id = $_GET['id'] ?? 0;

    // Admin tüm ilanları görebilir, diğer kullanıcılar sadece onaylı ilanları
    $durum_filtre = (isset($_SESSION['krolID']) && $_SESSION['krolID'] == 3) ? "" : "AND i.idurumID = 1";
    $sorgu = $db->prepare("SELECT i.*, v.firmaadi, v.hakkimda, c.calismatur, y.yanhak, s.sektorad 
                           FROM ilan i 
                           JOIN isveren v ON i.iisverenID = v.isverenID 
                           LEFT JOIN calismaturu c ON i.icalismaturID = c.calismaID 
                           LEFT JOIN yanhaklar y ON i.iyanhakID = y.yanhakID 
                           LEFT JOIN sektor s ON i.isektorID = s.sektorID
                           WHERE i.ilanID = ? $durum_filtre");
    $sorgu->execute([$ilan_id]);
    $ilan = $sorgu->fetch(PDO::FETCH_ASSOC);
    $kayitli_mi = false;
    $basvuruldu_mu = false;

    if (isset($_SESSION['kullaniciID']) && $_SESSION['krolID'] == 1) {
        $kID = $_SESSION['kullaniciID'];
        
        $kayit_kontrol = $db->prepare("SELECT COUNT(*) FROM kaydedilenler WHERE kullaniciID = ? AND ilanID = ?");
        $kayit_kontrol->execute([$kID, $ilan_id]);
        $kayitli_mi = $kayit_kontrol->fetchColumn() > 0;
        
        $isArayanSorgu = $db->prepare("SELECT isarayanID FROM isarayan WHERE akullaniciID = ?");
        $isArayanSorgu->execute([$kID]);
        $gercekIsArayanID = $isArayanSorgu->fetchColumn();
        
        if ($gercekIsArayanID) {
            $basvuru_kontrol = $db->prepare("SELECT COUNT(*) FROM basvuru WHERE bisarayanID = ? AND bilanID = ?");
            $basvuru_kontrol->execute([$gercekIsArayanID, $ilan_id]);
            $basvuruldu_mu = $basvuru_kontrol->fetchColumn() > 0;
        }
    }

    if(!function_exists('timeAgo')) {
        function timeAgo($timestamp) {
            if (!$timestamp) return 'Belirtilmemiş';
            if (!is_numeric($timestamp)) $timestamp = strtotime($timestamp);
            $diff = time() - $timestamp;
            if ($diff < 3600) return 'Yeni';
            $intervals = [
                31536000 => 'yıl',
                2592000 => 'ay',
                604800 => 'hafta',
                86400 => 'gün',
                3600 => 'saat',
                60 => 'dakika'
            ];
            foreach ($intervals as $seconds => $label) {
                if ($diff >= $seconds) {
                    $value = floor($diff / $seconds);
                    return $value . ' ' . $label . ' önce';
                }
            }
            return 'az önce';
        }
    }

    if ($ilan) {
        $ilan['ilanID'] = $ilan_id;
        $ilan['maas_formatli'] = number_format($ilan['maas'] ?? 0, 0, ',', '.');
        $ilan['tarih_formatli'] = date("d.m.Y", strtotime($ilan['yayintarihi']));
        $ilan['yayin_zamani'] = timeAgo($ilan['yayintarihi']);
        $ilan['aciklama_formatli'] = nl2br(htmlspecialchars($ilan['aciklama'] ?? '')); 
        $ilan['kayitli_mi'] = $kayitli_mi;
        $ilan['basvuruldu_mu'] = $basvuruldu_mu;
        $ilan['yanhak_label'] = $ilan['yanhak'] ?? 'Belirtilmemiş';
        $ilan['gunler'] = $ilan['calismagunleri'] ?? 'Belirtilmemiş';
        $ilan['saatler'] = $ilan['calismasaatleri'] ?? 'Belirtilmemiş';
        
        $basvuru_sayisi_sorgu = $db->prepare("SELECT COUNT(*) FROM basvuru WHERE bilanID = ?");
        $basvuru_sayisi_sorgu->execute([$ilan_id]);
        $ilan['basvuru_sayisi'] = $basvuru_sayisi_sorgu->fetchColumn();

        // BENZER İLANLARI GETİR (Aynı Sektör)
        $benzer_sorgu = $db->prepare("SELECT i.ilanID, i.baslik, v.firmaadi, i.acikadres, c.calismatur, i.yayintarihi 
                                    FROM ilan i 
                                    JOIN isveren v ON i.iisverenID = v.isverenID 
                                    LEFT JOIN calismaturu c ON i.icalismaturID = c.calismaID
                                    WHERE i.isektorID = ? AND i.ilanID != ? 
                                    ORDER BY i.yayintarihi DESC LIMIT 3");
        $benzer_sorgu->execute([$ilan['isektorID'], $ilan_id]);
        $benzerler = $benzer_sorgu->fetchAll(PDO::FETCH_ASSOC);
        
        foreach($benzerler as &$b) {
            $b['zaman'] = timeAgo($b['yayintarihi']);
            $b['konum'] = explode(',', $b['acikadres'])[0] ?? 'Türkiye';
        }
        $ilan['benzer_ilanlar'] = $benzerler;
        
        ob_clean();
        echo json_encode($ilan, JSON_UNESCAPED_UNICODE);
    } else {
        ob_clean();
        echo json_encode(["hata" => "İlan bulunamadı"], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    ob_clean();
    echo json_encode(["hata" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>