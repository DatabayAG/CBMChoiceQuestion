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

namespace ILIAS\Plugin\CBMChoiceQuestion\Form\Input\ScoringMatrixInput;

use ilCBMChoiceQuestionPlugin;
use ilCheckboxInputGUI;
use ilFormPropertyGUI;
use ilGlobalTemplateInterface;
use ILIAS\DI\Container;
use ilNumberInputGUI;
use ilTemplate;
use ilTemplateException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class ScoringMatrixInput
 * @package ILIAS\Plugin\CBMChoiceQuestion\Form\Input\ScoringMatrixInput
 * @author Marvin Beym <mbeym@databay.de>
 */
class ScoringMatrixInput extends ilFormPropertyGUI
{
    /**
     * @var ilGlobalTemplateInterface
     */
    private $mainTpl;

    /**
     * @var Container
     */
    protected $dic;

    /**
     * @var ilCBMChoiceQuestionPlugin
     */
    private $plugin;
    /**
     * @var RequestInterface|ServerRequestInterface
     */
    private $request;

    /**
     * @var string[]
     */
    private $columnNames = [];
    /**
     * @var string[]
     */
    private $rowNames = [];

    /**
     * @var array<string, ilNumberInputGUI>
     */
    private $inputs = [];

    public function __construct(string $title = "", string $postVar = "")
    {
        global $DIC;
        $this->dic = $DIC;
        $this->mainTpl = $this->dic->ui()->mainTemplate();
        $this->lng = $DIC->language();
        $this->request = $DIC->http()->request();
        $this->plugin = ilCBMChoiceQuestionPlugin::getInstance();
        parent::__construct($title, $postVar);
    }

    /**
     * @param string[] $columnNames
     * @param string[] $rowNames
     * @return void
     */
    public function setup(array $columnNames, array $rowNames) : void
    {
        $this->columnNames = $columnNames;
        $this->rowNames = $rowNames;

        $storeAsDefaultForSession = new ilCheckboxInputGUI(
            $this->plugin->txt("scoringMatrix.storeAsDefaultForSession"),
            "{$this->getPostVar()}_storeAsDefaultForSession"
        );
        $storeAsDefaultForSession->setOptionTitle($storeAsDefaultForSession->getTitle());
        $this->inputs["{$this->getPostVar()}_storeAsDefaultForSession"] = $storeAsDefaultForSession;

        foreach ($this->rowNames as $rowIndex => $rowName) {
            foreach ($this->columnNames as $colIndex => $columnName) {
                $this->inputs["{$this->getPostVar()}_values_{$rowIndex}_$colIndex"] = $this->createNumberInput($rowIndex, $colIndex);
            }
        }
    }

    public function setValueByArray(array $data) : void
    {
        if (isset($data[$this->getPostVar()])) {
            $data = $data[$this->getPostVar()];
        }
        foreach ($this->inputs as $input) {
            $input->setValueByArray($data);
        }
    }

    public function isStoreAsDefaultForSession() : bool
    {
        /**
         * @var ilCheckboxInputGUI $storeAsDefaultForSession
         */
        $storeAsDefaultForSession = $this->inputs["{$this->getPostVar()}_storeAsDefaultForSession"];
        return (bool) $storeAsDefaultForSession->getChecked();
    }

    public function getValue(bool $withStoreAsDefaultForSession = false) : array
    {
        $values = [];
        foreach ($this->inputs as $postVar => $input) {
            if ($input instanceof ilCheckboxInputGUI) {
                if (!$withStoreAsDefaultForSession) {
                    continue;
                }
                $values[$postVar] = (bool) $input->getChecked();
            } else {
                $values[$postVar] = (float) $input->getValue();
            }
        }
        return $values;
    }

    public function checkInput()
    {
        $success = true;

        foreach ($this->inputs as $input) {
            if ($input instanceof ilNumberInputGUI) {
                $input->setDecimals(2);
                $inputSuccess = $input->checkInput();
                $input->setDecimals(0);
            } else {
                $inputSuccess = $input->checkInput();
            }

            $success = $success === true ? $inputSuccess : $success;
        }

        return $success;
    }

    private function createNumberInput(string $rowIndex, string $colIndex) : ilNumberInputGUI
    {
        $input = new ilNumberInputGUI(
            "",
            "{$this->getPostVar()}_values_{$rowIndex}_$colIndex"
        );
        $input->setRequired(true);
        $input->allowDecimals(true);
        $input->setSize(2);
        return $input;
    }

    /**
     * @throws ilTemplateException
     * @noinspection DisconnectedForeachInstructionInspection
     */
    public function insert(ilTemplate $a_tpl) : void
    {
        $tpl = new ilTemplate($this->getFolderPath("tpl.scoringMatrix_input.html"), true, true);


        foreach ($this->columnNames as $columnName) {
            $tpl->setCurrentBlock("table_header");
            $tpl->setVariable("COL_NAME", $columnName);
            $tpl->parseCurrentBlock("table_header");
        }


        foreach ($this->rowNames as $rowIndex => $rowName) {
            foreach ($this->columnNames as $colIndex => $columnName) {
                $tpl->setCurrentBlock("row_input");
                $input = $this->inputs["{$this->getPostVar()}_values_{$rowIndex}_$colIndex"];
                $tpl->setVariable("INPUT", $input->render());
                if ($input->getAlert()) {
                    $tpl->setVariable("INPUT_ERROR", $input->getAlert());
                }
                $tpl->parseCurrentBlock("row_input");
            }

            $tpl->setCurrentBlock("row");
            $tpl->setVariable("ROW_NAME", $rowName);
            $tpl->parseCurrentBlock("row");
        }

        $storeAsDefaultInput = $this->inputs["{$this->getPostVar()}_storeAsDefaultForSession"];
        $tpl->setVariable(
            "STORE_AS_DEFAULT_INPUT",
            $storeAsDefaultInput->render()
        );
        if ($storeAsDefaultInput->getAlert()) {
            $tpl->setVariable("STORE_AS_DEFAULT_INPUT_ERROR", $storeAsDefaultInput->getAlert());
        }

        $a_tpl->setCurrentBlock('prop_generic');
        $a_tpl->setVariable('PROP_GENERIC', $tpl->get());
        $a_tpl->parseCurrentBlock();
        $this->mainTpl->addCSS($this->getFolderPath("style.css"));
    }

    private function getFolderPath(string $file = "") : string
    {
        return strstr(realpath(__DIR__), "Customizing") . "/$file";
    }
}
