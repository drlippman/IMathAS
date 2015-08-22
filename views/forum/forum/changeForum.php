<?php
use app\components\AssessmentUtility;
use app\components\AppUtility;
use app\components\AppConstant;
use kartik\date\DatePicker;
use kartik\time\TimePicker;
$this->title = 'Mass Change Forums';
$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$course->name], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'instructor/instructor/index?cid='.$course->id], 'page_title' => $this->title]); ?>
</div>

<form id="mainform" method=post action="change-forum?cid=<?php echo $course->id ?>" onsubmit="return valform();">
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?> </div>
        </div>
        <div class="pull-left header-btn">
            <button class="btn btn-primary pull-right page-settings" type="submit" value="Submit"><i class="fa fa-share header-right-btn"></i><?php echo 'Apply Changes' ?></button>
        </div>
    </div>
</div>
    <div class="tab-content shadowBox non-nav-tab-item">
    <?php

if (count($forumItems)==0) {
    echo '<p>No forums to change.</p>';

    exit;
}
?>


    <div class="">
    Check: <a href="#" onclick="return chkAllNone('mainform','checked[]',true)">All</a> <a href="#" onclick="return chkAllNone('mainform','checked[]',false)">None</a>

<ul class=nomark>
    <?php
    foreach($forumItems as $id=>$name) {
        echo '<li><input type="checkbox" name="checked['.$id.']" value="'.$id.'" /> '.$name.'</li>';
    }
    ?>
</ul>
    </div>
<p>With selected, make changes below
<fieldset>
    <legend>Forum Options</legend>
    <table class="table table-bordered table-striped table-hover data-table">
        <thead>
        <tr><th>Change?</th><th>Option</th><th>Setting</th></tr>
        </thead>
        <tbody >

            <tr class="coptr">
                <td><input type="checkbox" name="chgavail" class="chgbox"/></td>
        <td class="col-lg-2"><?php AppUtility::t('Visibility')?></td>
        <td class="col-lg-10">
            <input type=radio name="avail" value="1" checked="checked" /><span class='padding-left'><?php AppUtility::t('Show by Dates')?></span>
            <label class="non-bold" style="padding-left: 80px"><input type=radio name="avail" value="0"  /><span class="padding-left"><?php AppUtility::t('Hide')?></span></label>
            <label class="non-bold" style="padding-left: 80px"><input type=radio name="avail" value="2"  /><span class='padding-left'><?php AppUtility::t('Show Always')?></span>
                </td>
</tr>

        <tr class="coptr item-alignment">
            <td><input type="checkbox" name="chg-post-by" class="chgbox" /></td>
            <td class=col-lg-2><?php AppUtility::t('Students can create new threads')?></td>
            <td class="col-lg-10">
                <input type=radio name="post" value="Always" checked="checked" ><span class="padding-left"><?php AppUtility::t('Always')?></span><br>
                <input type=radio name="post" value="Never"  ><span class="padding-left"><?php AppUtility::t('Never')?></span><br>
                <input type=radio name="post" class="pull-left " value="Date"  >
                <?php
                echo '<label class="end pull-left non-bold padding-left"> Before</label>';
                echo '<div class = "col-lg-4 time-input">';
                echo DatePicker::widget([
                    'name' => 'postDate',
                    'type' => DatePicker::TYPE_COMPONENT_APPEND,
                    'value' => date("m/d/Y",strtotime("+1 week")),
                    'removeButton' => false,
                    'pluginOptions' => [
                        'autoclose' => true,
                        'format' => 'mm/dd/yyyy']
                ]);
                echo '</div>'; ?>
                <?php
                echo '<label class="end pull-left non-bold"> at </label>';
                echo '<div class=" col-lg-6">';
                echo TimePicker::widget([
                    'name' => 'postTime',
//                    'value' =>  $defaultValue['postByTime'],
                    'pluginOptions' => [
                        'showSeconds' => false,
                        'class' => 'time'
                    ]
                ]);
                               echo '</td>'; ?>
            </tr>



<tr class="coptr item-alignment">
    <td><input type="checkbox" name="chg-reply-by" class="chgbox" /></td>
            <td class=col-lg-2><?php AppUtility::t('Students can reply to posts')?></td>
            <td class="col-lg-10">
                <input type=radio name="reply" value="Always" checked="checked" ><span class="padding-left"><?php AppUtility::t('Always')?></span><br>
                <input type=radio name="reply" value="Never"  ><span class="padding-left"><?php AppUtility::t('Never')?></span><br>
                <input type=radio name="reply" class="pull-left "value="Date" >
                <?php
                echo '<label class="end pull-left non-bold padding-left">Before</label>';
                echo '<div class = "col-lg-4 time-input">';
                echo DatePicker::widget([
                    'name' => 'replyByDate',
                    'type' => DatePicker::TYPE_COMPONENT_APPEND,
                    'value' => date("m/d/Y",strtotime("+1 week")),
                    'removeButton' => false,
                    'pluginOptions' => [
                        'autoclose' => true,
                        'format' => 'mm/dd/yyyy']
                ]);
                echo '</div>'; ?>
                <?php
                echo '<label class="end pull-left non-bold"> at </label>';
                echo '<div class=" col-lg-6">';
                echo TimePicker::widget([
                    'name' => 'replyByTime',
//                    'value' => $defaultValue['replyByTime'],
                    'pluginOptions' => [
                        'showSeconds' => false,
                        'class' => 'time'
                    ]
                ]);
                echo '</div>'; ?>
                </td></tr>

<tr class="coptr item-alignment">
    <td><input type="checkbox" name="chg-cal-tag" class="chgbox" /></td>
            <td class=col-lg-2><?php AppUtility::t('Calendar icon')?></td>
            <td class=col-lg-10>
                <?php AppUtility::t('New Threads')?><span class="padding-left"><input type="text" name="caltagpost" value="FP" size="2"></span> ,
                <label class="padding-left non-bold"><?php AppUtility::t('Replies')?><span class="padding-left"><input type="text" name="caltagreply" value="FR" size="2"></span></label>
            </td>
        </tr>




<tr class="coptr item-alignment">
    <td><input type="checkbox" name="chg-allow-anon" class="chgbox"/></td>
    <td class=col-lg-2><?php AppUtility::t('Allow anonymous posts')?></td>
    <td class=col-lg-10>
        <input type="checkbox" name="allow-anonymous-posts" value="1"><br>
    </td>
</tr>

<tr class="coptr item-alignment">
    <td><input type="checkbox" name="chg-allow-mod" class="chgbox"/></td>
    <td class=col-lg-2><?php AppUtility::t('Allow students to modify posts')?></td>
    <td class=col-lg-10>
        <input type="checkbox" name="allow-students-to-modify-posts" value="1" ><br>
    </td>
</tr>

<tr class="coptr item-alignment">
    <td><input type="checkbox" name="chg-allow-del" class="chgbox"/></td>
    <td class=col-lg-2><?php AppUtility::t('Allow students to delete own posts (if no replies)')?></td>
    <td class=col-lg-10>
        <input type="checkbox" name="allow-students-to-delete-own-posts" value="1" ><br>
    </td>
</tr>

<tr class="coptr item-alignment">
    <td><input type="checkbox" name="chg-allow-likes" class="chgbox"/></td>
<td class=col-lg-2><?php AppUtility::t('Turn on "liking" posts')?></td>
<td class=col-lg-10>
    <input type="checkbox" name="like-post" value="1" ><br>
    </td>
</tr>

<tr class="coptr item-alignment">
    <td><input type="checkbox" name="chg-view-before-post" class="chgbox"/></td>
    <td class=col-lg-2><?php AppUtility::t('Viewing before posting')?></td>
    <td class=col-lg-10>
        <input type="checkbox" name="viewing-before-posting" value="1" >
        <label class="padding-left non-bold"><?php AppUtility::t('Prevent students from viewing posts until they have created a thread.
                            You will likely also want to disable modifying posts')?></label>
    </td>
</tr>

<tr class="coptr item-alignment">
    <td><input type="checkbox" name="chg-subscribe" class="chgbox"/></td>
    <td class=col-lg-2><?php AppUtility::t('Get email notify of new posts')?></td>
    <td class=col-lg-10>
        <input type="checkbox" name="Get-email-notify-of-new-posts" value="1" ><br>
    </td>
</tr>

<tr class="coptr item-alignment">
    <td><input type="checkbox" name="chg-def-display" class="chgbox"/></td>
    <td class=col-lg-2><?php AppUtility::t('Default display')?></td>
    <td class=col-lg-4>
        <select name="default-display" class="form-control">
            <option value="0"  >Expanded</option>
            <option value="1"  >Collapsed</option>
            <option value="2"  >Condensed</option>
        </select>
    </td>
</tr>

<tr class="coptr item-alignment">
    <td><input type="checkbox" name="chg-sort-by" class="chgbox"/></td>
    <td class=col-lg-2><?php AppUtility::t('Sort threads by')?></td>
    <td class=col-lg-10>
        <input type=radio name="sort-thread" value="0" checked /><span class="padding-left"><?php AppUtility::t('Thread start date')?></span><br>
        <input type=radio name="sort-thread" value="1" /><span class="padding-left"><?php AppUtility::t('Most recent reply date')?></span>
    </td>
</tr>

<tr class="coptr item-alignment">
    <td><input type="checkbox" name="chg-cnt-in-gb" class="chgbox"/></td>
    <td class=col-lg-2><?php AppUtility::t('Count')?></td>
    <td class=col-lg-10>
        <input type=radio name="count-in-gradebook" value="0" checked /><span class="padding-left"><?php AppUtility::t('No')?></span><br>
        <input type=radio name="count-in-gradebook" value="1" /><span class='padding-left'><?php AppUtility::t('Yes')?></span><br>
        <input type=radio name="count-in-gradebook" value="4" /><span class='padding-left'><?php AppUtility::t('Yes, but hide from students for now')?></span><br>
        <input type=radio name="count-in-gradebook" value="2" /><span class='padding-left'><?php AppUtility::t('Yes, as extra credit')?></span><br>
        If yes, for: <input type=text size=4 name="points" value=""/> points (leave blank to not change)
    </td>
</tr>

<tr class="coptr item-alignment">
    <td><input type="checkbox" name="chg-gb-cat" class="chgbox"/></td>
    <td class=col-lg-2><?php AppUtility::t('Gradebook Category')?></td>
    <td class=col-lg-4>
        <?php AssessmentUtility::writeHtmlSelect("gradebook-category",$gbcatsId,$gbcatsLabel,null,"Default",0," id=gbcat"); ?>
    </td>
    <?php $page_tutorSelect['label'] = array("No access to scores","View Scores","View and Edit Scores");
    $page_tutorSelect['val'] = array(2,0,1); ?>
</tr>

<tr class="coptr item-alignment">
    <td><input type="checkbox" name="chg-forum-type" class="chgbox"/></td>
    <td class=col-lg-2><?php AppUtility::t('Forum Type')?></td>
    <td class=col-lg-10>
        <input type=radio name="forum-type" value="0" /><span class="padding-left"><?php AppUtility::t('Regular forum')?></span><br>
        <input type=radio name="forum-type" value="1" /><span class='padding-left'><?php AppUtility::t('File sharing forum')?></span>
    </td>
</tr>

<tr class="coptr item-alignment">
    <td><input type="checkbox" name="chg-tag-list" class="chgbox"/></td>
    <td class=col-lg-2><?php AppUtility::t('Categorize posts?')?></td>
    <td class=col-lg-6>
        <input type=checkbox name="use-tags" value="1" <?php if ($defaultValue['tagList'] != '') {echo "checked=1";} ?>onclick="document.getElementById('tagholder').style.display=this.checked?'':'none';"/>
                              <span id="tagholder" style="display:<?php echo ($defaultValue['tagList'] == '') ? "none" : "inline"; ?>">
                              <span class="padding-left"><?php AppUtility::t('Enter in format CategoryDescription:category,category,category')?></span><br><br>
                              <input class="form-control" type="text" size="50" height="20" name="taglist" value="<?php echo $defaultValue['tagList']; ?>"  >
                              </span>
    </td>
</tr>

        </tbody>
    </table>
</fieldset>

</form>
    </div>
	
	
	

