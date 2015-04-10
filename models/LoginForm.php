<?php

namespace app\models;

use app\components\AppConstant;
use Yii;
use yii\base\Model;
use app\components\AppUtility;

/**
 * LoginForm is the model behind the login form.
 */
class LoginForm extends Model
{
    public $username;
    public $password;
    public $rememberMe = true;

    private $_user = false;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            // username and password are both required
            [['username', 'password'], 'required'],
            // rememberMe must be a boolean value
            ['rememberMe', 'boolean'],
            // password is validated by validatePassword()
            [['username', 'password'], 'validateLogin'],
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validateLogin($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->validateUser();
            if (!$user) {
                $this->addError('', AppConstant::INVALID_USERNAME_PASSWORD);
            }
        }
    }

    /**
     * Logs in a user using the provided username and password.
     * @return boolean whether the user is logged in successfully
     */
    public function login()
    {
        if ($this->validate()) {
            return Yii::$app->user->login($this->getUser(), $this->rememberMe ? AppConstant::REMEMBER_ME_TIME : AppConstant::ZERO_VALUE);
        } else {
            Yii::$app->session->setFlash('error', AppConstant::INVALID_USERNAME_PASSWORD);
            return false;
        }
    }

    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    public function getUser()
    {
        if ($this->_user === false) {
            $this->_user = User::findByUsername($this->username);
        }
        return $this->_user;
    }

    public function validateUser()
    {
        if ($this->_user === false) {

            $this->_user = User::findUser($this->username);
            if($this->_user)
            {
                require("../components/password.php");
            }
        }
        if(password_verify($this->password, $this->_user->password))
            return $this->_user;

        return false;
    }
}