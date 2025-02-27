<?php

namespace AlibabaCloud\Credentials\Providers;

use AlibabaCloud\Credentials\Utils\Helper;
use AlibabaCloud\Credentials\Utils\Filter;
use AlibabaCloud\Configure\Config;

class StaticSTSCredentialsProvider implements CredentialsProvider
{

    /**
     * @var string
     */
    private $accessKeyId;

    /**
     * @var string
     */
    private $accessKeySecret;

    /**
     * @var string
     */
    private $securityToken;

    /**
     * StaticSTSCredentialsProvider constructor.
     *
     * @param array $params
     */
    public function __construct(array $params = [])
    {
        $this->filterSTS($params);
    }

    private function filterSTS(array $params)
    {
        if (Helper::envNotEmpty(Config::ENV_PREFIX . 'ACCESS_KEY_ID')) {
            $this->accessKeyId = Helper::env(Config::ENV_PREFIX . 'ACCESS_KEY_ID');
        }

        if (Helper::envNotEmpty(Config::ENV_PREFIX . 'ACCESS_KEY_SECRET')) {
            $this->accessKeySecret = Helper::env(Config::ENV_PREFIX . 'ACCESS_KEY_SECRET');
        }

        if (Helper::envNotEmpty(Config::ENV_PREFIX . 'SECURITY_TOKEN')) {
            $this->securityToken = Helper::env(Config::ENV_PREFIX . 'SECURITY_TOKEN');
        }

        if (isset($params['accessKeyId'])) {
            $this->accessKeyId = $params['accessKeyId'];
        }
        if (isset($params['accessKeySecret'])) {
            $this->accessKeySecret = $params['accessKeySecret'];
        }
        if (isset($params['securityToken'])) {
            $this->securityToken = $params['securityToken'];
        }

        Filter::accessKey($this->accessKeyId, $this->accessKeySecret);
        Filter::securityToken($this->securityToken);
    }

    /**
     * Get credential.
     *
     * @return Credentials
     */
    public function getCredentials()
    {
        return new Credentials([
            'accessKeyId' => $this->accessKeyId,
            'accessKeySecret' => $this->accessKeySecret,
            'securityToken' => $this->securityToken,
            'providerName' => $this->getProviderName(),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getProviderName()
    {
        return "static_sts";
    }
}