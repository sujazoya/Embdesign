jQuery(document).ready(function($) {
    $('input[name="dst"]').on('change', function () {
        var input = this;
        if (!input.files.length) return;

        var formData = new FormData();
        formData.append('action', 'wcsu_parse_dst');
        formData.append('dst_file', input.files[0]);

        var $spinner = $('<span class="wcsu-loading">Parsing DST...</span>');
        $(input).after($spinner);

        $.ajax({
            url: wcsu_ajax.ajaxurl,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function (res) {
                if (res.success) {
                    const d = res.data;
                    $('input[name="product_title"]').val(d.design_name);
                    $('input[name="stitches"]').val(d.stitches);
                   // $('input[name="area"]').val(d.area);
                    $('input[name="height"]').val(d.height);
                    $('input[name="width"]').val(d.width);
                   // $('input[name="formats"]').val(d.formats);
                  //  $('input[name="needle"]').val(d.needle);
                } else {
                    alert("DST Parsing Failed: " + res.data);
                }
            },
            error: function () {
                alert("AJAX error parsing DST file.");
            },
            complete: function () {
                $spinner.remove();
            }
        });
    });
});
