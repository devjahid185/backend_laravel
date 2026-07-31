<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$admin = App\Models\Admin::query()->first();
if (! $admin) {
    fwrite(STDOUT, "NO_ADMIN\n");
    exit(0);
}

$token = $admin->createToken('admin-panel', ['admin'])->plainTextToken;
$accessToken = Laravel\Sanctum\PersonalAccessToken::findToken($token);
$tokenable = $accessToken?->tokenable;

fwrite(STDOUT, "TOKEN={$token}\n");
fwrite(STDOUT, "TOKENABLE_CLASS=" . ($tokenable ? get_class($tokenable) : 'null') . "\n");
fwrite(STDOUT, "TOKENABLE_ID=" . ($tokenable?->id ?? 'null') . "\n");
