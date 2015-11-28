<?php
use app\components\AppConstant;
use \app\components\AppUtility;

$this->title = 'Question Export';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', 'Admin'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'admin/admin/index'], 'page_title' => $this->title]); ?>
</div>
<div class="title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="tab-content shadowBox non-nav-tab-item">
    <?php
    if (!(isset($isTeacher)) && $myRights < AppConstant::GROUP_ADMIN_RIGHT) {
        $body = AppConstant::NO_TEACHER_RIGHTS;
    } elseif (isset($params['cid']) && $params['cid'] == "admin" && $myRights < AppConstant::GROUP_ADMIN_RIGHT) {
        $body = AppConstant::REQUIRED_ADMIN_ACCESS;
    } elseif (!(isset($params['cid'])) && $myRights < AppConstant::GROUP_ADMIN_RIGHT) {
        $body = AppConstant::ACCESS_THROUGH_MENU;
    } else {
    }

    if ($overWriteBody == AppConstant::NUMERIC_ONE) {
        echo $body;
    } else {
        ?>
        <script type="text/javascript">

            var curlibs = '<?php echo $searchlibs = AppConstant::NUMERIC_ZERO ?>';
            function libselect() {
                window.open('../../question/question/library-tree?libtree=popup&cid=<?php echo $cid ?>&libs=' + curlibs, 'libtree', 'width=400,height=' + (.7 * screen.height) + ',scrollbars=1,resizable=1,status=1,top=20,left=' + (screen.width - 420));
            }
            function setlib(libs) {
                document.getElementById("libs").value = libs;
                curlibs = libs;
            }
            function setlibnames(libn) {
                document.getElementById("libnames").innerHTML = libn;
            }
        </script>

    <?php echo $curBreadcrumb; ?>
        <div id="headerexport" class="pagetitle"><h2></h2></div>
        <form id="pform" method=post action="export-question-set?cid=<?php echo $cid ?>">

            <div class="col-md-12 col-sm-12"><h3><?php AppUtility::t('Questions Marked for Export') ?></h3></div>
            <?php
            if (count($checked) == AppConstant::NUMERIC_ZERO) {
                echo "<div class='col-md-12 col-sm-12'>".AppUtility::t('No Questions currently marked for export',false)."</div>\n";
            } else {
                ?>
<!--                --><?php //AppUtility::t('Check') ?>
<!--                <a href="#" onclick="return chkAllNone('pform','pchecked[]',true)">--><?php //AppUtility::t('All') ?><!--</a>-->
<!--                <a href="#" onclick="return chkAllNone('pform','pchecked[]',false)">--><?php //AppUtility::t('None') ?><!--</a>-->

        <div class="item margin-padding-admin-table padding-bottom margin-twenty">
            <div class="margin-twenty margin-left-twenty">
                <table cellpadding="5" id="myTable" class="potential-question-table " style="clear:both; position:relative;width: 100%">
                    <thead>
                    <tr><th class="questionId">
                            <div class="checkbox override-hidden">
                                <label style="padding-left:0px">
                                    <input type="checkbox" name="header-checked"  value="">
                                    <span class="cr">
                                        <i class="cr-icon fa fa-check"></i>
                                    </span>
                                </label>
                            </div>
                        </th>
                        <th><?php AppUtility::t('Description') ?></th>
                        <th><?php AppUtility::t('Type') ?></th>
                    </tr>
                    </thead>
                    <tbody id="potential-question-information-table">
                    <?php
                    $alt = AppConstant::NUMERIC_ZERO;
                    for ($i = AppConstant::NUMERIC_ZERO; $i < count($page_pChecked); $i++) {
                        if ($alt == AppConstant::NUMERIC_ZERO) {
                            echo "			<tr class=even>";
                            $alt = AppConstant::NUMERIC_ONE;
                        } else {
                            echo "			<tr class=odd>";
                            $alt = AppConstant::NUMERIC_ZERO;
                        }
                        ?>
                        <td>
                            <input type=checkbox name='pchecked[]' value='<?php echo $page_pChecked[$i]['id'] ?>'
                                   checked=checked>
                        </td>
                        <td><?php echo $page_pChecked[$i]['description'] ?></td>
                        <td><?php echo $page_pChecked[$i]['qtype'] ?></td>
                        </tr>
                    <?php
                    }
                    ?>
                    </tbody>
                </table>
                </div>
            </div>
            <?php
            }

            if (isset($_POST['finalize'])) {
            ?>
            <div class="col-md-12 col-sm-12"><h3><?php AppUtility::t('Export Settings') ?></h3></div>
            <div class="col-md-2 col-sm-2"><?php AppUtility::t('Library Description') ?></div>
            <div class="col-md-6 col-sm-6">
                <textarea name="libdescription" class="form-control text-area-alignment" rows=4 cols=60></textarea>
            </div>
            <br class=form><br/>

            <div class="col-md-2 col-sm-2"><?php AppUtility::t('Library Name') ?></div>
            <div class="col-md-6 col-sm-6"><input type=text class="form-control" name="libname" size="40"/></div>
            <br class=form><br/>

<!--            <div class=submit><input name="export" type=submit value="Export"></div><br class=form>-->
                <div class="header-btn floatleft padding-bottom-one-em padding-left-fifteen">
                    <button class="btn btn-primary page-settings submit" type="submit" name="export" value="Export"><i class="fa fa-share header-right-btn"></i><?php echo 'Export' ?></button>
                </div>
        </form>
    <?php
    } else {
    ?>

        <div class="col-md-12 col-sm-12"><h3><?php AppUtility::t('Potential Questions') ?></h3></div>
        <div class="col-md-12 col-sm-12"><?php AppUtility::t('In Libraries') ?><span id="libnames"
                                                                           class="padding-left-fifteen"><?php echo $lnames ?></span>
            <input type=hidden name="libs" id="libs" value="<?php echo $searchlibs ?>">
            <input type=button value="Select Libraries" onClick="libselect()"></div><br class=form><br class=form>
        <div class="col-md-12 col-sm-12 padding-bottom-one-em"><?php AppUtility::t('Search') ?>
            <span class="padding-left-fifteen">
                <input type=text size=15 class="form-control-1" name=search value="<?php echo $search ?>"></span>
            <input type=submit value="Update and Search">
            <input type=submit name="finalize" value="Finalize"></div>
    <?php
    if ($page_hasSearchResults == AppConstant::NUMERIC_ZERO) {
        echo "<div class='col-md-12 col-sm-12'>".AppUtility::t('No Questions matched search',false)."</div>\n";
    } else {
    ?>
<!--    --><?php //AppUtility::t('Check') ?>
<!--        <a href="#" onclick="return chkAllNone('pform','nchecked[]',true)">--><?php //AppUtility::t('All') ?><!--</a>-->
<!--        <a href="#" onclick="return chkAllNone('pform','nchecked[]',false)">--><?php //AppUtility::t('None') ?><!--</a>-->
        <div class="item margin-padding-admin-table padding-bottom margin-twenty">
            <div class="margin-twenty margin-left-twenty">
        <table cellpadding="5" id="myTable" class="potential-question-table" style="clear:both; position:relative;width: 100%; m">
            <thead>
            <tr><th class="questionId">
                    <div class="checkbox override-hidden">
                        <label style="padding-left:0px">
                            <input type="checkbox" name="header-checked"  value="">
                            <span class="cr"><i class="cr-icon fa fa-check"></i></span>
                        </label>
                    </div>
                </th>
                <th><?php AppUtility::t('Description') ?></th>
                <th><?php AppUtility::t('Type') ?></th>
            </tr>
            </thead>
            <tbody id="potential-question-information-table">
            <?php
            $alt = AppConstant::NUMERIC_ZERO;
            for ($i = AppConstant::NUMERIC_ZERO; $i < count($page_nChecked); $i++) {
                if ($alt == AppConstant::NUMERIC_ZERO) {
                    echo "			<tr class=even>";
                    $alt = AppConstant::NUMERIC_ONE;
                } else {
                    echo "			<tr class=odd>";
                    $alt = AppConstant::NUMERIC_ZERO;
                }
                ?>
                <td>
                    <input type=checkbox name='nchecked[]' value='<?php echo $page_nChecked[$i]['id'] ?>'>
                </td>
                <td><?php echo $page_nChecked[$i]['description'] ?></td>
                <td><?php echo $page_nChecked[$i]['qtype'] ?></td>
                </tr>
            <?php
            }
            ?>
            </tbody>
        </table></div></div>
    <?php
    }
        echo "<div class='col-md-12 col-sm-12 padding-left-fifteen padding-bottom-one-em'>";
                AppUtility::t(' Note: Export of questions with static image files is not yet supported.');
                echo"<div>";
    }
        echo "</form>";
    }
    ?>
</div>