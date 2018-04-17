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
	if (isset($sessiondata[$cid.'gbmode'])) {
		$gbmode =  $sessiondata[$cid.'gbmode'];
	} else {
		//DB $query = "SELECT defgbmode FROM imas_gbscheme WHERE courseid='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $gbmode = mysql_result($result,0,0);
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
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
		echo "&gt; <a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> &gt; Export Gradebook</div>";
		echo '<div id="headergb-export" class="pagetitle"><h2>Export Gradebook</h2></div>';

		echo "<form method=post action=\"gb-export.php?cid=$cid&stu=" . Sanitize::encodeUrlParam($stu) . "&gbmode=" . Sanitize::encodeUrlParam($gbmode);
		if (isset($_GET['export'])) {
			echo "&export=" . Sanitize::encodeUrlParam($_GET['export']);
		} else if (isset($_GET['emailgb'])) {
			echo "&emailgb=" . Sanitize::encodeUrlParam($_GET['emailgb']);
		}
		echo '" class="nolimit">';
		if ($_GET['emailgb']=="ask") {
			echo "<span class=\"form\">Email Gradebook To:</span><span class=\"formright\"> <input type=text name=\"email\" size=\"30\"/></span> <br class=\"form\" />";
		}

		echo '<span class="form">Locked students?</span><span class="formright"><input type="radio" name="locked" value="hide" checked="checked"> Hide <input type="radio" name="locked" value="show" > Show </span><br class="form" />';
		echo '<span class="form">Separate header line for points possible?</span><span class="formright"><input type="radio" name="pointsln" value="0" checked="checked"> No <input type="radio" name="pointsln" value="1"> Yes</span><br class="form" />';
		echo '<span class="form">Assessment comments:</span><span class="formright"> <input type="radio" name="commentloc" value="-1" checked="checked"> Don\'t include comments <br/>  <input type="radio" name="commentloc" value="1"> Separate set of columns at the end <br/><input type="radio" name="commentloc" value="0"> After each score column</span><br class="form" />';
		echo '<span class="form">Assessment times:</span><span class="formright"> <input type="radio" name="timestype" value="0" checked="checked"> Don\'t include assessment times <br/>  <input type="radio" name="timestype" value="1"> Include total assessment time <br/><input type="radio" name="timestype" value="2"> Include time in questions</span><br class="form" />';

		echo '<span class="form">Include last login date?</span><span class="formright"><input type="radio" name="lastlogin" value="0" checked="checked"> No <input type="radio" name="lastlogin" value="1" > Yes </span><br class="form" />';
		echo '<span class="form">Include total number of logins?</span><span class="formright"><input type="radio" name="logincnt" value="0" checked="checked"> No <input type="radio" name="logincnt" value="1" > Yes </span><br class="form" />';


		if (isset($_GET['export'])) {
			echo '<p><input type=submit name="submit" value="Download Gradebook as CSV" /> <input type=submit name="submit" value="Download Gradebook for Excel" /> <a href="gradebook.php?cid='.$cid.'">Return to gradebook</a></p>';
			echo '<p>When you click the <b>Download Gradebook</b> button, your browser will probably ask if you want to save or ';
			echo 'open the file.  Click <b>Save</b> to save the file to your computer, or <b>Open</b> to open the gradebook in Excel ';
			echo 'or whatever program your computer has set to open .csv spreadsheet files</p>';
			echo '<p>A CSV (comma separated values) file will just contain data, and can be opened in most spreadsheet programs</p>';
			echo '<p>Using the Download for Excel button will generate an HTML file that Excel can open, and will most likely preserve coloring and other formatting</p>';
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
		$gb = gbinstrexport();
		if (isset($_GET['export']) && $_GET['export']=="true") {
			header('Content-type: text/csv');
			header("Content-Disposition: attachment; filename=\"gradebook-$cid.csv\"");
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			foreach ($gb as $gbline) {
				$line = '';
				foreach ($gbline as $val) {
					 # remove any windows new lines, as they interfere with the parsing at the other end
					  $val = str_replace("\r\n", "\n", $val);
					  $val = str_replace("\n", " ", $val);
					  $val = str_replace(array("<BR>",'<br>','<br/>'), ' ',$val);
					  $val = str_replace("&nbsp;"," ",$val);

					  # if a deliminator char, a double quote char or a newline are in the field, add quotes
					  if(preg_match("/[\,\"\n\r]/", $val)) {
						  $val = '"'.str_replace('"', '""', $val).'"';
					  }
					  $line .= Sanitize::outgoingHtml($val).',';
				}
				# strip the last deliminator
				$line = substr($line, 0, -1);
				$line .= "\n";
				echo Sanitize::outgoingHtml($line);
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
				//DB $query = "SELECT email FROM imas_users WHERE id='$userid'";
				//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
				//DB $_GET['emailgb'] = mysql_result($result,0,0);
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




function gbinstrexport() {
	global $DBH,$hidenc,$nopt,$isteacher,$cid,$gbmode,$stu,$availshow,$isdiag,$catfilter,$secfilter,$totonleft,$commentloc,$pointsln,$lastlogin,$logincnt,$includetimes;
	$gbt = gbtable();
	$gbo = array();
	//print_r($gbt);
	$n=0;
	for ($i=0;$i<count($gbt[0][0]);$i++) { //biographical headers
		$gbo[0][$n] = $gbt[0][0][$i];
		$n++;
	}
	if ($totonleft) {
		//total totals
		if ($catfilter<0) {
			if (isset($gbt[0][3][0])) { //using points based
				$gbo[0][$n] = "Total: ".$gbt[0][3][$availshow]." pts";
				$n++;
				$gbo[0][$n] = "%";
				$n++;
			} else {
				$gbo[0][$n] = "Weighted Total %";
				$n++;
			}
		}
		if (count($gbt[0][2])>1 || $catfilter!=-1) { //want to show cat headers?
			for ($i=0;$i<count($gbt[0][2]);$i++) { //category headers
				if (($availshow<2 || $availshow==3) && $gbt[0][2][$i][2]>1) {
					continue;
				} else if ($availshow==2 && $gbt[0][2][$i][2]==3) {
					continue;
				}
				if ($availshow<3) {
					if (isset($gbt[0][3][0])) { //using points based
						$gbo[0][$n] = $gbt[0][2][$i][0].': '.$gbt[0][2][$i][3+$availshow].' pts';
					} else {
						$gbo[0][$n] = $gbt[0][2][$i][0].': '.$gbt[0][2][$i][11].'%';
					}
				} else if ($availshow==3) {
					if (isset($gbt[0][2][$i][11])) {
						$gbo[0][$n] = $gbt[0][2][$i][11].'%';
					}
				}
				$n++;
			}
		}

	}
	if ($catfilter>-2) {
		for ($i=0;$i<count($gbt[0][1]);$i++) { //assessment headers
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
			$gbo[0][$n] = $gbt[0][1][$i][0].': ';
			if ($gbt[0][1][$i][4]==0 || $gbt[0][1][$i][4]==3) {
				$gbo[0][$n] .=' (Not Counted)';
			} else {
				$gbo[0][$n] .= $gbt[0][1][$i][2].'&nbsp;pts';
				if ($gbt[0][1][$i][4]==2) {
					$gbo[0][$n] .= ' (EC)';
				}
			}
			if ($gbt[0][1][$i][5]==1) {
				$gbo[0][$n] .= ' (PT)';
			}
			$n++;
			if ($commentloc==0) {
				$gbo[0][$n] = $gbt[0][1][$i][0].': Comments';
				$n++;
			}
			if ($includetimes>0 && $gbt[0][1][$i][6]==0) {
				if ($includetimes==1) {
					$gbo[0][$n] = $gbt[0][1][$i][0].': Time spent';
				} else if ($includetimes==2) {
					$gbo[0][$n] = $gbt[0][1][$i][0].': Time spent in questions';
				}
				$n++;
			}
		}
	}
	if (!$totonleft) {
		//total totals
		if (count($gbt[0][2])>1 || $catfilter!=-1) { //want to show cat headers?
			for ($i=0;$i<count($gbt[0][2]);$i++) { //category headers
				if (($availshow<2 || $availshow==3) && $gbt[0][2][$i][2]>1) {
					continue;
				} else if ($availshow==2 && $gbt[0][2][$i][2]==3) {
					continue;
				}
				if ($availshow<3) {
					if (isset($gbt[0][3][0])) { //using points based
						$gbo[0][$n] = $gbt[0][2][$i][0].': '.$gbt[0][2][$i][3+$availshow].' pts';
					} else {
						$gbo[0][$n] = $gbt[0][2][$i][0].': '.$gbt[0][2][$i][11].'%';
					}
				} else if ($availshow==3) {
					if (isset($gbt[0][2][$i][11])) {
						$gbo[0][$n] = $gbt[0][2][$i][11].'%';
					}
				}
				$n++;
			}
		}
		if ($catfilter<0) {
			if (isset($gbt[0][3][0])) { //using points based
				$gbo[0][$n] = "Total: ".$gbt[0][3][$availshow]." pts";
				$n++;
				$gbo[0][$n] = "%";
				$n++;
			} else {
				$gbo[0][$n] = "Weighted Total %";
				$n++;
			}
		}
	}
	$gbo[0][$n] = "Comment";
	$gbo[0][$n+1] = "Instructor Note";
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
				$gbo[0][$n] = $gbt[0][1][$i][0].': Comments';
				$n++;
			}
		}
	}

	//get gb comments;
	$gbcomments = array();
	//DB $query = "SELECT userid,gbcomment,gbinstrcomment FROM imas_students WHERE courseid='$cid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB while ($row = mysql_fetch_row($result)) {
	$stm = $DBH->prepare("SELECT userid,gbcomment,gbinstrcomment FROM imas_students WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$gbcomments[$row[0]] = array($row[1],$row[2]);
	}
	//create student rows
	for ($i=1;$i<count($gbt);$i++) {
		$n=0;

		for ($j=0;$j<count($gbt[0][0]);$j++) {
			$gbo[$i][$n] = $gbt[$i][0][$j];
			$n++;
		}
		if ($totonleft) {
			//total totals
			if ($catfilter<0) {
				if ($availshow==3) {
					if (isset($gbt[$i][3][8])) { //using points based
						$gbo[$i][$n] = $gbt[$i][3][6].'/'.$gbt[$i][3][7];
						$n++;
						$gbo[$i][$n] = $gbt[$i][3][8] ;
						$n++;
					} else {
						$gbo[$i][$n] = $gbt[$i][3][6];
						$n++;
					}
				} else {
					if (isset($gbt[0][3][0])) { //using points based
						$gbo[$i][$n] = $gbt[$i][3][$availshow];
						$n++;
						$gbo[$i][$n] = $gbt[$i][3][$availshow+3] ;
						$n++;
					} else {
						$gbo[$i][$n] = $gbt[$i][3][$availshow];
						$n++;
					}
				}
			}
			//category totals
			if (count($gbt[0][2])>1 || $catfilter!=-1) { //want to show cat headers?
				for ($j=0;$j<count($gbt[0][2]);$j++) { //category headers
					if (($availshow<2 || $availshow==3) && $gbt[0][2][$j][2]>1) {
						continue;
					} else if ($availshow==2 && $gbt[0][2][$j][2]==3) {
						continue;
					}
					if ($catfilter!=-1 && $availshow<3 && $gbt[0][2][$j][$availshow+3]>0) {
						$gbo[$i][$n] = $gbt[$i][2][$j][$availshow].' ('.round(100*$gbt[$i][2][$j][$availshow]/$gbt[0][2][$j][$availshow+3])  .'%)';
					} else {
						if ($availshow==3) {
							$gbo[$i][$n] = $gbt[$i][2][$j][3].' of '.$gbt[$i][2][$j][4];
						} else {
							if (isset($gbt[$i][3][8])) { //using points based
								$gbo[$i][$n] = $gbt[$i][2][$j][$availshow];
							} else {
								if ($gbt[0][2][$j][3+$availshow]>0) {
									$gbo[$i][$n] = round(100*$gbt[$i][2][$j][$availshow]/$gbt[0][2][$j][3+$availshow],1).'%';
								} else {
									$gbo[$i][$n] = '0%';
								}
							}
						}

					}
					$n++;
				}
			}
		}
		//assessment values
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
				if ($gbt[0][1][$j][6]==0) {//online
					if (isset($gbt[$i][1][$j][0])) {
						$gbo[$i][$n] = $gbt[$i][1][$j][0];
						if ($gbt[$i][1][$j][3]==1) {
							$gbo[$i][$n] .=  ' (NC)';
						} else if ($gbt[$i][1][$j][3]==2) {
							$gbo[$i][$n] .=  ' (IP)';
						} else if ($gbt[$i][1][$j][3]==3) {
							$gbo[$i][$n] .= ' (OT)';
						} else if ($gbt[$i][1][$j][3]==4) {
							$gbo[$i][$n] .=  ' {PT)';
						}

					} else { //no score
						$gbo[$i][$n]  = '-';
					}
				} else if ($gbt[0][1][$j][6]==1) { //offline
					if (isset($gbt[$i][1][$j][0])) {
						$gbo[$i][$n] =  $gbt[$i][1][$j][0];
						if ($gbt[$i][1][$j][3]==1) {
							$gbo[$i][$n] .=  ' (NC)';
						}
					} else {
						$gbo[$i][$n] = '-';
					}

				} else if ($gbt[0][1][$j][6]==2) { //discuss
					if (isset($gbt[$i][1][$j][0])) {
						$gbo[$i][$n] = $gbt[$i][1][$j][0];
					} else {
						$gbo[$i][$n] =  '-';
					}
				}
				$n++;
				if ($commentloc==0) {
					if (isset($gbt[$i][1][$j][1])) {
						$gbo[$i][$n] = $gbt[$i][1][$j][1];
					} else {
						$gbo[$i][$n] = '';
					}
					$n++;
				}
				if ($includetimes>0 && $gbt[0][1][$j][6]==0) {
					if ($includetimes==1) {
						$gbo[$i][$n] = $gbt[$i][1][$j][7];
					} else if ($includetimes==2) {
						$gbo[$i][$n] = $gbt[$i][1][$j][8];
					}
					$n++;
				}
			}
		}
		if (!$totonleft) {
			//category totals
			if (count($gbt[0][2])>1 || $catfilter!=-1) { //want to show cat headers?
				for ($j=0;$j<count($gbt[0][2]);$j++) { //category headers
					if (($availshow<2 || $availshow==3) && $gbt[0][2][$j][2]>1) {
						continue;
					} else if ($availshow==2 && $gbt[0][2][$j][2]==3) {
						continue;
					}
					if ($catfilter!=-1 && $availshow<3 && $gbt[0][2][$j][$availshow+3]>0) {
						$gbo[$i][$n] = $gbt[$i][2][$j][$availshow].' ('.round(100*$gbt[$i][2][$j][$availshow]/$gbt[0][2][$j][$availshow+3])  .'%)';
					} else {
						if ($availshow==3) {
							$gbo[$i][$n] = $gbt[$i][2][$j][3].' of '.$gbt[$i][2][$j][4];
						} else {
							if (isset($gbt[$i][3][8])) { //using points based
								$gbo[$i][$n] = $gbt[$i][2][$j][$availshow];
							} else {
								if ($gbt[0][2][$j][3+$availshow]>0) {
									$gbo[$i][$n] = round(100*$gbt[$i][2][$j][$availshow]/$gbt[0][2][$j][3+$availshow],1).'%';
								} else {
									$gbo[$i][$n] = '0%';
								}
							}
						}

					}
					$n++;
				}
			}
			//total totals
			if ($catfilter<0) {
				if ($availshow==3) {
					if (isset($gbt[$i][3][8])) { //using points based
						$gbo[$i][$n] = $gbt[$i][3][6].'/'.$gbt[$i][3][7];
						$n++;
						$gbo[$i][$n] = $gbt[$i][3][8] ;
						$n++;
					} else {
						$gbo[$i][$n] = $gbt[$i][3][6];
						$n++;
					}
				} else {
					if (isset($gbt[0][3][0])) { //using points based
						$gbo[$i][$n] = $gbt[$i][3][$availshow];
						$n++;
						$gbo[$i][$n] = $gbt[$i][3][$availshow+3] ;
						$n++;
					} else {
						$gbo[$i][$n] = $gbt[$i][3][$availshow];
						$n++;
					}
				}
			}

		}
		if (isset($gbcomments[$gbt[$i][4][0]])) {
			$gbo[$i][$n] = $gbcomments[$gbt[$i][4][0]][0];
			$gbo[$i][$n+1] = $gbcomments[$gbt[$i][4][0]][1];
		} else {
			$gbo[$i][$n] = '';
			$gbo[$i][$n+1] = '';
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
						$gbo[$i][$n] = $gbt[$i][1][$j][1];
					} else {
						$gbo[$i][$n] = '';
					}
					$n++;
				}
			}
		}
	}
	if ($pointsln==1) {
		$ins = array();

		for ($i=0; $i<count($gbo[0]);$i++) {
			if (preg_match('/(-?[\d\.]+)(\s*|&nbsp;)pts.*/',$gbo[0][$i],$matches)) {
				$ins[$i] = $matches[1];
			} else {
				$ins[$i] = '';
			}
		}
		$ins[0] = "Points Possible";
		array_splice($gbo,1,0,array($ins));
	}
	return $gbo;
}


//HTML formatted, for Excel import?
function gbinstrdisp() {
	global $DBH,$hidenc,$isteacher,$istutor,$cid,$gbmode,$stu,$availshow,$catfilter,$secfilter,$totonleft,$imasroot,$isdiag,$tutorsection,$commentloc,$pointsln,$logincnt,$includetimes;

	if ($availshow==4) {
		$availshow=1;
		$hidepast = true;
	}
	$gbt = gbtable();
	echo '<table class="gb" id="myTable"><thead><tr>';
	$n=0;
	for ($i=0;$i<count($gbt[0][0]);$i++) { //biographical headers
		//if ($i==1 && $gbt[0][0][1]!='ID') { continue;}
		echo '<th>'.$gbt[0][0][$i];
		if ($gbt[0][0][$i]=='Name') {
			if ($pointsln==1) {
					echo '<br/>';
				} else {
					echo '&nbsp;';
				}
			echo '<span class="small">N='.(count($gbt)-2).'</span>';
		}
		echo '</th>';

		$n++;
	}
	if ($totonleft && !$hidepast) {
		//total totals
		if ($catfilter<0) {
			if (isset($gbt[0][3][0])) { //using points based
				echo '<th><span class="cattothdr">Total';
				if ($pointsln==1) {
					echo '<br/>';
				} else {
					echo '&nbsp;';
				}
				echo $gbt[0][3][$availshow].'&nbsp;pts</span></th>';
				echo '<th>%</th>';
				$n+=2;
			} else {
				echo '<th><span class="cattothdr">Weighted Total %</span></th>';
				$n++;
			}
		}
		if (count($gbt[0][2])>1 || $catfilter!=-1) { //want to show cat headers?
			for ($i=0;$i<count($gbt[0][2]);$i++) { //category headers
				if (($availshow<2 || $availshow==3) && $gbt[0][2][$i][2]>1) {
					continue;
				} else if ($availshow==2 && $gbt[0][2][$i][2]==3) {
					continue;
				}
				echo '<th class="cat'.$gbt[0][2][$i][1].'"><span class="cattothdr">';
				echo $gbt[0][2][$i][0];
				if ($pointsln==1) {
					echo '<br/>';
				} else {
					echo '&nbsp;';
				}
				if ($availshow<3) {
					if (isset($gbt[0][3][0])) { //using points based
						echo $gbt[0][2][$i][3+$availshow].'&nbsp;', _('pts');
					} else {
						echo $gbt[0][2][$i][11].'%';
					}
				} else {
					if (isset($gbt[0][2][$i][11])) {
						echo $gbt[0][2][$i][11].'%';
					}
				}
				echo '</span></th>';
				$n++;
			}
		}

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
			if ($pointsln==1) {
					echo '<br/>';
				} else {
					echo '&nbsp;';
				}
			if ($gbt[0][1][$i][4]==0 || $gbt[0][1][$i][4]==3) {
				echo $gbt[0][1][$i][2].' (Not Counted)';
			} else {
				echo $gbt[0][1][$i][2].'&nbsp;pts';
				if ($gbt[0][1][$i][4]==2) {
					echo ' (EC)';
				}
			}
			if ($gbt[0][1][$i][5]==1 && $gbt[0][1][$i][6]==0) {
				echo ' (PT)';
			}

			echo '</th>';
			$n++;
			if ($commentloc==0) {
				echo '<th>'. $gbt[0][1][$i][0].': Comments'.'</th>';
				$n++;
			}
			if ($includetimes>0 && $gbt[0][1][$i][6]==0) {
				if ($includetimes==1) {
					echo '<th>'. $gbt[0][1][$i][0].': Time spent'.'</th>';
				} else if ($includetimes==2) {
					echo '<th>'. $gbt[0][1][$i][0].': Time spent in questions'.'</th>';
				}
				$n++;
			}
		}
	}
	if (!$totonleft && !$hidepast) {
		if (count($gbt[0][2])>1 || $catfilter!=-1) { //want to show cat headers?
			for ($i=0;$i<count($gbt[0][2]);$i++) { //category headers
				if (($availshow<2 || $availshow==3) && $gbt[0][2][$i][2]>1) {
					continue;
				} else if ($availshow==2 && $gbt[0][2][$i][2]==3) {
					continue;
				}
				echo '<th class="cat'.$gbt[0][2][$i][1].'"><span class="cattothdr">';
				echo $gbt[0][2][$i][0];
				if ($pointsln==1) {
					echo '<br/>';
				} else {
					echo '&nbsp;';
				}
				if ($availshow<3) {
					if (isset($gbt[0][3][0])) { //using points based
						echo $gbt[0][2][$i][3+$availshow].'&nbsp;', _('pts');
					} else {
						echo $gbt[0][2][$i][11].'%';
					}
				} else {
					if (isset($gbt[0][2][$i][11])) {
						echo $gbt[0][2][$i][11].'%';
					}
				}
				echo '</span></th>';
				$n++;
			}
		}
		//total totals
		if ($catfilter<0) {
			if (isset($gbt[0][3][0])) { //using points based
				echo '<th><span class="cattothdr">Total';
				if ($pointsln==1) {
					echo '<br/>';
				} else {
					echo '&nbsp;';
				}
				echo $gbt[0][3][$availshow].'&nbsp;pts</span></th>';
				echo '<th>%</th>';
				$n+=2;
			} else {
				echo '<th><span class="cattothdr">Weighted Total %</span></th>';
				$n++;
			}
		}
	}
	echo '<th>Comment</th>';
	echo '<th>Instructor Note</th>';

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
				$n++;
			}
		}
	}
	echo '</tr></thead><tbody>';
	//get gb comments;
	$gbcomments = array();
	//DB $query = "SELECT userid,gbcomment,gbinstrcomment FROM imas_students WHERE courseid='$cid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB while ($row = mysql_fetch_row($result)) {
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
		for ($j=1;$j<count($gbt[0][0]);$j++) {
			echo '<td class="c">'.$gbt[$i][0][$j].'</td>';
		}
		if ($totonleft && !$hidepast) {
			//total totals
			if ($catfilter<0) {
				if ($availshow==3) {
					if (isset($gbt[$i][3][8])) { //using points based
						echo '<td class="c">'.$insdiv.$gbt[$i][3][6].'/'.$gbt[$i][3][7].$enddiv.'</td>';
						echo '<td class="c">'.$insdiv.$gbt[$i][3][8] .'%'.$enddiv .'</td>';

					} else {
						echo '<td class="c">'.$insdiv.$gbt[$i][3][6].'%'.$enddiv .'</td>';
					}
				} else {
					if (isset($gbt[0][3][0])) { //using points based
						echo '<td class="c">'.$gbt[$i][3][$availshow].'</td>';
						echo '<td class="c">'.$gbt[$i][3][$availshow+3] .'%</td>';
					} else {
						echo '<td class="c">'.$gbt[$i][3][$availshow].'%</td>';
					}
				}
			}
			//category totals
			if (count($gbt[0][2])>1 || $catfilter!=-1) { //want to show cat headers?
				for ($j=0;$j<count($gbt[0][2]);$j++) { //category headers
					if (($availshow<2 || $availshow==3) && $gbt[0][2][$j][2]>1) {
						continue;
					} else if ($availshow==2 && $gbt[0][2][$j][2]==3) {
						continue;
					}
					if ($catfilter!=-1 && $availshow<3 && $gbt[0][2][$j][$availshow+3]>0) {
						echo '<td class="c">'.$gbt[$i][2][$j][$availshow].' ('.round(100*$gbt[$i][2][$j][$availshow]/$gbt[0][2][$j][$availshow+3])  .'%)</td>';
					} else {
						echo '<td class="c">';
						if ($availshow==3) {
							echo $gbt[$i][2][$j][3].' of '.$gbt[$i][2][$j][4];
						} else {
							if (isset($gbt[$i][3][8])) { //using points based
								echo $gbt[$i][2][$j][$availshow];
							} else {
								if ($gbt[0][2][$j][3+$availshow]>0) {
									echo round(100*$gbt[$i][2][$j][$availshow]/$gbt[0][2][$j][3+$availshow],1).'%';
								} else {
									echo '0%';
								}
							}
						}
						echo '</td>';
					}
				}
			}
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
				if (isset($gbt[$i][1][$j][5]) && ($gbt[$i][1][$j][5]&(1<<$availshow)) && !$hidepast) {
					echo '<sub>d</sub></span>';
				}
				echo '</td>';
				if ($commentloc==0) {
					if (isset($gbt[$i][1][$j][1])) {
						echo '<td>'.$gbt[$i][1][$j][1].'</td>';
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
			//category totals
			if (count($gbt[0][2])>1 || $catfilter!=-1) { //want to show cat headers?
				for ($j=0;$j<count($gbt[0][2]);$j++) { //category headers
					if (($availshow<2 || $availshow==3) && $gbt[0][2][$j][2]>1) {
						continue;
					} else if ($availshow==2 && $gbt[0][2][$j][2]==3) {
						continue;
					}
					if ($catfilter!=-1 && $availshow<3 && $gbt[0][2][$j][$availshow+3]>0) {
						echo '<td class="c">'.$gbt[$i][2][$j][$availshow].' ('.round(100*$gbt[$i][2][$j][$availshow]/$gbt[0][2][$j][$availshow+3])  .'%)</td>';
					} else {
						echo '<td class="c">';
						if ($availshow==3) {
							echo $gbt[$i][2][$j][3].' of '.$gbt[$i][2][$j][4];
						} else {
							if (isset($gbt[$i][3][8])) { //using points based
								echo $gbt[$i][2][$j][$availshow];
							} else {
								if ($gbt[0][2][$j][3+$availshow]>0) {
									echo round(100*$gbt[$i][2][$j][$availshow]/$gbt[0][2][$j][3+$availshow],1).'%';
								} else {
									echo '0%';
								}
							}
						}
						echo '</td>';
					}
				}
			}

			//total totals
			if ($catfilter<0) {
				if ($availshow==3) {
					if (isset($gbt[$i][3][8])) { //using points based
						echo '<td class="c">'.$insdiv.$gbt[$i][3][6].'/'.$gbt[$i][3][7].$enddiv.'</td>';
						echo '<td class="c">'.$insdiv.$gbt[$i][3][8] .'%'.$enddiv .'</td>';

					} else {
						echo '<td class="c">'.$insdiv.$gbt[$i][3][6].'%'.$enddiv .'</td>';
					}
				} else {
					if (isset($gbt[0][3][0])) { //using points based
						echo '<td class="c">'.$gbt[$i][3][$availshow].'</td>';
						echo '<td class="c">'.$gbt[$i][3][$availshow+3] .'%</td>';
					} else {
						echo '<td class="c">'.$gbt[$i][3][$availshow].'%</td>';
					}
				}
			}
		}
		if (isset($gbcomments[$gbt[$i][4][0]])) {
			echo '<td>' . Sanitize::encodeStringForDisplay($gbcomments[$gbt[$i][4][0]][0]) . '</td>';
			echo '<td>' . Sanitize::encodeStringForDisplay($gbcomments[$gbt[$i][4][0]][1]) . '</td>';
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
						echo '<td>'.$gbt[$i][1][$j][1].'</td>';
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
