<?php

namespace IMathAS\assess2\questions\answerboxes;

require_once __DIR__ . '/AnswerBox.php';

use Sanitize;

class MoleculeAnswerBox implements AnswerBox
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

        $out = '';
        $tip = '';
        $sa = '';
        $preview = '';
        $params = [];

        $optionkeys = ['answer','showanswer','displayformat'];
        foreach ($optionkeys as $optionkey) {
            ${$optionkey} = getOptionVal($options, $optionkey, $multi, $partnum);
        }
        $questions = getOptionVal($options, 'questions', $multi, $partnum, 2);

        if ($multi) {$qn = ($qn + 1) * 1000 + $partnum;}

        $forcea11y = false;
        if ($la !== '') {
            $laparts = explode('~~~', $la);
            if (count($laparts) < 2) {
                $forcea11y = true;
            }
        } else {
            $laparts = ['',''];
        }
 
        $ansparts = explode('~~~', $answer);

        if ($_SESSION['userprefs']['drawentry'] == 0 || $forcea11y) { //accessible entry
            $classes = ['text'];
            if ($colorbox != '') {
                $classes[] = $colorbox;
            }
            $attributes = [
                'type' => 'text',
                'size' => 20,
                'name' => "qn$qn",
                'id' => "qn$qn",
                'value' => $laparts[0],
                'autocomplete' => 'off',
                'aria-label' => _('Enter the molecule in SMILES format'),
            ];

            $out .= '<input ' .
            Sanitize::generateAttributeString($attributes) .
            'class="' . implode(' ', $classes) .
                '" />';

            $sa .= _('Answer in SMILES format:') . $ansparts[0];
        } else {
            $out .= '<div id="qnwrap' . $qn . '">';
            $out .= '<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/kekule@1.0.2/dist/kekule.min.js?module=chemWidget,IO"></script>';
            $out .= '<script type="text/javascript">
                if (!$("link[href=\'https://cdn.jsdelivr.net/npm/kekule@1.0.2/dist/themes/default/kekule.min.css\']").length) {
                    $(\'<link href="https://cdn.jsdelivr.net/npm/kekule@1.0.2/dist/themes/default/kekule.min.css" rel="stylesheet">\').appendTo("head");
                }
            </script>';

            $classes = [];
            if ($colorbox != '') {
                $classes[] = $colorbox;
            }

            $attributes = [
                'type' => 'hidden',
                'name' => "qn$qn",
                'id' => "qn$qn",
                'value' => $laparts[0],
                'autocomplete' => 'off'
            ];

            $out .= '<input ' .
                Sanitize::generateAttributeString($attributes) .
                '" />';
            $out .= '<div id="chemdraw'.$qn.'" class="' . implode(' ', $classes) .'"></div>';
            if ($la !== '') {
                $params['chemla'] = html_entity_decode($laparts[1]);
            }
            $out .= '</div>';
            $params['displayformat'] = $displayformat;

            if (count($ansparts)<2) {
                $sa .= _('Answer in SMILES format:') . $ansparts[0];
            } else {
                $sa .= '<div id="chemsa'.$qn.'" data-cmldata="'.Sanitize::encodeStringForDisplay($ansparts[1]).'"></div>';
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
