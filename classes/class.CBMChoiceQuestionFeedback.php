<?php

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

declare(strict_types=1);

class CBMChoiceQuestionFeedback extends ilAssQuestionFeedback
{
    public function getSpecificAnswerFeedbackTestPresentation(int $question_id, int $question_index, int $answer_index): string
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

    public function getSpecificAnswerFeedbackContent(int $question_id, int $question_index, int $answer_index): string
    {
        return "";
    }

    public function getAllSpecificAnswerFeedbackContents(int $question_id): string
    {
        return "";
    }

    public function saveSpecificAnswerFeedbackContent(int $question_id, int $question_index, int $answer_index, string $feedback_content): int
    {
        return 0;
    }

    public function deleteSpecificAnswerFeedbacks(int $question_id, bool $isAdditionalContentEditingModePageObject): void
    {
    }

    protected function duplicateSpecificFeedback(int $originalQuestionId, int $duplicateQuestionId): void
    {
    }

    protected function isSpecificAnswerFeedbackId(int $feedbackId): bool
    {
        return false;
    }

    protected function syncSpecificFeedback(int $originalQuestionId, int $duplicateQuestionId): void
    {
    }

    public function getSpecificAnswerFeedbackExportPresentation(int $question_id, int $question_index, int $answer_index): string
    {
        return "";
    }

    public function importSpecificAnswerFeedback(int $question_id, int $question_index, int $answer_index, string $feedback_content): void
    {
    }
}
