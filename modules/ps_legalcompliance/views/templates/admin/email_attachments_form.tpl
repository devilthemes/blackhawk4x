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

<form id="emailAttachementsManager" class="defaultForm form-horizontal" action="{$form_action}" method="POST" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="AEUC_emailAttachmentsManager" value="1">
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-envelope"></i>
            {l s='Email content inclusion' mod='advancedeucompliance'}
        </div>
        <p>
            {l s='This section allows you to include information from the "Legal Content Management" section above at the bottom of your shop\'s emails.' mod='advancedeucompliance'}
        </p>
        <p>
            {l s='For each type of email, you can define which content you would like to include.' mod='advancedeucompliance'}
        </p>
        <br/>
        <div class="form-wrapper">
            <table class="table accesses">
                <thead>
                <tr>
                    <th>
                        <span class="title_box">
                            <input id="selectall_attach" type="checkbox"/>
                            {l s='Email templates' mod='advancedeucompliance'}
                        </span>

                    </th>
                    {foreach from=$legal_options item=option}
                        <th class="center fixed-width-xs">
                            <span class="title_box">
                                 <input id="selectall_opt_{$option.id|intval}" type="checkbox"/>
                                {$option.name|escape:'htmlall'}
                            </span>
                        </th>
                    {/foreach}
                </tr>
                </thead>
                <tbody>
                {foreach from=$mails_available item=mail}
                    <tr>
                        <td><input id="mail_{$mail.id_mail|intval}" class="select-all-for-mail" type="checkbox"/></th>&nbsp;{$mail.display_name|escape:'htmlall'}</td>
                        {foreach from=$legal_options item=option}
                            <td class="center">
                                <input name="attach_{$mail.id_mail|intval}_{$option.id|intval}" id="attach_{$mail.id_mail|intval}_{$option.id|intval}" type="checkbox"
                                {if in_array($mail.id_mail, $option.list_id_mail_assoc)}
                                    checked="true"
                                {/if}
                                /></th>
                            </td>
                        {/foreach}
                    </tr>

                {/foreach}
                </tbody>
            </table>
        </div>

        <div class="panel-footer">
            <button type="submit" class="btn btn-default pull-right">
                <i class="process-icon-save"></i>  {l s='Save' mod='advancedeucompliance'}
            </button>

        </div>
    </div>
</form>


