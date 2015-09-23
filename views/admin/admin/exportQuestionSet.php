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
        $body = "You need to log in as a teacher to access this page";
    } elseif (isset($params['cid']) && $params['cid'] == "admin" && $myRights < AppConstant::GROUP_ADMIN_RIGHT) {
        $body = "You need to log in as an admin to access this page";
    } elseif (!(isset($params['cid'])) && $myRights < AppConstant::GROUP_ADMIN_RIGHT) {
        $body = "Please access this page from the menu links only.";
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
            <br/>

            <div class="col-lg-12"><h3><?php AppUtility::t('Questions Marked for Export') ?></h3></div>
            <?php
            if (count($checked) == AppConstant::NUMERIC_ZERO) {
                echo "<div class='col-lg-12'>".AppUtility::t('No Questions currently marked for export',false)."</div>\n";
            } else {
                ?>
                <?php AppUtility::t('Check') ?>
                <a href="#" onclick="return chkAllNone('pform','pchecked[]',true)"><?php AppUtility::t('All') ?></a>
                <a href="#" onclick="return chkAllNone('pform','pchecked[]',false)"><?php AppUtility::t('None') ?></a>


                <table cellpadding=5 class=gb>
                    <thead>
                    <tr>
                        <th></th>
                        <th><?php AppUtility::t('Description') ?></th>
                        <th><?php AppUtility::t('Type') ?></th>
                    </tr>
                    </thead>
                    <tbody>
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

            <?php
            }

            if (isset($_POST['finalize'])) {
            ?>
            <div class="col-lg-12"><h3><?php AppUtility::t('Export Settings') ?></h3></div>
            <div class=col-lg-2><?php AppUtility::t('Library Description') ?></div>
            <div class=col-lg-6>
                <textarea name="libdescription" rows=4 cols=60></textarea>
            </div>
            <br class=form><br/>

            <div class=col-lg-2><?php AppUtility::t('Library Name') ?></div>
            <div class=col-lg-6><input type=text name="libname" size="40"/></div>
            <br class=form>

            <div class=submit><input name="export" type=submit value="Export"></div>
        </form>
    <?php
    } else {
    ?>

        <div class="col-lg-12"><h3><?php AppUtility::t('Potential Questions') ?></h3></div>
        <div class="col-lg-12"><?php AppUtility::t('In Libraries') ?><span id="libnames"
                                                                           class="padding-left-fifteen"><?php echo $lnames ?></span>
            <input type=hidden name="libs" id="libs" value="<?php echo $searchlibs ?>">
            <input type=button value="Select Libraries" onClick="libselect()"></div><br class=form><br class=form>
        <div class="col-lg-12"><?php AppUtility::t('Search') ?>
            <span class="padding-left-fifteen">
                <input type=text size=15 class="form-control-1" name=search value="<?php echo $search ?>"></span>
            <input type=submit value="Update and Search">
            <input type=submit name="finalize" value="Finalize"><BR></div>
    <?php
    if ($page_hasSearchResults == AppConstant::NUMERIC_ZERO) {
        echo "<div class='col-lg-12'>".AppUtility::t('No Questions matched search',false)."</div>\n";
    } else {
    ?>
    <?php AppUtility::t('Check') ?>
        <a href="#" onclick="return chkAllNone('pform','nchecked[]',true)"><?php AppUtility::t('All') ?></a>
        <a href="#" onclick="return chkAllNone('pform','nchecked[]',false)"><?php AppUtility::t('None') ?></a>

        <table cellpadding=5 id=myTable class=gb>
            <thead>
            <tr>
                <th></th>
                <th><?php AppUtility::t('Description') ?></th>
                <th><?php AppUtility::t('Type') ?></th>
            </tr>
            </thead>
            <tbody>
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
        </table>
        <script type="text/javascript">
            initSortTable('myTable', Array(false, 'S', 'S'), true);
        </script>
    <?php
    }
        echo "<div class='col-lg-12'>".AppUtility::t(' Note: Export of questions with static image files is not yet supported.')."</div>";
    }
        echo "</form>";
    }
    ?>
</div>