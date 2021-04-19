<?php
/*
 * Update S3 links to use region-agnostic addressing
 */

$DBH->beginTransaction();
if (isset($GLOBALS['AWSkey']) && isset($GLOBALS['AWSbucket'])) {
	$AWSbucket = $GLOBALS['AWSbucket'];
	$AWSbucket = preg_replace('/[^\w_\-]/','',$AWSbucket); //make sure it's safe
	
	$query = "UPDATE imas_questionset SET qtext=REPLACE(qtext, 'https://s3.amazonaws.com/$AWSbucket/', 'https://$AWSbucket.s3.amazonaws.com/')";
 	$res = $DBH->query($query);
 	if ($res===false) {
		 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
		 $DBH->rollBack();
		 return false;
	}
	
	$query = "UPDATE imas_inlinetext SET text=REPLACE(text, 'https://s3.amazonaws.com/$AWSbucket/', 'https://$AWSbucket.s3.amazonaws.com/')";
 	$res = $DBH->query($query);
 	if ($res===false) {
		 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
		 $DBH->rollBack();
		 return false;
	}
	$query = "UPDATE imas_drillassess SET summary=REPLACE(summary, 'https://s3.amazonaws.com/$AWSbucket/', 'https://$AWSbucket.s3.amazonaws.com/')";
 	$res = $DBH->query($query);
 	if ($res===false) {
		 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
		 $DBH->rollBack();
		 return false;
	}
	$query = "UPDATE imas_wikis SET description=REPLACE(description, 'https://s3.amazonaws.com/$AWSbucket/', 'https://$AWSbucket.s3.amazonaws.com/')";
 	$res = $DBH->query($query);
 	if ($res===false) {
		 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
		 $DBH->rollBack();
		 return false;
	}

	$query = "UPDATE imas_linkedtext SET text=REPLACE(text, 'https://s3.amazonaws.com/$AWSbucket/', 'https://$AWSbucket.s3.amazonaws.com/'),";
	$query .= "summary=REPLACE(summary, 'https://s3.amazonaws.com/$AWSbucket/', 'https://$AWSbucket.s3.amazonaws.com/')";
 	$res = $DBH->query($query);
 	if ($res===false) {
		 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
		 $DBH->rollBack();
		 return false;
	}
	
	$query = "UPDATE imas_assessments SET intro=REPLACE(intro, 'https://s3.amazonaws.com/$AWSbucket/', 'https://$AWSbucket.s3.amazonaws.com/'),";
	$query .= "summary=REPLACE(summary, 'https://s3.amazonaws.com/$AWSbucket/', 'https://$AWSbucket.s3.amazonaws.com/')";
 	$res = $DBH->query($query);
 	if ($res===false) {
		 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
		 $DBH->rollBack();
		 return false;
	}

	$query = "UPDATE imas_assessment_sessions SET lastanswers=REPLACE(lastanswers, 'https://s3.amazonaws.com/$AWSbucket/', 'https://$AWSbucket.s3.amazonaws.com/'),";
	$query .= "bestlastanswers=REPLACE(bestlastanswers, 'https://s3.amazonaws.com/$AWSbucket/', 'https://$AWSbucket.s3.amazonaws.com/') ";
 	$res = $DBH->query($query);
 	if ($res===false) {
		 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
		 $DBH->rollBack();
		 return false;
	}
	$query = "UPDATE imas_forums SET description=REPLACE(description, 'https://s3.amazonaws.com/$AWSbucket/', 'https://$AWSbucket.s3.amazonaws.com/'),";
	$query .= "postinstr=REPLACE(postinstr, 'https://s3.amazonaws.com/$AWSbucket/', 'https://$AWSbucket.s3.amazonaws.com/'),";
	$query .= "replyinstr=REPLACE(replyinstr, 'https://s3.amazonaws.com/$AWSbucket/', 'https://$AWSbucket.s3.amazonaws.com/')";
 	$res = $DBH->query($query);
 	if ($res===false) {
		 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
		 $DBH->rollBack();
		 return false;
	}
	
	$query = "UPDATE imas_forum_posts SET message=REPLACE(message, 'https://s3.amazonaws.com/$AWSbucket/', 'https://$AWSbucket.s3.amazonaws.com/')";
 	$res = $DBH->query($query);
 	if ($res===false) {
		 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
		 $DBH->rollBack();
		 return false;
	}

	$query = "UPDATE imas_msgs SET message=REPLACE(message, 'https://s3.amazonaws.com/$AWSbucket/', 'https://$AWSbucket.s3.amazonaws.com/')";
 	$res = $DBH->query($query);
 	if ($res===false) {
		 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
		 $DBH->rollBack();
		 return false;
	}
	
 	echo '<p>AWS S3 links updated to region-agnostic format</p>';
} else {
	echo '<p>AWS S3 not used - no changes needed in this migration</p>';
}

if ($DBH->inTransaction()) { $DBH->commit(); }
return true;
