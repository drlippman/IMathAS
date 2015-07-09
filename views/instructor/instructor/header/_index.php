<div class="index-header">
    <div class="left-side left-float">
        <div class="small-link">
            <a href="<?php echo isset($link_url) ?  $link_url : ""; ?>"><?php echo isset($link_title) ?  $link_title : ""; ?> </a>>>
        </div>
        <div class="big-title">
            <?php echo isset($page_title) ?  $page_title : ""; ?>
        </div>
    </div>
    <div class="right-side right-float" style="background-color: #104e8c; height: 50px; width: 150px">
        <div class="right-items left-float" >
            <div class="item-icon">
                <span>

                </span>
            </div>
            <div class="item-name left-float" style="margin-top: 15px; margin-left: 5px">
                <?php echo isset($item_name) ?  $item_name : ""; ?>
            </div>
            <div class="clear-both"></div>
        </div>
    </div>
    <div class="clear-both"></div>
</div>


<?php
//use yii\widgets\Breadcrumbs;
//
//echo Breadcrumbs::widget(['links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : []]);