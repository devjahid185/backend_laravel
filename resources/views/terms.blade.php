@extends('layouts.site')

@php
  $title = 'Terms of Service | ভোলাবাসী';
  $description = 'Terms of Service for ভোলাবাসী website and mobile application.';
@endphp

@section('content')
  <section class="pt-12 pb-8">
    <div class="max-w-3xl">
      <p class="text-xs text-slate-500">Legal</p>
      <h1 class="font-display text-4xl font-bold mt-2">Terms of Service</h1>
      <p class="text-slate-600 mt-3">Effective date: {{ date('Y-m-d') }}</p>
    </div>
  </section>

  <section class="pb-12">
    <div class="bg-white border border-line rounded-3xl p-6 space-y-6 text-sm text-slate-700 leading-7">
      <p>
        These Terms govern your use of ভোলাবাসী (“Services”). By using the Services, you agree to these Terms.
      </p>

      <div>
        <h2 class="font-semibold text-ink">1. Eligibility</h2>
        <p class="mt-2">You must provide accurate information and comply with local laws while using the Services.</p>
      </div>

      <div>
        <h2 class="font-semibold text-ink">2. User Content</h2>
        <p class="mt-2">You are responsible for the content you post, including listings, reviews, and messages. Content must be truthful and lawful.</p>
      </div>

      <div>
        <h2 class="font-semibold text-ink">3. Prohibited Activities</h2>
        <ul class="mt-3 list-disc pl-5 space-y-2">
          <li>Fraud, misleading information, or impersonation.</li>
          <li>Spam, harassment, or abusive behavior.</li>
          <li>Posting illegal, harmful, or infringing content.</li>
        </ul>
      </div>

      <div>
        <h2 class="font-semibold text-ink">4. Listings & Transactions</h2>
        <p class="mt-2">We provide a platform only. We do not guarantee the quality of services or products. Users are responsible for their own transactions.</p>
      </div>

      <div>
        <h2 class="font-semibold text-ink">5. Moderation</h2>
        <p class="mt-2">We may remove content or suspend accounts that violate these Terms or our policies.</p>
      </div>

      <div>
        <h2 class="font-semibold text-ink">6. Limitation of Liability</h2>
        <p class="mt-2">We are not liable for indirect, incidental, or consequential damages from use of the Services.</p>
      </div>

      <div>
        <h2 class="font-semibold text-ink">7. Termination</h2>
        <p class="mt-2">You may stop using the Services at any time. We may suspend or terminate access for violations.</p>
      </div>

      <div>
        <h2 class="font-semibold text-ink">8. Changes</h2>
        <p class="mt-2">We may update these Terms. Continued use means you accept the updated Terms.</p>
      </div>

      <div>
        <h2 class="font-semibold text-ink">9. Contact</h2>
        <p class="mt-2">Questions? Email support@volabashi.com</p>
      </div>
    </div>
  </section>
@endsection
