<?php
//IMathAS:  Federation API (sender), protocol version 2
//See federation-plan.md
//
//Read-only.  A peer (a row in imas_federation_peers, matched by peername)
//requests a batch of changed records, using a signed request.
//
//Parameters (GET):
//  peer   name this peer is known by in our imas_federation_peers
//  ts     unix timestamp of request; must be within FED_MAX_SKEW of our clock
//  type   ping | libs | questions | libitems
//  lm,id  cursor: return records after (lastmoddate=lm, id=id) in ascending order
//  limit  max records (capped)
//  sig    base64 HMAC-SHA256 of fed_signing_string() using the shared secret
//
//Response: JSON
//  {v, type, more, cursor:{lm,id}, data:[wire records]}
//  questions responses also include deps:[wire records], withheld:[uids], truncated:bool
//  errors: HTTP 4xx with {error:"..."}

require_once "../init_without_validate.php";
require_once "../includes/filehandler.php";
require_once "../includes/federationfuncs.php";

function fed_api_respond($arr, $code = 200) {
	http_response_code($code);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($arr, JSON_INVALID_UTF8_IGNORE | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE);
	exit;
}

// optional kill switch: $CFG['federation']['enableapi'] = false;
if (isset($CFG['federation']['enableapi']) && !$CFG['federation']['enableapi']) {
	fed_api_respond(array('error' => 'Not available'), 404);
}

foreach (array('peer', 'ts', 'type', 'sig') as $reqparam) {
	if (!isset($_GET[$reqparam]) || is_array($_GET[$reqparam])) {
		fed_api_respond(array('error' => 'Missing parameter: ' . $reqparam), 400);
	}
}

$req = array(
	'peer' => $_GET['peer'],
	'ts' => intval($_GET['ts']),
	'type' => $_GET['type'],
	'lm' => isset($_GET['lm']) ? intval($_GET['lm']) : 0,
	'id' => isset($_GET['id']) ? intval($_GET['id']) : 0,
	'limit' => isset($_GET['limit']) ? intval($_GET['limit']) : 0,
);

$stm = $DBH->prepare("SELECT id,secret FROM imas_federation_peers WHERE peername=:peername");
$stm->execute(array(':peername' => $req['peer']));
$peer = $stm->fetch(PDO::FETCH_ASSOC);
// Same response for unknown peer, empty secret and bad signature, and the signature is computed
// in every case, so neither the reply nor its timing shows whether a peer name exists.
// A peer with an empty secret can never authenticate (anyone could sign with an empty key).
$secret = ($peer !== false && is_string($peer['secret']) && strlen($peer['secret']) > 0) ? $peer['secret'] : null;
$sigok = fed_verify_sig($secret === null ? bin2hex(random_bytes(16)) : $secret, $req, $_GET['sig']);
if ($secret === null || !$sigok) {
	fed_api_respond(array('error' => 'Invalid authorization'), 403);
}
if (abs(time() - $req['ts']) > FED_MAX_SKEW) {
	fed_api_respond(array('error' => 'Request timestamp out of range; check server clocks'), 403);
}

if ($req['lm'] < 0 || $req['id'] < 0) {
	fed_api_respond(array('error' => 'Invalid cursor'), 400);
}

$type = $req['type'];
if ($type === 'ping') {
	fed_api_respond(array('v' => FED_PROTOCOL_VERSION, 'type' => 'ping', 'name' => fed_my_peer_name(), 'time' => time()));
}

$maxlimit = ($type === 'questions') ? 200 : 1000;
$limit = $req['limit'] > 0 ? min($req['limit'], $maxlimit) : $maxlimit;
$cursorsql = '(%1$s>:lm OR (%1$s=:lm2 AND %2$s>:id)) ORDER BY %1$s,%2$s LIMIT ' . ($limit + 1);
$cursorparams = array(':lm' => $req['lm'], ':lm2' => $req['lm'], ':id' => $req['id']);

$response = array('v' => FED_PROTOCOL_VERSION, 'type' => $type);

if ($type === 'libs') {
	$query = 'SELECT il.id,il.uniqueid,il.name,il.parent,il.sortorder,il.deleted,il.adddate,il.lastmoddate,pl.uniqueid AS parentuid ';
	$query .= 'FROM imas_libraries AS il LEFT JOIN imas_libraries AS pl ON il.parent=pl.id ';
	$query .= 'WHERE ' . FED_LIB_SHARED_SQL . ' AND ' . sprintf($cursorsql, 'il.lastmoddate', 'il.id');
	$stm = $DBH->prepare($query);
	$stm->execute($cursorparams);
	$rows = $stm->fetchAll(PDO::FETCH_ASSOC);
	$more = count($rows) > $limit;
	if ($more) { array_pop($rows); }
	$response['data'] = fed_serialize_libraries($rows);
} else if ($type === 'libitems') {
	// Walk ili in lastmoddate order via its index and join out per row (STRAIGHT_JOIN +
	// FORCE INDEX), rather than letting the planner join first and sort; stops at limit+1.
	$query = 'SELECT STRAIGHT_JOIN ili.id,il.uniqueid AS libuid,iq.uniqueid AS quid,ili.junkflag,ili.deleted,ili.lastmoddate ';
	$query .= 'FROM imas_library_items AS ili FORCE INDEX (lastmoddate) ';
	$query .= 'JOIN imas_libraries AS il ON il.id=ili.libid AND ' . FED_LIB_SHARED_SQL . ' ';
	$query .= 'JOIN imas_questionset AS iq ON iq.id=ili.qsetid AND ' . FED_QUESTION_SHARED_SQL . ' ';
	$query .= 'WHERE ili.lastmoddate>=:lm0 AND ' . sprintf($cursorsql, 'ili.lastmoddate', 'ili.id');
	$stm = $DBH->prepare($query);
	$stm->execute($cursorparams + array(':lm0' => $req['lm']));
	$rows = $stm->fetchAll(PDO::FETCH_ASSOC);
	$more = count($rows) > $limit;
	if ($more) { array_pop($rows); }
	$response['data'] = fed_serialize_libitems($rows);
} else if ($type === 'questions') {
	// questions in at least one shared library (library items of any deleted state, so
	// that a question's own deleted flag keeps flowing after it is removed from a library)
	// Walk imas_questionset in lastmoddate order via the index and probe EXISTS per
	// row, stopping after limit+1 matches. The hints stop MySQL from turning the
	// EXISTS into a materialized semijoin (which scans/sorts every federated question),
	// and the >= form of the cursor lets the lastmoddate index be used as a range.
	$query = 'SELECT /*+ NO_SEMIJOIN(@fedsub) */ iq.* FROM imas_questionset AS iq FORCE INDEX (lastmoddate) WHERE ' . FED_QUESTION_SHARED_SQL . ' ';
	$query .= 'AND EXISTS (SELECT /*+ QB_NAME(fedsub) */ 1 FROM imas_library_items AS ili JOIN imas_libraries AS il ON il.id=ili.libid AND ' . FED_LIB_SHARED_SQL;
	$query .= ' WHERE ili.qsetid=iq.id) ';
	$query .= 'AND ' . FED_QUESTION_CANON_SQL . ' ';
	$query .= 'AND iq.lastmoddate>=:lm0 AND ' . sprintf($cursorsql, 'iq.lastmoddate', 'iq.id');
	$stm = $DBH->prepare($query);
	$stm->execute($cursorparams + array(':lm0' => $req['lm']));
	$rows = $stm->fetchAll(PDO::FETCH_ASSOC);
	$more = count($rows) > $limit;
	if ($more) { array_pop($rows); }

	list($response['data'], $refids) = fed_serialize_questions($rows);
	$haveids = array();
	foreach ($rows as $row) { $haveids[] = $row['id']; }
	list($response['deps'], $response['withheld'], $response['truncated']) = fed_collect_dependencies($refids, $haveids);
} else {
	fed_api_respond(array('error' => 'Unknown type'), 400);
}

if (count($rows) > 0) {
	$last = $rows[count($rows) - 1];
	$response['cursor'] = array('lm' => (int) $last['lastmoddate'], 'id' => (int) $last['id']);
} else {
	$response['cursor'] = array('lm' => $req['lm'], 'id' => $req['id']);
}
$response['more'] = $more;

if (!$more) {
	$stm = $DBH->prepare('UPDATE imas_federation_peers SET lastpull=:now WHERE id=:id');
	$stm->execute(array(':now' => time(), ':id' => $peer['id']));
}

fed_api_respond($response);
