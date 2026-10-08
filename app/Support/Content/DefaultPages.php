<?php

namespace App\Support\Content;

use App\Enums\PageSlug;

/**
 * Statik sahifalarning boshlang'ich matni (bazada yozuv bo'lmasa ko'rsatiladi).
 * Tahririyat admin panel → Sozlamalar → Sahifalar bo'limida o'zgartiradi — shundan keyin bazadagi matn ishlatiladi.
 *
 * Matn formati (PageService / RichText.vue):
 *   - xatboshilar bo'sh qator bilan ajratiladi;
 *   - "- " bilan boshlangan qatorlar — ro'yxat, "1. " — raqamli ro'yxat;
 *   - **qalin** matn;
 *   - {journal}, {email}, {plagiarism_max} — avtomatik almashtiriladi.
 */
final class DefaultPages
{
    /**
     * @return array{title: array<string, string>, description: array<string, string>, sections: list<array{heading: array<string, string>, body: array<string, string>}>}
     */
    public static function for(PageSlug $slug): array
    {
        return match ($slug) {
            PageSlug::About => self::about(),
            PageSlug::Guidelines => self::guidelines(),
            PageSlug::Contact => self::contact(),
        };
    }

    /**
     * @return array{title: array<string, string>, description: array<string, string>, sections: list<array{heading: array<string, string>, body: array<string, string>}>}
     */
    private static function about(): array
    {
        return [
            'title' => ['uz' => 'Jurnal haqida', 'ru' => 'О журнале', 'en' => 'About the journal'],
            'description' => [
                'uz' => 'Missiya, ilmiy yo\'nalishlar, taqriz siyosati, tahririyat kengashi va indekslash ma\'lumotlari.',
                'ru' => 'Миссия, научные направления, политика рецензирования, редакционный совет и индексирование.',
                'en' => 'Mission, scope, peer review policy, editorial board and indexing.',
            ],
            'sections' => [
                [
                    'heading' => ['uz' => 'Missiyamiz', 'ru' => 'Наша миссия', 'en' => 'Our mission'],
                    'body' => [
                        'uz' => "«{journal}» — tarix, etnologiya, antropologiya, falsafa va ijtimoiy fanlar bo'yicha original tadqiqotlarni nashr etadigan ilmiy jurnal.\n\nMaqsadimiz — inson va jamiyatni o'rganishga bag'ishlangan sifatli ilmiy ishlarni keng ilmiy jamoatchilikka ochiq holda yetkazish, yosh tadqiqotchilarni qo'llab-quvvatlash va xalqaro ilmiy hamkorlikni rivojlantirish.",
                        'ru' => "«{journal}» — научный журнал, публикующий оригинальные исследования по истории, этнологии, антропологии, философии и социальным наукам.\n\nНаша цель — открыто доносить до научного сообщества качественные работы, посвящённые изучению человека и общества, поддерживать молодых исследователей и развивать международное научное сотрудничество.",
                        'en' => "«{journal}» is a scholarly journal publishing original research in history, ethnology, anthropology, philosophy and the social sciences.\n\nOur aim is to make high-quality research on humanity and society openly available to the academic community, to support early-career researchers and to foster international scientific cooperation.",
                    ],
                ],
                [
                    'heading' => ['uz' => 'Taqriz siyosati', 'ru' => 'Политика рецензирования', 'en' => 'Peer review policy'],
                    'body' => [
                        'uz' => "Jurnalga kelgan barcha maqolalar **yashirin taqriz** (blind review) asosida baholanadi: taqrizchi muallifni, muallif esa taqrizchini bilmaydi.\n\n- Maqola avval tahririyat tomonidan jurnal yo'nalishi va talablarga mosligi bo'yicha ko'rib chiqiladi.\n- So'ng soha mutaxassislari bo'lgan mustaqil taqrizchilarga yuboriladi.\n- Taqriz natijasiga ko'ra maqola qabul qilinadi, tuzatishga qaytariladi yoki rad etiladi.\n- Yakuniy qarorni tahririyat qabul qiladi.",
                        'ru' => "Все статьи проходят **слепое рецензирование**: рецензент не знает автора, автор — рецензента.\n\n- Сначала редакция проверяет соответствие статьи профилю журнала и требованиям.\n- Затем статья направляется независимым рецензентам — специалистам в данной области.\n- По итогам рецензирования статья принимается, возвращается на доработку или отклоняется.\n- Окончательное решение принимает редакция.",
                        'en' => "All submissions undergo **blind peer review**: reviewers do not know the authors and authors do not know the reviewers.\n\n- The editorial office first checks the manuscript against the journal's scope and requirements.\n- It is then sent to independent reviewers who are experts in the field.\n- Based on the reviews, the manuscript is accepted, returned for revision or rejected.\n- The final decision is made by the editorial office.",
                    ],
                ],
                [
                    'heading' => ['uz' => 'Ochiq kirish', 'ru' => 'Открытый доступ', 'en' => 'Open access'],
                    'body' => [
                        'uz' => "Jurnalda nashr etilgan barcha maqolalar ochiq kirishda: ularni ro'yxatdan o'tmasdan bepul o'qish va yuklab olish mumkin. Har bir maqolaga DOI beriladi va metama'lumotlar xalqaro ilmiy bazalarga uzatiladi.",
                        'ru' => 'Все статьи журнала находятся в открытом доступе: их можно бесплатно читать и скачивать без регистрации. Каждой статье присваивается DOI, а метаданные передаются в международные научные базы.',
                        'en' => 'All articles are published in open access and can be read and downloaded free of charge without registration. Each article receives a DOI and its metadata is shared with international scholarly databases.',
                    ],
                ],
                [
                    'heading' => ['uz' => 'Nashr etikasi', 'ru' => 'Публикационная этика', 'en' => 'Publication ethics'],
                    'body' => [
                        'uz' => "Tahririyat COPE (Nashr etikasi qo'mitasi) tamoyillariga amal qiladi.\n\n- Maqola original bo'lishi va boshqa nashrda ko'rib chiqilmayotgan bo'lishi kerak.\n- Matnning boshqa manbalar bilan o'xshashligi {plagiarism_max}% dan oshmasligi lozim.\n- Sun'iy intellekt vositalaridan foydalanilgan bo'lsa, bu maqolada ko'rsatiladi; AI muallif sifatida ko'rsatilmaydi.\n- Manfaatlar to'qnashuvi bo'lsa, muallif va taqrizchi bu haqda tahririyatni xabardor qiladi.",
                        'ru' => "Редакция следует принципам COPE (Комитета по публикационной этике).\n\n- Статья должна быть оригинальной и не рассматриваться в другом издании.\n- Сходство текста с другими источниками не должно превышать {plagiarism_max}%.\n- Использование инструментов искусственного интеллекта указывается в статье; ИИ не может быть автором.\n- О конфликте интересов автор и рецензент сообщают редакции.",
                        'en' => "The editorial office follows the principles of COPE (Committee on Publication Ethics).\n\n- Manuscripts must be original and not under consideration elsewhere.\n- Text similarity with other sources must not exceed {plagiarism_max}%.\n- Any use of artificial intelligence tools must be disclosed; AI cannot be listed as an author.\n- Authors and reviewers must report any conflict of interest to the editorial office.",
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array{title: array<string, string>, description: array<string, string>, sections: list<array{heading: array<string, string>, body: array<string, string>}>}
     */
    private static function guidelines(): array
    {
        return [
            'title' => ['uz' => 'Mualliflar uchun yo\'riqnoma', 'ru' => 'Требования к авторам', 'en' => 'Author guidelines'],
            'description' => [
                'uz' => 'Maqola tayyorlash qoidalari, rasmiylashtirish talablari, yuborish tartibi va Word shablon.',
                'ru' => 'Правила подготовки статьи, требования к оформлению, порядок подачи и шаблон Word.',
                'en' => 'Manuscript preparation rules, formatting requirements, submission process and Word template.',
            ],
            'sections' => [
                [
                    'heading' => ['uz' => 'Umumiy talablar', 'ru' => 'Общие требования', 'en' => 'General requirements'],
                    'body' => [
                        'uz' => "- Maqola o'zbek, rus yoki ingliz tilida yoziladi.\n- Avval nashr etilmagan va boshqa jurnalda ko'rib chiqilmayotgan bo'lishi kerak.\n- Matnning boshqa manbalar bilan o'xshashligi {plagiarism_max}% dan oshmasligi lozim.\n- Maqola hajmi odatda 8–20 bet (adabiyotlar ro'yxati bilan).",
                        'ru' => "- Статья пишется на узбекском, русском или английском языке.\n- Она не должна быть ранее опубликована или рассматриваться в другом журнале.\n- Сходство текста с другими источниками не должно превышать {plagiarism_max}%.\n- Объём статьи — как правило, 8–20 страниц (вместе со списком литературы).",
                        'en' => "- Manuscripts are accepted in Uzbek, Russian or English.\n- They must not be previously published or under consideration elsewhere.\n- Text similarity with other sources must not exceed {plagiarism_max}%.\n- The usual length is 8–20 pages, including references.",
                    ],
                ],
                [
                    'heading' => ['uz' => 'Maqola tuzilishi', 'ru' => 'Структура статьи', 'en' => 'Manuscript structure'],
                    'body' => [
                        'uz' => "1. UDK indeksi.\n2. Sarlavha (o'zbek, rus va ingliz tillarida).\n3. Mualliflar: F.I.Sh., ilmiy daraja, tashkilot, email va ORCID.\n4. Annotatsiya (150–250 so'z) va kalit so'zlar (5–8 ta) — uch tilda.\n5. Kirish, tadqiqot usullari, natijalar, muhokama va xulosa.\n6. Adabiyotlar ro'yxati.",
                        'ru' => "1. Индекс УДК.\n2. Название (на узбекском, русском и английском языках).\n3. Авторы: Ф.И.О., учёная степень, организация, email и ORCID.\n4. Аннотация (150–250 слов) и ключевые слова (5–8) — на трёх языках.\n5. Введение, методы, результаты, обсуждение и заключение.\n6. Список литературы.",
                        'en' => "1. UDC index.\n2. Title (in Uzbek, Russian and English).\n3. Authors: full name, academic degree, affiliation, email and ORCID.\n4. Abstract (150–250 words) and keywords (5–8) in three languages.\n5. Introduction, methods, results, discussion and conclusion.\n6. References.",
                    ],
                ],
                [
                    'heading' => ['uz' => 'Rasmiylashtirish', 'ru' => 'Оформление', 'en' => 'Formatting'],
                    'body' => [
                        'uz' => "- Format: A4, hoshiyalar — 2 sm.\n- Shrift: Times New Roman, 14 pt, qatorlar oralig'i — 1,5.\n- Jadval va rasmlar raqamlanadi va matnda ularga havola beriladi.\n- Iqtiboslar kvadrat qavsda: [3, 45-b.].\n\nTayyor **Word shablon**ni quyida yuklab olib, maqolangizni shu shablon asosida tayyorlang.",
                        'ru' => "- Формат: A4, поля — 2 см.\n- Шрифт: Times New Roman, 14 pt, межстрочный интервал — 1,5.\n- Таблицы и рисунки нумеруются, в тексте на них даются ссылки.\n- Ссылки — в квадратных скобках: [3, с. 45].\n\nСкачайте готовый **шаблон Word** ниже и оформите статью по нему.",
                        'en' => "- Format: A4, 2 cm margins.\n- Font: Times New Roman, 14 pt, 1.5 line spacing.\n- Tables and figures are numbered and referenced in the text.\n- In-text citations in square brackets: [3, p. 45].\n\nDownload the **Word template** below and prepare your manuscript using it.",
                    ],
                ],
                [
                    'heading' => ['uz' => 'Adabiyotlar ro\'yxati', 'ru' => 'Список литературы', 'en' => 'References'],
                    'body' => [
                        'uz' => "Adabiyotlar GOST yoki APA standartida, matnda keltirilish tartibida raqamlanadi. Kamida 10 ta manba, ulardan bir qismi so'nggi 5–10 yildagi ishlar bo'lishi tavsiya etiladi. Lotin yozuvida bo'lmagan manbalar uchun transliteratsiya beriladi.",
                        'ru' => 'Список литературы оформляется по ГОСТ или APA и нумеруется в порядке цитирования. Рекомендуется не менее 10 источников, часть из которых — работы последних 5–10 лет. Для источников не на латинице приводится транслитерация.',
                        'en' => 'References follow the GOST or APA style and are numbered in order of citation. At least 10 sources are recommended, some of them from the last 5–10 years. Non-Latin sources should be transliterated.',
                    ],
                ],
                [
                    'heading' => ['uz' => 'Yuborish tartibi', 'ru' => 'Порядок подачи', 'en' => 'Submission process'],
                    'body' => [
                        'uz' => "1. Saytda ro'yxatdan o'ting va profilingizni to'ldiring.\n2. Kabinetda «Yangi maqola yuborish» bo'limida maqola turini tanlang, ma'lumotlarni kiriting va faylni yuklang.\n3. Nashr to'lovini Click, Payme yoki bank orqali amalga oshiring.\n4. Maqola holatini kabinetda kuzating — tahririyat va taqriz natijalari shu yerda ko'rinadi.",
                        'ru' => "1. Зарегистрируйтесь на сайте и заполните профиль.\n2. В кабинете в разделе «Отправить новую статью» выберите тип статьи, заполните данные и загрузите файл.\n3. Оплатите публикацию через Click, Payme или банк.\n4. Следите за статусом статьи в кабинете — там отображаются решения редакции и результаты рецензирования.",
                        'en' => "1. Register on the website and complete your profile.\n2. In your dashboard, open «Submit a new article», choose the article type, fill in the details and upload the file.\n3. Pay the publication fee via Click, Payme or bank transfer.\n4. Track the article status in your dashboard, where editorial decisions and review results appear.",
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array{title: array<string, string>, description: array<string, string>, sections: list<array{heading: array<string, string>, body: array<string, string>}>}
     */
    private static function contact(): array
    {
        return [
            'title' => ['uz' => 'Aloqa', 'ru' => 'Контакты', 'en' => 'Contact'],
            'description' => [
                'uz' => 'Tahririyat bilan bog\'lanish: manzil, telefon, elektron pochta va xabar yuborish formasi.',
                'ru' => 'Связь с редакцией: адрес, телефон, электронная почта и форма обратной связи.',
                'en' => 'Contact the editorial office: address, phone, email and message form.',
            ],
            'sections' => [
                [
                    'heading' => ['uz' => 'Tahririyat', 'ru' => 'Редакция', 'en' => 'Editorial office'],
                    'body' => [
                        'uz' => "Ish vaqti: dushanba — juma, 9:00 — 18:00.\n\nMaqola holati bo'yicha savollarni **kabinetdagi xabarlar** orqali yuboring — javob tezroq keladi va yozishma maqolaga biriktiriladi.",
                        'ru' => "Часы работы: понедельник — пятница, 9:00 — 18:00.\n\nВопросы о статусе статьи отправляйте через **сообщения в кабинете** — так ответ придёт быстрее, а переписка будет привязана к статье.",
                        'en' => "Office hours: Monday to Friday, 9:00 — 18:00.\n\nPlease send questions about a submitted article via **messages in your dashboard** — you will get a faster reply and the conversation stays attached to the article.",
                    ],
                ],
            ],
        ];
    }
}
