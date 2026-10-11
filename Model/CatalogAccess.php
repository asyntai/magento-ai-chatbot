<?php
/**
 * Asyntai Chatbot catalogue access
 *
 * Makes, and takes back, the read-only Magento integration that lets the
 * assistant quote live prices and stock instead of the copy it read from the
 * shop's pages weeks ago.
 *
 * Three Magento facts shape this class:
 *
 *  - Magento has no "make me a token" call. An integration is created, its
 *    ACL resources are granted, and only then does an access token exist.
 *  - Since 2.4.4 that token is refused as a Bearer token until the store
 *    switches on "Allow OAuth Access Tokens to be used as standalone Bearer
 *    tokens". This class reports that state. It never changes it: that is the
 *    merchant's security setting, not ours.
 *  - The integration carries our own name, so removing it can never touch an
 *    integration somebody else made.
 *
 * @category  Asyntai
 * @package   Asyntai_Chatbot
 * @author    Asyntai <hello@asyntai.com>
 * @copyright Copyright (c) Asyntai
 * @license   MIT License
 */

namespace Asyntai\Chatbot\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\IntegrationException;
use Magento\Integration\Api\AuthorizationServiceInterface;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\Integration\Api\OauthServiceInterface;
use Magento\Integration\Model\Integration as IntegrationModel;

class CatalogAccess
{
    /**
     * The integration's name in System > Extensions > Integrations. It is the
     * only handle we have on our own row, so it must never change between
     * releases: a renamed integration would be orphaned on the store.
     */
    const INTEGRATION_NAME = 'Asyntai AI Chatbot';

    /**
     * Magento's own switch for using an integration token as a Bearer token.
     */
    const XML_PATH_BEARER = 'oauth/consumer/enable_integration_as_bearer';

    /**
     * Read access to the catalogue, its stock and the shop's CMS pages. This
     * is what the assistant answers from.
     */
    const CATALOG_RESOURCES = [
        'Magento_Catalog::products',
        'Magento_Catalog::catalog_inventory',
        'Magento_InventoryApi::source',
        'Magento_Cms::page',
        'Magento_Backend::store',
    ];

    /**
     * Read access to orders, so the assistant can answer "where is my order".
     * Granted only when the merchant ticks the box, because an order carries
     * a customer's name and address and the catalogue does not.
     */
    const ORDER_RESOURCES = [
        'Magento_Sales::actions_view',
    ];

    /**
     * @var IntegrationServiceInterface
     */
    protected $integrationService;

    /**
     * @var OauthServiceInterface
     */
    protected $oauthService;

    /**
     * @var AuthorizationServiceInterface
     */
    protected $authorizationService;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param IntegrationServiceInterface $integrationService
     * @param OauthServiceInterface $oauthService
     * @param AuthorizationServiceInterface $authorizationService
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        IntegrationServiceInterface $integrationService,
        OauthServiceInterface $oauthService,
        AuthorizationServiceInterface $authorizationService,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->integrationService = $integrationService;
        $this->oauthService = $oauthService;
        $this->authorizationService = $authorizationService;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Is Magento willing to accept an integration token as a Bearer token?
     *
     * @return bool
     */
    public function isBearerEnabled()
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_BEARER);
    }

    /**
     * What to tell a merchant whose store has that switch off.
     *
     * @return string
     */
    public function getBearerHint()
    {
        return (string)__(
            'To let the assistant read live prices and stock, switch on '
            . 'Stores > Configuration > Services > OAuth > Consumer Settings > '
            . '"Allow OAuth Access Tokens to be used as standalone Bearer tokens", '
            . 'then connect again. The chatbot itself works either way.'
        );
    }

    /**
     * Our integration row, or null when there is none.
     *
     * @return IntegrationModel|null
     */
    public function findOurs()
    {
        try {
            $integration = $this->integrationService->findByName(self::INTEGRATION_NAME);
        } catch (\Exception $e) {
            return null;
        }
        return ($integration && $integration->getId()) ? $integration : null;
    }

    /**
     * Create the integration if it is missing, grant it read access and hand
     * back its access token.
     *
     * @param string $email Contact address shown on the integration row
     * @param bool $includeOrders Also grant order read access
     * @return string The access token, or '' when one could not be made
     */
    public function createToken($email, $includeOrders = false)
    {
        $resources = self::CATALOG_RESOURCES;
        if ($includeOrders) {
            $resources = array_merge($resources, self::ORDER_RESOURCES);
        }

        try {
            $integration = $this->findOurs();
            if (!$integration) {
                $integration = $this->integrationService->create([
                    'name' => self::INTEGRATION_NAME,
                    'email' => $email ?: 'hello@asyntai.com',
                    'endpoint' => '',
                    'identity_link_url' => '',
                    'status' => IntegrationModel::STATUS_INACTIVE,
                    'setup_type' => IntegrationModel::TYPE_MANUAL,
                ]);
            }

            $this->authorizationService->grantPermissions($integration->getId(), $resources);

            $consumerId = $integration->getConsumerId();
            if (!$consumerId) {
                return '';
            }

            // createAccessToken() answers false when a token already exists.
            // Passing true clears the old one first, so reconnecting always
            // hands over a token the store will actually honour.
            $this->oauthService->createAccessToken($consumerId, true);
            $token = $this->oauthService->getAccessToken($consumerId);
            if (!$token) {
                return '';
            }

            $integration->setStatus(IntegrationModel::STATUS_ACTIVE)->save();

            return (string)$token->getToken();
        } catch (IntegrationException $e) {
            return '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Remove our integration, which revokes its token.
     *
     * Only ever touches the row named after this extension, so an integration
     * the merchant made by hand is left alone.
     *
     * @return bool
     */
    public function revoke()
    {
        try {
            $integration = $this->findOurs();
            if (!$integration) {
                return false;
            }
            $consumerId = $integration->getConsumerId();
            if ($consumerId) {
                $this->oauthService->deleteIntegrationToken($consumerId);
            }
            $this->integrationService->delete($integration->getId());
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
