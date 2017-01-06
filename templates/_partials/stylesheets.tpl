<link href="//cdn.rawgit.com/noelboss/featherlight/1.7.0/release/featherlight.min.css" type="text/css" rel="stylesheet" />
{foreach $stylesheets.external as $stylesheet}
  <link rel="stylesheet" href="{$stylesheet.uri}" type="text/css" media="{$stylesheet.media}">
{/foreach}

{foreach $stylesheets.inline as $stylesheet}
  <style>
    {$stylesheet.content}
  </style>
{/foreach}
