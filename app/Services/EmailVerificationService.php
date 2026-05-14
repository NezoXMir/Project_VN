<?php

namespace App\Services;

use App\Mail\EmailVerificationMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class EmailVerificationService
{
    /** Генерирует код, сохраняет в БД, отправляет письмо. */
    public function sendVerificationEmail(User $user): void
    {
        $code = $this->generateCode($user);
        $link = URL::temporarySignedRoute(
            'email.verify.link',
            now()->addHours(24),
            ['id' => $user->id],
        );

        Mail::to($user->email)->send(new EmailVerificationMail($user, $code, $link));
    }

    /** Проверяет код. Возвращает true и помечает email подтверждённым при совпадении. */
    public function verifyByCode(User $user, string $code): bool
    {
        if ($user->email_verification_code !== $code) {
            return false;
        }

        if ($user->email_verification_expires_at === null
            || $user->email_verification_expires_at->isPast()) {
            return false;
        }

        $this->markVerified($user);

        return true;
    }

    /** Помечает email подтверждённым (вызывается при переходе по ссылке). */
    public function verifyByLink(User $user): void
    {
        $this->markVerified($user);
    }

    private function generateCode(User $user): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'email_verification_code'        => $code,
            'email_verification_expires_at'  => now()->addMinutes(30),
        ]);

        return $code;
    }

    private function markVerified(User $user): void
    {
        $user->update([
            'email_verified_at'              => now(),
            'email_verification_code'        => null,
            'email_verification_expires_at'  => null,
        ]);
    }
}
