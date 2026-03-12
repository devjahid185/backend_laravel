@extends('layouts.site')

@php
  $title = 'হোটেল বুকিং | ভোলাবাসী';
  $description = 'জেলার হোটেল, রুম তথ্য, সুবিধা, রেটিং ও যোগাযোগ এক জায়গায়।';
@endphp

@push('head')
  <script type="application/ld+json">
  {
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
      {"@@type": "Question", "name": "হোটেল তালিকা কীভাবে আপডেট হয়?", "acceptedAnswer": {"@@type": "Answer", "text": "ভেরিফায়েড টিম নিয়মিত তথ্য আপডেট করে।"}},
      {"@@type": "Question", "name": "রেটিং কেন গুরুত্বপূর্ণ?", "acceptedAnswer": {"@@type": "Answer", "text": "সেবার মান ও সুবিধা বোঝার জন্য রেটিং কাজে লাগে।"}},
      {"@@type": "Question", "name": "দাম ও সুবিধা কি দেখা যাবে?", "acceptedAnswer": {"@@type": "Answer", "text": "প্রোফাইলে রুম ভাড়া ও সুবিধা উল্লেখ থাকে।"}}
    ]
  }
  </script>
@endpush

@section('content')
  <section class="pt-12 pb-10 grid gap-8 lg:grid-cols-[1.1fr_0.9fr] items-center">
    <div class="space-y-5">
      <p class="text-xs text-slate-500">হোটেল ও রুম বুকিং</p>
      <h1 class="font-display text-4xl font-bold">ভ্রমণে থাকার জায়গা খুঁজুন সহজে</h1>
      <p class="text-slate-600">দাম, সুবিধা, লোকেশন ও রেটিং অনুযায়ী হোটেল বাছাই করুন।</p>
      <div class="flex gap-3">
        <button class="rounded-full bg-ink text-white px-6 py-2 text-sm">হোটেল দেখুন</button>
        <button class="rounded-full border border-line px-6 py-2 text-sm">হোটেল যোগ</button>
      </div>
    </div>
    <div class="bg-white border border-line rounded-3xl p-6">
      <h3 class="font-semibold">হোটেল ফিচার</h3>
      <ul class="mt-4 space-y-2 text-sm text-slate-600">
        <li>রুম টাইপ ও ভাড়া</li>
        <li>ফ্যাসিলিটি ও রিভিউ</li>
        <li>লোকেশন ও কল অপশন</li>
      </ul>
    </div>
  </section>

  <section class="py-10">
    <h2 class="font-display text-2xl font-semibold mb-6">বিভাগ অনুযায়ী</h2>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      @foreach(['বাজেট হোটেল','ফ্যামিলি হোটেল','রিসোর্ট','গেস্ট হাউস','ব্যবসায়িক','লং-স্টে','সার্ভিস অ্যাপার্টমেন্ট','অন্যান্য'] as $item)
        <div class="bg-white border border-line rounded-2xl p-4">
          <p class="font-semibold">{{ $item }}</p>
          <p class="text-sm text-slate-600 mt-2">হোটেল সুবিধা তুলনা করুন।</p>
        </div>
      @endforeach
    </div>
  </section>

  <section class="py-10">
    <div class="bg-white border border-line rounded-3xl p-6">
      <h3 class="font-display text-xl font-semibold">হোটেল ডিরেক্টরি সম্পর্কে</h3>
      <p class="text-sm text-slate-600 mt-3">
        জেলার হোটেল তথ্য এক জায়গায় থাকায় ভ্রমণকারীরা দ্রুত সিদ্ধান্ত নিতে পারে। দাম, সুবিধা, লোকেশন ও
        রিভিউ দেখে বেছে নেওয়া যায়।
      </p>
      <div class="mt-4 grid gap-3 md:grid-cols-2">
        <div class="bg-mist rounded-2xl p-4">
          <p class="font-semibold">রুম টাইপ</p>
          <p class="text-sm text-slate-600 mt-1">ফ্যামিলি বা একক রুম অনুযায়ী তালিকা।</p>
        </div>
        <div class="bg-mist rounded-2xl p-4">
          <p class="font-semibold">লোকেশন সুবিধা</p>
          <p class="text-sm text-slate-600 mt-1">ম্যাপ ও যোগাযোগ তথ্য থাকে।</p>
        </div>
      </div>
    </div>
  </section>

  <section class="py-10">
    <h2 class="font-display text-2xl font-semibold mb-6">FAQ</h2>
    <div class="grid gap-4 md:grid-cols-2">
      <div class="bg-white border border-line rounded-2xl p-5">
        <h3 class="font-semibold">হোটেল বুকিং কীভাবে করবো?</h3>
        <p class="text-sm text-slate-600 mt-2">যোগাযোগ অপশন থেকে কল করে বুকিং করা যায়।</p>
      </div>
      <div class="bg-white border border-line rounded-2xl p-5">
        <h3 class="font-semibold">হোটেল যোগ করার শর্ত কী?</h3>
        <p class="text-sm text-slate-600 mt-2">সঠিক ঠিকানা ও যোগাযোগ তথ্য প্রয়োজন।</p>
      </div>
    </div>
  </section>
@endsection
