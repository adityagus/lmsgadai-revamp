<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class HttpsUrlsTest extends TestCase
{
    public function test_https_site_renders_secure_assets_over_an_internal_http_connection(): void
    {
        $this->assertPageScheme('https');
    }

    public function test_local_http_site_keeps_working_without_tls(): void
    {
        $this->assertPageScheme('http');
    }

    private function assertPageScheme(string $scheme): void
    {
        config(['app.url' => $scheme.'://beladiri.test', 'app.asset_url' => null]);
        URL::forceScheme(null);
        URL::useAssetOrigin(null);
        $this->app->getProvider(AppServiceProvider::class)->boot();

        // Exercise real Vite tag generation without depending on a local build/HMR.
        $this->app->instance(Vite::class, new class extends Vite {
            public function isRunningHot()
            {
                return false;
            }

            protected function manifest($buildDirectory)
            {
                return ['resources/js/app.js' => [
                    'src' => 'resources/js/app.js',
                    'file' => 'assets/app-test.js',
                    'isEntry' => true,
                    'css' => ['assets/app-test.css'],
                ]];
            }
        });

        $this->get('http://beladiri.test/')
            ->assertOk()
            ->assertSee('href="'.$scheme.'://beladiri.test/assets/images/logos/logo-only.png"', false)
            ->assertSee('href="'.$scheme.'://beladiri.test/build/assets/app-test.css"', false)
            ->assertSee('src="'.$scheme.'://beladiri.test/build/assets/app-test.js"', false);

        $this->assertSame($scheme.'://beladiri.test/sign-in', url('/sign-in'));
    }
}
