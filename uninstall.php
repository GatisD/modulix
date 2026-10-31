<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once __DIR__ . '/src/Uninstaller.php';

$modulix_cleanup_site = static function (): void {
	global $wpdb;
	\Modulix\Uninstaller::cleanupSite( $wpdb );
};

if ( is_multisite() ) {
	foreach ( get_sites( [ 'fields' => 'ids', 'number' => 0 ] ) as $modulix_site_id ) {
		switch_to_blog( (int) $modulix_site_id );
		$modulix_cleanup_site();
		restore_current_blog();
	}
} else {
	$modulix_cleanup_site();
}
