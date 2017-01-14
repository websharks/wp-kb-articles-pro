<?php
/**
 * Plugin Class
 *
 * @since 150113 First documented version.
 * @copyright WebSharks, Inc. <http://www.websharks-inc.com>
 * @license GNU General Public License, version 3
 */
namespace wp_kb_articles
{
	if(!defined('WPINC')) // MUST have WordPress.
		exit('Do NOT access this file directly.');

	require_once dirname(__FILE__).'/includes/classes/abs-base.php';

	if(!defined('WP_KB_ARTICLE_ROLES_ALL_CAPS'))
		/**
		 * @var string Back compat. constant with original release.
		 */
		define('WP_KB_ARTICLE_ROLES_ALL_CAPS', 'administrator');

	if(!defined('WP_KB_ARTICLE_ROLES_EDIT_CAPS'))
		/**
		 * @var string Back compat. constant with original release.
		 */
		define('WP_KB_ARTICLE_ROLES_EDIT_CAPS', 'administrator,editor,author');

	if(!class_exists('\\'.__NAMESPACE__.'\\plugin'))
	{
		/**
		 * Plugin Class
		 *
		 * @property-read utils_array           $utils_array
		 * @property-read utils_date            $utils_date
		 * @property-read utils_db              $utils_db
		 * @property-read utils_enc             $utils_enc
		 * @property-read utils_env             $utils_env
		 * @property-read utils_fs              $utils_fs
		 * @property-read utils_github          $utils_github
		 * @property-read utils_i18n            $utils_i18n
		 * @property-read utils_ip              $utils_ip
		 * @property-read utils_log             $utils_log
		 * @property-read utils_markup          $utils_markup
		 * @property-read utils_math            $utils_math
		 * @property-read utils_php             $utils_php
		 * @property-read utils_post            $utils_post
		 * @property-read utils_string          $utils_string
		 * @property-read utils_url             $utils_url
		 * @property-read utils_user            $utils_user
		 * @property-read utils_yaml            $utils_yaml
		 *
		 * @since 150113 First documented version.
		 */
		class plugin extends abs_base
		{
			/*
			 * Public Properties
			 */

			/**
			 * Identifies pro version.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var boolean `TRUE` for pro version.
			 */
			public $is_pro = TRUE;

			/**
			 * Plugin name.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var string Plugin name.
			 */
			public $name = 'WP KB Articles';

			/**
			 * Plugin name (abbreviated).
			 *
			 * @since 150113 First documented version.
			 *
			 * @var string Plugin name (abbreviated).
			 */
			public $short_name = 'WPKBA';

			/**
			 * Transient prefix.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var string 8-character transient prefix.
			 */
			public $transient_prefix = 'wpkbart_';

			/**
			 * Rewrite prefix.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var string Rewrite prefix.
			 */
			public $rewrite_prefix = 'kb-';

			/**
			 * Query var prefix.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var string Query var prefix.
			 */
			public $qv_prefix = 'kb_';

			/**
			 * Query var keys.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var array Query var keys.
			 */
			public $qv_keys = array(
				'page',
				'author',
				'category',
				'tag',
				'q',
			);
			/**
			 * Post type w/ underscores.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var string Post type w/ underscores.
			 */
			public $post_type = 'kb_article';

			/**
			 * Post type w/ dashes.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var string Post type w/ dashes.
			 */
			public $post_type_slug = 'kb-article';

			/**
			 * Site name.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var string Site name.
			 */
			public $site_name = 'WPKBArticles.com';

			/**
			 * Plugin product page URL.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var string Plugin product page URL.
			 */
			public $product_url = 'http://wpkbarticles.com';

			/**
			 * Used by the plugin's uninstall handler.
			 *
			 * @since 150113 Adding uninstall handler.
			 *
			 * @var boolean Defined by constructor.
			 */
			public $enable_hooks;

			/**
			 * Text domain for translations; based on `__NAMESPACE__`.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var string Defined by class constructor; for translations.
			 */
			public $text_domain;

			/**
			 * Plugin slug; based on `__NAMESPACE__`.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var string Defined by constructor.
			 */
			public $slug;

			/**
			 * Stub `__FILE__` location.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var string Defined by class constructor.
			 */
			public $file;

			/**
			 * Version string in YYMMDD[+build] format.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var string Current version of the software.
			 */
			public $version = '150411';

			/*
			 * Public Properties (Defined @ Setup)
			 */

			/**
			 * An array of all default option values.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var array Default options array.
			 */
			public $default_options;

			/**
			 * Configured option values.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var array Options configured by site owner.
			 */
			public $options;

			/**
			 * General capability requirement.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var string Capability required to administer.
			 *    i.e. to use any aspect of the plugin, including the configuration
			 *    of any/all plugin options and/or advanced settings.
			 */
			public $cap; // Most important cap.

			/**
			 * Auto-recompile capability requirement.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var string Capability required to auto-recompile.
			 *    i.e. to see notices regarding automatic recompilations
			 *    following an upgrade the plugin files/version.
			 */
			public $auto_recompile_cap;

			/**
			 * Upgrade capability requirement.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var string Capability required to upgrade.
			 *    i.e. the ability to run any sort of plugin upgrader.
			 */
			public $upgrade_cap;

			/**
			 * Uninstall capability requirement.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var string Capability required to uninstall.
			 *    i.e. the ability to deactivate and even delete the plugin.
			 */
			public $uninstall_cap;

			/**
			 * Roles to receive all KB article caps.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var array Roles to receive all KB article caps.
			 */
			public $roles_recieving_all_caps = array();

			/**
			 * Roles to receive KB article edit caps.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var array Roles to receive KB article edit caps.
			 */
			public $roles_recieving_edit_caps = array();

			/*
			 * Public Properties (Defined by Various Hooks)
			 */

			/**
			 * An array of all menu page hooks.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var array An array of all menu page hooks.
			 */
			public $menu_page_hooks = array();

			/*
			 * Plugin Constructor
			 */

			/**
			 * Plugin constructor.
			 *
			 * @param boolean $enable_hooks Defaults to a TRUE value.
			 *    If FALSE, setup runs but without adding any hooks.
			 *
			 * @since 150113 First documented version.
			 */
			public function __construct($enable_hooks = TRUE)
			{
				/*
				 * Parent constructor.
				 */
				$GLOBALS[__NAMESPACE__] = $this; // Global ref.
				parent::__construct(); // Run parent constructor.

				/*
				 * Initialize properties.
				 */
				$this->enable_hooks = (boolean)$enable_hooks;
				$this->text_domain  = $this->slug = str_replace('_', '-', __NAMESPACE__);
				$this->file         = preg_replace('/\.inc\.php$/', '.php', __FILE__);

				/*
				 * Initialize autoloader.
				 */
				require_once dirname(__FILE__).'/includes/classes/autoloader.php';
				new autoloader(); // Register the plugin's autoloader.

				/*
				 * With or without hooks?
				 */
				if(!$this->enable_hooks) // Without hooks?
					return; // Stop here; construct without hooks.

				/*
				 * Setup primary plugin hooks.
				 */
				add_action('after_setup_theme', array($this, 'setup'));
				register_activation_hook($this->file, array($this, 'activate'));
				register_deactivation_hook($this->file, array($this, 'deactivate'));
			}

			/*
			 * Setup Routine(s)
			 */

			/**
			 * Setup the plugin.
			 *
			 * @since 150113 First documented version.
			 */
			public function setup()
			{
				/*
				 * Setup already?
				 */
				if(!is_null($setup = &$this->cache_key(__FUNCTION__)))
					return; // Already setup. Once only!
				$setup = TRUE; // Once only please.
				/*
				 * Fire pre-setup hooks.
				 */
				if($this->enable_hooks) // Hooks enabled?
					do_action('before__'.__METHOD__, get_defined_vars());
				/*
				 * Load the plugin's text domain for translations.
				 */
				load_plugin_textdomain($this->text_domain); // Translations.
				/*
				 * Setup class properties related to authentication/capabilities.
				 */
				$this->cap                = apply_filters(__METHOD__.'_cap', 'activate_plugins');
				$this->auto_recompile_cap = apply_filters(__METHOD__.'_auto_recompile_cap', 'activate_plugins');
				$this->upgrade_cap        = apply_filters(__METHOD__.'_upgrade_cap', 'update_plugins');
				$this->uninstall_cap      = apply_filters(__METHOD__.'_uninstall_cap', 'delete_plugins');

				$max_execution_time = $this->utils_env->max_execution_time(); // Needed below.
				/*
				 * Setup the array of all plugin options.
				 */
				$this->default_options = array(
					/* Core/systematic option keys. */

					'version'                                                          => $this->version,
					'crons_setup'                                                      => '0', // `0` or timestamp.

					/* Related to data safeguards. */

					'uninstall_safeguards_enable'                                      => '1', // `0|1`; safeguards on?

					/* Related to GitHub integration. */

					'github_processing_enable'                                         => '0', // `0|1`; enable?

					'github_mirror_owner'                                              => '', // Repo owner.
					'github_mirror_repo'                                               => '', // Repo owner.
					'github_mirror_branch'                                             => '', // Branch.
					'github_mirror_username'                                           => '', // Username.
					'github_mirror_password'                                           => '', // Password.
					'github_mirror_api_key'                                            => '', // API key.
					'github_mirror_author'                                             => '', // User login|ID.

					'github_link_images_enable'                                        => '1', // `0|1`; enable?
					'github_issue_feedback_enable'                                     => '1', // `0|1`; enable?
					'github_markdown_parse_enable'                                     => '1', // `0|1`; enable?
					'github_readonly_content_enable'                                   => '1', // `0|1`; enable?

					'github_processor_max_time'                                        => (string)min(900, $max_execution_time), // In seconds.
					'github_processor_max_limit'                                       => (string)floor((min(900, $max_execution_time) / 15) * 16), // Total files.
					// ↑ This cannot exceed 1250 every 15 minutes; i.e., no more than 5000 per hour. See: <https://developer.github.com/v3/#rate-limiting>
					//       Since there are tree calls, and also blob calls, this needs to be well within that maximum at all times.
					//       The current maximum is `(900 / 15) * 16 = 960` every 15 minutes. That's a max of 3840 per hour.
					'github_processor_delay'                                           => '250', // In milliseconds.
					'github_processor_button_enable'                                   => '0', // `0|1`; enable?

					'github_processor_last_tree'                                       => '', // Last tree (or sub-tree).
					'github_processor_last_path'                                       => '', // Last directory/file path.

					/* Related to search indexing. */

					'index_rebuild_button_enable'                                      => '1', // `0|1`; enable?

					/* Related to TOC generation. */

					'hids_generation_enable'                                           => '1', // `0|1`; enable?
					'toc_generation_enable'                                            => '1', // `0|1`; enable?

					/* Related to IP tracking. */

					'prioritize_remote_addr'                                           => '0', // `0|1`; enable?
					'geo_location_tracking_enable'                                     => '0', // `0|1`; enable?

					/* Related to menu pages; i.e. logo display. */

					'menu_pages_logo_icon_enable'                                      => '0', // `0|1`; display?

					/* Article index-related options. */

					'sc_articles_list_index_post_id'                                   => '',

					/* Template-related config. options. */

					'template_type'                                                    => 's', // `a|s`.

					# Advanced HTML, PHP-based templates for the site.

					'template__type_a__site__articles__list_search_box___php'          => '', // HTML/PHP code.
					'template__type_a__site__articles__list_search_box___js___php'     => '', // HTML/PHP code.
					'template__type_a__site__articles__list_search_box___css'          => '', // CSS code.

					'template__type_a__site__articles__list___php'                     => '', // HTML/PHP code.
					'template__type_a__site__articles__list___js___php'                => '', // HTML/PHP code.
					'template__type_a__site__articles__list___css'                     => '', // CSS code.

					'template__type_a__site__articles__toc___php'                      => '', // HTML/PHP code.
					'template__type_a__site__articles__toc___js___php'                 => '', // HTML/PHP code.
					'template__type_a__site__articles__toc___css'                      => '', // CSS code.

					'template__type_a__site__articles__footer___php'                   => '', // HTML/PHP code.
					'template__type_a__site__articles__footer___js___php'              => '', // HTML/PHP code.
					'template__type_a__site__articles__footer___css'                   => '', // CSS code.

					# Simple snippet-based templates for the site.

					'template__type_s__site__articles__snippet__list_search_box___php' => '', // HTML code.
					'template__type_s__site__articles__snippet__list_article___php'    => '', // HTML code.
					'template__type_s__site__articles__snippet__toc___php'             => '', // HTML code.
					'template__type_s__site__articles__snippet__footer___php'          => '', // HTML code.

				); // Default options are merged with those defined by the site owner.
				$this->default_options = apply_filters(__METHOD__.'__default_options', $this->default_options); // Allow filters.
				$this->options         = is_array($this->options = get_option(__NAMESPACE__.'_options')) ? $this->options : array();

				$this->options = array_merge($this->default_options, $this->options); // Merge into default options.
				$this->options = array_intersect_key($this->options, $this->default_options); // Valid keys only.
				$this->options = apply_filters(__METHOD__.'__options', $this->options); // Allow filters.
				$this->options = array_map('strval', $this->options); // Force string values.

				if(WP_KB_ARTICLE_ROLES_ALL_CAPS) // Specific Roles?
					$this->roles_recieving_all_caps = // Convert these to an array.
						preg_split('/[\s;,]+/', WP_KB_ARTICLE_ROLES_ALL_CAPS, NULL, PREG_SPLIT_NO_EMPTY);

				if(WP_KB_ARTICLE_ROLES_EDIT_CAPS) // Specific Roles?
					$this->roles_recieving_edit_caps = // Convert these to an array.
						preg_split('/[\s;,]+/', WP_KB_ARTICLE_ROLES_EDIT_CAPS, NULL, PREG_SPLIT_NO_EMPTY);
				/*
				 * With or without hooks?
				 */
				if(!$this->enable_hooks) // Without hooks?
					return; // Stop here; setup without hooks.
				/*
				 * Setup all secondary plugin hooks.
				 */
				$_this = $this; // Referenced needed by closures.

				add_action('init', array($this, 'register_post_type'), -11, 0);
				add_action('init', array($this, 'register_rewrite_rules'), -11, 0);
				add_action('init', array($this, 'article_github_issue_redirect'), -10, 0);
				add_action('init', array($this, 'actions'), -10, 0);

				add_action('admin_init', array($this, 'check_version'), 10, 0);
				add_action('all_admin_notices', array($this, 'all_admin_notices'), 10, 0);

				add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'), 10, 0);
				add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'), 10, 0);

				add_action('admin_menu', array($this, 'add_menu_pages'), 10, 0);
				add_filter('set-screen-option', array($this, 'set_screen_option'), 10, 3);
				add_filter('plugin_action_links_'.plugin_basename($this->file), array($this, 'add_settings_link'), 10, 1);

				add_action('wp_print_scripts', array($this, 'enqueue_front_scripts'), 10, 0);
				add_action('wp_print_styles', array($this, 'enqueue_front_styles'), 10, 0);

				add_action('save_post_'.$this->post_type, array($this, 'save_article'), 10, 1);
				add_action('before_delete_post', array($this, 'delete_article'), 10, 1);

				add_action('set_object_terms', array($this, 'save_article_terms'), 10, 1);
				add_action('deleted_term_relationships', array($this, 'delete_article_terms'), 10, 1);

				add_filter('ezphp_exclude_post', array($this, 'maybe_exclude_article_from_ezphp'), 10, 2);

				add_filter('the_content', array($this, 'maybe_preserve_article_raw_html_content'), -PHP_INT_MAX, 1);
				add_filter('the_content', array($this, 'maybe_restore_article_raw_html_content'), PHP_INT_MAX - 1, 1);

				add_filter('get_the_excerpt', array($this, 'maybe_preserve_article_raw_html_excerpt'), -PHP_INT_MAX, 1);
				add_filter('get_the_excerpt', array($this, 'maybe_restore_article_raw_html_excerpt'), PHP_INT_MAX - 1, 1);

				add_filter('the_excerpt', array($this, 'maybe_preserve_article_raw_html_excerpt'), -PHP_INT_MAX, 1);
				add_filter('the_excerpt', array($this, 'maybe_restore_article_raw_html_excerpt'), PHP_INT_MAX - 1, 1);

				add_filter('the_content', array($this, 'article_headings'), PHP_INT_MAX, 1);
				add_filter('the_content', array($this, 'article_footer'), PHP_INT_MAX, 1);

				add_filter('author_link', array($this, 'sc_author_link'), 10, 3);
				add_filter('term_link', array($this, 'sc_term_link'), 10, 3);

				add_shortcode('kb_articles_list_search_box', array($this, 'sc_list_search_box'));
				add_shortcode('kb_articles_list', array($this, 'sc_list'));

				add_filter('post_row_actions', array($this, 'article_row_action_links'), 10, 2);
				add_action('edit_form_before_permalink', array($this, 'article_action_links_bp'), 10, 1);
				/*
				 * Setup CRON-related hooks.
				 */
				add_filter('cron_schedules', array($this, 'extend_cron_schedules'), 10, 1);

				if(substr($this->options['crons_setup'], -4) !== '-pro' || (integer)$this->options['crons_setup'] < 1382523750)
				{
					wp_clear_scheduled_hook('_cron_'.__NAMESPACE__.'_github_processor');
					wp_schedule_event(time() + 60, 'every15m', '_cron_'.__NAMESPACE__.'_github_processor');

					$this->options['crons_setup'] = time().'-pro'; // With `-pro` suffix.
					update_option(__NAMESPACE__.'_options', $this->options);
				}
				add_action('_cron_'.__NAMESPACE__.'_github_processor', array($this, 'github_processor'), 10);
				/*
				 * Fire setup completion hooks.
				 */
				do_action('after__'.__METHOD__, get_defined_vars());
				do_action(__METHOD__.'_complete', get_defined_vars());
			}

			/*
			 * Magic Methods
			 */

			/**
			 * Magic/overload property getter.
			 *
			 * @param string $property Property to get.
			 *
			 * @return mixed The value of `$this->___overload->{$property}`.
			 *
			 * @throws \exception If the `$___overload` property is undefined.
			 *
			 * @see http://php.net/manual/en/language.oop5.overloading.php
			 */
			public function __get($property)
			{
				$property          = (string)$property;
				$ns_class_property = '\\'.__NAMESPACE__.'\\'.$property;

				if(stripos($property, 'utils_') === 0 && class_exists($ns_class_property))
					if(!isset($this->___overload->{$property})) // Not defined yet?
						$this->___overload->{$property} = new $ns_class_property;

				return parent::__get($property);
			}

			/*
			 * Install-Related Methods
			 */

			/**
			 * First installation time.
			 *
			 * @since 150113 First documented version.
			 *
			 * @return integer UNIX timestamp.
			 */
			public function install_time()
			{
				return (integer)get_option(__NAMESPACE__.'_install_time');
			}

			/**
			 * Plugin activation hook.
			 *
			 * @since 150113 First documented version.
			 *
			 * @attaches-to {@link \register_activation_hook()}
			 */
			public function activate()
			{
				new installer(); // Installation handler.
			}

			/**
			 * Check current plugin version.
			 *
			 * @since 150113 First documented version.
			 *
			 * @attaches-to `admin_init` action.
			 */
			public function check_version()
			{
				if(version_compare($this->options['version'], $this->version, '>='))
					return; // Nothing to do; already @ latest version.

				new upgrader(); // Upgrade handler.
			}

			/*
			 * Uninstall-Related Methods
			 */

			/**
			 * Plugin deactivation hook.
			 *
			 * @since 150113 First documented version.
			 *
			 * @attaches-to {@link \register_deactivation_hook()}
			 */
			public function deactivate()
			{
				// Does nothing at this time.
			}

			/**
			 * Plugin uninstall handler.
			 *
			 * @since 150113 First documented version.
			 *
			 * @called-by {@link uninstall}
			 */
			public function uninstall()
			{
				new uninstaller(); // Uninstall handler.
			}

			/*
			 * Action-Related Methods
			 */

			/**
			 * Plugin action handler.
			 *
			 * @since 150113 First documented version.
			 *
			 * @attaches-to `init` action.
			 */
			public function actions()
			{
				if(empty($_REQUEST[__NAMESPACE__]))
				{
					$path        = $this->utils_url->current_path();
					$is_api_path = stripos($path, '/'.$this->slug.'/api/') === 0;

					if($is_api_path && preg_match('/^\/'.preg_quote($this->slug, '/').'\/api\/query(?:[\/?#]|$)/i', $path))
						$_REQUEST[__NAMESPACE__]['query_api'] = $_REQUEST;

					else if($is_api_path && preg_match('/^\/'.preg_quote($this->slug, '/').'\/api\/tax\-query(?:[\/?#]|$)/i', $path))
						$_REQUEST[__NAMESPACE__]['tax_query_api'] = $_REQUEST;
				}
				if(empty($_REQUEST[__NAMESPACE__]))
					return; // Nothing to do here.

				new actions(); // Handle action(s).
			}

			/*
			 * Option-Related Methods
			 */

			/**
			 * Saves new plugin options.
			 *
			 * @since 150227 Improving GitHub API Recursion.
			 *
			 * @param array $options An array of new plugin options.
			 */
			public function options_quick_save(array $options)
			{
				$this->options = array_merge($this->default_options, $this->options, $options);
				$this->options = array_intersect_key($this->options, $this->default_options);
				$this->options = array_map('strval', $this->options); // Force strings.

				update_option(__NAMESPACE__.'_options', $this->options); // DB update.
			}

			/**
			 * Saves new plugin options.
			 *
			 * @since 150113 First documented version.
			 *
			 * @param array $options An array of new plugin options.
			 */
			public function options_save(array $options)
			{
				$this->options = array_merge($this->default_options, $this->options, $options);
				$this->options = array_intersect_key($this->options, $this->default_options);
				$this->options = array_map('strval', $this->options); // Force strings.

				foreach($this->options as $_key => &$_value) if(strpos($_key, 'template__') === 0)
				{
					$_key_data             = template::option_key_data($_key);
					$_default_template     = new template($_key_data->file, $_key_data->type, TRUE);
					$_default_template_nws = preg_replace('/\s+/', '', $_default_template->file_contents());
					$_option_template_nws  = preg_replace('/\s+/', '', $_value);

					if($_option_template_nws === $_default_template_nws)
						$_value = ''; // Empty; it's a default value.
				}
				unset($_key, $_key_data, $_value, // Housekeeping.
					$_default_template, $_option_template_nws, $_default_template_nws);

				update_option(__NAMESPACE__.'_options', $this->options); // DB update.

				flush_rewrite_rules(); // Flush on save in case of changes.
			}

			/*
			 * Admin Menu-Page-Related Methods
			 */

			/**
			 * Adds CSS for administrative menu pages.
			 *
			 * @since 150113 First documented version.
			 *
			 * @attaches-to `admin_enqueue_scripts` action.
			 */
			public function enqueue_admin_styles()
			{
				if($this->utils_env->is_menu_page(__NAMESPACE__.'*'))
				{
					$deps = array('codemirror', 'font-awesome', 'sharkicons'); // Dependencies.

					wp_enqueue_style('codemirror', set_url_scheme('//cdnjs.cloudflare.com/ajax/libs/codemirror/4.7.0/codemirror.min.css'), array(), NULL, 'all');
					wp_enqueue_style('codemirror-fullscreen', set_url_scheme('//cdnjs.cloudflare.com/ajax/libs/codemirror/4.7.0/addon/display/fullscreen.min.css'), array('codemirror'), NULL, 'all');
					wp_enqueue_style('codemirror-ambiance-theme', set_url_scheme('//cdnjs.cloudflare.com/ajax/libs/codemirror/4.7.0/theme/ambiance.min.css'), array('codemirror'), NULL, 'all');

					wp_enqueue_style('font-awesome', set_url_scheme('//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css'), array(), NULL, 'all');
					wp_enqueue_style('sharkicons', $this->utils_url->to('/submodules/sharkicons/styles.min.css'), array(), NULL, 'all');

					wp_enqueue_style(__NAMESPACE__, $this->utils_url->to('/client-s/css/menu-pages.min.css'), $deps, $this->version, 'all');
				}
				else if(($this->utils_env->is_menu_page('edit.php') && isset($_REQUEST['post_type']) && $_REQUEST['post_type'] === $this->post_type)
				        || ($this->utils_env->is_menu_page('post.php') && isset($_REQUEST['post'], $_REQUEST['action'])
				            && $_REQUEST['action'] === 'edit' && get_post_type((integer)$_REQUEST['post']) === $this->post_type)
				) // If we are editing one or more posts, and if the the post type indicates it's a KB article.
				{
					$deps = array('font-awesome', 'sharkicons'); // Dependencies.

					wp_enqueue_style('font-awesome', set_url_scheme('//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css'), array(), NULL, 'all');
					wp_enqueue_style('sharkicons', $this->utils_url->to('/submodules/sharkicons/styles.min.css'), array(), NULL, 'all');

					wp_enqueue_style(__NAMESPACE__.'-edit', $this->utils_url->to('/client-s/css/post-type-edit.min.css'), $deps, $this->version, 'all');
				}
			}

			/**
			 * Adds JS for administrative menu pages.
			 *
			 * @since 150113 First documented version.
			 *
			 * @attaches-to `admin_enqueue_scripts` action.
			 */
			public function enqueue_admin_scripts()
			{
				if($this->utils_env->is_menu_page(__NAMESPACE__.'*'))
				{
					$deps = array('jquery', 'codemirror'); // Dependencies.

					wp_enqueue_script('codemirror', set_url_scheme('//cdnjs.cloudflare.com/ajax/libs/codemirror/4.7.0/codemirror.min.js'), array(), NULL, TRUE);
					wp_enqueue_script('codemirror-fullscreen', set_url_scheme('//cdnjs.cloudflare.com/ajax/libs/codemirror/4.7.0/addon/display/fullscreen.min.js'), array('codemirror'), NULL, TRUE);
					wp_enqueue_script('codemirror-matchbrackets', set_url_scheme('//cdnjs.cloudflare.com/ajax/libs/codemirror/4.7.0/addon/edit/matchbrackets.js'), array('codemirror'), NULL, TRUE);
					wp_enqueue_script('codemirror-htmlmixed', set_url_scheme('//cdnjs.cloudflare.com/ajax/libs/codemirror/4.7.0/mode/htmlmixed/htmlmixed.js'), array('codemirror'), NULL, TRUE);
					wp_enqueue_script('codemirror-xml', set_url_scheme('//cdnjs.cloudflare.com/ajax/libs/codemirror/4.7.0/mode/xml/xml.js'), array('codemirror'), NULL, TRUE);
					wp_enqueue_script('codemirror-javascript', set_url_scheme('//cdnjs.cloudflare.com/ajax/libs/codemirror/4.7.0/mode/javascript/javascript.js'), array('codemirror'), NULL, TRUE);
					wp_enqueue_script('codemirror-css', set_url_scheme('//cdnjs.cloudflare.com/ajax/libs/codemirror/4.7.0/mode/css/css.js'), array('codemirror'), NULL, TRUE);
					wp_enqueue_script('codemirror-clike', set_url_scheme('//cdnjs.cloudflare.com/ajax/libs/codemirror/4.7.0/mode/clike/clike.js'), array('codemirror'), NULL, TRUE);
					wp_enqueue_script('codemirror-php', set_url_scheme('//cdnjs.cloudflare.com/ajax/libs/codemirror/4.7.0/mode/php/php.js'), array('codemirror'), NULL, TRUE);

					wp_enqueue_script(__NAMESPACE__, $this->utils_url->to('/client-s/js/menu-pages.min.js'), $deps, $this->version, TRUE);

					wp_localize_script(__NAMESPACE__, __NAMESPACE__.'_vars', array(
						'pluginUrl'    => rtrim($this->utils_url->to('/'), '/'),
						'ajaxEndpoint' => rtrim($this->utils_url->page_nonce_only(), '/'),
					));
					wp_localize_script(__NAMESPACE__, __NAMESPACE__.'_i18n', array());
				}
				else if(($this->utils_env->is_menu_page('edit.php') && isset($_REQUEST['post_type']) && $_REQUEST['post_type'] === $this->post_type)
				        || ($this->utils_env->is_menu_page('post.php') && isset($_REQUEST['post'], $_REQUEST['action'])
				            && $_REQUEST['action'] === 'edit' && get_post_type((integer)$_REQUEST['post']) === $this->post_type)
				) // If we are editing one or more posts, and if the the post type indicates it's a KB article.
				{
					$deps = array('jquery'); // Dependencies.

					$is_edit_page         = $this->utils_env->is_menu_page('edit.php');
					$is_post_page         = !$is_edit_page && $this->utils_env->is_menu_page('post.php');
					$current_user_can_cap = current_user_can($this->cap);

					$github_enabled_configured        = $this->utils_github->enabled_configured();
					$github_processesor_button_enable = $github_enabled_configured && $this->options['github_processor_button_enable'] && $is_edit_page && $current_user_can_cap;
					$github_readonly_content_enable   = $github_enabled_configured && $this->options['github_readonly_content_enable'] && $is_post_page && $this->utils_github->get_path((integer)$_REQUEST['post']);
					$index_rebuild_button_enable      = $this->options['index_rebuild_button_enable'] && $is_edit_page && $current_user_can_cap;

					if($github_readonly_content_enable) // GitHub enabled/configured, and the content should be readonly?
						add_filter('user_can_richedit', '__return_false'); // Disable the visual editor.

					wp_enqueue_script(__NAMESPACE__.'-edit', $this->utils_url->to('/client-s/js/post-type-edit.min.js'), $deps, $this->version, TRUE);

					wp_localize_script(__NAMESPACE__.'-edit', __NAMESPACE__.'_edit_vars', array(
						'pluginUrl'                   => rtrim($this->utils_url->to('/'), '/'),
						'ajaxEndpoint'                => rtrim($this->utils_url->page_nonce_only(), '/'),

						'githubProcessorButtonEnable' => $github_processesor_button_enable,
						'githubReadonlyContentEnable' => $github_readonly_content_enable,

						'indexRebuildButtonEnable'    => $index_rebuild_button_enable,
					));
					wp_localize_script(__NAMESPACE__.'-edit', __NAMESPACE__.'_edit_i18n', array(
						'githubProcessorButtonText'         => sprintf(__('Run GitHub Processor', $this->text_domain)),
						'githubProcessorButtonTextComplete' => sprintf(__('GitHub Processing Complete (reloading...)', $this->text_domain)),
						'githubReadonlyContentEnabled'      => sprintf(__('<strong>%1$s:</strong> The content of this article is read-only to avoid edits in WordPress that would be overwritten by the underlying <a href="%2$s" target="_blank">GitHub Integration</a>.', $this->text_domain), esc_html($this->name), esc_attr($this->utils_url->main_menu_page_only())),

						'indexRebuildButtonText'            => sprintf(__('Rebuild Search Index', $this->text_domain)),
						'indexRebuildButtonTextComplete'    => sprintf(__('Search Index Rebuilt (reloading...)', $this->text_domain)),
					));
				}
			}

			/**
			 * Creates admin menu pages.
			 *
			 * @since 150113 First documented version.
			 *
			 * @attaches-to `admin_menu` action.
			 */
			public function add_menu_pages()
			{
				if(!current_user_can($this->cap))
					return; // Do not add.

				$divider = // Dividing line used by various menu items below.
					'<span style="display:block; padding:0; margin:0 0 12px 0; height:1px; line-height:1px; background:#CCCCCC; opacity:0.1;"></span>';

				$child_branch_indent = // Each child branch uses the following UTF-8 char `꜖`; <http://unicode-table.com/en/A716/>.
					'<span style="display:inline-block; margin-left:.5em; position:relative; top:-.2em; left:-.2em; font-weight:normal; opacity:0.2;">&#42774;</span> ';

				$current_menu_page = $this->utils_env->current_menu_page(); // Current menu page slug.

				/* ----------------------------------------- */

				$_menu_title                          = __('Config. Options', $this->text_domain);
				$_page_title                          = $this->name.'&trade; &#10609; '.__('Config. Options', $this->text_domain);
				$this->menu_page_hooks[__NAMESPACE__] = add_submenu_page('edit.php?post_type='.$this->post_type, $_page_title, $_menu_title, $this->cap, __NAMESPACE__, array($this, 'menu_page_options'));
				add_action('load-'.$this->menu_page_hooks[__NAMESPACE__], array($this, 'menu_page_options_screen'));

				$_menu_title                                           = // Visible on-demand only.
					'<small><em>'.$child_branch_indent.__('Import/Export', $this->text_domain).'</em></small>';
				$_page_title                                           = $this->name.'&trade; &#10609; '.__('Import/Export', $this->text_domain);
				$_menu_parent                                          = $current_menu_page === __NAMESPACE__.'_import_export' ? 'edit.php?post_type='.$this->post_type : NULL;
				$this->menu_page_hooks[__NAMESPACE__.'_import_export'] = add_submenu_page($_menu_parent, $_page_title, $_menu_title, $this->cap, __NAMESPACE__.'_import_export', array($this, 'menu_page_import_export'));
				add_action('load-'.$this->menu_page_hooks[__NAMESPACE__.'_import_export'], array($this, 'menu_page_import_export_screen'));

				$_menu_title                                            = // Visible on-demand only.
					'<small><em>'.$child_branch_indent.__('Site Templates', $this->text_domain).'</em></small>';
				$_page_title                                            = $this->name.'&trade; &#10609; '.__('Site Templates', $this->text_domain);
				$_menu_parent                                           = $current_menu_page === __NAMESPACE__.'_site_templates' ? 'edit.php?post_type='.$this->post_type : NULL;
				$this->menu_page_hooks[__NAMESPACE__.'_site_templates'] = add_submenu_page($_menu_parent, $_page_title, $_menu_title, $this->cap, __NAMESPACE__.'_site_templates', array($this, 'menu_page_site_templates'));
				add_action('load-'.$this->menu_page_hooks[__NAMESPACE__.'_site_templates'], array($this, 'menu_page_site_templates_screen'));

				unset($_menu_title, $_page_title, $_menu_parent); // Housekeeping.
			}

			/**
			 * Set plugin-related screen options.
			 *
			 * @since 150113 First documented version.
			 *
			 * @attaches-to `set-screen-option` filter.
			 *
			 * @param mixed|boolean $what_wp_says `FALSE` if not saving (default).
			 *    If we set this to any value besides `FALSE`, the option will be saved by WP.
			 *
			 * @param string        $option The option being checked; i.e. should we save this option?
			 *
			 * @param mixed         $value The current value for this option.
			 *
			 * @return mixed|boolean Returns `$value` for plugin-related options.
			 *    Other we simply return `$what_wp_says`.
			 */
			public function set_screen_option($what_wp_says, $option, $value)
			{
				if(strpos($option, __NAMESPACE__.'_') === 0)
					return $value; // Yes, save this.

				return $what_wp_says;
			}

			/**
			 * Menu page screen; for options.
			 *
			 * @since 150113 First documented version.
			 *
			 * @attaches-to `'load-'.$this->menu_page_hooks[__NAMESPACE__]` action.
			 *
			 * @see add_menu_pages()
			 */
			public function menu_page_options_screen()
			{
				$screen = get_current_screen();
				if(!($screen instanceof \WP_Screen))
					return; // Not possible.

				if(empty($this->menu_page_hooks[__NAMESPACE__])
				   || $screen->id !== $this->menu_page_hooks[__NAMESPACE__]
				) return; // Not applicable.

				return; // No screen for this page right now.
			}

			/**
			 * Menu page for options.
			 *
			 * @since 150113 First documented version.
			 *
			 * @see add_menu_pages()
			 */
			public function menu_page_options()
			{
				new menu_page('options');
			}

			/**
			 * Menu page screen; for import/export.
			 *
			 * @since 150113 First documented version.
			 *
			 * @attaches-to `'load-'.$this->menu_page_hooks[__NAMESPACE__.'_import_export']` action.
			 *
			 * @see add_menu_pages()
			 */
			public function menu_page_import_export_screen()
			{
				$screen = get_current_screen();
				if(!($screen instanceof \WP_Screen))
					return; // Not possible.

				if(empty($this->menu_page_hooks[__NAMESPACE__.'_import_export'])
				   || $screen->id !== $this->menu_page_hooks[__NAMESPACE__.'_import_export']
				) return; // Not applicable.

				return; // No screen for this page right now.
			}

			/**
			 * Menu page for import/export.
			 *
			 * @since 150113 First documented version.
			 *
			 * @see add_menu_pages()
			 */
			public function menu_page_import_export()
			{
				new menu_page('import_export');
			}

			/**
			 * Menu page screen; for site templates.
			 *
			 * @since 150113 First documented version.
			 *
			 * @attaches-to `'load-'.$this->menu_page_hooks[__NAMESPACE__.'_site_templates']` action.
			 *
			 * @see add_menu_pages()
			 */
			public function menu_page_site_templates_screen()
			{
				$screen = get_current_screen();
				if(!($screen instanceof \WP_Screen))
					return; // Not possible.

				if(empty($this->menu_page_hooks[__NAMESPACE__.'_site_templates'])
				   || $screen->id !== $this->menu_page_hooks[__NAMESPACE__.'_site_templates']
				) return; // Not applicable.

				return; // No screen for this page right now.
			}

			/**
			 * Menu page for site templates.
			 *
			 * @since 150113 First documented version.
			 *
			 * @see add_menu_pages()
			 */
			public function menu_page_site_templates()
			{
				new menu_page('site_templates');
			}

			/**
			 * Adds link(s) to plugin row on the WP plugins page.
			 *
			 * @since 150113 First documented version.
			 *
			 * @attaches-to `plugin_action_links_'.plugin_basename($this->file)` filter.
			 *
			 * @param array $links An array of the existing links provided by WordPress.
			 *
			 * @return array Revised array of links.
			 */
			public function add_settings_link(array $links)
			{
				$links[] = '<a href="'.esc_attr($this->utils_url->main_menu_page_only()).'">'.__('Settings', $this->text_domain).'</a><br/>';
				if(!$this->is_pro) $links[] = '<a href="'.esc_attr($this->utils_url->pro_preview()).'">'.__('Preview Pro Features', $this->text_domain).'</a>';
				if(!$this->is_pro) $links[] = '<a href="'.esc_attr($this->utils_url->product_page()).'" target="_blank">'.__('Upgrade', $this->text_domain).'</a>';

				return apply_filters(__METHOD__, $links, get_defined_vars());
			}

			/*
			 * Admin Notice/Error Related Methods
			 */

			/**
			 * Enqueue an administrative notice.
			 *
			 * @since 150113 First documented version.
			 *
			 * @param string $markup HTML markup containing the notice itself.
			 * @param array  $args An array of additional args; i.e. presentation/style.
			 */
			public function enqueue_notice($markup, array $args = array())
			{
				if(!($markup = trim((string)$markup)))
					return; // Nothing to do here.

				$default_args   = array(
					'markup'       => '',
					'requires_cap' => '',
					'for_user_id'  => 0,
					'for_page'     => '',
					'persistent'   => FALSE,
					'transient'    => FALSE,
					'push_to_top'  => FALSE,
					'type'         => 'notice',
				);
				$args['markup'] = (string)$markup; // + markup.
				$args           = array_merge($default_args, $args);
				$args           = array_intersect_key($args, $default_args);

				$args['requires_cap'] = trim((string)$args['requires_cap']);
				$args['requires_cap'] = $args['requires_cap'] // Force valid format.
					? strtolower(preg_replace('/\W/', '_', $args['requires_cap'])) : '';

				$args['for_user_id'] = (integer)$args['for_user_id'];
				$args['for_page']    = trim((string)$args['for_page']);

				$args['persistent']  = (boolean)$args['persistent'];
				$args['transient']   = (boolean)$args['transient'];
				$args['push_to_top'] = (boolean)$args['push_to_top'];

				if(!in_array($args['type'], array('notice', 'error'), TRUE))
					$args['type'] = 'notice'; // Use default type.

				ksort($args); // Sort args (by key) for key generation.
				$key = $this->utils_enc->hmac_sha256_sign(serialize($args));

				if(!is_array($notices = get_option(__NAMESPACE__.'_notices')))
					$notices = array(); // Force an array of notices.

				if($args['push_to_top']) // Push this notice to the top?
					$this->utils_array->unshift_assoc($notices, $key, $args);
				else $notices[$key] = $args; // Default behavior.

				update_option(__NAMESPACE__.'_notices', $notices);
			}

			/**
			 * Enqueue an administrative notice; for a particular user.
			 *
			 * @since 150113 First documented version.
			 *
			 * @param string $markup HTML markup. See {@link enqueue_notice()}.
			 * @param array  $args Additional args. See {@link enqueue_notice()}.
			 */
			public function enqueue_user_notice($markup, array $args = array())
			{
				if(!isset($args['for_user_id']))
					$args['for_user_id'] = get_current_user_id();

				$this->enqueue_notice($markup, $args);
			}

			/**
			 * Enqueue an administrative error.
			 *
			 * @since 150113 First documented version.
			 *
			 * @param string $markup HTML markup. See {@link enqueue_notice()}.
			 * @param array  $args Additional args. See {@link enqueue_notice()}.
			 */
			public function enqueue_error($markup, array $args = array())
			{
				$this->enqueue_notice($markup, array_merge($args, array('type' => 'error')));
			}

			/**
			 * Enqueue an administrative error; for a particular user.
			 *
			 * @since 150113 First documented version.
			 *
			 * @param string $markup HTML markup. See {@link enqueue_error()}.
			 * @param array  $args Additional args. See {@link enqueue_notice()}.
			 */
			public function enqueue_user_error($markup, array $args = array())
			{
				if(!isset($args['for_user_id']))
					$args['for_user_id'] = get_current_user_id();

				$this->enqueue_error($markup, $args);
			}

			/**
			 * Render admin notices; across all admin dashboard views.
			 *
			 * @since 150113 First documented version.
			 *
			 * @attaches-to `all_admin_notices` action.
			 */
			public function all_admin_notices()
			{
				if(!is_array($notices = get_option(__NAMESPACE__.'_notices')))
					update_option(__NAMESPACE__.'_notices', ($notices = array()));

				if(!$notices) return; // Nothing more to do in this case.

				$user_can_view_notices = current_user_can($this->cap);

				$original_notices = $notices; // Copy.

				foreach($notices as $_key => $_args)
				{
					$default_args = array(
						'markup'       => '',
						'requires_cap' => '',
						'for_user_id'  => 0,
						'for_page'     => '',
						'persistent'   => FALSE,
						'transient'    => FALSE,
						'push_to_top'  => FALSE,
						'type'         => 'notice',
					);
					$_args        = array_merge($default_args, $_args);
					$_args        = array_intersect_key($_args, $default_args);

					$_args['markup'] = trim((string)$_args['markup']);

					$_args['requires_cap'] = trim((string)$_args['requires_cap']);
					$_args['requires_cap'] = $_args['requires_cap'] // Force valid format.
						? strtolower(preg_replace('/\W/', '_', $_args['requires_cap'])) : '';

					$_args['for_user_id'] = (integer)$_args['for_user_id'];
					$_args['for_page']    = trim((string)$_args['for_page']);

					$_args['persistent']  = (boolean)$_args['persistent'];
					$_args['transient']   = (boolean)$_args['transient'];
					$_args['push_to_top'] = (boolean)$_args['push_to_top'];

					if(!in_array($_args['type'], array('notice', 'error'), TRUE))
						$_args['type'] = 'notice'; // Use default type.

					if($_args['transient']) // Transient; i.e. single pass only?
						unset($notices[$_key]); // Remove always in this case.

					if(!$user_can_view_notices) // Primary capability check.
						continue;  // Don't display to this user under any circumstance.

					if($_args['requires_cap'] && !current_user_can($_args['requires_cap']))
						continue; // Don't display to this user; lacks required cap.

					if($_args['for_user_id'] && get_current_user_id() !== $_args['for_user_id'])
						continue; // Don't display to this particular user ID.

					if($_args['for_page'] && !$this->utils_env->is_menu_page($_args['for_page']))
						continue; // Don't display on this page; i.e. pattern match failure.

					if($_args['markup']) // Only display non-empty notices.
					{
						if($_args['persistent']) // Need [dismiss] link?
						{
							$_dismiss_style = 'float: right;'.
							                  'margin: 0 0 0 15px;'.
							                  'display: inline-block;'.
							                  'text-decoration: none;'.
							                  'font-weight: bold;';
							$_dismiss_url   = $this->utils_url->dismiss_notice($_key);
							$_dismiss       = '<a href="'.esc_attr($_dismiss_url).'"'.
							                  '  style="'.esc_attr($_dismiss_style).'">'.
							                  '  '.__('dismiss &times;', $this->text_domain).
							                  '</a>';
						}
						else $_dismiss = ''; // Default value; n/a.

						$_classes = $this->slug.'-menu-page-area'; // Always.
						$_classes .= ' '.($_args['type'] === 'error' ? 'error' : 'updated');

						$_full_markup = // Put together the full markup; including other pieces.
							'<div class="'.esc_attr($_classes).'">'.
							'  '.$this->utils_string->p_wrap($_args['markup'], $_dismiss).
							'</div>';
						echo apply_filters(__METHOD__.'_notice', $_full_markup, get_defined_vars());
					}
					if(!$_args['persistent']) unset($notices[$_key]); // Once only; i.e. don't show again.
				}
				unset($_key, $_args, $_dismiss_style, $_dismiss_url, $_dismiss, $_classes, $_full_markup); // Housekeeping.

				if($original_notices !== $notices) update_option(__NAMESPACE__.'_notices', $notices);
			}

			/*
			 * Front-Side Scripts/Styles
			 */

			/**
			 * Enqueues front-side scripts.
			 *
			 * @since 150113 First documented version.
			 *
			 * @attaches-to `wp_print_scripts` hook.
			 */
			public function enqueue_front_scripts()
			{
				new front_scripts();
			}

			/**
			 * Enqueues front-side styles.
			 *
			 * @since 150113 First documented version.
			 *
			 * @attaches-to `wp_print_styles` hook.
			 */
			public function enqueue_front_styles()
			{
				new front_styles();
			}

			/*
			 * Article-Related Methods
			 */

			/**
			 * Handle article save actions.
			 *
			 * @since 150113 First documented version.
			 *
			 * @attaches-to `save_post_kb_article` hook.
			 *
			 * @param integer $post_id Post ID.
			 */
			public function save_article($post_id)
			{
				if(!($post_id = (integer)$post_id))
					return; // Not possible.

				if(!($post = get_post($post_id)))
					return; // Not possible.

				if($post->post_type !== $this->post_type)
					return; // Not applicable.

				$this->utils_post->update_popularity($post->ID, 0);

				$index = new index(); // Index class instance.
				$index->sync($post->ID, 'save'); // Save changes.
			}

			/**
			 * Handle article deletion actions.
			 *
			 * @since 150411 Improving searches.
			 *
			 * @attaches-to `before_delete_post` hook.
			 *
			 * @param integer $post_id Post ID.
			 */
			public function delete_article($post_id)
			{
				if(!($post_id = (integer)$post_id))
					return; // Not possible.

				if(!($post = get_post($post_id)))
					return; // Not possible.

				if($post->post_type !== $this->post_type)
					return; // Not applicable.

				$index = new index(); // Index class instance.
				$index->sync($post->ID, 'delete'); // Delete.
			}

			/**
			 * Handle article save terms changes.
			 *
			 * @since 150411 Improving searches.
			 *
			 * @attaches-to `set_object_terms` hook.
			 *
			 * @param integer $post_id Post ID.
			 */
			public function save_article_terms($post_id)
			{
				if(!($post_id = (integer)$post_id))
					return; // Not possible.

				if(!($post = get_post($post_id)))
					return; // Not possible.

				if($post->post_type !== $this->post_type)
					return; // Not applicable.

				$index = new index(); // Index class instance.
				$index->sync($post->ID, 'save'); // Save changes.
			}

			/**
			 * Handle article term deletions.
			 *
			 * @since 150411 Improving searches.
			 *
			 * @attaches-to `deleted_term_relationships` hook.
			 *
			 * @param integer $post_id Post ID.
			 */
			public function delete_article_terms($post_id)
			{
				if(!($post_id = (integer)$post_id))
					return; // Not possible.

				if(!($post = get_post($post_id)))
					return; // Not possible.

				if($post->post_type !== $this->post_type)
					return; // Not applicable.

				$index = new index(); // Index class instance.
				$index->sync($post->ID, 'save'); // Save changes.
			}

			/**
			 * Handle ezPHP exclusions.
			 *
			 * @since 150214 Enhancing content/excerpt filters.
			 *
			 * @attaches-to `ezphp_exclude_post` filter.
			 *
			 * @param boolean  $exclude Excluded?
			 * @param \WP_Post $post A WP Post object.
			 *
			 * @return boolean `TRUE` if post/article is excluded.
			 */
			public function maybe_exclude_article_from_ezphp($exclude, \WP_Post $post)
			{
				if($post->post_type !== $this->post_type)
					return $exclude; // Not applicable.

				return $this->utils_github->maybe_exclude_from_ezphp($exclude, $post);
			}

			/**
			 * Handle raw HTML content type.
			 *
			 * @since 150214 Enhancing content/excerpt filters.
			 *
			 * @attaches-to `the_content` filter.
			 *
			 * @param string $content The post/article content.
			 *
			 * @return string The post/article content.
			 */
			public function maybe_preserve_article_raw_html_content($content)
			{
				if(!$GLOBALS['post'] || $GLOBALS['post']->post_type !== $this->post_type)
					return $content; // Not applicable.

				return $this->utils_github->maybe_preserve_raw_html_content($content);
			}

			/**
			 * Handle raw HTML content type.
			 *
			 * @since 150214 Enhancing content/excerpt filters.
			 *
			 * @attaches-to `the_content` filter.
			 *
			 * @param string $content The post/article content.
			 *
			 * @return string The post/article content.
			 */
			public function maybe_restore_article_raw_html_content($content)
			{
				if(!$GLOBALS['post'] || $GLOBALS['post']->post_type !== $this->post_type)
					return $content; // Not applicable.

				return $this->utils_github->maybe_restore_raw_html_content($content);
			}

			/**
			 * Handle raw HTML content type.
			 *
			 * @since 150214 Enhancing content/excerpt filters.
			 *
			 * @attaches-to `get_the_excerpt` and `the_excerpt` filters.
			 *
			 * @param string $excerpt The post/article excerpt.
			 *
			 * @return string The post/article excerpt.
			 */
			public function maybe_preserve_article_raw_html_excerpt($excerpt)
			{
				if(!$GLOBALS['post'] || $GLOBALS['post']->post_type !== $this->post_type)
					return $excerpt; // Not applicable.

				return $this->utils_github->maybe_preserve_raw_html_excerpt($excerpt);
			}

			/**
			 * Handle raw HTML content type.
			 *
			 * @since 150214 Enhancing content/excerpt filters.
			 *
			 * @attaches-to `get_the_excerpt` and `the_excerpt` filters.
			 *
			 * @param string $excerpt The post/article excerpt.
			 *
			 * @return string The post/article excerpt.
			 */
			public function maybe_restore_article_raw_html_excerpt($excerpt)
			{
				if(!$GLOBALS['post'] || $GLOBALS['post']->post_type !== $this->post_type)
					return $excerpt; // Not applicable.

				return $this->utils_github->maybe_restore_raw_html_excerpt($excerpt);
			}

			/**
			 * Handle article headings/TOC.
			 *
			 * @since 150415 Enhancing TOC generation.
			 *
			 * @attaches-to `the_content` filter.
			 *
			 * @param string $content The content.
			 *
			 * @return string With heading IDs and TOC (if enabled).
			 */
			public function article_headings($content)
			{
				if(!$GLOBALS['post'] || $GLOBALS['post']->post_type !== $this->post_type)
					return $content; // Not applicable.

				$headings = new headings(); // Headings class instance.

				return $headings->filter($content); // With heading IDs.
			}

			/**
			 * Handle article footer.
			 *
			 * @since 150113 First documented version.
			 *
			 * @attaches-to `the_content` filter.
			 *
			 * @param string $content The content.
			 *
			 * @return string The original `$content` w/ possible footer appendage.
			 */
			public function article_footer($content)
			{
				if(!$GLOBALS['post'] || $GLOBALS['post']->post_type !== $this->post_type)
					return $content; // Not applicable.

				$footer = new footer(); // Footer class instance.

				return $footer->filter($content); // With footer.
			}

			/**
			 * Handles article row actions.
			 *
			 * @since 150302 Adding row actions.
			 *
			 * @attaches-to `post_row_actions` filter.
			 *
			 * @param array    $actions Current actions.
			 * @param \WP_Post $post Current post.
			 *
			 * @return array New row actions after having been filtered.
			 */
			public function article_row_action_links(array $actions, \WP_Post $post)
			{
				if($post->post_type !== $this->post_type)
					return $actions; // Not applicable.

				$row_action_links = new row_action_links(); // Row actions instance.

				return $row_action_links->filter($actions, $post);
			}

			/**
			 * Handles article actions before permalink.
			 *
			 * @since 150302 Adding article actions.
			 *
			 * @attaches-to `edit_form_before_permalink` hook.
			 *
			 * @param \WP_Post $post Current post.
			 */
			public function article_action_links_bp(\WP_Post $post)
			{
				if($post->post_type !== $this->post_type)
					return; // Not applicable.

				new action_links_bp($post);
			}

			/**
			 * Handle article issue redirects.
			 *
			 * @since 150225 Adding support for issue link references.
			 *
			 * @attaches-to `init` action hook.
			 */
			public function article_github_issue_redirect()
			{
				if(empty($_REQUEST[$this->qv_prefix.'github_issue_r']))
					return; // Not applicable.

				$this->utils_github->issue_redirect();
			}

			/*
			 * Shortcode-Related Methods
			 */

			/**
			 * Parses shortcode for list search box.
			 *
			 * @since 150220 Improving search box.
			 *
			 * @param array|string $attr Shortcode attributes.
			 * @param string       $content Shortcode content.
			 *
			 * @return string Parsed shortcode; i.e. HTML markup.
			 */
			public function sc_list_search_box($attr, $content = '')
			{
				$sc_list = new sc_list_search_box((array)$attr, $content);

				return $sc_list->parse(); // Parse shortcode.
			}

			/**
			 * Parses shortcode for articles list.
			 *
			 * @since 150113 First documented version.
			 *
			 * @param array|string $attr Shortcode attributes.
			 * @param string       $content Shortcode content.
			 *
			 * @return string Parsed shortcode; i.e. HTML markup.
			 */
			public function sc_list($attr, $content = '')
			{
				$sc_list = new sc_list((array)$attr, $content);

				return $sc_list->parse(); // Parse shortcode.
			}

			/**
			 * Filters author links.
			 *
			 * @since 150113 First documented version.
			 *
			 * @param string  $link The URL/link that WordPress has.
			 * @param integer $author_id The author ID.
			 * @param string  $author_slug The author slug.
			 *
			 * @return string The filtered author link; w/ possible alterations.
			 */
			public function sc_author_link($link, $author_id, $author_slug)
			{
				return $this->utils_post->author_link_filter($link, $author_id, $author_slug);
			}

			/**
			 * Filters term links.
			 *
			 * @since 150113 First documented version.
			 *
			 * @param string    $link The URL/link that WordPress has.
			 * @param \WP_Term $term The term object associated w/ this link.
			 * @param string    $taxonomy The taxonomy that we are dealing with.
			 *
			 * @return string The filtered term link; w/ possible alterations.
			 */
			public function sc_term_link($link, /* \WP_Term */ $term, $taxonomy)
			{
				return $this->utils_post->term_link_filter($link, $term, $taxonomy);
			}

			/*
			 * Custom post type handlers.
			 */

			/**
			 * Regisers post type.
			 *
			 * @since 150113 First documented version.
			 *
			 * @attaches-to `init` action.
			 */
			public function register_post_type()
			{
				if(!is_null($done = &$this->static_key(__FUNCTION__)))
					return; // Already did this; one time only please.
				$done = TRUE; // Flag this as being done.

				$icon = $this->utils_fs->inline_icon_svg();
				$icon = $this->utils_markup->color_svg_menu_icon($icon);

				$post_type_args           = array
				(
					'public'       => TRUE,
					'has_archive'  => $this->post_type_slug.'s',
					'menu_icon'    => 'data:image/svg+xml;base64,'.base64_encode($icon),
					'map_meta_cap' => TRUE, 'capability_type' => array($this->post_type, $this->post_type.'s'),
					'rewrite'      => array('slug' => $this->post_type_slug, 'with_front' => FALSE), // Like a Post (but no Post Formats).
					'supports'     => array('title', 'editor', 'author', 'excerpt', 'revisions', 'thumbnail', 'custom-fields', 'comments', 'trackbacks'),
					'taxonomies'   => array($this->post_type.'_category', $this->post_type.'_tag'),
				);
				$post_type_args['labels'] = array
				(
					'name'               => __('KB Articles', $this->text_domain),
					'singular_name'      => __('KB Article', $this->text_domain),
					'add_new'            => __('Add KB Article', $this->text_domain),
					'add_new_item'       => __('Add New KB Article', $this->text_domain),
					'edit_item'          => __('Edit KB Article', $this->text_domain),
					'new_item'           => __('New KB Article', $this->text_domain),
					'all_items'          => __('All KB Articles', $this->text_domain),
					'view_item'          => __('View KB Article', $this->text_domain),
					'search_items'       => __('Search KB Articles', $this->text_domain),
					'not_found'          => __('No KB Articles found', $this->text_domain),
					'not_found_in_trash' => __('No KB Articles found in Trash', $this->text_domain)
				);
				register_post_type($this->post_type, $post_type_args);

				$github_pending_post_status_args = array(
					'show_in_admin_all_list'    => TRUE,
					'show_in_admin_status_list' => TRUE,
					'public'                    => FALSE,
					'protected'                 => TRUE,
					'private'                   => TRUE,
					'exclude_from_search'       => FALSE,
					'label'                     => __('Pending (via GitHub)', $this->text_domain),
					'label_count'               => _n_noop('Pending (via GitHub) <span class="count">(%s)</span>', 'Pending (via GitHub) <span class="count">(%s)</span>'),
				);
				if($this->utils_github->enabled_configured()) // Using GitHub integration?
				{
					register_post_status('pending-via-github', $github_pending_post_status_args);
					add_filter('display_post_states', function (array $states, \WP_Post $post) use ($github_pending_post_status_args)
					{
						if($post->post_status === 'pending-via-github')
							if(empty($_REQUEST['post_status']) || $_REQUEST['post_status'] !== 'pending-via-github')
								$states['pending-via-github'] = $github_pending_post_status_args['label'];

						return $states; // Perhaps filtered by the routine above.

					}, 10, 2); // Displayed in the list of KB articles.
				}
				$category_taxonomy_args = array // Categories.
				(
				                                'public'       => TRUE, 'show_admin_column' => TRUE,
				                                'hierarchical' => TRUE, // This will use category labels.
				                                'rewrite'      => array('slug' => $this->post_type_slug.'-category', 'with_front' => FALSE),
				                                'capabilities' => array('assign_terms' => 'edit_'.$this->post_type.'s',
				                                                        'edit_terms'   => 'edit_'.$this->post_type.'s',
				                                                        'manage_terms' => 'edit_others_'.$this->post_type.'s',
				                                                        'delete_terms' => 'delete_others_'.$this->post_type.'s')
				);
				register_taxonomy($this->post_type.'_category', array($this->post_type), $category_taxonomy_args);

				$tag_taxonomy_args = array // Tags.
				(
				                           'public'       => TRUE, 'show_admin_column' => TRUE,
				                           'rewrite'      => array('slug' => $this->post_type_slug.'-tag', 'with_front' => FALSE),
				                           'capabilities' => array('assign_terms' => 'edit_'.$this->post_type.'s',
				                                                   'edit_terms'   => 'edit_'.$this->post_type.'s',
				                                                   'manage_terms' => 'edit_others_'.$this->post_type.'s',
				                                                   'delete_terms' => 'delete_others_'.$this->post_type.'s')
				);
				register_taxonomy($this->post_type.'_tag', array($this->post_type), $tag_taxonomy_args);
			}

			/**
			 * Registers rewrite rules/tags.
			 *
			 * @since 150113 First documented version.
			 *
			 * @attaches-to `init` action.
			 */
			public function register_rewrite_rules()
			{
				if(!is_null($done = &$this->static_key(__FUNCTION__)))
					return; // Already did this; one time only please.
				$done = TRUE; // Flag this as being done.

				foreach($this->qv_keys as $_qv) // e.g. `page`, `author`, etc.
					add_rewrite_endpoint($this->rewrite_prefix.$_qv, EP_PERMALINK | EP_PAGES, $this->qv_prefix.$_qv);
				unset($_qv); // Housekeeping.
				/*
				 * Endpoints are not that powerful; i.e. there's no way to collect them in any
				 *    sequence; or with more than one at a time. So we filter query vars.
				 */
				$_this = $this; // Needed by closure below.
				add_filter('request', function ($query_vars) use ($_this)
				{
					$current_path           = trim($_this->utils_url->current_path(), '/');
					$current_path_info      = trim($_this->utils_url->current_path_info(), '/');
					$current_path_with_info = trim($current_path.'/'.$current_path_info, '/');

					if(stripos($current_path_with_info, $_this->rewrite_prefix) === FALSE)
						return $query_vars; // Not applicable.

					if(($home_path = trim(parse_url(home_url(), PHP_URL_PATH), '/')))
						$current_path_with_info = preg_replace('/^'.preg_quote($home_path, '/').'/', '', $current_path_with_info);

					$rewrite_prefix_length        = strlen($_this->rewrite_prefix);
					$current_path_with_info_parts = explode('/', $current_path_with_info);

					for($_i = 0; $_i < count($current_path_with_info_parts); $_i++)
					{
						$_part = $current_path_with_info_parts[$_i];
						if(isset($current_path_with_info_parts[$_i + 1]))
							$_next_part = $current_path_with_info_parts[$_i + 1];
						else break; // Last item in this case.

						if(strpos($_part, $_this->rewrite_prefix) !== 0)
							continue; // Not applicable.

						$_unprefixed_part = substr($_part, $rewrite_prefix_length);
						if(!in_array($_unprefixed_part, $_this->qv_keys, TRUE))
							continue; // Not applicable.

						$query_vars[$_this->qv_prefix.$_unprefixed_part] = $_next_part;
						$_i++; // Skip the next part in this case.
					}
					unset($_i, $_part, $_next_part, $_unprefixed_part);

					return $query_vars; // Filter through.
				});
			}

			/**
			 * Activate or deactivate role-base caps.
			 *
			 * @since 150113 First documented version.
			 *
			 * @param string $action One of `activate` or `deactivate`.
			 */
			public function post_type_role_caps($action)
			{
				$all_caps = array(
					'edit_'.$this->post_type.'s',
					'edit_others_'.$this->post_type.'s',
					'edit_published_'.$this->post_type.'s',
					'edit_private_'.$this->post_type.'s',

					'publish_'.$this->post_type.'s',

					'delete_'.$this->post_type.'s',
					'delete_private_'.$this->post_type.'s',
					'delete_published_'.$this->post_type.'s',
					'delete_others_'.$this->post_type.'s',

					'read_private_'.$this->post_type.'s',
				);
				if($action === 'deactivate') // All on deactivate.
					$_roles = array_keys($GLOBALS['wp_roles']->roles);
				else $_roles = $this->roles_recieving_all_caps;

				foreach($_roles as $_role) if(is_object($_role = get_role($_role)))
					foreach($all_caps as $_cap) switch($action)
					{
						case 'activate': // Activating?

							$_role->add_cap($_cap);

							break; // Break switch handler.

						case 'deactivate': // Deactivating?

							$_role->remove_cap($_cap);

							break; // Break switch handler.
					}
				unset($_roles, $_role, $_cap); // Housekeeping.

				$edit_caps = array(
					'edit_'.$this->post_type.'s',
					'edit_published_'.$this->post_type.'s',

					'publish_'.$this->post_type.'s',

					'delete_'.$this->post_type.'s',
					'delete_published_'.$this->post_type.'s',
				);
				if($action === 'deactivate') // All on deactivate.
					$_roles = array_keys($GLOBALS['wp_roles']->roles);
				else $_roles = $this->roles_recieving_edit_caps;

				foreach($_roles as $_role) if(is_object($_role = get_role($_role)))
					foreach(($action === 'deactivate' ? $all_caps : $edit_caps) as $_cap) switch($action)
					{
						case 'activate': // Activating?

							$_role->add_cap($_cap);

							break; // Break switch handler.

						case 'deactivate': // Deactivating?

							$_role->remove_cap($_cap);

							break; // Break switch handler.
					}
				unset($_roles, $_role, $_cap); // Housekeeping.
			}

			/*
			 * CRON-Related Methods
			 */

			/**
			 * Extends WP-Cron schedules.
			 *
			 * @since 150113 First documented version.
			 *
			 * @attaches-to `cron_schedules` filter.
			 *
			 * @param array $schedules An array of the current schedules.
			 *
			 * @return array Revised array of WP-Cron schedules.
			 */
			public function extend_cron_schedules(array $schedules)
			{
				$schedules['every5m']  = array('interval' => 300, 'display' => __('Every 5 Minutes', $this->text_domain));
				$schedules['every15m'] = array('interval' => 900, 'display' => __('Every 15 Minutes', $this->text_domain));

				return apply_filters(__METHOD__, $schedules, get_defined_vars());
			}

			/**
			 * GitHub processor.
			 *
			 * @since 150113 First documented version.
			 *
			 * @attaches-to `_cron_'.__NAMESPACE__.'_github_processor` action.
			 */
			public function github_processor()
			{
				new github_processor();
			}
		}

		/*
		 * Namespaced Functions
		 */

		/**
		 * Used internally by other classes as an easy way to reference
		 *    the core {@link plugin} class instance.
		 *
		 * @since 150113 First documented version.
		 *
		 * @return plugin Class instance.
		 */
		function plugin() // Easy reference.
		{
			return $GLOBALS[__NAMESPACE__];
		}

		/*
		 * Automatic Plugin Loader
		 */

		/**
		 * A global reference to the plugin.
		 *
		 * @since 150113 First documented version.
		 *
		 * @var plugin Main plugin class.
		 */
		if(!isset($GLOBALS[__NAMESPACE__.'_autoload_plugin']) || $GLOBALS[__NAMESPACE__.'_autoload_plugin'])
			$GLOBALS[__NAMESPACE__] = new plugin(); // Load plugin automatically.
	}
	/*
	 * Catch a scenario where the plugin class already exists.
	 *    Assume both lite/pro are running in this case.
	 */
	else if(empty($GLOBALS[__NAMESPACE__.'_uninstalling'])) add_action('all_admin_notices', function ()
	{
		echo '<div class="error"> '. // Notify the site owner.
		     '   <p>'.
		     '      '.sprintf(__('Please disable the lite version of <code>%1$s</code> before activating the pro version.',
		                         str_replace('_', '-', __NAMESPACE__)), esc_html(str_replace('_', '-', __NAMESPACE__))).
		     '   </p>'.
		     '</div> ';
	});
}
