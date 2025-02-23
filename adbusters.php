<?php
/*
Plugin Name: Adbusters
Plugin URI: https://github.com/Automattic/Adbusters
Description: Iframe busters for popular ad networks.
Version: 1.0.8
Requires at least: 3.7
License: GPLv3
Author: Paul Gibbs, Mohammad Jangda, Automattic
Author URI: http://automattic.com/
Text Domain: adbusters

"Adbusters"
Copyright (C) 2013 Automattic

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License version 3 as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see http://www.gnu.org/licenses/.
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function wpcom_vip_get_ad_busters_array() {
	return array(
		'_uac/adpage.html',                   // AOL - atwola.com
		'adcom/aceFIF.html',                  // Advertising.com (ad.com)
		'adinterax/adx-iframe-v2.html',       // AdInterax
		'atlas/atlas_rm.htm',                 // Atlas
		'blogads/iframebuster-4.html',        // BlogAds
		'checkm8/CM8IframeBuster.html',       // CheckM8
		'comscore/cs-arIframe.htm',           // comScore
		'doubleclick/adx_iframe.html',        // Google - DoubleClick
		'doubleclick/DARTIframe.html',        // Google - DoubleClick
		'doubleclick/fif.html',               // Flite
		'doubleclick/TLIframe.html',          // TripleLift
		'flite/fif.html',                     // Flite
		'gumgum/iframe_buster.html',          // gumgum
		'jpd/jpxdm.html',                     // Jetpack Digital
		'knorex/knorex_psf.html',             // Knorex
		'linkstorm/linkstorm_certified.html', // Linkstorm
		'oggifinogi/oggiPlayerLoader.htm',    // Collective - OggiFinogi
		'pictela/Pictela_iframeproxy.html',   // AOL - Pictela
		'pointroll/PointRollAds.htm',         // PointRoll
		'rubicon/rp-smartfile.html',		  // Rubicon
		'saymedia/iframebuster.html',         // Say Media
		'xaxis/InfinityIframe.html',          // Xaxis
	);
}

/**
 * If an appropriate request comes in, load an iframe ad buster file.
 *
 * Note: the following networks/scripts are insecure and will not be added to the plugin:
 * > EyeReturn (/eyereturn/eyereturn.html)
 * > Unicast (/unicast/unicastIFD.html)
 *
 * @since Adbusters (1.0)
 */
function wpcom_vip_maybe_load_ad_busters() {

	$ad_busters = wpcom_vip_get_ad_busters_array();

	// To only support a specific ad network, use this filter and return an array containing the values of $ad_busters to load
	$whitelist_ads = (array) apply_filters( 'wpcom_vip_ad_busters_whitelist', $ad_busters );
	$ad_busters = array_intersect( $ad_busters, $whitelist_ads );

	// To ignore an ad network, use this filter and return an array containing the values of $ad_busters to not load
	$block_ads  = apply_filters( 'wpcom_vip_maybe_load_ad_busters', array() );
	$ad_busters = array_diff( $ad_busters, $block_ads );
	$ad_paths   = $ad_busters;

	// nothing to do if there are no paths after whitelisting and blocking
	if ( empty( $ad_paths ) )
		return;

	// If your ads need to be served from example.com/some/subfolder/*, pass "some/subfolder" to this filter
	$path = explode( '/', apply_filters( 'wpcom_vip_ad_busters_custom_path', '' ) );
	$path = array_filter( array_map( 'sanitize_title', $path ) );
	$path = implode( '/', $path );

	// Make sure both the ends of the path have slashes
	$path = _wpcom_vip_leadingslashit( $path );
	$path = trailingslashit( $path );

	$ad_busters = array_map( function( $ad_file ) use ( $path ) {
		return "{$path}{$ad_file}";
	}, $ad_busters );

	// Do we have a request for a supported network?
	$request = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
	$index   = array_search( $request, $ad_busters );
	if ( false === $index )
		return;

	// Spit out the template
	$file = plugin_dir_path( __FILE__ ) . 'templates/' . $ad_paths[$index];
	if ( ! file_exists( $file ) )
		return;

	header( 'Content-type: text/html' );
	readfile( $file );

	exit;
}
add_action( 'init', 'wpcom_vip_maybe_load_ad_busters', -1 );

/**
 * Prepends a leading slash.
 *
 * Will remove leading slash if it exists already before adding a leading slash. This prevents double slashing a string or path.
 * The primary use of this is for paths and thus should be used for paths. It is not restricted to paths and offers no specific path support.
 *
 * @access private
 * @param string $string What to add the leading slash to.
 * @return string String with leading slash added.
 */
function _wpcom_vip_leadingslashit( $string ) {
  return '/' . _wpcom_vip_unleadingslashit( $string );
}

/**
 * Removes leading slash if it exists.
 *
 * The primary use of this is for paths and thus should be used for paths. It is not restricted to paths and offers no specific path support.
 *
 * @access private
 * @param string $string What to remove the leading slash from.
 * @return string String without the leading slash.
 */
function _wpcom_vip_unleadingslashit( $string ) {
  return ltrim( $string, '/' );
}
