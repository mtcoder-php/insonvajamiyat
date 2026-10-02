<?php

/*
| Fayl yuklash bilan bog'liq validatsiya xabarlari (o'zbekcha).
| Qolgan kalitlar fallback_locale (en) dan olinadi.
*/

return [
    'uploaded' => ":attribute yuklanmadi. Fayl hajmi serverdagi chegaradan (php.ini: upload_max_filesize, post_max_size) katta bo'lishi mumkin.",
    'image' => ':attribute rasm bo\'lishi kerak.',
    'mimes' => ':attribute quyidagi formatlardan birida bo\'lishi kerak: :values.',
    'dimensions' => ':attribute o\'lchami (piksel) talabga mos emas.',
    'max' => [
        'file' => ':attribute hajmi :max KB dan oshmasligi kerak.',
        'string' => ':attribute :max belgidan oshmasligi kerak.',
        'numeric' => ':attribute :max dan katta bo\'lmasligi kerak.',
        'array' => ':attribute :max tadan ko\'p bo\'lmasligi kerak.',
    ],
];
