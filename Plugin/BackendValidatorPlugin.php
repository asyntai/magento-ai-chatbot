<?php
/**
 * Asyntai Chatbot Backend Validator Plugin
 *
 * @category  Asyntai
 * @package   Asyntai_Chatbot
 * @author    Asyntai <hello@asyntai.com>
 * @copyright Copyright (c) Asyntai
 * @license   MIT License
 */

namespace Asyntai\Chatbot\Plugin;

use Magento\Backend\App\Request\BackendValidator;
use Magento\Framework\App\RequestInterface;

class BackendValidatorPlugin
{
    /**
     * Bypass form key validation for Asyntai API endpoints
     *
     * @param BackendValidator $subject
     * @param callable $proceed
     * @param RequestInterface $request
     * @param \Magento\Framework\App\ActionInterface $action
     * @return void
     */
    public function aroundValidate(
        BackendValidator $subject,
        callable $proceed,
        RequestInterface $request,
        \Magento\Framework\App\ActionInterface $action
    ) {
        // Get the full action name
        $actionName = get_class($action);

        // Skip validation for Asyntai API endpoints
        if (
            strpos($actionName, 'Asyntai\Chatbot\Controller\Adminhtml\Api\Save') !== false ||
            strpos($actionName, 'Asyntai\Chatbot\Controller\Adminhtml\Api\Reset') !== false
        ) {
            return; // Skip validation
        }

        // Continue with normal validation for other actions
        return $proceed($request, $action);
    }
}
