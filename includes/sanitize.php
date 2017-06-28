<?php

class Sanitize
{

	private static $blacklistedFilenames = array(
		'/^\./',
		'/\.php\d?($|\.)/',
		'/\.bat($|\.)/',
		'/\.com($|\.)/',
		'/\.exe($|\.)/',
		'/\.pl($|\.)/',
		'/\.ph\d($|\.)/',
		'/\.phtml?($|\.)/',
		'/\.sh($|\.)/',
		'/\.asp($|\.)/',
		'/\.p($|\.)/'
	);

	/**
	 * Sanitize a filename and check it against a blacklist. Request processing is halted
	 * if the filename exists in the blacklist.
	 *
	 * @param $uncleanFilename string The filename to sanitize and check.
	 * @return string A sanitized filename.
	 */
	public static function sanitizeFilenameAndCheckBlacklist($uncleanFilename)
	{
		$safeFilename = preg_replace('/[^\da-z\._\-]/i', '', $uncleanFilename);

		if (self::isFilenameBlacklisted($safeFilename)) {
			print("Invalid filename used! Halting.\n");
			// Normally, an exception would be thrown here, but we don't have exception handling. Yet! :)
			exit;
		}

		return $safeFilename;
	}


	/**
	 * Sanitize a file path and and check  the filenameagainst a blacklist.
	 * Request processing is halted if the filename exists in the blacklist.
	 *
	 * @param $uncleanPath string The file path to sanitize and check.
	 *   example:  ufiles/1/filename.doc
	 * @return string A sanitized file path.
	 */
	public static function sanitizeFilePathAndCheckBlacklist($uncleanPath)
	{
		$saferFilePath = preg_replace('/[^\da-z\._\-\/]/i', '', $uncleanPath);
		//prevent ../ paths
		$cnt = 1;
		while ($cnt>0) {  //repeat until there are no more
			$saferFilePath = str_replace('../','',$saferFilePath,$cnt);
		}

		if (self::isFilenameBlacklisted(basename($saferFilePath)) || basename($saferFilePath)=='') {
			print("Invalid filename used! Halting.\n");
			// Normally, an exception would be thrown here, but we don't have exception handling. Yet! :)
			exit;
		}

		return $saferFilePath;
	}

	/**
	 * Check a filename to see if it's blacklisted.
	 *
	 * @param $filenameToCheck string The filename to check.
	 * @return bool False if not blacklisted, true if blacklisted.
	 */
	public static function isFilenameBlacklisted($filenameToCheck)
	{
		$filenameToCheck = strtolower($filenameToCheck);
		foreach (self::$blacklistedFilenames as $blacklistedFilename) {
			if (preg_match($blacklistedFilename, $filenameToCheck)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Encode a string for display in a web browser. Use for page text and HTML attributes only!
	 *
	 * This method will not double-encode existing HTML entities.
	 *
	 * @see encodeStringForJavascript
	 * @see encodeStringForCSS
	 *
	 * @param $string string The string to encode.
	 * @return string the encoded string.
	 */
	public static function encodeStringForDisplay($string)
	{
		return htmlspecialchars($string, ENT_QUOTES | ENT_HTML401, ini_get("default_charset"), false);
	}

	/**
	 * Encode a string for use in blocks of JavaScript, or in-line JavaScript.
	 *
	 * @see encodeStringForDisplay
	 * @see encodeStringForCSS
	 *
	 * @param $string string The string to encode.
	 * @return string the encoded string.
	 */
	public static function encodeStringForJavascript($string)
	{
		$safeString = '';

		$stringLength = strlen($string);
		for ($i = 0; $i < $stringLength; $i++) {
			$char = substr($string, $i, 1);
			$safeString .= preg_match("/[\da-z]/i", $char) ? $char : '\\x' . dechex(ord($char));
		}

		return $safeString;
	}

	/**
	 * Encode a string for use in blocks of CSS, or in-line CSS.
	 *
	 * @see encodeStringForDisplay
	 * @see encodeStringForJavascript
	 *
	 * @param $string string The string to encode.
	 * @return string the encoded string.
	 */
	public static function encodeStringForCSS($string)
	{
		$safeString = '';

		$stringLength = strlen($string);
		for ($i = 0; $i < $stringLength; $i++) {
			$char = substr($string, $i, 1);
			$safeString .= preg_match("/[\da-z]/i", $char) ? $char : '\\' . dechex(ord($char));
		}

		return $safeString;
	}

	/**
	 * Encode a string for use in a URL to be clicked on. Use for query parameters only!
	 *
	 * This method will not double-encode a string.
	 *
	 * @param $string string The string to encode.
	 * @return string The encoded string.
	 */
	public static function encodeUrlParam($string)
	{
		$decoded = urldecode($string);
		return urlencode($decoded);
	}

	/**
	 * Encodes all characters except -_.~/ with %hex
	 * Equivalent to rawurlencode, except it doesn't encode /
	 *
	 * @param $string string The path to sanitize.
	 * @return string The sanitized string.
	 */
	public static function rawurlencodePath($string)
	{
		return implode("/", array_map("rawurlencode", explode("/", $string)));
	}

	/**
	 * Sanitize a full URL string. This should include the protocol (http/https), port, path,
	 * and any query parameters.
	 *
	 * @param $string string The URL to sanitize.
	 * @return string The sanitized URL string.
	 */
	public static function fullUrl($string)
	{
		// Valid characters: https://www.ietf.org/rfc/rfc3986.txt
		return preg_replace('/[^\da-z\._~:\/?#\[\]%@!$&\'()*+,;=-]/i', '', $string);
	}

	/**
	 * Sanitize a complete URL query string. (Everything after and without the '?' character in a URL)
	 *
	 *
	 * Example input: "name=MyName&cid=994&color=blue"
	 *
	 *  Do NOT use this for a string like "name=$name&color=$color", as it
	 *  does not encode query parameters
	 *
	 * @param $string string The entire query string.
	 * @return string The encoded query string.
	 */
	public static function fullQueryString($string)
	{
		return self::fullUrl($string);
	}

	/**
	 * Remove HTML tags from a string.
	 *
	 * @param $string string The string to remove HTML tags from.
	 * @return string The string with HTML tags removed.
	 */
	public static function stripHtmlTags($string)
	{
		//changed to strip_tags since FILTER_SANITIZE_STRING removes
		//anything following a < symbol, which is overkill
		
		//return filter_var($string, FILTER_SANITIZE_STRING);
		return strip_tags($string);
	}

	/**
	 * Sanitize data so it only contains an integer value.
	 *
	 * @param $data mixed A variable containing a number.
	 * @return int A sanitized variable containing only an integer.
	 */
	public static function onlyInt($data)
	{
		return filter_var($data, FILTER_SANITIZE_NUMBER_INT);
	}

	/**
	 * Sanitize data so it only contains a float value.
	 *
	 * @param $data mixed A variable containing a number.
	 * @return float A sanitized variable containing only a float.
	 */
	public static function onlyFloat($data)
	{
		return filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
	}

	/**
	 * Sanitize a domain name without a port.
	 *
	 * @see Sanitize::domainNameWithPort()
	 *
	 * @param $domainName string The domain name.
	 * @return string The sanitized domain name.
	 */
	public static function domainNameWithoutPort($domainName)
	{
		return preg_replace('/[^\da-z\.-]/i', '', $domainName);
	}

	/**
	 * Sanitize a domain name with a port.
	 *
	 * The allowed format is: "domainOrHostname:portNumber"
	 *
	 * @see Sanitize::domainNameWithoutPort()
	 *
	 * @param $domainName string The domain name and port.
	 * @return string The sanitized domain name and port.
	 */
	public static function domainNameWithPort($domainName)
	{
		// Remove non-acceptable characters.
		$cleanDomainName = preg_replace('/[^\da-z\.:-]/i', '', $domainName);

		// Only allow one colon or domain strings without a port.
		$matches = null;
		preg_match('/([\da-z\.-]+:\d+|[\da-z\.-]+)/i', $cleanDomainName, $matches);

		if (null == $matches || 2 != count($matches)) {
			error_log(sprintf(": A valid domain string could not be found in \"%s\"",
				__METHOD__, $domainName));
			// Normally, an exception would be thrown here, but we don't have exception handling. Yet! :)
			return null;
		}

		return $matches[1];
	}

	/**
	 * Sanitize an email address.
	 *
	 * @param $address string An email address.
	 * @return string The sanitized email address.
	 */
	public static function emailAddress($address)
	{
		return filter_var($address, FILTER_SANITIZE_EMAIL);
	}

	/**
	 * Sanitize a course ID. Valid values are either an integer
	 *
	 * @param $courseId mixed A course ID, which may contain an integer or "admin".
	 * @return mixed A sanitized course ID or "admin". An empty string if neither are found.
	 */
	public static function courseId($courseId)
	{
		if ("admin" == strtolower(trim($courseId))) {
			return "admin";
		} else {
			return self::onlyInt($courseId);
		}
	}

	/**
	 * Generate a string containing SQL query placeholders based on the number of
	 * elements in an array.
	 *
	 * Example return string: "?, ?, ?, ?, ?" (note the lack of outer parenthesis)
	 *
	 * @see generateQueryPlaceholdersGrouped
	 *
	 * @param $array array The array to count for the number of placeholders to generate.
	 * @return string A string containing question marks, to be used in SQL queries.
	 */
	public static function generateQueryPlaceholders($array)
	{
		$query_placeholders = str_repeat('?, ', count($array) - 1) . '?';

		return $query_placeholders;
	}

	/**
	 * Generate a string containing grouped SQL query placeholders based on the number of
	 * elements in an array.
	 *
	 * For null elements, pass in a literal null value. (not a "NULL" string)
	 *
	 * Example return string: "(?, ?, ?), (?, ?, ?), (?, ?, ?)" (note the lack of outer parenthesis)
	 *
	 * @see generateQueryPlaceholders
	 *
	 * @param $array array The array to count for the number of placeholders to generate.
	 *                     This should be a single dimension array.
	 * @param $groupSize integer The size of each group of query placeholders.
	 * @return string A string containing question marks, to be used in SQL queries.
	 *                  Null is returned if the array size is not divisible by $groupSize.
	 */
	public static function generateQueryPlaceholdersGrouped($array, $groupSize)
	{
		if (0 != count($array) % $groupSize) {
			error_log(sprintf(": Array length (%d) is not divisible by group size (%d).",
				__METHOD__, count($array), $groupSize));
			// Normally, an exception would be thrown here, but we don't have exception handling. Yet! :)
			return null;
		}

		$placeholders = "";

		for ($i = 0; $i < count($array); $i += $groupSize) {
			$placeholders .= "(" . str_repeat('?, ', $groupSize - 1) . "?)";
			// Insert a comma between groups.
			if ($i < count($array) - $groupSize) {
				$placeholders .= ", ";
			}
		}

		return $placeholders;
	}

}
