@extends('layouts.site')

@php
  $title = $post->title . ' | ভোলাবাসী';
  $description = $post->excerpt;
  $defaultCover = '/logo_bholavashi_landscape_size.png';
@endphp

@push('head')
  <link rel="canonical" href="{{ url('/updates/' . $post->slug) }}" />
  <meta property="og:image" content="{{ $post->cover_url ?: $defaultCover }}" />
  <meta name="twitter:image" content="{{ $post->cover_url ?: $defaultCover }}" />
  <script type="application/ld+json">
  {
    "@@context": "https://schema.org",
    "@@type": "BlogPosting",
    "headline": "{{ $post->title }}",
    "datePublished": "{{ optional($post->published_at)->toAtomString() }}",
    "author": { "@@type": "Organization", "name": "ভোলাবাসী" },
    "description": "{{ $post->excerpt }}",
    "mainEntityOfPage": "{{ url('/updates/' . $post->slug) }}",
    "image": "{{ $post->cover_url ?: $defaultCover }}"
  }
  </script>
@endpush

@section('content')
  <section class="pt-12 pb-8">
    <div class="max-w-3xl">
      <p class="text-xs text-slate-500">আপডেটস</p>
      <h1 class="font-display text-4xl font-bold mt-2">{{ $post->title }}</h1>
      <div class="mt-3 text-sm text-slate-500 flex items-center gap-2">
        <span>{{ optional($post->published_at)->format('Y-m-d') }}</span>
        <span>•</span>
        <span>ভোলাবাসী টিম</span>
      </div>
      <div class="mt-4 flex flex-wrap gap-2">
        @foreach(($post->tags ?? []) as $tag)
          <span class="rounded-full bg-mist px-3 py-1 text-xs text-slate-600">{{ $tag }}</span>
        @endforeach
      </div>
    </div>
  </section>

  <section class="pb-6">
    <div class="bg-white border border-line rounded-3xl overflow-hidden">
      <div class="aspect-[16/9] bg-mist flex items-center justify-center">
        <img src="{{ $post->cover_url ?: $defaultCover }}" alt="{{ $post->title }}" class="max-h-full max-w-full object-contain p-4" />
      </div>
      <div class="p-6">
        <p class="text-slate-700 leading-7">{!! nl2br(e($post->body)) !!}</p>
      </div>
    </div>
  </section>

  <section class="pb-10">
    <div class="bg-mist rounded-3xl p-6">
      <h3 class="font-display text-xl font-semibold">আপডেট সম্পর্কে</h3>
      <p class="text-sm text-slate-600 mt-3">
        ভোলাবাসীে নিয়মিত নতুন ফিচার, কমিউনিটি আপডেট এবং সেবা তথ্য যুক্ত করা হয়। সর্বশেষ আপডেট জানতে
        আপডেটস সেকশন নিয়মিত ভিজিট করুন।
      </p>
      <div class="mt-4">
        <a href="/updates" class="text-sm text-leaf font-medium">সব আপডেট দেখুন</a>
      </div>
    </div>
  </section>
@endsection
