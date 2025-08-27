<?php

/*
 * Returns the userids from $users that are members of $course
 */
function filter_users_by_course(array $users, int $course) {    
    global $DBH;
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
    $ph = Sanitize::generateQueryPlaceholders($ids);
    $table = Sanitize::simpleString($table);
    $stm = $DBH->prepare("SELECT id FROM $table WHERE id IN ($ph) AND courseid=?");
    $stm->execute(array_merge($ids, [$course]));
    return array_map('intval', $stm->fetchAll(PDO::FETCH_COLUMN, 0));
}