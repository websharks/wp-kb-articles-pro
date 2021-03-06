<?php
/**
 * Environment Utilities
 *
 * @since 150113 First documented version.
 * @copyright WebSharks, Inc. <http://www.websharks-inc.com>
 * @license GNU General Public License, version 3
 */
namespace wp_kb_articles // Root namespace.
{
	if(!defined('WPINC')) // MUST have WordPress.
		exit('Do NOT access this file directly.');

	if(!class_exists('\\'.__NAMESPACE__.'\\utils_env'))
	{
		/**
		 * Environment Utilities
		 *
		 * @since 150113 First documented version.
		 */
		class utils_env extends abs_base
		{
			/**
			 * Class constructor.
			 *
			 * @since 150113 First documented version.
			 */
			public function __construct()
			{
				parent::__construct();
			}

			/**
			 * Current request is for a pro version preview?
			 *
			 * @since 150113 First documented version.
			 *
			 * @return boolean `TRUE` if the current request is for a pro preview.
			 */
			public function is_pro_preview()
			{
				if(!is_null($is = &$this->static_key(__FUNCTION__)))
					return $is; // Cached this already.

				if(!$this->is_menu_page(__NAMESPACE__.'*'))
					return ($is = FALSE);

				return ($is = !empty($_REQUEST[__NAMESPACE__.'_pro_preview']));
			}

			/**
			 * Current `$GLOBALS['pagenow']`.
			 *
			 * @since 150113 First documented version.
			 *
			 * @return string Current `$GLOBALS['pagenow']`.
			 */
			public function current_pagenow()
			{
				if(!is_null($pagenow = &$this->static_key(__FUNCTION__)))
					return $pagenow; // Cached this already.

				return ($pagenow = !empty($GLOBALS['pagenow']) ? (string)$GLOBALS['pagenow'] : '');
			}

			/**
			 * Current admin menu page.
			 *
			 * @since 150113 First documented version.
			 *
			 * @return string Current admin menu page.
			 */
			public function current_menu_page()
			{
				if(!is_null($page = &$this->static_key(__FUNCTION__)))
					return $page; // Cached this already.

				if(!is_admin()) return ($page = '');

				$page = !empty($_REQUEST['page'])
					? trim(stripslashes((string)$_REQUEST['page']))
					: $this->current_pagenow();

				return $page; // Current menu page.
			}

			/**
			 * Checks if current page is a menu page.
			 *
			 * @since 150113 First documented version.
			 *
			 * @param string $page_to_check A specific page to check (optional).
			 *    If empty, this returns `TRUE` for any admin menu page.
			 *
			 *    `*` wildcard characters are supported in the page to check.
			 *       Also note, the check is caSe insensitive.
			 *
			 * @return boolean TRUE if current page is a menu page.
			 *    Pass `$page_to_check` to check a specific page.
			 */
			public function is_menu_page($page_to_check = '')
			{
				$page_to_check = (string)$page_to_check;

				if(!is_null($is = &$this->static_key(__FUNCTION__, $page_to_check)))
					return $is; // Cached this already.

				if(!is_admin()) // Not admin area?
					return ($is = FALSE); // Nope!

				if(!($current_page = $this->current_menu_page()))
					return ($is = FALSE); // Not a menu page.

				if(!$page_to_check) // Any menu page?
					return ($is = TRUE); // Yep, it is!

				$page_to_check_regex = '/^'.preg_replace(array('/\\\\\*/', '/\\\\\^/'), array('.*?', '[^_]*?'), preg_quote($page_to_check, '/')).'$/i';

				return ($is = (boolean)preg_match($page_to_check_regex, $current_page));
			}

			/**
			 * Maxmizes available memory.
			 *
			 * @since 150113 First documented version.
			 */
			public function maximize_memory()
			{
				if(is_admin()) // In an admin area?
					@ini_set('memory_limit', // Maximize memory.
					         apply_filters('admin_memory_limit', WP_MAX_MEMORY_LIMIT));
				else @ini_set('memory_limit', WP_MAX_MEMORY_LIMIT);
			}

			/**
			 * Prepares for output delivery.
			 *
			 * @since 150113 First documented version.
			 */
			public function prep_for_output()
			{
				@set_time_limit(0);

				@ini_set('zlib.output_compression', 0);
				if(function_exists('apache_setenv'))
					@apache_setenv('no-gzip', '1');

				while(@ob_end_clean()) ;
			}

			/**
			 * Prepares for large output delivery.
			 *
			 * @since 150113 First documented version.
			 */
			public function prep_for_large_output()
			{
				$this->maximize_memory();
				$this->prep_for_output();
			}

			/**
			 * Max allowed file upload size.
			 *
			 * @since 150113 First documented version.
			 *
			 * @return float A floating point number.
			 */
			public function max_upload_size()
			{
				if(!is_null($max = &$this->static_key(__FUNCTION__)))
					return $max; // Already cached this.

				$limits = array(PHP_INT_MAX); // Initialize.

				if(($max_upload_size = ini_get('upload_max_filesize')))
					$limits[] = $this->plugin->utils_fs->abbr_bytes($max_upload_size);

				if(($post_max_size = ini_get('post_max_size')))
					$limits[] = $this->plugin->utils_fs->abbr_bytes($post_max_size);

				if(($memory_limit = ini_get('memory_limit')))
					$limits[] = $this->plugin->utils_fs->abbr_bytes($memory_limit);

				return ($max = min($limits));
			}

			/**
			 * Doing AJAX request?
			 *
			 * @since 150302 Adding AJAX check.
			 *
			 * @param boolean|null $doing Pass this to set the value.
			 *
			 * @return boolean `TRUE` if doing an AJAX request.
			 */
			public function doing_ajax($doing = NULL)
			{
				if(isset($doing)) // Setting the value?
					$GLOBALS[__NAMESPACE__.'_doing_ajax'] = (boolean)$doing;

				if((defined('DOING_AJAX') && DOING_AJAX)
				   || !empty($GLOBALS[__NAMESPACE__.'_doing_ajax'])
				) return TRUE; // Doing an AJAX request.

				return FALSE; // Default response.
			}

			/**
			 * Doing a CRON job?
			 *
			 * @since 150302 Adding CRON check.
			 *
			 * @param boolean|null $doing Pass this to set the value.
			 *
			 * @return boolean `TRUE` if doing a CRON job.
			 */
			public function doing_cron($doing = NULL)
			{
				if(isset($doing)) // Setting the value?
					$GLOBALS[__NAMESPACE__.'_doing_cron'] = (boolean)$doing;

				if((defined('DOING_CRON') && DOING_CRON)
				   || !empty($GLOBALS[__NAMESPACE__.'_doing_cron'])
				) return TRUE; // Doing a CRON job.

				return FALSE; // Default response.
			}

			/**
			 * Doing a redirect?
			 *
			 * @since 150302 Adding redirect check.
			 *
			 * @param boolean|null $doing Pass this to set the value.
			 *
			 * @return boolean `TRUE` if doing a redirection.
			 */
			public function doing_redirect($doing = NULL)
			{
				if(isset($doing)) // Setting the value?
					$GLOBALS[__NAMESPACE__.'_doing_redirect'] = (boolean)$doing;

				if((defined('DOING_REDIRECT') && DOING_REDIRECT)
				   || !empty($GLOBALS[__NAMESPACE__.'_doing_redirect'])
				) return TRUE; // Doing a redirection.

				return FALSE; // Default response.
			}

			/**
			 * Doing an exit?
			 *
			 * @since 150313 Adding exit check.
			 *
			 * @param boolean|null $doing Pass this to set the value.
			 *
			 * @return boolean `TRUE` if doing an exit.
			 */
			public function doing_exit($doing = NULL)
			{
				if(isset($doing)) // Setting the value?
					$GLOBALS[__NAMESPACE__.'_doing_exit'] = (boolean)$doing;

				if((defined('DOING_EXIT') && DOING_EXIT)
				   || !empty($GLOBALS[__NAMESPACE__.'_doing_exit'])
				   || $this->doing_ajax() || $this->doing_cron() || $this->doing_redirect()
				) return TRUE; // Doing an exit.

				return FALSE; // Default response.
			}

			/**
			 * Max execution time.
			 *
			 * @since 150303 Adding max execution time.
			 *
			 * @return integer Max execution time; in seconds.
			 *
			 * @note This ignores an inifinite timeout, and it ignores anything less than 30.
			 *    In such a case, a default value of 30 is returned; i.e., we expect to have a limit, and it must be >= 30.
			 */
			public function max_execution_time()
			{
				$max_execution_time = (integer)@ini_get('max_execution_time');

				return $max_execution_time >= 30 ? $max_execution_time : 30;
			}
		}
	}
}