<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$routes = ['/', '/login', '/portal', '/uat_beneficiaries_sample.csv', '/logo.png'];

foreach ($routes as $route) {
    try {
        $request = Illuminate\Http\Request::create($route, 'GET');
        $response = $kernel->handle($request);
        echo "Route [$route] => Status: " . $response->getStatusCode() . "\n";
        if ($response->getStatusCode() >= 400) {
            $content = $response->getContent();
            preg_match('/<title>(.*?)<\/title>/i', $content, $matches);
            echo "   Error title: " . ($matches[1] ?? 'Unknown') . "\n";
        }
    } catch (\Throwable $e) {
        echo "Route [$route] => Exception: " . $e->getMessage() . "\n";
    }
}
