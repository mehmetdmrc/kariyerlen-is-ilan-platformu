<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Kariyerlen - Hayalinizdeki İşi Bulun'; ?></title>
    <meta name="description" content="<?php echo isset($meta_description) && !empty($meta_description) ? htmlspecialchars($meta_description) : 'Kariyerlen ile güncel iş ilanlarını keşfedin, kariyerinize yön verin.'; ?>">
    <?php if(isset($meta_keywords) && !empty($meta_keywords)): ?>
    <meta name="keywords" content="<?php echo htmlspecialchars($meta_keywords); ?>">
    <?php endif; ?>
    <meta name="robots" content="index, follow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
    :root {
        --primary: #ff7e1d;
        --primary-hover: #ea580c;
        --primary-light: #fff4ec;
        --bg: #f5f5f5;
        --card-bg: #ffffff;
        --text-main: #1a1a2e;
        --text-muted: #6b7280;
        --text-light: #9ca3af;
        --border: #e5e7eb;
        --border-light: #f3f4f6;
        --radius: 12px;
        --radius-lg: 16px;
        --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
        --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
        --shadow-lg: 0 8px 24px rgba(0,0,0,0.1);
    }

    html { overflow-y: scroll; scroll-behavior: smooth; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        background-color: var(--bg);
        color: var(--text-main);
        -webkit-font-smoothing: antialiased;
        line-height: 1.5;
    }
    a { text-decoration: none; color: inherit; transition: color 0.15s; }

    /* ── HEADER ── */
    header {
        background: #fff;
        border-bottom: 1px solid var(--border);
        position: sticky;
        top: 0;
        z-index: 1000;
        height: 64px;
        display: flex;
        align-items: center;
        padding: 0 24px;
    }
    .header-inner {
        max-width: 1280px;
        width: 100%;
        margin: 0 auto;
        display: flex;
        align-items: center;
        gap: 32px;
    }
    .logo {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 800;
        font-size: 20px;
        color: var(--text-main);
        flex-shrink: 0;
        letter-spacing: -0.5px;
    }
    .logo-icon {
        background: var(--primary);
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;    
        color: #fff;
    }
    .logo-footer { color: white; }
    .logo-footer span { color: var(--primary); }

    .logo-text { color: var(--text-main); }
    .logo-text span { color: var(--primary); }

    /* Nav links */
    .header-nav {
        display: flex;
        align-items: center;
        gap: 4px;
        flex: 1;
    }
    .nav-link {
        color: var(--text-muted);
        font-weight: 500;
        font-size: 14px;
        padding: 6px 12px;
        border-radius: 10px;
        transition: all 0.15s;
    }
    .nav-link:hover { color: var(--text-main); background: var(--border-light); }
    .nav-link.aktif { color: var(--primary); font-weight: 600; }

    /* Header right */
    .header-right {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
    }

    /* Buttons */
    .btn-giris {
        color: var(--text-main);
        font-weight: 700;
        font-size: 14px;
        padding: 10px 20px;
        border-radius: 12px;
        border: 1.5px solid var(--border);
        background: #fff;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 2px 6px rgba(0,0,0,0.04);
    }
    .btn-giris:hover { border-color: var(--primary); color: var(--primary); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.06); }

    .btn-turuncu-nav {
        background: linear-gradient(135deg, #ff7e1d, #ea580c);
        color: #fff;
        font-weight: 700;
        font-size: 14px;
        padding: 10px 20px;
        border-radius: 12px;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 4px 12px rgba(234, 88, 12, 0.25);
    }
    .btn-turuncu-nav:hover { background: linear-gradient(135deg, #ea580c, #c2410c); color: #fff; transform: translateY(-1px); box-shadow: 0 6px 16px rgba(234, 88, 12, 0.35); }

    .btn-outline-nav {
        border: 1.5px solid var(--border);
        color: var(--text-main);
        font-weight: 700;
        font-size: 14px;
        padding: 10px 20px;
        border-radius: 12px;
        background: transparent;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 2px 6px rgba(0,0,0,0.04);
    }
    .btn-outline-nav:hover { border-color: var(--primary); color: var(--primary); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.06); }

    /* Profile dropdown */
    .profil-dropdown { position: relative; }
    .profil-buton {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 9px 18px;
        border: 1.5px solid var(--border);
        border-radius: 12px;
        background: #fff;
        font-weight: 700;
        font-size: 14px;
        cursor: pointer;
        color: var(--text-main);
        transition: all 0.2s;
        box-shadow: 0 2px 6px rgba(0,0,0,0.04);
    }
    .profil-buton:hover { border-color: var(--primary); color: var(--primary); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
    .dropdown-icerik {
        display: none;
        position: absolute;
        top: 100%;
        right: 0;
        background: #fff;
        min-width: 180px;
        border: 1px solid var(--border);
        border-radius: 10px;
        box-shadow: var(--shadow-lg);
        z-index: 1000;
        padding: 6px;
        margin-top: 5px;
    }
    .profil-dropdown::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        height: 8px;
    }
    .profil-dropdown:hover .dropdown-icerik { display: block; }
    .dropdown-icerik a {
        display: block;
        padding: 9px 14px;
        font-size: 14px;
        font-weight: 500;
        color: var(--text-muted);
        border-radius: 6px;
    }
    .dropdown-icerik a:hover { background: var(--border-light); color: var(--primary); }

    /* Dark mode toggle */
    .theme-toggle-btn {
        background: var(--border-light);
        border: none;
        cursor: pointer;
        width: 36px;
        height: 36px;
        border-radius: 6px;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s;
        flex-shrink: 0;
    }
    .theme-toggle-btn:hover { background: var(--border); color: var(--text-main); }

    /* Badge */
    .bildirim-rozet {
        background: #ef4444;
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        min-width: 18px;
        height: 18px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 4px;
    }

    /* ── MODALS ── */
    .modal-arkaplan {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.45);
        z-index: 2000;
        justify-content: center;
        align-items: center;
        backdrop-filter: blur(4px);
    }
    .modal-icerik {
        background: #fff;
        border-radius: 14px;
        overflow: hidden;
        position: relative;
        box-shadow: var(--shadow-lg);
        animation: modalIn 0.2s ease-out;
        border: 1px solid var(--border);
        width: 90%;
        max-width: 520px;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
    }
    .modal-icerik.buyuk { max-width: 1000px; }
    .modal-icerik.kucuk { max-width: 420px; }
    @keyframes modalIn { from { opacity:0; transform:scale(0.96) translateY(8px); } to { opacity:1; transform:scale(1) translateY(0); } }
    .kapat-btn {
        position: absolute;
        top: 16px;
        right: 16px;
        border: none;
        background: var(--border-light);
        width: 32px;
        height: 32px;
        border-radius: 50%;
        color: var(--text-muted);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 16px;
        z-index: 10;
    }
    .kapat-btn:hover { background: var(--border); }
    .modal-header-tabs {
        padding: 24px 32px 0;
        background: #fff;
        border-bottom: 1px solid var(--border-light);
        z-index: 5;
    }
    .modal-body { 
        padding: 32px;
        overflow-y: auto;
        flex: 1;
    }

    /* Global Profile Grid */
    .profil-ilan-izgara {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        padding: 5px;
    }
    @media (max-width: 768px) {
        .profil-ilan-izgara { grid-template-columns: 1fr; }
    }

    /* ── CUSTOM DROPDOWN ── */
    .custom-dropdown { position: relative; user-select: none; }
    .dropdown-trigger {
        background: #fff;
        border: 1.5px solid var(--border);
        padding: 10px 14px;
        border-radius: 8px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        font-weight: 500;
        color: var(--text-main);
        font-size: 14px;
        gap: 8px;
    }
    .dropdown-trigger:hover { border-color: var(--primary); }
    .dropdown-menu {
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        width: 100%;
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 10px;
        box-shadow: var(--shadow-lg);
        display: none;
        z-index: 1000;
        max-height: 240px;
        overflow-y: auto;
        padding: 4px;
    }
    .custom-dropdown.active .dropdown-menu { display: block; }
    .dropdown-option {
        padding: 9px 12px;
        border-radius: 6px;
        cursor: pointer;
        color: var(--text-muted);
        font-size: 14px;
        font-weight: 500;
    }
    .dropdown-option:hover { background: var(--border-light); color: var(--primary); }

    /* ── FOOTER ── */
    footer {
        background: #111827;
        color: #9ca3af;
        padding: 60px 24px 32px;
        margin-top: 0;
    }
    .footer-inner { max-width: 1280px; margin: 0 auto; }
    .footer-icerik {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr;
        gap: 48px;
        padding-bottom: 40px;
        border-bottom: 1px solid #1f2937;
        margin-bottom: 28px;
    }
    .footer-logo {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #fff;
        font-weight: 800;
        font-size: 18px;
        margin-bottom: 14px;
    }
    .footer-logo-icon {
        background: var(--primary);
        width: 32px;
        height: 32px;
        border-radius: 7px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
    }
    .footer-sutun p { font-size: 14px; line-height: 1.7; color: #6b7280; }
    .footer-sutun h4 {
        color: #fff;
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 18px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }
    .footer-sutun ul { list-style: none; display: flex; flex-direction: column; gap: 10px; }
    .footer-sutun ul li a { color: #6b7280; font-size: 14px; font-weight: 500; }
    .footer-sutun ul li a:hover { color: var(--primary); }
    .footer-alt {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 13px;
        color: #4b5563;
    }
    .sosyal-medya { display: flex; gap: 14px; }
    .sosyal-medya a { color: #4b5563; }
    .sosyal-medya a:hover { color: var(--primary); }

    /* ── GLOBAL UTILITY ── */
    .btn-tumunu-gor {
        display: block;
        margin: 48px auto 0;
        padding: 12px 36px;
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 8px;
        font-weight: 700;
        font-size: 14px;
        color: var(--text-main);
        text-align: center;
        width: fit-content;
        transition: all 0.15s;
    }
    .btn-tumunu-gor:hover { border-color: var(--primary); color: var(--primary); }

    /* Saved Button States - Premium Orange Theme */
    .btn-kaydet.aktif, .btn-icon-gray.aktif, .btn-action-save.aktif {
        background: #fff7ed !important;
        color: #ea580c !important;
        border-color: #ffedd5 !important;
        box-shadow: none;
    }

    /* Disabled Apply Button */
    .btn-apply-compact:disabled, .btn-apply:disabled {
        background: #e2e8f0 !important;
        color: #94a3b8 !important;
        border: none !important;
        cursor: not-allowed !important;
        transform: none !important;
        box-shadow: none !important;
    }

    @keyframes spin { to { transform: rotate(360deg); } }

    /* ── PREMIUM TOAST NOTIFICATION ── */
    #toast-container {
        position: fixed;
        top: 30px;
        right: 30px;
        z-index: 10000;
        display: flex;
        flex-direction: column;
        gap: 12px;
        pointer-events: none;
    }
    .toast {
        background: #fff;
        color: #1e293b;
        padding: 16px 24px;
        border-radius: 16px;
        font-size: 15px;
        font-weight: 700;
        box-shadow: 0 15px 40px rgba(0,0,0,0.12);
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 320px;
        border: 1px solid #f1f5f9;
        animation: toastInRight 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        pointer-events: auto;
        cursor: pointer;
    }
    .toast.success { border-left: 5px solid #22c55e; }
    .toast.error { border-left: 5px solid #ef4444; }
    
    .toast-icon {
        width: 36px;
        height: 36px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    @keyframes toastInRight {
        from { opacity: 0; transform: translateX(50px) scale(0.9); }
        to { opacity: 1; transform: translateX(0) scale(1); }
    }
    @keyframes toastOutRight {
        from { opacity: 1; transform: translateX(0); }
        to { opacity: 0; transform: translateX(50px); }
    }

    @media (max-width: 1024px) {
        .footer-icerik { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 768px) {
        header { height: auto; padding: 12px 16px; }
        .header-inner { flex-wrap: wrap; gap: 12px; }
        .header-nav { display: none; }
        .footer-icerik { grid-template-columns: 1fr; gap: 28px; }
        .footer-alt { flex-direction: column; gap: 12px; text-align: center; }
    }
    </style>

    <div id="toast-container"></div>

    <script>
    function showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        if(!container) return;
        
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        const iconName = type === 'success' ? 'icon-tick' : 'icon-error-3d';
        
        toast.innerHTML = `
            <div class="toast-icon">
                <svg width="30" height="30"><use xlink:href="#${iconName}"></use></svg>
            </div>
            <div class="toast-content">${message}</div>
        `;
        
        toast.onclick = () => {
            toast.style.animation = 'toastOutRight 0.4s forwards';
            setTimeout(() => toast.remove(), 400);
        };
        
        container.appendChild(toast);
        
        setTimeout(() => {
            if(toast.parentElement) {
                toast.style.animation = 'toastOutRight 0.4s forwards';
                setTimeout(() => toast.remove(), 400);
            }
        }, 3500);
    }
    </script>

    <!-- Global Onay Modalı -->
    <div id="confirmModal" class="modal-arkaplan" style="z-index:11000;">
        <div class="modal-icerik kucuk" style="max-width:380px; border-radius:24px;">
            <div class="modal-body" style="padding:40px 30px; text-align:center;">
                <div style="width:64px; height:64px; background:#fef2f2; color:#ef4444; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px;">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                </div>
                <h3 id="confirmTitle" style="font-size:20px; font-weight:800; color:#111827; margin-bottom:10px;">Emin misiniz?</h3>
                <p id="confirmText" style="font-size:14px; color:#64748b; line-height:1.5; margin-bottom:24px;">Bu işlem geri alınamaz.</p>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <button onclick="modalKapat('confirmModal')" style="background:#f1f5f9; color:#475569; border:none; padding:12px; border-radius:12px; font-weight:700; cursor:pointer;">İptal</button>
                    <button id="confirmYesBtn" style="background:#ef4444; color:#fff; border:none; padding:12px; border-radius:12px; font-weight:700; cursor:pointer; box-shadow:0 4px 12px rgba(239,68,68,0.2);">Evet, Sil</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    let confirmCallback = null;
    function gOnay(baslik, metin, callback) {
        document.getElementById('confirmTitle').innerText = baslik;
        document.getElementById('confirmText').innerText = metin;
        confirmCallback = callback;
        modalAc('confirmModal');
    }
    document.getElementById('confirmYesBtn').onclick = function() {
        if(confirmCallback) confirmCallback();
        modalKapat('confirmModal');
    };
    </script>
    <link rel="stylesheet" href="css/profil.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/dark-mode.css?v=<?php echo time(); ?>">
    <script src="js/dark-mode.js?v=<?php echo time(); ?>"></script>
</head>
<body>
    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-mode');
        }
    </script>
    <?php include 'components/svg_defs.php'; ?>

    <header>
        <div class="header-inner">
            <!-- Logo + Tema butonu (sol) -->
            <a href="index.php" class="logo">
                <img src="img/logo.png" alt="Kariyerlen" style="height: 36px; width: auto; display: block;">
            </a>
            <button id="theme-toggle" class="theme-toggle-btn" onclick="toggleTheme()" title="Tema Değiştir">
                <svg id="theme-icon-sun" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                <svg id="theme-icon-moon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
            </button>

            <!-- Spacer -->
            <div style="flex:1;"></div>

            <!-- Nav + Butonlar (sağ) -->
            <div class="header-right">
                <nav class="header-nav" style="display:flex;">
                    <a href="index.php" class="nav-link">Ana Sayfa</a>
                    <a href="is_ilanlari.php" class="nav-link">İş İlanları</a>
                    <a href="blog.php" class="nav-link">Blog</a>
                </nav>
                <div style="width:1px; height:20px; background:var(--border); flex-shrink:0;"></div>

                <?php if(isset($_SESSION['kullaniciID'])): ?>
                    <?php if($_SESSION['krolID'] == 2): ?>
                        <button onclick="ilanVerAc()" class="btn-turuncu-nav">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            İlan Ver
                        </button>
                    <?php endif; ?>
                    <div class="profil-dropdown">
                        <button class="profil-buton" onclick="profilAc()">
                            <svg width="16" height="16"><use xlink:href="#icon-user"></use></svg>
                            Profilim
                            <?php if(isset($okunmamis_mesaj_sayisi) && $okunmamis_mesaj_sayisi > 0): ?>
                                <span class="bildirim-rozet"><?php echo $okunmamis_mesaj_sayisi; ?></span>
                            <?php endif; ?>
                        </button>
                        <div class="dropdown-icerik">
                                <a onclick="profilAc('bilgiler')" style="cursor: pointer;">
                                    <svg width="14" height="14" style="margin-right:8px;"><use xlink:href="#icon-user"></use></svg>
                                    Profil &amp; Ayarlar
                                </a>
                            <a href="giris.php?islem=cikis">Çıkış Yap</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="giris.php?islem=giris" class="btn-giris">Giriş Yap</a>
                    <a href="giris.php?islem=kayit_sec" class="btn-turuncu-nav">Kayıt Ol</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
