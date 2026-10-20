<?php
// IMathAS: Signed-URL proxy for private files
// (c) 2026 David Lippman
//
// Parameters:
//   k  file key (e.g. ufiles/12/pic.png)
//   s  signature
//   u  userid (user-signed; must match the logged-in user)   e  expiry timestamp
//   m  mode: pub or perm; omitted = user-signed
//
// A pre-signature (key only) is never accepted here; filter() converts those
// into user/public signatures at render time.

require_once __DIR__ . "/includes/sanitize.php";
if (!file_exists(__DIR__ . "/config.php")) {
	getfile_fail(404);
}
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/includes/filehandler.php";

function getfile_fail($code = 404) {
	http_response_code($code);
	header('Content-Type: text/plain');
	header('X-Content-Type-Options: nosniff');
	echo $code == 403 ? 'Forbidden' : 'Not found';
	exit;
}

// Extensions always forced to download rather than render in our origin
$getfile_attachment = ['html', 'htm', 'xhtml', 'xml', 'js', 'swf', 'mjs', 'xht', 'shtml'];
$getfile_types = [
	'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif',
	'webp' => 'image/webp', 'svg' => 'image/svg+xml', 'bmp' => 'image/bmp',
	'pdf' => 'application/pdf', 'txt' => 'text/plain', 'csv' => 'text/csv',
	'mp3' => 'audio/mpeg', 'wav' => 'audio/wav', 'm4a' => 'audio/mp4', 'ogg' => 'audio/ogg',
	'mp4' => 'video/mp4', 'webm' => 'video/webm', 'mov' => 'video/quicktime',
	'zip' => 'application/zip',
];

$key = isset($_GET['k']) && is_string($_GET['k']) ? $_GET['k'] : '';
$sig = isset($_GET['s']) && is_string($_GET['s']) ? $_GET['s'] : '';
$mode = isset($_GET['m']) && is_string($_GET['m']) ? $_GET['m'] : 'user';

try {
	$prefix = filesig_validkey($key);
	if ($prefix === false || $sig === '') {
		getfile_fail(403);
	}
	// course files are only served here once the course-files extension is enabled
	if ($prefix == 'cfiles' && empty($GLOBALS['CFG']['GEN']['privcoursefiles'])) {
		getfile_fail(403);
	}
	$params = ['key' => $key, 'sig' => $sig];
	if ($mode === 'pub') {
		$params['exp'] = $_GET['e'] ?? '';
		$ok = filesig_verify('public', $params);
	} else if ($mode === 'perm') {
		$ok = filesig_verify('perm', $params);
	} else if ($mode === 'user') {
		$params['uid'] = $_GET['u'] ?? '';
		$params['exp'] = $_GET['e'] ?? '';
		$ok = filesig_verify('user', $params);
	} else {
		$ok = false;
	}
} catch (RuntimeException $e) {
	error_log('getfile.php: ' . $e->getMessage());
	getfile_fail(403);
}
if (!$ok) {
	getfile_fail(403);
}
if ($mode === 'user') {
	// user-signed URLs only work for the signed-in user they were issued to.
	// (public/perm signatures are deliberately session-less)
	$init_skip_validate = true;
	$init_session_start = true;
	require_once __DIR__ . "/init.php";
	$sessuserid = $_SESSION['userid'] ?? 0;
	session_write_close();
	header_remove('Pragma'); // session_start() adds no-cache headers; we set our own below
	header_remove('Expires');
	if (empty($sessuserid) || intval($sessuserid) !== intval($_GET['u'])) {
		getfile_fail(403);
	}
}

$ext = strtolower(pathinfo($key, PATHINFO_EXTENSION));
$filename = basename($key);
$isattach = in_array($ext, $getfile_attachment, true);

if (getfilehandlertype('filehandlertype') == 's3') {
	$s3 = new S3($GLOBALS['AWSkey'], $GLOBALS['AWSsecret']);
	$url = $s3->queryStringGet($GLOBALS['AWSbucket'], $key, 300);
	header('Cache-Control: private, no-store');
	header('Referrer-Policy: no-referrer');
	header('Location: ' . $url, true, 302);
	exit;
}

// Local storage
$path = getlocalprivfilepath($key);
if ($path === false) {
	getfile_fail(404);
}
$size = filesize($path);
$ctype = $getfile_types[$ext] ?? 'application/octet-stream';
if ($isattach) {
	$ctype = 'application/octet-stream';
}
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=600');
header('Content-Type: ' . $ctype);
header('Accept-Ranges: bytes');
if ($ext == 'svg') {
	header("Content-Security-Policy: sandbox; default-src 'none'; style-src 'unsafe-inline'; img-src data:");
}
$safename = str_replace(['"', "\r", "\n", '\\'], '', $filename);
header('Content-Disposition: ' . ($isattach || $ctype == 'application/octet-stream' ? 'attachment' : 'inline') .
	'; filename="' . $safename . '"; filename*=UTF-8\'\'' . rawurlencode($filename));

$start = 0;
$end = $size - 1;
if (isset($_SERVER['HTTP_RANGE']) && preg_match('/^bytes=(\d*)-(\d*)$/', trim($_SERVER['HTTP_RANGE']), $m) && ($m[1] !== '' || $m[2] !== '')) {
	if ($m[1] === '') { // suffix range
		$start = max(0, $size - intval($m[2]));
	} else {
		$start = intval($m[1]);
		if ($m[2] !== '') {
			$end = min($end, intval($m[2]));
		}
	}
	if ($start > $end || $start >= $size) {
		http_response_code(416);
		header('Content-Range: bytes */' . $size);
		exit;
	}
	http_response_code(206);
	header("Content-Range: bytes $start-$end/$size");
}
header('Content-Length: ' . ($end - $start + 1));

while (ob_get_level() > 0) {
	ob_end_clean();
}
if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
	exit;
}
$fh = fopen($path, 'rb');
if ($fh === false) {
	getfile_fail(404);
}
fseek($fh, $start);
$remaining = $end - $start + 1;
while ($remaining > 0 && !feof($fh)) {
	$chunk = fread($fh, min(8192 * 8, $remaining));
	if ($chunk === false) { break; }
	echo $chunk;
	$remaining -= strlen($chunk);
	flush();
}
fclose($fh);
