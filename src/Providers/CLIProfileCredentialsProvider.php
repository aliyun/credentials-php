<?php

namespace AlibabaCloud\Credentials\Providers;

use AlibabaCloud\Credentials\Utils\Helper;
use RuntimeException;

/**
 * @internal This class is intended for internal use within the package. 
 * Class CLIProfileCredentialsProvider
 *
 * @package AlibabaCloud\Credentials\Providers
 */
class CLIProfileCredentialsProvider implements CredentialsProvider
{

    /**
     * @var string
     */
    private $profileName;

    /**
     * @var CredentialsProvider
     */
    private $credentialsProvider;


    /**
     * CLIProfileCredentialsProvider constructor.
     *
     * @param array $params
     */
    public function __construct(array $params = [])
    {
        $this->filterProfileName($params);
    }

    private function filterProfileName(array $params)
    {
        if (Helper::envNotEmpty('ALIBABA_CLOUD_PROFILE')) {
            $this->profileName = Helper::env('ALIBABA_CLOUD_PROFILE');
        }

        if (isset($params['profileName'])) {
            $this->profileName = $params['profileName'];
        }
    }

    /**
     * @return bool
     */
    private function shouldReloadCredentialsProvider()
    {
        if (is_null($this->credentialsProvider)) {
            return true;
        }

        return false;
    }

    /**
     * @return CredentialsProvider
     */
    private function reloadCredentialsProvider($profileFile, $profileName)
    {
        if (!Helper::inOpenBasedir($profileFile)) {
            throw new RuntimeException('Unable to open credentials file: ' . $profileFile);
        }

        if (!\is_readable($profileFile) || !\is_file($profileFile)) {
            throw new RuntimeException('Credentials file is not readable: ' . $profileFile);
        }

        $jsonContent = \file_get_contents($profileFile);
        $fileArray = json_decode($jsonContent, true);

        if (\is_array($fileArray) && !empty($fileArray)) {
            if (is_null($profileName) || $profileName === '') {
                $profileName = $fileArray['current'];
            }
            if (isset($fileArray['profiles'])) {
                foreach ($fileArray['profiles'] as $profile) {
                    if ($profile['name'] === $profileName) {
                        switch ($profile['mode']) {
                            case 'AK':
                                return new StaticAKCredentialsProvider([
                                    'accessKeyId' => $profile['access_key_id'],
                                    'accessKeySecret' => $profile['access_key_secret'],
                                ]);
                            case 'RamRoleArn':
                                $innerProvider = new StaticAKCredentialsProvider([
                                    'accessKeyId' => $profile['access_key_id'],
                                    'accessKeySecret' => $profile['access_key_secret'],
                                ]);
                                return new RamRoleArnCredentialsProvider([
                                    'credentialsProvider' => $innerProvider,
                                    'roleArn' => $profile['ram_role_arn'],
                                    'roleSessionName' => $profile['ram_session_name'],
                                    'durationSeconds' => $profile['expired_seconds'],
                                    'stsRegionId' => $profile['sts_region'],
                                ]);
                            case 'EcsRamRole':
                                return new EcsRamRoleCredentialsProvider([
                                    'roleName' => $profile['ram_role_name'],
                                ]);
                            case 'OIDC':
                                return new OIDCRoleArnCredentialsProvider([
                                    'roleArn' => $profile['ram_role_arn'],
                                    'oidcProviderArn' => $profile['oidc_provider_arn'],
                                    'oidcTokenFilePath' => $profile['oidc_token_file'],
                                    'roleSessionName' => $profile['ram_session_name'],
                                    'durationSeconds' => $profile['expired_seconds'],
                                    'stsRegionId' => $profile['sts_region'],
                                ]);
                            case 'ChainableRamRoleArn':
                                $previousProvider = $this->reloadCredentialsProvider($profileFile, $profile['source_profile']);
                                return new RamRoleArnCredentialsProvider([
                                    'credentialsProvider' => $previousProvider,
                                    'roleArn' => $profile['ram_role_arn'],
                                    'roleSessionName' => $profile['ram_session_name'],
                                    'durationSeconds' => $profile['expired_seconds'],
                                    'stsRegionId' => $profile['sts_region'],
                                ]);
                            default:
                                throw new RuntimeException('Unsupported credential mode form CLI credentials file: ' . $profile['mode']);
                        }
                    }
                }
            }
        }
        throw new RuntimeException('Failed to get credential form CLI credentials file: ' . $profileFile);
    }
    /**
     * Get credential.
     *
     * @return Credentials
     * @throws RuntimeException
     */
    public function getCredentials()
    {
        if (Helper::envNotEmpty('ALIBABA_CLOUD_CLI_PROFILE_DISABLED') && Helper::env('ALIBABA_CLOUD_PROFILE') === true) {
            throw new RuntimeException('CLI credentials file is disabled');
        }
        $cliProfileFile = self::getDefaultFile();
        if ($this->shouldReloadCredentialsProvider()) {
            $this->credentialsProvider = $this->reloadCredentialsProvider($cliProfileFile, $this->profileName);
        }

        $credentials = $this->credentialsProvider->getCredentials();
        return new Credentials([
            'accessKeyId' => $credentials->getAccessKeyId(),
            'accessKeySecret' => $credentials->getAccessKeySecret(),
            'securityToken' => $credentials->getSecurityToken(),
            'providerName' => $this->getProviderName() . '/' . $this->credentialsProvider->getProviderName(),
        ]);

    }

    /**
     * Get the default credential file.
     *
     * @return string
     */
    private function getDefaultFile()
    {
        return Helper::getHomeDirectory() .
            DIRECTORY_SEPARATOR .
            '.aliyun' .
            DIRECTORY_SEPARATOR .
            'config.json';
    }

    /**
     * @return string
     */
    public function getProviderName()
    {
        return 'cli_profile';
    }
}
