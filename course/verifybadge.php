<?php

require("../init_without_validate.php");


if (empty($_GET['badgeid'])) {
	exit;
}
//verify a badge
$badgeid = intval($_GET['badgeid']);

if (!empty($_GET['userid'])) {
	$userid = intval($_GET['userid']);
	//DB $query = "SELECT SID FROM imas_users WHERE id='$userid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB if (mysql_num_rows($result)>0) {
		//DB $s = mysql_result($result,0,0);
	$stm = $DBH->prepare("SELECT SID FROM imas_users WHERE id=:id");
	$stm->execute(array(':id'=>$userid));
	if ($stm->rowCount()>0) {
		$s = $stm->fetchColumn(0);
		if (empty($_GET['v']) || $_GET['v'] != hash('sha256', $s . $userid)) {
			$userid = 0;
		}
	} else {
		$userid = 0;
	}
} else {
	$userid = 0;
}

//DB $query = "SELECT courseid, name, badgetext, description, longdescription, requirements FROM imas_badgesettings WHERE id=$badgeid";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB if (mysql_num_rows($result)==0) {
$stm = $DBH->prepare("SELECT courseid, name, badgetext, description, longdescription, requirements FROM imas_badgesettings WHERE id=:id");
$stm->execute(array(':id'=>$badgeid));
if ($stm->rowCount()==0) {
	echo "Invalid Badge";
	exit;
} else {
	//DB list($cid, $name, $badgetext, $descr, $longdescr, $req) = mysql_fetch_row($result);
	list($cid, $name, $badgetext, $descr, $longdescr, $req) = $stm->fetch(PDO::FETCH_NUM);
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
		//DB $query = "SELECT id FROM imas_students WHERE courseid=$cid AND userid=$userid";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB if (mysql_num_rows($result)==0) {
		$stm = $DBH->prepare("SELECT id FROM imas_students WHERE courseid=:courseid AND userid=:userid");
		$stm->execute(array(':courseid'=>$cid, ':userid'=>$userid));
		if ($stm->rowCount()==0) {
			//DB $query = "SELECT data FROM imas_badgerecords WHERE userid=$userid AND badgeid=$badgeid";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB if (mysql_fetch_row($result)==0) {
			$stm = $DBH->prepare("SELECT data FROM imas_badgerecords WHERE userid=:userid AND badgeid=:badgeid");
			$stm->execute(array(':userid'=>$userid, ':badgeid'=>$badgeid));
			if ($stm->fetch(PDO::FETCH_NUM)==0) {
				exit;
			} else {
				//DB $data = unserialize(mysql_result($result,0,0));
				$data = unserialize($stm->fetchColumn(0));
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
	echo '<h1>Badge: '.Sanitize::encodeStringForDisplay($name).'</h1>';
	if ($stuout != null) {
		echo '<h2>'.$stuname. ' ('.$email.')</h2>';
	}
	if ($descr!='') {echo '<p>'.Sanitize::encodeStringForDisplay($descr).'</p>';}
	if ($longdescr!='') {echo '<p>'.Sanitize::encodeStringForDisplay($longdescr).'</p>';}

	echo '<table style="margin-top: 10px;" class="gb"><thead><tr><th>Category/Course Total</th><th>Score Required</th>';
	if ($stuout != null) {
		echo '<th>Your Score</th><th>Requirement Met</th>';
	}
	echo '</tr></thead><tbody>';
	foreach ($reqnameout as $i=>$n) {
		echo '<tr><td>'.Sanitize::encodeStringForDisplay($n).'</td><td>'.Sanitize::encodeStringForDisplay($reqout[$i]);
		if ($stuout != null) {
			echo '<td>'.Sanitize::encodeStringForDisplay($stuout[$i]).'</td><td>'.Sanitize::encodeStringForDisplay($metout[$i]);
		}
		echo '</td></tr>';
	}
	echo '</tbody></table>';
	require("../footer.php");

}

function print_assertation($cid, $badgetext, $badgename, $descr, $userid, $email) {
	global $badgeid, $installname, $imasroot;
	header('Content-Type: application/json');
	if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=='https'))  {
		$urlmode = 'https://';
	} else {
		$urlmode = 'http://';
	}
	$urlbase = $urlmode . Sanitize::domainNameWithPort($_SERVER['HTTP_HOST']);
	$salt = generateSalt();
	$hash = 'sha256$'.hash('sha256', $email . $salt);

	$bs = substr($badgetext, 0, 7);
	if ($bs=='http://' || $bs=='https:/') {
		$img = $badgetext;
	} else {
		$img = "$imasroot/img/badge.php?text=".Sanitize::encodeUrlParam($badgetext);
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
	global $DBH;
	global $secfilter;
	global $canviewall;
	if (isset($userid) && $userid!=0) {
		require("gbtable2.php");
		$secfilter = -1;
		$canviewall = true;
		$gbt = gbtable($userid);
	}

	//DB $query = "SELECT id,name FROM imas_gbcats WHERE courseid='$cid' ORDER BY name";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	$stm = $DBH->prepare("SELECT id,name FROM imas_gbcats WHERE courseid=:courseid ORDER BY name");
	$stm->execute(array(':courseid'=>$cid));
	$gtypes = array('0'=>'Past Due', '3'=>'Past and Attempted', '1'=>'Past and Available', '2'=>'All Items');
	$gbcats = array();

	//DB while ($row = mysql_fetch_row($result)) {
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
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
			//DB $query = "SELECT FirstName, LastName, email FROM imas_users WHERE id=$userid";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $row = mysql_fetch_row($result);
			$stm = $DBH->prepare("SELECT FirstName, LastName, email FROM imas_users WHERE id=:id");
			$stm->execute(array(':id'=>$userid));
			$row = $stm->fetch(PDO::FETCH_NUM);
			$stuname = $row[1]. ', '.$row[0];
			$email = $row[2];
			$data = array($reqnameout, $reqout, $stuout, $metout, $stuname, $email);

			//DB $data = addslashes(serialize($data));
			$data = serialize($data);
			//DB $query = "UPDATE imas_badgerecords SET data='$data' WHERE badgeid='$badgeid' AND userid='$userid'";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			//DB if (mysql_affected_rows()==0) {
			$stm = $DBH->prepare("UPDATE imas_badgerecords SET data=:data WHERE badgeid=:badgeid AND userid=:userid");
			$stm->execute(array(':data'=>$data, ':badgeid'=>$badgeid, ':userid'=>$userid));
			if ($stm->rowCount()==0) {
				//DB $query = "INSERT INTO imas_badgerecords (badgeid,userid,data) VALUES ('$badgeid','$userid','$data')";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				$stm = $DBH->prepare("INSERT INTO imas_badgerecords (badgeid,userid,data) VALUES (:badgeid, :userid, :data)");
				$stm->execute(array(':badgeid'=>$badgeid, ':userid'=>$userid, ':data'=>$data));
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
