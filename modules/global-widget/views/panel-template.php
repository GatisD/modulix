<?php
// Modulix: derived from Elementor Pro 4.0.4, modules/global-widget/views/panel-template.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<script type="text/template" id="tmpl-elementor-panel-global-widget">
	<div id="elementor-global-widget-locked-header" class="elementor-nerd-box elementor-panel-nerd-box">
		<img class="elementor-nerd-box-icon" src="<?php echo ELEMENTOR_ASSETS_URL . 'images/information.svg'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" loading="lazy" />
		<div class="elementor-nerd-box-title elementor-panel-nerd-box-title"><?php echo esc_html__( 'Your Global Widget is locked', 'modulix' ); ?></div>
		<div class="elementor-nerd-box-message elementor-panel-nerd-box-message"><?php echo esc_html__( 'Edit this global widget to simultaneously update every place you used it, or unlink it so it gets back to being regular widget.', 'modulix' ); ?></div>
	</div>
	<div id="elementor-global-widget-locked-tools">
		<div id="elementor-global-widget-locked-edit" class="elementor-global-widget-locked-tool">
			<div class="elementor-global-widget-locked-tool-description"><?php echo esc_html__( 'Edit global widget', 'modulix' ); ?></div>
			<button class="elementor-button"><?php echo esc_html__( 'Edit', 'modulix' ); ?></button>
		</div>
		<div id="elementor-global-widget-locked-unlink" class="elementor-global-widget-locked-tool">
			<div class="elementor-global-widget-locked-tool-description"><?php echo esc_html__( 'Unlink from global', 'modulix' ); ?></div>
			<button class="elementor-button elementor-button-danger"><?php echo esc_html__( 'Unlink', 'modulix' ); ?></button>
		</div>
	</div>
	<div id="elementor-global-widget-loading" class="elementor-hidden">
		<i class="eicon-loading eicon-animation-spin" aria-hidden="true"></i>
		<span class="elementor-screen-only"><?php echo esc_html__( 'Loading', 'modulix' ); ?></span>
	</div>
</script>

<script type="text/template" id="tmpl-elementor-panel-global-widget-no-templates">
	<img src="<?php echo ELEMENTOR_ASSETS_URL . 'images/information.svg'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" alt="Elementor Information Nerd Icon" loading="lazy" />
	<div class="elementor-nerd-box-title elementor-panel-nerd-box-title"><?php echo esc_html__( 'Save Your First Global Widget', 'modulix' ); ?></div>
	<div class="elementor-nerd-box-message elementor-panel-nerd-box-message"><?php echo esc_html__( 'Save a widget as global, then add it to multiple areas. All areas will be editable from one single place.', 'modulix' ); ?></div>
</script>
