<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
require 'baglan.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $islem = $_POST['islem'] ?? '';

    if ($islem == 'kayit_bireysel') {
        $adsoyad   = trim($_POST['adsoyad']);
        $dogumyili = $_POST['dogumyili'];
        $cinsiyet  = $_POST['cinsiyet'];
        $telno     = trim($_POST['telno']);
        $email     = trim($_POST['email']);
        $sifre     = $_POST['sifre'];
        $mahalle_id = $_POST['mahalle_id'];

        $sifre_hash = password_hash($sifre, PASSWORD_DEFAULT);

        $kontrol = $db->prepare("SELECT kullaniciID FROM kullanici WHERE email = ?");
        $kontrol->execute([$email]);
        if ($kontrol->rowCount() > 0) {
            $_SESSION['hata'] = 'Bu e-posta zaten kayıtlı!'; header("Location: giris.php?islem=kayit_bireysel"); exit;
        }

        try {
            $db->beginTransaction();

            $sorgu1 = $db->prepare("INSERT INTO kullanici (telno, email, sifre, krolID, kayittarihi) VALUES (?, ?, ?, 1, CURDATE())");
            $sorgu1->execute([$telno, $email, $sifre_hash]);
            $yeniKullaniciID = $db->lastInsertId();

            $sorgu2 = $db->prepare("INSERT INTO isarayan (akullaniciID, adsoyad, dogumyili, cinsiyet, mahalleID) VALUES (?, ?, ?, ?, ?)");
            $sorgu2->execute([$yeniKullaniciID, $adsoyad, $dogumyili, $cinsiyet, $mahalle_id]);

            $db->commit();

            $_SESSION['kullaniciID'] = $yeniKullaniciID;
            $_SESSION['ad_soyad']    = $adsoyad;
            $_SESSION['email']       = $email;
            $_SESSION['krolID']      = 1;

            header("Location: index.php");
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            die("Kayıt Hatası: " . $e->getMessage());
        }
    }

    elseif ($islem == 'kayit_kurumsal') {
        $firmaadi  = trim($_POST['firmaadi']);
        $vergino   = trim($_POST['vergino']);
        $telno     = trim($_POST['telno']);
        $email     = trim($_POST['email']);
        $sifre     = $_POST['sifre'];
        $mahalle_id = $_POST['mahalle_id'];

        $sifre_hash = password_hash($sifre, PASSWORD_DEFAULT);

        $kontrol = $db->prepare("SELECT kullaniciID FROM kullanici WHERE email = ?");
        $kontrol->execute([$email]);
        if ($kontrol->rowCount() > 0) {
            $_SESSION['hata'] = 'Bu şirket e-postası zaten kayıtlı!'; header("Location: giris.php?islem=kayit_kurumsal"); exit;
        }

        try {
            $db->beginTransaction();

            $sorgu1 = $db->prepare("INSERT INTO kullanici (telno, email, sifre, krolID, kayittarihi) VALUES (?, ?, ?, 2, CURDATE())");
            $sorgu1->execute([$telno, $email, $sifre_hash]);
            $yeniKullaniciID = $db->lastInsertId();

            $sorgu2 = $db->prepare("INSERT INTO isveren (ikullaniciID, firmaadi, vergino, mahalleID) VALUES (?, ?, ?, ?)");
            $sorgu2->execute([$yeniKullaniciID, $firmaadi, $vergino, $mahalle_id]);

            $db->commit();

            $_SESSION['kullaniciID'] = $yeniKullaniciID;
            $_SESSION['ad_soyad']    = $firmaadi;
            $_SESSION['email']       = $email;
            $_SESSION['krolID']      = 2;

            header("Location: index.php");
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            die("Kayıt Hatası: " . $e->getMessage());
        }
    }

    elseif ($islem == 'giris') {
        $email = trim($_POST['email']);
        $sifre = $_POST['sifre'];

        $sorgu = $db->prepare("SELECT * FROM kullanici WHERE email = ?");
        $sorgu->execute([$email]);
        $kullanici = $sorgu->fetch(PDO::FETCH_ASSOC);

        if ($kullanici && password_verify($sifre, $kullanici['sifre']) && $kullanici['krolID'] != 3) {

            $_SESSION['kullaniciID'] = $kullanici['kullaniciID'];
            $_SESSION['email']       = $kullanici['email'];
            $_SESSION['krolID']      = $kullanici['krolID'];
            $_SESSION['fotograf']    = $kullanici['fotograf'];
            
            if($kullanici['krolID'] == 1) { 
                $ek = $db->prepare("SELECT adsoyad FROM isarayan WHERE akullaniciID = ?");
                $ek->execute([$kullanici['kullaniciID']]);
                $_SESSION['ad_soyad'] = $ek->fetchColumn();
            } else { 
                $ek = $db->prepare("SELECT firmaadi FROM isveren WHERE ikullaniciID = ?");
                $ek->execute([$kullanici['kullaniciID']]);
                $_SESSION['ad_soyad'] = $ek->fetchColumn();
            }
            header("Location: index.php");
            exit;
        } else {
            $_SESSION['hata'] = 'E-posta veya şifre hatalı!';
            header("Location: giris.php?islem=giris");
            exit;
        }
    }

    elseif ($islem == 'ilan_ekle') {
        header('Content-Type: application/json');
        
        if(!isset($_SESSION['kullaniciID']) || $_SESSION['krolID'] != 2) {
            echo json_encode(['hata' => 'Sadece işverenler ilan verebilir.']); exit;
        }

        $baslik         = trim($_POST['baslik']);
        $sektor_id      = !empty($_POST['sektor_id']) ? $_POST['sektor_id'] : null;
        $calismatur_id  = !empty($_POST['calismatur_id']) ? $_POST['calismatur_id'] : null;
        $maas           = !empty($_POST['maas']) ? $_POST['maas'] : 0;
        $yanhak_id      = !empty($_POST['yanhak_id']) ? $_POST['yanhak_id'] : null;
        $mahalle_id     = !empty($_POST['mahalle_id']) ? $_POST['mahalle_id'] : 1;
        $acikadres      = trim($_POST['acikadres']);
        $aciklama       = trim($_POST['aciklama']);
        
        $calismagunleri_dizi = $_POST['calismagunleri'] ?? [];
        $calismagunleri = implode(", ", $calismagunleri_dizi);
        
        if (isset($_POST['saat_baslangic']) && isset($_POST['saat_bitis'])) {
            $calismasaatleri = $_POST['saat_baslangic'] . " - " . $_POST['saat_bitis'];
        } else {
            $calismasaatleri = trim($_POST['calismasaatleri'] ?? '');
        }
        
        $isverenBul = $db->prepare("SELECT isverenID FROM isveren WHERE ikullaniciID = ?");
        $isverenBul->execute([$_SESSION['kullaniciID']]);
        $firma = $isverenBul->fetch(PDO::FETCH_ASSOC);
        
        if(!$firma) {
            echo json_encode(['hata' => 'İşveren profili bulunamadı.']); exit;
        }
        $isveren_id = $firma['isverenID'];

        try {
            $sorgu = $db->prepare("INSERT INTO ilan 
                (iisverenID, imahalleID, idurumID, baslik, aciklama, maas, icalismaturID, iyanhakID, acikadres, calismagunleri, calismasaatleri, yayintarihi, isiralamaID, isektorID) 
                VALUES (?, ?, 3, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 1, ?)");
            
            $sorgu->execute([$isveren_id, $mahalle_id, $baslik, $aciklama, $maas, $calismatur_id, $yanhak_id, $acikadres, $calismagunleri, $calismasaatleri, $sektor_id]);
            
            echo json_encode(['durum' => 'basarili', 'ilan_id' => $db->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['hata' => 'Veritabanı hatası: ' . $e->getMessage()]);
        }
        exit;
    }

    elseif ($islem == 'basvuru_yap_ajax') {
        header('Content-Type: application/json'); 
        
        if(!isset($_SESSION['kullaniciID']) || $_SESSION['krolID'] != 1) {
            echo json_encode(['hata' => 'Sadece iş arayanlar başvuru yapabilir.']); exit;
        }
        
        $ilan_id = $_POST['ilan_id'];
        $genel_kullanici_id = $_SESSION['kullaniciID'];

        try {
            $isarayanBul = $db->prepare("SELECT isarayanID FROM isarayan WHERE akullaniciID = ?");
            $isarayanBul->execute([$genel_kullanici_id]);
            $isarayan_bilgisi = $isarayanBul->fetch(PDO::FETCH_ASSOC);
            
            if(!$isarayan_bilgisi) {
                echo json_encode(['hata' => 'İş arayan profiliniz bulunamadı.']); exit;
            }
            $gercek_isarayan_id = $isarayan_bilgisi['isarayanID'];

            $kontrol = $db->prepare("SELECT basvuruID FROM basvuru WHERE bisarayanID = ? AND bilanID = ?");
            $kontrol->execute([$gercek_isarayan_id, $ilan_id]);

            if($kontrol->rowCount() > 0) {
                echo json_encode(['hata' => 'Bu ilana zaten başvuru yaptınız.']);
            } else {
                $db->prepare("INSERT INTO basvuru (bisarayanID, bilanID, tarih) VALUES (?, ?, CURDATE())")->execute([$gercek_isarayan_id, $ilan_id]);
                echo json_encode(['durum' => 'basarili']);
            }
        } catch (PDOException $e) {
            echo json_encode(['hata' => 'Veritabanı hatası: ' . $e->getMessage()]);
        }
        exit;
    }
    elseif ($islem == 'kaydet' || $islem == 'kayit_sil') {
        header('Content-Type: application/json'); 
        if(!isset($_SESSION['kullaniciID']) || $_SESSION['krolID'] != 1) {
            echo json_encode(['hata' => 'Sadece iş arayanlar ilan kaydedebilir.']); exit;
        }
        $ilan_id = $_POST['ilan_id'];
        $kul_id = $_SESSION['kullaniciID'];

        if($islem == 'kaydet') {
            $kontrol = $db->prepare("SELECT kayitID FROM kaydedilenler WHERE kullaniciID = ? AND ilanID = ?");
            $kontrol->execute([$kul_id, $ilan_id]);
            if($kontrol->rowCount() == 0) {
                $db->prepare("INSERT INTO kaydedilenler (kullaniciID, ilanID, tarih) VALUES (?, ?, CURDATE())")->execute([$kul_id, $ilan_id]);
            }
            echo json_encode(['durum' => 'basarili', 'eylem' => 'eklendi']);
        } else {
            $db->prepare("DELETE FROM kaydedilenler WHERE kullaniciID = ? AND ilanID = ?")->execute([$kul_id, $ilan_id]);
            echo json_encode(['durum' => 'basarili', 'eylem' => 'silindi']);
        }
        exit;
    }


    elseif ($islem == 'ilan_getir') {
        header('Content-Type: application/json');
        $id = $_POST['id'] ?? 0;
        try {
            $s = $db->prepare("SELECT * FROM ilan WHERE ilanID = ?");
            $s->execute([$id]);
            $i = $s->fetch(PDO::FETCH_ASSOC);
            echo json_encode($i);
        } catch (PDOException $e) {
            echo json_encode(['hata' => $e->getMessage()]);
        }
        exit;
    }

    elseif ($islem == 'ilan_duzenle') {
        if(!isset($_SESSION['kullaniciID']) || $_SESSION['krolID'] != 2) {
            die("Sadece işverenler ilan düzenleyebilir.");
        }

        try {
            $ilan_id        = $_POST['ilan_id'];
            $baslik         = trim($_POST['baslik']);
            $sektor_id      = $_POST['sektor_id'];
            $calismatur_id  = $_POST['calismatur_id'];
            $maas           = $_POST['maas'];
            $yanhak_id      = $_POST['yanhak_id'];
            $acikadres      = trim($_POST['acikadres']);
            $aciklama       = trim($_POST['aciklama']);
            
            $calismagunleri_dizi = $_POST['calismagunleri'] ?? [];
            $calismagunleri = implode(", ", $calismagunleri_dizi);
            
            if (isset($_POST['saat_baslangic']) && isset($_POST['saat_bitis'])) {
                $calismasaatleri = $_POST['saat_baslangic'] . " - " . $_POST['saat_bitis'];
            } else {
                $calismasaatleri = trim($_POST['calismasaatleri'] ?? '');
            }

            $isverenBul = $db->prepare("SELECT isverenID FROM isveren WHERE ikullaniciID = ?");
            $isverenBul->execute([$_SESSION['kullaniciID']]);
            $firma = $isverenBul->fetch(PDO::FETCH_ASSOC);
            
            if(!$firma) { die("İşveren profili bulunamadı."); }
            $isveren_id = $firma['isverenID'];

            $kontrol = $db->prepare("SELECT ilanID FROM ilan WHERE ilanID = ? AND iisverenID = ?");
            $kontrol->execute([$ilan_id, $isveren_id]);
            
            if($kontrol->rowCount() > 0) {
                $guncelle = $db->prepare("UPDATE ilan SET baslik=?, isektorID=?, icalismaturID=?, maas=?, iyanhakID=?, acikadres=?, aciklama=?, calismagunleri=?, calismasaatleri=?, red_nedeni=IF(idurumID=2, NULL, red_nedeni), idurumID=IF(idurumID=2, 3, idurumID) WHERE ilanID=?");
                $guncelle->execute([$baslik, $sektor_id, $calismatur_id, $maas, $yanhak_id, $acikadres, $aciklama, $calismagunleri, $calismasaatleri, $ilan_id]);
                
                echo json_encode(['durum' => 'basarili']);
                exit;
            } else {
                die("Bu ilanı düzenleme yetkiniz yok.");
            }
        } catch (PDOException $e) {
            die("İlan Düzenleme Hatası: " . $e->getMessage());
        }
    }

    elseif ($islem == 'ilan_sil') {
        if(!isset($_SESSION['kullaniciID']) || $_SESSION['krolID'] != 2) {
            echo json_encode(['hata' => 'Sadece işverenler ilan silebilir.']); exit;
        }

        try {
            $ilan_id = $_POST['ilan_id'];
            $kul_id = $_SESSION['kullaniciID'];

            // İşveren ID'sini bul
            $isverenBul = $db->prepare("SELECT isverenID FROM isveren WHERE ikullaniciID = ?");
            $isverenBul->execute([$kul_id]);
            $isveren = $isverenBul->fetch(PDO::FETCH_ASSOC);
            
            if(!$isveren) { echo json_encode(['hata' => 'İşveren profili bulunamadı.']); exit; }
            $isveren_id = $isveren['isverenID'];

            // İlanın bu işverene ait olduğunu kontrol et
            $kontrol = $db->prepare("SELECT ilanID FROM ilan WHERE ilanID = ? AND iisverenID = ?");
            $kontrol->execute([$ilan_id, $isveren_id]);
            
            if($kontrol->rowCount() > 0) {
                $db->beginTransaction();
                
                // 1. Önce bu ilana yapılan başvuruları sil
                $db->prepare("DELETE FROM basvuru WHERE bilanID = ?")->execute([$ilan_id]);
                
                // 2. Bu ilanı kaydedenlerin kayıtlarını sil
                $db->prepare("DELETE FROM kaydedilenler WHERE ilanID = ?")->execute([$ilan_id]);
                
                // 3. Bu ilana ait tüm mesajlaşmaları sil
                $db->prepare("DELETE FROM mesaj WHERE milanID = ?")->execute([$ilan_id]);
                
                // 4. En son ilanı sil
                $sil = $db->prepare("DELETE FROM ilan WHERE ilanID = ?");
                $sil->execute([$ilan_id]);
                
                $db->commit();
                echo json_encode(['durum' => 'basarili']);
            } else {
                echo json_encode(['hata' => 'Bu ilanı silme yetkiniz yok.']);
            }
        } catch (PDOException $e) {
            if($db->inTransaction()) $db->rollBack();
            echo json_encode(['hata' => 'Veritabanı hatası: ' . $e->getMessage()]);
        }
        exit;
    }

    elseif ($islem == 'isveren_getir') {
        header('Content-Type: application/json');
        $ilan_id = $_POST['ilan_id'] ?? 0;

        try {
            
            $sorgu = $db->prepare("
                SELECT v.isverenID, v.firmaadi, v.hakkimda, k.kayittarihi, i.acikadres
                FROM ilan i
                JOIN isveren v ON i.iisverenID = v.isverenID
                JOIN kullanici k ON v.ikullaniciID = k.kullaniciID
                WHERE i.ilanID = ?
            ");
            $sorgu->execute([$ilan_id]);
            $isveren = $sorgu->fetch(PDO::FETCH_ASSOC);

            if ($isveren) {
                // Bu işverenin toplam açtığı ilan sayısını bul
                $ilanSayisiSorgu = $db->prepare("SELECT COUNT(*) FROM ilan WHERE iisverenID = ?");
                $ilanSayisiSorgu->execute([$isveren['isverenID']]);
                $ilan_sayisi = $ilanSayisiSorgu->fetchColumn();

                $isveren['toplam_ilan'] = $ilan_sayisi;
                $isveren['kayit_tarihi_formatli'] = date("d.m.Y", strtotime($isveren['kayittarihi']));
                $isveren['son_aktif'] = date("d.m.Y"); // Şimdilik o gün aktif varsayıyoruz

                echo json_encode($isveren);
            } else {
                echo json_encode(['hata' => 'İşveren bulunamadı.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['hata' => 'Veritabanı hatası: ' . $e->getMessage()]);
        }
        exit;
    }
    elseif ($islem == 'aday_getir') {
        header('Content-Type: application/json');
        if(!isset($_SESSION['kullaniciID']) || $_SESSION['krolID'] != 2) {
            echo json_encode(['hata' => 'Sadece işverenler aday profili görüntüleyebilir.']); exit;
        }

        $basvuru_id = $_POST['basvuru_id'] ?? 0;
        $isveren_id = $_SESSION['kullaniciID'];

        try {
         
            $sorgu = $db->prepare("
                SELECT ia.adsoyad, ia.dogumyili, ia.cinsiyet, ia.hakkimda, ia.egitim, ia.ehliyet, ia.askerlik, ia.is_tecrubesi, 
                       ia.akullaniciID as aday_k_id, k.email, k.telno, i.baslik as ilan_baslik, b.tarih as basvuru_tarihi
                FROM basvuru b
                JOIN isarayan ia ON b.bisarayanID = ia.isarayanID
                JOIN kullanici k ON ia.akullaniciID = k.kullaniciID
                JOIN ilan i ON b.bilanID = i.ilanID
                JOIN isveren v ON i.iisverenID = v.isverenID
                WHERE b.basvuruID = ? AND v.ikullaniciID = ?
            ");
            $sorgu->execute([$basvuru_id, $isveren_id]);
            $aday = $sorgu->fetch(PDO::FETCH_ASSOC);

            if ($aday) {
                $aday['basvuru_tarihi_formatli'] = date("d.m.Y", strtotime($aday['basvuru_tarihi']));
                echo json_encode($aday);
            } else {
                echo json_encode(['hata' => 'Aday bilgisi bulunamadı veya yetkiniz yok.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['hata' => 'Veritabanı hatası: ' . $e->getMessage()]);
        }
    }
    elseif ($islem == 'basvuru_getir') {
        header('Content-Type: application/json');
        $bID = $_POST['id'] ?? 0;
        try {
            $s = $db->prepare("SELECT b.*, ia.*, k.email, k.telno, i.baslik FROM basvuru b JOIN isarayan ia ON b.bisarayanID = ia.isarayanID JOIN kullanici k ON ia.akullaniciID = k.kullaniciID JOIN ilan i ON b.bilanID = i.ilanID WHERE b.basvuruID = ?");
            $s->execute([$bID]);
            $b = $s->fetch(PDO::FETCH_ASSOC);
            echo json_encode($b);
        } catch (PDOException $e) {
            echo json_encode(['hata' => $e->getMessage()]);
        }
        exit;
    }


    // Profil fotoğrafı yükleme işlemi
    elseif ($islem == 'profil_foto_yukle') {
        if (!isset($_SESSION['kullaniciID'])) { header('Location: giris.php'); exit; }
        $kul_id = $_SESSION['kullaniciID'];
        if(isset($_FILES['profil_foto']) && $_FILES['profil_foto']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uzanti = strtolower(pathinfo($_FILES['profil_foto']['name'], PATHINFO_EXTENSION));
            $izin_verilenler = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if(!in_array($uzanti, $izin_verilenler)) {
                $_SESSION['hata'] = 'Geçersiz dosya uzantısı! Sadece jpg, jpeg, png, gif, webp yükleyebilirsiniz.';
            } else if($_FILES['profil_foto']['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['hata'] = 'Fotoğraf yüklenirken hata oluştu: ' . $_FILES['profil_foto']['error'];
            } else {
                // Eski fotoğrafı sil
                $eski_foto_sorgu = $db->prepare("SELECT fotograf FROM kullanici WHERE kullaniciID = ?");
                $eski_foto_sorgu->execute([$kul_id]);
                $eski_foto = $eski_foto_sorgu->fetchColumn();
                if($eski_foto && file_exists("uploads/" . $eski_foto)) {
                    unlink("uploads/" . $eski_foto);
                }

                $yeni_ad = "profil_" . $kul_id . "_" . time() . "." . $uzanti;
                $hedef = "uploads/" . $yeni_ad;
                if(!is_dir('uploads')) { mkdir('uploads', 0777, true); }
                if(move_uploaded_file($_FILES['profil_foto']['tmp_name'], $hedef)) {
                    $db->prepare("UPDATE kullanici SET fotograf=? WHERE kullaniciID=?")->execute([$yeni_ad, $kul_id]);
                    $_SESSION['fotograf'] = $yeni_ad;
                    // Eğer AJAX ise JSON dön
                    if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                        echo json_encode(['durum' => 'basarili', 'yeni_foto' => $yeni_ad]); exit;
                    }
                } else {
                    $_SESSION['hata'] = 'Fotoğraf yüklenemedi. Lütfen tekrar deneyin.';
                }
            }
        } else {
            $_SESSION['hata'] = 'Dosya seçilmedi.';
        }
        $geldigi_sayfa = $_SERVER['HTTP_REFERER'] ?? 'index.php';
        $yonlen = (strpos($geldigi_sayfa, '?') !== false) ? $geldigi_sayfa . '&modal=profil' : $geldigi_sayfa . '?modal=profil';
        header('Location: ' . $yonlen);
        exit;
    }

    elseif ($islem == 'profil_foto_sil') {
        if (!isset($_SESSION['kullaniciID'])) { header('Location: giris.php'); exit; }
        $kul_id = $_SESSION['kullaniciID'];
        
        $sorgu = $db->prepare("SELECT fotograf FROM kullanici WHERE kullaniciID = ?");
        $sorgu->execute([$kul_id]);
        $foto = $sorgu->fetchColumn();
        
        if ($foto) {
            if (file_exists("uploads/" . $foto)) {
                unlink("uploads/" . $foto);
            }
            $db->prepare("UPDATE kullanici SET fotograf = NULL WHERE kullaniciID = ?")->execute([$kul_id]);
            $_SESSION['fotograf'] = null;
        }

        if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['durum' => 'basarili']); exit;
        }
        
        $geldigi_sayfa = $_SERVER['HTTP_REFERER'] ?? 'index.php';
        $yonlen = (strpos($geldigi_sayfa, '?') !== false) ? $geldigi_sayfa . '&modal=profil' : $geldigi_sayfa . '?modal=profil';
        header('Location: ' . $yonlen);
        exit;
    }
    
    elseif ($islem == 'profil_guncelle') {
        header('Content-Type: application/json');
        if (!isset($_SESSION['kullaniciID'])) {
            echo json_encode(['hata' => 'Oturum açmanız gerekiyor.']); exit;
        }

        $kID = $_SESSION['kullaniciID'];
        $rol = $_SESSION['krolID'];

        try {
            $db->beginTransaction();

            // Ortak bilgileri güncelle (kullanici tablosu)
            $telno = trim($_POST['telno'] ?? '');
            $email = trim($_POST['email'] ?? '');
            
            if (!empty($email)) {
                $u_k = $db->prepare("UPDATE kullanici SET telno = ?, email = ? WHERE kullaniciID = ?");
                $u_k->execute([$telno, $email, $kID]);
            } else {
                $u_k = $db->prepare("UPDATE kullanici SET telno = ? WHERE kullaniciID = ?");
                $u_k->execute([$telno, $kID]);
            }

            // Role özel bilgileri güncelle
            if ($rol == 1) {
                // İş arayan
                $adsoyad = trim($_POST['adsoyad'] ?? '');
                $dogumyili = $_POST['dogumyili'] ?? null;
                $hakkimda = trim($_POST['hakkimda'] ?? '');
                $egitim = $_POST['egitim'] ?? '';
                $ehliyet = $_POST['ehliyet'] ?? '';
                $askerlik = $_POST['askerlik'] ?? '';
                $is_tecrubesi = trim($_POST['is_tecrubesi'] ?? '');

                $u_i = $db->prepare("UPDATE isarayan SET adsoyad = ?, dogumyili = ?, hakkimda = ?, egitim = ?, ehliyet = ?, askerlik = ?, is_tecrubesi = ? WHERE akullaniciID = ?");
                $u_i->execute([$adsoyad, $dogumyili, $hakkimda, $egitim, $ehliyet, $askerlik, $is_tecrubesi, $kID]);
            } else {
                // İşveren
                $firmaadi = trim($_POST['firmaadi'] ?? '');
                $vergino = trim($_POST['vergino'] ?? '');
                $hakkimda = trim($_POST['hakkimda'] ?? '');

                $u_v = $db->prepare("UPDATE isveren SET firmaadi = ?, vergino = ?, hakkimda = ? WHERE ikullaniciID = ?");
                $u_v->execute([$firmaadi, $vergino, $hakkimda, $kID]);
            }

            $db->commit();
            echo json_encode(['durum' => 'basarili']);
            exit;
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            echo json_encode(['hata' => 'Güncelleme hatası: ' . $e->getMessage()]);
            exit;
        }
    }
    elseif ($islem == 'ilan_durum_kontrol') {
        header('Content-Type: application/json');
        if(!isset($_SESSION['kullaniciID']) || $_SESSION['krolID'] != 2) { echo json_encode([]); exit; }
        
        $isverenBul = $db->prepare("SELECT isverenID FROM isveren WHERE ikullaniciID = ?");
        $isverenBul->execute([$_SESSION['kullaniciID']]);
        $firma = $isverenBul->fetch(PDO::FETCH_ASSOC);
        if(!$firma) { echo json_encode([]); exit; }
        
        $is = $db->prepare("SELECT ilanID, idurumID, red_nedeni FROM ilan WHERE iisverenID = ?");
        $is->execute([$firma['isverenID']]);
        $ilanlar = $is->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($ilanlar);
        exit;
    }
    elseif ($islem == 'mesaj_yukle') {
        header('Content-Type: application/json');
        if(!isset($_SESSION['kullaniciID'])) { echo json_encode([]); exit; }
        $kID = $_SESSION['kullaniciID'];
        
        try {
            $s = $db->prepare("
                SELECT m1.*, 
                       IF(m1.gonderenID = ?, m1.aliciID, m1.gonderenID) as muhatap_id,
                       k.fotograf,
                       COALESCE(ia.adsoyad, iv.firmaadi) as muhatap_ad
                FROM mesaj m1
                JOIN (
                    SELECT MAX(mesajID) as maxID
                    FROM mesaj
                    WHERE gonderenID = ? OR aliciID = ?
                    GROUP BY IF(gonderenID = ?, aliciID, gonderenID)
                ) m2 ON m1.mesajID = m2.maxID
                JOIN kullanici k ON k.kullaniciID = IF(m1.gonderenID = ?, m1.aliciID, m1.gonderenID)
                LEFT JOIN isarayan ia ON ia.akullaniciID = k.kullaniciID
                LEFT JOIN isveren iv ON iv.ikullaniciID = k.kullaniciID
                ORDER BY m1.tarih DESC
            ");
            $s->execute([$kID, $kID, $kID, $kID, $kID]);
            echo json_encode($s->fetchAll(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {
            echo json_encode(['hata' => $e->getMessage()]);
        }
        exit;
    }
    elseif ($islem == 'mesaj_detay_getir') {
        header('Content-Type: application/json');
        $kID = $_SESSION['kullaniciID'];
        $mID = $_POST['muhatap_id'];
        
        // Mesajları okundu yap
        $update = $db->prepare("UPDATE mesaj SET okundu = 1 WHERE gonderenID = ? AND aliciID = ? AND okundu = 0");
        $update->execute([$mID, $kID]);
        
        $s = $db->prepare("
            SELECT * FROM mesaj 
            WHERE (gonderenID = ? AND aliciID = ?) OR (gonderenID = ? AND aliciID = ?)
            ORDER BY tarih ASC
        ");
        $s->execute([$kID, $mID, $mID, $kID]);
        echo json_encode($s->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }
    elseif ($islem == 'mesaj_gonder') {
        header('Content-Type: application/json');
        $kID = $_SESSION['kullaniciID'];
        $aliciID = $_POST['alici_id'];
        $mesaj = trim($_POST['mesaj']);
        
        if(!$mesaj) exit;
        
        $s = $db->prepare("INSERT INTO mesaj (gonderenID, aliciID, mesajmetni, tarih, okundu) VALUES (?, ?, ?, NOW(), 0)");
        if($s->execute([$kID, $aliciID, $mesaj])) {
            echo json_encode(['durum' => 'basarili']);
        }
        exit;
    }
    
    elseif ($islem == 'sohbeti_sil') {
        header('Content-Type: application/json');
        if(!isset($_SESSION['kullaniciID'])) { echo json_encode(['hata' => 'Oturum açmanız gerekiyor.']); exit; }
        
        $kID = $_SESSION['kullaniciID'];
        $mID = $_POST['muhatap_id'] ?? 0;
        
        try {
            $s = $db->prepare("DELETE FROM mesaj WHERE (gonderenID = ? AND aliciID = ?) OR (gonderenID = ? AND aliciID = ?)");
            $s->execute([$kID, $mID, $mID, $kID]);
            echo json_encode(['durum' => 'basarili']);
        } catch (PDOException $e) {
            echo json_encode(['hata' => 'Sohbet silinirken hata oluştu: ' . $e->getMessage()]);
        }
        exit;
    }
    
    elseif ($islem == 'hesabi_sil') {
        header('Content-Type: application/json');
        if(!isset($_SESSION['kullaniciID'])) { echo json_encode(['hata' => 'Oturum açmanız gerekiyor.']); exit; }
        
        $kID = $_SESSION['kullaniciID'];
        $rol = $_SESSION['krolID'];
        
        try {
            $db->beginTransaction();
            
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
            
            // Tüm mesajları sil (gönderen veya alıcı olduğu)
            $db->prepare("DELETE FROM mesaj WHERE gonderenID = ? OR aliciID = ?")->execute([$kID, $kID]);
            
            // Kaydedilenleri sil
            $db->prepare("DELETE FROM kaydedilenler WHERE kullaniciID = ?")->execute([$kID]);
            
            // Profil fotoğrafını sil
            $fotoSorgu = $db->prepare("SELECT fotograf FROM kullanici WHERE kullaniciID = ?");
            $fotoSorgu->execute([$kID]);
            $foto = $fotoSorgu->fetchColumn();
            if ($foto && file_exists("uploads/" . $foto)) {
                unlink("uploads/" . $foto);
            }
            
            // Kullanıcıyı sil
            $db->prepare("DELETE FROM kullanici WHERE kullaniciID = ?")->execute([$kID]);
            
            $db->commit();
            session_destroy();
            echo json_encode(['durum' => 'basarili']);
        } catch (PDOException $e) {
            if($db->inTransaction()) $db->rollBack();
            echo json_encode(['hata' => 'Hesap silinirken hata oluştu: ' . $e->getMessage()]);
        }
        exit;
    }
    elseif ($islem == 'ilan_sikayet_et') {
        header('Content-Type: application/json');
        $ilan_id = intval($_POST['ilan_id'] ?? 0);
        $neden = trim($_POST['neden'] ?? '');
        $kullaniciID = isset($_SESSION['kullaniciID']) ? $_SESSION['kullaniciID'] : null;

        if($ilan_id > 0 && $neden != '') {
            $stmt = $db->prepare("INSERT INTO sikayet (ilanID, kullaniciID, neden) VALUES (?, ?, ?)");
            if($stmt->execute([$ilan_id, $kullaniciID, $neden])) {
                echo json_encode(['durum' => 'basarili']);
            } else {
                echo json_encode(['durum' => 'hata', 'hata' => 'Şikayet kaydedilemedi.']);
            }
        } else {
            echo json_encode(['durum' => 'hata', 'hata' => 'Eksik bilgi girdiniz.']);
        }
        exit;
    }

} else {
   
    header("Location: index.php");
    exit;
}
?>