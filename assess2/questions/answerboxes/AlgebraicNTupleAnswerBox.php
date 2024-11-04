<?php

namespace IMathAS\assess2\questions\answerboxes;

require_once __DIR__ . '/AnswerBox.php';

use Sanitize;

class AlgebraicNTupleAnswerBox implements AnswerBox
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
        $isConditional = $this->answerBoxParams->getIsConditional();

        $out = '';
        $tip = '';
        $sa = '';
        $preview = '';
        $params = [];

        $optionkeys = ['answerboxsize', 'answerformat', 'answer', 'displayformat', 
            'ansprompt', 'readerlabel', 'hidepreview', 'variables', 'domain'];
        foreach ($optionkeys as $optionkey) {
            ${$optionkey} = getOptionVal($options, $optionkey, $multi, $partnum);
        }

        $ansformats = array_map('trim', explode(',', $answerformat));

        if (empty($answerboxsize)) {$answerboxsize = 20;}
        if ($multi) {$qn = ($qn + 1) * 1000 + $partnum;}

        if ($displayformat == 'point') {
            $tip = _('Enter your answer as an algebraic point.  Example: (x,x^2)') . "<br/>";
            $shorttip = _('Enter an algebraic point');
        } else if ($displayformat == 'pointlist') {
            $tip = _('Enter your answer a list of algebraic points separated with commas.  Example: (x,x^2), (3,4x)') . "<br/>";
            $shorttip = _('Enter a list of algebraic points');
        } else if ($displayformat == 'vector') {
            $tip = _('Enter your answer as an algebraic vector.  Example: &lt;x,x^2&gt;') . "<br/>";
            $shorttip = _('Enter an algebraic vector');
        } else if ($displayformat == 'vectorlist') {
            $tip = _('Enter your answer a list of algebraic vectors separated with commas.  Example: &lt;x,x^2&gt;, &lt;3,x&gt;') . "<br/>";
            $shorttip = _('Enter a list of algebraic vectors');
        } else if ($displayformat == 'set') {
            $tip = _('Enter your answer as a set of algebraic expressions.  Example: {x,x^2,3x}') . "<br/>";
            $shorttip = _('Enter an algebraic set');
        } else if ($displayformat == 'setlist') {
            $tip = _('Enter your answer as a list of sets separated with commas.  Example: {x,3,x^2},{2x,5}') . "<br/>";
            $shorttip = _('Enter a list of algebraic sets');
        } else if ($displayformat == 'list') {
            $tip = _('Enter your answer as a list of n-tuples of algebraic expressions separated with commas: Example: (x,x^2),(3,4x)') . "<br/>";
            $shorttip = _('Enter a list of algebraic n-tuples');
        } else {
            $tip = _('Enter your answer as an n-tuple of algebraic expressions.  Example: (x,x^2)') . "<br/>";
            $shorttip = _('Enter an algebraic n-tuple');
        }
        if (in_array('generalcomplex', $ansformats)) {
            $tip .= _('Your answer can contain complex numbers.') . '<br/>';
        }
        if (!in_array('nosoln', $ansformats) && !in_array('nosolninf', $ansformats)) {
            $tip .= _('Enter DNE for Does Not Exist');
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

        $params['calcformat'] = $answerformat . (($answerformat == '') ? '' : ',') . $displayformat;
        if ($useeqnhelper) {
            $params['helper'] = 1;
        }
        if (empty($hidepreview)) {
            $params['preview'] = !empty($_SESSION['userprefs']['livepreview']) ? 1 : 2;
        }
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
        $preview .= "<span id=p$qn></span>";

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
                if ($displayformat == 'vectorlist' || $displayformat == 'vector') {
                    $sa = str_replace(array('<', '>'), array('(:', ':)'), $sa);
                }
                $sa = numfuncPrepShowanswer($sa, $variables);
                $sa = '`' . $sa . '`';
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
