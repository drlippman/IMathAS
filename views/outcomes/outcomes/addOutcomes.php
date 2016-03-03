<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppConstant;
use app\components\AppUtility;
$cnt = AppConstant::NUMERIC_ZERO;
$this->title = AppUtility::t('Course Outcomes', false);
$this->params['breadcrumbs'][] = $this->title;
?>
<div >
    <div class="item-detail-header">
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name], 'link_url' => [AppUtility::getHomeURL()  . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id]]); ?>
    </div>
    <div class = "title-container">
        <div class="row">
            <div class="pull-left page-heading">
                <div class="vertical-align title-page"><?php echo $this->title ?></div>
            </div>
        </div>
    </div>
    <div class="item-detail-content padding-top-two-em">
        <?php echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course, 'section' => 'roster']);?>
    </div>
    <input type="hidden" id="course-id" value="<?php echo $courseId?>">
    <input type="hidden" id="outcome-url" value="<?php echo AppUtility::getURLFromHome('outcomes','outcomes/add-outcomes?cid='.$courseId.'&save=save')?>">
    <div class="tab-content shadowBox clear1 col-md-12 col-sm-12 padding-left-zero padding-right-zero">
        <?php
        if (isset($order))
        {
            $print = new AppUtility();
            $print->printOutcomesData($outcomes,$outcomeInfo,$cnt);
        }else{?>
            <style type="text/css">.drag {color:red; background-color:#fcc;} .icon {cursor: pointer;} ul.qview li {padding: 3px}</style>
            <div class="outcomes-nav-tab">
                <div class="align-link col-md-12 col-sm-12 padding-left-zero padding-right-zero padding-top-five">
                    <span class="col-md-2 col-sm-3 padding-left-zero"><a href="<?php echo AppUtility::getURLFromHome('outcomes','outcomes/outcome-map?cid='.$courseId)?>">View Outcomes Map</a></span>
                    <span class="col-md-3 col-sm-3"><a href="<?php echo AppUtility::getURLFromHome('outcomes','outcomes/outcome-report?cid='.$courseId.'&report=0')?>">View Outcomes Report</a></span>
                </div>
            </div>
            <div class="align-outcomes">
                <?php
                echo '<div class="breadcrumb">Use colored boxes to drag-and-drop order and move outcomes inside groups
                <input type="button" id="recchg" disabled="disabled" value="Save Changes" onclick="submitChanges()"/>
                 </div>';
                echo "<span id=\"submitnotice\" style=\"color:red;\"></span>";
                echo '<ul id="qviewtree" class="qview">';
                $print = new AppUtility();
                $print->printOutcomesData($outcomes,$outcomeInfo,$cnt);
                echo '</ul>';
                echo '<input type="button" onclick="addoutcomegrp()" value="Add Outcome Group"/> ';
                echo '<span class="padding-left-one-em"><input type="button" onclick="addoutcome()" value="Add Outcome"/></span>';
                ?>
            </div>
        <?php }?>
    </div>
</div>

<script type="text/javascript">
    var AHAHsaveurl = $("#outcome-url").val();
    var j=jQuery.noConflict();
</script>
<script type="text/javascript">
    var noblockcookie=true;
    var ocnt = 0;
    var html ="";
    var unsavedmsg = "You have unrecorded changes.  Are you sure you want to abandon your changes?";
    function txtchg() {
        if (!sortIt.haschanged) {
            sortIt.haschanged = true;
            sortIt.fireEvent("onFirstChange", null);
            window.onbeforeunload = function() {return unsavedmsg;}
        }
    }
    function addoutcome() {
        var html = '<li id="new'+ocnt+'"><span class=icon style="background-color:#0f0">O</span>';
        html += '<input class="outcome" type="text" size="60" id="newo'+ocnt+'" onkeyup="txtchg()">';
        html += '<a href="#" onclick="removeoutcome(this);return false\">Delete</a></li>';
        j('#qviewtree').append(html);
        j("#new"+ocnt).focus();
        ocnt++;

        if (!sortIt.haschanged) {
            sortIt.haschanged = true;
            sortIt.fireEvent('onFirstChange', null);
            window.onbeforeunload = function() {return unsavedmsg;}
        }
    }
    function addoutcomegrp() {
        var html = '<li class="blockli" id="newgrp'+ocnt+'">' +
            '<span class=icon style="background-color:#66f">G</span>';
        html += '<input class="outcome" type="text" size="60" id="newg'+ocnt+'" onkeyup="txtchg()">';
        html += '<a href="#" onclick="removeoutcomegrp(this);return false\">Delete</a></li>';
        j("#qviewtree").append(html);
        j("#newgrp"+ocnt).focus();
        ocnt++;

        if (!sortIt.haschanged) {
            sortIt.haschanged = true;
            sortIt.fireEvent('onFirstChange', null);
            window.onbeforeunload = function() {return unsavedmsg;}
        }
    }


    function removeoutcome(el) {
        var message = '';
        message += "Are you sure you want to delete this outcome";
        var html = '<div><p>' + message + '</p></div>';
        j('<div id="dialog"></div>').appendTo('body').html(html).dialog({
            modal: true, title: 'Outcome Delete', zIndex: 10000, autoOpen: true,
            width: 'auto', resizable: false,
            closeText: "hide",draggable:false,
            buttons: {
                "Cancel": function () {
                    j(this).dialog('destroy').remove();
                    return false;
                },
                "Confirm": function () {
                    j(el).parent().remove();
                    j(this).remove();
                }
            },
            close: function (event, ui) {
                j(this).remove();
            },
            open: function () {
                j('.ui-widget-overlay').bind('click', function () {
                    j('#dialog').dialog('close');
                })
            }
        });
        if (!sortIt.haschanged) {
            sortIt.haschanged = true;
            sortIt.fireEvent('onFirstChange', null);
            window.onbeforeunload = function() {return unsavedmsg;}
        }
    }



    function removeoutcomegrp(el) {
        var curloc = j(el).parent();
        var message = '';
        message += "Are you sure you want to delete this outcome group?"+"<p>"+"This will not delete the included outcomes.";
        var html = '<div><p>' + message + '</p></div>';
        console.log(message);
        j('<div id="dialog"></div>').appendTo('body').html(html).dialog({
            modal: true, title: 'Outcome Group Delete', zIndex: 10000, autoOpen: true,
            width: 'auto', resizable: false,
            closeText: "hide",draggable:false,
            buttons: {
                "Cancel": function () {
                    j(this).dialog('destroy').remove();
                    return false;
                },
                "Confirm": function () {
                    curloc.find("li").each(function() {
                        curloc.before(j(this));
                    });
                    curloc.remove();
                    j(this).remove();
                }
            },
            close: function (event, ui) {
                j(this).remove();
            },
            open: function () {
                j('.ui-widget-overlay').bind('click', function () {
                    j('#dialog').dialog('close');
                })
            }
        });
            if (!sortIt.haschanged) {
                sortIt.haschanged = true;
                sortIt.fireEvent('onFirstChange', null);
                window.onbeforeunload = function() {return unsavedmsg;}
            }
    }
</script>