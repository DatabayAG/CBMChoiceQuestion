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
use ilHiddenInputGUI;
use ILIAS\DI\Container;
use ILIAS\Plugin\CBMChoiceQuestion\Form\Input\FieldMappingInput;
use ilImageFileInputGUI;
use ilNumberInputGUI;
use ilPropertyFormGUI;
use ilSelectInputGUI;
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

    public function __construct(CBMChoiceQuestionGUI $parent)
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

        $points = new ilHiddenInputGUI('points');
        $points->setValue(1);
        $this->addItem($points);

        $shuffle = new ilCheckboxInputGUI($this->lng->txt("shuffle_answers"), "shuffle");
        $shuffle->setValue(true);
        $shuffle->setRequired(false);
        $this->addItem($shuffle);

        $answerTypes = new ilSelectInputGUI($this->lng->txt("answer_types"), "answer_type");
        $answerTypes->setRequired(false);
        $answerTypes->setOptions(
            [
                $this->lng->txt('answers_singleline'),
                $this->lng->txt('answers_multiline')
            ]
        );
        $this->addItem($answerTypes);

        if ($parent->object->getAnswerType() === 0) {
            // thumb size
            $thumb_size = new ilNumberInputGUI($this->lng->txt("thumb_size"), "thumb_size");
            $thumb_size->setSuffix($this->lng->txt("thumb_size_unit_pixel"));
            $thumb_size->setMinValue(20);
            $thumb_size->setDecimals(0);
            $thumb_size->setSize(6);
            $thumb_size->setInfo($this->lng->txt('thumb_size_info'));
            $thumb_size->setRequired(false);
            $this->addItem($thumb_size);
        }

        $measure = new ilCheckboxInputGUI($this->plugin->txt('field_hide_measure'), 'hide_measure');
        $measure->setInfo($this->plugin->txt('field_hide_measure_info'));
        $measure->setValue('1');
        $this->addItem($measure);

        $allowMultipleSelection = new ilCheckboxInputGUI(
            $this->plugin->txt("question.config.allowMultipleSelection"),
            "allowMultipleSelection"
        );
        $this->addItem($allowMultipleSelection);

        $imageFile = new ilImageFileInputGUI($this->lng->txt("answer_image"), "answerImage");
        $answers = new FieldMappingInput($this->lng->txt("answers"), "answers");
        $answers->addField(new ilTextInputGUI($this->lng->txt("answer_text"), "answerText"));
        $answers->addField($imageFile, false);
        $answers->addField(new ilCheckboxInputGUI($this->plugin->txt("question.config.correct"), "answerCorrect"));
        $answers->setRequired(true);
        $answers->setRowData([[
            "answerText" => "",
            "answerImage" => "",
            "answerCorrect" => false
        ]]);

        $this->addItem($answers);
        $parent->addQuestionFormCommandButtons($this);
    }
}
