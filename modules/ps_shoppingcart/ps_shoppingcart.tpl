<div class="blockcart dropdown close cart-preview  col-sm-3 col-xs-12" data-refresh-url="{$refresh_url}">
  
    <a rel="nofollow" href="{$cart_url}"    class="header btn btn-link dropdown-toggle" id="menu1" type="button" data-toggle="dropdown">
      <i class="fa fa-shopping-basket"></i><span>{l s='Cart' d='Shop.Theme.Actions'}</span>
      <span>{$cart.summary_string}</span>
	   <span class="sr-only">Toggle Dropdown</span>
    </a>
  
  <div class="dropdown-menu" class="dropdown-menu" role="menu" aria-labelledby="menu1">
    <ul class="dropdown-item">
      {foreach from=$cart.products item=product}
        <li>{include 'module:ps_shoppingcart/ps_shoppingcart-product-line.tpl' product=$product}</li>
      {/foreach}
    </ul>
    <div class="cart-subtotals">
      {foreach from=$cart.subtotals item="subtotal"}
        <div class="{$subtotal.type}">
          <span class="label">{$subtotal.label}</span>
          <span class="value">{$subtotal.amount}</span>
        </div>
      {/foreach}
    </div>
    <div class="cart-total">
      <span class="label">{$cart.totals.total.label}</span>
      <span class="value">{$cart.totals.total.amount}</span>
    </div>
  </div>
</div>


