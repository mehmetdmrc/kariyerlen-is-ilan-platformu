# Kariyerlen - İş ve Kariyer Platformu

[![GitHub Repository](https://img.shields.io/badge/GitHub-Repository-blue?logo=github)](https://github.com/mehmetdmrc/kariyerlen-is-ilan-platformu)

Kariyerlen, iş arayanlarla işverenleri bir araya getiren kapsamlı bir iş ilanı ve kariyer portalıdır. PHP ve MySQL kullanılarak geliştirilmiştir. Proje içerisinde iş ilanlarını listeleme, detaylı inceleme, blog yazıları okuma, kullanıcı girişi ve yönetici paneli gibi temel fonksiyonlar bulunmaktadır.

## 🚀 Özellikler

- **İş İlanları:** Kullanıcılar güncel iş ilanlarını listeleyebilir ve detaylarına ulaşabilir.
- **Kullanıcı Sistemi:** Kayıt olma ve giriş yapma (Şirket veya İş Arayan olarak).
- **Blog:** Kariyer tavsiyeleri ve güncel sektörel haberlerin yer aldığı blog bölümü.
- **Yönetici Paneli:** Sistemi yönetmek için kapsamlı bir admin aracı (`/admin`).
- **Dinamik İçerik:** Tüm veriler MySQL veritabanından dinamik olarak çekilir.
- **Mesajlaşma:** Kullanıcılar arası temel iletişim altyapısı.

## 💻 Kullanılan Teknolojiler

- **Backend:** PHP (PDO ile)
- **Veritabanı:** MySQL
- **Frontend:** HTML5, CSS3, JavaScript
- **Sunucu Ortamı:** XAMPP, Apache

## 🛠️ Kurulum

Projeyi yerel ortamınızda çalıştırmak için aşağıdaki adımları izleyin:

1. Bu projeyi bilgisayarınıza klonlayın veya zip olarak indirin.
2. XAMPP (veya benzeri bir lokal sunucu) kurun ve Apache ile MySQL servislerini başlatın.
3. Proje klasörünü `htdocs` (XAMPP için) dizininin içine `kariyer` adıyla taşıyın.
4. Tarayıcınızdan `http://localhost/phpmyadmin` adresine gidin.
5. `kariyerlen` adında yeni bir veritabanı oluşturun.
6. Proje ile birlikte gelen veritabanı tablosunu/sql dosyasını bu veritabanına aktarın.
7. `baglan.php` dosyasındaki veritabanı bağlantı bilgilerinin kendi lokalinizdeki bilgilerle uyumlu olduğundan emin olun:
   ```php
   $host = 'localhost';
   $dbname = 'kariyerlen'; 
   $username = 'root'; 
   $password = '';  
   ```
8. Tarayıcınızda `http://localhost/kariyer` adresine giderek projeyi çalıştırabilirsiniz.

## 📸 Proje Görselleri (Menü)

Aşağıdaki menüden proje arayüzüne ait ekran görüntülerine ulaşabilirsiniz. İncelemek istediğiniz görselin üzerine tıklayarak açabilirsiniz:

<details>
  <summary>🖼️ 1. Ekran Görüntüsü (Görseli açmak için tıklayın)</summary>
  <br>
  <img src="siteiçi/Ekran%20g%C3%B6r%C3%BCnt%C3%BCs%C3%BC%202026-06-09%20212859.png" alt="Ekran Görüntüsü 1">
</details>

<details>
  <summary>🖼️ 2. Ekran Görüntüsü (Görseli açmak için tıklayın)</summary>
  <br>
  <img src="siteiçi/Ekran%20g%C3%B6r%C3%BCnt%C3%BCs%C3%BC%202026-06-09%20212909.png" alt="Ekran Görüntüsü 2">
</details>

<details>
  <summary>🖼️ 3. Ekran Görüntüsü (Görseli açmak için tıklayın)</summary>
  <br>
  <img src="siteiçi/Ekran%20g%C3%B6r%C3%BCnt%C3%BCs%C3%BC%202026-06-09%20212922.png" alt="Ekran Görüntüsü 3">
</details>

<details>
  <summary>🖼️ 4. Ekran Görüntüsü (Görseli açmak için tıklayın)</summary>
  <br>
  <img src="siteiçi/Ekran%20g%C3%B6r%C3%BCnt%C3%BCs%C3%BC%202026-06-09%20212928.png" alt="Ekran Görüntüsü 4">
</details>

<details>
  <summary>🖼️ 5. Ekran Görüntüsü (Görseli açmak için tıklayın)</summary>
  <br>
  <img src="siteiçi/Ekran%20g%C3%B6r%C3%BCnt%C3%BCs%C3%BC%202026-06-09%20212952.png" alt="Ekran Görüntüsü 5">
</details>

<details>
  <summary>🖼️ 6. Ekran Görüntüsü (Görseli açmak için tıklayın)</summary>
  <br>
  <img src="siteiçi/Ekran%20g%C3%B6r%C3%BCnt%C3%BCs%C3%BC%202026-06-09%20213008.png" alt="Ekran Görüntüsü 6">
</details>

---
*Bu proje, kariyer hedeflerinize ulaşmanız için geliştirilmiştir.*
