@extends('layouts.site')

@php
  $title = 'সার্ভিস ডিরেক্টরি | ভোলাবাসী';
  $description = 'দক্ষ কর্মী, টেকনিশিয়ান, হেল্পার, ডেলিভারি সহ আপনার জেলার সার্ভিস তালিকা এক জায়গায়।';
@endphp

@push('head')
  <script type="application/ld+json">
  {
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
      {"@@type": "Question", "name": "সার্ভিস তালিকা কীভাবে কাজ করে?", "acceptedAnswer": {"@@type": "Answer", "text": "ক্যাটাগরি ও লোকেশন অনুযায়ী কর্মী দেখানো হয়, প্রোফাইল দেখে সরাসরি যোগাযোগ করা যায়।"}},
      {"@@type": "Question", "name": "কর্মী রেজিস্টার করা যাবে কীভাবে?", "acceptedAnswer": {"@@type": "Answer", "text": "রেজিস্টার বাটনে গিয়ে প্রয়োজনীয় তথ্য দিয়ে প্রোফাইল তৈরি করা যায়।"}},
      {"@@type": "Question", "name": "রিভিউ ও রেটিং কেন জরুরি?", "acceptedAnswer": {"@@type": "Answer", "text": "রিভিউয়ের মাধ্যমে সেবার মান বোঝা যায় এবং ভবিষ্যৎ ব্যবহারকারীরা সিদ্ধান্ত নিতে পারে।"}}
    ]
  }
  </script>
@endpush

@section('content')
  <section class="pt-12 pb-10 grid gap-8 lg:grid-cols-[1.1fr_0.9fr] items-center">
    <div class="space-y-5">
      <p class="text-xs text-slate-500">সার্ভিস ডিরেক্টরি</p>
      <h1 class="font-display text-4xl font-bold">জেলার সকল কর্মী ও সার্ভিস এক তালিকায়</h1>
      <p class="text-slate-600">প্লাম্বার, ইলেকট্রিশিয়ান, মিস্ত্রি, হেল্পার, ডেলিভারি—কাজ অনুযায়ী দ্রুত খুঁজুন এবং সরাসরি যোগাযোগ করুন।</p>
      <div class="flex gap-3">
        <button class="rounded-full bg-ink text-white px-6 py-2 text-sm">কর্মী খুঁজুন</button>
        <button class="rounded-full border border-line px-6 py-2 text-sm">কর্মী রেজিস্টার</button>
      </div>
    </div>
    <div class="bg-white border border-line rounded-3xl p-6">
      <h3 class="font-semibold">স্মার্ট ফিল্টার</h3>
      <ul class="mt-4 space-y-2 text-sm text-slate-600">
        <li>কাজের ধরন অনুযায়ী ফিল্টার</li>
        <li>লোকেশন ও সার্ভিস এরিয়া</li>
        <li>রেটিং ও অভিজ্ঞতা</li>
      </ul>
    </div>
  </section>

  <section class="py-10">
    <h2 class="font-display text-2xl font-semibold mb-6">জনপ্রিয় সার্ভিস ক্যাটাগরি</h2>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      @foreach(['ইলেকট্রিশিয়ান','প্লাম্বার','টাইলস/পেইন্ট','হোম কেয়ার','ডেলিভারি','সফটওয়্যার/আইটি','পরিষ্কার পরিচ্ছন্নতা','অন্যান্য'] as $item)
        <div class="bg-white border border-line rounded-2xl p-4">
          <p class="font-semibold">{{ $item }}</p>
          <p class="text-sm text-slate-600 mt-2">আপনার এলাকার টিম দ্রুত রেসপন্স করবে।</p>
        </div>
      @endforeach
    </div>
  </section>

  <section class="py-10">
    <div class="bg-mist rounded-3xl p-6">
      <h3 class="font-display text-xl font-semibold">সার্ভিস পেতে ৩ ধাপ</h3>
      <ol class="mt-4 space-y-2 text-sm text-slate-600">
        <li>১. ক্যাটাগরি বাছাই করুন</li>
        <li>২. প্রোফাইল দেখে যোগাযোগ করুন</li>
        <li>৩. কাজ শেষে রিভিউ দিন</li>
      </ol>
    </div>
  </section>

  <section class="py-10">
    <div class="bg-white border border-line rounded-3xl p-6">
      <h3 class="font-display text-xl font-semibold">সার্ভিস ডিরেক্টরি সম্পর্কে</h3>
      <p class="text-sm text-slate-600 mt-3">
        এই পেজে জেলার সব সার্ভিস প্রদানকারীর তথ্য এক জায়গায় পাওয়া যায়। প্রতিটি প্রোফাইলে কাজের ধরন,
        লোকেশন, অভিজ্ঞতা ও রেটিং দেখানো থাকে যাতে ব্যবহারকারী দ্রুত সিদ্ধান্ত নিতে পারে।
      </p>
      <div class="mt-4 grid gap-3 md:grid-cols-2">
        <div class="bg-mist rounded-2xl p-4">
          <p class="font-semibold">নিরাপদ যোগাযোগ</p>
          <p class="text-sm text-slate-600 mt-1">প্রোফাইল ভেরিফিকেশন ও রিপোর্টিং সুবিধা আছে।</p>
        </div>
        <div class="bg-mist rounded-2xl p-4">
          <p class="font-semibold">লোকেশন ভিত্তিক সার্ভিস</p>
          <p class="text-sm text-slate-600 mt-1">আপনার উপজেলার সেবা দ্রুত পাওয়া যায়।</p>
        </div>
      </div>
    </div>
  </section>

  <section class="py-10">
    <h2 class="font-display text-2xl font-semibold mb-6">FAQ</h2>
    <div class="grid gap-4 md:grid-cols-2">
      <div class="bg-white border border-line rounded-2xl p-5">
        <h3 class="font-semibold">সার্ভিস বুকিং কীভাবে করবো?</h3>
        <p class="text-sm text-slate-600 mt-2">প্রোফাইল থেকে কল/চ্যাট করে কাজের সময় ঠিক করা যায়।</p>
      </div>
      <div class="bg-white border border-line rounded-2xl p-5">
        <h3 class="font-semibold">ভুয়া প্রোফাইল দেখলে কী করবো?</h3>
        <p class="text-sm text-slate-600 mt-2">রিপোর্ট অপশন ব্যবহার করলে টিম যাচাই করে।</p>
      </div>
    </div>
  </section>
@endsection
