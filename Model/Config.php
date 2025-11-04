<?php
/**
 * Asyntai Chatbot Config Model
 *
 * @category  Asyntai
 * @package   Asyntai_Chatbot
 * @author    Asyntai <hello@asyntai.com>
 * @copyright Copyright (c) Asyntai
 * @license   MIT License
 */

namespace Asyntai\Chatbot\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Cache\Frontend\Pool;

class Config
{
    const XML_PATH_SITE_ID = 'asyntai_chatbot/settings/site_id';
    const XML_PATH_SCRIPT_URL = 'asyntai_chatbot/settings/script_url';
    const XML_PATH_ACCOUNT_EMAIL = 'asyntai_chatbot/settings/account_email';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @var Pool
     */
    protected $cacheFrontendPool;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $configWriter
     * @param TypeListInterface $cacheTypeList
     * @param Pool $cacheFrontendPool
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList,
        Pool $cacheFrontendPool
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
    }

    /**
     * Get site ID
     *
     * @return string
     */
    public function getSiteId()
    {
        return trim((string)$this->scopeConfig->getValue(self::XML_PATH_SITE_ID));
    }

    /**
     * Get script URL
     *
     * @return string
     */
    public function getScriptUrl()
    {
        $url = trim((string)$this->scopeConfig->getValue(self::XML_PATH_SCRIPT_URL));
        return $url ?: 'https://asyntai.com/static/js/chat-widget.js';
    }

    /**
     * Get account email
     *
     * @return string
     */
    public function getAccountEmail()
    {
        return trim((string)$this->scopeConfig->getValue(self::XML_PATH_ACCOUNT_EMAIL));
    }

    /**
     * Save settings
     *
     * @param array $data
     * @return void
     */
    public function saveSettings(array $data)
    {
        if (isset($data['site_id'])) {
            $this->configWriter->save(self::XML_PATH_SITE_ID, trim($data['site_id']));
        }
        if (isset($data['script_url'])) {
            $this->configWriter->save(self::XML_PATH_SCRIPT_URL, trim($data['script_url']));
        }
        if (isset($data['account_email'])) {
            $this->configWriter->save(self::XML_PATH_ACCOUNT_EMAIL, trim($data['account_email']));
        }
        $this->flushCache();
    }

    /**
     * Reset settings
     *
     * @return void
     */
    public function resetSettings()
    {
        $this->configWriter->save(self::XML_PATH_SITE_ID, '');
        $this->configWriter->save(self::XML_PATH_SCRIPT_URL, 'https://asyntai.com/static/js/chat-widget.js');
        $this->configWriter->save(self::XML_PATH_ACCOUNT_EMAIL, '');
        $this->flushCache();
    }

    /**
     * Flush config cache
     *
     * @return void
     */
    protected function flushCache()
    {
        $this->cacheTypeList->cleanType('config');
        $this->cacheTypeList->cleanType('full_page');
        $this->cacheTypeList->cleanType('block_html');
        $this->cacheTypeList->cleanType('layout');
    }
}
