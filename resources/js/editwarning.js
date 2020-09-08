(function (mw, $) {
	/*
	 * Prevent the VE from Loading resources without reloading the page
	 * This is needed for the EditWarning to ensure that
	 * the PageBeforeDisplay hook is triggered every time a page is edited
	 */

	$(document).ready(function () {

		// make sure that ext.visualEditor.desktopArticleTarget.init module is loaded before we try to remove the event from the "edit" link
		mw.loader.using(['ext.visualEditor.desktopArticleTarget.init']).then(function () {
			// remove .ve-target event from element and reload the page by href value of "edit" button

			$('#ca-ve-edit').off('.ve-target').on('click.ve-target', function (e) {
				$.ajax({
					url: mw.util.wikiScript('api'),
					data: { action:'editwarning', format:'json', ewaction:'lock', user: mw.config.get('wgUserName'), articleid: mw.config.get('wgArticleId') },
					error: function (err) {
						console.error('error: ', err);
					},
					fail: function (fail) {
						console.log('fail:', fail);
					}
				});
			});


			// handle ve edit on sections
			$('.mw-editsection-visualeditor').off('click').on('click', function (e) {
				$.ajax({
					url: mw.util.wikiScript('api'),
					data: { action:'editwarning', format:'json', ewaction:'lock', user: mw.config.get('wgUserName'), articleid: mw.config.get('wgArticleId') },
					error: function (err) {
						console.error('error: ', err);
					},
					fail: function (fail) {
						console.log('fail:', fail);
					}
				});
			});
		});

		// remove entry from lock table and warning/notice box if user leaves VE
		mw.hook('ve.deactivate').add(function () {
			$.ajax({
				url: mw.util.wikiScript('api'),
				data: { action:'editwarning', format:'json', ewaction:'unlock', user: mw.config.get('wgUserName'), articleid: mw.config.get('wgArticleId') },
				error: function (err) {
					console.error('error: ', err);
				},
				fail: function (fail) {
					console.log('fail:', fail);
				}
			});

			$('#edit-warning-overlay').remove();
			$('.edit-warning-infobox').remove();
		});

	});

}(mediaWiki, jQuery));