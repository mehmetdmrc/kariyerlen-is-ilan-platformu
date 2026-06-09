<?php
session_start();
require 'baglan.php';
$iller = $db->query("SELECT * FROM il ORDER BY ilisim ASC")->fetchAll(PDO::FETCH_ASSOC);

$kullanici_kayitli_ilanlar = [];
$kullanici_basvurulan_ilanlar = [];
$gercekIsArayanID = null;

if(isset($_SESSION['kullaniciID']) && $_SESSION['krolID'] == 1) {
    $kID = $_SESSION['kullaniciID'];
    
    $kayit_kontrol = $db->prepare("SELECT ilanID FROM kaydedilenler WHERE kullaniciID = ?");
    $kayit_kontrol->execute([$kID]);
    $kullanici_kayitli_ilanlar = $kayit_kontrol->fetchAll(PDO::FETCH_COLUMN); 
    
    $isArayanSorgu = $db->prepare("SELECT isarayanID FROM isarayan WHERE akullaniciID = ?");
    $isArayanSorgu->execute([$kID]);
    $gercekIsArayanID = $isArayanSorgu->fetchColumn();

    if($gercekIsArayanID) {
        $basvuru_kontrol = $db->prepare("SELECT bilanID FROM basvuru WHERE bisarayanID = ?");
        $basvuru_kontrol->execute([$gercekIsArayanID]);
        $kullanici_basvurulan_ilanlar = $basvuru_kontrol->fetchAll(PDO::FETCH_COLUMN); 
    }
}

$sektorler = []; $calismaturleri = []; $yanhaklar = [];
if(isset($_SESSION['kullaniciID']) && $_SESSION['krolID'] == 2) {
    $sektorler = $db->query("SELECT * FROM sektor ORDER BY sektorad ASC")->fetchAll(PDO::FETCH_ASSOC);
    $calismaturleri = $db->query("SELECT * FROM calismaturu ORDER BY calismatur ASC")->fetchAll(PDO::FETCH_ASSOC);
    $yanhaklar = $db->query("SELECT * FROM yanhaklar ORDER BY yanhak ASC")->fetchAll(PDO::FETCH_ASSOC);
}

$profil = null; $basvurular = []; $kaydedilenler = []; $ilanlarim = []; $basvurular_firma = [];
$isveren_ilan_sayisi = 0; 

// OKUNMAMIŞ MESAJ SAYISINI ÇEK
$okunmamis_mesaj_sayisi = 0;
if(isset($_SESSION['kullaniciID'])) {
    $msj_sorgu = $db->prepare("SELECT COUNT(*) FROM mesaj WHERE aliciID = ? AND okundu = 0");
    $msj_sorgu->execute([$_SESSION['kullaniciID']]);
    $okunmamis_mesaj_sayisi = $msj_sorgu->fetchColumn();
}

if(isset($_SESSION['kullaniciID'])) {
    $kID = $_SESSION['kullaniciID'];
    $rol = $_SESSION['krolID'];
    
    if ($rol == 1) { 
        $psorgu = $db->prepare("SELECT k.telno, k.email, k.kayittarihi, i.adsoyad, i.dogumyili, i.cinsiyet, i.hakkimda, i.egitim, i.ehliyet, i.askerlik, i.is_tecrubesi FROM kullanici k JOIN isarayan i ON k.kullaniciID = i.akullaniciID WHERE k.kullaniciID = ?");
        
        if($gercekIsArayanID) {
            $b_sorgu = $db->prepare("SELECT b.*, i.baslik, v.firmaadi, i.acikadres, ct.calismatur, b.tarih FROM basvuru b JOIN ilan i ON b.bilanID = i.ilanID JOIN isveren v ON i.iisverenID = v.isverenID LEFT JOIN calismaturu ct ON i.icalismaturID = ct.calismaID WHERE b.bisarayanID = ? ORDER BY b.basvuruID DESC");
            $b_sorgu->execute([$gercekIsArayanID]); 
            $basvurular = $b_sorgu->fetchAll(PDO::FETCH_ASSOC);
        }
        
        $k_sorgu = $db->prepare("SELECT kayit.*, i.baslik, v.firmaadi, i.acikadres, ct.calismatur, kayit.tarih FROM kaydedilenler kayit JOIN ilan i ON kayit.ilanID = i.ilanID JOIN isveren v ON i.iisverenID = v.isverenID LEFT JOIN calismaturu ct ON i.icalismaturID = ct.calismaID WHERE kayit.kullaniciID = ? ORDER BY kayit.kayitID DESC");
        $k_sorgu->execute([$kID]); $kaydedilenler = $k_sorgu->fetchAll(PDO::FETCH_ASSOC);

    } else { 
        $psorgu = $db->prepare("SELECT k.telno, k.email, k.kayittarihi, v.firmaadi, v.vergino, v.isverenID, v.hakkimda FROM kullanici k JOIN isveren v ON k.kullaniciID = v.ikullaniciID WHERE k.kullaniciID = ?");
        $psorgu->execute([$kID]); $profil = $psorgu->fetch(PDO::FETCH_ASSOC);
        $iID = $profil['isverenID'];

        $il_sorgu = $db->prepare("SELECT i.*, ct.calismatur FROM ilan i LEFT JOIN calismaturu ct ON i.icalismaturID = ct.calismaID WHERE i.iisverenID = ? ORDER BY i.ilanID DESC");
        $il_sorgu->execute([$iID]); $ilanlarim = $il_sorgu->fetchAll(PDO::FETCH_ASSOC);

        $isveren_ilan_sayisi = count($ilanlarim);

        // Mesaj atabilmek için adayın kullaniciID'sini ve okunmamış mesaj sayısını çekiyoruz
        $bf_sorgu = $db->prepare("
            SELECT b.*, ia.adsoyad, ia.dogumyili, i.baslik, b.tarih, k.kullaniciID as aday_kullanici_id,
            (SELECT COUNT(*) FROM mesaj m WHERE m.milanID = b.bilanID AND m.gonderenID = k.kullaniciID AND m.aliciID = ? AND m.okundu = 0) as okunmamis_mesaj_sayisi
            FROM basvuru b 
            JOIN ilan i ON b.bilanID = i.ilanID 
            JOIN isarayan ia ON b.bisarayanID = ia.isarayanID 
            JOIN kullanici k ON ia.akullaniciID = k.kullaniciID 
            WHERE i.iisverenID = ? 
            ORDER BY b.basvuruID DESC
        ");
        $bf_sorgu->execute([$kID, $iID]); 
        $basvurular_firma = $bf_sorgu->fetchAll(PDO::FETCH_ASSOC);
    }
    if($rol == 1) { $psorgu->execute([$kID]); $profil = $psorgu->fetch(PDO::FETCH_ASSOC); }
}


$aktif_ekran = $_GET['islem'] ?? 'giris';

if ($aktif_ekran == 'cikis') {
    $_SESSION = array();
    session_destroy();
    header("Location: index.php");
    exit;
}

if (!isset($_SESSION['kullaniciID']) && $aktif_ekran == 'profil') {
    header("Location: giris.php?islem=giris");
    exit;
}

if (isset($_SESSION['kullaniciID']) && ($aktif_ekran == 'giris' || $aktif_ekran == 'kayit_sec')) {
    header("Location: index.php");
    exit;
}

if ($aktif_ekran == 'profil' && isset($_SESSION['kullaniciID'])) {
    $kID = $_SESSION['kullaniciID'];
    $rol = $_SESSION['krolID'];
    
    if ($rol == 1) {
        $sorgu = $db->prepare("
            SELECT k.telno, k.email, k.kayittarihi, i.adsoyad, i.dogumyili, i.cinsiyet, 
                   m.mahalleisim, c.ilceisim, l.ilisim 
            FROM kullanici k 
            JOIN isarayan i ON k.kullaniciID = i.akullaniciID 
            LEFT JOIN mahalle m ON i.mahalleID = m.mahalleID 
            LEFT JOIN ilce c ON m.milceID = c.ilceID 
            LEFT JOIN il l ON c.ilID = l.ilID 
            WHERE k.kullaniciID = ?
        ");
        $sorgu->execute([$kID]);
        $profil = $sorgu->fetch(PDO::FETCH_ASSOC);
    } else {
        $sorgu = $db->prepare("
            SELECT k.telno, k.email, k.kayittarihi, v.firmaadi, v.vergino, 
                   m.mahalleisim, c.ilceisim, l.ilisim 
            FROM kullanici k 
            JOIN isveren v ON k.kullaniciID = v.ikullaniciID 
            LEFT JOIN mahalle m ON v.mahalleID = m.mahalleID 
            LEFT JOIN ilce c ON m.milceID = c.ilceID 
            LEFT JOIN il l ON c.ilID = l.ilID 
            WHERE k.kullaniciID = ?
        ");
        $sorgu->execute([$kID]);
        $profil = $sorgu->fetch(PDO::FETCH_ASSOC);
    }
}
?>
<?php 
$page_title = 'Kariyerlen - Portal';
include 'components/header.php'; 
?>
    <style>
        .ana-icerik { display: flex; justify-content: center; align-items: center; min-height: calc(100vh - 80px); padding: 40px 20px; }
        
        .kutu { background: #ffffff; width: 100%; max-width: 550px; padding: 40px; border-radius: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.04); text-align: center; border: 1px solid #f1f1f1; }
        
        /* Yeni Giriş Tasarımı */
        .auth-container { display: flex; width: 100%; min-height: calc(100vh - 75px); background: #ffffff; }
        .auth-left { flex: 1; background: #f8fafc; display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; padding: 40px; }
        .auth-right { flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px; background: #ffffff; }
        
        .auth-circle { width: 180px; height: 180px; background: linear-gradient(135deg, #ffffff 0%, #fff7ed 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 20px 40px rgba(234, 88, 12, 0.1), 0 0 0 40px rgba(255,126,29,0.03), 0 0 0 80px rgba(255,126,29,0.01); position: relative; z-index: 2; border: 1px solid rgba(255,126,29,0.1); }
        .auth-logo-icon { width: 70px; height: 70px; filter: drop-shadow(0 8px 15px rgba(234, 88, 12, 0.3)); }
        .auth-logo-k { font-size: 96px; font-weight: 900; color: #ff7e1d; font-family: 'Inter', sans-serif; letter-spacing: -2px; background: linear-gradient(135deg, #ff9e4f 0%, #ff7e1d 50%, #ea580c 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; filter: drop-shadow(0 4px 10px rgba(255, 126, 29, 0.25)); display: flex; align-items: center; justify-content: center; line-height: 1; user-select: none; margin-bottom: 5px; }
        
        .info-bubble { background: #ffffff; padding: 16px 24px; border-radius: 16px; box-shadow: 0 12px 30px rgba(0,0,0,0.06); position: absolute; display: flex; align-items: center; gap: 12px; font-weight: 600; font-size: 15px; color: #374151; z-index: 3; animation: float 5s ease-in-out infinite; border: 1px solid #f1f5f9; }
        .info-bubble.b1 { top: 25%; left: 15%; animation-delay: 0s; }
        .info-bubble.b2 { top: 40%; right: 10%; animation-delay: 1s; }
        .info-bubble.b3 { bottom: 25%; left: 25%; animation-delay: 2s; }
        .info-bubble.b4 { bottom: 15%; right: 15%; animation-delay: 3s; }
        
        @keyframes float { 0% { transform: translateY(0px); } 50% { transform: translateY(-12px); } 100% { transform: translateY(0px); } }
        .check-icon-auth { background: #ff7e1d; color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; flex-shrink: 0; }

        .auth-form-kutu { width: 100%; max-width: 450px; text-align: center; }
        .auth-form-kutu h2 { font-size: 28px; margin-bottom: 25px; color: #111827; font-weight: 800; }
        
        /* Diğer Profil vs İçin Kutu */
        .profil-avatar { width: 80px; height: 80px; background: #ff7e1d; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 20px auto; box-shadow: 0 8px 16px rgba(255,126,29,0.2); }
        .bilgi-liste { text-align: left; margin-top: 20px; }
        .bilgi-satir { display: flex; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid #f8fafc; font-size: 15px; }
        .bilgi-label { color: #6b7280; font-weight: 500; }
        .bilgi-deger { color: #111827; font-weight: 700; text-align: right; max-width: 60%; }

        .form-grup { text-align: left; margin-bottom: 18px; }
        .form-grup label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 6px; color: #374151; }
        .form-grup input, .form-grup select { width: 100%; padding: 14px 16px; border: 1px solid #e5e7eb; border-radius: 12px; background: #f9fafb; outline: none; transition: 0.3s; font-size: 15px; }
        .form-grup input:focus, .form-grup select:focus { border-color: #ff7e1d; background: #fff; box-shadow: 0 0 0 4px rgba(255,126,29,0.1); }
        
        .form-btn { width: 100%; padding: 16px; background-color: #ff7e1d; color: white; border: none; border-radius: 12px; font-size: 16px; font-weight: 700; cursor: pointer; transition: 0.3s; margin-top: 10px; display: block; text-align: center; }
        .form-btn:hover { background-color: #ea580c; transform: translateY(-1px); box-shadow: 0 6px 15px rgba(234,88,12,0.2); }

        .ikili-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .uclu-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; }
        .secenek-kutu { display: flex; gap: 15px; margin: 20px 0; }
        .secenek-kart { flex: 1; padding: 30px 20px; border: 2px solid #f1f5f9; border-radius: 16px; cursor: pointer; transition: 0.3s; color: #374151; text-align: center; background: #fff; }
        .secenek-kart:hover { border-color: #ff7e1d; background: #fffaf5; transform: translateY(-3px); box-shadow: 0 10px 20px rgba(255,126,29,0.05); }

        .ayirici { display: flex; align-items: center; text-align: center; margin: 25px 0; color: #9ca3af; font-size: 14px; font-weight: 500; }
        .ayirici::before, .ayirici::after { content: ''; flex: 1; border-bottom: 1px solid #e5e7eb; }
        .ayirici:not(:empty)::before { margin-right: .5em; }
        .ayirici:not(:empty)::after { margin-left: .5em; }

        .sosyal-btn { width: 100%; padding: 14px; background: white; border: 1px solid #e5e7eb; border-radius: 12px; color: #374151; font-weight: 600; font-size: 15px; display: flex; align-items: center; justify-content: center; gap: 10px; cursor: pointer; transition: 0.2s; margin-bottom: 12px; }
        .sosyal-btn:hover { background: #f9fafb; border-color: #d1d5db; }

        .yonlendirme { margin-top: 25px; font-size: 14px; color: #6b7280; font-weight: 500; }
        .yonlendirme a { color: #ff7e1d; font-weight: 700; text-decoration: none; }
        .yonlendirme a:hover { text-decoration: underline; }
        .hata { background: #fef2f2; color: #dc2626; padding: 12px; border-radius: 8px; margin-bottom: 15px; font-size: 13px; border: 1px solid #fee2e2; font-weight: bold; }

        @media screen and (max-width: 768px) {
            header { display: flex; flex-direction: column; align-items: center; gap: 15px; padding: 20px 15px; height: auto; }
            .logo { order: 1; }
            .sag-menu { order: 2; width: 100%; justify-content: center; flex-wrap: wrap; gap: 10px; }
            .nav-link, .btn-turuncu-nav, .profil-buton { font-size: 13px !important; padding: 8px 16px !important; }
            .ana-icerik { padding: 20px 15px; }
            .kutu { padding: 25px 20px; border-radius: 16px; }
            .auth-container { flex-direction: column; }
            .auth-left { display: none; } /* Hide visuals on mobile */
            .auth-right { padding: 30px 20px; }
            .ikili-row, .uclu-row { grid-template-columns: 1fr; gap: 15px; }
            .secenek-kutu { flex-direction: column; gap: 15px; }
            .secenek-kart { padding: 20px; }
            .bilgi-satir { flex-direction: column; gap: 5px; text-align: left; }
            .bilgi-deger { text-align: left; max-width: 100%; }
            .dropdown-icerik { right: auto; left: 50%; transform: translateX(-50%); width: max-content; min-width: 180px; }
        }
    /* Profesyonel Başarı Modal CSS */
    .onay-konteynir { text-align: center; padding: 20px; }
    .basari-animasyon { width: 80px; height: 80px; margin: 0 auto 25px; position: relative; }
    .basari-daire { width: 80px; height: 80px; border: 4px solid #10b981; border-radius: 50%; position: absolute; top: 0; left: 0; animation: daireAnim 0.6s ease-in-out forwards; opacity: 0; }
    .basari-tik { width: 40px; height: 20px; border-left: 5px solid #10b981; border-bottom: 5px solid #10b981; position: absolute; top: 25px; left: 20px; transform: rotate(-45deg); opacity: 0; animation: tikAnim 0.4s 0.5s ease-in-out forwards; }
    @keyframes daireAnim { from { transform: scale(0.5); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    @keyframes tikAnim { from { width: 0; height: 0; opacity: 0; } to { width: 40px; height: 20px; opacity: 1; } }
    
    .mesaj-baslik-yeni { font-size: 24px; font-weight: 800; color: #111827; margin-bottom: 12px; }
    .mesaj-metin-yeni { font-size: 16px; color: #6b7280; margin-bottom: 30px; line-height: 1.5; }
    .btn-onay-kapat { background: #111827; color: white; border: none; padding: 14px 40px; border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.3s; font-size: 15px; }
    .btn-onay-kapat:hover { background: #000; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
    </style>


<?php if(in_array($aktif_ekran, ['giris', 'kayit_sec', 'kayit_bireysel', 'kayit_kurumsal'])): ?>
    <div class="auth-container">
        <div class="auth-left">
            <div class="info-bubble b1">
                <svg width="20" height="20" style="flex-shrink:0;"><use xlink:href="#icon-tick"></use></svg>
                Sizi bekleyen <span style="color:#ff7e1d;">binlerce ilan</span>
            </div>
            <div class="info-bubble b2">
                <svg width="20" height="20" style="flex-shrink:0;"><use xlink:href="#icon-tick"></use></svg>
                Sizi keşfedecek <span style="color:#ff7e1d;">150 bin işveren</span>
            </div>
            <div class="info-bubble b3">
                <svg width="20" height="20" style="flex-shrink:0;"><use xlink:href="#icon-tick"></use></svg>
                <span style="color:#ff7e1d;">7 binden fazla</span> maaş verisi
            </div>
            <div class="info-bubble b4">
                <svg width="20" height="20" style="flex-shrink:0;"><use xlink:href="#icon-tick"></use></svg>
                Hızlı ve <span style="color:#ff7e1d;">kolay başvuru</span>
            </div>
            <div class="auth-circle">
                <span class="auth-logo-k">K</span>
            </div>
        </div>
        <div class="auth-right">
            
            <?php if($aktif_ekran == 'giris'): ?>
            <div class="auth-form-kutu">
                <h2>Kariyerlen'e hoş geldin!</h2>
                <?php if(isset($_SESSION['hata'])) { echo '<div class="hata">'.$_SESSION['hata'].'</div>'; unset($_SESSION['hata']); } ?>
                <form action="islem.php" method="POST">
                    <input type="hidden" name="islem" value="giris">
                    <div class="form-grup"><label>Kullanıcı adı veya E-posta</label><input type="email" name="email" required placeholder="E-posta adresiniz"></div>
                    <div class="form-grup"><label>Şifre</label><input type="password" name="sifre" required placeholder="••••••••"></div>
                    <button type="submit" class="form-btn">Devam et</button>
                </form>
                
                <div class="ayirici">ya da</div>
                
                <button type="button" class="sosyal-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24"><path fill="#EA4335" d="M12.545,10.239v3.821h5.445c-0.712,2.315-2.647,3.972-5.445,3.972c-3.332,0-6.033-2.701-6.033-6.032s2.701-6.032,6.033-6.032c1.498,0,2.866,0.549,3.921,1.453l2.814-2.814C17.503,2.988,15.139,2,12.545,2C7.021,2,2.543,6.477,2.543,12s4.478,10,10.002,10c8.396,0,10.249-7.85,9.426-11.748L12.545,10.239z"/></svg>
                    Google ile devam et
                </button>
                <button type="button" class="sosyal-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24"><path fill="#000000" d="M17.05,20.28c-.98.95-2.05,1.8-3.08,1.8-1.09,0-1.46-.66-2.76-.66-1.32,0-1.74.65-2.79.65-1.11,0-2.28-.96-3.23-2.02-1.63-1.81-2.88-5.18-2.14-8.03.48-1.85,1.8-3.08,3.29-3.1,1.04-.02,2.02.69,2.69.69.66,0,1.87-.84,3.13-.71,1.34.13,2.57.74,3.25,1.74-2.78,1.64-2.31,5.55.51,6.67-.65,1.55-1.55,3.04-2.87,4.02v.01Z"/><path fill="#000000" d="M12.03,4.98c-.16-2.11,1.66-4.04,3.7-4.18.23,2.23-1.81,4.2-3.7,4.18Z"/></svg>
                    Apple ile devam et
                </button>

                <div class="yonlendirme">Hesabınız yok mu? <a href="giris.php?islem=kayit_sec">Kayıt Ol</a></div>
            </div>
            <?php endif; ?>

            <?php if($aktif_ekran == 'kayit_sec'): ?>
            <div class="auth-form-kutu" style="max-width: 550px;">
                <h2 style="font-size:32px; color:#111827; font-weight:900; margin-bottom:10px;">Kayıt Ol</h2>
                <div class="secenek-kutu" style="gap:25px; margin: 40px 0;">
                    <a href="giris.php?islem=kayit_bireysel" class="secenek-kart" style="display:flex; flex-direction:column; align-items:center; justify-content:center; height: 210px; border-radius: 20px; border: 1px solid #e5e7eb;">
                        <svg width="85" height="85" style="margin-bottom:20px; filter: drop-shadow(0px 10px 15px rgba(255, 126, 29, 0.25));"><use xlink:href="#icon-user"></use></svg>
                        <b style="font-size:22px; color:#1f2937; font-weight:800;">İş Arayan</b>
                    </a>
                    <a href="giris.php?islem=kayit_kurumsal" class="secenek-kart" style="display:flex; flex-direction:column; align-items:center; justify-content:center; height: 210px; border-radius: 20px; border: 1px solid #e5e7eb;">
                        <svg width="85" height="85" style="margin-bottom:20px; filter: drop-shadow(0px 10px 15px rgba(30, 58, 138, 0.25));"><use xlink:href="#icon-company"></use></svg>
                        <b style="font-size:22px; color:#1f2937; font-weight:800;">İş Veren</b>
                    </a>
                </div>
                <div class="yonlendirme" style="margin-top:20px; font-size:16px;">Hesabınız var mı ? <a href="giris.php?islem=giris" style="color:#ff7e1d;">Giriş Yap</a></div>
            </div>
            <?php endif; ?>

            <?php if($aktif_ekran == 'kayit_bireysel'): ?>
            <div class="auth-form-kutu" style="max-width: 550px;">
                <h2 style="margin-bottom: 15px;">İş Arayan Kaydı</h2>
                <p style="color: #6b7280; font-size: 15px; margin-bottom: 25px;">Hayalindeki işi bulmak için hemen ücretsiz hesap oluştur.</p>
                <?php if(isset($_SESSION['hata'])) { echo '<div class="hata">'.$_SESSION['hata'].'</div>'; unset($_SESSION['hata']); } ?>
                <form action="islem.php" method="POST" onsubmit="return sifreKontrol('sifre1_b', 'sifre2_b')">
                    <input type="hidden" name="islem" value="kayit_bireysel">
                    <div class="form-grup"><label>Ad Soyad</label><input type="text" name="adsoyad" required></div>
                    <div class="ikili-row">
                        <div class="form-grup"><label>Doğum Yılı</label><input type="number" name="dogumyili" required></div>
                        <div class="form-grup"><label>Cinsiyet</label><select name="cinsiyet" required><option value="">Seçiniz</option><option value="E">Erkek</option><option value="K">Kadın</option></select></div>
                    </div>
                    <div class="uclu-row" style="overflow:visible;">
                        <div class="form-grup">
                            <label>İl</label>
                            <div class="custom-dropdown" id="regBireyselIlDropdown">
                                <div class="dropdown-trigger">
                                    <span>Seçiniz</span>
                                    <svg width="14" height="14"><use xlink:href="#icon-chevron"></use></svg>
                                </div>
                                <div class="dropdown-menu">
                                    <div class="dropdown-option" data-value="">Seçiniz</div>
                                    <?php $iller = $db->query("SELECT * FROM il ORDER BY ilisim ASC")->fetchAll(); foreach($iller as $il): ?>
                                        <div class="dropdown-option" data-value="<?php echo $il['ilID']; ?>"><?php echo htmlspecialchars($il['ilisim']); ?></div>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" id="reg_b_il_input" required>
                            </div>
                        </div>
                        <div class="form-grup">
                            <label>İlçe</label>
                            <div class="custom-dropdown disabled" id="regBireyselIlceDropdown">
                                <div class="dropdown-trigger">
                                    <span>Seçiniz</span>
                                    <svg width="14" height="14"><use xlink:href="#icon-chevron"></use></svg>
                                </div>
                                <div class="dropdown-menu">
                                    <div class="dropdown-option" data-value="">Seçiniz</div>
                                </div>
                                <input type="hidden" id="reg_b_ilce_input" required>
                            </div>
                        </div>
                        <div class="form-grup">
                            <label>Mahalle</label>
                            <div class="custom-dropdown disabled" id="regBireyselMahalleDropdown">
                                <div class="dropdown-trigger">
                                    <span>Seçiniz</span>
                                    <svg width="14" height="14"><use xlink:href="#icon-chevron"></use></svg>
                                </div>
                                <div class="dropdown-menu">
                                    <div class="dropdown-option" data-value="">Seçiniz</div>
                                </div>
                                <input type="hidden" name="mahalle_id" id="reg_b_mahalle_input" required>
                            </div>
                        </div>
                    </div>
                    <div class="ikili-row">
                        <div class="form-grup"><label>Telefon No</label><input type="text" name="telno" required></div>
                        <div class="form-grup"><label>E-posta</label><input type="email" name="email" required></div>
                    </div>
                    <div class="ikili-row">
                        <div class="form-grup"><label>Şifre</label><input type="password" name="sifre" id="sifre1_b" required></div>
                        <div class="form-grup"><label>Şifre Tekrar</label><input type="password" name="sifre_tekrar" id="sifre2_b" required></div>
                    </div>
                    <button type="submit" class="form-btn" style="margin-top:20px;">Kaydı Tamamla</button>
                </form>
                <div class="yonlendirme">Hesabınız var mı ?<a href="giris.php?islem=giris">Giriş Yap</a></div>
            </div>
            <?php endif; ?>

            <?php if($aktif_ekran == 'kayit_kurumsal'): ?>
            <div class="auth-form-kutu" style="max-width: 550px;">
                <h2 style="margin-bottom: 15px;">İş Veren Kaydı</h2>
                <p style="color: #6b7280; font-size: 15px; margin-bottom: 25px;">Yeteneği bulmak için şirket hesabınızı hemen oluşturun.</p>
                <?php if(isset($_SESSION['hata'])) { echo '<div class="hata">'.$_SESSION['hata'].'</div>'; unset($_SESSION['hata']); } ?>
                <form action="islem.php" method="POST" onsubmit="return sifreKontrol('sifre1_k', 'sifre2_k')">
                    <input type="hidden" name="islem" value="kayit_kurumsal">
                    <div class="ikili-row">
                        <div class="form-grup"><label>Firma Adı</label><input type="text" name="firmaadi" required></div>
                        <div class="form-grup"><label>Vergi Numarası</label><input type="text" name="vergino" required></div>
                    </div>
                    <div class="uclu-row" style="overflow:visible;">
                        <div class="form-grup">
                            <label>İl</label>
                            <div class="custom-dropdown" id="regKurumsalIlDropdown">
                                <div class="dropdown-trigger">
                                    <span>Seçiniz</span>
                                    <svg width="14" height="14"><use xlink:href="#icon-chevron"></use></svg>
                                </div>
                                <div class="dropdown-menu">
                                    <div class="dropdown-option" data-value="">Seçiniz</div>
                                    <?php foreach($iller as $il): ?>
                                        <div class="dropdown-option" data-value="<?php echo $il['ilID']; ?>"><?php echo htmlspecialchars($il['ilisim']); ?></div>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" id="reg_k_il_input" required>
                            </div>
                        </div>
                        <div class="form-grup">
                            <label>İlçe</label>
                            <div class="custom-dropdown disabled" id="regKurumsalIlceDropdown">
                                <div class="dropdown-trigger">
                                    <span>Seçiniz</span>
                                    <svg width="14" height="14"><use xlink:href="#icon-chevron"></use></svg>
                                </div>
                                <div class="dropdown-menu">
                                    <div class="dropdown-option" data-value="">Seçiniz</div>
                                </div>
                                <input type="hidden" id="reg_k_ilce_input" required>
                            </div>
                        </div>
                        <div class="form-grup">
                            <label>Mahalle</label>
                            <div class="custom-dropdown disabled" id="regKurumsalMahalleDropdown">
                                <div class="dropdown-trigger">
                                    <span>Seçiniz</span>
                                    <svg width="14" height="14"><use xlink:href="#icon-chevron"></use></svg>
                                </div>
                                <div class="dropdown-menu">
                                    <div class="dropdown-option" data-value="">Seçiniz</div>
                                </div>
                                <input type="hidden" name="mahalle_id" id="reg_k_mahalle_input" required>
                            </div>
                        </div>
                    </div>
                    <div class="ikili-row">
                        <div class="form-grup"><label>Telefon No</label><input type="text" name="telno" required></div>
                        <div class="form-grup"><label>E-posta</label><input type="email" name="email" required></div>
                    </div>
                    <div class="ikili-row">
                        <div class="form-grup"><label>Şifre</label><input type="password" name="sifre" id="sifre1_k" required></div>
                        <div class="form-grup"><label>Şifre Tekrar</label><input type="password" name="sifre_tekrar" id="sifre2_k" required></div>
                    </div>
                    <button type="submit" class="form-btn" style="margin-top:20px;">Şirket Kaydını Tamamla</button>
                </form>
                <div class="yonlendirme">Hesabınız var mı ? <a href="giris.php?islem=giris">Giriş Yap</a></div>
            </div>
            <?php endif; ?>

        </div>
    </div>
<?php else: ?>
    <div class="ana-icerik">
        
        <?php if($aktif_ekran == 'profil' && isset($profil)): ?>
        <div class="kutu" style="max-width: 650px;box-shadow:0 8px 32px rgba(0,0,0,0.07);border-radius:28px;padding:48px 36px 36px 36px;">
            <div style="display:flex;align-items:center;gap:28px;margin-bottom:30px;flex-wrap:wrap;justify-content:center;">
                <div style="position:relative;">
                    <?php $profilFoto = isset($_SESSION['fotograf']) && $_SESSION['fotograf'] ? $_SESSION['fotograf'] : null; ?>
                    <img src="<?php echo $profilFoto ? 'uploads/' . htmlspecialchars($profilFoto) : 'img/home/default_avatar.png'; ?>" alt="Profil Fotoğrafı" style="width:110px;height:110px;object-fit:cover;border-radius:50%;border:4px solid #ffedd5;box-shadow:0 4px 18px rgba(234,88,12,0.07);background:#fff;">
                    <form action="islem.php" method="POST" enctype="multipart/form-data" style="position:absolute;bottom:-10px;right:-10px;">
                        <input type="hidden" name="islem" value="profil_foto_yukle">
                        <label style="background:#ff7e1d;color:#fff;padding:6px 12px;border-radius:20px;font-size:13px;cursor:pointer;box-shadow:0 2px 8px rgba(234,88,12,0.10);">
                            Fotoğraf Yükle
                            <input type="file" name="profil_foto" accept="image/*" style="display:none;" onchange="this.form.submit()">
                        </label>
                    </form>
                </div>
                <div>
                    <h2 style="margin-bottom: 5px;font-size:28px;font-weight:800;color:#111827;">Hesap Bilgilerim</h2>
                    <p style="color: #6b7280; font-size: 15px; margin-bottom: 0;">Tüm profil detaylarınız aşağıda.</p>
                </div>
            </div>

            <div class="bilgi-liste" style="margin-top:0;">
                <div class="bilgi-satir"><span class="bilgi-label">Hesap Türü</span><span class="bilgi-deger" style="color: #ff7e1d;"><?php echo ($rol == 1) ? 'İş Arayan' : 'İş Veren'; ?></span></div>
                
                <?php if($rol == 1): ?>
                    <div class="bilgi-satir"><span class="bilgi-label">Ad Soyad</span><span class="bilgi-deger"><?php echo htmlspecialchars($profil['adsoyad']); ?></span></div>
                    <div class="bilgi-satir"><span class="bilgi-label">Doğum Yılı</span><span class="bilgi-deger"><?php echo htmlspecialchars($profil['dogumyili']); ?></span></div>
                    <div class="bilgi-satir"><span class="bilgi-label">Cinsiyet</span><span class="bilgi-deger"><?php echo ($profil['cinsiyet'] == 'E') ? 'Erkek' : 'Kadın'; ?></span></div>
                <?php else: ?>
                    <div class="bilgi-satir"><span class="bilgi-label">Firma Adı</span><span class="bilgi-deger"><?php echo htmlspecialchars($profil['firmaadi']); ?></span></div>
                    <div class="bilgi-satir"><span class="bilgi-label">Vergi Numarası</span><span class="bilgi-deger"><?php echo htmlspecialchars($profil['vergino']); ?></span></div>
                <?php endif; ?>

                <div class="bilgi-satir"><span class="bilgi-label">E-posta</span><span class="bilgi-deger"><?php echo htmlspecialchars($profil['email']); ?></span></div>
                <div class="bilgi-satir"><span class="bilgi-label">Telefon Numarası</span><span class="bilgi-deger"><?php echo htmlspecialchars($profil['telno']); ?></span></div>
                
                <?php 
                $tamAdres = "Belirtilmemiş";
                if(!empty($profil['ilisim'])) {
                    $tamAdres = $profil['ilisim'] . " / " . $profil['ilceisim'] . " / " . $profil['mahalleisim'];
                }
                ?>
                <div class="bilgi-satir"><span class="bilgi-label">Kayıtlı Adres</span><span class="bilgi-deger"><?php echo htmlspecialchars($tamAdres); ?></span></div>
                
                <div class="bilgi-satir"><span class="bilgi-label">Kayıt Tarihi</span><span class="bilgi-deger"><?php echo date("d.m.Y", strtotime($profil['kayittarihi'])); ?></span></div>
            </div>

            <a href="index.php" class="form-btn" style="border-radius:50px; width:100%;">Anasayfaya Dön</a>
        </div>
        <?php endif; ?>

    </div>
<?php endif; ?>

<script>
function sifreKontrol(id1, id2) {
    if(document.getElementById(id1).value !== document.getElementById(id2).value) { alert("Şifreler uyuşmuyor!"); return false; } return true;
}

document.addEventListener("DOMContentLoaded", function() {
    const urlParams = new URLSearchParams(window.location.search);
    const mesajType = urlParams.get('mesaj');
    if(mesajType) {
        let title = 'Bilgi'; let text = '';
        if(mesajType === 'kayit_basarili') { title = 'Aramıza Hoş Geldin!'; text = 'Kaydınız başarıyla tamamlandı. Şimdi giriş yapabilirsiniz.'; }
        else if(mesajType === 'profil_guncellendi') { title = 'Güncellendi!'; text = 'Profil bilgileriniz başarıyla kaydedildi.'; }
        else if(mesajType === 'sifre_degisti') { title = 'Şifre Güncellendi'; text = 'Şifreniz başarıyla değiştirildi.'; }
        else if(mesajType === 'hata') { title = 'Hata'; text = 'Bir işlem sırasında hata oluştu.'; }
        else if(mesajType === 'ilan_yayinda') { title = 'Tebrikler!'; text = 'İlanınız başarıyla yayına alındı.'; }
        else if(mesajType === 'basvuru_basarili') { title = 'Tebrikler!'; text = 'Başvurunuz başarıyla firmaya iletildi.'; }
        else if(mesajType === 'zaten_basvuruldu') { title = 'Uyarı'; text = 'Bu ilana zaten başvuru yaptınız.'; }
        else if(mesajType === 'ilan_guncellendi') { title = 'Başarılı!'; text = 'İlanınız başarıyla güncellendi.'; }

        if(text !== ''){
            const titleEl = document.getElementById('mesaj_baslik');
            const textEl = document.getElementById('mesaj_metin');
            if(titleEl && textEl) {
                titleEl.innerText = title;
                textEl.innerText = text;
                modalAc('basariModal');
            }
            window.history.replaceState(null, null, window.location.pathname);
        }
    }

    initCustomDropdown('regBireyselIlDropdown', 'reg_b_il_input');
    initCustomDropdown('regBireyselIlceDropdown', 'reg_b_ilce_input');
    initCustomDropdown('regBireyselMahalleDropdown', 'reg_b_mahalle_input');

    initCustomDropdown('regKurumsalIlDropdown', 'reg_k_il_input');
    initCustomDropdown('regKurumsalIlceDropdown', 'reg_k_ilce_input');
    initCustomDropdown('regKurumsalMahalleDropdown', 'reg_k_mahalle_input');

    function initCustomDropdown(id, inputId) {
        const d = document.getElementById(id); if(!d) return;
        const trigger = d.querySelector('.dropdown-trigger');
        const input = document.getElementById(inputId);
        if(!trigger) return;

        trigger.onclick = (e) => {
            e.stopPropagation();
            document.querySelectorAll('.custom-dropdown.active').forEach(dd => {
                if(dd !== d) dd.classList.remove('active');
            });
            d.classList.toggle('active');
        };

        d.querySelectorAll('.dropdown-option').forEach(opt => {
            opt.onclick = (e) => {
                e.stopPropagation();
                input.value = opt.dataset.value;
                trigger.querySelector('span').innerText = opt.innerText;
                d.classList.remove('active');
                input.dispatchEvent(new Event('change'));
            };
        });
    }

    const bIlInput = document.getElementById('reg_b_il_input');
    if(bIlInput) {
        bIlInput.addEventListener('change', async function() {
            const ilceDropdown = document.getElementById('regBireyselIlceDropdown');
            const ilceMenu = ilceDropdown.querySelector('.dropdown-menu');
            const ilceTriggerSpan = ilceDropdown.querySelector('.dropdown-trigger span');
            const ilceInput = document.getElementById('reg_b_ilce_input');
            
            ilceMenu.innerHTML = '<div class="dropdown-option" data-value="">Yükleniyor...</div>';
            ilceTriggerSpan.innerText = 'Seçiniz';
            ilceInput.value = '';
            ilceDropdown.classList.add('disabled');

            if(!this.value) return;
            try {
                const res = await fetch('adres_getir.php?islem=ilce&il_id=' + this.value);
                const data = await res.json();
                ilceMenu.innerHTML = '<div class="dropdown-option" data-value="">Seçiniz</div>';
                data.forEach(d => {
                    const opt = document.createElement('div');
                    opt.className = 'dropdown-option';
                    opt.dataset.value = d.ilceID;
                    opt.textContent = d.ilceisim;
                    ilceMenu.appendChild(opt);
                });
                ilceDropdown.classList.remove('disabled');
                // Re-bind options
                ilceMenu.querySelectorAll('.dropdown-option').forEach(opt => {
                    opt.onclick = (e) => {
                        e.stopPropagation();
                        ilceInput.value = opt.dataset.value;
                        ilceTriggerSpan.innerText = opt.innerText;
                        ilceDropdown.classList.remove('active');
                        ilceInput.dispatchEvent(new Event('change'));
                    };
                });
            } catch(e) { console.error(e); }
        });
    }

    const bIlceInput = document.getElementById('reg_b_ilce_input');
    if(bIlceInput) {
        bIlceInput.addEventListener('change', async function() {
            const mahalleDropdown = document.getElementById('regBireyselMahalleDropdown');
            const mahalleMenu = mahalleDropdown.querySelector('.dropdown-menu');
            const mahalleTriggerSpan = mahalleDropdown.querySelector('.dropdown-trigger span');
            const mahalleInput = document.getElementById('reg_b_mahalle_input');
            
            mahalleMenu.innerHTML = '<div class="dropdown-option" data-value="">Yükleniyor...</div>';
            mahalleTriggerSpan.innerText = 'Seçiniz';
            mahalleInput.value = '';
            mahalleDropdown.classList.add('disabled');

            if(!this.value) return;
            try {
                const res = await fetch('adres_getir.php?islem=mahalle&ilce_id=' + this.value);
                const data = await res.json();
                mahalleMenu.innerHTML = '<div class="dropdown-option" data-value="">Seçiniz</div>';
                data.forEach(d => {
                    const opt = document.createElement('div');
                    opt.className = 'dropdown-option';
                    opt.dataset.value = d.mahalleID;
                    opt.textContent = d.mahalleisim;
                    mahalleMenu.appendChild(opt);
                });
                mahalleDropdown.classList.remove('disabled');
                // Re-bind options
                mahalleMenu.querySelectorAll('.dropdown-option').forEach(opt => {
                    opt.onclick = (e) => {
                        e.stopPropagation();
                        mahalleInput.value = opt.dataset.value;
                        mahalleTriggerSpan.innerText = opt.innerText;
                        mahalleDropdown.classList.remove('active');
                    };
                });
            } catch(e) { console.error(e); }
        });
    }

    const kIlInput = document.getElementById('reg_k_il_input');
    if(kIlInput) {
        kIlInput.addEventListener('change', async function() {
            const ilceDropdown = document.getElementById('regKurumsalIlceDropdown');
            const ilceMenu = ilceDropdown.querySelector('.dropdown-menu');
            const ilceTriggerSpan = ilceDropdown.querySelector('.dropdown-trigger span');
            const ilceInput = document.getElementById('reg_k_ilce_input');
            
            ilceMenu.innerHTML = '<div class="dropdown-option" data-value="">Yükleniyor...</div>';
            ilceTriggerSpan.innerText = 'Seçiniz';
            ilceInput.value = '';
            ilceDropdown.classList.add('disabled');

            if(!this.value) return;
            try {
                const res = await fetch('adres_getir.php?islem=ilce&il_id=' + this.value);
                const data = await res.json();
                ilceMenu.innerHTML = '<div class="dropdown-option" data-value="">Seçiniz</div>';
                data.forEach(d => {
                    const opt = document.createElement('div');
                    opt.className = 'dropdown-option';
                    opt.dataset.value = d.ilceID;
                    opt.textContent = d.ilceisim;
                    ilceMenu.appendChild(opt);
                });
                ilceDropdown.classList.remove('disabled');
                // Re-bind options
                ilceMenu.querySelectorAll('.dropdown-option').forEach(opt => {
                    opt.onclick = (e) => {
                        e.stopPropagation();
                        ilceInput.value = opt.dataset.value;
                        ilceTriggerSpan.innerText = opt.innerText;
                        ilceDropdown.classList.remove('active');
                        ilceInput.dispatchEvent(new Event('change'));
                    };
                });
            } catch(e) { console.error(e); }
        });
    }

    const kIlceInput = document.getElementById('reg_k_ilce_input');
    if(kIlceInput) {
        kIlceInput.addEventListener('change', async function() {
            const mahalleDropdown = document.getElementById('regKurumsalMahalleDropdown');
            const mahalleMenu = mahalleDropdown.querySelector('.dropdown-menu');
            const mahalleTriggerSpan = mahalleDropdown.querySelector('.dropdown-trigger span');
            const mahalleInput = document.getElementById('reg_k_mahalle_input');
            
            mahalleMenu.innerHTML = '<div class="dropdown-option" data-value="">Yükleniyor...</div>';
            mahalleTriggerSpan.innerText = 'Seçiniz';
            mahalleInput.value = '';
            mahalleDropdown.classList.add('disabled');

            if(!this.value) return;
            try {
                const res = await fetch('adres_getir.php?islem=mahalle&ilce_id=' + this.value);
                const data = await res.json();
                mahalleMenu.innerHTML = '<div class="dropdown-option" data-value="">Seçiniz</div>';
                data.forEach(d => {
                    const opt = document.createElement('div');
                    opt.className = 'dropdown-option';
                    opt.dataset.value = d.mahalleID;
                    opt.textContent = d.mahalleisim;
                    mahalleMenu.appendChild(opt);
                });
                mahalleDropdown.classList.remove('disabled');
                // Re-bind options
                mahalleMenu.querySelectorAll('.dropdown-option').forEach(opt => {
                    opt.onclick = (e) => {
                        e.stopPropagation();
                        mahalleInput.value = opt.dataset.value;
                        mahalleTriggerSpan.innerText = opt.innerText;
                        mahalleDropdown.classList.remove('active');
                    };
                });
            } catch(e) { console.error(e); }
        });
    }
});
</script>

<?php include 'components/footer.php'; ?>

</body>
</html>
