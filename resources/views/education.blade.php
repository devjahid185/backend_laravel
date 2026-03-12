@extends('layouts.site')

@php
  $title = 'শিক্ষা প্রতিষ্ঠান | ভোলাবাসী';
  $description = 'স্কুল, কলেজ, মাদ্রাসা, কোচিং ও শিক্ষা প্রতিষ্ঠানের তথ্য এক জায়গায়।';
@endphp

@push('head')
  <script type="application/ld+json">
  {
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
      {"@@type": "Question", "name": "প্রতিষ্ঠান খুঁজতে কীভাবে সার্চ করবো?", "acceptedAnswer": {"@@type": "Answer", "text": "ক্যাটাগরি ও লোকেশন অনুযায়ী সার্চ করা যায়।"}},
      {"@@type": "Question", "name": "প্রতিষ্ঠান তথ্য কে আপডেট করে?", "acceptedAnswer": {"@@type": "Answer", "text": "ভেরিফায়েড টিম নিয়মিত তথ্য আপডেট করে।"}},
      {"@@type": "Question", "name": "ভুল তথ্য দেখলে কী করবো?", "acceptedAnswer": {"@@type": "Answer", "text": "রিপোর্ট দিলে তথ্য যাচাই করা হয়।"}}
    ]
  }
  </script>
@endpush

@section('content')
  <section class="pt-12 pb-10 grid gap-8 lg:grid-cols-[1.1fr_0.9fr] items-center">
    <div class="space-y-5">
      <p class="text-xs text-slate-500">শিক্ষা প্রতিষ্ঠান</p>
      <h1 class="font-display text-4xl font-bold">শিক্ষা প্রতিষ্ঠানের তথ্য এক ক্লিকে</h1>
      <p class="text-slate-600">স্কুল, কলেজ, মাদ্রাসা, কোচিং—লোকেশন ও ক্যাটাগরি অনুযায়ী খুঁজুন।</p>
      <div class="flex gap-3">
        <button class="rounded-full bg-ink text-white px-6 py-2 text-sm">প্রতিষ্ঠান দেখুন</button>
        <button class="rounded-full border border-line px-6 py-2 text-sm">প্রতিষ্ঠান যোগ</button>
      </div>
    </div>
    <div class="bg-white border border-line rounded-3xl p-6">
      <h3 class="font-semibold">তথ্যসমূহ</h3>
      <ul class="mt-4 space-y-2 text-sm text-slate-600">
        <li>ঠিকানা ও যোগাযোগ</li>
        <li>ক্যাটাগরি ও শিক্ষা স্তর</li>
        <li>রিভিউ ও রেটিং</li>
      </ul>
    </div>
  </section>

  <section class="py-10">
    <h2 class="font-display text-2xl font-semibold mb-6">শিক্ষা ক্যাটাগরি</h2>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      @foreach(['প্রাথমিক বিদ্যালয়','মাধ্যমিক বিদ্যালয়','কলেজ','মাদ্রাসা','কোচিং','কারিগরি','ভর্তি কোচিং','অন্যান্য'] as $item)
        <div class="bg-white border border-line rounded-2xl p-4">
          <p class="font-semibold">{{ $item }}</p>
          <p class="text-sm text-slate-600 mt-2">তথ্য ও যোগাযোগ দেখুন।</p>
        </div>
      @endforeach
    </div>
  </section>

  <section class="py-10">
    <div class="bg-white border border-line rounded-3xl p-6">
      <h3 class="font-display text-xl font-semibold">শিক্ষা ডিরেক্টরি সম্পর্কে</h3>
      <p class="text-sm text-slate-600 mt-3">
        শিক্ষার্থী ও অভিভাবকদের জন্য প্রয়োজনীয় প্রতিষ্ঠান তথ্য এক জায়গায় থাকে। লোকেশন, শিক্ষা স্তর,
        যোগাযোগ ও রিভিউ দেখে দ্রুত সিদ্ধান্ত নেওয়া যায়।
      </p>
      <div class="mt-4 grid gap-3 md:grid-cols-2">
        <div class="bg-mist rounded-2xl p-4">
          <p class="font-semibold">লোকেশন ভিত্তিক</p>
          <p class="text-sm text-slate-600 mt-1">উপজেলা/ওয়ার্ড অনুযায়ী ফিল্টার।</p>
        </div>
        <div class="bg-mist rounded-2xl p-4">
          <p class="font-semibold">ভেরিফায়েড তথ্য</p>
          <p class="text-sm text-slate-600 mt-1">নিয়মিত আপডেট করা হয়।</p>
        </div>
      </div>
    </div>
  </section>

  <section class="py-10">
    <h2 class="font-display text-2xl font-semibold mb-6">FAQ</h2>
    <div class="grid gap-4 md:grid-cols-2">
      <div class="bg-white border border-line rounded-2xl p-5">
        <h3 class="font-semibold">প্রতিষ্ঠান যোগ করার নিয়ম কী?</h3>
        <p class="text-sm text-slate-600 mt-2">ঠিকানা, যোগাযোগ ও ক্যাটাগরি তথ্য দিতে হয়।</p>
      </div>
      <div class="bg-white border border-line rounded-2xl p-5">
        <h3 class="font-semibold">কোচিং সেন্টার কি যুক্ত থাকবে?</h3>
        <p class="text-sm text-slate-600 mt-2">হ্যাঁ, কোচিং সেন্টারও তালিকায় থাকবে।</p>
      </div>
    </div>
  </section>
@endsection
