<?php
require_once "../includes/htmLawed.php";
$loadmathfilter = true; $loadgraphfilter = true;
require_once "../filter/filter.php";
require_once "../includes/filehandler.php";


function mbxfilter($str) {
	global $basesiteurl,$imasroot;
	$mathexp = array();  $mathexpcnt = -1;
	$svgexp = array(); $svgexpcnt = -1;
//$C = array('elements'=>'*-script-form');

//$str = 'This is <span>the</span> text `x^2+3` and `3 &lt; 4` blah<br><br>And heres a table:<table class="stats"><thead><tr><th scope="col">x</p><p>3</th><th scope="col">y</th></tr></thead><tbody><tr><td>1</td><td>1</td></tr><tr><td>8</td><td>8</td></tr></tbody></table><br><br>Do something:';
//$str = 'This is <span>the</span> text `x^2+3` and `3 &lt; 4` blah<br><br>And heres blah<br>and on the next line';
//$str = 'Test <i>x</i><sup>3</sup> blah.Test y<sup>2</sup> blah';


// rewrite auto-added answerboxes to use p instead of div
    $str = preg_replace('|<div class="toppad">(.*?)</div>|', '<p>$1</p>', $str);

//rewrite drawing canvas
	$str = preg_replace('/<canvas.*?\'(\w+\.png)\'.*?\/script>/','<img src="'.$imasroot.'/filter/graph/imgs/$1" alt="Graph"/>',$str);

//render any images
	$str = preg_replace_callback('/<\s*embed[^>]*?sscr=(.)(.+?)\1.*?>/s','svgfiltersscrcallback',$str);
	$str = preg_replace_callback('/<\s*embed[^>]*?script=(.)(.+?)\1.*?>/s','svgfilterscriptcallback',$str);
	//store in S3 if needed
	$curdir = rtrim(dirname(__FILE__), '/\\');
	if (getfilehandlertype('filehandlertype') == 's3') {
		$str = preg_replace_callback('#"'.$imasroot.'/filter/graph/imgs/(\w+\.png)#', function($m) {
			global $curdir;
			$newurl = relocatefileifneeded($curdir.'/../filter/graph/imgs/'.$m[1], 'gimgs/'.$m[1]);
			return '"'.$newurl;
		}, $str);
	} else {
		//rewrite to absolute URL
		$str = str_replace('"'.$imasroot.'/filter/graph/imgs/', '"'.$basesiteurl.'/filter/graph/imgs/', $str);
	}

//match and close tags; rebalance
//strip any tags we're not planning on dealing with.
	//pre-strip divs, as they can mess up block-level matching
	$str = preg_replace('#</?div[^>]*>#','',$str);
	//strip scripts - don't want to see insides left behind
	$str = preg_replace('#<script.*?</script>\s*#s','',$str);
	//if not editor-generated, wrap in <p> and rewrite double line breaks.
	if (substr($str,0,2)!='<p') {
		$str = '<p>'.$str.'</p>';
	}
	$str = str_replace('<br><br>','</p><p>',$str);
	$str = str_replace(array('<br>','<br/>','<br />','<BR>','<BR/>','<BR />'),'</p><p>',$str);
	//make a stab at unwrapping tables up front
	$str = str_replace(array('<table','</table>'), array('</p><table>','</table><p>'), $str);

    // wrap image in div so htmlawed will break it out of inside p
	$str = preg_replace('|<img.*?src="(.*?)"[^>]*>(\s*</img>)?|','<p>$0</p>', $str);

    //strip buttons, since they won't translate, and we'll be left with the text of the button which isn't desirable
    $str = preg_replace('/<button[^>]*>.*?<\/button>/','',$str); 

	if (!empty($_GET['preservesvg'])) {
		$str = preg_replace_callback('|<svg[^>]*>.*?</svg>|s', function($m) {
			global $svgexp, $svgexpcnt;
			$svgexpcnt++;
			$svgexp[$svgexpcnt] = $m[0];
			return '<p>@SVGIMG'.$svgexpcnt.'</p>';
		}, $str);
	}

	//enforce stuff
	$C = array('elements'=>'a,b,br,canvas,em,h1,h2,h3,h4,h5,h6,i,img,input,li,ol,option,p,pre,select,strong,sub,sup,table,tbody,td,textarea,th,thead,tr,u,ul,statement,solution,hint');
	$str = htmLawed($str, $C);
	$str = str_replace("\n\n","\n",$str);

//rewrite input boxes, select, textarea, etc.
	$str = preg_replace('/<input[^>]*Preview[^>]*>\s*/','',$str); //strip preview buttons
	$str = preg_replace('/<input[^>]*hidden[^>]*>\s*/','',$str); //strip hidden fields

	//assume we've bypassed any $displayformat options for choices,multans,matching
	//replace any radio or checkboxes with nothing (they're already in a list)
	$str = preg_replace('#<input[^>]*type="?(checkbox|radio)[^>]*>#','',$str);
	//replace <ul class=nomark> with circle list (might be nicer to do ordered list for print purposes?
	$str = preg_replace('#<ul[^>]*class="?nomark[^>]*>(.*?)</ul>#s','<ul label="circle">$1</ul>',$str);

	//any remaining selects should be from matching pull-downs.  Remove them.
	$str = preg_replace('#<select.*</select>#','',$str);

	//any remaining inputs should be text fields; replace with <var>
	$varcnt = 0;
	$str = preg_replace_callback('#<input[^>]*size="?(\d+)[^>]*>#',function($m) {
		global $varcnt;
		$varcnt++;
		//return '<var name="qn'.$varcnt.'" width="'.$m[1].'" />';
		return '<fillin characters="'.$m[1].'" />';
	  },$str);

	//replace any textareas
	$str = preg_replace_callback('#<textarea*cols="?(\d+)[^>]*>.*?</textarea>#',function($m) {
		global $varcnt;
		$varcnt++;
		//return '<var name="qn'.$varcnt.'" width="'.$m[1].'" form="essay" />';
		return '<fillin characters="'.$m[1].'" />';
	  },$str);


//convert tables
	//strip thead/tbody
	$str = preg_replace('#</?(tbody|thead)[^>]*>#','',$str);
	//replace <table> with <tabular>
	$str = preg_replace('|<table[^>]*>|','<tabular>', $str);
	$str = str_replace('</table>', '</tabular>', $str);
	//replace <tr>
	$str = preg_replace('/<tr[^>]*>/','<row>', $str);
	$str = str_replace('</tr>', '</row>', $str);
	//replace <td>, <th>
	$str = preg_replace('#<(td|th)[^>]*>#','<cell>', $str);
	$str = str_replace(array('</td>','</th>'), '</cell>', $str);

    
//convert images, remove wrapping div. don't bother wrapping in figure
	$str = preg_replace('|<p><img.*?src="(.*?)"[^>]*>(\s*</img>)?\s*</p>|','<image source="$1" />', $str);

//rewrite specific tags
	//links
	$str = preg_replace('|<a[^>]*?href="(.*?)"[^>]*>(.*?)</a>|','<url href="$1">$2</url>',$str);

	//strip any classes or such from format tags we'll rewrite
	$str = preg_replace('#<(b|i|u|strong|br|h\d|sub|sup)\b[^>]*>#','<$1>',$str);

	//attempt to rewrite sub/sup as math.  Base or exponent might be italicized
	$str = preg_replace_callback('#(<i>)?(\w+?)(</i>)?<sup>(\w+?)</sup>#',function($m) {
		global $mathexp, $mathexpcnt;
		$mathexpcnt++;
		$mathexp[$mathexpcnt] = '{'.$m[2].'}^{'.$m[4].'}';
		return '<m>'.$mathexpcnt.'</m>';
		}, $str);
	$str = preg_replace_callback('#(<i>)?(\w+?)(</i>)?<sub>(\w+?)</sub>#',function($m) {
		global $mathexp, $mathexpcnt;
		$mathexpcnt++;
		$mathexp[$mathexpcnt] = '{'.$m[2].'}_{'.$m[4].'}';
		return '<m>'.$mathexpcnt.'</m>';
		}, $str);

	//rewrite opening and closing tags. Strip any remaining sub/sup
	$str = str_replace(
		array('<b>','<i>','<u>','<strong>','<br>','<h1>','<h1>','<h2>','<h3>','<h5>','<h6>','<sup>','<sub>'),
		array('<em>','<em>','','<em>','','<p>','<p>','<p>','<p>','<p>','<p>','^','_'),
		$str);
	$str = str_replace(
		array('</b>','</i>','</u>','</strong>','</br>','</h1>','</h1>','</h2>','</h3>','</h5>','</h6>','</sup>','</sub>'),
		array('</em>','</em>','','</em>','','</p>','</p>','</p>','</p>','</p>','</p>','',''),
		$str);

	//HTML doesn't allow ul and ol inside p; mbx requires it, so wrap ul and ol in p
	//this is going to make a mess of nested lists
	$str = str_replace(array('<ul','<ol','</ul>','</ol>'),array('<p><ul','<p><ol','</ul></p>','</ol></p>'), $str);


//capture math and convert to latex

	$str = preg_replace_callback('/`(.*?)`/s', function($m) {
		global $mathexp, $mathexpcnt, $AMT;
		$mathexpcnt++;
        $m[1] = normalizemathunicode($m[1]);
        $m[1] = str_replace('&amp;','&',$m[1]);
		$m[1] = str_replace(array('&ne;','&quot;','&le;','&ge;','<','>','&lt;','&gt;','&#8321;','&#8322;','&sup2;','&sup3;','&sup2','&sup3'),
                            array('ne','"','le','ge','lt','gt','lt','gt','_1','_2','^2','^3','^2','^3'),$m[1]);
        $tex = $AMT->convert($m[1]);
		$tex = str_replace('&','\amp',$tex);  //need to use \amp macro for array enviro & symbols
		$mathexp[$mathexpcnt] = $tex;
		return '<m>'.$mathexpcnt.'</m>';
	  }, $str);

    // store tags and attributes to avoid overwriting with $ent sub
    $tags = [];
    $tagcnt = 0;
    $str = preg_replace_callback('/<[^\/].*?>/s', function($m) {
        global $tags,$tagcnt;
        $tagcnt++;
        $tags[$tagcnt] = $m[0];
        return '<t'.$tagcnt.'>';
    }, $str);


//convert entities that we don't want to disturb inside math
	//?? What about &le; and &ge;?
	$ent = array(
		'&nbsp;–'=>'<ndash/>',
		'&ndash;'=>'<ndash/>',
		'–'=>'<ndash/>',
		'&mdash;'=>'<mdash/>',
		'—'=>'<mdash/>',
		'&lt;'=>'<less/>',
		'&gt;'=>'<greater/>',
		'&le;'=>'<m>\le</m>',
		'&ge;'=>'<m>\ge</m>',
		'&hellip;'=>'<ellipsis/>',
		'…'=>'<ellipsis/>',
		'$'=>'<dollar/>',
		'%'=>'<percent/>',
		'^'=>'<circumflex/>',
		'_'=>'<underscore/>',
		'{'=>'<lbrace/>',
		'}'=>'<rbrace/>',
		'['=>'<lbracket/>',
		']'=>'<rbracket/>',
		'~'=>'<tilde/>',
		'\\'=>'<backslash/>',
		'*'=>'<asterisk/>',
		'&ldquo;'=>'<lq/>',
		'&rdquo;'=>'<rq/>',
		'&lsquo;'=>'<lsq/>',
		'&rsquo;'=>'<rsq/>',
		'&amp'=>'&',
		'“'=>'<lq/>',
		'”'=>'<rq/>',
		'‘'=>'<lsq/>',
		'’'=>'<rsq/>',
	);
	$str = str_replace(array_keys($ent),array_values($ent), $str);

//attempt to convert any remaining entities to unicode, then convert & to <ampersand/>
	$str = html_entity_decode($str);
	$str = str_replace(['#','&'],['<hash/>','<ampersand />'],$str);

//restore tags
    $str = preg_replace_callback('|<t(\d+)>|', function($m) {
    global $tags;
    return $tags[$m[1]];
    }, $str);
    
//restore math
	$str = preg_replace_callback('|<m>(\d+)</m>|', function($m) {
		global $mathexp;
		return '<m>'.$mathexp[$m[1]].'</m>';
	  }, $str);

//restore svg
	if (!empty($_GET['preservesvg'])) {
		$str = preg_replace_callback('|<p>@SVGIMG(\d+)</p>|', function($m) {
			global $svgexp;
			return '<image>'.$svgexp[$m[1]].'</image>';
		}, $str);
	}

//pretty up line spaces
	$str = preg_replace('#<p>\s*</p>#s','',$str);
	$str = str_replace('</p><p>',"</p>\n<p>",$str);

return $str;
}
?>
