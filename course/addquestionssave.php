<?php
//IMathAS:  Save changes to addquestions submitted through AHAH
//(c) 2007 IMathAS/WAMAP Project
	require("../validate.php");
	$cid = $_GET['cid'];
	$aid = $_GET['aid'];
	if (!isset($teacherid)) {
		echo "error: validation";
	}
	$query = "SELECT itemorder,viddata,intro FROM imas_assessments WHERE id='$aid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error()); 
	list($rawitemorder,$viddata,$current_intro_json) = mysql_fetch_row($result);
	
	$itemorder = str_replace('~',',',$rawitemorder);
	$curitems = array();
	foreach (explode(',',$itemorder) as $qid) {
		if (strpos($qid,'|')===false) {
			$curitems[] = $qid;
		}
	}
	
	if (($intro=json_decode($current_intro_json,true))!==null) { //is json intro
		$current_intro = $intro[0];
	} else {
		$current_intro = $current_intro_json; //it actually isn't JSON
	}
	$new_text_segments_json = json_decode(stripslashes($_POST['text_order']),true);
	if (count($new_text_segments_json)>0) {
		array_unshift($new_text_segments_json, $current_intro);
		$new_intro = json_encode($new_text_segments_json);
	} else {
		$new_intro = $current_intro;
	}
	
	$submitted = $_REQUEST['order'];
	$submitted = str_replace('~',',',$submitted);
	$newitems = array();
	foreach (explode(',',$submitted) as $qid) {
		if (strpos($qid,'|')===false) {
			$newitems[] = $qid;
		}
	}
	$toremove = array_diff($curitems,$newitems);
	
	if ($viddata != '') {
		$viddata = unserialize($viddata);
		$qorder = explode(',',$rawitemorder);
		$qidbynum = array();
		for ($i=0;$i<count($qorder);$i++) {
			if (strpos($qorder[$i],'~')!==false) {
				$qids = explode('~',$qorder[$i]);
				if (strpos($qids[0],'|')!==false) { //pop off nCr
					$qidbynum[$i] = $qids[1];
				} else {
					$qidbynum[$i] = $qids[0];
				}
			} else {
				$qidbynum[$i] = $qorder[$i];
			}
		}
		
		$qorder = explode(',',$_REQUEST['order']);
		$newbynum = array();
		for ($i=0;$i<count($qorder);$i++) {
			if (strpos($qorder[$i],'~')!==false) {
				$qids = explode('~',$qorder[$i]);
				if (strpos($qids[0],'|')!==false) { //pop off nCr
					$newbynum[$i] = $qids[1];
				} else {
					$newbynum[$i] = $qids[0];
				}
			} else {
				$newbynum[$i] = $qorder[$i];
			}
		}
		
		$qidbynumflip = array_flip($qidbynum);
		
		$newviddata = array();
		$newviddata[0] = $viddata[0]; 
		for ($i=0;$i<count($newbynum);$i++) {   //for each new item
			$oldnum = $qidbynumflip[$newbynum[$i]];   
			$found = false; //look for old item in viddata
			for ($j=1;$j<count($viddata);$j++) {
				if (isset($viddata[$j][2]) && $viddata[$j][2]==$oldnum) {
					//if found, copy data, and any non-question data following
					$new = $viddata[$j];
					$new[2] = $i;  //update question number;
					$newviddata[] = $new;
					$j++;
					while (isset($viddata[$j]) && !isset($viddata[$j][2])) {
						$newviddata[] = $viddata[$j];
						$j++;
					}
					$found = true;
					break;
				}
			}
			if (!$found) {
				//item was not found in viddata.  it should have been.
				//count happen if the first item in a group was removed, perhaps
				//Add a blank item
				$newviddata[] =  array('','',$i);
			}
		}
		//any old items will not get copied.
		$viddata = addslashes(serialize($newviddata));
				
		
	}
	
	//delete any removed questions
	$query = "DELETE FROM imas_questions WHERE id IN ('".implode("','",$toremove)."')";
	mysql_query($query) or die("Query failed : " . mysql_error()); 
	
	//store new itemorder
	$query = "UPDATE imas_assessments SET itemorder='{$_REQUEST['order']}',viddata='$viddata',intro='".addslashes($new_intro)."' WHERE id='$aid'";
	mysql_query($query) or die("Query failed : " . mysql_error()); 
	
	if (mysql_affected_rows()>0) {
		echo "OK";
	} else {
		echo "error: not saved";
	}
	
?>
