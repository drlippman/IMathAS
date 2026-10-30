<?php
require_once __DIR__."/../../assessment/mathparser.php";
// ASCIIsvgIMG.php
// (c) 2006-2009 David Lippman   http://www.pierce.ctc.edu/dlippman
// Generates an image based on an ASCIIsvg script
// as a backup for ASCIIsvg.js SVG generator script
//
// Revised 3/08 to add angle to text
// Revised 8/09 to add arc
// Major rewrite 10/26 to make more consistent with JS
//
// Based on ASCIIsvg.js (c) Peter Jipsen
// http://www.chapman.edu/~jipsen/svg/asciisvg.html
//
// Recognized commands:
//	setBorder(border) or setBorder(left,bottom,right,top)
//	initPicture(xmin,xmax,{ymin,ymax})
//	axes(xtick,ytick,{"labels",xgrid,ygrid,dox,doy})      //0 for off
//
//	plot("f(x)",{xmin,xmax,steps})
//	plot("[x(t),y(t)]",{tmin,tmax,steps})
//	slopefield("dy/dx",{xres,yres})

//	line([x1,y1],[x2,y2])
//	path([[x1,y1],...,[xn,yn]])
//	circle([x1,y1],rad)
//	ellipse([x1,y1],xrad,yrad)
//      arc([x1,y1],[x2,y2],rad)       //arc drawn along counterclockwise arc
//	rect([x1,y1],[x2,y2])
//	text([x1,y1],"string",{pos,angle});  	//(pos: left,right,above,below,aboveleft,...)
//	textabs([pixelx,pixely],"string",{pos,angle});
//	dot([x1,y1],{type,label,pos});	//(type: open, closed)
//	stroke = "color"		//line color
//	fill = "color"			//fill color
//      fontfill = "color"		//text color
//	fontbackground = "color"	//background color for text
//	strokewidth=width		//line thickness
//	strokedasharray=array		//dash array, ie "5 3" for 5 pixel color, 3 white
//	marker = marker			//"dot" or "arrow" or "arrowdot" or "none"
//					//marker for lines or paths
//
// Use:
//	$AS = new AStoIMG(width,height);
//	$AS->processScript($ASscriptstring);
//		or
//	$AS->processShortScript($ASsscrString);
//	$AS->outputimage({filename});	//if no filename, outputed to stream

class AStoIMG
{

var $usegd2, $usettf;
var $xmin = -5;
var $xmax = 5;
var $ymin = -5;
var $ymax = 5;
var $border = array(0,0,0,0);
var $origin = array(0,0);
var $width;
var $height;
var $img;
var $winxmax,$winxmin,$winymin,$winymax;
var $white,$black,$red,$orange,$yellow,$green,$blue,$cyan,$purple,$gray;
var $stroke = 'black', $fill = 'none', $curdash='', $isdashed=false, $marker='none';
var $markerfill = 'yellow', $markerstroke = 'black',  $gridcolor = 'gray', $axescolor = 'black', $arrowrelsize = 1, $arrowoffset = 0;
var $strokewidth = 1, $xunitlength, $yunitlength, $dotradius=8, $ticklength=4;
var $fontsize = 16, $fontfile, $fontfill='black', $fontbackground='';
var $isinit = false;
var $colors = array();
var $isphp8 = false;
var $extendedcolors = ['aliceblue'=>[240,248,255], 'antiquewhite'=>[250,235,215], 'aqua'=>[0,255,255], 'aquamarine'=>[127,255,212], 'azure'=>[240,255,255], 'beige'=>[245,245,220], 'bisque'=>[255,228,196], 'black'=>[0,0,0], 'blanchedalmond'=>[255,235,205], 'blue'=>[0,0,255], 'blueviolet'=>[138,43,226], 'brown'=>[165,42,42], 'burlywood'=>[222,184,135], 'cadetblue'=>[95,158,160], 'chartreuse'=>[127,255,0], 'chocolate'=>[210,105,30], 'coral'=>[255,127,80], 'cornflowerblue'=>[100,149,237], 'cornsilk'=>[255,248,220], 'crimson'=>[220,20,60], 'cyan'=>[0,255,255], 'darkblue'=>[0,0,139], 'darkcyan'=>[0,139,139], 'darkgoldenrod'=>[184,134,11], 'darkgray'=>[169,169,169], 'darkgreen'=>[0,100,0], 'darkgrey'=>[169,169,169], 'darkkhaki'=>[189,183,107], 'darkmagenta'=>[139,0,139], 'darkolivegreen'=>[85,107,47], 'darkorange'=>[255,140,0], 'darkorchid'=>[153,50,204], 'darkred'=>[139,0,0], 'darksalmon'=>[233,150,122], 'darkseagreen'=>[143,188,143], 'darkslateblue'=>[72,61,139], 'darkslategray'=>[47,79,79], 'darkslategrey'=>[47,79,79], 'darkturquoise'=>[0,206,209], 'darkviolet'=>[148,0,211], 'deeppink'=>[255,20,147], 'deepskyblue'=>[0,191,255], 'dimgray'=>[105,105,105], 'dimgrey'=>[105,105,105], 'dodgerblue'=>[30,144,255], 'firebrick'=>[178,34,34], 'floralwhite'=>[255,250,240], 'forestgreen'=>[34,139,34], 'fuchsia'=>[255,0,255], 'gainsboro'=>[220,220,220], 'ghostwhite'=>[248,248,255], 'gold'=>[255,215,0], 'goldenrod'=>[218,165,32], 'gray'=>[128,128,128], 'green'=>[0,128,0], 'greenyellow'=>[173,255,47], 'grey'=>[128,128,128], 'honeydew'=>[240,255,240], 'hotpink'=>[255,105,180], 'indianred'=>[205,92,92], 'indigo'=>[75,0,130], 'ivory'=>[255,255,240], 'khaki'=>[240,230,140], 'lavender'=>[230,230,250], 'lavenderblush'=>[255,240,245], 'lawngreen'=>[124,252,0], 'lemonchiffon'=>[255,250,205], 'lightblue'=>[173,216,230], 'lightcoral'=>[240,128,128], 'lightcyan'=>[224,255,255], 'lightgoldenrodyellow'=>[250,250,210], 'lightgray'=>[211,211,211], 'lightgreen'=>[144,238,144], 'lightgrey'=>[211,211,211], 'lightpink'=>[255,182,193], 'lightsalmon'=>[255,160,122], 'lightseagreen'=>[32,178,170], 'lightskyblue'=>[135,206,250], 'lightslategray'=>[119,136,153], 'lightslategrey'=>[119,136,153], 'lightsteelblue'=>[176,196,222], 'lightyellow'=>[255,255,224], 'lime'=>[0,255,0], 'limegreen'=>[50,205,50], 'linen'=>[250,240,230], 'magenta'=>[255,0,255], 'maroon'=>[128,0,0], 'mediumaquamarine'=>[102,205,170], 'mediumblue'=>[0,0,205], 'mediumorchid'=>[186,85,211], 'mediumpurple'=>[147,112,219], 'mediumseagreen'=>[60,179,113], 'mediumslateblue'=>[123,104,238], 'mediumspringgreen'=>[0,250,154], 'mediumturquoise'=>[72,209,204], 'mediumvioletred'=>[199,21,133], 'midnightblue'=>[25,25,112], 'mintcream'=>[245,255,250], 'mistyrose'=>[255,228,225], 'moccasin'=>[255,228,181], 'navajowhite'=>[255,222,173], 'navy'=>[0,0,128], 'oldlace'=>[253,245,230], 'olive'=>[128,128,0], 'olivedrab'=>[107,142,35], 'orange'=>[255,165,0], 'orangered'=>[255,69,0], 'orchid'=>[218,112,214], 'palegoldenrod'=>[238,232,170], 'palegreen'=>[152,251,152], 'paleturquoise'=>[175,238,238], 'palevioletred'=>[219,112,147], 'papayawhip'=>[255,239,213], 'peachpuff'=>[255,218,185], 'peru'=>[205,133,63], 'pink'=>[255,192,203], 'plum'=>[221,160,221], 'powderblue'=>[176,224,230], 'purple'=>[128,0,128], 'red'=>[255,0,0], 'rosybrown'=>[188,143,143], 'royalblue'=>[65,105,225], 'saddlebrown'=>[139,69,19], 'salmon'=>[250,128,114], 'sandybrown'=>[244,164,96], 'seagreen'=>[46,139,87], 'seashell'=>[255,245,238], 'sienna'=>[160,82,45], 'silver'=>[192,192,192], 'skyblue'=>[135,206,235], 'slateblue'=>[106,90,205], 'slategray'=>[112,128,144], 'slategrey'=>[112,128,144], 'snow'=>[255,250,250], 'springgreen'=>[0,255,127], 'steelblue'=>[70,130,180], 'tan'=>[210,180,140], 'teal'=>[0,128,128], 'thistle'=>[216,191,216], 'tomato'=>[255,99,71], 'turquoise'=>[64,224,208], 'violet'=>[238,130,238], 'wheat'=>[245,222,179], 'white'=>[255,255,255], 'whitesmoke'=>[245,245,245], 'yellow'=>[255,255,0], 'yellowgreen'=>[154,205,50]];

var $AScom;

function __construct() {
	$this->usegd2 = function_exists('imagesetthickness');
	$this->usettf = function_exists('imagettftext');
    $this->isphp8 = (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 80000);
}

function AStoIMG($w=200, $h=200) {
	$this->xmin = -5; $this->xmax = 5; $this->ymin = -5; $this->ymax = 5; $this->border = array(0,0,0,0);
	$this->stroke = 'black'; $this->fill = 'none'; $this->curdash=''; $this->isdashed=false; $this->marker='none';
	$this->markerfill = 'yellow'; $this->markerstroke = 'black';  $this->gridcolor = 'gray'; $this->axescolor = 'black';
	$this->strokewidth = 1; $this->dotradius=8; $this->ticklength=4;
	$this->fontsize = 16; $this->fontfill='black'; $this->fontbackground='';
	$this->isinit = false;
	$this->colors = array();

	if ($w<=0) {$w=200;}
	if ($h<=0) {$h=200;}
	$w = (int) round($w);
	$h = (int) round($h);
	
	if ($this->usegd2) {
		$this->img = imagecreatetruecolor($w,$h);
		$this->colors['transblue'] = imagecolorallocatealpha($this->img, 0,0,255,90);
		$this->colors['transgreen'] = imagecolorallocatealpha($this->img, 0,128,0,90);
		$this->colors['transred'] = imagecolorallocatealpha($this->img, 255,0,0,90);
		$this->colors['transwhite'] = imagecolorallocatealpha($this->img, 255,255,255,90);
		$this->colors['transorange'] = imagecolorallocatealpha($this->img, 255,165,0,90);
		$this->colors['transyellow'] = imagecolorallocatealpha($this->img, 255,255,0,90);
		$this->colors['transpurple'] = imagecolorallocatealpha($this->img, 128,0,128,90);
	} else {
		$this->img = imagecreate($w,$h);
	}
	$this->fontfile =  __DIR__.'/FreeSerifItalic.ttf';
	$this->width = $w;
	$this->height = $h;
	$this->xunitlength = $w/10;
	$this->yunitlength = $h/10;
	$this->origin = array(round($w/2),round($h/2));
	$this->colors['white'] = imagecolorallocate($this->img, 255,255,255);
	$this->colors['black'] = imagecolorallocate($this->img, 0,0,0);
	$this->colors['gray'] = imagecolorallocate($this->img, 200,200,200);
	$this->colors['red'] = imagecolorallocate($this->img, 255,0,0);
	$this->colors['orange']= imagecolorallocate($this->img, 255,165,0);
	$this->colors['yellow'] = imagecolorallocate($this->img, 255,255,0);
	$this->colors['gold'] = imagecolorallocate($this->img, 255,215,0);
	$this->colors['green'] = imagecolorallocate($this->img, 0,155,0);
	$this->colors['blue'] = imagecolorallocate($this->img, 0,0,255);
	$this->colors['cyan'] = imagecolorallocate($this->img, 0,255,255);
	$this->colors['purple'] = imagecolorallocate($this->img, 128,0,128);
    $this->colors['brown'] = imagecolorallocate($this->img, 165, 42, 42);
	imagefill($this->img,0,0,$this->colors['white']);
}

function processShortScript($script) {
	//$xmin = -5; $xmax = 5; $ymin = -5; $ymax = 5; $border = 5;
	//$stroke = 'black'; $fill = 'none'; $curdash=''; $isdashed=false; $marker='none';
        //$markerfill = 'green'; $gridcolor = 'gray'; $axescolor = 'black';
	//$strokewidth = 1; $dotradius=8; $ticklength=4; $fontsize = 12;

	$sa = explode(',',$script);
	if (count($sa)>10) {
		$this->AStoIMG($sa[9],$sa[10]);
		$this->border = array(5,5,5,5);
		$this->ASinitPicture(array_slice($sa,0,4));//$sa[0] .','. $sa[1] .','. $sa[2] .','. $sa[3]);
		$this->ASaxes(array_slice($sa,4,5));//$sa[4] .','. $sa[5] .','. $sa[6] .','. $sa[7] .','. $sa[8]);
		$inx = 11;
		while (count($sa) > $inx+9) {
			if ($sa[$inx+7] != 'none' && !isset($this->colors[$sa[$inx+7]])) {
				$this->addcolor($sa[$inx+7]);
			}
			$this->stroke = $sa[$inx+7];
			$this->strokewidth = $sa[$inx+8];
			if (!is_numeric($this->strokewidth)) {
				$this->strokewidth = 1;
			}
			if ($this->usegd2) {
				imagesetthickness($this->img,(int) round($this->strokewidth > 0.5 ? $this->strokewidth : 1));
			}
			if ($sa[$inx+9] != "") {
				$this->ASsetdash($sa[$inx+9]);
			} else {
				$this->ASsetdash('none');
			}
			if ($sa[$inx]=='slope') {
				$this->ASslopefield(array($sa[$inx+1],$sa[$inx+2],$sa[$inx+2]));
			} else if ($sa[$inx]=='label') {
				$sa[$inx+1] = str_replace(['&amp;x44;', '&x44;', '&amp;quot;', '&quot;'], [',', ',', '"', '"'], $sa[$inx+1]);
				$origfontfill = $this->fontfill;
				$this->fontfill = $sa[$inx+7];
				$this->AStext(array('['.$sa[$inx+5].','.$sa[$inx+6].']',$sa[$inx+1]));
				$this->fontfill = $origfontfill;
			} else {
				if ($sa[$inx]=='func') {
					$eqn = $sa[$inx+1];
				} else if ($sa[$inx]=='polar') {
					$eqn = '[cos(t)*('.$sa[$inx+1].'),sin(t)*('.$sa[$inx+1].')]';
				} else if ($sa[$inx]=='param') {
					$eqn = '['.$sa[$inx+1].','.$sa[$inx+2].']';
				}
				if (is_numeric($sa[$inx+5])) {
					$this->ASplot(array($eqn,$sa[$inx+5],$sa[$inx+6],null,null,$sa[$inx+3],$sa[$inx+4]));
				} else {
					$this->ASplot(array($eqn,null,null,null,null,$sa[$inx+3],$sa[$inx+4]));
				}
			}
			$inx += 10;
		}
	}
}

function commasToSemicolons($input) {
    $result = '';
    $depth = 0;          // parenthesis/bracket nesting depth
    $inString = false;
    $stringChar = '';    // which quote started the string (' or ")
    $len = strlen($input);

    for ($i = 0; $i < $len; $i++) {
        $char = $input[$i];

        if ($inString) {
            $result .= $char;
            // Handle escaped characters (e.g. \' or \")
            if ($char === '\\') {
                // Consume the next character as-is
                if ($i + 1 < $len) {
                    $result .= $input[++$i];
                }
            } elseif ($char === $stringChar) {
                $inString = false;
            }
        } else {
            if ($char === '"' || $char === "'") {
                $inString = true;
                $stringChar = $char;
                $result .= $char;
            } elseif ($char === '(' || $char === '[' || $char === '{') {
                $depth++;
                $result .= $char;
            } elseif ($char === ')' || $char === ']' || $char === '}') {
                $depth--;
                $result .= $char;
            } elseif ($char === ',' && $depth === 0) {
                $result .= ';';  // <-- the key substitution
            } else {
                $result .= $char;
            }
        }
    }

    return $result;
}

// splits a script into commands on top-level commas and semicolons,
// leaving those inside strings or brackets alone
function splitCommands($input) {
	$out = array();
	$cur = '';
	$depth = 0;
	$inString = false;
	$stringChar = '';
	$len = strlen($input);
	for ($i = 0; $i < $len; $i++) {
		$char = $input[$i];
		if ($inString) {
			$cur .= $char;
			if ($char === '\\') {
				if ($i + 1 < $len) {
					$cur .= $input[++$i];
				}
			} elseif ($char === $stringChar) {
				$inString = false;
			}
		} else if ($char === '"' || $char === "'") {
			$inString = true;
			$stringChar = $char;
			$cur .= $char;
		} else if ($char === '(' || $char === '[' || $char === '{') {
			$depth++;
			$cur .= $char;
		} else if ($char === ')' || $char === ']' || $char === '}') {
			$depth--;
			$cur .= $char;
		} else if (($char === ',' || $char === ';') && $depth <= 0) {
			$out[] = $cur;
			$cur = '';
		} else {
			$cur .= $char;
		}
	}
	$out[] = $cur;
	return $out;
}

// splits on $sep at the top level, ignoring separators inside (), [], or quotes
function splitTop($str, $sep=',') {
	$out = array();
	$cur = '';
	$depth = 0;
	$q = '';
	$len = strlen($str);
	for ($i = 0; $i < $len; $i++) {
		$c = $str[$i];
		if ($q !== '') {
			if ($c === $q) { $q = ''; }
		} else if ($c === '"' || $c === "'") {
			$q = $c;
		} else if ($c === '(' || $c === '[') {
			$depth++;
		} else if ($c === ')' || $c === ']') {
			$depth--;
		} else if ($c === $sep && $depth <= 0) {
			$out[] = $cur;
			$cur = '';
			continue;
		}
		$cur .= $c;
	}
	$out[] = $cur;
	return $out;
}

function processScript($script) {
	//$xmin = -5; $xmax = 5; $ymin = -5; $ymax = 5; $border = 5;
	//$stroke = 'black'; $fill = 'none'; $curdash=''; $isdashed=false; $marker='none';
        //$markerfill = 'green'; $gridcolor = 'gray'; $axescolor = 'black';
	//$strokewidth = 1; $dotradius=8; $ticklength=4; $fontfill = ''; $fontsize = 12;
	//$script = preg_replace('/&[^\s]*;
	$script = html_entity_decode($script, ENT_COMPAT, 'UTF-8');
	$this->AScom = $this->splitCommands($script);
	foreach ($this->AScom as $com) {
		if (preg_match('/^\s*(?:var\s+)?(\w+)\s*=(?!=)(.+)$/s',$com,$matches)) { //is assignment operator
			$matches[2] = trim(str_replace(array('"','\''),'',$matches[2]));
			switch($matches[1]) {
				case 'border':
						if ($matches[2] !== '' && $matches[2][0] == '[') {
							$this->border = array_map(array($this,'evalifneeded'), $this->splitTop(trim($matches[2],'[]')));
						} else {
							$this->border = $this->evalifneeded($matches[2]);
						}
						break;
					case 'xmin':
					case 'xmax':
					case 'ymin':
					case 'ymax':
					case 'arrowrelsize':
					case 'arrowoffset':
						$this->{$matches[1]} = $this->evalifneeded($matches[2]);
						break;
					case 'marker':
						$this->marker = $matches[2];
						break;
					case 'fontsize':
						$this->fontsize = $this->evalifneeded($matches[2]);
						break;
					case 'dotradius': //JS value is a radius, ours is a diameter
						$this->dotradius = 2*$this->evalifneeded($matches[2]);
						break;
					case 'axesstroke':
					case 'gridstroke':
						if ($matches[2] != 'none' && !isset($this->colors[$matches[2]])) {
							$this->addcolor($matches[2]);
						}
						$this->{$matches[1]=='axesstroke'?'axescolor':'gridcolor'} = $matches[2];
						break;
					case 'fill':
					case 'markerfill':
					case 'markerstroke':
				case 'fontbackground':
				case 'fontfill':
					if ($matches[2] != 'none' && !isset($this->colors[$matches[2]])) {
						$this->addcolor($matches[2]);
					}
                    $this->{$matches[1]} = $matches[2];
					break;
				case 'stroke':
					if ($matches[2] != 'none' && !isset($this->colors[$matches[2]])) {
						$this->addcolor($matches[2]);
					}
                    $this->stroke = $matches[2];
					if ($this->isdashed) {
						$this->ASsetdash();
					}
					break;
				case 'strokedasharray':
					$this->ASsetdash($matches[2]);
					break;
				case 'strokewidth':
					$matches[2] = $this->evalifneeded($matches[2]);
					$this->strokewidth = (int) round($matches[2] > 0.5 ? $matches[2] : 1);
					if ($this->usegd2) {
							imagesetthickness($this->img,$this->strokewidth);
						}
						if ($this->isdashed) {
							$this->ASsetdash();
						}
						break;
			}
		}
		if (preg_match('/^\s*(\w+)\s*\((.*)\)\s*$/s',$com,$matches)) { //is function
			$argarr = $this->parseargs($matches[2]);
			switch($matches[1]) {
				case 'initPicture':
					$this->ASinitPicture($argarr);
					break;
				case 'setBorder':
					//(left,bottom,right,top)
					if (count($argarr) < 4) {
						$argarr = [$argarr[0],$argarr[0],$argarr[0],$argarr[0]];
					}
					$this->border = $argarr;
					break;
				case 'axes':
					$this->ASaxes($argarr);
					break;
				case 'line':
					$this->ASline($argarr);
					break;
				case 'path':
					$this->ASpath($argarr);
					break;
				case 'circle':
					$this->AScircle($argarr);
					break;
				case 'ellipse':
					$this->ASellipse($argarr);
					break;
				case 'rect':
					$this->ASrect($argarr);
					break;
				case 'text':
					$this->AStext($argarr);
					break;
				case 'textfrac':
					$this->AStextfrac($argarr);
					break;
				case 'textabs':
					$this->AStextAbs($argarr);
					break;
				case 'dot':
					$this->ASdot2($argarr);
					break;
				case 'plot':
					$this->ASplot($argarr);
					break;
				case 'slopefield':
					$this->ASslopefield($argarr);
					break;
				case 'arc':
					$this->ASarc($argarr);
					break;
				case 'sector':
						$this->ASsector($argarr);
						break;
					case 'curve':
						$this->AScurve($argarr);
						break;
					case 'loop':
						$this->ASloop($argarr);
						break;
					case 'grid':
						$this->ASgrid($argarr);
						break;
					case 'noaxes':
						if (!$this->isinit) {$this->ASinitPicture();}
						break;
					case 'setBackgroundColor':
						$this->ASsetBackgroundColor($argarr);
						break;
				case 'arrowhead':
					$this->ASarrowhead($argarr[0],$argarr[1]);
					break;
			}
		}
	}
}

function addcolor(&$origcolor) {
	$color = $origcolor;
	if (substr($color,0,5)=='trans') {
		$alpha = 90;
		$color = substr($color,5);
	} else {
		$alpha = 0;
	}
	if (strlen($color)>0 && $color[0]=='#') {
		if (strlen($color)==4 || strlen($color)==5) { //#rgb or #rgba shorthand
			$color = '#'.preg_replace('/(.)/', '$1$1', substr($color,1));
		}
		$r = hexdec(substr($color,1,2));
		$g = hexdec(substr($color,3,2));
		$b = hexdec(substr($color,5,2));
		if (strlen($color)==9) {
			$alpha = 127 - floor(hexdec(substr($color,7,2))/2);
		}
		$this->colors[$origcolor] = imagecolorallocatealpha($this->img, $r, $g, $b, $alpha);
	} else if (preg_match('/^rgba?\((.*?)\)$/', $color, $m)) {
		$parts = array_map('floatval', array_map('trim', explode(',', $m[1])));
		if (count($parts) === 3) {
			$this->colors[$origcolor] = imagecolorallocatealpha($this->img, $parts[0], $parts[1], $parts[2], 0);
		} else if (count($parts) === 4) {
			$this->colors[$origcolor] = imagecolorallocatealpha($this->img, $parts[0], $parts[1], $parts[2], 127*(1-$parts[3]));
		} else {
			$origcolor = 'black';
		}
	} else if (isset($this->extendedcolors[strtolower($color)])) {
        $cd = $this->extendedcolors[strtolower($color)];
        $this->colors[$origcolor] = imagecolorallocatealpha($this->img, $cd[0], $cd[1], $cd[2], $alpha);
    } else {
        $origcolor = 'black';
    }
}
function ASsetdash() {
	if (!$this->isinit) {$this->ASinitPicture();}
	if (func_num_args()>0) {
		$dash = func_get_arg(0);
		$this->curdash = $dash;
	} else {
		$dash = $this->curdash;
	}
	if ($dash=='none' || !preg_match('/\d/',$dash) || $this->stroke=='none') {
		$this->isdashed = false;
	} else {
		$dash = preg_replace('/\s+/',',',$dash);
		$darr = explode(',',$dash);
		$style = array();
		// GD advances the dash pattern once per pixel of line thickness, so scale to keep SVG-like lengths
		$thick = max(1,(int) round(is_numeric($this->strokewidth) && $this->strokewidth > 0.5 ? $this->strokewidth : 1));
		$alt = 0;
		$doagain = count($darr)%2;  //do twice if odd number
		while ($doagain>-1) {
			for ($i=0;$i<count($darr);$i++) {
				$n = max(1,(int) round(floatval($darr[$i])))*$thick;
				$color = $alt==0 ? $this->colors[$this->stroke] : IMG_COLOR_TRANSPARENT;
				$style = array_pad($style,count($style)+$n,$color);
				$alt = 1-$alt;
			}
			$doagain--;
		}
        if (!empty($style)) {
            imagesetstyle($this->img,$style);
            $this->isdashed = true;
        } else {
            $this->isdashed = false;
        }
		
	}
}
function AStext($arg) {
	if (!$this->isinit) {$this->ASinitPicture();}
	$pos = '';  $angle = 0;
	if (func_num_args()>1) {
		$p = $this->pt2arr($arg);
		$st = func_get_arg(1);
		if (func_num_args()>2) {
			$pos = func_get_arg(2);
		}
		if (func_num_args()>3) {
			$angle = floatval(func_get_arg(3));
		}
	} else {
		$p = $this->pt2arr($arg[0]);
		$st = $arg[1];
		if (isset($arg[2])) {
			$pos = $arg[2];
		}
		if (isset($arg[3])) {
			$angle = floatval($arg[3]);
		}
	}
	$this->AStextInternal($p,$st,$pos,$angle);
}

function AStextfrac($arg) {
	if (!$this->isinit) {$this->ASinitPicture();}
	$d = array_splice($arg,2,1);
	if ($d[0] != 1) {
		$arg[1] .= '/' . $d[0];
	}
	$this->AStext($arg);
}

function AStextAbs($arg) {
	if (!$this->isinit) {$this->ASinitPicture();}
	$pos = '';  $angle = 0;
	if (func_num_args()>1) {
		$pt = $arg;
		$st = func_get_arg(1);
		if (func_num_args()>2) {
			$pos = func_get_arg(2);
		}
		if (func_num_args()>3) {
			$angle = floatval(func_get_arg(3));
		}
	} else {
		$pt = $arg[0];
		$st = $arg[1];
		if (isset($arg[2])) {
			$pos = $arg[2];
		}
		if (isset($arg[3])) {
			$angle = floatval($arg[3]);
		}
	}
	$pt = $this->splitTop(trim(trim($pt),'[]'));
		$pt[0] = $this->evalifneeded($pt[0]);
		$pt[1] = $this->height - $this->evalifneeded($pt[1]);
	$this->AStextInternal($pt,$st,$pos,$angle);
}
function AStextInternal($p,$st,$pos,$angle) {
	/*if (func_num_args()>1) {
		$p = $this->pt2arr($arg);
		$st = func_get_arg(1);
		if (func_num_args()>2) {
			$pos = func_get_arg(2);
		}
		if (func_num_args()>3) {
			$angle = func_get_arg(3);
		}
	} else {
		$p = $this->pt2arr($arg[0]);
		$st = $arg[1];
		if (isset($arg[2])) {
			$pos = $arg[2];
		}
		if (isset($arg[3])) {
			$angle = $arg[3];
		}
	}*/
	/*else {
		if (preg_match('/\s*\[(.*?)\]\s*,\s*[\'"](.*?)[\'"]\s*,([^,]*)/',$arg,$m)) {
			$p = $this->pt2arr($m[1]);
			$st = $m[2];
			$pos = trim(str_replace(array('"',"'"),'',$m[3]));
		} else {
			$arg = explode(',',$arg);
			$p = $this->pt2arr($arg[0].','.$arg[1]);
			$st = str_replace(array('"',"'"),'',$arg[2]);
		}
	}*/

	$st = (string) $st;
		$st = preg_replace_callback('/<sub>(.*?)<\/sub>/i', function($m) {
			return preg_replace_callback('/\d/', function($d) { return html_entity_decode('&#'.(8320+$d[0]).';', ENT_COMPAT, 'UTF-8'); }, $m[1]);
		}, $st);
		
		$fcolor = $this->fontfill;
		if ($fcolor == '' || $fcolor == 'none' || !isset($this->colors[$fcolor])) {
			$fcolor = 'black';
		}
		$fbg = $this->fontbackground;
		if ($fbg != '' && $fbg != 'none' && !isset($this->colors[$fbg])) {
			$fbg = '';
		}
		if ($this->usettf) {
			$bb = imagettfbbox($this->fontsize*0.75,$angle,$this->fontfile,$st);

		$bbw = $bb[4]-$bb[0];
		$bbh = -1*($bb[5]-$bb[1]);

		$p[0] = $p[0] - .5*($bbw);
		$p[1] = $p[1] + .5*($bbh);

		if ($pos=='above' || $pos=='aboveright' || $pos=='aboveleft') {
			$p[1] = $p[1] - .5*(abs($bbh)) - $this->fontsize/2 - 2;
		}
		if ($pos=='below' || $pos=='belowright' || $pos=='belowleft') {
			$p[1] = $p[1] + .5*(abs($bbh)) + $this->fontsize/2 + 2;
		}
		if ($pos=='left' || $pos=='aboveleft' || $pos=='belowleft') {
			$p[0] = $p[0] - .5*(abs($bbw)) -$this->fontsize/2;
		}
		if ($pos=='right' || $pos=='aboveright' || $pos=='belowright') {
			$p[0] = $p[0] + .5*(abs($bbw)) +$this->fontsize/2;
		}
		$color = $fcolor;
		if ($fbg != '' && $fbg != 'none') {
			$minX = min(array($bb[0],$bb[2],$bb[4],$bb[6]));
			$maxX = max(array($bb[0],$bb[2],$bb[4],$bb[6]));
			$minY = min(array($bb[1],$bb[3],$bb[5],$bb[7]));
			$maxY = max(array($bb[1],$bb[3],$bb[5],$bb[7]));
			imagefilledrectangle($this->img, (int) round($p[0]+$minX-2), (int) round($p[1]+$minY-1), (int) round($p[0]+$maxX+2), (int) round($p[1]+$maxY+1), $this->colors[$fbg]);
		}
		imagettftext($this->img,$this->fontsize*0.75,$angle, (int) round($p[0]), (int) round($p[1]),$this->colors[$color],$this->fontfile,$st);
	} else {
		if ($this->fontsize<9) {
			$fs = 1;
		} else if ($this->fontsize<13) {
			$fs = 2;
		} else {
			$fs = 4;
		}
		if ($angle==90 || $angle==270) {
			$bb = array(imagefontheight($fs),imagefontwidth($fs)*strlen($st));
		} else {
			$bb = array(imagefontwidth($fs)*strlen($st),imagefontheight($fs));
		}
		$p[0] = $p[0] - .5*$bb[0];
		if ($angle==90 || $angle==270) {
			$p[1] = $p[1] + .5*$bb[1];
		} else {
			$p[1] = $p[1] - .5*$bb[1];
		}
		if ($pos=='above' || $pos=='aboveright' || $pos=='aboveleft') {
			$p[1] = $p[1] - .5*$bb[1] - $fs*2;
		}
		if ($pos=='below' || $pos=='belowright' || $pos=='belowleft') {
			$p[1] = $p[1] + .5*$bb[1] + $fs*2;
		}
		if ($pos=='left' || $pos=='aboveleft' || $pos=='belowleft') {
			$p[0] = $p[0] - .5*$bb[0] - $fs*2;
		}
		if ($pos=='right' || $pos=='aboveright' || $pos=='belowright') {
			$p[0] = $p[0] + .5*$bb[0] + $fs*2;
		}
		$color = $fcolor;
		if ($fbg != '' && $fbg != 'none') {
			imagefilledrectangle($this->img, (int) round($p[0]-2), (int) round($p[1]-2), (int) round($p[0]+$bb[0]+2), (int) round($p[1]+$bb[1]+2), $this->colors[$fbg]);
		}
		if ($angle==90 || $angle==270) {
			imagestringup($this->img,$fs,(int) round($p[0]), (int) round($p[1]),$st,$this->colors[$color]);
		} else {
			imagestring($this->img,$fs,(int) round($p[0]), (int) round($p[1]),$st,$this->colors[$color]);
		}
	}
}

function ASinitPicture($arg=array()) {
		for ($i=0;$i<4;$i++) {
			if (isset($arg[$i]) && (trim($arg[$i])==='' || trim($arg[$i])==='null')) {
				$arg[$i] = null;
			}
		}


	//$arg = explode(',',$arg);
	if (isset($arg[0]) && $arg[0]!='') { $this->xmin = $this->evalifneeded($arg[0]);}
	if (isset($arg[1])) { $this->xmax = $this->evalifneeded($arg[1]);}
	if (isset($arg[2])) { $this->ymin = $this->evalifneeded($arg[2]);}
	if (isset($arg[3])) { $this->ymax = $this->evalifneeded($arg[3]);}

	if ($this->xmin == $this->xmax) {
		$this->xmax = $this->xmin + .000001;
	}
	if (!is_array($this->border)) {
		$this->border = array($this->border,$this->border,$this->border,$this->border);
	} else if (count($this->border)<4) {
		for ($i=count($this->border);$i<5;$i++) {
			if ($i==1) {
				$this->border[$i] = $this->border[0];
			} else {
				$this->border[$i] = $this->border[$i-2];
			}
		}
	}
	$this->xunitlength = ($this->width - $this->border[0] - $this->border[2])/($this->xmax - $this->xmin);
	if ($this->xunitlength<=0) {
		$this->xunitlength = 1;
	}
	$this->yunitlength = $this->xunitlength;
	if (!isset($arg[2])) { // no ymin
		$this->origin[0] = -$this->xmin*$this->xunitlength + $this->border[0];
		$this->origin[1] = $this->height / 2;
		$this->ymin = -($this->height - $this->border[1] - $this->border[3])/(2*$this->yunitlength);
		$this->ymax = -$this->ymin;
	} else {
		if (isset($arg[3])) {  // do have ymax too
			if ($this->ymax == $this->ymin) {
				$this->yunitlength = 1;
			} else {
				$this->yunitlength = ($this->height - $this->border[1] - $this->border[3])/($this->ymax - $this->ymin);
			}
			if ($this->yunitlength<=0) {
				$this->yunitlength = 1;
			}
		} else {
			$this->ymax = ($this->height - $this->border[1] - $this->border[3])/($this->yunitlength) + $this->ymin;
		}
		$this->origin[0] = -$this->xmin*$this->xunitlength + $this->border[0];
		$this->origin[1] = -$this->ymin*$this->yunitlength + $this->border[1];
	}

	$this->winxmin = max($this->border[0] - 5,0);
	$this->winxmax = min($this->width - $this->border[2] + 5, $this->width);
	$this->winymin = max($this->border[3] -5,0);
	$this->winymax = min($this->height - $this->border[1] + 5 , $this->height);

	$this->isinit = true;
}
function ASaxes($arg) {
	if (!$this->isinit) {$this->ASinitPicture();}
	//$arg = explode(',',$arg);
	$xscl = 0; $yscl = 0; $xgrid = 0; $ygrid = 0; $dolabels = false; $dogrid = false; $dosmallticks = false;
	$fqonlyx = false; $fqonlyy = false;
	$dox = true;
	$doy = true;
	if (!is_bool($arg[0])) {
		$xscl = $this->evalifneeded($arg[0]);
	} else {
		$dolabels = true;
	}
	if (count($arg)>1) {
		if (!is_bool($arg[1])) {
			$yscl = $this->evalifneeded($arg[1]);
		} else {
			$dogrid = true;
		}
	}
	if (count($arg)>2) {
		if ($arg[2]=='0' || $arg[2]=='null' || $arg[2]=='"null"') {
			$dolabels = false;
		} else {
			$dolabels = true;
		}
	}
	if (count($arg)>3) {
		if ($arg[3]=='0' || $arg[3]=='null' || $arg[3]=='"null"') {
			$dogrid = false;
		} else {
			$xgrid = $this->evalifneeded($arg[3]);
			$dogrid = true;
		}
	}
	if (count($arg)>4) {
		$ygrid = $this->evalifneeded($arg[4]);
	}
	if (count($arg)>5) {
		if ($arg[5]==='fq') {
			$fqonlyx = true;
		} else if ($arg[5]=='off' || $arg[5]=='0') {
			$dox = false;
		}
	}
	if (count($arg)>6) {
		if ($arg[6]==='fq') {
			$fqonlyy = true;
		} else if ($arg[6]=='off' || $arg[6]=='0') {
			$doy = false;
		}
	}
	if (count($arg)>7) {
		if ($arg[7]=='1' && $dogrid==true) {
			$dogrid = false;
			$dosmallticks = true;
		}
	}
	/*if ($xscl<0) {
		$xscl *= -1;
	}
	if ($yscl<0) {
		$yscl *= -1;
	}*/
	if ($xgrid<0) {
			$xgrid *= -1;
		}
	if ($ygrid<0) {
		$ygrid *= -1;
	}
	if ($xscl==0) {
		$xscl = $this->xunitlength;
	} else {
		$xscl *= $this->xunitlength;
	}
	if ($yscl==0) {
		$yscl = $this->yunitlength;
	} else {
		$yscl *= $this->yunitlength;
	}
	if (!$doy) {
		$this->fontsize = floor(min(abs($xscl)/1.5,16));
		} else if (!$dox) {
			$this->fontsize = floor(min(abs($yscl)/1.5,16));
		} else {
			$this->fontsize = floor(min(abs($xscl)/1.5,abs($yscl)/1.5,16));
		}
		$this->ticklength = $this->fontsize/4;
	if ($this->usegd2) {
		imagesetthickness($this->img,1);
	}
	if ($xgrid==0) {
		$xgrid = $this->xunitlength;
	} else {
		$xgrid *= $this->xunitlength;
	}
	if ($ygrid==0) {
		$ygrid = $this->yunitlength;
	} else {
		$ygrid *= $this->yunitlength;
	}
	if (($this->winxmax - $this->winxmin)/$xgrid > $this->width) {
		$xgrid = ($this->winxmax - $this->winxmin);
	}
	if (($this->winymax - $this->winymin)/$ygrid > $this->height) {
		$ygrid = ($this->winymax - $this->winymin);
	}
	if (($this->winxmax - $this->winxmin)/$xscl > $this->width) {
		$xscl = ($this->winxmax - $this->winxmin);
	}
	if (($this->winymax - $this->winymin)/$yscl > $this->height) {
		$yscl = ($this->winymax - $this->winymin);
	}
	if ($dogrid) {
			$gc = $this->gridcolor;
			if ($dox && $xgrid>0) {
			for ($x=$this->origin[0]+($doy?$xgrid:0); $x<=$this->winxmax; $x += $xgrid) {
				if ($x>=$this->winxmin) {
					imageline($this->img, (int) round($x), (int) round($this->winymin), (int) round($x), (int) round(($fqonlyy?$this->height-$this->origin[1]:$this->winymax)), $this->colors[$gc]);
				}
			}
			if (!$fqonlyx) {
				for ($x=$this->origin[0]-$xgrid; $x>=$this->winxmin; $x -= $xgrid) {
					if ($x<=$this->winxmax) {
						imageline($this->img, (int) round($x), (int) round($this->winymin), (int) round($x), (int) round(($fqonlyy?$this->height-$this->origin[1]:$this->winymax)), $this->colors[$gc]);
					}
				}
			}
		}
		if ($doy && $ygrid>0) {
				if (!$fqonlyy) {
				for ($y=$this->height - $this->origin[1]+($dox?$ygrid:0); $y<=$this->winymax; $y += $ygrid) {
					if ($y>=$this->winymin) {
						imageline($this->img, (int) round(($fqonlyx?$this->origin[0]:$this->winxmin)), (int) round($y), (int) round($this->winxmax), (int) round($y), $this->colors[$gc]);
					}
				}
			}
			for ($y=$this->height - $this->origin[1]-$ygrid; $y>$this->winymin; $y -= $ygrid) {
				if ($y<=$this->winymax) {
					imageline($this->img, (int) round(($fqonlyx?$this->origin[0]:$this->winxmin)), (int) round($y), (int) round($this->winxmax), (int) round($y), $this->colors[$gc]);
				}
			}
		}
	} else if ($dosmallticks) {
		$ac = $this->axescolor;

		if ($dox && $xgrid>0) {
			for ($x=$this->origin[0]+($doy?$xgrid:0); $x<=$this->winxmax; $x += $xgrid) {
				if ($x>=$this->winxmin) {
					imageline($this->img, (int) round($x), (int) round($this->height-$this->origin[1]-.5*$this->ticklength), (int) round($x), (int) round($this->height-$this->origin[1]+.5*$this->ticklength), $this->colors[$ac]);
				}
			}
			for ($x=$this->origin[0]-$xgrid; $x>=$this->winxmin; $x -= $xgrid) {
				if ($x<=$this->winxmax) {
					imageline($this->img, (int) round($x), (int) round($this->height-$this->origin[1]-.5*$this->ticklength), (int) round($x), (int) round($this->height-$this->origin[1]+.5*$this->ticklength), $this->colors[$ac]);
				}
			}
		}
		if ($doy && $ygrid>0) {
			for ($y=$this->height - $this->origin[1]+($dox?$ygrid:0); $y<=$this->winymax; $y += $ygrid) {
				if ($y>=$this->winymin) {
					imageline($this->img, (int) round($this->origin[0]-.5*$this->ticklength), (int) round($y), (int) round($this->origin[0]+.5*$this->ticklength), (int) round($y), $this->colors[$ac]);
				}
			}
			for ($y=$this->height - $this->origin[1]-$ygrid; $y>$this->winymin; $y -= $ygrid) {
				if ($y<=$this->winymax) {
					imageline($this->img, (int) round($this->origin[0]-.5*$this->ticklength), (int) round($y), (int) round($this->origin[0]+.5*$this->ticklength), (int) round($y), $this->colors[$ac]);
				}
			}
		}
	}

	$ac = $this->axescolor;
	if ($doy) {
		if ($this->origin[0]>=$this->winxmin && $this->origin[0]<=$this->winxmax) {
			imageline($this->img, (int) round($this->origin[0]), (int) round($this->winymin), (int) round($this->origin[0]), (int) round(($fqonlyy?$this->height-$this->origin[1]:$this->winymax)), $this->colors[$ac]);
			if ($yscl>0) {
				//ticks
				if (!$fqonlyy) {
					for ($y=$this->height - $this->origin[1]; $y<=$this->winymax; $y += $yscl) {
						if ($y>=$this->winymin) {
							imageline($this->img, (int) round($this->origin[0]-$this->ticklength), (int) round($y), (int) round($this->origin[0]+$this->ticklength), (int) round($y), $this->colors[$ac]);
						}
					}
				}
				for ($y=$this->height - $this->origin[1]-$yscl; $y>=$this->winymin; $y -= $yscl) {
					if ($y<=$this->winymax) {
						imageline($this->img, (int) round($this->origin[0]-$this->ticklength), (int) round($y), (int) round($this->origin[0]+$this->ticklength), (int) round($y), $this->colors[$ac]);
					}
				}
			}
		}
	}
	if ($dox) {
		if ($this->origin[1]>=$this->winymin && $this->origin[1]<=$this->winymax) {
			imageline($this->img, (int) round(($fqonlyx?$this->origin[0]:$this->winxmin)), (int) round($this->height-$this->origin[1]), (int) round($this->winxmax), (int) round($this->height-$this->origin[1]), $this->colors[$ac]);
			if ($xscl>0) {
				//ticks
				for ($x=$this->origin[0]; $x<=$this->winxmax; $x += $xscl) {
					if ($x>=$this->winxmin) {
						imageline($this->img, (int) round($x), (int) round($this->height- $this->origin[1] -$this->ticklength), (int) round($x), (int) round($this->height- $this->origin[1] +$this->ticklength), $this->colors[$ac]);
					}
				}
				if (!$fqonlyx) {
					for ($x=$this->origin[0]-$xscl; $x>=$this->winxmin; $x -= $xscl) {
						if ($x<=$this->winxmax) {
							imageline($this->img, (int) round($x), (int) round($this->height-$this->origin[1]-$this->ticklength), (int) round($x), (int) round($this->height-$this->origin[1]+$this->ticklength), $this->colors[$ac]);
						}
					}
				}
			}
		}
	}

	if ($dolabels) {
		$ldx = $xscl/$this->xunitlength;
		$ldy = $yscl/$this->yunitlength;
			$ddx = $ldx>0 ? max(0,floor(1-log10($ldx))+1) : 0;
			$ddy = $ldy>0 ? max(0,floor(1-log10($ldy))+1) : 0;
		if ($this->xmin>0 || $this->xmax<0) {
			$lx = $this->xmin;
			$lyp = 'right';
		} else {
			$lx = 0;
			$lyp = 'left';
		}
		if ($this->ymin>0 || $this->ymax<0) {
			$ly = $this->ymin;
			$lxp = 'above';
		} else {
			$ly = 0;
			$lxp = 'below';
		}

		$backupstroke = $this->stroke;
		$this->stroke = 'black';
		if ($dox && $ldx>0) {
			for ($x=($doy?$ldx:0);$x<=$this->xmax; $x += $ldx) {
				if ($x>=$this->xmin) {
					$this->AStext("[$x,$ly]",$this->rounddec($x,$ddx),$lxp);
				}
			}
			if (!$fqonlyx) {
				for ($x=-$ldx;$this->xmin<=$x; $x -= $ldx) {
					if ($x<=$this->xmax) {
						$this->AStext("[$x,$ly]",$this->rounddec($x,$ddx),$lxp);
					}
				}
			}
		}
		if ($doy && $ldy>0) {
			for ($y=($dox?$ldy:0);$y<=$this->ymax; $y += $ldy) {
				if ($y>=$this->ymin) {
					$this->AStext("[$lx,$y]",$this->rounddec($y,$ddy),$lyp);
				}
			}
			if (!$fqonlyy) {
				for ($y=-$ldy;$this->ymin<=$y; $y -= $ldy) {
					if ($y<=$this->ymax) {
						$this->AStext("[$lx,$y]",$this->rounddec($y,$ddy),$lyp);
					}
				}
			}
		}
		$this->stroke = $backupstroke;
	}
	if ($this->usegd2) {
		imagesetthickness($this->img, (int) round($this->strokewidth > 0.5 ? $this->strokewidth : 1));
	}
}

function ptpx($x,$y) {
	return array($x*$this->xunitlength + $this->origin[0], $this->height - $y*$this->yunitlength - $this->origin[1]);
}
// $mode: 'full' draws markers, 'dot' draws only dot markers, 'none' draws no markers
function ASline($arg, $mode='full') {
	if (!$this->isinit) {$this->ASinitPicture();}
	if (count($arg)<2) { return;}
	$this->ASlinePx($this->pt2arr($arg[0]), $this->pt2arr($arg[1]), $mode);
}
function ASlinePx($p, $q, $mode='full') {
	if ($this->stroke != 'none' && isset($this->colors[$this->stroke])) {
		if ($this->isdashed) {
			imageline($this->img, (int) round($p[0]), (int) round($p[1]), (int) round($q[0]), (int) round($q[1]), IMG_COLOR_STYLED);
		} else {
			imageline($this->img, (int) round($p[0]), (int) round($p[1]), (int) round($q[0]), (int) round($q[1]), $this->colors[$this->stroke]);
		}
	}
	if ($mode == 'none') { return; }
	if ($this->marker=='dot' || $this->marker=='arrowdot') {
		$this->ASdot($p,8);
	}
	if ($mode == 'full' && ($this->marker=='arrow' || $this->marker=='arrowdot')) {
		$this->ASarrowheadPx($p,$q);
	}
	if ($this->marker=='dot' || $this->marker=='arrowdot') {
		$this->ASdot($q,8);
	}
}
// parses "[[x1,y1],[x2,y2],...]" into an array of numeric [x,y] pairs in user units
function parsePointList($str) {
	$str = trim($str);
	if ($str !== '' && $str[0] == '[') { $str = substr($str,1); }
	if ($str !== '' && substr($str,-1) == ']') { $str = substr($str,0,-1); }
	$pts = array();
	foreach ($this->splitTop($str) as $ptstr) {
		$xy = $this->splitTop(trim(trim($ptstr),'[]'));
		if (count($xy) < 2) { continue; }
		$pts[] = array($this->evalifneeded($xy[0]), $this->evalifneeded($xy[1]));
	}
	return $pts;
}
// draws a polyline (filled if fill is set) through points given in user units.
// $dotpts: points to put dot markers on, if any (defaults to all)
function ASdrawpoly($upts, $dotpts=null) {
	$px = array();
	foreach ($upts as $u) {
		$px[] = $this->ptpx($u[0],$u[1]);
	}
	if ($this->fill != 'none' && count($px)>2 && isset($this->colors[$this->fill])) {
		$flat = array();
		foreach ($px as $p) {
			$flat[] = (int) round($p[0]);
			$flat[] = (int) round($p[1]);
		}
		if ($this->isphp8) {
			imagefilledpolygon($this->img,$flat,$this->colors[$this->fill]);
		} else {
			imagefilledpolygon($this->img,$flat,count($flat)/2,$this->colors[$this->fill]);
		}
	}
	for ($i=0; $i<count($px)-1; $i++) {
		$this->ASlinePx($px[$i],$px[$i+1],'none');
	}
	if ($this->marker=='dot' || $this->marker=='arrowdot') {
		if ($dotpts === null) { $dotpts = $upts; }
		foreach ($dotpts as $u) {
			$this->ASdot($this->ptpx($u[0],$u[1]),8);
		}
	}
}
function ASpath($arg) {
	if (!$this->isinit) {$this->ASinitPicture();}
	$pts = $this->parsePointList($arg[0]);
	if (count($pts)<2) { return;}
	$this->ASdrawpoly($pts);
}
// smooth curve through points, like SVG "T" path commands
function AScurve($arg) {
	if (!$this->isinit) {$this->ASinitPicture();}
	$pts = $this->parsePointList($arg[0]);
	if (count($pts)<2) { return;}
	$n = 16;
	$out = array($pts[0]);
	$cur = $pts[0];
	$ctrl = $pts[0];
	for ($i=1; $i<count($pts); $i++) {
		$end = $pts[$i];
		for ($k=1; $k<=$n; $k++) {
			$t = $k/$n;
			$out[] = array((1-$t)*(1-$t)*$cur[0] + 2*(1-$t)*$t*$ctrl[0] + $t*$t*$end[0],
				(1-$t)*(1-$t)*$cur[1] + 2*(1-$t)*$t*$ctrl[1] + $t*$t*$end[1]);
		}
		$ctrl = array(2*$end[0]-$ctrl[0], 2*$end[1]-$ctrl[1]);
		$cur = $end;
	}
	$dotpts = array();
	foreach ($pts as $i=>$pt) {
		if ($i != 1 && $i != 2) { $dotpts[] = $pt; }
	}
	$this->ASdrawpoly($out, $dotpts);
}
// loop starting at point p, in direction d (default [1,0])
function ASloop($arg) {
	if (!$this->isinit) {$this->ASinitPicture();}
	$p = $this->parsePointList($arg[0]);
	if (count($p)<1) { return;}
	$p = $p[0];
	$d = array(1,0);
	if (isset($arg[1]) && $arg[1] !== '' && $arg[1] !== 'null') {
		$dd = $this->parsePointList($arg[1]);
		if (count($dd)>0) { $d = $dd[0]; }
	}
	$c1 = array($p[0]+$d[0], $p[1]+$d[1]);
	$c2 = array($p[0]-$d[1], $p[1]+$d[0]);
	$n = 24;
	$out = array($p);
	for ($k=1; $k<=$n; $k++) {
		$t = $k/$n; $u = 1-$t;
		$out[] = array($u*$u*$u*$p[0] + 3*$u*$u*$t*$c1[0] + 3*$u*$t*$t*$c2[0] + $t*$t*$t*$p[0],
			$u*$u*$u*$p[1] + 3*$u*$u*$t*$c1[1] + 3*$u*$t*$t*$c2[1] + $t*$t*$t*$p[1]);
	}
	$this->ASdrawpoly($out, array($p,$p));
	if ($this->marker=='arrow' || $this->marker=='arrowdot') {
		$a = array($p[0]+cos(1.4)*$d[0]-sin(1.4)*$d[1], $p[1]+sin(1.4)*$d[0]+cos(1.4)*$d[1]);
		$this->ASarrowheadPx($this->ptpx($a[0],$a[1]), $this->ptpx($p[0],$p[1]));
	}
}
function ASgrid($arg) {
	$dy = isset($arg[1]) ? $arg[1] : $arg[0];
	$this->ASaxes(array($arg[0], $dy, 'null', $arg[0], $dy));
}
function ASsetBackgroundColor($arg) {
	if (!$this->isinit) {$this->ASinitPicture();}
	$c = $arg[0];
	if ($c != 'none' && !isset($this->colors[$c])) {
		$this->addcolor($c);
	}
	if (isset($this->colors[$c])) {
		imagefilledrectangle($this->img, 0, 0, $this->width, $this->height, $this->colors[$c]);
	}
}
function AScircle($arg) {
	if (!$this->isinit) {$this->ASinitPicture();}
	$this->ASellipse(array($arg[0],$arg[1],$arg[1]));
}
function ASellipse($arg) {
	if (!$this->isinit) {$this->ASinitPicture();}
	$p = $this->pt2arr($arg[0]);
	$arg[1] = $this->evalifneeded($arg[1])*$this->xunitlength;
	$arg[2] = $this->evalifneeded($arg[2])*$this->yunitlength;
	if ($this->fill != 'none' && isset($this->colors[$this->fill])) {
		$color = $this->fill;
		if ($this->usegd2) {
			imagefilledellipse($this->img, (int) round($p[0]), (int) round($p[1]), (int) round($arg[1]*2), (int) round($arg[2]*2), $this->colors[$color]);
		}
	}
	if ($this->isdashed) {
		imageellipse($this->img, (int) round($p[0]), (int) round($p[1]), (int) round($arg[1]*2), (int) round($arg[2]*2), IMG_COLOR_STYLED);
	} else if ($this->stroke != 'none' && isset($this->colors[$this->stroke])) {
		imageellipse($this->img, (int) round($p[0]), (int) round($p[1]), (int) round($arg[1]*2), (int) round($arg[2]*2), $this->colors[$this->stroke]);
	}
}
function ASrect($arg) {
	if (!$this->isinit) {$this->ASinitPicture();}
	//$arg = explode(',',$arg);
	$p = $this->pt2arr($arg[0]);
	$q = $this->pt2arr($arg[1]);
	$sx = min($p[0],$q[0]); $bx = max($p[0],$q[0]);
	$sy = min($p[1],$q[1]); $by = max($p[1],$q[1]);
	if ($this->fill != 'none') {
		$color = $this->fill;
		imagefilledrectangle($this->img, (int) round($sx), (int) round($sy), (int) round($bx), (int) round($by), $this->colors[$color]);
	}

	if ($this->isdashed) {
		imagerectangle($this->img, (int) round($sx), (int) round($sy), (int) round($bx), (int) round($by), IMG_COLOR_STYLED);
	} else {
		$color = $this->stroke;
		if ($color != 'none') {
			imagerectangle($this->img, (int) round($sx), (int) round($sy), (int) round($bx), (int) round($by), $this->colors[$color]);
		}
	}
}
function ASsector($arg) {
	if (!$this->isinit) {$this->ASinitPicture();}
	list($cx,$cy) = $this->pt2arr($arg[0]);
	$r = $this->evalifneeded($arg[1]);
	$origstart = $this->evalifneeded($arg[2]);
	$origend = $this->evalifneeded($arg[3]);

	if ($origend < $origstart) {
		$startt = 2*M_PI - $origstart;
		$endt = 2*M_PI - $origend;
	} else {
		$startt = 2*M_PI - $origend;
		$endt = 2*M_PI - $origstart;
	}

	$xdiam = 2*$r*$this->xunitlength;
	$ydiam = 2*$r*$this->yunitlength;

	if ($this->fill != 'none') {
		$color = $this->fill;
		imagefilledarc($this->img, (int) round($cx), (int) round($cy), (int) round($xdiam), (int) round($ydiam), (int) round($startt*180/M_PI), (int) round($endt*180/M_PI), $this->colors[$color], IMG_ARC_PIE);
	}
	$color = $this->stroke;
	if ($this->isdashed) {
		imagefilledarc($this->img, (int) round($cx), (int) round($cy), (int) round($xdiam), (int) round($ydiam), (int) round($startt*180/M_PI), (int) round($endt*180/M_PI), IMG_COLOR_STYLED, IMG_ARC_PIE|IMG_ARC_NOFILL|IMG_ARC_EDGED);

	} else {
		if ($color != 'none') {
			imagefilledarc($this->img, (int) round($cx), (int) round($cy), (int) round($xdiam), (int) round($ydiam), (int) round($startt*180/M_PI), (int) round($endt*180/M_PI), $this->colors[$color], IMG_ARC_PIE|IMG_ARC_NOFILL|IMG_ARC_EDGED);
		}
	}

}
function ASarc($arg) {
	if (!$this->isinit) {$this->ASinitPicture();}
	$p = $this->pt2arr($arg[0]);
	$q = $this->pt2arr($arg[1]);
	$po[0] = ($p[0]-$this->origin[0])/$this->xunitlength;
	$qo[0] = ($q[0]-$this->origin[0])/$this->xunitlength;
	$po[1] = ($this->height - $p[1] - $this->origin[1])/$this->yunitlength;
	$qo[1] = ($this->height - $q[1] - $this->origin[1])/$this->yunitlength;
	$t[0] = ($po[0]+$qo[0])/2;
	$t[1] = ($po[1]+$qo[1])/2;

	$m = sqrt(($po[0]-$qo[0])*($po[0]-$qo[0]) + ($po[1]-$qo[1])*($po[1]-$qo[1]));
	if ($m < 0.000000001) { return; }
	if (!isset($arg[2]) || trim($arg[2])==='' || trim($arg[2])==='null') {
		$r = $m; //default radius is the chord length
	} else {
		$r = $this->evalifneeded($arg[2]);
	}
	if ($r*$r < $m*$m/4) { $r = $m/2; } //radius too small to span the points
	$cxo = $t[0] + sqrt($r*$r-($m*$m/4))*($po[1]-$qo[1])/$m;
	$cyo = $t[1] - sqrt($r*$r-($m*$m/4))*($po[0]-$qo[0])/$m;
	$cx = round($cxo*$this->xunitlength + $this->origin[0]);
	$cy = round($this->height - $cyo*$this->yunitlength - $this->origin[1]);

	$endt = atan2(-$po[1]+$cyo,$po[0]-$cxo);
	$startt = atan2(-$qo[1]+$cyo,$qo[0]-$cxo);
	$xdiam = 2*$r*$this->xunitlength;
	$ydiam = 2*$r*$this->yunitlength;
	if ($this->fill != 'none' && isset($this->colors[$this->fill])) {
		// fill the circular segment between the arc and its chord (GD's own chord fill isn't that)
		$a1 = ($endt < $startt) ? $endt + 2*M_PI : $endt;
		$flat = array();
		for ($k=0; $k<=48; $k++) {
			$a = $startt + ($a1-$startt)*$k/48;
			$flat[] = (int) round($cx + $xdiam/2*cos($a));
			$flat[] = (int) round($cy + $ydiam/2*sin($a));
		}
		if ($this->isphp8) {
			imagefilledpolygon($this->img,$flat,$this->colors[$this->fill]);
		} else {
			imagefilledpolygon($this->img,$flat,count($flat)/2,$this->colors[$this->fill]);
		}
	}

	if ($this->isdashed) {
		imagearc($this->img, (int) round($cx), (int) round($cy), (int) round($xdiam), (int) round($ydiam), (int) round($startt*180/M_PI), (int) round($endt*180/M_PI), IMG_COLOR_STYLED);
	} else if ($this->stroke != 'none' && isset($this->colors[$this->stroke])) {
		imagearc($this->img, (int) round($cx), (int) round($cy), (int) round($xdiam), (int) round($ydiam), (int) round($startt*180/M_PI), (int) round($endt*180/M_PI), $this->colors[$this->stroke]);
	}

	if ($this->marker=='arrow' || $this->marker=='arrowdot') {
		$u = array(($qo[1]-$po[1])/4, ($po[0]-$qo[0])/4);
		$v = array(($qo[0]-$po[0])/2, ($qo[1]-$po[1])/2);
		$v = array($po[0]+$v[0]+$u[0], $po[1]+$v[1]+$u[1]);
	} else {
		$v = $po;
	}
	if ($this->marker=='dot' || $this->marker=='arrowdot') {
		$this->ASdot($p,8);
		if ($this->marker=='arrowdot') { $this->ASarrowheadPx($this->ptpx($v[0],$v[1]),$q); }
		$this->ASdot($q,8);
	} else if ($this->marker=='arrow') {
		$this->ASarrowheadPx($this->ptpx($v[0],$v[1]),$q);
	}
}

function ASdot($pt,$r) {
	if (!$this->isinit) {$this->ASinitPicture();}
	if ($this->markerfill!='none' && isset($this->colors[$this->markerfill])) {
		$color = $this->markerfill;
		if ($this->usegd2) {
			imagefilledellipse($this->img, (int) round($pt[0]), (int) round($pt[1]), (int) round($r), (int) round($r), $this->colors[$color]);
		} else {
			imagefilledpolygon($this->img,array($pt[0]-$r,$pt[1],$pt[0],$pt[1]+$r,$pt[0]+$r,$pt[1],$pt[0],$pt[1]-$r),4,$this->colors[$color]);
		}
	}
	if ($this->markerstroke!='none' && isset($this->colors[$this->markerstroke])) {
		imageellipse($this->img, (int) round($pt[0]), (int) round($pt[1]), (int) round($r), (int) round($r), $this->colors[$this->markerstroke]);
	}
}
// draws a dot of the given type at pixel point $pt
function ASdotPx($pt,$type) {
	$color = $this->stroke;
	if ($type=='+' || $type=='-' || $type=='|') {
		$tl = $this->ticklength;
		$x = (int) round($pt[0]); $y = (int) round($pt[1]);
		if ($type=='+') {
			$ac = $this->axescolor;
			if ($ac != 'none' && isset($this->colors[$ac])) {
				imageline($this->img, (int) round($x-$tl), $y, (int) round($x+$tl), $y, $this->colors[$ac]);
				imageline($this->img, $x, (int) round($y-$tl), $x, (int) round($y+$tl), $this->colors[$ac]);
			}
		} else if ($color != 'none' && isset($this->colors[$color])) {
			if ($type=='-') {
				imageline($this->img, (int) round($x-$tl), $y, (int) round($x+$tl), $y, $this->colors[$color]);
			} else {
				imageline($this->img, $x, (int) round($y-$tl), $x, (int) round($y+$tl), $this->colors[$color]);
			}
		}
		return;
	}
	if ($type!='open') {
		if ($color != 'none' && isset($this->colors[$color])) {
			if ($this->usegd2) {
				imagefilledellipse($this->img, (int) round($pt[0]), (int) round($pt[1]), (int) round($this->dotradius), (int) round($this->dotradius), $this->colors[$color]);
			} else {
				$r = $this->dotradius;
				imagefilledpolygon($this->img,array($pt[0]-$r,$pt[1],$pt[0],$pt[1]+$r,$pt[0]+$r,$pt[1],$pt[0],$pt[1]-$r),4,$this->colors[$color]);
			}
		}
	} else {
		if ($this->usegd2) {
			imagefilledellipse($this->img, (int) round($pt[0]), (int) round($pt[1]), (int) round($this->dotradius), (int) round($this->dotradius), $this->colors['white']);
		} else {
			$r = $this->dotradius;
			imagefilledpolygon($this->img,array($pt[0]-$r,$pt[1],$pt[0],$pt[1]+$r,$pt[0]+$r,$pt[1],$pt[0],$pt[1]-$r),4,$this->colors['white']);
		}
		if ($color != 'none' && isset($this->colors[$color])) {
			imageellipse($this->img, (int) round($pt[0]), (int) round($pt[1]), (int) round($this->dotradius), (int) round($this->dotradius), $this->colors[$color]);
		}
	}
}
function ASdot2($arg) {
	if (!$this->isinit) {$this->ASinitPicture();}
	$pt = $this->pt2arr($arg[0]);
	$this->ASdotPx($pt, isset($arg[1])?$arg[1]:'closed');
	if (isset($arg[2]) && $arg[2] !== '' && $arg[2] !== 'null') {
		$pos = (isset($arg[3]) && $arg[3] !== '') ? $arg[3] : 'below';
		$this->AStext(array($arg[0],$arg[2],$pos));
	}
}
function ASarrowhead($p,$q) {
	if (!$this->isinit) {$this->ASinitPicture();}
	$this->ASarrowheadPx($this->pt2arr($p),$this->pt2arr($q));
}
function ASarrowheadPx($v,$w) {
	$u = array($w[0]-$v[0],$w[1]-$v[1]);
	$d = sqrt($u[0]*$u[0]+$u[1]*$u[1]);
	$rs = $this->arrowrelsize;
	$off = $this->arrowoffset;
	if ($d > 0.00000001 && $this->stroke != 'none' && isset($this->colors[$this->stroke])) {
		$u = array($u[0]/$d, $u[1]/$d);
		$up = array(-$u[1],$u[0]);
		$arr = array(
			(int) round($w[0]-$rs*15*$u[0]-$rs*4*$up[0]-$off*$u[0]),
			(int) round($w[1]-$rs*15*$u[1]-$rs*4*$up[1]-$off*$u[1]),
			(int) round($w[0]-1*$u[0]-$off*$u[0]),
			(int) round($w[1]-1*$u[1]-$off*$u[1]),
			(int) round($w[0]-$rs*15*$u[0]+$rs*4*$up[0]-$off*$u[0]),
			(int) round($w[1]-$rs*15*$u[1]+$rs*4*$up[1]-$off*$u[1]));
		$color = $this->stroke;
		if ($this->isphp8) {
			imagefilledpolygon($this->img,$arr,$this->colors[$color]);
		} else {
			imagefilledpolygon($this->img,$arr,count($arr)/2,$this->colors[$color]);
		}
	}
}
function ASslopefield($arg) {
	if (!$this->isinit) {$this->ASinitPicture();}
	$func = str_replace('Math.','',$arg[0]);
	if (count($arg)>1) {
		$dx = $this->evalifneeded($arg[1]);
		if ($dx*1==0) { $dx = 1;}
	} else {
		$dx = 1;
	}
	if (count($arg)>2) {
		$dy = $this->evalifneeded($arg[2]);
		if ($dy*1==0) { $dy = 1;}
	} else {
		$dy = 1;
	}
	$efunc = makeMathFunction($func, "x,y");
	if ($efunc === false) { return; }
	$dz = sqrt($dx*$dx + $dy*$dy)/6;
	$x_min = $dx*ceil($this->xmin/$dx);
	$y_min = $dy*ceil($this->ymin/$dy);
	for ($x = $x_min; $x<= $this->xmax; $x+= $dx) {
		for ($y = $y_min; $y<= $this->ymax; $y+= $dy) {
			$gxy = @$efunc(['x'=>$x, 'y'=>$y]);
			if (is_numeric($gxy) && !is_nan($gxy)) {
				if (is_infinite($gxy)) {
					$u = 0; $v = $dz;
				} else {
					$u = $dz/sqrt(1+$gxy*$gxy);
					$v = $gxy*$u;
				}
				$this->ASlinePx($this->ptpx($x-$u,$y-$v), $this->ptpx($x+$u,$y+$v));
			}
		}
	}
}

// Adaptively samples $g(x) over [xa,xb].  Starts with a uniform grid, then
// recursively bisects any interval whose midpoint is more than tol pixels from
// the chord joining its endpoints (y clamped to [ylo,yhi] first).  Intervals
// with an undefined endpoint or midpoint are also bisected to find domain edges
// and asymptotes.  Jump discontinuities are flagged in $brk (index of a point
// that should not be connected to the previous one).  Port of adaptiveSamples
// in ASCIIsvg.js.
function adaptiveSamples($g, $xa, $xb, $ylo, $yhi) {
	$nseed = 47; $maxdepth = 7; $tol = 0.25; $maxpts = 2500; $jumppx = 3;
	$yu = $this->yunitlength;
	$xs = array($xa);
	$ys = array($g($xa));
	$brk = array();
	$bad = function($y) { return is_nan($y) || is_infinite($y); };
	$sy = function($y) use ($yu,$ylo,$yhi) { return $yu*min($yhi, max($ylo, $y)); };
	$rec = function($x0,$y0,$x1,$y1,$d,$pgap) use (&$rec,&$xs,&$ys,&$brk,$g,$bad,$sy,$maxdepth,$tol,$maxpts,$jumppx,$ylo,$yhi) {
		$nan0 = $bad($y0); $nan1 = $bad($y1);
		$gap = ($nan0 || $nan1) ? 0 : abs($sy($y1) - $sy($y0));
		if ($d < $maxdepth && count($xs) < $maxpts) {
			$xm = ($x0 + $x1)/2;
			$ym = $g($xm);
			$nanm = $bad($ym);
			if ($nan0 && $nan1 && $nanm) {
				$split = false;
			} else if ($nan0 || $nan1 || $nanm) {
				$split = true;
			} else {
				$split = abs($sy($ym) - ($sy($y0) + $sy($y1))/2) > $tol;
			}
			if ($split) {
				$rec($x0, $y0, $xm, $ym, $d+1, $gap);
				$rec($xm, $ym, $x1, $y1, $d+1, $gap);
				return;
			}
		} else if ($d >= $maxdepth && !$nan0 && !$nan1 && $gap > $jumppx && $gap >= 0.9*$pgap &&
			$y0 >= $ylo && $y0 <= $yhi && $y1 >= $ylo && $y1 <= $yhi) {
			$brk[count($xs)] = true;
		}
		$xs[] = $x1;
		$ys[] = $y1;
	};
	$step = ($xb - $xa)/$nseed;
	$x0 = $xa; $y0 = $ys[0];
	for ($j = 1; $j <= $nseed; $j++) {
		$x1 = ($j == $nseed) ? $xb : $xa + $step*$j;
		$y1 = $g($x1);
		$rec($x0, $y0, $x1, $y1, 0, 0);
		$x0 = $x1;
		$y0 = $y1;
	}
	return array($xs, $ys, $brk);
}

// clips the pixel-space segment p-q to the image; returns null if entirely outside
function clipSegPx($p, $q) {
	$xl = -2; $xh = $this->width + 2; $yl = -2; $yh = $this->height + 2;
	$dx = $q[0]-$p[0]; $dy = $q[1]-$p[1];
	$t0 = 0; $t1 = 1;
	$ps = array(-$dx, $dx, -$dy, $dy);
	$qs = array($p[0]-$xl, $xh-$p[0], $p[1]-$yl, $yh-$p[1]);
	for ($i=0; $i<4; $i++) {
		if ($ps[$i] == 0) {
			if ($qs[$i] < 0) { return null; }
		} else {
			$r = $qs[$i]/$ps[$i];
			if ($ps[$i] < 0) {
				if ($r > $t1) { return null; }
				if ($r > $t0) { $t0 = $r; }
			} else {
				if ($r < $t0) { return null; }
				if ($r < $t1) { $t1 = $r; }
			}
		}
	}
	return array(array($p[0]+$t0*$dx, $p[1]+$t0*$dy), array($p[0]+$t1*$dx, $p[1]+$t1*$dy));
}

// draws a run of plot points (user units) as connected segments
function ASplotpath($pth) {
	$n = count($pth);
	$prev = null;
	for ($i=0; $i<$n; $i++) {
		$cur = $this->ptpx($pth[$i][0],$pth[$i][1]);
		if ($prev !== null) {
			$seg = $this->clipSegPx($prev, $cur);
			if ($seg !== null) {
				$this->ASlinePx($seg[0], $seg[1], 'none');
			}
		}
		$prev = $cur;
	}
	if ($this->marker=='dot' || $this->marker=='arrowdot') {
		foreach ($pth as $pt) {
			$px = $this->ptpx($pt[0],$pt[1]);
			if ($px[0] > -10 && $px[0] < $this->width+10 && $px[1] > -10 && $px[1] < $this->height+10) {
				$this->ASdot($px,8);
			}
		}
	}
}

function ASplot($function) {
	if (!$this->isinit) {$this->ASinitPicture();}
	$efunc = $exfunc = $eyfunc = null;
	$function = str_replace(array('"','\'',';','Math.'),'',$function);
	$f0 = trim($function[0]);
	$isparametric = (strpos($f0,'[')===0);
	if ($isparametric) {
		if (substr($f0,-1)==']') { $f0 = substr($f0,0,-1); }
		$funcp = $this->splitTop(substr($f0,1));
		if (count($funcp)<2) { return; }
		$exfunc = makeMathFunction($funcp[0], "t");
		$eyfunc = makeMathFunction($funcp[1], "t");
		if ($exfunc === false || $eyfunc === false) {
			return;
		}
	} else {
		$efunc = makeMathFunction($f0, "x");
		if ($efunc === false) {
			return;
		}
	}
	$avoid = array();
	if (isset($function[1]) && $function[1]!=='' && $function[1]!=='null') {
		$min = $this->evalifneeded($function[1]);
	} else {
		$min = $this->xmin;
	}
	if (isset($function[2]) && $function[2]!=='' && $function[2]!=='null') {
		$xmaxarr = explode('!',$function[2]);
		$max = $this->evalifneeded($xmaxarr[0]);
		foreach (array_slice($xmaxarr,1) as $av) {
			$avoid[] = $this->evalifneeded($av);
		}
	} else {
		$max = $this->xmax;
	}
	if ($max <= $min) { return; }
	$points = null;
	if (isset($function[3]) && $function[3]!=='' && $function[3]!=='null') {
		$points = $this->evalifneeded($function[3]);
		if ($points < 1) { $points = null; }
	}

	// g gives y (or y(t) for parametric); f gives x(t) for parametric
	$g = function($v) use ($isparametric,$efunc,$eyfunc,$avoid) {
		if (in_array($v,$avoid)) { return NAN; }
		$y = $isparametric ? @$eyfunc(['t'=>$v]) : @$efunc(['x'=>$v]);
		return is_numeric($y) ? (float) $y : NAN;
	};
	$f = function($v) use ($isparametric,$exfunc) {
		if (!$isparametric) { return $v; }
		$x = @$exfunc(['t'=>$v]);
		return is_numeric($x) ? (float) $x : NAN;
	};

	$brk = array();
	if (!$isparametric && $this->ymax > $this->ymin) {
		list($ts, $gs, $brk) = $this->adaptiveSamples($g, $min, $max-0.000001*($max-$min),
			$this->ymin-($this->ymax-$this->ymin), $this->ymax+($this->ymax-$this->ymin));
	} else {
		$n = ($points===null) ? 200 : $points;
		$inc = ($max-$min-0.000001*($max-$min))/$n;
		$ts = array(); $gs = array();
		for ($i=0; $i<=$n; $i++) {
			$t = $min + $inc*$i;
			if ($t > $max) { break; }
			$ts[] = $t;
			$gs[] = $g($t);
		}
	}

	$pth = array();
	$firstpts = array();
	$yrange = $this->ymax - $this->ymin;
	for ($i=0; $i<count($ts); $i++) {
		$gt = $gs[$i];
		$ft = $f($ts[$i]);
		if (is_nan($gt) || is_infinite($gt) || is_nan($ft) || is_infinite($ft)) { continue; }
		if (count($pth) > 0 && (isset($brk[$i]) || abs($gt - $pth[count($pth)-1][1]) > $yrange)) {
			// jump or too steep to connect; end this path and start another
			if (count($pth) > 1) { $this->ASplotpath($pth); }
			$pth = array();
		}
		$pth[] = array($ft, $gt);
		if (count($firstpts) < 2) { $firstpts[] = $pth[count($pth)-1]; }
	}
	if (count($pth) > 1) { $this->ASplotpath($pth); }

	if (isset($function[5]) && $function[5]!=='' && $function[5]!=='null' && count($firstpts)>0) {
		$p0 = $firstpts[0];
		if ($function[5]==1 && count($firstpts)>1) {
			$this->ASarrowheadPx($this->ptpx($firstpts[1][0],$firstpts[1][1]), $this->ptpx($p0[0],$p0[1]));
		} else if ($function[5]==2) {
			$this->ASdotPx($this->ptpx($p0[0],$p0[1]), 'open');
		} else if ($function[5]==3) {
			$this->ASdotPx($this->ptpx($p0[0],$p0[1]), 'closed');
		}
	}
	if (isset($function[6]) && $function[6]!=='' && $function[6]!=='null' && count($pth)>0) {
		$pl = $pth[count($pth)-1];
		if ($function[6]==1 && count($pth)>1) {
			$pp = $pth[count($pth)-2];
			$this->ASarrowheadPx($this->ptpx($pp[0],$pp[1]), $this->ptpx($pl[0],$pl[1]));
		} else if ($function[6]==2) {
			$this->ASdotPx($this->ptpx($pl[0],$pl[1]), 'open');
		} else if ($function[6]==3) {
			$this->ASdotPx($this->ptpx($pl[0],$pl[1]), 'closed');
		}
	}
}
// converts a point like "[x,y]" or "x,y" (in user units) to pixel coordinates
function pt2arr($pt) {
	$pt = trim($pt);
	if ($pt !== '' && $pt[0] == '[') { $pt = substr($pt,1); }
	if ($pt !== '' && substr($pt,-1) == ']') { $pt = substr($pt,0,-1); }
	$pt = $this->splitTop($pt);
	if (count($pt) < 2) { $pt[1] = 0; }
	return $this->ptpx($this->evalifneeded($pt[0]), $this->evalifneeded($pt[1]));
}
function parseargs($str) {
	$args = $this->splitTop($str);
	for ($i=0;$i<count($args);$i++) {
		$args[$i] = trim(str_replace(array('"','\''),'',$args[$i]));
	}
	return $args;
}
function outputimage() {
	if (func_num_args()>0) {
		$filename = func_get_arg(0);
		imagepng($this->img,$filename,8);
	} else {
		imagepng($this->img,null,8);
	}
}
function rounddec($v,$dec) {
	$dec = 2 + max(0,$dec-2);
	$m = pow(10,$dec);
	return (string) (round($v*$m)/$m + 0);
}
// handle the JS-isms that scripts use in numeric arguments
function prepexpr($str) {
	$str = str_replace('Math.','',$str);
	$str = preg_replace(array('/\bPI\b/','/\bE\b/','/\bacos\(/','/\basin\(/','/\batan\(/','/\bwidth\b/','/\bheight\b/'),
		array('pi','e','arccos(','arcsin(','arctan(',$this->width,$this->height), $str);
	// functions the expression parser lacks; evaluate innermost calls first
	$n = 0;
	while ($n++ < 25 && preg_match('/\b(max|min|pow|floor|ceil)\(([^()]*)\)/', $str, $m, PREG_OFFSET_CAPTURE)) {
		$a = array();
		foreach ($this->splitTop($m[2][0]) as $part) {
			$a[] = evalMathParser(trim($part));
		}
		switch ($m[1][0]) {
			case 'max': $val = max($a); break;
			case 'min': $val = min($a); break;
			case 'pow': $val = (count($a)>1) ? pow($a[0],$a[1]) : NAN; break;
			case 'floor': $val = floor($a[0]); break;
			case 'ceil': $val = ceil($a[0]); break;
		}
		if (is_nan($val) || is_infinite($val)) { $val = 0; }
		$str = substr_replace($str, '('.sprintf('%.14F',$val).')', $m[0][1], strlen($m[0][0]));
	}
	return $str;
}
function evalifneeded($str) {
	if (is_numeric($str)) {
		return $str;
	} else {
		$val = evalMathParser($this->prepexpr((string) $str));
		if (is_nan($val)) { return 0;}
		return $val;
	}
}
} //end AStoIMG class

?>
