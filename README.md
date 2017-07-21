# Magento 2 Order Comment Module
## Installation

Log in to the Magento server, go to your Magento install directory and run following commands:
```
composer require rohitkundale/order-comment

php -f bin/magento module:enable RohitKundale_OrderComment
php -f bin/magento setup:upgrade

rm -rf pub/static/*; rm -rf var/view_preprocessed/*;
php -f bin/magento setup:static-content:deploy
```
