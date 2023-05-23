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
use ILIAS\Plugin\CBMChoiceQuestion\Form\Input\FieldMappingInput;
use ILIAS\Plugin\CBMChoiceQuestion\Form\Input\ScoringMatrixInput\ScoringMatrixInput;
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
        $this->setId('cbmChoiceQuestion');
        parent::__construct();

        $this->setFormAction($this->ctrl->getFormAction($parent));
        $this->setTitle($parent->outQuestionType());
        $this->setMultipart(true);
        $this->setTableWidth('100%');

        $parent->addBasicQuestionFormProperties($this);

        $points = new ilNumberInputGUI($this->lng->txt("points"), "pointsForQuestion");
        $points->allowDecimals(true);
        $points->setDecimals(2);
        $points->setRequired(true);
        $points->setSize(3);
        $points->setMinValue(0.0);
        $points->setMinvalueShouldBeGreater(true);
        $this->addItem($points);

        $shuffle = new ilCheckboxInputGUI($this->lng->txt("shuffle_answers"), "shuffle");
        $shuffle->setValue(true);
        $shuffle->setRequired(false);
        $this->addItem($shuffle);

        $answerTypes = new ilSelectInputGUI($this->lng->txt("answer_types"), "answerType");
        $answerTypes->setRequired(false);
        $answerTypes->setOptions(
            [
                $this->lng->txt('answers_singleline'),
                $this->lng->txt('answers_multiline')
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
            $thumbSize->setInfo($this->lng->txt('thumb_size_info'));
            $thumbSize->setRequired(false);
            $this->addItem($thumbSize);
        }

        $measure = new ilCheckboxInputGUI($this->plugin->txt('field_hide_measure'), 'hideMeasure');
        $measure->setInfo($this->plugin->txt('field_hide_measure_info'));
        $measure->setValue('1');
        $this->addItem($measure);

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
            $answerText->setRteTagSet('full');
        }
        $aa = new ScoringMatrixInput("Scoring Matrix", "scoringMatrix");
        $aa->setup([
            "certain" => $this->plugin->txt("question.cbm.certain"),
            "uncertain" => $this->plugin->txt("question.cbm.uncertain")
        ], [
            "correct" => $this->plugin->txt("question.cbm.correct"),
            "incorrect" => $this->plugin->txt("question.cbm.incorrect")
        ]);
        $this->addItem($aa);

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
