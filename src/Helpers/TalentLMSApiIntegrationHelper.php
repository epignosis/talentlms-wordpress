<?php

namespace TalentlmsIntegration\Helpers;

use Exception;
use TalentLMS;
use TalentLMS_ApiError;

trait TalentLMSApiIntegrationHelper
{

    /**
     * @throws TalentLMS_ApiError
     */
    public function enableTalentLMSLib(): void
    {
        if (empty(get_option('tlms-domain')) || empty(get_option('tlms-apikey'))) {
            throw new TalentLMS_ApiError(__('You need to specify a TalentLMS domain and a TalentLMS API key.', 'talentlms'));
        }

        try {
            TalentLMS::setDomain(get_option('tlms-domain'));
            TalentLMS::setApiKey(get_option('tlms-apikey'));
        } catch (Exception $e) {
            if ($e instanceof TalentLMS_ApiError) {
                tlms_logError($e->getMessage());
            }
        }
    }
}
