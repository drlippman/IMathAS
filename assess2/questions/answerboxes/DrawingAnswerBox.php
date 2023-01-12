<?php

namespace IMathAS\assess2\questions\answerboxes;

require_once __DIR__ . '/AnswerBox.php';

use Sanitize;

class DrawingAnswerBox implements AnswerBox
{
    private $answerBoxParams;

    private $answerBox;
    private $jsParams;
    private $entryTip;
    private $correctAnswerForPart;
    private $previewLocation;

    public function __construct(AnswerBoxParams $answerBoxParams)
    {
        $this->answerBoxParams = $answerBoxParams;
    }

    public function generate(): void
    {
        global $RND, $myrights, $useeqnhelper, $showtips, $imasroot, $staticroot;

        $anstype = $this->answerBoxParams->getAnswerType();
        $qn = $this->answerBoxParams->getQuestionNumber();
        $multi = $this->answerBoxParams->getIsMultiPartQuestion();
        $isConditional = $this->answerBoxParams->getIsConditional();
        $partnum = $this->answerBoxParams->getQuestionPartNumber();
        $la = $this->answerBoxParams->getStudentLastAnswers();
        $options = $this->answerBoxParams->getQuestionWriterVars();
        $colorbox = $this->answerBoxParams->getColorboxKeyword();

        $out = '';
        $tip = '';
        $sa = '';
        $preview = '';
        $params = [];

        $optionkeys = ['grid', 'snaptogrid', 'answerformat', 'readerlabel'];
        foreach ($optionkeys as $optionkey) {
            ${$optionkey} = getOptionVal($options, $optionkey, $multi, $partnum);
        }
        $optionkeys = ['answers','background'];
        foreach ($optionkeys as $optionkey) {
            ${$optionkey} = getOptionVal($options, $optionkey, $multi, $partnum, 1);
        }

        if (!is_array($answers)) {
            settype($answers, "array");
        }
        $answers = array_map('clean', $answers);
        if (empty($snaptogrid)) {
            $snaptogrid = 0;
        } else {
            $snapparts = explode(':', $snaptogrid);
            $snapparts = array_map('evalbasic', $snapparts);
            $snaptogrid = implode(':', $snapparts);
        }
        if ($multi) {$qn = ($qn + 1) * 1000 + $partnum;}
        $imgborder = 5;

        if (empty($answerformat)) {
            $answerformat = array('line', 'dot', 'opendot');
        } else if (!is_array($answerformat)) {
            $answerformat = array_map('trim', explode(',', $answerformat));
        }

        if ($answerformat[0] == 'numberline') {
            $settings = array(-5, 5, 0, 0, 1, 0, 300, 50, "", "");
            $locky = 1;
            if (count($answerformat) == 1) {
                $answerformat[] = "lineseg";
                $answerformat[] = "dot";
                $answerformat[] = "opendot";
            }
        } else {
            $settings = array(-5, 5, -5, 5, 1, 1, 300, 300, "", "");
            $locky = 0;
        }
        $xsclgridpts = array('');
        if (!empty($grid)) {
            if (!is_array($grid)) {
                $grid = array_map('trim', explode(',', $grid));
            } else if (strpos($grid[0], ',') !== false) { //forgot to set as multipart?
                $grid = array();
            }
            for ($i = 0; $i < count($grid); $i++) {
                if ($grid[$i] != '') {
                    if (strpos($grid[$i], ':') !== false) {
                        $pts = explode(':', $grid[$i]);
                        foreach ($pts as $k => $v) {
                            if ($v[0] === "h") {
                                $pts[$k] = ($k<2) ? "h" . evalbasic(substr($v, 1)) : $v;
                            } else {
                                $pts[$k] = ($k<2) ? evalbasic($v) : $v;
                            }
                        }
                        $settings[$i] = implode(':', $pts);
                    } else {
                        $settings[$i] = evalbasic($grid[$i]);
                    }
                }
            }

            $origxmin = $settings[0];
            if (strpos($settings[0], '0:') === 0) {
                $settings[0] = substr($settings[0], 2);
            }
            $origymin = $settings[2];
            if (strpos($settings[2], '0:') === 0) {
                $settings[2] = substr($settings[2], 2);
            }
            if (isset($grid[4])) {
                $xsclgridpts = explode(':', $grid[4]);
            }
            if (strpos($xsclgridpts[0], '/') !== false || strpos($xsclgridpts[0], 'pi') !== false) {
                if (strpos($settings[4], ':') !== false) {
                    $settings4pts = explode(':', $settings[4]);
                    $settings[4] = 2 * ($settings[1] - $settings[0]) . ':' . $settings4pts[1];
                } else {
                    $settings[4] = 2 * ($settings[1] - $settings[0]) . ':' . $settings[4];
                }
            }
        } else {
            $origxmin = $settings[0];
            $origymin = $settings[2];
        }
        if (empty($background)) {$background = '';}

        if ($answerformat[0] == 'numberline') {
            $settings[2] = 0;
            $origymin = 0;
            $settings[3] = 0;
            if (strpos($settings[4], ':') !== false) {
                $settings[4] = explode(':', $settings[4]);
                if ($settings[4][0][0] == 'h') {
                    $sclinglbl = substr($settings[4][0], 1) . ':0:off';
                } else {
                    $sclinglbl = $settings[4][0];
                }
                $sclinggrid = $settings[4][1];
            } else {
                $sclinglbl = $settings[4];
                if ($sclinglbl > 1 && $sclinglbl < 6 && ($settings[1] - $settings[0]) < 10 * $sclinglbl) {
                    $sclinggrid = 1;
                } else {
                    $sclinggrid = 0;
                }

            }
        } else {
            if (strpos($settings[4], ':') !== false) {
                $settings[4] = explode(':', $settings[4]);
                $xlbl = $settings[4][0];
                $xgrid = $settings[4][1];
            } else {
                $xlbl = $settings[4];
                $xgrid = $settings[4];
            }
            if (strpos($settings[5], ':') !== false) {
                $settings[5] = explode(':', $settings[5]);
                $ylbl = $settings[5][0];
                $ygrid = $settings[5][1];
            } else {
                $ylbl = $settings[5];
                $ygrid = $settings[5];
            }
            $sclinglbl = "$xlbl:$ylbl";
            $sclinggrid = "$xgrid:$ygrid";
        }
        if ($snaptogrid !== 0) {
            list($newwidth, $newheight) = getsnapwidthheight($settings[0], $settings[1], $settings[2], $settings[3], $settings[6], $settings[7], $snaptogrid);
            if (abs($newwidth - $settings[6]) / $settings[6] < .1) {
                $settings[6] = $newwidth;
            }
            if (abs($newheight - $settings[7]) / $settings[7] < .1) {
                $settings[7] = $newheight;
            }
        }
        if ($_SESSION['userprefs']['drawentry'] == 1 && $_SESSION['graphdisp'] == 0) {
            //can't imagine why someone would pick this, but if they do, need to set graphdisp to 2 temporarily
            $revertgraphdisp = true;
            $_SESSION['graphdisp'] = 2;
        } else {
            $revertgraphdisp = false;
        }
        if (!is_array($background) && substr($background, 0, 5) == "draw:") {
            $plot = showplot("", $origxmin, $settings[1], $origymin, $settings[3], $sclinglbl, $sclinggrid, $settings[6], $settings[7]);
            $insat = strpos($plot, ');', strpos($plot, 'axes')) + 2;
            $plot = substr($plot, 0, $insat) . str_replace("'", '"', substr($background, 5)) . substr($plot, $insat);
        } else if (!is_array($background) && $background == 'none') {
            $plot = showasciisvg("initPicture(0,10,0,10);", $settings[6], $settings[7]);
        } else {
            $plot = showplot($background, $origxmin, $settings[1], $origymin, $settings[3], $sclinglbl, $sclinggrid, $settings[6], $settings[7]);
        }
        if (is_array($xsclgridpts) && count($xsclgridpts) > 2) {
            $plot = addlabel($plot, $settings[1], 0, $xsclgridpts[2], "black", "aboveleft");
        }
        if (is_array($settings[5]) && count($settings[5]) > 2) {
            $plot = addlabel($plot, 0, $settings[3], $settings[5][2], "black", "belowright");
        }
        if (!empty($grid) && (strpos($xsclgridpts[0], '/') !== false || strpos($xsclgridpts[0], 'pi') !== false)) {
            $plot = addfractionaxislabels($plot, $xsclgridpts[0]);
        }

        if ($settings[8] != "") {
        }

        $dotline = 0;
        $a11yinfo = '';
        if ($colorbox != '') {$out .= '<div class="' . $colorbox . '" id="qnwrap' . $qn . '">';}
        //$bg = getgraphfilename($plot);
        $bg = preg_replace('/.*script=\'(.*?[^\\\\])\'.*/', '$1', $plot);
        if (isset($GLOBALS['hidedrawcontrols'])) {
            $out .= $plot;
        } else {
            if ($_SESSION['userprefs']['drawentry'] == 0) { //accessible entry
                $def = 0;
                $a11yinfo = implode(',', $answerformat);
                if ($_SESSION['graphdisp'] == 0) {
                    $a11yinfo .= ',noprev';
                }
                $out .= '<p class="sr-only">' . $this->answerBoxParams->getQuestionIdentifierString() .
                    (!empty($readerlabel) ? ' ' . Sanitize::encodeStringForDisplay($readerlabel) : '') . '</p>';
                $out .= '<p>' . _('Graph to add drawings to:') . '</p>';
                if ($_SESSION['graphdisp'] > 0) {
                    $plot = str_replace('<embed', '<embed data-nomag=1', $plot); //hide mag
                    //overlay canvas over SVG.
                    $out .= '<div class="drawcanvas" style="position:relative;width:' . $settings[6] . 'px;height:' . $settings[7] . 'px">';
                    $out .= '<div class="canvasbg" style="position:absolute;top:0px;left:0px;">' . $plot . '</div>';
                    $out .= '<div class="drawcanvasholder" style="position:relative;top:0;left:0;z-index:2">';
                    $out .= "<canvas id=\"canvas$qn\" width=\"{$settings[6]}\" height=\"{$settings[7]}\"></canvas>";
                    $out .= '</div></div>';    
                } else {
                    $out .= '<p>' . $plot . '</p>';
                }
                $out .= '<p tabindex="-1">' . _('Elements to draw:') . '</p>';
                $out .= '<ul class="a11ydraw" id="a11ydraw' . $qn . '"></ul>';
                $out .= '<p class="empty-state">' . _('No elements have been added yet') . '</p>';
                $out .= '<p><label>' . _('Add new drawing element');
                $out .= ': <select id="a11ydrawnew' . $qn . '"></select></label> ';
                $out .= '<button type="button" class="a11ydrawadd" data-qn="' . $qn . '">' . _('Add') . '</button></p>';
                if ($answerformat[0] == "polygon") {
                    $dotline = 1;
                } else if ($answerformat[0] == "closedpolygon") {
                    $answerformat[0] = "polygon";
                    $dotline = 2;
                }
                
            } else {
                $plot = str_replace('<embed', '<embed data-nomag=1', $plot); //hide mag
                //overlay canvas over SVG.
                $out .= '<div class="drawcanvas" style="position:relative;width:' . $settings[6] . 'px;height:' . $settings[7] . 'px">';
                $out .= '<div class="canvasbg" style="position:absolute;top:0px;left:0px;">' . $plot . '</div>';
                $out .= '<div class="drawcanvasholder" style="position:relative;top:0;left:0;z-index:2">';
                $out .= "<canvas id=\"canvas$qn\" width=\"{$settings[6]}\" height=\"{$settings[7]}\"></canvas>";
                $out .= '</div></div>';

                $out .= "<div><span id=\"drawtools$qn\" class=\"drawtools\">";
                $out .= "<span data-drawaction=\"clearcanvas\" data-qn=\"$qn\">" . _('Clear All') . "</span> ";
                //if ($answerformat[0]=='freehand' && count($answerformat)==1) {
                //    $out .= "<span onclick=\"imathasDraw.clearlastline($qn)\">" . _('Clear Last') . "</span> ";
                //}
                $out .= _('Draw:') . " ";
                if ($answerformat[0] == 'inequality') {
                    $def = 10;
                    if (in_array('both', $answerformat)) {
                        $out .= "<img src=\"$staticroot/img/tpineq.gif\" data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"10\" class=\"sel\" alt=\"Linear inequality, solid line\"/>";
                        $out .= "<img src=\"$staticroot/img/tpineqdash.gif\" data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"10.2\" alt=\"Linear inequality, dashed line\"/>";
                        $out .= "<img src=\"$staticroot/img/tpineqparab.gif\" data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"10.3\" alt=\"Quadratic inequality, solid line\"/>";
                        $out .= "<img src=\"$staticroot/img/tpineqparabdash.gif\" data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"10.4\" alt=\"Quadratic inequality, dashed line\"/>";
                        $def = 10;
                    }
                    if (count($answerformat) == 1 || in_array('line', $answerformat)) {
                        $out .= "<img src=\"$staticroot/img/tpineq.gif\" data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"10\" class=\"sel\" alt=\"Linear inequality, solid line\"/>";
                        $out .= "<img src=\"$staticroot/img/tpineqdash.gif\" data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"10.2\" alt=\"Linear inequality, dashed line\"/>";
                        if (count($answerformat) == 1 || $answerformat[1] == 'line') {
                            $def = 10;
                        }
                    }
                    if (in_array('parab', $answerformat)) {
                        $out .= "<img src=\"$staticroot/img/tpineqparab.gif\" data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"10.3\" class=\"sel\" alt=\"Quadratic inequality, solid line\"/>";
                        $out .= "<img src=\"$staticroot/img/tpineqparabdash.gif\" data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"10.4\" alt=\"Quadratic inequality, dashed line\"/>";
                        if ($answerformat[1] == 'parab') {
                            $def = 10.3;
                        }
                    }
                    if (in_array('abs', $answerformat)) {
                        $out .= "<img src=\"$staticroot/img/tpineqabs.gif\" data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"10.5\" class=\"sel\" alt=\"Absolute Value inequality, solid line\"/>";
                        $out .= "<img src=\"$staticroot/img/tpineqabsdash.gif\" data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"10.6\" alt=\"Absolute Value inequality, dashed line\"/>";
                        if ($answerformat[1] == 'abs') {
                            $def = 10.5;
                        }
                    }

                } else if ($answerformat[0] == 'twopoint') {
                    $def = 5;
                    if (count($answerformat) == 1 || in_array('line', $answerformat)) {
                        $out .= "<img src=\"$staticroot/img/tpline.gif\" data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"5\" ";
                        if (count($answerformat) == 1 || $answerformat[1] == 'line') {$out .= 'class="sel" ';
                            $def = 5;}
                        $out .= ' alt="Line"/>';
                    }
                    //$out .= "<img src=\"$staticroot/img/tpline2.gif\" onclick=\"settool(this,$qn,5.2)\"/>";
                    if (in_array('lineseg', $answerformat)) {
                        $out .= "<img src=\"$staticroot/img/tpline3.gif\" data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"5.3\" ";
                        if (count($answerformat) > 1 && $answerformat[1] == 'lineseg') {$out .= 'class="sel" ';
                            $def = 5.3;}
                        $out .= ' alt="Line segment"/>';
                    }
                    if (in_array('ray', $answerformat)) {
                        $out .= "<img src=\"$staticroot/img/tpline2.gif\" data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"5.2\" ";
                        if (count($answerformat) > 1 && $answerformat[1] == 'ray') {$out .= 'class="sel" ';
                            $def = 5.2;}
                        $out .= ' alt="Ray"/>';
                    }
                    if (in_array('vector', $answerformat)) {
                        $out .= "<img src=\"$staticroot/img/tpvec.gif\" data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"5.4\" ";
                        if (count($answerformat) > 1 && $answerformat[1] == 'vector') {$out .= 'class="sel" ';
                            $def = 5.4;}
                        $out .= ' alt="Vector"/>';
                    }
                    if (count($answerformat) == 1 || in_array('parab', $answerformat)) {
                        $out .= "<img src=\"$staticroot/img/tpparab.png\" data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"6\" ";
                        if (count($answerformat) > 1 && $answerformat[1] == 'parab') {$out .= 'class="sel" ';
                            $def = 6;}
                        $out .= ' alt="Parabola"/>';
                    }
                    if (in_array('horizparab', $answerformat)) {
                        $out .= "<img src=\"$staticroot/img/tphorizparab.png\" data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"6.1\" ";
                        if (count($answerformat) > 1 && $answerformat[1] == 'horizparab') {$out .= 'class="sel" ';
                            $def = 6.1;}
                        $out .= ' alt="Horizontal parabola"/>';
                    }
                    if (in_array('halfparab', $answerformat)) {
                        $out .= "<img src=\"$staticroot/img/tphalfparab.png\" data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"6.2\" ";
                        if (count($answerformat) > 1 && $answerformat[1] == 'halfparab') {$out .= 'class="sel" ';
                            $def = 6.2;}
                        $out .= ' alt="Half Parabola"/>';
                    }
                    if (in_array('cubic', $answerformat)) {
                        $out .= "<img src=\"$staticroot/img/tpcubic.png\" data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"6.3\" ";
                        if (count($answerformat) > 1 && $answerformat[1] == 'cubic') {$out .= 'class="sel" ';
                            $def = 6.3;}
                        $out .= ' alt="Cubic"/>';
                    }
                    if (in_array('sqrt', $answerformat)) {
                        $out .= "<img src=\"$staticroot/img/tpsqrt.png\" data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"6.5\" ";
                        if (count($answerformat) > 1 && $answerformat[1] == 'sqrt') {$out .= 'class="sel" ';
                            $def = 6.5;}
                        $out .= ' alt="Square root"/>';
                    }
                    if (in_array('cuberoot', $answerformat)) {
                        $out .= "<img src=\"$staticroot/img/tpcuberoot.png\" data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"6.6\" ";
                        if (count($answerformat) > 1 && $answerformat[1] == 'cuberoot') {$out .= 'class="sel" ';
                            $def = 6.6;}
                        $out .= ' alt="Cube Root"/>';
                    }
                    if (count($answerformat) == 1 || in_array('abs', $answerformat)) {
                        $out .= "<img src=\"$staticroot/img/tpabs.gif\" data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"8\" ";
                        if (count($answerformat) > 1 && $answerformat[1] == 'abs') {$out .= 'class="sel" ';
                            $def = 8;}
                        $out .= ' alt="Absolute value"/>';
                    }
                    if (in_array('rational', $answerformat)) {
                        $out .= "<img src=\"$staticroot/img/tprat.png\" data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"8.2\" ";
                        if (count($answerformat) > 1 && $answerformat[1] == 'rational') {$out .= 'class="sel" ';
                            $def = 8.2;}
                        $out .= ' alt="Rational"/>';
                    }
                    if (in_array('exp', $answerformat)) {
                        $out .= "<img src=\"$staticroot/img/tpexp.png\" data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"8.3\" ";
                        if (count($answerformat) > 1 && $answerformat[1] == 'exp') {$out .= 'class="sel" ';
                            $def = 8.3;}
                        $out .= ' alt="Exponential"/>';
                    }
                    if (in_array('genexp', $answerformat)) {
                        $out .= "<img src=\"$staticroot/img/tpgenexp.png\" data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"8.5\" ";
                        if (count($answerformat) > 1 && $answerformat[1] == 'genexp') {$out .= 'class="sel" ';
                            $def = 8.5;}
                        $out .= ' alt="General Exponential"/>';
                    }
                    if (in_array('log', $answerformat)) {
                        $out .= "<img src=\"$staticroot/img/tplog.png\" data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"8.4\" ";
                        if (count($answerformat) > 1 && $answerformat[1] == 'log') {$out .= 'class="sel" ';
                            $def = 8.4;}
                        $out .= ' alt="Logarithm"/>';
                    }
                    if (in_array('genlog', $answerformat)) {
                        $out .= "<img src=\"$staticroot/img/tpgenlog.png\" data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"8.6\" ";
                        if (count($answerformat) > 1 && $answerformat[1] == 'genlog') {$out .= 'class="sel" ';
                            $def = 8.6;}
                        $out .= ' alt="General Logarithm"/>';
                    }
                    if ($settings[6] * ($settings[3] - $settings[2]) == $settings[7] * ($settings[1] - $settings[0])) {
                        //only circles if equal spacing in x and y
                        if (count($answerformat) == 1 || in_array('circle', $answerformat)) {
                            $out .= "<img src=\"$staticroot/img/tpcirc.png\" data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"7\" ";
                            if (count($answerformat) > 1 && $answerformat[1] == 'circle') {$out .= 'class="sel" ';
                                $def = 7;}
                            $out .= ' alt="Circle"/>';
                        }
                    }
                    if (in_array('ellipse', $answerformat)) {
                        $out .= "<img src=\"$staticroot/img/tpellipse.png\" data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"7.2\" ";
                        if (count($answerformat) > 1 && $answerformat[1] == 'ellipse') {$out .= 'class="sel" ';
                            $def = 7.2;}
                        $out .= ' alt="Ellipse"/>';
                    }
                    if (in_array('hyperbola', $answerformat)) {
                        $out .= "<img src=\"$staticroot/img/tpverthyper.png\" data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"7.4\" ";
                        if (count($answerformat) > 1 && $answerformat[1] == 'hyperbola') {$out .= 'class="sel" ';
                            $def = 7.4;}
                        $out .= ' alt="Vertical hyperbola"/>';
                        $out .= "<img src=\"$staticroot/img/tphorizhyper.png\" data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"7.5\" ";
                        $out .= ' alt="Horizontal hyperbola"/>';
                    }
                    if (in_array('trig', $answerformat)) {
                        $out .= "<img src=\"$staticroot/img/tpcos.png\" data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"9\" ";
                        if (count($answerformat) > 1 && $answerformat[1] == 'trig') {$out .= 'class="sel" ';
                            $def = 9;}
                        $out .= ' alt="Cosine"/>';
                        $out .= "<img src=\"$staticroot/img/tpsin.png\" data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"9.1\" alt=\"Sine\"/>";
                    }
                    if (count($answerformat) == 1 || in_array('dot', $answerformat)) {
                        $out .= "<img src=\"$staticroot/img/tpdot.gif\" data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"1\" ";
                        if (count($answerformat) > 1 && $answerformat[1] == 'dot') {$out .= 'class="sel" ';
                            $def = 1;}
                        $out .= ' alt="Dot"/>';
                    }
                    if (in_array('opendot', $answerformat)) {
                        $out .= "<img src=\"$staticroot/img/tpodot.gif\" data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"2\" ";
                        if (count($answerformat) > 1 && $answerformat[1] == 'opendot') {$out .= 'class="sel" ';
                            $def = 2;}
                        $out .= ' alt="Open dot"/>';
                    }
                } else if ($answerformat[0] == 'numberline') {
                    $def = 0.5;
                    if (in_array('lineseg', $answerformat)) {
                        $out .= "<img src=\"$staticroot/img/numlines.png\" data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"0.5\" ";
                        if (count($answerformat) == 1 || $answerformat[1] == 'lineseg') {$out .= 'class="sel" ';
                            $def = 0.5;}
                        $out .= ' alt="Line segments and rays" title="Line segments and rays"/>';
                    }
                    if (count($answerformat) == 1 || in_array('dot', $answerformat)) {
                        $out .= "<img src=\"$staticroot/img/tpdot.gif\" data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"1\" ";
                        if (count($answerformat) > 1 && $answerformat[1] == 'dot') {$out .= 'class="sel" ';
                            $def = 1;}
                        $out .= ' alt="Closed dot" title="Closed dot"/>';
                    }
                    if (in_array('opendot', $answerformat)) {
                        $out .= "<img src=\"$staticroot/img/tpodot.gif\" data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"2\" ";
                        if (count($answerformat) > 1 && $answerformat[1] == 'opendot') {$out .= 'class="sel" ';
                            $def = 2;}
                        $out .= ' alt="Open dot" title="Open dot"/>';
                    }
                } else {
                    if ($answerformat[0] == 'numberline') {
                        array_shift($answerformat);
                    }
                    for ($i = 0; $i < count($answerformat); $i++) {
                        if ($i == 0) {
                            $out .= '<span class="sel" ';
                        } else {
                            $out .= '<span ';
                        }
                        if ($answerformat[$i] == 'line') {
                            $out .= "data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"0\">" . _('Line') . "</span>";
                        } else if ($answerformat[$i] == 'lineseg') {
                            $out .= "data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"0.5\">" . _('Line Segment') . "</span>";
                        } else if ($answerformat[$i] == 'freehand') {
                            $out .= "data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"0.7\">" . _('Freehand Draw') . "</span>";
                            if ($answerformat[0] == 'freehand' && count($answerformat) == 1) {
                                $out .= "<span data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"-1\">" . _('Eraser') . "</span>";
                            }
                        } else if ($answerformat[$i] == 'dot') {
                            $out .= "data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"1\">" . _('Dot') . "</span>";
                        } else if ($answerformat[$i] == 'opendot') {
                            $out .= "data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"2\">" . _('Open Dot') . "</span>";
                        } else if ($answerformat[$i] == 'polygon') {
                            $out .= "data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"0\">" . _('Polygon') . "</span>";
                            $dotline = 1;
                        } else if ($answerformat[$i] == 'closedpolygon') {
                            $out .= "data-drawaction=\"settool\" data-qn=\"$qn\" data-val=\"0\">" . _('Polygon') . "</span>";
                            $dotline = 2;
                            $answerformat[$i] = 'polygon';
                        }
                    }
                    if ($answerformat[0] == 'line') {
                        $def = 0;
                    } else if ($answerformat[0] == 'lineseg') {
                        $def = 0.5;
                    } else if ($answerformat[0] == 'freehand') {
                        $def = 0.7;
                    } else if ($answerformat[0] == 'dot') {
                        $def = 1;
                    } else if ($answerformat[0] == 'opendot') {
                        $def = 2;
                    } else if ($answerformat[0] == 'polygon') {
                        $def = 0;
                    } else {
                        $def = 0;
                    }
                }
                $out .= '</span></div>';
            }
            //fix la's that were encoded incorrectly
            $la = str_replace(',,', ',', $la);
            $la = str_replace(';,', ';', $la);

            $attributes = [
                'type' => 'hidden',
                'name' => "qn$qn",
                'id' => "qn$qn",
                'value' => $la,
                'autocomplete' => 'off',
                'aria-label' => $this->answerBoxParams->getQuestionIdentifierString() .
                (!empty($readerlabel) ? ' ' . Sanitize::encodeStringForDisplay($readerlabel) : ''),
            ];

            $settings = array_map('floatval', $settings);
            $params['canvas'] = [$qn, $bg, $settings[0], $settings[1], $settings[2], $settings[3], 5, $settings[6], $settings[7], $def, $dotline, $locky, $snaptogrid, $a11yinfo];

            $out .= '<input ' .
            Sanitize::generateAttributeString($attributes) .
                '" />';

            if (isset($GLOBALS['capturedrawinit'])) {
                $GLOBALS['drawinitdata'][$qn] = [$bg, $settings[0], $settings[1], $settings[2], $settings[3], 5, $settings[6], $settings[7], $def, $dotline, $locky, $snaptogrid, $a11yinfo];
                //$params['livepoll_drawinit'] = "'$bg',{$settings[0]},{$settings[1]},{$settings[2]},{$settings[3]},5,{$settings[6]},{$settings[7]},$def,$dotline,$locky,$snaptogrid";
                $params['livepoll_drawinit'] = $GLOBALS['drawinitdata'][$qn];
            }
        }
        if ($colorbox != '') {$out .= '</div>';}

        if ($revertgraphdisp) {
            $_SESSION['graphdisp'] = 0;
        }
        $tip = _('Enter your answer by drawing on the graph.');
        if (is_array($answers) && !$isConditional && (count($answers)>1 || $answers[0] !== '')) {
            $saarr = array();
            $ineqcolors = array("blue", "red", "green");
            $k = 0;
            foreach ($answers as $ans) {
                if (is_array($ans)) {continue;} //shouldn't happen, unless user forgot to set question to multipart
                if ($ans == '') {continue;}
                $function = array_map('trim', explode(',', $ans));
                $defcolor = 'blue';
                if ($function[0] == 'optional') {
                    array_shift($function);
                    $defcolor = 'grey';
                }
                if (count($function)==2 && ($function[1][0]==='<' || $function[1][0]==='>')) {
                    $val = substr($function[1],1);
                    if ($function[1][0]==='<') {
                        $function[1] = '-oo';
                        $function[2] = $val;
                    } else {
                        $function[1] = $val;
                        $function[2] = 'oo';
                    }
                }
                if ($answerformat[0] == 'inequality') {
                    $saarr[$k] = makepretty($function[0]) . ',' . $ineqcolors[$k % 3];
                } else {
                    if (count($function) == 2 || (count($function) == 3 && ($function[2] == 'open' || $function[2] == 'closed'))) { //is dot
                        $saarr[$k] = $function[1] . ',' . $defcolor . ',' . $function[0] . ',' . $function[0];
                        if (count($function) == 2 || $function[2] == 'closed') {
                            $saarr[$k] .= ',closed';
                        } else {
                            $saarr[$k] .= ',open';
                        }
                        if ($locky == 1) {
                            $saarr[$k] .= ',,2';
                        }
                    } else if ($function[0] == 'vector') {
                        if (count($function) > 4) {
                            $xs = evalbasic($function[1],true);
                            $ys = evalbasic($function[2],true);
                            $dx = evalbasic($function[3],true) - $xs;
                            $dy = evalbasic($function[4],true) - $ys;
                            
                        } else {
                            $dx = evalbasic($function[1],true);
                            $dy = evalbasic($function[2],true);
                            $xs = 0;
                            $ys = 0;
                        }
                        $saarr[$k] = "[$xs + ($dx)*t, $ys + ($dy)*t],$defcolor,0,1,,arrow";
                    } else if ($function[0] == 'circle') { //is circle
                        $saarr[$k] = "[{$function[3]}*cos(t)+{$function[1]},{$function[3]}*sin(t)+{$function[2]}],$defcolor,0,6.31";
                    } else if ($function[0] == 'ellipse') {
                        $saarr[$k] = "[{$function[3]}*cos(t)+{$function[1]},{$function[4]}*sin(t)+{$function[2]}],$defcolor,0,6.31";
                    } else if ($function[0] == 'verthyperbola') {
                        //(y-yc)^2/a^2 -  (x-xc)^2/b^2 = 1
                        $saarr[$k] = "sqrt($function[3]^2*(1+(x-$function[1])^2/($function[4])^2))+$function[2]";
                        $k++;
                        $saarr[$k] = "-sqrt($function[3]^2*(1+(x-$function[1])^2/($function[4])^2))+$function[2]";
                        $k++;
                        $saarr[$k] = "[$function[1]+$function[4]*t,$function[2]+$function[3]*t],green,,,,,,dash";
                        $k++;
                        $saarr[$k] = "[$function[1]+$function[4]*t,$function[2]-$function[3]*t],green,,,,,,dash";
                    } else if ($function[0] == 'horizhyperbola') {
                        //(x-xc)^2/a^2 - (y-yc)^2/b^2 = 1
                        $saarr[$k] = "[sqrt($function[3]^2*(1+(t-$function[2])^2/($function[4])^2))+{$function[1]},t],$defcolor,$settings[2],$settings[3]";
                        $k++;
                        $saarr[$k] = "[-sqrt($function[3]^2*(1+(t-$function[2])^2/($function[4])^2))+{$function[1]},t],$defcolor,$settings[2],$settings[3]";
                        $k++;
                        $saarr[$k] = "[$function[1]+$function[3]*t,$function[2]+$function[4]*t],green,,,,,,dash";
                        $k++;
                        $saarr[$k] = "[$function[1]+$function[3]*t,$function[2]-$function[4]*t],green,,,,,,dash";
                    } else if (substr($function[0], 0, 2) == 'x=') {
                        if (count($function) == 3) {
                            if ($function[1] == '-oo') {$function[1] = $settings[2] - .1 * ($settings[3] - $settings[2]);}
                            if ($function[2] == 'oo') {$function[2] = $settings[3] + .1 * ($settings[3] - $settings[2]);}
                            $saarr[$k] = '[' . substr(str_replace('y', 't', $function[0]), 2) . ',t],' . $defcolor . ',' . $function[1] . ',' . $function[2];
                        } else {
                            $saarr[$k] = '[' . substr(str_replace('y', 't', $function[0]), 2) . ',t],' . $defcolor . ',' . ($settings[2] - 1) . ',' . ($settings[3] + 1);
                        }
                    } else { //is function
                        if (preg_match('/(sin[^\(]|cos[^\(]|sqrt[^\(]|log[^\(_]|log_\d+[^\d\(]|ln[^\(]|root[^\(]|root\([^\)]*?\)[^\(])/', $function[0], $m)) {
                            echo "Invalid notation on " . Sanitize::encodeStringForDisplay($function[0]) . ": missing function parens";
                            continue;
                        }
                        $saarr[$k] = $function[0] . ',' . $defcolor;
                        if (count($function) > 2) {
                            if ($function[1] == '-oo') {$function[1] = $settings[0] - .1 * ($settings[1] - $settings[0]);}
                            if ($function[2] == 'oo') {$function[2] = $settings[1] + .1 * ($settings[1] - $settings[0]);}
                            if ($locky && $function[1] < $settings[0]) {
                                $function[1] = $settings[0] - 10 * ($settings[1] - $settings[0]) / ($settings[6] - 10);
                            }
                            if ($locky && $function[2] > $settings[1]) {
                                $function[2] = $settings[1] + 10 * ($settings[1] - $settings[0]) / ($settings[6] - 10);
                            }
                            $saarr[$k] .= ',' . $function[1] . ',' . $function[2];
                            if ($locky) {
                                if ($function[1] == '-oo' || $function[1] < $settings[0]) { //left arrow
                                    $saarr[$k] .= ',arrow';
                                } else {
                                    $saarr[$k] .= ',';
                                }
                                if ($function[2] == 'oo' || $function[2] > $settings[1]) { //right arrow
                                    $saarr[$k] .= ',arrow';
                                } else {
                                    $saarr[$k] .= ',';
                                }
                            } else {
                                $saarr[$k] .= ',,';
                            }
                            if ($locky == 1) {
                                $saarr[$k] .= ',3';
                            }
                        } else if ($locky == 1) {
                            $saarr[$k] .= ',,,,,3';
                        }
                        //add asymptotes for rational function graphs
                        if (strpos($function[0], '/x') !== false || preg_match('|/\([^\)]*x|', $function[0])) {
                            $func = makeMathFunction(makepretty($function[0]), 'x');

                            $epsilon = ($settings[1] - $settings[0]) / 97;
                            $x1 = 1 / 4 * $settings[1] + 3 / 4 * $settings[0] + $epsilon;
                            $x2 = 1 / 2 * $settings[1] + 1 / 2 * $settings[0] + $epsilon;
                            $x3 = 3 / 4 * $settings[1] + 1 / 4 * $settings[0] + $epsilon;

                            $y1 = $func(['x' => $x1]);
                            $y2 = $func(['x' => $x2]);
                            $y3 = $func(['x' => $x3]);

                            $denom = -$x1 * $y2 + $x1 * $y3 + $x2 * $y1 - $x2 * $y3 - $x3 * $y1 + $x3 * $y2;
                            if ($denom == 0) { continue; }
                            $va = ($x1 * $x2 * $y1 - $x1 * $x2 * $y2 - $x1 * $x3 * $y1 + $x1 * $x3 * $y3 + $x2 * $x3 * $y2 - $x2 * $x3 * $y3) / ($denom);
                            $ha = (($x1 * $y1 - $x2 * $y2) - $va * ($y1 - $y2)) / ($x1 - $x2);

                            $k++;
                            $saarr[$k] = "$ha,green,,,,,,dash";
                            $k++;
                            $saarr[$k] = "[$va,t],green,,,,,,dash";
                        }
                    }
                }
                $k++;
            }

            if ($background != '') {
                if (!is_array($background) && substr($background, 0, 5) == "draw:") {
                    $sa = showplot($saarr, $origxmin, $settings[1], $origymin, $settings[3], $sclinglbl, $sclinggrid, $settings[6], $settings[7]);
                    $insat = strpos($sa, ');', strpos($sa, 'axes')) + 2;
                    $sa = substr($sa, 0, $insat) . str_replace("'", '"', substr($background, 5)) . substr($sa, $insat);
                } else if (!is_array($background) && $background == 'none') {
                    $sa = showasciisvg("initPicture(0,10,0,10);", $settings[6], $settings[7]);
                } else {
                    if (!is_array($background)) {
                        settype($background, "array");
                    }
                    $saarr = array_merge($background, $saarr);
                    $sa = showplot($saarr, $origxmin, $settings[1], $origymin, $settings[3], $sclinglbl, $sclinggrid, $settings[6], $settings[7]);
                    if (!empty($grid) && (strpos($xsclgridpts[0], '/') !== false || strpos($xsclgridpts[0], 'pi') !== false)) {
                        $sa = addfractionaxislabels($sa, $xsclgridpts[0]);
                    }
                }

            } else {
                $sa = showplot($saarr, $origxmin, $settings[1], $origymin, $settings[3], $sclinglbl, $sclinggrid, $settings[6], $settings[7]);
                if (!empty($grid) && (strpos($xsclgridpts[0], '/') !== false || strpos($xsclgridpts[0], 'pi') !== false)) {
                    $sa = addfractionaxislabels($sa, $xsclgridpts[0]);
                }
            }
            if ($answerformat[0] == "polygon") {
                if ($dotline == 2) {
                    $cmd = 'fill="transblue";path([[' . implode('],[', $answers) . ']]);fill="blue";';
                } else {
                    $cmd = 'stroke="blue";path([[' . implode('],[', $answers) . ']]);';
                }
                for ($i = 0; $i < count($answers) - 1; $i++) {
                    $cmd .= 'dot([' . $answers[$i] . ']);';
                }
                $sa = adddrawcommand($sa, $cmd);
            }
        }

        // Done!
        $this->answerBox = $out;
        $this->jsParams = $params;
        $this->entryTip = $tip;
        $this->correctAnswerForPart = '<div>' . $sa . '</div>';
        $this->previewLocation = $preview;
    }

    public function getAnswerBox(): string
    {
        return $this->answerBox;
    }

    public function getJsParams(): array
    {
        return $this->jsParams;
    }

    public function getEntryTip(): string
    {
        return $this->entryTip;
    }

    public function getCorrectAnswerForPart(): string
    {
        return $this->correctAnswerForPart;
    }

    public function getPreviewLocation(): string
    {
        return $this->previewLocation;
    }
}
