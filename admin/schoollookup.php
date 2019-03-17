<?php

require('../init.php');
if ($myrights < 20) {
	exit;
}
if (!isset($_POST['lookup'])) {
	require('../header.php');
	?>
	<script>
	function go() {
		$.ajax({
			type: "POST",
			url: imasroot+'/admin/schoollookup.php',
			data: {lookup: document.getElementById("lookup").value},
			dataType: 'json'
		})
		.done(function(data) {
			var out = '';
			var i;
			for (i in data) {
				out += '<li>'+data[i].name+'</li>';
			}
			$("#out").html(out);
		});
			
	}
	</script>
	<form method="post">
	<input id=lookup><button type=button onclick="go()">Lookup</button>
	</form>
	<ul id="out"></ul>
	<?php
	require('../footer.php');
	exit;
}

header('Content-Type: application/json');

$keywords = preg_split('/\s+/', $_POST['lookup']);


$qarr = array();
$searchstr = array();
foreach ($keywords as $kw) {
	$lower = strtolower($kw);
	if ($lower=='of' || $lower=='the' || $lower=='in') {
		continue;
	}
	if (is_numeric($kw) && strlen($kw)==5) {
		$searchstr[] = 'zip=?';
		$qarr[] = intval($kw);
	} else if (strlen($kw)==2 && ctype_upper($kw)) {
		$searchstr[] = 'state=?';
		$qarr[] = $kw;
	} else {
		$searchstr[] = 'name REGEXP ?';
		$qarr[] = '[[:<:]]'.$kw.'[[:>:]]';
	}
}
if (count($qarr)==0) {
	echo '[]';
	exit;
}
$ph = implode(' AND ', $searchstr);
$stm = $DBH->prepare("SELECT id,name FROM imas_schoolref WHERE $ph LIMIT 30");
$stm->execute($qarr);
$out = $stm->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($out);
