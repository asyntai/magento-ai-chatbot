<?php
/**
 * Asyntai Chatbot Widget Block
 *
 * @category  Asyntai
 * @package   Asyntai_Chatbot
 * @author    Asyntai <hello@asyntai.com>
 * @copyright Copyright (c) Asyntai
 * @license   MIT License
 */

namespace Asyntai\Chatbot\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Asyntai\Chatbot\Model\Config;

class Widget extends Template
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Context $context
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $config,
        array $data = []
    ) {
        $this->config = $config;
        parent::__construct($context, $data);
    }

    /**
     * Get site ID
     *
     * @return string
     */
    public function getSiteId()
    {
        return $this->config->getSiteId();
    }

    /**
     * Get script URL
     *
     * @return string
     */
    public function getScriptUrl()
    {
        return $this->config->getScriptUrl();
    }

    /**
     * Check if widget should be displayed
     *
     * @return bool
     */
    public function shouldDisplay()
    {
        $siteId = $this->getSiteId();
        return !empty($siteId);
    }
}
