<?php

namespace AlibabaCloud\Credentials;

use AlibabaCloud\Credentials\Credential\CredentialModel;

/**
 * @internal This class is intended for internal use within the package. 
 * Interface CredentialsInterface
 *
 * @codeCoverageIgnore
 */
interface CredentialsInterface
{

    /**
     * @return CredentialModel
     */
    public function getCredential();
}
