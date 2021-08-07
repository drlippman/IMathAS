<?php
//IMathAS:  Export or email Gradebook
//(c) 2007 David Lippman
	require("../init.php");

	$isteacher = isset($teacherid);
	$cid = Sanitize::courseId($_GET['cid']);
	if (!$isteacher) {
		echo "This page not available to students";
		exit;
	}
	$canviewall = true;
	if (isset($_SESSION[$cid.'gbmode'])) {
		$gbmode =  $_SESSION[$cid.'gbmode'];
	} else {
		$stm = $DBH->prepare("SELECT defgbmode FROM imas_gbscheme WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$cid));
		$gbmode = $stm->fetchColumn(0);
	}
	if (isset($_GET['stu']) && $_GET['stu']!='') {
		$stu = $_GET['stu'];
	} else {
		$stu = 0;
	}

	if (!isset($_POST['commentloc'])) {
		require("../header.php");
        echo "<div class=breadcrumb>$breadcrumbbase ";
        if (empty($_COOKIE['fromltimenu'])) {
            echo " <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
        }
        echo " <a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> &gt; Export Gradebook</div>";
		echo '<div id="headergb-export" class="pagetitle"><h1>Export Gradebook</h1></div>';

		echo "<form method=post action=\"gb-export.php?cid=$cid&stu=" . Sanitize::encodeUrlParam($stu) . "&gbmode=" . Sanitize::encodeUrlParam($gbmode);
		if (isset($_GET['export'])) {
			echo "&export=" . Sanitize::encodeUrlParam($_GET['export']);
		} else if (isset($_GET['emailgb'])) {
			echo "&emailgb=" . Sanitize::encodeUrlParam($_GET['emailgb']);
		}
		echo '" class="nolimit">';
		if (isset($_GET['emailgb']) && $_GET['emailgb']=="ask") {
			echo "<span class=\"form\">Email Gradebook To:</span><span class=\"formright\"> <input type=text name=\"email\" size=\"30\"/></span> <br class=\"form\" />";
		}

		echo '<span class="form">Locked students?</span><span class="formright"><input type="radio" name="locked" value="hide" checked="checked"> Hide <input type="radio" name="locked" value="show" > Show </span><br class="form" />';
		echo '<span class="form">Separate header line for points possible?</span><span class="formright"><input type="radio" name="pointsln" value="0" checked="checked"> No <input type="radio" name="pointsln" value="1"> Yes</span><br class="form" />';
		echo '<span class="form">Assessment comments:</span><span class="formright"> <input type="radio" name="commentloc" value="-1" checked="checked"> Don\'t include comments <br/>  <input type="radio" name="commentloc" value="1"> Separate set of columns at the end <br/><input type="radio" name="commentloc" value="0"> After each score column</span><br class="form" />';
		echo '<span class="form">Assessment times:</span><span class="formright"> <input type="radio" name="timestype" value="0" checked="checked"> Don\'t include assessment times <br/>  <input type="radio" name="timestype" value="1"> Include total assessment time <br/><input type="radio" name="timestype" value="2"> Include time in questions</span><br class="form" />';

		echo '<span class="form">Include last login date?</span><span class="formright"><input type="radio" name="lastlogin" value="0" checked="checked"> No <input type="radio" name="lastlogin" value="1" > Yes </span><br class="form" />';
		echo '<span class="form">Include total number of logins?</span><span class="formright"><input type="radio" name="logincnt" value="0" checked="checked"> No <input type="radio" name="logincnt" value="1" > Yes </span><br class="form" />';
		echo '<span class="form">Include email address?</span><span class="formright"><input type="radio" name="emailcol" value="0" checked="checked"> No <input type="radio" name="emailcol" value="1" > Yes </span><br class="form" />';


		if (isset($_GET['export'])) {
			echo '<p><input type=submit name="submit" value="Download Gradebook as CSV" /> <input type=submit name="submit" value="Download Gradebook for Excel" /> <a href="gradebook.php?cid='.$cid.'">Return to gradebook</a></p>';
			echo '<p>When you click the <b>Download Gradebook</b> button, your browser will probably ask if you want to save or ';
			echo 'open the file.  Click <b>Save</b> to save the file to your computer, or <b>Open</b> to open the gradebook in Excel ';
			echo 'or whatever program your computer has set to open .csv spreadsheet files</p>';
			echo '<p>A CSV (comma separated values) file will just contain data, and can be opened in most spreadsheet programs</p>';
			echo '<p>Using the Download for Excel button will generate an HTML file that Excel can open, preserving coloring and other formatting. ';
			echo 'Excel may show a "File format and extension don\'t match" warning. This is expected; hit Yes to open the file.</p>';
		} else {
			echo '<div class="submit"><input type=submit value="Email Gradebook" /></div>';
		}
		echo '</form>';
		require("../footer.php");
		exit;
	}

	if (isset($_POST['email'])) {
		$_GET['emailgb'] = $_POST['email'];
	}


	$commentloc = $_POST['commentloc'];  //0: interleve, 1: at end
	$pointsln = $_POST['pointsln']; //0: on main, 1: separate line
	$lastlogin = $_POST['lastlogin']; //0: no, 1 yes
    $logincnt = $_POST['logincnt']; //0: no, 1 yes
    $includeemail = !empty($_POST['emailcol']);
	$hidelocked = ($_POST['locked']=='hide')?true:false;
	$includetimes = intval($_POST['timestype']); //1 total time, 2 time on task

	$catfilter = -1;
	$secfilter = -1;

	//Gbmode : Links NC Dates
	$totonleft = floor($gbmode/1000)%10 ; //0 right, 1 left
	$links = floor($gbmode/100)%10; //0: view/edit, 1 q breakdown
	$hidenc = (floor($gbmode/10)%10)%4; //0: show all, 1 stu visisble (cntingb not 0), 2 hide all (cntingb 1 or 2)
	$availshow = $gbmode%10; //0: past, 1 past&cur, 2 all

	require("gbtable2.php");
	$includecomments = true;
	if ($_POST['submit']=="Download Gradebook for Excel") {
		header('Content-type: application/vnd.ms-excel');
		header('Content-Disposition: attachment; filename="gradebook-'.$cid.'.xls"');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		echo '<html><head>';
		echo '<style type="text/css">';
		require("../imascore.css");
		require("../themes/modern.css");
		echo '</style></head><body>';
		gbinstrdisp();
		echo '</body></html>';
		exit;
	} else {
		//grab HTML version, then strip out all the tags and reformat as CSV
		ob_start();
		gbinstrdisp();
		$gb = ob_get_clean();
		$gb = str_replace(array("<BR>",'<br>','<br/>',"\r\n","\n",'&nbsp;'), ' ',$gb);
		$gb = preg_replace('/<su(p|b)>.*?<\/su(p|b)>/', '', $gb);
		$gb = preg_replace('/<\/tr>.*?<tr.*?>/',';;tr;;', $gb);
		$gb = preg_replace('/<\/t(d|h)>\s*<t(d|h).*?>/',';;td;;', $gb);
		$gb = strip_tags($gb);
		$gb = explode(';;tr;;', $gb);
		foreach ($gb as $k=>$row) {
			$gb[$k] = explode(';;td;;', $row);
		}
		if (isset($_GET['export']) && $_GET['export']=="true") {
			header('Content-type: text/csv');
			header("Content-Disposition: attachment; filename=\"gradebook-$cid.csv\"");
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			foreach ($gb as $gbline) {
				$line = '';
				foreach ($gbline as $val) {
					  # if a deliminator char, a double quote char or a newline are in the field, add quotes
					  if(preg_match("/[\,\"\n\r]/", $val)) {
						  $val = '"'.str_replace('"', '""', $val).'"';
					  }
					  $line .= Sanitize::stripHtmlTags($val).',';
				}
				# strip the last deliminator
				$line = substr($line, 0, -1);
				$line .= "\n";
				echo $line;
			}
			exit;
		}
		if (isset($_GET['emailgb'])) {

			$line = '';
			foreach ($gb as $gbline) {

				foreach ($gbline as $val) {
					 # remove any windows new lines, as they interfere with the parsing at the other end
					  $val = str_replace("\r\n", "\n", $val);
					  $val = str_replace("\n", " ", $val);
					  $val = str_replace("<BR>", " ",$val);
					  $val = str_replace("<br/>", " ",$val);
					  $val = str_replace("&nbsp;"," ",$val);

					  # if a deliminator char, a double quote char or a newline are in the field, add quotes
					   if(preg_match("/[\,\"\n\r]/", $val)) {
						  $val = '"'.str_replace('"', '""', $val).'"';
					  }
					  $line .= $val.',';
				}
				# strip the last deliminator
				$line = substr($line, 0, -1);
				$line .= "\n";
			}
			$boundary = '-----=' . md5( uniqid ( rand() ) );
			$message = "--".$boundary . "\n";
			$message .= "Content-Type: text/csv; name=\"Gradebook\"\n";
			$message .= "Content-Transfer-Encoding: base64\n";
			$message .= "Content-Disposition: attachment; filename=\"gradebook.csv\"\n\n";
			$content_encode = chunk_split(base64_encode($line));
			$message .= $content_encode . "\n";
			$message .= "--" . $boundary . "--\n";
			$headers  = "From: $sendfrom\n";
			$headers .= "MIME-Version: 1.0\n";
			$headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"";
			if ($_GET['emailgb']=="me") {
				$stm = $DBH->prepare("SELECT email FROM imas_users WHERE id=:id");
				$stm->execute(array(':id'=>$userid));
				$_GET['emailgb'] = $stm->fetchColumn(0);
			}
			if ($_GET['emailgb']!='') {
				mail(Sanitize::emailAddress($_GET['emailgb']), "Gradebook for $coursename", $message, $headers);
				require("../header.php");
				echo "Gradebook Emailed.  <a href=\"gradebook.php?cid=$cid\">Return to Gradebook</a>";
				require("../footer.php");
				exit;
			}

		}
	}


//HTML formatted, for Excel import?
function gbInstrCatHdrs(&$gbt, &$pointsrow) {
	global $catfilter, $availshow, $totonleft, $cid, $pointsln;

	$n = 0;
	$tots = '';
	$pointstots = '';
	if ($catfilter<0) {
		if ($gbt[0][4][0]==0) { //using points based
			if ($availshow<3) {
				$tots .= '<th><div><span class="cattothdr">'. _('Total');
				if ($pointsln==1) {
					$pointstots .= '<th>'.$gbt[0][3][$availshow].'&nbsp;'. _('pts').'</th>';
				} else {
					$tots .= '&nbsp;'.$gbt[0][3][$availshow].'&nbsp;'. _('pts');
				}
				$tots .= '</span></div></th>';
			} else {
				$tots .= '<th><div><span class="cattothdr">'. _('Total').'</span></div></th>';
				$pointstots .= '<th></th>';
			}
			$tots .= '<th><div>%</div></th>';
			$pointstots .= '<th></th>';
			$n+=2;
		} else {
			$tots .= '<th><div><span class="cattothdr">'. _('Weighted Total %'). '</span></div></th>';
			$pointstots .= '<th></th>';
			$n++;
		}
	}
	if ($totonleft) {
		echo $tots;
		$pointsrow .= $pointstots;
	}
	if (count($gbt[0][2])>1 || $catfilter!=-1) { //want to show cat headers?
		for ($i=0;$i<count($gbt[0][2]);$i++) { //category headers
			if (($availshow<2 || $availshow==3) && $gbt[0][2][$i][2]>1) {
				continue;
			} else if ($availshow==2 && $gbt[0][2][$i][2]==3) {
				continue;
			}
			echo '<th class="cat'.$gbt[0][2][$i][1].'"><div><span class="cattothdr">';
			if ($availshow<3) {
				echo $gbt[0][2][$i][0];
				if ($gbt[0][4][0]==0) { //using points based
					$v = $gbt[0][2][$i][3+$availshow].'&nbsp;'. _('pts');
				} else {
					$v = $gbt[0][2][$i][11].'%';
				}
				if ($pointsln==1) {
					$pointsrow .= '<th>'.$v.'</th>';
				} else {
					echo '&nbsp;'.$v;
				}
			} else if ($availshow==3) { //past and attempted
				echo $gbt[0][2][$i][0];
				if (isset($gbt[0][2][$i][11])) {
					if ($pointsln==1) {
						$pointsrow .= '<th>'.$gbt[0][2][$i][11].'</th>';
					} else {
						echo '<br/>'.$gbt[0][2][$i][11].'%';
					}
				} else if ($pointsln==1) {
					$pointsrow .= '<th></th>';
				}
			}

			echo '</span></div></th>';
			$n++;
		}
	}
	if (!$totonleft) {
		echo $tots;
		$pointsrow .= $pointstots;
	}
	return $n;
}
function gbInstrCatCols(&$gbt, $i, $insdiv='', $enddiv='') {
	global $catfilter, $availshow, $totonleft, $cid;

	//total totals
	$tot = '';
	if ($catfilter<0) {
		$fivenum = "<span onmouseover=\"tipshow(this,'". _('5-number summary:'). " {$gbt[0][3][3+$availshow]}')\" onmouseout=\"tipout()\" >";
		if ($gbt[$i][3][4+$availshow]>0) {
			$pct = round(100*$gbt[$i][3][$availshow]/$gbt[$i][3][4+$availshow],1);
		} else {
			$pct = 0;
		}
		if ($availshow==3 || $gbt[0][4][0]==0) { //attempted or using points based
			if ($gbt[$i][0][0]=='Averages') {
				if ($gbt[0][4][0]==0) { //using points based
					$tot .= '<td class="c">'.$insdiv.$pct.'%'.$enddiv .'</td>';
				}
				$tot .= '<td class="c">'.$insdiv.$fivenum.$pct.'%</span>'.$enddiv .'</td>';
			} else {
				if ($gbt[0][4][0]==0) { //using points based
					$tot .= '<td class="c">'.$insdiv.$gbt[$i][3][$availshow].'/'.$gbt[$i][3][4+$availshow].$enddiv.'</td>';
					$tot .= '<td class="c">'.$insdiv.$pct .'%'.$enddiv .'</td>';

				} else {
					$tot .= '<td class="c">'.$insdiv.$pct.'%'.$enddiv .'</td>';
				}
			}
		} else {
			if ($gbt[0][4][0]==0) { //using points based
				$tot .= '<td class="c">'.$insdiv.$gbt[$i][3][$availshow].$enddiv .'</td>';
				if ($gbt[$i][0][0]=='Averages') {
					$tot .= '<td class="c">'.$insdiv.$fivenum.$pct .'%</span>'.$enddiv .'</td>';
				} else {
					$tot .= '<td class="c">'.$insdiv.$pct .'%'.$enddiv .'</td>';
				}
			} else {
				if ($gbt[$i][0][0]=='Averages') {
					$tot .= '<td class="c">'.$insdiv.$fivenum.$pct.'%</span>'.$enddiv .'</td>';
				} else {
					$tot .= '<td class="c">'.$insdiv.$pct.'%'.$enddiv .'</td>';
				}
			}
		}
	}
	if ($totonleft) {
		echo $tot;
	}
	//category totals
	if (count($gbt[0][2])>1 || $catfilter!=-1) { //want to show cat headers?
		for ($j=0;$j<count($gbt[0][2]);$j++) { //category headers
			if (($availshow<2 || $availshow==3) && $gbt[0][2][$j][2]>1) {
				continue;
			} else if ($availshow==2 && $gbt[0][2][$j][2]==3) {
				continue;
			}
			if ($gbt[$i][2][$j][4+$availshow]>0) {
				$pct = round(100*$gbt[$i][2][$j][$availshow]/$gbt[$i][2][$j][4+$availshow],1);
			} else {
				$pct = $gbt[$i][2][$j][$availshow];
			}
			echo '<td class="c">'.$insdiv;
			if ($gbt[$i][0][0]=='Averages' && $gbt[0][2][$j][6+$availshow]!='') {
				echo "<span onmouseover=\"tipshow(this,'", _('5-number summary:'), " {$gbt[0][2][$j][6+$availshow]}')\" onmouseout=\"tipout()\" >";
			}
			if ($catfilter!=-1) { //single category view

				if ($gbt[$i][0][0]=='Averages') {
					if ($gbt[$i][2][$j][4+$availshow] == 0) {
						echo $gbt[$i][2][$j][$availshow].'%';
					} else {
						echo $gbt[$i][2][$j][$availshow];
					}
				} else if ($gbt[$i][2][$j][4+$availshow]>0) { //category total has points poss listed
					echo $gbt[$i][2][$j][$availshow].'/'.$gbt[$i][2][$j][4+$availshow].' ('.$pct.'%)';
				} else {
					echo $pct.'%';
				}

			} else {
				if ($availshow==3 || ($gbt[0][4][0]==0 && $gbt[0][2][$j][13]==0)) {  //attempted or points based w/o percent scaling
					if ($gbt[$i][0][0]=='Averages') {
						if ($gbt[$i][2][$j][4+$availshow] == 0) {
							echo $gbt[$i][2][$j][$availshow].'%';
						} else {
							echo $gbt[$i][2][$j][$availshow];
						}
					} else if ($gbt[0][2][$j][14]==true || $availshow==3) { //if has drops or attempted
						echo $gbt[$i][2][$j][$availshow].'/'.$gbt[$i][2][$j][4+$availshow];
					} else {
						echo $gbt[$i][2][$j][$availshow];
					}
				} else {
					echo $pct.'%';
				}
			}
			if ($gbt[$i][0][0]=='Averages' && $availshow!=3 && $gbt[0][2][$j][6+$availshow]!='') {
				echo '</span>';
			}
			echo $enddiv .'</td>';
		}
	}
	if (!$totonleft) {
		echo $tot;
	}
}
function gbinstrdisp() {
	global $DBH,$hidenc,$isteacher,$istutor,$cid,$gbmode,$stu,$availshow,$catfilter,$secfilter,$totonleft,$imasroot,$isdiag,$tutorsection,$commentloc,$pointsln,$logincnt,$includetimes;

	if ($availshow==4) {
		$availshow=1;
		$hidepast = true;
	}
	$gbt = gbtable();
	echo '<table class="gb" id="myTable"><thead><tr>';
	$n=0;
	$pointsrow = '<th>Points Possible</th>';
	for ($i=0;$i<count($gbt[0][0]);$i++) { //biographical headers
		//if ($i==1 && $gbt[0][0][1]!='ID') { continue;}
		echo '<th>'.$gbt[0][0][$i];
		if ($gbt[0][0][$i]=='Name') {
			echo '&nbsp;<span class="small">N='.(count($gbt)-2).'</span>';
		}
		echo '</th>';
		if ($i>0) {
			$pointsrow .= '<th></th>';
		}
		$n++;
	}
	if ($totonleft && !$hidepast) {
		$n += gbInstrCatHdrs($gbt, $pointsrow);
	}

	if ($catfilter>-2) {
		for ($i=0;$i<count($gbt[0][1]);$i++) { //assessment headers
			if (!$isteacher && !$istutor && $gbt[0][1][$i][4]==0) { //skip if hidden
				continue;
			}
			if ($hidenc==1 && $gbt[0][1][$i][4]==0) { //skip NC
				continue;
			} else if ($hidenc==2 && ($gbt[0][1][$i][4]==0 || $gbt[0][1][$i][4]==3)) {//skip all NC
				continue;
			}
			if ($gbt[0][1][$i][3]>$availshow) {
				continue;
			}
			if ($hidepast && $gbt[0][1][$i][3]==0) {
				continue;
			}
			//name and points
			echo '<th class="cat'.$gbt[0][1][$i][1].'">'.$gbt[0][1][$i][0];

			if ($gbt[0][1][$i][4]==0 || $gbt[0][1][$i][4]==3) {
				$v = $gbt[0][1][$i][2].' (Not Counted)';
			} else {
				$v = $gbt[0][1][$i][2].'&nbsp;pts';
				if ($gbt[0][1][$i][4]==2) {
					$v .= ' (EC)';
				}
			}
			if ($gbt[0][1][$i][5]==1 && $gbt[0][1][$i][6]==0) {
				$v .= ' (PT)';
			}
			if ($pointsln==1) {
				$pointsrow .= '<th>'.$v.'</th>';
			} else {
				echo '&nbsp;'.$v;
			}
			echo '</th>';
			$n++;
			if ($commentloc==0) {
				echo '<th>'. $gbt[0][1][$i][0].': Comments'.'</th>';
				if ($pointsln==1) {
					$pointsrow .= '<th></th>';
				}
				$n++;
			}
			if ($includetimes>0 && $gbt[0][1][$i][6]==0) {
				if ($includetimes==1) {
					echo '<th>'. $gbt[0][1][$i][0].': Time spent'.'</th>';
				} else if ($includetimes==2) {
					echo '<th>'. $gbt[0][1][$i][0].': Time spent in questions'.'</th>';
				}
				$pointsrow .= '<th></th>';
				$n++;
			}
		}
	}
	if (!$totonleft && !$hidepast) {
		$n += gbInstrCatHdrs($gbt, $pointsrow);
	}
	echo '<th>Comment</th>';
	echo '<th>Instructor Note</th>';
	$pointsrow .= '<th></th><th></th>';

	$n+=2;
	if ($commentloc == 1) {
		if ($catfilter>-2) {
			for ($i=0;$i<count($gbt[0][1]);$i++) { //assessment comment headers
				if (!$isteacher && $gbt[0][1][$i][4]==0) { //skip if hidden
					continue;
				}
				if ($hidenc==1 && $gbt[0][1][$i][4]==0) { //skip NC
					continue;
				} else if ($hidenc==2 && ($gbt[0][1][$i][4]==0 || $gbt[0][1][$i][4]==3)) {//skip all NC
					continue;
				}
				if ($gbt[0][1][$i][3]>$availshow) {
					continue;
				}
				//name and points
				echo '<th>'.$gbt[0][1][$i][0].': Comments'.'</th>';
				$pointsrow .= '<th></th>';
				$n++;
			}
		}
	}
	echo '</tr>';
	if ($pointsln==1) {
		//remove "pts" from points possible row
		$pointsrow = str_replace('&nbsp;'. _('pts'), '', $pointsrow);
		echo '<tr>'.$pointsrow.'</tr>';
	}
	echo '</thead><tbody>';
	//get gb comments;
	$gbcomments = array();
	$stm = $DBH->prepare("SELECT userid,gbcomment,gbinstrcomment FROM imas_students WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$gbcomments[$row[0]] = array($row[1],$row[2]);
	}
	//create student rows
	for ($i=1;$i<count($gbt);$i++) {
		if ($i%2!=0) {
			echo "<tr class=even onMouseOver=\"highlightrow(this)\" onMouseOut=\"unhighlightrow(this)\">";
		} else {
			echo "<tr class=odd onMouseOver=\"highlightrow(this)\" onMouseOut=\"unhighlightrow(this)\">";
		}
		echo '<td class="locked" scope="row">';
		echo $gbt[$i][0][0];
		echo '</td>';
		for ($j=1;$j<count($gbt[0][0]);$j++) {
			echo '<td class="c">'.$gbt[$i][0][$j].'</td>';
		}
		if ($totonleft && !$hidepast) {
			gbInstrCatCols($gbt, $i);
		}
		//assessment values
		if ($catfilter>-2) {
			for ($j=0;$j<count($gbt[0][1]);$j++) {
				if (!$isteacher && !$istutor && $gbt[0][1][$j][4]==0) { //skip if hidden
					continue;
				}
				if ($hidenc==1 && $gbt[0][1][$j][4]==0) { //skip NC
					continue;
				} else if ($hidenc==2 && ($gbt[0][1][$j][4]==0 || $gbt[0][1][$j][4]==3)) {//skip all NC
					continue;
				}
				if ($gbt[0][1][$j][3]>$availshow) {
					continue;
				}
				if ($hidepast && $gbt[0][1][$j][3]==0) {
					continue;
				}
				echo '<td class="c">';
				if (isset($gbt[$i][1][$j][5]) && ($gbt[$i][1][$j][5]&(1<<$availshow)) && !$hidepast) {
					echo '<span style="font-style:italic">';
				}
				if ($gbt[0][1][$j][6]==0) {//online
					if (isset($gbt[$i][1][$j][0])) {

						echo $gbt[$i][1][$j][0];
						if ($gbt[$i][1][$j][3]==1) {
							echo ' (NC)';
						} else if ($gbt[$i][1][$j][3]==2) {
							echo ' (IP)';
						} else if ($gbt[$i][1][$j][3]==3) {
							echo ' (OT)';
						} else if ($gbt[$i][1][$j][3]==4) {
							echo ' (PT)';
						}

					} else { //no score
						if ($gbt[$i][0][0]=='Averages') {
							echo '-';
						} else {
							echo '-';
						}
					}
					if (isset($gbt[$i][1][$j][6]) ) {
						if ($gbt[$i][1][$j][6]>1) {
							if ($gbt[$i][1][$j][6]>2) {
								echo '<sup>LP ('.($gbt[$i][1][$j][6]-1).')</sup>';
							} else {
								echo '<sup>LP</sup>';
							}
						} else {
							echo '<sup>e</sup>';
						}
                    }
				} else if ($gbt[0][1][$j][6]==1) { //offline

					if (isset($gbt[$i][1][$j][0])) {
						echo $gbt[$i][1][$j][0];
						if ($gbt[$i][1][$j][3]==1) {
							echo ' (NC)';
						}
					} else {
						echo '-';
					}

					if ($gbt[$i][1][$j][1]==1) {
						echo '<sup>*</sup>';
					}
				} else if ($gbt[0][1][$j][6]==2) { //discuss
					if (isset($gbt[$i][1][$j][0])) {
						echo $gbt[$i][1][$j][0];
					} else {
						echo '-';
					}
                }
                if (!empty($gbt[$i][1][$j][14])) { //excused
					echo '<sup>x</sup>';
				}
				if (isset($gbt[$i][1][$j][5]) && ($gbt[$i][1][$j][5]&(1<<$availshow)) && !$hidepast) {
					echo '<sub>d</sub></span>';
				}
				echo '</td>';
				if ($commentloc==0) {
					if (isset($gbt[$i][1][$j][1])) {
						echo '<td>'.strip_tags($gbt[$i][1][$j][1]).'</td>';
					} else {
						echo '<td></td>';
					}
					$n++;
				}
				if ($includetimes>0 && $gbt[0][1][$j][6]==0) {
					if ($includetimes==1) {
						echo '<td>'.$gbt[$i][1][$j][7].'</td>';
					} else if ($includetimes==2) {
						echo '<td>'.$gbt[$i][1][$j][8].'</td>';
					}
					$n++;
				}
			}
		}
		if (!$totonleft && !$hidepast) {
			gbInstrCatCols($gbt, $i);
		}
		if (isset($gbcomments[$gbt[$i][4][0]])) {
			echo '<td>' . Sanitize::encodeStringForDisplay(strip_tags($gbcomments[$gbt[$i][4][0]][0])) . '</td>';
			echo '<td>' . Sanitize::encodeStringForDisplay(strip_tags($gbcomments[$gbt[$i][4][0]][1])) . '</td>';
		} else {
			echo '<td></td>';
			echo '<td></td>';
		}
		$n+=2;
		if ($commentloc == 1) {
			if ($catfilter>-2) {
				for ($j=0;$j<count($gbt[0][1]);$j++) {
					if (!$isteacher && $gbt[0][1][$j][4]==0) { //skip if hidden
						continue;
					}
					if ($hidenc==1 && $gbt[0][1][$j][4]==0) { //skip NC
						continue;
					} else if ($hidenc==2 && ($gbt[0][1][$j][4]==0 || $gbt[0][1][$j][4]==3)) {//skip all NC
						continue;
					}
					if ($gbt[0][1][$j][3]>$availshow) {
						continue;
					}
					if (isset($gbt[$i][1][$j][1])) {
						echo '<td>'.strip_tags($gbt[$i][1][$j][1]).'</td>';
					} else {
						echo '<td></td>';
					}
					$n++;
				}
			}
		}
		echo '</tr>';
	}
	echo "</tbody></table>";
}

?>
