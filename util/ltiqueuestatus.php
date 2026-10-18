<?php
//IMathAS: admin util to check LTI queue status for an assessment/user

require_once "../init.php";

if ($myrights<100) {
	echo _("You are not authorized to view this page");
	exit;
}

require_once "../header.php";
require_once "../includes/ltioutcomes.php";

/**
 * Run a curl request, returning [http_code, curl_error, headers, body]
 */
function ltiqueuestatus_curl($url, $postbody, $headers) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postbody);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($ch, CURLOPT_TIMEOUT, 20);
	curl_setopt($ch, CURLOPT_USERAGENT, Sanitize::simpleASCII($GLOBALS['installname'] ?? 'IMathAS'));
	if (!empty($GLOBALS['CFG']['LTI']['skipsslverify'])) {
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	}
	$resp = curl_exec($ch);
	$info = curl_getinfo($ch);
	$err = curl_error($ch);
	if ($resp === false) {
		return [$info['http_code'], $err, '', ''];
	}
	$hs = $info['header_size'];
	return [$info['http_code'], $err, substr($resp, 0, $hs), substr($resp, $hs)];
}

function ltiqueuestatus_showresp($label, $r) {
	echo '<p><b>' . Sanitize::encodeStringForDisplay($label) . '</b>: ';
	echo sprintf(_('HTTP code %d'), intval($r[0]));
	if ($r[1] !== '') {
		echo ', ' . sprintf(_('curl error: %s'), Sanitize::encodeStringForDisplay($r[1]));
	}
	echo '</p><pre>' . Sanitize::encodeStringForDisplay(trim($r[2] . "\n" . $r[3])) . '</pre>';
}

echo '<h1>' . _('LTI Queue Status') . '</h1>';
echo '<form method="post">';
echo '<label for="aid">' . _('Assessment ID:') . '</label> ';
echo '<input type="text" size="10" name="aid" id="aid" value="' . Sanitize::encodeStringForDisplay($_POST['aid'] ?? '') . '" /> ';
echo '<label for="uid">' . _('User ID:') . '</label> ';
echo '<input type="text" size="10" name="uid" id="uid" value="' . Sanitize::encodeStringForDisplay($_POST['uid'] ?? '') . '" /> ';
echo '<button type="submit">' . _('Look up') . '</button></form>';

if (isset($_POST['aid']) && isset($_POST['uid'])) {
	$aid = intval($_POST['aid']);
	$uid = intval($_POST['uid']);
	$hash = $aid . '-' . $uid;
	$hashDisp = Sanitize::encodeStringForDisplay($hash);

	// 1) lti_sourcedid from assessment record
	$stm = $DBH->prepare('SELECT lti_sourcedid FROM imas_assessment_records WHERE assessmentid=? AND userid=?');
	$stm->execute([$aid, $uid]);
	$sourcedid = $stm->fetchColumn(0);
	if ($sourcedid === false) {
		echo '<p>' . sprintf(_('No assessment record found for assessment %d, user %d.'), $aid, $uid) . '</p>';
	} else {
		echo '<p>' . sprintf(_('lti_sourcedid: %s'), Sanitize::encodeStringForDisplay($sourcedid)) . '</p>';
	}

	// 2) ltiqueue record
	$stm = $DBH->prepare('SELECT failures FROM imas_ltiqueue WHERE hash=?');
	$stm->execute([$hash]);
	$failures = $stm->fetchColumn(0);
	if ($failures === false) {
		echo '<p>' . sprintf(_('No imas_ltiqueue record exists with hash %s.'), $hashDisp) . '</p>';
	} else {
		$failures = intval($failures);
		echo '<p>' . sprintf(_('imas_ltiqueue record exists with hash %s. Failures: %d'), $hashDisp, $failures) . '</p>';

		// 3) if given up, show log entries
		if ($failures == 7) {
			$stm = $DBH->prepare('SELECT time,log FROM imas_log WHERE log LIKE ? ORDER BY time DESC LIMIT 10');
			$stm->execute(['%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $hash) . '%']);
			$found = false;
			while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
				$found = true;
				echo '<p>' . sprintf(_('Log entry from %s:'), date('n/j/y g:i a', $row['time'])) . '</p>';
				echo '<pre>' . Sanitize::encodeStringForDisplay($row['log']) . '</pre>';
			}
			if (!$found) {
				echo '<p>' . _('No imas_log entry found containing the hash.') . '</p>';
			}
		}

		// resend button
		if ($failures > 0 && !isset($_POST['resend'])) {
			echo '<form method="post">';
			echo '<input type="hidden" name="aid" value="' . $aid . '" />';
			echo '<input type="hidden" name="uid" value="' . $uid . '" />';
			echo '<input type="hidden" name="resend" value="1" />';
			echo '<button type="submit">' . _('Attempt immediate resend') . '</button></form>';
		}

		// 4) check token for LTI 1.3 failures
		if ($failures > 0 && $sourcedid !== false && substr($sourcedid, 0, 6) == 'LTI1.3') {
			$parts = explode(':|:', $sourcedid);
			$platformid = isset($parts[3]) ? intval($parts[3]) : 0;
			echo '<p>' . sprintf(_('LTI 1.3 platform id: %d'), $platformid) . '</p>';
			$stm = $DBH->prepare('SELECT token,scopes,expires FROM imas_lti_tokens WHERE platformid=?');
			$stm->execute([$platformid]);
			$found = false;
			while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
				$found = true;
				$isfailed = (strpos($row['token'], 'failed') !== false);
				echo '<p>' . sprintf(_('Token record (scopes %s, expires %s):'),
					Sanitize::encodeStringForDisplay($row['scopes']), date('n/j/y g:i a', $row['expires'])) . ' ';
				if ($isfailed) {
					echo '<b>' . _('token contains "failed"') . '</b> (' . Sanitize::encodeStringForDisplay($row['token']) . ')';
				} else {
					echo _('token does not contain "failed"');
				}
				echo '</p>';
			}
			if (!$found) {
				echo '<p>' . _('No imas_lti_tokens record found for this platform.') . '</p>';
			}
		}
		// resend
		if ($failures > 0 && isset($_POST['resend'])) {
			echo '<h2>' . _('Resend attempt') . '</h2>';
			$stm = $DBH->prepare('SELECT * FROM imas_ltiqueue WHERE hash=?');
			$stm->execute([$hash]);
			$q = $stm->fetch(PDO::FETCH_ASSOC);
			$qsource = $q['sourcedid'];
			$success = false;
			if (substr($qsource, 0, 6) == 'LTI1.3') {
				$p = explode(':|:', $qsource);
				if (count($p) < 4 || !is_numeric($p[3])) {
					echo '<p>' . _('Invalid LTI 1.3 sourcedid in queue record.') . '</p>';
				} else {
					list($ltiver, $ltiuserid, $score_url, $platformid) = $p;
					$platformid = intval($platformid);
					$updater = new LTI_Grade_Update($DBH);
					$pinfo = $updater->get_platform_info($platformid);
					if (empty($pinfo)) {
						echo '<p>' . sprintf(_('Platform %d not found.'), $platformid) . '</p>';
					} else {
						// use an existing non-failed, non-expired token if there is one;
						// otherwise request a new one (even if a previous request failed)
						$token = false;
						if ($updater->have_token($platformid) && $updater->token_valid($platformid)) {
							$token = $updater->get_access_token($platformid);
							if ($token !== false) {
								echo '<p>' . _('Using existing valid token from database.') . '</p>';
							}
						}
						if ($token === false) {
							$tokenpost = $updater->get_token_request_post($platformid, $pinfo['client_id'], $pinfo['auth_token_url'], $pinfo['auth_server']);
							$r = ltiqueuestatus_curl($pinfo['auth_token_url'], $tokenpost, []);
							ltiqueuestatus_showresp(sprintf(_('Token request to %s'), $pinfo['auth_token_url']), $r);
							$tokendata = json_decode($r[3], true);
							if (!empty($tokendata['access_token']) && !empty($tokendata['expires_in'])) {
								$updater->store_access_token($platformid, $tokendata);
								$token = $tokendata['access_token'];
								echo '<p>' . _('Token obtained and stored.') . '</p>';
							}
						}
						if ($token !== false) {
							$res = $updater->send_update($token, $score_url,
								floatval($q['grade']), intval($q['ptsposs']), $ltiuserid, $hash,
								'Submitted', 'FullyGraded', $q['isstu'], intval($q['addedon']), '', true);
							ltiqueuestatus_showresp(sprintf(_('Score update to %s'), $res['url']),
								[$res['http_code'], $res['error'], implode("\n", $res['headers']), $res['body']]);
							$success = $res['success'];
						} else {
							echo '<p>' . _('Token request failed; grade not sent.') . '</p>';
						}
					}
				}
			} else {
				$grade = $q['ptsposs'] > 0 ? $q['grade'] / $q['ptsposs'] : 0;
				// checkResponse=true waits for and returns array(ok, response text)
				$value = updateLTI1p1grade('update', $qsource, $aid, $uid, $grade, true, true, $q['isstu'] ? intval($q['addedon']) : null);
				if (!is_array($value)) {
					echo '<p>' . _('Unable to send: invalid sourcedid or unable to look up secret.') . '</p>';
				} else {
					echo '<p><b>' . _('LTI 1.1 update response') . '</b>:</p>';
					echo '<pre>' . Sanitize::encodeStringForDisplay($value[1] ?? '') . '</pre>';
					$success = (!empty($value[0]) && strpos($value[1] ?? '', 'success') !== false);
				}
			}
			if ($success) {
				$stm = $DBH->prepare('DELETE FROM imas_ltiqueue WHERE hash=?');
				$stm->execute([$hash]);
				echo '<p><b>' . _('Update succeeded; queue record removed.') . '</b></p>';
			} else {
				echo '<p><b>' . _('Update did not succeed; queue record unchanged.') . '</b></p>';
			}
		}
	}
}
require_once "../footer.php";
