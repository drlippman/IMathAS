<?php

namespace Idealstack\DynamoDbSessionsDependencyFree;

/**
 * Class DynamoDbClient
 * @package Idealstack
 *
 * Dependency-free dynamodb client.  This means you can include it without polluting the namespace with a forked version of
 * AWS
 */
class AwsClient
{
    const ECS_SERVER_URI = 'http://169.254.170.2';
    const ENV_URI = "AWS_CONTAINER_CREDENTIALS_RELATIVE_URI";
    const ENV_KEY = 'AWS_ACCESS_KEY_ID';
    const ENV_SECRET = 'AWS_SECRET_ACCESS_KEY';
    const ENV_SESSION = 'AWS_SESSION_TOKEN';
    const ENV_PROFILE = 'AWS_PROFILE';
    const ENV_FILENAME = 'AWS_CREDENTIALS_FILENAME';


    const EC2_SERVER_URI = 'http://169.254.169.254/latest/';
    const CRED_PATH = 'meta-data/iam/security-credentials/';

    const ENV_DISABLE = 'AWS_EC2_METADATA_DISABLED';

    protected $service = 'DynamoDB';
    protected $client;
    protected $config;
    protected $region;
    protected $cache = [];
    protected $version = '20120810';

    /** @var false|resource Curl handle */
    protected $curl;

    public function __construct($config)
    {

        $config += [
            'debug' => false
        ];

        $this->config = $config;

        if (array_key_exists('service', $config)) {
            $this->service = $config['service'];
        }

        if (array_key_exists('region', $config)) {
            $this->region = $config['region'];
        } elseif (getenv('AWS_REGION')) {
            $this->region = getenv('AWS_REGION');
        } else {
            throw new AwsClientException('No region specified');
        }


        $this->curl = curl_init();

        // defaults
        $default_curl_options = array(
            CURLOPT_ENCODING => 'gzip,deflate',
            CURLOPT_HEADER => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 30,
        );


        if($this->config['debug']) {
            $default_curl_options[CURLINFO_HEADER_OUT] =  true;
        }

        // apply default options
        curl_setopt_array($this->curl, $default_curl_options);
    }

    /**
     * Make an HTTP request using curl
     *
     * @param string HTTP method (GET|POST|PUT|DELETE)
     * @param string URI
     * @param mixed content for POST and PUT methods
     * @param array headers
     * @param array curl options
     * @return array of 'headers', 'content', 'error'
     */
    function curl($uri, $method = 'GET', $data = null, $headers = array(), $curl_options = array())
    {

        $curl_headers = [];
        foreach ($headers as $header => $value) {
            $curl_headers[] = "$header: $value";
        }

        $default_headers = array();

        // validate input
        $method = strtoupper(trim($method));

        // init
        curl_setopt($this->curl, CURLOPT_URL, $uri);


        // apply method specific options
        switch ($method) {
            case 'GET':
                curl_setopt($this->curl, CURLOPT_POST, 0);
                break;
            case 'POST':
                curl_setopt($this->curl, CURLOPT_POST, 1);
                curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
                $curl_headers[] = "Content-length: " . strlen($data);
                break;
            case 'PUT':
                curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $method);
                curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
                $curl_headers[] = "Content-length: " . strlen($data);
                break;
            case 'DELETE':
                curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $method);
                break;
        }

        // apply user options
        curl_setopt_array($this->curl, $curl_options);

        // add headers
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, array_merge($default_headers, $curl_headers));

        // parse result
        $raw = rtrim(curl_exec($this->curl));
        $lines = explode("\r\n", $raw);
        $headers = array();
        $content = '';
        $write_content = false;
        if (count($lines) > 3) {
            foreach ($lines as $h) {
                if ($h == '') {
                    $write_content = true;
                } else {
                    if ($write_content) {
                        $content .= $h . "\n";
                    } else {
                        $headers[] = $h;
                    }
                }
            }
        }

        $httpcode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        $error = curl_error($this->curl);

        if ($this->config['debug']) {
            $info = curl_getinfo($this->curl);
            echo "Request: ";
            print_r($info);
            echo "Response: ";
            echo $raw;
       }

        // return
        return array(
            'raw' => $raw,
            'headers' => $headers,
            'content' => $content,
            'error' => $error,
            'code' => $httpcode
        );
    }


    /**
     * Memoize a function call to avoid calling it over and over
     * @param $func
     * @return \Closure
     */
    private function runOnce($key, $func)
    {
        if (!isset($this->cache[$key])) {
            $this->cache[$key] = $func();
        }

        return $this->cache[$key];
    }

    /**
     * Print debug information
     * @param $message
     */
    protected function debugPrint($message) {
        if ($this->config['debug']) {
            echo $message ."\n";
        }

    }

    /**
     * Return an array of AWS connection details
     * @return array
     */
    private function getCredentials()
    {
        return $this->runOnce('credentials', function () {
            if (array_key_exists('credentials', $this->config)) {
                //Then the credentials need to have been set manually (eg for testing)
                $this->debugPrint('Using manually set credentials');
                return $this->config['credentials'];
            } elseif (getenv(self::ENV_KEY) !== false) {
                $this->debugPrint('Using credentials from the environment: '.getenv(self::ENV_KEY));
                $credentials = [
                    'key' => getenv(self::ENV_KEY),
                    'secret' => getenv(self::ENV_SECRET)
                ];
                    if (self::ENV_SESSION) {
                    $credentials ['token'] = getenv(self::ENV_SESSION);
                }
                return $credentials;
            } elseif ($credentials = self::ini()) {
                $this->debugPrint('Using credentials from the ini file');
                return $credentials;
            } elseif (getenv(self::ENV_URI)) {
                $this->debugPrint('Using credentials from ECS');
                return $this->getEcsCredentials();
            } else {
                $this->debugPrint('Using credentials from the instance profile');
                return $this->getInstanceProfileCredentials();
            }
        });
    }

    /**
     * Credentials provider that creates credentials using an ini file stored
     * in the current user's home directory.
     *
     * @param string|null $profile Profile to use. If not specified will use
     *                              the "default" profile in "~/.aws/credentials".
     * @param string|null $filename If provided, uses a custom filename rather
     *                              than looking in the home directory.
     *
     * @return callable
     */
    private static function ini($profile = null, $filename = null)
    {
        $filename = $filename ?: getenv(self::ENV_FILENAME) ? getenv(self::ENV_FILENAME) : self::getHomeDir() . '/.aws/credentials';
        $profile = $profile ?: (getenv(self::ENV_PROFILE) ?: 'default');

        if (!is_readable($filename)) {
            return;
        }
        $data = parse_ini_file($filename, true, INI_SCANNER_RAW);
        if ($data === false) {
            return;
        }
        if (!isset($data[$profile])) {
            return;
        }
        if (!isset($data[$profile]['aws_access_key_id'])
            || !isset($data[$profile]['aws_secret_access_key'])
        ) {
            return;
        }

        if (empty($data[$profile]['aws_session_token'])) {
            $data[$profile]['aws_session_token']
                = isset($data[$profile]['aws_security_token'])
                ? $data[$profile]['aws_security_token']
                : null;
        }
        $credentials = [
            'key' => trim($data[$profile]['aws_access_key_id']),
            'secret' => trim($data[$profile]['aws_secret_access_key']),

        ];
        if (array_key_exists('aws_session_token', $data[$profile])) {
            $credentials['token'] = trim($data[$profile]['aws_session_token']);
        }
        return $credentials;
    }

    /**
     * Gets the environment's HOME directory if available.
     *
     * @return null|string
     */
    private static function getHomeDir()
    {
        // On Linux/Unix-like systems, use the HOME environment variable
        if ($homeDir = getenv('HOME')) {
            return $homeDir;
        }

        // Get the HOMEDRIVE and HOMEPATH values for Windows hosts
        $homeDrive = getenv('HOMEDRIVE');
        $homePath = getenv('HOMEPATH');

        return ($homeDrive && $homePath) ? $homeDrive . $homePath : null;
    }

    private function getEcsCredentials()
    {
        $curl_result = $this->curl($this->getEcsUri());
        $response = $curl_result['content'];
        $result = $this->decodeResult($response);
        return [
            'key' => $result['AccessKeyId'],
            'secret' => $result['SecretAccessKey'],
            'token' => $result['Token'],
            'expiration' => strtotime($result['Expiration']),
        ];
    }


    private function getInstanceProfileCredentials()
    {
        $curl_result = $this->curl(self::EC2_SERVER_URI . self::CRED_PATH);
        $response = $curl_result['content'];
        $result = $this->decodeResult($response);
        return [
            'key' => $result['AccessKeyId'],
            'secret' => $result['SecretAccessKey'],
            'token' => $result['Token'],
            'expiration' => strtotime($result['Expiration']),
        ];
    }


    /**
     * Fetch credential URI from ECS environment variable
     *
     * @return string Returns ECS URI
     */
    private function getEcsUri()
    {
        $creds_uri = getenv(self::ENV_URI);
        return self::ECS_SERVER_URI . $creds_uri;
    }

    private function decodeResult($response)
    {
        $result = json_decode($response, true);

        if (!isset($result['AccessKeyId'])) {
            throw new \Exception('Unexpected ECS credential value');
        }
        return $result;
    }

    /**
     * Create the canonical request as part of signature
     *         https://docs.aws.amazon.com/general/latest/gr/signing_aws_api_requests.html
     * @param $body
     */
    private function getCanonicalRequest($method, $uri, $query_params, $headers, $body)
    {
        //Construct the 'canonical request' https://docs.aws.amazon.com/general/latest/gr/sigv4-create-canonical-request.html
        $canonical_uri = parse_url($uri, PHP_URL_PATH);
        if ($canonical_uri == '') {
            $canonical_uri = '/';
        }

        ksort($query_params);

        $canonical_query = array_map(function ($data) {
            return rawurlencode($data);
        }, $query_params);
        $canonical_query_string = implode('&', array_map(
                function ($key, $value) {
                    return "$key=$value";
                },
                array_keys($canonical_query), array_values($canonical_query))
        );

        //Host must be included in headers
        if (!array_key_exists('Host', $headers)) {
            $headers['Host'] = $canonical_url = parse_url($uri, PHP_URL_HOST);
        }

        $canonical_headers = [];
        foreach ($headers as $key => $value) {
            $canonical_headers[trim(preg_replace('/\s+/', ' ', strtolower($key)))] = trim(preg_replace('/\s+/', ' ',
                $value));
        }

        ksort($canonical_headers);

        $canonical_header_string = implode("\n", array_map(
                function ($key, $value) {
                    return "$key:$value";
                },
                array_keys($canonical_headers), array_values($canonical_headers))
        );


        $signed_headers = implode(';', array_keys($canonical_headers));

        $hashed_body = hash('sha256', $body);

        return [
            'CanonicalRequest' => "$method\n$canonical_uri\n$canonical_query_string\n$canonical_header_string\n\n$signed_headers\n$hashed_body",
            'SignedHeaders' => $signed_headers
        ];
    }

    /** Construct the AWS request */
    private function getAwsRequestHeaders($method, $uri, $params, $headers, $body, $date = null)
    {
        if (is_null($date)) {
            $date = time();
        }
        $credentials = $this->getCredentials();

        if (array_key_exists('token', $credentials) && $credentials['token']) {
            $headers['X-Amz-Security-Token'] = $credentials['token'];
        }

        $headers['X-Amz-Date'] = gmdate('Ymd\THis\Z', $date);
        $credential_scope = gmdate('Ymd', $date) . "/$this->region/" . strtolower($this->service) . "/aws4_request";

        $canonical_request = $this->getCanonicalRequest($method, $uri, $params, $headers, $body, $credential_scope);
        $canonical_request_hash = hash('sha256',
            $canonical_request['CanonicalRequest']
        );
        $string_to_sign = "AWS4-HMAC-SHA256\n" . $headers['X-Amz-Date'] . "\n$credential_scope\n$canonical_request_hash";
        $signing_key = $this->getSigningKey($date, $this->service);

        $signature = bin2hex(hash_hmac('sha256', $string_to_sign, $signing_key, true));


        //Construct the Authorization header
        $access_key_id = $credentials['key'];
        $headers['Authorization'] = "AWS4-HMAC-SHA256 Credential=$access_key_id/$credential_scope, SignedHeaders=" . $canonical_request['SignedHeaders'] .
            ", Signature=$signature";

        return $headers;
    }


    /**
     * Calculate the signing key
     *        https://docs.aws.amazon.com/general/latest/gr/sigv4-calculate-signature.html
     * @param $date
     */
    private function getSigningKey($date, $service)
    {
        $credentials = $this->getCredentials();

        $kSecret = utf8_encode("AWS4" . $credentials['secret']);
        $kDate = hash_hmac('sha256', gmdate('Ymd', $date), hex2bin(bin2hex($kSecret)), true);
        $kRegion = hash_hmac('sha256', $this->region, $kDate, true);
        $kService = hash_hmac('sha256', strtolower($service), $kRegion, true);
        $kSigning = hash_hmac('sha256', "aws4_request", $kService, true);
        return $kSigning;
    }


    protected function awsRequest($action, $params)
    {
        $endpoint = 'https://' . strtolower($this->service) . '.' . $this->region . '.amazonaws.com/';

        $headers = [
            'Content-Type' => 'application/x-amz-json-1.0',
            'X-Amz-Target' => $this->service . '_' . $this->version . '.' . $action,
        ];

        $body = json_encode($params);
        $headers = $this->getAwsRequestHeaders('POST', $endpoint, [], $headers, $body);
        $curl_result = $this->curl($endpoint, 'POST', $body, $headers);
        $result = $curl_result['content'];

        $decoded_result = json_decode($result, 1);
        if (!is_array($decoded_result)) {
            $decoded_result = [];
        }

        if ($curl_result['error'] || $curl_result['code'] >= 400) {
            $exception = new AwsClientException($curl_result['error'] . (
                array_key_exists('message',
                    $decoded_result) ? $decoded_result['message'] : $result),
                $curl_result['code'], null);
            if (array_key_exists('__type', $decoded_result)) {
                $exception->setAwsErrorType($decoded_result['__type']);
            }
            throw $exception;
        }

        return $decoded_result;
    }
}

class AwsClientException extends \Exception
{
    protected $aws_error_code;

    public function __construct($message = "", $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Set the aws error '__type' {"__type":"com.amazon.coral.service#UnknownOperationException"}
     * @param $type
     */
    public function setAwsErrorType($type)
    {
        $matches = [];
        if (preg_match('/#(.*)/', $type, $matches)) {
            $this->aws_error_code = $matches[1];
        } else {
            $this->aws_error_code = $type;
        }
    }

    public function getAwsErrorCode()
    {
        return $this->aws_error_code;
    }

}
