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
<div class="<?php echo esc_attr(__NAMESPACE__.'-toc'); ?> font-body">

	<h4 class="-title">
		<?php echo __('Table of Contents', $plugin->text_domain); ?>
	</h4>

	<div class="-list">
		<?php echo $toc; ?>
	</div>

</div>