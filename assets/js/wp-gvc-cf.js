(function ($) {
    var wp_gvc = jQuery.noConflict();
    wp_gvc(document).on("change", ".vcard-select", function () {
        if (wp_gvc(this).is(":checked")) {
            wp_gvc(this).closest("tr").find(".vcard-select-option[name='" + wp_gvc(this).attr('name') + "-select" + "']").removeAttr("disabled").addClass("active");
        } else {
            wp_gvc(this).closest("tr").find(".vcard-select-option[name='" + wp_gvc(this).attr('name') + "-select" + "']").val("").attr("disabled", "disabled").removeClass("active");
            wp_gvc(this).closest("tr").find(".vcard-select-option[name='" + wp_gvc(this).attr('name') + "-select" + "']").closest("td").find(".error").remove();
        }
    });

    wp_gvc(document).on("submit", ".vcard-form", function (e) {
        wp_gvc(".vcard-form").find(".error").remove();
        if (wp_gvc(document).find("[name='is_send_vcard_enable']:checked").val() == "1") {
            var wp_gvccf_vcard_form_error = false;
            wp_gvc(".vcard-form").find(".vcard-select-option.active").each(function () {
                if (wp_gvc(this).val() == "") {
                    wp_gvccf_vcard_form_error = true;
                    wp_gvc("<div class='error'>Please select option</div>").insertAfter(wp_gvc(this));
                }
            });
            if (wp_gvccf_vcard_form_error) {
                e.preventDefault();
            }
        }
    });
    wp_gvc(document).on("click", ".wp-gvc-cf-sec .nav-tab", function () {
        wp_gvc(".nav-tab").removeClass("nav-tab-active");
        wp_gvc(this).addClass("nav-tab-active");
        wp_gvc("[data-tab-view]").removeClass("active");
        wp_gvc("[data-tab-view='" + wp_gvc(this).attr("data-tab") + "']").addClass("active");
    });
    wp_gvc(document).on("change", ".wp_gvccf_cf7_select", function () {
        if (wp_gvc(this).val() != "") {
            window.location = wp_gvc(this).attr("data-href") + "&wp-gvc-cf=" + wp_gvc(this).val();
        }
    });


    wp_gvc(document).on("change", ".wp-gvc-cf-table .vcard-select-option", function () {
        if (wp_gvc(this).val() != '') {
            wp_gvc(this).addClass('wp-gvc-cf-selected-bg');
        } else {
            wp_gvc(this).removeClass('wp-gvc-cf-selected-bg');
        }
    });

    wp_gvc(window).resize(function () {
        var height = '';
        setTimeout(function () {
            wp_gvc('.wp-gvc-cf-sidebar-box').each(function () {
                if (wp_gvc(this).height() >= height) {
                    height = wp_gvc(this).height();
                }
            });
            wp_gvc('.wp-gvc-cf-sidebar-box').css('min-height', height + 'px');
        }, 400);


        var window_height = wp_gvc(window).height();
        wp_gvc('.wp-gvc-cf-modal-table-sec').css({'max-height': ((window_height * 80) / 100)});
    }).trigger('resize');

    /*Modal Open*/
    wp_gvc(document).on('click', '.wp-gvc-cf-details', function () {
        var window_height = wp_gvc(window).height();
        wp_gvc('.wp-gvc-cf-modal-table-sec').css({'max-height': ((window_height * 80) / 100)});
        var table_data = JSON.parse(wp_gvc(this).attr('data-details'));
        var table_details = '';
        wp_gvc.each(table_data, function (index, value) {
            table_details += '<tr><th align="left">' + index + '</th><td align="left">' + value + '</td></tr>';
        });
        wp_gvc('.wp-gvc-cf-modal-table').html(table_details);
        wp_gvc('.wp-gvc-cf-modal').fadeIn();
    });
    /*Modal Close*/
    wp_gvc(document).on('click', ".wp-gvc-cf-close", function () {
        wp_gvc('.wp-gvc-cf-modal').fadeOut();
    });

    /*Delete Confirmation*/
    wp_gvc(document).on('click', '.wp-gvc-cf-delete', function () {
        if (confirm('Are you sure to delete this entry?')) {
            window.location = wp_gvc(this).attr('data-href');
        }
    });
})(jQuery);