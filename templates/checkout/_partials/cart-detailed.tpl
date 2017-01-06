<div class="cart-overview js-cart" data-refresh-url="{url entity='cart' params=['ajax' => 1]}">
  <div class="body">
    <ul class="grid-table">
      <li class="thead">
		<span class="img td"></span>
		<span class="product td">{l s='Product' d='Shop.Theme.Checkout'}</span>
		<span class="price td">{l s='Unit Price' d='Shop.Theme.Checkout'}</span>
		<span class="qty td">{l s='Quantity' d='Shop.Theme.Checkout'}</span>
		<span class="remove td"></span>
		<span class="total td">{l s='Total' d='Shop.Theme.Checkout'}</span>
	  </li>
	  {foreach from=$cart.products item=product}
        <li class="cart-item tr">{include file='checkout/_partials/cart-detailed-product-line.tpl' product=$product}</li>
      {/foreach}
    </ul>
  </div>
</div>
