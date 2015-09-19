<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppConstant;
use app\components\AppUtility;
$this->title = 'Import Question Set';
$this->params['breadcrumbs'][] = $this->title;
global $parents;
global $names;
$names = $namesData;
$parents = $parentsData;
?>
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

        }else
        {
            if(isset($params['process']))
            {?>
                <div class="align-success-message">
                    Import Successful.<br>
                    New Questions: <?php echo $newQ ?>.<br>
                    Updated Questions: <?php echo $updateQ ?>.<br>
                    New Library items: <?php echo $newLi ?>.<br>
                    <?php if($isAdmin || $isGrpAdmin ){?>
                        <a href="<?php echo AppUtility::getURLFromHome('admin','admin/index');?>"><?php AppUtility::t('Return to Admin page')?></a>
                    <?php }else{?>
                        <a href="<?php echo AppUtility::getURLFromHome('instructor','instructor/index?cid='.$courseId);?>"><?php AppUtility::t('Return to Course page')?></a>
                    <?php }?>
                </div>
            <?php }
            else {?>
                <?php echo  $page_fileNoticeMsg;?>
                <form enctype="multipart/form-data" method=post action="<?php echo AppUtility::getURLFromHome('admin','admin/import-question-set?cid='.$courseId)?>">
                    <?php
                    if ($_FILES['userfile']['name']=='')
                    {?>
                        <input type="hidden" name="MAX_FILE_SIZE" value="9000000" />
                        Import file:<input name="userfile" type="file" />
                        <br/>
                        <input type=submit value="Submit">
                    <?php }else{?>
                        <?php if (strlen($page_fileErrorMsg)>1)
                        {
                            echo $page_fileErrorMsg;
                        }else{?>
                            <?php echo $page_fileHiddenInput;?>
                            <?php echo $qData['libdesc'];
                                   echo $page_existingMsg;?>
                    <h3>Select Questions to import</h3>

                    <p>
                        Set Question Use Rights to <select name=userights class="form-control-import-question">
                            <option value="0">Private</option>
                            <option value="2" SELECTED>Allow use, use as template, no modifications</option>
                            <option value="3">Allow use by all and modifications by group</option>
                            <option value="4">Allow use and modifications by all</option>
                        </select>
                    </p>

                    <p>
                        Assign to library: <span id="libnames">Unassigned</span>
                        <input type=hidden name="libs" id="libs"  value="0">
                        <input type=button value="Select Libraries" onClick="libselect()"><br>
                        Check: <a href="#" onclick="return chkAllNone('qform','checked[]',true)">All</a> <a href="#" onclick="return chkAllNone('qform','checked[]',false)">None</a>

                    </p>

                    <table cellpadding=5 class=gb>
                        <thead>
                        <tr><th></th><th>Description</th><th>Type</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            $alt=0;
                            for ($i = 0 ; $i<(count($qData)-1); $i++) {
                                if ($alt==0) {echo "						<tr class=even>"; $alt=1;} else {echo "						<tr class=odd>"; $alt=0;}
                                ?>
                                <td>
                                    <input type=checkbox name='checked[]' value='<?php echo $i ?>' checked=checked>
                                </td>
                                <td><?php echo $qData[$i]['description'] ?></td>
                                <td><?php echo $qData[$i]['qtype'] ?></td>
                                </tr>
                            <?php
                            }
                            ?>
                        </tbody>
                    </table><BR>
                            <input type=submit name="process" value="Import Questions">
                        <?php }?>
                    <?php }?>
                </form>
            <?php }?>
        <?php }?>
    </div>
</div>


<script type="text/javascript">

    var curlibs = '0';
    function libselect() {
        window.open('../course/libtree.php?cid=<?php echo $cid ?>&libtree=popup&selectrights=1&libs='+curlibs,'libtree','width=400,height='+(.7*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));
    }
    function setlib(libs) {
        if (libs.charAt(0)=='0' && libs.indexOf(',')>-1) {
            libs = libs.substring(2);
        }
        document.getElementById("libs").value = libs;
        curlibs = libs;
    }
    function setlibnames(libn) {
        if (libn.indexOf('Unassigned')>-1 && libn.indexOf(',')>-1) {
            libn = libn.substring(11);
        }
        document.getElementById("libnames").innerHTML = libn;
    }
</script>
