    var editor;
    ContentTools.StylePalette.add([new ContentTools.Style('Bootstrap Table', 'table', ['table'])]);
    ContentTools.StylePalette.add([new ContentTools.Style('Table Bordered', 'table-bordered', ['table'])]);
    ContentTools.StylePalette.add([new ContentTools.Style('Table Striped', 'table-striped', ['table'])]);

    ContentTools.StylePalette.add([new ContentTools.Style('Text Danger', 'text-danger')]);
    ContentTools.StylePalette.add([new ContentTools.Style('Text Info', 'text-info')]);
    ContentTools.StylePalette.add([new ContentTools.Style('Text Success', 'text-success')]);
    ContentTools.StylePalette.add([new ContentTools.Style('Text Warning', 'text-warning')]);

    function init_proposal_editor() {
        ContentTools.IMAGE_UPLOADER = imageUploader;
        editor = ContentTools.EditorApp.get();
        editor.init('*[data-editable]', 'data-name');
        ignition = editor._ignition
        editor.bind('save', function(regions, calledBy) {
            var name, onStateChange, payload, xhr;
            // Set the editor as busy while we save our changes
            if (calledBy !== 'autoSave') {
                this.busy(true);
            }
            // Collect the contents of each region into a FormData instance
            payload = new FormData();
            payload.append('proposal_id', proposal_id);

            for (name in regions) {
                payload.append(name, regions[name]);
            }
            // Send the update content to the server to be saved
            onStateChange = function(ev) {
                // Check if the request is finished
                if (ev.target.readyState == 4) {
                    editor.busy(false);
                    if (ev.target.status == '200') {
                        // Save was successful, notify the user with a flash
                        if (calledBy !== 'autoSave') {
                            new ContentTools.FlashUI('ok');
                        }
                    } else {
                        // Save failed, notify the user with a flash
                        new ContentTools.FlashUI('no');
                    }
                }
            };
            xhr = new XMLHttpRequest();
            xhr.addEventListener('readystatechange', onStateChange);
            xhr.open('POST', admin_url + 'proposals/save_proposal_data');
            xhr.send(payload);
            $('body').find('.ct-edit-manual').removeClass('hide');
        });
         ignition.bind('cancel', function(test) {
            console.log('test');
         });
    }

    $(document).ready(function() {
        get_proposal_comments();

          $('body').on('click', '.add-proposal-items', function() {
        $.get(admin_url + 'proposals/get_proposal_items_template', function(response) {
            $('#items_helper_area').html(response);
            init_selectpicker();
            $('#proposal_items_template').modal('show');
            // If the editor isn't currently in editing mode start it
            var editor = ContentTools.EditorApp.get();
            if (editor.isReady()) {
                ignition._domEdit.click();

            }
        });
    });

    $('body').on('click', '.add-items-to-proposal', function() {
        $('body').find('#proposal-pre-items').find('.delete_item').remove();
        var table_data = $('body').find('#proposal-pre-items').html();
        var exists = $('[data-editable]').find('.table.proposal-items');
        var region;
        if (exists.length > 0) {
            var regions = editor.regions();
            region = regions['main-content'].children;
            for (var q = 0; q < region.length; q++) {
                if ($(region[q]._domElement).hasClass('proposal-items')) {
                    var columns = 6;
                    var cell, cellText, i, row, section, _i;
                    total_rows = $(table_data).find('tbody tr').length;
                    for (var x = 0; x < total_rows; x++) {
                        var body = $(region[q]._domElement).find('tbody');
                        row = new ContentEdit.TableRow();
                        if (body.length == 0) {
                            section = new ContentEdit.TableSection('tbody');
                            section.attach(row);
                        }
                        for (i = _i = 0; 0 <= columns ? _i < columns : _i > columns; i = 0 <= columns ? ++_i : --_i) {
                            cell = new ContentEdit.TableCell('td');
                            row.attach(cell);
                            var table_data_desc = $(table_data).find('tbody tr').eq(x).find('td').eq(i).text();
                            cellText = new ContentEdit.TableCellText(table_data_desc.trim());
                            cell.attach(cellText);
                        }
                        if (body.length == 0) {
                            region[q].attach(section);
                        } else {
                            region[q].children[1].attach(row);
                        }
                    }
                }
            }
        } else {

            if ($('body').find('#proposal-pre-items tbody tr').length > 0) {
                var templates = {
                    'pricesTable': ['table', table_data]
                };
                // When a user selects a template compile it to a set of editable elements
                var template = document.createElement('div');
                template.innerHTML = templates['pricesTable'];
                var elements = new ContentEdit.Region(template).children;
                // Insert the elements at the end of your content
                var region = ContentTools.EditorApp.get().orderedRegions()[0];
                for (var i = 0; i < elements.length; i++) {
                    region.attach(elements[i]);
                }

            }
        }
        $('#proposal_items_template').modal('hide');
    });

    });

    function set_editor_edit_mode(event){
         var editor = ContentTools.EditorApp.get();
         if (editor.isReady()) {
            ignition._domEdit.click();
            editor.highlightRegions(true);
            setTimeout(function(){
                editor.highlightRegions(false);
            },1500)
         }
    }


    function imageUploader(dialog) {
        var image, xhr, xhrComplete, xhrProgress;
        dialog.bind('imageUploader.fileReady', function(file) {
            // Upload a file to the server
            var formData;

            // Define functions to handle upload progress and completion
            xhrProgress = function(ev) {
                // Set the progress for the upload
                dialog.progress((ev.loaded / ev.total) * 100);
            }

            xhrComplete = function(ev) {
                var response;

                // Check the request is complete
                if (ev.target.readyState != 4) {
                    return;
                }
                // Clear the request
                xhr = null
                xhrProgress = null
                xhrComplete = null

                // Handle the result of the upload
                if (parseInt(ev.target.status) == 200) {
                    // Unpack the response (from JSON)
                    response = JSON.parse(ev.target.responseText);
                    // Store the image details
                    image = {
                        size: response.size,
                        url: site_url + 'media/' + response.folder + '/' + response.filename,
                        path: 'media/' + response.folder + '/' + response.filename
                    };
                    // Populate the dialog
                    dialog.populate(image.url, image.size);

                } else {
                    // The request failed, notify the user
                    new ContentTools.FlashUI('no');
                }
            }

            // Set the dialog state to uploading and reset the progress bar to 0
            dialog.state('uploading');
            dialog.progress(0);

            // Build the form data to post to the server
            formData = new FormData();
            formData.append('file', file);

            // Make the request
            xhr = new XMLHttpRequest();
            xhr.upload.addEventListener('progress', xhrProgress);
            xhr.addEventListener('readystatechange', xhrComplete);
            xhr.open('POST', admin_url + 'utilities/upload_media', true);
            xhr.send(formData);
        });

        dialog.bind('imageUploader.cancelUpload', function() {
            // Cancel the current upload

            // Stop the upload
            if (xhr) {
                xhr.upload.removeEventListener('progress', xhrProgress);
                xhr.removeEventListener('readystatechange', xhrComplete);
                xhr.abort();
            }

            // Set the dialog to empty
            dialog.state('empty');
        });

        dialog.bind('imageUploader.clear', function() {
            // Clear the current image
            dialog.clear();
            image = null;
        });

        dialog.bind('imageUploader.save', function() {
            dialog.save(
                image.url,
                image.size, {
                    'alt': image.alt,
                    'data-ce-max-width': image.size[0]
                });
        });
    }

    function add_proposal_comment() {
        var comment = $('#comment').val();
        if (comment == '') {
            return;
        }
        var data = {};
        data.content = comment;
        data.proposalid = proposal_id;
        $('body').append('<div class="dt-loader"></div>');
        $.post(admin_url + 'proposals/add_proposal_comment', data).success(function(response) {
            response = $.parseJSON(response);
            $('body').find('.dt-loader').remove();
            if (response.success == true) {
                $('#comment').val('');
                get_proposal_comments();
            }
        });
    }

    function get_proposal_comments() {
        if (typeof(proposal_id) == 'undefined') {
            return;
        }
        $.get(admin_url + 'proposals/get_proposal_comments/' + proposal_id, function(response) {
            $('body').find('#proposal-comments').html(response);
        });
    }

    function remove_proposal_comment(commentid) {
        $.get(admin_url + 'proposals/remove_comment/' + commentid, function(response) {
            if (response.success == true) {
                $('[data-commentid="' + commentid + '"]').remove();
            }
        }, 'json');
    }

    (function() {
        var CropMarksUI,
            extend = function(child, parent) {
                for (var key in parent) {
                    if (hasProp.call(parent, key))
                        child[key] = parent[key];
                }

                function ctor() {
                    this.constructor = child;
                }
                ctor.prototype = parent.prototype;
                child.prototype = new ctor();
                child.__super__ = parent.prototype;
                return child;
            },
            hasProp = {}.hasOwnProperty;

        ContentTools.ImageDialog = (function(superClass) {
            extend(ImageDialog, superClass);

            function ImageDialog() {
                ImageDialog.__super__.constructor.call(this, 'Insert image');
                this._cropMarks = null;
                this._imageURL = null;
                this._imageSize = null;
                this._progress = 0;
                this._state = 'empty';
                if (ContentTools.IMAGE_UPLOADER) {
                    ContentTools.IMAGE_UPLOADER(this);
                }
            }

            ImageDialog.prototype.cropRegion = function() {
                if (this._cropMarks) {
                    return this._cropMarks.region();
                }
                return [
                    0,
                    0,
                    1,
                    1
                ];
            };

            ImageDialog.prototype.addCropMarks = function() {
                if (this._cropMarks) {
                    return;
                }
                this._cropMarks = new CropMarksUI(this._imageSize);
                this._cropMarks.mount(this._domView);
                return ContentEdit.addCSSClass(this._domCrop, 'ct-control--active');
            };

            ImageDialog.prototype.clear = function() {
                if (this._domImage) {
                    this._domImage.parentNode.removeChild(this._domImage);
                    this._domImage = null;
                }
                this._imageURL = null;
                this._imageSize = null;
                return this.state('empty');
            };

            ImageDialog.prototype.mount = function() {
                var domActions, domProgressBar, domTools;
                ImageDialog.__super__.mount.call(this);
                ContentEdit.addCSSClass(this._domElement, 'ct-image-dialog');
                ContentEdit.addCSSClass(this._domElement, 'ct-image-dialog--empty');
                ContentEdit.addCSSClass(this._domView, 'ct-image-dialog__view');
                domTools = this.constructor.createDiv([
                    'ct-control-group',
                    'ct-control-group--left'
                ]);
                this._domControls.appendChild(domTools);
                this._domRotateCCW = this.constructor.createDiv([
                    'ct-control',
                    'ct-control--icon',
                    'ct-control--rotate-ccw'
                ]);
                this._domRotateCCW.setAttribute('data-tooltip', ContentEdit._('Rotate') + ' -90Â°');
                domTools.appendChild(this._domRotateCCW);
                this._domRotateCW = this.constructor.createDiv([
                    'ct-control',
                    'ct-control--icon',
                    'ct-control--rotate-cw'
                ]);
                this._domRotateCW.setAttribute('data-tooltip', ContentEdit._('Rotate') + ' 90Â°');
                domTools.appendChild(this._domRotateCW);
                this._domCrop = this.constructor.createDiv([
                    'ct-control',
                    'ct-control--icon',
                    'ct-control--crop'
                ]);
                this._domCrop.setAttribute('data-tooltip', ContentEdit._('Crop marks'));
                domTools.appendChild(this._domCrop);
                domProgressBar = this.constructor.createDiv([
                    'ct-progress-bar'
                ]);
                domTools.appendChild(domProgressBar);
                this._domProgress = this.constructor.createDiv([
                    'ct-progress-bar__progress'
                ]);
                domProgressBar.appendChild(this._domProgress);
                domActions = this.constructor.createDiv([
                    'ct-control-group',
                    'ct-control-group--right'
                ]);
                this._domControls.appendChild(domActions);

                this._domURLInput = document.createElement('input');
                this._domURLInput.setAttribute('class', 'ct-image-dialog__input ct-control--fetch form-control');
                this._domURLInput.setAttribute('name', 'url');
                this._domURLInput.setAttribute('placeholder', ContentEdit._('Paste image URL') + '...');
                this._domURLInput.setAttribute('type', 'text');
                domActions.appendChild(this._domURLInput);

                this._domFetch = this.constructor.createDiv([
                    'ct-control',
                    'ct-control--text',
                    'ct-control--fetch'
                ]);
                this._domFetch.textContent = ContentEdit._('Fetch');
                domActions.appendChild(this._domFetch);

                this._domUpload = this.constructor.createDiv([
                    'ct-control',
                    'ct-control--text',
                    'ct-control--upload'
                ]);
                this._domUpload.textContent = ContentEdit._('Upload');
                domActions.appendChild(this._domUpload);

                this._domInput = document.createElement('input');
                this._domInput.setAttribute('class', 'ct-image-dialog__file-upload');
                this._domInput.setAttribute('name', 'file');
                this._domInput.setAttribute('type', 'file');
                this._domInput.setAttribute('accept', 'image/*');
                this._domUpload.appendChild(this._domInput);

                this._domInsert = this.constructor.createDiv([
                    'ct-control',
                    'ct-control--text',
                    'ct-control--insert'
                ]);
                this._domInsert.textContent = ContentEdit._('Insert');
                domActions.appendChild(this._domInsert);
                this._domCancelUpload = this.constructor.createDiv([
                    'ct-control',
                    'ct-control--text',
                    'ct-control--cancel'
                ]);
                this._domCancelUpload.textContent = ContentEdit._('Cancel');
                domActions.appendChild(this._domCancelUpload);
                this._domClear = this.constructor.createDiv([
                    'ct-control',
                    'ct-control--text',
                    'ct-control--clear'
                ]);
                this._domClear.textContent = ContentEdit._('Clear');
                domActions.appendChild(this._domClear);
                this._addDOMEventListeners();
                return this.trigger('imageUploader.mount');
            };

            ImageDialog.prototype.populate = function(imageURL, imageSize) {
                this._imageURL = imageURL;
                this._imageSize = imageSize;
                if (!this._domImage) {
                    this._domImage = this.constructor.createDiv([
                        'ct-image-dialog__image'
                    ]);
                    this._domView.appendChild(this._domImage);
                }
                this._domImage.style['background-image'] = "url(" + imageURL + ")";
                return this.state('populated');
            };

            ImageDialog.prototype.progress = function(progress) {
                if (progress === void 0) {
                    return this._progress;
                }
                this._progress = progress;
                if (!this.isMounted()) {
                    return;
                }
                return this._domProgress.style.width = this._progress + "%";
            };

            ImageDialog.prototype.removeCropMarks = function() {
                if (!this._cropMarks) {
                    return;
                }
                this._cropMarks.unmount();
                this._cropMarks = null;
                return ContentEdit.removeCSSClass(this._domCrop, 'ct-control--active');
            };

            ImageDialog.prototype.save = function(imageURL, imageSize, imageAttrs) {
                return this.trigger('save', imageURL, imageSize, imageAttrs);
            };

            ImageDialog.prototype.fetchImage = function(imageURL) {
                var _this = this;
                // Create image object in order to load image and determine its dimension
                var img = new Image();
                img.onerror = function(e) {
                    alert(ContentEdit._('Image is invalid'));
                };

                img.onload = function() {
                    _this._imageSize = [
                        img.width,
                        img.height
                    ];
                    _this._imageURL = img.src;
                    _this.populate(_this._imageURL, _this._imageSize);
                };
                img.src = imageURL;
            };



            ImageDialog.prototype.state = function(state) {
                var prevState;
                if (state === void 0) {
                    return this._state;
                }
                if (this._state === state) {
                    return;
                }
                prevState = this._state;
                this._state = state;
                if (!this.isMounted()) {
                    return;
                }
                ContentEdit.addCSSClass(this._domElement, "ct-image-dialog--" + this._state);
                return ContentEdit.removeCSSClass(this._domElement, "ct-image-dialog--" + prevState);
            };

            ImageDialog.prototype.unmount = function() {
                ImageDialog.__super__.unmount.call(this);
                this._domCancelUpload = null;
                this._domClear = null;
                this._domCrop = null;
                this._domInput = null;
                this._domInsert = null;
                this._domProgress = null;
                this._domRotateCCW = null;
                this._domRotateCW = null;
                this._domUpload = null;
                return this.trigger('imageUploader.unmount');
            };

            ImageDialog.prototype._addDOMEventListeners = function() {
                ImageDialog.__super__._addDOMEventListeners.call(this);

                // when user hit return
                this._domURLInput.addEventListener('keydown', (function(_this) {
                    return function(ev) {
                        if (ev.keyCode !== 13 || !_this._domURLInput.value)
                            return;

                        _this.fetchImage(_this._domURLInput.value);

                        return _this.trigger('imageUploader.fetchReady', {});
                    };
                })(this));

                this._domFetch.addEventListener('click', (function(_this) {
                    return function(ev) {
                        if (!_this._domURLInput.value)
                            return;

                        _this.fetchImage(_this._domURLInput.value);

                        return _this.trigger('imageUploader.fetchReady', {});
                    };
                })(this));

                this._domInput.addEventListener('change', (function(_this) {
                    return function(ev) {
                        var file;
                        file = ev.target.files[0];
                        ev.target.value = '';
                        if (ev.target.value) {
                            ev.target.type = 'text';
                            ev.target.type = 'file';
                        }
                        return _this.trigger('imageUploader.fileReady', file);
                    };
                })(this));
                this._domCancelUpload.addEventListener('click', (function(_this) {
                    return function(ev) {
                        return _this.trigger('imageUploader.cancelUpload');
                    };
                })(this));
                this._domClear.addEventListener('click', (function(_this) {
                    return function(ev) {
                        _this.removeCropMarks();
                        _this.clear();
                        return _this.trigger('imageUploader.clear');
                    };
                })(this));
                // this._domRotateCCW.addEventListener('click', (function (_this) {
                //   return function (ev) {
                //     _this.removeCropMarks();
                //     return _this.trigger('imageUploader.rotateCCW');
                //   };
                // })(this));
                // this._domRotateCW.addEventListener('click', (function (_this) {
                //   return function (ev) {
                //     _this.removeCropMarks();
                //     return _this.trigger('imageUploader.rotateCW');
                //   };
                // })(this));
                this._domCrop.addEventListener('click', (function(_this) {
                    return function(ev) {
                        if (_this._cropMarks) {
                            return _this.removeCropMarks();
                        } else {
                            return _this.addCropMarks();
                        }
                    };
                })(this));
                return this._domInsert.addEventListener('click', (function(_this) {
                    return function(ev) {
                        _this.save(_this._imageURL, _this._imageSize, {});
                        return _this.trigger('imageUploader.save');
                    };
                })(this));
            };

            return ImageDialog;

        })(ContentTools.DialogUI);

        CropMarksUI = (function(superClass) {
            extend(CropMarksUI, superClass);

            function CropMarksUI(imageSize) {
                CropMarksUI.__super__.constructor.call(this);
                this._bounds = null;
                this._dragging = null;
                this._draggingOrigin = null;
                this._imageSize = imageSize;
            }

            CropMarksUI.prototype.mount = function(domParent, before) {
                if (before == null) {
                    before = null;
                }
                this._domElement = this.constructor.createDiv([
                    'ct-crop-marks'
                ]);
                this._domClipper = this.constructor.createDiv([
                    'ct-crop-marks__clipper'
                ]);
                this._domElement.appendChild(this._domClipper);
                this._domRulers = [
                    this.constructor.createDiv([
                        'ct-crop-marks__ruler',
                        'ct-crop-marks__ruler--top-left'
                    ]),
                    this.constructor.createDiv([
                        'ct-crop-marks__ruler',
                        'ct-crop-marks__ruler--bottom-right'
                    ])
                ];
                this._domClipper.appendChild(this._domRulers[0]);
                this._domClipper.appendChild(this._domRulers[1]);
                this._domHandles = [
                    this.constructor.createDiv([
                        'ct-crop-marks__handle',
                        'ct-crop-marks__handle--top-left'
                    ]),
                    this.constructor.createDiv([
                        'ct-crop-marks__handle',
                        'ct-crop-marks__handle--bottom-right'
                    ])
                ];
                this._domElement.appendChild(this._domHandles[0]);
                this._domElement.appendChild(this._domHandles[1]);
                CropMarksUI.__super__.mount.call(this, domParent, before);
                return this._fit(domParent);
            };

            CropMarksUI.prototype.region = function() {
                return [
                    parseFloat(this._domHandles[0].style.top) / this._bounds[1],
                    parseFloat(this._domHandles[0].style.left) / this._bounds[0],
                    parseFloat(this._domHandles[1].style.top) / this._bounds[1],
                    parseFloat(this._domHandles[1].style.left) / this._bounds[0]
                ];
            };

            CropMarksUI.prototype.unmount = function() {
                CropMarksUI.__super__.unmount.call(this);
                this._domClipper = null;
                this._domHandles = null;
                return this._domRulers = null;
            };

            CropMarksUI.prototype._addDOMEventListeners = function() {
                CropMarksUI.__super__._addDOMEventListeners.call(this);
                this._domHandles[0].addEventListener('mousedown', (function(_this) {
                    return function(ev) {
                        if (ev.button === 0) {
                            return _this._startDrag(0, ev.clientY, ev.clientX);
                        }
                    };
                })(this));
                return this._domHandles[1].addEventListener('mousedown', (function(_this) {
                    return function(ev) {
                        if (ev.button === 0) {
                            return _this._startDrag(1, ev.clientY, ev.clientX);
                        }
                    };
                })(this));
            };

            CropMarksUI.prototype._drag = function(top, left) {
                var height, minCrop, offsetLeft, offsetTop, width;
                if (this._dragging === null) {
                    return;
                }
                ContentSelect.Range.unselectAll();
                offsetTop = top - this._draggingOrigin[1];
                offsetLeft = left - this._draggingOrigin[0];
                height = this._bounds[1];
                left = 0;
                top = 0;
                width = this._bounds[0];
                minCrop = Math.min(Math.min(ContentTools.MIN_CROP, height), width);
                if (this._dragging === 0) {
                    height = parseInt(this._domHandles[1].style.top) - minCrop;
                    width = parseInt(this._domHandles[1].style.left) - minCrop;
                } else {
                    left = parseInt(this._domHandles[0].style.left) + minCrop;
                    top = parseInt(this._domHandles[0].style.top) + minCrop;
                }
                offsetTop = Math.min(Math.max(top, offsetTop), height);
                offsetLeft = Math.min(Math.max(left, offsetLeft), width);
                this._domHandles[this._dragging].style.top = offsetTop + "px";
                this._domHandles[this._dragging].style.left = offsetLeft + "px";
                this._domRulers[this._dragging].style.top = offsetTop + "px";
                return this._domRulers[this._dragging].style.left = offsetLeft + "px";
            };

            CropMarksUI.prototype._fit = function(domParent) {
                var height, heightScale, left, ratio, rect, top, width, widthScale;
                rect = domParent.getBoundingClientRect();
                widthScale = rect.width / this._imageSize[0];
                heightScale = rect.height / this._imageSize[1];
                ratio = Math.min(widthScale, heightScale);
                width = ratio * this._imageSize[0];
                height = ratio * this._imageSize[1];
                left = (rect.width - width) / 2;
                top = (rect.height - height) / 2;
                this._domElement.style.width = width + "px";
                this._domElement.style.height = height + "px";
                this._domElement.style.top = top + "px";
                this._domElement.style.left = left + "px";
                this._domHandles[0].style.top = '0px';
                this._domHandles[0].style.left = '0px';
                this._domHandles[1].style.top = height + "px";
                this._domHandles[1].style.left = width + "px";
                this._domRulers[0].style.top = '0px';
                this._domRulers[0].style.left = '0px';
                this._domRulers[1].style.top = height + "px";
                this._domRulers[1].style.left = width + "px";
                return this._bounds = [
                    width,
                    height
                ];
            };

            CropMarksUI.prototype._startDrag = function(handleIndex, top, left) {
                var domHandle;
                domHandle = this._domHandles[handleIndex];
                this._dragging = handleIndex;
                this._draggingOrigin = [
                    left - parseInt(domHandle.style.left),
                    top - parseInt(domHandle.style.top)
                ];
                this._onMouseMove = (function(_this) {
                    return function(ev) {
                        return _this._drag(ev.clientY, ev.clientX);
                    };
                })(this);
                document.addEventListener('mousemove', this._onMouseMove);
                this._onMouseUp = (function(_this) {
                    return function(ev) {
                        return _this._stopDrag();
                    };
                })(this);
                return document.addEventListener('mouseup', this._onMouseUp);
            };

            CropMarksUI.prototype._stopDrag = function() {
                document.removeEventListener('mousemove', this._onMouseMove);
                document.removeEventListener('mouseup', this._onMouseUp);
                this._dragging = null;
                return this._draggingOrigin = null;
            };

            return CropMarksUI;

        })(ContentTools.AnchoredComponentUI);

    }).call(this);

    function convert_template(invoker) {
        var template = $(invoker).data('template');
        var html_helper_selector;
        if (template == 'estimate') {
            html_helper_selector = 'estimate';
        } else if (template == 'invoice') {
            html_helper_selector = 'invoice';
        } else {
            return false;
        }
        $.get(admin_url + 'proposals/get_' + html_helper_selector + '_convert_data/' + proposal_id).success(function(data) {
            $('#convert_helper').html(data);
            $('#convert_to_' + html_helper_selector).modal('show');
            reorder_items();
        });
    }
