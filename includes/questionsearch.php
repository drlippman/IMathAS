<?php

// Question search utilities

/**
 * parses a search string to pull out special
 * @param  string $str The search string
 * @return array      a parsed array with these possible keys:
 *    id:       an question ID number to search for
 *    author:   an author name
 *    type:     question type
 *    regex:    regex to use in the search
 *    terms:    array of keywords
 **/
function parseSearchString($str)
{
    $out = array();
    preg_match_all('/(author|type|id|regex|used|avgtime|mine|unused|private|res):("[^"]+?"|\w+)/', $str, $matches, PREG_SET_ORDER);
    if (count($matches) > 0) {
        foreach ($matches as $match) {
            $out[$match[1]] = str_replace('"', '', $match[2]);
        }
        $str = preg_replace('/(author|type|id|regex|used|avgtime|mine|unused|private|res):("[^"]+?"|\w+)/', '', $str);
    }

    $out['terms'] = preg_split('/\s+/', trim($str));
    foreach ($out['terms'] as $k => $v) {
        if (ctype_digit($v) && !isset($out['id'])) {
            $out['id'] = $v;
            if (count($out['terms']) == 1) { // only id, remove as keyword
                unset($out['terms']);
                break;
            }
        }
    }
    return $out;
}

/**
 * Search the question library
 * @param  array $search an array with one or more of these possible keys:
 *    id:       an question ID number to search for
 *    author:   an author name
 *    type:     question type
 *    regex:    regex to use in the search
 *    avgtime:  Average time: min,max
 *    mine:     1 to limit to mine only
 *    unused:   1 to exclude existing
 *    private:  0 to exclude private questions
 *    terms:    array of keywords
 * @param int  $userid   userid of searcher
 * @param string $searchtype  'all' to search all libs, 'libs' to search libs, 'assess' to search assessments
 * @param array $libs an array of libraries or assessments to search
 * @param array $options, with these possible keys:
 *    existing:  array   question IDs to not include in results if unused is set
 *    hidereplaceby: boolean  true to hide questions with replaceby set (def: false)
 *    skipfederated: boolean  true to skip questions in federated libraries (def: false)
 *    getowner: boolean true to include owners name (def: false)
 *    isadmin: boolean  user is search as admin (def false)
 *    isgroupadmin: int  user is searching as group admin (pass group id)
 * @param int  offset  offset when doing paginated results
 * @param int  max   maximum number of rows to return
 * @return PDOStatement executed PDOStatement
 */
function searchQuestions($search, $userid, $searchtype, $libs = array(), $options = array(), $offset = 0, $max = 200)
{
    global $DBH;

    $searchand = [];
    $searchvals = [];

    if ($searchtype != 'all' && !is_array($libs)) {
        $libs = explode(',', $libs);
    }

    if (!empty($search['type'])) {
        $types = array_map('trim', explode(',', $search['type']));
        $typesearch = [];
        foreach ($types as $type) {
            $typesearch[] = 'iq.qtype=?';
            $searchvals[] = $type;
        }
        $searchand[] = '(' . implode(' OR ', $typesearch) . ')';
    }
    if (!empty($search['author'])) {
        if (ctype_digit($search['author'])) {
            $searchand[] = 'iq.ownerid=?';
            $searchvals[] = $search['author'];
        } else {
            $names = preg_split('/([,\s]+)/', trim($search['author']), -1, PREG_SPLIT_DELIM_CAPTURE);
            if (count($names) == 1) {
                $searchand[] = 'iq.author LIKE ?';
                $searchvals[] = $names[0] . '%';
            } else if (trim($names[1]) == ',') {
                $searchand[] = 'iq.author LIKE ?';
                $searchvals[] = $names[0] . ',' . $names[1] . '%';
            } else {
                $searchand[] = '(iq.author LIKE ? OR iq.author LIKE ?)';
                $searchvals[] = $names[0] . ',' . $names[1] . '%';
                $searchvals[] = $names[1] . ',' . $names[0] . '%';
            }
        }
    }
    if (!empty($search['regex'])) {
        $searchand[] = 'iq.description REGEXP ?';
        $searchvals[] = $search['regex'];
    }
    if (!empty($search['terms'])) {
        $wholewords = array();
        foreach ($search['terms'] as $k => $v) {
            $sgn = '+';
            if ($v[0] == '!') {
                $sgn = '-';
                $v = substr($v, 1);
            }
            if (ctype_alnum($v) && strlen($v) > 3) {
                $wholewords[] = $sgn . $v . '*';
                unset($search['terms'][$k]);
            }
        }
        if (count($wholewords) > 0) {
            $searchand[] = 'MATCH(iq.description) AGAINST(? IN BOOLEAN MODE)';
            $searchvals[] = implode(' ', $wholewords);
        }
        if (count($search['terms']) > 0) {
            foreach ($search['terms'] as $k => $v) {
                if ($v[0] == '!') {
                    $v = substr($v, 1);
                    $searchand[] = 'iq.description NOT LIKE ?';
                } else {
                    $searchand[] = 'iq.description LIKE ?';
                }
                $searchvals[] = '%' . str_replace('%', '\\%', $v) . '%';
            }
        }
    }
    if (!empty($search['avgtime'])) {
        $avgtimeparts = explode(',', $search['avgtime']);
        if (!empty($avgtimeparts[0])) {
            $searchand[] = 'iq.meantime > ?';
            $searchvals[] = $avgtimeparts[0];
        }
        if (!empty($avgtimeparts[1])) {
            $searchand[] = 'iq.meantime < ?';
            $searchvals[] = $avgtimeparts[1];
        }
    }
    if (!empty($search['res'])) {
        $helps = explode(',', $search['res']);
        if (in_array('help', $helps)) {
            $searchand[] = 'LENGTH(iq.extref)>0';
        }
        if (in_array('cap', $helps)) {
            $searchand[] = "iq.extref LIKE '%!!1%'";
        }
        if (in_array('soln', $helps)) {
            $searchand[] = '(LENGTH(iq.solution) > 0 AND (iq.solutionopts&5)=5)';
        }
        if (in_array('WE', $helps)) {
            $searchand[] = '(LENGTH(iq.solution) > 0 AND (iq.solutionopts&2)=2)';
        }
    }
    $searchquery = '';
    if (count($searchand) > 0) {
        $searchquery = '(' . implode(' AND ', $searchand) . ')';
    }
    // do this last, since this will be an OR with other stuff
    // TODO: extend to allow searching for multiple IDs
    if (isset($search['id'])) {
        $ids = explode(',', $search['id']);
        $idors = [];
        foreach ($ids as $id) {
            $idors[] = 'iq.id=?';
            $searchvals[] = $id;
        }
        $idsearch = implode(' OR ', $idors);
        if ($searchquery === '') {
            $searchquery = '(' . $idsearch . ')';
        } else {
            $searchquery = '(' . $searchquery . ' OR ' . $idors . ')';
        }
    }

    $libquery = '';
    if ($searchtype == 'libs' && count($libs) > 0) {
        $llist = implode(',', array_map('intval', $libs));
        $libquery = "ili.libid IN ($llist)";
        $libnames = [];
        $sortorder = [];
        $stm = $DBH->query("SELECT name,id,sortorder FROM imas_libraries WHERE id IN ($llist)");
        while ($row = $stm->fetch(PDO::FETCH_NUM)) {
            $libnames[$row[1]] = Sanitize::encodeStringForDisplay($row[0]);
            $sortorder[$row[0]] = $row[2];
        }
    } else if ($searchtype == 'assess' && count($libs) > 0) {
        $llist = implode(',', array_map('intval', $libs));
        $libquery = "ia.id IN ($llist)";
        $stm = $DBH->query("SELECT id,name,itemorder FROM imas_assessments WHERE id IN ($llist)");
        $aidnames = [];
        $qidmap = [];
        while ($row = $stm->fetch(PDO::FETCH_NUM)) {
            $aidnames[$row[0]] = Sanitize::encodeStringForDisplay($row[1]);
            $items = explode(',', str_replace('~', ',', $row[2]));
            foreach ($items as $k => $v) {
                $qidmap[$v] = array($row[0], $k);
            }
        }
    }

    $rightsand = [];
    if (!empty($search['mine'])) {
        $rightsand[] = '(iq.ownerid=?)';
        $searchvals[] = $userid;
    } else {
        if (!empty($options['isadmin'])) {
            if (isset($search['private']) && $search['private'] == 0) {
                $rightsand[] = 'iq.userights>0';
            }
        } else if (!empty($options['isgroupadmin'])) {
            $groupid = $options['isgroupadmin'];
            if (isset($search['private']) && $search['private'] == 0) {
                $rightsand[] = 'iq.userights>0';
            } else {
                $rightsand[] = '(imas_users.groupid=? OR iq.userights>0)';
                $searchvals[] = $groupid;
            }
            if (isset($search['id'])) {
                $rightsand[] = '(ili.libid > 0 OR imas_users.groupid=? OR iq.id=?)';
                $searchvals[] = $groupid;
                $searchvals[] = $search['id'];
            } else {
                $rightsand[] = '(ili.libid > 0 OR imas_users.groupid=?)';
                $searchvals[] = $groupid;
            }
        } else {
            if (isset($search['private']) && $search['private'] == 0) {
                $rightsand[] = 'iq.userights>0';
            } else {
                $rightsand[] = '(iq.ownerid=? OR iq.userights>0)';
                $searchvals[] = $userid;
            }
            if (isset($search['id'])) {
                $rightsand[] = '(ili.libid > 0 OR iq.ownerid=? OR iq.id=?)';
                $searchvals[] = $userid;
                $searchvals[] = $search['id'];
            } else {
                $rightsand[] = '(ili.libid > 0 OR iq.ownerid=?)';
                $searchvals[] = $userid;
            }
        }
    }
    if (count($rightsand) > 0) {
        $rightsquery = '(' . implode(' AND ', $rightsand) . ')';
    } else {
        $rightsquery = '';
    }

    if ($searchquery === '' && $libquery === '') {
        return 'Cannot search all libraries without a search term';
    }

    $query = 'SELECT iq.id, iq.description, iq.userights, iq.qtype, iq.extref,
    MIN(ili.libid) AS libid, iq.ownerid, iq.meantime, iq.meanscore,iq.meantimen,
    imas_users.LastName, imas_users.FirstName, imas_users.groupid,
    LENGTH(iq.solution) AS hassolution,iq.solutionopts,
    ili.junkflag, iq.broken, ili.id AS libitemid ';
    if ($searchtype == 'assess') {
        $query .= ',iaq.id AS qid ';
    }
    $query .= 'FROM imas_questionset AS iq JOIN imas_library_items AS ili ON
    ili.qsetid=iq.id AND ili.deleted=0 ';
    if ($searchtype == 'assess') {
        $query .= 'JOIN imas_questions AS iaq ON iaq.questionsetid=iq.id
        JOIN imas_assessments AS ia ON iaq.assessmentid = ia.id ';
    }
    $query .= 'JOIN imas_users ON iq.ownerid=imas_users.id WHERE iq.deleted=0';
    // TODO: Add group BY iq.id to eliminate duplicates from multiple libraries
    if (!empty($options['hidereplaceby'])) {
        $query .= ' AND iq.replaceby=0';
    }

    // NOTE: replaced string solution with int hassolution (=0 means no solution)

    if ($searchquery !== '') {
        $query .= ' AND ' . $searchquery;
    }
    if (!empty($options['skipfederated'])) {
        $query .= ' AND iq.id NOT IN (SELECT iq.id FROM imas_questionset
      AS iq JOIN imas_library_items as ili on ili.qsetid=iq.id AND ili.deleted=0
      JOIN imas_libraries AS il ON ili.libid=il.id AND il.deleted=0 WHERE
      il.federationlevel>0)';
    }
    if (!empty($options['existing']) && !empty($search['unused'])) {
        $existingq = implode(',', array_map('intval', $options['existing']));
        $query .= " AND iq.id NOT IN ($existingq)";
    }
    if ($libquery !== '') {
        $query .= ' AND ' . $libquery;
    }
    if ($rightsquery !== '') {
        $query .= ' AND ' . $rightsquery;
    }
    //$query .= ' GROUP BY ili.qsetid ';

    if ($searchtype == 'assess') {
        $query .= ' GROUP BY iaq.id,ili.qsetid ';
        $query .= ' ORDER BY ia.id ';
    } else {
        $query .= ' GROUP BY ili.qsetid ';
    }
    if (!empty($max) && intval($max) > 0) {
        $query .= ' LIMIT ' . intval($max);
        if (!empty($offset) && intval($offset) > 0 && $offset < 1000000000) {
            $query .= ' OFFSET ' . intval($offset);
        }
    }
    $stm = $DBH->prepare($query);
    $stm->execute($searchvals);
    $res = [];
    $qsids = [];
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        $row['description'] = Sanitize::encodeStringForDisplay($row['description']);
        if (!empty($options['getowner'])) {
            $row['ownername'] = Sanitize::encodeStringForDisplay($row['LastName'].', '.$row['FirstName']);
        }
        unset($row['LastName']);
        unset($row['FirstName']);
        $row['extrefval'] = 0;
        if ($row['extref']!='') {
            $extref = explode('~~',$row['extref']);
            $hasvid = false;  $hasother = false; $hascap = false;
            foreach ($extref as $v) {
                if (substr($v,0,5)=="Video" || strpos($v,'youtube.com')!==false || strpos($v,'youtu.be')!==false) {
                    $row['extrefval'] |= 1; // has video
                    if (strpos($v,'!!1')!==false) {
                        $row['extrefval'] |= 2; // video captioned
                    } 
                } else {
                    $row['extrefval'] |= 4; // has other extref
                }
            }
        }
        if ($row['hassolution']>0 && ($row['solutionopts']&2)==2) {
            $row['extrefval'] |= 8; // has written example
        }
        // don't need to send these to frontend
        unset($row['extref']);
        unset($row['solutionopts']);
        unset($row['hassolution']);
        $row['meantime'] = round($row['meantime']/60,1);
        $row['meanscore'] = round($row['meanscore']);
        $row['mine'] = ($row['ownerid'] == $userid) ? 1 : 0;
        $res[] = $row;
        $qsids[] = $row['id'];
    }

    // pull timesused
    if (count($qsids)>0) {
        $allusedqids = implode(',', array_unique($qsids));
        $stm = $DBH->query("SELECT questionsetid,COUNT(id) FROM imas_questions WHERE questionsetid IN ($allusedqids) GROUP BY questionsetid");
        $timesused = [];
        while ($row = $stm->fetch(PDO::FETCH_NUM)) {
            $timesused[$row[0]] = $row[1];
        }
        foreach ($res as $k=>$v) {
            $res[$k]['times'] = !empty($timesused[$v['id']]) ? $timesused[$v['id']] : 0;
        }
    }

    // do sorting if needed
    if ($searchtype == 'assess') {
        foreach ($res as $k => $v) {
            $res[$k]['grp'] = $qidmap[$v['qid']][0];
            $res[$k]['qn'] = $qidmap[$v['qid']][1];
        }
        usort($res, function ($a, $b) {
            if ($a['grp'] != $b['grp']) {
                return ($a['grp'] < $b['grp']) ? -1 : 1;
            } else if ($a['qn'] != $b['qn']) {
                return ($a['qn'] < $b['qn']) ? -1 : 1;
            } else {
                return ($a['id'] < $b['id']) ? -1 : 1;
            }
        });
        $out = ['qs' => $res, 'names' => $aidnames, 'type'=>'assess'];
    } else if ($searchtype == 'libs') {
        usort($res, function ($a, $b) use ($sortorder) {
            if ($a['libid'] != $b['libid']) {
                return ($a['libid'] < $b['libid']) ? -1 : 1;
            } else if ($a['broken'] != $b['broken']) {
                return ($a['broken'] < $b['broken']) ? -1 : 1;
            } else if ($a['junkflag'] != $b['junkflag']) {
                return ($a['junkflag'] < $b['junkflag']) ? -1 : 1;
            } else if ($sortorder[$a['libid']] == 1) { // alpha
                return strnatcasecmp($a['descr'], $b['descr']);
            } else {
                return ($a['id'] < $b['id']) ? -1 : 1;
            }
        });
        $out = ['qs' => $res, 'names' => $libnames, 'type'=>'libs'];
    } else {
        $out = ['qs' => $res, 'type'=>'all'];
    }
    $out['offset'] = $offset;
    if (count($res) == $max) {
        $out['next'] = $offset + $max;
    }
    if ($offset > 0) {
        $out['prev'] = $offset - $max;
    }
    return $out;
}
