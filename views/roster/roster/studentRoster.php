
<?php
use app\components\AppUtility;
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = 'Roster';
$this->params['breadcrumbs'][] = $this->title;

?>

<div class="item-detail-header">
    <?php echo $this->render("header/_index",['item_name' => 'help', 'link_title'=>['Home',$course->name], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'instructor/instructor/index?cid='.$course->id], 'page_title' => $this->title]); ?>
</div>


<div class="item-detail-content">
    <?php echo $this->render("../../instructor/instructor/_toolbarTeacher", ['course' => $course, 'section' => 'roster']);?>
</div>

<div class="tab-content shadowBox"">
<div style="background: forestgreen;height: 100px;width: 100%">


</div>
<br><br><h1>student roster</h1><br><br>
</div>