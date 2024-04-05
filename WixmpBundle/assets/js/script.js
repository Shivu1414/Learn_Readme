'use strict';

import $ from 'jquery';
import 'bootstrap';
import ko from 'knockout';
//import MarketplaceApp from './Components/MarketplaceApp';
import komapping from 'common/knockout_mapping.js';
import 'knockout.validation';
import 'common/ajax_jobs.js';
import 'common/Components/CoreApp.js';
import './Components/script';
import 'jquery-ui/ui/widgets/sortable';
//import 'jquery-ui/external/requirejs/require.js';
//import 'jquery-ui/demos/bootstrap.js';
import 'common/knockout_sortable.js';
import heic2any from "heic2any";


// MP Product plugin 
(function (factory) {
    "use strict";
    if (typeof define === "function" && define.amd) {
        // AMD. Register as an anonymous module.
        define(["jquery"], factory);
    } else if (typeof exports !== "undefined") {
        module.exports = factory(require("jquery"));
    } else {
        // Browser globals
        factory(window.jQuery);
    }
}(function ($) {
    "use strict";
    var pluginName = "mpAddProduct";
    var mpAddProductDefaults = {
        formSelector: '#mp-product-form',
        rowSelector: '.form-row',

    };
    var mpAddProduct = function (element, options) {
        this.element = $(element);
        this.settings = $.extend({}, mpAddProductDefaults, options);
        this.init();
        return this;
    };
    mpAddProduct.prototype.init = function () {
        var widget = this;
        this.bindEvents();
    };
    mpAddProduct.prototype.bindEvents = function () {
        var widget = this;
        var settings = this.settings;
        // toggle views
        // toggle inventory options
        if ($("input[name='form[inventory_tracking]']:checked").val() == 'product') {
            // show inventory fields 
            $("#productInventoryTrackingSimple").removeClass("d-none").show();
        } else {
            $("#productInventoryTrackingSimple").addClass("d-none");
        }
        // toggle availability
        if ($("input[name='form[availability]']:checked").val() == 'preorder') {
            // show inventory fields 
            $("#productAvailabilityPreorderType").removeClass("d-none").show();
        } else {
            $("#productAvailabilityPreorderType").addClass("d-none");
        }
        if ($("input[name='form[trackInventory]']").is(':checked')) {
            $("select[name='form[inventory_status]']").attr('disabled', true);
            $("input[name='form[quantity]']").attr('disabled', false);
        } else {
            $("select[name='form[inventory_status]']").attr('disabled', false);
            $("input[name='form[quantity]']").attr('disabled', true);
        }
        $("input[name='form[trackInventory]']").change(function (e) { 
            if(e.target.checked) {
                $("select[name='form[inventory_status]']").attr('disabled', true);
                $("input[name='form[quantity]']").removeAttr('disabled');
            } else {
                $("select[name='form[inventory_status]']").removeAttr('disabled');
                $("input[name='form[quantity]']").attr('disabled', true);
            }
        });
        // bind submit
        // $(this.element).submit(function(e){           
        //     return widget.validate();
        //     //e.preventDefault();
        // });
    };
    mpAddProduct.prototype.validate = function () {
        // validate for components 
        var valid = true;
        // var val = new mpCustomfield();
        if (!($("#custom_fields").mpCustomfield('validate'))) {
            //custom field error 
            valid = false;
            //display error 
            this.showMessage('danger', wkMpTrans.customfield_error_msg);
        }
        return valid;
    };
    mpAddProduct.prototype.showMessage = function (n_type, n_message) {
        var notification = '';
        notification += '<div class="alert alert-dismissible alert-' + n_type + '" role="alert">'
        notification += '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
        notification += '<span aria-hidden="true">&times;</span>';
        notification += '</button>';
        notification += n_message;
        notification += '</div>';
        $('.messages').append(notification);
    };
    mpAddProduct.prototype.methods = {
        showMessage: function (n_type, n_message) {
            var notification = '';
            notification += '<div class="alert alert-dismissible alert-' + n_type + '" role="alert">'
            notification += '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
            notification += '<span aria-hidden="true">&times;</span>';
            notification += '</button>';
            notification += n_message;
            notification += '</div>';
            $('.messages').append(notification);
        },
        validate: function () {
            var valid = true;
            // var val = new mpCustomfield();
            if (!($("#custom_fields").mpCustomfield('validate'))) {
                //custom field error 
                valid = false;
                //display error 
                this.showMessage('danger', wkMpTrans.customfield_error_msg);
            }
            // validate images if required 
            var imageIsRequired = document.getElementById('wk-image-required').value;
            var oldImageCount = document.getElementById('wk-images-count').value;
            if (parseInt(imageIsRequired) && !parseInt(oldImageCount)) {
                // check if new images added
                if (!$("input[name='form[image_url][]']").length) {
                    valid = false;
                    //display error 
                    this.showMessage('danger', wkMpTrans.required_image_error_msg);
                    $(".wk-uploader").addClass("is-invalid");
                }
            }
            var intervalCount = 0;
            var validationInterval = window.setInterval(function () {
                intervalCount++;
                if (intervalCount > 5) {
                    clearInterval(validationInterval);
                } else {
                    if ($('.is-invalid').length > 0) {
                        var tabPane = $('.is-invalid')[0].closest(".tab-pane");
                        if ($(tabPane).length > 0) {
                            var elId = $(tabPane).attr('id');
                            $('a[href="#' + elId + '"]')[0].click();
                            clearInterval(validationInterval);
                        }
                    }
                }
            }, 200);
            return valid;
        }

    };
    $.fn.mpAddProduct = function (options) {
        var args = arguments;
        if (typeof options === "string") {
            var returnResponse;
            this.each(function () {
                var plugin = $.data(this, pluginName);
                if (plugin instanceof mpAddProduct) {
                    if (typeof plugin.methods[options] === "function") {
                        returnResponse = plugin.methods[options].apply(plugin, Array.prototype.slice.call(args, 1));
                    } else {
                        $.error("Method " + options + " does not exist in jQuery.mpAddProduct");
                    }
                } else {
                    $.error("Unknown plugin data found by jQuery.mpAddProduct");
                }

            });
            return returnResponse;
        } else {
            return this.each(function () {
                if (!$.data(this, pluginName)) {
                    $.data(this, pluginName, new mpAddProduct(this, options));
                }
            });
        }
    };
}));


// image drag drop plugin

(function (factory) {
    "use strict";
    if (typeof define === "function" && define.amd) {
        // AMD. Register as an anonymous module.
        define(["jquery"], factory);
    } else if (typeof exports !== "undefined") {
        module.exports = factory(require("jquery"));
    } else {
        // Browser globals
        factory(window.jQuery);
    }
}(function ($) {
    "use strict";
    var pluginName = "wkImage";
    // These are the plugin defaults values
    var wkImageDefaults = {
        removeSelector: '.pimage-remove',
        previewSelector: '.pimage-preview',
        multiple: false,
        enableDrag: false,
        extraData: {},
        headers: {},
        dataType: null,
        fieldName: "file",
        maxFileSize: 0,
        allowedTypes: "*",
        extFilter: null,
        imageIsRequired: 0,
        oldImageCount: 0,
        limit:0,
        resetFileField: true,
        onInit: function () { },
        onChangeInput: function () { },
        onComplete: function () { },
        onFallbackMode: function () { },
        onNewFile: function () { },        //params: id, file
        onFileTypeError: function () { },  //params: file
        onFileSizeError: function () { },  //params: file
        onFileExtError: function () { },   //params: file
        onDragEnter: function () { },
        onDragLeave: function () { },
        onDocumentDragEnter: function () { },
        onDocumentDragLeave: function () { }
    };
    var wkImageFile = function (file, widget) {
        this.data = file;
        this.widget = widget;
        this.jqXHR = null;
        // The file id doesnt have to bo that special.... or not?
        this.id = Math.random().toString(36).substr(2);
    };

    var wkImage = function (element, options) {
        this.element = $(element);
        this.settings = $.extend({}, wkImageDefaults, options);
        if (!this.checkSupport()) {
            $.error("Browser not supported by jQuery.wkImage");
            this.settings.onFallbackMode.call(this.element);
            return false;
        }
        this.init();
        return this;
    };

    wkImage.prototype.checkSupport = function () {
        // This one is mandatory for all modes
        if (typeof window.FormData === "undefined") {
            return false;
        }

        // Test based on: Modernizr/feature-detects/forms/fileinput.js
        var exp = new RegExp(
            "/(Android (1.0|1.1|1.5|1.6|2.0|2.1))|" +
            "(Windows Phone (OS 7|8.0))|(XBLWP)|" +
            "(ZuneWP)|(w(eb)?OSBrowser)|(webOS)|" +
            "(Kindle\/(1.0|2.0|2.5|3.0))/");

        if (exp.test(window.navigator.userAgent)) {
            return false;
        }

        return !$("<input type=\"file\" />").prop("disabled");
    };

    wkImage.prototype.init = function () {
        var widget = this;
        this.queue = [];
        this.queuePos = -1;
        this.queueRunning = false;
        this.activeFiles = 0; this.queue = [];
        this.queuePos = -1;
        this.queueRunning = false;
        this.activeFiles = 0;
        this.draggingOver = 0;
        this.draggingOverDoc = 0;
        // check if element is input itself or has child input
        var input = widget.element.is("input[type=file]") ?
            widget.element : widget.element.find("input[type=file]");
        if (input.length > 0) {
            input.prop("multiple", this.settings.multiple);
            input.on("change." + pluginName, function (evt) {
                var files = evt.target && evt.target.files;
                if (!files || !files.length) {
                    return;
                }
                widget.settings.onChangeInput.call(this.element);
                widget.addFiles(files);
                if (widget.settings.resetFileField) {
                    $(this).val("");
                }
                
            });
        }
        if (this.settings.enableDrag) {
            this.initDragDrop();
        }
        if (input.length === 0 && !this.settings.enableDrag) {
            // Trigger an error because if this happens the plugin wont do anything.
            $.error("Markup error found by jQuery.wkImage");
            return null;
        }
        //  is image required : already added 
        // validate images if required 
        if (document.getElementById('wk-image-required')) {
            this.settings.imageIsRequired = document.getElementById('wk-image-required').value;
        }
        if (document.getElementById('wk-images-count')) {
            this.settings.oldImageCount = document.getElementById('wk-images-count').value;
        }
        this.settings.onInit.call(this.element);
        //bind events 
        this.bindEvents();
        return this;
    };
    wkImage.prototype.bindEvents = function () {
        var widget = this;
        
        if (this.settings.removeSelector) {
            $('body').on("click", this.settings.removeSelector, function (e) {
                e.preventDefault();
                $(this).closest(widget.settings.previewSelector).remove();
                // if required and no image add error class 
                
                if (parseInt(widget.settings.imageIsRequired) && !parseInt(widget.settings.oldImageCount)) {
                    // check if new images added
                    if (!$("input[name='form[image_url][]']").length) {
                        //display error 
                        $(".wk-uploader").addClass("is-invalid");
                    }
                }
            });
        }
        
    };
    wkImage.prototype.initDragDrop = function () {
        var widget = this;
        // -- Now our own Drop
        widget.element.on("drop." + pluginName, function (evt) {
            evt.preventDefault();
            if (widget.draggingOver > 0) {
                widget.draggingOver = 0;
                widget.settings.onDragLeave.call(widget.element);
            }

            var dataTransfer = evt.originalEvent && evt.originalEvent.dataTransfer;
            if (!dataTransfer || !dataTransfer.files || !dataTransfer.files.length) {
                return;
            }

            // Take only the first file if not acepting multiple, this is kinda ugly. Needs Review ?
            var files = [];

            if (widget.settings.multiple) {
                files = dataTransfer.files;
            } else {
                files.push(dataTransfer.files[0]);
            }
            widget.addFiles(files, true);


        });

        //-- These two events/callbacks are onlt to maybe do some fancy visual stuff
        widget.element.on("dragenter." + pluginName, function (evt) {
            evt.preventDefault();

            if (widget.draggingOver === 0) {
                widget.settings.onDragEnter.call(widget.element);
            }

            widget.draggingOver++;
        });

        widget.element.on("dragleave." + pluginName, function (evt) {
            evt.preventDefault();

            widget.draggingOver--;

            if (widget.draggingOver === 0) {
                widget.settings.onDragLeave.call(widget.element);
            }
        });

        if (!widget.settings.hookDocument) {
            return;
        }
        // Adding some off/namepacing to prevent some weird cases when people use multiple instances
        $(document).off("drop." + pluginName).on("drop." + pluginName, function (evt) {
            evt.preventDefault();
            if (widget.draggingOverDoc > 0) {
                widget.draggingOverDoc = 0;
                widget.settings.onDocumentDragLeave.call(widget.element);
            }
        });

        $(document).off("dragenter." + pluginName).on("dragenter." + pluginName, function (evt) {
            evt.preventDefault();

            if (widget.draggingOverDoc === 0) {
                widget.settings.onDocumentDragEnter.call(widget.element);
            }

            widget.draggingOverDoc++;
        });

        $(document).off("dragleave." + pluginName).on("dragleave." + pluginName, function (evt) {
            evt.preventDefault();

            widget.draggingOverDoc--;

            if (widget.draggingOverDoc === 0) {
                widget.settings.onDocumentDragLeave.call(widget.element);
            }
        });

        $(document).off("dragover." + pluginName).on("dragover." + pluginName, function (evt) {
            evt.preventDefault();
        });
    };
    wkImage.prototype.releaseEvents = function () {
        // Leave everyone ALONE ;_;

        this.element.off("." + pluginName);
        this.element.find("input[type=file]").off("." + pluginName);

        if (this.settings.hookDocument) {
            $(document).off("." + pluginName);
        }
    };

    wkImage.prototype.validateFile = function (file) {
        // Check file size
        if ((this.settings.maxFileSize > 0) &&
            (file.size > this.settings.maxFileSize)) {

            this.settings.onFileSizeError.call(this.element, file);

            return false;
        }

        // Check file type
        if ((this.settings.allowedTypes !== "*") &&
            !file.type.match(this.settings.allowedTypes)) {

            this.settings.onFileTypeError.call(this.element, file);

            return false;
        }

        // Check file extension
        if (this.settings.extFilter !== null) {
            var ext = file.name.toLowerCase().split(".").pop();

            if ($.inArray(ext, this.settings.extFilter) < 0) {
                this.settings.onFileExtError.call(this.element, file);

                return false;
            }
        }

        return new wkImageFile(file, this);
    };

    wkImage.prototype.addFiles = function (files, drop = false) {
        var nFiles = 0;
        for (var i = 0; i < files.length; i++) {
            if (this.settings.limit && i >= this.settings.limit) {
                break;
            }
            var file = this.validateFile(files[i]);
            if (!file) {
                continue;
            }
            // If the callback returns false file will not be processed. This may allow some customization
            var can_continue = this.settings.onNewFile.call(this.element, file.id, file.data, drop);
            if (can_continue === false) {
                continue;
            }
            this.queue.push(file);
            nFiles++;
        }

        // No files were added
        if (nFiles === 0) {
            return this;
        }
        // remove error class         
        $(".wk-uploader").removeClass("is-invalid");
            
        return this;
    };

    $.fn.wkImage = function (options) {
        var args = arguments;

        if (typeof options === "string") {
            this.each(function () {
                var plugin = $.data(this, pluginName);

                if (plugin instanceof wkImage) {
                    if (typeof plugin.methods[options] === "function") {
                        plugin.methods[options].apply(plugin, Array.prototype.slice.call(args, 1));
                    } else {
                        $.error("Method " + options + " does not exist in jQuery.wkImage");
                    }
                } else {
                    $.error("Unknown plugin data found by jQuery.wkImage");
                }
            });
        } else {
            return this.each(function () {
                if (!$.data(this, pluginName)) {
                    $.data(this, pluginName, new wkImage(this, options));
                }
            });
        }
    };
}));
$(document).ready(function() {
    // $('#form_sales_price').hide();

    var discountTypeValue = $('#discount_type_form').val();
    if (discountTypeValue === 'PERCENT') {
        // If the value is 'PERCENT', select the 'percent' option
        $('#percent').prop('selected', true);
    } else {
        // If the value is anything else, select the 'amount' option
        $('#amount').prop('selected', true);
    }
})
// general scripts
$(document).ready(function () {
     //add wkImage plugin 
     $('input[name="form[images][]"]').wkImage({ //
        enableDrag: true,
        multiple: true,
        removeSelector: '.pimage-remove',
        previewSelector: '.pimage-preview',
        resetFileField: false,
        onChangeInput: function (e) { //alert("hi");
            $(".pimage-preview-container").html('');
        },
        onNewFile: function (id, file, drop) { //alert(imgCount);
            
            // Make sure `file.name` matches our extensions criteria
            if (!/\.(jpe?g|png|gif)$/i.test(file.name)) {
                return alert(file.name + " is not an image");
            } // else...
            var reader = new FileReader();

            reader.addEventListener("load", function () {
                var image = new Image();
                var previewClass = '';
                // if (drop) {
                //     previewClass = 'img-dd-preview';
                // } else {
                //     previewClass = 'img-file-preview';
                // }

                // For thumbnail
                // var thumbnailEle = $(".thumb-radio"); //console.log(thumbnailEle);
                // var thumbIndex = thumbnailEle.length - 1;
                // var lastThumb = $(thumbnailEle[thumbIndex]).val();
                // lastThumb = Number(lastThumb) + 1;

                // if(isNaN(lastThumb)){
                //     lastThumb = 0;
                // }
                
                image.height = 100;
                image.title = file.name;
                image.src = this.result;
                var previewImg = $("<div class='pimage-preview'></div>");
                $('.pimage-preview-container').append(previewImg); //console.log(image);
                //$(previewImg).remove("");
                $(previewImg).append(image);
                //$(previewImg).append(image);
                var fileUrl = $("<input type='url' value='" + this.result + "' name='form[image_url][]' class='d-none pimage_url'/>");
                // var filename = $("<input type='text' value='" + file.name + "' name='form[file_name][]' class='d-none pimage_url'/>");
                //var thumbnailRadio = $("<input type='radio' value='"+lastThumb+"' name='form[default_thumbnail]' class='thumb-radio wk-control-input'/>");
                //var thumbLbl = $("<label class='thumb-lbl'>Make Image Default</label>");
                //console.log(fileUrl);
                //var removeLink = $("<span class='fa fa-times-circle pimage-remove'></span>");
                //$(previewImg).append(thumbnailRadio);
                //$(previewImg).append(thumbLbl);
                $(previewImg).append(fileUrl);
                // $(previewImg).append(filename);
                //$(previewImg).append(thumbnailRadio);
                //$(previewImg).append(removeLink);
                //display clear button 
                //$(".pimage-clear-all").show();
                // generate img url if drop

            });

            reader.readAsDataURL(file);

            //check if add image modal is enable : close
            if ($('#add_image').is(':visible')) {
                // modal issue 
                //$("#add_image").modal('close');
                $('#add_image').find("button[data-dismiss='modal']").trigger("click");
            }
        },
        onFallbackMode: function () {

        },
        onFileSizeError: function (file) {

        }
    });


    function initializeWkImage(inputName, previewClass) {
        $('input[name="' + inputName + '"]').wkImage({
            enableDrag: true,
            multiple: false,
            removeSelector: '.pimage-remove',
            previewSelector: '.pimage-preview',
            resetFileField: false,
            onNewFile: function (id, file, drop) {
                if (!/\.(jpe?g|png|gif|heic|heif)$/i.test(file.name)) {
                    return alert(file.name + " is not an image");
                }
                if(file.size > 5000000) {
                    return swal({
                        title: 'Please upload file less than 5MB. Thanks!!',
                        icon: "error",
                    });
                }
                
                let fileFormat = ['heic', 'heif'];
                if (fileFormat.indexOf(file.name.split('.').pop().toLowerCase()) > -1) {
                    $(".wk-overlay").show();
                    let fileConvert = new Promise(function(resolve, reject) {
                        heic2any({
                            blob: file,
                            toType: 'image/jpeg',
                            quality: 0.9
                        }).then(function(resultBlob) {
                            $('.' + previewClass).html('');
                            file = new File([resultBlob], "heic"+".jpg",{type:"image/jpeg", lastModified:new Date().getTime()});
                            var reader = new FileReader();
                            reader.addEventListener("load", function () {
                                var image = new Image();
                                image.height = 100;
                                image.title = file.name;
                                image.src = this.result;

                                var previewImg = $("<div class='pimage-preview'></div>");
                                $('.' + previewClass).append(previewImg);
                                $(previewImg).append(image);
                                var fileUrl = $("<textarea type='url' value='" + this.result + "' name='form[image_url][]' class='d-none pimage_url'>" + this.result + "</textarea>");
                                $(previewImg).append(fileUrl);
                                resolve('success'); // when successful
                            });

                            reader.readAsDataURL(file);

                        }).catch(function(error) {
                            console.error(error);
                            console.error('Error converting HEIC to JPEG.');
                            reject(error)
                        })
                    });

                    fileConvert.then(
                        function(value) {
                            $(".wk-overlay").hide();
                        },
                        function(error) {
                            $(".wk-overlay").hide();
                        }
                    );

                } else {

                    $('.' + previewClass).html('');
                    var reader = new FileReader();

                    reader.addEventListener("load", function () {
                        var image = new Image();
                        image.height = 100;
                        image.title = file.name;
                        image.src = this.result;

                        var previewImg = $("<div class='pimage-preview'></div>");
                        $('.' + previewClass).append(previewImg);
                        $(previewImg).append(image);

                        var fileUrl = $("<textarea type='url' value='" + this.result + "' name='form[image_url][]' class='d-none pimage_url'>" + this.result + "</textarea>");
                        $(previewImg).append(fileUrl);
                    });

                    reader.readAsDataURL(file);

                }
            },
            onFallbackMode: function () {
                // Your existing fallback mode handling
            },
            onFileSizeError: function (file) {
                // Your existing file size error handling
            }
        });
    }
    
    // Example usage
    var fileInputs = ['form[images1]', 'form[images2]', 'form[images3]', 'form[images4]', 'form[images5]', 'form[images6]'];
    var previewClasses = ['pre1', 'pre2', 'pre3', 'pre4', 'pre5', 'pre6'];
    
    for (var i = 0; i < fileInputs.length; i++) {
        initializeWkImage(fileInputs[i], previewClasses[i]);
    }
    
    //image by url 
    // $("#add-pimage-url").on("click", function (e) { 
    //     e.preventDefault();
    //     var urlInput = $("#form_pimage_url");
    //     var imageUrl = urlInput.val();
    //     if (!imageUrl || imageUrl.trim() == "") {
    //         //error
    //         $(urlInput).addClass('wk-input-error');
    //         return false;
    //     }
    //     var imgReg = /(http(s ?):)([/|.|\w|\s|-])*\.(?:jpeg|jpg|png)/g;
    //     if (!imgReg.test(imageUrl)) {
    //         //error
    //         $(urlInput).addClass('wk-input-error');
    //         return false;
    //     }
    //     //add preview image 
    //     var image = new Image();
    //     image.height = 100;
    //     image.src = imageUrl;

    //     // For thumbnail
    //     // var thumbnailEle = $(".thumb-radio"); //console.log(thumbnailEle);
    //     // var thumbIndex = thumbnailEle.length - 1;
    //     // var lastThumb = $(thumbnailEle[thumbIndex]).val();
    //     // lastThumb = Number(lastThumb) + 1;

    //     // if(isNaN(lastThumb)){
    //     //     lastThumb = 0;
    //     // }

    //     var previewImg = $("<div class='pimage-preview'></div>");
    //     $('.pimage-preview-container').append(previewImg);
    //     $(previewImg).append(image);
    //     var fileUrl = $("<input type='url' value='" + imageUrl + "' name='form[image_url][]' class='d-none pimage_url'/>");
    //     var removeLink = $("<span class='fa fa-times-circle pimage-remove'></span>");

    //     //var thumbnailRadio = $("<input type='radio' value='"+lastThumb+"' name='form[default_thumbnail]' class='thumb-radio wk-control-input'/>");
    //     //var thumbLbl = $("<label class='thumb-lbl'>Make Image Default</label>");

    //     //$(previewImg).append(thumbnailRadio);
    //     //$(previewImg).append(thumbLbl);
        
    //     $(previewImg).append(fileUrl);
    //     $(previewImg).append(removeLink);

    //     //display clear button 
    //     //$(".pimage-clear-all").show();
    //     //clear this field
    //     $(urlInput).val("");
    //     //close popup
    //     // modal issue 
    //     //$("#add_image").modal('close');
    //     $('#add_image').find("button[data-dismiss='modal']").trigger("click");
    // });

    //bind add product plugin 
    $('form[name="wixmp_product_form"]').mpAddProduct();

    $("[data-toggle='ajaxjobs']").each(function (i, v) {
        $(this).ajaxJobs({
            ajaxUrl: $(this).attr('data-href'),
            limit: 100,
            locale: {
                title: wkMpTrans.ajaxjobsModalTitle,
                close_btn: wkMpTrans.modalClose,
                processing_job: wkMpTrans.ajaxjobsProcessingJobs,
                message_handle: wkMpTrans.messageText,
                cancel_job_close_modal: wkMpTrans.cancelRunningJobs,
                are_you_sure: wkMpTrans.are_you_sure,
                ok_btn: wkMpTrans.ok_btn,
                cancel_btn: wkMpTrans.cancel_btn
            },
            onAjaxError: function(element , error){
                let arr = $(this).attr('data-href').split('/');
                if(arr != '' && arr[arr.length - 1] == 'sync' && arr[arr.length - 2] == 'catalog' ){
                  let url = arr.join('/')+'/cache';
                   $.ajax({
                       url: url,
                       method: "POST",
                       dataType: 'json',
                       success: function(response){
       
                       }
                   })
       
                }
            }
        });
    });

    // bind ajax jobs using self biding
    $("#sync-order").ajaxJobs({
        ajaxUrl: $("#sync-order").attr('data-href'),
        locale: {
            title: wkMpTrans.ajaxjobsModalTitle,
            close_btn: wkMpTrans.modalClose,
            processing_job: wkMpTrans.ajaxjobsProcessingJobs,
            message_handle: wkMpTrans.messageText,
            cancel_job_close_modal: wkMpTrans.cancelRunningJobs,
            are_you_sure: wkMpTrans.are_you_sure,
            ok_btn: wkMpTrans.ok_btn,
            cancel_btn: wkMpTrans.cancel_btn
        },
        onBeforeRun: function (el) {
            //var toDate = $('input[name="order[sync][to_date]"]').val();
            //var fromDate = $('input[name="order[sync][from_date]"]').val();
            //var minOrderId = $('input[name="order[sync][min_order_id]"]').val();
            //var maxOrderId = $('input[name="order[sync][max_order_id]"]').val();
            var orderIds = $('textarea[name="order[sync][store_order_ids]"]').val();

            //el.formData.append("toDate", toDate);
            //el.formData.append("fromDate", fromDate);
            //el.formData.append("minOrderId", minOrderId);
            //el.formData.append("maxOrderId", maxOrderId);
            el.formData.append("orderIds", orderIds);
        },
        onBeforeModalDisplay: function (element, modal) {
            $("#orderSyncModal").modal('hide');
        },
    });

    $('body').on("keypress", '#syncOrderIds', function (e) {
        var key = e.which;

        if ((key < 48 || key > 57) && key != 44) {
            e.preventDefault();
        }

    });

    $("#WixImportForm").ajaxJobs({
        ajaxUrl: $("#WixImportForm").attr('action'),
        isForm: true,
        limit: 1,
        locale: {
            title: wkMpTrans.ajaxjobsModalTitle,
            close_btn: wkMpTrans.modalClose,
            processing_job: wkMpTrans.ajaxjobsProcessingJobs,
            message_handle: wkMpTrans.messageText,
            cancel_job_close_modal: wkMpTrans.cancelRunningJobs,
            are_you_sure: wkMpTrans.are_you_sure,
            ok_btn: wkMpTrans.ok_btn,
            cancel_btn: wkMpTrans.cancel_btn
        },
        onBeforeModalDisplay: function (element, modal) {
            $("#csvImportWixProducts").modal('hide');
        },
    });

    $('.wk-wix-export-csv').on('click', function (e) {
		e.preventDefault();
		var $target_url = $(this).data('href');//(e.currentTarget);
		$.ajax({
            url: $target_url,
            data: {
                enclosure: '"',
                delimiter: 'C',
                filename: 'sample.txt',
            },
			method: 'POST',
			beforeSend: function () {
				$(".wk-overlay").show();
			},
			success: function (data) {
				if(data.status == 200 && data.redirect_url != ''){
					window.open(data.redirect_url, '_blank');
				}
				if(data.notification.type && data.notification.message && data.notification.message != ''){
					show_message(data.notification.type, data.notification.message);
				}
			},
			complete: function () {
				$(".wk-overlay").hide();
				$('#buttonExportFormClose').trigger('click');
			}
		});
	});

    // bind ajax jobs on form 
    //export form 
    $("#wixMpSellerExportForm").ajaxJobs({
        ajaxUrl: $("#wixMpSellerExportForm").attr('action'),
        isForm: true,
        locale: {
            title: wkMpTrans.ajaxjobsModalTitle,
            close_btn: wkMpTrans.modalClose,
            processing_job: wkMpTrans.ajaxjobsProcessingJobs,
            message_handle: wkMpTrans.messageText,
            cancel_job_close_modal: wkMpTrans.cancelRunningJobs,
            are_you_sure: wkMpTrans.are_you_sure,
            ok_btn: wkMpTrans.ok_btn,
            cancel_btn: wkMpTrans.cancel_btn
        },
        onBeforeModalDisplay: function (element, modal) {
            $("#csvExportSellers").modal('hide');
        },
        onJobComplete: function (response, hasMore) {
            if (!hasMore && response.redirect_url && response.totalCount) {
                //open csv window
                window.open(response.redirect_url, '_blank');
            }
        }
    });

    function show_message(n_type, n_message) {
		var notification = '';
		notification += '<div class="alert alert-dismissible alert-' + n_type + '" role="alert">'
		notification += '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
		notification += '<span aria-hidden="true">&times;</span>';
		notification += '</button>';
		notification += n_message;
		notification += '</div>';
		$('.messages').append(notification);
    }
    $(document).on("click", "#bulkMail-alert", function() {
        var formData = $('input[name="order_ids[]"]:checked').serializeArray();
        if (formData.length <= 0) {
            swal({
                title:"Warning?",
                text:wkMpTrans.select_item_for_send_bulk_mail,
                icon:"warning",
                buttons:true,
                dangerMode:true,
                })
                .then((wilCancel) => {
                if (wilCancel) {
                    return false;
                // $(this).closest('form').submit();
                }
                });
        } else {
            $("#bulkMail").trigger('click');
        }    
    });
    $("#bulkMail").ajaxJobs({
        ajaxUrl: $("#bulkMail").attr('data-href'),
    
        locale: {
            title: wkMpTrans.ajaxjobsModalTitle,
            close_btn: wkMpTrans.modalClose,
            processing_job: wkMpTrans.ajaxjobsProcessingJobs,
            message_handle: wkMpTrans.messageText,
            cancel_job_close_modal: wkMpTrans.cancelRunningJobs,
            are_you_sure: wkMpTrans.are_you_sure,
            ok_btn: wkMpTrans.ok_btn,
            cancel_btn: wkMpTrans.cancel_btn
        },
        onBeforeRun: function (el) {
            var formData = $('input[name="order_ids[]"]:checked').serializeArray();
            var ids = [];
            $.each(formData, function (i, v) {
                ids = ids.concat(v.value);
            });
            
            el.formData.append("order_Ids", ids);
        },
        onJobComplete: function (response, hasMore) {
            if (!hasMore && response.redirect_url && response.totalCount) {
                //open csv window
                window.open(response.redirect_url, '_blank');
            }
        }
    });

    $(document).on('click', "#account_wix_order_view", function (event) {
        var dataUrl = $(this).data('url');
        window.open(dataUrl,'account_wix_order_view','width=1000,height=1000,menubar=no,toolbar=no, location=no');
    });

    // single seller pay
    $(".pay-single-seller").on('click', function (e) {
        swal({
            title: wkMpTrans.are_you_sure,
            text: wkMpTrans.wix_wixmp_send_payout_question,
            icon: "warning",
            buttons: [wkMpTrans.cancel_btn, wkMpTrans.ok_btn],
        }) .then((allow) => {
            if (allow) {
                e.preventDefault();
                $("input[name='seller_ids[]']").prop('checked', false);
                var sellerId = $(this).data('seller-id');
                if (sellerId) {
                    // checkbox checked
                    $("input[name='seller_ids[]'][value='" + sellerId + "']").prop('checked', true);
                    // select batch action 
                    $("select[name='commission[batch_action]']").val('pay');
                    // Set value for payment type
                    var paymentType = $(this).data('payment-type');
                    if (paymentType != "") {
                        $("input[name='payment_type']").val(paymentType);
                    }
                    //submit form
                    $(this).closest('form').submit();
                    return false;
                }
                console.error('Not found sellerId or payout Form');
                return false;
            }
        })
        return false;
    });

    // $("#form_name").on('blur', function (e) {
    //     var productName = $("#form_name").val();
    //     productName = productName.replace(" ","");
    //     productName = productName.toUpperCase();
    //     var sku = productName.substr(0,4);
    //     sku = sku + Math.floor(1000 + Math.random() * 100);
    //     $("#form_sku").val(sku);
    // });

    $(".messages").delay(3000).fadeOut(800);

    var enableAutoPay = $("#custom_form_enable_auto_pay").val();
    if (enableAutoPay == 1) {
        $("#auto_pay_order_status").show();
    } else {
        $("#auto_pay_order_status").hide();
    }

    $("#custom_form_enable_auto_pay").on('change', function (e) {
        var enableAutoPay = $("#custom_form_enable_auto_pay").val();
        if (enableAutoPay == 1) {
            $("#auto_pay_order_status").show();
        } else {
            $("#auto_pay_order_status").hide();
        }
    });
    
    var enableAutoPayStripe = $("#custom_form_stripe_enable_auto_pay").val();
    if (enableAutoPayStripe == 1) {
        $("#stripe_auto_pay_order_status").show();
    } else {
        $("#stripe_auto_pay_order_status").hide();
    }

    $("#custom_form_stripe_enable_auto_pay").on('change', function (e) {
        var enableAutoPayStripe = $("#custom_form_stripe_enable_auto_pay").val();
        if (enableAutoPayStripe == 1) {
            $("#stripe_auto_pay_order_status").show();
        } else {
            $("#stripe_auto_pay_order_status").hide();
        }
    });

    $('.wk-wix-seller-fullfillment_status').change(function() {
       
        var statusEvent = 0;
        if ($(this).prop('checked')) {
            statusEvent = 1;
        } else {
            statusEvent = 0;
        }
        var baseUrl = $(this).closest('input').attr('data-base-url');
        var Id = $(this).closest('input').attr('id');
        
        if (statusEvent == 1) { 
            swal({
                title: 'You cannot turn "Off" the toggle button once marked "On" !',
                text: 'Are you sure ?',
                icon: "warning",
                buttons: ['Cancel', 'Confirm'],
                dangerMode: true,
            }).then((allow) => {
                if (allow) {
                    $.ajax({
                        url: baseUrl,
                        type: 'POST',
                        data: {
                            fullfillmentStatus: statusEvent
                        },
                        beforeSend: function() {
                            $('.wk-overlay').removeClass('wk-hidden');
                            $('.wk-overlay').show();
                        },
                        success: function(data) {
                            $("#"+Id).attr('disabled', true);
                            $('.wk-overlay').addClass('wk-hidden');
                            window.location.reload();
                        }
                    });
                } else {
                    $(this).closest('input').prop('checked', false);
                }
            });
        } else {
            $.ajax({
                url: baseUrl,
                type: 'POST',
                data: {
                    fullfillmentStatus: statusEvent
                },
                beforeSend: function() {
                    $('.wk-overlay').removeClass('wk-hidden');
                    $('.wk-overlay').show();
                },
                success: function(data) {
                    $('.wk-overlay').addClass('wk-hidden');
                }
            });
        } 
    });

    $("select.wix-fullfillment-batch-action-list").on("change", function() {

        var this_this = this;
        if (!$(this).val() || $(this).val == '') {
            return; //no task for blank batch action
        }
        if (this.form) {
            // batch action can only be performed on form
            // check for checkboxes : Class must be wk_checkbox_item in layout file
            if ($("input.wk_checkbox_item:checked").length <= 0) {
                // select atleast one row to perform batch action 
                swal({
                    title: 'No orders selected',
                    text: 'Select atleast one order to perform batch action',
                    icon: "warning",
                });
                // deselect batch action
                $(this_this).val("");
            } else {
                swal({
                    title: 'You cannot turn "Off" the toggle button once marked "On" !',
                    text: wkMpTrans.confirm_to_process_batch_action + ' -' + $(this).val(),
                    icon: "warning",
                    buttons: [wkMpTrans.cancel_btn, wkMpTrans.ok_btn],
                    dangerMode: true,
                }).then((performAction) => {
                    if (performAction) {
                        $(".wk-overlay").show();
                        this_this.form.submit();
                    } else {
                        // deselect batch action
                        $(".wk-overlay").hide();
                        $(this_this).val("");
                    }
                });
            }
        }
    });

    //$(".wk-archieve-row-js").each(function (i, v) { 
    document.addEventListener('click', function(event) {
        
        if(event.target.matches(".wk-archieve-row-js")) {

            let activeElm = this.activeElement;
        
            swal({
                title: wkMpTrans.wixmp_archive_item_question,
                text: wkMpTrans.are_you_sure,
                icon: "warning",
                buttons: [wkMpTrans.cancel_btn, wkMpTrans.ok_btn],
                dangerMode: true,
            }).then((willArchieve) => {
                if (!willArchieve) {
                    
                } else {
                    // $(this).ajaxJobs({
                    //     ajaxUrl: $(activeElm).data("url"),
                    //     locale: {
                    //         title: wkMpTrans.ajaxjobs_disable_products,
                    //         close_btn: wkMpTrans.modalClose,
                    //         processing_job: wkMpTrans.ajaxjobsProcessingJobs,
                    //         message_handle: wkMpTrans.messageText,
                    //         cancel_job_close_modal: wkMpTrans.cancelRunningJobs,
                    //         are_you_sure: wkMpTrans.are_you_sure,
                    //         ok_btn: wkMpTrans.ok_btn,
                    //         cancel_btn: wkMpTrans.cancel_btn
                    //     },
                    //     onJobComplete: function (response, hasMore) {
                    //         if (!hasMore) {
                                
                    //             //show complete message 
                    //             Pam.renderMessages([{
                    //                 'type': 'success',
                    //                 'message': wkMpTrans.seller_archived_successfully
                    //             }]);
                    //             // update status 
                    //             var activeDdText = $(this).text();
                    //             var ddMain = $(this).closest('.dropdown');
                    //             ddMain.find('.dropdown-toggle').text(activeDdText);
                    //             ddMain.find('.dropdown-item').removeClass('disabled');
                    //             $(this).addClass('disabled');
                    //             $("#ajax-jobs-modal").modal("hide"); //hide modal
                    //             $(".wk-overlay").show(); 
                    //             setTimeout(function(){
                    //                 window.location.reload();
                    //             }, 2000);
                    //             //sucessAlert();
                    //         }
                    //     }
                    // }); 

                    $.ajax({
                        url: $(activeElm).data("url"),
                        method: 'POST',
                        beforeSend: function() {
                            $(".wk-overlay").show();
                        },
                        success: function(data) {
                            $(".wk-overlay").hide();
                            if (data.code == 400) {
                                swal({
                                    title: "Can't Aarchive this Item!",
                                    text: "",
                                    icon: "error",
                                    //buttons: true,
                                    dangerMode: false,
                                    closeModal: false
                                }).then((res) => {
                                    window.location.reload();
                                });
                            } else {
                                swal({
                                    title: "Archived this Item!",
                                    text: "",
                                    icon: "success",
                                    //buttons: true,
                                    dangerMode: false,
                                    closeModal: false
                                }).then((res) => {
                                    window.location.reload();
                                });
                            }
                        },
                        complete: function(response) { 
                        }
                    });
                }
            });
        }
    });

    // function sucessAlert()
    // {
    //     swal({
    //         title: "Archived this Item!",
    //         text: "",
    //         icon: "success",
    //         //buttons: true,
    //         dangerMode: false,
    //         closeModal: false
    //     }).then((res) => {
    //         $("#ajax-jobs-modal").modal("hide");
    //         // setTimeout(function(){
    //         //     window.location.reload();
    //         // }, 100);
    //     });
    // }
    
    $(".wk-unarchieve-row-js").on("click", function() {
        $.ajax({
            url: $(this).data('url'),
            method: 'POST',
            beforeSend: function() {
                $(".wk-overlay").show();
            },
            success: function(data) {
                $(".wk-overlay").hide();
                if (data.code == 400) {
                    swal({
                        title: wkMpTrans.wix_wixmp_cannot_unarchive_item,
                        text: "",
                        icon: "error",
                        //buttons: true,
                        dangerMode: false,
                        closeModal: false
                    }).then((res) => {
                        window.location.reload();
                    });
                } else {
                    swal({
                        title: wkMpTrans.wix_wixmp_unarchived_item,
                        text: "",
                        icon: "success",
                        //buttons: true,
                        dangerMode: false,
                        closeModal: false
                    }).then((res) => {
                        window.location.reload();
                    });
                }
            },
            complete: function(response) { 
            }
        });
    });

    //seller disable ajaxJobs 
    $(".seller-status-D").each(function (i, v) {
        $(this).ajaxJobs({
            ajaxUrl: $(this).attr('href'),
            locale: {
                title: wkMpTrans.ajaxjobs_disable_products,
                close_btn: wkMpTrans.modalClose,
                processing_job: wkMpTrans.ajaxjobsProcessingJobs,
                message_handle: wkMpTrans.messageText,
                cancel_job_close_modal: wkMpTrans.cancelRunningJobs,
                are_you_sure: wkMpTrans.are_you_sure,
                ok_btn: wkMpTrans.ok_btn,
                cancel_btn: wkMpTrans.cancel_btn
            },
            onJobComplete: function (response, hasMore) {
                if (!hasMore) {
                    //show complete message 
                    Pam.renderMessages([{
                        'type': 'success',
                        'message': wkMpTrans.seller_disabled_successfully
                    }]);
                    // update status 
                    var activeDdText = $(this).text();
                    var ddMain = $(this).closest('.dropdown');
                    ddMain.find('.dropdown-toggle').text(activeDdText);
                    ddMain.find('.dropdown-item').removeClass('disabled');
                    $(this).addClass('disabled');
                    $("#ajax-jobs-modal").modal("hide"); //hide modal 
                }
            }
        });
    });
});

$(document).ready(function(e) {
    $(".wix_start_date").datepicker({
        dateFormat: "yy-mm-dd",
        onSelect: function(date) {
            var date2 = $('.wix_start_date').datepicker('getDate');
            date2.setDate(date2.getDate() + 1);
            $('.wix_end_date').datepicker('setDate', date2);
            $('.wix_end_date').datepicker('option', 'minDate', date2);
        }
    });
    $('.wix_end_date').datepicker({
        dateFormat: "yy-mm-dd",
        onClose: function() {
            var dt1 = $('.wix_start_date').datepicker('getDate');
            var dt2 = $('.wix_end_date').datepicker('getDate');
            if (dt2 <= dt1) {
                var minDate = $('.wix_end_date').datepicker('option', 'minDate');
                $('.wix_end_date').datepicker('setDate', minDate);
            }
        }
    });

    var isValidate = true;
    var isComapnyName = true;
    var isEmail = true;  
    var isPhone = true;
    var isAddress = true;
    var isCity = true;
    var isState = true;
    var isZip = true;
    var isPassword = true;
    var isConfirmPassword = true;

    //validation for company name
    $('#seller_form_seller').on('blur', function () {
        var companyName = $(this).val();
        var minLength = 3;
        var maxLength = 40;
        if (companyName.length == 0) {
            isValidate = false;
            isComapnyName = false;
            $('.seller-company-name-msg').addClass('text-danger').text(wkMpTrans.warning_company_name_required);
            $(this).addClass('is-invalid').removeClass('valid-input');
        }
        else if (companyName.length < minLength) {
            isValidate = false;
            isComapnyName = false;
            $('.seller-company-name-msg').addClass('text-danger').text(wkMpTrans.warning_company_atleast3);
            $(this).addClass('is-invalid').removeClass('valid-input');
        }
        else if (companyName.length > maxLength) {
            isValidate = false;
            isComapnyName = false;
            $('.seller-company-name-msg').addClass('text-danger').text(wkMpTrans.warning_company_atmost40);
            $(this).addClass('is-invalid').removeClass('valid-input');
        }
        else {
            isValidate = true;
            isComapnyName = true;
            $('.seller-company-name-msg').empty();
            $(this).addClass('valid-input').removeClass('is-invalid');
        }
    });

    // valiadtion for email
    $('#seller_form_email').on('blur', function () {
        var emailAddress = $(this).val();
        var validEmail = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,7})+$/;
        if (emailAddress.length == 0) {
            isValidate = false;
            isEmail = false;
            $('.email-msg').addClass('text-danger').text(wkMpTrans.warning_email_required);
            $(this).addClass('is-invalid').removeClass('valid-input');
        }
        else if (!validEmail.test(emailAddress)) {
            isValidate = false;
            isEmail = false;
            $('.email-msg').addClass('text-danger').text(wkMpTrans.warning_enter_valid_email);
            $(this).addClass('is-invalid').removeClass('valid-input');
        }
        else {
            isValidate = true;
            isEmail = true;
            $('.email-msg').empty();
            $(this).addClass('valid-input').removeClass('is-invalid');
        }
    });

    //validation for phone
    $('#seller_form_phone').on('blur', function () {
        var phone = $(this).val();
        var filter = /^((\+[1-9]{1,4}[ \-]*)|(\([0-9]{2,3}\)[ \-]*)|([0-9]{2,4})[ \-]*)*?[0-9]{3,4}?[ \-]*[0-9]{3,4}?$/;
        var minLength = 8;
        var maxLength = 15;
        if (phone.length == 0) {
            isValidate = false;
            isPhone = false;
            $('.phone-msg').addClass('text-danger').text(wkMpTrans.warning_phone_required);
            $(this).addClass('is-invalid').removeClass('valid-input');
        }
        else if (!phone.match(filter)) {
            isValidate = false;
            isPhone = false;
            $('.phone-msg').addClass('text-danger').text(wkMpTrans.warning_phone_invalid);
            $(this).addClass('is-invalid').removeClass('valid-input');
        }
        else if (phone.length < minLength) {
            isValidate = false;
            isPhone = false;
            $('.phone-msg').addClass('text-danger').text(wkMpTrans.warning_phone_atleast8);
            $(this).addClass('is-invalid').removeClass('valid-input');
        }
        else if (phone.length > maxLength) {
            isValidate = false;
            isPhone = false;
            $('.phone-msg').addClass('text-danger').text(wkMpTrans.warning_phone_atmost15);
            $(this).addClass('is-invalid').removeClass('valid-input');
        }
        else {
            isValidate = true;
            isPhone = true;
            $('.phone-msg').empty();
            $(this).addClass('valid-input').removeClass('is-invalid');
        }
    });

    //validation for address
    $('#seller_form_address').on('blur', function () {
        var address = $(this).val();
        if (address.length == 0) {
            isValidate = false;
            isAddress = false;
            $('.address-msg').addClass('text-danger').text(wkMpTrans.warning_address_required);
            $(this).addClass('is-invalid').removeClass('valid-input');
        }
        else {
            isValidate = true;
            isAddress = true;
            $('.address-msg').empty();
            $(this).addClass('valid-input').removeClass('is-invalid');
        }
    });

    //validation for city
    $('#seller_form_city').on('blur', function () {
        var city = $(this).val();
        if (city.length == 0) {
            isValidate = false;
            isCity = false;
            $('.city-msg').addClass('text-danger').text(wkMpTrans.warning_city_required);
            $(this).addClass('is-invalid').removeClass('valid-input');
        }
        else {
            isValidate = true;
            isCity = true;
            $('.city-msg').empty();
            $(this).addClass('valid-input').removeClass('is-invalid');
        }
    });

    //validation for state
    $('#seller_form_state').on('blur', function () {
        var state = $(this).val();
        if (state.length == 0) {
            isValidate = false;
            isState = false;
            $('.state-msg').addClass('text-danger').text(wkMpTrans.warning_state_required);
            $(this).addClass('is-invalid').removeClass('valid-input');
        }
        else {
            isValidate = true;
            isState = true;
            $('.state-msg').empty();
            $(this).addClass('valid-input').removeClass('is-invalid');
        }
    });

    //validation for zipcode
    $('#seller_form_zipcode').on('blur', function () {
        var zip = $(this).val();
        var matches = "[0-9]";
        var minLength = 4;
        var maxLength = 8;
        if (zip.length == 0) {
            isValidate = false;
            isZip = false;
            $('.zipcode-msg').addClass('text-danger').text(wkMpTrans.warning_zip_required);
            $(this).addClass('is-invalid').removeClass('valid-input');
        }
        else if (!zip.match(matches)) {
            isValidate = false;
            isZip = false;
            $('.zipcode-msg').addClass('text-danger').text(wkMpTrans.warning_zip_invalid);
            $(this).addClass('is-invalid').removeClass('valid-input');
        }
        else if (zip.length < minLength) {
            isValidate = false;
            isZip = false;
            $('.zipcode-msg').addClass('text-danger').text(wkMpTrans.warning_zip_atleast4);
            $(this).addClass('is-invalid').removeClass('valid-input');
        }
        else if (zip.length > maxLength) {
            isValidate = false;
            isZip = false;
            $('.zipcode-msg').addClass('text-danger').text(wkMpTrans.warning_zip_atmost8);
            $(this).addClass('is-invalid').removeClass('valid-input');
        }
        else {
            isValidate = true;
            isZip = true;
            $('.zipcode-msg').empty();
            $(this).addClass('valid-input').removeClass('is-invalid');
        }
    });

    $('#seller_form_password').on('blur', function () {
        var password = $(this).val();
        var minLength = 4;
        var maxLength = 25;
        //var matches = "(?=^.{4,}$)(?=.*\d)(?=.*[!@#$%^&*]+)(?![.\n])(?=.*[A-Z])(?=.*[a-z]).*$";
        //var matches = "(?=^.{4,}$)(?=*\d)(?=.*[!@#$%^&*]+)(?![.\n])(?=.*[A-Z])(?=.*[a-z]).*$";
        
        if (password.length == 0) {
            isValidate = false;
            isPassword = false;
            $('.password-msg').addClass('text-danger').text("Password is required");
            $(this).addClass('is-invalid').removeClass('valid-input');
        } else if (password.length < minLength) {
            isValidate = false;
            isPassword = false;
            $('.password-msg').addClass('text-danger').text("Password must have atleast 4 digits");
            $(this).addClass('is-invalid').removeClass('valid-input');
        } else if (password.length > maxLength) {
            isValidate = false;
            isPassword = false;
            $('.password-msg').addClass('text-danger').text("Password must have atmost 25 digits");
            $(this).addClass('is-invalid').removeClass('valid-input');
        }  else if(containsWhitespace(password)){
            isValidate = false;
            isPassword = false;
            $('.password-msg').addClass('text-danger').text("Password must not contain Space !");
            $(this).addClass('is-invalid').removeClass('valid-input');
        } else {
            isValidate = true;
            isPassword = true;
            $('.password-msg').empty();
            $(this).addClass('valid-input').removeClass('is-invalid');
        }
    });

    $('#seller_form_confirmPassword').on('blur', function () {
        var confirmPassword = $(this).val();
        var password = $("#seller_form_password").val();
        var minLength = 4;
        var maxLength = 25;
        if (confirmPassword.length == 0) {
            isValidate = false;
            isConfirmPassword = false;
            $('.confirm-password-msg').addClass('text-danger').text("Confirm Password is required");
            $(this).addClass('is-invalid').removeClass('valid-input');
        } else if (confirmPassword.length < minLength) {
            isValidate = false;
            isConfirmPassword = false;
            $('.confirm-password-msg').addClass('text-danger').text("Password must have atleast 4 digits");
            $(this).addClass('is-invalid').removeClass('valid-input');
        } else if (confirmPassword.length > maxLength) {
            isValidate = false;
            isConfirmPassword = false;
            $('.confirm-password-msg').addClass('text-danger').text("Password must have atmost 25 digits");
            $(this).addClass('is-invalid').removeClass('valid-input');
        } else if(password != confirmPassword) {
            isValidate = false;
            isConfirmPassword = false;
            $('.confirm-password-msg').addClass('text-danger').text("Confirm Password and Password doesn't match !");
            $(this).addClass('is-invalid').removeClass('valid-input');
        } else if(containsWhitespace(confirmPassword)){
            isValidate = false;
            isConfirmPassword = false;
            $('.confirm-password-msg').addClass('text-danger').text("Confirm Password must not contain Space !");
            $(this).addClass('is-invalid').removeClass('valid-input');
        } else {
            isValidate = true;
            isConfirmPassword = true;
            $('.confirm-password-msg').empty();
            $(this).addClass('valid-input').removeClass('is-invalid');
        }
    });

    $('#wix_seller_register_btn').click(function() {
        
        var password = $("#seller_form_password").val();
        var confirmPassword = $("#seller_form_confirmPassword").val();
        
        if (password != confirmPassword) {
            $('.confirm-password-msg').addClass('text-danger').text("Confirm Password and Password doesn't match !");
            $('.password-msg').addClass('text-danger').text("Password and Confirm Password doesn't match !");
            $("#seller_form_password").addClass('is-invalid').removeClass('valid-input');
            $("#seller_form_confirmPassword").addClass('is-invalid').removeClass('valid-input');
        }

        if(isValidate && isComapnyName && isEmail && isPhone && isAddress && isCity && isState && isZip && isPassword && isConfirmPassword) {
            return true;
        } else {
            return false;
        }
    });

    // For Product Sales Price Calculation
    salesPriceCalculation(e);
    
    $('#form_discount').on('input', function (e) {
        var keyCode = e.which ? e.which : e.keyCode;
        if ((keyCode < 48 || keyCode > 57) && keyCode != 46) {
            e.preventDefault();
            return false;
        }
        salesPriceCalculation(e);
    });

    $('#form_discount_type').on('change', function (e) {
        salesPriceCalculation(e);
    });

    $('#form_sales_price').on('keypress blur', function (e) {
        var keyCode = e.which ? e.which : e.keyCode;
        if ((keyCode < 48 || keyCode > 57) && keyCode != 46) {
            e.preventDefault();
            return false;
        }
        discountCalculation(e);
    });

    $("#wix-seller-login-form").on("submit",function(e)
    {
        var response = grecaptcha.getResponse();
        if(response.length == 0) { 
            //reCaptcha not verified
            $("#wix_seller_login_btn").prop('disabled', true);
            $(".wk-overlay").hide();
            e.preventDefault();
            return false;
        }
        
        //secret key validation
        var targetUrl = document.getElementById("g-recaptcha").getAttribute('data-href');

        // var formData = new FormData();
        // formData.append('captchaResponse', response);

        if (!localStorage.getItem('isGoogleCaptchaCredVerified')) {

            e.preventDefault();
            
            $.ajax({
                url: targetUrl,
                data: {
                    'captchaResponse': response
                },
                method: 'POST',
                beforeSend: function () {
                    $(".wk-overlay").show();
                },
                success: function (data) {
                    if (data.status) {

                        let errorEle = `<div class="alert alert-dismissible alert-danger" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                        <span id = "wix-login-captcha-msg-span"></span>
                        </div>`;

                        document.getElementById("wix-login-captcha-msg").innerHTML = errorEle;

                        document.getElementById("wix-login-captcha-msg").style.display = "block";
                        document.getElementById("wix-login-captcha-msg-span").innerHTML = data.msg;
                        document.getElementById("wix_seller_login_btn").setAttribute("disabled", true); 
        
                    } else {
                        localStorage.setItem('isGoogleCaptchaCredVerified', true);
                        document.getElementById("wix-login-captcha-msg").style.display = "none";
                        document.getElementById("wix_seller_login_btn").removeAttribute("disabled");
                    }
                },
                complete: function () {
                    $(".wk-overlay").hide();
                }
            });
        }
    });

    $("#wix-seller-reg-form").on("submit",function(e)
    {
        var response = grecaptcha.getResponse();
        if(response.length == 0) { 
            //reCaptcha not verified
            $("#wix_seller_register_btn").prop('disabled', true);
            $(".wk-overlay").hide();
            e.preventDefault();
            return false;
        }
    });
});

function salesPriceCalculation(e)
{
    $("#wix_product_btn").prop('disabled', false);
    $("#form_sales_price").removeClass('is-invalid');
    $("#form_discount").removeClass('is-invalid');
    var discountType = $("#form_discount_type").val();
    var discountValue = $("#form_discount").val();
    var productPrice = $("#form_price").val();

    //var keyCode = e.which ? e.which : e.keyCode;
    // if (keyCode < 48 || keyCode > 57) {
    //     e.preventDefault();
    //     return false;
    // }
    
    switch(discountType) { 
        
        case 'AMOUNT':
            var salesPrice = productPrice - discountValue;
            $("#form_sales_price").val(salesPrice.toFixed(2));
            if (salesPrice < 0) {
                $("#form_sales_price").addClass('is-invalid');
                $("#form_sales_price").focus();
                $("#wix_product_btn").prop('disabled', true);
            }

            if (discountValue < 0) {
                $("#form_discount").addClass('is-invalid');
                $("#form_discount").focus();
                $("#wix_product_btn").prop('disabled', true);
            }
        break;
            
        case 'PERCENT':
            var percentage = (productPrice * discountValue) / 100;
            var salesPrice = productPrice - percentage;
            $("#form_sales_price").val(salesPrice.toFixed(2));

            if (salesPrice < 0) {
                $("#form_sales_price").val(productPrice);
                $("#form_discount").val(0);
                //$("#form_sales_price").addClass('is-invalid');
                //$("#form_sales_price").focus();
                //$("#wix_product_btn").prop('disabled', true);
            }

            if (discountValue < 0) {
                $("#form_discount").addClass('is-invalid');
                $("#form_discount").focus();
                $("#wix_product_btn").prop('disabled', true);
            }
        break;
   }
   
}

function discountCalculation(e)
{
    $("#wix_product_btn").prop('disabled', false);
    $("#form_sales_price").removeClass('is-invalid');
    $("#form_discount").removeClass('is-invalid');
    var discountValue = $("#form_discount").val();
    var productPrice = $("#form_price").val();
    var salesPrice = $("#form_sales_price").val();

    // var keyCode = e.which ? e.which : e.keyCode;
    // if (keyCode < 48 || keyCode > 57) {
    //     e.preventDefault();
    //     return false;
    // }

    $('#form_discount_type option[value="AMOUNT"]').prop("selected", true);

    var discount = productPrice - salesPrice;

    $("#form_discount").val(discount.toFixed(2));

    if (discount < 0) {
        $("#form_discount").addClass('is-invalid');
        $("#form_discount").focus();
        $("#wix_product_btn").prop('disabled', true);
    }

    if (salesPrice < 0) {
        $("#form_sales_price").addClass('is-invalid');
        $("#form_sales_price").focus();
        $("#wix_product_btn").prop('disabled', true);
    }
}

function containsWhitespace(str) {
    return /\s/.test(str);
}

$(document).ready(function() {
    $(".option_data").hide();
    $('#custom_field_form_type').on('change', function() {
        var dataValue = $(this).val();
        if(dataValue == "option" || dataValue == "checkbox" || dataValue == "radio") {
            $(".option_data").show();
            $(".is_requied_field").hide();
            $("#custom_field_form_isRequired").val(0);
        } else if (dataValue == "textarea") {
            $(".option_data").hide();
            $(".is_requied_field").hide();
            $("#custom_field_form_isRequired").val(0);
        } else {
            $(".option_data").hide();
            $(".is_requied_field").show();
        }
    });

    var optionValue = $("#custom_field_form_type").val();
    if(optionValue == "option" || optionValue == "checkbox" || optionValue == "radio") {
        $(".option_data").show();
        $(".is_requied_field").hide();
    } else if(optionValue == "textarea") {
        $(".option_data").hide();
        $(".is_requied_field").hide();
    }

    $(".add-options").on('click', function(e) {
        e.preventDefault();
        
        var count = $('.new-added-field').length;
        if(count <= 4) {
            var html = '<div class="form-group new-added-field"><div class="row"><div class="col-md-6"><input type="text" class="form-control addOptionField" name="options[]" maxlength="30"></div><div class="remove-added-field"><span class="fa fa-2x fa-minus-circle" id="" style="color: #4B71FC;;"></span></div></div></div>';

            $('.options').append(html);
        }
    });

    $("div").on('click', '.remove-added-field', function(e) {
        e.preventDefault();
        $(this).closest('.new-added-field').remove();
    });

    $('.addOptionField').keypress(function (e) {
        if (e.which == 13 || e.which == 44) {
            return false;
        }
    });

    $(".customFieldSave").on('click', function(e) {
        var isLabel = true;
        var isOption = true;
        var isValid = true;

        var label = $('.fieldNameInput').val();
        if(label.length == 0) {
            isValid = false;
            isLabel = false;
            $('.fieldNameInput').addClass('is-invalid').removeClass('valid-input');
        }

        var dataValue = $('#custom_field_form_type').val();
        if(dataValue == "option" || dataValue == "checkbox" || dataValue == "radio") {
            var optionField = $('.option_data').find('.addOptionField');
            if(optionField.length != 0) {
                optionField.each(function() {
                    var inputValue = $(this).val();
                    
                    if(inputValue.length == 0 || inputValue.trim() == "" || inputValue.indexOf(',') > -1) {
                        isValid = false;
                        isOption = false;
                        $(this).addClass('is-invalid').removeClass('valid-input');
                    }
                });
            } else {
                isValid = false;
                isOption = false;
                $('.add-options').css('border-color', '#dc3545');
            }
        }

        if(isOption && isValid && isLabel) {
            return true;
        } else {
            return false;
        }
    });

    // Hide category commission rate type div by defualt on seller plan update page.
    categoryCommissionType.init();

    $("#seller_plan_condition_commission_type").on("change", function(e){
        categoryCommissionType.onChangeCommissionType();
    });

    $('#form_commission').on('keypress blur', function (e) {
        categoryCommissionType.onKeyPress(e);
    });
});

var categoryCommissionType = {

    init : function() {
        
        $("#seller_plan_category_comission_rate_type_div").hide();
        let commissionType = $("#seller_plan_condition_commission_type").val();
        
        if (commissionType == "commission_per_category") {

            $("#seller_plan_category_comission_rate_type_div").show();
            $("#wk-wix-plan-commission").hide();

        } else if(commissionType == "commission_per_product") {

            $("#seller_plan_category_comission_rate_type_div").hide();
            $("#wk-wix-plan-commission").hide();
        }
    },

    onChangeCommissionType() {
        
        let commissionType = $("#seller_plan_condition_commission_type").val();
        
        if (commissionType == "commission_per_category") {
            
            $("#seller_plan_category_comission_rate_type_div").show();
            $("#wk-wix-plan-commission").hide();

        } else if (commissionType == "commission_per_product") {

            $("#wk-wix-plan-commission").hide();
            $("#seller_plan_category_comission_rate_type_div").hide();

        } else {
            $("#seller_plan_category_comission_rate_type_div").hide();
            $("#wk-wix-plan-commission").show();
        }
    },

    onKeyPress : function (e) {
        var keyCode = e.which ? e.which : e.keyCode;
        if ((keyCode < 48 || keyCode > 57) && keyCode != 46) {
            e.preventDefault();
            return false;
        }
    }
};

$(document).ready(function() {
    $("#options").on("change", function() {
        var selectedValue = $(this).val();
        if (selectedValue === "percentage") {
            $("#percentageBox").show();
            $("#valueBox").hide();
        } else if (selectedValue === "value") {
            $("#percentageBox").hide();
            $("#valueBox").show();
        }
    });
});

$(document).ready(function() {
  
    $(document).on('input', '.commission-input-field', function() {
    
        let currentValue = $(this).val();

     
        let sanitizedValue = currentValue.replace(/[^\d.]/g, '');

    
        let dotCount = sanitizedValue.split('.').length - 1;
        if (dotCount > 1) {
            sanitizedValue = sanitizedValue.slice(0, sanitizedValue.lastIndexOf('.'));
        }

        let twoDigitValue = sanitizedValue.slice(0, 2);

     
        $(this).val(twoDigitValue);
    });
});
$(document).ready(function() {
    $('#options').val("percentage");
    $(".percentage").show();
    $('.flat').hide();

    $("#options").on("change", function() {
        var selectedValue = $(this).val();
        if (selectedValue === "percentage") {
            $(".percentage").show();
            $(".flat").hide();
        } else if (selectedValue === "fixed") {
            $(".percentage").hide();
            $(".flat").show();
        }
    });

});

$(document).ready(function() {
    const countrySelect = $('#form_country');
    const regionSelect = $('#form_region');
    const classificationSelect = $('#form_classification');
    const regionSelectWrapper = $('.region');
    const classificationSelectWrapper = $('.classification');
    
    const regionsByCountry = {
        'New Zealand': ['Auckland', 'Canterbury / Waipara Valley', 'Cental Otago', 
        'Gisborne', 'Hawke´s Bay', 'Marlborogh', 'Martinborough', 'Nelson', 'Northland', 'Waikato', 'Wairarapa', 'Waitaki Valley'],
        'Austria': ['Bergland', 'Bodensee-Vorarlberg', 'Burgenland', 'Burgenland', 'Niederösterreich (Lower Austria)','Steiermark (Styria)','Wien (Vienna)'],
        'Germany' : ['Ahr', 'Baden', 'Franken', 'Hessische Bergstrasse', 'Mittelrhein', 'Mosel', 'Nahe', 'Pfalz', 'Rheingau', 'Rheinhessen', 'Saale-Urstut', 'Sachsen', 'Schleswig Holstein','Württemberg'],
        'Switzerland' : ['Appenzell','Argovie','Basel','Berne','Fribourg','Geneve','Glaris','Graubünden','Jura','Luzern','Neuchatel','Nidwald','Obwald','Romandie','Schaffhouse','Schwyz','St. Gallen','Thurgau','Ticino','Uri','Valais','Vaud','Zug','Zürich'],
        'South Africa' : ['Eastern Cape', 'Kwazulu-Natal', 'Limpopo', 'Nothern Cape', 'Western Cape'],
        'China' : ['Gansu', 'Hebei', 'Heilongjiang', 'Henan', 'Jilin', 'Liaoning', 'Ningxia', 'Shandong', 'Shanxi', 'Tianjin', 'Xinjiang', 'Yunnan'],
        'Italy' : ['Abruzzo', 'Basilicata', 'Calabria', 'Campania', 'Emilia Romagna', 'Friaul-Venezia Giulia', 'Lazio', 'Liguria', 'Lombardia', 'Marche', 'Molise', 'Piemonte', 'Puglia', 'Sardegna', 'Sicilia', 'Toscana' ,'Trentino Alto-Adige', 'Umbria', 'Valle d´Aosta', 'Veneto'],
        'France' : ['Alsace', 'Auvergne', 'Beaujolais', 'Bordeaux', 'Burgund', 'Bretagne', 'Champagne', 'Corse', 'Ile de France', 'Jura', 'Languedoc-Roussillion', 'Lorraine', 'Nord', 'Normandie', 'Outre-Mer', 'Cognac', 'Provence', 'Savoie', 'Sud-Oest', 'Vallee de la Loire', 'Vallee du Rhone', 'Vosges'],
        'Australia' : ['New South Wales', 'Queensland', 'South Australia', 'South Eastern Australia', 'Tasmania', 'Victoria', 'Western Australia'],
        'Argentina' : ['Buenos Aires', 'Catamarca', 'Chubut', 'Cordoba', 'Jujuy', 'La Pampa', 'La Rioja', 'Mendoza', 'Neuquen', 'Patagonia', 'Rio Negro', 'Salta', 'San Juan', 'Tucuman'],
        'Luxembourg' : ['Moselle Luxembourgeoise'],
        'Bulgaria' : ['Black Sea', 'Danubian Plain', 'Rose Valley', 'Struma Valley', 'Thracian Valley'],
        'Greece' : ['Agean Islands', 'Epirus', 'Ionian Islands', 'Kreta', 'Makedonia', 'Peloponnes', 'Sterea Ellada / Central Greece', 'Thessalia', 'Thraki'],
        'Romania' : ['Banat', 'Colinele Dobrogei', 'Crisana', 'Danube Terraces', 'Donbrudja', 'Moldavia', 'Muntenia', 'Oltenia', 'Transylvania'],
        'USA' : ['Alabama', 'Alaska', 'Arizona', 'Arkansas', 'California', 'Colorado', 'Connecticut', 'Delaware', 'Florida', 'Georgia', 'Hawaii', 'Idaho', 'Illinois','Indiana','Iowa','Kansas','Kentucky','Louisiana','Maine','Maryland','Massachusetts','Michigan','Minnesota','Mississippi','Missouri','Montana', 'Nebraska','Nevada','New Hampshire','New Jersey','New Mexico','New York','North Carolina','North Dakota','Ohio','Oklahoma','Oregon','Pennsylvania','Rhode Island','South Carolina','South Dakota','Tennessee', 'Texas','Utah','Vermont','Virginia','Washington','West Virginia','Wisconsin','Wyoming'],
        'Spain' : ['Andalucia', 'Aragon', 'Asturias', 'Baleares', 'Canarias', 'Cantabria', 'Castilla La Mancha', 'Castilla y Leon', 'Cataluna', 'Extremadura', 'Galicia', 'Madrid','La Rioja','Madrid','Murcia','Navarra','Pais Vasco','Valencia'],
        'Portugal' : ['Alentejo','Algarve', 'Acores', 'Beira Atalntico','Beira Interior', 'Douro','Dao', 'Lisboa', 'Madeira','Minho','Setubal','Tavora e Varosa','Tejo','Tras-os-Montes'],
        'Chile' : ['Aconcagua','Atacama','Austral','Central Valley','Coquimbo','Southern Chile'],
        'Slovenia' : ['Podravje (Sava Valley)','Podravje (Lower Sava Valley)','Primorska (Littoral)'],
        'Lebanon' : ['Bekaa Valley'],
        'Croatia' : ['Coastal Croatia','Continental Croatia','Istria'],
        'Hungary' : ['Balaton','Duna- The great Hungarian plain (Alföld)','Del-Pannonia (South Pannonia)','Felso-Magyarorszag (Hegyvidek)','Tokaj','Eszak-Dunantul (North-Transdanubia)'],
        'England' : ['Campbeltown (Scotland)','Cornwall (England)','Devon (England)','Dorset (England','EastAnglia €','Gloucestershire (Eng.)','Hampshire (Eng.)','Herefordshire (Eng.)','Highland ( Scot.)','Island (Scot.)', 'Islay (Scot.)','Isle of Arran (Eng.)','Isle of Wight (Eng.)','Isle of Scilly (Eng.)','Jersey (Eng.)','Kent (Eng.)','Lincolnshire (Eng.)', 'London ', 'Lowland (Scot.)','Northhamptonshire (Eng.)','Oxfordshire (Eng.)','Shropshire (Eng.)','Somerset (Eng.)','Speyside ( Scot.)','Surrey (Eng.)','Sussex (Eng.)','Wales (Wales)', 'Worcestershire (Eng.)','Yorkshire (Eng.)'],
        'Georgia' : ['Black Sea Coastal Zone', 'Imereti', 'Kakheti', 'Kartli', 'Meskheti','Racha-Lechkhumi / Kvemo Svaneti'],
        'Canada' : ['British Columbia','Newfoundland','Nova Scotia','Ontario','Quebec']
        // Add more countries and their respective regions here
    };

    for (const country in regionsByCountry) {
        if (regionsByCountry.hasOwnProperty(country)) {
            regionsByCountry[country] = sortAlphabetically(regionsByCountry[country]);
        }
    }

    const classificationByCountry = {
        '' : [],
        'New Zealand' : ['CO- Certified Origin'],
        'Georgia' : [],
        'United Kingdom' : ["English Quality Sparkling Wine PDO","English Regional Wine PGI","English Wine PDO","Welsh Wine PDO"],
        'Canada' : ["VQA - Vintners Quality Alliance"],
        'Croatia' : [],
        'Hungary': ["DHC - PDO / OEM","FN","OFJ / PGI"],
        "Romania" : ["DOC - CIB","DOC - CMD","DOC - CT","IGT - Indicatie Geografica Tipica"],
        'Luxembourg' : ["AOC / AOP","Grand Premier Cru","Premier Cru","Vendanges Tardives","Vin classe","Vin de Glace","Vin de Paille"],
        'Bulgaria' : ['PDO'],
        'Greece' : ["Epitrapezios Inos","OPAP","OPE","PDO","PGI","TSG","Topikos Inos","Varietal Wine"],
        'Lebanon' : [],
        'Slovenia' : [],
        'Chile' : ["Andes","Costa","DO","Entre Cordilleras","Gran Reserva","Reserva","Reserva Especial","Reserva Privada","Secano Interior"],
        'China' : [],
        'Argentina' : ["DOC","Gran Reserva","IG","Reserva","Reserva Especial"],
        'South Africa' : ['WO - Wine of Origin'],
        'Portugal' : ["DOC / DOP","IGP / Vinho Regional","IPR","Vinho de Mesa"],
        'France' : ["1er Cru","1er Cru Superieur","1er Grand Cru Classe","1er Grand Cru Classe A","2eme Grand Cru Classe","3eme Grand Cru Classe","4eme Grand Cru Classe","5eme Grand Cru Classe","AOP / AOC","Cru Artisan","Cru Bourgeois","Cru Bourgeois Exceptionnel","Cru Bourgeois Superieur","Cru Classe","Grand Cru","Grand Cru Classe","IGP / Vin de Pays","Vin de France / Vin de Table"],
        'Spain' : ["DO Crianza","DO Gran Reserva", "DO Joven","DO Reserva","DOCa","DOCa Crianza","DOCa Gran Reserva","DOCa Joven","DOCa Reserva","DOP","DOP / DO de Pago","DOP / DO de Pago Calificado","DOQ",
        "VCIG - Vino de Calidad con Indicación Geográfica","VORS","VOS","Vino de Mesa","Vino de Pago","Vino de Tierra"],
        'Lebanon' : [],
        'Slovenia' : [],
        'Chile' : ["Andes","Costa","DO","Entre Cordilleras","Gran Reserva","Reserva","Reserva Especial","Reserva Privada","Secano Interior"],
        'China' : [],
        'Argentina' : ["DOC","Gran Reserva","IG","Reserva", "Reserva Especial"],
        'South Africa' : ['WO - Wine of Origin'],
        'Portugal' : ["DOC / DOP","IGP / Vinho Regional","IPR","Vinho de Mesa"],
        'France' : ["1er Cru","1er Cru Superieur","1er Grand Cru Classe","1er Grand Cru Classe A","2eme Grand Cru Classe","3eme Grand Cru Classe","4eme Grand Cru Classe","5eme Grand Cru Classe",
        "AOP / AOC","Cru Artisan","Cru Bourgeois","Cru Bourgeois Exceptionnel","Cru Bourgeois Superieur","Cru Classe","Grand Cru","Grand Cru Classe","IGP / Vin de Pays","Vin de France / Vin de Table"],
        'Spain' : ["DO Crianza","DO Gran Reserva","DO Joven","DO Reserva","DOCa","DOCa Crianza","DOCa Gran Reserva","DOCa Joven","DOCa Reserva","DOP","DOP / DO de Pago",
        "DOP / DO de Pago Calificado","DOQ","VCIG - Vino de Calidad cin Indicación Geográfica","VORS","VOS","Vino de Mesa","Vino de Pago","Vino de Tierra"],
        'Italy' : ["VSQ - Vino Spumante de Qualita", "DOCG", "DOCG Dolce Naturale", "DOCG Riserva","DOCG Superiore","DOCG Superiore Riserva","DOP / DOC", "DOP / DOC Riserva","DOP / DOC Superiore","DOP / DOC Superiore Riserva","Gran Selezione","IGT / IGP","VS - Vino Spumante","Vino Comune","Vino Territoriale", "Vino Varietale","Vino di Tavola"],
        'Austria' :["Ausbruch","Auslese","Beerenauslese","DAC - Districtus Austriae Controllatus","DAC - Reserve","Eiswein", "Erste STK Lage","Federspiel","Gebietswein",
        "Große STK Lage","Kabinett","Landwein","Ortswein","Qualitätswein","Riedenwein","Sekt - Große Reserve","Sekt - Klassik","Sekt - Reserve","Smaragd","Spätlese","Steinfeder","Strohwein / Schilfwein","Trockenbeerenauslese","ÖTW Erste Lage"],
        'Germany' :["Auslese","Beerenauslese","Deutscher Wein / Tafelwein","Eiswein","Erstes Gewächs","Kabinett","Landwein","QbA - Qualitätswein","QmP- Prädikatswein","Sekt","Spätlese","Trockenbeerenauslese","VDP. Aus Ersten Lagen","VDP. Erste Lage","VDP. Große Lage","VDP. Großes Gewächs","VDP. Gutswein","VDP. Ortswein"],
        'USA' : ["AVA (American Viticultural Area)","County Appellation","State Appellation"],
        'Switzerland' : ["AOC / DOC (Appellation d'Origine Contrôlée / Denominazione di Origine Controllata)",
        "Grand Cru","Premier Cru","Premier Grand Cru",,"Vin de Pays"],
        'Australia' : ['GI - Geographical Indication'],
        'New Zealand' : ['CO- Certified Origin'],
    };

    for (const country in classificationByCountry) {
        if (classificationByCountry.hasOwnProperty(country)) {
            classificationByCountry[country] = sortAlphabetically(classificationByCountry[country]);
        }
    }
    // Event listener to update the "region" field when the "country" field changes
    countrySelect.on('change',function() {
        const selectedCountry = countrySelect.val();
        const regions = regionsByCountry[selectedCountry] || [];
        const classifications = classificationByCountry[selectedCountry] || [];
        
        // Clear the current options
        regionSelect.empty();
        classificationSelect.empty();
        const defaultOption = $('<option>', { value: '', text: wkMpTrans.select_a_region });
        regionSelect.append(defaultOption);
        // Create and append new options for regions
        for (const region of regions) {
            const option = $('<option>', { value: region, text: region });
            regionSelect.append(option);
        }

        const defaultClassification = $('<option>', { value: '', text: wkMpTrans.select_a_classification });
        classificationSelect.append(defaultClassification);
        for(const classification of classifications) {
            const option = $('<option>', { value: classification, text: classification})
            classificationSelect.append(option);
        }

        if (regions.length === 0) {
            regionSelectWrapper.hide();
            $('label[for="form_region"]').hide();
        } else {
            regionSelectWrapper.show();
            $('label[for="form_region"]').show();
        }

        if(classifications.length === 0) {
            classificationSelectWrapper.hide();
            $('label[for="form_classification"]').hide();
        } else {
            classificationSelectWrapper.show();
            $('label[for="form_classification"]').show();
        }
    });
    
});

$(document).ready(function(){
    const regionselect = $('#form_region');
    const appellationselect = $('#form_appellation');
    const appellationselectwrapper = $('.appellation');

    const appellationbyregion = {
        'New South Wales' : ["Big Rivers","Broke Fordwich","Canberra","Central Ranges","Cowra","Gundagai","Hastings River","Hilltops","Hunter Valley","Lower Hunter Valley","Mudgee",
            "Murray Darling","New England","Northern Rivers","Northern Slopes","Orange","Perricoota","Pokolbin","Riverina","Shoalhaven Coast","Southern Highlands","Southern New South Wales","Swan Hill","Tumbarumba","Upper Hunter Valley","Western Plains"],
        'Auckland' : ["Auckland","Aukland","Henderson","Kumeu","Matakana","Waiheke Island","West Auckland"],
        'Canterbury / Waipara Valley' :  ["Canterbury / Waipara Valley","CaterburCanterbury","North Canterbury","Waipara"],
        'Cental' : ["Alexandra","Bannockburn","Bendigo","Central Otago","Cromwell","Gibbston","Lowburn / Pisa","Wanaka"],
        'Gisborne' : ["Gisborne","Manutuke","Ormond","Patutahi"],
        "Hawke´s Bay" :[ 'Hawke´s Bay','Bridge Pa','Central Hawke´s Bay','Eskdale','Gimblett Gravels','Havelock North','Hawke´s Bay','Korokipo','Maraekakaho','Meanee','Ohiti','Taradale','Te Awanga',],
        "Marlborogh": [ 'Marlborough','Awatere Valley','Blenheim','Cloudy Bay','Marlborough','Renwick','Seddon','Spring Creek','Waihopai','Wairau Valley'],
        "Martinborough"  : [],
        "Nelson" : [ 'Nelson','Moutere Hills','Nelson','Waimere Plains'],
        "Northland" : ["Northland"],
        "Waikato" : [''],
        "Wairarapa" : [  'Gladstone','Martinborough','Masterton','Wairarapa',],
        "Waitaki Valley" : ['Waitaki Valley'],
        'Queensland' : ['Granite Belt', 'South Burnett'],
        'South Australia' : [ 'Adelaide Hills','Adelaide Plains','Barossa Valley','Clare Valley','Coonawarra','Currency Creek','Eden Valley','Far North','Fleurieu','High Eden',
        'Kangaroo Island','Langhorne Creek','Lenswood','Limestone Coast','McLaren Vale','Mount Benson','Mount Lofty Ranges','Padthaway','Peninsulas','Piccadilly Valley','Ranges','Riverland','Robe','Southern Fleurieu','Southern Flinders Range','Wrattonbully',],
        'South Eastern Australia' : [],
        'Tasmania' : [ 'Coal River','Derwent Valley','East Coast','North West','Pipers River','Southern','Tamar Valley','Tasmania',],
        'Victoria' : [ 'Victoria','Alpine Valleys','Beechworth','Bendigo','Geelong','Gippsland','Glenrowan','Goulburn Valley','Grampians','Heathcote','Henty','King Valley','Macedon Ranges','Mornington Peninsula','Murray Darling','Nagambie','Port Phillip','Pyrenees','Rutherglen','Strathbogie Ranges','Sunbury','Swan Hill','Upper Goulburn',],
        'Western Australia' : ['Western Australia','Blackwood Valley','Geographe','Great Southern','Greater Perth', 'Manjimup', 'Margaret River','Peel','Pemberton','Perth Hills','South Western Australia','Swan Valley','Wilyabrup',],
        'Appenzell' : ['Appenzell','Oberegg',' Wienacht-Tobel'],
        'Argovie' : [  'Arau','Aargau','Baden','Birmenstorf','Bremgarten','Bözberg','Fricktal','Hallwilersee','Klingnau','Obersiggenthal','Remingen','Schniznach','Tegerfelden','Untersiggenthal','Villigen',],
        'Basel' : [    'Basel','Basel-Landschaft','Basel-Stadt','Nordwestschweiz',],
        'Berne' : [  'Berne','Bielersee / Lac de Bienne', 'Erlach','La Neuveville', 'Ligerz','Schafiser / Schafis','Thunersee','Schugg','Twann','Tüscherz','Vigneules',],
        'Fribourg' : ['Broye','Cheyres','Vully'],
        'Geneve' : ['Bardonnex','Chateau de Choully','Chateau du Crest','Cologny','Confignon','Coteau de Bossy','Coteau de Bourdigny','Coteau de Chevrens','Coteau de Choulex','Coteau de Choully','Coteau de Genthod','Coteau de Lully','Coteau de Peissy','Coteau de la vigne blanche','Coteau de Baillets','Coteaux de Dardagny','Coteaux de Meinier','Coteaux de Peney', 'Celigny','Cotes de Landecy','Cotes de Russin','Domaine de l´Abbaye','Geneve','Grand Carraz','Hermance','La Feuillee','Laconnex','Mandement de Jussy','Rougemont','Satigny',],
        'Glaris' : ['Glaris','Glarus','Mollis','Niederurnen'],
        'Graubünden' : [ 'Graubünden', 'Fläsch','Jenis','Maienfeld','Malans'],
        'Jura' : ['Buix'],
        'Luzern' : ['Baldeggersee','Luzern','Sempachersee',],
        'Neuchatel' : [ 'Neuchatel','Auvernier','Bevaix','Boudry','Bole','Chmapreveyres','Chez le Bart','Colombier','Corcelles-Cormondreche','Cornaux','Cortaillod','Cressier','Fresens','Gorgier','Hauterive','La Coudre','Le Landeron','Peseux','Saint-Aubin-Sauges','Saint-Blaise','Vaumarcus','Vin de Pays Romand',],
        'Nidwald' : [],
        'Obwald' : ['Obwalden'],
        'Romandie' : [],
        'Schaffhouse' : ['Schaffhouse','Aarau','Baden','Brugg','Dietikon','Einsiedeln','Freienbach','Horgen','Knonau','Lenzburg','Liestal','Menziken','Muri','Rapperswil-Jona','Schinznach','Steinhausen','Thalwil','Uster','Wädenswil','Wil','Winterthur','Zug'],
        'Schwyz' : ['Schwyz','Buchen','Eschenbach','Freienbach','Gersau','Höfe','Küssnacht','Lachen','March','Münchwilen','Schwyz','Siglistorf','Steinhausen','Thalwil','Uster','Wädenswil','Wil','Winterthur','Zug'],
        'Solothurn' : ['Dornach','Flüh','Solothurn'],
        'St. Gallen' : ['Berneck','Heerbrugg','Rapperwil','Rheintal','St. Gallen'],
        'Thurgau' : ['Dettinghof','Diessenhofen','Ermatingen','Frauenfeld','Herden','Hüttwilen','Nussbaum','Salenstein','Schlattingen'],
        'Ticino' : ['Bianco del Ticino','Biasca','Castel San Pietro','Chiasso','Giornico','Giubiasco','Gordola','Gudo','Malvaglia','Morbio','Morcote','Pedrinate','Rivera','Rovio','Stabio','Tenero','Ticino','Verscio'],
        'Uri' : ['Bürglen','Uri'],
        'Valais' : [  'Ardon','Ayent', 'Chamoson','Conthey','Coteaux de Sierre','Fully','Grimisuat','Lens','Leytron','Martigny','Miège','Saillon','Salquenen','Savièse','Saxon','Sion','Valais','Varen','Venthône','Vétroz',],
        'Vaud' : [ 'Vaud','Aigle','Allaman','Arnex sur Orbe','Aubonne','Begnins','Bex','Bonvillars','Bursinel','Calamin','Chablais','Chardonne','Coteau de Vincy','Cotes de lOrbe','Dezaley','Dezaley-Marsens', 'Epesses','Fechy','La Côte','Lavaux','Lonay','Luins','Lutry','Mont-sur-Rolle','Morges','Nyon','Ollon','Perroy','Saphorin','Signy-Avenex','Tartegnin','Vaud','Vevey-Montreux','Villeneuve','Villette','Vinzel','Vully','Yvorne'],
        'Zug' : ['Zug','Hünenberg','Walchwil','Zug'],
        'Zürich' : [ 'Zürich','Andelfingen','Au','Dachsen','Eglisau','Flaach','Meilen','Neftenbach','Oberstammheim','Seuzach','Stäfa','Unterstammheim','Weiningen','Wädenswil','Zürichsee'],
        'Alabama' : ['Alabama'],
        'Alaska' : [],
        'Arizona' : [ 'Arizona','Sonoita','Verde Valley','Willcox'],
        'Arkansas' : ['Altus','Arkansas','Arkansas Mountain','Ozark Mountain'],
        'California' : [  'California','Adelaida District','Chalone','Alexander Valley','Alta Mesa','Amador County','Anderson Valley','Antelope Valley','Arroyo Grande Valley','Arroyo Seco','Atlas Peak','Ballard Canyon','Ben Lomond Mountain','Benmore Valley','Bennett Valley','Big Valley District','Borden Ranch','Calaveras County','California Shenandoah Valley','Calistoga','Capay Valley','Camel Valley','Carneros','Central Coast','Chalk Hill','Chiles Valley','Cienega Valley','Clarksburg','Clear Lake','Clements Hills', 'Cole Ranch','Contra Costa County','Coombsville','Cosumnes River','Covelo','Creston District','Cucamonga Valley','Diablo Grande','Diamond Mountain District','Dos Rios','Dry Creek Valley','Dunnigan Hills',
        'Eagle Peak Mendocino County','Edna Valley','El Dorado','El Dorado County','El Pomar District','Fair Play','Fiddletown','Fort Ross-Seaview','Fountaingrove District','Fresno County','Green Valley of Russian River Valley','Guenoc Valley',
        'Hames Valley','Happy Canyon of Santa Barbara','High Valley','Howell Mountain','Humboldt County','Jahant','Kelsey Bench-Lake County','Knights Valley','Lake County','Leona Valley','Lime Kiln Valley','Livermore Valley','Lodi','Los Angeles County',
        'Los Olivos District','Madera','Malibu Coast','Malibu-Newton Canyon','Manton Valley','Marin County','Mariposa County','McDowell Valley','Mendocino','Mendocino County','Mendocino Ridge','Merritt Island','Mokelumne River','Monterey','Monterey County','Moon Mountain District','Mt. Harlan','Mt. Veeder','Napa County','Napa Valley','Nevada County','North Coast','North Yuba', 'Northern Sonoma','Oak Knoll District','Oakville','Pacheco Pass',
        'Paicines','Paso Robles','Paso Robles Estrella District','Paso Robles Geneseo District','Paso Robles Highland District','Paso Robles Willow Creek District','Petaluma Gap','Pine Mountain-Cloverdale','Placer County','Potter Valley','Ramona Valley','Red Hills Lake County','Redwood Valley','River Junction','Rockpile','Russian River Valley','Rutherford','Saddle Rock-Malibu','Saint Helena','Salado Creek','San Antonio Valley','San Benito','San Benito County','San Bernabe','San Diego County','San Francisco Bay','San Joaquin County','San Juan Creek','San Lucas','San Luis Obispo County','San Miguel District','San Pasqual Valley','San Ysidro District','Santa Barbara County','Santa Clara Valley','Santa Clara County','Santa Cruz Mountains','Santa Lucia Highlands','Santa Margarita Ranch','Santa Maria Valley','Santa Ynez Valley','Seiad Valley','Sierra Foothills','Sierra Pelona Valley','Sloughhouse','Solano County','Solano County Green Valley','Sonoma Coast','Sonoma County','Sonoma Mountain','Sonoma Valley','South Coast','Spring Mountain District','Squaw Valley-Miramonte','Sta. Rita Hills','Stags Leap District','Suisun Valley','Tehachapi-Cummings Valley','Temecula Valley','Templeton Gap District','Tracy Hills','Trinity Lakes','Trinity County','Tuolumne County','Ventura County','Wild Horse Valley','Willow Creek','Yolo County','York Mountain','Yorkville Highlands','Yountville',],
        'Colorado' : ['Grand Valley','West Elks'],
        'Connecticut' : ['Connecticut','Southeastern New England','Western Connecticut Highland'],
        'Delaware' : ['Delaware'],
        'Florida' : ['Florida'],
        'Georgia' : ['Georgia','Lumpkin County'],
        'Hawaii' : ['Hawaii'],
        'Idaho' : ['Idaho', 'Lewis-Clark Valley', 'Snake River Valley'],
        'Illinois' : ['Shawnee Hills', 'Upper Mississippi River Valley'],
        'Indiana' : ['Ohio River Valley'],
        'Iowa' : ['Iowa', 'Upper Mississippi River Valley'],
        'Kansas' : ['Kansas'],
        'Kentucky' : ['Ohio River Valley'],
        'Louisiana' : ['Louisiana', 'Mississippi Delta'],
        'Maine' : ['Maine'],
        'Maryland' : ['Catoctin','Cumberland Valley', 'Linganore', 'Maryland'],
        'Massachusetts' : ['Martha\'s Vineyard', 'Massachusetts', 'Southeastern New England'],
        'Michigan' : ['Fennville', 'Lake Michigan Shore', 'Leelanau Peninsula', 'Michigan', 'Old Mission Peninsula'],
        'Minnesota' : ['Alexandria Lakes', 'Minnesota', 'Upper Mississippi River Valley'],
        'Mississippi' : ['Mississippi', 'Mississippi', 'Mississippi Delta'],
        'Missouri' : ['Augusta', 'Hermann', 'Missouri', 'Ozark Highlands', 'Ozark Mountain'],
        'Montana' : ['Montana'],
        'Nebraska' : ['Nebraska'],
        'Nevada' : ['Nevada'],
        'New Hampshire' : ['New Hampshire'],
        'New Jersey' : [ 'Central Delaware Valley', 'New Jersey', 'Outer Coastal Plain', 'Warren Hills'],
        'New Mexico' : ['Mesilla Valley', 'Middle Rio Grande Valley', 'Mimbres Valley', 'New Mexico'],
        'New York' : ['Cayuga Lake', 'Finger Lakes', 'Hudson River Region', 'Lake Erie', 'Long Island', 'New York', 'Niagara County', 'Niagara Escarpment', 'North Fork of Long Island', 'Seneca Lake', 'The Hamptons, Long Island'],
        'North Carolina' : ['Haw River Valley', 'North Carolina', 'Swan Creek', 'Yadkin Valley'],
        'North Dakota' : ['North Dakota'],
        'Ohio' : ['Grand River Valley', 'Isle St. George', 'Kanawha River Valley', 'Lake Erie', 'Loramie Creek', 'Ohio', 'Ohio River Valley'],
        'Oklahoma' : ['Oklahoma', 'Ozark Mountain'],
        'Oregon' : [
            'Applegate Valley', 'Chehalem Mountains', 'Columbia Gorge', 'Columbia Valley', 'Dundee Hills',
            'Elkton Oregon', 'Eola Amity Hills', 'Hood River County', 'McMinnville', 'Oregon', 'Polk County',
            'Red Hill Douglas County', 'Ribbon Ridge', 'Rouge Valley', 'Snake River Valley', 'Southern Oregon',
            'The Rocks District of Milton-Freewater', 'Umpqua Valley', 'Van Duzer Corridor', 'Walla Walla Valley'
        ],
        'Pennsylvania' : ['Pennsylvania', 'Brandywine Valley', 'Central Delaware Valley', 'Cumberland Valley', 'Lake Erie', 'Lancaster Valley', 'Lehigh Valley', 'Pennsylvania'],
        'Rhode Island' : ['Rhode Island', 'Southeastern New England'],
        'South Carolina' : ['South Carolina'],
        'South Dakota' : ['South Dakota'],
        'Tennessee' : ['Mississippi Delta', 'Tennessee'],
        'Texas' :  ['Texas', 'Bell Mountain', 'Escondido Valley', 'Fredericksburg in the Texas Hill Country', 'Lubbock County', 'Mesilla Valley', 'Texas', 'Texas Davis Mountains', 'Texas High Plains', 'Texas Hill Country', 'Texoma'],
        'Utah' : ['Utah'],
        'Vermont' : ['Vermont'],
        'Virginia' :  ['Loudoun County', 'Monticello', 'North Fork of Roanoke', 'Northern Neck George Washington Birthplace','Orange County', 'Rocky Knob', 'Shenandoah Valley', 'Virginia', 'Virginia\'s Eastern Shore'],
        'Washington' :  ['Ancient Lakes of Columbia Valley', 'Columbia Gorge', 'Columbia Valley', 'Horse Heaven Hills','Lake Chelan', 'Lewis-Clark Valley', 'Naches Heights', 'Puget Sound', 'Rattlesnake Hills','Red Mountain', 'Royal Slope', 'Snipes Mountain', 'The Rocks District of Milton-Freewater','Wahluke Slope', 'Walla Walla Valley', 'Washington', 'Yakima Valley'],
        'West Virginia' : ['Kanawha River Valley', 'Ohio River Valley', 'Shenandoah Valley', 'West Virginia'],
        'Wisconsin' : ['Lake Wisconsin', 'Upper Mississippi River Valley', 'Wisconsin'],
        'Wyoming' : ['Wyoming'],
        'Ahr' : ['Landwein Ahrtaler','Walporzheim'],
        'Baden' : ['Badische Bergstrasse', 'Bodensee', 'Breisgau', 'Kaiserstuhl', 'Kraichgau','Landwein Taubertäler', 'Landwein Unterbadischer', 'Markgräflerland', 'Ortenau','Südbadischer', 'Tauberfranken', 'Tuniberg'],
        'Franken' : ['Burgstadt', 'Landwein Fränkischer', 'Landwein Regensbruger', 'Maindreieck', 'Mainviereck', 'Steigerwald', 'Tauberfranken'],
        'Hessische Bergstrasse' : ['Landwein Starkenburger', 'Starkenburg', 'Umstadt'],
        'Mittelrhein' : ['Landwein Rheinburgen', 'Loreley', 'Siebengebirge'],
        'Mosel' : ['Bernkastel', 'Burg Cochem', 'Landwein Saarländischer', 'Landwein der Mosel','Landwein der Ruwer', 'Moseltor', 'Obermosel', 'Ruwertal'],
        'Nahe' : ['Landwein Nahegauer', 'Nahetal'],
        'Pfalz' : ['Landwein Pfälzer', 'Mittelhaardt-Deutsche Weinstrasse', 'Südliche Weinstrasse'],
        'Rheingau' : ['Johannisberg', 'Landwein Altrheingauer'],
        'Rheinhessen' : ['Bingen', 'Landwein Rheinischer', 'Nierstein', 'Wonnegau'],
        'Saale-Urstut' : ['Landwein Mitteldeutscher', 'Mansfelder Seen', 'Schlossneuenburg', 'Thüringen'],
        'Sachsen' : ['Dresden', 'Elstertal', 'Landwein Mecklenburger', 'Landwein Sächsischer', 'Meissen'],
        'Schleswig Holstein' : [],
        'Württemberg' : ['Bayerischer Bodensee', 'Kocher-Jagst-Tauber', 'Landwein Bayerischer Bodensee','Landwein Schwäbischer', 'Oberer Neckar', 'Remstal-Stuttgart', 'Württembergisch Bodensee','Württembergisch Unterland'],
        'Bergland' : ['Kärnten', 'Oberösterreich', 'Salzburg', 'Tirol'],
        'Bodensee-Vorarlberg' : [],
        'Burgenland' : ['Eisenberg', 'Leithaberg', 'Mittelburgenland', 'Neusiedlersee','Neusiedlersee-Hügelland', 'Rosalia', 'Ruster Ausbruch', 'Südburgenland'],
        'Niederösterreich (Lower Austria)' : ['Carnuntum', 'Kamptal', 'Kremstal', 'Thermenregion', 'Traisental', 'Wachau', 'Wagram', 'Weinviertel'],
        'Steiermark (Styria)' : ['Südsteiermark', 'Vulkanland Steiermark', 'Weststeiermark'],
        'Wien (Vienna)' : [],
        'Abruzzo' : ['Alto Tirino', 'Cerasuolo dAbruzzo', 'Colli Aprutini', 'Colli del Sangro','Colline Frentane', 'Colline Pescaresi', 'Colline Teatine', 'Controguerra',
            'Del Vastese / Histonium', 'Montepulciano dAbruzzo', 'Ortona', 'Terre Aquilane',
            'Terre Tollesi', 'Terre di Chieti', 'Trebbiano dAbruzzo', 'Valle Peligna', 'Villamagna'
        ],
        'Basilicata' : ['Aglianico del Vulture', 'Basilicata', 'Grottino di Roccanova', 'Matera', 'Terre dell Alta Val dAgri'],
        'Calabria' : ['Arghilla', 'Bigonvi', 'Calabria', 'Ciro', 'Costa Viola', 'Greco di Bianco','Lamezia', 'Lipuda', 'Locride', 'Melissa', 'Palizzi', 'Pellaro','San´t Anna di Isola Capo Rizzuto', 'Savuto', 'Scavigna', 'Scilla','Terre di Cosenza', 'Val di Neto', 'Valdamato'],
        'Campania' : ['Aglianico del Taburno', 'Aversa', 'Beneventano', 'Campania', 'Campi Flegrei','Capri', 'Casavecchia di Pontelatone', 'Castel San Lorenzo', 'Catalanesca del Monte Somma','Cliento', 'Colli di Salerno', 'Costa d´Amalfi', 'Dugenta', 'Epomeo', 'Falanghina del Sannio','Falerno del Massico', 'Fiano di Avellino', 'Galluccio', 'Greco Campania', 'Greco di Tufo','Irpinia', 'Ischia', 'Lacrima Christi del Vesuvio', 'Paestum', 'Penisola Sorrentina', 'Pompeiano','Roccamonfina', 'Sannio', 'Taurasi', 'Terre del Volturno', 'Vesuvio'],
        'Emilia Romagna' : ['Bianco di Castelfranco Emilia', 'Bosco Eliceo', 'Colli Bolognesi', 'Colli Bolognesi Classico Pignoletto','Colli Bolognesi Pignoletto', 'Colli Piacentini', 'Colli Romagna Centrale', 'Colli d´Imola', 'Colli di Faenza','Colli di Parma', 'Colli di Rimini', 'Colli di Scandiano e di Canossa', 'Emilia', 'Forli', 'Fontana del Taro','Gutturnio', 'Lambrusco Grasparossa di Castelvetro', 'Lambrusco Salamino di Santa Croce', 'Lambrusco di Sorbara','Modena', 'Ortrugo dei Colli Piacentini', 'Ravenna', 'Reggiano', 'Reno', 'Romagna', 'Sillaro', 'Terre di Veleja','Val Tidone'],
        'Friaul-Venezia Giulia' : ['Alto Livenza', 'Carso', 'Carso Classico', 'Colli Orientali del Friuli', 'Collio', 'Collio Goriziano','Delle Venezie', 'Friuli', 'Friuli Annia', 'Friuli Aquileia', 'Friuli Colli Orientali', 'Friuli Grave','Griuli Isonzo', 'Friuli Latisana', 'Lison', 'Lison Classico', 'Lison Pramaggiore', 'Prosecco', 'Prosecco Trieste','Ramandolo', 'Rosazzo', 'Trevenezie', 'Venezia Giulia'],
        'Lazio' : ['Aleatico di Gradoli', 'Anagni', 'Aprilla', 'Atina', 'Bianco Capena', 'Cannellino di Frascati','Castelli Romani', 'Cervereti', 'Cesanese del Piglio', 'Cesanese di Affile', 'Cesanese di Olevano Romano','Circeo', 'Civitella d Agliano', 'Colli Albani', 'Colli Cimini', 'Colli Etruschi Vitebesi', 'Colli Lanuvini','Colli della Sabina', 'Cori', 'Costa Etrusco Romana', 'Frascati', 'Frusinate', 'Genazzano', 'Lazio', 'Marino','Montecompatri Colonna', 'Nettuno', 'Orvieto', 'Roma', 'Tarquinia', 'Terracina', 'Velletri', 'Vignanello','Zagarolo'],
        'Liguria' : ['Cinque Terre', 'Cinque terre Sciacchetra', 'Colli di Luni', 'Colline Savonesi', 'Colline del Genovesato','Colline di Levanto', 'Golfo die Poeti', 'Golfo del Tigullio', 'Golfo del Tigullio-Portofino o Portofino','Liguria di Levante', 'Pornassio', 'Rivera Liguria di Ponente', 'Rossesse di Doleacqua', 'Terrazze dell Imperiese','Val Polcevera'],
        'Lombardia' :  ['Alpi Retiche', 'Alto Mincio', 'Benaco Bresciano', 'Bergamasca', 'Bonarda dell´Oltrepo Pavese','Botticino', 'Buttafuoco dell´Oltrepo Pavese', 'Capriano del Colle', 'Casteggio', 'Cellatica','Collina del Milanese', 'Curtefranca', 'Franciacorta', 'Garda', 'Garda Colli Mantovani','Lambrusco Mantovano', 'Lugana', 'Montenetto di Brescia', 'Moscato di Scanzo', 'Oltrepo Pavese','Oltrepo Pavese Metodo Classico', 'Provincia di Mantova', 'Provincia di Pavia', 'Quistello','Riviera del Garda Classico', 'Ronchi Varesini', 'Ronchi di Brescia', 'Sabbioneta', 'San Colombano al Lambro','San Martino della Battaglia', 'Sangue di Giuda', 'Sebino', 'Sforzato di Valtellina', 'Terrazze Retiche di Sondrio','Terre Lariane', 'Terre del Colleoni', 'Valcamonica', 'Valcalepio', 'Valtellina', 'Valtenesi'],
        'Marche' :  ['Bianchello del Metauro', 'Castelli di Jesi Verdicchio', 'Castelli di Jesi Verdicchio Classico', 'Colli Maceratesi','Colli Pesaresi', 'Conero', 'Esino', 'Falerio', 'I Terreni di San Severino', 'Lacrima di Morro d´Alba','Marche', 'Offida', 'Pergola', 'Rosso Conero', 'Rosso Piceno', 'San Ginesio', 'Serrapetrona', 'Terre di Offida','Verdicchio del Castelli di Jesi', 'Verdicchio del Castelli di Jesi Classico', 'Verdicchio die Matelica','Vernaccia di Serrapetrona'],
        'Molise' : ['Biferno', 'Molise', 'Osco', 'Pentro d´Isernia', 'Rotae', 'Terre degli Osci', 'Tintilla del Molise'],
        'Piemonte' : [
            'Alba', 'Albugnano', 'Alta Langa', 'Asti', 'Barbaresco', 'Barbera d´Alba', 'Barbera d´Asti', 'Barbera del Monferrato',
            'Barolo', 'Boca', 'Brachetto d´Acqui', 'Bramaterra', 'Calosso', 'Caluso Passito', 'Canavese', 'Carema',
            'Cisterna d´Asti', 'Colli Saluzzesi', 'Colli Tortonesi', 'Collina Torinese', 'Colline Novaresi',
            'Cortese dell´Alto Monferrato', 'Coste della Sesia', 'Dogliani', 'Dolcetto d´Acqui', 'Dolcetto d´Asti',
            'Dolectto d´Alba', 'Dolcetto delle Langhe Monregalesi', 'Dolcetto di Diano d´Alba', 'Dolcetto di Dogliani',
            'Dolcetto di Ovada', 'Erbaluce di Caluso', 'Fara', 'Freisa d´Asti', 'Freisa di Chieri', 'Gabiano', 'Gattinara',
            'Gavi', 'Ghemme', 'Grignolino d´Asti', 'Grignolino del Monferrato Casalese', 'Langhe', 'Lessona', 'Loazzolo',
            'Malvasia di Casorzo d´Asti', 'Malvasia di Castelnuovo Don Bosco', 'Monferrato', 'Moscato d´Asti',
            'Nebbiolo d´Alba', 'Nizza', 'Piemonte', 'Piemonte Moscato Passito', 'Pinerolese', 'Roero', 'Rubino di Cantavenna',
            'Ruche di Castagnole Monferrato', 'Sizzano', 'Terre Alfieri', 'Valli Ossolane', 'Valsusa', 'Verduno Pelaverga'
        ],
        'Puglia' : ['Aleatico di Puglia', 'Alezio', 'Barletta', 'Brindisi', 'Cacc´e Mitte di Lucera', 'Canosa', 'Castel del Monte','Colline Joniche Tarantine', 'Copertino', 'Daunia', 'Galatina', 'Gioia del Colle', 'Gravina', 'Leverano', 'Lizzano','Locorotondo', 'Martina Franca', 'Matino', 'Moscato di Trani', 'Murgia', 'Nardo', 'Orta Nova', 'Ostuni',
            'Primitivo del Salento', 'Primitivo di Manduria', 'Puglia', 'Rosso di Cerignola', 'Salento', 'Salice Salentino','San Severo', 'Squinzano', 'Tarantino', 'Tavoliere', 'Terra d´Otranto', 'Valle d´Itria'],
        'Sicilia' : ['Alcamo', 'Avola', 'Camarro', 'Cerasuolo di Vittoria', 'Cerasuolo di Vittoria Classico', 'Contea si Sclafani','Contessa Entellina', 'Delia Nivolelli Nero d´Avola', 'Eloro', 'Erice', 'Etna', 'Etna bianco', 'Etna rosato','Etna rosso', 'Faro', 'Fontanarossa di Cerda', 'Malvasia delle Lipari', 'Mamertino di Milazzo', 'Marsala','Menfi', 'Monreale', 'Moscato di Noto Naturale', 'Moscato di Pantelliera', 'Moscato di Siracusa', 'Noto',
            'Pantelliera', 'Passito di Pantelliera', 'Riesi', 'Salaparuta', 'Salemi', 'Salina', 'Sambuca di Sicilia','Santa Margherita di Belice', 'Sciacca', 'Sicilia', 'Siracusa', 'Terre Siciliane', 'Valle Belice', 'Vittoria'
        ],
        'Toscana' : [
            'Alta Valle della Greve', 'Ansonica Costa dell´Argentario', 'Barco Reale di Camignano', 'Bianco Pisano di San Trope', 'Bianco dell´Empolese', 'Bianco di Pitigliano', 'Bolgheri', 'Bolgheri Sassicaia', 'Brunello di Montalcino', 'Candia die Colli Apuani', 'Capalbio', 'Carmignano', 'Chianti', 'Chianti Classico', 'Colli dell´Etruria Centrale', 'Colli della Toscana Centrale', 'Colli di Luni', 'Colline Lucchesi', 'Cortona', 'Costa Toscana', 'Elba', 'Elba Aleatico Passito', 
            'Grance Senesi', 'Maremma Toscana', 'Montecarlo', 'Montecastelli', 'Montecucco', 'Monteregio di Massa Marittima', 'Montescudaio', 'Morellino di Scansano', 'Moscedello di Montalcino', 'Orcia', 'Parrina', 'Pomino', 'Rosso Toscano', 'Rosso di Montalcino', 'Rosso di Montepulciano', 'San Gimignano', 'Sant´Antimo', 'Sovana', 'Suvereto', 'Terratico di Bibbona', 'Terra di Casole', 'Terra di Pisa', 'Toscana', 'Val d´Arbia', 'Val di Cornia', 'Val di Magra', 'Val d´Arno di Sopra', 'Valdichiana', 'Valdinievole', 'Varnaccia di San Gimignano', 'Vin Santo del Chianti', 'Vin Santo del Chianti Classico', 'Vin Santo di Carmignano', 'Vin Santo di Montepulciano', 'Vino Nobile di Montepulciano'
        ],
        'Trentino Alto-Adige' :  ['Alto Adige / Südtirol','Alto Adige Terlano','Casteller','Delle Venezie','Lago di Caldaro','Mitterberg','Teroldego Rotaliano','Trentino','Trento','Trevenezie','Valdadige','Valdadige Terradeiforti','Vallagarina','Vigneti delle Dolomiti / Weinberg Dolomiten'],
        'Umbria' : [ 'Alto Adige / Südtirol','Alto Adige Terlano','Casteller','Delle Venezie','Lago di Caldaro','Mitterberg','Teroldego Rotaliano','Trentino','Trento','Trevenezie','Valdadige','Valdadige Terradeiforti','Vallagarina','Vigneti delle Dolomiti / Weinberg Dolomiten'],
        'Valle d´Aosta' : ['Valle d´Aosta'],
        'Veneto' : ['Alto Livenza', 'Alto Mincio', 'Amarone della Valpolicella Classico', 'Amarone della Valpolicella', 'Arcole', 'Asolo Prosecco', 'Bagnoli Friularo', 'Bagnoli di Spora / Bagnoli', 'Bardolino', 'Bardolino Classico', 'Bianco di Custoza', 'Breganze', 'Colli Berici', 'Colli Euganei', 'Colli Euganei Fior d´Arancio', 'Colli Trevigiani', 'Colli di Conegliano', 'Conegliano Valdobbiadene Prosecco', 'Conselvano', 'Corti Benedettine del Padovano', 'Delle Venezie', 'Gambellara', 'Gambellara Classico', 'Garda', 'Lessini Duello', 'Lison', 'Lison Pramaggiore', 'Lugana', 'Marca Trevigiana', 'Merlara', 'Monovitigno Corvina Veronese', 'Montello', 'Montello E Colli Asolani', 'Monti Lessini', 'Piave', 'Piave Malanotte', 'Prosecco', 'Prosecco Treviso', 'Recioto della Valpolicella', 'Recioto di Soave', 'Recioto di Gamellara', 'Ripasso Superiore Della Valpolicella', 'Riviera del Brenta', 'San Martino della Battaglia', 'Soave', 'Soave Classico', 'Valdadige', 'Valdadige Terra die Forti', 'Valdobbiadene Prosecco', 'Vallagarina', 'Valpolicella', 'Valpolicella Classico', 'Valpolicella Ripasso', 'Valpolicella Ripasso Classico', 'Valpolicella Ripasso Valpantena', 'Veneto', 'Veneto Orientale', 'Venezia', 'Verona', 'Vicenza', 'Veronese', 'Vigneti della Serenissima', 'Vigneti delle Dolomiti'],
        'Andalucia' : ['Altiplano de Sierra Nevada', 'Bailen', 'Condado de Huelva', 'Cumbres de Guadalfeo', 'Cadiz', 'Cordoba', 'Desierto de Almeria', 'Granada', 'Jerez /Xeres/Sherry', 'Laujar-Alpujarra', 'Lebrija', 'Los Palacios', 'Manzanilla', 'Montilla-Moriles', 'Malaga', 'Norte de Almeria', 'Ribera del Andarax', 'Sierra Norte de Sevilla', 'Sierra Sur de Jaen', 'Sierras de Las Estancias y Los Filabres', 'Sierras de Malaga', 'Torreperogil', 'Villaviciosa de Cordoba'],
        'Aragon' : ['Bajo Aragon', 'Calatayud', 'Cambo de Borja', 'Carinena', 'Cava', 'Pago Ayles', 'Ribera del gallego-Cinco Villas', 'Ribera del Jiloca', 'Ribera del Queiles', 'Somontano', 'Valdejalon', 'Valle del Cinca'],
        'Asturias' : ['Cangas'],
        'Baleares' : ['Binissalem-Mallorca', 'Formentera', 'Ibiza', 'Illes Balears', 'Isla de Menorca', 'Mallorca', 'Pla i Llevant', 'Serra de Tramuntana-Costa Nord'],
        'Canarias' : ['Abona', 'El Hierro', 'Gran Canaria', 'Islas Canarias', 'La Gomera', 'La Palma', 'Lanzarote', 'Tacoronte-Acentejo', 'Valle de Güimar', 'Valle de la Orotava', 'Ycoden-Daute-Isora'],
        'Cantabria' : ['Costa de Cantabria','Liebana'],
        'Castilla La Mancha' : ['Almansa', 'Campo de la Guardia', 'Casa del Blanco', 'Castilla', 'Dehesa del Carrizal', 'Domino de Valdepusa', 'Finca Elez', 'Guijoso', 'Galvez', 'Jumilla', 'La Mancha', 'Machuela', 'Mentrida', 'Mondejar', 'Pago Calzadillla', 'Pago Florentino', 'Pozohondo', 'Ribera del Jucar', 'Sierra de Alcaraz', 'Ucles', 'Valdepenas'],
        'Castilla y Leon' : ['Arianza', 'Arribes', 'Bierzo', 'Castilla y Leon', 'Cava', 'Cabreros', 'Cigales', 'Ribera del Duero', 'Rueda', 'Sardon de Duero', 'Sierra de Salamanca', 'Tierra de Leon', 'Tierra del vino de Zamora', 'Toro', 'Valles de Benavente', 'Valtiendas'],
        'Cataluna' : ['Alella', 'Catalunya', 'Cava', 'Conca Del Riu Anoia', 'Conca de Barbera', 'Corpinnat', 'Costers del Segre', 'Emporda', 'Montsant', 'Penedes', 'Pia de Bages', 'Priorat', 'Tarragona', 'Terra Alta'],
        'Extremadura' : ['Cava','Extremadura','Ribera del Guadiana'],
        'Galicia' : ['Barbanza e Iria', 'Betanzos', 'Monterrei', 'Ribeira Sacra', 'Ribeiras do Morrazo', 'Ribeiro', 'Rias Baixas', 'Valdeorras', 'Valle del Mino-Orense'],
        'La Rioja' : ['Cava','Rioja','Valles de Sadacia'],
        'Madrid' : ['Vinos de Madrid'],
        'Murcia' : ['Abanilla', 'Bullas', 'Campo de Cartagena', 'Jumilla', 'Yecla'],
        'Navarra' : ['3 Riberas', 'Baja Montana', 'Cava', 'Pago Finca Bolandin', 'Pago de Arinzano', 'Pago de Otazu', 'Prado de Irache', 'Ribera Alta', 'Ribera Baja', 'Ribera del Quelles', 'Tierra Estella', 'Valdizarbe'],
        'Pais Vasco' : ['Arabako Txakolina', 'Bizkaiko Txakolina', 'Cava', 'Getariako Txakolina', 'Rioja Alavesa', 'Vizcaya Txakolina'],
        'Valencia' : ['Arabako Txakolina', 'Bizkaiko Txakolina', 'Cava', 'Getariako Txakolina', 'Rioja Alavesa', 'Vizcaya Txakolina'],
        'Alsace' : ['Alsace', 'Alsace Edelzwicker', 'Alsace Grand Cru', 'Bas Rhin', 'Cremant dÁlsace', 'Haut Rhin'],
        'Auvergne' : ['Cantal','Puy de Dome'],
        'Beaujolais' : ['Beaujolais', 'Beaujolais-Villages', 'Brouilly', 'Chiroubles', 'Chenas', 'Citeaux du Lyonnais', 'Cote de Brouilly', 'Fleurie', 'Julienas', 'Morgon', 'Moulin a Vent', 'Regnie', 'Saint-Amour'],
        'Bordeaux' : ['Barsac', 'Blaye', 'Blaye-Cotes de Bordeaux', 'Bordeaux Clairet', 'Bordeaux Cotes de France', 'Bordeaux Haut-Benauge', 'Bordeaux Rose', 'Bordeaux Sec', 'Bordeaux Superieur', 'Cadillac', 'Cadillac-Cotes de Bordeaux', 'Canon-Fronsac', 'Castillon-Cotes de Bordeaux', 'Cremant de Bordeaux', 'Cerons', 'Cotes de Blaye', 'Cote de Bordeaux', 'Cote de Bordeaux Saint-Macaire', 'Cotes de Bourg', 'Cotes de Castillon', 'Entre-Deux-Mers', 'Entre-Deux-Mers Haut-Benauge', 'Francs-Cotes de Bordeaux', 'Fronsac', 'Graves', 'Graves Superieures', 'Graves de Vayres', 'Haut-Medoc', 'Lalande de Pomerol', 'Listrac-Medoc', 'Loupiac', 'Lussac Saint-Emilion', 'Moulis en Medoc', 'Medoc', 'Paulliac', 'Pessac-Leognan', 'Pomerol', 'Premieres Cote de Blaye', 'Premieres Cote de Bordeaux', 'Puisseguin Saint Emilion', 'Saint-Emilion', 'Saint-Emilion Grand Cru', 'Saint-Estephe', 'Saint-Georges Saint-Emilion', 'Saint-Julien', 'Sainte-Croix du Mont', 'Sainte-Foy Bordeaux', 'Sauternes'],
        'Burgund' : ["Aloxe-Corton","Auxey-Duresses","Auxois","Batrad-Montrachet","Beaune","Bienvenues-Batard-Montrachet","Blagny","Bonnes-Mares","Bourgogne","Bourgogne Aligote","Bourgogne Chitry","Bourgogne","Choulanges-la-Vineuse","Bourgogne Cote Chalonnaise","Bourgogne Cote Saint-Jacques","Bourgogne Cote dÓr","Bourgogne Cote dÓr","Bourgogne Cote du Couchois","Bourgogne Cote d´Auxerre","Bourgogne Epineuil","Bourgogne Haut-Cotes de Beaune","Bourgogne Hautes-Cotes de Nuits","Bourgogne La Chapelle Notre-Dame","Bourgogne Le Chapitre","Bourgogne Montrecul","Bourgogne Mousseux","Bourgogne Passe-tout-grains","Bourgogne Tonnerre","Bouzeron","Chablis","Chablis 1er Cru","Chablis Grand Cru","Chambertin","Chambertin Clos-de-Beze","Chambolle Musigny","Chapelle Chambertin","Charlemagne","Charmes-Chambertin","Chassagne-Montrachet","Chevalier-Montrachet","Chorey-les-Beaune","Clos Saint-Denis","Clos Vougeot","Clos de Tart","Clos de la Roche","Clos des Lambrays","Corton","Corton-Charlemagne","Coteaux Bourguignons","Coteaux de Tannay","Criots Batard-Montrachet","Cremant de Bourgogne","Cote de Beaune","Cotes de Beaune-Villages","Cote de Nuits-Villages","Echezeaux","Fixin","Gevrey-Chambertin","Givry","Grands Echezeaux","Griotte-Chambertin","Irancy","La Grande Rue","La Romanee","La Tache","Ladoix","Latricieres-Chambertin","Maranges","Marsannay","Marsanny Rose","Matis-Chambertin","Mazoyeres-Chambertin","Mercuery","Meursault","Meursault-Blagny","Montagny","Monthelie","Montrachet","Morey Saint-Denis","Musigny","Macon","Macon Superieur","Macon-Aze","Macon-Bray","Macon-Burgy","Macon-Bussieres","Macon-Chaintre","Macon-Chardonnay","Macon-Charnay-les-Macon","Macon-Cruzille","Macon-Davaye","Macon-Fuisse","Macon-Ige","Macon-La Roche-Vineuse","Macon-Loche","Macon-Lugny","Macon-Mancey","Macon-Milly-Lamartine","Macon-Montbellet","Macon-Pierreclos","Macon-Prisse","Macon-Perrone","Macon-Saint-Genoux-le-National","Macon-Serrieres","Macon-Solutre-Pouilly","Macon-Uchizy","Macon-Vergisson","Macon-Verze","Macon-Villages","Macon-Vinzelles","Nievre","Nuits-Saint-Georges","Pernand-Vergelesses","Petit Chablis","Pommard","Pouilly-Fuisse","Pouilly-Loche","Pouilly-Vinzelles","Puligny-Montrachet","Richebourg","Romanee Saint-Vivant","Romanee-Conti","Ruchottes-Chambertin","Rully","Saint-Aubin","Saint-Bris","Saint-Romain","Saint-Veran","Santeany","Savigny-les-Beaune","Saone-et-Loire","Vezelay","Vire-Clesse","Volnay","Vosne-Romanee","Vougeot","Yonne"],
        'Bretagne' :  ["Cidre de Bretagne", "Cidre de Cornouaille", "Cidre de variete Guillevic", "Pommeau de Bretagne"],
        'Champagne' : ["Champagne", "Champagne", "Coteaux Champenois", "Coteaux de Coiffy", "Haute-Marne", "Rose des Riceys"],
        'Corse' : ["Corse", "Ajaccio", "Corse", "Corse Clavi", "Corse Coteaux du Cap Corse", "Corse Figari", "Corse Porto Vecchio", "Corse Sartene", "Ile de Beaute", "Muscat du Cap Rose", "Patrimonio"],
        'Ile de France' : ['Ile de France'],
        'Jura' : ["Arbois", "Arbois Pupillin", "Chateau-Chalon", "Cremant de Jura", "Cotes de Jura", "Doubs", "Franche Comte", "L´Etoile", "Macvin du Jura"],
        'Languedoc-Roussillion' : ["Adrailhou", "Aude", "Banyuls", "Banyuls Grand Cru", "Bessan", "Blanquette de Limoux", "Benovie", "Bernage", "Cabardes", "Cassan", "Catalan", "Caux", "Cessenon", "Cite de Carcassonne", "Clairette du Languedoc", "Collines de la Moure", "Collioure", "Corbieres", "Corbieres Boutenac", "Costieres de Nimes", "Coteaux Flaviens", "Coteaux d´Enserune", "Coteaux de Bessilles", "Coteaux de Beziers", "Coteaux de Ceze", "Coteaux de Fenouilledes", "Coteuax de Foncaude", "Coteaux de Laurens", "Coteuax de Miramont", "Coteaux de Murviel", "Coteuax de Narbonne", "Coteaux de Peyriac", "Coteaux de la Cabrerisse", "Coteaux du Languedoc", "Coteaux du Libron", "Coteaux de Littoral Audois", "Coteaux de Pont du Gard", "Coteaux de Limoux", "Cucugnan", "Cevennes", "Cote Vermeille", "Cotes Catalanes", "Cotes de Lastours", "Cotes de Prouille", "Cotes de Perignan", "Cotes de Thau", "Cotes de Thongue", "Cotes du Brian", "Cotes du Ceressou", "Cotes du Roussillion", "Cotes du Roussillion Villages", "Cotes du Roussillion Villages Caramany", "Cotes du Roussillion Villiages Latour de France", "Cotes du Roussillion Villages Lesquerde", "Cotes du Roussillion Villages Tautavel", "Cote du Vidourel", "Duche d´Uzes", "Faugeres", "Fitou", "Gard", "Gorges de l´Herault", "Haut Vallee de l´Aude", "Haut Vallee de l´Orb", "Hauterive den Pays d´Aude", "Hauts de Badens", "Herault", "La Clape", "Languedoc", "Languedoc Cabrieres", "Languedoc Fonseranes", "Languedoc Gres de Montpellier", "Languedoc La Mejanelle", "Languedoc Montpeyroux", "Languedoc Pezenas", "Languedoc Quatourze", "Languedoc Saint-Christol", "Languedoc Saint-Drezery", "Languedoc Saint-Georges d´Orques", "Languedoc Saint-Saturnin", "Languedoc Sommieres", "Languedoc Terrasses de Beziers", "Limoux", "Malepere", "Maury", "Maury Sec", "Minervois", "Minervois-La-Liviniere", "Mont Baudile", "Monts de la Grage", "Muscat de Frontignan", "Muscat de Lunel", "Muscat de Mireval", "Muscat de Rivesaltes", "Muscat de Saint-Jean de Minervois", "Mediterranee", "Pays Cathare", "Pays d´Oc", "Pic Saint-Loup", "Picpoul de Pinet", "Pyrenees-Orientales", "Rivesaltes", "Rivsesaltes sec", "Sables du Camargue", "Sables du Golfe du Lion", "Saint-Chinian", "Saint-Chinian - Berlou", "Saint-Chinian - Roquebrun", "Saint-Guihem-Le-Desert", "Terrasses du Lazarc", "Terres du Midi", "Torgan", "Val de Cesse", "Val de Dagne", "Val de Montferrand", "Vallee du Paradis", "Vals d´Agly", "Vaunage", "Vicomte d´Aumelas", "Vistrenque"],
        'Lorraine' : ["Cotes de Meuse", "Cotes de Toul", "Meuse", "Moselle"],
        'Nord' : [],
        'Normandie' : ["Calvados", "Cidre Pays d´Auge", "Cidre de Normandie", "Pays d´Auge Cambremer"],
        'Outre-Mer' : ["Guadeloupe", "Guyane", "Martinique", "Mayotte", "Nouvelle-Caledonie", "Polynesie francaise", "Reunion", "Saint-Barthelemy", "Saint-Martin", "Saint-Pierre-et-Miquelon", "Wallis et Futuna"],
        'Cognac' : ["Charentais", "Cognac", "Deux-Sevres", "Pineau des Charentes", "Vienne"],
        'Provence' :  ["Aigues", "Alpes Martitimes", "Aples de Haut Provence", "Alpilles", "Argens", "Bandol", "Bellet", "Bouches du Rhone", "Cassis", "Coteaux Varois", "Coteaux d´Aic-En-Provence", "Coteaux-du-Verdon", "Cotes de Provence", "Cotes de Provence Frejus", "Cotes de Provence La-Londe", "Cotes de Provence Pierrefeu", "Cotes de Provence Sainte-Victoire", "Hautes Alpes", "Les Baux de Provence", "Maures", "Mont-Chaumes", "Mediterranee", "Palette", "Petite Crau", "Principaute d´Orange", "Var"],
        'Savoie' : ["Ain", "Allobroges", "Bugey", "Bugey-Cerdon", "Chignin", "Chignin-Bergeron", "Coteaux de l´Ain", "Coteaux du Gresivaudan", "Cremant du Savoie", "Crepy", "Isere", "Roussette de Monterminod", "Roussette de Savoie", "Roussette de Bugey", "Savoie", "Seyssel", "Vin de Savoie Ripaille"],
        'Sud-Oest' : ["Agennais", "Armagnac", "Atalantique", "Aveyron", "Bas-Armagnac", "Bergerac", "Bigorre", "Brulhois", "Buzet", "Bearn", "Cahors", "Comte-Tolosan", "Correze", "Coteaux de Glanes", "Coteaux de Quercy", "Coteaux et Terrasses de Montauban", "Cotes de Bergerac", "Cotes de Duras", "Cotes de Gascogne", "Cotes de Millau", "Cotes de Montestruc", "Cotes de Montravel", "Cotes du Condomois", "Cotes du Frontonnais", "Cotes du Lot", "Cotes du Marmandais", "Cotes-du-Tarn", "Dordogne", "Entraygues et le Fel", "Estaing", "Floc de Gascogne", "Fronton", "Gaillac", "Gers", "Gironde", "Haut-Montravel", "Haute-Garonne", "Iroulegyu", "Jurancon", "Landes", "Lavilledieu", "Lot", "Lot et Garonne", "Madrian", "Marcillac", "Monbazillac", "Montravel", "Pacherenc du Vic-Blh", "Pays de Brive", "Pecharmant", "Perigord", "Rosette", "Saint-Mont", "Saint-Sardos", "Saussignac", "Tarn-et-Garonne", "Terroirs Landais", "Thezac-Perricard", "Tursan"],
        'Vallee de la Loire' : ["Anjou", "Anjou-Coteaux de la Loire", "Anjou-Gamay", "Anjou-Villages", "Anjou-Villages Brissac", "Bonnezeaux", "Bourbonnais", "Bourgueil", "Cabernet d´Anjou", "Chaume", "Cher", "Cheverny", "Chinon", "Chateaumeillant", "Coteaux d´Ancenis", "Coteaux de l´Aubance", "Coteaux du Cher et de l´Arnon", "Coteaux du Giennois", "Coteaux du Loir", "Coteaux du Vendomois", "Coteaux-Charitois", "Coteaux-de-Saumur", "Coulee de Serrant", "Cour-Cheverny", "Cremant de Loir", "Cote Roannaise", "Cotes d´Auvergne", "Cotes du Forez", "Deux-Sevres", "Fiefs Vendeens", "Gros Plant du Pays Nantais", "Haut-Poitou", "Indre et Loire", "Jardin de la France", "Jasnieres", "Loir et Cher", "Loire Atlantique", "Loiret", "Maine-et-Loire", "Marches de Bretagne", "Mentou-Salon", "Montlouis", "Muscadet", "Muscadet Coteaux de la Loire", "Musacadet Cotes de Grand-Lieu", "Msacadet Sevre-et-Maine", "Muscadet-sur-Lie", "Orleans", "Orleans Clery", "Pouilly-Fume", "Pouilly-sur-Loire", "Quarts de Chaume", "Quincy", "Retz", "Reuilly", "Rose d´Anjou", "Saint-Nicolas de Bourgueil", "Saint-Pourcain", "Sancerre", "Sarthe", "Saumur", "Saumur Puy-Notre-Dame", "Saumur-Champigny", "Savennieres", "Savennieres Coulee-de-Serrant", "Savennieres Roche-aux-Moines", "Touraine", "Touraine Amboise", "Touraine Azay le Rideau", "Touraine Chenonceaux", "Touraine Mesland", "Touraine Noble-Joue", "Touraine Oisly", "Val de Loire", "Valencay", "Vendee", "Vienne", "Vin du Thouarsais", "Vouvray"],
        'Vallee du Rhone' :  ["Ardeche", "Balmes Dauphinoises", "Beaumes de Venise", "Brezeme", "Cairanne", "Chateau Grillet", "Chateauneuf-du-Pape", "Chatillon-en-Diois", "Clariette de Bellegarde", "Clairette de Die", "Collines Rhodaniennes", "Comte de Grignan", "Comtes Rhodaniens", "Condrieu", "Cornas", "Costieres de Nimes", "Coteaux de Die", "Coteuax de Pierrevert", "Coteaux de Tricastin", "Coteaux de l´Ardeche", "Coteaux des Baronnies", "Coteaux du Gresivaudan", "Crozes-Hermitage", "Cremant de Die", "Cote Rotie", "Coteaux de Vienne", "Cotes du Rhone", "Cotes du Rhone Villages", "Cotes du Rhone Villages Cairanne", "Cotes du Rhone Villages Chusclan", "Cotes du Rhone Villages Laudun", "Cotes du Rhone Villages Massif d´Uchaux", "Cotes du Rhone Villages Paln de Dieu", "Cotes du Rhone Villages Puymeras", "Cotes du Rhone Villages Roaix", "Cotes du Rhone Villages Rochegude", "Cotes du Rhone Villages Rousset-les-Vignes", "Cotes du Rhone Villages Sablet", "Cotes du Rhone Villages Saint-Gervais", "Cotes du Rhone Villages Saint-Maurice", "Cotes du Rhones Villages Saint-Pantaleon-les-Vignes", "Cotes du Rhone Villages Signargues", "Cotes du Rhone Villages Suze-la-Rousse", "Cotes du Rhone Villages Seguret", "Cotes du Rhone Villages Valreas", "Cotes du Rhone Villages Visan", "Cotes du Vivarais", "Drome", "Duche d´Uzes", "Gard", "Gigondas", "Grigan-Les-Adhemar", "Hermitage", "Lirac", "Luberon", "Muscat de Beaumes de Venise", "Mediterranee", "Pierrevert", "Principaute d´Orange", "Rasteau", "Saint-Joseph", "Saint-Peray", "Tavel", "Urfe", "Vacqueyras", "Valeras", "Vaucluse", "Ventoux", "Vinsobres"],
        'Vosges' : [],
        'Alentejo' : ['Alentejo','Alentejano'],
        'Algarve' :  ["Algarve", "Lagoa", "Lagos", "Portimao", "Tavira"],
        'Acores' :  ["Acores", "Biscoitos", "Graciosa", "Pico"],
        'Beira Atalntico' : ["Bairrada", "Beira Atalntico", "Sico"],
        'Beira Interior' : ["Beira Interior", "Beiras", "Terras de Beira"],
        'Douro' : ["Douro", "Duriense", "Moscatel do Douro", "Porto"],
        'Dao' : ["Dao", "Lafoes"],
        'Lisboa' :  ["Alenquer", "Arruda", "Bucelas", "Carcavelos", "Colares", "Encostas d´Aire", "Lisboa", "Lourinha", "Torres Vedras", "Obidos"],
        'Madeira' : ["Madeira", "Madeirense", "Tierras Madeirenses"],
        'Minho' : ["Minho", "Vinho Verde"],
        'Setubal' : ["Moscatel de Setubal", "Palmela", "Peninsula de Setubal", "Setubal", "Setubal Roxo", "Terras do Sado"],
        'Tavora e Varosa' : ['Tavora e Varosa'],
        'Tejo' : ['Tejo'],
        'Tras-os-Montes' : ["Transmontano", "Tras-os-Montes"],
        'Eastern Cape' : ['Eastern Cape','St Francis Bay'],
        'Kwazulu-Natal' : [],
        'Limpopo' : [],
        'Nothern Cape' : ["Central Orange River", "Douglas", "Northern Cape", "Rietriver", "Sutherland-Karoo"],
        'Western Cape' : ["Aan-de-Doorns", "Agterkliphoogte", "Bamboes Bay", "Banghoek", "Boberg", "Boesmansrivier", "Bonnievale", "Bot River", "Bottelary", "Breede River Valley", "Breedekloof", "Buffeljags", "Calitzdrop", "Cape Agulhas", "Cape Peninsula", "Cape Point", "Cape South Coast", "Cape Town", "Cederberg", "Ceres", "Citrusadl Mountain", "Citrusdal Valley", "Coastal Region", "Constantia", "Darling", "Devon Valley", "Durbanville", "Eilandia", "Elgin", "Elim", "Franschhoek", "Goudini", "Greyton", "Groenekloof", "Hemel-en-Aarde Valley", "Hemel-en-Aarde Ridge", "Herbertsdale", "Hex River Valley", "Hoopsrivier", "Hout Bay", "Jonkerhoek Valley", "Klaasvoogds", "Klein Karoo", "Klein River", "Koekenaap", "Lambert Bay", "Langeberg-Garcia", "Le Chasseur", "Lower Duivenhoks River", "Lutzville Valley", "Malgas", "Malmesbury", "McGregor", "Montagu", "Nuy", "Olifants River", "Outeniqua", "Overberg", "Paarl", "Papegaaiberg", "Philadelphia", "Piekenierskloof", "Piketberg", "Plettenberg Bay", "Polkadraai Hills", "Prince Albert Valley", "Riebeekberg", "Robertson", "Ruiterbosch", "Scherpenheuvel", "Simonsberg - Paarl", "Simonsberg - Stellenbosch", "Slanghoek", "Spruitdrift", "Stellenbosch", "Stilbaai East", "Stormsvlei", "Sunday´s Glen", "Swartberg", "Swartland", "Swellendam", "Theewater", "Tradouw", "Tradouw Highlands", "Tulbagh", "Tygerberg", "Upper Hemel-en-Aarde Valley", "Upper Langkloof", "Vinkrivier", "Voor Paardeberg", "Vredendal", "Walker Bay", "Wellington", "Western Cape", "Worcester"],
        'Buenos Aires' : [],
        'Catamarca' : ["Belen", "Fiambala", "Santa Maria", "Tinogasta"],
        'Chubut' : [],
        'Cordoba' : ["Caroya", "Traslasierra"],
        'Jujuy' : [],
        'La Pampa' : ['25 de Mayo'],
        'La Rioja' : ['Famatina'],
        'Mendoza' :["Agrelo", "Chacayes", "El Cepillo", "Gualtallary", "Junin", "La Consulta", "La Paz", "Lujan de Cuyo", "Lunlunta", "Maipu", "Paraje Altamira", "Rivadavia", "San Carlos", "San Rafael", "Santa Rosa", "Tunuyan", "Tupungato", "Valle de Uco", "Vista Flores"],
        'Neuquen' : ['San Patricio del Chanar'],
        'Patagonia' : [],
        'Rio Negro' : ["Rio Colorado", "Rio Negro"],
        'Salta' : ["Cafayate", "Calchaqui"],
        'San Juan' : ["Calingasta", "Jachal", "Pedernal", "Tulum", "Ullum", "Zonda", "Iglesias"],
        'Tucuman' : ['Amaicha','Colalao'],
        'Gansu' : [],
        'Hebei' : [],
        'Heilongjiang' : [],
        'Henan' : [],
        'Jilin' : [],
        'Liaoning' : [],
        'Ningxia' : ["Helan", "Hongsipu", "Qingtongxia", "Shizuishan", "Yinchuan", "Yongning"],
        'Shandong' :  ["Penglai", "Qingdao", "Yantai"],
        'Shanxi' : [],
        'Tianjin' : [],
        'Xinjiang' : [],
        'Yunnan' : [],
        'Aconcagua' : ["Aconcagua", "Casablanca", "Leyda", "Lo Abarca", "San Antonio"],
        'Atacama' : ["Atacama", "Copiapo", "Husaco"],
        'Austral' :  ["Cautin", "Osorno"],
        'Central Valley' : ["Apalta", "Cachapoal", "Cauquenes", "Colchagua", "Curico", "Lontue", "Los Lingues", "Maipo", "Maule", "Peumo", "Puente Alto", "Rapel"],
        'Coquimbo' : ["Choapa", "Elqui", "Limari"],
        'Southern Chile' : ["Bio-Bio", "Itata", "Malleco"],
        'Podravje (Sava Valley)' : ["Goricko", "Haloze", "Lendava", "Ljutomer-Ormoz", "Maribor", "Radgonska", "Srednje Slovenske Gorice"],
        'Podravje (Lower Sava Valley)' :  ["Bela krajina", "Bizeljsko-Sremic", "Dolenjska"],
        'Primorska (Littoral)' : ["Goriska Brda", "Istra (Koper)", "Kras", "Vipavska Dolina"],
        'Lebanon' : ['Bekaa Valley'],
        'Agean Islands' : ["Chios", "Cyclades", "Ikaria", "Lesvos", "Limnos", "Lipsi", "Mykonos", "Paros", "Rodos", "Samos", "Santorini", "Sikinos", "Syros", "Tinos"],
        'Epirus' : ["Ioannina", "Metsovo", "Zitsa"],
        'Ionian Islands' : ["Corfu", "Kefalonia", "Lefkada", "Slopes of Aenos", "Zakynthos"],
        'Kreta' : ["Corfu", "Kefalonia", "Lefkada", "Slopes of Aenos", "Zakynthos"],
        'Makedonia' : ["Amyntaio", "Chalkidiki", "Drama", "Epanomi", "Florina", "Giannitsa", "Grevena", "Imathia", "Kastoria", "Kavala", "Mount Athos", "Naoussa", "Pangeon", "Piera", "Plagies Melitona", "Serres", "Siatista", "Sithonia", "Thasos", "Thessaloniki", "Velvendo Kozanis"],
        'Peloponnes' : ["Ahaia", "Aigiala", "Argolida", "Llia", "Klimenti", "Korinthia", "Lakonia", "Letrini Llias", "Mantinia", "Messinia", "Monemvasia", "Namea", "Patra", "Rio Patras", "Tegea", "Trifillia"],
        'Sterea Ellada / Central Greece' : ["Afrati Evia", "Agios Konstantinos", "Aliartos", "Anavissos", "Askri", "Asopia Tanagras", "Atlanti", "Attiki", "Dervenohoria", "Distomo", "Dorida", "Evia", "Fokida", "Fthiotida", "Gialtra Edipsos", "Istiaia", "Karistos", "Koropi", "Krania", "Lamia", "Lilantio Pedio Evia", "Malakonta Evia", "Markopoulo", "Martino", "Megara", "Messologi", "Mouriki Thivas", "Oinofita", "Opountia Lokridos", "Orhomenos", "Pallini", "Parnasos Fthiotida", "Parnassos Fokida", "Peania", "Pendeliko", "Pikermi", "Plagies Kitherona Attika", "Plagies Parnithas Viotia", "Plagies Kitherona Viotia", "Plataea", "Prodromos", "Ritsona", "Schimatari", "Spata", "Stylida", "Tanagra", "Thiva", "Vagia Thivas", "Valley of Atalanti"],
        'Thessalia' : ["Anhialos", "Elassona", "Karditsa", "Krania", "Krannonas", "Magnisia", "Messenikolas", "Meteora", "Rapsani", "Tyrnavos"],
        'Thraki' : ['Avdira','Evros','Ismaros'],
        'Black Sea' : ['Evxinograd','Novi Pazar'],
        'Danubian Plain' : ["Lovech", "Lozitsa", "Lyaskovets", "Pavlikeni", "Pleven", "Rouisse", "Svishtov", "Vidin"],
        'Rose Valley' : ['Sliven'],
        'Struma Valley' : ['Melnik','Sandanski'],
        'Thracian Valley' : ["Assenovgrad", "Brestinik", "Haskovo", "Ivauylovgrad", "Karnobat", "Lyubimets", "Nova Zagora", "Peroushtica", "Plovidiv", "Pomorie", "Sakar", "Septemvri", "Stara Zagora", "Yambol"],
        'Moselle Luxembourgeoise' : ["Cremant de Luxembourg", "Luxembourg", "Moselle Luxembourgeoise"],
        'Balaton' : ["Badacsony", "Balatonfelvidek", "Balatonfüred-Csopak", "Balatonpglar", "Nagy-Somlo", "Zala"],
        'Duna- The great Hungarian plain (Alföld)' : ["Csongrad", "Hajos-Baja", "Kunsag"],
        'Del-Pannonia (South Pannonia)' : ["Pecs", "Szekszard", "Tolna", "Villany"],
        'Felso-Magyarorszag (Hegyvidek)' : ["Bükk", "Eger", "Matra"],
        'Tokaj' : ['Tokaj-Hegyalja'],
        'Eszak-Dunantul (North-Transdanubia)' : ["Etyek-Buda", "Mor", "Pannonhalma", "Sopron", "Aszar-Neszmely"],
        'Coastal Croatia' :   ["Central and Southern Dalmatia", "Croatian Primorje", "Dalmatian Highlands", "Northern Dalmatia"],
        'Continental Croatia' : ["Moslavina", "Plesevica", "Podunavlje", "Pokuplje", "Prigorje-Bilogra", "Slavonia", "Zagorje-Medimurje"],
        'Istria' : [],
        'British Columbia' : ["British Columbia", "Cowichan", "Fraser Valley", "Golden Mile Bench", "Gulf Islands", "Naramata Bench", "Okanagan Falls", "Okanagan Valley", "Similkameen Valley", "Skaha Bench", "Vancouver", "Vancouver Island"],
        'Newfoundland' : [],
        'Nova Scotia' : ["Annapolis Valley", "Beat River Valley", "LaHave River Valley", "Malagash Peninsula"],
        'Ontario' : ["Beamsville Bench", "Creek Shores", "Four Mile Creek", "Lake Erie North Shore", "Lincoln Lakeshore", "Niagara Escarpment", "Niagara Lakeshore", "Niagara Peninsula", "Niagara River", "Niagara-on-the-Lake", "Ontario", "Pelee Island", "Prince Edward County", "Short Hills Bench", "St. David's Bench", "Toronto", "Twenty Mile Bench", "Vinemount Ridge"],
        'Quebec' : ["Bas-Saint-Laurent", "Basses Laurentides", "Cantons-de-l'Est", "Centre-du-Quebec", "Lanaudiere", "Laurentides", "Laval", "Monteregie", "Outaouais", "Quebec"],
        'Campbeltown (Scotland)' : [],
        'Cornwall (England)' : [],
        'Devon (England)' : [],
        'Dorset (England' : [],
        'EastAnglia €' : [],
        'Gloucestershire (Eng.)' : [],
        'Hampshire (Eng.)' : [],
        'Herefordshire (Eng.)' : [],
        'Highland ( Scot.)' : [],
        'Island (Scot.)' : [],
        'Islay (Scot.)' : [],
        'Isle of Arran (Eng.)' : [],
        'Isle of Wight (Eng.)' : [],
        'Isle of Scilly (Eng.)' : [],
        'Jersey (Eng.)' : [],
        'Kent (Eng.)' : [],
        'Lincolnshire (Eng.)' : [],
        'London ' : [],
        'Lowland (Scot.)' : [],
        'Northhamptonshire (Eng.)' : [],
        'Oxfordshire (Eng.)' : [],
        'Shropshire (Eng.)' : [],
        'Somerset (Eng.)' : [],
        'Speyside ( Scot.)' : [],
        'Surrey (Eng.)' : [],
        'Sussex (Eng.)' : [],
        'Wales (Wales)' : [],
        'Worcestershire (Eng.)' : [],
        'Yorkshire (Eng.)' : [],
        'Black Sea Coastal Zone' : [],
        'Imereti' : ['Sviri'],
        'Kakheti' :  ["Akhasheni", "Gurjaani", "Kakheti", "Kardanakhi", "Kindzmarauli", "Kotekhi", "Kvareli", "Manavi", "Mukzani", "Napareuli", "Teliani", "Tibaani", "Tsinandali", "Vazisubani"],
        'Kartli' : ['Atenuri'],
        'Meskheti' : [],
        'Racha-Lechkhumi / Kvemo Svaneti' : ['Khvanchkara','Tvishi'],
    }

    regionselect.on('change',function(){
        const selectedRegion = regionselect.val();
        const appellations = appellationbyregion[selectedRegion] || [];
        appellationselect.empty();
        const defaultAppelation = $('<option>', { value: '', text: wkMpTrans.select_an_appellation });
        appellationselect.append(defaultAppelation);

        for (const appellation of appellations) {
            const option = $('<option>', {value: appellation, text: appellation });
            appellationselect.append(option);
        }

        if(appellations.length === 0) {
            appellationselectwrapper.hide();
            $('label[for="form_appellation"]').hide();
        } else {
            appellationselectwrapper.show();
            $('label[for="form_appellation"]').show();
        }
        
    });

});

$(document).ready(function($) {
    // $('#form_awards').select2()
    // var defaultValue = $('#form_awards option:selected').val();
    // var ratingsArray = $('#form_awards').attr('data-value');
    // const defaultValue = ratingsArray.split(' ,');
    // var defaultValue = ['James Suckling: 0-100', 'Wine Spectator: 0-100', 'Antonio Galloni: 0-100']
    // Set the default value in Select2
    // $('#form_awards').val(defaultValue).trigger('change');
    var clickCount = 0;
    $('#award-add-button').click(function (event) {
        clickCount++;
        let parentEle = $(this).parent();
        let awardEle = $(".form_awards");
        let valueEle = $(".form_awardsValue");
        var isValid = true;
        var awardEleHtml;
        var awardValueEleHtml;

        awardEle.each(function(index, element) {
            if ($(element).val() === "" || $(element).val() === wkMpTrans.select_awards) {
                swal({
                    title: "Warning",
                    text: "Select Awards",
                    icon: "warning",
                })
                isValid = false;
                return false;
            }
            awardEleHtml = element.parentElement.innerHTML.replace('selected=""', '')
        });

        valueEle.each(function(index, element) {
            if ($(element).val() === "" || $(element).val() === wkMpTrans.select_awards_value) {
                swal({
                    title: "Warning",
                    text: "Select Awards value",
                    icon: "warning",
                })
                isValid = false;
                return false;
            }
            awardValueEleHtml = element.parentElement.innerHTML.replace('selected=""', '')
        });

        if (!isValid) {
            return false;
        }

        var html = '<div class="col-sm-5 mt-2 add-' + clickCount + '"> ' + awardEleHtml + '</div> <div class="col-sm-5 mt-2 add-' + clickCount + '">  ' + awardValueEleHtml + ' </div> <div class="col-sm-2 mt-2"> <button type="button" id="award-add-button-remove" class="btn btn-danger award-add-button-remove" data-remove="add-' + clickCount + '" style="margin-top: 1.7rem !important;"> - </button> </div>';

        parentEle.after(html);
        $('.award-add-button-remove').on('click', function(e) {
            let deleteEle = $(this).attr('data-remove');
            $(this).parent().remove()
            $("." + deleteEle).remove()
            // const index = $(this).parent().remove();
        });
    })

    $('.award-add-button-remove').on('click', function(e) {
        let deleteEle = $(this).attr('data-remove');
        $(this).parent().remove()
        $("." + deleteEle).remove()
    });

});

$(document).ready(function() {
    $('#custom-select').select2({
        theme: 'classic', 
        minimumResultsForSearch: Infinity,// or 'default'
    });
    $('.vellum-discount').select2({
        minimumResultsForSearch: Infinity,
    })
    $('#form_grape_varity').select2({
        minimumResultsForSearch: Infinity,
    })
    var defaultValue = $('#form_grape_varity').attr('data-value');
    $('#form_grape_varity').val(defaultValue).trigger('change');

    $('#form_country').select2({
        minimumResultsForSearch: Infinity,  
    })
    var defaultValue = $('#form_country').attr('data-value');
    $('#form_country').val(defaultValue).trigger('change');

    $('#form_region').select2({
        minimumResultsForSearch: Infinity,
    })
    var defaultValue = $('#form_region').attr('data-value');
    $('#form_region').val(defaultValue).trigger('change');

    $('#form_appellation').select2({
        minimumResultsForSearch: Infinity,
    })
    var defaultValue = $('#form_appellation').attr('data-value');
    $('#form_appellation').val(defaultValue).trigger('change');

    $('#form_classification').select2({
        minimumResultsForSearch: Infinity,
    })
    var defaultValue = $('#form_classification').attr('data-value');
    $('#form_classification').val(defaultValue).trigger('change');

    $('#search_status_vellum').select2({
        minimumResultsForSearch: Infinity,
    })
    $('#search_interval_vellum').select2({
        minimumResultsForSearch: Infinity,
    })
    $('.custom-select_vellum').select2({
        minimumResultsForSearch: Infinity,
    })
    $('.vellum-select').select2({
        minimumResultsForSearch: Infinity,
    })
});

$(document).ready(function() {
    $('#wix_product_btn').click(function(e) {
        
        var url = window.location.pathname;
        var urlParts = url.split('/');
        var path = urlParts[urlParts.length - 1];
        var match = url.match(/\/product\/add/);
        var isValid = true;
        var focus = false;
        let storeHash = wkMpTrans.storeHash;
        let sellerMatch = url.match(/\/catalog\/add/);
        
        let arrayOfStoreHash = ['VILLUMIb6f3', 'RishabhStore-SAAS727a'];
        if (!arrayOfStoreHash.includes(storeHash)) {
            console.log(true);
            return true;
        }

        if (!match) {
            sellerMatch ? match = sellerMatch : false;
        }
        if (match) {
            var path = match[0];
            if (path === '/product/add' || path === '/catalog/add') {
                // Prodct create 
                $('#wixmp-product-form input[required], #wixmp-product-form select[required], #wixmp-product-form textarea[required]').each(function() {
                    if ($(this).val() === '') {
                        let elementType = $(this).get(0).nodeName;
                        if (elementType === 'SELECT') {
                            var cssRule = `.select2-container--default.select2-container--focus .select2-selection--single {
                                border-color: red;
                            }`;
                            $("<style>").text(cssRule).appendTo("head");
                            $(this).css('border-color', 'red');
                        } else if ( $(this).attr('type') === 'file') {
                            $(this).parent().css('border-color', 'red');
                        }

                        let eleName = $(this).attr('name');
                        $(".tab-pane").each(function(index, tab){
                            let emptyFeildTab = $(tab).find('[name="' + eleName + '"]');
                            if (emptyFeildTab.length >= 1) {
                                $('.tab-pane').removeClass('active show');
                                $('#' + $(tab).attr('id')).addClass('active show');
                                // $('a[href="#' + $(tab).attr('id') + '"]').click();
                                $('.nav-link').removeClass('active');
                                $('a[href="#' + $(tab).attr('id') + '"]').addClass('active');
                            }
                        });

                        if (!focus) {
                            $(this).focus().select()
                            focus = true;
                        }
                        isValid = false;
                        $(this).css('border-color', 'red');
                    } else {
                        $(this).removeAttr('style')
                    }
                });
            }
        } else {
            // Prodct update 
            $('#wixmp-product-form input[required], #wixmp-product-form select[required], #wixmp-product-form textarea[required]').each(function() {

                if ($(this).val() === '') {
                    let elementType = $(this).get(0).nodeName;
                    
                    if (elementType === 'SELECT') {
                        var cssRule = `.select2-container--default.select2-container--focus .select2-selection--single {
                            border-color: red;
                        }`;
                        $("<style>").text(cssRule).appendTo("head");
                        $(this).css('border-color', 'red');
                    } else if ( $(this).attr('type') !== 'file') {
                        $(this).css('border-color', 'red');
                    }

                    let eleName = $(this).attr('name');
                    $(".tab-pane").each(function(index, tab){
                        let emptyFeildTab = $(tab).find('[name="' + eleName + '"]');
                        if (emptyFeildTab.length >= 1) {
                            $('.tab-pane').removeClass('active show');
                            $('#' + $(tab).attr('id')).addClass('active show');
                            // $('a[href="#' + $(tab).attr('id') + '"]').click();
                            $('.nav-link').removeClass('active');
                            $('a[href="#' + $(tab).attr('id') + '"]').addClass('active');
                        }
                    });

                    if (!focus) {
                        $(this).focus().select()
                        focus = true;
                    }
                    isValid = false;
                } else {
                    $(this).removeAttr('style')
                }
            });
        }

        if (isValid) {
            e.preventDefault();
            var formData = $('#wixmp-product-form').serialize();
            $.ajax({
                method: 'POST',
                data: formData,
                beforeSend: function() {
                    $('.wk-overlay').show();
                },
                success: function(response) {
                    if (response.type === "success") {
                        swal({
                            title: "Success",
                            text: response.message ? response.message : '',
                            icon: "success",
                        })
                        window.location.href = response.url ? response.url : location.reload();
                    } else {
                        swal({
                            title: "Please check the fields!",
                            text: response.message ? response.message : '',
                            icon: "warning",
                        })
                    }
                },
                error: function(error) {
                    // $('.wk-overlay').hide();
                },
                complete: function() {
                    $('.wk-overlay').hide();
                },
            });
        }
    });

    $("#form_BottleSize").change(function (event) {
        let bottle_weight = $(this).val();
        // Check if the selected value is not empty
        if (bottle_weight !== "" && bottle_weight !== "Select Bottle Size") {
            // Set the value of #form_weight to the selected bottle size
            $("#form_weight").val(bottle_weight);
        } else {
            $("#form_weight").val(0);
        }
    });

    $("#form_name").keydown(function () {
        let product_name = $(this).val();
        return $('#form_sku').val(getSKU(product_name).toUpperCase());
    })

    function getSKU(product) {
        let randomString = '';
        for (let i = 0; i < 5; i++) {
          const randomIndex = Math.floor(Math.random()  * product.length);
          randomString += product.charAt(randomIndex);
        }
        return randomString;
    }

    let arrayOfStoreHash = ['VILLUMIb6f3', 'RishabhStore-SAAS727a'];
    if (arrayOfStoreHash.includes(wkMpTrans.storeHash) && window.wkMpTrans.area !== 'undefined' && window.wkMpTrans.area === "mp-wix-seller" ) {
        let header = $('.app-nav')
        document.getElementById('need-help') ? document.getElementById('need-help').remove() : '';
        header.append(`<li class="app-search">
        <a style="color:#fff" href="https://www.villumi.com/contactus" target="_blank" rel="nofollow"> <i class="fa fa-question-circle fa-lg"></i> &nbsp;Need Help ? </a></li>`);
    }

    $('#form_condition1').change(function(){
        let parentEle = $(this).parent();
        if ($(this).val() === "Other") {
            var html = '<textarea id="form-condition-add" name="form[conditionAdd]" required="required" rows="3" class="wk-input-medium form-control mt-2"></textarea>';
            parentEle.after(html);
        } else {
            $("#form-condition-add").remove();
        }
    })

    $('#form_condition4').change(function(){
        let parentEle = $(this).parent();
        if ($(this).val() === "Other") {
            var html = '<textarea id="form-condition-fourth-add" name="form[conditionFourthAdd]" required="required" rows="3" class="wk-input-medium form-control mt-2"></textarea>';
            parentEle.after(html);
        } else {
            $("#form-condition-fourth-add").remove();
        }
    })

    $('.wk-delete-row-js').on('click', function(e) {
        e.preventDefault();
        const $link = $(e.currentTarget);
        swal({
                title: wkMpTrans.delete_item,
                text: wkMpTrans.are_you_sure,
                icon: "warning",
                buttons: [wkMpTrans.cancel_btn, wkMpTrans.ok_btn],
                dangerMode: true,
            })
            .then((willDelete) => {
                if (willDelete) {
                    _deleteData($link);
                }
            });
    });

    function _deleteData($link) {
        $link.addClass('text-danger');
        $link.find('.fa')
            .removeClass('fa-trash')
            .addClass('fa-spinner')
            .addClass('fa-spin');
        const deleteUrl = $link.data('url');
        const $row = $link.closest('tr');
        $.ajax({
            url: deleteUrl,
            method: 'DELETE',
            beforeSend: function() {
                $(".wk-overlay").show();
            },
            success: function(data) {
                if (data.code == 200) {
                    Pam.renderMessages([{
                        'type': 'success',
                        'message': 'Item deleted Successfully!'
                    }]);
                    $row.fadeOut('normal', () => {
                        $row.remove();
                    });
                } else {
                    if (data.notification) {
                        for (var k in data.notification) {
                            Pam.renderMessages([{
                                'type': data.notification[k].type,
                                'message': data.notification[k].message
                            }]);
                        }
                    }

                    $link.find('.fa')
                        .addClass('fa-trash')
                        .removeClass('fa-spinner')
                        .removeClass('fa-spin');
                    $link.removeClass('wk-delete-row-js');
                }
            },
            complete: function() {
                $(".wk-overlay").hide();
                window.location.reload();
            }
        })
    }

});

function sortAlphabetically(data) {
    return data.sort();
}

// New UI
$(document).ready(function() {
    if (localStorage.getItem('dontShowPopup')) {
        $('#new_theme_settings').remove();
    }
    const targetTimestamp = $("#app-nav-time").data('time');

    function updateTimer() {
        const currentTimestamp = Math.floor(Date.now() / 1000);
        const remainingTime = targetTimestamp - currentTimestamp;
        if (remainingTime <= 0) {
            $('#app-nav-sub-expire').html('Timer Expired!');
        } else {
            const years = Math.floor(remainingTime / (365 * 24 * 3600));
            const months = Math.floor((remainingTime % (365 * 24 * 3600)) / (30 * 24 * 3600));
            const days = Math.floor((remainingTime % (30 * 24 * 3600)) / (24 * 3600));
            const hours = Math.floor((remainingTime % (24 * 3600)) / 3600);
            const minutes = Math.floor((remainingTime % 3600) / 60);
            const seconds = remainingTime % 60;
            // Display the remaining time
            var ExpireTime = years > 0 ? years + 'Y &nbsp;' : '';
            ExpireTime += months > 0 ? months + 'M &nbsp;' : '';
            ExpireTime += days > 0 ? days + 'D &nbsp;' : '';
            ExpireTime += hours > 0 ? hours + 'H &nbsp;' : '';
            ExpireTime += minutes > 0 ? minutes + 'M &nbsp;' : '';
            ExpireTime += seconds > 0 ? seconds + 'S &nbsp;' : '';
            $('#app-nav-sub-expire').html(`<b>${ExpireTime}</b>`);
        }
    }
    setInterval(updateTimer, 1000);
    wkNewUI.init();
})

var wkNewUI = {
    'init': function() {

        $('#new-ui-close').on('click', function() {
            setTimeout(function() {
                $('.wk-overlay').hide();
            }, 200);
            // Event handler for the close button
            var dontShowAgain = $('#dontShowAgainCheckbox').prop('checked');
            // If the checkbox is checked, set a localStorage item to remember the choice
            if (dontShowAgain) {
                localStorage.setItem('dontShowPopup', true);
            }
        });

        $('#new-ui-save').on('click', function() {
            $('.wk-overlay').show();
            localStorage.removeItem('dontShowPopup');
            if ($(this).data('theme').trim()) {
                var saveUrl = $(this).data('href');
                $.ajax({
                    url: saveUrl + '?theme=' + $(this).data('theme'),
                    // dataType: 'json',
                    success: function(data) {
                        if (data == 'true') {
                            swal({
                                title: wkMpTrans.wix_wixmp_update_installed,
                                icon: "success",
                                timer: 2000
                            });
                            location.reload();
                        } else {
                            swal({
                                title: wkMpTrans.wix_wixmp_update_failed,
                                icon: "error",
                                timer: 2000
                            });
                        }
                    },
                    error: function (error) {
                        console.error(error.statusText);
                    },
                    complete: function() {
                        // write code here
                        $('.wk-overlay').hide();
                    }
                });
            }
        });

        $('#back-to-old').on('click', function() {
            swal({
                title: wkMpTrans.are_you_sure,
                text: wkMpTrans.wix_wixmp_back_to_old_version,
                icon: "warning",
                buttons: [wkMpTrans.cancel_btn, wkMpTrans.ok_btn],
            }) .then((allow) => {
                console.log(allow);
                if (allow) {
                    $('.wk-overlay').show();
                    var saveUrl = $(this).data('href');
                    $.ajax({
                        url: saveUrl + '?theme=' + $(this).data('theme'),
                        // dataType: 'json',
                        success: function(data) {
                            if (data == 'true') {
                                swal({
                                    title: wkMpTrans.wix_wixmp_downgraded_old_version,
                                    icon: "success",
                                    timer: 2000
                                });
                                location.reload();
                            } else {
                                swal({
                                    title: wkMpTrans.wix_wixmp_update_failed,
                                    icon: "error",
                                    timer: 2000
                                });
                            }
                        },
                        error: function (error) {
                            console.error(error.statusText);
                        },
                    });
                }
                setTimeout(function() {
                    $('.wk-overlay').hide();
                }, 2000);
            })

        });
    }
};