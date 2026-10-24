<?php

namespace Modulix\Modules\LinkInBio\Classes\Render;
// Modulix: derived from Elementor Pro 4.0.4, modules/link-in-bio/classes/render/icons-below-cta-render.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Modules\LinkInBio\Classes\Render\Render_Base;

class Icons_Below_Cta_Render extends Render_Base {

	public function render(): void {
		$this->build_layout_render_attribute();
		?>
		<div <?php echo $this->widget->get_render_attribute_string( 'layout' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<div class="e-link-in-bio__content">

				<?php
				$this->render_identity_image();

				$this->render_bio();

				$this->render_ctas();

				$this->render_icons();

				?>

			</div>
			<div class="e-link-in-bio__bg">
				<div class="e-link-in-bio__bg-overlay"></div>
			</div>
		</div>
		<?php
	}
}
