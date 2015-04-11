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
		exit('Do NOT access this file directly: '.basename(__FILE__));

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

				$default_args   = array_merge(query::$default_args, array(
					'expand' => TRUE, // Expand properties list?
				));
				$args           = array_merge($default_args, $args);
				$args           = array_intersect_key($args, $default_args);
				$args['expand'] = (boolean)$args['expand']; // Force type.

				$this->query = new query($args); // Perform DB query.
				$this->args  = (object)array_merge($args, (array)$this->query->args);

				if(!$this->plugin->utils_env->doing_exit())
					return; // Stop; invalid context.

				echo $this->response(); // JSON response.
			}

			/**
			 * JSON output handler.
			 *
			 * @since 150410 Improving searches.
			 */
			protected function response()
			{
				$results = array(); // Initialize.

				if($this->query->wp_query->have_posts())
					while($this->query->wp_query->have_posts())
					{
						$this->query->wp_query->the_post();
						$_post = $GLOBALS['post'];

						$_terms = get_the_terms($_post->ID, $this->plugin->post_type.'_tag');
						$_terms = is_wp_error($_terms) ? array() : $_terms;

						$_result = array(
							'id'    => $_post->ID,
							'title' => get_the_title(),
							'url'   => get_permalink(),
						);
						if($this->args->expand)
							$_result = array_merge($_result, array(
								'author'    => get_the_author(),
								'time'      => strtotime($_post->post_date_gmt),

								'visits'    => $this->query->results[$_post->ID]->visits,
								'hearts'    => $this->query->results[$_post->ID]->hearts,
								'relevance' => !empty($this->query->results[$_post->ID]->relevance)
									? $this->query->results[$_post->ID]->relevance : 0,

								'snippet'   => !empty($this->query->results[$_post->ID]->snippet)
									? $this->query->results[$_post->ID]->snippet : '',
							));
						$results[] = $_result; // Push this result onto the stack.
					}
				unset($_post, $_terms, $_result); // Housekeeping.

				wp_reset_postdata(); // Request post globals now.

				return json_encode(compact('results'));
			}
		}
	}
}
