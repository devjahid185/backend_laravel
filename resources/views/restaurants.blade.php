@extends('layouts.site')

@php
  $title = 'রেস্তোরাঁ ডিরেক্টরি | ভোলাবাসী';
  $description = 'রেস্তোরাঁ, ক্যাফে, মেনু, দাম ও রিভিউ এক জায়গায়।';
@endphp

@push('head')
  <script type="application/ld+json">
  {
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
      {"@@type": "Question", "name": "রেস্তোরাঁ তথ্য কীভাবে যাচাই হয়?", "acceptedAnswer": {"@@type": "Answer", "text": "ভেরিফায়েড টিম ও কমিউনিটি রিপোর্টিংয়ের মাধ্যমে আপডেট হয়।"}},
      {"@@type": "Question", "name": "মেনু ও দাম কি দেখা যাবে?", "acceptedAnswer": {"@@type": "Answer", "text": "প্রোফাইলে মেনু ও প্রাইস রেঞ্জ তথ্য থাকে।"}},
      {"@@type": "Question", "name": "রিভিউ দেওয়া যাবে?", "acceptedAnswer": {"@@type": "Answer", "text": "খাবার নেওয়ার পর রিভিউ ও রেটিং দেওয়া যায়।"}}
    ]
  }
  </script>
@endpush

@section('content')
  <section class="pt-12 pb-10 grid gap-8 lg:grid-cols-[1.1fr_0.9fr] items-center">
    <div class="space-y-5">
      <p class="text-xs text-slate-500">রেস্তোরাঁ ও খাবার</p>
      <h1 class="font-display text-4xl font-bold">খাবার ও রেস্তোরাঁ খুঁজুন সহজে</h1>
      <p class="text-slate-600">মেনু, দাম, রেটিং ও লোকেশন দেখে রেস্তোরাঁ বাছাই করুন।</p>
      <div class="flex gap-3">
        <button class="rounded-full bg-ink text-white px-6 py-2 text-sm">রেস্তোরাঁ দেখুন</button>
        <button class="rounded-full border border-line px-6 py-2 text-sm">রেস্তোরাঁ যোগ</button>
      </div>
    </div>
    <div class="bg-white border border-line rounded-3xl p-6">
      <h3 class="font-semibold">রেস্তোরাঁ ফিচার</h3>
      <ul class="mt-4 space-y-2 text-sm text-slate-600">
        <li>মেনু ও প্রাইস রেঞ্জ</li>
        <li>রিভিউ ও রেটিং</li>
        <li>লোকেশন ও কল</li>
      </ul>
    </div>
  </section>

  <section class="py-10">
    <h2 class="font-display text-2xl font-semibold mb-6">জনপ্রিয় ধরন</h2>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      @foreach(['বাংলা খাবার','ফাস্ট ফুড','ক্যাফে','মিষ্টি','চাইনিজ','বিরিয়ানি','সমুদ্র খাবার','অন্যান্য'] as $item)
        <div class="bg-white border border-line rounded-2xl p-4">
          <p class="font-semibold">{{ $item }}</p>
          <p class="text-sm text-slate-600 mt-2">সার্ভিস ও দাম তুলনা করুন।</p>
        </div>
      @endforeach
    </div>
  </section>

  <section class="py-10">
    <div class="bg-white border border-line rounded-3xl p-6">
      <h3 class="font-display text-xl font-semibold">রেস্তোরাঁ ডিরেক্টরি সম্পর্কে</h3>
      <p class="text-sm text-slate-600 mt-3">
        জেলার রেস্তোরাঁ ও ক্যাফে তথ্য এক জায়গায় থাকায় খাবার বাছাই সহজ হয়। মেনু, দাম, লোকেশন ও রিভিউ
        দেখে দ্রুত সিদ্ধান্ত নেওয়া যায়।
      </p>
      <div class="mt-4 grid gap-3 md:grid-cols-2">
        <div class="bg-mist rounded-2xl p-4">
          <p class="font-semibold">দাম তুলনা</p>
          <p class="text-sm text-slate-600 mt-1">প্রাইস রেঞ্জ অনুযায়ী বাছাই করুন।</p>
        </div>
        <div class="bg-mist rounded-2xl p-4">
          <p class="font-semibold">রেটিং ভিত্তিক তালিকা</p>
          <p class="text-sm text-slate-600 mt-1">ভালো রিভিউ পাওয়া জায়গা আগে দেখুন।</p>
        </div>
      </div>
    </div>
  </section>

  <section class="py-10">
    <h2 class="font-display text-2xl font-semibold mb-6">FAQ</h2>
    <div class="grid gap-4 md:grid-cols-2">
      <div class="bg-white border border-line rounded-2xl p-5">
        <h3 class="font-semibold">রেস্তোরাঁ যোগ করতে কী লাগবে?</h3>
        <p class="text-sm text-slate-600 mt-2">ঠিকানা, যোগাযোগ ও মেনু তথ্য দিতে হবে।</p>
      </div>
      <div class="bg-white border border-line rounded-2xl p-5">
        <h3 class="font-semibold">ডেলিভারি তথ্য থাকবে কি?</h3>
        <p class="text-sm text-slate-600 mt-2">যদি ডেলিভারি থাকে তবে প্রোফাইলে উল্লেখ থাকবে।</p>
      </div>
    </div>
  </section>
@endsection
