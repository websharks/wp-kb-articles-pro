<?php
/**
 * Tax Query API Handler
 *
 * @since 150421 Adding tax query API.
 * @copyright WebSharks, Inc. <http://www.websharks-inc.com>
 * @license GNU General Public License, version 3
 */
namespace wp_kb_articles // Root namespace.
{
	if(!defined('WPINC')) // MUST have WordPress.
		exit('Do NOT access this file directly: '.basename(__FILE__));

	if(!class_exists('\\'.__NAMESPACE__.'\\tax_query_api'))
	{
		/**
		 * Tax Query API Handler
		 *
		 * @since 150421 Adding tax query API.
		 */
		class tax_query_api extends abs_base
		{
			/**
			 * Array of term results.
			 *
			 * @since 150421 Adding tax query API.
			 *
			 * @var array Term results.
			 */
			protected $terms;

			/**
			 * Query args.
			 *
			 * @since 150421 Adding tax query API.
			 *
			 * @var \stdClass Query args.
			 */
			protected $args;

			/**
			 * Term args.
			 *
			 * @since 150421 Adding tax query API.
			 *
			 * @var array Term args.
			 */
			protected $term_args;

			/**
			 * Class constructor.
			 *
			 * @since 150421 Adding tax query API.
			 *
			 * @param array $args Query arguments.
			 */
			public function __construct(array $args = array())
			{
				parent::__construct();

				$this->term_args = array(
					'orderby'           => 'name',
					'order'             => 'ASC',
					'hide_empty'        => FALSE,
					'exclude'           => array(),
					'exclude_tree'      => array(),
					'include'           => array(),
					'number'            => 2500,
					'fields'            => 'all',
					'slug'              => '',
					'parent'            => '',
					'child_of'          => 0,
					'get'               => '',
					'name__like'        => '',
					'description__like' => '',
					'hierarchical'      => TRUE,
					'pad_counts'        => FALSE,
					'offset'            => '',
					'search'            => '',
					'cache_domain'      => __CLASS__,
				);
				$default_args    = array(
					'type'   => 'tag',
					'q'      => '',
					'expand' => TRUE,
				);
				$args            = array_merge($default_args, $args);
				$args            = array_intersect_key($args, $default_args);

				if(!in_array($args['type'], array('tag', 'category'), TRUE))
					$args['type'] = 'tag'; // Force valid type.

				$args['q'] = trim((string)$args['q']);

				if($args['expand'] === '') // Empty string?
					$args['expand'] = $default_args['expand'];

				if(is_array($args['expand'])) // Force strings[].
					$args['expand'] = array_map('strtolower', $args['expand']);

				else if(is_string($args['expand']) && !preg_match('/^(?:1|on|true|yes|0|off|false|no)$/i', $args['expand']))
					$args['expand'] = preg_split('/,+/', strtolower($args['expand']), NULL, PREG_SPLIT_NO_EMPTY);

				else $args['expand'] = filter_var($args['expand'], FILTER_VALIDATE_BOOLEAN);

				$this->args                    = (object)$args;
				$this->term_args['name__like'] = $this->args->q;

				if(!is_array($this->terms = get_terms($this->plugin->post_type.'_'.$this->args->type, $this->term_args)))
					$this->terms = array(); // Force array.

				if(!$this->plugin->utils_env->doing_exit())
					return; // Stop; invalid context.

				$this->output_response(); // JSON response.
			}

			/**
			 * JSON|JSONP output handler.
			 *
			 * @since 150421 Adding tax query API.
			 */
			protected function output_response()
			{
				# Collect all results.

				$results = array(); // Initialize.

				foreach($this->terms as $_term)
				{
					$_i           = count($results);
					$results[$_i] = array(); // Initialize.
					$_result      = &$results[$_i]; // By reference.

					$_result['id']   = $_term->term_id;
					$_result['name'] = $this->decode($_term->name);
					$_result['slug'] = $this->decode($_term->slug);
					$_result['url']  = get_term_link($_term);

					if(!$this->expand()) // Not expanding?
						continue; // Nothing more to do.

					if($this->expand('result.description'))
						$_result['description'] = $this->decode($_term->description);

					if($this->expand('result.parent'))
						$_result['parent_id'] = $this->decode($_term->parent);

					if($this->expand('result.count'))
						$_result['count'] = $this->decode($_term->count);
				}
				unset($_i, $_term, $_result); // Housekeeping.

				# Formulate the output response data.

				$response = compact('results');

				if($this->expand('args'))
					$response['args'] = $this->args;

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
			 * @since 150421 Adding tax query API.
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
			 * @since 150421 Adding tax query API.
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
