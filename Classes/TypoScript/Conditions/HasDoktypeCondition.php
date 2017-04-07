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
 * Has doktype condition
 */
class HasDoktypeCondition extends AbstractCondition
{
    /**
     * Condition used to check if a content element has a certain
     * doktype This is used in the Permissions.ts TSConfig file to make
     * sure only certain content elements are placed in certain columns.
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
            $doktype = (int)$conditionParameters[0];

            $get = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET();

            // Case 1). edit one or more existing elements
            // Case 2). Adding a new element using the new-element icon
            // Case 3). Right after new element creation
            if (isset($get['id'])) {
                $pageUid = (int)$get['id'];

                $result = $doktype === $this->getDoktypeFromDatabase($pageUid);
            } elseif (isset($get['edit']) && is_array($get['edit']) && key($get['edit']) === 'pages') {
                $pageUid = (int)key($get['edit']['pages']);

                $result = $doktype === $this->getDoktypeFromDatabase($pageUid);

            } elseif(isset($get['returnUrl'])) {
                $pageUid = (int)substr(strrchr($get['returnUrl'], '='), 1);

                $result = $doktype === $this->getDoktypeFromDatabase($pageUid);

                // Case 4). Elements pasted from a clipboard
            } elseif (is_array($get['CB'])) {
                $newColPosData = $GLOBALS['BE_USER']->getSessionData('core.www_tue_ce.newColPos');
                if (is_array($newColPosData) && ($doktype == $newColPosData['doktype'])) {
                    $result = true;
                }
            }
        }

        return $result;
    }

    /**
     * Get the doktype from a page in the database
     *
     * @param int $pageUid The page uid
     * @return int The doktype
     */
    protected function getDoktypeFromDatabase($pageUid)
    {
        $queryResult = $this->getDatabaseConnection()->sql_query(
            'SELECT doktype FROM pages WHERE uid =' . $pageUid
        );

        /** @var array $page */
        $page = $this->getDatabaseConnection()->sql_fetch_assoc($queryResult);

        $this->getDatabaseConnection()->sql_free_result($queryResult);

        return (int)$page['doktype'];
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