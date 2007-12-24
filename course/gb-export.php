<?php
//IMathAS:  Export or email Gradebook
//(c) 2007 David Lippman
	require("../validate.php");
	$isteacher = isset($teacherid);
	$cid = $_GET['cid'];
	if (!$isteacher) {
		echo "This page not available to students";
		exit;
	}
	if (isset($sessiondata[$cid.'gbmode'])) {
		$gbmode =  $sessiondata[$cid.'gbmode'];
	} else {
		$query = "SELECT defgbmode FROM imas_gbscheme WHERE courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$gbmode = mysql_result($result,0,0);
	}
	if (isset($_GET['stu']) && $_GET['stu']!='') {
		$stu = $_GET['stu'];
	} else {
		$stu = 0;
	}
	
	$catfilter = -1;
	$secfilter = -1;
	
	//Gbmode : Links NC Dates
	$totonleft = floor($gbmode/1000)%10 ; //0 right, 1 left
	$links = floor($gbmode/100)%10; //0: view/edit, 1 q breakdown
	$hidenc = floor($gbmode/10)%10; //0: show all, 1 stu visisble (cntingb not 0), 2 hide all (cntingb 1 or 2)
	$availshow = $gbmode%10; //0: past, 1 past&cur, 2 all

	require("gbtable2.php");
	$gb = gbinstrexport();
	if (isset($_GET['export']) && $_GET['export']=="true") {
		header('Content-type: text/csv');
		header("Content-Disposition: attachment; filename=\"gradebook-$cid.csv\"");
		foreach ($gb as $gbline) {
			$line = '';
			foreach ($gbline as $val) {
				 # remove any windows new lines, as they interfere with the parsing at the other end 
				  $val = str_replace("\r\n", "\n", $val); 
				  $val = str_replace("\n", " ", $val);
				  $val = str_replace(array("<BR>",'<br>','<br/>'), ' ',$val);
				  $val = str_replace("&nbsp;"," ",$val);
			
				  # if a deliminator char, a double quote char or a newline are in the field, add quotes 
				  if(ereg("[\,\"\n\r]", $val)) { 
					  $val = '"'.str_replace('"', '""', $val).'"'; 
				  }
				  $line .= $val.',';
			}
			# strip the last deliminator 
			$line = substr($line, 0, -1); 
			$line .= "\n";
			echo $line;
		}
		exit;
	} 
	if (isset($_GET['emailgb'])) {
		if ($_GET['emailgb']=="ask") {
			if (isset($_POST['email'])) {
				$_GET['emailgb'] = $_POST['email'];
			} else {
				echo "<html><body><form method=post action=\"gb-export.php?stu=$stu&cid=$cid&emailgb=ask\">";
				echo "Email Gradebook To: <input type=text name=\"email\" /> <input type=submit value=\"Email\"/>";
				echo "</form></body></html>";
				exit;
			}
		}
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
				  if(ereg("[\,\"\n\r]", $val)) { 
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
			$query = "SELECT email FROM imas_users WHERE id='$userid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$_GET['emailgb'] = mysql_result($result,0,0);
		}
		if ($_GET['emailgb']!='') {
			mail($_GET['emailgb'], "Gradebook for $coursename", $message, $headers);
			require("../header.php");
			echo "Gradebook Emailed.  <a href=\"gradebook.php?cid=$cid\">Return to Gradebook</a>";
			require("../footer.php");
			exit;
		}
		
	}
	
	
	
	
function gbinstrexport() {
	global $hidenc,$nopt,$isteacher,$cid,$gbmode,$stu,$availshow,$isdiag,$catfilter,$secfilter,$totonleft;
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
				if ($availshow<2 && $gbt[0][2][$i][2]>1) {
					continue;
				} else if ($availshow==2 && $gbt[0][2][$i][2]==3) {
					continue;
				}
				$gbo[0][$n] = $gbt[0][2][$i][0].': '.$gbt[0][2][$i][3+$availshow].' pts';
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
		}
	}
	if (!$totonleft) {
		//total totals
		if (count($gbt[0][2])>1 || $catfilter!=-1) { //want to show cat headers?
			for ($i=0;$i<count($gbt[0][2]);$i++) { //category headers	
				if ($availshow<2 && $gbt[0][2][$i][2]>1) {
					continue;
				} else if ($availshow==2 && $gbt[0][2][$i][2]==3) {
					continue;
				}
				$gbo[0][$n] = $gbt[0][2][$i][0].': '.$gbt[0][2][$i][3+$availshow].' pts';
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
			//category totals
			if (count($gbt[0][2])>1 || $catfilter!=-1) { //want to show cat headers?
				for ($j=0;$j<count($gbt[0][2]);$j++) { //category headers	
					if ($availshow<2 && $gbt[0][2][$j][2]>1) {
						continue;
					} else if ($availshow==2 && $gbt[0][2][$j][2]==3) {
						continue;
					}
					$gbo[$i][$n] = $gbt[$i][2][$j][$availshow];
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
			}
		}
		if (!$totonleft) {
			//category totals
			if (count($gbt[0][2])>1 || $catfilter!=-1) { //want to show cat headers?
				for ($j=0;$j<count($gbt[0][2]);$j++) { //category headers	
					if ($availshow<2 && $gbt[0][2][$j][2]>1) {
						continue;
					} else if ($availshow==2 && $gbt[0][2][$j][2]==3) {
						continue;
					}
					$gbo[$i][$n] = $gbt[$i][2][$j][$availshow];
					$n++;
				}
			}
			//total totals
			if ($catfilter<0) {
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
	return $gbo;
}
?>
