{*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}


<dl>
    <dt>{l s='Amount' mod='ps_wirepayment'}</dt>
    <dd>{$total}</dd>
    <dt>{l s='Name of account owner' mod='ps_wirepayment'}</dt>
    <dd>{$bankwireOwner}</dd>
    <dt>{l s='Please include these details' mod='ps_wirepayment'}</dt>
    <dd>{$bankwireDetails}</dd>
    <dt>{l s='Bank name' mod='ps_wirepayment'}</dt>
    <dd>{$bankwireAddress nofilter}</dd>
</dl>
