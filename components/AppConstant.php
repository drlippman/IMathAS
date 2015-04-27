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
    const LIMITED_COURSE_CREATOR_RIGHT = 40;
    const DIAGNOSTIC_CREATOR_RIGHT = 60;
    const GROUP_ADMIN_RIGHT = 75;
    const INSTALL_NAME = 'OpenMath';
    const INSTRUCTOR_REQUEST_SUCCESS = 'Your new account request has been sent.';
    const ADD_NEW_USER = 'Added new user.';
    const INSTRUCTOR_REQUEST_MAIL_SUBJECT = 'New Instructor Account Request';
    const STUDENT_REQUEST_MAIL_SUBJECT = 'New Student Account Request';
    const STUDENT_REQUEST_SUCCESS = 'Your new account request has been sent.';
    const DEFAULT_TIME_ZONE = 'Asia/Kolkata';
    const UPLOAD_DIRECTORY = 'Uploads/';
    const DESCENDING = SORT_DESC;
    const ASCENDING = SORT_ASC;
    const HIDE_ICONS_VALUE = 0;
    const CPLOC_VALUE = 7;
    const CHATSET_VALUE = 0;
    const SHOWLATEPASS = 1;
    const UNENROLL_VALUE = 0;
    const PIC_ICONS_VALUE = 1;
    const TOPBAR_VALUE = '0,1,2,3,9|0,2,3,4,6,9|0';
    const AVAILABLE_NOT_CHECKED_VALUE = 3;
    const NAVIGATION_NOT_CHECKED_VALUE = 7;
}