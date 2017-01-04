<div class="cart-overview js-cart" data-refresh-url="{url entity='cart' params=['ajax' => 1]}">
  <div class="body">
    <ul class="grid-table">
      {foreach from=$cart.products item=product}
        <li class="cart-item tr">{include file='checkout/_partials/cart-detailed-product-line.tpl' product=$product}</li>
      {/foreach}
    </ul>
  </div>
</div>
