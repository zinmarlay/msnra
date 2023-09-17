<!DOCTYPE html>
<html <?php language_attributes(); ?> <?php blankslate_schema_type(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<meta name="viewport" content="width=device-width" />
<link rel="stylesheet" type="text/css" href="<?php bloginfo('template_url')?>/css/style.css">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header>
<div style="padding:10px 0; background:green;">
    top bar
</div>
<?php if ( function_exists( 'groovy_menu' ) ) { groovy_menu(); } ?>
</header>
<section>
<?php xo_slider( 34 ); ?>
</section>