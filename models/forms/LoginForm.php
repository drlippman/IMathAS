<?php

namespace app\models\forms;

use app\components\AppConstant;
use app\models\AppModel;
use app\models\User;
use Yii;
use app\components\AppUtility;

/**
 * LoginForm is the model behind the login form.
 */
class LoginForm extends AppModel
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
            if (!$this->validateUser()) {
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
        $this->_user = User::findByUsername($this->username);
        if($this->_user){
            return $this->_user;
        }
        return false;
    }

    /**
     * @return bool|null|static
     * Checks user's authentication by username and password.
     */
    public function validateUser()
    {
        if ($this->_user === false) {
            $user = User::findUser($this->username);
            if($user)
            {
               if(AppUtility::verifyPassword($this->password, $user->password))
                {
                    $this->_user = $user;
                }
            }
        }
        return $this->_user;
    }
}