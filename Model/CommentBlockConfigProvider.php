<?php
/**
 * Copyright © 2017 RohitKundale. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RohitKundale\OrderComment\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class CommentBlockConfigProvider
 *
 * @package RohitKundale_OrderComment
 */
class CommentBlockConfigProvider implements ConfigProviderInterface
{
    const CHECKOUT_COMMENT_ENABLED = 'checkout/options/checkout_comments_enabled';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfiguration;

    /**
     * CommentBlockConfigProvider constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfiguration
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfiguration
    ) {
        $this->_scopeConfiguration = $scopeConfiguration;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
		/** @var array() $displayConfig */
        $displayConfig = [];

		/** @var boolean $enabled */
        $enabled = $this->_scopeConfiguration->getValue(self::CHECKOUT_COMMENT_ENABLED, ScopeInterface::SCOPE_STORE);
        $displayConfig['show_comment_block'] = ($enabled) ? true : false;
        return $displayConfig;
    }
}