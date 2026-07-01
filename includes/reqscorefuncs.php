<?php

// Functions for handling reqscorejson
// (c) 2026 David Lippman for IMathAS

/*
Alt: single [aid, score, type]
bool: ["&", [array of singles]]
*/

$GLOBALS['reqScoreData'] = [];
$GLOBALS['reqScoreExcused'] = [];

function extractReqScoreAids(array $json): array {
    $out = [];
    if ($json[0]=='&' || $json[0]=='|') {  // is boolean
        foreach ($json[1] as $r) {
            if ($r[0]=='&' || $r[0]=='&') {
                array_push($out, ...extractReqScoreAids($json[1]));
            } else {
                array_push($out, $r[0]);
            }
        }
    } else {
        array_push($out, $json[0]);
    }
    return $out;
}


function singleMeetsReqScore(array $req): bool {
    global $reqScoreData;
    if (empty($reqScoreData[$req[0]]['score'])) {
        return false;
    }
    if ($req[2] == 0) {
        return (round($reqScoreData[$req[0]]['score'],1)+.02>abs($req[1]));
    } else {
        return (round(100*$reqScoreData[$req[0]]['score']/$reqScoreData[$req[0]]['ptsposs'],1)+.02>abs($req[1]));
    }
}

function getReqScoreOneStr(array $req): string {
    global $reqScoreData;
    return $req[1] . ($req[2] == 0 ? ($req[2]>1?' '._('points'):' '._('point')) : '%') . ' ' ._('on'). ' ' . $reqScoreData[$req[0]]['name'];
}

function getReqScoreStr(array $json): string {
    if ($json[0]=='&' || $json[0]=='|') {  // is boolean
        foreach ($json[1] as $child) {
            if ($child[0]=='&' || $child[0]=='|') {
                $res[] = '('.getReqScoreStr($child).')';
            } else {
                $res[] = getReqScoreOneStr($child);
            }
        }
        if ($json[0] == '&') {
            return implode(' and ', $res);
        } else {
            return implode(' or ', $res);
        }
    } else {
        return getReqScoreOneStr($json);
    }
}

function reqScoreGetData(array $aids, int $uid = 0) {
    global $DBH,$userid,$reqScoreData;

    if ($uid === 0) {
        $uid = $userid;
    }
    $tolookup = array_diff($aids, array_keys($reqScoreData));

    if (count($tolookup) > 0) {
        $ph = Sanitize::generateQueryPlaceholders($tolookup);
        $query = "SELECT ia.id,ia.name,ia.ptsposs,iar.score FROM
            imas_assessments AS ia LEFT JOIN imas_assessment_records AS iar
            ON iar.assessmentid=ia.id AND iar.userid=? WHERE ia.id IN ($ph)";
        $stm = $DBH->prepare($query);
        $stm->execute([$uid, ...$tolookup]);
        while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
            $reqScoreData[$row['id']] = $row;
        }
    }
}

function reqScoreGetExecused(array $aids, int $uid = 0) {
    global $DBH,$cid,$userid,$reqScoreExcused,$excused;

    if ($uid === 0) {
        $uid = $userid;
    }

    $tolookup = array_diff($aids, array_keys($reqScoreExcused));
    if (count($tolookup) > 0) {
        if (isset($execused)) { // these are loaded in loaditemshowdata; convert format
            foreach ($tolookup as $aid) {
                if (!empty($excused['A'.$aid])) {
                    $reqScoreExcused[$aid] = 1;
                }
            }
        } else {
            $ph = Sanitize::generateQueryPlaceholders($tolookup);
            $stm = $DBH->prepare("SELECT typeid FROM imas_excused WHERE courseid=? AND userid=? AND type='A' AND typeid IN ($ph)");
            $stm->execute([$cid, $uid, ...$tolookup]);
            while ($aid = $stm->fetchColumn(0)) {
                $reqScoreExcused[$aid] = 1;
            }
        }
    }
}

function meetsReqScore(array $json, bool $getstring = false, bool $checkdata = true, int $uid = 0) {
    global $DBH, $cid, $userid, $excused, $studentid, $reqScoreData;

    if ($uid === 0) {
        $uid = $userid;
    }

    if ($checkdata) {
        $aids = extractReqScoreAids($json);
        reqScoreGetData($aids, $uid);
        if ((isset($studentid) || $uid != $userid)) {
            reqScoreGetExecused($aids, $uid);
        }
    }

    $str = '';
    if ($getstring && $checkdata) {
        $str = getReqScoreStr($json);
    }

    if ($json[0]=='&' || $json[0]=='|') {  // is boolean
        foreach ($json[1] as $child) {
            if ($child[0]=='&' || $child[0]=='|') {
                $res = meetsReqScore($child,false, false);
            } else {
                $res = !empty($excusals[$child[0]]) || singleMeetsReqScore($child);
            }
            if ($res === false && $json[0] == '&') {
                return $getstring ? [false, $str] : false;
            } else if ($res === true && $json[0] == '|') {
                return $getstring ? [true, $str] : true;
            }
        }
        if ($json[0] == '&') {
            return $getstring ? [true, $str] : true;
        } else {
            return $getstring ? [false, $str] : false;
        }
    } else {
        $res = !empty($excusals[$json[0]]) || singleMeetsReqScore($json);
        return $getstring ? [$res, $str] : $res;
    }
}

function removeAidFromReqscore($json, $aid, $level = 0) {
    if ($json[0]=='&' || $json[0]=='|') {  // is boolean
        $newout = [];
        foreach ($json[1] as $child) {
            if ($child[0]=='&' || $child[0]=='|') {
                $sub = removeAidFromReqscore($child, $aid, 1);
                if ($sub !== null) {
                    $newout[] = $sub;
                }
            } else if ($child[0] != $aid) {
                $newout[] = $child;
            }
        }
        if (count($newout)==0) {
            return null;
        } else if (count($newout)==1) {
            return $newout[0];
        } else {
            return [$json[0], $newout];
        }
    } else if ($json[0] != $aid) {
        return $json;
    } else {
        return null;
    }
}

function remapReqScore($json, $assessmap, $samecourse = false) {
    // use assessmap to map all reqscore aids.
    // if missing from map:  if samecourse, leave. otherwise, remove requirement
    if ($json[0]=='&' || $json[0]=='|') {  // is boolean
        $newout = [];
        foreach ($json[1] as $child) {
            if ($child[0]=='&' || $child[0]=='|') {
                $sub = remapReqScore($child, $assessmap, $samecourse);
                if ($sub !== null) {
                    $newout[] = $sub;
                }
            } else if (!empty($assessmap[$child[0]])) { // have map
                $child[0] = $assessmap[$child[0]];
                $newout[] = $child;
            } else if ($samecourse) { // keep if samecourse
                $newout[] = $child;
            }
        }
        if (count($newout)==0) {
            return null;
        } else if (count($newout)==1) {
            return $newout[0];
        } else {
            return [$json[0], $newout];
        }
    } else if (!empty($assessmap[$json[0]])) {
        $json[0] = $assessmap[$json[0]];
        return $json;
    } else if ($samecourse) {
        return $json;
    } else {
        return null;
    }
}