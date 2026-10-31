<?php

/*
 * Returns the userids from $users that are members of $course
 */
function filter_users_by_course(array $users, int $course) {    
    global $DBH;
    if (count($users) === 0){ 
        return [];
    }
    $ph = Sanitize::generateQueryPlaceholders($users);
    $stm = $DBH->prepare("SELECT userid FROM imas_students WHERE userid IN ($ph) AND courseid=?");
    $stm->execute(array_merge($users, [$course]));
    return array_map('intval', $stm->fetchAll(PDO::FETCH_COLUMN, 0));
}

/*
 * Returns the ids from $ids in table $table that are associated with course $course
 */
function filter_items_by_course(array $ids, string $table, int $course) {    
    global $DBH;
    if (count($ids) === 0){ 
        return [];
    }
    $ph = Sanitize::generateQueryPlaceholders($ids);
    $table = Sanitize::simpleString($table);
    $stm = $DBH->prepare("SELECT id FROM $table WHERE id IN ($ph) AND courseid=?");
    $stm->execute(array_merge($ids, [$course]));
    return array_map('intval', $stm->fetchAll(PDO::FETCH_COLUMN, 0));
}

/*
 * Checks user has some role in course
 */
function check_user_in_course($uid, $course) {
    global $DBH;
    $stm = $DBH->prepare("SELECT id FROM imas_students WHERE userid=? AND courseid=?
        UNION ALL SELECT id FROM imas_tutors WHERE userid=? AND courseid=?
        UNION ALL SELECT id FROM imas_teachers WHERE userid=? AND courseid=?");
    $stm->execute([$uid,$course,$uid,$course,$uid,$course]);
    return ($stm->fetchColumn(0) !== false);
}


/*
 * For tutors limited to a section, checks that $uid is a student in that section.
 * Always true for anyone who is not a section-limited tutor.
 */
function tutor_can_access_user($uid, $course) {
    global $DBH, $tutorid, $tutorsection;
    if (!isset($tutorid) || !isset($tutorsection) || $tutorsection === '') {
        return true;
    }
    $stm = $DBH->prepare("SELECT section FROM imas_students WHERE userid=? AND courseid=?");
    $stm->execute([$uid, $course]);
    $sec = $stm->fetchColumn(0);
    return ($sec !== false && strtolower(trim((string) $sec)) === strtolower($tutorsection));
}
