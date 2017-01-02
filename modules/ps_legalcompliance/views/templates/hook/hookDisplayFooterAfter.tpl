{**
 * 2007-2016 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
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
 *  @author 	PrestaShop SA <contact@prestashop.com>
 *  @copyright  2007-2016 PrestaShop SA
 *  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 *}

<div class="aeuc_footer_info">
	{if isset($delivery_additional_information)}
		* {$delivery_additional_information}
		<a href="{$link_shipping}">{l s='Shipping and payment' mod='ps_legalcompliance'}</a>
	{else}
		{if $tax_included}
			{l s='All prices are mentioned tax included' mod='ps_legalcompliance'}
		{else}
			{l s='All prices are mentioned tax excluded' mod='ps_legalcompliance'}
		{/if}
		{if $show_shipping}
			{l s='and' mod='ps_legalcompliance'}
			{if $link_shipping}
				<a href="{$link_shipping}">
			{/if}
			{l s='shipping excluded' mod='ps_legalcompliance'}
			{if $link_shipping}
				</a>
			{/if}
		{/if}
	{/if}
</div>