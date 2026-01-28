<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeViewStructure extends Command
{
    protected $signature = 'make:view-structure';
    protected $description = 'Generate view folder & blade structure for ecommerce app';

    public function handle()
    {
        $base = resource_path('views');

        $structure = [
            'layouts' => [
                'app',
                'customer',
                'admin',
                'super-admin',
            ],
            'components' => [
                'navbar',
                'sidebar',
                'alert',
            ],
            'auth' => [
                'login',
                'register',
                'verify-email',
            ],
            'profile' => [
                'show',
                'edit',
                'change-password',
            ],
            'customer' => [
                'home',
                'products/index',
                'products/show',
                'cart/index',
                'checkout/index',
                'orders/index',
                'orders/show',
                'payment/upload',
                'wishlist/index',
                'reviews/create',
                'reviews/edit',
            ],
            'admin' => [
                'dashboard',
                'products/index',
                'products/create',
                'products/edit',
                'products/show',
                'products/images',
                'stock/index',
                'orders/index',
                'orders/show',
            ],
            'super-admin' => [
                'dashboard',
                'categories/index',
                'categories/create',
                'categories/edit',
                'users/index',
                'users/create',
                'users/edit',
                'payments/index',
                'payments/show',
                'orders/index',
                'orders/show',
                'reports/sales',
                'reports/products',
                'reports/customers',
                'reports/revenue',
            ],
        ];

        foreach ($structure as $folder => $files) {
            foreach ($files as $file) {
                $path = $base . '/' . $folder . '/' . $file . '.blade.php';
                File::ensureDirectoryExists(dirname($path));

                if (!File::exists($path)) {
                    File::put($path, "<!-- {$file}.blade.php -->");
                    $this->info("Created: {$path}");
                }
            }
        }

        $this->info('✅ View structure generated successfully!');
    }
}
