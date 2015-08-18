<?php
namespace app\controllers\groups;

use app\components\AppConstant;
use app\models\Assessments;
use app\models\AssessmentSession;
use app\models\Course;
use app\models\Exceptions;
use app\models\Forums;
use app\models\GbCats;
use app\models\GbItems;
use app\models\GbScheme;
use app\models\Grades;
use app\models\InlineText;
use app\models\LinkedText;
use app\models\Outcomes;
use app\models\Questions;
use app\models\Student;
use app\models\Stugroups;
use app\models\StuGroupSet;
use app\models\User;
use app\components\AppUtility;
use app\controllers\AppController;

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
        if($copyGrpSet){
            i
        }
        $this->includeCSS(['groups.css']);
        return $this->renderWithData('manageStudentGroups',['course' => $course,'page_groupSets' => $page_groupSets,'addGrpSet' => $addGrpSet,'renameGrpSet' => $renameGrpSet,'grpSetName' => $grpSetName]);


    }


}