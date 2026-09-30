<?php

namespace Database\Seeders;

use App\Enums\PartnerType;
use App\Enums\PostType;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\ArticleType;
use App\Models\Event;
use App\Models\JournalIssue;
use App\Models\Partner;
use App\Models\Post;
use App\Models\RecommendedBook;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * FAQAT lokal ishlab chiqish uchun: bosh sahifa va katalogni to'ldiruvchi
 * namunaviy kontent (sonlar, maqolalar, e'lonlar, tadbirlar, hamkorlar).
 *
 *   php artisan db:seed --class=DemoContentSeeder
 */
class DemoContentSeeder extends Seeder
{
    /** @var array<int, array{0: string, 1: string}> [sarlavha, yo'nalish slug] */
    private const ARTICLES = [
        ["O'rta asrlar davrida Amir Temur davlatining ijtimoiy-siyosiy tizimi", 'history'],
        ["O'zbek xalq amaliy san'atining etnik xususiyatlari va zamonaviy talqinlari", 'ethnology'],
        ['Oila va jamiyatda ijtimoiy transformatsiyalar: Samarqand viloyati misolida', 'anthropology'],
        ['Sharq mutafakkirlarining inson va jamiyat haqidagi qarashlari', 'philosophy'],
        ['Alisher Navoiy asarlarida inson va jamiyat falsafasi', 'philology'],
        ['Buxoro vohasidagi qadimiy shaharlar: yangi arxeologik topilmalar', 'history'],
        ["Xiva xonligida me'morchilik an'analari va ularning o'ziga xosligi", 'cultural-studies'],
        ["Farg'ona vodiysi to'y marosimlarining etnografik tahlili", 'ethnography'],
        ['Raqamli davrda yoshlarning ijtimoiy identikligi', 'sociology'],
        ["Markaziy Osiyo xalqlarining an'anaviy turar joylari", 'ethnography'],
        ["Jadidlar ma'rifatchiligi va milliy uyg'onish g'oyalari", 'history'],
        ["Zamonaviy o'zbek oilasida qadriyatlar ierarxiyasi", 'sociology'],
        ["Forobiyning fozil shahar haqidagi ta'limoti va bugungi kun", 'philosophy'],
        ["Qoraqalpoq xalq og'zaki ijodida qahramonlik motivlari", 'philology'],
        ['Surxondaryo vohasi aholisining etnik tarkibi (XIX–XX asrlar)', 'ethnology'],
        ['Urbanizatsiya jarayonlarining mahalla instituti rivojiga ta\'siri', 'anthropology'],
    ];

    private const LAST_NAMES = ['Karimov', 'Abdullayeva', "To'xtayeva", 'Saidov', 'Rahmonov', 'Xolmirzayev', 'Qodirov', 'Yusupova', 'Safarov', 'Karimova', 'Ergashev', 'Nazarova'];

    private const FIRST_NAMES = ['Anvar', 'Zulfiya', 'Malika', 'Bobur', 'Nodir', 'Sardor', 'Rustam', 'Shahnoza', 'Dilshod', 'Nilufar', 'Jasur', 'Madina'];

    public function run(): void
    {
        $this->call(SubjectSeeder::class);

        $subjects = Subject::query()->pluck('id', 'slug');
        $type = ArticleType::query()->firstOrCreate(
            ['slug' => 'scientific_article'],
            ['name' => ['uz' => 'Ilmiy maqola', 'ru' => 'Научная статья', 'en' => 'Research article'], 'price' => 0],
        );
        $submitter = User::query()->where('email', 'author@insonvajamiyat.test')->first()
            ?? User::factory()->author()->createOne();

        // Sonlar: 1–3-sonlar, har biri ~3 oy oralig'ida (oxirgisi — eng yangi)
        $year = (int) now()->year;
        $issues = [];

        foreach ([1, 2, 3] as $number) {
            $issues[] = JournalIssue::factory()->published(
                now()->subDays((3 - $number) * 90 + 5),
            )->createOne([
                'year' => $year,
                'number' => $number,
                'slug' => "{$year}-{$number}",
                'doi' => "10.5281/zenodo.{$year}{$number}0",
                'title' => ['uz' => '"Inson va Jamiyat" ilmiy jurnali'],
                'description' => ['uz' => "Ushbu sonda jamiyat taraqqiyoti, tarixiy jarayonlar, etnologik tadqiqotlar va falsafiy qarashlarga oid ilmiy maqolalar o'rin olgan."],
            ]);
        }

        foreach (self::ARTICLES as $index => [$title, $subjectSlug]) {
            $issue = $issues[$index % 3];
            $publishedAt = now()->subDays(6 + $index * 11)->setTime(10, 0);

            $article = Article::factory()->published($publishedAt)->createOne([
                'submitter_id' => $submitter->id,
                'article_type_id' => $type->id,
                'subject_id' => $subjects[$subjectSlug] ?? null,
                'title' => ['uz' => $title],
                'slug' => Str::slug($title),
            ]);

            foreach (range(0, $index % 2) as $position) {
                ArticleAuthor::factory()->for($article)->create([
                    'last_name' => self::LAST_NAMES[($index + $position * 5) % count(self::LAST_NAMES)],
                    'first_name' => self::FIRST_NAMES[($index + $position * 7) % count(self::FIRST_NAMES)],
                    'sort_order' => $position,
                    'is_corresponding' => $position === 0,
                ]);
            }

            $issue->articles()->attach($article->id, [
                'position' => $index,
                'page_from' => 5 + $index * 8,
                'page_to' => 12 + $index * 8,
            ]);
        }

        $this->seedPosts();
        $this->seedEvents();
        $this->seedPartners();
        $this->seedBooks();
    }

    private function seedPosts(): void
    {
        $announcements = [
            ['Maqola qabul qilish muddati uzaytirildi', 'Navbatdagi son uchun maqolalar oy oxirigacha qabul qilinadi.'],
            ['Xalqaro ilmiy-amaliy konferensiya', "\"Yangi O'zbekiston: ilm, ta'lim va innovatsiya\" mavzusida konferensiya o'tkaziladi."],
            ['Tahririyat tarkibi yangilandi', "Tahririyat kengashiga yangi a'zolar qabul qilindi."],
        ];
        $news = [
            ['Jurnalning navbatdagi soni nashrga tayyor', "Yangi sonda tarix, etnologiya va falsafa yo'nalishlaridagi maqolalar o'rin oladi."],
            ["Xalqaro ilmiy hamkorlik bo'yicha yangi kelishuv", "Xorijiy universitetlar bilan qo'shma tadqiqotlar va taqrizchilar almashinuvi yo'lga qo'yiladi."],
            ['"Ilm va jamiyat" mavzusida ilmiy anjuman', "Anjumanda yosh tadqiqotchilar o'z ilmiy natijalarini taqdim etishdi."],
            ['Saytimizda yangi imkoniyatlar: maqola yuborish platformasi', 'Endi maqolalar onlayn yuboriladi va taqriz jarayoni shaxsiy kabinetda kuzatiladi.'],
        ];

        foreach ($announcements as $i => [$title, $excerpt]) {
            Post::factory()->announcement()->create([
                'title' => ['uz' => $title],
                'excerpt' => ['uz' => $excerpt],
                'slug' => Str::slug($title),
                'published_at' => now()->subDays(3 + $i * 9),
            ]);
        }

        foreach ($news as $i => [$title, $excerpt]) {
            Post::factory()->create([
                'type' => PostType::News,
                'title' => ['uz' => $title],
                'excerpt' => ['uz' => $excerpt],
                'slug' => Str::slug($title),
                'published_at' => now()->subDays(2 + $i * 7),
            ]);
        }
    }

    private function seedEvents(): void
    {
        $events = [
            ['Xalqaro ilmiy-amaliy konferensiya "Markaziy Osiyoda madaniy meros"', 'Toshkent', 12],
            ['"Ma\'naviyat va jamiyat" ilmiy forumi', 'Samarqand', 35],
            ['Yosh tadqiqotchilar konferensiyasi', 'Buxoro', 64],
        ];

        foreach ($events as [$title, $location, $days]) {
            Event::factory()->create([
                'title' => ['uz' => $title],
                'location' => ['uz' => $location],
                'slug' => Str::slug($title),
                'starts_at' => now()->addDays($days)->setTime(10, 0),
            ]);
        }
    }

    private function seedPartners(): void
    {
        $indexing = [
            ['Google Scholar', 'Indekslangan', 'https://scholar.google.com'],
            ['CrossRef', 'Hamkorlik', 'https://www.crossref.org'],
            ['Zenodo', 'DOI berish', 'https://zenodo.org'],
            ['OpenAIRE', 'Indekslangan', 'https://www.openaire.eu'],
        ];

        foreach ($indexing as $i => [$name, $subtitle, $url]) {
            Partner::factory()->create([
                'type' => PartnerType::Indexing,
                'name' => ['uz' => $name],
                'subtitle' => ['uz' => $subtitle],
                'url' => $url,
                'sort_order' => $i,
            ]);
        }

        $partners = [
            "O'zbekiston Respublikasi Oliy ta'lim, fan va innovatsiyalar vazirligi",
            'Yangi Asr universiteti',
            "O'zbekiston Milliy universiteti",
            'Toshkent davlat pedagogika universiteti',
        ];

        foreach ($partners as $i => $name) {
            Partner::factory()->create([
                'type' => PartnerType::Partner,
                'name' => ['uz' => $name],
                'url' => null,
                'sort_order' => $i,
            ]);
        }
    }

    private function seedBooks(): void
    {
        $books = [
            ["O'rta Osiyo xalqlari etnologiyasi", 'A. Karimov', 2023],
            ['Markaziy Osiyo tarixiy manbalari', 'B. Qosimov', 2022],
            ["O'zbek adabiyoti tarixi", 'D. Rahmonov', 2021],
        ];

        foreach ($books as $i => [$title, $author, $year]) {
            RecommendedBook::factory()->create([
                'title' => ['uz' => $title],
                'author' => $author,
                'year' => $year,
                'sort_order' => $i,
            ]);
        }
    }
}
