# Sistem Atlası tasarım sistemi

## Ürün karakteri

Sistem Atlası, Linux, Windows ve macOS hakkında teknik karar vermeyi kolaylaştıran iki dilli bir arşivdir. Public yüz sakin, sola hizalı ve editoryaldir. Yönetim yüzü gerçek veriyle çalışan kompakt bir yayın çalışma alanıdır. Arayüz terminali, finans dashboard’unu veya üretken yapay zekâ estetiğini taklit etmez.

Tasarım ayarları: `Variance 7`, `Motion 6`, `Density 5`.

## Renk

| Rol | Açık | Koyu |
|---|---|---|
| Arşiv zemini | `#F1F2EE` | `#121513` |
| İkincil zemin | `#E5E8E3` | `#191D1A` |
| Ana metin | `#171A18` | `#EDF0EA` |
| İkincil metin | `#626963` | `#A7AEA8` |
| Ayırıcı | `#C9CDC7` | `#39403B` |
| Rota / odak | `#356979` | `#79A9B4` |

404 kurtarma kontrolünde sınırlı olarak `#E56650` mercan kullanılır. Admin kabuğu karakteristik olarak `#0F1110` ve `#171A18` yüzeylerinden oluşur. Semantik yeşil, amber ve kırmızı yalnız gerçek durumlarda kullanılır.

## Tipografi

- Public display: Iowan Old Style, Charter, Palatino Linotype, Georgia.
- Arayüz ve gövde: Segoe UI Variable, Segoe UI, sistem sans-serif.
- Mono: yalnız cihaz ekranı, gerçek kod ve teknik tanımlayıcılar için Cascadia Mono / Consolas.
- Başlıklar sola hizalı, sıkı harf aralıklı ve cümle düzenindedir.
- Uzun metinler 80 karakterden kısa satırlarda tutulur.
- Büyük harf sadece kısa fiziksel/teknik işaretlerde kullanılır.

## Yapı

- Public içerik kart ızgarası yerine kayıt satırları, cetveller ve asimetrik kolonlar kullanır.
- Admin masaüstünde ikon rayı, metin navigasyonu ve çalışma alanından oluşur.
- Mobil admin yatay navigasyon ve etiketli kayıt bloklarına dönüşür; yatay sayfa kaydırmaz.
- 404 sayfası özgün bir kurtarma konsolunu HTML/CSS/SVG ile oluşturur; referans varlıkları kopyalanmaz ve oyun içermez.
- Marka yalnızca `Sistem Atlası` metniyle gösterilir. `SA`, noktalı wordmark veya slogan kullanılmaz.

## Etkileşim ve hareket

- Hızlı geri bildirim 120 ms, UI durumu 200–240 ms, sayfa geçişi 320–420 ms.
- Easing: `cubic-bezier(.22, 1, .36, 1)`.
- Hover hiçbir kontrolün ölçüsünü veya ölçeğini değiştirmez.
- Public tarafta tek hero girişi ve seyrek bölüm çizgisi reveal’i kullanılır; her satır sırayla fade olmaz.
- Admin sayfa değişimleri kısa fade/slide kullanır.
- 404 medya değişimi sonludur ve sabit durumda biter.
- `prefers-reduced-motion` bütün zorunlu olmayan hareketleri kapatır.

## Erişilebilirlik

- Mobil hedefler en az 44 px; mobil form yazısı en az 16 px.
- Klavye odağı görünür ve sticky alanlar tarafından örtülmez.
- Durumlar yalnız renkle anlatılmaz.
- Değişen durumlar uygun `aria-live`, hata metinleri `role="alert"` kullanır.
- Native link navigasyon, native button eylem içindir.
- Dark/light, forced-colors, increased contrast, safe-area ve 200% zoom desteklenir.
