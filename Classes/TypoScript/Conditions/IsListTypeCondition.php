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
 * Is list type condition
 */
class IsListTypeCondition extends AbstractCondition
{
    /**
     * Condition used to check if a content element is a certain plugin
     * This is used in the Permissions.ts TSConfig file change configuration for
     * certain content elements
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
            $listType = (string)$conditionParameters[0];

            $get = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET();

            // Case 1). Right after new element creation
            if (
                isset($get['defVals']['tt_content']['list_type'])
                && $get['defVals']['tt_content']['list_type'] === $listType
            ) {
                $result = true;

                // Case 2). edit one or more existing elements
            } elseif (isset($get['edit']['tt_content'])) {
                $getUid = $get['edit']['tt_content'];

                if (is_array($getUid)) {
                    $uid = (int)abs(rtrim(key($getUid), ','));

                    $result = $listType === $this->getListTypeFromDatabase($uid);
                }
            }
        }

        return $result;
    }

    /**
     * Get the list type from a content element in the database
     *
     * @param int $contentElementUid The content element uid
     * @return int The list type
     */
    protected function getListTypeFromDatabase($contentElementUid)
    {
        $queryResult = $this->getDatabaseConnection()->sql_query(
            'SELECT list_type FROM tt_content WHERE uid =' . $contentElementUid
        );

        /** @var array $contentElement */
        $contentElement = $this->getDatabaseConnection()->sql_fetch_assoc($queryResult);

        $this->getDatabaseConnection()->sql_free_result($queryResult);

        return (string)$contentElement['list_type'];
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