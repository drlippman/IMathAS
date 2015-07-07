<?php
use app\components\AppUtility;
$this->title = 'Gradebook Comments';
$this->params['breadcrumbs'][] = ['label' => ucfirst($course->name), 'url' => ['/instructor/instructor/index?cid='.$course->id]];
$this->params['breadcrumbs'][] = ['label' => 'Gradebook', 'url' => ['/gradebook/gradebook/gradebook?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;

?>
<div><pre><a href="#">View/Edit Instructor notes</a></pre></div>
<h2>Modify Gradebook Comments</h2>
<p>These comments will display at the top of the student's gradebook score list.</p>
<p><a href="#">Upload comments</a></p>
<form id="mainform" method="post" action="#">
    <span class="form">Add/Replace to all</span>
    <span class="formright">
        <textarea cols="50" rows="3" id="feedback_txt" class="form-control"></textarea><br>
        <input type="hidden" id="isComment" name="isComment" value="1"/>
        <input type="hidden" id="courseId" value="<?php echo $course->id ?>"/>
        <input type="button"  value="Append" class="btn btn-primary" onclick="appendPrependReplaceText(1)" />
            <input type="button" value="Prepend" class="btn btn-primary" onclick="appendPrependReplaceText(3)"/>
            <input type="button" value="Replace" class="btn btn-primary" onclick="appendPrependReplaceText(2)"/>
    </span>
    <?php foreach($studentsInfo as $student){?>
        <br class="form">
        <span class="form"><?php echo ucfirst($student['LastName']." ".ucfirst($student['FirstName']));?></span>
        <span class="formright">
        <textarea class="form-control feedback-text-id" cols="50" rows="3" name="<?php echo $student['id'];?>"><?php echo trim($student['gbcomment']);?></textarea>
        </span>
    <?php
    }
    ?>
    <br class="form">
    <div class="submit">
        <input type="submit" class="btn btn-primary" id="gbComments" value="Save Comments">
    </div>
</form>