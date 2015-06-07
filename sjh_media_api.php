<?php
/**
 * Class sjh_media
 */
final class sjh_media {

	// TODO; Add your own API credentials here
	const API_KEY    = 'YOUR_API_KEY_HERE';
	const SECRET_KEY = 'YOUR_SECRET_KEY_HERE';
	// TODO; End of settings to be changed - Don't edit beneath here

	const API_VERSION = '1.0';
	const API_HOSTNAME = 'https://www.sjhmedia.net';

	private $api_params = [
		'api_key' => self::API_KEY
	];
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
		'recycle' => [
			'method' => 'POST',
			'required_parameters' => [
				'gadgets',
				'first_name',
				'last_name',
				'email_address',
				'phone_number',
				'address1',
				'city',
				'county',
				'postcode',
				'country',
				'ip_address',
				'payment_method',
				'trade_in_packs_required',
			],
		]
	];

	/**
	 * @param string $api_endpoint
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
	 * @return string
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
					'x-sjh-media-signature: ' . $this->getRequsetSignature()
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
			if ($api_response === false) {
				$response_headers = curl_getinfo($curl);
				echo '<p><pre>' . print_r($response_headers, true) . '</pre></p>'."\n";
			}
			curl_close($curl);

			return $api_response;

		} catch(Exception $e) {
			throw new Exception($e);
		}
	}

	/**
	 * @return bool
	 */
	public function setRequestParameter( /* Polymorphic - Can be $key, $value OR array('key1' => 'val1'...) */ ) {
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
	 * @return string
	 */
	private function getEndpointUrl() {
		return self::API_HOSTNAME . '/api/' . self::API_VERSION . '/' . $this->api_endpoint;
	}

	/**
	 * @return array
	 */
	private function getSortedParameters() {
		ksort($this->api_params); // Sort parameters alphabetically by their key

		return $this->api_params;
	}

	/**
	 * @return string
	 */
	private function getRequestMethod() {
		return strtoupper($this->api_endpoint_details['method']);
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
}