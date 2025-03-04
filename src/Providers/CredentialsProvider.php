<?php

namespace AlibabaCloud\Credentials\Providers;

interface CredentialsProvider
{

    /**
     * @return Credentials
     */
    public function getCredentials();

    /**
     * @return string
     */
    public function getProviderName();
}
