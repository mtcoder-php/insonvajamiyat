<?php

/*
|--------------------------------------------------------------------------
| Zaxira nusxa (Admin → Zaxira nusxa)
|--------------------------------------------------------------------------
| Arxiv: ma'lumotlar bazasi (database.sql.gz) + yuklangan fayllar (public va private
| disklari, backups papkasidan tashqari) + manifest.json — bitta .zip faylda.
| Jadval va saqlash soni admin paneldan boshqariladi (settings → "backup" guruhi).
|
| MySQL/MariaDB uchun mysqldump, PostgreSQL uchun pg_dump kerak:
|   sudo apt install mysql-client   (yoki mariadb-client / postgresql-client)
*/

return [

    'disk' => env('BACKUP_DISK', 'local'),

    'directory' => env('BACKUP_DIRECTORY', 'backups'),

    'mysqldump' => env('BACKUP_MYSQLDUMP', 'mysqldump'),

    'pg_dump' => env('BACKUP_PG_DUMP', 'pg_dump'),

    // Bitta zaxira jarayonining eng uzoq davomiyligi (soniya)
    'timeout' => (int) env('BACKUP_TIMEOUT', 1800),

    // Standart qiymatlar (admin panelda o'zgartiriladi)
    'defaults' => [
        'enabled' => false,
        'time' => '03:30',
        'type' => 'full',
        'keep' => 14,
    ],
];
