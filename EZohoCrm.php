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
 * @version 2.0
 *
 * Extension was improved by
 * @author: Dmitry Kulikov <kulikovdn@gmail.com>
 *
 * TODO documentation
 *
 * TODO use Yii::t for translation
 *
 * TODO unit tests
 *
 * TODO check for compatibility with Yii 2 and modify if needed
 *
 * TODO rewrite as descendant of CApplicationComponent
 *
 * TODO validate data before sending of insert / update requests to Zoho CRM API,
 * TODO because API just ignore incorrect values: it is needed to validate length, is date etc.
 */

namespace ext\EZohoCrm;

/**
 * EZohoCrm is main class of extension. It is good idea to create in your project class ZohoCrm extending this class.
 */
class EZohoCrm
{
    /**
     * Maximum number of records which can be created / updated in one API call.
     */
    const MAX_RECORDS_INSERT_UPDATE = 100;

    /**
     * Maximum number of records which can be retrieved in one getRecords API call.
     */
    const MAX_RECORDS_GET_RECORDS = 200;

    /**
     * Auth Token
     * An authentication token is required in order to be able to make use of the Zoho CRM
     * API. An authentication token can be obtained by using the generateAuthToken
     * function inside this class or by using the url
     * https://accounts.zoho.com/apiauthtoken/create?SCOPE=ZohoCRM/crmapi while being
     * logged in in Zoho CRM. You could hardcode the authToken, obtain it from a config
     * file or obtain it from a database in the line below.
     * @var string
     */
    public $authToken = null;

    /**
     * Module
     * Defines the module which you want to use within the application.
     * @var string
     */
    public $module;

    /**
     * Print
     * Defines whether print or return result of API call, defaults to false.
     * @var boolean
     */
    public $print = false;

    /**
     * Timeout
     * Timeout for EHttpClient requests in seconds, defaults to 30 seconds.
     * @var integer
     */
    public $timeout = 30;

    /**
     * Max Attempts
     * Maximum number of attempts to send request. If this number is greater than 1
     * then request will be automatically repeated in case of connection timeout up to reaching of Max Attempts value.
     * @var integer
     */
    public $maxAttempts = 1;

    /**
     * Attempts Count
     * Number of already performed attempts to send request.
     * @var integer
     */
    public $attemptsCount = 1;

    /**
     * Sleep Time
     * Time in seconds between attempts to send request in case of connection timeout.
     * @var integer
     */
    public $sleepTime = 1;

    /**
     * Debug
     * Enable debug mode, extension logs all requests to Zoho CRM API in debug mode.
     * @var bool
     */
    public $debug = YII_DEBUG;

    /**
     * Curl Options
     * Option for a cURL transfer.
     * @var array
     */
    public $curlOptions = array();

    /**
     * MODULE constants
     */
    const MODULE_ACCOUNTS = 'Accounts';
    const MODULE_CALLS = 'Calls';
    const MODULE_CAMPAIGNS = 'Campaigns';
    const MODULE_CASES = 'Cases';
    const MODULE_COMPETITORS = 'Competitors';
    const MODULE_CONTACTS = 'Contacts';
    const MODULE_DASHBOARDS = 'Dashboards';
    const MODULE_EMAILS = 'Emails';
    const MODULE_EVENTS = 'Events';
    const MODULE_FORECASTS = 'Forecasts';
    const MODULE_INFO = 'Info';
    const MODULE_INTEGRATIONS = 'Integrations';
    const MODULE_INVOICES = 'Invoices';
    const MODULE_LEADS = 'Leads';
    const MODULE_POTENTIALS = 'Potentials';
    const MODULE_PRICE_BOOKS = 'PriceBooks';
    const MODULE_PRODUCTS = 'Products';
    const MODULE_PURCHASE_ORDERS = 'PurchaseOrders';
    const MODULE_QUOTES = 'Quotes';
    const MODULE_REPORTS = 'Reports';
    const MODULE_SOLUTIONS = 'Solutions';
    const MODULE_SALES_ORDERS = 'SalesOrders';
    const MODULE_TASKS = 'Tasks';
    const MODULE_USERS = 'Users';
    const MODULE_VENDORS = 'Vendors';

    /**
     * USER_TYPE constants
     */
    const USER_TYPE_ALL_USERS = 'AllUsers';
    const USER_TYPE_ACTIVE_USERS = 'ActiveUsers';
    const USER_TYPE_DEACTIVE_USERS = 'DeactiveUsers';
    const USER_TYPE_ADMIN_USERS = 'AdminUsers';
    const USER_TYPE_ACTIVE_CONFIRMED_ADMINS = 'ActiveConfirmedAdmins';

    /**
     * SCOPE constants
     */
    const SCOPE = 'crmapi';
    const SCOPE_AUTH_TOKEN_REQUEST = 'ZohoCRM/crmapi';
    const VERSION = 2;

    /**
     * BASE_URL constant
     */
    const BASE_URL = 'https://crm.zoho.com/crm/private/json/';

    /**
     * AUTH TOKEN REQUEST URL constant
     */
    const AUTH_TOKEN_REQUEST_URL = 'https://accounts.zoho.com/apiauthtoken/nb/create';

    /**
     * Parameter constants
     * These constants are used when calling methods in the API.
     */
    const ALL_COLUMNS = 'All';
    const SORT_ORDER_ASC = 'asc';
    const SORT_ORDER_DESC = 'desc';

    /**
     * Constructor
     * @param array $configArray use it to override default values for variables
     */
    public function __construct($configArray = null)
    {
        // default curl options
        $this->curlOptions = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FAILONERROR => false,
            CURLOPT_SSL_VERIFYPEER => true,
            /**
             * Please everyone, stop setting CURLOPT_SSL_VERIFYPEER to false or 0. If your PHP installation does not
             * have an up-to-date CA root certificate bundle, download the one at the curl website
             * and save it on your server: http://curl.haxx.se/docs/caextract.html
             * Then set a path to it in your php.ini file, e.g. on Windows:
             *     curl.cainfo=c:\php\cacert.pem
             * Turning off CURLOPT_SSL_VERIFYPEER allows man in the middle (MITM) attacks, which you don't want!
             */
            CURLOPT_SSL_CIPHER_LIST => 'TLSv1+HIGH:!SSLv2:!aNULL:!eNULL',
        );

        if (!empty($configArray)) {
            foreach ($configArray as $key => $value) {
                $this->$key = $value;
            }
        }
    }

    protected function zohoCrmApiCall(
        $path,
        $method = \EHttpClient::GET,
        $getParameters = null,
        $postParameters = null,
        $postBody = null,
        $bodyEncodingType = null
    ) {
        $adapter = new \EHttpClientAdapterCurl();

        $client = new \EHttpClient(
            $path,
            array(
                'maxredirects' => 2,
                'timeout' => $this->timeout,
                'adapter' => 'EHttpClientAdapterCurl',
            )
        );

        $client->setMethod($method);

        $defaultGetParameters = array('scope' => static::SCOPE);

        if (!empty($this->authToken)) {
            $defaultGetParameters['authtoken'] = $this->authToken;
        }

        // GET parameters
        if (!empty($getParameters)) {
            $getParameters = array_merge($defaultGetParameters, $getParameters);
        } else {
            $getParameters = $defaultGetParameters;
        }
        $client->setParameterGet($getParameters);

        // POST parameters
        if (!empty($postParameters)) {
            $client->setParameterPost($postParameters);
        }

        // raw POST data
        if (!empty($postBody)) {
            $client->setRawData($postBody, $bodyEncodingType);
        }

        if (!empty($postParameters) && !empty($postBody)) {
            \Yii::log(
                'Attempt to send POST parameters and POST data. ' .
                "Setting raw POST data for a request will override any POST parameters or file uploads.\n" .
                EUtils::printVarDump($client, true),
                'warning',
                'ext.eZohoCrm'
            );
        }

        $client->setAdapter($adapter);
        $adapter->setConfig(array('curloptions' => $this->curlOptions));
        if ($this->debug) {
            \Yii::log(
                "Sending of request to Zoho CRM API:\n" . EUtils::printVarDump($client, true),
                'info',
                'ext.eZohoCrm'
            );
        }

        $this->attemptsCount = 0;

        $json = $this->request($client)->getBody();
        $decodedResponse = json_decode($json);
        $jsonLastError = EUtils::getJsonLastError();
        if (isset($jsonLastError)) {
            throw new EZohoCrmException("Invalid JSON: $jsonLastError.", EZohoCrmException::ZOHO_CRM_INVALID_JSON);
        }
        if (!is_object($decodedResponse)) {
            throw new EZohoCrmException(
                "Object expected as decoded response, but got\n" . EUtils::printVarDump($decodedResponse, true),
                EZohoCrmException::ZOHO_CRM_INVALID_RESPONSE
            );
        }
        if (isset($decodedResponse->response->error)) {
            throw new EZohoCrmException(
                'Error ' . $decodedResponse->response->error->code . ': ' . $decodedResponse->response->error->message .
                ' Uri was "' . $decodedResponse->response->uri . '".',
                EZohoCrmException::ZOHO_CRM_RESPONSE_ERROR
            );
        }

        if ($this->print) {
            $this->printResponse($json);

            return null;
        } else {

            return $this->preprocessResponse($decodedResponse);
        }
    }

    /**
     * Preprocess response before return to main application. If you want to add your own processing it is good idea
     * to override this method.
     * @param $response
     * @return mixed
     */
    protected function preprocessResponse($response)
    {
        return $this->rowToArray($response);
    }

    /**
     * @param \EHttpClient $client
     * @return mixed
     * @throws \EHttpClientException
     * @throws \Exception
     */
    protected function request($client)
    {
        try {
            $this->attemptsCount++;
            $response = $client->request();
        } catch (\EHttpClientException $e) {
            if ($this->maxAttempts == 1 || strpos(strtolower($e->getMessage()), 'timed out') === false) {
                // repeating of requests disabled or not timed out error
                throw $e;
            }
            \Yii::log(
                "exception 'EHttpClientException' with message '{$e->getMessage()}'" .
                " in {$e->getFile()}:{$e->getLine()}\nStack trace:\n" . $e->getTraceAsString(),
                'error',
                'exception.EHttpClientException'
            );
            if ($this->attemptsCount < $this->maxAttempts) {
                sleep($this->sleepTime);
                $response = $this->request($client);
            } else {
                throw new EZohoCrmException(
                    "Can't perform request after {$this->attemptsCount} attempts " .
                    "with {$this->sleepTime} second(s) intervals.",
                    EZohoCrmException::RETRY_ATTEMPTS_LIMIT
                );
            }
        }

        return $response;
    }

    /**
     * convertLead
     * You can use this method to convert lead to potential, account and contact.
     * @link https://www.zoho.com/crm/help/api/convertlead.html
     * @param $leadId
     * @param $createPotential
     * @param $assignTo
     * @param $notifyLeadOwner
     * @param $notifyNewEntityOwner
     * @param null $potentialName
     * @param null $closingDate
     * @param null $potentialStage
     * @param null $contactRole
     * @param null $amount
     * @param null $probability
     * @param bool $excludeNull
     * @param integer $version
     * @return mixed
     * @throws \Exception
     */
    public function convertLead(
        $leadId,
        $createPotential,
        $assignTo,
        $notifyLeadOwner,
        $notifyNewEntityOwner,
        $potentialName = null,
        $closingDate = null,
        $potentialStage = null,
        $contactRole = null,
        $amount = null,
        $probability = null,
        $excludeNull = false,
        $version = self::VERSION
    ) {
        $moduleBefore = $this->module;
        $this->module = static::MODULE_POTENTIALS;

        $rowNo1 = array(
            'createPotential' => $this->getBoolean($createPotential),
            'assignTo' => (string)$assignTo,
            'notifyLeadOwner' => $this->getBoolean($notifyLeadOwner),
            'notifyNewEntityOwner' => $this->getBoolean($notifyNewEntityOwner),
        );

        if ($closingDate instanceof \DateTime) {
            $closingDate = $closingDate->format('m/d/Y');
        }

        $rowNo2 = array(
            'Potential Name' => (string)$potentialName,
            'Closing Date' => (string)$closingDate,
            'Potential Stage' => (string)$potentialStage,
            'Contact Role' => (string)$contactRole,
            'Amount' => (string)$amount,
            'Probability' => (string)$probability,
        );

        $xmlData = '<' . $this->module . '><row no="1">';

        foreach ($rowNo1 as $key => $value) {
            $xmlData .= '<option val="' . $key . '">' . $value . '</option>';
        }
        $xmlData .= '</row>';
        if ($createPotential) {
            $xmlData .= '<row no="2">';
            foreach ($rowNo2 as $key => $value) {
                $xmlData .= '<FL val="' . $key . '">' . $value . '</FL>';
            }
            $xmlData .= '</row>';
        }
        $xmlData .= '</' . $this->module . '>';

        $path = static::BASE_URL . $moduleBefore . '/' . __FUNCTION__;

        $getParameters = array(
            'leadId' => (string)$leadId,
            'xmlData' => $xmlData,
            'newFormat' => $this->getNewFormat($excludeNull),
            'version' => $version,
        );

        $this->module = $moduleBefore;

        return $this->zohoCrmApiCall($path, \EHttpClient::GET, $getParameters);
    }

    /**
     * deleteRecords
     * You can use this method to delete the selected record (you must specify unique ID
     * of the record) and move to the recycle bin.
     * @link https://www.zoho.com/crm/help/api/deleterecords.html
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    public function deleteRecords($id)
    {
        $path = static::BASE_URL . $this->module . '/' . __FUNCTION__;

        $getParameters = array('id' => $id);

        return $this->zohoCrmApiCall($path, \EHttpClient::GET, $getParameters);
    }

    /**
     * generateAuthToken
     * The Zoho CRM API is available in all editions of Zoho CRM. To use the API, you'll
     * require the Zoho CRM Authentication Token from your CRM account. Please make sure
     * that you have the permission to access the API service. If you do not have
     * permission, please contact your CRM administrator.
     * @link https://www.zoho.com/crm/help/api/using-authentication-token.html
     * @param $usernameOrEmail
     * @param $password
     * @return string
     * @throws \Exception
     */
    public function generateAuthToken($usernameOrEmail, $password)
    {
        $path = static::AUTH_TOKEN_REQUEST_URL;

        $getParameters = array(
            'SCOPE' => static::SCOPE_AUTH_TOKEN_REQUEST,
            'EMAIL_ID' => $usernameOrEmail,
            'PASSWORD' => $password,
        );

        return $this->zohoCrmApiCall($path, \EHttpClient::GET, $getParameters);
    }

    /**
     * getBoolean
     * Returns a string for the given boolean.
     * @param $boolean
     * @return string
     */
    protected function getBoolean($boolean)
    {
        return $boolean ? 'true' : 'false';
    }

    /**
     * getCVRecords
     * You can use the getCVRecords method to fetch data with respect to the Custom View in Zoho CRM.
     * IMPORTANT: Irrespective of the Zoho CRM Edition, you can send only 250 API requests / day.
     * In each request you can fetch a maximum of 200 records.
     * @link https://www.zoho.com/crm/help/api/getcvrecords.html
     * @param $cvName
     * @param integer $fromIndex
     * @param integer $toIndex
     * @param null $lastModifiedTime
     * @param bool $excludeNull
     * @param integer $version
     * @return mixed
     * @throws \Exception
     */
    public function getCVRecords(
        $cvName,
        $fromIndex = 1,
        $toIndex = 20,
        $lastModifiedTime = null,
        $excludeNull = false,
        $version = self::VERSION
    ) {
        $path = static::BASE_URL . $this->module . '/' . __FUNCTION__;

        $getParameters = array(
            'cvName' => $cvName,
            'fromIndex' => $fromIndex,
            'toIndex' => $toIndex,
            'lastModifiedTime' => $lastModifiedTime,
            'newFormat' => $this->getNewFormat($excludeNull),
            'version' => $version,
        );

        return $this->zohoCrmApiCall($path, \EHttpClient::GET, $getParameters);
    }

    /**
     * getEscapedValue
     * Returns the escaped value which can be used in the xmlData parameter.
     * @param $value
     * @param $method
     * @return string
     * @throws EZohoCrmException
     */
    protected function getEscapedValue($value, $method)
    {
        switch ($method) {
            case \EHttpClient::GET:
                $value = '<![CDATA[' . htmlentities($value) . ']]>';
                break;
            case \EHttpClient::POST:
                $value = '<![CDATA[' . $value . ']]>';
                break;
            default:
                throw new EZohoCrmException(
                    "Unknown HTTP request method $method.",
                    EZohoCrmException::UNKNOWN_HTTP_METHOD
                );
        }

        return $value;
    }

    /**
     * getFields
     * You can use the getFields method to fetch details of the fields available in a particular module.
     * @link https://www.zoho.com/crm/help/api/getfields.html
     * @return mixed
     * @throws \Exception
     */
    public function getFields()
    {
        $path = static::BASE_URL . $this->module . '/' . __FUNCTION__;

        return $this->zohoCrmApiCall($path, \EHttpClient::GET);
    }

    /**
     * getModules
     * You can use the getModules method to get the list of modules in your CRM account.
     * @link https://www.zoho.com/crm/help/api/getmodules.html
     * @return mixed
     * @throws \Exception
     */
    public function getModules()
    {
        $path = static::BASE_URL . static::MODULE_INFO . '/' . __FUNCTION__;

        return $this->zohoCrmApiCall($path, \EHttpClient::GET);
    }

    /**
     * getMyRecords
     * You can use the getMyRecords method to fetch data by the owner of the
     * Authentication token specified in the API request.
     * @link https://www.zoho.com/crm/help/api/getmyrecords.html
     * @param array $columns
     * @param integer $fromIndex
     * @param integer $toIndex
     * @param null $sortColumnString
     * @param string $sortOrderString
     * @param null $lastModifiedTime
     * @param bool $excludeNull
     * @param integer $version
     * @return mixed
     */
    public function getMyRecords(
        $columns = array(),
        $fromIndex = 1,
        $toIndex = 20,
        $sortColumnString = null,
        $sortOrderString = self::SORT_ORDER_ASC,
        $lastModifiedTime = null,
        $excludeNull = false,
        $version = self::VERSION
    ) {
        return $this->getRecords(
            $columns,
            $fromIndex,
            $toIndex,
            $sortColumnString,
            $sortOrderString,
            $lastModifiedTime,
            $excludeNull,
            $version,
            true
        );
    }

    /**
     * getNewFormat
     * New format is an integer and can be either 1 or 2. 1 means that null values are excluded, 2 means the opposite.
     * @param $excludeNull
     * @return integer
     */
    protected function getNewFormat($excludeNull)
    {
        return ($excludeNull ? 1 : 2);
    }

    /**
     * getRecordById
     * You can use this method to retrieve individual records by record ID.
     * @link https://www.zoho.com/crm/help/api/getrecordbyid.html
     * @param $id
     * @param bool $excludeNull
     * @param integer $version
     * @return mixed
     * @throws \Exception
     */
    public function getRecordById($id, $excludeNull = false, $version = self::VERSION)
    {
        $path = static::BASE_URL . $this->module . '/' . __FUNCTION__;

        $getParameters = array(
            'id' => (string)$id,
            'newFormat' => $this->getNewFormat($excludeNull),
            'version' => $version,
        );

        return $this->zohoCrmApiCall($path, \EHttpClient::GET, $getParameters);
    }

    /**
     * getRecords
     * You can use the getRecords method to fetch all users data specified in the API request.
     * @link https://www.zoho.com/crm/help/api/getrecords.html
     * @param array $columns
     * @param integer $fromIndex
     * @param integer $toIndex
     * @param null|string $sortColumnString
     * @param string $sortOrderString
     * @param null|string $lastModifiedTime
     * @param bool $excludeNull
     * @param integer $version
     * @param bool $myRecords
     * @return mixed
     * @throws \Exception
     */
    public function getRecords(
        $columns = array(),
        $fromIndex = 1,
        $toIndex = 20,
        $sortColumnString = null,
        $sortOrderString = self::SORT_ORDER_ASC,
        $lastModifiedTime = null,
        $excludeNull = false,
        $version = self::VERSION,
        $myRecords = false
    ) {
        $path = static::BASE_URL . $this->module . '/' . ($myRecords ? 'getMyRecords' : __FUNCTION__);

        $getParameters = array(
            'selectColumns' => $this->getSelectColumns($columns),
            'fromIndex' => $fromIndex,
            'toIndex' => $toIndex,
            'sortColumnString' => $sortColumnString,
            'sortOrderString' => $sortOrderString,
            'lastModifiedTime' => $lastModifiedTime,
            'newFormat' => ($excludeNull ? 1 : 2),
            'version' => $version,
        );

        return $this->zohoCrmApiCall($path, \EHttpClient::GET, $getParameters);
    }

    /**
     * getRelatedRecords
     * You can use the getRelatedRecords method to fetch related records.
     * @link https://www.zoho.com/crm/help/api/getrelatedrecords.html
     * @param $parentModule
     * @param $id
     * @param bool $excludeNull
     * @param integer $fromIndex
     * @param integer $toIndex
     * @return mixed
     * @throws \Exception
     */
    public function getRelatedRecords($parentModule, $id, $excludeNull = false, $fromIndex = 1, $toIndex = 20)
    {
        $nonSupportedModules = array(static::MODULE_EMAILS, static::MODULE_COMPETITORS, static::MODULE_INTEGRATIONS);

        if (in_array($this->module, $nonSupportedModules)) {
            throw new EZohoCrmException(
                "Module $this->module not supported for this function.",
                EZohoCrmException::MODULE_NOT_SUPPORTED
            );
        }

        $path = static::BASE_URL . $this->module . '/' . __FUNCTION__;

        $getParameters = array(
            'parentModule' => (string)$parentModule,
            'id' => (string)$id,
            'newFormat' => $this->getNewFormat($excludeNull),
            'fromIndex' => (string)$fromIndex,
            'toIndex' => (string)$toIndex,
        );

        return $this->zohoCrmApiCall($path, \EHttpClient::GET, $getParameters);
    }

    /**
     * getSearchRecords
     * You can use this method to search records by expressions of the selected columns.
     * @link https://www.zoho.com/crm/help/api/getsearchrecords.html
     * @param array $selectColumns
     * @param $searchCondition
     * @param bool $excludeNull
     * @param integer $fromIndex
     * @param integer $toIndex
     * @param integer $version
     * @return mixed
     * @throws \Exception
     */
    public function getSearchRecords(
        $selectColumns = array(),
        $searchCondition,
        $excludeNull = false,
        $fromIndex = 1,
        $toIndex = 20,
        $version = self::VERSION
    ) {
        $path = static::BASE_URL . $this->module . '/' . __FUNCTION__;

        $getParameters = array(
            'selectColumns' => $this->getSelectColumns($selectColumns),
            'searchCondition' => $searchCondition,
            'newFormat' => $this->getNewFormat($excludeNull),
            'fromIndex' => $fromIndex,
            'toIndex' => $toIndex,
            'version' => $version,
        );

        return $this->zohoCrmApiCall($path, \EHttpClient::GET, $getParameters);
    }

    /**
     * getSearchRecordsByPDC
     * You can use this method to search the values based on predefined columns.
     * @link https://www.zoho.com/crm/help/api/getsearchrecordsbypdc.html
     * @param array $selectColumns
     * @param $searchColumn
     * @param $searchValue
     * @param bool $excludeNull
     * @param integer $version
     * @return mixed
     * @throws \Exception
     */
    public function getSearchRecordsByPDC(
        $selectColumns = array(),
        $searchColumn,
        $searchValue,
        $excludeNull = false,
        $version = self::VERSION
    ) {
        $path = static::BASE_URL . $this->module . '/' . __FUNCTION__;

        $getParameters = array(
            'selectColumns' => $this->getSelectColumns($selectColumns),
            'searchColumn' => (string)$searchColumn,
            'searchValue' => (string)$searchValue,
            'newFormat' => $this->getNewFormat($excludeNull),
            'version' => $version,
        );

        return $this->zohoCrmApiCall($path, \EHttpClient::GET, $getParameters);
    }

    /**
     * getSelectColumns
     * Returns a string indicating which columns should be returned based on the selectColumns input variable.
     * @param $selectColumns
     * @return string
     */
    protected function getSelectColumns($selectColumns)
    {
        if ($selectColumns === array()) {
            return static::ALL_COLUMNS;
        } else {
            return $this->module . '(' . implode(',', $selectColumns) . ')';
        }
    }

    /**
     * getUsers
     * You can use the getUsers method to get the list of users in your organization.
     * @link https://www.zoho.com/crm/help/api/getusers.html
     * @param $type
     * @param bool $excludeNull
     * @return mixed
     * @throws \Exception
     */
    public function getUsers($type, $excludeNull = false)
    {
        $path = static::BASE_URL . static::MODULE_USERS . '/' . __FUNCTION__;

        $getParameters = array(
            'type' => (string)$type,
            'newFormat' => $this->getNewFormat($excludeNull),
        );

        return $this->zohoCrmApiCall($path, \EHttpClient::GET, $getParameters);
    }

    /**
     * insertRecords
     * You can use the insertRecords method to insert records into the required Zoho CRM module.
     * @link https://www.zoho.com/crm/help/api/insertrecords.html
     * @param $records
     * @param bool $wfTrigger
     * @param integer $duplicateCheck
     * @param bool $isApproval
     * @param bool $excludeNull
     * @param integer $version
     * @return mixed
     * @throws \Exception
     */
    public function insertRecords(
        $records,
        $wfTrigger = false,
        $duplicateCheck = 1,
        $isApproval = false,
        $excludeNull = false,
        $version = self::VERSION
    ) {
        $path = static::BASE_URL . $this->module . '/' . __FUNCTION__;

        $getParameters = array(
            'wfTrigger' => (string)$wfTrigger,
            'isApproval' => (string)$isApproval,
            'newFormat' => $this->getNewFormat($excludeNull),
            'version' => $version,
        );
        if ($duplicateCheck) {
            $getParameters['duplicateCheck'] = $duplicateCheck;
        }

        $postParameters = array('xmlData' => $this->transformRecordsToXmlData($records, \EHttpClient::POST));

        return $this->zohoCrmApiCall($path, \EHttpClient::POST, $getParameters, $postParameters);
    }

    /**
     * transformRecordsToXmlData
     * Transform one or multiple records to XML Data. This function can, for example, be
     * used to format an array of Leads to XML Data in order to make the data ready for
     * the insertRecords function.
     * @param $records
     * @param $method
     * @return string
     * @throws \Exception
     */
    public function transformRecordsToXmlData($records, $method)
    {
        $modulesNotSupportedForMultipleInsert = array(
            static::MODULE_QUOTES,
            static::MODULE_SALES_ORDERS,
            static::MODULE_INVOICES,
            static::MODULE_PURCHASE_ORDERS
        );

        if (count($records) > 1 && in_array($this->module, $modulesNotSupportedForMultipleInsert)) {
            throw new EZohoCrmException(
                "Module $this->module does not support multiple inserts.",
                EZohoCrmException::MODULE_NOT_SUPPORTED
            );
        }

        if (count($records) > static::MAX_RECORDS_INSERT_UPDATE) {
            throw new EZohoCrmException(
                'Only the first ' . static::MAX_RECORDS_INSERT_UPDATE .
                ' records will be considered when inserting multiple records.',
                EZohoCrmException::RECORDS_INSERT_UPDATE_LIMIT
            );
        }

        $xml = '<' . $this->module . '>';
        $rowNumber = 1;
        foreach ($records as $record) {
            $xml .= '<row no="' . $rowNumber++ . '">';
            foreach ($record as $key => $value) {
                $value = $this->getEscapedValue($value, $method);
                $xml .= '<FL val="' . $key . '">' . $value . '</FL>';
            }
            $xml .= '</row>';
        }
        $xml .= '</' . $this->module . '>';

        return $xml;
    }

    /**
     * updateRecords
     * You can use the updateRecords method to update or modify the records in Zoho CRM.
     * @link https://www.zoho.com/crm/help/api/updaterecords.html
     * @param $id
     * @param $records
     * @param bool $wfTrigger
     * @param bool $excludeNull
     * @param integer $version
     * @return mixed
     * @throws \Exception
     */
    public function updateRecords($id, $records, $wfTrigger = false, $excludeNull = false, $version = self::VERSION)
    {
        $path = static::BASE_URL . $this->module . '/' . __FUNCTION__;

        $getParameters = array(
            'id' => (string)$id,
            'wfTrigger' => (string)$wfTrigger,
            'newFormat' => $this->getNewFormat($excludeNull),
            'version' => $version,
        );
        $postParameters = array(
            'xmlData' => $this->transformRecordsToXmlData($records, \EHttpClient::POST),
        );

        return $this->zohoCrmApiCall($path, \EHttpClient::POST, $getParameters, $postParameters);
    }

    /**
     * updateRelatedRecords
     * You can use the updateRelatedRecords method to update records related to another record.
     * @link https://www.zoho.com/crm/help/api/updaterelatedrecords.html
     * @param $relatedModule
     * @param $id
     * @param $records
     * @return mixed|null
     * @throws \Exception
     */
    public function updateRelatedRecords($relatedModule, $id, $records)
    {
        $path = static::BASE_URL . $this->module . '/' . __FUNCTION__;

        $getParameters = array(
            'relatedModule' => (string)$relatedModule,
            'id' => (string)$id,
            'xmlData' => $this->transformRecordsToXmlData($records, \EHttpClient::GET),
        );

        return $this->zohoCrmApiCall($path, \EHttpClient::GET, $getParameters);
    }

    /**
     * printResponse
     * Print response.
     * @param $response
     */
    protected function printResponse($response)
    {
        echo '<pre>';
        print_r($response);
        echo '</pre>';
    }

    public function checkResponseOnMultipleRecordRequest($response, $records)
    {
        $errorMessage = '';
        foreach ($response->response->result->row as $item) {
            if (isset($item->error)) {
                $errorMessage .= "\nError {$item->error->code}: {$item->error->details}";
            }
        }
        if (!empty($errorMessage)) {
            throw new EZohoCrmException(
                $errorMessage . "\nUri was \"{$response->response->uri}\".\n" . "Records data:\n" .
                EUtils::printVarDump($records, true),
                EZohoCrmException::ZOHO_CRM_RESPONSE_ERROR
            );
        }
    }

    /**
     * This method needed to make response of Zoho CRM API more consequent: API calls containing "row" return data
     * with different structure depending on number of items, there is difference between response with one item and
     * with many items, this methods makes response unified.
     * @param $response
     * @return mixed
     */
    protected function rowToArray($response)
    {
        $path = array('response', 'result', $this->module, 'row');
        $rows = EUtils::get($response, $path);
        if (isset($rows) && is_object($rows)) {
            EUtils::set($response, $path, array($rows));
        }

        return $response;
    }
}