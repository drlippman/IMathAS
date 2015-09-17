<?php
namespace app\components;
use \yii\base\Component;

class CourseItemsUtility extends Component
{
    /*
     *ASSESSMENT
     */

    public $cnt = AppConstant::NUMERIC_ZERO;

    public static function AddAssessment($assessment, $item, $course, $currentTime, $parent, $canEdit, $viewAll)
    {
        $assessment = $item[key($item)];
        $notHidden = $item['nothidden'];
        $hasstats = true;
        if (strpos($assessment['summary'], '<p ') !== AppConstant::NUMERIC_ZERO && strpos($assessment['summary'], '<ul') !== AppConstant::NUMERIC_ZERO && strpos($assessment['summary'], '<ol') !== AppConstant::NUMERIC_ZERO) {
            $assessment['summary'] = '<p>' . $assessment['summary'] . '</p>';
            if (preg_match('/^\s*<p[^>]*>\s*<\/p>\s*$/', $assessment['summary'])) {
                $assessment['summary'] = '';
            }
        }
        if ($assessment['startdate'] == AppConstant::NUMERIC_ZERO) {
            $startDate = _('Always');
        } else {
            $startDate = AppUtility::formatdate($assessment['startdate']);
        }
        if ($assessment['enddate'] == AppConstant::ALWAYS_TIME) {
            $endDate = _('Always');
        } else {
            $endDate = AppUtility::formatdate($assessment['enddate']);
        }
        if ($assessment['reviewdate'] == AppConstant::ALWAYS_TIME) {
            $reviewDate = _('Always');
        } else {
            $reviewDate = AppUtility::formatdate($assessment['reviewdate']);
        }
        if ($assessment->avail == AppConstant::NUMERIC_ONE && $assessment->enddate > $currentTime && $assessment->startdate < $currentTime && $notHidden) {
            if (substr($assessment->deffeedback, AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_EIGHT) == 'Practice') {
                $endName = 'Available until';
            } else {
                $endName = 'Due';
            }
            if ($assessment->enddate != AppConstant::ALWAYS_TIME) {
                $message = "$endName $endDate";
            }
        } else if ($assessment->avail == AppConstant::NUMERIC_ONE && $assessment->enddate < $currentTime && $assessment->reviewdate > $currentTime) {
            $message = sprintf(AppConstant::PAST_DUE_DATE, $endDate);
            if ($assessment->reviewdate != AppConstant::ALWAYS_TIME) {
                $message .= " until $reviewDate. ";
            }
        } else if ($viewAll) {
            if ($assessment->avail == AppConstant::NUMERIC_ZERO) {
                $message = "Hidden";
            } else {
                $message = sprintf(AppConstant::AVAILABLE_UNTIL, $startDate, $endDate);
                if ($assessment['reviewdate'] > AppConstant::NUMERIC_ZERO && $assessment['enddate'] != AppConstant::ALWAYS_TIME) {
                    $message .= sprintf(_(', Review until %s'), $reviewDate);
                }
            }
        }
        ?>
        <div class="item">
            <img alt="assess" class="floatleft item-icon-alignment"
                 src="<?php echo AppUtility::getAssetURL() ?>img/iconAssessment.png"/>

            <div class="title">
                <b>
                    <a href="<?php echo AppUtility::getURLFromHome('question', 'question/show-test?id=' . $assessment->id . '&cid=' . $course->id) ?>"
                       class="confirmation-require assessment-link"
                       id="<?php echo $assessment->id ?>"><?php echo ucfirst($assessment->name) ?></a>
                </b><br>

                <div class="floatright">
                    <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown"
                       href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button"
                                                       src="<?php echo AppUtility::getAssetURL() ?>img/courseSettingItem.png"/></a>
                    <ul class=" select1 dropdown-menu selected-options">
                        <li><a class="question" href="<?php echo AppUtility::getURLFromHome('question', 'question/add-questions?cid='.$course->id.'&aid='.$assessment->id); ?>"><?php AppUtility::t('Questions'); ?></a></li>
                        <li><a class="modify"
                               href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/add-assessment?id='.$assessment->id . '&cid=' . $course->id . '&block=0') ?>"><?php AppUtility::t('Setting'); ?></a>
                        </li>
                        <li><a id="delete"
                               href="javascript:deleteItem('<?php echo $assessment->id; ?>','<?php echo AppConstant::ASSESSMENT ?>','<?php echo $parent; ?>','<?php echo $course->id; ?>')"><?php AppUtility::t('Delete'); ?></a>
                        </li>
                        <li><a id="copy"
                               href="javascript:copyItem('<?php echo $item['assessment']['id']; ?>','<?php echo AppConstant::ASSESSMENT ?>','<?php echo $parent; ?>','<?php echo $course->id; ?>')"><?php AppUtility::t('Copy'); ?></a>
                        </li>
                        <li><a id="grades"
                               href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/item-analysis?cid='.$course->id.'&asid=average&aid='.$assessment->id); ?>"><?php AppUtility::t('Grades'); ?></a>
                        </li>
                        <?php if (isset($hasstats['a' . $assessment->id])) { ?>
                            <li><a id="stats" href="#"><?php AppUtility::t('Stats'); ?></a></li>
                        <?php } ?>
                    </ul>
                </div>

                <input type="hidden" class="confirmation-require" id="time-limit<?php echo $assessment->id ?>"
                       name="urlTimeLimit" value="<?php echo $assessment->timelimit; ?>">
                <?php echo $message; ?>
                <?php if ($assessment->allowlate != AppConstant::NUMERIC_ZERO) { ?>
                    <span title="Late Passes Allowed">LP</span>
                <?php
                } ?>
            </div>
            <div class="itemsum">
                <p><?php echo $assessment->summary ?></p>
            </div>
        </div>
    <?php
    }

    /*
     * FORUM
     */
    public static function AddForum($item, $course, $currentTime, $parent)
    {
        $forum = $item[key($item)];
        if ($forum->avail == AppConstant::NUMERIC_TWO || $forum->startdate < $currentTime && $forum->enddate > $currentTime && $forum->avail == AppConstant::NUMERIC_ONE) {
            ?>

            <div class="item">
                <!--Hide title and icon-->
                <?php if ($forum->name != '##hidden##') {
                $endDate = AppUtility::formatDate($forum->enddate);?>
                <img alt="text item" class="floatleft item-icon-alignment"
                     src="<?php echo AppUtility::getAssetURL() ?>img/iconForum.png"/>

                <div class="title">
                    <b><a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/thread?cid=' . $forum->courseid . '&forumid=' . $forum->id) ?>">
                            <?php echo $forum->name ?></a></b>

                    <div class="floatright">
                        <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown"
                           href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button"
                                                           src="<?php echo AppUtility::getAssetURL() ?>img/courseSettingItem.png"/></a>
                        <ul class=" select1 dropdown-menu selected-options">
                            <li><a class="modify"
                                   href="<?php echo AppUtility::getURLFromHome('site', 'work-in-progress?cid=' . $course->id); ?>"><?php AppUtility::t('Modify'); ?></a>
                            </li>
                            <li><a id="delete"
                                   href="javascript:deleteItem('<?php echo $forum->id; ?>','<?php echo AppConstant::FORUM ?>','<?php echo $parent; ?>','<?php echo $course->id; ?>')"><?php AppUtility::t('Delete'); ?></a>
                            </li>
                            <li><a id="copy"
                                   href="javascript:copyItem('<?php echo $item['forum']['id']; ?>','<?php echo AppConstant::FORUM ?>','<?php echo $parent; ?>','<?php echo $course->id; ?>')"><?php AppUtility::t('Copy'); ?></a>
                            </li>
                        </ul>
                    </div>
                    <br>
                </div>
                <div class="itemsum">
                    <?php } ?>

                    <?php if ($forum->avail == AppConstant::NUMERIC_TWO) { ?>
                        <?php echo "Showing Always"; ?>
                    <?php
                    } else {
                        if ($forum->startdate == AppConstant::NUMERIC_ZERO && $forum->enddate == AppConstant::ALWAYS_TIME || $forum->startdate != AppConstant::NUMERIC_ZERO && $forum->enddate == AppConstant::ALWAYS_TIME) {
                            echo "Showing until: Always"; ?>
                        <?php
                        } else {
                            echo "Showing until: " . $endDate;?>
                        <?php
                        }
                    }
                    if ($forum->postby > $currentTime && $forum->postby != AppConstant::ALWAYS_TIME) {
                        echo('New Threads due '), AppUtility::formatdate($forum->postby) . ".";
                    }
                    if ($forum->replyby > $currentTime && $forum->replyby != AppConstant::ALWAYS_TIME) {
                        echo(' Replies due '), AppUtility::formatdate($forum->replyby) . ".";
                    }
                    ?>
                    <p><?php echo $forum->description ?></p>
                </div>
            </div>

        <?php } elseif ($forum->avail == AppConstant::NUMERIC_ZERO) { ?>
            <div class="item">
                <!--Hide title and icon-->
                <?php if ($forum->name != '##hidden##') {
                $endDate = AppUtility::formatDate($forum->enddate);?>
                <img alt="assess" class="floatleft faded item-icon-alignment"
                     src="<?php echo AppUtility::getAssetURL() ?>img/iconForum.png"/>

                <div class="title">
                    <b><a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/thread?cid=' . $forum->courseid . '&forumid=' . $forum->id) ?>">
                            <?php echo $forum->name ?></a></b>

                    <div class="floatright">
                        <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown"
                           href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button"
                                                           src="<?php echo AppUtility::getAssetURL() ?>img/courseSettingItem.png"/></a>
                        <ul class=" select1 dropdown-menu selected-options">
                            <li><a class="modify"
                                   href="<?php echo AppUtility::getURLFromHome('site', 'work-in-progress?cid=' . $course->id); ?>"><?php AppUtility::t('Modify'); ?></a>
                            </li>
                            <li><a id="delete"
                                   href="javascript:deleteItem('<?php echo $forum->id; ?>','<?php echo AppConstant::FORUM ?>','<?php echo $parent; ?>','<?php echo $course->id; ?>')"><?php AppUtility::t('Delete'); ?></a>
                            </li>
                            <li><a id="copy"
                                   href="javascript:copyItem('<?php echo $item['forum']['id']; ?>','<?php echo AppConstant::FORUM ?>','<?php echo $parent; ?>','<?php echo $course->id; ?>')"><?php AppUtility::t('Copy'); ?></a>
                            </li>
                        </ul>
                    </div>
                    <br>
                </div>
                <div class="itemsum"><p>
                        <?php
                        echo 'Hidden'; ?>
                        <?php
                        } ?>

                    <p><?php echo $forum->description ?></p>
                </div>
            </div>
        <?php } else { ?>
            <div class="item">
                <?php if ($forum->name != '##hidden##') {
                $endDate = AppUtility::formatDate($forum->enddate);?>
                <img alt="assess" class="floatleft faded item-icon-alignment"
                     src="<?php echo AppUtility::getAssetURL() ?>img/iconForum.png"/>

                <div class="title">
                    <b><a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/thread?cid=' . $forum->courseid . '&forumid=' . $forum->id) ?>">
                            <?php echo $forum->name ?></a></b>

                    <div class="floatright">
                        <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown"
                           href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button"
                                                           src="<?php echo AppUtility::getAssetURL() ?>img/courseSettingItem.png"/></a>
                        <ul class=" select1 dropdown-menu selected-options">
                            <li><a class="modify"
                                   href="<?php echo AppUtility::getURLFromHome('site', 'work-in-progress?cid=' . $course->id); ?>"><?php AppUtility::t('Modify'); ?></a>
                            </li>
                            <li><a id="delete"
                                   href="javascript:deleteItem('<?php echo $forum->id; ?>','<?php echo AppConstant::FORUM ?>','<?php echo $parent; ?>','<?php echo $course->id; ?>')"><?php AppUtility::t('Delete'); ?></a>
                            </li>
                            <li><a id="copy"
                                   href="javascript:copyItem('<?php echo $item['forum']['id']; ?>','<?php echo AppConstant::FORUM ?>','<?php echo $parent; ?>','<?php echo $course->id; ?>')"><?php AppUtility::t('Copy'); ?></a>
                            </li>
                        </ul>
                    </div>
                    <br>
                </div>
                <div class="itemsum"><p>
                        <?php
                        }
                        $startDate = AppUtility::formatDate($forum->startdate);
                        $endDate = AppUtility::formatDate($forum->enddate);
                        echo "Showing " . $startDate . " until " . $endDate; ?>
                </div>

            </div>
        <?php } ?>

    <?php
    }

    /*
     * WIKI
     */
    public static function AddWiki($item, $course, $parent, $currentTime)
    {
        $wikis = $item[key($item)]; ?>
        <?php $endDateOfWiki = AppUtility::formatDate($wikis['enddate'], 'm/d/y');
        $startDateOfWiki = AppUtility::formatDate($wikis['startdate'], 'm/d/y');
        ?>
        <?php if ($wikis->avail == AppConstant::NUMERIC_ZERO) { ?>

        <div class="item">
            <img alt="assess" class="floatleft item-icon-alignment"
                 src="<?php echo AppUtility::getAssetURL() ?>img/iconWiki.png"/>

            <div class="title">
                <b><a href="<?php echo AppUtility::getURLFromHome('wiki', 'wiki/show-wiki?courseId=' . $wikis->courseid . '&wikiId=' . $wikis->id) ?>">
                        <?php echo ucfirst($wikis->name) ?></a></b>

                <div class="floatright">
                    <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown"
                       href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button"
                                                       src="<?php echo AppUtility::getAssetURL() ?>img/courseSettingItem.png"/></a>
                    <ul class=" select1 dropdown-menu selected-options">
                        <li><a class="modify"
                               href="<?php echo AppUtility::getURLFromHome('wiki', 'wiki/add-wiki?id=' . $wikis->id . '&courseId=' . $course->id) ?>"><?php AppUtility::t('Modify'); ?></a>
                        </li>
                        <li><a id="delete"
                               href="javascript:deleteItem('<?php echo $wikis->id; ?>','<?php echo AppConstant::WIKI ?>','<?php echo $parent; ?>','<?php echo $course->id; ?>')"><?php AppUtility::t('Delete'); ?></a>
                        </li>
                        <li><a id="copy"
                               href="javascript:copyItem('<?php echo $item['wiki']['id']; ?>','<?php echo AppConstant::WIKI ?>','<?php echo $parent; ?>','<?php echo $course->id; ?>')"><?php AppUtility::t('Copy'); ?></a>
                        </li>
                    </ul>
                </div>

                <br><span>Hidden</span>

            </div>
            <div class="itemsum">
                <p>

                <p>&nbsp;<?php echo $wikis->description ?></p></p>
            </div>
            <div class="clear"></div>
        </div>
    <?php } elseif ($wikis->avail == AppConstant::NUMERIC_ONE) { ?>
        <div class="item">
            <img alt="assess" class="floatleft item-icon-alignment"
                 src="<?php echo AppUtility::getAssetURL() ?>img/iconWiki.png"/>

            <div class="title">
                <b><a href="<?php echo AppUtility::getURLFromHome('wiki', 'wiki/show-wiki?courseId=' . $wikis->courseid . '&wikiId=' . $wikis->id) ?>">
                        <?php echo ucfirst($wikis->name) ?></a></b>

                <div class="floatright">
                    <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown"
                       href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button"
                                                       src="<?php echo AppUtility::getAssetURL() ?>img/courseSettingItem.png"/></a>
                    <ul class=" select1 dropdown-menu selected-options">
                        <li><a class="modify"
                               href="<?php echo AppUtility::getURLFromHome('wiki', 'wiki/add-wiki?id=' . $wikis->id . '&courseId=' . $course->id) ?>"><?php AppUtility::t('Modify'); ?></a>
                        </li>
                        <li><a id="delete"
                               href="javascript:deleteItem('<?php echo $wikis->id; ?>','<?php echo AppConstant::WIKI ?>','<?php echo $parent; ?>','<?php echo $course->id; ?>')"><?php AppUtility::t('Delete'); ?></a>
                        </li>
                        <li><a id="copy"
                               href="javascript:copyItem('<?php echo $item['wiki']['id']; ?>','<?php echo AppConstant::WIKI ?>','<?php echo $parent; ?>','<?php echo $course->id; ?>')"><?php AppUtility::t('Copy'); ?></a>
                        </li>
                    </ul>
                </div>
                <br>
                <?php
                if ($wikis['startdate'] == AppConstant::NUMERIC_ZERO && $wikis['enddate'] <= $currentTime) {
                    echo 'Showing Always until: ' . $endDateOfWiki;
                } elseif (($wikis['startdate'] == AppConstant::NUMERIC_ZERO && $wikis['enddate'] == AppConstant::ALWAYS_TIME)) {
                    echo 'Showing until: Always';
                } elseif ($wikis['startdate'] <= $currentTime && $wikis['enddate'] == AppConstant::ALWAYS_TIME) {
                    echo 'Showing until: Always';
                } elseif ($wikis['startdate'] <= $currentTime && $wikis['enddate'] >= $currentTime) {
                    echo 'Showing Until: ' . $endDateOfWiki;
                } else {
                    echo 'Showing ' . $startDateOfWiki . ' Until ' . $endDateOfWiki;
                } ?>

                <?php if ($wikis['editbydate'] > AppConstant::NUMERIC_ONE && $wikis['editbydate'] < AppConstant::ALWAYS_TIME) { ?>
                    Edits due by <? echo $endDateOfWiki; ?>
                <?php } ?>
            </div>
            <div class="itemsum">
                <p>

                <p>&nbsp;<?php echo $wikis->description ?></p></p>
            </div>
            <div class="clear"></div>
        </div>
    <?php } else if ($wikis->avail == AppConstant::NUMERIC_TWO) { ?>
        <div class="item">
            <img alt="assess" class="floatleft item-icon-alignment"
                 src="<?php echo AppUtility::getAssetURL() ?>img/iconWiki.png"/>

            <div class="title">
                <b><a href="<?php echo AppUtility::getURLFromHome('wiki', 'wiki/show-wiki?courseId=' . $wikis->courseid . '&wikiId=' . $wikis->id) ?>">
                        <?php echo ucfirst($wikis->name) ?></a></b>

                <div class="floatright">
                    <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown"
                       href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button"
                                                       src="<?php echo AppUtility::getAssetURL() ?>img/courseSettingItem.png"/></a>
                    <ul class=" select1 dropdown-menu selected-options">
                        <li><a class="modify"
                               href="<?php echo AppUtility::getURLFromHome('wiki', 'wiki/add-wiki?id=' . $wikis->id . '&courseId=' . $course->id) ?>"><?php AppUtility::t('Modify'); ?></a>
                        </li>
                        <li><a id="delete"
                               href="javascript:deleteItem('<?php echo $wikis->id; ?>','<?php echo AppConstant::WIKI ?>','<?php echo $parent; ?>','<?php echo $course->id; ?>')"><?php AppUtility::t('Delete'); ?></a>
                        </li>
                        <li><a id="copy"
                               href="javascript:copyItem('<?php echo $item['wiki']['id']; ?>','<?php echo AppConstant::WIKI ?>','<?php echo $parent; ?>','<?php echo $course->id; ?>')"><?php AppUtility::t('Copy'); ?></a>
                        </li>
                    </ul>
                </div>
                <br><span>Showing Always</span>

                <?php if ($wikis['editbydate'] > AppConstant::NUMERIC_ONE && $wikis['editbydate'] < AppConstant::ALWAYS_TIME) { ?>
                    Edits due by <? echo $endDateOfWiki; ?>
                <?php } ?>
            </div>
            <div class="itemsum">
                <p>

                <p>&nbsp;<?php echo $wikis->description ?></p></p>
            </div>
            <div class="clear"></div>
        </div>
    <?php } ?>

    <?php
    }

    /*
     * LINK
     */
    public static function AddLink($item, $currentTime, $parent, $course)
    {
        $link = $item[key($item)]; ?>
        <?php $text = $link->text; ?>
        <?php $startDateOfLink = AppUtility::formatDate($link->startdate);
        $endDateOfLink = AppUtility::formatDate($link->enddate); ?>
        <?php if ((substr($text, AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_FOUR) == 'http') && (strpos(trim($text), " ") == false)) { ?>
        <div class="item">
            <img alt="link to web" class="floatleft"
                 src="<?php echo AppUtility::getHomeURL() ?>img/web.png"/>

            <div class="title">
                <?php if ($link->target == AppConstant::NUMERIC_ONE) { ?>
                    <b><a href="<?php echo $text ?>" target="_blank"><?php echo $link->title ?>&nbsp;<img
                                src="<?php echo AppUtility::getHomeURL() ?>img/extlink.png"/></a></b></a></b>
                <?php } else { ?>
                    <b><a href="<?php echo $text ?>"><?php echo ucfirst($link->title); ?></a></b>
                <?php } ?>
                <div class="floatright">
                    <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown"
                       href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button"
                                                       src="<?php echo AppUtility::getAssetURL() ?>img/courseSettingItem.png"/></a>
                    <ul class=" select1 dropdown-menu selected-options">
                        <li><a class="modify"
                               href="<?php echo AppUtility::getURLFromHome('course', 'course/add-link?cid=' . $course->id . '&id=' . $link->id); ?>"><?php AppUtility::t('Modify'); ?></a>
                        </li>
                        <li><a id="delete"
                               href="javascript:deleteItem('<?php echo $link->id; ?>','<?php echo AppConstant::LINK ?>','<?php echo $parent; ?>','<?php echo $course->id; ?>')"><?php AppUtility::t('Delete'); ?></a>
                        </li>
                        <li><a id="copy"
                               href="javascript:copyItem('<?php echo $item['link']['id']; ?>','<?php echo AppConstant::LINK ?>','<?php echo $parent; ?>','<?php echo $course->id; ?>')"><?php AppUtility::t('Copy'); ?></a>
                        </li>
                    </ul>
                </div>

                <?php if ($link['avail'] == AppConstant::NUMERIC_ZERO) { ?>
                    <BR>Hidden
                <?php } else if ($link['avail'] == AppConstant::NUMERIC_TWO) { ?>
                    <br>Showing Always
                <?php } else if ($link->enddate >= $currentTime && $link->startdate >= $currentTime || $link->enddate <= $currentTime && $link->startdate <= $currentTime) { ?>

                    <?php if ($link['avail'] == AppConstant::NUMERIC_ONE && $link->startdate != AppConstant::NUMERIC_ZERO) { ?>
                        <br>Showing <?php echo $startDateOfLink ?>
                        <?php if ($link->enddate == AppConstant::ALWAYS_TIME) { ?>
                            until Always
                        <? } else { ?>
                            until <?php echo $endDateOfLink ?>,
                        <?php
                        }
                    } else if ($link->startdate == AppConstant::NUMERIC_ZERO) {
                        ?>
                        <br>Showing Always until <?php echo $endDateOfLink ?>
                    <?php } ?>
                <?php } else if ($link->enddate == AppConstant::ALWAYS_TIME || $link->startdate == AppConstant::NUMERIC_ZERO) { ?>
                    <br>Showing until:
                    <?php if ($link->enddate == AppConstant::ALWAYS_TIME) { ?>
                        Always
                    <?php } else { ?>
                        <?php echo $endDateOfLink ?>
                    <?php
                    }
                } else if ($link->startdate <= $currentTime && $link->enddate >= $currentTime) {
                    ?>
                    <br> Showing until:<?php echo $endDateOfLink; ?>
                <?php } else if ($link->startdate >= $currentTime && $link->enddate <= $currentTime) { ?>
                    <br>Showing <?php echo $startDateOfLink; ?> until <?php echo $endDateOfLink; ?>
                <?php } ?>

            </div>
            <div class="itemsum">
                <p>

                <p><?php echo $link->summary ?>&nbsp;</p></p>
            </div>
            <div class="clear"></div>
        </div>
        <!--                        Link type : file-->
    <?php } elseif ((substr($link->text, AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_FIVE) == 'file:')) { ?>
        <div class="item">
            <img alt="link to doc" class="floatleft"
                 src="<?php echo AppUtility::getHomeURL() ?>img/doc.png"/>

            <div class="title">
                <?php if ($link->target != AppConstant::NUMERIC_ZERO) { ?>
                    <?php
                    $filename = substr(strip_tags($link->text), AppConstant::NUMERIC_FIVE);
                    $alink = AppUtility::getHomeURL() . 'Uploads/' . $filename;
                    echo '<a href="' . $alink . '">' . $link->title . '</a>';
                } else {
                    $filename = substr(strip_tags($link->text), AppConstant::NUMERIC_FIVE);
                    $alink = AppUtility::getHomeURL() . 'Uploads/' . $filename;
                    echo '<a href="' . $alink . '">' . ucfirst($link->title) . '</a>';
                } ?>
                <div class="floatright">
                    <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown"
                       href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button"
                                                       src="<?php echo AppUtility::getAssetURL() ?>img/courseSettingItem.png"/></a>
                    <ul class=" select1 dropdown-menu selected-options">
                        <li><a class="modify"
                               href="<?php echo AppUtility::getURLFromHome('course', 'course/add-link?cid=' . $course->id . '&id=' . $link->id); ?>"><?php AppUtility::t('Modify'); ?></a>
                        </li>
                        <li><a id="delete"
                               href="javascript:deleteItem('<?php echo $link->id; ?>','<?php echo AppConstant::LINK ?>','<?php echo $parent; ?>','<?php echo $course->id; ?>')"><?php AppUtility::t('Delete'); ?></a>
                        </li>
                        <li><a id="copy"
                               href="javascript:copyItem('<?php echo $item['link']['id']; ?>','<?php echo AppConstant::LINK ?>','<?php echo $parent; ?>','<?php echo $course->id; ?>')"><?php AppUtility::t('Copy'); ?></a>
                        </li>
                    </ul>
                </div>

                <?php if ($link['avail'] == AppConstant::NUMERIC_ZERO) { ?>
                    <BR>Hidden
                <?php } else if ($link['avail'] == AppConstant::NUMERIC_TWO) { ?>
                    <br>Showing Always
                <?php } else if ($link->enddate >= $currentTime && $link->startdate >= $currentTime || $link->enddate <= $currentTime && $link->startdate <= $currentTime) { ?>

                    <?php if ($link['avail'] == AppConstant::NUMERIC_ONE && $link->startdate != AppConstant::NUMERIC_ZERO) { ?>
                        <br>Showing <?php echo $startDateOfLink ?>
                        <?php if ($link->enddate == AppConstant::ALWAYS_TIME) { ?>
                            until Always
                        <? } else { ?>
                            until <?php echo $endDateOfLink ?>,
                        <?php
                        }
                    } else if ($link->startdate == AppConstant::NUMERIC_ZERO) {
                        ?>
                        <br>Showing Always until <?php echo $endDateOfLink ?>
                    <?php } ?>
                <?php } else if ($link->enddate == AppConstant::ALWAYS_TIME || $link->startdate == AppConstant::NUMERIC_ZERO) { ?>
                    <br>Showing until:
                    <?php if ($link->enddate == AppConstant::ALWAYS_TIME) { ?>
                        Always
                    <?php } else { ?>
                        <?php echo $endDateOfLink ?>

                    <?php
                    }
                } else if ($link->startdate <= $currentTime && $link->enddate >= $currentTime) {
                    ?>
                    <br> Showing until:<?php echo $endDateOfLink; ?>
                <?php } else if ($link->startdate >= $currentTime && $link->enddate <= $currentTime) { ?>
                    <br>Showing <?php echo $startDateOfLink; ?> until <?php echo $endDateOfLink; ?>
                <?php } ?>

            </div>
            <div class="itemsum">
                <p>

                <p><?php echo $link->summary ?>&nbsp;</p></p>
            </div>
            <div class="clear"></div>
        </div>
        <!--Link type : external tool-->
    <?php } elseif (substr($link->text, AppConstant::NUMERIC_ZERO, 8) == 'exttool:') { ?>
        <div class="item">
            <img alt="link to html" class="floatleft item-icon-alignment"
                 src="<?php echo AppUtility::getHomeURL() ?>img/link.png"/>

            <div class="title">
                <!--open on new window or on same window-->
                <?php if ($link->target != AppConstant::NUMERIC_ZERO) { ?>
                    <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $link->courseid . '&id=' . $link->id) ?>"
                          target="_blank">
                            <?php echo $link->title ?>&nbsp;<img
                                src="<?php echo AppUtility::getHomeURL() ?>img/extlink.png"/></a></b>
                <?php } else { ?>
                    <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid=' . $link->courseid . '&id=' . $link->id) ?>">
                            <?php echo ucfirst($link->title) ?></a></b>
                <?php } ?>
                <div class="floatright">
                    <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown"
                       href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button"
                                                       src="<?php echo AppUtility::getAssetURL() ?>img/courseSettingItem.png"/></a>
                    <ul class=" select1 dropdown-menu selected-options">
                        <li><a class="modify"
                               href="<?php echo AppUtility::getURLFromHome('course', 'course/add-link?cid=' . $course->id . '&id=' . $link->id); ?>"><?php AppUtility::t('Modify'); ?></a>
                        </li>
                        <li><a id="delete"
                               href="javascript:deleteItem('<?php echo $link->id; ?>','<?php echo AppConstant::LINK ?>','<?php echo $parent; ?>','<?php echo $course->id; ?>')"><?php AppUtility::t('Delete'); ?></a>
                        </li>
                        <li><a id="copy"
                               href="javascript:copyItem('<?php echo $item['link']['id']; ?>','<?php echo AppConstant::LINK ?>','<?php echo $parent; ?>','<?php echo $course->id; ?>')"><?php AppUtility::t('Copy'); ?></a>
                        </li>
                    </ul>
                </div>

                <?php if ($link['avail'] == AppConstant::NUMERIC_ZERO) { ?>
                    <BR>Hidden
                <?php } else if ($link['avail'] == AppConstant::NUMERIC_TWO) { ?>
                    <br>Showing Always
                <?php } else if ($link->enddate >= $currentTime && $link->startdate >= $currentTime || $link->enddate <= $currentTime && $link->startdate <= $currentTime) { ?>

                    <?php if ($link['avail'] == AppConstant::NUMERIC_ONE && $link->startdate != AppConstant::NUMERIC_ZERO) { ?>
                        <br>Showing <?php echo $startDateOfLink ?>
                        <?php if ($link->enddate == AppConstant::ALWAYS_TIME) { ?>
                            until Always
                        <? } else { ?>
                            until <?php echo $endDateOfLink ?>,
                        <?php
                        }
                    } else if ($link->startdate == AppConstant::NUMERIC_ZERO) {
                        ?>
                        <br>Showing Always until <?php echo $endDateOfLink ?>
                    <?php } ?>
                <?php } else if ($link->enddate == AppConstant::ALWAYS_TIME || $link->startdate == AppConstant::NUMERIC_ZERO) { ?>
                    <br>Showing until:
                    <?php if ($link->enddate == AppConstant::ALWAYS_TIME) { ?>
                        Always
                    <?php } else { ?>
                        <?php echo $endDateOfLink ?>
                    <?php
                    }
                } else if ($link->startdate <= $currentTime && $link->enddate >= $currentTime) {
                    ?>
                    <br> Showing until:<?php echo $endDateOfLink; ?>
                <?php } else if ($link->startdate >= $currentTime && $link->enddate <= $currentTime) { ?>
                    <br>Showing <?php echo $startDateOfLink; ?> until <?php echo $endDateOfLink; ?>
                <?php } ?>

            </div>
            <div class="itemsum"><p>

                <p><?php echo $link->summary ?>&nbsp;</p></p></div>
            <div class="clear"></div>
        </div>
    <?php } else { ?>
        <div class="item">
            <img alt="link to html" class="floatleft item-icon-alignment"
                 src="<?php echo AppUtility::getHomeURL() ?>img/link.png"/>

            <div class="title">
                <?php if ($link->target != AppConstant::NUMERIC_ZERO) { ?>
                    <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/show-linked-text?cid=' . $link->courseid . '&id=' . $link->id) ?>"
                          target="_blank">
                            <?php echo ucfirst($link->title) ?>&nbsp;<img
                                src="<?php echo AppUtility::getHomeURL() ?>img/extlink.png"/></a></b>
                <?php } else { ?>
                    <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/show-linked-text?cid=' . $link->courseid . '&id=' . $link->id) ?>">
                            <?php echo $link->title ?></a></b>
                <?php } ?>
                <div class="floatright">
                    <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown"
                       href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button"
                                                       src="<?php echo AppUtility::getAssetURL() ?>img/courseSettingItem.png"/></a>
                    <ul class=" select1 dropdown-menu selected-options">
                        <li><a class="modify"
                               href="<?php echo AppUtility::getURLFromHome('course', 'course/add-link?cid=' . $course->id . '&id=' . $link->id); ?>"><?php AppUtility::t('Modify'); ?></a>
                        </li>
                        <li><a id="delete"
                               href="javascript:deleteItem('<?php echo $link->id; ?>','<?php echo AppConstant::LINK ?>','<?php echo $parent; ?>','<?php echo $course->id; ?>')"><?php AppUtility::t('Delete'); ?></a>
                        </li>
                        <li><a id="copy"
                               href="javascript:copyItem('<?php echo $item['link']['id']; ?>','<?php echo AppConstant::LINK ?>','<?php echo $parent; ?>','<?php echo $course->id; ?>')"><?php AppUtility::t('Copy'); ?></a>
                        </li>
                    </ul>

                </div>
                <?php if ($link['avail'] == AppConstant::NUMERIC_ZERO) { ?>
                    <BR>Hidden
                <?php } else if ($link['avail'] == AppConstant::NUMERIC_TWO) { ?>
                    <br>Showing Always
                <?php } else if ($link->enddate >= $currentTime && $link->startdate >= $currentTime || $link->enddate <= $currentTime && $link->startdate <= $currentTime) { ?>

                    <?php if ($link['avail'] == AppConstant::NUMERIC_ONE && $link->startdate != AppConstant::NUMERIC_ZERO) { ?>
                        <br>Showing <?php echo $startDateOfLink ?>
                        <?php if ($link->enddate == AppConstant::ALWAYS_TIME) { ?>
                            until Always
                        <? } else { ?>
                            until <?php echo $endDateOfLink ?>,
                        <?php
                        }
                    } else if ($link->startdate == AppConstant::NUMERIC_ZERO) {
                        ?>
                        <br>Showing Always until <?php echo $endDateOfLink ?>
                    <?php } ?>
                <?php } else if ($link->enddate == AppConstant::ALWAYS_TIME || $link->startdate == AppConstant::NUMERIC_ZERO) { ?>
                    <br>Showing until:
                    <?php if ($link->enddate == AppConstant::ALWAYS_TIME) { ?>
                        Always
                    <?php } else { ?>
                        <?php echo $endDateOfLink ?>
                    <?php
                    }
                } else if ($link->startdate <= $currentTime && $link->enddate >= $currentTime) {
                    ?>
                    <br> Showing until:<?php echo $endDateOfLink; ?>
                <?php } else if ($link->startdate >= $currentTime && $link->enddate <= $currentTime) { ?>
                    <br>Showing <?php echo $startDateOfLink; ?> until <?php echo $endDateOfLink; ?>
                <?php } ?>

            </div>
            <div class="itemsum"><p>

                <p><?php echo $link->summary ?>&nbsp;</p></p></div>
            <div class="clear"></div>
        </div>
    <?php } ?>
    <?php
    }

    /*
     * Inline Text
     */
    public static function AddInlineText($item, $currentTime, $course, $parent)
    {
        $inline = $item[key($item)];
        ?>
        <input type="hidden" id="inlineText-selected-id" value="<?php echo $inline->id ?>">
        <?php if ($inline->avail != AppConstant::NUMERIC_ZERO && $inline->avail == AppConstant::NUMERIC_TWO || $inline->startdate < $currentTime && $inline->enddate > $currentTime && $inline->avail == AppConstant::NUMERIC_ONE) { ?> <!--Hide ends and displays show always-->
        <div class="item">
            <?php $InlineId = $inline->id;
            $endDate = AppUtility::formatDate($inline->enddate);?>
            <div class="floatright">
                <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown"
                   href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button"
                                                   src="<?php echo AppUtility::getAssetURL() ?>img/courseSettingItem.png"/></a>
                <ul class=" select1 dropdown-menu selected-options pull-right">
                    <li><a class="modify"
                           href="<?php echo AppUtility::getURLFromHome('course', 'course/modify-inline-text?cid=' . $course->id . '&id=' . $inline->id) ?>"><?php AppUtility::t('Modify'); ?></a>
                    </li>
                    <li><a id="delete"
                           href="javascript:deleteItem('<?php echo $inline->id; ?>','<?php echo AppConstant::INLINE_TEXT ?>','<?php echo $parent; ?>','<?php echo $course->id; ?>')"><?php AppUtility::t('Delete'); ?></a>
                    </li>
                    <li><a id="copy"
                           href="javascript:copyItem('<?php echo $item['inline']['id']; ?>','<?php echo AppConstant::INLINE_TEXT ?>','<?php echo $parent; ?>','<?php echo $course->id; ?>')"><?php AppUtility::t('Copy'); ?></a>
                    </li>
                </ul>
            </div>
            <!--Hide title and icon-->

            <?php if ($inline->title != '##hidden##') {

            ?>
        <img alt="assess" class="floatleft item-icon-alignment"
             src="<?php echo AppUtility::getAssetURL() ?>img/inlineText.png"/>
            <div class="title">
                <b><?php echo ucfirst($inline->title) ?></b>
            </div>
            <div class="itemsum">
                <?php } ?>
                <?php if ($inline->avail == AppConstant::NUMERIC_TWO) { ?>
                    <?php echo "Showing Always";
                } else {
                    if ($inline->startdate == AppConstant::NUMERIC_ZERO && $inline->enddate == AppConstant::ALWAYS_TIME || $inline->startdate != AppConstant::NUMERIC_ZERO && $inline->enddate == AppConstant::ALWAYS_TIME) {
                        echo "Showing until: Always";
                    } else {
                        echo "Showing until: " . $endDate;
                    }
                }
                ?>
                <p><?php echo $inline->text ?></p>
            </div>
            <?php if ($inline->instrFiles != AppConstant::NUMERIC_ZERO) {
                foreach ($inline->instrFiles as $key => $instrFile) {
                    ?>
                    <ul class="fileattachlist">
                        <li>
                            <a href="/math/web/Uploads/<?php echo $instrFile->filename ?>"><?php echo $instrFile->filename ?></a>
                        </li>
                    </ul>
                <?php
                }
            } ?>
        </div>
    <?php } elseif ($inline->avail == AppConstant::NUMERIC_ZERO) { ?>
        <div class="item">
            <a class="dropdown-toggle grey-color-link select_button1" data-toggle="dropdown" href="javascript:void(0);"><img
                    alt="setting" class="floatright course-setting-button"
                    src="<?php echo AppUtility::getAssetURL() ?>img/courseSettingItem.png"/></a>
            <ul class=" select1 dropdown-menu selected-options pull-right">
                <li><a class="modify"
                       href="<?php echo AppUtility::getURLFromHome('site', 'work-in-progress?cid=' . $course->id) ?>"><?php AppUtility::t('Modify'); ?></a>
                </li>
                <li><a id="delete"
                       href="javascript:deleteItem('<?php echo $inline->id; ?>','<?php echo AppConstant::INLINE_TEXT ?>','<?php echo $parent; ?>','<?php echo $course->id; ?>')"><?php AppUtility::t('Delete'); ?></a>
                </li>
                <li><a id="copy"
                       href="javascript:copyItem('<?php echo $item['inline']['id']; ?>','<?php echo AppConstant::INLINE_TEXT ?>','<?php echo $parent; ?>','<?php echo $course->id; ?>')"><?php AppUtility::t('Copy'); ?></a>
                </li>
            </ul>
            <br>
            <!--Hide title and icon-->
            <?php if ($inline->title != '##hidden##') {
            $endDate = AppUtility::formatDate($inline->enddate);?>
        <img alt="assess" class="floatleft faded item-icon-alignment"
             src="<?php echo AppUtility::getAssetURL() ?>img/inlineText.png"/>
            <div class="title">
                <b><?php echo ucfirst($inline->title) ?></b>
            </div>
            <div class="itemsum"><p>
                    <?php
                    }
                    echo 'Hidden';
                    ?>

                <p><?php echo $inline->text ?></p>
            </div>
            <?php if ($inline->instrFiles != AppConstant::NUMERIC_ZERO) {
                foreach ($inline->instrFiles as $key => $instrFile) {
                    ?>
                    <ul class="fileattachlist">
                        <li>
                            <a href="/math/web/Uploads/<?php echo $instrFile->filename ?>"><?php echo $instrFile->filename ?></a>
                        </li>
                    </ul>
                <?php
                }
            } ?>
        </div>
        <div class="clear"></div>
    <?php } else { ?>
        <div class="item">
            <?php if ($inline->title != '##hidden##') {
            $endDate = AppUtility::formatDate($inline->enddate);?>
            <img alt="assess" class="floatleft faded item-icon-alignment"
                 src="<?php echo AppUtility::getAssetURL() ?>img/inlineText.png"/>

            <div class="title">
                <b><?php echo ucfirst($inline->title) ?></b>
                <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown"
                   href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button"
                                                   src="<?php echo AppUtility::getAssetURL() ?>img/courseSettingItem.png"/></a>
                <ul class=" select1 dropdown-menu selected-options pull-right">
                    <li><a class="modify"
                           href="<?php echo AppUtility::getURLFromHome('site', 'work-in-progress?cid=' . $course->id) ?>"><?php AppUtility::t('Modify'); ?></a>
                    </li>
                    <li><a id="delete"
                           href="javascript:deleteItem('<?php echo $inline->id; ?>','<?php echo AppConstant::INLINE_TEXT ?>','<?php echo $parent; ?>','<?php echo $course->id; ?>')"><?php AppUtility::t('Delete'); ?></a>
                    </li>
                    <li><a id="copy"
                           href="javascript:copyItem('<?php echo $item['inline']['id']; ?>','<?php echo AppConstant::INLINE_TEXT ?>','<?php echo $parent; ?>','<?php echo $course->id; ?>')"><?php AppUtility::t('Copy'); ?></a>
                    </li>
                </ul>
                <br>
            </div>
            <div class="itemsum"><p>
                    <?php
                    }
                    $startDate = AppUtility::formatDate($inline->startdate);
                    $endDate = AppUtility::formatDate($inline->enddate);
                    echo "Showing " . $startDate . " until " . $endDate; ?>
            </div>
        </div>
    <?php } ?>

    <?php
    }

    /*
     * Calender
     */
    public static function  AddCalendar($item, $parent, $course)
    {
        $currentTime = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
        ?>
        <div class="item" style="padding-bottom: 15px; padding-right: 15px">
                    <pre><a href="javascript:deleteItem('<?php echo $item['Calendar']['id']; ?>','<?php echo AppConstant::CALENDAR ?>','<?php echo $parent; ?>','<?php echo $course->id; ?>')">Delete</a> | <a
                            href="
                    <?php echo AppUtility::getURLFromHome('instructor', 'instructor/manage-events?cid=' . $course->id); ?>">Manage
                            Events</a></pre>
            <div class="col-lg-12 padding-alignment calendar-container">
                <div class='calendar padding-alignment calendar-alignment col-lg-9 pull-left'>
                    <input type="hidden" class="current-time" value="<?php echo $currentTime ?>">

                    <div id="demo" style="display:table-cell; vertical-align:middle;"></div>
                    <input type="hidden" class="calender-course-id" value="<?php echo $course->id ?>">
                </div>
                <div class="calendar-day-details-right-side pull-left col-lg-3">
                    <div class="day-detail-border">
                        <b>Day Details:</b>
                    </div>
                    <div class="calendar-day-details"></div>
                </div>
            </div>
        </div>
    <?php
    }

    /*
     * Block
     */
    public function DisplayWholeBlock($item, $currentTime, $assessment, $course, $parent, $cnt, $canEdit = null, $viewAll = null)
    {
        $block = $item[key($item)];
        $blockId = $block['id'];
        ?>
        <input type="hidden" id="SH" value="<?php echo $block['SH'] ?>">
        <input type="hidden" id="id" value="<?php echo $block['id'] ?>">
        <?php $StartDate = AppUtility::formatDate($block['startdate']); ?>
        <?php $endDate = AppUtility::formatDate($block['enddate']); ?>
        <div class="block item">
            <?php if (strlen($block['SH']) > AppConstant::NUMERIC_ONE && $block['SH'][1] == 'F') { ?>
                <span class=left>
                        <img alt="folder" src="<?php echo AppUtility::getHomeURL() ?>img/folder2.gif">
                    </span>
            <?php } elseif (strlen($block['SH']) > AppConstant::NUMERIC_ONE && $block['SH'][1] == 'T') { ?>
                <span class=left>
                         <img alt="folder" src="<?php echo AppUtility::getHomeURL() ?>img/folder_tree.png">
                    </span>
            <?php } else { ?>
                <span class=left>
                         <img alt="expand/collapse" style="cursor:pointer;" id="img<?php echo $block['id'] ?>"
                              onclick="xyz(this,<?php echo $block['id'] ?>)"
                              src="<?php echo AppUtility::getHomeURL() ?>img/collapse.gif"/>
                    </span>
            <?php } ?>
            <div class="title">
                    <span class="pointer" onclick="#">
                        <b>
                            <?php if (strlen($block['SH']) > AppConstant::NUMERIC_ONE && $block['SH'][1] == 'F') { ?>
                            <a href="<?php echo AppUtility::getURLFromHome('block', 'block/add-block?cid=' . $course->id) ?>">
                                <?php } elseif (strlen($block['SH']) > AppConstant::NUMERIC_ONE && $block['SH'][1] == 'T') { ?>
                                <a href="<?php echo AppUtility::getURLFromHome('block', 'block/tree-reader?cid=' . $course->id . '&folder=' . $parent . '-' . $cnt) ?>">
                                    <?php } else { ?>
                                    <a href="#" onclick="return false;">
                                        <?php } ?>

                                        <?php echo $block['name'] ?></a>
                                    <?php if ($block['newflag'] == AppConstant::NUMERIC_ONE) { ?>
                                        <span class="red">New</span>
                                    <?php } ?>
                        </b>
                    </span>
                <?php if (($block['avail']) == AppConstant::NUMERIC_ONE || ($block['avail']) == AppConstant::NUMERIC_TWO) { ?>
                    <span class="instrdates" style="font-family: " Times New Roman", Times, serif">
                            <?php if ($block['SH'] == 'HC') {
                        $title = 'Showing Collapsed';
                    } else if ($block['SH'] == 'HO') {
                        $title = 'Showing Expanded';
                    } elseif ($block['SH'] == 'HF') {
                        $title = 'Showing as Folder';
                    } elseif ($block['SH'] == 'HT') {
                        $title = 'Showing as TreeReader';
                    } elseif ($block['SH'] == 'SO') {
                        $title = 'Showing Expanded';
                    } elseif ($block['SH'] == 'SC') {
                        $title = 'Showing Collapsed';
                    } elseif ($block['SH'] == 'SF') {
                        $title = 'Showing as Folder';
                    } elseif ($block['SH'] == 'ST') {
                        $title = 'Showing as TreeReader';
                    }?>
                            <?php if ($block['avail'] == AppConstant::NUMERIC_ONE) { ?>
                        <?php if ($block['startdate'] == AppConstant::NUMERIC_ZERO && $block['enddate'] == AppConstant::ALWAYS_TIME) {
                            $StartDate = 'ALways';
                            $endDate = 'ALways';
                        } ?>
                        <br><?php echo $title ?>   <?php echo $StartDate ?> until <?php echo $endDate ?></span>
                        <span class="instronly">
                                         <?php if ($block['SH'] == 'HT' || $block['SH'] == 'ST') { ?>
                                             <a class="dropdown-toggle grey-color-link select_button1 floatright"
                                                data-toggle="dropdown" href="javascript:void(0);"><img alt="setting"
                                                                                                       class="floatright course-setting-button"
                                                                                                       src="<?php echo AppUtility::getAssetURL() ?>img/courseSettingItem.png"/></a>
                                             <ul class=" select1 dropdown-menu selected-options pull-right">
                                                 <li><a class="modify"
                                                        href="#"><?php AppUtility::t('Edit Content'); ?></a></li>
                                                 <li><a class="modify"
                                                        href= "<?php echo AppUtility::getURLFromHome('block','block/add-block?courseId='.$course->id.'&id='.$parent.'-'.$cnt.'&modify=1')?>"><?php AppUtility::t('Modify'); ?></a>
                                                 </li>
                                                 <li><a id="delete"
                                                        href="javascript:deleteItem('<?php echo $parent . '-' . $cnt ?>','<?php echo AppConstant::BLOCK ?>','<?php echo $parent; ?>','<?php echo $course->id; ?>')"><?php AppUtility::t('Delete'); ?></a>
                                                 </li>
                                                 <li><a id="copy"
                                                        href="javascript:copyItem('<?php echo $parent . '-' . $cnt; ?>','<?php echo AppConstant::BLOCK ?>','<?php echo $parent; ?>','<?php echo $course->id; ?>')"><?php AppUtility::t('Copy'); ?></a>
                                                 </li>
                                                 <li><a id="copy"
                                                        href="<?php echo AppUtility::getURLFromHome('block', 'block/new-flag?cid=' . $course->id . '&newflag=' . $parent . '-' . $cnt) ?>"><?php AppUtility::t('NewFlag'); ?></a>
                                                 </li>
                                             </ul><br>
                                         <?php } else { ?>
                                             <a class="dropdown-toggle grey-color-link select_button1 floatright"
                                                data-toggle="dropdown" href="javascript:void(0);"><img alt="setting"
                                                                                                       class="floatright course-setting-button"
                                                                                                       src="<?php echo AppUtility::getAssetURL() ?>img/courseSettingItem.png"/></a>
                                             <ul class=" select1 dropdown-menu selected-options pull-right">
                                                 <li><a class="isolate"
                                                        href="<?php echo AppUtility::getURLFromHome('course', 'course/block-isolate?cid=' . $course->id . "&blockId=" . $blockId) ?>"><?php AppUtility::t('Isolate'); ?></a>
                                                 </li>
                                                 <li><a class="modify"
                                                        href= "<?php echo AppUtility::getURLFromHome('block','block/add-block?courseId='.$course->id.'&id='.$parent.'-'.$cnt.'&modify=1')?>"><?php AppUtility::t('Modify'); ?></a>
                                                 </li>
                                                 <li><a id="delete"
                                                        href="javascript:deleteItem('<?php echo $parent . '-' . $cnt ?>','<?php echo AppConstant::BLOCK ?>','<?php echo $parent; ?>','<?php echo $course->id; ?>')"><?php AppUtility::t('Delete'); ?></a>
                                                 </li>
                                                 <li><a id="copy"
                                                        href="javascript:copyItem('<?php echo $parent . '-' . $cnt; ?>','<?php echo AppConstant::BLOCK ?>','<?php echo $parent; ?>','<?php echo $course->id; ?>')"><?php AppUtility::t('Copy'); ?></a>
                                                 </li>
                                                 <li><a id="copy"
                                                        href="<?php echo AppUtility::getURLFromHome('block', 'block/new-flag?cid=' . $course->id . '&newflag=' . $parent . '-' . $cnt) ?>"><?php AppUtility::t('NewFlag'); ?></a>
                                                 </li>
                                             </ul><br>
                                         <?php } ?>
                                    </span>
                    <?php } else { ?>
                        <br><?php echo $title ?> Always</span>
                        <span class="instronly">
                                          <a class="dropdown-toggle grey-color-link select_button1 floatright"
                                             data-toggle="dropdown" href="javascript:void(0);"><img alt="setting"
                                                                                                    class="floatright course-setting-button"
                                                                                                    src="<?php echo AppUtility::getAssetURL() ?>img/courseSettingItem.png"/></a>
                                             <ul class=" select1 dropdown-menu selected-options pull-right">
                                                 <li><a class="isolate"
                                                        href="<?php echo AppUtility::getURLFromHome('course', 'course/block-isolate?cid=' . $course->id . "&blockId=" . $blockId) ?>"><?php AppUtility::t('Isolate'); ?></a>
                                                 </li>
                                                 <li><a class="modify"
                                                        href= "<?php echo AppUtility::getURLFromHome('block','block/add-block?courseId='.$course->id.'&id='.$parent.'-'.$cnt.'&modify=1')?>"><?php AppUtility::t('Modify'); ?></a>
                                                 </li>
                                                 <li><a id="delete"
                                                        href="javascript:deleteItem('<?php echo $parent . '-' . $cnt ?>','<?php echo AppConstant::BLOCK ?>','<?php echo $parent; ?>','<?php echo $course->id; ?>')"><?php AppUtility::t('Delete'); ?></a>
                                                 </li>
                                                 <li><a id="copy"
                                                        href="javascript:copyItem('<?php echo $parent . '-' . $cnt; ?>','<?php echo AppConstant::BLOCK ?>','<?php echo $parent; ?>','<?php echo $course->id; ?>')"><?php AppUtility::t('Copy'); ?></a>
                                                 </li>
                                                 <li><a id="copy"
                                                        href="<?php echo AppUtility::getURLFromHome('block', 'block/new-flag?cid=' . $course->id . '&newflag=' . $parent . '-' . $cnt) ?>"><?php AppUtility::t('NewFlag'); ?></a>
                                                 </li>
                                             </ul><br>
                                    </span>
                    <?php } ?>
                    <?php } else { ?>
                    <input type="hidden" id="isHidden" value="1">
                    <span class="instrdates">
                            <br>Hidden</span>
                    <span class="instronly">
                            <a class="dropdown-toggle grey-color-link select_button1 floatright" data-toggle="dropdown"
                               href="javascript:void(0);"><img alt="setting" class="floatright course-setting-button"
                                                               src="<?php echo AppUtility::getAssetURL() ?>img/courseSettingItem.png"/></a>
                                             <ul class=" select1 dropdown-menu selected-options pull-right">
                                                 <li><a class="isolate"
                                                        href="<?php echo AppUtility::getURLFromHome('course', 'course/block-isolate?cid=' . $course->id . "&blockId=" . $blockId) ?>"><?php AppUtility::t('Isolate'); ?></a>
                                                 </li>
                                                 <li><a class="modify"
                                                        href= "<?php echo AppUtility::getURLFromHome('block','block/add-block?courseId='.$course->id.'&id='.$parent.'-'.$cnt.'&modify=1')?>"><?php AppUtility::t('Modify'); ?></a>
                                                 </li>
                                                 <li><a id="delete"
                                                        href="javascript:deleteItem('<?php echo $parent . '-' . $cnt ?>','<?php echo AppConstant::BLOCK ?>','<?php echo $parent; ?>','<?php echo $course->id; ?>')"><?php AppUtility::t('Delete'); ?></a>
                                                 </li>
                                                 <li><a id="copy"
                                                        href="javascript:copyItem('<?php echo $parent . '-' . $cnt; ?>','<?php echo AppConstant::BLOCK ?>','<?php echo $parent; ?>','<?php echo $course->id; ?>')"><?php AppUtility::t('Copy'); ?></a>
                                                 </li>
                                                 <li><a id="copy"
                                                        href="<?php echo AppUtility::getURLFromHome('block', 'block/new-flag?cid=' . $course->id . '&newflag=' . $parent . '-' . $cnt) ?>"><?php AppUtility::t('NewFlag'); ?></a>
                                                 </li>
                                             </ul><br>
                        </span>
                <?php } ?>
            </div>
        </div>
        <div class="blockitems block-alignment" id="block5<?php echo $block['id'] ?>">
            <div class="course-sub-block">
                <?php if (count($item['itemList'])) { ?>
                    <?php $blockList = array();
                    $countCourseDetails = count($item['itemList']);
                    for ($i = AppConstant::NUMERIC_ZERO; $i < $countCourseDetails; $i++) {
                        if ($item['itemList'][$i]['Block']) { //if is a block
                            $blockList[] = $i + AppConstant::NUMERIC_ONE;
                        }
                    }
                    ?>
                    <?php foreach ($item['itemList'] as $itemlistKey => $item) { ?>
                        <?php echo AssessmentUtility::createItemOrder($itemlistKey, $countCourseDetails, $parent . '-' . $cnt, $blockList); ?>
                        <?php switch (key($item)):
                            /*Assessment here*/
                            case 'Assessment':
                                ?>
                                <div class="inactivewrapper "
                                     onmouseout="this.className='inactivewrapper'">
                                    <?php $this->AddAssessment($assessment, $item, $course, $currentTime, $parent . '-' . $cnt, $canEdit, $viewAll); ?>
                                </div>
                                <?php break; ?>

                                <!-- Forum here-->
                            <?php
                            case 'Forum':
                                ?>
                                <?php $this->AddForum($item, $course, $currentTime, $parent . '-' . $cnt); ?>
                                <?php break; ?>

                                <!-- Wiki here-->
                            <?php
                            case 'Wiki':
                                ?>
                                <?php $this->AddWiki($item, $course, $parent . '-' . $cnt, $currentTime); ?>
                                <?php break; ?>

                                <!-- Linked text here -->
                            <?php
                            case 'LinkedText':
                                ?>
                                <?php $this->AddLink($item, $currentTime, $parent . '-' . $cnt, $course); ?>
                                <?php break; ?>
                                <!-- Inline text here -->
                            <?php
                            case 'InlineText':
                                ?>
                                <?php $this->AddInlineText($item, $currentTime, $course, $parent . '-' . $cnt); ?>
                                <?php break; ?>

                                <!-- Calender Here-->
                            <?php
                            case 'Calendar':
                                ?>
                                <?php $this->AddCalendar($item, $parent . '-' . $cnt, $course); ?>
                                <?php break; ?>
                            <?php
                            case '':
                                ?>
                                <?php

                                $this->DisplayWholeBlock($block['items'], $currentTime, $assessment, $course, $parent++, $cnt);
                                ?>
                                <?php break; ?>
                                a                        <?php endswitch; ?>
                    <?php } ?>
                <?php } ?>
            </div>
            <?php $this->AddItemsDropDown(); ?>
            <input type="hidden" id="parent" value="<?php echo $parent . '-' . $cnt; ?>">

        </div>
        <div class="clear"></div>
    <?php
    }
    /*
     * POP FOR ADDING NEW ITEMS
     */
    public static function AddItemsDropDown()
    {
        ?>
        <div class="row add-item-in-block">
            <div class="col-md-1 plus-icon">
                <img class="add-item-icon" src="<?php echo AppUtility::getAssetURL() ?>img/addItem.png">
            </div>
            <div class=" col-md-2 add-item-text">
                <p><?php AppUtility::t('Add An Item...'); ?></p>
            </div>
        </div>
    <?php
    }
}

?>

<script>


    $(document).ready(function () {
        var assessmentLink = $(".assessment-link").val();
        var courseId = $(".courseId").val();

        $('.add-item-in-block').on('click', function (evt) {
            var block = $('#parent').val();
            var tb = 't';

            var html = '<div class="">' +
                '<a href="../../assessment/assessment/add-assessment?cid=' + courseId + '&block=' + block + '&tb=' + tb + '">' +
                '<div class="assessment itemLink" >' +
                '<img class="icon-center icon-size" id=\"addtype$parent-$tb\" onclick= \"additem(1, t)" src="../../img/iconAssessment.png">' +
                '<div class="item-name">Assessment</div>' +
                '</div>' +
                '</a>' +

                '<a href="../../course/course/modify-inline-text?cid=' + courseId + '&block=' + block + '&tb=' + tb + '"><div class="inline-text itemLink">' +
                '<img class="icon-center icon-size" src="../../img/inlineText.png">' +
                '<div class="item-name">Inline Text</div>' +
                '</div></a>' +

                '<a href="../../course/course/add-link?cid=' + courseId + '&block=' + block + '&tb=' + tb + '"><div class="link itemLink">' +
                '<img class="icon-center icon-size" src="../../img/link.png">' +
                '<div class="item-name-small">Link</div>' +
                '</div></a>' +

                '<a href="../../forum/forum/add-forum?cid=' + courseId + '&block=' + block + '&tb=' + tb + '"><div class="forum itemLink">' +
                '<img class="icon-center icon-size" src="../../img/iconForum.png">' +
                '<div class="item-name-small">Forum</div>' +
                '</div></a>' +

                '<a href="../../wiki/wiki/add-wiki?courseId=' + courseId + '"><div class="wiki itemLink">' +
                '<img class="icon-center icon-size" src="../../img/iconWiki.png">' +
                '<div class="item-name-small">Wiki</div>' +
                '</div></a>' +

                '<a href="../../instructor/instructor/index?cid=' + courseId + '&block=' + block + '&tb=' + tb + '&type=' + "calendar" + '"><div class="calendar-pop-up itemLink">' +
                '<img class="icon-center icon-size" src="../../img/iconCalendar.png">' +
                '<div class="item-name">Calendar</div>' +
                '</div></a>' +

                '<a href="../../block/block/add-block?courseId=' + courseId + '&block=' + block + '&tb=' + tb + '"><div class="block-item itemLink">' +
                '<img class="icon-center icon-size" src="../../img/block.png">' +
                '<div class="item-name-small block-name-alignment">Block</div>' +
                '</div></a>' +
                '</div>';
            $('<div class="dialog-items close-box" id="dialog"></div>').appendTo('body').html(html).dialog({
                modal: true, message: 'Add An Item', zIndex: 10000, autoOpen: true, width: '410px', height: '419px', title: 'Add an Item...',
                closeText: "show",
                close: function (event, ui) {
                    $(this).remove();
                },
                open: function () {
                    jQuery('.ui-widget-overlay').bind('click', function () {
                        jQuery('#dialog').dialog('close');
                    })
                }
            });
        });

        $("#plus-icon, #add-item").click(function () {
            $('<div id=""></div>').appendTo('body').html(html).dialog({
                modal: true, zIndex: 10000, autoOpen: true,
                width: '30%', resizable: false,
                closeText: "hide",
                close: function (event, ui) {
                    $(this).remove();
                }
            });
        });
    });
</script>
