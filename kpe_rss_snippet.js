jQuery(document).ready(function(){
    
    // Dynamically populate category or tag select options
    jQuery('#feed').change(function(){
        let myfeed = jQuery(this).val();
        jQuery('#category_tag').empty();
        if (myfeed !== 'Global') {
            jQuery.ajax({
                url: myAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'fetch_records', // This corresponds to the WordPress action hook
                    xfeed: myfeed,
                },
                success: function (data) {
                    console.log(data); // Handle the response (e.g., display records)
                    var obj = jQuery.parseJSON (data);
                    var obj_len = obj.length;
                    for(x=0;x<obj_len;x++) {
                        var opt = document.createElement("OPTION");
                        opt.value = obj[x].term_id;
                        opt.text = obj[x].name;

                        if (jQuery('#cat_tag').val() !== '') {
                            if (jQuery('#cat_tag').val() == obj[x].term_id) {
                                opt.selected = 'selected';
                            }
                        }

                        jQuery('#category_tag').append(opt);
                    }
                },
            });
        } else {
            var xopt = document.createElement("OPTION");
            jQuery('#category_tag').append(xopt);
        }
    });

    // Wait 100 milliseconds after the DOM is ready and dynamically update the feed type and taxonomy in Edit page
    setTimeout(
        function() {
            if (jQuery('#old_feed').val() !== '') {
                jQuery("#feed").val(jQuery('#old_feed').val()).trigger("change");
            }
        }, 100);
    
});