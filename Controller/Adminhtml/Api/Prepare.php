<?php
/**
 * Asyntai Chatbot API Prepare Controller
 *
 * Runs before the connect window opens. It makes a one-time state token, and,
 * when the store allows it, a read-only Magento integration whose access
 * token is parked at Asyntai under that same state. The merchant then signs
 * in inside the window and both halves are claimed together.
 *
 * The catalogue is always best effort. A store that cannot hand over a token
 * still connects the chatbot, because the assistant answers from the shop's
 * pages either way.
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
use Magento\Framework\Math\Random;
use Magento\Store\Model\StoreManagerInterface;
use Asyntai\Chatbot\Model\CatalogAccess;
use Asyntai\Chatbot\Model\Config;

class Prepare extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Asyntai_Chatbot::config';

    /**
     * Where a freshly made token waits for the merchant to finish signing in.
     */
    const STAGE_URL = 'https://asyntai.com/api/v1/wp-search/stage/';

    /**
     * Staging must never hold up the connect window. Ten seconds is far more
     * than the endpoint needs and short enough that nobody waits on it.
     */
    const STAGE_TIMEOUT = 10;

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
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var Random
     */
    protected $random;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Config $config
     * @param CatalogAccess $catalogAccess
     * @param StoreManagerInterface $storeManager
     * @param Curl $curl
     * @param Random $random
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Config $config,
        CatalogAccess $catalogAccess,
        StoreManagerInterface $storeManager,
        Curl $curl,
        Random $random
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->config = $config;
        $this->catalogAccess = $catalogAccess;
        $this->storeManager = $storeManager;
        $this->curl = $curl;
        $this->random = $random;
    }

    /**
     * Prepare action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        $state = 'mag_' . $this->random->getRandomString(24);
        $catalog = 'skipped';
        $message = '';

        try {
            $includeOrders = (bool)$this->getRequest()->getParam('include_orders');

            if (!$this->catalogAccess->isBearerEnabled()) {
                $catalog = 'unavailable';
                $message = $this->catalogAccess->getBearerHint();
            } else {
                $token = $this->catalogAccess->createToken(
                    $this->getAdminEmail(),
                    $includeOrders
                );
                if (!$token) {
                    $catalog = 'unavailable';
                    $message = (string)__('The catalogue could not be connected. The chatbot still works.');
                } elseif ($this->stage($state, $token)) {
                    $catalog = 'staged';
                } else {
                    // Nothing reached Asyntai, so the token would sit unused
                    // on the store. Take it back rather than leave it live.
                    $this->catalogAccess->revoke();
                    $catalog = 'unavailable';
                    $message = (string)__('Asyntai could not be reached for the catalogue. The chatbot still works.');
                }
            }
        } catch (\Exception $e) {
            $catalog = 'unavailable';
            $message = (string)__('The catalogue could not be connected. The chatbot still works.');
        }

        $this->config->setCatalogStatus($catalog === 'staged' ? 'connected' : 'unavailable');

        return $result->setData([
            'success' => true,
            'state' => $state,
            'catalog' => $catalog,
            'message' => $message,
        ]);
    }

    /**
     * Park the token at Asyntai under this state.
     *
     * @param string $state
     * @param string $token
     * @return bool
     */
    protected function stage($state, $token)
    {
        $payload = [
            'state' => $state,
            'platform' => 'magento',
            'product' => 'chat',
            'site_url' => $this->getStoreUrl(),
            'access_token' => $token,
        ];

        try {
            $this->curl->setTimeout(self::STAGE_TIMEOUT);
            $this->curl->addHeader('Content-Type', 'application/json');
            $this->curl->post(self::STAGE_URL, json_encode($payload));
            if ((int)$this->curl->getStatus() !== 200) {
                return false;
            }
            $body = json_decode((string)$this->curl->getBody(), true);
            return is_array($body) && !empty($body['ok']);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * The store's own address, without the admin path.
     *
     * @return string
     */
    protected function getStoreUrl()
    {
        try {
            return rtrim((string)$this->storeManager->getStore()->getBaseUrl(), '/');
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * The signed-in admin's address, used as the integration's contact.
     *
     * @return string
     */
    protected function getAdminEmail()
    {
        try {
            $user = $this->_auth->getUser();
            return $user ? (string)$user->getEmail() : '';
        } catch (\Exception $e) {
            return '';
        }
    }
}
