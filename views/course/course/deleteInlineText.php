<?php
use app\components\AppUtility;
?>
<h3><?php echo $inline->title; ?></h3>
Are you SURE you want to delete this text item?
<form method="post" action="<?php AppUtility::getURLFromHome('course', 'course/delete-inline-text?id ='.$inline->id.'&courseId='.$course->id)?>">
<p><input type=submit name="submitbtn" class="btn btn-primary" value="Yes, Delete">
<input type=button value="Nevermind" class="secondarybtn" onClick="window.location='course.php?cid=<?php echo $cid ?>'"></p>
</form>