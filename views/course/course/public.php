<?php
use app\components\AppUtility;
use \app\components\ShowItemCourse;

$this->title = $pageTitle;
$this->params['breadcrumbs'][] = $this->title;
?>

    <div class="item-detail-header">
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home'], 'link_url' => [AppUtility::getHomeURL() . 'site/index'], 'page_title' => $this->title]); ?>
    </div>

    <div class ="title-container">
        <div class="row">
            <div class="pull-left page-heading">
                <div class="vertical-align title-page"><?php echo $this->title ?></div>
            </div>
        </div>
    </div>
<div class="tab-content shadowBox non-nav-tab-item col-md-12 col-sm-12">
<?php
$blockAddress = AppUtility::getURLFromHome('course', 'course/get-block-items-public?cid='.$cid. '&folder=');?>
<script>
    var getbiaddr = '<?php echo $blockAddress;?>';
    var oblist = '<?php echo $oblist ?>';
    var plblist = '<?php echo $plblist ?>';
    var cid = '<?php echo $cid ?>';
</script>


<?php
echo "<h2>$curname</h2>\n";
if (count($items)>0) {
    $courseItem = new ShowItemCourse();
    $courseItem->showitems($items, $params['folder'], $blockIsPublic);
}

echo "<hr/>This is the publicly accessible content from a course on OpenMath.  There may be additional content available by <a href=\"course?cid=$cid\">logging in</a>";
 ?>
    </div>