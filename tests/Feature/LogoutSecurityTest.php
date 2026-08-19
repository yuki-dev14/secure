<?php

namespace Tests\Feature;

use App\Http\Middleware\PreventBackHistory;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class LogoutSecurityTest extends TestCase
{
    public function test_prevent_back_history_middleware_attaches_anti_cache_headers(): void
    {
        $middleware = new PreventBackHistory();
        $request = Request::create('/portal/dashboard', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('Dashboard Content');
        });

        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);

        $this->assertEquals('no-cache', $response->headers->get('Pragma'));
        $this->assertEquals('Sun, 02 Jan 1990 00:00:00 GMT', $response->headers->get('Expires'));
    }
}
