<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
foreach (App\Models\User::where('role', 'admin')->get() as $u) {
    echo "id={$u->id} username={$u->username} email={$u->email} status={$u->status} must_change_password={$u->must_change_password}\n";
}
