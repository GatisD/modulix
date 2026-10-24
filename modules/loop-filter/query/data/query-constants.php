<?php

namespace Modulix\Modules\LoopFilter\Query\Data;
// Modulix: derived from Elementor Pro 4.0.4, modules/loop-filter/query/data/query-constants.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

class Query_Constants {
	public const DATA = [
		'AND' => [
			'separator' => [
				'decoded' => '+',
				'from-browser' => ' ',
				'encoded' => '%2B',
			],
			'operator' => 'AND',
			'relation' => 'AND',
		],
		'OR' => [
			'separator' => [
				'decoded' => '~',
				'from-browser' => '~',
				'encoded' => '%7C',
			],
			'operator' => 'IN',
			'relation' => 'OR',
		],
		'NOT' => [
			'separator' => [
				'decoded' => '!',
				'from-browser' => '!',
				'encoded' => '%21',
			],
			'operator' => 'NOT IN',
			'relation' => 'AND',
		],
		'DISABLED' => [
			'separator' => [
				'decoded' => '',
				'from-browser' => '',
				'encoded' => '',
			],
			'operator' => 'AND',
			'relation' => 'AND',
		],
	];
}
