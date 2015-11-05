<?php
use app\components\AppUtility;
use app\components\AppConstant;
use kartik\time\TimePicker;
use kartik\date\DatePicker;
use app\components\AssessmentUtility;
$this->title = $pageTitle;
?>

    <form enctype="multipart/form-data" method=post action="<?php echo $page_formActionTag ?>">
    <?php if ($modifyForumId){ ?>
        <input type="hidden" name="modifyFid" value="<?php echo $modifyForumId;?>">
    <?php } ?>
     <div class="item-detail-header">
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$course->name], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'course/course/course?cid='.$course->id], 'page_title' => $this->title]); ?>
    </div>
    <div class = "title-container">
        <div class="row">
            <div class="pull-left page-heading">
                <div class="vertical-align title-page"><?php echo $this->title ?><img class="help-img" src="<?php echo AppUtility::getAssetURL()?>img/helpIcon.png" alt="Help" onClick="window.open('<?php echo AppUtility::getHomeURL() ?>docs/help.php?section=forumitems','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))"/></div>
            </div>

        </div>
    </div>

    <div class="tab-content shadowBox non-nav-tab-item col-md-12 col-sm-12">
        <div class="name-of-item">
            <div class="col-md-2 col-sm-2 padding-right-zero"><?php AppUtility::t('Name of Forum')?></div>
            <div class="col-md-10 col-sm-10">
                <?php $title = AppUtility::t('Enter forum name here', false);
                if ($forumData) {
                $title = $forumData['name'];
                } ?>
                <input class="form-control" type=text size=0 style="width: 100%;height: 40px; border: #a9a9a9 1px solid;" name=name value="<?php echo $title;?>">
            </div>
        </div>
            <BR class=form>

        <div class="editor-summary col-md-12 col-sm-12 padding-left-zero padding-right-zero">
            <div class="col-md-2 col-sm-2">
                <?php AppUtility::t('Description')?>
            </div>
            <div class="col-md-10 col-sm-10 padding-left-zero padding-right-zero">
                <div class="col-md-12 col-sm-12 editor add-forum-summary-textarea">
                    <?php  $description = 'Enter forum description here';
                             if ($forumData) {
                                 $description = $forumData['description'];
                        } ?>
                        <textarea cols="5" rows="12" id="description" name="description" style="width: 100%;">
                    <?php echo $description; ?></textarea>
                </div>
            </div>
        </div>
            <!--Show-->
        <div class="col-md-12 col-sm-12 padding-left-zero padding-top-one-em">
            <div class="col-md-2 col-sm-2"><?php AppUtility::t('Visibility')?></div>
            <div class="col-md-10 col-sm-10">
                <div class="col-md-3 col-sm-3 padding-left-zero">
                    <input type=radio name="avail" value="1" <?php AssessmentUtility::writeHtmlChecked($defaultValue['avail'],AppConstant::NUMERIC_ONE);?> onclick="toggleGBdetail1(true)"/>
                    <span class='padding-left-pt-five-em'>
                        <?php AppUtility::t('Show by Dates')?>
                    </span>
                </div>
                <label class="non-bold col-md-2 col-sm-3 padding-left-zero">
                    <input type=radio name="avail" value="0" <?php AssessmentUtility::writeHtmlChecked($defaultValue['avail'],AppConstant::NUMERIC_ZERO);?> onclick="toggleGBdetail1(false)"/>
                    <span class="padding-left-pt-five-em">
                        <?php AppUtility::t('Hide')?>
                    </span>
                </label>
                <label class="non-bold col-md-3 col-sm-3 padding-left-zero">
                    <input type=radio name="avail" value="2" <?php AssessmentUtility::writeHtmlChecked($defaultValue['avail'], AppConstant::NUMERIC_TWO); ?>onclick="toggleGBdetail1(false)"/>
                    <span class='padding-left-pt-five-em'>
                        <?php AppUtility::t('Show Always')?>
                    </span>
                </label>
            </div>
        </div>
                <!--Show by dates-->
        <div class="col-md-12 col-sm-12 padding-left-zero" id="datediv" <?php if ($defaultValue['avail']!= AppConstant::NUMERIC_ONE) { echo 'style="display:none;"';}?>>
            <div class="col-md-12 col-sm-12 padding-left-zero padding-top-one-em padding-right-zero">
                <div class="col-md-2 col-sm-2 padding-top-pt-five-em padding-right-zero"><?php AppUtility::t('Available After')?></div>
                <div class="col-md-10 col-sm-10">
                    <div class="col-md-3 col-sm-5 padding-left-zero padding-right-zero padding-top-pt-five-em">
                        <input type=radio name="available-after" class="pull-left" value="0" <?php if($defaultValue['startDate'] == 0 ){ echo "checked=1"; }?>/>
                        <span class="padding-left-pt-eight-em">
                            <?php AppUtility::t('Always until end date')?>
                        </span>
                    </div>

                    <div class="col-md-3 col-sm-4 padding-left-zero padding-right-zero">
                        <label class="non-bold floatleft padding-top-pt-five-em">
                            <input type=radio name="available-after" class="pull-left" value="1" <?php if($defaultValue['startDate'] == 1 ){ echo "checked=1"; }?>/>
                        </label>
                        <div class = "time-input pull-left col-md-10 col-sm-10 padding-right-zero add-forum-date-font">
                        <?php echo DatePicker::widget([
                            'name' => 'sdate',
                            'type' => DatePicker::TYPE_COMPONENT_APPEND,
                            'value' => $defaultValue['sDate'],
                            'removeButton' => false,
                            'pluginOptions' => [
                                'autoclose' => true,
                                'format' => 'mm/dd/yyyy']
                        ]); ?>
                        </div>
                    </div>

                    <div class="col-md-4 col-sm-6 padding-left-zero">
                        <label class="end pull-left non-bold padding-top-pt-five-em"> at </label>
                        <div class="pull-left col-md-11 col-sm-10 padding-right-zero add-forum-date-font">
                            <?php echo TimePicker::widget([
                            'name' => 'stime',
                            'value' => $defaultValue['sTime'],
                            'pluginOptions' => [
                                'showSeconds' => false,
                                'class' => 'time'
                            ]
                            ]); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12 col-sm-12 padding-left-zero padding-top-one-em">
                <div class="col-md-2 col-sm-2 padding-top-pt-five-em padding-right-zero">
                    <?php AppUtility::t('Available Until')?>
                </div>
                <div class="col-md-10 col-sm-10 padding-right-zero">
                    <div class="col-md-3 col-sm-5 padding-left-zero padding-right-zero padding-top-pt-five-em">
                        <input type=radio name="available-until" class="pull-left" value="2000000000"  <?php if($defaultValue['endDate'] == AppConstant::ALWAYS_TIME ){ echo "checked=1"; }?>/>
                        <span class="pull-left padding-left padding-left-pt-five-em">
                            <?php AppUtility::t('Always after start date')?>
                        </span>

                    </div>

                    <div class = "col-md-3 col-sm-4 padding-left-zero padding-right-zero">
                        <label class=" pull-left non-bold padding-top-pt-five-em">
                            <input type=radio name="available-until" class="pull-left" value="1" <?php if($defaultValue['endDate'] == 1 ){ echo "checked=1"; }?>/>
                        </label>
                        <div class="time-input pull-left col-md-10 col-sm-10 padding-right-zero add-forum-date-font">
                            <?php echo DatePicker::widget([
                            'name' => 'edate',
                            'type' => DatePicker::TYPE_COMPONENT_APPEND,
                            'value' => $defaultValue['eDate'],
                            'removeButton' => false,
                            'pluginOptions' => [
                                'autoclose' => true,
                                'format' => 'mm/dd/yyyy']
                            ]); ?>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-6 padding-left-zero">
                        <label class="end pull-left non-bold padding-top-pt-five-em"> at </label>
                        <div class="pull-left col-md-11 col-sm-10 padding-right-zero add-forum-date-font">
                            <?php echo TimePicker::widget([
                                'name' => 'etime',
                                'value' => $defaultValue['eTime'],
                                'pluginOptions' => [
                                    'showSeconds' => false,
                                    'class' => 'time'
                                ]
                            ]); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12 col-sm-12 padding-left-zero padding-top-one-em">
            <div class="col-md-2 col-sm-2 padding-top-pt-five-em padding-right-zero">
                <?php AppUtility::t('Group forum?')?>
            </div>
            <div class="col-md-3 col-sm-4">
                 <?php
                AssessmentUtility::writeHtmlSelect("groupsetid",$groupNameId,$groupNameLabel,$defaultValue['groupSetId'],"Not group forum",0);
                if ($defaultValue['groupSetId'] > 0 && $defaultValue['hasGroupThreads']) {
                    echo '<br/> WARNING: <span style="font-size: 80%">Group threads exist.  Changing the group set will set all existing threads to be non-group-specific threads</span>';
                }        ?>
            </div>
        </div>
        <div class="item-alignment col-md-offset-2 col-sm-offset-2 col-md-10 col-sm-10">
            <div class="floatleft">
                <input type="checkbox" name="allow-anonymous-posts" value="1"<?php if ($defaultValue['allowAnonymous']) { echo "checked=1";}?> >
            </div>
            <div class="col-md-4 col-sm-4">
                <?php AppUtility::t('Allow anonymous posts')?>
            </div>
        </div>

        <div class="item-alignment col-md-offset-2 col-sm-offset-2 col-md-10 col-sm-10">
            <div class="floatleft">
                <input type="checkbox" name="allow-students-to-modify-posts" value="2"<?php if ($defaultValue['allowModify']) { echo "checked=1";}?>><br>
            </div>
            <div class="col-md-6 col-sm-6"><?php AppUtility::t('Allow students to modify posts')?></div>
        </div>

        <div class="item-alignment col-md-offset-2 col-sm-offset-2 col-md-10 col-sm-10">
            <div class="floatleft">
                <input type="checkbox" name="allow-students-to-delete-own-posts" value="4"<?php if ($defaultValue['allowDelete']) { echo "checked=1";}?>><br>
            </div>
            <div class="col-md-6 col-sm-6">
                <?php AppUtility::t('Allow students to delete own posts (if no replies)')?>
            </div>
        </div>

        <div class="item-alignment col-md-offset-2 col-sm-offset-2 col-md-10 col-sm-10">
            <div class="floatleft">
                <input type="checkbox" name="like-post" value="8"<?php if ($defaultValue['allowLikes']) { echo "checked=1";}?>><br>
            </div>
            <div class="col-md-4 col-sm-4">
                <?php AppUtility::t('Turn on "liking" posts')?>
            </div>
        </div>


        <div class="item-alignment col-md-12 col-sm-12 padding-left-zero">
            <div class="col-md-2 col-sm-2"><?php AppUtility::t('Viewing before posting')?></div>
            <div class="col-md-10 col-sm-10">
                <input type="checkbox" name="viewing-before-posting" value="16"<?php if ($defaultValue['viewAfterPost']) { echo "checked=1";}?>>
                <label class="padding-left non-bold"><?php AppUtility::t('Prevent students from viewing posts until they have created a thread.
                You will likely also want to disable modifying posts')?></label>
            </div>
        </div>

        <div class="item-alignment col-md-offset-2 col-sm-offset-2 col-md-10 col-sm-10">
            <div class="floatleft">
                <input type="checkbox" name="Get-email-notify-of-new-posts" value="1"<?php if ($defaultValue['hasSubScrip']) { echo "checked=1";}?>><br>
            </div>
            <div class="col-md-4 col-sm-4">
                <?php AppUtility::t('Get email notify of new posts')?>
            </div>
         </div>

        <div class="item-alignment col-md-12 col-sm-12 padding-left-zero">
            <div class="col-md-2 col-sm-2">
                <?php AppUtility::t('Default display')?>
            </div>
            <div class="col-md-3 col-sm-4">
                <select name="default-display" class="form-control">
                    <option value="0" <?php if ($defaultValue['defDisplay']==0 || $defaultValue['defDisplay']==1) {echo "selected=1";}?>>Expanded</option>
                    <option value="2" <?php if ($defaultValue['defDisplay']==2) {echo "selected=1";}?>>Condensed</option>
                </select>
            </div>
        </div>

                    <div class="item-alignment">
                        <div class="col-md-2 col-sm-2"><?php AppUtility::t('Sort threads by')?></div>
                            <div class="col-md-10 col-sm-10">
                                <input type=radio name="sort-thread" value="0" <?php AssessmentUtility::writeHtmlChecked($defaultValue['sortBy'],0);?>><span class="padding-left"><?php AppUtility::t('Thread start date')?></span><br>
                                <input type=radio name="sort-thread" value="1"<?php AssessmentUtility::writeHtmlChecked($defaultValue['sortBy'],1);?>/><span class="padding-left"><?php AppUtility::t('Most recent reply date')?></span>
                            </div><br class="form"/>
                    </div>

                    <div class="item-alignment">
                        <div class="col-md-2 col-sm-2"><?php AppUtility::t('Students can create new threads')?></div>
                        <div class="col-md-10 col-sm-10">
                            <input type=radio name="post" value="2000000000" <?php if ($defaultValue['postBy']==2000000000) { echo "checked=1";}?>><span class="padding-left"><?php AppUtility::t('Always')?></span><br>
                            <input type=radio name="post" value="0" <?php if ($defaultValue['postBy']==0) { echo "checked=1";}?>><span class="padding-left"><?php AppUtility::t('Never')?></span><br>
                            <input type=radio name="post" class="pull-left " value="1" <?php if ($defaultValue['postBy'] == 1) { echo "checked=1";}?> >
                            <?php
                            echo '<label class="end pull-left non-bold padding-left"> Before</label>';
                            echo '<div class = "col-md-4 col-sm-4 time-input">';
                            echo DatePicker::widget([
                                'name' => 'postDate',
                                'type' => DatePicker::TYPE_COMPONENT_APPEND,
                                'value' => $defaultValue['postDate'],
                                'removeButton' => false,
                                'pluginOptions' => [
                                    'autoclose' => true,
                                    'format' => 'mm/dd/yyyy']
                            ]);
                            echo '</div>'; ?>
                            <?php
                            echo '<label class="end pull-left non-bold"> at </label>';
                            echo '<div class=" col-md-6 col-sm-6">';
                            echo TimePicker::widget([
                                'name' => 'postTime',
                                'value' =>  $defaultValue['postByTime'],
                                'pluginOptions' => [
                                    'showSeconds' => false,
                                    'class' => 'time'
                                ]
                            ]);
                            echo '</div>'; ?>
                        </div>
                        </div><BR class=form>
                        <div class="item-alignment">
                            <div class="col-md-2 col-sm-2"><?php AppUtility::t('Students can reply to posts')?></div>
                            <div class="col-md-10 col-sm-10">
                                <input type=radio name="reply" value="2000000000" <?php if ($defaultValue['replyBy']==2000000000) { echo "checked=1";}?>><span class="padding-left"><?php AppUtility::t('Always')?></span><br>
                                <input type=radio name="reply" value="0" <?php if ($defaultValue['replyBy']==0) { echo "checked=1";}?>><span class="padding-left"><?php AppUtility::t('Never')?></span><br>
                                <input type=radio name="reply" class="pull-left "value="1" <?php if ($defaultValue['replyBy'] == 1) { echo "checked=1";}?> >
                                <?php
                                echo '<label class="end pull-left non-bold padding-left">Before</label>';
                                echo '<div class = "col-md-4 col-sm-4 time-input">';
                                echo DatePicker::widget([
                                    'name' => 'replyByDate',
                                    'type' => DatePicker::TYPE_COMPONENT_APPEND,
                                    'value' => $defaultValue['replyByDate'],
                                    'removeButton' => false,
                                    'pluginOptions' => [
                                        'autoclose' => true,
                                        'format' => 'mm/dd/yyyy']
                                ]);
                                echo '</div>'; ?>
                                <?php
                                echo '<label class="end pull-left non-bold"> at </label>';
                                echo '<div class=" col-md-6 col-sm-6">';
                                echo TimePicker::widget([
                                    'name' => 'replyByTime',
                                    'value' => $defaultValue['replyByTime'],
                                    'pluginOptions' => [
                                        'showSeconds' => false,
                                        'class' => 'time'
                                    ]
                                ]);
                                echo '</div>'; ?>
                    </div></div><BR class=form>

                    <div class="item-alignment">
                        <div class="col-md-2 col-sm-2"><?php AppUtility::t('Calendar icon')?></div>
                           <div class="col-md-10 col-sm-10">
                        <?php AppUtility::t('New Threads')?><span class="padding-left"><input type="text" name="calendar-icon-text1" value="<?php echo $defaultValue['postTag'];?>" size="2"></span> ,
                        <label class="padding-left non-bold"><?php AppUtility::t('Replies')?><span class="padding-left"><input type="text" name="calendar-icon-text2" value="<?php echo $defaultValue['replyTag'];?>" size="2"></span></label>
                            </div><br class=form>
                    </div>
                 <div class="item-alignment">
                    <div class="col-md-2 col-sm-2"><?php AppUtility::t('Count in gradebook?')?></div>
                    <div class="col-md-10 col-sm-10">
                        <input type=radio name="count-in-gradebook" value="0" <?php if ($defaultValue['cntInGb'] == 0) { echo 'checked=1';}?> onclick="toggleGBdetail(false)"/><span class="padding-left"><?php AppUtility::t('No')?></span><br>
                        <input type=radio name="count-in-gradebook" value="1" <?php if ($defaultValue['cntInGb'] == 1) { echo 'checked=1';}?> onclick="toggleGBdetail(true)"/><span class='padding-left'><?php AppUtility::t('Yes')?></span><br>
                        <input type=radio name="count-in-gradebook" value="4" <?php if ($defaultValue['cntInGb'] == 4 && $defaultValue['points'] > 0) { echo 'checked=1';}?> onclick="toggleGBdetail(true)"/><span class='padding-left'><?php AppUtility::t('Yes, but hide from students for now')?></span><br>
                        <input type=radio name="count-in-gradebook" value="2" <?php if ($defaultValue['cntInGb'] == 2) { echo 'checked=1';}?> onclick="toggleGBdetail(true)"/><span class='padding-left'><?php AppUtility::t('Yes, as extra credit')?></span>
                </div></div><br class="form"/>

                    <div id="gbdetail" <?php if ($defaultValue['cntInGb']==0 && $defaultValue['points']==0) { echo 'style="display:none;"';}?>><br>

                        <div class="col-md-2 col-sm-2"><?php AppUtility::t('Points:')?></div>
                        <div class="col-md-10 col-sm-10">
                            <input type="text" name="points" value="<?php echo $defaultValue['points'];?>" size="3"> <?php AppUtility::t('Points:')?>
                    </div><br class=form>

                    <div class="item-alignment">
                        <div class="col-md-2 col-sm-2"><?php AppUtility::t('Gradebook Category')?></div>
                        <div class="col-md-4 col-sm-4">
                             <?php AssessmentUtility::writeHtmlSelect("gradebook-category",$gbcatsId,$gbcatsLabel,$defaultValue['gbCat'],"Default",0); ?>
                        </div>
                         <?php $page_tutorSelect['label'] = array("No access to scores","View Scores","View and Edit Scores");
                        $page_tutorSelect['val'] = array(2,0,1); ?>
                    </div><br class="form"/>

                    <div class="item-alignment">
                        <div class="col-md-2 col-sm-2"><?php AppUtility::t('Tutor Access:')?></div>
                        <div class="col-md-4 col-sm-4">
                            <?php AssessmentUtility::writeHtmlSelect("tutor-edit",$page_tutorSelect['val'],$page_tutorSelect['label'],$defaultValue['tutorEdit']); ?>
                        </div>
                    </div><br class=form>
                    <div class="item-alignment">
                        <div class="col-md-2 col-sm-2"><?php AppUtility::t('Use Scoring Rubric')?></div>
                        <div class="col-md-4 col-sm-4">
                                <?php AssessmentUtility::writeHtmlSelect('rubric',$rubricsId,$rubricsLabel,$defaultValue['rubric']); ?>
                                <a href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/add-rubric?cid='.$course->id.'&id=new&from=addf&fid'.$modifyForumId) ?>">
                                <?php AppUtility::t('Add new rubric')?></a> |
                            <a href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/add-rubric?cid='.$course->id.'&nomanage=&from=addf&fid='.$modifyForumId) ?>"><?php AppUtility::t('Edit rubrics')?></a>
                          </div>
                          <br class=form>
                    </div>
                        <?php if ($defaultValue['isOutcomes'] != null) { ?>
                    <div class="item-alignment">
                        <br>

                        <div class="col-md-2"><?php AppUtility::t('Associate Outcomes:')?></div>
                        <div class="col-md-10 col-sm-10">
                        <?php

                        if($defaultValue['outcomes'] != " "){
                            $gradeoutcomes = explode(',',$defaultValue['outcomes']);
                        }
                            AssessmentUtility::writeHtmlMultiSelect('outcomes', $pageOutcomesList, $pageOutcomes, $gradeoutcomes, 'Select an outcome...'); ?>
                            <br class="form"/>

                        <br class=form>
                    </div>
                    </div>
                        <?php } ?>
                        </div>
                        <div class="item-alignment">
                       <div class="col-md-2 col-sm-2"><?php AppUtility::t('Forum Type')?></div>
                            <div class="col-md-10 col-sm-10">
                                <input type=radio name="forum-type" value="0" <?php if ($defaultValue['forumType']==0) { echo 'checked=1';}?>/><span class="padding-left"><?php AppUtility::t('Regular forum')?></span><br>
                                <input type=radio name="forum-type" value="1" <?php if ($defaultValue['forumType']==1) { echo 'checked=1';}?>/><span class='padding-left'><?php AppUtility::t('File sharing forum')?></span><br>
                            </div>
                       </div><br class="form"/>
                    <div class="item-alignment">
                        <div class="col-md-2 col-sm-2"><?php AppUtility::t('Categorize posts?')?></div>
                         <div class="col-md-6 col-sm-6">
                             <input type=checkbox name="categorize-posts" value="1" <?php if ($defaultValue['tagList'] != '') {echo "checked=1";} ?>onclick="document.getElementById('tagholder').style.display=this.checked?'':'none';"/>
                              <span id="tagholder" style="display:<?php echo ($defaultValue['tagList'] == '') ? "none" : "inline"; ?>">
                              <span class="padding-left"><?php AppUtility::t('Enter in format CategoryDescription:category,category,category')?></span><br><br>
                              <input class="form-control" type="text" size="50" height="20" name="taglist" value="<?php echo $defaultValue['tagList']; ?>"  >
                              </span><br class=form><br class=form>
            </div>
                        <div class="header-btn col-sm-6 col-sm-offset-2 padding-top-ten padding-bottom-thirty">
                            <button class="btn btn-primary page-settings" type="submit" value="Submit"><i class="fa fa-share header-right-btn"></i><?php echo $saveTitle ?></button>
                        </div>
                   </div>
    </form>