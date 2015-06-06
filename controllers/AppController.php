<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 13/4/15
 * Time: 8:32 PM
 */

namespace app\controllers;

use app\components\AppConstant;
use app\components\AppUtility;
use yii\web\Controller;
use Yii;

class AppController extends Controller
{

    public $enableCsrfValidation = false;

    function getBodyParams()
    {
        return $_POST;
    }

    function getRequestParams()
    {
        return $_REQUEST;
    }

    function getParam($key)
    {
        return $_REQUEST[$key];
    }

    function setSuccessFlash($message)
    {
        $this->_setFlash('success', $message);
    }

    function setErrorFlash($message)
    {
        $this->_setFlash('danger', $message);
    }

    function setWarningFlash($message)
    {
        $this->_setFlash('warning', $message);
    }

    function unauthorizedAccessHandler()
    {
        if (!$this->isGuestUser()) {
            return $this->goHome();
            exit;
        }
    }

    function isGuestUser(){
        return \Yii::$app->user->isGuest;
    }

    function guestUserHandler($isAjaxCall = false){
        if(self::isGuestUser())
        {
            if($isAjaxCall)
            {
//                return self::terminateResponse(AppConstant::UNAUTHORIZED_ACCESS);
                return false;
            }else{
                return $this->redirect(Yii::$app->homeUrl.'site/login');
            }
        }
    }

    function getUserId(){
        return $this->getAuthenticatedUser()->getId();
    }

    function getUserTimezone(){
        return AppConstant::DEFAULT_TIME_ZONE;
    }

    function includeCSS($cssFileArray){
        for($i=0; $i<count($cssFileArray); $i++){
            $this->getView()->registerCssFile("../../css/".$cssFileArray[$i]);
        }
    }

    function includeJS($jsFileArray){
        for($i=0; $i<count($jsFileArray); $i++){
            $this->getView()->registerJsFile("../../js/".$jsFileArray[$i]);
        }
    }

    function directIncludeCSS($cssFileArray){
        for($i=0; $i<count($cssFileArray); $i++){
            $this->getView()->registerCssFile("../css/".$cssFileArray[$i]);
        }
    }

    function directIncludeJS($jsFileArray){
        for($i=0; $i<count($jsFileArray); $i++){
            $this->getView()->registerJsFile("../js/".$jsFileArray[$i]);
        }
    }

    public function renderWithData($viewName, $data = array()){
        if(isset($data)){
            return $this->render($viewName, $data);
        }else{
            return $this->render($viewName);
        }
    }

    function getAuthenticatedUser(){
        return \Yii::$app->user->identity;
    }

    private function _setFlash($type, $message)
    {
        \Yii::$app->session->setFlash($type, $message);
    }

    public function isPost(){
        return Yii::$app->request->getMethod() == 'POST';
    }

    public function successResponse($data = '')
    {
        return json_encode(array('status' =>AppConstant::RETURN_SUCCESS, 'data' => $data));
    }

    public function terminateResponse($msg)
    {
        return json_encode(array('status' => AppConstant::RETURN_ERROR, 'message' => $msg));
    }

    public function getParamVal($key){
        return Yii::$app->request->get($key);
    }

    public function getSanitizedValue($key, $defaultVal = '')
    {
        isset($key) ? $key : $defaultVal;
    }

    public function isPostMethod(){
        return Yii::$app->request->post();
    }

}