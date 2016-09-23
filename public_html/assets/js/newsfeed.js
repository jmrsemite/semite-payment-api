var totalFiles = 0;
var newsfeed_posts_page = 0;
var track_load_post_likes = 0;
var track_load_comment_likes = 0;
var current_post_images = {};
var track_next_click = 0;
var track_prev_click = 0;
// Total pages
var post_likes_total_pages = 0;
var comment_likes_total_pages = 0;
$(document).ready(function() {
    // Init news feed
    load_newsfeed();
    // When adding comment if user press enter to submit comment too
    $("body").on('keyup', '.comment-input input', function(event) {
        if (event.keyCode == 13) {
            add_comment(this);
        }
    });

    // Showing post likes modal
    $('#modal_post_likes').on('show.bs.modal', function(e) {
        track_load_post_likes = 0;
        $('#modal_post_likes_wrapper').empty();
        $('.likes_modal .modal-footer').removeClass('hide');
        var invoker = $(e.relatedTarget);
        var postid = $(invoker).data('postid')
        post_likes_total_pages = $(invoker).data('total-pages');
        $(".load_more_post_likes").attr('data-postid', postid);
        load_post_likes(postid);
    });

    // Showing comment likes modal
    $('#modal_post_comment_likes').on('show.bs.modal', function(e) {
        $('#modal_comment_likes_wrapper').empty();
        track_load_comment_likes = 0;
        $('.likes_modal .modal-footer').removeClass('hide');
        var invoker = $(e.relatedTarget);
        var commentid = $(invoker).data('commentid');
        comment_likes_total_pages = $(invoker).data('total-pages');
        $(".load_more_post_comment_likes").attr('data-commentid', commentid);
        load_comment_likes(commentid);
    });

    // Load more post likes from modal
    $('.load_more_post_likes').on('click', function(e) {
        e.preventDefault();
        load_post_likes($(this).data('postid'));
    });

    // Load more comment likes from modal
    $('.load_more_post_comment_likes').on('click', function(e) {
        e.preventDefault();
        load_comment_likes($(this).data('commentid'));
    });

    // Add post attachment used for dropzone
    $('.add-attachments').on('click', function(e) {
        e.preventDefault();
        $('#post-attachments').toggleClass('hide');
    });

    // Set post visibility on change select by department
    $('#post-visibility').on('change', function() {
        var value = $(this).val();
        if (value != null) {
            if (value.indexOf('all') > -1) {
                if (value.length > 1) {
                    value.splice(0, 1);
                    $(this).selectpicker('val', value);
                }
            }
        }
    });
    // Init new post form
    $('#new-post-form').submit(function() {
        $.post(this.action, $(this).serialize()).success(function(response) {
            response = $.parseJSON(response);
            if (response.postid) {
                if (newsFeedDropzone.getQueuedFiles().length > 0) {
                    newsFeedDropzone.options.url = admin_url + 'newsfeed/add_post_attachments/' + response.postid;
                    newsFeedDropzone.processQueue();
                    return;
                }
                newsfeed_new_post(response.postid);
                clear_newsfeed_post_area();
            }
        });
        return false;
    });
    // Configure dropzone
    Dropzone.autoDiscover = false;
    if($('#new-post-form').length > 0){
    var newsFeedDropzone = new Dropzone("#new-post-form", {
        clickable: '.add-post-attachments',
        autoProcessQueue: false,
        addRemoveLinks: true,
        parallelUploads: newsfeed_maximum_files_upload,
        maxFiles: newsfeed_maximum_files_upload,
        maxFilesize: newsfeed_maximum_file_size,
        acceptedFiles: newsfeed_upload_file_extensions,
    });


    // On post added success
    newsFeedDropzone.on('success', function(files, response) {
        totalFiles--;
        if (totalFiles == 0) {
            response = $.parseJSON(response);
            newsfeed_new_post(response.postid);
            clear_newsfeed_post_area();
            newsFeedDropzone.removeAllFiles();
        }
    });

    // When drag finished
    newsFeedDropzone.on("dragover", function(file) {
        $('#new-post-form').addClass('dropzone-active')
    });

    newsFeedDropzone.on("drop", function(file) {
        $('#new-post-form').removeClass('dropzone-active')
    });
    // On error files decrement total files
    newsFeedDropzone.on("error", function(file) {
        totalFiles--;
    });
    // When user click on remove files decrement total files
    newsFeedDropzone.on('removedfile', function(file) {
        totalFiles--;
    });
    // On added new file increment total files variable
    newsFeedDropzone.on("addedfile", function(file) {
        // Refresh total files to zero if no files are found becuase removedFile goes to --;
        if (this.getQueuedFiles().length == 0) {
            totalFiles = 0;
        }
        totalFiles++;
    });
   } // end if newsfeed dropzone
});

// When window scroll to down load more posts
$(window).scroll(function() {
    if ($(window).scrollTop() + $(window).height() == $(document).height()) {
        load_newsfeed();
    }
});
// Clear newsfeed new post area
function clear_newsfeed_post_area() {
    $('#new-post-form textarea').val('');
    $(this).selectpicker('val', 'all');
}
// Load post likes modal
function load_post_likes(postid) {

    if (track_load_post_likes <= post_likes_total_pages) {
        $.post(admin_url + 'newsfeed/load_likes_modal', {
            page: track_load_post_likes,
            postid: postid
        }).success(function(response) {
            track_load_post_likes++
            $('#modal_post_likes_wrapper').append(response);
        });

        if (track_load_post_likes >= post_likes_total_pages - 1) {
            $('.likes_modal .modal-footer').addClass('hide');
        }
    }
}
// Load comment likes modal
function load_comment_likes(commentid) {

    if (track_load_comment_likes <= comment_likes_total_pages) {
        $.post(admin_url + 'newsfeed/load_comment_likes_model', {
            page: track_load_comment_likes,
            commentid: commentid
        }).success(function(response) {
            track_load_comment_likes++
            $('#modal_comment_likes_wrapper').append(response);
        });

        if (track_load_comment_likes >= comment_likes_total_pages - 1) {
            $('.likes_modal .modal-footer').addClass('hide');
        }
    }
}
// On click href load more comments from single post
function load_more_comments(link) {
    var postid = $(link).data('postid');
    var page = $(link).find('input[name="page"]').val();
    var total_pages = $(link).data('total-pages');

    if (page <= total_pages) {
        $.post(admin_url + 'newsfeed/init_post_comments/' + postid, {
            page: page
        }).success(function(response) {
            $(link).data('track-load-comments', page);
            $('[data-comments-postid="' + postid + '"] .load-more-comments').before(response);
        });
        page++;
        $(link).find('input[name="page"]').val(page);
        if (page >= total_pages - 1) {
            $(link).addClass('hide');
            $(link).removeClass('display-block');
        }
    }
}
// new post added append data
function newsfeed_new_post(postid) {
    var data = {};
    data.postid = postid;
    $.post(admin_url + 'newsfeed/load_newsfeed', data).success(function(response) {
        var pinned = $('#newsfeed_data').find('.pinned');
        var pinned_length = pinned.length
        if (pinned_length == 0) {
            $('#newsfeed_data').prepend(response);
        } else {
            var last_pinned = $('#newsfeed_data').find('.pinned').eq(pinned_length - 1);
            $(last_pinned).after(response);
        }
    });
}
// Init newsfeed data
function load_newsfeed() {

    var data = {};
    data.page = newsfeed_posts_page;
    var total_pages = $('input[name="total_pages"]').val();
    if (newsfeed_posts_page <= total_pages) {
        $.post(admin_url + 'newsfeed/load_newsfeed', data).success(function(response) {
            newsfeed_posts_page++
            $('#newsfeed_data').append(response);
        });
        if (newsfeed_posts_page >= total_pages - 1) {
            return;
        }
    }
}

// When user click heart button
function like_post(postid) {
    $.get(admin_url + 'newsfeed/like_post/' + postid, function(response) {
        if (response.success == true) {
            refresh_post_likes(postid);
        }
    }, 'json');
}
// Unlikes post
function unlike_post(postid) {
    $.get(admin_url + 'newsfeed/unlike_post/' + postid, function(response) {
        if (response.success == true) {
            refresh_post_likes(postid);
        }
    }, 'json');
}
// Like post comment
function like_comment(commentid, postid) {
    $.get(admin_url + 'newsfeed/like_comment/' + commentid + '/' + postid, function(response) {
        if (response.success == true) {
            refresh_post_comments(postid);
        }
    }, 'json');
}
// Unlike post comment
function unlike_comment(commentid, postid) {
    $.get(admin_url + 'newsfeed/unlike_comment/' + commentid + '/' + postid, function(response) {
        if (response.success == true) {
            refresh_post_comments(postid)
        }
    }, 'json');
}
// Add new comment to post
function add_comment(input) {
    var postid = $(input).data('postid');
    $.post(admin_url + 'newsfeed/add_comment', {
        content: $(input).val(),
        postid: postid
    }).success(function(response) {
        response = $.parseJSON(response);
        if (response.success == true) {
            $(input).val('');
            refresh_post_comments(postid)
        }
    });
}
// Removes post comment
function remove_post_comment(id, postid) {
    $.get(admin_url + 'newsfeed/remove_post_comment/' + id + '/' + postid, function(response) {
        if (response.success == true) {
            $('.comment[data-commentid="' + id + '"]').remove();
        }
    }, 'json');
}
// Refreshing only post likes
function refresh_post_likes(postid) {
    $.get(admin_url + 'newsfeed/init_post_likes/' + postid + '?refresh_post_likes=true', function(response) {
        $('[data-likes-postid="' + postid + '"]').html(response);
    });
}
// Refreshing only post comments
function refresh_post_comments(postid) {
    $.get(admin_url + 'newsfeed/init_post_comments/' + postid + '?refresh_post_comments=true', function(response) {
        $('[data-comments-postid="' + postid + '"]').html(response);
    });
}
// Delete post from database
function delete_post(postid) {
    $.post(admin_url + 'newsfeed/delete_post/' + postid, function(response) {
        if (response.success == true) {
            $('[data-main-postid="' + postid + '"]').remove();
        }
    }, 'json');
}

// Custom lightbox for displaying images
//Receive attachment id
function NewsFeedLightBox(e, id, postid) {
    e.stopPropagation();
    var n_nav = $('#newsfeed_images_modal .modal-header');
    $.get(admin_url + 'newsfeed/get_post_image_attachments/' + postid, function(response) {
        current_post_images = response;
        if (typeof(id) == 'undefined') {
            $('.prev').addClass('hide');
            add_lightbox_image(postid, current_post_images[0].id, current_post_images[0].filename);
        } else {
            var total_attachments = current_post_images.length
            for (i = 0; i < current_post_images.length; i++) {
                if (current_post_images[i].id == id) {
                    track_next_click = i + 1;
                    add_lightbox_image(postid, id, current_post_images[i].filename);
                    if (current_post_images[total_attachments - 1].id == id) {
                        $('.next').addClass('hide');
                    } else {
                        $('.next').removeClass('hide');
                    }
                    if (i == 0) {
                        $('.prev').addClass('hide');
                    } else {
                        $('.prev').removeClass('hide');
                    }
                    break;
                }
            }
            if (current_post_images.length == 1) {
                n_nav.addClass('hide');
            } else {
                if (n_nav.hasClass('hide')) {
                    n_nav.removeClass('hide');
                }
            }
        }
        $('#newsfeed_images_modal').modal('show');
    }, 'json');

}
// Get the next image from post used in lightbox
function next_newsfeed_lightbox_image() {
    var current_image = $('#newsfeed_images_modal .modal-body img').data('id');
    var postid = $('#newsfeed_images_modal .modal-body img').data('postid');
    $('.prev').removeClass('hide');
    if (current_post_images.length == 1) {
        return;
    }

    for (i = 0; i < current_post_images.length; i++) {
        if (current_post_images[i].id == current_image) {
            add_lightbox_image(postid, current_post_images[i + 1].id, current_post_images[i + 1].filename);
            if ((i + 1) == current_post_images.length - 1) {
                $('.next').addClass('hide');
                return
            }
        }
    }
}
// Track prev image from newsfeed post images
function prev_newsfeed_lightbox_image() {
    var current_image = $('#newsfeed_images_modal .modal-body img').data('id');
    var postid = $('#newsfeed_images_modal .modal-body img').data('postid');

    for (i = 0; i < current_post_images.length; i++) {
        if (current_post_images[i].id == current_image) {
            add_lightbox_image(postid, current_post_images[i - 1].id, current_post_images[i - 1].filename);
            if ((i + 1) > 0) {
                $('.next').removeClass('hide')
                $('.prev').removeClass('hide')
            }
            if ((i) == 1) {
                $('.prev').addClass('hide');
            }
            break;
        }
    }
}
// Add image to the lightbox modal
function add_lightbox_image(postid, id, filename) {
    $('#newsfeed_images_modal .modal-body').html('<img onclick="next_newsfeed_lightbox_image()" src="' + site_url + 'uploads/newsfeed/' + postid + '/' + filename + '" class="img img-responsive img-gallery" data-id="' + id + '" data-postid="' + postid + '">');
}
// Ping post to top
function pin_post(id) {

    $.get(admin_url + 'newsfeed/pin_newsfeed_post/' + id, function(response) {
        if (response.success == true) {
            window.location.href = site_url + 'admin';
        }
    }, 'json');
}
// Unpin post from top
function unpin_post(id) {
    $.get(admin_url + 'newsfeed/unpin_newsfeed_post/' + id, function(response) {
        if (response.success == true) {
            window.location.href = site_url + 'admin';
        }
    }, 'json');
}
