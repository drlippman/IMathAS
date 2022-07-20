<?php

namespace IMathAS\assess2\questions\answerboxes;

require_once __DIR__ . '/AnswerBox.php';

use Sanitize;

class FunctionExpressionAnswerBox implements AnswerBox
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
        global $RND, $myrights, $useeqnhelper, $showtips, $imasroot;

        $anstype = $this->answerBoxParams->getAnswerType();
        $qn = $this->answerBoxParams->getQuestionNumber();
        $multi = $this->answerBoxParams->getIsMultiPartQuestion();
        $partnum = $this->answerBoxParams->getQuestionPartNumber();
        $la = $this->answerBoxParams->getStudentLastAnswers();
        $options = $this->answerBoxParams->getQuestionWriterVars();
        $colorbox = $this->answerBoxParams->getColorboxKeyword();
        $correctAnswerWrongFormat = $this->answerBoxParams->getCorrectAnswerWrongFormat();

        $out = '';
        $tip = '';
        $sa = '';
        $preview = '';
        $params = [];

        $optionkeys = ['ansprompt', 'variables', 'domain', 'answerboxsize',
            'hidepreview', 'answerformat', 'answer', 'readerlabel'];
        foreach ($optionkeys as $optionkey) {
            ${$optionkey} = getOptionVal($options, $optionkey, $multi, $partnum);
        }

        if (empty($answerboxsize)) {$answerboxsize = 20;}
        if ($multi) {$qn = ($qn + 1) * 1000 + $partnum;}

        if (!empty($correctAnswerWrongFormat)) {
            $rightanswrongformat = true;
            if ($colorbox == 'ansred') {
                $colorbox = 'ansorg';
            }
        }

        $ansformats = array_map('trim', explode(',', $answerformat));

        if (!empty($ansprompt) && !in_array('nosoln', $ansformats) && !in_array('nosolninf', $ansformats)) {
            $out .= $ansprompt;
        }

        if (in_array('allowplusminus', $ansformats) && !in_array('list', $ansformats)) {
            $ansformats[] = 'list';
            $answerformat = ($answerformat == '') ? 'list' : $answerformat . ',list';
            $isListAnswer = true;
        }

        if (in_array('list', $ansformats)) {
            if (in_array('equation', $ansformats)) {
                $shorttip = _('Enter a list of algebraic equations');
                $tip = _('Enter a list of equations, separated by commas.  Example: y=3x^2+1, 2+x+y=3') . "\n<br/>" . _('Be sure your variables match those in the question');
            } else if (in_array('inequality', $ansformats)) {
                $shorttip = _('Enter a list of algebraic inequalities');
                $tip = _('Enter a list of inequalities, separated by commas.  Example: y<3x^2+1, 2+x+y>=3') . "\n<br/>" . _('Be sure your variables match those in the question');
            } else {
                $shorttip = _('Enter a list of algebraic expressions');
                $tip = _('Enter a list of expressions, separated by commas.  Example: 3x^2+1, x/5, 2x+1') . "\n<br/>" . _('Be sure your variables match those in the question');
            }
        } else {
            if (in_array('equation', $ansformats)) {
                $shorttip = _('Enter an algebraic equation');
                $tip = _('Enter your answer as an equation.  Example: y=3x^2+1, 2+x+y=3') . "\n<br/>" . _('Be sure your variables match those in the question');
            } else if (in_array('inequality', $ansformats)) {
                $shorttip = _('Enter an algebraic inequality');
                $tip = _('Enter your answer as an inequality.  Example: y<3x^2+1, 2+x+y>=3') . "\n<br/>" . _('Be sure your variables match those in the question');
            } else {
                $shorttip = _('Enter an algebraic expression');
                $tip = _('Enter your answer as an expression.  Example: 3x^2+1, x/5, (a+b)/c') . "\n<br/>" . _('Be sure your variables match those in the question');
            }
        }

        if (empty($variables)) {$variables = "x";}
        $variables = array_values(array_filter(array_map('trim', explode(",", $variables)), 'strlen'));
        $ofunc = array();
        for ($i = 0; $i < count($variables); $i++) {
            $variables[$i] = trim($variables[$i]);
            if (strpos($variables[$i], '()') !== false) {
                $ofunc[] = substr($variables[$i], 0, strpos($variables[$i], '('));
                $variables[$i] = substr($variables[$i], 0, strpos($variables[$i], '('));
            }
        }

        if (!empty($domain)) {
            $fromto = array_map('trim', explode(",", $domain));
            for ($i=0; $i < count($fromto); $i++) {
                if ($fromto[$i] === 'integers') { continue; }
                else if (!is_numeric($fromto[$i])) {
                    $fromto[$i] = evalbasic($fromto[$i]);
                }
            }
        } else { 
            $fromto[0] = -10;
            $fromto[1] = 10;
        }
        if (count($fromto) == 1) {$fromto[0] = -10;
            $fromto[1] = 10;}
        $domaingroups = array();
        $i = 0;
        while ($i < count($fromto)) {
            if (isset($fromto[$i + 2]) && $fromto[$i + 2] == 'integers') {
                $domaingroups[] = array(intval($fromto[$i]), intval($fromto[$i + 1]), true);
                $i += 3;
            } else if (isset($fromto[$i + 1])) {
                $domaingroups[] = array(floatval($fromto[$i]), floatval($fromto[$i + 1]), false);
                $i += 2;
            } else {
                break;
            }
        }

        uasort($variables, 'lensort');
        $newdomain = array();
        $restrictvartoint = array();
        foreach ($variables as $i => $v) {
            if (isset($domaingroups[$i])) {
                $touse = $i;
            } else {
                $touse = 0;
            }
            $newdomain[] = $domaingroups[$touse];
        }

        $variables = array_values($variables);
        usort($ofunc, 'lensort');

        $classes = ['text'];
        if ($colorbox != '') {
            $classes[] = $colorbox;
        }
        $attributes = [
            'type' => 'text',
            'size' => $answerboxsize,
            'name' => "qn$qn",
            'id' => "qn$qn",
            'value' => $la,
            'autocomplete' => 'off',
            'aria-label' => $this->answerBoxParams->getQuestionIdentifierString() .
            (!empty($readerlabel) ? ' ' . Sanitize::encodeStringForDisplay($readerlabel) : ''),
        ];

        $params['tip'] = $shorttip;
        $params['longtip'] = $tip;
        if ($useeqnhelper) {
            $params['helper'] = 1;
        }
        if (empty($hidepreview)) {
            $params['preview'] = $_SESSION['userprefs']['livepreview'] ? 1 : 2;
        }
        $params['calcformat'] = Sanitize::encodeStringForDisplay($answerformat);
        $params['vars'] = $variables;
        $params['fvars'] = $ofunc;
        $params['domain'] = $newdomain;

        $out .= '<input ' .
        Sanitize::generateAttributeString($attributes) .
        'class="' . implode(' ', $classes) .
            '" />';

        if (empty($hidepreview)) {
            $preview .= '<button type=button class=btn id="pbtn' . $qn . '">';
            $preview .= _('Preview') . ' <span class="sr-only">' . $this->answerBoxParams->getQuestionIdentifierString() . '</span>';
            $preview .= '</button> &nbsp;';
        }
        $preview .= "<span id=p$qn></span>\n";

        if (in_array('nosoln', $ansformats) || in_array('nosolninf', $ansformats)) {
            list($out, $answer) = setupnosolninf($qn, $out, $answer, $ansformats, $la, $ansprompt, $colorbox);
        }
        if ($answer !== '' && !is_array($answer)) {
            if ($GLOBALS['myrights'] > 10 && strpos($answer, '|') !== false) {
                echo 'Warning: use abs(x) not |x| in $answer';
            }
            $sa = $answer;
            if (in_array('allowplusminus', $ansformats)) {
                $sa = str_replace('+-','pm',$sa);
            }
            $sa = makeprettydisp($sa);
            $greekletters = array('alpha', 'beta', 'chi', 'delta', 'epsilon', 'gamma', 'varphi', 'phi', 'psi', 'sigma', 'rho', 'theta', 'lambda', 'mu', 'nu', 'omega');

            for ($i = 0; $i < count($variables); $i++) {
                if (strlen($variables[$i]) > 1) {
                    $isgreek = false;
                    $varlower = strtolower($variables[$i]);
                    for ($j = 0; $j < count($greekletters); $j++) {
                        if ($varlower == $greekletters[$j]) {
                            $isgreek = true;
                            break;
                        }
                    }
                    if (!$isgreek && preg_match('/^(\w+)_(\w+|\(.*?\))$/', $variables[$i], $matches)) {
                        $chg = false;
                        if (strlen($matches[1]) > 1) {
                            $matches[1] = '"' . $matches[1] . '"';
                            $chg = true;
                        }
                        if (strlen($matches[2]) > 1 && $matches[2][0] != '(') {
                            $matches[2] = '"' . $matches[2] . '"';
                            $chg = true;
                        }
                        if ($chg) {
                            $sa = str_replace($matches[0], $matches[1] . '_' . $matches[2], $sa);
                        }
                    } else if (!$isgreek) {
                        $sa = str_replace($variables[$i], '"' . $variables[$i] . '"', $sa);
                    }
                }
            }
        }

        // Done!
        $this->answerBox = $out;
        $this->jsParams = $params;
        $this->entryTip = $tip;
        $this->correctAnswerForPart = (string) $sa;
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
