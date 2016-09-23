$(document).ready(function() {
    // Initialize dropzone
    Dropzone.options.mediaUpload = {
        paramName: "file",
        addRemoveLinks: true,
        accept: function(file, done) {
            done();
        },
        success: function(file, response) {
            $('.table-media').DataTable().ajax.reload();
        },
        init: function() {
            this.on('removedfile', function(file, response) {
                var response = $.parseJSON(file.xhr.response);
                remove_file(response.filename, response.folder, 'undefined', file.name);
            });
        }
    };
});

// Remove file from folder
function remove_file(filename, folder) {
    $.post(admin_url + 'utilities/remove_media_file/', {
        filename: filename,
        folder: folder
    }).success(function() {
        $('.table-media').DataTable().ajax.reload();
    });
}

