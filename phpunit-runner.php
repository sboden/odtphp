<?php
use PHPUnit\TextUI\Configuration\Configuration;
use PHPUnit\TextUI\TestRunner;

require_once 'vendor/autoload.php';

// Custom error handler to suppress specific warnings
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // List of patterns to ignore
    $ignoredPatterns = [
        '/Class .* does not extend PHPUnit\\\\Framework\\\\TestCase/',
        '/Class .* cannot be found/',
        '/vendor\/phpunit/',
        '/vendor\/scrutinizer/',
        '/vendor\/symfony/'
    ];

    foreach ($ignoredPatterns as $pattern) {
        if (preg_match($pattern, $errstr)) {
            return true; // Suppress the warning
        }
    }

    // For other errors, use the default error handler
    return false;
});

// Run PHPUnit
$arguments = $_SERVER['argv'];
array_shift($arguments); // Remove script name

$configuration = (new Configuration())->load($arguments);
$testRunner = new TestRunner();
$result = $testRunner->run($configuration);

exit($result->wasSuccessful() ? 0 : 1);
