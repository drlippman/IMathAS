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
<!--    <div class="right-side right-float right-side-button">-->
<!--        <div class="right-items left-float" >-->
<!--            <div class="item-icon">-->
<!--                <span>-->
<!---->
<!--                </span>-->
<!--            </div>-->
<!--            <div class="item-name left-float" style="padding-top: 15px; padding-left: 5px">-->
<!--                --><?php //echo isset($item_name) ?  $item_name : ""; ?>
<!--            </div>-->
<!--            <div class="clear-both"></div>-->
<!--        </div>-->
<!--    </div>-->
    <div class="clear-both"></div>
</div>

