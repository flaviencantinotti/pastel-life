<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( function_exists( 'hello_get_header_display' ) && ! hello_get_header_display() ) {
	return;
}

get_template_part( 'template-parts/header' );
