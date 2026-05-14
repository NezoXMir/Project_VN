<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Подтверждение email</title>
    <style>
        body { margin: 0; padding: 0; background: #f3f4f6; font-family: Arial, sans-serif; color: #1f2937; }
        .wrap { max-width: 520px; margin: 40px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
        .header { background: #4f46e5; padding: 28px 32px; text-align: center; }
        .header h1 { margin: 0; color: #fff; font-size: 20px; }
        .body { padding: 32px; }
        .greeting { font-size: 15px; margin-bottom: 16px; }
        .code-box { background: #f5f3ff; border: 2px dashed #a5b4fc; border-radius: 10px; text-align: center; padding: 20px 16px; margin: 24px 0; }
        .code-label { font-size: 12px; color: #6b7280; margin-bottom: 8px; }
        .code { font-size: 42px; font-weight: 700; letter-spacing: 10px; color: #4f46e5; font-variant-numeric: tabular-nums; }
        .code-expire { font-size: 11px; color: #9ca3af; margin-top: 8px; }
        .divider { display: flex; align-items: center; gap: 12px; margin: 24px 0; color: #9ca3af; font-size: 12px; }
        .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: #e5e7eb; }
        .btn { display: block; text-align: center; background: #4f46e5; color: #fff; text-decoration: none; padding: 13px 24px; border-radius: 8px; font-size: 15px; font-weight: 600; }
        .link-note { font-size: 11px; color: #9ca3af; margin-top: 10px; word-break: break-all; text-align: center; }
        .footer { padding: 20px 32px; border-top: 1px solid #f3f4f6; font-size: 11px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="header">
        <h1>🎯 Виртуальный наставник</h1>
    </div>
    <div class="body">
        <p class="greeting">Привет, <strong>{{ $user->name }}</strong>!</p>
        <p style="font-size:14px;color:#4b5563;margin-bottom:0">
            Вы зарегистрировались на платформе. Подтвердите адрес электронной почты
            одним из двух способов ниже.
        </p>

        {{-- Способ 1: код --}}
        <div class="code-box">
            <div class="code-label">Способ 1 — введите код на сайте</div>
            <div class="code">{{ $code }}</div>
            <div class="code-expire">Код действителен 30 минут</div>
        </div>

        {{-- Способ 2: ссылка --}}
        <div class="divider">или</div>
        <p style="font-size:13px;color:#6b7280;margin-bottom:12px;text-align:center">
            Способ 2 — нажмите кнопку ниже (ссылка действует 24 часа)
        </p>
        <a href="{{ $verifyLink }}" class="btn">Подтвердить email</a>
        <p class="link-note">{{ $verifyLink }}</p>
    </div>
    <div class="footer">
        Если вы не регистрировались — просто проигнорируйте это письмо.
    </div>
</div>
</body>
</html>
