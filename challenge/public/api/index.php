<?php

use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../../vendor/autoload.php';

// DIC
$container = new Container();
$container->set('redisClient', function () {
    return new Predis\Client(['host' => 'redis']);
});

// Helper function to generate UUID to be used for Session Cookies.
function guidv4() {
    // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
    $data = random_bytes(16);
    assert(strlen($data) == 16);

    // Set version to 0100
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    // Set bits 6-7 to 10
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    // Output the 36 character UUID.
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// Generate and Set Session_id cookie
if(!isset($_COOKIE['app_session_id'])) {
    $expires = time() + 60 * 60 * 24 * 30; // 30 days.
    $cookieValue = guidv4();
    setcookie('app_session_id', $cookieValue, $expires, '/');
    $_COOKIE['app_session_id'] = $cookieValue;

}


AppFactory::setContainer($container);
$app = AppFactory::create();
$app->setBasePath('/api');
$app->addRoutingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

date_default_timezone_set('Asia/Singapore');




/**
 * TODO
 * Here you can write your own API endpoints.
 * You can use Redis and/or cookies for data persistence.
 *
 * Find below an example of a GET endpoint that uses redis to temporarily store a name,
 * and cookies to keep track of an event date and time.
 */

// $app->get('/hello/{name}', function (Request $request, Response $response, $args) {

//     // Redis usage example:
//     /** @var \Predis\Client $redisClient */
//     $redisClient = $this->get('redisClient');
//     $oldName = $redisClient->get('name');
//     if (is_string($oldName)) {
//         $name = $oldName;
//     } else {
//         $redisClient->set('name', $args['name'], 'EX', 10);
//         $name = $args['name'];
//     }

//     // Setting a cookie example:
//     $cookieValue = '';
//     if (empty($_COOKIE["FirstSalutationTime"])) {
//         $cookieName = "FirstSalutationTime";
//         $cookieValue = (string)time();
//         $expires = time();  // 30 days.
//         setcookie($cookieName, $cookieValue, $expires, '/');
//     }

//     // Response example:
//     $response->getBody()->write(json_encode([
//         'name' => $name,
//         'salutation' => "Hello, $name!",
//         'first_salutation_time' => $_COOKIE["FirstSalutationTime"] ?? $cookieValue,
//     ], JSON_THROW_ON_ERROR));

//     return $response->withHeader('Content-Type', 'application/json');
// });


// Declare endpoint for Setting and Getting startReading time.
$app->get('/reading/start',function(Request $request , Response $response){


    $redisClient = $this->get('redisClient');
    $redisKey = $_COOKIE['app_session_id'].'_startRead';
    $startReadingTime = $redisClient->get($redisKey);
    if (!$startReadingTime) {
        $dateTime = date('m/d/Y h:i:s a', time());
        $redisClient->set($redisKey, $dateTime, 'EX', 30*24*3600);
        $startReadingTime = $dateTime;
    }
    $response->getBody()->write(json_encode([
        'startReadingTime' => $startReadingTime,
    ], JSON_THROW_ON_ERROR));

    return $response->withHeader('Content-Type', 'application/json');
});

// Declare endpoint for Setting and Getting endReading time.
$app->get('/reading/end',function(Request $request , Response $response){

    $redisClient = $this->get('redisClient');
    $redisKey = $_COOKIE['app_session_id'].'_endRead';
    $endReadingTime = $redisClient->get($redisKey);
    if (!$endReadingTime) {
        $dateTime = date('m/d/Y h:i:s a', time());
        $redisClient->set($redisKey, $dateTime, 'EX', 30*24*3600);
        $endReadingTime = $dateTime;
    }
    $response->getBody()->write(json_encode([
        'endReadingTime' => $endReadingTime,
    ], JSON_THROW_ON_ERROR));

    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();
