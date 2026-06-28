<?php

// functions to verify section restrictions still make sense after 
// a section was possibly lost due to reassigning sections or unenrollment
// (c) 2026 David Lippman for IMathAS

function validateSections($cid) {
    global $DBH;

    $stm = $DBH->prepare("SELECT DISTINCT section FROM imas_students WHERE courseid=? AND section IS NOT NULL");
    $stm->execute([$cid]);
    $sections = [];
    while ($sec = $stm->fetchColumn(0)) {
        if (trim($sec)!=='') {
            $sections[] = 's-'.$sec;
        }
    }

    $stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=?");
    $stm->execute([$cid]);
    $itemorder = $stm->fetchColumn(0);

    if ($itemorder !== false) {
        $items = unserialize($itemorder);
        checkSectionsInItems($items, $sections);
        $stm = $DBH->prepare("UPDATE imas_courses SET itemorder=? WHERE id=?");
        $stm->execute([serialize($items), $cid]);
    }
}

function checkSectionsInItems(&$items, $sections) {
    for ($i=0;$i<count($items);$i++) {
        if (is_array($items[$i])) {
            if (!empty($items[$i]['grouplimit'])) {
                $items[$i]['grouplimit'] = array_intersect($items[$i]['grouplimit'], $sections);
            }
            checkSectionsInItems($items[$i]['items'], $sections);
        }
    }
}