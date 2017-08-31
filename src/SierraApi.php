<?php

namespace UncLibrary\SierraApi;
use Illuminate\Contracts\Session\Session;

class SierraApi
{
    /**
     * The Authorization Token array returned when accessing a token request.
     *
     * @var array
     */
    private $token = [];

    /**
     * The Client Key
     *
     * @var string
     */
    private $key;

    /**
     * The Client secret
     *
     * @var string
     */
    private $secret;

    /**
     * The url host of the sierra instance
     *
     * @var string
     */
    private $endpoint;



    /**
     * Create a new Sierra API instance.
     *
     * @param  object $session
     */
    public function __construct($key, $secret, $host, $path, $session)
    {
        $this->key = $key;
        $this->secret = $secret;
        $cleanPath = implode('/',array_filter(explode('/',$path)));
        $this->endpoint = "$host/$cleanPath/v4/";

        $session_token = $session->get('_sierra_token');
        if($session_token){
            $this->token = $session_token;
        } else {
            $this->_accessToken();
        }

        $this->_checkToken();
    }

    /**
     * Checks if Authentication Token exists or has expired. A new Authentication Token will
     * be created if one does not exist.
     *
     * @return boolean True if token is valid
     */
    private function _checkToken()
    {

        if (!$this->token || (time() >= $this->token->expires_at)) {
            return $this->_accessToken();
        }
        return true;
    }

    /**
     * Requests a Authentication Token from Sierra
     *
     * @return boolean True if a token is created
     */
    private function _accessToken() {
        $auth = base64_encode($this->key . ':' . $this->secret);

        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic ' . $auth,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['grant_type', 'client_credentials']));
        curl_setopt($ch, CURLOPT_URL, $this->endpoint.'token');
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'SierraAPI/0.1');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        $token = json_decode($result);
        curl_close($ch);

        if (!$token) return false;

        if (empty($token->error)) {
            $token->expires_at = time() + $token->expires_in;
            $this->token = $token;
            session(['_sierra_token', $this->token]);
            return true;
        }
        return false;
    }

    /**
     * Requests data from Sierra
     *
     * @param string $url The full URL to the REST API call
     * @param array $params The query paramaters to pass to the call
     * @param array $header Additional header information to include
     * @param string $type The request type 'GET' or 'POST'
     * @return array Result array
     *
     * ### Result keys returned
     * - 'status': The return status from the server
     * - 'header': The header information fo the server
     * - 'body': The body of the message
     */
    private function _request($url, $params = array(), $type = 'get', $payload = '')
    {
        $type = strtolower($type);

        $ch = curl_init();
        $headers = [
            'Authorization: ' . title_case($this->token->token_type) . ' ' .$this->token->access_token,
            'Content-Type: application/json;charset=UTF-8',
            'User-Agent: Sierra Api Client',
        ];

        if ($type === 'post') {
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($payload)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            }
        }

        $url .= $params ? '?' . http_build_query($params) : '';

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return ['body' => $result, 'status' => $status];
    }

    /**
     * Makes the resource request
     *
     * @param string $resource
     * @param array $params
     * @return object
     */
    public function get($resource, $params = array()) {
        if (!$this->_checkToken()) return null;


        // For whatever reason the SierraAPI only seems to selectively authenticate the token.
        // I could not figure out why.
        $i = 0;
        $response = $this->_request($this->endpoint . $resource, $params);
        while($response['status'] == 401 || $i == 15){
            $i++;
            $response = $this->_request($this->endpoint . $resource, $params);
        }

        if ($response['status'] != 200) return null;

        return json_decode($response['body']);
    }

    /**
     * Makes the resource request
     *
     * @param string $resource The resource being requested
     * @param array $params Array of paramaters
     * @param boolean $marc True to have the response include MARC data
     * @return array Array of data
     */
    public function query($resource, $query = array(), $offset = 0, $limit = 20) {
        if (!$this->_checkToken()) return null;

        $payload = json_encode($query);
        $params = [
            'offset' => $offset,
            'limit' => $limit,
        ];

        $response = $this->_request($this->endpoint . $resource.'/query', $params, 'post', $payload);
        if ($response['status'] != 200) return null;


        return json_decode($response['body'], true);
    }

}