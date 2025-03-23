$(document).ready(function(){
    $('.add-slider-product-list').slick({
        slidesToShow: 4,
        slidesToScroll: 1,
        autoplay: false,
        autoplaySpeed: 2000,    
        dots: true,
        arrows: false,
        centerMode: true, 
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: 3, // Hiển thị 3 sản phẩm trên tablet
                }
            },
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 2, // Hiển thị 2 sản phẩm trên điện thoại
                }
            },
            {
                breakpoint: 480,
                settings: {
                    slidesToShow: 2, // Nếu màn hình quá nhỏ, chỉ hiển thị 1 sản phẩm
                }
            }
        ]
    });
});


$(document).ready(function(){
    $('.add-slider-product-list-1').slick({
        slidesToShow: 6,
        slidesToScroll: 1,
        autoplay: false,
        autoplaySpeed: 2000,    
        dots: true,
        arrows: false,
        centerMode: true, 
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: 3, // Hiển thị 3 sản phẩm trên tablet
                }
            },
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 2, // Hiển thị 2 sản phẩm trên điện thoại
                }
            },
            {
                breakpoint: 480,
                settings: {
                    slidesToShow: 2, // Nếu màn hình quá nhỏ, chỉ hiển thị 1 sản phẩm
                }
            }
        ]
    });
});
