(function (mw, $) {
	if ($('.edit-warning-infobox').length && $('#edit-warning-overlay').length ){

		$('.edit-warning-infobox').appendTo('#edit-warning-overlay');
		$('#edit-warning-overlay').appendTo('body');
	}
}(mediaWiki, jQuery));