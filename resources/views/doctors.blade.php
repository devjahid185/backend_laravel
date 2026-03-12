@extends('layouts.site')

@php
  $title = 'ডাক্তার ডিরেক্টরি | ভোলাবাসী';
  $description = 'বিশেষজ্ঞ ডাক্তার, চেম্বার টাইম, ফি, রিভিউ—সব এক জায়গায়।';
@endphp

@push('head')
  <script type="application/ld+json">
  {
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
      {"@@type": "Question", "name": "ডাক্তার খুঁজতে কোন তথ্য দরকার?", "acceptedAnswer": {"@@type": "Answer", "text": "বিশেষায়ন, লোকেশন ও ফি দিয়ে সহজে খুঁজতে পারবেন।"}},
      {"@@type": "Question", "name": "ডাক্তার রিভিউ দেওয়া যাবে?", "acceptedAnswer": {"@@type": "Answer", "text": "সেবা নেওয়ার পর রিভিউ ও রেটিং দেওয়া যায়।"}},
      {"@@type": "Question", "name": "চেম্বার টাইম কোথায় থাকবে?", "acceptedAnswer": {"@@type": "Answer", "text": "প্রোফাইলে চেম্বার টাইম ও যোগাযোগ তথ্য থাকবে।"}}
    ]
  }
  </script>
@endpush

@section('content')
  <section class="pt-12 pb-10 grid gap-8 lg:grid-cols-[1.1fr_0.9fr] items-center">
    <div class="space-y-5">
      <p class="text-xs text-slate-500">ডাক্তার ডিরেক্টরি</p>
      <h1 class="font-display text-4xl font-bold">বিশেষজ্ঞ ডাক্তার খুঁজুন সহজে</h1>
      <p class="text-slate-600">বিশেষায়ন, ফি, অভিজ্ঞতা ও লোকেশন অনুযায়ী ডাক্তার নির্বাচন করুন।</p>
      <div class="flex gap-3">
        <button class="rounded-full bg-ink text-white px-6 py-2 text-sm">ডাক্তার খুঁজুন</button>
        <button class="rounded-full border border-line px-6 py-2 text-sm">ডাক্তার রেজিস্টার</button>
      </div>
    </div>
    <div class="bg-white border border-line rounded-3xl p-6">
      <h3 class="font-semibold">ডাক্তার প্রোফাইল</h3>
      <ul class="mt-4 space-y-2 text-sm text-slate-600">
        <li>চেম্বার টাইম ও ফি</li>
        <li>বিশেষায়ন ও অভিজ্ঞতা</li>
        <li>রিভিউ ও রেটিং</li>
      </ul>
    </div>
  </section>

  <section class="py-10">
    <h2 class="font-display text-2xl font-semibold mb-6">জনপ্রিয় বিভাগ</h2>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      @foreach(['মেডিসিন','কার্ডিওলজি','নিউরোলজি','চর্মরোগ','গাইনী','অর্থোপেডিক','চক্ষু','অন্যান্য'] as $item)
        <div class="bg-white border border-line rounded-2xl p-4">
          <p class="font-semibold">{{ $item }}</p>
          <p class="text-sm text-slate-600 mt-2">আপনার সমস্যার জন্য বিশেষজ্ঞ দেখুন।</p>
        </div>
      @endforeach
    </div>
  </section>

  <section class="py-10">
    <div class="bg-white border border-line rounded-3xl p-6">
      <h3 class="font-display text-xl font-semibold">ডাক্তার ডিরেক্টরি সম্পর্কে</h3>
      <p class="text-sm text-slate-600 mt-3">
        জেলার ডাক্তারদের প্রোফাইল এক জায়গায় থাকায় রোগীর জন্য সিদ্ধান্ত নেওয়া সহজ হয়। বিশেষায়ন, অভিজ্ঞতা,
        চেম্বার টাইম এবং ফি দেখে দ্রুত যোগাযোগ করা যায়।
      </p>
      <div class="mt-4 grid gap-3 md:grid-cols-2">
        <div class="bg-mist rounded-2xl p-4">
          <p class="font-semibold">বিশেষজ্ঞ তালিকা</p>
          <p class="text-sm text-slate-600 mt-1">সমস্যা অনুযায়ী ডাক্তার খুঁজুন।</p>
        </div>
        <div class="bg-mist rounded-2xl p-4">
          <p class="font-semibold">রিভিউ ভিত্তিক সিদ্ধান্ত</p>
          <p class="text-sm text-slate-600 mt-1">কমিউনিটির অভিজ্ঞতা জানতে পারবেন।</p>
        </div>
      </div>
    </div>
  </section>

  <section class="py-10">
    <h2 class="font-display text-2xl font-semibold mb-6">FAQ</h2>
    <div class="grid gap-4 md:grid-cols-2">
      <div class="bg-white border border-line rounded-2xl p-5">
        <h3 class="font-semibold">ডাক্তার তালিকা আপডেট হয় কীভাবে?</h3>
        <p class="text-sm text-slate-600 mt-2">ভেরিফায়েড টিম নিয়মিত তথ্য আপডেট করে।</p>
      </div>
      <div class="bg-white border border-line rounded-2xl p-5">
        <h3 class="font-semibold">ডাক্তার যুক্ত করতে কী লাগে?</h3>
        <p class="text-sm text-slate-600 mt-2">BMDC ও প্রয়োজনীয় তথ্য দিয়ে রেজিস্টার করা যায়।</p>
      </div>
    </div>
  </section>
@endsection
