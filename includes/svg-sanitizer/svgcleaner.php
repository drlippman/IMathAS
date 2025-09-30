<?php

/*
 * Simple program that uses svg-sanitizer
 * to find issues in files specified on the
 * command line, and prints a JSON output with
 * the issues found on exit.
 */

require_once( __DIR__ . '/data/AttributeInterface.php' );
require_once( __DIR__ . '/data/TagInterface.php' );
require_once( __DIR__ . '/data/AllowedAttributes.php' );
require_once( __DIR__ . '/data/AllowedTags.php' );
require_once( __DIR__ . '/data/XPath.php' );
require_once( __DIR__ . '/ElementReference/Resolver.php' );
require_once( __DIR__ . '/ElementReference/Subject.php' );
require_once( __DIR__ . '/ElementReference/Usage.php' );
require_once( __DIR__ . '/Exceptions/NestingException.php' );
require_once( __DIR__ . '/Helper.php' );
require_once( __DIR__ . '/SvgSanitizer.php' );

use enshrined\svgSanitize\SvgSanitizer;

function sanitizeSvg($fileinfo) {
	if (preg_match('/\.svg$/', $fileinfo['name'])) {
		$sanitizer = new SvgSanitizer();
		$sanitizer->removeRemoteReferences(true);
		$dirtySVG = @file_get_contents($fileinfo['tmp_name']);
		if ($dirtySVG !== false) {
			$cleanSVG = $sanitizer->sanitize($dirtySVG);
			file_put_contents($fileinfo['tmp_name'], $cleanSVG);
		}
	}
}