# Sinov (test) hisoboti

**«Inson va Jamiyat» onlayn ilmiy jurnali veb-tizimi**

|        |                                                                                                          |
| ------ | -------------------------------------------------------------------------------------------------------- |
| Hujjat | Sinov hisoboti                                                                                           |
| Tizim  | insonvajamiyat.uz                                                                                        |
| Sana   | 2026-yil 8-oktabr                                                                                        |
| Asos   | Texnik topshiriq v1.0, 8 va 10-bo'limlar                                                                 |
| Natija | **387 ta avtomatik test, 14 450 ta tekshiruv — barchasi muvaffaqiyatli (0 xato, 0 o'tkazib yuborilgan)** |

---

## 1. Maqsad va qamrov

Sinovning maqsadi — tizimning texnik topshiriqdagi funksional va nofunksional talablarga mosligini, xavfsizligini va to'lov tizimlari bilan to'g'ri ishlashini tasdiqlash.

Qamrov:

- **Web (ommaviy) qism** — bosh sahifa, katalog, arxiv, maqola sahifasi, statik sahifalar, yangiliklar, ko'p tillilik, SEO;
- **Autentifikatsiya** — ro'yxatdan o'tish, email tasdiqlash, parol tiklash, 2FA, Google/ORCID;
- **Muallif kabineti** — maqola yuborish, to'lov, tuzatish sikli, korrektura, yozishma, AI Studio;
- **Admin panel** — barcha 17 bo'lim;
- **Integratsiyalar** — Click, Payme, Anthropic Claude API, OAI-PMH, Crossref;
- **Nofunksional talablar** — xavfsizlik, samaradorlik, tarjimalar to'liqligi.

---

## 2. Sinov muhiti va vositalar

| Komponent          | Versiya / sozlama                                                                                        |
| ------------------ | -------------------------------------------------------------------------------------------------------- |
| PHP                | 8.4 (sinov muhiti); production talabi 8.3+                                                               |
| Laravel            | 13.34                                                                                                    |
| Ma'lumotlar bazasi | Testlarda SQLite (xotirada, har bir test uchun toza baza — `RefreshDatabase`); production MySQL 8        |
| Navbat             | Testlarda `sync` (ishlar darhol bajariladi)                                                              |
| Pochta             | `array` drayveri — xatlar yuborilmaydi, tarkibi tekshiriladi                                             |
| Tashqi API'lar     | Click, Payme, Anthropic, Google, ORCID — `Http::fake()` va soxta webhook so'rovlari bilan almashtirilgan |
| Node.js            | 22                                                                                                       |
| qpdf               | 11.9 (son PDF'ini yig'ish testi uchun)                                                                   |

| Vosita                            | Vazifasi                                                          |
| --------------------------------- | ----------------------------------------------------------------- |
| **PHPUnit** (Laravel test runner) | Funksional (feature) va birlik (unit) testlar                     |
| **PHPStan + Larastan**, 7-daraja  | PHP kodining statik tahlili (tiplar, null xavfsizligi, o'lik kod) |
| **Laravel Pint**                  | PHP kod uslubi                                                    |
| **vp check** (oxlint + oxfmt)     | TypeScript/Vue lint va formatlash                                 |
| **vue-tsc**                       | Frontend tiplarini tekshirish                                     |
| **Playwright** (Chromium)         | Sahifalarni brauzerda ochib, skrinshot orqali vizual tekshirish   |
| **OAI-PMH.xsd**                   | OAI-PMH javoblarini rasmiy XML sxema bo'yicha validatsiya         |

Barcha tekshiruvlar bitta buyruq bilan ishga tushiriladi: `composer ci:check`. Har bir reliz (bundle) shu tekshiruvdan o'tkazilgan, testlar qo'shimcha ravishda Vite dev-server rejimida (`public/hot`) ham ishga tushirilgan.

---

## 3. Umumiy natijalar

| Ko'rsatkich               | Qiymat     |
| ------------------------- | ---------- |
| Test fayllari             | 70         |
| Testlar                   | **387**    |
| Tekshiruvlar (assertions) | **14 450** |
| Muvaffaqiyatli            | 387 (100%) |
| Xato / muvaffaqiyatsiz    | 0          |
| O'tkazib yuborilgan       | 0          |
| Bajarilish vaqti          | ~32 soniya |
| PHPStan (7-daraja)        | 0 xato     |
| Pint, vp check, vue-tsc   | 0 xato     |

---

## 4. Sohalar bo'yicha natijalar

### 4.1. Web (ommaviy) qism

| Test to'plami                                   | Tekshiriladi                                                               | Testlar | Tekshiruvlar | Natija |
| ----------------------------------------------- | -------------------------------------------------------------------------- | ------: | -----------: | :----: |
| HomePageTest                                    | Bosh sahifa, ommaviy maqola va son sahifalari                              |      11 |          166 |   ✔    |
| CatalogAndArchiveTest                           | Katalog qidiruvi va filtrlari, sonlar arxivi                               |       4 |           84 |   ✔    |
| PublishingTest                                  | Sonni chop etish, maqola sahifasi, PDF, hisoblagichlar                     |       6 |          101 |   ✔    |
| StaticPagesTest                                 | «Jurnal haqida», «Yo'riqnoma», «Aloqa», tahririyat kengashi, aloqa formasi |       5 |          150 |   ✔    |
| JournalDocumentsTest                            | Mualliflar uchun fayllar: yuklash, almashtirish, yuklab olish              |       2 |           76 |   ✔    |
| NewsAndEventsTest                               | Yangiliklar va tadbirlar                                                   |       5 |           81 |   ✔    |
| NewsletterSubscriptionTest                      | Yangiliklarga obuna                                                        |       4 |           24 |   ✔    |
| LocaleTest                                      | UZ / RU / EN tilini almashtirish                                           |       5 |           40 |   ✔    |
| InterfaceTranslationTest                        | Har bir interfeys matni rus va ingliz lug'atlarida borligi (2 325 kalit)   |       6 |        9 380 |   ✔    |
| SeoAndSecurityTest                              | Open Graph, Google Scholar teglari, sitemap, robots.txt                    |       5 |           59 |   ✔    |
| FrontendAuditTest                               | Meta description, tadbir vaqti                                             |       2 |           83 |   ✔    |
| ArticleCoverImporterTest, BookCoverImporterTest | Rasmlarni import qilish buyruqlari                                         |       5 |           36 |   ✔    |

### 4.2. Autentifikatsiya va hisob

| Test to'plami                                       | Tekshiriladi                                                                | Testlar | Tekshiruvlar | Natija |
| --------------------------------------------------- | --------------------------------------------------------------------------- | ------: | -----------: | :----: |
| RegistrationTest                                    | Ro'yxatdan o'tish, telefon formati, muallif roli                            |       8 |           24 |   ✔    |
| AuthenticationTest                                  | Kirish, chiqish, cheklovlar                                                 |       9 |           20 |   ✔    |
| EmailVerificationTest, VerificationNotificationTest | Email tasdiqlash                                                            |       9 |           24 |   ✔    |
| EmailNotificationsTest                              | O'zbekcha tasdiqlash va parol xatlari                                       |       7 |           29 |   ✔    |
| PasswordResetTest, PasswordConfirmationTest         | Parolni tiklash va tasdiqlash                                               |       7 |           20 |   ✔    |
| TwoFactorChallengeTest                              | 2FA kirish bosqichi                                                         |       2 |           10 |   ✔    |
| SocialLoginTest                                     | Google / ORCID: kirish, ro'yxatdan o'tish, bog'lash, uzish                  |      18 |          188 |   ✔    |
| ProfileUpdateTest, SecurityTest (Settings)          | Profil, parol, 2FA sozlamalari                                              |      13 |           76 |   ✔    |
| AreaAccessTest, DashboardTest                       | Web / kabinet / admin qismlarining ajratilganligi, rolga qarab yo'naltirish |      13 |          146 |   ✔    |
| RolesAndPermissionsTest                             | Rollar va ruxsatlar                                                         |       8 |           40 |   ✔    |

### 4.3. Muallif kabineti

| Test to'plami          | Tekshiriladi                                                          | Testlar | Tekshiruvlar | Natija |
| ---------------------- | --------------------------------------------------------------------- | ------: | -----------: | :----: |
| ArticleSubmissionTest  | 7 bosqichli forma: qoralama, bosqichlar, fayllar, yuborish, ruxsatlar |      17 |          112 |   ✔    |
| ArticleWorkflowTest    | Maqola holatlari mashinasi va timeline                                |       5 |           16 |   ✔    |
| AuthorArticlesTest     | Dashboard, «Mening maqolalarim», qaytarib olish, faqat o'z fayllari   |       7 |           83 |   ✔    |
| RevisionCycleTest      | Tuzatish sikli va yangi taqriz raundi                                 |       6 |           59 |   ✔    |
| ArticleMessagesTest    | Muallif ↔ tahririyat yozishmasi, fayllar                              |       5 |           64 |   ✔    |
| NotificationCenterTest | Bildirishnomalar                                                      |       6 |           85 |   ✔    |
| CabinetAiStudioTest    | Kabinetdagi AI Studio, Word eksport                                   |       1 |           44 |   ✔    |

### 4.4. Admin panel

| Test to'plami                         | Tekshiriladi                                                                  | Testlar | Tekshiruvlar | Natija |
| ------------------------------------- | ----------------------------------------------------------------------------- | ------: | -----------: | :----: |
| DashboardTest (Admin), NavigationTest | Dashboard, menyu, ruxsatlar va raqamlar                                       |       8 |          106 |   ✔    |
| EditorialWorkspaceTest                | Navbatlar, ko'rib chiqishga olish, qarorlar, mas'ul muharrir                  |       9 |          103 |   ✔    |
| ReviewProcessTest                     | Taqriz taklifi, qabul/rad, fayllarga kirish, topshirish, blind review         |       9 |          147 |   ✔    |
| PeopleTest                            | Mualliflar va taqrizchilar bo'limlari                                         |       5 |          203 |   ✔    |
| ProductionTest                        | Maketlash, yakuniy PDF, korrektura, tekshiruv, bosh muharrir tasdig'i         |       8 |          128 |   ✔    |
| ProofDeadlineTest                     | Korrektura muddati, eslatmalar, muallifsiz tasdiqlash                         |       4 |           55 |   ✔    |
| ArticleCoverTest, PdfPagesTest        | Maqola rasmi, PDF betlari va sahifalarni hisoblash                            |       5 |           52 |   ✔    |
| IssueManagementTest                   | Son yaratish, tarkib, tartib, fayllar, mundarija                              |      10 |           91 |   ✔    |
| IssuePdfBuildTest                     | To'liq son PDF'ini qpdf bilan yig'ish (xatcho'plar, sahifa belgilari)         |       3 |           41 |   ✔    |
| PaymentManagementTest                 | To'lovlar ro'yxati, qo'lda tasdiqlash, ozod qilish                            |       8 |          110 |   ✔    |
| PaymentRemindersTest                  | Avtomatik (3/7/14-kun) va qo'lda eslatmalar, sutkalik cheklov                 |       2 |           43 |   ✔    |
| RefundTest                            | Qaytarish: Click API, Payme kabinet + CancelTransaction, bank                 |       6 |           82 |   ✔    |
| AiStudioTest                          | Proofreader, Translator, Analytics, limitlar, sozlamalar                      |       8 |          132 |   ✔    |
| MessageCenterTest                     | Yozishmalar markazi, ommaviy xabar                                            |       3 |          127 |   ✔    |
| ReportsTest                           | Statistika, filtrlar, CSV eksport, PDF hisobot                                |       8 |          140 |   ✔    |
| SettingsTest, SettingsContentTest     | Yo'nalishlar, narxlar, bannerlar, yangiliklar, tadbirlar, kitoblar, hamkorlar |       9 |          202 |   ✔    |
| UserManagementTest                    | Foydalanuvchilar: yaratish, rollar, bloklash, o'chirish                       |      13 |          150 |   ✔    |
| RolesAndSystemTest                    | Ruxsatlar matritsasi, tizim sozlamalari, test xat                             |       4 |           94 |   ✔    |
| AuditLogTest                          | Audit yozuvlari, filtrlar, eksport, tozalash                                  |       5 |           77 |   ✔    |
| BackupTest                            | Zaxira arxivi tarkibi, yuklab olish, jadval                                   |       3 |           51 |   ✔    |

### 4.5. Integratsiyalar

| Test to'plami       | Tekshiriladi                                                                                                      | Testlar | Tekshiruvlar | Natija |
| ------------------- | ----------------------------------------------------------------------------------------------------------------- | ------: | -----------: | :----: |
| ClickPaymentTest    | Click SHOP API: Prepare → Complete, imzo, summa, takroriy so'rovlar                                               |       6 |           55 |   ✔    |
| PaymePaymentTest    | Payme Merchant API: avtorizatsiya, CheckPerform → Create → Perform, bekor qilish, 12 soatlik muddat, GetStatement |       5 |           84 |   ✔    |
| OaiPmhTest          | OAI-PMH 2.0: barcha verb'lar, xatolar, sahifalash, o'chirilgan yozuvlar — XSD bo'yicha                            |       5 |          223 |   ✔    |
| CrossrefDepositTest | Crossref DOI deposit XML (sxema 5.4.0)                                                                            |       3 |           50 |   ✔    |

### 4.6. Nofunksional talablar

| Test to'plami                           | Tekshiriladi                                                                                                                                                           | Testlar | Tekshiruvlar | Natija |
| --------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------: | -----------: | :----: |
| SecurityHardeningTest                   | Hisobni egallab olishdan himoya, muharrir o'z maqolasini ko'ra olmasligi, rate limiting, emailni o'zgartirishda parol, CSP, ochiq yo'naltirishdan himoya, private disk |      11 |           74 |   ✔    |
| PerformanceTest                         | Email navbatga qo'yilishi, ro'yxatlarda so'rovlar soni o'zgarmasligi (N+1 yo'q), indekslar                                                                             |       3 |           12 |   ✔    |
| PublicPathConflictTest                  | `public/` papkalari route'larni to'smasligi                                                                                                                            |       1 |            1 |   ✔    |
| PhoneNumberTest, UploadLimitTest (unit) | Telefon normalizatsiyasi, yuklash chegaralari                                                                                                                          |      10 |           21 |   ✔    |
| ErrorPagesTest                          | Brendlangan 404/403/500 sahifalari, 419 da qaytish, JSON va webhook javoblari o'zgarmasligi, statik 503 sahifasi                                                       |       6 |           63 |   ✔    |
| LaunchReadinessTest                     | «Ishga tushirishga tayyorlik»: tizim holati tabi, `app:launch-check`, cron va worker tiriklik belgilari, production to'siqlari                                         |       4 |           35 |   ✔    |

---

## 5. Texnik topshiriq talablariga moslik

| TZ bo'limi | Talab                                                                                       | Tasdiqlovchi testlar                                                                       | Holat |
| ---------- | ------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------ | :---: |
| 4.1.1      | Bosh sahifa, «Jurnal haqida», arxiv, katalog, maqola sahifasi (APA/GOST), yo'riqnoma, aloqa | HomePageTest, CatalogAndArchiveTest, PublishingTest, StaticPagesTest, JournalDocumentsTest |   ✔   |
| 4.1.1      | Ko'p tillilik UZ / RU / EN                                                                  | LocaleTest, InterfaceTranslationTest                                                       |   ✔   |
| 4.1.2      | Ro'yxatdan o'tish, email tasdiqlash, ORCID, parolni tiklash                                 | RegistrationTest, EmailVerificationTest, SocialLoginTest, PasswordResetTest                |   ✔   |
| 4.1.3      | Muallif profili, maqolalar va holatlar, yuborish formasi, shablon, fayllar, maqola turi     | ArticleSubmissionTest, AuthorArticlesTest, ProfileUpdateTest, JournalDocumentsTest         |   ✔   |
| 4.1.3      | Muharrir/taqrizchi bilan yozishmalar                                                        | ArticleMessagesTest, MessageCenterTest                                                     |   ✔   |
| 4.1.4      | Click va Payme protokollari, imzo tekshiruvi, idempotentlik, loglash                        | ClickPaymentTest, PaymePaymentTest                                                         |   ✔   |
| 4.1.4      | Qaytarish (refund)                                                                          | RefundTest                                                                                 |   ✔   |
| 4.1.5      | AI imlo va uslub tekshiruvi, qabul/rad, saqlash                                             | AiStudioTest                                                                               |   ✔   |
| 4.1.6      | AI ilmiy tarjima, tahrirlash, Word, versiyalar                                              | AiStudioTest, CabinetAiStudioTest                                                          |   ✔   |
| 4.2.1      | Login, 2FA, RBAC                                                                            | AuthenticationTest, TwoFactorChallengeTest, RolesAndPermissionsTest, NavigationTest        |   ✔   |
| 4.2.2      | Maqolalarni boshqarish, blind review, qarorlar, versiyalash                                 | EditorialWorkspaceTest, ReviewProcessTest, RevisionCycleTest                               |   ✔   |
| 4.2.3      | Sonlarni shakllantirish, muqova, mundarija, chop etish                                      | IssueManagementTest, IssuePdfBuildTest, PublishingTest                                     |   ✔   |
| 4.2.4      | Xodimlar, rollar, mualliflarni bloklash                                                     | UserManagementTest, RolesAndSystemTest                                                     |   ✔   |
| 4.2.5      | Bannerlar, statik sahifalar, shablon fayllari                                               | SettingsTest, SettingsContentTest, StaticPagesTest, JournalDocumentsTest                   |   ✔   |
| 4.2.6      | Statistika, ko'rishlar/yuklab olishlar, Excel/PDF eksport                                   | ReportsTest, PublishingTest                                                                |   ✔   |
| 4.2.7      | AI kaliti va prompt shablonlari                                                             | AiStudioTest                                                                               |   ✔   |
| 4.2.8      | Narxlar, to'lovlar ro'yxati, qo'lda tasdiqlash, eslatmalar, qaytarishlar tarixi             | SettingsTest, PaymentManagementTest, PaymentRemindersTest, RefundTest                      |   ✔   |
| 6          | AI navbat orqali, logga yozish, bo'laklarga bo'lish, yakuniy tasdiq muallifda               | AiStudioTest                                                                               |   ✔   |
| 7          | Xavfsizlik: CSRF, parollar, fayl tekshiruvi, imzolar, HTTPS sarlavhalari                    | SecurityHardeningTest, SeoAndSecurityTest                                                  |   ✔   |
| 7          | Unumdorlik va kesh                                                                          | PerformanceTest                                                                            |   ✔   |
| 7          | SEO: meta teglar, sitemap.xml                                                               | SeoAndSecurityTest, FrontendAuditTest                                                      |   ✔   |
| 7          | Zaxira nusxalash, audit log                                                                 | BackupTest, AuditLogTest                                                                   |   ✔   |
| 7          | DOI / Crossref va indekslash                                                                | CrossrefDepositTest, OaiPmhTest                                                            |   ✔   |

---

## 6. Qo'lda bajarilgan tekshiruvlar

- **Vizual tekshiruv** — har bir yangi yoki o'zgargan sahifa Playwright (Chromium, 1440 px) yordamida ochilib, skrinshot asosida dizayn maketlariga solishtirildi: bosh sahifa, katalog, arxiv, maqola sahifasi, statik sahifalar, kabinet, admin bo'limlari, dialog oynalari.
- **Mobil ko'rinish** — asosiy sahifalar tor ekranda (menyu, filtrlar paneli, jadvallarning gorizontal aylanishi).
- **Xavfsizlik auditi** (70-bosqich) — kirish nazorati, hisobni egallash ssenariylari, fayllarga kirish, rate limiting; topilgan kamchiliklar tuzatilib, regressiya testlari qo'shilgan.
- **Samaradorlik auditi** (71-bosqich) — N+1 so'rovlar, indekslar, emaillarni navbatga o'tkazish.
- **Frontend/SEO auditi** (72-bosqich) — meta teglar, gidratatsiya, kirish imkoniyatlari (a11y).
- **Yakuniy brauzer sinovi (rollar bo'yicha avtomatik aylanib chiqish)** — Playwright skripti har bir rol (mehmon, bosh administrator, bosh muharrir, muharrir, taqrizchi, maketchi, kontent menejeri, muallif) bilan tizimga kirib, sahifadagi barcha havolalar bo'ylab ~60–100 ta sahifani ochdi va quyidagilarni yozib bordi: HTTP 4xx/5xx javoblar, xato sahifalari, JavaScript xatolari (konsol, pageerror) va gorizontal siljish (sahifa ekrandan kengayib ketishi). Sinov 390, 768, 1024 va 1440 px kengliklarda takrorlandi. Natija: 5xx va JavaScript xatolari yo'q; ruxsat yo'q bo'limlar kutilganidek 403. Topilgan va tuzatilgan kamchiliklar: muallif kabinetining telefondagi ko'rinishi (statistika kartalari ekrandan chiqib ketardi) va bosh sahifadagi yo'nalishlar qatori 1024–1279 px ekranlarda. Sonning chop etiladigan mundarijasi (A4 varaq) telefonda kengroq bo'lishi kutilgan holat.
- **Hujjatlashtirish jarayonidagi tekshiruv** — qo'llanmalar yozilayotganda topilgan kamchiliklar tuzatildi: dashboard tezkor amallari va «Yangi kelgan maqolalar» jadvali havolalari, kabinetdagi AI kartasi, profil to'ldirilganini aniqlash, parol talabi matni, kabinetdagi foydali havolalar.

---

## 7. Cheklovlar va tavsiyalar

| №   | Cheklov                                                                                                               | Tavsiya                                                                                                                                                                         |
| --- | --------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Click va Payme testlari to'lov tizimlari so'rovlarini simulyatsiya qiladi; haqiqiy kassalar bilan sinov o'tkazilmagan | Shartnoma tuzilgach **Payme sinov kassasida** test sahifasidagi barcha ssenariylarni va Click test to'lovini o'tkazing; keyin ishchi kalitlarga o'ting                          |
| 2   | Click qaytarish API'si va Payme kabinetidan bekor qilish haqiqiy hisobda sinalmagan                                   | Birinchi qaytarishni kichik summali to'lovda tekshiring                                                                                                                         |
| 3   | Testlar SQLite'da bajariladi; production — MySQL 8                                                                    | Serverda o'rnatilgach `php artisan migrate` va asosiy ssenariylarni qo'lda tekshiring (ro'yxatdan o'tish, maqola yuborish, to'lov, son chop etish)                              |
| 4   | Yuklama (load) testlari o'tkazilmagan                                                                                 | Ishga tushirishdan oldin k6 yoki Apache Bench bilan bosh sahifa, katalog va maqola sahifasini 50–100 bir vaqtdagi foydalanuvchida sinang; maqsad — sahifa yuklanishi < 2 soniya |
| 5   | Haqiqiy SMTP orqali xat yetkazilishi testlarda tekshirilmaydi                                                         | Admin panel → Tizim sozlamalari → Pochta → «Test xat yuborish»; SPF/DKIM yozuvlarini sozlang                                                                                    |
| 6   | Anthropic API haqiqiy so'rovlari testlarda simulyatsiya qilinadi                                                      | API kalit kiritilgach har bir AI xizmatida bitta qisqa so'rov yuboring                                                                                                          |
| 7   | Brauzer moslik testlari Chromium'da bajarilgan                                                                        | Firefox, Safari (iOS) va mobil Chrome'da asosiy sahifalarni ko'rib chiqing                                                                                                      |

---

## 8. Xulosa

Tizimning barcha 433 ta avtomatik testi muvaffaqiyatli o'tdi, statik tahlil va kod uslubi tekshiruvlarida xato yo'q. Texnik topshiriqning 4-bo'limidagi funksional talablar va 7-bo'limidagi nofunksional talablar avtomatik testlar bilan qoplangan. 7-bo'limda keltirilgan haqiqiy to'lov kassalari, pochta va yuklama bo'yicha sinovlar server muhitida, ishga tushirishdan oldin bajarilishi tavsiya etiladi.

**Testlarni qayta ishga tushirish:**

```bash
composer install && npm ci
php artisan test            # faqat testlar
composer ci:check           # lint + tiplar + PHPStan + testlar
```
