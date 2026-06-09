<?php
session_name('kariyer_admin');
    session_start();
if(!isset($_SESSION['kullaniciID']) || $_SESSION['krolID'] != 3) {
    header('Location: giris.php');
    exit;
}
require '../baglan.php';

$sekme = $_GET['sekme'] ?? 'tumu'; // tumu, isarayan, isveren

$kullanicilar = [];

if($sekme == 'tumu' || $sekme == 'isarayan') {
    $sorgu_arayan = $db->query("
        SELECT k.*, ia.adsoyad as ad, 'İş Arayan' as rol_text, 'info' as badge_color,
            (SELECT COUNT(*) FROM basvuru b JOIN isarayan ia2 ON b.bisarayanID = ia2.isarayanID WHERE ia2.akullaniciID = k.kullaniciID) as islem_sayisi
        FROM kullanici k 
        JOIN isarayan ia ON k.kullaniciID = ia.akullaniciID 
        WHERE k.krolID = 1
    ");
    $kullanicilar = array_merge($kullanicilar, $sorgu_arayan->fetchAll(PDO::FETCH_ASSOC));
}

if($sekme == 'tumu' || $sekme == 'isveren') {
    $sorgu_isveren = $db->query("
        SELECT k.*, iv.firmaadi as ad, 'İşveren' as rol_text, 'success' as badge_color,
            (SELECT COUNT(*) FROM ilan i JOIN isveren iv2 ON i.iisverenID = iv2.isverenID WHERE iv2.ikullaniciID = k.kullaniciID) as islem_sayisi
        FROM kullanici k 
        JOIN isveren iv ON k.kullaniciID = iv.ikullaniciID 
        WHERE k.krolID = 2
    ");
    $kullanicilar = array_merge($kullanicilar, $sorgu_isveren->fetchAll(PDO::FETCH_ASSOC));
}

// Tarihe göre sırala (en yeni üstte)
usort($kullanicilar, function($a, $b) {
    return strtotime($b['kayittarihi']) - strtotime($a['kayittarihi']);
});

$page_title = 'Kullanıcı Yönetimi';
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
        <div class="admin-card-title">Sistemdeki Kullanıcılar (<?php echo count($kullanicilar); ?>)</div>
        <div class="tab-nav">
            <a href="kullanicilar.php?sekme=tumu" class="tab-item <?php echo $sekme == 'tumu' ? 'active' : ''; ?>">Tümü</a>
            <a href="kullanicilar.php?sekme=isarayan" class="tab-item <?php echo $sekme == 'isarayan' ? 'active' : ''; ?>">İş Arayanlar</a>
            <a href="kullanicilar.php?sekme=isveren" class="tab-item <?php echo $sekme == 'isveren' ? 'active' : ''; ?>">İşverenler</a>
        </div>
    </div>
    
    <div class="admin-table-wrapper" style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th>Kullanıcı Adı/Firma</th>
                    <th>E-posta</th>
                    <th>Telefon</th>
                    <th>Rol</th>
                    <th>Kayıt Tarihi</th>
                    <th>Aktivite (İlan/Başvuru)</th>
                    <th style="text-align: right;">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($kullanicilar)): ?>
                <tr><td colspan="8" style="text-align: center; color: var(--text-secondary); padding: 32px;">Kayıt bulunamadı.</td></tr>
                <?php else: foreach($kullanicilar as $kul): ?>
                <tr id="kul_row_<?php echo $kul['kullaniciID']; ?>">
                    <td style="color: var(--text-secondary);">#<?php echo $kul['kullaniciID']; ?></td>
                    <td style="font-weight: 600;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <?php if($kul['fotograf']): ?>
                                <img src="../uploads/<?php echo $kul['fotograf']; ?>" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                            <?php else: ?>
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #64748b;">
                                    <?php echo mb_substr($kul['ad'], 0, 1); ?>
                                </div>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($kul['ad']); ?>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($kul['email']); ?></td>
                    <td><?php echo htmlspecialchars($kul['telno']); ?></td>
                    <td><span class="badge badge-<?php echo $kul['badge_color']; ?>"><?php echo $kul['rol_text']; ?></span></td>
                    <td><?php echo date("d.m.Y", strtotime($kul['kayittarihi'])); ?></td>
                    <td><strong><?php echo $kul['islem_sayisi']; ?></strong> adet</td>
                    <td style="text-align: right;">
                        <div style="display: flex; justify-content: flex-end; gap: 6px;">
                            <button onclick="kullaniciDuzenleModal(<?php echo $kul['kullaniciID']; ?>, <?php echo $kul['krolID']; ?>)" class="admin-btn admin-btn-sm admin-btn-outline">Düzenle</button>
                            <button onclick="kullaniciSilConfirm(<?php echo $kul['kullaniciID']; ?>)" class="admin-btn admin-btn-sm admin-btn-danger">Sil</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
async function kullaniciSilConfirm(id) {
    if(!confirm('Bu kullanıcıyı kalıcı olarak SİLMEK istediğinize emin misiniz? Bu işlem geri alınamaz ve kullanıcının tüm ilanları/başvuruları silinir!')) return;
    
    try {
        const formData = new FormData();
        formData.append('islem', 'kullanici_sil');
        formData.append('kullanici_id', id);
        
        const res = await fetch('admin_islem.php', { method: 'POST', body: formData });
        const data = await res.json();
        
        if(data.durum === 'basarili') {
            document.getElementById('kul_row_' + id).remove();
            alert('Kullanıcı başarıyla silindi.');
        } else {
            alert(data.hata);
        }
    } catch(e) {
        alert('Bir hata oluştu.');
    }
}

async function kullaniciDuzenleModal(id, krolID) {
    try {
        const formData = new FormData();
        formData.append('islem', 'kullanici_getir');
        formData.append('kullanici_id', id);
        
        const res = await fetch('admin_islem.php', { method: 'POST', body: formData });
        const data = await res.json();
        
        if(data.hata) {
            alert(data.hata);
            return;
        }
        
        document.getElementById('edit_kullanici_id').value = id;
        document.getElementById('edit_krol_id').value = krolID;
        
        // Ortak alanlar
        document.getElementById('edit_email').value = data.email || '';
        document.getElementById('edit_telno').value = data.telno || '';
        
        // Rol tabanlı alanları göster/gizle
        const isArayanAlanlar = document.getElementById('isarayan_alanlari');
        const isverenAlanlar = document.getElementById('isveren_alanlari');
        
        if(krolID == 1) { // İş arayan
            isArayanAlanlar.style.display = 'block';
            isverenAlanlar.style.display = 'none';
            document.getElementById('edit_adsoyad').value = data.adsoyad || '';
            document.getElementById('edit_dogumyili').value = data.dogumyili || '';
        } else if(krolID == 2) { // İşveren
            isArayanAlanlar.style.display = 'none';
            isverenAlanlar.style.display = 'block';
            document.getElementById('edit_firmaadi').value = data.firmaadi || '';
            document.getElementById('edit_vergino').value = data.vergino || '';
        }
        
        document.getElementById('kullaniciEditModal').style.display = 'flex';
        
    } catch(e) {
        alert('Kullanıcı bilgileri yüklenemedi.');
    }
}

async function kullaniciGuncelle() {
    const formData = new FormData(document.getElementById('kullaniciEditForm'));
    formData.append('islem', 'kullanici_guncelle');
    
    try {
        const res = await fetch('admin_islem.php', { method: 'POST', body: formData });
        const data = await res.json();
        
        if(data.durum === 'basarili') {
            alert('Kullanıcı bilgileri başarıyla güncellendi.');
            location.reload();
        } else {
            alert(data.hata || 'Bir hata oluştu.');
        }
    } catch(e) {
        alert('Sunucu ile iletişim kurulamadı.');
    }
}
</script>

<!-- Kullanıcı Düzenleme Modal -->
<div id="kullaniciEditModal" class="admin-modal-overlay">
    <div class="admin-modal">
        <div class="admin-modal-header">
            <h3 class="admin-card-title">Kullanıcı Düzenle</h3>
            <button onclick="document.getElementById('kullaniciEditModal').style.display='none'" style="background:none;border:none;font-size:24px;cursor:pointer;">&times;</button>
        </div>
        <div class="admin-modal-body">
            <form id="kullaniciEditForm" onsubmit="event.preventDefault(); kullaniciGuncelle();">
                <input type="hidden" name="kullanici_id" id="edit_kullanici_id">
                <input type="hidden" name="krol_id" id="edit_krol_id">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <label style="font-size: 13px; font-weight: 600; color: var(--text-secondary); margin-bottom: 4px; display: block;">E-posta</label>
                        <input type="email" name="email" id="edit_email" class="admin-input" required>
                    </div>
                    <div>
                        <label style="font-size: 13px; font-weight: 600; color: var(--text-secondary); margin-bottom: 4px; display: block;">Telefon</label>
                        <input type="text" name="telno" id="edit_telno" class="admin-input">
                    </div>
                </div>
                
                <hr style="border: 0; border-top: 1px solid var(--border); margin: 20px 0;">
                
                <!-- İş Arayan Alanları -->
                <div id="isarayan_alanlari" style="display: none;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div>
                            <label style="font-size: 13px; font-weight: 600; color: var(--text-secondary); margin-bottom: 4px; display: block;">Ad Soyad</label>
                            <input type="text" name="adsoyad" id="edit_adsoyad" class="admin-input">
                        </div>
                        <div>
                            <label style="font-size: 13px; font-weight: 600; color: var(--text-secondary); margin-bottom: 4px; display: block;">Doğum Yılı</label>
                            <input type="number" name="dogumyili" id="edit_dogumyili" class="admin-input" min="1950" max="2010">
                        </div>
                    </div>
                </div>
                
                <!-- İşveren Alanları -->
                <div id="isveren_alanlari" style="display: none;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div>
                            <label style="font-size: 13px; font-weight: 600; color: var(--text-secondary); margin-bottom: 4px; display: block;">Firma Adı</label>
                            <input type="text" name="firmaadi" id="edit_firmaadi" class="admin-input">
                        </div>
                        <div>
                            <label style="font-size: 13px; font-weight: 600; color: var(--text-secondary); margin-bottom: 4px; display: block;">Vergi No</label>
                            <input type="text" name="vergino" id="edit_vergino" class="admin-input">
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="admin-modal-footer">
            <button onclick="document.getElementById('kullaniciEditModal').style.display='none'" class="admin-btn admin-btn-outline">İptal</button>
            <button onclick="kullaniciGuncelle()" class="admin-btn admin-btn-primary">Kaydet</button>
        </div>
    </div>
</div>

</main>
</div>
</body>
</html>
