<?php
use app\components\AppUtility;
use app\components\AppConstant;
use app\components\AssessmentUtility;
$this->title = AppUtility::t('Sent Message ',false);
$this->params['breadcrumbs'][] = $this->title;
$currentTime = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
$now = $currentTime;
$address = AppUtility::getURLFromHome('message', 'message/sent-message?cid='.$course->id.'&filtercid=');
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name,AppUtility::t('Message',false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id,AppUtility::getHomeURL() . 'message/message/index?cid=' . $course->id]]); ?>
</div>
<div class ="title-container padding-bottom-two-em">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo AppUtility::t('Message:',false);?><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course, 'section' => '']);?>
</div>
<input type="hidden" class="send-course-id" value="<?php echo $course->id ?>">
<input type="hidden" class="send-user-id" value="<?php echo $course->ownerid ?>">
<div class="tab-content shadowBox col-md-12 col-sm-12 padding-left-right-zero padding-bottom-two-em">
    <div class="col-md-12 col-sm-12 second-level-message-navigation mobile-padding-right-zero">
        <div class="col-md-2 col-sm-3 padding-left-right-zero padding-top-pt-five-em">
            <a  href="<?php echo AppUtility::getURLFromHome('message', 'message/index?cid='.$course->id); ?>"><?php echo AppUtility::t('Received Messages')?></a>
        </div>
        <div class="col-md-10 padding-left-right-zero">

            <div class="col-md-4 col-sm-5 padding-left-right-zero">
                <span class="select-text-margin floatleft">Filter by course:</span>
               <span class="col-md-7 col-sm-7 mobile-float-right">
                   <select id="filtercid" class="form-control" onchange="chgfilter()">
                       <?php
                       echo "<option value=\"0\" ";
                       if ($filtercid==0) {
                           echo "selected=1 ";
                       }
                       echo ">All courses</option>";
                       foreach($byCourse as $row){
                           echo "<option value=\"{$row['id']}\" ";
                           if ($filtercid==$row['id']) {
                               echo 'selected=1';
                           }
                           $CourseName = AppUtility::truncate($row['name'], 60);
                           echo " >{$CourseName}</option>";
                       }
                       echo "</select></span> ";?>
            </div>


                <div class="col-md-4 col-sm-4 padding-left-right-zero">
                <span class="select-text-margin floatleft">
                    <?php echo 'By recipient: </span>
                    <span class="col-md-8 col-sm-8 mobile-float-right">
                    <select id="filteruid" class="form-control" onchange="chgfilter()"><option value="0" ';
                        if ($filteruid==0) {
                        echo 'selected="selected" ';
                        }
                        echo '>All</option>';
                    foreach($byRecipient as $row){
                        echo "<option value=\"{$row['id']}\" ";
                            if ($filteruid==$row['id']) {
                            echo 'selected=1';
                            }
                        echo " > ";
                        echo "{$row['LastName']}, {$row['FirstName']}</option> ";

                        }
                    echo "</select></p>";
                    ?>
                </span>
            </div>

            <div class="col-md-4 col-sm-4 padding-left-right-zero mobile-padding-top-one-em">
                <span class="select-text-margin floatleft padding-left-one-em mobile-padding-left-zero">
                    <?php echo AppUtility::t('With Selected')?>
                </span>
                <span class="col-md-7 col-sm-7 padding-right-zero floatright padding-left-zero">
                    <select onchange="changeMessageStatus()"  class="form-control with-selected">
                        <option value="0"><?php echo AppUtility::t('Select')?></option>
                        <option value="1" id="mark-sent-delete"><?php echo AppUtility::t('Remove From Sent Message List')?></option>
                        <?php if($isTeacher) {?>
                        <option value="2" id="mark-unsend"><?php echo AppUtility::t('Unsend')?></option>
                        <?php }?>
                    </select>
                </span>
            </div>
        </div>

    </div>
    <div class="message-div">
    <table id="message-table-show display-message-table" class="display-message-pagination display table table-bordered table-striped table-hover data-table">
        <thead>
        <tr><th>
                <div class='checkbox override-hidden'>
                    <label><input type='checkbox' id='message-header-checkbox' name='header-checked' value=''>
                        <span class='cr'><i class='cr-icon fa fa-check'></i></span>
                    </label>
                </div>
        </th><th>Message</th><th>To</th><th>Read</th><th>Sent</th></tr>
        </thead>
        <tbody class="message-table-body">
        <?php

        if (count($displayMessage)==0) {
            echo "<tr><td></td><td>No messages</td><td></td><td></td></tr>";
        }
        foreach($displayMessage as $line) {
            if (trim($line['title'])=='') {
                $line['title'] = '[No Subject]';
            }
            $n = 0;
            while (strpos($line['title'],'Re: ')===0) {
                $line['title'] = substr($line['title'],4);
                $n++;
            }
            if ($n==1) {
                $line['title'] = 'Re: '.$line['title'];
            } else if ($n>1) {
                $line['title'] = "Re<sup>$n</sup>: ".$line['title'];
            }
            echo "<tr class='message-checkbox-\"{$line['id']}\"'><td><div class='checkbox override-hidden'><label>

            <input type=checkbox name=\"msg-check\" class='message-checkbox-\"{$line['id']}\"' value=\"{$line['id']}\"/><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></div></td><td>";
//		echo "<a href=\"viewmsg.php?page$page&cid=$cid&filtercid=$filtercid&filteruid=$filteruid&type=sent&msgid={$line['id']}\">";
            echo $line['title'];
            echo "</a></td>";
            echo "<td>{$line['LastName']}, {$line['FirstName']}</td>";
            if (($line['isread']&1)==1) {
                echo "<td>Yes</td>";
            } else {
                echo "<td>No</td>";
            }
            $senddate = AppUtility::tzdate("F j, Y, g:i a",$line['senddate']);
            echo "<td>$senddate</td></tr>";
        }
        ?>
        </tbody>
    </table>
    </div>
</div>

<script type="text/javascript">
    function chgfilter() {
        var filtercid = document.getElementById("filtercid").value;
        var filteruid = document.getElementById("filteruid").value;
        window.location = "<?php echo $address;?>"+filtercid+"&filteruid="+filteruid;
    }
</script>