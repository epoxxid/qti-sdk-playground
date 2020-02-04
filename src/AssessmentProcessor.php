<?php declare(strict_types=1);


/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 *
 * @author Roman Kovalev <roman.kovalev@taotesting.com>
 */

namespace App;

use qtism\common\datatypes\QtiFloat;
use qtism\data\AssessmentItem;
use qtism\data\AssessmentTest;
use qtism\data\results\AssessmentResult;
use qtism\data\results\ItemResult;
use qtism\data\results\ResultOutcomeVariable;
use qtism\data\state\Value;
use qtism\data\state\ValueCollection;

class AssessmentProcessor
{
    /** @var AssessmentTest */
    private $assessmentTest;

    /** @var AssessmentResult */
    private $assessmentResult;

    public function __construct(
        AssessmentTest $assessmentTest,
        AssessmentResult $assessmentResult
    )
    {
        $this->assessmentTest = $assessmentTest;
        $this->assessmentResult = $assessmentResult;
    }

    public function getAssessmentResult(): AssessmentResult
    {
        return $this->assessmentResult;
    }

    public function updateResultWithScores(array $scores): void
    {
        foreach ($scores as $itemId => $itemScores) {
            $this->log('>>> Updating scores of item %s', $itemId);
            $this->updateItemWithScores($itemId, $itemScores);
        }
    }

    private function updateItemWithScores(string $itemId, array $scores): void
    {
        $item = $this->findItemById($itemId);

        $totalScore = 0;
        foreach ($scores as $outcomeId => $score) {
            $outcomeVariable = $this->findOutcomeVariableById($item, $outcomeId);
            $this->updateOutcomeVariableValue($outcomeVariable, (float)$score);
            $totalScore += $score;
        }
        $this->updateItemTotalScore($item, $totalScore);
    }

    private function findItemById(string $itemId): ItemResult
    {
        /** @var ItemResult $item */
        foreach ($this->assessmentResult->getItemResults() as $item) {
            if ($item->getIdentifier()->getValue() === $itemId) {
                $this->log('Found item with id %s', $itemId);
                return $item;
            }
        }

        throw new \RuntimeException("Unable to find item with ID $itemId");
    }

    private function findOutcomeVariableById(ItemResult $item, string $outcomeVariableId): ResultOutcomeVariable
    {
        foreach ($item->getItemVariables() as $itemVariable) {
            if (!($itemVariable instanceof ResultOutcomeVariable)) {
                continue;
            }
            if ($itemVariable->getIdentifier()->getValue() === $outcomeVariableId) {
                $this->log(
                    'Found outcome variable %s at item %s',
                    $outcomeVariableId,
                    $item->getIdentifier()->getValue()
                );
                return $itemVariable;
            }
        }

        throw new \RuntimeException(sprintf(
            'Unable to find outcome declaration with ID %s at item with ID %s',
            $outcomeVariableId,
            $item->getIdentifier()->getValue()
        ));
    }

    private function updateOutcomeVariableValue(
        ResultOutcomeVariable $outcomeVariable,
        float $value,
        bool $overrideValue = true
    ): void
    {
        $valueContainer = $outcomeVariable->getValues()[0];

        if (null === $valueContainer) {
            $this->log('Outcome %s does not have a value yet, setting a default one');
            $valueContainer = new Value(0);
            $outcomeVariable->setValues(new ValueCollection([$valueContainer]));
        }

        $initialValue = $overrideValue ? 0 : (float)$valueContainer->getValue();
        $newValue = $initialValue + $value;
        $valueContainer->setValue(new QtiFloat($newValue));
        $this->log(
            'New value %s was set to outcome %s',
            $newValue,
            $outcomeVariable->getIdentifier()->getValue()
        );
    }

    private function updateItemTotalScore(ItemResult $item, float $score): void
    {
        $this->log('- Updating total score of an item %s', $item->getIdentifier()->getValue());
        $totalScoreOutcome = $this->findOutcomeVariableById($item, 'SCORE');
        $this->updateOutcomeVariableValue($totalScoreOutcome, $score, false);
    }

    private function log(string $template, ...$args): void
    {
        echo sprintf($template, ...$args) . PHP_EOL;
    }
}