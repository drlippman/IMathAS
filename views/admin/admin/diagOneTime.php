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
    echo '<span class=col-md-4><b>' . $nameOfDiag['name'] . '</b></span><br/><br/>';
    if (isset($params['generate'])) {
        if (isset($params['n'])) {

            echo "<span class=col-md-3>Newly generated passwords</b></span> <span class=col-md-2><a href=" . AppUtility::getURLFromHome('admin', 'admin/diag-one-time?id=' . $diag . '&view=true') . ">View all</a></span><br><br/>";
            echo '<div class="col-md-12"><table class="table table-bordered table-striped table-hover data-table">
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
            echo '<div class="col-md-6"><div class="col-md-2 padding-top-five">' . AppUtility::t('Generate', false) . '</div><div class="col-md-2 padding-left-zero"><input type="text" class="form-control" size="1" value="1" name="n" /></div><div class="col-md-1 padding-left-zero padding-top-five">passwords </div><br/><br>';
            echo '<div class="col-md-12"><div class="col-md-4 padding-left-zero padding-top-five">' . AppUtility::t('Allow multi-use within', false) . '</div><div class="col-md-2 padding-left-zero"><input type="text" class="form-control" size="1" value="0" name="multi" /></div><div class="col-md-6 padding-left-zero padding-top-five">' . AppUtility::t('minutes (0 for one-time-only use)', false) . '</div></div>';
            echo '<br/><span class="col-md-6"><input type="submit" value="Go" /></span>';
            echo '</form>';
        }
    } else if (isset($_GET['delete'])) {
    } else {
        echo "<div class=col-md-3><b>" . AppUtility::t('All one-time passwords', false) . "</b></div> <div class=col-md-1><a href=" . AppUtility::getURLFromHome('admin', 'admin/diag-one-time?id=' . $diag . '&generate=true') . " ?>Generate</a></div>
             <div class=col-md-1><a href='#' onclick=deleteAll($diag)>Delete All</a></div><br/><br/>";
//             <div class=col-md-1><a href=" .AppUtility::getURLFromHome('admin', 'admin/diag-one-time?id=' . $diag . '&delete=check') ." onclick=deleteAll($diag)>Delete All</a></div><br/><br/>";
        echo '<div class="col-md-12"><table class="table table-bordered table-striped table-hover data-table">
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
<script>
    function deleteAll(diag)
    {
        var html ='<div><p>Are you sure you want to delete all one-time passwords for this diagnostic?' ;
        $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
            modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
            width: 'auto', resizable: false,
            closeText: "hide",
            buttons: {
                "Cancel": function () {
                    $(this).dialog('destroy').remove();
                    return false;
                },
                "Confirm": function () {
                    $(this).dialog("close");
                    window.location = "diag-one-time?id="+diag+"&delete=true";
                    return true;
                }
            },
            close: function (event, ui) {
                $(this).remove();
            },
            open: function(){
                jQuery('.ui-widget-overlay').bind('click',function(){
                    jQuery('#dialog').dialog('close');
                })
            }
        });
    }
</script>