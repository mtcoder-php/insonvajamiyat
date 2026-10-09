{{--
    Statik xato sahifasi (Vite/Inertia'siz): texnik rejim (503) va Inertia sahifasini
    chizib bo'lmagan holatlar uchun. Barcha uslublar shu faylning ichida.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#001e3c">
    @hasSection('refresh')
        <meta http-equiv="refresh" content="@yield('refresh')">
    @endif
    <title>@yield('title') — Inson va Jamiyat</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;
            padding:32px 20px;background:#f6f8fb;color:#001e3c;
            font-family:system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;text-align:center}
        .card{max-width:560px;width:100%;background:#fff;border:1px solid #e3e9f1;border-radius:20px;
            padding:44px 32px;box-shadow:0 30px 60px -40px rgba(0,30,60,.55)}
        .brand{font-family:Georgia,"Times New Roman",serif;font-weight:700;letter-spacing:.08em;font-size:14px;
            text-transform:uppercase;color:#001e3c;margin-bottom:28px}
        .brand span{display:block;font-family:inherit;font-style:italic;letter-spacing:0;text-transform:none;
            font-weight:400;font-size:12px;color:#5b6b80;margin-top:2px}
        .code{font-family:Georgia,"Times New Roman",serif;font-size:96px;line-height:1;font-weight:700;
            background:linear-gradient(135deg,#001e3c,#006cf6 55%,#c49a45);-webkit-background-clip:text;
            background-clip:text;color:transparent}
        h1{font-family:Georgia,"Times New Roman",serif;font-size:24px;margin:18px 0 10px}
        p{color:#4a5b70;line-height:1.6;font-size:15px}
        .muted{margin-top:6px;font-size:13px;color:#7a8799}
        .bar{height:4px;width:72px;border-radius:4px;background:#c49a45;margin:22px auto 0}
        a.btn{display:inline-block;margin-top:26px;padding:12px 22px;border-radius:10px;background:#001e3c;
            color:#fff;text-decoration:none;font-weight:600;font-size:14px;transition:transform .2s,background .2s}
        a.btn:hover{transform:translateY(-2px);background:#0b3a6e}
        footer{margin-top:24px;font-size:12px;color:#93a0b1}
    </style>
</head>
<body>
    <div class="card">
        <div class="brand">Inson va Jamiyat<span>Scientific Journal</span></div>
        <div class="code">@yield('code')</div>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        @hasSection('note')
            <p class="muted">@yield('note')</p>
        @endif
        <div class="bar"></div>
        @hasSection('action')
            @yield('action')
        @endif
    </div>
    <footer>© {{ date('Y') }} «Inson va Jamiyat» ilmiy jurnali</footer>
</body>
</html>
