<?php
session_name('kariyer_admin');
    session_start();
if(isset($_SESSION['kullaniciID']) && $_SESSION['krolID'] == 3) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Girişi | Kariyerlen</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { 
            background-color: #0f172a; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh;
            color: #fff;
        }
        .login-card {
            background: #1e293b;
            padding: 48px;
            border-radius: 16px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
            border: 1px solid rgba(255,255,255,0.1);
        }
        .login-logo {
            display: flex;
            justify-content: center;
            margin-bottom: 32px;
        }
        .login-logo img {
            max-height: 50px;
            mix-blend-mode: lighten;
        }
        .login-title {
            text-align: center;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
            color: #f1f5f9;
        }
        .login-subtitle {
            text-align: center;
            font-size: 14px;
            color: #94a3b8;
            margin-bottom: 32px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 8px;
        }
        .form-input {
            width: 100%;
            background: #0f172a;
            border: 1px solid #334155;
            color: #fff;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 15px;
            outline: none;
            transition: border-color 0.2s;
        }
        .form-input:focus {
            border-color: #ff7e1d;
        }
        .login-btn {
            width: 100%;
            background: #ff7e1d;
            color: #fff;
            border: none;
            padding: 14px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 12px;
        }
        .login-btn:hover {
            background: #ea580c;
        }
        .error-message {
            background: rgba(239, 68, 68, 0.1);
            color: #f87171;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            display: none;
            text-align: center;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="login-logo">
            <img src="img/admin-logo-dark.jpg" alt="Kariyerlen">
        </div>
        <div class="login-title">Yönetim Paneli</div>
        <div class="login-subtitle">Devam etmek için yetkili girişi yapın</div>
        
        <div class="error-message" id="errorMsg"></div>
        
        <form id="adminLoginForm" onsubmit="event.preventDefault(); adminGiris();">
            <div class="form-group">
                <label class="form-label">E-posta Adresi</label>
                <input type="email" name="email" class="form-input" required placeholder="admin@kariyerlen.com">
            </div>
            <div class="form-group">
                <label class="form-label">Şifre</label>
                <input type="password" name="sifre" class="form-input" required placeholder="••••••••">
            </div>
            <button type="submit" class="login-btn" id="loginBtn">Giriş Yap</button>
        </form>
        <br>
        <p>admin123</p>
    </div>

    <script>
    async function adminGiris() {
        const btn = document.getElementById('loginBtn');
        const err = document.getElementById('errorMsg');
        
        btn.disabled = true;
        btn.innerText = 'Giriş yapılıyor...';
        err.style.display = 'none';
        
        const formData = new FormData(document.getElementById('adminLoginForm'));
        formData.append('islem', 'admin_giris');
        
        try {
            const res = await fetch('admin_islem.php', { method: 'POST', body: formData });
            const data = await res.json();
            
            if(data.durum === 'basarili') {
                window.location.href = 'index.php';
            } else {
                err.innerText = data.hata || 'Giriş başarısız.';
                err.style.display = 'block';
                btn.disabled = false;
                btn.innerText = 'Giriş Yap';
            }
        } catch(e) {
            err.innerText = 'Sunucu bağlantı hatası.';
            err.style.display = 'block';
            btn.disabled = false;
            btn.innerText = 'Giriş Yap';
        }
    }
    </script>
</body>
</html>
