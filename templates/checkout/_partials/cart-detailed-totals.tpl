<div class="cart-detailed-totals">
  <div class="cart-subtotals">
    {foreach from=$cart.subtotals item="subtotal"}
      <div class="{$subtotal.type} tr">
        <span class="label td">{$subtotal.label}</span>
        <span class="value td">{$subtotal.amount}</span>
      </div>
    {/foreach}
  </div>

  <div class="cart-total tr">
    <span class="label td">{$cart.totals.total.label}</span>
    <span class="value td">{$cart.totals.total.amount}</span>
    {if $subtotal.type === 'shipping'}
        {hook h='displayCheckoutSubtotalDetails' subtotal=$subtotal}
    {/if}
  </div>
</div>
