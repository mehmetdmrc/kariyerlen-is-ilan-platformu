<?php
if(!isset($_SESSION)) { session_start(); }

// Bekleyen ilan sayısını al
$bekleyen_sorgu = $db->query("SELECT COUNT(*) FROM ilan WHERE idurumID = 3");
$bekleyen_sayi = $bekleyen_sorgu->fetchColumn();

$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="admin-sidebar">
    <div class="sidebar-logo">
        <img src="img/admin-logo-dark.jpg" alt="Kariyerlen Logo" style="max-height: 60px; width: auto; mix-blend-mode: lighten;">
    </div>
    
    <div class="sidebar-nav">
        <a href="index.php" class="sidebar-item <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            Dashboard
        </a>
        
        <a href="ilanlar.php" class="sidebar-item <?php echo $current_page == 'ilanlar.php' ? 'active' : ''; ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
            İlan Yönetimi
            <?php if($bekleyen_sayi > 0): ?>
            <span class="sidebar-badge"><?php echo $bekleyen_sayi; ?></span>
            <?php endif; ?>
        </a>

        <a href="bloglar.php" class="sidebar-item <?php echo $current_page == 'bloglar.php' ? 'active' : ''; ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
            Blog Yönetimi
        </a>

        <a href="sikayetler.php" class="sidebar-item <?php echo $current_page == 'sikayetler.php' ? 'active' : ''; ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            Şikayetler
        </a>
        
        <a href="kullanicilar.php" class="sidebar-item <?php echo $current_page == 'kullanicilar.php' ? 'active' : ''; ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            Kullanıcılar
        </a>
        
        <a href="basvurular.php" class="sidebar-item <?php echo $current_page == 'basvurular.php' ? 'active' : ''; ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            Başvurular
        </a>
    </div>
    
    <div class="sidebar-footer">
        <a href="../index.php" class="sidebar-item" target="_blank" style="margin-bottom: 8px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
            Ana Siteye Dön
        </a>
        <a href="admin_cikis.php" class="sidebar-item" style="color: #ef4444;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            Çıkış Yap
        </a>
    </div>
</div>
