<div class="[namespace]-footer font-body">
	<div class="-meta">

		<div class="-popularity-tags-feedback">
			<div class="-popularity">
				[popularity]
			</div>
			[if tags]
				<div class="-tags">
					<em>Tagged:</em> [tags]
				</div>
			[endif]
			[if github_enabled_configured && github_issue_feedback_enable]
				<div class="-feedback">
					How can we <a href="[github_issue_url]">improve this article</a>?
					[if current_user_can_edit]
						[if github_path_exists]
							<span class="-edit -github-edit">[<a href="[github_repo_edit_url]">edit</a>]</span>
						[else]
							<span class="-edit -wp-edit">[<a href="[wp_edit_url]">edit</a>]</span>
						[endif]
					[endif]
				</div>
			[endif]
		</div>

		<div class="-author-popularity">
			<div class="-author">
				[if show_avatars]
					<div class="-avatar">
						<a href="[author_posts_url]">[author_avatar]</a>
					</div>
				[endif]
				<div class="-byline">
					<span class="-by">Article written by:</span>
					<a class="-author" href="[author_posts_url]">[author]</a>
					<span class="-date">[date]</span>
				</div>
			</div>
			<a href="#" class="-popularity" data-post-id="[post_id]">
			<span class="-vote">
				<strong>Did you find this article helpful?</strong>
				<i class="fa fa-hand-o-right"></i> Let the author know by clicking here!
			</span>
			<span class="-thank-you">
				<strong>Thank you!</strong> <i class="fa fa-smile-o"></i>
				~ A heart has been given to the author.
			</span>
			</a>
		</div>

	</div>
</div>
