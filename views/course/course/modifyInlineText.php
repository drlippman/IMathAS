<?php
use yii\helpers\Html;
use app\components\AppUtility;
use app\components\AppConstant;
use \app\components\AssessmentUtility;
use kartik\time\TimePicker;
use kartik\date\DatePicker;
use app\components\filehandler;

$this->title = $pageTitle;
$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/course/course/course?cid=' . $course->id]];
$this->params['breadcrumbs'][] = $this->title;
?>
<?php if ($inlineId){ ?>
<form enctype="multipart/form-data" method=post
      action="modify-inline-text?cid=<?php echo $course->id ?>&id=<?php echo $inlineId; ?>">
    <?php }else{ ?>
    <form enctype="multipart/form-data" method=post
          action="modify-inline-text?cid=<?php echo $course->id ?>&block=<?php echo $block ?>">
        <?php } ?>
        <div class="item-detail-header">
            <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id], 'page_title' => $this->title]); ?>
        </div>
        <div class="title-container">
            <div class="row">
                <div class="pull-left page-heading">
                    <div class="vertical-align title-page"><?php echo $this->title ?></div>
                </div>
            </div>
        </div>

        <div class="tab-content shadowBox non-nav-tab-item col-md-12 col-sm-12">
            <form>
                <input type="hidden" value="<?php echo $filter ?>" name="tb">

                <div class="name-of-item">
                    <div class="col-md-2 col-sm-3 padding-top-pt-five-em"><?php AppUtility::t('Name of Inline Text') ?></div>
                    <div class="col-md-10 col-sm-9 padding-left-zero">
                        <?php $title = AppUtility::t('Enter title here', false);
                        if ($line['title']) {
                            $title = $line['title'];
                        } ?>
                        <div class="col-md-12 col-sm-12"><input class="form-control input-item-title" maxlength="60" required=" " type=text size=0 name=title value="<?php echo $title; ?>"></div>
                        <div class="col-md-5 col-sm-5 padding-top-two-em">
                            <input type="checkbox" name="hidetitle" value="1" <?php AppUtility::writeHtmlChecked($hidetitle, true) ?>/>
                            <?php AppUtility::t('Hide title and icon') ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 col-sm-12 padding-top-two-em padding-left-zero">
                    <div class="col-md-2 col-sm-3">
                        <?php AppUtility::t('Summary') ?>
                    </div>
                    <div class="col-md-10 col-sm-9">
                        <div class="editor summary-inline-text">
                            <textarea class="col-md-12" cols=5 rows=12 id=description name=description style="width: 100%;">
                                <?php $text = AppUtility::t('Enter text here');
                                echo htmlentities($text);?>
                            </textarea>
                        </div>
                    </div>
                </div>
                <!--File Attachment -->
                <div class="col-sm-12 col-sm-12 padding-top-two-em">
                <div class='col-md-2 col-sm-3 padding-left-zero'><?php AppUtility::t('Attached Files') ?></div>
                <?php

                if (isset($params['id'])) {
                    foreach ($page_FileLinks as $k => $arr) {
                        AppUtility::generatemoveselect($page_fileorderCount, $k);
                        ?>
                        <a href="<?php echo filehandler::getcoursefileurl($arr['link']); ?>" target="_blank"> <?php AppUtility::t('View')?> </a>
                        <input class="form-control-1" type="text" name="filedescr-<?php echo $arr['fid'] ?>" value="<?php echo $arr['desc'] ?>"/>
                        <?php AppUtility::t('Delete?')?> <input type=checkbox name="delfile-<?php echo $arr['fid'] ?>"/><br/>
                    <?php
                    }
                }
                ?>
                <div class='col-md-10 col-sm-9 padding-left-zero'>
                    <div class="col-md-12 col-sm-12 padding-left-zero">
                        <input type="hidden" name="MAX_FILE_SIZE" value="10000000"/>
                        <span class="floatleft"><?php AppUtility::t('New file') ?><sup>*</sup></span>
                        <span class="col-md-3 col-sm-3"><input type="file" name="userfile"/></span>
                    </div>

                    <div class="col-md-12 col-sm-12 padding-left-zero padding-top-two-em">
                        <span class="floatleft padding-top-pt-five-em"><?php AppUtility::t('Description') ?></span>
                        <span class="col-md-4 col-sm-4"><input class="form-control" type="text" name="newfiledescr"/></span>
                    </div>
                    <div class="col-md-12 col-sm-12 padding-left-zero padding-top-two-em">
                        <input type=submit name="submitbtn" class="btn btn-primary" value="Add / Update Files"/>
                    </div>

                </div>
                </div>

                <!--List of Youtube Videos-->
                <div class="col-md-12 col-sm-12 padding-left-zero padding-top-two-em">
                    <div class="col-md-2 col-sm-3"><?php AppUtility::t('List of YouTube videos') ?></div>
                    <div class="col-md-10 col-sm-9">
                        <input type="checkbox" name="isplaylist" value="1" <?php AppUtility::writeHtmlChecked($line['isplaylist'], 1); ?>/>
                        <span class="padding-left"><?php AppUtility::t('Show as embedded playlist') ?></span>
                    </div>
                </div>

                <div class="col-md-12 col-sm-12 padding-top-two-em padding-left-zero">
                        <div class="col-md-2 col-sm-3"><?php AppUtility::t('Visibility') ?></div>
                        <div class="col-md-10 col-sm-9">
                            <input type=radio name="avail" value="1" <?php AppUtility::writeHtmlChecked($line['avail'], AppConstant::NUMERIC_ONE); ?>
                            onclick="document.getElementById('datediv').style.display='block';document.getElementById('altcaldiv').style.display='none';"/>
                            <span class="padding-left"><?php AppUtility::t('Show by Dates') ?></span>
                            <label class="non-bold" style="padding-left: 80px">
                                <input type=radio name="avail" value="0" <?php AppUtility::writeHtmlChecked($line['avail'], 0); ?> onclick="document.getElementById('datediv').style.display='none';document.getElementById('altcaldiv').style.display='none';"/>
                                <span class="padding-left"><?php AppUtility::t('Hide') ?></span>
                            </label>
                            <label class="non-bold" style="padding-left: 80px">
                                <input type=radio name="avail" value="2" <?php AppUtility::writeHtmlChecked($line['avail'], 2); ?>
                                onclick="document.getElementById('datediv').style.display='none';document.getElementById('altcaldiv').style.display='block';"/>
                                <span class="padding-left"><?php AppUtility::t('Show Always') ?></span>
                            </label>
                        </div>
                </div>
                        <div class="col-md-12 col-sm-12 padding-left-zero" id="datediv" style="display:<?php echo ($line['avail'] == 1) ? "block" : "none"; ?>">
                            <div class="col-md-12 col-sm-12 padding-left-zero padding-top-two-em">
                                <div class="col-md-2 col-sm-3">
                                    <?php AppUtility::t('Available After') ?>
                                </div>
                                <div class="col-md-10 col-sm-9">
                                    <label class="pull-left non-bold">
                                        <input type=radio name="sdatetype" value="0" <?php AppUtility::writeHtmlChecked($startDate, '0', 0) ?>/>
                                        <span class='padding-left'><?php AppUtility::t(' Always until end date') ?></span>
                                    </label>
                                    <label class="pull-left non-bold" style="padding-left: 36px">
                                        <input type=radio name="sdatetype" class="pull-left" value="sdate" <?php AppUtility::writeHtmlChecked($startDate, '0', 1) ?>/>
                                    </label>

                                    <?php
                                    echo '<div class = "time-input pull-left col-md-3 col-sm-4">';
                                    echo DatePicker::widget([
                                        'name' => 'sdate',
                                        'type' => DatePicker::TYPE_COMPONENT_APPEND,
                                        'value' => $sdate,
                                        'removeButton' => false,
                                        'pluginOptions' => [
                                            'autoclose' => true,
                                            'format' => 'mm/dd/yyyy']
                                    ]);
                                    echo '</div>';?>
                                    <?php
                                    echo '<label class="end pull-left non-bold col-md-1 col-sm-1">'; AppUtility::t('at');  echo'</label>';
                                    echo '<div class="padding-left-zero col-md-4 col-sm-6">';

                                    echo TimePicker::widget([
                                        'name' => 'stime',
                                        'value' => $stime,
                                        'pluginOptions' => [
                                            'showSeconds' => false,
                                            'class' => 'time'
                                        ]
                                    ]);
                                    echo '</div>';?>
                                </div>
                            </div>

                            <div class="col-md-12 col-sm-12 padding-left-zero padding-top-two-em">
                            <div class="col-md-2 col-sm-3"><?php AppUtility::t('Available Until') ?></div>
                            <div class="col-md-10 col-sm-9">
                                <label class="pull-left non-bold">
                                    <input type=radio name="edatetype" value="2000000000" <?php AppUtility::writeHtmlChecked($endDate, '2000000000', 0) ?>/>
                                    <span class="padding-left"><?php AppUtility::t('Always after start date') ?></span>
                                </label>
                                <label class="pull-left non-bold" style="padding-left: 34px">
                                    <input type=radio name="edatetype" class="pull-left" value="edate" <?php AppUtility::writeHtmlChecked($endDate, '2000000000', 1) ?>/>
                                </label>
                                <?php
                                echo '<div class = "time-input pull-left col-md-3 col-sm-4">';
                                echo DatePicker::widget([
                                    'name' => 'edate',
                                    'type' => DatePicker::TYPE_COMPONENT_APPEND,
                                    'value' => $edate,
                                    'removeButton' => false,
                                    'pluginOptions' => [
                                        'autoclose' => true,
                                        'format' => 'mm/dd/yyyy']
                                ]);
                                echo '</div>';?>
                                <?php
                                echo '<label class="end pull-left non-bold col-md-1 col-sm-1"> at </label>';
                                echo '<div class="padding-left-zero col-md-4 col-sm-6">';

                                echo TimePicker::widget([
                                    'name' => 'etime',
                                    'value' => $etime,
                                    'pluginOptions' => [
                                        'showSeconds' => false,
                                        'class' => 'time'
                                    ]
                                ]);
                                echo '</div>';?>
                            </div>
                            </div>

                            <div class="col-md-12 col-sm-12 padding-left-zero padding-top-two-em">
                                <div class="col-md-2 col-sm-3"><?php AppUtility::t('Place on Calendar?') ?></div>
                                <div class="col-md-10 col-sm-9">
                                    <div class="col-md-12 col-sm-12 padding-left-zero padding-top-pt-five-em">
                                        <input type=radio name="oncal" value=0 <?php AppUtility::writeHtmlChecked($line['oncal'], 0); ?> />
                                        <span class="padding-left"><?php AppUtility::t('No') ?></span>
                                    </div>

                                    <div class="col-md-12 col-sm-12 padding-left-zero padding-top-pt-five-em">
                                        <input type=radio name="oncal" value=1 <?php AppUtility::writeHtmlChecked($line['oncal'], 1); ?> />
                                        <span class="padding-left"><?php AppUtility::t('Yes, on Available after date (will only show after that date)') ?></span>
                                    </div>

                                    <div class="col-md-12 col-sm-12 padding-left-zero padding-top-pt-five-em">
                                        <input type=radio name="oncal" value=2 <?php AppUtility::writeHtmlChecked($line['oncal'], 2); ?> />
                                        <span class="padding-left"><?php AppUtility::t('Yes, on Available until date') ?></span>
                                    </div>

                                    <div class="col-md-12 col-sm-12 padding-left-zero padding-top-pt-five-em">
                                        <span class="floatleft"><?php AppUtility::t('With tag')?></span>
                                        <span class="col-md-2 col-sm-2 padding-left-one-em">
                                            <input class="form-control" name="calTag" type=text size=1 maxlength="20" value="<?php echo $line['caltag']; ?>"/></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                <div id="altcaldiv" style="display:<?php echo ($line['avail']==2)?"block":"none"; ?>">
                    <div class="col-md-2 col-sm-2">Place on Calendar?</div>
             <div class="col-md-10 col-sm-10 padding-left-zero">
                 <div class="col-md-12 col-sm-12"><input type=radio name="altoncal" value="0" <?php AppUtility::writeHtmlChecked($altoncal,0); ?> />
                     <span class="padding-left-fifteen">
                    <?php AppUtility::t('No') ?>
                </span></div>
                 <div class='col-md-12 col-sm-12 padding-top-fifteen'>
                     <label class="pull-left">
			<input type=radio name="altoncal" value="1" <?php AppUtility::writeHtmlChecked($altoncal,1); ?> /></label>
                     <span class="pull-left padding-left-twenty">
                        <?php AppUtility::t('Yes, on') ?>
                </span>

            <?php
            echo '<div class = "time-input pull-left col-md-4">';
            echo DatePicker::widget([
                'name' => 'cdate',
                'type' => DatePicker::TYPE_COMPONENT_APPEND,
                'value' => $sdate,
                'removeButton' => false,
                'pluginOptions' => [
                    'autoclose' => true,
                    'format' => 'mm/dd/yyyy']
            ]);
            echo '</div>'; ?>
                     <div class="col-md-12 col-sm-12 padding-top-twenty padding-bottom-twenty">
                         <?php AppUtility::t('With tag') ?>
                         <span class="padding-left-five">
                    <input class="form-control display-inline-block width-five-per" type="text" size="3" maxlength="20" value=<?php echo $line['caltag'];?> name="altcaltag">
                </span>
                     </div>
		</div>
                </div></div>
                        <div class="col-md-12 col-sm-12 padding-left-zero padding-top-one-em">
                            <?php if (count($outcome) > 0) { ?>
                                <span class="col-md-2 col-sm-3 padding-top-pt-five-em"><?php AppUtility::t('Associate Outcomes')?></span>
                                <span class="col-md-8 col-sm-8 padding-left-zero">
                                    <?php AssessmentUtility::writeHtmlMultiSelect('outcomes', $outcome, $outcomenames, $gradeoutcomes, 'Select an outcome...'); ?>
                                </span>
                            <?php } ?>
                        </div>

                            <div class="col-md-6 col-md-offset-2 col-sm-6 col-sm-offset-3 padding-top-one-em padding-bottom-two-em">
                                <button type=submit name="submitbtn" value="Submit"><?php echo $savetitle ?></button>
                            </div>
            </form>
        </div>
        <?php $urlmode = \app\components\AppUtility::urlMode();?>
        <script type="text/javascript">
            function movefile(from) {
                var to = document.getElementById('ms-' + from).value;
                var address = "<?php echo $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/modify-inline-text?cid = $cid&block=$block&id=" . $_GET['id'] ?>";

                if (to != from) {
                    var toopen = address + '&movefile=' + from + '&movefileto=' + to;
                    window.location = toopen;
                }
            }
        </script>