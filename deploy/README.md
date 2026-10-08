# Serverga o'rnatish (production)

Bu papkada saytni Ubuntu 24.04 serverga joylash uchun tayyor fayllar bor:

| Fayl                                    | Serverdagi joyi                                          | Vazifasi                                                     |
| --------------------------------------- | -------------------------------------------------------- | ------------------------------------------------------------ |
| `nginx/insonvajamiyat.conf`             | `/etc/nginx/sites-available/insonvajamiyat.conf`         | HTTPS, gzip, statik fayllar keshi, yashirin fayllarni yopish |
| `php/99-insonvajamiyat.ini`             | `/etc/php/8.3/fpm/conf.d/` va `/etc/php/8.3/cli/conf.d/` | Yuklash hajmi, OPcache                                       |
| `supervisor/insonvajamiyat-worker.conf` | `/etc/supervisor/conf.d/`                                | Navbat ishchilari (email, AI, PDF, zaxira)                   |
| `cron`                                  | `crontab -u www-data`                                    | Rejalashtiruvchi (`schedule:run` har daqiqa)                 |
| `deploy.sh`                             | loyiha ichida qoladi                                     | Har bir yangilanishda ishga tushiriladi                      |
| `../.env.production.example`            | `/var/www/insonvajamiyat/.env`                           | Production sozlamalari namunasi                              |

> Domen, yo'l (`/var/www/insonvajamiyat`) yoki PHP versiyasi boshqacha bo'lsa — fayllardagi
> mos qatorlarni almashtiring.

## 1. Talablar

- **PHP 8.3+** va kengaytmalar: `fpm cli mysql mbstring xml curl zip gd intl bcmath opcache`
- **MySQL 8** (yoki MariaDB 10.6+), `mysql-client` (zaxira uchun `mysqldump`)
- **Node.js 22+** va npm (faqat build uchun)
- **Composer 2**, **Nginx**, **Supervisor**, **certbot**
- **qpdf 11+** — jurnal sonining to'liq PDF'ini yig'ish uchun
- **unzip**, **git**

```bash
sudo apt update
sudo apt install -y nginx mysql-server mysql-client supervisor certbot python3-certbot-nginx \
  git unzip qpdf \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip \
  php8.3-gd php8.3-intl php8.3-bcmath php8.3-opcache

# Composer
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer

# Node.js 22
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash - && sudo apt install -y nodejs
```

## 2. Ma'lumotlar bazasi

```bash
sudo mysql
```

```sql
CREATE DATABASE insonvajamiyat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'insonvajamiyat'@'localhost' IDENTIFIED BY 'KUCHLI_PAROL';
GRANT ALL PRIVILEGES ON insonvajamiyat.* TO 'insonvajamiyat'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 3. Birinchi o'rnatish

```bash
sudo mkdir -p /var/www/insonvajamiyat && sudo chown -R $USER:www-data /var/www/insonvajamiyat
git clone <REPO_URL> /var/www/insonvajamiyat
cd /var/www/insonvajamiyat

cp .env.production.example .env
nano .env                      # DB_PASSWORD, MAIL_*, APP_URL va kalitlarni to'ldiring

composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder --force
php artisan storage:link

php artisan wayfinder:generate --with-form
npm ci && npm run build

# Huquqlar: storage va bootstrap/cache'ga faqat www-data yozadi
sudo chown -R www-data:www-data storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod 775 {} \;
sudo find storage bootstrap/cache -type f -exec chmod 664 {} \;
chmod 600 .env && sudo chown www-data:www-data .env

php artisan optimize
```

Birinchi bosh administratorni yaratish (`/register` orqali ro'yxatdan o'tib, keyin rol berish):

```bash
php artisan tinker --execute="App\Models\User::where('email','admin@insonvajamiyat.uz')->first()->syncRoles(['super_admin']);"
```

## 4. Nginx, PHP, SSL

```bash
sudo cp deploy/php/99-insonvajamiyat.ini /etc/php/8.3/fpm/conf.d/
sudo cp deploy/php/99-insonvajamiyat.ini /etc/php/8.3/cli/conf.d/
sudo systemctl restart php8.3-fpm

# Avval SSL qatorlarisiz ishlatish uchun certbot'ni oldin olamiz:
sudo certbot certonly --nginx -d insonvajamiyat.uz -d www.insonvajamiyat.uz

sudo cp deploy/nginx/insonvajamiyat.conf /etc/nginx/sites-available/
sudo ln -sf /etc/nginx/sites-available/insonvajamiyat.conf /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx

# Sertifikat avtomatik yangilanishini tekshirish
sudo certbot renew --dry-run
```

## 5. Navbat va rejalashtiruvchi

```bash
sudo cp deploy/supervisor/insonvajamiyat-worker.conf /etc/supervisor/conf.d/
sudo supervisorctl reread && sudo supervisorctl update
sudo supervisorctl start "insonvajamiyat-worker:*"
sudo supervisorctl status

sudo crontab -u www-data deploy/cron
php artisan schedule:list
```

## 6. Yangilash (har safar)

```bash
cd /var/www/insonvajamiyat
sudo -u www-data bash deploy/deploy.sh
```

Skript: texnik rejim → `git reset --hard origin/main` → composer → wayfinder + npm build →
migratsiya → `optimize` → `queue:restart` → sayt yoqiladi → php-fpm reload (OPcache).
Xato bo'lsa sayt avtomatik qayta yoqiladi.

> `www-data` uchun git va npm ishlashi kerak: `sudo -u www-data git -C /var/www/insonvajamiyat status`.
> Agar ruxsat bo'lmasa, deploy'ni o'z foydalanuvchingizdan ishga tushirib, oxirida
> `sudo chown -R www-data:www-data storage bootstrap/cache` bajaring.

## 7. Ishga tushirishdan oldin tekshiruv

- [ ] `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...`
- [ ] `https://insonvajamiyat.uz/up` → 200
- [ ] `https://insonvajamiyat.uz/robots.txt` — `Sitemap:` qatori bor
- [ ] `https://insonvajamiyat.uz/sitemap.xml` — maqolalar ro'yxati
- [ ] Sarlavhalar: `curl -sI https://insonvajamiyat.uz | grep -iE "strict-transport|x-frame|x-content"`
- [ ] Admin → Tizim sozlamalari → Pochta → **Test xat yuborish**
- [ ] Admin → Zaxira nusxa → **Hozir yaratish** (mysqldump ishlashini tekshiradi), keyin jadvalni yoqing
- [ ] Click / Payme kabinetida URL'lar: `/payments/click/prepare`, `/payments/click/complete`, `/payments/payme`
- [ ] Google Search Console'ga `sitemap.xml` ni qo'shing; Google Scholar uchun maqola sahifasida `citation_*` teglari bor
- [ ] `public/images/og-default.png` (1200×630) — ijtimoiy tarmoqlar uchun standart rasm; xohlasangiz o'zingiznikiga almashtiring
- [ ] Google / ORCID orqali kirish — quyidagi 7.1-bo'lim (kalitlar bo'lmasa tugmalar ko'rinmaydi)

### 7.1. Google va ORCID orqali kirish

**Google** — [Google Cloud Console](https://console.cloud.google.com/apis/credentials) → _Create credentials → OAuth client ID_:

- Application type: **Web application**
- Authorized JavaScript origins: `https://insonvajamiyat.uz`
- Authorized redirect URIs: `https://insonvajamiyat.uz/auth/google/callback`
- _OAuth consent screen_: ilova nomi, logotip, `insonvajamiyat.uz` domeni; scope'lar — `openid`, `email`, `profile`; holatni **In production** ga o'tkazing

**ORCID** — [orcid.org](https://orcid.org) → hisobingiz → _Developer tools_ → **Register for the free ORCID public API**:

- Website: `https://insonvajamiyat.uz`
- Redirect URI: `https://insonvajamiyat.uz/auth/orcid/callback`
- Sinov uchun avval [sandbox.orcid.org](https://sandbox.orcid.org) da ro'yxatdan o'tib, `ORCID_SANDBOX=true` bilan tekshirish mumkin

`.env` ga yozing va konfiguratsiya keshini yangilang:

```bash
GOOGLE_CLIENT_ID=...apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=...
ORCID_CLIENT_ID=APP-XXXXXXXXXXXXXXXX
ORCID_CLIENT_SECRET=...
ORCID_SANDBOX=false

php artisan config:cache
```

Tugmalardagi rasmiy belgilar (ixtiyoriy): Google va ORCID brend sahifalaridan SVG yuklab olib,
`public/images/social/google.svg` va `public/images/social/orcid.svg` nomi bilan qo'ying — fayl bo'lmasa umumiy ikonka chiqadi.

## 8. Zaxira nusxalar

Zaxiralar `storage/app/private/backups/` da saqlanadi (admin panel → Zaxira nusxa).
Server diski buzilsa ham saqlanib qolishi uchun ularni vaqti-vaqti bilan boshqa joyga ko'chiring:

```bash
rsync -avz user@insonvajamiyat.uz:/var/www/insonvajamiyat/storage/app/private/backups/ ~/ivj-backups/
```

Tiklash: arxivni oching → `gunzip -c database.sql.gz | mysql -u insonvajamiyat -p insonvajamiyat`,
`files/public/*` → `storage/app/public/`, `files/private/*` → `storage/app/private/`.

## 9. Muammolar

| Belgi                    | Sabab va yechim                                                          |
| ------------------------ | ------------------------------------------------------------------------ |
| 502 Bad Gateway          | `systemctl status php8.3-fpm`, socket yo'li nginx'dagi bilan bir xilmi   |
| Xatlar ketmayapti        | `supervisorctl status`, `storage/logs/worker.log`, Admin → Tizim → Holat |
| CSS/JS eski              | `npm run build` bajarilganmi, brauzerda Ctrl+F5                          |
| 500 xato                 | `storage/logs/laravel-YYYY-MM-DD.log`                                    |
| Fayl yuklanmayapti (413) | `client_max_body_size` (nginx) va `upload_max_filesize` (php.ini)        |
