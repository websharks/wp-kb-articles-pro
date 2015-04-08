<?php
/**
 * GitHub Utilities
 *
 * @since 150113 First documented version.
 * @copyright WebSharks, Inc. <http://www.websharks-inc.com>
 * @license GNU General Public License, version 3
 */
namespace wp_kb_articles // Root namespace.
{
	if(!defined('WPINC')) // MUST have WordPress.
		exit('Do NOT access this file directly: '.basename(__FILE__));

	if(!class_exists('\\'.__NAMESPACE__.'\\utils_github'))
	{
		/**
		 * GitHub Utilities
		 *
		 * @since 150113 First documented version.
		 */
		class utils_github extends abs_base
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
			 * Is GitHub processing enabled/configured?
			 *
			 * @since 150113 First documented version.
			 *
			 * @return boolean `TRUE` if enabled/configured.
			 */
			public function enabled_configured()
			{
				if(!is_null($is = &$this->cache_key(__FUNCTION__)))
					return $is; // Already cached this.

				if(!$this->plugin->options['github_processing_enable'])
					return ($is = FALSE); // Disabled currently.

				if(!$this->plugin->options['github_mirror_owner'])
					return ($is = FALSE); // Not possible.

				if(!$this->plugin->options['github_mirror_repo'])
					return ($is = FALSE); // Not possible.

				if(!$this->plugin->options['github_mirror_branch'])
					return ($is = FALSE); // Not possible.

				if(!$this->plugin->options['github_mirror_api_key'])
					if(!$this->plugin->options['github_mirror_username'] || !$this->plugin->options['github_mirror_password'])
						return ($is = FALSE); // possible.

				if(!$this->plugin->options['github_mirror_author'])
					return ($is = FALSE); // Not possible.

				return ($is = TRUE); // Enabled and configured properly.
			}

			/**
			 * Builds a title based on the content of an article body.
			 *
			 * @since 150113 First documented version.
			 *
			 * @param string $body Article body.
			 *
			 * @return string Title from body; else `Untitled`.
			 */
			public function body_title($body)
			{
				$body = trim((string)$body);

				foreach(explode("\n", $body) as $_line)
					if(strpos($_line, '#') === 0 && preg_match('/^#+ /', $_line))
						if(($_title = trim($_line, " \r\n\t\0\x0B".'#')))
							return $_title; // Markdown title line.
				unset($_line, $_title); // Housekeeping.

				return $this->plugin->utils_string->clip($body);
			}

			/**
			 * Converts a repo path to a post ID.
			 *
			 * @since 150113 First documented version.
			 *
			 * @param string $path GitHub repo path to a file.
			 *
			 * @return integer WordPress Post ID.
			 */
			public function path_post_id($path)
			{
				$path = trim((string)$path);

				$sql = "SELECT `post_id` FROM `".esc_sql($this->plugin->utils_db->wp->postmeta)."`".
				       " WHERE `meta_key` = '".esc_sql(__NAMESPACE__.'_github_path')."'".
				       " AND `meta_value` = '".esc_sql($path)."'".
				       " ORDER BY `post_id` DESC LIMIT 1";

				return (integer)$this->plugin->utils_db->wp->get_var($sql);
			}

			/**
			 * Gets repo path for an article.
			 *
			 * @since 150113 First documented version.
			 *
			 * @param integer $post_id WordPress post ID.
			 *
			 * @return string Repo path for the article.
			 */
			public function get_path($post_id)
			{
				if(!($post_id = (integer)$post_id))
					return ''; // Not possible.

				return trim((string)get_post_meta($post_id, __NAMESPACE__.'_github_path', TRUE));
			}

			/**
			 * Updates repo path for an article.
			 *
			 * @since 150113 First documented version.
			 *
			 * @param integer $post_id WordPress post ID.
			 * @param string  $path The repo file path.
			 */
			public function update_path($post_id, $path)
			{
				if(!($post_id = (integer)$post_id))
					return; // Not possible.

				$path = trim((string)$path); // Can be empty.

				update_post_meta($post_id, __NAMESPACE__.'_github_path', $path);
			}

			/**
			 * Is a given path to be excluded automatically?
			 *
			 * @since 150408 Improving exclusion detection.
			 *
			 * @param string $path Path to test; e.g., `/path/to/file.md`.
			 *
			 * @return boolean `TRUE` if the path is excluded.
			 */
			public function is_path_excluded($path)
			{
				if(!($path = trim((string)$path)))
					return FALSE; // Not possible.

				$excluded_file_basenames   = array(
					'readme',
					'contributing',
					'changelog',
					'changes',
					'license',
					'package',
					'index',
				);
				$supported_file_extensions = array('md', 'html');

				$extension = $this->plugin->utils_fs->extension($path);
				$basename  = basename($path, $extension ? '.'.$extension : NULL);

				if(strpos($basename, '.') === 0) // Exclude?
					return TRUE; // Exlude dot dirs/files.

				if(!in_array($extension, $supported_file_extensions, TRUE))
					return TRUE; // Not a supported file extension.

				if(in_array(strtolower($basename), $excluded_file_basenames, TRUE))
					return TRUE; // Auto-exclude these basenames.

				return FALSE; // Default return value.
			}

			/**
			 * Gets content type for an article.
			 *
			 * @since 150214 Enhancing content/excerpt filters.
			 *
			 * @param integer $post_id WordPress post ID.
			 *
			 * @return string Content type value.
			 */
			public function get_content_type($post_id)
			{
				if(!($post_id = (integer)$post_id))
					return ''; // Not possible.

				return trim((string)get_post_meta($post_id, __NAMESPACE__.'_github_content_type', TRUE));
			}

			/**
			 * Updates content type for an article.
			 *
			 * @since 150214 Enhancing content/excerpt filters.
			 *
			 * @param integer $post_id WordPress post ID.
			 * @param string  $content_type Content type value.
			 */
			public function update_content_type($post_id, $content_type)
			{
				if(!($post_id = (integer)$post_id))
					return; // Not possible.

				$content_type = trim((string)$content_type); // Can be empty.

				update_post_meta($post_id, __NAMESPACE__.'_github_content_type', $content_type);
			}

			/**
			 * Gets SHA1 hash for an article.
			 *
			 * @since 150113 First documented version.
			 *
			 * @param integer $post_id WordPress post ID.
			 *
			 * @return string SHA1 hash for the article.
			 */
			public function get_sha($post_id)
			{
				if(!($post_id = (integer)$post_id))
					return ''; // Not possible.

				return trim((string)get_post_meta($post_id, __NAMESPACE__.'_github_sha', TRUE));
			}

			/**
			 * Updates SHA1 hash for an article.
			 *
			 * @since 150113 First documented version.
			 *
			 * @param integer $post_id WordPress post ID.
			 * @param string  $sha Most recent SHA1 hash.
			 */
			public function update_sha($post_id, $sha)
			{
				if(!($post_id = (integer)$post_id))
					return; // Not possible.

				$sha = trim((string)$sha); // Can be empty.

				update_post_meta($post_id, __NAMESPACE__.'_github_sha', $sha);
			}

			/**
			 * Gets issue URL for an article.
			 *
			 * @since 150117 Adding support for `github-issue:` in YAML config.
			 *
			 * @param integer $post_id WordPress post ID.
			 * @param boolean $else_issues If the issue URL is not defined, return `/issues/`?
			 *
			 * @return string Issue URL for the article.
			 */
			public function get_issue_url($post_id, $else_issues = FALSE)
			{
				if(!($post_id = (integer)$post_id))
					return ''; // Not possible.

				$issue_url = trim((string)get_post_meta($post_id, __NAMESPACE__.'_github_issue_url', TRUE));

				if(!$issue_url && $else_issues) // Fallback on `/issues/`?
					$issue_url = $this->repo_url().'/issues/';

				return $issue_url; // Issue (or `/issues/`) URL.
			}

			/**
			 * Updates issue URL for an article.
			 *
			 * @since 150117 Adding support for `github-issue:` in YAML config.
			 *
			 * @param integer $post_id WordPress post ID.
			 * @param string  $issue Issue number or URL.
			 */
			public function update_issue_url($post_id, $issue)
			{
				if(!($post_id = (integer)$post_id))
					return; // Not possible.

				$issue     = $this->plugin->utils_string->trim((string)$issue, '', '#');
				$issue_url = $issue; // Assume it is already a URL by default.

				if(isset($issue[0]) && is_numeric($issue)) // e.g. `github-issue: [number]`.
					$issue_url = $this->repo_url().'/issues/'.urlencode($issue);

				else if($issue && preg_match('/^(?P<owner>[^\/]+)\/(?P<repo>[^\/]+)#(?P<issue>[1-9][0-9]*)$/', $issue, $_m))
					// e.g. `github-issue: owner/repo#[number]`; as supported by all aspects of GitHub.
					$issue_url = $this->base_url().'/'.urlencode($_m['owner']).'/'.urlencode($_m['repo']).'/issues/'.urlencode($_m['issue']);

				update_post_meta($post_id, __NAMESPACE__.'_github_issue_url', $issue_url);
			}

			/**
			 * Gets article post ID based on issue number.
			 *
			 * @since 150225 Adding support for issue link references.
			 *
			 * @param integer $issue Issue number.
			 *
			 * @return integer Post ID.
			 */
			public function issue_post_id($issue)
			{
				if(!($issue = (integer)$issue))
					return 0; // Not possible.

				$issue_url = $this->repo_url().'/issues/'.urlencode($issue);

				$sql = "SELECT `".esc_sql($this->plugin->utils_db->wp->postmeta)."`.`post_id`".
				       " FROM `".esc_sql($this->plugin->utils_db->wp->postmeta)."`, `".esc_sql($this->plugin->utils_db->wp->posts)."`".
				       " WHERE `".esc_sql($this->plugin->utils_db->wp->postmeta)."`.`post_id` = `".esc_sql($this->plugin->utils_db->wp->posts)."`.`ID`".

				       " AND `".esc_sql($this->plugin->utils_db->wp->postmeta)."`.`meta_key` = '".esc_sql(__NAMESPACE__.'_github_issue_url')."'".
				       " AND `".esc_sql($this->plugin->utils_db->wp->postmeta)."`.`meta_value` = '".esc_sql($issue_url)."'".
				       " AND `".esc_sql($this->plugin->utils_db->wp->posts)."`.`post_status` = 'publish' LIMIT 1";

				return (integer)$this->plugin->utils_db->wp->get_var($sql);
			}

			/**
			 * Gets edit URL for an article.
			 *
			 * @since 150313 Adding ability to edit files on GitHub.
			 *
			 * @param integer $post_id WordPress post ID.
			 *
			 * @return string Edit URL for an article.
			 */
			public function repo_edit_url($post_id)
			{
				if(!($post_id = (integer)$post_id))
					return ''; // Not possible.

				if(!($path = $this->get_path($post_id)))
					return ''; // Not possible.

				$edit_url = $this->repo_url().'/edit/'.urlencode($this->plugin->options['github_mirror_branch']).'/'.$path;

				return $edit_url; // GitHub URL for editing the file.
			}

			/**
			 * Gets TOC-enable value for an article.
			 *
			 * @since 150118 Adding TOC generation.
			 *
			 * @param integer $post_id WordPress post ID.
			 *
			 * @return string TOC-enable value.
			 */
			public function get_toc_enable($post_id)
			{
				if(!($post_id = (integer)$post_id))
					return ''; // Not possible.

				return trim((string)get_post_meta($post_id, __NAMESPACE__.'_toc_enable', TRUE));
			}

			/**
			 * Updates TOC-enable value for an article.
			 *
			 * @since 150118 Adding TOC generation.
			 *
			 * @param integer $post_id WordPress post ID.
			 * @param string  $toc_enable TOC-enable value.
			 */
			public function update_toc_enable($post_id, $toc_enable)
			{
				if(!($post_id = (integer)$post_id))
					return; // Not possible.

				$toc_enable = trim((string)$toc_enable); // Can be empty.

				update_post_meta($post_id, __NAMESPACE__.'_toc_enable', $toc_enable);
			}

			/**
			 * GitHub repo URL for the configured repo.
			 *
			 * @since 150117 Adding support for `github-issue:` in YAML config.
			 *
			 * @return string The GitHub repo URL for the configured repo.
			 */
			public function repo_url()
			{
				return $this->base_url().'/'.urlencode($this->plugin->options['github_mirror_owner']).'/'.urlencode($this->plugin->options['github_mirror_repo']);
			}

			/**
			 * GitHub base URL.
			 *
			 * @since 150117 Adding support for `github-issue:` in YAML config.
			 *
			 * @return string GitHub base URL.
			 */
			public function base_url()
			{
				return 'https://github.com';
			}

			/**
			 * Issue reference redirections.
			 *
			 * @since 150225 Adding support for issue link references.
			 */
			public function issue_redirect()
			{
				if(empty($_REQUEST[$this->plugin->qv_prefix.'github_issue_r']))
					return; // Not applicable.

				if(!($issue = (integer)$_REQUEST[$this->plugin->qv_prefix.'github_issue_r']))
					return; // Not applicable.

				if(!$this->enabled_configured()) return; // Not applicable.

				if(($post_id = $this->issue_post_id($issue)) && ($permalink = get_permalink($post_id)))
					wp_redirect($permalink, 301).exit(); // Article matching this issue number.

				wp_redirect($this->repo_url().'/issues/'.urlencode($issue)).exit();
			}

			/**
			 * Filter issue references.
			 *
			 * @since 150225 Adding support for issue link references.
			 *
			 * @param string $body The body of a GitHub markdown/HTML file.
			 *
			 * @return string Filtered body of a GitHub markdown/HTML file.
			 */
			public function issue_redirect_filter($body)
			{
				if(!($body = trim((string)$body)))
					return $body; // Nothing to do.

				$_this = $this; // Needed by closures below.
				$spcsm = $this->plugin->utils_string->spcsm_tokens($body, array('shortcodes', 'pre', 'code', 'samp', 'md_fences'));

				$spcsm['string'] = preg_replace_callback('/\]\('.preg_quote($this->repo_url(), '/').'\/issues\/(?P<issue>[1-9][0-9]*).*?\)/i', function ($m) use ($_this)
				{
					return ']('.add_query_arg($_this->plugin->qv_prefix.'github_issue_r', urlencode($m['issue']), home_url('/', 'http')).')'; #

				}, $spcsm['string']); // Filters links in Markdown syntax.

				$spcsm['string'] = preg_replace_callback('/\shref\s*\=\s*([\'"])'.preg_quote($this->repo_url(), '/').'\/issues\/(?P<issue>[1-9][0-9]*).*?\\1/i', function ($m) use ($_this)
				{
					return ' href='.$m[1].add_query_arg($_this->plugin->qv_prefix.'github_issue_r', urlencode($m['issue']), home_url('/', 'http')).$m[1]; #

				}, $spcsm['string']); // Filters links in HTML anchor tags also.

				return ($body = $this->plugin->utils_string->spcsm_restore($spcsm));
			}

			/**
			 * Media library upload handler.
			 *
			 * @since 150227 Adding support for media library storage.
			 *
			 * @param string $body The body of a GitHub markdown/HTML file.
			 *
			 * @return string Filtered body of a GitHub markdown/HTML file.
			 */
			public function media_library_filter($body)
			{
				if(!($body = trim((string)$body)))
					return $body; // Nothing to do.

				if(!($uploads = wp_upload_dir()) || !empty($uploads['error']))
					return $body; // Not possible.

				$_this        = $this; // Needed by closures below.
				$uploads_host = parse_url($uploads['url'], PHP_URL_HOST); // Host name associated with media library.
				$spcsm        = $this->plugin->utils_string->spcsm_tokens($body, array('shortcodes', 'pre', 'code', 'samp', 'md_fences'));

				require_once ABSPATH.'wp-admin/includes/image.php'; // Required for `wp_generate_attachment_metadata()`.

				$callback_helper = function ($m, $prefix, $suffix) use ($_this, $uploads, $uploads_host)
				{
					$guid = sha1($m['url']); // Unique ID for this URL.

					if(!($url = parse_url($m['url'])))
						return $m[0]; // Not possible.

					if(empty($url['host']) || empty($url['path']))
						return $m[0]; // Not possible.

					if(strcasecmp($uploads_host, $url['host']) === 0)
						return $m[0]; // Not applicable.

					if(strcasecmp($_SERVER['HTTP_HOST'], $url['host']) === 0)
						return $m[0]; // Not applicable.

					$fragment_ext = ''; // Initialize fragment-based extension.
					if(!empty($url['fragment']) && strpos($url['fragment'], '.') === 0)
						if(in_array($url['fragment'], array('.png', '.jpg', '.jpeg', '.gif'), TRUE))
							$fragment_ext = $url['fragment']; // e.g., `.png`, `.jpg`, `.gif`.

					if(!($path = wp_check_filetype(basename($url['path']).$fragment_ext)))
						return $m[0]; // Not possible.

					if(empty($path['ext']) || empty($path['type']))
						return $m[0]; // Not possible.

					if(!in_array(strtolower($path['ext']), array('png', 'jpg', 'jpeg', 'gif'), TRUE))
						return $m[0]; // Not applicable.

					$sql = "SELECT `ID` FROM `".esc_sql($_this->plugin->utils_db->wp->posts)."`".
					       " WHERE (`guid` = '".esc_sql('http://'.$guid)."' OR `guid` = '".esc_sql($guid)."')".
					       " AND `post_type` = 'attachment' LIMIT 1";
					if(($attachment_id = (integer)$_this->plugin->utils_db->wp->get_var($sql))) // Exists?
					{
						if(($src = wp_get_attachment_image_src($attachment_id, 'full')))
							return $prefix.$src[0].$suffix; // Attachment src URL.
						return $m[0]; // Not possible.
					}
					if(!($remote = wp_remote_get($m['url'])))
						return $m[0]; // Not possible.

					if(!($remote_response = wp_remote_retrieve_body($remote)))
						return $m[0]; // Not possible.

					$tmp_file = $_this->plugin->utils_fs->n_seps(get_temp_dir()).
					            '/'.$_this->plugin->utils_enc->uunnci_key_20_max().'.'.$path['ext'];
					$file     = $uploads['path'].'/'.$guid.'.'.$path['ext'];

					if(!is_writable(dirname($tmp_file)) || !is_writable(dirname($file)))
						return $m[0]; // Not possible.

					if(file_put_contents($tmp_file, $remote_response) === FALSE)
						return $m[0]; // Not possible.

					if(file_put_contents($file, $remote_response) === FALSE)
					{
						unlink($tmp_file); // Ditch tmp file.
						return $m[0]; // Not possible.
					}
					if(!($finfo = finfo_open(FILEINFO_MIME_TYPE))
					   || stripos(finfo_file($finfo, $tmp_file), 'image/') !== 0
					) // Make sure what we downloaded has an `image/*` MIME type.
					{
						unlink($tmp_file); // Ditch tmp file.
						unlink($file); // Ditch uploaded file.
						return $m[0]; // Not possible.
					}
					unlink($tmp_file); // Ditch tmp file.

					$attachment = array(
						'guid'           => $guid,
						'file'           => $file,
						'post_mime_type' => $path['type'],
						'post_title'     => preg_replace('/\.[^.]+$/', '', basename($url['path'])),
					);
					if(!($attachment_id = (integer)wp_insert_attachment($attachment)))
					{
						unlink($file); // Ditch uploaded file.
						return $m[0]; // Not possible.
					}
					if(!($src = wp_get_attachment_image_src($attachment_id, 'full')))
						return $m[0]; // Not possible.

					$attachment_meta = wp_generate_attachment_metadata($attachment_id, $file);
					wp_update_attachment_metadata($attachment_id, $attachment_meta);

					return $prefix.$src[0].$suffix; // Attachment src URL.
				};
				$spcsm['string'] = preg_replace_callback('/\]\((?P<url>https?\:\/\/.+?)\)/i', function ($m) use ($callback_helper)
				{
					return $callback_helper($m, '](', ')'); // Use callback helper.

				}, $spcsm['string']); // Filters links in Markdown syntax.

				$spcsm['string'] = preg_replace_callback('/\ssrc\s*\=\s*([\'"])(?P<url>https?\:\/\/.+?)\\1/i', function ($m) use ($callback_helper)
				{
					return $callback_helper($m, ' src='.$m[1], $m[1]); // Use callback helper.

				}, $spcsm['string']); // Filters src attributes in HTML tags also.

				return ($body = $this->plugin->utils_string->spcsm_restore($spcsm));
			}

			/**
			 * Handle ezPHP exclusions.
			 *
			 * @since 150214 Enhancing content/excerpt filters.
			 *
			 * @param boolean  $exclude Excluded?
			 * @param \WP_Post $post A WP Post object.
			 *
			 * @return boolean `TRUE` if post/article is excluded.
			 */
			public function maybe_exclude_from_ezphp($exclude, \WP_Post $post)
			{
				if($post->post_type === $this->plugin->post_type)
					if($this->enabled_configured() || $this->get_content_type($post->ID))
						return ($exclude = TRUE); // Disallow ezPHP in these cases.

				return $exclude; // Exclude; pass through.
			}

			/**
			 * Handle raw HTML content type.
			 *
			 * @since 150214 Enhancing content/excerpt filters.
			 *
			 * @param string $content The post/article content.
			 *
			 * @return string The post/article content.
			 */
			public function maybe_preserve_raw_html_content($content)
			{
				if(!$GLOBALS['post'] || $GLOBALS['post']->post_type !== $this->plugin->post_type)
					return $content; // Not applicable.

				if($this->get_content_type($GLOBALS['post']->ID) !== 'text/html')
					return $content; // Not applicable.

				$token = '%%'.__NAMESPACE__.'_raw_html_'.$GLOBALS['post']->ID.'%%';

				if(strpos($content, $token) !== FALSE)
					return $content; // Already filtered this.

				$raw_html = &$this->cache_key(__FUNCTION__, $GLOBALS['post']->ID);
				$raw_html = (string)$content; // Preserve raw HTML content.

				return $token; // Other filters will see the token only.
			}

			/**
			 * Handle raw HTML content type.
			 *
			 * @since 150214 Enhancing content/excerpt filters.
			 *
			 * @param string $content The post/article content.
			 *
			 * @return string The post/article content.
			 */
			public function maybe_restore_raw_html_content($content)
			{
				if(!$GLOBALS['post'] || $GLOBALS['post']->post_type !== $this->plugin->post_type)
					return $content; // Not applicable.

				if($this->get_content_type($GLOBALS['post']->ID) !== 'text/html')
					return $content; // Not applicable.

				$raw_html = &$this->cache_key('maybe_preserve_raw_html_content', $GLOBALS['post']->ID);
				$raw_html = (string)$raw_html; // Force string value.

				$content  = str_replace('%%'.__NAMESPACE__.'_raw_html_'.$GLOBALS['post']->ID.'%%', $raw_html, $content);
				$raw_html = ''; // Empty this out now; just wasting memory otherwise.

				return $content; // Content w/ raw HTML preserved.
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
			public function maybe_preserve_raw_html_excerpt($excerpt)
			{
				if(!$GLOBALS['post'] || $GLOBALS['post']->post_type !== $this->plugin->post_type)
					return $excerpt; // Not applicable.

				if($this->get_content_type($GLOBALS['post']->ID) !== 'text/html')
					return $excerpt; // Not applicable.

				$token = '%%'.__NAMESPACE__.'_raw_html_'.$GLOBALS['post']->ID.'%%';

				if(strpos($excerpt, $token) !== FALSE)
					return $excerpt; // Already filtered this.

				$raw_html = &$this->cache_key(__FUNCTION__, $GLOBALS['post']->ID);
				$raw_html = (string)$excerpt; // Preserve raw HTML content.

				if(!$raw_html && !has_excerpt()) // No excerpt?
				{
					$content        = get_the_content('');
					$excerpt        = apply_filters('the_content', $content);
					$excerpt        = str_replace(']]>', ']]&gt;', $excerpt);
					$excerpt_length = apply_filters('excerpt_length', 55);
					$excerpt_more   = apply_filters('excerpt_more', ' '.'[&hellip;]');
					$excerpt        = wp_trim_words($excerpt, $excerpt_length, $excerpt_more);
					$raw_html       = $excerpt = apply_filters('wp_trim_excerpt', $excerpt, '');
				}
				return $token; // Other filters will see the token only.
			}

			/**
			 * Handle raw HTML content type.
			 *
			 * @since 150214 Enhancing content/excerpt filters.
			 *
			 * @param string $excerpt The post/article excerpt.
			 *
			 * @return string The post/article excerpt.
			 */
			public function maybe_restore_raw_html_excerpt($excerpt)
			{
				if(!$GLOBALS['post'] || $GLOBALS['post']->post_type !== $this->plugin->post_type)
					return $excerpt; // Not applicable.

				if($this->get_content_type($GLOBALS['post']->ID) !== 'text/html')
					return $excerpt; // Not applicable.

				$raw_html = &$this->cache_key('maybe_preserve_raw_html_excerpt', $GLOBALS['post']->ID);
				$raw_html = (string)$raw_html; // Force string value.

				$excerpt  = str_replace('%%'.__NAMESPACE__.'_raw_html_'.$GLOBALS['post']->ID.'%%', $raw_html, $excerpt);
				$raw_html = ''; // Empty this out now; just wasting memory otherwise.

				return $excerpt; // Excerpt w/ raw HTML preserved successfully.
			}

			/**
			 * Is a string in SHA1 format?
			 *
			 * @since 150214 Enhancing content/excerpt filters.
			 *
			 * @param string $string The string to test.
			 *
			 * @return boolean `TRUE` if it's in SHA1 format.
			 */
			public function is_sha($string)
			{
				return (boolean)preg_match('/^[0-9a-f]{40}$/i', (string)$string);
			}

			/**
			 * Signed API event key.
			 *
			 * @return string Signed API event key.
			 */
			public function event_key()
			{
				return $this->plugin->utils_enc->hmac_sha256_sign(home_url('/'));
			}
		}
	}
}
