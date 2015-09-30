<?php
use app\components\AppConstant;
use app\components\AppUtility;
$name = $linkData['title'];
$text = $linkData['text'];
$points = $linkData['points'];
$toolparts = explode('~~',substr($text,8));
if (isset($toolparts[3])) {
    $gbcat = $toolparts[3];
    $cntingb = $toolparts[4];
    $tutoredit = $toolparts[5];
}
if (isset($params['clear']) && $isteacher)
{
    if (isset($params['confirm']))
    {
    } else
    {
        echo "<p>Are you SURE you want to clear all associated grades on this item from the gradebook?</p>";
        echo "<p><a href=\"edittoolscores.php?stu={$params['stu']}&gbmode={$params['gbmode']}&cid=$cid&lid=$lid&confirm=true\">Clear Scores</a>";
        echo " <a href=\"gradebook.php?stu={$params['stu']}&gbmode={$params['gbmode']}&cid=$cid\">Nevermind</a>";
        exit;
    }
}
echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$params['cid']}\">$coursename</a> ";
echo "&gt; <a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> ";
if ($params['stu'] > AppConstant::NUMERIC_ZERO) {
    echo "&gt; <a href=\"gradebook.php?stu={$params['stu']}&cid=$cid\">Student Detail</a> ";
} else if ($params['stu']==-1) {
    echo "&gt; <a href=\"gradebook.php?stu={$params['stu']}&cid=$cid\">Averages</a> ";
}
echo "&gt; External Tool Grades</div>";
echo "<div id=\"headerexttoolgrades\" class=\"pagetitle\"><h2>Modify External Tool Grades</h2></div>";
echo '<h3>'.$name.'</h3>'; ?>
 <form id="mainform" method=post action="edit-tool-score?stu=<?php echo $params['stu']?>&gbmode=<?php echo $params['gbmode']?>&cid=<?php echo $course-> id?>&lid=<?php echo $lid?>&uid=<?php echo $params['uid']?>">
 <div id="gradeboxes">
 <input type=button value="Expand Feedback Boxes" onClick="togglefeedback(this)">
<?php
if ($params['uid']=='all') { ?>
     <br/>
    <span class=form><?php AppUtility::t('Add/Replace to all grades')?>:</span>
    <span class=formright><input type=text size=3 id="toallgrade" onblur="this.value = doonblur(this.value);"/>
        <input type=button value="Add" onClick="sendtoall(0,0);"/>
        <input type=button value="Multiply" onclick="sendtoall(0,1)"/>
        <input type=button value="Replace" onclick="sendtoall(0,2)"/>
    </span><br class="form"/>
     <span class=form><?php AppUtility::t('Add/Replace to all feedback')?>:</span>
     <span class=formright><input type=text size=40 id="toallfeedback"/>
      <input type=button value="Append" onClick="sendtoall(1,0);"/>
         <input type=button value="Prepend" onclick="sendtoall(1,1)"/>
         <input type=button value="Replace" onclick="sendtoall(1,2)"/>
     </span><br class="form"/>
<?php }
echo '<div class="clear"></div>'; ?>
 <table id=myTable><thead><tr><th><?php AppUtility::t('Name')?></th>
<?php if ($hassection)
{ ?>
     <th><?php AppUtility::t('Section')?></th>
<?php } ?>
 <th><?php AppUtility::t('Grade')?></th><th><?php AppUtility::t('Feedback')?></th></tr></thead><tbody>
<?php foreach ($externalToolData as $row)
{
    if ($row['score']!=null) {
        $score[$row['userid']] = $row['score'];
    } else {
        $score[$row['userid']] = '';
    }
    $feedback[$row['userid']] = $row['feedback'];
}
foreach ($studentData as $row) {
    if ($row['locked'] > 0) {
        echo '<tr><td style="text-decoration: line-through;">';
    } else {
        echo '<tr><td>';
    }
    echo "{$row['LastName']}, {$row['FirstName']}";
    echo '</td>';
    if ($hassection) {
        echo "<td>{$row['section']}</td>";
    }
    if (isset($score[$row['id']])) {
        echo "<td><input type=\"text\" size=\"3\" autocomplete=\"off\" name=\"score[{$row['id']}]\" id=\"score{$row['id']}\" value=\"";
        echo $score[$row['id']];
    } else {
        echo "<td><input type=\"text\" size=\"3\" autocomplete=\"off\" name=\"newscore[{$row['id']}]\" id=\"score{$row['id']}\" value=\"";
    }
    echo "\" onkeypress=\"return onenter(event,this)\" onkeyup=\"onarrow(event,this)\" onblur=\"this.value = doonblur(this.value);\" />";
    echo "</td>";
    echo "<td><textarea cols=60 rows=1 id=\"feedback{$row['id']}\" name=\"feedback[{$row['id']}]\">{$feedback[$row['id']]}</textarea></td>";
    echo "</tr>";
}
echo "</tbody></table>";
if ($hassection) {
    echo "<script type='javascript'> initSortTable('myTable',Array('S','S',false,false),false);</script>";
}
?>
<div class=submit><input type=submit value="Submit"></div></div>
</form>


