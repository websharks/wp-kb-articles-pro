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
				if(!$this->plugin->options['github_processing_enable'])
					return FALSE; // Disabled currently.

				if(!$this->plugin->options['github_mirror_owner'])
					return FALSE; // Not possible.

				if(!$this->plugin->options['github_mirror_repo'])
					return FALSE; // Not possible.

				if(!$this->plugin->options['github_mirror_branch'])
					return FALSE; // Not possible.

				if(!$this->plugin->options['github_mirror_api_key'])
					if(!$this->plugin->options['github_mirror_username'] || !$this->plugin->options['github_mirror_password'])
						return FALSE; // possible.

				if(!$this->plugin->options['github_mirror_author'])
					return FALSE; // Not possible.

				return TRUE; // Enabled and configured properly.
			}

			/**
			 * Converts a repo path into a WP slug.
			 *
			 * @since 150113 First documented version.
			 *
			 * @param string $path GitHub repo path to a file.
			 *
			 * @return string Slugified path.
			 */
			public function path_to_slug($path)
			{
				$path = trim((string)$path);

				$slug = preg_replace('/\.[^.]*$/', '', $path);
				$slug = preg_replace('/[^a-z0-9]/i', '-', $slug);
				$slug = trim($slug, '-');

				return substr($slug, 0, 200);
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
					if(strpos($_line, '#') === 0 && ($_title = trim($_line, " \r\n\t\0\x0B".'#')))
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

				if(!($path = trim((string)$path)))
					return; // Not possible.

				update_post_meta($post_id, __NAMESPACE__.'_github_path', $path);
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

				if(!($sha = trim((string)$sha)))
					return; // Not possible.

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

				if(!($issue = $this->plugin->utils_string->trim((string)$issue, '', '#')))
					return; // Not possible; issue empty after trimming.

				$issue_url = $issue; // Assume it is already a URL by default.

				if(is_numeric($issue)) // e.g. `github-issue: [number]`.
					$issue_url = $this->repo_url().'/issues/'.urlencode($issue);

				else if(preg_match('/^(?P<owner>[^\/]+)\/(?P<repo>[^\/]+)#(?P<issue>[1-9][0-9]*)$/', $issue, $_m))
					// e.g. `github-issue: owner/repo#[number]`; as supported by all aspects of GitHub.
					$issue_url = $this->base_url().'/'.urlencode($_m['owner']).'/'.urlencode($_m['repo']).'/issues/'.urlencode($_m['issue']);

				update_post_meta($post_id, __NAMESPACE__.'_github_issue_url', $issue_url);
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
		}
	}
}