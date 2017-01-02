{block name='header_banner'}
  <div class="header-banner">
    {hook h='displayBanner'}
  </div>
{/block}

{block name='header_nav'}
  <div class="header-nav">
	<div class="container">
		<div class="row">
		{hook h='displayNav'}
		</div>
	</div>
  </div>
{/block}

{block name='header_logo'}
<div class="logo-bar">
<div class="container">
		<div class="row">
		<div class="logo_block col-sm-4 col-xs-12">
  <a class="logo" href="{$urls.base_url}" title="{$shop.name}">
 
    <img src="{$shop.logo}" alt="{$shop.name}" /> 
  </a></div>
  {hook h='displayLogo'}
  </div></div>
  </div>
{/block}

{block name='header_top'}
  <div class="navbar navbar-default">
<div class="container">
	<div class="row">
	

	
    {hook h='displayTop'}

	
  </div>
   </div>
</div>
  {hook h='displayNavFullWidth'}

{/block}
