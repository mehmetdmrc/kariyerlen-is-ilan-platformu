<?php
session_start();
require 'baglan.php';

$kat_filter = $_GET['kategori'] ?? 'Tümü';

// Veritabanından blogları çek
if($kat_filter == 'Tümü') {
    $sorgu = $db->query("SELECT * FROM blog ORDER BY sira DESC, tarih DESC, id DESC");
} else {
    $sorgu = $db->prepare("SELECT * FROM blog WHERE kategori = ? ORDER BY sira DESC, tarih DESC, id DESC");
    $sorgu->execute([$kat_filter]);
}
$filtreli_makaleler = $sorgu->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Blog - Kariyerlen';
$meta_description = 'Kariyerlen blog ile iş dünyası, özgeçmiş hazırlama, mülakat teknikleri ve mesleki gelişim hakkında en güncel yazıları okuyun.';
$meta_keywords = 'blog, kariyer, iş arama, mülakat, özgeçmiş, mesleki gelişim';
include 'components/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Bitter:wght@400;700;900&display=swap" rel="stylesheet">

<style>
    :root {
        --isinolsun-red: #E0212B;
        --blog-bg: #F5F5F5;
        --blog-font: 'Bitter', serif;
    }
    
    body { background-color: var(--blog-bg); font-family: 'Inter', sans-serif; }

    /* ── MASONRY HERO ── */
    .blog-masonry {
        max-width: 1400px;
        margin: 40px auto;
        padding: 0 20px;
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        grid-template-rows: 220px 220px;
        gap: 15px;
    }

    .masonry-item {
        position: relative;
        border-radius: 4px;
        overflow: hidden;
        cursor: pointer;
        background: #000;
        display: block;
        text-decoration: none;
    }
    .masonry-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        opacity: 0.7;
        transition: 0.5s;
    }
    .masonry-item:hover img { transform: scale(1.05); opacity: 0.5; }
    
    .masonry-content {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        padding: 30px;
        background: linear-gradient(transparent, rgba(0,0,0,0.8));
        color: #fff;
    }
    .masonry-cat { font-size: 13px; font-weight: 700; color: #fff; margin-bottom: 10px; border-bottom: 2px solid var(--isinolsun-red); display: inline-block; padding-bottom: 2px; }
    .masonry-title { font-family: var(--blog-font); font-size: 24px; font-weight: 900; line-height: 1.2; margin-top: 5px; color: #fff; }

    /* Grid positions */
    .item-1 { grid-column: 1 / 2; grid-row: 1 / 2; }
    .item-2 { grid-column: 2 / 3; grid-row: 1 / 2; }
    .item-3 { grid-column: 1 / 3; grid-row: 2 / 3; }
    .item-4 { grid-column: 3 / 5; grid-row: 1 / 3; }
    .item-4 .masonry-title { font-size: 42px; }

    /* ── SUB NAV ── */
    .blog-subnav {
        background: #fff;
        border-bottom: 1px solid #eee;
        margin-bottom: 40px;
        position: sticky;
        top: 64px;
        z-index: 100;
    }
    .subnav-inner {
        max-width: 1400px;
        margin: 0 auto;
        display: flex;
        gap: 30px;
        padding: 0 20px;
        overflow-x: auto;
    }
    .subnav-link {
        padding: 20px 0;
        font-size: 14px;
        font-weight: 700;
        color: #333;
        text-decoration: none;
        border-bottom: 3px solid transparent;
        transition: 0.2s;
        white-space: nowrap;
    }
    .subnav-link:hover, .subnav-link.active { color: var(--isinolsun-red); border-color: var(--isinolsun-red); }

    /* ── ARTICLE GRID ── */
    .blog-content {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 20px 100px;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
    }

    .blog-card {
        background: #fff;
        border-radius: 4px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        cursor: pointer;
        transition: 0.3s;
        display: block;
        text-decoration: none;
        color: inherit;
    }
    .blog-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); color: inherit; }
    .card-img-wrap { width: 100%; padding-top: 50%; position: relative; }
    .card-img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-size: cover; background-position: center; }
    
    .card-body { padding: 25px; }
    .card-cat { font-size: 12px; font-weight: 700; color: var(--isinolsun-red); margin-bottom: 10px; }
    .card-title { font-family: var(--blog-font); font-size: 20px; font-weight: 900; color: #333; line-height: 1.3; margin-bottom: 15px; }
    .card-text { font-size: 14px; color: #666; line-height: 1.6; }

    @media (max-width: 1024px) {
        .blog-masonry { grid-template-columns: 1fr 1fr; grid-template-rows: auto; }
        .item-1, .item-2, .item-3, .item-4 { grid-column: auto; grid-row: auto; height: 300px; }
        .blog-content { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 768px) {
        .blog-masonry { grid-template-columns: 1fr; }
        .blog-content { grid-template-columns: 1fr; }
    }
</style>

<div class="blog-subnav">
    <div class="subnav-inner">
        <a href="blog.php?kategori=Tümü" class="subnav-link <?php echo $kat_filter=='Tümü' ? 'active' : ''; ?>">Tümü</a>
        <a href="blog.php?kategori=Haberin Olsun" class="subnav-link <?php echo $kat_filter=='Haberin Olsun' ? 'active' : ''; ?>">Haberin Olsun</a>
        <a href="blog.php?kategori=Çalışan Rehberi" class="subnav-link <?php echo $kat_filter=='Çalışan Rehberi' ? 'active' : ''; ?>">Çalışan Rehberi</a>
        <a href="blog.php?kategori=İşveren Rehberi" class="subnav-link <?php echo $kat_filter=='İşveren Rehberi' ? 'active' : ''; ?>">İşveren Rehberi</a>
        <a href="blog.php?kategori=Mesleki Gelişim" class="subnav-link <?php echo $kat_filter=='Mesleki Gelişim' ? 'active' : ''; ?>">Mesleki Gelişim</a>
    </div>
</div>

<?php if($kat_filter == 'Tümü' && count($filtreli_makaleler) >= 4): ?>
<div class="blog-masonry">
    <a href="blog_detay.php?id=<?php echo $filtreli_makaleler[1]['id']; ?>" class="masonry-item item-1">
        <img src="<?php echo htmlspecialchars($filtreli_makaleler[1]['resim']); ?>">
        <div class="masonry-content">
            <div class="masonry-title"><?php echo htmlspecialchars($filtreli_makaleler[1]['baslik']); ?></div>
        </div>
    </a>
    <a href="blog_detay.php?id=<?php echo $filtreli_makaleler[2]['id']; ?>" class="masonry-item item-2">
        <img src="<?php echo htmlspecialchars($filtreli_makaleler[2]['resim']); ?>">
        <div class="masonry-content">
            <div class="masonry-title"><?php echo htmlspecialchars($filtreli_makaleler[2]['baslik']); ?></div>
        </div>
    </a>
    <a href="blog_detay.php?id=<?php echo $filtreli_makaleler[3]['id']; ?>" class="masonry-item item-3">
        <img src="<?php echo htmlspecialchars($filtreli_makaleler[3]['resim']); ?>">
        <div class="masonry-content">
            <div class="masonry-cat"><?php echo htmlspecialchars($filtreli_makaleler[3]['kategori']); ?></div>
            <div class="masonry-title"><?php echo htmlspecialchars($filtreli_makaleler[3]['baslik']); ?></div>
        </div>
    </a>
    <a href="blog_detay.php?id=<?php echo $filtreli_makaleler[0]['id']; ?>" class="masonry-item item-4">
        <img src="<?php echo htmlspecialchars($filtreli_makaleler[0]['resim']); ?>">
        <div class="masonry-content">
            <div class="masonry-cat"><?php echo htmlspecialchars($filtreli_makaleler[0]['kategori']); ?></div>
            <div class="masonry-title"><?php echo htmlspecialchars($filtreli_makaleler[0]['baslik']); ?></div>
        </div>
    </a>
</div>
<?php endif; ?>

<div class="blog-content" style="<?php echo ($kat_filter != 'Tümü' || count($filtreli_makaleler) < 4) ? 'margin-top:40px;' : ''; ?>">
    <?php if(empty($filtreli_makaleler)): ?>
        <div style="grid-column: 1/-1; text-align:center; padding:100px; color:#999;">
            Bu kategoride henüz makale bulunmamaktadır.
        </div>
    <?php else: ?>
        <?php 
        // Eğer tümü seçiliyse ve 4'ten fazla varsa, ilk 4'ü masonry'de gösterildiği için atla
        $baslangic = ($kat_filter == 'Tümü' && count($filtreli_makaleler) >= 4) ? 4 : 0;
        for($i = $baslangic; $i < count($filtreli_makaleler); $i++): 
            $m = $filtreli_makaleler[$i];
        ?>
        <a href="blog_detay.php?id=<?php echo $m['id']; ?>" class="blog-card">
            <div class="card-img-wrap">
                <div class="card-img" style="background-image: url('<?php echo htmlspecialchars($m['resim']); ?>')"></div>
            </div>
            <div class="card-body">
                <div class="card-cat"><?php echo htmlspecialchars($m['kategori']); ?></div>
                <h3 class="card-title"><?php echo htmlspecialchars($m['baslik']); ?></h3>
                <div class="card-text"><?php echo $m['ozet']; ?></div>
            </div>
        </a>
        <?php endfor; ?>
    <?php endif; ?>
</div>

<?php include 'components/footer.php'; ?>
