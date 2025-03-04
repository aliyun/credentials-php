<?php

namespace AlibabaCloud\Credentials\Providers;

use AlibabaCloud\Credentials\Utils\Helper;
use AlibabaCloud\Credentials\Utils\Filter;
use AlibabaCloud\Credentials\Request\Request;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use RuntimeException;
use AlibabaCloud\Credentials\Credential\RefreshResult;
use AlibabaCloud\Configure\Config;

class URLCredentialsProvider extends SessionCredentialsProvider
{

    /**
     * @var string
     */
    private $credentialsURI;

    /**
     * @var int
     */
    private $connectTimeout = 5;

    /**
     * @var int
     */
    private $readTimeout = 5;

    /**
     * URLCredentialsProvider constructor.
     *
     * @param array $params
     * @param array $options
     */
    public function __construct(array $params = [], array $options = [])
    {
        $this->filterOptions($options);
        $this->filterCredentialsURI($params);
    }

    private function filterOptions(array $options)
    {
        if (isset($options['connectTimeout'])) {
            $this->connectTimeout = $options['connectTimeout'];
        }

        if (isset($options['readTimeout'])) {
            $this->readTimeout = $options['readTimeout'];
        }

        Filter::timeout($this->connectTimeout, $this->readTimeout);
    }

    private function filterCredentialsURI(array $params)
    {
        if (Helper::envNotEmpty(Config::ENV_PREFIX . 'CREDENTIALS_URI')) {
            $this->credentialsURI = Helper::env(Config::ENV_PREFIX . 'CREDENTIALS_URI');
        }

        if (isset($params['credentialsUri'])) {
            $this->credentialsURI = $params['credentialsUri'];
        }

        Filter::credentialsURI($this->credentialsURI);
    }

    /**
     * Get credentials by request.
     *
     * @return RefreshResult
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws GuzzleException
     */
    public function refreshCredentials()
    {
        $options = Request::commonOptions();
        $options['read_timeout'] = $this->readTimeout;
        $options['connect_timeout'] = $this->connectTimeout;

        $result = Request::createClient()->request('GET', $this->credentialsURI, $options);

        if ($result->getStatusCode() !== 200) {
            throw new RuntimeException('Error refreshing credentials from credentialsURI, statusCode: ' . $result->getStatusCode() . ', result: ' . (string) $result);
        }

        $credentials = $result->toArray();

        if (!isset($credentials['AccessKeyId']) || !isset($credentials['AccessKeySecret']) || !isset($credentials['SecurityToken']) || !isset($credentials['Expiration'])) {
            throw new RuntimeException('Error retrieving credentials from credentialsURI result:' . $result->toJson());
        }

        return new RefreshResult(new Credentials([
            'accessKeyId' => $credentials['AccessKeyId'],
            'accessKeySecret' => $credentials['AccessKeySecret'],
            'securityToken' => $credentials['SecurityToken'],
            'expiration' => \strtotime($credentials['Expiration']),
            'providerName' => $this->getProviderName(),
        ]), $this->getStaleTime(strtotime($credentials['Expiration'])));
    }


    /**
     * @return string
     */
    public function key()
    {
        return 'credential_uri#' . $this->credentialsURI;
    }

    /**
     * @return string
     */
    public function getProviderName()
    {
        return 'credential_uri';
    }
}
