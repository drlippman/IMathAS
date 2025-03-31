<?php

namespace IMathAS\assess2\questions\answerboxes;

require_once __DIR__ . '/AnswerBox.php';

use Sanitize;

class MatrixAnswerBox implements AnswerBox
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

        $optionkeys = ['ansprompt', 'answersize', 'answerboxsize', 'answerformat',
            'answer', 'reqdecimals', 'displayformat', 'readerlabel'];
        foreach ($optionkeys as $optionkey) {
            ${$optionkey} = getOptionVal($options, $optionkey, $multi, $partnum);
        }

        $ansformats = array_map('trim', explode(',', $answerformat));
        $dispformats = array_map('trim', explode(',', $displayformat));

        if ($multi) {$qn = ($qn + 1) * 1000 + $partnum;}

        if (!empty($ansprompt) && !in_array('nosoln', $ansformats) && !in_array('nosolninf', $ansformats)) {
            $out .= $ansprompt;
        }
        if (!empty($answersize)) {
            if ($anstype === 'complexmatrix') {
                if (in_array('allowjcomplex', $ansformats)) {
                    $tip = _('Enter each element of the matrix as a complex number in a+bj form.  Example: -3-4j') . "<br/>";
                } else {
                    $tip = _('Enter each element of the matrix as a complex number in a+bi form.  Example: -3-4i') . "<br/>";
                }
                $shorttip = _('Enter a complex number');
            } else {
                $tip = _('Enter each element of the matrix as number (like 5, -3, 2.2)');
                $shorttip = _('Enter an integer or decimal number');
            }
            if ($reqdecimals!=='') {
                list($reqdecimals, $exactreqdec, $reqdecoffset, $reqdecscoretype) = parsereqsigfigs($reqdecimals);

                $tip .= "<br/>" . sprintf(_('Your numbers should be accurate to %d decimal places.'), $reqdecimals);
                $shorttip .= sprintf(_(", accurate to at least %d decimal places"), $reqdecimals);
            }
            if (empty($answerboxsize)) {$answerboxsize = 3;}
            if (in_array('inline', $dispformats)) {
                $style = ' style="display:inline-block;vertical-align:middle"';
            } else {
                $style = '';
            }
            if ($colorbox == '') {
                $out .= '<div id="qnwrap' . $qn . '"' . $style . '>';
            } else {
                $out .= '<div class="' . $colorbox . '" id="qnwrap' . $qn . '"' . $style . '>';
            }
            $answersize = explode(",", $answersize);
            $arialabel = $this->answerBoxParams->getQuestionIdentifierString() .
                ' ' . sprintf(_('matrix entry with %d rows and %d columns'), $answersize[0], $answersize[1]) .
                (!empty($readerlabel) ? ' ' . Sanitize::encodeStringForDisplay($readerlabel) : '');
            $out .= '<table role="group" aria-label="' . $arialabel . '">';
            if (in_array('det', $dispformats)) {
                $out .= '<tr><td class="matrixdetleft">&nbsp;</td><td style="padding:0px">';
            } else {
                $out .= '<tr><td class="matrixleft">&nbsp;</td><td style="padding:0px">';
            }
            if (isset($GLOBALS['capturechoices'])) {
                $GLOBALS['answersize'][$qn] = $answersize;
            }
            $out .= "<table>";
            $count = 0;
            $las = explode("|", $la);
            $cellcnt = $answersize[0] * $answersize[1];
            $augcolumn = -1;
            if (in_array('augmented', $dispformats)) {
                $augcolumn = $answersize[1] - 1;
            }
            for ($row = 0; $row < $answersize[0]; $row++) {
                $out .= "<tr>";
                for ($col = 0; $col < $answersize[1]; $col++) {
                    if ($col == $augcolumn) {
                        $out .= '<td style="border-left: 1px solid #000">';
                    } else {
                        $out .= '<td>';
                    }
                    $attributes = [
                        'type' => 'text',
                        'style' => 'width:'.sizeToCSS($answerboxsize),
                        'name' => "qn$qn-$count",
                        'id' => "qn$qn-$count",
                        'value' => ($las[$count] ?? ''),
                        'autocomplete' => 'off',
                    ];

                    $out .= '<input ' .
                        Sanitize::generateAttributeString($attributes) .
                        '" />';

                    $out .= "</td>\n";
                    $count++;
                }
                $out .= "</tr>";
            }
            $out .= '</table>';
            if (in_array('det', $dispformats)) {
                $out .= '</td><td class="matrixdetright">&nbsp;</td></tr></table>';
            } else {
                $out .= '</td><td class="matrixright">&nbsp;</td></tr></table>';
            }
            $out .= "</div>\n";
            $preview .= "<span id=p$qn></span>";
            $params['matrixsize'] = $answersize;
            $params['calcformat'] = $answerformat . ($answerformat == '' ? '' : ',') . 'decimal';
            $params['tip'] = $shorttip;
            $params['longtip'] = $tip;
        } else {
            if ($multi == 0) {
                $qnref = "$qn-0";
            } else {
                $qnref = ($multi - 1) . '-' . ($qn % 1000);
            }
            if (empty($answerboxsize)) {$answerboxsize = 20;}
            if ($anstype === 'complexmatrix') {
                if (in_array('allowjcomplex', $ansformats)) {
                    $tip = _('Enter your answer as a matrix filled with complex numbers in a+bj form, like [(2,3+j),(1-j,j)]') . "<br/>";
                } else {
                    $tip = _('Enter your answer as a matrix filled with complex numbers in a+bi form, like [(2,3+i),(1-i,i)]') . "<br/>";
                }
                $shorttip = _('Enter a matrix of complex numbers');    
            } else {
                $shorttip = _('Enter a matrix of integer or decimal numbers');
                $tip = _('Enter your answer as a matrix filled with integer or decimal numbers, like [(2,3,4),(3,4,5)]');
            }
            if ($reqdecimals !== '') {
                list($reqdecimals, $exactreqdec, $reqdecoffset, $reqdecscoretype) = parsereqsigfigs($reqdecimals);
                $tip .= "<br/>" . sprintf(_('Your numbers should be accurate to %d decimal places.'), $reqdecimals);
            }

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

            $out .= '<input ' .
            Sanitize::generateAttributeString($attributes) .
            'class="' . implode(' ', $classes) .
                '" />';

            $params['calcformat'] = $answerformat;
            if (empty($hidepreview)) {
                if ($useeqnhelper) {
                    $params['helper'] = 1;
                    $params['calcformat'] = $answerformat . ($answerformat == '' ? '' : ',') . 'decimal';
                }
                $params['preview'] = 1;
                $preview .= '<button type=button class=btn id="pbtn' . $qn . '">';
                $preview .= _('Preview') . ' <span class="sr-only">' . $this->answerBoxParams->getQuestionIdentifierString() . '</span>';
                $preview .= '</button> &nbsp;';
            }
            $preview .= "<span id=p$qn></span> ";
        }

        $nosolntype = 0;
        if (in_array('nosoln', $ansformats) || in_array('nosolninf', $ansformats)) {
            list($out, $answer, $nosolntype) = setupnosolninf($qn, $out, $answer, $ansformats, $la, $ansprompt, $colorbox);
        }

        if ($answer !== '' && !is_array($answer) && !$isConditional) {
            if ($nosolntype > 0) {
                $sa = $answer;
            } else {
                $sa = '`' . $answer . '`';
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
