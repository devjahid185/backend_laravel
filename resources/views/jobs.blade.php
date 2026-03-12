@extends('layouts.site')

@php
  $title = 'চাকরি ও নিয়োগ | ভোলাবাসী';
  $description = 'চাকরি পোস্ট, আবেদন, CV আপলোড ও নিয়োগ সব এক জায়গায়।';
@endphp

@push('head')
  <script type="application/ld+json">
  {
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
      {"@@type": "Question", "name": "চাকরি পোস্ট করা যাবে কীভাবে?", "acceptedAnswer": {"@@type": "Answer", "text": "প্রোফাইল থেকে চাকরি পোস্ট ফর্ম পূরণ করে পোস্ট করা যায়।"}},
      {"@@type": "Question", "name": "CV আপলোড সুবিধা আছে?", "acceptedAnswer": {"@@type": "Answer", "text": "PDF/Word/ইমেজ ফরম্যাটে CV আপলোড করা যায়।"}},
      {"@@type": "Question", "name": "আবেদনগুলো কোথায় দেখা যাবে?", "acceptedAnswer": {"@@type": "Answer", "text": "My Applications সেকশনে আবেদনগুলোর তালিকা থাকে।"}}
    ]
  }
  </script>
@endpush

@section('content')
  <section class="pt-12 pb-10 grid gap-8 lg:grid-cols-[1.1fr_0.9fr] items-center">
    <div class="space-y-5">
      <p class="text-xs text-slate-500">চাকরি ও নিয়োগ</p>
      <h1 class="font-display text-4xl font-bold">চাকরি খুঁজুন, নিয়োগ দিন—সব এক প্ল্যাটফর্মে</h1>
      <p class="text-slate-600">ফুলটাইম, পার্টটাইম, ফ্রিল্যান্স—সব ধরনের চাকরি সহজে পোস্ট ও আবেদন করুন।</p>
      <div class="flex gap-3">
        <button class="rounded-full bg-ink text-white px-6 py-2 text-sm">চাকরি দেখুন</button>
        <button class="rounded-full border border-line px-6 py-2 text-sm">চাকরি পোস্ট</button>
      </div>
    </div>
    <div class="bg-white border border-line rounded-3xl p-6">
      <h3 class="font-semibold">ফিচারসমূহ</h3>
      <ul class="mt-4 space-y-2 text-sm text-slate-600">
        <li>CV আপলোড ও ডাউনলোড</li>
        <li>My Posts & My Applications</li>
        <li>স্মার্ট ক্যাটাগরি ফিল্টার</li>
      </ul>
    </div>
  </section>

  <section class="py-10">
    <h2 class="font-display text-2xl font-semibold mb-6">জনপ্রিয় ক্যাটাগরি</h2>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      @foreach(['অফিস স্টাফ','সেলস/মার্কেটিং','ডেলিভারি','হাসপাতাল স্টাফ','শিক্ষক','হোটেল/রেস্টুরেন্ট','ড্রাইভার','অন্যান্য'] as $item)
        <div class="bg-white border border-line rounded-2xl p-4">
          <p class="font-semibold">{{ $item }}</p>
          <p class="text-sm text-slate-600 mt-2">লোকেশন অনুযায়ী চাকরি দেখুন।</p>
        </div>
      @endforeach
    </div>
  </section>

  <section class="py-10">
    <div class="bg-white border border-line rounded-3xl p-6">
      <h3 class="font-display text-xl font-semibold">চাকরি পোর্টাল সম্পর্কে</h3>
      <p class="text-sm text-slate-600 mt-3">
        এই পোর্টালে নিয়োগদাতা ও চাকরি প্রার্থী একসাথে যুক্ত হতে পারেন। ফিল্টার, CV আপলোড এবং দ্রুত আবেদন
        ব্যবস্থার মাধ্যমে সময় বাঁচে এবং সঠিক ম্যাচিং হয়।
      </p>
      <div class="mt-4 grid gap-3 md:grid-cols-2">
        <div class="bg-mist rounded-2xl p-4">
          <p class="font-semibold">স্বচ্ছ নিয়োগ</p>
          <p class="text-sm text-slate-600 mt-1">সব পোস্টে ভেরিফায়েড তথ্য থাকে।</p>
        </div>
        <div class="bg-mist rounded-2xl p-4">
          <p class="font-semibold">দ্রুত আবেদন</p>
          <p class="text-sm text-slate-600 mt-1">এক ক্লিকে আবেদন পাঠানো যায়।</p>
        </div>
      </div>
    </div>
  </section>

  <section class="py-10">
    <h2 class="font-display text-2xl font-semibold mb-6">FAQ</h2>
    <div class="grid gap-4 md:grid-cols-2">
      <div class="bg-white border border-line rounded-2xl p-5">
        <h3 class="font-semibold">চাকরির জন্য কি ফি লাগে?</h3>
        <p class="text-sm text-slate-600 mt-2">না, সাধারণভাবে আবেদন সম্পূর্ণ ফ্রি।</p>
      </div>
      <div class="bg-white border border-line rounded-2xl p-5">
        <h3 class="font-semibold">কোন ধরনের চাকরি পাওয়া যায়?</h3>
        <p class="text-sm text-slate-600 mt-2">ফুলটাইম, পার্টটাইম, ফ্রিল্যান্স সব ধরনের পোস্ট থাকে।</p>
      </div>
    </div>
  </section>
@endsection
