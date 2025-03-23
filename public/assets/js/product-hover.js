$(document).ready(function () {
    $(".hover-product").each(function () {
        let originalImg = $(this).find(".hover-product-img img").attr("src");
        $(this).attr("data-original-img", originalImg);
    });

    $(".hover-product").hover(
        function () {
            let newImg = $(this).attr("data-img");
            $(this).find(".hover-product-img img").attr("src", newImg)
                   .addClass("img-blink"); // Thêm hiệu ứng nhấp nháy
            $(this).find(".advertising-seo").css("display", "none");
        },
        function () {
            let originalImg = $(this).attr("data-original-img");
            $(this).find(".hover-product-img img").attr("src", originalImg)
                   .removeClass("img-blink"); // Xóa hiệu ứng khi rời chuột
            $(this).find(".advertising-seo").css("display", "flex");
        }
    );
});
