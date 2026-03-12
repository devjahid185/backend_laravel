@extends('layouts.site')

@php
  $title = 'ভোলাবাসী | জেলার সব সেবা এক জায়গায়';
  $description = 'ভোলাবাসী আপনার জেলার সকল সেবা, ব্যবসা, চাকরি, স্বাস্থ্য, শিক্ষা, বাজার, মার্কেটপ্লেস ও জরুরি তথ্য এক প্ল্যাটফর্মে দেয়।';
  $canonical = url('/');
@endphp

@push('head')
  <script type="application/ld+json">
  {
    "@@context": "https://schema.org",
    "@@type": "WebSite",
    "name": "ভোলাবাসী",
    "url": "{{ url('/') }}",
    "description": "ভোলাবাসী আপনার জেলার সকল সেবা, ব্যবসা, চাকরি, স্বাস্থ্য, শিক্ষা, বাজার, মার্কেটপ্লেস ও জরুরি তথ্য এক প্ল্যাটফর্মে দেয়।",
    "inLanguage": "bn"
  }
  </script>
  <script type="application/ld+json">
  {
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
      {"@@type": "Question", "name": "ভোলাবাসী কী?", "acceptedAnswer": {"@@type": "Answer", "text": "এটি জেলার সব সেবা, তথ্য ও কমিউনিটিকে এক প্ল্যাটফর্মে এনে দেয়।"}},
      {"@@type": "Question", "name": "ওয়েবে কি অ্যাপের সব ফিচার থাকবে?", "acceptedAnswer": {"@@type": "Answer", "text": "হ্যাঁ, প্রধান ফিচারগুলো ওয়েবেও দেখা যাবে।"}},
      {"@@type": "Question", "name": "তথ্য কতবার আপডেট হয়?", "acceptedAnswer": {"@@type": "Answer", "text": "ভেরিফায়েড টিম নিয়মিত তথ্য আপডেট করে।"}}
    ]
  }
  </script>
@endpush

@section('content')
  <section class="pt-12 pb-10 grid gap-8 lg:grid-cols-[1.1fr_0.9fr] items-center">
    <div class="space-y-6">
      <div class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-1 text-xs text-slate-600 border border-line">
        <span class="h-2 w-2 rounded-full bg-sun"></span>
        আপনার জেলার দৈনন্দিন সব সেবা
      </div>
      <h1 class="font-display text-4xl md:text-5xl font-bold leading-tight">জেলার সকল প্রয়োজনীয় সেবা এখন এক প্ল্যাটফর্মে</h1>
      <p class="text-base md:text-lg text-slate-600">
        শ্রমিক খোঁজা, ব্যবসা প্রচার, জরুরি নম্বর, চাকরি, রক্তদাতা, শিক্ষা প্রতিষ্ঠান, হাসপাতাল, হোটেল, রেস্তোরাঁ এবং আরও অনেক কিছু—সবকিছু সহজে খুঁজুন, যুক্ত হোন, সেবা নিন।
      </p>
      <div class="flex flex-col sm:flex-row gap-3">
        <div class="flex-1 rounded-2xl bg-white border border-line px-4 py-3 flex items-center gap-3">
          <span class="text-slate-400">🔍</span>
          <input class="w-full text-sm outline-none" placeholder="সেবা, প্রতিষ্ঠান, লোকেশন খুঁজুন" />
        </div>
        <button class="rounded-2xl bg-ink px-6 py-3 text-white text-sm">সার্চ করুন</button>
      </div>
      <div class="flex flex-wrap gap-4 text-sm">
        <div class="bg-white border border-line rounded-2xl px-4 py-2">
          <p class="font-semibold">২০০+ সেবা</p>
          <p class="text-xs text-slate-500">এক প্ল্যাটফর্মে</p>
        </div>
        <div class="bg-white border border-line rounded-2xl px-4 py-2">
          <p class="font-semibold">২৪/৭ আপডেট</p>
          <p class="text-xs text-slate-500">লাইভ তথ্য</p>
        </div>
        <div class="bg-white border border-line rounded-2xl px-4 py-2">
          <p class="font-semibold">ভেরিফায়েড প্রোফাইল</p>
          <p class="text-xs text-slate-500">নির্ভরযোগ্য সেবা</p>
        </div>
      </div>
    </div>
    <div class="space-y-4">
      <div class="bg-white border border-line rounded-3xl p-6">
        <p class="text-xs uppercase tracking-wide text-slate-400">লাইভ হাইলাইট</p>
        <h3 class="font-display text-xl font-semibold mt-2">আজকের জরুরি আপডেট</h3>
        <ul class="mt-4 space-y-3 text-sm text-slate-600">
          <li class="flex items-center gap-3"><span class="h-2 w-2 rounded-full bg-leaf"></span> রক্তদাতা তালিকা আপডেট হয়েছে</li>
          <li class="flex items-center gap-3"><span class="h-2 w-2 rounded-full bg-sun"></span> নতুন চাকরি পোস্ট: ৩৫টি</li>
          <li class="flex items-center gap-3"><span class="h-2 w-2 rounded-full bg-ink"></span> হাসপাতাল তথ্য যাচাই সম্পন্ন</li>
        </ul>
      </div>
      <div class="bg-ink rounded-3xl p-6 text-white">
        <h3 class="font-display text-lg font-semibold">কমিউনিটি আপডেট</h3>
        <p class="text-sm text-white/80 mt-2">আপনার এলাকার নতুন ঘোষণা, ইভেন্ট, বাজারদর এবং গুরুত্বপূর্ণ নোটিফিকেশন এখানে পাবেন।</p>
        <button class="mt-4 rounded-full bg-white text-ink px-4 py-2 text-sm">কমিউনিটি দেখুন</button>
      </div>
    </div>
  </section>

  <section id="features" class="py-10">
    <div class="flex items-center justify-between mb-6">
      <h2 class="font-display text-2xl font-semibold">মেইন ফিচার</h2>
      <button class="text-sm text-leaf font-medium">সব দেখুন</button>
    </div>
    <div class="grid gap-4 md:grid-cols-3">
      <div class="bg-white border border-line rounded-2xl p-5">
        <h3 class="font-semibold">স্মার্ট সার্চ ও ফিল্টার</h3>
        <p class="text-sm text-slate-600 mt-2">ক্যাটাগরি, লোকেশন, রেটিং, প্রাইস, টাইপ অনুযায়ী দ্রুত খুঁজুন।</p>
      </div>
      <div class="bg-white border border-line rounded-2xl p-5">
        <h3 class="font-semibold">রিভিউ ও রেটিং</h3>
        <p class="text-sm text-slate-600 mt-2">ব্যবহারকারীর অভিজ্ঞতা দেখে সিদ্ধান্ত নিন এবং নিজে রেটিং দিন।</p>
      </div>
      <div class="bg-white border border-line rounded-2xl p-5">
        <h3 class="font-semibold">সেইফ কানেক্ট</h3>
        <p class="text-sm text-slate-600 mt-2">কল, মেসেজ বা ইন-অ্যাপ চ্যাটের মাধ্যমে সহজ যোগাযোগ।</p>
      </div>
      <div class="bg-white border border-line rounded-2xl p-5">
        <h3 class="font-semibold">লোকেশন বেইজড</h3>
        <p class="text-sm text-slate-600 mt-2">জেলা, উপজেলা, ওয়ার্ড অনুযায়ী সেবার তালিকা।</p>
      </div>
      <div class="bg-white border border-line rounded-2xl p-5">
        <h3 class="font-semibold">অ্যাকাউন্ট ড্যাশবোর্ড</h3>
        <p class="text-sm text-slate-600 mt-2">নিজের পোস্ট, রিভিউ, বুকিং, রিকোয়েস্ট সব একসাথে।</p>
      </div>
      <div class="bg-white border border-line rounded-2xl p-5">
        <h3 class="font-semibold">ভেরিফিকেশন</h3>
        <p class="text-sm text-slate-600 mt-2">ভেরিফায়েড প্রোফাইল ও রিপোর্টিং সিস্টেম।</p>
      </div>
    </div>
  </section>

  <section id="modules" class="py-10">
    <div class="flex items-center justify-between mb-6">
      <h2 class="font-display text-2xl font-semibold">অ্যাপে থাকা সব মডিউল</h2>
      <span class="text-sm text-slate-500">ওয়েবে একইভাবে দেখা যাবে</span>
    </div>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <a href="/services" class="bg-white border border-line rounded-2xl p-4 hover:border-slate-300">
        <p class="text-xs text-slate-500">সার্ভিস</p>
        <h3 class="font-semibold mt-2">কর্মী তালিকা</h3>
        <p class="text-sm text-slate-600 mt-2">দক্ষ শ্রমিক, টেকনিশিয়ান, ডেলিভারি, হেল্পার।</p>
      </a>
      <a href="/market" class="bg-white border border-line rounded-2xl p-4 hover:border-slate-300">
        <p class="text-xs text-slate-500">মার্কেটপ্লেস</p>
        <h3 class="font-semibold mt-2">পণ্য কেনা-বেচা</h3>
        <p class="text-sm text-slate-600 mt-2">সেলার প্রোফাইল, চ্যাট, রিপোর্ট সিস্টেম।</p>
      </a>
      <a href="/jobs" class="bg-white border border-line rounded-2xl p-4 hover:border-slate-300">
        <p class="text-xs text-slate-500">চাকরি</p>
        <h3 class="font-semibold mt-2">পোস্ট ও আবেদন</h3>
        <p class="text-sm text-slate-600 mt-2">হায়ারিং, ফ্রিল্যান্স, CV আপলোড।</p>
      </a>
      <a href="/doctors" class="bg-white border border-line rounded-2xl p-4 hover:border-slate-300">
        <p class="text-xs text-slate-500">স্বাস্থ্য</p>
        <h3 class="font-semibold mt-2">ডাক্তার তালিকা</h3>
        <p class="text-sm text-slate-600 mt-2">স্পেশালিস্ট, শিডিউল, রিভিউ।</p>
      </a>
      <a href="/hospitals" class="bg-white border border-line rounded-2xl p-4 hover:border-slate-300">
        <p class="text-xs text-slate-500">হাসপাতাল</p>
        <h3 class="font-semibold mt-2">হাসপাতাল ও ক্লিনিক</h3>
        <p class="text-sm text-slate-600 mt-2">রেটিং, লোকেশন, কল অপশন।</p>
      </a>
      <a href="/restaurants" class="bg-white border border-line rounded-2xl p-4 hover:border-slate-300">
        <p class="text-xs text-slate-500">খাবার</p>
        <h3 class="font-semibold mt-2">রেস্তোরাঁ</h3>
        <p class="text-sm text-slate-600 mt-2">মেনু, সুবিধা, বুকিং তথ্য।</p>
      </a>
      <a href="/hotels" class="bg-white border border-line rounded-2xl p-4 hover:border-slate-300">
        <p class="text-xs text-slate-500">ভ্রমণ</p>
        <h3 class="font-semibold mt-2">হোটেল</h3>
        <p class="text-sm text-slate-600 mt-2">দাম, সুবিধা, বুকিং তথ্য।</p>
      </a>
      <a href="/property" class="bg-white border border-line rounded-2xl p-4 hover:border-slate-300">
        <p class="text-xs text-slate-500">প্রপার্টি</p>
        <h3 class="font-semibold mt-2">ভাড়া/বিক্রি</h3>
        <p class="text-sm text-slate-600 mt-2">বাসা, দোকান, জমি তালিকা।</p>
      </a>
      <a href="/education" class="bg-white border border-line rounded-2xl p-4 hover:border-slate-300">
        <p class="text-xs text-slate-500">শিক্ষা</p>
        <h3 class="font-semibold mt-2">শিক্ষা প্রতিষ্ঠান</h3>
        <p class="text-sm text-slate-600 mt-2">স্কুল, কলেজ, মাদ্রাসা, কোচিং।</p>
      </a>
    </div>
  </section>

  <section id="how" class="py-10">
    <div class="bg-white border border-line rounded-3xl p-6">
      <h2 class="font-display text-2xl font-semibold">কিভাবে কাজ করে</h2>
      <div class="mt-6 grid gap-4 md:grid-cols-4">
        <div class="p-4 rounded-2xl bg-mist">
          <p class="text-xs text-slate-500">ধাপ ১</p>
          <h3 class="font-semibold mt-1">অ্যাকাউন্ট খুলুন</h3>
          <p class="text-sm text-slate-600 mt-2">ফোন নম্বর দিয়ে দ্রুত রেজিস্টার করুন।</p>
        </div>
        <div class="p-4 rounded-2xl bg-mist">
          <p class="text-xs text-slate-500">ধাপ ২</p>
          <h3 class="font-semibold mt-1">সেবা খুঁজুন</h3>
          <p class="text-sm text-slate-600 mt-2">ক্যাটাগরি, লোকেশন, রেটিং অনুযায়ী ফিল্টার।</p>
        </div>
        <div class="p-4 rounded-2xl bg-mist">
          <p class="text-xs text-slate-500">ধাপ ৩</p>
          <h3 class="font-semibold mt-1">যোগাযোগ করুন</h3>
          <p class="text-sm text-slate-600 mt-2">কল, চ্যাট বা বুকিং রিকোয়েস্ট পাঠান।</p>
        </div>
        <div class="p-4 rounded-2xl bg-mist">
          <p class="text-xs text-slate-500">ধাপ ৪</p>
          <h3 class="font-semibold mt-1">রিভিউ দিন</h3>
          <p class="text-sm text-slate-600 mt-2">অভিজ্ঞতা শেয়ার করে কমিউনিটিকে সাহায্য করুন।</p>
        </div>
      </div>
    </div>
  </section>

  <section id="faq" class="py-10">
    <div class="flex items-center justify-between mb-6">
      <h2 class="font-display text-2xl font-semibold">প্রায়শই জিজ্ঞাসিত প্রশ্ন</h2>
      <button class="text-sm text-leaf font-medium">সব FAQ দেখুন</button>
    </div>
    <div class="grid gap-4 md:grid-cols-2">
      <div class="bg-white border border-line rounded-2xl p-5">
        <h3 class="font-semibold">অ্যাপের সব ফিচার কি ওয়েবে থাকবে?</h3>
        <p class="text-sm text-slate-600 mt-2">হ্যাঁ, অ্যাপে থাকা প্রধান ফিচারগুলোর ওয়েব সংস্করণ থাকবে যাতে সবাই সহজে তথ্য পায়।</p>
      </div>
      <div class="bg-white border border-line rounded-2xl p-5">
        <h3 class="font-semibold">কিভাবে তথ্য আপডেট করা হবে?</h3>
        <p class="text-sm text-slate-600 mt-2">ভেরিফায়েড টিম ও কমিউনিটি রিপোর্টিংয়ের মাধ্যমে নিয়মিত আপডেট করা হবে।</p>
      </div>
    </div>
  </section>

  <section class="py-10">
    <div class="bg-white border border-line rounded-3xl p-6">
      <h3 class="font-display text-xl font-semibold">ওয়েব প্ল্যাটফর্ম সম্পর্কে</h3>
      <p class="text-sm text-slate-600 mt-3">
        এই ওয়েবসাইটে জেলার সব গুরুত্বপূর্ণ সেবা, তথ্য ও ডিরেক্টরি এক জায়গায় পাওয়া যাবে। অ্যাপের প্রধান
        ফিচারগুলো ওয়েবেও থাকবে যাতে সবাই দ্রুত তথ্য পায় এবং প্রয়োজনীয় সেবা খুঁজে পায়।
      </p>
      <div class="mt-4 grid gap-3 md:grid-cols-2">
        <div class="bg-mist rounded-2xl p-4">
          <p class="font-semibold">ফিচারসমূহ এক জায়গায়</p>
          <p class="text-sm text-slate-600 mt-1">সার্ভিস, চাকরি, স্বাস্থ্য, শিক্ষা, বাজার সব অন্তর্ভুক্ত।</p>
        </div>
        <div class="bg-mist rounded-2xl p-4">
          <p class="font-semibold">SEO ও AdSense প্রস্তুত</p>
          <p class="text-sm text-slate-600 mt-1">পরিষ্কার কনটেন্ট, FAQ ও স্কিমা যুক্ত করা হয়েছে।</p>
        </div>
      </div>
    </div>
  </section>

  <section class="py-10" id="contact">
    <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
      <div class="bg-ink rounded-3xl p-8 text-white">
        <h2 class="font-display text-2xl font-semibold">আপনার জেলার জন্য ডিজিটাল সমাধান</h2>
        <p class="text-white/80 mt-3">প্রশাসন, উদ্যোক্তা, কমিউনিটি লিডার—সবার জন্য একসাথে কাজ করার প্ল্যাটফর্ম।</p>
        <div class="mt-6 flex flex-wrap gap-3">
          <button class="rounded-full bg-white text-ink px-5 py-2 text-sm">অ্যাডমিন ডেমো চাই</button>
          <button class="rounded-full border border-white/40 px-5 py-2 text-sm">যোগাযোগ করুন</button>
        </div>
      </div>
      <div class="bg-white border border-line rounded-3xl p-6">
        <h3 class="font-display text-xl font-semibold">যোগাযোগ তথ্য</h3>
        <div class="mt-4 space-y-3 text-sm text-slate-600">
          <p>ইমেইল: support@volabashi.com</p>
          <p>হেল্পলাইন: +880 1XXX-XXXXXX</p>
          <p>ঠিকানা: জেলা তথ্য সেবা কেন্দ্র</p>
        </div>
        <div class="mt-6 rounded-2xl bg-mist p-4 text-sm">
          <p class="font-semibold">ডেভেলপার API ও পার্টনারশিপ</p>
          <p class="text-slate-600 mt-1">নতুন সেবা যোগ করতে আমাদের সাথে যোগাযোগ করুন।</p>
        </div>
      </div>
    </div>
  </section>
@endsection
