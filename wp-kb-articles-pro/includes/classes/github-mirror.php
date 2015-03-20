<?php
/**
 * GitHub Mirror
 *
 * @since 150113 First documented version.
 * @copyright WebSharks, Inc. <http://www.websharks-inc.com>
 * @license GNU General Public License, version 3
 */
namespace wp_kb_articles // Root namespace.
{
	if(!defined('WPINC')) // MUST have WordPress.
		exit('Do NOT access this file directly: '.basename(__FILE__));

	if(!class_exists('\\'.__NAMESPACE__.'\\github_mirror'))
	{
		/**
		 * GitHub Mirror
		 *
		 * @since 150113 First documented version.
		 */
		class github_mirror extends abs_base
		{
			/**
			 * Arguments.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var array Args.
			 */
			protected $args;

			/**
			 * Current user.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var \WP_User Current user.
			 */
			protected $current_user;

			/**
			 * File path.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var string File path.
			 */
			protected $path;

			/**
			 * File extension.
			 *
			 * @since 150214 Enhancing content/excerpt filters.
			 *
			 * @var string File extension.
			 */
			protected $extension;

			/**
			 * File content type; after body filters.
			 *
			 * @since 150214 Enhancing content/excerpt filters.
			 *
			 * @var string File content type; after body filters.
			 */
			protected $content_type;

			/**
			 * SHA1 hash.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var string SHA1 hash.
			 */
			protected $sha;

			/**
			 * Issue number/string/URL.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var string Issue number/string/URL.
			 */
			protected $issue;

			/**
			 * Post.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var \WP_Post|null Post.
			 */
			protected $post;

			/**
			 * It's new?
			 *
			 * @since 150113 First documented version.
			 *
			 * @var boolean It's new?
			 */
			protected $is_new;

			/**
			 * Slug.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var string Slug.
			 */
			protected $slug;

			/**
			 * Title.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var string Title.
			 */
			protected $title;

			/**
			 * Categories.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var array Categories.
			 */
			protected $categories;

			/**
			 * Tags.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var array Tags.
			 */
			protected $tags;

			/**
			 * Author.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var integer Author.
			 */
			protected $author;

			/**
			 * Status.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var string Status.
			 */
			protected $status;

			/**
			 * Pub date.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var string Pub date.
			 */
			protected $pubdate;

			/**
			 * Body.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var string Body.
			 */
			protected $body;

			/**
			 * Excerpt.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var string Excerpt.
			 */
			protected $excerpt;

			/**
			 * Comment status.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var string Comment status.
			 */
			protected $comment_status;

			/**
			 * Ping status.
			 *
			 * @since 150113 First documented version.
			 *
			 * @var string Ping status.
			 */
			protected $ping_status;

			/**
			 * TOC enable?
			 *
			 * @since 150118 Adding TOC generation.
			 *
			 * @var string TOC enable?
			 */
			protected $toc_enable;

			/**
			 * Class constructor.
			 *
			 * @since 150113 First documented version.
			 *
			 * @param array $args Arguments to constructor.
			 */
			public function __construct(array $args)
			{
				parent::__construct();

				$default_args = array(
					'path'           => '', // e.g. `my/article.md`.
					'sha'            => '', // SHA1 hash from GitHub.
					'issue'          => '', // e.g. `1`, `owner/repo#1`, or `[URL]`.

					'slug'           => '', // e.g. `my-article`.
					'title'          => '', // e.g. My Article Title.

					'categories'     => '', // Comma-delimited list.
					'tags'           => '', // Comma-delimited list.

					'author'         => '', // `1`, or `johndoe` (ID or username).
					'status'         => '', // `draft`, `pending`, `pending-via-github`, `publish`, `future`.
					'pubdate'        => '', // `strtotime()` compatible.

					'body'           => '', // Article body content.
					'excerpt'        => '', // Article excerpt.

					'comment_status' => '', // `open` or `closed`.
					'ping_status'    => '', // `open` or `closed`.

					'toc_enable'     => '', // `true` or `false`.
				);
				if(isset($args['github_issue']) && !isset($args['issue']))
					$args['issue'] = $args['github_issue'];

				if(isset($args['category']) && !isset($args['categories']))
					$args['categories'] = $args['category'];

				if(isset($args['tag']) && !isset($args['tags']))
					$args['tags'] = $args['tag'];

				$args               = array_merge($default_args, $args);
				$args               = array_intersect_key($args, $default_args);
				$this->args         = $args; // Set arguments property.
				$this->current_user = wp_get_current_user();

				$this->normalize_props(); // Normalize.
				$this->mirror(); // Mirror headers/body.
			}

			/**
			 * Normalizes all class properties.
			 *
			 * @since 150113 First documented version.
			 *
			 * @throws \exception If the `path` or `sha` args are empty.
			 */
			protected function normalize_props()
			{
				# Collect string values.

				if(!($this->path = trim((string)$this->args['path'])))
					throw new \exception(__('Missing path.', $this->plugin->text_domain));

				if(!($this->extension = $this->plugin->utils_fs->extension($this->path)))
					throw new \exception(__('Missing extension.', $this->plugin->text_domain));

				if($this->extension === 'md') $this->content_type = 'text/markdown';
				else if($this->extension === 'html') $this->content_type = 'text/html';
				else throw new \exception(__('Unexpected content type.', $this->plugin->text_domain));

				if(!($this->sha = trim((string)$this->args['sha'])))
					throw new \exception(__('Missing sha.', $this->plugin->text_domain));

				$this->issue = trim((string)$this->args['issue']);

				$this->slug  = trim((string)$this->args['slug']);
				$this->title = trim((string)$this->args['title']);

				$this->categories = trim((string)$this->args['categories']);
				$this->tags       = trim((string)$this->args['tags']);

				$this->author  = trim((string)$this->args['author']);
				$this->status  = trim((string)$this->args['status']);
				$this->pubdate = trim((string)$this->args['pubdate']);

				$this->body    = trim((string)$this->args['body']);
				$this->excerpt = trim((string)$this->args['excerpt']);

				$this->comment_status = trim((string)$this->args['comment_status']);
				$this->ping_status    = trim((string)$this->args['ping_status']);

				$this->toc_enable = trim((string)$this->args['toc_enable']);

				# Convert to post ID, if possible.

				if(($_post_id = $this->plugin->utils_github->path_post_id($this->path)))
					$this->post = get_post($_post_id); // Get the existing article.
				unset($_post_id); // Housekeeping.

				# Determine if post is new; i.e. there's no existing post?

				$this->is_new = empty($this->post); // No post ID yet?

				# Handle new KB articles; i.e. new posts.

				if($this->is_new) // It's a new KB article; i.e. post?
				{
					if(!$this->title) // Get title from the body.
						$this->title = $this->plugin->utils_github->body_title($this->body);

					if(!$this->slug) // Convert title to slug in this case.
						$this->slug = sanitize_title($this->title);

					if(!$this->author) // Use default author in this case.
						$this->author = $this->plugin->options['github_mirror_author'];

					if(!$this->status) // Default status.
						$this->status = 'pending-via-github';

					if(!$this->pubdate) // Use the current time.
						$this->pubdate = 'now'; // `strtotime()` compatible.

					if(!$this->comment_status) // Default comment status.
						$this->comment_status = get_option('default_comment_status');

					if(!$this->ping_status) // Default ping status.
						$this->ping_status = get_option('default_ping_status');
				}
				# Normalize all properties.

				$this->slug = strtolower($this->slug); // Force lowercase.

				$this->categories = preg_split('/,+/', $this->categories, NULL, PREG_SPLIT_NO_EMPTY);
				$this->categories = $this->plugin->utils_string->trim_deep($this->categories);
				$this->categories = $this->plugin->utils_array->remove_emptys($this->categories);

				$this->tags = preg_split('/,+/', $this->tags, NULL, PREG_SPLIT_NO_EMPTY);
				$this->tags = $this->plugin->utils_string->trim_deep($this->tags);
				$this->tags = $this->plugin->utils_array->remove_emptys($this->tags);

				if($this->author && is_numeric($this->author))
				{
					if(($_author_user = \WP_User::get_data_by('id', $this->author)))
						$this->author = (integer)$_author_user->ID; // User ID.

					else if($this->plugin->options['github_mirror_author'] && is_numeric($this->plugin->options['github_mirror_author']))
						$this->author = (integer)$this->plugin->options['github_mirror_author']; // User ID.

					else if(($_author_user = \WP_User::get_data_by('login', $this->plugin->options['github_mirror_author'])))
						$this->author = (integer)$_author_user->ID; // User ID.

					unset($_author_user); // Housekeeping.
				}
				else if($this->author) // Convert username to a user ID.
				{
					if(($_author_user = \WP_User::get_data_by('login', $this->author)))
						$this->author = (integer)$_author_user->ID; // User ID.

					else if($this->plugin->options['github_mirror_author'] && is_numeric($this->plugin->options['github_mirror_author']))
						$this->author = (integer)$this->plugin->options['github_mirror_author']; // User ID.

					else if(($_author_user = \WP_User::get_data_by('login', $this->plugin->options['github_mirror_author'])))
						$this->author = (integer)$_author_user->ID; // User ID.

					unset($_author_user); // Housekeeping.
				}
				$this->author = (integer)$this->author; // Force integer value.
				$this->status = strtolower($this->status); // Force lowercase.

				$this->comment_status = strtolower($this->comment_status);
				$this->ping_status    = strtolower($this->ping_status);

				$this->toc_enable = strtolower($this->toc_enable);

				if($this->body) $this->apply_body_filters();
			}

			/**
			 * Mirrors article/post.
			 *
			 * @since 150113 First documented version.
			 */
			protected function mirror()
			{
				if($this->is_new)
					$this->insert();
				else $this->update();
			}

			/**
			 * Inserts a new article/post.
			 *
			 * @since 150113 First documented version.
			 *
			 * @throws \exception If unable to insert article.
			 */
			protected function insert()
			{
				$data = array(
					'post_type'      => $this->plugin->post_type,
					'post_name'      => $this->slug,
					'post_title'     => $this->title,

					'post_author'    => $this->author,
					'post_status'    => $this->status,

					'post_date'      => date('Y-m-d H:i:s', strtotime($this->pubdate) + (get_option('gmt_offset') * HOUR_IN_SECONDS)),
					'post_date_gmt'  => date('Y-m-d H:i:s', strtotime($this->pubdate)),

					'post_content'   => $this->body,
					'post_excerpt'   => $this->excerpt,

					'comment_status' => $this->comment_status,
					'ping_status'    => $this->ping_status,
				);
				if(!($ID = wp_insert_post(wp_slash($data))) || !($this->post = get_post($ID)))
					throw new \exception(__('Insertion failure.', $this->plugin->text_domain));

				$this->maybe_update_terms(); // Updates terms; i.e. categories/tags.

				$this->plugin->utils_github->update_path($this->post->ID, $this->path);
				$this->plugin->utils_github->update_content_type($this->post->ID, $this->content_type);
				$this->plugin->utils_github->update_sha($this->post->ID, $this->sha);

				if(isset($this->issue[0])) // Only if used by this site.
					$this->plugin->utils_github->update_issue_url($this->post->ID, $this->issue);

				if(isset($this->toc_enable[0])) // Only if specified.
					$this->plugin->utils_github->update_toc_enable($this->post->ID, $this->toc_enable);

				$this->plugin->utils_post->update_popularity($this->post->ID, 0);
			}

			/**
			 * Updating existing article/post.
			 *
			 * @since 150113 First documented version.
			 *
			 * @throws \exception If unable to update article.
			 */
			protected function update()
			{
				$data = array('ID' => $this->post->ID);

				if($this->slug) $data['post_name'] = $this->slug;
				if($this->title) $data['post_title'] = $this->title;

				if($this->author) $data['post_author'] = $this->author;
				if($this->status) $data['post_status'] = $this->status;

				if($this->pubdate && !$this->plugin->utils_date->is_relative($this->pubdate))
				{ // Don't update relative dates; e.g. if pubdate is `now` or `+3 days` that works only for new articles.
					$data['post_date']     = date('Y-m-d H:i:s', strtotime($this->pubdate) + (get_option('gmt_offset') * HOUR_IN_SECONDS));
					$data['post_date_gmt'] = date('Y-m-d H:i:s', strtotime($this->pubdate));
				}
				if($this->body) $data['post_content'] = $this->body;
				if($this->excerpt) $data['post_excerpt'] = $this->excerpt;

				if($this->comment_status) $data['comment_status'] = $this->comment_status;
				if($this->ping_status) $data['ping_status'] = $this->ping_status;

				if(!wp_update_post(wp_slash($data))) // Update failure?
					throw new \exception(__('Update failure.', $this->plugin->text_domain));

				$this->maybe_update_terms(); // Updates terms; i.e. categories/tags.

				$this->plugin->utils_github->update_path($this->post->ID, $this->path);
				$this->plugin->utils_github->update_content_type($this->post->ID, $this->content_type);
				$this->plugin->utils_github->update_sha($this->post->ID, $this->sha);

				if(isset($this->issue[0])) // Only if used by this site.
					$this->plugin->utils_github->update_issue_url($this->post->ID, $this->issue);

				if(isset($this->toc_enable[0])) // Only if specified.
					$this->plugin->utils_github->update_toc_enable($this->post->ID, $this->toc_enable);

				$this->plugin->utils_post->update_popularity($this->post->ID, 0);
			}

			/**
			 * Updates terms; i.e. categories/tags.
			 *
			 * @since 150113 First documented version.
			 *
			 * @throws \exception If unable to update terms.
			 */
			protected function maybe_update_terms()
			{
				if($this->categories) // Updating categories in this case?
					if(is_wp_error($_ = wp_set_object_terms($this->post->ID, $this->categories, $this->plugin->post_type.'_category')))
						throw new \exception(sprintf(__('Category update failure. %1$s', $this->plugin->text_domain), $_->get_error_message()));

				if($this->tags) // Updating tags in this case?
					if(is_wp_error($_ = wp_set_object_terms($this->post->ID, $this->tags, $this->plugin->post_type.'_tag')))
						throw new \exception(sprintf(__('Tag update failure. %1$s', $this->plugin->text_domain), $_->get_error_message()));
			}

			/**
			 * Applies body filters.
			 *
			 * @since 150113 First documented version.
			 */
			protected function apply_body_filters()
			{
				if(!$this->body) // Do we have a body?
					return; // Nothing to do here.

				$this->body = $this->plugin->utils_github->issue_redirect_filter($this->body);
				$this->body = $this->plugin->utils_github->media_library_filter($this->body);

				if($this->plugin->options['github_markdown_parse_enable'] && $this->content_type === 'text/markdown')
				{
					$this->body         = $this->plugin->utils_string->markdown($this->body, array('oembed' => TRUE));
					$this->content_type = 'text/html'; // Raw HTML in this case.
				}
			}
		}
	}
}