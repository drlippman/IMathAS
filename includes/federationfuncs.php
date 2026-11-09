<?php
// Federation v2 shared functions.  See federation-plan.md.
//
// "Wire format" records identify everything by uniqueid rather than local id,
// and are what gets sent between installs, hashed, staged, and diffed.
// Both the sending API and the local-state comparison on the pulling side
// must produce wire records through these functions so hashes agree.

const FED_PROTOCOL_VERSION = 2;

const FED_TYPE_LIB = 1;
const FED_TYPE_QUESTION = 2;
const FED_TYPE_LIBITEM = 3;

const FED_MIN_SECRET_LENGTH = 16;  // for newly entered peer secrets
const FED_MAX_SECRET_LENGTH = 64;   // size of the column

const FED_MAX_SKEW = 300;       // seconds of allowed clock difference on signed requests
const FED_DEP_MAX_DEPTH = 10;   // levels of question-reference closure followed
const FED_DEP_MAX_COUNT = 500;  // max dependency questions attached to one batch

// SQL fragment: library (alias il) is shared via federation
const FED_LIB_SHARED_SQL = 'il.federationlevel>0 AND il.userights=8 AND il.uniqueid>0';
// SQL fragment: question (alias iq) may be shared via federation
const FED_QUESTION_SHARED_SQL = 'iq.uniqueid>0 AND iq.userights>1 AND iq.license>0';

/**
 * Name this install presents to peers (must match peername in their table)
 */
function fed_my_peer_name() {
	global $CFG, $installname;
	return isset($CFG['federatedname']) ? $CFG['federatedname'] : $installname;
}

/**
 * Canonical string that is signed for an API request.
 * @param array $p  peer, ts, type, lm, id, limit
 */
function fed_signing_string($p) {
	return implode("\n", array(
		'v'.FED_PROTOCOL_VERSION,
		(string) $p['peer'],
		(string) intval($p['ts']),
		(string) $p['type'],
		(string) intval($p['lm']),
		(string) intval($p['id']),
		(string) intval($p['limit'])
	));
}

function fed_sign($secret, $p) {
	return base64_encode(hash_hmac('sha256', fed_signing_string($p), $secret, true));
}

function fed_verify_sig($secret, $p, $sig) {
	return hash_equals(fed_sign($secret, $p), (string) $sig);
}

// ---------------------------------------------------------------------
// Content hashing
// ---------------------------------------------------------------------

function fed_norm_text($s) {
	return str_replace("\r\n", "\n", (string) $s);
}

function fed_hash_values($vals) {
	return sha1(json_encode($vals, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR));
}

/**
 * Content hash of a wire record.
 * Excludes lastmoddate/adddate, local ids, owners, rights, federationlevel,
 * and local-only state (broken, a11ystatus, usage stats).
 */
function fed_hash_record($type, $rec) {
	if ($type == FED_TYPE_LIB) {
		return fed_hash_values(array(
			fed_norm_text($rec['name']), (string) $rec['parent'],
			(string) $rec['sortorder'], (string) $rec['deleted']
		));
	} else if ($type == FED_TYPE_LIBITEM) {
		return fed_hash_values(array(
			(string) $rec['junkflag'], (string) $rec['deleted']
		));
	} else if ($type == FED_TYPE_QUESTION) {
		$imgs = array();
		foreach ($rec['imgs'] as $img) {
			$imgs[] = array((string) $img['var'], fed_norm_text($img['alttext']), fed_image_key($img['filename']));
		}
		sort($imgs);
		$vals = array();
		foreach (fed_question_hash_fields() as $f) {
			$vals[] = fed_norm_text($rec[$f]);
		}
		$vals[] = $imgs;
		return fed_hash_values($vals);
	}
	return '';
}

/**
 * Comparable name for an image, whether given as a peer URL or a local stored filename.
 * Rehosting adds a "<id>-" prefix and a "_N" suffix on collisions, and strips odd characters,
 * so those are removed before comparing.
 */
function fed_image_key($name) {
	$path = parse_url($name, PHP_URL_PATH);
	if ($path === null || $path === false) { $path = $name; }
	$base = basename(rawurldecode($path));
	$ext = strtolower(pathinfo($base, PATHINFO_EXTENSION));
	if ($ext === 'jpeg') { $ext = 'jpg'; }
	$stem = pathinfo($base, PATHINFO_FILENAME);
	$stem = preg_replace('/^\d+-/', '', $stem);
	$stem = preg_replace('/(_\d+)+$/', '', $stem);
	$stem = substr(preg_replace('/[^A-Za-z0-9_\-]/', '', $stem), 0, 60);
	return $stem . '.' . $ext;
}

function fed_question_hash_fields() {
	return array('description', 'author', 'qtype', 'control', 'qcontrol', 'qtext', 'answer',
		'extref', 'solution', 'solutionopts', 'license', 'ancestorauthors', 'otherattribution',
		'deleted', 'a11yalttype', 'a11yalt', 'replaceby');
}

/**
 * Item key used in imas_federation_items / imas_federation_staged
 */
function fed_record_uid($type, $rec) {
	if ($type == FED_TYPE_LIBITEM) {
		return $rec['libuid'] . ':' . $rec['quid'];
	}
	return (string) $rec['uid'];
}

// ---------------------------------------------------------------------
// Serializing local rows to wire records
// ---------------------------------------------------------------------

/**
 * @param array $rows  rows of imas_libraries joined with parent uniqueid as parentuid
 */
function fed_serialize_libraries($rows) {
	$out = array();
	foreach ($rows as $row) {
		$out[] = array(
			'uid' => (string) $row['uniqueid'],
			'name' => $row['name'],
			'parent' => ($row['parent'] == 0 || empty($row['parentuid'])) ? '0' : (string) $row['parentuid'],
			'sortorder' => (int) $row['sortorder'],
			'deleted' => (int) $row['deleted'],
			'adddate' => (int) $row['adddate'],
			'lastmoddate' => (int) $row['lastmoddate']
		);
	}
	return $out;
}

/**
 * @param array $rows  rows with libuid, quid, junkflag, deleted, lastmoddate
 */
function fed_serialize_libitems($rows) {
	$out = array();
	foreach ($rows as $row) {
		$out[] = array(
			'libuid' => (string) $row['libuid'],
			'quid' => (string) $row['quid'],
			'junkflag' => (int) $row['junkflag'],
			'deleted' => (int) $row['deleted'],
			'lastmoddate' => (int) $row['lastmoddate']
		);
	}
	return $out;
}

/**
 * Serialize full imas_questionset rows to wire records.
 * Question references (includecodefrom, includeqtextfrom, replaceby, a11yalt)
 * are converted to uniqueids.  includecodefrom(12) becomes includecodefrom(UID123456).
 *
 * @param array $rows  full rows of imas_questionset
 * @return array  array(records, refids): refids = local ids of referenced questions
 */
function fed_serialize_questions($rows) {
	global $DBH;
	if (count($rows) == 0) { return array(array(), array()); }

	// collect references
	$refids = array();
	foreach ($rows as $row) {
		if (preg_match_all('/includecodefrom\((\d+)\)/', $row['control'], $m)) {
			$refids = array_merge($refids, $m[1]);
		}
		if (preg_match_all('/includeqtextfrom\((\d+)\)/', $row['qtext'], $m)) {
			$refids = array_merge($refids, $m[1]);
		}
		if ($row['replaceby'] > 0) { $refids[] = $row['replaceby']; }
		if ($row['a11yalt'] > 0) { $refids[] = $row['a11yalt']; }
	}
	$refids = array_values(array_unique(array_map('intval', $refids)));

	$uidmap = array();
	if (count($refids) > 0) {
		$ph = Sanitize::generateQueryPlaceholders($refids);
		$stm = $DBH->prepare("SELECT id,uniqueid FROM imas_questionset WHERE id IN ($ph) AND uniqueid>0");
		$stm->execute($refids);
		while ($r = $stm->fetch(PDO::FETCH_NUM)) {
			$uidmap[$r[0]] = (string) $r[1];
		}
	}

	// images
	$qids = array();
	foreach ($rows as $row) {
		if ($row['hasimg'] > 0) { $qids[] = (int) $row['id']; }
	}
	$imgmap = array();
	if (count($qids) > 0) {
		$ph = Sanitize::generateQueryPlaceholders($qids);
		$stm = $DBH->prepare("SELECT qsetid,var,filename,alttext FROM imas_qimages WHERE qsetid IN ($ph) ORDER BY id");
		$stm->execute($qids);
		while ($r = $stm->fetch(PDO::FETCH_NUM)) {
			$imgmap[$r[0]][] = array(
				'var' => $r[1],
				'filename' => getqimageurl($r[2], true),
				'alttext' => $r[3]
			);
		}
	}

	$cb_code = function ($m) use ($uidmap) {
		return isset($uidmap[$m[1]]) ? 'includecodefrom(UID' . $uidmap[$m[1]] . ')' : $m[0];
	};
	$cb_text = function ($m) use ($uidmap) {
		return isset($uidmap[$m[1]]) ? 'includeqtextfrom(UID' . $uidmap[$m[1]] . ')' : $m[0];
	};

	$out = array();
	foreach ($rows as $row) {
		$out[] = array(
			'uid' => (string) $row['uniqueid'],
			'adddate' => (int) $row['adddate'],
			'lastmoddate' => (int) $row['lastmoddate'],
			'author' => $row['author'],
			'description' => $row['description'],
			'qtype' => $row['qtype'],
			'control' => preg_replace_callback('/includecodefrom\((\d+)\)/', $cb_code, $row['control']),
			'qcontrol' => $row['qcontrol'],
			'qtext' => preg_replace_callback('/includeqtextfrom\((\d+)\)/', $cb_text, $row['qtext']),
			'answer' => $row['answer'],
			'extref' => $row['extref'],
			'solution' => $row['solution'],
			'solutionopts' => (int) $row['solutionopts'],
			'license' => (int) $row['license'],
			'ancestorauthors' => $row['ancestorauthors'],
			'otherattribution' => $row['otherattribution'],
			'deleted' => (int) $row['deleted'],
			'isrand' => (int) $row['isrand'],
			'a11yalttype' => (int) $row['a11yalttype'],
			'a11yalt' => ($row['a11yalt'] > 0 && isset($uidmap[$row['a11yalt']])) ? $uidmap[$row['a11yalt']] : '0',
			'replaceby' => ($row['replaceby'] > 0 && isset($uidmap[$row['replaceby']])) ? $uidmap[$row['replaceby']] : '0',
			'imgs' => isset($imgmap[$row['id']]) ? $imgmap[$row['id']] : array()
		);
	}
	return array($out, $refids);
}

/**
 * Collect the questions referenced (transitively) by a set of questions, so that
 * they can be sent along with them.  Dependencies are included regardless of
 * lastmoddate and of library membership, but must still pass the question
 * sharing filter (userights/license).
 *
 * @param array $refids   local ids referenced by the batch
 * @param array $haveids  local ids already in the batch (not repeated)
 * @return array  array(deps, withheld, truncated)
 *    deps: wire records; withheld: uids of referenced questions that
 *    could not be shared; truncated: true if a cap was hit
 */
function fed_collect_dependencies($refids, $haveids) {
	global $DBH;
	$have = array_fill_keys(array_map('intval', $haveids), true);
	$deps = array();
	$withheld = array();
	$truncated = false;
	$todo = array_values(array_diff(array_map('intval', $refids), array_keys($have)));

	for ($depth = 0; $depth < FED_DEP_MAX_DEPTH && count($todo) > 0; $depth++) {
		$ph = Sanitize::generateQueryPlaceholders($todo);
		$stm = $DBH->prepare("SELECT * FROM imas_questionset WHERE id IN ($ph)");
		$stm->execute($todo);
		$rows = array();
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			$have[(int) $row['id']] = true;
			if ($row['uniqueid'] > 0 && $row['userights'] > 1 && $row['license'] > 0) {
				$rows[] = $row;
			} else if ($row['uniqueid'] > 0) {
				$withheld[] = (string) $row['uniqueid'];
			}
		}
		foreach ($todo as $id) {
			$have[$id] = true;
		}
		if (count($deps) + count($rows) > FED_DEP_MAX_COUNT) {
			$rows = array_slice($rows, 0, max(0, FED_DEP_MAX_COUNT - count($deps)));
			$truncated = true;
		}
		list($recs, $nextrefs) = fed_serialize_questions($rows);
		$deps = array_merge($deps, $recs);
		if ($truncated) { break; }
		$todo = array_values(array_diff($nextrefs, array_keys($have)));
	}
	if (count($todo) > 0) { $truncated = true; }
	return array($deps, array_values(array_unique($withheld)), $truncated);
}

// ---------------------------------------------------------------------
// Validation of records received from a peer
// ---------------------------------------------------------------------

const FED_MAX_TEXT = 65535;    // the TEXT columns these end up in
const FED_MAX_IMAGES = 50;

function fed_clean_uid($v) {
	if (!is_scalar($v)) { return null; }
	$v = (string) $v;
	return preg_match('/^\d{1,20}$/', $v) ? $v : null;
}

function fed_clean_ref($v) {
	return ($v === 0 || $v === '0' || $v === null || $v === '') ? '0' : fed_clean_uid($v);
}

function fed_clean_text($v, $max = FED_MAX_TEXT) {
	if (!is_scalar($v) && $v !== null) { return null; }
	$v = (string) $v;
	return strlen($v) <= $max ? $v : null;
}

function fed_clean_int($v, $min, $max) {
	if (!is_scalar($v) || !is_numeric($v)) { return null; }
	$v = (int) $v;
	return ($v >= $min && $v <= $max) ? $v : null;
}

/**
 * Check and normalize a record received from a peer: only known keys are kept,
 * every value gets the expected type and size.  Peers are not trusted to send
 * well-formed data.
 * @return array|null  null if the record is unusable
 */
function fed_normalize_record($type, $raw) {
	if (!is_array($raw)) { return null; }
	$out = array();
	if ($type == FED_TYPE_LIB) {
		$spec = array('uid' => 'uid', 'name' => array('text', 254), 'parent' => 'ref', 'sortorder' => array('int', 0, 255),
			'deleted' => array('int', 0, 1), 'adddate' => array('int', 0, 4294967295), 'lastmoddate' => array('int', 0, 4294967295));
	} else if ($type == FED_TYPE_LIBITEM) {
		$spec = array('libuid' => 'uid', 'quid' => 'uid', 'junkflag' => array('int', 0, 1),
			'deleted' => array('int', 0, 1), 'lastmoddate' => array('int', 0, 4294967295));
	} else if ($type == FED_TYPE_QUESTION) {
		$spec = array('uid' => 'uid', 'adddate' => array('int', 0, 4294967295), 'lastmoddate' => array('int', 0, 4294967295),
			'author' => 'text', 'description' => array('text', 254), 'qtype' => array('text', 20),
			'control' => 'text', 'qcontrol' => 'text', 'qtext' => 'text', 'answer' => 'text', 'extref' => 'text',
			'solution' => 'text', 'solutionopts' => array('int', 0, 255), 'license' => array('int', 0, 255),
			'ancestorauthors' => 'text', 'otherattribution' => 'text', 'deleted' => array('int', 0, 1),
			'isrand' => array('int', 0, 1), 'a11yalttype' => array('int', 0, 255),
			'a11yalt' => 'ref', 'replaceby' => 'ref');
	} else {
		return null;
	}
	foreach ($spec as $key => $rule) {
		if (!array_key_exists($key, $raw)) { return null; }
		$v = $raw[$key];
		if ($rule === 'uid') {
			$v = fed_clean_uid($v);
		} else if ($rule === 'ref') {
			$v = fed_clean_ref($v);
		} else if ($rule === 'text') {
			$v = fed_clean_text($v);
		} else if ($rule[0] === 'text') {
			$v = fed_clean_text($v, $rule[1]);
		} else {
			$v = fed_clean_int($v, $rule[1], $rule[2]);
		}
		if ($v === null) { return null; }
		$out[$key] = $v;
	}
	if ($type == FED_TYPE_QUESTION) {
		$out['imgs'] = array();
		if (isset($raw['imgs']) && is_array($raw['imgs'])) {
			foreach (array_slice($raw['imgs'], 0, FED_MAX_IMAGES) as $img) {
				if (!is_array($img) || !isset($img['var'], $img['filename'], $img['alttext'])) { continue; }
				$var = fed_clean_text($img['var'], 50);
				$fn = fed_clean_text($img['filename'], 2000);
				$alt = fed_clean_text($img['alttext'], 254);
				if ($var === null || $fn === null || $alt === null || !preg_match('~^https?://~i', $fn)) { continue; }
				$out['imgs'][] = array('var' => $var, 'filename' => $fn, 'alttext' => $alt);
			}
		}
	}
	return $out;
}

/**
 * Normalize a list of records, dropping unusable ones.
 * @param int $invalid  incremented for each record dropped
 */
function fed_normalize_list($type, $recs, &$invalid) {
	$out = array();
	if (!is_array($recs)) { return $out; }
	foreach ($recs as $raw) {
		$rec = fed_normalize_record($type, $raw);
		if ($rec === null) { $invalid++; } else { $out[] = $rec; }
	}
	return $out;
}
