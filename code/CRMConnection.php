<?php

namespace OP;

use SilverStripe\Control\Director;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Config\Configurable;

use Exception;
use SilverStripe\Core\Environment;


class CRMConnection {
    use Extensible;
    use Injectable;
    use Configurable;

	private $access_token = '';

	private static $url = "https://login.microsoftonline.com/common/oauth2/token";

	public function __construct()
	{
		//$cache = JSONCache::getJSONCache('CRMConnection');
		//$cache->Data = $room_list_request->Raw();
		//$cache->Write();
		$this->getAccessToken();
	}

	public function getToken() {
		return $this->access_token;
	}

	private function getAccessToken() {
		$headers = array(
			"Content-Type: application/x-www-form-urlencoded",
			"Accept: application/json"
		);
		$data_string = http_build_query(
			array(
				"grant_type" => "password",
				"resource" => $this->getURL(),
				"client_secret" => $this->config()->client_secret,
				"client_id" => $this->config()->client_id,
				"username" => $this->config()->username,
				"password" => $this->config()->password
			)
		);
		$session = curl_init( $this->config()->url);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($session, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($session, CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($session, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($session, CURLOPT_POST, true);

        //CWP proxy stuff
        if(Environment::getEnv('SS_OUTBOUND_PROXY') && Environment::getEnv('SS_OUTBOUND_PROXY_PORT')) {
            curl_setopt($session, CURLOPT_PROXY, Environment::getEnv('SS_OUTBOUND_PROXY'));
            curl_setopt($session, CURLOPT_PROXYPORT, Environment::getEnv('SS_OUTBOUND_PROXY_PORT'));
        }

		$content = curl_exec($session);
		$code = curl_getinfo($session, CURLINFO_HTTP_CODE);

		if ($code !== 200) {
			throw new Exception('CRM did not return an access token. With code: '.$code );
		}
		$content = json_decode($content);
		if (!isset($content->access_token)) {
			throw new Exception('token invalid');
		}
		$this->access_token = $content->access_token;
	}


	public function getURL() {
		if (Director::isTest()) {
			return $this->config()->locationTest;
		} else if (Director::isDev()) {
			return $this->config()->locationDev;
		} else if (Director::isLive()) {
			return $this->config()->locationLive;
		}
		return '';
	}
}
