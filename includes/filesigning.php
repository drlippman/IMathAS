<?php
// Signing helpers for private file access via getfile.php.
// Pure functions (no DB/session) so they can be unit tested.
//
// Signature types (each hashed string starts with its own type prefix so one
// type can't be replayed as another):
//   pre|KEY                 pre-signature; no expiry, signed with filepresecret.
//                           Never grants access; filter() converts it.
//   usr|KEY|UID|EXP         user signature
//   pub|KEY|EXP             public-page signature (no user)
//   perm|KEY                permanent signature (CC export links)
//   ctx|CONTEXT|CID|UID|EXP upload-context token (not used by getfile.php)

define('FILESIG_USER_LIFETIME', 3*24*3600);
define('FILESIG_EXPIRY_GRANULARITY', 3600); // round expiry so URLs are stable (cacheable) within an hour
define('FILESIG_CTX_LIFETIME', 6*3600);
define('FILESIG_CTX_ASSESS_LIFETIME', 48*3600); // assessment pages can stay open a long time

/**
 * Get configured secrets as an array (current first, then previous ones).
 * @param string $which 'pre' or 'sig'
 * @return array of non-empty strings
 * @throws RuntimeException if not configured
 */
function filesig_getsecrets($which) {
	$name = ($which === 'pre') ? 'filepresecret' : 'filesigsecret';
	$val = $GLOBALS['CFG']['GEN'][$name] ?? null;
	if ($which === 'pre' && is_array($val)) {
		$val = $val[0] ?? null; // pre-signature secret is never rotated
	}
	$arr = is_array($val) ? array_values($val) : [$val];
	$arr = array_values(array_filter($arr, function ($v) {
		return is_string($v) && strlen($v) >= 16;
	}));
	if (count($arr) == 0) {
		throw new RuntimeException('File signing secret $CFG[\'GEN\'][\'' . $name . '\'] is not set (min 16 chars). See the upgrade notes.');
	}
	return $arr;
}

function filesig_hmac($str, $secret) {
	return hash_hmac('sha256', $str, $secret);
}

function filesig_expiry($lifetime = FILESIG_USER_LIFETIME, $now = null) {
	if ($now === null) { $now = time(); }
	return (int) (ceil(($now + $lifetime) / FILESIG_EXPIRY_GRANULARITY) * FILESIG_EXPIRY_GRANULARITY);
}

function filesig_presign($key) {
	return filesig_hmac('pre|' . $key, filesig_getsecrets('pre')[0]);
}
function filesig_verifypre($key, $sig) {
	return is_string($sig) && hash_equals(filesig_presign($key), $sig);
}

function filesig_usersign($key, $uid, $exp, $secret = null) {
	$secret = $secret ?? filesig_getsecrets('sig')[0];
	return filesig_hmac('usr|' . $key . '|' . intval($uid) . '|' . intval($exp), $secret);
}
function filesig_publicsign($key, $exp, $secret = null) {
	$secret = $secret ?? filesig_getsecrets('sig')[0];
	return filesig_hmac('pub|' . $key . '|' . intval($exp), $secret);
}
function filesig_permsign($key, $secret = null) {
	$secret = $secret ?? filesig_getsecrets('sig')[0];
	return filesig_hmac('perm|' . $key, $secret);
}
function filesig_ctxsign($context, $cid, $uid, $exp, $secret = null) {
	$secret = $secret ?? filesig_getsecrets('sig')[0];
	return filesig_hmac('ctx|' . $context . '|' . intval($cid) . '|' . intval($uid) . '|' . intval($exp), $secret);
}

/**
 * Verify a signature against any configured sig secret
 * @param string $mode 'user','public','perm'
 * @param array $p  key, sig, and as needed uid, exp
 * @param int|null $now
 * @return bool
 */
function filesig_verify($mode, $p, $now = null) {
	if ($now === null) { $now = time(); }
	$sig = $p['sig'] ?? '';
	$key = $p['key'] ?? '';
	if (!is_string($sig) || !is_string($key) || $sig === '' || $key === '') {
		return false;
	}
	if ($mode === 'user' || $mode === 'public') {
		if (!isset($p['exp']) || !ctype_digit((string) $p['exp']) || intval($p['exp']) < $now) {
			return false;
		}
	}
	if ($mode === 'user' && (!isset($p['uid']) || !ctype_digit((string) $p['uid']))) {
		return false;
	}
	foreach (filesig_getsecrets('sig') as $secret) {
		if ($mode === 'user') {
			$expected = filesig_usersign($key, $p['uid'], $p['exp'], $secret);
		} else if ($mode === 'public') {
			$expected = filesig_publicsign($key, $p['exp'], $secret);
		} else if ($mode === 'perm') {
			$expected = filesig_permsign($key, $secret);
		} else {
			return false;
		}
		if (hash_equals($expected, $sig)) {
			return true;
		}
	}
	return false;
}

/**
 * Key validation: strict charset, no .., no leading /, no NUL.
 * Returns the store prefix ('ufiles','ffiles','adata','cfiles') or false.
 */
function filesig_validkey($key) {
	if (!is_string($key) || $key === '' || strlen($key) > 300) { return false; }
	if (!preg_match('#^[A-Za-z0-9_\-./ ()\[\],;:~]+$#', $key)) { return false; }
	if (strpos($key, '..') !== false || $key[0] === '/' || substr($key, -1) === '/') { return false; }
	if (strpos($key, '//') !== false) { return false; }
	$prefix = strstr($key, '/', true);
	if (!in_array($prefix, ['ufiles', 'ffiles', 'adata', 'cfiles'], true)) { return false; }
	return $prefix;
}

/**
 * Upload-context token: the server grants "store publicly" to a course-page
 * editor. Opaque value: base64url(context|cid|uid|exp) . '.' . sig
 */
function filesig_makectxtoken($context, $cid, $uid, $now = null) {
	$lifetime = ($context === 'assess') ? FILESIG_CTX_ASSESS_LIFETIME : FILESIG_CTX_LIFETIME;
	$exp = ($now ?? time()) + $lifetime;
	$sig = filesig_ctxsign($context, $cid, $uid, $exp);
	return $context . '.' . intval($cid) . '.' . intval($uid) . '.' . $exp . '.' . $sig;
}
/**
 * @param bool $allowexpired if true, a correctly signed token for this user is returned
 *   even if expired (lets callers fail closed rather than fall back to less-restricted behavior)
 * @return array|false [context,cid] if valid for this user and unexpired
 */
function filesig_verifyctxtoken($token, $uid, $now = null, $allowexpired = false) {
	if ($now === null) { $now = time(); }
	if (!is_string($token)) { return false; }
	$parts = explode('.', $token);
	if (count($parts) != 5) { return false; }
	list($context, $cid, $tuid, $exp, $sig) = $parts;
	if (!preg_match('/^\w+$/', $context) || !ctype_digit($cid) || !ctype_digit($tuid) || !ctype_digit($exp)) {
		return false;
	}
	if (intval($tuid) !== intval($uid) || (!$allowexpired && intval($exp) < $now)) { return false; }
	foreach (filesig_getsecrets('sig') as $secret) {
		if (hash_equals(filesig_ctxsign($context, $cid, $tuid, $exp, $secret), $sig)) {
			return [$context, intval($cid)];
		}
	}
	return false;
}

/**
 * Pre-signed URL stored in editor HTML (relative to imasroot).
 * The key is deliberately the LAST query parameter so that the URL ends with the
 * filename (clients that show/delete by the last path segment keep working).
 */
function filesig_presignedurl($key, $imasroot) {
	return $imasroot . '/getfile.php?s=' . filesig_presign($key) . '&k=' . Sanitize::rawurlencodePath($key);
}

/**
 * Build a signed getfile.php URL
 * @param string $mode 'user','public','perm'
 */
function filesig_signedurl($key, $mode, $imasroot, $uid = 0, $now = null) {
	$k = '&k=' . Sanitize::rawurlencodePath($key);
	$url = $imasroot . '/getfile.php?';
	if ($mode === 'user') {
		$exp = filesig_expiry(FILESIG_USER_LIFETIME, $now);
		return $url . 'u=' . intval($uid) . '&e=' . $exp . '&s=' . filesig_usersign($key, $uid, $exp) . $k;
	} else if ($mode === 'public') {
		$exp = filesig_expiry(FILESIG_USER_LIFETIME, $now);
		return $url . 'm=pub&e=' . $exp . '&s=' . filesig_publicsign($key, $exp) . $k;
	} else if ($mode === 'perm') {
		return $url . 'm=perm&s=' . filesig_permsign($key) . $k;
	}
	return false;
}

// Matches a getfile.php URL (optionally absolute) and captures its query string
define('FILESIG_URL_REGEX', '~(?:https?://[^/"\'\s<>]+)?[\w\-./]*?/getfile\.php\?((?:(?!&quot;|&#0?39;|&lt;|&gt;)[^"\'\s<>])+)~');

/**
 * Parse the query string of a matched getfile.php URL
 * @return array|false  with key (decoded), s, and optional u,e,m; false if malformed
 */
function filesig_parsequery($qs) {
	parse_str(str_replace(['&amp;amp;', '&amp;'], '&', $qs), $q);
	if (!isset($q['k'], $q['s']) || !is_string($q['k']) || !is_string($q['s'])) { return false; }
	if (!preg_match('/^[0-9a-f]{64}$/', $q['s'])) { return false; }
	foreach (['u', 'e', 'm'] as $f) {
		if (isset($q[$f]) && !is_string($q[$f])) { return false; }
	}
	return $q;
}

/**
 * Reverse of filesig_rewritehtml: turn validly signed getfile.php URLs (user, public, or
 * perm; expired is fine) back into stored pre-signed form.  Used on incoming HTML so that
 * content round-tripped through an editor does not store expiring URLs.
 * URLs whose signature does not verify are left alone.  User-signed URLs are only
 * converted if signed for $uid, so a user can't launder a URL signed for someone else.
 */
function filesig_presignhtml($html, $imasroot, $uid = 0) {
	if (strpos($html, 'getfile.php?') === false) { return $html; }
	return preg_replace_callback(
		FILESIG_URL_REGEX,
		function ($m) use ($imasroot, $uid) {
			$q = filesig_parsequery($m[1]);
			if ($q === false || !isset($q['u']) && !isset($q['m'])) { return $m[0]; } // plain pre-signed or junk
			$key = $q['k'];
			if (filesig_validkey($key) === false) { return $m[0]; }
			$mode = $q['m'] ?? 'user';
			$mode = ($mode === 'pub') ? 'public' : $mode;
			if (!in_array($mode, ['user', 'public', 'perm'], true)) { return $m[0]; }
			if (isset($q['e']) && !ctype_digit($q['e'])) { return $m[0]; }
			if ($mode === 'user' && (!isset($q['u']) || !ctype_digit($q['u']) || intval($uid) === 0 || intval($q['u']) !== intval($uid))) {
				return $m[0]; // signed for someone else
			}
			$p = ['key' => $key, 'sig' => $q['s'], 'uid' => $q['u'] ?? null, 'exp' => $q['e'] ?? null];
			if (!filesig_verify($mode, $p, 0)) { return $m[0]; }
			return str_replace('&', '&amp;', filesig_presignedurl($key, $imasroot));
		},
		$html
	);
}

/**
 * Rewrite pre-signed getfile.php URLs in HTML into user-, public-, or perm-signed ones.
 * Pre-signatures that don't verify are left untouched (they grant nothing).
 * @param string $mode 'user', 'public' or 'perm'
 * @param string $imasroot base the new URL is built on
 */
function filesig_rewritehtml($html, $mode, $imasroot, $uid = 0) {
	if (strpos($html, 'getfile.php?') === false) { return $html; }
	return preg_replace_callback(
		FILESIG_URL_REGEX,
		function ($m) use ($mode, $imasroot, $uid) {
			$q = filesig_parsequery($m[1]);
			// only plain pre-signed URLs (key + s) are converted
			if ($q === false || isset($q['u']) || isset($q['e']) || isset($q['m'])) { return $m[0]; }
			$key = $q['k'];
			if (filesig_validkey($key) === false || !filesig_verifypre($key, $q['s'])) {
				return $m[0];
			}
			$url = filesig_signedurl($key, $mode, $imasroot, $uid);
			// keep HTML-attribute-safe form
			return str_replace('&', '&amp;', $url);
		},
		$html
	);
}
