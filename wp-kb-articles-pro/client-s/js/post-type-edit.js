(function($)
{
	'use strict';

	var plugin = {},
		$window = $(window),
		$document = $(document);

	plugin.onReady = function() // jQuery DOM ready event handler.
	{
		/* ------------------------------------------------------------------------------------------------------------
		 Plugin-specific selectors needed by routines below.
		 ------------------------------------------------------------------------------------------------------------ */

		var namespace = 'wp_kb_articles',
			namespaceSlug = 'wp-kb-articles',

			$postBodyContent = $('#post-body-content'),
			$postBodyContentTitleDiv = $postBodyContent.find('#titlediv'),
			$postBodyContentWrap = $postBodyContent.find('#wp-content-wrap'),
			$postBodyContentEditorTools = $postBodyContent.find('#wp-content-editor-tools'),
			$postBodyContentEditorToolbar = $postBodyContent.find('#ed_toolbar'),
			$postBodyContentTextarea = $postBodyContent.find('#content'),

			vars = window[namespace + '_edit_vars'], i18n = window[namespace + '_edit_i18n'];

		/* ------------------------------------------------------------------------------------------------------------
		 Plugin-specific JS for KB articles in the post editing station.
		 ------------------------------------------------------------------------------------------------------------ */

		if(vars.gitHubReadonlyContentEnable) // Read only?
		{
			var readonlyClass = namespaceSlug + '-github-readonly-content-enabled';

			$postBodyContentWrap.addClass(readonlyClass),
				$postBodyContentEditorTools.addClass(readonlyClass),
				$postBodyContentEditorToolbar.addClass(readonlyClass),
				$postBodyContentTextarea.addClass(readonlyClass).attr('readonly', 'readonly');

			$postBodyContentTitleDiv.after(
				'<div class="' + readonlyClass + '-notice">' +
				' <i class="wsi-' + namespaceSlug + '"></i> ' + i18n.gitHubReadonlyContentEnabled +
				'</div>'
			);
		}
		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
	};
	$document.ready(plugin.onReady); // DOM ready handler.
})(jQuery);