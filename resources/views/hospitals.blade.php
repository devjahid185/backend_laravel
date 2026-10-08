@extends('layouts.site')

@php
  $title = 'সেকশন আপডেট হচ্ছে | ভোলাবাসী';
  $description = 'এই সেকশনটি বর্তমানে ভোলাবাসী অ্যাপে প্রকাশিত নেই।';
@endphp

@push('head')
  <meta name="robots" content="noindex, nofollow" />
@endpush

@section('content')
  <section class="py-16">
    <div class="max-w-2xl bg-white border border-line rounded-3xl p-8">
      <p class="text-xs text-slate-500">ভোলাবাসী</p>
      <h1 class="font-display text-3xl font-bold mt-2">এই সেকশনটি বর্তমানে প্রকাশিত নেই</h1>
      <p class="text-slate-600 mt-3">
        Play policy review এবং content cleanup চলার কারণে এই তথ্যভিত্তিক সেকশনটি আপাতত অ্যাপ ও ওয়েবসাইটে দেখানো হচ্ছে না।
      </p>
      <a href="/" class="inline-flex mt-5 rounded-full bg-brand px-5 py-2 text-sm font-semibold text-white">হোমে ফিরে যান</a>
    </div>
  </section>
@endsection
