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
      <p class="text-slate-600 mt-3">Effective date: September 24, 2026</p>
    </div>
  </section>

  <section class="pb-12">
    <div class="bg-white border border-line rounded-3xl p-6 space-y-6 text-sm text-slate-700 leading-7">
      <p>
        This Privacy Policy explains how ভোলাবাসী / Bholavashi (“we”, “our”, “us”), operated by Sohoj IT,
        collects, uses, shares, stores, and protects information when you use our website, mobile app and
        related local digital services.
      </p>
      <p>
        This policy is designed to match our Google Play Data safety disclosures. If you do not use a feature,
        data related to that feature may not be collected from you.
      </p>

      <div>
        <h2 class="font-semibold text-ink">1. Information We Collect</h2>
        <p class="mt-2">We collect information you provide directly and information generated through your use of the Services.</p>
        <ul class="mt-3 list-disc pl-5 space-y-2">
          <li><span class="font-semibold">Account information:</span> name, phone number, email address, profile photo, address details, OTP verification and Google sign-in identifiers.</li>
          <li><span class="font-semibold">Order and service information:</span> food and medicine orders, saved delivery addresses, support tickets, reviews, booking/listing details and transaction status.</li>
          <li><span class="font-semibold">Payment and financial records:</span> item totals, delivery fees, discounts, payment method, cash-on-delivery status, manual bKash/Nagad transaction ID, payment proof and settlement status.</li>
          <li><span class="font-semibold">Health-related service information:</span> medicine order details, prescription-related notes, blood donor/request details, doctor appointment details and similar user-provided information.</li>
          <li><span class="font-semibold">Location data:</span> current or selected delivery location, restaurant location, rider location during active delivery, route visibility and distance calculation data.</li>
          <li><span class="font-semibold">Device, notification and usage data:</span> device token, app version, IP-based security logs, basic technical logs, app interactions, searches, cart/order actions and SDK events where enabled.</li>
          <li><span class="font-semibold">Media, files and documents:</span> profile photos, restaurant/food images, KYC documents, NID/driving license/vehicle documents, support attachments, payment proof or delivery proof that users choose to upload.</li>
        </ul>
      </div>

      <div>
        <h2 class="font-semibold text-ink">2. How We Use Information</h2>
        <ul class="mt-3 list-disc pl-5 space-y-2">
          <li>Create and secure accounts, verify OTP/Google login and manage active devices.</li>
          <li>Process food/medicine orders, calculate delivery charges, apply discounts and handle order/payment status.</li>
          <li>Assign riders, show delivery route/location, manage restaurant owner/rider workflows and provide live order tracking.</li>
          <li>Support health-related service requests, listings, bookings, reviews, support tickets and user-submitted content.</li>
          <li>Send order, support, security, rider and service-related notifications.</li>
          <li>Prevent fraud, abuse, unsafe behavior and platform misuse, and improve app reliability.</li>
        </ul>
        <p class="mt-3">We do not sell personal location data or personal information.</p>
      </div>

      <div>
        <h2 class="font-semibold text-ink">3. Legal Bases (where applicable)</h2>
        <p class="mt-2">We process data to provide requested services, comply with legal obligations, and pursue legitimate interests such as security, fraud prevention, settlement, dispute resolution and service improvement.</p>
      </div>

      <div>
        <h2 class="font-semibold text-ink">4. Sharing of Information</h2>
        <ul class="mt-3 list-disc pl-5 space-y-2">
          <li>With restaurants, riders, admins and support teams only as needed to complete orders, deliveries, support and safety workflows.</li>
          <li>With service providers who support hosting/database/media storage, Google Maps/routing, Firebase Cloud Messaging, Google sign-in, Meta App Events, SMS/email gateways, payment-related processing, analytics and security.</li>
          <li>With legal authorities if required by law or to protect rights and safety.</li>
        </ul>
        <p class="mt-3">We do not sell personal data or share data for unrelated third-party resale.</p>
      </div>

      <div>
        <h2 class="font-semibold text-ink">5. Permissions and Optional Access</h2>
        <p class="mt-2">The app may request notification permission for order updates, rider requests, support updates and service alerts.</p>
        <p class="mt-2">The app may request approximate or precise location permission when users select current location, delivery location, restaurant location, rider delivery routing or live tracking features.</p>
        <p class="mt-2">The app may allow users to choose images or files for profile photos, food/restaurant images, rider KYC, support attachments, payment proof or delivery proof.</p>
      </div>

      <div>
        <h2 class="font-semibold text-ink">6. Data We Do Not Collect From the Device</h2>
        <p class="mt-2">Based on the current app implementation, Bholavashi does not request or collect contacts, SMS/MMS message content, call logs, calendar data, microphone/audio recordings, installed apps list or web browsing history from the device.</p>
      </div>

      <div>
        <h2 class="font-semibold text-ink">7. Data Retention</h2>
        <p class="mt-2">We keep data only as long as needed for account management, service delivery, legal/accounting obligations, rider and restaurant settlement, safety, fraud prevention, dispute resolution and operational purposes.</p>
        <p class="mt-2">Some transaction, payout, fraud-prevention or legal records may be retained even after account deletion if required for legitimate business, tax, accounting, safety or legal reasons.</p>
      </div>

      <div>
        <h2 class="font-semibold text-ink">8. Your Choices & Rights</h2>
        <ul class="mt-3 list-disc pl-5 space-y-2">
          <li>Update profile information from the app settings.</li>
          <li>Control notification and location permissions through Android device settings.</li>
          <li>Request account deletion or data deletion from inside the app or at https://bholavashi.site/delete-account/.</li>
        </ul>
      </div>

      <div>
        <h2 class="font-semibold text-ink">9. Security</h2>
        <p class="mt-2">We use HTTPS for data in transit and reasonable administrative, technical, and physical safeguards to protect your data. No system is 100% secure.</p>
      </div>

      <div>
        <h2 class="font-semibold text-ink">10. Children’s Privacy</h2>
        <p class="mt-2">Our Services are not directed to children under 13. If you believe a child has provided us data, contact support.</p>
      </div>

      <div>
        <h2 class="font-semibold text-ink">11. Changes to This Policy</h2>
        <p class="mt-2">We may update this policy periodically. The updated date will be shown at the top of this page.</p>
      </div>

      <div>
        <h2 class="font-semibold text-ink">12. Contact Us</h2>
        <p class="mt-2">If you have privacy, support, account deletion or policy questions, contact: support@bholavashi.site</p>
      </div>
    </div>
  </section>
@endsection
