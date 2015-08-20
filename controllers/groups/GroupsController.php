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
        $grpSetId = $this->getParamVal('grpSetId');
        if(isset($grpSetId))
        {
            $query = StuGroupSet::getByGrpSetId($grpSetId);
            $grpSetName = $query['name'];
            $page_Grp = array();
            $page_GrpMembers = array();
            $grpNum = AppConstant::NUMERIC_ONE;
            $query = Stugroups::findByGrpSetIdToManageSet($grpSetId);
            foreach($query as $singleData)
            {
                 if($singleData['name'] == 'Unamed Group')
                 {
                     $singleData['name'] .= " $grpNum";
                     $grpNum++;
                 }
                $page_Grp[$singleData['id']] = $singleData['name'];
                $page_GrpMembers[$singleData['id']] = array();
            }
            $grpIds = implode(',',array_keys($page_Grp));
            natsort($page_Grp);
            $stuNames = array();
            $hasUserImg = array();
            $query = User::findStuForGroups($courseId);
            foreach($query  as $singleStuData)
            {
                $stuNames[$singleStuData['id']] = $singleStuData['LastName'].','.$singleStuData['FirstName'];
                $hasUserImg[$singleStuData['id']] = $singleStuData['hasuserimg'];
            }
            $stuUserIdsInGroup = array();
            if (count($page_Grp)>0)
            {

                $query =StuGroupMembers::manageGrpSet($grpIds);
                foreach($query as $singleMember)
                {
                    if (!isset($page_GrpMembers[$singleMember['stugroupid']]))
                    {
                        $page_GrpMembers[$singleMember['stugroupid']] = array();
                    }
                    $page_GrpMembers[$singleMember['stugroupid']][$singleMember['userid']] = $stuNames[$singleMember['userid']];
                    $stuUserIdsInGroup[] = $singleMember['userid'];
                }

                foreach ($page_GrpMembers as $k=>$stuArr)
                {
                    natcasesort($stuArr);
                    $page_GrpMembers[$k] = $stuArr;
                }
                $unGrpIds = array_diff(array_keys($stuNames),$stuUserIdsInGroup);
                $page_unGrpStu = array();
                foreach ($unGrpIds as $uid)
                {
                    $page_unGrpStu[$uid] = $stuNames[$uid];
                }
                natcasesort($page_unGrpStu);

            }
        }
        $renameGrp = $this->getParamVal('renameGrp');
        if(isset($renameGrp))
        {

            $params = $this->getRequestParams();
            $grpName = $params['grpname'];
            if(isset($grpName))
            {
                Stugroups::renameGrpName($renameGrp,$grpName);
                return $this->redirect('manage-student-groups?cid='.$course->id.'&grpSetId='.$grpSetId);

            }else
            {
                $query = Stugroups::getById($renameGrp);
                $currGrpName = $query['name'];
                $query = StuGroupSet::getByGrpSetId($grpSetId);
                $grpSetName = $query['name'];
            }

        }
        $deleteGrp = $this->getParamVal('deleteGrp');
        if(isset($deleteGrp))
        {
            $confirm = $this->getParamVal('confirm');
            $params = $this->getRequestParams();
            $delPost = $params['delpost'];
            if(isset($confirm))
            {
                $this->deleteGroup($deleteGrp,$delPost=1);
                return $this->redirect('manage-student-groups?cid='.$course->id.'&grpSetId='.$grpSetId);
            }else
            {
                $query = Stugroups::getById($deleteGrp);
                $currGrpNameToDlt = $query['name'];
                $query = StuGroupSet::getByGrpSetId($grpSetId);
                $currGrpSetNameToDlt = $query['name'];

            }
        }
        $this->includeCSS(['groups.css']);
        return $this->renderWithData('manageStudentGroups',['course' => $course,'page_groupSets' => $page_groupSets,'addGrpSet' => $addGrpSet,'renameGrpSet' => $renameGrpSet,'grpSetName' => $grpSetName,'deleteGrpSet' => $deleteGrpSet,'used' => $used,'deleteGrpName' => $deleteGrpName,'grpSetId' => $grpSetId,'hasUserImg' => $hasUserImg,'page_Grp' => $page_Grp,'page_GrpMembers' => $page_GrpMembers,'page_unGrpStu' => $page_unGrpStu,'grpSetName' => $grpSetName,'renameGrp' => $renameGrp,'currGrpName' => $currGrpName,'currGrpNameToDlt' => $currGrpNameToDlt,'currGrpSetNameToDlt' => $currGrpSetNameToDlt,'deleteGrp' => $deleteGrp]);


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
