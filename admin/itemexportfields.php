<?php

$db_fields = array();
$db_fields['gbcats'] = 'name,scale,scaletype,chop,dropn,weight,hidden,calctype';
$db_fields['gbscheme'] = 'useweights,orderby,defaultcat,defgbmode,usersort,stugbmode,colorize';
$db_fields['offline'] = 'name,points,showdate,gbcategory,cntingb,tutoredit';
$db_fields['calitems'] = 'date,title,tag';

//set grouplimit=array() on import
$db_fields['block'] = 'name,avail,startdate,enddate,SH,colors,public,fixedheight';

//look for non-changable fields in $CFG and don't write those on import
$db_fields['course'] = 'enrollkey,allowunenroll,copyrights,msgset,available,theme,latepasshrs,ltisecret,showlatepass,toolset,deflatepass,deftime,UIver';

$db_fields['inlinetext'] = 'title,text,startdate,enddate,fileorder,avail,oncal,caltag,isplaylist';
$db_fields['linkedtext'] = 'title,summary,text,startdate,enddate,avail,oncal,caltag,target,points';
$db_fields['forum'] = 'name,description,startdate,enddate,settings,defdisplay,replyby,postby,points,gbcategory,avail,sortby,cntingb,caltag,forumtype,taglist,tutoredit,postinstr,replyinstr,allowlate';
$db_fields['forum_posts'] = 'forumid,subject,message,posttype,isanon,replyby,files,tag';
$db_fields['wiki'] = 'name,description,startdate,enddate,editbydate,settings,avail';
$db_fields['questions'] = 'questionsetid,points,attempts,penalty,category,regen,showans,showhints,extracredit,fixedseeds';
$db_fields['assessment'] = 'name,summary,intro,startdate,enddate,reviewdate,timelimit,displaymethod,defpoints,defattempts,deffeedback,defpenalty,itemorder,shuffle,gbcategory,password,cntingb,minscore,showcat,showhints,isgroup,reqscorejson,reqscoretype,noprint,avail,groupmax,allowlate,exceptionpenalty,exceptionpenaltyinterval,earlybonus,endmsg,tutoredit,eqnhelper,caltag,showtips,calrtag,deffeedbacktext,posttoforum,msgtoinstr,istutorial,viddata,ptsposs,LPcutoff,extrefs,submitby,showscores,showans,viewingb,scoresingb,ansingb,defregens,defregenpenalty,ver,keepscore,retakewait,overtime_grace,overtime_penalty,showwork,workcutoff';
$db_fields['drill'] = 'itemdescr,itemids,scoretype,showtype,n,showtostu,name,summary,startdate,enddate,avail,caltag';
//added qimgs field if hasimg==1
//includecodefrom(EID___) uses export ID
$db_fields['questionset'] = 'uniqueid,adddate,lastmoddate,ownerid,author,userights,description,qtype,control,qcontrol,qtext,answer,hasimg,extref,deleted,broken,replaceby,solution,solutionopts,license,ancestorauthors,otherattribution,a11yalttype,a11yalt';

//fields that must be present in an import file (in at least the first record of
//each type) or the import will be rejected. Everything else in $db_fields is optional
//and falls back to the DB default if missing.
//(questionset uniqueid is not listed since it is generated on import if absent)
$db_required = array(
	'gbcats'=>array('name'),
	'offline'=>array('name'),
	'calitems'=>array('date','title'),
	'inlinetext'=>array('title'),
	'linkedtext'=>array('title','text'),
	'forum'=>array('name'),
	'forum_posts'=>array('subject','message'),
	'wiki'=>array('name'),
	'questions'=>array('questionsetid'),
	'assessment'=>array('name','itemorder'),
	'drill'=>array('name','itemids'),
	'questionset'=>array('description','qtype','control','qtext')
);

//maps item type names in the export file to $db_required keys
$db_itemtypekeys = array(
	'InlineText'=>'inlinetext', 'LinkedText'=>'linkedtext', 'Forum'=>'forum',
	'Wiki'=>'wiki', 'Assessment'=>'assessment', 'Drill'=>'drill'
);

/**
 * Check parsed import data for required fields.
 * Only checks the first record of each type, consistent with how the importer
 * decides which columns to use.
 * @param array $data  parsed JSON import
 * @return array  list of error strings (empty if all required fields present)
 */
function checkImportRequiredFields($data) {
	global $db_required, $db_itemtypekeys;
	$missing = array();
	$check = function ($key, $record) use (&$missing, $db_required) {
		if (!is_array($record)) {
			$m = $db_required[$key];
		} else {
			$m = array_diff($db_required[$key], array_keys($record));
		}
		if (count($m) > 0) {
			$missing[$key] = array_merge($missing[$key] ?? array(), $m);
		}
	};
	//top-level sections: db_required key => key in import data
	$sections = array('gbcats'=>'gbcats', 'offline'=>'offline', 'calitems'=>'calitems',
		'forum_posts'=>'stickyposts', 'questions'=>'questions', 'questionset'=>'questionset');
	foreach ($sections as $key => $datakey) {
		if (isset($data[$datakey]) && is_array($data[$datakey]) && count($data[$datakey]) > 0) {
			$check($key, reset($data[$datakey]));
		}
	}
	//items: check first item of each type
	$seen = array();
	$badtypes = array();
	foreach (($data['items'] ?? array()) as $item) {
		$type = $item['type'] ?? '';
		if (!is_string($type) || ($type !== 'Calendar' && !isset($db_itemtypekeys[$type]))) {
			$badtypes[] = is_string($type) ? $type : gettype($type);
			continue;
		}
		if ($type === 'Calendar' || isset($seen[$type])) {
			continue;
		}
		$seen[$type] = true;
		$check($db_itemtypekeys[$type], $item['data'] ?? null);
	}
	$errors = array();
	if (count($badtypes) > 0) {
		$errors[] = sprintf(_('Invalid item types: %s'), implode(', ', array_unique($badtypes)));
	}
	foreach ($missing as $key => $fields) {
		$errors[] = sprintf(_('Missing required fields for %s: %s'), $key, implode(', ', array_unique($fields)));
	}
	return $errors;
}

$db_fields['html'] = array(
	'inlinetext'=>array('text'),
	'linkedtext'=>array('summary','text'),
	'forum'=>array('description','replyinstr','postinstr'),
	'wiki'=>array('description'),
	'assessment'=>array('summary'), //need to handle intro specially
	'drill'=>array('summary'),
	'forum_posts'=>array('message')
);
