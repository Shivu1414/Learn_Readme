
$(document).ready(function () {
	
	// $('#SampleProgressBarModalCenter').on('show.bs.modal', function (event) {
    //     var $modal = $(this);
    //     $modal.find('.sample-div').html("<iframe src='/en/app/marketplace/n6xxftc956/admin/progress_bar' width='100%' frameBorder='0'></iframe>");
    // })

    // check checkbox
    $('#productWixSellerAssignModal').on('show.bs.modal', function (event) {
        var $button = $(event.relatedTarget); 
        //var $modal = $(this);
        var product_id = $button.data('product-id');
        if (product_id) {
            //var product_name = $button.data('product-name');
            var productsForm = jQuery(this).closest('form');

            //$modal.find('.wk-product-name').html(product_name);
            // $modal.find('input[name=product_id]').val(product_id);
            productsForm.find('input[name = "product_ids[]"][value = "' + product_id + '"]').prop('checked', true);
        }
        
    });
    // uncheck product ids checkbox
    $('#productWixSellerAssignModal').on('hide.bs.modal', function (event) {    
        var productsForm = $(this).closest('form');    
        productsForm.find('input[name="product_ids[]"],[name="check"]').prop('checked', false);
    });
    // check and assign seller 
    $("#assign-wix-seller-submit").on("click",function(e){
        var returnStatus = true;
        // check if product ids selected
        if ($("input[name='product_ids[]']:checked").length <= 0) {
            // select atleast one row to perform batch action 
            swal({
                title: 'No product Selected',
                text: 'Select atleast one product to assign seller',
                icon: "warning",
            });
            returnStatus = false;
        } else if (!$(this.form.seller).val() || $(this.form.seller).val() == '') {
            // select atleast one row to perform batch action 
            swal({
                title: 'No Seller Selected',
                text: 'Select seller to assign to the product(s)',
                icon: "warning",
            });
            returnStatus = false;
        } else {
            // $(this.form.assign_seller).val(true);
            // $(this.form).submit();
            returnStatus = true;
        }
        return returnStatus;
    });
  
    $('#multiProductSellerAssignModal').on('show.bs.modal', function (event) {
        var $button = $(event.relatedTarget);
        var product_names = [];
        var product_ids = [];
        $("input[name='product_ids[]']").each( function () {
            if ($(this).is(':checked') == true) {
                product_names.push($(this).data('product-name'));
                product_ids.push($(this).val());
            }
        });
        var $modal = $(this);
        var product_names_str = product_names.join('<br>');
        var product_ids_str = product_ids.join(',');
        if(product_ids.length == 0){
            product_names_str = $button.data('lang-no-data-selected');
            $modal.find('.wk-submit-form').hide();
        }else{
            $modal.find('.wk-submit-form').show();
        }
        $modal.find('.wk-product-name').html(product_names_str);
        $modal.find('.wk-product-ids').val(product_ids_str);
    });

    $('#multiProductSellerDeleteModal').on('show.bs.modal', function (event) {
        var $button = $(event.relatedTarget);
        var product_names = [];
        var product_ids = [];
        var platform_product_ids = [];
        $("input[name='product_ids[]']").each( function () {
            if ($(this).is(':checked') == true) {
                product_names.push($(this).data('product-name'));
                product_ids.push($(this).val());
                platform_product_ids.push($(this).data('platform-product-id'));
            }
        });
        var $modal = $(this);
        var product_names_str = product_names.join('<br>');
        var product_ids_str = product_ids.join(',');
        var platform_product_ids_str = platform_product_ids.join(',');
        if(product_ids.length == 0){
            product_names_str = $button.data('lang-no-data-selected');
            $modal.find('.wk-submit-form').hide();
        }else{
            $modal.find('.wk-submit-form').show();
        }
        $modal.find('.wk-product-name').html(product_names_str);
        $modal.find('.wk-product-ids').val(product_ids_str);
        $modal.find('.wk-platform-product-ids').val(platform_product_ids_str);
    });    
    

});


