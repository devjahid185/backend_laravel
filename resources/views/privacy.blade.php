@extends('layouts.site')

@php
  $title = 'Privacy Policy | ভোলাবাসী';
  $description = 'Privacy Policy for ভোলাবাসী covering web and mobile app data practices.';
@endphp

@section('content')
  <section class="pt-12 pb-8">
    <div class="max-w-3xl">
      <p class="text-xs text-slate-500">Legal</p>
      <h1 class="font-display text-4xl font-bold mt-2">Privacy Policy</h1>
      <p class="text-slate-600 mt-3">Effective date: {{ date('Y-m-d') }}</p>
    </div>
  </section>

  <section class="pb-12">
    <div class="bg-white border border-line rounded-3xl p-6 space-y-6 text-sm text-slate-700 leading-7">
      <p>
        This Privacy Policy explains how ভোলাবাসী (“we”, “our”, “us”) collects, uses, shares, and protects
        information when you use our website and mobile applications (collectively, the “Services”).
      </p>

      <div>
        <h2 class="font-semibold text-ink">1. Information We Collect</h2>
        <p class="mt-2">We collect information you provide directly and information generated through your use of the Services.</p>
        <ul class="mt-3 list-disc pl-5 space-y-2">
          <li><span class="font-semibold">Account information:</span> name, phone number, email address, password (hashed), profile photo.</li>
          <li><span class="font-semibold">Profile details:</span> address, district/upazila, service category, business or professional details.</li>
          <li><span class="font-semibold">User content:</span> posts, listings, reviews, ratings, comments, uploaded images/files (e.g., CVs).</li>
          <li><span class="font-semibold">Location data:</span> if you allow location access, we collect approximate or precise location to show nearby services.
              You can also enter location manually.</li>
          <li><span class="font-semibold">Device & usage data:</span> app version, device type, OS, IP address, browser type, pages viewed, actions taken.</li>
          <li><span class="font-semibold">Support communications:</span> messages sent to support, feedback, and attachments.</li>
        </ul>
      </div>

      <div>
        <h2 class="font-semibold text-ink">2. How We Use Information</h2>
        <ul class="mt-3 list-disc pl-5 space-y-2">
          <li>Provide and improve Services (search, listings, filters, recommendations).</li>
          <li>Verify accounts and prevent fraud or abuse.</li>
          <li>Enable communication (calls, messages) between users where applicable.</li>
          <li>Process postings, reviews, ratings, and marketplace listings.</li>
          <li>Send service-related notifications (important updates, responses).</li>
          <li>Maintain safety, moderation, and policy compliance.</li>
        </ul>
      </div>

      <div>
        <h2 class="font-semibold text-ink">3. Legal Bases (where applicable)</h2>
        <p class="mt-2">We process data to perform our contract with you, comply with legal obligations, and pursue legitimate interests like security and service improvement.</p>
      </div>

      <div>
        <h2 class="font-semibold text-ink">4. Sharing of Information</h2>
        <ul class="mt-3 list-disc pl-5 space-y-2">
          <li>With other users as part of your public listings or profiles (e.g., business name, services, contact info).</li>
          <li>With service providers who support hosting, analytics, email, and security.</li>
          <li>With legal authorities if required by law or to protect rights and safety.</li>
        </ul>
        <p class="mt-3">We do not sell personal data to third parties.</p>
      </div>

      <div>
        <h2 class="font-semibold text-ink">5. Data Retention</h2>
        <p class="mt-2">We keep your data as long as your account is active or as needed to provide the Services and comply with legal obligations.</p>
      </div>

      <div>
        <h2 class="font-semibold text-ink">6. Your Choices & Rights</h2>
        <ul class="mt-3 list-disc pl-5 space-y-2">
          <li>Update profile information from the app settings.</li>
          <li>Opt out of location access via device settings.</li>
          <li>Request account deletion by contacting support.</li>
        </ul>
      </div>

      <div>
        <h2 class="font-semibold text-ink">7. Security</h2>
        <p class="mt-2">We use reasonable administrative, technical, and physical safeguards to protect your data. No system is 100% secure.</p>
      </div>

      <div>
        <h2 class="font-semibold text-ink">8. Children’s Privacy</h2>
        <p class="mt-2">Our Services are not directed to children under 13. If you believe a child has provided us data, contact support.</p>
      </div>

      <div>
        <h2 class="font-semibold text-ink">9. Changes to This Policy</h2>
        <p class="mt-2">We may update this policy periodically. The updated date will be shown at the top of this page.</p>
      </div>

      <div>
        <h2 class="font-semibold text-ink">10. Contact Us</h2>
        <p class="mt-2">If you have questions, contact: support@volabashi.com</p>
      </div>
    </div>
  </section>
@endsection
