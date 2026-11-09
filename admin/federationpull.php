<?php
//IMathAS:  Federation pull, protocol version 2
//See federation-plan.md
//
//Fetches changes from a federation peer and stages them for review.
//Does not change any question or library content.

require_once "../init.php";
require_once "../includes/federationpull.php";

if ($myrights < 100) {
	echo "Not authorized";
	exit;
}

$peerid = intval($_GET['peer'] ?? 0);
$stm = $DBH->prepare('SELECT id,peername,peerdescription,secret,url,lastpull FROM imas_federation_peers WHERE id=:id');
$stm->execute(array(':id' => $peerid));
$peer = $stm->fetch(PDO::FETCH_ASSOC);
if ($peer === false) {
	echo 'Invalid peer ID';
	exit;
}

$stagenames = array(0 => _('Libraries'), 1 => _('Questions'), 2 => _('Library assignments'));

function fed_pull_json($pull, $error = null) {
	global $stagenames;
	$stats = json_decode($pull['stats'], true);
	$counts = array();
	foreach (($stats['counts'] ?? array()) as $stage => $c) {
		$counts[$stagenames[$stage]] = $c;
	}
	header('Content-Type: application/json');
	echo json_encode(array(
		'error' => $error,
		'pullid' => (int) $pull['id'],
		'done' => $pull['status'] == FED_PULL_DONE,
		'abandoned' => $pull['status'] == FED_PULL_ABANDONED,
		'stage' => (int) $pull['stage'],
		'stagename' => $stagenames[(int) $pull['stage']],
		'received' => $stats['rows'] ?? array(),
		'counts' => $counts,
		'withheld' => count($stats['withheld'] ?? array()),
		'unresolved' => (int) ($stats['unresolved'] ?? 0),
		'truncated' => (int) ($stats['truncated'] ?? 0),
	));
	exit;
}

// AJAX actions (POST only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
	if ($_POST['action'] === 'start') {
		list($resp, $err) = fed_request($peer, 'ping');
		if ($err !== null) {
			header('Content-Type: application/json');
			echo json_encode(array('error' => $err));
			exit;
		}
		fed_pull_json(fed_pull_start($peerid));
	}
	$pull = fed_get_active_pull($peerid);
	if ($pull === false) {
		header('Content-Type: application/json');
		echo json_encode(array('error' => _('No pull in progress')));
		exit;
	}
	if ($_POST['action'] === 'step') {
		list($pull, $err) = fed_pull_step($peer, $pull);
		fed_pull_json($pull, $err);
	} else if ($_POST['action'] === 'abandon') {
		fed_pull_abandon($pull['id']);
		$pull['status'] = FED_PULL_ABANDONED;
		fed_pull_json($pull);
	}
	exit;
}

$active = fed_get_active_pull($peerid);
$stm = $DBH->prepare('SELECT COUNT(*) FROM imas_federation_staged WHERE peerid=? AND status=?');
$stm->execute(array($peerid, FED_STATUS_PENDING));
$pendingcnt = $stm->fetchColumn();

$pagetitle = _('Pull from Federation Peer');
$curBreadcrumb = '<a href="../index.php">Home</a> &gt; <a href="admin2.php">Admin</a> &gt; ' . _('Federation pull');
require_once "../header.php";

echo '<div class="breadcrumb">' . $curBreadcrumb . '</div>';
echo '<h1>' . sprintf(_('Pull from %s'), Sanitize::encodeStringForDisplay($peer['peername'])) . '</h1>';
echo '<p>' . Sanitize::encodeStringForDisplay($peer['peerdescription']) . '</p>';
echo '<p>' . _('A pull downloads changed libraries and questions from the peer and puts them in a review queue. Nothing in your libraries is changed until you approve it.') . '</p>';
printf('<p><a href="federationreview.php?peer=%d">%s</a> (%d %s)</p>', $peerid,
	_('Review staged changes'), $pendingcnt, _('pending'));

echo '<div id="pullcontrols">';
if ($active !== false) {
	echo '<p>' . sprintf(_('A pull started %s has not finished.'), tzdate('n/j/y g:i a', $active['starttime'])) . '</p>';
	echo '<button type="button" id="startbtn">' . _('Continue pull') . '</button> ';
	echo '<button type="button" id="abandonbtn" class="secondarybtn">' . _('Abandon pull') . '</button>';
} else {
	echo '<button type="button" id="startbtn">' . _('Start new pull') . '</button>';
}
echo '</div>';
echo '<div id="pullstatus" aria-live="polite"></div>';
?>
<script>
(function () {
	var url = 'federationpull.php?peer=<?php echo $peerid; ?>';
	var statusEl = document.getElementById('pullstatus');
	var running = false;

	function post(action) {
		var headers = {'Content-Type': 'application/x-www-form-urlencoded'};
		// CSRF protector token, when this install has it turned on
		if (window.CSRFP && typeof CSRFP._getAuthKey === 'function') {
			headers[CSRFP.CSRFP_TOKEN] = CSRFP._getAuthKey();
		}
		return fetch(url, {
			method: 'POST',
			headers: headers,
			body: 'action=' + action,
			credentials: 'same-origin'
		}).then(function (r) { return r.json(); });
	}
	function esc(s) {
		var d = document.createElement('div');
		d.textContent = s;
		return d.innerHTML;
	}
	function show(d) {
		var h = '<p>' + esc(d.done ? <?php echo json_encode(_('Pull complete.')); ?> : <?php echo json_encode(_('Working on: ')); ?> + d.stagename) + '</p>';
		h += '<ul>';
		Object.keys(d.counts).forEach(function (k) {
			var c = d.counts[k];
			var parts = [];
			Object.keys(c).forEach(function (t) { if (c[t] > 0) { parts.push(c[t] + ' ' + t); } });
			h += '<li>' + esc(k) + ': ' + esc(parts.join(', ') || <?php echo json_encode(_('nothing new')); ?>) + '</li>';
		});
		h += '</ul>';
		if (d.withheld > 0) { h += '<p>' + esc(d.withheld + <?php echo json_encode(_(' referenced questions could not be shared by the peer.')); ?>) + '</p>'; }
		if (d.unresolved > 0) { h += '<p>' + esc(d.unresolved + <?php echo json_encode(_(' questions have unresolved references to other questions.')); ?>) + '</p>'; }
		if (d.truncated > 0) { h += '<p>' + esc(<?php echo json_encode(_('Some referenced questions were left out because there were too many.')); ?>) + '</p>'; }
		statusEl.innerHTML = h;
	}
	function step() {
		post('step').then(function (d) {
			show(d);
			if (d.error) {
				statusEl.innerHTML += '<p class="noticetext">' + esc(d.error) + '</p>';
				running = false;
				document.getElementById('startbtn').disabled = false;
			} else if (d.done) {
				statusEl.innerHTML += '<p><a href="federationreview.php?peer=<?php echo $peerid; ?>">' + esc(<?php echo json_encode(_('Review staged changes')); ?>) + '</a></p>';
				running = false;
			} else {
				step();
			}
		}).catch(function (e) {
			statusEl.innerHTML = '<p class="noticetext">' + esc(String(e)) + '</p>';
			running = false;
			document.getElementById('startbtn').disabled = false;
		});
	}
	document.getElementById('startbtn').addEventListener('click', function () {
		if (running) { return; }
		running = true;
		this.disabled = true;
		statusEl.textContent = <?php echo json_encode(_('Contacting peer...')); ?>;
		post('start').then(function (d) {
			if (d.error) {
				statusEl.innerHTML = '<p class="noticetext">' + esc(d.error) + '</p>';
				running = false;
				document.getElementById('startbtn').disabled = false;
			} else {
				step();
			}
		});
	});
	var ab = document.getElementById('abandonbtn');
	if (ab) {
		ab.addEventListener('click', function () {
			post('abandon').then(function () { window.location.reload(); });
		});
	}
})();
</script>
<?php
require_once "../footer.php";
