<?php
// Federation v2: pulling from a peer and staging incoming changes.
// See federation-plan.md.
//
// A pull NEVER writes to imas_questionset / imas_libraries / imas_library_items.
// It only writes to the imas_federation_* tables: the sync base
// (imas_federation_items) for records already identical on both sides, and
// the review queue (imas_federation_staged) for everything that would change
// local content.

require_once __DIR__ . '/federationfuncs.php';

// staged kinds
const FED_KIND_NEW = 1;
const FED_KIND_UPDATE = 2;
const FED_KIND_CONFLICT = 3;
const FED_KIND_DELETE = 4;
const FED_KIND_DELVEDIT = 5;   // deleted on one side, edited on the other

// staged status
const FED_STATUS_PENDING = 0;
const FED_STATUS_APPLIED = 1;
const FED_STATUS_REJECTED = 2;
const FED_STATUS_SUPERSEDED = 3;

// pull status
const FED_PULL_ACTIVE = 0;
const FED_PULL_DONE = 1;
const FED_PULL_ABANDONED = 2;

// pull stages, in order; libraries first so parents exist, library items last
const FED_STAGE_TYPES = array(0 => FED_TYPE_LIB, 1 => FED_TYPE_QUESTION, 2 => FED_TYPE_LIBITEM);
const FED_STAGE_API = array(0 => 'libs', 1 => 'questions', 2 => 'libitems');

// A new pull starts this many seconds before the previous cursor, to catch rows
// whose transaction committed late.  Unchanged rows are skipped by hash.
const FED_PULL_OVERLAP = 120;

// Largest response accepted from a peer in a single request
const FED_MAX_RESPONSE = 67108864;

const FED_KIND_NAMES = array(
	FED_KIND_NEW => 'New',
	FED_KIND_UPDATE => 'Update',
	FED_KIND_CONFLICT => 'Conflict',
	FED_KIND_DELETE => 'Delete',
	FED_KIND_DELVEDIT => 'Deleted vs. edited'
);

/**
 * Decide what to do with one incoming record.  Pure function (no DB).
 *
 * @param string      $remoteHash
 * @param bool        $remoteDeleted
 * @param string|null $localHash     null if there is no local copy
 * @param bool        $localDeleted
 * @param string|null $baseHash      hash recorded at last sync with this peer; null if never synced
 * @return array  array(action, kind): action is 'skip', 'insync' (refresh base), or 'stage'
 */
function fed_classify($remoteHash, $remoteDeleted, $localHash, $localDeleted, $baseHash) {
	if ($localHash === null) {
		if ($remoteDeleted) {
			return array('skip', 0);        // nothing to import
		}
		if ($baseHash !== null && $baseHash === $remoteHash) {
			return array('skip', 0);        // already seen this version
		}
		return array('stage', FED_KIND_NEW);
	}
	if ($localHash === $remoteHash || ($remoteDeleted && $localDeleted)) {
		return array('insync', 0);
	}
	if ($baseHash === null) {
		// exists on both sides, differs, and we have no record of a common version
		return array('stage', FED_KIND_CONFLICT);
	}
	$remoteChanged = ($remoteHash !== $baseHash);
	$localChanged = ($localHash !== $baseHash);
	if (!$remoteChanged) {
		return array('skip', 0);            // only we changed it
	}
	if (!$localChanged) {
		return array('stage', ($remoteDeleted && !$localDeleted) ? FED_KIND_DELETE : FED_KIND_UPDATE);
	}
	return array('stage', ($remoteDeleted != $localDeleted) ? FED_KIND_DELVEDIT : FED_KIND_CONFLICT);
}

/**
 * UIDs of questions a wire question record refers to
 */
function fed_record_refs($rec) {
	$refs = array();
	if (preg_match_all('/includecodefrom\(UID(\d+)\)/', $rec['control'], $m)) {
		$refs = array_merge($refs, $m[1]);
	}
	if (preg_match_all('/includeqtextfrom\(UID(\d+)\)/', $rec['qtext'], $m)) {
		$refs = array_merge($refs, $m[1]);
	}
	if ($rec['replaceby'] !== '0') { $refs[] = $rec['replaceby']; }
	if ($rec['a11yalt'] !== '0') { $refs[] = $rec['a11yalt']; }
	return array_values(array_unique($refs));
}

// ---------------------------------------------------------------------
// HTTP
// ---------------------------------------------------------------------

/**
 * Signed request to a peer's federatedapi.php
 * @param array $peer  row from imas_federation_peers (url, secret)
 * @return array  array(data, error): one of them is null
 */
function fed_request($peer, $type, $lm = 0, $id = 0, $limit = 0) {
	global $CFG;
	$p = array('peer' => fed_my_peer_name(), 'ts' => time(), 'type' => $type,
		'lm' => (int) $lm, 'id' => (int) $id, 'limit' => (int) $limit);
	$p['sig'] = fed_sign($peer['secret'], $p);

	$base = rtrim($peer['url'], '/');
	if (!preg_match('~^https?://~i', $base)) {
		return array(null, 'Peer URL must start with https://');
	}
	if (stripos($base, 'https://') !== 0 && empty($CFG['federation']['allowhttp'])) {
		return array(null, 'Peer URL must use https');
	}
	$url = $base . '/admin/federatedapi.php?' . http_build_query($p);

	$body = false;
	$httpcode = 0;
	if (function_exists('curl_init')) {
		$ch = curl_init($url);
		$buf = '';
		curl_setopt_array($ch, array(
			CURLOPT_WRITEFUNCTION => function ($c, $chunk) use (&$buf) {
				$buf .= $chunk;
				return strlen($buf) > FED_MAX_RESPONSE ? 0 : strlen($chunk);   // 0 aborts the transfer
			},
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_CONNECTTIMEOUT => 15,
			CURLOPT_TIMEOUT => 120,
			CURLOPT_SSL_VERIFYPEER => true,
		));
		$body = curl_exec($ch) === false ? false : $buf;
		$httpcode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
		if ($body === false) {
			$err = curl_error($ch);
			return array(null, 'Request failed: ' . $err);
		}
	} else {
		$ctx = stream_context_create(array('http' => array('timeout' => 120, 'ignore_errors' => true, 'follow_location' => 0)));
		$body = @file_get_contents($url, false, $ctx, 0, FED_MAX_RESPONSE + 1);
		if ($body === false || strlen($body) > FED_MAX_RESPONSE) {
			return array(null, 'Request failed');
		}
		$hdrs = function_exists('http_get_last_response_headers') ? http_get_last_response_headers() : ($http_response_header ?? array());
		if (isset($hdrs[0]) && preg_match('/ (\d{3}) /', $hdrs[0], $m)) {
			$httpcode = (int) $m[1];
		}
	}
	$data = json_decode($body, true);
	if (!is_array($data)) {
		return array(null, 'Peer returned an invalid response (HTTP ' . $httpcode . ')');
	}
	if (isset($data['error'])) {
		return array(null, 'Peer error: ' . $data['error']);
	}
	if ($httpcode != 200 || ($data['v'] ?? 0) != FED_PROTOCOL_VERSION || $data['type'] != $type) {
		return array(null, 'Unexpected response from peer (HTTP ' . $httpcode . ')');
	}
	return array($data, null);
}

// ---------------------------------------------------------------------
// Local state lookups, keyed by item uid, as wire records
// ---------------------------------------------------------------------

/**
 * @param int   $type
 * @param array $recs  incoming wire records
 * @return array  uid => wire record of the local copy
 */
function fed_local_records($type, $recs) {
	global $DBH;
	$out = array();
	if (count($recs) == 0) { return $out; }
	if ($type == FED_TYPE_LIB || $type == FED_TYPE_QUESTION) {
		$uids = array();
		foreach ($recs as $rec) { $uids[] = $rec['uid']; }
		$ph = Sanitize::generateQueryPlaceholders($uids);
		if ($type == FED_TYPE_LIB) {
			$query = "SELECT il.*,pl.uniqueid AS parentuid FROM imas_libraries AS il ";
			$query .= "LEFT JOIN imas_libraries AS pl ON il.parent=pl.id WHERE il.uniqueid IN ($ph)";
			$stm = $DBH->prepare($query);
			$stm->execute($uids);
			foreach (fed_serialize_libraries($stm->fetchAll(PDO::FETCH_ASSOC)) as $rec) {
				if (!isset($out[$rec['uid']])) { $out[$rec['uid']] = $rec; }
			}
		} else {
			$stm = $DBH->prepare("SELECT * FROM imas_questionset WHERE uniqueid IN ($ph) ORDER BY id");   // lowest id first, matching the sender's canonical copy
			$stm->execute($uids);
			list($local,) = fed_serialize_questions($stm->fetchAll(PDO::FETCH_ASSOC));
			foreach ($local as $rec) {
				if (!isset($out[$rec['uid']])) { $out[$rec['uid']] = $rec; }
			}
		}
	} else if ($type == FED_TYPE_LIBITEM) {
		$libuids = array(); $quids = array(); $wanted = array();
		foreach ($recs as $rec) {
			$libuids[$rec['libuid']] = 1;
			$quids[$rec['quid']] = 1;
			$wanted[fed_record_uid($type, $rec)] = 1;
		}
		$libuids = array_keys($libuids); $quids = array_keys($quids);
		$query = "SELECT il.uniqueid AS libuid,iq.uniqueid AS quid,ili.junkflag,ili.deleted,ili.lastmoddate ";
		$query .= "FROM imas_library_items AS ili JOIN imas_libraries AS il ON il.id=ili.libid ";
		$query .= "JOIN imas_questionset AS iq ON iq.id=ili.qsetid ";
		$query .= "WHERE il.uniqueid IN (" . Sanitize::generateQueryPlaceholders($libuids) . ") ";
		$query .= "AND iq.uniqueid IN (" . Sanitize::generateQueryPlaceholders($quids) . ")";
		$stm = $DBH->prepare($query);
		$stm->execute(array_merge($libuids, $quids));
		// there can be more than one row per pair; prefer an undeleted one, then the newest
		foreach (fed_serialize_libitems($stm->fetchAll(PDO::FETCH_ASSOC)) as $rec) {
			$key = fed_record_uid($type, $rec);
			if (!isset($wanted[$key])) { continue; }
			if (!isset($out[$key]) || ($out[$key]['deleted'] && !$rec['deleted']) ||
				($out[$key]['deleted'] == $rec['deleted'] && $rec['lastmoddate'] > $out[$key]['lastmoddate'])
			) {
				$out[$key] = $rec;
			}
		}
	}
	return $out;
}

function fed_get_base($peerid, $type, $uids) {
	global $DBH;
	$out = array();
	if (count($uids) == 0) { return $out; }
	$ph = Sanitize::generateQueryPlaceholders($uids);
	$stm = $DBH->prepare("SELECT id,uid,content_hash FROM imas_federation_items WHERE peerid=? AND type=? AND uid IN ($ph)");
	$stm->execute(array_merge(array($peerid, $type), $uids));
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		$out[$row['uid']] = $row;
	}
	return $out;
}

function fed_get_pending($peerid, $type, $uids) {
	global $DBH;
	$out = array();
	if (count($uids) == 0) { return $out; }
	$ph = Sanitize::generateQueryPlaceholders($uids);
	$stm = $DBH->prepare("SELECT id,uid,kind,content_hash FROM imas_federation_staged WHERE peerid=? AND type=? AND status=? AND uid IN ($ph)");
	$stm->execute(array_merge(array($peerid, $type, FED_STATUS_PENDING), $uids));
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		$out[$row['uid']] = $row;
	}
	return $out;
}

// ---------------------------------------------------------------------
// Processing a batch
// ---------------------------------------------------------------------

/**
 * Classify a batch of incoming records and update the sync base / staging queue.
 *
 * @param int   $parentrefs  array uid => parent staged id (for dependencies); optional
 * @return array  array(counts, stagedids): counts by outcome; stagedids uid => staged row id
 */
function fed_process_records($peerid, $pullid, $type, $recs, $parents = array()) {
	global $DBH;
	$counts = array('new' => 0, 'update' => 0, 'conflict' => 0, 'delete' => 0, 'delvedit' => 0, 'insync' => 0, 'skipped' => 0, 'invalid' => 0);
	$kindkeys = array(FED_KIND_NEW => 'new', FED_KIND_UPDATE => 'update', FED_KIND_CONFLICT => 'conflict',
		FED_KIND_DELETE => 'delete', FED_KIND_DELVEDIT => 'delvedit');
	$stagedids = array();
	$recs = fed_normalize_list($type, $recs, $counts['invalid']);   // never trust the peer's data
	if (count($recs) == 0) { return array($counts, $stagedids); }

	// The same uid can appear more than once in a batch (uniqueids are not guaranteed
	// unique in imas_questionset).  The base/pending lookups below are done once per batch,
	// so duplicates would each try to insert the same sync-base row.  Keep one per uid:
	// prefer an undeleted copy, then the most recently modified.
	$byuid = array();
	foreach ($recs as $rec) {
		$u = fed_record_uid($type, $rec);
		if (!isset($byuid[$u])) {
			$byuid[$u] = $rec;
		} else {
			$cur = $byuid[$u];
			if ((!empty($cur['deleted']) && empty($rec['deleted'])) ||
				(!empty($cur['deleted']) == !empty($rec['deleted']) && $rec['lastmoddate'] > $cur['lastmoddate'])
			) {
				$byuid[$u] = $rec;
			}
			$counts['skipped']++;
		}
	}
	$recs = array_values($byuid);

	$uids = array_keys($byuid);
	$local = fed_local_records($type, $recs);
	$base = fed_get_base($peerid, $type, $uids);
	$pending = fed_get_pending($peerid, $type, $uids);
	$now = time();

	$upd_base = $DBH->prepare("UPDATE imas_federation_items SET remote_lastmod=?,local_lastmod=?,content_hash=? WHERE id=?");
	$ins_base = $DBH->prepare("INSERT INTO imas_federation_items (peerid,type,uid,remote_lastmod,local_lastmod,content_hash) VALUES (?,?,?,?,?,?)");
	$supersede = $DBH->prepare("UPDATE imas_federation_staged SET status=?,resolved=? WHERE id=?");
	$ins_stage = $DBH->prepare("INSERT INTO imas_federation_staged (peerid,pullid,type,uid,kind,status,parentstageid,remote_lastmod,content_hash,staged,payload) VALUES (?,?,?,?,?,?,?,?,?,?,?)");

	foreach ($recs as $rec) {
		$uid = fed_record_uid($type, $rec);
		$remoteHash = fed_hash_record($type, $rec);
		$lrec = isset($local[$uid]) ? $local[$uid] : null;
		$localHash = $lrec === null ? null : fed_hash_record($type, $lrec);
		$baseHash = isset($base[$uid]) ? $base[$uid]['content_hash'] : null;

		list($action, $kind) = fed_classify($remoteHash, !empty($rec['deleted']),
			$localHash, $lrec !== null && !empty($lrec['deleted']), $baseHash);

		if ($action == 'insync') {
			// identical (or both deleted): record as the common version, drop any stale review item
			$localmod = $lrec['lastmoddate'];
			if (isset($base[$uid])) {
				$upd_base->execute(array($rec['lastmoddate'], $localmod, $remoteHash, $base[$uid]['id']));
			} else {
				$ins_base->execute(array($peerid, $type, $uid, $rec['lastmoddate'], $localmod, $remoteHash));
			}
			if (isset($pending[$uid])) {
				$supersede->execute(array(FED_STATUS_SUPERSEDED, $now, $pending[$uid]['id']));
			}
			$counts['insync']++;
		} else if ($action == 'stage') {
			if (isset($pending[$uid])) {
				if ($pending[$uid]['content_hash'] === $remoteHash && $pending[$uid]['kind'] == $kind) {
					$stagedids[$uid] = $pending[$uid]['id'];  // already waiting for review
					$counts[$kindkeys[$kind]]++;
					continue;
				}
				$supersede->execute(array(FED_STATUS_SUPERSEDED, $now, $pending[$uid]['id']));
			}
			$ins_stage->execute(array($peerid, $pullid, $type, $uid, $kind, FED_STATUS_PENDING,
				isset($parents[$uid]) ? $parents[$uid] : 0, $rec['lastmoddate'], $remoteHash, $now,
				json_encode($rec, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_IGNORE)));
			$stagedids[$uid] = $DBH->lastInsertId();
			$counts[$kindkeys[$kind]]++;
		} else {
			$counts['skipped']++;
		}
	}
	return array($counts, $stagedids);
}

function fed_add_counts(&$total, $counts) {
	foreach ($counts as $k => $v) {
		$total[$k] = ($total[$k] ?? 0) + $v;
	}
}

// ---------------------------------------------------------------------
// Pull sessions
// ---------------------------------------------------------------------

function fed_get_active_pull($peerid) {
	global $DBH;
	$stm = $DBH->prepare("SELECT * FROM imas_federation_pulls WHERE peerid=? AND status=? ORDER BY id DESC LIMIT 1");
	$stm->execute(array($peerid, FED_PULL_ACTIVE));
	return $stm->fetch(PDO::FETCH_ASSOC);
}

/**
 * Start a pull, or return the one already in progress.
 */
function fed_pull_start($peerid) {
	global $DBH;
	$active = fed_get_active_pull($peerid);
	if ($active !== false) { return $active; }

	// where did the last completed pull end?
	$prev = array(0 => array(0, 0), 1 => array(0, 0), 2 => array(0, 0));
	$stm = $DBH->prepare("SELECT stats FROM imas_federation_pulls WHERE peerid=? AND status=? ORDER BY id DESC LIMIT 1");
	$stm->execute(array($peerid, FED_PULL_DONE));
	$laststats = $stm->fetchColumn();
	if ($laststats !== false) {
		$ls = json_decode($laststats, true);
		if (isset($ls['endcursors'])) {
			foreach ($ls['endcursors'] as $s => $c) {
				$prev[(int) $s] = array((int) $c[0], (int) $c[1]);
			}
		}
	}
	$start = array();
	foreach ($prev as $s => $c) {
		$start[$s] = $c[0] > 0 ? array(max(0, $c[0] - FED_PULL_OVERLAP), 0) : array(0, 0);
	}
	$stats = array('counts' => array(), 'rows' => array(0 => 0, 1 => 0, 2 => 0), 'endcursors' => new stdClass(), 'withheld' => array(), 'unresolved' => 0, 'truncated' => 0);
	$stm = $DBH->prepare("INSERT INTO imas_federation_pulls (peerid,starttime,status,stage,lastcursor,startcursors,stats) VALUES (?,?,?,?,?,?,?)");
	$stm->execute(array($peerid, time(), FED_PULL_ACTIVE, 0, '', json_encode(array('start' => $start, 'prev' => $prev)), json_encode($stats)));
	$id = $DBH->lastInsertId();
	$stm = $DBH->prepare("SELECT * FROM imas_federation_pulls WHERE id=?");
	$stm->execute(array($id));
	return $stm->fetch(PDO::FETCH_ASSOC);
}

function fed_pull_abandon($pullid) {
	global $DBH;
	$stm = $DBH->prepare("UPDATE imas_federation_pulls SET status=?,endtime=? WHERE id=? AND status=?");
	$stm->execute(array(FED_PULL_ABANDONED, time(), $pullid, FED_PULL_ACTIVE));
}

/**
 * Fetch and process one batch for an active pull.
 * @return array  array(pull row, error): error is null on success
 */
function fed_pull_step($peer, $pull) {
	global $DBH;
	if ($pull['status'] != FED_PULL_ACTIVE) {
		return array($pull, 'Pull is not active');
	}
	$stage = (int) $pull['stage'];
	$starts = json_decode($pull['startcursors'], true);
	$stats = json_decode($pull['stats'], true);

	if ($pull['lastcursor'] !== '') {
		list($lm, $id) = array_map('intval', explode(':', $pull['lastcursor']));
	} else {
		list($lm, $id) = $starts['start'][$stage];
	}

	list($resp, $err) = fed_request($peer, FED_STAGE_API[$stage], $lm, $id);
	if ($err !== null) {
		return array($pull, $err);
	}
	if (!isset($resp['data']) || !is_array($resp['data']) || !isset($resp['cursor']) || !is_array($resp['cursor']) ||
		!isset($resp['cursor']['lm'], $resp['cursor']['id']) || !is_numeric($resp['cursor']['lm']) || !is_numeric($resp['cursor']['id'])
	) {
		return array($pull, 'Malformed response from peer');
	}
	$resp['cursor'] = array('lm' => max(0, (int) $resp['cursor']['lm']), 'id' => max(0, (int) $resp['cursor']['id']));
	$type = FED_STAGE_TYPES[$stage];
	$counts = array();
	$invalid = 0;
	$data = fed_normalize_list($type, $resp['data'], $invalid);
	$deps = ($stage == 1 && isset($resp['deps'])) ? fed_normalize_list($type, $resp['deps'], $invalid) : array();

	$DBH->beginTransaction();
	list($c, $stagedids) = fed_process_records($peer['id'], $pull['id'], $type, $data);
	fed_add_counts($counts, $c);
	$counts['invalid'] = $invalid;   // includes the dependency list, which is normalized above too
	$stats['rows'][$stage] += count($resp['data']);

	if ($stage == 1) {
		// referenced questions come along with the batch; link each to a question that needs it
		$parents = array();
		foreach ($data as $rec) {
			if (!isset($stagedids[$rec['uid']])) { continue; }
			foreach (fed_record_refs($rec) as $ref) {
				if (!isset($parents[$ref])) { $parents[$ref] = $stagedids[$rec['uid']]; }
			}
		}
		list($c,) = fed_process_records($peer['id'], $pull['id'], $type, $deps, $parents);
		$c['invalid'] = 0;   // already counted above
		fed_add_counts($counts, $c);
		foreach (array_merge($data, $deps) as $rec) {
			if (preg_match('/include(code|qtext)from\(\d+\)/', $rec['control'] . ' ' . $rec['qtext'])) {
				$stats['unresolved']++;
			}
		}
		if (!empty($resp['withheld']) && is_array($resp['withheld'])) {
			$withheld = array_filter(array_map('fed_clean_uid', array_filter($resp['withheld'], 'is_scalar')));
			$stats['withheld'] = array_slice(array_values(array_unique(array_merge($stats['withheld'], $withheld))), 0, 200);
		}
		if (!empty($resp['truncated'])) { $stats['truncated']++; }
	}

	if (!isset($stats['counts'][$stage])) { $stats['counts'][$stage] = array(); }
	fed_add_counts($stats['counts'][$stage], $counts);

	$newstage = $stage;
	$lastcursor = $resp['cursor']['lm'] . ':' . $resp['cursor']['id'];
	$endtime = 0;
	$status = FED_PULL_ACTIVE;
	if (empty($resp['more'])) {
		// stage finished: remember where it ended.  If nothing came back, keep the previous end
		// rather than the overlapped start, so the cursor doesn't creep backwards on empty pulls.
		$stats['endcursors'] = (array) $stats['endcursors'];
		$stats['endcursors'][$stage] = $stats['rows'][$stage] > 0
			? array((int) $resp['cursor']['lm'], (int) $resp['cursor']['id'])
			: $starts['prev'][$stage];
		$newstage = $stage + 1;
		$lastcursor = '';
		if ($newstage > 2) {
			$status = FED_PULL_DONE;
			$endtime = time();
		}
	}
	$stm = $DBH->prepare("UPDATE imas_federation_pulls SET stage=?,lastcursor=?,stats=?,status=?,endtime=? WHERE id=?");
	$stm->execute(array(min($newstage, 2), $lastcursor, json_encode($stats), $status, $endtime, $pull['id']));
	$DBH->commit();

	$stm = $DBH->prepare("SELECT * FROM imas_federation_pulls WHERE id=?");
	$stm->execute(array($pull['id']));
	return array($stm->fetch(PDO::FETCH_ASSOC), null);
}
