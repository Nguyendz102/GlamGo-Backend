$(document).ready(function () {
    $(".apply-discount-code").click(function () {
        $(".form-discount-code").toggle();
    });

    $(".gift-card").click(function () {
        $(".form-gift-card").toggle();
    });

    $(".view-slide-choose").click(function () {
        $(".toggle-slide").toggle(); // Bật/tắt phần slide

        if ($(".toggle-slide").is(":visible")) {
            setTimeout(() => {
                $(".list_slide_choose").slick("setPosition");
                $(".overlay_slide").fadeOut();
            }, 100);
            $(".icon svg").addClass("active");
            $(this).find("span:first").text("HIDE"); // Đổi VIEW thành HIDE
        } else {
            $(".icon svg").removeClass("active");
            $(this).find("span:first").text("VIEW"); // Đổi lại HIDE thành VIEW
        }
    });

    $(".form-custom").click(function () {
        $(".form-custom-cart-1").toggle();

        if ($(".form-custom-cart-1").is(":visible")) {
            $(".form-custom .icon svg").addClass("active");
            $(this).find("span:first").text("HIDE"); // Đổi VIEW thành HIDE
        } else {
            $(".form-custom .icon svg").removeClass("active");
            $(this).find("span:first").text("VIEW"); // Đổi lại HIDE thành VIEW
        }
    });

    $(".form-choose").click(function () {
        $(".form-custom-cart-2").toggle();

        if ($(".form-custom-cart-2").is(":visible")) {
            $(".form-choose .icon svg").addClass("active");
            $(this).find("span:first").text("HIDE"); // Đổi VIEW thành HIDE
        } else {
            $(".form-choose .icon svg").removeClass("active");
            $(this).find("span:first").text("VIEW"); // Đổi lại HIDE thành VIEW
        }
    });

    const btnRemove = $(".remove-modal");
    const modal = $(".asfy__modal__contact");
    const submitContact = $(".submit-contact");

    const mediaQuery = window.matchMedia("(max-width: 768px)");
    const mediaQueryXl = window.matchMedia("(min-width: 2000px)");

    function handleWidthChange(e) {
        if (e.matches) {
            $(".asfy__modal__contact .modal__body").css("height", "663px");
        }
    }
    function handleWidthChangeXl(e) {
        if (e.matches) {
            $(".asfy__modal__contact .modal__body").css("height", "675px");
        }
    }

    // Lắng nghe sự thay đổi
    mediaQuery.addEventListener("change", handleWidthChange);
    mediaQuery.addEventListener("change", handleWidthChangeXl);

    // Kiểm tra trạng thái ban đầu

    // if (submitContact) {
    //     submitContact.on("click", () => {
    //         submitContact.prop("disabled", true);
    //         $(".submit-contact .loader_contact").css("display", "inline-block");
    //         const fullname = $(".page-contact #fullname").val();
    //         const email = $(".page-contact #email").val();
    //         const phone = $(".page-contact #phone").val();
    //         const service = $(".page-contact #service").val();
    //         const note = $(".page-contact #note").val();
    //         const gender = $(
    //             '.page-contact input[name="gender"]:checked'
    //         ).val();

    //         const formData = new FormData();
    //         formData.append("fullname", fullname);
    //         formData.append("email", email);
    //         formData.append("phone", phone);
    //         formData.append("service", service);
    //         formData.append("note", note);
    //         formData.append("gender", gender);

    //         const modalBody = window.matchMedia("max-width:  430px");

    //         // console.log([...formData]);

    //         const origin = window.location.origin;

    //         const request = axios.post(
    //             `${origin}/wp-json/asfy/api/receive-contact`,
    //             formData
    //         );

    //         request
    //             .then((response) => {
    //                 $(".box__form__input").css("margin-top", "20px");
    //                 $(".err-has_code").text("");
    //                 $(".err-fullname").text("");
    //                 $(".err-email").text("");
    //                 $(".err-phone").text("");
    //                 $(".err-gender").text("");
    //                 $(".err-service").text("");
    //                 $(".info-req").css("display", "inline-block");
    //                 $(".asfy__modal__contact .modal__body").css(
    //                     "height",
    //                     "635px"
    //                 );
    //                 handleWidthChangeXl(mediaQueryXl);
    //                 handleWidthChange(mediaQuery);
    //                 submitContact.prop("disabled", false);
    //                 $(".submit-contact .loader_contact").css("display", "none");
    //                 $(".success-info").removeClass("text-danger");
    //                 $(".success-info").addClass("text-success");
    //                 $(".success-info").text(
    //                     "Asfy Tech đã nhận thông tin liên hệ của bạn."
    //                 );
    //             })
    //             .catch((err) => {
    //                 $(".box__form__input").css("margin-top", "20px");
    //                 // $(".success-info").removeClass("text-success");
    //                 // $(".success-info").addClass("text-danger");
    //                 // $(".info-req").css("display", "inline-block");
    //                 $(".asfy__modal__contact .modal__body").css(
    //                     "height",
    //                     "635px"
    //                 );
    //                 handleWidthChangeXl(mediaQueryXl);
    //                 handleWidthChange(mediaQuery);
    //                 // $(".success-info").text(
    //                 //   "Bạn vui lòng kiểm tra lại thông tin vừa cung cấp."
    //                 // );
    //                 submitContact.prop("disabled", false);
    //                 $(".submit-contact .loader_contact").css("display", "none");
    //                 // console.log(err);
    //                 const listErrors = err.response.data.message;

    //                 if (listErrors.has_code) {
    //                     $(".err-has_code").text(listErrors.has_code);
    //                 } else {
    //                     $(".err-has_code").text("");
    //                 }

    //                 if (listErrors.fullname) {
    //                     $(".err-fullname").text(listErrors.fullname);
    //                 } else {
    //                     $(".err-fullname").text("");
    //                 }

    //                 if (listErrors.email) {
    //                     $(".err-email").text(listErrors.email);
    //                 } else {
    //                     $(".err-email").text("");
    //                 }

    //                 if (listErrors.phone) {
    //                     $(".err-phone").text(listErrors.phone);
    //                 } else {
    //                     $(".err-phone").text("");
    //                 }

    //                 if (listErrors.gender) {
    //                     $(".err-gender").text(listErrors.gender);
    //                 } else {
    //                     $(".err-gender").text("");
    //                 }

    //                 if (listErrors.service) {
    //                     $(".err-service").text(listErrors.service);
    //                 } else {
    //                     $(".err-service").text("");
    //                 }
    //             });
    //     });
    // }

    // Hiển thị modal sau 10 giây với hiệu ứng

    $(".cart-overview .overview-item .by-friend-referred").click(function () {
        modal.addClass("active");
    });

    // Khi bấm vào nút đóng modal
    if (modal) {
        btnRemove.on("click", () => {
            modal.removeClass("active"); // Ẩn modal bằng cách xóa class
        });
    }

    // ===================================================== FIXED ==========================================

    function getElementOffsetTop(element) {
        let offsetTop = 0;
        while (element) {
            offsetTop += element.offsetTop;
            element = element.offsetParent; // Di chuyển lên phần tử cha
        }
        return offsetTop;
    }

    const cartOverview = document.querySelector(".cart-overview");
    const mainHeader = document.querySelector(".main-header");
    const mainFooter = document.querySelector(".main-footer");

    if (cartOverview && mainHeader && mainFooter) {
        let originalWidth = cartOverview.offsetWidth; // Lấy chiều rộng ban đầu
        const marginCart = getElementOffsetTop(cartOverview);
        $(window).on("scroll", function () {
            if (window.matchMedia("(min-width: 1200px)").matches) {
                if (document.documentElement.scrollTop >= marginCart) {
                    if (
                        !cartOverview.classList.contains("cart-overview-fixed")
                    ) {
                        cartOverview.classList.add("cart-overview-fixed");
                        cartOverview.style.width = originalWidth + "px";
                    }
                } else {
                    cartOverview.classList.remove("cart-overview-fixed");
                    cartOverview.style.width = ""; // Reset width về trạng thái ban
                }

                // Nếu scroll lên đầu trang, xóa class luôn
                if (window.scrollY === 0) {
                    cartOverview.classList.remove("cart-overview-fixed");
                    cartOverview.style.width = "";
                }

                const cartRect = cartOverview.getBoundingClientRect();
                const footerRect = mainFooter.getBoundingClientRect();

                // Kiểm tra nếu phần tử cart chạm vào footer
                if (cartRect.bottom >= footerRect.top) {
                    cartOverview.classList.remove("cart-overview-fixed");
                    cartOverview.style.width = "";
                }
            }
        });
    }
});
