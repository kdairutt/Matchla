# Matchla Backend API

Matchla, spor yapmak isteyen bireylerin ekip bulma, güvenilir partner bulma ve sportif performans takibi sorunlarını tek bir ekosistemde çözen sosyal bir spor platformudur. Bu repo, Matchla'nın PHP ile yazılmış backend API'sini içermektedir.

## Teknoloji

- **PHP 8.5** — raw PHP
- **MySQL** — 
- **Composer** — autoload yönetimi

## Proje Yapısı

```
backend/
  src/
    Controllers/    → HTTP isteklerini karşılar, yanıt döner
    Models/         → Veritabanı işlemleri
    Middleware/     → Kimlik doğrulama, yetkilendirme
    Services/       → İş mantığı (puanlama algoritması, takım dengeleme)
    Helpers/        → Yardımcı fonksiyonlar
  config/           → Veritabanı ve uygulama ayarları
  routes/           → API endpoint tanımları
  public/           → Giriş noktası (index.php)
  .env.example      → Gerekli environment değişkenleri
```

## Kurulum

**Gereksinimler:** PHP 8.0+, MySQL, Composer

```bash
# Repoyu klonla
git clone https://github.com/kdairutt/matchla-backend.git
cd matchla-backend/backend

# Bağımlılıkları yükle
composer install

# Environment dosyasını oluştur
cp .env.example .env
# .env dosyasını düzenle

# Geliştirme sunucusunu başlat
cd public
php -S localhost:8000
```

## API Endpoints

### Auth
| Method | Endpoint | Açıklama |
|--------|----------|----------|
| POST | `/api/auth/register` | Oyuncu kaydı |
| POST | `/api/auth/login` | Oturum açma |

### Oyuncular
| Method | Endpoint | Açıklama |
|--------|----------|----------|
| GET | `/api/players/{id}` | Oyuncu profili |
| PUT | `/api/players/{id}` | Profil güncelleme |

### Maçlar
| Method | Endpoint | Açıklama |
|--------|----------|----------|
| GET | `/api/matches` | Yakın çevredeki maçları listele |
| POST | `/api/matches` | Maç oluştur |
| GET | `/api/matches/{id}` | Maç detayı |
| PUT | `/api/matches/{id}` | Maç güncelle |
| DELETE | `/api/matches/{id}` | Maç iptal et |

### Adaylar
| Method | Endpoint | Açıklama |
|--------|----------|----------|
| GET | `/api/matches/{id}/candidates` | Maçın adaylarını listele |
| POST | `/api/matches/{id}/apply` | Maça başvur |
| PUT | `/api/matches/{matchId}/candidates/{candidateId}` | Adayı onayla / reddet |

### Değerleme
| Method | Endpoint | Açıklama |
|--------|----------|----------|
| POST | `/api/matches/{matchId}/players/{playerId}` | Oyuncuyu değerle |


- **Router** — Gelen isteği method ve URI'a göre ilgili controller'a yönlendirir. Statik ve dinamik route'ları destekler.
- **Middleware** — JWT doğrulama, yetkilendirme ve rate limiting işlemlerini yürütür.
- **Controller** — İsteği alır, Service'i çağırır, yanıtı döner. İş mantığı içermez.
- **Service** — Puanlama algoritması, takım dengeleme, aday önerme gibi iş mantığını yürütür.
- **Model** — Veritabanı sorguları ve veri erişim katmanı.

## Özellikler (Geliştirme Aşamasında)

- [x] Router (statik + dinamik route desteği)
- [x] Authentication (JWT)
- [x] Oyuncu profili
- [x] Maç yönetimi ve yaşam döngüsü
- [x] Aday/Katılımcı sistemi
- [ ] Puanlama algoritması
- [ ] Takım dengeleme algoritması
- [ ] Aday önerme algoritması

## Yapılacaklar (Öncelik Sıralı)
- **Models** - Data transaction'ları için Modeller. Yüksek öncelik.
- **Helpers** - Kalıplaşmış response ifadeleri, request verilerinin yüklenmesi ve benzeri.
## Hakkında

Bu proje, YBS370 — Sistem Analizi ve Tasarımı dersi kapsamında hazırlanan [Matchla SRS belgesi](docs/SRS.pdf) temel alınarak geliştirilmektedir.

**Geliştirici:** Abdülkadir İpek
