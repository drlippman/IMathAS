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
        $isConditional = $this->answerBoxParams->getIsConditional();

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
                if (in_array('inequality', $ansformats)) {
                    $shorttip = _('Enter a list of algebraic equations or inequalities');
                    $tip = _('Enter a list of equations or inequalities, separated by commas.  Example: y=3x^2+1, 2+x+y=3') . "\n<br/>" . _('Be sure your variables match those in the question');
                } else {
                    $shorttip = _('Enter a list of algebraic equations');
                    $tip = _('Enter a list of equations, separated by commas.  Example: y=3x^2+1, 2+x+y=3') . "\n<br/>" . _('Be sure your variables match those in the question');
                }
            } else if (in_array('inequality', $ansformats)) {
                $shorttip = _('Enter a list of algebraic inequalities');
                $tip = _('Enter a list of inequalities, separated by commas.  Example: y<3x^2+1, 2+x+y>=3') . "\n<br/>" . _('Be sure your variables match those in the question');
            } else {
                $shorttip = _('Enter a list of algebraic expressions');
                $tip = _('Enter a list of expressions, separated by commas.  Example: 3x^2+1, x/5, 2x+1') . "\n<br/>" . _('Be sure your variables match those in the question');
            }
        } else {
            if (in_array('equation', $ansformats)) {
                if (in_array('inequality', $ansformats)) {
                    $shorttip = _('Enter an algebraic equation or inequality');
                    $tip = _('Enter your answer as an equation or inequality.  Example: y=3x^2+1, 2+x+y=3') . "\n<br/>" . _('Be sure your variables match those in the question');
                } else {
                    $shorttip = _('Enter an algebraic equation');
                    $tip = _('Enter your answer as an equation.  Example: y=3x^2+1, 2+x+y=3') . "\n<br/>" . _('Be sure your variables match those in the question');
                }
            } else if (in_array('inequality', $ansformats)) {
                $shorttip = _('Enter an algebraic inequality');
                $tip = _('Enter your answer as an inequality.  Example: y<3x^2+1, 2+x+y>=3') . "\n<br/>" . _('Be sure your variables match those in the question');
            } else {
                $shorttip = _('Enter an algebraic expression');
                $tip = _('Enter your answer as an expression.  Example: 3x^2+1, x/5, (a+b)/c') . "\n<br/>" . _('Be sure your variables match those in the question');
            }
        }
        if (in_array('generalcomplex', $ansformats)) {
            $tip .= '<br>'._('Your answer can contain complex numbers.');
        }

        if (empty($variables)) {$variables = "x";}
        $addvars = [];
        if (in_array('generalcomplex', $ansformats)) {
            $addvars[] = 'i';
        }

        list($variables, $ofunc, $newdomain, $restrictvartoint) = numfuncParseVarsDomain($variables, $domain, $addvars);

        $classes = ['text'];
        if ($colorbox != '') {
            $classes[] = $colorbox;
        }
        $attributes = [
            'type' => 'text',
            'style' => 'width:'.sizeToCSS($answerboxsize),
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
            $params['preview'] = !empty($_SESSION['userprefs']['livepreview']) ? 1 : 2;
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

        $nosolntype = 0;
        if (in_array('nosoln', $ansformats) || in_array('nosolninf', $ansformats)) {
            list($out, $answer, $nosolntype) = setupnosolninf($qn, $out, $answer, $ansformats, $la, $ansprompt, $colorbox);
        }

        if ($answer !== '' && !is_array($answer) && !$isConditional) {
            if ($nosolntype > 0) {
                $sa = $answer;
            } else {
                if ($GLOBALS['myrights'] > 10 && strpos($answer, '|') !== false) {
                    echo 'Warning: use abs(x) not |x| in $answer';
                }
                $sa = $answer;
                if (in_array('allowplusminus', $ansformats)) {
                    $sa = str_replace('+-','pm',$sa);
                }
                $sa = makeprettydisp($sa);
                
                $sa = numfuncPrepShowanswer($sa, $variables);
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
