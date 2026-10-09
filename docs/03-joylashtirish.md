# Serverga joylashtirish bo'yicha texnik hujjat

**«Inson va Jamiyat» onlayn ilmiy jurnali — tizim administratori uchun**

|                          |                                                       |
| ------------------------ | ----------------------------------------------------- |
| Hujjat                   | Serverga joylashtirish (deployment) va ekspluatatsiya |
| Tizim                    | insonvajamiyat.uz                                     |
| Kimlar uchun             | Server administratori, dasturchi                      |
| Asos                     | Texnik topshiriq v1.0, 2, 7, 9-bo'limlar              |
| Tayyor konfiguratsiyalar | Repozitoriydagi `deploy/` papkasi                     |

---

## 1. Arxitektura

| Qatlam             | Texnologiya                                                                                           |
| ------------------ | ----------------------------------------------------------------------------------------------------- |
| Backend            | Laravel 13 (PHP 8.3+)                                                                                 |
| Frontend           | Vue 3 (Composition API, TypeScript) + Inertia.js v3, Tailwind CSS 4                                   |
| Build              | Vite 8 (Node.js 22+ — faqat build bosqichida)                                                         |
| Ma'lumotlar bazasi | MySQL 8 (yoki MariaDB 10.6+)                                                                          |
| Navbat (queue)     | `database` drayveri (Redis ixtiyoriy) — Supervisor boshqaradigan ishchilar                            |
| Rejalashtiruvchi   | Laravel Scheduler — cron har daqiqada `schedule:run`                                                  |
| Veb-server         | Nginx + PHP-FPM, HTTPS (Let's Encrypt)                                                                |
| Fayllar            | Laravel Storage: `storage/app/public` (ommaviy) va `storage/app/private` (maqola fayllari, zaxiralar) |
| Tashqi xizmatlar   | SMTP pochta, Click, Payme, Anthropic Claude API, Google va ORCID OAuth, Crossref, OAI-PMH             |

**Tizim qismlari:**

| Qism                | URL                                    | Kim uchun                                         |
| ------------------- | -------------------------------------- | ------------------------------------------------- |
| Web (ommaviy)       | `/`, `/articles`, `/issues`, …         | Hamma                                             |
| Muallif kabineti    | `/cabinet/*`                           | Ro'yxatdan o'tgan, emaili tasdiqlangan mualliflar |
| Admin panel         | `/admin/*`                             | Faqat xodimlar (`staff` middleware + RBAC)        |
| To'lov webhook'lari | `/payments/click/*`, `/payments/payme` | Click va Payme serverlari                         |
| Indekslash          | `/oai`, `/sitemap.xml`, `/robots.txt`  | Ilmiy bazalar va qidiruv tizimlari                |
| Sog'liq tekshiruvi  | `/up`                                  | Monitoring                                        |

---

## 2. Server talablari

| Resurs    | Minimal                                     | Tavsiya                                          |
| --------- | ------------------------------------------- | ------------------------------------------------ |
| OS        | Ubuntu 22.04 LTS                            | Ubuntu 24.04 LTS                                 |
| CPU / RAM | 2 vCPU / 2 GB                               | 4 vCPU / 4 GB                                    |
| Disk      | 40 GB SSD                                   | 100 GB SSD (maqola fayllari va zaxiralar o'sadi) |
| Domen     | `insonvajamiyat.uz` A yozuvi server IP'siga | + `www`                                          |

**Dasturiy ta'minot:**

- **PHP 8.3+** kengaytmalari: `fpm cli mysql mbstring xml curl zip gd intl bcmath opcache`
- **MySQL 8** va `mysql-client` (zaxira uchun `mysqldump`)
- **Composer 2**, **Node.js 22+** va npm
- **Nginx**, **Supervisor**, **certbot**
- **qpdf 11+** — jurnal sonining to'liq PDF'ini yig'ish uchun
- **git**, **unzip**
- Redis — ixtiyoriy (navbat va kesh uchun; standart sozlamada `database` drayveri ishlatiladi)

```bash
sudo apt update
sudo apt install -y nginx mysql-server mysql-client supervisor certbot python3-certbot-nginx \
  git unzip qpdf \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip \
  php8.3-gd php8.3-intl php8.3-bcmath php8.3-opcache

curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash - && sudo apt install -y nodejs
```

---

## 3. Birinchi o'rnatish

### 3.1. Ma'lumotlar bazasi

```sql
CREATE DATABASE insonvajamiyat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'insonvajamiyat'@'localhost' IDENTIFIED BY 'KUCHLI_PAROL';
GRANT ALL PRIVILEGES ON insonvajamiyat.* TO 'insonvajamiyat'@'localhost';
FLUSH PRIVILEGES;
```

### 3.2. Kod va bog'liqliklar

```bash
sudo mkdir -p /var/www/insonvajamiyat && sudo chown -R $USER:www-data /var/www/insonvajamiyat
git clone <REPO_URL> /var/www/insonvajamiyat
cd /var/www/insonvajamiyat

cp .env.production.example .env
nano .env                                   # 4-bo'limga qarang

composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder --force
php artisan db:seed --class=SubjectSeeder --force      # asosiy ilmiy yo'nalishlar
php artisan storage:link

php artisan wayfinder:generate --with-form
npm ci && npm run build

sudo chown -R www-data:www-data storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod 775 {} \;
sudo find storage bootstrap/cache -type f -exec chmod 664 {} \;
chmod 600 .env && sudo chown www-data:www-data .env

php artisan optimize
```

> Rollar seed qilinmasa ro'yxatdan o'tish ishlamaydi — yangi foydalanuvchiga `author` roli biriktiriladi.

### 3.3. Birinchi bosh administrator

```bash
php artisan app:create-super-admin        # email, ism va parol interaktiv so'raladi
```

Qolgan xodimlarni bosh administrator admin panelning «Foydalanuvchilar» bo'limida qo'shadi.

### 3.4. Nginx, PHP va SSL

| Fayl (`deploy/`)                        | Serverdagi joyi                                          | Vazifasi                                                                                                                 |
| --------------------------------------- | -------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------ |
| `nginx/insonvajamiyat.conf`             | `/etc/nginx/sites-available/`                            | HTTPS, HTTP→HTTPS va www→asosiy domen yo'naltirish, gzip, statik fayllar keshi, yashirin fayllarni yopish, 64 MB yuklash |
| `php/99-insonvajamiyat.ini`             | `/etc/php/8.3/fpm/conf.d/` va `/etc/php/8.3/cli/conf.d/` | Yuklash hajmi (32 MB), xotira, OPcache                                                                                   |
| `supervisor/insonvajamiyat-worker.conf` | `/etc/supervisor/conf.d/`                                | Navbat ishchilari                                                                                                        |
| `cron`                                  | `crontab -u www-data`                                    | Rejalashtiruvchi                                                                                                         |
| `deploy.sh`                             | loyiha ichida                                            | Yangilash skripti                                                                                                        |

```bash
sudo cp deploy/php/99-insonvajamiyat.ini /etc/php/8.3/fpm/conf.d/
sudo cp deploy/php/99-insonvajamiyat.ini /etc/php/8.3/cli/conf.d/
sudo systemctl restart php8.3-fpm

sudo certbot certonly --nginx -d insonvajamiyat.uz -d www.insonvajamiyat.uz

sudo cp deploy/nginx/insonvajamiyat.conf /etc/nginx/sites-available/
sudo ln -sf /etc/nginx/sites-available/insonvajamiyat.conf /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx
sudo certbot renew --dry-run
```

### 3.5. Navbat ishchilari va rejalashtiruvchi

```bash
sudo cp deploy/supervisor/insonvajamiyat-worker.conf /etc/supervisor/conf.d/
sudo supervisorctl reread && sudo supervisorctl update
sudo supervisorctl start "insonvajamiyat-worker:*"
sudo supervisorctl status

sudo crontab -u www-data deploy/cron
php artisan schedule:list
```

**Navbat orqali bajariladigan ishlar** (worker ishlamasa, ular `jobs` jadvalida kutib qoladi):

| Ish                | Tavsif                                                                                           |
| ------------------ | ------------------------------------------------------------------------------------------------ |
| Email xatlar       | Tasdiqlash, parolni tiklash, maqola holati, taqriz takliflari, to'lov, eslatmalar, aloqa formasi |
| `ProcessAiRequest` | AI Studio so'rovlari (katta matn bo'laklarga bo'linadi)                                          |
| `BuildIssuePdf`    | Jurnal sonining to'liq PDF'ini qpdf bilan yig'ish                                                |
| `SendBroadcast`    | Ommaviy xabarlar                                                                                 |
| `RunBackup`        | Zaxira nusxa (eng uzun ish — 3600 s gacha)                                                       |

> `.env` dagi `DB_QUEUE_RETRY_AFTER=3700` eng uzun ish vaqtidan (3600 s) katta bo'lishi shart.

**Rejalashtirilgan vazifalar** (`routes/console.php`, vaqt — UTC):

| Buyruq                                   | Jadval                          | Vazifasi                                                                                |
| ---------------------------------------- | ------------------------------- | --------------------------------------------------------------------------------------- |
| `model:prune` (AuditLog)                 | har kuni 03:15                  | Saqlash muddati o'tgan audit yozuvlarini tozalash                                       |
| `app:proof-reminders`                    | har soatda (:07)                | Korrektura muddati yaqinlashganda muallifga, o'tganda maketchi va bosh muharrirga xabar |
| `app:payment-reminders`                  | har kuni 05:00 (Toshkent 10:00) | To'lov kutilayotgan maqolalar mualliflariga 3, 7, 14-kun eslatmalari                    |
| `backup:run --scheduled`                 | har 10 daqiqada                 | Admin paneldagi jadval bo'yicha avtomatik zaxira (vaqti kelganini o'zi tekshiradi)      |
| `heartbeat:scheduler`, `heartbeat:queue` | har daqiqada / har 5 daqiqada   | Cron va navbat ishchisi tirikligini «Tizim holati»da ko'rsatish uchun                   |

---

## 4. Muhit sozlamalari (`.env`)

Namuna: `.env.production.example`. Pochta, jurnal rekvizitlari va AI kalitini **admin panel orqali** ham o'zgartirish mumkin — bazadagi qiymat `.env` dan ustun turadi.

### 4.1. Asosiy

| O'zgaruvchi                                | Qiymat / izoh                                                                                  |
| ------------------------------------------ | ---------------------------------------------------------------------------------------------- |
| `APP_ENV`                                  | `production`                                                                                   |
| `APP_DEBUG`                                | `false` (aks holda xatolar tafsiloti ochiladi)                                                 |
| `APP_URL`                                  | `https://insonvajamiyat.uz` — havolalar, OAI identifikatorlari va webhook'lar shundan tuziladi |
| `APP_LOCALE` / `APP_FALLBACK_LOCALE`       | `uz` / `en`                                                                                    |
| `LOG_STACK`, `LOG_DAILY_DAYS`, `LOG_LEVEL` | `daily`, `30`, `warning`                                                                       |
| `DB_*`                                     | Alohida foydalanuvchi va kuchli parol (root emas)                                              |
| `SESSION_ENCRYPT`, `SESSION_SECURE_COOKIE` | `true`, `true`                                                                                 |
| `QUEUE_CONNECTION`, `DB_QUEUE_RETRY_AFTER` | `database`, `3700`                                                                             |
| `CACHE_STORE`, `CACHE_PREFIX`              | `database` (yoki `redis`), `ivj_`                                                              |

### 4.2. Pochta

| O'zgaruvchi                             | Izoh                                                       |
| --------------------------------------- | ---------------------------------------------------------- |
| `MAIL_MAILER`                           | `smtp` (`log` — xatlar yuborilmaydi, faqat logga yoziladi) |
| `MAIL_HOST`, `MAIL_PORT`, `MAIL_SCHEME` | Masalan Gmail: `smtp.gmail.com`, `587`, `null`             |
| `MAIL_USERNAME`, `MAIL_PASSWORD`        | Gmail uchun «App password»; hech kimga ko'rsatmang         |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`   | `noreply@insonvajamiyat.uz`, `Inson va Jamiyat`            |

### 4.3. Jurnal

| O'zgaruvchi                                           | Standart                        | Izoh                                                                    |
| ----------------------------------------------------- | ------------------------------- | ----------------------------------------------------------------------- |
| `JOURNAL_ISSN`, `JOURNAL_EISSN`, `JOURNAL_DOI_PREFIX` | —                               | Bo'sh bo'lsa saytda ko'rsatilmaydi                                      |
| `JOURNAL_FREQUENCY`                                   | Yiliga 4 marta                  |                                                                         |
| `JOURNAL_EMAIL`, `JOURNAL_PHONE`, `JOURNAL_ADDRESS`   |                                 | Aloqa formasi xatlari `JOURNAL_EMAIL`ga boradi                          |
| `JOURNAL_PLAGIARISM_MAX`                              | 20                              | Plagiat chegarasi, %                                                    |
| `JOURNAL_PAYMENT_*`                                   | —                               | Bank rekvizitlari (qabul qiluvchi, bank, hisob, MFO, STIR)              |
| `JOURNAL_*_URL`                                       | —                               | Ijtimoiy tarmoqlar                                                      |
| `JOURNAL_PROOF_DAYS`, `JOURNAL_PROOF_REMINDER_HOURS`  | 5, 24                           | Korrektura muddati va eslatma                                           |
| `JOURNAL_ARTICLE_TEMPLATE`                            | `downloads/maqola-shablon.docx` | Admin «Fayllar»da shablon yuklanmagan bo'lsa ishlatiladigan zaxira fayl |
| `AUDIT_RETENTION_DAYS`                                | 365                             | Audit log saqlash muddati                                               |
| `QPDF_BINARY`, `ISSUE_PDF_TIMEOUT`                    | `qpdf`, 300                     | Son PDF'ini yig'ish                                                     |

### 4.4. To'lov tizimlari

| O'zgaruvchi                                                                  | Izoh                                                         |
| ---------------------------------------------------------------------------- | ------------------------------------------------------------ |
| `CLICK_ENABLED`, `CLICK_SERVICE_ID`, `CLICK_MERCHANT_ID`, `CLICK_SECRET_KEY` | Click SHOP API (to'lov qabul qilish)                         |
| `CLICK_MERCHANT_USER_ID`                                                     | Click Merchant API — to'lovni **qaytarish** (reversal) uchun |
| `PAYME_ENABLED`, `PAYME_MERCHANT_ID`, `PAYME_KEY`                            | Payme Merchant API                                           |
| `PAYME_TEST_MODE`                                                            | Sinov kassasida `true`, ishchi rejimda `false`               |
| `PAYME_IKPU`, `PAYME_PACKAGE_CODE`, `PAYME_VAT_PERCENT`                      | Elektron chek (fiskalizatsiya): MXIK kodi va qadoq kodi      |

### 4.5. Kirish va AI

| O'zgaruvchi                                                                               | Izoh                                      |
| ----------------------------------------------------------------------------------------- | ----------------------------------------- |
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`                                                | Bo'sh bo'lsa Google tugmasi ko'rinmaydi   |
| `ORCID_CLIENT_ID`, `ORCID_CLIENT_SECRET`, `ORCID_SANDBOX`                                 | Bo'sh bo'lsa ORCID tugmasi ko'rinmaydi    |
| `ANTHROPIC_API_KEY`, `ANTHROPIC_MODEL`                                                    | AI Studio (admin panelda ham kiritiladi)  |
| `AI_ENABLED`, `AI_AUTHOR_MONTHLY_TOKENS`, `AI_STAFF_MONTHLY_TOKENS`, `AI_MAX_INPUT_CHARS` | Standart: `true`, 50 000, 200 000, 30 000 |

`.env` o'zgargach: `php artisan config:cache` (yoki `php artisan optimize`).

---

## 5. Tashqi xizmatlarni ulash

### 5.1. Pochta

Admin panel → **Tizim sozlamalari → Pochta** — SMTP ma'lumotlarini kiriting va **«Test xat yuborish»** bilan tekshiring. Barcha xatlar navbat orqali ketadi — worker ishlayotganiga ishonch hosil qiling.

### 5.2. Click

Click kabinetida (merchant.click.uz → Xizmatlar):

- **Prepare URL**: `https://insonvajamiyat.uz/payments/click/prepare`
- **Complete URL**: `https://insonvajamiyat.uz/payments/click/complete`

Qaytarish (reversal) uchun Merchant API foydalanuvchisi (`CLICK_MERCHANT_USER_ID`) kerak. Click faqat joriy hisobot oyidagi to'lovlarni qaytaradi; o'tgan oy to'lovi — faqat oyning 1-kuni va onlayn karta bilan to'langan bo'lsa.

### 5.3. Payme

Payme biznes kabinetida (Kassa → Sozlamalar):

- **Endpoint URL**: `https://insonvajamiyat.uz/payments/payme`
- **Hisob (account) maydoni**: `payment_id`

Avval **sinov kassasida** (`PAYME_TEST_MODE=true`) Payme'ning test sahifasidagi barcha ssenariylarni o'tkazing, so'ng ishchi kalitga o'ting.

**Qaytarish.** Payme Merchant API'da sotuvchi tomonidan pul qaytarish metodi yo'q: admin panelda qaytarish so'rovi ochiladi, so'ng tranzaksiya **business.payme.uz** kabinetida bekor qilinadi. Payme `CancelTransaction` so'rovini yuboradi va tizim holatni avtomatik yangilaydi. Ochiq so'rov bo'lmasa, bajarilgan tranzaksiyani bekor qilish `-31007` xatosi bilan rad etiladi.

### 5.4. Google va ORCID orqali kirish

- **Google** — Google Cloud Console → OAuth client ID (Web application): JavaScript origin `https://insonvajamiyat.uz`, redirect URI `https://insonvajamiyat.uz/auth/google/callback`; scope'lar `openid email profile`; consent screen'ni «In production» holatiga o'tkazing.
- **ORCID** — orcid.org → Developer tools → Public API: redirect URI `https://insonvajamiyat.uz/auth/orcid/callback`. Sinov uchun sandbox.orcid.org va `ORCID_SANDBOX=true`.

### 5.5. AI Studio

Anthropic konsolida API kalit yarating va admin panel → **AI Studio → Sozlamalar**ga kiriting (bazada shifrlangan holda saqlanadi). U yerda model, oylik token limitlari va prompt shablonlari boshqariladi.

### 5.6. Indekslash (OAI-PMH, Crossref, Google Scholar)

- **OAI-PMH**: `https://insonvajamiyat.uz/oai` — tekshirish: `/oai?verb=Identify`. Format `oai_dc`; to'plamlar `subject:{slug}` va `issue:{slug}`.

    | Baza           | Ro'yxatdan o'tish                           | Kiritiladi                                          |
    | -------------- | ------------------------------------------- | --------------------------------------------------- |
    | BASE           | base-search.net → Suggest repository        | OAI-PMH base URL                                    |
    | OpenAIRE       | provide.openaire.eu → Register a repository | OAI-PMH base URL, `oai_dc`                          |
    | CyberLeninka   | tahririyat murojaati                        | OAI-PMH base URL                                    |
    | Google Scholar | avtomatik                                   | maqola sahifasidagi `citation_*` teglari va sitemap |

- **Crossref**: son chop etilgach admin → Jurnallar → son → **Crossref XML** (sxema 5.4.0) → doi.crossref.org → Submissions → Upload → Metadata.
- **Google Search Console**ga `https://insonvajamiyat.uz/sitemap.xml` ni qo'shing.

---

## 6. Yangilash (har bir reliz)

```bash
cd /var/www/insonvajamiyat
sudo -u www-data bash deploy/deploy.sh
```

Skript tartibi: texnik rejim (`artisan down --render="errors::503"` — Vite assetlarisiz statik sahifa) → `git reset --hard origin/main` → `composer install --no-dev` → `wayfinder:generate` va `npm run build` → `migrate --force` → `optimize` → `queue:restart` → sayt yoqiladi → `php8.3-fpm` reload (OPcache). Xato bo'lsa sayt texnik rejimdan avtomatik chiqariladi.

**Relizdan oldin** (dasturchi kompyuterida yoki CI'da):

```bash
composer ci:check      # frontend lint/format, TypeScript, PHPStan, barcha testlar
```

### 6.1. Orqaga qaytarish (rollback)

1. `php artisan down`
2. Oldingi commit'ga qaytish: `git reset --hard <OLDINGI_COMMIT>` → `composer install --no-dev --optimize-autoloader` → `npm ci && npm run build`
3. Reliz yangi migratsiya qo'shgan bo'lsa — avval **zaxira nusxadan bazani tiklang** (7.2) yoki `php artisan migrate:rollback --step=N` (faqat migratsiyalar `down()` qismi to'g'ri ekaniga ishonch bo'lsa).
4. `php artisan optimize && php artisan queue:restart && php artisan up`

> Har bir relizdan oldin admin panelda **to'liq zaxira nusxa** yarating.

---

## 7. Zaxira nusxa va tiklash

### 7.1. Zaxira

- Admin panel → **Zaxira nusxa**: qo'lda yaratish (to'liq / faqat baza / faqat fayllar) va **avtomatik jadval** (standart 03:30, oxirgi 14 ta saqlanadi).
- Arxivlar: `storage/app/private/backups/`. Arxiv tarkibi: `database.sql.gz` va `files/public`, `files/private`.
- Server diskining buzilishidan himoya uchun arxivlarni muntazam **boshqa joyga** ko'chiring:

```bash
rsync -avz user@insonvajamiyat.uz:/var/www/insonvajamiyat/storage/app/private/backups/ ~/ivj-backups/
```

### 7.2. Tiklash

```bash
cd /var/www/insonvajamiyat
php artisan down
unzip backup-YYYY-MM-DD-HHMMSS-full.zip -d tiklash    # arxiv nomi admin paneldagi ro'yxatda
gunzip -c tiklash/database.sql.gz | mysql -u insonvajamiyat -p insonvajamiyat
cp -r tiklash/files/public/*  storage/app/public/
cp -r tiklash/files/private/* storage/app/private/
sudo chown -R www-data:www-data storage
php artisan optimize && php artisan queue:restart
php artisan up
rm -rf tiklash
```

> Arxivda shaxsiy ma'lumotlar va to'lovlar bor — uni shifrlangan yoki faqat administrator kira oladigan joyda saqlang.

---

## 8. Xavfsizlik

Tizimda amalga oshirilgan himoya choralari (TZ 7-bo'lim):

| Soha                 | Chora                                                                                                                                                                                                               |
| -------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Transport            | Majburiy HTTPS (HTTP→HTTPS 301), HSTS, TLS 1.2/1.3                                                                                                                                                                  |
| Sarlavhalar          | `Content-Security-Policy`, `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, `Cross-Origin-Opener-Policy`; admin va kabinet sahifalari `X-Robots-Tag: noindex`                  |
| Autentifikatsiya     | Bcrypt parollar; production'da 12+ belgi, murakkablik va sizib chiqqan parollar tekshiruvi; ixtiyoriy 2FA (TOTP + zaxira kodlar); email tasdiqlash                                                                  |
| Kirish cheklovi      | Login, 2FA, ro'yxatdan o'tish, parolni tiklash, to'lov, AI, xabar va boshqa formalarga **rate limiting**                                                                                                            |
| Avtorizatsiya        | Rollar va ruxsatlar (spatie/laravel-permission), Policy'lar; xodim qismi `staff` middleware bilan ajratilgan                                                                                                        |
| CSRF / SQL injection | Laravel CSRF tokeni; Eloquent va parametrli so'rovlar                                                                                                                                                               |
| Fayllar              | Tur (MIME + kengaytma) va hajm tekshiruvi; maqola fayllari `private` diskda, faqat ruxsatli foydalanuvchiga beriladi; taqrizchiga neytral nom bilan                                                                 |
| To'lov webhook'lari  | Click — `sign_string` imzosi; Payme — Basic auth kaliti; idempotentlik (bitta tranzaksiya — bitta yozuv, bitta maqola — bitta muvaffaqiyatli nashr to'lovi DB darajasida); har bir so'rov `payment_logs`ga yoziladi |
| Maxfiy kalitlar      | `.env` (600 huquq); SMTP paroli va AI kaliti bazada **shifrlangan**                                                                                                                                                 |
| Sessiya              | Shifrlangan, `Secure` va `SameSite=Lax` cookie                                                                                                                                                                      |
| Audit                | Barcha muhim amallar `audit_logs`da (kim, qachon, nima, IP), 365 kun saqlanadi                                                                                                                                      |
| Spam                 | Aloqa formasida honeypot va cheklov                                                                                                                                                                                 |

**Ekspluatatsiya tavsiyalari:**

- Serverga faqat SSH kalit bilan kiring; `ufw` bilan faqat 22, 80, 443 portlarini oching.
- `sudo apt upgrade` ni muntazam bajaring; `composer audit` va `npm audit` natijalarini kuzating.
- Barcha xodimlar, ayniqsa bosh administrator, **2FA**ni yoqsin.
- `APP_DEBUG=false` ekanini «Tizim holati» sahifasida tekshiring.

---

## 9. Monitoring va loglar

| Nima                   | Qayerda                                                                            |
| ---------------------- | ---------------------------------------------------------------------------------- |
| Ilova xatolari         | `storage/logs/laravel-YYYY-MM-DD.log`                                              |
| Navbat ishchilari      | `storage/logs/worker.log`, `sudo supervisorctl status`                             |
| Nginx                  | `/var/log/nginx/insonvajamiyat.{access,error}.log`                                 |
| To'lov so'rovlari      | `payment_logs` jadvali (har bir Click/Payme so'rovi, imzo natijasi, javob)         |
| Foydalanuvchi amallari | Admin panel → Audit log                                                            |
| Umumiy holat           | Admin panel → Tizim sozlamalari → **Tizim holati**; `https://insonvajamiyat.uz/up` |

Tashqi monitoring (masalan UptimeRobot) uchun `/up` manzilini kuzating.

---

## 10. Ishga tushirishdan oldin tekshiruv ro'yxati

Avtomatik tekshiruv — serverda:

```bash
php artisan app:launch-check          # xato bo'lsa 1 kodi bilan tugaydi
php artisan app:launch-check --json   # monitoring / CI uchun
```

Xuddi shu ro'yxat admin panelda: **Tizim sozlamalari → Tizim holati → «Ishga tushirishga tayyorlik»**. U sozlamalar, migratsiyalar, cron va navbat ishchisi tirikligi (har daqiqa / har 5 daqiqada yangilanadigan belgilar), kontent, to'lov usullari, Payme rejimi, bosh administratorlarda 2FA, demo hisoblar va zaxira holatini tekshiradi.

Qo'lda tekshiriladiganlar:

- [ ] `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...`
- [ ] `https://insonvajamiyat.uz/up` → 200
- [ ] `robots.txt` da `Sitemap:` qatori, `sitemap.xml` da maqolalar
- [ ] `curl -sI https://insonvajamiyat.uz | grep -iE "strict-transport|x-frame|x-content"`
- [ ] Admin → Tizim sozlamalari → Pochta → **Test xat yuborish**
- [ ] `sudo supervisorctl status` — worker `RUNNING`; parolni tiklash xati keladi
- [ ] `php artisan schedule:list` — 6 ta vazifa ko'rinadi
- [ ] Admin → Zaxira nusxa → **Hozir yaratish**, keyin avtomatik jadvalni yoqing
- [ ] Click va Payme kabinetlarida URL'lar; Payme sinov kassasida barcha ssenariylar
- [ ] Admin → Sozlamalar → **Fayllar**: maqola shabloni yuklangan
- [ ] Admin → Sozlamalar → **Sahifalar** va **Tahririyat kengashi** to'ldirilgan
- [ ] Admin → Tizim sozlamalari → **Jurnal**: ISSN, DOI prefiksi; **Rekvizitlar**
- [ ] `/oai?verb=Identify` XML qaytaradi
- [ ] Google Search Console'ga sitemap qo'shilgan

---

## 11. Muammolarni bartaraf etish

| Belgi                                         | Sabab va yechim                                                                               |
| --------------------------------------------- | --------------------------------------------------------------------------------------------- |
| 502 Bad Gateway                               | `systemctl status php8.3-fpm`; Nginx'dagi socket yo'li PHP versiyasiga mosmi                  |
| 500 xato                                      | `storage/logs/laravel-*.log`; ruxsatlar: `storage`, `bootstrap/cache` — `www-data`            |
| Xatlar ketmayapti                             | `supervisorctl status`, `storage/logs/worker.log`, admin → Tizim holati, pochta sozlamalari   |
| CSS/JS eski                                   | `npm run build` bajarilganmi; brauzerda Ctrl+F5                                               |
| O'zgarishlar ko'rinmayapti                    | `php artisan optimize:clear && php artisan optimize`; `systemctl reload php8.3-fpm` (OPcache) |
| Fayl yuklanmayapti (413)                      | Nginx `client_max_body_size` va PHP `upload_max_filesize` / `post_max_size`                   |
| Son PDF'i yig'ilmayapti                       | `qpdf --version` (11+), admin → Tizim holati; worker ishlayaptimi                             |
| Click «Sign check failed»                     | `CLICK_SECRET_KEY` va `CLICK_SERVICE_ID` kabinetdagi bilan bir xilmi                          |
| Payme `-32504`                                | `PAYME_KEY` (test/ishchi kalit) va `PAYME_TEST_MODE` mosmi                                    |
| AI so'rovlari «navbatda» qolib ketdi          | Worker ishlamayapti yoki API kaliti noto'g'ri — `worker.log`, AI Studio → Sozlamalar          |
| Avtomatik zaxira yoki eslatmalar ishlamayapti | `sudo crontab -u www-data -l`, `php artisan schedule:list`                                    |
