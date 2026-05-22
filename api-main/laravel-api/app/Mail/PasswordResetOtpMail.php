<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $userName,
        public string $otp,
        public int $expiryMinutes
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Yono Todolist password reset code',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtml(),
        );
    }

    private function buildHtml(): string
    {
        $name = e($this->userName ?: 'there');
        $otp = e($this->otp);
        $expiryMinutes = e((string) $this->expiryMinutes);

        return <<<HTML
<div style="margin:0;padding:32px 16px;background:#f4f7fb;font-family:Arial,sans-serif;color:#14213d;">
  <div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:20px;padding:32px;border:1px solid #dbe4f0;">
    <p style="margin:0 0 12px;font-size:14px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#2563eb;">
      Password Reset
    </p>
    <h1 style="margin:0 0 14px;font-size:28px;line-height:1.2;color:#0f172a;">Hello {$name},</h1>
    <p style="margin:0 0 20px;font-size:16px;line-height:1.7;color:#334155;">
      Use the verification code below to reset your Yono Todolist account password.
    </p>
    <div style="margin:0 0 20px;padding:18px 20px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:16px;text-align:center;">
      <p style="margin:0 0 10px;font-size:13px;letter-spacing:0.12em;text-transform:uppercase;color:#1d4ed8;">
        Verification Code
      </p>
      <p style="margin:0;font-size:34px;font-weight:800;letter-spacing:0.5em;color:#0f172a;">{$otp}</p>
    </div>
    <p style="margin:0 0 12px;font-size:15px;line-height:1.6;color:#475569;">
      This code will expire in {$expiryMinutes} minutes. If you did not request a password reset, you can safely ignore this email.
    </p>
    <p style="margin:0;font-size:14px;line-height:1.6;color:#64748b;">
      For your security, only enter this code on the official reset password page.
    </p>
  </div>
</div>
HTML;
    }
}
