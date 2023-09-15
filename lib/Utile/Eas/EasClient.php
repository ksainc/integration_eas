<?php
//declare(strict_types=1);

/**
* @copyright Copyright (c) 2023 Sebastian Krupinski <krupinski01@gmail.com>
* 
* @author Sebastian Krupinski <krupinski01@gmail.com>
*
* @license AGPL-3.0-or-later
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU Affero General Public License as
* published by the Free Software Foundation, either version 3 of the
* License, or (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*
*/

namespace OCA\EAS\Utile\Eas;

/**
 * Exchange Active Sync Client
 */
class EasClient
{
    /**
     * Transport Version
     *
     * @var int
     */
    const TRANSPORT_VERSION_1 = CURL_HTTP_VERSION_1_0;
    const TRANSPORT_VERSION_1_1 = CURL_HTTP_VERSION_1_1;
    const TRANSPORT_VERSION_2 = CURL_HTTP_VERSION_2_0;
    /**
     * Transport Mode
     *
     * @var string
     */
    const TRANSPORT_MODE_STANDARD = 'http://';
    const TRANSPORT_MODE_SECURE = 'https://';
    /**
     * Service Version
     *
     * @var integer
     */
    const SERVICE_VERSION_161 = '16.1';
    const SERVICE_VERSION_160 = '16.0';
    const SERVICE_VERSION_141 = '14.1';
    const SERVICE_VERSION_140 = '14.0';
    const SERVICE_VERSION_121 = '12.1';
    const SERVICE_VERSION_120 = '12.0';
    /**
     * Transport Mode
     *
     * @var string
     */
    protected string $_TransportMode = self::TRANSPORT_MODE_SECURE;
    /**
     * Transpost Header
     */
    protected array $_TransportHeader = [
		'Connection' => 'Connection: Keep-Alive',
		'Content-Type' => 'Content-Type: application/vnd.ms-sync.wbxml',
		'MS-ASProtocolVersion' => 'MS-ASProtocolVersion: ' . self::SERVICE_VERSION_161,
        'X-MS-PolicyKey' => 'X-MS-PolicyKey: 0'
    ];
    /**
     * Transpost Options
     */
    protected array $_TransportOptions = [
        CURLOPT_USERAGENT => 'NextCloudEAS/1.0 (1.0; x64)',
        CURLOPT_HTTP_VERSION => self::TRANSPORT_VERSION_2,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_POST => true,
        CURLOPT_CUSTOMREQUEST => null
    ];
     /**
     * Service Host
     *
     * @var string
     */
    protected string $_ServiceHost = '';
    /**
     * Service Path
     *
     * @var string
     */
    protected string $_ServicePath = '/Microsoft-Server-ActiveSync';
     /**
     * Services Uri base that we are going to connect to
     *
     * @var string 
     */
    protected string $_ServiceUriBase = '';
    /**
     * Services uri qery that we are going to send to
     *
     * @var string 
     */
    protected string $_ServiceUriQuery = '';
    /**
     * Authentication to use when connecting to the service
     *
     * @var EasAuthenticationBasic|EasAuthenticationBearer
     */
    protected $_ServiceAuthentication;
    /**
     * Service Devide Type
     *
     * @var string
     */
    protected string $_ServiceDeviceType = 'NextCloudEAS';
    /**
     * Service Devide Id
     *
     * @var string
     */
    protected string $_ServiceDeviceId = '';
    /**
     * Service Devide Key
     *
     * @var string
     */
    protected string $_ServiceDeviceKey = '';
    /**
     * Services version that we are going to connect with
     *
     * @var string 
     */
    protected string $_ServiceVersion;
    /**
     * cURL resource used to make the request
     *
     * @var CurlHandle
     */
    protected $_client;
    
    /**
     * Constructor for the ExchangeWebServices class
     *
     * @param string $host              EAS Service Provider (FQDN, IPv4, IPv6)
     * @param string $authentication    EAS Authentication
     * @param string $version           EAS Protocol Version
     */
    public function __construct(
        $host = '',
        $authentication = null,
        $id = '',
        $key = '',
        $version = self::SERVICE_VERSION_161
    ) {

        // set service host
        $this->setHost($host);
        // set service authentication
        $this->setAuthentication($authentication);
        // set service device id
        $this->setDeviceId($id);
        // set service device key
        $this->setDeviceKey($key);
        // set service version
        $this->setVersion($version);

    }

    public function configureTransportVersion(int $value): void {
        
        // store parameter
        $this->_TransportOptions[CURLOPT_HTTP_VERSION] = $value;
        // destroy existing client will need to be initilized again
        $this->_client = null;

    }
    
    public function configureTransportMode(string $value): void {

        // store parameter
        $this->_TransportMode = $value;
        // destroy existing client will need to be initilized again
        $this->_client = null;
        // reconstruct service location
        $this->constructServiceUriBase();

    }

    public function configureTransportOptions(array $options): void {

        // store parameter
        $this->_TransportOptions = array_replace($this->_TransportOptions, $options);
        // destroy existing client will need to be initilized again
        $this->_client = null;

    }

    public function configureTransportVerification(bool $value): void {

        // store parameter
        $this->_TransportOptions[CURLOPT_SSL_VERIFYPEER] = $value;
        // destroy existing client will need to be initilized again
        $this->_client = null;

    }

    protected function constructServiceUriBase(): void {

        // set service location
        $this->_ServiceUriBase = $this->_TransportMode . $this->_ServiceHost . $this->_ServicePath;

    }

    protected function constructServiceUriQuery(): void {
        
        // set service location
        $this->_ServiceUriQuery = '?DeviceType=' . $this->_ServiceDeviceType . 
                                  '&DeviceId=' . $this->_ServiceDeviceId . 
                                  '&User=' . $this->_ServiceAuthentication->Id;

    }

    public function setTransportAgent(string $value): void {

        // store transport agent parameter
        $this->_TransportOptions[CURLOPT_USERAGENT] = $value;
        // destroy existing client will need to be initilized again
        $this->_client = null;

    }

    public function getTransportAgent(): string {

        // return transport agent paramater
        return $this->_TransportOptions[CURLOPT_USERAGENT];

    }

    /**
     * Gets the service host parameter
     *
     * @return string
     */
    public function getHost(): string {
        
        // return service host parameter
        return $this->_ServiceHost;

    }

    /**
     * Sets the service host parameter to be used for all requests
     *
     * @param string $value
     */
    public function setHost(string $value): void {

        // store service host
        $this->_ServiceHost = $value;
        // destroy existing client will need to be initilized again
        $this->_client = null;
        // reconstruct service uri base
        $this->constructServiceUriBase();

    }

    /**
     * Gets the service path parameter
     *
     * @return string
     */
    public function getPath(): string {
        
        // return service path parameter
        return $this->_ServicePath;

    }

    /**
     * Sets the service path parameter to be used for all requests
     *
     * @param string $value
     */
    public function setPath(string $value): void {

        // store service path parameter
        $this->ServicePath = $value;
        // destroy existing client will need to be initilized again
        $this->_client = null;
        // reconstruct service uri base
        $this->constructServiceUriBase();

    }

    /**
     * Gets the protocol version parameter
     *
     * @return string
     */
    public function getVersion(): string {
        
        // return version information
        return $this->_ServiceVersion;

    }

    /**
     * Sets the protocol version parameter to be used for all requests
     *
     * @param int $value
     */
    public function setVersion(string $value): void {

        // store service version
        $this->_ServiceVersion = $value;
        // set version on client
        $this->_TransportHeader['MS-ASProtocolVersion'] = 'MS-ASProtocolVersion: ' . $value;

    }

    /**
     * Gets the service device id parameter
     *
     * @return string
     */
    public function getDeviceId(): string {
        
        // return service device id parameter
        return $this->_ServiceDeviceId;

    }

    /**
     * Sets the service device policy key to be used for all requests
     *
     * @param string $value
     */
    public function setDeviceId(string $value): void {

        // store service policy key parameter
        $this->_ServiceDeviceId = $value;
        // set key on client
        $this->constructServiceUriQuery();

    }

    /**
     * Gets the service device policy key parameter
     *
     * @return string
     */
    public function getDeviceKey(): string {
        
        // return service policy key parameter
        return $this->_ServiceDeviceKey;

    }

    /**
     * Sets the service device policy key to be used for all requests
     *
     * @param string $value
     */
    public function setDeviceKey(string $value): void {

        // store service policy key parameter
        $this->_ServiceDeviceKey = $value;
        // set key on client
        $this->_TransportHeader['X-MS-PolicyKey'] = 'X-MS-PolicyKey: ' . $value;

    }

     /**
     * Gets the authentication parameters object
     *
     * @return AuthenticationBasic|AuthenticationBeare
     */
    public function getAuthentication(): EasAuthenticationBasic|EasAuthenticationBearer {
        
        // return authentication information
        return $this->_ServiceAuthentication;

    }

     /**
     * Sets the authentication parameters to be used for all requests
     *
     * @param AuthenticationBasic|AuthenticationBearer $value
     */
    public function setAuthentication(EasAuthenticationBasic|EasAuthenticationBearer $value): void {
        
        // store parameter
        $this->_ServiceAuthentication = $value;
        // destroy existing client will need to be initilized again
        $this->_client = null;
        // set service basic authentication
        if ($this->_ServiceAuthentication instanceof EasAuthenticationBasic) {
            $this->_TransportOptions[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
            $this->_TransportOptions[CURLOPT_USERPWD] = $this->_ServiceAuthentication->Id . ':' . $this->_ServiceAuthentication->Secret;
        }
        // set service bearer authentication
        if ($this->_ServiceAuthentication instanceof EasAuthenticationBearer) {
            unset($this->_TransportOptions[CURLOPT_HTTPAUTH]);
            $this->_TransportHeader['Authorization'] = 'Authorization: Bearer ' . $this->_ServiceAuthentication->Token;
        }
        // construct service query
        $this->constructServiceUriQuery();

    }

    public function performCommand($message): null|string {
        // clear last headers and response
        $this->_ResponseHeaders = '';
        $this->_ResponseData = '';

        // evaluate if http client is initilized and location is the same
        if (!isset($this->_client) || curl_getinfo($this->_client, CURLINFO_EFFECTIVE_URL) != $location) {
            $this->_client = curl_init($location);
            curl_setopt_array($this->_client, $this->_TransportOptions);
        }

        curl_setopt($this->_client, CURLOPT_HTTPHEADER, array_values($this->_TransportHeader));
        // set request data
        if (!empty($message)) {
            curl_setopt($this->_client, CURLOPT_POSTFIELDS, $message);
        }
        // execute request
        $this->_ResponseData = curl_exec($this->_client);

        // evealuate execution errors
        $code = curl_errno($this->_client);
        if ($code > 0) {
            throw new RuntimeException(curl_error($this->_client), $code);
        }

        // evaluate http responses
        $code = (int) curl_getinfo($this->_client, CURLINFO_RESPONSE_CODE);
        if ($code > 400) {
            switch ($code) {
                case 401:
                    throw new RuntimeException('Unauthorized', $code);
                    break;
                case 403:
                    throw new RuntimeException('Forbidden', $code);
                    break;
                case 404:
                    throw new RuntimeException('Not Found', $code);
                    break;
                case 408:
                    throw new RuntimeException('Request Timeout', $code);
                    break;
            }
        }

        // separate headers and body
        $size = curl_getinfo($this->_client, CURLINFO_HEADER_SIZE);
        $this->_ResponseHeaders = substr($this->_ResponseData, 0, $size);
        $this->_ResponseData = substr($this->_ResponseData, $size);
        // return body
        return $this->_ResponseData;
    }

    public function performConnect(): null|string {

        // configure client for command
        unset($this->_TransportOptions[CURLOPT_POST]);
        $this->_TransportOptions[CURLOPT_CUSTOMREQUEST] = 'OPTIONS';
        $this->_TransportOptions[CURLOPT_URL] = $this->_ServiceUriBase;
        // perform command
        $data = $this->performCommand('');
        // configure client to defualts
        $this->_TransportOptions[CURLOPT_POST] = true;
        unset($this->_TransportOptions[CURLOPT_CUSTOMREQUEST]);
        // return response body
        return $data;

    }
    
    public function performFind($data): null|string {

        $this->_TransportOptions[CURLOPT_URL] = $this->_ServiceUriBase . $this->_ServiceUriQuery . '&Cmd=Find';
        return $this->performCommand($data);

    }

    public function performCollectionCreate($data): null|string {

        $this->_TransportOptions[CURLOPT_URL] = $this->_ServiceUriBase . $this->_ServiceUriQuery . '&Cmd=FolderCreate';
        return $this->performCommand($data);

    }

    public function performCollectionDelete($data): null|string {

        $this->_TransportOptions[CURLOPT_URL] = $this->_ServiceUriBase . $this->_ServiceUriQuery . '&Cmd=FolderDelete';
        return $this->performCommand($data);

    }

    public function performCollectionUpdate($data): null|string {

        $this->_TransportOptions[CURLOPT_URL] = $this->_ServiceUriBase . $this->_ServiceUriQuery . '&Cmd=FolderUpdate';
        return $this->performCommand($data);

    }

    public function performCollectionSync($data): null|string {

        $this->_TransportOptions[CURLOPT_URL] = $this->_ServiceUriBase . $this->_ServiceUriQuery . '&Cmd=FolderSync';
        return $this->performCommand($data);

    }

    public function performGetAttachment($data): null|string {

        $this->_TransportOptions[CURLOPT_URL] = $this->_ServiceUriBase . $this->_ServiceUriQuery . '&Cmd=GetAttachment';
        return $this->performCommand($data);

    }

    public function performGetHierarchy($data): null|string {

        $this->_TransportOptions[CURLOPT_URL] = $this->_ServiceUriBase . $this->_ServiceUriQuery . '&Cmd=GetHierarchy';
        return $this->performCommand($data);

    }

    public function performEntityEstimate($data): null|string {

        $this->_TransportOptions[CURLOPT_URL] = $this->_ServiceUriBase . $this->_ServiceUriQuery . '&Cmd=GetItemEstimate';
        return $this->performCommand($data);

    }

    public function performEntityOperations($data): null|string {

        $this->_TransportOptions[CURLOPT_URL] = $this->_ServiceUriBase . $this->_ServiceUriQuery . '&Cmd=ItemOperations';
        return $this->performCommand($data);

    }

    public function performMeetingResponse($data): null|string {

        $this->_TransportOptions[CURLOPT_URL] = $this->_ServiceUriBase . $this->_ServiceUriQuery . '&Cmd=MeetingResponse';
        return $this->performCommand($data);

    }

    public function performMoveItems($data): null|string {

        $this->_TransportOptions[CURLOPT_URL] = $this->_ServiceUriBase . $this->_ServiceUriQuery . '&Cmd=MoveItems';
        return $this->performCommand($data);

    }
    
    public function performPing($data): null|string {

        $this->_TransportOptions[CURLOPT_URL] = $this->_ServiceUriBase . $this->_ServiceUriQuery . '&Cmd=Ping';
        return $this->performCommand($data);

    }

    public function performProvision($data): null|string {

        $this->_TransportOptions[CURLOPT_URL] = $this->_ServiceUriBase . $this->_ServiceUriQuery . '&Cmd=Provision';
        return $this->performCommand($data);

    }
    
    public function performResolveRecipients($data): null|string {

        $this->_TransportOptions[CURLOPT_URL] = $this->_ServiceUriBase . $this->_ServiceUriQuery . '&Cmd=ResolveRecipients';
        return $this->performCommand($data);

    }
    
    public function performEntitySearch($data): null|string {

        $this->_TransportOptions[CURLOPT_URL] = $this->_ServiceUriBase . $this->_ServiceUriQuery . '&Cmd=Search';
        return $this->performCommand($data);

    }
    
    public function performSendMail($data): null|string {

        $this->_TransportOptions[CURLOPT_URL] = $this->_ServiceUriBase . $this->_ServiceUriQuery . '&Cmd=SendMail';
        return $this->performCommand($data);

    }
    
    public function performSettings($data): null|string {

        $this->_TransportOptions[CURLOPT_URL] = $this->_ServiceUriBase . $this->_ServiceUriQuery . '&Cmd=Settings';
        return $this->performCommand($data);

    }
    
    public function performSmartForward($data): null|string {

        $this->_TransportOptions[CURLOPT_URL] = $this->_ServiceUriBase . $this->_ServiceUriQuery . '&Cmd=SmartForward';
        return $this->performCommand($data);

    }
    
    public function performSmartReply($data): null|string {

        $this->_TransportOptions[CURLOPT_URL] = $this->_ServiceUriBase . $this->_ServiceUriQuery . '&Cmd=SmartReply';
        return $this->performCommand($data);

    }

    public function performSync($data): null|string {

        $this->_TransportOptions[CURLOPT_URL] = $this->_ServiceUriBase . $this->_ServiceUriQuery . '&Cmd=Sync';
        return $this->performCommand($data);

    }

    public function performValidateCert($data): null|string {

        $this->_TransportOptions[CURLOPT_URL] = $this->_ServiceUriBase . $this->_ServiceUriQuery . '&Cmd=ValidateCert';
        return $this->performCommand($data);

    }
    
}
