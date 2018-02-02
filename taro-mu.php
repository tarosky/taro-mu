<?php
/**
 * Plugin Name: Taro MU
 * Plugin URI: https://tarosky.co.jp
 * Description: Tarosky's must-use plugins.
 * Version: 1.0.0
 * Author: tarosky
 * Author URI: https://tarosky.co.jp
 * Requires at least: 4.8
 * Tested up to: 4.9.2
 */

$taro_mu_plugins = [];
$taro_mu_dir = __DIR__ . '/taro-mu';
if ( is_dir( $taro_mu_dir ) ) {
	foreach ( scandir( $taro_mu_dir ) as $file ) {
		if ( preg_match( '#^[^._].*\.php$#u', $file ) ) {
			$taro_mu_plugins[] = $file;
		}
	}
}

/**
 * Get settings
 *
 * @return stdClass
 */
function taro_mu_load_setting() {
	static $settings = null;
	$path = __DIR__ . '/taro-mu-setting.json';
	if ( ! is_null( $settings ) ) {
		return $settings;
	}
	if ( ! file_exists( $path ) ) {
		return null;
	}
	$settings = json_decode( file_get_contents( $path ) );
	return $settings;
}



$taro_mu_settings = taro_mu_load_setting();
if ( isset( $taro_mu_settings->excludes ) ) {
	foreach ( $taro_mu_settings->excludes as $exclude ) {
		if ( false !== ( $index = array_search( $exclude, $taro_mu_plugins ) ) ) {
			unset( $taro_mu_plugins[ $index ] );
		}
	}
}




foreach ( $taro_mu_plugins as $file ) {
	include $taro_mu_dir . '/' . $file;
}
