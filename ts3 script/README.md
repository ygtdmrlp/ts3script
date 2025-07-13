# TS3 Yönetim Paneli

Gelişmiş Teamspeak3 sunucu yönetim paneli. PHP ile geliştirilmiş, modern ve kullanıcı dostu arayüz.

## 🚀 Özellikler

### 🔐 Güvenlik
- Kullanıcı kimlik doğrulama sistemi
- Rol tabanlı yetkilendirme (Admin, Moderatör, Kullanıcı)
- CSRF koruması
- Güvenli şifre hashleme
- IP adresi loglama

### 👥 Kullanıcı Yönetimi
- TS3 kullanıcılarını görüntüleme
- Kullanıcı atma (kick)
- Kullanıcı yasaklama (ban)
- Kullanıcı durumu takibi
- IP adresi görüntüleme

### 📢 Kanal Yönetimi
- Kanal listesi görüntüleme
- Yeni kanal oluşturma
- Kanal silme
- Kanal hiyerarşisi
- Kullanıcı sayısı takibi

### 🛠️ Sistem Yönetimi
- Admin paneli
- Kullanıcı hesap yönetimi
- Sistem logları
- Aktivite takibi
- İstatistikler
- Bağlantı testi

### 📊 Dashboard
- Sunucu durumu
- Gerçek zamanlı istatistikler
- Son aktiviteler
- Hızlı erişim menüleri

## 📋 Gereksinimler

- PHP 7.4 veya üzeri
- MySQL 5.7 veya üzeri
- Teamspeak3 Server Query erişimi
- Web sunucusu (Apache/Nginx)

## 🛠️ Kurulum

### 1. Dosyaları İndirin
```bash
git clone https://github.com/your-username/ts3-management.git
cd ts3-management
```

### 2. Veritabanı Kurulumu
1. MySQL'de yeni bir veritabanı oluşturun:
```sql
CREATE DATABASE ts3_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. `config/database.php` dosyasını düzenleyin:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'ts3_management');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### 3. TS3 Sunucu Ayarları
`config/database.php` dosyasında TS3 ayarlarını güncelleyin:
```php
define('TS3_HOST', 'your_ts3_server_ip');
define('TS3_PORT', 10011);
define('TS3_USERNAME', 'serveradmin');
define('TS3_PASSWORD', 'your_query_password');
define('TS3_SERVER_PORT', 9987);
```

### 4. Web Sunucusu Yapılandırması
Apache için `.htaccess` dosyası otomatik oluşturulur. Nginx kullanıyorsanız:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### 5. İzinler
```bash
chmod 755 assets/
chmod 644 config/database.php
```

## 🔑 Varsayılan Giriş

İlk kurulumda otomatik olarak oluşturulan admin hesabı:
- **Kullanıcı Adı:** `admin`
- **Şifre:** `admin123`

**⚠️ Güvenlik için ilk girişten sonra şifreyi değiştirin!**

## 📖 Kullanım

### Dashboard
- Sunucu durumu ve istatistikler
- Son aktiviteler
- Hızlı erişim menüleri

### Kullanıcılar
- Çevrimiçi kullanıcıları görüntüleme
- Kullanıcı atma/yasaklama
- Kullanıcı bilgileri

### Kanallar
- Kanal listesi
- Yeni kanal oluşturma
- Kanal silme
- Kanal istatistikleri

### Admin Paneli
- Sistem kullanıcı yönetimi
- Log görüntüleme
- Sistem ayarları
- Bağlantı testi

## 🔧 Yapılandırma

### Güvenlik Ayarları
- `config/database.php` dosyasında güvenlik ayarları
- Session timeout ayarları
- IP whitelist/blacklist

### TS3 Bağlantı Ayarları
- Query port ayarları
- Timeout değerleri
- Bağlantı limitleri

## 📁 Dosya Yapısı

```
ts3-management/
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── app.js
├── config/
│   └── database.php
├── includes/
│   ├── functions.php
│   ├── header.php
│   └── sidebar.php
├── backups/          # Yedekleme dosyaları (otomatik oluşturulur)
├── index.php
├── login.php
├── logout.php
├── clients.php
├── channels.php
├── users.php
├── logs.php
├── bans.php
├── test_connection.php
├── server_settings.php
├── backup.php
├── statistics.php
├── profile.php
└── README.md
```

## 🛡️ Güvenlik

### Öneriler
1. Varsayılan admin şifresini değiştirin
2. Güçlü şifreler kullanın
3. HTTPS kullanın
4. Düzenli yedekleme yapın
5. Güncel tutun

### Güvenlik Özellikleri
- SQL injection koruması
- XSS koruması
- CSRF token doğrulama
- Şifre hashleme
- Session güvenliği

## 🔄 Güncelleme

1. Dosyaları yedekleyin
2. Yeni dosyaları yükleyin
3. Veritabanı güncellemelerini kontrol edin
4. Ayarları kontrol edin

## 🐛 Sorun Giderme

### Yaygın Sorunlar

#### TS3 Bağlantı Hatası
**Hata:** "Hedef makine etkin olarak reddettiğinden bağlantı kurulamadı"

**Çözümler:**
1. **TS3 Sunucu Durumu:**
   - TS3 sunucusunun çalıştığından emin olun
   - Sunucu loglarını kontrol edin

2. **Query Port Ayarları:**
   - TS3 sunucu ayarlarında Query port'un açık olduğunu kontrol edin
   - Varsayılan Query port: 10011
   - Firewall'da port'un açık olduğunu kontrol edin

3. **IP Adresi ve Port:**
   - `config/database.php` dosyasında doğru IP adresini kullandığınızdan emin olun
   - Query port numarasının doğru olduğunu kontrol edin

4. **Query Kullanıcı Ayarları:**
   - TS3 sunucu ayarlarında Query kullanıcısının oluşturulduğunu kontrol edin
   - Query kullanıcı adı ve şifresinin doğru olduğunu kontrol edin
   - Query kullanıcısının gerekli yetkilere sahip olduğunu kontrol edin

5. **Bağlantı Testi:**
   - Admin panelinde "Bağlantı Testi" sayfasını kullanın
   - Her adımı ayrı ayrı test edin

#### Veritabanı Hatası
- Bağlantı bilgilerini kontrol edin
- Veritabanı izinlerini kontrol edin
- PHP PDO eklentisini kontrol edin

#### Giriş Sorunu
- Varsayılan kullanıcı bilgilerini kontrol edin
- Session ayarlarını kontrol edin

### Detaylı Sorun Giderme

#### TS3 Query Ayarları
1. **TS3 Sunucu Yöneticisi'nde:**
   - Tools > ServerQuery Login
   - Yeni bir Query kullanıcısı oluşturun
   - Gerekli yetkileri verin

2. **Query Kullanıcı Yetkileri:**
   - `b_virtualserver_info_view`
   - `b_virtualserver_client_list`
   - `b_virtualserver_channel_list`
   - `b_client_kick`
   - `b_client_ban`
   - `b_channel_create`
   - `b_channel_delete`

3. **Firewall Ayarları:**
   - Windows Firewall'da Query port'u açın
   - Router'da port forwarding ayarlarını kontrol edin

4. **Test Komutları:**
   ```bash
   # Telnet ile port testi
   telnet your_ts3_ip 10011
   
   # PowerShell ile test
   Test-NetConnection -ComputerName your_ts3_ip -Port 10011
   ```

#### Bağlantı Testi Sayfası
Admin panelinde "Bağlantı Testi" sayfası şunları test eder:
1. **Temel Bağlantı:** TS3 sunucusuna bağlantı
2. **Kimlik Doğrulama:** Query kullanıcı bilgileri
3. **Sunucu Seçimi:** TS3 sunucusu seçimi
4. **Sunucu Bilgisi:** Sunucu bilgilerini alma

### Hata Kodları
- **Error 10061:** Bağlantı reddedildi (port kapalı/firewall)
- **Error 10060:** Bağlantı zaman aşımı (yanlış IP/port)
- **Error 10065:** Host erişilemez (yanlış IP adresi)

## 📞 Destek

Sorunlar için:
1. README dosyasını kontrol edin
2. Log dosyalarını kontrol edin
3. Bağlantı testi sayfasını kullanın
4. GitHub Issues kullanın

## 📄 Lisans

Bu proje MIT lisansı altında lisanslanmıştır.

## 🤝 Katkıda Bulunma

1. Fork yapın
2. Feature branch oluşturun
3. Değişikliklerinizi commit edin
4. Pull request gönderin

## 📝 Changelog

### v1.0.0
- İlk sürüm
- Temel TS3 yönetim özellikleri
- Admin paneli
- Kullanıcı yönetimi
- Kanal yönetimi
- Log sistemi
- Bağlantı testi

## 🔮 Gelecek Özellikler

- [ ] WebSocket desteği
- [ ] Mobil uygulama
- [ ] Gelişmiş istatistikler
- [ ] Otomatik yedekleme
- [ ] Çoklu sunucu desteği
- [ ] API desteği
- [ ] Tema sistemi
- [ ] Çoklu dil desteği

---

**Not:** Bu proje eğitim amaçlı geliştirilmiştir. Prodüksiyon ortamında kullanmadan önce güvenlik testleri yapın. 