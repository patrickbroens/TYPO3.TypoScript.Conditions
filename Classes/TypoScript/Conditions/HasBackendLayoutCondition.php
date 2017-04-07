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
 * Has backend layout condition
 */
class HasBackendLayoutCondition extends AbstractCondition
{
    /**
     * User function used to check if a content element has a certain
     * backend_layout This is used in the Permissions.ts TSConfig file to make
     * sure only certain content elements are placed in certain columns.
     *
     * This method detects several 'entry points' to new element creation:
     *
     * 1). Clicking on a pencil icon
     * In this case the GET parameter edit tt_content will be set. It contains
     * the uid of the content element being edited. We can deduce the colPos
     * and backend_layout from there. ( edit[tt_content][6789,]:edit )
     *
     * 2). Clicking the content-with-green-plus-sign icon on a column header
     * In this case the GET parameterers colPos and id are set. This
     * directly provides us with the colPos. We can deduce the backend_layout
     * from the id which holds the page uid.
     *
     * 3). Right after creating a new element and displaying it.
     * When a new element is created, we need to apply the permissions too.
     * In this case the GET parameters defVals[tt_content][colPos] and
     * returnUrl may be used. Some of these parameters are also available in
     * case 1). Like the return Url.
     *
     * 4). Elements are pasted from a clipboard.
     * Any clipboard data? Then we're copying or moving elements. In that
     * case use the newColPos data stored in the processCmdmap hook.
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
            $backendLayout = 'file__' . $conditionParameters[0];

            $get = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET();

            // Case 1). edit one or more existing elements
            // Case 2). Adding a new element using the new-element icon
            // Case 3). Right after new element creation
            if (isset($get['id'])) {
                $pageUid = (int)$get['id'];

                $result = $backendLayout === $this->getBackendLayoutFromDatabase($pageUid);

            } elseif (isset($get['edit']) && is_array($get['edit']) && key($get['edit']) === 'pages') {
                $pageUid = (int)key($get['edit']['pages']);

                $result = $backendLayout === $this->getBackendLayoutFromDatabase($pageUid);

            } elseif (isset($get['returnUrl'])) {
                $pageUid = (int)substr(strrchr($get['returnUrl'], '='), 1);

                $result = $backendLayout === $this->getBackendLayoutFromDatabase($pageUid);

                // Case 4). Elements pasted from a clipboard
            } elseif (is_array($get['CB'])) {
                $newColPosData = $GLOBALS['BE_USER']->getSessionData('core.www_tue_ce.newColPos');
                if (
                    is_array($newColPosData)
                    && $backendLayout === $newColPosData['backend_layout']
                ) {
                    $result = true;
                };
            }
        }

        return $result;
    }

    /**
     * Get the backend layout from a page in the database
     *
     * @param int $pageUid The page uid
     * @return string The backend layout
     */
    protected function getBackendLayoutFromDatabase($pageUid)
    {
        $queryResult = $this->getDatabaseConnection()->sql_query(
            'SELECT backend_layout FROM pages WHERE uid =' . $pageUid
        );

        /** @var array $page */
        $page = $this->getDatabaseConnection()->sql_fetch_assoc($queryResult);

        $this->getDatabaseConnection()->sql_free_result($queryResult);

        return (string)$page['backend_layout'];
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