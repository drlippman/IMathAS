<?php

namespace IMathAS\assess2\questions\answerboxes;

/**
 * Class AnswerBoxParams Contains data required by answer type specific
 *                       answer box generatoors.
 */
class AnswerBoxParams
{
    private $questionWriterVars;
    // FIXME: The answer box generators don't appear to be using this.
    private $varsForAnswerBoxGenerator;

    private $answerType;
    private $questionNumber;
    private $isMultiPartQuestion;
    private $isConditional;
    private $questionPartNumber;
    private $questionPartCount = 1;
    private $assessmentId = 0;
    private $studentLastAnswers;
    private $correctAnswerWrongFormat = false;
    private $colorboxKeyword;

    /**
     * Get the variables created by the question writer's code.
     *
     * @return array
     * @see QuestionHtmlGenerator::ALLOWED_QUESTION_WRITER_VARS
     */
    public function getQuestionWriterVars(): ?array
    {
        return $this->questionWriterVars;
    }

    /**
     * Set the variables created by the question writer's code.
     *
     * @param array $questionWriterVars
     * @return AnswerBoxParams
     * @see QuestionHtmlGenerator::ALLOWED_QUESTION_WRITER_VARS
     */
    public function setQuestionWriterVars(?array $questionWriterVars): AnswerBoxParams
    {
        $this->questionWriterVars = $questionWriterVars;
        return $this;
    }

    /**
     * Get additional vars used by the answer box generator.
     *
     * These are currently packaged up and provided by QuestionHtmlGenerator.
     *
     * @return array
     * @see QuestionHtmlGenerator::VARS_FOR_ANSWERBOX_GENERATOR
     */
    public function getVarsForAnswerBoxGenerator(): ?array
    {
        return $this->varsForAnswerBoxGenerator;
    }

    /**
     * Set additional vars used by the answer box generator.
     *
     * These are currently packaged up and provided by QuestionHtmlGenerator.
     *
     * @param array $varsForAnswerBoxGenerator
     * @return AnswerBoxParams
     * @see QuestionHtmlGenerator::VARS_FOR_ANSWERBOX_GENERATOR
     */
    public function setVarsForAnswerBoxGenerator(?array $varsForAnswerBoxGenerator): AnswerBoxParams
    {
        $this->varsForAnswerBoxGenerator = $varsForAnswerBoxGenerator;
        return $this;
    }

    /**
     * Get the question's answer type. (@see AnswerBoxFactory)
     *
     * @return string
     */
    public function getAnswerType(): ?string
    {
        return $this->answerType;
    }

    /**
     * Set the question's answer type. (@see AnswerBoxFactory)
     *
     * @param string $answerType
     * @return AnswerBoxParams
     */
    public function setAnswerType(?string $answerType): AnswerBoxParams
    {
        $this->answerType = $answerType;
        return $this;
    }

    /**
     * Get the question number. (NOT the database row ID)
     *
     * @return int
     */
    public function getQuestionNumber(): ?int
    {
        return $this->questionNumber;
    }

    /**
     * Set the question number. (NOT the database row ID)
     *
     * @param int $questionNumber
     * @return AnswerBoxParams
     */
    public function setQuestionNumber(?int $questionNumber): AnswerBoxParams
    {
        $this->questionNumber = $questionNumber;
        return $this;
    }

    /**
     * Set the count of parts in this question
     *
     * @param int $questionPartCount
     * @return AnswerBoxParams
     */
    public function setQuestionPartCount(?int $questionPartCount): AnswerBoxParams
    {
        $this->questionPartCount = $questionPartCount;
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
     * @return AnswerBoxParams
     */
    public function setAssessmentId(?int $assessmentId): AnswerBoxParams
    {
        $this->assessmentId = $assessmentId;
        return $this;
    }

    /**
     * Get the question multipart status. (true = yes, false = no)
     *
     * @return bool
     */
    public function getIsMultiPartQuestion(): ?bool
    {
        return $this->isMultiPartQuestion;
    }

    /**
     * Set the question multipart status. (true = yes, false = no)
     *
     *
     * @param bool $isMultiPartQuestion
     * @return AnswerBoxParams
     */
    public function setIsMultiPartQuestion(?bool $isMultiPartQuestion)
    {
        $this->isMultiPartQuestion = $isMultiPartQuestion;
        return $this;
    }

    /**
     * Get the question conditional status. (true = yes, false = no)
     *
     * @return bool
     */
    public function getIsConditional(): ?bool
    {
        return $this->isConditional;
    }

    /**
     * Set the question conditional status. (true = yes, false = no)
     *
     *
     * @param bool $isConditional
     * @return AnswerBoxParams
     */
    public function setIsConditional(?bool $isConditional)
    {
        $this->isConditional = $isConditional;
        return $this;
    }

    /**
     * Get the question identifier string
     *
     * @return string
     */
    public function getQuestionIdentifierString(): ?string
    {
        $str = sprintf(_('Question %d'), $this->questionNumber + 1);
        if ($this->isMultiPartQuestion && $this->questionPartCount > 1) {
          $str .= ' ' . sprintf(_('Part %d of %d'), $this->questionPartNumber + 1,
            $this->questionPartCount);
        }
        return $str;
    }

    /**
     * Get the question part number, if this is for a multpart question.
     *
     * @return int
     */
    public function getQuestionPartNumber(): ?int
    {
        return $this->questionPartNumber;
    }

    /**
     * Set the question part number, if this is for a multpart question.
     *
     * @param int $questionPartNumber
     * @return AnswerBoxParams
     */
    public function setQuestionPartNumber(?int $questionPartNumber): AnswerBoxParams
    {
        $this->questionPartNumber = $questionPartNumber;
        return $this;
    }

    /**
     * Get the student's last answers.
     *
     * @return mixed
     */
    public function getStudentLastAnswers()
    {
        return $this->studentLastAnswers;
    }

    /**
     * Set the student's last answers.
     *
     * @param mixed $studentLastAnswers
     * @return AnswerBoxParams
     */
    public function setStudentLastAnswers($studentLastAnswers): AnswerBoxParams
    {
        $this->studentLastAnswers = $studentLastAnswers;
        return $this;
    }

    /**
     * Get whether it was the correct answer but wrong format.
     *
     * @return bool
     */
    public function getCorrectAnswerWrongFormat()
    {
        return $this->correctAnswerWrongFormat;
    }

    /**
     * Set whether it was the correct answer but wrong format.
     *
     * @param bool $correctAnswerWrongFormat
     * @return AnswerBoxParams
     */
    public function setCorrectAnswerWrongFormat($correctAnswerWrongFormat): AnswerBoxParams
    {
        $this->correctAnswerWrongFormat = $correctAnswerWrongFormat;
        return $this;
    }

    /**
     * Get the color box keyword.
     *
     * This value is used when determining which icon to display and how
     * to color the answer box when checking a student's answer.
     *
     * Currently one of: ansgrn, ansyel, ansred, ansorg
     *
     * @return string
     */
    public function getColorboxKeyword(): ?string
    {
        return $this->colorboxKeyword;
    }

    /**
     * Set the color box keyword.
     *
     * This value is used when determining which icon to display and how
     * to color the answer box when checking a student's answer.
     *
     * Currently one of: ansgrn, ansyel, ansred, ansorg
     *
     * @param string $colorboxKeyword
     * @return AnswerBoxParams
     */
    public function setColorboxKeyword(?string $colorboxKeyword)
    {
        $this->colorboxKeyword = $colorboxKeyword;
        return $this;
    }
}
