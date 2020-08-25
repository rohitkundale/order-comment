# Magento 2 Order Comment Module

## Description
This extension allows add a special note/message/comment or instruction while placing an order.
The comment field is displayed in the billing step right above the place order button.

Store owners can then see these comments in the backend on the order view page.

## Installation

Log in to the Magento server, go to your Magento install directory and run following commands:
```
composer require rohitkundale/order-comment

php -f bin/magento module:enable RohitKundale_OrderComment
php -f bin/magento setup:upgrade

php -f bin/magento setup:static-content:deploy
```
