'use strict';

import 'common/ajax_jobs.js';

$(function () {
    
    var wixetsy = {

        init : function() {},

        syncProducts : function(obj) {
            
            obj.ajaxJobs({
                ajaxUrl: obj.attr('data-href'),
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
                }
            });
        },

        importProducts : function(obj) {

        },

        categoryMapping : function (obj) {
            
            let wixSelectedCateId = $("#wixCategory option:selected").val();
            let etsySelectedCateId = $("#etsyCategory option:selected").val();

            let wixSelectedCateText = $("#wixCategory option:selected").text();
            let etsySelectedCateText = $("#etsyCategory option:selected").text();

            let table = '\
                <table class="table table-responsive">\
                    <thead>\
                        <tr>\
                            <th width="15%">Wix Category</th>\
                            <th width="10%">Etsy Category</th>\
                            <th width="5%"></th>\
                        </tr>\
                    </thead>\
                    <tbody id="wixEtsyCateMapTBody">\
                    <tbody>\
                </table>';

            let tableRow = '\
                <tr class = "wixEtsyCateMapRow">\
                    <td>'+ wixSelectedCateText +'</td>\
                    <td>'+ etsySelectedCateText +'</td>\
                    <td><div class = "d-flex justify-content-center" style = "margin-top : -28px"><font size = "5"><span class="fa fa-times-circle wk-pointer wixEtsyCateMapRemove" style="padding-top: 30px;"><span></font></div></td>\
                    <input type = "hidden" name = "wixEtsyCategoryMapping[wixCategory][]" value = "'+ wixSelectedCateId +'">\
                    <input type = "hidden" name = "wixEtsyCategoryMapping[etsyCategory][]" value = "'+ etsySelectedCateId +'">\
                </tr>\
            ';
            if (wixSelectedCateId && etsySelectedCateId) {
                if (!$("#wixEtsyCateMapTBody").length) {
                    $("#wixEtsyMapTable").html(table);
                }

                let bodyvalue = $('#wixEtsyCateMapTBody').html();
                if (bodyvalue.includes(wixSelectedCateId) && bodyvalue.includes(etsySelectedCateId)) {
                    swal({
                        title: 'Already Selected',
                        text: 'Category Already Selected',
                        icon: "error",
                        button: false,
                    })
                } else {
                    $("#wixEtsyCateMapTBody").append(tableRow);
                }   
               
            } else {

                swal({
                    title: 'No Category selected',
                    text: 'Select atleast one category to Map',
                    icon: "error",
                    button: false,
                    timer: 7000
                });
            }
        },

        categoryMappingRemove : function (obj) {
            
            obj.closest(".wixEtsyCateMapRow").remove();
        }
    };


    $("#etsy-import-products").ajaxJobs({
        ajaxUrl: $("#etsy-import-products").attr('data-href'),
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

            const productIds = document.querySelectorAll('input[name="product_ids[]"]:checked');

            const selectedProductIds = [];
            
            productIds.forEach(function (productId) {
                selectedProductIds.push(productId.value);
            });
            if(selectedProductIds.length == 0) {
                swal({
                    title: "Warning?",
                    text: "Select at least one product to Import!",
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                });
                return false;
            } else {
                el.formData.append("productIds", selectedProductIds);
                return true;
            }
            // el.formData.append("productIds", selectedProductIds);
        },
        onBeforeModalDisplay: function (element, modal) {
            //$("#orderSyncModal").modal('hide');
        },
    });

    $("#wix-import-orders").ajaxJobs({
        ajaxUrl: $("#wix-import-orders").attr('data-href'),
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

            const productIds = document.querySelectorAll('input[name="order_ids[]"]:checked');

            const selectedProductIds = [];
            
            productIds.forEach(function (productId) {
                selectedProductIds.push(productId.value);
            });
            if (selectedProductIds.length == 0) {
                swal({
                    title: "Warning?",
                    text: "Select at least one order to Import!",
                    icon: "warning",
                    button: true,
                    dangerMode: true
                })
                return false;
            } else {
                el.formData.append("orderIds", selectedProductIds);
                return true;
            }
        },
        onBeforeModalDisplay: function (element, modal) {
            //$("#orderSyncModal").modal('hide');
        },
    });
    $("#etsy-sync-order").ajaxJobs({
        ajaxUrl: $("#etsy-sync-order").attr('data-href'),
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
            var toDate = $('input[name="order[sync][to_date]"]').val();
            var fromDate = $('input[name="order[sync][from_date]"]').val();
            var shipmentStatus = $('select[name="order[sync][shipment_status]"]').val();
            var paymentStatus = $('select[name="order[sync][payment_status]"]').val();
            var orderIds = $('textarea[name="order[sync][receipt_ids]"]').val();

            //el.formData.append("toDate", toDate);
            //el.formData.append("fromDate", fromDate);
            el.formData.append("shipmentStatus", shipmentStatus);
            el.formData.append("paymentStatus", paymentStatus);
            el.formData.append("orderIds", orderIds);
        },
        onBeforeModalDisplay: function (element, modal) {
            $("#etsyOrderSyncModal").modal('hide');
        },
    });

    $('body').on("keypress", '#syncOrderIds', function (e) {
        var key = e.which;

        if ((key < 48 || key > 57) && key != 44) {
            e.preventDefault();
        }

    });

    $('body').on("click", '.select2-selection__rendered', function (e) {

        if(document.getElementsByClassName('esty-subcategory').length > 0){
            var containerPosition = $('.esty-subcategory').next('.select2-container').offset().top;
            var dropdownPosition = $('.select2-dropdown').offset().top;
            if (dropdownPosition > containerPosition) {
                $('.select2-container--default .select2-search--dropdown').css({"position": "absolute", "bottom": "auto" , "top" :0,"width":'100%'});
            } else {
                $('.select2-container--default .select2-search--dropdown').css({"position": "absolute", "top": "auto" , "bottom" :0,"width":'100%'});
            }
        }
        
    });

    $(document).on('scroll', function() {
        if(document.getElementsByClassName('esty-subcategory').length > 0){
            var containerPosition = $('.esty-subcategory').next('.select2-container').offset().top;
            if($('.select2-dropdown').offset() != undefined){
                var dropdownPosition = $('.select2-dropdown').offset().top;
                if (dropdownPosition > containerPosition) {
                    $('.select2-container--default .select2-search--dropdown').css({"position": "absolute", "bottom": "auto" , "top" :0,"width":'100%'});
                } else {
                    $('.select2-container--default .select2-search--dropdown').css({"position": "absolute", "top": "auto" , "bottom" :0,"width":'100%'});
                }
            }
        }
    });


    $("[data-toggle='ajaxjobs']").each(function (i, v) {
        wixetsy.syncProducts($(this));
    });

    $('body').on("click", '#wixEtsyCateMapAdd', function (e) {
        wixetsy.categoryMapping($(this));
    });

    $('body').on("click", '.wixEtsyCateMapRemove', function (e) {
        wixetsy.categoryMappingRemove($(this));
    });
    // $('body').on('click', '$etsy-import-products', function(e) {
    //     wixetsy.SyncProducttoEtsy($(this))
    // });
    $('body').on("keypress", '#syncReceiptIds', function (e) {
        var key = e.which;

        if ((key < 48 || key > 57) && key != 44) {
            e.preventDefault();
        }

    });

});

$(document).ready(function() {
    $('#wixCategory').select2();
    $('#etsyCategory').select2();
    $('.esty-subcategory').select2({});
})
