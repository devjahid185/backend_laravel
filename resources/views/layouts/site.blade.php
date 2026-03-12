<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ $title ?? 'ভোলাবাসী | জেলার সব সেবা এক জায়গায়' }}</title>
  <meta name="description" content="{{ $description ?? 'ভোলাবাসী আপনার জেলার সকল সেবা, ব্যবসা, চাকরি, স্বাস্থ্য, শিক্ষা, বাজার, মার্কেটপ্লেস ও জরুরি তথ্য এক প্ল্যাটফর্মে দেয়।' }}" />
  <meta name="keywords" content="ভোলা, সেবা, মার্কেটপ্লেস, চাকরি, রক্তদাতা, হাসপাতাল, শিক্ষা, হোটেল, রেস্তোরাঁ, কমিউনিটি" />
  <meta name="robots" content="index, follow" />
  <link rel="canonical" href="{{ $canonical ?? url()->current() }}" />

  <meta property="og:type" content="website" />
  <meta property="og:title" content="{{ $title ?? 'ভোলাবাসী | জেলার সব সেবা এক জায়গায়' }}" />
  <meta property="og:description" content="{{ $description ?? 'ভোলাবাসী আপনার জেলার সকল সেবা, ব্যবসা, চাকরি, স্বাস্থ্য, শিক্ষা, বাজার, মার্কেটপ্লেস ও জরুরি তথ্য এক প্ল্যাটফর্মে দেয়।' }}" />
  <meta property="og:url" content="{{ $canonical ?? url()->current() }}" />
  <meta property="og:site_name" content="ভোলাবাসী" />
  <meta property="og:image" content="{{ $ogImage ?? url('/logo_bholavashi_landscape_size.png') }}" />

  <meta name="twitter:card" content="summary" />
  <meta name="twitter:title" content="{{ $title ?? 'ভোলাবাসী | জেলার সব সেবা এক জায়গায়' }}" />
  <meta name="twitter:description" content="{{ $description ?? 'ভোলাবাসী আপনার জেলার সকল সেবা, ব্যবসা, চাকরি, স্বাস্থ্য, শিক্ষা, বাজার, মার্কেটপ্লেস ও জরুরি তথ্য এক প্ল্যাটফর্মে দেয়।' }}" />
  <meta name="twitter:image" content="{{ $ogImage ?? url('/logo_bholavashi_landscape_size.png') }}" />

  <link rel="icon" type="image/png" sizes="32x32" href="/favicon_bholavashi.png" />
  <link rel="icon" type="image/png" sizes="16x16" href="/favicon_bholavashi.png" />
  <link rel="apple-touch-icon" href="/logo_bholavashi_squre.png" />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            ink: '#0f172a',
            leaf: '#0f766e',
            sun: '#f59e0b',
            mist: '#f1f5f9',
            line: '#e2e8f0'
          },
          fontFamily: {
            display: ['Hind Siliguri', 'ui-sans-serif', 'system-ui'],
            bangla: ['Hind Siliguri', 'ui-sans-serif', 'system-ui'],
          }
        }
      }
    };
  </script>
  <style>
    html, body {
      font-family: 'Hind Siliguri', ui-sans-serif, system-ui !important;
      color: #0f172a;
      background-color: #f8fafc;
    }
    h1, h2, h3, h4, h5, h6, p, span, a, li, button, input, textarea, label {
      font-family: 'Hind Siliguri', ui-sans-serif, system-ui !important;
    }
  </style>
  @stack('head')
</head>
<body class="min-h-screen">
  <header class="sticky top-0 z-30 bg-white border-b border-line">
    <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
      <a href="/" class="flex items-center gap-3">
        <div class="h-10 w-10 rounded-xl bg-leaf/10 flex items-center justify-center overflow-hidden">
          <img src="/logo_bholavashi_squre.png" alt="ভোলাবাসী লোগো" class="h-8 w-8 object-contain" />
        </div>
        <div>
          <p class="font-display text-lg font-semibold">ভোলাবাসী</p>
          <p class="text-xs text-slate-500">সব সেবা এক জায়গায়</p>
        </div>
      </a>
      <nav class="hidden md:flex items-center gap-4 text-sm text-slate-600">
        <a class="hover:text-ink" href="/services">সার্ভিস</a>
        <a class="hover:text-ink" href="/market">মার্কেট</a>
        <a class="hover:text-ink" href="/jobs">চাকরি</a>
        <a class="hover:text-ink" href="/doctors">ডাক্তার</a>
        <a class="hover:text-ink" href="/hospitals">হাসপাতাল</a>
        <a class="hover:text-ink" href="/hotels">হোটেল</a>
        <a class="hover:text-ink" href="/restaurants">রেস্তোরাঁ</a>
        <a class="hover:text-ink" href="/property">প্রপার্টি</a>
        <a class="hover:text-ink" href="/education">শিক্ষা</a>
        <a class="hover:text-ink" href="/updates">আপডেটস</a>
      </nav>
      <div class="flex items-center gap-3">
        <button class="hidden sm:inline-flex items-center gap-2 rounded-full border border-line px-4 py-2 text-sm text-ink hover:border-slate-300">লগইন</button>
        <button class="inline-flex items-center gap-2 rounded-full bg-leaf px-4 py-2 text-sm text-white shadow-sm hover:bg-teal-700">ডাউনলোড অ্যাপ</button>
      </div>
    </div>
    <div class="md:hidden px-4 pb-3">
      <details class="group rounded-2xl border border-line bg-white px-4 py-3">
        <summary class="flex cursor-pointer list-none items-center justify-between text-sm text-slate-700">
          মেনু দেখুন
          <span class="text-slate-400 group-open:rotate-180">⌄</span>
        </summary>
        <div class="mt-3 grid grid-cols-2 gap-2 text-sm text-slate-600">
          <a class="rounded-xl border border-line px-3 py-2" href="/services">সার্ভিস</a>
          <a class="rounded-xl border border-line px-3 py-2" href="/market">মার্কেট</a>
          <a class="rounded-xl border border-line px-3 py-2" href="/jobs">চাকরি</a>
          <a class="rounded-xl border border-line px-3 py-2" href="/doctors">ডাক্তার</a>
          <a class="rounded-xl border border-line px-3 py-2" href="/hospitals">হাসপাতাল</a>
          <a class="rounded-xl border border-line px-3 py-2" href="/hotels">হোটেল</a>
          <a class="rounded-xl border border-line px-3 py-2" href="/restaurants">রেস্তোরাঁ</a>
          <a class="rounded-xl border border-line px-3 py-2" href="/property">প্রপার্টি</a>
          <a class="rounded-xl border border-line px-3 py-2" href="/education">শিক্ষা</a>
          <a class="rounded-xl border border-line px-3 py-2" href="/updates">আপডেটস</a>
          <a class="rounded-xl border border-line px-3 py-2" href="/about">About</a>
          <a class="rounded-xl border border-line px-3 py-2" href="/support">Support</a>
        </div>
      </details>
    </div>
  </header>

  <main class="max-w-6xl mx-auto px-4">
    @yield('content')
  </main>

  <footer class="mt-12 border-t border-line bg-white">
    <div class="max-w-6xl mx-auto px-4 py-6 grid gap-6 md:grid-cols-[1.4fr_1fr_1fr] text-sm text-slate-600">
      <div>
        <img src="/logo_bholavashi_landscape_size.png" alt="ভোলাবাসী লোগো" class="h-10 w-auto" />
        <p class="mt-3">ভোলাবাসী আপনার জেলার সব সেবা, তথ্য ও কমিউনিটি এক জায়গায়।</p>
      </div>
      <div>
        <p class="font-semibold text-ink">লিংকসমূহ</p>
        <ul class="mt-2 space-y-1">
          <li><a href="/services" class="hover:text-ink">সার্ভিস</a></li>
          <li><a href="/market" class="hover:text-ink">মার্কেটপ্লেস</a></li>
          <li><a href="/jobs" class="hover:text-ink">চাকরি</a></li>
          <li><a href="/education" class="hover:text-ink">শিক্ষা</a></li>
          <li><a href="/about" class="hover:text-ink">About</a></li>
        </ul>
      </div>
      <div>
        <p class="font-semibold text-ink">নীতিমালা</p>
        <ul class="mt-2 space-y-1">
          <li><a href="/privacy" class="hover:text-ink">Privacy Policy</a></li>
          <li><a href="/terms" class="hover:text-ink">Terms</a></li>
          <li><a href="/support" class="hover:text-ink">Support</a></li>
        </ul>
      </div>
    </div>
    <div class="border-t border-line">
      <div class="max-w-6xl mx-auto px-4 py-4 text-xs text-slate-500 flex flex-col md:flex-row gap-2 md:items-center md:justify-between">
        <p>© 2026 ভোলাবাসী. সর্বস্বত্ব সংরক্ষিত।</p>
        <p>সর্বশেষ আপডেট: {{ date('Y-m-d') }}</p>
      </div>
    </div>
  </footer>
</body>
</html>
