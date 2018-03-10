<?php

$db_fields = array();
$db_fields['gbcats'] = 'name,scale,scaletype,chop,dropn,weight,hidden,calctype';
$db_fields['gbscheme'] = 'useweights,orderby,defaultcat,defgbmode,usersort,stugbmode,colorize';
$db_fields['offline'] = 'name,points,showdate,gbcategory,cntingb,tutoredit';
$db_fields['calitems'] = 'date,title,tag';

//set grouplimit=array() on import
$db_fields['block'] = 'name,avail,startdate,enddate,SH,colors,public,fixedheight';

//look for non-changable fields in $CFG and don't write those on import
$db_fields['course'] = 'enrollkey,hideicons,allowunenroll,copyrights,msgset,available,theme,latepasshrs,picicons,ltisecret,showlatepass,toolset,deflatepass,deftime';

$db_fields['inlinetext'] = 'title,text,startdate,enddate,fileorder,avail,oncal,caltag,isplaylist';
$db_fields['linkedtext'] = 'title,summary,text,startdate,enddate,avail,oncal,caltag,target,points';
$db_fields['forum'] = 'name,description,startdate,enddate,settings,defdisplay,replyby,postby,points,gbcategory,avail,sortby,cntingb,caltag,forumtype,taglist,tutoredit,postinstr,replyinstr,allowlate';
$db_fields['forum_posts'] = 'forumid,subject,message,posttype,isanon,replyby,files,tag';
$db_fields['wiki'] = 'name,description,startdate,enddate,editbydate,settings,avail';
$db_fields['questions'] = 'questionsetid,points,attempts,penalty,category,regen,showans,showhints,extracredit,fixedseeds';
$db_fields['assessment'] = 'name,summary,intro,startdate,enddate,reviewdate,timelimit,displaymethod,defpoints,defattempts,deffeedback,defpenalty,itemorder,shuffle,gbcategory,password,cntingb,minscore,showcat,showhints,isgroup,reqscoreaid,reqscore,reqscoretype,noprint,avail,groupmax,allowlate,exceptionpenalty,endmsg,tutoredit,eqnhelper,caltag,showtips,calrtag,deffeedbacktext,posttoforum,msgtoinstr,istutorial,viddata,ptsposs';
$db_fields['drill'] = 'itemdescr,itemids,scoretype,showtype,n,showtostu,name,summary,startdate,enddate,avail,caltag';
//added qimgs field if hasimg==1
//includecodefrom(EID___) uses export ID
$db_fields['questionset'] = 'uniqueid,adddate,lastmoddate,ownerid,author,userights,description,qtype,control,qcontrol,qtext,answer,hasimg,extref,deleted,broken,replaceby,solution,solutionopts,license,ancestorauthors,otherattribution';

$db_fields['html'] = array(
	'inlinetext'=>array('text'),
	'linkedtext'=>array('summary','text'),
	'forum'=>array('description','replyinstr','postinstr'),
	'wiki'=>array('description'),
	'assessment'=>array('summary'), //need to handle intro specially
	'drill'=>array('summary'),
	'forum_posts'=>array('message')
);
