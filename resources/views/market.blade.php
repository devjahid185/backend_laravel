@extends('layouts.site')

@php
  $title = 'মার্কেটপ্লেস | ভোলাবাসী';
  $description = 'স্থানীয় পণ্য কেনা-বেচা, সেলার প্রোফাইল, নিরাপদ চ্যাট ও রিপোর্টিং সহ জেলা মার্কেটপ্লেস।';
@endphp

@push('head')
  <script type="application/ld+json">
  {
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
      {"@@type": "Question", "name": "পণ্য নিরাপদে কেনা-বেচা কীভাবে করবো?", "acceptedAnswer": {"@@type": "Answer", "text": "সেলার প্রোফাইল যাচাই করুন এবং ইন-অ্যাপ চ্যাটে কথা বলে সিদ্ধান্ত নিন।"}},
      {"@@type": "Question", "name": "রিপোর্ট সিস্টেম কী কাজ করে?", "acceptedAnswer": {"@@type": "Answer", "text": "ভুয়া বা সন্দেহজনক লিস্টিং রিপোর্ট করলে দ্রুত যাচাই হয়।"}},
      {"@@type": "Question", "name": "ছবি ও ডিটেইলস কতটা জরুরি?", "acceptedAnswer": {"@@type": "Answer", "text": "পরিষ্কার ছবি ও ডিটেইলস থাকলে দ্রুত সেল হয়।"}}
    ]
  }
  </script>
@endpush

@section('content')
  <section class="pt-12 pb-10 grid gap-8 lg:grid-cols-[1.1fr_0.9fr] items-center">
    <div class="space-y-5">
      <p class="text-xs text-slate-500">মার্কেটপ্লেস</p>
      <h1 class="font-display text-4xl font-bold">স্থানীয় কেনা-বেচা এখন আরও নিরাপদ</h1>
      <p class="text-slate-600">পণ্য ব্রাউজ করুন, সেলার ভেরিফাই করুন, ইন-অ্যাপ চ্যাটে কথা বলুন—সবকিছু নিরাপদে।</p>
      <div class="flex gap-3">
        <button class="rounded-full bg-ink text-white px-6 py-2 text-sm">পণ্য দেখুন</button>
        <button class="rounded-full border border-line px-6 py-2 text-sm">পোস্ট দিন</button>
      </div>
    </div>
    <div class="bg-white border border-line rounded-3xl p-6">
      <h3 class="font-semibold">মার্কেট ফিচার</h3>
      <ul class="mt-4 space-y-2 text-sm text-slate-600">
        <li>ক্যাটাগরি ও প্রাইস রেঞ্জ ফিল্টার</li>
        <li>ইমেজ গ্যালারি</li>
        <li>রিপোর্ট ও সেফটি টিপস</li>
      </ul>
    </div>
  </section>

  <section class="py-10">
    <h2 class="font-display text-2xl font-semibold mb-6">জনপ্রিয় ক্যাটাগরি</h2>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      @foreach(['ইলেকট্রনিক্স','মোবাইল','ফার্নিচার','বই/স্টেশনারি','গাড়ি/বাইক','গৃহস্থালি','ফ্যাশন','অন্যান্য'] as $item)
        <div class="bg-white border border-line rounded-2xl p-4">
          <p class="font-semibold">{{ $item }}</p>
          <p class="text-sm text-slate-600 mt-2">দামের তুলনা ও লোকেশন দেখুন।</p>
        </div>
      @endforeach
    </div>
  </section>

  <section class="py-10">
    <div class="bg-white border border-line rounded-3xl p-6">
      <h3 class="font-display text-xl font-semibold">মার্কেটপ্লেস সম্পর্কে</h3>
      <p class="text-sm text-slate-600 mt-3">
        জেলা মার্কেটপ্লেসে স্থানীয় ক্রেতা ও বিক্রেতা এক জায়গায় যুক্ত হয়। নিরাপদ যোগাযোগ, রেটিং ও রিপোর্টিং
        সিস্টেম থাকায় লেনদেন সহজ ও নির্ভরযোগ্য হয়।
      </p>
      <div class="mt-4 grid gap-3 md:grid-cols-2">
        <div class="bg-mist rounded-2xl p-4">
          <p class="font-semibold">লোকেশনভিত্তিক সার্চ</p>
          <p class="text-sm text-slate-600 mt-1">নিজের এলাকার পণ্য আগে দেখুন।</p>
        </div>
        <div class="bg-mist rounded-2xl p-4">
          <p class="font-semibold">সেলার যাচাই</p>
          <p class="text-sm text-slate-600 mt-1">ভেরিফাইড ব্যাজ ও রিভিউ সাহায্য করে।</p>
        </div>
      </div>
    </div>
  </section>

  <section class="py-10">
    <h2 class="font-display text-2xl font-semibold mb-6">FAQ</h2>
    <div class="grid gap-4 md:grid-cols-2">
      <div class="bg-white border border-line rounded-2xl p-5">
        <h3 class="font-semibold">পণ্য পোস্ট করতে কী লাগে?</h3>
        <p class="text-sm text-slate-600 mt-2">ছবি, দাম, অবস্থা ও লোকেশন দিলেই পোস্ট করা যায়।</p>
      </div>
      <div class="bg-white border border-line rounded-2xl p-5">
        <h3 class="font-semibold">সেলার যোগাযোগ কোথায়?</h3>
        <p class="text-sm text-slate-600 mt-2">প্রোফাইল থেকে কল/চ্যাট অপশন থাকবে।</p>
      </div>
    </div>
  </section>
@endsection
