jQuery(document).ready(function($) {

	// $('.post_video_gallery_video').nyroModal();
	/*
	 $('a[rel*=facebox]').facebox({
     closeImage : 'wp-content/plugins/video-gallery/img/closelabel.gif',
     loadingImage : 'wp-content/plugins/video-gallery/img/loading.gif'
   });
	 */

	$('.videoPlayerHidden').click(function(e) {
		e.preventDefault();
		var content = $("#hidden_video_" + $(this).attr('id').split('_')[1]).html();
		$.nyroModalManual({
			windowResize: true,
			resizing: true,
			content: content,
			minWidth: 0,
			minHeight: 0
		});
		return false;
	});

});



