<?php
//XML templates for Blackboard native cartridge
//(c) 2018 IMathAS

$bbhandlers = array(
	'lti' => array('resource/x-bb-blti-link', 'REGULAR', 'resource/x-bb-document'),
	'text' => array('resource/x-bb-document', 'REGULAR', 'resource/x-bb-document'),
	'page' => array('resource/x-bb-blankpage', 'REGULAR', 'resource/x-bb-document'),
	'file' => array('resource/x-bb-file', 'REGULAR', 'resource/x-bb-document', 'S'),
	'link' => array('resource/x-bb-externallink', 'URL', 'resource/x-bb-document'),
	'forumitem' => array('resource/x-bb-forumlink', 'LINK', 'resource/x-bb-document'),
	'forumlink' => array('', '', 'resource/x-bb-link'),
	'forum' => array('', '', 'resource/x-bb-discussionboard'),
	'folder' => array('', '', 'resource/x-bb-document'),
	'gb' => array('','','course/x-bb-gradebook'),
	'toc' => array('', '', 'course/x-bb-coursetoc'),
	'conf' => array('', '', 'resource/x-bb-conference')
);

$bbtemplates = array(
	
'types' =>  array(
	'main' => "course/x-bb-coursetoc",
	'forum' => "resource/x-bb-discussionboard",
	'doc' => "resource/x-bb-document",
	'gb' => "course/x-bb-gradebook",
	'courselink' => "resource/x-bb-link"
),


'imsmanifest' =>  '<?xml version="1.0" encoding="UTF-8"?>
<manifest xmlns:bb="http://www.blackboard.com/content-packaging/" identifier="man00001">
<organizations default="toc00001">
    <organization identifier="toc00001">
    	<item identifier="itm00001" identifierref="res00001">
            <title>{{coursename}}</title>
            <item identifier="itm00002" identifierref="res00002">
                <title>--TOP--</title>
                {{items}}
            </item>
        </item>
    </organization>
</organizations>
<resources>
    {{resources}}
</resources>
</manifest>',

//main top-level folder
'toc' =>  '<?xml version="1.0" encoding="UTF-8"?>
<COURSETOC id="{{id}}">
	<LABEL value="{{label}}"/>
	<URL value=""/>
	<TARGETTYPE value="CONTENT"/>
	<INTERNALHANDLE value=""/>
	<FLAGS>
		<LAUNCHINNEWWINDOW value="true"/>
		<ISENABLED value="true"/>
		<ISENTYRPOINT value="true"/>
		<ALLOWOBSERVERS value="false"/>
		<ALLOWGUESTS value="false"/>
	</FLAGS>
</COURSETOC>',

//.dat to go with folder <item>
//first level has {{title}} of: --TOP--
//  and {{parentid}} of: {unset id}
//start and end can be empty strings
// 2018-04-06 21:17:06 EDT   "Y-m-d H:i:s T"
'toctop' =>  '<?xml version="1.0" encoding="UTF-8"?>
<CONTENT id="{{id}}">
	<TITLE value="{{title}}"/>
	<TITLECOLOR value="#000000"/>
	<BODY>
		<TEXT/>
		<TYPE value="H"/>
	</BODY>
	<DATES>
		<CREATED value="{{created}}"/>
		<UPDATED value="{{created}}"/>
		<START value="{{start}}"/>
		<END value="{{end}}"/>
	</DATES>
	<FLAGS>
		<ISAVAILABLE value="true"/>
		<ISFROMCARTRIDGE value="false"/>
		<ISFOLDER value="true"/>
		<ISDESCRIBED value="false"/>
		<ISTRACKED value="false"/>
		<ISLESSON value="false"/>
		<ISSEQUENTIAL value="false"/>
		<ALLOWGUESTS value="true"/>
		<ALLOWOBSERVERS value="true"/>
		<LAUNCHINNEWWINDOW value="false"/>
		<ISREVIEWABLE value="false"/>
		<ISGROUPCONTENT value="false"/>
		<ISSAMPLECONTENT value="false"/>
	</FLAGS>
	<CONTENTHANDLER value="resource/x-bb-folder"/>
	<RENDERTYPE value="REGULAR"/>
	<URL value=""/>
	<VIEWMODE value="TEXT_ICON_ONLY"/>
	<OFFLINENAME value=""/>
	<OFFLINEPATH value=""/>
	<LINKREF value=""/>
	<PARENTID value="{{parentid}}"/>
	<VERSION value="3"/>
	<EXTENDEDDATA/>
	<FILES/>
</CONTENT>',

'basicitem' =>  '<?xml version="1.0" encoding="UTF-8"?>
<CONTENT id="{{id}}">
	<TITLE value="{{title}}"/>
	<TITLECOLOR value="#000000"/>
	<BODY>
		<TEXT>{{summary}}</TEXT>
		<TYPE value="{{bodytype}}"/>
	</BODY>
	<DATES>
		<CREATED value="{{created}}"/>
		<UPDATED value="{{created}}"/>
		<START value="{{start}}"/>
		<END value="{{end}}"/>
	</DATES>
	<FLAGS>
		<ISAVAILABLE value="{{avail}}"/>
		<ISFROMCARTRIDGE value="false"/>
		<ISFOLDER value="false"/>
		<ISDESCRIBED value="false"/>
		<ISTRACKED value="false"/>
		<ISLESSON value="false"/>
		<ISSEQUENTIAL value="false"/>
		<ALLOWGUESTS value="true"/>
		<ALLOWOBSERVERS value="true"/>
		<LAUNCHINNEWWINDOW value="{{newwindow}}"/>
		<ISREVIEWABLE value="false"/>
		<ISGROUPCONTENT value="false"/>
		<ISSAMPLECONTENT value="false"/>
	</FLAGS>
	<CONTENTHANDLER value="{{handler}}"/>
	<RENDERTYPE value="{{rendertype}}"/>
	<URL value="{{launchurl}}"/>
	<VIEWMODE value="TEXT_ICON_ONLY"/>
	<OFFLINENAME value=""/>
	<OFFLINEPATH value=""/>
	<LINKREF value=""/>
	<PARENTID value="{{parentid}}"/>
	<VERSION value="3"/>
	<EXTENDEDDATA>{{extendeddata}}</EXTENDEDDATA>
	<FILES>{{files}}</FILES>
</CONTENT>',

'file' =>  '<FILE id="{{fileitemid}}">
	<NAME>/xid-{{fileid}}</NAME>
	<SIZE value="{{size}}"/>
	<FILEACTION value="{{fileaction}}"/>
	<LINKNAME value="{{filename}}"/>
	<STORAGETYPE value="CS"/>
	<DATES>
		<CREATED value="{{created}}"/>
		<UPDATED value="{{created}}"/>
	</DATES>
	<REGISTRY/>
</FILE>',

//each file goes in csfiles/home_dir/filename
//with name:  basename__xid-{{fileid}}.extension
//with a corresponding file with .xml added ($filexml template)
//imsmanifest needs a resource tag like:
// <resource bb:file="res00005.dat" bb:title="CSResourceLinks" identifier="res00005" type="course/x-bb-csresourcelinks" xml:base="res00005"/>
//that .dat file uses template $crs below, where {{linkset}} is a set of $crslink templates
//fileid needs to match between the filename, .xml, and crslink
//fileitemid needs to match with <FILE> id from containing item

//CSResourceLink
'crs' =>  '<?xml version="1.0" encoding="UTF-8"?>
<cms_resource_link_list>
	{{linkset}}
</cms_resource_link_list>',

//{{fileitemid}} is the fileitemid used in the $file template above 
'crslink' =>  '
	<cms_resource_link>
		<courseId data-type="blackboard.data.course.Course">{{courseid}}</courseId>
		<parentId parent_data_type="contentfile">{{fileitemid}}</parentId>
		<resourceId>
			<![CDATA[{{fileid}}]]>
		</resourceId>
		<storageType>
			<![CDATA[PUBLIC]]>
		</storageType>
		<id data-type="blackboard.cms.platform.contentsystem.data.CSResourceLink">{{id}}</id>
	</cms_resource_link>
',


'filexml' =>  '<?xml version="1.0" encoding="UTF-8"?>
<lom
	xmlns="http://www.imsglobal.org/xsd/imsmd_rootv1p2p1"
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.imsglobal.org/xsd/imsmd_rootv1p2p1 imsmd_rootv1p2p1.xsd">
	<relation>
		<resource>
			<identifier>{{fileid}}#/courses/coursename/{{filename}}</identifier>
		</resource>
	</relation>
</lom>',

//forum requires a regular item with the forum/link handler
//itemdat is res00015 or whatever the name of the dat file for the <item>
//forumdat is res0003 or whatever the name of the dat file for the forum is
'forumlink' =>  '<?xml version="1.0" encoding="UTF-8"?>
<LINK id="{{id}}">
	<TITLE value=""/>
	<FLAGS>
		<ISAVAILABLE value="{{avail}}"/>
	</FLAGS>
	<REFERRER id="{{itemdat}}" type="CONTENT"/>
	<REFERREDTO id="{{forumdat}}" type="FORUM"/>
</LINK>',

'forum' =>  '<?xml version="1.0" encoding="UTF-8"?>
<FORUM id="{{id}}">
	<CONFERENCEID value="{{conferenceid}}" />
	<TITLE value="{{title}}" />
	<DESCRIPTION>
		<TEXT>{{summary}}</TEXT>
		<FLAGS>
			<ISHTML value="true" />
			<ISNEWLINELITERAL value="false" />
		</FLAGS>
	</DESCRIPTION>
	<DATES>
		<CREATED value="{{created}}" />
		<UPDATED value="{{created}}" />
	</DATES>
	<POSTFIRST value="NO_POST_FIRST" />
	<FLAGS>
		<ALLOWDELETETREE value="false" />
		<ALLOWANONYMOUSPOSTINGS value="false" />
		<ALLOWAUTHOREDIT value="false" />
		<ALLOWAUTHORREMOVE value="false" />
		<ALLOWFILEATTACHMENTS value="true" />
		<ALLOWFORUMGRADING value="true" />
		<ALLOWMEMBERRATE value="false" />
		<ALLOWMESSAGETAGGING value="false" />
		<ALLOWNEWTHREADS value="true" />
		<ALLOWQUOTE value="true" />
		<ALLOWSUBSCRIBEFORUM value="true" />
		<ALLOWSUBSCRIBETHREAD value="false" />
		<ALLOWTHREADALIGNMENTS value="false" />
		<ALLOWTHREADGRADING value="false" />
		<ENFORCEMODERATION value="false" />
		<INCLUDEMESSAGESUBSCRIPTION value="false" />
		<ISAVAILABLE value="{{avail}}" />
	</FLAGS>
	<MESSAGETHREADS />
</FORUM>',

'conf' =>  '<?xml version="1.0" encoding="UTF-8"?>
<CONFERENCES>
	<CONFERENCE id="{{id}}">
		<TITLE value="{{coursename}}"/>
		<DESCRIPTION>
			<TEXT/>
			<TYPE value="S"/>
		</DESCRIPTION>
		<AREA value=""/>
		<ICON value=""/>
		<GROUPID value=""/>
		<FLAGS>
			<ISAVAILABLE value="true"/>
		</FLAGS>
		<DATES>
			<CREATED value="{{created}}"/>
			<UPDATED value="{{created}}"/>
		</DATES>
	</CONFERENCE>
</CONFERENCES>',


//outcomedef
'outcomedef' =>  '
		<OUTCOMEDEFINITION id="{{defid}}">
			<CATEGORYID value="" />
			<SCALEID value="scale1" />
			<SECONDARY_SCALEID value="" />
			<CONTENTID value="{{resid}}" />
			<GRADING_PERIODID value="" />
			<ASIDATAID value="" />
			<DATES>
				<CREATED value="" />
				<UPDATED value="" />
				<DUE value="{{duedate}}" />
			</DATES>
			<TITLE value="{{title}}" />
			<DISPLAY_TITLE value="" />
			<POSITION value="0" />
			<VERSION value="594296179" />
			<DELETED value="false" />
			<EXTERNALREF value="" />
			<HANDLERURL value="" />
			<ANALYSISURL value="" />
			<WEIGHT value="0" />
			<POINTSPOSSIBLE value="{{ptsposs}}" />
			<ISVISIBLE value="true" />
			<VISIBLE_BOOK value="true" />
			<VISIBLE_ALL_TERMS value="false" />
			<SHOW_STATS_TO_STUDENT value="false" />
			<HIDEATTEMPT value="false" />
			<AGGREGATIONMODEL value="Last" />
			<SCORE_PROVIDER_HANDLE value="resource/x-bb-blti-link" />
			<SINGLE_ATTEMPT value="false" />
			<CALCULATIONTYPE value="NON_CALCULATED" />
			<ISCALCULATED value="false" />
			<ISSCORABLE value="true" />
			<ISUSERCREATED value="false" />
			<MULTIPLEATTEMPTS value="0" />
			<ACTIVITY_COUNT_COL_DEFS />
		</OUTCOMEDEFINITION>',

'gb' =>  '<?xml version="1.0" encoding="UTF-8"?>
<GRADEBOOK>
	<CATEGORIES />
	<SCALES>
		<SCALE id="scale1">
			<TITLE value="Score.title" />
			<DESCRIPTION />
			<ISUSERDEFINED value="false" />
			<ISTABULARSCALE value="false" />
			<ISPERCENTAGE value="false" />
			<ISNUMERIC value="true" />
			<TYPE value="SCORE" />
			<VERSION value="594483442" />
		</SCALE>
	</SCALES>
	<GRADING_PERIODS />
	<OUTCOMEDEFINITIONS>
	{{outcomedefs}}
	</OUTCOMEDEFINITIONS>
	<FORMULAE />
	<CUSTOM_VIEWS />
	<SETTINGS />
	<STUDENT_INFO_LAYOUTS />
</GRADEBOOK>',

'bbinfo' =>  '#Bb PackageInfo Property File
Export created by '.$installname

);

