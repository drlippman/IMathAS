<?php
//IMathAS:  Copy Course Items
//(c) 2006 David Lippman

//boost operation time
@set_time_limit(0);
ini_set("max_execution_time", "600");

/*** master php includes *******/
require("../init.php");
require("../includes/copyiteminc.php");
require("../includes/htmlutil.php");

/*** pre-html data manipulation, including function code *******/

 //set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Copy Course Items";
$cidLookUp = Sanitize::onlyInt($_POST['cidlookup']);
$ctc = Sanitize::onlyInt($_POST['ctc']);

$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=" .Sanitize::courseId($_GET['cid']). "\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; Copy Course Items";

	// SECURITY CHECK DATA PROCESSING
if (!(isset($teacherid))) {
	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} else {

	$cid = Sanitize::courseId($_GET['cid']);
	$oktocopy = 1;

	if (!empty($cidLookUp)) {
		$query = "SELECT ic.id,ic.name,ic.enrollkey,ic.copyrights,ic.termsurl,iu.groupid,iu.LastName,iu.FirstName FROM imas_courses AS ic ";
		$query .= "JOIN imas_users AS iu ON ic.ownerid=iu.id WHERE ic.id=:id";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':id'=>$cidLookUp));
		if ($stm->rowCount()==0) {
			echo '{}';
		} else {
			$row = $stm->fetch(PDO::FETCH_ASSOC);
			$out = array(
				"id"=>Sanitize::onlyInt($row['id']), 
				"name"=>Sanitize::encodeStringForDisplay($row['name'] . ' ('.$row['LastName'].', '.$row['FirstName'].')'),
				"termsurl"=>Sanitize::url($row['termsurl']));
			$out['needkey'] = !($row['copyrights'] == 2 || ($row['copyrights'] == 1 && $row['groupid']==$groupid));
			echo json_encode($out);
		}
		exit;
	} else if (isset($_GET['action'])) {
		//DB $query = "SELECT imas_courses.id FROM imas_courses,imas_teachers WHERE imas_courses.id=imas_teachers.courseid";
		//DB $query .= " AND imas_teachers.userid='$userid' AND imas_courses.id='{$_POST['ctc']}'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB if (mysql_num_rows($result)==0) {
		$query = "SELECT imas_courses.id FROM imas_courses,imas_teachers WHERE imas_courses.id=imas_teachers.courseid";
		$query .= " AND imas_teachers.userid=:userid AND imas_courses.id=:id";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':userid'=>$userid, ':id'=>$_POST['ctc']));
		if ($stm->rowCount()==0) {
			//DB $query = "SELECT enrollkey,copyrights,termsurl FROM imas_courses WHERE id='{$_POST['ctc']}'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("SELECT enrollkey,copyrights,termsurl FROM imas_courses WHERE id=:id");
			$stm->execute(array(':id'=>$_POST['ctc']));
			list($ekey, $copyrights, $termsurl) = $stm->fetch(PDO::FETCH_NUM);
			//DB $copyrights = mysql_result($result,0,1)*1;
			if ($copyrights<2) {
				$oktocopy = 0;
				if ($copyrights==1) {
					//DB $query = "SELECT imas_users.groupid FROM imas_courses,imas_users,imas_teachers WHERE imas_courses.id=imas_teachers.courseid ";
					//DB $query .= "AND imas_teachers.userid=imas_users.id AND imas_courses.id='{$_POST['ctc']}'";
					//DB $r2 = mysql_query($query) or die("Query failed : " . mysql_error());
					//DB while ($row = mysql_fetch_row($r2)) {
					$query = "SELECT imas_users.groupid FROM imas_courses,imas_users,imas_teachers WHERE imas_courses.id=imas_teachers.courseid ";
					$query .= "AND imas_teachers.userid=imas_users.id AND imas_courses.id=:id";
					$stm2 = $DBH->prepare($query);
					$stm2->execute(array(':id'=>$_POST['ctc']));
					while ($row = $stm2->fetch(PDO::FETCH_NUM)) {
						if ($row[0]==$groupid) {
							$oktocopy=1;
							break;
						}
					}
				}
				if ($oktocopy==0) {
					//DB $ekey = mysql_result($result,0,0);
					if (!isset($_POST['ekey']) || strtolower(trim($ekey)) != strtolower(trim($_POST['ekey']))) {
						$overwriteBody = 1;
						$body = "Invalid enrollment key entered.  <a href=\"copyitems.php?cid=$cid\">Try Again</a>";
					} else {
						$oktocopy = 1;
					}
				}
			}
		}
		//DB $query = "SELECT termsurl FROM imas_courses WHERE id='{$_POST['ctc']}'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $termsurl = mysql_result($result,0,0);
		if ($termsurl != '' && $_GET['action']=="select") {
			if (!isset($_POST['termsagree'])) {
				$oktocopy = 0;
				$overwriteBody = 1;
				$body = "Must agree to course terms of use to copy it.  <a href=\"copyitems.php?cid=$cid\">Try Again</a>";
			} else {
				$now = time();
				$ctc = intval($_POST['ctc']);
				$userid = intval($userid);
				//DB $query = "INSERT INTO imas_log (time,log) VALUES($now,'User $userid agreed to terms of use on course $cid')";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				$stm = $DBH->prepare("INSERT INTO imas_log (time,log) VALUES(:time, :log)");
				$stm->execute(array(':time'=>$now, ':log'=>"User $userid agreed to terms of use on course $cid"));
			}
		}
	}
	if ($oktocopy == 1) {
		if (isset($_GET['action']) && $_GET['action']=="copycalitems") {
			if (isset($_POST['clearexisting'])) {
				//DB $query = "DELETE FROM imas_calitems WHERE courseid='$cid'";
				//DB mysql_query($query) or die("Query failed :$query " . mysql_error());
				$stm = $DBH->prepare("DELETE FROM imas_calitems WHERE courseid=:courseid");
				$stm->execute(array(':courseid'=>$cid));
			}
			if (isset($_POST['checked']) && count($_POST['checked'])>0) {
				$checked = $_POST['checked'];
				$chklist = implode(',', array_map('intval',$checked));
				//DB $query = "SELECT date,tag,title FROM imas_calitems WHERE id IN ($chklist) AND courseid='{$_POST['ctc']}'";
				//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				$stm = $DBH->prepare("SELECT date,tag,title FROM imas_calitems WHERE id IN ($chklist) AND courseid=:courseid");
				$stm->execute(array(':courseid'=>$_POST['ctc']));
				$insarr = array();
				$qarr = array();
				//DB while ($row = mysql_fetch_row($result)) {
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					//DB $insarr[] = "('$cid','".implode("','",addslashes_deep($row))."')";
					$insarr[] = "(?,?,?,?)";
					array_push($qarr, $cid, $row[0], $row[1], $row[2]);
				}
				//DB $query = "INSERT INTO imas_calitems (courseid,date,tag,title) VALUES ";
				//DB $query .= implode(',',$insarr);
				//DB mysql_query($query) or die("Query failed :$query " . mysql_error());
				$query = "INSERT INTO imas_calitems (courseid,date,tag,title) VALUES ";
				$query .= implode(',',$insarr);
				$stm = $DBH->prepare($query);
				$stm->execute($qarr);
			}
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=$cid&r=" . Sanitize::randomQueryStringParam());
			exit;
		} else if (isset($_GET['action']) && $_GET['action']=="copy") {
			if ($_POST['whattocopy']=='all') {
				/*$_POST['copycourseopt'] = 1;
				$_POST['copygbsetup'] = 1;
				$_POST['removewithdrawn'] = 1;
				$_POST['usereplaceby'] = 1;
				$_POST['copyrubrics'] = 1;
				$_POST['copyoutcomes'] = 1;
				$_POST['copystickyposts'] = 1;
				if (isset($_POST['copyofflinewhole'])) {
					$_POST['copyoffline'] = 1;
				}
				*/
				$_POST['addto'] = 'none';
				$_POST['append'] = '';
			}
			//DB mysql_query("START TRANSACTION") or die("Query failed :$query " . mysql_error());
			$DBH->beginTransaction();
			if (isset($_POST['copycourseopt'])) {
				$tocopy = 'ancestors,hideicons,allowunenroll,copyrights,msgset,picicons,showlatepass,theme,latepasshrs';
				//DB $query = "SELECT $tocopy FROM imas_courses WHERE id='{$_POST['ctc']}'";
				//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				//DB $row = mysql_fetch_row($result);
				$stm = $DBH->prepare("SELECT $tocopy FROM imas_courses WHERE id=:id");
				$stm->execute(array(':id'=>$ctc));
				$row = $stm->fetch(PDO::FETCH_ASSOC);
				$tocopyarr = explode(',',$tocopy);
				if ($row['ancestors']=='') {
					$row['ancestors'] = intval($ctc);
				} else {
					$row['ancestors'] = intval($ctc).','.$row['ancestors'];
				}
				if (isset($CFG['CPS']['theme']) && $CFG['CPS']['theme'][1]==0) {
					$row['theme'] = $defaultcoursetheme;
				} else if (isset($CFG['CPS']['themelist']) && strpos($CFG['CPS']['themelist'], $coursetheme)===false) {
					$row['theme'] = $defaultcoursetheme;
				}
				$sets = '';
				for ($i=0; $i<count($tocopyarr); $i++) {
					if ($i>0) {$sets .= ',';}
					//DB $sets .= $tocopyarr[$i] . "='" . addslashes($row[$i])."'";
					$sets .= $tocopyarr[$i] . "=:" . $tocopyarr[$i];
				}
				//DB $query = "UPDATE imas_courses SET $sets WHERE id='$cid'";
				//DB mysql_query($query) or die("Query failed :$query " . mysql_error());
				$stm = $DBH->prepare("UPDATE imas_courses SET $sets WHERE id=:id");
				$row[':id'] = $cid;
				$stm->execute($row);
			}
			if (isset($_POST['copygbsetup'])) {
				//DB $query = "SELECT useweights,orderby,defaultcat,defgbmode,stugbmode,colorize FROM imas_gbscheme WHERE courseid='{$_POST['ctc']}'";
				//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				//DB $row = mysql_fetch_row($result);
				$stm = $DBH->prepare("SELECT useweights,orderby,defaultcat,defgbmode,stugbmode,colorize FROM imas_gbscheme WHERE courseid=:courseid");
				$stm->execute(array(':courseid'=>$ctc));
				$row = $stm->fetch(PDO::FETCH_NUM);
				//DB $query = "UPDATE imas_gbscheme SET useweights='{$row[0]}',orderby='{$row[1]}',defaultcat='{$row[2]}',defgbmode='{$row[3]}',stugbmode='{$row[4]}',colorize='{$row[5]}' WHERE courseid='$cid'";
				//DB mysql_query($query) or die("Query failed :$query " . mysql_error());
				$stm = $DBH->prepare("UPDATE imas_gbscheme SET useweights=:useweights,orderby=:orderby,defaultcat=:defaultcat,defgbmode=:defgbmode,stugbmode=:stugbmode,colorize=:colorize WHERE courseid=:courseid");
				$stm->execute(array(':useweights'=>$row[0], ':orderby'=>$row[1], ':defaultcat'=>$row[2], ':defgbmode'=>$row[3], ':stugbmode'=>$row[4], ':colorize'=>$row[5], ':courseid'=>$cid));

				//DB $query = "SELECT id,name,scale,scaletype,chop,dropn,weight,hidden,calctype FROM imas_gbcats WHERE courseid='{$_POST['ctc']}'";
				//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				//DB while ($row = mysql_fetch_row($result)) {
				$gb_cat_src=null; $gb_cat_ins = null; $gb_cat_upd = null;
				$stm = $DBH->prepare("SELECT id,name,scale,scaletype,chop,dropn,weight,hidden,calctype FROM imas_gbcats WHERE courseid=:courseid");
				$stm->execute(array(':courseid'=>$ctc));
				while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
					//DB $query = "SELECT id FROM imas_gbcats WHERE courseid='$cid' AND name='{$row[1]}'";
					//DB $r2 = mysql_query($query) or die("Query failed :$query " . mysql_error());
					//DB if (mysql_num_rows($r2)==0) {
					if ($gb_cat_src===null) {
						$gb_cat_src = $DBH->prepare("SELECT id FROM imas_gbcats WHERE courseid=:courseid AND name=:name");
					}
					$gb_cat_src->execute(array(':courseid'=>$cid, ':name'=>$row['name']));
					if ($gb_cat_src->rowCount()==0) {
						//DB $query = "INSERT INTO imas_gbcats (courseid,name,scale,scaletype,chop,dropn,weight,hidden,calctype) VALUES ";
						//DB $frid = array_shift($row);
						//DB $irow = "'".implode("','",addslashes_deep($row))."'";
						//DB $query .= "('$cid',$irow)";
						//DB mysql_query($query) or die("Query failed :$query " . mysql_error());
						if ($gb_cat_ins===null) {
							$query = "INSERT INTO imas_gbcats (courseid,name,scale,scaletype,chop,dropn,weight,hidden,calctype) VALUES ";
							$query .= "(:courseid, :name, :scale, :scaletype, :chop, :dropn, :weight, :hidden, :calctype)";
							$gb_cat_ins = $DBH->prepare($query);
						}
						$gb_cat_ins->execute(array(':courseid'=>$cid, ':name'=>$row['name'], ':scale'=>$row['scale'], ':scaletype'=>$row['scaletype'],
							':chop'=>$row['chop'], ':dropn'=>$row['dropn'], ':weight'=>$row['weight'], ':hidden'=>$row['hidden'], ':calctype'=>$row['calctype']));

						//DB $gbcats[$frid] = mysql_insert_id();
						$gbcats[$row['id']] = $DBH->lastInsertId();
					} else {
						//DB $rpid = mysql_result($r2,0,0);
						$rpid = $gb_cat_src->fetchColumn(0);
						//DB $query = "UPDATE imas_gbcats SET scale='{$row[2]}',scaletype='{$row[3]}',chop='{$row[4]}',dropn='{$row[5]}',weight='{$row[6]}',hidden='{$row[7]}',calctype='{$row[8]}' ";
						//DB $query .= "WHERE id='$rpid'";
						if ($gb_cat_upd===null) {
							$query = "UPDATE imas_gbcats SET scale=:scale,scaletype=:scaletype,chop=:chop,dropn=:dropn,weight=:weight,hidden=:hidden,calctype=:calctype ";
							$query .= "WHERE id=:id";
							$gb_cat_upd = $DBH->prepare($query);
						}
						$gb_cat_upd->execute(array(':scale'=>$row['scale'], ':scaletype'=>$row['scaletype'], ':chop'=>$row['chop'], ':dropn'=>$row['dropn'],
							':weight'=>$row['weight'], ':hidden'=>$row['hidden'], ':calctype'=>$row['calctype'], ':id'=>$rpid));
						$gbcats[$row['id']] = $rpid;
					}
				}
			} else {
				$gbcats = array();
				//DB $query = "SELECT tc.id,toc.id FROM imas_gbcats AS tc JOIN imas_gbcats AS toc ON tc.name=toc.name WHERE tc.courseid='{$_POST['ctc']}' AND ";
				//DB $query .= "toc.courseid='$cid'";
				//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				//DB while ($row = mysql_fetch_row($result)) {
				$query = "SELECT tc.id,toc.id FROM imas_gbcats AS tc JOIN imas_gbcats AS toc ON tc.name=toc.name WHERE tc.courseid=:courseid AND ";
				$query .= "toc.courseid=:courseid2";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':courseid'=>$ctc, ':courseid2'=>$cid));
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					$gbcats[$row[0]] = $row[1];
				}
			}
			if (isset($_POST['copyoutcomes'])) {
				//load any existing outcomes
				$outcomes = array();
				//DB $query = "SELECT tc.id,toc.id FROM imas_outcomes AS tc JOIN imas_outcomes AS toc ON tc.name=toc.name WHERE tc.courseid='{$_POST['ctc']}' AND ";
				//DB $query .= "toc.courseid='$cid'";
				//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				//DB if (mysql_num_rows($result)>0) {
				$query = "SELECT tc.id,toc.id FROM imas_outcomes AS tc JOIN imas_outcomes AS toc ON tc.name=toc.name WHERE tc.courseid=:courseid AND ";
				$query .= "toc.courseid=:courseid2";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':courseid'=>$ctc, ':courseid2'=>$cid));
				if ($stm->rowCount()>0) {
					$hasoutcomes = true;
				} else {
					$hasoutcomes = false;
				}
				//DB while ($row = mysql_fetch_row($result)) {
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					$outcomes[$row[0]] = $row[1];
				}
				$newoutcomes = array();
				//DB $query = "SELECT id,name,ancestors FROM imas_outcomes WHERE courseid='{$_POST['ctc']}'";
				//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				//DB while ($row = mysql_fetch_row($result)) {
				$stm = $DBH->prepare("SELECT id,name,ancestors FROM imas_outcomes WHERE courseid=:courseid");
				$stm->execute(array(':courseid'=>$ctc));
				$out_ins_stm = null;
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					if (isset($outcomes[$row[0]])) { continue;}
					if ($row[2]=='') {
						$row[2] = $row[0];
					} else {
						$row[2] = $row[0].','.$row[2];
					}
					//DB $row[1] = addslashes($row[1]);
					//DB $query = "INSERT INTO imas_outcomes (courseid,name,ancestors) VALUES ";
					//DB $query .= "('$cid','{$row[1]}','{$row[2]}')";
					//DB mysql_query($query) or die("Query failed :$query " . mysql_error());
					//DB $outcomes[$row[0]] = mysql_insert_id();
					if ($out_ins_stm===null) {
						$query = "INSERT INTO imas_outcomes (courseid,name,ancestors) VALUES ";
						$query .= "(:courseid, :name, :ancestors)";
						$out_ins_stm = $DBH->prepare($query);
					}
					$out_ins_stm->execute(array(':courseid'=>$cid, ':name'=>$row[1], ':ancestors'=>$row[2]));
					$outcomes[$row[0]] = $DBH->lastInsertId();
					$newoutcomes[] = $outcomes[$row[0]];
				}

				if ($hasoutcomes) {
					//already has outcomes, so we'll just add to the end of the existing list new outcomes
					//DB $query = "SELECT outcomes FROM imas_courses WHERE id='$cid'";
					//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
					//DB $row = mysql_fetch_row($result);
					$stm = $DBH->prepare("SELECT outcomes FROM imas_courses WHERE id=:id");
					$stm->execute(array(':id'=>$cid));
					$row = $stm->fetch(PDO::FETCH_NUM);
					$outcomesarr = unserialize($row[0]);
					foreach ($newoutcomes as $o) {
						$outcomesarr[] = $o;
					}
				} else {
					//rewrite whole order
					//DB $query = "SELECT outcomes FROM imas_courses WHERE id='{$_POST['ctc']}'";
					//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
					//DB $row = mysql_fetch_row($result);
					$stm = $DBH->prepare("SELECT outcomes FROM imas_courses WHERE id=:id");
					$stm->execute(array(':id'=>$ctc));
					$row = $stm->fetch(PDO::FETCH_NUM);
					function updateoutcomes(&$arr) {
						global $outcomes;
						foreach ($arr as $k=>$v) {
							if (is_array($v)) {
								updateoutcomes($arr[$k]['outcomes']);
							} else {
								$arr[$k] = $outcomes[$v];
							}
						}
					}
					$outcomesarr = unserialize($row[0]);
					if (!is_array($outcomearr)) {
						$outcomearr = array();
					}
					updateoutcomes($outcomesarr);
				}
				//DB $newoutcomearr = addslashes(serialize($outcomesarr));
				$newoutcomearr = serialize($outcomesarr);
				//DB $query = "UPDATE imas_courses SET outcomes='$newoutcomearr' WHERE id='$cid'";
				//DB mysql_query($query) or die("Query failed :$query " . mysql_error());
				$stm = $DBH->prepare("UPDATE imas_courses SET outcomes=:outcomes WHERE id=:id");
				$stm->execute(array(':outcomes'=>$newoutcomearr, ':id'=>$cid));

			} else {
				$outcomes = array();
				//DB $query = "SELECT tc.id,toc.id FROM imas_outcomes AS tc JOIN imas_outcomes AS toc ON tc.name=toc.name WHERE tc.courseid='{$_POST['ctc']}' AND ";
				//DB $query .= "toc.courseid='$cid'";
				//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				//DB while ($row = mysql_fetch_row($result)) {
				$query = "SELECT tc.id,toc.id FROM imas_outcomes AS tc JOIN imas_outcomes AS toc ON tc.name=toc.name WHERE tc.courseid=:courseid AND ";
				$query .= "toc.courseid=:courseid2";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':courseid'=>$ctc, ':courseid2'=>$cid));
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					$outcomes[$row[0]] = $row[1];
				}
			}

			if (isset($_POST['removewithdrawn'])) {
				$removewithdrawn = true;
			}
			if (isset($_POST['usereplaceby'])) {
				$usereplaceby = "all";
				//DB $query = 'SELECT imas_questionset.id,imas_questionset.replaceby FROM imas_questionset JOIN ';
				//DB $query .= 'imas_questions ON imas_questionset.id=imas_questions.questionsetid JOIN ';
				//DB $query .= 'imas_assessments ON imas_assessments.id=imas_questions.assessmentid WHERE ';
				//DB $query .= "imas_assessments.courseid='{$_POST['ctc']}' AND imas_questionset.replaceby>0";
				//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				//DB while ($row = mysql_fetch_row($result)) {
				$query = 'SELECT imas_questionset.id,imas_questionset.replaceby FROM imas_questionset JOIN ';
				$query .= 'imas_questions ON imas_questionset.id=imas_questions.questionsetid JOIN ';
				$query .= 'imas_assessments ON imas_assessments.id=imas_questions.assessmentid WHERE ';
				$query .= "imas_assessments.courseid=:courseid AND imas_questionset.replaceby>0";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':courseid'=>$ctc));
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					$replacebyarr[$row[0]] = $row[1];
				}
			}

			if (isset($_POST['checked']) || $_POST['whattocopy']=='all') {
				$checked = $_POST['checked'];
				//DB $query = "SELECT blockcnt FROM imas_courses WHERE id='$cid'";
				//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
				//DB $blockcnt = mysql_result($result,0,0);
				$stm = $DBH->prepare("SELECT blockcnt,dates_by_lti FROM imas_courses WHERE id=:id");
				$stm->execute(array(':id'=>$cid));
				list($blockcnt,$datesbylti) = $stm->fetch(PDO::FETCH_NUM);

				//DB $query = "SELECT itemorder FROM imas_courses WHERE id='{$_POST['ctc']}'";
				//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
				//DB $items = unserialize(mysql_result($result,0,0));
				$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
				$stm->execute(array(':id'=>$ctc));
				$items = unserialize($stm->fetchColumn(0));
				$newitems = array();

				if (isset($_POST['copystickyposts'])) {
					$copystickyposts = true;
				} else {
					$copystickyposts = false;
				}

				if ($_POST['whattocopy']=='all') {
					copyallsub($items,'0',$newitems,$gbcats);
				} else {
					copysub($items,'0',$newitems,$gbcats,isset($_POST['copyhidden']));
				}
				doaftercopy($_POST['ctc']);

				//DB $query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
				//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
				//DB $items = unserialize(mysql_result($result,0,0));
				$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
				$stm->execute(array(':id'=>$cid));
				$items = unserialize($stm->fetchColumn(0));
				if ($_POST['addto']=="none") {
					array_splice($items,count($items),0,$newitems);
				} else {
					$blocktree = explode('-',$_POST['addto']);
					$sub =& $items;
					for ($i=1;$i<count($blocktree);$i++) {
						$sub =& $sub[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
					}
					array_splice($sub,count($sub),0,$newitems);
				}
				//DB $itemorder = addslashes(serialize($items));
				$itemorder = serialize($items);
				if ($itemorder!='') {
					//DB $query = "UPDATE imas_courses SET itemorder='$itemorder',blockcnt='$blockcnt' WHERE id='$cid'";
					//DB mysql_query($query) or die("Query failed : $query" . mysql_error());
					$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder,blockcnt=:blockcnt WHERE id=:id");
					$stm->execute(array(':itemorder'=>$itemorder, ':blockcnt'=>$blockcnt, ':id'=>$cid));
				}
			}
			$offlinerubrics = array();
			if (isset($_POST['copyoffline'])) {
				//DB $query = "SELECT name,points,showdate,gbcategory,cntingb,tutoredit,rubric FROM imas_gbitems WHERE courseid='{$_POST['ctc']}'";
				//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				$stm = $DBH->prepare("SELECT name,points,showdate,gbcategory,cntingb,tutoredit,rubric FROM imas_gbitems WHERE courseid=:courseid");
				$stm->execute(array(':courseid'=>$ctc));
				$gbi_ins_stm = null;
				//DB while ($row = mysql_fetch_row($result)) {
				while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
					//DB $rubric = array_pop($row);
					$rubric = $row['rubric'];
					unset($row['rubric']);
					if (isset($gbcats[$row['gbcategory']])) {
						$row['gbcategory'] = $gbcats[$row['gbcategory']];
					} else {
						$row['gbcategory'] = 0;
					}
					//DB $ins = "('$cid','".implode("','",addslashes_deep($row))."')";
					//DB $query = "INSERT INTO imas_gbitems (courseid,name,points,showdate,gbcategory,cntingb,tutoredit) VALUES $ins";
					if ($gbi_ins_stm === null) {
						$query = "INSERT INTO imas_gbitems (courseid,name,points,showdate,gbcategory,cntingb,tutoredit) VALUES ";
						$query .= "(:courseid,:name,:points,:showdate,:gbcategory,:cntingb,:tutoredit)";
						$gbi_ins_stm = $DBH->prepare($query);
					}
					$row[':courseid'] = $cid;
					$gbi_ins_stm->execute($row);
					//DB mysql_query($query) or die("Query failed :$query " . mysql_error());
					if ($rubric>0) {
						//DB $offlinerubrics[mysql_insert_id()] = $rubric;
						$offlinerubrics[$DBH->lastInsertId()] = $rubric;
					}
				}
			}
			if (isset($_POST['copyrubrics'])) {
				copyrubrics($offlinerubrics);
			}
			if (isset($_POST['copystudata']) && ($myrights==100 || ($myspecialrights&32)==32 || ($myspecialrights&64)==64)) {
				require("../util/copystudata.php");
				copyStuData($cid, $_POST['ctc']);
			}
			//DB mysql_query("COMMIT") or die("Query failed :$query " . mysql_error());
			$DBH->commit();
			if (isset($_POST['selectcalitems'])) {
				$_GET['action']='selectcalitems';
				$calitems = array();
				//DB $query = "SELECT id,date,tag,title FROM imas_calitems WHERE courseid='{$_POST['ctc']}' ORDER BY date";
				//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
				//DB while ($row = mysql_fetch_row($result)) {
				$stm = $DBH->prepare("SELECT id,date,tag,title FROM imas_calitems WHERE courseid=:courseid ORDER BY date");
				$stm->execute(array(':courseid'=>$ctc));
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					$calitems[] = $row;
				}
			} else {
			  header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=$cid&r=" . Sanitize::randomQueryStringParam());

				exit;
			}

		} elseif (isset($_GET['action']) && $_GET['action']=="select") { //DATA MANIPULATION FOR second option
			$items = false;

			$stm = $DBH->prepare("SELECT id,itemorder,picicons,name FROM imas_courses WHERE id IN (?,?)");
			$stm->execute(array($_POST['ctc'], $cid));
			while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
				if ($row['id']==$ctc) {
					$items = unserialize($row['itemorder']);
					$picicons = $row['picicons'];
					$ctcname = $row['name'];
				}
				if ($row['id']==$cid) {
					$existblocks = array();
					buildexistblocks(unserialize($row['itemorder']),'0');
				}
			}
			if ($items===false) {
				echo 'Error with course to copy';
				exit;
			}
			
			$ids = array();
			$types = array();
			$names = array();
			$sums = array();
			$parents = array();
			require_once("../includes/loaditemshowdata.php");
			$itemshowdata = loadItemShowData($items,false,true,false,false,false,true);
			getsubinfo($items,'0','',false,' ');

			$i=0;
			$page_blockSelect = array();

			foreach ($existblocks as $k=>$name) {
				$page_blockSelect['val'][$i] = $k;
				$page_blockSelect['label'][$i] = $name;
				$i++;
			}

		} else if (isset($_GET['loadothers'])) {
			//DB $query = "SELECT id,name FROM imas_groups";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB if (mysql_num_rows($result)>0) {
			$stm = $DBH->query("SELECT id,name FROM imas_groups ORDER BY name");
			if ($stm->rowCount()>0) {
				$page_hasGroups=true;
				$grpnames = array();
				$grpnames[] = array('id'=>0,'name'=>"Default Group");
				//DB while ($row = mysql_fetch_row($result)) {
				while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
					if ($row['id']==$groupid) {continue;}
					$grpnames[] = $row;
				}
			}

		} else if (isset($_GET['loadothergroup'])) {

			$query = "SELECT ic.id,ic.name,ic.copyrights,iu.LastName,iu.FirstName,iu.email,it.userid,iu.groupid,ic.termsurl,ic.istemplate FROM imas_courses AS ic,imas_teachers AS it,imas_users AS iu  WHERE ";
			$query .= "it.courseid=ic.id AND it.userid=iu.id AND iu.groupid=:groupid AND iu.id<>:userid AND ic.available<4 ORDER BY iu.LastName,iu.FirstName,it.userid,ic.name";
			$courseGroupResults = $DBH->prepare($query);
			$courseGroupResults->execute(array(':groupid'=>$_GET['loadothergroup'], ':userid'=>$userid));


		} else { //DATA MANIPULATION FOR DEFAULT LOAD

			$stm = $DBH->prepare("SELECT jsondata FROM imas_users WHERE id=:id");
			$stm->execute(array(':id'=>$userid));
			$userjson = json_decode($stm->fetchColumn(0), true);

			$myCourseResult = $DBH->prepare("SELECT ic.id,ic.name,ic.termsurl,ic.copyrights FROM imas_courses AS ic,imas_teachers WHERE imas_teachers.courseid=ic.id AND imas_teachers.userid=:userid and ic.id<>:cid AND ic.available<4 ORDER BY ic.name");
			$myCourseResult->execute(array(':userid'=>$userid, ':cid'=>$cid));
			$myCourses = array();
			$myCoursesDefaultOrder = array();
			while ($line = $myCourseResult->fetch(PDO::FETCH_ASSOC)) {
				$myCourses[$line['id']] = $line;
				$myCoursesDefaultOrder[] = $line['id'];
			}
		/*	$i=0;
			$page_mineList = array();
			while ($row = mysql_fetch_row($result)) {
				$page_mineList['val'][$i] = $row[0];
				$page_mineList['label'][$i] = $row[1];
				$i++;
			}
		*/
			//DB $query = "SELECT ic.id,ic.name,ic.copyrights,iu.LastName,iu.FirstName,iu.email,it.userid,ic.termsurl FROM imas_courses AS ic,imas_teachers AS it,imas_users AS iu WHERE ";
			//DB $query .= "it.courseid=ic.id AND it.userid=iu.id AND iu.groupid='$groupid' AND iu.id<>'$userid' AND ic.available<4 ORDER BY iu.LastName,iu.FirstName,ic.name";
			//DB $courseTreeResult = mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "SELECT ic.id,ic.name,ic.copyrights,iu.LastName,iu.FirstName,iu.email,it.userid,ic.termsurl FROM imas_courses AS ic,imas_teachers AS it,imas_users AS iu WHERE ";
			$query .= "it.courseid=ic.id AND it.userid=iu.id AND iu.groupid=:groupid AND iu.id<>:userid AND ic.available<4 ORDER BY iu.LastName,iu.FirstName,it.userid,ic.name";
			$courseTreeResult = $DBH->prepare($query);
			$courseTreeResult->execute(array(':groupid'=>$groupid, ':userid'=>$userid));
			$lastteacher = 0;


			//$query = "SELECT ic.id,ic.name,ic.copyrights FROM imas_courses AS ic,imas_teachers WHERE imas_teachers.courseid=ic.id AND imas_teachers.userid='$templateuser' AND ic.available<4 ORDER BY ic.name";
			//DB $query = "SELECT id,name,copyrights,termsurl FROM imas_courses WHERE (istemplate&1)=1 AND copyrights=2 AND available<4 ORDER BY name";
			//DB $courseTemplateResults = mysql_query($query) or die("Query failed : " . mysql_error());
			$courseTemplateResults = $DBH->query("SELECT id,name,copyrights,termsurl FROM imas_courses WHERE (istemplate&1)=1 AND copyrights=2 AND available<4 ORDER BY name");

			//DB $query = "SELECT ic.id,ic.name,ic.copyrights,ic.termsurl FROM imas_courses AS ic JOIN imas_users AS iu ON ic.ownerid=iu.id WHERE ";
			//DB $query .= "iu.groupid='$groupid' AND (ic.istemplate&2)=2 AND ic.copyrights>0 AND ic.available<4 ORDER BY ic.name";
			//DB $groupTemplateResults = mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "SELECT ic.id,ic.name,ic.copyrights,ic.termsurl FROM imas_courses AS ic JOIN imas_users AS iu ON ic.ownerid=iu.id WHERE ";
			$query .= "iu.groupid=:groupid AND (ic.istemplate&2)=2 AND ic.copyrights>0 AND ic.available<4 ORDER BY ic.name";
			$groupTemplateResults = $DBH->prepare($query);
			$groupTemplateResults->execute(array(':groupid'=>$groupid));
		}
	}
}
function printCourseOrder($order, $data, &$printed) {
	foreach ($order as $item) {
		if (is_array($item)) {
			echo '<li class="coursegroup"><span class=dd>-</span> ';
			echo '<b>'.Sanitize::encodeStringForDisplay($item['name']).'</b>';
			echo '<ul class="nomark">';
			printCourseOrder($item['courses'], $data, $printed);
			echo '</ul></li>';
		} else if (isset($data[$item])) {
			printCourseLine($data[$item]);
			$printed[] = $item;
		}
	}		
}
function printCourseLine($data) {
	echo '<li><span class=dd>-</span> ';
	writeCourseInfo($data, -1);
	echo '</li>';
}
function writeCourseInfo($line, $skipcopyright=2) {
	$itemclasses = array();
	if ($line['copyrights']<$skipcopyright) {
		$itemclasses[] = 'copyr';
	}
	if ($line['termsurl']!='') {
		$itemclasses[] = 'termsurl';
	}
	echo '<input type="radio" name="ctc" value="' . Sanitize::encodeStringForDisplay($line['id']) . '" ' . ((count($itemclasses)>0)?'class="' . implode(' ',$itemclasses) . '"':'');
	if ($line['termsurl']!='') {
		echo ' data-termsurl="'.Sanitize::url($line['termsurl']).'"';
	}
	echo '>';
	echo Sanitize::encodeStringForDisplay($line['name']);

	if ($line['copyrights']<$skipcopyright) {
		echo "&copy;\n";
	} else {
		echo " <a href=\"course.php?cid=" . Sanitize::courseId($line['id']) . "\" target=\"_blank\" class=\"small\">Preview</a>";
	}
}

function writeOtherGrpTemplates($grptemplatelist) {
	if (count($grptemplatelist)==0) { return;}
	?>
	<li class=lihdr>
	<span class=dd>-</span>
	<span class=hdr onClick="toggle('OGT<?php echo $line['groupid'] ?>')">
		<span class=btn id="bOGT<?php echo $line['groupid'] ?>">+</span>
	</span>
	<span class=hdr onClick="toggle('OGT<?php echo $line['groupid'] ?>')">
		<span id="nOGT<?php echo $line['groupid'] ?>" ><?php echo _('Group Templates') . "\n" ?>
		</span>
	</span>
	<ul class=hide id="OGT<?php echo $line['groupid'] ?>">
	<?php
	$showncourses = array();
	foreach ($grptemplatelist as $gt) {
		if (in_array($gt['courseid'], $showncourses)) {continue;}
		echo '<li><span class=dd>-</span>';
		writeCourseInfo($gt);
		$showncourses[] = $gt['courseid'];
		echo '<li>';
	}
	echo '</ul></li>';
}


/******* begin html output ********/

if (!isset($_GET['loadothers']) && !isset($_GET['loadothergroup'])) {
$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/libtree.js\"></script>\n";
$placeinhead .= "<style type=\"text/css\">\n<!--\n@import url(\"$imasroot/course/libtree.css\");\n-->\n</style>\n";
$placeinhead .= '<script type="text/javascript">
	function updatetocopy(el) {
		if (el.value=="all") {
			$("#selectitemstocopy").hide();$("#allitemsnote").show();
			$("#copyoptions").show();
			$("#copyoptions .selectonly").hide();
			$("#copyoptions .allon input[type=checkbox]").prop("checked",true);
		} else {
			$("#selectitemstocopy").show();$("#allitemsnote").hide();
			$("#copyoptions").show();
			$("#copyoptions .selectonly").show();
			$("#copyoptions .allon input[type=checkbox]").prop("checked",false);
		}
	}
	function copyitemsonsubmit() {
		if (!document.getElementById("whattocopy1").checked && !document.getElementById("whattocopy2").checked) {
			alert(_("Select an option for what to copy"));
			return false;
		} else {
			return true;
		}
	}
	$(function() {
		$("input:radio").change(function() {
			if ($(this).attr("id")!="coursebrowserctc") {
				$("#coursebrowserout").hide();
			}
			if ($(this).hasClass("copyr")) {
				$("#ekeybox").show();
			} else {
				$("#ekeybox").hide();
			}
			if ($(this).hasClass("termsurl")) {
				$("#termsbox").show();
				console.log($(this).data("termsurl"));
				$("#termsurl").attr("href",$(this).data("termsurl"));
			} else {
				$("#termsbox").hide();
			}
		});
	});
	function showCourseBrowser() {
		GB_show("Course Browser","../admin/coursebrowser.php?embedded=true",800,"auto");
	}
	function setCourse(course) {
		$("#coursebrowserctc").val(course.id).prop("checked",true);
		$("#templatename").text(course.name);
		$("#coursebrowserout").show();
		if (course.termsurl && course.termsurl != "") {
			$("#termsbox").show(); $("#termsurl").attr("href",course.termsurl);
		} else {
			$("#termsbox").hide();
			$("form").submit();
		}
		GB_hide();
	}
	function lookupcid() {
		$("#cidlookuperr").text("");
		var cidtolookup = $("#cidlookup").val();
		$.ajax({
			type: "POST",
			url: "copyitems.php?cid="+cid,
			data: { cidlookup: cidtolookup},
			dataType: "json"
		}).done(function(res) {
			if ($.isEmptyObject(res)) {
				$("#cidlookuperr").text("Course ID not found");
				$("#cidlookupout").hide();
			} else {
				$("#cidlookupctc").val(res.id);
				if (res.needkey) {
					res.name += " &copy;";
				} else {
					res.name +=  " <a href=\"course.php?cid="+res.id+"\" target=\"_blank\" class=\"small\">Preview</a>";
				}
				$("#cidlookupname").html(res.name);
				if (res.termsurl != "") {
					$("#cidlookupctc").addClass("termsurl");
					$("#cidlookupctc").attr("data-termsurl",res.termsurl);
				} else {
					$("#cidlookupctc").removeClass("termsurl");
					$("#cidlookupctc").removeAttr("data-termsurl");
				}
				if (res.needkey) {
					$("#cidlookupctc").addClass("copyr");
				} else {
					$("#cidlookupctc").removeClass("copyr");
				}
				$("#cidlookupctc").prop("checked",true).trigger("change");
				$("#cidlookupout").show();
			}
		}).fail(function() {
			$("#cidlookuperr").text("Lookup error");
			$("#cidlookupout").hide();
		});
	}
		</script>';
require("../header.php");
}
if ($overwriteBody==1) {
	echo $body;
} else {
	if (!isset($_GET['loadothers']) && !isset($_GET['loadothergroup'])) {
?>

	<div class=breadcrumb><?php echo $curBreadcrumb ?></div>
	<div id="headercopyitems" class="pagetitle"><h2>Copy Course Items</h2></div>

<?php
	}
	if (isset($_GET['action']) && $_GET['action']=='selectcalitems') {
//DISPLAY BLOCK FOR selecting calendar items to copy
?>
	<form id="qform" method=post action="copyitems.php?cid=<?php echo $cid ?>&action=copycalitems">
	<input type=hidden name=ekey id=ekey value="<?php echo Sanitize::encodeStringForDisplay($_POST['ekey']); ?>">
	<input type=hidden name=ctc id=ctc value="<?php echo Sanitize::encodeStringForDisplay($ctc); ?>">
	<h4>Select Calendar Items to Copy</h4>
	Check: <a href="#" onclick="return chkAllNone('qform','checked[]',true)">All</a> <a href="#" onclick="return chkAllNone('qform','checked[]',false)">None</a>

	<table cellpadding=5 class=gb>
		<thead>
		<tr><th></th><th>Date</th><th>Tag</th><th>Text</th></tr>
		</thead>
		<tbody>
<?php
		$alt=0;
		for ($i = 0 ; $i<(count($calitems)); $i++) {
			if ($alt==0) {echo "		<tr class=even>"; $alt=1;} else {echo "		<tr class=odd>"; $alt=0;}
?>
			<td>
			<input type=checkbox name='checked[]' value='<?php echo Sanitize::encodeStringForDisplay($calitems[$i][0]); ?>' checked="checked"/>
			</td>
			<td class="nowrap"><?php echo tzdate("m/d/Y",$calitems[$i][1]); ?></td>
			<td><?php echo Sanitize::encodeStringForDisplay($calitems[$i][2]); ?></td>
			<td><?php echo Sanitize::encodeStringForDisplay($calitems[$i][3]); ?></td>
		</tr>
<?php
		}
?>
		</tbody>
	</table>
	<p>Remove all existing calendar items? <input type="checkbox" name="clearexisting" value="1" /></p>
	<p><input type=submit value="Copy Calendar Items"></p>
	</form>

<?php

	} else if (isset($_GET['action']) && $_GET['action']=="select") {

//DISPLAY BLOCK FOR SECOND STEP - selecting course item
?>
	<script type="text/javascript">

	function chkgrp(frm, arr, mark) {
	  var els = frm.getElementsByTagName("input");
	  for (var i = 0; i < els.length; i++) {
		  var el = els[i];
		  if (el.type=='checkbox' && (el.id.indexOf(arr+'.')==0 || el.id.indexOf(arr+'-')==0 || el.id==arr)) {
	     	       el.checked = mark;
		  }
	  }
	}
	</script>
	<p>Copying course: <b><?php echo Sanitize::encodeStringForDisplay($ctcname);?></b></p>
	
	<form id="qform" method=post action="copyitems.php?cid=<?php echo $cid ?>&action=copy" onsubmit="return copyitemsonsubmit();">
	<input type=hidden name=ekey id=ekey value="<?php echo Sanitize::encodeStringForDisplay($_POST['ekey']); ?>">
	<input type=hidden name=ctc id=ctc value="<?php echo Sanitize::encodeStringForDisplay($ctc); ?>">
	<p>What to copy:
	<?php
		if ($_POST['ekey']=='') { echo ' <a class="small" target="_blank" href="course.php?cid='.Sanitize::onlyInt($ctc).'">Preview source course</a>';}
	?>
	<br/>
	<input type=radio name=whattocopy value="all" id=whattocopy1 onchange="updatetocopy(this)"> <label for=whattocopy1>Copy whole course</label><br/>
	<input type=radio name=whattocopy value="select" id=whattocopy2 onchange="updatetocopy(this)"> <label for=whattocopy2>Select items to copy</label></p>

	<div id="allitemsnote" style="display:none;">
	<p class="noticetext">You are about to copy ALL items in this course.</p>
	<p>In most cases, you'll want to leave the options below set to their default
		values </p>
	</div>
	<div id="selectitemstocopy" style="display:none;">
	<h4>Select Items to Copy</h4>

	Check: <a href="#" onclick="return chkAllNone('qform','checked[]',true)">All</a> <a href="#" onclick="return chkAllNone('qform','checked[]',false)">None</a>

	<table cellpadding=5 class=gb>
		<thead>
		<?php
		if ($picicons) {
			echo '<tr><th></th><th>Title</th><th>Summary</th></tr>';
		} else {
			echo '<tr><th></th><th>Type</th><th>Title</th><th>Summary</th></tr>';
		}
		?>

		</thead>
		<tbody>
<?php
		$alt=0;

		for ($i = 0 ; $i<(count($ids)); $i++) {
			if ($alt==0) {echo "		<tr class=even>"; $alt=1;} else {echo "		<tr class=odd>"; $alt=0;}
			echo '<td>';
			if (strpos($types[$i],'Block')!==false) {
				echo "<input type=checkbox name='checked[]' value='{$ids[$i]}' id='{$parents[$i]}' ";
				echo "onClick=\"chkgrp(this.form, '{$ids[$i]}', this.checked);\" ";
				echo '/>';
			} else {
				echo "<input type=checkbox name='checked[]' value='{$ids[$i]}' id='{$parents[$i]}.{$ids[$i]}' ";
				echo '/>';
			}
?>
			</td>

		<?php
			$tdpad = 16*strlen($prespace[$i]);

			if ($picicons) {
				echo '<td style="padding-left:'.$tdpad.'px"><img alt="'.$types[$i].'" title="'.$types[$i].'" src="'.$imasroot.'/img/';
				switch ($types[$i]) {
					case 'Calendar': echo $CFG['CPS']['miniicons']['calendar']; break;
					case 'InlineText': echo $CFG['CPS']['miniicons']['inline']; break;
					case 'LinkedText': echo $CFG['CPS']['miniicons']['linked']; break;
					case 'Forum': echo $CFG['CPS']['miniicons']['forum']; break;
					case 'Wiki': echo $CFG['CPS']['miniicons']['wiki']; break;
					case 'Block': echo $CFG['CPS']['miniicons']['folder']; break;
					case 'Assessment': echo $CFG['CPS']['miniicons']['assess']; break;
					case 'Drill': echo $CFG['CPS']['miniicons']['drill']; break;
				}
				echo '" class="floatleft"/><div style="margin-left:21px">'.$names[$i].'</div></td>';
			} else {

				echo '<td>'.$prespace[$i].$names[$i].'</td>';
				echo '<td>'.$types[$i].'</td>';
			}
		?>
			<td><?php echo $sums[$i] ?></td>
		</tr>
<?php
		}
?>

		</tbody>
	</table>
</div>
	<p> </p>
<div id="copyoptions" style="display:none;">
	<fieldset><legend>Options</legend>
	<table>
	<tbody>
	<tr class="allon"><td class="r">Copy course settings?</td><td><input type=checkbox name="copycourseopt"  value="1"/></td></tr>
	<tr class="allon"><td class="r">Copy gradebook scheme and categories<br/>(<i>will overwrite current scheme</i>)? </td><td>
		<input type=checkbox name="copygbsetup" value="1"/></td></tr>
	<tr><td class="r">Set all copied items as hidden to students?</td><td><input type="checkbox" name="copyhidden" value="1"/></td></tr>
	<tr><td class="r">Copy offline grade items?</td><td> <input type=checkbox name="copyoffline"  value="1"/></td></tr>
	<tr><td class="r">Remove any withdrawn questions from assessments?</td><td> <input type=checkbox name="removewithdrawn"  value="1" checked="checked"/></td></tr>
	<tr><td class="r">Use any suggested replacements for old questions?</td><td> <input type=checkbox name="usereplaceby"  value="1" checked="checked"/></td></tr>
	<tr><td class="r">Copy rubrics? </td><td><input type=checkbox name="copyrubrics"  value="1" checked="checked"/></td></tr>
	<tr><td class="r">Copy outcomes? </td><td><input type=checkbox name="copyoutcomes"  value="1" /></td></tr>
	<tr><td class="r">Select calendar items to copy?</td><td> <input type=checkbox name="selectcalitems"  value="1"/></td></tr>

	<tr><td class="r">Copy "display at top" instructor forum posts? </td><td><input type=checkbox name="copystickyposts"  value="1" checked="checked"/></td></tr>

	<tr class="selectonly"><td class="r">Append text to titles?</td><td> <input type="text" name="append"></td></tr>
	<tr class="selectonly"><td class="r">Add to block:</td><td>

<?php
writeHtmlSelect ("addto",$page_blockSelect['val'],$page_blockSelect['label'],$selectedVal=null,$defaultLabel="Main Course Page",$defaultVal="none",$actions=null);
?>


	</td></tr>
	<?php
	if ($myrights==100 || ($myspecialrights&32)==32 || ($myspecialrights&64)==64) {
		echo '<tr><td class="r">Also copy students and assessment attempt data?</td>';
		echo '<td><input type=checkbox name=copystudata value=1> NOT recommended unless you know what you are doing.</td></tr>';
	}
	?>
	</tbody>
	</table>
	</fieldset>
	</div>
	<p><input type=submit value="Copy Items"></p>
	</form>
<?php
	} else if (isset($_GET['loadothers'])) { //loading others subblock
	 if ($page_hasGroups) {
				foreach ($grpnames as $grp) {
					?>
								<li class=lihdr>
									<span class=dd>-</span>
									<span class=hdr onClick="loadothergroup('<?php echo Sanitize::encodeStringForJavascript($grp['id']); ?>')">
										<span class=btn id="bg<?php echo Sanitize::encodeStringForDisplay($grp['id']); ?>">+</span>
									</span>
									<span class=hdr onClick="loadothergroup('<?php echo Sanitize::encodeStringForJavascript($grp['id']); ?>')">
										<span id="ng<?php echo Sanitize::encodeStringForDisplay($grp['id']); ?>" ><?php echo Sanitize::encodeStringForDisplay($grp['name']); ?></span>
									</span>
									<ul class=hide id="g<?php echo Sanitize::encodeStringForDisplay($grp['id']); ?>">
										<li>Loading...</li>
									</ul>
								</li>
					<?php
				}
		 } else {
			 echo '<li>No other users</li>';
		 }

	} else if (isset($_GET['loadothergroup'])) { //loading others subblock
	 if ($courseGroupResults->rowCount()>0) {
				$lastteacher = 0;
				$grptemplatelist = array(); //writeOtherGrpTemplates($grptemplatelist);
				while ($line = $courseGroupResults->fetch(PDO::FETCH_ASSOC)) {
					if ($line['userid']!=$lastteacher) {
						if ($lastteacher!=0) {
							echo "				</ul>\n			</li>\n";
						}
	?>
				<li class=lihdr>
					<span class=dd>-</span>
					<span class=hdr onClick="toggle(<?php echo Sanitize::encodeStringForJavascript($line['userid']); ?>)">
						<span class=btn id="b<?php echo Sanitize::encodeStringForDisplay($line['userid']); ?>">+</span>
					</span>
					<span class=hdr onClick="toggle(<?php echo Sanitize::encodeStringForJavascript($line['userid']); ?>)">
						<span id="n<?php echo Sanitize::encodeStringForDisplay($line['userid']); ?>" ><?php echo Sanitize::encodeStringForDisplay($line['LastName']) . ", " . Sanitize::encodeStringForDisplay($line['FirstName']) . "\n" ?>
						</span>
					</span>
					<a href="mailto:<?php echo Sanitize::emailAddress($line['email']); ?>">Email</a>
					<ul class=hide id="<?php echo Sanitize::encodeStringForDisplay($line['userid']); ?>">
	<?php
						$lastteacher = $line['userid'];
					}
	?>
						<li>
							<span class=dd>-</span>
							<?php
							//do class for has terms.  Attach data-termsurl attribute.
							writeCourseInfo($line);
							if (($line['istemplate']&2)==2) {
								$grptemplatelist[] = $line;
							}
							?>
						</li>
	<?php
				}
	?>

						</ul>
					</li>
					<?php writeOtherGrpTemplates($grptemplatelist);?>

	<?php
		 } else {
			 echo '<li>No group members with courses</li>';
		 }

	} else { //DEFAULT DISPLAY BLOCK
?>
	<script type="text/javascript">
	var othersloaded = false;
	var othergroupsloaded = [];
	var ahahurl = '<?php echo $imasroot?>/course/copyitems.php?cid=<?php echo $cid ?>';
	function loadothers() {
		if (!othersloaded) {
			//basicahah(ahahurl, "other");
			$.ajax({url:ahahurl+"&loadothers=true", dataType:"html"}).done(function(resp) {
				$('#other').html(resp);
			});
			othersloaded = true;
		}
	}
	function loadothergroup(n) {
		toggle("g"+n);
		if (othergroupsloaded.indexOf(n) === -1) {
			$.ajax({url:ahahurl+"&loadothergroup="+n, dataType:"html"}).done(function(resp) {
				$('#g'+n).html(resp);
				$("#g"+n+" input:radio").change(function() {
					if ($(this).hasClass("copyr")) {
						$("#ekeybox").show();
					} else {
						$("#ekeybox").hide();
					}
					if ($(this).hasClass("termsurl")) {
						$("#termsbox").show();
						$("#termsurl").attr("href",$(this).data("termsurl"));
					} else {
						$("#termsbox").hide();
					}
				});
			});
			othergroupsloaded.push(n);
		}
	}
	</script>
	<h4>Select a course to copy items from</h4>

	<form method=post action="copyitems.php?cid=<?php echo $cid ?>&action=select">
<?php
	if (isset($CFG['coursebrowser'])) {
		//use the course browser
		echo '<p>';
		if (isset($CFG['coursebrowsermsg'])) {
			echo $CFG['coursebrowsermsg'];
		} else {
			echo _('Copy a template or promoted course');
		}
		echo ' <button type="button" onclick="showCourseBrowser()">'._('Browse Courses').'</button>';
		echo '<span id="coursebrowserout" style="display:none"><br/>';
		echo '<input type=radio name=ctc value=0 id=coursebrowserctc /> ';
		echo '<span id=templatename></span>';
		echo '</span>';
		echo '</p>';
		echo '<p>'._('Or, select from the course list below').'</p>';		
	} else {
		echo '<p>'._('Course List').'</p>';
	}
?>	
		<ul class=base>
			<li><span class=dd>-</span>
				<input type=radio name=ctc value="<?php echo $cid ?>" checked=1>This Course</li>
			<li class=lihdr><span class=dd>-</span>
				<span class=hdr onClick="toggle('mine')">
					<span class=btn id="bmine">+</span>
				</span>
				<span class=hdr onClick="toggle('mine')">
					<span id="nmine" >My Courses</span>
				</span>
				<ul class=hide id="mine">
<?php
//my items
		if (isset($userjson['courseListOrder']['teach'])) {
			$printed = array();
			printCourseOrder($userjson['courseListOrder']['teach'], $myCourses, $printed);
			$notlisted = array_diff(array_keys($myCourses), $printed);
			foreach ($notlisted as $course) {
				printCourseLine($myCourses[$course]);
			}
		} else {
			foreach ($myCoursesDefaultOrder as $course) {
				printCourseLine($myCourses[$course]);
			}
		}
?>
				</ul>
			</li>
			<li class=lihdr><span class=dd>-</span>
				<span class=hdr onClick="toggle('grp')">
					<span class=btn id="bgrp">+</span>
				</span>
				<span class=hdr onClick="toggle('grp')">
					<span id="ngrp" >My Group's Courses</span>
				</span>
				<ul class=hide id="grp">

<?php
//group's courses
		//DB if (mysql_num_rows($courseTreeResult)>0) {
			//DB while ($line = mysql_fetch_array($courseTreeResult, MYSQL_ASSOC)) {
		if ($courseTreeResult->rowCount()>0) {
			while ($line = $courseTreeResult->fetch(PDO::FETCH_ASSOC)) {
				if ($line['userid']!=$lastteacher) {
					if ($lastteacher!=0) {
						echo "				</ul>\n			</li>\n";
					}
?>
					<li class=lihdr>
						<span class=dd>-</span>
						<span class=hdr onClick="toggle(<?php echo Sanitize::encodeStringForJavascript($line['userid']); ?>)">
							<span class=btn id="b<?php echo Sanitize::encodeStringForDisplay($line['userid']); ?>">+</span>
						</span>
						<span class=hdr onClick="toggle(<?php echo Sanitize::encodeStringForJavascript($line['userid']); ?>)">
							<span id="n<?php echo Sanitize::encodeStringForDisplay($line['userid']); ?>"><?php echo Sanitize::encodeStringForDisplay($line['LastName']) . ", " . Sanitize::encodeStringForDisplay($line['FirstName']) . "\n" ?>
							</span>
						</span>
						<a href="mailto:<?php echo Sanitize::emailAddress($line['email']); ?>">Email</a>
						<ul class=hide id="<?php echo Sanitize::encodeStringForDisplay($line['userid']); ?>">
<?php
					$lastteacher = $line['userid'];
				}
?>
							<li>
								<span class=dd>-</span>
								<?php
								writeCourseInfo($line, 1);
								?>
							</li>
<?php
			}
			echo "						</ul>\n					</li>\n";
			echo "				</ul>			</li>\n";
		} else {
			echo "				</ul>\n			</li>\n";
		}
?>
			<li class=lihdr>
				<span class=dd>-</span>
				<span class=hdr onClick="toggle('other');loadothers();">
					<span class=btn id="bother">+</span>
				</span>
				<span class=hdr onClick="toggle('other');loadothers();">
					<span id="nother" >Other's Courses</span>
				</span>
				<ul class=hide id="other">

<?php
//Other's courses: loaded via AHAH when clicked
		echo "<li>Loading...</li>			</ul>\n		</li>\n";

//template courses
		//DB if (mysql_num_rows($courseTemplateResults)>0) {
		if ($courseTemplateResults->rowCount()>0 && !isset($CFG['coursebrowser'])) {
?>
		<li class=lihdr>
			<span class=dd>-</span>
			<span class=hdr onClick="toggle('template')">
				<span class=btn id="btemplate">+</span>
			</span>
			<span class=hdr onClick="toggle('template')">
				<span id="ntemplate" >Template Courses</span>
			</span>
			<ul class=hide id="template">

<?php
			//DB while ($line = mysql_fetch_assoc($courseTemplateResults)) {
			while ($line = $courseTemplateResults->fetch(PDO::FETCH_ASSOC)) {
?>
				<li>
					<span class=dd>-</span>
					<?php
					writeCourseInfo($line);
					?>
				</li>

<?php
			}
			echo "			</ul>\n		</li>\n";
		}
		//DB if (mysql_num_rows($groupTemplateResults)>0) {
		if ($groupTemplateResults->rowCount()>0 && !isset($CFG['coursebrowser'])) {
?>
		<li class=lihdr>
			<span class=dd>-</span>
			<span class=hdr onClick="toggle('gtemplate')">
				<span class=btn id="bgtemplate">+</span>
			</span>
			<span class=hdr onClick="toggle('gtemplate')">
				<span id="ngtemplate" >Group Template Courses</span>
			</span>
			<ul class=hide id="gtemplate">

<?php
			//DB while ($line = mysql_fetch_assoc($groupTemplateResults)) {
			while ($line = $groupTemplateResults->fetch(PDO::FETCH_ASSOC)) {
?>
				<li>
					<span class=dd>-</span>
					<?php
					writeCourseInfo($line, 1);
					?>
				</li>

<?php
			}
			echo "			</ul>\n		</li>\n";
		}
?>
		</ul>
		
		<p>Or, lookup using course ID: 
			<input type="text" size="7" id="cidlookup" />
			<button type="button" onclick="lookupcid()">Look up course</button>
			<span id="cidlookupout" style="display:none;"><br/>
				<input type=radio name=ctc value=0 id=cidlookupctc />
				<span id="cidlookupname"></span>
			</span>
			<span id="cidlookuperr"></span>
		</p>

		<p id="ekeybox" style="display:none;">
		For courses marked with &copy;, you must supply the course enrollment key to show permission to copy the course.<br/>
		Enrollment key: <input type=text name=ekey id=ekey size=30></p>

		<p id="termsbox" style="display:none;">
		This course has additional <a target="_blank" href="" id="termsurl">Terms of Use</a> you must agree to before copying the course.<br/>
		<input type="checkbox" name="termsagree" /> I agree to the Terms of Use specified in the link above.</p>

		<input type=submit value="Select Course Items">
		<p>&nbsp;</p>
	</form>

<?php
	}
}
if (!isset($_GET['loadothers']) && !isset($_GET['loadothergroup'])) {
 require ("../footer.php");
}
?>
