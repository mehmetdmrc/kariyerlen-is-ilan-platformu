<?php
session_name('kariyer_admin');
    session_start();
if(!isset($_SESSION['kullaniciID']) || $_SESSION['krolID'] != 3) {
    header('Location: giris.php');
    exit;
}
require '../baglan.php';

$page_title = 'Blog Yönetimi';
include 'components/header.php';

// Fetch blogs
$bloglar = $db->query("SELECT * FROM blog ORDER BY sira DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
    selector: '#blogIcerik',
    plugins: 'code link lists image',
    toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist | link image blockquote code',
    height: 450,
    menubar: false,
    branding: false,
    promotion: false,
    skin: 'oxide',
    images_upload_url: 'blog_islem.php?islem=editor_resim_yukle',
    automatic_uploads: true,
    file_picker_types: 'image',
    convert_urls: false,
    setup: function (editor) {
        editor.on('change', function () {
            tinymce.triggerSave();
        });
    }
});

tinymce.init({
    selector: '#blogOzet',
    plugins: 'code link lists',
    toolbar: 'bold italic underline | link bullist numlist | code',
    height: 150,
    menubar: false,
    branding: false,
    promotion: false,
    skin: 'oxide',
    convert_urls: false,
    setup: function (editor) {
        editor.on('change', function () {
            tinymce.triggerSave();
        });
    }
});
</script>

<div class="admin-card">
    <div class="admin-card-header">
        <div class="admin-card-title">Blog Yazıları</div>
        <button class="admin-btn admin-btn-primary" onclick="blogEkleModal()"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg> Yeni Ekle</button>
    </div>
    
    <div class="admin-table-wrapper" style="overflow-x: auto;">
        <table class="admin-table" id="blogTable">
            <thead>
                <tr>
                    <th style="width: 40px;"></th>
                    <th style="width: 50px;">ID</th>
                    <th style="width: 60px;">Sıra</th>
                    <th style="width: 80px;">Resim</th>
                    <th>Başlık</th>
                    <th>Kategori</th>
                    <th>Tarih</th>
                    <th style="text-align: right;">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($bloglar)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--text-secondary); padding: 30px;">Henüz blog yazısı bulunmuyor.</td>
                </tr>
                <?php else: foreach($bloglar as $b): ?>
                <tr id="row_<?php echo $b['id']; ?>">
                    <td style="cursor: grab; text-align: center; color: #94a3b8;" class="drag-handle" title="Sürükleyip Sırala">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                    </td>
                    <td><?php echo $b['id']; ?></td>
                    <td><span style="font-weight:700; color:#3b82f6;"><?php echo $b['sira']; ?></span></td>
                    <td>
                        <?php if($b['resim']): ?>
                            <img src="../<?php echo htmlspecialchars($b['resim']); ?>" alt="" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;">
                        <?php else: ?>
                            <div style="width: 50px; height: 50px; background: #eee; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #999; font-size: 10px;">Yok</div>
                        <?php endif; ?>
                    </td>
                    <td style="font-weight: 600;"><?php echo htmlspecialchars($b['baslik']); ?></td>
                    <td><span class="badge badge-info"><?php echo htmlspecialchars($b['kategori']); ?></span></td>
                    <td><?php echo date("d.m.Y", strtotime($b['tarih'])); ?></td>
                    <td style="text-align: right;">
                        <button class="admin-btn admin-btn-sm admin-btn-outline" onclick="blogDuzenle(<?php echo $b['id']; ?>)" style="padding: 6px 10px; color: #3b82f6; border-color: #3b82f6;">Düzenle</button>
                        <button class="admin-btn admin-btn-sm admin-btn-outline" onclick="blogSil(<?php echo $b['id']; ?>)" style="padding: 6px 10px; color: var(--danger); border-color: var(--danger); margin-left: 5px;">Sil</button>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
/* Blog Modal Premium Styles */
.blog-modal-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
}
.blog-modal-section {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
}
.blog-modal-section-title {
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    border-bottom: 2px solid #e2e8f0;
    padding-bottom: 12px;
}
.seo-section {
    background: #f0fdf4;
    border-color: #bbf7d0;
}
.seo-section .blog-modal-section-title {
    color: #166534;
    border-color: #bbf7d0;
}
.image-upload-wrapper {
    position: relative;
    border: 2px dashed #cbd5e1;
    border-radius: 8px;
    padding: 30px 20px;
    text-align: center;
    background: #fff;
    cursor: pointer;
    transition: 0.2s;
}
.image-upload-wrapper:hover {
    border-color: #3b82f6;
    background: #eff6ff;
}
.modern-label {
    font-weight: 600;
    font-size: 13px;
    color: #475569;
    margin-bottom: 8px;
    display: block;
}
.modern-input {
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    padding: 10px 14px;
    width: 100%;
    font-size: 14px;
    transition: all 0.2s;
}
.modern-input:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
    outline: none;
}
@media (max-width: 768px) {
    .blog-modal-grid { grid-template-columns: 1fr; }
}
.modal-arkaplan {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    display: none; /* hidden by default */
    z-index: 9990;
    overflow-y: auto;
    padding: 20px;
}
.modal-icerik {
    position: relative;
    margin: auto;
    width: 100%;
    animation: modalSlide 0.3s ease-out;
}
@keyframes modalSlide {
    from { transform: translateY(-20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
/* TinyMCE Modals z-index fix */
.tox-tinymce-aux {
    z-index: 99999 !important;
}
</style>

<!-- Blog Ekle/Düzenle Modal -->
<div id="blogModal" class="modal-arkaplan" onclick="if(event.target===this) modalKapat('blogModal')">
    <div class="modal-icerik buyuk" style="background:#fff; border-radius:16px; max-width:1100px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
        <div class="modal-header-tabs" style="padding: 24px 30px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #fff; border-radius: 16px 16px 0 0;">
            <h3 id="blogModalTitle" style="margin:0; font-size: 20px; font-weight: 800; color: #1e293b;">Yeni Blog Ekle</h3>
            <button class="kapat-btn" style="position:static; background:#f1f5f9; color:#64748b;" onclick="modalKapat('blogModal')">×</button>
        </div>
        <div class="modal-body" style="padding: 30px; background: #fdfdfd;">
            <form id="blogForm" onsubmit="blogKaydet(event)">
                <input type="hidden" name="islem" id="blogIslem" value="blog_kaydet">
                <input type="hidden" name="blog_id" id="blogId" value="">
                
                <div class="blog-modal-grid">
                    <!-- Sol Taraf -->
                    <div>
                        <div class="blog-modal-section" style="background:#fff;">
                            <div class="blog-modal-section-title">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                İçerik Detayları
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label class="modern-label">Başlık *</label>
                                <input type="text" name="baslik" id="blogBaslik" class="modern-input" required>
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label class="modern-label">Özet (Listede görünecek kısa metin) *</label>
                                <textarea name="ozet" id="blogOzet" class="modern-input" rows="3" required></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label class="modern-label">İçerik Editörü *</label>
                                <textarea name="icerik" id="blogIcerik" class="modern-input" rows="8"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sağ Taraf -->
                    <div>
                        <div class="blog-modal-section" style="background:#fff;">
                            <div class="blog-modal-section-title">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                Yayın Ayarları
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label class="modern-label">Kategori *</label>
                                <input type="text" name="kategori" id="blogKategori" class="modern-input" required placeholder="Örn: Haberin Olsun">
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label class="modern-label">Sıralama (Büyük sayı üstte çıkar) *</label>
                                <input type="number" name="sira" id="blogSira" class="modern-input" value="0" required>
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label class="modern-label">Yayın Tarihi *</label>
                                <input type="date" name="tarih" id="blogTarih" class="modern-input" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="modern-label">Kapak Resmi</label>
                                <div class="image-upload-wrapper">
                                    <input type="file" name="resim" id="blogResim" accept="image/*" style="opacity:0; position:absolute; inset:0; width:100%; height:100%; cursor:pointer; z-index:2;">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin-bottom:12px;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                                    <div style="font-size:14px; color:#64748b; font-weight:600;">Tıklayın veya sürükleyin</div>
                                    <div style="font-size:12px; color:#94a3b8; margin-top:4px;">PNG, JPG, JPEG</div>
                                </div>
                                <div id="mevcutResim" style="margin-top: 16px; display: none;">
                                    <img src="" id="mevcutResimImg" style="width: 100%; height: 160px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                                    <div style="text-align: center; font-size: 12px; color: #64748b; margin-top: 6px; font-weight: 500;">Mevcut Resim</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="blog-modal-section seo-section">
                            <div class="blog-modal-section-title">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                SEO Optimizasyonu
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 16px;">
                                <label style="font-weight:600; font-size:12px; color:#166534; margin-bottom:6px; display:block;">Meta Title</label>
                                <input type="text" name="meta_title" id="blogMetaTitle" class="modern-input" placeholder="Arama motoru başlığı" style="border-color:#bbf7d0; background:#fff;">
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 16px;">
                                <label style="font-weight:600; font-size:12px; color:#166534; margin-bottom:6px; display:block;">Meta Description</label>
                                <textarea name="meta_description" id="blogMetaDesc" class="modern-input" rows="3" placeholder="Sayfa açıklaması" style="border-color:#bbf7d0; background:#fff;"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label style="font-weight:600; font-size:12px; color:#166534; margin-bottom:6px; display:block;">Meta Keywords</label>
                                <input type="text" name="meta_keywords" id="blogMetaKey" class="modern-input" placeholder="Örn: iş, kariyer (virgülle ayırın)" style="border-color:#bbf7d0; background:#fff;">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 10px; padding-top: 24px; border-top: 1px solid #e2e8f0;">
                    <button type="button" class="admin-btn admin-btn-outline" onclick="modalKapat('blogModal')" style="padding: 12px 28px; border-radius: 8px; font-weight: 700; font-size: 14px;">İptal Et</button>
                    <button type="submit" class="admin-btn admin-btn-primary" id="blogKaydetBtn" style="padding: 12px 28px; border-radius: 8px; font-weight: 700; font-size: 14px; box-shadow: 0 4px 12px rgba(59,130,246,0.3);">Bloğu Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const resimInput = document.getElementById('blogResim');
    if(resimInput) {
        resimInput.addEventListener('change', function(e) {
            if(this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('mevcutResim').style.display = 'block';
                    document.getElementById('mevcutResimImg').src = e.target.result;
                }
                reader.readAsDataURL(this.files[0]);
            }
        });
    }

    const tbody = document.querySelector('#blogTable tbody');
    if(tbody) {
        new Sortable(tbody, {
            animation: 150,
            handle: '.drag-handle',
            onEnd: function (evt) {
                const rows = tbody.querySelectorAll('tr');
                const order = [];
                rows.forEach(row => {
                    if(row.id && row.id.startsWith('row_')) {
                        order.push(row.id.replace('row_', ''));
                    }
                });
                
                if(order.length > 0) {
                    fetch('blog_islem.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'islem=blog_sirala&order=' + encodeURIComponent(JSON.stringify(order))
                    })
                    .then(r => r.text())
                    .then(text => {
                        const res = parseSafeJSON(text);
                        if(res.durum == 'basarili') {
                            window.location.reload(); // Reload to show the new updated 'Sıra' numbers on screen
                        } else {
                            alert('Sıralama güncellenirken bir hata oluştu.');
                        }
                    })
                    .catch(e => console.error(e));
                }
            }
        });
    }
});

function modalAc(id) {
    const el = document.getElementById(id);
    if(el) {
        el.style.display = 'flex';
        el.style.alignItems = 'center';
        el.style.justifyContent = 'center';
    }
}
function modalKapat(id) {
    const el = document.getElementById(id);
    if(el) el.style.display = 'none';
}

function parseSafeJSON(text) {
    const match = text.match(/\{[\s\S]*\}|\[[\s\S]*\]/);
    if(match) {
        try {
            return JSON.parse(match[0]);
        } catch(e) {
            console.error('Safe JSON Parse Error:', e, text);
            throw e;
        }
    }
    return JSON.parse(text);
}

function blogEkleModal() {
    document.getElementById('blogForm').reset();
    document.getElementById('blogId').value = '';
    document.getElementById('blogSira').value = '0';
    document.getElementById('blogModalTitle').innerText = 'Yeni Blog Ekle';
    document.getElementById('mevcutResim').style.display = 'none';
    document.getElementById('blogTarih').valueAsDate = new Date();
    if(tinymce.get('blogIcerik')) tinymce.get('blogIcerik').setContent('');
    if(tinymce.get('blogOzet')) tinymce.get('blogOzet').setContent('');
    modalAc('blogModal');
}

function blogDuzenle(id) {
    fetch('blog_islem.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'islem=blog_getir&id=' + id
    })
    .then(r => r.text())
    .then(text => {
        const res = parseSafeJSON(text);
        if(res.hata) {
            alert(res.hata);
            return;
        }
        
        document.getElementById('blogForm').reset();
        document.getElementById('blogModalTitle').innerText = 'Blog Düzenle';
        document.getElementById('blogId').value = res.id;
        document.getElementById('blogSira').value = res.sira || 0;
        document.getElementById('blogBaslik').value = res.baslik;
        document.getElementById('blogKategori').value = res.kategori;
        document.getElementById('blogTarih').value = res.tarih;
        document.getElementById('blogOzet').value = res.ozet;
        if(tinymce.get('blogOzet')) tinymce.get('blogOzet').setContent(res.ozet || '');
        document.getElementById('blogIcerik').value = res.icerik;
        if(tinymce.get('blogIcerik')) tinymce.get('blogIcerik').setContent(res.icerik || '');
        document.getElementById('blogMetaTitle').value = res.meta_title || '';
        document.getElementById('blogMetaDesc').value = res.meta_description || '';
        document.getElementById('blogMetaKey').value = res.meta_keywords || '';
        
        if(res.resim) {
            document.getElementById('mevcutResim').style.display = 'block';
            document.getElementById('mevcutResimImg').src = '../' + res.resim;
        } else {
            document.getElementById('mevcutResim').style.display = 'none';
        }
        
        modalAc('blogModal');
    })
    .catch(e => {
        alert('Bir hata oluştu!');
        console.error(e);
    });
}

function blogSil(id) {
    if(confirm('Bu blog yazısını silmek istediğinize emin misiniz?')) {
        fetch('blog_islem.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'islem=blog_sil&id=' + id
        })
        .then(r => r.text())
        .then(text => {
            const res = parseSafeJSON(text);
            if(res.durum == 'basarili') {
                const row = document.getElementById('row_' + id);
                if(row) row.remove();
            } else {
                alert(res.hata || 'Silme başarısız!');
            }
        });
    }
}

function blogKaydet(e) {
    e.preventDefault();
    tinymce.triggerSave();
    const btn = document.getElementById('blogKaydetBtn');
    btn.disabled = true;
    btn.innerText = 'Kaydediliyor...';
    
    const form = document.getElementById('blogForm');
    const formData = new FormData(form);
    
    fetch('blog_islem.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.text())
    .then(text => {
        const res = parseSafeJSON(text);
        btn.disabled = false;
        btn.innerText = 'Kaydet';
        
        if(res.durum == 'basarili') {
            modalKapat('blogModal');
            window.location.reload(); // Reload to see changes easily for now
        } else {
            alert(res.hata || 'Kayıt başarısız!');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerText = 'Kaydet';
        alert('Sunucu hatası oluştu!');
        console.error(err);
    });
}
</script>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

</main>
</div>
</body>
</html>
