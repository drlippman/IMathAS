<?php

namespace app\components;

class AppConstant {

	const REMEMBER_ME_TIME = 2592000; //Time in second
    const ZERO_VALUE = '0';
    const INVALID_USERNAME_PASSWORD = 'Invalid username or password.';
    const MAX_SESSION_TIME = 86400;
    const LOGIN_FIRST = 'Please login into the system.';
    const FORGOT_PASS_MAIL_SUBJECT = 'Password Reset Request';
    const FORGOT_USER_MAIL_SUBJECT = 'User Name Request';
    const INVALID_EMAIL = 'User does not exist with this email.';
    const INVALID_USER_NAME = 'User does not exist.';
    const ADMIN_RIGHT = 100;
    const STUDENT_RIGHT = 10;
    const TEACHER_RIGHT = 20;
    const GUEST_RIGHT = 5;
    const GROUP_ADMIN_RIGHT = 75;
    const INSTALL_NAME = 'OpenMath';

}