<?php
namespace wp_kb_articles;

/**
 * @var plugin      $plugin Plugin class.
 * @var template    $template Template class.
 *
 * Other variables made available in this template file:
 *
 * @var \stdClass   $attr Parsed attributes.
 * @var query       $query Query class instance.
 * @var \stdClass[] $tab_categories Tab categories.
 * @var \stdClass[] $tags An array of all KB tags.
 * @var array       $filters Filters that apply.
 *
 * -------------------------------------------------------------------
 * @note In addition to plugin-specific variables & functionality,
 *    you may also use any WordPress functions that you like.
 */
?>
<div class="<?php echo esc_attr(__NAMESPACE__.'-list'); ?>">

	<div class="-navigation">
		<?php if($tab_categories): ?>
			<div class="-tabs">
				<ul class="-list">
					<?php foreach($tab_categories as $_term): ?>
						<li>
							<a href="<?php echo esc_attr($plugin->utils_url->sc_list($attr->url, array('category' => $_term->slug, 'page' => 1))); ?>"
							   data-category="<?php echo esc_attr($_term->term_id); ?>"
								<?php if(in_array((integer)$_term->term_id, $attr->category, TRUE)): ?> class="-active"<?php endif; ?>
								><?php echo esc_html($_term->name); ?></a>
						</li>
					<?php endforeach; // End category iteration.
					unset($_term); // Housekeeping. ?>
				</ul>
			</div>
		<?php endif; ?>

		<?php if($tags): ?>
			<div class="-tags">
				<div class="-filter">
					<a href="#"><?php echo __('Filter by Tag', $plugin->text_domain); ?></a>
				</div>
				<div class="-overlay">
					<div class="-selected">
						<i class="fa fa-tags"></i>
						<strong><?php echo __('Tags Selected', $plugin->text_domain); ?>:</strong>
						<?php $_tags = ''; // Initialize.

						foreach($attr->tag as $_term_id) // Iterate tags in query.
							foreach($tags as $_term) if($_term_id === (integer)$_term->term_id)
							{
								$_tags .= ($_tags ? ', ' : '').esc_html($_term->name);
								break; // Break the inner iteration; we found this tag.
							}
						if(!$_tags) // There are no tags selected right now?
							$_tags = '<strong>'.__('None', $plugin->text_domain).'</strong>'.
							         ' '.__('(select some tags) and click `filter by tags`', $this->plugin->text_domain);
						echo $_tags; // Currently selected tags.

						unset($_term, $_tags); // Housekeeping. ?>
					</div>
					<ul class="-list">
						<?php foreach($tags as $_term): ?>
							<li><a href="#" data-tag="<?php echo esc_attr($_term->term_id); ?>"
									<?php if(in_array((integer)$_term->term_id, $attr->tag, TRUE)): ?> class="-active"<?php endif; ?>
									><?php echo esc_html($_term->name); ?></a></li>
						<?php endforeach; // End tag iteration.
						unset($_term); // Housekeeping. ?>
					</ul>
					<button type="button" class="-button">
						<?php echo __('Filter by Tags', $plugin->text_domain); ?>
					</button>
				</div>
			</div>
		<?php endif; ?>

	</div>

	<?php if($filters): ?>
		<div class="-filters">
			<div class="-apply">
				<?php echo __('Showing all KB articles matching:', $plugin->text_domain); ?>
			</div>
			<ul>
				<?php $_clear = __('clear', $plugin->text_domain); ?>
				<?php foreach($filters as $_filter => $_by): ?>
					<?php if($_by): // This filter applies? ?>
						<li>
							<?php switch($_filter) // i.e. `author`, `category`, `tag`, `q`.
							{
								case 'author': // Filtered by author?
									echo '<i class="fa fa-user fa-fw"></i> '.sprintf(__('Author: %1$s', $plugin->text_domain), $_by).' <a href="#" data-click-author="" class="-clear">'.$_clear.'</a>';
									break; // Break switch handler.

								case 'category': // Filtered by category?
									echo '<i class="fa fa-folder-open fa-fw"></i> '.sprintf(__('Category: %1$s', $plugin->text_domain), $_by).' <a href="#" data-click-category="" class="-clear">'.$_clear.'</a>';
									break; // Break switch handler.

								case 'tag': // Filtered by tag?
									echo '<i class="fa fa-tag fa-fw"></i> '.sprintf(__('Tag: %1$s', $plugin->text_domain), $_by).' <a href="#" data-click-tag="" class="-clear">'.$_clear.'</a>';
									break; // Break switch handler.

								case 'q': // Filtered by search terms?
									echo '<i class="fa fa-search fa-fw"></i> '.sprintf(__('Search for: %1$s', $plugin->text_domain), $_by).' <a href="#" data-click-q="" class="-clear">'.$_clear.'</a>';
									break; // Break switch handler.
							} ?>
						</li>
					<?php endif; ?>
				<?php endforeach; // End filter loop.
				unset($_clear, $_filter, $_by); // Housekeeping. ?>
			</ul>
		</div>
	<?php endif; ?>

	<div class="-articles">
		<?php if($query->wp_query->have_posts()): ?>
			<?php while($query->wp_query->have_posts()): $query->wp_query->the_post(); ?>
				<?php $_post = $GLOBALS['post']; ?>
				<div class="-article">
					<?php $_tags = ''; // Initialize.
					if(($_terms = get_the_terms($_post->ID, $plugin->post_type.'_tag')) && !is_wp_error($_terms)):
						foreach($_terms as $_term) // Iterate the tags that it has.
							$_tags .= ($_tags ? ', ' : ''). // Comma-delimited tags.
							          '<a href="#" data-click-tag="'.esc_attr($_term->term_id).'">'.esc_attr($_term->name).'</a>';
					endif; // End if article has tags.
					unset($_terms, $_term); // Housekeeping.

					echo $template->snippet(
						'list-article.php', array(

						'tags'                   => $_tags,
						'comments_open'          => comments_open(),
						'comments_number'        => get_comments_number(),
						'show_avatars'           => get_option('show_avatars'),
						'has_snippet'            => !empty($query->results[$_post->ID]->snippet),
						'current_user_can_edit'  => current_user_can('edit_post', $_post->ID),

						'[namespace]'            => esc_attr(__NAMESPACE__),

						'[post_id]'              => esc_html($_post->ID),
						'[permalink]'            => esc_attr(get_permalink()),
						'[title]'                => $plugin->utils_markup->hilite_search_terms($attr->q, esc_html(get_the_title())),
						'[snippet]'              => !empty($query->results[$_post->ID]->snippet) // Only if we have a snippet.
							? $plugin->utils_markup->hilite_search_terms($attr->q, esc_html($query->results[$_post->ID]->snippet)) : '',

						'[popularity]'           => esc_html($plugin->utils_post->get_popularity($_post->ID)),

						'[author_id]'            => esc_attr(get_the_author_meta('ID')),
						'[author_posts_url]'     => esc_attr(get_author_posts_url(get_the_author_meta('ID'))),
						'[author_avatar]'        => get_avatar(get_the_author_meta('ID'), 32),
						'[author]'               => esc_html(get_the_author()),

						'[tags]'                 => $_tags, // Contains raw HTML markup.

						'[comments_number_text]' => esc_html(get_comments_number_text()),
						'[pub_date]'             => esc_html(get_the_date()),
						'[mod_date]'             => esc_html(get_the_modified_date()),
					));
					unset($_tags); // Housekeeping. ?>
				</div>
			<?php endwhile; ?>
			<?php unset($_post); ?>
			<?php wp_reset_postdata(); ?>
		<?php else: ?>
			<p><i class="fa fa-meh-o"></i> <?php echo __('No articles matching search criteria.', $plugin->text_domain); ?></p>
		<?php endif; ?>
	</div>

	<?php if($query->pagination->total_pages > 1): ?>
		<div class="-pagination">
			<div class="-pages">
				<ul class="-list">
					<?php if($query->pagination->current_page > 1): // Create a previous page link? ?>
						<li class="-prev -prev-next">
							<a href="<?php echo esc_attr($plugin->utils_url->sc_list($attr->url, array('page' => $query->pagination->current_page - 1))); ?>"
							   data-click-page="<?php echo esc_attr($query->pagination->current_page - 1); ?>">&laquo; <?php echo __('prev', $plugin->text_domain); ?></a>
						</li>
					<?php else: // Not possible; this is the first page. ?>
						<li class="-prev -prev-next">
							<a href="#" class="-disabled">&laquo; <?php echo __('prev', $plugin->text_domain); ?></a>
						</li>
					<?php endif; ?>

					<?php // Individual page links now.
					$_max_page_links           = 15; // Max individual page links to show on each page.
					$_page_links_start_at_page = // This is a mildly complex calculation that we can do w/ help from the plugin class.
						$plugin->utils_db->pagination_links_start_page($query->pagination->current_page, $query->pagination->total_pages, $_max_page_links);

					for($_i = 1, $_page = $_page_links_start_at_page; $_i <= $_max_page_links && $_page <= $query->pagination->total_pages; $_i++, $_page++): ?>
						<li>
							<a href="<?php echo esc_attr($plugin->utils_url->sc_list($attr->url, array('page' => $_page))); ?>"
							   data-click-page="<?php echo esc_attr($_page); ?>"
								<?php if($_page === $query->pagination->current_page): ?> class="-active"<?php endif; ?>
								><?php echo esc_html($_page); ?></a>
						</li>
					<?php endfor; // End the iteration of page links.
					unset($_max_page_links, $_page_links_start_at_page, $_page, $_i); // Housekeeping. ?>

					<?php if($query->pagination->current_page < $query->pagination->total_pages): // Create a next page link? ?>
						<li class="-next -prev-next">
							<a href="<?php echo esc_attr($plugin->utils_url->sc_list($attr->url, array('page' => $query->pagination->current_page + 1))); ?>"
							   data-click-page="<?php echo esc_attr($query->pagination->current_page + 1); ?>"><?php echo __('next', $plugin->text_domain); ?> &raquo;</a>
						</li>
					<?php else: // Not possible; this is the last page. ?>
						<li class="-next -prev-next">
							<a href="#" class="-disabled"><?php echo __('next', $plugin->text_domain); ?> &raquo;</a>
						</li>
					<?php endif; ?>
				</ul>
			</div>
		</div>
	<?php endif; ?>

	<div class="-hidden">
		<div class="-attr" data-attr="<?php echo esc_attr($plugin->utils_enc->xencrypt(serialize($attr->strings))); ?>"></div>
		<div class="-attr-page" data-attr="<?php echo esc_attr($attr->strings['page']); ?>"></div>
		<div class="-attr-orderby" data-attr="<?php echo esc_attr($attr->strings['orderby']); ?>"></div>
		<div class="-attr-author" data-attr="<?php echo esc_attr($attr->strings['author']); ?>"></div>
		<div class="-attr-category" data-attr="<?php echo esc_attr($attr->strings['category']); ?>"></div>
		<div class="-attr-tag" data-attr="<?php echo esc_attr($attr->strings['tag']); ?>"></div>
		<div class="-attr-q" data-attr="<?php echo esc_attr($attr->strings['q']); ?>"></div>
	</div>

</div>
