{foreach $javascript.external as $js}
  <script type="text/javascript" src="{$js.uri}" {$js.attribute}></script>
{/foreach}
 <script type="text/javascript" src="{$urls.js_url}bxslider/jquery.bxslider.js"></script>
 <script type="text/javascript" src="{$urls.js_url}bxslider/plugins/jquery.easing.1.3.js"></script>
{foreach $javascript.inline as $js}
  <script type="text/javascript">
    {$js.content nofilter}
  </script>
{/foreach}

{if isset($vars) && $vars|@count}
  <script type="text/javascript">
    {foreach from=$vars key=var_name item=var_value}
    var {$var_name} = {$var_value|json_encode nofilter};
    {/foreach}
  </script>
{/if}
