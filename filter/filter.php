<?php
	//IMathAS (c) 2006 David Lippman
	//Filter file - converts ASCIIsvg and ASCIImath to image tags
	//if needed
	
	//load in filters as needed
	$filterdir = rtrim(dirname(__FILE__), '/\\');
	if ($sessiondata['mathdisp']==2) { //use image fallback for math
		include("$filterdir/math/ASCIIMath2TeX.php");
		$AMT = new AMtoTeX;
	} 
	if ($sessiondata['graphdisp']==2 || isset($loadgraphfilter)) { //use image fallback for graphs
		include("$filterdir/graph/asciisvgimg.php");
		$AS = new AStoIMG;
	} 
	function mathfiltercallback($arr) {
		global $AMT,$mathimgurl;
		$arr[1] = str_replace(array('&ne;','&quot;'),array('ne','"'),$arr[1]);
		return ('<img style="vertical-align: middle;" src="'.$mathimgurl.'?'.rawurlencode($AMT->convert($arr[1])).'" alt="'.str_replace('"','&quot;',$arr[0]).'">');
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
			if (strpos($str,'`')!==FALSE) {
				$str = preg_replace_callback('/`(.*?)`/s', 'mathfiltercallback', $str);
			}
		}
		if ($sessiondata['graphdisp']==2) {
			if (strpos($str,'embed')!==FALSE) {
				$str = preg_replace_callback('/<\s*embed.*?sscr=(.)(.+?)\1.*?>/s','svgfiltersscrcallback',$str);
				$str = preg_replace_callback('/<\s*embed.*?script=(.)(.+?)\1.*?>/s','svgfilterscriptcallback',$str);
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
		
?>
