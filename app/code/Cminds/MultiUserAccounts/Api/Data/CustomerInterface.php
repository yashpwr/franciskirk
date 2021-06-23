<?php

namespace Cminds\MultiUserAccounts\Api\Data;

use Magento\Framework\Api\CustomAttributesDataInterface;

/**
 * Cminds MultiUserAccounts customer interface.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Piotr Pierzak <piotr@cminds.com>
 */
interface CustomerInterface extends CustomAttributesDataInterface
{
    /**
     * Customer entity data keys.
     */
    const ID = 'id';
    const CONFIRMATION = 'confirmation';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const EMAIL = 'email';
    const FIRSTNAME = 'firstname';
    const LASTNAME = 'lastname';
    const MIDDLENAME = 'middlename';
    const TAXVAT = 'taxvat';
    const DOB = 'dob';
    const GENDER = 'gender';
    const SUFFIX = 'suffix';
    const PREFIX = 'prefix';
    const STORE_ID = 'store_id';
    const WEBSITE_ID = 'website_id';

    /**
     * Get customer id.
     *
     * @api
     * @return int|null
     */
    public function getId();

    /**
     * Set customer id.
     *
     * @api
     * @param   int $id
     * @return  CustomerInterface
     */
    public function setId($id);

    /**
     * Get confirmation.
     *
     * @api
     * @return  string|null
     */
    public function getConfirmation();

    /**
     * Set confirmation.
     *
     * @api
     * @param   string $confirmation
     * @return  CustomerInterface
     */
    public function setConfirmation($confirmation);

    /**
     * Get created at time.
     *
     * @api
     * @return  string|null
     */
    public function getCreatedAt();

    /**
     * Set created at time.
     *
     * @api
     * @param   string $createdAt
     * @return  CustomerInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * Get updated at time.
     *
     * @api
     * @return  string|null
     */
    public function getUpdatedAt();

    /**
     * Set updated at time.
     *
     * @api
     * @param   string $updatedAt
     * @return  CustomerInterface
     */
    public function setUpdatedAt($updatedAt);

    /**
     * Get email address.
     *
     * @api
     * @return  string
     */
    public function getEmail();

    /**
     * Set email address.
     *
     * @api
     * @param   string $email
     * @return  CustomerInterface
     */
    public function setEmail($email);

    /**
     * Get first name.
     *
     * @api
     * @return  string
     */
    public function getFirstname();

    /**
     * Set first name.
     *
     * @api
     * @param   string $firstname
     * @return  CustomerInterface
     */
    public function setFirstname($firstname);

    /**
     * Get last name.
     *
     * @api
     * @return  string
     */
    public function getLastname();

    /**
     * Set last name.
     *
     * @api
     * @param   string $lastname
     * @return  CustomerInterface
     */
    public function setLastname($lastname);

    /**
     * Get middlename.
     *
     * @api
     * @return  string
     */
    public function getMiddlename();

    /**
     * Set middlename.
     *
     * @api
     * @param   string $middlename
     * @return  CustomerInterface
     */
    public function setMiddlename($middlename);

    /**
     * Get prefix.
     *
     * @api
     * @return  string
     */
    public function getPrefix();

    /**
     * Set prefix.
     *
     * @api
     * @param   string $prefix
     * @return  CustomerInterface
     */
    public function setPrefix($prefix);

    /**
     * Get suffix.
     *
     * @api
     * @return  string
     */
    public function getSuffix();

    /**
     * Set suffix.
     *
     * @api
     * @param   string $suffix
     * @return  CustomerInterface
     */
    public function setSuffix($suffix);

    /**
     * Get store id.
     *
     * @api
     * @return  int|null
     */
    public function getStoreId();
    
    /**
     * Get VAT.
     *
     * @api
     * @return string
     */
    public function getTaxvat();
    
    /**
     * Get date of birth.
     *
     * @api
     * @return string
     */
    public function getDob();

    /**
     * Get gender.
     *
     * @api
     * @return int|null
     */
    public function getGender();

    /**
     * Set store id.
     *
     * @api
     * @param   int $storeId
     * @return  CustomerInterface
     */
    public function setStoreId($storeId);

    /**
     * Get website id.
     *
     * @api
     * @return  int|null
     */
    public function getWebsiteId();

    /**
     * Set website id.
     *
     * @api
     * @param   int $websiteId
     * @return  CustomerInterface
     */
    public function setWebsiteId($websiteId);
}
