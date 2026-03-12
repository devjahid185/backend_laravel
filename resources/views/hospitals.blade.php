@extends('layouts.site')

@php
  $title = 'হাসপাতাল ও ক্লিনিক | ভোলাবাসী';
  $description = 'হাসপাতাল, ক্লিনিক, ডায়াগনস্টিক ও জরুরি সেবা তথ্য এক জায়গায়।';
@endphp

@push('head')
  <script type="application/ld+json">
  {
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
      {"@@type": "Question", "name": "হাসপাতাল তথ্য কীভাবে যাচাই হয়?", "acceptedAnswer": {"@@type": "Answer", "text": "টিম যাচাই ও কমিউনিটি রিপোর্টের মাধ্যমে তথ্য আপডেট হয়।"}},
      {"@@type": "Question", "name": "রেটিং ও রিভিউ কেন দরকার?", "acceptedAnswer": {"@@type": "Answer", "text": "সেবার মান বোঝার জন্য রিভিউ গুরুত্বপূর্ণ।"}},
      {"@@type": "Question", "name": "লোকেশন দিয়ে হাসপাতাল খুঁজতে পারবো?", "acceptedAnswer": {"@@type": "Answer", "text": "জেলা ও উপজেলা অনুযায়ী ফিল্টার আছে।"}}
    ]
  }
  </script>
@endpush

@section('content')
  <section class="pt-12 pb-10 grid gap-8 lg:grid-cols-[1.1fr_0.9fr] items-center">
    <div class="space-y-5">
      <p class="text-xs text-slate-500">হাসপাতাল ও ক্লিনিক</p>
      <h1 class="font-display text-4xl font-bold">হাসপাতাল তথ্য ও রিভিউ এখন এক ক্লিকে</h1>
      <p class="text-slate-600">লোকেশন, বিভাগ, সেবা ও রেটিং দেখে হাসপাতাল নির্বাচন করুন।</p>
      <div class="flex gap-3">
        <button class="rounded-full bg-ink text-white px-6 py-2 text-sm">হাসপাতাল খুঁজুন</button>
        <button class="rounded-full border border-line px-6 py-2 text-sm">হাসপাতাল যোগ</button>
      </div>
    </div>
    <div class="bg-white border border-line rounded-3xl p-6">
      <h3 class="font-semibold">রিভিউ সিস্টেম</h3>
      <ul class="mt-4 space-y-2 text-sm text-slate-600">
        <li>সেবার মান রেটিং</li>
        <li>লোকেশন ও কল</li>
        <li>অ্যাম্বুলেন্স তথ্য</li>
      </ul>
    </div>
  </section>

  <section class="py-10">
    <h2 class="font-display text-2xl font-semibold mb-6">সেবার ধরন</h2>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      @foreach(['জেনারেল হাসপাতাল','ডায়াগনস্টিক','ডেন্টাল','ম্যাটার্নিটি','ক্লিনিক','ফিজিওথেরাপি','ফার্মেসি','অন্যান্য'] as $item)
        <div class="bg-white border border-line rounded-2xl p-4">
          <p class="font-semibold">{{ $item }}</p>
          <p class="text-sm text-slate-600 mt-2">দ্রুত লোকেশন ও সময় দেখুন।</p>
        </div>
      @endforeach
    </div>
  </section>

  <section class="py-10">
    <div class="bg-white border border-line rounded-3xl p-6">
      <h3 class="font-display text-xl font-semibold">হাসপাতাল ডিরেক্টরি সম্পর্কে</h3>
      <p class="text-sm text-slate-600 mt-3">
        জরুরি সময়ে দ্রুত সিদ্ধান্ত নিতে হাসপাতাল তথ্য খুব জরুরি। এই ডিরেক্টরিতে লোকেশন, সেবা, রেটিং ও
        যোগাযোগ তথ্য দেওয়া থাকে।
      </p>
      <div class="mt-4 grid gap-3 md:grid-cols-2">
        <div class="bg-mist rounded-2xl p-4">
          <p class="font-semibold">জরুরি কল</p>
          <p class="text-sm text-slate-600 mt-1">হাসপাতালের নম্বর দ্রুত পাওয়া যায়।</p>
        </div>
        <div class="bg-mist rounded-2xl p-4">
          <p class="font-semibold">লোকেশন নির্দেশনা</p>
          <p class="text-sm text-slate-600 mt-1">ম্যাপ ভিত্তিক তথ্য রাখা হয়।</p>
        </div>
      </div>
    </div>
  </section>

  <section class="py-10">
    <h2 class="font-display text-2xl font-semibold mb-6">FAQ</h2>
    <div class="grid gap-4 md:grid-cols-2">
      <div class="bg-white border border-line rounded-2xl p-5">
        <h3 class="font-semibold">হাসপাতাল যোগ করার নিয়ম কী?</h3>
        <p class="text-sm text-slate-600 mt-2">প্রয়োজনীয় তথ্য ও যোগাযোগ দিয়ে যোগ করা যায়।</p>
      </div>
      <div class="bg-white border border-line rounded-2xl p-5">
        <h3 class="font-semibold">ভুল তথ্য দেখলে কী করবো?</h3>
        <p class="text-sm text-slate-600 mt-2">রিপোর্ট অপশন ব্যবহার করলে সংশোধন করা হয়।</p>
      </div>
    </div>
  </section>
@endsection
