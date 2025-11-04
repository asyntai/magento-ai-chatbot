<?php
/**
 * Asyntai Chatbot API Save Controller
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
use Asyntai\Chatbot\Model\Config;

class Save extends Action implements CsrfAwareActionInterface
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
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Config $config
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Config $config
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->config = $config;
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        try {
            // Get raw input stream
            $content = file_get_contents('php://input');

            // Magento's AJAX appends form_key to JSON body, breaking it
            // Extract only the JSON part (everything before '&' if present)
            if (strpos($content, '}&') !== false) {
                $content = substr($content, 0, strpos($content, '}&') + 1);
            }

            $data = json_decode($content, true);

            // If JSON decode failed, try Magento's getContent method
            if (!is_array($data)) {
                $content = $this->getRequest()->getContent();
                if (strpos($content, '}&') !== false) {
                    $content = substr($content, 0, strpos($content, '}&') + 1);
                }
                $data = json_decode($content, true);
            }

            // If still failed, try to get from POST parameters
            if (!is_array($data)) {
                $data = $this->getRequest()->getPostValue();
            }

            // If still not array, return error
            if (!is_array($data)) {
                return $result->setHttpResponseCode(400)->setData([
                    'success' => false,
                    'error' => 'Invalid JSON'
                ]);
            }

            $siteId = isset($data['site_id']) ? trim((string)$data['site_id']) : '';

            if ($siteId === '') {
                return $result->setHttpResponseCode(400)->setData([
                    'success' => false,
                    'error' => 'missing site_id'
                ]);
            }

            $this->config->saveSettings($data);

            return $result->setData([
                'success' => true,
                'saved' => [
                    'site_id' => $this->config->getSiteId(),
                    'script_url' => $this->config->getScriptUrl(),
                    'account_email' => $this->config->getAccountEmail(),
                ]
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
