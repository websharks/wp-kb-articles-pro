<?php
/**
 * GitHub Event Handler
 *
 * @since 150313 Adding GitHub event handler.
 * @copyright WebSharks, Inc. <http://www.websharks-inc.com>
 * @license GNU General Public License, version 3
 */
namespace wp_kb_articles // Root namespace.
{
	if(!defined('WPINC')) // MUST have WordPress.
		exit('Do NOT access this file directly: '.basename(__FILE__));

	if(!class_exists('\\'.__NAMESPACE__.'\\github_event'))
	{
		/**
		 * GitHub Event Handler
		 *
		 * @since 150313 Adding GitHub event handler.
		 */
		class github_event extends abs_base
		{
			/**
			 * @var \stdClass Event payload.
			 */
			protected $payload;

			/**
			 * @var string Event type.
			 */
			protected $event_type;

			/**
			 * @var github_api GitHub API class.
			 */
			protected $github_api;

			/**
			 * Class constructor.
			 *
			 * @since 150313 Adding GitHub event handler.
			 *
			 * @param string $event_key Secret event key.
			 */
			public function __construct($event_key)
			{
				parent::__construct();

				if(!$this->plugin->utils_env->doing_exit())
					return; // Stop; invalid context.

				if($event_key !== $this->plugin->utils_github->event_key())
					return; // Unauthenticated; ignore.

				if(strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST'
				   || !($raw_post_data = trim(file_get_contents('php://input')))
				) return; // Nothing to do in this case.

				if(!is_object($this->payload = json_decode($raw_post_data)))
					return; // Nothing to do in this case.

				if(empty($_SERVER['HTTP_X_GITHUB_EVENT']) // Incoming header.
				   || !($this->event_type = trim(strtolower((string)$_SERVER['HTTP_X_GITHUB_EVENT'])))
				) return; // Nothing to do in this case.

				$this->prep_current_user();
				$this->prep_wp_filters();
				$this->maybe_process();
			}

			/**
			 * Prep current user.
			 *
			 * @since 150313 Adding GitHub event handler.
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
			 * @since 150313 Adding GitHub event handler.
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
			 * GitHub processor.
			 *
			 * @since 150313 Adding GitHub event handler.
			 */
			protected function maybe_process()
			{
				if(!$this->plugin->utils_github->enabled_configured())
					return; // Not enabled; or not configured properly.

				# For the configured repository and branch?

				if(empty($this->payload->repository->owner->name) || !is_string($this->payload->repository->owner->name)
				   || strcasecmp($this->payload->repository->owner->name, $this->plugin->options['github_mirror_owner']) !== 0
				) return; // Not a matching repository owner.

				if(empty($this->payload->repository->name) || !is_string($this->payload->repository->name)
				   || strcasecmp($this->payload->repository->name, $this->plugin->options['github_mirror_repo']) !== 0
				) return; // Not a matching repository name.

				if(empty($this->payload->ref) || !is_string($this->payload->ref)
				   || strcasecmp($this->payload->ref, 'refs/heads/'.$this->plugin->options['github_mirror_branch']) !== 0
				) return; // Not a matching repository branch.

				# Instance of GitHub API; for operations below.

				$this->github_api = new github_api(
					array(
						'owner'    => $this->plugin->options['github_mirror_owner'],
						'repo'     => $this->plugin->options['github_mirror_repo'],
						'branch'   => $this->plugin->options['github_mirror_branch'],

						'username' => $this->plugin->options['github_mirror_username'],
						'password' => $this->plugin->options['github_mirror_password'],
						'api_key'  => $this->plugin->options['github_mirror_api_key'],
					));
				# Maybe process event-driven operations below.

				switch($this->event_type) // Based on the event type.
				{
					case 'push': // Handle one-commit, one-file push events.

						if(empty($this->payload->commits) || !is_array($this->payload->commits))
							break; // Stop here; no commits.

						if(count($this->payload->commits) !== 1 || empty($this->payload->commits[0]))
							break; // Only handle one-commit push events.

						$commit = $this->payload->commits[0]; // First and only commit.

						if(empty($commit->modified) || !is_array($commit->modified))
							break; // Stop here; no file modified in this commit.

						if(count($commit->modified) !== 1 || empty($commit->modified[0]))
							break; // Only handle one-commit, one-file push events.

						$this->maybe_process_file($commit->modified[0]);

						break; // Break switch handler.
				}
			}

			/**
			 * File process; if applicable.
			 *
			 * @since 150313 Adding GitHub event handler.
			 *
			 * @param string $path GitHub file path; relative to repo root.
			 *
			 * @throws \exception If invalid parameters are passed to this routine.
			 * @throws \exception If there is any failure to acquire a particular article.
			 */
			protected function maybe_process_file($path)
			{
				if(!($path = trim((string)$path))) // Must have path.
					throw new \exception(__('Missing path.', $this->plugin->text_domain));

				if(!($article = $this->github_api->retrieve_article($path)))
					throw new \exception(__('Article retrieval failure.', $this->plugin->text_domain));

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