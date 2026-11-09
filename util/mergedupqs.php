<?php
// Find imas_questionset rows that share a uniqueid, and merge the ones that are
// content-identical down to a single retained copy.
//
// Default is a report only.  Submitting the form with "apply" does the merge:
//  - imas_questions, imas_firstscores, imas_questionerrorlog, imas_a11yreviews and
//    imas_qimages are repointed at the retained copy
//  - imas_library_items are repointed at the retained copy, or removed if the retained
//    copy is already in that library
//  - replaceby / a11yalt, numeric includecodefrom(ID) / includeqtextfrom(ID) references
//    and imas_drillassess.itemids are repointed
//  - the duplicate imas_questionset rows are deleted
// Groups whose copies differ in content are listed with a choice per group: skip, merge to a
// chosen id, or give the later copies a new uniqueid (keeping the timestamp prefix).
// A group with exactly one undeleted copy is merged to that copy regardless of content.
// Trailing whitespace on lines and at the end of fields is ignored when comparing.
// Not repointed: imas_questionset.ancestors (history only).
require_once "../init.php";
if ($myrights < 100) { exit; }
set_time_limit(0);
$pagetitle = 'Merge duplicate questions';
require_once "../header.php";

// fields that must match for copies to be considered the same question
$cmpfields = array('qtype', 'control', 'qtext', 'extref', 'solution', 'solutionopts', 'qcontrol', 'answer', 'hasimg');

$apply = isset($_POST['apply']) && $_POST['apply'] === 'yes';

/**
 * Order so that index 0 is the copy to retain: undeleted first, then shareable,
 * then higher userights, then lowest id.
 */
function mdq_rank($a, $b) {
	if ($a['deleted'] != $b['deleted']) { return $a['deleted'] - $b['deleted']; }
	$sa = ($a['userights'] > 1 && $a['license'] > 0) ? 0 : 1;
	$sb = ($b['userights'] > 1 && $b['license'] > 0) ? 0 : 1;
	if ($sa != $sb) { return $sa - $sb; }
	if ($a['userights'] != $b['userights']) { return $b['userights'] - $a['userights']; }
	return $a['id'] - $b['id'];
}

// normalize a field for comparison: ignore trailing whitespace on each line and at the end of the field
function mdq_norm($v) {
	$v = (string) $v;
	$v = preg_replace('/[ \t\r\f\v]+$/m', '', $v);
	return rtrim($v, " \t\r\n");
}

// make a new uniqueid with the same timestamp prefix (first 11 chars), differing only after that
function mdq_newuid($uid, &$used) {
	global $DBH;
	$uid = (string) $uid;
	$prefix = substr($uid, 0, 10);
	$len = max(strlen($uid) - 10, 1);
	$chk = $DBH->prepare('SELECT 1 FROM imas_questionset WHERE uniqueid=? LIMIT 1');
	for ($try = 0; $try < 10000; $try++) {
		$cand = $prefix . str_pad((string) random_int(0, pow(10, min($len, 15)) - 1), $len, '0', STR_PAD_LEFT);
		if ($cand === $uid || isset($used[$cand])) { continue; }
		$chk->execute(array($cand));
		if ($chk->fetchColumn() !== false) { continue; }
		$used[$cand] = 1;
		return $cand;
	}
	throw new Exception('Could not generate a unique uniqueid for ' . $uid);
}

// line diff of two normalized strings.  Returns array of array(' '|'-'|'+', line)
function mdq_diff($a, $b) {
	$x = explode("\n", mdq_norm($a));
	$y = explode("\n", mdq_norm($b));
	// trim common head/tail to keep the LCS table small
	$head = array(); $tail = array();
	while (count($x) && count($y) && $x[0] === $y[0]) { $head[] = array(' ', array_shift($x)); array_shift($y); }
	while (count($x) && count($y) && end($x) === end($y)) { array_unshift($tail, array(' ', array_pop($x))); array_pop($y); }
	$n = count($x); $m = count($y);
	$mid = array();
	if ($n * $m > 4000000) {
		foreach ($x as $l) { $mid[] = array('-', $l); }
		foreach ($y as $l) { $mid[] = array('+', $l); }
	} else {
		$L = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));
		for ($i = $n - 1; $i >= 0; $i--) {
			for ($j = $m - 1; $j >= 0; $j--) {
				$L[$i][$j] = $x[$i] === $y[$j] ? $L[$i+1][$j+1] + 1 : max($L[$i+1][$j], $L[$i][$j+1]);
			}
		}
		$i = 0; $j = 0;
		while ($i < $n && $j < $m) {
			if ($x[$i] === $y[$j]) { $mid[] = array(' ', $x[$i]); $i++; $j++; }
			else if ($L[$i+1][$j] >= $L[$i][$j+1]) { $mid[] = array('-', $x[$i++]); }
			else { $mid[] = array('+', $y[$j++]); }
		}
		while ($i < $n) { $mid[] = array('-', $x[$i++]); }
		while ($j < $m) { $mid[] = array('+', $y[$j++]); }
	}
	return array_merge($head, $mid, $tail);
}

// render a diff as HTML, collapsing unchanged runs to 2 lines of context
function mdq_diffhtml($d) {
	$keep = array();
	foreach ($d as $k => $line) {
		if ($line[0] != ' ') {
			for ($c = max(0, $k - 2); $c <= min(count($d) - 1, $k + 2); $c++) { $keep[$c] = 1; }
		}
	}
	$out = '<pre style="margin:0;overflow:auto;max-height:30em;border:1px solid #ccc;padding:2px;">';
	$gap = false;
	foreach ($d as $k => $line) {
		if (!isset($keep[$k])) { $gap = true; continue; }
		if ($gap) { $out .= '<span style="color:#888">&hellip;</span>' . "\n"; $gap = false; }
		$e = Sanitize::encodeStringForDisplay($line[1]);
		if ($line[0] == '-') { $out .= '<span style="background:#fcc;display:block;">- ' . $e . '</span>'; }
		else if ($line[0] == '+') { $out .= '<span style="background:#cfc;display:block;">+ ' . $e . '</span>'; }
		else { $out .= '<span style="display:block;">  ' . $e . '</span>'; }
	}
	if ($gap) { $out .= '<span style="color:#888">&hellip;</span>'; }
	return $out . '</pre>';
}

function mdq_in($ids) {
	return implode(',', array_map('intval', $ids));
}

$stm = $DBH->query('SELECT uniqueid FROM imas_questionset WHERE uniqueid>0 GROUP BY uniqueid HAVING COUNT(*)>1');
$uids = $stm->fetchAll(PDO::FETCH_COLUMN, 0);

$groups = array();     // uniqueid => rows (retained first)
$identical = array();  // uniqueids whose copies all match
$differ = array();     // uniqueid => list of differing field names
$cols = 'id,uniqueid,deleted,userights,license,' . implode(',', $cmpfields);
$sel = $DBH->prepare("SELECT $cols FROM imas_questionset WHERE uniqueid=? ORDER BY id");
foreach ($uids as $uid) {
	$sel->execute(array($uid));
	$rows = $sel->fetchAll(PDO::FETCH_ASSOC);
	usort($rows, 'mdq_rank');
	$groups[$uid] = $rows;
	$diff = array();
	$nlive = 0;
	foreach ($rows as $r) { if (!$r['deleted']) { $nlive++; } }
	if ($nlive != 1) {   // exactly one undeleted copy: merge to it without comparing
		foreach ($cmpfields as $f) {
			$base = mdq_norm($rows[0][$f]);
			foreach ($rows as $r) {
				if (mdq_norm($r[$f]) !== $base) { $diff[] = $f; break; }
			}
		}
	}
	if (count($diff) == 0) { $identical[] = $uid; } else { $differ[$uid] = $diff; }
}

$mergeGroups = array();  // uniqueid => array(keep id, array of dup ids)
foreach ($identical as $uid) {
	$dups = array();
	for ($i = 1; $i < count($groups[$uid]); $i++) { $dups[] = (int) $groups[$uid][$i]['id']; }
	$mergeGroups[$uid] = array((int) $groups[$uid][0]['id'], $dups);
}
$renames = array();   // uniqueid => ids that get a new uniqueid

if ($apply) {
	foreach ($differ as $uid => $diff) {
		$choice = isset($_POST['choice'][$uid]) ? (string) $_POST['choice'][$uid] : 'skip';
		$ids = array();
		foreach ($groups[$uid] as $r) { $ids[] = (int) $r['id']; }
		sort($ids);
		if (strpos($choice, 'keep_') === 0 && in_array((int) substr($choice, 5), $ids)) {
			$keep = (int) substr($choice, 5);
			$mergeGroups[$uid] = array($keep, array_values(array_diff($ids, array($keep))));
		} else if ($choice === 'new') {
			$renames[$uid] = array_slice($ids, 1);
		}
	}
}

$map = array();   // duplicate id => retained id
foreach ($mergeGroups as $uid => $g) {
	foreach ($g[1] as $d) { $map[$d] = $g[0]; }
}

echo '<h2>Duplicate question uniqueids</h2>';
echo '<p>' . count($uids) . ' uniqueids with more than one copy; ' . count($identical) . ' identical (' . count($map) .
	' duplicate rows removable); ' . count($differ) . ' differ in content and will be skipped.</p>';

if ($apply && (count($map) > 0 || count($renames) > 0)) {
	$now = time();
	$dupids = array_keys($map);
	$log = array();

	// per-group work
	$qsetids_stm = null;
	$DBH->beginTransaction();
	try {
		// give later copies new uniqueids
		$used = array();
		$updu = $DBH->prepare('UPDATE imas_questionset SET uniqueid=?,lastmoddate=? WHERE id=?');
		$nrenamed = 0;
		foreach ($renames as $uid => $ids) {
			foreach ($ids as $id) {
				$updu->execute(array(mdq_newuid($uid, $used), $now, $id));
				$nrenamed++;
			}
		}
		if ($nrenamed > 0) { $log[] = "$nrenamed questions assigned new uniqueids"; }

		foreach ($mergeGroups as $uid => $g) {
			$keep = $g[0];
			$dups = $g[1];
			if (count($dups) == 0) { continue; }
			$dl = mdq_in($dups);

			$DBH->exec("UPDATE imas_questions SET questionsetid=$keep WHERE questionsetid IN ($dl)");
			$DBH->exec("DELETE FROM imas_questionerrorlog WHERE qsetid IN ($dl)");
			$DBH->exec("UPDATE IGNORE imas_a11yreviews SET qsetid=$keep WHERE qsetid IN ($dl)");
			$DBH->exec("DELETE FROM imas_a11yreviews WHERE qsetid IN ($dl)");

			// images: keep the retained copy's if it has any, else adopt the first duplicate's
			$hasimg = $DBH->query("SELECT COUNT(*) FROM imas_qimages WHERE qsetid=$keep")->fetchColumn();
			if ($hasimg == 0) {
				foreach ($dups as $d) {
					$n = $DBH->query("SELECT COUNT(*) FROM imas_qimages WHERE qsetid=$d")->fetchColumn();
					if ($n > 0) {
						$DBH->exec("UPDATE imas_qimages SET qsetid=$keep WHERE qsetid=$d");
						break;
					}
				}
			}
			$DBH->exec("DELETE FROM imas_qimages WHERE qsetid IN ($dl)");

			// library items
			$liveInKeep = array();
			$deadInKeep = array();
			foreach ($DBH->query("SELECT id,libid,deleted FROM imas_library_items WHERE qsetid=$keep") as $li) {
				if ($li['deleted']) { $deadInKeep[$li['libid']][] = $li['id']; } else { $liveInKeep[$li['libid']] = 1; }
			}
			$items = $DBH->query("SELECT id,libid,deleted FROM imas_library_items WHERE qsetid IN ($dl) ORDER BY deleted,id")->fetchAll(PDO::FETCH_ASSOC);
			foreach ($items as $li) {
				$lib = $li['libid'];
				if ($li['deleted'] || isset($liveInKeep[$lib])) {
					$DBH->exec('DELETE FROM imas_library_items WHERE id=' . (int) $li['id']);
				} else {
					if (isset($deadInKeep[$lib])) {   // revive: drop the retained copy's tombstone
						$DBH->exec('DELETE FROM imas_library_items WHERE id IN (' . mdq_in($deadInKeep[$lib]) . ')');
						unset($deadInKeep[$lib]);
					}
					$DBH->exec("UPDATE imas_library_items SET qsetid=$keep,lastmoddate=$now WHERE id=" . (int) $li['id']);
					$liveInKeep[$lib] = 1;
				}
			}
		}

		// replaceby / a11yalt pointing at a removed copy
		$all = count($dupids) > 0 ? mdq_in($dupids) : '0';
		foreach (array('replaceby', 'a11yalt') as $col) {
			$stm = $DBH->query("SELECT id,$col FROM imas_questionset WHERE $col IN ($all)");
			$upd = $DBH->prepare("UPDATE imas_questionset SET $col=?,lastmoddate=? WHERE id=?");
			$n = 0;
			foreach ($stm->fetchAll(PDO::FETCH_NUM) as $r) {
				if (isset($map[$r[1]]) && !isset($map[$r[0]])) { $upd->execute(array($map[$r[1]], $now, $r[0])); $n++; }
			}
			$log[] = "$n $col references repointed";
		}

		// numeric includecodefrom(ID) / includeqtextfrom(ID) references (UID-form refs need no change)
		$cb = function ($m) use ($map) {
			return isset($map[$m[2]]) ? $m[1] . $map[$m[2]] . ')' : $m[0];
		};
		$recode = '/(includecodefrom\()(\d+)\)/';
		$reqtext = '/(includeqtextfrom\()(\d+)\)/';
		$stm = $DBH->query("SELECT id,control,qtext FROM imas_questionset WHERE (control LIKE '%includecodefrom(%' AND control REGEXP 'includecodefrom[(][0-9]+[)]')
			OR (qtext LIKE '%includeqtextfrom(%' AND qtext REGEXP 'includeqtextfrom[(][0-9]+[)]')");
		$upd = $DBH->prepare('UPDATE imas_questionset SET control=?,qtext=?,lastmoddate=? WHERE id=?');
		$n = 0;
		while ($r = $stm->fetch(PDO::FETCH_ASSOC)) {
			if (isset($map[$r['id']])) { continue; }   // about to be deleted anyway
			$c = preg_replace_callback($recode, $cb, $r['control']);
			$q = preg_replace_callback($reqtext, $cb, $r['qtext']);
			if ($c !== $r['control'] || $q !== $r['qtext']) {
				$upd->execute(array($c, $q, $now, $r['id']));
				$n++;
			}
		}
		$log[] = "$n questions had include references rewritten";

		// drill assessments keep a comma-separated list of question ids
		$stm = $DBH->query("SELECT id,itemids FROM imas_drillassess WHERE itemids<>''");
		$upd = $DBH->prepare('UPDATE imas_drillassess SET itemids=? WHERE id=?');
		$n = 0;
		foreach ($stm->fetchAll(PDO::FETCH_NUM) as $r) {
			$parts = explode(',', $r[1]);
			$changed = false;
			foreach ($parts as $k => $p) {
				if (isset($map[trim($p)])) { $parts[$k] = $map[trim($p)]; $changed = true; }
			}
			if ($changed) { $upd->execute(array(implode(',', $parts), $r[0])); $n++; }
		}
		$log[] = "$n drill assessments updated";

		// finally remove the duplicates
		foreach (array_chunk($dupids, 500) as $chunk) {
			$DBH->exec('DELETE FROM imas_questionset WHERE id IN (' . mdq_in($chunk) . ')');
		}
		$log[] = count($dupids) . ' duplicate questions deleted';
		$DBH->commit();
	} catch (Throwable $e) {
		$DBH->rollBack();
		echo '<p><b>Failed, rolled back:</b> ' . Sanitize::encodeStringForDisplay($e->getMessage()) . '</p>';
		require_once "../footer.php";
		exit;
	}
	echo '<p><b>Done.</b></p><ul>';
	foreach ($log as $l) { echo '<li>' . Sanitize::encodeStringForDisplay($l) . '</li>'; }
	echo '</ul>';
	require_once "../footer.php";
	exit;
}

echo '<form method="post"><input type="hidden" name="apply" value="yes"/>';

if (count($differ) > 0) {
	echo '<h3>Groups that differ</h3><table class="gb"><thead><tr><th>uniqueid</th><th>ids (deleted marked *)</th><th>differing fields</th><th>Action</th></tr></thead><tbody>';
	foreach ($differ as $uid => $diff) {
		$rows = $groups[$uid];
		usort($rows, function ($a, $b) { return $a['id'] - $b['id']; });
		$idlabels = array();
		foreach ($rows as $r) { $idlabels[] = (int) $r['id'] . ($r['deleted'] ? '*' : ''); }
		echo '<tr><td>' . Sanitize::onlyInt($uid) . '</td><td>' . implode(', ', $idlabels) . '</td><td>' .
			Sanitize::encodeStringForDisplay(implode(', ', $diff));
		// expandable diff of each differing field: every other copy compared to the lowest id
		echo '<details><summary>Show differences</summary>';
		foreach ($diff as $f) {
			echo '<p><b>' . Sanitize::encodeStringForDisplay($f) . '</b></p>';
			for ($i = 1; $i < count($rows); $i++) {
				if (mdq_norm($rows[$i][$f]) === mdq_norm($rows[0][$f])) { continue; }
				echo '<div>id ' . (int) $rows[0]['id'] . ' (-) vs id ' . (int) $rows[$i]['id'] . ' (+)</div>';
				echo mdq_diffhtml(mdq_diff($rows[0][$f], $rows[$i][$f]));
			}
		}
		echo '</details></td><td>';
		echo '<select name="choice[' . Sanitize::onlyInt($uid) . ']"><option value="skip">Skip</option>';
		foreach ($rows as $r) {
			echo '<option value="keep_' . (int) $r['id'] . '">Keep id ' . (int) $r['id'] . '</option>';
		}
		echo '<option value="new">Assign new uniqueid to later copies</option></select></td></tr>';
	}
	echo '</tbody></table>';
}

if (count($map) > 0) {
	echo '<h3>Identical groups to merge</h3><p>First 50 shown (retained id &larr; removed ids):</p><ul>';
	$shown = 0;
	foreach ($mergeGroups as $uid => $g) {
		if ($shown++ >= 50) { break; }
		echo '<li>' . $g[0] . ' &larr; ' . implode(', ', $g[1]) . '</li>';
	}
	echo '</ul>';
	echo '<p><b>Back up first.</b> This deletes rows and repoints references, in one transaction.</p>';
	echo '<button type="submit">Merge ' . count($map) . ' duplicate questions and apply selections</button>';
} else if (count($differ) > 0) {
	echo '<p><b>Back up first.</b></p><button type="submit">Apply selections</button>';
}
echo '</form>';
require_once "../footer.php";
