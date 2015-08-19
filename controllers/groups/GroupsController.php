<?php
namespace app\controllers\groups;

use app\components\AppConstant;
use app\models\Assessments;
use app\models\AssessmentSession;
use app\models\Course;
use app\models\Exceptions;
use app\models\ForumPosts;
use app\models\Forums;
use app\models\ForumThread;
use app\models\GbCats;
use app\models\GbItems;
use app\models\GbScheme;
use app\models\Grades;
use app\models\InlineText;
use app\models\LinkedText;
use app\models\Outcomes;
use app\models\Questions;
use app\models\Student;
use app\models\StuGroupMembers;
use app\models\Stugroups;
use app\models\StuGroupSet;
use app\models\User;
use app\components\AppUtility;
use app\controllers\AppController;
use app\models\Wiki;
use app\models\WikiRevision;

class GroupsController extends AppController
{

    public function actionManageStudentGroups()
    {
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
        $this->layout = 'master';
        $courseId = $this->getParamVal('cid');
        $course = Course::getById($courseId);
        $page_groupSets = array();
        $groupsData = StuGroupSet::findGroupData($courseId);
        foreach($groupsData as $group)
        {
            $page_groupSets[] = $group;
        }
        $addGrpSet = $this->getParamVal('addgrpset');
        if(isset($addGrpSet))
        {
            $params = $this->getRequestParams();
            $groupName = $params['grpsetname'];
            if (isset($groupName))
            {
                if (trim($groupName)=='')
                {
                    $groupName = 'Unnamed group set';
                    }
                //if name is set
                $saveGroup  = new StuGroupSet();
                $saveGroup->InsertGroupData($groupName,$courseId);
                return $this->redirect('manage-student-groups?cid='.$course->id);
            }
        }
        $renameGrpSet = $this->getParamVal('renameGrpSet');
        if(isset($renameGrpSet))
        {
            $params = $this->getRequestParams();
            $modifiedGrpName = $params['grpsetname'];
            if(isset($modifiedGrpName))
            {
                $updateGrpSet = new StuGroupSet();
                $updateGrpSet->UpdateGrpSet($modifiedGrpName,$params['renameGrpSet']);
                return $this->redirect('manage-student-groups?cid='.$course->id);

            }else
            {
                $grpSetName =StuGroupSet::getByGrpSetId($params['renameGrpSet']);
            }
        }
        $copyGrpSet = $this->getParamVal('copyGrpSet');
        if($copyGrpSet)
        {
            $query = new StuGroupSet();
             $NewGrpSetId = $query->copyGroupSet($copyGrpSet,$courseId);
            $groups = Stugroups::findByGrpSetIdForCopy($copyGrpSet);
            if($groups){
                foreach($groups as $group)
                {
                    $stuGroupName = addslashes($group['name']);
                    $query = new Stugroups();
                    $newStuGrpId = $query->insertStuGrpData($stuGroupName,$NewGrpSetId);
                    $stuGroupMembersData = StuGroupMembers::findByStuGroupId($group['id']);
                    if($stuGroupMembersData){
                        foreach($stuGroupMembersData as $data)
                        {
                            $query = new StuGroupMembers();
                            $query->insertStuGrpMemberData($data['userid'],$newStuGrpId);
                        }
                    }
                }
            }
            return $this->redirect('manage-student-groups?cid='.$course->id);
        }
        $deleteGrpSet = $this->getParamVal('deleteGrpSet');
        if(isset($deleteGrpSet))
        {
                $used = '';
                $assessmentData = Assessments::getByGroupSetId($deleteGrpSet);
                if($assessmentData)
                {
                    foreach($assessmentData as $data)
                    {
                        $used .= "Assessment: {$data['name']}<br/>";
                    }
                }

                $forumData = Forums::getByGroupSetId($deleteGrpSet);
                if($forumData)
                {
                    foreach($forumData as $data)
                    {

                        $used .= "Forum: {$data['name']}<br/>";
                    }
                }
                $wikiData = Wiki::getByGroupSetId($deleteGrpSet);
                if($wikiData)
                {
                    foreach($wikiData as $data)
                    {
                        $used .= "Wiki: {$data['name']}<br/>";
                    }
                }
                $confirm = $this->getParamVal('confirm');
                if(isset($confirm))
                {
                    $this->deleteGrpSet($deleteGrpSet);
                    return $this->redirect('manage-student-groups?cid='.$course->id);
                }else
                {
                    $query= StuGroupSet::getByGrpSetId($deleteGrpSet);
                    $deleteGrpName = $query['name'];
                }

        }
        $this->includeCSS(['groups.css']);
        return $this->renderWithData('manageStudentGroups',['course' => $course,'page_groupSets' => $page_groupSets,'addGrpSet' => $addGrpSet,'renameGrpSet' => $renameGrpSet,'grpSetName' => $grpSetName,'deleteGrpSet' => $deleteGrpSet,'used' => $used,'deleteGrpName' => $deleteGrpName ]);


    }

    public function deleteGrpSet($deleteGrpSet)
    {
        $query = Stugroups::findByGrpSetIdToDlt($deleteGrpSet);
        if($query)
        {
            foreach($query as $data)
            {
                $this->deleteGroup($data['id']);
            }
        }

        StuGroupSet::deleteGrpSet($deleteGrpSet);
        Assessments::updateAssessmentForGroups($deleteGrpSet);
        Forums::updateForumForGroups($deleteGrpSet);
        Wiki::updateWikiForGroups($deleteGrpSet);
    }

    public function deleteGroup($grpId,$delPosts=true)
    {
        $this->removeAllGrpMember($grpId);
        if($delPosts)
        {
            $query = ForumThread::findByStuGrpId($grpId);
            $toDel = array();
            if($query)
            {
                foreach($query as $data)
                {
                    $toDel[] = $data['id'];
                }
            }
            if(count($toDel) > AppConstant::NUMERIC_ZERO)
            {
                $delList = implode(',',$toDel);
                 ForumThread::deleteForumThread($delList);
                ForumPosts::deleteForumPosts($delList);
            }
        }
        else
        {
            ForumThread::updateThreadForGroups($grpId);
        }
        Stugroups::deleteGrp($grpId);
        WikiRevision::deleteGrp($grpId);
    }

    public function removeAllGrpMember($grpId)
    {
        StuGroupMembers::deleteStuGroupMembers($grpId);
        AssessmentSession::updateAssSessionForGrp($grpId);
        $now = time();
        if(isset($log))
        {
            /*Remaining*/
        }
    }
}
