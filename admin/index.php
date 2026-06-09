<?php
session_name('kariyer_admin');
    session_start();
if(!isset($_SESSION['kullaniciID']) || $_SESSION['krolID'] != 3) {
    header('Location: giris.php');
    exit;
}
require '../baglan.php';

// İstatistikleri Çek
$istatistikler = [];

// İlan istatistikleri
$istatistikler['toplam_ilan'] = $db->query("SELECT COUNT(*) FROM ilan")->fetchColumn();
$istatistikler['onay_bekleyen'] = $db->query("SELECT COUNT(*) FROM ilan WHERE idurumID = 3")->fetchColumn();
$istatistikler['aktif_ilan'] = $db->query("SELECT COUNT(*) FROM ilan WHERE idurumID = 1")->fetchColumn();
$istatistikler['bugunku_ilan'] = $db->query("SELECT COUNT(*) FROM ilan WHERE DATE(yayintarihi) = CURDATE()")->fetchColumn();

// Kullanıcı istatistikleri
$istatistikler['toplam_kullanici'] = $db->query("SELECT COUNT(*) FROM kullanici WHERE krolID != 3")->fetchColumn();
$istatistikler['is_arayan'] = $db->query("SELECT COUNT(*) FROM kullanici WHERE krolID = 1")->fetchColumn();
$istatistikler['isveren'] = $db->query("SELECT COUNT(*) FROM kullanici WHERE krolID = 2")->fetchColumn();

// Başvuru istatistikleri
$istatistikler['toplam_basvuru'] = $db->query("SELECT COUNT(*) FROM basvuru")->fetchColumn();

// Son İlanlar (5 adet)
$son_ilanlar = $db->query("
    SELECT i.*, v.firmaadi 
    FROM ilan i 
    JOIN isveren v ON i.iisverenID = v.isverenID 
    ORDER BY i.ilanID DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Son Başvurular (5 adet)
$son_basvurular = $db->query("
    SELECT b.*, ia.adsoyad, i.baslik 
    FROM basvuru b 
    JOIN isarayan ia ON b.bisarayanID = ia.isarayanID 
    JOIN ilan i ON b.bilanID = i.ilanID 
    ORDER BY b.basvuruID DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Dashboard';
include 'components/header.php';
?>

<div class="stat-cards">
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(59,130,246,0.1); color: #3b82f6;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?php echo $istatistikler['toplam_ilan']; ?></div>
            <div class="stat-label">Toplam İlan</div>
        </div>
    </div>
    
    <div class="stat-card" style="border-color: var(--warning);">
        <div class="stat-icon" style="background: rgba(245,158,11,0.1); color: var(--warning);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value" style="color: var(--warning);"><?php echo $istatistikler['onay_bekleyen']; ?></div>
            <div class="stat-label">Onay Bekleyen İlan</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(34,197,94,0.1); color: var(--success);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?php echo $istatistikler['toplam_kullanici']; ?></div>
            <div class="stat-label">Toplam Kullanıcı</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(168,85,247,0.1); color: #a855f7;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?php echo $istatistikler['toplam_basvuru']; ?></div>
            <div class="stat-label">Toplam Başvuru</div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 24px;">
    <!-- Son İlanlar -->
    <div class="admin-card">
        <div class="admin-card-header">
            <div class="admin-card-title">Son Eklenen İlanlar</div>
            <a href="ilanlar.php" class="admin-btn admin-btn-sm admin-btn-outline">Tümünü Gör</a>
        </div>
        <div class="admin-table-wrapper" style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Başlık</th>
                        <th>Firma</th>
                        <th>Tarih</th>
                        <th>Durum</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($son_ilanlar)): ?>
                    <tr><td colspan="4" style="text-align: center; color: var(--text-secondary);">Henüz ilan bulunmuyor.</td></tr>
                    <?php else: foreach($son_ilanlar as $ilan): 
                        $durum_renk = $ilan['idurumID'] == 1 ? 'success' : ($ilan['idurumID'] == 2 ? 'danger' : 'warning');
                        $durum_text = $ilan['idurumID'] == 1 ? 'Aktif' : ($ilan['idurumID'] == 2 ? 'Reddedildi' : 'Bekliyor');
                    ?>
                    <tr>
                        <td style="font-weight: 600;"><?php echo htmlspecialchars($ilan['baslik']); ?></td>
                        <td><?php echo htmlspecialchars($ilan['firmaadi']); ?></td>
                        <td><?php echo date("d.m.Y", strtotime($ilan['yayintarihi'])); ?></td>
                        <td><span class="badge badge-<?php echo $durum_renk; ?>"><?php echo $durum_text; ?></span></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Son Başvurular -->
    <div class="admin-card">
        <div class="admin-card-header">
            <div class="admin-card-title">Son Başvurular</div>
            <a href="basvurular.php" class="admin-btn admin-btn-sm admin-btn-outline">Tümünü Gör</a>
        </div>
        <div style="padding: 16px;">
            <?php if(empty($son_basvurular)): ?>
                <div style="text-align: center; color: var(--text-secondary); padding: 20px;">Henüz başvuru bulunmuyor.</div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <?php foreach($son_basvurular as $basvuru): ?>
                    <div style="display: flex; gap: 12px; align-items: flex-start; padding-bottom: 16px; border-bottom: 1px solid var(--border);">
                        <div style="width: 36px; height: 36px; background: rgba(34,197,94,0.1); color: var(--success); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        </div>
                        <div>
                            <div style="font-size: 14px; font-weight: 600;"><?php echo htmlspecialchars($basvuru['adsoyad']); ?></div>
                            <div style="font-size: 13px; color: var(--text-secondary); margin-top: 2px;">
                                <strong><?php echo htmlspecialchars($basvuru['baslik']); ?></strong> ilanına başvurdu.
                            </div>
                            <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;"><?php echo date("d.m.Y", strtotime($basvuru['tarih'])); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="stat-cards">
    <div class="stat-card" style="padding: 16px; gap: 12px;">
        <div class="stat-icon" style="width: 40px; height: 40px; background: rgba(59,130,246,0.1); color: #3b82f6;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value" style="font-size: 18px;"><?php echo $istatistikler['is_arayan']; ?></div>
            <div class="stat-label">İş Arayan</div>
        </div>
    </div>
    <div class="stat-card" style="padding: 16px; gap: 12px;">
        <div class="stat-icon" style="width: 40px; height: 40px; background: rgba(168,85,247,0.1); color: #a855f7;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value" style="font-size: 18px;"><?php echo $istatistikler['isveren']; ?></div>
            <div class="stat-label">İşveren</div>
        </div>
    </div>
    <div class="stat-card" style="padding: 16px; gap: 12px;">
        <div class="stat-icon" style="width: 40px; height: 40px; background: rgba(34,197,94,0.1); color: var(--success);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value" style="font-size: 18px;"><?php echo $istatistikler['aktif_ilan']; ?></div>
            <div class="stat-label">Aktif İlan</div>
        </div>
    </div>
    <div class="stat-card" style="padding: 16px; gap: 12px;">
        <div class="stat-icon" style="width: 40px; height: 40px; background: rgba(255,126,29,0.1); color: var(--primary);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value" style="font-size: 18px;"><?php echo $istatistikler['bugunku_ilan']; ?></div>
            <div class="stat-label">Bugünkü İlan</div>
        </div>
    </div>
</div>

</main>
</div>
</body>
</html>
