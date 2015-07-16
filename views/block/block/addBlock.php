<?php
use app\components\AssessmentUtility;
use kartik\time\TimePicker;
use kartik\date\DatePicker;
$this->title = 'Add Block';
$this->params['breadcrumbs'][] = $this->title;
?>
<h3><strong>ADD BLOCK</strong></h3>
    <form method=post action="create-block?courseId=<?php echo $courseId; if(isset($block)){echo "&block=$block";} if(isset($toTb)){echo "&toTb=$toTb";}?>">
        <span class=form>Title: </span>
        <span class=formright><input type=text size=60 name=title value="<?php echo str_replace('"','&quot;',$defaultBlockData['title']);?>" ></span>
        <BR class=form>
        <div>
            <span class=form>Show:</span>
		<span class=formright>
			<input type=radio name="avail" value="0" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['avail'], 0); ?>
                   onclick="document.getElementById('datediv').style.display='none';"/>Hide<br/>
			<input type=radio name="avail" value="1" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['avail'], 1); ?>
                   onclick="document.getElementById('datediv').style.display='block';" />Show by Dates<br/>
			<input type=radio name="avail" value="2" <?php AssessmentUtility:: writeHtmlChecked($defaultBlockData['avail '], 2); ?>
                   onclick="document.getElementById('datediv').style.display='none'; "/>Show Always<br/>
		</span><br class="form"/>

            <!--Show by dates-->
            <div id="datediv" style="display:<?php echo ($forum['avail'] == 1) ? "block" : "none"; ?>">

                <span class=form>Available After:</span>
		        <span class=formright>
			        <input type=radio name="available-after" value="0" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['startDate'], '0', 0) ?>/>Always until end date<br/>
                    <input type=radio name="available-after" value="1" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['startDate'], '1', 1) ?>/>Now<br/>
			        <input type=radio name="available-after" class="pull-left" value="sdate" <?php echo AssessmentUtility::writeHtmlChecked($defaultBlockData['startDate'], '0', 1) ?>/>
                    <?php
                    echo '<div class = "pull-left col-lg-4 time-input">';
                    echo DatePicker::widget([
                        'name' => 'sdate',
                        'type' => DatePicker::TYPE_COMPONENT_APPEND,
                        'value' => date("m/d/Y"),
                        'removeButton' => false,
                        'pluginOptions' => [
                            'autoclose' => true,
                            'format' => 'mm/dd/yyyy']
                    ]);
                    echo '</div>'; ?>
                    <?php
                    echo '<label class="end pull-left col-lg-1"> at </label>';
                    echo '<div class="pull-left col-lg-6">';

                    echo TimePicker::widget([
                        'name' => 'stime',
                        'value' => time(),
                        'pluginOptions' => [
                            'showSeconds' => false,
                            'class' => 'time'
                        ]
                    ]);
                    echo '</div>'; ?>
		        </span><BR class=form>

                <span class=form>Available Until:</span>
		        <span class=formright>
			        <input type=radio name="available-until"
                           value="2000000000" <?php echo AssessmentUtility::writeHtmlChecked($defaultBlockData['endDate'], '2000000000', 0) ?>/>
                        Always after start date<br/>
                        <input type=radio name="available-until" class="pull-left"
                               value="edate" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['endDate'], '2000000000', 1) ?>/>
                    <?php
                    echo '<div class = "pull-left col-lg-4 time-input">';
                    echo DatePicker::widget([
                        'name' => 'edate',
                        'type' => DatePicker::TYPE_COMPONENT_APPEND,
                        'value' => date("m/d/Y"),
                        'removeButton' => false,
                        'pluginOptions' => [
                            'autoclose' => true,
                            'format' => 'mm/dd/yyyy']
                    ]);
                    echo '</div>'; ?>
                    <?php
                    echo '<label class="end pull-left col-lg-1"> at </label>';
                    echo '<div class="pull-left col-lg-6">';

                    echo TimePicker::widget([
                        'name' => 'etime',
                        'value' => time(),
                        'pluginOptions' => [
                            'showSeconds' => false,
                            'class' => 'time'
                        ]
                    ]);
                    echo '</div>'; ?>
		    </span><BR class=form>
            </div>
        <span class=form>When available:</span>
	<span class=formright>
	<input type=radio name=availBeh value="O" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['availBeh'],'O')?> />Show Expanded<br/>
	<input type=radio name=availBeh value="C" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['availBeh'],'C')?> />Show Collapsed<br/>
	<input type=radio name=availBeh value="F" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['availBeh'],'F')?> />Show as Folder<br/>
	<input type=radio name=availBeh value="T" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['availBeh'],'T')?> />Show as TreeReader
	</span><br class=form />
        <span class=form>When not available:</span>
	<span class=formright>
	<input type=radio name=showhide value="H" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['showHide'],'H') ?> />Hide from Students<br/>
	<input type=radio name=showhide value="S" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['showHide'],'S') ?> />Show Collapsed/as folder
	</span><br class=form />

        <span class="form">If expanded, limit height to:</span>
	<span class="formright">
	<input type="text" name="fixedheight" size="4" value="<?php if ($defaultBlockData['fixedHeight']>0) {echo $defaultBlockData['fixedHeight'];};?>" />pixels (blank for no limit)
	</span><br class="form" />

            <fieldset>
                <span class=form>Restrict access to students in section:</span>
                    <span class=formright>
                      <?php AssessmentUtility::writeHtmlSelect('grouplimit',$page_sectionListVal,$page_sectionListLabel,$grouplimit[0]); ?>
	                  </span><br class=form>
    <span class=form>Make items publicly accessible<sup>*</sup>:</span>
	<span class=formright>
	<input type=checkbox name=public value="1" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['public'],'1') ?> />
	</span><br class=form />

    <div class=submit><input type=submit class="btn btn-primary" value="Create Block"></div>
    </form>
    <p class="small"><sup>*</sup>If a parent block is set to be publicly accessible, this block will automatically be publicly accessible, regardless of your selection here.<br/>
        Items from publicly accessible blocks can viewed without logging in at <?php echo "" ?>/public.php?cid=<?php echo ""?>. </p>



