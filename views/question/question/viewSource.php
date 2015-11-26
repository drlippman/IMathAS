<?php
use app\components\AppUtility;
use app\components\AppConstant;
$this->title = AppUtility::t('Question Source', false);
if (isset($params['aid'])) {
    $title = AppUtility::t('Add/Remove Questions', false);
    $url = AppUtility::getHomeURL().'question/question/add-questions?aid='.$params['aid'].'&cid='.$params['cid'];
} else {
    if ($params['cid']=="admin") {
        $title = AppUtility::t('Admin', false);
        $url = AppUtility::getHomeURL().'question/question/manage-question-set?cid=admin';
        $isAdmin = true;
    } else {
        $title = AppUtility::t('Manage Question Set', false);
        $url = AppUtility::getHomeURL().'question/question/manage-question-set?cid='.$_GET['cid'];
    }
}
?>
    <div class="item-detail-header">
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name, $title ], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid='. $params['cid'], $url] ]); ?>
    </div>

<div class="title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>

<div class="col-md-12 col-sm-12 tab-content shadowBox padding-top-fifteen padding-bottom-twenty-five margin-top-ten">
    <div class="col-md-12 col-sm-12">
        <h4><?php AppUtility::t('Description');?></h4>
        <pre> <?php if($qSetData['description']) {echo $qSetData['description'];}else{ echo "No data available"; } ?> </pre>
    </div>
    <div class="col-md-12 col-sm-12">
        <h4><?php AppUtility::t('Author')?></h4>
        <pre><?php if($qSetData['author']) {echo $qSetData['author'];}else{echo AppConstant::NO_DATA;} ?></pre>
    </div>
    <div class="col-md-12 col-sm-12">
        <h4><?php AppUtility::t('Question Type')?></h4>
        <pre><?php if($qSetData['qtype']) {echo $qSetData['qtype'];}else{echo AppConstant::NO_DATA;} ?></pre>
    </div>
    <div class="col-md-12 col-sm-12">
        <h4><?php AppUtility::t('Common Control')?></h4>
        <pre><?php if($qSetData['control']) {echo $qSetData['control'];}else{echo AppConstant::NO_DATA;} ?></pre>
    </div>
    <div class="col-md-12 col-sm-12">
        <h4><?php AppUtility::t('Question Control')?></h4>
        <pre><?php if($qSetData['qcontrol']) {echo $qSetData['qcontrol'];}else{ echo AppConstant::NO_DATA ; } ?> </pre>
    </div>
    <div class="col-md-12 col-sm-12">
        <h4><?php AppUtility::t('Question Text')?></h4>
        <pre><?php if($qSetData['qtext']) {echo $qSetData['qtext'];}else{echo AppConstant::NO_DATA ;} ?></pre>
    </div>
    <div class="col-md-12 col-sm-12">
        <h4><?php AppUtility::t('Answer')?>/h4>
        <pre><?php if($qSetData['answer']) {echo $qSetData['answer'];}else{echo AppConstant::NO_DATA ;} ?></pre>
    </div>
    <div class="col-md-12 col-sm-12">
    <?php
    if (!isset($params['aid'])) { ?>
        <a href="manage-question-set?cid= <?php echo $params['cid'] ?> "><?php AppUtility::t('Return to Question Set Management')?></a>
    <?php } else { ?>
        <a href="<?php echo AppUtility::getURLFromHome('question', 'question/add-questions?cid='.$params['cid'].'&aid='. $params['aid']) ?>"><?php AppUtility::t('Return to Assessment')?></a>
    <?php }?>
    </div>
</div>