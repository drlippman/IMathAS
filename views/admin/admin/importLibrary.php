<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppConstant;
use app\components\AppUtility;
$this->title = 'Import Library';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false),AppUtility::t('Admin', false),AppUtility::t('Util', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index',AppUtility::getHomeURL() . 'admin/admin/index',AppUtility::getHomeURL() . 'utilities/utilities/admin-utilities']]);?>
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

        }else
        {
            if(isset($params['process']))
            {
                echo $page_uploadSuccessMsg;
            }
            else {?>
                <form enctype="multipart/form-data" method=post action="<?php echo AppUtility::getURLFromHome('admin','admin/import-library?cid='.$courseId)?>">
            <?php }?>
                    <?php
                    if ($_FILES['userfile']['name']=='')
                    {?>
                        <input type="hidden" name="MAX_FILE_SIZE" value="9000000" />
                        <span class=form>Import file: </span>
                        <span class=formright><input name="userfile" type="file" /></span><br class=form>
                        <div class=submit><input type=submit value="Submit"></div>
                    <?php }else{?>
                                    <?php if (strlen($page_fileErrorMsg)>1)
                                    {
                                        echo $page_fileErrorMsg;
                                     }else{?>
                                        <?php echo $page_fileHiddenInput;?>
                                <p>This page will import entire questions libraries with heirarchy structure.
                                    To import specific questions into existing libraries, use the
                                    <a href="import.php?cid=<?php echo $cid ?>">Question Import</a> page
                                </p>
                                <?php echo $packName; ?>
                                <h3>Select Libraries to import</h3>
                                <p>Note:  If a parent library is not selected, NONE of the children libraries will be added,
                                    regardless of whether they're checked or not
                                </p>

                                <p>
                                    Set Question Use Rights to:
                                    <select name=qrights>
                                        <option value="0">Private</option>
                                        <option value="2" SELECTED>Allow use, use as template, no modifications</option>
                                        <option value="3">Allow use and modifications</option>
                                    </select>
                                </p>
                                <p>
                                    Set Library Use Rights to:
                                    <select name="librights">
                                        <option value="0">Private</option>
                                        <option value="1">Closed to group, private to others</option>
                                        <option value="2" SELECTED>Open to group, private to others</option>
                                <?php if($isAdmin || $isGrpAdmin || $allowNonGroupLibs){?>
                                    <option value="4">Closed to all</option>
                                    <option value="5">Open to group, closed to others</option>
                                    <option value="8">Open to all</option>
                                <?php }?>
                                    </select>
                                </p>

                            <p>Parent library:
                                <span id="libnames">Root</span>
                                <input type=hidden name="parent" id="parent"  value="0">
                                <input type=button value="Select Parent" onClick="libselect()">
                            </p>

                            <p>If a library or question already exists on this system, do you want to:<br/>
                                <input type=radio name=merge value="1" CHECKED>Update existing,
                                <input type=radio name=merge value="0">import as new, or
                                <input type=radio name=merge value="-1">Keep existing
                                <?php if ($myRights == 100){echo '<input type=radio name=merge value="2">Force update';}?>
                                <br/>
                                Note that updating existing libraries will not place those imported libraries
                                in the parent selected above.
                            </p>Base
                            <ul class=base>
                                <?php printlist(0,$parent,$names); ?>
                                <p><input type=submit name="process" value="Import Libraries"></p>
                      <?php }?>
                    <?php }?>
                    </form>
      <?php }?>
    </div>
</div>

<?php
function printlist($parent,$parents=null,$names=null)
{
    $children = array_keys($parents,$parent);
    foreach ($children as $child)
    {
        if (!in_array($child,$parents)) { //if no children
            echo "<li><span class=dd>-</span><input type=checkbox name=\"libs[]\" value=\"$child\" CHECKED>{$names[$child]}</li>";
        } else { // if children
            echo "<li class=lihdr><span class=dd>-</span><span class=hdr onClick=\"toggle($child)\"><span class=btn id=\"b$child\">+</span> ";
            echo "</span><input type=checkbox name=\"libs[]\" value=$child CHECKED>";
            echo "<span class=hdr onClick=\"toggle($child)\">{$names[$child]}</span>";
            echo "<ul class=hide id=$child>\n";
            printlist($child);
            echo "</ul></li>\n";
        }
    }
}
?>
<!--<script type="text/javascript">-->
<!--    var curlibs = '0';-->
<!--    function libselect()-->
<!--    {-->
<!--        window.open('../course/libtree.php?libtree=popup&cid=--><?php //echo $cid ?><!--&selectrights=1&select=parent&type=radio&libs='+curlibs,'libtree','width=400,height='+(.7*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));-->
<!--    }-->
<!--    function setlib(libs)-->
<!--    {-->
<!--        document.getElementById("parent").value = libs;-->
<!--        curlibs = libs;-->
<!--    }-->
<!--    function setlibnames(libn)-->
<!--    {-->
<!--        document.getElementById("libnames").innerHTML = libn;-->
<!--    }-->
<!---->
<!--    function toggle(id)-->
<!--    {-->
<!--        node = document.getElementById(id);-->
<!--        button = document.getElementById('b'+id);-->
<!--        if (node.className == "show") {-->
<!--            node.className = "hide";-->
<!--            button.innerHTML = "+";-->
<!--        } else-->
<!--        {-->
<!--            node.className = "show";-->
<!--            button.innerHTML = "-";-->
<!--        }-->
<!--    }-->
<!--</script>-->