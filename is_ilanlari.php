<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require 'baglan.php';

// Filtre Parametreleri
$ara = $_GET['ara'] ?? '';
$sehir = $_GET['sehir'] ?? '';
$tarih = $_GET['tarih'] ?? 'tum_zamanlar';
$calismasekli = $_GET['calismasekli'] ?? '';
$kategori = $_GET['kategori'] ?? '';
$siralama = $_GET['siralama'] ?? 'en_yeni';

// Temel Sorgu
$sql = "SELECT i.*, v.firmaadi, v.ikullaniciID, c.calismatur, s.sektorad 
        FROM ilan i 
        JOIN isveren v ON i.iisverenID = v.isverenID 
        LEFT JOIN calismaturu c ON i.icalismaturID = c.calismaID
        LEFT JOIN sektor s ON i.isektorID = s.sektorID
        WHERE i.idurumID = 1";

$params = [];

if($ara != '') { $sql .= " AND (i.baslik LIKE ? OR v.firmaadi LIKE ? OR i.aciklama LIKE ? OR i.acikadres LIKE ?)"; $p = "%$ara%"; $params[] = $p; $params[] = $p; $params[] = $p; $params[] = $p; }
if($sehir != '') { $sql .= " AND i.acikadres LIKE ?"; $params[] = "%$sehir%"; }
if($calismasekli != '') { $sql .= " AND i.icalismaturID = ?"; $params[] = $calismasekli; }
if($kategori != '') { $sql .= " AND i.isektorID = ?"; $params[] = $kategori; }

if($tarih == 'son_24_saat') { $sql .= " AND i.yayintarihi >= DATE_SUB(NOW(), INTERVAL 1 DAY)"; }
else if($tarih == 'son_3_gun') { $sql .= " AND i.yayintarihi >= DATE_SUB(NOW(), INTERVAL 3 DAY)"; }
else if($tarih == 'son_7_gun') { $sql .= " AND i.yayintarihi >= DATE_SUB(NOW(), INTERVAL 7 DAY)"; }

if($siralama == 'en_eski') { $sql .= " ORDER BY i.ilanID ASC"; }
else { $sql .= " ORDER BY i.ilanID DESC"; }

$sorgu = $db->prepare($sql);
$sorgu->execute($params);
$ilanlar = $sorgu->fetchAll(PDO::FETCH_ASSOC);
$toplam_ilan = count($ilanlar);

$iller = $db->query("SELECT * FROM il ORDER BY ilisim ASC")->fetchAll(PDO::FETCH_ASSOC);
$sektorler = $db->query("SELECT * FROM sektor ORDER BY sektorad ASC")->fetchAll(PDO::FETCH_ASSOC);
$calismaturleri = $db->query("SELECT * FROM calismaturu ORDER BY calismatur ASC")->fetchAll(PDO::FETCH_ASSOC);

$kullanici_kayitli_ilanlar = [];
$kullanici_basvurulan_ilanlar = [];
if(isset($_SESSION['kullaniciID']) && $_SESSION['krolID'] == 1) {
    $kID = $_SESSION['kullaniciID'];
    $k_sorgu = $db->prepare("SELECT ilanID FROM kaydedilenler WHERE kullaniciID = ?");
    $k_sorgu->execute([$kID]);
    $kullanici_kayitli_ilanlar = $k_sorgu->fetchAll(PDO::FETCH_COLUMN);
    
    $isarayanSorgu = $db->prepare("SELECT isarayanID FROM isarayan WHERE akullaniciID = ?");
    $isarayanSorgu->execute([$kID]);
    $isarayanID = $isarayanSorgu->fetchColumn();
    if($isarayanID) {
        $b_sorgu = $db->prepare("SELECT bilanID FROM basvuru WHERE bisarayanID = ?");
        $b_sorgu->execute([$isarayanID]);
        $kullanici_basvurulan_ilanlar = $b_sorgu->fetchAll(PDO::FETCH_COLUMN);
    }
}

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
$page_title = 'İş İlanları - Kariyerlen';
include 'components/header.php'; 
?>

<style>
    /* ── FILTER BAR ── */
    .filter-bar {
        background: #fff;
        border-bottom: 1px solid var(--border);
        padding: 0 24px;
        position: sticky;
        top: 64px;
        z-index: 900;
        box-shadow: 0 2px 10px rgba(0,0,0,0.02);
    }
    .filter-container {
        max-width: 1280px;
        margin: 0 auto;
        display: flex;
        gap: 12px;
        align-items: center;
        padding: 12px 0;
        flex-wrap: wrap; /* Allow wrapping on small screens */
    }
    /* Removed overflow-x to prevent clipping dropdowns */

    .filter-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 0 16px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 500;
        color: #475569;
        cursor: pointer;
        transition: all 0.15s;
        white-space: nowrap;
        height: 36px;
    }
    .filter-pill:hover { background: #f8fafc; border-color: #cbd5e1; }
    .filter-pill.aktif { border-color: var(--primary); color: var(--primary); background: #fff4ec; }
    .filter-pill svg { width: 10px; height: 10px; color: #94a3b8; }
    .filter-pill { position: relative; } /* For absolute dropdowns */

    .custom-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        z-index: 10001; /* Extremely high to stay on top */
        display: none;
        background: #fff;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        margin-top: 8px;
        min-width: 240px;
    }
    /* Align right for filters on the right side of the screen */
    #cityPill .custom-dropdown { left: auto; right: 0; }
    .custom-dropdown.active { display: block; }
    .dropdown-menu { max-height: 300px; overflow-y: auto; }
    .dropdown-option {
        padding: 10px 16px;
        font-size: 13px;
        color: #475569;
        cursor: pointer;
        transition: 0.1s;
    }
    .dropdown-option:hover { background: #f8fafc; color: var(--primary); }

    .search-pill {
        background: #f1f5f9;
        border: 1px solid transparent;
        border-radius: 50px;
        padding: 0 16px;
        display: flex;
        align-items: center;
        gap: 8px;
        height: 36px;
        flex: 1;
        min-width: 200px;
    }
    .search-pill:focus-within { border-color: var(--primary); background: #fff; }
    .search-pill input {
        border: none;
        background: transparent;
        outline: none;
        font-size: 13px;
        font-weight: 500;
        width: 100%;
        color: var(--text-main);
    }
    .btn-find-ilan {
        background: var(--primary);
        color: #fff;
        border: none;
        border-radius: 50px;
        padding: 0 24px;
        font-size: 14px;
        font-weight: 700;
        height: 36px;
        cursor: pointer;
        transition: 0.2s;
    }
    .btn-find-ilan:hover { background: var(--primary-hover); }

    /* ── MAIN SPLIT LAYOUT ── */
    .main-container {
        display: flex;
        align-items: flex-start;
        max-width: 1280px;
        margin: 0 auto 60px auto;
        min-height: calc(100vh - 125px);
        background: #fff;
    }

    /* Left panel */
    .ilan-liste-paneli {
        width: 420px;
        flex-shrink: 0;
        border-right: 1px solid var(--border);
        background: #fff;
        position: sticky;
        top: 125px;
        height: calc(100vh - 125px);
        overflow-y: auto;
    }
    .panel-header {
        padding: 12px 20px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        background: #fff;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* Mini job card */
    .mini-ilan-kart {
        padding: 16px 20px;
        border-bottom: 1px solid #f1f5f9;
        cursor: pointer;
        transition: 0.1s;
        background: #fff;
        display: flex;
        gap: 16px;
    }
    .mini-ilan-kart:hover { background: #f8fafc; }
    .mini-ilan-kart.aktif {
        background: #fff;
        box-shadow: inset 4px 0 0 var(--primary);
        border-bottom-color: #f1f5f9;
    }
    .mini-logo {
        width: 64px;
        height: 64px;
        background: #f4f9f1;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        color: #8dbd7b;
    }
    .mini-info { flex: 1; min-width: 0; position: relative; }
    .mini-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px; }
    .mini-baslik { font-size: 15px; font-weight: 700; color: #1e293b; line-height: 1.3; padding-right: 20px; }
    .mini-save-btn { position: absolute; top: 0; right: 0; color: #cbd5e1; }
    .mini-firma { font-size: 13px; color: #64748b; font-weight: 500; margin-bottom: 4px; }
    .mini-konum { font-size: 13px; color: #94a3b8; margin-bottom: 12px; }
    .mini-footer { display: flex; justify-content: space-between; align-items: center; }
    .mini-zaman { font-size: 12px; color: #94a3b8; font-weight: 500; }
    .mini-zaman.yeni { color: var(--primary); font-weight: 700; }

    /* Right detail panel */
    .ilan-detay-paneli {
        flex: 1;
        background: #fff;
        border-left: 1px solid #f1f5f9;
        min-height: calc(100vh - 125px);
    }

    /* Detail Header */
    .detay-header-fixed {
        background: #fff;
        padding: 24px 32px;
        border-bottom: 1px solid #f1f5f9;
    }
    .detay-top-row { display: flex; justify-content: space-between; align-items: flex-start; }
    .detay-titles { flex: 1; }
    .detay-h1 { font-size: 20px; font-weight: 700; color: #1e293b; margin-bottom: 4px; }
    .detay-firma-link { font-size: 14px; font-weight: 600; color: #3b82f6; display: flex; align-items: center; gap: 4px; }
    
    .detay-actions-group { display: flex; gap: 8px; }
    .btn-apply-compact {
        background: var(--primary);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 0 32px;
        height: 40px;
        font-weight: 700;
        font-size: 15px;
        cursor: pointer;
        transition: 0.2s;
    }
    .btn-apply-compact:hover { background: var(--primary-hover); transform: translateY(-1px); }
    .btn-icon-gray {
        height: 38px;
        padding: 0 12px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #fff;
        display: flex;
        align-items: center;
        gap: 6px;
        color: #475569;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
    }

    .detay-meta-line { margin-top: 16px; color: #64748b; font-size: 13px; display: flex; gap: 12px; align-items: center; }
    .detay-meta-line span { display: flex; align-items: center; gap: 4px; }

    /* Content */
    .detay-content-inner { padding: 32px; }
    .detay-section-modern { margin-bottom: 32px; }
    .section-h2 { font-size: 16px; font-weight: 700; color: #1e293b; margin-bottom: 16px; }
    .desc-text { font-size: 14px; color: #475569; line-height: 1.7; }

    .grid-info-modern { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
    .info-box-modern { display: flex; flex-direction: column; gap: 6px; }
    .info-label { font-size: 15px; font-weight: 700; color: #1e293b; }
    .info-val { font-size: 14px; font-weight: 500; color: #64748b; line-height: 1.4; }

    .report-link { color: #ef4444; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; margin-top: 20px; }
    
    .badge-blue-check {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #eff6ff;
        color: #3b82f6;
        padding: 4px 10px;
        border-radius: 50px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    @media (max-width: 1024px) {
        .main-container { flex-direction: column; min-height: auto; align-items: stretch; }
        .ilan-liste-paneli { width: 100%; height: 350px; position: static; border-right: none; }
        .grid-info-modern { grid-template-columns: 1fr; }
    }

    /* ── SEARCH PAGE DARK MODE OVERRIDES ── */
    body.dark-mode {
        background-color: #111827 !important;
    }
    body.dark-mode .filter-bar {
        background: #1f2937 !important;
        border-bottom-color: #374151 !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.25) !important;
    }
    body.dark-mode .filter-pill {
        background: #374151 !important;
        border-color: #4b5563 !important;
        color: #f3f4f6 !important;
    }
    body.dark-mode .filter-pill:hover {
        background: #4b5563 !important;
        border-color: #6b7280 !important;
    }
    body.dark-mode .filter-pill.aktif {
        border-color: var(--primary) !important;
        color: var(--primary) !important;
        background: rgba(255, 126, 29, 0.1) !important;
    }
    body.dark-mode .custom-dropdown {
        background: #1f2937 !important;
        border-color: #374151 !important;
        box-shadow: 0 15px 35px rgba(0,0,0,0.4) !important;
    }
    body.dark-mode .dropdown-option {
        color: #d1d5db !important;
    }
    body.dark-mode .dropdown-option:hover {
        background: #374151 !important;
        color: var(--primary) !important;
    }
    body.dark-mode .search-pill {
        background: #374151 !important;
        border-color: transparent !important;
    }
    body.dark-mode .search-pill:focus-within {
        border-color: var(--primary) !important;
        background: #1f2937 !important;
    }
    body.dark-mode .search-pill input {
        color: #f9fafb !important;
    }
    body.dark-mode .search-pill svg {
        color: #94a3b8 !important;
    }
    body.dark-mode .main-container {
        background: #111827 !important;
    }
    body.dark-mode .ilan-liste-paneli {
        background: #111827 !important;
        border-right-color: #1f2937 !important;
    }
    body.dark-mode .panel-header {
        background: #1f2937 !important;
        border-bottom-color: #374151 !important;
        color: #9ca3af !important;
    }
    body.dark-mode .mini-ilan-kart {
        background: #1f2937 !important;
        border-bottom-color: #111827 !important;
    }
    body.dark-mode .mini-ilan-kart:hover {
        background: #283548 !important;
    }
    body.dark-mode .mini-ilan-kart.aktif {
        background: #2d3d54 !important;
        box-shadow: inset 4px 0 0 var(--primary) !important;
        border-bottom-color: #111827 !important;
    }
    body.dark-mode .mini-logo {
        background: #374151 !important;
        border-color: #4b5563 !important;
        color: #ff7e1d !important;
    }
    body.dark-mode .mini-baslik {
        color: #f9fafb !important;
    }
    body.dark-mode .mini-firma {
        color: #9ca3af !important;
    }
    body.dark-mode .mini-konum {
        color: #64748b !important;
    }
    body.dark-mode .mini-zaman {
        color: #64748b !important;
    }
    body.dark-mode .mini-zaman.yeni {
        color: var(--primary) !important;
    }
    body.dark-mode .ilan-detay-paneli {
        background: #1f2937 !important;
        border-left-color: #374151 !important;
    }
    body.dark-mode .detay-header-fixed {
        background: #1f2937 !important;
        border-bottom-color: #374151 !important;
    }
    body.dark-mode .detay-h1 {
        color: #f9fafb !important;
    }
    body.dark-mode .detay-firma-link {
        color: #60a5fa !important;
    }
    body.dark-mode .btn-icon-gray {
        background: #374151 !important;
        border-color: #4b5563 !important;
        color: #f9fafb !important;
    }
    body.dark-mode .btn-icon-gray:hover {
        background: #4b5563 !important;
        color: #ffffff !important;
    }
    body.dark-mode .btn-icon-gray.aktif {
        background: rgba(255, 126, 29, 0.1) !important;
        border-color: var(--primary) !important;
        color: var(--primary) !important;
    }
    body.dark-mode .detay-meta-line {
        color: #94a3b8 !important;
    }
    body.dark-mode .detay-meta-line span[style*="color:#1e293b"],
    body.dark-mode .detay-meta-line span[style*="color: #1e293b"] {
        color: #f9fafb !important;
    }
    body.dark-mode .section-h2 {
        color: #f9fafb !important;
        border-bottom: 2px solid #374151 !important;
        padding-bottom: 8px !important;
    }
    body.dark-mode .desc-text {
        color: #d1d5db !important;
    }
    body.dark-mode .info-label {
        color: #f9fafb !important;
    }
    body.dark-mode .info-val {
        color: #9ca3af !important;
    }
    body.dark-mode .detay-section-modern[style*="background:#f8fafc"],
    body.dark-mode .detay-section-modern[style*="background: #f8fafc"] {
        background: #111827 !important;
        border: 1px solid #374151 !important;
    }
</style>





<div class="filter-bar">
    <form class="filter-container" id="filterForm" method="GET">
        <div class="filter-pill <?php echo $tarih!='tum_zamanlar' ? 'aktif' : ''; ?>" id="tarihPill" onclick="toggleDropdown(event, 'filterTarihDropdown')">
            <span><?php 
                if($tarih=='son_24_saat') echo 'Son 24 Saat';
                else if($tarih=='son_3_gun') echo 'Son 3 Gün';
                else if($tarih=='son_7_gun') echo 'Son 7 Gün';
                else echo 'Yayınlanma Tarihi';
            ?></span>
            <svg width="10" height="10"><use xlink:href="#icon-chevron"></use></svg>
            <div class="custom-dropdown" id="filterTarihDropdown">
                <div class="dropdown-menu">
                    <div class="dropdown-option" data-value="tum_zamanlar">Tüm Zamanlar</div>
                    <div class="dropdown-option" data-value="son_24_saat">Son 24 Saat</div>
                    <div class="dropdown-option" data-value="son_3_gun">Son 3 Gün</div>
                    <div class="dropdown-option" data-value="son_7_gun">Son 7 Gün</div>
                </div>
            </div>
            <input type="hidden" name="tarih" id="filter_tarih_input" value="<?php echo htmlspecialchars($tarih); ?>">
        </div>
        
        <div class="filter-pill <?php echo $calismasekli ? 'aktif' : ''; ?>" id="calismaPill" onclick="toggleDropdown(event, 'filterCalismaDropdown')">
            <span><?php 
                $ct_isim = 'Çalışma Türü';
                foreach($calismaturleri as $ct) { if($ct['calismaID'] == $calismasekli) $ct_isim = $ct['calismatur']; }
                echo $ct_isim;
            ?></span>
            <svg width="10" height="10"><use xlink:href="#icon-chevron"></use></svg>
            <div class="custom-dropdown" id="filterCalismaDropdown">
                <div class="dropdown-menu">
                    <div class="dropdown-option" data-value="">Tüm Türler</div>
                    <?php foreach($calismaturleri as $ct): ?>
                        <div class="dropdown-option" data-value="<?php echo $ct['calismaID']; ?>"><?php echo $ct['calismatur']; ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <input type="hidden" name="calismasekli" id="filter_calisma_input" value="<?php echo htmlspecialchars($calismasekli); ?>">
        </div>

        <div class="filter-pill <?php echo $sehir ? 'aktif' : ''; ?>" id="cityPill" onclick="toggleDropdown(event, 'filterSehirDropdown')">
            <span><?php echo $sehir ? $sehir : 'Şehir'; ?></span>
            <svg width="10" height="10"><use xlink:href="#icon-chevron"></use></svg>
            <div class="custom-dropdown" id="filterSehirDropdown">
                <div class="dropdown-menu">
                    <div class="dropdown-option" data-value="">Tüm Şehirler</div>
                    <?php foreach($iller as $il): ?>
                        <div class="dropdown-option" data-value="<?php echo $il['ilisim']; ?>"><?php echo $il['ilisim']; ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <input type="hidden" name="sehir" id="filter_sehir_input" value="<?php echo htmlspecialchars($sehir); ?>">
        </div>

        <div class="search-pill">
            <svg width="14" height="14"><use xlink:href="#icon-search"></use></svg>
            <input type="text" name="ara" placeholder="Arama yap..." value="<?php echo htmlspecialchars($ara); ?>">
        </div>

        <input type="hidden" name="siralama" id="main_filter_siralama_input" value="<?php echo htmlspecialchars($siralama); ?>">
        <button type="submit" class="btn-find-ilan">Ara</button>
        <?php if($ara || $sehir || $calismasekli || $tarih!='tum_zamanlar'): ?>
            <a href="is_ilanlari.php" style="font-size:12px; color:#ef4444; font-weight:600; text-decoration:none;">Temizle</a>
        <?php endif; ?>
    </form>
</div>

<div class="main-container">
    <aside class="ilan-liste-paneli">
        <div class="panel-header">
            <span>İş ilanları · <?php echo $toplam_ilan; ?> ilan</span>
            <div class="filter-pill" style="border:none; height:auto; padding:0; background:transparent;" onclick="toggleDropdown(event, 'filterSiralamaDropdown')">
                <span style="display:flex; align-items:center; gap:4px; cursor:pointer; color:var(--primary); font-weight:700;">
                    <?php 
                        if($siralama == 'en_yeni') echo 'En Yeni';
                        else if($siralama == 'en_eski') echo 'En Eski';
                        else echo 'Akıllı Sıralama';
                    ?> 
                    <svg width="8" height="8"><use xlink:href="#icon-chevron"></use></svg>
                </span>
                <div class="custom-dropdown" id="filterSiralamaDropdown" style="right:0; left:auto; top:25px;">
                    <div class="dropdown-menu">
                        <div class="dropdown-option" data-value="akilli">Akıllı Sıralama</div>
                        <div class="dropdown-option" data-value="en_yeni">En Yeni</div>
                        <div class="dropdown-option" data-value="en_eski">En Eski</div>
                    </div>
                </div>
            </div>
        </div>
        <div id="ilan-listesi-scroll" style="flex:1; overflow-y:auto;">
        <?php foreach($ilanlar as $index => $ilan):
            $z = timeAgo($ilan['yayintarihi']);
        ?>
        <div class="mini-ilan-kart" data-id="<?php echo $ilan['ilanID']; ?>" onclick="ilanDetayGosterSplit(<?php echo $ilan['ilanID']; ?>, this)">
            <div class="mini-logo">
                <svg width="32" height="32"><use xlink:href="#icon-company"></use></svg>
            </div>
            <div class="mini-info">
                <div class="mini-top">
                    <div class="mini-baslik"><?php echo htmlspecialchars($ilan['baslik']); ?></div>
                    <?php if(isset($_SESSION['krolID']) && $_SESSION['krolID'] == 1): ?>
                        <?php $is_saved = in_array($ilan['ilanID'], $kullanici_kayitli_ilanlar); ?>
                        <svg class="mini-save-btn <?php echo $is_saved ? 'aktif' : ''; ?>" width="18" height="18"><use xlink:href="#icon-save<?php echo $is_saved ? '-active' : ''; ?>"></use></svg>
                    <?php endif; ?>
                </div>
                <div class="mini-konum"><?php echo htmlspecialchars($ilan['acikadres']); ?></div>
                <div class="mini-footer">
                    <span class="mini-zaman <?php echo $z=='Yeni' ? 'yeni' : ''; ?>"><?php echo $z; ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </aside>

    <main class="ilan-detay-paneli" id="detayPaneli">
        <div class="detay-bos-state" style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; color:#94a3b8; gap:16px;">
            <svg width="64" height="64"><use xlink:href="#icon-briefcase"></use></svg>
            <p style="font-weight:600;">İncelemek için bir ilan seçin</p>
        </div>
    </main>
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

function initCustomDropdown(id, inputId) {
    const d = document.getElementById(id); if(!d) return;
    const input = document.getElementById(inputId);
    
    d.querySelectorAll('.dropdown-option').forEach(opt => {
        opt.onclick = (e) => {
            e.stopPropagation();
            input.value = opt.dataset.value;
            d.classList.remove('active');
            document.getElementById('filterForm').submit();
        };
    });
}

function toggleDropdown(e, id) {
    e.stopPropagation();
    document.querySelectorAll('.custom-dropdown').forEach(d => {
        if(d.id !== id) d.classList.remove('active');
    });
    const target = document.getElementById(id);
    if(target) target.classList.toggle('active');
}

document.addEventListener("click", () => {
    document.querySelectorAll('.custom-dropdown, .iv-dropdown').forEach(d => d.classList.remove('active'));
});

document.addEventListener("DOMContentLoaded", function() {
    initCustomDropdown('filterSehirDropdown', 'filter_sehir_input');
    initCustomDropdown('filterTarihDropdown', 'filter_tarih_input');
    initCustomDropdown('filterCalismaDropdown', 'filter_calisma_input');
    initCustomDropdown('filterSiralamaDropdown', 'main_filter_siralama_input');
    
    const urlParams = new URLSearchParams(window.location.search);
    const targetId = urlParams.get('id');
    
    const allCards = document.querySelectorAll('.mini-ilan-kart');
    if(allCards.length > 0) {
        if(targetId) {
            const targetEl = document.querySelector(`.mini-ilan-kart[data-id="${targetId}"]`);
            if(targetEl) {
                targetEl.click();
                targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                allCards[0].click();
            }
        } else {
            allCards[0].click();
        }
    }
});

async function ilanDetayGosterSplit(id, el) {
    document.querySelectorAll('.mini-ilan-kart').forEach(k => k.classList.remove('aktif'));
    if(el) el.classList.add('aktif');
    
    const panel = document.getElementById('detayPaneli');
    panel.innerHTML = `
        <div style="display:flex; align-items:center; justify-content:center; height:100px; margin-top:100px;">
            <div style="width:40px; height:40px; border:3px solid #eee; border-top-color:var(--primary); border-radius:50%; animation: spin 1s linear infinite;"></div>
        </div>
    `;
    
    try {
        const res = await fetch('ilan_getir.php?id=' + id);
        const ilan = await res.json();
        
        if(ilan.hata) {
            panel.innerHTML = `<div class="detay-bos-state">Hata: ${ilan.hata}</div>`;
            return;
        }

        const adresEncoded = encodeURIComponent(ilan.acikadres || 'Türkiye');
        const mapUrl = `https://maps.google.com/maps?q=${adresEncoded}&t=&z=14&ie=UTF8&iwloc=&output=embed`;
        
        panel.innerHTML = `
            <div class="detay-header-fixed">
                <div class="detay-top-row">
                    <div class="detay-titles">
                        <h1 class="detay-h1">${ilan.baslik}</h1>
                        <div class="detay-firma-link" onclick="firmaProfiliGoster(${ilan.ilanID})" style="cursor:pointer; color:#3b82f6;">
                            <svg width="16" height="16"><use xlink:href="#icon-company"></use></svg>
                            ${ilan.firmaadi}
                        </div>
                    </div>
                    <div class="detay-actions-group">
                        <button class="btn-icon-gray" onclick="ilanPaylas(${id}, '${ilan.baslik}')" title="Paylaş">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                        </button>
                        ${JS_ROL == 1 ? `
                        <button class="btn-icon-gray ${ilan.kayitli_mi ? 'aktif' : ''}" onclick="ilanKaydetToggleSplit(this, ${id})">
                            <svg width="18" height="18"><use xlink:href="#icon-save${ilan.kayitli_mi ? '-active' : ''}"></use></svg>
                            ${ilan.kayitli_mi ? 'Kaydedildi' : 'Kaydet'}
                        </button>
                        ` : ''}
                        ${JS_ROL == 1 ? `
                        <button class="btn-apply-compact" id="split_apply_btn" onclick="ilanBasvur(${id})" ${ilan.basvuruldu_mu ? 'disabled' : ''}>
                            ${ilan.basvuruldu_mu ? 'Başvuruldu' : 'Hemen Başvur'}
                        </button>
                        ` : ''}
                    </div>
                </div>
                <div class="detay-meta-line">
                    <span style="font-weight:600; color:#1e293b;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg> ${ilan.acikadres}</span>
                    <span style="color:#e2e8f0;">|</span>
                    <span style="color:#3b82f6; cursor:pointer; font-weight:700;" onclick="window.open('https://www.google.com/maps/search/${adresEncoded}', '_blank')">Haritada Gör</span>
                    <span style="flex:1;"></span>
                    <span style="color:#94a3b8; font-size:12px;">${ilan.tarih_formatli} tarihinde yayınlandı.</span>
                </div>
            </div>

            <div class="detay-content-inner">
                <div class="detay-section-modern">
                    <h2 class="section-h2">Genel Nitelikler ve İş Tanımı</h2>
                    <div class="desc-text">
                        ${ilan.aciklama_formatli}
                    </div>
                </div>

                <div class="detay-section-modern" id="firma_section">
                    <h2 class="section-h2">Firma Hakkında</h2>
                    <div class="desc-text">
                        ${ilan.hakkimda || 'Bu firma hakkında detaylı bilgi bulunmamaktadır.'}
                    </div>
                </div>

                <div class="detay-section-modern">
                    <h2 class="section-h2">İlan Bilgileri</h2>
                    <div class="grid-info-modern">
                        <div class="info-box-modern">
                            <span class="info-label">Sektör</span>
                            <span class="info-val">${ilan.sektorad || 'Belirtilmemiş'}</span>
                        </div>
                        <div class="info-box-modern">
                            <span class="info-label">Çalışma Türü</span>
                            <span class="info-val">${ilan.calismatur || 'Belirtilmemiş'}</span>
                        </div>
                        <div class="info-box-modern">
                            <span class="info-label">Çalışma Günleri</span>
                            <span class="info-val">${ilan.calismagunleri || 'Hafta içi'}</span>
                        </div>
                        <div class="info-box-modern">
                            <span class="info-label">Başvuru Sayısı</span>
                            <span class="info-val">${ilan.basvuru_sayisi || 0} Aday</span>
                        </div>
                        <div class="info-box-modern">
                            <span class="info-label">Yan Haklar</span>
                            <span class="info-val">${ilan.yanhak || 'Belirtilmemiş'}</span>
                        </div>
                        <div class="info-box-modern">
                            <span class="info-label">Maaş Bilgisi</span>
                            <span class="info-val">${ilan.maas_formatli} TL / Aylık</span>
                        </div>
                        <div class="info-box-modern">
                            <span class="info-label">Çalışma Günleri</span>
                            <span class="info-val">${ilan.calismagunleri || 'Hafta içi'}</span>
                        </div>
                        <div class="info-box-modern">
                            <span class="info-label">Çalışma Saatleri</span>
                            <span class="info-val">${ilan.calismasaatleri || '08:00 - 18:00'}</span>
                        </div>
                    </div>
                </div>


                <div class="report-link" onclick="sikayetModalAc(${ilan.ilanID})">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
                    İlanı Şikayet Et
                </div>

                <div class="detay-section-modern" style="margin-top:48px; padding:40px; background:#f8fafc; border-radius:20px;">
                    <div style="display:flex; align-items:center; gap:16px; margin-bottom:20px;">
                        <div style="width:64px; height:64px; background:#fff; border-radius:14px; display:flex; align-items:center; justify-content:center; color:var(--primary); box-shadow:0 4px 10px rgba(0,0,0,0.05);">
                            <svg width="32" height="32"><use xlink:href="#icon-company"></use></svg>
                        </div>
                        <div>
                            <h2 class="section-h2" style="margin-bottom:4px;">${ilan.firmaadi}</h2>
                            <span style="font-size:13px; color:#64748b; font-weight:600;">Doğrulanmış İşveren</span>
                        </div>
                    </div>
                    <div class="desc-text" style="font-size:14px; color:#64748b;">
                        ${ilan.hakkimda || 'Firma hakkında detaylı bilgi bulunmamaktadır.'}
                    </div>
                </div>
            </div>
        `;
    } catch(e) {
        console.error(e);
        panel.innerHTML = `<div class="detay-bos-state">İlan yüklenirken bir hata oluştu: ${e.message}</div>`;
    }
}

async function ilanBasvur(id) {
    if(!JS_LOGIN) { window.location.href='giris.php'; return; }
    const res = await fetch('islem.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `islem=basvuru_yap_ajax&ilan_id=${id}`
    });
    const data = await res.json();
    if(data.durum === 'basarili') {
        document.getElementById('mesaj_metin').innerText = 'Başvurunuz başarıyla iletildi.';
        modalAc('basariModal');
        const btn = document.getElementById('split_apply_btn');
        if(btn) { btn.innerText = 'Başvuruldu'; btn.disabled = true; }
    } else { alert(data.hata); }
}

async function ilanKaydetToggleSplit(btn, id) {
    if(!JS_LOGIN) { window.location.href='giris.php'; return; }
    
    // Anında UI güncellemesi (Optimistic UI)
    const isSil = btn.classList.contains('aktif');
    const islem = isSil ? 'kayit_sil' : 'kaydet';
    const newValue = !isSil;
    
    // Detayları al (header altından)
    let baslik = 'İlan', firmaadi = 'Firma';
    const hE = document.querySelector('.detay-h1');
    if(hE) baslik = hE.innerText.trim();
    const fE = document.querySelector('.detay-firma-link');
    if(fE) firmaadi = fE.innerText.trim();
    
    if(typeof syncSavedStateAcrossDOM === 'function') {
        syncSavedStateAcrossDOM(id, newValue, baslik, firmaadi, new Date().toLocaleDateString('tr-TR'));
    } else {
        const newIcon = newValue ? '#icon-save-active' : '#icon-save';
        btn.classList.toggle('aktif');
        btn.innerHTML = `<svg width="18" height="18"><use xlink:href="${newIcon}"></use></svg> ${newValue ? 'Kaydedildi' : 'Kaydet'}`;
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
        } else {
            showToast(newValue ? 'İlan kaydedildi' : 'İlan kaydedilenlerden çıkarıldı');
        }
    } catch(err) {
        if(typeof syncSavedStateAcrossDOM === 'function') {
            syncSavedStateAcrossDOM(id, !newValue, baslik, firmaadi, new Date().toLocaleDateString('tr-TR'));
        } else {
            const revertIcon = !newValue ? '#icon-save-active' : '#icon-save';
            btn.classList.toggle('aktif');
            btn.innerHTML = `<svg width="18" height="18"><use xlink:href="${revertIcon}"></use></svg> ${!newValue ? 'Kaydedildi' : 'Kaydet'}`;
        }
        if(typeof showToast === 'function') showToast('Bir hata oluştu', 'error');
    }
}
async function ilanPaylas(id, baslik) {
    const url = window.location.origin + window.location.pathname + '?id=' + id;
    if (navigator.share) {
        try {
            await navigator.share({
                title: baslik,
                text: 'Kariyerlen üzerinde bu iş ilanına göz atın: ' + baslik,
                url: url
            });
        } catch (err) {
            console.log('Paylaşım iptal edildi veya hata oluştu.');
        }
    } else {
        // Fallback: Copy to clipboard
        try {
            await navigator.clipboard.writeText(url);
            alert('İlan linki kopyalandı!');
        } catch (err) {
            alert('Link: ' + url);
        }
    }
}
</script>

<?php include 'components/footer.php'; ?>