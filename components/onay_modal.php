<div id="onayModal" class="modal-arkaplan" style="z-index: 9999;">
    <div class="modal-icerik kucuk" style="border-radius: 24px; overflow: hidden; max-width: 400px;">
        <div class="modal-body" style="padding: 40px 30px; text-align: center;">
            <div style="width: 70px; height: 70px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <svg width="60" height="60"><use xlink:href="#icon-trash"></use></svg>
            </div>
            <h2 style="font-size: 22px; font-weight: 800; color: #111827; margin-bottom: 12px;">Emin misiniz?</h2>
            <p id="onay_metin" style="font-size: 15px; color: #6b7280; line-height: 1.5; margin-bottom: 30px;">Bu işlemi gerçekleştirmek istediğinize emin misiniz?</p>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <button type="button" style="background: #f1f5f9; color: #64748b; border: none; padding: 14px; border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.3s; font-size: 15px;" onclick="modalKapat('onayModal')">Vazgeç</button>
                <button type="button" id="onay_onayla_btn" style="background: #ef4444; color: white; border: none; padding: 14px; border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.3s; font-size: 15px;">Evet, Sil</button>
            </div>
        </div>
    </div>
</div>

<script>
let onayCallback = null;

function onaySoru(mesaj, callback) {
    const modal = document.getElementById('onayModal');
    const metin = document.getElementById('onay_metin');
    const onaylaBtn = document.getElementById('onay_onayla_btn');
    
    metin.innerText = mesaj;
    onayCallback = callback;
    
    modal.style.display = 'flex';
    
    const yeniOnaylaBtn = onaylaBtn.cloneNode(true);
    onaylaBtn.parentNode.replaceChild(yeniOnaylaBtn, onaylaBtn);
    
    yeniOnaylaBtn.addEventListener('click', function() {
        modalKapat('onayModal');
        if(onayCallback) onayCallback();
    });
}
</script>
