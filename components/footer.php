    <footer>
        <div class="footer-icerik">
            <div class="footer-sutun">
                <a href="index.php" class="footer-logo">
                    <img src="img/logo.png" alt="Kariyerlen" style="height: 32px; width: auto; display: block; filter: brightness(0) invert(1);">
                </a>
                <p style="line-height: 1.6;">Hayalinizdeki işi veya aradığınız yeteneği bulmanın en hızlı ve güvenilir yolu. Evinize en yakın iş fırsatlarını keşfedin ve kariyerinize yön verin.</p>
            </div>
            <div class="footer-sutun">
                <h4>Kariyerlen</h4>
                <ul>
                    <li><a href="#">Hakkımızda</a></li>
                    <li><a href="#">Sıkça Sorulan Sorular</a></li>
                    <li><a href="#">İletişim</a></li>
                    <li><a href="#">Gizlilik Politikası</a></li>
                </ul>
            </div>
            <div class="footer-sutun">
                <h4>Adaylar İçin</h4>
                <ul>
                    <li><a href="is_ilanlari.php">Tüm İş İlanları</a></li>
                    <li><a href="giris.php?islem=kayit_sec">Özgeçmiş (CV) Oluştur</a></li>
                    <li><a href="kariyer_rehberi.php">Kariyer Rehberi</a></li>
                    <li><a href="#">İş Alarmı Kur</a></li>
                </ul>
            </div>
            <div class="footer-sutun">
                <h4>İşverenler İçin</h4>
                <ul>
                    <li><a href="giris.php?islem=kayit_sec">Ücretsiz İlan Ver</a></li>
                    <li><a href="#">Aday Havuzunda Ara</a></li>
                    <li><a href="#">Fiyatlandırma</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-alt">
            <p>&copy; <?php echo date('Y'); ?> Kariyerlen. Tüm Hakları Saklıdır.</p>
            <div class="sosyal-medya">
                <a href="#"><svg width="20" height="20"><use xlink:href="#icon-facebook"></use></svg></a>
                <a href="#"><svg width="20" height="20"><use xlink:href="#icon-twitter"></use></svg></a>
                <a href="#"><svg width="20" height="20"><use xlink:href="#icon-instagram"></use></svg></a>
                <a href="#"><svg width="20" height="20"><use xlink:href="#icon-linkedin"></use></svg></a>
            </div>
        </div>
    </footer>

    <?php if(isset($_SESSION['kullaniciID'])): ?>
        <?php include 'components/profil_modal.php'; ?>
        <?php include 'components/onay_modal.php'; ?>
        <?php if($_SESSION['krolID'] == 2): ?>
            <?php include 'components/ilan_ver_modal.php'; ?>
        <?php endif; ?>
    <?php endif; ?>

    <script>
    function modalAc(id) { 
        const m = document.getElementById(id);
        if(m) m.style.display = 'flex'; 
    }
    function modalKapat(id) { 
        const m = document.getElementById(id);
        if(m) m.style.display = 'none'; 
    }
    
    // İlan Şikayet Modalı
    document.write(`
    <div id="sikayetModal" class="modal-arkaplan">
        <div class="modal-icerik kucuk">
            <button class="kapat-btn" onclick="modalKapat('sikayetModal')">×</button>
            <div class="modal-body" style="padding:28px;">
                <h3 style="margin:0 0 8px 0; font-weight:800; font-size:18px; color:#1e293b;">İlanı Şikayet Et</h3>
                <p style="font-size:13px; color:#64748b; margin:0 0 24px 0; line-height:1.5;">Lütfen bu ilanı şikayet etme nedeninizi belirtin. Geri bildiriminiz incelenecektir.</p>
                <form onsubmit="return sikayetGonder(event)">
                    <input type="hidden" id="sikayet_ilan_id" name="ilan_id">
                    <div style="display:flex; flex-direction:column; gap:16px;">
                        <div>
                            <label style="font-size:12px; font-weight:700; color:#475569; display:block; margin-bottom:6px;">Şikayet Nedeni</label>
                            <select name="neden" style="width:100%; padding:12px 14px; border:1px solid #e2e8f0; border-radius:10px; outline:none; background:#f8fafc; font-size:14px; color:#1e293b;" required>
                                <option value="">Seçiniz...</option>
                                <option value="Yanlış/Yanıltıcı Bilgi">Yanlış/Yanıltıcı Bilgi</option>
                                <option value="Sahte İlan">Sahte İlan / Dolandırıcılık</option>
                                <option value="Uygunsuz İçerik">Uygunsuz İçerik</option>
                                <option value="Diğer">Diğer</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-size:12px; font-weight:700; color:#475569; display:block; margin-bottom:6px;">Detaylı Açıklama (İsteğe Bağlı)</label>
                            <textarea name="detay" rows="4" style="width:100%; padding:12px 14px; border:1px solid #e2e8f0; border-radius:10px; outline:none; background:#f8fafc; resize:none; font-size:14px; color:#1e293b;" placeholder="Lütfen durumu kısaca açıklayın..."></textarea>
                        </div>
                        <button type="submit" style="background:#ef4444; color:#fff; border:none; padding:14px; border-radius:10px; font-weight:700; font-size:14px; cursor:pointer; width:100%; transition:0.2s; margin-top:8px;">Şikayeti Gönder</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    `);

    function sikayetModalAc(ilanID) {
        document.getElementById('sikayet_ilan_id').value = ilanID;
        modalAc('sikayetModal');
    }
    
    function sikayetGonder(e) {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        const form = e.target;
        
        const fd = new FormData(form);
        fd.append('islem', 'ilan_sikayet_et');
        
        btn.disabled = true;
        btn.innerText = 'Gönderiliyor...';
        
        fetch('islem.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if(d.durum === 'basarili') {
                    if(typeof showToast === 'function') {
                        showToast('Şikayetiniz başarıyla iletildi. Teşekkür ederiz.');
                    } else {
                        alert('Şikayetiniz iletildi.');
                    }
                    modalKapat('sikayetModal');
                    form.reset();
                } else {
                    if(typeof showToast === 'function') {
                        showToast(d.hata || 'Bir hata oluştu.', 'error');
                    } else {
                        alert(d.hata || 'Bir hata oluştu.');
                    }
                }
            })
            .catch(err => {
                console.error(err);
                alert('Bağlantı hatası.');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerText = 'Şikayeti Gönder';
            });
            
        return false;
    }
    
    // Global profil modal helper
    function profilAc(sekme) {
        modalAc('profilModal');
        if(sekme && typeof sekmeAc === 'function') {
            const btn = document.querySelector(`.p-sekme[onclick*="${sekme}"]`);
            if(btn) btn.click();
        }
    }
    function syncSavedStateAcrossDOM(ilanID, isSaved, baslik, firmaadi, tarih) {
        document.querySelectorAll(`.io-favori[data-id="${ilanID}"], .mini-ilan-kart[data-id="${ilanID}"] .mini-save-btn, .btn-icon-gray[onclick*="ilanKaydetToggleSplit"][onclick*="${ilanID}"], #btn_modal_kaydet[data-id="${ilanID}"]`).forEach(btn => {
            const isMini = btn.classList.contains('mini-save-btn');
            const isSplitBtn = btn.classList.contains('btn-icon-gray');
            
            if(isSaved) {
                btn.classList.add('aktif');
                if(isSplitBtn) {
                    btn.innerHTML = `<svg width="18" height="18"><use xlink:href="#icon-save-active"></use></svg> Kaydedildi`;
                } else if(!isMini) {
                    btn.innerHTML = `<svg width="20" height="20"><use xlink:href="#icon-save-active"></use></svg>`;
                } else {
                    btn.querySelector('use').setAttribute('xlink:href', '#icon-save-active');
                }
            } else {
                btn.classList.remove('aktif');
                if(isSplitBtn) {
                    btn.innerHTML = `<svg width="18" height="18"><use xlink:href="#icon-save"></use></svg> Kaydet`;
                } else if(!isMini) {
                    btn.innerHTML = `<svg width="20" height="20"><use xlink:href="#icon-save"></use></svg>`;
                } else {
                    btn.querySelector('use').setAttribute('xlink:href', '#icon-save');
                }
            }
        });

        const profilSavedBox = document.querySelector('#pm-sekme-kaydedilenler .db-list') || document.querySelector('#pm-sekme-kaydedilenler .pm-grid');
        if(!profilSavedBox) return;
        
        if(isSaved) {
            const bos = profilSavedBox.querySelector('.p-bos-uyari') || profilSavedBox.querySelector('.db-empty-state');
            if(bos) bos.remove();
            
            if(!document.getElementById('pm_kayit_' + ilanID)) {
                const cardHtml = `
                    <div class="db-list-item" onclick="window.location.href='is_ilanlari.php?id=${ilanID}'" id="pm_kayit_${ilanID}">
                        <div class="item-left">
                            <div class="item-icon orange">
                                <svg width="24" height="24"><use xlink:href="#icon-save-active"></use></svg>
                            </div>
                            <div class="item-info">
                                <h4>${baslik || 'İlan'}</h4>
                                <div class="item-meta">
                                    <span><svg width="12" height="12"><use xlink:href="#icon-company"></use></svg> ${firmaadi || 'Firma'}</span>
                                    <span>• ${tarih || new Date().toLocaleDateString('tr-TR')}</span>
                                </div>
                            </div>
                        </div>
                        <div class="item-right">
                            <button class="db-action-btn" onclick="event.stopPropagation(); window.location.href='is_ilanlari.php?id=${ilanID}'" title="İlana Git">
                                <svg width="18" height="18"><use xlink:href="#icon-external"></use></svg>
                            </button>
                            <button type="button" class="db-action-btn io-favori aktif" data-id="${ilanID}" onclick="event.stopPropagation(); ilanKaydetToggle(this)" title="Kaydet/Kaldır">
                                <svg width="20" height="20"><use xlink:href="#icon-save-active"></use></svg>
                            </button>
                        </div>
                    </div>
                `;
                profilSavedBox.insertAdjacentHTML('afterbegin', cardHtml);
            }
        } else {
            const kart = document.getElementById('pm_kayit_' + ilanID);
            if(kart) {
                kart.style.opacity = '0';
                setTimeout(() => { 
                    kart.remove(); 
                    if(profilSavedBox.children.length === 0) {
                        profilSavedBox.innerHTML = "<p class='p-bos-uyari'>Henüz kaydettiğiniz bir ilan yok.</p>";
                    }
                }, 300);
            }
        }
    }
    </script>
</body>
</html>
