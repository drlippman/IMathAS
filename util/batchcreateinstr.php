<?php
//IMathAS:  Batch create instructors
//(c) 2017 David Lippman for Lumen Learning
 
@set_time_limit(0);
ini_set("max_input_time", "1600");
ini_set("max_execution_time", "1600");
ini_set("memory_limit", "104857600");
ini_set("upload_max_filesize", "10485760");
ini_set("post_max_size", "10485760");

require("../init.php");
require_once("../includes/copyiteminc.php");

if ($myrights < 100 && ($myspecialrights&16)!=16 && ($myspecialrights&32)!=32) {
  echo "You're not authorized for this page";
  exit;
}
$curBreadcrumb = "<div class=breadcrumb>$breadcrumbbase <a href=\"admin2.php\">Admin</a> &gt; Batch Create Instructors</div>\n";

if (isset($_POST['groupid']) && is_uploaded_file($_FILES['uploadedfile']['tmp_name'])) {
  if ($myrights == 100 || ($myspecialrights&32)==32) {
  	  if ($_POST['groupid']<1) {
  	  	  echo "Invalid group selection";
  	  	  exit;
  	  } else {
  	  	  $newusergroupid = $_POST['groupid'];
  	  }
  } else {
  	  $newusergroupid = $groupid;
  }
  if (isset($CFG['GEN']['newpasswords'])) {
    require_once("../includes/password.php");
  }
  if (isset($CFG['GEN']['homelayout'])) {
    $homelayout = $CFG['GEN']['homelayout'];
  } else {
    $homelayout = '|0,1,2||0,1';
  }
  $now = time();
  $isoktocopy = array();
  $handle = fopen_utf8($_FILES['uploadedfile']['tmp_name'],'r');
  while (($data = fgetcsv($handle,2096))!==false) {
    if (trim($data[0])=='') {continue;}
    if (count($data)<5) {
      echo "Invalid row - skipping<br/>";
      continue;
    }
    $stm = $DBH->prepare("SELECT id FROM imas_users WHERE SID=:SID");
    $stm->execute(array(':SID'=>$data[0]));
    if ($stm->rowCount()>0) {
      echo "Username ".Sanitize::encodeStringForDisplay($data[0])." already in use - skipping user<br/>";
      continue;
    }

    if (isset($CFG['GEN']['newpasswords'])) {
			$hashpw = password_hash($data[1], PASSWORD_DEFAULT);
		} else {
			$hashpw = md5($data[1]);
		}
    echo "Importing ".Sanitize::encodeStringForDisplay($data[0])."<br/>";
    $query = 'INSERT INTO imas_users (SID,password,FirstName,LastName,rights,email,groupid,homelayout,forcepwreset) VALUES (:SID, :password, :FirstName, :LastName, :rights, :email, :groupid, :homelayout, 1)';
    $stm = $DBH->prepare($query);
    $stm->execute(array(':SID'=>$data[0], ':password'=>$hashpw, ':FirstName'=>$data[2], ':LastName'=>$data[3],
            ':rights'=>40, ':email'=>$data[4], ':groupid'=>$newusergroupid, ':homelayout'=>$homelayout));

    $newuserid = $DBH->lastInsertId();

    //enroll as stu if needed
		if (isset($CFG['GEN']['enrollonnewinstructor'])) {
			$valbits = array();
			$valvals = array();
			foreach ($CFG['GEN']['enrollonnewinstructor'] as $ncid) {
				$valbits[] = "(?,?)";
				array_push($valvals, $newuserid,$ncid);
			}
			$stm = $DBH->prepare("INSERT INTO imas_students (userid,courseid) VALUES ".implode(',',$valbits));
			$stm->execute($valvals);
		}

    //log new account
	$stm = $DBH->prepare("INSERT INTO imas_log (time, log) VALUES (:now, :log)");
	$stm->execute(array(':now'=>$now, ':log'=>"New Instructor Request: $newuserid:: Group: $newusergroupid, manually added by $userid"));
	
	$reqdata = array('added'=>$now, 'actions'=>array(array('by'=>$userid, 'on'=>$now, 'status'=>11, 'via'=>'batchcreate')));
	$stm = $DBH->prepare("INSERT INTO imas_instr_acct_reqs (userid,status,reqdate,reqdata) VALUES (?,11,?,?)");
	$stm->execute(array($newuserid, $now, json_encode($reqdata)));
	
    //copy courses
    $i = 5;
    while (isset($data[$i]) && $data[$i]!='' && intval($data[$i])>0) {
      if ($myrights == 100 || ($myspecialrights&32)==32) {
        $isoktocopy[$data[$i]] = true;
      } else if (!isset($isoktocopy[$data[$i]])) {
      	$query = "SELECT ic.copyrights,iu.groupid FROM imas_courses AS ic JOIN imas_users AS iu ON ic.ownerid=iu.id ";
      	$query .= "WHERE ic.id=?";
      	$stm = $DBH->prepare($query);
      	$stm->execute(array($data[$i]));
      	$row = $stm->fetch(PDO::FETCH_ASSOC);
      	if ($row!==false && ($row['copyrights']==2 || ($row['copyrights']==1 && $row['groupid']==$groupid))) {
      	  $isoktocopy[$data[$i]] = true;
      	} else {
      	  $isoktocopy[$data[$i]] = false;
      	}
      }
      $i++;
    }
    $i = 5;
    while (isset($data[$i]) && $data[$i]!='' && intval($data[$i])>0) {
      if (empty($isoktocopy[$data[$i]])) {
        echo "Skipping copying course {$data[$i]} - you don't have rights to copy this course without the enrollment key which is not supported by this batch process<br/>";
        $i++;
        continue;
      }
      echo "Copying course {$data[$i]}<br/>";
      $uid = $newuserid;
      $sourcecid = $data[$i];
      $blockcnt = 1;
      $itemorder = serialize(array());
      $DBH->beginTransaction();
      $query = "INSERT INTO imas_courses (name,ownerid,enrollkey,hideicons,picicons,allowunenroll,copyrights,msgset,toolset,showlatepass,itemorder,available,istemplate,deftime,deflatepass,theme,ltisecret,blockcnt) ";
      $query .= "SELECT name,:ownerid,enrollkey,hideicons,picicons,allowunenroll,copyrights,msgset,toolset,showlatepass,:itemorder,available,0,deftime,deflatepass,theme,'',1 ";
      $query .= "FROM imas_courses WHERE id=:sourceid";
      $stm = $DBH->prepare($query);
      $stm->execute(array(':ownerid'=>$uid, ':itemorder'=>$itemorder, ':sourceid'=>$sourcecid));
      $cid = $DBH->lastInsertId();
      //if ($myrights==40) {
        $stm = $DBH->prepare("INSERT INTO imas_teachers (userid,courseid) VALUES (:userid, :courseid)");
        $stm->execute(array(':userid'=>$uid, ':courseid'=>$cid));
      //}

      $query = "INSERT INTO imas_gbscheme (courseid,useweights,orderby,defaultcat,defgbmode,stugbmode,usersort) ";
      $query .= "SELECT :cid,useweights,orderby,defaultcat,defgbmode,stugbmode,usersort FROM imas_gbscheme WHERE courseid=:sourceid";
      $stm = $DBH->prepare($query);
      $stm->execute(array(':cid'=>$cid, ':sourceid'=>$sourcecid));

      $gbcats = array();
      $gb_cat_ins = null;
      $stm = $DBH->prepare("SELECT id,name,scale,scaletype,chop,dropn,weight,hidden,calctype FROM imas_gbcats WHERE courseid=:courseid");
      $stm->execute(array(':courseid'=>$sourcecid));
      while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        $frid = $row['id'];
        if ($gb_cat_ins===null) {
          $query = "INSERT INTO imas_gbcats (courseid,name,scale,scaletype,chop,dropn,weight,hidden,calctype) VALUES ";
          $query .= "(:courseid, :name, :scale, :scaletype, :chop, :dropn, :weight, :hidden, :calctype)";
          $gb_cat_ins = $DBH->prepare($query);
        }
        $gb_cat_ins->execute(array(':courseid'=>$cid, ':name'=>$row['name'], ':scale'=>$row['scale'], ':scaletype'=>$row['scaletype'],
          ':chop'=>$row['chop'], ':dropn'=>$row['dropn'], ':weight'=>$row['weight'], ':hidden'=>$row['hidden'], ':calctype'=>$row['calctype']));
        $gbcats[$frid] = $DBH->lastInsertId();
      }
      $copystickyposts = true;
      $stm = $DBH->prepare("SELECT itemorder,ancestors,outcomes,latepasshrs FROM imas_courses WHERE id=:id");
      $stm->execute(array(':id'=>$sourcecid));
      $r = $stm->fetch(PDO::FETCH_NUM);
      $items = unserialize($r[0]);
      $ancestors = $r[1];
      $outcomesarr = $r[2];
      $latepasshrs = $r[3];
      if ($ancestors=='') {
        $ancestors = intval($sourcecid);
      } else {
        $ancestors = intval($sourcecid).','.$ancestors;
      }
      $outcomes = array();

      $query = 'SELECT imas_questionset.id,imas_questionset.replaceby FROM imas_questionset JOIN ';
      $query .= 'imas_questions ON imas_questionset.id=imas_questions.questionsetid JOIN ';
      $query .= 'imas_assessments ON imas_assessments.id=imas_questions.assessmentid WHERE ';
      $query .= "imas_assessments.courseid=:courseid AND imas_questionset.replaceby>0";
      $stm = $DBH->prepare($query);
      $stm->execute(array(':courseid'=>$sourcecid));
      while ($row = $stm->fetch(PDO::FETCH_NUM)) {
        $replacebyarr[$row[0]] = $row[1];
      }

      if ($outcomesarr!='' && $outcomesarr!='b:0;') {
        $stm = $DBH->prepare("SELECT id,name,ancestors FROM imas_outcomes WHERE courseid=:courseid");
        $stm->execute(array(':courseid'=>$sourcecid));
        $out_ins_stm = null;
        while ($row = $stm->fetch(PDO::FETCH_NUM)) {
          if ($row[2]=='') {
            $row[2] = $row[0];
          } else {
            $row[2] = $row[0].','.$row[2];
          }
          if ($out_ins_stm===null) {
            $query = "INSERT INTO imas_outcomes (courseid,name,ancestors) VALUES ";
            $query .= "(:courseid, :name, :ancestors)";
            $out_ins_stm = $DBH->prepare($query);
          }
          $out_ins_stm->execute(array(':courseid'=>$cid, ':name'=>$row[1], ':ancestors'=>$row[2]));
          $outcomes[$row[0]] = $DBH->lastInsertId();
        }

        $outcomesarr = unserialize($outcomesarr);
        updateoutcomes($outcomesarr);
        $newoutcomearr = serialize($outcomesarr);
      } else {
        $newoutcomearr = '';
      }
      $removewithdrawn = true;
      $usereplaceby = "all";
      $newitems = array();
      copyallsub($items,'0',$newitems,$gbcats);
      doaftercopy($sourcecid);
      $itemorder = serialize($newitems);
      $stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder,blockcnt=:blockcnt,ancestors=:ancestors,outcomes=:outcomes,latepasshrs=:latepasshrs WHERE id=:id");
      $stm->execute(array(':itemorder'=>$itemorder, ':blockcnt'=>$blockcnt, ':ancestors'=>$ancestors, ':outcomes'=>$newoutcomearr, ':latepasshrs'=>$latepasshrs, ':id'=>$cid));
      //copy offline
      $offlinerubrics = array();
      $stm = $DBH->prepare("SELECT name,points,showdate,gbcategory,cntingb,tutoredit,rubric FROM imas_gbitems WHERE courseid=:courseid");
      $stm->execute(array(':courseid'=>$sourcecid));
      $gbi_ins_stm = null;
      while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        $rubric = $row['rubric'];
        unset($row['rubric']);
        if (isset($gbcats[$row['gbcategory']])) {
          $row['gbcategory'] = $gbcats[$row['gbcategory']];
        } else {
          $row['gbcategory'] = 0;
        }
        if ($gbi_ins_stm === null) {
          $query = "INSERT INTO imas_gbitems (courseid,name,points,showdate,gbcategory,cntingb,tutoredit) VALUES ";
          $query .= "(:courseid,:name,:points,:showdate,:gbcategory,:cntingb,:tutoredit)";
          $gbi_ins_stm = $DBH->prepare($query);
        }
        $row[':courseid'] = $cid;
        $gbi_ins_stm->execute($row);
        if ($rubric>0) {
          $offlinerubrics[$DBH->lastInsertId()] = $rubric;
        }
      }
      copyrubrics($offlinerubrics);


      $DBH->commit();

      $i++;
    }
  }

  echo '<p>Done. <a href="../admin/admin2.php">Admin page</a></p>';
} else {
  require("../header.php");
  $curBreadcrumb = "$breadcrumbbase <a href=\"$imasroot/admin/admin2.php\">Admin</a>\n";
  if ($_GET['from'] != 'admin') {
  	  $curBreadcrumb = $curBreadcrumb . " &gt; <a href=\"$imasroot/util/utils.php\">Utils</a> \n";
  }
  echo '<div class="breadcrumb">'.$curBreadcrumb.' &gt; Batch Create Instructors</div>';
  echo '<form enctype="multipart/form-data" method="post" action="'.$imasroot.'/util/batchcreateinstr.php">';
  echo '<p>This page lets you create instructor accounts from a CSV, and copy courses for them if desired</p>';
  echo '<p>Column Format:</p><ul>';
  echo '<li>1) username</li><li>2) temporary password</li><li>3) First Name</li>';
  echo '<li>4) Last Name</li><li>5) email</li>';
  echo '<li>Columns 6,7,etc. can be course IDs to create copies of for that instructor</li></ul>';
  if ($myrights == 100 || ($myspecialrights&32)==32) {
	  echo '<p>Group: <select name="groupid"><option value="-1">Select...</option>';
		$stm = $DBH->query("SELECT id,name FROM imas_groups ORDER BY name");
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			echo '<option value="'.Sanitize::onlyInt($row[0]).'">'.Sanitize::encodeStringForDisplay($row[1]).'</option>';
		}
	  echo '</select><br/>';
  } else {
  	  echo '<input type=hidden name=groupid value="'.Sanitize::onlyInt($groupid).'" />';
  }
  echo 'CSV file: <input type=file name=uploadedfile /><br/>';
  echo '<input type="submit" value="Go"/>';
  echo '</form>';
  require("../footer.php");
}


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
// Reads past the UTF-8 bom if it is there.
function fopen_utf8 ($filename, $mode) {
    $file = @fopen($filename, $mode);
    $bom = fread($file, 3);
    if ($bom != b"\xEF\xBB\xBF") {
        rewind($file);
    }
    return $file;
}
