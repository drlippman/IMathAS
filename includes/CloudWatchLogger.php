<?php

/**
 * CloudWatchLogger
 *
 * Sends JSON log entries to AWS CloudWatch Logs using raw HTTP requests
 * and AWS Signature Version 4 — no external dependencies required.
 *
 * Usage:
 *   $logger = new CloudWatchLogger(
 *       region:       'us-east-1',
 *       logGroup:     '/my-app/production',
 *       logStream:    'web-server-1',
 *       accessKey:    '...',
 *       secretKey:    '...'
 *   );
 *
 *   $logger->log(['level' => 'info', 'message' => 'Hello CloudWatch!']);
 *   $logger->log(['level' => 'error', 'message' => 'Something went wrong', 'code' => 500]);
 * 
 * Setup:
 *   Create new cloudwatch log group and stream
 *   In IAM, generate new user with programmatic access
 *     crease accesskey, application running outside aws
 *     IAM policy (adjust for region and log group):
 {
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "AllowCloudWatchLogging",
      "Effect": "Allow",
      "Action": [
        "logs:PutLogEvents"
      ],
      "Resource": [
        "arn:aws:logs:us-east-1:YOUR_ACCOUNT_ID:log-group:/my-app/production:*",
        "arn:aws:logs:us-east-1:YOUR_ACCOUNT_ID:log-group:/my-app/production"
      ]
    }
  ]
}
 * 
 */
class CloudWatchLogger
{
    private string $region;
    private string $logGroup;
    private string $logStream;
    private string $accessKey;
    private string $secretKey;
    private ?string $sessionToken;
 
    private const SERVICE   = 'logs';
    private const ALGORITHM = 'AWS4-HMAC-SHA256';
    private const TARGET    = 'Logs_20140328.PutLogEvents';
 
    public function __construct(
        string  $region,
        string  $logGroup,
        string  $logStream,
        string  $accessKey,
        string  $secretKey,
        ?string $sessionToken = null
    ) {
        $this->region       = $region;
        $this->logGroup     = $logGroup;
        $this->logStream    = $logStream;
        $this->accessKey    = $accessKey;
        $this->secretKey    = $secretKey;
        $this->sessionToken = $sessionToken;
    }
 
    /**
     * Send a log entry. The array is JSON-encoded and timestamped automatically.
     *
     * @param  array $data  Associative array of data to log.
     * @throws RuntimeException on HTTP or CloudWatch error.
     */
    public function log(array $data): void
    {
        $timestampMs = (int) (microtime(true) * 1000);
        $message     = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
 
        $payload = [
            'logGroupName'  => $this->logGroup,
            'logStreamName' => $this->logStream,
            'logEvents'     => [
                ['timestamp' => $timestampMs, 'message' => $message],
            ],
        ];
 
        $body        = json_encode($payload);
        $now         = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $amzDate     = $now->format('Ymd\THis\Z');
        $dateStamp   = $now->format('Ymd');
        $endpoint    = "https://logs.{$this->region}.amazonaws.com/";
        $host        = "logs.{$this->region}.amazonaws.com";
        $contentHash = hash('sha256', $body);
 
        // ── Build canonical headers ──────────────────────────────────────────
        $headers = [
            'content-type' => 'application/x-amz-json-1.1',
            'host'         => $host,
            'x-amz-date'   => $amzDate,
            'x-amz-target' => self::TARGET,
        ];
 
        if ($this->sessionToken !== null) {
            $headers['x-amz-security-token'] = $this->sessionToken;
        }
 
        ksort($headers);
 
        $canonicalHeaders = '';
        $signedHeadersList = [];
        foreach ($headers as $key => $value) {
            $canonicalHeaders   .= $key . ':' . $value . "\n";
            $signedHeadersList[] = $key;
        }
        $signedHeaders = implode(';', $signedHeadersList);
 
        // ── Canonical request ────────────────────────────────────────────────
        $canonicalRequest = implode("\n", [
            'POST',
            '/',
            '',                  // query string
            $canonicalHeaders,
            $signedHeaders,
            $contentHash,
        ]);
 
        // ── String to sign ───────────────────────────────────────────────────
        $credentialScope = "{$dateStamp}/{$this->region}/" . self::SERVICE . '/aws4_request';
        $stringToSign    = implode("\n", [
            self::ALGORITHM,
            $amzDate,
            $credentialScope,
            hash('sha256', $canonicalRequest),
        ]);
 
        // ── Signing key & signature ──────────────────────────────────────────
        $signingKey = $this->deriveSigningKey($dateStamp);
        $signature  = hash_hmac('sha256', $stringToSign, $signingKey);
 
        // ── Authorization header ─────────────────────────────────────────────
        $authorization = sprintf(
            '%s Credential=%s/%s, SignedHeaders=%s, Signature=%s',
            self::ALGORITHM,
            $this->accessKey,
            $credentialScope,
            $signedHeaders,
            $signature
        );
 
        // ── Send request ─────────────────────────────────────────────────────
        $httpHeaders = ["Authorization: {$authorization}"];
        foreach ($headers as $key => $value) {
            $httpHeaders[] = "{$key}: {$value}";
        }
        $httpHeaders[] = "content-length: " . strlen($body);
 
        $ctx = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'header'        => implode("\r\n", $httpHeaders),
                'content'       => $body,
                'ignore_errors' => true,
            ],
        ]);
 
        $responseBody = file_get_contents($endpoint, false, $ctx);
 
        if ($responseBody === false) {
            throw new RuntimeException('CloudWatchLogger: HTTP request failed (network error).');
        }
 
        // Check HTTP status
        if (function_exists('http_get_last_response_headers')) {
            $http_response_header = http_get_last_response_headers(); // PHP 8.4+
        }
        $statusLine = $http_response_header[0] ?? '';
        if (!preg_match('/\s(2\d{2})\s/', $statusLine, $m)) {
            throw new RuntimeException(
                "CloudWatchLogger: Unexpected HTTP response: {$statusLine}\nBody: {$responseBody}"
            );
        }
 
        json_decode($responseBody, true);
    }
 
    // ── Private helpers ──────────────────────────────────────────────────────
 
    private function deriveSigningKey(string $dateStamp): string
    {
        $kDate    = hash_hmac('sha256', $dateStamp,          'AWS4' . $this->secretKey, true);
        $kRegion  = hash_hmac('sha256', $this->region,       $kDate,    true);
        $kService = hash_hmac('sha256', self::SERVICE,       $kRegion,  true);
        $kSigning = hash_hmac('sha256', 'aws4_request',      $kService, true);
        return $kSigning;
    }
}

function addLoginLog($eventtype,$userid,$additionalinfo = []) {
    global $CFG;

    $logger = new CloudWatchLogger(
        $CFG['cloudwatch_loginlog']['region'],
        $CFG['cloudwatch_loginlog']['logGroup'],
        $CFG['cloudwatch_loginlog']['logStream'],
        $CFG['cloudwatch_loginlog']['accessKey'],
        $CFG['cloudwatch_loginlog']['secretKey']
    );
    $baseinfo = [
        'level' => 'info',
        'eventtype' => $eventtype,
        'userid' => $userid,
        'ip' => $_SERVER['HTTP_CF_CONNECTING_IP']
                ?? $_SERVER['HTTP_X_FORWARDED_FOR']
                ?? $_SERVER['REMOTE_ADDR']
                ?? $_SERVER['HTTP_CLIENT_IP']
                ?? '',
        'timestamp' => time(),
        'geo_country' => $_SERVER['HTTP_CF_IPCOUNTRY'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ];
    $logger->log($baseinfo + $additionalinfo);
}