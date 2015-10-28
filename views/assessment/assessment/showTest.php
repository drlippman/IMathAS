<?php
use app\components\AppUtility;
$this->title = $testsettings['name'];
?>

<?php
if ($pwfail) {
    if (!$isdiag && strpos($_SERVER['HTTP_REFERER'],'treereader')===false && !(isset($sessiondata['ltiitemtype']) && $sessiondata['ltiitemtype']==0)) {
        $temp .= "<div class=breadcrumb>$breadcrumbbase <a href=\"../../course/course/course?cid={$_GET['cid']}\">{$sessiondata['coursename']}</a> ";
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $sessiondata['coursename']], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course', 'course/course?stu='.$studentid.'cid=' . $testsettings['courseid']]]);
    }
}
if (isset($sessiondata['actas'])) {
    echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $sessiondata['coursename'], AppUtility::t('Gradebook Detail',false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course', 'course/course?stu='.$studentid.'cid=' . $testsettings['courseid'], AppUtility::getHomeURL() . 'gradebook/gradebook/gradebook-view-assessment-details?cid=' . $testsettings['courseid'].'&asid='.$testid.'&uid='.$sessiondata['actas']]]);
} else {
   echo '<span class="color-white floatright">'.$userfullname.'</span>';
    if (isset($sessiondata['ltiitemtype']) && $sessiondata['ltiitemtype'] == 0) {
    } else {
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $sessiondata['coursename']], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $testsettings['courseid']]]);
    }


}

?>
<div class="title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="tab-content shadowBox non-nav-tab-item padding-thirty">
    <?php echo $displayQuestions; ?>
</div>