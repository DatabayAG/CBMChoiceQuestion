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

use ILIAS\DI\Container;
use ILIAS\Plugin\CBMChoiceQuestion\Utils\UiUtil;

require_once __DIR__ . "/../vendor/autoload.php";

/**
 * Class ilassCBMChoiceQuestion
 *
 * @author Marvin Beym <mbeym@databay.de>
 */
class ilCBMChoiceQuestionPlugin extends ilQuestionsPlugin
{
    /** @var string */
    public const CTYPE = "Modules";
    /** @var string */
    public const CNAME = "TestQuestionPool";
    /** @var string */
    public const SLOT_ID = "qst";
    /** @var string */
    public const PNAME = "CBMChoiceQuestion";

    public const CBM_CHOICE_SCORING_MATRIX_STORE_AS_DEFAULT_IN_SESSION_KEY = "cbm_choice_scoringMatrix_storeAsDefaultForSession";
    public const ANSWER_TYPE_SINGLE_LINE = 0;
    public const ANSWER_TYPE_MULTI_LINE = 1;

    protected ilCtrl $ctrl;
    public ilSetting $settings;
    protected Container $dic;
    private static ?ilCBMChoiceQuestionPlugin $instance = null;
    private UiUtil $uiUtil;

    public function __construct(ilDBInterface $db, ilComponentRepositoryWrite $component_repository, string $id)
    {
        global $DIC;
        $this->dic = $DIC;
        $this->ctrl = $this->dic->ctrl();
        $this->settings = new ilSetting(self::PNAME);
        $this->uiUtil = new UiUtil();
        parent::__construct($db, $component_repository, $id);
    }

    public static function getInstance(): self
    {
        if (self::$instance) {
            return self::$instance;
        }

        global $DIC;

        /**
         * @var ilComponentFactory $componentFactory
         */
        $componentFactory = $DIC["component.factory"];
        self::$instance = $componentFactory->getPlugin("cbmChoice");
        return self::$instance;
    }

    public function getPluginName(): string
    {
        return self::PNAME;
    }

    public function getQuestionType(): string
    {
        return "CBMChoiceQuestion";
    }

    final public function getQuestionTypeTranslation(): string
    {
        return $this->txt($this->getQuestionType());
    }

    public function assetsFolder(string $file = ""): string
    {
        return $this->getDirectory() . "/assets/$file";
    }

    public function cssFolder(string $file = ""): string
    {
        return $this->assetsFolder("css/$file");
    }

    public function imagesFolder(string $file = ""): string
    {
        return $this->assetsFolder("images/$file");
    }

    public function templatesFolder(string $file = ""): string
    {
        return $this->assetsFolder("templates/$file");
    }

    public function jsFolder(string $file = ""): string
    {
        return $this->assetsFolder("js/$file");
    }

    public function redirectToHome(): void
    {
        $this->ctrl->redirectByClass("ilDashboardGUI", "show");
    }

    public function denyConfigIfPluginNotActive(): void
    {
        if (!$this->isActive()) {
            $this->uiUtil->sendFailure($this->txt("general.plugin.notActivated"), true);
            $this->ctrl->redirectByClass(ilObjComponentSettingsGUI::class, "view");
        }
    }

    protected function beforeUninstall(): bool
    {
        $this->settings->deleteAll();
        return parent::beforeUninstall();
    }
}
