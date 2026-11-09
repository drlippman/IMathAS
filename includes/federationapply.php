<?php
// Federation v2: applying (approving), rejecting and resolving staged changes.
// See federation-plan.md.
//
// Everything that changes questions/libraries/library items for federation
// goes through here.  Rules:
//  - local lastmoddate is always set to "now", so other installs pulling from
//    this one see the change;
//  - the sync base is updated to the peer's version, so the change is not
//    mistaken for a local edit and a rejected change does not reappear;
//  - image files are never written over existing files.

require_once __DIR__ . '/federationpull.php';
require_once __DIR__ . '/filehandler.php';

const FED_MAX_IMAGE_BYTES = 10485760;

/**
 * @param int $userid  admin doing the approving
 */
function fed_apply_context($userid, $groupid) {
	global $CFG;
	return array(
		'userid' => (int) $userid,
		'groupid' => (int) $groupid,
		'now' => time(),
		'ownerid' => !empty($CFG['federation']['ownerid']) ? (int) $CFG['federation']['ownerid'] : (int) $userid,
		'qrights' => isset($CFG['federation']['question_userights']) ? (int) $CFG['federation']['question_userights'] : 2,
	);
}

// ---------------------------------------------------------------------
// Lookups
// ---------------------------------------------------------------------

/**
 * @param string $table  imas_libraries or imas_questionset (internal values only)
 * @return array uniqueid => local id (lowest id if there are duplicates)
 */
function fed_ids_by_uid($table, $uids) {
	global $DBH;
	$out = array();
	$uids = array_values(array_unique(array_map('strval', $uids)));
	foreach (array_chunk($uids, 1000) as $chunk) {
		$ph = Sanitize::generateQueryPlaceholders($chunk);
		$stm = $DBH->prepare("SELECT uniqueid,id FROM $table WHERE uniqueid IN ($ph) ORDER BY id DESC");
		$stm->execute($chunk);
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$out[(string) $row[0]] = (int) $row[1];
		}
	}
	return $out;
}

function fed_load_staged($peerid, $ids, $status = FED_STATUS_PENDING) {
	global $DBH;
	$out = array();
	$ids = array_values(array_unique(array_map('intval', $ids)));
	if (count($ids) == 0) { return $out; }
	$ph = Sanitize::generateQueryPlaceholders($ids);
	$stm = $DBH->prepare("SELECT * FROM imas_federation_staged WHERE peerid=? AND status=? AND id IN ($ph) ORDER BY id");
	$stm->execute(array_merge(array($peerid, $status), $ids));
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		$row['rec'] = json_decode($row['payload'], true);
		$out[$row['id']] = $row;
	}
	return $out;
}

function fed_pending_by_uid($peerid, $type, $uids) {
	global $DBH;
	$out = array();
	$uids = array_values(array_unique(array_map('strval', $uids)));
	if (count($uids) == 0) { return $out; }
	$ph = Sanitize::generateQueryPlaceholders($uids);
	$stm = $DBH->prepare("SELECT * FROM imas_federation_staged WHERE peerid=? AND type=? AND status=? AND uid IN ($ph)");
	$stm->execute(array_merge(array($peerid, $type, FED_STATUS_PENDING), $uids));
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		$row['rec'] = json_decode($row['payload'], true);
		$out[$row['uid']] = $row;
	}
	return $out;
}

function fed_set_base($peerid, $type, $uid, $remote_lastmod, $local_lastmod, $hash) {
	global $DBH;
	$stm = $DBH->prepare("SELECT id FROM imas_federation_items WHERE peerid=? AND type=? AND uid=?");
	$stm->execute(array($peerid, $type, $uid));
	$id = $stm->fetchColumn();
	if ($id !== false) {
		$stm = $DBH->prepare("UPDATE imas_federation_items SET remote_lastmod=?,local_lastmod=?,content_hash=? WHERE id=?");
		$stm->execute(array($remote_lastmod, $local_lastmod, $hash, $id));
	} else {
		$stm = $DBH->prepare("INSERT INTO imas_federation_items (peerid,type,uid,remote_lastmod,local_lastmod,content_hash) VALUES (?,?,?,?,?,?)");
		$stm->execute(array($peerid, $type, $uid, $remote_lastmod, $local_lastmod, $hash));
	}
}

function fed_mark_staged($id, $status, $now) {
	global $DBH;
	$stm = $DBH->prepare("UPDATE imas_federation_staged SET status=?,resolved=? WHERE id=?");
	$stm->execute(array($status, $now, $id));
}

// ---------------------------------------------------------------------
// Library items / unassigned questions
// ---------------------------------------------------------------------

/**
 * Questions must be in at least one undeleted library; the "unassigned" library
 * (libid 0) holds questions that are in no other library.  Same rules as manageqset.php.
 */
function fed_fix_unassigned($qsetids, $now) {
	global $DBH;
	$qsetids = array_values(array_unique(array_map('intval', $qsetids)));
	foreach (array_chunk($qsetids, 500) as $chunk) {
		$ph = Sanitize::generateQueryPlaceholders($chunk);
		$stm = $DBH->prepare("SELECT id FROM imas_questionset WHERE deleted=0 AND id IN ($ph)");
		$stm->execute($chunk);
		$live = $stm->fetchAll(PDO::FETCH_COLUMN);
		if (count($live) == 0) { continue; }
		$ph2 = Sanitize::generateQueryPlaceholders($live);
		$stm = $DBH->prepare("SELECT qsetid,SUM(libid>0 AND deleted=0),SUM(libid=0 AND deleted=0),SUM(libid=0) FROM imas_library_items WHERE qsetid IN ($ph2) GROUP BY qsetid");
		$stm->execute($live);
		$stats = array();
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$stats[$row[0]] = $row;
		}
		foreach ($live as $qid) {
			$assigned = isset($stats[$qid]) ? $stats[$qid][1] : 0;
			$unassn_live = isset($stats[$qid]) ? $stats[$qid][2] : 0;
			$unassn_any = isset($stats[$qid]) ? $stats[$qid][3] : 0;
			if ($assigned == 0 && $unassn_live == 0) {
				if ($unassn_any > 0) {
					$upd = $DBH->prepare("UPDATE imas_library_items SET deleted=0,lastmoddate=? WHERE qsetid=? AND libid=0");
					$upd->execute(array($now, $qid));
				} else {
					$ins = $DBH->prepare("INSERT INTO imas_library_items (libid,qsetid,ownerid,junkflag,deleted,lastmoddate) SELECT 0,id,ownerid,0,0,? FROM imas_questionset WHERE id=?");
					$ins->execute(array($now, $qid));
				}
			} else if ($assigned > 0 && $unassn_live > 0) {
				$upd = $DBH->prepare("UPDATE imas_library_items SET deleted=1,lastmoddate=? WHERE qsetid=? AND libid=0 AND deleted=0");
				$upd->execute(array($now, $qid));
			}
		}
	}
}

// ---------------------------------------------------------------------
// Writing records
// ---------------------------------------------------------------------

/**
 * Write a library record.  Parent is resolved later (fed_resolve_lib_parents).
 * @return int|null local id
 */
function fed_write_lib($rec, &$ctx) {
	global $DBH;
	$now = $ctx['now'];
	$ids = fed_ids_by_uid('imas_libraries', array($rec['uid']));
	if (isset($ids[$rec['uid']])) {
		$id = $ids[$rec['uid']];
		$stm = $DBH->prepare("UPDATE imas_libraries SET name=?,sortorder=?,deleted=?,lastmoddate=? WHERE id=?");
		$stm->execute(array($rec['name'], $rec['sortorder'], $rec['deleted'], $now, $id));
		if ($rec['deleted']) {
			// as managelibs.php does: removing a library removes its assignments
			$stm = $DBH->prepare("SELECT DISTINCT qsetid FROM imas_library_items WHERE libid=? AND deleted=0");
			$stm->execute(array($id));
			$ctx['fixq'] = array_merge($ctx['fixq'], $stm->fetchAll(PDO::FETCH_COLUMN));
			$stm = $DBH->prepare("UPDATE imas_library_items SET deleted=1,lastmoddate=? WHERE libid=? AND deleted=0");
			$stm->execute(array($now, $id));
		}
	} else {
		if ($rec['deleted']) { return null; }   // nothing to import
		$stm = $DBH->prepare("INSERT INTO imas_libraries (uniqueid,adddate,lastmoddate,name,ownerid,userights,sortorder,parent,groupid,federationlevel,deleted) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
		$stm->execute(array($rec['uid'], $rec['adddate'] > 0 ? $rec['adddate'] : $now, $now, $rec['name'],
			$ctx['ownerid'], 8, $rec['sortorder'], 0, $ctx['groupid'], 0, 0));
		$id = (int) $DBH->lastInsertId();
	}
	$ctx['libparents'][$id] = $rec['parent'];
	return $id;
}

function fed_resolve_lib_parents(&$ctx, &$warnings) {
	global $DBH;
	if (count($ctx['libparents']) == 0) { return; }
	$pids = fed_ids_by_uid('imas_libraries', array_values($ctx['libparents']));
	$upd = $DBH->prepare("UPDATE imas_libraries SET parent=? WHERE id=?");
	foreach ($ctx['libparents'] as $id => $puid) {
		if ($puid === '0') {
			$upd->execute(array(0, $id));
		} else if (isset($pids[$puid]) && $pids[$puid] != $id) {
			$upd->execute(array($pids[$puid], $id));
		} else {
			$warnings[$id][] = sprintf('Parent library %s is not on this install; placed at top level.', $puid);
			$upd->execute(array(0, $id));
		}
	}
	$ctx['libparents'] = array();
}

function fed_question_write_fields() {
	return array('author', 'description', 'qtype', 'control', 'qtext', 'extref',
		'solution', 'solutionopts', 'license', 'ancestorauthors', 'otherattribution', 'deleted', 'a11yalttype');   // isrand is derived locally, see fed_resolve_question_refs
}

/**
 * Write a question record.  Question references (UID forms) are resolved
 * afterwards in fed_resolve_question_refs, once every question in the group exists.
 * @return int|null local id
 */
function fed_write_question($rec, &$ctx) {
	global $DBH;
	$now = $ctx['now'];
	$fields = fed_question_write_fields();
	// qcontrol/answer are obsolete: store them empty, folding any value (e.g. from a payload
	// staged before this rule) into control first
	$rec['control'] = fed_merge_legacy_control($rec['control'], $rec['qcontrol'] ?? '', $rec['answer'] ?? '');
	$rec['qcontrol'] = ''; $rec['answer'] = '';
	$fields[] = 'qcontrol'; $fields[] = 'answer';
	$ids = fed_ids_by_uid('imas_questionset', array($rec['uid']));
	if (isset($ids[$rec['uid']])) {
		$id = $ids[$rec['uid']];
		$sets = array(); $vals = array();
		foreach ($fields as $f) { $sets[] = "$f=?"; $vals[] = $rec[$f]; }
		$sets[] = 'lastmoddate=?'; $vals[] = $now;
		$vals[] = $id;
		$stm = $DBH->prepare("UPDATE imas_questionset SET " . implode(',', $sets) . " WHERE id=?");
		$stm->execute($vals);
		if ($rec['deleted']) {
			// as manageqset.php does: deleting a question removes its library assignments
			$stm = $DBH->prepare("UPDATE imas_library_items SET deleted=1,lastmoddate=? WHERE qsetid=? AND deleted=0");
			$stm->execute(array($now, $id));
		} else {
			$ctx['fixq'][] = $id;
		}
	} else {
		if ($rec['deleted']) { return null; }
		$cols = array_merge(array('uniqueid', 'adddate', 'lastmoddate', 'ownerid', 'userights'), $fields);
		$vals = array($rec['uid'], $rec['adddate'] > 0 ? $rec['adddate'] : $now, $now, $ctx['ownerid'], $ctx['qrights']);
		foreach ($fields as $f) { $vals[] = $rec[$f]; }
		$stm = $DBH->prepare("INSERT INTO imas_questionset (" . implode(',', $cols) . ") VALUES (" . Sanitize::generateQueryPlaceholders($vals) . ")");
		$stm->execute($vals);
		$id = (int) $DBH->lastInsertId();
		// until its library assignments are applied, keep it findable in the unassigned library
		$ctx['fixq'][] = $id;
	}
	$ctx['qrecs'][$id] = $rec;
	return $id;
}

/**
 * Does this question code use randomization?  Same rule as course/moddataset.php:
 * the code itself, or the code of a question it includes directly (local ids).
 * Derived here rather than taken from the peer, since included code can differ.
 */
function fed_derive_isrand($control) {
	global $DBH;
	if (preg_match('/((shuffle|getprimes?|rand[a-zA-Z]*)\s*\(|randweights)/', $control)) { return 1; }
	if (preg_match_all('/includecodefrom\(\s*(\d+)\s*\)/', $control, $m) && count($m[1]) > 0) {
		$ids = array_values(array_unique($m[1]));
		$stm = $DBH->prepare("SELECT control FROM imas_questionset WHERE id IN (" . Sanitize::generateQueryPlaceholders($ids) . ")");
		$stm->execute($ids);
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			if (preg_match('/(shuffle|getprimes?|rand[a-zA-Z]*)\(/', $row[0])) { return 1; }
		}
	}
	return 0;
}

/**
 * Convert UID references back to local ids for the questions written in this group.
 */
function fed_resolve_question_refs(&$ctx, &$warnings) {
	global $DBH;
	if (count($ctx['qrecs']) == 0) { return; }
	$refuids = array();
	foreach ($ctx['qrecs'] as $rec) {
		$refuids = array_merge($refuids, fed_record_refs($rec));
	}
	$map = fed_ids_by_uid('imas_questionset', $refuids);
	$upd = $DBH->prepare("UPDATE imas_questionset SET control=?,qtext=?,replaceby=?,a11yalt=?,isrand=? WHERE id=?");
	foreach ($ctx['qrecs'] as $id => $rec) {
		$missing = array();
		$cb = function ($m) use ($map, &$missing) {
			if (isset($map[$m[2]])) { return 'include' . $m[1] . 'from(' . $map[$m[2]] . ')'; }
			$missing[] = $m[2];
			return $m[0];
		};
		$control = preg_replace_callback('/include(code)from\(UID(\d+)\)/', $cb, $rec['control']);
		$qtext = preg_replace_callback('/include(qtext)from\(UID(\d+)\)/', $cb, $rec['qtext']);
		$replaceby = 0; $a11yalt = 0;
		if ($rec['replaceby'] !== '0') {
			if (isset($map[$rec['replaceby']])) { $replaceby = $map[$rec['replaceby']]; } else { $missing[] = $rec['replaceby']; }
		}
		if ($rec['a11yalt'] !== '0') {
			if (isset($map[$rec['a11yalt']])) { $a11yalt = $map[$rec['a11yalt']]; } else { $missing[] = $rec['a11yalt']; }
		}
		$upd->execute(array($control, $qtext, $replaceby, $a11yalt, fed_derive_isrand($control), $id));
		if (count($missing) > 0) {
			$warnings[$id][] = sprintf('Refers to questions not on this install (%s); it may not work until they are pulled.',
				implode(', ', array_unique($missing)));
		}
	}
	$ctx['qrecs'] = array();
}

/**
 * Write a library item record.
 * @return string|null  error message, or null if written
 */
function fed_write_libitem($rec, &$ctx) {
	global $DBH;
	$now = $ctx['now'];
	$lib = fed_ids_by_uid('imas_libraries', array($rec['libuid']));
	$q = fed_ids_by_uid('imas_questionset', array($rec['quid']));
	if (!isset($lib[$rec['libuid']])) {
		return sprintf('Library %s is not on this install; apply the library first.', $rec['libuid']);
	}
	if (!isset($q[$rec['quid']])) {
		return sprintf('Question %s is not on this install; apply the question first.', $rec['quid']);
	}
	$libid = $lib[$rec['libuid']]; $qid = $q[$rec['quid']];
	$stm = $DBH->prepare("SELECT COUNT(*) FROM imas_library_items WHERE libid=? AND qsetid=?");
	$stm->execute(array($libid, $qid));
	$exists = $stm->fetchColumn() > 0;
	if ($exists) {
		$stm = $DBH->prepare("UPDATE imas_library_items SET deleted=?,junkflag=?,lastmoddate=? WHERE libid=? AND qsetid=?");
		$stm->execute(array($rec['deleted'], $rec['junkflag'], $now, $libid, $qid));
	} else if (!$rec['deleted']) {
		$stm = $DBH->prepare("INSERT INTO imas_library_items (libid,qsetid,ownerid,junkflag,deleted,lastmoddate) VALUES (?,?,?,?,?,?)");
		$stm->execute(array($libid, $qid, $ctx['ownerid'], $rec['junkflag'], 0, $now));
	}
	$ctx['fixq'][] = $qid;
	return null;
}

// ---------------------------------------------------------------------
// Images: never write over an existing file
// ---------------------------------------------------------------------

/**
 * May we fetch this image URL?  The peer's own host is always fine (an admin configured it).
 * Anything else must be http(s) on port 80/443 and resolve only to public addresses, so a
 * peer cannot make this server request internal addresses.
 */
function fed_image_url_allowed($url, $peerurl) {
	$u = parse_url($url);
	if ($u === false || empty($u['host']) || isset($u['user']) || isset($u['pass'])) { return false; }
	$scheme = strtolower($u['scheme'] ?? '');
	if ($scheme !== 'http' && $scheme !== 'https') { return false; }
	$port = $u['port'] ?? ($scheme === 'https' ? 443 : 80);
	$p = $peerurl !== '' ? parse_url($peerurl) : false;
	if ($p !== false && !empty($p['host'])) {
		$pport = $p['port'] ?? (strtolower($p['scheme'] ?? 'http') === 'https' ? 443 : 80);
		if (strcasecmp($u['host'], $p['host']) == 0 && $port == $pport) { return true; }
	}
	if ($port != 80 && $port != 443) { return false; }
	$ips = filter_var($u['host'], FILTER_VALIDATE_IP) ? array($u['host']) : gethostbynamel($u['host']);
	if (!$ips) { return false; }
	foreach ($ips as $ip) {
		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) { return false; }
	}
	return true;
}

/**
 * Download an image from a peer to a temp file.  The file type is decided from the
 * contents, never from the URL, and SVGs are sanitized.
 * @return array|false  array(temp file path, extension to store it with)
 */
function fed_download_image($url, $peerurl, &$err) {
	if (!fed_image_url_allowed($url, $peerurl)) { $err = 'image address not allowed'; return false; }
	$ctx = stream_context_create(array('http' => array('timeout' => 30, 'follow_location' => 0)));
	$data = @file_get_contents($url, false, $ctx, 0, FED_MAX_IMAGE_BYTES + 1);
	if ($data === false || strlen($data) == 0) { $err = 'could not download'; return false; }
	if (strlen($data) > FED_MAX_IMAGE_BYTES) { $err = 'image too large'; return false; }
	$tmp = tempnam(sys_get_temp_dir(), 'fedimg');
	file_put_contents($tmp, $data);
	unset($data);

	$extmap = array(IMAGETYPE_GIF => array('gif'), IMAGETYPE_JPEG => array('jpg', 'jpeg'),
		IMAGETYPE_PNG => array('png'), IMAGETYPE_WEBP => array('webp'));
	$info = @getimagesize($tmp);
	if ($info !== false && isset($extmap[$info[2]])) {
		$urlext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
		return array($tmp, in_array($urlext, $extmap[$info[2]]) ? $urlext : $extmap[$info[2]][0]);
	}
	if (function_exists('mime_content_type') && mime_content_type($tmp) === 'image/svg+xml') {
		require_once __DIR__ . '/svg-sanitizer/svgcleaner.php';
		sanitizeSvg(array('name' => 'image.svg', 'tmp_name' => $tmp));
		if (filesize($tmp) > 0) { return array($tmp, 'svg'); }
	}
	@unlink($tmp);
	$err = 'not a supported image type';
	return false;
}

/** Is the stored image identical to this md5? */
function fed_qimage_matches($filename, $md5) {
	if (substr($filename, 0, 4) == 'http') { return false; }
	if (getfilehandlertype('filehandlertypecfiles') == 's3') {
		$s3 = new S3($GLOBALS['AWSkey'], $GLOBALS['AWSsecret']);
		$info = $s3->getObjectInfo($GLOBALS['AWSbucket'], 'qimages/' . $filename);
		return $info !== false && isset($info['hash']) && trim($info['hash'], '"') === $md5;
	}
	$path = rtrim(dirname(__DIR__), '/\\') . '/assessment/qimages/' . $filename;
	return is_file($path) && md5_file($path) === $md5;
}

/**
 * Store a file as a new qimage under a name that does not exist yet.
 * @return string|false  stored name
 */
function fed_store_qimage($tmp, $name) {
	$name = Sanitize::sanitizeFilenameAndCheckBlacklist($name);
	$dot = strpos($name, '.');
	$stem = $dot === false ? $name : substr($name, 0, $dot);
	$ext = $dot === false ? '' : substr($name, $dot);
	if (getfilehandlertype('filehandlertypecfiles') == 's3') {
		$s3 = new S3($GLOBALS['AWSkey'], $GLOBALS['AWSsecret']);
		$fn = $name; $t = 0;
		while ($s3->getObjectInfo($GLOBALS['AWSbucket'], 'qimages/' . $fn, false) !== false) {
			$fn = $stem . '_' . $t . $ext; $t++;
		}
		return $s3->putObjectFile($tmp, $GLOBALS['AWSbucket'], 'qimages/' . $fn, 'public-read') ? $fn : false;
	}
	$dir = rtrim(dirname(__DIR__), '/\\') . '/assessment/qimages';
	if (!is_dir($dir)) { mkdir_recursive($dir); }
	$fn = $name; $t = 0;
	while (file_exists($dir . '/' . $fn)) {
		$fn = $stem . '_' . $t . $ext; $t++;
	}
	return copy($tmp, $dir . '/' . $fn) ? $fn : false;
}

/**
 * Make a question's images match the peer's list.  Existing identical files are
 * kept; changed images get new files; files are deleted only when no other
 * question refers to them.
 * @return array warnings
 */
function fed_apply_images($qsetid, $imgs, $peerurl = '') {
	global $DBH;
	$warnings = array();
	$stm = $DBH->prepare("SELECT id,var,filename,alttext FROM imas_qimages WHERE qsetid=?");
	$stm->execute(array($qsetid));
	$existing = array();
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		$existing[$row['var']] = $row;
	}
	$wanted = array();
	$delrows = array();
	foreach ($imgs as $img) {
		$var = preg_replace('/[^\w\[\]]/', '', $img['var']);
		if ($var === '') { continue; }
		$wanted[$var] = true;
		$alt = Sanitize::stripHtmlTags($img['alttext']);
		$dl = fed_download_image($img['filename'], $peerurl, $err);
		if ($dl === false) {
			$warnings[] = sprintf('Image "%s" was not copied (%s).', $var, $err);
			continue;   // leave whatever is there
		}
		list($tmp, $ext) = $dl;
		$md5 = md5_file($tmp);
		if (isset($existing[$var]) && fed_qimage_matches($existing[$var]['filename'], $md5)) {
			if ($existing[$var]['alttext'] !== $alt) {
				$upd = $DBH->prepare("UPDATE imas_qimages SET alttext=? WHERE id=?");
				$upd->execute(array($alt, $existing[$var]['id']));
			}
			@unlink($tmp);
			continue;
		}
		$stem = pathinfo(fed_image_key($img['filename']), PATHINFO_FILENAME);
		if ($stem === '') { $stem = 'image'; }
		$newfn = fed_store_qimage($tmp, $qsetid . '-' . $stem . '.' . $ext);
		@unlink($tmp);
		if ($newfn === false) {
			$warnings[] = sprintf('Image "%s" could not be stored.', $var);
			continue;
		}
		if (isset($existing[$var])) { $delrows[] = $existing[$var]; }
		$ins = $DBH->prepare("INSERT INTO imas_qimages (qsetid,var,filename,alttext) VALUES (?,?,?,?)");
		$ins->execute(array($qsetid, $var, $newfn, $alt));
	}
	foreach ($existing as $var => $row) {
		if (!isset($wanted[$var])) { $delrows[] = $row; }   // peer no longer has this image
	}
	foreach ($delrows as $row) {
		$del = $DBH->prepare("DELETE FROM imas_qimages WHERE id=?");
		$del->execute(array($row['id']));
		if (substr($row['filename'], 0, 4) != 'http') {
			$cnt = $DBH->prepare("SELECT COUNT(*) FROM imas_qimages WHERE filename=?");
			$cnt->execute(array($row['filename']));
			if ($cnt->fetchColumn() == 0) { deleteqimage($row['filename']); }
		}
	}
	$cnt = $DBH->prepare("SELECT COUNT(*) FROM imas_qimages WHERE qsetid=?");
	$cnt->execute(array($qsetid));
	$upd = $DBH->prepare("UPDATE imas_questionset SET hasimg=? WHERE id=?");
	$upd->execute(array($cnt->fetchColumn() > 0 ? 1 : 0, $qsetid));
	return $warnings;
}

// ---------------------------------------------------------------------
// Approval
// ---------------------------------------------------------------------

/**
 * Add the staged rows that a set of rows needs in order to work:
 * referenced questions for a question; the library and question for a library assignment.
 * Rows that are conflicts cannot be added automatically; the row needing them is blocked.
 * @return array  array(rows (id => row), blocked (id => message))
 */
function fed_expand_group($peerid, $rows, $forceids) {
	$blocked = array();
	$all = $rows;
	$queue = array_keys($rows);
	$needs = array();   // id => array of needed row ids
	$guard = 0;
	while (count($queue) > 0 && $guard++ < 100) {
		$wantq = array(); $wantlib = array();
		$owners = array();
		foreach ($queue as $id) {
			$row = $all[$id];
			if ($row['type'] == FED_TYPE_QUESTION) {
				foreach (fed_record_refs($row['rec']) as $u) { $wantq[$u][] = $id; }
			} else if ($row['type'] == FED_TYPE_LIBITEM) {
				$wantlib[$row['rec']['libuid']][] = $id;
				$wantq[$row['rec']['quid']][] = $id;
			}
		}
		$queue = array();
		$foundq = fed_pending_by_uid($peerid, FED_TYPE_QUESTION, array_keys($wantq));
		$foundl = fed_pending_by_uid($peerid, FED_TYPE_LIB, array_keys($wantlib));
		foreach (array(array($foundq, $wantq), array($foundl, $wantlib)) as $pair) {
			foreach ($pair[0] as $uid => $nrow) {
				foreach ($pair[1][$uid] as $owner) { $needs[$owner][] = $nrow['id']; }
				if (!isset($all[$nrow['id']])) {
					$all[$nrow['id']] = $nrow;
					$queue[] = $nrow['id'];
				}
			}
		}
	}
	// a needed row that is a conflict (and not being resolved right now) blocks whoever needs it
	$isblocking = function ($rid) use (&$all, $forceids) {
		return in_array($all[$rid]['kind'], array(FED_KIND_CONFLICT, FED_KIND_DELVEDIT)) && !in_array($rid, $forceids);
	};
	foreach ($rows as $id => $row) {
		$seen = array(); $stack = isset($needs[$id]) ? $needs[$id] : array();
		while (count($stack) > 0) {
			$n = array_pop($stack);
			if (isset($seen[$n])) { continue; }
			$seen[$n] = true;
			if ($isblocking($n)) {
				$blocked[$id] = sprintf('Needs %s %s, which has a conflict to resolve first.',
					$all[$n]['type'] == FED_TYPE_LIB ? 'library' : 'question', $all[$n]['uid']);
				break;
			}
			if (isset($needs[$n])) { $stack = array_merge($stack, $needs[$n]); }
		}
	}
	return array($all, $blocked);
}

/**
 * Has the local copy changed since a change was staged?  A row that was staged as a
 * simple new/update/delete but is now a conflict is turned into one.
 * @return array id => null (go ahead) | array(status, message)
 */
function fed_recheck_rows($peerid, $rows) {
	global $DBH;
	$out = array();
	$bytype = array();
	foreach ($rows as $id => $row) { $bytype[$row['type']][$id] = $row; }
	foreach ($bytype as $type => $trows) {
		$recs = array();
		foreach ($trows as $row) { $recs[] = $row['rec']; }
		$local = fed_local_records($type, $recs);
		$base = fed_get_base($peerid, $type, array_column($trows, 'uid'));
		foreach ($trows as $id => $row) {
			$rec = $row['rec'];
			$lrec = isset($local[$row['uid']]) ? $local[$row['uid']] : null;
			list($action, $kind) = fed_classify(fed_hash_record($type, $rec), !empty($rec['deleted']),
				$lrec === null ? null : fed_hash_record($type, $lrec), $lrec !== null && !empty($lrec['deleted']),
				isset($base[$row['uid']]) ? $base[$row['uid']]['content_hash'] : null);
			if ($action == 'insync') {
				$out[$id] = array('done', 'Local copy already matches.');
			} else if ($action == 'skip') {
				$out[$id] = array('superseded', 'No longer needed.');
			} else if (in_array($kind, array(FED_KIND_CONFLICT, FED_KIND_DELVEDIT)) &&
				!in_array($row['kind'], array(FED_KIND_CONFLICT, FED_KIND_DELVEDIT))) {
				$upd = $DBH->prepare("UPDATE imas_federation_staged SET kind=? WHERE id=?");
				$upd->execute(array($kind, $id));
				$out[$id] = array('blocked', 'The local copy changed after this was staged; it now needs review as a conflict.');
			} else {
				$out[$id] = null;
			}
		}
	}
	return $out;
}

/**
 * Approve staged rows (and the rows they need).
 *
 * @param array $ids        staged row ids requested
 * @param array $forceids   rows being resolved explicitly (conflicts): applied as given, without the conflict check
 * @param array $overrides  id => replacement wire record (merge results)
 * @return array  id => array('ok'=>bool, 'msg'=>string, 'warnings'=>array, 'auto'=>bool)
 */
function fed_approve($peerid, $ids, $ctx, $forceids = array(), $overrides = array()) {
	global $DBH;
	$results = array();
	$requested = fed_load_staged($peerid, $ids);
	foreach ($ids as $id) {
		if (!isset($requested[$id])) { $results[$id] = array('ok' => false, 'msg' => 'Not found or already handled.', 'warnings' => array()); }
	}
	foreach ($overrides as $id => $rec) {
		if (isset($requested[$id])) { $requested[$id]['rec'] = $rec; }
	}
	// conflicts can only be applied through explicit resolution
	foreach ($requested as $id => $row) {
		if (in_array($row['kind'], array(FED_KIND_CONFLICT, FED_KIND_DELVEDIT)) && !in_array($id, $forceids)) {
			$results[$id] = array('ok' => false, 'msg' => 'Needs review: this is a conflict.', 'warnings' => array());
			unset($requested[$id]);
		}
	}
	list($all, $blocked) = fed_expand_group($peerid, $requested, $forceids);
	foreach ($blocked as $id => $msg) {
		$results[$id] = array('ok' => false, 'msg' => $msg, 'warnings' => array());
		unset($all[$id]);
	}
	// rows only included because something needed them go away if all their requesters were blocked;
	// that is rare enough that we simply apply them
	$check = array();
	foreach ($all as $id => $row) {
		if (!in_array($id, $forceids)) { $check[$id] = $row; }
	}
	foreach (fed_recheck_rows($peerid, $check) as $id => $r) {
		if ($r === null) { continue; }
		if ($r[0] == 'done' || $r[0] == 'superseded') {
			$status = $r[0] == 'done' ? FED_STATUS_APPLIED : FED_STATUS_SUPERSEDED;
			fed_mark_staged($id, $status, $ctx['now']);
			if ($r[0] == 'done') {
				fed_set_base($peerid, $all[$id]['type'], $all[$id]['uid'], $all[$id]['remote_lastmod'], $ctx['now'], $all[$id]['content_hash']);
			}
			$results[$id] = array('ok' => true, 'msg' => $r[1], 'warnings' => array());
		} else {
			$results[$id] = array('ok' => false, 'msg' => $r[1], 'warnings' => array());
		}
		unset($all[$id]);
	}
	if (count($all) == 0) { return $results; }

	$ctx['fixq'] = array(); $ctx['libparents'] = array(); $ctx['qrecs'] = array();
	$warnings = array();      // local id => messages, per type below
	$libwarn = array(); $qwarn = array();
	$localids = array();      // staged id => local id
	$imageq = array();        // staged id => array(local qid, imgs)

	$oldmode = $DBH->getAttribute(PDO::ATTR_ERRMODE);
	$DBH->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$DBH->beginTransaction();
	try {
		$done = array();
		foreach (array(FED_TYPE_LIB, FED_TYPE_QUESTION, FED_TYPE_LIBITEM) as $type) {
			foreach ($all as $id => $row) {
				if ($row['type'] != $type) { continue; }
				$rec = $row['rec'];
				if ($type == FED_TYPE_LIB) {
					$localids[$id] = fed_write_lib($rec, $ctx);
				} else if ($type == FED_TYPE_QUESTION) {
					$localids[$id] = fed_write_question($rec, $ctx);
					if ($localids[$id] !== null && empty($rec['_keepimgs'])) {
						$imageq[$id] = array($localids[$id], $rec['imgs']);
					}
				} else {
					$err = fed_write_libitem($rec, $ctx);
					if ($err !== null) {
						$results[$id] = array('ok' => false, 'msg' => $err, 'warnings' => array());
						continue;
					}
				}
				$done[$id] = true;
			}
			if ($type == FED_TYPE_LIB) { fed_resolve_lib_parents($ctx, $libwarn); }
			if ($type == FED_TYPE_QUESTION) { fed_resolve_question_refs($ctx, $qwarn); }
		}
		fed_fix_unassigned($ctx['fixq'], $ctx['now']);
		foreach ($done as $id => $_) {
			$row = $all[$id];
			fed_set_base($peerid, $row['type'], $row['uid'], $row['remote_lastmod'], $ctx['now'], $row['content_hash']);
			fed_mark_staged($id, FED_STATUS_APPLIED, $ctx['now']);
		}
		$DBH->commit();
	} catch (Exception $e) {
		if ($DBH->inTransaction()) { $DBH->rollBack(); }
		$DBH->setAttribute(PDO::ATTR_ERRMODE, $oldmode);
		foreach ($all as $id => $row) {
			$results[$id] = array('ok' => false, 'msg' => 'Database error; nothing was changed. ' . $e->getMessage(), 'warnings' => array());
		}
		return $results;
	}
	$DBH->setAttribute(PDO::ATTR_ERRMODE, $oldmode);

	// images after the commit, so a failed download cannot undo the rest
	$stm = $DBH->prepare("SELECT url FROM imas_federation_peers WHERE id=?");
	$stm->execute(array($peerid));
	$peerurl = (string) $stm->fetchColumn();
	$imgwarn = array();
	foreach ($imageq as $id => $info) {
		$imgwarn[$id] = fed_apply_images($info[0], $info[1], $peerurl);
	}
	foreach ($done as $id => $_) {
		$w = array();
		if (isset($imgwarn[$id])) { $w = array_merge($w, $imgwarn[$id]); }
		if (isset($localids[$id])) {
			if ($all[$id]['type'] == FED_TYPE_QUESTION && isset($qwarn[$localids[$id]])) { $w = array_merge($w, $qwarn[$localids[$id]]); }
			if ($all[$id]['type'] == FED_TYPE_LIB && isset($libwarn[$localids[$id]])) { $w = array_merge($w, $libwarn[$localids[$id]]); }
		}
		$results[$id] = array('ok' => true, 'msg' => 'Applied.', 'warnings' => $w, 'auto' => !isset($requested[$id]));
	}
	return $results;
}

/**
 * Reject staged rows: keep the local version.  The peer's version is recorded as seen,
 * so the same change does not come back until the peer changes it again.
 */
function fed_reject($peerid, $ids, $ctx) {
	$rows = fed_load_staged($peerid, $ids);
	$results = array();
	foreach ($ids as $id) {
		if (!isset($rows[$id])) {
			$results[$id] = array('ok' => false, 'msg' => 'Not found or already handled.', 'warnings' => array());
			continue;
		}
		$row = $rows[$id];
		fed_set_base($peerid, $row['type'], $row['uid'], $row['remote_lastmod'], 0, $row['content_hash']);
		fed_mark_staged($id, FED_STATUS_REJECTED, $ctx['now']);
		$results[$id] = array('ok' => true, 'msg' => 'Kept local version.', 'warnings' => array());
	}
	return $results;
}
