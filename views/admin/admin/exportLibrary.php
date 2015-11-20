<?php
use app\components\AppConstant;
use app\components\AppUtility;

$this->title = 'Export Library';
$this->params['breadcrumbs'][] = $this->title;
?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js" type="text/javascript"></script>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), AppUtility::t('Admin', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'admin/admin/index']]); ?>
</div>
<div class="title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="item-detail-content"></div>
<div class="tab-content shadowBox">
    <br>
    <div class="align-copy-course">
        <?php
        if ($overwriteBody == AppConstant::NUMERIC_ONE) {
            echo $body;
        } else {
            ?>
            <form method=post
                  action="<?php echo AppUtility::getURLFromHome('admin', 'admin/export-lib?cid=' . $courseId) ?>">

                <div id="headerexportlib" class="pagetitle"><h2><?php AppUtility::t('Library Export'); ?></h2></div>
                <div class="col-md-12 col-sm-12"><?php AppUtility::t("Note:  If a parent library is selected, it's children libraries are included in the export,
                and heirarchy will be maintained.  If libraries from different trees are selected, the topmost
                libraries in each branch selected will be exported at the same level."); ?></div>
                <?php
                global $select, $params, $nonPrivate;
                $select = "all";
                include("libTree.php");
                ?>
                <span class=""><?php AppUtility::t('Limit to non-private questions and libs?'); ?></span>
		<span class="formright">
			<input type="checkbox" name="nonpriv" checked="checked"/>
		</span><br class="form"/>
                <span class=form><?php AppUtility::t('Package Description'); ?></span>
        <div class="col-md-5 col-sm-5">
			<textarea name="packdescription" class="form-control text-area-alignment" rows=4 cols=60></textarea>
		</div><br class=form>
<!--                <div class="header-btn floatleft">-->
<!--                    <button class="btn btn-primary page-settings" type="submit" value="Submit"><i class="fa fa-share header-right-btn"></i>--><?php //echo 'Export' ?><!--</button>-->
<!--                </div>-->
                <div class="header-btn floatleft"><input type=submit name="submit" value="Export"></div><br/><br class="form">
                <?php AppUtility::t('Once exported,'); ?> <a
                    href="<?php echo AppUtility::getAssetURL() ?>Uploads/Qimages.tar.gz"><?php AppUtility::t('download image files'); ?></a> <?php AppUtility::t('to be put in assessment/qimages'); ?><br/><br class="form">
            </form>
        <?php } ?>
    </div>
</div>