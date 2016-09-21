<?php
require_once(__DIR__ . '/../tokens.php');
require_once __DIR__ . '/../include/google-api-php-client/vendor/autoload.php';
require_once __DIR__ . '/../include/google-api-php-client/examples/templates/base.php';

Flight::set('retry_url', MY_URL .'login');


function render_boilerplate() {
    Flight::render('head',
        array(
            'my_url' => MY_URL,
            'title' => _('WLAN at ') . PAGE_NAME,
        ),
        'head');
    Flight::render('foot',
        array(
            'privacy_url' => MY_URL . 'privacy/',
            'imprint_url' => IMPRINT_URL,
        ),
        'foot');
    Flight::render('back_to_code_widget',
        array(
            'retry_url' => Flight::get('retry_url'),
        ),
        'back_to_code_widget');
    Flight::render('access_code_widget',
        array(
            'codeurl' => MY_URL . 'access_code/',
        ),
        'access_code_widget');
}

echo "1<br>";
if (!$oauth_credentials = getOAuthCredentialsFile()) {
	echo "2<br>";
	echo $oauth_credentials."<br>";
	echo missingOAuth2CredentialsWarning();
	return;
}

function google_checkin(){
	echo "1<br>";
	if (!$oauth_credentials = getOAuthCredentialsFile()) {
		echo "2<br>";
		echo $oauth_credentials."<br>";
		echo missingOAuth2CredentialsWarning();
		return;
	}
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
	echo "1<br>";
	if (!$oauth_credentials = getOAuthCredentialsFile()) {
		echo "2<br>";
		echo $oauth_credentials."<br>";
		echo missingOAuth2CredentialsWarning();
		return;
	}
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

function login_success($redirect = True) {
    //  http://" . $gw_address . ":" . $gw_port . "/wifidog/auth?token=" . $token
    $token = make_token();
    $url = 'http://' . $_SESSION['gw_address'] . ':'
        . $_SESSION['gw_port'] . '/wifidog/auth?token=' . $token;
    if ($redirect) {
        Flight::redirect($url);
    } else {
        return $url;
    }
}

function handle_access_code() {

    render_boilerplate();
    $request = Flight::request();
    $code = $request->query->access_code;
    $code = strtolower(trim($code));

    if (empty($code)) {
        Flight::render('denied_code', array(
            'msg' => _('No access code sent.'),
        ));
    } else if ($code != ACCESS_CODE) {
        Flight::render('denied_code', array(
            'msg' => _('Wrong access code.'),
        ));
    } else {
        login_success();
    }
}

?>