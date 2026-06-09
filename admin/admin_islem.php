<?php
session_name('kariyer_admin');
    session_start();
// Auth check is moved below so login can happen
require '../baglan.php';

header('Content-Type: application/json');

if(isset($_POST['islem'])) {
    $islem = $_POST['islem'];
    
    // --- Admin Giriş İşlemi (Oturum Açık Değilken Çalışabilir) ---
    if($islem == 'admin_giris') {
        $email = trim($_POST['email']);
        $sifre = trim($_POST['sifre']);
        
        $sorgu = $db->prepare("SELECT * FROM kullanici WHERE email = ? AND krolID = 3");
        $sorgu->execute([$email]);
        $kullanici = $sorgu->fetch(PDO::FETCH_ASSOC);
        
        if($kullanici && password_verify($sifre, $kullanici['sifre'])) {
            $_SESSION['kullaniciID'] = $kullanici['kullaniciID'];
            $_SESSION['email'] = $kullanici['email'];
            $_SESSION['krolID'] = $kullanici['krolID'];
            $_SESSION['ad_soyad'] = 'Admin';
            echo json_encode(['durum' => 'basarili']);
        } else {
            echo json_encode(['hata' => 'E-posta veya şifre hatalı. Veya yetkiniz yok.']);
        }
        exit;
    }

    // Geri kalan tüm işlemler için admin yetki kontrolü
    if(!isset($_SESSION['kullaniciID']) || $_SESSION['krolID'] != 3) {
        echo json_encode(['hata' => 'Yetkisiz işlem']);
        exit;
    }
    
    if($islem == 'ilan_onayla') {
        $id = $_POST['ilan_id'];
        $sorgu = $db->prepare("UPDATE ilan SET idurumID = 1, red_nedeni = NULL WHERE ilanID = ?");
        if($sorgu->execute([$id])) {
            echo json_encode(['durum' => 'basarili']);
        } else {
            echo json_encode(['hata' => 'Onaylama başarısız']);
        }
        exit;
    }
    
    elseif($islem == 'ilan_reddet') {
        $id = $_POST['ilan_id'];
        $neden = trim($_POST['red_nedeni']);
        $sorgu = $db->prepare("UPDATE ilan SET idurumID = 2, red_nedeni = ? WHERE ilanID = ?");
        if($sorgu->execute([$neden, $id])) {
            echo json_encode(['durum' => 'basarili']);
        } else {
            echo json_encode(['hata' => 'Reddetme başarısız']);
        }
        exit;
    }
    
    elseif($islem == 'ilan_sil') {
        $id = $_POST['ilan_id'];
        try {
            $db->beginTransaction();
            $db->prepare("DELETE FROM basvuru WHERE bilanID = ?")->execute([$id]);
            $db->prepare("DELETE FROM kaydedilenler WHERE ilanID = ?")->execute([$id]);
            $db->prepare("DELETE FROM mesaj WHERE milanID = ?")->execute([$id]);
            $db->prepare("DELETE FROM ilan WHERE ilanID = ?")->execute([$id]);
            $db->commit();
            echo json_encode(['durum' => 'basarili']);
        } catch(PDOException $e) {
            $db->rollBack();
            echo json_encode(['hata' => 'Silme hatası: ' . $e->getMessage()]);
        }
        exit;
    }
    
    elseif($islem == 'kullanici_sil') {
        $kID = $_POST['kullanici_id'];
        try {
            $db->beginTransaction();
            
            // Kullanıcı rolünü bul
            $rol_sorgu = $db->prepare("SELECT krolID FROM kullanici WHERE kullaniciID = ?");
            $rol_sorgu->execute([$kID]);
            $rol = $rol_sorgu->fetchColumn();
            
            if ($rol == 2) {
                // İşveren
                $isverenSorgu = $db->prepare("SELECT isverenID FROM isveren WHERE ikullaniciID = ?");
                $isverenSorgu->execute([$kID]);
                $isverenID = $isverenSorgu->fetchColumn();
                
                if ($isverenID) {
                    $ilanlarSorgu = $db->prepare("SELECT ilanID FROM ilan WHERE iisverenID = ?");
                    $ilanlarSorgu->execute([$isverenID]);
                    $ilanlar = $ilanlarSorgu->fetchAll(PDO::FETCH_COLUMN);
                    
                    if (!empty($ilanlar)) {
                        $ilanIDs = implode(',', array_map('intval', $ilanlar));
                        $db->query("DELETE FROM basvuru WHERE bilanID IN ($ilanIDs)");
                        $db->query("DELETE FROM kaydedilenler WHERE ilanID IN ($ilanIDs)");
                        $db->query("DELETE FROM mesaj WHERE milanID IN ($ilanIDs)");
                        $db->prepare("DELETE FROM ilan WHERE iisverenID = ?")->execute([$isverenID]);
                    }
                }
                $db->prepare("DELETE FROM isveren WHERE ikullaniciID = ?")->execute([$kID]);
            } else {
                // İş arayan
                $isarayanSorgu = $db->prepare("SELECT isarayanID FROM isarayan WHERE akullaniciID = ?");
                $isarayanSorgu->execute([$kID]);
                $isarayanID = $isarayanSorgu->fetchColumn();
                
                if ($isarayanID) {
                    $db->prepare("DELETE FROM basvuru WHERE bisarayanID = ?")->execute([$isarayanID]);
                }
                $db->prepare("DELETE FROM isarayan WHERE akullaniciID = ?")->execute([$kID]);
            }
            
            $db->prepare("DELETE FROM mesaj WHERE gonderenID = ? OR aliciID = ?")->execute([$kID, $kID]);
            $db->prepare("DELETE FROM kaydedilenler WHERE kullaniciID = ?")->execute([$kID]);
            
            // Fotoğrafı sil
            $fotoSorgu = $db->prepare("SELECT fotograf FROM kullanici WHERE kullaniciID = ?");
            $fotoSorgu->execute([$kID]);
            $foto = $fotoSorgu->fetchColumn();
            if ($foto && file_exists("../uploads/" . $foto)) {
                unlink("../uploads/" . $foto);
            }
            
            $db->prepare("DELETE FROM kullanici WHERE kullaniciID = ?")->execute([$kID]);
            
            $db->commit();
            echo json_encode(['durum' => 'basarili']);
        } catch(PDOException $e) {
            $db->rollBack();
            echo json_encode(['hata' => 'Silme hatası: ' . $e->getMessage()]);
        }
        exit;
    }
    
    elseif($islem == 'ilan_detay') {
        $id = $_POST['ilan_id'];
        $sorgu = $db->prepare("
            SELECT i.*, v.firmaadi, v.hakkimda, c.calismatur, y.yanhak, s.sektorad 
            FROM ilan i 
            JOIN isveren v ON i.iisverenID = v.isverenID 
            LEFT JOIN calismaturu c ON i.icalismaturID = c.calismaID 
            LEFT JOIN yanhaklar y ON i.iyanhakID = y.yanhakID 
            LEFT JOIN sektor s ON i.isektorID = s.sektorID
            WHERE i.ilanID = ?
        ");
        $sorgu->execute([$id]);
        $ilan = $sorgu->fetch(PDO::FETCH_ASSOC);
        
        if($ilan) {
            $ilan['yayintarihi_formatli'] = date("d.m.Y H:i", strtotime($ilan['yayintarihi']));
            $ilan['maas_formatli'] = number_format($ilan['maas'], 0, ',', '.') . ' TL';
            $ilan['aciklama_formatli'] = nl2br(htmlspecialchars($ilan['aciklama']));
            echo json_encode($ilan);
        } else {
            echo json_encode(['hata' => 'İlan bulunamadı']);
        }
        exit;
    }
    
    elseif($islem == 'kullanici_getir') {
        $id = $_POST['kullanici_id'];
        $rol_sorgu = $db->prepare("SELECT krolID, email, telno FROM kullanici WHERE kullaniciID = ?");
        $rol_sorgu->execute([$id]);
        $kullanici = $rol_sorgu->fetch(PDO::FETCH_ASSOC);
        
        if(!$kullanici) {
            echo json_encode(['hata' => 'Kullanıcı bulunamadı']);
            exit;
        }
        
        if($kullanici['krolID'] == 1) {
            $detay_sorgu = $db->prepare("SELECT adsoyad, dogumyili FROM isarayan WHERE akullaniciID = ?");
            $detay_sorgu->execute([$id]);
            $detay = $detay_sorgu->fetch(PDO::FETCH_ASSOC);
            if($detay) { $kullanici = array_merge($kullanici, $detay); }
        } elseif($kullanici['krolID'] == 2) {
            $detay_sorgu = $db->prepare("SELECT firmaadi, vergino FROM isveren WHERE ikullaniciID = ?");
            $detay_sorgu->execute([$id]);
            $detay = $detay_sorgu->fetch(PDO::FETCH_ASSOC);
            if($detay) { $kullanici = array_merge($kullanici, $detay); }
        }
        
        echo json_encode($kullanici);
        exit;
    }
    
    elseif($islem == 'kullanici_guncelle') {
        $id = $_POST['kullanici_id'];
        $krolID = $_POST['krol_id'];
        $email = trim($_POST['email']);
        $telno = trim($_POST['telno']);
        
        try {
            $db->beginTransaction();
            
            // Kullanıcı tablosunu güncelle
            $sorgu = $db->prepare("UPDATE kullanici SET email = ?, telno = ? WHERE kullaniciID = ?");
            $sorgu->execute([$email, $telno, $id]);
            
            if($krolID == 1) {
                $adsoyad = trim($_POST['adsoyad']);
                $dogumyili = $_POST['dogumyili'];
                $sorgu = $db->prepare("UPDATE isarayan SET adsoyad = ?, dogumyili = ? WHERE akullaniciID = ?");
                $sorgu->execute([$adsoyad, $dogumyili, $id]);
            } elseif($krolID == 2) {
                $firmaadi = trim($_POST['firmaadi']);
                $vergino = trim($_POST['vergino']);
                $sorgu = $db->prepare("UPDATE isveren SET firmaadi = ?, vergino = ? WHERE ikullaniciID = ?");
                $sorgu->execute([$firmaadi, $vergino, $id]);
            }
            
            $db->commit();
            echo json_encode(['durum' => 'basarili']);
        } catch(PDOException $e) {
            $db->rollBack();
            echo json_encode(['hata' => 'Güncelleme hatası: ' . $e->getMessage()]);
        }
        exit;
    }
    
    // Şikayet İşlemleri
    if ($islem == 'incelendi_isaretle') {
        $id = intval($_POST['sikayet_id'] ?? 0);
        $sorgu = $db->prepare("UPDATE sikayet SET durum = 1 WHERE sikayetID = ?");
        if($sorgu->execute([$id])) echo json_encode(['durum' => 'basarili']);
        else echo json_encode(['hata' => 'İşlem başarısız']);
        exit;
    }
    if ($islem == 'bekliyor_isaretle') {
        $id = intval($_POST['sikayet_id'] ?? 0);
        $sorgu = $db->prepare("UPDATE sikayet SET durum = 0 WHERE sikayetID = ?");
        if($sorgu->execute([$id])) echo json_encode(['durum' => 'basarili']);
        else echo json_encode(['hata' => 'İşlem başarısız']);
        exit;
    }
    if ($islem == 'sikayet_sil') {
        $id = intval($_POST['sikayet_id'] ?? 0);
        $sorgu = $db->prepare("DELETE FROM sikayet WHERE sikayetID = ?");
        if($sorgu->execute([$id])) echo json_encode(['durum' => 'basarili']);
        else echo json_encode(['hata' => 'İşlem başarısız']);
        exit;
    }
}
?>
