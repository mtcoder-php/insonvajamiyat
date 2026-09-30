# Inson va Jamiyat — onlayn ilmiy jurnal platformasi

**insonvajamiyat.uz** — ilmiy maqolalarni onlayn qabul qilish, taqriz, tahrir,
AI yordamida tekshiruv/tarjima va nashr qilish tizimi.

## Texnologiyalar

| Qatlam             | Texnologiya                                        |
| ------------------ | -------------------------------------------------- |
| Backend            | Laravel 13, PHP 8.3+                               |
| Frontend           | Vue 3 (Composition API, TypeScript), Inertia.js v3 |
| UI                 | Tailwind CSS 4, shadcn-vue (reka-ui)               |
| Build              | Vite 8 (vite-plus)                                 |
| Auth               | Laravel Fortify (email tasdiqlash, 2FA)            |
| Rollar             | spatie/laravel-permission                          |
| Ko'p tillilik      | spatie/laravel-translatable (uz / ru / en)         |
| Ma'lumotlar bazasi | MySQL 8                                            |

## Tizim qismlari

Tizim uchta mustaqil qismga ajratilgan — har birining o'z route fayli,
middleware'i, layout'i va sahifalar papkasi bor:

| Qism             | URL                   | Route fayli          | Sahifalar                      | Layout          | Kimlar uchun                          |
| ---------------- | --------------------- | -------------------- | ------------------------------ | --------------- | ------------------------------------- |
| Web (public)     | `/`, `/articles`, ... | `routes/web.php`     | `resources/js/pages/web/*`     | `WebLayout`     | Hamma                                 |
| Muallif kabineti | `/cabinet/*`          | `routes/cabinet.php` | `resources/js/pages/cabinet/*` | `CabinetLayout` | Ro'yxatdan o'tgan, email tasdiqlangan |
| Admin panel      | `/admin/*`            | `routes/admin.php`   | `resources/js/pages/admin/*`   | `AdminLayout`   | Faqat xodimlar (`staff` middleware)   |

Login'dan keyin `/dashboard` foydalanuvchini roliga qarab yo'naltiradi:
xodim → `/admin`, muallif → `/cabinet`.

## Rollar

| Rol                     | Kod               | Qanday beriladi                             |
| ----------------------- | ----------------- | ------------------------------------------- |
| Bosh administrator      | `super_admin`     | `php artisan app:create-super-admin`        |
| Bosh muharrir           | `chief_editor`    | Super Admin                                 |
| Muharrir                | `editor`          | Super Admin                                 |
| Taqrizchi               | `reviewer`        | Super Admin                                 |
| Texnik xodim (maketchi) | `layout_editor`   | Super Admin                                 |
| Kontent-menejer         | `content_manager` | Super Admin                                 |
| Muallif                 | `author`          | Saytda ro'yxatdan o'tish orqali (avtomatik) |

Saytda **faqat muallif** ro'yxatdan o'ta oladi. Rollar va ruxsatlar
`app/Enums/RoleName.php` va `app/Enums/PermissionName.php` da belgilangan.

## O'rnatish (lokal)

```bash
git clone <repo-url> insonvajamiyat
cd insonvajamiyat

cp .env.example .env            # DB_* sozlamalarini to'ldiring
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed             # rollar, yo'nalishlar + lokal demo hisoblar va kontent
php artisan storage:link        # yuklangan muqova/banner rasmlari uchun

npm install
composer run dev                # server + vite + queue
```

Demo hisoblar (`APP_ENV=local` bo'lganda seed qilinadi):

| Email                        | Rol         |
| ---------------------------- | ----------- |
| admin@insonvajamiyat.test    | Super Admin |
| editor@insonvajamiyat.test   | Muharrir    |
| reviewer@insonvajamiyat.test | Taqrizchi   |
| author@insonvajamiyat.test   | Muallif     |

Lokal seed bosh sahifa uchun namunaviy kontent ham yaratadi (3 ta son,
16 ta maqola, e'lonlar, tadbirlar, hamkorlar — `DemoContentSeeder`).

## Serverga joylashtirish (production)

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder --force
php artisan db:seed --class=SubjectSeeder --force   # asosiy ilmiy yo'nalishlar
php artisan storage:link
php artisan app:create-super-admin       # birinchi administrator (parol interaktiv)
npm ci && npm run build
php artisan optimize
```

> Rollar seed qilinmasa, ro'yxatdan o'tish ishlamaydi — yangi muallifga
> `author` roli biriktiriladi.

## Testlar

```bash
php artisan test
```

## Hujjatlar

- Texnik topshiriq: loyiha papkasidagi TZ (v1.0, 2026-yil sentyabr)
- DB sxemasi: `database/migrations/2026_09_29_*`
