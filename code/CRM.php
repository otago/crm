<?php

namespace OP;

use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use Exception;


class CRM  {
    use Extensible;
    use Injectable;
    use Configurable;

	private static $connection;

	public static function request($webservice_url_str, $method = "GET", $param = array(), $extra_headers = array()) {
		if(!isset(static::$connection)) {
			static::$connection = CRMConnection::create();
		}

		if(!static::$connection) {
			throw new Exception('Could not connect to CRM');
		}

		$session = curl_init($webservice_url_str);
		$headers = array(
			"Content-Type: application/json",
			"Accept: application/json",
			"Authorization: Bearer " . static::$connection->getToken()
		);
		$headers = array_merge($headers, $extra_headers);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($session, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($session, CURLOPT_HTTPHEADER, $headers);
        //CWP proxy stuff
        if(Environment::getEnv('SS_OUTBOUND_PROXY') && Environment::getEnv('SS_OUTBOUND_PROXY_PORT')) {
            curl_setopt($session, CURLOPT_PROXY, Environment::getEnv('SS_OUTBOUND_PROXY'));
            curl_setopt($session, CURLOPT_PROXYPORT, Environment::getEnv('SS_OUTBOUND_PROXY_PORT'));
        }

		// REST
		switch(strtoupper($method)) {
			case "GET":
				curl_setopt($session, CURLOPT_HTTPGET, true);
				break;
			case "POST":
				curl_setopt($session, CURLOPT_POST, true);
				break;
			case "PATCH":
				curl_setopt($session, CURLOPT_CUSTOMREQUEST, "PATCH");
				break;
			case "DELETE":
				curl_setopt($session, CURLOPT_CUSTOMREQUEST, "DELETE");
				break;
			case "PUT":
				curl_setopt($session, CURLOPT_CUSTOMREQUEST, "PUT");
				break;
		}

		if (!empty($param)) {
			$data_string = json_encode($param);
			curl_setopt($session, CURLOPT_POSTFIELDS, $data_string);
		}

		$content = curl_exec($session);
		$code = curl_getinfo($session, CURLINFO_HTTP_CODE);
		curl_close($session);

		return new CRMResult($content, $code);
	}

}