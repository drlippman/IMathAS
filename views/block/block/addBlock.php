<?php
use app\components\AssessmentUtility;
use kartik\time\TimePicker;
use kartik\date\DatePicker;
use app\components\AppUtility;
use \app\components\AppConstant;

$this->title = $defaultBlockData['pageTitle'];
$this->params['breadcrumbs'][] = ['label' => $courseName, 'url' => ['/course/course/course?cid=' . $courseId]];
$this->params['breadcrumbs'][] = $this->title;
//AppUtility::dump($defaultBlockData);?>

<form method=post action="create-block?cid=<?php echo $courseId;
if (isset($block)) {
    echo "&block=$block";
}
if (isset($toTb)) {
    echo "&toTb=$toTb";
}
if (isset($id)) {
    echo "&id=$id";
} ?>">
    <div class="item-detail-header">
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', $courseName], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $courseId], 'page_title' => $this->title]); ?>
    </div>
    <div class="title-container">
        <div class="row">
            <div class="pull-left page-heading">
                <div class="vertical-align title-page"><?php echo $this->title ?>
                    <a href="#" onclick="window.open('/math/web/docs/help.php?section=blocks','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))"><i class="fa fa-question fa-fw help-icon"></i></a>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-content shadowBox non-nav-tab-item col-md-12 col-sm-12">
        <div class="col-md-12 col-sm-12 padding-top-two-em">
            <div class="col-md-2 col-sm-3 padding-left-zero"><?php AppUtility::t('Name of Block') ?> </div>
            <div class="col-md-10 col-sm-9 padding-left-zero">
                <input class="input-ite m-title form-control" type=text size=0 name=title
                       value="<?php echo str_replace('"', '&quot;', $defaultBlockData['title']); ?>">
            </div>
        </div>
            <div class="col-md-12 col-sm-12 padding-left-zero padding-top-one-pt-five-em">
                <div class="col-md-2 col-sm-3"><?php AppUtility::t('Visibility') ?></div>
                <div class="col-md-10 col-sm-9">
                    <span class="col-md-3 col-sm-4 padding-left-zero">
                        <input type=radio name="avail"
                               value="1" <?php AppUtility::writeHtmlChecked($defaultBlockData['avail'], AppConstant::NUMERIC_ONE);  ?>
                               onclick="document.getElementById('datediv').style.display='block'; "/>
                        <span class="padding-left-pt-five-em"><?php AppUtility::t('Show by Dates') ?></span>
                    </span>
                    <span class="col-md-2 col-sm-3 padding-left-zero non-bold">
                            <input type=radio name="avail"
                                   value="0" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['avail'], 0); ?>
                                   onclick="document.getElementById('datediv').style.display='none';"/>
                            <span class="padding-left-pt-five-em"><?php AppUtility::t('Hide') ?></span>
                    </span>
                    <span class="col-md-3 col-sm-3 padding-left-zero non-bold">
                        <input type=radio name="avail" value="2" <?php AssessmentUtility:: writeHtmlChecked($defaultBlockData['avail '], 2); ?>
                                                       onclick="document.getElementById('datediv').style.display='none'; "/>
                        <span class="padding-left-pt-five-em"><?php AppUtility::t('Show Always') ?></span>
                    </span>
                </div>
            </div>

            <!--Show by dates-->
            <div id="datediv" style="display:<?php echo ($defaultBlockData['avail'] == 1) ? "block" : "none"; ?>">
                <div class="col-md-12 col-sm-12 padding-left-zero padding-top-one-pt-five-em">
                    <div class="col-md-2 col-sm-3"><?php AppUtility::t('Available After') ?></div>
                    <div class="col-md-10 col-sm-9">
                        <div class="col-md-12 col-sm-12 padding-left-zero">
                            <label class="col-md-3 col-sm-6 non-bold padding-left-zero">
                                <input type=radio name="available-after" value="0" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['startDate'], '0', 0) ?>/>
                                <span class="padding-left-pt-five-em"><?php AppUtility::t('Always until end date') ?></span>
                            </label>

                            <label class="col-md-3 col-sm-3 pull-left non-bold padding-left-zero">
                                <input type=radio class="pull-left" name="available-after" value="1" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['startDate'], '1', 1) ?>/>
                                <span class="padding-left-pt-five-em"><?php AppUtility::t('Now') ?></span>
                            </label>

                        </div>

                        <div class="col-md-12 col-sm-12 padding-left-zero padding-top-one-pt-five-em">
                            <label class="pull-left padding-top-pt-five-em">
                                <input type=radio name="available-after" class="pull-left" value="sdate" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['startDate'], '0', 1) ?>/>
                            </label>
                            <div class = "time-input pull-left col-md-3 col-sm-5">
                                <?php    echo DatePicker::widget([
                                'name' => 'sdate',
                                'type' => DatePicker::TYPE_COMPONENT_APPEND,
                                'value' => $sdate,
                                'removeButton' => false,
                                'pluginOptions' => [
                                    'autoclose' => true,
                                    'format' => 'mm/dd/yyyy']
                                 ]); ?>
                            </div>
                            <label class="end pull-left non-bold padding-top-pt-five-em"> at </label>
                            <div class="pull-left col-md-4 col-sm-5 padding-right-zero">
                                <?php    echo TimePicker::widget([
                                    'name' => 'stime',
                                    'value' => time(),
                                    'pluginOptions' => [
                                        'showSeconds' => false,
                                        'class' => 'time'
                                    ]
                                ]); ?>
                            </div>
                        </div>
                    </div>
                </div>

            <div class="col-md-12 col-sm-12 padding-top-one-pt-five-em padding-left-zero">
                <div class="col-md-2 col-sm-3"><?php AppUtility::t('Available Until') ?></div>
                <div class="col-md-10 col-sm-9 padding-left-zero">
                    <label class='pull-left non-bold col-md-3 col-sm-5 padding-top-pt-five-em'>
                        <input type=radio name="available-until" value="2000000000" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['endDate'], '2000000000', 0) ?>/>
                        <span class="padding-left-pt-five-em"><?php AppUtility::t('Always after start date') ?></span>
                    </label>
                    <label class='pull-left non-bold padding-top-pt-five-em'>
                        <input type=radio name="available-until" class="pull-left" value="edate" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['endDate'], '2000000000', 1) ?>/>
                    </label>
                    <div class = "time-input pull-left col-md-3 col-sm-4">
                        <?php   echo DatePicker::widget([
                            'name' => 'edate',
                            'type' => DatePicker::TYPE_COMPONENT_APPEND,
                            'value' => date("m/d/Y"),
                            'removeButton' => false,
                            'pluginOptions' => [
                                'autoclose' => true,
                                'format' => 'mm/dd/yyyy']
                        ]); ?>
                    </div>
                    <label class="end pull-left non-bold padding-top-pt-five-em"> at </label>
                    <div class="pull-left col-md-4 col-sm-5">
                        <?php   echo TimePicker::widget([
                            'name' => 'etime',
                            'value' => time(),
                            'pluginOptions' => [
                                'showSeconds' => false,
                                'class' => 'time'
                            ]
                        ]); ?>
                    </div>
                </div>
            </div>
            </div>
            <div class="col-md-12 col-sm-12 padding-top-one-pt-five-em padding-left-zero">
                <div class="col-md-2 col-sm-3"><?php AppUtility::t('When available') ?></div>
                <div class="col-md-10 col-sm-10">
                    <span class="col-md-12 col-sm-12 padding-left-zero">
                        <input type=radio name=availBeh
                               value="O" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['availBeh'], 'O') ?> />
                        <span class="padding-left-pt-five-em"><?php AppUtility::t('Show Expanded') ?></span>
                    </span>
                    <span class="col-md-12 col-sm-12 padding-left-zero padding-top-pt-five-em">
                        <input type=radio name=availBeh value="C" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['availBeh'], 'C') ?> />
                        <span class="padding-left-pt-five-em"><?php AppUtility::t('Show Collapsed') ?></span>
                    </span>
                    <span class="col-md-12 col-sm-12 padding-left-zero padding-top-pt-five-em">
                        <input type=radio name=availBeh value="F" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['availBeh'], 'F') ?> />
                        <span class="padding-left-pt-five-em"><?php AppUtility::t('Show as Folder') ?></span>
                    </span>
                    <span class="col-md-12 col-sm-12 padding-left-zero padding-top-pt-five-em">
                        <input type=radio name=availBeh value="T" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['availBeh'], 'T') ?> />
                        <span class="padding-left-pt-five-em"><?php AppUtility::t('Show as TreeReader') ?></span>
                    </span>
                </div>
            </div>

            <div class="col-md-12 col-sm-12 padding-top-one-pt-five-em padding-left-zero">
                <div class="col-md-2 col-sm-3"><?php AppUtility::t('When not available') ?></div>
                <div class="col-md-10 col-sm-9">
                    <span class="col-md-12 col-sm-12 padding-left-zero">
                        <input type=radio name=showhide value="H" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['showHide'], 'H') ?> />
                        <span class="padding-left-pt-five-em"><?php AppUtility::t('Hide from Students') ?></span>
                    </span>
                    <span class="col-md-12 col-sm-12 padding-left-zero padding-top-pt-five-em">
                        <input type=radio name=showhide value="S" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['showHide'], 'S') ?> />
                        <span class="padding-left-pt-five-em"><?php AppUtility::t('Show Collapsed/as folder') ?></span>
                    </span>
                </div>
            </div>

        <div class="col-md-12 col-sm-12 padding-top-one-pt-five-em padding-left-zero">
            <div class="col-md-2 col-sm-3"><?php AppUtility::t('If expanded, limit height to') ?></div>
            <div class="col-md-10 col-sm-9 padding-left-zero">
                <div class="col-md-3 col-sm-3">
                    <input class="form-control" type="text" name="fixedheight" size="4" value="<?php if ($defaultBlockData['fixedHeight'] > 0) {
                        echo $defaultBlockData['fixedHeight'];
                    }; ?>"/>
                </div>
                <span class="col-md-4 col-sm-4 padding-left-zero padding-top-pt-five-em"><?php AppUtility::t('pixels (blank for no limit)') ?></span>
            </div>
        </div>
        <div class="col-md-12 col-sm-12 padding-top-one-pt-five-em padding-left-zero">
            <div class="col-md-2 col-sm-3">
                <?php AppUtility::t('Restrict access to students in section') ?>
            </div>
            <div class="col-md-3 col-sm-3">
                <?php AssessmentUtility::writeHtmlSelect('grouplimit', $page_sectionListVal, $page_sectionListLabel, $grouplimit[0]); ?>
            </div>
        </div>
        <div class="col-md-offset-2 col-sm-offset-3 col-md-10 col-sm-9 padding-top-one-pt-five-em padding-left-zero">
            <div class="col-md-12 col-sm-12">
                <input class="floatleft" type=checkbox name=public value="1" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['public'], '1') ?> />
                <div class="col-md-6 col-sm-6 padding-left-pt-five-em">
                    <?php AppUtility::t('Make items publicly accessible') ?><sup>*</sup>
                </div>
            </div>
        </div>
        <div class="header-btn col-md-offset-2 col-sm-offset-3 col-md-6 col-sm-6 padding-top-one-pt-five-em padding-bottom-two-em">
            <button class="btn btn-primary page-settings" type="submit" value="Submit">
                <i class="fa fa-share header-right-btn"></i>
                <?php echo $defaultBlockData['saveTitle'] ?>
            </button>
        </div>
</form>
<p class="col-md-12 col-sm-12 small col-md-10">
    <sup>*</sup>
    <?php AppUtility::t('If a parent block is set to be publicly accessible, this block will automatically be publicly accessible, regardless of your selection here.') ?>
    <?php AppUtility::t('Items from publicly accessible blocks can viewed without logging in at ') ?>
    <?php echo "" ?> /public.php?cid=<?php echo "" ?>.
</p>
</div>
