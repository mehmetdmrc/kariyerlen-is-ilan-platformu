<?php
session_start();
require 'baglan.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$sorgu = $db->prepare("SELECT * FROM blog WHERE id = ?");
$sorgu->execute([$id]);
$blog = $sorgu->fetch(PDO::FETCH_ASSOC);

if(!$blog) {
    header("Location: blog.php");
    exit;
}

// SEO Değişkenleri
$page_title = (!empty($blog['meta_title']) ? $blog['meta_title'] : $blog['baslik']) . ' - Kariyerlen Blog';
$meta_description = !empty($blog['meta_description']) ? $blog['meta_description'] : strip_tags($blog['ozet']);
$meta_keywords = !empty($blog['meta_keywords']) ? $blog['meta_keywords'] : '';

include 'components/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Bitter:wght@400;700;900&display=swap" rel="stylesheet">

<style>
    :root {
        --isinolsun-red: #E0212B;
        --blog-font: 'Bitter', serif;
    }
    
    body { background-color: #fff; font-family: 'Inter', sans-serif; }

    .post-container { 
        max-width: 850px; 
        margin: 0 auto; 
        padding: 60px 20px 100px; 
        position: relative;
    }
    .post-header-top { text-align: center; margin-bottom: 50px; }
    .post-cat-tag { 
        display: inline-block; 
        color: var(--isinolsun-red); 
        font-weight: 800; 
        font-size: 13px; 
        text-transform: uppercase; 
        letter-spacing: 1.5px;
        margin-bottom: 15px;
        border-bottom: 2px solid var(--isinolsun-red);
        padding-bottom: 4px;
        text-decoration: none;
    }
    .post-cat-tag:hover { color: #b91c1c; border-color: #b91c1c; }
    
    .post-title { 
        font-family: var(--blog-font); 
        font-size: 48px; 
        font-weight: 900; 
        color: #1a1a1a; 
        margin-bottom: 30px; 
        line-height: 1.15; 
        letter-spacing: -1px;
    }
    .post-content li {
        margin-bottom: 10px;
    }
    .post-tags {
        margin-top: 50px;
        padding-top: 30px;
        border-top: 1px solid #f1f5f9;
    }
    .tags-title {
        font-weight: 700;
        font-size: 15px;
        color: #0f172a;
        margin-bottom: 12px;
    }
    .tags-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    .post-tag {
        display: inline-block;
        background: #f1f5f9;
        color: #475569;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 500;
        transition: 0.2s;
        cursor: pointer;
    }
    .post-tag:hover {
        background: #e2e8f0;
        color: #0f172a;
    }
    @media (prefers-color-scheme: dark) {
        .post-tags { border-top-color: #334155; }
        .tags-title { color: #f8fafc; }
        .post-tag {
            background: #1e293b;
            color: #cbd5e1;
        }
        .post-tag:hover {
            background: #334155;
            color: #f8fafc;
        }
    }
    @media (max-width: 768px) {
        .post-title { font-size: 32px; }
    }
    
    .post-meta-modern {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 15px;
        color: #666;
        font-size: 14px;
    }
    
    .post-hero-wrap { position: relative; margin-bottom: 50px; }
    .post-hero-img { 
        width: 100%; 
        max-height: 450px;
        object-fit: cover;
        border-radius: 12px; 
        box-shadow: 0 30px 60px -20px rgba(0,0,0,0.2); 
    }
    
    .post-content { 
        font-size: 19px; 
        line-height: 1.9; 
        color: #2c3e50; 
        font-family: 'Inter', sans-serif;
    }
    .post-content p { margin-bottom: 25px; }
    .post-content h3 { 
        font-family: var(--blog-font); 
        font-size: 28px; 
        font-weight: 900; 
        margin: 45px 0 20px; 
        color: #1a1a1a;
    }
    .post-content blockquote {
        border-left: 5px solid var(--isinolsun-red);
        padding: 20px 30px;
        margin: 40px 0;
        background: #fef2f2;
        font-style: italic;
        font-size: 22px;
        color: #333;
        border-radius: 0 12px 12px 0;
    }
    .post-content img {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
        margin: 20px 0;
    }
    
    .post-share-sidebar {
        position: absolute;
        left: -80px;
        top: 200px;
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    @media (max-width: 1080px) {
        .post-share-sidebar {
            position: static;
            flex-direction: row;
            justify-content: center;
            margin-bottom: 40px;
        }
    }
    
    .share-btn-round {
        width: 45px; height: 45px;
        border-radius: 50%;
        background: #fff;
        border: 1px solid #eee;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        transition: 0.3s;
        color: #666;
        text-decoration: none;
    }
    .share-btn-round:hover { background: var(--isinolsun-red); color: #fff; border-color: var(--isinolsun-red); transform: scale(1.1); }
</style>

<div class="post-container">
    <div class="post-share-sidebar">
        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']); ?>" target="_blank" class="share-btn-round" title="Facebook'ta Paylaş"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg></a>
        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode($blog['baslik']); ?>" target="_blank" class="share-btn-round" title="X'te Paylaş"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4l11.733 16h4.267l-11.733-16z"/><path d="M4 20l6.768-6.768m2.464-2.464l6.768-6.768"/></svg></a>
        <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']); ?>" target="_blank" class="share-btn-round" title="LinkedIn'de Paylaş"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg></a>
        <div class="share-btn-round" title="Bağlantıyı Kopyala" onclick="navigator.clipboard.writeText(window.location.href); showToast('Bağlantı kopyalandı!');"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg></div>
    </div>
    
    <div class="post-header-top">
        <a href="blog.php?kategori=<?php echo urlencode($blog['kategori']); ?>" class="post-cat-tag"><?php echo htmlspecialchars($blog['kategori']); ?></a>
        <h1 class="post-title"><?php echo htmlspecialchars($blog['baslik']); ?></h1>
        <div class="post-meta-modern">
            <div style="color:#94a3b8; font-size:14px; font-weight:600;"><?php echo date("d.m.Y", strtotime($blog['tarih'])); ?> • Kariyerlen Ekibi</div>
        </div>
    </div>
    
    <?php if($blog['resim']): ?>
    <div class="post-hero-wrap">
        <img src="<?php echo htmlspecialchars($blog['resim']); ?>" class="post-hero-img" alt="<?php echo htmlspecialchars($blog['baslik']); ?>">
    </div>
    <?php endif; ?>
    
    <div class="post-content">
        <?php 
        // İçerik zaten HTML destekliyoruz
        echo $blog['icerik']; 
        ?>
    </div>
    
    <?php if(!empty($blog['meta_keywords'])): ?>
    <div class="post-tags">
        <div class="tags-title">Etiketler:</div>
        <div class="tags-list">
            <?php 
            $keywords = explode(',', $blog['meta_keywords']);
            foreach($keywords as $key) {
                $k = trim($key);
                if(!empty($k)) {
                    echo '<span class="post-tag">#' . htmlspecialchars($k) . '</span>';
                }
            }
            ?>
        </div>
    </div>
    <?php endif; ?>
    
    <div style="margin-top: 60px; padding-top: 30px; border-top: 1px solid #eee; text-align: center;">
        <a href="blog.php" style="display: inline-flex; align-items: center; gap: 8px; color: #666; text-decoration: none; font-weight: 600; transition: 0.2s;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            Bloglara Dön
        </a>
    </div>
</div>

<?php include 'components/footer.php'; ?>
