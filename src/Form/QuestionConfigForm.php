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

namespace ILIAS\Plugin\CBMChoiceQuestion\Form;

use CBMChoiceQuestionGUI;
use ilCBMChoiceQuestionPlugin;
use ilCheckboxInputGUI;
use ILIAS\DI\Container;
use ILIAS\Plugin\CBMChoiceQuestion\Form\Input\ScoringMatrixInput\ScoringMatrixInput;
use ILIAS\Plugin\Libraries\FieldMappingInput\FieldMappingInput;
use ilImageFileInputGUI;
use ilNumberInputGUI;
use ilPropertyFormGUI;
use ilSelectInputGUI;
use ilTextAreaInputGUI;
use ilTextInputGUI;

/**
 * Class QuestionConfigForm
 * @package ILIAS\Plugin\CBMChoiceQuestion\Form
 * @author Marvin Beym <mbeym@databay.de>
 */
class QuestionConfigForm extends ilPropertyFormGUI
{
    /**
     * @var ilCBMChoiceQuestionPlugin
     */
    private $plugin;
    /**
     * @var Container
     */
    private $dic;

    public function __construct(CBMChoiceQuestionGUI $parent, bool $singleLineAnswer = true)
    {
        $this->plugin = ilCBMChoiceQuestionPlugin::getInstance();
        global $DIC;
        $this->dic = $DIC;
        $this->setId("cbmChoiceQuestion");
        parent::__construct();

        $this->setFormAction($this->ctrl->getFormAction($parent));
        $this->setTitle($parent->outQuestionType());
        $this->setMultipart(true);
        $this->setTableWidth("100%");

        $parent->addBasicQuestionFormProperties($this);

        $shuffle = new ilCheckboxInputGUI($this->lng->txt("shuffle_answers"), "shuffle");
        $shuffle->setValue(true);
        $shuffle->setRequired(false);
        $this->addItem($shuffle);

        $answerTypes = new ilSelectInputGUI($this->lng->txt("answer_types"), "answerType");
        $answerTypes->setRequired(false);
        $answerTypes->setOptions(
            [
                $this->lng->txt("answers_singleline"),
                $this->lng->txt("answers_multiline")
            ]
        );
        $this->addItem($answerTypes);

        if ($singleLineAnswer) {
            // thumb size
            $thumbSize = new ilNumberInputGUI($this->lng->txt("thumb_size"), "thumbSize");
            $thumbSize->setSuffix($this->lng->txt("thumb_size_unit_pixel"));
            $thumbSize->setMinValue(20);
            $thumbSize->setDecimals(0);
            $thumbSize->setSize(6);
            $thumbSize->setInfo($this->lng->txt("thumb_size_info"));
            $thumbSize->setRequired(false);
            $this->addItem($thumbSize);
        }

        $allowMultipleSelection = new ilCheckboxInputGUI(
            $this->plugin->txt("question.config.allowMultipleSelection"),
            "allowMultipleSelection"
        );
        $this->addItem($allowMultipleSelection);

        $cbmAnswerRequired = new ilCheckboxInputGUI(
            $this->plugin->txt("question.config.cbmAnswerRequired"),
            "cbmAnswerRequired"
        );
        $this->addItem($cbmAnswerRequired);

        $imageFile = new ilImageFileInputGUI($this->lng->txt("answer_image"), "answerImage");
        $answers = new FieldMappingInput($this->lng->txt("answers"), "answers");

        if ($singleLineAnswer) {
            $answerText = new ilTextInputGUI($this->lng->txt("answer_text"), "answerText");
        } else {
            $answerText = new ilTextAreaInputGUI($this->lng->txt("answer_text"), "answerText");
            $answerText->setUseRte(true);
            $answerText->setUseRTE(true);
            $answerText->setRteTagSet("full");
        }
        $scoringMatrix = new ScoringMatrixInput($this->plugin->txt("scoringMatrix.title"), "scoringMatrix");
        $scoringMatrix->setup([
            "certain" => $this->plugin->txt("question.cbm.certain"),
            "uncertain" => $this->plugin->txt("question.cbm.uncertain")
        ], [
            "correct" => $this->plugin->txt("question.cbm.correct"),
            "incorrect" => $this->plugin->txt("question.cbm.incorrect")
        ]);
        $scoringMatrix->setInfo($this->plugin->txt("scoringMatrix.info"));
        $this->addItem($scoringMatrix);

        $answers->addField($answerText);
        if ($singleLineAnswer) {
            $answers->addField($imageFile, false);
        }
        $answers->addField(new ilCheckboxInputGUI($this->plugin->txt("question.config.correct"), "answerCorrect"));
        $answers->setRequired(true);

        $defaultData = [
            "answerText" => "",
            "answerCorrect" => false
        ];
        if ($singleLineAnswer) {
            $defaultData["answerImage"] = "";
        }

        $answers->setRowData([$defaultData]);

        $this->addItem($answers);
        $parent->addQuestionFormCommandButtons($this);
    }
}
