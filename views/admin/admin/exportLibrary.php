<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppConstant;
use app\components\AppUtility;
$this->title = 'Export Library';
$this->params['breadcrumbs'][] = $this->title;
?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js" type="text/javascript"></script>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false),AppUtility::t('Admin', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index',AppUtility::getHomeURL() . 'admin/admin/index']]);?>
</div>
<div class = "title-container">
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
        if($overwriteBody == AppConstant::NUMERIC_ONE)
        {
            echo $body;

        }else{?>

        <form method=post action="<?php echo AppUtility::getURLFromHome('admin','admin/export-lib?cid='.$courseId)?>">

            <div id="headerexportlib" class="pagetitle"><h2>Library Export</h2></div>
            <p>Note:  If a parent library is selected, it's children libraries are included in the export,
                and heirarchy will be maintained.  If libraries from different trees are selected, the topmost
                libraries in each branch selected will be exported at the same level.</p>
            <?php
            global $select,$params,$nonPrivate;
            $select = "all";
            include("libTree.php");
            ?>
            <span class="">Limit to non-private questions and libs?</span>
		<span class="formright">
			<input type="checkbox" name="nonpriv" checked="checked" />
		</span><br class="form" />
            <span class=form>Package Description</span>
		<span class=formright>
			<textarea name="packdescription" rows=4 cols=60></textarea>
		</span><br class=form>
            <input type=submit name="submit" value="Export"><br/>
            Once exported, <a href="<?php echo AppUtility::getAssetURL()?>Uploads/Qimages.tar.gz">download image files</a> to be put in assessment/qimages
        </form>

        <?php }?>

    </div>
</div>