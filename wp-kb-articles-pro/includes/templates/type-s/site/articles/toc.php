<?php
namespace wp_kb_articles;

/**
 * @var plugin   $plugin Plugin class.
 * @var template $template Template class.
 *
 * Other variables made available in this template file:
 *
 * @var \WP_Post $post WordPress post object reference.
 * @var string   $toc The table of contents; i.e. `<ul></ul>` tags.
 *
 * -------------------------------------------------------------------
 * @note In addition to plugin-specific variables & functionality,
 *    you may also use any WordPress functions that you like.
 */
?>
<?php
echo $template->snippet(
	'toc.php', array(

	'[namespace]' => esc_attr(__NAMESPACE__),

	'[post_id]'   => esc_html($post->ID),
	'[permalink]' => esc_attr(get_permalink()),
	'[title]'     => esc_html(get_the_title()),

	'[toc]'       => $toc,
));
?>