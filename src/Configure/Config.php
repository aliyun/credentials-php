<?php

namespace AlibabaCloud\Configure;

class Config
{
    const ENV_PREFIX = "ALIBABA_CLOUD_";
    const KEY = "AlibabaCloud";
    const STS_DEFAULT_ENDPOINT = "sts.aliyuncs.com";
    const ENDPOINT_SUFFIX = "aliyuncs.com";
    const CREDENTIAL_FILE_PATH = ".alibabacloud";
    const CLI_CONFIG_DIR = ".aliyun";
    const ECS_METADATA_HOST = "100.100.100.200";
    const ECS_METADATA_HEADER_PREFIX = "X-aliyun-";
    const SIGN_PREFIX = "aliyun_v4"; // cloud_v4
    const SIGNATURE_TYPE_PREFIX = "ACS4-";// CLOUD4-
}
