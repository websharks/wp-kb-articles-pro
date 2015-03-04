<?php
/**
 * Menu Page Actions
 *
 * @since 150113 First documented version.
 * @copyright WebSharks, Inc. <http://www.websharks-inc.com>
 * @license GNU General Public License, version 3
 */
namespace wp_kb_articles // Root namespace.
{
	if(!defined('WPINC')) // MUST have WordPress.
		exit('Do NOT access this file directly: '.basename(__FILE__));

	if(!class_exists('\\'.__NAMESPACE__.'\\menu_page_actions'))
	{
		/**
		 * Menu Page Actions
		 *
		 * @since 150113 First documented version.
		 */
		class menu_page_actions extends abs_base
		{
			/**
			 * @var array Valid actions.
			 *
			 * @since 150113 First documented version.
			 */
			protected $valid_actions;

			/**
			 * Class constructor.
			 *
			 * @since 150113 First documented version.
			 */
			public function __construct()
			{
				parent::__construct();

				$this->valid_actions = array(
					'save_options',
					'set_template_type',
					'restore_default_options',

					'dismiss_notice',

					'import',
					'export',

					'github_reprocess',
					'github_processor_via_ajax',
					'github_connectivity_test_via_ajax',
				);
				$this->maybe_handle();
			}

			/**
			 * Action handler.
			 *
			 * @since 150113 First documented version.
			 */
			protected function maybe_handle()
			{
				if(!is_admin())
					return; // Not applicable.

				if(empty($_REQUEST[__NAMESPACE__]))
					return; // Not applicable.

				if(!$this->plugin->utils_url->has_valid_nonce())
					return; // Unauthenticated; ignore.

				foreach((array)$_REQUEST[__NAMESPACE__] as $_action => $_request_args)
					if($_action && in_array($_action, $this->valid_actions, TRUE))
						$this->{$_action}($this->plugin->utils_string->trim_strip_deep($_request_args));
				unset($_action, $_request_args); // Housekeeping.
			}

			/**
			 * Saves options.
			 *
			 * @since 150113 First documented version.
			 *
			 * @param mixed $request_args Input argument(s).
			 */
			protected function save_options($request_args)
			{
				$request_args = (array)$request_args;

				if(!current_user_can($this->plugin->cap))
					return; // Unauthenticated; ignore.

				$this->plugin->options_save($request_args);

				$notice_markup = // Notice regarding options having been updated successfully.
					sprintf(__('%1$s&trade; options updated successfully.', $this->plugin->text_domain), esc_html($this->plugin->name));
				$this->plugin->enqueue_user_notice($notice_markup, array('transient' => TRUE));

				wp_redirect($this->plugin->utils_url->options_updated()).exit();
			}

			/**
			 * Sets template type/mode.
			 *
			 * @since 150113 First documented version.
			 *
			 * @param mixed $request_args Input argument(s).
			 */
			protected function set_template_type($request_args)
			{
				$template_type = (string)$request_args;

				if(!current_user_can($this->plugin->cap))
					return; // Unauthenticated; ignore.

				$this->plugin->options_save(compact('template_type'));

				$notice_markup = // Notice regarding options having been updated successfully.

					sprintf(__('Template mode updated to: <code>%2$s</code>.', $this->plugin->text_domain),
					        esc_html($this->plugin->name), $template_type === 'a' ? __('advanced', $this->plugin->text_domain) : __('simple', $this->plugin->text_domain)).

					' '.($template_type === 'a' // Provide an additional note; to help explain what just occured in this scenario.
						? 'A new set of templates has been loaded below. This mode uses advanced PHP-based templates. Recommended for advanced customization.</i>'
						: 'A new set of templates has been loaded below. This mode uses simple shortcode templates. Easiest to work with <i class="fa fa-smile-o"></i>');

				$this->plugin->enqueue_user_notice($notice_markup, array('transient' => TRUE));

				wp_redirect($this->plugin->utils_url->template_type_updated()).exit();
			}

			/**
			 * Restores defaults options.
			 *
			 * @since 150113 First documented version.
			 *
			 * @param mixed $request_args Input argument(s).
			 */
			protected function restore_default_options($request_args)
			{
				$request_args = NULL; // Not used here.

				if(!current_user_can($this->plugin->cap))
					return; // Unauthenticated; ignore.

				delete_option(__NAMESPACE__.'_options');
				$this->plugin->options = $this->plugin->default_options;

				$notice_markup = // Notice regarding options having been retored successfully.
					sprintf(__('%1$s&trade; default options restored successfully.', $this->plugin->text_domain), esc_html($this->plugin->name));
				$this->plugin->enqueue_user_notice($notice_markup, array('transient' => TRUE));

				wp_redirect($this->plugin->utils_url->default_options_restored()).exit();
			}

			/**
			 * Dismisses a persistent notice.
			 *
			 * @since 150113 First documented version.
			 *
			 * @param mixed $request_args Input argument(s).
			 */
			protected function dismiss_notice($request_args)
			{
				$request_args = (array)$request_args;

				if(empty($request_args['key']))
					return; // Not possible.

				if(!current_user_can($this->plugin->cap))
					return; // Unauthenticated; ignore.

				$notices = get_option(__NAMESPACE__.'_notices');
				if(!is_array($notices)) $notices = array();

				unset($notices[$request_args['key']]);
				update_option(__NAMESPACE__.'_notices', $notices);

				wp_redirect($this->plugin->utils_url->notice_dismissed()).exit();
			}

			/**
			 * Runs a specific import type.
			 *
			 * @since 150113 First documented version.
			 *
			 * @param mixed $request_args Input argument(s).
			 */
			protected function import($request_args)
			{
				$request_args = (array)$request_args;

				if(empty($request_args['type']) || !is_string($request_args['type']))
					return; // Missing and/or invalid import type.

				if(!in_array($request_args['type'], array('ops'), TRUE))
					return; // Invalid import type.

				if(!class_exists($class = '\\'.__NAMESPACE__.'\\import_'.$request_args['type']))
					return; // Invalid import type.

				if(!current_user_can($this->plugin->cap))
					return; // Unauthenticated; ignore.

				if(!empty($_FILES[__NAMESPACE__]['tmp_name']['import']['data_file']))
					$request_args['data_file'] = $_FILES[__NAMESPACE__]['tmp_name']['import']['data_file'];

				$importer = new $class($request_args); // Instantiate.
			}

			/**
			 * Runs a specific export type.
			 *
			 * @since 150113 First documented version.
			 *
			 * @param mixed $request_args Input argument(s).
			 */
			protected function export($request_args)
			{
				$request_args = (array)$request_args;

				if(empty($request_args['type']) || !is_string($request_args['type']))
					return; // Missing and/or invalid import type.

				if(!in_array($request_args['type'], array('ops'), TRUE))
					return; // Invalid import type.

				if(!class_exists($class = '\\'.__NAMESPACE__.'\\export_'.$request_args['type']))
					return; // Invalid import type.

				if(!current_user_can($this->plugin->cap))
					return; // Unauthenticated; ignore.

				$exporter = new $class($request_args); // Instantiate.
			}

			/**
			 * GitHub reprocess.
			 *
			 * @since 150302 Adding GitHub reprocessor.
			 *
			 * @param mixed $request_args Input argument(s).
			 */
			protected function github_reprocess($request_args)
			{
				$post_id = (integer)$request_args;

				define('DONOTCACHEPAGE', TRUE);
				define('ZENCACHE_ALLOWED', FALSE);

				if(!current_user_can($this->plugin->cap))
					return; // Unauthenticated; ignore.

				if(!current_user_can('edit_post', $post_id))
					return; // Unauthenticated; ignore.

				if(!($post = get_post($post_id)))
					return; // Post missing.

				$this->plugin->utils_env->doing_redirect(TRUE);

				nocache_headers(); // Disallow browser cache.

				new github_reprocess($post->ID); // Reprocess.
				$post = get_post($post_id); // After updates.

				$notice_markup = // Construct confirmation notice markup.
					'<i class="fa fa-github fa-3x" style="float:left; margin:0 .25em 0 0;"></i>'. // Float this to the left side.
					sprintf(__('KB Article ID: <code>%1$s</code> has been synced with GitHub.', $this->plugin->text_domain), esc_html($post->ID)).'<br />'.
					'<span style="opacity:0.5;"><i class="fa fa-level-up fa-rotate-90" style="margin:0 .25em 0 1em;"></i> Title: <em>'.sprintf(__('"%1$s"', $this->plugin->text_domain), esc_html($post->post_title)).'</em></span>';

				$this->plugin->enqueue_user_notice($notice_markup, array('transient' => TRUE));

				wp_redirect($this->plugin->utils_url->github_reprocessed()).exit();
			}

			/**
			 * GitHub processor via AJAX.
			 *
			 * @since 150302 Adding GitHub connectivity tests.
			 *
			 * @param mixed $request_args Input argument(s).
			 */
			protected function github_processor_via_ajax($request_args)
			{
				$request_args = NULL; // Not used here.

				define('DONOTCACHEPAGE', TRUE);
				define('ZENCACHE_ALLOWED', FALSE);

				if(!current_user_can($this->plugin->cap))
					return; // Unauthenticated; ignore.

				$this->plugin->utils_env->doing_ajax(TRUE);

				status_header(200); // Return response.
				nocache_headers(); // Disallow browser cache.
				header('Content-Type: text/plain; charset=UTF-8');

				new github_processor(); // Run one time-limited process.

				exit(__('GitHub processing complete!', $this->plugin->text_domain));
			}

			/**
			 * Tests GitHub connectivity.
			 *
			 * @since 150302 Adding GitHub connectivity tests.
			 *
			 * @param mixed $request_args Input argument(s).
			 */
			protected function github_connectivity_test_via_ajax($request_args)
			{
				$request_args = (array)$request_args;

				define('DONOTCACHEPAGE', TRUE);
				define('ZENCACHE_ALLOWED', FALSE);

				if(!current_user_can($this->plugin->cap))
					return; // Unauthenticated; ignore.

				$this->plugin->utils_env->doing_ajax(TRUE);

				status_header(200); // Return response.
				nocache_headers(); // Disallow browser cache.
				header('Content-Type: text/plain; charset=UTF-8');

				if(empty($request_args['owner']) || empty($request_args['repo']) || empty($request_args['branch'])
				   || (empty($request_args['api_key']) && (empty($request_args['username']) && empty($request_args['password'])))
				) exit (__('Test failed. Missing one or more config. options.', $this->plugin->text_domain));

				$github_api = new github_api(
					array(
						'owner'    => $request_args['owner'],
						'repo'     => $request_args['repo'],
						'branch'   => $request_args['branch'],

						'username' => !empty($request_args['username']) ? $request_args['username'] : '',
						'password' => !empty($request_args['password']) ? $request_args['password'] : '',
						'api_key'  => !empty($request_args['api_key']) ? $request_args['api_key'] : '',
					));
				if($github_api->test_connectivity())
					exit(__('Success! Your GitHub configuration looks good.', $this->plugin->text_domain));
				exit(__('Test failed. Please check your GitHub configuration options.', $this->plugin->text_domain));
			}
		}
	}
}