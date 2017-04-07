<?php
namespace Tue\WwwTueNl\TypoScript\Conditions;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractCondition;
use TYPO3\CMS\Core\Database\DatabaseConnection;

/**
 * Has colPos condition
 */
class HasColPosCondition extends AbstractCondition
{
    /**
     * Condition used to check if a content element is in a certain column
     * This is used in the Permissions.ts TSConfig file to make sure only
     * certain content elements are placed in certain columns.
     *
     * @param array $conditionParameters Parameters from condition
     * @return bool
     */
    public function matchCondition(array $conditionParameters)
    {
        $result = false;

        if (0 === count($conditionParameters)) {
            $result = true;

        } else {
            $colPos = (int)$conditionParameters[0];

            $get = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET();

            // Case 2). Adding a new element using the new-element icon
            if (isset($get['colPos'])) {
                $result = $colPos === (int)$get['colPos'];

                // Case 3). Right after new element creation
            } elseif (isset($get['defVals']['tt_content']['colPos'])) {
                $result = $colPos === (int)$get['defVals']['tt_content']['colPos'];

                // Case 1). edit one or more existing elements
            } elseif (isset($get['edit']['tt_content'])) {
                $getUid = $get['edit']['tt_content'];

                if (is_array($getUid)) {
                    $uid = (int)abs(rtrim(key($getUid), ','));

                    $result = $colPos === $this->getColPosFromDatabase($uid);
                }

                // Case 4). Elements pasted from a clipboard
            } elseif (is_array($get['CB'])) {
                $newColPosData = $GLOBALS['BE_USER']->getSessionData('core.www_tue_ce.newColPos');

                if (is_array($newColPosData) && ($colPos == $newColPosData['colPos'])) {
                    $result = true;
                };
            }
        }

        return $result;
    }

    /**
     * Get the colPos from a content element in the database
     *
     * @param int $contentElementUid The content element uid
     * @return int The colPos
     */
    protected function getColPosFromDatabase($contentElementUid)
    {
        $queryResult = $this->getDatabaseConnection()->sql_query(
            'SELECT colPos FROM tt_content WHERE uid =' . $contentElementUid
        );

        /** @var array $contentElement */
        $contentElement = $this->getDatabaseConnection()->sql_fetch_assoc($queryResult);

        $this->getDatabaseConnection()->sql_free_result($queryResult);

        return (int)$contentElement['colPos'];
    }

    /**
     * Get the database connection
     *
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}