<?php
session_name('kariyer_admin');
    session_start();
if(!isset($_SESSION['kullaniciID']) || $_SESSION['krolID'] != 3) {
    header('Location: giris.php');
    exit;
}
require '../baglan.php';

// Filtreleme
$durum = $_GET['durum'] ?? 'bekleyen'; // Varsayılan olarak bekleyenleri göster
$ara = $_GET['ara'] ?? '';

$sql = "SELECT i.*, v.firmaadi, s.sektorad 
        FROM ilan i 
        JOIN isveren v ON i.iisverenID = v.isverenID 
        LEFT JOIN sektor s ON i.isektorID = s.sektorID
        WHERE 1=1";

$params = [];

if($durum == 'bekleyen') { $sql .= " AND i.idurumID = 3"; }
elseif($durum == 'aktif') { $sql .= " AND i.idurumID = 1"; }
elseif($durum == 'reddedilen') { $sql .= " AND i.idurumID = 2"; }

if($ara != '') {
    $sql .= " AND (i.baslik LIKE ? OR v.firmaadi LIKE ?)";
    $params[] = "%$ara%";
    $params[] = "%$ara%";
}

$sql .= " ORDER BY i.ilanID DESC";
$sorgu = $db->prepare($sql);
$sorgu->execute($params);
$ilanlar = $sorgu->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'İlan Yönetimi';
include 'components/header.php';
?>

<style>
.tab-nav {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
    border-bottom: 1px solid var(--border);
    padding-bottom: 12px;
}
.tab-item {
    padding: 8px 16px;
    border-radius: 8px;
    color: var(--text-secondary);
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
    transition: all 0.2s;
}
.tab-item:hover { background: #f1f5f9; }
.tab-item.active {
    background: var(--primary);
    color: #fff;
}
</style>

<div class="admin-card">
    <div class="admin-card-header" style="flex-direction: column; align-items: stretch; gap: 16px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div class="admin-card-title">İlan Listesi (<?php echo count($ilanlar); ?> İlan)</div>
            <form method="GET" style="display: flex; gap: 8px;">
                <input type="hidden" name="durum" value="<?php echo htmlspecialchars($durum); ?>">
                <input type="text" name="ara" class="admin-input" placeholder="İlan veya firma ara..." value="<?php echo htmlspecialchars($ara); ?>" style="width: 250px;">
                <button type="submit" class="admin-btn admin-btn-primary">Ara</button>
            </form>
        </div>
        
        <div class="tab-nav">
            <a href="ilanlar.php?durum=tumu" class="tab-item <?php echo $durum == 'tumu' ? 'active' : ''; ?>">Tümü</a>
            <a href="ilanlar.php?durum=bekleyen" class="tab-item <?php echo $durum == 'bekleyen' ? 'active' : ''; ?>">Onay Bekleyen</a>
            <a href="ilanlar.php?durum=aktif" class="tab-item <?php echo $durum == 'aktif' ? 'active' : ''; ?>">Aktif İlanlar</a>
            <a href="ilanlar.php?durum=reddedilen" class="tab-item <?php echo $durum == 'reddedilen' ? 'active' : ''; ?>">Reddedilenler</a>
        </div>
    </div>
    
    <div class="admin-table-wrapper" style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th>Başlık</th>
                    <th>Firma</th>
                    <th>Tarih</th>
                    <th>Durum</th>
                    <th style="text-align: right;">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($ilanlar)): ?>
                <tr><td colspan="6" style="text-align: center; color: var(--text-secondary); padding: 32px;">Kayıt bulunamadı.</td></tr>
                <?php else: foreach($ilanlar as $ilan): 
                    $durum_renk = $ilan['idurumID'] == 1 ? 'success' : ($ilan['idurumID'] == 2 ? 'danger' : 'warning');
                    $durum_text = $ilan['idurumID'] == 1 ? 'Aktif' : ($ilan['idurumID'] == 2 ? 'Reddedildi' : 'Bekliyor');
                ?>
                <tr id="ilan_row_<?php echo $ilan['ilanID']; ?>">
                    <td style="color: var(--text-secondary);">#<?php echo $ilan['ilanID']; ?></td>
                    <td style="font-weight: 600;"><?php echo htmlspecialchars($ilan['baslik']); ?></td>
                    <td><?php echo htmlspecialchars($ilan['firmaadi']); ?></td>
                    <td><?php echo date("d.m.Y", strtotime($ilan['yayintarihi'])); ?></td>
                    <td><span class="badge badge-<?php echo $durum_renk; ?>"><?php echo $durum_text; ?></span></td>
                    <td style="text-align: right;">
                        <div style="display: flex; justify-content: flex-end; gap: 6px;">
                            <button onclick="ilanDetay(<?php echo $ilan['ilanID']; ?>)" class="admin-btn admin-btn-sm admin-btn-outline">Detay</button>
                            
                            <?php if($ilan['idurumID'] == 3 || $ilan['idurumID'] == 2): ?>
                            <button onclick="ilanOnayla(<?php echo $ilan['ilanID']; ?>)" class="admin-btn admin-btn-sm admin-btn-success">Onayla</button>
                            <?php endif; ?>
                            
                            <?php if($ilan['idurumID'] == 3 || $ilan['idurumID'] == 1): ?>
                            <button onclick="ilanReddetModal(<?php echo $ilan['ilanID']; ?>)" class="admin-btn admin-btn-sm admin-btn-warning">Reddet</button>
                            <?php endif; ?>
                            
                            <button onclick="ilanSilConfirm(<?php echo $ilan['ilanID']; ?>)" class="admin-btn admin-btn-sm admin-btn-danger">Sil</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Detay Modal -->
<div id="detayModal" class="admin-modal-overlay">
    <div class="admin-modal">
        <div class="admin-modal-header">
            <h3 class="admin-card-title" id="d_baslik">İlan Detayı</h3>
            <button onclick="document.getElementById('detayModal').style.display='none'" style="background:none;border:none;font-size:24px;cursor:pointer;">×</button>
        </div>
        <div class="admin-modal-body" id="detayIcerik">
            <!-- Ajax ile dolacak -->
        </div>
        <div class="admin-modal-footer">
            <button onclick="document.getElementById('detayModal').style.display='none'" class="admin-btn admin-btn-outline">Kapat</button>
        </div>
    </div>
</div>

<!-- Reddetme Modal -->
<div id="redModal" class="admin-modal-overlay">
    <div class="admin-modal" style="max-width: 400px;">
        <div class="admin-modal-header">
            <h3 class="admin-card-title">İlanı Reddet</h3>
            <button onclick="document.getElementById('redModal').style.display='none'" style="background:none;border:none;font-size:24px;cursor:pointer;">×</button>
        </div>
        <div class="admin-modal-body">
            <input type="hidden" id="red_ilan_id">
            <label style="font-weight: 600; font-size: 14px; margin-bottom: 8px; display: block;">Reddetme Nedeni (İşverene Gösterilecektir)</label>
            <textarea id="red_nedeni" class="admin-input" rows="4" placeholder="Lütfen ilan içeriğinde şu düzeltmeleri yapınız..."></textarea>
        </div>
        <div class="admin-modal-footer">
            <button onclick="document.getElementById('redModal').style.display='none'" class="admin-btn admin-btn-outline">İptal</button>
            <button onclick="ilanReddetGonder()" class="admin-btn admin-btn-danger">İlanı Reddet</button>
        </div>
    </div>
</div>

<script>
async function ilanOnayla(id) {
    if(!confirm('Bu ilanı onaylamak ve yayınlamak istediğinize emin misiniz?')) return;
    
    try {
        const formData = new FormData();
        formData.append('islem', 'ilan_onayla');
        formData.append('ilan_id', id);
        
        const res = await fetch('admin_islem.php', { method: 'POST', body: formData });
        const data = await res.json();
        
        if(data.durum === 'basarili') {
            alert('İlan başarıyla onaylandı.');
            location.reload();
        } else {
            alert(data.hata);
        }
    } catch(e) {
        alert('Bir hata oluştu.');
    }
}

function ilanReddetModal(id) {
    document.getElementById('red_ilan_id').value = id;
    document.getElementById('red_nedeni').value = '';
    document.getElementById('redModal').style.display = 'flex';
}

async function ilanReddetGonder() {
    const id = document.getElementById('red_ilan_id').value;
    const neden = document.getElementById('red_nedeni').value;
    
    if(!neden) { alert('Lütfen bir reddetme nedeni girin.'); return; }
    
    try {
        const formData = new FormData();
        formData.append('islem', 'ilan_reddet');
        formData.append('ilan_id', id);
        formData.append('red_nedeni', neden);
        
        const res = await fetch('admin_islem.php', { method: 'POST', body: formData });
        const data = await res.json();
        
        if(data.durum === 'basarili') {
            alert('İlan reddedildi.');
            location.reload();
        } else {
            alert(data.hata);
        }
    } catch(e) {
        alert('Bir hata oluştu.');
    }
}

async function ilanSilConfirm(id) {
    if(!confirm('Bu ilanı kalıcı olarak SİLMEK istediğinize emin misiniz? Bu işlem geri alınamaz!')) return;
    
    try {
        const formData = new FormData();
        formData.append('islem', 'ilan_sil');
        formData.append('ilan_id', id);
        
        const res = await fetch('admin_islem.php', { method: 'POST', body: formData });
        const data = await res.json();
        
        if(data.durum === 'basarili') {
            document.getElementById('ilan_row_' + id).remove();
            alert('İlan başarıyla silindi.');
        } else {
            alert(data.hata);
        }
    } catch(e) {
        alert('Bir hata oluştu.');
    }
}

async function ilanDetay(id) {
    try {
        const formData = new FormData();
        formData.append('islem', 'ilan_detay');
        formData.append('ilan_id', id);
        
        const res = await fetch('admin_islem.php', { method: 'POST', body: formData });
        const data = await res.json();
        
        if(data.hata) {
            alert(data.hata);
            return;
        }
        
        document.getElementById('d_baslik').innerText = data.baslik;
        
        let redNedeniHtml = '';
        if(data.idurumID == 2 && data.red_nedeni) {
            redNedeniHtml = `
            <div style="background: #fee2e2; border-left: 4px solid #ef4444; padding: 12px; margin-bottom: 16px; border-radius: 4px;">
                <strong style="color: #991b1b;">Reddetme Nedeni:</strong><br>
                <span style="color: #7f1d1d;">${data.red_nedeni}</span>
            </div>`;
        }
        
        const html = `
            ${redNedeniHtml}
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; padding: 16px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 14px;">
                <div><strong>Firma:</strong> <span style="color: var(--text-secondary);">${data.firmaadi}</span></div>
                <div><strong>Sektör:</strong> <span style="color: var(--text-secondary);">${data.sektorad || '-'}</span></div>
                <div><strong>Çalışma Türü:</strong> <span style="color: var(--text-secondary);">${data.calismatur || '-'}</span></div>
                <div><strong>Maaş:</strong> <span style="color: var(--text-secondary);">${data.maas_formatli}</span></div>
                <div><strong>Çalışma Günleri:</strong> <span style="color: var(--text-secondary);">${data.calismagunleri || '-'}</span></div>
                <div><strong>Çalışma Saatleri:</strong> <span style="color: var(--text-secondary);">${data.calismasaatleri || '-'}</span></div>
                <div><strong>Yan Haklar:</strong> <span style="color: var(--text-secondary);">${data.yanhak || '-'}</span></div>
                <div><strong>Yayın Tarihi:</strong> <span style="color: var(--text-secondary);">${data.yayintarihi_formatli}</span></div>
                <div style="grid-column: 1 / -1;"><strong>Açık Adres:</strong> <span style="color: var(--text-secondary);">${data.acikadres || '-'}</span></div>
            </div>
            <div>
                <strong>İlan Açıklaması:</strong><br>
                <div style="margin-top: 8px; background: #ffffff; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 14px; line-height: 1.6;">
                    ${data.aciklama_formatli}
                </div>
            </div>
        `;
        
        document.getElementById('detayIcerik').innerHTML = html;
        document.getElementById('detayModal').style.display = 'flex';
        
    } catch(e) {
        alert('İlan detayları yüklenemedi.');
    }
}
</script>

</main>
</div>
</body>
</html>
