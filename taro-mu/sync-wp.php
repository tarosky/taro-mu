<?php

if ( ! class_exists( 'WP_CLI_Command' ) ) {
	return;
}

/**
 * Sync local WordPress to remote wp.
 */
class TaroSyncWp extends WP_CLI_Command {

	/**
	 * Do synchronization.
     *
     * @synopsis [--timeout=<timeout>]
	 *
     * @param array $args
     * @param array $assoc
	 */
	public function sync( $args, $assoc ) {
		$setting = $this->get_info();
		$timeout = ( isset( $assoc['timeout'] ) && is_numeric( $assoc['timeout'] ) ) ? $assoc['timeout'] : 300 ;
		try {
			if ( ! isset( $setting['url'] ) ) {
				throw new Exception( 'URL is not set.' );
			}
			$to   = untrailingslashit( home_url( '' ) );
			$args = [
				'timeout'     => $timeout,
			];
			if ( isset( $setting['auth'] ) ) {
				$args['headers'] = [
					'Authorization' => 'Basic ' . base64_encode( "{$setting['auth']->user}:{$setting['auth']->password}" ),
				];
			}
			$response = wp_remote_get( $setting['url'], $args );
			if ( is_wp_error( $response ) ) {
				throw new Exception( $response->get_error_message() );
			} elseif ( 200 != $response['response']['code'] ) {
				throw new Exception( sprintf( 'Response is %d %s.', $response['response']['code'], get_status_header_desc( $response['response']['code'] ) ) );
			}
			WP_CLI::line( 'Donwnload finished' );
			// Save tmp dir.
			$temp_dir  = ABSPATH . 'wp-content';
			$temp_path = tempnam( $temp_dir, 'wp-' );
			$temp_gz = $temp_path . '.tar.gz';
			file_put_contents( $temp_gz, $response['body'] );

			// unpack
			$cmd = "tar xzf {$temp_gz} -C {$temp_dir}";
			$unpacked_dir = $temp_dir . '/backup.folder';
			exec( $cmd );
			if ( ! is_dir( $unpacked_dir ) ) {
				throw new Exception( sprintf( 'Unpack failed: %s', $unpacked_dir ) );
			}
			exec( "rm -rf {$temp_gz}" );
			WP_CLI::line( sprintf( 'Backup file: %s', $unpacked_dir ) );

			// Copy plugins and uploads
			foreach ( [ 'uploads', 'plugins', 'themes' ] as $dir ) {
				$target = ABSPATH . "wp-content";
				$src    = "$unpacked_dir/{$dir}";
				if ( ! is_dir( $src ) ) {
					WP_CLI::warning( sprintf( 'No backup in %1$s.', $dir ) );
					continue;
				}
				switch ( $dir ) {
					// Copy all uploads.
					case 'uploads':
						$cmd = "cp -rf {$src} {$target}";
						exec( $cmd );
						break;
					case 'themes':
					case 'plugins':
						foreach ( scandir( $src ) as $plugin_dir ) {
							if ( 0 === strpos( $plugin_dir, '.' ) ) {
								continue;
							}
							// Check if this plugin is excluded.
							if ( $this->is_excluded( $dir, $plugin_dir ) ) {
								WP_CLI::line( sprintf( '[SKIP] %1$s in %2$s', $plugin_dir, $dir ) );
								continue;
							}
							$plugin_dir = "{$src}/{$plugin_dir}";
							$cmd = "cp -rf {$plugin_dir} {$target}/plugins";
							exec( $cmd );
						}
						break;
				}
				WP_CLI::line( sprintf( '[DONE] %s copied...',  $dir ) );
			}

			$from = untrailingslashit( $setting['home'] );

			// Import backup file.
			foreach ( [
				"wp db import {$unpacked_dir}/wordpress_db.sql --allow-root",
				"wp search-replace '{$from}' '{$to}' --allow-root",
			] as $cmd ) {
				exec( $cmd );
			}

			// Remove original dir.
			exec("rm -rf {$unpacked_dir}");
			exec("rm -rf {$temp_path}");

			// Contents finished.
			WP_CLI::success( 'Import finished!' );

		} catch ( Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}
	}
	
	/**
	 * Check if plugin is excludes.
	 */
	private function is_excluded( $target, $dir ) {
		$setting = $this->get_info();
		$prop = $target . '_excluded';
		if ( ! isset( $setting[ $prop ] ) || ! is_array( $setting[ $prop ] ) ) {
			return false;
		} else {
			return in_array( $dir, $setting[ $prop ], true );
		}
	}

	/**
	 * Get information
	 *
	 * @return array
	 */
	protected function get_info() {
		$setting = taro_mu_load_setting();
		$values = [];
		if ( isset( $setting->sync ) ) {
			foreach ( $setting->sync as $key => $value ) {
				$values[ $key ] = $value;
			}
		}
		return $values;
	}

	/**
	 * Display Information
	 */
	public function info() {
		$setting = $this->get_info();
		if ( ! $setting ) {
			WP_CLI::error( 'No setting file found.' );
		}
		$table = new \cli\Table();
		$table->setHeaders( [ 'KEY', 'VALUE' ] );
		foreach ( $setting as $key => $val ) {
			if ( is_string( $val ) ) {
				$table->addRow( [ $key, $val ] );
			} elseif ( is_array( $val ) ) {
				$table->addRow( [ $key, $val ? implode( ', ', $val ) : 'EMPTY ARRAY' ] );
			} else {
				$table->addRow( [ $key, gettype( $val ) ] );
			}
		}
		$table->display();
	}
}

WP_CLI::add_command( 'taro-sync', 'TaroSyncWp' );
