# Admin panel foydalanuvchi qo'llanmasi

**«Inson va Jamiyat» onlayn ilmiy jurnali — tahririyat xodimlari uchun**

|              |                                                                                                  |
| ------------ | ------------------------------------------------------------------------------------------------ |
| Hujjat       | Admin panel foydalanuvchi qo'llanmasi                                                            |
| Tizim        | insonvajamiyat.uz                                                                                |
| Kimlar uchun | Bosh administrator, bosh muharrir, muharrir, taqrizchi, texnik xodim (maketchi), kontent-menejer |
| Asos         | Texnik topshiriq v1.0, 4.2-bo'lim                                                                |

---

## 1. Umumiy ma'lumot

Admin panel — jurnal tahririyati ishlaydigan yopiq qism. U faqat xodim roliga ega va elektron pochtasi tasdiqlangan foydalanuvchilarga ochiladi. Manzil: **`https://insonvajamiyat.uz/admin`**.

Tizimga kirgach, `/dashboard` sizni rolingizga qarab yo'naltiradi: xodimlar — admin panelga, mualliflar — muallif kabinetiga.

### 1.1. Rollar

| Rol                                           | Vazifasi                                                                                                            | Kim beradi                                                                          |
| --------------------------------------------- | ------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------- |
| **Bosh administrator** (`super_admin`)        | Tizimning barcha qismlariga to'liq kirish, rollar va tizim sozlamalari                                              | Server buyrug'i `php artisan app:create-super-admin` yoki boshqa bosh administrator |
| **Bosh muharrir** (`chief_editor`)            | Maqolalar bo'yicha qaror, jurnal sonlarini shakllantirish va chop etish, nashrga yakuniy tasdiq, to'lovni qaytarish | Bosh administrator                                                                  |
| **Muharrir** (`editor`)                       | Maqolalarni ko'rib chiqish, taqrizchi tayinlash, qaror qabul qilish, muallif bilan yozishma                         | Bosh administrator                                                                  |
| **Taqrizchi** (`reviewer`)                    | Biriktirilgan maqolalarni baholash va taqriz yozish                                                                 | Bosh administrator yoki muharrir («Taqrizchilar» bo'limi)                           |
| **Texnik xodim (maketchi)** (`layout_editor`) | Maketlash, yakuniy PDF, son tarkibi va PDF yig'ish                                                                  | Bosh administrator                                                                  |
| **Kontent-menejer** (`content_manager`)       | Sayt kontenti: yangiliklar, tadbirlar, bannerlar, statik sahifalar                                                  | Bosh administrator                                                                  |
| **Muallif** (`author`)                        | Admin panelga kirmaydi — faqat muallif kabineti                                                                     | Saytda ro'yxatdan o'tganda avtomatik                                                |

Rolga biriktirilgan ruxsatlar 16-bo'limdagi matritsada keltirilgan. Bosh administrator ularni «Rollar va ruxsatlar» bo'limida o'zgartirishi mumkin.

### 1.2. Interfeys

- **Chap menyu (sidebar)** — sizga ruxsat berilgan bo'limlargina ko'rinadi. Ba'zi bandlarda qizil raqam bor: u sizni kutayotgan ishlar sonini bildiradi (yangi maqolalar, javob kutilayotgan yozishmalar va h.k.).
- **Yuqori panel** — qidiruv, til tanlagich (UZ / RU / EN), **qo'ng'iroqcha** (bildirishnomalar) va profil menyusi.
- **Bildirishnomalar** saytda darhol paydo bo'ladi va bir vaqtda elektron pochtaga ham yuboriladi.
- **Mavzu** — admin panelda yorug' yoki qorong'i ko'rinishni «Sozlamalar → Ko'rinish» bo'limida tanlash mumkin.

### 1.3. Xavfsizlik bo'yicha tavsiyalar

- Har bir xodim **ikki bosqichli himoyani (2FA)** yoqishi tavsiya etiladi (20-bo'lim).
- Parolni boshqalarga bermang; xodim ishdan ketsa, uning hisobini **bloklang** (15-bo'lim).
- Tizimdagi barcha muhim amallar **Audit log**ga yoziladi: kim, qachon, nima qildi va qaysi IP manzildan.

---

## 2. Bosh sahifa (Dashboard)

Manzil: `/admin`. Barcha xodimlarga ochiq.

| Blok                     | Nimani ko'rsatadi                                                                                                                                 |
| ------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------- |
| Statistika kartalari     | Jami maqolalar, Ko'rib chiqilayotganlar, Qabul qilinganlar, Tuzatish talab qilinganlar, Nashr etilganlar — o'tgan davrga nisbatan o'zgarish bilan |
| Maqolalar dinamikasi     | Joriy yil bo'yicha oyma-oy: jami, qabul qilingan, nashr etilgan                                                                                   |
| Maqolalar holati         | Holatlar bo'yicha taqsimot (donut)                                                                                                                |
| Yangi kelgan maqolalar   | So'nggi yuborilgan maqolalar; «Ko'rish» tugmasi maqolani «Maqolalar» bo'limida ochadi                                                             |
| To'lovlar statistikasi   | Click, Payme va qo'lda tasdiqlangan tushum; so'nggi to'lovlar                                                                                     |
| AI xizmatlari            | AI Studio'dan foydalanish ko'rsatkichlari                                                                                                         |
| So'nggi bildirishnomalar | O'qilmagan xabarlar                                                                                                                               |
| Faol foydalanuvchilar    | Yaqinda tizimga kirganlar                                                                                                                         |
| Tizim holati             | Veb-server, ma'lumotlar bazasi, to'lov tizimlari, AI server: «Ishlamoqda» / «Ishlamayapti» / «Sozlanmagan»                                        |
| Tezkor amallar           | «Yangi maqolalar», «Jurnal sonini yaratish», «To'lov qabul qilish», «Foydalanuvchi qo'shish» — faqat ruxsatingiz bor amallar ko'rinadi            |

---

## 3. Tahririyat jarayoni — umumiy sxema

Maqola quyidagi holatlardan o'tadi:

1. **Qoralama → Yuborildi.** Muallif formani to'ldirib yuboradi.
2. **To'lov kutilmoqda** (faqat pullik maqola turida). To'lov yoki to'lovdan ozod qilingach — tahririyat navbatiga.
3. **Ko'rib chiqilmoqda → Taqrizda.** Muharrir maqolani oladi va taqrizchilarga yuboradi.
4. **Muharrir qarori:**
    - **Tuzatish talab etiladi → Qayta yuborildi** — yana ko'rib chiqiladi yoki yangi taqriz raundi boshlanadi;
    - **Qabul qilindi → Nashrga tayyorlanmoqda → Nashr etildi**;
    - **Rad etildi**.
5. Muallif dastlabki bosqichlarda maqolani **qaytarib olishi** mumkin → **Qaytarib olindi**.

| Holat                  | Ma'nosi                                       | Kim o'tkazadi                     |
| ---------------------- | --------------------------------------------- | --------------------------------- |
| Qoralama               | Muallif formani to'ldirmoqda                  | Muallif                           |
| Yuborildi              | Tahririyat navbatida («Yangi»)                | Muallif / tizim                   |
| To'lov kutilmoqda      | Pullik maqola turi — nashr to'lovi kutilmoqda | Tizim                             |
| Ko'rib chiqilmoqda     | Muharrir dastlabki ko'rikda                   | Muharrir                          |
| Taqrizda               | Taqrizchilarga yuborilgan                     | Muharrir (taqrizchi tayinlaganda) |
| Tuzatish talab etiladi | Muallif tuzatilgan versiya yuborishi kerak    | Muharrir                          |
| Qayta yuborildi        | Tuzatilgan versiya keldi                      | Muallif                           |
| Qabul qilindi          | Nashrga qabul qilindi                         | Muharrir                          |
| Nashrga tayyorlanmoqda | Maketlash va korrektura                       | Maketchi                          |
| Nashr etildi           | Saytda e'lon qilingan                         | Son chop etilganda avtomatik      |
| Rad etildi             | Yakuniy rad                                   | Muharrir                          |
| Qaytarib olindi        | Muallif o'zi qaytarib olgan                   | Muallif                           |

**To'lov holatlari** (maqola bo'yicha): To'lanmagan, To'lov jarayonda, To'langan, Qaytarilgan, To'lovdan ozod.

---

## 4. Maqolalar

Manzil: `/admin/articles`. Ruxsat: **Barcha maqolalarni ko'rish**.

### 4.1. Navbatlar va qidiruv

Chap tomonda navbatlar, o'ngda tanlangan maqola kartasi joylashgan.

| Navbat             | Tarkibi                                                |
| ------------------ | ------------------------------------------------------ |
| Yangi              | «Yuborildi» holatidagi maqolalar (eng eskisi birinchi) |
| Ko'rib chiqilmoqda | Ko'rib chiqilmoqda, Taqrizda, Qayta yuborildi          |
| Tuzatishda         | Muallif tuzatish kiritmoqda                            |
| Nashrga tayyor     | Qabul qilindi, Nashrga tayyorlanmoqda                  |
| To'lov kutilmoqda  | Nashr to'lovi kutilayotganlar                          |
| Nashr etilgan      | Saytda e'lon qilinganlar                               |
| Rad / qaytarilgan  | Rad etilgan va qaytarib olinganlar                     |
| Mening vazifalarim | Siz mas'ul muharrir bo'lgan maqolalar                  |
| Barchasi           | Hammasi                                                |

Qidiruv: «Maqola nomi yoki muallif...» — sarlavha (uch tilda), muallif ismi yoki email bo'yicha.

### 4.2. Maqola kartasi

- **Asosiy ma'lumotlar**: tur, yo'nalish, to'lov holati, jurnal soni, sana.
- **Mualliflar** — «Aloqa uchun mas'ul» muallif belgilangan.
- **Annotatsiya, kalit so'zlar, adabiyotlar ro'yxati**.
- **Fayllar** — versiyalar bo'yicha (1-versiya, 2-versiya…).
- **Taqrizchilar**, **Muharrir qarorlari**, **Holat tarixi**.
- **Yozishma** — muallif bilan muloqot. Muallif sizning ismingizni emas, «Tahririyat»ni ko'radi; har bir xabar unga emailda ham boradi.
- **Muharrir izohlari** — ichki izohlar (2000 belgigacha), **muallifga ko'rinmaydi**.

### 4.3. Amallar

1. **Ko'rib chiqishga olish** — «Yuborildi» yoki «Qayta yuborildi» maqolani «Ko'rib chiqilmoqda» holatiga o'tkazadi. Mas'ul muharrir belgilanmagan bo'lsa, siz mas'ul bo'lasiz. Muallifga xabar boradi.
2. **Mas'ul muharrir** — ro'yxatdan muharrirni tanlab «Saqlash» bosiladi.
3. **Taqrizchilarni tayinlash** (faqat Ko'rib chiqilmoqda, Taqrizda yoki Qayta yuborildi holatida):
    - ro'yxatda faqat faol (to'xtatilmagan) taqrizchilar; maqola yo'nalishiga mos kelganlar **«Mos yo'nalish»** belgisi bilan birinchi chiqadi, yonida yuklamasi («faol: X · yakunlangan: Y»);
    - bir vaqtda **5 tagacha** taqrizchi; **Taqriz muddati** 3–60 kun (standart 14);
    - maqola muallifi va hammualliflarini tanlab bo'lmaydi;
    - «Taklif yuborish» bosilganda maqola «Taqrizda» holatiga o'tadi, taqrizchilarga bildirishnoma va email boradi;
    - kerak bo'lsa, taklifni **«Taklifni bekor qilish»** orqali qaytarib olish mumkin.
4. **Qaror qabul qilish** (ruxsat: «Maqola bo'yicha qaror qabul qilish»):
    - **Tuzatish talab qilish** — «Muallifga izoh» majburiy;
    - **Qabul qilish (nashrga)** — izoh ixtiyoriy;
    - **Maqolani rad etish** — sabab majburiy.

    Har bir dialogda **«Ichki izoh»** (faqat xodimlar ko'radi) bor. Muallifga «Tahririyat qarori: …» mavzusida xabar boradi; taqriz natijalari muallifga **qaror qabul qilingandan keyin** anonim ko'rinishda ochiladi.

5. **Nashr jarayonida ochish** — qabul qilingan maqola uchun.

> **Blind review.** Taqrizchi muallifning shaxsiy ma'lumotlarini ko'rmaydi (fayllar neytral nom bilan beriladi), muallif esa taqrizchini «Taqrizchi 1», «Taqrizchi 2» ko'rinishida ko'radi.

---

## 5. Taqrizlarim (taqrizchilar uchun)

Manzil: `/admin/reviews`. Ruxsat: **Taqriz yozish**.

**Tablar:** Yangi takliflar, Jarayonda, Yakunlangan, Rad etilgan / bekor, Barchasi. Har bir kartada muddat ko'rsatiladi: «N kun qoldi», «Bugun tugaydi» yoki «N kun kechikdi».

**Ish tartibi:**

1. Taklif kelganda bildirishnoma va email olasiz.
2. Taqriz sahifasini ochib **«Taklifni qabul qilish»** yoki **«Taklifni rad etish»** (sabab ixtiyoriy) bosing. Fayllar faqat taklif qabul qilingandan keyin ochiladi.
3. **Taqrizlash formasi**ni to'ldiring:
    - **Umumiy baho** (1–5 yulduz; tanlanmasa mezonlar o'rtachasi olinadi);
    - **Baholash mezonlari** (har biri 1–5): Mavzuning dolzarbligi, Ilmiy yangilik, Tadqiqot metodologiyasi, Natijalar va tahlil, Xulosa va tavsiyalar, Adabiyotlar sifati;
    - **Taqriz va izohlar** — muallifga (majburiy, 50–5000 belgi, anonim ko'rinadi) va **muharrir uchun maxfiy izoh** (ixtiyoriy);
    - **Taqriz fayli** — ixtiyoriy, PDF/DOC/DOCX, 10 MB gacha;
    - **Qaror (tavsiya)**: Qabul qilish, Kichik tuzatishlar bilan, Jiddiy qayta ishlash kerak, Rad etish.
4. **«Qoralama saqlash»** — keyinroq davom ettirish uchun; **«Taqrizni yuborish»** — yakuniy topshirish (barcha mezonlar, tavsiya va izoh to'ldirilgan bo'lishi shart). Muharrirlarga xabar boradi.

Keyingi raundda sahifada **«Muallif javobi»** va tuzatilgan fayl birinchi bo'lib ko'rsatiladi.

---

## 6. Taqrizchilar

Manzil: `/admin/reviewers`. Ruxsat: **Taqrizchi biriktirish**.

- Ro'yxat: bo'sh / band / to'xtatilgan / muddati o'tgan taqrizi bor taqrizchilar, yo'nalish bo'yicha filtr, saralash.
- **Taqrizchi qo'shish**: mavjud foydalanuvchini qidiring (kamida 2 harf), ilmiy yo'nalishlarini (20 tagacha) belgilang va «Taqrizchi qilish» bosing. Yangi odam bo'lsa, avval «Foydalanuvchilar» bo'limida hisob yarating.
- Profil sahifasida: o'rtacha baho, o'rtacha muddat, tavsiyalar taqsimoti, taqrizlar tarixi.
- **Vaqtincha to'xtatish** — taqrizchiga yangi taklif yuborilmaydi, joriy taqrizlari davom etadi. «Faollashtirish» bilan qaytariladi.
- **Taqrizchilikdan chiqarish** — faol taqrizi bo'lmasa mumkin; tarix saqlanadi.

---

## 7. Nashr jarayoni

Manzil: `/admin/production`. Ruxsat: **Maketlash va PDF tayyorlash**.

**Tablar:** Maketga olinmagan, Maketlanmoqda, Nashrga tayyor, Nashr etilgan, Barchasi.

Maqola sahifasidagi bosqichlar: Qabul qilindi → Maket → Yakuniy PDF → Muallif tasdig'i → Jurnal soni → Bosh muharrir → Nashr.

1. **Maketga olish** — maqola «Nashrga tayyorlanmoqda» holatiga o'tadi, siz maketchi sifatida belgilanasiz.
2. **Yakuniy PDF yuklash** (faqat PDF, 30 MB gacha):
    - betlar soni PDF'dan avtomatik olinadi;
    - muallifga **korrektura** uchun xabar boradi; muddat — standart **5 kun** (`JOURNAL_PROOF_DAYS`);
    - yangi PDF yuklansa, oldingi tasdiqlar bekor bo'ladi.
3. **Muallif korrekturasi** — muallif kabinetida «Tasdiqlayman» yoki «Tuzatish kerak» tugmasini bosadi. Tuzatish so'ralsa, izoh yozishmaga tushadi va siz xabar olasiz. Muddat tugashidan 24 soat oldin muallifga eslatma boradi; muddat o'tsa maketchi va bosh muharrirga xabar keladi.
4. **Muallifsiz tasdiqlash** — faqat muddat o'tgan va muallif javob bermagan bo'lsa; sabab majburiy (muallifga yuboriladi).
5. **Nashr ma'lumotlari** («Tahrirlash»): DOI (`10.xxxx/...`, takrorlanmas), UDK, plagiat foizi (chegara standart 20%), jurnal soni, boshlang'ich va oxirgi bet.
6. **Maqola rasmi** — katalog kartochkasi uchun: JPG/PNG/WEBP, 4 MB gacha, kamida 600×400 (tavsiya 1200×800).
7. **Nashr oldidan tekshiruv** ro'yxati: yakuniy PDF; format mosligi (qo'lda belgilanadi); plagiat chegarada; meta ma'lumotlar to'liq; DOI; jurnal soniga biriktirilgan; muallif roziligi.
8. **Bosh muharrir tasdig'i** — barcha bandlar bajarilgach «Tasdiqlash» (ruxsat: «Jurnal sonini chop etish»). Muallifga «Maqolangiz nashrga tayyor» xabari boradi.
9. **Maketdan qaytarish** — maqola «Qabul qilindi» holatiga qaytadi (muallifga xabar bormaydi).

> Yangi PDF, meta ma'lumot o'zgarishi, sondan chiqarish yoki muallifning tuzatish so'rovi **bosh muharrir tasdig'ini bekor qiladi** — qaytadan tasdiqlash kerak.

Maqolalar odatda son bilan birga chop etiladi (8-bo'lim). Allaqachon chop etilgan songa keyin qo'shilgan maqola uchun **«Nashr qilish»** tugmasi bor.

---

## 8. Jurnallar (sonlar)

Manzil: `/admin/issues`. Ruxsat: **Jurnal sonlarini shakllantirish**; chop etish — **Jurnal sonini chop etish**.

### 8.1. Yangi son

«Yangi son» tugmasi: **Yil** (majburiy), **Jild**, **Son №** (yil ichida takrorlanmas), **DOI**, **Maxsus nom**, **Tavsif**. Son «Qoralama» holatida yaratiladi.

### 8.2. Son sahifasi

- **Son tarkibi** — «Maqola qo'shish» (Qabul qilindi / Nashrga tayyorlanmoqda holatidagi maqolalar), sudrab yoki «Yuqoriga / Pastga» bilan tartiblash, **rukn** va sahifalar, «Sondan chiqarish».
- **Sahifalarni avtomatik hisoblash** — birinchi maqola sahifasini kiritib «Hisoblash» bosiladi; maqolalar tartibi va PDF betlari bo'yicha raqamlanadi.
- **Son fayllari**:
    - Muqova — JPG/PNG/WEBP, 5 MB gacha, kamida 300×400;
    - To'liq son PDF va Mundarija PDF — 50 MB gacha;
    - «Mundarijani chop etish» — brauzerda PDF sifatida saqlanadigan mundarija sahifasi.
- **Son PDF ini yig'ish** — muqova + mundarija + maqolalar bitta PDF'ga, xatcho'plar va jurnal sahifa raqamlari bilan (serverda `qpdf` 11+ kerak). Fon rejimida ishlaydi.
- **Chop etishga tayyorlik** — har bir maqola bo'yicha muammolar ro'yxati (bosh muharrir tasdiqlamagan, PDF yo'q, sahifalar belgilanmagan…).

### 8.3. Chop etish

**«Sonni chop etish»** — tasdiqlagach son va undagi barcha maqolalar saytda e'lon qilinadi, maqolalar «Nashr etildi» holatiga o'tadi, har bir muallifga «Maqolangiz chop etildi» xabari boradi.

Chop etilgandan keyin **«Crossref XML»** tugmasi paydo bo'ladi: DOI'si bor maqolalar uchun Crossref kabinetiga (doi.crossref.org → Submissions → Upload) yuklanadigan fayl.

---

## 9. Mualliflar

Manzil: `/admin/authors`. Ruxsat: **Barcha maqolalarni ko'rish**.

- Statistika: jami mualliflar, maqola yuborganlar, maqolasi chop etilganlar, ORCID bog'laganlar.
- Qidiruv (ism, email, tashkilot, ORCID), yo'nalish va holat filtrlari, saralash.
- Muallif sahifasi: aloqa va ilmiy ma'lumotlar, maqolalari (hammuallifligidagilar bilan), so'nggi to'lovlari. Sahifa faqat ko'rish uchun; hisobni o'zgartirish «Foydalanuvchilar» bo'limida.

---

## 10. To'lovlar

Manzil: `/admin/payments`. Ruxsat: **To'lovlarni ko'rish**.

**Tablar:** To'lov kutilmoqda, Barcha to'lovlar, Click, Payme, Qo'lda tasdiqlangan, Muvaffaqiyatsiz, Qaytarishlar.

Yuqorida: jami tushum, Click / Payme / qo'lda tasdiqlangan to'lovlar, kutilayotgan summa, oylik grafik va to'lov usullari bo'yicha taqsimot.

### 10.1. To'lov kutilayotgan maqolalar

Jadvalda: maqola, muallif, maqola turi, summa, kutish kunlari, eslatma holati va amallar.

- **Tasdiqlash** (ruxsat: «To'lovni qo'lda tasdiqlash») — bank orqali to'langan holatlar uchun:
    - Summa, To'lov sanasi (majburiy), To'lov hujjati raqami, Izoh, Kvitansiya (PDF/JPG/PNG, 5 MB gacha);
    - natija: chek raqami (masalan, PAY-00042) bilan muvaffaqiyatli to'lov, maqola tahririyat navbatiga («Yangi») o'tadi, muharrirlarga xabar boradi.
- **Ozod qilish** — maqolani to'lovdan ozod qilish; sabab majburiy (muallifga ko'rinadi).
- **Eslatish** — muallifga to'lov eslatmasi (kabinet + email). Bitta maqolaga **sutkada bir martadan ko'p emas**.
- **Hammasiga eslatma** — barcha kutilayotgan maqolalar mualliflariga (sutka ichida eslatilganlar o'tkazib yuboriladi).

**Avtomatik eslatma** — maqola yuborilganidan **3, 7 va 14 kun** o'tib har kuni soat 10:00 da (Toshkent) yuboriladi.

### 10.2. To'lov tafsilotlari va qaytarish (refund)

Ro'yxatdagi to'lovni bosing — o'ngda **«To'lov tafsilotlari»** paneli ochiladi: holat, usul, tranzaksiya raqami, to'lovchi, sana, tarkib, kvitansiya va qaytarish tarixi.

**To'lovni qaytarish** (ruxsat: «To'lovni qaytarish»; standart bo'yicha bosh muharrir va bosh administrator):

- faqat **muvaffaqiyatli** to'lov va bitta ochiq so'rov;
- nashr to'lovi faqat **rad etilgan** yoki **muallif qaytarib olgan** maqola uchun;
- **Sabab** (10–1000 belgi) va «Tasdiqlayman…» belgisi majburiy.

| Usul              | Qanday ishlaydi                                                                                                                                                                                                                                      |
| ----------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Click**         | Click Merchant API orqali darhol bekor qilinadi. Click faqat joriy oy to'lovlarini qaytaradi (o'tgan oy to'lovi — faqat oyning 1-kuni). Xato bo'lsa to'lov o'zgarmaydi, xato matni ko'rsatiladi va qayta urinish mumkin.                             |
| **Payme**         | Tizimda so'rov ochiladi («Jarayonda»). Keyin **business.payme.uz** kabinetida shu tranzaksiyani bekor qiling — Payme serverga xabar beradi va holat avtomatik «Qaytarildi» bo'ladi. Fikringiz o'zgarsa, «Qaytarishlar» tabida so'rovni bekor qiling. |
| **Qo'lda (bank)** | Pulni bank orqali qaytaring va **bank hujjati raqamini** kiriting — qaytarish darhol yakunlanadi.                                                                                                                                                    |

Qaytarilgach: to'lov va maqolaning to'lov holati «Qaytarilgan», muallifga «Nashr to'lovi qaytarildi» xabari, yozuv audit logda. **«Qaytarishlar»** tabida butun tarix (So'raldi, Jarayonda, Qaytarildi, Xato) ko'rinadi.

---

## 11. AI Studio

Manzil: `/admin/ai`. Ruxsat: **AI Studio'dan foydalanish**; sozlamalar — **AI sozlamalari** (standart bo'yicha faqat bosh administrator).

| Xizmat                       | Vazifasi                                                                                                                                                                                                                             |
| ---------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Tahrirlash (Proofreader)** | Imlo, grammatika, uslub va terminologiya bo'yicha xatolarni topadi. Har bir taklif «Qabul» / «Rad»; «Hammasini qabul qilish»; «Yonma-yon» va «Tahrirlangan matn» ko'rinishi; Word'ga yuklab olish va yangi versiya sifatida saqlash. |
| **Tarjima (Translator)**     | O'zbek ↔ rus ↔ ingliz ilmiy tarjima; natijani tahrirlash, versiyalarni saqlash, Word (.docx) sifatida yuklab olish.                                                                                                                  |
| **Tahlil (Analytics)**       | Umumiy baho, kuchli tomonlar, kamchiliklar va tavsiyalar (matnning birinchi 12 000 belgisi).                                                                                                                                         |

- So'rovlar **navbat orqali** bajariladi — sahifani yopish mumkin, natija «Tarix» bo'limida saqlanadi.
- Natija hech qachon avtomatik qo'llanmaydi — har doim foydalanuvchi tasdiqlaydi.
- Bitta so'rovda standart **30 000 belgigacha** matn; daqiqasiga 10 ta so'rov.

**Sozlamalar** tabi (faqat AI sozlamalari ruxsati bilan):

- Xizmat yoqilgan/o'chirilgan; **API kaliti** (Anthropic, shifrlangan holda saqlanadi); model identifikatori;
- oylik token limitlari — muallif uchun (standart 50 000) va xodim uchun (standart 200 000); 0 — cheklanmagan;
- **Ko'rsatma shablonlari (prompt)** — har bir xizmat uchun tizim ko'rsatmasi, temperatura, maksimal token; «Standartga qaytarish»;
- **Shaxsiy limit** — alohida foydalanuvchi uchun limit.

---

## 12. Xabarlar

Manzil: `/admin/messages`. Ruxsat: **Muallif bilan yozishish**.

- **Yozishmalar** — barcha maqolalar bo'yicha muloqot; filtrlar: Hammasi, Javob kutmoqda, Menga biriktirilgan. Javob yozish va maqolani ochish shu yerdan.
- **Ommaviy xabar** (qo'shimcha ruxsat: «Foydalanuvchilarni boshqarish»):
    - **Kimga**: barcha mualliflar, maqola yuborgan mualliflar, maqolasi nashr etilganlar, taqrizchilar, tahririyat xodimlari yoki barcha foydalanuvchilar;
    - Mavzu va Matn; **«Email ham yuborish»** belgisi;
    - yuborilgan xabarni qaytarib bo'lmaydi; tarix «Yuborilganlar»da.
- **Bildirishnomalar** — barcha bildirishnomalar va «Hammasini o'qilgan deb belgilash».

### 12.1. Obuna (sayt obunachilari)

Manzil: `/admin/newsletter`. Ruxsat: **Kontentni boshqarish**.

Saytning pastidagi «Yangiliklardan xabardor bo'ling» formasi ikki bosqichli (double opt-in):
manzil kiritilgach, unga tasdiqlash xati boradi; havola bosilgandagina obunachi **faol** bo'ladi.
30 kun ichida tasdiqlanmagan manzillar avtomatik o'chiriladi. Har bir xatda «Obunadan chiqish»
havolasi bor, pochta dasturlaridagi «Отписаться / Unsubscribe» tugmasi ham ishlaydi.

- **Xat yuborish** tabi:
    - **Yangi son haqida tayyor matn** — so'nggi chop etilgan sonlardan birini bossangiz, mavzu, matn (mundarija bilan) va «Sonni o'qish» tugmasi o'zi to'ladi; tahrirlab yuborish mumkin. ✓ belgisi — bu son haqida xat allaqachon ketgan;
    - **Kimga**: barcha faol obunachilar yoki faqat bitta til (o'zbek / rus / ingliz — obuna bo'lgandagi sayt tili);
    - Mavzu, Matn (xatboshilar bo'sh qator bilan), ixtiyoriy **tugma** (matn + https havola);
    - yuborilgan xatni qaytarib bo'lmaydi; holati va soni «Yuborilganlar»da.
- **Yangi son haqida avtomatik xat** (o'ng tomondagi kalit, standart — yoqiq): son chop etilganda barcha faol obunachilarga avtomatik xat ketadi (har bir songa bir marta).
- **Obunachilar** tabi: email bo'yicha qidiruv, holat filtri (Faol / Tasdiq kutilmoqda / Chiqqan), **CSV yuklab olish** (Excel'da ochiladi), o'chirish. Obunadan chiqqanlarni o'chirmang — aks holda ular xatlarni qayta olishi mumkin.

---

## 13. Statistika va hisobotlar

Manzil: `/admin/reports`. Ruxsat: **Hisobotlarni ko'rish**.

- **Davr**: oxirgi 7 / 30 kun, joriy oy, 3 / 6 / 12 oy, joriy yil, o'tgan yil yoki ixtiyoriy oraliq; **fan yo'nalishi** filtri.
- **Umumiy ko'rsatkichlar**: yuborilgan, qabul qilingan, rad etilgan maqolalar, faol mualliflar, o'rtacha taqriz vaqti, ko'rishlar; maqolalar va daromadlar dinamikasi, yo'nalishlar, mamlakatlar, tashkilotlar, top mualliflar.
- **Taqrizchilar samaradorligi**: takliflar, topshirilgan, rad etilgan, muddatida / kechikkan, o'rtacha kun.
- **Eksport**: maqolalar, to'lovlar, taqrizchilar va mualliflar bo'yicha **Excel (CSV)**; **Umumiy statistik hisobot** — brauzerda PDF sifatida saqlanadigan A4 sahifa.

---

## 14. Sozlamalar (sayt kontenti)

Manzil: `/admin/settings`. Ruxsat: **Sayt kontentini boshqarish**. Barcha matnlar uch tilda (o'zbekcha majburiy, rus va ingliz ixtiyoriy — bo'lmasa o'zbekchasi ko'rsatiladi).

| Tab                           | Nimani boshqaradi                                                                                                                                                                                                                                                                                                          |
| ----------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Yo'nalishlar**              | Ilmiy yo'nalishlar (rukn): nom, shifr, ota yo'nalish, faol, tartib. Maqolasi bor yo'nalishni o'chirib bo'lmaydi — nofaol qiling.                                                                                                                                                                                           |
| **Maqola turlari va narxlar** | Faqat «Narxlarni boshqarish» ruxsati bilan. Nom, narx (0 — bepul, to'lov bosqichi o'tkazib yuboriladi), ko'rib chiqish muddati (kun). Narx o'zgarishi audit logga yoziladi.                                                                                                                                                |
| **Sahifalar**                 | «Jurnal haqida», «Mualliflar uchun yo'riqnoma», «Aloqa» sahifalari matni: sarlavha, qisqa tavsif va 20 tagacha bo'lim. Matnda bo'sh qator — yangi xatboshi, «- » — ro'yxat, «1. » — raqamli ro'yxat, `**qalin**`. `{journal}`, `{email}`, `{plagiarism_max}` avtomatik almashtiriladi. «Standart matnga qaytarish» mavjud. |
| **Tahririyat kengashi**       | A'zolar: rol (bosh muharrir, o'rinbosar, mas'ul kotib, a'zo), F.I.Sh., ilmiy daraja, lavozim, tashkilot, davlat, ORCID, portret (4 MB gacha). «Jurnal haqida» sahifasida rollar bo'yicha chiqadi.                                                                                                                          |
| **Fayllar**                   | Mualliflar uchun yuklab olinadigan fayllar: maqola shabloni, yo'riqnoma, ariza va shartnoma shakllari (DOC, DOCX, DOTX, PDF, RTF, ODT, XLS, XLSX, ZIP — 20 MB gacha). **Birinchi faol «Maqola shabloni»** saytdagi va kabinetdagi «Shablonni yuklab olish» tugmalariga ulanadi. Yuklab olishlar soni ko'rinadi.            |
| **Bannerlar**                 | Bosh sahifa slayderi: rasm (kamida 1200×400), havola, faol, ko'rsatish muddati. Faol banner bo'lmasa standart slaydlar chiqadi.                                                                                                                                                                                            |
| **Yangiliklar va e'lonlar**   | Tur, rasm, matn, chop etish va qadash; kelajakdagi sana — rejalashtirilgan nashr.                                                                                                                                                                                                                                          |
| **Tadbirlar**                 | Boshlanish/tugash vaqti, ro'yxatdan o'tish havolasi, rasm.                                                                                                                                                                                                                                                                 |
| **Tavsiya etilgan kitoblar**  | Bosh sahifaning o'ng ustuni: muallif, yil, havola, muqova.                                                                                                                                                                                                                                                                 |
| **Hamkorlar**                 | Hamkor tashkilotlar va indekslash bazalari logolari.                                                                                                                                                                                                                                                                       |

---

## 15. Foydalanuvchilar

Manzil: `/admin/users`. Ruxsat: **Foydalanuvchilarni boshqarish**.

- Ro'yxat: qidiruv (ism, email, telefon, tashkilot), rol va holat filtrlari (faol, bloklangan, email tasdiqlanmagan, o'chirilgan, hech kirmagan).
- **Foydalanuvchi qo'shish** — shaxsiy va ilmiy ma'lumotlar, parol («Parol yaratish» tugmasi), **rollar** (kamida bitta), «Email tasdiqlangan» belgisi. Bosh administrator rolini faqat bosh administrator bera oladi.
- Profil sahifasida: **Tahrirlash**, **Bloklash** (sabab bilan; bloklangan foydalanuvchi tizimga kira olmaydi) / Blokdan chiqarish, **O'chirish** (qayta tiklash mumkin), **Xavfsizlik**: yangi parol o'rnatish, parolni tiklash havolasini yuborish, 2FA holati; faoliyat: kirishlar, AI so'rovlari, maqolalar, to'lovlar.

> Production serverda parol kamida **12 belgi**, katta-kichik harf, raqam va belgi aralash bo'lishi va ma'lum sizib chiqqan parollar bazasida bo'lmasligi kerak.

---

## 16. Rollar va ruxsatlar

Manzil: `/admin/roles`. Ruxsat: **Rollar va ruxsatlarni boshqarish**.

Matritsa: ustunlarda rollar, qatorlarda ruxsatlar. Har bir rol uchun «Saqlash», hammasi uchun «Hammasini saqlash», «Standart ruxsatlarga qaytarish». Bosh administrator ruxsatlari o'zgartirilmaydi.

### 16.1. Standart ruxsatlar matritsasi

| Ruxsat                             | Bosh admin | Bosh muharrir | Muharrir | Taqrizchi | Maketchi | Kontent-menejer |
| ---------------------------------- | :--------: | :-----------: | :------: | :-------: | :------: | :-------------: |
| Admin panelga kirish               |     ✔      |       ✔       |    ✔     |     ✔     |    ✔     |        ✔        |
| AI Studio'dan foydalanish          |     ✔      |       ✔       |    ✔     |     ✔     |    ✔     |        ✔        |
| Barcha maqolalarni ko'rish         |     ✔      |       ✔       |    ✔     |           |          |                 |
| Taqrizchi biriktirish              |     ✔      |       ✔       |    ✔     |           |          |                 |
| Maqola bo'yicha qaror qabul qilish |     ✔      |       ✔       |    ✔     |           |          |                 |
| Muallif bilan yozishish            |     ✔      |       ✔       |    ✔     |           |          |                 |
| Taqriz yozish                      |     ✔      |               |          |     ✔     |          |                 |
| Jurnal sonlarini shakllantirish    |     ✔      |       ✔       |          |           |    ✔     |                 |
| Jurnal sonini chop etish           |     ✔      |       ✔       |          |           |          |                 |
| Maketlash va PDF tayyorlash        |     ✔      |       ✔       |          |           |    ✔     |                 |
| Sayt kontentini boshqarish         |     ✔      |               |          |           |          |        ✔        |
| To'lovlarni ko'rish                |     ✔      |       ✔       |    ✔     |           |          |                 |
| To'lovni qo'lda tasdiqlash         |     ✔      |               |          |           |          |                 |
| To'lovni qaytarish                 |     ✔      |       ✔       |          |           |          |                 |
| Narxlarni boshqarish               |     ✔      |               |          |           |          |                 |
| Hisobotlarni ko'rish               |     ✔      |       ✔       |          |           |          |                 |
| Foydalanuvchilarni boshqarish      |     ✔      |               |          |           |          |                 |
| Rollar va ruxsatlarni boshqarish   |     ✔      |               |          |           |          |                 |
| Audit logni ko'rish                |     ✔      |               |          |           |          |                 |
| Tizim sozlamalari                  |     ✔      |               |          |           |          |                 |
| AI sozlamalari                     |     ✔      |               |          |           |          |                 |

Muallif roli faqat «AI Studio'dan foydalanish» ruxsatiga ega bo'lishi mumkin.

---

## 17. Audit log

Manzil: `/admin/audit-log`. Ruxsat: **Audit logni ko'rish**.

- Qisqa statistika: jami yozuvlar, bugungi amallar, so'nggi 24 soatdagi faol foydalanuvchilar va muvaffaqiyatsiz kirishlar.
- Filtrlar: qidiruv, bo'lim (kirish, maqolalar, taqriz, nashr, sonlar, to'lovlar, AI, kontent, foydalanuvchilar, hisobotlar, tizim), amal, ahamiyati (Ma'lumot / Muhim / Xavfli), foydalanuvchi, sana oralig'i.
- «Tafsilotlar» — o'zgarishdan oldingi va keyingi qiymatlar.
- **Excel (CSV) eksport** — 20 000 qatorgacha.
- Yozuvlar standart bo'yicha **365 kun** saqlanadi (`AUDIT_RETENTION_DAYS`), keyin avtomatik tozalanadi.

---

## 18. Zaxira nusxa

Manzil: `/admin/backups`. Ruxsat: **Tizim sozlamalari**.

- **Zaxira nusxa yaratish** — turini tanlang: «To'liq (baza + fayllar)», «Ma'lumotlar bazasi» yoki «Yuklangan fayllar». Jarayon fon rejimida (Navbatda → Yaratilmoqda → Tayyor / Xato).
- **Avtomatik zaxira** — yoqish, vaqt (standart 03:30), tur va saqlanadigan nusxalar soni (standart 14). Server cron'i ishlashi shart.
- **Saqlangan arxivlar** — «Yuklab olish» va «O'chirish».
- **Tiklash** xavfsizlik uchun faqat serverda bajariladi — buyruqlar sahifada va «Serverga joylashtirish» hujjatida keltirilgan.

> Arxivda maxfiy ma'lumotlar (foydalanuvchilar, to'lovlar) bor — uni xavfsiz joyda saqlang va boshqalarga bermang.

---

## 19. Tizim sozlamalari

Manzil: `/admin/system-settings`. Ruxsat: **Tizim sozlamalari**. O'zgarishlar darhol kuchga kiradi va audit logga yoziladi.

| Tab              | Maydonlar                                                                                                                                                                                                                                                                                                               |
| ---------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Jurnal**       | Jurnal nomi, qo'shimcha nom, qisqa tavsif, ISSN (bosma), e-ISSN, DOI prefiksi, davriylik, plagiat chegarasi (%). Indekslash kartasi: OAI-PMH manzili (`/oai`) — «Nusxalash» va «Tekshirish».                                                                                                                            |
| **Aloqa**        | Email, telefon, manzil, Telegram / Facebook / Instagram / YouTube / LinkedIn havolalari (bo'shlari saytda ko'rinmaydi).                                                                                                                                                                                                 |
| **Rekvizitlar**  | Bank orqali to'lov uchun: qabul qiluvchi tashkilot, bank, hisob raqami, MFO, STIR. Muallif kabinetida «To'lov kutilmoqda» holatida ko'rsatiladi.                                                                                                                                                                        |
| **Pochta**       | Yuborish usuli (SMTP yoki faqat logga yozish), server, port, shifrlash, login, parol (shifrlangan saqlanadi), yuboruvchi. Tayyor sozlamalar: Gmail, Yandex, Mail.ru, Outlook. **«Test xat yuborish»** — sozlamalarni saqlagandan keyin tekshiring.                                                                      |
| **Tizim holati** | **Ishga tushirishga tayyorlik** — sozlamalar, cron va navbat ishchisi tirikligi, kontent, to'lov usullari, xavfsizlik (2FA, demo hisoblar) va zaxira bo'yicha ro'yxat (serverda `php artisan app:launch-check` bilan bir xil). Pastda: PHP va Laravel versiyalari, baza, navbat, pochta, qpdf, Click, Payme, AI Studio. |

---

## 20. Shaxsiy sozlamalar va xavfsizlik

Manzil: `/settings/profile` va `/settings/security` (profil menyusi orqali).

- **Profil** — shaxsiy va ilmiy ma'lumotlar, profil rasmi. Emailni o'zgartirish joriy parolni va yangi manzilni tasdiqlashni talab qiladi.
- **Parolni almashtirish** — joriy parol, yangi parol va uning takrori.
- **Ikki bosqichli himoya (2FA)**:
    1. «Yoqish» → parolni tasdiqlang;
    2. QR kodni **Google Authenticator** yoki **Microsoft Authenticator** ilovasida skanerlang;
    3. ilovadagi 6 xonali kodni kiriting → «Tasdiqlash»;
    4. **Zaxira kodlar**ni xavfsiz joyda saqlang — telefon yo'qolsa ular bilan kirasiz (har biri bir marta ishlaydi).
- **Bog'langan akkauntlar** — Google va ORCID orqali kirishni bog'lash yoki uzish.
- **Ko'rinish** — yorug' / qorong'i mavzu (faqat xodimlar uchun).

---

## 21. Tez-tez uchraydigan vaziyatlar

| Vaziyat                                                                 | Nima qilish kerak                                                                                                                                                                   |
| ----------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Muallif bank orqali to'ladi, lekin maqola «To'lov kutilmoqda»da turibdi | «To'lovlar → To'lov kutilmoqda» → «Tasdiqlash», kvitansiyani biriktiring.                                                                                                           |
| Taqrizchi javob bermayapti                                              | «Maqolalar» → maqola → «Taqrizchilar» paneli → taklifni bekor qilib, boshqa taqrizchi tayinlang. Taqrizchi uzoq muddat band bo'lsa — «Taqrizchilar» bo'limida vaqtincha to'xtating. |
| Muallif korrekturaga javob bermadi                                      | Muddat o'tgach «Nashr jarayoni» → «Muallifsiz tasdiqlash» (sababini yozing).                                                                                                        |
| Son PDF'i yig'ilmayapti                                                 | «Son PDF ini yig'ish» oynasidagi tekshiruvlarga qarang: barcha maqolalarda yakuniy PDF bormi, serverda qpdf o'rnatilganmi («Tizim holati»).                                         |
| Xatlar kelmayapti                                                       | «Tizim sozlamalari → Pochta → Test xat yuborish». Xat ketmasa — server administratoriga navbat ishchisi (worker) holatini tekshirishni ayting.                                      |
| Rad etilgan maqola to'lovini qaytarish kerak                            | «To'lovlar» → to'lovni oching → «To'lovni qaytarish» (10.2-bo'lim).                                                                                                                 |
| Xodim ishdan ketdi                                                      | «Foydalanuvchilar» → profil → «Bloklash». Taqrizchi bo'lsa — avval faol taqrizlarini boshqa taqrizchiga o'tkazing.                                                                  |
| Shablon faylini yangilash kerak                                         | «Sozlamalar → Fayllar» → shablonni tahrirlab yangi faylni yuklang — havolalar avtomatik yangilanadi.                                                                                |
