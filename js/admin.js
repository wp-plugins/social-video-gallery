function _result2Array(r) {
	r = r.substring(0, r.length-1);
	r = r.split('::');
	return r;
}

function _log(str) {
    if(typeof console != 'undefined')
        console.log(str);
}


var add_gallery_string = objectL10n.addGallery;
var close_string = objectL10n.close;


jQuery(document).ready(function($) {

	// Add gallery link
	$("#vg_add_gallery_link").click(function() {
		var e = $("#vg_add_gallery_div");
		var s = ( e.css('display') == 'none' ) ? close_string : add_gallery_string;
		$(this).attr('value', s);
		$("#vg_add_gallery_div").toggle();
	});

	// Add gallery form submit
	$("#vg_add_gallery_form").submit(function(e) {
		e.preventDefault();

		var videoGalleries = '';

		$('.vg_add_gallery_url').each(function(index) {
			var val = $("#vg_add_gallery_url_" + (index + 1)).val();
			if( val.length ) {
				videoGalleries += (index + 1) + '=' + val + '::';
			}
		});

		$.post('admin-ajax.php', {
			action : 'vg_add_gallery',
			gallery_name : $("#gallery_name").val(),
			video_width : $("#add_gallery_video_width").val(),
			video_height : $("#add_gallery_video_height").val(),
			thumbnail_width : $("#add_video_gallery_thumb_width").val(),
			thumbnail_height : $("#add_video_gallery_thumb_height").val(),
			video_galleries : videoGalleries
		}, function(r) {
			r = _result2Array(r);
			if( r[0] == 1 ) {
				document.getElementById('vg_add_gallery_form').reset();
				$("#vg_add_gallery_message").text(r[1]).addClass('success').css('display','block');
				$.scrollTo("#vg_add_gallery_message", 1000, {
					margin: true
				});
				setTimeout(function() {
					$("#vg_add_gallery_message").fadeOut(1000, function() {
						$(this).text('').removeClass('success').show().css('display','none');
					});
				}, 5000);
                
                setTimeout(function() {
                    // document.location.reload();
                }, 2000);
                
			} else {
				$("#vg_add_gallery_message").text(r[1]);
			}
		});
	});

	// Delete gallery link
	$('.gallery_delete_link').click(function(e) {
		e.preventDefault();
		var object_id = $(this).attr("id");
		var object_id_array = object_id.split('_');
		var gallery_id = object_id_array[3];
    
		$.post('admin-ajax.php', {
			action : 'vg_delete_gallery',
			gallery_id : gallery_id
		}, function(r) {
			r = _result2Array(r);
			if( r[0] ) {
				$("#gallery_" + gallery_id).fadeOut(1000, function() {
					$(this).remove();
				})
			}
		});

	});

	// Edit gallery link
	$('.gallery_edit_link').click(function(e) {
		e.preventDefault();
		var object_id = $(this).attr("id");
		var object_id_array = object_id.split('_');
		var gallery_id = object_id_array[3];
		if($("#gallery_edit_" + gallery_id).length == 1) {
			return;
		}
		
		$.post('admin-ajax.php', {
			action : 'vg_edit_gallery_form',
			gallery_id : gallery_id
		}, function(r) {
			r = _result2Array(r);
			if( r[0] ) {
				$("#gallery_" + gallery_id).after(r[1]);
			}
		});

	});

	// Gallery edit add video button
	$('.gallery_edit_add_video_button').live('click', function() {
		var object_id = $(this).attr("id");
		var object_id_array = object_id.split('_');
		var gallery_id = object_id_array[5];
		var how_many = parseInt($("#gallery_edit_add_video_count_" +gallery_id).val());

		var appendString = '<table class="form-table">';
		appendString += '<tbody>';
		for( var i = 0; i < how_many; i++ ) {
			appendString += '<tr valign="top">';
			appendString += '<th scope="col" style="border:none;">Video ' + (i+1) + '</th>';
			appendString += '<td style="border:none;"><input type="text" class="gallery_edit_add_video_url" id="gallery_edit_add_video_url_'+ ( i + 1 ) +'" /></td>';
			appendString += '</tr>';
		}
		appendString += '</tbody>';
		appendString += '</table>';

		$("#gallery_edit_add_video_videos_" + gallery_id).html(appendString);

	});

	// Gallery edit add video button submit
	$('.gallery_edit_add_video_submit_button').live('click', function() {
		var object_id = $(this).attr('id');
		var object_id_array = object_id.split('_');
		var gallery_id = object_id_array[6];

		var videos = '';

		$('.gallery_edit_add_video_url').each(function(index) {
			var val = $("#gallery_edit_add_video_url_" + (index + 1)).val();
			if( val.length ) {
				videos += (index + 1) + '=' + val + '::';
			}
		});

		var gallery_name = $("#vg_edit_gallery_gallery_name_" + gallery_id).val();

		$.post('admin-ajax.php', {
			action : 'vg_edit_gallery_submit',
			videos : videos,
			gallery_name : gallery_name,
			video_width : $("#gallery_edit_video_width_" + gallery_id).val(),
			video_height : $("#gallery_edit_video_height_" + gallery_id).val(),
			thumbnail_width : $("#gallery_edit_thumb_width_" + gallery_id).val(),
			thumbnail_height : $("#gallery_edit_thumb_height_" + gallery_id).val(),
			gallery_id : gallery_id
		}, function(r) {
			r = _result2Array(r);
			if( r[0] ) {
				$("#gallery_" + gallery_id).css("backgroundColor","#85FF87");
				$("#gallery_edit_" + gallery_id).animate({
					height: 0
				}, 1000, function() {
					$(this).remove();
					$("#gallery_" + gallery_id).animate({
						backgroundColor: "#fff"
					},"slow", function() {
						var gallery_video_count = $("#gallery_video_count_" + gallery_id);
						gallery_video_count.text(parseInt(gallery_video_count.text()) + parseInt(r[1]));
						$("#gallery_list_gallery_name_" + gallery_id).text(gallery_name);
					});
				});

			} else {
				$("#gallery_" + gallery_id).css("backgroundColor","#FF8585");
				$("#gallery_" + gallery_id).animate({
					backgroundColor: "#fff"
				},"slow");
			}
		});
	});

	// Edit gallery delete video link
	$('.gallery_edit_video_delete_link').live('click', function(e) {
		e.preventDefault();
		var object_id = $(this).attr('id');
		var object_id_array = object_id.split('_');
		var video_id = object_id_array[5];
		var gallery_id = object_id_array[6];
		$.post('admin-ajax.php', {
			action : 'vg_delete_video',
			video_id : video_id
		},function(r) {
			r = _result2Array(r);
			if( r[0] ) {
				$("#video_" + video_id).fadeOut("slow", function() {
					$(this).remove();
					var gallery_video_count = $("#gallery_video_count_" + gallery_id);
					var video_count = parseInt(gallery_video_count.text());
					var new_video_count = ( (video_count - 1) > 0 ) ? video_count - 1 : 0;
					gallery_video_count.text(new_video_count);
				});
			} else {
				alert('There is a problem!');
			}
		});

	});

	// Edit gallery close button
	$(".gallery_edit_back_button").live('click', function() {
		var object_id = $(this).attr('id');
		var object_id_array = object_id.split('_');
		var gallery_id = object_id_array[4];

		$("#gallery_edit_" + gallery_id).animate({
			height: 0
		},1000, function() {
			$(this).remove();
		});

	});

	// Add gallery video count change
	$("#add_gallery_video_count_button").click(function() {
		var video_count = parseInt($("#add_gallery_video_count").val());
		// $('.vg_add_gallery_url_tr').remove();

		var urls = $('.vg_add_gallery_url');
		var last_url = urls[urls.length-1];
		var last_url_array = $(last_url).attr('id').split('_');
		var last_url_id = parseInt(last_url_array[4]);
		var appendString = '';
		var new_video_count = last_url_id + video_count;
		for ( var i = last_url_id; i < new_video_count; i++ ) {
			appendString += '<tr valign="top" class="vg_add_gallery_url_tr" id="vg_add_gallery_url_tr_'+ ( i + 1 )+'">';
			appendString += '<td scope="col">Video ' + (i+1) +'</td>';
			appendString += '<td><input type="text" class="regular-text vg_add_gallery_url" id="vg_add_gallery_url_'+ (i+1) +'" /> </td>';
			appendString += '</tr>';
		}
    
		$("#vg_add_gallery_url_tr_" + last_url_id).after(appendString);
	});

	$('#add_gallery_video_tip_url').click(function(e) {
		e.preventDefault();
		//$("#add_gallery_video_tip_tr").css("display","block");
		var o = $("#add_gallery_video_tip_div");
		if( o.css("display") == "none" )
			$("#add_gallery_video_tip_div").slideDown();
		else
			$("#add_gallery_video_tip_div").slideUp();
	});

	$('.gallery_edit_video_copy_text, .vg_edit_gallery_gallery_copy_text').live('click', function() {
		this.select();
	});



});

