<?php
use app\components\AppUtility;
use app\components\AssessmentUtility;

if ($overwriteBody==1) {
    echo $body;
} else {

    ?>
    <script type="text/javascript">
        function nextpage() {
            var aid = document.getElementById('aidselect').value;
            var togo = '<?php echo $addr; ?>&aid='+aid;
            window.location = togo;
        }
    </script>


    <div class=breadcrumb><?php echo $curBreadcrumb ?></div>
    <div id="headerexception" class="pagetitle"><h2>Make Start/Due Date Exception</h2></div>

    <?php
        echo '<h3>'.$stuname.'</h3>';
    echo $page_isExceptionMsg;
    echo '<p><span class="form">Assessment:</span><span class="formright">';
     AssessmentUtility::writeHtmlSelect ("aidselect",$page_courseSelect['val'],$page_courseSelect['label'],$params['aid'],"Select an assessment","", " onchange='nextpage()'");
    echo '</span><br class="form"/></p>';

    if (isset($params['aid']) && $params['aid']!='') {
         ?>
        <form method=post action="exception?cid=<?php echo $course->id ?>&aid=<?php echo $params['aid'] ?>&uid=<?php echo $params['uid'] ?>&asid=<?php echo $asid;?>&from=<?php echo $from;?>">
            <span class=form>Available After:</span>
		<span class=formright>
			<input type=text size=10 name=sdate value="<?php echo $sdate ?>"> 
			<a href="#" onClick="displayDatePicker('sdate', this); return false">
                <img src="../img/cal.gif" alt="Calendar"/></A>
			at <input type=text size=10 name=stime value="<?php echo $stime ?>">
		</span><BR class=form>
            <span class=form>Available Until:</span>
		<span class=formright>
			<input type=text size=10 name=edate value="<?php echo $edate ?>">
			<a href="#" onClick="displayDatePicker('edate', this); return false">
                <img src="../img/cal.gif" alt="Calendar"/></A>
			at <input type=text size=10 name=etime value="<?php echo $etime ?>">
		</span><BR class=form>
            <span class="form"><input type="checkbox" name="forceregen"/></span>
		<span class="formright">Force student to work on new versions of all zquestions?  Students 
		   will keep any scores earned, but must work new versions of questions to improve score.</span><br class="form"/>
            <span class="form"><input type="checkbox" name="eatlatepass"/></span>
		<span class="formright">Deduct <input type="input" name="latepassn" size="1" value="1"/> LatePass(es).  
		   Student currently has <?php echo $latepasses;?> latepasses.</span><br class="form"/>
            <span class="form"><input type="checkbox" name="waivereqscore"/></span>
            <span class="formright">Waive "show based on an another assessment" requirements, if applicable.</span><br class="form"/>
            <div class=submit><input type=submit value="<?php echo $savetitle;?>"></div>
        </form>

    <?php
    }
}

?>
