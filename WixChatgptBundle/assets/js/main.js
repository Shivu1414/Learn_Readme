"use strict";

import swal from "sweetalert";

$(document).ready(function () {
    let productData = "";
    $("#custom-Description").hide();
    $('.wix-seo-list').hide();
    
    $("#product-name").keyup(function () {
        var name = $("#product-name").val();
        productData = "";
        if (name.length <= 2) {
            document.getElementById("productLiveSearch").innerHTML = "";
            // document.getElementById("productLiveSearch").style.border = "0px";
            $("#productLiveSearch").attr('hidden',true)
            return;
        } else {
            $.ajax({
                type: "GET",
                url: $(this).attr('data-search-url') + "?search=" + name,
                dataType : 'json',
                beforeSend: function() {
                    searchLoader('#product-name');
                },
                success: function (result) {
                    $("#productLiveSearch").removeAttr("hidden");
                    if(result.data.length > 0) {
                        $("#productLiveSearch").empty();
                        result.data.forEach(productDetails);
                        document.getElementById("productLiveSearch").innerHTML = productData;
                    } else {
                        $("#productLiveSearch").css("height", "auto");
                        $("#productLiveSearch").html(' <li class="list-group-item"> Product not found </li>');
                    }
                    removeSearchLoader('#product-name');
                },
            });
        }
    });

    function productDetails(item, index) {
        productData += "<a href='#' id='product-data-value' role='option' class='list-group-item list-group-item-action product-data-value' search-product-id=" + item.productId + ">" + item.productName + "</a>"; 
    }

    $(document).on("click", ".product-data-value", function () {
        $(this).attr("search-product-id");
        $("#product-name").val($(this).text())
        $("#product-name").attr("data-product-id", $(this).attr('search-product-id'));
        $("#productLiveSearch").attr("hidden", true);
    });

    $("#description-type").change(function(){
        let descType = $(this).val();
        if (descType !== "undefined" && descType === "custom") {
            $("#custom-Description").show();
        } else {
            $("#custom-Description").hide();
        }
    })

    $("#gererate_description_button").click(function() {

        let product = $("#product-name").attr('data-product-id');
        let productName = $("#product-name").val() ? $("#product-name").val() : "";
        let additionalInfo = $("#additional-info").val() ? $("#additional-info").val() : "";
        let wordNumber = $("#word-limit").val() ? $("#word-limit").val() : "";
        let descType = $("#description-type").val() ? $("#description-type").val() : "";
        let customDesc = $("#custom-Description").val() ? $("#custom-Description").val() : "";
        
        if (typeof product === "undefined") {
            swal({
                title: "Please Select Product",
                icon: "warning",
            })
            return false;
        }
        if ($.trim(wordNumber)  === "" || typeof wordNumber == "undefined") {
            swal({
                title: "Please Enter the number of words",
                icon: "warning",
            })
            return false;
        }
        $(this).attr('disabled','disabled');
        $.ajax({
            type: "POST",
            url: "generate-product-description",
            data: {
                product : productName,
                additionalInfo : additionalInfo,
                totalWord : wordNumber,
                descType : descType,
                customDesc : customDesc 
            },
            dataType : 'json',
            beforeSend: function() {
                searchLoader('#gererate_description_button');
            },
            success: function (result) {
                if (result.status == "SUCCESS") { 
                    $("#product-description").val(result.content)
                } else {
                    swal({
                        title: "Please Check your details",
                        text: "error message (" + result.message + ") ",
                        icon: "error",
                    })
                }
                removeSearchLoader('#gererate_description_button');
                $("#gererate_description_button").attr('disabled',false);
            },
            error: function(xhr, textStatus, error){
                swal({
                    title: "Please send valid request",
                    icon: "error",
                })
                $("#gererate_description_button").attr('disabled',false);
                removeSearchLoader('#gererate_description_button');
            }

        })

    })

    $("#chat-gpt-form").submit(function(event) {
        event.preventDefault();
        let formData = $(this).serialize();
        let additionalData = {
            productId: $("#product-name").attr('data-product-id'),
        };
        let requestData = formData + "&" + $.param(additionalData);
        let productName = $("input[name='product-name']").val();
        let productId = $("input[name='product-name']").attr('data-product-id');
        let description = $("textarea[name='description']").val();
        if ($.trim(productName)  === "" || typeof productId == "undefined") {
            swal({
                title: "Please Select Product",
                icon: "warning",
            })
            return false;
        }
        if ($.trim(description) === "") {
            swal({
                title: "Description Empty",
                icon: "warning",
            })
          return false;
        }

        $.ajax({
            url: "",
            type: "POST",
            data: requestData,
            dataType: "json",
            success: function(result) {
                $('.wk-overlay').hide();
                if (result.status == "SUCCESS") {
                    swal({
                        title: result.message,
                        icon: "success",
                    })
                } else {
                    swal({
                        title: result.message,
                        icon: "error",
                    })
                }
            },
            error: function(xhr, status, error) {
                $('.wk-overlay').hide();
                swal({
                    title: "please try again",
                    icon: "error",
                })
            }
        });
    })

    // SEO PAGE
    $("#seo_switch").click(function() {
        var switchValue = this.checked ? "ON" : "OFF";
        if ( switchValue === "OFF") {
            $("#seo-generate-button").hide();
            $('.wix-seo-list').show();
        } else {
            $("#seo-generate-button").show();
            $('.wix-seo-list').hide();
        }
    })

    $('#gererate_seo_button').click(function() {

        let productId = $("#product-name").attr('data-product-id');
        let formData = $("#chat-gpt-seo-form").serialize()

        if (typeof productId === "undefined") {
            swal({
                title: "Please Select Product",
                icon: "warning",
            })
            return false;
        }

        var titleCheck = $("input[name='titleCheck']").is(":checked") ? "ON" : "OFF";
        var metaCheck = $("input[name='metaCheck']").is(":checked") ? "ON" : "OFF";
        if ( titleCheck === "OFF" && metaCheck === "OFF") {
            swal({
                title: "Please select Tag",
                icon: "warning",
            })
            return false;
        }

        let additionalData = {
            productId: productId,
        };
        let requestData = formData + "&" + $.param(additionalData);
        $(this).attr('disabled','disabled');

        $.ajax({
            type: "POST",
            url: $(this).attr("data-seo-url"),
            data: requestData,
            dataType : 'json',
            beforeSend: function() {
                searchLoader("#gererate_seo_button");
            },
            success: function (result) {
                if (result.status == "SUCCESS") {
                    swal({
                        title: result.message,
                        icon: "success",
                    })
                } else {
                    swal({
                        title: "Please Check your details",
                        text: "error code (" + result.message + ") ",
                        icon: "error",
                    })
                }
                removeSearchLoader("#gererate_seo_button");
                $("#gererate_seo_button").attr('disabled',false);
            },
            error: function(xhr, textStatus, error){
                swal({
                    title: "Please send valid request",
                    icon: "error",
                })
                $("#gererate_seo_button").attr('disabled',false);
                removeSearchLoader("#gererate_seo_button");
            }
        })
    })

    $("#chat-gpt-seo-form").submit(function(event) {
        event.preventDefault();
        let formData = $(this).serialize();
        let additionalData = {
            productId: $("#product-name").attr('data-product-id'),
        };
        let requestData = formData + "&" + $.param(additionalData);
        let productName = $("input[name='product-name']").val();
        let productId = $("input[name='product-name']").attr('data-product-id');

        if ($.trim(productName)  === "" || typeof productId == "undefined") {
            swal({
                title: "Please Select Product",
                icon: "warning",
            })
            return false;
        }

        var titleCheck = $("input[name='titleCheck']").is(":checked") ? "ON" : "OFF";
        var metaCheck = $("input[name='metaCheck']").is(":checked") ? "ON" : "OFF";
        console.log("dafd")
        if ( titleCheck === "OFF" && metaCheck === "OFF") {
            swal({
                title: "Please select tag",
                icon: "warning",
            })
            return false;
        }

        $.ajax({
            url: "",
            type: "POST",
            data: requestData,
            dataType: "json",
            success: function(result) {
                $('.wk-overlay').hide();
                if (result.status == "SUCCESS") {
                    swal({
                        title: result.message,
                        icon: "success",
                    })
                } else {
                    swal({
                        title: result.message,
                        icon: "error",
                    })
                }
            },
            error: function(xhr, status, error) {
                $('.wk-overlay').hide();
                swal({
                    title: "please try again",
                    icon: "error",
                })
            }
        });
    })

    $(".number-field").keypress(function(evt) {
        var charCode = (evt.which) ? evt.which : evt.keyCode;
        if (charCode > 31 && (charCode < 48 || charCode > 57))
            return false;
        
        let txt = evt.currentTarget;
        txt.value = txt.value.indexOf(".") >= 0 ? txt.value.slice(0, txt.value.indexOf(".") + 2) : txt.value;
        if ( txt.name === "wordOfMeta" && txt.value + evt.key > 500 || txt.id === "word-limit" && txt.value + evt.key > 8000)
            return false;

        return true;
    });

});

//  Common 

function searchLoader(inputSelector) {
    const $parent = $(inputSelector).closest('.loader-group');
    if (!$parent.find('.loader').length) {
        const loader = '<div class="loader"></div>';
        $parent.append(loader);
    }
}

function removeSearchLoader(inputSelector) {

    $(inputSelector).closest('.loader-group').find('.loader').remove();

}
