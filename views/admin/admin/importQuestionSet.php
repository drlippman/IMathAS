<?php
use app\components\AppConstant;
use app\components\AppUtility;

$this->title = AppUtility::t('Import Question Set',false);
$this->params['breadcrumbs'][] = $this->title;
global $parents;
global $names;
$names = $namesData;
$parents = $parentsData;
?>
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
            if (isset($params['process'])) {
                ?>
                <div class="align-success-message">
                    <?php AppUtility::t('Import Successful.'); ?><br>
                    <?php AppUtility::t('New Questions:'); ?> <?php echo $newQ ?>.<br>
                    <?php AppUtility::t('Updated Questions:'); ?><?php echo $updateQ ?>.<br>
                    <?php AppUtility::t('New Library items:'); ?><?php echo $newLi ?>.<br>
                    <?php if ($isAdmin || $isGrpAdmin) { ?>
                        <a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/index'); ?>"><?php AppUtility::t('Return to Admin page') ?></a>
                    <?php } else { ?>
                        <a href="<?php echo AppUtility::getURLFromHome('course', 'course/course?cid=' . $courseId); ?>"><?php AppUtility::t('Return to Course page') ?></a>
                    <?php } ?>
                </div>
            <?php
            } else {
                ?>
                <?php echo $page_fileNoticeMsg; ?>
                <form enctype="multipart/form-data" method=post
                      action="<?php echo AppUtility::getURLFromHome('admin', 'admin/import-question-set?cid=' . $courseId) ?>">
                    <?php
                    if ($_FILES['userfile']['name'] == '') {
                        ?>
                        <input type="hidden" name="MAX_FILE_SIZE" value="9000000"/>
                        <?php AppUtility::t('Import file:'); ?><input name="userfile" type="file"/>
                        <br/>
                        <input type=submit value="<?php AppUtility::t('Submit');?>">
                    <?php } else { ?>
                        <?php if (strlen($page_fileErrorMsg) > AppConstant::NUMERIC_ONE) {
                            echo $page_fileErrorMsg;
                        } else {
                            ?>
                            <?php echo $page_fileHiddenInput; ?>
                            <?php echo $qData['libdesc'];
                            echo $page_existingMsg;?>
                            <div class="col-md-12 col-sm-12 padding-left-zero"><h3><?php AppUtility::t('Select Questions to import'); ?></h3></div>

                            <div class="col-md-12 padding-left-zero">
                                <div class="col-md-2 col-sm-3 padding-left-zero"><?php AppUtility::t('Set Question Use Rights to'); ?></div>
                                <div class="col-md-4 col-sm-4 padding-left-zero"><select name=userights class="form-control-import-question">
                                    <option value="0"><?php AppUtility::t('Private'); ?></option>
                                    <option value="2"
                                            SELECTED><?php AppUtility::t('Allow use, use as template, no modifications'); ?></option>
                                    <option
                                        value="3"><?php AppUtility::t('Allow use by all and modifications by group'); ?></option>
                                    <option
                                        value="4"><?php AppUtility::t('Allow use and modifications by all'); ?></option>
                                </select></div>
                            </div><br class="form"><br/>

                            <div class="col-md-12 col-sm-12 padding-left-zero">
                                <div class="col-sm-2 col-md-2 padding-left-zero"><?php AppUtility::t('Assign to library'); ?></div> <div
                                    id="libnames" class="col-md-1 col-sm-1 padding-left-zero"><?php AppUtility::t('Unassigned'); ?></div>
                                <input type=hidden name="libs" id="libs" value="0">
                                <div class="col-md-1 col-sm-1 padding-left-zero"><input type=button value="<?php AppUtility::t('Select Libraries'); ?>" onClick="libselect()"></div><br>
<!--                                --><?php //AppUtility::t('Check:'); ?><!-- <a href="#" onclick="return chkAllNone('qform','checked[]',true)">--><?php //AppUtility::t('All'); ?><!--</a>-->
<!--                                <a href="#" onclick="return chkAllNone('qform','checked[]',false)">--><?php //AppUtility::t('None'); ?><!--</a>-->
                            </div><br/>

                            <br/><br class="form"><table id="myTable" class="potential-question-table gb" style="clear:both; position:relative;width: 100%">
                                <thead>
                                <tr>
                                    <th class="questionId">
                                        <div class="checkbox override-hidden floatleft">
                                            <label style="padding-left:0px">
                                                <input type="checkbox" name="header-checked"  value="">
                                                <span class="cr">
                                                    <i class="cr-icon fa fa-check"></i>
                                                </span>
                                            </label>
                                        </div>
                                    </th>
                                    <th style="text-align: left"><?php AppUtility::t('Description') ?></th>
                                    <th style="text-align: left"><?php AppUtility::t('Type') ?></th>
                                </tr>
                                </thead>
                                <tbody id="potential-question-information-table">
                                <?php
                                $alt = AppConstant::NUMERIC_ZERO;
                                for ($i = AppConstant::NUMERIC_ZERO; $i < (count($qData) - AppConstant::NUMERIC_ONE); $i++) {
                                    if ($alt == AppConstant::NUMERIC_ZERO) {
                                        echo "<tr class=even>";
                                        $alt = AppConstant::NUMERIC_ONE;
                                    } else {
                                        echo "<tr class=odd>";
                                        $alt = AppConstant::NUMERIC_ZERO;
                                    }
                                    ?>
                                    <tr>
                                        <td>
                                            <input type=checkbox name='checked[]' value='<?php echo $i ?>'>
    <!--                                        checked=checked-->
                                        </td>
                                        <td><?php echo $qData[$i]['description'] ?></td>
                                        <td><?php echo $qData[$i]['qtype'] ?></td>
                                    </tr>
                                <?php
                                }
                                ?>
                                </tbody>
                            </table><BR>
                            <input type=submit name="process" value="<?php AppUtility::t('Import Questions'); ?>"><br/><br class="form">
                        <?php } ?>
                    <?php } ?>
                </form>
            <?php } ?>
        <?php } ?>
    </div>
</div>

<script type="text/javascript">

    var curlibs = '0';
    function libselect() {
        window.open('../course/libtree.php?cid=<?php echo $cid ?>&libtree=popup&selectrights=1&libs=' + curlibs, 'libtree', 'width=400,height=' + (.7 * screen.height) + ',scrollbars=1,resizable=1,status=1,top=20,left=' + (screen.width - 420));
    }
</script>
