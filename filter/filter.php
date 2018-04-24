<?php
	//IMathAS (c) 2006 David Lippman
	//Filter file - converts ASCIIsvg and ASCIImath to image tags
	//if needed

	//load in filters as needed
	$filterdir = rtrim(dirname(__FILE__), '/\\');
	//include("$filterdir/simplelti/simplelti.php");
	if ((isset($sessiondata['mathdisp']) && $sessiondata['mathdisp']==2 ) || isset($loadmathfilter)) { //use image fallback for math
		include("$filterdir/math/ASCIIMath2TeX.php");
		$AMT = new AMtoTeX;
	}
	if ((isset($sessiondata['graphdisp']) && $sessiondata['graphdisp']==2) || isset($loadgraphfilter)) { //use image fallback for graphs
		include("$filterdir/graph/asciisvgimg.php");
		$AS = new AStoIMG;
	}
	if ((!isset($sessiondata['graphdisp']) || $sessiondata['graphdisp']==0)) {
		include_once("$filterdir/graph/sscrtotext.php");
	}
	function mathfiltercallback($arr) {
		global $AMT,$mathimgurl,$coursetheme,$sessiondata;
		//$arr[1] = str_replace(array('&ne;','&quot;','&lt;','&gt;','&le;','&ge;'),array('ne','"','lt','gt','le','ge'),$arr[1]);
		$arr[1] = str_replace(array('&ne;','&quot;','&le;','&ge;','<','>'),array('ne','"','le','ge','&lt;','&gt;'),$arr[1]);
		$tex = $AMT->convert($arr[1]);
		if (trim($tex)=='') {
			return '';
		} else {
			if (isset($coursetheme) && strpos($coursetheme,'_dark')!==false) {
				$tex = '\\reverse '.$tex;
			}
			if ($sessiondata['texdisp']==true) {
				if (isset($sessiondata['texdoubleescape'])) {
					return ' \\\\('.htmlentities($tex).'\\\\) ';
				} else {
					return ' '.htmlentities($tex).' ';
				}
			} else {
				return ('<img style="vertical-align: middle;" src="'.$mathimgurl.'?'.rawurlencode($tex).'" alt="'.str_replace('"','&quot;',$arr[1]).'">');
			}
		}
	}
	function svgsscrtotextcallback($arr) {
		if (trim($arr[2])=='') {return '';}
		return '['.shortscriptToText($arr[2]).']';
	}
	function svgfiltersscrcallback($arr) {
		global $filterdir, $AS, $imasroot;
		if (trim($arr[2])=='') {return $arr[0];}

		if (!isset($AS) || $AS===null) {
			include("$filterdir/graph/asciisvgimg.php");
			$AS = new AStoIMG;
		}

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

		if (!isset($AS) || $AS===null) {
			include("$filterdir/graph/asciisvgimg.php");
			$AS = new AStoIMG;
		}

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
		global $sessiondata,$userfullname,$urlmode,$imasroot;
		if ($urlmode == 'https://') {
			$str = str_replace(array('http://www.youtube.com','http://youtu.be'),array('https://www.youtube.com','https://youtu.be'), $str);
		}
		if (strip_tags($str)==$str) {
			$str = str_replace("\n","<br/>\n",$str);
		}
		if ($sessiondata['graphdisp']==0) {
			if (strpos($str,'embed')!==FALSE) {
				$str = preg_replace('/<embed[^>]*alt="([^"]*)"[^>]*>/',"[$1]", $str);
				//$str = preg_replace('/<embed[^>]*sscr[^>]*>/',"[Graph with no description]", $str);
				$str = preg_replace_callback('/<\s*embed[^>]*?sscr=(.)(.+?)\1.*?>/s','svgsscrtotextcallback',$str);
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
		} else {
			$str = str_replace("<embed type='image/svg+xml'","<embed type='image/svg+xml' wmode=\"transparent\" ",$str);
			$str = str_replace("src=\"$imasroot/javascript/d.svg\"","",$str);
		}

		if (strpos($str,'[WA')!==false) {
			$search = '/\[WA:\s*(.+?)\s*\]/';

			if (preg_match_all($search, $str, $res, PREG_SET_ORDER)){
				foreach ($res as $resval) {
					$tag = '<script type="text/javascript" id="WolframAlphaScript'.$resval[1].'" src="'.$urlmode.'//www.wolframalpha.com/widget/widget.jsp?id='.$resval[1].'"></script>';
					$str = str_replace($resval[0], $tag, $str);
				}
			}
		}

		if (strpos($str,'[EMBED')!==false) {
			$search = '/\[EMBED:\s*([^\]]+)\]/';

			if (preg_match_all($search, $str, $res, PREG_SET_ORDER)){
				foreach ($res as $resval) {
					$respt = explode(',',$resval[1]);
					if (isset($respt[3])) {
						$nobord = true;
						array_pop($respt);
					} else {
						$nobord = false;
					}
					if (count($respt)==1) {
						$url = $respt[0]; $w = 600; $h = 400;
					} else if (count($respt)<3) {
						continue;
					} else {
						if (strpos($respt[2],'http')!==false) {
							list ($w,$h,$url) = $respt;
						} else {
							list ($url,$w,$h) = $respt;
						}
					}
					$url = trim(str_replace(array('"','&nbsp;'),'',$url));
					if (substr($url,0,18)=='https://tegr.it/y/') {
						$url = preg_replace('/[^\w:\/\.]/','',$url);
						//$tag = '<script type="text/javascript" src="'.$url.'"></script>';
						$url = "$imasroot/course/embedhelper.php?w=$w&amp;h=$h&amp;type=tegrity&amp;url=".Sanitize::encodeUrlParam($url);
						$tag = "<iframe width=\"$w\" height=\"$h\" src=\"$url\" frameborder=\"0\"></iframe>";

					} else {
						$tag = "<iframe width=\"$w\" height=\"$h\" src=\"$url\" ";
						if ($nobord) {
							$tag .= 'frameborder="0" ';
						}
						$tag .= "></iframe>";
					}
					$str = str_replace($resval[0], $tag, $str);
				}
			}
		}

		if (strpos($str,'[CDF')!==false) {
			$search = '/\[CDF:\s*([^,]+),([^,]+),([^,\]]+)\]/';

			if (preg_match_all($search, $str, $res, PREG_SET_ORDER)){
				foreach ($res as $resval) {
					/*if (!isset($GLOBALS['has_set_cdf_embed_script'])) {
						$GLOBALS['has_set_cdf_embed_script'] = true;
						$tag = '<script type="text/javascript" src="'.$urlmode.'www.wolfram.com/cdf-player/plugin/v2.1/cdfplugin.js"></script><script type="text/javascript">var cdf = new cdfplugin();';
					} else {
						$tag = '<script type="text/javascript">';
					}
					if (strpos($resval[3],'http')!==false) {
						list ($junk,$w,$h,$url) = $resval;
					} else {
						list ($junk,$url,$w,$h) = $resval;
					}

					$tag .= "cdf.embed('$url',$w,$h);</script>";
					$str = str_replace($resval[0], $tag, $str);
					*/
					if (strpos($resval[3],'http')!==false) {
						list ($junk,$w,$h,$url) = $resval;
					} else {
						list ($junk,$url,$w,$h) = $resval;
					}
					$url = "$imasroot/course/embedhelper.php?w=$w&amp;h=$h&amp;type=cdf&amp;url=".Sanitize::encodeUrlParam($url);
					$tag = "<iframe width=\"$w\" height=\"$h\" src=\"$url\" frameborder=\"0\"></iframe>";
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
	function forcefiltermath($str) {
		$str = str_replace('\\`','&grave;',$str);
		if (strpos($str,'`')!==FALSE) {
			$str = preg_replace_callback('/`(.*?)`/s', 'mathfiltercallback', $str);
		}
		$str = str_replace('&grave;','`',$str);
		return $str;
	}
	function getgraphfilename($str) {
		$str = forcefiltergraph($str);
		preg_match('/(\w+\.png)/',$str,$matches);
		return ($matches[1]);
	}
	function getgraphfilenames($str) {
		preg_match_all('/(\w+\.png)/',$str,$matches,PREG_PATTERN_ORDER);
		return ($matches[1]);
	}
	function mathentitycleanup($arr) {
		$arr[1] = str_replace(array('<','>'),array('&lt;','&gt;'),$arr[1]);
		return '`'.$arr[1].'`';
	}
	function printfilter($str) {
		global $imasroot;
		$str = preg_replace('/<canvas.*?\'(\w+\.png)\'.*?\/script>/','<div><img src="'.$imasroot.'/filter/graph/imgs/$1" alt="Graph"/></div>',$str);
		$str = preg_replace('/<script.*?\/script>/','',$str);  //strip scripts
		$str = preg_replace('/<input[^>]*Preview[^>]*>/','',$str); //strip preview buttons
		if (isset($_POST['hidetxtboxes'])) {
			$str = preg_replace('/<input[^>]*text[^>]*>/','',$str);
			$str = preg_replace('/<input[^>]*(radio|checkbox)[^>]*>/','',$str);
			$str = preg_replace('/<select.*?\/select>/','',$str);
		} else {
			$str = preg_replace('/<input[^>]*text[^>]*>/','__________________',$str);
			$str = preg_replace('/<input[^>]*(radio|checkbox)[^>]*>/','__',$str);
			$str = preg_replace('/<select.*?\/select>/','____',$str);
		}
		$str = preg_replace('/<table/','<table cellspacing="0"',$str);
		$str = preg_replace('/`\s*(\w)\s*`/','<i>$1</i>',$str);

		$str = preg_replace('/<input[^>]*hidden[^>]*>/','',$str); //strip hidden fields
		if (strpos($str,'`')!==FALSE) {
			$str = preg_replace_callback('/`(.*?)`/s', 'mathentitycleanup', $str);
		}
		return $str;
	}


?>
