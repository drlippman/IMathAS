<?php
namespace app\controllers\forum;

use app\components\AppConstant;
use app\components\AssessmentUtility;
use app\models\Assessments;
use app\models\AssessmentSession;
use app\models\ContentTrack;
use app\models\Course;
use app\models\ExternalTools;
use app\models\forms\ChangeUserInfoForm;
use app\models\forms\ForumForm;
use app\controllers\AppController;
use app\models\forms\ThreadForm;
use app\models\ForumLike;
use app\models\ForumPosts;
use app\models\ForumSubscriptions;
use app\models\ForumThread;
use app\models\ForumView;
use app\models\Forums;
use app\models\GbCats;
use app\models\Grades;
use app\models\Items;
use app\models\LinkedText;
use app\models\Outcomes;
use app\models\Rubrics;
use app\models\Student;
use app\models\StuGroupMembers;
use app\models\Stugroups;
use app\models\StuGroupSet;
use app\models\Thread;
use app\models\Tutor;
use app\models\User;
use app\components\htmLawed;
use yii\web\UploadedFile;
use app\components\AppUtility;
use Yii;

class ForumController extends AppController
{
    public $postData = array();
    public $totalPosts = array();
    public $children = array();
    public $threadLevel = AppConstant::NUMERIC_ONE;

    public function beforeAction($action)
    {
        $actionPath = Yii::$app->controller->action->id;
        $user = $this->getAuthenticatedUser();
        $courseId =  ($this->getRequestParams('cid') || $this->getRequestParams('courseId')) ? ($this->getRequestParams('cid')?$this->getRequestParams('cid'):$this->getRequestParams('courseId') ): AppUtility::getDataFromSession('courseId');
        return $this->accessForTeacherAndStudentForumController($user,$courseId,$actionPath);
    }

    /*
    * Controller Action To Redirect To Search Forum Page
    */
    public function actionSearchForum()
    {
        $cid = $this->getParamVal('cid');
        $course = Course::getById($cid);
        $user = $this->getAuthenticatedUser();
        $params = $this->getRequestParams();
        $this->layout = "master";
        $teacherid = $this->isTeacher($user['id'],$cid);
        $tutorid = $this->isTutor($user['id'],$cid);
        $studentid = $this->isStudent($user['id'],$cid);
        if (!isset($teacherid) && !isset($tutorid) && !isset($studentid)) {
            $this->setErrorFlash('You are not enrolled in this course.');
            return $this->redirect(Yii::$app->getHomeUrl());
        }
        if ($teacherid) {
            $isteacher = true;
        } else {
            $isteacher = false;
        }
        if (!isset($params['cid'])) {
            exit;
        }

        if (isset($params['searchsubmit'])) {
            if (trim($params['search'])=='' && $params['tagfiltersel'] == '') {
                $params['clearsearch'] = true;
            }
        }
        $sessionId = $this->getSessionId();
        $sessiondata = $this->getSessionData($sessionId);
        if (isset($params['clearsearch'])) {
            unset($sessiondata['forumsearchstr'.$cid]);
            unset($sessiondata['forumsearchtype'.$cid]);
            unset($sessiondata['forumsearchtag'.$cid]);
            AppUtility::writesessiondata($sessiondata,$sessionId);
            $searchtype = "none";
        } else if(isset($params['searchsubmit'])) {
            $searchstr = trim($params['search']);
            $searchtype = $params['searchtype'];
            $searchtag = $params['tagfiltersel'];
            $sessiondata['forumsearchstr'.$cid] = $searchstr;
            $sessiondata['forumsearchtype'.$cid] = $searchtype;
            $sessiondata['forumsearchtag'.$cid] = $searchtag;
            AppUtility::writesessiondata($sessiondata,$sessionId);
        } else if (isset($sessiondata['forumsearchstr'.$cid])) {
            $searchstr = $sessiondata['forumsearchstr'.$cid];
            $searchtype = $sessiondata['forumsearchtype'.$cid];
            $searchtag = $sessiondata['forumsearchtag'.$cid];
        } else {
            $searchtype = "none";
        }
        //get general forum info and page order
        $now = time();
        $forums = Forums::getByCourseIdAndTeacher($cid,$isteacher,$now);

        $forumdata = array();
        $anyforumsgroup = false;
        foreach($forums as $line )
        {
            $forumdata[$line['id']] = $line;
            if ($line['groupsetid']>0) {
                $anyforumsgroup = true;
            }
        }
        $itemorder = unserialize($course['itemorder']);
        global $addto;
        $itemsimporder = array();

        if($itemorder)
        {
            $this->flattenitems($itemorder,$itemsimporder);
        } else{
            $this->setErrorFlash("No items present.");
        }

        $itemsassoc = array();
        $items = Items::getByCourseIdAndItenType($cid,'Forum');
        if(count($addto) > 0)
        {
            $itemsimporder = $addto;
        }

        foreach ($items as $item)
        {
            $itemsassoc[$item['id']] = $item['typeid'];
            if (!in_array($item['id'],$itemsimporder)) {
                //capture any forums that are in imas_items but not imas_courses.itemorder
                $itemsimporder[] = $item['id'];
            }
        }
        if(count($itemsimporder) > 0)
        {
            $maxitemnum = max($itemsimporder) + 1;
        }


//capture any forums that are not in imas_items
        foreach ($forumdata as $fid=>$line)
        {
            if (in_array($fid,$itemsassoc)) { continue; }
            $itemsassoc[$maxitemnum] = $fid;
            $itemsimporder[] = $maxitemnum;
            $maxitemnum++;
        }

        if ($searchtype == 'thread') {
            //doing a search of thread subjects
            $now = time();
            if ($searchstr != '') {
                $searchstr = str_replace(' and ', ' ',$searchstr);
                $searchterms = explode(" ", $searchstr);
                $searchlikes = "(imas_forum_posts.subject LIKE '%".implode("%' AND imas_forum_posts.subject LIKE '%",$searchterms)."%')";
            }
            $searchedPost = ForumPosts::getBySearchTextForThread($isteacher, $now, $cid, $searchlikes,$anyforumsgroup,$searchstr,$searchtag,$user->id);

            $threaddata = array();
            $threadids = array();
            foreach ($searchedPost as $line )
            {
                $threaddata[$line['id']] = $line;
                $threadids[] = $line['id'];
            }

            $maxPost = ForumPosts::getMaxPostDateWithThreadId($threadids);
            $postcount = array();
            $maxdate = array();
            foreach ($maxPost as $row)
            {
                $postcount[$row['threadid']] = $row['postcount'] - 1;
                $maxdate[$row['threadid']] = $row['maxdate'];
            }

        } else if ($searchtype == 'posts') {
            //doing a search of all posts
            if ($searchstr != '') {
                $searchstr = str_replace(' and ', ' ',$searchstr);
                $searchterms = explode(" ",$searchstr);

                $searchlikes = "(imas_forum_posts.message LIKE '%".implode("%' AND imas_forum_posts.message LIKE '%",$searchterms)."%')";
                $searchlikes2 = "(imas_forum_posts.subject LIKE '%".implode("%' AND imas_forum_posts.subject LIKE '%",$searchterms)."%')";
                $searchlikes3 = "(imas_users.LastName LIKE '%".implode("%' AND imas_users.LastName LIKE '%",$searchterms)."%')";

            }
            $searchedPost = ForumPosts::getBySearchTextForForum($isteacher, $now, $cid, $searchlikes, $searchlikes2, $searchlikes3,$anyforumsgroup,$searchstr,$searchtag,$user->id);

        } else {

                //default display
            $forumPost = ForumPosts::threadCount($cid);

            foreach ($forumPost as $post) {
                $threadcount[$post['id']] = $post['COUNT(imas_forum_posts.id)'];
            }
            $maxPostDate = Forums::getMaxPostDate($cid);
            foreach ($maxPostDate as $row ) {
                $postcount[$row['id']] = $row['postcount'];
                $maxdate[$row['id']] = $row['MAX(imas_forum_posts.postdate)'];
            }

            $forumThreadCount = ForumThread::forumThreadCount($cid,$user['id'],$teacherid);
            foreach ($forumThreadCount as $row) {
                $newcnt[$row['forumid']] = $row['COUNT(imas_forum_threads.id)'];
            }

        }
        $this->includeCSS(['dataTables.bootstrap.css','forums.css', 'dashboard.css']);
        $this->includeJS(['forum/thread.js','general.js?ver=012115','jquery.dataTables.min.js','dataTables.bootstrap.js','forum/thread.js']);
        $this->setReferrer();
        $this->includeCSS(['course/course.css']);
        $responseData = array('searchedPost' => $searchedPost,'threadids' => $threadids,'forums' => $forums,'threaddata' => $threaddata,'searchtag' => $searchtag,'newcnt' => $newcnt,'threadcount' => $threadcount,'postcount' => $postcount,'maxdate' => $maxdate,'forumdata' => $forumdata,'isteacher' => $isteacher,
            'itemsassoc' => $itemsassoc,'threadids' => $threadids,'itemsimporder' => $itemsimporder,'searchedPost' => $searchedPost,'cid' => $cid, 'users' => $user, 'course' => $course,'searchtype' => $searchtype);
        return $this->renderWithData('forum',$responseData);
    }

    /*Controller action to show new post to user*/
    public function actionNewPost()
    {
        $this->guestUserHandler();
        $this->layout = 'master';
        $user = $this->getAuthenticatedUser();
        $courseId = $this->getParamVal('cid');
        $countPost = $this->getNotificationDataForum($courseId, $user);
        $msgList = $this->getNotificationDataMessage($courseId, $user);
        $this->setSessionData('messageCount', $msgList);
        $this->setSessionData('postCount', $countPost);
        $course = Course::getById($courseId);
        $threadArray = array();
        $NewPostCounts = Thread::findNewPostCnt($courseId, $user);

        foreach ($NewPostCounts as $newPost) {
            $threads = ThreadForm::thread($newPost['forumid']);
            $forumName = Forums::getForumName($newPost['forumid']);
            foreach ($threads as $thread) {
                $username = User::getById($thread['userid']);
                $lastView = ForumView::getLastView($user, $thread['threadid']);
                if ($thread['postdate'] >= $lastView[AppConstant::NUMERIC_ZERO]['lastview'] && $user['id'] != $username->id) {
                    $temparray = array
                    (
                        'parent' => $thread['parent'],
                        'threadId' => $thread['threadid'],
                        'forumiddata' => $thread['forumid'],
                        'forumName' => $forumName['name'],
                        'subject' => $thread['subject'],
                        'postdate' => AppController::customizeDate($thread['postdate']),
                        'name' => AppUtility::getFullName($username->FirstName, $username->LastName),
                        'userright' => $user['rights'],
                        'postUserId' => $username->id,
                        'currentUserId' => $user['id'],
                    );
                    if ($temparray['parent'] == AppConstant::NUMERIC_ZERO) {
                        array_push($threadArray, $temparray);
                    }

                }
            }
        }
        $this->includeCSS(['forums.css']);
        return $this->renderWithData('newPost', ['threadArray' => $threadArray, 'course' => $course, 'users' => $user]);
    }

    /*
     * Controller Action To Redirect To Thread Page
     */
    public function actionThread()
    {
        $this->layout = "master";
        $this->guestUserHandler();
        $params = $this->getRequestParams();
        $currentUser = $this->getAuthenticatedUser();
        $threadsperpage = $currentUser['listperpage'];
        $forumId = $params['forum'];
        $courseId = $params['cid'];
        if ($params['forum']) {
            $forumId = $params['forum'];
        } else if ($params['forumid']) {
            $forumId = $params['forumid'];
        }
        if (!isset($params['page']) || $params['page'] == '') {
            $page = 1;
        } else {
            $page = $params['page'];
        }
        $teacherId = $this->isTeacher($currentUser['id'], $courseId);
        $tutorId = $this->isTutor($currentUser['id'], $courseId);
        $studentId = Student::getByCourseId($courseId, $currentUser['id']);
        if ($teacherId) {
            $isteacher = true;
        } else {
            $isteacher = false;
        }

        $forumData = Forums::getById($forumId);
        if (($isteacher || isset($tutorId)) && isset($params['score'])) {

            if (isset($tutorId))
            {
                if ($forumData['tutoredit'] != 1) {
                    //no rights to edit score
                    exit;
                }
            }
            $existingscores = array();
            $gradeData = Grades::getByGradeTypeIdAndGradeType('forum', $forumId);
            foreach ($gradeData as $grade) {
                $existingscores[$grade['refid']] = $grade['id'];
            }
            $postuserids = array();

            $forumPosts = ForumPosts::getByRefIds(array_keys($params['score']));
            foreach ($forumPosts as $forumPost) {
                $postuserids[$forumPost['id']] = $forumPost['userid'];
            }
            foreach ($params['score'] as $k => $v) {
                if (isset($params['feedback'][$k])) {
                    $feedback = $params['feedback'][$k];
                } else {
                    $feedback = '';
                }
                if (is_numeric($v)) {
                    if (isset($existingscores[$k])) {
                        Grades::updateById($v, $feedback, $existingscores[$k]);
                    } else {
                        $grade = array(
                            'gradetype' => 'forum',
                            'gradetypeid' => $forumId,
                            'userid' => $postuserids[$k],
                            'refid' => $k,
                            'score' => $v,
                            'feedback' => $feedback
                        );
                        $insertGrade = new Grades();
                        $insertGrade->insertForumDataInToGrade($grade);
                    }
                } else {
                    if (isset($existingscores[$k])) {
                        Grades::deleteByOnlyId($existingscores[$k]);
                    }
                }
            }
            if (isset($params['save']) && $params['save'] == 'Save Grades and View Previous')
            {
                return $this->redirect('post?page=' . $page . '&courseid=' . $courseId . '&forumid=' . $forumId . '&threadid=' . $params['prevth']);
            } else if (isset($params['save']) && $params['save'] == 'Save Grades and View Next') {
                return $this->redirect('post?page=' . $page . '&courseid=' . $courseId . '&forumid=' . $forumId . '&threadid=' . $params['nextth']);
            } else
            {
                return $this->redirect('thread?page=' . $page . '&cid=' . $courseId . '&forum=' . $forumId);
            }
        }

        $forumname = $forumData['name'];
        $postby = $forumData['postby'];
        $forumsettings = $forumData['settings'];
        $groupsetid = $forumData['groupsetid'];
        $sortby = $forumData['sortby'];
        $taglist = $forumData['taglist'];
        $enddate = $forumData['enddate'];
        $avail = $forumData['avail'];
        if (isset($studentId) && ($avail == 0 || ($avail == 1 && time() > $enddate))) {
            $this->setWarningFlash('This forum is closed.');
            return $this->redirect(AppUtility::getURLFromHome('course', 'course/course?cid' . $courseId));
        }
        $sessionId = $this->getSessionId();
        $sessionData = $this->getSessionData($sessionId);
        $allowmod = (($forumsettings & 2) == 2);
        $allowdel = ((($forumsettings & 4) == 4) || $isteacher);
        $dofilter = false;
        $now = time();
        $grpqs = '';
        if ($groupsetid > 0) {
            if (isset($params['ffilter'])) {
                $sessionData['ffilter' . $forumId] = $params['ffilter'];
                $this->writesessiondata($sessionData, $sessionId);
            }
            if (!$isteacher) {
                $studentGroup = Stugroups::getStuGrpDataForGradebook($currentUser['id'], $groupsetid);
                if (count($studentGroup) > 0) {
                    $groupid = $studentGroup['id'];
                } else {
                    $groupid = 0;
                }
                $dofilter = true;
            } else
            {
                if (isset($sessionData['ffilter' . $forumId]) && $sessionData['ffilter' . $forumId] > -1) {
                    $groupid = $sessionData['ffilter' . $forumId];
                    $dofilter = true;
                    $grpqs = "&grp=$groupid";
                } else {
                    $groupid = 0;
                }
            }

            if ($dofilter) {
                $limthreads = array();
                if ($isteacher || $groupid == 0)
                {
                    $threadData = Thread::getByStuGroupIdNonZero($groupid);
                } else {
                    $threadData = Thread::getByStuGroupId($groupid);
                }

                foreach ($threadData as $thread) {

                    $limthreads[] = $thread['id'];
                }

                if (count($limthreads) == 0) {
                    $limthreads = '0';
                }
            }
        } else {
            $groupid = 0;
        }

        if (isset($params['tagfilter']))
        {

            $sessionData['tagfilter' . $forumId] = stripslashes($params['tagfilter']);
            $this->writesessiondata($sessionData, $sessionId);
            $tagfilter =  $params['tagfilter'];

        }else if (isset($sessionData['tagfilter' . $forumId]) && $sessionData['tagfilter' . $forumId] != '')
        {
            $tagfilter = $sessionData['tagfilter' . $forumId];
        } else {
            $tagfilter = '';
        }

        if ($tagfilter != '')
        {
            $threadIds = ForumPosts::getThreadId($limthreads, $dofilter, $tagfilter);
            $limthreads = array();
            foreach ($threadIds as $threadId)
            {
                $limthreads[] = $threadId['threadid'];
            }

            if (count($limthreads) == 0) {
                $limthreads = '0';
            }
            $dofilter = true;
        }

        if (isset($params['search']) && trim($params['search']) != '')
        {
            $safesearch = $params['search'];
            $safesearch = str_replace(' and ', ' ', $safesearch);
            $searchterms = explode(" ", $safesearch);
            $searchlikes = "(imas_forum_posts.message LIKE '%" . implode("%' AND imas_forum_posts.message LIKE '%", $searchterms) . "%')";
            $searchlikes2 = "(imas_forum_posts.subject LIKE '%" . implode("%' AND imas_forum_posts.subject LIKE '%", $searchterms) . "%')";
            $searchlikes3 = "(imas_users.LastName LIKE '%" . implode("%' AND imas_users.LastName LIKE '%", $searchterms) . "%')";
            $searchedPost = ForumPosts::getBySearchText($isteacher, $now, $courseId, $searchlikes, $searchlikes2, $searchlikes3, $forumId, $limthreads, $dofilter, $params);
        }

        if (isset($params['markallread'])) {

            $readPost = ForumPosts::MarkAllRead($forumId, $dofilter, $limthreads);

            $now = time();
            foreach ($readPost as $row)
            {

                $views = ForumView::getId($row['threadid'], $currentUser['id']);

                if (count($views) > 0) {

                    ForumView::setLastview($views['id']);
                } else {

                    $forumViewArray = array(
                        'userid' => $currentUser['id'],
                        'postdate' => time()
                    );

                    $addView = new ForumView();
                    $addView->addView($row['threadid'], $forumViewArray);
                }
            }
        }

        $postData = ForumPosts::getMaxPostDate($dofilter, $limthreads, $forumId);

        $postcount = array();
        $maxdate = array();
        foreach ($postData as $post) {
            $postcount[$post['threadid']] = $post['postcount'] - 1;
            $maxdate[$post['threadid']] = $post['maxdate'];
        }
        $viewData = ForumView::getForumDataByUserId($currentUser['id'], $dofilter, $limthreads);

        $lastview = array();
        $flags = array();
        foreach ($viewData as $row) {
            $lastview[$row['threadid']] = $row['lastview'];
            if ($row['tagged'] == 1) {
                $flags[$row['threadid']] = 1;
            }
        }
        $flaggedlist = implode(',', array_keys($flags));
        //make new list
        $newpost = array();
        foreach (array_keys($maxdate) as $tid) {
            if (!isset($lastview[$tid]) || $lastview[$tid] < $maxdate[$tid]) {
                $newpost[] = $tid;
            }
        }
        if ($page == -1 && count($newpost) == 0)
        {
            $page = 1;
        } else if ($page == -2 && count($flags) == 0)
        {
                $this->setWarningFlash('No result found for limit to flagged');
            $page = 1;
        }
        $prevnext = '';
        if ($page > 0) {
            $countOfPostId = ForumPosts::getForumPostId($forumId, $limthreads, $dofilter);
        }
        if ($isteacher && $groupsetid > 0) {
            if (isset($sessionData['ffilter' . $forumId])) {
                $curfilter = $sessionData['ffilter' . $forumId];
            } else {
                $curfilter = -1;
            }
            $groupnames = array();
            $groupnames[0] = "Non-group-specific";
            $studentGroupData = Stugroups::findByGrpSetIdToManageSet($groupsetid);
            $grpnums = 1;
            foreach ($studentGroupData as $row) {
                if ($row['name'] == 'Unnamed group') {
                    $row['name'] .= " $grpnums";
                    $grpnums++;
                }
                $groupnames[$row['id']] = $row['name'];
            }
        }
        $postIds = ForumPosts::getPostIds($forumId, $dofilter, $page, $limthreads, $newpost, array_keys($flags));
        $postInformtion = ForumPosts::getPostDataForThread($forumId, $dofilter, $page, $limthreads, $newpost, array_keys($flags), $sortby, $threadsperpage);
        $course = Course::getById($courseId);
        $this->includeCSS(['dataTables.bootstrap.css', 'forums.css', 'dashboard.css']);
        $this->includeJS(['jquery.dataTables.min.js', 'dataTables.bootstrap.js', 'general.js?ver=012115', 'forum/thread.js?ver=' . time() . '']);
        $responseData = array('params' => $params, 'flags' => $flags, 'lastview' => $lastview, 'newpost' => $newpost, 'postInformtion' => $postInformtion, 'postIds' => $postIds, 'groupnames' => $groupnames, 'curfilter' => $curfilter,
            'dofilter' => $dofilter, 'groupsetid' => $groupsetid, 'isteacher' => $isteacher, 'countOfPostId' => $countOfPostId, 'cid' => $courseId, 'users' => $currentUser,
            'searchedPost' => $searchedPost, 'forumid' => $forumId,'tagfilter' => $tagfilter,'taglist' => $taglist, 'maxdate' => $maxdate, 'course' => $course, 'forumData' => $forumData, 'page' => $page, 'threadsperpage' => $threadsperpage, 'postcount' => $postcount);
        return $this->renderWithData('thread', $responseData);
    }

    /*
     * controller method for redirect to Move Thread page,This method is used to store moved thread data in database.
     */
    public function actionMoveThread()//only for teacher
    {
        $this->layout = 'master';
        $courseId = $this->getParamVal('courseId');
        $course = Course::getById($courseId);
        $threadId = $this->getParamVal('threadId');
        $forumId = $this->getParamVal('forumId');
        $forums = Forums::getByCourseId($courseId);
        $thread = ThreadForm::thread($forumId);
        $user = $this->getAuthenticatedUser();
        $forumArray = array();
        foreach ($forums as $key => $forum) {
            $tempArray = array
            (
                'forumId' => $forum->id,
                'forumName' => $forum->name,
                'courseId' => $forum->courseid,
            );
            array_push($forumArray, $tempArray);
        }
        if ($thread) {
            $threadArray = array();
            foreach ($thread as $data) {
                $tempArray = array(
                    'threadId' => $data['id'],
                    'forumIdData' => $data['forumid'],
                    'subject' => $data['subject'],
                    'parent' => $data['parent'],
                );
                array_push($threadArray, $tempArray);
            }
            if ($this->isPostMethod()) {
                $params = $this->getRequestParams();
                $moveType = $params['movetype'];
                $thread_Id = $params['threadId'];
                if ($moveType == AppConstant::NUMERIC_ONE) {
                    if (isset($params['thread-name'])) {
                        $moveThreadId = $params['thread-name'];
                        ForumPosts::updatePostMoveThread($thread_Id, $moveThreadId);
                        Thread::deleteThreadById($thread_Id);
                    }
                } else {
                    if ($params['forum-name']) {
                        $forum_Id = $params['forum-name'];
                        Thread::moveAndUpdateThread($forum_Id, $thread_Id);
                        ForumPosts::updateMoveThread($forum_Id, $thread_Id);
                    }
                }
                return $this->redirect('thread?cid='.$courseId.'&forum='.$forumId);
            }
            $this->includeCSS(['forums.css']);
            $this->includeJS(['forum/movethread.js']);
            $responseData = array('forums' => $forumArray, 'threads' => $threadArray, 'threadId' => $threadId, 'forumId' => $forumId, 'course' => $course, 'user' => $user);
            return $this->renderWithData('moveThread', $responseData);
        }
    }

    /*
     * controller method for redirect to modify post page with selected thread data and fetch modified thread from Modify page and store in database.
     */
    public function actionModifyPost()
    {
        $this->layout = 'master';
        $params = $this->getRequestParams();
        $this->guestUserHandler();
        $courseId = $this->getParamVal('courseId');
        $course = Course::getById($courseId);
        $currentUser = $this->getAuthenticatedUser();
        $userId = $currentUser['id'];
        $threadId = $this->getParamVal('threadId');
        $forumId = $this->getParamVal('forumId');
        $forumData = Forums::getById($forumId);
        $groupsetid = $forumData['groupsetid'];


        $teacherId = $this->isTeacher($currentUser['id'], $courseId);
        if ($teacherId) {
            $isteacher = true;
        } else {
            $isteacher = false;
        }

        $thread = ThreadForm::thread($forumId);
        if ($params['modify']=='new') {
            echo "Add Thread</div>\n";
            $line['subject'] = "";
            $line['message'] = "";
            $line['posttype'] = 0;
            $line['files'] = '';
            $line['tag'] = '';
            $curstugroupid = 0;
            $replyby = null;
            echo "<h2>Add Thread - \n";
            if (isset($params['quoteq'])) {
                require_once("../components/displayQuestion.php");
                $showa = false;
                $parts = explode('-',$params['quoteq']);
                $assessmentId = $parts[3];
                if (count($parts)==5) {
                    //wants to show ans
                    $result = AssessmentSession::getDataWithUserIdAssessment($userId,$assessmentId);
//                    AppUtility::dump($result);
//                    $query = "SELECT seeds,attempts,questions FROM imas_assessment_sessions WHERE userid='$userid' AND assessmentid='{$parts[3]}'";
//                    $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
                    $seeds = explode(',',mysql_result($result,0,0));
                    $seeds = $seeds[$parts[0]];
                    $attempts = explode(',',mysql_result($result,0,1));
                    $attempts = $attempts[$parts[0]];
                    $qs = explode(',',mysql_result($result,0,2));
                    $qid = intval($qs[$parts[0]]);
                    $query = "SELECT questionsetid,attempts,showans FROM imas_questions WHERE id=$qid";
                    $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
                    $parts[1] = mysql_result($result,0,0);
                    $allowedattempts = mysql_result($result,0,1);
                    $showans = mysql_result($result,0,2);

                    $query = "SELECT defattempts,deffeedback,displaymethod FROM imas_assessments WHERE id='{$parts[3]}'";
                    $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
                    list($displaymode,$defshowans) = explode('-',mysql_result($result,0,1));
                    if ($allowedattempts==9999) {
                        $allowedattempts = mysql_result($result,0,0);
                    }
                    if ($showans==0) {
                        $showans = $defshowans;
                    }
                    if ($attempts >= $allowedattempts) {
                        if ($showans=='F' || $showans=='J') {
                            $showa = true;
                        }
                    }

                }
                $message = displayq($parts[0],$parts[1],$parts[2],$showa,false,0,true);
                $message = printfilter(forcefiltergraph($message));

//                if (isset($CFG['GEN']['AWSforcoursefiles']) && $CFG['GEN']['AWSforcoursefiles'] == true) {
//                    $message = preg_replace_callback('|'.$imasroot.'/filter/graph/imgs/([^\.]*?\.png)|', function ($matches) {
//                        $curdir = rtrim(dirname(__FILE__), '/\\');
//                        return relocatefileifneeded($curdir.'/../filter/graph/imgs/'.$matches[1], 'gimgs/'.$matches[1]);
//                    }, $message);
//                }
//                print_r($message);die;
                $message = preg_replace('/(`[^`]*`)/',"<span class=\"AM\">$1</span>",$message);
                $line['message'] = '<p> </p><br/><hr/>'.$message;
                if (isset($parts[3])) {
                    $result = Assessments::getDataWithUserId(intval($parts[3]));
//                    $query = "SELECT name,itemorder FROM imas_assessments WHERE id='".intval($parts[3])."'";
//                    $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
                    $line['subject'] = 'Question about #'.($parts[0]+1).' in '.str_replace('"','&quot;',$result[0]['name']);
                    $itemorder = $result[0]['itemorder'];
                    $isgroupedq = false;
                    if (strpos($itemorder, '~')!==false) {
                        $itemorder = explode(',',$itemorder);
                        $icnt = 0;
                        foreach ($itemorder as $item) {
                            if (strpos($item,'~')===false) {
                                if ($icnt==$parts[0]) { break;}
                                $icnt++;
                            } else {
                                $pts = explode('|',$item);
                                if ($parts[0]<$icnt+$pts[0]) {
                                    $isgroupedq = true; break;
                                }
                                $icnt += $pts[0];
                            }
                        }
                    }
                    if ($groupsetid>0) {
                        if ($isteacher) {
                            if (isset($params['stugroup'])) {
                                $groupid = $params['stugroup'];
                            } else {
                                $groupid = 0;
                            }
                        }
                    }
                    if (!$isgroupedq) {
                        $result = ForumPosts::getDataByJoin($line['subject'], $forumId, $groupsetid, $isteacher, $groupid);
//                        $query = "SELECT ift.id FROM imas_forum_posts AS ifp JOIN imas_forum_threads AS ift ON ifp.threadid=ift.id AND ifp.parent=0 ";
//                        $query .= "WHERE ifp.subject='".addslashes($line['subject'])."' AND ift.forumid='$forumid'";
//                        if ($groupsetid >0 && !$isteacher) {
//                            $query .= " AND ift.stugroupid='$groupid'";
//                        }
//                        $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
                        if (count($result)>0) {
                            $notice =  '<span style="color:red;font-weight:bold">This question has already been posted about.</span><br/>';
                            $notice .= 'Please read and participate in the existing discussion.';
                            foreach ($result as $row ) {
                                $notice .=  "<br/><a href=\"posts.php?cid=$cid&forum=$forumid&thread={$row[0]}\">{$line['subject']}</a>";
                            }
                        }
                    }
                }
            } //end if quoteq
        }
        $threadArray = array();

        foreach ($thread as $data) {
            if (($data['id']) == $threadId) {
                $tempArray = array(
                    'threadId' => $data['threadid'],
                    'subject' => $data['subject'],
                    'message' => $data['message'],
                    'postType' => $data['posttype'],
                    'replyBy' => $data['replyby'],
                    'isANon' => $data['isanon'],
                    'files' => $data['files']
                );
                array_push($threadArray, $tempArray);
            }
        }

        $forumPostData = ForumPosts::getbyid($threadId);
        $threadCreatedUserData = User::getById($forumPostData[0]['userid']);
        if ($this->isPostMethod())
        {
            $files = ForumPosts::getFileDetails($params['threadId']);
            if ($files == '') {
                $files = array();
            } else {
                $files = explode('@@', $files['files']);
            }
            if ($params['file']) {
                foreach ($params['file'] as $i => $v) {
                    $files[2 * $i] = str_replace('@@', '@', $v);

                }
                for ($i = count($files) / 2 - 1; $i >= 0; $i--) {
                    if (isset($params['fileDel'][$i])) {
                        if ($this->deleteForumFile($files[2 * $i + 1])) {
                            array_splice($files, 2 * $i, 2);
                        }
                    }
                }
            }
            if ($_FILES['file-0'])
            {
                $j = 0;
                $uploadDir = AppConstant::UPLOAD_DIRECTORY . 'forumFiles/';

                $badExtensions = array(".php", ".php3", ".php4", ".php5", ".bat", ".com", ".pl", ".p");
                while (isset($_FILES['file-' . $j]) && is_uploaded_file($_FILES['file-' . $j]['tmp_name'])) {
                    $uploadFile = $uploadDir . basename($_FILES['file-' . $j]['name']);
                    $userFileName = basename($_FILES['file-' . $j]['name']);

                    if (trim($params['description-' . $j]) == '') {
                        $params['description-' . $j] = $userFileName;
                    }
                    $params['description-' . $j] = str_replace('@@', '@', $params['description-' . $j]);
                    $extension = strtolower(strrchr($userFileName, "."));
                    if (!in_array($extension, $badExtensions)) {
                        $files[] = stripslashes($params['description-' . $j]);
                        $files[] = $userFileName;
                        move_uploaded_file($_FILES['file-' . $j]['tmp_name'], $uploadFile);
                    } else {
                        $this->setErrorFlash("File with (.php,.php3,.php4,.php5,.bat,.com,.pl,.p) are not allowed");
                        return $this->redirect('add-new-thread?forumid=' . $forumId . '&cid=' . $courseId);
                    }
                    $j++;
                }
                $fileName = implode('@@', $files);
            }
            if (strlen(trim($params['subject'])) > AppConstant::NUMERIC_ZERO)
            {
                $threadIdOfPost = ForumPosts::modifyPost($params, $fileName);
                $contentTrackRecord = new ContentTrack();
                if ($currentUser->rights == AppConstant::STUDENT_RIGHT) {
                    $contentTrackRecord->insertForumData($currentUser->id, $courseId, $forumId, $threadId, $threadIdOfPost, $type = AppConstant::NUMERIC_TWO);
                }
                if($params['from'] == 1)
                {
                    $this->redirect('thread?cid='.$courseId.'&forum='.$forumId);
                }else
                {
                    $this->redirect('list-post-by-name?page=1&cid='.$courseId.'&forumid='.$forumId);
                }

            }
        }
//        $thread[0]['message'] = $message;
//AppUtility::dump($thread[0]['message']);
        $this->setReferrer();
        $this->includeCSS(['forums.css']);
        $this->includeJS(["editor/tiny_mce.js", 'editor/tiny_mce_src.js', 'general.js', 'forum/modifypost.js']);
        $responseData = array('threadId' => $threadId,'message'=> $message,  'forumId' => $forumId, 'course' => $course, 'thread' => $threadArray, 'currentUser' => $currentUser, 'threadCreatedUserData' => $threadCreatedUserData, 'forumData' => $forumData, 'forumPostData' => $forumPostData);
        return $this->renderWithData('modifyPost', $responseData);
    }

    function deleteForumFile($file)
    {
        if ($GLOBALS['filehandertype'] == 's3') {
            /*for amazon*/
        } else {
            $base = $uploadDir = AppConstant::UPLOAD_DIRECTORY . 'forumFiles/';
            if (unlink($base . "$file")) {
                return true;
            } else {
                return false;
            }
        }
    }

    /*
    * Controller Action To Redirect To Post Page
    */
    public function actionPost()
    {
        $this->guestUserHandler();
        $this->layout = 'master';
        $currentUser = $this->getAuthenticatedUser();
        $courseId = $this->getParamVal('courseid');
        $course = Course::getById($courseId);
        $threadId = $this->getParamVal('threadid');
        $forumId = $this->getParamVal('forumid');
        $forumData = Forums::getById($forumId);
        $allThreadIds = Thread::getAllThread($forumId);
        $prevNextValueArray = Thread::checkPreOrNextThreadByForunId($forumId);
        $isNew = ForumView::getById($threadId, $currentUser['id']);
        $tagValue = $isNew[0]['tagged'];
        $isTeacher = $this->isTeacher($currentUser['id'], $courseId);
        $isTutor = $this->isTutor($currentUser['id'], $courseId);
        $canViewAll = false;
        if ($isTeacher || $isTutor) {
            $canViewAll = true;
        }
        $atLeastOneThread = ForumPosts::checkLeastOneThread($forumId, $currentUser['id']);
        $FullThread = ForumPosts::getbyid($threadId);
        $data = array();
        $titleCountArray = array();
        foreach ($FullThread as $postData) {
            $this->children[$postData['parent']][] = $postData['id'];
            $username = User::getById($postData['userid']);
            $forumName = Forums::getById($postData['forumid']);
            $titleLevel = AppUtility::calculateLevel($postData['subject']);
            $likeImage = ForumLike::checkStatus($postData['id'], $currentUser);
            $count = new ForumLike();
            $likeCnt = $count->CalculateCount($postData['id']);
            $studentCount = AppConstant::NUMERIC_ZERO;
            $teacherCount = AppConstant::NUMERIC_ZERO;
            $isReplies = AppConstant::NUMERIC_ZERO;
            $threadReplies = ForumPosts::isThreadHaveReply($postData['id']);
            if ($threadReplies) {
                $isReplies = AppConstant::NUMERIC_ONE;
            }
            foreach ($likeCnt as $like) {
                $Rights = User::getById($like['userid']);
                if ($Rights->rights == AppConstant::STUDENT_RIGHT) {
                    $studentCount = $studentCount + AppConstant::NUMERIC_ONE;
                } elseif ($Rights->rights >= AppConstant::TEACHER_RIGHT) {
                    $teacherCount = $teacherCount + AppConstant::NUMERIC_ONE;
                }
                $tempArray = array(
                    'postId' => $like['postid'],
                    'studentCount' => $studentCount,
                    'teacherCount' => $teacherCount,
                );
            }
            if ($postData['parent'] == AppConstant::NUMERIC_ZERO) {
                $replyBy = $postData['replyby'];
            }
            array_push($titleCountArray, $tempArray);
            $tempArray = array();
            $tempArray['id'] = $postData['id'];
            $tempArray['threadId'] = $postData['threadid'];
            $tempArray['forumIdData'] = $postData['forumid'];
            $tempArray['subject'] = $titleLevel['title'];
            $tempArray['forumName'] = ucfirst($forumName->name);
            $tempArray['postdate'] = AppController::customizeDate($postData->postdate);
            $tempArray['postType'] = $postData['posttype'];
            $tempArray['name'] = AppUtility::getFullName($username->FirstName, $username->LastName);
            $tempArray['userId'] = $username->id;
            $tempArray['userRights'] = $username->rights;
            $tempArray['userId'] = $username->id;
            $tempArray['settings'] = $forumName->settings;
            $tempArray['hasImg'] = $username->hasuserimg;
            $tempArray['likeImage'] = $likeImage;
            $tempArray['studentCount'] = $studentCount;
            $tempArray['teacherCount'] = $teacherCount;
            $tempArray['likeCnt'] = count($likeCnt);
            $tempArray['lastView'] = $isNew[AppConstant::NUMERIC_ZERO]['lastview'];
            $tempArray['message'] = $postData['message'];
            $tempArray['level'] = $titleLevel['level'];
            $tempArray['parent'] = $postData['parent'];
            $tempArray['files'] = $postData['files'];
            $tempArray['fileType'] = $forumData['forumtype'];
            $tempArray['isReplies'] = $isReplies;
            if (($postData['parent'] != AppConstant::NUMERIC_ZERO) && (substr($postData['subject'], AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_FOUR) !== 'Re: ')) {
                $this->threadLevel = AppConstant::NUMERIC_ONE;
                $this->calculatePostLevel($postData);
                $tempArray['level'] = $this->threadLevel;
            }
            $this->postData[$postData['id']] = $tempArray;
        }
        Thread::saveViews($threadId);
        $viewsData = new ForumView();
        $viewsData->updateData($threadId, $currentUser);
        $this->createChild($this->children[0]);
        $Count = new ForumLike();
        $likeCount = $Count->findCOunt($threadId);
        $myLikes = $Count->UserLikes($threadId, $currentUser);
        $this->setReferrer();
        foreach ($this->totalPosts as $key => $threadArray) {
            if ($threadArray) {
                foreach ($this->totalPosts as $singleThread) {
                    if ($threadArray['parent'] == $singleThread['id']) {
                        if (substr($threadArray['subject'], AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_TWO) !== 'Re') {
                            $moveThreadSubject = $threadArray['id'];
                            $threadArray['level'] = $threadArray['level'] + $singleThread['level'];
                        }
                        if ($threadArray['parent'] == $moveThreadSubject) {
                            $threadArray['level'] = $threadArray['level'] + $singleThread['level'];
                        }
                    }
                }
            }
            $FinalPostArray[$key] = $threadArray;
            if ($threadArray['parent'] == AppConstant::NUMERIC_ZERO) {
                $postType = $threadArray['postType'];
            }
            if ($threadArray['userId'] == $currentUser['id']) {
                $studentPosts[] = $threadArray['id'];
            }
        }
        $allowReply = AppConstant::NUMERIC_ZERO;
        if ($forumData['replyby'] == AppConstant::ALWAYS_TIME || $forumData['replyby'] > time()) {
            $allowReply = AppConstant::NUMERIC_ONE;
        }
        if ($postType == AppConstant::NUMERIC_THREE && $currentUser['rights'] == AppConstant::NUMERIC_TEN) {
            $threadOnce = AppConstant::NUMERIC_ZERO;
            foreach ($FinalPostArray as $array) {
                global $parentArray;
                $this->parentList($array['id']);
                $isShow = false;
                if ($parentArray) {
                    foreach ($studentPosts as $studentPost) {
                        if (in_array($studentPost, $parentArray)) {
                            $isShow = true;
                            break;
                        }
                    }
                    $parentArray = null;
                }

                if ($array['parent'] == AppConstant::NUMERIC_ZERO && $threadOnce == AppConstant::NUMERIC_ZERO) {
                    $threadOnce = AppConstant::NUMERIC_ONE;
                    array_push($data, $array);
                } else if ($array['userId'] == $currentUser['id'] || in_array($array['parent'], $studentPosts)) {
                    array_push($data, $array);
                } else if ($isShow == true) {
                    array_push($data, $array);
                }
            }
        } else {
            $data = $FinalPostArray;
        }

        $this->includeCSS(['forums.css']);
        $this->includeJS(["general.js", "forum/post.js?ver=<?php echo time() ?>"]);
        $responseData = array('atLeastOneThread' => $atLeastOneThread, 'postdata' => $data, 'allowReply' => $allowReply, 'canViewAll' => $canViewAll, 'forumData' => $forumData, 'course' => $course, 'currentUser' => $currentUser, 'forumId' => $forumId, 'threadId' => $threadId, 'tagValue' => $tagValue, 'prevNextValueArray' => $prevNextValueArray, 'likeCount' => $likeCount, 'mylikes' => $myLikes, 'titleCountArray' => $titleCountArray, 'allThreadIds' => $allThreadIds, 'replyBy' => $replyBy);
        return $this->render('post', $responseData);
    }

    public function calculatePostLevel($data)
    {
        $parentData = ForumPosts::getParentDataByParentId($data['parent']);
        if ($parentData['parent'] == AppConstant::NUMERIC_ZERO) {
            return AppConstant::NUMERIC_ZERO;
        } else {
            $this->threadLevel++;
            $this->calculatePostLevel($parentData);
        }
    }

    public function createChild($childArray, $arrayKey = AppConstant::NUMERIC_ZERO)
    {
        $this->children = AppUtility::removeEmptyAttributes($this->children);
        foreach ($childArray as $superKey => $child) {
            array_push($this->totalPosts, $this->postData[$child]);
            unset($this->children[$arrayKey][$superKey]);
            if (isset($this->children[$child])) {
                return $this->createChild($this->children[$child], $child);
            } else {
                continue;
            }
        }
        if (count($this->children)) {
            $this->createChild($this->children[key($this->children)], key($this->children));
        }
    }

    /*
     * controller ajax method for fetch select as remove thread from Thread page and remove from database.
     */
    public function actionMarkAsRemoveAjax()
    {
        $params = $this->getRequestParams();
        $threadId = $params['threadId'];
        $parentId = $params['parentId'];
        $deleteThreadData = ForumPosts::getPostById($threadId);
        ForumPosts::removeThread($threadId, $parentId);
        if ($parentId == AppConstant::NUMERIC_ZERO) {
            ForumThread::removeThread($threadId);
            ForumView::removeThread($threadId);
        } else {
            ForumPosts::updateParentId($threadId, $deleteThreadData['parent']);
        }
        return $this->successResponse($parentId);
    }

    /*
     * Controller Action To Reply To A Post
     */
    public function actionReplyPost()
    {
        $this->layout = 'master';
        $this->guestUserHandler();
        $isPost = $this->getParamVal('listbypost');
        $courseId = $this->getParamVal('courseid');
        $course = Course::getById($courseId);
        $threadArray = array();
        $forumId = $this->getParamVal('forumid');
        $forumData = Forums::getById($forumId);
        $Id = $this->getParamVal('id');
        $threadId = $this->getParamVal('threadId');
        $userData = $this->getAuthenticatedUser();
        $threadData = ForumPosts::getbyidpost($Id);
        $contentTrackRecord = new ContentTrack();
        if ($userData->rights == AppConstant::STUDENT_RIGHT) {
            $contentTrackRecord->insertForumData($userData->id, $courseId, $forumId, $Id, $threadId, $type = AppConstant::NUMERIC_ONE);
        }
        foreach ($threadData as $data) {
            $tempArray = array
            (
                'subject' => $data['subject'],
                'userName' => $data->user->FirstName . ' ' . $data->user->LastName,
                'message' => $data['message'],
                'forumType' => $forumData['forumtype'],
                'files' => $data['files'],
                'postDate' => AppController::customizeDate($data['postdate']),
            );
            array_push($threadArray, $tempArray);
        }
        if ($this->isPostMethod()) {
            $files = array();
            $params = $this->getRequestParams();
            if ($_FILES['file-0']) {
                $j = 0;
                $uploadDir = AppConstant::UPLOAD_DIRECTORY . 'forumFiles/';
                $badExtensions = array(".php", ".php3", ".php4", ".php5", ".bat", ".com", ".pl", ".p");
                while (isset($_FILES['file-' . $j]) && is_uploaded_file($_FILES['file-' . $j]['tmp_name'])) {
                    $uploadFile = $uploadDir . basename($_FILES['file-' . $j]['name']);
                    $userFileName = preg_replace('/[^\w\.]/', '', basename($_FILES['file-' . $j]['name']));
                    if (trim($params['description-' . $j]) == '') {
                        $params['description-' . $j] = $userFileName;
                    }
                    $params['description-' . $j] = str_replace('@@', '@', $params['description-' . $j]);
                    $extension = strtolower(strrchr($userFileName, "."));
                    if (!in_array($extension, $badExtensions)) {
                        $files[] = stripslashes($params['description-' . $j]);
                        $files[] = $userFileName;
                        move_uploaded_file($_FILES['file-' . $j]['tmp_name'], $uploadFile);
                    } else {
                        $this->setErrorFlash("File with (.php,.php3,.php4,.php5,.bat,.com,.pl,.p) are not allowed");
                        return $this->redirect('reply-post?forumid=' . $forumId . '&cid=' . $courseId);
                    }
                    $j++;
                }
            }
            $fileName = implode('@@', $files);
            $isPost = $params['isPost'];
            $user = $this->getAuthenticatedUser();
            $reply = new ForumPosts();
            $reply->createReply($params, $user, $fileName);
            if (isset($isPost)) {
                return $this->redirect('list-post-by-name?cid=' . $params['courseid'] . '&forumid=' . $params['forumid']);
            } else {
                return $this->redirect('post?courseid=' . $params['courseid'] . '&threadid=' . $params['threadId'] . '&forumid=' . $params['forumid']);
            }

        }
        $this->includeCSS(['forums.css']);
        $this->includeJS(['editor/tiny_mce.js', 'editor/tiny_mce_src.js', 'general.js', 'forum/replypost.js']);
        $responseData = array('reply' => $threadArray, 'course' => $course, 'forumId' => $forumId, 'threadId' => $threadId, 'parentId' => $Id, 'isPost' => $isPost, 'currentUser' => $userData);
        return $this->renderWithData('replyPost', $responseData);
    }

    /*
     * Controller Action To Redirect To New Thread Page
     */
    public function actionAddNewThread()
    {
        $this->layout = 'master';
        $user = $this->getAuthenticatedUser();
        $userId = $this->getUserId();
        $rights = $user->rights;
        $forumId = $this->getParamVal('forumid');
        $courseId = $this->getParamVal('cid');
        $course = Course::getById($courseId);
        $forumData = Forums::getById($forumId);
        $files = array();
        if ($this->isPostMethod()) {

            $params = $this->getRequestParams();
            $postType = AppConstant::NUMERIC_ZERO;
            $alwaysReplies = null;
            $isNonValue = AppConstant::NUMERIC_ZERO;
            if ($user->rights > AppConstant::NUMERIC_TEN) {
                $postType = $params['post-type'];
                $date = strtotime($params['endDate'] . ' ' . $params['startTime']);
            } else {
                $isNonValue = $params['settings'];
            }
            if ($_FILES['file-0']) {
                $j = 0;
                $uploadDir = AppConstant::UPLOAD_DIRECTORY . 'forumFiles/';
                $badExtensions = array(".php", ".php3", ".php4", ".php5", ".bat", ".com", ".pl", ".p");
                while (isset($_FILES['file-' . $j])) {
                    $uploadFile = $uploadDir . basename($_FILES['file-' . $j]['name']);
                    $userFileName = preg_replace('/[^\w\.]/', '', basename($_FILES['file-' . $j]['name']));
                    if (trim($params['description-' . $j]) == '') {
                        $params['description-' . $j] = $userFileName;
                    }
                    $params['description-' . $j] = str_replace('@@', '@', $params['description-' . $j]);
                    $extension = strtolower(strrchr($userFileName, "."));
                    if (!in_array($extension, $badExtensions)) {
                        $files[] = stripslashes($params['description-' . $j]);
                        $files[] = $userFileName;
                        move_uploaded_file($_FILES['file-' . $j]['tmp_name'], $uploadFile);
                    } else {
                        $this->setErrorFlash("File with (.php,.php3,.php4,.php5,.bat,.com,.pl,.p) are not allowed");
                        return $this->redirect('add-new-thread?forumid=' . $forumId . '&cid=' . $courseId);
                    }
                    $j++;
                }
            }

            $fileName = implode('@@', $files);
            $alwaysReplies = $params['always-replies'];
            if ($user['rights'] == AppConstant::STUDENT_RIGHT) {
                $alwaysReplies = 0;
                $postType = 0;
                $isNonValue = 0;

            }
            $newThread = new ForumPosts();
            $threadId = $newThread->createThread($params, $user->id, $postType, $alwaysReplies, $date, $isNonValue, $fileName);
            $newThread = new ForumThread();
            $newThread->createThread($params, $user->id, $threadId);
            $views = new ForumView();
            $views->createThread($user->id, $threadId);
            $contentTrackRecord = new ContentTrack();
            if ($this->getAuthenticatedUser()->rights == AppConstant::STUDENT_RIGHT) {
                $contentTrackRecord->insertForumData($user->id, $params['cid'], $params['forumid'], $threadId, $threadIdOfPost = null, $type = AppConstant::NUMERIC_ZERO);
            }
            return $this->redirect('thread?cid='.$params['cid'].'&forum='.$params['forumid']);

        }
        $this->includeCSS(['forums.css']);
        $this->includeJS(['editor/tiny_mce.js', 'editor/tiny_mce_src.js', 'general.js', 'forum/addnewthread.js']);
        $responseData = array('forumData' => $forumData, 'course' => $course, 'userId' => $userId, 'rights' => $rights);
        return $this->renderWithData('addNewThread', $responseData);
    }

    /*Controller Action To Toggle The Flag Image On Click*/
    public function actionChangeImageAjax()
    {
        $params = $this->getRequestParams();
        $rowId = $params['rowId'];
        $userId = $params['userId'];
        if ($rowId == AppConstant::NUMERIC_NEGATIVE_ONE) {
            $threadId = $params['threadId'];
            ForumView::deleteByUserIdAndThreadId($threadId, $userId);
        } else {
            $updateView = new ForumView();
            $updateView->updateFlagValue($rowId, $userId);
        }
        return $this->successResponse();
    }
    /*
     * Controller Action To Search Post Of That Forum
     */
    public function actionGetOnlyPostAjax()
    {
        $this->guestUserHandler();
        $params = $this->getRequestParams();
        $search = $params['search'];
        $forumId = $params['forumid'];
        $query = ForumForm::byAllpost($search);
        if ($query) {
            $searchPost = array();
            foreach ($query as $data) {
                if ($forumId == $data['forumid']) {
                    $username = User::getById($data['userid']);
                    $postdate = Thread::getById($data['threadid']);
                    $forumName = Forums::getById($data['forumid']);
                    $tempArray = array
                    (
                        'forumIdData' => $data['forumid'],
                        'threadId' => $data['threadid'],
                        'subject' => $data['subject'],
                        'views' => $data['views'],
                        'forumName' => ucfirst($forumName->name),
                        'postdate' => AppController::customizeDate($postdate->lastposttime),
                        'name' => ucfirst($username->FirstName) . ' ' . ucfirst($username->LastName),
                        'message' => $data['message'],
                    );
                    array_push($searchPost, $tempArray);
                }
            }
            $this->includeJS(['forum/forum.js', 'forum/thread.js']);
            $responseData = array('data' => $searchPost);
            return $this->successResponse($responseData);
        } else {
            return $this->terminateResponse('No Data');
        }
    }

    /*
     *
     */
    public function actionListPostByName()
    {
        $this->guestUserHandler();
        $this->layout = 'master';
        $userRights = $this->getAuthenticatedUser()->rights;
        $userId = $this->getAuthenticatedUser()->id;
        $params = $this->getRequestParams();
        $courseId = $this->getParamVal('cid');
        $course = Course::getById($courseId);
        $sort = AppConstant::DESCENDING;
        $forumId = $params['forumid'];
        $forumName = Forums::getById($forumId);
        $orderBy = 'postdate';
        $thread = ThreadForm::postByName($forumId, $sort, $orderBy);
        $page = $params['page'];
        $read = $params['read'];

        $teacherid = $this->isTeacher($userId,$courseId);
        if (isset($teacherid)) {
            $isteacher = true;
        } else {
            $isteacher = false;
        }

        if(isset($params['read']) && $read == 1)
        {

            $now = time();
            $readThreadId = ForumPosts::MarkAllRead($forumId);
            foreach ($readThreadId as $data) {
                $viewsData = new ForumView();
                $viewsData->updateDataForPostByName($data['threadid'], $userId, $now);
            }
        }
        $forumData = Forums::getById($forumId);
        $forumsettings = $forumData['settings'];
        $replyby = $forumData['replyby'];
        $defdisplay = $forumData['defdisplay'];
        $forumname = $forumData['name'];
        $pointspos = $forumData['points'];
        $rubric = $forumData['rubric'];
        $tutoredit = $forumData['tutoredit'];
        $groupsetid = $forumData['groupsetid'];
        $allowanon = (($forumsettings&1)==1);

        $allowmod = ($isteacher || (($forumsettings&2)==2));
        $allowdel = ($isteacher || (($forumsettings&4)==4));
        $postbeforeview = (($forumsettings&16)==16);
        $haspoints = ($pointspos>0);

        $canviewall = (isset($teacherid) || isset($tutorid));
        $caneditscore = (isset($teacherid) || (isset($tutorid) && $tutoredit==1));
        $canviewscore = (isset($teacherid) || (isset($tutorid) && $tutoredit<2));
        $allowreply = ($canviewall || (time()<$replyby));

        $caller = "byname";
        $this->includeCSS(['forums.css']);

        if ($haspoints && $caneditscore && $rubric != 0) {
            $this->includeJS(['gradebook/rubric.js']);
//            require("../includes/rubric.php");
        }

        if (!$canviewall && $postbeforeview)
        {
            $forumPost = ForumPosts::checkLeastOneThread($forumId,$userId);
            if (count($forumPost) == 0)
            {
                $this->setWarningFlash(AppConstant::BLOCK_POST);
                return $this->redirect($this->goHome());
            }
        }

        if ($haspoints && $caneditscore && $rubric != 0)
        {
            $rubricData = Rubrics::getById($rubric);

            if (count($rubricData) > 0)
            {
                $rubricDataRow = array(
                    '0' => $rubricData['id'],
                    '1' => $rubricData['rubrictype'],
                    '2' => $rubricData['rubric'],
                );
            }
        }

        $scores = array();
        $feedback = array();
        if ($haspoints)
        {
            $grades = Grades::getByGradeTypeIdAndGradeType('forum',$forumId);
            foreach($grades as $grade)
            {
                $scores[$grade['refid']] = $grade['score'];
                $feedback[$grade['refid']] = $grade['feedback'];
            }
        }

        $dofilter = false;
        if (!$canviewall && $groupsetid>0)
        {
            $stuGroup = StuGroupMembers::getStudGroupAndStudGroupMemberData($userId,$groupsetid);
            if (count($stuGroup) > 0)
            {
                $groupid = $stuGroup['id'];
            } else {
                $groupid=0;
            }
            $dofilter = true;
            $threadIds = Thread::getByStuGroupId($groupid);
            $limthreads = array();
            foreach ($threadIds as $thread)
            {
                $limthreads[] = $thread['id'];
            }
            if (count($limthreads)==0) {
                $limthreads = '0';
            } else {
                $limthreads = implode(',',$limthreads);
            }
        }
        $posts = ForumPosts::getPosts($userId,$forumId,$limthreads,$dofilter);
        if ($thread) {
            $this->includeCSS(['forums.css']);
            $this->includeJS(['general.js','forum/listpostbyname.js','gradebook/manageaddgrades.js']);
            $status = AppConstant::NUMERIC_ONE;
            $responseData = array('posts' => $posts,'pointspos' => $pointspos,'rubricData' => $rubricData,'forumname' => $forumname,'rubricDataRow' => $rubricDataRow,
                'rubric' => $rubric,'scores' => $scores,'haspoints' => $haspoints,'caneditscore' => $caneditscore,'page' => $page,'forumId' => $forumId, 'forumName' => $forumName, 'course' => $course, 'status' => $status,
                'allowmod' => $allowmod,'allowdel' => $allowdel,'allowreply' => $allowreply,'userRights' => $userRights, 'canviewscore' => $canviewscore,'isteacher' => $isteacher,'userId' => $userId);
            return $this->renderWithData('listPostByName', $responseData);
        }
    }

    public function actionLikePostAjax()
    {
        $this->guestUserHandler();
        $userId = $this->getAuthenticatedUser()->id;
        $params = $this->getRequestParams();
        $like = $params['like'];
        if ($this->isPostMethod()) {
            if ($like == AppConstant::NUMERIC_ZERO) {
                $like = new ForumLike();
                $like->InsertLike($params, $userId);
            } elseif ($like == AppConstant::NUMERIC_ONE) {
                $like = new ForumLike();
                $like->DeleteLike($params, $userId);
            }
        }
        return $this->successResponse();
    }

    public function actionDataLikePostAjax()
    {
        $this->guestUserHandler();
        $params = $this->getRequestParams();
        $count = new ForumLike();
        $displayCountData = $count->checkCount($params);
        $countDataArray = array();
        foreach ($displayCountData as $data) {
            $user = User::getById($data->userid);
            $tempArray = array('id' => $data->userid, 'userName' => AppUtility::getFullName($user->FirstName, $user->LastName));
            array_push($countDataArray, $tempArray);
        }
        $responseData = array('displayCountData' => $countDataArray);
        return $this->successResponse($responseData);
    }

    public function actionAddForum()
    {
        $this->guestUserHandler();
        $this->layout = 'master';
        $params = $this->getRequestParams();
        $block = $this->getParamVal('block');
        $user = $this->getAuthenticatedUser();
        $courseId = $params['cid'];
        $course = Course::getById($courseId);
        $modifyForumId = $params['id'];
        $groupNames = StuGroupSet::getByCourseId($courseId);
        $key = AppConstant::NUMERIC_ZERO;
        $teacherId = $this->isTeacher($user['id'], $courseId);
        $this->noValidRights($teacherId);
        foreach ($groupNames as $group) {
            $groupNameId[$key] = $group['id'];
            $groupNameLabel[$key] = AppConstant::USE_GROUP_SET . $group['name'];
            $key++;
        }
        if (isset($params['tb'])) {
            $filter = $params['tb'];
        } else {
            $filter = 'b';
        }
        $key = AppConstant::NUMERIC_ZERO;
        $gbCatsData = GbCats::getByCourseId($courseId);
        foreach ($gbCatsData as $singleGbCatsData) {
            $gbCatsId[$key] = $singleGbCatsData['id'];
            $gbCatsLabel[$key] = $singleGbCatsData['name'];
            $key++;
        }
        $key = AppConstant::NUMERIC_ZERO;
        $rubricsId = array(0);
        $rubricsLabel = array('None');
        $rubrics = Rubrics::getIdAndName($user['id'], $user['groupid']);
        foreach ($rubrics as $rubric) {
            $rubricsId[$key] = $rubric['id'];
            $rubricsLabel[$key] = $rubric['name'];
            $key++;
        }
        $OutcomesData = Outcomes::getByCourseId($courseId);
        $key = AppConstant::NUMERIC_ONE;
        $pageOutcomes = array();
        if ($OutcomesData) {
            foreach ($OutcomesData as $singleData) {
                $pageOutcomes[$singleData['id']] = $singleData['name'];
                $key++;
            }
        }
        $pageOutcomesList = array();
        $query = $course['outcomes'];
        $outcomeArray = unserialize($query);
        global $outcomesList;
        $this->flatArray($outcomeArray);
        if ($outcomesList) {
            foreach ($outcomesList as $singlePage) {
                array_push($pageOutcomesList, $singlePage);
            }
        }
        $pageTitle = AppConstant::ADD_FORUM;
        $saveTitle = AppConstant::CREATE_FORUM;
        $defaultValue = array(
            'startDate' => AppConstant::NUMERIC_ONE,
            'replyBy' => AppConstant::ALWAYS_TIME,
            'postBy' => AppConstant::ALWAYS_TIME,
            'endDate' => AppConstant::NUMERIC_ONE,
            'hasSubScrip' => false,
            'hasGroupThreads' => AppConstant::NUMERIC_ZERO,
            'postTag' => 'FP',
            'replyTag' => 'FR',
            'cntInGb' => AppConstant::NUMERIC_ZERO,
            'points' => AppConstant::NUMERIC_ZERO,
            'forumType' => AppConstant::NUMERIC_ZERO,
            'tagList' => '',
            'rubric' => AppConstant::NUMERIC_ZERO,
            'groupSetId' => AppConstant::NUMERIC_ZERO,
            'gbCat' => AppConstant::NUMERIC_ZERO,
            'sortBy' => AppConstant::NUMERIC_ZERO,
            'tutorEdit' => AppConstant::NUMERIC_ZERO,
            'sDate' => date("m/d/Y"),
            'sTime' => date('g:i a'),
            'eDate' => date("m/d/Y", strtotime("+1 week")),
            'eTime' => date('g:i a'),
            'postDate' => date("m/d/Y", strtotime("+1 week")),
            'replyByDate' => date("m/d/Y", strtotime("+1 week")),
            'avail' => AppConstant::NUMERIC_ONE,
            'defDisplay' => AppConstant::NUMERIC_ZERO,
            'replyByTime' => date('g:i a'),
            'postByTime' => date('g:i a'),
            'outcomes' => ' ',
            'isOutcomes' => $course['outcomes'],
        );
        if ($modifyForumId) {
            $pageTitle = 'Modify Forum';
            $saveTitle = AppConstant::SAVE_BUTTON;
            $forumData = Forums::getById($modifyForumId);
            if ($forumData['groupsetid'] > AppConstant::NUMERIC_ZERO) {
                $threadData = Thread::getByForumId($modifyForumId);
                if (count($threadData) > AppConstant::NUMERIC_ZERO) {
                    $hasGroupThreads = true;
                } else {
                    $hasGroupThreads = false;
                }
            }
            $startDate = $forumData['startdate'];
            $endDate = $forumData['enddate'];
            if ($startDate != AppConstant::NUMERIC_ZERO) {
                $sDate = AppUtility::tzdate("m/d/Y", $startDate);
                $sTime = AppUtility::tzdate("g:i a", $startDate);
                $startDate = AppConstant::NUMERIC_ONE;
            } else {
                $sDate = date('m/d/Y');
                $sTime = date('g:i a');
            }

            if ($endDate != AppConstant::ALWAYS_TIME) {
                $eDate = AppUtility::tzdate("m/d/Y", $endDate);
                $eTime = AppUtility::tzdate("g:i a", $endDate);
                $endDate = AppConstant::NUMERIC_ONE;
            } else {
                $eDate = date("m/d/Y", strtotime("+1 week"));
                $eTime = date('g:i a');
            }
            $allNon = (($forumData['settings'] & AppConstant::NUMERIC_ONE) == AppConstant::NUMERIC_ONE);
            if (!$allNon) {
                $allNon = AppConstant::NUMERIC_ZERO;
            }
            $allMod = (($forumData['settings'] & AppConstant::NUMERIC_TWO) == AppConstant::NUMERIC_TWO);
            if (!$allMod) {
                $allMod = AppConstant::NUMERIC_ZERO;
            }
            $allDel = (($forumData['settings'] & AppConstant::NUMERIC_FOUR) == AppConstant::NUMERIC_FOUR);
            if (!$allDel) {
                $allDel = AppConstant::NUMERIC_ZERO;
            }
            $allLikes = (($forumData['settings'] & AppConstant::NUMERIC_EIGHT) == AppConstant::NUMERIC_EIGHT);
            if (!$allLikes) {
                $allLikes = AppConstant::NUMERIC_ZERO;
            }
            $viewAfterPost = (($forumData['settings'] & AppConstant::SIXTEEN) == AppConstant::SIXTEEN);
            if (!$viewAfterPost) {
                $viewAfterPost = AppConstant::NUMERIC_ZERO;
            }
            $subscriptionsData = ForumSubscriptions::getByForumIdUserId($modifyForumId, $user['id']);
            if (count($subscriptionsData) > AppConstant::NUMERIC_ZERO) {
                $hasSubScrip = true;
            }
            if ($forumData['replyby'] > AppConstant::NUMERIC_ZERO && $forumData['replyby'] < AppConstant::ALWAYS_TIME) {
                $replyBy = AppConstant::NUMERIC_ONE;
                $forumData['replyby'] = AppUtility::tzdate("m/d/Y", $forumData['replyby']);
                $replyByTime = AppUtility::tzdate("g:i a", $forumData['replyby']);
            } else {
                $replyBy = $forumData['replyby'];
                $forumData['replyby'] = date("m/d/Y", strtotime("+1 week"));
                $replyByTime = date('g:i a');
            }
            if ($forumData['postby'] > AppConstant::NUMERIC_ZERO && $forumData['postby'] < AppConstant::ALWAYS_TIME) {
                $postBy = AppConstant::NUMERIC_ONE;
                $forumData['postby'] = AppUtility::tzdate("m/d/Y", $forumData['postby']);
                $postByTime = AppUtility::tzdate("g:i a", $forumData['postby']);
            } else {
                $postBy = $forumData['postby'];
                $forumData['postby'] = date("m/d/Y", strtotime("+1 week"));
                $postByTime = date('g:i a');
            }
            if ($forumData['outcomes']) {
                $outcomes = $forumData['outcomes'];
            } else {
                $outcomes = ' ';
            }
            list($postTag, $replyTag) = explode('--', $forumData['caltag']);
            $page_formActionTag = "?block=$block&cid=$courseId&folder=" . $params['folder'];
            $page_formActionTag .= (isset($_GET['id'])) ? "&id=" . $_GET['id'] : "";
            $page_formActionTag .= "&tb=$filter";
            $defaultValue = array(
                'allowAnonymous' => $allNon,
                'allowModify' => $allMod,
                'allowDelete' => $allDel,
                'allowLikes' => $allLikes,
                'viewAfterPost' => $viewAfterPost,
                'sDate' => $sDate,
                'sTime' => $sTime,
                'eDate' => $eDate,
                'eTime' => $eTime,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'hasGroupThreads' => $hasGroupThreads,
                'hasSubScrip' => $hasSubScrip,
                'defDisplay' => $forumData['defdisplay'],
                'sortBy' => $forumData['sortby'],
                'postTag' => $postTag,
                'replyTag' => $replyTag,
                'cntInGb' => $forumData['cntingb'],
                'points' => $forumData['points'],
                'gbCat' => $forumData['gbcategory'],
                'groupSetId' => $forumData['groupsetid'],
                'forumType' => $forumData['forumtype'],
                'rubric' => $forumData['rubric'],
                'tagList' => $forumData['taglist'],
                'tutorEdit' => $forumData['tutoredit'],
                'avail' => $forumData['avail'],
                'postDate' => $forumData['postby'],
                'replyByDate' => $forumData['replyby'],
                'replyByTime' => $replyByTime,
                'postByTime' => $postByTime,
                'replyBy' => $replyBy,
                'postBy' => $postBy,
                'outcomes' => $outcomes,
                'isOutcomes' => $course['outcomes'],
            );
        }
        if ($this->isPostMethod()) {
            if ($params['modifyFid']) {
                $outcomes = array();
                if (isset($params['outcomes'])) {
                    foreach ($params['outcomes'] as $outcomeId) {
                        if (is_numeric($outcomeId) && $outcomeId > AppConstant::NUMERIC_ZERO) {
                            $outcomes[] = intval($outcomeId);
                        }
                    }
                    if ($outcomes != null)
                    {
                        $params['outcomes'] = implode(',', $outcomes);
                    } else {
                        $params['outcomes'] = " ";
                    }
                } else {
                    $params['outcomes'] = " ";
                }
                $startDate = AppUtility::parsedatetime($params['sdate'], $params['stime']);
                $endDate = AppUtility::parsedatetime($params['edate'], $params['etime']);
                $postDate = AppUtility::parsedatetime($params['postDate'], $params['postTime']);
                $replyByDate = AppUtility::parsedatetime($params['replyByDate'], $params['replyByTime']);
                $settingValue = $params['allow-anonymous-posts'] + $params['allow-students-to-modify-posts'] + $params['allow-students-to-delete-own-posts'] + $params['like-post'] + $params['viewing-before-posting'];
                $updateForum = new Forums();
                $updateForum->UpdateForum($params, $endDate, $startDate, $postDate, $replyByDate, $settingValue);
                if (isset($params['Get-email-notify-of-new-posts'])) {
                    $subscriptionEntry = new ForumSubscriptions();
                    $subscriptionEntry->AddNewEntry($params['modifyFid'], $user['id']);
                } else {
                    ForumSubscriptions::deleteSubscriptionsEntry($params['modifyFid'], $user['id']);
                }
            } else {
                $endDate = AppUtility::parsedatetime($params['edate'], $params['etime']);
                $startDate = AppUtility::parsedatetime($params['sdate'], $params['stime']);
                $postDate = AppUtility::parsedatetime($params['postDate'], $params['postTime']);
                $replyByDate = AppUtility::parsedatetime($params['replyByDate'], $params['replyByTime']);
                $settingValue = $params['allow-anonymous-posts'] + $params['allow-students-to-modify-posts'] + $params['allow-students-to-delete-own-posts'] + $params['like-post'] + $params['viewing-before-posting'];
                $finalArray['name'] = trim($params['name']);
                if ($params['description'] == AppConstant::FORUM_DESCRIPTION) {
                    $finalArray['description'] = '';
                } else {
                    /*
                     * Apply html lawed here
                     */
                    $finalArray['description'] = $params['description'];
                }
                $finalArray['courseid'] = $params['cid'];
                $finalArray['settings'] = $settingValue;
                if ($params['avail'] == AppConstant::NUMERIC_ONE) {
                    if ($params['available-after'] == AppConstant::NUMERIC_ZERO) {
                        $startDate = AppConstant::NUMERIC_ZERO;
                    }
                    if ($params['available-until'] == AppConstant::ALWAYS_TIME) {
                        $endDate = AppConstant::ALWAYS_TIME;
                    }
                    $finalArray['startdate'] = $startDate;
                    $finalArray['enddate'] = $endDate;
                } else {
                    $finalArray['startdate'] = AppConstant::NUMERIC_ZERO;
                    $finalArray['enddate'] = AppConstant::ALWAYS_TIME;
                }
                $finalArray['sortby'] = $params['sort-thread'];
                $finalArray['defdisplay'] = $params['default-display'];
                if ($params['post'] == AppConstant::NUMERIC_ONE) {
                    $finalArray['postby'] = $postDate;
                } else {
                    $finalArray['postby'] = $params['post'];
                }
                if ($params['reply'] == AppConstant::NUMERIC_ONE) {
                    $finalArray['replyby'] = $replyByDate;
                } else {
                    $finalArray['replyby'] = $params['reply'];
                }
                if ($params['count-in-gradebook'] != AppConstant::NUMERIC_ZERO) {
                    $finalArray['gbcategory'] = $params['gradebook-category'];
                    $finalArray['points'] = $params['points'];
                    $finalArray['tutoredit'] = $params['tutor-edit'];
                    $finalArray['rubric'] = $params['rubric'];
                    if (isset($params['outcomes'])) {
                        foreach ($params['outcomes'] as $outcomeId) {
                            if (is_numeric($outcomeId) && $outcomeId > AppConstant::NUMERIC_ZERO) {
                                $outcomes[] = intval($outcomeId);
                            }
                        }
                        if ($outcomes != null) {
                            $params['outcomes'] = implode(',', $outcomes);
                        } else {
                            $params['outcomes'] = " ";
                        }
                    } else {
                        $params['outcomes'] = " ";
                    }
                    $finalArray['outcomes'] = $params['outcomes'];
                } else {
                    $finalArray['gbcategory'] = AppConstant::NUMERIC_ZERO;
                    $finalArray['points'] = AppConstant::NUMERIC_ZERO;
                    $finalArray['tutoredit'] = AppConstant::NUMERIC_ZERO;
                    $finalArray['rubric'] = AppConstant::NUMERIC_ZERO;
                    $finalArray['outcomes'] = " ";
                }
                $finalArray['groupsetid'] = $params['groupsetid'];
                $finalArray['cntingb'] = $params['count-in-gradebook'];
                $finalArray['avail'] = $params['avail'];
                $finalArray['forumtype'] = $params['forum-type'];
                $finalArray['caltag'] = $params['calendar-icon-text1'] . '--' . $params['calendar-icon-text2'];
                $tagList = '';
                if ($params['categorize-posts'] == AppConstant::NUMERIC_ONE) {
                    $tagList = trim($params['taglist']);
                }
                $finalArray['taglist'] = $tagList;
                $newForum = new Forums();
                $forumId = $newForum->addNewForum($finalArray);
                $itemType = 'Forum';
                $itemId = new Items();
                $lastItemId = $itemId->saveItems($courseId, $forumId, $itemType);
                $subscriptionEntry = new ForumSubscriptions();
                $subscriptionEntry->AddNewEntry($forumId, $user['id']);
                $courseItemOrder = Course::getItemOrder($courseId);
                $itemOrder = $courseItemOrder->itemorder;
                $items = unserialize($itemOrder);
                $blockTree = explode('-', $block);
                $sub =& $items;
                for ($i = AppConstant::NUMERIC_ONE; $i < count($blockTree); $i++) {
                    $sub =& $sub[$blockTree[$i] - AppConstant::NUMERIC_ONE]['items'];
                }
                if ($filter=='b') {
                    $sub[] = $lastItemId;
                } else if ($filter=='t') {
                    array_unshift($sub,$lastItemId);
                }
                $itemOrder = serialize($items);
                $saveItemOrderIntoCourse = new Course();
                $saveItemOrderIntoCourse->setItemOrder($itemOrder, $courseId);
            }
            return $this->redirect(AppUtility::getURLFromHome('course', 'course/course?cid=' . $course->id));
        }
        $this->includeJS(["editor/tiny_mce.js", "forum/addforum.js", "general.js"]);
        $this->includeCSS(['course/items.css']);
        $responseData = array('course' => $course, 'groupNameId' => $groupNameId, 'groupNameLabel' => $groupNameLabel, 'saveTitle' => $saveTitle, 'pageTitle' => $pageTitle, 'rubricsLabel' => $rubricsLabel, 'rubricsId' => $rubricsId, 'pageOutcomesList' => $pageOutcomesList,
            'pageOutcomes' => $pageOutcomes, 'defaultValue' => $defaultValue, 'forumData' => $forumData, 'modifyForumId' => $modifyForumId,
            'gbcatsLabel' => $gbCatsLabel, 'gbcatsId' => $gbCatsId, 'block' => $block,'page_formActionTag' => $page_formActionTag);
        return $this->renderWithData('addForum', $responseData);
    }

    public function flatArray($outcomesData)
    {
        global $outcomesList;
        if ($outcomesData) {
            foreach ($outcomesData as $singleData) {
                if (is_array($singleData)) { //outcome group
                    $outcomesList[] = array($singleData['name'], AppConstant::NUMERIC_ONE);
                    $this->flatArray($singleData['outcomes']);
                } else {
                    $outcomesList[] = array($singleData, AppConstant::NUMERIC_ZERO);
                }
            }
        }
    }

    public function actionChangeForum()//only for teacher
    {
        $courseId = $this->getParamVal('cid');
        $currentUser = $this->getAuthenticatedUser();
        $isTeacher = $this->isTeacher($currentUser->id, $courseId);
        $course = Course::getById($courseId);
        $this->layout = "master";
        $forumItems = array();
        $sort = AppConstant::ASCENDING;
        $orderBy = 'name';
        $forumData = Forums::getByCourseIdOrdered($courseId, $sort, $orderBy);
        foreach ($forumData as $forum) {
            $forumItems[$forum['id']] = $forum['name'];
        }
        $groupNames = StuGroupSet::getByCourseId($courseId);
        $key = AppConstant::NUMERIC_ZERO;
        foreach ($groupNames as $group) {
            $groupNameId[$key] = $group['id'];
            $groupNameLabel[$key] = 'Use group set:' . $group['name'];
            $key++;
        }
        $key = AppConstant::NUMERIC_ZERO;
        $gbCatsData = GbCats::getByCourseId($courseId);
        foreach ($gbCatsData as $singleGbCatsData) {
            $gbCatsId[$key] = $singleGbCatsData['id'];
            $gbCatsLabel[$key] = $singleGbCatsData['name'];
            $key++;
        }
        if ($this->isPostMethod()) {
            $params = $this->getRequestParams();
            if (isset($params['checked'])) { //form submitted
                $count = AppConstant::NUMERIC_ZERO;
                foreach ($params as $key => $singleParams) {
                    if (!is_array($key) && substr($key, AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_THREE) === 'chg') {
                        $count++;
                    }
                }
                if ($count == AppConstant::NUMERIC_ZERO) {
                    $this->setWarningFlash(AppConstant::NO_SETTING);
                    return $this->redirect('change-forum?cid=' . $courseId);
                }
                $checked = $params['checked'];
                $checkedList = "'" . implode("','", $checked) . "'";
                $sets = array();
                if (isset($params['chg-avail']))
                {
                    $sets['avail'] = intval($params['avail']);
                }
                if (isset($params['chg-reply-by']))
                {
                    if ($params['reply'] == "Always") {
                        $replyBy = AppConstant::ALWAYS_TIME;
                    } else if ($params['reply'] == "Never") {
                        $replyBy = AppConstant::NUMERIC_ZERO;
                    } else {
                        $replyBy = AppUtility::parsedatetime($params['replyByDate'], $params['replyByTime']);
                    }
                    $sets['replyby'] =  $replyBy;
                }

                if (isset($params['chg-post-by'])) {
                    if ($params['post'] == "Always") {
                        $postBy = AppConstant::ALWAYS_TIME;
                    } else if ($params['post'] == "Never") {
                        $postBy = AppConstant::NUMERIC_ZERO;
                    } else {
                        $postBy = AppUtility::parsedatetime($params['postDate'], $params['postTime']);
                    }
                    $sets['postby'] =  $postBy ;
                }
                if (isset($params['chg-cal-tag'])) {
                    $sets['caltag'] =  $params['cal-tag-post'] . '--' . $params['caltagreply'];
                }
                $sops = array();
                if (isset($params['chg-allow-anon'])) {
                    if (isset($params['allow-anonymous-posts']) && $params['allow-anonymous-posts'] == AppConstant::NUMERIC_ONE) {
                        //turn on 1's bit
                        $sops[] = " | 1";
                    } else {
                        //turn off 1's bit
                        $sops[] = " & ~1";
                    }
                }
                if (isset($params['chg-allow-mod'])) {
                    if (isset($params['allow-students-to-modify-posts']) && $params['allow-students-to-modify-posts'] == AppConstant::NUMERIC_ONE) {
                        //turn on 2's bit
                        $sops[] = " | 2";
                    } else {
                        //turn off 2's bit
                        $sops[] = " & ~2";
                    }
                }
                if (isset($params['chg-allow-del'])) {
                    if (isset($params['allow-students-to-delete-own-posts']) && $params['allow-students-to-delete-own-posts'] == AppConstant::NUMERIC_ONE) {
                        //turn on 4's bit
                        $sops[] = " | 4";
                    } else {
                        //turn off 4's bit
                        $sops[] = " & ~4";
                    }
                }
                if (isset($params['chg-allow-likes'])) {
                    if (isset($params['like-post']) && $params['like-post'] == AppConstant::NUMERIC_ONE) {
                        //turn on 8's bit
                        $sops[] = " | 8";
                    } else {
                        //turn off 8's bit
                        $sops[] = " & ~8";
                    }
                }
                if (isset($params['chg-view-before-post'])) {
                    if (isset($params['viewing-before-posting']) && $params['viewing-before-posting'] == AppConstant::NUMERIC_ONE) {
                        //turn on 8's bit
                        $sops[] = " | 16";
                    } else {
                        //turn off 8's bit
                        $sops[] = " & ~16";
                    }
                }
                if (count($sops) > AppConstant::NUMERIC_ZERO) {
                    $out = "settings";
                    foreach ($sops as $op) {
                        $out = "($out $op)";
                    }
                    $sets['settings'] =  $out ;
                }
                if (isset($params['chg-def-display'])) {
                    $sets['defdisplay'] =  intval($params['default-display']);
                }
                if (isset($params['chg-sort-by'])) {
                    $sets['sortby'] =  intval($params['sort-thread']);
                }
                if (isset($params['chg-cnt-in-gb'])) {
                    if (is_numeric($params['points']) && $params['points'] == AppConstant::NUMERIC_ZERO) {
                        $params['count-in-gradebook'] = AppConstant::NUMERIC_ZERO;
                    } else if ($params['count-in-gradebook'] == AppConstant::NUMERIC_ZERO) {
                        $params['points'] = AppConstant::NUMERIC_ZERO;
                    } else if ($params['count-in-gradebook'] == AppConstant::NUMERIC_FOUR) {
                        $params['count-in-gradebook'] = AppConstant::NUMERIC_ZERO;
                    }
                    $sets['cntingb'] =   intval($params['count-in-gradebook']);
                    if (is_numeric($params['points'])) {
                        $sets['points'] =  intval($params['points']);
                    }
                }
                if (isset($params['chg-gb-cat'])) {
                    $sets['gbcategory'] =  $params['gradebook-category'];
                }
                if (isset($params['chg-forum-type'])) {
                    $sets['forumtype'] =  $params['forum-type'];
                }
                if (isset($params['chg-tag-list']))
                {
                    if (isset($params['use-tags'])) {
                        $tagList = trim($params['taglist']);
                    } else {
                        $tagList = '';
                    }
                    $sets['taglist'] =  $tagList;
                }
                if (count($sets) > AppConstant::NUMERIC_ZERO & count($checked) > AppConstant::NUMERIC_ZERO) {
                    $setsList = implode(',', $sets);
                    $forum = new Forums();
                    $forum->updateForumData($sets, $checked);
                }
                if (isset($params['chg-subscribe'])) {
                    if (isset($params['Get-email-notify-of-new-posts'])) {
                        //add any subscriptions we don't already have
                        $subscriptionId = ForumSubscriptions::getByManyForumIdsANdUserId($checked, $currentUser['id']);
                        $hasSubscribe = array();
                        if ($subscriptionId > AppConstant::NUMERIC_ZERO) {
                            foreach ($subscriptionId as $subscription) {
                                $hasSubscribe[] = $subscription['forumid'];
                            }
                        }
                        $toAdd = array_diff($params['checked'], $hasSubscribe);
                        foreach ($toAdd as $fid) {
                            $fid = intval($fid);
                            if ($fid > AppConstant::NUMERIC_ZERO) {
                                $subscription = new ForumSubscriptions();
                                $subscription->AddNewEntry($fid, $currentUser->id);
                            }
                        }
                    } else {
                        //remove any existing subscriptions
                        foreach ($params['checked'] as $forumId) {
                            ForumSubscriptions::deleteSubscriptionsEntry($forumId, $currentUser->id);
                        }
                    }
                }
                $this->setWarningFlash(AppConstant::FORUM_UPDATED);
                return $this->redirect(AppUtility::getURLFromHome('course', 'course/course?cid=' . $courseId));
            } else {
                $this->setWarningFlash(AppConstant::NO_FORUM_SELECTED);
                return $this->redirect('change-forum?cid=' . $courseId);
            }
        }

        $this->includeCSS(['assessment.css', 'dataTables.bootstrap.css']);
        $this->includeJS(['general.js?ver=012115', 'DataTables-1.10.6/media/js/jquery.dataTables.js']);
        $responseData = array('course' => $course, 'gbCatsId' => $gbCatsId, 'gbCatsLabel' => $gbCatsLabel, 'groupNameId' => $groupNameId, 'isTeacher' => $isTeacher, 'forumItems' => $forumItems);
        return $this->renderWithData('changeForum', $responseData);
    }

    public function parentList($id)
    {
        global $parentArray;
        $parentData = ForumPosts::getPostById($id);
        if ($parentData['parent'] == AppConstant::NUMERIC_ZERO) {
            return $parentArray;
        } else {
            $parentArray[] = $parentData['parent'];
            $this->parentList($parentData['parent']);
        }
    }

    public function actionViewForumGrade()//only for teacher
    {
        $params = $this->getRequestParams();
        $courseId = intval($params['cid']);
        $course = Course::getById($courseId);
        $currentUser = $this->getAuthenticatedUser();
        $isTeacher = $this->isTeacher($currentUser['id'], $courseId);
        $isTutor = $this->isTutor($currentUser['id'], $courseId);;
        $studentId = intval($params['stu']);
        $userId = $currentUser['id'];
        if ($isTeacher || $isTutor) {
            $userId = intval($params['uid']);
        }
        $forumId = intval($params['fid']);

        if (($isTeacher || $isTutor) && (isset($params['score']) || isset($params['newscore']))) {
            if ($isTutor) {
                $forumData = Forums::getById($forumId);
                if ($forumData['tutoredit'] != AppConstant::NUMERIC_ONE) {
                    exit; //not auth for score change
                }
            }
            //check for grades marked as newscore that aren't really new
            //shouldn't happen, but could happen if two browser windows open
            if (isset($params['newscore'])) {
                $keys = array_keys($params['newscore']);
                foreach ($keys as $k => $v) {
                    if (trim($v) == '') {
                        unset($keys[$k]);
                    }
                }
                if (count($keys) > AppConstant::NUMERIC_ZERO) {
                    $gradeData = Grades::getForumData($forumId, $userId, $keys);
                    foreach ($gradeData as $singleGrade) {
                        $params['score'][$singleGrade['refid']] = $params['newscore'][$singleGrade['refid']];
                        unset($params['newscore'][$singleGrade['refid']]);
                    }
                }
            }
            if (isset($params['score'])) {
                foreach ($params['score'] as $key => $score) {
                    if (trim($key) == '') {
                        continue;
                    }
                    $score = trim($score);
                    if ($score != '') {
                        Grades::updateForumData($score, $params['feedback'][$key], $forumId, $userId, $key);
                    } else {
                        Grades::deleteForumData($forumId, $userId, $key);
                    }
                }
            }
            if (isset($params['newscore'])) {
                foreach ($params['newscore'] as $scoreKey => $score) {
                    if (trim($scoreKey) == '') {
                        continue;
                    }
                    if ($score != '') {
                        $grade = array
                        (
                            'gradetype' => 'forum',
                            'gradetypeid' => $forumId,
                            'refid' => $scoreKey,
                            'userid' => $userId,
                            'score' => $score,
                            'feedback' => $params['feedback'][$scoreKey]
                        );
                        $insertGrade = new Grades();
                        $insertGrade->insertForumDataInToGrade($grade);
                    }
                }
            }
            return $this->redirect('gradebook?stu=' . $studentId . '&cid=' . $courseId);
        }
        $user = User::userDataUsingForum($userId, $forumId);
        $tutorEdit = $user['tutoredit'];
        if ($isTutor && $tutorEdit == AppConstant::NUMERIC_TWO) {
            $this->setWarningFlash(AppConstant::NO_FORUM_ACCESS);
            return $this->goBack();
        }
        $forumInformation = Grades::getForumDataUsingUserId($userId, $forumId);
        $forumPostData = ForumPosts::getbyForumIdAndUserID($forumId, $userId);
        $responseData = array('user' => $user, 'forumPostData' => $forumPostData, 'forumInformation' => $forumInformation, 'course' => $course, 'forumId' => $forumId, 'studentId' => $studentId, 'userId' => $userId);
        return $this->renderWithData('viewForumGrade', $responseData);
    }

    public function actionListViews()
    {
        $currentUser = $this->getAuthenticatedUser();
        $params = $this->getRequestParams();

        $teacherid = $this->isTeacher($currentUser['id'],$params['cid']);
        if (!isset($teacherid))
        {
            echo "Not authorized to view this page";
            exit;
        }
        if (!isset($params['thread'])) {
            echo "No thread specified";
            exit;
        }
        $thread = intval($params['thread']);
        $forumId = Forums::getForumId($thread,$params['cid']);

        if (count($forumId) == 0)
        {
            echo 'Invalid thread';
            exit;
        }
        $users = User::lastViewsUser($thread);
        echo '<h4>'._('Thread Views').'</h4>';
        $responseData = array('users' => $users);
        $flexwidth = true;
        $nologo = true;
        echo '<h4>'._('Thread Views').'</h4>';


        if (count($users)==0) {
            echo '<p>'._('No thread views').'</p>';
        } else {
            echo '<table><thead><tr><th>'._('Name').'</th><th>'._('Last Viewed').'</th></tr></thead>';
            echo '<tbody>';
            foreach ($users as $row ) {
                echo '<tr><td>'.$row['LastName'].', '.$row['FirstName'].'</td>';
                echo '<td>'.AppUtility::tzdate("F j, Y, g:i a", $row['lastview']).'</td></tr>';
            }
            echo '</tbody></table>';
        }
        echo '<p class="small">'._('Note: Only the most recent thread view per person is shown').'</p>';

//        return $this->renderWithData('listViews',$responseData);
    }
    public  function flattenitems($items,&$addto) {
        global $itemsimporder,$addto;

        foreach ($items as $item) {
            if (is_array($item)) {
                $this->flattenitems($item['items'],$addto);
            } else {
                $addto[] = $item;

            }
        }

    }

    public function actionGetThreadAjax()
    {
        $params = $this->getRequestParams();
        $currentUser = $this->getAuthenticatedUser();
        $isValue = $params['isValue'];
        $forumId = $params['forumid'];
        $hideLink = $params['hideLink'];
        $threads = ThreadForm::thread($forumId);
        $forumData = Forums::getById($forumId);
        $threadArray = array();
        $uniquesDataArray = array();
        if (!empty($threads)) {
            if ($isValue == AppConstant::NUMERIC_ONE) {
                foreach ($threads as $thread) {
                    $username = User::getById($thread['userid']);
                    $uniquesData = ForumView::getByThreadId($thread['threadid']);
                    $lastView = ForumView::getLastView($currentUser, $thread['threadid']);
                    $count = ForumView::uniqueCount($thread['threadid']);
                    $tagged = ForumView::forumViews($thread['threadid'], $currentUser['id']);
                    $isReplies = AppConstant::NUMERIC_ZERO;
                    $threadReplies = ForumPosts::isThreadHaveReply($thread['id']);
                    if($threadReplies)
                    {
                        $isReplies = AppConstant::NUMERIC_ONE;
                    }
                    $views  = Thread::getByForumIdAndId($forumId,$thread['threadid']);
                    if ($tagged[0]['tagged'] == AppConstant::NUMERIC_ONE) {
                        $temparray = array
                        (
                            'parent' => $thread['parent'],
                            'threadId' => $thread['threadid'],
                            'forumiddata' => $thread['forumid'],
                            'subject' => $thread['subject'],
                            'views' => $views,
                            'replyby' => $thread['replyby'],
                            'postdate' => AppController::customizeDate($thread['postdate']),
                            'name' => AppUtility::getFullName($username->FirstName, $username->LastName),
                            'tagged' => $tagged[0]['tagged'],
                            'lastview' => AppController::customizeDate($lastView[0]['lastview']),
                            'userright' => $currentUser['rights'],
                            'postUserId' => $username->id,
                            'currentUserId' => $currentUser['id'],
                            'countArray' => $count,
                            'posttype' => $thread['posttype'],
                            'isReplies' => $isReplies,
                            'isanon' => $thread['isanon'],
                            'groupSetId' => $forumData['groupsetid'],
                        );
                        array_push($threadArray, $temparray);
                        array_push($uniquesDataArray, $uniquesData);
                    }
                }
            } else if ($isValue == AppConstant::NUMERIC_TWO || $isValue == AppConstant::NUMERIC_THREE) {
                foreach ($threads as $thread) {
                    $username = User::getById($thread['userid']);
                    $uniquesData = ForumView::getByThreadId($thread['threadid']);
                    $lastView = ForumView::getLastView($currentUser, $thread['threadid']);
                    $count = ForumView::uniqueCount($thread['threadid']);
                    $tagged = ForumView::forumViews($thread['threadid'], $currentUser['id']);
                    $views  = Thread::getByForumIdAndId($forumId,$thread['threadid']);
                    $isReplies = AppConstant::NUMERIC_ZERO;
                    $threadReplies = ForumPosts::isThreadHaveReply($thread['id']);
                    if($threadReplies)
                    {
                        $isReplies = AppConstant::NUMERIC_ONE;
                    }
                    if ($thread['postdate'] >= $lastView[AppConstant::NUMERIC_ZERO]['lastview'] && $currentUser['id'] != $username->id)
                    {
                        $temparray = array
                        (
                            'parent' => $thread['parent'],
                            'threadId' => $thread['threadid'],
                            'forumiddata' => $thread['forumid'],
                            'subject' => $thread['subject'],
                            'views' => $views,
                            'replyby' => $thread['replyby'],
                            'postdate' => AppController::customizeDate($thread['postdate']),
                            'name' => AppUtility::getFullName($username->FirstName, $username->LastName),
                            'tagged' => $tagged[0]['tagged'],
                            'lastview' => AppController::customizeDate($lastView[0]['lastview']),
                            'userright' => $currentUser['rights'],
                            'postUserId' => $username->id,
                            'currentUserId' => $currentUser['id'],
                            'countArray' => $count,
                            'posttype' => $thread['posttype'],
                            'isReplies' => $isReplies,
                            'isanon' => $thread['isanon'],
                            'groupSetId' => $forumData['groupsetid'],
                        );
                        if ($isValue == AppConstant::NUMERIC_THREE) {
                            array_push($threadArray, $temparray);
                            $ViewData = new ForumView();
                            $ViewData->inserIntoTable($threadArray);
                        } else {
                            array_push($threadArray, $temparray);
                        }
                        array_push($uniquesDataArray, $uniquesData);
                    }
                }
            } else {
                foreach ($threads as $thread) {
                    $username = User::getById($thread['userid']);
                    $uniquesData = ForumView::getByThreadId($thread['threadid']);
                    $lastView = ForumView::getLastView($currentUser, $thread['threadid']);
                    if($lastView){
                        $lastView1 = AppController::customizeDate($lastView[0]['lastview']);
                    }else{
                        $lastView1 = AppConstant::NUMERIC_ZERO;
                    }
                    $tagged = ForumView::forumViews($thread['threadid'], $currentUser['id']);
                    $count = ForumView::uniqueCount($thread['threadid']);
                    $views  = Thread::getByForumIdAndId($forumId,$thread['threadid']);
                    $isReplies = AppConstant::NUMERIC_ZERO;
                    $threadReplies = ForumPosts::isThreadHaveReply($thread['id']);
                    if($threadReplies)
                    {
                        $isReplies = AppConstant::NUMERIC_ONE;
                    }
                    $temparray = array
                    (
                        'parent' => $thread['parent'],
                        'threadId' => $thread['threadid'],
                        'forumiddata' => $thread['forumid'],
                        'subject' => $thread['subject'],
                        'views' => $views,
                        'replyby' => $thread['replyby'],
                        'postdate' => AppController::customizeDate($thread['postdate']),
                        'name' => AppUtility::getFullName($username->FirstName, $username->LastName),
                        'tagged' => $tagged[0]['tagged'],
                        'userright' => $currentUser['rights'],
                        'lastview' => $lastView1,
                        'postUserId' => $username->id,
                        'currentUserId' => $currentUser['id'],
                        'countArray' => $count,
                        'posttype' => $thread['posttype'],
                        'isReplies' => $isReplies,
                        'isanon' => $thread['isanon'],
                        'groupSetId' => $forumData['groupsetid'],
                    );
                    array_push($threadArray, $temparray);
                    array_push($uniquesDataArray, $uniquesData);
                }
            }
            $FinalUniquesData = array();
            foreach ($uniquesDataArray as $unique) {
                foreach ($unique as $un) {
                    $username = User::getById($un['userid']);
                    $temparrayForUnique = array(
                        'threadId' => $un['threadid'],
                        'lastView' => AppController::customizeDate($un['lastview']),
                        'name' => AppUtility::getFullName($username->FirstName, $username->LastName),
                    );
                    array_push($FinalUniquesData, $temparrayForUnique);
                }
            }
        } else
        {
            return $this->terminateResponse('');
        }
        $this->includeJS(['forum/forum.js']);
        $responseData = array('threadArray' => $threadArray, 'uniquesDataArray' => $FinalUniquesData, 'isValue' => $isValue);
        return $this->successResponse($responseData);
    }
}