<?php
//Virtual Manipulatives
//Functions for displaying and scoring virtual manipulatives.
//
//Some of these can be used an exploration/calculation aid in a question without
//being scored.  Most have the ability to score the interaction with the manipulative.
//
//When being scored, use the String question type.  For most of these, you must
//manually implement the scoring, usually by defining a wrong $answer, then changing
//it to match the value from $stuanswers if the values are correct.
//A typical pattern would look like:
//
//loadlibrary("virtmanip")
//$listener = vmgetlistener($thisq)
//$stua = $stuanswers[$thisq]
//$vm = vmsetupnumberline($stua,$thisq)
//$showanswer = "Answers will vary"
//$answer = "wrong".$stua
//if ($stua != null) {
//  $scale,$val = vmnumberlinegetvals($stua)
//  $answer = $stua if ($val==$correctval)
//}
//
//Then $vm and $listener would get placed in the question text.
//To hide the answerbox, wrap it in something like this:
//&lt;div style="position:absolute;left:0;right:0;visibility:hidden"&gt;$answerbox&lt;/div&gt;
//
//Ver 1.0 by David Lippman and Bill Meacham, May 2014

global $allowedmacros;
array_push($allowedmacros,"vmsetup","vmgetlistener","vmgetparam","vmparamtoarray",
	"vmsetupchipmodel","vmchipmodelgetcount","vmsetupnumbertiles","vmnumbertilesgetcount",
	"vmsetupitemsort","vmitemsortgetcontainers","vmsetupnumberlineaddition",
	"vmnumberlineadditiongetvals","vmsetupnumberline","vmnumberlinegetvals",
	"vmsetupnumberlineinterval","vmnumberlineintervalgetvals","vmsetupfractionline",
	"vmgetfractionlinevals","vmsetupfractionmult","vmgetfractionmultvals",
	"vmsetupfractioncompare","vmgetfractioncompareval","vmdrawinchruler",
	"vmdrawcmruler","vmdrawclock");

function vmsetup($vmname, $vmparams, $width, $height, $qn, $part=null) {
	if ($part !== null) {$qn = 1000*($qn)+$part;} else {$qn--;}
	$vmparams['a11y_graph'] = Sanitize::onlyInt($_SESSION['userprefs']['graphdisp']);
	$vmparams['a11y_mouse'] = Sanitize::onlyInt($_SESSION['userprefs']['drawentry']);
	$vmparams['a11y_math'] = Sanitize::onlyInt($_SESSION['userprefs']['mathdisp']);
	$vmparams['qn'] = $qn;

	if (substr($vmname,0,4)!='http') {
			$vmname = Sanitize::simpleString($vmname);
			$vmurl = 'https://s3-us-west-2.amazonaws.com/oervm/'.$vmname.'/'.$vmname.'.html';
	} else {
		$vmurl = Sanitize::url($vmname);
	}
	$vmurl .= '?'.Sanitize::generateQueryStringFromMap($vmparams);
	$iframe = '<iframe src="'.$vmurl.'" ';
	$iframe .= 'width="'.Sanitize::encodeStringForDisplay($width).'" ';
	$iframe .= 'height="'.Sanitize::encodeStringForDisplay($height).'" ';
	$iframe .= 'frameborder=0></iframe>';
	return $iframe;
}

//vmgetlistener(qn,[part])
//Generates a listener to receive values from virtual manipulatives.
//this needs to be generated and included in the question text if the manipulative
//is to be scored
function vmgetlistener($qn,$part=null) {
	if ($part !== null) {$qn = 1000*($qn)+$part;} else {$qn--;}
	$out = '<script type="text/javascript">';
	$out .= '$(function() { $(window).on("message", function(e) {
		var data = e.originalEvent.data.split("::");
		if (data[0] == '.$qn.') {
			$("#qn'.$qn.'").val(data[1]).trigger("change");
		}});});';
	$out .= '</script>';
	return $out;
}

//vmgetparam(parameter string, parameter name)
//Extracts one parameter value from a parameter string, where
//parameter string is of the form "name1:value1,name2:value2,name3:value3"
//returns the value if found, or FALSE if not found
function vmgetparam($paramstr,$name) {
	$params = explode(',', $paramstr);
	foreach ($params as $param) {
		$p = strpos($param, ':');
		if ($p !== false && trim(substr($param,0,$p))==$name) {
			return trim(substr($param,$p+1));
		}
	}
	return false;
}

//vmparamtoarray(parameter string)
//Converts a parameter string of the form "name1:value1,name2:value2,name3:value3"
//into an associative array.  For example, the above would be converted into an
//array where $a["name1"] would be "value1"
function vmparamtoarray($paramstr) {
	$params = explode(',', $paramstr);
	$out = array();
	foreach ($params as $param) {
		$p = strpos($param, ':');
		if ($p !== false) {
			$out[trim(substr($param,0,$p))] = trim(substr($param,$p+1));
		}
	}
	return $out;
}

//vmsetupchipmodel(stuans,qn,[part])
//Set up a chip model manipulative
function vmsetupchipmodel($state,$qn,$part=null) {
	if ($part !== null) {$qn = 1000*($qn)+$part;} else {$qn--;}
	$initbase = "[]";
	$initobj = "[]";
	if ($state!="") {
		list($initbasestr,$initobjstr,$cont) = explode('|',$state);
		if ($initbasestr != '') {
			$initbase = '[['.str_replace(';','],[',$initbasestr).']]';
			$initbase = preg_replace('/\[([^\[\]]+?),/','[&quot;$1&quot;,',$initbase);
		}
		if ($initobjstr != '') {
			$initobj = '[['.str_replace(';','],[',$initobjstr).']]';
			$initobj = preg_replace('/\[([^\[\]]+?),/','[&quot;$1&quot;,',$initobj);
		}
	}
	$out = '<iframe src="https://s3-us-west-2.amazonaws.com/oervm/chipmodel/chipmodel.html?qn='.$qn.'&initbase='.$initbase.'&initobj='.$initobj.'" width="400" height="300" frameborder="0"></iframe>';
	return $out;
}

//vmchipmodelgetcount(stuans)
//return an array array(poscount,negcount) of the count of positive and
//negative chips in the drop region.
function vmchipmodelgetcount($state) {
	list($initbasestr,$initobjstr,$cont) = explode('|',$state);
	$pos = substr_count($cont,"pos");
	$neg = substr_count($cont,"neg");
	return array($pos,$neg);
}

//vmsetupnumbertiles(stuans,qn,[part])
//Set up a number tiles manipulative
function vmsetupnumbertiles($state,$qn,$part=null) {
	if ($part !== null) {$qn = 1000*($qn)+$part;} else {$qn--;}
	$initbase = "[]";
	$initobj = "[]";
	if ($state!="") {
		list($initbasestr,$initobjstr,$cont) = explode('|',$state);
		if ($initbasestr != '') {
			$initbase = '[['.str_replace(';','],[',$initbasestr).']]';
			$initbase = preg_replace('/\[([^\[\]]+?),/','[&quot;$1&quot;,',$initbase);
		}
		if ($initobjstr != '') {
			$initobj = '[['.str_replace(';','],[',$initobjstr).']]';
			$initobj = preg_replace('/\[([^\[\]]+?),/','[&quot;$1&quot;,',$initobj);
		}
	}
	$out = '<iframe src="https://s3-us-west-2.amazonaws.com/oervm/numbertiles/numbertiles.html?qn='.$qn.'&initbase='.$initbase.'&initobj='.$initobj.'" width="450" height="300" frameborder="0"></iframe>';
	return $out;
}

//vmnumbertilesgetcount(stuans)
//return an array array(hundredcount,tencount,onecount) of the count of
//hundred blocks, ten blocks, and ones blocks in the drop area
function vmnumbertilesgetcount($state) {
	list($initbasestr,$initobjstr,$cont) = explode('|',$state);
	$hund = substr_count($cont,"hund");
	$ten = substr_count($cont,"ten");
	$one = substr_count($cont,"one");
	return array($hund,$ten,$one);
}

//vmsetupitemsort(tosort,cats,stuans,qn,[part,width])
//Set up an item sort manipulative, where students sort items into 2 categories
//tosort = array of items to sort
//cats = array of titles for the drop areas
function vmsetupitemsort($numbers,$cats,$state,$qn,$part=null,$width=150) {
	if ($part !== null) {$qn = 1000*($qn)+$part;} else {$qn--;}
	$numbers = Sanitize::encodeUrlParam(implode(';;',$numbers));
	$height = (count($cats)>2)?600:300;
	$totwidth = $width*3 + 60;
	$cats = Sanitize::encodeUrlParam(implode(';;',$cats));
	$initbase = "[]";
	$initobj = "[]";
	if ($state!="") {
		list($initbasestr,$initobjstr,$cont) = explode('|',$state);
		if ($initbasestr != '') {
			$initbase = '[['.str_replace(';','],[',$initbasestr).']]';
			$initbase = preg_replace('/\[([^\[\]]+?),/','[&quot;$1&quot;,',$initbase);
		}
		if ($initobjstr != '') {
			$initobj = '[['.str_replace(';','],[',$initobjstr).']]';
			$initobj = preg_replace('/\[([^\[\]]+?),/','[&quot;$1&quot;,',$initobj);
		}
	}
	$out = '<iframe src="https://s3-us-west-2.amazonaws.com/oervm/numbersort/numbersort.html?qn='.$qn.'&initbase='.$initbase.'&initobj='.$initobj.'&str='.$numbers.'&cats='.$cats.'&width='.$width.'" width="'.$totwidth.'" height="'.$height.'" frameborder="0"></iframe>';
	return $out;
}

//vmitemsortgetcontainers(stuans, tosort)
//tosort = array of items to sort
//returns an array of container values
// out[i] gives the container that item tosort[i] was sorted into
// out[i] = -1 means unsorted; = 0 is first container, = 1 is second container
function vmitemsortgetcontainers($state, $n) {
	if (!is_array($n)) {
		$n = array();
	}
	list($initbasestr,$initobjstr,$cont) = explode('|',$state);
	//this is a very inelegant parsing of the container info
	//the format is
	//contname:el,el,el;contname2:el,el
	//where the el's are object names
	//In this case, we don't care about the container names, just that
	//they are in order.  The el's in this case are just
	// the index into the numbers array
	$contparts = explode(';',$cont);
	$out = array();
	for ($i=0;$i<count($n);$i++) {
		$out[$i] = -1;
	}

	for ($i=0;$i<count($contparts);$i++) {
		$p = explode(":",$contparts[$i]);
		if ($p[1]!='') {
			$pieces = explode(",",$p[1]);
			foreach ($pieces as $nv) {
				$out[$nv] = $i;
			}
		}
	}
	return $out;
}

//vmsetupnumberlineaddition(stuans,qn,[part])
//Set up a number line addition manipulative
function vmsetupnumberlineaddition($state,$qn,$part=null) {
        if ($part !== null) {$qn = 1000*($qn)+$part;} else {$qn--;}
        $initaddend = "";
        $initsum = "";
        if ($state!="") {
                list($initaddend,$initsum) = explode(',',$state);
        }
        $out = '<iframe src="https://s3-us-west-2.amazonaws.com/oervm/NumberLineAddition/NumberLineAddition.html?qn='.$qn.'&addend='.$initaddend.'&sum='.$initsum.'" width="625" height="130" frameborder="0"></iframe>';
        return $out;
}

//vmnumberlineadditiongetvals(stuans)
//return array(value of first dot, value of sum)
function vmnumberlineadditiongetvals($state) {
        return explode(',',$state);
}

//vmsetupnumberline(stuans,qn,[part,snap])
//Set up a number line manipulative, with a changeable scale
// set snap="false" to allow values in between grid markers
// to set snap without setting part, use null for part
function vmsetupnumberline($state,$qn,$part=null,$snap="true") {
        if ($part !== null) {$qn = 1000*($qn)+$part;} else {$qn--;}
        $initscale = "";
        $initval = "";
        if ($state!="") {
                list($initscale,$initval) = explode(',',$state);
        }
        $out = '<iframe src="https://s3-us-west-2.amazonaws.com/oervm/NumberLine/NumberLine.html?qn='.$qn.'&scale='.$initscale.'&val='.$initval.'&snap='.$snap.'" width="440" height="150" frameborder="0"></iframe>';
        return $out;
}

//vmnumberlinegetvals(stuans)
//return array(scale, value of dot)
function vmnumberlinegetvals($state) {
        $out = explode(',',$state);
        if (count($out) != 2) {
            $out = [1,0];
        }
        return $out;
}

//vmsetupnumberlineinterval(stuans,qn,[part])
//Set up a number line interval manipulative, with a changeable scale
function vmsetupnumberlineinterval($state,$qn,$part=null) {
        if ($part !== null) {$qn = 1000*($qn)+$part;} else {$qn--;}
        $initscale = "";
        $initNval = "";
        $initSval = "";
        $initlep = "";
        $initrep = "";
        if ($state!="") {
                list($initscale,$initSval,$initNval,$initlep,$initrep) = explode(',',$state);
        }
        $out = '<iframe src="https://s3-us-west-2.amazonaws.com/oervm/NumberLineInterval/NumberLineInterval.html?qn='.$qn.'&scale='.$initscale.'&Nval='.$initNval.'&Sval='.$initSval.'&lep='.$initlep.'&rep='.$initrep.'" width="625" height="250" frameborder="0"></iframe>';
        return $out;
}

//vmnumberlineintervalgetvals(stuans)
//return array(scale, left endpoint value, right endpoint value, left type, right type)
//types are 1 = arrow, 2 = paren, 3 = square bracket, 4 = none
function vmnumberlineintervalgetvals($state) {
        return explode(',',$state);
}

//vmsetupfractionline([stuans,qn,part])
//Set up a fraction number line manipulative, with a single line
function vmsetupfractionline($state="",$qn=null,$part=null) {
	if ($qn==null) {
		$querystr = '';
	} else {
		if ($part !== null) {$qn = 1000*($qn)+$part;} else {$qn--;}
		if ($state!="") {
			list($N,$D) = explode(',',$state);
		} else{
			$N = 1;  $D = 4;
		}
		$querystr = '?qn='.$qn.'&N='.$N.'&D='.$D;
	}
	return '<iframe src="https://s3-us-west-2.amazonaws.com/oervm/fractions/equivfrac.html'.$querystr.'" width="420" height="225" frameborder="0"></iframe>';
}

//vmgetfractionlineval(stuans)
//return array(numerator, denominator)
function vmgetfractionlinevals($state) {
	return explode(',',$state);
}


//vmsetupfractioncompare([stuans,qn,part])
//Set up a fraction number line comparison manipulative, with two lines
function vmsetupfractioncompare($state="",$qn=null,$part=null) {
	if ($qn==null) {
		$querystr = '';
	} else {
		if ($part !== null) {$qn = 1000*($qn)+$part;} else {$qn--;}
		if ($state!="") {
			list($Na,$Da,$Nb,$Db) = explode(',',$state);
		} else{
			$Na = 1;  $Da = 4; $Nb = 1;  $Db = 4;
		}
		$querystr = '?qn='.$qn.'&Na='.$Na.'&Da='.$Da.'&Nb='.$Nb.'&Db='.$Db;
	}
	return '<iframe src="https://s3-us-west-2.amazonaws.com/oervm/fractions/fraccompare.html'.$querystr.'" width="620" height="225" frameborder="0"></iframe>';
}

//vmgetfractioncompareval(stuans)
//return array(upper line numerator, upper line denominator, lower line numerator, lower line denominator)
function vmgetfractioncompareval($state) {
	return explode(',',$state);
}

//vmsetupfractionmult([stuans,qn,part])
//Set up a fraction multiplication manipulative, with a single line
function vmsetupfractionmult($state="",$qn=null,$part=null) {
	if ($qn==null) {
		$querystr = '';
	} else {
		if ($part !== null) {$qn = 1000*($qn)+$part;} else {$qn--;}
		if ($state!="") {
			list($Nh,$Dh,$Nv,$Dv) = explode(',',$state);
		} else{
			$Nh = 1;  $Dh = 4;
			$Nv = 1;  $Dv = 3;
		}
		$querystr = '?qn='.$qn.'&Nh='.$Nh.'&Dh='.$Dh.'&Nv='.$Nv.'&Dv='.$Dv;
	}
	return '<iframe src="https://s3-us-west-2.amazonaws.com/oervm/fractions/fracmult.html'.$querystr.'" width="620" height="605" frameborder="0"></iframe>';
}

//vmgetfractionmultvals(stuans)
//return array(horiz numerator, horiz denominator, vert num, vert denom)
function vmgetfractionmultvals($state) {
	return explode(',',$state);
}

function vmdrawinchruler($max=4,$val=0,$width=500,$height=80) {
	$maxx = ceil(16*$max+2);
	$svg = "initPicture(0,$maxx,0,10);rect([0,0],[$maxx,7]);";
	for ($i=1;$i<=$maxx;$i++) {
	  $h = 7 - sqrt(gcd($i,16));
	  $svg .= "line([$i,7],[$i,$h]);";
	  if ($i%16==0) {
	    $v = $i/16;
	    $svg .= "text([$i,$h],'$v','below');";
	  }
	}
	$alt = 'Ruler with 16 divisions between each labeled number.';
	if ($val > 0) {
		$valx = 16*$val;
		$svg .= "strokewidth=2;marker='arrow';stroke='blue';line([$valx,10],[$valx,7]);";
		$offset = $valx%16;
		$whole = floor($valx/16);
		$alt .= " Arrow is pointing $offset divisions past $whole";
	}
	return showasciisvg($svg,$width,$height,$alt);
}
function vmdrawcmruler($max=4,$val=0,$width=500,$height=80) {
	$maxx = ceil(10*$max+2);
	$svg = "initPicture(0,$maxx,0,10);rect([0,0],[$maxx,7]);";
	for ($i=1;$i<=$maxx;$i++) {
	  $h = ($i%10==0)?4:5.5;
	  $svg .= "line([$i,7],[$i,$h]);";
	  if ($i%10==0) {
	    $v = $i/10;
	    $svg .= "text([$i,$h],'$v','below');";
	  }
	}
	$alt = 'Ruler with 10 divisions between each labeled number.';
	if ($val > 0) {
		$valx = 10*$val;
		$svg .= "strokewidth=2;marker='arrow';stroke='blue';line([$valx,10],[$valx,7]);";
		$offset = $valx%10;
		$whole = floor($valx/10);
		$alt .= " Arrow is pointing $offset divisions past $whole";
	}
	return showasciisvg($svg,$width,$height,$alt);
}
function vmdrawclock($hr,$min,$size=200) {
	$fontsize = floor($size/12);
	$svg = "setBorder(5);initPicture(-5,5,-5,5);fontsize=$fontsize;";
	for ($i=0;$i<60;$i++) {
		if ($i%5==0) { continue; }
		$x1 = round(3.7*cos($i*M_PI/30),3);
		$x2 = round(3.9*cos($i*M_PI/30),3);
		$y1 = round(3.7*sin($i*M_PI/30),3);
		$y2 = round(3.9*sin($i*M_PI/30),3);
		$svg .= "line([$x1,$y1],[$x2,$y2]);";
	}
	$svg .= 'strokewidth=2;circle([0,0],5);';
	for ($i=1;$i<13;$i++) {
		$x1 = round(3.5*cos($i*M_PI/6),3);
		$x2 = round(3.9*cos($i*M_PI/6),3);
		$x3 = round(4.5*cos($i*M_PI/6),3);
		$y1 = round(3.5*sin($i*M_PI/6),3);
		$y2 = round(3.9*sin($i*M_PI/6),3);
		$y3 = round(4.5*sin($i*M_PI/6),3);
		$svg .= "line([$x1,$y1],[$x2,$y2]);";
		$val = (14-$i)%12 + 1;
		$svg .= "text([$x3,$y3],'$val');";
	}

	$x1 = round(2.4*cos((3-$hr-$min/60)/12*2*M_PI),3);
	$x2 = round(3.3*cos((15-$min)/60*2*M_PI),3);
	$y1 = round(2.4*sin((3-$hr-$min/60)/12*2*M_PI),3);
	$y2 = round(3.3*sin((15-$min)/60*2*M_PI),3);
	$svg .= "line([0,0],[$x1,$y1]);line([0,0],[$x2,$y2]);dot([0,0]);";
	$alt = 'Analog clock with the short hand pointing ';
	if ($min==0) {
		$alt .= "at $hr and the long hand pointing at 12";
	} else {
		$alt .= "between $hr and ".($hr%12+1);
		$alt .= " and the long hand pointing ";
		if ($min%5==0) {
			$alt .= 'at '.($min/5);
		} else {
			$rem = $min%5;
			$alt .= $rem . ' marks past '.floor($min/5);
		}
	}
	return showasciisvg($svg,$size,$size,$alt);
}

?>
