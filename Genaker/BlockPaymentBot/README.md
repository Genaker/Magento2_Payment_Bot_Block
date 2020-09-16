# Mage2 Module Genaker BlockPaymentBot

How to test 

send POSTrequest to {domain}/rest/default/V1/guest-carts/dgfjsdhfgsdhfgsdhfgsdhfgsdjfk/payment-information

with the same cart ID multiple times after you request will be blocked for 5 minutes...

You can adjust rate and time.

    ``genaker/module-blockpaymentbot``

 - [Main Functionalities](#markdown-header-main-functionalities)
 - [Installation](#markdown-header-installation)
 - [Configuration](#markdown-header-configuration)
 - [Specifications](#markdown-header-specifications)
 - [Attributes](#markdown-header-attributes)


## Main Functionalities


## Installation
\* = in production please use the `--keep-generated` option

### Type 1: Zip file

 - Unzip the zip file in `app/code/Genaker`
 - Enable the module by running `php bin/magento module:enable Genaker_BlockPaymentBot`
 - Apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`

### Type 2: Composer

 - Make the module available in a composer repository for example:
    - private repository `repo.magento.com`
    - public repository `packagist.org`
    - public github repository as vcs
 - Add the composer repository to the configuration by running `composer config repositories.repo.magento.com composer https://repo.magento.com/`
 - Install the module composer by running `composer require genaker/module-blockpaymentbot`
 - enable the module by running `php bin/magento module:enable Genaker_BlockPaymentBot`
 - apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`


## Configuration




## Specifications

 - Observer
	- core_abstract_load_before > Genaker\BlockPaymentBot\Observer\Webapi\Core\AbstractLoadBefore


## Attributes


