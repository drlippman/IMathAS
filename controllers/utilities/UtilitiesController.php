<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 1/9/15
 * Time: 1:59 PM
 */

namespace app\controllers\utilities;

use app\components\AppConstant;
use app\components\AppUtility;
use app\models\Course;
use app\models\forms\LtiUserForm;
use app\models\Items;
use app\models\User;
use \yii\web\Controller;
use app\controllers\AppController;

class UtilitiesController extends AppController
{
    public function actionAdminUtilities()
    {
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
        $removeLti = $this->getParamVal('removelti');
        $form = $this->getParamVal('form');
        $debug = $this->getParamVal('debug');
        $params = $this->getRequestParams();
        $this->layout = 'master';
        if($user->rights < AppConstant::ADMIN_RIGHT)
        {
            $body = AppConstant::NUMERIC_ONE;
            $message = 'You are not authorized to view this page';
        }
        if (isset($removeLti))
        {
            $id = intval($this->getParamVal('removelti'));
            $query = "DELETE FROM imas_ltiusers WHERE id=$id";
            mysql_query($query) or die("Query failed : " . mysql_error());
        }
        if (isset($form))
        {

            if ($form == 'lookup')
            {
                if(!empty($params['LastName']) || !empty($params['FirstName']) || !empty($params['SID']) || !empty($params['email']))
                {

                    if(!empty($params['SID']))
                    {
                        $queryForUser = User::getDataByJoin($params['SID'],AppConstant::NUMERIC_ZERO);
                    }
                    elseif(!empty($params['email']))
                    {
                        $queryForUser = User::getDataByJoin($params['email'],AppConstant::NUMERIC_ONE);

                    }
                    else
                    {
                        $queryForUser = User::getDataByJoinForName($params);
                    }
                    if(!$queryForUser)
                    {
                        $message = 'No results found';
                    }
                    else
                    {
                        foreach($queryForUser as $userData)
                        {
                            $queryForCourse = Course::queryForCourse($userData['id']);
                            $queryFromCourseForTutor = Course::queryFromCourseForTutor($userData['id']);
                            $queryFromCourseForTeacher = Course::queryFromCourseForTeacher($userData['id']);
                            $queryForLtiUser = LtiUserForm::getUserData($userData['id']);
                        }

                    }
                }

                }

        }
        $responseData = array('form' => $form,'debug' => $debug,'body' => $body,'message' => $message,'queryForCourse' => $queryForCourse,'queryFromCourseForTutor' => $queryFromCourseForTutor,'queryFromCourseForTeacher' => $queryFromCourseForTeacher,'queryForLtiUser' => $queryForLtiUser,'params' => $params,'queryForUser' => $queryForUser);
        return $this->render('adminUtilities',$responseData);
    }

    public function actionRescueCourse()
    {
        $this->guestUserHandler();
        $params = $this->getRequestParams();
        $courseId = $params['cid'];
        if(isset($courseId))
        {
           $query = Course::getItemOrderAndBlockCnt($courseId);
            $items = unserialize($query['itemorder']);
            $blockCnt = $query['blockcnt'];
        }else
        {

        }
        global $itemsFnd;
        $itemsFnd = array();
        $this->fixSub($items);
        $recoveredItems = array();
        $queryForItems = Items::getByCourseId($courseId);
        if($queryForItems)
        {
            foreach($queryForItems  as $singleItem)
            {
                if (!in_array($singleItem['id'],$itemsFnd))
                {
                    $recoveredItems[] = $singleItem['id'];
                }
            }
        }
        if(count($recoveredItems))
        {
            $block = array();
            $block['name'] = "Recovered items";
            $block['id'] = $blockCnt ;
            $block['startdate'] = AppConstant::NUMERIC_ZERO;
            $block['enddate'] = AppConstant::ALWAYS_TIME;
            $block['avail'] = AppConstant::NUMERIC_ZERO;
            $block['SH'] = "HO";
            $block['colors'] = '';
            $block['fixedheight'] = AppConstant::NUMERIC_ZERO;
            $block['public'] = AppConstant::NUMERIC_ZERO;
            $block['items'] = $recoveredItems;
            array_push($items,$block);
            echo "recovered ". count($recoveredItems) . "items";
            print_r($items);
        }

    }
    public function fixSub($items)
    {
        global $itemsFnd;
        foreach($items as $k=>$item) {
            if ($item==null) {
                unset($items[$k]);
            } else if (is_array($item)) {
                if (!isset($item['items']) || !is_array($item['items'])) {
                    unset($items[$k]);
                } else if (count($item['items'])>0) {
                    $this->fixSub($items[$k]['items']);
                }
            } else {
                if ($item==null || $item=='') {
                    unset($items[$k]);
                } else {
                    $itemsFnd[] = $item;
                }
            }
        }
        $items = array_values($items);

    }

} 