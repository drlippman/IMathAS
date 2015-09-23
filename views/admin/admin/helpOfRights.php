<?php
use \app\components\AppUtility;

?>
<div class=h3>
    <h3 style="color: #000099"><?php AppUtility::t('OpenMath Help'); ?></h3>
    <h4 style="color: #000099"><?php AppUtility::t('Rights'); ?></h4>

    <h5><?php AppUtility::t('When adding a new administrator or changing rights, there are several rights levels, each higher level including the rights of the lower levels:'); ?> </h5>
    <ol>
        <h5>
            <li>
                <b> <?php AppUtility::t('Guest') ?> </b>
                <?php AppUtility::t(': Can access all class materials, including taking tests (however, test is restarted next time the guest user accesses the test).Cannot enroll or unenroll in courses, or change user info or password. Cannot post in forums.'); ?>
            </li>
            <li>
                <b><?php AppUtility::t('Student'); ?></b><?php AppUtility::t(': Can only access class materials - cannot edit anything.'); ?>
            </li>
            <li>
                <b><?php AppUtility::t('Teacher'); ?></b><?php AppUtility::t(': Can edit course materials and create assessments, but only in courses to which they have been assigned as a teacher.'); ?>
            </li>
            <li>
                <b><?php AppUtility::t('Limited Course Creator'); ?></b><?php AppUtility::t(': Can add courses, and they are automatically assigned as the teacher. Can delete courses that they create.'); ?>
            </li>
            <li>
                <b><?php AppUtility::t('Diagnostic Creator'); ?></b><?php AppUtility::t(': Can add courses, and they are automatically assigned as the teacher. Can delete courses that they create. Can create diagnostics.'); ?>
            </li>
            <li>
                <b><?php AppUtility::t('Group Admin'); ?></b><?php AppUtility::t(': Can add/delete teachers and set user rights, but only for users in their group. Can modify/delete questions and libraries created by members of the group regardless of ownership or use rights. Can always create "open to all" libraries.'); ?>
            </li>
            <li>
                <b><?php AppUtility::t('Full Administrator'); ?></b><?php AppUtility::t(': Can add/delete administrators and set user rights. Can import macro files, if installation allows. Can modify/delete questions and libraries regardless of ownership or use rights.'); ?>
            </li>
        </h5>
        <ol>


</div>