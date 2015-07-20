
<?php
use app\components\AppUtility;
use app\components\AssessmentUtility;
use app\components\AppConstant;
use app\components\CourseItemsUtility;
$this->title = ucfirst($course->name);
$this->params['breadcrumbs'][] = $this->title;
$cnt=0;
?>
<link href='<?php echo AppUtility::getHomeURL() ?>css/fullcalendar.print.css' rel='stylesheet' media='print'/>
<!--<div class="mainbody">-->

<div>
    <?php
    $currentTime = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
    $now = $currentTime;

    echo $this->render('_toolbarTeacher', ['course' => $course]); ?>
</div>
<input type="hidden" class="calender-course-id" id="courseIdentity" value="<?php echo $course->id ?>">
<input type="hidden" class="home-path"
       value="<?php echo AppUtility::getURLFromHome('instructor', 'instructor/index?cid=' . $course->id) ?>">
<div class="col-lg-2 needed pull-left">
    <?php echo $this->render('_leftSideTeacher', ['course' => $course, 'messageList' => $messageList]); ?>
</div>

<!--Course name-->
<div class="col-lg-10 pull-left">
<div class="course-title">
    <h3><b><?php echo ucfirst($course->name) ?></b></h3>

    <div class="col-lg-offset-3 buttonAlignment">
        <div class="view">
            <p>View:</p>
        </div>
        <a class="btn btn-primary ">Instructor</a>
        <a class="btn btn-primary" href="#">Student</a>
        <a class="btn btn-primary" href="#">Quick Rearrange</a>
    </div>
</div>
<div class="course-content">
    <?php if (!$courseDetail) { ?>
        <p><strong>Welcome to your course!</strong></p>

        <p> To start by copying from another course, use the <a href="#">Course Items: Copy link</a> along the left
            side
            of the screen. </p>

        <p> If you want to build from scratch, use the "Add An Item" pulldown below to get started. </p>

    <?php } ?></div>
<div class="col-lg-3 pull-left padding-zero">
    <?php
    $parent = AppConstant::NUMERIC_ZERO;
    $tb = 't';
    $html = "<select class='form-control' name=addtype id=\"addtype$parent-$tb\" onchange=\"additem('$parent','$tb')\" ";
    if ($tb == 't') {
        $html .= 'style="margin-bottom:5px;"';
    }
    $html .= ">\n";
    $html .= "<option value=\"\">" . _('Add An Item...') . "</option>\n";
    $html .= "<option value=\"assessment\">" . _('Add Assessment') . "</option>\n";
    $html .= "<option value=\"inlinetext\">" . _('Add Inline Text') . "</option>\n";
    $html .= "<option value=\"linkedtext\">" . _('Add Link') . "</option>\n";
    $html .= "<option value=\"forum\">" . _('Add Forum') . "</option>\n";
    $html .= "<option value=\"wiki\">" . _('Add Wiki') . "</option>\n";
    $html .= "<option value=\"block\">" . _('Add Block') . "</option>\n";
    $html .= "<option value=\"calendar\">" . _('Add Calendar') . "</option>\n";
    $html .= "</select><BR>\n";
    echo $html;
    ?>
</div>
<br><br><br>
<!-- ////////////////// Assessment here //////////////////-->


<?php $countCourseDetails = count($courseDetail);
if ($countCourseDetails){

$assessment = $blockList = array();
foreach ($courseDetail as $key => $item){
echo AssessmentUtility::createItemOrder($key, $countCourseDetails, $parent, $blockList);
switch (key($item)):
case 'Assessment': ?>
<?php CourseItemsUtility::AddAssessment($assessment,$item,$course,$currentTime,$parent); ?>
<?php break; ?>
<!-- ///////////////////////////// Forum here /////////////////////// -->,
<?php case 'Forum': ?>
<?php CourseItemsUtility::AddForum($item,$course,$currentTime,$parent); ?>
<?php break; ?>
<!-- ////////////////// Wiki here //////////////////-->
<?php case 'Wiki': ?>
<?php CourseItemsUtility::AddWiki($item,$course,$parent); ?>
<?php break; ?>
<!-- ////////////////// Linked text here //////////////////-->
<?php
case 'LinkedText': ?>
<?php CourseItemsUtility::AddLink($item,$currentTime,$parent,$course);?>
<?php break; ?>
<!-- ////////////////// Inline text here //////////////////-->
<?php case 'InlineText': ?>
<?php CourseItemsUtility::AddInlineText($item,$currentTime,$course,$parent);?>
<?php break; ?>
<!-- Calender Here-->
<?php case 'Calendar': ?>
<?php CourseItemsUtility::AddCalendar($item,$parent,$course);?>
<?php break; ?>
<!--  Block here-->
<?php case  'Block': ?>
    <?php  $cnt++; ?>
    <?php $displayBlock = new CourseItemsUtility();
    $displayBlock->DisplayWholeBlock($item,$currentTime,$assessment,$course,$parent,$cnt);
    ?>
    <?php break; ?>
<?php endswitch;
    ?>

<?php }?>

<?php } ?>
</div>

<script>
    $(document).ready(function ()
    {
      var SH = $('#SH').val();
        var id = $('#id').val();
       var isHidden = $('#isHidden').val();
        if(SH == 'HC')
        {
             var node = document.getElementById('block5' + id);
            var img = document.getElementById('img' + id);
            if (node.className == 'blockitems')
            {
                node.className = 'hidden';
                img.src = '../../img/expand.gif'
            }
        }
    });
    function xyz(e,id)
    {
        var node = document.getElementById('block5' + id);
        var img = document.getElementById('img' + id);
        if (node.className == 'blockitems')
        {
            node.className = 'hidden';
            img.src = '../../img/expand.gif'
        }
        else
        {
            node.className = 'blockitems';
            img.src = '../../img/collapse.gif'
        }
    }
</script>
