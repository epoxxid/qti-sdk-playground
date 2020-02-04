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

use qtism\data\AssessmentItemRef;
use qtism\data\AssessmentItemRefCollection;
use qtism\data\AssessmentTest;
use qtism\data\results\AssessmentResult;
use qtism\data\results\ItemResult;
use qtism\data\results\ResultOutcomeVariable;
use qtism\data\state\OutcomeDeclaration;
use qtism\runtime\common\OutcomeVariable;
use qtism\runtime\common\State;

class OutcomeProcessingState extends State
{
    /** @var AssessmentTest */
    private $assessmentTest;
    /**
     * @var AssessmentResult
     */
    private $assessmentResult;

    public function __construct(
        AssessmentTest $assessmentTest,
        AssessmentResult $assessmentResult
    )
    {
        $stateVariables = [];
        
        /** @var OutcomeDeclaration $testVariable */
        foreach ($assessmentResult->getTestResult()->getItemVariables() as $testVariable) {
            $stateVariables[] = new OutcomeVariable(
                $testVariable->getIdentifier()->getValue(),
                $testVariable->getCardinality(),
                $testVariable->getBaseType()
            );
        }

        parent::__construct($stateVariables);
        $this->assessmentTest = $assessmentTest;
        $this->assessmentResult = $assessmentResult;
    }
    
    public function getItemSubset()
    {
        $items = new AssessmentItemRefCollection();

        $item = new AssessmentItemRef('hello', 'world');


        $items->attach(new AssessmentItemRef('hello', 'world'));

        /** @var AssessmentItemRef $testItem */
//        foreach ($this->assessmentTest->getComponentsByClassName('assessmentItemRef') as $testItem) {
//            /** @var ItemResult $itemResult */
//            foreach ($this->assessmentResult->getItemResults() as $itemResult) {
//                if ($testItem->getIdentifier() === $itemResult->getIdentifier()->getValue()) {
//                    $items->attach($testItem);
//                }
//            }
//        }

        return $items;
    }

    public function getAssessmentItemSessions()
    {
        return false;
    }
}
