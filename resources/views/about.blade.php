@extends('layouts.site')

@php
  $title = 'About | ভোলাবাসী';
  $description = 'Learn about ভোলাবাসী, our mission, team, and how we help people in Bhola access trusted local services.';
@endphp

@section('content')
  <section class="pt-12 pb-8">
    <div class="max-w-3xl">
      <p class="text-xs text-slate-500">About</p>
      <h1 class="font-display text-4xl font-bold mt-2">ভোলাবাসী</h1>
      <p class="text-slate-600 mt-3">A community-first platform for Bhola that connects people with trusted local services, listings, and updates.</p>
    </div>
  </section>

  <section class="pb-12">
    <div class="bg-white border border-line rounded-3xl p-6 space-y-8 text-sm text-slate-700 leading-7">
      <div>
        <h2 class="font-semibold text-ink">Mission</h2>
        <p class="mt-2">
          Our mission is to make reliable local information and services in Bhola accessible, transparent, and easy to use.
          We want every resident to find services, jobs, healthcare, education, and community updates in one trusted place.
        </p>
      </div>

      <div>
        <h2 class="font-semibold text-ink">Vision</h2>
        <p class="mt-2">Build a trusted digital backbone for Bhola—where people, businesses, and institutions can connect responsibly.</p>
      </div>

      <div>
        <h2 class="font-semibold text-ink">What We Provide</h2>
        <ul class="mt-3 list-disc pl-5 space-y-2">
          <li>Local services directory (workers, professionals, emergency support).</li>
          <li>Marketplace for local buying and selling with safety features.</li>
          <li>Jobs and CV system for employers and job seekers.</li>
          <li>Healthcare: doctors, hospitals, diagnostics, and reviews.</li>
          <li>Education: schools, colleges, madrasas, coaching, and updates.</li>
          <li>Hotels, restaurants, and property listings.</li>
          <li>Community announcements, verified updates, and reporting.</li>
        </ul>
      </div>

      <div>
        <h2 class="font-semibold text-ink">Trust & Safety</h2>
        <p class="mt-2">
          We use verification, moderation, and community reporting to keep the platform safe. Reviews and ratings help users
          make informed decisions.
        </p>
      </div>

      <div>
        <h2 class="font-semibold text-ink">Our Team</h2>
        <p class="mt-2">
          ভোলাবাসী is built by a small, focused team with local insight. We collaborate with community leaders, service
          providers, and volunteers to keep information accurate and useful.
        </p>
        <ul class="mt-3 list-disc pl-5 space-y-2">
          <li>Product & Community: user needs, local partnerships, feedback loops.</li>
          <li>Engineering: platform stability, security, and performance.</li>
          <li>Data & Verification: content accuracy, updates, and moderation.</li>
          <li>Support: user assistance and issue resolution.</li>
        </ul>
      </div>

      <div>
        <h2 class="font-semibold text-ink">Contact</h2>
        <p class="mt-2">Email: support@volabashi.com</p>
      </div>
    </div>
  </section>
@endsection