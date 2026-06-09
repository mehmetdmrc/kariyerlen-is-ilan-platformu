<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
        if($profil) {
            $iID = $profil['isverenID'];

            $il_sorgu = $db->prepare("SELECT i.*, ct.calismatur FROM ilan i LEFT JOIN calismaturu ct ON i.icalismaturID = ct.calismaID WHERE i.iisverenID = ? ORDER BY i.ilanID DESC");
            $il_sorgu->execute([$iID]); $ilanlarim = $il_sorgu->fetchAll(PDO::FETCH_ASSOC);

            $isveren_ilan_sayisi = count($ilanlarim);

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
    }
    if($rol == 1) { $psorgu->execute([$kID]); $profil = $psorgu->fetch(PDO::FETCH_ASSOC); }
}

$sekme = $_GET['sekme'] ?? 'one_cikan';
$ek_sorgu = "";
if($sekme == 'part_time') {
    $ek_sorgu = " AND c.calismatur LIKE '%Yarı Zamanlı%' ";
}

$sql = "SELECT i.*, v.firmaadi, c.calismatur FROM ilan i JOIN isveren v ON i.iisverenID = v.isverenID LEFT JOIN calismaturu c ON i.icalismaturID = c.calismaID WHERE i.idurumID = 1 $ek_sorgu ORDER BY i.ilanID DESC LIMIT 9";
$ilanlar_sorgu = $db->prepare($sql);
$ilanlar_sorgu->execute();
$ilanlar = $ilanlar_sorgu->fetchAll(PDO::FETCH_ASSOC);

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
?>
<?php 
$page_title = 'Kariyerlen - Hayalinizdeki İşi Bulun';
include 'components/header.php'; 
?>

<style>
    /* ── HERO ── */
    .hero-bolumu {
        background: #fff;
        border-bottom: 1px solid var(--border);
        padding: 72px 24px 64px;
    }
    .hero-container {
        max-width: 1280px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        gap: 64px;
        flex-wrap: wrap;
    }
    .hero-text { flex: 1.4; min-width: 300px; }
    .hero-text h1 {
        font-size: 48px;
        font-weight: 900;
        color: var(--text-main);
        margin-bottom: 16px;
        line-height: 1.15;
        letter-spacing: -1.5px;
    }
    .hero-text h1 span { color: var(--primary); }
    .hero-text p {
        font-size: 17px;
        color: var(--text-muted);
        margin-bottom: 32px;
        line-height: 1.7;
        max-width: 520px;
    }

    /* Search box - isinolsun style */
    .arama-kutusu {
        display: flex;
        align-items: center;
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 16px;
        /* overflow: hidden; Removed to show dropdown */
        max-width: 700px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.06);
        position: relative;
    }
    .arama-input {
        flex: 1;
        border: none;
        outline: none;
        padding: 14px 18px;
        font-size: 15px;
        background: transparent;
        font-weight: 500;
        font-family: inherit;
    }
    .arama-ayirici { width: 1px; height: 28px; background: var(--border); flex-shrink: 0; }
    .arama-btn {
        background: var(--primary);
        color: #fff;
        border: none;
        padding: 14px 28px;
        font-weight: 700;
        font-size: 15px;
        cursor: pointer;
        font-family: inherit;
        flex-shrink: 0;
        transition: background 0.15s;
        border-radius: 0 16px 16px 0;
    }
    .arama-btn:hover { background: var(--primary-hover); }

    /* Category chips */
    .kategori-chips {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 20px;
    }
    .chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 14px;
        background: var(--bg);
        border: 1px solid var(--border);
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-muted);
        cursor: pointer;
        transition: all 0.15s;
        text-decoration: none;
    }
    .chip:hover { border-color: var(--primary); color: var(--primary); background: #fff4ec; }

    /* Stats */
    .hero-stats {
        display: flex;
        gap: 32px;
        margin-top: 36px;
        flex-wrap: wrap;
    }
    .hero-stat { }
    .hero-stat-num { font-size: 26px; font-weight: 900; color: var(--text-main); letter-spacing: -1px; }
    .hero-stat-num span { color: var(--primary); }
    .hero-stat-label { font-size: 13px; color: var(--text-muted); font-weight: 500; }

    /* ── JOB LISTINGS ── */
    .ilanlar-section { max-width: 1280px; margin: 60px auto; padding: 0 24px; }
    .bolum-baslik {
        font-size: 22px;
        font-weight: 800;
        color: var(--text-main);
        margin-bottom: 24px;
        letter-spacing: -0.5px;
    }
    .bolum-baslik-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 24px;
    }
    .bolum-tum-link {
        font-size: 14px;
        font-weight: 600;
        color: var(--primary);
    }
    .bolum-tum-link:hover { text-decoration: underline; }

    /* Job cards - Grid layout */
    .ilan-grid { 
        display: grid; 
        grid-template-columns: repeat(3, 1fr); 
        gap: 24px; 
        background: transparent; 
        border: none; 
        border-radius: 0; 
        overflow: visible; 
    }

    .io-kart {
        background: #fff;
        padding: 16px;
        display: flex;
        flex-direction: row;
        gap: 16px;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
        border: 1px solid #eef0f2;
        border-radius: 18px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        height: 100%;
        align-items: flex-start;
    }
    .io-kart:hover { 
        background: #fff; 
        transform: translateY(-4px);
        box-shadow: var(--shadow-md);
        border-color: var(--primary);
    }
    .io-kart:first-child, .io-kart:last-child { border-radius: 18px; }

    .io-logo {
        width: 64px;
        height: 64px;
        background: #f4f9f1; /* Light green background from image */
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        color: #8dbd7b; /* Storefront icon color from image */
    }
    .io-icerik { flex: 1; min-width: 0; display: flex; flex-direction: column; height: 100%; }
    .io-baslik { font-size: 16px; font-weight: 700; color: #111; margin-bottom: 4px; line-height: 1.2; }
    .io-firma { font-size: 14px; color: #555; font-weight: 500; display: flex; align-items: center; gap: 4px; margin-bottom: 2px; }
    .io-firma svg { color: #3b82f6; flex-shrink: 0; } /* Blue checkmark as per image */
    .io-konum { font-size: 14px; color: #666; margin-bottom: 12px; }

    .io-alt { display: flex; align-items: center; justify-content: space-between; margin-top: auto; width: 100%; padding-top: 8px; }
    .io-tur {
        font-size: 13px;
        font-weight: 500;
        color: #222;
    }
    .io-zaman { font-size: 13px; color: #a35a16ff; font-weight: 600; }
    .io-yeni { font-size: 13px; font-weight: 600; color: #16a34a; }

    .io-favori {
        position: absolute;
        top: 16px;
        right: 16px;
        background: transparent;
        border: none;
        cursor: pointer;
        color: #94a3b8;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        z-index: 2;
        padding: 4px;
    }
    .io-favori:hover { 
        transform: scale(1.15);
        color: var(--primary);
    }
    .io-favori.aktif { 
        color: var(--primary);
    }

    .btn-tumunu-gor {
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 24px auto 0;
        padding: 12px 32px;
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 14px;
        font-weight: 700;
        font-size: 14px;
        color: var(--text-main);
        width: 15%;
        transition: all 0.15s;
        cursor: pointer;
    }
    .btn-tumunu-gor:hover { border-color: var(--primary); color: var(--primary); }

    /* CTA section */
    .cta-section {
        background: linear-gradient(135deg, #fff4ec 0%, #fff 100%);
        border-top: 1px solid var(--border);
        border-bottom: 1px solid var(--border);
        padding: 64px 24px;
        margin-top: 60px;
    }
    .cta-inner {
        max-width: 1280px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        gap: 60px;
        flex-wrap: wrap;
    }
    .cta-text { flex: 1; min-width: 260px; }
    .cta-text h3 { font-size: 28px; font-weight: 800; color: var(--text-main); margin-bottom: 12px; letter-spacing: -0.5px; }
    .cta-text p { font-size: 16px; color: var(--text-muted); margin-bottom: 24px; }

    @media (max-width: 1024px) {
        .ilan-grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 768px) {
        .hero-text h1 { font-size: 30px; }
        .arama-kutusu { flex-direction: column; border-radius: 16px; overflow: visible; }
        .arama-ayirici { display: none; }
        .arama-btn { width: 100%; border-radius: 0 0 16px 16px; }
        .hero-stats { gap: 20px; }
        .ilan-grid { grid-template-columns: 1fr; }
        .io-kart { flex-wrap: wrap; }
    }
    /* ── MODAL MODERN ── */
    #ilanModal .modal-icerik {
        max-width: 900px;
        width: 95%;
        padding: 0;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        max-height: 90vh;
    }
    .modal-detay-govde {
        display: flex;
        overflow: hidden;
        height: 100%;
    }
    .modal-detay-sol {
        flex: 1.8;
        padding: 32px;
        overflow-y: auto;
        border-right: 1px solid var(--border);
    }
    .modal-detay-sag {
        flex: 1;
        padding: 24px;
        background: #f8fafc;
        overflow-y: auto;
    }
    
    .m-header { margin-bottom: 24px; }
    .m-baslik { font-size: 24px; font-weight: 800; color: #1e293b; margin-bottom: 8px; }
    .m-firma { font-size: 16px; color: #3b82f6; font-weight: 700; display: flex; align-items: center; gap: 6px; }
    
    .m-bilgi-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
        margin: 24px 0;
    }
    .m-bilgi-kutu {
        background: #f1f5f9;
        padding: 12px 16px;
        border-radius: 12px;
    }
    .m-bilgi-label { font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: 800; display: block; margin-bottom: 4px; }
    .m-bilgi-val { font-size: 14px; font-weight: 700; color: #1e293b; }

    .m-aciklama { font-size: 15px; line-height: 1.7; color: #475569; }
    
    .benzer-ilan-kart {
        background: #fff;
        padding: 12px;
        border-radius: 12px;
        border: 1px solid var(--border);
        margin-bottom: 12px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .benzer-ilan-kart:hover { border-color: var(--primary); transform: translateX(4px); }
    .benzer-baslik { font-size: 14px; font-weight: 700; color: #1e293b; margin-bottom: 4px; }
    .benzer-firma { font-size: 12px; color: #64748b; display: flex; align-items: center; gap: 4px; }
    
    @media (max-width: 768px) {
        .modal-detay-govde { flex-direction: column; }
        .modal-detay-sag { border-top: 1px solid var(--border); }
    }
</style>

<div class="hero-bolumu">
    <div class="hero-container">
        <div class="hero-text">
            <h1>Hayalinizdeki işi<br><span>hemen</span> keşfet</h1>
            <p>Türkiye'nin en güncel iş platformunda binlerce ilan arasından size en uygununu bulun.</p>

            <form action="is_ilanlari.php" method="GET" class="arama-kutusu">
                <svg width="18" height="18" style="margin-left:14px; color:#9ca3af; flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" name="ara" class="arama-input" placeholder="Pozisyon, şirket veya beceri...">
                <div class="arama-ayirici"></div>
                <div class="custom-dropdown" id="sehirDropdown" style="width:180px; border:none;">
                    <div class="dropdown-trigger" style="border:none; background:transparent; padding:0 14px; height:100%;">
                        <span>Şehir Seçin</span>
                        <svg width="14" height="14"><use xlink:href="#icon-chevron"></use></svg>
                    </div>
                    <div class="dropdown-menu">
                        <div class="dropdown-option" data-value="">Tüm Şehirler</div>
                        <?php foreach($iller as $il): ?>
                            <div class="dropdown-option" data-value="<?php echo htmlspecialchars($il['ilisim']); ?>"><?php echo htmlspecialchars($il['ilisim']); ?></div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="sehir" id="sehir_input" value="">
                </div>
                <button type="submit" class="arama-btn">İş Bul</button>
            </form>

            <div class="kategori-chips">
                <a href="is_ilanlari.php" class="chip">Tüm İlanlar</a>
                <a href="is_ilanlari.php?calismasekli=1" class="chip">Tam Zamanlı</a>
                <a href="is_ilanlari.php?calismasekli=2" class="chip">Yarı Zamanlı</a>
                <a href="is_ilanlari.php?calismasekli=3" class="chip">Uzaktan</a>
                <a href="is_ilanlari.php?calismasekli=4" class="chip">Staj</a>
            </div>
        </div>
        <div style="flex:0.8; display:flex; justify-content:center;">
            <img src='img/home/hero.png' alt='Hero' style='max-width:480px; width:100%; border-radius:20px; box-shadow: 0 20px 48px rgba(0,0,0,0.1);'>
        </div>
    </div>
</div>

<div class="ilanlar-section">
    <div class="bolum-baslik-row">
        <h2 class="bolum-baslik">Güncel İş İlanları</h2>
    </div>
    <div class="ilan-grid">
        <?php foreach($ilanlar as $ilan):
            $kayitli_mi = in_array($ilan['ilanID'], $kullanici_kayitli_ilanlar);
            $basvuruldu_mu = in_array($ilan['ilanID'], $kullanici_basvurulan_ilanlar);
            $zaman = timeAgo($ilan['yayintarihi']);
        ?>
        <div class="io-kart" onclick="ilanDetayGoster(<?php echo $ilan['ilanID']; ?>)">
            <div class="io-logo">
                <svg width="40" height="40"><use xlink:href="#icon-company"></use></svg>
            </div>
            <div class="io-icerik">
                <div class="io-baslik"><?php echo htmlspecialchars($ilan['baslik']); ?></div>
                <div class="io-firma">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                    <?php echo htmlspecialchars($ilan['firmaadi']); ?>
                </div>
                <div class="io-konum">
                    <?php echo htmlspecialchars(explode(',', $ilan['acikadres'])[0] ?? 'Türkiye'); ?>
                </div>
                <div class="io-alt">
                    <span class="io-tur"><?php echo htmlspecialchars($ilan['calismatur'] ?? 'Tam Zamanlı'); ?></span>
                    <span class="<?php echo ($zaman=='Yeni')?'io-yeni':'io-zaman'; ?>"><?php echo $zaman; ?></span>
                </div>
            </div>
            <?php if(isset($_SESSION['kullaniciID']) && $_SESSION['krolID']==1): ?>
            <button type="button" class="io-favori <?php echo $kayitli_mi?'aktif':''; ?>" data-id="<?php echo $ilan['ilanID']; ?>" onclick="event.stopPropagation(); ilanKaydetToggle(this)">
                <svg width="20" height="20"><use xlink:href="#icon-save<?php echo $kayitli_mi?'-active':''; ?>"></use></svg>
            </button>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <a href="is_ilanlari.php" class="btn-tumunu-gor">Tüm İlanları Gör</a>
</div>

<?php if(!isset($_SESSION['kullaniciID'])): ?>
<div class="cta-section">
    <div class="cta-inner">
        <div class="cta-text">
            <h3>Kariyerinde yeni bir adım at!</h3>
            <p>Hemen ücretsiz üye ol, iş ilanlarına kolayca başvur ve kariyer fırsatlarını kaçırma.</p>
            <a href="giris.php?islem=kayit_sec" class="btn-turuncu-nav" style="padding:12px 28px; font-size:15px;">Ücretsiz Kayıt Ol →</a>
        </div>
        <div style="flex:1; display:flex; justify-content:center;">
            <img src="img/home/cta.png" style="max-width:420px; width:100%; border-radius:20px;">
        </div>
    </div>
</div>
<?php endif; ?>

<!-- İLAN DETAY MODAL -->
<div id="ilanModal" class="modal-arkaplan">
    <div class="modal-icerik">
        <button class="kapat-btn" onclick="modalKapat('ilanModal')">×</button>
        
        <div id="modal_content_loading" style="padding:100px; text-align:center;">
            <div style="width:40px; height:40px; border:3px solid #eee; border-top-color:var(--primary); border-radius:50%; animation: spin 1s linear infinite; margin:0 auto;"></div>
            <p style="margin-top:16px; color:#64748b;">Yükleniyor...</p>
        </div>

        <div id="modal_content_main" class="modal-detay-govde" style="display:none;">
            <div class="modal-detay-sol">
                <div class="m-header" style="display:flex; justify-content:space-between; align-items:flex-start;">
                    <div>
                        <h1 id="m_baslik" class="m-baslik"></h1>
                        <div id="m_firmaadi" class="m-firma" style="cursor:pointer; color:#3b82f6;" title="Firma profilini görüntüle"></div>
                    </div>
                    <div style="display:flex; gap:10px;">
                        <button id="btn_paylas" class="btn-icon-gray" title="Paylaş" style="padding:10px; border-radius:12px; border:1px solid #e2e8f0; background:#fff; cursor:pointer;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                        </button>
                        <?php if(isset($_SESSION['kullaniciID']) && $_SESSION['krolID'] == 1): ?>
                        <button id="btn_modal_kaydet" class="btn-icon-gray modal-kaydet-btn" style="padding:0 16px; border-radius:12px; border:1px solid #e2e8f0; background:#fff; cursor:pointer; font-weight:600; display:flex; align-items:center; gap:6px;">
                            <svg width="18" height="18"><use xlink:href="#icon-save"></use></svg> Kaydet
                        </button>
                        <button id="btn_basvur" class="btn-turuncu-nav" style="padding:0 24px; height:44px; font-size:15px; font-weight:700;"></button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="m-bilgi-grid">
                    <div class="m-bilgi-kutu">
                        <span class="m-bilgi-label">Çalışma Şekli</span>
                        <span id="m_calisma" class="m-bilgi-val"></span>
                    </div>
                    <div class="m-bilgi-kutu">
                        <span class="m-bilgi-label">Maaş Bilgisi</span>
                        <span id="m_maas" class="m-bilgi-val"></span>
                    </div>
                    <div class="m-bilgi-kutu">
                        <span class="m-bilgi-label">Sektör</span>
                        <span id="m_sektor" class="m-bilgi-val"></span>
                    </div>
                    <div class="m-bilgi-kutu">
                        <span class="m-bilgi-label">Başvuru Sayısı</span>
                        <span id="m_basvuru_sayisi" class="m-bilgi-val"></span>
                    </div>
                    <div class="m-bilgi-kutu">
                        <span class="m-bilgi-label">Konum <a href="#" id="m_harita_link" target="_blank" style="text-transform:none; margin-left:8px; color:#3b82f6; text-decoration:underline;">Haritada Gör</a></span>
                        <span id="m_konum" class="m-bilgi-val"></span>
                    </div>
                    <div class="m-bilgi-kutu">
                        <span class="m-bilgi-label">Çalışma Saatleri</span>
                        <span id="m_saatler" class="m-bilgi-val"></span>
                    </div>
                    <div class="m-bilgi-kutu">
                        <span class="m-bilgi-label">Çalışma Günleri</span>
                        <span id="m_gunler" class="m-bilgi-val"></span>
                    </div>
                    <div class="m-bilgi-kutu">
                        <span class="m-bilgi-label">Yan Haklar</span>
                        <span id="m_yanhaklar" class="m-bilgi-val"></span>
                    </div>
                </div>

                <div style="margin-top:24px; display:flex; align-items:center; justify-content:space-between;">
                    <div style="font-size:12px; color:#94a3b8; display:flex; align-items:center; gap:4px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        Yayınlanma Tarihi: <span id="m_yayin_tarihi"></span>
                    </div>
                    <button id="btn_sikayet" style="background:none; border:none; color:#ef4444; font-size:12px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:4px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>
                        İlanı Şikayet Et
                    </button>
                </div>

                <div class="detay-section-modern">
                    <h3 style="font-size:18px; margin-bottom:12px;">İş Tanımı</h3>
                    <div id="m_aciklama" class="m-aciklama"></div>
                </div>
            </div>

            <div class="modal-detay-sag">
                <h3 style="font-size:16px; font-weight:800; color:#1e293b; margin-bottom:16px;">Benzer İlanlar</h3>
                <div id="m_benzer_ilanlar"></div>
                
                <div style="margin-top:32px;">
                    <h3 id="m_firma_baslik" style="font-size:14px; font-weight:800; color:#1e293b; margin-bottom:12px;"></h3>
                    <p id="m_firma_hakkinda" style="font-size:13px; line-height:1.6; color:#64748b;"></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const JS_LOGIN = <?php echo isset($_SESSION['kullaniciID']) ? 'true' : 'false'; ?>;
const JS_ROL = <?php echo isset($_SESSION['krolID']) ? $_SESSION['krolID'] : 'null'; ?>;

// Note: modalAc, modalKapat, profilAc are now global in footer.php

function sekmeAc(id, btn) {
    document.querySelectorAll('.p-icerik').forEach(p => p.classList.remove('aktif'));
    document.querySelectorAll('.p-sekme').forEach(p => p.classList.remove('aktif'));
    const sekme = document.getElementById('sekme-' + id);
    if(sekme) sekme.classList.add('aktif');
    if(btn) btn.classList.add('aktif');
}

document.addEventListener("DOMContentLoaded", function() {
    initCustomDropdown('sehirDropdown', 'sehir_input');

    // Dışarıya tıklanınca tüm dropdown'ları kapat
    document.addEventListener('click', function(e) {
        document.querySelectorAll('.custom-dropdown.active').forEach(d => {
            if(!d.contains(e.target)) d.classList.remove('active');
        });
    });
});

function initCustomDropdown(id, inputId, onSelect) {
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
            if(onSelect) onSelect(opt.dataset.value, opt.innerText);
        };
    });
}

async function ilanDetayGoster(id) {
    modalAc('ilanModal');
    const main = document.getElementById('modal_content_main');
    const loading = document.getElementById('modal_content_loading');
    
    main.style.display = 'none';
    loading.style.display = 'block';
    
    try {
        const res = await fetch('ilan_getir.php?id=' + id);
        const text = await res.text();
        let ilan;
        try {
            ilan = JSON.parse(text);
        } catch(e) {
            console.error('Sunucu JSON dışı bir yanıt döndürdü:', text);
            throw new Error('Sunucudan geçersiz yanıt geldi. Konsolu kontrol edin.');
        }
        
        if(ilan.hata || ilan.mesaj || ilan.durum === 'hata') {
            throw new Error(ilan.hata || ilan.mesaj || 'Sunucu hatası oluştu.');
        }
        
        document.getElementById('m_baslik').innerText = ilan.baslik;
        document.getElementById('m_firmaadi').innerText = ilan.firmaadi;
        document.getElementById('m_firmaadi').onclick = () => firmaProfiliGoster(id);
        
        document.getElementById('m_aciklama').innerHTML = ilan.aciklama_formatli;
        document.getElementById('m_calisma').innerText = ilan.calismatur || 'Belirtilmemiş';
        document.getElementById('m_maas').innerText = ilan.maas_formatli + ' TL';
        document.getElementById('m_sektor').innerText = ilan.sektorad || 'Hizmet';
        document.getElementById('m_basvuru_sayisi').innerText = (ilan.basvuru_sayisi || 0) + ' Aday';
        document.getElementById('m_konum').innerText = ilan.acikadres || 'Belirtilmemiş';
        document.getElementById('m_harita_link').href = 'https://www.google.com/maps/search/' + encodeURIComponent(ilan.acikadres || 'Türkiye');
        document.getElementById('m_saatler').innerText = ilan.saatler || 'Belirtilmemiş';
        document.getElementById('m_gunler').innerText = ilan.gunler || 'Belirtilmemiş';
        document.getElementById('m_yanhaklar').innerText = ilan.yanhak_label || 'Belirtilmemiş';
        document.getElementById('m_yayin_tarihi').innerText = ilan.tarih_formatli + ' (' + ilan.yayin_zamani + ')';
        
        document.getElementById('m_firma_baslik').innerText = ilan.firmaadi;
        document.getElementById('m_firma_hakkinda').innerText = ilan.hakkimda || 'Bu firma hakkında detaylı bilgi bulunmamaktadır.';
        
        const btnSikayet = document.getElementById('btn_sikayet');
        if(btnSikayet) {
            btnSikayet.onclick = () => {
                if(!JS_LOGIN) { window.location.href='giris.php'; return; }
                sikayetModalAc(id);
            };
        }

        const btnB = document.getElementById('btn_basvur');
        if(btnB) {
            btnB.onclick = () => ilanBasvur(id);
            if(ilan.basvuruldu_mu) { btnB.innerText = 'Başvuruldu'; btnB.disabled = true; }
            else { btnB.innerText = 'Başvur'; btnB.disabled = false; }
        }
        
        const btnM = document.getElementById('btn_modal_kaydet');
        if(btnM) {
            btnM.dataset.id = id;
            if(ilan.kayitli_mi) {
                btnM.classList.add('aktif');
                btnM.innerHTML = `<svg width="18" height="18"><use xlink:href="#icon-save-active"></use></svg> Kaydedildi`;
            } else {
                btnM.classList.remove('aktif');
                btnM.innerHTML = `<svg width="18" height="18"><use xlink:href="#icon-save"></use></svg> Kaydet`;
            }
            btnM.onclick = () => ilanModalKaydet(btnM, id);
        }
        
        document.getElementById('btn_paylas').onclick = () => ilanPaylas(id, ilan.baslik);

        // Benzer İlanları Render Et
        const benzerBox = document.getElementById('m_benzer_ilanlar');
        if(ilan.benzer_ilanlar && ilan.benzer_ilanlar.length > 0) {
            benzerBox.innerHTML = ilan.benzer_ilanlar.map(b => `
                <div class="benzer-ilan-kart" onclick="ilanDetayGoster(${b.ilanID})">
                    <div class="benzer-baslik">${b.baslik}</div>
                    <div class="benzer-firma">
                        <svg width="12" height="12"><use xlink:href="#icon-company"></use></svg>
                        ${b.firmaadi}
                    </div>
                    <div style="font-size:11px; color:#94a3b8; margin-top:4px;">${b.konum} • ${b.zaman}</div>
                </div>
            `).join('');
        } else {
            benzerBox.innerHTML = '<p style="font-size:13px; color:#94a3b8;">Benzer ilan bulunamadı.</p>';
        }
        
        loading.style.display = 'none';
        main.style.display = 'flex';
    } catch(e) {
        console.error('İlan Detay Hatası:', e);
        alert('İlan detayları yüklenirken bir hata oluştu. Detaylar konsolda.');
        modalKapat('ilanModal');
    }
}

async function ilanPaylas(id, baslik) {
    const shareData = {
        title: baslik,
        text: baslik + ' ilanına göz at!',
        url: window.location.href.substring(0, window.location.href.lastIndexOf("/")) + '/is_ilanlari.php?id=' + id
    };
    try {
        if (navigator.share) {
            await navigator.share(shareData);
        } else {
            await navigator.clipboard.writeText(shareData.url);
            alert('İlan bağlantısı kopyalandı!');
        }
    } catch (err) { console.log('Paylaşım hatası:', err); }
}

// Eski ilanSikayetEt fonksiyonu silindi, yerine sikayetModalAc kullanılıyor

async function ilanBasvur(id) {
    if(!JS_LOGIN) { window.location.href='giris.php'; return; }
    const res = await fetch('islem.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `islem=basvuru_yap_ajax&ilan_id=${id}`
    });
    const data = await res.json();
    if(data.durum === 'basarili') {
        document.getElementById('mesaj_metin').innerText = 'Başvuru başarılı!';
        modalAc('basariModal');
    } else { alert(data.hata); }
}

async function ilanKaydetToggle(btn) {
    if(!JS_LOGIN) { window.location.href='giris.php'; return; }
    
    // Anında UI güncellemesi (Optimistic UI)
    const id = btn.dataset.id;
    const isSil = btn.classList.contains('aktif');
    const islem = isSil ? 'kayit_sil' : 'kaydet';
    const newValue = !isSil;
    
    // Detayları al
    let baslik = 'İlan', firmaadi = 'Firma';
    const kart = btn.closest('.io-kart');
    if(kart) {
        const hE = kart.querySelector('.io-baslik');
        if(hE) baslik = hE.innerText.trim();
        const fE = kart.querySelector('.io-firma');
        if(fE) firmaadi = fE.innerText.replace('check_circle', '').trim();
    }
    
    if(typeof syncSavedStateAcrossDOM === 'function') {
        syncSavedStateAcrossDOM(id, newValue, baslik, firmaadi, new Date().toLocaleDateString('tr-TR'));
    } else {
        btn.classList.toggle('aktif');
        btn.innerHTML = `<svg width="20" height="20"><use xlink:href="#icon-save${newValue ? '-active' : ''}"></use></svg>`;
    }

    try {
        const res = await fetch('islem.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `islem=${islem}&ilan_id=${id}`
        });
        const data = await res.json();
        if(data.durum !== 'basarili') {
            throw new Error(data.hata || 'Hata oluştu');
        }
    } catch(err) {
        if(typeof syncSavedStateAcrossDOM === 'function') {
            syncSavedStateAcrossDOM(id, !newValue, baslik, firmaadi, new Date().toLocaleDateString('tr-TR'));
        } else {
            btn.classList.toggle('aktif');
            btn.innerHTML = `<svg width="20" height="20"><use xlink:href="#icon-save${!newValue ? '-active' : ''}"></use></svg>`;
        }
        if(typeof showToast === 'function') showToast('Bir hata oluştu', 'error');
    }
}
async function ilanModalKaydet(btn, id) {
    if(!JS_LOGIN) { window.location.href='giris.php'; return; }
    
    const isSil = btn.classList.contains('aktif');
    const islem = isSil ? 'kayit_sil' : 'kaydet';
    const newValue = !isSil;
    
    let baslik = document.getElementById('m_baslik').innerText.trim();
    let firmaadi = document.getElementById('m_firmaadi').innerText.trim();
    
    if(typeof syncSavedStateAcrossDOM === 'function') {
        syncSavedStateAcrossDOM(id, newValue, baslik, firmaadi, new Date().toLocaleDateString('tr-TR'));
    }
    
    if(newValue) {
        btn.classList.add('aktif');
        btn.innerHTML = `<svg width="18" height="18"><use xlink:href="#icon-save-active"></use></svg> Kaydedildi`;
    } else {
        btn.classList.remove('aktif');
        btn.innerHTML = `<svg width="18" height="18"><use xlink:href="#icon-save"></use></svg> Kaydet`;
    }

    try {
        const res = await fetch('islem.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `islem=${islem}&ilan_id=${id}`
        });
        const data = await res.json();
        if(data.durum !== 'basarili') throw new Error(data.hata);
        else showToast(newValue ? 'İlan kaydedildi' : 'İlan kaydedilenlerden çıkarıldı');
    } catch(err) {
        if(typeof syncSavedStateAcrossDOM === 'function') {
            syncSavedStateAcrossDOM(id, !newValue, baslik, firmaadi, new Date().toLocaleDateString('tr-TR'));
        }
        if(!newValue) {
            btn.classList.add('aktif');
            btn.innerHTML = `<svg width="18" height="18"><use xlink:href="#icon-save-active"></use></svg> Kaydedildi`;
        } else {
            btn.classList.remove('aktif');
            btn.innerHTML = `<svg width="18" height="18"><use xlink:href="#icon-save"></use></svg> Kaydet`;
        }
        showToast('Bir hata oluştu', 'error');
    }
}
</script>

<?php include 'components/footer.php'; ?>