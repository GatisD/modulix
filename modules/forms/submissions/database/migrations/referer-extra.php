<?php
namespace Modulix\Modules\Forms\Submissions\Database\Migrations;
// Modulix: derived from Elementor Pro 4.0.4, modules/forms/submissions/database/migrations/referer-extra.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

class Referer_Extra extends Base_Migration {
	public function run() {
		$max_index_length = static::MAX_INDEX_LENGTH;

		// phpcs:disable
		$this->wpdb->query("
			ALTER TABLE `{$this->query->get_table_submissions()}`
			ADD COLUMN `referer_title` varchar(300) null AFTER `referer`;
		");

		$this->wpdb->query("
			ALTER TABLE `{$this->query->get_table_submissions()}`
			ADD INDEX `referer_index` (`referer`({$max_index_length})),
			ADD INDEX `referer_title_index` (`referer_title`({$max_index_length}));
		");
		// phpcs:enable
	}
}
