<?php
// Modulix: derived from Elementor Pro 4.0.4, modules/theme-builder/views/theme-support-footer.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.
use Modulix\Modules\ThemeBuilder\Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$location_manager = Module::instance()->get_locations_manager();
$location_manager->do_location( 'footer' ); ?>

<?php wp_footer(); ?>

</body>
</html>
