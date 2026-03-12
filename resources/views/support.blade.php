@extends('layouts.site')

@php
  $title = 'Contact & Support | ভোলাবাসী';
  $description = 'Get support, send feedback, or contact the ভোলাবাসী team.';
@endphp

@section('content')
  <section class="pt-12 pb-8">
    <div class="max-w-3xl">
      <p class="text-xs text-slate-500">Support</p>
      <h1 class="font-display text-4xl font-bold mt-2">Contact & Support</h1>
      <p class="text-slate-600 mt-3">We are here to help with account, listings, verification, or any service-related questions.</p>
    </div>
  </section>

  <section class="pb-12">
    <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
      <div class="bg-white border border-line rounded-3xl p-6 space-y-4 text-sm text-slate-700">
        <h2 class="font-semibold text-ink">Support Channels</h2>
        <ul class="list-disc pl-5 space-y-2">
          <li>Email: support@volabashi.com</li>
          <li>Phone: +880 1XXX-XXXXXX (9:00 AM - 6:00 PM)</li>
          <li>Office: District Information Service Center, Bhola</li>
        </ul>
        <div class="bg-mist rounded-2xl p-4 text-sm">
          <p class="font-semibold">Response Time</p>
          <p class="text-slate-600 mt-1">We usually respond within 24-48 hours on business days.</p>
        </div>
        <div class="bg-mist rounded-2xl p-4 text-sm">
          <p class="font-semibold">Report an Issue</p>
          <p class="text-slate-600 mt-1">Please include screenshots, listing links, and your account email if possible.</p>
        </div>
      </div>
      <div class="bg-white border border-line rounded-3xl p-6 text-sm text-slate-700">
        <h2 class="font-semibold text-ink">FAQ Quick Links</h2>
        <ul class="mt-3 space-y-2">
          <li>Account verification</li>
          <li>Posting listings</li>
          <li>Removing incorrect info</li>
          <li>Safety and reporting</li>
        </ul>
        <div class="mt-6">
          <h3 class="font-semibold text-ink">Community Guidelines</h3>
          <p class="text-slate-600 mt-2">Be respectful, avoid spam, and report misinformation to keep the platform safe.</p>
        </div>
      </div>
    </div>
  </section>
@endsection
