<?php
namespace app\controllers\groups;

use app\components\AppConstant;
use app\components\filehandler;
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
    public $user = null;

    public function beforeAction($action)
    {
        $this->user = $this->getAuthenticatedUser();
        $courseId =  ($this->getParamVal('cid') || $this->getParamVal('courseId')) ? ($this->getParamVal('cid')?$this->getParamVal('cid'):$this->getParamVal('courseId') ): AppUtility::getDataFromSession('courseId');
        return $this->accessForTeacher($this->user,$courseId);
    }

    public function actionManageStudentGroups()
    {
        $this->guestUserHandler();
        $user = $this->user;
        $this->layout = 'master';
        $courseId = $this->getParamVal('cid');
        $course = Course::getById($courseId);
        $page_groupSets = array();
        $groupsData = StuGroupSet::findGroupData($courseId);
        $grpSetId =$this->getParamVal('grpSetId');
        $html = '';
        if($groupsData)
        {
            foreach($groupsData as $group)
            {
                $page_groupSets[] = $group;
            }
        }
        if(!$this->isTeacher($user->id,$courseId))
        {
            $message = AppConstant::GROUP_MESSAGE;

        }else
        {
            $addGrpSet = $this->getParamVal('addgrpset');
            $addGrp = $this->getParamVal('addGrp');
            $params = $this->getRequestParams();
            $newGrpName = $params['grpname'];
            $addStuToGrp = $this->getParamVal('addstutogrp');
            $stuToAdd = $params['stutoadd'];
            if(isset($addGrp) && isset($newGrpName) && isset($grpSetId))
            {
                if(trim($newGrpName) == '')
                {
                    $newGrpName = AppConstant::NEW_GROUP_NAME;
                }
                $query = new Stugroups();
                $insertId = $query->insertStuGrpName($grpSetId,$newGrpName);

                if($insertId->errors['name'])
                {
                    $this->setWarningFlash('Group name should contain at most 50 characters.');
                    return $this->redirect('manage-student-groups?cid='.$course->id.'&grpSetId='.$grpSetId.'&addGrp=true');
                }
                if(!isset($stuToAdd))
                {
                    return $this->redirect('manage-student-groups?cid='.$course->id.'&grpSetId='.$grpSetId);

                }else
                {
                    $addToGrpId = $insertId['id'];
                    $addStuToGrp = true;
                }
            }
            if(isset($addStuToGrp))
            {
                $params = $this->getRequestParams();
                $rowGrpTest = '';
                if($params['addtogrpid'] == '--new--')
                {
                    $addGrp = true;
                    if($stuToAdd)
                    {
                        $stuList = implode(',',$stuToAdd);
                    }
                }
                else
                {
                    if(isset($newGrpName))
                    {
                        $grpId = $addToGrpId;
                    }else
                    {
                        $grpId = $params['addtogrpid'];
                    }
                    $logInfo = "instr adding stu to group $grpId. ";
                    if (!is_array($stuToAdd)) {
                        $stuToAdd = explode(',',$stuToAdd);
                    }
                    $alreadyGroupedStu = array();
                    $stuList = "'".implode("','",$stuToAdd)."'";
                    $userId = StuGroupMembers::alreadyStuAdded($grpSetId,$stuList);
                    foreach($userId as $uid)
                    {
                        $alreadyGroupedStu[] = $uid['userid'];
                    }
                    $stuToAdd = array_diff($stuToAdd,$alreadyGroupedStu);
                    $query = StuGroupMembers::findByStuGroupId($grpId);
                    $existingGrpMembers = array();
                    foreach($query as $data)
                    {
                        $existingGrpMembers[] = $data['userid'];
                    }
                    if(count($stuToAdd) > AppConstant::NUMERIC_ZERO)
                    {
                        for($i= AppConstant::NUMERIC_ZERO;$i<count($stuToAdd);$i++)
                        {
                            $stuGrpMember = new StuGroupMembers();
                            $stuGrpMember->insertStuGrpMemberData($stuToAdd[$i],$grpId);

                        }
                        $query = Assessments::getIdForGroups($grpSetId);
                        $stuList = "'".implode("','",$stuToAdd)."'";
                        if($query)
                        {
                            $fieldsToCopy = 'assessmentid,agroupid,questions,seeds,scores,attempts,lastanswers,starttime,endtime,bestseeds,bestattempts,bestscores,bestlastanswers,feedback,reviewseeds,reviewattempts,reviewscores,reviewlastanswers,reattempting,reviewreattempting,timeontask';
                            foreach($query as $data)
                            {
                                if($grpSetId > AppConstant::NUMERIC_ZERO)
                                {
                                    $query  = AssessmentSession::getDataForGroups($fieldsToCopy,$grpId,$data['id']);
                                    if($query)
                                    {
                                        $rowGrpTest =$query;
                                        $grpAsIdExists = true;
                                    }else
                                    {
                                        $grpAsIdExists = false;
                                        $query = AssessmentSession::getIdForGroups($stuToAdd,$data['id'],$fieldsToCopy);
                                        if(count($query) > AppConstant::NUMERIC_ZERO)
                                        {
                                            $srcAsId = array_shift($query);
                                            $rowGrpTest = $query;
                                            $rowGrpTest [1] = $grpId;
                                            foreach($query as $data)
                                            {
                                                $fileData = filehandler::deleteasidfilesfromstring2($data['lastanswers'].$data['bestlastanswers'],'id',$data['id'],$data['assessmentid']);
                                            }
                                        }
                                    }
                                    if($rowGrpTest != '')
                                    {
                                        $fieldsToCopyArr = explode(',',$fieldsToCopy);
                                        $insRow = "'".implode("','",$rowGrpTest)."'";
                                        if ($grpAsIdExists==false)
                                        {
                                            $stuToAdd = array_merge($stuToAdd,$existingGrpMembers);
                                            foreach ($stuToAdd as $stuId)
                                            {
                                                $query = AssessmentSession::getAGroupId($stuId,$data['id']);
                                                if($query)
                                                {
                                                    $logInfo .= "updating ias for $stuId.";
                                                    $sets = array();
                                                    foreach ($fieldsToCopyArr as $k=>$val)
                                                    {
                                                        $sets[$val] =  $rowGrpTest[$val];
                                                    }
                                                    $setsList = implode(',',$sets);
                                                    AssessmentSession::updateAssessmentForStuGrp($query['id'],$sets);

                                                }else
                                                {
                                                    $logInfo .= "inserting ias for $stuId.";
                                                    $sets = array();
                                                    $sets['userid'] = $stuId;
                                                    foreach ($fieldsToCopyArr as $k=>$val)
                                                    {
                                                        $sets[$val] =  $rowGrpTest[$val];
                                                    }
                                                    $assessmentSession = new AssessmentSession();
                                                    $assessmentSession->insertAssessmentSessionData($sets);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if(count($alreadyGroupedStu) > AppConstant::NUMERIC_ZERO)
                    {
                        $html .= '<p>Some students joined a group already and were skipped:</p><p>';
                        $stuList = "'".implode("','",$alreadyGroupedStu)."'";
                        $query = User::insertDataFroGroups($alreadyGroupedStu);
                        if($query)
                        {
                            foreach($query as $data)
                            {
                                $html .= $data['LastName'].', '.$data['FirstName'].'<br/>';
                                $logInfo .= $data['LastName'].', '.$data['FirstName'].' already in group.';
                            }
                        }
                        $html .= "<p><a href='#'>Continue</a></p>";
                        $now = time();
                        if(isset($log))
                        {
                            /*Remaining*/
                        }
                    }
                    else
                    {
                        $now = time();
                        if(isset($log))
                        {
                            /*Remaining*/
                        }
                    }
                    return $this->redirect('manage-student-groups?cid='.$course->id.'&grpSetId='.$grpSetId);
                }
            }
            $remove  = $this->getParamVal('remove');
            $grpId = $this->getParamVal('grpId');
            if(isset($remove) && isset($grpId))
            {
                $confirmRemove = $this->getParamVal('confirm');
                if(isset($confirmRemove))
                {
                    $this->removeGrpMember($remove,$grpId);
                    return $this->redirect('manage-student-groups?cid='.$course->id.'&grpSetId='.$grpSetId);
                }
            }
            $removeAll = $this->getParamVal('removeall');
            if(isset($removeAll))
            {
                $confirmRemove = $this->getParamVal('confirm');
                if(isset($confirmRemove))
                {
                    $this->removeAllGrpMember($removeAll);
                    return $this->redirect('manage-student-groups?cid='.$course->id.'&grpSetId='.$grpSetId);
                }
            }
            if(isset($addGrpSet))
            {
                $params = $this->getRequestParams();
                $groupName = $params['grpsetname'];
                if (isset($groupName))
                {
                    if (trim($groupName)=='')
                    {
                        $groupName = AppConstant::GROUP_NAME;
                    }
                    /*
                     * if name is set
                     */
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
                if($groups)
                {
                    foreach($groups as $group)
                    {
                        $stuGroupName =  $group['name'];
                        $query = new Stugroups();
                        $newStuGrpId = $query->insertStuGrpData($stuGroupName,$NewGrpSetId);
                        $stuGroupMembersData = StuGroupMembers::findByStuGroupId($group['id']);
                        if($stuGroupMembersData)
                        {
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
                $confirm = $this->getParamVal('confirm');
                if(isset($confirm))
                {
                    $this->deleteGrpSet($deleteGrpSet);
                    return $this->redirect('manage-student-groups?cid='.$course->id);
                }
            }
            if(isset($addGrp))
            {
                $query = StuGroupSet::getByGrpSetId($grpSetId);
                $newGrpSetName = $query['name'];
            }
            if(isset($grpSetId))
            {
                $query = StuGroupSet::getByGrpSetId($grpSetId);
                $showImg = AppConstant::NUMERIC_ONE;
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
                if (count($page_Grp)>AppConstant::NUMERIC_ZERO)
                {
                    $query =StuGroupMembers::manageGrpSet(array_keys($page_Grp));
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
                }
                $unGrpIds = array_diff(array_keys($stuNames),$stuUserIdsInGroup);
                $page_unGrpStu = array();
                foreach ($unGrpIds as $uid)
                {
                    $page_unGrpStu[$uid] = $stuNames[$uid];
                }
                natcasesort($page_unGrpStu);
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
        }
        $this->includeCSS(['groups.css']);
        return $this->renderWithData('manageStudentGroups',['course' => $course,'page_groupSets' => $page_groupSets,'addGrpSet' => $addGrpSet,'renameGrpSet' => $renameGrpSet,'grpSetName' => $grpSetName,'deleteGrpSet' => $deleteGrpSet,'used' => $used,'deleteGrpName' => $deleteGrpName,'grpSetId' => $grpSetId,'hasUserImg' => $hasUserImg,'page_Grp' => $page_Grp,'page_GrpMembers' => $page_GrpMembers,'page_unGrpStu' => $page_unGrpStu,'grpSetName' => $grpSetName,'renameGrp' => $renameGrp,'currGrpName' => $currGrpName,'newGrpSetName' => $newGrpSetName,'addGrp' => $addGrp,'stuList' => $stuList,'remove' => $remove,'grpId' => $grpId,'removeAll' => $removeAll,'showImg' => $showImg,'message' => $message, 'html' => $html, 'alreadyGroupedStu' => $alreadyGroupedStu]);
    }

    /*Ajax Call to delete group set*/
    public function actionDeleteGrpSetAjax()
    {
        $params = $this->getRequestParams();
        $deleteGrpSet = $params['deleteId'];
        $cid = $params['cid'];
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
        $query= StuGroupSet::getByGrpSetId($deleteGrpSet);
        $deleteGrpName = $query['name'];
        $responseData = array('used' => $used,'deleteGrpName' => $deleteGrpName,'deleteGrpSet' => $deleteGrpSet,'cid' => $cid);
        return $this->successResponse($responseData);
    }
    /*Ajax Call to delete group*/
    public function actionRemoveAjax()
    {
        $params = $this->getRequestParams();
        $remove = $params['removeId'];
        $cid = $params['cid'];
        $grpId = $params['grpId'];
        $grpSetId = $params['grpSetId'];
        $query = User::userDataForGroups($remove);
        $stuNameToBeRemoved = $query[0]['LastName'].','.$query[0]['FirstName'];
        $query = Stugroups::getById($grpId);
        $StuGrpName = $query['name'];
        $Stu_GrpName = AppUtility::truncate($StuGrpName,30);
        $query = StuGroupSet::getByGrpSetId($grpSetId);
        $StuGrpSetName = $query['name'];
        $Stu_GrpSetName = AppUtility::truncate($StuGrpSetName, 30);
        $responseData = array('stuNameToBeRemoved' => $stuNameToBeRemoved,'Stu_GrpName' => $Stu_GrpName,'cid' => $cid,'grpSetId' => $grpSetId,'remove' => $remove,'Stu_GrpSetName' => $Stu_GrpSetName,'grpId' => $grpId);
        return $this->successResponse($responseData);
    }

    /*Ajax Call to delete group*/
    public function actionDeleteGrpAjax()
    {
        $params = $this->getRequestParams();
        $deleteGrp = $params['deleteId'];
        $cid = $params['cid'];
        $grpSetId = $params['grpId'];
        $query = Stugroups::getById($deleteGrp);
        $currGrpNameToDelete = $query['name'];
        $currGrpNameToDlt = AppUtility::truncate($currGrpNameToDelete,30);
        $query = StuGroupSet::getByGrpSetId($grpSetId);
        $currGrpSetNameToDelete = $query['name'];
        $currGrpSetNameToDlt = AppUtility::truncate($currGrpSetNameToDelete,30);
        $responseData = array('currGrpNameToDlt' => $currGrpNameToDlt,'currGrpSetNameToDlt' => $currGrpSetNameToDlt,'cid' => $cid,'grpSetId' => $grpSetId,'deleteGrp' => $deleteGrp);
        return $this->successResponse($responseData);
    }
    /*Ajax Call to delete group On confirmation*/
    public function actionDeleteOnConfirmationAjax()
    {
        $params = $this->getRequestParams();
        $delPost = $params['selected'];
        $deleteGrp = $params['deleteGrp'];
        $grpSetId = $params['grpSetId'];
        $cid = $params['cid'];
        $this->deleteGroup($deleteGrp,$delPost= AppConstant::NUMERIC_ONE);
        $responseData = array('deleteGrp ' => $deleteGrp ,'cid' => $cid,'grpSetId' => $grpSetId);
        return $this->successResponse($responseData);
    }
    /*Ajax Call to Remove all Group Member*/
    public function actionRemoveAllAjax()
    {
        $params = $this->getRequestParams();
        $removeAll = $params['removeId'];
        $grpSetId = $params['grpSetId'];
        $cid = $params['cid'];
        $query = Stugroups::getById($removeAll);
        $Stu_GrpName = $query['name'];
        $responseData = array('removeAll' => $removeAll ,'cid' => $cid,'grpSetId' => $grpSetId,'Stu_GrpName' => $Stu_GrpName);
        return $this->successResponse($responseData);
    }

        /*function to delete Group Set*/
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
    /*function to delete Group*/
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
                 ForumThread::deleteForumThread($toDel);
                ForumPosts::deleteForumPosts($toDel);
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
    public function removeGrpMember($uid,$grpId)
    {
        StuGroupMembers::removeGrpMember($uid,$grpId);
        AssessmentSession::updateAssSessionForGrpByGrpIdAndUid($uid,$grpId);
        $now = time();
        if (isset($log))
        {
            /*Remaining*/
        }
    }
}
