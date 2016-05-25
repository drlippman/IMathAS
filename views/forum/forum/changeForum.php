<?php
use app\components\AssessmentUtility;
use app\components\AppUtility;
use app\components\AppConstant;
use kartik\date\DatePicker;
use kartik\time\TimePicker;
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use yii\helpers\HtmlPurifier;

$this->title = AppUtility::t('Mass Change Forums',false);
?>
<div class="item-detail-header">
    <!-- $course->name and $course->id are not html::encoded b/c $course data is populated in controller using getById (internal process not accessible by user )
         In addition, $this->title is not encoded either because it is hard-coded through AppUtility
    -->
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id], 'page_title' => $this->title]); ?>
</div>

<form id="mainform" method=post action="change-forum?cid=<?php echo Html::encode($course->id) ?>">
    <div class="title-container">
        <div class="row">
            <div class="pull-left page-heading">
                <div class="vertical-align title-page"><?php echo Html::encode($this->title) ?> </div>
            </div>
        </div>
    </div>

    <div class="tab-content shadowBox non-nav-tab-item">
        <div class="col-sm-12 col-md-12 padding-top-two-em">
            <?php
            if (count($forumItems) == 0)
            { ?>
                 <br><div style='  margin-left: 35%;'> <h4><p><?php AppUtility::t('No forums to change.');?></p></h4><br></div>
            <?php }else{
            AppUtility::t('Check:'); ?>
             <a href="#" onclick="return chkAllNone('mainform','checked[]',true)"><?php AppUtility::t('All');?></a>
             <a href="#" onclick="return chkAllNone('mainform','checked[]',false)"><?php AppUtility::t('None')?></a>
            <ul class=nomark>
                <?php
                foreach ($forumItems as $id => $name) {
                    echo '<li class="padding-left-zero"><input type="checkbox" name="checked[]" value="' . $id . '" /> ' . $name . '</li>';
                }
                ?>
            </ul>
        </div>
        <div class="col-sm-12 col-md-12">
            <p><?php AppUtility::t('With selected, make changes below');?>
                <h3><?php AppUtility::t('Forum Options')?></h3>
            <table class="col-sm-12 col-md-12 table table-bordered table-striped table-hover data-table">
                <thead>
                <tr>
                    <th class="col-sm-1 col-md-1"><?php AppUtility::t('Change?');?></th>
                    <th class="col-sm-2 col-md-2  "><?php AppUtility::t('Option');?></th>
                    <th class="col-sm-9 col-md-9 text-align-center"><?php AppUtility::t('Setting')?></th>
                </tr>
                </thead>
                <tbody>

                <tr class="coptr">
                    <td class="col-sm-1 col-md-1"><input type="checkbox" name="chg-avail" class="chgbox"/></td>
                    <td class="col-md-2"><?php AppUtility::t('Visibility')?></td>
                    <td class="col-md-9">
                        <input type=radio name="avail" value="1" checked="checked"/><span
                            class='padding-left'><?php AppUtility::t('Show by Dates')?></span>&nbsp;
                        <label class="non-bold"> <input type=radio name="avail"
                                                                                  value="0"/><span
                                class="padding-left"><?php AppUtility::t('Hide')?></span></label>&nbsp;
                        <label class="non-bold"><input type=radio name="avail"
                                                                                  value="2"/><span
                                class='padding-left'><?php AppUtility::t('Show Always')?></span>
                    </td>
                </tr>
                <tr class="coptr item-alignment">
                    <td class="col-sm-1 col-md-1"><input type="checkbox" name="chg-post-by" class="chgbox"/></td>
                    <td class=col-md-2><?php AppUtility::t('Students can create new threads')?></td>
                    <td class="col-md-9">
                        <input type=radio name="post" value="Always" checked="checked"><span
                            class="padding-left"><?php AppUtility::t('Always')?></span><br>
                        <input type=radio name="post" value="Never"><span
                            class="padding-left"><?php AppUtility::t('Never')?></span><br>
                        <input type=radio name="post" class="pull-left " value="Date">
                        <?php
                        echo '<label class="end pull-left non-bold padding-left"> Before</label>';
                        echo '<div class = "col-md-4 col-sm-4 time-input">';
                        echo DatePicker::widget([
                            'name' => 'postDate',
                            'type' => DatePicker::TYPE_COMPONENT_APPEND,
                            'value' => date("m/d/Y", strtotime("+1 week")),
                            'removeButton' => false,
                            'pluginOptions' => [
                                'autoclose' => true,
                                'format' => 'mm/dd/yyyy']
                        ]);
                        echo '</div>'; ?>
                        <?php
                        echo '<label class="end pull-left non-bold"> at </label>';
                        echo '<div class=" col-md-4 col-sm-5">';
                        echo TimePicker::widget([
                            'name' => 'postTime',
                            'pluginOptions' => [
                                'showSeconds' => false,
                                'class' => 'time'
                            ]
                        ]);
                        echo '</div>';
                        echo '</td>'; ?>
                </tr>
                <tr class="coptr item-alignment">
                    <td class="col-sm-1 col-md-1"><input type="checkbox" name="chg-reply-by" class="chgbox"/></td>
                    <td class="col-md-2 col-sm-2"><?php AppUtility::t('Students can reply to posts')?></td>
                    <td class="col-md-9 col-sm-9">
                        <input type=radio name="reply" value="Always" checked="checked"><span
                            class="padding-left"><?php AppUtility::t('Always')?></span><br>
                        <input type=radio name="reply" value="Never"><span
                            class="padding-left"><?php AppUtility::t('Never')?></span><br>
                        <input type=radio name="reply" class="pull-left " value="Date">
                         <label class="end pull-left non-bold padding-left"><?php AppUtility::t('Before')?></label>
                        <?php echo '<div class = "col-md-4 col-sm-4 time-input">';
                        echo DatePicker::widget([
                            'name' => 'replyByDate',
                            'type' => DatePicker::TYPE_COMPONENT_APPEND,
                            'value' => date("m/d/Y", strtotime("+1 week")),
                            'removeButton' => false,
                            'pluginOptions' => [
                                'autoclose' => true,
                                'format' => 'mm/dd/yyyy']
                        ]);
                        echo '</div>'; ?>
                        <?php
                        echo '<label class="end pull-left non-bold"> at </label>';
                        echo '<div class=" col-md-4 col-sm-5">';
                        echo TimePicker::widget([
                            'name' => 'replyByTime',
                            'pluginOptions' => [
                                'showSeconds' => false,
                                'class' => 'time'
                            ]
                        ]);
                        echo '</div>'; ?>
                    </td>
                </tr>

                <tr class="coptr item-alignment">
                    <td class="col-sm-1 col-md-1"><input type="checkbox" name="chg-cal-tag" class="chgbox"/></td>
                    <td class="col-md-2 col-sm-2"><?php AppUtility::t('Calendar icon')?></td>
                    <td class="col-md-9 col-sm-9">
                        <?php AppUtility::t('New Threads')?>
                        <span class="padding-left">
                            <input class="form-control display-inline-block width-ten-per" type="text" name="cal-tag-post" value="FP" ></span> ,
                        <label class=" non-bold"><?php AppUtility::t('Replies')?></label><span class="padding-left"><input
                                   class="form-control display-inline-block width-ten-per" type="text" name="caltagreply" value="FR" size="2"></span>
                    </td>
                </tr>


                <tr class="coptr item-alignment">
                    <td class="col-sm-1 col-md-1"><input type="checkbox" name="chg-allow-anon" class="chgbox"/></td>
                    <td class="col-md-2 col-sm-2"><?php AppUtility::t('Allow anonymous posts')?></td>
                    <td class="col-md-9 col-sm-9">
                        <input type="checkbox" name="allow-anonymous-posts" value="1"><br>
                    </td>
                </tr>

                <tr class="coptr item-alignment">
                    <td class="col-sm-1 col-md-1"><input type="checkbox" name="chg-allow-mod" class="chgbox"/></td>
                    <td class="col-md-2 col-sm-2"><?php AppUtility::t('Allow students to modify posts')?></td>
                    <td class="col-md-9 col-sm-9">
                        <input type="checkbox" name="allow-students-to-modify-posts" value="1"><br>
                    </td>
                </tr>

                <tr class="coptr item-alignment">
                    <td class="col-sm-1 col-md-1"><input type="checkbox" name="chg-allow-del" class="chgbox"/></td>
                    <td class="col-md-2 col-sm-2"><?php AppUtility::t('Allow students to delete own posts (if no replies)')?></td>
                    <td class="col-md-9 col-sm-9">
                        <input type="checkbox" name="allow-students-to-delete-own-posts" value="1"><br>
                    </td>
                </tr>

                <tr class="coptr item-alignment">
                    <td class="col-sm-1 col-md-1"><input type="checkbox" name="chg-allow-likes" class="chgbox"/></td>
                    <td class="col-md-2 col-sm-2"><?php AppUtility::t('Turn on "liking" posts')?></td>
                    <td class="col-md-9 col-sm-9">
                        <input type="checkbox" name="like-post" value="1"><br>
                    </td>
                </tr>

                <tr class="coptr item-alignment">
                    <td class="col-sm-1 col-md-1"><input type="checkbox" name="chg-view-before-post" class="chgbox"/></td>
                    <td class="col-md-2 col-sm-2"><?php AppUtility::t('Viewing before posting')?></td>
                    <td class="col-md-9 col-sm-9">
                        <input type="checkbox" name="viewing-before-posting" value="1">
                        <label class="padding-left display-inline non-bold">
                            <?php AppUtility::t('Prevent students from viewing posts until they have created a thread.
                            You will likely also want to disable modifying posts')?></label>
                    </td>
                </tr>

                <tr class="coptr item-alignment">
                    <td class="col-sm-1 col-md-1"><input type="checkbox" name="chg-subscribe" class="chgbox"/></td>
                    <td class="col-md-2 col-sm-2"><?php AppUtility::t('Get email notify of new posts')?></td>
                    <td class="col-md-9 col-sm-9">
                        <input type="checkbox" name="Get-email-notify-of-new-posts" value="1"><br>
                    </td>
                </tr>

                <tr class="coptr item-alignment">
                    <td class="col-sm-1 col-md-1"><input type="checkbox" name="chg-def-display" class="chgbox"/></td>
                    <td class="col-md-2 col-sm-2"><?php AppUtility::t('Default display')?></td>
                    <td class="col-md-9 col-md-9">
                        <select name="default-display" class="form-control width-fourty-per">
                            <option value="0"><?php AppUtility::t('Expanded');?></option>
                            <option value="1"><?php AppUtility::t('Collapsed');?></option>
                            <option value="2"><?php AppUtility::t('Condensed');?></option>
                        </select>
                    </td>
                </tr>

                <tr class="coptr item-alignment">
                    <td class="col-sm-1 col-md-1"><input type="checkbox" name="chg-sort-by" class="chgbox"/></td>
                    <td class="col-md-2 col-sm-2"><?php AppUtility::t('Sort threads by')?></td>
                    <td class="col-md-9 col-sm-9">
                        <input type=radio name="sort-thread" value="0" checked/><span
                            class="padding-left"><?php AppUtility::t('Thread start date')?></span><br>
                        <input type=radio name="sort-thread" value="1"/><span
                            class="padding-left"><?php AppUtility::t('Most recent reply date')?></span>
                    </td>
                </tr>

                <tr class="coptr item-alignment">
                    <td class="col-sm-1 col-md-1"><input type="checkbox" name="chg-cnt-in-gb" class="chgbox"/></td>
                    <td class="col-md-2 col-sm-2"><?php AppUtility::t('Count')?></td>
                    <td class="col-md-9 col-sm-9">
                        <input type=radio name="count-in-gradebook" value="0" checked/><span
                            class="padding-left"><?php AppUtility::t('No')?></span><br>
                        <input type=radio name="count-in-gradebook" value="1"/><span
                            class='padding-left'><?php AppUtility::t('Yes')?></span><br>
                        <input type=radio name="count-in-gradebook" value="4"/><span
                            class='padding-left'><?php AppUtility::t('Yes, but hide from students for now')?></span><br>
                        <input type=radio name="count-in-gradebook" value="2"/><span
                            class='padding-left'><?php AppUtility::t('Yes, as extra credit')?></span><br>
                        <?php AppUtility::t('If yes, for:')?>
                        <span class="padding-left-one-em padding-right-one-em"><input class="form-control display-inline-block width-ten-per" type=text size=4 name="points" value=""/></span>
                        <?php AppUtility::t('points (leave blank to not change)')?>
                    </td>
                </tr>

                <tr class="coptr item-alignment">
                    <td class="col-sm-1 col-md-1"><input type="checkbox" name="chg-gb-cat" class="chgbox"/></td>
                    <td class="col-md-2 col-sm-2"><?php AppUtility::t('Gradebook Category')?></td>
                    <td class="col-md-4 col-sm-9">
                        <div class="width-fourty-per">
                        <?php AssessmentUtility::writeHtmlSelect("gradebook-category", $gbCatsId, $gbCatsLabel, null, "Default", 0, " id=gbcat"); ?>
                        </div>
                    </td>
                </tr>

                <tr class="coptr item-alignment">
                    <td class="col-sm-1 col-md-1"><input type="checkbox" name="chg-forum-type" class="chgbox"/></td>
                    <td class="col-sm-2 col-md-2"><?php AppUtility::t('Forum Type')?></td>
                    <td class="col-sm-9 col-md-9">
                        <input type=radio name="forum-type" checked value="0"/><span
                            class="padding-left"><?php AppUtility::t('Regular forum')?></span><br>
                        <input type=radio name="forum-type" value="1"/><span
                            class='padding-left'><?php AppUtility::t('File sharing forum')?></span>
                    </td>
                </tr>

                <tr class="coptr item-alignment">
                    <td class="col-sm-1 col-md-1"><input type="checkbox" name="chg-tag-list" class="chgbox"/></td>
                    <td class="col-md-2 col-sm-2"><?php AppUtility::t('Categorize posts?')?></td>
                    <td class="col-md-9 col-sm-9">
                        <input type=checkbox name="use-tags" value="1" <?php if ($defaultValue['tagList'] != '') {
                            echo "checked=1";
                        } ?>onclick="document.getElementById('tagholder').style.display=this.checked?'':'none';"/>
                              <span id="tagholder"
                                    style="display:<?php echo ($defaultValue['tagList'] == '') ? "none" : "inline"; ?>">
                              <span
                                  class="padding-left"><?php AppUtility::t('Enter in format CategoryDescription:category,category,category')?></span><br><br>
                              <input class="form-control" type="text" size="50" height="20" name="taglist"
                                     value="<?php echo $defaultValue['tagList']; ?>">
                              </span>
                    </td>
                </tr>

                </tbody>
            </table>
        </div>
        <?php if (count($forumItems) != 0) { ?>
            <div class="header-btn col-sm-4 padding-top-ten padding-bottom-thirty">
                <button class="btn btn-primary page-settings padding-left-one-em padding-right-one-em" type="submit" value="Submit"><i
                        class="fa fa-share header-right-btn"></i><?php AppUtility::t('Apply Changes') ?></button>
            </div>
        <?php } ?>
    </div>
    <?php } ?>
</form>

	
	
	

