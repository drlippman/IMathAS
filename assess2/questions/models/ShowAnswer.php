<?php

namespace IMathAS\assess2\questions\models;

/**
 * Class ShowAnswer Defines allowed values for $showAnswer QuestionParams.php.
 *
 * This is used during question generation to determine when/if
 * answers should be displayed for questions.
 */
class ShowAnswer
{
    /**
     * Never show answers.
     */
    const DISABLED = 0;

    /**
     * The user must click a button to see answers.
     */
    const BUTTONS = 1;

    /**
     * Always show answers inline, without the need to click a button.
     */
    const ALWAYS = 2;
}
