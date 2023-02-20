<?php

require("../init_without_validate.php");
require("../header.php");
echo '<h2>Question License</h2>';

if (empty($_GET['id'])) {
	echo "No IDs specified";
	exit;
}

function getquestionlicense($row) {
	global $CFG, $sendfrom;
	$license = 'This question was written by '.Sanitize::encodeStringForDisplay($row['author']);
	if ($row['ancestorauthors']!='') {
		$license .= ', derived from work by '.Sanitize::encodeStringForDisplay($row['ancestorauthors']);
	}
	if ($row['license']==0) {
		$license .= '. This work is copyrighted, or contains copyright material. ';
	} else if ($row['license']==1) {
		$license .= '. This work is licensed under the <a href="http://www.imathas.com/communitylicense.html">IMathAS Community License (GPL + CC-BY)</a>.<br/>';
		$license .= 'The code that generated this question can be obtained by instructors by ';
		if (isset($CFG['GEN']['meanstogetcode'])) {
			$license .= $CFG['GEN']['meanstogetcode'];
		} else {
			global $sendfrom;
			$license .= 'emailing '.Sanitize::emailAddress($sendfrom);
		}
		$license .= '. ';
	} else if ($row['license']==2) {
		$license .= '. This work has been placed in the <a href="https://creativecommons.org/publicdomain/zero/1.0/">Public Domain</a>. ';
	} else if ($row['license']==3) {
		$license .= '. This work, both code and generated output, is licensed under the <a href="http://creativecommons.org/licenses/by-nc-sa/4.0/">Creative Commons Attribution-NonCommercial-ShareAlike</a> license. ';
	} else if ($row['license']==4) {
		$license .= '. This work, both code and generated output, is licensed under the <a href="http://creativecommons.org/licenses/by-sa/4.0/">Creative Commons Attribution-ShareAlike</a> license. ';
	}
	if ($row['otherattribution']!='') {
		$license .= '<br/>Other Attribution: '.Sanitize::encodeStringForDisplay($row['otherattribution']);
	}
	if (strpos($row['control'], 'geogebra')!==false || strpos($row['qtext'], 'geogebra.org')!==false) {
		$content = 'content';
        $ggbuser = '';
        if (preg_match('/addGeogebra\(["\'](\w+)+?["\']/', $row['control'], $matches)) {
            $url = "https://api.geogebra.org/v1.0/worksheets/" . $matches[1] . "?embed=creator";
            $ctx = stream_context_create(array('http'=>
                array(
                'timeout' => 1
                )
            ));
            $data = @file_get_contents($url, false, $ctx);
            if ($data !== false) {
                $data = json_decode($data, true);
                if ($data !== null) {
                    $content = '<a href="https://geogebra.org/m/' . $matches[1] . '">content</a>';
                    $ggbuser .= ' by <a href="https://geogebra.org'. Sanitize::encodeStringForDisplay($data['creator']['profile']) . '">';
                    $ggbuser .= Sanitize::encodeStringForDisplay($data['creator']['displayname']) . '</a>';
                }
            }
        }
        $license .= '<br/>Includes '.$content.' created with Geogebra (<a href="https://geogebra.org">geogebra.org</a>)' . $ggbuser . '.';
	}
	return $license;
}

$ids = array_map('Sanitize::onlyInt', explode('-',$_GET['id']));

$idlist_query_placeholders = Sanitize::generateQueryPlaceholders($ids);

$stm = $DBH->prepare("SELECT id,uniqueid,author,ancestorauthors,license,otherattribution,control,qtext FROM imas_questionset WHERE id IN ($idlist_query_placeholders)");
$stm->execute($ids);
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
	echo "<p>Question ID ".Sanitize::onlyInt($row['id']).' (Universal ID '.Sanitize::onlyInt($row['uniqueid']).')</p>';
	echo '<p style="margin-left:20px">';
	echo getquestionlicense($row);
	echo '</p>';
}
require('../footer.php');
?>
