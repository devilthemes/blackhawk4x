/**
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
 */

$(document).ready(function(){

    aeuc_controller = new AEUC_Controller();

    if (!!$.prototype.fancybox) {
        $("a.iframe").fancybox({
            'type': 'iframe',
            'width': 600,
            'height': 600
        });
    }

    $('button[name="processCarrier"]').click(function(event){
        /* Avoid any further action */
        event.preventDefault();
        event.stopPropagation();

        if (aeuc_has_virtual_products === true && aeuc_controller.checkVirtualProductRevocation() === false)
        {
            var to_display = $('<div/>').html(aeuc_virt_prod_err_str).text();
            $.fancybox(to_display,{
                minWidth: 'auto',
                minHeight: 'auto'
            });
            return;
        }
        $("#form").submit();
    });

});

var AEUC_Controller = function() {

    this.checkVirtualProductRevocation = function() {
        if ($('#revocation_vp_terms_agreed').prop('checked')) {
            return true;
        }

        return false;
    }
};