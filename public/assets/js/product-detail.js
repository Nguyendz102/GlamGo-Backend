$(document).ready(function () {
    $("#selectors-img").slick({
        dots: false,
        infinite: true,
        speed: 300,
        slidesToShow: 4,
        slidesToScroll: 4,
        // centerMode: true,
        // variableWidth: true,
        prevArrow:
            '<button type="button" class="slick-prev"><svg xmlns="http://www.w3.org/2000/svg" height="18px" viewBox="0 -960 960 960" width="24px" fill="#000"><path d="M560-240 320-480l240-240 56 56-184 184 184 184-56 56Z"/></svg></button>',
        nextArrow:
            '<button type="button" class="slick-next"><svg xmlns="http://www.w3.org/2000/svg" height="18px" viewBox="0 -960 960 960" width="24px" fill="#000"><path d="M504-480 320-664l56-56 240 240-240 240-56-56 184-184Z"/></svg></button>',
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: 4,
                    slidesToScroll: 4,
                    infinite: true,
                    dots: false,
                },
            },
            {
                breakpoint: 600,
                settings: {
                    slidesToShow: 4,
                    slidesToScroll: 4,
                    infinite: true,
                    dots: false,
                },
            },
            {
                breakpoint: 480,
                settings: {
                    slidesToShow: 4,
                    slidesToScroll: 4,
                    infinite: true,
                    dots: false,
                },
            },
        ],
    });

    $("#selectors-img").on("click", ".slick-slide", function () {
        $("#selectors-img .slick-slide").css("border", "none");
        $(this).css("border", "1px solid #dbac5a");
        $(this).css("border-radius", "10px");
    });

    $(".product_config_in_style").each(function () {
        const $attributeGroup = $(this);

        $attributeGroup.on("click", ".item-flex", function () {
            $attributeGroup.find(".item-flex").css("border", "1px solid rgba(245, 245, 245, 1)");
            $attributeGroup.find(".item-flex .is_choose").css("display", "none");
            $attributeGroup.find(".item-flex").removeClass("selected");

            $(this).css("border", "2px solid rgba(113,172,171,1)");
            $(this).find(".is_choose").css("display", "flex");
            $(this).addClass("selected");
        });
    });

    //code cũ
    /*
    $(".config_in_style-1 .item-flex").click(function () {
        // Xóa trạng thái chọn trước đó
        $(".config_in_style-1 .item-flex").css(
            "border",
            "1px solid rgba(245, 245, 245, 1)"
        );
        $(".config_in_style-1 .item-flex .is_choose").css("display", "none");

        // Thêm trạng thái chọn cho item được click
        $(this).css("border", "2px solid rgba(113,172,171,1)");
        $(this).find(".is_choose").css("display", "flex");
    });

    $(".config_in_style-2 .item-flex").click(function () {
        // Xóa trạng thái chọn trước đó
        $(".config_in_style-2 .item-flex").css(
            "border",
            "1px solid rgba(245, 245, 245, 1)"
        );
        $(".config_in_style-2 .item-flex .is_choose").css("display", "none");

        // Thêm trạng thái chọn cho item được click
        $(this).css("border", "2px solid rgba(113,172,171,1)");
        $(this).find(".is_choose").css("display", "flex");
    });
    */
});