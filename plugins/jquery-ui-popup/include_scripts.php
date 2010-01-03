<?php

wp_register_script( 'jquery-ui-position', wpcf7_plugin_url( '/plugins/jquery-ui-popup/position.js' ), 
	array('jquery', 'jquery-ui-core', ), WPCF7_VERSION, $in_footer );

wp_register_script( 'jquery-ui-popup', wpcf7_plugin_url( '/plugins/jquery-ui-popup/jquery-ui-popup.js' ), 
	array('jquery', 'jquery-ui-core', 'jquery-ui-position'), WPCF7_VERSION, $in_footer );

wp_enqueue_script('popup-adapter', wpcf7_plugin_url( '/plugins/jquery-ui-popup/popup-adapter.js' ), 
	array('jquery', 'jquery-ui-core', 'jquery-ui-position', 'jquery-ui-popup'), WPCF7_VERSION, $in_footer );

?>
