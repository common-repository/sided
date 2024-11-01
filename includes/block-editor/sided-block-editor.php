<?php

add_action( 'init', 'sided_init_block_editor_assets', 10, 0 );

function sided_init_block_editor_assets() {
	$assets = array();

	$assets = wp_parse_args( $assets, array(
		'src' => sided_plugin_url( 'includes/block-editor/index.js' ),
		'dependencies' => array(
			'wp-blocks',
			'wp-element',
			'wp-block-editor',
		),
		'version' => SIDED_VERSION,
	) );

	wp_register_script(
		'embed-sided-debates-block-editor',
		$assets['src'],
		$assets['dependencies'],
		$assets['version']
	);

	register_block_type(
		'sided/sided-debate-selector',
		array(
			'editor_script' => 'embed-sided-debates-block-editor',
		)
	);

	register_block_type(
		'sided/sided-debate-creator',
		array(
			'editor_script' => 'embed-sided-debates-block-editor',
		)
	);

	

}
