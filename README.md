# Sistem Atlası

Linux dağıtımlarını, Windows sürümlerini ve macOS nesillerini teknik temelleriyle anlatan iki dilli bir işletim sistemi arşivi. Sistem profilleri; mimari, kurulum, donanım gereksinimleri, günlük kullanım ve bakım gibi başlıkları bir araya getirir. Türkçe ve İngilizce yazılar da bu profilleri tamamlar.

Bu depo, sitenin **güncel WordPress sürümünü** içerir. Özel tema ve içerik eklentisinin yanı sıra yerel geliştirmede kullanılan WordPress çekirdeği, Polylang ve SQLite Database Integration kodları da dahildir. Önceki Laravel denemesi bu deponun parçası değildir.

## Neler var?

- `/tr/` ve `/en/` altında iki dilli yayın akışı
- İşletim sistemi profilleri (`atlas_os`) ve teknik yazılar (`atlas_article`)
- İşletim sistemi ailesi taksonomisi (`atlas_family`), teknik alanlar ve kurulum rehberleri
- Koyu/açık görünüm, ölçülü giriş ve kaydırma animasyonları, hareket azaltma tercihi desteği
- WordPress yönetiminde özel içerik düzenleme alanları
- MySQL sunucusu gerektirmeyen yerel SQLite kurulumu

## Yerel kurulum

PHP **8.3+** gerekir. `pdo_sqlite` ve `sqlite3` eklentileri etkin olmalıdır. Bu depo WordPress çekirdeğini ve gereken eklenti kodlarını içerir; ayrıca Composer veya Node kurulumu gerektirmez.

1. Depoyu indirin:

   ```bash
   git clone https://github.com/iambatuw/sistematlasi.git
   cd sistematlasi
   ```

2. `wp-config-sample.php` dosyasını `wp-config.php` adıyla kopyalayın. Örnekteki sekiz güvenlik anahtarını benzersiz değerlerle değiştirin. SQLite bağlantısı için `wp-content/db.php` drop-in dosyası hazırdır; örnekteki MySQL kullanıcı adı ve parolası SQLite tarafından kullanılmaz.
3. Sunucuyu başlatın:

   ```bash
   php -S 127.0.0.1:8000 router.php
   ```

   Windows'ta `start.bat`, Bash ortamında `./start.sh` da kullanılabilir.
4. `http://127.0.0.1:8000/wp-admin/install.php` adresinde WordPress kurulumunu tamamlayın ve kendi yönetici hesabınızı oluşturun.
5. Yönetim panelinden **Polylang** ve **Sistem Atlası İçerik** eklentilerini etkinleştirin; **Sistem Atlası** temasını seçin. SQLite Database Integration eklentisi de etkin olmalıdır. **Ayarlar → Kalıcı Bağlantılar** bölümünde “Yazı ismi” biçimini seçip kaydedin.
6. Polylang'da Türkçe ve İngilizce dillerini ekleyin; Türkçeyi varsayılan dil yapın. Dillerin URL'de dizin olarak gösterilmesini ve özel yazı türleri ile `atlas_family` taksonomisinin çevrilmesini etkinleştirin. Mevcut bir kurulumu aynı ayarlara getirmek için WP-CLI ile `php wp-cli.phar eval-file tools/setup-polylang.php` komutu da kullanılabilir.

Ardından `/tr/`, `/tr/os/`, `/tr/blog/` ve İngilizce karşılıklarını ziyaret edebilirsiniz. **Bu depo canlı sitenin veritabanını içermez:** yeni kurulum içerik olmadan açılır; profiller ve yazılar yönetim panelinden eklenir. Yerel veritabanı `wp-content/database/` altında oluşturulur.

## Proje yapısı

| Yol | Görev |
| --- | --- |
| `wp-content/themes/sistem-atlasi/` | Ön yüz şablonları, stiller, animasyonlar ve çeviriler |
| `wp-content/plugins/sistem-atlasi-content/` | İçerik türleri, taksonomi, düzenleyici alanları ve yönlendirmeler |
| `wp-content/plugins/polylang/` | Çok dillilik bağımlılığı |
| `wp-content/plugins/sqlite-database-integration/`, `wp-content/db.php` | SQLite veritabanı katmanı |
| `router.php` | PHP'nin yerleşik sunucusunda kalıcı bağlantılar |
| `tools/` | Yerel kurulum ve isteğe bağlı eski içerik taşıma araçları |
| `DESIGN.md` | Tasarım ilkeleri ve etkileşim kararları |

`tools/import-from-sqlite.php`, önceki Laravel verilerinden geçiş için isteğe bağlı bir araçtır. Kaynak Laravel veritabanı bu depoda bulunmaz; aracı kullanmak için kendi yerel kaynak dosyalarınızı ayrıca sağlamanız gerekir. Sıfırdan WordPress kurulumu için bu araç gerekli değildir.

## Güvenlik ve lisans

`wp-config.php`, SQLite veritabanı, yüklenen medya ve yerel arşiv Git'e dahil edilmez. Gerçek güvenlik anahtarlarını veya yönetici parolalarını repoya eklemeyin. WordPress çekirdeği ve üçüncü taraf eklentiler kendi lisanslarıyla gelir; tema meta bilgisinde GPL v2 veya sonrası belirtilmiştir.
