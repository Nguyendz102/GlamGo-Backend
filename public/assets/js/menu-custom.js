$(document).ready(function () {
    $(window).on("scroll", function () {
        let mainHeader = $('header.main-header');
        let logoMain = $('.main-logo img');
        if (document.documentElement.scrollTop > 111) {
            $(mainHeader).addClass('fixed-header');
            $(logoMain).addClass('anm-fix-logo');
        }else {
            $(mainHeader).removeClass('fixed-header');
            $(logoMain).removeClass('anm-fix-logo');

        }
    });
});
