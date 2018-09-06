<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="content-type" content="text/html; charset=UTF-8"/>

    <base href="{$basedir}" />

    <title>PiFotomat</title>

    <link href="res/css/bootstrap.css" rel="stylesheet">

    {foreach $customcssfile as $cssFile}
        <link rel="stylesheet" href="{$cssFile}"/>
    {/foreach}
</head>
<body style="padding-top: 60px;">

<nav class="navbar navbar-default navbar-fixed-top">
    <div class="container">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="#">PiFotomat</a>
        </div>

        <!-- Collect the nav links, forms, and other content for toggling -->
        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav">
                {foreach $nav_tabs as $tab}
                    {if isset($tab.items)}
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">{$tab.name} <span class="caret"></span></a>
                            <ul class="dropdown-menu" role="menu">
                                {foreach $tab.items as $item}
                                    {if $item.route == "separator"}
                                        <li role="separator" class="divider"></li>
                                    {elseif $item.route == "header"}
                                        <li class="dropdown-header">{$item.name}</li>
                                    {else}
                                        <li><a href="{$item.route}" {if !empty($item.target)}target="{$item.target}"{/if}>{$item.name}</a></li>
                                    {/if}
                                {/foreach}
                            </ul>
                        </li>
                    {else}<li><a href="{$tab.url}">{$tab.name}</a></li>
                    {/if}
                {/foreach}
            </ul>
            <ul class="nav navbar-nav navbar-right">
                {*
                <form class="navbar-form navbar-left" role="search">
                    <div class="form-group">
                        <input type="text" class="form-control" placeholder="Search">
                    </div>
                    <button type="submit" class="btn btn-default">Search</button>
                </form>
                *}
                {foreach $nav_tabs_right as $tab}
                    {if isset($tab.items)}
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">{$tab.name} <span class="caret"></span></a>
                            <ul class="dropdown-menu" role="menu">
                                {foreach $tab.items as $item}
                                    {if $item.route == "separator"}
                                        <li role="separator" class="divider"></li>
                                    {elseif $item.route == "header"}
                                        <li class="dropdown-header">{$item.name}</li>
                                    {else}
                                        <li><a href="{$item.route}" {if !empty($item.target)}target="{$item.target}"{/if}>{$item.name}</a></li>
                                    {/if}
                                {/foreach}
                            </ul>
                        </li>
                    {else}<li><a href="{$tab.url}">{$tab.name}</a></li>
                    {/if}
                {/foreach}
            </ul>
        </div>
    </div>
</nav>

<div class="container">
    {if count($success) gt 0}
        <div class="alert alert-dismissable alert-success">
            <ul>
                {foreach $success as $succ}
                    <li>{$succ}</li>
                {/foreach}
            </ul>
        </div>
    {/if}
    {if count($errors) gt 0}
        <div class="alert alert-dismissable alert-warning">
            <h4>{t}Es sind Fehler aufgetreten{/t}</h4>
            <ul>
                {foreach $errors as $error}
                    <li>{$error}</li>
                {/foreach}
            </ul>
        </div>
    {/if}

    {$content}
</div>

<script src="res/js/jquery.min.js"></script>
<script src="res/js/bootstrap.min.js"></script>
{foreach from=$customjsfile item=jsFile}
    <script type="text/javascript" src="js/{$jsFile}.js" charset="utf-8"></script>
{/foreach}

{if isset($debugbar)}
    {$debugbar->render()}
{/if}

<footer class="footer">
    <div class="container">
        <p class="text-muted text-center">&copy {$smarty.now|date_format:'%Y'} Peter Große</p>
    </div>
</footer>

</body>
</html>

