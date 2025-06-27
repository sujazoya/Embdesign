jQuery(document).ready(function($) {
    // Highlight current category
    var currentCategory = $('.acd-list-item a[href="' + window.location.href + '"]');
    if (currentCategory.length) {
        $('.acd-list-item').removeClass('active');
        currentCategory.parent().addClass('active');
    } else {
        // Highlight "All Categories" by default if no category is selected
        $('.acd-list-item.all-categories').addClass('active');
    }

    $(".acd-list-item a").on("click", function(e) {
        e.preventDefault();
        $(".acd-list-item").removeClass("active");
        $(this).parent().addClass("active");
        window.location.href = $(this).attr("href");
    });
});