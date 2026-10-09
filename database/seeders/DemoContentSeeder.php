<?php

namespace Database\Seeders;

use App\Enums\AiRequestType;
use App\Enums\ArticleStatus;
use App\Enums\PartnerType;
use App\Enums\PaymentProvider;
use App\Enums\PaymentPurpose;
use App\Enums\PostType;
use App\Models\AiRequest;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\ArticleStatusHistory;
use App\Models\ArticleType;
use App\Models\Event;
use App\Models\JournalIssue;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\Post;
use App\Models\RecommendedBook;
use App\Models\Subject;
use App\Models\User;
use App\Services\Payments\ManualPaymentService;
use App\Services\Web\ArticleCoverImporter;
use App\Services\Web\BookCoverImporter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
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

    /** @var array<string, int> Maqola turlari uchun namunaviy narxlar (so'm) */
    private const DEMO_PRICES = [
        'scientific_article' => 150000,
        'review_article' => 150000,
        'thesis' => 80000,
        'express' => 300000,
    ];

    /** @var array<string, array<int, string>> Yo'nalish bo'yicha namunaviy kalit so'zlar */
    private const KEYWORDS = [
        'history' => ['Tarix', 'Davlatchilik', 'Manbashunoslik', 'Temuriylar'],
        'ethnology' => ['Etnologiya', 'Madaniyat', "An'ana", 'Etnos'],
        'ethnography' => ['Etnografiya', 'Marosim', 'Urf-odat', 'Turmush tarzi'],
        'anthropology' => ['Antropologiya', 'Oila', 'Jamiyat', 'Transformatsiya'],
        'philosophy' => ['Falsafa', 'Inson', 'Axloq', 'Ijtimoiy ong'],
        'philology' => ['Filologiya', 'Adabiyot', 'Matn', 'Folklor'],
        'sociology' => ['Sotsiologiya', 'Yoshlar', 'Identiklik', "So'rovnoma"],
        'cultural-studies' => ['Madaniyat', "Me'morchilik", 'Meros', 'San\'at'],
    ];

    /** @var array<string, string> Holatga o'tishda muallifga ko'rinadigan izoh */
    private const STATUS_COMMENTS = [
        'awaiting_payment' => "Maqolangiz dastlabki tekshiruvdan o'tdi. Nashr to'lovini amalga oshiring.",
        'under_review' => "Maqolangiz muharrir tomonidan ko'rib chiqilmoqda.",
        'in_review' => 'Maqolangiz taqrizchiga yuborildi. Taqriz natijasi haqida xabar beramiz.',
        'revision_required' => "Maqolangizga kichik o'zgartirishlar kiritish talab qilinmoqda. Taqrizchi izohlari bilan tanishib chiqing.",
        'accepted' => 'Tabriklaymiz! Maqolangiz nashrga qabul qilindi.',
        'in_production' => 'Maqolangiz sahifalash (maket) bosqichiga topshirildi.',
    ];

    /** @var array<int, array{0: string, 1: string}> [sarlavha, qisqa matn] */
    private const ANNOUNCEMENTS = [
        ['Maqola qabul qilish muddati uzaytirildi', 'Navbatdagi son uchun maqolalar oy oxirigacha qabul qilinadi.'],
        ['Xalqaro ilmiy-amaliy konferensiya', "\"Yangi O'zbekiston: ilm, ta'lim va innovatsiya\" mavzusida konferensiya o'tkaziladi."],
        ['Tahririyat tarkibi yangilandi', "Tahririyat kengashiga yangi a'zolar qabul qilindi."],
    ];

    /** @var array<int, array{0: string, 1: string}> [sarlavha, qisqa matn] */
    private const NEWS = [
        ['Jurnalning navbatdagi soni nashrga tayyor', "Yangi sonda tarix, etnologiya va falsafa yo'nalishlaridagi maqolalar o'rin oladi."],
        ["Xalqaro ilmiy hamkorlik bo'yicha yangi kelishuv", "Xorijiy universitetlar bilan qo'shma tadqiqotlar va taqrizchilar almashinuvi yo'lga qo'yiladi."],
        ['"Ilm va jamiyat" mavzusida ilmiy anjuman', "Anjumanda yosh tadqiqotchilar o'z ilmiy natijalarini taqdim etishdi."],
        ['Saytimizda yangi imkoniyatlar: maqola yuborish platformasi', 'Endi maqolalar onlayn yuboriladi va taqriz jarayoni shaxsiy kabinetda kuzatiladi.'],
        ['Jurnal maqolalariga DOI raqamlari berila boshlandi', 'Har bir nashr etilgan maqolaga xalqaro DOI identifikatori biriktiriladi.'],
        ['Taqrizchilar uchun seminar-trening bo\'lib o\'tdi', 'Seminarda ilmiy taqriz yozish standartlari va akademik halollik masalalari muhokama qilindi.'],
        ["Mualliflar uchun yo'riqnoma yangilandi", "Maqolalarni rasmiylashtirish, adabiyotlar ro'yxati va annotatsiya talablari aniqlashtirildi."],
    ];

    /** @var array<int, array{0: string, 1: string, 2: int}> [nom, joy, necha kundan keyin] */
    private const EVENTS = [
        ['Xalqaro ilmiy-amaliy konferensiya "Markaziy Osiyoda madaniy meros"', 'Toshkent', 12],
        ['"Ma\'naviyat va jamiyat" ilmiy forumi', 'Samarqand', 35],
        ['Yosh tadqiqotchilar konferensiyasi', 'Buxoro', 64],
        ["Etnografik ekspeditsiyalar natijalari bo'yicha davra suhbati", 'Xiva', 88],
        ['Ilmiy maqola yozish bo\'yicha onlayn master-klass', 'Onlayn', 110],
        ['"Tarix va xotira" ilmiy seminari', 'Toshkent', -20],
    ];

    /** @var array<int, array{0: string, 1: string, 2: string}> [nom, izoh, url] */
    private const INDEXING = [
        ['Google Scholar', 'Indekslangan', 'https://scholar.google.com'],
        ['CrossRef', 'Hamkorlik', 'https://www.crossref.org'],
        ['Zenodo', 'DOI berish', 'https://zenodo.org'],
        ['OpenAIRE', 'Indekslangan', 'https://www.openaire.eu'],
    ];

    /** @var array<int, array{0: string, 1: string}> [nom, sayt] */
    private const PARTNERS = [
        ["O'zbekiston Respublikasi Oliy ta'lim, fan va innovatsiyalar vazirligi", 'https://edu.uz'],
        ['Yangi Asr universiteti', 'https://yangiasr.uz'],
        ["O'zbekiston Milliy universiteti", 'https://nuu.uz'],
        ['Toshkent davlat pedagogika universiteti', 'https://tdpu.uz'],
    ];

    /** @var array<int, array{0: string, 1: string, 2: int}> [nom, muallif, yil] */
    private const BOOKS = [
        ["O'rta Osiyo xalqlari etnologiyasi", 'A. Karimov', 2023],
        ['Markaziy Osiyo tarixiy manbalari', 'B. Qosimov', 2022],
        ["O'zbek adabiyoti tarixi", 'D. Rahmonov', 2021],
    ];

    private const POST_BODY = "Tahririyat ushbu yangilik yuzasidan barcha mualliflar, taqrizchilar va o'quvchilarni xabardor qiladi. Batafsil ma'lumot uchun jurnal tahririyatiga elektron pochta yoki telefon orqali murojaat qilishingiz mumkin.\n\n«Inson va Jamiyat» ilmiy jurnali ijtimoiy-gumanitar fanlar sohasidagi tadqiqotlarni keng jamoatchilikka yetkazish va ilmiy hamkorlikni rivojlantirishga xizmat qiladi.";

    private const EVENT_DESCRIPTION = "Tadbirda ijtimoiy-gumanitar fanlar sohasidagi olimlar, tadqiqotchilar va doktorantlar ishtirok etadi. Ma'ruzalar asosida tayyorlangan eng yaxshi maqolalar jurnalning navbatdagi sonlarida nashr etilishi mumkin.\n\nIshtirok etish uchun oldindan ro'yxatdan o'tish talab etiladi.";

    private const LAST_NAMES = ['Karimov', 'Abdullayeva', "To'xtayeva", 'Saidov', 'Rahmonov', 'Xolmirzayev', 'Qodirov', 'Yusupova', 'Safarov', 'Karimova', 'Ergashev', 'Nazarova'];

    private const FIRST_NAMES = ['Anvar', 'Zulfiya', 'Malika', 'Bobur', 'Nodir', 'Sardor', 'Rustam', 'Shahnoza', 'Dilshod', 'Nilufar', 'Jasur', 'Madina'];

    public function run(): void
    {
        $this->call(SubjectSeeder::class);

        $subjects = Subject::query()->pluck('id', 'slug');
        $this->call(ArticleTypeSeeder::class);

        // Lokal sinov uchun namunaviy narxlar (pullik tur → "To'lov kutilmoqda" oqimi)
        foreach (self::DEMO_PRICES as $slug => $price) {
            ArticleType::query()->where('slug', $slug)->update(['price' => $price]);
        }

        $type = ArticleType::query()->where('slug', 'scientific_article')->firstOrFail();
        $submitter = User::query()->where('email', 'author@insonvajamiyat.test')->first()
            ?? User::factory()->author()->createOne(['name' => 'Muallif', 'email' => 'author@insonvajamiyat.test']);

        // Qayta ishga tushirilganda oldingi demo ma'lumotlar o'chiriladi (dublikat bo'lmasligi uchun)
        $this->purgeDemoContent($submitter);

        // Sonlar: o'tgan yilning 3–4-sonlari va joriy yilning 1-soni (eng yangisi,
        // bosh sahifadagi "So'nggi son" — dizayndagi kabi maxsus son)
        $year = (int) now()->year;
        $description = "Ushbu sonda jamiyat taraqqiyoti, tarixiy jarayonlar, etnologik tadqiqotlar va falsafiy qarashlarga oid ilmiy maqolalar o'rin olgan.";
        $issues = [];

        foreach ([[$year - 1, 3, 185], [$year - 1, 4, 95], [$year, 1, 5]] as [$issueYear, $number, $daysAgo]) {
            $isLatest = $issueYear === $year;

            $issues[] = JournalIssue::factory()->published(now()->subDays($daysAgo))->createOne([
                'year' => $issueYear,
                'number' => $number,
                'slug' => "{$issueYear}-{$number}",
                'doi' => "10.12345/rj.{$issueYear}.{$number}",
                'title' => ['uz' => $isLatest
                    ? 'Maxsus son: "Markaziy Osiyo: tarix, madaniyat va inson"'
                    : '"Inson va Jamiyat" ilmiy jurnali'],
                'description' => ['uz' => $description],
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
                'keywords' => ['uz' => self::KEYWORDS[$subjectSlug]],
                'updated_at' => $publishedAt,
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
        $this->seedDashboardData($submitter, $type->id, $subjects->all());

        // Maqola va kitob rasmlari: public/web/article/, public/web/books/ (bo'lsa).
        // Testlarda haqiqiy storage'ga fayl yozmaslik uchun o'tkazib yuboriladi.
        if (! app()->runningUnitTests()) {
            app(ArticleCoverImporter::class)->import();
            app(BookCoverImporter::class)->import();
        }
    }

    /**
     * Shu seeder avval yaratgan demo yozuvlarni o'chiradi — seeder'ni qayta
     * ishga tushirish mumkin bo'ladi. Faqat demo belgilari bo'yicha:
     * demo muallifning maqolalari / to'lovlari / AI so'rovlari, DOI 10.12345/rj.* sonlar,
     * ro'yxatdagi e'lon, tadbir, hamkor va kitoblar, DemoNotification bildirishnomalari.
     */
    private function purgeDemoContent(User $submitter): void
    {
        DB::transaction(function () use ($submitter): void {
            $articleIds = DB::table('articles')
                ->where('submitter_id', $submitter->id)
                ->orWhereIn('slug', array_map(fn (array $article): string => Str::slug($article[0]), self::ARTICLES))
                ->pluck('id');
            $paymentIds = DB::table('payments')->where('user_id', $submitter->id)->pluck('id');
            $issueIds = DB::table('journal_issues')->where('doi', 'like', '10.12345/rj.%')->pluck('id');

            DB::table('refunds')->whereIn('payment_id', $paymentIds)->delete();
            DB::table('payments')->whereIn('id', $paymentIds)->delete();
            DB::table('ai_requests')->where('user_id', $submitter->id)->delete();

            // issue_articles.article_id — restrictOnDelete; qolgan bog'liq jadvallar cascade
            DB::table('issue_articles')
                ->whereIn('article_id', $articleIds)
                ->orWhereIn('journal_issue_id', $issueIds)
                ->delete();
            DB::table('articles')->whereIn('id', $articleIds)->delete();
            DB::table('journal_issues')->whereIn('id', $issueIds)->delete();

            $postSlugs = array_map(
                fn (array $post): string => Str::slug($post[0]),
                [...self::ANNOUNCEMENTS, ...self::NEWS],
            );
            DB::table('posts')->whereIn('slug', $postSlugs)->delete();
            DB::table('events')->whereIn('slug', array_map(fn (array $event): string => Str::slug($event[0]), self::EVENTS))->delete();

            $partnerNames = [...array_column(self::INDEXING, 0), ...array_column(self::PARTNERS, 0)];
            Partner::query()->whereIn('name->uz', $partnerNames)->get()->each->delete();
            RecommendedBook::query()->whereIn('title->uz', array_column(self::BOOKS, 0))->get()->each->delete();

            DB::table('notifications')->where('type', 'App\\Notifications\\DemoNotification')->delete();
        });
    }

    private function seedPosts(): void
    {
        foreach (self::ANNOUNCEMENTS as $i => [$title, $excerpt]) {
            Post::factory()->announcement()->create([
                'title' => ['uz' => $title],
                'excerpt' => ['uz' => $excerpt],
                'slug' => Str::slug($title),
                'published_at' => now()->subDays(3 + $i * 9),
            ]);
        }

        foreach (self::NEWS as $i => [$title, $excerpt]) {
            Post::factory()->create([
                'type' => PostType::News,
                'title' => ['uz' => $title],
                'excerpt' => ['uz' => $excerpt],
                'slug' => Str::slug($title),
                'body' => ['uz' => $excerpt."\n\n".self::POST_BODY],
                'published_at' => now()->subDays(2 + $i * 7),
            ]);
        }
    }

    private function seedEvents(): void
    {
        foreach (self::EVENTS as [$title, $location, $days]) {
            Event::factory()->create([
                'title' => ['uz' => $title],
                'location' => ['uz' => $location],
                'description' => ['uz' => self::EVENT_DESCRIPTION],
                'slug' => Str::slug($title),
                'starts_at' => now()->addDays($days)->setTime(10, 0),
            ]);
        }
    }

    private function seedPartners(): void
    {
        foreach (self::INDEXING as $i => [$name, $subtitle, $url]) {
            Partner::factory()->create([
                'type' => PartnerType::Indexing,
                'name' => ['uz' => $name],
                'subtitle' => ['uz' => $subtitle],
                'url' => $url,
                'sort_order' => $i,
            ]);
        }

        foreach (self::PARTNERS as $i => [$name, $url]) {
            Partner::factory()->create([
                'type' => PartnerType::Partner,
                'name' => ['uz' => $name],
                'url' => $url,
                'sort_order' => $i,
            ]);
        }
    }

    private function seedBooks(): void
    {
        foreach (self::BOOKS as $i => [$title, $author, $year]) {
            RecommendedBook::factory()->create([
                'title' => ['uz' => $title],
                'author' => $author,
                'year' => $year,
                'sort_order' => $i,
            ]);
        }
    }

    /**
     * Admin dashboard uchun: turli holatdagi maqolalar, holat tarixi,
     * to'lovlar, AI so'rovlari, oxirgi kirishlar va bildirishnomalar.
     *
     * @param  array<array-key, mixed>  $subjects
     */
    private function seedDashboardData(User $submitter, int $typeId, array $subjects): void
    {
        $inProgress = [
            ['Zamonaviy ta\'lim texnologiyalari va ularning samaradorligi', ArticleStatus::Submitted],
            ["O'zbekistonda raqamli iqtisodiyot rivojlanishi", ArticleStatus::UnderReview],
            ["Sun'iy intellektning ta'lim tizimidagi roli", ArticleStatus::RevisionRequired],
            ['Milliy qadriyatlar va zamonaviy ta\'lim', ArticleStatus::Accepted],
            ['Axborot xavfsizligi: muammolar va yechimlar', ArticleStatus::InReview],
            ["Qadimgi Xorazm sug'orish tizimlari", ArticleStatus::Submitted],
            ['Mahalla institutining ijtimoiy funksiyalari', ArticleStatus::Resubmitted],
            ["O'zbek to'y marosimlarining zamonaviy talqini", ArticleStatus::InProduction],
            ["Temuriylar davri me'morchiligi", ArticleStatus::Accepted],
            ['Yoshlar orasida kitobxonlik madaniyati', ArticleStatus::AwaitingPayment],
        ];

        $subjectIds = array_values($subjects);
        $subjectSlugs = array_keys($subjects);

        foreach ($inProgress as $i => [$title, $status]) {
            $submittedAt = now()->subHours(6 + $i * 29);
            $subjectIndex = $i % max(1, count($subjectIds));

            $article = Article::factory()->status($status)->createOne([
                'submitter_id' => $submitter->id,
                'article_type_id' => $typeId,
                'subject_id' => $subjectIds[$subjectIndex] ?? null,
                'title' => ['uz' => $title],
                'keywords' => ['uz' => self::KEYWORDS[(string) ($subjectSlugs[$subjectIndex] ?? '')] ?? []],
                'submitted_at' => $submittedAt,
                'accepted_at' => in_array($status, [ArticleStatus::Accepted, ArticleStatus::InProduction], true)
                    ? $submittedAt->copy()->addHours(3)
                    : null,
            ]);

            ArticleAuthor::factory()->for($article)->create([
                'last_name' => self::LAST_NAMES[($i + 3) % count(self::LAST_NAMES)],
                'first_name' => self::FIRST_NAMES[($i + 2) % count(self::FIRST_NAMES)],
                'is_corresponding' => true,
            ]);

            ArticleStatusHistory::query()->create([
                'article_id' => $article->id,
                'from_status' => ArticleStatus::Draft,
                'to_status' => ArticleStatus::Submitted,
                'created_at' => $submittedAt,
            ]);

            // Muallif kabinetidagi timeline uchun real ketma-ketlik
            $path = $this->statusPath($status);
            $previous = ArticleStatus::Submitted;
            $stepMinutes = intdiv((6 + $i * 29) * 60, count($path) + 1);

            foreach ($path as $step => $to) {
                ArticleStatusHistory::query()->create([
                    'article_id' => $article->id,
                    'from_status' => $previous,
                    'to_status' => $to,
                    'comment' => self::STATUS_COMMENTS[$to->value] ?? null,
                    'created_at' => $submittedAt->copy()->addMinutes($stepMinutes * ($step + 1)),
                ]);
                $previous = $to;
            }

            // "Oxirgi yangilanish" — oxirgi holat o'zgarishi vaqti
            Article::query()->whereKey($article->id)->update([
                'updated_at' => $submittedAt->copy()->addMinutes($stepMinutes * count($path)),
            ]);
        }

        // To'lovlar: yil boshidan hozirgacha, har oy bir nechtadan
        for ($m = 0; $m < (int) now()->month; $m++) {
            foreach ([PaymentProvider::Click, PaymentProvider::Payme] as $p => $provider) {
                foreach (range(1, 2 + ($m % 3) + $p) as $n) {
                    $paidAt = now()->startOfYear()->addMonths($m)->addDays($n * 3 + $p)->setTime(10 + $n, 15);

                    // Kelajakdagi sana bo'lmasin (joriy oy boshida)
                    if ($paidAt->isFuture()) {
                        continue;
                    }

                    $payment = Payment::factory()->paid($paidAt)->createOne([
                        'created_at' => $paidAt,
                        'user_id' => $submitter->id,
                        'purpose' => PaymentPurpose::Publication,
                        'provider' => $provider,
                        'amount' => [150000, 200000, 250000, 80000][($m + $n + $p) % 4],
                    ]);
                    $payment->forceFill(['receipt_number' => ManualPaymentService::receiptNumber($payment)])->save();
                }
            }
        }

        Payment::factory()->create([
            'user_id' => $submitter->id,
            'provider' => PaymentProvider::Click,
            'amount' => 200000,
        ]);

        // AI so'rovlari: joriy va o'tgan oy
        foreach ([[now(), 42], [now()->subMonthNoOverflow(), 34]] as [$month, $count]) {
            foreach (range(1, $count) as $n) {
                AiRequest::factory()->create([
                    'user_id' => $submitter->id,
                    'type' => AiRequestType::cases()[$n % 3 === 0 ? 1 : ($n % 5 === 0 ? 2 : 0)],
                    'created_at' => $month->copy()->startOfMonth()->addHours($n * 3),
                ]);
            }
        }

        // Oxirgi kirishlar (Faol foydalanuvchilar)
        foreach (['admin', 'editor', 'reviewer', 'author'] as $i => $login) {
            User::query()
                ->where('email', "{$login}@insonvajamiyat.test")
                ->update(['last_login_at' => now()->subMinutes([2, 12, 25, 60][$i])]);
        }

        // Super Admin uchun namunaviy bildirishnomalar
        $admin = User::query()->where('email', 'admin@insonvajamiyat.test')->first();

        if ($admin === null) {
            return;
        }

        $notifications = [
            ['article_submitted', 'Yangi maqola yuborildi', "\"Zamonaviy ta'lim texnologiyalari\" maqolasi", 10],
            ['reviewer_assigned', 'Taqrizchi tayinlandi', 'Axborot xavfsizligi maqolasiga taqrizchi biriktirildi', 32],
            ['payment_confirmed', "To'lov tasdiqlandi", "Click orqali 150 000 so'm", 60],
            ['published', 'Nashr etildi', 'Jurnalning navbatdagi soni chop etildi', 180],
            ['security', 'Xavfsizlik ogohlantirishi', 'Tizimga noma\'lum qurilmadan kirish qayd etildi', 300],
        ];

        foreach ($notifications as [$kind, $title, $message, $minutes]) {
            DB::table('notifications')->insert([
                'id' => (string) Str::uuid(),
                'type' => 'App\\Notifications\\DemoNotification',
                'notifiable_type' => User::class,
                'notifiable_id' => $admin->id,
                'data' => json_encode(['kind' => $kind, 'title' => $title, 'message' => $message], JSON_UNESCAPED_UNICODE),
                'read_at' => null,
                'created_at' => now()->subMinutes($minutes),
                'updated_at' => now()->subMinutes($minutes),
            ]);
        }
    }

    /**
     * "Yuborildi" dan keyingi holatlar zanjiri (namunaviy ma'lumot uchun).
     *
     * @return array<int, ArticleStatus>
     */
    private function statusPath(ArticleStatus $status): array
    {
        $review = [ArticleStatus::UnderReview, ArticleStatus::InReview];

        return match ($status) {
            ArticleStatus::AwaitingPayment => [ArticleStatus::AwaitingPayment],
            ArticleStatus::UnderReview => [ArticleStatus::UnderReview],
            ArticleStatus::InReview => $review,
            ArticleStatus::RevisionRequired => [...$review, ArticleStatus::RevisionRequired],
            ArticleStatus::Resubmitted => [...$review, ArticleStatus::RevisionRequired, ArticleStatus::Resubmitted],
            ArticleStatus::Accepted => [...$review, ArticleStatus::Accepted],
            ArticleStatus::InProduction => [...$review, ArticleStatus::Accepted, ArticleStatus::InProduction],
            default => [],
        };
    }
}
