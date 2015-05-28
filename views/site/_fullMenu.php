<?php
use app\components\AppUtility;
?>

<div class="floatright" id="homelinkbox">
    <a href="<?php echo AppUtility::getURLFromHome('site', 'change-user-info') ?>">Change User Info</a> |
    <a href="<?php echo AppUtility::getURLFromHome('site', 'change-password') ?>">Change Password</a> |
    <a href="<?php echo AppUtility::getURLFromHome('site', 'work-in-progress?id=') ?>">Messages</a> |
    <a href="<?php echo AppUtility::getURLFromHome('site', 'work-in-progress?id=') ?>">Documentation</a>
</div>
