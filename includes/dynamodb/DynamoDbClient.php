<?php

namespace Idealstack\DynamoDbSessionsDependencyFree;

require_once __DIR__ . '/AwsClient.php';

/**
 * Class DynamoDbClient
 * @package Idealstack
 *
 * Minimal DynamoDB client with minimal dependencies.  Only implements the
 * functions required for PHP sessions.
 */
class DynamoDbClient extends AwsClient
{

    /** @var int Max number of retries */
    const MAX_RETRIES = 5;

    /** @var array Errors we should retry */
    const RETRY_ERRORS = [
        'LimitExceededException',
        'ProvisionedThroughputExceededException',
        'RequestLimitExceeded',
        'ThrottlingException',
        'ServiceUnavailableException'
    ];

    public function __call($name, $arguments)
    {
        $action = ucfirst($name);
        $retry = 0;
        $retries = 0;
        do {
            usleep(2 ^ ($retries * 100) * 1000);
            try {
                return $this->awsRequest($action, $arguments[0]);
            } catch (AwsClientException $e) {
                if ($e->getCode() == 500 ||
                    array_search($e->getAwsErrorCode(), self::RETRY_ERRORS) !== false) {
                    error_log ( "Error received calling dynamodb for sessions.  ".$e->getAwsErrorCode() . ':'.$e->getMessage());
                    $retry = true;
                }
                else {
                    throw new DynamoDbException($e->getMessage(), $e->getCode(), $e);
                }
            }
            $retries++;
        } while ($retry && ($retries < self::MAX_RETRIES));
    }
}

class DynamoDbException extends \Exception
{

}