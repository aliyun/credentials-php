<?php

namespace AlibabaCloud\Credentials;

use AlibabaCloud\Credentials\Credential\CredentialModel;
use AlibabaCloud\Credentials\Providers\CredentialsProvider;

class CredentialsProviderWrap implements CredentialsInterface
{
    /**
     * @var string
     */
    private $typeName;

    /**
     * @var CredentialsProvider
     */
    private $credentialsProvider;

    /**
     * CLIProfileCredentialsProvider constructor.
     *
     * @param string $typeName
     * @param CredentialsProvider $credentialsProvider
     */
    public function __construct($typeName, $credentialsProvider)
    {
        $this->typeName = $typeName;
        $this->credentialsProvider = $credentialsProvider;
    }

    /**
     * @inheritDoc
     */
    public function getCredential()
    {
        $credentials = $this->credentialsProvider->getCredentials();
        return new CredentialModel([
            'accessKeyId' => $credentials->getAccessKeyId(),
            'accessKeySecret' => $credentials->getAccessKeySecret(),
            'securityToken' => $credentials->getSecurityToken(),
            'type' => $this->typeName,
            'providerName' => $credentials->getProviderName(),
        ]);
    }

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return $this->credentialsProvider->$name($arguments);
    }

    public function __toString()
    {
        return "credentialsProviderWrap#$this->typeName";
    }
}