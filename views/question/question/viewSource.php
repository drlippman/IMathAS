<?php
use app\components\AppUtility;
$this->title = AppUtility::t('Add Question', false);
$this->params['breadcrumbs'][] = $this->title;

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


<div id="headerviewsource" class="pagetitle"><h2>Question Source</h2></div>
<div class="col-md-12 tab-content shadowBox padding-top-fifteen padding-bottom-twenty-five margin-top-ten">
    <div class="col-md-12">
        <h4>Description</h4>
        <pre> <?php if($qSetData['description']) {echo $qSetData['description'];}else{ echo "No data available"; } ?> </pre>
    </div>
    <div class="col-md-12">
        <h4>Author</h4>
        <pre><?php if($qSetData['author']) {
                echo $qSetData['author'];
            }else{
                echo "No data available";
            } ?></pre>
    </div>
    <div class="col-md-12">
        <h4>Question Type</h4>
        <pre><?php if($qSetData['qtype']) {echo $qSetData['qtype'];
            }else{
                echo "No data available";
            } ?></pre>
    </div>
    <div class="col-md-12">
        <h4>Common Control</h4>
        <pre><?php if($qSetData['control']) {echo $qSetData['control'];
            }else{
                echo "No data available";
            } ?></pre>
    </div>
    <div class="col-md-12">
        <h4>Question Control</h4>
        <pre><?php if($qSetData['qcontrol']) {echo $qSetData['qcontrol'];}else{ echo "No data available"; } ?> </pre>
    </div>
    <div class="col-md-12">
        <h4>Question Text</h4>
        <pre><?php if($qSetData['qtext']) {echo $qSetData['qtext'];}else{echo "No data available";} ?></pre>
    </div>
    <div class="col-md-12">
        <h4>Answer</h4>
        <pre><?php if($qSetData['answer']) {echo $qSetData['answer'];}else{echo "No data available";} ?></pre>
    </div>
    <div class="col-md-12">
    <?php
    if (!isset($params['aid'])) { ?>
        <a href="manage-question-set?cid= <?php echo $params['cid'] ?> ">Return to Question Set Management</a>
    <?php } else { ?>
        <a href="<?php echo AppUtility::getURLFromHome('question', 'question/add-questions?cid='.$params['cid'].'&aid='. $params['aid']) ?>">Return to Assessment</a>
    <?php }?>
    </div>
</div>