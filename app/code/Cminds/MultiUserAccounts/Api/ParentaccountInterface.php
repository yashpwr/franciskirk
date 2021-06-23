<?php
namespace Cminds\MultiUserAccounts\Api;

use Cminds\MultiUserAccounts\Api\Data\ApiParentAccountInterface;

interface ParentaccountInterface
{
    /**
     * Returns Parent Account info.
     *
     * @param integer $parentId
     *
     * @return \Cminds\MultiUserAccounts\Api\Data\ApiParentAccountInterface
     *
     * @api
     */
    public function getById($parentId);
    /**
     * Returns Parent Account info.
     *
     * @param string $customerEmail
     *
     * @return \Cminds\MultiUserAccounts\Api\Data\ApiParentAccountInterface
     *
     * @api
     */
    public function getByEmail($customerEmail);
    /**
     * Returns Parent Account info.
     *
     * @param string $customerId
     *
     * @return \Cminds\MultiUserAccounts\Api\Data\ApiParentAccountInterface
     *
     * @api
     */
    public function getEmailById($customerId);
    /**
     * Create new Parent Account
     *
     * @api
     *
     * @param \Cminds\MultiUserAccounts\Api\Data\ApiParentAccountInterface $customer
     *
     * @return \Cminds\MultiUserAccounts\Api\Data\ApiParentAccountInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function create(ApiParentAccountInterface $customer);

    /**
     * Update existing parent account information by Customer ID
     *
     * @api
     *
     * @param string $parentId
     * @param \Cminds\MultiUserAccounts\Api\Data\ApiParentAccountInterface $customer
     *
     * @return \Cminds\MultiUserAccounts\Api\Data\ApiParentAccountInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function updateById($parentId, ApiParentAccountInterface $customer);

    /**
     * Delete Parent Account by customer ID
     *
     * @api
     *
     * @param int $parentId
     *
     * @return string
     */
    public function deleteById($parentId);
}
