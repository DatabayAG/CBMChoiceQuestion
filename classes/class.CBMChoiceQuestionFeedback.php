<?php

declare(strict_types=1);
/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

/**
 * Class ilCBMChoiceQuestionFeedback
 * @author Marvin Beym <mbeym@databay.de>
 */
class CBMChoiceQuestionFeedback extends ilAssQuestionFeedback
{
    /**
     * @inheritDoc
     */
    public function getSpecificAnswerFeedbackTestPresentation($questionId, $questionIndex, $answerIndex)
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function completeSpecificFormProperties(ilPropertyFormGUI $form)
    {
    }

    /**
     * @inheritDoc
     */
    public function initSpecificFormProperties(ilPropertyFormGUI $form)
    {
    }

    /**
     * @inheritDoc
     */
    public function saveSpecificFormProperties(ilPropertyFormGUI $form)
    {
    }

    /**
     * @inheritDoc
     */
    public function getSpecificAnswerFeedbackContent($questionId, $questionIndex, $answerIndex)
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getAllSpecificAnswerFeedbackContents($questionId)
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function saveSpecificAnswerFeedbackContent($questionId, $questionIndex, $answerIndex, $feedbackContent)
    {
    }

    /**
     * @inheritDoc
     */
    public function deleteSpecificAnswerFeedbacks($questionId, $isAdditionalContentEditingModePageObject)
    {
    }

    /**
     * @inheritDoc
     */
    protected function duplicateSpecificFeedback($originalQuestionId, $duplicateQuestionId)
    {
    }

    /**
     * @inheritDoc
     */
    protected function isSpecificAnswerFeedbackId($feedbackId)
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    protected function syncSpecificFeedback($originalQuestionId, $duplicateQuestionId)
    {
    }

    /**
     * @inheritDoc
     */
    public function getSpecificAnswerFeedbackExportPresentation($questionId, $questionIndex, $answerIndex)
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function importSpecificAnswerFeedback($questionId, $questionIndex, $answerIndex, $feedbackContent)
    {
    }
}
