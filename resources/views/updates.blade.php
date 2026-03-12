@extends('layouts.site')

@php
  $title = 'আপডেটস ও ব্লগ | ভোলাবাসী';
  $description = 'ভোলাবাসীের সর্বশেষ আপডেট, ফিচার ঘোষণা, কমিউনিটি নিউজ ও টিপস।';
  $defaultCover = '/logo_bholavashi_landscape_size.png';
@endphp

@push('head')
  <script type="application/ld+json">
  {
    "@@context": "https://schema.org",
    "@@type": "Blog",
    "name": "ভোলাবাসী আপডেটস",
    "url": "{{ url('/updates') }}",
    "inLanguage": "bn"
  }
  </script>
@endpush

@section('content')
  <section class="pt-12 pb-6">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
      <div>
        <p class="text-xs text-slate-500">আপডেটস ও ব্লগ</p>
        <h1 class="font-display text-4xl font-bold">নতুন ফিচার, ঘোষণা ও কমিউনিটি খবর</h1>
        <p class="text-slate-600 mt-3">অ্যাপের নতুন ফিচার, সেবা আপডেট এবং স্থানীয় গুরুত্বপূর্ণ খবর এখানেই পাবেন।</p>
      </div>
      <div class="bg-white border border-line rounded-2xl px-4 py-3 text-sm text-slate-600">
        সর্বশেষ আপডেট: {{ optional($posts->first())->published_at?->format('Y-m-d') ?? date('Y-m-d') }}
      </div>
    </div>
  </section>

  <section class="pb-10">
    <div class="grid gap-6 lg:grid-cols-[1.6fr_0.9fr]">
      <div class="space-y-6">
        @forelse($posts as $post)
          <article class="bg-white border border-line rounded-3xl overflow-hidden">
            <div class="aspect-[16/9] bg-mist flex items-center justify-center">
              <img src="{{ $post->cover_url ?: $defaultCover }}" alt="{{ $post->title }}" class="max-h-full max-w-full object-contain p-4" loading="lazy" />
            </div>
            <div class="p-6">
              <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                <span>{{ optional($post->published_at)->format('Y-m-d') }}</span>
                <span>•</span>
                <span>ভোলাবাসী টিম</span>
              </div>
              <h2 class="font-display text-2xl font-semibold mt-3">
                <a href="{{ url('/updates/' . $post->slug) }}" class="hover:text-leaf">{{ $post->title }}</a>
              </h2>
              <p class="text-slate-600 mt-3">{{ $post->excerpt }}</p>
              <div class="mt-4 flex flex-wrap gap-2">
                @foreach(($post->tags ?? []) as $tag)
                  <span class="rounded-full bg-mist px-3 py-1 text-xs text-slate-600">{{ $tag }}</span>
                @endforeach
              </div>
              <div class="mt-4">
                <a class="text-sm text-leaf font-medium" href="{{ url('/updates/' . $post->slug) }}">বিস্তারিত পড়ুন</a>
              </div>
            </div>
          </article>
        @empty
          <div class="bg-white border border-line rounded-3xl p-6">
            <p class="text-slate-600">এখনো কোনো আপডেট প্রকাশ হয়নি।</p>
          </div>
        @endforelse
      </div>
      <aside class="space-y-6">
        <div class="bg-white border border-line rounded-3xl p-6">
          <h3 class="font-display text-xl font-semibold">আপডেট সাবস্ক্রাইব</h3>
          <p class="text-sm text-slate-600 mt-2">নতুন আপডেট জানার জন্য ইমেইল দিন।</p>
          <div class="mt-4 space-y-3">
            <input class="w-full rounded-2xl border border-line px-4 py-3 text-sm" placeholder="আপনার ইমেইল" />
            <button class="w-full rounded-2xl bg-ink text-white py-3 text-sm">সাবস্ক্রাইব</button>
          </div>
        </div>
        <div class="bg-white border border-line rounded-3xl p-6">
          <h3 class="font-display text-xl font-semibold">জনপ্রিয় টপিক</h3>
          <div class="mt-4 flex flex-wrap gap-2">
            @php
              $allTags = $posts->pluck('tags')->flatten()->filter()->unique()->take(8);
            @endphp
            @forelse($allTags as $tag)
              <span class="rounded-full border border-line px-3 py-1 text-xs">{{ $tag }}</span>
            @empty
              <span class="text-xs text-slate-500">এখনো টপিক নেই</span>
            @endforelse
          </div>
        </div>
        <div class="bg-white border border-line rounded-3xl p-6">
          <h3 class="font-display text-xl font-semibold">কমিউনিটি নীতিমালা</h3>
          <p class="text-sm text-slate-600 mt-2">ভেরিফায়েড তথ্য শেয়ার করুন, স্প্যাম এড়াতে সাহায্য করুন।</p>
        </div>
      </aside>
    </div>
  </section>

  <section class="pb-10">
    <div class="bg-mist rounded-3xl p-6">
      <h3 class="font-display text-xl font-semibold">SEO সহায়ক লং-ফর্ম কনটেন্ট</h3>
      <p class="text-sm text-slate-600 mt-3">
        ভোলাবাসী আপডেট সেকশন নিয়মিত নতুন তথ্য প্রকাশ করে—যেমন নতুন ফিচার, স্থানীয় সেবা তালিকা,
        কমিউনিটি নির্দেশনা ও জরুরি নোটিশ। এই নিয়মিত কনটেন্ট সার্চ ইঞ্জিনে ওয়েবসাইটের র‍্যাঙ্ক বাড়াতে সাহায্য করে
        এবং ব্যবহারকারীদের দরকারি তথ্য দিতে সহায়তা করে।
      </p>
    </div>
  </section>

  <section class="pb-12">
    <h2 class="font-display text-2xl font-semibold mb-6">FAQ</h2>
    <div class="grid gap-4 md:grid-cols-2">
      <div class="bg-white border border-line rounded-2xl p-5">
        <h3 class="font-semibold">আপডেট কত ঘন ঘন দেয়া হয়?</h3>
        <p class="text-sm text-slate-600 mt-2">সাধারণত সপ্তাহে একাধিক আপডেট দেওয়া হয়।</p>
      </div>
      <div class="bg-white border border-line rounded-2xl p-5">
        <h3 class="font-semibold">নিউজ সাবমিট করা যাবে?</h3>
        <p class="text-sm text-slate-600 mt-2">ভেরিফায়েড কমিউনিটি রিপোর্টের মাধ্যমে তথ্য যুক্ত করা যায়।</p>
      </div>
    </div>
  </section>
@endsection
