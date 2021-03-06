<?php

/**
 * Class sjh_media
 */
final class sjh_media {

    const API_VERSION = '2.10';
    const API_HOSTNAME = 'https://www.sjhmedia.net';

    private $api_params = [];
    private $api_key = null;
    private $api_secret = null;
    private $api_endpoint = null;
    private $api_endpoint_details = null;

    private $api_endpoints = [
        'feed' => [
            'method' => 'GET',
            'required_parameters' => [],
        ],
        'status' => [
            'method' => 'GET',
            'required_parameters' => [
                'id',
            ],
        ],
        'update' => [
            'method' => 'POST',
            'required_parameters' => [
                'id',
                'action',
            ],
        ],
        'recycle' => [
            'method' => 'POST',
            'required_parameters' => [
                'gadgets',
                'first_name',
                'last_name',
                'email_address',
                'phone_number',
                'address1',
                'town',
                'county',
                'postcode',
                'country',
                'ip_address',
                'payment_method',
                'number_of_labels',
            ],
        ]
    ];
    
    /**
     * @param string $api_key
     * @param string $api_secret
     */
    public function __construct($api_key, $api_secret) {
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
        
        $this->api_params['api_key'] = $this->api_key;
    }

    /**
     * @param string $api_endpoint
     *
     * @return bool
     * @throws \Exception
     */
    public function setApiEndpoint($api_endpoint) {
        $api_endpoint = strtolower(trim($api_endpoint, '/\\ '));
        if (isset($this->api_endpoints[$api_endpoint])) {
            // Store the API endpoint details
            $this->api_endpoint_details = $this->api_endpoints[$api_endpoint];
            $this->api_endpoint = $api_endpoint;

            return true;
        }

        throw new Exception('Please ensure you\'re specifying a valid endpoint for this API/SDK version');
    }

    /**
     * @return array|string
     * @throws \Exception
     */
    public function doApiRequest() {
        try {
            $this->doVerifyRequest();

            $request_method = $this->getRequestMethod();
            $curl_options = [
                CURLOPT_URL => $this->getEndpointUrl(),
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT => 'SJH Media SDK v' . self::API_VERSION,
                CURLOPT_HTTPHEADER => [
                    'x-sjh-media-signature: ' . $this->getRequsetSignature(),
                    'x-sjh-media-referrer: ' . $this->getReferrer()
                ]
            ];

            $request_parameters = $this->getSortedParameters();

            if ($request_method === 'POST') {
                $curl_options[CURLOPT_POST] = true;
                $curl_options[CURLOPT_POSTFIELDS] = http_build_query($request_parameters);
            } else if ($request_method === 'GET') {
                $curl_options[CURLOPT_URL] .= '?' . http_build_query($request_parameters);
            }

            $curl = curl_init();
            curl_setopt_array($curl, $curl_options);
            $api_response = curl_exec($curl);
            $response_headers = curl_getinfo($curl);
            curl_close($curl);
            
            if ($api_response === false) {
                throw new Exception('API request failed, response headers: ' . print_r($response_headers, true))
            }
            if ($json_response = json_decode($api_response, true)) {
                 $api_response = $json_response;
            }

            return $api_response;
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }

    /**
     * @return bool
     * @throws \Exception
     */
    private function doVerifyRequest() {
        $errors = [];

        if ($this->api_endpoint === null || $this->api_endpoint_details === null) {
            $errors[] = 'Please make sure to call $this->setApiEndpoint("/change_to_your_required_feature") before calling $this->doApiRequest()';
        }

        if (!isset($this->api_params['nonce'])) {
            $this->api_params['nonce'] = $this->getNonceValue();
        }
        if (!empty($this->api_endpoint_details['required_parameters'])) {
            $missing_parameters = [];
            foreach ($this->api_endpoint_details['required_parameters'] as $required_param) {
                if (!isset($this->api_params[$required_param])) {
                    $missing_parameters[] = $required_param;
                }
            }

            if (!empty($missing_parameters)) {
                $errors[] = 'The following required parameters are missing from your API request: ' . implode(', ', $missing_parameters);
            }
        }

        if (!empty($errors)) {
            throw new Exception('API request failed to pass checks with the following errors: ' . implode(', ', $errors));
        }

        return true;
    }

    /**
     * @return string
     */
    private function getRequestMethod() {
        return strtoupper($this->api_endpoint_details['method']);
    }

    /**
     * @return string
     */
    private function getEndpointUrl() {
        return self::API_HOSTNAME . '/api/' . self::API_VERSION . '/' . $this->api_endpoint;
    }

    /**
     * @return string
     */
    private function getRequsetSignature() {
        return hash_hmac('sha512', $this->getFullRequestUrl(), self::SECRET_KEY);
    }

    /**
     * @return string
     */
    private function getFullRequestUrl() {
        $url = $this->getEndpointUrl();
        if (!empty($this->api_params)) {
            $url .= '?' . http_build_query($this->getSortedParameters());
        }

        return $url;
    }

    /**
     * @return array
     */
    private function getSortedParameters() {
        ksort($this->api_params); // Sort parameters alphabetically by their key

        return $this->api_params;
    }

    /**
     * @return null|string
     */
    private function getReferrer() {
        if (isset($_SERVER['HTTP_HOST']) && !empty($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_URI'])) {
            return 'http' . ($this->isHttps() ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        }

        return null;
    }

    /**
     * @return bool
     */
    private function isHttps() {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            return true;
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
            // Fix for proxies / load balancers which oftern strip the HTTPS headers before routing
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function setRequestParameter( /* Polymorphic - Can be $key, $value OR array('key1' => 'val1'...) */) {
        $arguments = func_get_args();
        if (count($arguments) == 2) {
            // Handle $key, $value arguments
            $this->api_params[$arguments[0]] = $arguments[1];
        } else if (is_array($arguments[0])) {
            // Handle arrays arguments
            foreach ($arguments[0] as $key => $value) {
                $this->api_params[$key] = $value;
            }
        } else {
            throw new InvalidArgumentException('$this->setRequestParameter() is Polymorphic - Parameters can be formatted as ($key, $value) OR (array("key1" => "val1", ...))');
        }

        return true;
    }

    /**
     * @return int
     */
    private function getNonceValue() {
        return time(); // By default this will suffice
    }
}
