<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppConstant;
use app\components\AppUtility;
$this->title = 'Forums';

$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => [AppUtility::getRefererUri(Yii::$app->session->get('referrer'))]];
$this->params['breadcrumbs'][] = $this->title;
?>
<!-- DataTables CSS -->
<link rel="stylesheet" type="text/css" href="<?php echo AppUtility::getHomeURL() ?>js/DataTables-1.10.6/media/css/jquery.dataTables.css">
<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
<script type="text/javascript" charset="utf8" src="<?php echo AppUtility::getHomeURL() ?>js/DataTables-1.10.6/media/js/jquery.dataTables.js"></script>
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
    <?= $form->field($model, 'thread')->inline()->radioList(['subject' => 'All thread subjects' , 'post' => 'All Post']) ?>
    <div class="form-group">
        <div class="col-lg-offset-1 col-lg-11">
            <input type="button" id="forum_search" class="btn btn-primary" value="Search"/>
        </div>
    </div>
    <input type="hidden" id="courseId" class="courseId" value="<?php echo $cid ?>">
    <br>
    <?php if(!empty($forum)){?>
   <div id="display">
    <table id="forum-table displayforum" class="forum-table">
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
    <?php } else if($users->rights== 20){
            echo "<p>There are no active forums at this time,you can add new using course page.</p>";

            }
            else {
                      echo "<p>There are no active forums at this time.</p>";
             }?>
    <?php ActiveForm::end(); ?>
</div>

<div id="searchthread">
    <table id="forumsearch-table displayforum" class="forumsearch-table">
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



