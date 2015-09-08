<?php
use yii\helpers\Html;
use app\components\AppUtility;
use app\components\AppConstant;
use kartik\time\TimePicker;
use kartik\date\DatePicker;
use app\components\filehandler;
$this->title = $pageTitle;
$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;
//include_once('../components/filehandler.php');

?>
<form enctype="multipart/form-data" method=post action="<?php echo $page_formActionTag ?>">
    <div class="name-of-item">
        <div class="col-lg-2"><?php AppUtility::t('Name of Inline Text')?></div>
        <div class="col-lg-10">
            <?php $title = AppUtility::t('Enter title here', false);
            if($line['title']){
                $title = $line['title'];
            } ?>
            <input class="input-item-title" type=text size=0 name=title value="<?php echo $title;?>">
            <input type="checkbox" name="hidetitle" value="1" <?php AppUtility::writeHtmlChecked($hidetitle,true) ?>/><?php AppUtility::t('Hide title and icon')?>
        </div>
    </div>
    <BR><br class=form />

    <div class="editor-summary">
        <div class="col-lg-2"><?php AppUtility::t('Summary')?></div>
        <div class="col-lg-10">
            <div class=editor>
                <textarea cols=5 rows=12 id=description name=description style="width: 100%;">
                    <?php $text = AppUtility::t('Enter text here');
//                    if($line['text'])
//                    {
//                        $text = $line['text'];
//                    }
                    echo htmlentities($text);?>
                </textarea>
            </div>
        </div>
    </div><br class=form /><br class=form />

    <!--File Attachment -->
    <div class=col-lg-2><?php AppUtility::t('Attached Files')?></div>
<?php
if (isset($inlineId)) {
    foreach ($page_FileLinks as $k=>$arr) {
         AppUtility::generatemoveselect($page_fileorderCount,$k);
        ?>
        		<a href="<?php echo filehandler::getcoursefileurl($arr['link']); ?>" target="_blank">
        		View</a>
        		<input type="text" name="filedescr-<?php echo $arr['fid'] ?>" value="<?php echo $arr['desc'] ?>"/>
        		Delete? <input type=checkbox name="delfile-<?php echo $arr['fid'] ?>"/><br/>
    <?php
    }
}
?>
        <div class=col-lg-10>
            <input type="hidden" name="MAX_FILE_SIZE" value="10000000" />
            New file<sup>*</sup>: <input type="file" name="userfile" /><br />
            Description: <input type="text" name="newfiledescr"/><br/><br/>
            <input type=submit name="submitbtn" class="btn btn-primary" value="Add / Update Files"/>
        </div> <br class=form>

    <!--List of Youtube Videos-->
    <div class="youtube-video">
        <div class="col-lg-2"><?php AppUtility::t('List of YouTube videos')?></div>
        <div class="col-lg-10">
            <input type="checkbox" name="isplaylist" value="1" <?php AppUtility::writeHtmlChecked($line['isplaylist'],1);?>/>
            <span class="padding-left"><?php AppUtility::t('Show as embedded playlist')?></span>
        </div>
    </div><br class="form"/>

    <div>
        <div class="visibility-item">
            <div class="col-lg-2"><?php AppUtility::t('Visibility')?></div>
            <div class="col-lg-10">
            <input type=radio name="avail" value="1" <?php AppUtility::writeHtmlChecked($line['avail'], AppConstant::NUMERIC_ONE);?> onclick="document.getElementById('datediv').style.display='block';document.getElementById('altcaldiv').style.display='none';"/><span class="padding-left"><?php AppUtility::t('Show by Dates')?></span>
            <label class="non-bold" style="padding-left: 80px"><input type=radio name="avail" value="0" <?php AppUtility::writeHtmlChecked($line['avail'],0);?> onclick="document.getElementById('datediv').style.display='none';document.getElementById('altcaldiv').style.display='none';"/><span class="padding-left"><?php AppUtility::t('Hide')?></span></label>
            <label class="non-bold" style="padding-left: 80px"><input type=radio name="avail" value="2" <?php AppUtility::writeHtmlChecked($line['avail'],2);?> onclick="document.getElementById('datediv').style.display='none';document.getElementById('altcaldiv').style.display='block';"/><span class="padding-left"><?php AppUtility::t('Show Always')?></span></label>
            </div>
            <br class="form"/>

            <div id="datediv" style="display:<?php echo ($line['avail']==1)?"block":"none"; ?>"><br class="form"/>

            <div class=col-lg-2><?php AppUtility::t('Available After')?></div>
            <div class=col-lg-10>
                <label class="pull-left non-bold"><input type=radio name="sdatetype" value="0" <?php AppUtility::writeHtmlChecked($startDate,'0',0) ?>/><span class='padding-left'><?php AppUtility::t(' Always until end date')?></span></label>
                <label class="pull-left non-bold" style="padding-left: 36px"><input type=radio name="sdatetype" class="pull-left" value="sdate" <?php AppUtility::writeHtmlChecked($startDate,'0',1) ?>/></label>

                <?php
                echo '<div class = "time-input pull-left col-lg-4">';
                echo DatePicker::widget([
                    'name' => 'sdate',
                    'type' => DatePicker::TYPE_COMPONENT_APPEND,
                    'value' => $sdate,
                    'removeButton' => false,
                    'pluginOptions' => [
                        'autoclose' => true,
                        'format' => 'mm/dd/yyyy' ]
                ]);
                echo '</div>';?>
                <?php
                echo '<label class="end pull-left non-bold col-lg-1"> at </label>';
                echo '<div class="pull-left col-lg-4">';

                echo TimePicker::widget([
                    'name' => 'stime',
                    'value' => $stime,
                    'pluginOptions' => [
                        'showSeconds' => false,
                        'class' => 'time'
                    ]
                ]);
                echo '</div>';?>
            </div><BR class=form><BR class=form>

                <div class=col-lg-2><?php AppUtility::t('Available Until')?></div>
                <div class=col-lg-10>
                    <label class="pull-left non-bold"><input type=radio name="edatetype" value="2000000000" <?php AppUtility::writeHtmlChecked($endDate,'2000000000',0) ?>/><span class="padding-left"><?php AppUtility::t('Always after start date')?></span></label>
                    <label class="pull-left non-bold" style="padding-left: 34px"><input type=radio name="edatetype" class="pull-left" value="edate" <?php AppUtility::writeHtmlChecked($endDate,'2000000000',1) ?>/></label>
                    <?php
                    echo '<div class = "time-input pull-left col-lg-4">';
                    echo DatePicker::widget([
                        'name' => 'edate',
                        'type' => DatePicker::TYPE_COMPONENT_APPEND,
                        'value' => $edate,
                        'removeButton' => false,
                        'pluginOptions' => [
                            'autoclose' => true,
                            'format' => 'mm/dd/yyyy' ]
                    ]);
                    echo '</div>';?>
                    <?php
                    echo '<label class="end pull-left non-bold col-lg-1"> at </label>';
                    echo '<div class="pull-left col-lg-4">';

                    echo TimePicker::widget([
                        'name' => 'etime',
                        'value' => $etime,
                        'pluginOptions' => [
                            'showSeconds' => false,
                            'class' => 'time'
                        ]
                    ]);
                    echo '</div>';?>
                </div><BR class=form><BR class=form>

                <div class=col-lg-2><?php AppUtility::t('Place on Calendar?')?></div>
                <div class=col-lg-10>
                    <input type=radio name="oncal" value=0 <?php AppUtility::writeHtmlChecked($line['oncal'],0); ?> /><span class="padding-left"><?php AppUtility::t('No')?></span><br>
                    <input type=radio name="oncal" value=1 <?php AppUtility::writeHtmlChecked($line['oncal'],1); ?> /><span class="padding-left"><?php AppUtility::t('Yes, on Available after date (will only show after that date)')?></span><br/>
                    <input type=radio name="oncal" value=2 <?php AppUtility::writeHtmlChecked($line['oncal'],2); ?> /><span class="padding-left"><?php AppUtility::t('Yes, on Available until date')?></span><br />
                    With tag: <input name="calTag" type=text size=1 value="<?php echo $line['caltag'];?>"/>
                </div><br class="form" />
            </div>

<!--            <div id="altcaldiv" style="display:--><?php //echo ($line['avail'] == 2)?"block":"none"; ?><!--"><BR class=form>-->

<!--                <div class=col-lg-2>--><?php //AppUtility::t('Place on Calendar?')?><!--</div>-->
<!--                <div class=col-lg-10>-->
<!--                    -->
<!--                    <input type=radio name="altoncal" value="0" --><?php //AppUtility::writeHtmlChecked($altoncal,0); ?><!-- /><span class='padding-left'>--><?php //AppUtility::t('No')?><!--</span><br>-->
<!--                    <input type=radio name="altoncal" class="pull-left" value="1" --><?php //AppUtility::writeHtmlChecked($altoncal,1); ?><!-- /><span class="pull-left padding-left">--><?php //AppUtility::t('Yes On')?><!--</span>-->
<!--                    --><?php
//                    echo '<div class = "col-lg-4 time-input">';
//                    echo DatePicker::widget([
//                        'name' => 'cdate',
//                        'type' => DatePicker::TYPE_COMPONENT_APPEND,
//                        'value' => $sdate,
//                        'removeButton' => false,
//                        'pluginOptions' => [
//                            'autoclose' => true,
//                            'format' => 'mm/dd/yyyy' ]
//                    ]);
//
//                    echo '</div>';?><!--<BR class=form>-->
<!--                    With tag:<input name="altcaltag" type=text size=1 value="--><?php //echo $line['caltag'];?><!--"/>-->
<!--        </div>-->


        <?php
        if (count($outcome)>0)
        {
            echo '<span class="form">Associate Outcomes:</span></span class="formright">';
            \app\components\AssessmentUtility::writeHtmlMultiSelect('outcomes',$outcome,$outcomenames,$gradeoutcomes,'Select an outcome...');
            echo '</span><br class="form"/>';
        }
        ?>
    </div>

    <div class=submit><button type=submit name="submitbtn" value="Submit"><?php echo $savetitle ?></button></div>
</form>
<script type="text/javascript">
    function movefile(from) {
        var to = document.getElementById('ms-'+from).value;
        var address = "<?php echo $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/addinlinetext.php?cid = $cid&block=$block&id=" . $_GET['id'] ?>";

        if (to != from) {
            var toopen = address + '&movefile=' + from + '&movefileto=' + to;
            window.location = toopen;
        }
    }
</script>