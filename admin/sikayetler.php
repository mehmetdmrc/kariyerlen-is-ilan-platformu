<?php
session_name('kariyer_admin');
    session_start();
if(!isset($_SESSION['kullaniciID']) || $_SESSION['krolID'] != 3) {
    header('Location: giris.php');
    exit;
}
require '../baglan.php';

// Filtreleme
$durum = $_GET['durum'] ?? 'bekleyen';

$sql = "SELECT s.*, i.baslik as ilan_baslik, v.firmaadi, u.email as sikayet_eden_eposta 
        FROM sikayet s 
        JOIN ilan i ON s.ilanID = i.ilanID 
        JOIN isveren v ON i.iisverenID = v.isverenID 
        LEFT JOIN kullanici u ON s.kullaniciID = u.kullaniciID
        WHERE 1=1";

if($durum == 'bekleyen') { $sql .= " AND s.durum = 0"; }
elseif($durum == 'incelendi') { $sql .= " AND s.durum = 1"; }

$sql .= " ORDER BY s.sikayetID DESC";
$sorgu = $db->query($sql);
$sikayetler = $sorgu->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'İlan Şikayetleri';
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
    text-decoration: none;
    color: var(--text-light);
    font-weight: 600;
    transition: 0.2s;
}
.tab-item:hover {
    background: var(--bg-hover);
}
.tab-item.active {
    background: var(--primary);
    color: white;
}
.grid-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}
.grid-table th, .grid-table td {
    padding: 16px;
    text-align: left;
    border-bottom: 1px solid var(--border);
}
.grid-table th {
    background: #f8fafc;
    font-weight: 700;
    color: var(--text-light);
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.5px;
}
.grid-table tr:last-child td { border-bottom: none; }
.btn-sm {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.btn-success { background: #10b981; color: white; }
.btn-primary { background: var(--primary); color: white; }
.btn-danger { background: #ef4444; color: white; }

.neden-box {
    background: #fef2f2;
    color: #991b1b;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 13px;
    line-height: 1.5;
    margin-top: 4px;
    border-left: 3px solid #ef4444;
}
</style>

<div class="tab-nav">
    <a href="?durum=bekleyen" class="tab-item <?php echo $durum == 'bekleyen' ? 'active' : ''; ?>">Yeni Şikayetler</a>
    <a href="?durum=incelendi" class="tab-item <?php echo $durum == 'incelendi' ? 'active' : ''; ?>">İncelenmiş Olanlar</a>
</div>

<div style="overflow-x: auto;">
    <table class="grid-table">
        <thead>
            <tr>
                <th>İlan / Firma</th>
                <th>Şikayet Detayı</th>
                <th>Şikayet Eden</th>
                <th>Tarih</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($sikayetler) == 0): ?>
            <tr>
                <td colspan="5" style="text-align:center; padding: 32px; color:var(--text-light);">
                    Bu kategoride şikayet bulunmuyor.
                </td>
            </tr>
            <?php endif; ?>
            
            <?php foreach($sikayetler as $s): ?>
            <tr>
                <td>
                    <div style="font-weight:700; color:var(--text-dark); margin-bottom:4px;"><?php echo htmlspecialchars($s['ilan_baslik']); ?></div>
                    <div style="font-size:13px; color:var(--text-light);"><?php echo htmlspecialchars($s['firmaadi']); ?></div>
                </td>
                <td style="max-width: 300px;">
                    <div class="neden-box">
                        <?php echo nl2br(htmlspecialchars($s['neden'])); ?>
                    </div>
                </td>
                <td>
                    <div style="font-size:13px;">
                        <?php echo $s['sikayet_eden_eposta'] ? htmlspecialchars($s['sikayet_eden_eposta']) : 'Ziyaretçi / Bilinmiyor'; ?>
                    </div>
                </td>
                <td>
                    <div style="font-size:13px; color:var(--text-light);">
                        <?php echo date('d.m.Y H:i', strtotime($s['tarih'])); ?>
                    </div>
                </td>
                <td>
                    <div style="display:flex; gap:6px; flex-wrap:wrap;">
                        <a href="../is_ilanlari.php?id=<?php echo $s['ilanID']; ?>" target="_blank" class="btn-sm btn-primary" title="İlanı Görüntüle">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            Görüntüle
                        </a>
                        
                        <?php if($s['durum'] == 0): ?>
                        <button onclick="sikayetIslem(<?php echo $s['sikayetID']; ?>, 'incelendi_isaretle')" class="btn-sm btn-success">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            İncelendi
                        </button>
                        <?php else: ?>
                        <button onclick="sikayetIslem(<?php echo $s['sikayetID']; ?>, 'bekliyor_isaretle')" class="btn-sm" style="background:#e2e8f0; color:#475569;">
                            Geri Al
                        </button>
                        <?php endif; ?>
                        
                        <button onclick="sikayetIslem(<?php echo $s['sikayetID']; ?>, 'sikayet_sil')" class="btn-sm btn-danger" title="Şikayeti Kalıcı Olarak Sil">
                            Sil
                        </button>
                        
                        <button onclick="ilanSilConfirm(<?php echo $s['ilanID']; ?>)" class="btn-sm" style="background:#ef4444; color:white; margin-left: 8px;" title="İlanı Kalıcı Olarak Kaldır">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                            İlanı Kaldır
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
async function ilanSilConfirm(ilan_id) {
    if(!confirm('Bu ilanı kalıcı olarak SİLMEK istediğinize emin misiniz? (Bu işlem geri alınamaz ve ilan tamamen kaldırılır)')) return;
    
    try {
        const formData = new FormData();
        formData.append('islem', 'ilan_sil');
        formData.append('ilan_id', ilan_id);
        
        const res = await fetch('admin_islem.php', { method: 'POST', body: formData });
        const data = await res.json();
        
        if(data.durum === 'basarili') {
            alert('İlan başarıyla silindi.');
            window.location.reload();
        } else {
            alert(data.hata || 'İlan silinirken bir hata oluştu.');
        }
    } catch(e) {
        alert('Bağlantı hatası.');
    }
}

function sikayetIslem(id, islemTur) {
    let mesaj = '';
    if(islemTur === 'incelendi_isaretle') mesaj = 'Bu şikayeti incelendi olarak işaretlemek istediğinize emin misiniz?';
    if(islemTur === 'bekliyor_isaretle') mesaj = 'Bu şikayeti tekrar bekliyor durumuna almak istediğinize emin misiniz?';
    if(islemTur === 'sikayet_sil') mesaj = 'Bu şikayet kaydını kalıcı olarak silmek istediğinize emin misiniz?';
    
    if(confirm(mesaj)) {
        const fd = new FormData();
        fd.append('islem', islemTur);
        fd.append('sikayet_id', id);
        
        fetch('admin_islem.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if(d.durum === 'basarili') {
                    window.location.reload();
                } else {
                    alert(d.hata || 'Bir hata oluştu');
                }
            })
            .catch(err => {
                console.error(err);
                alert('Bağlantı hatası');
            });
    }
}
</script>

</main>
</div>
</body>
</html>
