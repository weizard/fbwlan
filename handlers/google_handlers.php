<?php
require_once(__DIR__ . '/../tokens.php');
require_once __DIR__ . '/../include/google-api-php-client/vendor/autoload.php';
require_once __DIR__ . '/../include/google-api-php-client/examples/templates/base.php';

if (!$oauth_credentials = getOAuthCredentialsFile()) {
  echo missingOAuth2CredentialsWarning();
  return;
}

function google_checkin(){
	$client = new Google_Client();
	$client->setAuthConfig($oauth_credentials);
	$client->setScopes('email');
	if (isset($_GET['code'])) {
	  $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
	  $client->setAccessToken($token);

	  // store in the session also
	  $_SESSION['id_token_token'] = $token;

	  // redirect back to the example
	  header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
	}
	if ($client->getAccessToken()) {
	  $token_data = $client->verifyIdToken();
	Flight::render('googlecheckin',
		array(
			'loginurl' => login_success(False),
			)
	);
	}
}

function google_login(){
	// google login 
	$redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . "/googlecheckin.php"; 
	$client = new Google_Client();
	$client->setAuthConfig($oauth_credentials);
	$client->setRedirectUri($redirect_uri);
	$client->setScopes('email');
	if (
	  !empty($_SESSION['id_token_token'])
	  && isset($_SESSION['id_token_token']['id_token'])
	) {
	  $client->setAccessToken($_SESSION['id_token_token']);
	} else {
	  $authUrl = $client->createAuthUrl();
	}
    $google_login_url = $authUrl;
    $code_login_url = MY_URL . 'access_code/';
    Flight::render('google_login', array(
        'googleurl' => $google_login_url,
        'codeurl' =>  $code_login_url
        ));
}

// function login_success($redirect = True) {
//     //  http://" . $gw_address . ":" . $gw_port . "/wifidog/auth?token=" . $token
//     $token = make_token();
//     $url = 'http://' . $_SESSION['gw_address'] . ':'
//         . $_SESSION['gw_port'] . '/wifidog/auth?token=' . $token;
//     if ($redirect) {
//         Flight::redirect($url);
//     } else {
//         return $url;
//     }
// }

?>