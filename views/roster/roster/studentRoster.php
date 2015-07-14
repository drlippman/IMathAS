
<?php
use app\components\AppUtility;
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = 'Roster';
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="item-detail-header">
    <?php echo $this->render("header/_index",['item_name'=>'Course Setting', 'link_title'=>['Home',$course->name], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'site/index'], 'page_title' => $this->title]); ?>
</div>