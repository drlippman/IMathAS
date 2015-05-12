<?php
use yii\helpers\Html;
use app\components\AppUtility;

?>
<?php echo $this->render('_toolbar');
?>
<!--    Display assessment name-->
<h2><?php echo $assessments->name ?></h2>
<input type="hidden" id="timerlimit" name="time" value="<?php echo abs($assessments->timelimit)?>">

<html>
<!--    Show total time and remaining time-->
<div class=right id=timelimitholder>
<?php
        /*Conversion into hour, minute and seconds*/
    $hour = (floor(abs($assessments->timelimit)/3600) < 10) ? '0'+floor(abs($assessments->timelimit)/3600) : floor(abs($assessments->timelimit)/3600);
    $min = floor((abs($assessments->timelimit)%3600)/60);
?>
    <span id="timercontent"><b>Timelimit : <?php echo $hour .' hour, ' .$min .' minutes.'?></b>
        <span id="timerwrap"><b>
            <span id='timer'></span>

            remaining.</span>
    </b></span>

</div>
<div class=intro>

    <p>Total Points Possible:10</p>
</div>
<a href="#beginquestions">
    <img class=skipnav src="/IMathAS/img/blank.gif" alt="Skip Navigation"/>
</a>

<div class="navbar needed">
    <h4>Questions</h4>
    <?php
    $grade = 0; $totalpoints = 0;

    foreach($questions as $key => $question) {

    $totalpoints += $question->points; /*Total possible points*/
    $grade += $question->points; //Grade
    ?>
<!--        Display left side: question list-->
    <ul class=qlist>
        <li>

<!--            <span class=current>-->
                <img alt="untried" src="/IMathAS/img/te_blue_arrow.png"/>
                <a href="<?php echo AppUtility::getURLFromHome('course', 'course/show-assessment?to=' . $question->id) ?>">Q <?php echo $key+1?></a> (0/<?php echo $question->points ?>)
<!--            </span>-->
        </li>
    </ul>
    <?php }?>
    <br />
<!--    Display total points: Grade-->
    <p>Grade: 0/<?php echo $grade?></p><p><br /><br />
        <a href="#" onclick="window.open('/IMathAS/assessment/printtest.php','printver','width=400,height=300,toolbar=1,menubar=1,scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));return false;">
            Print Version
        </a>
    </p>
</div>

<div class=inset>
    <form id="qform" method="post" enctype="multipart/form-data" action="showtest.php?action=skip&amp;score=0"
          onsubmit="return doonsubmit(this)">

        <input type="hidden" name="asidverify" value="1"/>
        <input type="hidden" name="disptime" value="1431096254"/>
        <input type="hidden" name="isreview" value="0"/>
        <a name="beginquestions"></a>

        <div class="question">
            <?php foreach($questionSets as $key => $questionSet) {?>
            <div> <?php echo $questionSet->qtext?>
            </div>
            <?php }?>
            <div class="toppad">
                <input onfocus="showehdd('qn0','Enter a whole or decimal number','0-0')" onblur="hideeh()"
                       onclick="reshrinkeh('qn0')" class="text " type="text" size="20" name=qn0 id=qn0 value=""
                       autocomplete="off"/>
            </div>

            <div>
                <p class="tips" style="display:none;">Box 1: <span id="tips0-0">Enter your answer as a whole or decimal number.  Examples: 3, -4, 5.5<br/>Enter DNE for Does Not Exist, oo for Infinity</span>
                </p>
            </div>
        </div>
        <div class="review clearfix">
        <span style="float:right;font-size:70%">
            <a target="license" href="/IMathAS/course/showlicense.php?id=36">License</a>
        </span>Points possible: <?php echo $question->points?><br/>This is attempt 1 of <?php echo $question->id?>.
            <input type=hidden id="verattempts" name="verattempts" value="0"/>
        </div>
        <input type="submit" class="btn" value="Submit"/>
    </form>
</div>
<div class="clear"></div>
</div>
<div class="footerwrapper"></div>
</div>
<div id="ehdd" class="ehdd">
    <span id="ehddtext">

    </span>
    <span onclick="showeh(curehdd);" style="cursor:pointer;">[more..]
    </span>
</div>
<div id="eh" class="eh">

</div>
</body>
</html>
<script type="text/javascript">
    $(document).ready(function(){
        var timer = $('#timerlimit').val();
        window.onload = CreateTimer("timer",timer);
    });
</script>