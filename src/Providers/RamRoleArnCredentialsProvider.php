<?php

namespace AlibabaCloud\Credentials\Providers;

use AlibabaCloud\Credentials\Utils\Helper;
use AlibabaCloud\Credentials\Utils\Filter;
use AlibabaCloud\Credentials\Request\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use RuntimeException;
use AlibabaCloud\Credentials\Credential\RefreshResult;
use AlibabaCloud\Configure\Config;

class RamRoleArnCredentialsProvider extends SessionCredentialsProvider
{

    /**
     * @var CredentialsProvider
     */
    private $credentialsProvider;

    /**
     * @var string
     */
    private $roleArn;

    /**
     * @var string
     */
    private $roleSessionName;

    /**
     * @description role session expiration
     * @example 3600
     * @var int
     */
    private $durationSeconds = 3600;

    /**
     * @var string
     */
    private $externalId;

    /**
     * @var string
     */
    private $policy;

    /**
     * @var string
     */
    private $stsEndpoint;

    /**
     * @var int
     */
    private $connectTimeout = 5;

    /**
     * @var int
     */
    private $readTimeout = 5;

    /**
     * RamRoleArnCredentialsProvider constructor.
     *
     * @param array $params
     * @param array $options
     */
    public function __construct(array $params = [], array $options = [])
    {
        $this->filterOptions($options);
        $this->filterCredentials($params);
        $this->filterRoleArn($params);
        $this->filterRoleSessionName($params);
        $this->filterDurationSeconds($params);
        $this->filterPolicy($params);
        $this->filterExternalId($params);
        $this->filterSTSEndpoint($params);
    }

    private function filterRoleArn(array $params)
    {
        if (Helper::envNotEmpty(Config::ENV_PREFIX + 'ROLE_ARN')) {
            $this->roleArn = Helper::env(Config::ENV_PREFIX + 'ROLE_ARN');
        }

        if (isset($params['roleArn'])) {
            $this->roleArn = $params['roleArn'];
        }

        Filter::roleArn($this->roleArn);
    }

    private function filterRoleSessionName(array $params)
    {
        if (Helper::envNotEmpty(Config::ENV_PREFIX + 'ROLE_SESSION_NAME')) {
            $this->roleSessionName = Helper::env(Config::ENV_PREFIX + 'ROLE_SESSION_NAME');
        }

        if (isset($params['roleSessionName'])) {
            $this->roleSessionName = $params['roleSessionName'];
        }

        if (is_null($this->roleSessionName) || $this->roleSessionName === '') {
            $this->roleSessionName = 'phpSdkRoleSessionName';
        }
    }

    private function filterDurationSeconds(array $params)
    {
        if (isset($params['durationSeconds'])) {
            if (is_int($params['durationSeconds'])) {
                $this->durationSeconds = $params['durationSeconds'];
            }
        }
        if ($this->durationSeconds < 900) {
            throw new InvalidArgumentException('Role session expiration should be in the range of 900s - max session duration');
        }
    }

    private function filterPolicy(array $params)
    {
        if (isset($params['policy'])) {
            if (is_string($params['policy'])) {
                $this->policy = $params['policy'];
            }

            if (is_array($params['policy'])) {
                $this->policy = json_encode($params['policy']);
            }
        }
    }

    private function filterExternalId(array $params)
    {
        if (isset($params['externalId'])) {
            if (is_string($params['externalId'])) {
                $this->externalId = $params['externalId'];
            }
        }
    }

    private function filterSTSEndpoint(array $params)
    {
        $prefix = 'sts';
        if (Helper::envNotEmpty(Config::ENV_PREFIX . 'VPC_ENDPOINT_ENABLED') || (isset($params['enableVpc']) && $params['enableVpc'] === true)) {
            $prefix = 'sts-vpc';
        }
        if (Helper::envNotEmpty(Config::ENV_PREFIX . 'STS_REGION')) {
            $this->stsEndpoint = $prefix . '.' . Helper::env(Config::ENV_PREFIX . 'STS_REGION') . '.' . Config::ENDPOINT_SUFFIX;
        }

        if (isset($params['stsRegionId'])) {
            $this->stsEndpoint = $prefix . '.' . $params['stsRegionId'] . '.' . Config::ENDPOINT_SUFFIX;
        }

        if (isset($params['stsEndpoint'])) {
            $this->stsEndpoint = $params['stsEndpoint'];
        }

        if (is_null($this->stsEndpoint) || $this->stsEndpoint === '') {
            $this->stsEndpoint = Config::STS_DEFAULT_ENDPOINT;
        }
    }

    private function filterCredentials(array $params)
    {
        if (isset($params['credentialsProvider'])) {
            if (!($params['credentialsProvider'] instanceof CredentialsProvider)) {
                throw new InvalidArgumentException('Invalid credentialsProvider option for ram_role_arn');
            }
            $this->credentialsProvider = $params['credentialsProvider'];
        } else if (isset($params['accessKeyId']) && isset($params['accessKeySecret']) && isset($params['securityToken'])) {
            Filter::accessKey($params['accessKeyId'], $params['accessKeySecret']);
            Filter::securityToken($params['securityToken']);
            $this->credentialsProvider = new StaticSTSCredentialsProvider($params);
        } else if (isset($params['accessKeyId']) && isset($params['accessKeySecret'])) {
            Filter::accessKey($params['accessKeyId'], $params['accessKeySecret']);
            $this->credentialsProvider = new StaticAKCredentialsProvider($params);
        } else {
            throw new InvalidArgumentException('Missing required credentials option for ram_role_arn');
        }
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

    /**
     * Get credentials by request.
     *
     * @return RefreshResult
     * @throws RuntimeException
     * @throws GuzzleException
     */
    public function refreshCredentials()
    {
        $options = Request::commonOptions();
        $options['read_timeout'] = $this->readTimeout;
        $options['connect_timeout'] = $this->connectTimeout;

        $date = gmdate('Y-m-d\TH:i:s\Z');
        $options['headers']['x-acs-action'] = 'AssumeRole';
        $options['headers']['x-acs-action'] = '2015-04-01';
        $options['query']['accept'] = 'application/json';
        $options['headers']['x-acs-date'] = $date;
        
        $options['query']['RoleArn'] = $this->roleArn;
        $options['query']['RoleSessionName'] = $this->roleSessionName;
        $options['query']['DurationSeconds'] = (string) $this->durationSeconds;
        if (!is_null($this->policy) && $this->policy !== '') {
            $options['query']['Policy'] = $this->policy;
        }
        if (!is_null($this->externalId) && $this->externalId !== '') {
            $options['query']['ExternalId'] = $this->externalId;
        }

        $options['headers']['x-acs-signature-nonce'] = Request::uuid(json_encode($options['query']));

        $sessionCredentials = $this->credentialsProvider->getCredentials();
        if (!is_null($sessionCredentials->getSecurityToken())) {
            $options['headers']['x-acs-accesskey-id'] = $sessionCredentials->getAccessKeyId();
            $options['headers']['x-acs-security-token'] = $sessionCredentials->getSecurityToken();
        }
        
        $dateNew = substr($date, 0, 11);
        $dateNew = str_replace(
            "-",
            "",
            $dateNew
        );
        $region = $this->getRegion($request->productId, $config->endpoint, $config->regionId);
        $signingkey = $this->getSigningkey($signatureAlgorithm, $accessKeySecret, $request->productId, $region, $dateNew);
        $request->headers["Authorization"] = $this->getAuthorization($request->pathname, $request->method, $request->query, $request->headers, $signatureAlgorithm, $hashedRequestPayload, $accessKeyId, $signingkey, $request->productId, $region, $dateNew);

        $url = (new Uri())->withScheme('https')->withHost($this->stsEndpoint);

        $result = Request::createClient()->request('GET', $url, $options);

        if ($result->getStatusCode() !== 200) {
            throw new RuntimeException('Error refreshing credentials from RamRoleArn, statusCode: ' . $result->getStatusCode() . ', result: ' . (string) $result);
        }

        $json = $result->toArray();
        $credentials = $json['Credentials'];

        if (!isset($credentials['AccessKeyId']) || !isset($credentials['AccessKeySecret']) || !isset($credentials['SecurityToken'])) {
            throw new RuntimeException('Error retrieving credentials from RamRoleArn result:' . $result->toJson());
        }

        return new RefreshResult(new Credentials([
            'accessKeyId' => $credentials['AccessKeyId'],
            'accessKeySecret' => $credentials['AccessKeySecret'],
            'securityToken' => $credentials['SecurityToken'],
            'expiration' => \strtotime($credentials['Expiration']),
            'providerName' => $this->getProviderName(),
        ]), $this->getStaleTime(strtotime($credentials['Expiration'])));
    }

    public function key()
    {
        $credentials = $this->credentialsProvider->getCredentials();
        return 'ram_role_arn#credential#' . $credentials->getAccessKeyId() . '#roleArn#' . $this->roleArn . '#roleSessionName#' . $this->roleSessionName;
    }

    public function getProviderName()
    {
        return 'ram_role_arn/' . $this->credentialsProvider->getProviderName();
    }

    /**
     * @return string
     */
    public function getRoleArn()
    {
        return $this->roleArn;
    }

    /**
     * @return string
     */
    public function getRoleSessionName()
    {
        return $this->roleSessionName;
    }

    /**
     * @return string
     */
    public function getPolicy()
    {
        return $this->policy;
    }

    /**
     * @deprecated
     * @return string
     */
    public function getOriginalAccessKeyId()
    {
        return $this->credentialsProvider->getCredentials()->getAccessKeyId();
    }

    /**
     * @deprecated
     * @return string
     */
    public function getOriginalAccessKeySecret()
    {
        return $this->credentialsProvider->getCredentials()->getAccessKeySecret();
    }
}
