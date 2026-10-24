<?php
// Modulix: derived from Elementor Pro 4.0.4, modules/assets-manager/asset-types/icons/templates.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<script type="text/template" id="elementor-custom-icons-template-footer">
	<div class="elementor-icon-set-footer"><?php echo esc_html__( 'Created on', 'modulix' ); ?>: {{day}}/{{mm}}/{{year}}, {{hour}}:{{minute}}</div>
</script>

<script type="text/template" id="elementor-custom-icons-template-header">
	<div class="elementor-icon-set-header">
		<div><span class="elementor-icon-set-header-meta"><?php echo esc_html__( 'Name', 'modulix' ); ?>: </span><span class="elementor-icon-set-header-meta-value">{{name}}</span></div>
		<div><span class="elementor-icon-set-header-meta"><?php echo esc_html__( 'CSS Prefix', 'modulix' ); ?>: </span><span class="elementor-icon-set-header-meta-value">{{prefix}}</span></div>
		<div><span class="elementor-icon-set-header-meta"><?php echo esc_html__( 'Icons Count', 'modulix' ); ?>: </span><span class="elementor-icon-set-header-meta-value">{{count}}</span></div>
	</div>
</script>

<script type="text/template" id="elementor-custom-icons-template-duplicate-prefix">
	<div class="elementor-icon-set-duplicate-prefix"><?php echo esc_html__( 'The Icon Set prefix already exists in your site. In order to avoid conflicts we recommend to use a unique prefix per Icon Set.', 'modulix' ); ?></div>
</script>
