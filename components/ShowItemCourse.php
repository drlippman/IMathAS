<?php
namespace app\components;
use app\controllers\AppController;
use app\models\Assessments;
use app\models\AssessmentSession;
use app\models\ContentTrack;
use app\models\Forums;
use app\models\InlineText;
use app\models\InstrFiles;
use app\models\Items;
use app\models\LinkedText;
use app\models\Student;
use app\models\Stugroups;
use app\models\Wiki;
use app\models\WikiRevision;
use app\models\WikiView;
use \yii\base\Component;
use app\components\Calendar;
use yii\helpers\Html;
use yii\helpers\HtmlPurifier;

class ShowItemCourse extends Component
{
    public function showItems($items,$parent,$inpublic=false) {
        global $teacherId,$isTutor,$isStudent,$courseId,$userId,$openBlocks,$firstLoad,$sessionData,$previewShift,$myRights;
        global $hideIcons,$exceptions,$latePasses,$graphicalIcons,$isPublic,$studentInfo,$newPostCnts,$CFG,$latePassHrs,$hasStats,$toolSet,$readLinkedItems, $haveCalcedViewedAssess, $viewedAssess, $courseStudent;
        $studview = $sessionData['stuview'];
        $groupAdmin = $myRights >= AppConstant::GROUP_ADMIN_RIGHT;

        $imasroot = AppUtility::getHomeURL();
        if (!($CFG['CPS']['itemicons'])) {
            $itemIcons = array('folder'=>'folder2.gif', 'foldertree'=>'folder_tree.png', 'assess'=>'assess.png',
                'inline'=>'inline.png',	'web'=>'web.png', 'doc'=>'doc.png', 'wiki'=>'wiki.png',
                'html'=>'html.png', 'forum'=>'forum.png', 'pdf'=>'pdf.png',
                'ppt'=>'ppt.png', 'zip'=>'zip.png', 'png'=>'image.png', 'xls'=>'xls.png',
                'gif'=>'image.png', 'jpg'=>'image.png', 'bmp'=>'image.png',
                'mp3'=>'sound.png', 'wav'=>'sound.png', 'wma'=>'sound.png',
                'swf'=>'video.png', 'avi'=>'video.png', 'mpg'=>'video.png',
                'nb'=>'mathnb.png', 'mws'=>'maple.png', 'mw'=>'maple.png');
        } else {
            $itemIcons = $CFG['CPS']['itemicons'];
        }
        if($studview>-1){
            $studview=1;
        }
        if ($teacherId || ($isTutor && $groupAdmin)) {
            $canEdit = true;
            $viewAll = true;
        } else if($isTutor) {
            $canEdit = false;
            $viewAll = true;
        } else {
            $canEdit = false;
            $viewAll = false;
        }
        $now = time() + $previewShift;
        $blocklist = array();
        for ($i = AppConstant::NUMERIC_ZERO; $i < count($items); $i++)
        {
            if (is_array($items[$i]))
            {
                /*
                 * if is a block
                 */
                $blocklist[] = $i+AppConstant::NUMERIC_ONE;
            }
        }
        if ($canEdit && !$isStudent)
        {
            echo ShowItemCourse::generateAddItem($parent,'t',$studview);
        }

        for ($i = AppConstant::NUMERIC_ZERO; $i < count($items); $i++)
        {
            if (is_array($items[$i]))
            {

                /*
                 * if is a block
                 */
                $turnonpublic = false;
                if ($isPublic && !$inpublic) {
                    if (($items[$i]['public']) && $items[$i]['public'] == AppConstant::NUMERIC_ONE) {
                        $turnonpublic = true;
                    } else {
                        continue;
                    }
                }

                if (($items[$i]['grouplimit']) && count($items[$i]['grouplimit']) > AppConstant::NUMERIC_ZERO && !$viewAll) {
                  if (!('s-'.$studentInfo['section']==$items[$i]['grouplimit'])) {
                      continue;
                  }
                }

                $items[$i]['name'] = stripslashes($items[$i]['name']);
                if ($canEdit && !$studview && !$isStudent) {
                    echo ShowItemCourse::generatemoveselect($i,count($items),$parent,$blocklist);
                }
                if ($items[$i]['startdate'] == AppConstant::NUMERIC_ZERO) {
                    $startDate = _('Always');
                } else {
                    $startDate = AppUtility::formatdate($items[$i]['startdate']);
                }
                if ($items[$i]['enddate'] == AppConstant::ALWAYS_TIME) {
                    $endDate = _('Always');
                } else {
                    $endDate = AppUtility::formatdate($items[$i]['enddate']);
                }

                $bnum = $i+AppConstant::NUMERIC_ONE;
                if (in_array($items[$i]['id'],$openBlocks))
                {
                    $isopen = true;
                } else
                {
                    $isopen = false;
                }
                if (strlen($items[$i]['SH']) == AppConstant::NUMERIC_ONE || $items[$i]['SH'][1] == 'O') {
                    $availbeh = _('Expanded');
                } else if ($items[$i]['SH'][1]=='F') {
                    $availbeh = _('as Folder');
                } else if ($items[$i]['SH'][1]=='T') {
                    $availbeh = _('as TreeReader');
                } else {
                    $availbeh = _('Collapsed');
                }
                if ($items[$i]['colors']=='') {
                    $titlebg = '';
                } else {
                    list($titlebg,$titletxt,$bicolor) = explode(',',$items[$i]['colors']);
                }

                if (!isset($items[$i]['avail'])) { //backwards compat
                    $items[$i]['avail'] = AppConstant::NUMERIC_ONE;
                }

                if ($items[$i]['avail'] == AppConstant::NUMERIC_TWO || (($items[$i]['avail'] == AppConstant::NUMERIC_ONE )&& ($items[$i]['startdate']<$now) && ($items[$i]['enddate'] > $now)))
                {

                    /*
                     * if "available"
                     */
                    if ($firstLoad && (strlen($items[$i]['SH'])== AppConstant::NUMERIC_ONE || $items[$i]['SH'][1]=='O')) {
                        echo "<script> oblist = oblist + ',".$items[$i]['id']."';</script>\n";
                        $isopen = true;

                    }
                    if ($items[$i]['avail'] == AppConstant::NUMERIC_TWO) {
                        $show = sprintf(_('Showing %s Always'), $availbeh);
                    } else {
                        $show = sprintf(_('Showing %1$s %2$s until %3$s'), $availbeh, $startDate, $endDate);
                    }
                    if (strlen($items[$i]['SH'])> AppConstant::NUMERIC_ONE && $items[$i]['SH'][1]=='F') {
                        /*
                         * show as folder
                         */
                        if ($canEdit) {
                            echo '<div class="inactivewrapper" onmouseover="this.className=\'activewrapper\'" onmouseout="this.className=\'inactivewrapper\'">';
                        }
                        echo "<div class='block item'";
                        if ($titlebg!='') {
                            echo "style=\"background-color:$titlebg;color:$titletxt;\"";
                            $astyle = "style=\"color:$titletxt;\"";
                        } else {
                            $astyle = '';
                        }
                        echo ">";

                        if (($hideIcons&16) == AppConstant::NUMERIC_ZERO) {
                            if ($isPublic) {
                                echo "<span class=left><a href=\"public?cid=$courseId&folder=$parent-$bnum\" border=0>";
                            } else {
                                echo "<span class=left><a href=\"courssse?cid=$courseId&folder=$parent-$bnum\" border=0>";
                            }
                                echo "<img alt=\"folder\" src=\"$imasroot"."img/folder.gif\"></a></span>";
                            echo "<div class=title>";
                        }
                        if ($isPublic) {
                            echo "<a href=\"public?cid=$courseId&folder=$parent-$bnum\" $astyle><b>{$items[$i]['name']}</b></a> ";
                        } else {
                            echo "<a href=\"course?cid=$courseId&folder=$parent-$bnum\" $astyle><b>{$items[$i]['name']}</b></a> ";
                        }
                        if (($items[$i]['newflag']) && $items[$i]['newflag']== AppConstant::NUMERIC_ONE) {
                            echo "<span style=\"color:red;\">", _('New'), "</span>";
                        }
                        if ($viewAll) {
                            echo '<span class="instrdates">';
                            echo "<br>$show ";
                            echo '</span>';
                        }
                        if ($canEdit && !$studview && !$isStudent) { ?>
                            <span class="instronly common-setting">
                            <a class="dropdown-toggle grey-color-link select_button1 floatright"
                               data-toggle="dropdown" href="javascript:void(0);">
                                <img alt="setting" class="floatright course-setting-button" src="<?php echo AppUtility::getAssetURL() ?>img/courseSettingItem.png"/></a>
                            <ul class="select1 dropdown-menu selected-options pull-right">
                                <li><a class="modify"
                                       href= "<?php echo AppUtility::getURLFromHome('block','block/add-block?cid='.$courseId.'&id='.$parent.'-'.$bnum.'&modify=1')?>"><?php AppUtility::t('Modify'); ?></a>
                                </li>
                                <li><a id=" delete"
                                       href="javascript:deleteItem('<?php echo $parent . '-' . $bnum ?>','<?php echo AppConstant::BLOCK ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Delete'); ?></a>
                                </li>
                                <li><a id="copy"
                                       href="javascript:copyItem('<?php echo $parent . '-' . $bnum; ?>','<?php echo AppConstant::BLOCK ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Copy'); ?></a>
                                </li>
                                <li><a id="copy"
                                       href="<?php echo AppUtility::getURLFromHome('block', 'block/new-flag?cid=' . $courseId . '&newflag=' . $parent . '-' . $bnum) ?>"><?php AppUtility::t('NewFlag'); ?></a>
                                </li>
                            </ul></span>
                        <?php }
                        if (($hideIcons&16) == AppConstant::NUMERIC_ZERO) {
                            echo "</div>";
                        }
                        echo '<div class="clear"></div>';
                        echo "</div>";
                        if ($canEdit) {
                            echo '</div>'; //itemwrapper
                        }
                    } else if (strlen($items[$i]['SH'])> AppConstant::NUMERIC_ONE && $items[$i]['SH'][1]=='T') {
                        /*
                         * show as tree reader
                         */
                        if ($isPublic) {
                            continue;
                        } //public treereader not supported yet.
                        if ($canEdit) {
                            echo '<div class="inactivewrapper" onmouseover="this.className=\'activewrapper\'" onmouseout="this.className=\'inactivewrapper\'">';
                        }
                        echo "<div class='block item'";
                        if ($titlebg!='') {
                            echo "style=\"background-color:$titlebg;color:$titletxt;\"";
                            $astyle = "style=\"color:$titletxt;\"";
                        } else {
                            $astyle = '';
                        }
                        echo ">";

                        $treeReaderLink = AppUtility::getURLFromHome('block', 'block/tree-reader?cid='.$courseId.'&folder='.$parent.'-'.$bnum);
                        if (($hideIcons&16) == AppConstant::NUMERIC_ZERO) {
                            if ($isPublic) {
                            } else {
                                echo "<span class=left>";?>
                                <a href="<?php echo $treeReaderLink;?>" border="0">
                                <img alt="folder" src="<?php echo $imasroot; ?>img/folder_tree.png"></a></span>
                            <?php }
                            echo "<div class=title>";
                        }
                        if ($isPublic) {
                        } else { ?>
                             <a href="<?php echo AppUtility::getURLFromHome('block','block/tree-reader?cid='.$courseId.'&folder='.$parent.'-'.$bnum)?>"><b><?php echo $items[$i]['name'];?></b></a>
                        <?php }
                        if (($items[$i]['newflag']) && $items[$i]['newflag']== AppConstant::NUMERIC_ONE) {
                            echo "<span style=\"color:red;\">", _('New'), "</span>";
                        }
                        if ($viewAll) {
                            echo '<span class="instrdates">';
                            echo "<br>$show ";
                            echo '</span>';
                        }
                        if ($canEdit && !$studview && !$isStudent ) { ?>

                            <span class="instronly common-setting">

                                <a class="dropdown-toggle grey-color-link select_button1"
                               data-toggle="dropdown" href="javascript:void(0);">
                                <img alt="setting" class="floatright course-setting-button" src="<?php echo AppUtility::getAssetURL() ?>img/courseSettingItem.png"/></a>
                            <ul class="select1 dropdown-menu selected-options pull-right">
                                <li><a class="modify"
                                       href="<?php echo AppUtility::getURLFromHome('course','course/course?cid='.$courseId.'&folder='.$parent.'-'.$bnum)?>"><?php AppUtility::t('Edit Content'); ?></a></li>
                                <li><a class="modify"
                                       href= "<?php echo AppUtility::getURLFromHome('block','block/add-block?cid='.$courseId.'&id='.$parent.'-'.$bnum.'&modify=1')?>"><?php AppUtility::t('Modify'); ?></a>
                                </li>
                                <li><a id="delete"
                                       href="javascript:deleteItem('<?php echo $parent . '-' . $bnum ?>','<?php echo AppConstant::BLOCK ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Delete'); ?></a>
                                </li>
                                <li><a id="copy"
                                       href="javascript:copyItem('<?php echo $parent . '-' . $bnum; ?>','<?php echo AppConstant::BLOCK ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Copy'); ?></a>
                                </li>
                                <li><a id="copy"
                                       href="<?php echo AppUtility::getURLFromHome('block', 'block/new-flag?cid=' . $courseId . '&newflag=' . $parent . '-' . $bnum) ?>"><?php AppUtility::t('NewFlag'); ?></a>
                                </li>
                            </ul>

                            </span>

                        <?php
                        }
                        if (($hideIcons&16) == AppConstant::NUMERIC_ZERO) {
                            echo "</div>";
                        }
                        echo '<div class="clear"></div>';
                        echo "</div>";
                        if ($canEdit) {
                            echo '</div>'; //itemwrapper
                        }
                    } else {
                        if ($canEdit) {
                            echo '<div class="inactivewrapper" onmouseover="this.className=\'activewrapper\'" onmouseout="this.className=\'inactivewrapper\'">';
                        }
                        echo "<div class='block item block-item-name'";
                        if ($titlebg!='') {
                            echo "style=\"background-color:$titlebg;color:$titletxt;\"";
                            $astyle = "style=\"color:$titletxt;\"";
                        } else {
                            $astyle = '';
                        }
                        echo ">";

                        if (($hideIcons&16) == AppConstant::NUMERIC_ZERO) {
                            echo "<span class=left>";
                            echo "<img alt=\"expand/collapse\" style=\"cursor:pointer;\" id=\"img{$items[$i]['id']}\" src=\"$imasroot"."img/";
                            if ($isopen)
                            {
                                echo _('collapse');
                            } else {
                                echo _('expand');
                            }
                            echo ".gif\" onClick=\"toggleblock('{$items[$i]['id']}','$parent-$bnum')\" /></span>";
                            echo "<div class=title>";
                        }
                        if (!$canEdit) {
                            echo '<span class="right">';
                            echo "<a href=\"".($isPublic?"public":"course")."?cid=$courseId&folder=$parent-$bnum\" $astyle>", _('Isolate'), "</a>";
                            echo '</span>';
                        }
                        echo "<span class=pointer onClick=\"toggleblock('{$items[$i]['id']}','$parent-$bnum')\">";
                        echo "<b><a href=\"#\" onclick=\"return false;\" $astyle>{$items[$i]['name']}</a></b></span> ";
                        if (($items[$i]['newflag']) && $items[$i]['newflag']== AppConstant::NUMERIC_ONE) {
                            echo "<span style=\"color:red;\">", _('New'), "</span>";
                        }
                        if ($viewAll) {
                            echo '<span class="instrdates">';
                            echo "<br>$show ";
                            echo '</span>';
                        }
                        if ($canEdit && !$studview && !$isStudent) {
                           ?>
                            <span class="instronly common-setting margin-right-minus-fifteen">
                            <a class="dropdown-toggle grey-color-link select_button1 floatright"
                               data-toggle="dropdown" href="javascript:void(0);">
                                <img alt="setting" class="floatright course-setting-button" src="<?php echo AppUtility::getAssetURL() ?>img/courseSettingItem.png"/></a>
                            <ul class="select1 dropdown-menu selected-options pull-right">
                                <li><a class="modify"
                                       href="<?php echo AppUtility::getURLFromHome('course','course/course?cid='.$courseId.'&folder='.$parent.'-'.$bnum)?>"><?php AppUtility::t('Isolate'); ?></a></li>
                                <li><a class="modify"
                                       href= "<?php echo AppUtility::getURLFromHome('block','block/add-block?cid='.$courseId.'&id='.$parent.'-'.$bnum.'&modify=1')?>"><?php AppUtility::t('Modify'); ?></a>
                                </li>
                                <li><a id="delete"
                                       href="javascript:deleteItem('<?php echo $parent . '-' . $bnum ?>','<?php echo AppConstant::BLOCK ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Delete'); ?></a>
                                </li>
                                <li><a id="copy"
                                       href="javascript:copyItem('<?php echo $parent . '-' . $bnum; ?>','<?php echo AppConstant::BLOCK ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Copy'); ?></a>
                                </li>
                                <li><a id="copy"
                                       href="<?php echo AppUtility::getURLFromHome('block', 'block/new-flag?cid=' . $courseId . '&newflag=' . $parent . '-' . $bnum) ?>"><?php AppUtility::t('NewFlag'); ?></a>
                                </li>
                            </ul></span>
                           <?php
                        }
                        if (($hideIcons&16) == AppConstant::NUMERIC_ZERO) {
                            echo "</div>";
                        }
                        echo "</div>\n";
                        if ($canEdit) {
                            echo '</div>'; //itemwrapper
                        }
                        if ($isopen) {
                            echo "<div class='blockitems block-alignment'";
                        } else {
                            echo "<div class=hidden ";
                        }
                        $style = '';
                        if (($items[$i]['fixedheight']) && $items[$i]['fixedheight'] > AppConstant::NUMERIC_ZERO) {
                            if (strpos($_SERVER['HTTP_USER_AGENT'],'MSIE 6')!==false) {
                                $style .= 'overflow: auto; height: expression( this.offsetHeight > '.$items[$i]['fixedheight'].' ? \''.$items[$i]['fixedheight'].'px\' : \'auto\' );';
                            } else {
                                $style .= 'overflow: auto; max-height:'.$items[$i]['fixedheight'].'px;';
                            }
                        }
                        if ($titlebg!='') {
                            $style .= "background-color:$bicolor;";
                        }
                        if ($style != '') {
                            echo "style=\"$style\" ";
                        }
                        echo "id=\"block{$items[$i]['id']}\">";
                        if ($isopen) {

                            $this->showItems($items[$i]['items'],$parent.'-'.$bnum,$inpublic||$turnonpublic);
                        } else {
                            echo _('Loading content...');
                        }
                        echo "</div>";
                    }
                } else if ($viewAll || ($items[$i]['SH'][0] == 'S' && $items[$i]['avail'] > AppConstant::NUMERIC_ZERO)) { //if "unavailable"
                    if ($items[$i]['avail'] == AppConstant::NUMERIC_ZERO) {
                        $show = _('Hidden');
                    } else if ($items[$i]['SH'][0] == 'S') {
                        $show = _('Currently Showing');
                        if (strlen($items[$i]['SH']) > AppConstant::NUMERIC_ONE && $items[$i]['SH'][1] == 'F') {
                            $show .= _(' as Folder. ');
                        } else if (strlen($items[$i]['SH'])> AppConstant::NUMERIC_ONE && $items[$i]['SH'][1]=='T') {
                            $show .= _(' as TreeReader. ');
                        } else {
                            $show .= _(' Collapsed. ');
                        }
                        $show .= sprintf(_('Showing %1$s %2$s to %3$s'), $availbeh, $startDate, $endDate);
                    } else { //currently hidden, using dates
                        $show = "Currently Hidden. ";
                        $show .= sprintf(_('Showing %1$s %2$s to %3$s'), $availbeh, $startDate, $endDate);
                    }
                    if (strlen($items[$i]['SH']) > AppConstant::NUMERIC_ONE && $items[$i]['SH'][1] == 'F')
                    {
                       /*
                        * show as folder
                        */
                        if ($canEdit) {
                            echo '<div class="inactivewrapper" onmouseover="this.className=\'activewrapper\'" onmouseout="this.className=\'inactivewrapper\'">';
                        }
                        echo "<div class='block item'";
                        if ($titlebg!='') {
                            echo "style=\"background-color:$titlebg;color:$titletxt;\"";
                            $astyle = "style=\"color:$titletxt;\"";
                        } else {
                            $astyle = '';
                        }
                        echo ">";
                        if (($hideIcons&16) == AppConstant::NUMERIC_ZERO) {
                            echo "<span class=left><a href=\"course?cid=$courseId&folder=$parent-$bnum\" border=0>";
                                echo "<img alt=\"folder\" src=\"$imasroot"."img/folder.gif\"></a></span>";
                            echo "<div class=title>";
                        }
                        echo "<a href=\"course?cid=$courseId&folder=$parent-$bnum\" $astyle><b>";
                        if ($items[$i]['SH'][0]=='S') {echo "{$items[$i]['name']}</b></a> ";} else {echo "<i>{$items[$i]['name']}</i></b></a>";}
                        if (($items[$i]['newflag']) && $items[$i]['newflag']== AppConstant::NUMERIC_ONE) {
                            echo " <span style=\"color:red;\">", _('New'), "</span>";
                        }
                        if ($viewAll) {
                            echo '<span class="instrdates">';
                            echo "<br><i>$show</i> ";
                            echo '</span>';
                        }
                        if ($canEdit && !$studview && !$isStudent) {
                            ?>
                            <span class="instronly common-setting">
                            <a class="dropdown-toggle grey-color-link select_button1 floatright"
                               data-toggle="dropdown" href="javascript:void(0);">
                                <img alt="setting" class="floatright course-setting-button" src="<?php echo AppUtility::getAssetURL() ?>img/courseSettingItem.png"/></a>
                            <ul class="select1 dropdown-menu selected-options pull-right">
                                <li><a class="modify"
                                       href= "<?php echo AppUtility::getURLFromHome('block','block/add-block?cid='.$courseId.'&id='.$parent.'-'.$bnum.'&modify=1')?>"><?php AppUtility::t('Modify'); ?></a>
                                </li>
                                <li><a id="delete"
                                       href="javascript:deleteItem('<?php echo $parent . '-' . $bnum ?>','<?php echo AppConstant::BLOCK ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Delete'); ?></a>
                                </li>
                                <li><a id="copy"
                                       href="javascript:copyItem('<?php echo $parent . '-' . $bnum; ?>','<?php echo AppConstant::BLOCK ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Copy'); ?></a>
                                </li>
                                <li><a id="copy"
                                       href="<?php echo AppUtility::getURLFromHome('block', 'block/new-flag?cid=' . $courseId . '&newflag=' . $parent . '-' . $bnum) ?>"><?php AppUtility::t('NewFlag'); ?></a>
                                </li>
                            </ul></span>
                        <?php
                        }

                        if (($hideIcons&16) == AppConstant::NUMERIC_ZERO) {
                            echo "</div>";
                        }
                        echo '<div class="clear"></div>';
                        echo "</div>";
                        if ($canEdit) {
                            echo '</div>'; //itemwrapper
                        }
                    } else if (strlen($items[$i]['SH'])> AppConstant::NUMERIC_ONE && $items[$i]['SH'][1]=='T') { //show as tree reader
                        if ($canEdit) {
                            echo '<div class="inactivewrapper" onmouseover="this.className=\'activewrapper\'" onmouseout="this.className=\'inactivewrapper\'">';
                        }
                        echo "<div class='block item'";
                        if ($titlebg!='') {
                            echo "style=\"background-color:$titlebg;color:$titletxt;\"";
                            $astyle = "style=\"color:$titletxt;\"";
                        } else {
                            $astyle = '';
                        }
                        echo ">";
                        $treeReaderLink = AppUtility::getURLFromHome('block', 'block/tree-reader?cid='.$courseId.'&folder='.$parent.'-'.$bnum);
                        if (($hideIcons&16) == AppConstant::NUMERIC_ZERO) { ?>
                            <span class=left><a href="<?php echo $treeReaderLink ;?>" border= "0">
                               <img alt="folder" src="<?php echo $imasroot;?>img/folder_tree.png"></a></span>
                        <?php
                            echo "<div class=title>";
                        } ?>
                        <a href="<?php echo AppUtility::getURLFromHome('block','block/tree-reader?cid='.$courseId.'&folder='.$parent.'-'.$bnum)?>"><b><?php echo $items[$i]['name'];?></b></a>
                       <?php if ($items[$i]['SH'][0]=='S') {echo "{$items[$i]['name']}</b></a> ";} else {echo "<i>{$items[$i]['name']}</i></b></a>";}
                        if (($items[$i]['newflag']) && $items[$i]['newflag'] == AppConstant::NUMERIC_ONE) {
                            echo " <span style=\"color:red;\">", _('New'), "</span>";
                        }
                        if ($viewAll) {
                            echo '<span class="instrdates">';
                            echo "<br><i>$show</i> ";
                            echo '</span>';
                        }
                        if ($canEdit && !$studview && !$isStudent) {
                             ?>
                            <span class="instronly common-setting">
                            <a class="dropdown-toggle grey-color-link select_button1 floatright"
                               data-toggle="dropdown" href="javascript:void(0);">
                                <img alt="setting" class="floatright course-setting-button" src="<?php echo AppUtility::getAssetURL() ?>img/courseSettingItem.png"/></a>
                            <ul class="select1 dropdown-menu selected-options pull-right">
                                <li><a class="modify"
                                       href="<?php echo AppUtility::getURLFromHome('course','course/course?cid='.$courseId.'&folder='.$parent.'-'.$bnum)?>"><?php AppUtility::t('Edit Contents'); ?></a></li>
                                <li><a class="modify"
                                       href= "<?php echo AppUtility::getURLFromHome('block','block/add-block?cid='.$courseId.'&id='.$parent.'-'.$bnum.'&modify=1')?>"><?php AppUtility::t('Modify'); ?></a>
                                </li>
                                <li><a id="delete"
                                       href="javascript:deleteItem('<?php echo $parent . '-' . $bnum ?>','<?php echo AppConstant::BLOCK ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Delete'); ?></a>
                                </li>
                                <li><a id="copy"
                                       href="javascript:copyItem('<?php echo $parent . '-' . $bnum; ?>','<?php echo AppConstant::BLOCK ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Copy'); ?></a>
                                </li>
                                <li><a id="copy"
                                       href="<?php echo AppUtility::getURLFromHome('block', 'block/new-flag?cid=' . $courseId . '&newflag=' . $parent . '-' . $bnum) ?>"><?php AppUtility::t('NewFlag'); ?></a>
                                </li>
                            </ul></span>
                          <?php  echo '</span>';
                        }

                        if (($hideIcons&16) == AppConstant::NUMERIC_ZERO) {
                            echo "</div>";
                        }
                        echo '<div class="clear"></div>';
                        echo "</div>";
                        if ($canEdit) {
                            echo '</div>'; //itemwrapper
                        }
                    } else {
                        if ($canEdit) {
                            echo '<div class="inactivewrapper" onmouseover="this.className=\'activewrapper\'" onmouseout="this.className=\'inactivewrapper\'">';
                        }
                        echo "<div class='block item'";
                        if ($titlebg!='') {
                            echo "style=\"background-color:$titlebg;color:$titletxt;\"";
                            $astyle = "style=\"color:$titletxt;\"";
                        } else {
                            $astyle = '';
                        }
                        echo ">";
                        if (($hideIcons&16) == AppConstant::NUMERIC_ZERO) {
                            echo "<span class=left>";
                            echo "<img alt=\"expand/collapse\" style=\"cursor:pointer;\" id=\"img{$items[$i]['id']}\" src=\"$imasroot"."img/";
                            if ($isopen) {echo _('collapse');} else {echo _('expand');}
                            echo ".gif\" onClick=\"toggleblock('{$items[$i]['id']}','$parent-$bnum')\" /></span>";
                            echo "<div class=title>";
                        }
                        if (!$canEdit) {
                            echo '<span class="right">';
                            echo "<a href=\"".($isPublic?"public":"course")."?cid=$courseId&folder=$parent-$bnum\" $astyle>", _('Isolate'), "</a>";
                            echo '</span>';
                        }
                        echo "<span class=pointer onClick=\"toggleblock('{$items[$i]['id']}','$parent-$bnum')\">";
                        echo "<b>";
                        if ($items[$i]['SH'][0] == 'S') {
                            echo "<a href=\"#\" onclick=\"return false;\" $astyle>{$items[$i]['name']}</a>";
                        } else {
                            echo "<i><a href=\"#\" onclick=\"return false;\" $astyle>{$items[$i]['name']}</a></i>";
                        }
                        echo "</b></span> ";
                        if (($items[$i]['newflag']) && $items[$i]['newflag'] == AppConstant::NUMERIC_ONE) {
                            echo "<span style=\"color:red;\">", _('New'), "</span>";
                        }
                        if ($viewAll) {
                            echo '<span class="instrdates">';
                            echo "<br><i>$show</i> ";
                            echo '</span>';
                        }
                        if ($canEdit && !$studview && !$isStudent) {
                            ?>
                            <span class="instronly common-setting">
                            <a class="dropdown-toggle grey-color-link select_button1 floatright"
                               data-toggle="dropdown" href="javascript:void(0);">
                                <img alt="setting" class="floatright course-setting-button" src="<?php echo AppUtility::getAssetURL() ?>img/courseSettingItem.png"/></a>
                            <ul class="select1 dropdown-menu selected-options pull-right">
                                <li><a class="modify"
                                       href="<?php echo AppUtility::getURLFromHome('course','course/course?cid='.$courseId.'&folder='.$parent.'-'.$bnum)?>"><?php AppUtility::t('Isolate1'); ?></a></li>
                                <li><a class="modify"
                                       href= "<?php echo AppUtility::getURLFromHome('block','block/add-block?cid='.$courseId.'&id='.$parent.'-'.$bnum.'&modify=1')?>"><?php AppUtility::t('Modify'); ?></a>
                                </li>
                                <li><a id="delete"
                                       href="javascript:deleteItem('<?php echo $parent . '-' . $bnum ?>','<?php echo AppConstant::BLOCK ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Delete'); ?></a>
                                </li>
                                <li><a id="copy"
                                       href="javascript:copyItem('<?php echo $parent . '-' . $bnum; ?>','<?php echo AppConstant::BLOCK ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Copy'); ?></a>
                                </li>
                                <li><a id="copy"
                                       href="<?php echo AppUtility::getURLFromHome('block', 'block/new-flag?cid=' . $courseId . '&newflag=' . $parent . '-' . $bnum) ?>"><?php AppUtility::t('NewFlag'); ?></a>
                                </li>
                            </ul></span>
                            <?php echo '</span>';
                        }
                        if (($hideIcons&16) == AppConstant::NUMERIC_ZERO) {
                            echo "</div>";
                        }
                        echo "</div>\n";
                        if ($canEdit) {
                            echo '</div>'; //itemwrapper
                        }
                        if ($isopen) {
                            echo "<div class='blockitems block-alignment'";
                        } else {
                            echo "<div class=hidden ";
                        }
                        //if ($titlebg!='') {
                        //	echo "style=\"background-color:$bicolor;\"";
                        //}
                        $style = '';
                        if ($items[$i]['fixedheight'] > AppConstant::NUMERIC_ZERO) {
                            if (strpos($_SERVER['HTTP_USER_AGENT'],'MSIE 6')!==false) {
                                $style .= 'overflow: auto; height: expression( this.offsetHeight > '.$items[$i]['fixedheight'].' ? \''.$items[$i]['fixedheight'].'px\' : \'auto\' );';
                            } else {
                                $style .= 'overflow: auto; max-height:'.$items[$i]['fixedheight'].'px;';
                            }
                        }
                        if ($titlebg!='') {
                            $style .= "background-color:$bicolor;";
                        }
                        if ($style != '') {
                            echo "style=\"$style\" ";
                        }
                        echo "id=\"block{$items[$i]['id']}\">";
                        if ($isopen) {
                            $this->showitems($items[$i]['items'],$parent.'-'.$bnum,$inpublic||$turnonpublic);
                        } else {
                            echo _('Loading content...');
                        }
                        echo "</div>";
                    }
                }
                continue;
            } else if ($isPublic && !$inpublic) {
                continue;
            }
            /**
             * Items out of block.
             */
            $line = Items::getByItem($items[$i]);

            if ($canEdit && !$studview && !$isStudent) {
                echo ShowItemCourse::generatemoveselect($i,count($items),$parent,$blocklist);
            }
            if ($line['itemtype'] == "Calendar") {
                global $currentTime;
                $currentTime = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
                if ($isPublic) { continue;}
                ShowItemCourse::beginitem($canEdit); ?>

            <?php   if ($canEdit && !$studview && !$isStudent) {
                    ?>
                    <a href="#" onclick="deleteItem('<?php echo $items[$i] ;?>','<?php echo AppConstant::CALENDAR ?>','<?php echo $parent ;?>','<?php echo $courseId ;?>')"><?php AppUtility::t('Delete')?></a> |
                    <a href="<?php echo AppUtility::getURLFromHome('instructor', 'instructor/manage-events?cid=' . $courseId); ?>">Manage Events</a>
              <?php } ?>
                <div style="padding-bottom: 15px; padding-right: 15px">
                    <div class="col-lg-12 padding-alignment calendar-container">
                        <div class='calendar padding-alignment calendar-alignment col-lg-9 pull-left'>
                            <input type="hidden" class="current-time" value="<?php echo $currentTime ?>">

                            <div id="demo" style="display:table-cell; vertical-align:middle;"></div>
                            <input type="hidden" class="calender-course-id" value="<?php echo $courseId ?>">
                        </div>
                        <div class="calendar-day-details-right-side pull-left col-lg-3">
                            <div class="day-detail-border">
                                <b style="font-size: 18px"><?php AppUtility::t('Day Details')?></b>
                                <a style="font-size: 12px; float: right" href="#" onclick="ShowAll();">Show All</a>
                                <div class="day-details"></div>
                            </div>
                            <div class="calendar-day-details word-wrap-break-word"></div>
                        </div>
                    </div>
                </div>

                <?php
                ShowItemCourse::enditem($canEdit);
            } else if ($line['itemtype'] == "Assessment") {
                /**
                 * Assessment
                 */
                if ($isPublic) {
                    continue;
                }
                $typeid = $line['typeid'];
                $line = Assessments::getAssessmentDataById($typeid);
                /*
                 * do time limit mult
                 */
                if (($studentInfo['timelimitmult'])) {
                    $line['timelimit'] *= $studentInfo['timelimitmult'];
                }
                if (strpos($line['summary'],'<p ') !== AppConstant::NUMERIC_ZERO && strpos($line['summary'],'<ul') !== AppConstant::NUMERIC_ZERO && strpos($line['summary'],'<ol')!==0) {
                    $line['summary'] = '<p>'.$line['summary'].'</p>';
                    if (preg_match('/^\s*<p[^>]*>\s*<\/p>\s*$/',$line['summary'])) {
                        $line['summary'] = '';
                    }
                }
                if (($isStudent) && !($sessionData['stuview'])) {
                    $rec = "data-base=\"assesssum-$typeid\" ";
                    $line['summary'] = str_replace('<a ','<a '.$rec, $line['summary']);
                }
                /*
                 * check for exception
                 */
                $canundolatepass = false;
                $latepasscnt = AppConstant::NUMERIC_ZERO;
                if (($exceptions[$items[$i]]))
                {
                    /*
                     * if latepass and it's before original due date or exception is for more than a latepass past now
                     */
                    if ($exceptions[$items[$i]][2] > AppConstant::NUMERIC_ZERO && ($now < $line['enddate'] || $exceptions[$items[$i]][1] > $now + $latePassHrs*AppConstant::NUMERIC_SIXTY*AppConstant::NUMERIC_SIXTY)) {
                        $canundolatepass = true;
                    }
                    if($latePassHrs != 0){
                        $latepasscnt = round(($exceptions[$items[$i]][1] - $line['enddate'])/($latePassHrs*AppConstant::NUMERIC_THREE_SIX_ZERO_ZERO));
                    }

                    $line['startdate'] = $exceptions[$items[$i]][0];
                    $line['enddate'] = $exceptions[$items[$i]][1];
                }

                if ($line['startdate'] == AppConstant::NUMERIC_ZERO) {
                    $startDate = _('Always');
                } else {
                    $startDate = AppUtility::formatdate($line['startdate']);
                }
                if ($line['enddate']== AppConstant::ALWAYS_TIME) {
                    $endDate =  _('Always');
                } else {
                    $endDate = AppUtility::formatdate($line['enddate']);
                }
                if ($line['reviewdate'] == AppConstant::ALWAYS_TIME) {
                    $reviewdate = 'Always';
                } else {
                    $reviewdate = AppUtility::formatdate($line['reviewdate']);
                }
                $nothidden = true;
                if ($line['reqscore'] > AppConstant::NUMERIC_ZERO && $line['reqscoreaid'] > AppConstant::NUMERIC_ZERO && !$viewAll && $line['enddate']>$now
                    && (!($exceptions[$items[$i]]) || $exceptions[$items[$i]][3] == AppConstant::NUMERIC_ZERO))
                {
                    $bestScore = $line['reqscoreaid'];
                    $result = AssessmentSession::getBestScore($bestScore, $userId);
                    if ($result == AppConstant::NUMERIC_ZERO) {
                        $nothidden = false;
                    } else {
                        $scores = explode(';',$result[0]['bestscores']);
                        if (round(getpts($scores[0]),1)+.02<$line['reqscore']) {
                            $nothidden = false;
                        }
                    }
                }
                if (!$haveCalcedViewedAssess && $line['avail'] > AppConstant::NUMERIC_ZERO && $line['enddate'] < $now && $line['allowlate'] > AppConstant::NUMERIC_TEN)
                {
                    $haveCalcedViewedAssess = true;
                    $viewedAssess = array();
                    $type = 'gbviewasid';
                    $r2 = ContentTrack::getTypeId($courseId, $userId,$type);
                    foreach($r2 as $key => $r) {
                        $viewedAssess[] = $r['typeid'];
                    }
                }

                if ($line['avail'] == AppConstant::NUMERIC_ONE && $line['startdate'] < $now && $line['enddate'] > $now && $nothidden)
                {
                    /*
                     * regular show
                     */
                    ShowItemCourse::beginitem($canEdit,$items[$i]); //echo "<div class=item>\n"; 2 assessmene
                    if (($hideIcons&1) == AppConstant::NUMERIC_ZERO) {
                        if ($graphicalIcons) { ?>
                            <img alt="assess" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/iconAssessment.png"/>
                        <?php } else {
                            echo "<div class= icon" . ShowItemCourse::makecolor2($line['startdate'],$line['enddate'],$now) . ";\">"; ?>
                             <img alt="assess" class="floatleft item-icon-alignment" src="<?php echo AppUtility::getAssetURL() ?>img/iconAssessment.png"/>
                           <?php echo"</div>";
                        }
                    }
                    if (substr($line['deffeedback'],0,8) == 'Practice') {
                        $endname = _('Available until');
                    } else {
                        $endname = _('Due');
                    }
                    $line['timelimit'] = abs($line['timelimit']);
                    if ($line['timelimit'] > AppConstant::NUMERIC_ZERO) {
                        if ($line['timelimit'] > AppConstant::NUMERIC_THREE_SIX_ZERO_ZERO) {
                            $tlhrs = floor($line['timelimit']/AppConstant::NUMERIC_THREE_SIX_ZERO_ZERO);
                            $tlrem = $line['timelimit'] % AppConstant::NUMERIC_THREE_SIX_ZERO_ZERO;
                            $tlmin = floor($tlrem/AppConstant::NUMERIC_SIXTY);
                            $tlsec = $tlrem % AppConstant::NUMERIC_SIXTY;
                            $tlwrds = "$tlhrs " . _('hour');
                            if ($tlhrs > AppConstant::NUMERIC_ONE)
                            {
                                $tlwrds .= "s";
                            }
                            if ($tlmin > AppConstant::NUMERIC_ZERO) {
                                $tlwrds .= ", $tlmin " . _('minute');
                            }
                            if ($tlmin > AppConstant::NUMERIC_ONE) {
                                $tlwrds .= "s";
                            }
                            if ($tlsec > AppConstant::NUMERIC_ZERO) {
                                $tlwrds .= ", $tlsec " . _('second');
                            }
                            if ($tlsec > AppConstant::NUMERIC_ONE) {
                                $tlwrds .= "s";
                            }
                        } else if ($line['timelimit'] > AppConstant::NUMERIC_SIXTY) {
                            $tlmin = floor($line['timelimit']/AppConstant::NUMERIC_SIXTY);
                            $tlsec = $line['timelimit'] % AppConstant::NUMERIC_SIXTY;
                            $tlwrds = "$tlmin " . _('minute');
                            if ($tlmin > AppConstant::NUMERIC_ONE) {
                                $tlwrds .= "s";
                            }
                            if ($tlsec > AppConstant::NUMERIC_ZERO) {
                                $tlwrds .= ", $tlsec " . _('second');
                            }
                            if ($tlsec > AppConstant::NUMERIC_ONE) {
                                $tlwrds .= "s";
                            }
                        } else {
                            $tlwrds = $line['timelimit'] . _(' second(s)');
                        }
                    } else {
                        $tlwrds = '';
                    }

                    echo "<div class=title><b>"; ?>
                    <a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/show-test?id='.$typeid . '&cid=' . $courseId) ?>"
                    <?php if ($tlwrds != '') {
                        echo "onclick='return confirm(\"", sprintf(_('This assessment has a time limit of %s.  Click OK to start or continue working on the assessment.'), $tlwrds), "\")' ";
                    }
                    // This is not encoded because for some reason it messes up the html tags instead of purly encodeing them
                    echo ">". $line['name']."</a></b>";
                    if ($line['enddate'] != AppConstant::ALWAYS_TIME) {
                        echo "<BR> $endname $endDate \n";
                    }
                    if ($canEdit && !$studview  && !$isStudent) {
                        echo '<span class="instronly">';
                        if ($line['allowlate'] > AppConstant::NUMERIC_ZERO) {
                            echo ' <span onmouseover="tipshow(this,\'', _('LatePasses Allowed'), '\')" onmouseout="tipout()">', _('LP'), '</span> |';
                        } ?>
                        <div class=" floatright common-setting">
                            <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown"
                               href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button"
                                                               src="<?php echo AppUtility::getAssetURL() ?>img/courseSettingItem.png"/></a>
                            <ul class="select1 dropdown-menu selected-options pull-right">
                                <li><a class="question" href="<?php echo AppUtility::getURLFromHome('question', 'question/add-questions?cid='.$courseId.'&aid='.$typeid); ?>"><?php AppUtility::t('Questions'); ?></a></li>
                                <li><a class="modify"  href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/add-assessment?id='.$typeid . '&cid=' . $courseId . '&block=0') ?>"><?php AppUtility::t('Settings'); ?></a>
                                </li>
                                <li><a id="delete" href="javascript:deleteItem('<?php echo $typeid; ?>','<?php echo AppConstant::ASSESSMENT ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Delete'); ?></a>
                                </li>
                                <li><a id="copy" href="javascript:copyItem('<?php echo $items[$i]; ?>','<?php echo AppConstant::ASSESSMENT ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Copy'); ?></a>
                                </li>
                                <li><a id="grades" href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/item-analysis?cid='.$courseId.'&asid=average&aid='.$typeid); ?>"><?php AppUtility::t('Grades'); ?></a>
                                </li>

                                <?php if (isset($hasStats['a' . $typeid])) { ?>
                                    <li><a id="stats" href="<?php echo AppUtility::getURLFromHome('instructor', 'instructor/content-stats?cid='.$courseId.'&type=A&id='.$typeid)?>"><?php AppUtility::t('Stats'); ?></a></li>
                                <?php } ?>
                            </ul>
                        </div>

                    <?php } else if (($line['allowlate']%AppConstant::NUMERIC_TEN == AppConstant::NUMERIC_ONE || $line['allowlate']%AppConstant::NUMERIC_TEN-1>$latepasscnt) && $latePasses > AppConstant::NUMERIC_ZERO) {
                        echo " <a href=\"#\">", _('Use LatePass'), "</a>";?>
                        <a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/late-pass?id=' . $typeid.'&cid=' .$courseId. '&confirm=true') ?>" class="confirmation-late-pass" id="<?php echo $line['id']?>"> Use Late Pass</a>
<?php                        if ($canundolatepass) {
                           echo " | <a href=\"#\">", _('Un-use LatePass'), "</a>"; ?>
                            <a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/late-pass?id=' . $typeid.'&cid=' .$courseId. '&undo=true') ?>" class="confirmation-late-pass" id="<?php echo $line['id']?>">Un-use LatePass</a>
                      <?php  }
                    } else if ($line['allowlate'] > AppConstant::NUMERIC_ZERO && ($sessionData['stuview'])) {
                        echo _(' LatePass Allowed');
                    } else if ($line['allowlate'] > AppConstant::NUMERIC_ZERO && $canundolatepass) {
                        echo " <a href=\"#\">", _('Un-use LatePass'), "</a>"; ?>
                        <a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/late-pass?id=' .$typeid.'&cid=' .$courseId. '&undo=true') ?>" class="confirmation-late-pass" id="<?php echo $line['id']?>">Un-use LatePass</a>
                 <?php   }
                    echo filter("</div><div class=itemsum>{$line['summary']}</div>\n");
                    ShowItemCourse::enditem($canEdit); //echo "</div>\n";
                } else if($line['avail'] == AppConstant::NUMERIC_ONE && $line['enddate']<$now && $line['reviewdate']>$now) { //review show // && $nothidden
                    ShowItemCourse::beginitem($canEdit,$items[$i]); //echo "<div class=item>\n"; 3 assessment
                    if (($hideIcons&1) == AppConstant::NUMERIC_ZERO) {
                        if ($graphicalIcons) { ?>
                            <img alt="assess" class="floatleft item-icon-alignment"
                                 src="<?php echo AppUtility::getAssetURL() ?>img/iconAssessment.png"/>
                      <?php  } else { ?>
                            <img alt="assess" class="floatleft item-icon-alignment"
                                 src="<?php echo AppUtility::getAssetURL() ?>img/iconAssessment.png"/>
                      <?php }
                    }
                    echo "<div class=title><b>";?>

                    <a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/show-test?id='.$typeid . '&cid=' . $courseId) ?>"
                    <?php echo ">".HTML::encode($line['name'])."</a></b><BR> ", sprintf(_('Past Due Date of %s.  Showing as Review'), $endDate).'.';
                    if ($line['reviewdate'] != AppConstant::ALWAYS_TIME) {
                        echo " ", _('until'), " $reviewdate \n";
                    }
                    if ($line['allowlate'] > AppConstant::NUMERIC_TEN && ($now - $line['enddate'])<$latePassHrs*AppConstant::NUMERIC_THREE_SIX_ZERO_ZERO && !in_array($typeid,$viewedAssess) && $latePasses > AppConstant::NUMERIC_ZERO && !($sessionData['stuview'])) {
                       echo " <a href=\"#\">", _('Use LatePass'), "</a>"; ?>
                        <a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/late-pass?id=' .$typeid.'&cid=' .$courseId. '&confirm=true') ?>" class="confirmation-late-pass" id="<?php echo $line['id']?>"> Use Late Pass</a>
                    <?php }
                    if ($canEdit && !$studview && !$isStudent) {
                        echo '<span class="instronly">';
                        if ($line['allowlate'] > AppConstant::NUMERIC_ZERO) {
                            echo ' <span onmouseover="tipshow(this,\'', _('LatePasses Allowed'), '\')" onmouseout="tipout()">LP</span> |';
                        } ?>
                        <div class="floatright common-setting">
                            <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown"
                               href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button"
                                                               src="<?php echo AppUtility::getAssetURL() ?>img/courseSettingItem.png"/></a>
                            <ul class="select1 dropdown-menu selected-options pull-right">
                                <li><a class="question" href="<?php echo AppUtility::getURLFromHome('question', 'question/add-questions?cid='.$courseId.'&aid='.$typeid); ?>"><?php AppUtility::t('Questions'); ?></a></li>
                                <li><a class="modify"
                                       href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/add-assessment?id='.$typeid . '&cid=' . $courseId . '&block=0') ?>"><?php AppUtility::t('Settings'); ?></a>
                                </li>
                                <li><a id="delete"
                                       href="javascript:deleteItem('<?php echo $typeid; ?>','<?php echo AppConstant::ASSESSMENT ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Delete'); ?></a>
                                </li>
                                <li><a id="copy"
                                       href="javascript:copyItem('<?php echo $items[$i]; ?>','<?php echo AppConstant::ASSESSMENT ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Copy'); ?></a>
                                </li>
                                <li><a id="grades"
                                       href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/item-analysis?cid='.$courseId.'&asid=average&aid='.$typeid); ?>"><?php AppUtility::t('Grades'); ?></a>
                                </li>

                                <?php if (isset($hasStats['a' . $typeid])) { ?>
                                    <li><a id="stats" href="<?php echo AppUtility::getURLFromHome('instructor', 'instructor/content-stats?cid='.$courseId.'&type=A&id='.$typeid)?>"><?php AppUtility::t('Stats'); ?></a></li>
                                <?php } ?>
                            </ul>
                        </div>

                   <?php } else if (($sessionData['stuview']) && $line['allowlate'] > AppConstant::NUMERIC_TEN && ($now - $line['enddate'])<$latePassHrs*AppConstant::NUMERIC_THREE_SIX_ZERO_ZERO) {
                        echo _(' LatePass Allowed');
                    }
                    echo filter("<br/><i>" . _('This assessment is in review mode - no scores will be saved') . "</i></div><div class=itemsum>{$line['summary']}</div>\n");
                    ShowItemCourse::enditem($canEdit); //echo "</div>\n";
                } else if ($viewAll && !$studview && !$isStudent) { //not avail to stu
                    if ($line['avail'] == AppConstant::NUMERIC_ZERO) {
                        $show = _('Hidden');
                    } else {
                        $show = sprintf(_('Available %1$s until %2$s'), $startDate, $endDate);
                        if ($line['reviewdate'] > AppConstant::NUMERIC_ZERO && $line['enddate'] != AppConstant::ALWAYS_TIME) {
                            $show .= sprintf(', Review until %s', $reviewdate);
                        }
                    }
                    ShowItemCourse::beginitem($canEdit,$items[$i]); //echo "<div class=item>\n"; 4 assessment
                    if (($hideIcons&1) == AppConstant::NUMERIC_ZERO) {
                        if ($graphicalIcons) { ?>
                            <img alt="assess" class="floatleft item-icon-alignment"
                                 src="<?php echo AppUtility::getAssetURL() ?>img/iconAssessment.png"/>
                       <?php } else {
                            echo "<div class=icon\">?</div>";
                        }
                    }
                    echo "<div class=title><i>"; ?>
                    <a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/show-test?id='.$typeid . '&cid=' . $courseId) ?>"><?php echo HTML::encode($line['name']) ?></a>
                  <?php  echo '<span class="instrdates">';
                    echo "<br/><i>$show</i>\n";
                    echo '</span>';
                    if ($canEdit && !$studview && !$isStudent) {
                        echo '<span class="instronly">';
                        if ($line['allowlate'] > AppConstant::NUMERIC_ZERO) {
                            echo ' <span onmouseover="tipshow(this,\'', _('LatePasses Allowed'), '\')" onmouseout="tipout()">', _('LP'), '</span> |';
                        }?>

                        <div class="floatright common-setting">
                            <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown"
                               href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button"
                                                               src="<?php echo AppUtility::getAssetURL() ?>img/courseSettingItem.png"/></a>
                            <ul class="select1 dropdown-menu selected-options pull-right">
                                <li><a class="question" href="<?php echo AppUtility::getURLFromHome('question', 'question/add-questions?cid='.$courseId.'&aid='.$typeid); ?>"><?php AppUtility::t('Questions'); ?></a></li>
                                <li><a class="modify"
                                       href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/add-assessment?id='.$typeid . '&cid=' . $courseId . '&block=0') ?>"><?php AppUtility::t('Settings'); ?></a>
                                </li>
                                <li><a id="delete"
                                       href="javascript:deleteItem('<?php echo $typeid; ?>','<?php echo AppConstant::ASSESSMENT ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Delete'); ?></a>
                                </li>
                                <li><a id="copy"
                                       href="javascript:copyItem('<?php echo $items[$i]; ?>','<?php echo AppConstant::ASSESSMENT ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Copy'); ?></a>
                                </li>
                                <li><a id="grades"
                                       href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/item-analysis?cid='.$courseId.'&asid=average&aid='.$typeid); ?>"><?php AppUtility::t('Grades'); ?></a>
                                </li>

                                <?php if (isset($hasStats['a' . $typeid])) { ?>
                                    <li><a id="stats" href="<?php echo AppUtility::getURLFromHome('instructor', 'instructor/content-stats?cid='.$courseId.'&type=A&id='.$typeid)?>"><?php AppUtility::t('Stats'); ?></a></li>
                                <?php } ?>
                            </ul>
                        </div>
                   <?php }
                    echo filter("</div><div class=itemsum>{$line['summary']}</div>\n");
                    ShowItemCourse::enditem($canEdit);
                }
            } else if ($line['itemtype'] == "InlineText") {
                /**
                 * Inline text
                 */
                $typeid = $line['typeid'];
                $line = InlineText::getById($typeid);

                $isvideo = (preg_match_all('/youtu/',$line['text'],$matches) > AppConstant::NUMERIC_ONE) && ($line['isplaylist'] > AppConstant::NUMERIC_ZERO);
                if ($isvideo) {
                    $json = array();
                    preg_match_all('/<a[^>]*(youtube\.com|youtu\.be)(.*?)"[^>]*?>(.*?)<\/a>/',$line['text'],$matches, PREG_SET_ORDER);
                    foreach ($matches as $k=>$m) {
                        if ($m[1]=='youtube.com') {
                            $p = explode('v=',$m[2]);
                            $p2 = preg_split('/[#&]/',$p[1]);
                        } else if ($m[1]=='youtu.be') {
                            $p2 = preg_split('/[#&?]/',substr($m[2],1));
                        }
                        $vidid = $p2[0];
                        if (preg_match('/.*[^r]t=((\d+)m)?((\d+)s)?.*/',$m[2],$tm)) {
                            $start = ($tm[2]?$tm[2]*AppConstant::NUMERIC_SIXTY:0) + ($tm[4]?$tm[4]*1:0);
                        } else if (preg_match('/start=(\d+)/',$m[2],$tm)) {
                            $start = $tm[1];
                        } else {
                            $start = AppConstant::NUMERIC_ZERO;
                        }
                        if (preg_match('/end=(\d+)/',$m[2],$tm)) {
                            $end = $tm[1];
                        } else {
                            $end = AppConstant::NUMERIC_ZERO;
                        }
                        $json[] = '{"name":"'.str_replace('"','\\"',$m[3]).'", "vidid":"'.str_replace('"','\\"',$vidid).'", "start":'.$start.', "end":'.$end.'}';
                        $line['text'] = str_replace($m[0],'<a href="#" onclick="playliststart('.$typeid.','.$k.');return false;">'.$m[3].'</a>',$line['text']);
                    }

                    $playlist = '<div class="playlistbar" id="playlistbar'.$typeid.'"><div class="vidtracksA"></div> <span> Playlist</span> ';
                    $playlist .= '<div class="vidplay" style="margin-left:1em;cursor:pointer" onclick="playliststart('.$typeid.',0)"></div>';
                    $playlist .= '<div class="vidrewI" style="display:none;"></div><div class="vidff" style="display:none;margin-right:1em;"></div> ';
                    $playlist .= '<span class="playlisttitle"></span></div>';
                    $playlist .= '<div class="playlistwrap" id="playlistwrap'.$typeid.'">';
                    $playlist .= '<div class="playlisttext">'.$line['text'].'</div><div class="playlistvid"></div></div>';
                    $playlist .= '<script type="text/javascript">playlist['.$typeid.'] = ['.implode(',',$json).'];</script>';
                    $line['text'] = $playlist;

                } else if (strpos($line['text'],'<p ') !== AppConstant::NUMERIC_ZERO && strpos($line['text'],'<ul ') !== AppConstant::NUMERIC_ZERO && strpos($line['text'],'<ol ') !== 0) {
                    $line['text'] = '<p>'.$line['text'].'</p>';
                    if (preg_match('/^\s*<p[^>]*>\s*<\/p>\s*$/',$line['text'])) {
                        $line['text'] = '';
                    }
                }
                if (($isStudent) && !($sessionData['stuview'])) {
                    $rec = "data-base=\"inlinetext-$typeid\" ";
                    $line['text'] = str_replace('<a ','<a '.$rec, $line['text']);
                }
                if ($line['startdate'] == AppConstant::NUMERIC_ZERO) {
                    $startDate = _('Always');
                } else {
                    $startDate = AppUtility::formatdate($line['startdate']);
                }
                if ($line['enddate'] == AppConstant::ALWAYS_TIME) {
                    $endDate = _('Always');
                } else {
                    $endDate = AppUtility::formatdate($line['enddate']);
                }
                if ($line['avail'] == AppConstant::NUMERIC_TWO || ($line['startdate'] < $now && $line['enddate'] > $now && $line['avail'] == AppConstant::NUMERIC_ONE)) {
                    if ($line['avail'] == AppConstant::NUMERIC_TWO) {
                        $show = _('Showing Always ');
                        $color = '#0f0';
                    } else {
                        $show = _('Showing until:') . " $endDate";
                        $color = ShowItemCourse::makecolor2($line['startdate'],$line['enddate'],$now);
                    }
                    ShowItemCourse::beginitem($canEdit,$items[$i]);// echo "<div class=item>\n"; 5 inline text
                    echo '<a name="inline'.$typeid.'"></a>';
                    if ($line['title']!='##hidden##') {
                        if (($hideIcons&2) == AppConstant::NUMERIC_ZERO) {
                            if ($graphicalIcons) { ?>
                                <img alt="assess" class="floatleft item-icon-alignment"
                                     src="<?php echo AppUtility::getAssetURL() ?>img/inlineText.png"/>
                           <?php } else {?>
                                <img alt="assess" class="floatleft item-icon-alignment"
                                     src="<?php echo AppUtility::getAssetURL() ?>img/inlineText.png"/>
                           <?php }
                        }
                        echo "<div class=title> <b>{$line['title']}</b>\n";
                        if ($viewAll) {
                            echo '<span class="instrdates">';
                            echo "<br/>$show ";
                            echo '</span>';
                        }
                        if ($canEdit && !$studview && !$isStudent) { ?>
                            <div class=" floatright common-setting">
                                <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown"
                                   href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button"
                                                                   src="<?php echo AppUtility::getAssetURL() ?>img/courseSettingItem.png"/></a>
                                <ul class="select1 dropdown-menu selected-options pull-right">
                                    <li><a class="modify" href="<?php echo AppUtility::getURLFromHome('course', 'course/modify-inline-text?cid=' . $courseId . '&id=' . $typeid) ?>"><?php AppUtility::t('Modify'); ?></a>
                                    </li>
                                    <li><a id="delete" href="javascript:deleteItem('<?php echo $typeid; ?>','<?php echo AppConstant::INLINE_TEXT ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Delete'); ?></a>
                                    </li>
                                    <li><a id="copy" href="javascript:copyItem('<?php echo $items[$i]; ?>','<?php echo AppConstant::INLINE_TEXT ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Copy'); ?></a>
                                    </li>
                                </ul>
                            </div>
                        <?php }
                        echo "</div>";
                    } else {
                        if ($viewAll) {
                            echo '<span class="instrdates">';
                            echo "<br/>$show ";
                            echo '</span>';
                        }
                        if ($canEdit && !$studview && !$isStudent) { ?>
                            <div class=" floatright common-setting">
                                <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown"
                                   href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button"
                                                                   src="<?php echo AppUtility::getAssetURL() ?>img/courseSettingItem.png"/></a>
                                <ul class="select1 dropdown-menu selected-options pull-right">
                                    <li><a class="modify"
                                           href="<?php echo AppUtility::getURLFromHome('course', 'course/modify-inline-text?cid=' . $courseId . '&id=' . $typeid) ?>"><?php AppUtility::t('Modify'); ?></a>
                                    </li>
                                    <li><a id="delete"
                                           href="javascript:deleteItem('<?php echo $typeid; ?>','<?php echo AppConstant::INLINE_TEXT ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Delete'); ?></a>
                                    </li>
                                    <li><a id="copy"
                                           href="javascript:copyItem('<?php echo $items[$i]; ?>','<?php echo AppConstant::INLINE_TEXT ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Copy'); ?></a>
                                    </li>
                                </ul>
                            </div>
                       <?php }
                    }
                    echo filter("<div class=itemsum>{$line['text']}\n");
                    $result = InstrFiles::getFileName($typeid);
                    if (count($result) > AppConstant::NUMERIC_ZERO) {
                        echo '<ul class="fileattachlist">';
                        $filenames = array();
                        $filedescr = array();
                        foreach($result as $key => $row) {
                            $filenames[$row['id']] = $row['filename'];
                            $filedescr[$row['id']] = $row['description'];
                        }
                        foreach (explode(',',$line['fileorder']) as $fid)
                        {
                            echo "<li><a href=\"".filehandler::getcoursefileurl($filenames[$fid])."\" target=\"_blank\">{$filedescr[$fid]}</a></li>";
                        }

                        echo "</ul>";
                    }
                    echo "</div>";
                    ShowItemCourse::enditem($canEdit); //echo "</div>\n";
                } else if ($viewAll && !$studview) {
                    if ($line['avail'] == AppConstant::NUMERIC_ZERO) {
                        $show = _('Hidden');
                    } else {
                        $show = sprintf(_('Showing %1$s until %2$s'), $startDate, $endDate);
                    }
                    ShowItemCourse::beginitem($canEdit,$items[$i]); //echo "<div class=item>\n"; 6 inline text
                    if ($line['title']!='##hidden##') {
                        if ($graphicalIcons) { ?>
                            <img alt="assess" class="floatleft item-icon-alignment"
                                 src="<?php echo AppUtility::getAssetURL() ?>img/inlineText.png"/>
                        <?php } else { ?>
                            <img alt="assess" class="floatleft item-icon-alignment"
                                 src="<?php echo AppUtility::getAssetURL() ?>img/inlineText.png"/>
                     <?php   }
                        echo "<div class=title><i> <b>{$line['title']}</b> </i><br/>";
                    } else {
                        echo "<div class=title>";
                    }
                    echo '<span class="instrdates">';
                    echo "<i>$show</i> ";
                    echo '</span>';
                    if ($canEdit && !$studview && !$isStudent) { ?>
                        <div class=" floatright common-setting">
                            <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown"
                               href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button"
                                                               src="<?php echo AppUtility::getAssetURL() ?>img/courseSettingItem.png"/></a>
                            <ul class="select1 dropdown-menu selected-options pull-right">
                                <li><a class="modify"
                                       href="<?php echo AppUtility::getURLFromHome('course', 'course/modify-inline-text?cid=' . $courseId . '&id=' . $typeid) ?>"><?php AppUtility::t('Modify'); ?></a>
                                </li>
                                <li><a id="delete"
                                       href="javascript:deleteItem('<?php echo $typeid; ?>','<?php echo AppConstant::INLINE_TEXT ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Delete'); ?></a>
                                </li>
                                <li><a id="copy"
                                       href="javascript:copyItem('<?php echo $items[$i]; ?>','<?php echo AppConstant::INLINE_TEXT ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Copy'); ?></a>
                                </li>
                            </ul>
                        </div>
                   <?php }
                    echo filter("</div><div class=itemsum>{$line['text']}\n");
                    $result = InstrFiles::getFileName($typeid);
                    if ($result > AppConstant::NUMERIC_ZERO)
                    {
                        echo '<ul class="fileattachlist">';
                        $filenames = array();
                        $filedescr = array();
                        foreach($result as $key => $row){
                            $filenames[$row['id']] = $row['filename'];
                            $filedescr[$row['id']] = $row['description'];
                        }
                        foreach (explode(',',$line['fileorder']) as $fid) {
                            echo "<li><a href=\"".filehandler::getcoursefileurl($filenames[$fid])."\" target=\"_blank\">{$filedescr[$fid]}</a></li>";

                        }

                        echo "</ul>";
                    }
                    echo "</div>";
                    ShowItemCourse::enditem($canEdit); //echo "</div>\n";
                }
            } else if ($line['itemtype']=="LinkedText") {
                $typeid = $line['typeid'];
                $line = LinkedText::getById($typeid);

                if (strpos($line['summary'],'<p ') !== AppConstant::NUMERIC_ZERO && strpos($line['summary'],'<ul ') !== AppConstant::NUMERIC_ZERO && strpos($line['summary'],'<ol ') !== AppConstant::NUMERIC_ZERO) {
                    $line['summary'] = '<p>'.$line['summary'].'</p>';
                    if (preg_match('/^\s*<p[^>]*>\s*<\/p>\s*$/',$line['summary'])) {
                        $line['summary'] = '';
                    }
                }
                if (($isStudent) && !($sessionData['stuview'])) {
                    $rec = "data-base=\"linkedsum-$typeid\" ";
                    $line['summary'] = str_replace('<a ','<a '.$rec, $line['summary']);
                }
                if ($line['startdate'] == AppConstant::NUMERIC_ZERO) {
                    $startDate = _('Always');
                } else {
                    $startDate = AppUtility::formatdate($line['startdate']);
                }
                if ($line['enddate'] == AppConstant::ALWAYS_TIME) {
                    $endDate = _('Always');
                } else {
                    $endDate = AppUtility::formatdate($line['enddate']);
                }
                if ($line['target'] == AppConstant::NUMERIC_ONE) {
                    $target = 'target="_blank"';
                } else {
                    $target = '';
                }
                if ((substr($line['text'],0,4)=="http") && (strpos(trim($line['text'])," ")===false)) { //is a web link

                    $alink = trim($line['text']);
                    $icon = 'web';
                } else if (substr(strip_tags($line['text']),0,5)=="file:") {
                    $filename = substr(strip_tags($line['text']),5);
                    $alink = filehandler::getcoursefileurl($filename);
                    $ext = substr($filename,strrpos($filename,'.')+ AppConstant::NUMERIC_ONE);
                    switch($ext) {
                        case 'xls': $icon = 'xls'; break;
                        case 'pdf': $icon = 'pdf'; break;
                        case 'html': $icon = 'html'; break;
                        case 'ppt': $icon = 'ppt'; break;
                        case 'zip': $icon = 'zip'; break;
                        case 'png':
                        case 'gif':
                        case 'jpg':
                        case 'bmp': $icon = 'image'; break;
                        case 'mp3':
                        case 'wav':
                        case 'wma': $icon = 'sound'; break;
                        case 'swf':
                        case 'avi':
                        case 'mpg': $icon = 'video'; break;
                        case 'nb': $icon = 'mathnb'; break;
                        case 'mws':
                        case 'mw': $icon = 'maple'; break;
                        default : $icon = 'doc'; break;
                    }
                    if (!($itemIcons[$icon])) {
                        $icon = 'doc';
                    }

                } else {
                    if ($isPublic) {
                        $alink = "show-linked-text-public?cid=$courseId&id=$typeid";
                    } else {
                        $alink = "show-linked-text?cid=$courseId&id=$typeid";
                    }
                    $icon = 'html';
                }
                if (($isStudent) && !($sessionData['stuview'])) {
                    $rec = "data-base-site=\"linkedlink-$typeid\"";
                } else {
                    $rec = '';
                }

                if ($line['avail'] == AppConstant::NUMERIC_TWO || ($line['avail'] == AppConstant::NUMERIC_ONE && $line['startdate'] < $now && $line['enddate'] > $now)) {
                    if ($line['avail'] == AppConstant::NUMERIC_TWO) {
                        $show = _('Showing Always ');
                        $color = '#0f0';
                    } else {
                        $show = _('Showing until:') . " $endDate";
                        $color = ShowItemCourse::makecolor2($line['startdate'],$line['enddate'],$now);
                    }
                    ShowItemCourse::beginitem($canEdit,$items[$i]); //echo "<div class=item>\n"; 7 link
                    if (($hideIcons&4) == AppConstant::NUMERIC_ZERO) {
                        if ($graphicalIcons) { ?>
                            <img alt="link to web" class="floatleft"
                                 src="<?php echo AppUtility::getHomeURL() ?>img/web.png"/>
                        <?php } else { ?>
                            <img alt="link to web" class="floatleft"
                                 src="<?php echo AppUtility::getHomeURL() ?>img/web.png"/>
                     <?php   }
                    }
                    echo "<div class=title>";
                    if (($readLinkedItems[$typeid])) {
                        echo '<b class="readitem">';
                    } else {
                        echo '<b>';
                    }
                    echo "<a href=\"$alink\" $rec $target>{$line['title']}</a></b>\n";
                    if ($viewAll) {
                        echo '<span class="instrdates">';
                        echo "<br/>$show ";
                        echo '</span>';
                    }
                    if ($canEdit && !$studview && !$isStudent) { ?>

                        <div class="floatright common-setting">
                            <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown"
                               href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button"
                                                               src="<?php echo AppUtility::getAssetURL() ?>img/courseSettingItem.png"/></a>
                            <ul class="select1 dropdown-menu selected-options pull-right">
                                <li><a class="modify" href="<?php echo AppUtility::getURLFromHome('course', 'course/add-link?cid=' . $courseId . '&id=' . $typeid); ?>"><?php AppUtility::t('Modify'); ?></a>
                                </li>
                                <li><a id="delete" href="javascript:deleteItem('<?php echo $typeid; ?>','<?php echo AppConstant::LINK ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Delete'); ?></a>
                                </li>
                                <li><a id="copy" href="javascript:copyItem('<?php echo $items[$i]; ?>','<?php echo AppConstant::LINK ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Copy'); ?></a>
                                </li>
                                <?php
                                if (isset($hasStats['l'.$typeid])) {
                                    ?>
                                    <li><a id="stats" href="<?php echo AppUtility::getURLFromHome('instructor', 'instructor/content-stats?cid='.$courseId.'&type=L&id='.$typeid)?>"><?php AppUtility::t('Stats'); ?></a></li>
                                <?php } ?>
                            </ul>
                        </div>
                   <?php }
                    echo filter("</div><div class=itemsum>{$line['summary']}</div>\n");
                    ShowItemCourse::enditem($canEdit); //echo "</div>\n";
                } else if ($viewAll && !$studview) {
                    if ($line['avail'] == AppConstant::NUMERIC_ZERO) {
                        $show = _('Hidden');
                    } else {
                        $show = sprintf(_('Showing %1$s until %2$s'), $startDate, $endDate);
                    }
                    ShowItemCourse::beginitem($canEdit,$items[$i]); //echo "<div class=item>\n"; 8 link
                    if ($graphicalIcons) { ?>
                        <img alt="link to web" class="floatleft"
                             src="<?php echo AppUtility::getHomeURL() ?>img/web.png"/>
                    <?php } else { ?>
                        <img alt="link to web" class="floatleft"
                             src="<?php echo AppUtility::getHomeURL() ?>img/web.png"/>
                   <?php }
                    echo "<div class=title>";
                    echo "<i> <b><a href=\"$alink\" onclick=\"$rec\" $target>{$line['title']}</a></b> </i>";
                    echo '<span class="instrdates">';
                    echo "<br/><i>$show</i> ";
                    echo '</span>';
                    if ($canEdit && !$studview && !$isStudent) { ?>
                        <div class=" floatright common-setting">
                            <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown"
                               href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button"
                                                               src="<?php echo AppUtility::getAssetURL() ?>img/courseSettingItem.png"/></a>
                            <ul class="select1 dropdown-menu selected-options pull-right">
                                <li><a class="modify" href="<?php echo AppUtility::getURLFromHome('course', 'course/add-link?cid=' . $courseId . '&id=' . $typeid); ?>"><?php AppUtility::t('Modify'); ?></a>
                                </li>
                                <li><a id="delete" href="javascript:deleteItem('<?php echo $typeid; ?>','<?php echo AppConstant::LINK ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Delete'); ?></a>
                                </li>
                                <li><a id="copy" href="javascript:copyItem('<?php echo $items[$i]; ?>','<?php echo AppConstant::LINK ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Copy'); ?></a>
                                </li>
                                <?php
                                if (isset($hasStats['l'.$typeid])) {
                                    ?>
                                    <li><a id="stats" href="<?php echo AppUtility::getURLFromHome('instructor', 'instructor/content-stats?cid='.$courseId.'&type=L&id='.$typeid)?>"><?php AppUtility::t('Stats'); ?></a></li>
                                <?php } ?>
                            </ul>
                        </div>
                   <?php }
                    echo filter("</div><div class=itemsum>{$line['summary']}</div>\n");
                    ShowItemCourse::enditem($canEdit); // echo "</div>\n";
                }
            } else if ($line['itemtype']=="Forum") {
                if ($isPublic) { continue;}
                $typeid = $line['typeid'];
                $line = Forums::getById($typeid);

                if (strpos($line['description'],'<p ') !== AppConstant::NUMERIC_ZERO) {
                    $line['description'] = '<p>'.HtmlPurifier::process($line['description']).'</p>';
                    if (preg_match('/^\s*<p[^>]*>\s*<\/p>\s*$/',$line['description'])) {
                        $line['description'] = '';
                    }
                }
                if ($line['startdate'] == AppConstant::NUMERIC_ZERO) {
                    $startDate = _('Always');
                } else {
                    $startDate = AppUtility::formatdate($line['startdate']);
                }
                if ($line['enddate'] == AppConstant::ALWAYS_TIME) {
                    $endDate = _('Always');
                } else {
                    $endDate = AppUtility::formatdate($line['enddate']);
                }

                if ($line['avail'] == AppConstant::NUMERIC_TWO || ($line['avail']== AppConstant::NUMERIC_ONE && $line['startdate']<$now && $line['enddate']>$now)) {

                    if ($line['avail'] == AppConstant::NUMERIC_TWO) {
                        $show = _('Showing Always ');
                        $color = '#0f0';
                    } else {
                        $show = _('Showing until:') . " $endDate";
                        $color = ShowItemCourse::makecolor2($line['startdate'],$line['enddate'],$now);
                    }

                    $duedates = "";
                    if ($line['postby']>$now && $line['postby'] != AppConstant::ALWAYS_TIME) {
                        $duedates .= sprintf(_('New Threads due %s. '), AppUtility::formatdate($line['postby']));
                    }
                    if ($line['replyby']>$now && $line['replyby'] != AppConstant::ALWAYS_TIME) {
                        $duedates .= sprintf(_('Replies due %s. '), AppUtility::formatdate($line['replyby']));
                    }
                    ShowItemCourse::beginitem($canEdit,$items[$i]); //echo "<div class=item>\n"; 9 forum
                    if (($hideIcons&8) == AppConstant::NUMERIC_ZERO) {
                        if ($graphicalIcons) { ?>
                            <img alt="text item" class="floatleft item-icon-alignment"
                                 src="<?php echo AppUtility::getAssetURL() ?>img/iconForum.png"/>
                      <?php  } else { ?>
                            <img alt="text item" class="floatleft item-icon-alignment"
                                 src="<?php echo AppUtility::getAssetURL() ?>img/iconForum.png"/>
                       <?php }
                    }
                    echo "<div class=title> "; ?>
                    <b><a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/thread?cid='.$courseId.'&forumid='.$line['id']);?>"><?php echo HTML::encode($line['name'])?></a></b>
                  <?php  if (isset($newPostCnts[$line['id']]) && $newPostCnts[$line['id']] > AppConstant::NUMERIC_ZERO) { ?>
                        <a style="color:red" href="<?php echo AppUtility::getURLFromHome('forum', 'forum/thread?cid='.$courseId.'&forumid='.$line['id'],'&page=-1')?>"><?php echo sprintf(_('New Posts (%s)'),$newPostCnts[$line['id']])?></a>
                  <?php  }
                    if ($viewAll ) {
                        echo '<span class="instrdates">';
                        echo "<br/>$show ";
                        echo '</span>';
                    }
                    if ($canEdit && !$studview && !$isStudent) { ?>
                        <div class="floatright common-setting">
                            <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown"
                               href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button"
                                                               src="<?php echo AppUtility::getAssetURL() ?>img/courseSettingItem.png"/></a>
                            <ul class="select1 dropdown-menu selected-options pull-right">
                                <li><a class="modify" href="<?php echo AppUtility::getURLFromHome('forum', 'forum/add-forum?cid=' . $courseId.'&fromForum=1&id='.$typeid); ?>"><?php AppUtility::t('Modify'); ?></a>
                                </li>
                                <li><a id="delete" href="javascript:deleteItem('<?php echo $typeid; ?>','<?php echo AppConstant::FORUM ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Delete'); ?></a>
                                </li>
                                <li><a id="copy" href="javascript:copyItem('<?php echo $items[$i]; ?>','<?php echo AppConstant::FORUM ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Copy'); ?></a>
                                </li>
                                <?php if (isset($hasStats['f' . $typeid])) { ?>
                                    <li><a id="stats" href="<?php echo AppUtility::getURLFromHome('instructor', 'instructor/content-stats?cid='.$courseId.'&type=F&id='.$typeid)?>"><?php AppUtility::t('Stats'); ?></a></li>
                                <?php } ?>
                            </ul>
                        </div>
                    <?php }
                    if ($duedates!='') {echo "<br/>$duedates";}
                    echo filter("</div><div class=itemsum>". HtmlPurifier::process($line['description']) ."</div>\n");
                    ShowItemCourse::enditem($canEdit); //echo "</div>\n";

                } else if ($viewAll && !$studview) {

                    if ($line['avail'] == AppConstant::NUMERIC_ZERO) {
                        $show = _('Hidden');
                    } else {
                        $show = sprintf(_('Showing %1$s until %2$s'), $startDate, $endDate);
                    }
                    ShowItemCourse::beginitem($canEdit,$items[$i]); //echo "<div class=item>\n"; 10 forum
                    if ($graphicalIcons) { ?>
                        <img alt="text item" class="floatleft item-icon-alignment"
                             src="<?php echo AppUtility::getAssetURL() ?>img/iconForum.png"/>
                    <?php } else { ?>
                        <img alt="text item" class="floatleft item-icon-alignment"
                             src="<?php echo AppUtility::getAssetURL() ?>img/iconForum.png"/>
                    <?php }
                    echo "<div class=title><i>"; ?>
                      <!-- the fallowing $line['name'] is the one for the forum names  -->
                    <b><a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/thread?cid='.$courseId.'&forumid='.$line['id'])?>"><?php echo HTML::encode($line['name'])?></a></b></i>
                   <?php if (($newPostCnts[$line['id']]) && $newPostCnts[$line['id']] > AppConstant::NUMERIC_ZERO) { ?>
                        <a style="color:red" href="<?php echo AppUtility::getURLFromHome('forum', 'forum/thread?cid='.$courseId.'&forumid='.$line['id'],'&page=-1')?>"><?php sprintf(_('New Posts (%s)'),$newPostCnts[$line['id']])?></a>
                   <?php }
                    echo '<span class="instrdates">';
                    echo "<br/><i>$show </i>";
                    echo '</span>';

                    if ($canEdit && !$studview && !$isStudent) { ?>
                        <div class=" floatright common-setting">
                            <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown"
                               href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button"
                                                               src="<?php echo AppUtility::getAssetURL() ?>img/courseSettingItem.png"/></a>
                            <ul class="select1 dropdown-menu selected-options pull-right">
                                <li><a class="modify" href="<?php echo AppUtility::getURLFromHome('forum', 'forum/add-forum?cid=' . $courseId.'&fromForum=1&id='.$typeid); ?>"><?php AppUtility::t('Modify'); ?></a>
                                </li>
                                <li><a id="delete" href="javascript:deleteItem('<?php echo $typeid; ?>','<?php echo AppConstant::FORUM ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Delete'); ?></a>
                                </li>
                                <li><a id="copy" href="javascript:copyItem('<?php echo $items[$i]; ?>','<?php echo AppConstant::FORUM ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Copy'); ?></a>
                                </li>
                                <?php if (isset($hasStats['f' . $typeid])) { ?>
                                    <li><a id="stats" href="<?php echo AppUtility::getURLFromHome('instructor', 'instructor/content-stats?cid='.$courseId.'&type=F&id='.$typeid)?>"><?php AppUtility::t('Stats'); ?></a></li>
                                <?php } ?>
                            </ul>
                        </div>
                   <?php }
                    echo filter("</div><div class=itemsum>".HtmlPurifier::process($line['description']) ."</div>\n");
                    ShowItemCourse::enditem($canEdit); //echo "</div>\n";
                }
            } else if ($line['itemtype'] == "Wiki") {
                // if ($isPublic) { continueo;}
                $typeid = $line['typeid'];
                $line = Wiki::getById($typeid);
                if ($isPublic && $line['groupsetid'] > AppConstant::NUMERIC_ZERO)
                {
                    continue;
                }
                if (strpos($line['description'],'<p ') !== AppConstant::NUMERIC_ZERO) {
                    $line['description'] = '<p>'. HtmlPurifier::process($line['description']).'</p>';
                    if (preg_match('/^\s*<p[^>]*>\s*<\/p>\s*$/',$line['description'])) {
                        $line['description'] = '';
                    }
                }
                if ($line['startdate'] == AppConstant::NUMERIC_ZERO) {
                    $startDate = _('Always');
                } else {
                    $startDate = AppUtility::formatdate($line['startdate']);
                }
                if ($line['enddate'] == AppConstant::ALWAYS_TIME) {
                    $endDate = _('Always');
                } else {
                    $endDate = AppUtility::formatdate($line['enddate']);
                }
                $hasnew = false;
                if ($viewAll || $line['avail'] == AppConstant::NUMERIC_TWO || ($line['avail'] == AppConstant::NUMERIC_ONE && $line['startdate'] < $now && $line['enddate'] > $now)) {
                    if ($line['groupsetid'] > AppConstant::NUMERIC_ZERO && !$canEdit) {
                        $groupSetId = $line['groupsetid'];
                        $result = Stugroups::getStuGrpId($userId, $groupSetId);
                        if (count($result) > AppConstant::NUMERIC_ZERO) {
                            $wikigroupid = $result[0]['id'];
                        } else {
                            $wikigroupid = AppConstant::NUMERIC_ZERO;
                        }
                    }

                    $wikilastviews = array();
                    $result = WikiView::getByUserIdAndWikiId($userId, $typeid);

                    foreach($result as $key => $row){
                        $wikilastviews[$row['stugroupid']] = $row['lastview'];
                    }
                    $groupSetId = $line['groupsetid'];
                    $result = WikiRevision::getMaxTime($typeid, $groupSetId, $canEdit, $wikigroupid);
                    foreach($result as $key => $row){
                        if (!($wikilastviews[$row['stugroupid']]) || $wikilastviews[$row['stugroupid']] < $row['MAX(time)']) {
                            $hasnew = true;
                            break;
                        }
                    }
                }
                if ($line['avail'] == AppConstant::NUMERIC_TWO || ($line['avail'] == AppConstant::NUMERIC_ONE && $line['startdate'] < $now && $line['enddate'] > $now)) {
                    if ($line['avail'] == AppConstant::NUMERIC_TWO) {
                        $show = _('Showing Always ');
                        $color = '#0f0';
                    } else {
                        $show = _('Showing until:') . " $endDate";
                        $color = ShowItemCourse::makecolor2($line['startdate'],$line['enddate'],$now);
                    }
                    $duedates = "";
                    if ($line['editbydate'] > $now && $line['editbydate'] != AppConstant::ALWAYS_TIME) {
                        $duedates .= sprintf(_('Edits due by %s. '), AppUtility::formatdate($line['editbydate']));
                    }
                    ShowItemCourse::beginitem($canEdit,$items[$i]); //echo "<div class=item>\n"; 11 wiki
                    if (($hideIcons&8) == AppConstant::NUMERIC_ZERO) {
                        if ($graphicalIcons) { ?>
                            <img alt="assess" class="floatleft item-icon-alignment"
                                 src="<?php echo AppUtility::getAssetURL() ?>img/iconWiki.png"/>
                        <?php } else { ?>
                            <img alt="assess" class="floatleft item-icon-alignment"
                                 src="<?php echo AppUtility::getAssetURL() ?>img/iconWiki.png"/>
                        <?php }
                    }
                    echo "<div class=title> ";

                        if($groupSetId){
                            $groupId=Stugroups::getByGrpSetId($groupSetId);
                            if(count($groupId)==AppConstant::NUMERIC_ZERO){
                                $defaultgroupid=AppConstant::NUMERIC_ZERO;
                            }
                            else
                            $defaultgroupid=$groupId[AppConstant::NUMERIC_ZERO]['id'];
                        }else
                            $defaultgroupid=AppConstant::NUMERIC_ZERO;

                    if ($isPublic) { ?>
                         <a href="<?php echo AppUtility::getURLFromHome('wiki', 'wiki/view-wiki-public?courseId='.$courseId.'&wikiId='.$typeid.'&grp='.$defaultgroupid)?>"><?php echo HTML::encode($line['name'])?></a>
                   <?php } else {
                        if (($isStudent) && !($sessionData['stuview'])) {
                            $rec = "data-base=\"wiki-$typeid\"";
                        } else {
                            $rec = '';
                        }?>

<!--                        echo "<b><a href=\"#\" $rec>{$line['name']}</a></b>\n";-->
                           <a href="<?php echo AppUtility::getURLFromHome('wiki', 'wiki/show-wiki?courseId='.$courseId.'&wikiId='.$typeid.'&grp='.$defaultgroupid)?>"><?php echo HTML::encode($line['name'])?></a>
                      <?php  if ($hasnew) {
                            echo " <span style=\"color:red\">", _('New Revisions'), "</span>";
                        }
                    }
                    if ($viewAll) {
                        echo '<span class="instrdates">';
                        echo "<br/>$show ";
                        echo '</span>';
                    }
                    if ($canEdit && !$studview && !$isStudent) {
                        echo '<span class="instronly">';
                        $itemsTypeId = $items['typeid'];
                        ?>
                        <div class=" floatright common-setting">
                            <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown"
                               href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button"
                                                               src="<?php echo AppUtility::getAssetURL() ?>img/courseSettingItem.png"/></a>
                            <ul class="select1 dropdown-menu selected-options pull-right">
                                <li><a class="modify" href="<?php echo AppUtility::getURLFromHome('wiki', 'wiki/add-wiki?id=' . $typeid . '&cid=' . $courseId) ?>"><?php AppUtility::t('Modify'); ?></a>
                                </li>
                                <li><a id="delete" href="javascript:deleteItem('<?php echo $typeid; ?>','<?php echo AppConstant::WIKI ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Delete'); ?></a>
                                </li>
                                <li><a id="copy" href="javascript:copyItem('<?php echo $items[$i]; ?>','<?php echo AppConstant::WIKI ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Copy'); ?></a>
                                </li>
                                <?php
                                if (isset($hasStats['w'.$typeid])) {
                                    ?>
                                    <li><a id="stats" href="<?php echo AppUtility::getURLFromHome('instructor', 'instructor/content-stats?cid='.$courseId.'&type=W&id='.$typeid)?>"><?php AppUtility::t('Stats'); ?></a></li>
                                <?php } ?>
                            </ul>
                        </div>
                       <?php echo '</span>';
                     }
                    if ($duedates!='') {echo "<br/>$duedates";}
                    echo filter("</div><div class=itemsum>". HtmlPurifier::process($line['description']) ."</div>\n");
                    ShowItemCourse::enditem($canEdit); //echo "</div>\n";
                } else if ($viewAll && !$studview) {
                    if ($line['avail'] == AppConstant::NUMERIC_ZERO) {
                        $show = _('Hidden');
                    } else {
                        $show = sprintf(_('Showing %1$s until %2$s'), $startDate, $endDate);
                    }
                    ShowItemCourse::beginitem($canEdit,$items[$i]); //echo "<div class=item>\n"; 12 wiki
                    if ($graphicalIcons) { ?>
                        <img alt="assess" class="floatleft item-icon-alignment"
                             src="<?php echo AppUtility::getAssetURL() ?>img/iconWiki.png"/>
                   <?php } else { ?>
                        <img alt="assess" class="floatleft item-icon-alignment"
                             src="<?php echo AppUtility::getAssetURL() ?>img/iconWiki.png"/>
                   <?php }
                    echo "<div class=title><i> <b>"; ?>
<!--                    <a href=\"#\">{$line['name']}</a></b></i> ";-->
                    <a href="<?php echo AppUtility::getURLFromHome('wiki', 'wiki/show-wiki?courseId='.$courseId.'&wikiId='.$typeid)?>"><?php echo HTML::encode($line['name'])?></a>
                    <?php if ($hasnew) {
                        echo " <span style=\"color:red\">", _('New Revisions'), "</span>";
                    }
                    echo '<span class="instrdates">';
                    echo "<br/><i>$show </i>";
                    echo '</span>';
                    if ($canEdit && !$studview && !$isStudent) {
                        echo '<span class="instronly">';
                        ?>
                        <div class=" floatright common-setting">
                            <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown"
                               href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button"
                                                               src="<?php echo AppUtility::getAssetURL() ?>img/courseSettingItem.png"/></a>
                            <ul class="select1 dropdown-menu selected-options pull-right">
                                <li><a class="modify"
                                       href="<?php echo AppUtility::getURLFromHome('wiki', 'wiki/add-wiki?id=' . $typeid . '&cid=' . $courseId) ?>"><?php AppUtility::t('Modify'); ?></a>
                                </li>
                                <li><a id="delete"
                                       href="javascript:deleteItem('<?php echo $typeid; ?>','<?php echo AppConstant::WIKI ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Delete'); ?></a>
                                </li>
                                <li><a id="copy"
                                       href="javascript:copyItem('<?php echo $items[$i]; ?>','<?php echo AppConstant::WIKI ?>','<?php echo $parent; ?>','<?php echo $courseId; ?>')"><?php AppUtility::t('Copy'); ?></a>
                                </li>
                                <?php
                                if (isset($hasStats['w'.$typeid])) {
                                    ?>
                                    <li><a id="stats" href="<?php echo AppUtility::getURLFromHome('instructor', 'instructor/content-stats?cid='.$courseId.'&type=W&id='.$typeid)?>"><?php AppUtility::t('Stats'); ?></a></li>
                                <?php } ?>
                            </ul>
                        </div>
                       <?php echo '</span>';
                    }
                    echo filter("</div><div class=itemsum>". HtmlPurifier::process($line['description']) ."</div>\n");
                    ShowItemCourse::enditem($canEdit); //echo "</div>\n";

                }
            }
        }
        if (count($items) > AppConstant::NUMERIC_ZERO) {
            if ($canEdit && !$studview && !$isStudent)
            {
                echo ShowItemCourse::generateAddItem($parent,'b',$studview);
            }
        }
    }

    public static function generateAddItem($blk,$tb,$studview)
    {
        ?>
        <?php if(!$studview){?>
        <div class="row add-item" onclick="getAddItem('<?php echo $blk?>', '<?php echo $tb?>')">
            <div class="col-md-1 col-sm-1 plus-icon">
                <input type="hidden" id="block" value="<?php echo $blk ?>">
                <input type="hidden" id="tb-value" value="<?php echo $tb ?>">
                <img class="add-item-icon" src="<?php echo AppUtility::getAssetURL()?>img/addItem.png">
            </div>
            <div class="col-md-2 col-sm-2 add-item-text">
                <p><a><?php AppUtility::t('Add An Item...');?></a></p>
            </div>
        </div>
     <?php }   ?>

    <?php }
    public static function beginitem($canEdit,$aname=AppConstant::NUMERIC_ZERO) {
        if ($canEdit) {
            echo '<div class="inactivewrapper" onmouseover="this.className=\'activewrapper\'" onmouseout="this.className=\'inactivewrapper\'">';
        }
        echo "<div class=items>\n"; // 13 function
        if ($aname != AppConstant::NUMERIC_ZERO) {
            echo "<a name=\"$aname\"></a>";
        }
    }
    public static function enditem($canEdit) {
        echo '<div class="clear"></div>';
        echo "</div>\n";
        if ($canEdit) {
            echo '</div>'; //itemwrapper
        }

    }
    public static function makeColor($etime,$now) {
        if (!$GLOBALS['colorshift']) {
            return "#ff0";
        }
        //$now = time();
        if ($etime<$now) {
            $color = "#ccc";
        } else if ($etime-$now < AppConstant::NUMERIC_SIX_ZERO_FIVE_EIGHT_ZERO_ZERO) {  //due within a week
            $color = "#f".dechex(floor(AppConstant::SIXTEEN*($etime-$now)/AppConstant::NUMERIC_SIX_ZERO_FIVE_EIGHT_ZERO_ONE))."0";
        } else if ($etime-$now < AppConstant::NUMERIC_ONE_TWO_ONE_ONE_SIX_ZERO_ZERO) { //due within two weeks
            $color = "#". dechex(floor(AppConstant::SIXTEEN*(AppConstant::NUMERIC_ONE -($etime-$now-AppConstant::NUMERIC_SIX_ZERO_FIVE_EIGHT_ZERO_ZERO)/AppConstant::NUMERIC_SIX_ZERO_FIVE_EIGHT_ZERO_ONE))) . "f0";
        } else {
            $color = "#0f0";
        }
        return $color;
    }

    public static function makeColor2($stime,$etime,$now) {
        if (!$GLOBALS['colorshift']) {
            return "#ff0";
        }
        if ($etime == AppConstant::ALWAYS_TIME && $now >= $stime) {
            return '#0f0';
        } else if ($stime == AppConstant::NUMERIC_ZERO) {
            return ShowItemCourse::makecolor($etime,$now);
        }
        if ($etime==$stime) {
            return '#ccc';
        }
        $r = ($etime-$now)/($etime-$stime);  //0 = etime, 1=stime; 0:#f00, 1:#0f0, .5:#ff0
        if ($etime<$now || $stime>$now) {
            $color = '#ccc';
        } else if ($r<.5) {
            $color = '#f'.dechex(floor(32*$r)).'0';
        } else if ($r < AppConstant::NUMERIC_ONE) {
            $color = '#'.dechex(floor(32*(1-$r))).'f0';
        } else {
            $color = '#0f0';
        }
        return $color;
    }

    public static function generatemoveselect($num,$count,$blk,$blocklist) {
        global $toolset;
        if (($toolset&4) == AppConstant::NUMERIC_FOUR) {return '';}
        $num = $num + AppConstant::NUMERIC_ONE;  //adjust indexing
        $html = "<select class=\"mvsel\" id=\"$blk-$num\" onchange=\"moveitem($num,'$blk')\">\n";
        for ($i = 1; $i <= $count; $i++) {
            $html .= "<option value=\"$i\" ";
            if ($i==$num) { $html .= "SELECTED";}
            $html .= ">$i</option>\n";
        }
        for ($i=0; $i<count($blocklist); $i++) {
            if ($num!=$blocklist[$i]) {
                $html .= "<option value=\"B-{$blocklist[$i]}\">" . sprintf(_('Into %s'),$blocklist[$i]) . "</option>\n";
            }
        }
        if ($blk!='0') {
            $html .= '<option value="O-' . $blk . '">' . _('Out of Block') . '</option>';
        }
        $html .= "</select>\n";
        return $html;
    }


public static function makeTopMenu($studview) {

    global $teacherId,$courseId,$imasroot,$previewshift, $topBar, $msgSet, $newMsgs, $quickView, $courseNewFlag,$useviewButtons,$newPostsCnt;
    if ($useviewButtons && (($teacherId) || $previewshift > -1)) {

        echo '<div id="viewbuttoncont">View: ';
        echo "<a href=\"course?cid=$courseId&quickview=off&teachview=1\" ";
        if ($previewshift == -1 && $quickView != 'on') {
            echo 'class="buttonactive buttoncurveleft"';
        } else {
            echo 'class="buttoninactive buttoncurveleft"';
        }
        echo '>', _('Instructor'), '</a>';
        echo "<a href=\"course?cid=$courseId&quickview=off&stuview=0\" ";
        if ($previewshift>-1 && $quickView != 'on') {
            echo 'class="buttonactive"';
        } else {
            echo 'class="buttoninactive"';
        }
        echo '>', _('Student'), '</a>';
        echo "<a href=\"course?cid=$courseId&quickview=on&teachview=1\" ";
        if ($previewshift==-1 && $quickView == 'on') {
            echo 'class="buttonactive buttoncurveright"';
        } else {
            echo 'class="buttoninactive buttoncurveright"';
        }
        echo '>', _('Quick Rearrange'), '</a>';
        echo '</div>';
        //echo '<br class="clear"/>';


    } else {
        $useviewButtons = false;
    }
    if (($teacherId) && $quickView == 'on') {
        echo '<div class="cpmid">';
        if (!$useviewButtons) {
            echo _('Quick View.'), " <a href=\"course?cid=$courseId&quickview=off\">", _('Back to regular view'), "</a>. ";
        }
        if (isset($CFG['CPS']['miniicons'])) {
            echo _('Use icons to drag-and-drop order.'),' ',_('Click the icon next to a block to expand or collapse it. Click an item title to edit it in place.'), '  <input type="button" id="recchg" disabled="disabled" value="', _('Save Changes'), '" onclick="submitChanges()"/>';

        } else {
            echo _('Use colored boxes to drag-and-drop order.'),' ',_('Click the B next to a block to expand or collapse it. Click an item title to edit it in place.'), '  <input type="button" id="recchg" disabled="disabled" value="', _('Save Changes'), '" onclick="submitChanges()"/>';
        }
        echo '<span id="submitnotice" style="color:red;"></span>';
        echo '<div class="clear"></div>';
        echo '</div>';

    }
    if (($courseNewFlag&1) == AppConstant::NUMERIC_ONE) {
        $gbnewflag = ' <span class="red">' . _('New') . '</span>';
    } else {
        $gbnewflag = '';
    }


    if ($previewshift>-1 || $studview) {
        ?>

    <script type="text/javascript">
        function changeshift() {
            var shift = document.getElementById("pshift").value;
            var CourseId= <?php echo $courseId?>;
            var toopen = "<?php echo AppUtility::getURLFromHome('course','course/course?cid='.$courseId)?>&stuview="+shift;
            window.location = toopen;
        }
    </script>

<?php } ?>



<?php    /*if (($teacherId) && count($topBar[1]) > AppConstant::NUMERIC_ZERO && $topBar[2] == AppConstant::NUMERIC_ZERO) {

    } else*/ if (((count($topBar[0]) > AppConstant::NUMERIC_ZERO && $topBar[2] == AppConstant::NUMERIC_ZERO) || $studview)) {
        if ($topBar[2] == AppConstant::NUMERIC_ZERO) {

            if ($previewshift>-1 && count($topBar[0]) > AppConstant::NUMERIC_ZERO) { echo '<br/>';}
        }
    if ($previewshift>-1 || $studview) {
            echo _('Showing student view. Show view:'), ' <select id="pshift" onchange="changeshift()">';
            echo '<option value="0" ';
            if ($previewshift==0) {echo "selected=1";}
            echo '>', _('Now'), '</option>';
            echo '<option value="3600" ';
            if ($previewshift==3600) {echo "selected=1";}
            echo '>', _('1 hour from now'), '</option>';
            echo '<option value="14400" ';
            if ($previewshift==14400) {echo "selected=1";}
            echo '>', _('4 hours from now'), '</option>';
            echo '<option value="86400" ';
            if ($previewshift==86400) {echo "selected=1";}
            echo '>', _('1 day from now'), '</option>';
            echo '<option value="604800" ';
            if ($previewshift==604800) {echo "selected=1";}
            echo '>', _('1 week from now'), '</option>';
            echo '</select>';
            echo " <a href=\"course?cid=$courseId&teachview=1\">", _('Back to instructor view'), "</a>";
        }
        echo '<div class=clear></div>';
    }
  }
}
