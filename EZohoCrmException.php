<?php

/**
 * EZohoCrm extension for Yii framework.
 *
 * API Reference Zoho CRM
 * @link https://www.zoho.com/crm/help/api/api-methods.html
 *
 * @author: Emile Bons <emile@emilebons.nl>
 * @link http://www.malvee.com
 * @link http://www.emilebons.nl
 * @copyright Copyright &copy; Emile Bons 2013
 * @license The MIT License
 * @category Yii 1.1
 * @package ext\EZohoCrm
 *
 * Extension was improved by
 * @author: Dmitry Kulikov <kulikovdn@gmail.com>
 */

namespace ext\EZohoCrm;

/**
 * EZohoCrmException is class of exceptions which EZohoCrm throws.
 */
class EZohoCrmException extends \CException
{
    /**
     * Error code for cases when module does not support action or option.
     */
    const MODULE_NOT_SUPPORTED = 1;

    /**
     * Error code for cases when number of records to insert / update in one request exceeds Zoho CRM API limits.
     */
    const RECORDS_INSERT_UPDATE_LIMIT = 2;

    /**
     * Error code for cases when maximum number of attempts to send request was exceeded.
     */
    const RETRY_ATTEMPTS_LIMIT = 3;

    /**
     * Error code for cases when unknown HTTP request method was specified.
     */
    const UNKNOWN_HTTP_METHOD = 4;

    /**
     * Error code for cases when JSON in response from Zoho CRM API can't be parsed.
     */
    const ZOHO_CRM_INVALID_JSON = 5;

    /**
     * Error code for cases when response from Zoho CRM API is invalid.
     */
    const ZOHO_CRM_INVALID_RESPONSE = 6;

    /**
     * Error code for cases when response from Zoho CRM API contains error message.
     */
    const ZOHO_CRM_RESPONSE_ERROR = 7;
}
