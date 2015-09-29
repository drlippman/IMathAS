<?php
use app\components\AppUtility;
$pageTitle = $testsettings['name'];
?>

<?php
if (isset($sessiondata['actas'])) {
    echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $sessiondata['coursename']], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $testsettings['courseid']]]);
} else {
   echo '<span style="float:right;">'.$userfullname.'</span>';
    if (isset($sessiondata['ltiitemtype']) && $sessiondata['ltiitemtype'] == 0) {
    } else {
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $sessiondata['coursename']], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $testsettings['courseid']]]);
    }


}

?>

<div class="tab-content shadowBox non-nav-tab-item">
    <?php echo $displayQuestions; ?>
</div>