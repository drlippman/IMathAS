<?php
//IMathAS:  Federation review queue: approve, reject and resolve staged changes
//See federation-plan.md

require_once "../init.php";
require_once "../includes/federationapply.php";
require_once "../includes/diff.php";

if ($myrights < 100) {
	echo "Not authorized";
	exit;
}

$peerid = intval($_GET['peer'] ?? 0);
$stm = $DBH->prepare('SELECT id,peername FROM imas_federation_peers WHERE id=:id');
$stm->execute(array(':id' => $peerid));
$peer = $stm->fetch(PDO::FETCH_ASSOC);
if ($peer === false) {
	echo 'Invalid peer ID';
	exit;
}

$typenames = array(FED_TYPE_LIB => _('Library'), FED_TYPE_QUESTION => _('Question'), FED_TYPE_LIBITEM => _('Library assignment'));

// fields shown / compared / mergeable, by record type
$fieldsets = array(
	FED_TYPE_LIB => array('name', 'parent', 'sortorder', 'deleted'),
	FED_TYPE_QUESTION => array('description', 'author', 'qtype', 'control', 'qcontrol', 'qtext', 'answer', 'extref',
		'solution', 'solutionopts', 'license', 'ancestorauthors', 'otherattribution', 'deleted', 'a11yalttype', 'a11yalt', 'replaceby'),
	FED_TYPE_LIBITEM => array('junkflag', 'deleted'),
);
$textfields = array('name', 'description', 'author', 'control', 'qcontrol', 'qtext', 'answer', 'extref', 'solution', 'ancestorauthors', 'otherattribution');

function fed_row_label($type, $rec) {
	if ($type == FED_TYPE_LIB) {
		return $rec['name'] ?? '';
	} else if ($type == FED_TYPE_QUESTION) {
		return $rec['description'] ?? '';
	}
	$label = sprintf(_('Question %s in library %s'), $rec['quid'] ?? '', $rec['libuid'] ?? '');
	if (!empty($rec['deleted'])) { $label .= ' (' . _('removed') . ')'; }
	return $label;
}

function fed_imgs_text($imgs) {
	$lines = array();
	foreach ($imgs as $img) {
		$lines[] = $img['var'] . ': ' . $img['alttext'] . ' [' . basename(parse_url($img['filename'], PHP_URL_PATH) ?: $img['filename']) . ']';
	}
	return implode("\n", $lines);
}

function fed_value_text($field, $rec) {
	if ($field == 'imgs') { return fed_imgs_text($rec['imgs'] ?? array()); }
	return (string) ($rec[$field] ?? '');
}

/** Line diff from old to new as escaped HTML */
function fed_diff_html($old, $new) {
	$old = str_replace("\r\n", "\n", $old);
	$new = str_replace("\r\n", "\n", $new);
	$ol = $old === '' ? array() : explode("\n", $old);
	$nl = $new === '' ? array() : explode("\n", $new);
	$out = '';
	foreach (diff($ol, $nl) as $part) {
		if (is_array($part)) {
			foreach ($part['d'] as $line) { $out .= '<del>' . Sanitize::encodeStringForDisplay($line) . "</del>\n"; }
			foreach ($part['i'] as $line) { $out .= '<ins>' . Sanitize::encodeStringForDisplay($line) . "</ins>\n"; }
		} else {
			$out .= Sanitize::encodeStringForDisplay($part) . "\n";
		}
	}
	return $out;
}

$ctx = fed_apply_context($userid, $groupid);
$messages = array();

function fed_report($results) {
	global $DBH, $typenames, $peerid;
	$out = array();
	if (count($results) == 0) { return $out; }
	$ids = array_keys($results);
	$stm = $DBH->prepare('SELECT id,type,payload FROM imas_federation_staged WHERE peerid=? AND id IN (' . Sanitize::generateQueryPlaceholders($ids) . ')');
	$stm->execute(array_merge(array($peerid), $ids));
	$labels = array();
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		$labels[$row['id']] = $typenames[$row['type']] . ': ' . fed_row_label($row['type'], json_decode($row['payload'], true));
	}
	foreach ($results as $id => $r) {
		$label = $labels[$id] ?? ('#' . $id);
		$line = ($r['ok'] ? '' : _('Not done: ')) . $label . ' - ' . $r['msg'];
		if (!empty($r['auto'])) { $line .= ' (' . _('included because another change needs it') . ')'; }
		foreach ($r['warnings'] as $w) { $line .= ' ' . _('Warning:') . ' ' . $w; }
		$out[] = array($r['ok'], $line);
	}
	return $out;
}

// ---- actions (POST) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
	$ids = array_map('intval', (array) ($_POST['ids'] ?? array()));
	if ($_POST['action'] === 'approve' && count($ids) > 0) {
		$messages = fed_report(fed_approve($peerid, $ids, $ctx));
	} else if ($_POST['action'] === 'reject' && count($ids) > 0) {
		$messages = fed_report(fed_reject($peerid, $ids, $ctx));
	} else if ($_POST['action'] === 'resolve') {
		$rid = intval($_POST['id'] ?? 0);
		$rows = fed_load_staged($peerid, array($rid));
		if (!isset($rows[$rid])) {
			$messages[] = array(false, _('That change was not found or has already been handled.'));
		} else {
			$row = $rows[$rid];
			$choice = $_POST['choice'] ?? '';
			if ($choice === 'mine') {
				$messages = fed_report(fed_reject($peerid, array($rid), $ctx));
			} else if ($choice === 'theirs') {
				$messages = fed_report(fed_approve($peerid, array($rid), $ctx, array($rid)));
			} else if ($choice === 'merge') {
				$local = fed_local_records($row['type'], array($row['rec']));
				$mine = $local[$row['uid']] ?? null;
				if ($mine === null) {
					$messages[] = array(false, _('There is no local copy to merge with.'));
				} else {
					$merged = $mine;
					$merged['adddate'] = $row['rec']['adddate'] ?? ($mine['adddate'] ?? 0);
					$picks = is_array($_POST['pick'] ?? null) ? $_POST['pick'] : array();
					$customs = is_array($_POST['custom'] ?? null) ? $_POST['custom'] : array();
					$toolong = false;
					foreach ($fieldsets[$row['type']] as $f) {
						$pick = (isset($picks[$f]) && is_string($picks[$f])) ? $picks[$f] : 'mine';
						if ($pick === 'theirs') {
							$merged[$f] = $row['rec'][$f];
						} else if ($pick === 'custom' && in_array($f, $textfields) && isset($customs[$f]) && is_string($customs[$f])) {
							$merged[$f] = str_replace("\r\n", "\n", $customs[$f]);
							$maxlen = ($f == 'description' || $f == 'name') ? 254 : FED_MAX_TEXT;
							if (strlen($merged[$f]) > $maxlen) { $toolong = true; }
						}
					}
					if ($row['type'] == FED_TYPE_QUESTION) {
						if (($picks['imgs'] ?? 'mine') === 'theirs') {
							$merged['imgs'] = $row['rec']['imgs'];
						} else {
							$merged['_keepimgs'] = true;
						}
					}
					if ($toolong) {
						$messages[] = array(false, _('One of the edited fields is too long.'));
					} else {
						$messages = fed_report(fed_approve($peerid, array($rid), $ctx, array($rid), array($rid => $merged)));
					}
				}
			}
		}
	}
}

$pagetitle = _('Federation Review');
$curBreadcrumb = '<a href="../index.php">Home</a> &gt; <a href="admin2.php">Admin</a> &gt; '
	. '<a href="federationpull.php?peer=' . $peerid . '">' . _('Federation pull') . '</a> &gt; '
	. '<a href="federationreview.php?peer=' . $peerid . '">' . _('Review') . '</a>';
$placeinhead = '<style type="text/css">ins {background-color:#d4fcbc; text-decoration:none; display:block;} del {background-color:#fbb6c2; display:block;} '
	. 'pre.fed {white-space:pre-wrap; max-width:60em; margin:0; padding:.3em; border:1px solid #ccc;} '
	. 'table.fedcmp td {vertical-align:top; padding:.3em;}</style>';
require_once "../header.php";
echo '<div class="breadcrumb">' . $curBreadcrumb . '</div>';
echo '<h1>' . sprintf(_('Staged changes from %s'), Sanitize::encodeStringForDisplay($peer['peername'])) . '</h1>';

foreach ($messages as $m) {
	echo '<p class="' . ($m[0] ? '' : 'noticetext') . '">' . Sanitize::encodeStringForDisplay($m[1]) . '</p>';
}

// ---- detail view ----
if (isset($_GET['id'])) {
	$rid = intval($_GET['id']);
	$stm = $DBH->prepare('SELECT * FROM imas_federation_staged WHERE id=? AND peerid=?');
	$stm->execute(array($rid, $peerid));
	$row = $stm->fetch(PDO::FETCH_ASSOC);
	if ($row === false) {
		echo '<p>' . _('Change not found.') . '</p>';
		require_once "../footer.php";
		exit;
	}
	$rec = json_decode($row['payload'], true);
	$type = (int) $row['type'];
	$local = fed_local_records($type, array($rec));
	$mine = $local[$row['uid']] ?? null;
	$pending = $row['status'] == FED_STATUS_PENDING;
	$showall = !empty($_GET['full']);
	$fullparam = $showall ? '&full=1' : '';

	echo '<h2>' . Sanitize::encodeStringForDisplay($typenames[$type] . ': ' . fed_row_label($type, $rec)) . '</h2>';
	echo '<p>' . Sanitize::encodeStringForDisplay(_(FED_KIND_NAMES[$row['kind']])) . ' &middot; '
		. sprintf(_('Peer modified %s'), tzdate('n/j/y g:i a', $row['remote_lastmod']));
	if ($mine !== null) {
		echo ' &middot; ' . sprintf(_('Local modified %s'), tzdate('n/j/y g:i a', $mine['lastmoddate']));
	}
	echo '</p>';
	if (!$pending) {
		echo '<p>' . _('This change has already been handled.') . '</p>';
	}

	if ($pending) {
		printf('<form method="post" action="federationreview.php?peer=%d&id=%d%s">', $peerid, $rid, $fullparam);
		echo '<input type="hidden" name="action" value="resolve"><input type="hidden" name="id" value="' . $rid . '">';
	}
	$isconflict = in_array($row['kind'], array(FED_KIND_CONFLICT, FED_KIND_DELVEDIT));
	$fields = $fieldsets[$type];
	if ($type == FED_TYPE_QUESTION) { $fields[] = 'imgs'; }
	if ($mine !== null) {
		printf('<p><a href="federationreview.php?peer=%d&id=%d%s">%s</a></p>', $peerid, $rid,
			$showall ? '' : '&full=1',
			$showall ? _('Show only the fields that differ') : _('Show all fields, with changes highlighted in context'));
	}
	echo '<table class="fedcmp"><tbody>';
	$shown = 0;
	foreach ($fields as $f) {
		$theirs = fed_value_text($f, $rec);
		$mineval = $mine === null ? '' : fed_value_text($f, $mine);
		$same = ($mine !== null && $theirs === $mineval);
		if ($same && (!$showall || $theirs === '')) { continue; }
		if ($mine === null && $theirs === '') { continue; }
		if (!$same) { $shown++; }
		echo '<tr><td><b>' . Sanitize::encodeStringForDisplay($f === 'imgs' ? _('images') : $f) . '</b>';
		if ($same) { echo '<br><span class="small">' . _('unchanged') . '</span>'; }
		echo '</td><td>';
		if ($mine === null || $same) {
			echo '<pre class="fed">' . Sanitize::encodeStringForDisplay($theirs) . '</pre>';
		} else {
			echo '<pre class="fed">' . fed_diff_html($mineval, $theirs) . '</pre>';
			echo '<span class="small">' . _('Red: your version only. Green: the peer\'s version only.') . '</span>';
		}
		echo '</td>';
		if ($pending && $isconflict && $mine !== null && !$same) {
			$fn = Sanitize::encodeStringForDisplay($f);
			echo '<td><label><input type="radio" name="pick[' . $fn . ']" value="mine" checked> ' . _('Mine') . '</label><br>';
			echo '<label><input type="radio" name="pick[' . $fn . ']" value="theirs"> ' . _('Theirs') . '</label>';
			if (in_array($f, $textfields)) {
				echo '<br><label><input type="radio" name="pick[' . $fn . ']" value="custom"> ' . _('Edited:') . '</label>';
				echo '<br><textarea name="custom[' . $fn . ']" rows="6" cols="40">' . Sanitize::encodeStringForDisplay($mineval) . '</textarea>';
			}
			echo '</td>';
		}
		echo '</tr>';
	}
	if ($shown == 0 && $mine !== null) {
		echo '<tr><td>' . _('No visible differences in the content that is compared.') . '</td></tr>';
	}
	echo '</tbody></table>';

	if ($pending) {
		echo '<p>';
		if ($isconflict && $mine !== null) {
			echo '<button type="submit" name="choice" value="merge">' . _('Apply my selections above') . '</button> ';
		}
		echo '<button type="submit" name="choice" value="theirs">' . _('Take the peer\'s version') . '</button> ';
		echo '<button type="submit" name="choice" value="mine" class="secondarybtn">' . _('Keep my version') . '</button>';
		echo '</p></form>';
		if ($type == FED_TYPE_QUESTION) {
			echo '<p class="small">' . _('Taking the peer\'s version also copies its images (existing image files are never overwritten) and any questions it refers to that are waiting for review.') . '</p>';
		}
	}
	printf('<p><a href="federationreview.php?peer=%d">%s</a></p>', $peerid, _('Back to the list'));
	require_once "../footer.php";
	exit;
}

// ---- list view ----
echo '<p>' . _('These changes have been downloaded from the peer. Nothing is changed in your libraries until you approve it.') . '</p>';

$kindfilter = intval($_GET['kind'] ?? 0);
$typefilter = intval($_GET['type'] ?? 0);

$stm = $DBH->prepare('SELECT type,kind,COUNT(*) FROM imas_federation_staged WHERE peerid=? AND status=? GROUP BY type,kind');
$stm->execute(array($peerid, FED_STATUS_PENDING));
$summary = array();
while ($r = $stm->fetch(PDO::FETCH_NUM)) {
	$summary[$r[0]][$r[1]] = $r[2];
}

$query = 'SELECT id,type,uid,kind,parentstageid,remote_lastmod,payload FROM imas_federation_staged WHERE peerid=? AND status=?';
$qarr = array($peerid, FED_STATUS_PENDING);
if ($kindfilter > 0) { $query .= ' AND kind=?'; $qarr[] = $kindfilter; }
if ($typefilter > 0) { $query .= ' AND type=?'; $qarr[] = $typefilter; }
$query .= ' ORDER BY type,kind,id LIMIT 500';
$stm = $DBH->prepare($query);
$stm->execute($qarr);
$rows = $stm->fetchAll(PDO::FETCH_ASSOC);

echo '<table class="gb"><thead><tr><th>' . _('Type') . '</th>';
foreach (FED_KIND_NAMES as $kname) {
	echo '<th>' . Sanitize::encodeStringForDisplay(_($kname)) . '</th>';
}
echo '</tr></thead><tbody>';
foreach ($typenames as $t => $tname) {
	echo '<tr><td>' . Sanitize::encodeStringForDisplay($tname) . '</td>';
	foreach (FED_KIND_NAMES as $k => $kname) {
		$n = $summary[$t][$k] ?? 0;
		if ($n > 0) {
			printf('<td><a href="federationreview.php?peer=%d&type=%d&kind=%d">%d</a></td>', $peerid, $t, $k, $n);
		} else {
			echo '<td>0</td>';
		}
	}
	echo '</tr>';
}
echo '</tbody></table>';

if ($kindfilter > 0 || $typefilter > 0) {
	printf('<p><a href="federationreview.php?peer=%d">%s</a></p>', $peerid, _('Show all'));
}

if (count($rows) == 0) {
	echo '<p>' . _('Nothing is waiting for review.') . '</p>';
} else {
	printf('<form method="post" action="federationreview.php?peer=%d&type=%d&kind=%d">', $peerid, $typefilter, $kindfilter);
	echo '<table class="gb"><thead><tr><th><input type="checkbox" id="fedall" aria-label="' . _('Select all') . '"></th><th>' . _('Type') . '</th><th>' . _('Change') . '</th><th>'
		. _('Item') . '</th><th>' . _('Peer modified') . '</th><th>' . _('Needed by') . '</th><th></th></tr></thead><tbody>';
	foreach ($rows as $row) {
		$rec = json_decode($row['payload'], true);
		$isconf = in_array($row['kind'], array(FED_KIND_CONFLICT, FED_KIND_DELVEDIT));
		echo '<tr><td><input type="checkbox" class="fedsel" name="ids[]" value="' . Sanitize::onlyInt($row['id']) . '"></td>';
		echo '<td>' . Sanitize::encodeStringForDisplay($typenames[$row['type']]) . '</td>';
		echo '<td>' . Sanitize::encodeStringForDisplay(_(FED_KIND_NAMES[$row['kind']])) . '</td>';
		echo '<td>' . Sanitize::encodeStringForDisplay(fed_row_label($row['type'], $rec)) . ' <span class="small">(' . Sanitize::encodeStringForDisplay($row['uid']) . ')</span></td>';
		echo '<td>' . tzdate('n/j/y g:i a', $row['remote_lastmod']) . '</td>';
		echo '<td>' . ($row['parentstageid'] > 0 ? '#' . Sanitize::onlyInt($row['parentstageid']) : '') . '</td>';
		printf('<td><a href="federationreview.php?peer=%d&id=%d">%s</a></td></tr>', $peerid, $row['id'], $isconf ? _('Resolve') : _('View'));
	}
	echo '</tbody></table>';
	echo '<p><button type="submit" name="action" value="approve">' . _('Approve selected') . '</button> ';
	echo '<button type="submit" name="action" value="reject" class="secondarybtn">' . _('Keep my version of selected') . '</button></p>';
	echo '<p class="small">' . _('Approving a question also approves the questions it refers to, and approving a library assignment also approves its library and question, if those are waiting. Conflicts have to be resolved one at a time with Resolve.') . '</p>';
	echo '</form>';
	if (count($rows) == 500) {
		echo '<p>' . _('Showing the first 500 matching changes.') . '</p>';
	}
	echo '<script>document.getElementById("fedall").addEventListener("change", function () {'
		. 'var on = this.checked; document.querySelectorAll(".fedsel").forEach(function (c) { c.checked = on; }); });</script>';
}
require_once "../footer.php";
