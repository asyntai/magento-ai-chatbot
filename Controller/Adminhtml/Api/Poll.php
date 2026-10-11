<?php
/**
 * Asyntai Chatbot API Poll Controller
 *
 * Asks Asyntai whether the merchant has finished signing in.
 *
 * The browser used to do this by loading a script from asyntai.com and
 * letting it call back. That makes the admin page execute remote code. This
 * controller reads the same short-lived, public answer over plain JSON from
 * PHP instead, so nothing outside the store ever runs in the admin.
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
use Magento\Framework\HTTP\Client\Curl;

class Poll extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Asyntai_Chatbot::config';

    /**
     * Plain JSON twin of connect-status.js.
     */
    const STATUS_URL = 'https://asyntai.com/api/v1/wp-search/connect-status/';

    /**
     * One poll must not hold a PHP worker for long: the browser asks again.
     */
    const TIMEOUT = 10;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Curl $curl
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Curl $curl
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->curl = $curl;
    }

    /**
     * Poll action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        $state = trim((string)$this->getRequest()->getParam('state'));
        if ($state === '') {
            return $result->setData(['ready' => false]);
        }

        try {
            $this->curl->setTimeout(self::TIMEOUT);
            $this->curl->get(self::STATUS_URL . '?state=' . rawurlencode($state));
            if ((int)$this->curl->getStatus() !== 200) {
                return $result->setData(['ready' => false]);
            }
            $body = json_decode((string)$this->curl->getBody(), true);
        } catch (\Exception $e) {
            return $result->setData(['ready' => false]);
        }

        if (!is_array($body) || empty($body['ready']) || empty($body['data'])) {
            return $result->setData(['ready' => false]);
        }

        $data = $body['data'];
        if (empty($data['site_id'])) {
            return $result->setData(['ready' => false]);
        }

        // Only the three fields the settings page saves are passed on. The
        // answer comes from outside the store, so nothing else is trusted.
        return $result->setData([
            'ready' => true,
            'data' => [
                'site_id' => (string)$data['site_id'],
                'script_url' => isset($data['script_url']) ? (string)$data['script_url'] : '',
                'account_email' => isset($data['account_email']) ? (string)$data['account_email'] : '',
            ],
        ]);
    }
}
