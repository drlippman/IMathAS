<?php
use app\components\AppUtility;
?>
<div class="index-header">
    <div class="left-side left-float">
        <div class="small-link">
            <?php for($i = 0; $i < count($link_title); $i++){ ?>
                <a href="<?php echo isset($link_url[$i]) ?  $link_url[$i] : ""; ?>"><?php echo isset($link_title[$i]) ?  $link_title[$i] : ""; ?> </a>>>
            <?php } ?>
        </div>
        <div class="big-title">
            <?php echo isset($page_title) ?  $page_title : ""; ?>
        </div>
    </div>
    <div class="add-help left-float" style="">
        <a href="<?php echo AppUtility::getHomeURL() ?>docs/help.php?section=calendar"> <i class="fa fa-question fa-align-center help-icon"></i> </a>
    </div>

    <div class="clear-both"></div>
</div>

