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
				$this->from_v150113();
			}

			/**
			 * Runs upgrade handler for this specific version.
			 *
			 * @since 150117 Adding support for `github-issue:`.
			 */
			protected function from_v150113()
			{
				if(version_compare($this->prev_version, '150113', '>'))
					return; // Not applicable.

				if(!($options = get_option(__NAMESPACE__.'_options')))
					return; // Not applicable.

				if(!isset($options['github_markdown_parse']))
					return; // Not applicable.

				$this->plugin->options_save(array('github_markdown_parse_enable' => $options['github_markdown_parse']));
			}
		}
	}
}