/**
 * @license Copyright (c) 2003-2023, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see https://ckeditor.com/legal/ckeditor-oss-license
 */

CKEDITOR.editorConfig = function (config) {
    // Define changes to default configuration here. For example:
    // config.language = 'fr';
    // config.uiColor = '#AADC6E';

    config.filebrowserBrowseUrl = "/plugins/ckfinder/ckfinder.html";

    config.filebrowserUploadUrl =
        "/plugins/ckfinder/core/connector/php/connector.php?command=DeleteFiles&QuickUpload&type=Files";

    config.extraPlugins = "youtube, customImage, image2";
    config.youtube_width = "640";
    config.youtube_height = "480";
    config.youtube_responsive = true;
    config.youtube_related = true;
    config.youtube_older = false;
    config.youtube_autoplay = false;
    config.youtube_privacy = false;
    config.youtube_controls = true;
    config.youtube_disabled_fields = ["txtEmbed", "chkAutoplay"];
};

// Lắng nghe sự kiện chọn ảnh từ CKFinder
// Lắng nghe sự kiện chọn ảnh từ CKFinder
function processImageWithWatermark(url, editorInstance) {
    let img = new Image();
    img.crossOrigin = "anonymous"; // Tránh lỗi CORS
    img.src = url;

    img.onload = function () {
        let canvas = document.createElement("canvas");
        let ctx = canvas.getContext("2d");

        // Kích thước ảnh
        canvas.width = img.width;
        canvas.height = img.height;
        ctx.drawImage(img, 0, 0);

        // Thêm watermark
        let watermark = new Image();
        watermark.crossOrigin = "anonymous";
        watermark.src =
            "https://www.my1styears.com/media/mf_webp/png/media/logo/websites/6/Logos-01.webp";

        watermark.onload = function () {
            let maxSize = 100; // Kích thước tối đa 150px
            let ratio = watermark.width / watermark.height;
            let logoWidth, logoHeight;

            // Tính toán kích thước watermark giữ nguyên tỷ lệ
            if (watermark.width > watermark.height) {
                logoWidth = maxSize;
                logoHeight = maxSize / ratio;
            } else {
                logoHeight = maxSize;
                logoWidth = maxSize * ratio;
            }

            let x = 15; // Căn phải 15px
            let y = 20; // Căn dưới 20px

            ctx.drawImage(watermark, x, y, logoWidth, logoHeight);

            // Chuyển canvas thành base64
            let dataURL = canvas.toDataURL("image/png");

            //
            fetch("/api/upload-image-finder", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },
                body: JSON.stringify({ image: dataURL }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.url) {
                        // Chèn ảnh đã lưu vào trình soạn thảo
                        let imgTag = `<img src="${data.url}" alt="watermarked image">`;
                        editorInstance.insertHtml(imgTag);
                    } else {
                        console.error("Lỗi khi lưu ảnh:", data.error);
                    }
                })
                .catch((error) => console.error("Fetch error:", error));

            //
        };
    };

    img.onerror = function () {
        console.error("Không thể tải ảnh. Kiểm tra URL hoặc lỗi CORS.");
    };
}

// Mở CKFinder và xử lý ảnh sau khi chọn
function openCKFinder(editorInstance) {
    CKFinder.popup({
        chooseFiles: true,
        width: 800,
        height: 600,
        onInit: function (finder) {
            finder.on("files:choose", function (evt) {
                let file = evt.data.files.first();
                let fileUrl = file.getUrl();

                // Xử lý ảnh đã chọn bằng watermark
                processImageWithWatermark(fileUrl, editorInstance);
            });
        },
    });
}

// Thêm nút mở CKFinder vào CKEditor
CKEDITOR.plugins.add("customImage", {
    init: function (editor) {
        // console.log('Plugin customImage đã được tải thành công!');
        editor.ui.addButton("CustomImage", {
            label: "Chọn ảnh với watermark",
            command: "openCKFinder",
            toolbar: "insert",
            icon: "image",
        });

        editor.addCommand("openCKFinder", {
            exec: function (editor) {
                openCKFinder(editor);
            },
        });
    },
});

// Kích hoạt plugin mới
CKEDITOR.replace("editor1", {
    extraPlugins: "customImage",
});
