<?php
if(!isset($_SESSION)) { session_start(); }
if(isset($_SESSION['kullaniciID'])):
    if(!isset($db)) { include 'baglan.php'; }
    $kID = $_SESSION['kullaniciID'];
    $rol = $_SESSION['krolID'];

    // Temel profil verilerini çek
    if ($rol == 1) {
        $ps = $db->prepare("SELECT k.*, i.* FROM kullanici k JOIN isarayan i ON k.kullaniciID = i.akullaniciID WHERE k.kullaniciID = ?");
    } else {
        $ps = $db->prepare("SELECT k.*, v.* FROM kullanici k JOIN isveren v ON k.kullaniciID = v.ikullaniciID WHERE k.kullaniciID = ?");
    }
    $ps->execute([$kID]);
    $profil = $ps->fetch(PDO::FETCH_ASSOC);

    // İstatistikler ve Listeler
    if ($rol == 2) {
        $isverenID = $profil['isverenID'];
        // İlanlarım
        $is = $db->prepare("SELECT * FROM ilan WHERE iisverenID = ? ORDER BY yayintarihi DESC");
        $is->execute([$isverenID]);
        $ilanlarim = $is->fetchAll(PDO::FETCH_ASSOC);

        $ilan_aktif = []; $ilan_bekleyen = []; $ilan_reddedilen = [];
        foreach($ilanlarim as $il) {
            if($il['idurumID'] == 1) $ilan_aktif[] = $il;
            elseif($il['idurumID'] == 2) $ilan_reddedilen[] = $il;
            else $ilan_bekleyen[] = $il;
        }

        // Gelen Başvurular
        $bs = $db->prepare("SELECT b.*, i.baslik, ia.adsoyad, ia.dogumyili, ia.akullaniciID as aday_k_id 
                           FROM basvuru b 
                           JOIN ilan i ON b.bilanID = i.ilanID 
                           JOIN isarayan ia ON b.bisarayanID = ia.isarayanID 
                           WHERE i.iisverenID = ? ORDER BY b.tarih DESC");
        $bs->execute([$isverenID]);
        $basvurular_firma = $bs->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // İş arayan için veriler (mevcut yapı korunabilir ama sadeleştirilmiş)
        $isarayanID = $profil['isarayanID'];
        $bs = $db->prepare("SELECT b.*, i.baslik, v.firmaadi FROM basvuru b JOIN ilan i ON b.bilanID = i.ilanID JOIN isveren v ON i.iisverenID = v.isverenID WHERE b.bisarayanID = ?");
        $bs->execute([$isarayanID]);
        $basvurularim = $bs->fetchAll(PDO::FETCH_ASSOC);

        // Kaydedilen ilanları çek
        $ks = $db->prepare("SELECT k.kayitID, k.tarih as kayit_tarihi, i.ilanID, i.baslik, v.firmaadi FROM kaydedilenler k JOIN ilan i ON k.ilanID = i.ilanID JOIN isveren v ON i.iisverenID = v.isverenID WHERE k.kullaniciID = ? ORDER BY k.tarih DESC");
        $ks->execute([$kID]);
        $kaydedilenler = $ks->fetchAll(PDO::FETCH_ASSOC);
    }

    // Modal Dropdown Verileri (İlan Düzenleme için)
    $sektorler = $db->query("SELECT * FROM sektor ORDER BY sektorad ASC")->fetchAll(PDO::FETCH_ASSOC);
    $calismaturleri = $db->query("SELECT * FROM calismaturu ORDER BY calismatur ASC")->fetchAll(PDO::FETCH_ASSOC);
    $yanhaklar = $db->query("SELECT * FROM yanhaklar ORDER BY yanhak ASC")->fetchAll(PDO::FETCH_ASSOC);
    $iller = $db->query("SELECT * FROM il ORDER BY ilisim ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div id="profilModal" class="modal-arkaplan">
    <div class="modal-icerik buyuk db-layout">
        <button class="kapat-btn" onclick="modalKapat('profilModal')" style="z-index: 100;">×</button>
        
        <!-- SIDEBAR -->
        <div class="db-sidebar">
            <div class="db-profile-info">
                <div class="db-foto-alani" onclick="toggleFotoMenu(event)">
                    <?php if(!empty($profil['fotograf'])): ?>
                        <img src="uploads/<?php echo $profil['fotograf']; ?>" class="p-ana-foto">
                    <?php else: ?>
                        <div class="p-ana-foto-bos">
                            <?php echo mb_substr(($rol == 2 ? $profil['firmaadi'] : $profil['adsoyad']), 0, 1); ?>
                        </div>
                    <?php endif; ?>
                    <div class="p-foto-badge"><svg width="14" height="14"><use xlink:href="#icon-camera"></use></svg></div>
                    
                    <div id="foto_menu" class="p-foto-menu">
                        <label class="p-menu-item">
                            <svg width="14" height="14"><use xlink:href="#icon-upload"></use></svg> Fotoğraf Yükle
                            <input type="file" accept="image/*" style="display:none;" onchange="profilFotoYukle(this)">
                        </label>
                        <?php if(!empty($profil['fotograf'])): ?>
                            <div class="p-menu-item sil" onclick="profilFotoSil()">
                                <svg width="14" height="14"><use xlink:href="#icon-trash-premium"></use></svg> Fotoğrafı Kaldır
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <h2><?php echo htmlspecialchars($rol == 2 ? $profil['firmaadi'] : $profil['adsoyad']); ?></h2>
                <span class="db-rol-etiket"><?php echo ($rol == 2 ? 'İşveren Hesabı' : 'İş Arayan Hesabı'); ?></span>
            </div>

            <div class="db-nav">
                <?php if($rol == 2): ?>
                    <div style="display:flex; flex-direction:column; gap:4px;">
                        <button class="db-nav-btn aktif" onclick="pmSekme('ilanlarim', this); pmAltSekme('ilan_aktif', document.getElementById('nav_ilan_aktif'))"><svg width="18" height="18"><use xlink:href="#icon-list-3d"></use></svg> İlanlarım</button>
                        <div id="ilanlarim_alt_menu" class="db-sub-menu">
                            <button id="nav_ilan_aktif" class="db-nav-btn-alt aktif" onclick="pmSekme('ilanlarim', document.querySelector('.db-nav-btn.aktif')); pmAltSekme('ilan_aktif', this)">Aktif İlanlar (<?php echo count($ilan_aktif); ?>)</button>
                            <button id="nav_ilan_bekleyen" class="db-nav-btn-alt" onclick="pmSekme('ilanlarim', document.querySelector('.db-nav-btn.aktif')); pmAltSekme('ilan_bekleyen', this)">Bekleyen İlanlar (<?php echo count($ilan_bekleyen); ?>)</button>
                            <button id="nav_ilan_reddedilen" class="db-nav-btn-alt" onclick="pmSekme('ilanlarim', document.querySelector('.db-nav-btn.aktif')); pmAltSekme('ilan_reddedilen', this)">Reddedilenler (<?php echo count($ilan_reddedilen); ?>)</button>
                        </div>
                    </div>
                    <button class="db-nav-btn" onclick="pmSekme('basvurular', this)"><svg width="18" height="18"><use xlink:href="#icon-user"></use></svg> Başvurular</button>
                    <button class="db-nav-btn" onclick="pmSekme('mesajlar', this); pmMesajYukle();">
                        <svg width="18" height="18"><use xlink:href="#icon-message"></use></svg> Mesajlar
                        <span id="unread_badge" class="db-badge" style="display:none;">0</span>
                    </button>
                    <button class="db-nav-btn" onclick="pmSekme('firma', this)"><svg width="18" height="18"><use xlink:href="#icon-company"></use></svg> Firma Bilgileri</button>
                <?php else: ?>
                    <button class="db-nav-btn aktif" onclick="pmSekme('basvurularim', this)"><svg width="18" height="18"><use xlink:href="#icon-list-3d"></use></svg> Başvurular</button>
                    <button class="db-nav-btn" onclick="pmSekme('kaydedilenler', this)"><svg width="18" height="18"><use xlink:href="#icon-save"></use></svg> Kaydedilenler</button>
                    <button class="db-nav-btn" onclick="pmSekme('mesajlar', this); pmMesajYukle();">
                        <svg width="18" height="18"><use xlink:href="#icon-message"></use></svg> Mesajlar
                        <span id="unread_badge_isarayan" class="db-badge" style="display:none;">0</span>
                    </button>
                    <button class="db-nav-btn" onclick="pmSekme('bilgiler', this)"><svg width="18" height="18"><use xlink:href="#icon-user"></use></svg> Profil Bilgileri</button>
                <?php endif; ?>
            </div>
        </div>

        <!-- MAIN CONTENT -->
        <div class="db-content">
            
            <?php if($rol == 2): ?>
                <!-- İLANLARIM -->
                <div id="pm-sekme-ilanlarim" class="pm-icerik aktif">
                    <div class="db-header">
                        <h3>Yayınladığınız İlanlar</h3>
                        <p>Tüm ilanlarınızı buradan yönetebilir, düzenleyebilir veya silebilirsiniz.</p>
                        
                        <!-- Mini Sekmeler -->
                        <div class="profil-sekmeler" style="margin-top:16px; margin-bottom: 0;">
                            <button class="p-sekme aktif" id="tab_ilan_aktif" onclick="pmAltSekme('ilan_aktif', document.getElementById('nav_ilan_aktif'))">Aktif İlanlar</button>
                            <button class="p-sekme" id="tab_ilan_bekleyen" onclick="pmAltSekme('ilan_bekleyen', document.getElementById('nav_ilan_bekleyen'))">Bekleyen İlanlar</button>
                            <button class="p-sekme" id="tab_ilan_reddedilen" onclick="pmAltSekme('ilan_reddedilen', document.getElementById('nav_ilan_reddedilen'))">Reddedilen İlanlar</button>
                        </div>
                    </div>

                    <!-- Aktif İlanlar -->
                    <div id="ilan_aktif" class="db-list pm-alt-liste">
                        <?php if(count($ilan_aktif) > 0): foreach($ilan_aktif as $i): ?>
                            <div class="db-list-item" id="pm_ilan_<?php echo $i['ilanID']; ?>" onclick="window.location.href='is_ilanlari.php?id=<?php echo $i['ilanID']; ?>'">
                                <div class="item-left">
                                    <div class="item-icon green">
                                        <svg width="24" height="24"><use xlink:href="#icon-list-3d"></use></svg>
                                    </div>
                                    <div class="item-info">
                                        <h4><?php echo htmlspecialchars($i['baslik']); ?></h4>
                                        <div class="item-meta">
                                            <span><svg width="12" height="12"><use xlink:href="#icon-location"></use></svg> <?php echo htmlspecialchars(explode(',',$i['acikadres'])[0]); ?></span>
                                            <span>• <?php echo date("d.m.Y", strtotime($i['yayintarihi'])); ?></span>
                                            <span class="status-badge success">Aktif</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="item-right">
                                    <button class="db-action-btn" onclick="event.stopPropagation(); ilanDuzenleAc(<?php echo $i['ilanID']; ?>)" title="Düzenle">
                                        <svg width="16" height="16"><use xlink:href="#icon-edit"></use></svg>
                                    </button>
                                    <button class="db-action-btn danger" onclick="event.stopPropagation(); ilanSil(<?php echo $i['ilanID']; ?>)" title="Sil">
                                        <svg width="16" height="16"><use xlink:href="#icon-trash-premium"></use></svg>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; else: echo "<div class='db-empty-state'><svg width='48' height='48' style='margin: 0 auto; display: block; opacity: 0.2;'><use xlink:href='#icon-list-3d'></use></svg><p>Aktif ilanınız bulunmuyor.</p></div>"; endif; ?>
                    </div>

                    <!-- Bekleyen İlanlar -->
                    <div id="ilan_bekleyen" class="db-list pm-alt-liste" style="display:none;">
                        <?php if(count($ilan_bekleyen) > 0): foreach($ilan_bekleyen as $i): ?>
                            <div class="db-list-item" id="pm_ilan_<?php echo $i['ilanID']; ?>" onclick="window.location.href='is_ilanlari.php?id=<?php echo $i['ilanID']; ?>'">
                                <div class="item-left">
                                    <div class="item-icon orange">
                                        <svg width="24" height="24"><use xlink:href="#icon-list-3d"></use></svg>
                                    </div>
                                    <div class="item-info">
                                        <h4><?php echo htmlspecialchars($i['baslik']); ?></h4>
                                        <div class="item-meta">
                                            <span><svg width="12" height="12"><use xlink:href="#icon-location"></use></svg> <?php echo htmlspecialchars(explode(',',$i['acikadres'])[0]); ?></span>
                                            <span>• <?php echo date("d.m.Y", strtotime($i['yayintarihi'])); ?></span>
                                            <span class="status-badge" style="background: rgba(249,115,22,0.15); color: #fb923c;">Onay Bekliyor</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="item-right">
                                    <button class="db-action-btn" onclick="event.stopPropagation(); ilanDuzenleAc(<?php echo $i['ilanID']; ?>)" title="Düzenle">
                                        <svg width="16" height="16"><use xlink:href="#icon-edit"></use></svg>
                                    </button>
                                    <button class="db-action-btn danger" onclick="event.stopPropagation(); ilanSil(<?php echo $i['ilanID']; ?>)" title="Sil">
                                        <svg width="16" height="16"><use xlink:href="#icon-trash-premium"></use></svg>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; else: echo "<div class='db-empty-state'><svg width='48' height='48' style='margin: 0 auto; display: block; opacity: 0.2;'><use xlink:href='#icon-list-3d'></use></svg><p>Bekleyen ilanınız bulunmuyor.</p></div>"; endif; ?>
                    </div>

                    <!-- Reddedilen İlanlar -->
                    <div id="ilan_reddedilen" class="db-list pm-alt-liste" style="display:none;">
                        <?php if(count($ilan_reddedilen) > 0): foreach($ilan_reddedilen as $i): ?>
                            <div class="db-list-item" id="pm_ilan_<?php echo $i['ilanID']; ?>" onclick="window.location.href='is_ilanlari.php?id=<?php echo $i['ilanID']; ?>'">
                                <div class="item-left">
                                    <div class="item-icon" style="background: rgba(239,68,68,0.15); color: #ef4444;">
                                        <svg width="24" height="24"><use xlink:href="#icon-error-3d"></use></svg>
                                    </div>
                                    <div class="item-info">
                                        <h4><?php echo htmlspecialchars($i['baslik']); ?></h4>
                                        <div class="item-meta">
                                            <span><svg width="12" height="12"><use xlink:href="#icon-location"></use></svg> <?php echo htmlspecialchars(explode(',',$i['acikadres'])[0]); ?></span>
                                            <span>• <?php echo date("d.m.Y", strtotime($i['yayintarihi'])); ?></span>
                                            <span class="status-badge" style="background: rgba(239,68,68,0.15); color: #ef4444;">Reddedildi</span>
                                        </div>
                                        <?php if(!empty($i['red_nedeni'])): ?>
                                        <div class="red-neden-kutusu" style="margin-top: 10px; padding: 10px 14px; background: #fef2f2; border-left: 4px solid #ef4444; border-radius: 6px; font-size: 12px; color: #991b1b; line-height:1.5;">
                                            <strong>Red Nedeni: </strong> <?php echo htmlspecialchars($i['red_nedeni']); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="item-right">
                                    <button class="db-action-btn" onclick="event.stopPropagation(); ilanDuzenleAc(<?php echo $i['ilanID']; ?>)" title="Düzenle">
                                        <svg width="16" height="16"><use xlink:href="#icon-edit"></use></svg>
                                    </button>
                                    <button class="db-action-btn danger" onclick="event.stopPropagation(); ilanSil(<?php echo $i['ilanID']; ?>)" title="Sil">
                                        <svg width="16" height="16"><use xlink:href="#icon-trash-premium"></use></svg>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; else: echo "<div class='db-empty-state'><svg width='48' height='48' style='margin: 0 auto; display: block; opacity: 0.2;'><use xlink:href='#icon-list-3d'></use></svg><p>Reddedilen ilanınız bulunmuyor.</p></div>"; endif; ?>
                    </div>
                </div>

                <!-- BAŞVURULAR -->
                <div id="pm-sekme-basvurular" class="pm-icerik">
                    <div class="db-header">
                        <h3>Gelen Başvurular</h3>
                        <p>İlanlarınıza yapılan başvuruları buradan inceleyebilirsiniz.</p>
                    </div>
                    <div id="pm_basvuru_liste" class="db-list">
                        <?php if ($rol == 2): ?>
                        <?php if(count($basvurular_firma) > 0): foreach($basvurular_firma as $b): ?>
                            <div class="db-list-item" onclick="window.location.href='is_ilanlari.php?id=<?php echo $b['bilanID']; ?>'">
                                <div class="item-left">
                                    <div class="db-avatar">
                                        <?php echo mb_substr($b['adsoyad'], 0, 1); ?>
                                    </div>
                                    <div class="item-info">
                                        <h4><?php echo htmlspecialchars($b['adsoyad']); ?></h4>
                                        <div class="item-meta">
                                            <span>Başvuru: <?php echo htmlspecialchars($b['baslik']); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="item-right" style="gap: 12px;">
                                    <button class="db-btn-primary outline" onclick="event.stopPropagation(); mesajPenceresiAc(<?php echo $b['aday_k_id']; ?>, '<?php echo addslashes($b['adsoyad']); ?>', '')">
                                        <svg width="16" height="16"><use xlink:href="#icon-message"></use></svg>
                                        Mesaj Gönder
                                    </button>
                                    <button class="db-btn-primary" onclick="event.stopPropagation(); adayProfiliGoster(<?php echo $b['basvuruID']; ?>)">
                                        <svg width="16" height="16"><use xlink:href="#icon-external"></use></svg>
                                        Profili Gör
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; else: echo "<div class='db-empty-state'><svg width='48' height='48' style='margin: 0 auto; display: block; opacity: 0.2;'><use xlink:href='#icon-user'></use></svg><p>Gelen başvuru bulunmuyor.</p></div>"; endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- FİRMA BİLGİLERİ -->
                <div id="pm-sekme-firma" class="pm-icerik">
                    <div class="db-header">
                        <h3>Firma Bilgileri</h3>
                        <p>Kurumsal profil bilgilerinizi güncelleyin.</p>
                    </div>
                    <div class="db-form-container">
                        <form onsubmit="return profilGuncelle(event)" class="db-form">
                            <input type="hidden" name="islem" value="profil_guncelle">
                            <div class="db-form-group">
                                <label>Firma Adı</label>
                                <input type="text" name="firmaadi" value="<?php echo htmlspecialchars($profil['firmaadi']); ?>" required>
                            </div>
                            <div class="db-form-row">
                                <div class="db-form-group">
                                    <label>Telefon</label>
                                    <input type="text" name="telno" value="<?php echo htmlspecialchars($profil['telno']); ?>">
                                </div>
                                <div class="db-form-group">
                                    <label>E-posta</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($profil['email']); ?>">
                                </div>
                            </div>
                            <div class="db-form-group">
                                <label>Vergi No</label>
                                <input type="text" name="vergino" value="<?php echo htmlspecialchars($profil['vergino']); ?>">
                            </div>
                            <div class="db-form-group">
                                <label>Firma Hakkında</label>
                                <textarea name="hakkimda" rows="4"><?php echo htmlspecialchars($profil['hakkimda']); ?></textarea>
                            </div>
                            <button type="submit" class="db-btn-primary full">Değişiklikleri Kaydet</button>
                        </form>
                        
                        <div class="db-danger-zone">
                            <button onclick="hesabiSil()" class="db-btn-danger">
                                <svg width="16" height="16"><use xlink:href="#icon-trash-premium"></use></svg> Hesabı Kalıcı Olarak Sil
                            </button>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- İŞ ARAYAN SEKMELERİ -->
                <div id="pm-sekme-basvurularim" class="pm-icerik aktif">
                    <div class="db-header">
                        <h3>Başvurularım</h3>
                        <p>Yaptığınız tüm başvuruları buradan takip edebilirsiniz.</p>
                    </div>
                    <div class="db-list">
                        <?php if(count($basvurularim) > 0): foreach($basvurularim as $b): ?>
                            <div class="db-list-item" onclick="window.location.href='is_ilanlari.php?id=<?php echo $b['bilanID']; ?>'">
                                <div class="item-left">
                                    <div class="item-icon blue">
                                        <svg width="24" height="24"><use xlink:href="#icon-list-3d"></use></svg>
                                    </div>
                                    <div class="item-info">
                                        <h4><?php echo htmlspecialchars($b['baslik']); ?></h4>
                                        <div class="item-meta">
                                            <span><svg width="12" height="12"><use xlink:href="#icon-company"></use></svg> <?php echo htmlspecialchars($b['firmaadi']); ?></span>
                                            <span>• <?php echo date("d.m.Y", strtotime($b['tarih'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="item-right">
                                    <span class="status-badge success">Başvuruldu</span>
                                </div>
                            </div>
                        <?php endforeach; else: echo "<div class='db-empty-state'><p>Henüz başvurunuz bulunmuyor.</p></div>"; endif; ?>
                    </div>
                </div>

                <div id="pm-sekme-kaydedilenler" class="pm-icerik">
                    <div class="db-header">
                        <h3>Kaydedilen İlanlar</h3>
                        <p>Daha sonra incelemek için kaydettiğiniz ilanlar.</p>
                    </div>
                    <div class="db-list">
                        <?php if(isset($kaydedilenler) && count($kaydedilenler) > 0): foreach($kaydedilenler as $k): ?>
                            <div class="db-list-item" onclick="window.location.href='is_ilanlari.php?id=<?php echo $k['ilanID']; ?>'" id="pm_kayit_<?php echo $k['ilanID']; ?>">
                                <div class="item-left">
                                    <div class="item-icon orange">
                                        <svg width="24" height="24"><use xlink:href="#icon-save-active"></use></svg>
                                    </div>
                                    <div class="item-info">
                                        <h4><?php echo htmlspecialchars($k['baslik']); ?></h4>
                                        <div class="item-meta">
                                            <span><svg width="12" height="12"><use xlink:href="#icon-company"></use></svg> <?php echo htmlspecialchars($k['firmaadi']); ?></span>
                                            <span>• <?php echo date("d.m.Y", strtotime($k['kayit_tarihi'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="item-right">
                                    <button class="db-action-btn" onclick="event.stopPropagation(); window.location.href='is_ilanlari.php?id=<?php echo $k['ilanID']; ?>'" title="İlana Git">
                                        <svg width="18" height="18"><use xlink:href="#icon-external"></use></svg>
                                    </button>
                                    <button type="button" class="db-action-btn io-favori aktif" data-id="<?php echo $k['ilanID']; ?>" onclick="event.stopPropagation(); profilIlanKaydetToggle(this)" title="Kaydet/Kaldır">
                                        <svg width="20" height="20"><use xlink:href="#icon-save-active"></use></svg>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; else: echo "<div class='db-empty-state'><p>Henüz kaydettiğiniz bir ilan yok.</p></div>"; endif; ?>
                    </div>
                </div>

                <div id="pm-sekme-bilgiler" class="pm-icerik">
                    <div class="db-header">
                        <h3>Profil Bilgileri</h3>
                        <p>Kişisel özgeçmiş bilgilerinizi güncelleyin.</p>
                    </div>
                    <div class="db-form-container">
                        <form onsubmit="return profilGuncelle(event)" class="db-form">
                            <input type="hidden" name="islem" value="profil_guncelle">
                            <div class="db-form-group">
                                <label>Ad Soyad</label>
                                <input type="text" name="adsoyad" value="<?php echo htmlspecialchars($profil['adsoyad']); ?>" required>
                            </div>
                            <div class="db-form-row">
                                <div class="db-form-group">
                                    <label>Doğum Yılı</label>
                                    <input type="number" name="dogumyili" value="<?php echo htmlspecialchars($profil['dogumyili']); ?>">
                                </div>
                                <div class="db-form-group">
                                    <label>Telefon</label>
                                    <input type="text" name="telno" value="<?php echo htmlspecialchars($profil['telno']); ?>">
                                </div>
                            </div>
                            <div class="db-form-row">
                                <div class="db-form-group">
                                    <label>Eğitim Durumu</label>
                                    <input type="text" name="egitim" value="<?php echo htmlspecialchars($profil['egitim'] ?? ''); ?>" placeholder="Örn: Üniversite Mezunu">
                                </div>
                                <div class="db-form-group">
                                    <label>Ehliyet</label>
                                    <input type="text" name="ehliyet" value="<?php echo htmlspecialchars($profil['ehliyet'] ?? ''); ?>" placeholder="Örn: B Sınıfı">
                                </div>
                            </div>
                            <div class="db-form-group">
                                <label>Askerlik Durumu</label>
                                <input type="text" name="askerlik" value="<?php echo htmlspecialchars($profil['askerlik'] ?? ''); ?>" placeholder="Örn: Yapıldı / Muaf / Tecilli">
                            </div>
                            <div class="db-form-group">
                                <label>İş Tecrübesi</label>
                                <textarea name="is_tecrubesi" rows="3" placeholder="Önceki iş deneyimleriniz..."><?php echo htmlspecialchars($profil['is_tecrubesi'] ?? ''); ?></textarea>
                            </div>
                            <div class="db-form-group">
                                <label>Hakkımda</label>
                                <textarea name="hakkimda" rows="4" placeholder="Kendinizden kısaca bahsedin..."><?php echo htmlspecialchars($profil['hakkimda'] ?? ''); ?></textarea>
                            </div>
                            <button type="submit" class="db-btn-primary full">Değişiklikleri Kaydet</button>
                        </form>

                        <div class="db-danger-zone">
                            <button onclick="hesabiSil()" class="db-btn-danger">
                                <svg width="16" height="16"><use xlink:href="#icon-trash-premium"></use></svg> Hesabı Kalıcı Olarak Sil
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- MESAJLAR (Ortak) -->
            <div id="pm-sekme-mesajlar" class="pm-icerik">
                <div class="db-header">
                    <h3>Mesajlarınız</h3>
                    <p>Adaylar veya işverenlerle olan görüşmeleriniz.</p>
                </div>
                <div id="pm_mesaj_liste" class="db-list">
                    <!-- AJAX ile yüklenecek -->
                </div>
            </div>

        </div>
    </div>
</div>

<div id="ilanDuzenleModal" class="modal-arkaplan">
    <div class="modal-icerik buyuk" style="max-width:780px;">
        <button class="kapat-btn" onclick="modalKapat('ilanDuzenleModal')">×</button>
        <div class="modal-body" style="padding:28px; max-height:85vh; overflow-y:auto;">
            <h3 style="margin-bottom:20px; font-weight:800;">İlanı Düzenle</h3>
            <form id="ilanDuzenleForm" onsubmit="return ilanGuncelle(event)" class="p-form">
                <!-- Dinamik -->
            </form>
        </div>
    </div>
</div>

<!-- Aday Detay Modal -->
<div id="adayDetayModal" class="modal-arkaplan">
    <div class="modal-icerik">
        <button class="kapat-btn" onclick="modalKapat('adayDetayModal')">×</button>
        <div class="modal-body" id="aday_modal_content">
            <!-- Dinamik -->
        </div>
    </div>
</div>

<!-- Firma Detay Modal -->
<div id="firmaDetayModal" class="modal-arkaplan">
    <div class="modal-icerik">
        <button class="kapat-btn" onclick="modalKapat('firmaDetayModal')">×</button>
        <div class="modal-body" id="firma_modal_content">
            <!-- Dinamik -->
        </div>
    </div>
</div>

<!-- Mesajlaşma Penceresi Modal -->
<div id="mesajPenceresiModal" class="modal-arkaplan">
    <div class="modal-icerik kucuk" style="max-width:450px; border-radius:20px; overflow:hidden;">
        <div class="modal-header-custom" style="padding:12px 20px; display:flex; align-items:center; justify-content:space-between; background:#f8fafc; border-bottom:1px solid #f1f5f9;">
            <div style="display:flex; align-items:center; gap:12px;">
                <img id="chat_muhatap_foto" src="img/default-user.png" style="width:36px; height:36px; border-radius:50%; object-fit:cover; background:#fff;">
                <h4 id="chat_muhatap_ad" style="margin:0; font-size:16px; font-weight:800; color:#1e293b;">Mesajlaşma</h4>
            </div>
            <button class="kapat-btn" onclick="mesajPenceresiKapat()" style="position:static; font-size:24px;">×</button>
        </div>
        <div class="modal-body" style="padding:0;">
            <div id="chat_messages" style="height:350px; overflow-y:auto; padding:20px; background:#fff; display:flex; flex-direction:column; gap:10px;">
                <!-- Mesajlar -->
            </div>
            <div style="padding:16px; border-top:1px solid #f1f5f9; background:#fff;">
                <form onsubmit="return mesajGonder(event)" style="display:flex; gap:10px;">
                    <input type="hidden" id="chat_alici_id">
                    <input type="text" id="chat_input" placeholder="Bir mesaj yazın..." style="flex:1; padding:10px 14px; border:1px solid #e2e8f0; border-radius:10px; outline:none; font-size:14px;">
                    <button type="submit" style="width:40px; height:40px; background:#ea580c; color:#fff; border:none; border-radius:10px; cursor:pointer; display:flex; align-items:center; justify-content:center;">
                        <svg width="20" height="20"><use xlink:href="#icon-send"></use></svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* --- PROFIL DASHBOARD MASTER STYLES --- */
.modal-icerik.buyuk.db-layout {
    display: flex;
    flex-direction: row;
    padding: 0;
    max-width: 1000px;
    height: 85vh;
    border-radius: 20px;
    overflow: hidden;
    background: #f8fafc;
}

/* SIDEBAR */
.db-sidebar {
    width: 280px;
    background: #ffffff;
    border-right: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    padding: 40px 0;
    z-index: 10;
}
.db-profile-info {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 30px;
    padding: 0 20px;
}
.db-foto-alani { position: relative; width: 90px; height: 90px; cursor: pointer; margin-bottom: 16px; }
.p-ana-foto, .p-ana-foto-bos { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 3px solid #fff; box-shadow: 0 4px 15px rgba(234,88,12,0.15); }
.p-ana-foto-bos { background: linear-gradient(135deg, #fff7ed, #ffedd5); color: #ea580c; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: 800; }
.p-foto-badge { position: absolute; bottom: 0; right: 0; background: #fff; color: #ea580c; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid #fff; transition: 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
.db-foto-alani:hover .p-foto-badge { background: #ea580c; color: #fff; }
.db-profile-info h2 { margin: 0 0 4px; font-size: 18px; color: #1e293b; font-weight: 800; text-align: center; }
.db-rol-etiket { font-size: 11px; color: #ea580c; font-weight: 700; background: #fff7ed; padding: 4px 10px; border-radius: 20px; }

.db-nav { display: flex; flex-direction: column; padding: 0 16px; gap: 4px; }
.db-nav-btn {
    padding: 14px 20px;
    border: none;
    background: transparent;
    font-size: 14px;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    border-radius: 12px;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 12px;
    text-align: left;
}
.db-nav-btn:hover { background: #f1f5f9; color: #1e293b; }
.db-nav-btn.aktif { background: #ea580c; color: #fff; box-shadow: 0 4px 12px rgba(234, 88, 12, 0.2); }
.db-nav-btn.aktif svg { fill: #fff; }
.db-badge { background: #ef4444; color: #fff; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 10px; margin-left: auto; }

/* MAIN CONTENT */
.db-content {
    flex: 1;
    overflow-y: auto;
    padding: 40px;
    background: #f8fafc;
}
.pm-icerik { display: none; animation: fadeUp 0.3s ease-out; }
.pm-icerik.aktif { display: block; }
@keyframes fadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

.db-header { margin-bottom: 30px; }
.db-header h3 { margin: 0 0 8px; font-size: 24px; font-weight: 800; color: #1e293b; }
.db-header p { margin: 0; font-size: 14px; color: #64748b; }

/* LIST VIEWS (İlanlar, Başvurular vs) */
.db-list { display: flex; flex-direction: column; gap: 12px; }
.db-list-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 16px 20px;
    transition: all 0.2s;
    cursor: pointer;
}
.db-list-item:hover { border-color: #cbd5e1; transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.04); }
.item-left { display: flex; align-items: center; gap: 16px; }
.item-icon { width: 48px; height: 48px; border-radius: 12px; background: #f1f5f9; color: #475569; display: flex; align-items: center; justify-content: center; }
.item-icon.green { background: #d1fae5; color: #10b981; }
.item-icon.orange { background: #ffedd5; color: #f97316; }
.item-icon.blue { background: #dbeafe; color: #2563eb; }
.db-avatar { width: 48px; height: 48px; border-radius: 50%; background: #fff7ed; color: #ea580c; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 800; border: 1px solid #ffedd5; }
.item-info h4 { margin: 0 0 4px; font-size: 15px; font-weight: 700; color: #1e293b; }
.item-meta { display: flex; align-items: center; gap: 10px; font-size: 12px; color: #64748b; }
.status-badge { background: #dbeafe; color: #2563eb; padding: 2px 8px; border-radius: 6px; font-weight: 600; font-size: 10px; }
.status-badge.success { background: #d1fae5; color: #10b981; }

.item-right { display: flex; align-items: center; gap: 8px; }
.db-action-btn { width: 36px; height: 36px; border-radius: 10px; border: 1px solid #e2e8f0; background: #fff; color: #64748b; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; }
.db-action-btn:hover { background: #ea580c; color: #fff; border-color: #ea580c; }
.db-action-btn.danger:hover { background: #ef4444; color: #fff; border-color: #ef4444; }

.db-btn-primary { background: linear-gradient(135deg, #ff7e1d, #ea580c); color: #fff; border: none; padding: 10px 18px; border-radius: 12px; font-weight: 700; font-size: 13px; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 12px rgba(234, 88, 12, 0.25); }
.db-btn-primary:hover { background: linear-gradient(135deg, #ea580c, #c2410c); transform: translateY(-1px); box-shadow: 0 6px 16px rgba(234, 88, 12, 0.35); }
.db-btn-primary.outline { background: transparent; color: #ea580c; border: 1.5px solid #ea580c; box-shadow: none; }
.db-btn-primary.outline:hover { background: #fff7ed; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(234, 88, 12, 0.1); }
.db-btn-primary.full { width: 100%; padding: 14px; font-size: 15px; margin-top: 10px; }

.db-action-btn.io-favori.aktif { color: #ea580c; background: #fff7ed; border-color: #ffedd5; position: static !important; }
.db-action-btn.io-favori:not(.aktif) { color: #64748b; background: #fff; border-color: #e2e8f0; position: static !important; }

/* FORMS */
.db-form-container { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 32px; }
.db-form { display: flex; flex-direction: column; gap: 20px; }
.db-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.db-form-group { display: flex; flex-direction: column; gap: 6px; }
.db-form-group label { font-size: 12px; font-weight: 700; color: #475569; }
.db-form-group input, .db-form-group textarea, .db-form-group select { padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 14px; outline: none; transition: 0.2s; background: #f8fafc; font-family: inherit; }
.db-form-group input:focus, .db-form-group textarea:focus, .db-form-group select:focus { border-color: #ea580c; background: #fff; box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.1); }
.db-form-group textarea { resize: vertical; min-height: 100px; }


.db-danger-zone { margin-top: 40px; padding-top: 24px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; }
.db-btn-danger { background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; padding: 10px 16px; border-radius: 10px; font-weight: 700; cursor: pointer; transition: 0.2s; font-size: 13px; display: flex; align-items: center; gap: 8px; }
.db-btn-danger:hover { background: #fee2e2; }

.db-empty-state { text-align: center; padding: 60px 20px; color: #94a3b8; }
.db-empty-state p { margin-top: 16px; font-size: 15px; font-weight: 500; }

.p-foto-menu { position: absolute; top: 100%; left: 0; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 100; display: none; min-width: 160px; padding: 4px; margin-top: 8px; }
.p-foto-menu.aktif { display: block; }
.p-menu-item { display: flex; align-items: center; gap: 8px; padding: 8px 12px; font-size: 13px; font-weight: 600; color: #475569; border-radius: 6px; cursor: pointer; }
.p-menu-item:hover { background: #f1f5f9; color: #1e293b; }
.p-menu-item.sil { color: #ef4444; }
.p-menu-item.sil:hover { background: #fef2f2; }

/* MESSAGING INTERFACE */
.msg-balon {
    max-width: 75%;
    padding: 10px 14px;
    border-radius: 14px;
    font-size: 14px;
    line-height: 1.5;
    position: relative;
}
.msg-balon.giden {
    background: #ea580c;
    color: #fff;
    align-self: flex-end;
    border-bottom-right-radius: 4px;
}
.msg-balon.gelen {
    background: #f1f5f9;
    color: #1e293b;
    align-self: flex-start;
    border-bottom-left-radius: 4px;
}
.msg-tarih {
    font-size: 11px;
    font-weight: 500;
}

/* --- PROFIL DASHBOARD DARK MODE OVERRIDES --- */
body.dark-mode .modal-icerik.buyuk.db-layout { background: #111827; }
body.dark-mode .db-sidebar { background: #1f2937; border-right-color: #374151; }
body.dark-mode .db-content { background: #111827; }
body.dark-mode .db-profile-info h2 { color: #f9fafb; }
body.dark-mode .db-nav-btn { color: #9ca3af; }
body.dark-mode .db-nav-btn:hover { background: #374151; color: #f9fafb; }
body.dark-mode .db-nav-btn.aktif { background: var(--primary); color: #fff; }
body.dark-mode .db-header h3 { color: #f9fafb; }
body.dark-mode .db-header p { color: #9ca3af; }
body.dark-mode .db-list-item { background: #1f2937; border-color: #374151; }
body.dark-mode .db-list-item:hover { background: #283548; border-color: #4b5563; }
body.dark-mode .item-icon { background: #374151; color: #d1d5db; }
body.dark-mode .item-icon.green { background: rgba(16, 185, 129, 0.15); color: #34d399; }
body.dark-mode .item-icon.orange { background: rgba(249, 115, 22, 0.15); color: #fb923c; }
body.dark-mode .item-icon.blue { background: rgba(37, 99, 235, 0.15); color: #60a5fa; }
body.dark-mode .item-info h4 { color: #f9fafb; }
body.dark-mode .item-meta { color: #9ca3af; }
body.dark-mode .db-action-btn { background: #374151; border-color: #4b5563; color: #d1d5db; }
body.dark-mode .db-action-btn:hover { background: var(--primary); border-color: var(--primary); color: #fff; }
body.dark-mode .db-action-btn.danger:hover { background: #ef4444; border-color: #ef4444; color: #fff; }
body.dark-mode .db-action-btn.io-favori:not(.aktif) { background: #374151; border-color: #4b5563; color: #d1d5db; }
body.dark-mode .db-action-btn.io-favori.aktif { background: rgba(249, 115, 22, 0.15); border-color: rgba(249, 115, 22, 0.3); color: #fb923c; }
body.dark-mode .db-form-container { background: #1f2937; border-color: #374151; }
body.dark-mode .db-form-container h4 { color: #f9fafb !important; border-bottom-color: #374151 !important; }
body.dark-mode .db-form-group label { color: #d1d5db; }
body.dark-mode .db-form-group input, 
body.dark-mode .db-form-group textarea, 
body.dark-mode .db-form-group select { background: #111827; border-color: #374151; color: #f9fafb; }
body.dark-mode .db-form-group input:focus, 
body.dark-mode .db-form-group textarea:focus, 
body.dark-mode .db-form-group select:focus { background: #1f2937; border-color: var(--primary); }
body.dark-mode .db-danger-zone { border-top-color: #374151; }
body.dark-mode .db-btn-danger { background: rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.2); color: #f87171; }
body.dark-mode .db-btn-danger:hover { background: rgba(239, 68, 68, 0.2); }
body.dark-mode .db-empty-state { color: #6b7280; }
body.dark-mode .p-foto-menu { background: #1f2937; border-color: #374151; }
body.dark-mode .p-menu-item { color: #d1d5db; }
body.dark-mode .p-menu-item:hover { background: #374151; color: #f9fafb; }
body.dark-mode .p-menu-item.sil { color: #f87171; }
body.dark-mode .p-menu-item.sil:hover { background: rgba(239, 68, 68, 0.1); }
body.dark-mode .modal-header-custom { background: #1f2937 !important; border-bottom-color: #374151 !important; }
body.dark-mode #chat_muhatap_ad { color: #f9fafb !important; }
body.dark-mode #chat_messages { background: #111827 !important; }
body.dark-mode .msg-balon.gelen { background: #374151; color: #f9fafb; }
body.dark-mode .msg-balon.giden { background: var(--primary); color: #fff; }
body.dark-mode .msg-tarih { color: #9ca3af !important; }
body.dark-mode #chat_messages + div { background: #1f2937 !important; border-top-color: #374151 !important; }
body.dark-mode #chat_input { background: #111827 !important; border-color: #374151 !important; color: #f9fafb !important; }

/* EK FORMLAR (.profil-kart, .iv-dropdown) İÇİN DARK MODE */
body.dark-mode .profil-kart { background: #1f2937; border-color: #374151; }
body.dark-mode .profil-kart-baslik { border-bottom-color: #374151; }
body.dark-mode .profil-kart-baslik h3 { color: #f9fafb; }
body.dark-mode .profil-input-satir label { color: #9ca3af; }
body.dark-mode .profil-input-grup input, 
body.dark-mode .profil-input-grup textarea, 
body.dark-mode .profil-input-grup select { background: #111827; border-color: #374151; color: #f9fafb; }
body.dark-mode .profil-input-grup input:focus, 
body.dark-mode .profil-input-grup textarea:focus, 
body.dark-mode .profil-input-grup select:focus { border-color: var(--primary); background: #1f2937; }
body.dark-mode .iv-dropdown .dropdown-trigger { background: #111827; border-color: #374151; }
body.dark-mode .iv-dropdown.active .dropdown-trigger { border-color: var(--primary); background: #1f2937; }
body.dark-mode .iv-dropdown .dropdown-trigger span { color: #f9fafb; }
body.dark-mode .iv-dropdown .dropdown-menu { background: #1f2937; border-color: #374151; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
body.dark-mode .iv-dropdown .dropdown-option { color: #d1d5db; }
body.dark-mode .iv-dropdown .dropdown-option:hover { background: #374151; color: var(--primary); }
body.dark-mode .iv-gun-chip span { background: #111827; border-color: #374151; color: #9ca3af; }
body.dark-mode .iv-gun-chip span:hover { border-color: var(--primary); color: var(--primary); }
body.dark-mode .iv-gun-chip input[type="checkbox"]:checked + span { background: rgba(234, 88, 12, 0.15); border-color: var(--primary); color: var(--primary); }

  /* Ek Düzeltmeler */
  .db-sub-menu {
      display: none;
      flex-direction: column;
      padding-left: 36px;
      gap: 4px;
      margin-top: -2px;
      margin-bottom: 8px;
  }
  .db-nav-btn.aktif + .db-sub-menu {
      display: flex;
  }

  .db-nav-btn-alt {
      padding: 8px 16px;
      border: none;
      background: transparent;
      color: #64748b;
      font-size: 13px;
      font-weight: 600;
      text-align: left;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.2s;
  }
  .db-nav-btn-alt:hover { background: #f1f5f9; color: #1e293b; }
  .db-nav-btn-alt.aktif { background: #fff7ed; color: var(--primary); }
  body.dark-mode .db-nav-btn-alt { color: #9ca3af; }
  body.dark-mode .db-nav-btn-alt:hover { background: #374151; color: #f9fafb; }
  body.dark-mode .db-nav-btn-alt.aktif { background: rgba(234, 88, 12, 0.15); color: #fb923c; }

  /* Kullanıcı geri bildirimlerine istinaden düzeltmeler */
  body.dark-mode .db-rol-etiket { background: rgba(234, 88, 12, 0.15) !important; color: #fb923c !important; }
  body.dark-mode .status-badge { background: rgba(37, 99, 235, 0.15) !important; color: #60a5fa !important; }
  body.dark-mode .status-badge.success { background: rgba(16, 185, 129, 0.15) !important; color: #34d399 !important; }
  body.dark-mode .db-btn-primary.outline:hover { background: rgba(234, 88, 12, 0.15) !important; }
  
  body.dark-mode .confirmModal { background: #1f2937 !important; border: 1px solid #374151 !important; box-shadow: 0 10px 25px rgba(0,0,0,0.5) !important; }
  body.dark-mode .confirmModal p { color: #e5e7eb !important; }
  body.dark-mode .confirmModal .iptal-btn { background: #374151 !important; color: #e5e7eb !important; border: 1px solid #4b5563 !important; }
  body.dark-mode .confirmModal .iptal-btn:hover { background: #4b5563 !important; }
  body.dark-mode .red-neden-kutusu { background: rgba(239, 68, 68, 0.1) !important; color: #fca5a5 !important; border-left-color: #ef4444 !important; }
  body.dark-mode .red-neden-kutusu strong { color: #f87171 !important; }
  body.dark-mode #confirmModal .modal-icerik { background: #1f2937 !important; border: 1px solid #374151 !important; }
  body.dark-mode #confirmModal h3 { color: #f9fafb !important; }
  body.dark-mode #confirmModal p { color: #9ca3af !important; }
  body.dark-mode #confirmModal button[style*="background:#f1f5f9"] { background: #374151 !important; color: #d1d5db !important; }
  body.dark-mode #confirmModal button[style*="background:#f1f5f9"]:hover { background: #4b5563 !important; color: #f9fafb !important; }
</style>

<script>
// Dropdown Verilerini JS'ye Aktar
const pData = {
    sektorler: <?php echo json_encode($sektorler); ?>,
    calismaturleri: <?php echo json_encode($calismaturleri); ?>,
    yanhaklar: <?php echo json_encode($yanhaklar); ?>,
    iller: <?php echo json_encode($iller); ?>
};

function ilanDuzenleAc(id) {
    modalAc('ilanDuzenleModal');
    const form = document.getElementById('ilanDuzenleForm');
    form.innerHTML = '<div class="p-bos-uyari">İlan bilgileri alınıyor...</div>';
    
    const fd = new FormData();
    fd.append('islem', 'ilan_getir');
    fd.append('id', id);

    fetch('islem.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(i => {
            // Mevcut Günleri Bul
            const currentDays = i.calismagunleri ? i.calismagunleri.split(', ') : [];
            const gunler = ['Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi','Pazar'];
            
            // Saatleri Ayrıştır
            let s_bas = '09:00', s_bit = '18:00';
            if(i.calismasaatleri && i.calismasaatleri.includes(' - ')) {
                const p = i.calismasaatleri.split(' - ');
                s_bas = p[0]; s_bit = p[1];
            }

            form.innerHTML = `
                <input type="hidden" name="islem" value="ilan_duzenle">
                <input type="hidden" name="ilan_id" value="${i.ilanID}">
                
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
                                <input type="text" name="baslik" value="${i.baslik}" required>
                            </div>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                            <div class="profil-input-satir">
                                <label><svg width="14" height="14"><use xlink:href="#icon-company"></use></svg> SEKTÖR</label>
                                <div class="profil-input-grup">
                                    <div class="iv-dropdown" id="ed_sektorDropdown">
                                        <div class="dropdown-trigger">
                                            <span>${pData.sektorler.find(s=>s.sektorID==i.isektorID)?.sektorad || 'Sektör Seçin'}</span>
                                            <svg width="14" height="14"><use xlink:href="#icon-chevron"></use></svg>
                                        </div>
                                        <div class="dropdown-menu">
                                            ${pData.sektorler.map(s => `<div class="dropdown-option" data-value="${s.sektorID}">${s.sektorad}</div>`).join('')}
                                        </div>
                                        <input type="hidden" name="sektor_id" id="ed_sektor_input" value="${i.isektorID}" required>
                                    </div>
                                </div>
                            </div>
                            <div class="profil-input-satir">
                                <label><svg width="14" height="14"><use xlink:href="#icon-briefcase"></use></svg> ÇALIŞMA TÜRÜ</label>
                                <div class="profil-input-grup">
                                    <div class="iv-dropdown" id="ed_calismaturDropdown">
                                        <div class="dropdown-trigger">
                                            <span>${pData.calismaturleri.find(ct=>ct.calismaID==i.icalismaturID)?.calismatur || 'Seçiniz'}</span>
                                            <svg width="14" height="14"><use xlink:href="#icon-chevron"></use></svg>
                                        </div>
                                        <div class="dropdown-menu">
                                            ${pData.calismaturleri.map(ct => `<div class="dropdown-option" data-value="${ct.calismaID}">${ct.calismatur}</div>`).join('')}
                                        </div>
                                        <input type="hidden" name="calismatur_id" id="ed_calismatur_input" value="${i.icalismaturID}" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                            <div class="profil-input-satir">
                                <label><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg> MAAŞ (TL)</label>
                                <div class="profil-input-grup">
                                    <input type="number" name="maas" value="${i.maas}" required>
                                </div>
                            </div>
                            <div class="profil-input-satir">
                                <label><svg width="14" height="14"><use xlink:href="#icon-info"></use></svg> YAN HAKLAR</label>
                                <div class="profil-input-grup">
                                    <div class="iv-dropdown" id="ed_yanhakDropdown">
                                        <div class="dropdown-trigger">
                                            <span>${pData.yanhaklar.find(yh=>yh.yanhakID==i.iyanhakID)?.yanhak || 'Seçiniz'}</span>
                                            <svg width="14" height="14"><use xlink:href="#icon-chevron"></use></svg>
                                        </div>
                                        <div class="dropdown-menu">
                                            ${pData.yanhaklar.map(yh => `<div class="dropdown-option" data-value="${yh.yanhakID}">${yh.yanhak}</div>`).join('')}
                                        </div>
                                        <input type="hidden" name="yanhak_id" id="ed_yanhak_input" value="${i.iyanhakID}" required>
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
                        <div class="profil-input-satir">
                            <label><svg width="14" height="14"><use xlink:href="#icon-location"></use></svg> AÇIK ADRES</label>
                            <div class="profil-input-grup">
                                <input type="text" name="acikadres" value="${i.acikadres}" required>
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
                                ${gunler.map(g => `
                                    <label class="iv-gun-chip">
                                        <input type="checkbox" name="calismagunleri[]" value="${g}" ${currentDays.includes(g) ? 'checked' : ''}>
                                        <span>${g}</span>
                                    </label>
                                `).join('')}
                            </div>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-top:12px;">
                            <div class="profil-input-satir">
                                <label>BAŞLANGIÇ SAATİ</label>
                                <div class="profil-input-grup">
                                    <input type="time" name="saat_baslangic" value="${s_bas}">
                                </div>
                            </div>
                            <div class="profil-input-satir">
                                <label>BİTİŞ SAATİ</label>
                                <div class="profil-input-grup">
                                    <input type="time" name="saat_bitis" value="${s_bit}">
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
                            <textarea name="aciklama" required style="min-height:140px;">${i.aciklama}</textarea>
                        </div>
                    </div>
                </div>

                <button type="submit" class="profil-kaydet-btn" id="ed_yayinla_btn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    Değişiklikleri Kaydet
                </button>
            `;
            
            if(typeof initIVDropdown === 'function') {
                initIVDropdown('ed_sektorDropdown', 'ed_sektor_input');
                initIVDropdown('ed_calismaturDropdown', 'ed_calismatur_input');
                initIVDropdown('ed_yanhakDropdown', 'ed_yanhak_input');
            }
        });
}

function ilanGuncelle(e) {
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    const fd = new FormData(e.target);
    btn.disabled = true; btn.innerText = 'Güncelleniyor...';
    
    fetch('islem.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if(d.durum === 'basarili') {
                showToast('İlan güncellendi ');
                modalKapat('ilanDuzenleModal');
                // Kartı güncelle
                const kart = document.getElementById('pm_ilan_' + fd.get('ilan_id'));
                if(kart) {
                    kart.querySelector('h4').innerText = fd.get('baslik');
                    
                    const reddedilenListe = document.getElementById('ilan_reddedilen');
                    if(reddedilenListe && reddedilenListe.contains(kart)) {
                        const bekleyenListe = document.getElementById('ilan_bekleyen');
                        if(bekleyenListe) {
                            bekleyenListe.prepend(kart);
                            
                            const iconDiv = kart.querySelector('.item-icon');
                            if(iconDiv) {
                                iconDiv.style.background = '';
                                iconDiv.style.color = '';
                                iconDiv.className = 'item-icon orange';
                                iconDiv.innerHTML = '<svg width="24" height="24"><use xlink:href="#icon-list-3d"></use></svg>';
                            }
                            
                            const badge = kart.querySelector('.status-badge');
                            if(badge) {
                                badge.className = 'status-badge';
                                badge.style.background = 'rgba(249,115,22,0.15)';
                                badge.style.color = '#fb923c';
                                badge.innerText = 'Onay Bekliyor';
                            }
                            
                            const redNedeni = kart.querySelector('.red-neden-kutusu');
                            if(redNedeni) redNedeni.remove();
                            
                            if(typeof guncelleIlanSayilari === 'function') guncelleIlanSayilari();
                        }
                    }
                }
            } else {
                showToast(d.hata || 'Hata oluştu ❌', 'error');
                btn.disabled = false; btn.innerText = 'Değişiklikleri Kaydet';
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Bağlantı hatası oluştu', 'error');
            btn.disabled = false; btn.innerText = 'Değişiklikleri Kaydet';
        });
    return false;
}

function pmSekme(ad, btn) {
    document.querySelectorAll('.pm-icerik').forEach(s => s.classList.remove('aktif'));
    document.querySelectorAll('.db-nav-btn').forEach(s => s.classList.remove('aktif'));
    const h = document.getElementById('pm-sekme-' + ad);
    if(h) h.classList.add('aktif');
    if(btn) btn.classList.add('aktif');
}

function toggleFotoMenu(e) {
    e.stopPropagation();
    document.getElementById('foto_menu').classList.toggle('aktif');
}
document.addEventListener('click', () => { 
    const m = document.getElementById('foto_menu');
    if(m) m.classList.remove('aktif');
});

function profilFotoYukle(input) {
    if(!input.files || !input.files[0]) return;
    const fd = new FormData();
    fd.append('islem', 'profil_foto_yukle');
    fd.append('profil_foto', input.files[0]);

    showToast('Fotoğraf yükleniyor...');
    fetch('islem.php', { 
        method: 'POST', 
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(d => {
        if(d.durum === 'basarili') {
            const url = 'uploads/' + d.yeni_foto;
            document.querySelectorAll('.p-ana-foto, .p-ana-foto-bos').forEach(el => {
                if(el.tagName === 'IMG') el.src = url;
                else {
                    const img = document.createElement('img');
                    img.src = url;
                    img.className = 'p-ana-foto';
                    el.replaceWith(img);
                }
            });
            showToast('Fotoğraf güncellendi');
        } else {
            showToast(d.hata || 'Yükleme başarısız', 'error');
        }
    });
}

function profilFotoSil() {
    gOnay('Fotoğrafı Kaldır', 'Profil fotoğrafınızı silmek istediğinize emin misiniz?', function() {
        const fd = new FormData(); 
        fd.append('islem', 'profil_foto_sil');
        fetch('islem.php', { 
            method: 'POST', 
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(d => {
            if(d.durum === 'basarili') {
                const bos = document.createElement('div');
                bos.className = 'p-ana-foto-bos';
                bos.innerHTML = '<svg width="40" height="40"><use xlink:href="#icon-company"></use></svg>';
                document.querySelectorAll('.p-ana-foto').forEach(el => el.replaceWith(bos.cloneNode(true)));
                showToast('Fotoğraf kaldırıldı ');
            }
        });
    });
}

function profilGuncelle(e) {
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    const fd = new FormData(e.target);
    btn.disabled = true; btn.innerText = 'Kaydediliyor...';
    
    fetch('islem.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if(d.durum === 'basarili') { 
                showToast('Profil başarıyla güncellendi '); 
                const yeniAd = fd.get('firmaadi') || fd.get('adsoyad');
                if(yeniAd) document.querySelector('.p-isim-alani h2').innerText = yeniAd;
            } else { 
                showToast(d.hata || 'Hata oluştu', 'error'); 
            }
            btn.disabled = false; btn.innerText = 'Değişiklikleri Kaydet';
        })
        .catch(err => {
            console.error("Hata:", err);
            showToast('İşlem sırasında bir hata oluştu', 'error');
            btn.disabled = false; btn.innerText = 'Değişiklikleri Kaydet';
        });
    return false;
}

function ilanSil(id) {
    gOnay('İlanı Sil', 'Bu ilanı kalıcı olarak silmek istediğinize emin misiniz?', function() {
        const fd = new FormData();
        fd.append('islem', 'ilan_sil');
        fd.append('ilan_id', id); // backend ilan_id bekliyor
        
        fetch('islem.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if(d.durum === 'basarili') { 
                    showToast('İlan başarıyla silindi ');
                    const kart = document.getElementById('pm_ilan_'+id);
                    if(kart) {
                        kart.style.opacity = '0';
                        setTimeout(() => { 
                            kart.remove(); 
                            if(typeof guncelleIlanSayilari === 'function') guncelleIlanSayilari();
                        }, 300);
                    }
                }
                else { showToast(d.hata || 'Bir hata oluştu ❌', 'error'); }
            });
    });
}

async function profilIlanKaydetToggle(btn) {
    const id = btn.dataset.id;
    const isSil = btn.classList.contains('aktif');
    const islem = isSil ? 'kayit_sil' : 'kaydet';
    const newValue = !isSil;
    
    // Anında UI
    if(typeof syncSavedStateAcrossDOM === 'function') {
        syncSavedStateAcrossDOM(id, newValue, 'İlan', 'Firma', new Date().toLocaleDateString('tr-TR'));
    }

    try {
        const fd = new FormData();
        fd.append('islem', islem);
        fd.append('ilan_id', id);
        const res = await fetch('islem.php', { method: 'POST', body: fd });
        const data = await res.json();
        if(data.durum !== 'basarili') throw new Error(data.hata);
    } catch(err) {
        // Geri al
        if(typeof syncSavedStateAcrossDOM === 'function') {
            syncSavedStateAcrossDOM(id, !newValue, 'İlan', 'Firma', new Date().toLocaleDateString('tr-TR'));
        }
        if(typeof showToast === 'function') showToast('Bir hata oluştu', 'error');
    }
}


function ilanKaydiSil(ilanID) {
    gOnay('İlanı Kaldır', 'Bu ilanı kaydedilenlerden çıkarmak istediğinize emin misiniz?', function() {
        const fd = new FormData();
        fd.append('islem', 'kayit_sil');
        fd.append('ilan_id', ilanID);
        
        fetch('islem.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if(d.durum === 'basarili') {
                    showToast('İlan kaydedilenlerden çıkarıldı');
                    const kart = document.getElementById('pm_kayit_' + ilanID);
                    if(kart) {
                        kart.style.opacity = '0';
                        setTimeout(() => { kart.remove(); }, 300);
                    }
                    if(typeof syncSavedStateAcrossDOM === 'function') {
                        syncSavedStateAcrossDOM(ilanID, false);
                    }
                }
            });
    });
}
let mesajInterval = null;
let activeChatMuhatap = null;

function pmMesajYukle() {
    const liste = document.getElementById('pm_mesaj_liste');
    // Eğer liste boşsa yükleniyor yazısı kalsın, değilse dokunmayalım (titremeyi önlemek için)
    if(liste.innerHTML === '') liste.innerHTML = '<div class="p-bos-uyari">Mesajlar yükleniyor...</div>';
    
    const fd = new FormData(); fd.append('islem', 'mesaj_yukle');
    fetch('islem.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if(data.length === 0) {
                liste.innerHTML = '<div class="p-bos-uyari">Henüz bir mesajınız bulunmuyor.</div>';
                return;
            }
            
            let html = '';
            let unreadTotal = 0;
            data.forEach(m => {
                const isUnread = m.okundu == 0 && m.aliciID == <?php echo $_SESSION['kullaniciID']; ?>;
                const initial = m.muhatap_ad.charAt(0);
                const avatar = m.fotograf 
                    ? `<img src="uploads/${m.fotograf}" style="width:45px; height:45px; border-radius:50%; object-fit:cover;">` 
                    : `<div style="width:45px; height:45px; border-radius:50%; background:#fff7ed; color:#ea580c; border:1px solid #ffedd5; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:18px;">${initial}</div>`;
                
                html += `
                    <div class="db-list-item" onclick="mesajPenceresiAc(${m.muhatap_id}, '${m.muhatap_ad}', '${m.fotograf ? 'uploads/'+m.fotograf : ''}')" style="${activeChatMuhatap == m.muhatap_id ? 'border-color:#ea580c; background:#fff7ed;' : ''}">
                        <div class="item-left">
                            ${avatar}
                            <div class="item-info">
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <h4 style="margin:0; font-size:14px; font-weight:${isUnread ? '800' : '700'};">${m.muhatap_ad}</h4>
                                    ${isUnread ? '<div style="width:8px; height:8px; background:#ea580c; border-radius:50%;"></div>' : ''}
                                </div>
                                <div class="item-meta">
                                    <span style="font-size:13px; color:${isUnread ? '#1e293b' : '#64748b'}; font-weight:${isUnread ? '600' : '400'}; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        ${m.gonderenID == <?php echo $_SESSION['kullaniciID']; ?> ? 'Siz: ' : ''}${m.mesajmetni}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="item-right">
                            <span class="pm-tarih" style="font-size:11px; color:#94a3b8; font-weight:500;">${new Date(m.tarih).toLocaleDateString('tr-TR')}</span>
                            <button class="db-action-btn danger" onclick="event.stopPropagation(); sohbetiSil(${m.muhatap_id})" title="Sohbeti Sil">
                                <svg width="14" height="14"><use xlink:href="#icon-trash-premium"></use></svg>
                            </button>
                        </div>
                    </div>
                `;
            });
            liste.innerHTML = html;
            
            // Unread Badge Güncelle (Sekme Üstündeki)
            const badge = document.getElementById('unread_badge');
            if(badge) {
                badge.innerText = unreadTotal;
                badge.style.display = unreadTotal > 0 ? 'flex' : 'none';
            }
        });
}

// Periyodik kontrol başlat
function ilanDurumlariniKontrolEt() {
    const fd = new FormData();
    fd.append('islem', 'ilan_durum_kontrol');
    fetch('islem.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if(!data || data.length === 0) return;
            let degisiklikOldu = false;
            data.forEach(ilan => {
                const kart = document.getElementById('pm_ilan_' + ilan.ilanID);
                if(kart) {
                    const lAktif = document.getElementById('ilan_aktif');
                    const lBekleyen = document.getElementById('ilan_bekleyen');
                    const lReddedilen = document.getElementById('ilan_reddedilen');
                    if(!lAktif || !lBekleyen || !lReddedilen) return;
                    
                    const aktifte = lAktif.contains(kart);
                    const bekleyende = lBekleyen.contains(kart);
                    const reddedilende = lReddedilen.contains(kart);
                    
                    let guncelListe = '';
                    if(ilan.idurumID == 1) guncelListe = 'aktif';
                    else if(ilan.idurumID == 2) guncelListe = 'reddedilen';
                    else guncelListe = 'bekleyen';
                    
                    if((guncelListe === 'aktif' && !aktifte) || 
                       (guncelListe === 'bekleyen' && !bekleyende) || 
                       (guncelListe === 'reddedilen' && !reddedilende)) {
                        
                        document.getElementById('ilan_' + guncelListe).prepend(kart);
                        degisiklikOldu = true;
                        
                        const iconDiv = kart.querySelector('.item-icon');
                        const badge = kart.querySelector('.status-badge');
                        let redKutusu = kart.querySelector('.red-neden-kutusu');
                        
                        if(guncelListe === 'aktif') {
                            if(iconDiv) { iconDiv.className = 'item-icon green'; iconDiv.style.background = 'rgba(16,185,129,0.15)'; iconDiv.style.color = '#10b981'; iconDiv.innerHTML = '<svg width="24" height="24"><use xlink:href="#icon-list-3d"></use></svg>'; }
                            if(badge) { badge.className = 'status-badge success'; badge.style.background = ''; badge.style.color = ''; badge.innerText = 'Aktif'; }
                            if(redKutusu) redKutusu.remove();
                        } 
                        else if(guncelListe === 'bekleyen') {
                            if(iconDiv) { iconDiv.className = 'item-icon orange'; iconDiv.style.background = ''; iconDiv.style.color = ''; iconDiv.innerHTML = '<svg width="24" height="24"><use xlink:href="#icon-list-3d"></use></svg>'; }
                            if(badge) { badge.className = 'status-badge'; badge.style.background = 'rgba(249,115,22,0.15)'; badge.style.color = '#fb923c'; badge.innerText = 'Onay Bekliyor'; }
                            if(redKutusu) redKutusu.remove();
                        }
                        else if(guncelListe === 'reddedilen') {
                            if(iconDiv) { iconDiv.className = 'item-icon'; iconDiv.style.background = 'rgba(239,68,68,0.15)'; iconDiv.style.color = '#ef4444'; iconDiv.innerHTML = '<svg width="24" height="24"><use xlink:href="#icon-error-3d"></use></svg>'; }
                            if(badge) { badge.className = 'status-badge'; badge.style.background = 'rgba(239,68,68,0.15)'; badge.style.color = '#ef4444'; badge.innerText = 'Reddedildi'; }
                            
                            if(!redKutusu && ilan.red_nedeni) {
                                const infoDiv = kart.querySelector('.item-info');
                                if(infoDiv) {
                                    redKutusu = document.createElement('div');
                                    redKutusu.className = 'red-neden-kutusu';
                                    redKutusu.style = "margin-top: 10px; padding: 10px 14px; background: #fef2f2; border-left: 4px solid #ef4444; border-radius: 6px; font-size: 12px; color: #991b1b; line-height:1.5;";
                                    redKutusu.innerHTML = `<strong>Red Nedeni: </strong> ${ilan.red_nedeni}`;
                                    infoDiv.appendChild(redKutusu);
                                }
                            } else if (redKutusu && ilan.red_nedeni) {
                                redKutusu.innerHTML = `<strong>Red Nedeni: </strong> ${ilan.red_nedeni}`;
                            }
                        }
                    }
                }
            });
            if(degisiklikOldu && typeof guncelleIlanSayilari === 'function') {
                guncelleIlanSayilari();
            }
        });
}

if(!mesajInterval) {
    mesajInterval = setInterval(() => {
        pmMesajYukle();
        if(activeChatMuhatap) mesajPenceresiYenile();
        if(document.getElementById('pm-sekme-ilanlarim') && document.getElementById('pm-sekme-ilanlarim').classList.contains('aktif')) {
            ilanDurumlariniKontrolEt();
        }
    }, 2000); // 2 saniyede bir kontrol et (Daha anlık)
}

function mesajPenceresiAc(muhatapID, muhatapAd, muhatapFoto) {
    activeChatMuhatap = muhatapID;
    document.getElementById('chat_muhatap_ad').innerText = muhatapAd;
    const fotoEl = document.getElementById('chat_muhatap_foto');
    const headerDiv = fotoEl.parentElement;
    
    if(muhatapFoto) {
        fotoEl.src = muhatapFoto;
        fotoEl.style.display = 'block';
        if(document.getElementById('chat_muhatap_initial')) document.getElementById('chat_muhatap_initial').remove();
    } else {
        fotoEl.style.display = 'none';
        if(!document.getElementById('chat_muhatap_initial')) {
            const initial = document.createElement('div');
            initial.id = 'chat_muhatap_initial';
            initial.style = "width:36px; height:36px; border-radius:50%; background:#fff7ed; color:#ea580c; border:1px solid #ffedd5; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:14px;";
            initial.innerText = muhatapAd.charAt(0);
            headerDiv.insertBefore(initial, document.getElementById('chat_muhatap_ad'));
        } else {
            document.getElementById('chat_muhatap_initial').innerText = muhatapAd.charAt(0);
        }
    }
    document.getElementById('chat_alici_id').value = muhatapID;
    document.getElementById('chat_messages').innerHTML = '<div class="p-bos-uyari">Yükleniyor...</div>';
    modalAc('mesajPenceresiModal');
    mesajPenceresiYenile(true); // true = otomatik scroll yap
}

function mesajPenceresiYenile(forceScroll = false) {
    if(!activeChatMuhatap) return;
    
    const fd = new FormData();
    fd.append('islem', 'mesaj_detay_getir');
    fd.append('muhatap_id', activeChatMuhatap);
    
    fetch('islem.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            const box = document.getElementById('chat_messages');
            let html = '';
            data.forEach(m => {
                const isMine = m.gonderenID == <?php echo $_SESSION['kullaniciID']; ?>;
                const status = isMine ? (m.okundu == 1 ? '✓✓' : '✓') : '';
                html += `
                    <div class="msg-balon ${isMine ? 'giden' : 'gelen'}">
                        ${m.mesajmetni}
                        <div style="display:flex; justify-content:flex-end; align-items:center; gap:4px; margin-top:4px;">
                            <span class="msg-tarih" style="margin:0; opacity:0.8;">${new Date(m.tarih).toLocaleTimeString('tr-TR', {hour:'2-digit', minute:'2-digit'})}</span>
                            ${isMine ? `<span style="font-size:11px; opacity:0.9;">${status}</span>` : ''}
                        </div>
                    </div>
                `;
            });
            
            // Sadece içerik değiştiyse güncelle ki titremesin
            if(box.innerHTML !== html) {
                box.innerHTML = html;
                box.scrollTop = box.scrollHeight;
            } else if(forceScroll) {
                box.scrollTop = box.scrollHeight;
            }
        });
}

function adayProfiliGoster(basvuruID) {
    modalAc('adayDetayModal');
    const content = document.getElementById('aday_modal_content');
    content.innerHTML = '<div class="p-bos-uyari">Aday bilgileri yükleniyor...</div>';
    
    const fd = new FormData();
    fd.append('islem', 'aday_getir');
    fd.append('basvuru_id', basvuruID);
    
    fetch('islem.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if(d.hata) {
                content.innerHTML = `<div class="p-bos-uyari">${d.hata}</div>`;
                return;
            }
            const yas = d.dogumyili ? (new Date().getFullYear() - d.dogumyili) : '?';
            content.innerHTML = `
                <div style="text-align:center; margin-bottom:24px;">
                    <div style="width:80px; height:80px; background:#fff7ed; color:#ea580c; border:1px solid #ffedd5; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 15px; font-size:32px; font-weight:800;">
                        ${d.adsoyad.charAt(0)}
                    </div>
                    <h3 style="margin:0; font-size:22px; font-weight:800; color:#1e293b;">${d.adsoyad}</h3>
                    <p style="margin:5px 0 0; color:#64748b; font-size:14px;">${d.ilan_baslik} Adayı</p>
                </div>
                
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:24px;">
                    <div style="background:#f8fafc; padding:12px; border-radius:10px;">
                        <label style="font-size:10px; text-transform:uppercase; font-weight:800; color:#94a3b8; display:block; margin-bottom:4px;">Kişisel Bilgiler</label>
                        <div style="font-size:13px; font-weight:700; color:#1e293b;">${yas} Yaş, ${d.cinsiyet}</div>
                    </div>
                    <div style="background:#f8fafc; padding:12px; border-radius:10px;">
                        <label style="font-size:10px; text-transform:uppercase; font-weight:800; color:#94a3b8; display:block; margin-bottom:4px;">Eğitim Durumu</label>
                        <div style="font-size:13px; font-weight:700; color:#1e293b;">${d.egitim || 'Belirtilmemiş'}</div>
                    </div>
                    <div style="background:#f8fafc; padding:12px; border-radius:10px;">
                        <label style="font-size:10px; text-transform:uppercase; font-weight:800; color:#94a3b8; display:block; margin-bottom:4px;">İletişim</label>
                        <div style="font-size:13px; font-weight:700; color:#1e293b;">${d.telno}</div>
                        <div style="font-size:11px; color:#64748b;">${d.email}</div>
                    </div>
                    <div style="background:#f8fafc; padding:12px; border-radius:10px;">
                        <label style="font-size:10px; text-transform:uppercase; font-weight:800; color:#94a3b8; display:block; margin-bottom:4px;">Ehliyet & Askerlik</label>
                        <div style="font-size:13px; font-weight:700; color:#1e293b;">${d.ehliyet || 'Yok'} / ${d.askerlik || 'Muaf/Yapıldı'}</div>
                    </div>
                </div>

                <div style="margin-bottom:20px;">
                    <label style="font-size:11px; text-transform:uppercase; font-weight:800; color:#94a3b8; display:block; margin-bottom:8px;">İş Tecrübesi</label>
                    <div style="font-size:14px; line-height:1.6; color:#1e293b; background:#f1f5f9; padding:16px; border-radius:12px; border-left:4px solid #ea580c;">
                        ${d.is_tecrubesi || 'İş tecrübesi bilgisi girilmemiş.'}
                    </div>
                </div>
                
                <div style="margin-bottom:24px;">
                    <label style="font-size:11px; text-transform:uppercase; font-weight:800; color:#94a3b8; display:block; margin-bottom:8px;">Aday Hakkında</label>
                    <div style="font-size:13px; line-height:1.6; color:#475569;">
                        ${d.hakkimda || 'Aday kendini tanıtan bir bilgi girmemiş.'}
                    </div>
                </div>
            `;
        });
}

function firmaProfiliGoster(ilanID) {
    modalAc('firmaDetayModal');
    const content = document.getElementById('firma_modal_content');
    content.innerHTML = '<div class="p-bos-uyari">Firma bilgileri yükleniyor...</div>';
    
    const fd = new FormData();
    fd.append('islem', 'isveren_getir');
    fd.append('ilan_id', ilanID);
    
    fetch('islem.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if(d.hata) {
                content.innerHTML = `<div class="p-bos-uyari">${d.hata}</div>`;
                return;
            }
            content.innerHTML = `
                <div style="text-align:center; margin-bottom:24px;">
                    <div style="width:80px; height:80px; background:#f8fafc; color:#3b82f6; border:1px solid #e2e8f0; border-radius:16px; display:flex; align-items:center; justify-content:center; margin:0 auto 15px; box-shadow:0 4px 10px rgba(0,0,0,0.05);">
                        <svg width="40" height="40"><use xlink:href="#icon-company"></use></svg>
                    </div>
                    <h3 style="margin:0; font-size:22px; font-weight:800; color:#1e293b;">${d.firmaadi}</h3>
                    <p style="margin:5px 0 0; color:#64748b; font-size:14px; display:flex; align-items:center; justify-content:center; gap:4px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        Doğrulanmış İşveren
                    </p>
                </div>
                
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:24px;">
                    <div style="background:#f8fafc; padding:12px; border-radius:10px;">
                        <label style="font-size:10px; text-transform:uppercase; font-weight:800; color:#94a3b8; display:block; margin-bottom:4px;">Kayıt Tarihi</label>
                        <div style="font-size:13px; font-weight:700; color:#1e293b;">${d.kayit_tarihi_formatli}</div>
                    </div>
                    <div style="background:#f8fafc; padding:12px; border-radius:10px;">
                        <label style="font-size:10px; text-transform:uppercase; font-weight:800; color:#94a3b8; display:block; margin-bottom:4px;">Aktif İlan Sayısı</label>
                        <div style="font-size:13px; font-weight:700; color:#1e293b;">${d.toplam_ilan} İlan</div>
                    </div>
                    <div style="background:#f8fafc; padding:12px; border-radius:10px; grid-column: 1 / -1;">
                        <label style="font-size:10px; text-transform:uppercase; font-weight:800; color:#94a3b8; display:block; margin-bottom:4px;">Merkez Adresi</label>
                        <div style="font-size:13px; font-weight:700; color:#1e293b; line-height:1.4;">${d.acikadres}</div>
                    </div>
                </div>

                <div style="margin-bottom:24px;">
                    <label style="font-size:11px; text-transform:uppercase; font-weight:800; color:#94a3b8; display:block; margin-bottom:8px;">Firma Hakkında</label>
                    <div style="font-size:14px; line-height:1.6; color:#475569; background:#fff; padding:16px; border-radius:12px; border:1px solid #e2e8f0;">
                        ${d.hakkimda || 'Firma hakkında detaylı bilgi bulunmamaktadır.'}
                    </div>
                </div>
            `;
        });
}

function mesajGonder(e) {
    e.preventDefault();
    const aliciID = document.getElementById('chat_alici_id').value;
    const input = document.getElementById('chat_input');
    const msg = input.value.trim();
    if(!msg) return false;
    
    const fd = new FormData();
    fd.append('islem', 'mesaj_gonder');
    fd.append('alici_id', aliciID);
    fd.append('mesaj', msg);
    
    input.value = '';
    fetch('islem.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if(d.durum === 'basarili') {
                mesajPenceresiYenile(true);
                pmMesajYukle();
            }
        });
    return false;
}

function sohbetiSil(muhatapID) {
    if(!muhatapID) return;
    gOnay('Sohbeti Sil', 'Bu kişiyle olan tüm mesajlaşma geçmişinizi silmek istediğinize emin misiniz?', function() {
        const fd = new FormData();
        fd.append('islem', 'sohbeti_sil');
        fd.append('muhatap_id', muhatapID);
        
        fetch('islem.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if(d.durum === 'basarili') {
                    showToast('Sohbet başarıyla silindi ');
                    if(activeChatMuhatap == muhatapID) {
                        mesajPenceresiKapat();
                    }
                    pmMesajYukle();
                } else {
                    showToast(d.hata || 'Bir hata oluştu', 'error');
                }
            });
    });
}

function hesabiSil() {
    gOnay('Hesabı Kalıcı Olarak Sil', 'Hesabınızı kalıcı olarak silmek istediğinize emin misiniz? Bu işlem geri alınamaz ve tüm verileriniz kalıcı olarak silinecektir.', function() {
        const fd = new FormData();
        fd.append('islem', 'hesabi_sil');
        
        fetch('islem.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if(d.durum === 'basarili') {
                    showToast('Hesabınız başarıyla silindi. Yönlendiriliyorsunuz...');
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1500);
                } else {
                    showToast(d.hata || 'Bir hata oluştu', 'error');
                }
            });
    });
}

function pmAltSekme(id, btn) {
    document.querySelectorAll('.pm-alt-liste').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.db-nav-btn-alt').forEach(el => el.classList.remove('aktif'));
    document.querySelectorAll('.p-sekme').forEach(el => el.classList.remove('aktif'));
    
    document.getElementById(id).style.display = 'flex';
    if(document.getElementById('nav_' + id)) document.getElementById('nav_' + id).classList.add('aktif');
    if(document.getElementById('tab_' + id)) document.getElementById('tab_' + id).classList.add('aktif');
}

function pmAltSekmeTetikle() {
    // Sadece ilanlarım açılınca ilk alt sekmeyi aktif et
    if(document.getElementById('pm-sekme-ilanlarim') && document.getElementById('pm-sekme-ilanlarim').classList.contains('aktif')) {
        // Eğer hiçbir alt sekme aktif değilse, aktif'i seç
        const hasActive = document.querySelector('.db-nav-btn-alt.aktif');
        if(!hasActive && document.getElementById('nav_ilan_aktif')) pmAltSekme('ilan_aktif', document.getElementById('nav_ilan_aktif'));
    }
}

function guncelleIlanSayilari() {
    const lists = ['aktif', 'bekleyen', 'reddedilen'];
    lists.forEach(t => {
        const listDiv = document.getElementById('ilan_' + t);
        if(listDiv) {
            const items = listDiv.querySelectorAll('.db-list-item');
            const count = items.length;
            
            const btn = document.getElementById('nav_ilan_' + t);
            if(btn) {
                const text = t === 'aktif' ? 'Aktif İlanlar' : (t === 'bekleyen' ? 'Bekleyen İlanlar' : 'Reddedilenler');
                btn.innerText = `${text} (${count})`;
            }
            
            let emptyState = listDiv.querySelector('.db-empty-state');
            if(count > 0 && emptyState) {
                emptyState.remove();
            } else if(count === 0 && !emptyState) {
                const text = t === 'aktif' ? 'Aktif ilanınız bulunmuyor.' : (t === 'bekleyen' ? 'Bekleyen ilanınız bulunmuyor.' : 'Reddedilen ilanınız bulunmuyor.');
                listDiv.innerHTML = `<div class='db-empty-state'><svg width='48' height='48' style='margin: 0 auto; display: block; opacity: 0.2;'><use xlink:href='#icon-list-3d'></use></svg><p>${text}</p></div>`;
            }
        }
    });
}
// Mesaj penceresi kapatıldığında takibi durdur
function mesajPenceresiKapat() {
    activeChatMuhatap = null;
    modalKapat('mesajPenceresiModal');
}

// Window click eventini güncelle (backdrop için)
window.addEventListener('click', (e) => {
    if(e.target.id === 'mesajPenceresiModal') activeChatMuhatap = null;
    if(e.target.id === 'profilModal') activeChatMuhatap = null;
});
</script>

<?php endif; ?>
