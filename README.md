# Okul Dijital Bilgi Panosu

Okullardaki televizyon ekranlarında duyuru ve günlük bilgileri tek ekranda göstermek için geliştirdiğim PHP tabanlı dijital pano uygulaması.

İçerikler yönetim panelinden düzenlenebilir. Yapılan değişiklikler, ekranı elle yenilemeye gerek kalmadan panoya otomatik yansır.

## Özellikler

- Okul adı ve logo yükleme, değiştirme, kaldırma
- Nöbetçi öğretmen listesi
- Günün yemek menüsü: kahvaltı, öğle ve ikindi
- Öğrenci doğum günleri
- Yaklaşan önemli günler ve etkinlikler
- Ders ve teneffüs geri sayımı
- Görsel slaytlar ve kayan duyurular
- Günün mesajı
- Saat, tarih ve Ankara hava durumu / hava kalitesi
- Excel üzerinden veri aktarımı
- Tam ekran gösterim

## Teknik Yapı

PHP, JavaScript ve CSS ile geliştirilmiştir. Veriler JSON dosyasında tutulur; MySQL kurulumu gerektirmez.

Excel aktarımı için sunucuda **ZipArchive** ve **SimpleXML** eklentileri bulunmalıdır.

## Kurulum

1. Dosyaları PHP destekli sunucunuza yükleyin.
2. `data/` ve `assets/uploads/` klasörlerine PHP’nin yazabildiğinden emin olun.
3. Ana dizinden panoyu, `/admin/` adresinden yönetim panelini açın.
4. Genel Ayarlar bölümünden okul adını ve logosunu düzenleyin.

**Örnek yönetim paneli girişi:**

- Kullanıcı adı: `admin`
- Şifre: `admin`

Gerçek kullanım öncesinde giriş bilgilerini `config.php` dosyasından değiştirin.

## Örnek Veriler

Paylaşılan sürümde kurum logosu ve gerçek öğrenci/öğretmen bilgileri bulunmaz. İçerikler tanıtım amacıyla hazırlanmış temsili verilerdir. Tarihli örnek kayıtları kullanımınıza göre güncelleyebilirsiniz.

Görüş, hata bildirimi ve geliştirme önerilerinizi Issues bölümünden paylaşabilirsiniz.
