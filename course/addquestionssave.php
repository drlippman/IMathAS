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
	$rawitemorder = mysql_result($result,0,0);
	$viddata = mysql_result($result,0,1);
	$itemorder = str_replace('~',',',$rawitemorder);
	$curitems = array();
	foreach (explode(',',$itemorder) as $qid) {
		if (strpos($qid,'|')===false) {
			$curitems[] = $qid;
		}
	}
	$current_intro_json = mysql_result($result,0,2);
	if (($intro=json_decode($current_intro_json,true))!==null) { //is json intro
		$current_intro = $intro[0];
		$text_segments = array_slice($intro,1); //remove initial Intro text
		$text_segments_json = json_encode($text_segments);
	} else {
		$current_intro = $current_intro_json; //it actually isn't JSON
		$text_segments = null;
		$text_segments_json = '[]';
	}
	$new_text_segments_json = stripslashes($_POST['text_order']);
file_put_contents("text_order_post_data",$new_text_segments_json);
	//insert the new_text_segments_json into 
	$composite_intro_json = json_encode(array($current_intro,json_decode($new_text_segments_json, true)) );
	
	$submitted = $_POST['order'];
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
		
		$qorder = explode(',',$_POST['order']);
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
	
	//store new itemorder and intro
	$query = "UPDATE imas_assessments SET itemorder='{$_POST['order']}',viddata='$viddata', intro='$composite_intro_json' WHERE id='$aid'";
	mysql_query($query) or die("Query failed : " . mysql_error()); 
	
	if (mysql_affected_rows()>0) {
		echo "OK";
	} else {
		echo "error: not saved";
	}
	
?>
