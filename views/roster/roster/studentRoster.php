
<?php
use app\components\AppUtility;
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = 'Roster';
$this->params['breadcrumbs'][] = $this->title;

?>
<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
<div class="item-detail-header">
    <?php echo $this->render("header/_index",['item_name' => 'help', 'link_title'=>['Home',$course->name], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'instructor/instructor/index?cid='.$course->id], 'page_title' => $this->title]); ?>
</div>
<div class="item-detail-content">
<ul class="nav nav-tabs nav-justified">
    <li class="master-tabs active"><a href="#">Course</a></li>
    <li class="master-tabs"><a href="#">Gradebook</a></li>
    <li class="master-tabs"><a href="#">Calendar</a></li>
    <li class="master-tabs"><a href="#">Roster</a></li>
</ul>
</div>