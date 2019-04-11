<?php
//IMathAS:  Federated libraries update pull
//(c) 2017 David Lippman

//TODO: Fix handling of library items when questions are deleted, undeleted
//      when questions are added

require("../init.php");
require_once("../includes/filehandler.php");

if ($myrights<100) {
	echo "Not authorized";
	exit;
}

$peer = intval($_GET['peer']);
$mypeername = isset($CFG['federatedname'])?$CFG['federatedname']:$installname;

function print_header() {
	global $peer;
	echo '<h1>Pulling from Federation Peer</h1>';
	echo '<form method="post" action="federationpull.php?peer='.Sanitize::onlyInt($peer).'">';
}
$placeinhead = '<style type="text/css">ins {background-color: #d4fcbc;} del {background-color: #fbb6c2;}</style>';


//look up the peer to call
$stm = $DBH->prepare('SELECT peername,peerdescription,secret,url FROM imas_federation_peers WHERE id=:id');
$stm->execute(array(':id'=>$peer));
if ($stm->rowCount()==0) {
	echo 'Invalid peer ID';
	exit;
}
$peerinfo = $stm->fetch(PDO::FETCH_ASSOC);
if (function_exists("hash_hmac")) {
	$computed_signature =  base64_encode(hash_hmac('sha1', $mypeername, $peerinfo['secret'], true));
} else {
	$computed_signature = base64_encode(custom_hmac('sha1', $mypeername, $peerinfo['secret'], true));
}

//see if we have a pull to continue
$stm = $DBH->prepare('SELECT id,pulltime,step,fileurl,record FROM imas_federation_pulls WHERE step<10 AND peerid=:id ORDER BY pulltime DESC LIMIT 1');
$res = $stm->execute(array(':id'=>$peer));
if ($stm->rowCount()==0 || $_GET['stage']==-1) {
	$continuing = false;
} else {
	$continuing = true;
	$pullstatus = $stm->fetch(PDO::FETCH_ASSOC);
	$record = json_decode($pullstatus['record'], true);
	$since = $record['since'];
}

$now = time();

if (!$continuing) {  //start a fresh pull
	//look up our last successful pull to them
	$stm = $DBH->prepare('SELECT pulltime FROM imas_federation_pulls WHERE peerid=:id AND step=99 ORDER BY pulltime DESC LIMIT 1');
	$res = $stm->execute(array(':id'=>$peer));
	if ($stm->rowCount()==0) {
		$since = 0;
	} else {
		$since = $stm->fetchColumn(0);
	}

	$record = array('since'=>$since);

	//pull from remote
	$getdata = http_build_query( array(
		'peer'=>$mypeername,
		'sig'=>$computed_signature,
		'since'=>$since,
		'stage'=>0));
	$data = file_get_contents($peerinfo['url'].'/admin/federatedapi.php?'.$getdata);

	//store for our use
	storecontenttofile($data, 'fedpulls/'.$peer.'_'.$now.'_0.json', 'public');

	$parsed = json_decode($data, true);
	if ($parsed===NULL) {
		echo 'Invalid data received';
		exit;
	} else if ($parsed['stage']!=0) {
		echo 'Wrong data stage sent';
		exit;
	}

	//note that we've pulled it
	$query = 'INSERT INTO imas_federation_pulls (peerid,pulltime,step,fileurl,record) VALUES ';
	$query .= "(:peerid, :pulltime, 0, :fileurl, :record)";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':peerid'=>$peer, ':pulltime'=>$now, ':fileurl'=>'fedpulls/'.$peer.'_'.$now.'_0.json', ':record'=>json_encode($record)));
	$done = false;
	$autocontinue = true;
} else if ($pullstatus['step']==0 && !isset($_POST['record'])) {
	//have pulled library info
	//do interactive confirm.
	require("../header.php");
	print_header();

	echo '<h1>Updating Libraries</h1>';

	$data = json_decode(file_get_contents(getfopenloc($pullstatus['fileurl'])), true);

	$libs = array();
	$libnames = array(0=>'Root');
	foreach ($data['data'] as $i=>$lib) {
		if (ctype_digit($lib['uid'])) {
			$libs[] = $lib['uid'];
			$libnames[$lib['uid']] = $lib['n'];
		} else {
			//remove any invalid uniqueids
			unset($data['data'][$i]);
		}
	}
	if (count($libs)==0) {
		echo '<p>No libraries to update</p>';
	} else {
		$liblist = implode(',', $libs);  //sanitized above

		//pull local info on these libraries
		$query = 'SELECT A.id,A.uniqueid,A.federationlevel,A.name,A.deleted,A.lastmoddate,A.parent,B.uniqueid as parentuid,B.name AS parentname ';
		$query .= 'FROM imas_libraries AS A LEFT JOIN imas_libraries AS B ON A.parent=B.id ';
		$query .= "WHERE A.uniqueid IN ($liblist)";
		$stm = $DBH->query($query);
		$libdata = array();
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			if ($row['parent']==0) { $row['parentuid'] = 0;}
			$libdata[$row['uniqueid']] = $row;
			$libnames[$row['parentuid']] = $row['parentname'];
		}
		$toadd = array();
		$tochg = array();
		$neednames = array();
		//for each sent library, figure out what's changed.
		foreach ($data['data'] as $lib) {
			if (!isset($libdata[$lib['uid']])) {
				if ($lib['d']==0) {
					if (!isset($libnames[$lib['p']])) {
						$neednames[] = $lib['p'];
					}
					$parent = $lib['p'];
					$toadd[] = array($lib['uid'], $lib['n'], $lib['fl'], $parent);
				}
			} else {
				$curlib = $libdata[$lib['uid']];
				$chgs = array();
				if ($lib['fl']!=$curlib['federationlevel']) {
					$chgs['fedlevel'] = array($lib['fl'],$curlib['federationlevel']);
				}
				if ($lib['n']!=$curlib['name']) {
					$chgs['name'] = $lib['n'];
				}
				if ($lib['p']!=$curlib['parentuid']) {
					if (!isset($libnames[$lib['p']])) {
						$neednames[] = $lib['p'];
					}
					$chgs['parent'] = array($lib['p'], $curlib['parentuid']);
				}
				if ($lib['d']!=$curlib['deleted']) {
					$chgs['del'] = array($lib['d'],$curlib['deleted']);
				}
				if (count($chgs)>0) {
					if ($curlib['lastmoddate']>$since && $curlib['lastmoddate']!=$lib['lm']) {
						$chgs['localmod'] = $curlib['lastmoddate'];
					}
					$tochg[] = array($lib['uid'], $curlib['name'], $chgs);
				}
			}
		}
		foreach ($neednames as $k=>$v) {
			if (!ctype_digit($v)) {
				unset($neednames[$k]);
			}
		}
		if (count($neednames)>0) {
			$neednamelist = implode(',', $neednames);
			$stm = $DBH->query("SELECT uniqueid,name FROM imas_libraries WHERE uniqueid IN ($neednamelist)");
			while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
				$libnames[$row['uniqueid']] = $row['name'];
			}
		}

		echo '<h2>Libraries to Add</h2>';
		if (count($toadd)==0) {
			echo '<p>No libraries to add</p>';
		} else {
			echo '<table class="gb">';
			echo '<thead><tr>';
			echo '<th>Add?</th>';
			echo '<th>Name</th>';
			echo '<th>Level</th>';
			echo '<th>Parent</th>';
			echo '</tr></thead><tbody>';

			foreach ($toadd as $a) {
				if (($a[3]>0 && !isset($libnames[$a[3]])) || ($a[3]==0 && $a[2]<2)) {
					//skip if wrong level, or if parent doesn't exist;
					continue;
				}
				echo '<tr><td><input type="checkbox" name="toadd'.$a[0].'" value="1" checked/></td>';
				echo '<td>'.$a[1].'</td>';
				echo '<td><select name="fedlevel'.$a[0].'">';
				echo ' <option value=1 '.($a[2]==1?'selected':'').'>Federated</option>';
				echo ' <option value=2 '.($a[2]==2?'selected':'').'>Top Level Federated</option>';
				echo '</select></td>';
				echo '<td>'.($a[3]==0?'Root':$libnames[$a[3]]).'</td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
		}

		echo '<h2>Libraries to Change</h2>';
		if (count($tochg)==0) {
			echo '<p>No libraries to change</p>';
		} else {
			echo '<table class="gb gridded">';
			echo '<thead><tr>';
			echo '<th>Current Name</th>';
			echo '<th>Changes</th>';
			echo '</tr></thead><tbody>';

			foreach ($tochg as $a) {
				echo '<tr><td>'.$a[1].'</td>';
				echo '<td>';
				foreach ($a[2] as $type=>$chginfo) {
					if ($type=='localmod') {
						echo '<p>Note: Library modified locally since last pull</p>';
					} else if ($type=='fedlevel') {
						echo '<p>Fed Level<br/>Current: ';
						if ($chginfo[1]==2) { echo 'Top Level Federated';}
						else if ($chginfo[1]==1) { echo 'Federated';}
						else { echo 'Not Federated';}
						echo '<br/>New: ';
						echo '<select name="fedlevel'.$a[0].'">';
						echo ' <option value=0>Not Federated</option>';
						echo ' <option value=1 '.($chginfo[0]==1?'selected':'').'>Federated</option>';
						echo ' <option value=2 '.($chginfo[0]==2?'selected':'').'>Top Level Federated</option>';
						echo '</select></p>';
					} else if ($type=='name') {
						echo '<p>Name<br/>Current: '.$a[1];
						echo '<br/><input type=checkbox name="chgname'.$a[0].'" value=1 checked/> New: '.$chginfo;
						echo '</p>';
					} else if ($type=='parent') {
						echo '<p>Parent<br/>Current: '.$libnames[$chginfo[1]];
						if (isset($libnames[$chginfo[0]])) {
							echo '<br/><input type=checkbox name="chgparent'.$a[0].'" value=1 checked/> New: '.$libnames[$chginfo[0]];
						} else {// if new parent isn't in system or in pull
							echo '<br/><input type=checkbox name="chgparent'.$a[0].' disabled"/> New: Unknown';
						}
						echo '</p>';
					} else if ($type=='del') {
						echo '<p>Deleted<br/>Current: '.($chginfo[1]==1?'Yes':'No');
						echo '<br/><input type=checkbox name="chgdel'.$a[0].'" value=1 checked/> New: '.($chginfo[0]==1?'Yes':'No');
						echo '</p>';
					}
				}
				echo '</td></tr>';
			}

			echo '</tbody></table>';
		}
	}
	echo '<input type="submit" name="record" value="Record"/>';

	$done = false;
	$autocontinue = false;

} else if ($pullstatus['step']==0 && isset($_POST['record'])) {
	//have postback from library confirmation

	//$record['step0'] = $_POST;

	$data = json_decode(file_get_contents(getfopenloc($pullstatus['fileurl'])), true);

	$libs = array();
	$parentref = array();
	$altparents = array();
	foreach ($data['data'] as $i=>$lib) {
		if (ctype_digit($lib['uid']) && ctype_digit($lib['p'])) {
			$libs[] = $lib['uid'];
			//build a backref for parents to children
			if (isset($_POST['toadd'.$lib['uid']])) {
				if (!isset($parentref[$lib['p']])) {
					$parentref[$lib['p']] = array($lib['uid']);
				} else {
					$parentref[$lib['p']][] = $lib['uid'];
				}
			}
			if (isset($_POST['chgparent'.$lib['uid']])) {
				$altparents[] = $lib['p'];
			}
		} else {
			//remove any invalid uniqueids
			unset($data['data'][$i]);
		}
	}

	if (count($libs)==0) {
		echo '<p>No libraries to update</p>';
	} else {
		//look up info for libraries, new q parents, and chgparent new parents
		$liblist = implode(',', array_merge($libs,array_keys($parentref),$altparents));  //sanitized above

		//pull local info on these libraries
		$query = 'SELECT A.id,A.uniqueid,A.parent,B.uniqueid as parentuid ';
		$query .= 'FROM imas_libraries AS A LEFT JOIN imas_libraries AS B ON A.parent=B.id ';
		$query .= "WHERE A.uniqueid IN ($liblist)";
		$stm = $DBH->query($query);
		$localids = array();
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			if ($row['parent']===null) {$row['parent'] = 0;}
			if ($row['parent']==0) { $row['parentuid'] = 0;}
			//backref for parents to children
			if (!isset($parentref[$row['parentuid']])) {
				$parentref[$row['parentuid']] = array($row['uid']);
			} else {
				$parentref[$lib['parentuid']][] = $row['uid'];
			}
			$localid[$row['uniqueid']] = $row['id'];
			$localid[$row['parentuid']] = $row['parent'];
		}
		$childremcnt = 0;

		function unsetchildren($parentlib) {
			global $parentref,$childremcnt;
			foreach ($parentref[$parentlib] as $childlib) {
				if (isset($_POST['toadd'.$childlib])) {
					unset($_POST['toadd'.$childlib]);
					$childremcnt++;
				}
				if (isset($parentref[$childlib])) {
					unsetchildren($childlib);
				}
			}
		}

		//don't add any libraries if we're not adding the parent lib
		foreach ($data['data'] as $lib) {
			if (!isset($localid[$lib['uid']])) {
				//new library
				if (!isset($_POST['toadd'.$lib['uid']])) {
					//we're not ading this library, so unset any children adds
					unsetchildren($lib['uid']);
				}
			}
		}

		//don't change any parents if we're not adding the new parent
		foreach ($data['data'] as $lib) {
			if (isset($localid[$lib['uid']])) {
				//changed library
				if (isset($_POST['chgparent'.$lib['uid']])) {
					//we're changing the parent - let's make sure
					//the new parent is either local or we're
					//actually adding it
					//if not, don't change the parent
					if (!isset($localid[$lib['p']]) && !isset($_POST['toadd'.$lib['uid']])) {
						echo $lib['p'];
						print_r($localid);
						unset($_POST['chgparent'.$lib['uid']]);
					}
				}
			}
		}

		//now we can actually do the adds and changes
		$parentstoupdate = array();
		foreach ($data['data'] as $lib) {
			if (isset($_POST['toadd'.$lib['uid']])) {
				//add the library
				$thisparent = 0;
				if ($lib['p']>0) {
					if (isset($localid[$lib['p']])) {
						$thisparent = $localid[$lib['p']];
					} else {
						$parentstoupdate[$lib['uid']] = $lib['p'];
					}
				}
				$query = 'INSERT INTO imas_libraries (uniqueid, adddate, lastmoddate, name, ownerid, federationlevel, parent, groupid) ';
				$query .= 'VALUES (:uniqueid, :adddate, :lastmoddate, :name, :ownerid, :federationlevel, :parent, :groupid)';
				$stm = $DBH->prepare($query);
				$stm->execute(array(':uniqueid'=>$lib['uid'], ':adddate'=>$pullstatus['pulltime'], ':lastmoddate'=>$pullstatus['pulltime'],
					':name'=>$lib['n'], ':ownerid'=>$userid, ':federationlevel'=>$_POST['fedlevel'.$lib['uid']],
					':parent'=>$thisparent, ':groupid'=>$groupid));
				//record new ID
				"Adding lib<Br/>";
				$localid[$lib['uid']] = $DBH->lastInsertId();
			}
		}
		//update parents if needed
		$stm = $DBH->prepare("UPDATE imas_libraries SET parent=:parent WHERE id=:id");
		foreach ($parentstoupdate as $libuid=>$libparentuid) {
			$stm->execute(array(':parent'=>$localid[$libparentuid], ':id'=>$localid[$libuid]));
		}

		//now we can actually do the changes
		foreach ($data['data'] as $lib) {
			if (isset($_POST['fedlevel'.$lib['uid']])) {
				$stm = $DBH->prepare("UPDATE imas_libraries SET federationlevel=:fedlevel,lastmoddate=:lastmod WHERE id=:id AND federationlevel<>:fedlevel2");
				$stm->execute(array(':fedlevel'=>$_POST['fedlevel'.$lib['uid']], ':fedlevel2'=>$_POST['fedlevel'.$lib['uid']],
					':lastmod'=>$pullstatus['pulltime'],
					':id'=>$localid[$lib['uid']]));
				echo "setting fedlive for ".$localid[$lib['uid']].'<Br/>';
			}
			if (isset($_POST['chgname'.$lib['uid']])) {
				$stm = $DBH->prepare("UPDATE imas_libraries SET name=:name,lastmoddate=:lastmod WHERE id=:id");
				$stm->execute(array(':name'=>$lib['n'],
					':lastmod'=>$pullstatus['pulltime'],
					':id'=>$localid[$lib['uid']]));
				echo "changing name for ".$localid[$lib['uid']].'<Br/>';
			}
			if (isset($_POST['chgdel'.$lib['uid']])) {
				$stm = $DBH->prepare("UPDATE imas_libraries SET deleted=:deleted,lastmoddate=:lastmod WHERE id=:id");
				$stm->execute(array(':deleted'=>$lib['d'],
					':lastmod'=>$pullstatus['pulltime'],
					':id'=>$localid[$lib['uid']]));

				//also delete library items if deleting
				if ($lib['d']==1) {
					$stm = $DBH->prepare("UPDATE imas_library_items SET deleted=1,lastmoddate=:lastmod WHERE libid=:id");
					$stm->execute(array(':lastmod'=>$pullstatus['pulltime'],
						':id'=>$localid[$lib['uid']]));
				}
				echo "changing delete for ".$localid[$lib['uid']].'<Br/>';
			}
			if (isset($_POST['chgparent'.$lib['uid']])) {
				$stm = $DBH->prepare("UPDATE imas_libraries SET parent=:parent,lastmoddate=:lastmod WHERE id=:id");
				$stm->execute(array(':parent'=>$localid[$lib['p']],
					':lastmod'=>$pullstatus['pulltime'],
					':id'=>$localid[$lib['uid']]));
				echo "changing parent for ".$localid[$lib['uid']].'<Br/>';
			}

		}
	}

	//update step number and redirect to start step 1
	$stm = $DBH->prepare("UPDATE imas_federation_pulls SET step=1,record=:record WHERE id=:id");
	$stm->execute(array(':record'=>json_encode($record), ':id'=>$pullstatus['id']));

	$done = false;
	$autocontinue = true;

} else if ($pullstatus['step']==1 && !isset($_POST['record'])) {
	//pull step 1 from remote

	if (isset($record['stage1offset'])) {
		$offset = $record['stage1offset'];
	} else {
		$offset = 0;
	}
	$getdata = http_build_query( array(
		'peer'=>$mypeername,
		'sig'=>$computed_signature,
		'since'=>$since,
		'stage'=>1,
		'offset'=>$offset));
	$data = file_get_contents($peerinfo['url'].'/admin/federatedapi.php?'.$getdata);

	//store for our use
	storecontenttofile($data, 'fedpulls/'.$peer.'_'.$pullstatus['pulltime'].'_1.json', 'public');

	$parsed = json_decode($data, true);
	if ($parsed===NULL) {
		echo 'Invalid data received';
		exit;
	} else if ($parsed['stage']!=1) {
		echo 'Wrong data stage sent';
		exit;
	}
	//update pull record
	$query = 'UPDATE imas_federation_pulls SET fileurl=:fileurl,record=:record,step=2 WHERE id=:id';
	$stm = $DBH->prepare($query);
	$stm->execute(array(':fileurl'=>'fedpulls/'.$peer.'_'.$pullstatus['pulltime'].'_1.json',
											':record'=>json_encode($record), ':id'=>$pullstatus['id']));

	$autocontinue = true;
	$done = false;
} else if ($pullstatus['step']==2 && !isset($_POST['record'])) {
	//have pulled a batch of questions.
	//do interactive confirmation

	$data = json_decode(file_get_contents(getfopenloc($pullstatus['fileurl'])), true);
	$placeinhead .= '<script type="text/javascript">
	function chkall(n) {
		$(".Q"+n).prop("checked",true);
	}
	function chknone(n) {
		$(".Q"+n).prop("checked",false);
	}
	function chkall2(n) {
		$(".U"+n).prop("checked",true);
	}
	function chknone2(n) {
		$(".U"+n).prop("checked",false);
	}
	</script>';
	require("../header.php");
	print_header();

	echo '<h1>Updating Questions Batch</h1>';

	echo '<input type="hidden" name="nextoffset" value="'.Sanitize::onlyInt($data['nextoffset']).'"/>';

	$quids = array();
	$quidref = array();
	foreach ($data['data'] AS $i=>$q) {
		if (ctype_digit($q['uniqueid'])) {
			$quids[] = $q['uniqueid'];
			$quidref[$q['uniqueid']] = $i;
		} else {
			//remove any invalid uniqueids
			unset($data['data'][$i]);
		}
	}
	if (count($quids)==0) {
		echo '<p>No questions to update</p>';
	} else {
		require("../includes/diff.php");
		$licenses = array(
			0=>'Copyrighted',
			1=>'IMathAS / WAMAP / MyOpenMath Community License',
			2=>'Public Domain',
			3=>'Creative Commons Attribution-NonCommercial-ShareAlike',
			4=>'Creative Commons Attribution-ShareAlike');
		//pull library names and local ids
		$stm = $DBH->query("SELECT uniqueid,id,name FROM imas_libraries WHERE federationlevel>0");
		$libdata = array();
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			$libdata[$row['uniqueid']] = array('id'=>$row['id'], 'name'=>$row['name']);
		}

		//pull existing library items for imported questions
		$placeholders = Sanitize::generateQueryPlaceholders($quids);
		$query = "SELECT il.uniqueid,ili.id,ili.qsetid,ili.deleted,ili.junkflag,ili.lastmoddate FROM imas_libraries AS il ";
		$query .= "JOIN imas_library_items AS ili ON il.id=ili.libid ";
		$query .= "JOIN imas_questionset AS iq ON ili.qsetid=iq.id ";
		$query .= "WHERE iq.uniqueid IN ($placeholders)";
		$stm = $DBH->prepare($query);
		$stm->execute($quids);
		$qlibs= array();
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			if (!isset($qlibs[$row['qsetid']])) {
				$qlibs[$row['qsetid']] = array();
			}
			$qlibs[$row['qsetid']][$row['uniqueid']] = array('deleted'=>$row['deleted'],
				'junkflag'=>$row['junkflag'], 'lastmoddate'=>$row['lastmoddate'], 'iliid'=>$row['id']);
		}

		//pull existing question info
		//we'll interactive ask about these as needed, then worry about
		//questions that weren't already on the system
		$stm = $DBH->prepare("SELECT * FROM imas_questionset WHERE uniqueid IN ($placeholders)");
		$stm->execute($quids);
		while ($local = $stm->fetch(PDO::FETCH_ASSOC)) {
			$remote = $data['data'][$quidref[$local['uniqueid']]];
			//unset $quidref for this question so we know it's been used
			unset($quidref[$local['uniqueid']]);
			//if remote lastmod==adddate, and local lastmod is newer, skip the question
			// since it wasn't modified remotely after being added
			if ($remote['adddate']==$remote['lastmoddate'] && $local['lastmoddate']>$remote['lastmoddate']) {
				continue; //just skip it
			} else if ($local['lastmoddate']==$remote['lastmoddate']) {
				//same lastmoddate local and remote - skip it
				continue;
			}

			//check libraries
			$livesinalib = false;  $libhtml = '';
			$remotelibs = array();
			foreach ($remote['libs'] as $rlib) {
				$remotelibs[] = $rlib['ulibid'];
				if (isset($qlibs[$local['id']][$rlib['ulibid']])) {
					$llib = $qlibs[$local['id']][$rlib['ulibid']];
					$livesinalib = true;
					//question is already in lib locally - look for changes.
					if ($rlib['lastmoddate']<$since) {
						//since already on our system, and hasn't been updated since last pull, skip this part
						continue;
					}
					if ($llib['deleted']==1 && $rlib['deleted']==0) {
						$libhtml .= '<li>Library assignment: '.Sanitize::encodeStringForDisplay($libdata[$rlib['ulibid']]['name']).'. ';
						$libhtml .= 'Not deleted remotely, deleted locally. ';
						$libhtml .= '<input type="checkbox" class="Q'.$local['id'].'" name="undeleteli[]" value="'.$llib['iliid'].'" checked> Un-delete locally and update</li>';
					} else if ($llib['deleted']==0 && $rlib['deleted']==1) {
						$libhtml .= '<li>Library assignment: '.Sanitize::encodeStringForDisplay($libdata[$rlib['ulibid']]['name']).'. ';
						$libhtml .= 'Deleted remotely, not deleted locally. ';
						if ($llib['junkflag']==1) {
							$libhtml .= 'Marked as wrong lib locally. ';
							$libhtml .= '<input type="checkbox" name="deleteli[]" value="'.$llib['iliid'].'" checked> Delete locally </p>';
						} else {
							$libhtml .= '<input type="checkbox" name="deleteli[]" value="'.$llib['iliid'].'"> Delete locally </p>';
						}
					} else if ($llib['junkflag']==1 && $rlib['junkflag']==0) {
						$libhtml .= '<li>Library assignment: '.Sanitize::encodeStringForDisplay($libdata[$rlib['ulibid']]['name']).'. ';
						$libhtml .= 'Marked OK remotely, Marked as wrong lib locally. ';
						$libhtml .= '<input type="checkbox" name="unjunkli[]" value="'.$llib['iliid'].'" checked> Un-mark as wrong lib</li>';
					} else if ($llib['junkflag']==0 && $rlib['junkflag']==1) {
						$libhtml .= '<li>Library assignment: '.Sanitize::encodeStringForDisplay($libdata[$rlib['ulibid']]['name']).'. ';
						$libhtml .= 'Marked as wrong lib remotely, marked OK locally. ';
						$libhtml .= '<input type="checkbox" name="junkli[]" value="'.$llib['iliid'].'" checked> Mark as wrong lib </li>';
					}

				} else if (isset($libdata[$rlib['ulibid']]) && $rlib['deleted']==0 && $rlib['junkflag']==0) {
					//new library assignment to an existing lib, and it isn't deleted or junk
					$libhtml .= '<li>';
					$libhtml = 'New library assignment: '.Sanitize::encodeStringForDisplay($libdata[$rlib['ulibid']]['name']).'.';
					//value is localqsetid:locallibid
					$libhtml .= '<input type="checkbox" class="Q'.$local['id'].'" name="addli[]" value="'.$local['id'].':'.$libdata[$rlib['ulibid']]['id'].'" checked> Add it</li>';
					$livesinalib = true;
				}
			}
			if (!$livesinalib) {
				//we must not have created local copies of any of the libraries the question
				//is in. No point asking about questions where we didn't bring in the library
				continue;
			}
			$chghtml = '';
			$changedfields = array();
			if ($remote['deleted']==1 && $local['deleted']==0) {
				$chghtml .= '<p>Deleted remotely, not deleted locally.  ';
				$chghtml .= '<input type="checkbox" name="deleteq-'.$local['uniqueid'].'" value="1"> Delete locally </p>';
			} else if ($remote['deleted']==0 && $local['deleted']==1) {
				$chghtml .= '<p>Not deleted remotely, deleted locally.  ';
				$chghtml .= '<input type="checkbox" class="Q'.$local['id'].'" name="undeleteq-'.$local['uniqueid'].'" value="1" checked> Un-delete locally and update</p>';
				$chghtml .= '<p>Library assignments, if undeleted:<ul>'.$libhtml.'</ul></p>';
			} else {
				//show changes to most fields
				$fields = array('author','description', 'qtype', 'control',	'qcontrol', 'qtext', 'answer','extref', 'broken',
					'solution', 'solutionopts', 'license','ancestorauthors', 'otherattribution');
				if ($remote['lastmoddate']>$local['lastmoddate']) {
					$defchk = 'checked';
				} else {
					$defchk = '';
				}
				foreach ($fields as $field) {
					$remote[$field] = preg_replace("/\s+\n/","\n",$remote[$field]);
					$local[$field] = preg_replace("/\s+\n/","\n",$local[$field]);
					$remote[$field] = str_replace(array("\r","\n"),array("",' <br/>'),Sanitize::encodeStringForDisplay(trim($remote[$field])));
					$local[$field] = str_replace(array("\r","\n"),array("",' <br/>'),Sanitize::encodeStringForDisplay(trim($local[$field])));
					if ($field=='ancestorauthors' && $remote[$field]=='') {
						continue;
					}
					if ($remote[$field]!=$local[$field]) {
						if ($field=='license') {
							$remote['field'] = $licenses[$remote[$field]];
							$local['field'] = $licenses[$local[$field]];
						}
						$changedfields[] = $field;
						$chghtml .= '<p>'.ucwords($field). ' changed. ';
						$chghtml .= '<input type="checkbox" class="Q'.$local['id'].'" name="update'.$field.'-'.$local['uniqueid'].'" value="1" '.$defchk.'> Update it</p>';
						$chghtml .= '<table class="gridded"><tr><td>';
						//$chghtml .= $local[$field].'</td><td>'.$remote[$field].'</td><td>';
						$chghtml .= htmlDiff($local[$field],$remote[$field]);
						$chghtml .= '</td><tr/></table>';
						//$chghtml .= '<table class="gridded"><tr><td>Local</td><td>Remote</td></tr>';
						//$chghtml .= '<tr><td>'.str_replace("\n",'<br/>',Sanitize::encodeStringForDisplay($local[$field])).'</td>';
						//$chghtml .= '<td>'.str_replace("\n",'<br/>',Sanitize::encodeStringForDisplay($remote[$field])).'</td></tr></table>';
					}
				}
				//TODO:  Figure a way to handle replaceby
				//plan: Update qimages if control is updated
				//TODO: figure out how to tell if qimages are changed
				if ($libhtml!='') {
					$chghtml .= '<p>Library assignments:<ul>'.$libhtml.'</ul></p>';
				}
			}
			if ($chghtml == '' || (count($changedfields)==1 && $changedfields[0]=='author')) {
				//no changes at all - don't display anything
				continue;
			}

			echo '<p><b>Question '.$local['id'].'. '.Sanitize::encodeStringForDisplay($local['description']).'</b>';
			echo '<input type="hidden" name="uidref'.$local['uniqueid'].'" value="'.$local['id'].'" />';
			echo '</p>';
			echo '<p>';
			if ($local['lastmoddate']<$since) {
				//it's been updated remotely but not locally
				echo '<span style="color: #ff6600;">Changed Remotely - no local conflict</span>';
			} else if ($local['lastmoddate']==$local['adddate']) {
				//it's been updated both remotely and locally since $since - potential conflict
				//but adddate=lastmoddate implying it was imported
				echo '<span style="color: #ff0000;">Changed Remotely and Imported Locally - potential conflict</span>';
			} else {
				//it's been updated both remotely and locally - potential conflict
				echo '<span style="color: #ff0000;">Changed Remotely and Locally - potential conflict</span>';
				echo '. Local: '.tzdate('Y-m-d',$local['lastmoddate']).', Remote: '.tzdate('Y-m-d',$remote['lastmoddate']);
			}
			echo '. Check <a href="#" onclick="chkall('.$local['id'].');return false;">All</a> ';
			echo '<a href="#" onclick="chknone('.$local['id'].');return false;">None</a>';
			echo '</p>';
			echo $chghtml;
		}

		//handle any new questions
		//we've unset any $quidref that were used, so loop over unused
		if (count($quidref)>0) {
			echo '<h2>Adding Questions</h2>';
		}
		foreach ($quidref as $uqid=>$i) {
			$remote = $data['data'][$i];
			//check libraries
			$livesinalib = false;  $libhtml = '';
			$remotelibs = array();
			foreach ($remote['libs'] as $rlib) {
				$remotelibs[] = $rlib['ulibid'];
				if (isset($libdata[$rlib['ulibid']]) && $rlib['deleted']==0 && $rlib['junkflag']==0) {
					//new library assignment to an existing lib, and it isn't deleted or junk
					$libhtml .= '<li>';
					$libhtml = 'New library assignment: '.Sanitize::encodeStringForDisplay($libdata[$rlib['ulibid']]['name']).'.';
					//value is localqsetid:locallibid
					$libhtml .= '<input type="checkbox" class="U'.$remote['uniqueid'].'" name="addnewqli[]" value="'.$remote['uniqueid'].':'.$libdata[$rlib['ulibid']]['id'].':'.$rlib['lastmoddate'].'" checked> Add it</li>';
					$livesinalib = true;
				}
			}
			if (!$livesinalib) {
				//we must not have created local copies of any of the libraries the question
				//is in. No point asking about questions where we didn't bring in the library
				continue;
			}
			echo '<h3><b>Question UID '.$remote['uniqueid'].'</b>.</h3> ';
			echo '<p>Description: '.Sanitize::encodeStringForDisplay($remote['description']);
			echo '. Check <a href="#" onclick="chkall2('.$remote['uniqueid'].');return false;">All</a> ';
			echo '<a href="#" onclick="chknone2('.$remote['uniqueid'].');return false;">None</a></p>';
			echo '<p><input type="checkbox" class="U'.$remote['uniqueid'].'" name="addnewq-'.$remote['uniqueid'].'" value="1" checked> Add Question.</p>';
			echo '<p>Library assignments: <ul>'.$libhtml.'</ul></p>';
		}

	}
	echo '<input type="submit" name="record" value="Record"/>';

	$done = false;
	$autocontinue = false;
} else if ($pullstatus['step']==2 && isset($_POST['record'])) {
	//record results from interactive.
	//when done, look at nextoffset:  if -1, then all questions have been sent,
	//and autocontinue to step 3
	//if not, update nextoffset record and reset step to 1 before autocontinue

	//$record['step1-'.$record['stage1offset']] = $_POST;

	$data = json_decode(file_get_contents(getfopenloc($pullstatus['fileurl'])), true);

	$quids = array();
	$quidref = array();
	$localqidref = array();
	foreach ($data['data'] AS $i=>$q) {
		if (ctype_digit($q['uniqueid'])) {
			$quids[] = $q['uniqueid'];
			$quidref[$q['uniqueid']] = $i;
			if (isset($_POST['uidref'.$q['uniqueid']])) {
				$localqidref[$q['uniqueid']] = Sanitize::onlyInt($_POST['uidref'.$q['uniqueid']]);
			}
		} else {
			//remove any invalid uniqueids
			unset($data['data'][$i]);
		}
	}

	$delq = $DBH->prepare("UPDATE imas_questionset SET deleted=1,lastmoddate=:lastmoddate WHERE id=:id");
	$delli_by_qid = $DBH->prepare("UPDATE imas_library_items SET deleted=1,lastmoddate=:lastmoddate WHERE qsetid=:id");
	$del_qimg = $DBH->prepare("DELETE FROM imas_qimages WHERE qsetid=:qsetid");
	$add_qimg = $DBH->prepare("INSERT INTO imas_qimages (qsetid,var,filename,alttext) VALUES (:qsetid,:var,:filename,:alttext)");
	$qfields = array('author','description', 'qtype', 'control',	'qcontrol', 'qtext', 'answer','extref', 'broken',
		'solution', 'solutionopts', 'license','ancestorauthors', 'otherattribution');
	$qallfields = array('uniqueid','adddate','lastmoddate','author','description', 'qtype', 'control','qcontrol', 'qtext', 'answer','extref','broken','deleted',
		'solution', 'solutionopts', 'license','ancestorauthors', 'otherattribution');
	$addq = $DBH->prepare("INSERT INTO imas_questionset (".implode(',',$qallfields).",ownerid) VALUES (:".implode(',:',$qallfields).",:ownerid)");

	//DO WORK
	//loop over questions
	$includedqs = array();  //includecodefrom to resolve
	$includetoresolve = array();
	foreach ($quids as $quid) {
		$remote = $data['data'][$quidref[$quid]];

		//if adding or updating control/qtext, look for includecodefrom
		//resolve immediately if possible; otherwise store for later
		if ((isset($_POST['addnewq-'.$quid]) || isset($_POST['updatecontrol-'.$quid])) && preg_match_all('/includecodefrom\(UID(\d+)\)/',$remote['control'],$matches,PREG_PATTERN_ORDER) >0) {
			foreach ($matches[1] as $includeduid) {
				if (isset($localqidref[$includeduid])) {
					$remote['control'] = str_replace('includecodefrom(UID'.$includeduid.')', 'includecodefrom('.$localqidref[$includeduid].')', $remote['control']);
					/*$remote['control'] = preg_replace_callback('/includecodefrom\(UID(\d+)\)/', function($matches) use ($localqidref) {
							return "includecodefrom(".$localqidref[$matches[1]].")";
						}, $remote['control']);*/
				} else {
					$includedqs[] = $includeduid;
					$includetoresolve[$quid] = 1;
				}
			}
		}
		if ((isset($_POST['addnewq-'.$quid]) || isset($_POST['updateqtext-'.$quid])) && preg_match_all('/includeqtextfrom\(UID(\d+)\)/',$remote['qtext'],$matches,PREG_PATTERN_ORDER) >0) {
			foreach ($matches[1] as $includeduid) {
				if (isset($localqidref[$includeduid])) {
					$remote['qtext'] = str_replace('includeqtextfrom(UID'.$includeduid.')', 'includeqtextfrom('.$localqidref[$includeduid].')', $remote['qtext']);
					/*
					$remote['qtext'] = preg_replace_callback('/includeqtextfrom\(UID(\d+)\)/', function($matches) use ($localqidref) {
							return "includeqtextfrom(".$localqidref[$matches[1]].")";
						}, $remote['qtext']);*/
				} else {
					$includedqs[] = $includeduid;
					$includetoresolve[$quid] = 1;
				}
			}
		}

		if (isset($_POST['deleteq-'.$quid])) {
			//  if isset deleteq-uniqueid then set as deleted
			$delq->execute(array(':lastmoddate'=>$pullstatus['pulltime'], ':id'=>$localqidref[$quid]));
			$delli_by_qid->execute(array(':lastmoddate'=>$pullstatus['pulltime'], ':id'=>$localqidref[$quid]));
			//echo "Deleting $quid<br/>";
		} else if (isset($_POST['addnewq-'.$quid])) {
			//  if isset addnewq-uniqueid then Insert
			$vals = array();
			foreach ($qallfields as $field) {
				$vals[':'.$field] = $remote[$field];
			}
			$vals[':adddate'] = $pullstatus['pulltime'];
			$vals[':lastmoddate'] = $pullstatus['pulltime'];
			$vals[':ownerid'] = $userid;
			$addq->execute($vals);
			//echo "Adding $quid<br/>";
			$localqidref[$quid] = $DBH->lastInsertId();
		} else {
			// if isset undeleteq-uniqueid then update all
			// if isset updateField-uniqueid then update those
			$chgs = array();
			$vals = array();
			foreach ($qfields as $field) {
				if (isset($_POST['update'.$field.'-'.$quid]) || isset($_POST['undeleteq-'.$quid])) {
						$chgs[] = $field.'=:'.$field;
						$vals[':'.$field] = $remote[$field];
				}
			}
			if (isset($_POST['undeleteq-'.$quid])) {
				$chgs[] = "deleted=0";
			}
			if (count($chgs)>0) {
				//there are changes to make - make them
				$chgs[] = "lastmoddate=:lastmoddate";
				//TODO: this is going to cause problems, as if the lastmoddate is in the past,
				// other sites pulling won't see the change, since it won't be after "since"
				// solution:  use pulltime as lastmoddate
				//            to prevent sending back to source peer, in federatedapi
				//						we need to look for _pulls for the peer, look at all the pulltime
				//						records, and skip them.
				$vals[":lastmoddate"] = $pullstatus['pulltime'];
				$sets = implode(',', $chgs);
				$vals[':id'] = $localqidref[$quid];
				$stm = $DBH->prepare("UPDATE imas_questionset SET $sets WHERE id=:id");
				$stm->execute($vals);
				
				//echo "Updating $quid<br/>";
				if (isset($_POST['updatecontrol-'.$quid]) && count($remote['imgs'])>0) {
					//TODO:  update imas_qimages
					//lazy - should do better
					//delete old ones
					$del_qimg->execute(array(':qsetid'=>$localqidref[$quid]));
					//add new ones
					foreach ($remote['imgs'] as $img) {
						$add_qimg->execute(array(':qsetid'=>$localqidref[$quid],
							':var'=>$img['var'], ':filename'=>$img['filename'], ':alttext'=>$img['alttext']));
					}
				}
			}
		}
	} // end loop over questions

	if (count($includetoresolve)>0) {
		//lookup backrefs
		$includedbackref = array();
		if (count($includedqs)>0) {
			$placeholders = Sanitize::generateQueryPlaceholders($includedqs);
			$stm = $DBH->prepare("SELECT id,uniqueid FROM imas_questionset WHERE uniqueid IN ($placeholders)");
			$stm->execute($includedqs);
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$includedbackref[$row[1]] = $row[0];
			}
		}
		$qidstoupdate = array_values(array_keys($includetoresolve));
		$placeholders = Sanitize::generateQueryPlaceholders($qidstoupdate);
		$stm = $DBH->prepare("SELECT id,control,qtext,broken,uniqueid FROM imas_questionset WHERE uniqueid IN ($placeholders)");
		$stm->execute($qidstoupdate);
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$thisisbroken = $row[3];
			$quid = $row[4];
			if (isset($_POST['addnewq-'.$quid]) || isset($_POST['updatecontrol-'.$quid])) {
				$control = preg_replace_callback('/includecodefrom\(UID(\d+)\)/', function($matches) use ($includedbackref, &$thisisbroken) {
						if (isset($includedbackref[$matches[1]])) {
							return "includecodefrom(".$includedbackref[$matches[1]].")";
						} else {
							$thisisbroken = 1;
							return "includecodefrom(undefined)";
						}
					}, $row[1]);
			}
			if (isset($_POST['addnewq-'.$quid]) || isset($_POST['updateqtext-'.$quid])) {
				$qtext = preg_replace_callback('/includeqtextfrom\(UID(\d+)\)/', function($matches) use ($includedbackref, &$thisisbroken) {
						if (isset($includedbackref[$matches[1]])) {
							return "includeqtextfrom(".$includedbackref[$matches[1]].")";
						} else {
							$thisisbroken = 1;
							return "includecodefrom(undefined)";
						}
					}, $row[2]);
			}
			$stm2 = $DBH->prepare("UPDATE imas_questionset SET control=:control,qtext=:qtext,broken=:broken WHERE id=:id");
			$stm2->execute(array(':control'=>$control, ':qtext'=>$qtext, ':id'=>$row[0], ':broken'=>$thisisbroken));
		}
	}

	$now = time();

	$livals = array();
	if (isset($_POST['addnewqli'])) {
		foreach ($_POST['addnewqli'] as $liinfo) {
			//loop over addnewqli and resolve uniqueid:locallibid to localqid:locallibid
			$liparts = explode(':', $liinfo);
			if (!isset($localqidref[$liparts[0]])) {
				//don't have a local id - skip it
				continue;
			}
			//echo "Adding new lib item for new q<br/>";
			array_push($livals, $localqidref[$liparts[0]], $liparts[1], $userid, $pullstatus['pulltime']);
		}
	}
	if (isset($_POST['addli'])) {
		foreach ($_POST['addli'] as $liinfo) {
			//loop over addli and add localqid:locallibid
			$liparts = explode(':', $liinfo);
			array_push($livals, $liparts[0], $liparts[1], $userid, $pullstatus['pulltime']);
			//echo "Adding new lib item for existing q<br/>";
		}
	}
	//add new li if any
	if (count($livals)>0) {
		$placeholders = Sanitize::generateQueryPlaceholdersGrouped($livals,4);
		$stm = $DBH->prepare("INSERT INTO imas_library_items (qsetid,libid,ownerid,lastmoddate) VALUES $placeholders");
		$stm->execute($livals);
	}

	//loop over undeleteli, deleteli, unjunkli, junkli and make the change
	if (count($_POST['undeleteli'])>0) {
		$placeholders = Sanitize::generateQueryPlaceholders($_POST['undeleteli']);
		$stm = $DBH->prepare("UPDATE imas_library_items SET deleted=0,lastmoddate=? WHERE id IN ($placeholders)");
		$stm->execute(array_merge(array($pullstatus['pulltime']),$_POST['undeleteli']));
	}
	if (count($_POST['deleteli'])>0) {
		$placeholders = Sanitize::generateQueryPlaceholders($_POST['deleteli']);
		$stm = $DBH->prepare("UPDATE imas_library_items SET deleted=1,lastmoddate=? WHERE id IN ($placeholders)");
		$stm->execute(array_merge(array($pullstatus['pulltime']),$_POST['deleteli']));
	}
	if (count($_POST['unjunkli'])>0) {
		$placeholders = Sanitize::generateQueryPlaceholders($_POST['unjunkli']);
		$stm = $DBH->prepare("UPDATE imas_library_items SET junkflag=0,lastmoddate=? WHERE id IN ($placeholders)");
		$stm->execute(array_merge(array($pullstatus['pulltime']),$_POST['unjunkli']));
	}
	if (count($_POST['junkli'])>0) {
		$placeholders = Sanitize::generateQueryPlaceholders($_POST['junkli']);
		$stm = $DBH->prepare("UPDATE imas_library_items SET junkflag=1,lastmoddate=? WHERE id IN ($placeholders)");
		$stm->execute(array_merge(array($pullstatus['pulltime']),$_POST['junkli']));
	}


	if ($_POST['nextoffset']==-1) {
		//done with questions//update step number and redirect to start step 3
		$stm = $DBH->prepare("UPDATE imas_federation_pulls SET step=3,record=:record WHERE id=:id");
		$stm->execute(array(':record'=>json_encode($record), ':id'=>$pullstatus['id']));
	} else {
		//more questions to pull.  Update offset, return to step 1
		$record['stage1offset'] = $_POST['nextoffset'];
		$stm = $DBH->prepare("UPDATE imas_federation_pulls SET step=1,record=:record WHERE id=:id");
		$stm->execute(array(':record'=>json_encode($record), ':id'=>$pullstatus['id']));
	}
	$done = false;
	$autocontinue = true;

} else if ($pullstatus['step']==3 && !isset($_POST['record'])) {
	//pull step 3 from remote (changed libs, unchanged q's)
	$getdata = http_build_query( array(
		'peer'=>$mypeername,
		'sig'=>$computed_signature,
		'since'=>$since,
		'stage'=>3));
	$data = file_get_contents($peerinfo['url'].'/admin/federatedapi.php?'.$getdata);

	//store for our use
	storecontenttofile($data, 'fedpulls/'.$peer.'_'.$pullstatus['pulltime'].'_3.json', 'public');

	$parsed = json_decode($data, true);
	if ($parsed===NULL) {
		echo 'Invalid data received';
		exit;
	} else if ($parsed['stage']!=3) {
		echo 'Wrong data stage sent';
		exit;
	}
	//update pull record
	$query = 'UPDATE imas_federation_pulls SET fileurl=:fileurl,record=:record,step=4 WHERE id=:id';
	$stm = $DBH->prepare($query);
	$stm->execute(array(':fileurl'=>'fedpulls/'.$peer.'_'.$pullstatus['pulltime'].'_3.json',
											':record'=>json_encode($record), ':id'=>$pullstatus['id']));

	$autocontinue = true;
	$done = false;
} else if ($pullstatus['step']==4 && !isset($_POST['record'])) {
	//have pulled changed libraries (unchanged q's).
	//do interactive confirmation

	$data = json_decode(file_get_contents(getfopenloc($pullstatus['fileurl'])), true);
	require("../header.php");
	print_header();

	//pull library names and local ids
	$stm = $DBH->query("SELECT uniqueid,id,name FROM imas_libraries WHERE federationlevel>0");
	$libdata = array();
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		$libdata[$row['uniqueid']] = array('id'=>$row['id'], 'name'=>$row['name']);
	}

	echo '<h1>Updating Library Assignments for Unchanged Questions</h1>';
	$lookups = array();
	$qlookups = array();
	foreach ($data['data']['libitems'] AS $i=>$rli) {
		if (ctype_digit($rli['uniqueid']) && ctype_digit($rli['ulibid'])) {
			array_push($lookups, $rli['ulibid'],$rli['uniqueid']);
			$qlookups[] = $rli['uniqueid'];
		} else {
			//remove any invalid uniqueids
			unset($data['data']['libitems'][$i]);
		}
	}
	if (count($data['data']['libitems'])==0) {
		echo '<p>No Changes to Make</p>';
	} else {

		$ph = Sanitize::generateQueryPlaceholders($qlookups);
		$stm = $DBH->prepare("SELECT uniqueid,id,description FROM imas_questionset WHERE uniqueid IN ($ph)");
		$stm->execute($qlookups);
		$qdata = array();
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			$qdata[$row['uniqueid']] = array('id'=>$row['id'], 'description'=>$row['description']);
		}

		$ph = Sanitize::generateQueryPlaceholdersGrouped($lookups,2);
		$query = "SELECT ili.id,il.uniqueid AS ulid,iq.uniqueid AS uqid,ili.lastmoddate,ili.junkflag,ili.deleted,iq.deleted as qdel ";
		$query .= "FROM imas_library_items AS ili ";
		$query .= "JOIN imas_libraries AS il ON il.id=ili.libid ";
		$query .= "JOIN imas_questionset AS iq ON iq.id=ili.qsetid ";
		$query .= "WHERE (il.uniqueid,iq.uniqueid) IN ($ph)";
		$stm = $DBH->prepare($query);
		$stm->execute($lookups);
		$llib = array();
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			$llis[$row['ulid'].'-'.$row['uqid']] = $row;
		}
		foreach ($data['data']['libitems'] AS $i=>$rlib) {
			if (isset($llis[$rlib['ulibid'].'-'.$rlib['uniqueid']])) {
				//lib item exists locally
				$llib = $llis[$rlib['ulibid'].'-'.$rlib['uniqueid']];
				//only included if lastmoddate>since, so no need to check
				/*if ($rlib['lastmoddate']<$since) {
					//since already on our system, and hasn't been updated since last pull, skip this part
					continue;
				}*/
				if ($llib['qdel']==1) { //question is deleted locally - skip lib item stuff
					continue;
				}
				if ($llib['deleted']==1 && $rlib['deleted']==0) {
					echo '<p>Library: '.Sanitize::encodeStringForDisplay($libdata[$rlib['ulibid']]['name']).'. ';
					echo 'Question: '.Sanitize::encodeStringForDisplay($qdata[$rlib['uniqueid']]['description']).'. ';
					echo 'Not deleted remotely, deleted locally. ';
					echo '<input type="checkbox" name="undeleteli[]" value="'.$llib['id'].'" checked> Un-delete locally and update</p>';
				} else if ($llib['deleted']==0 && $rlib['deleted']==1) {
					echo '<p>Library: '.Sanitize::encodeStringForDisplay($libdata[$rlib['ulibid']]['name']).'. ';
					echo 'Question: '.Sanitize::encodeStringForDisplay($qdata[$rlib['uniqueid']]['description']).'. ';
					echo 'Deleted remotely, not deleted locally. ';
					if ($llib['junkflag']==1) {
						echo 'Marked as wrong lib locally. ';
						echo '<input type="checkbox" name="deleteli[]" value="'.$llib['id'].'" checked> Delete locally </p>';
					} else {
						echo '<input type="checkbox" name="deleteli[]" value="'.$llib['id'].'"> Delete locally </p>';
					}
				}
				if ($llib['junkflag']==1 && $rlib['junkflag']==0) {
					echo '<p>Library: '.Sanitize::encodeStringForDisplay($libdata[$rlib['ulibid']]['name']).'. ';
					echo 'Question: '.Sanitize::encodeStringForDisplay($qdata[$rlib['uniqueid']]['description']).'. ';
					echo 'Marked OK remotely, Marked as wrong lib locally. ';
					echo '<input type="checkbox" name="unjunkli[]" value="'.$llib['id'].'" checked> Un-mark as wrong lib</p>';
				} else if ($llib['junkflag']==0 && $rlib['junkflag']==1) {
					echo '<p>Library: '.Sanitize::encodeStringForDisplay($libdata[$rlib['ulibid']]['name']).'. ';
					echo 'Question: '.Sanitize::encodeStringForDisplay($qdata[$rlib['uniqueid']]['description']).'. ';
					echo 'Marked as wrong lib remotely, marked OK locally. ';
					echo '<input type="checkbox" name="junkli[]" value="'.$llib['id'].'" checked> Mark as wrong lib </p>';
				}
			} else if (isset($libdata[$rlib['ulibid']]) && isset($qdata[$rlib['uniqueid']]) && $rlib['deleted']==0 && $rlib['junkflag']==0) {
				//new lib item and lib and q already exist
				echo '<p>New library assignment. ';
				echo 'Library: '.Sanitize::encodeStringForDisplay($libdata[$rlib['ulibid']]['name']).'. ';
				echo 'Question: '.Sanitize::encodeStringForDisplay($qdata[$rlib['uniqueid']]['description']).'. ';
				echo '<input type="checkbox" name="addli[]" value="'.$qdata[$rlib['uniqueid']]['id'].':'.$libdata[$rlib['ulibid']]['id'].'" checked> Add it</p>';
			}
		}
	}

	echo '<h1>Updating ReplaceBy Records</h1>';
	$lookups = array();
	$qlookups = array();
	foreach ($data['data']['replacebys'] AS $i=>$rb) {
		if (ctype_digit($rb['uniqueid']) && ctype_digit($rb['replaceby'])) {
			array_push($lookups, $rb['uniqueid'],$rb['replaceby']);
		} else {
			//remove any invalid uniqueids
			unset($data['data']['replacebys'][$i]);
		}
	}
	if (count($data['data']['replacebys'])==0) {
		echo '<p>No Changes to Make</p>';
	} else {

		$ph = Sanitize::generateQueryPlaceholders($lookups);
		$stm = $DBH->prepare("SELECT uniqueid,id,description FROM imas_questionset WHERE uniqueid IN ($ph)");
		$stm->execute($lookups);
		$qdata = array();
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			$qdata[$row['uniqueid']] = array('id'=>$row['id'], 'description'=>$row['description']);
		}

		foreach ($data['data']['replacebys'] AS $i=>$rb) {
			if (!isset($qdata[$rb['uniqueid']]) || !isset($qdata[$rb['replaceby']]) ) {
				//question or replacement isn't on system
				continue;
			}
			echo '<p><b>Question '.$qdata[$rb['uniqueid']]['id'].': '.$qdata[$rb['uniqueid']]['description'].'</b></p>';
			echo '<p><input type="checkbox" name="replacebys[]" value="'.$qdata[$rb['uniqueid']]['id'].':'.$qdata[$rb['replaceby']]['id'].'" checked /> ';
			echo 'Deprecate and replace with Question '.$qdata[$rb['replaceby']]['id'].': '.$qdata[$rb['replaceby']]['description'].'</p>';
		}
	}

	echo '<input type="submit" name="record" value="Record"/>';

	$done = false;
	$autocontinue = false;

} else if ($pullstatus['step']==4 && isset($_POST['record'])) {
	//record results from interactive.
	//$record['step4']] = $_POST;
	$data = json_decode(file_get_contents(getfopenloc($pullstatus['fileurl'])), true);

	foreach ($data['data']['libitems'] AS $i=>$rli) {
		if (ctype_digit($rli['uniqueid']) && ctype_digit($rli['ulibid'])) {

		} else {
			//remove any invalid uniqueids
			unset($data['data']['libitems'][$i]);
		}
	}

	$livals = array();
	if (isset($_POST['addli'])) {
		foreach ($_POST['addli'] as $liinfo) {
			//loop over addli and add localqid:locallibid
			$liparts = explode(':', $liinfo);
			array_push($livals, $liparts[0], $liparts[1], $userid, $pullstatus['pulltime']);
		}
	}
	//add new li if any
	if (count($livals)>0) {
		$placeholders = Sanitize::generateQueryPlaceholdersGrouped($livals,4);
		$stm = $DBH->prepare("INSERT INTO imas_library_items (qsetid,libid,ownerid,lastmoddate) VALUES $placeholders");
		$stm->execute($livals);
	}
	//loop over undeleteli, deleteli, unjunkli, junkli and make the change
	if (count($_POST['undeleteli'])>0) {
		$placeholders = Sanitize::generateQueryPlaceholders($_POST['undeleteli']);
		$stm = $DBH->prepare("UPDATE imas_library_items SET deleted=0,lastmoddate=? WHERE id IN ($placeholders)");
		$stm->execute(array_merge(array($pullstatus['pulltime']),$_POST['undeleteli']));
	}
	if (count($_POST['deleteli'])>0) {
		$placeholders = Sanitize::generateQueryPlaceholders($_POST['deleteli']);
		$stm = $DBH->prepare("UPDATE imas_library_items SET deleted=1,lastmoddate=? WHERE id IN ($placeholders)");
		$stm->execute(array_merge(array($pullstatus['pulltime']),$_POST['deleteli']));
	}
	if (count($_POST['unjunkli'])>0) {
		$placeholders = Sanitize::generateQueryPlaceholders($_POST['unjunkli']);
		$stm = $DBH->prepare("UPDATE imas_library_items SET junkflag=0,lastmoddate=? WHERE id IN ($placeholders)");
		$stm->execute(array_merge(array($pullstatus['pulltime']),$_POST['unjunkli']));
	}
	if (count($_POST['junkli'])>0) {
		$placeholders = Sanitize::generateQueryPlaceholders($_POST['junkli']);
		$stm = $DBH->prepare("UPDATE imas_library_items SET junkflag=1,lastmoddate=? WHERE id IN ($placeholders)");
		$stm->execute(array_merge(array($pullstatus['pulltime']),$_POST['junkli']));
	}

	if (isset($_POST['replacebys'])) {
		$stm = $DBH->prepare("UPDATE imas_questionset SET replaceby=:replaceby WHERE id=:id");
		$query = 'UPDATE imas_questions LEFT JOIN imas_assessment_sessions ON imas_questions.assessmentid = imas_assessment_sessions.assessmentid ';
		$query .= "SET imas_questions.questionsetid=:replaceby WHERE imas_assessment_sessions.id IS NULL AND imas_questions.questionsetid=:questionsetid";
		$upd_assess_stm = $DBH->prepare($query);
		foreach ($_POST['replacebys'] as $rbinfo) {
			//loop over addli and add localqid:locallibid
			$rbparts = explode(':', $rbinfo);                                               
			$stm->execute(array(':id'=>$rbparts[0], ':replaceby'=>$rbparts[1]));
			$upd_assess_stm->execute(array(':replaceby'=>$rbparts[1], ':questionsetid'=>$rbparts[0]));
		}
	}

	//now, resolve any unassigned issues
	//first, try to undelete the unassigned library item for any question with no undeleted library items
	$query = "UPDATE imas_library_items AS ili JOIN (SELECT qsetid FROM imas_library_items GROUP BY qsetid HAVING min(deleted)=1) AS tofix ON ili.qsetid=tofix.qsetid ";
	$query .= "JOIN imas_questionset AS iq ON ili.qsetid=iq.id ";
	$query .= "SET ili.deleted=0 WHERE ili.libid=0 AND iq.deleted=0";
	$stm = $DBH->query($query);
	
	//if any still have no undeleted library items, then they must not have an unassigned entry to undelete, so add it
	$query = "INSERT INTO imas_library_items (libid,qsetid,ownerid,junkflag,deleted,lastmoddate) ";
	$query .= "(SELECT 0,ili.qsetid,iq.ownerid,0,0,iq.lastmoddate FROM imas_library_items AS ili JOIN imas_questionset AS iq ON iq.id=ili.qsetid WHERE iq.deleted=0 GROUP BY ili.qsetid HAVING min(ili.deleted)=1)";
	$stm = $DBH->query($query);
	
	//if there are any questions with NO library items, add an unassigned one
	$query = "INSERT INTO imas_library_items (libid,qsetid,ownerid,junkflag,deleted,lastmoddate) ";
	$query .= "(SELECT 0,iq.id,iq.ownerid,0,iq.deleted,iq.lastmoddate FROM imas_questionset AS iq LEFT JOIN imas_library_items AS ili ON iq.id=ili.qsetid WHERE ili.id IS NULL)";
	$stm = $DBH->query($query);
	
	//make unassigned deleted if there's also an undeleted other library
	$query = "UPDATE imas_library_items AS A JOIN imas_library_items AS B ON A.qsetid=B.qsetid AND A.deleted=0 AND B.deleted=0 ";
	$query .= "SET A.deleted=1 WHERE A.libid=0 AND B.libid>0";
	$stm = $DBH->query($query);
	
	//all done.  Record we're done
	$stm = $DBH->prepare("UPDATE imas_federation_pulls SET step=99,record=:record WHERE id=:id");
	$stm->execute(array(':record'=>json_encode($record), ':id'=>$pullstatus['id']));

	$done = true;
	$autocontinue = false;
}

if ($autocontinue) {
	header('Location: ' . $GLOBALS['basesiteurl'] . "/admin/federationpull.php?peer=".Sanitize::encodeUrlParam($peer));
	exit;
} else if ($done) {
	require("../header.php");
	print_header();
	echo '<p>Done!</p>';
	echo '<p><a href="admin2.php">Back to Admin Page</a></p>';
	require("../footer.php");
} else {
	echo '</form>';
	require("../footer.php");
}
?>
