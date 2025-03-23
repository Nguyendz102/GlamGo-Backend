document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.getElementById("sidebar-menu-mobi");
    const overlay = document.getElementById("sidebar-overlay");
    const navMobile = document.querySelector(".nav-mobile");
    const toggleBtn = document.querySelector(".nav-mobile .toggle-bar");
    const mainHeader = document.querySelector("header.main-header");
    const body = document.querySelector("body");

    function openSidebar() {
        innerClose();
        const closeBtn = document.querySelector(".nav-mobile .toggle-close");
        closeBtn.addEventListener("click", closeSidebar);
        sidebar.classList.add("active");
        overlay.classList.add("active");
        body.style.overflow = "hidden";
        mainHeader.classList.add("fixed-header-2");
    }

    function closeSidebar() {
        innerBar();
        const toggleBtn = document.querySelector(".nav-mobile .toggle-bar");
        toggleBtn.addEventListener("click", openSidebar);
        sidebar.classList.remove("active");
        overlay.classList.remove("active");
        body.style.overflow = "auto";
        mainHeader.classList.remove("fixed-header-2");
    }

    function innerClose() {
        navMobile.innerHTML = `<span class="toggle-close"><i class="fa-solid fa-xmark"></i></span>`;
    }
    function innerBar() {
        navMobile.innerHTML = `<span class="toggle-bar"><i class="fa-solid fa-bars"></i></span>`;
    }

    // Mở menu
    toggleBtn.addEventListener("click", openSidebar);

    // Đóng menu khi nhấn vào nút đóng hoặc overlay
    overlay.addEventListener("click", closeSidebar);
});

//  Đóng/ Mở con

$(document).ready(function () {
    $(".nav-parent .toggle-submenu").click(function (e) {
        e.preventDefault();

        // Toggle class 'active' để xác định trạng thái mở / đóng
        $(this).toggleClass("active");

        // Kiểm tra nếu đang mở thì đổi SVG thành mũi tên xuống, ngược lại là mũi tên phải
        if ($(this).hasClass("active")) {
            $(this)
                .html(`<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000">
                   <path d="M480-344 240-584l56-56 184 184 184-184 56 56-240 240Z"/>
               </svg>`);
        } else {
            $(this)
                .html(` <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                            fill="#000">
                            <path d="M504-480 320-664l56-56 240 240-240 240-56-56 184-184Z" />
                        </svg>`);
        }
        $(this).closest("li").find(".sub-menu").fadeToggle(300);
    });
    $(".nav-parent .toggle-submenu-2").click(function (e) {
        e.preventDefault();
        // Toggle class 'active' để xác định trạng thái mở / đóng
        $(this).toggleClass("active");

        // Kiểm tra nếu đang mở thì đổi SVG thành mũi tên xuống, ngược lại là mũi tên phải
        if ($(this).hasClass("active")) {
            $(this)
                .html(`<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000">
                    <path d="M480-344 240-584l56-56 184 184 184-184 56 56-240 240Z"/>
                </svg>`);
        } else {
            $(this)
                .html(` <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                             fill="#000">
                             <path d="M504-480 320-664l56-56 240 240-240 240-56-56 184-184Z" />
                         </svg>`);
        }
        $(this).closest("li").find(".sub-menu-2").fadeToggle(300);
    });

    $(".main-footer .toggle-submenu").click(function (e) {
        e.preventDefault();
        // Toggle class 'active' để xác định trạng thái mở / đóng
        $(this).toggleClass("active");

        // Kiểm tra nếu đang mở thì đổi SVG thành mũi tên xuống, ngược lại là mũi tên phải
        if ($(this).hasClass("active")) {
            $(this).html(`<i class="fa-solid fa-minus"></i>`);
        } else {
            $(this).html(`<i class="fa-solid fa-plus"></i>`);
        }
        $(this)
            .closest(".footer-item-1")
            .find(".submenu-footer")
            .fadeToggle(300);
        $(this).closest(".footer-item").find(".submenu-footer").fadeToggle(300);
    });

    $(window).resize(function () {
        if ($(window).width() >= 1025) {
            $(".main-footer .submenu-footer").css("display", "block");
        } else {
            $(".main-footer .submenu-footer").css("display", "none");
        }
    });

    $(".my-gift").click(function (event) {
        $(".main-search .my-gift .show-my-cart").toggleClass("active");
    });

    // Ngăn nổi bọt khi click vào .show-my-cart
    $(".show-my-cart").click(function (event) {
        event.stopPropagation();
    });
    $(".list-item-bag .item-title").click(function () {
        $(".bag-list").toggle();

        if ($(".bag-list").is(":visible")) {
            $(".item-title .value").removeClass("active");
        } else {
            $(".item-title .value").addClass("active");
        }
    });

    $(".list-item-bag .show-detail").click(function () {
        let parent = $(this).closest(".info-total"); // Tìm phần tử cha gần nhất
        let detailInfo = parent.find(".detail-info"); // Chỉ lấy .detail-info trong phần tử cha đó
        let svgIcon = $(this).find("svg"); // Chỉ lấy svg của phần tử được click

        detailInfo.toggle(); // Hiển thị/ẩn detail-info của phần tử được click
        svgIcon.toggleClass("active"); // Thêm/xóa class active vào icon svg
    });

    $('.order-summary .remove-modal').click(function() {
        $('.order-summary').removeClass('active');
    })
});
