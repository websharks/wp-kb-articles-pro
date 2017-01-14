<?php
/**
 * Query API Handler
 *
 * @since 150411 Adding Query API.
 * @copyright WebSharks, Inc. <http://www.websharks-inc.com>
 * @license GNU General Public License, version 3
 */
namespace wp_kb_articles // Root namespace.
{
	if(!defined('WPINC')) // MUST have WordPress.
		exit('Do NOT access this file directly.');

	if(!class_exists('\\'.__NAMESPACE__.'\\query_api'))
	{
		/**
		 * Query API Handler
		 *
		 * @since 150411 Adding Query API.
		 */
		class query_api extends abs_base
		{
			/**
			 * Query class instance.
			 *
			 * @since 150411 Adding Query API.
			 *
			 * @var query Query class instance.
			 */
			protected $query;

			/**
			 * Query args.
			 *
			 * @since 150411 Adding Query API.
			 *
			 * @var \stdClass Query args.
			 */
			protected $args;

			/**
			 * Class constructor.
			 *
			 * @since 150410 Improving searches.
			 *
			 * @param array $args Query arguments.
			 */
			public function __construct(array $args = array())
			{
				parent::__construct();

				$default_args = array_merge(
					query::$default_args, array(
					'expand' => TRUE, // Expand properties?
				));
				$args         = array_merge($default_args, $args);
				$args         = array_intersect_key($args, $default_args);

				if($args['expand'] === '') // Empty string?
					$args['expand'] = $default_args['expand'];

				if(is_array($args['expand'])) // Force strings[].
					$args['expand'] = array_map('strtolower', $args['expand']);

				else if(is_string($args['expand']) && !preg_match('/^(?:1|on|true|yes|0|off|false|no)$/i', $args['expand']))
					$args['expand'] = preg_split('/,+/', strtolower($args['expand']), NULL, PREG_SPLIT_NO_EMPTY);

				else $args['expand'] = filter_var($args['expand'], FILTER_VALIDATE_BOOLEAN);

				$this->query                   = new query($args); // Perform DB query.
				$this->args                    = (object)array_merge($args, (array)$this->query->args);
				$this->args->strings['expand'] = is_array($this->args->expand) // Implode these?
					? implode(',', $this->args->expand) : ($this->args->expand ? 'true' : 'false');

				if(!$this->plugin->utils_env->doing_exit())
					return; // Stop; invalid context.

				$this->output_response(); // JSON response.
			}

			/**
			 * JSON|JSONP output handler.
			 *
			 * @since 150410 Improving searches.
			 */
			protected function output_response()
			{
				# Collect all results.

				$results = array(); // Initialize.

				while($this->query->wp_query->have_posts())
				{
					$this->query->wp_query->the_post();
					$_post = $GLOBALS['post'];

					$_i           = count($results);
					$results[$_i] = array(); // Initialize.
					$_result      = &$results[$_i]; // By reference.

					$_result['id']    = $_post->ID;
					$_result['title'] = $this->decode(get_the_title());
					$_result['url']   = get_permalink();

					if(!$this->expand()) // Not expanding?
						continue; // Nothing more to do.

					if($this->expand('result.snippet'))
						$_result['snippet'] = $this->decode($this->query->results[$_post->ID]->snippet);

					if($this->expand('result.relevance'))
						$_result['relevance'] = $this->query->results[$_post->ID]->relevance;

					if($this->expand('result.hearts'))
						$_result['hearts'] = $this->query->results[$_post->ID]->hearts;

					if($this->expand('result.visits'))
						$_result['visits'] = $this->query->results[$_post->ID]->visits;

					if($this->expand('result.views'))
						$_result['views'] = $this->query->results[$_post->ID]->views;

					if($this->expand('result.last_view_time'))
						$_result['last_view_time'] = $this->query->results[$_post->ID]->last_view_time;

					if($this->expand('result.time'))
						$_result['time'] = strtotime($_post->post_date_gmt);

					if($this->expand('result.author'))
						$_result['author'] = array(
							'id'           => get_the_author_meta('ID'),
							'login'        => get_the_author_meta('login'),
							'nicename'     => get_the_author_meta('nicename'),
							'nickname'     => get_the_author_meta('nickname'),
							'first_name'   => get_the_author_meta('first_name'),
							'last_name'    => get_the_author_meta('last_name'),
							'display_name' => get_the_author_meta('display_name'),
						);
					if($this->expand('result.categories')) // Collect categories?
					{
						$_result['categories'] = array(); // Initialize array.
						$_terms                = get_the_terms($_post->ID, $this->plugin->post_type.'_category');
						$_terms                = is_wp_error($_terms) ? array() : $_terms;

						foreach($_terms as $_term) // Collect category slugs and names.
							$_result['categories'][] = array(
								'id'   => $_term->term_id,
								'slug' => $_term->slug,
								'name' => $_term->name,
							);
					}
					if($this->expand('result.tags')) // Collect tag names?
					{
						$_result['tags'] = array(); // Initialize array.
						$_terms          = get_the_terms($_post->ID, $this->plugin->post_type.'_tag');
						$_terms          = is_wp_error($_terms) ? array() : $_terms;

						foreach($_terms as $_term) // Collect category slugs and names.
							$_result['tags'][] = array(
								'id'   => $_term->term_id,
								'slug' => $_term->slug,
								'name' => $_term->name,
							);
					}
				}
				wp_reset_postdata(); // Just a little housekeeping.
				unset($_i, $_post, $_terms, $_result, $_terms, $_term, $_tags);

				# Formulate the output response data.

				$response = compact('results');

				if($this->expand('args'))
					$response['args'] = $this->args;

				if($this->expand('pagination'))
					$response['pagination'] = $this->query->pagination;

				# Output the response data; i.e., JSON or JSONP output handling.

				$callback = $this->not_empty_coalesce( // Any of these.
					$_REQUEST['jsonp'], $_REQUEST['_jsonp'], $_REQUEST['__jsonp'],
					$_REQUEST['callback'], $_REQUEST['_callback'], $_REQUEST['__callback']
				);
				if($callback && ($callback = trim(stripslashes($callback))))
				{
					header('Content-Type: application/javascript; charset=UTF-8');
					echo $callback.'('.json_encode($response).');';
				}
				header('Content-Type: application/json; charset=UTF-8');
				echo json_encode($response); // Default behavior.
			}

			/**
			 * Expanding; or expanding a particular property?
			 *
			 * @since 150410 Improving searches.
			 *
			 * @param string $property Optional; a property to check.
			 *
			 * @return boolean `TRUE` if expanding.
			 */
			protected function expand($property = '')
			{
				if(!$this->args->expand)
					return FALSE; // No!

				if($this->args->expand === TRUE)
					return TRUE; // Expand all.

				if(!($property = trim((string)$property)))
					return (boolean)$this->args->expand;

				if(strpos($property, 'result.') === 0)
					if(in_array('results', (array)$this->args->expand, TRUE))
						return TRUE; // Yes, expand all result properties.

				if(in_array($property, (array)$this->args->expand, TRUE))
					return TRUE; // Yes, expand this result property.

				return FALSE; // Default return value.
			}

			/**
			 * Alias for HTML entities decode.
			 *
			 * @since 150410 Improving searches.
			 *
			 * @param string $string String to decode.
			 *
			 * @return string Decoded string.
			 */
			protected function decode($string)
			{
				return $this->plugin->utils_string->html_entities_decode($string);
			}
		}
	}
}
