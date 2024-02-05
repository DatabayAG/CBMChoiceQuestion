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

namespace ILIAS\Plugin\CBMChoiceQuestion\Model;

use ReflectionClass;

/**
 * Class AnswerData
 * @package ILIAS\Plugin\CBMChoiceQuestion\Model
 * @author Marvin Beym <mbeym@databay.de>
 */
class AnswerData
{
    private int $id;
    private string $answerText;
    private string $answerImage;
    private bool $answerCorrect;

    public function __construct(int $id, string $answerText, string $answerImage, bool $answerCorrect)
    {
        $this->id = $id;
        $this->answerText = $answerText;
        $this->answerImage = $answerImage;
        $this->answerCorrect = $answerCorrect;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAnswerText(): string
    {
        return $this->answerText;
    }

    public function setAnswerText(string $answerText): AnswerData
    {
        $this->answerText = $answerText;
        return $this;
    }

    public function getAnswerImage(): string
    {
        return $this->answerImage;
    }

    public function setAnswerImage(string $answerImage): AnswerData
    {
        $this->answerImage = $answerImage;
        return $this;
    }

    public function isAnswerCorrect(): bool
    {
        return $this->answerCorrect;
    }

    public function setAnswerCorrect(bool $answerCorrect): AnswerData
    {
        $this->answerCorrect = $answerCorrect;
        return $this;
    }

    /**
     * @param string[] $propertyBlacklist
     * @return array<string, mixed>
     */
    public function toArray(array $propertyBlacklist = []): array
    {
        $values = [];
        $refClass = new ReflectionClass($this);
        foreach ($refClass->getProperties() as $property) {
            if (in_array($property->getName(), $propertyBlacklist, true)) {
                continue;
            }
            $property->setAccessible(true);
            $values[$property->getName()] = $property->getValue($this);
        }

        return $values;
    }
}
