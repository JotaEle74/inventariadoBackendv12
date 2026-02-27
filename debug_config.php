<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Foundation\Http\Kernel::class);
$kernel->bootstrap();
use App\Models\Inventariado\Configuracion;
print_r(Configuracion::all()->toArray());

// test property access on string
$str = '1';
try {
    var_dump(@$str->id);
} catch (\Throwable $e) {
    echo 'exception '.$e->getMessage()."\n";
}
