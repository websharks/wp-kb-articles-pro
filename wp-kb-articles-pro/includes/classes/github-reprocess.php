<?php
/**
 * GitHub Reprocessor
 *
 * @since 150302 Adding GitHub reprocessor.
 * @copyright WebSharks, Inc. <http://www.websharks-inc.com>
 * @license GNU General Public License, version 3
 */
namespace wp_kb_articles // Root namespace.
{
	if(!defined('WPINC')) // MUST have WordPress.
		exit('Do NOT access this file directly: '.basename(__FILE__));

	if(!class_exists('\\'.__NAMESPACE__.'\\github_reprocess'))
	{
		/**
		 * GitHub Reprocessor
		 *
		 * @since 150302 Adding GitHub reprocessor.
		 */
		class github_reprocess extends abs_base
		{
			/**
			 * @var integer Post (article) ID.
			 *
			 * @since 150302 Adding GitHub reprocessor.
			 */
			protected $post_id;

			/**
			 * @var boolean Force reprocessing?
			 *
			 * @since 150302 Adding GitHub reprocessor.
			 */
			protected $force;

			/**
			 * Class constructor.
			 *
			 * @since 150302 Adding GitHub reprocessor.
			 *
			 * @param integer $post_id A specific post (article) ID.
			 * @param boolean $force Force reprocessing? i.e., even if the sha is the same?
			 */
			public function __construct($post_id, $force = TRUE)
			{
				parent::__construct();

				$this->post_id = (integer)$post_id;
				$this->force   = (boolean)$force;

				if(!$this->plugin->utils_env->doing_ajax())
					if(!$this->plugin->utils_env->doing_cron())
						if(!$this->plugin->utils_env->doing_redirect())
							return; // Stop; invalid context.

				$this->prep_current_user();
				$this->prep_wp_filters();
				$this->maybe_reprocess();
			}

			/**
			 * Prep current user.
			 *
			 * @since 150302 Adding GitHub reprocessor.
			 */
			protected function prep_current_user()
			{
				if(!($admins = get_users(array('role' => 'administrator', 'fields' => array('ID'), 'number' => 1))))
					throw new \exception(__('Unable to find an administrator.', $this->plugin->text_domain));

				wp_set_current_user($admins[0]->ID); // Set current user.
			}

			/**
			 * Prep WordPress filters.
			 *
			 * @since 150302 Adding GitHub reprocessor.
			 */
			protected function prep_wp_filters()
			{
				remove_all_filters('content_save_pre');
				remove_all_filters('pre_post_content');

				remove_all_filters('excerpt_save_pre');
				remove_all_filters('pre_post_excerpt');

				kses_remove_filters(); // After setting current user.
			}

			/**
			 * GitHub reprocess.
			 *
			 * @since 150302 Adding GitHub reprocessor.
			 */
			protected function maybe_reprocess()
			{
				if(!$this->plugin->utils_github->enabled_configured())
					return; // Not enabled; or not configured properly.

				if(!($post = get_post($this->post_id)))
					return; // Not possible; post missing.

				if($post->post_type !== $this->plugin->post_type)
					return; // Not possible; not an article.

				if(!($path = $this->plugin->utils_github->get_path($post->ID)))
					return; // Not possible; no GitHub path.

				if(!($sha = $this->plugin->utils_github->get_sha($post->ID)))
					return; // Not possible; no GitHub sha.

				$github_api = new github_api(
					array(
						'owner'    => $this->plugin->options['github_mirror_owner'],
						'repo'     => $this->plugin->options['github_mirror_repo'],
						'branch'   => $this->plugin->options['github_mirror_branch'],

						'username' => $this->plugin->options['github_mirror_username'],
						'password' => $this->plugin->options['github_mirror_password'],
						'api_key'  => $this->plugin->options['github_mirror_api_key'],
					));
				if(!($article = $github_api->retrieve_article($path)))
					return; // Not possible; no longer exists.

				if(!$this->force && $sha === $article['sha'])
					return; // No change; not necesssary.

				$github_mirror = new github_mirror(
					array_merge($article['headers'], array(
						'path' => $path,
						'sha'  => $article['sha'],
						'body' => $article['body'],
					)));
			}
		}
	}
}