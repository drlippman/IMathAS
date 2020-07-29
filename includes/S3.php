<?php
/**
* $Id: S3.php 34 2008-09-11 06:30:22Z don.schonknecht $
*
* Copyright (c) 2007-2008, Donovan Schonknecht.  All rights reserved.
*
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions are met:
*
* - Redistributions of source code must retain the above copyright notice,
*   this list of conditions and the following disclaimer.
* - Redistributions in binary form must reproduce the above copyright
*   notice, this list of conditions and the following disclaimer in the
*   documentation and/or other materials provided with the distribution.
*
* THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
* AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
* IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
* ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
* LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
* CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
* SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
* INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
* CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
* ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
* POSSIBILITY OF SUCH DAMAGE.
*/

/**
* Amazon S3 PHP class
*
* @link http://undesigned.org.za/2007/10/22/amazon-s3-php-class
* @version 0.3.4
*/
class S3 {
	// ACL flags
	const ACL_PRIVATE = 'private';
	const ACL_PUBLIC_READ = 'public-read';
	const ACL_PUBLIC_READ_WRITE = 'public-read-write';

	public static $useSSL = true;

	private static $__accessKey; // AWS Access key
	private static $__secretKey; // AWS Secret key


	/**
	* Constructor, used if you're not calling the class statically
	*
	* @param string $accessKey Access key
	* @param string $secretKey Secret key
	* @param boolean $useSSL Whether or not to use SSL
	* @return void
	*/
	public function __construct($accessKey = null, $secretKey = null, $useSSL = true) {
		if ($accessKey !== null && $secretKey !== null)
			self::setAuth($accessKey, $secretKey);
		self::$useSSL = $useSSL;
	}


	/**
	* Set access information
	*
	* @param string $accessKey Access key
	* @param string $secretKey Secret key
	* @return void
	*/
	public static function setAuth($accessKey, $secretKey) {
		self::$__accessKey = $accessKey;
		self::$__secretKey = $secretKey;
	}


	/**
	* Get a list of buckets
	*
	* @param boolean $detailed Returns detailed bucket list when true
	* @return array | false
	*/
	public static function listBuckets($detailed = false) {
		$rest = new S3Request('GET', '', '');
		$rest = $rest->getResponse();
		if ($rest->error === false && $rest->code !== 200)
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false) {
			trigger_error(sprintf("S3::listBuckets(): [%s] %s", $rest->error['code'], $rest->error['message']), E_USER_WARNING);
			return false;
		}
		$results = array(); //var_dump($rest->body);
		if (!isset($rest->body->Buckets)) return $results;

		if ($detailed) {
			if (isset($rest->body->Owner, $rest->body->Owner->ID, $rest->body->Owner->DisplayName))
			$results['owner'] = array(
				'id' => (string)$rest->body->Owner->ID, 'name' => (string)$rest->body->Owner->ID
			);
			$results['buckets'] = array();
			foreach ($rest->body->Buckets->Bucket as $b)
				$results['buckets'][] = array(
					'name' => (string)$b->Name, 'time' => strtotime((string)$b->CreationDate)
				);
		} else
			foreach ($rest->body->Buckets->Bucket as $b) $results[] = (string)$b->Name;

		return $results;
	}


	/*
	* Get contents for a bucket
	*
	* If maxKeys is null this method will loop through truncated result sets
	*
	* @param string $bucket Bucket name
	* @param string $prefix Prefix
	* @param string $marker Marker (last file listed)
	* @param string $maxKeys Max keys (maximum number of keys to return)
	* @param string $delimiter Delimiter
	* @return array | false
	*/
	public static function getBucket($bucket, $prefix = null, $marker = null, $maxKeys = null, $delimiter = null) {
		$rest = new S3Request('GET', $bucket, '');
		if ($prefix !== null && $prefix !== '') $rest->setParameter('prefix', $prefix);
		if ($marker !== null && $marker !== '') $rest->setParameter('marker', $marker);
		if ($maxKeys !== null && $maxKeys !== '') $rest->setParameter('max-keys', $maxKeys);
		if ($delimiter !== null && $delimiter !== '') $rest->setParameter('delimiter', $delimiter);
		$response = $rest->getResponse();
		if ($response->error === false && $response->code !== 200)
			$response->error = array('code' => $response->code, 'message' => 'Unexpected HTTP status');
		if ($response->error !== false) {
			trigger_error(sprintf("S3::getBucket(): [%s] %s", $response->error['code'], $response->error['message']), E_USER_WARNING);
			return false;
		}

		$results = array();

		$lastMarker = null;
		if (isset($response->body, $response->body->Contents))
			foreach ($response->body->Contents as $c) {
				$results[(string)$c->Key] = array(
					'name' => (string)$c->Key,
					'time' => strtotime(str_replace('TO',' ',(string)$c->LastModified)),
					'size' => (int)$c->Size,
					'hash' => substr((string)$c->ETag, 1, -1)
				);
				$lastMarker = (string)$c->Key;
				//$response->body->IsTruncated = 'true'; break;
			}


		if (isset($response->body->IsTruncated) &&
		(string)$response->body->IsTruncated == 'false') return $results;

		// Loop through truncated results if maxKeys isn't specified
		if ($maxKeys == null && $lastMarker !== null && (string)$response->body->IsTruncated == 'true')
		do {
			$rest = new S3Request('GET', $bucket, '');
			if ($prefix !== null) $rest->setParameter('prefix', $prefix);
			$rest->setParameter('marker', $lastMarker);

			if (($response = $rest->getResponse(true)) == false || $response->code !== 200) break;
			if (isset($response->body, $response->body->Contents))
				foreach ($response->body->Contents as $c) {
					$results[(string)$c->Key] = array(
						'name' => (string)$c->Key,
						'time' => strtotime((string)$c->LastModified),
						'size' => (int)$c->Size,
						'hash' => substr((string)$c->ETag, 1, -1)
					);
					$lastMarker = (string)$c->Key;
				}
		} while ($response !== false && (string)$response->body->IsTruncated == 'true');

		return $results;
	}


	/**
	* Put a bucket
	*
	* @param string $bucket Bucket name
	* @param constant $acl ACL flag
	* @param string $location Set as "EU" to create buckets hosted in Europe
	* @return boolean
	*/
	public static function putBucket($bucket, $acl = self::ACL_PRIVATE, $location = false) {
		$rest = new S3Request('PUT', $bucket, '');
		$rest->setAmzHeader('x-amz-acl', $acl);

		if ($location !== false) {
			$dom = new DOMDocument;
			$createBucketConfiguration = $dom->createElement('CreateBucketConfiguration');
			$locationConstraint = $dom->createElement('LocationConstraint', strtoupper($location));
			$createBucketConfiguration->appendChild($locationConstraint);
			$dom->appendChild($createBucketConfiguration);
			$rest->data = $dom->saveXML();
			$rest->size = strlen($rest->data);
			$rest->setHeader('Content-Type', 'application/xml');
		}
		$rest = $rest->getResponse();

		if ($rest->error === false && $rest->code !== 200)
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false) {
			trigger_error(sprintf("S3::putBucket({$bucket}, {$acl}, {$location}): [%s] %s",
			$rest->error['code'], $rest->error['message']), E_USER_WARNING);
			return false;
		}
		return true;
	}


	/**
	* Delete an empty bucket
	*
	* @param string $bucket Bucket name
	* @return boolean
	*/
	public static function deleteBucket($bucket) {
		$rest = new S3Request('DELETE', $bucket);
		$rest = $rest->getResponse();
		if ($rest->error === false && $rest->code !== 204)
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false) {
			trigger_error(sprintf("S3::deleteBucket({$bucket}): [%s] %s",
			$rest->error['code'], $rest->error['message']), E_USER_WARNING);
			return false;
		}
		return true;
	}


	/**
	* Create input info array for putObject()
	*
	* @param string $file Input file
	* @param mixed $md5sum Use MD5 hash (supply a string if you want to use your own)
	* @return array | false
	*/
	public static function inputFile($file, $md5sum = true) {
		if (!file_exists($file) || !is_file($file) || !is_readable($file)) {
			trigger_error('S3::inputFile(): Unable to open input file: '.$file, E_USER_WARNING);
			return false;
		}
		return array('file' => $file, 'size' => filesize($file),
		'md5sum' => $md5sum !== false ? (is_string($md5sum) ? $md5sum :
		base64_encode(md5_file($file, true))) : '');
	}


	/**
	* Use a resource for input
	*
	* @param string $file Input file
	* @param integer $bufferSize Input byte size
	* @param string $md5sum MD5 hash to send (optional)
	* @return array | false
	*/
	public static function inputResource(&$resource, $bufferSize, $md5sum = '') {
		if (!is_resource($resource) || $bufferSize <= 0) {
			trigger_error('S3::inputResource(): Invalid resource or buffer size', E_USER_WARNING);
			return false;
		}
		$input = array('size' => $bufferSize, 'md5sum' => $md5sum);
		$input['fp'] =& $resource;
		return $input;
	}


	/**
	* Put an object
	*
	* @param mixed $input Input data
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @param constant $acl ACL constant
	* @param array $metaHeaders Array of x-amz-meta-* headers
	* @param mixed $requestHeaders Array of request headers or content type as a string
	* @return boolean
	*/
	public static function putObject($input, $bucket, $uri, $acl = self::ACL_PRIVATE, $metaHeaders = array(), $requestHeaders = array()) {
		if ($input == false) return false;
		$rest = new S3Request('PUT', $bucket, $uri);

		if (is_string($input)) $input = array(
			'data' => $input, 'size' => strlen($input),
			'md5sum' => base64_encode(md5($input, true))
		);

		// Data
		if (isset($input['fp']))
			$rest->fp =& $input['fp'];
		elseif (isset($input['file']))
			$rest->fp = @fopen($input['file'], 'rb');
		elseif (isset($input['data']))
			$rest->data = $input['data'];

		// Content-Length (required)
		if (isset($input['size']) && $input['size'] > -1)
			$rest->size = $input['size'];
		else {
			if (isset($input['file']))
				$rest->size = filesize($input['file']);
			elseif (isset($input['data']))
				$rest->size = strlen($input['data']);
		}

		// Custom request headers (Content-Type, Content-Disposition, Content-Encoding)
		if (is_array($requestHeaders))
			foreach ($requestHeaders as $h => $v) $rest->setHeader($h, $v);
		elseif (is_string($requestHeaders)) // Support for legacy contentType parameter
			$input['type'] = $requestHeaders;

		// Content-Type
		if (!isset($input['type'])) {
			if (isset($requestHeaders['Content-Type']))
				$input['type'] =& $requestHeaders['Content-Type'];
			elseif (isset($input['file']))
				$input['type'] = self::__getMimeType($input['file'],$uri);
			else
				$input['type'] = 'application/octet-stream';
		}
		
		// We need to post with Content-Length and Content-Type, MD5 is optional
		if ($rest->size > 0 && ($rest->fp !== false || $rest->data !== false)) {
			$rest->setHeader('Content-Type', $input['type']);
			if (isset($input['md5sum'])) $rest->setHeader('Content-MD5', $input['md5sum']);

			$rest->setAmzHeader('x-amz-acl', $acl);
			foreach ($metaHeaders as $h => $v) $rest->setAmzHeader('x-amz-meta-'.$h, $v);
			$rest->getResponse();
		} else
			$rest->response->error = array('code' => 0, 'message' => 'Missing input parameters');

		if ($rest->response->error === false && $rest->response->code !== 200)
			$rest->response->error = array('code' => $rest->response->code, 'message' => 'Unexpected HTTP status');
		if ($rest->response->error !== false) {
			trigger_error(sprintf("S3::putObject(): [%s] %s", $rest->response->error['code'], $rest->response->error['message']), E_USER_WARNING);
			return false;
		}
		return true;
	}


	/**
	* Puts an object from a file (legacy function)
	*
	* @param string $file Input file path
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @param constant $acl ACL constant
	* @param array $metaHeaders Array of x-amz-meta-* headers
	* @param string $contentType Content type
	* @return boolean
	*/
	public static function putObjectFile($file, $bucket, $uri, $acl = self::ACL_PRIVATE, $metaHeaders = array(), $contentType = null) {
		return self::putObject(self::inputFile($file), $bucket, $uri, $acl, $metaHeaders, $contentType);
	}


	/**
	* Put an object from a string (legacy function)
	*
	* @param string $string Input data
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @param constant $acl ACL constant
	* @param array $metaHeaders Array of x-amz-meta-* headers
	* @param string $contentType Content type
	* @return boolean
	*/
	public static function putObjectString($string, $bucket, $uri, $acl = self::ACL_PRIVATE, $metaHeaders = array(), $contentType = 'text/plain') {
		return self::putObject($string, $bucket, $uri, $acl, $metaHeaders, $contentType);
	}


	/**
	* Get an object
	*
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @param mixed &$saveTo Filename or resource to write to
	* @return mixed
	*/
	public static function getObject($bucket, $uri = '', $saveTo = false) {
		$rest = new S3Request('GET', $bucket, $uri);
		if ($saveTo !== false) {
			if (is_resource($saveTo))
				$rest->fp =& $saveTo;
			else
				if (($rest->fp = @fopen($saveTo, 'wb')) == false)
				$rest->response->error = array('code' => 0, 'message' => 'Unable to open save file for writing: '.$saveTo);
		}
		if ($rest->response->error === false) $rest->getResponse();

		if ($rest->response->error === false && $rest->response->code !== 200)
			$rest->response->error = array('code' => $rest->response->code, 'message' => 'Unexpected HTTP status');
		if ($rest->response->error !== false) {
			trigger_error(sprintf("S3::getObject({$bucket}, {$uri}): [%s] %s",
			$rest->response->error['code'], $rest->response->error['message']), E_USER_WARNING);
			return false;
		}
		$rest->file = realpath($saveTo);
		return $rest->response;
	}


	/**
	* Get object information
	*
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @param boolean $returnInfo Return response information
	* @return mixed | false
	*/
	public static function getObjectInfo($bucket, $uri = '', $returnInfo = true) {
		$rest = new S3Request('HEAD', $bucket, $uri);
		$rest = $rest->getResponse();
		if ($rest->error === false && ($rest->code !== 200 && $rest->code !== 404))
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false) {
			trigger_error(sprintf("S3::getObjectInfo({$bucket}, {$uri}): [%s] %s",
			$rest->error['code'], $rest->error['message']), E_USER_WARNING);
			return false;
		}
		return $rest->code == 200 ? $returnInfo ? $rest->headers : true : false;
	}


	/**
	* Copy an object
	*
	* @param string $bucket Source bucket name
	* @param string $uri Source object URI
	* @param string $bucket Destination bucket name
	* @param string $uri Destination object URI
	* @return mixed | false
	*/
	public static function copyObject($srcBucket, $srcUri, $bucket, $uri) {
		$rest = new S3Request('PUT', $bucket, $uri);
		$rest->setAmzHeader('x-amz-copy-source', sprintf('/%s/%s', $srcBucket, $srcUri));
		$rest = $rest->getResponse();
		if ($rest->error === false && $rest->code !== 200)
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false) {
			trigger_error(sprintf("S3::copyObject({$srcBucket}, {$srcUri}, {$bucket}, {$uri}): [%s] %s",
			$rest->error['code'], $rest->error['message']), E_USER_WARNING);
			return false;
		}
		return isset($rest->body->LastModified, $rest->body->ETag) ? array(
			'time' => strtotime((string)$rest->body->LastModified),
			'hash' => substr((string)$rest->body->ETag, 1, -1)
		) : false;
	}


	/**
	* Set logging for a bucket
	*
	* @param string $bucket Bucket name
	* @param string $targetBucket Target bucket (where logs are stored)
	* @param string $targetPrefix Log prefix (e,g; domain.com-)
	* @return boolean
	*/
	public static function setBucketLogging($bucket, $targetBucket, $targetPrefix) {
		$dom = new DOMDocument;
		$bucketLoggingStatus = $dom->createElement('BucketLoggingStatus');
		$bucketLoggingStatus->setAttribute('xmlns', 'http://s3.amazonaws.com/doc/2006-03-01/');

		$loggingEnabled = $dom->createElement('LoggingEnabled');

		$loggingEnabled->appendChild($dom->createElement('TargetBucket', $targetBucket));
		$loggingEnabled->appendChild($dom->createElement('TargetPrefix', $targetPrefix));

		// TODO: Add TargetGrants

		$bucketLoggingStatus->appendChild($loggingEnabled);
		$dom->appendChild($bucketLoggingStatus);

		$rest = new S3Request('PUT', $bucket, '');
		$rest->setParameter('logging', null);
		$rest->data = $dom->saveXML();
		$rest->size = strlen($rest->data);
		$rest->setHeader('Content-Type', 'application/xml');
		$rest = $rest->getResponse();
		if ($rest->error === false && $rest->code !== 200)
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false) {
			trigger_error(sprintf("S3::setBucketLogging({$bucket}, {$uri}): [%s] %s",
			$rest->error['code'], $rest->error['message']), E_USER_WARNING);
			return false;
		}
		return true;
	}


	/**
	* Get logging status for a bucket
	*
	* This will return false if logging is not enabled.
	* Note: To enable logging, you also need to grant write access to the log group
	*
	* @param string $bucket Bucket name
	* @return array | false
	*/
	public static function getBucketLogging($bucket) {
		$rest = new S3Request('GET', $bucket, '');
		$rest->setParameter('logging', null);
		$rest = $rest->getResponse();
		if ($rest->error === false && $rest->code !== 200)
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false) {
			trigger_error(sprintf("S3::getBucketLogging({$bucket}): [%s] %s",
			$rest->error['code'], $rest->error['message']), E_USER_WARNING);
			return false;
		}
		if (!isset($rest->body->LoggingEnabled)) return false; // No logging
		return array(
			'targetBucket' => (string)$rest->body->LoggingEnabled->TargetBucket,
			'targetPrefix' => (string)$rest->body->LoggingEnabled->TargetPrefix,
		);
	}


	/**
	* Get a bucket's location
	*
	* @param string $bucket Bucket name
	* @return string | false
	*/
	public static function getBucketLocation($bucket) {
		$rest = new S3Request('GET', $bucket, '');
		$rest->setParameter('location', null);
		$rest = $rest->getResponse();
		if ($rest->error === false && $rest->code !== 200)
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false) {
			trigger_error(sprintf("S3::getBucketLocation({$bucket}): [%s] %s", $rest->error['code'], $rest->error['message']), E_USER_WARNING);
			return false;
		}
		return (isset($rest->body[0]) && (string)$rest->body[0] !== '') ? (string)$rest->body[0] : 'US';
	}


	/**
	* Set object or bucket Access Control Policy
	*
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @param array $acp Access Control Policy Data (same as the data returned from getAccessControlPolicy)
	* @return boolean
	*/
	public static function setAccessControlPolicy($bucket, $uri = '', $acp = array()) {
		$dom = new DOMDocument;
		$dom->formatOutput = true;
		$accessControlPolicy = $dom->createElement('AccessControlPolicy');
		$accessControlList = $dom->createElement('AccessControlList');

		// It seems the owner has to be passed along too
		$owner = $dom->createElement('Owner');
		$owner->appendChild($dom->createElement('ID', $acp['owner']['id']));
		$owner->appendChild($dom->createElement('DisplayName', $acp['owner']['name']));
		$accessControlPolicy->appendChild($owner);

		foreach ($acp['acl'] as $g) {
			$grant = $dom->createElement('Grant');
			$grantee = $dom->createElement('Grantee');
			$grantee->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
			if (isset($g['id'])) { // CanonicalUser (DisplayName is omitted)
				$grantee->setAttribute('xsi:type', 'CanonicalUser');
				$grantee->appendChild($dom->createElement('ID', $g['id']));
			} elseif (isset($g['email'])) { // AmazonCustomerByEmail
				$grantee->setAttribute('xsi:type', 'AmazonCustomerByEmail');
				$grantee->appendChild($dom->createElement('EmailAddress', $g['email']));
			} elseif ($g['type'] == 'Group') { // Group
				$grantee->setAttribute('xsi:type', 'Group');
				$grantee->appendChild($dom->createElement('URI', $g['uri']));
			}
			$grant->appendChild($grantee);
			$grant->appendChild($dom->createElement('Permission', $g['permission']));
			$accessControlList->appendChild($grant);
		}

		$accessControlPolicy->appendChild($accessControlList);
		$dom->appendChild($accessControlPolicy);

		$rest = new S3Request('PUT', $bucket, $uri);
		$rest->setParameter('acl', null);
		$rest->data = $dom->saveXML();
		$rest->size = strlen($rest->data);
		$rest->setHeader('Content-Type', 'application/xml');
		$rest = $rest->getResponse();
		if ($rest->error === false && $rest->code !== 200)
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false) {
			trigger_error(sprintf("S3::setAccessControlPolicy({$bucket}, {$uri}): [%s] %s",
			$rest->error['code'], $rest->error['message']), E_USER_WARNING);
			return false;
		}
		return true;
	}


	/**
	* Get object or bucket Access Control Policy
	*
	* Currently this will trigger an error if there is no ACL on an object (will fix soon)
	*
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @return mixed | false
	*/
	public static function getAccessControlPolicy($bucket, $uri = '') {
		$rest = new S3Request('GET', $bucket, $uri);
		$rest->setParameter('acl', null);
		$rest = $rest->getResponse();
		if ($rest->error === false && $rest->code !== 200)
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false) {
			trigger_error(sprintf("S3::getAccessControlPolicy({$bucket}, {$uri}): [%s] %s",
			$rest->error['code'], $rest->error['message']), E_USER_WARNING);
			return false;
		}

		$acp = array();
		if (isset($rest->body->Owner, $rest->body->Owner->ID, $rest->body->Owner->DisplayName)) {
			$acp['owner'] = array(
				'id' => (string)$rest->body->Owner->ID, 'name' => (string)$rest->body->Owner->DisplayName
			);
		}
		if (isset($rest->body->AccessControlList)) {
			$acp['acl'] = array();
			foreach ($rest->body->AccessControlList->Grant as $grant) {
				foreach ($grant->Grantee as $grantee) {
					if (isset($grantee->ID, $grantee->DisplayName)) // CanonicalUser
						$acp['acl'][] = array(
							'type' => 'CanonicalUser',
							'id' => (string)$grantee->ID,
							'name' => (string)$grantee->DisplayName,
							'permission' => (string)$grant->Permission
						);
					elseif (isset($grantee->EmailAddress)) // AmazonCustomerByEmail
						$acp['acl'][] = array(
							'type' => 'AmazonCustomerByEmail',
							'email' => (string)$grantee->EmailAddress,
							'permission' => (string)$grant->Permission
						);
					elseif (isset($grantee->URI)) // Group
						$acp['acl'][] = array(
							'type' => 'Group',
							'uri' => (string)$grantee->URI,
							'permission' => (string)$grant->Permission
						);
					else continue;
				}
			}
		}
		return $acp;
	}


	/**
	* Delete an object
	*
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @return mixed
	*/
	public static function deleteObject($bucket, $uri) {
		$rest = new S3Request('DELETE', $bucket, $uri);
		$rest = $rest->getResponse();
		if ($rest->error === false && $rest->code !== 204)
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false) {
			trigger_error(sprintf("S3::deleteObject(): [%s] %s", $rest->error['code'], $rest->error['message']), E_USER_WARNING);
			return false;
		}
		return true;
	}


	/**
	* Get MIME type for file
	*
	* @internal Used to get mime types
	* @param string &$file File path
	* @return string
	*/
	public static function __getMimeType($file, $uri) {
		$type = false;
		$ext = strToLower(pathInfo($file, PATHINFO_EXTENSION));
		
		if ($ext=='tmp' || $ext=='') {   
			//if temp file, try to grab mime type from key
			$file = $uri;
			$ext = strToLower(pathInfo($file, PATHINFO_EXTENSION));
		} else {
			if (extension_loaded('fileinfo') &&
                ($finfo = finfo_open(FILEINFO_MIME_TYPE)) !== false
            ) {
				$type = finfo_file($finfo, $file);
				finfo_close($finfo);
			} 
		}
		/*  
		Seems to have errors
		if ($type === false && function_exists('mime_content_type')) {  
			// If anyone is still using mime_content_type()
			$type = trim(mime_content_type($file));
		}
		*/
		if ($type !== false && strlen($type) > 0) return $type;
		
		// Otherwise do it the old fashioned way
		static $exts = array(
			'jpg' => 'image/jpeg', 'gif' => 'image/gif', 'png' => 'image/png',
            'tif' => 'image/tiff', 'tiff' => 'image/tiff', 'ico' => 'image/x-icon',
            'svg' => 'image/svg+xml', 'jpeg' => 'image/jpeg',
			'swf' => 'application/x-shockwave-flash', 'pdf' => 'application/pdf',
			'zip' => 'application/zip', 'gz' => 'application/x-gzip',
			'tar' => 'application/x-tar', 'bz' => 'application/x-bzip',
			'bz2' => 'application/x-bzip2', 'txt' => 'text/plain',
			'asc' => 'text/plain', 'htm' => 'text/html', 'html' => 'text/html',
			'xml' => 'text/xml', 'xsl' => 'application/xsl+xml',
			'ogg' => 'application/ogg', 'mp3' => 'audio/mpeg', 'wav' => 'audio/x-wav',
			'avi' => 'video/x-msvideo', 'mpg' => 'video/mpeg', 'mpeg' => 'video/mpeg',
			'mov' => 'video/quicktime', 'flv' => 'video/x-flv', 'php' => 'text/x-php',
			'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
		);
		
		return isset($exts[$ext]) ? $exts[$ext] : 'application/octet-stream';
	}


	/**
	* Generate the auth string: "AWS AccessKey:Signature"
	*
	* This uses the hash extension if loaded
	*
	* @internal Signs the request
	* @param string $string String to sign
	* @return string
	*/
	public static function __getSignature($string) {
		return 'AWS '.self::$__accessKey.':'.base64_encode(extension_loaded('hash') ?
		hash_hmac('sha1', $string, self::$__secretKey, true) : pack('H*', sha1(
		(str_pad(self::$__secretKey, 64, chr(0x00)) ^ (str_repeat(chr(0x5c), 64))) .
		pack('H*', sha1((str_pad(self::$__secretKey, 64, chr(0x00)) ^
		(str_repeat(chr(0x36), 64))) . $string)))));
	}
	public static function __getSign($string) {
		return base64_encode(extension_loaded('hash') ?
		hash_hmac('sha1', $string, self::$__secretKey, true) : pack('H*', sha1(
		(str_pad(self::$__secretKey, 64, chr(0x00)) ^ (str_repeat(chr(0x5c), 64))) .
		pack('H*', sha1((str_pad(self::$__secretKey, 64, chr(0x00)) ^
		(str_repeat(chr(0x36), 64))) . $string)))));
	}
	//added
	public function queryStringGet($bucket, $key, $expires){
		$expires = time() + $expires;
		$stringToSign = "GET\n\n\n$expires\n/$bucket/$key";
		$signature = urlencode(self::__getSign($stringToSign));
		$queryString = "https://$bucket.s3.amazonaws.com/$key?AWSAccessKeyId=".self::$__accessKey."&Expires=$expires&Signature=$signature";
		return $queryString;
	}
}



final class S3Request {
	private $verb, $bucket, $uri, $resource = '', $parameters = array(),
	$amzHeaders = array(), $headers = array(
		'Host' => '', 'Date' => '', 'Content-MD5' => '', 'Content-Type' => ''
	);
	public $fp = false, $size = 0, $data = false, $response;


	/**
	* Constructor
	*
	* @param string $verb Verb
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @return mixed
	*/
	function __construct($verb, $bucket = '', $uri = '') {
		$this->verb = $verb;
		$this->bucket = strtolower($bucket);
		$this->uri = $uri !== '' ? '/'.$uri : '/';

		if ($this->bucket !== '') {
			$this->resource = '/'.$this->bucket.$this->uri;
			$this->headers['Host'] = $this->bucket.'.s3.amazonaws.com';
		} else {
			$this->headers['Host'] = 's3.amazonaws.com';
			$this->resource = strlen($this->uri) > 1 ? '/'.$this->bucket.$this->uri : $this->uri;
		}
		$this->headers['Date'] = gmdate('D, d M Y H:i:s T');

		$this->response = new STDClass;
		$this->response->error = false;
	}


	/**
	* Set request parameter
	*
	* @param string $key Key
	* @param string $value Value
	* @return void
	*/
	public function setParameter($key, $value) {
		$this->parameters[$key] = $value;
	}


	/**
	* Set request header
	*
	* @param string $key Key
	* @param string $value Value
	* @return void
	*/
	public function setHeader($key, $value) {
		$this->headers[$key] = $value;
	}


	/**
	* Set x-amz-meta-* header
	*
	* @param string $key Key
	* @param string $value Value
	* @return void
	*/
	public function setAmzHeader($key, $value) {
		$this->amzHeaders[$key] = $value;
	}


	/**
	* Get the S3 response
	*
	* @return object | false
	*/
	public function getResponse() {
		$query = '';
		if (sizeof($this->parameters) > 0) {
			$query = substr($this->uri, -1) !== '?' ? '?' : '&';
			foreach ($this->parameters as $var => $value)
				if ($value == null || $value == '') $query .= $var.'&';
				else $query .= $var.'='.$value.'&';
			$query = substr($query, 0, -1);
			$this->uri .= $query;
			
			if (array_key_exists('acl', $this->parameters) ||
			array_key_exists('location', $this->parameters) ||
			array_key_exists('torrent', $this->parameters) ||
			array_key_exists('logging', $this->parameters))
				$this->resource .= $query;

		}
		$url = ((S3::$useSSL && extension_loaded('openssl')) ?
		'https://':'http://').$this->headers['Host'].$this->uri;
		//var_dump($this->bucket, $this->uri, $this->resource, $url);

		// Basic setup
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_USERAGENT, 'S3/php');
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_URL, $url);

		// Headers
		$headers = array(); $amz = array();
		foreach ($this->amzHeaders as $header => $value)
			if (strlen($value) > 0) $headers[] = $header.': '.$value;
		foreach ($this->headers as $header => $value)
			if (strlen($value) > 0) $headers[] = $header.': '.$value;
		
		// Collect AMZ headers for signature
		foreach ($this->amzHeaders as $header => $value)
			if (strlen($value) > 0) $amz[] = strToLower($header).':'.$value;

		// AMZ headers must be sorted (thanks Malone)
		if (sizeof($amz) > 0) {
			sort($amz);
			$amz = "\n".implode("\n", $amz);
		} else $amz = '';

		// Authorization string
		$headers[] = 'Authorization: ' . S3::__getSignature(
			$this->verb."\n".
			$this->headers['Content-MD5']."\n".
			$this->headers['Content-Type']."\n".
			$this->headers['Date'].$amz."\n".$this->resource
		);

		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
		curl_setopt($curl, CURLOPT_WRITEFUNCTION, array(&$this, '__responseWriteCallback'));
		curl_setopt($curl, CURLOPT_HEADERFUNCTION, array(&$this, '__responseHeaderCallback'));
		@curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

		// Request types
		switch ($this->verb) {
			case 'GET': break;
			case 'PUT':
				if ($this->fp !== false) {
					curl_setopt($curl, CURLOPT_PUT, true);
					curl_setopt($curl, CURLOPT_INFILE, $this->fp);
					if ($this->size > 0)
						curl_setopt($curl, CURLOPT_INFILESIZE, $this->size);
				} elseif ($this->data !== false) {
					curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
					curl_setopt($curl, CURLOPT_POSTFIELDS, $this->data);
					if ($this->size > 0)
						curl_setopt($curl, CURLOPT_BUFFERSIZE, $this->size);
				} else
					curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
			break;
			case 'HEAD':
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'HEAD');
				curl_setopt($curl, CURLOPT_NOBODY, true);
			break;
			case 'DELETE':
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
			break;
			default: break;
		}

		// Execute, grab errors
		if (curl_exec($curl))
			$this->response->code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		else
			$this->response->error = array(
				'code' => curl_errno($curl),
				'message' => curl_error($curl),
				'resource' => $this->resource
			);

		@curl_close($curl);

		// Parse body into XML
		if ($this->response->error === false && isset($this->response->headers['type']) &&
		$this->response->headers['type'] == 'application/xml' && isset($this->response->body)) {
			$this->response->body = simplexml_load_string($this->response->body);

			// Grab S3 errors
			if (!in_array($this->response->code, array(200, 204)) &&
			isset($this->response->body->Code, $this->response->body->Message)) {
				$this->response->error = array(
					'code' => (string)$this->response->body->Code,
					'message' => (string)$this->response->body->Message
				);
				if (isset($this->response->body->Resource))
					$this->response->error['resource'] = (string)$this->response->body->Resource;
				unset($this->response->body);
			}
		}

		// Clean up file resources
		if ($this->fp !== false && is_resource($this->fp)) fclose($this->fp);

		return $this->response;
	}


	/**
	* CURL write callback
	*
	* @param resource &$curl CURL resource
	* @param string &$data Data
	* @return integer
	*/
	private function __responseWriteCallback(&$curl, &$data) {
		if ($this->response->code == 200 && $this->fp !== false)
			return fwrite($this->fp, $data);
		else
			$this->response->body .= $data;
		return strlen($data);
	}


	/**
	* CURL header callback
	*
	* @param resource &$curl CURL resource
	* @param string &$data Data
	* @return integer
	*/
	private function __responseHeaderCallback(&$curl, &$data) {
		if (($strlen = strlen($data)) <= 2) return $strlen;
		if (substr($data, 0, 4) == 'HTTP')
			$this->response->code = (int)substr($data, 9, 3);
		else {
			list($header, $value) = explode(': ', trim($data), 2);
			if ($header == 'Last-Modified')
				$this->response->headers['time'] = strtotime($value);
			elseif ($header == 'Content-Length')
				$this->response->headers['size'] = (int)$value;
			elseif ($header == 'Content-Type')
				$this->response->headers['type'] = $value;
			elseif ($header == 'ETag')
				$this->response->headers['hash'] = substr($value, 1, -1);
			elseif (preg_match('/^x-amz-meta-.*$/', $header))
				$this->response->headers[$header] = is_numeric($value) ? (int)$value : $value;
		}
		return $strlen;
	}

}
