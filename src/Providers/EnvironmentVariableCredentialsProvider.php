<?php

namespace AlibabaCloud\Credentials\Providers;

use AlibabaCloud\Credentials\Utils\Helper;
use InvalidArgumentException;
use AlibabaCloud\Configure\Config;

class EnvironmentVariableCredentialsProvider implements CredentialsProvider
{
    /**
     * EnvironmentVariableCredentialsProvider constructor.
     */
    public function __construct() {}

    /**
     * Get credential.
     *
     * @return Credentials
     * @throws InvalidArgumentException
     */
    public function getCredentials()
    {
        if (Helper::envNotEmpty(Config::ENV_PREFIX . 'ACCESS_KEY_ID')) {
            $accessKeyId = Helper::env(Config::ENV_PREFIX . 'ACCESS_KEY_ID');
        } else {
            throw new InvalidArgumentException('Access key ID must be specified via environment variable (' . Config::ENV_PREFIX . 'ACCESS_KEY_ID)');
        }

        if (Helper::envNotEmpty(Config::ENV_PREFIX . 'ACCESS_KEY_SECRET')) {
            $accessKeySecret = Helper::env(Config::ENV_PREFIX . 'ACCESS_KEY_SECRET');
        } else {
            throw new InvalidArgumentException('Access key Secret must be specified via environment variable (' . Config::ENV_PREFIX . 'ACCESS_KEY_SECRET)');
        }

        if (Helper::envNotEmpty(Config::ENV_PREFIX . 'SECURITY_TOKEN')) {
            $securityToken = Helper::env(Config::ENV_PREFIX . 'SECURITY_TOKEN');
            return new Credentials([
                'accessKeyId' => $accessKeyId,
                'accessKeySecret' => $accessKeySecret,
                'securityToken' => $securityToken,
                'providerName' => $this->getProviderName(),
            ]);
        }

        return new Credentials([
            'accessKeyId' => $accessKeyId,
            'accessKeySecret' => $accessKeySecret,
            'providerName' => $this->getProviderName(),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getProviderName()
    {
        return "env";
    }
}
