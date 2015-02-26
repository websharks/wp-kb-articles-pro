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

				return (integer)$this->utils_db->wp->get_var($sql);
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
				$spcsm = $this->utils_string->spcsm_tokens($body, array('shortcodes', 'pre', 'code', 'samp', 'md_fences'));

				$spcsm['string'] = preg_replace_callback('/\]\('.preg_quote($this->repo_url(), '/').'\/issues\/(?P<issue>[1-9][0-9]*).*?\)/i', function ($m) use ($_this)
				{
					return ']('.add_query_arg($_this->plugin->qv_prefix.'github_issue_r', urlencode($m['issue']), home_url('/', 'http')).')'; #

				}, $spcsm['string']); // Filters links in Markdown syntax.

				$spcsm['string'] = preg_replace_callback('/\shref\s*\=\s*([\'"])'.preg_quote($this->repo_url(), '/').'\/issues\/(?P<issue>[1-9][0-9]*).*?\\1/i', function ($m) use ($_this)
				{
					return ' href='.$m[1].add_query_arg($_this->plugin->qv_prefix.'github_issue_r', urlencode($m['issue']), home_url('/', 'http')).$m[1]; #

				}, $spcsm['string']); // Filters links in HTML anchor tags also.

				return ($body = $this->utils_string->spcsm_restore($spcsm));
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
		}
	}
}