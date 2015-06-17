<?php
use yii\helpers\Html;
use app\components\AppUtility;

$this->title = $course->name;
$this->params['breadcrumbs'][] = $this->title;
?>
<!--<div class="mainbody">-->

     <div>
        <?php echo $this->render('_toolbarTeacher'); ?>
    </div>


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
        <p><strong>Welcome to your course!</strong></p>

        <p> To start by copying from another course, use the <a href="#">Course Items: Copy link</a> along the left side
            of the screen. </p>

        <p> If you want to build from scratch, use the "Add An Item" pulldown below to get started. </p>

        <div class="col-lg-3 pull-left padding-zero">
            <select name="seluid" class="dropdown form-control" id="seluid">
                <option value>Add an item...</option>
                <option value="assessment">Add Assessment</option>
                <option value="inlinetext">Add Inline Text</option>
                <option value="linkedtext">Add Link</option>
                <option value="forum">Add Forum</option>
                <option value="wiki">Add Wiki</option>
                <option value="block">Add Block</option>
                <option value="calendar">Add Calendar</option>
            </select>
        </div>
    </div>
  </div>