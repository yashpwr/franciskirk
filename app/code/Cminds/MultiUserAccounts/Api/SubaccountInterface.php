<?php

namespace Cminds\MultiUserAccounts\Api;

use Cminds\MultiUserAccounts\Api\Data\ApiSubAccountInterface;

/**
 * Interface SubaccountInterface
 *
 * @package Cminds\MultiUserAccounts\Api
 */

interface SubaccountInterface
{
    /**
     * Returns list of all subaccounts for given parent.
     *
     * @param integer $parentId Parent id.
     * @return array
     *
     * @api
     */
    public function getSubaccounts($parentId);

    /**
     * Returns Subaccount Account info.
     *
     * @param integer $parentId Parent id.
     * @param integer $subaccountId Sub id.
     *
     * @return \Cminds\MultiUserAccounts\Api\Data\ApiSubAccountInterface
     *
     * @api
     */
    public function getById($parentId, $subaccountId);

    /**
     * Create new Sub Account
     *
     * @param integer $parentId Parent id.
     * @param \Cminds\MultiUserAccounts\Api\Data\ApiSubAccountInterface $customer
     *
     * @return \Cminds\MultiUserAccounts\Api\Data\ApiParentAccountInterface
     *
     * @api
     */
    public function create($parentId, ApiSubAccountInterface $customer);

    /**
     * Update existing sub account information by Customer ID
     *
     * @param integer $parentId Parent id.
     * @param integer $subaccountId
     * @param \Cminds\MultiUserAccounts\Api\Data\ApiSubAccountInterface $customer
     *
     * @return \Cminds\MultiUserAccounts\Api\Data\ApiParentAccountInterface
     *
     * @api
     */
    public function updateById($parentId, $subaccountId, ApiSubAccountInterface $customer);

    /**
     * Delete Sub Account by customer ID
     *
     * @param integer $parentId Parent id.
     * @param integer $subaccountId Sub id.
     *
     * @return string
     *
     * @api
     */
    public function deleteById($parentId, $subaccountId);
}
