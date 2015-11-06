<?php
use app\components\AppUtility;
use \app\components\ShowItemCourse;

$blockAddress = AppUtility::getURLFromHome('course', 'course/get-block-items-public?cid='.$course->id. '&folder=');?>
<script>
    var getbiaddr = '<?php echo $blockAddress;?>';
    var oblist = '<?php echo $oblist ?>';
    var plblist = '<?php echo $plblist ?>';
    var cid = '<?php echo $cid ?>';
</script>
<div class=breadcrumb>
    <?php echo $curBreadcrumb ?>
    <div class=clear></div>
</div>

<?php
echo "<h2>$curname</h2>\n";
if (count($items)>0) {
    $courseItem = new ShowItemCourse();
    $courseItem->showitems($items, $_GET['folder'], $blockIsPublic);
}

echo "<hr/>This is the publicly accessible content from a course on OpenMath.  There may be additional content available by <a href=\"course?cid=$cid\">logging in</a>";
 ?>