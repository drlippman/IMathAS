<?php

require("../config.php");

if (empty($_GET['badgeid'])) {
	exit;
}
//verify a badge
$badgeid = intval($_GET['badgeid']);

if (!empty($_GET['userid'])) {
	$userid = intval($_GET['userid']);
	$query = "SELECT SID FROM imas_users WHERE id='$userid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	if (mysql_num_rows($result)>0) {
		$s = mysql_result($result,0,0);
		if (empty($_GET['v']) || $_GET['v'] != hash('sha256', $s . $userid)) {
			$userid = 0;
		}
	} else {
		$userid = 0;
	}
} else {
	$userid = 0;
}

$query = "SELECT courseid, name, badgetext, description, longdescription, requirements FROM imas_badgesettings WHERE id=$badgeid";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
if (mysql_num_rows($result)==0) { 
	echo "Invalid Badge";
	exit;
} else {
	list($cid, $name, $badgetext, $descr, $longdescr, $req) = mysql_fetch_row($result);
	$req = unserialize($req);
	if ($userid==0) {//this is a criteria request
		if ($_GET['format']=='json') {
			header('Content-Type: application/json');
			echo '{}';
			exit;
		}
		list($reqnameout,$reqout,$stuout,$metout) = validatebadge($badgeid, $cid, $req, 0);
		print_html($badgetext, $name, $descr, $longdescr, $reqnameout, $reqout);
	} else { //student specific
		$query = "SELECT id FROM imas_students WHERE courseid=$cid AND userid=$userid";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_num_rows($result)==0) {  //no longer in the student records - go to imas_badgerecords for backup
			$query = "SELECT data FROM imas_badgerecords WHERE userid=$userid AND badgeid=$badgeid";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_fetch_row($result)==0) {  //no records.  Uh oh!
				exit;
			} else {
				$data = unserialize(mysql_result($result,0,0));
				//if ($_GET['format']=='json') {
					print_assertation($cid, $badgetext, $name, $descr, $userid, $data[5]);
				//} else {
				///	print_html($badgetext, $name, $descr, $longdescr, $data[0], $data[1], $data[2], $data[3], $data[4], $data[5]);
				//}
			}
		} else {
			//still is a student - pull data
			if ($data = validatebadge($badgeid, $cid, $req, $userid)) {
				//if ($_GET['format']=='json') {
					print_assertation($cid, $badgetext, $name, $descr, $userid, $data[5]);
				//} else {
				//	print_html($badgetext, $name, $descr, $longdescr, $data[0], $data[1], $data[2], $data[3], $data[4], $data[5]);
				//}
			} else {
				exit;
			}
		}
	
	}
}
function print_html($badgetext, $name, $descr, $longdescr, $reqnameout, $reqout, $stuout=null, $metout=null, $stuname=null, $email=null) {
	global $installname, $imasroot;
	$coursetheme = "default.css";
	require("../header.php");
	echo '<h2>Badge: '.$name.'</h2>';
	if ($stuout != null) {
		echo '<h3>'.$stuname. ' ('.$email.')</h3>';
	}
	if ($descr!='') {echo '<p>'.$descr.'</p>';}
	if ($longdescr!='') {echo '<p>'.$longdescr.'</p>';}
	
	echo '<table style="margin-top: 10px;" class="gb"><thead><tr><th>Category/Course Total</th><th>Score Required</th>';
	if ($stuout != null) {
		echo '<th>Your Score</th><th>Requirement Met</th>';
	}
	echo '</tr></thead><tbody>';
	foreach ($reqnameout as $i=>$n) {
		echo '<tr><td>'.$n.'</td><td>'.$reqout[$i];
		if ($stuout != null) {
			echo '<td>'.$stuout[$i].'</td><td>'.$metout[$i];
		} 
		echo '</td></tr>';
	}
	echo '</tbody></table>';
	require("../footer.php");
	
}

function print_assertation($cid, $badgetext, $badgename, $descr, $userid, $email) {
	global $badgeid, $installname, $imasroot;
	header('Content-Type: application/json');
	if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') {
		$urlmode = 'https://';
	} else {
		$urlmode = 'http://';
	}
	$urlbase = $urlmode . $_SERVER['HTTP_HOST'];
	$salt = generateSalt();
	$hash = 'sha256$'.hash('sha256', $email . $salt);
	
	$bs = substr($badgetext, 0, 7);
	if ($bs=='http://' || $bs=='https:/') {
		$img = $badgetext;
	} else {
		$img = "$imasroot/img/badge.php?text=".urlencode($badgetext);
	}
	
	/*$query = "SELECT imas_courses.name AS cname, imas_users.LastName, imas_users.FirstName, imas_users.email, imas_groups.name FROM imas_courses JOIN imas_teachers ON imas_courses.id=imas_teachers.courseid ";
	$query .= "JOIN imas_users ON imas_teachers.userid=imas_users.id LEFT JOIN imas_groups ON imas_users.groupid=imas_groups.id WHERE imas_courses.id='$cid' LIMIT 1";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	if (mysql_num_rows($result)==0) {
		$org = ' ';
		$email = ' ';
	} else {
		$t = array();
		$e = array();
		$cname = '';
		while ($row = mysql_fetch_row($result)) {
			$cname = $row[0];
			if ($row[4]==null) {
				$t[] = $row[1].', '.$row[0];
			} else {
				$t[0] = $row[1].', '.$row[2] . ' ('.$row[4].')';
			}
			$e[] = $row[3];
		}
		$org = 'Course: '.$cname.'. Instructor'.((count($t)>1)?'s':'').': '.implode(', ',$t);
		$contact = implode(', ', $e);
	}*/
		
	echo <<<END
{
	"recipient": "$hash",
	"salt": "$salt",
	"badge": {
		"version": "0.5.0",
		"name": "$badgename",
		"image": "$img",
		"description": "$descr",
		"criteria": "$imasroot/course/verifybadge.php?badgeid=$badgeid",
		"issuer": {
			"origin": "$urlbase",
			"name": "$installname"
		}
	}
}		
END;
//  can't include because of FERPA :(  
// "evidence": "$imasroot/course/verifybadge.php?badgeid=$badgeid&userid=$userid",
//
//too long, so don't bother
// 			"org": "$org",
//			"email": "$contact"

}

function generateSalt($max = 15) {
        $characterList = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*?";
        $i = 0;
        $salt = "";
        while ($i < $max) {
            $salt .= $characterList{mt_rand(0, (strlen($characterList) - 1))};
            $i++;
        }
        return $salt;
}

function validatebadge($badgeid, $cid, $req, $userid=0) {
	//get student's scores
	global $secfilter;
	global $canviewall;
	if (isset($userid) && $userid!=0) {
		require("gbtable2.php");
		$secfilter = -1;
		$canviewall = true;
		$gbt = gbtable($userid);
	}
	
	$query = "SELECT id,name FROM imas_gbcats WHERE courseid='$cid' ORDER BY name";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$gtypes = array('0'=>'Past Due', '3'=>'Past and Attempted', '1'=>'Past and Available', '2'=>'All Items'); 
	$gbcats = array();
	
	while ($row = mysql_fetch_row($result)) {
		$gbcats[$row[0]] = $row[1];
	}
	$reqnameout = array();
	$reqout = array();
	$stuout = array();
	$metout = array();
	$reqmet = true;
	foreach ($req['data'] as $r) {  //r = array(gbcat, gradetype, score)
		$metthis = false;
		if ($r[0]>0) {//is a category total
			$reqnameout[] =  $gbcats[$r[0]] . ' ('.$gtypes[$r[1]].')';
		} else {
			$reqnameout[] = 'Course Total ('.$gtypes[$r[1]].')';
		}
		$reqout[] = $r[2].'%';
		if (isset($userid) && $userid!=0) {
			if ($r[0]>0) {//is a category total
				foreach ($gbt[0][2] as $i=>$catinfo) {
					if ($catinfo[10]==$r[0]) { //found category
						if ($r[1]==3) {
							$mypercent = round(100*$gbt[1][2][$i][3]/$gbt[1][2][$i][4],1);
						} else {
							if ($catinfo[$r[1]+3]==0) { 
								$mypercent= 0;
							} else {
								$mypercent = round(100*$gbt[1][2][$i][$r[1]]/$catinfo[$r[1]+3],1);
							}
						}
						$stuout[] =  $mypercent.'%';
						if ($mypercent>=$r[2]) {
							$metthis = true;
						}
					}
				}
			} else { //is a course total
				if ($r[1]==3) { //past and attempted
					if ($gbt[1][3][8]==null) {
						$mypercent = $gbt[1][3][6];
					} else {
						$mypercent = $gbt[1][3][8];
					}
				} else {
					if ($gbt[1][3][3+$r[1]]==null) {
						$mypercent = $gbt[1][3][$r[1]];
					} else {
						$mypercent = $gbt[1][3][3+$r[1]];
					}
				}
				$stuout[] =  $mypercent.'%';
				if ($mypercent>=$r[2]) {
					$metthis = true;
				}
			}
			if ($metthis==true) {
				$metout[] = 'Yes!';
			} else {
				$metout[] = 'No';
				$reqmet = false;
			}
		}
		
	}
	
	if ($reqmet) {
		if (isset($userid) && $userid!=0) {
			$query = "SELECT FirstName, LastName, email FROM imas_users WHERE id=$userid";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$row = mysql_fetch_row($result);
			$stuname = $row[1]. ', '.$row[0];
			$email = $row[2];
			$data = array($reqnameout, $reqout, $stuout, $metout, $stuname, $email);
			
			$data = addslashes(serialize($data));
			$query = "UPDATE imas_badgerecords SET data='$data' WHERE badgeid='$badgeid' AND userid='$userid'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_affected_rows()==0) {//no existing record
				$query = "INSERT INTO imas_badgerecords (badgeid,userid,data) VALUES ('$badgeid','$userid','$data')";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
		} else {
			$stuname = '';
			$email = '';
		}
		
		return array($reqnameout,$reqout,$stuout,$metout, $stuname, $email);
	} else {
		return false;
	}
}

?>
