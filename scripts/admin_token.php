<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$admin = App\Models\Admin::query()->first();
if (! $admin) {
    fwrite(STDOUT, "NO_ADMIN");
    exit(0);
}

$token = $admin->createToken('admin-panel', ['admin'])->plainTextToken;
fwrite(STDOUT, $token);
