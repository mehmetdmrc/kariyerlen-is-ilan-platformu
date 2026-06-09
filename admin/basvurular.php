<?php
session_name('kariyer_admin');
    session_start();
if(!isset($_SESSION['kullaniciID']) || $_SESSION['krolID'] != 3) {
    header('Location: giris.php');
    exit;
}
require '../baglan.php';

$ara = $_GET['ara'] ?? '';
$params = [];

$sql = "
    SELECT b.*, ia.adsoyad, i.baslik, i.idurumID, v.firmaadi
    FROM basvuru b
    JOIN isarayan ia ON b.bisarayanID = ia.isarayanID
    JOIN ilan i ON b.bilanID = i.ilanID
    JOIN isveren v ON i.iisverenID = v.isverenID
    WHERE 1=1
";

if($ara != '') {
    $sql .= " AND (ia.adsoyad LIKE ? OR i.baslik LIKE ? OR v.firmaadi LIKE ?)";
    $params[] = "%$ara%";
    $params[] = "%$ara%";
    $params[] = "%$ara%";
}

$sql .= " ORDER BY b.basvuruID DESC";
$sorgu = $db->prepare($sql);
$sorgu->execute($params);
$basvurular = $sorgu->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Başvuru İzleme';
include 'components/header.php';
?>

<div class="admin-card">
    <div class="admin-card-header">
        <div class="admin-card-title">Tüm Başvurular (<?php echo count($basvurular); ?>)</div>
        <form method="GET" style="display: flex; gap: 8px;">
            <input type="text" name="ara" class="admin-input" placeholder="Aday, ilan veya firma ara..." value="<?php echo htmlspecialchars($ara); ?>" style="width: 250px;">
            <button type="submit" class="admin-btn admin-btn-primary">Ara</button>
        </form>
    </div>
    
    <div class="admin-table-wrapper" style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th>Aday (İş Arayan)</th>
                    <th>Başvurulan İlan</th>
                    <th>Firma (İşveren)</th>
                    <th>İlan Durumu</th>
                    <th>Başvuru Tarihi</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($basvurular)): ?>
                <tr><td colspan="6" style="text-align: center; color: var(--text-secondary); padding: 32px;">Kayıt bulunamadı.</td></tr>
                <?php else: foreach($basvurular as $b): 
                    $durum_renk = $b['idurumID'] == 1 ? 'success' : ($b['idurumID'] == 2 ? 'danger' : 'warning');
                    $durum_text = $b['idurumID'] == 1 ? 'Aktif' : ($b['idurumID'] == 2 ? 'Reddedildi' : 'Bekliyor');
                ?>
                <tr>
                    <td style="color: var(--text-secondary);">#<?php echo $b['basvuruID']; ?></td>
                    <td style="font-weight: 600; color: var(--primary);"><?php echo htmlspecialchars($b['adsoyad']); ?></td>
                    <td style="font-weight: 600;"><?php echo htmlspecialchars($b['baslik']); ?></td>
                    <td><?php echo htmlspecialchars($b['firmaadi']); ?></td>
                    <td><span class="badge badge-<?php echo $durum_renk; ?>"><?php echo $durum_text; ?></span></td>
                    <td><?php echo date("d.m.Y", strtotime($b['tarih'])); ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

</main>
</div>
</body>
</html>
