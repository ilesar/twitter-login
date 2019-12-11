<?php

use Abraham\TwitterOAuth\TwitterOAuth;
use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require __DIR__ . '/vendor/autoload.php';

$consumerKey = getenv('TWITTER_CONSUMER_KEY');
$consumerSecret = getenv('TWITTER_CONSUMER_SECRET');
$oauthCallback = getenv('TWITTER_OAUTH_CALLBACK');

$app = AppFactory::create();

if (!isset($_SESSION)) {
    session_name('APHPSESSID');
    session_start();
}

$app->get('/twitter/login', function (Request $request, Response $response, $args) use ($consumerKey, $consumerSecret, $oauthCallback) {
    if (isset($_SESSION['access_token'])) {
        return $response
            ->withHeader('Location', '/twitter/id')
            ->withStatus(302);
    }

    $connection = new TwitterOAuth($consumerKey, $consumerSecret);
    $request_token = $connection->oauth('oauth/request_token', array('oauth_callback' => $oauthCallback));

    $_SESSION['oauth_token'] = $request_token['oauth_token'];
    $_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];

    $url = $connection->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token']));

    return $response
        ->withHeader('Location', $url)
        ->withStatus(302);
});

$app->get('/twitter/logout', function (Request $request, Response $response, $args) {
    unset($_SESSION['access_token']);

    return $response
        ->withHeader('Location', '/twitter/login')
        ->withStatus(302);
});

$app->get('/twitter/callback', function (Request $request, Response $response, $args) use ($consumerKey, $consumerSecret) {
    $request_token = [];
    $request_token['oauth_token'] = $_SESSION['oauth_token'];
    $request_token['oauth_token_secret'] = $_SESSION['oauth_token_secret'];

    if (isset($request->getQueryParams()['oauth_token']) && $request_token['oauth_token'] !== $request->getQueryParams()['oauth_token']) {
        // Abort! Something is wrong.

        return $response
            ->withHeader('Location', '/twitter/login')
            ->withStatus(302);
    }

    $connection = new TwitterOAuth($consumerKey, $consumerSecret, $request_token['oauth_token'], $request_token['oauth_token_secret']);

    $access_token = $connection->oauth('oauth/access_token', ['oauth_verifier' => $request->getQueryParams()['oauth_verifier']]);

    $_SESSION['access_token'] = $access_token;

    return $response
        ->withHeader('Location', '/twitter/id')
        ->withStatus(302);
});

$app->get('/twitter/id', function (Request $request, Response $response, $args) {
    if (!isset($_SESSION['access_token'])) {
        return $response
            ->withHeader('Location', '/twitter/login')
            ->withStatus(302);
    }

    $data = ['id' => $_SESSION['access_token']['user_id']];
    $payload = json_encode($data);

    $response->getBody()->write($payload);
    return $response
        ->withHeader('Content-Type', 'application/json');
});

$app->run();
