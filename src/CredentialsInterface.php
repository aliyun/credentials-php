<?php

namespace AlibabaCloud\Credentials;

use AlibabaCloud\Credentials\Credential\CredentialModel;

interface CredentialsInterface
{
    /**
     * @deprecated
     * @return string
     */
    public function __toString();

    /**
     * @return CredentialModel
     */
    public function getCredential();
}
