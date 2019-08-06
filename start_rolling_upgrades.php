<?php

# Change to your server, do not include https.
define('UBNTSERVER','ubnt.yourdomain.com:8443');
define('UBNTLOGIN','superadministratorusername'); # create a different user for this purpose
define('UBNTPASS','changethis');
define('COOKIESTORAGE', tempnam("/tmp", "ubnt_cronjobs_cookies"));

####
#
#		You dont need to change anything below, but feel free to do so and push back improvements.
#
####

$data = array("username" => UBNTLOGIN, "password" => UBNTPASS, "remember" => false, "strict" => true);
$data_string = json_encode($data);

$ch = curl_init('https://'.UBNTSERVER.'/api/login');
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); # self signed certificate? leave false.
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); # self signed certificate? leave false.
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIESTORAGE);
curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIESTORAGE);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Accept: application/json, text/plain, */*',
    'Content-Length: ' . strlen($data_string))
);
$response = curl_exec($ch);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$header = substr($response, 0, $header_size);
$body = substr($response, $header_size);
curl_close($ch);

#var_dump($header); # for debug, uncomment this.
#var_dump($response); # for debug, uncomment this.

$headers = explode("\n",$header);
foreach($headers AS $head) {
	if (strpos($head, 'csrf_token') !== false) {
		$cookies=explode(';',$head);
		$cookie=explode('=',$cookies[0]);
		$token=$cookie[1];
		echo 'Login successful, got token: '.$token."\n\n";
	}
}
if (!isset($token)) die('No token, will not proceed without a token.');


###
#	Fetch all sites.
###
$ch = curl_init('https://'.UBNTSERVER.'/api/self/sites');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIESTORAGE);
curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIESTORAGE);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Accept: application/json, text/plain, */*')
);

$response = curl_exec($ch);
#var_dump($response); # for debug uncomment this.
curl_close($ch);
$sites = json_decode($response,true);
#var_dump($sites); # for nice debug, uncomment this.

# Process each site
foreach($sites['data'] AS $site) {
	echo 'Enabeling rolling upgrade on ';
	echo $site['desc'];
	echo ' - ';
	echo $site['name'];
	echo "\n";
	
	# Send set-rollupgrade to one site at a time..
	$data = array("cmd" => "set-rollupgrade");
	$data_string = json_encode($data);

	$ch = curl_init('https://'.UBNTSERVER.'/api/s/'.$site['name'].'/cmd/devmgr');
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIESTORAGE);
	curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIESTORAGE);
																 
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(   
		'Content-Type: application/json',
		'Accept: application/json, text/plain, */*',
		'Content-Length: ' . strlen($data_string),
		'X-Csrf-Token: '.$token
		)
	);

	$response = curl_exec($ch);
	curl_close($ch);
	echo $response;
	echo "\n\n";
}

unlink(COOKIESTORAGE);