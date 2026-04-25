<?php

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__).'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->loadEnv(dirname(__DIR__).'/.env');

$env = 'dev';
$debug = true;

$kernel = new Kernel($env, $debug);
$request = Request::create('/sitemap.xml');
try {
    $response = $kernel->handle($request, 1, false);
    echo "Status code: " . $response->getStatusCode() . "\n";
    if ($response->getStatusCode() >= 500) {
        echo "Error response!\n";
        // To get the actual error, we can look at the log later
    }
} catch (\Throwable $e) {
    echo "Exception caught:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
