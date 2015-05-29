<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 13/4/15
 * Time: 8:32 PM
 */

namespace app\controllers;

use app\components\AppConstant;
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

    function guestUserHandler(){
        if(self::isGuestUser())
        {
            return $this->redirect(Yii::$app->homeUrl.'site/login');
        }
    }

    function getUserTimezone(){
        return AppConstant::DEFAULT_TIME_ZONE;
    }

    function includeCSS($cssFileArray){
        for($i=0; $i<count($cssFileArray); $i++){
            $this->getView()->registerCssFile("../" . $cssFileArray[$i]);
        }
    }

    function includeJS($jsFileArray){
        for($i=0; $i<count($jsFileArray); $i++){
            $this->getView()->registerJsFile("../" . $jsFileArray[$i]);
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
    public function successResponse($data)
    {
        return json_encode(array('status' => 0, 'data' => $data));
    }

    public function terminateResponse($msg)
    {
        return json_encode(array('status' => -1, 'message' => $msg));
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

    public function getReturnableResponse($status, $data = ''){
        return json_encode(array('status' =>$status, 'data' => $data));
    }

}