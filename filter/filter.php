<?php
	//IMathAS (c) 2006 David Lippman
	//Filter file - converts ASCIIsvg and ASCIImath to image tags
	//if needed
	
	//load in filters as needed
	$filterdir = rtrim(dirname(__FILE__), '/\\');
	//include("$filterdir/simplelti/simplelti.php");
	if ($sessiondata['mathdisp']==2) { //use image fallback for math
		include("$filterdir/math/ASCIIMath2TeX.php");
		$AMT = new AMtoTeX;
	} 
	if ($sessiondata['graphdisp']==2 || isset($loadgraphfilter)) { //use image fallback for graphs
		include("$filterdir/graph/asciisvgimg.php");
		$AS = new AStoIMG;
	} 
	function mathfiltercallback($arr) {
		global $AMT,$mathimgurl,$coursetheme;
		//$arr[1] = str_replace(array('&ne;','&quot;','&lt;','&gt;','&le;','&ge;'),array('ne','"','lt','gt','le','ge'),$arr[1]);
		$arr[1] = str_replace(array('&ne;','&quot;','&le;','&ge;'),array('ne','"','le','ge'),$arr[1]);
		$tex = $AMT->convert($arr[1]);
		if (trim($tex)=='') {
			return '';
		} else {
			if (isset($coursetheme) && strpos($coursetheme,'_dark')!==false) {
				$tex = '\\reverse '.$tex;
			}
			return ('<img style="vertical-align: middle;" src="'.$mathimgurl.'?'.rawurlencode($tex).'" alt="'.str_replace('"','&quot;',$arr[1]).'">');
		}
	}
	function svgfiltersscrcallback($arr) {
		global $filterdir, $AS, $imasroot;
		if (trim($arr[2])=='') {return $arr[0];}
		if (strpos($arr[0],'style')!==FALSE) {
			$sty = preg_replace('/.*style\s*=\s*(.)(.+?)\1.*/',"$2",$arr[0]);
		} else {
			$sty = "vertical-align: middle;";
		}
		$fn = md5($arr[2]);
		if (!file_exists($filterdir.'/graph/imgs/'.$fn.'.png')) {
			$AS->AStoIMG(300,300);
			$AS->processShortScript($arr[2]);
			$AS->outputimage($filterdir.'/graph/imgs/'.$fn.'.png');
		}
		return ('<img src="'.$imasroot.'/filter/graph/imgs/'.$fn.'.png" style="'.$sty.'" alt="Graphs"/>');
	}
	function svgfilterscriptcallback($arr) {
		global $filterdir, $AS, $imasroot;
		if (trim($arr[2])=='') {return $arr[0];}
		
		$w = preg_replace('/.*\bwidth\s*=\s*.?(\d+).*/',"$1",$arr[0]);
		$h = preg_replace('/.*\bheight\s*=\s*.?(\d+).*/',"$1",$arr[0]);
		
		if (strpos($arr[0],'style')!==FALSE) {
			$sty = preg_replace('/.*style\s*=\s*(.)(.+?)\1.*/',"$2",$arr[0]);
		} else {
			$sty = "vertical-align: middle;";
		}
		$fn = md5($arr[2].$w.$h);
			
		if (!file_exists($filterdir.'/graph/imgs/'.$fn.'.png')) {
			$AS->AStoIMG($w+0,$h+0);
			$AS->processScript($arr[2]);
			//echo $arr[2];
			$AS->outputimage($filterdir.'/graph/imgs/'.$fn.'.png');
		}
		return ('<img src="'.$imasroot.'/filter/graph/imgs/'.$fn.'.png" style="'.$sty.'" alt="Graphs"/>');
	}
	
	function filter($str) {
		global $sessiondata;
		if (strip_tags($str)==$str) {
			$str = str_replace("\n","<br/>\n",$str);
		}
		if ($sessiondata['graphdisp']==0) {
			if (strpos($str,'embed')!==FALSE) {
				$str = preg_replace('/<embed[^>]*alt="([^"]*)"[^>]*>/',"[$1]", $str);
			}
		}
		if ($sessiondata['mathdisp']==2) {
			$str = str_replace('\\`','&grave;',$str);
			if (strpos($str,'`')!==FALSE) {
				$str = preg_replace_callback('/`(.*?)`/s', 'mathfiltercallback', $str);
			}
			$str = str_replace('&grave;','`',$str);
		}
		if ($sessiondata['graphdisp']==2) {
			if (strpos($str,'embed')!==FALSE) {
				$str = preg_replace_callback('/<\s*embed[^>]*?sscr=(.)(.+?)\1.*?>/s','svgfiltersscrcallback',$str);
				$str = preg_replace_callback('/<\s*embed[^>]*?script=(.)(.+?)\1.*?>/s','svgfilterscriptcallback',$str);
			}
		}
		$search = '/\[LTI:\s*url=(.*),\s*key=(.*),\s*secret=([^\]]*)\]/';
		
		if (preg_match($search, $str, $res)){
			$url = $res[1];
			$key = $res[2];
			$secret = $res[3];
			$sessiondata['lti-secrets'][$key] = $secret;
			writesessiondata();	
			$replamnt = getltiiframe($url,$key,time());
			$str = preg_replace('/\[LTI:[^\]]*\]/', $replamnt, $str);
		}
		/* simplelti - deprecated.  No consumer support for basiclti yet
		$search = '/\[LTI:\s*url=(.*),\s*secret=([^\]]*)\]/';
		
		if (preg_match($search, $str, $res)){
			$secret = $res[2];
			$url = $res[1];
			$params = simplelti_get_request_params($secret);
			$response = simplelti_request($url, $secret,$params);
			$replamnt = simplelti_print_response($response);
			$str = preg_replace('/\[LTI:[^\]]*\]/', $replamnt, $str);
		}
		*/
		return $str;
	}
	function filtergraph($str) {
		global $sessiondata;
		if ($sessiondata['graphdisp']==2) {
			if (strpos($str,'embed')!==FALSE) {
				$str = preg_replace_callback('/<\s*embed.*?sscr=(.)(.+?)\1.*?>/','svgfiltersscrcallback',$str);
				$str = preg_replace_callback('/<\s*embed.*?script=(.)(.+?)\1.*?>/','svgfilterscriptcallback',$str);
			}
		}
		return $str;
	}
	function forcefiltergraph($str) {
		global $filterdir;
		if (strpos($str,'embed')!==FALSE) {
			$str = preg_replace_callback('/<\s*embed.*?sscr=(.)(.+?)\1.*?>/','svgfiltersscrcallback',$str);
			$str = preg_replace_callback('/<\s*embed.*?script=(.)(.+?)\1.*?>/','svgfilterscriptcallback',$str);
		}
		return $str;
	}
	function getgraphfilename($str) {
		$str = forcefiltergraph($str);
		preg_match('/(\w+\.png)/',$str,$matches);
		return ($matches[1]);
	}
	function printfilter($str) {
		$str = preg_replace('/<script.*?\/script>/','',$str);  //strip scripts
		$str = preg_replace('/<input[^>]*Preview[^>]*>/','',$str); //strip preview buttons
		$str = preg_replace('/<input[^>]*text[^>]*>/','__________________',$str);
		$str = preg_replace('/<select.*?\/select>/','____',$str);
		$str = preg_replace('/<input[^>]*hidden[^>]*>/','',$str); //strip hidden fields
		return $str;
	}
	
	function getltiiframe($url,$key,$linkback) {
		global $cid;
		if (!isset($cid) && isset($_GET['cid'])) {
			$cid = $_GET['cid'];
		}
		$height = '95%';
		$width = '95%';
		$param = 'key='.urlencode($key) . '&linkback=' . urlencode($linkback) . '&url=' . urlencode($url);
		if (isset($cid)) {
			$param .= '&cid='.$cid;
		}
		$out = '<iframe src="'.$imasroot.'/filter/basiclti/post.php?'.$param.'" height="'.$height.'" width="'.$width.'" ';
		$out .= 'scrolling="auto" frameborder="1" transparency>   <p>Error</p> </iframe>';	
		return $out;
	}
		
?>
