<?php declare(strict_types=1);
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

use ASS_AnswerBinaryStateImage;
use ASS_AnswerMultipleResponseImage;
use CBMChoiceQuestionGUI;
use ilCBMChoiceQuestionPlugin;
use ilCheckboxInputGUI;
use ilHiddenInputGUI;
use ILIAS\DI\Container;
use ilMultipleChoiceWizardInputGUI;
use ilPropertyFormGUI;
use ilRadioGroupInputGUI;
use ilRadioOption;
use ilSingleChoiceWizardInputGUI;

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

        $measure = new ilCheckboxInputGUI($this->plugin->txt('field_hide_measure'), 'hide_measure');
        $measure->setInfo($this->plugin->txt('field_hide_measure_info'));
        $measure->setValue('1');
        //$measure->setChecked($this->object->isMeasureHidden());
        $this->addItem($measure);

        //$parent->populateTaxonomyFormSection($form);

        $answersVariant = new ilRadioGroupInputGUI($this->lng->txt("answers"), "answers_variant");
        $answersSingleOption = new ilRadioOption($this->lng->txt("assSingleChoice"), "answers_variant_single");
        $answersMultiOption = new ilRadioOption($this->lng->txt("assMultipleChoice"), "answers_variant_multi");

        $answersSingle = new ilSingleChoiceWizardInputGUI("AAA", "answers_single");
        $answersSingle->setRequired(true);
        $answersSingle->setQuestionObject($parent->object);
        $answersSingle->setSingleline(true);
        if (count($parent->object->getAnswersSingle()) === 0) {
            $parent->object->addAnswerSingle();
        }

        $answersMulti = new ilMultipleChoiceWizardInputGUI("BB", "answers_multi");
        $answersMulti->setRequired(true);
        $answersMulti->setQuestionObject($parent->object);
        $answersMulti->setSingleline(true);
        if (count($parent->object->getAnswersMulti()) === 0) {
            $parent->object->addAnswerMulti();
        }

        $answersSingleOption->addSubItem($answersSingle);
        $answersMultiOption->addSubItem($answersMulti);

        $answersVariant->setRequired(true);
        $answersVariant->addOption($answersSingleOption);
        $answersVariant->addOption($answersMultiOption);

        //ToDo: Aktuells problem: Werter werden nicht mehr angezeigt (also der default leer wert, der erlaubt einen zu definieren für single und multii

        $this->addItem($answersVariant);

        $parent->addQuestionFormCommandButtons($this);
    }
}