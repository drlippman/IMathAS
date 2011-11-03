<?php
	//IMathAS (c) 2006 David Lippman
	//Filter file - converts ASCIIsvg and ASCIImath to image tags
	//if needed
	
	//load in filters as needed
	$filterdir = rtrim(dirname(__FILE__), '/\\');
	//include("$filterdir/simplelti/simplelti.php");
	if (isset($sessiondata['mathdisp']) && $sessiondata['mathdisp']==2) { //use image fallback for math
		include("$filterdir/math/ASCIIMath2TeX.php");
		$AMT = new AMtoTeX;
	} 
	if (isset($sessiondata['graphdisp']) && $sessiondata['graphdisp']==2 || isset($loadgraphfilter)) { //use image fallback for graphs
		include("$filterdir/graph/asciisvgimg.php");
		$AS = new AStoIMG;
	} 
	function mathfiltercallback($arr) {
		global $AMT,$mathimgurl,$coursetheme,$sessiondata;
		//$arr[1] = str_replace(array('&ne;','&quot;','&lt;','&gt;','&le;','&ge;'),array('ne','"','lt','gt','le','ge'),$arr[1]);
		$arr[1] = str_replace(array('&ne;','&quot;','&le;','&ge;'),array('ne','"','le','ge'),$arr[1]);
		$tex = $AMT->convert($arr[1]);
		if (trim($tex)=='') {
			return '';
		} else {
			if (isset($coursetheme) && strpos($coursetheme,'_dark')!==false) {
				$tex = '\\reverse '.$tex;
			}
			if ($sessiondata['texdisp']==true) {
				return htmlentities($tex);
			} else {
				return ('<img style="vertical-align: middle;" src="'.$mathimgurl.'?'.rawurlencode($tex).'" alt="'.str_replace('"','&quot;',$arr[1]).'">');
			}
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
		global $sessiondata,$userfullname;
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
		
		if (strpos($str,'[LTI')!==false) {
			$search = '/\[LTI:\s*url=(.+),\s*key=(.+),\s*secret=([^\],]+)([^\]]*)\]/';
			
			if (preg_match($search, $str, $res)){
				$url = $res[1];
				$key = $res[2];
				$secret = $res[3];
				if (isset($res[4]) && $res[4]!='') {
					$opts = $res[4];
				} else {
					$opts = '';
				}
				$sessiondata['lti-secrets'][$key] = array($secret,$opts);
				writesessiondata();	
				$replamnt = getltiiframe($url,$key,time());
				$str = preg_replace('/\[LTI:[^\]]*\]/', $replamnt, $str);
			}
		}
		
		if (strpos($str,'[WA')!==false) {
			$search = '/\[WA:\s*(.+?)\s*\]/';
			
			if (preg_match_all($search, $str, $res, PREG_SET_ORDER)){
				foreach ($res as $resval) {
					$tag = '<script type="text/javascript" id="WolframAlphaScript'.$resval[1].'" src="http://www.wolframalpha.com/widget/widget.jsp?id='.$resval[1].'"></script>';
					$str = str_replace($resval[0], $tag, $str);
				}
			}
		}
		
		if (strpos($str,'[EMBED')!==false) {
			$search = '/\[EMBED:\s*([^,]+),([^,]+),([^,\]]+)\]/';
			
			if (preg_match_all($search, $str, $res, PREG_SET_ORDER)){
				foreach ($res as $resval) {
					$tag = "<iframe width=\"{$resval[1]}\" height=\"{$resval[2]}\" src=\"{$resval[3]}\" ></iframe>";
					$str = str_replace($resval[0], $tag, $str);
				}
			}
		}
		
		if (strpos($str,'[CDF')!==false) {
			$search = '/\[CDF:\s*([^,]+),([^,]+),([^,\]]+)\]/';
	
			if (preg_match_all($search, $str, $res, PREG_SET_ORDER)){
				foreach ($res as $resval) {
					if (!isset($GLOBALS['has_set_cdf_embed_script'])) {
						$GLOBALS['has_set_cdf_embed_script'] = true;
						$tag = '<script type="text/javascript" src="http://www.wolfram.com/cdf-player/plugin/v2.1/cdfplugin.js"></script><script type="text/javascript">var cdf = new cdfplugin();';
					} else {
						$tag = '<script type="text/javascript">';
					}
					$tag .= "cdf.embed('{$resval[1]}',{$resval[2]},{$resval[3]});</script>";
					$str = str_replace($resval[0], $tag, $str);
				}
			}
		}
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
		$str = preg_replace('/<input[^>]*(radio|checkbox)[^>]*>/','__',$str);
		$str = preg_replace('/<table/','<table cellspacing="0"',$str);
		$str = preg_replace('/`\s*(\w)\s*`/','<i>$1</i>',$str);
		$str = preg_replace('/<select.*?\/select>/','____',$str);
		$str = preg_replace('/<input[^>]*hidden[^>]*>/','',$str); //strip hidden fields
		return $str;
	}
	
	function getltiiframe($url,$key,$linkback) {
		global $cid,$imasroot;
		if (!isset($cid) && isset($_GET['cid'])) {
			$cid = $_GET['cid'];
		}
		$height = '500px';
		$width = '95%';
		$param = 'key='.urlencode($key) . '&linkback=' . urlencode($linkback) . '&url=' . urlencode(str_replace('&amp;','&',$url));
		if (isset($cid)) {
			$param .= '&cid='.$cid;
		}
		$out = '<iframe src="'.$imasroot.'/filter/basiclti/post.php?'.$param.'" height="'.$height.'" width="'.$width.'" ';
		$out .= 'scrolling="auto" frameborder="1" transparency>   <p>Error</p> </iframe>';	
		return $out;
	}
		
?>
