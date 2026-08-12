<?php

namespace App\Services;

use App\Models\User;

class AuthEmailService
{
    public function __construct(private readonly EmailService $email)
    {
    }

    public function sendWelcome(User $user): void
    {
        if (! $user->email) {
            return;
        }

        $name = e($user->name ?: 'প্রিয় ব্যবহারকারী');
        $html = $this->layout(
            title: 'ভোলাবাসীতে স্বাগতম',
            intro: "হ্যালো {$name},",
            body: 'আপনার ভোলাবাসী অ্যাকাউন্ট সফলভাবে তৈরি হয়েছে। এখন থেকে একই অ্যাপের মাধ্যমে এলাকার প্রয়োজনীয় সার্ভিস, খাবার অর্ডার, নোটিফিকেশন এবং আপনার প্রোফাইল সহজে ব্যবহার করতে পারবেন।',
            highlight: 'আপনার অ্যাকাউন্ট এখন ব্যবহার করার জন্য প্রস্তুত।',
            footer: 'আপনি যদি এই অ্যাকাউন্ট তৈরি না করে থাকেন, অনুগ্রহ করে আমাদের সাপোর্ট টিমের সাথে যোগাযোগ করুন।'
        );

        $this->email->sendHtml($user->email, 'ভোলাবাসীতে স্বাগতম', $html);
    }

    public function sendPasswordResetCode(User $user, string $code): void
    {
        $name = e($user->name ?: 'প্রিয় ব্যবহারকারী');
        $safeCode = e($code);
        $html = $this->layout(
            title: 'পাসওয়ার্ড রিসেট কোড',
            intro: "হ্যালো {$name},",
            body: 'আপনার ভোলাবাসী অ্যাকাউন্টের পাসওয়ার্ড রিসেট করার জন্য নিচের যাচাইকরণ কোডটি ব্যবহার করুন।',
            highlight: $safeCode,
            footer: 'এই কোডটি ১৫ মিনিট পর্যন্ত কার্যকর থাকবে। নিরাপত্তার জন্য কোডটি কাউকে জানাবেন না।'
        );

        $this->email->sendHtml($user->email, 'ভোলাবাসী পাসওয়ার্ড রিসেট কোড', $html);
    }

    private function layout(string $title, string $intro, string $body, string $highlight, string $footer): string
    {
        return <<<HTML
<!doctype html>
<html lang="bn">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$title}</title>
</head>
<body style="margin:0;background:#f5f7fb;font-family:Arial,'Noto Sans Bengali',sans-serif;color:#172033;">
  <div style="padding:32px 16px;">
    <div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e8edf5;">
      <div style="background:#ee0012;padding:24px 28px;color:#ffffff;">
        <div style="font-size:24px;font-weight:800;line-height:1.3;">Bholavashi</div>
        <div style="font-size:14px;opacity:.9;margin-top:4px;">আপনার এলাকার ডিজিটাল সেবা প্ল্যাটফর্ম</div>
      </div>
      <div style="padding:28px;">
        <h1 style="margin:0 0 16px;font-size:24px;line-height:1.35;color:#111827;">{$title}</h1>
        <p style="margin:0 0 12px;font-size:16px;line-height:1.75;">{$intro}</p>
        <p style="margin:0 0 22px;font-size:15px;line-height:1.8;color:#4b5563;">{$body}</p>
        <div style="border-radius:14px;background:#fff3f4;border:1px solid #ffd3d7;padding:18px 20px;text-align:center;font-size:26px;font-weight:800;letter-spacing:4px;color:#cc0010;">
          {$highlight}
        </div>
        <p style="margin:22px 0 0;font-size:14px;line-height:1.8;color:#6b7280;">{$footer}</p>
      </div>
      <div style="padding:18px 28px;background:#f9fafb;color:#7b8494;font-size:12px;line-height:1.6;">
        এই ইমেইলটি স্বয়ংক্রিয়ভাবে পাঠানো হয়েছে। অনুগ্রহ করে এই ইমেইলে রিপ্লাই করবেন না।
      </div>
    </div>
  </div>
</body>
</html>
HTML;
    }
}
