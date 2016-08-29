<?php

require("../validate.php");
require("../includes/htmlawed.php");
$cid = $_GET['cid'];
$aid = $_GET['aid'];

if (!isset($teacherid)) {
	echo "You are not authorized for this action";
	exit;
}

$query = "SELECT intro,itemorder FROM imas_assessments WHERE id='$aid' AND courseid='$cid'";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
if (mysql_num_rows($result)==0) {echo "Invalid id"; exit;}
list($current_intro_json,$qitemorder) = mysql_fetch_row($result);

if (($intro=json_decode($current_intro_json,true))!==null) { //is json intro
	echo "Already converted";
	exit;
} else {
	$intro = $current_intro_json;
	$introjson = array();
	$isembed = false;
	if (strpos($intro,'[QUESTION')!==false) {
		$isembed = true;
		$intro = preg_replace('/<p[^>]*>((<span|<strong|<em)[^>]*>)*?(&nbsp;|\s)*\[QUESTION\s+(\d+)\s*\]/','[QUESTION $4]',$intro);
		$intro = preg_replace('/\[QUESTION\s+(\d+)\s*\](&nbsp;|\s)*(<br\s*\/>\s*)?((<\/span|<\/strong|<\/em)[^>]*>)*?<\/p>/','[QUESTION $1]',$intro);
		//no reason for this $intro = preg_replace('/\[QUESTION\s+(\d+)\s*\]/','</div>[QUESTION $1]<div class="intro">',$intro);
		if (strpos($intro,'[PAGE')!==false) {
			$intro = preg_replace('/<p[^>]*>((<span|<strong|<em)[^>]*>)?\[PAGE\s*([^\]]*)\]((<\/span|<\/strong|<\/em)[^>]*>)?<\/p>/','[PAGE $3]',$intro);
			//no reason for this $intro = preg_replace('/\[PAGE\s*([^\]]*)\]/','</div>[PAGE $1]<div class="intro">',$intro);
			$intropages = preg_split('/\[PAGE\s*([^\]]*)\]/',$intro,-1,PREG_SPLIT_DELIM_CAPTURE); //main pagetitle cont 1 pagetitle
			$mainintro = $intropages[0];
			$introjson[] = $mainintro;
			$lastqn = -1;
			for ($i=1;$i<count($intropages);$i+=2) {
				$qpages = preg_split('/\[QUESTION\s*(\d+)\]/',$intropages[$i+1],-1,PREG_SPLIT_DELIM_CAPTURE);
				for ($j=0;$j<count($qpages);$j+=2) {
					$qpages[$j] = myhtmLawed($qpages[$j]);
					$qpages[$j] = preg_replace('/<span[^>]*>(&nbsp;|\s)*<\/span>/','',$qpages[$j]);
					$qpages[$j] = preg_replace('/<div[^>]*>\s*(&nbsp;|<p[^>]*>(\s|&nbsp;)*<\/p>|<\/p>|\s*)\s*<\/div>/','',$qpages[$j]);
					if (trim($qpages[$j])!='') {
						if (isset($qpages[$j+1])) {
							$qn = $qpages[$j+1]-1;
							$lastqn = $qn;
						} else {
							$qn = $lastqn+1;
						}
						$introjson[] = array(
							'displayBefore'=>$qn,
							'displayUntil'=>$qn,
							'text'=>str_replace(array("\n","\r"),array(' ',' '),myhtmLawed($qpages[$j])),
							'ispage'=>($j==0)?1:0,
							'pagetitle'=>($j==0)?strip_tags(str_replace(array("\n","\r","]"),array(' ',' ','&#93;'),$intropages[$i])):''
							);

					} else if (isset($qpages[$j+1])) {
						$lastqn = $qpages[$j+1]-1;
					}
				}
			}
		} else {
			$mainintro = '';
			$introjson[] = $mainintro;
			$qpages = preg_split('/\[QUESTION\s*(\d+)\]/',$intro,-1,PREG_SPLIT_DELIM_CAPTURE);
			for ($j=0;$j<count($qpages);$j+=2) {
				$qpages[$j] = myhtmLawed($qpages[$j]);
				$qpages[$j] = preg_replace('/<span[^>]*>(&nbsp;|\s)*<\/span>/','',$qpages[$j]);
				$qpages[$j] = preg_replace('/<div[^>]*>\s*(&nbsp;|<p[^>]*>(\s|&nbsp;)*<\/p>|<\/p>|\s*)\s*<\/div>/','',$qpages[$j]);
				if (trim($qpages[$j])!='') {
					if (isset($qpages[$j+1])) {
						$qn = $qpages[$j+1]-1;
					} else {
						$qn = $qpages[$j-1];
					}
					$introjson[] = array(
						'displayBefore'=>$qn,
						'displayUntil'=>$qn,
						'text'=>str_replace(array("\n","\r"),array(' ',' '),myhtmLawed($qpages[$j])),
						'ispage'=>0,
						'pagetitle'=>''
						);
				}
			}
		}
	} else if (strpos($intro,'[Q')!==false) {
		$intro = preg_replace('/((<span|<strong|<em)[^>]*>)?\[Q\s+(\d+(\-(\d+))?)\s*\]((<\/span|<\/strong|<\/em)[^>]*>)?/','[Q $3]',$intro);
		if(preg_match_all('/\<p[^>]*>\s*\[Q\s+(\d+)(\-(\d+))?\s*\]\s*<\/p>/',$intro,$introdividers,PREG_SET_ORDER)) {
			$intropieces = preg_split('/\<p[^>]*>\s*\[Q\s+(\d+)(\-(\d+))?\s*\]\s*<\/p>/',$intro);
			foreach ($introdividers as $k=>$v) {
				if (count($v)==4) {
					$introdividers[$k][2] = $v[3];
				} else if (count($v)==2) {
					$introdividers[$k][2] = $v[1];
				}
			}
			$mainintro = array_shift($intropieces);
			$introjson[] = $mainintro;
			foreach ($introdividers as $k=>$v) {
				$introjson[] = array(
					'displayBefore'=>$v[1]-1,
					'displayUntil'=>$v[2]-1,
					'text'=>str_replace(array("\n","\r"),array(' ',' '),myhtmLawed($intropieces[$k])),
					'ispage'=>0,
					'pagetitle'=>''
					);
			}
		}
	}
	if (isset($_GET['confirm'])) {
		$query = "UPDATE imas_assessments SET intro='".addslashes(json_encode($introjson))."' WHERE id='$aid'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/addassessment.php?id=$aid&cid=$cid");
	} else {
		$qcnt = substr_count($qitemorder, ',')+1;
		$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\">$coursename</a> &gt; ";
		$curBreadcrumb .= "<a href=\"addassessment.php?cid=$cid&id=$aid\">"._("Modify Assessment")."</a>";
		require("../header.php");
		echo '<div class=breadcrumb>'.$curBreadcrumb.' &gt '._('Convert Intro').'</div>';
		echo '<div id="headeraddlinkedtext" class="pagetitle"><h2>'._('Convert Intro').'</h2></div>';
		if ($isembed) {
			echo '<p>'._('This assessment is using an older [QUESTION #] tag approach for embedding questions in text. There is now a simpler approach that will allow you to edit the between question text on the Add/Remove Questions page.').'</p>';
		} else {
			echo '<p>'._('This assessment is using an older [Q #] tag approach for providing introduction text or videos before questions. There is now a simpler approach that will allow you to edit the before question text on the Add/Remove Questions page.').'</p>';
		}
		echo '<p>'._('Converting assessments to use the new approach sometimes has issues, so please confirm below that everything looks as expected.  To be totally safe, you may wish to make a copy of your assessment before trying to convert it.').'</p>';
		echo '<h3>'._('The following will be the main intro/instruction text').'</h3>';
		echo '<div style="margin-left:30px;border:2px solid #000; padding: 10px;">';
		echo $introjson[0];
		array_shift($introjson);
		echo '</div>';
		if ($isembed) {
			echo '<h3>'._('The remaining shows the text, along with the position of the questions.').'</h3>';
		} else {
			echo '<h3>'._('The remaining which questions the text segments will show before.').'</h3>';
		}
		$nextquestion = 0;
		if ($isembed) {
			echo '<div>';
			foreach ($introjson as $intpc) {
				if ($intpc['displayBefore']>$nextquestion) {
					for ($i=$nextquestion;$i<$intpc['displayBefore'];$i++) {
						echo '<p style="color:#900;font-weight:bold">'.sprintf(_("Question %d displays here"), $i+1).'</p>';
					}
				}
				if ($intpc['ispage']==1) {
					echo '</div>';
					echo '<div style="margin-top: 10px; margin-left:30px;border:2px solid #000; padding: 10px;">';
					echo '<h3>'._('New Page: ').$intpc['pagetitle'].'</h3>';
				}
				echo $intpc['text'];
				$nextquestion = $intpc['displayBefore'];
			}
			for ($i=$nextquestion;$i<$qcnt;$i++) {
				echo '<p style="color:#900;font-weight:bold">'.sprintf(_("Question %d displays here"), $i+1).'</p>';
			}
			echo '</div>';
		} else {
			foreach ($introjson as $intpc) {
				if ($intpc['displayBefore']==$intpc['displayUntil']) {
					echo '<p style="color:#900;font-weight:bold">'.sprintf(_("The following will display before question %d"), $intpc['displayBefore']+1).'</p>';
				} else {
					echo '<p style="color:#900;font-weight:bold">'.sprintf(_("The following will display before questions %d - %d"), $intpc['displayBefore']+1, $intpc['displayUntil']+1).'</p>';
				}
				echo '<div style="margin-left:30px;border:2px solid #000; padding: 10px;">';
				echo $intpc['text'];
				echo '</div>';
			}
		}
		echo '<p>'._('Do you want to convert this assessment?').'</p>';
		echo '<p><button type="button" onClick="window.location=\'convertintro.php?cid='.$cid.'&aid='.$aid.'&confirm=true\'">'._('Convert').'</button> ';
		echo '<button type="button" class="secondarybtn" onClick="window.location=\'addassessment.php?cid='.$cid.'&id='.$aid.'\'">'._('Nevermind').'</button></p>';
		require("../footer.php");
	}
}
?>
