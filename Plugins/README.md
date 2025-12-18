# Silkroad Remote - PHP Backend

Bu klasör, Silkroad Remote eklentisinin PHP sunucu tarafı dosyalarını içerir. Bu dosyalar, phBot eklentisi ile mobil uygulama arasındaki iletişimi sağlar.

## Dosya Açıklamaları

### Konfigürasyon Dosyaları
- **config.php** - Veritabanı bağlantı ayarları ve yardımcı fonksiyonlar
- **database_schema.sql** - MySQL veritabanı şeması (tabloları oluşturmak için)

### API Endpoint Dosyaları
- **dataSave.php** - Bot'tan karakter verilerini kaydeder (HP, MP, EXP, konum, vb.)
- **dataReceive.php** - Mobil uygulamadan gelen komutları (eğitim alanı, başlat/durdur) bot'a iletir
- **chatReceive.php** - Party/Guild chat mesajlarını yönetir (çift yönlü)
- **partyReceive.php** - Party üye bilgilerini yönetir
- **itemReceive.php** - Envanter/item verilerini yönetir
- **notificationIn.php** - Ölüm ve nadir eşya düşmesi bildirimlerini yönetir
- **qrReceive.php** - QR kod kayıt işlemlerini yönetir
- **mobileApi.php** - Mobil uygulama için birleştirilmiş API endpoint'i

## Kurulum

### 1. Gereksinimler
- PHP 7.4 veya üstü
- MySQL 5.7 veya üstü
- PDO PHP extension

### 2. Veritabanı Kurulumu
```sql
-- database_schema.sql dosyasını MySQL'de çalıştırın
mysql -u root -p < database_schema.sql
```

### 3. Konfigürasyon
`config.php` dosyasını açın ve veritabanı bilgilerinizi güncelleyin:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'silkroad_remote');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### 4. Web Sunucusu Kurulumu
PHP dosyalarını web sunucunuza (Apache/Nginx) yükleyin. Dosyalar şu URL yapısında erişilebilir olmalıdır:
```
https://yourdomain.com/dataSave.php
https://yourdomain.com/dataReceive.php
https://yourdomain.com/chatReceive.php
https://yourdomain.com/partyReceive.php
https://yourdomain.com/itemReceive.php
https://yourdomain.com/notificationIn.php
https://yourdomain.com/qrReceive.php
https://yourdomain.com/mobileApi.php
```

### 5. Python Eklentisini Güncelleme
`Silkroad_Remote.py` dosyasındaki URL'leri kendi sunucu adresinizle değiştirin:
```python
path="http://yourdomain.com/dataSave.php" 
notificationPath="http://yourdomain.com/notificationIn.php"
bottingDataPath = "http://yourdomain.com/dataReceive.php"
# ... diğer URL'ler
```

## API Kullanımı

### Karakter Verisi Alma (Mobil Uygulama İçin)
```
GET /mobileApi.php?qrId=YOUR_QR_ID&action=getCharacter
```

### Komut Gönderme (Mobil Uygulama İçin)
```
GET /mobileApi.php?qrId=YOUR_QR_ID&action=setCommand&startBot=1&stopBot=0
GET /mobileApi.php?qrId=YOUR_QR_ID&action=setCommand&trainingAreaX=1000&trainingAreaY=2000&trainingRadius=50
```

### Envanter Alma
```
GET /mobileApi.php?qrId=YOUR_QR_ID&action=getInventory
```

### Bildirimleri Alma
```
GET /mobileApi.php?qrId=YOUR_QR_ID&action=getNotifications&limit=20
```

### Chat Mesajı Gönderme
```
GET /chatReceive.php?qrId=YOUR_QR_ID&accountId=12345&sendMessage=Merhaba&whichChat=0
```
- whichChat: 0 = Party, 1 = Guild

## Veri Formatları

### Party Kullanıcıları
Format: `isim☽hp_yüzde☽mp_yüzdeΨisim2☽hp2☽mp2Ψ...`

### Envanter Öğeleri
Format: `miktar☽plus☽isimΨmiktar2☽plus2☽isim2Ψ...`

## Güvenlik Notları

1. **config.php** dosyasını web üzerinden erişilemez bir konuma taşıyın
2. HTTPS kullanın
3. Veritabanı kullanıcısına minimum yetkiler verin
4. Rate limiting uygulayın (DDoS koruması için)

## Sorun Giderme

### Veritabanı Bağlantı Hatası
- config.php'deki bilgilerin doğru olduğunu kontrol edin
- MySQL servisinin çalıştığını kontrol edin

### 404 Hatası
- PHP dosyalarının doğru dizinde olduğunu kontrol edin
- Web sunucusu konfigürasyonunu kontrol edin

### JSON Parse Hatası
- PHP'nin UTF-8 encoding kullandığından emin olun
- Veritabanı charset'inin utf8mb4 olduğunu kontrol edin
