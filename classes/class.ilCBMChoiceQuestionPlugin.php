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

use ILIAS\DI\Container;
use ILIAS\Plugin\MatrixChatClient\Libs\JsonTranslationLoader\JsonTranslationLoader;

require_once __DIR__ . '/../vendor/autoload.php';

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
    /**
     * @var ilCtrl
     */
    protected $ctrl;
    /**
     * @var ilSetting
     */
    public $settings;
    /**
     * @var Container
     */
    protected $dic;
    /**
     * @var self|null
     */
    private static $instance = null;

    public function __construct()
    {
        global $DIC;
        $this->dic = $DIC;
        $this->ctrl = $this->dic->ctrl();
        $this->settings = new ilSetting(self::PNAME);
        parent::__construct();
    }

    /** @noinspection PhpIncompatibleReturnTypeInspection */
    public static function getInstance() : self
    {
        return self::$instance ?? (self::$instance = ilPluginAdmin::getPluginObject(
            self::CTYPE,
            self::CNAME,
            self::SLOT_ID,
            self::PNAME
        ));
    }

    public function getPluginName() : string
    {
        return self::PNAME;
    }

    public function getQuestionType() : string
    {
        return "CBMChoiceQuestion";
    }

    final public function getQuestionTypeTranslation() : string
    {
        return $this->txt($this->getQuestionType());
    }

    public function assetsFolder(string $file = "") : string
    {
        return $this->getDirectory() . "/assets/$file";
    }

    public function cssFolder(string $file = "") : string
    {
        return $this->assetsFolder("css/$file");
    }

    public function imagesFolder(string $file = "") : string
    {
        return $this->assetsFolder("images/$file");
    }

    public function templatesFolder(string $file = "") : string
    {
        return $this->assetsFolder("templates/$file");
    }

    public function jsFolder(string $file = "") : string
    {
        return $this->assetsFolder("js/$file");
    }

    public function redirectToHome() : void
    {
        if ($this->isAtLeastIlias6()) {
            $this->ctrl->redirectByClass("ilDashboardGUI", "show");
        } else {
            $this->ctrl->redirectByClass("ilPersonalDesktopGUI");
        }
    }

    public function isAtLeastIlias6() : bool
    {
        return version_compare(ILIAS_VERSION_NUMERIC, "6", ">=");
    }


    public function denyConfigIfPluginNotActive() : void
    {
        if (!$this->isActive()) {
            ilUtil::sendFailure($this->txt("general.plugin.notActivated"), true);
            $this->ctrl->redirectByClass(ilObjComponentSettingsGUI::class, "view");
        }
    }

    public function updateLanguages($a_lang_keys = null) : void
    {
        try {
            $jsonTranslationLoader = new JsonTranslationLoader($this->getDirectory() . "/lang");
            $jsonTranslationLoader->load();
        } catch (Exception $e) {
        }
        parent::updateLanguages($a_lang_keys);
    }


    protected function beforeUninstall() : bool
    {
        $this->settings->deleteAll();
        return parent::beforeUninstall();
    }
}