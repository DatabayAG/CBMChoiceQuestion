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

namespace ILIAS\Plugin\CBMChoiceQuestion\Utils;

use ilFormPropertyGUI;

/**
 * Class AnswerTextSanitizer
 * @package ILIAS\Plugin\CBMChoiceQuestion\Utils
 * @author Marvin Beym <mbeym@databay.de>
 */
class AnswerTextSanitizer
{
    /**
     * @var ilFormPropertyGUI
     */
    private $formInput;

    public function __construct()
    {
        $this->formInput = new ilFormPropertyGUI("", "");
    }

    public function sanitize(string $string): string
    {
        return $this->formInput->stripSlashesAddSpaceFallback($string);
    }

    public function desanitize(string $sanitizedString): string
    {
        return str_replace(["< ", "<  "], "<", $sanitizedString);
    }
}
