<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppConstant;
use app\components\AppUtility;
$this->title = 'Forums';
if ($users->rights > AppConstant::STUDENT_RIGHT){

    $this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid=' . $course->id]];
}
else{
    $this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/course/course/index?cid=' . $course->id]];
}
//$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => [AppUtility::getRefererUri(Yii::$app->session->get('referrer'))]];
$this->params['breadcrumbs'][] = $this->title;
?>
<!-- DataTables CSS -->
<div class="site-login">

    <?php $form = ActiveForm::begin([
        'id' => 'login-form',
        'options' => ['class' => 'form-horizontal'],
        'fieldConfig' => [
            'template' => "{label}\n<div class=\"col-lg-3\">{input}</div>\n<div class=\"col-lg-5\">{error}</div>",
            'labelOptions' => ['class' => 'col-lg-1 control-label'],
        ],
    ]); ?>

<div>
    <?= $form->field($model, 'search')->textInput(['id' => 'search_text']); ?>

</div>
    <?= $form->field($model, 'thread')->inline()->radioList(['subject' => 'All thread subjects' , 'post' => 'All Posts']) ?>
    <div class="form-group">
        <div class="col-lg-offset-1 col-lg-11">
            <input type="button" id="forum_search" class="btn btn-primary" value="Search"/>
        </div>
    </div>
    <input type="hidden" id="courseId" class="courseId" value="<?php echo $cid ?>">
    <br>
    <?php if(!empty($forum)){?>
   <div id="display">
    <table id="forum-table displayforum" class="forum-table table table-bordered table-striped table-hover data-table">
        <thead>
        <tr>
            <th>Forum Name</th>
            <th>Threads</th>
            <th>Posts</th>
            <th>Last Post Date</th>
        </tr>
        </thead>
        <tbody class="forum-table-body">
        </tbody>
    </table>
     </div>
    <?php } else if($users->rights== AppConstant::TEACHER_RIGHT){
            echo "<p>There are no active forums at this time,you can add new using course page.</p>";

            }
            else {
                      echo "<p>There are no active forums at this time.</p>";
             }?>
    <?php ActiveForm::end(); ?>
</div>

<div id="searchthread">
    <table id="forumsearch-table displayforum" class="forumsearch-table table table-bordered table-striped table-hover data-table">
        <thead>

        <th>Topic</th>
        <th>Replies</th>
        <th>Views</th>
        <th>Last Post Date</th>


        </thead>
        <tbody class="forumsearch-table-body">
        </tbody>
    </table>

</div>
<div id="searchpost"></div>
<div id="result">
    <h5><Strong>No result found for your search.</Strong></h5>
</div>



