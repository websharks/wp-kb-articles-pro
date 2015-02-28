<?php
/**
 * GitHub Processor
 *
 * @since 150113 First documented version.
 * @copyright WebSharks, Inc. <http://www.websharks-inc.com>
 * @license GNU General Public License, version 3
 */
namespace wp_kb_articles // Root namespace.
{
	if(!defined('WPINC')) // MUST have WordPress.
		exit('Do NOT access this file directly: '.basename(__FILE__));

	if(!class_exists('\\'.__NAMESPACE__.'\\github_processor'))
	{
		/**
		 * GitHub Processor
		 *
		 * @since 150113 First documented version.
		 */
		class github_processor extends abs_base
		{
			/**
			 * @var integer Start time.
			 *
			 * @since 150113 First documented version.
			 */
			protected $start_time;

			/**
			 * @var integer Max time (in seconds).
			 *
			 * @since 150113 First documented version.
			 */
			protected $max_time;

			/**
			 * @var integer Delay (in milliseconds).
			 *
			 * @since 150113 First documented version.
			 */
			protected $delay;

			/**
			 * @var integer Max entries to process.
			 *
			 * @since 150113 First documented version.
			 */
			protected $max_limit;

			/**
			 * @var integer Processed file counter.
			 *
			 * @since 150113 First documented version.
			 */
			protected $processed_trees_blobs_counter;

			/**
			 * @var github_api GitHub API instance.
			 *
			 * @since 150113 First documented version.
			 */
			protected $github_api;

			/**
			 * @var string Last tree (or sub-tree).
			 *
			 * @since 150228 Improving GitHub API recursion.
			 */
			protected $last_tree;

			/**
			 * @var string Last directory/file path.
			 *
			 * @since 150228 Improving GitHub API recursion.
			 */
			protected $last_path;

			/**
			 * @var boolean Fast-forwarding?
			 *
			 * @since 150228 Improving GitHub API recursion.
			 */
			protected $fast_forwarding;

			/**
			 * Class constructor.
			 *
			 * @since 150113 First documented version.
			 *
			 * @param integer|null $max_time Max time (in seconds).
			 *
			 *    This cannot be less than `10` seconds.
			 *    This cannot be greater than `300` seconds.
			 *
			 *    * A default value is taken from the plugin options.
			 *
			 * @param integer|null $delay Delay (in milliseconds).
			 *
			 *    This cannot be less than `0` milliseconds.
			 *    This (converted to seconds) cannot be greater than `$max_time` - `5`.
			 *
			 *    * A default value is taken from the plugin options.
			 *
			 * @param integer|null $max_limit Max files to process.
			 *
			 *    This cannot be less than `1`.
			 *    This cannot be greater than `1000` (filterable).
			 *
			 *    * A default value is taken from the plugin options.
			 */
			public function __construct($max_time = NULL, $delay = NULL, $max_limit = NULL)
			{
				parent::__construct();

				$this->start_time = time(); // Start time.

				if(isset($max_time)) // Argument is set?
					$this->max_time = (integer)$max_time; // This takes precedence.
				else $this->max_time = (integer)$this->plugin->options['github_processor_max_time'];

				if($this->max_time < 10) $this->max_time = 10;
				if($this->max_time > 300) $this->max_time = 300;

				if(isset($delay)) // Argument is set?
					$this->delay = (integer)$delay; // This takes precedence.
				else $this->delay = (integer)$this->plugin->options['github_processor_delay'];

				if($this->delay < 0) $this->delay = 0;
				if($this->delay && $this->delay / 1000 > $this->max_time - 5)
					$this->delay = 250; // Cannot be greater than max time - 5 seconds.

				if(isset($max_limit)) // Argument is set?
					$this->max_limit = (integer)$max_limit; // This takes precedence.
				else $this->max_limit = (integer)$this->plugin->options['github_processor_max_limit'];

				if($this->max_limit < 1) $this->max_limit = 1;
				$upper_max_limit = (integer)apply_filters(__CLASS__.'_upper_max_limit', 1000);
				if($this->max_limit > $upper_max_limit) $this->max_limit = $upper_max_limit;

				$this->processed_trees_blobs_counter = 0; // Initialize; zero for now.
				$this->github_api                    = NULL; // Initialize the API reference.
				$this->last_tree                     = $this->plugin->options['github_processor_last_tree'];
				$this->last_path                     = $this->plugin->options['github_processor_last_path'];
				$this->fast_forwarding               = $this->last_tree && $this->last_path;

				$this->prep_cron_job();
				$this->prep_current_user();
				$this->prep_wp_filters();
				$this->maybe_process();
			}

			/**
			 * Prep CRON job.
			 *
			 * @since 150113 First documented version.
			 */
			protected function prep_cron_job()
			{
				ignore_user_abort(TRUE);

				@set_time_limit($this->max_time); // Max time only (first).
				// Doing this first in case the times below exceed an upper limit.
				// i.e. hosts may prevent this from being set higher than `$max_time`.

				// The following may not work, but we can try :-)
				if($this->delay) // Allow some extra time for the delay?
					@set_time_limit(min(300, ceil($this->max_time + ($this->delay / 1000) + 30)));
				else @set_time_limit(min(300, $this->max_time + 30));
			}

			/**
			 * Prep current user.
			 *
			 * @since 150113 First documented version.
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
			 * @since 150113 First documented version.
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
			 * @since 150113 First documented version.
			 */
			protected function maybe_process()
			{
				if(!$this->plugin->utils_github->enabled_configured())
					return; // Not enabled; or not configured properly.

				$this->github_api = new github_api(
					array(
						'owner'    => $this->plugin->options['github_mirror_owner'],
						'repo'     => $this->plugin->options['github_mirror_repo'],
						'branch'   => $this->plugin->options['github_mirror_branch'],

						'username' => $this->plugin->options['github_mirror_username'],
						'password' => $this->plugin->options['github_mirror_password'],
						'api_key'  => $this->plugin->options['github_mirror_api_key'],
					));
				if(($root_trees_blobs = $this->github_api->retrieve_article_trees_blobs()))
					$this->maybe_process_trees_blobs('___root___', $root_trees_blobs);
			}

			/**
			 * Processes trees/blobs recursively.
			 *
			 * @since 150228 Improving GitHub API recursion.
			 *
			 * @param string $tree_path The current tree (or sub-tree) path.
			 * @param array  $trees_blobs An array representing the current tree (or sub-tree).
			 *
			 * @throws \exception If invalid parameters are passed to this routine.
			 */
			protected function maybe_process_trees_blobs($tree_path, array $trees_blobs)
			{
				if(!($tree_path = trim((string)$tree_path))) // Must have.
					throw new \exception(__('Missing tree path.', $this->plugin->text_domain));

				$this->maybe_update_last_tree($tree_path); // Update.

				$total_paths  = count($trees_blobs); // Total paths.
				$path_counter = 0; // Initialize path counter; needed below.

				foreach($trees_blobs as $_path => $_tree_blob)
				{
					$path_counter++; // Bump this counter each time.

					if($this->fast_forwarding && $tree_path === $this->last_tree && $_path === $this->last_path)
						$this->fast_forwarding = FALSE; // Where we left off; stop fast-forwarding.

					if($_tree_blob['type'] === 'tree') // Recursion occurs here; i.e., query sub-trees.
					{
						if(($_sub_trees_blobs = $this->github_api->retrieve_article_trees_blobs($_tree_blob['sha'])))
							$this->maybe_process_trees_blobs($_path, $_sub_trees_blobs);
						$this->processed_trees_blobs_counter++; // Bump the counter.

						$this->maybe_update_last_tree($tree_path); // Update.
					}
					else // Blob; i.e., a file that we might need to process.
						$this->maybe_process_file($_path, $_tree_blob);

					$this->maybe_update_last_path($_path); // Update.

					if($this->processed_trees_blobs_counter >= $this->max_limit)
						break; // Reached limit; all done for now.

					$_last_root_path = // Is this the last root path?
						$tree_path === '___root___' && $path_counter >= $total_paths;

					if($_last_root_path) $this->fast_forwarding = FALSE;
					if($_last_root_path) $this->maybe_update_last_tree('');
					if($_last_root_path) $this->maybe_update_last_path('');

					if($this->is_out_of_time()) break; // Out of time.

					if(!$_last_root_path && $this->is_delay_out_of_time())
						break; // Out of time.
				}
				unset($_path, $_tree_blob, $_sub_trees_blobs); // Housekeeping.
			}

			/**
			 * File processor; if applicable.
			 *
			 * @since 150113 First documented version.
			 *
			 * @param string $path GitHub file path; relative to repo root.
			 * @param array  $file File data from GitHub API tree call.
			 *
			 * @throws \exception If invalid parameters are passed to this routine.
			 * @throws \exception If there is any failure to acquire a particular article.
			 */
			protected function maybe_process_file($path, array $file)
			{
				print_r($path."\n");
				var_dump($this->fast_forwarding);
				print_r($file);

				if($this->fast_forwarding)
					return; // Nothing to do.

				if(!($path = trim((string)$path))) // Must have path.
					throw new \exception(__('Missing path.', $this->plugin->text_domain));

				if(empty($file['sha'])) // Must have this too.
					throw new \exception(__('Missing SHA1.', $this->plugin->text_domain));

				$post_id = $this->plugin->utils_github->path_post_id($path);

				if(!$post_id) // Article does not exist yet?
				{
					if(!($article = $this->github_api->retrieve_article($file['sha'])))
						throw new \exception(__('Article retrieval failure.', $this->plugin->text_domain));

					$github_mirror = new github_mirror(
						array_merge($article['headers'], array(
							'path' => $path,
							'sha'  => $file['sha'],
							'body' => $article['body'],
						)));
					$this->processed_trees_blobs_counter++; // Bump the counter.
				}
				else if($this->plugin->utils_github->get_sha($post_id) !== $file['sha'])
				{
					if(!($article = $this->github_api->retrieve_article($file['sha'])))
						throw new \exception(__('Article retrieval failure.', $this->plugin->text_domain));

					$github_mirror = new github_mirror(
						array_merge($article['headers'], array(
							'path' => $path,
							'sha'  => $file['sha'],
							'body' => $article['body'],
						)));
					$this->processed_trees_blobs_counter++; // Bump the counter.
				}
			}

			/**
			 * Updates last tree.
			 *
			 * @since 150228 Improving GitHub API recursion.
			 *
			 * @param string $tree The last tree.
			 */
			protected function maybe_update_last_tree($tree)
			{
				if($this->fast_forwarding)
					return; // Nothing to do.

				$this->last_tree = trim((string)$tree);
				$this->plugin->options_quick_save(array('github_processor_last_tree' => $this->last_tree));
			}

			/**
			 * Updates last directory/file path.
			 *
			 * @since 150228 Improving GitHub API recursion.
			 *
			 * @param string $path The last directory/file path.
			 */
			protected function maybe_update_last_path($path)
			{
				if($this->fast_forwarding)
					return; // Nothing to do.

				$this->last_path = trim((string)$path);
				$this->plugin->options_quick_save(array('github_processor_last_path' => $this->last_path));
			}

			/**
			 * Out of time yet?
			 *
			 * @since 150113 First documented version.
			 *
			 * @return boolean TRUE if out of time.
			 */
			protected function is_out_of_time()
			{
				if((time() - $this->start_time) >= ($this->max_time - 5))
					return TRUE; // Out of time.

				return FALSE; // Let's keep mailing!
			}

			/**
			 * Out of time after a possible delay?
			 *
			 * @since 150113 First documented version.
			 *
			 * @return boolean TRUE if out of time.
			 */
			protected function is_delay_out_of_time()
			{
				if(!$this->delay) // No delay?
					return FALSE; // Nothing to do.

				if($this->fast_forwarding)
					return FALSE; // Nothing to do.

				usleep($this->delay * 1000); // Delay.

				return $this->is_out_of_time();
			}
		}
	}
}