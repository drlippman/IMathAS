<?php
$this->title = 'Add New Thread';
$this->params['breadcrumbs'][] = $this->title;
use kartik\date\DatePicker;
use kartik\time\TimePicker;
?>
<div class="" xmlns="http://www.w3.org/1999/html">
    <h3><b>Add Thread - <?php echo $forumName->name;?></h3>
    <br><br>
    <div>
        <div class="col-md-2"><b>Subject</b></div>
        <div class="col-md-8"><input class="subject form-control" type="text"></div>
    </div>
    <br><br><br>
    <div>
        <div class="col-md-2"><b>Message</b></div>
        <?php echo "<div class='left col-md-10'><div class= 'editor'>
        <textarea id='message' name='message' style='width: 100%;' rows='20' cols='20'></textarea></div></div><br>"; ?>
    </div>
    <div>
        <br>
        <input type="hidden" id="userId" value="<?php echo $userId; ?>">
        <input type="hidden" id="forumId" value="<?php echo $forumName->id; ?>">
        <input type="hidden" id="courseId" value="<?php echo $courseid; ?>">

    </div>
    <?php if($rights > 10)
    {?>
         <div >
            <span class="col-md-2"><b>Post Type:</b></span>
        <span class="col-md-10" id="rdaiobtn">
            <input type="radio" name="PostType" id="self" value="Regular" checked="checked"> Regular<br>
            <input type="radio" name="PostType" id="self" value="Displayedattopoflist" >Displayed at top of list<br>
            <input type="radio" name="PostType" id="self" value="Displayedattopandlocked"> Displayed at top and locked (no replies)<br>
            <input type="radio" name="PostType" id="self" value="onlyStudentscansee"> Displayed at top and students can only see their own replies <br>
            </span>
        </div>
        <div>
            <span class="col-md-2"><b>Always Replies:</b></span>
        <span class="col-md-10">
            <input type="radio" name="AlwaysReplies" id="self" value="Usedefault" checked="checked"> Use default<br>
            <input type="radio" name="PostType" id="self" value="Always" >Always<br>
            <input type="radio" name="PostType" id="self" value="Never"> Never<br>
            <input type="radio" name="PostType" class="end pull-left"  id="self" value="Before"><label class="end pull-left">Before</label>


                <div class="col-md-3 " id="datepicker-id">
                   <?php
                   echo DatePicker::widget([
                       'name' => 'date_picker',
                       'type' => DatePicker::TYPE_COMPONENT_APPEND,
                       'value' => date("m-d-Y",strtotime("+1 week ")),
                       'pluginOptions' => [
                           'autoclose' => true,
                           'format' => 'mm-dd-yyyy'
                       ]
                   ]);
                 echo '</div>';?>

                   <?php
                   echo '<label class="end pull-left "> At</label>';
                   echo '<div class="pull-left col-lg-4">';
                   echo TimePicker::widget([
                       'name' => 'end_time',
                       'pluginOptions' => [
                           'showSeconds' => false,
                           'class' => 'time'
                       ]
                   ]);

                   echo '</div>';?>
            </div>

            </span>
        </div>
 <?php }?>
    <div class="col-md-4  col-lg-offset-2">
    <input type="button" class="btn btn-primary" id="addNewThread" value="Post Thread">
        </div>
    
</div>

