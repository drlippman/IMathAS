<?php
//IMathAS: Course browser page
//(c) 2017 David Lippman

//Use of course browser requires setting $CFG['coursebrowser'] in your config.php
//to a javascript file in /javascript/.  See coursebrowserprops.js.dist for an 
//example.  In that file, you can specify whatever characteristics you want
//provided when promoting a course.
//For a dropdown, specify "options".  
//For a multi-select, specify "options" and add "multi": true
//For strings, put "type": "string"
//For textarea, put "type": "textarea"
//For a field you'll fill in (no entry box), put "fixed": true
//use "sortby" with consecutive values to specify order in characteristics
//should be used to sort values. Use negative for a descending sort

require("../init.php");
if (!isset($CFG['coursebrowser'])) {
	echo "Course Browser is not enabled on this site";
	exit;
}
$browserprops = json_decode(file_get_contents(__DIR__.'/../javascript/'.$CFG['coursebrowser'], false, null, 25), true);

$action = "redirect";
if (isset($_GET['embedded'])) {
	$action = "framecallback";
	$nologo = true;
	$flexwidth = true;
}


/*** Utility functions ***/
function getCourseBrowserJSON() {
  global $DBH, $browserprops, $groupid;
  $query = "SELECT ic.id,ic.name,ic.jsondata,iu.FirstName,iu.LastName,ig.name AS groupname,ic.istemplate,iu.groupid ";
  $query .= "FROM imas_courses AS ic JOIN imas_users AS iu ON ic.ownerid=iu.id JOIN imas_groups AS ig ON iu.groupid=ig.id ";
  $query .= "WHERE ((ic.istemplate&17)>0 OR ((ic.istemplate&2)>0 AND iu.groupid=?))";
  $stm = $DBH->prepare($query);
  $stm->execute(array($groupid));
  $courseinfo = array();
  while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
    $jsondata = json_decode($row['jsondata'], true);
    if (!isset($jsondata['browser'])) {
      $jsondata['browser'] = array();
    }
    
    $jsondata['browser']['id'] = $row['id'];
    if (empty($jsondata['browser']['owner'])) {
    	    $jsondata['browser']['owner'] = $row['FirstName'].' '. $row['LastName']. ' ('.$row['groupname'].')';
    }
     if (!isset($jsondata['browser']['name'])) {
    	    $jsondata['browser']['name'] = $row['name'];
    }
   
    if (($row['istemplate']&2)==2 && $row['groupid']==$groupid) { //group template for user's group
    	$jsondata['browser']['coursetype'] = 0;	    
    } else if (($row['istemplate']&1)==1) { //global template
    	$jsondata['browser']['coursetype'] = 1;	        
    } else {
    	$jsondata['browser']['coursetype'] = 2;	        
    }
    $courseinfo[] = $jsondata['browser'];
  }
  //Sort courseinfo
  $sortby = array();
  $sortby[0] = array(
  	  'prop'=>'coursetype',
  	  'asc'=>true);
  foreach($browserprops as $propname=>$props) {
  	  if (isset($props['sortby'])) {
  	  	  $loc = abs($props['sortby']);
  	  	  $sortby[$loc] = array();
  	  	  $sortby[$loc]['prop'] = $propname;
  	  	  $sortby[$loc]['asc'] = ($props['sortby']>0);
  	  	  if (isset($props['options'])) {
  	  	  	  $i = 0;
  	  	  	  $orderref = array();
  	  	  	  foreach ($props['options'] as $k=>$v) {
  	  	  	  	  $orderref[$k] = $i;
  	  	  	  	  $i++;
  	  	  	  }
  	  	  	  $sortby[$loc]['ref'] = $orderref;
  	  	  }
  	  }
  }
  ksort($sortby);
  usort($courseinfo, function($a,$b) use ($sortby) {
  	foreach ($sortby as $sortinf) {
  		if ($sortinf['prop']=='id' && $a['coursetype']<2) {
  			$sortinf['prop'] = 'name';
  			$sortinf['asc'] = 1;
  		}
  		if (!isset($a[$sortinf['prop']]) || !isset($b[$sortinf['prop']])) {
  			continue;
  		}
  		$aval = $a[$sortinf['prop']];
  		if (is_array($aval)) { $aval = $aval[0];}
  		$bval = $b[$sortinf['prop']];
  		if (is_array($bval)) { $bval = $bval[0];}
  		if (isset($sortinf['ref'])) {
  			if ($sortinf['ref'][$aval] != $sortinf['ref'][$bval]) {
  				return (($sortinf['ref'][$aval] < $sortinf['ref'][$bval])? -1 : 1)*($sortinf['asc']?1:-1);
  			}
  		} else {
  			if ($aval != $bval) {
  				return (($aval < $bval)? -1 : 1)*($sortinf['asc']?1:-1);	
  			}
  		}
  	}	 
  	return 0;
  });
  
  return json_encode($courseinfo);
}

/*** Start output ***/
$placeinhead = '<script type="text/javascript">';
$placeinhead .= 'var courses = '.getCourseBrowserJSON().';';
$placeinhead .= 'var courseBrowserAction = "'.Sanitize::simpleString($action).'";';
$placeinhead .= '</script>';
$placeinhead .= '<script src="https://cdn.jsdelivr.net/npm/vue@2.5.6/dist/vue.min.js"></script>
<script src="'.$imasroot.'/javascript/'.$CFG['coursebrowser'].'"></script>
<link rel="stylesheet" href="coursebrowser.css?v=121817" type="text/css" />';

$pagetitle = _('Course Browser');
require("../header.php");

if (!isset($_GET['embedded'])) {
  $curBreadcrumb = $breadcrumbbase . _('Course Browser');
  echo '<div class=breadcrumb>'.$curBreadcrumb.'</div>';
	echo '<div id="headercoursebrowser" class="pagetitle"><h1>'.$pagetitle.'</h1></div>';
}
?>
<div id="app" v-cloak>
<div <?php if (isset($_GET['embedded'])) {echo 'id="fixedfilters"';}?>>Filter results: 
<span v-for="propname in propsToFilter" class="dropdown-wrap">
	<button @click="showFilter = (showFilter==propname)?'':propname">
		{{ courseBrowserProps[propname].name }} {{ catprops[propname].length > 0 ? '('+catprops[propname].length+')': '' }}
		<span class="arrow-down" :class="{rotated: showFilter==propname}"></span>	
	</button>
	<transition name="fade" @enter="adjustpos">
		<ul v-if="showFilter == propname" class="filterwrap">
			<li v-if="courseBrowserProps[propname].hasall">
				<span>Show courses that contain <i>all</i> of:</span>
			</li>
			<li v-if="!courseBrowserProps[propname].hasall">
				<span>Show courses that contain <i>any</i> of:</span>
			</li>
			<li v-for="(longname,propval) in courseBrowserProps[propname].options">
				<span v-if="propval.match(/^group/)" class="optgrplabel"><em>{{ longname }}</em></span>
				<label v-else><input type="checkbox" :value="propname+'.'+propval" v-model="selectedItems">
				{{ longname }}</label>
			</li>
		</ul>
	</transition>
</span>
<a href="#" @click.prevent="selectedItems = []" v-if="selectedItems.length>0">Clear Filters</a>
</div>
<div style="position: relative" id="card-deck-wrap">
<transition-group name="fade" tag="div" class="card-deck">
<div v-if="filteredCourses.length==0" key="none">No matches found</div>
<div v-for="(course,index) in filteredCourses" :key="course.id" class="card">
  <div class="card-body">
  	<div class="card-header" :class="'coursetype'+course.coursetype">
  		<span class="course-type-marker">{{ courseTypes[course.coursetype] }}</span>
  		<b>{{ course.name }}</b>
  	</div>
	<div class="card-main">
		<table class="proplist"><tbody>
		<tr v-for="(propval,propname) in courseOut(course)">
			<th>{{ courseBrowserProps[propname].name }}</th>
			<td v-if="!Array.isArray(propval)"> {{ propval }} </td>
			<td v-if="Array.isArray(propval)">
				<ul class="nomark">
					<li v-for="subprop in propval"> 
						{{ courseBrowserProps[propname].options[subprop] }} 
					</li>
				</ul>
			</td>
		</tr>
	
		</tbody></table>
		<p v-for="(propval,propname) in course" 
		   v-if="courseBrowserProps[propname] && courseBrowserProps[propname].type && courseBrowserProps[propname].type=='textarea'" 
		   class="pre-line"
		>{{ propval }}</p>
	</div>
	<div class="card-footer">
		<button @click="previewCourse(course.id)">Preview Course</button>
		<button @click="copyCourse(course)">Copy This Course</button>
	</div>
  </div>
</div>
</transition-group>
</div>

</div>
<script type="text/javascript">
new Vue({
	el: '#app',
	data: {
		selectedItems: [],
		courseBrowserProps: courseBrowserProps,
		showFilters: false,
		showFilter: '',
		filterLeft: 0,
		courseTypes: courseBrowserProps.meta.courseTypes,
	},
	methods: {
		clickaway: function(event) {
			var dropdowns = document.getElementsByClassName("dropdown-wrap");
			var isClickInside = false;
			for (var i=0;i<dropdowns.length;i++) {
				if (dropdowns[i].contains(event.target)) {
					isClickInside = true;
					break;
				}
			}
			if (!isClickInside) {
				this.showFilter = '';
			}
		},
		courseOut: function (course) {
			var courseout = {};
			for (propname in course) {
				if (this.courseBrowserProps[propname]) {
					if (this.courseBrowserProps[propname].options) {
						if (Array.isArray(course[propname])) {
							courseout[propname] = course[propname];
						} else if (course[propname]=='other') {
							courseout[propname] = course[propname+'other'];
						} else {
							courseout[propname] = this.courseBrowserProps[propname].options[course[propname]];
						}
					} else if (courseBrowserProps[propname].type && courseBrowserProps[propname].type=='string' && propname!='name') {
						courseout[propname] = course[propname];
					}
				}
			}
			return courseout;
		},
		previewCourse: function (id) {
			window.open("../course/course.php?cid="+id,"_blank");
		},
		copyCourse: function (course) {
			if (courseBrowserAction=="redirect") {
				var url = "forms.php?from=home&action=addcourse";
				url += "&tocopyid="+course.id;
				url += "&tocopyname="+encodeURIComponent(course.name);
				window.location.href = imasroot+"/admin/"+url;
			} else {
				window.parent.setCourse(course);
			}
		},
		adjustpos: function(tgt) {
			var rect = tgt.parentNode.getBoundingClientRect();
			var width = window.innerWidth || document.body.clientWidth;
			var height = window.innerHeight || document.body.clientHeight;
			tgt.style.maxHeight = (height - rect.bottom - 30) + "px";
			if (rect.left + tgt.offsetWidth > width) {
				if (rect.right < tgt.offsetWidth) {
					tgt.style.left = "auto";
					tgt.style.right = "auto";
				} else {
					tgt.style.left = "auto";
					tgt.style.right = "0px";
				}
			} else {
				tgt.style.right = "auto";
				tgt.style.left = "0px";
			}
		}
	},
	computed: {
		propsToFilter: function() {
			var props = [];
			for (prop in this.courseBrowserProps) {
				if (this.courseBrowserProps[prop]["options"]) {
					props.push(prop);
				}
			}
			return props;
		},
		catprops: function() {
			var catarr = {};
			for (prop in this.courseBrowserProps) {
				catarr[prop] = [];
			}
			var parts;
			for (i=0;i<this.selectedItems.length;i++) {
				parts = this.selectedItems[i].split('.');
				catarr[parts[0]].push(parts[1]);
			}
			return catarr;
		},
		filteredCourses: function() {
			var selectedCourses = [];
			
			var includeCourse = true;
			for (var i=0; i<courses.length; i++) {
				includeCourse = true;
				for (prop in this.courseBrowserProps) {
					if (this.catprops[prop].length==0) {
						//no filters selected, skip it
						continue;
					} else if (!courses[i][prop]) {
						//if prop selected, but course doesn't contain it
						includeCourse = false;
						break;
					} else if (this.courseBrowserProps[prop].hasall) {
						//only include if ALL selected filters are included
						if (!this.catprops[prop].every(function(v) {
							return courses[i][prop].indexOf(v) >= 0;	
						})) {
							includeCourse = false;
							break;
						}
					} else if (typeof courses[i][prop] == 'object') {
						//only include if ONE of the filter items is in course
						if (!this.catprops[prop].some(function(v) {
							return courses[i][prop].indexOf(v) >= 0;	
						})) {
							includeCourse = false;
							break;
						}
					} else {
						//only include if filter is in course
						if (this.catprops[prop].indexOf(courses[i][prop])==-1) {
							includeCourse = false;
							break;
						}
					}
				}
				if (includeCourse) {
					selectedCourses.push(courses[i]);
				}
			}
			return selectedCourses;
		}
	}, 
	created: function() {
		document.addEventListener('click', this.clickaway);
	},
	mounted: function() {
		$("#fixedfilters + #card-deck-wrap").css("margin-top", $("#fixedfilters").outerHeight() + 10);
	}
	
});
</script>
<?php
require("../footer.php");
