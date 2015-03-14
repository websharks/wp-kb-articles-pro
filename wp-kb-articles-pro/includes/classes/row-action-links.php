<?php
/**
 * Article Row Actions
 *
 * @since 150302 Adding post row actions.
 * @copyright WebSharks, Inc. <http://www.websharks-inc.com>
 * @license GNU General Public License, version 3
 */
namespace wp_kb_articles // Root namespace.
{
	if(!defined('WPINC')) // MUST have WordPress.
		exit('Do NOT access this file directly: '.basename(__FILE__));

	if(!class_exists('\\'.__NAMESPACE__.'\\row_action_links'))
	{
		/**
		 * Article Row Actions
		 *
		 * @since 150302 Adding post row actions.
		 */
		class row_action_links extends abs_base
		{
			/**
			 * Class constructor.
			 *
			 * @since 150302 Adding post row actions.
			 */
			public function __construct()
			{
				parent::__construct();
			}

			/**
			 * Filters the row actions.
			 *
			 * @since 150302 Adding post row actions.
			 *
			 * @param array    $actions Current actions.
			 * @param \WP_Post $post Current post.
			 *
			 * @return array New row actions after having been filtered.
			 */
			public function filter(array $actions, \WP_Post $post)
			{
				if($post->post_type !== $this->plugin->post_type)
					return $actions; // Not applicable.

				if(!current_user_can('edit_post', $post->ID))
					return $actions; // Not applicable.

				if(!$this->plugin->utils_github->enabled_configured())
					return $actions; // Not applicable.

				if(!$this->plugin->utils_github->get_path($post->ID))
					return $actions; // Not applicable.

				$actions[__NAMESPACE__.'_github_reprocess']
					= '<a href="'.esc_attr($this->plugin->utils_url->github_reprocess($post->ID)).'">'.
					  __('<i class="fa fa-github"></i> Sync', $this->plugin->text_domain).
					  '</a>';
				$actions[__NAMESPACE__.'_github_edit']
					= '<a href="'.esc_attr($this->plugin->utils_github->repo_edit_url($post->ID)).'" target="_blank">'.
					  __('<i class="fa fa-github"></i> Edit', $this->plugin->text_domain).
					  '</a>';
				return $actions; // Filtered actions.
			}
		}
	}
}