<?php
/**
 * Asyntai Chatbot API Reset Controller
 *
 * @category  Asyntai
 * @package   Asyntai_Chatbot
 * @author    Asyntai <hello@asyntai.com>
 * @copyright Copyright (c) Asyntai
 * @license   MIT License
 */

namespace Asyntai\Chatbot\Controller\Adminhtml\Api;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Asyntai\Chatbot\Model\CatalogAccess;
use Asyntai\Chatbot\Model\Config;

class Reset extends Action implements CsrfAwareActionInterface
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Asyntai_Chatbot::config';

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CatalogAccess
     */
    protected $catalogAccess;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Config $config
     * @param CatalogAccess $catalogAccess
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Config $config,
        CatalogAccess $catalogAccess
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->config = $config;
        $this->catalogAccess = $catalogAccess;
    }

    /**
     * Reset action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        try {
            // Take the read-only integration back as well, so a merchant who
            // disconnects is left with no live token on their store.
            $this->catalogAccess->revoke();
            $this->config->resetSettings();

            return $result->setData([
                'success' => true
            ]);
        } catch (\Exception $e) {
            return $result->setHttpResponseCode(500)->setData([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
