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
// Groups whose copies differ in content are listed and left alone.
// Not repointed: imas_questionset.ancestors (history only).
require_once "../init.php";
if ($myrights < 100) { exit; }
set_time_limit(0);

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
	foreach ($cmpfields as $f) {
		foreach ($rows as $r) {
			if ($r[$f] !== $rows[0][$f]) { $diff[] = $f; break; }
		}
	}
	if (count($diff) == 0) { $identical[] = $uid; } else { $differ[$uid] = $diff; }
}

$map = array();   // duplicate id => retained id
foreach ($identical as $uid) {
	$keep = $groups[$uid][0]['id'];
	for ($i = 1; $i < count($groups[$uid]); $i++) {
		$map[$groups[$uid][$i]['id']] = $keep;
	}
}

echo '<h2>Duplicate question uniqueids</h2>';
echo '<p>' . count($uids) . ' uniqueids with more than one copy; ' . count($identical) . ' identical (' . count($map) .
	' duplicate rows removable); ' . count($differ) . ' differ in content and will be skipped.</p>';

if ($apply && count($map) > 0) {
	$now = time();
	$dupids = array_keys($map);
	$log = array();

	// per-group work
	$qsetids_stm = null;
	$DBH->beginTransaction();
	try {
		foreach ($identical as $uid) {
			$rows = $groups[$uid];
			$keep = (int) $rows[0]['id'];
			$dups = array();
			for ($i = 1; $i < count($rows); $i++) { $dups[] = (int) $rows[$i]['id']; }
			$dl = mdq_in($dups);

			$DBH->exec("UPDATE imas_questions SET questionsetid=$keep WHERE questionsetid IN ($dl)");
			$DBH->exec("UPDATE imas_firstscores SET qsetid=$keep WHERE qsetid IN ($dl)");
			$DBH->exec("UPDATE IGNORE imas_questionerrorlog SET qsetid=$keep WHERE qsetid IN ($dl)");
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
		$all = mdq_in($dupids);
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
		exit;
	}
	echo '<p><b>Done.</b></p><ul>';
	foreach ($log as $l) { echo '<li>' . Sanitize::encodeStringForDisplay($l) . '</li>'; }
	echo '</ul>';
	exit;
}

if (count($differ) > 0) {
	echo '<h3>Groups that differ (not merged)</h3><table class="gb"><tr><th>uniqueid</th><th>ids (retain first)</th><th>differing fields</th></tr>';
	foreach ($differ as $uid => $diff) {
		$ids = array();
		foreach ($groups[$uid] as $r) { $ids[] = (int) $r['id']; }
		echo '<tr><td>' . Sanitize::onlyInt($uid) . '</td><td>' . implode(', ', $ids) . '</td><td>' .
			Sanitize::encodeStringForDisplay(implode(', ', $diff)) . '</td></tr>';
	}
	echo '</table>';
}

if (count($map) > 0) {
	echo '<h3>Identical groups to merge</h3><p>First 50 shown (retained id &larr; removed ids):</p><ul>';
	$shown = 0;
	foreach ($identical as $uid) {
		if ($shown++ >= 50) { break; }
		$ids = array();
		foreach ($groups[$uid] as $r) { $ids[] = (int) $r['id']; }
		$keep = array_shift($ids);
		echo '<li>' . $keep . ' &larr; ' . implode(', ', $ids) . '</li>';
	}
	echo '</ul>';
	echo '<form method="post"><input type="hidden" name="apply" value="yes"/>';
	echo '<p><b>Back up first.</b> This deletes rows and repoints references, in one transaction.</p>';
	echo '<button type="submit">Merge ' . count($map) . ' duplicate questions</button></form>';
}
