<?php

add_action( 'init', 'wpcf7_init_switch', 11 );

function wpcf7_init_switch() {
	if ( 'POST' == $_SERVER['REQUEST_METHOD'] && 1 == (int) $_POST['_wpcf7_is_ajax_call'] ) {
		wpcf7_ajax_json_echo();
		exit();
	} elseif ( isset( $_POST['_wpcf7'] ) ) {
		wpcf7_process_nonajax_submitting();
	}
}

function wpcf7_ajax_json_echo() {
	global $wpcf7_contact_form;

	$echo = '';

	if ( isset( $_POST['_wpcf7'] ) ) {
		$id = (int) $_POST['_wpcf7'];
		$unit_tag = $_POST['_wpcf7_unit_tag'];

		if ( $wpcf7_contact_form = wpcf7_contact_form( $id ) ) {
			$validation = $wpcf7_contact_form->validate();

			$items = array(
				'mailSent' => false,
				'into' => '#' . $unit_tag,
				'captcha' => null );

			$items = apply_filters( 'wpcf7_ajax_json_echo', $items );

			if ( ! $validation['valid'] ) { // Validation error occured
				$invalids = array();
				foreach ( $validation['reason'] as $name => $reason ) {
					$invalids[] = array(
						'into' => 'span.wpcf7-form-control-wrap.' . $name,
						'message' => $reason );
				}

				$items['message'] = $wpcf7_contact_form->message( 'validation_error' );
				$items['invalids'] = $invalids;

			} elseif ( ! $wpcf7_contact_form->accepted() ) { // Not accepted terms
				$items['message'] = $wpcf7_contact_form->message( 'accept_terms' );

			} elseif ( $wpcf7_contact_form->akismet() ) { // Spam!
				$items['message'] = $wpcf7_contact_form->message( 'akismet_says_spam' );
				$items['spam'] = true;

			} elseif ( $wpcf7_contact_form->mail() ) {
				$items['mailSent'] = true;
				$items['message'] = $wpcf7_contact_form->message( 'mail_sent_ok' );

				$on_sent_ok = $wpcf7_contact_form->additional_setting( 'on_sent_ok', false );
				if ( ! empty( $on_sent_ok ) ) {
					$on_sent_ok = array_map( 'wpcf7_strip_quote', $on_sent_ok );
				} else {
					$on_sent_ok = null;
				}
				$items['onSentOk'] = $on_sent_ok;

				do_action_ref_array( 'wpcf7_mail_sent', array( &$wpcf7_contact_form ) );

			} else {
				$items['message'] = $wpcf7_contact_form->message( 'mail_sent_ng' );
			}

			// remove upload files
			foreach ( (array) $wpcf7_contact_form->uploaded_files as $name => $path ) {
				@unlink( $path );
			}

			$wpcf7_contact_form = null;
		}
	}

	$echo = wpcf7_json( $items );

	if ( $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' ) {
		@header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
		echo $echo;
	} else {
		@header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		echo '<textarea>' . $echo . '</textarea>';
	}
}

function wpcf7_process_nonajax_submitting() {
	global $wpcf7_contact_form;

	if ( ! isset($_POST['_wpcf7'] ) )
		return;

	$id = (int) $_POST['_wpcf7'];

	if ( $wpcf7_contact_form = wpcf7_contact_form( $id ) ) {
		$validation = $wpcf7_contact_form->validate();

		if ( ! $validation['valid'] ) {
			$_POST['_wpcf7_validation_errors'] = array( 'id' => $id, 'messages' => $validation['reason'] );
		} elseif ( ! $wpcf7_contact_form->accepted() ) { // Not accepted terms
			$_POST['_wpcf7_mail_sent'] = array( 'id' => $id, 'ok' => false, 'message' => $wpcf7_contact_form->message( 'accept_terms' ) );
		} elseif ( $wpcf7_contact_form->akismet() ) { // Spam!
			$_POST['_wpcf7_mail_sent'] = array( 'id' => $id, 'ok' => false, 'message' => $wpcf7_contact_form->message( 'akismet_says_spam' ), 'spam' => true );
		} elseif ( $wpcf7_contact_form->mail() ) {
			$_POST['_wpcf7_mail_sent'] = array( 'id' => $id, 'ok' => true, 'message' => $wpcf7_contact_form->message( 'mail_sent_ok' ) );

			do_action_ref_array( 'wpcf7_mail_sent', array( &$wpcf7_contact_form ) );
		} else {
			$_POST['_wpcf7_mail_sent'] = array( 'id' => $id, 'ok' => false, 'message' => $wpcf7_contact_form->message( 'mail_sent_ng' ) );
		}

		// remove upload files
		foreach ( (array) $wpcf7_contact_form->uploaded_files as $name => $path ) {
			@unlink( $path );
		}

		$wpcf7_contact_form = null;
	}
}

add_filter( 'the_content', 'wpcf7_the_content_filter', 9 );

function wpcf7_the_content_filter( $content ) {
	global $wpcf7_processing_within, $wpcf7_unit_count;

	$wpcf7_processing_within = 'p' . get_the_ID();
	$wpcf7_unit_count = 0;

	return $content;
}

add_filter( 'widget_text', 'wpcf7_widget_text_filter', 9 );

function wpcf7_widget_text_filter( $content ) {
	global $wpcf7_widget_count, $wpcf7_processing_within, $wpcf7_unit_count;

	$wpcf7_widget_count += 1;
	$wpcf7_processing_within = 'w' . $wpcf7_widget_count;
	$wpcf7_unit_count = 0;

	$regex = '/\[\s*contact-form\s+(\d+(?:\s+.*)?)\]/';
	return preg_replace_callback( $regex, 'wpcf7_widget_text_filter_callback', $content );
}

function wpcf7_widget_text_filter_callback( $matches ) {
	return do_shortcode( $matches[0] );
}

add_shortcode( 'contact-form', 'wpcf7_contact_form_tag_func' );

function wpcf7_contact_form_tag_func( $atts ) {
	global $wpcf7_contact_form, $wpcf7_unit_count, $wpcf7_processing_within;

	if ( is_string( $atts ) )
		$atts = explode( ' ', $atts, 2 );

	$atts = (array) $atts;

	$id = (int) array_shift( $atts );

	if ( ! ( $wpcf7_contact_form = wpcf7_contact_form( $id ) ) )
		return '[contact-form 404 "Not Found"]';

	$wpcf7_unit_count += 1;

	$unit_tag = 'wpcf7-f' . $id . '-' . $wpcf7_processing_within . '-o' . $wpcf7_unit_count;
	$wpcf7_contact_form->unit_tag = $unit_tag;

	$form = $wpcf7_contact_form->form_html();

	$wpcf7_contact_form = null;

	return $form;
}

if ( WPCF7_LOAD_JS )
	add_action( 'wp_print_scripts', 'wpcf7_enqueue_scripts' );

function wpcf7_enqueue_scripts() {
	$in_footer = true;
	if ( 'header' === WPCF7_LOAD_JS )
		$in_footer = false;

	wp_enqueue_script( 'contact-form-7', wpcf7_plugin_url( 'contact-form-7.js' ),
		array('jquery', 'jquery-form'), WPCF7_VERSION, $in_footer );
}

if ( WPCF7_LOAD_CSS )
	add_action( 'wp_print_styles', 'wpcf7_enqueue_styles' );

function wpcf7_enqueue_styles() {
	wp_enqueue_style( 'contact-form-7', wpcf7_plugin_url( 'stylesheet.css' ),
		array(), WPCF7_VERSION, 'all' );

	if ( 'rtl' == get_bloginfo( 'text_direction' ) ) {
		wp_enqueue_style( 'contact-form-7-rtl', wpcf7_plugin_url( 'stylesheet-rtl.css' ),
			array(), WPCF7_VERSION, 'all' );
	}
}

?>