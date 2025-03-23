$(document).ready(function () {
    let promotionBtn = $(".close-promotion-btn");
    let boxPromotion = $(".gratitude_to_customers");

    if (promotionBtn.length) {
        promotionBtn.click(function () {
            boxPromotion.addClass("hide-promotion"); // Thêm class để chạy hiệu ứng

            // Chờ hiệu ứng chạy xong rồi mới xóa
            setTimeout(() => {
                boxPromotion.remove();
            }, 500); // Thời gian trùng với transition trong CSS
        });
    }

    //

    let giftBoxInfo2 = $(".gift-box-info:last-child");

    giftBoxInfo2.slick({
        dots: false,
        arrows: true,
        infinite: true,
        speed: 500,
        slidesToShow: 3, // Mặc định hiển thị 3 slide
        slidesToScroll: 1,
        autoplay: true, // Tự động chạy
        autoplaySpeed: 2000, // Chạy mỗi 2 giây
        pauseOnHover: false, // Không dừng khi di chuột vào
        pauseOnFocus: false, // Không dừng khi focus vào slider
        adaptiveHeight: true, // Chiều cao tự động theo nội dung
        prevArrow:
            '<button type="button" class="slick-prev"><svg xmlns="http://www.w3.org/2000/svg" height="18px" viewBox="0 -960 960 960" width="24px" fill="#000"><path d="M560-240 320-480l240-240 56 56-184 184 184 184-56 56Z"/></svg></button>',
        nextArrow:
            '<button type="button" class="slick-next"><svg xmlns="http://www.w3.org/2000/svg" height="18px" viewBox="0 -960 960 960" width="24px" fill="#000"><path d="M504-480 320-664l56-56 240 240-240 240-56-56 184-184Z"/></svg></button>',
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: 2, // Ở tablet hiển thị 2 slide
                },
            },
            {
                breakpoint: 600,
                settings: {
                    slidesToShow: 1, // Ở mobile chỉ hiển thị 1 slide
                },
            },
        ],
    });
});
