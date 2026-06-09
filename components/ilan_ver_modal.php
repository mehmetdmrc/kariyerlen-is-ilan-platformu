<?php 
/**
 * İlan Ver Modal Bileşeni
 * İşveren (krolID=2) olarak giriş yapılmışsa ilan ekleme formu gösterilir.
 * Bu dosya header.php'den sonra, </body>'den önce dahil edilmelidir.
 * Gerekli değişkenler: $sektorler, $calismaturleri, $yanhaklar, $iller
 */
if(isset($_SESSION['kullaniciID']) && $_SESSION['krolID'] == 2):

// Veriler yoksa çek
if(!isset($sektorler) || empty($sektorler)) {
    $sektorler = $db->query("SELECT * FROM sektor ORDER BY sektorad ASC")->fetchAll(PDO::FETCH_ASSOC);
}
if(!isset($calismaturleri) || empty($calismaturleri)) {
    $calismaturleri = $db->query("SELECT * FROM calismaturu ORDER BY calismatur ASC")->fetchAll(PDO::FETCH_ASSOC);
}
if(!isset($yanhaklar) || empty($yanhaklar)) {
    $yanhaklar = $db->query("SELECT * FROM yanhaklar ORDER BY yanhak ASC")->fetchAll(PDO::FETCH_ASSOC);
}
if(!isset($iller) || empty($iller)) {
    $iller = $db->query("SELECT * FROM il ORDER BY ilisim ASC")->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!-- İlan Ver Modal -->
<div id="ilanVerModal" class="modal-arkaplan">
    <div class="modal-icerik buyuk" style="max-width:780px;">
        <button class="kapat-btn" onclick="modalKapat('ilanVerModal')">×</button>
        <div class="modal-body" style="padding:28px; max-height:85vh; overflow-y:auto;">
            
            <!-- Header -->
            <div style="display:flex; align-items:center; gap:14px; margin-bottom:24px; padding-bottom:18px; border-bottom:1px solid var(--border);">
                <div style="width:48px; height:48px; background:linear-gradient(135deg,#ff7e1d,#ea580c); border-radius:14px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                </div>
                <div>
                    <h2 style="margin:0; font-size:20px; font-weight:800; color:var(--text-main);">Yeni İlan Oluştur</h2>
                    <p style="margin:2px 0 0; font-size:13px; color:var(--text-muted);">Tüm alanları doldurarak ilanınızı yayına alın.</p>
                </div>
            </div>

            <form id="ilanVerForm" onsubmit="return ilanVerFormGonder(event)">
                <input type="hidden" name="islem" value="ilan_ekle">

                <!-- İlan Detayları -->
                <div class="profil-kart" style="margin-bottom:16px;">
                    <div class="profil-kart-baslik">
                        <svg width="18" height="18"><use xlink:href="#icon-edit"></use></svg>
                        <h3>İlan Detayları</h3>
                    </div>
                    <div class="profil-kart-govde">
                        <div class="profil-input-satir">
                            <label><svg width="14" height="14"><use xlink:href="#icon-briefcase"></use></svg> İLAN BAŞLIĞI</label>
                            <div class="profil-input-grup">
                                <input type="text" name="baslik" placeholder="Örn: Garson, Aşçıbaşı, Satış Danışmanı..." required>
                            </div>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                            <div class="profil-input-satir">
                                <label><svg width="14" height="14"><use xlink:href="#icon-company"></use></svg> SEKTÖR</label>
                                <div class="profil-input-grup">
                                    <div class="iv-dropdown" id="iv_sektorDropdown">
                                        <div class="dropdown-trigger">
                                            <span>Sektör Seçin</span>
                                            <svg width="14" height="14"><use xlink:href="#icon-chevron"></use></svg>
                                        </div>
                                        <div class="dropdown-menu">
                                            <?php foreach($sektorler as $s): ?>
                                            <div class="dropdown-option" data-value="<?php echo $s['sektorID']; ?>"><?php echo htmlspecialchars($s['sektorad']); ?></div>
                                            <?php endforeach; ?>
                                        </div>
                                        <input type="hidden" name="sektor_id" id="iv_sektor_input" required>
                                    </div>
                                </div>
                            </div>
                            <div class="profil-input-satir">
                                <label><svg width="14" height="14"><use xlink:href="#icon-briefcase"></use></svg> ÇALIŞMA TÜRÜ</label>
                                <div class="profil-input-grup">
                                    <div class="iv-dropdown" id="iv_calismaturDropdown">
                                        <div class="dropdown-trigger">
                                            <span>Çalışma Türü Seçin</span>
                                            <svg width="14" height="14"><use xlink:href="#icon-chevron"></use></svg>
                                        </div>
                                        <div class="dropdown-menu">
                                            <?php foreach($calismaturleri as $ct): ?>
                                            <div class="dropdown-option" data-value="<?php echo $ct['calismaID']; ?>"><?php echo htmlspecialchars($ct['calismatur']); ?></div>
                                            <?php endforeach; ?>
                                        </div>
                                        <input type="hidden" name="calismatur_id" id="iv_calismatur_input" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                            <div class="profil-input-satir">
                                <label><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg> MAAŞ (TL)</label>
                                <div class="profil-input-grup">
                                    <input type="number" name="maas" placeholder="Örn: 25000" min="0" required>
                                </div>
                            </div>
                            <div class="profil-input-satir">
                                <label><svg width="14" height="14"><use xlink:href="#icon-info"></use></svg> YAN HAKLAR</label>
                                <div class="profil-input-grup">
                                    <div class="iv-dropdown" id="iv_yanhakDropdown">
                                        <div class="dropdown-trigger">
                                            <span>Yan Hak Seçin</span>
                                            <svg width="14" height="14"><use xlink:href="#icon-chevron"></use></svg>
                                        </div>
                                        <div class="dropdown-menu">
                                            <?php foreach($yanhaklar as $yh): ?>
                                            <div class="dropdown-option" data-value="<?php echo $yh['yanhakID']; ?>"><?php echo htmlspecialchars($yh['yanhak']); ?></div>
                                            <?php endforeach; ?>
                                        </div>
                                        <input type="hidden" name="yanhak_id" id="iv_yanhak_input" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Konum Bilgileri -->
                <div class="profil-kart" style="margin-bottom:16px;">
                    <div class="profil-kart-baslik">
                        <svg width="18" height="18"><use xlink:href="#icon-location"></use></svg>
                        <h3>Konum Bilgileri</h3>
                    </div>
                    <div class="profil-kart-govde">
                        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px;">
                            <div class="profil-input-satir">
                                <label>İL</label>
                                <div class="profil-input-grup">
                                    <div class="iv-dropdown" id="iv_ilDropdown">
                                        <div class="dropdown-trigger">
                                            <span>İl Seçin</span>
                                            <svg width="14" height="14"><use xlink:href="#icon-chevron"></use></svg>
                                        </div>
                                        <div class="dropdown-menu">
                                            <?php foreach($iller as $il): ?>
                                            <div class="dropdown-option" data-value="<?php echo $il['ilID']; ?>"><?php echo htmlspecialchars($il['ilisim']); ?></div>
                                            <?php endforeach; ?>
                                        </div>
                                        <input type="hidden" id="iv_il_input" value="">
                                    </div>
                                </div>
                            </div>
                            <div class="profil-input-satir">
                                <label>İLÇE</label>
                                <div class="profil-input-grup">
                                    <div class="iv-dropdown" id="iv_ilceDropdown">
                                        <div class="dropdown-trigger">
                                            <span>Önce il seçin</span>
                                            <svg width="14" height="14"><use xlink:href="#icon-chevron"></use></svg>
                                        </div>
                                        <div class="dropdown-menu" id="iv_ilce_menu"></div>
                                        <input type="hidden" id="iv_ilce_input" value="">
                                    </div>
                                </div>
                            </div>
                            <div class="profil-input-satir">
                                <label>MAHALLE</label>
                                <div class="profil-input-grup">
                                    <div class="iv-dropdown" id="iv_mahalleDropdown">
                                        <div class="dropdown-trigger">
                                            <span>Önce ilçe seçin</span>
                                            <svg width="14" height="14"><use xlink:href="#icon-chevron"></use></svg>
                                        </div>
                                        <div class="dropdown-menu" id="iv_mahalle_menu"></div>
                                        <input type="hidden" name="mahalle_id" id="iv_mahalle_input" value="">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="profil-input-satir" style="margin-top:4px;">
                            <label><svg width="14" height="14"><use xlink:href="#icon-location"></use></svg> AÇIK ADRES</label>
                            <div class="profil-input-grup">
                                <input type="text" name="acikadres" placeholder="Örn: Atatürk Cad. No:12, Merkez/İstanbul" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Çalışma Zamanı -->
                <div class="profil-kart" style="margin-bottom:16px;">
                    <div class="profil-kart-baslik">
                        <svg width="18" height="18"><use xlink:href="#icon-calendar"></use></svg>
                        <h3>Çalışma Zamanı</h3>
                    </div>
                    <div class="profil-kart-govde">
                        <div class="profil-input-satir">
                            <label>ÇALIŞMA GÜNLERİ</label>
                            <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:4px;">
                                <?php 
                                $gunler = ['Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi','Pazar'];
                                foreach($gunler as $gun): ?>
                                <label class="iv-gun-chip">
                                    <input type="checkbox" name="calismagunleri[]" value="<?php echo $gun; ?>">
                                    <span><?php echo $gun; ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-top:12px;">
                            <div class="profil-input-satir">
                                <label>BAŞLANGIÇ SAATİ</label>
                                <div class="profil-input-grup">
                                    <input type="time" name="saat_baslangic" value="09:00">
                                </div>
                            </div>
                            <div class="profil-input-satir">
                                <label>BİTİŞ SAATİ</label>
                                <div class="profil-input-grup">
                                    <input type="time" name="saat_bitis" value="18:00">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- İş Tanımı -->
                <div class="profil-kart" style="margin-bottom:20px;">
                    <div class="profil-kart-baslik">
                        <svg width="18" height="18"><use xlink:href="#icon-file"></use></svg>
                        <h3>İş Tanımı</h3>
                    </div>
                    <div class="profil-kart-govde">
                        <div class="profil-input-grup">
                            <textarea name="aciklama" placeholder="İş tanımını detaylı bir şekilde yazın. Aranan özellikler, sorumluluklar, deneyim beklentileri..." required style="min-height:140px;"></textarea>
                        </div>
                        <p style="margin:10px 0 0; font-size:12px; color:#9ca3af;">İpucu: Detaylı açıklama daha fazla nitelikli başvuru almanızı sağlar.</p>
                    </div>
                </div>

                <!-- Submit -->
                <button type="submit" class="profil-kaydet-btn" id="iv_yayinla_btn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    İlanı Yayına Al
                </button>
            </form>
        </div>
    </div>
</div>

<!-- İlan Yayınlandı Onay Modal -->
<div id="ilanBasariModal" class="modal-arkaplan" style="z-index:9999;">
    <div class="modal-icerik kucuk" style="border-radius:24px; overflow:hidden; max-width:440px;">
        <div class="modal-body" style="padding:50px 30px; text-align:center;">
            <div style="width:80px; height:80px; background:linear-gradient(135deg,#22c55e,#16a34a); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 24px; box-shadow:0 10px 30px rgba(34,197,94,0.3);">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </div>
            <h2 style="font-size:24px; font-weight:900; color:#111827; margin-bottom:8px;">İlan Yayınlandı! 🎉</h2>
            <p style="font-size:15px; color:#6b7280; line-height:1.6; margin-bottom:30px;">İlanınız başarıyla yayına alındı. Artık iş arayanlar ilanınızı görebilir ve başvuru yapabilir.</p>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <button onclick="window.location.href='is_ilanlari.php'" style="background:#f1f5f9; color:#475569; border:none; padding:14px; border-radius:12px; font-weight:700; cursor:pointer; font-size:14px; transition:0.2s;">İlanları Gör</button>
                <button onclick="window.location.reload()" style="background:linear-gradient(135deg,#ff7e1d,#ea580c); color:#fff; border:none; padding:14px; border-radius:12px; font-weight:700; cursor:pointer; font-size:14px; transition:0.2s; box-shadow:0 4px 12px rgba(234,88,12,0.3);">Tamam</button>
            </div>
        </div>
    </div>
</div>

<style>
.iv-gun-chip {
    display: inline-flex;
    align-items: center;
    cursor: pointer;
    user-select: none;
}
.iv-gun-chip input[type="checkbox"] { display: none; }
.iv-gun-chip span {
    padding: 8px 16px;
    border: 1.5px solid var(--border);
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-muted);
    background: #fff;
    transition: all 0.2s;
}
.iv-gun-chip input[type="checkbox"]:checked + span {
    background: var(--primary-light);
    border-color: var(--primary);
    color: var(--primary);
    font-weight: 700;
}
.iv-gun-chip span:hover {
    border-color: var(--primary);
    color: var(--primary);
}

/* Modal Dropdown Styles */
.iv-dropdown {
    position: relative;
    width: 100%;
}
.iv-dropdown .dropdown-trigger {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    background: #f9fafb;
    border: 1.5px solid #e5e7eb;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
    min-height: 48px;
}
.iv-dropdown .dropdown-trigger:hover {
    border-color: var(--primary);
    background: #fff;
}
.iv-dropdown.active .dropdown-trigger {
    border-color: var(--primary);
    background: #fff;
    box-shadow: 0 0 0 4px rgba(234, 88, 12, 0.1);
}
.iv-dropdown .dropdown-trigger span {
    font-size: 14px;
    color: #374151;
    font-weight: 500;
}
.iv-dropdown .dropdown-menu {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    z-index: 1000;
    display: none;
    max-height: 250px;
    overflow-y: auto;
}
.iv-dropdown.active .dropdown-menu {
    display: block;
}
.iv-dropdown .dropdown-option {
    padding: 12px 16px;
    font-size: 14px;
    color: #4b5563;
    cursor: pointer;
    transition: 0.2s;
}
.iv-dropdown .dropdown-option:hover {
    background: #fff7ed;
    color: var(--primary);
}
</style>

<script>
// İlan Ver Form AJAX gönderimi
async function ilanVerFormGonder(e) {
    e.preventDefault();
    const form = document.getElementById('ilanVerForm');
    const btn = document.getElementById('iv_yayinla_btn');
    
    // Basit validasyon
    const requiredFields = {
        'sektor_id': 'Lütfen bir sektör seçin.',
        'calismatur_id': 'Lütfen çalışma türü seçin.',
        'yanhak_id': 'Lütfen yan hak seçin.',
        'mahalle_id': 'Lütfen konum seçin (Mahalle).'
    };

    const formData = new FormData(form);
    for (const [field, msg] of Object.entries(requiredFields)) {
        if (!formData.get(field)) {
            alert(msg);
            return false;
        }
    }

    btn.disabled = true;
    btn.innerHTML = '<div style="width:18px;height:18px;border:2px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;animation:spin 0.8s linear infinite;"></div> Yayınlanıyor...';
    
    try {
        const res = await fetch('islem.php', { method: 'POST', body: formData });
        const data = await res.json();
        
        if(data.durum === 'basarili') {
            modalKapat('ilanVerModal');
            modalAc('ilanBasariModal');
            form.reset();
            // Dropdown'ları sıfırla
            document.querySelectorAll('#ilanVerModal .dropdown-trigger span').forEach(s => {
                const orig = s.closest('.iv-dropdown').querySelector('.dropdown-option');
                if(orig) s.innerText = s.innerText; // keep as-is, form.reset handles inputs
            });
        } else {
            showToast(data.hata || 'Bir hata oluştu', 'error');
            btn.disabled = false;
            btn.innerHTML = originalBtnHTML;
        }
    } catch(err) {
        alert('İlan eklenirken bir hata oluştu. Lütfen tekrar deneyin.');
        btn.disabled = false;
        btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg> İlanı Yayına Al';
    }
    return false;
}

// İlan Ver Dropdown Başlatma
function initIlanVerDropdowns() {
    initIVDropdown('iv_sektorDropdown', 'iv_sektor_input');
    initIVDropdown('iv_calismaturDropdown', 'iv_calismatur_input');
    initIVDropdown('iv_yanhakDropdown', 'iv_yanhak_input');

    // İl → İlçe → Mahalle kademeli dropdown
    initIVDropdown('iv_ilDropdown', 'iv_il_input', function(val) {
        document.getElementById('iv_ilce_input').value = '';
        document.getElementById('iv_mahalle_input').value = '';
        const ilceTrigger = document.querySelector('#iv_ilceDropdown .dropdown-trigger span');
        const mahalleTrigger = document.querySelector('#iv_mahalleDropdown .dropdown-trigger span');
        ilceTrigger.innerText = 'Yükleniyor...';
        mahalleTrigger.innerText = 'Önce ilçe seçin';
        document.getElementById('iv_ilce_menu').innerHTML = '';
        document.getElementById('iv_mahalle_menu').innerHTML = '';

        fetch('adres_getir.php?islem=ilce&il_id=' + val)
            .then(r => r.json())
            .then(ilceler => {
                const menu = document.getElementById('iv_ilce_menu');
                menu.innerHTML = '';
                ilceler.forEach(ilce => {
                    const opt = document.createElement('div');
                    opt.className = 'dropdown-option';
                    opt.dataset.value = ilce.ilceID;
                    opt.textContent = ilce.ilceisim;
                    menu.appendChild(opt);
                });
                ilceTrigger.innerText = ilceler.length > 0 ? 'İlçe Seçin' : 'İlçe bulunamadı';
                initIVDropdown('iv_ilceDropdown', 'iv_ilce_input', function(ilceVal) {
                    document.getElementById('iv_mahalle_input').value = '';
                    mahalleTrigger.innerText = 'Yükleniyor...';
                    document.getElementById('iv_mahalle_menu').innerHTML = '';

                    fetch('adres_getir.php?islem=mahalle&ilce_id=' + ilceVal)
                        .then(r => r.json())
                        .then(mahalleler => {
                            const mMenu = document.getElementById('iv_mahalle_menu');
                            mMenu.innerHTML = '';
                            mahalleler.forEach(m => {
                                const mOpt = document.createElement('div');
                                mOpt.className = 'dropdown-option';
                                mOpt.dataset.value = m.mahalleID;
                                mOpt.textContent = m.mahalleisim;
                                mMenu.appendChild(mOpt);
                            });
                            mahalleTrigger.innerText = mahalleler.length > 0 ? 'Mahalle Seçin' : 'Mahalle bulunamadı';
                            initIVDropdown('iv_mahalleDropdown', 'iv_mahalle_input');
                        });
                });
            });
    });
}

function initIVDropdown(id, inputId, onSelect) {
    const d = document.getElementById(id); if(!d) return;
    const trigger = d.querySelector('.dropdown-trigger');
    const input = document.getElementById(inputId);
    trigger.onclick = (e) => { 
        e.stopPropagation();
        document.querySelectorAll('.iv-dropdown.active').forEach(dd => {
            if(dd !== d) dd.classList.remove('active');
        });
        d.classList.toggle('active'); 
    };
    d.querySelectorAll('.dropdown-option').forEach(opt => {
        opt.onclick = (e) => {
            e.stopPropagation();
            input.value = opt.dataset.value;
            trigger.querySelector('span').innerText = opt.innerText;
            d.classList.remove('active');
            if(onSelect) onSelect(opt.dataset.value, opt.innerText);
        };
    });
}

// İlan Ver Modal'ı açma fonksiyonu (global)
function ilanVerAc() { 
    modalAc('ilanVerModal'); 
}

// Sayfa yüklendiğinde dropdown'ları başlat
document.addEventListener('DOMContentLoaded', function() {
    if(document.getElementById('ilanVerModal')) {
        initIlanVerDropdowns();
    }
    // Dışarı tıklanınca dropdown kapat
    document.addEventListener('click', function(e) {
        document.querySelectorAll('#ilanVerModal .custom-dropdown.active').forEach(d => {
            if(!d.contains(e.target)) d.classList.remove('active');
        });
    });
});
</script>

<?php endif; ?>
