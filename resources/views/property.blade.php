@extends('layouts.site')

@php
  $title = 'প্রপার্টি | ভোলাবাসী';
  $description = 'ভাড়া ও বিক্রির প্রপার্টি তালিকা, লোকেশন ও বিস্তারিত তথ্য এক জায়গায়।';
@endphp

@push('head')
  <script type="application/ld+json">
  {
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
      {"@@type": "Question", "name": "ভাড়া ও বিক্রি ট্যাব কীভাবে কাজ করে?", "acceptedAnswer": {"@@type": "Answer", "text": "ভাড়া ও বিক্রি আলাদা ট্যাবে দেখানো হয়।"}},
      {"@@type": "Question", "name": "লোকেশন দিয়ে প্রপার্টি খুঁজতে পারবো?", "acceptedAnswer": {"@@type": "Answer", "text": "হ্যাঁ, জেলা/উপজেলা অনুযায়ী সার্চ করা যায়।"}},
      {"@@type": "Question", "name": "প্রপার্টি যোগ করতে কী লাগে?", "acceptedAnswer": {"@@type": "Answer", "text": "ঠিকানা, মূল্য ও প্রয়োজনীয় তথ্য দিয়ে পোস্ট করা যায়।"}}
    ]
  }
  </script>
@endpush

@section('content')
  <section class="pt-12 pb-10 grid gap-8 lg:grid-cols-[1.1fr_0.9fr] items-center">
    <div class="space-y-5">
      <p class="text-xs text-slate-500">প্রপার্টি</p>
      <h1 class="font-display text-4xl font-bold">ভাড়া ও বিক্রির প্রপার্টি খুঁজুন দ্রুত</h1>
      <p class="text-slate-600">বাসা, দোকান, জমি, অফিস স্পেস—সবকিছু একসাথে।</p>
      <div class="flex gap-3">
        <button class="rounded-full bg-ink text-white px-6 py-2 text-sm">প্রপার্টি দেখুন</button>
        <button class="rounded-full border border-line px-6 py-2 text-sm">প্রপার্টি যোগ</button>
      </div>
    </div>
    <div class="bg-white border border-line rounded-3xl p-6">
      <h3 class="font-semibold">ফিল্টার অপশন</h3>
      <ul class="mt-4 space-y-2 text-sm text-slate-600">
        <li>ভাড়া/বিক্রি ট্যাব</li>
        <li>লোকেশন ও বাজেট</li>
        <li>সাইজ ও সুবিধা</li>
      </ul>
    </div>
  </section>

  <section class="py-10">
    <h2 class="font-display text-2xl font-semibold mb-6">জনপ্রিয় ক্যাটাগরি</h2>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      @foreach(['বাসা ভাড়া','বাড়ি বিক্রি','জমি বিক্রি','দোকান/শোরুম','অফিস স্পেস','গুদাম','কৃষিজমি','অন্যান্য'] as $item)
        <div class="bg-white border border-line rounded-2xl p-4">
          <p class="font-semibold">{{ $item }}</p>
          <p class="text-sm text-slate-600 mt-2">সার্চ ও বুকিং সহজ।</p>
        </div>
      @endforeach
    </div>
  </section>

  <section class="py-10">
    <div class="bg-white border border-line rounded-3xl p-6">
      <h3 class="font-display text-xl font-semibold">প্রপার্টি ডিরেক্টরি সম্পর্কে</h3>
      <p class="text-sm text-slate-600 mt-3">
        বাসা, দোকান, জমি বা অফিস—সব ধরনের প্রপার্টি তথ্য এক জায়গায় থাকায় দ্রুত সিদ্ধান্ত নেওয়া যায়।
        লোকেশন, বাজেট ও সুবিধা দেখে বাছাই করুন।
      </p>
      <div class="mt-4 grid gap-3 md:grid-cols-2">
        <div class="bg-mist rounded-2xl p-4">
          <p class="font-semibold">বাজেট ফিল্টার</p>
          <p class="text-sm text-slate-600 mt-1">পছন্দের রেঞ্জে খুঁজুন।</p>
        </div>
        <div class="bg-mist rounded-2xl p-4">
          <p class="font-semibold">লোকেশন ম্যাপিং</p>
          <p class="text-sm text-slate-600 mt-1">ঠিকানা ও আশেপাশের সুবিধা দেখুন।</p>
        </div>
      </div>
    </div>
  </section>

  <section class="py-10">
    <h2 class="font-display text-2xl font-semibold mb-6">FAQ</h2>
    <div class="grid gap-4 md:grid-cols-2">
      <div class="bg-white border border-line rounded-2xl p-5">
        <h3 class="font-semibold">প্রপার্টি পোস্ট করলে কতদিন থাকবে?</h3>
        <p class="text-sm text-slate-600 mt-2">সাধারণভাবে বিক্রি/ভাড়া হওয়া পর্যন্ত লিস্টিং থাকে।</p>
      </div>
      <div class="bg-white border border-line rounded-2xl p-5">
        <h3 class="font-semibold">ভুয়া পোস্ট চিহ্নিত হলে কী হবে?</h3>
        <p class="text-sm text-slate-600 mt-2">রিপোর্টের ভিত্তিতে পোস্ট রিমুভ করা হয়।</p>
      </div>
    </div>
  </section>
@endsection
