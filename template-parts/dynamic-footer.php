<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( function_exists( 'hello_get_footer_display' ) && ! hello_get_footer_display() ) {
	return;
}

get_template_part( 'template-parts/footer' );
