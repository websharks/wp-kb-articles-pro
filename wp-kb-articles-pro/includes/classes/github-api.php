<?php
/**
 * GitHub API Class
 *
 * @since 150107 First documented version.
 * @copyright WebSharks, Inc. <http://www.websharks-inc.com>
 * @license GNU General Public License, version 3
 */
namespace wp_kb_articles // Root namespace.
{
	if(!defined('WPINC')) // MUST have WordPress.
		exit('Do NOT access this file directly.');

	if(!class_exists('\\'.__NAMESPACE__.'\\github_api'))
	{
		/**
		 * GitHub API Class
		 *
		 * @since 150107 First documented version.
		 */
		class github_api extends abs_base
		{
			/**
			 * Repo owner; e.g. `https://github.com/[owner]`.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var string Repo owner; e.g. `https://github.com/[owner]`.
			 */
			protected $owner;

			/**
			 * Repo name; e.g. `https://github.com/[owner]/[repo]`.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var string Repo name; e.g. `https://github.com/[owner]/[repo]`.
			 */
			protected $repo;

			/**
			 * Repo owner; e.g. `https://github.com/[owner]/[repo]/[branch]`.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var string Repo owner; e.g. `https://github.com/[owner]/[repo]/[branch]`.
			 */
			protected $branch;

			/**
			 * API key; e.g. `Authorization: token [api_key]`.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var string API key; e.g. `Authorization: token [api_key]`.
			 */
			protected $api_key;

			/**
			 * GitHub username; e.g. `https://[username]@github.com/`.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var string GitHub username; e.g. `https://[username]@github.com/`.
			 */
			protected $username;

			/**
			 * GitHub password or API key; e.g. `https://[username]:[password]@github.com/`.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var string GitHub password or API key; e.g. `https://[username]:[password]@github.com/`.
			 */
			protected $password;

			/**
			 * Class constructor.
			 *
			 * @since 150113 First documented version.
			 *
			 * @param array $args Array of arguments specific to the GitHub integration.
			 */
			public function __construct(array $args)
			{
				parent::__construct();

				$default_args = array(
					'owner'    => '',
					'repo'     => '',

					'branch'   => 'HEAD',

					'username' => '',
					'password' => '',
					'api_key'  => '',
				);
				$args         = array_merge($default_args, $args);
				$args         = array_intersect_key($args, $default_args);

				$this->owner = trim(strtolower((string)$args['owner']));
				$this->repo  = trim(strtolower((string)$args['repo']));

				$this->branch = trim((string)$args['branch']);

				$this->username = trim(strtolower((string)$args['username']));
				$this->password = trim((string)$args['password']);
				$this->api_key  = trim((string)$args['api_key']);
			}

			/* === Public Methods === */

			/**
			 * Retrieves an array of directories/files within a repo.
			 *
			 * @since 150113 First documented version.
			 *
			 * @param string $sha A specific tree (i.e., directory sha) to retrieve.
			 *
			 * @return array|boolean An associative array of all articles; else `FALSE` on error.
			 *
			 *    Array keys contain the directory/file paths.
			 *    Each item in the array will contain the following elements:
			 *
			 *    - `sha` The SHA1 from the GitHub side.
			 *    - `type` Item type; i.e., `tree` or `blob`.
			 */
			public function retrieve_article_trees_blobs($sha = '')
			{
				if(!($tree = $this->retrieve_tree($sha)))
					return FALSE; // Not possible.

				$trees_blobs = array(); // Initialize.

				foreach($tree['tree'] as $_tree_blob)
				{
					if($_tree_blob['type'] === 'blob') // i.e., NOT a directory.
						if($this->plugin->utils_github->is_path_excluded($_tree_blob['path']))
							continue; // Exclude this path please.

					$trees_blobs[$_tree_blob['path']] = array(
						'sha'  => $_tree_blob['sha'],
						'type' => $_tree_blob['type'],
					);
				}
				unset($_tree_blob); // Housekeeping.

				if(count($trees_blobs) >= 1000) $this->plugin->enqueue_notice // Notify site owner.
					(
						sprintf(__('<strong>%1$s&trade;</strong> found 1000+ items in a single GitHub folder. This can lead to GitHub API errors. Please use dated sub-directories so that you can avoid problems.', $this->plugin->text_domain), esc_html($this->plugin->name)),
						array('persistent' => TRUE, 'push_to_top' => TRUE)
					);
				return $trees_blobs; // Array of all sub-trees and article blobs.
			}

			/**
			 * Retrieves an article, including the body.
			 *
			 * @since 150113 First documented version.
			 *
			 * @param string $sha_path A sha1 hash or file path.
			 *
			 * @return array|boolean Array with the following elements; else `FALSE` on failure.
			 *
			 *    - `sha` SHA1 of the current body content data.
			 *    - `headers` An associative array of all YAML headers.
			 *    - `body` The body part of the article after YAML headers were parsed.
			 */
			public function retrieve_article($sha_path)
			{
				if(!($sha_path = $this->plugin->utils_string->trim((string)$sha_path, '', '/')))
					return FALSE; // Not possible.

				if($this->plugin->utils_github->is_sha($sha_path))
				{
					if(!($blob = $this->retrieve_blob($sha_path)))
						return FALSE; // Error.

					if($blob['encoding'] === 'base64')
						$body = base64_decode($blob['content']);
					else $body = $blob['content'];

					$article = array('sha' => $sha_path);
				}
				else // Assume it is a file path in this case.
				{
					if(!($body = $this->retrieve_file($sha_path)))
						return FALSE; // Failure.

					$article = array('sha' => sha1('blob '.strlen($body)."\0".$body));
				}
				return array_merge($article, $this->parse_article($body));
			}

			/**
			 * Tests connectivity by checking rate limit.
			 *
			 * @since 150302 Adding GitHub connectivity tests.
			 *
			 * @return boolean `TRUE` if rate limit is > 60.
			 */
			public function test_connectivity()
			{
				if(!($rate_limit = $this->retrieve_rate_limit()))
					return FALSE; // Total failure.

				return $rate_limit > 60; // Authenticated?
			}

			/* === Base GitHub Retrieval === */

			/**
			 * Retrieves API rate limit.
			 *
			 * @since 150302 Adding GitHub connectivity tests.
			 *
			 * @return integer|boolean Rate limit; else `FALSE` on failure.
			 */
			protected function retrieve_rate_limit()
			{
				$url = 'api.github.com/rate_limit';

				if(($response = $this->get_response($url)))
					if(is_array($response_array = json_decode($response['body'], TRUE)))
						return (integer)$response_array['resources']['core']['limit'];

				return FALSE; // Failure.
			}

			/**
			 * Wrapper for `retrieve_blob()` and `retrieve_file()`.
			 *
			 * @since 150113 First documented version.
			 *
			 * @param string $sha_path A sha1 hash or file path.
			 *
			 * @return string|boolean Body contents; else `FALSE` on error.
			 */
			protected function retrieve_body($sha_path)
			{
				if(!($sha_path = $this->plugin->utils_string->trim((string)$sha_path, '', '/')))
					return FALSE; // Not possible.

				if($this->plugin->utils_github->is_sha($sha_path))
				{
					if(!($blob = $this->retrieve_blob($sha_path)))
						return FALSE; // Error.

					if(!empty($blob['encoding']) && $blob['encoding'] === 'base64')
						return base64_decode($blob['content']);

					return $blob['content'];
				}
				return $this->retrieve_file($sha_path);
			}

			/**
			 * Retrieves list of directories/files.
			 *
			 * @since 150227 Improving GitHub API recursion.
			 *
			 * @param string $sha A specific tree (i.e., directory sha) to retrieve.
			 *
			 * @return array|boolean Array of directories/files; else `FALSE` on error.
			 */
			protected function retrieve_tree($sha = '')
			{
				$sha = $this->plugin->utils_string->trim((string)$sha, '', '/');

				$url = 'api.github.com/repos/%1$s/%2$s/git/trees/%3$s%4$s';
				$url = sprintf($url, $this->owner, $this->repo, $sha ? '' : $this->branch, $sha);

				if(($response = $this->get_response($url)))
					if(is_array($response_array = json_decode($response['body'], TRUE)))
						return $response_array;

				return FALSE; // Failure.
			}

			/**
			 * Retrieves file/blob contents.
			 *
			 * @since 150113 First documented version.
			 *
			 * @param string $sha A sha that identifies a blob to retrieve.
			 *
			 * @return array|boolean File/blob; else `FALSE` on error.
			 */
			protected function retrieve_blob($sha)
			{
				if(!($sha = $this->plugin->utils_string->trim((string)$sha, '', '/')))
					return FALSE; // Not possible.

				$url = 'api.github.com/repos/%1$s/%2$s/git/blobs/%3$s';
				$url = sprintf($url, $this->owner, $this->repo, $sha);

				if(($response = $this->get_response($url)))
					if(is_array($response_array = json_decode($response['body'], TRUE)))
						return $response_array;

				return FALSE; // Failure.
			}

			/**
			 * Retrieves file contents via path.
			 *
			 * @since 150113 First documented version.
			 *
			 * @param string $path The path to a file to retrieve.
			 *
			 * @return string|boolean File contents; else `FALSE` on error.
			 */
			protected function retrieve_file($path)
			{
				if(!($path = $this->plugin->utils_string->trim((string)$path, '', '/')))
					return FALSE; // Not possible.

				$url = 'raw.githubusercontent.com/%1$s/%2$s/%3$s/%4$s';
				$url = sprintf($url, $this->owner, $this->repo, $this->branch, $path);
				$url .= '?no_cache='.urlencode(uniqid());

				if(($response = $this->get_response($url)))
					return $response['body'];

				return FALSE; // Failure.
			}

			/**
			 * Parses a KB article w/ possible YAML front matter.
			 *
			 * @since 150113 First documented version.
			 *
			 * @param string $article Input article content to parse.
			 *
			 * @return array An array with two elements.
			 *
			 *    - `headers` An associative array of all YAML headers.
			 *    - `body` The body part of the article after YAML headers were parsed.
			 */
			protected function parse_article($article)
			{
				$parts   = array(
					'headers' => array(),
					'body'    => '',
				);
				$article = (string)$article; // Force string value.
				$article = str_replace(array("\r\n", "\r"), "\n", $article);
				$article = trim($article); // Trim it up now.

				if(strpos($article, '---'."\n") !== 0)
				{
					$parts['body'] = $article;
					return $parts; // Body only.
				}
				$article_parts = preg_split('/^\-{3}$/m', $article, 3);

				if(count($article_parts) !== 3)
				{
					$parts['body'] = $article;
					return $parts; // Body only.
				}
				list(, $article_headers, $parts['body']) = array_map('trim', $article_parts);

				foreach($this->plugin->utils_yaml->parse($article_headers) as $_name => $_value)
				{
					if($_value === TRUE) $_value = 'true';
					if($_value === FALSE) $_value = 'false';
					$_name                    = str_replace('-', '_', strtolower(trim($_name)));
					$parts['headers'][$_name] = trim((string)$_value);
				}
				unset($_name, $_value); // Housekeeping.

				return $parts; // Headers and body.
			}

			/**
			 * Universal GitHub HTTP request method.
			 *
			 * @since 150113 First documented version.
			 *
			 * @param string $url The URL to request.
			 * @param array  $args An associative array of arguments that can be used to overwrite the defaults used by the function.
			 *
			 * @return array|boolean An array with the following elements; else `FALSE` on error.
			 *
			 *    - `request` = Result from `wp_remote_request()` call.
			 *    - `body` = Result from `wp_remote_retrieve_body()` call.
			 *    - `headers` = Result from `wp_remote_retrieve_headers()` call.
			 *    - `response_code` = Result from `wp_remote_retrieve_response_code()` call.
			 */
			protected function get_response($url, array $args = array())
			{
				$default_args = array(
					'headers'    => array(),
					'user-agent' => $this->plugin->name.' @ '.$_SERVER['HTTP_HOST'],
				);
				$args         = array_merge($default_args, $args);
				$args         = array_intersect_key($args, $default_args);

				if($this->api_key) // Associative.
					$args['headers']['Authorization'] = 'token '.$this->api_key;

				$user_pass_prefix = ''; // Initialize.
				if(isset($this->username[0], $this->password[0]))
					$user_pass_prefix = $this->username.':'.$this->password.'@';
				$url = 'https://'.$user_pass_prefix.$url;

				if(is_wp_error($request = wp_remote_request($url, $args)))
					return FALSE; // Error.

				$body          = wp_remote_retrieve_body($request);
				$headers       = wp_remote_retrieve_headers($request);
				$response_code = wp_remote_retrieve_response_code($request);

				if($response_code !== 302 && $response_code !== 200)
					return FALSE; // Error.

				return compact('request', 'body', 'headers', 'response_code');
			}
		}
	}
}
