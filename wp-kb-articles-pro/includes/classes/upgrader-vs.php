<?php
/**
 * Upgrader (Version-Specific)
 *
 * @since 150113 First documented version.
 * @copyright WebSharks, Inc. <http://www.websharks-inc.com>
 * @license GNU General Public License, version 3
 */
namespace wp_kb_articles // Root namespace.
{
	if(!defined('WPINC')) // MUST have WordPress.
		exit('Do NOT access this file directly: '.basename(__FILE__));

	if(!class_exists('\\'.__NAMESPACE__.'\\upgrader_vs'))
	{
		/**
		 * Upgrader (Version-Specific)
		 *
		 * @since 150113 First documented version.
		 */
		class upgrader_vs extends abs_base
		{
			/**
			 * @var string Previous version.
			 *
			 * @since 150113 First documented version.
			 */
			protected $prev_version;

			/**
			 * Class constructor.
			 *
			 * @since 150113 First documented version.
			 *
			 * @param string $prev_version Version they are upgrading from.
			 */
			public function __construct($prev_version)
			{
				parent::__construct();

				$this->prev_version = (string)$prev_version;

				$this->run_handlers(); // Run upgrade(s).
			}

			/**
			 * Runs upgrade handlers in the proper order.
			 *
			 * @since 150113 First documented version.
			 */
			protected function run_handlers()
			{
				$this->from_lte_v150303();
				$this->from_lte_v150304();
			}

			/**
			 * Runs upgrade handler for this specific version.
			 *
			 * @since 150304 Improving GitHub processor.
			 */
			protected function from_lte_v150303()
			{
				if(version_compare($this->prev_version, '150303', '>'))
					return; // Not applicable.

				$options['github_processor_max_time']  = $this->plugin->default_options['github_processor_max_time'];
				$options['github_processor_max_limit'] = $this->plugin->default_options['github_processor_max_limit'];

				$this->plugin->options_save($options); // Update to the defaults.
			}

			/**
			 * Runs upgrade handler for this specific version.
			 *
			 * @since 150411 Improving search functionality.
			 */
			protected function from_lte_v150304()
			{
				if(version_compare($this->prev_version, '150304', '>'))
					return; // Not applicable.

				new installer(); // Reinstall; forcing table recreation.
				// ↑ This adds the new `index` table that we need below.

				$index = new index(); // Index class instance.
				$index->rebuild(); // Rebuild entire index.
			}
		}
	}
}
