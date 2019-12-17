<?php

use Abraham\TwitterOAuth\TwitterOAuth;
use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require __DIR__ . '/vendor/autoload.php';

$consumerKey = getenv('TWITTER_CONSUMER_KEY');
$consumerSecret = getenv('TWITTER_CONSUMER_SECRET');
$oauthCallback = getenv('TWITTER_OAUTH_CALLBACK');
$redirectUrl = getenv('TWITTER_REDIRECT_URL');

$app = AppFactory::create();

if (!isset($_SESSION)) {
    session_name('APHPSESSID');
    session_start();
}

function prepareAjaxResponse($response, $data) {
    $payload = json_encode($data);

    $response->getBody()->write($payload);
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withHeader('Access-Control-Allow-Credentials', 'true');
}

$app->get('/twitter/login', function (Request $request, Response $response, $args) use ($consumerKey, $consumerSecret, $oauthCallback, $redirectUrl) {
    if (isset($_SESSION['access_token'])) {
        return $response
            ->withHeader('Location', $redirectUrl)
            ->withStatus(302);
    }
    $connection = new TwitterOAuth($consumerKey, $consumerSecret);
    $request_token = $connection->oauth('oauth/request_token', array('oauth_callback' => $oauthCallback));

    $_SESSION['oauth_token'] = $request_token['oauth_token'];
    $_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];

    $url = $connection->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token']));

//    var_dump($url);die;
    return prepareAjaxResponse($response, ['url' => $url]);
//    return $response
//        ->withHeader('Location', $url)
//        ->withStatus(302);
});

$app->get('/twitter/logout', function (Request $request, Response $response, $args) {
    unset($_SESSION['access_token']);

    return prepareAjaxResponse($response, ['status' => 'ok']);
});

$app->get('/twitter/callback', function (Request $request, Response $response, $args) use ($consumerKey, $consumerSecret) {
    $request_token = [];
    $request_token['oauth_token'] = $_SESSION['oauth_token'];
    $request_token['oauth_token_secret'] = $_SESSION['oauth_token_secret'];



    if (isset($request->getQueryParams()['oauth_token']) && $request_token['oauth_token'] !== $request->getQueryParams()['oauth_token']) {
        return $response
            ->withHeader('Location', '/twitter/login')
            ->withStatus(302);
    }

    $connection = new TwitterOAuth($consumerKey, $consumerSecret, $request_token['oauth_token'], $request_token['oauth_token_secret']);

    $access_token = $connection->oauth('oauth/access_token', ['oauth_verifier' => $request->getQueryParams()['oauth_verifier']]);

    $_SESSION['access_token'] = $access_token;

    return prepareAjaxResponse($response, ['id' => $access_token['user_id']]);
});

$app->get('/twitter/id', function (Request $request, Response $response, $args) {
    $id = null;

    if (isset($_SESSION['access_token'])) {
        $id = $_SESSION['access_token']['user_id'];
    }

    return prepareAjaxResponse($response, ['id' => $id]);
});

$app->get('/twitter/post', function (Request $request, Response $response, $args) use ($consumerKey, $consumerSecret, $oauthCallback, $redirectUrl) {
    $request_token = [];
    $request_token['oauth_token'] = $_SESSION['oauth_token'];
    $request_token['oauth_token_secret'] = $_SESSION['oauth_token_secret'];

    if (isset($_SESSION['access_token']) === false) {
        return prepareAjaxResponse($response, ['status' => 'fail']);
    }

    $accessToken = $_SESSION['access_token']['oauth_token'];
    $accessTokenSecret = $_SESSION['access_token']['oauth_token_secret'];

    $connection = new TwitterOAuth($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret);
    $statues = $connection->post("statuses/update", ["status" => "I’ve voted, have you? Just voted for the best websites of the year on @Awwwards! For the chance to win 2 tickets to Awwwards Conference Amsterdam head this way: LINK  #AwwwardsSOTY"]);

    if ($connection->getLastHttpCode() == 200) {
        return prepareAjaxResponse($response, ['status' => 'ok']);
    }

    return prepareAjaxResponse($response, ['id' => 'fail']);
});

$app->run();
