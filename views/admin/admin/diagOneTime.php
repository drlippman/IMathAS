<?php
use app\components\AppUtility;

$this->title = 'Diagnostic One-time Passwords';
$this->params['breadcrumbs'] = $this->title;
?>
<div class="item-detail-header">
    <?php
    echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', 'Admin'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'admin/admin/index'], 'page_title' => $this->title]);
    ?>
</div>
<div class="title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="tab-content shadowBox non-nav-tab-item">
    <br/>
    <?php
    echo '<span class=col-lg-4><b>' . $nameOfDiag['name'] . '</b></span><br/><br/>';
    if (isset($params['generate'])) {
        if (isset($params['n'])) {

            echo "<span class=col-lg-3>Newly generated passwords</b></span> <span class=col-lg-2><a href=" . AppUtility::getURLFromHome('admin', 'admin/diag-one-time?id=' . $diag . '&view=true') . ">View all</a></span><br><br/>";
            echo '<div class="col-lg-12"><table class="table table-bordered table-striped table-hover data-table">
                <thead>
                    <tr>
                    <th style="text-align: center">' . AppUtility::t('Codes', false) . '</th>
                    <th style="text-align: center">Good For</th>
                    </tr>
                </thead>
                <tbody>';
            foreach ($code_list as $code) {
                echo "<tr><td style='text-align: center'> {$code['code']}</td><td style='text-align: center'>{$code['goodfor']}</td></tr>";
            }
            echo '</tbody></table></div>';
        } else {
            echo "<form method='post' action='diag-one-time?id=$diag&generate=true'>";
            echo '<div class="col-lg-6"><div class="col-lg-2 padding-top-five">' . AppUtility::t('Generate', false) . '</div><div class="col-lg-2 padding-left-zero"><input type="text" class="form-control" size="1" value="1" name="n" /></div><div class="col-lg-1 padding-left-zero padding-top-five">passwords </div><br/><br>';
            echo '<div class="col-lg-12"><div class="col-lg-4 padding-left-zero padding-top-five">' . AppUtility::t('Allow multi-use within', false) . '</div><div class="col-lg-2 padding-left-zero"><input type="text" class="form-control" size="1" value="0" name="multi" /></div><div class="col-lg-6 padding-left-zero padding-top-five">' . AppUtility::t('minutes (0 for one-time-only use)', false) . '</div></div>';
            echo '<br/><span class="col-lg-6"><input type="submit" value="Go" /></span>';
            echo '</form>';
        }
    } else if (isset($_GET['delete'])) {
        echo "<div class='col-lg-10'>" . AppUtility::t('Are you sure you want to delete all one-time passwords for this diagnostic?', false) . "</div>\n<br>";
        echo "<br><div class='col-lg-10'><div class='col-lg-1 padding-left-zero'><input type=button value=\"Delete\" onclick=\"window.location='diag-one-time?id=$diag&delete=true'\"></div>\n";
        echo "<div class='col-lg-2 padding-left-zero'><input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='index'\"></div></div>\n";
    } else {
        echo "<div class=col-lg-3><b>" . AppUtility::t('All one-time passwords', false) . "</b></div> <div class=col-lg-1><a href=" . AppUtility::getURLFromHome('admin', 'admin/diag-one-time?id=' . $diag . '&generate=true') . " ?>Generate</a></div>
             <div class=col-lg-1><a href=" . AppUtility::getURLFromHome('admin', 'admin/diag-one-time?id=' . $diag . '&delete=check') . ">Delete All</a></div><br/><br/>";
        echo '<div class="col-lg-12"><table class="table table-bordered table-striped table-hover data-table">
                    <thead>
                        <tr>
                            <th style="text-align: center">' . AppUtility::t('Codes', false) . '</th>
                            <th style="text-align: center">' . AppUtility::t('Good For', false) . '</th>
                            <th style="text-align: center">' . AppUtility::t('Created', false) . '</th>
                        </tr>
                    </thead>
                    <tbody  >';
        foreach ($code_list as $row) {
            echo "<tr>
                        <td style='text-align: center'>{$row['code']}</td>
                        <td style='text-align: center'>{$row['goodfor']}</td>
                        <td style='text-align: center'>{$row['time']}</td>
                    </tr>";
        }
        echo '</tbody></table></div>';
    }
    ?>
</div>