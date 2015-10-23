<?php
use app\components\AppUtility;
use app\components\AppConstant;
$this->title = AppUtility::t('Add Question', false);
$this->params['breadcrumbs'][] = $this->title;

if (isset($params['aid'])) {
    $title = AppUtility::t('Add/Remove Questions', false);
    $url = AppUtility::getHomeURL().'question/question/add-questions?aid='.$params['aid'].'&cid='.$params['cid'];
} else {
    if ($params['cid']=="admin") {
        $title = AppUtility::t('Manage Question Set', false);
        $url = AppUtility::getHomeURL().'question/question/manage-question-set?cid=admin';
        $isAdmin = true;
    } else {
        $title = AppUtility::t('Manage Question Set', false);
        $url = AppUtility::getHomeURL().'question/question/manage-question-set?cid='.$params['cid'];
    }
}
$placeinhead = '<style type="text/css">
  .txted {
    padding-left: 1px;
    padding-right: 1px;
    margin-left: 0px;
    }
 </style>';
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name, $title ], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid='. $params['cid'], $url] ]); ?>
</div>
    <div id="headermoddataset" class="pagetitle">
    <h2><?php $addMod ?>Tutorial Question</h2>
</div>
<div class="col-md-12 tab-content shadowBox padding-top-fifteen padding-bottom-twenty-five margin-top-ten">

<?php

if ($editMsg != '' || $_GET['id']!='new') {
    echo '<p>'.$editMsg;
    if ($id!='new') {
        echo ' <a href="mod-data-set?cid='.$cid.'&id='.$id.'">Open in the regular question editor</a>';
    } else {
        echo ' <a href="mod-data-set?cid='.$cid.'">Open in the regular question editor</a>';
    }
    echo '</p>';
}
if ($line['deleted']==1) {
    echo '<p style="color:red;">This question has been marked for deletion.  This might indicate there is an error in the question. ';
    echo 'It is recommended you discontinue use of this question when possible</p>';
}

if (isset($inUseCnt) && $inUseCnt>0) {
    echo '<p style="color:red;">This question is currently being used in ';
    if ($inUseCnt>1) {
        echo $inUseCnt.' assessments that are not yours.  ';
    } else {
        echo 'one assessment that is not yours.  ';
    }
    echo 'In consideration of the other users, if you want to make changes other than minor fixes to this question, consider creating a new version of this question instead.  </p>';

}
if (isset($_GET['qid'])) {
    echo "<p><a href=\"mod-data-set?id={$_GET['id']}&cid=$cid&aid={$_GET['aid']}&template=true&makelocal={$_GET['qid']}\">Template this question</a> for use in this assessment.  ";
    echo "This will let you modify the question for this assessment only without affecting the library version being used in other assessments.</p>";
}
if (!$myQ) {
    echo "<p>This question is not set to allow you to modify the code.  You can only view the code and make additional library assignments</p>";
}

?>

<script type="text/javascript">

</script>

<form enctype="multipart/form-data" method=post action="mod-tutorial-question?process=true<?php
if (isset($cid)) {
    echo "&cid=$cid";
}
if (isset($_GET['aid'])) {
    echo "&aid={$_GET['aid']}";
}
if (isset($_GET['id']) && !isset($_GET['template'])) {
    echo "&id={$_GET['id']}";
}
if (isset($_GET['template'])) {
    echo "&templateid={$_GET['id']}";
}
if (isset($_GET['makelocal'])) {
    echo "&makelocal={$_GET['makelocal']}";
}
if ($fromPot==1) {
    echo "&frompot=1";
}
?>">

    <p>
        Description:<BR>
        <textarea cols=60 rows=4 name=description <?php if (!$myQ) echo "readonly=\"readonly\"";?>><?php echo $line['description'];?></textarea>
    </p>
    <p>
        Author: <?php echo $line['author']; ?> <input type="hidden" name="author" value="<?php echo $author; ?>">
    </p>
    <p>
        <?php
        if (!isset($line['ownerid']) || isset($_GET['template']) || $line['ownerid']==$userId || ($line['userights']==3 && $line['groupid']==$groupId) || $isAdmin || ($isGrpAdmin && $line['groupid']==$groupId)) {
            echo "Use Rights <select name=userights>\n";
            echo "<option value=\"0\" ";
            if ($line['userights']==0) {echo "SELECTED";}
            echo ">Private</option>\n";
            echo "<option value=\"2\" ";
            if ($line['userights']==2) {echo "SELECTED";}
            echo ">Allow use, use as template, no modifications</option>\n";
            echo "<option value=\"3\" ";
            if ($line['userights']==3) {echo "SELECTED";}
            echo ">Allow use by all and modifications by group</option>\n";
            echo "<option value=\"4\" ";
            if ($line['userights']==4) {echo "SELECTED";}
            echo ">Allow use and modifications by all</option>\n";
        }
        ?>
        </select>
    </p>
    <script>
        var curlibs = '<?php echo $inLibs;?>';
        var locklibs = '<?php echo $lockLibs;?>';
        function libselect() {
            window.open('libtree.php?libtree=popup&cid=<?php echo $cid;?>&selectrights=1&libs='+curlibs+'&locklibs='+locklibs,'libtree','width=400,height='+(.7*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));
        }
    </script>
    <p>
        My library assignments: <span id="libnames"><?php echo $lNames;?></span><input type=hidden name="libs" id="libs" size="10" value="<?php echo $inLibs;?>">
        <input type=button value="Select Libraries" onClick="libselect()">
    </p>

    <p>This question has
        <?php

        AppUtility::writeHtmlSelect("nparts",range(1,10),range(1,10), $nParts,null,null,'onchange="changenparts(this)"');
        ?>
        parts.</p>

    <?php
    for ($n=0;$n<10;$n++) {
        if (!isset($qParts[$n])) { $qParts[$n] = 4;}
        echo '<div id="partwrapper'.$n.'"';
        if ($n>=$nParts) {echo ' style="display:none;"';};
        echo '>';

        echo '<h4>Part '.($n).' Question</h4>';
        echo '<p>This part is ';
        AppUtility::writeHtmlSelect("qtype$n",$qTypeVal,$qTypeLbl, $qType[$n], null, null, 'onchange="changeqtype('.$n.',this)"');

        echo ' with ';

        if ($qType[$n]!='essay') { // if it has question parts
            echo '<span class="hasparts'.$n.'">';
        } else {
            echo '<span class="hasparts'.$n.'" style="display:none;">';
        }

        AppUtility::writeHtmlSelect("qparts$n",range(1,6),range(1,6), $qParts[$n],null,null,'onchange="changeqparts('.$n.',this)"');

        //choices
        echo '<span id="qti'.$n.'mc" ';
        if (isset($qType[$n]) && $qType[$n]!='choices') {echo ' style="display:none;"';};
        echo '> choices.  Display those ';
        AppUtility::writeHtmlSelect("qdisp$n",$dispVal,$dispLbl, $qDisp[$n]);
        echo '. Shuffle: ';
        AppUtility::writeHtmlSelect("qshuffle$n",$shuffleVal,$shuffleLbl, $qShuffle[$n]);
        echo '</span>';

        //numeric
        echo '<span id="qti'.$n.'num" ';
        if ($qType[$n]!='number') {echo ' style="display:none;"';};
        echo '> values that will receive feedback. Use a(n) ';
        AppUtility::writeHtmlSelect("qtol$n",$qTolVal,$qTolLbl, $qTol[$n]);
        echo ' tolerance of <input autocomplete="off" name="tol'.$n.'" type="text" size="5" value="'.(isset($qTold[$n])?$qTold[$n]:0.001).'"/>.';
        echo ' Box size: <input autocomplete="off" name="numboxsize'.$n.'" type="text" size="2" value="'.(isset($answerBoxSize[$n])?$answerBoxSize[$n]:5).'"/>.';
        echo '</span>';
        echo '</span>'; // end question parts span
        //TODO:  Add essay question options

        echo '<span id="essayopts'.$n.'" ';
        if ($qType[$n]!='essay') {echo ' style="display:none;"';};
        echo '> <input autocomplete="off" name="essayrows'.$n.'" type="text" size="2" value="'.(isset($answerBoxSize[$n])?$answerBoxSize[$n]:3).'"/> rows. ';
        echo '<input type="checkbox" name="useeditor'.$n.'" ';
        if (isset($displayFormat[$n]) && $displayFormat[$n]=='editor') {
            echo 'checked="checked"';
        }
        echo '/> Use editor.  ';
        echo '<input type="checkbox" name="takeanything'.$n.'" ';
        if (isset($scoreMethod[$n]) && $scoreMethod[$n]=='takeanything') {
            echo 'checked="checked"';
        }
        echo '/> Give credit for any answer.  ';


        echo '</span>';
        echo '</p>';

        if ($qType[$n]!='essay') { // if it has question parts
            echo '<div class="hasparts'.$n.'">';
        } else {
            echo '<div class="hasparts'.$n.'" style="display:none;">';
        }
        echo '<table class="choicetbl"><thead><tr><th>Correct</th><th id="choicelbl'.$n.'">'.(($qType[$n]=='choices')?"Choice":"Answer").'</th><th>Feedback</th><th>Partial Credit (0-1)</th></tr></thead><tbody>';
        for ($i=0;$i<6;$i++) {
            echo '<tr id="qc'.$n.'-'.$i.'" ';
            if ($i>=$qParts[$n]) {echo ' style="display:none;"';};
            echo '><td><input type="radio" name="ans'.$n.'" value="'.$i.'" ';
            if ($i==$answer[$n]) {echo 'checked="checked"';}
            echo '/></td>';
            echo '<td><input autocomplete="off" id="txt'.$n.'-'.$i.'" name="txt'.$n.'-'.$i.'" type="text" size="60" value="'.(isset($questions[$n][$i])?AppUtility::prepd($questions[$n][$i]):"").'"/><input type="button" class="txted" value="E" onclick="popupeditor(\'txt'.$n.'-'.$i.'\')"/></td>';
            echo '<td><input autocomplete="off" id="fb'.$n.'-'.$i.'" name="fb'.$n.'-'.$i.'" type="text" size="60" value="'.(isset($feedBackTxt[$n][$i])?AppUtility::prepd($feedBackTxt[$n][$i]):"").'"/><input type="button" class="txted" value="E" onclick="popupeditor(\'fb'.$n.'-'.$i.'\')"/></td>';
            echo '<td><input autocomplete="off" id="pc'.$n.'-'.$i.'" name="pc'.$n.'-'.$i.'" type="text" size="3" value="'.(isset($partial[$n][$i])?$partial[$n][$i]:"").'"/></td>';

            echo '</tr>';
        }
        echo '<tr id="qc'.$n.'-def" ';
        if ($qType[$n]!="number") {echo ' style="display:none;"';};
        echo '><td colspan="4">Default feedback for incorrect answers: ';
        echo '<input autocomplete="off" id="fb'.$n.'-def" name="fb'.$n.'-def" type="text" size="60" value="'.(isset($feedBackTxtDef[$n])?AppUtility::prepd($feedBackTxtDef[$n]):"").'"/><input type="button" class="txted" value="E" onclick="popupeditor(\'fb'.$n.'-def\')"/></td></tr>';
        echo '</tbody></table>';
        echo '</div>'; //end hasparts holder div
        echo '<div id="essay'.$n.'wrap" ';
        if ($qType[$n]!='essay') {echo ' style="display:none;"';};
        echo '> Feedback to show: <br/>';
        echo '<textarea id="essay'.$n.'-fb" name="essay'.$n.'-fb" cols="80" rows="4">';
        if (isset($feedBackTxtEssay[$n])) { echo AppUtility::prepd($feedBackTxtEssay[$n]);}
        echo '</textarea><input type="button" class="txted" value="E" onclick="popupeditor(\'essay'.$n.'-fb\')"/>';
        echo '</div>'; //end essaywrap div
        echo '</div>'; //end partwrapper div
    }

    echo '<h4>Hints</h4>';
    echo '<p>This question has ';
    AppUtility::writeHtmlSelect("nhints",range(0,4),range(0,4), $nHints,null,null,'onchange="changehparts(this)"');
    echo 'hints.</p>';
    for ($n=0;$n<4;$n++) {
        echo '<p id="hintwrapper'.$n.'"';
        if ($n>=$nHints) {echo ' style="display:none;"';};
        echo '>Hint '.($n+1).':';
        echo '<input autocomplete="off" id="hint'.$n.'" name="hint'.$n.'" type="text" size="80" value="'.(isset($hintText[$n])?AppUtility::prepd($hintText[$n]):"").'"/><input type="button" class="txted" value="E" onclick="popupeditor(\'hint'.$n.'\')"/></p>';
    }

    echo '<h4>Question Text</h4>';
    echo '<p>In the question text, enter <span id="anstipsingle" ';
    if ($nParts!=1) {echo 'style="display:none;" ';}
    echo '><b>$answerbox</b> to place the question list into the question.  Enter <b>$feedback</b> to indicate where the feedback should be displayed.</span> <span id="anstipmult" ';
    if ($nParts==1) {echo 'style="display:none;" ';}
    echo '><b>$answerbox[0]</b> to place the question list for Part 0, <b>$answerbox[1]</b> to place the question list for Part 1, and so on.  Similarly, ';
    echo 'enter <b>$feedback[0]</b> to indicate where the feedback for Part 0 should be displayed, and so on.</span></p>';

    ?>

    <div class=editor>
        <textarea cols="60" rows="20" id="text" name="text" style="width: 100%"><?php echo str_replace(array(">","<"),array("&gt;","&lt;"),$qText);?></textarea>
    </div>

    <div class="editor" id="GB_window" style="display:none; position: absolute; height: auto;">
        <div id="GB_caption" style="cursor:move;";><span style="float:right;"><span class="pointer clickable" onclick="GB_hide()">[X]</span></span> Edit Text</div>
        <textarea cols="60" rows="6" id="popuptxt" name="popuptxt" style="width: 100%"></textarea>
        <input type="button" value="Save" onclick="popuptxtsave()"/>
    </div>
    <p><input type="submit" value="Save and Test"/></p>
    <p>&nbsp;</p>

</form>

</div>