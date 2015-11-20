<?php

use app\components\AppUtility;
use app\components\AppConstant;

$this->title = AppUtility::t('Print Test', false);
$this->params['breadcrumbs'][] = $this->title;
?>
<input type="hidden" class="" value="<?php echo $courseId ?>">
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', $course['name']], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id], 'page_title' => $this->title]); ?>
</div>
<div class="title-container">
    <div class="row">
        <div class="col-sm-11">
            <div class=" col-sm-6" style="right: 30px;">
                <div class="vertical-align title-page">Print Test</div>
            </div>
            <div class="col-sm-6">
            <div class="col-sm-2 pull-right">
            </div>
        </div>
    </div>
</div>
</div>
<div class="tab-content shadowBox print-test margin-top-fourty">
<?php

if ($overwriteBody == AppConstant::NUMERIC_ONE) {
    echo $body;
}
if (!isset($params['versions'])) {

    echo '<div class="print-test-header"><div class="col-md-12 margin-left-fifteen"><a href="print-test?cid=' . $courseId . '&aid=' . $assessmentId . '">Generate for in-browser printing</a></div>';
    if (1) {
        echo ' |<div class="col-md-12"> <a href="print-layout-word?cid=' . $courseId . '&amp;aid=' . $assessmentId . '">Generate for Word</a></div>';
    } ?>
    </div>
        <div class="col-md-12 padding-left-zero padding-bottom-five">
            <form method=post action="print-layout-bare?cid=<?php echo $courseId ?>&aid=<?php echo $assessmentId ?>">
            <h4>Copy-and-Paste Print Version</h4>
            <div class="col-md-12 margin-top-twenty padding-left-zero">
                <span>This page will help you create a copy of this assessment that you should be able to cut and
                paste into Word or another word processor and adjust layout for printing</span>
            </div>
            <div class="col-md-12 margin-top-twenty padding-left-zero">
                <span class="col-md-3 padding-left-zero">Number of different versions to generate:</span>
                <span class="col-md-9 margin-top-five">
                    <input class="form-control width-fifty-per" type=text name=versions value="1" size="3">
                </span>
            </div>
            <div class="col-md-12 margin-top-twenty padding-left-zero">
                <span class="col-md-3 padding-left-zero">Format?</span>
                <span class="col-md-9">
                    <span>
                        <input type="radio" name="format" value="trad" checked="checked" />
                        <span class="margin-left-ten">Form A &nbsp; 1&nbsp; 2&nbsp; 3,</span>
                    </span>
                    <span class="margin-left-five">Form B&nbsp; 1&nbsp; 2&nbsp; 3</span>
                    <span class="margin-left-thirty">
                        <input type="radio" name="format" value="inter"/>
                        <span class="margin-left-five">&nbsp;1a &nbsp; 1b &nbsp;  2a &nbsp; 2b</span>
                    </span>
                </span>
            </div>

            <div class="col-md-12 padding-left-zero margin-top-twenty">
            <span class="col-md-3 padding-left-zero">Generate answer keys?</span>
            <span class="col-md-9">
                <span>
                    <input type=radio name=keys value=1 checked=1>
                    <span class="margin-left-ten">Yes</span>
                </span>
                <span class="margin-left-thirty">
                <input type=radio name=keys value=0><span class="margin-left-fifteen">No</span>
                </span>
            </span>
            </div>
            <div class="col-md-12 margin-top-twenty padding-left-zero">
            <span class="col-md-3 padding-left-zero select-text-margin">Question separator</span>
            <span class="col-md-9">
                <input class="form-control width-fifty-per" type=text name="qsep" value="" />
            </span>
            </div>
            <div class="col-md-12 margin-top-twenty-five padding-left-zero">
            <span class="col-md-3 padding-left-zero select-text-margin">Version separator</span>
            <span class="col-md-9">
                <input class="form-control width-fifty-per" type=text name="vsep" value="+++++++++++++++" />
            </span>
            </div>

            <div class="col-md-12 margin-top-twenty padding-left-zero">
            <span class="col-md-3 padding-left-zero">Math display</span>
            <span class="col-md-9">
                <input type="radio" name="mathdisp" value="img" checked="checked" /><span class="margin-left-ten">Images</span>
                   <span class="margin-left-thirty"><input type="radio" name="mathdisp" value="text"/><span class="margin-left-ten">Text</span></span>
                   <span class="margin-left-thirty"><input type="radio" name="mathdisp" value="tex"/><span class="margin-left-ten">TeX</span></span>
                   <span class="margin-left-thirty"><input type="radio" name="mathdisp" value="textandimg"/><span class="margin-left-ten">Images,&nbsp; then again in text</span></span>
            </span>
            </div>

            <div class="col-md-12 margin-top-twenty padding-left-zero">
            <span class="col-md-3 padding-left-zero">Include question numbers and point values:</span>
            <span class="col-md-9">
                <input type="checkbox" name="showqn" checked="checked" />
            </span>
            </div>
            <div class="col-md-12 margin-top-twenty padding-left-zero">
            <span class="col-md-3 padding-left-zero">Hide text entry lines?</span>
            <span class="col-md-9">
                <input type=checkbox name=hidetxtboxes >
            </span>
            </div>
            <div class="col-md-12 padding-left-zero padding-top-thirty">
                <input type=submit value="Continue">
            </div>
    </form>
    </div>
<?php
} else { ?>
    <style type="text/css">
        body {
            padding: 0px;
            margin: 0px;
        }

        form {
            padding: 0px;
            margin: 0px;
        }

        div.maintest {
            position: absolute;
            top: 0px;
            left: 0px;
        }

        .pageb {
            clear: both;
            padding: 0px;
            margin: 0px;
            page-break-after: always;
            border-bottom: 1px dashed #aaa;
        }

        div.mainbody {
            margin: 0px;
            padding: 0px;
        }
    </style>
    <?php
    for ($pt = AppConstant::NUMERIC_ZERO; $pt < $printTwice; $pt++) {
        if ($pt == AppConstant::NUMERIC_ONE) {
            $sessionData['mathdisp'] = AppConstant::NUMERIC_ZERO;
            echo $params['vsep'] . '<br/>';;
        }

        if ($params['format'] == 'trad') {
            for ($j = AppConstant::NUMERIC_ZERO; $j < $copies; $j++) {
                if ($j > AppConstant::NUMERIC_ZERO) {
                    echo $params['vsep'] . '<br/>';
                }

                $headerleft = '';
                $headerleft .= $line['name'];
                if ($copies > AppConstant::NUMERIC_ONE) {
                    $headerleft .= ' - Form ' . ($j + AppConstant::NUMERIC_ONE);
                }
                if ((isset($params['iname']) || isset($params['cname'])) && isset($params['aname'])) {
                    $headerleft .= "<br/>";
                }
                $headerright = '';
                echo "<div class='print-test-header q'>\n";
                echo "<div class='col-md-12 hdrm'>\n";

                echo "<div class='margin-left-twenty' id=headerleft>$headerleft</div><div id=headerright>$headerright</div>\n";
                echo "<div id=intro>{$line['intro']}</div>\n";
                echo "</div>\n";
                echo "</div>\n";


                for ($i = AppConstant::NUMERIC_ZERO; $i < $numq; $i++) {
                    if ($i > AppConstant::NUMERIC_ZERO) {
                        echo $params['qsep'];
                    }
                    $sa[$j][$i] = AppUtility::printq($i, $qn[$questions[$i]], $seeds[$j][$i], $points[$questions[$i]], isset($params['showqn']));
                }

            }

            if ($params['keys'] > AppConstant::NUMERIC_ZERO) { //print answer keys
                for ($j = AppConstant::NUMERIC_ZERO; $j < $copies; $j++) {
                    echo $params['vsep'] . '<br/>';
                    echo '<b>Key - Form ' . ($j + AppConstant::NUMERIC_ONE) . "</b>\n";
                    echo "<ol>\n";
                    for ($i = AppConstant::NUMERIC_ZERO; $i < $numq; $i++) {
                        echo '<li>';
                        if (is_array($sa[$j][$i])) {
                            echo printfilter(filter(implode(' ~ ', $sa[$j][$i])));
                        } else {
                            echo printfilter(filter($sa[$j][$i]));
                        }
                        echo "</li>\n";
                    }
                    echo "</ol>\n";
                }
            }
        } else if ($params['format'] == 'inter') {

            $headerleft = '';
            $headerleft .= $line['name'];
            if ((isset($params['iname']) || isset($params['cname'])) && isset($params['aname'])) {
                $headerleft .= "<br/>";
            }
            $headerright = '';
            echo "<div class=q>\n";
            echo "<div class=hdrm>\n";

            echo "<div id=headerleft>$headerleft</div><div id=headerright>$headerright</div>\n";
            echo "<div id=intro>{$line['intro']}</div>\n";
            echo "</div>\n";
            echo "</div>\n";
            for ($i = AppConstant::NUMERIC_ZERO; $i < $numq; $i++) {
                if ($i > AppConstant::NUMERIC_ZERO) {
                    echo $params['qsep'];
                }
                for ($j = AppConstant::NUMERIC_ZERO; $j < $copies; $j++) {
                    if ($j > AppConstant::NUMERIC_ZERO) {
                        echo $params['qsep'];
                    }
                    $sa[] = AppUtility::printq($i, $qn[$questions[$i]], $seeds[$j][$i], $points[$questions[$i]], isset($params['showqn']));
                }
            }
            if ($params['keys'] > AppConstant::NUMERIC_ZERO) { //print answer keys
                echo $params['vsep'] . '<br/>';
                echo "<b>Key</b>\n";
                echo "<ol>\n";
                for ($i = AppConstant::NUMERIC_ZERO; $i < count($sa); $i++) {
                    echo '<li>';
                    if (is_array($sa[$i])) {
                        echo printfilter(filter(implode(' ~ ', $sa[$i])));
                    } else {
                        echo printfilter(filter($sa[$i]));
                    }
                    echo "</li>\n";
                }
                echo "</ol>\n";
            }
        }
    }
    $licurl = AppUtility::getURLFromHome('assessment', 'assessment/show-license?id=' . implode('-', $qn));
    echo '<hr/><p style="font-size:70%">License info at: <a href="' . $licurl . '">' . $licurl . '</a></p>';

    echo "<div class=cbutn><a href=" . AppUtility::getURLFromHome('course', 'course/course?cid=' . $courseId) . ">Return to course page</a></div>\n";
}
?>
</div>