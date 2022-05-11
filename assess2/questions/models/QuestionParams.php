<?php

namespace IMathAS\assess2\questions\models;

/**
 * Class QuestionParams Contains all parameters for generating a question for
 *                      display to a user. Used by QuestionGenerator.php.
 */
class QuestionParams
{
    private $dbQuestionSetId;   // Orig: $qidx
    private $questionNumber;    // Orig: $qnidx
    private $displayQuestionNumber;
    private $assessmentId = 0;
    private $questionId = 0; // imas_questions.id
    private $questionSeed;      // Orig: $seed
    private $questionData;      // Obtained from imas_questionset; optional

    private $allQuestionAnswers;        // Orig: $GLOBALS['stuanswers']
    private $allQuestionAnswersAsNum;   // Orig: $GLOBALS['stuanswersval']
    private $allQuestionAutosaves;      // related to stuanswers
    private $studentAttemptNumber;
    private $studentPartAttemptCount;
    private $seqPartDone;
    private $lastRawScores;     // All of a student's scores on their last attempt.
    private $correctAnswerWrongFormat;
    private $printFormat = false;

    // Orig: $doshowans - int, from displayq2.php
    private $showAnswer;    // @see ShowAnswer.php
    private $showAnswerParts;
    // Orig: $nosabutton - bool, from displayq2.php
    private $showAnswerButton;

    // Orig: $showhints - boolean, from displayq2.php
    private $showHints;

    // These must made available to the question code to be eval'd.
    // Orig: $scorenonzero - hashmap, from macros.php:getscorenonzero() - Used in question eval.
    private $scoreNonZero;
    // Orig: $scoreiscorrect - hashmap, from macros.php:getiscorrect() - Used in question eval.
    private $scoreIsCorrect;
    private $teacherInGb = false;

    /**
     * Get the question's database row ID from imas_questionset.
     *
     * @return int
     */
    public function getDbQuestionSetId(): ?int
    {
        return $this->dbQuestionSetId;
    }

    /**
     * Set the question's database row ID from imas_questionset.
     *
     * @param int $dbQuestionSetId
     * @return QuestionParams
     */
    public function setDbQuestionSetId(?int $dbQuestionSetId): QuestionParams
    {
        $this->dbQuestionSetId = $dbQuestionSetId;
        return $this;
    }

    /**
     * Get this question's number.
     *
     * This IS the number that is visible to students and instructors.
     * This is NOT the imas_questionset row ID.
     *
     * @return int
     */
    public function getQuestionNumber(): ?int
    {
        return $this->questionNumber;
    }

    /**
     * Get this question's number.
     *
     * This IS the number that is visible to students and instructors.
     * This is NOT the imas_questionset row ID.
     *
     * @return int
     */
    public function getDisplayQuestionNumber(): ?int
    {
        return $this->displayQuestionNumber;
    }

    /**
     * Set this question's number.
     *
     * This IS the number that is visible to students and instructors.
     * This is NOT the imas_questionset row ID.
     *
     * @param int $questionNumber
     * @return QuestionParams
     */
    public function setQuestionNumber(?int $questionNumber): QuestionParams
    {
        $this->questionNumber = $questionNumber;
        $this->displayQuestionNumber = $questionNumber;
        return $this;
    }

    /**
     * Set this question's display number.
     *
     * This IS the number that is visible to students and instructors.
     * This is NOT the imas_questionset row ID.
     *
     * @param int $displayQuestionNumber
     * @return QuestionParams
     */
    public function setDisplayQuestionNumber(?int $displayQuestionNumber): QuestionParams
    {
        $this->displayQuestionNumber = $displayQuestionNumber;
        return $this;
    }

    /**
     * Get the assessment ID
     *
     * @return int The assessment ID.
     */
    public function getAssessmentId(): ?int
    {
        return $this->assessmentId;
    }

    /**
     * Set the assessment ID.
     *
     * @param int $assessmentId The assessment ID.
     * @return QuestionParams
     */
    public function setAssessmentId(?int $assessmentId): QuestionParams
    {
        $this->assessmentId = $assessmentId;
        return $this;
    }

    /**
     * Get the imas_questions.id ID
     *
     * @return int The question ID.
     */
    public function getQuestionId(): ?int
    {
        return $this->questionId;
    }

    /**
     * Set the question ID.
     *
     * @param int $questionId The question ID.
     * @return QuestionParams
     */
    public function setQuestionId(?int $questionId): QuestionParams
    {
        $this->questionId = $questionId;
        return $this;
    }

    /**
     * Get this question's RNG seed.
     *
     * @return int
     */
    public function getQuestionSeed(): ?int
    {
        return $this->questionSeed;
    }

    /**
     * Set this question's RNG seed.
     *
     * @param int $questionSeed
     * @return QuestionParams
     */
    public function setQuestionSeed(?int $questionSeed): QuestionParams
    {
        $this->questionSeed = $questionSeed;
        return $this;
    }

    /**
     * Get the question's data.
     *
     * This is an array of columns from imas_questionset for this question.
     *
     * @return array
     */
    public function getQuestionData(): ?array
    {
        return $this->questionData;
    }

    /**
     * Set the question's data.
     *
     * This is an array of columns from imas_questionset for this question.
     *
     * If left null, question data will be loaded from imas_questionset.
     *
     * @param array $questionData
     * @return QuestionParams
     */
    public function setQuestionData(?array $questionData): QuestionParams
    {
        $this->questionData = $questionData;
        return $this;
    }

    /**
     * Get all of the student's answers to ALL questions. (as entered)
     *
     * @return array
     */
    public function getAllQuestionAnswers(): ?array
    {
        return $this->allQuestionAnswers;
    }

    /**
     * Set all of the student's answers to ALL questions. (as entered)
     *
     * @param array $allQuestionAnswers
     * @return QuestionParams
     */
    public function setAllQuestionAnswers(?array $allQuestionAnswers): QuestionParams
    {
        $this->allQuestionAnswers = $allQuestionAnswers;
        return $this;
    }

    /**
     * Get all of the student's answers to ALL questions. (as floats)
     *
     * @return array
     */
    public function getAllQuestionAnswersAsNum(): ?array
    {
        return $this->allQuestionAnswersAsNum;
    }

    /**
     * Set all of the student's answers to ALL questions. (as floats)
     *
     * @param array $allQuestionAnswersAsNum
     * @return QuestionParams
     */
    public function setAllQuestionAnswersAsNum(?array $allQuestionAnswersAsNum): QuestionParams
    {
        $this->allQuestionAnswersAsNum = $allQuestionAnswersAsNum;
        return $this;
    }

    /**
     * Get all of the student's autosaves to ALL questions. (as entered)
     *
     * @return array
     */
    public function getAllQuestionAutosaves(): ?array
    {
        return $this->allQuestionAutosaves;
    }

    /**
     * Set all of the student's autosaves to ALL questions. (as entered)
     *
     * @param array $allQuestionAutosaves
     * @return QuestionParams
     */
    public function setAllQuestionAutosaves(?array $allQuestionAutosaves): QuestionParams
    {
        $this->allQuestionAutosaves = $allQuestionAutosaves;
        return $this;
    }

    /**
     * @return int
     */
    public function getStudentAttemptNumber(): ?int
    {
        return $this->studentAttemptNumber;
    }

    /**
     * @param int $studentAttemptNumber
     * @return QuestionParams
     */
    public function setStudentAttemptNumber(?int $studentAttemptNumber): QuestionParams
    {
        $this->studentAttemptNumber = $studentAttemptNumber;
        return $this;
    }

    /**
     * The number of times each question part has been attempted.
     *
     * @return array
     */
    public function getStudentPartAttemptCount(): ?array
    {
        return $this->studentPartAttemptCount;
    }

    /**
     * The number of times each question part has been attempted.
     *
     * @param array $studentPartAttemptCount
     * @return QuestionParams
     */
    public function setStudentPartAttemptCount(?array $studentPartAttemptCount): QuestionParams
    {
        $this->studentPartAttemptCount = $studentPartAttemptCount;
        return $this;
    }

    /**
     * Whether the part is "done" for purposes of showing the next in sequence
     *
     * @return array
     */
    public function getSeqPartDone()
    {
        return $this->seqPartDone;
    }

    /**
     * Whether the part is "done" for purposes of showing the next in sequence
     *
     * @param array $seqPartDone
     * @return QuestionParams
     */
    public function setSeqPartDone($seqPartDone): QuestionParams
    {
        $this->seqPartDone = $seqPartDone;
        return $this;
    }

    /**
     * Get the student's last scores for THIS question. (as raw values)
     *
     * This includes all parts for the question.
     *
     * @return array
     */
    public function getLastRawScores(): ?array
    {
        return $this->lastRawScores;
    }

    /**
     * Get the student's last scores for THIS question. (as raw values)
     *
     * This includes all parts for the question.
     *
     * @param array $lastRawScores
     * @return QuestionParams
     */
    public function setLastRawScores(?array $lastRawScores): QuestionParams
    {
        $this->lastRawScores = $lastRawScores;
        return $this;
    }

    /**
     * Defines whether answers are displayed. @see ShowAnswer
     *
     * Originally "$doshowans" from displayq2.php.
     *
     * @return int
     * @see ShowAnswer
     */
    public function getShowAnswer(): ?int
    {
        return $this->showAnswer;
    }

    /**
     * Defines whether answers are displayed. @see ShowAnswer
     *
     * Originally "$doshowans" from displayq2.php.
     *
     * @param int $showAnswer
     * @return QuestionParams
     * @see ShowAnswer
     */
    public function setShowAnswer(?int $showAnswer): QuestionParams
    {
        $this->showAnswer = $showAnswer;
        return $this;
    }

    /**
     * Defines whether answers for parts are shown
     *
     * @param array $showAnswerParts
     * @return QuestionParams
     * @see ShowAnswer
     */
    public function setShowAnswerParts(?array $showAnswerParts): QuestionParams
    {
        $this->showAnswerParts = $showAnswerParts;
        return $this;
    }

    /**
     * Defines whether answers for parts are shown
     *
     * @return array
     * @see ShowAnswer
     */
    public function getShowAnswerParts(): ?array
    {
        return $this->showAnswerParts;
    }

    /**
     * When showing the question answer, should the Answer button be
     * displayed (true), or * should we just display the answer (false)?
     *
     * Originally "$nosabutton" from displayq2.php
     *
     * @return bool
     */
    public function getShowAnswerButton(): ?bool
    {
        return $this->showAnswerButton;
    }

    /**
     * When showing the question answer, should the Answer button be
     * displayed (true), or * should we just display the answer (false)?
     *
     * Originally "$nosabutton" from displayq2.php
     *
     * @param bool $showAnswerButton
     * @return QuestionParams
     */
    public function setShowAnswerButton(?bool $showAnswerButton): QuestionParams
    {
        $this->showAnswerButton = $showAnswerButton;
        return $this;
    }

    /**
     * Show question hints? Bitwise: 1 hints, 2 help buttons
     *
     * @return int
     */
    public function getShowHints(): ?int
    {
        return $this->showHints;
    }

    /**
     * Show question hints?  Bitwise: 1 hints, 2 help buttons
     *
     * @param int $showHints
     * @return QuestionParams
     */
    public function setShowHints(?int $showHints): QuestionParams
    {
        $this->showHints = $showHints;
        return $this;
    }

    /**
     * Get the value calculated by macros.php:getscorenonzero(). This is used
     * during question code eval.
     *
     * @return array
     */
    public function getScoreNonZero(): ?array
    {
        return $this->scoreNonZero;
    }

    /**
     * Set the value calculated by macros.php:getscorenonzero(). This is used
     * during question code eval.
     *
     * @param array $scoreNonZero
     * @return QuestionParams
     */
    public function setScoreNonZero(?array $scoreNonZero): QuestionParams
    {
        $this->scoreNonZero = $scoreNonZero;
        return $this;
    }

    /**
     * Get the value calculated by macros.php:getiscorrect(). This is used
     * during question code eval.
     *
     * @return array
     */
    public function getScoreIsCorrect(): ?array
    {
        return $this->scoreIsCorrect;
    }

    /**
     * Set the value calculated by macros.php:getiscorrect(). This is used
     * during question code eval.
     *
     * @param array $scoreIsCorrect
     * @return QuestionParams
     */
    public function setScoreIsCorrect(?array $scoreIsCorrect): QuestionParams
    {
        $this->scoreIsCorrect = $scoreIsCorrect;
        return $this;
    }

    /**
     * Get whether each part is right answer but wrong format.
     *
     * @return array
     */
    public function getCorrectAnswerWrongFormat(): ?array
    {
        return $this->correctAnswerWrongFormat;
    }

    /**
     * Set whether each part is right answer but wrong format.
     *
     * @param array $correctAnswerWrongFormat
     * @return QuestionParams
     */
    public function setCorrectAnswerWrongFormat(?array $correctAnswerWrongFormat): QuestionParams
    {
        $this->correctAnswerWrongFormat = $correctAnswerWrongFormat;
        return $this;
    }

    /**
     * Get whether should be formatted for print.
     *
     * @return array
     */
    public function getPrintFormat(): bool
    {
        return $this->printFormat;
    }

    /**
     * Set whether should be formatted for print.
     *
     * @param bool $printFormat
     * @return QuestionParams
     */
    public function setPrintFormat(bool $printFormat): QuestionParams
    {
        $this->printFormat = $printFormat;
        return $this;
    }

    /**
     * Get whether is teacher in gradebook.
     *
     * @return array
     */
    public function getTeacherInGb(): bool
    {
        return $this->teacherInGb;
    }

    /**
     * Set whether is teacher in gradebook.
     *
     * @param bool $teacherInGb
     * @return QuestionParams
     */
    public function setTeacherInGb(bool $teacherInGb): QuestionParams
    {
        $this->teacherInGb = $teacherInGb;
        return $this;
    }
}
