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
    public function getSpecificAnswerFeedbackTestPresentation($questionId, $questionIndex, $answerIndex): string
    {
        return "";
    }

    public function completeSpecificFormProperties(ilPropertyFormGUI $form): void
    {
    }

    public function initSpecificFormProperties(ilPropertyFormGUI $form): void
    {
    }

    public function saveSpecificFormProperties(ilPropertyFormGUI $form): void
    {
    }

    public function getSpecificAnswerFeedbackContent($questionId, $questionIndex, $answerIndex): string
    {
        return "";
    }

    public function getAllSpecificAnswerFeedbackContents($questionId): string
    {
        return "";
    }

    public function saveSpecificAnswerFeedbackContent($questionId, $questionIndex, $answerIndex, $feedbackContent): int
    {
    }

    public function deleteSpecificAnswerFeedbacks($questionId, $isAdditionalContentEditingModePageObject): void
    {
    }

    protected function duplicateSpecificFeedback($originalQuestionId, $duplicateQuestionId): void
    {
    }

    protected function isSpecificAnswerFeedbackId($feedbackId): bool
    {
        return false;
    }

    protected function syncSpecificFeedback($originalQuestionId, $duplicateQuestionId): void
    {
    }

    public function getSpecificAnswerFeedbackExportPresentation($questionId, $questionIndex, $answerIndex): string
    {
        return "";
    }

    public function importSpecificAnswerFeedback($questionId, $questionIndex, $answerIndex, $feedbackContent): void
    {
    }
}
