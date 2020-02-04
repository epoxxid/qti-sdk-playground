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

use qtism\data\results\AssessmentResult;
use qtism\data\state\OutcomeDeclaration;
use qtism\runtime\common\OutcomeVariable;
use qtism\runtime\common\State;

class OutcomeProcessingState extends State
{
    public function __construct(AssessmentResult $assessmentResult)
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
    }
}
