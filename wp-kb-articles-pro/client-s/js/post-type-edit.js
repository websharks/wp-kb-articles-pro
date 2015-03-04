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

			$wpBodyContent = $('#wpbody-content'),

			$postBodyContent = $('#post-body-content'),
			$postBodyContentTitleDiv = $postBodyContent.find('#titlediv'),
			$postBodyContentWrap = $postBodyContent.find('#wp-content-wrap'),
			$postBodyContentEditorTools = $postBodyContent.find('#wp-content-editor-tools'),
			$postBodyContentEditorToolbar = $postBodyContent.find('#ed_toolbar'),
			$postBodyContentTextarea = $postBodyContent.find('#content'),

			vars = window[namespace + '_edit_vars'], i18n = window[namespace + '_edit_i18n'];

		/* ------------------------------------------------------------------------------------------------------------
		 Plugin-specific JS for KB articles in the list of those to edit.
		 ------------------------------------------------------------------------------------------------------------ */

		if(vars.githubProcessorButtonEnable)
		{
			var processorButtonClass = namespaceSlug + '-github-processor',
				$addNewH2 = $wpBodyContent.find('> .wrap > h2 > a.add-new-h2');

			$addNewH2.removeClass('add-new-h2').addClass('button'),
				$addNewH2.before('<a href="#" class="' + processorButtonClass + ' button">' +
				                ' <i class="fa fa-github"></i> ' + i18n.githubProcessorButtonText +
				                '</a>'
				);
			var $processorButton = $wpBodyContent.find('.' + processorButtonClass + '.button'),
				$processorButtonIcon = $processorButton.find('> .fa'), postVars = {};
			postVars[namespace] = {github_processor_via_ajax: true};

			$processorButton.on('click', function(e)
			{
				e.preventDefault(), e.stopImmediatePropagation();

				$processorButtonIcon.removeClass('fa-github').addClass('fa-spinner fa-spin');

				$.post(vars.ajaxEndpoint, postVars, function(data)
				{
					$processorButtonIcon.removeClass('fa-spinner fa-spin').addClass('fa-github'),
						$processorButton.html('<i class="fa fa-check-circle" style="color:#009C59;"></i> ' + i18n.githubProcessorButtonTextComplete),
						setTimeout(function(){ location.reload(); }, 500); // Now reload the page.
				});
			});
		}
		/* ------------------------------------------------------------------------------------------------------------
		 Plugin-specific JS for KB articles in the post editing station.
		 ------------------------------------------------------------------------------------------------------------ */

		if(vars.githubReadonlyContentEnable)
		{
			var readonlyClass = namespaceSlug + '-github-readonly-content-enabled';

			$postBodyContentWrap.addClass(readonlyClass),
				$postBodyContentEditorTools.addClass(readonlyClass),
				$postBodyContentEditorToolbar.addClass(readonlyClass),
				$postBodyContentTextarea.addClass(readonlyClass).attr('readonly', 'readonly');

			$postBodyContentTitleDiv.after(
				'<div class="' + readonlyClass + '-notice">' +
				' <i class="wsi-' + namespaceSlug + '"></i> ' + i18n.githubReadonlyContentEnabled +
				'</div>'
			);
		}
		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
	};
	$document.ready(plugin.onReady); // DOM ready handler.
})(jQuery);