<!doctype html>
<html lang="{$language.locale}">

  <head>
    {block name='head'}
      {include file='_partials/head.tpl'}
    {/block}
  </head>

  <body id="{$page.page_name}" class="{$page.body_classes|classnames}">

    {hook h='displayAfterBodyOpeningTag'}

    <header id="header">
      {block name='header'}
        {include file='_partials/header.tpl'}
      {/block}
    </header>

    {block name='notifications'}
      {include file='_partials/notifications.tpl'}
    {/block}

    <div id="wrapper" class="container" >
	<div class="row">
      {block name='breadcrumb'}
        {include file='_partials/breadcrumb.tpl'}
      {/block}

      {block name='left_column'}
        <div id="left-column" class="col-sm-4 col-xs-12">
          {if $page.page_name == 'product'}
            {hook h='displayLeftColumnProduct'}
          {else}
            {hook h="displayLeftColumn"}
          {/if}
        </div>
      {/block}

      {block name='right_column'}
        <div id="right-column" class="col-sm-4 col-xs-12">
          {if $page.page_name == 'product'}
            {hook h='displayRightColumnProduct'}
          {else}
            {hook h="displayRightColumn"}
          {/if}
        </div>
      {/block}

      {block name='content_wrapper'}
        <div id="content-wrapper" class="left-column right-column col-sm-8 col-xs-12">
          {block name='content'}
            <p>Hello world! This is HTML5 Boilerplate.</p>
          {/block}
        </div>
      {/block}
	</div>
    </div>

    <footer id="footer">
      {block name='footer'}
        {include file='_partials/footer.tpl'}
      {/block}
    </footer>

    {block name='javascript_bottom'}
      {include file="_partials/javascript.tpl" javascript=$javascript.bottom}
    {/block}

    {hook h='displayBeforeBodyClosingTag'}

  </body>

</html>
