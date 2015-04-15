<?php
/**
 * Article Headings
 *
 * @since 150415 Enhancing TOC generation.
 * @copyright WebSharks, Inc. <http://www.websharks-inc.com>
 * @license GNU General Public License, version 3
 */
namespace wp_kb_articles // Root namespace.
{
	if(!defined('WPINC')) // MUST have WordPress.
		exit('Do NOT access this file directly: '.basename(__FILE__));

	if(!class_exists('\\'.__NAMESPACE__.'\\headings'))
	{
		/**
		 * Article Headings
		 *
		 * @since 150415 Enhancing TOC generation.
		 */
		class headings extends abs_base
		{
			/**
			 * Class constructor.
			 *
			 * @since 150415 Enhancing TOC generation.
			 */
			public function __construct()
			{
				parent::__construct();
			}

			/**
			 * Filters the content.
			 *
			 * @since 150415 Enhancing TOC generation.
			 *
			 * @param string $content Input markdown or raw HTML markup.
			 *
			 * @return string Output markup w/ possible TOC added here.
			 */
			public function filter($content)
			{
				$post    = $GLOBALS['post'];
				$content = (string)$content;

				if(!$post || !is_singular($this->plugin->post_type))
					return $content; // Not applicable.

				$hids_enable = (string)get_post_meta($post->ID, __NAMESPACE__.'_hids_enable', TRUE);
				$hids_enable = isset($hids_enable[0]) ? filter_var($hids_enable, FILTER_VALIDATE_BOOLEAN) : NULL;

				if(!isset($hids_enable)) // Use default global setting?
					$hids_enable = $this->plugin->options['hids_generation_enable'];

				$toc_enable = (string)get_post_meta($post->ID, __NAMESPACE__.'_toc_enable', TRUE);
				$toc_enable = isset($toc_enable[0]) ? filter_var($toc_enable, FILTER_VALIDATE_BOOLEAN) : NULL;

				if(!isset($toc_enable) && stripos($content, '%%toc%%') !== FALSE)
					$toc_enable = TRUE; // Content contains a replacement code.

				if(!isset($toc_enable)) // Use default global setting?
					$toc_enable = $this->plugin->options['toc_generation_enable'];

				if($toc_enable) // Depends on HIDs.
					$hids_enable = $toc_enable;

				if(!$hids_enable && !$toc_enable)
					return $content; // Disabled here.

				if(!($tocify = $this->tocify($content)))
					return $content; // Not possible.

				if(!$toc_enable || !$tocify['toc_markup'])
					return $tocify['markup'];

				$toc                 = $tocify['toc_markup'];
				$toc_template_vars   = get_defined_vars();
				$toc_template        = new template('site/articles/toc.php');
				$toc_template_output = $toc_template->parse($toc_template_vars);

				$_this = $this; // Reference needed by closure.

				$embeds_regex_quick_check = // Quick test only.

					'/^'. // Beginning of line.
					'\s*'. // Leading whitespace.
					'(?:\<p\>\s*)?'. // Opening <p> tag?
					'\<(?:iframe|embed|object|video)'. // Embed tag.
					'[\s\/>]/i'; // Confirmation it's an embed tag.

				$embeds_regex = // e.g. <p><iframe></iframe></p>
					// e.g. <iframe/><video /><object></object><embed></embed>
					// e.g. <p><iframe/><video /><embed></embed></p>

					'/^'. // Beginning of line.
					'(?:'. // Recursive group.
					'\s*'. // Leading whitespace.
					'(?:\<p\>\s*)?'. // Opening <p> tag?
					'\<(iframe|embed|object|video)'. // Embed tag.
					'(?:\/\s*\>|\s[^\/>]*\/\s*\>|[\s>].*?\<\/\\1\>)'. // Closing embed tag.
					'(?:\s*\<\/p\>)?'. // Closing </p> tag?
					'\s*'. // Trailing whitespace.
					')+/is'; // One or more.

				if(stripos($tocify['markup'], '%%toc%%') !== FALSE)
				{
					$_tokenize_only = array('shortcodes', 'pre', 'code', 'samp');
					$_spcsm         = $this->plugin->utils_string->spcsm_tokens($tocify['markup'], $_tokenize_only);

					if(stripos($_spcsm['string'], '%%toc%%') !== FALSE)
					{
						$_spcsm['string'] = str_ireplace('%%toc%%', $toc_template_output, $_spcsm['string']);
						$tocify['markup'] = $this->plugin->utils_string->spcsm_restore($_spcsm);

						return $tocify['markup']; // With `%%toc%%` now.
					}
					unset($_tokenize_only, $_spcsm); // Housekeeping.
				}
				if(preg_match($embeds_regex_quick_check, $tocify['markup']))
					return preg_replace_callback($embeds_regex, function ($m) use ($_this, $toc_template_output)
					{
						return $m[0]."\n".$toc_template_output."\n"; // After embeds.

					}, $tocify['markup']);

				return $toc_template_output.$tocify['markup'];
			}

			/**
			 * Generates TOC markup.
			 *
			 * @since 150415 Enhancing TOC generation.
			 *
			 * @param string $md_html Input markdown or raw HTML markup.
			 *
			 * @return array An array with modified `markup` and `toc_markup` also.
			 */
			protected function tocify($md_html)
			{
				$output = array(
					'markup'     => $md_html, // Initialize.
					'toc_markup' => '', // Initialize.
				);
				if(!($output['markup'] = trim((string)$output['markup'])))
					return $output; // Not possible; no markup.

				$headings = array(); // Initialize headings.

				$md_pre_code_regex       = '/(`+).*?\\1/s'; // MD pre/code.
				$md_size_heading_regex   = '/^(?P<size>#+) +(?P<heading>.+)$/m';
				$html_size_heading_regex = '/\<h(?P<size>[1-6])(?:\s+[^<>]*)?\>(?P<heading>.+?)\<\/h\\1\>/is';
				$toc_md_pc_token_regex   = '/%%toc\-md\-pc\-token\-(?P<token>[0-9]+)%%/';

				$has_html_h_tags = stripos($output['markup'], '</h') !== FALSE; // e.g. `</h1>`.
				$has_md_h_tags   = !$has_html_h_tags && preg_match('/(?:^|'."\n".')#+ /', $output['markup']);

				if(!$has_html_h_tags && $has_md_h_tags) // Treat it as markdown?
				{
					$_md_pc_tokens = array(); // Initialize MD pre/code tokens.

					if(strpos($output['markup'], '`') !== FALSE) // Has possible MD pre/code?
						$output['markup'] = preg_replace_callback($md_pre_code_regex, function ($m) use ($_md_pc_tokens)
						{
							$_md_pc_tokens[] = $m[0]; // Collect pre/code token.
							return '%%toc-md-pc-token-'.count($_md_pc_tokens - 1).'%%';
						}, $output['markup']); // Excluding pre/code via tokens.

					$output['markup'] = preg_replace_callback($md_size_heading_regex, function ($m) use (&$headings)
					{
						$size       = strlen($m['size']);
						$heading    = strip_tags($m['heading']);
						$heading    = trim(preg_replace('/\s+/', ' ', $heading));
						$crc32b     = hash('crc32b', strtolower($heading));
						$headings[] = compact('size', 'heading', 'crc32b');

						return '<a id="'.esc_attr('toc-'.$crc32b).'" style="display:block;"></a>'."\n".$m[0];
					}, $output['markup']); // Excluding pre/code via tokens.

					if(strpos($output['markup'], '%%toc-md-pc-token-') !== FALSE) // Has MD pre/code token(s)?
						$output['markup'] = preg_replace_callback($toc_md_pc_token_regex, function ($m) use ($_md_pc_tokens)
						{
							return $_md_pc_tokens[(integer)$m['token']]; # Restore pre/code.
						}, $output['markup']); // Restoring pre/code via tokens.

					unset($_md_pc_tokens); // Housekeeping.
				}
				else if($has_html_h_tags) // Treat it as raw HTML markup in this case.
				{
					$output['markup'] = preg_replace_callback($html_size_heading_regex, function ($m) use (&$headings)
					{
						$size       = (integer)$m['size'];
						$heading    = strip_tags($m['heading']);
						$heading    = trim(preg_replace('/\s+/', ' ', $heading));
						$crc32b     = hash('crc32b', strtolower($heading));
						$headings[] = compact('size', 'heading', 'crc32b');

						return '<a id="'.esc_attr('toc-'.$crc32b).'" style="display:block;"></a>'."\n".$m[0];
					}, $output['markup']); // Pure HTML markup in this case.
				}
				if(!$headings) // Do we have headings to iterate now?
					return $output; // All done in this case.

				# Construct `<ul>` tags now w/ list items for TOC markup.

				$_child_ul_tags_open  = 0; // Initialize.
				$_prev_heading        = NULL; // Initialize.
				$output['toc_markup'] = '<ul>'; // Initialize.

				foreach($headings as $_key => &$_heading) // Iterate.
				{
					if($_prev_heading) // After the first heading.
					{
						if($_heading['size'] > $_prev_heading['size'])
						{
							$output['toc_markup'] .= '<ul>'; // Child `<ul>` tag.
							$_child_ul_tags_open++; // Increase `<ul>` child counter.
						}
						else if($_heading['size'] < $_prev_heading['size'])
						{
							$output['toc_markup'] .= '</li>'; // Close previous.
							if($_child_ul_tags_open) // `<ul>` child counter is > `0`?
								$output['toc_markup'] .= str_repeat('</ul></li>', $_child_ul_tags_open);
							$_child_ul_tags_open = 0; // Reset the `<ul>` child counter.
						}
						else if($_heading['size'] === $_prev_heading['size'])
							$output['toc_markup'] .= '</li>'; // Close.
					}
					# Add this list item now. We always do this.

					$output['toc_markup'] .=  // List item.
						'<li>'. // This is left open in case of children.
						'<a href="'.esc_attr('#toc-'.$_heading['crc32b']).'" title="'.esc_attr($_heading['heading']).'"'.
						' data-toggle="tooltip" data-delay=\'{"show":1000,"hide":0}\'>'.
						esc_html($_heading['heading']).
						'</a>';
					$_prev_heading = &$_heading; // Reference previous.
				}
				unset($_key, $_heading); // Housekeeping.

				$output['toc_markup'] .= '</li></ul>'; // Close now.

				return $output; // `markup` and `toc_markup` also.
			}
		}
	}
}
