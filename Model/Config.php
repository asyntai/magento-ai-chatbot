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
    const XML_PATH_CATALOG_STATUS = 'asyntai_chatbot/settings/catalog_status';

    /**
     * Where the widget script lives now. It moved to its own subdomain, so a
     * store that connected before this release still holds the old address in
     * its configuration. getScriptUrl() rewrites that address as it is read,
     * which moves every existing store on update without anybody reconnecting.
     */
    const WIDGET_ORIGIN = 'https://widget.asyntai.com';
    const SCRIPT_URL = self::WIDGET_ORIGIN . '/static/js/chat-widget.js';

    /**
     * The addresses the widget script used to be served from.
     */
    const LEGACY_SCRIPT_URLS = [
        'https://asyntai.com/static/js/chat-widget.js',
        'https://www.asyntai.com/static/js/chat-widget.js',
        'http://asyntai.com/static/js/chat-widget.js',
        'http://www.asyntai.com/static/js/chat-widget.js',
    ];

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
        if (!$url) {
            return self::SCRIPT_URL;
        }
        // Only the exact old address is rewritten. A merchant who set their
        // own address on purpose keeps it.
        if (in_array($url, self::LEGACY_SCRIPT_URLS, true)) {
            return self::SCRIPT_URL;
        }
        return $url;
    }

    /**
     * Get catalogue status: '' (never tried), 'connected' or 'unavailable'
     *
     * @return string
     */
    public function getCatalogStatus()
    {
        return trim((string)$this->scopeConfig->getValue(self::XML_PATH_CATALOG_STATUS));
    }

    /**
     * Save catalogue status
     *
     * @param string $status
     * @return void
     */
    public function setCatalogStatus($status)
    {
        $this->configWriter->save(self::XML_PATH_CATALOG_STATUS, (string)$status);
        $this->flushCache();
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
        $this->configWriter->save(self::XML_PATH_SCRIPT_URL, self::SCRIPT_URL);
        $this->configWriter->save(self::XML_PATH_ACCOUNT_EMAIL, '');
        $this->configWriter->save(self::XML_PATH_CATALOG_STATUS, '');
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
