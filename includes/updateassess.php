<?php
//IMathAS (c) David Lippman, Lumen Learning
//utility code for removing withdrawn questions from assessments 
//and replacing questions where replaceby exists.

//aidarr is array of assessment IDs, or course ID to do all
function updateassess($aidarr,$removewithdrawn,$doreplaceby) {
	//need to look up which assessments have withdrawn questions 
	//and/or replaceable questions
	//for replaceable questions, look up replacement id
	//pull itemorders, remove withdrawn or replace ids, update itemorder
	if (!$removewithdrawn && !$doreplaceby) { return 'No changes reqested';}
	
	if (is_array($aidarr)) {
		foreach ($aidarr as $k=>$v) {
			$aidarr[$k] = intval($v);
		}
	}
	if ($doreplaceby) {
		$query = "UPDATE imas_questions AS iq JOIN imas_questionset AS iqs ON iq.questionsetid=iqs.id ";
		if (!is_array($aidarr)) {
			$query .= "JOIN imas_assessments AS ia ON iq.assessmentid=ia.id ";
		}
		$query .= "SET iq.questionsetid=iqs.replaceby WHERE iqs.replaceby>0 ";
		if (is_array($aidarr)) {
			$query .= " AND iq.assessmentid IN (".implode(',',$aidarr).")";	
		} else {
			$query .= " AND ia.courseid='$aidarr'";
		}
		mysql_query($query) or die("Query failed : " . mysql_error());
		$replacedcnt = mysql_affected_rows();
	}
		
	if ($removewithdrawn) {
		$query = "SELECT iq.assessmentid,iq.id,iq.withdrawn FROM imas_questions AS iq ";
		if (!is_array($aidarr)) {
			$query .= "JOIN imas_assessments AS ia ON iq.assessmentid=ia.id ";
		}
		$query .= "WHERE iq.withdrawn>0";
	
		if (is_array($aidarr)) {
			$query .= " AND iq.assessmentid IN (".implode(',',$aidarr).")";	
		} else {
			$query .= " AND ia.courseid='$aidarr'";
		}
		$todoaid = array();
		$withdrawn = array();
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$todoaid[] = $row[0];
			if ($row[2]>0) {
				$withdrawn[$row[1]] = true;
			}
		}
		if (count($todoaid)==0) { return 'No changes to make';}
		
		$todoaid = array_unique($todoaid);
		
		$query = "SELECT id,itemorder FROM imas_assessments WHERE id IN (".implode(',',$todoaid).')';
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$items = explode(',',$row[1]);
			$newitems = array();
			foreach ($items as $k=>$q) {
				if (strpos($q,'~')!==false) {
					$sub = explode('~',$q);
					$newsub = array();
					$front = 0;
					if (strpos($sub[0],'|')!==false) {
						$newsub[] = array_shift($sub);
						$front = 1;
					}
					foreach ($sub as $sq) {
						if (!isset($withdrawn[$sq])) {
							$newsub[] = $sq;
						} 
					}
					if (count($newsub)==$front) {
						
					} else if (count($newsub)==$front+1) {
						$newitems[] = $newsub[$front];
					} else {
						$newitems[] = implode('~',$newsub);
					}
				} else {
					if (!isset($withdrawn[$q])) {
						$newitems[] = $q;	
					} 
				}
			}
			$newitemlist = implode(',', $newitems);
			$query = "UPDATE imas_assessments SET itemorder='$newitemlist' WHERE id={$row[0]}";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
	}
	
	$msg = '';
	if ($removewithdrawn) {
		if (count($withdrawn)>0) {
			$msg .= 'Removed '.count($withdrawn).' withdrawn questions. ';
		} else {
			$msg .= 'No withdrawn questions to remove. ';
		}
	}
	if ($doreplaceby) {
		if ($replacedcnt>0) {
			$msg .= 'Updated '.$replacedcnt.' questions. ';
		} else {
			$msg .= 'No questions to update. ';
		}
	}
	return $msg;
}
?>
