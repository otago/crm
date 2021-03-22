<?php

namespace OP;

use SilverStripe\Core\Extensible;
use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use Exception;
use Symfony\Component\Cache\Simple\FilesystemCache;
use SilverStripe\Dev\Debug;

class CRM
{
    use Injectable;
    use Configurable;

    // the token used to send and retrive data from the CRM API REST service
    private $access_token = '';

    /*
    * @config default headers used for communication
    */
    private static $headers = [
        "Content-Type" => "application/x-www-form-urlencoded",
        "Accept" => "application/json"
    ];

    /**
     * Builds the query used to fetch the token. is able to use
     * client_credentials or password authentication methods
     * 
     */
    public function __construct()
    {

        $cache = new FilesystemCache('OP');

        if ($cache->has($this->getCacheKey())) {
            $this->access_token = $cache->get($this->getCacheKey());
        } else {
            $this->access_token = $this->RequestAccessToken();
        }
    }

    /**
     * builds a unique key to store the token against based on the content of the connection details
     * 
     * @return string
     */
    public function getCacheKey()
    {
        return 'CRMConnection.token' . hash('md5', json_encode($this->CreateTokenHTTPQuery()));
    }


    /**
     * Builds the query used to fetch the token. is able to use
     *  client_credentials or password authentication methods
     * 
     * @return array 
     */
    protected function CreateTokenHTTPQuery()
    {
        return [
            "grant_type" => 'client_credentials',
            "resource" => Environment::getEnv('AZUREAPPLICATIONRESOURCELOCATION'),
            "client_secret" => Environment::getEnv('AZUREAPPLICATIONSECRET'),
            "client_id" => Environment::getEnv('AZUREAPPLICATIONCLIENT')
        ];
    }

    /**
     * returns the resource of where the data is coming from
     * 
     * @return string
     */
    public function getResourceURL() {
        return Environment::getEnv('AZUREAPPLICATIONRESOURCELOCATION');
    }

    /**
     * Creates the authentication token used for communication with CRM
     * 
     * @return string a uniqued hashed key used for authentication
     */
    private function RequestAccessToken()
    {
        // endpoint url
        $endpoint = Environment::getEnv('AZUREAPPLICATIONENDPOINT');
        $session = curl_init($endpoint);

        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($session, CURLOPT_HTTPHEADER, $this->HeadersDeAssociative($this->config()->headers));
        curl_setopt($session, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($session, CURLOPT_POST, true);
        curl_setopt($session, CURLOPT_ENCODING, true);

        // build the post paramaters
        curl_setopt($session, CURLOPT_POSTFIELDS, http_build_query($this->CreateTokenHTTPQuery()));

        // set the proxy if on CWP
        if (Environment::getEnv('SS_OUTBOUND_PROXY') && Environment::getEnv('SS_OUTBOUND_PROXY_PORT')) {
            curl_setopt($session, CURLOPT_PROXY, Environment::getEnv('SS_OUTBOUND_PROXY'));
            curl_setopt($session, CURLOPT_PROXYPORT, Environment::getEnv('SS_OUTBOUND_PROXY_PORT'));
        }

        $content = curl_exec($session);
        $code = curl_getinfo($session, CURLINFO_HTTP_CODE);

        if ($code !== 200) {
            throw new Exception('CRM did not return an access token. With code: ' . $code);
        }
        $content = json_decode($content);
        if (!isset($content->access_token)) {
            throw new Exception('token invalid');
        }

        // set the cache and the time to live according to the result
        if (isset($content->expires_in)) {
            $cache = new FilesystemCache('OP');
            $this->access_token = $cache->set($this->getCacheKey(), $content->access_token, (int) $content->expires_in);
        }

        return $content->access_token;
    }

    /**
     * 
     */
    public function HeadersDeAssociative($headers)
    {
        $retarray = [];
        foreach ($headers as $key => $header) {
            $retarray[] = $key . ": " . $header;
        }

        return $retarray;
    }


    /**
     * Fetches data from the CRM service
     * 
     * @param string webservice_url_str the service
     * @param string $method delete, get, put or post data
     * @param array $postdata data to place into post fields
     * @param array $extra_headers headers to insert
     * @return CRMResult
     */
    public function fetch($webservice_url_str, $method = "GET", $postdata = null, $extra_headers = [])
    {
        $session = curl_init($webservice_url_str);

        // build the auth headers
        $authheader = array_merge($this->config()->headers, [
            "Authorization" => " Bearer " .  $this->access_token
        ]);
        $headers = array_merge($authheader, $extra_headers);
        curl_setopt($session, CURLOPT_HTTPHEADER, $this->HeadersDeAssociative($headers));

        // return data and try for 5 seconds
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($session, CURLOPT_CONNECTTIMEOUT, 5);
       // curl_setopt($session, CURLOPT_VERBOSE, 1);
        curl_setopt($session, CURLOPT_HEADER, 1);

        //CWP proxy stuff
        if (Environment::getEnv('SS_OUTBOUND_PROXY') && Environment::getEnv('SS_OUTBOUND_PROXY_PORT')) {
            curl_setopt($session, CURLOPT_PROXY, Environment::getEnv('SS_OUTBOUND_PROXY'));
            curl_setopt($session, CURLOPT_PROXYPORT, Environment::getEnv('SS_OUTBOUND_PROXY_PORT'));
        }

        // REST
        switch (strtoupper($method)) {
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

        if (isset($postdata) && $postdata) {
            if (is_array($postdata)) {
                $jsonpostfields = json_encode($postdata);
                curl_setopt($session, CURLOPT_POSTFIELDS, $jsonpostfields);
            } else {
                curl_setopt($session, CURLOPT_POSTFIELDS, $postdata);
            }
        }

        $response = curl_exec($session);
        $code = curl_getinfo($session, CURLINFO_HTTP_CODE);

        $header_size = curl_getinfo($session, CURLINFO_HEADER_SIZE);
        $resultheaders = substr($response, 0, $header_size);
        $content = substr($response, $header_size);

        curl_close($session);

        return new CRMResult($content, $code, $resultheaders);
    }

    /**
     * Calls a static request based on defauly yml paramaters
     * 
     * @return CRMResult
     */
    public static function request($webservice_url_str, $method = "GET", $param = [], $extra_headers = [])
    {
        $crm = CRM::create();
        return $crm->fetch($webservice_url_str, $method, $param, $extra_headers);
    }
}
