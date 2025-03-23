async function fetchRatings(productId) {
    try {
        let response = await fetch(`/api/ratings/${productId}`);
        let data = await response.json();

        // Gọi hàm cập nhật UI
        updateRatingUI(data);
    } catch (error) {
        console.error("Lỗi khi lấy dữ liệu đánh giá:", error);
    }
}

function updateRatingUI(data) {
    let globalValue = document.querySelector(".rating-post .global-value");
    let totalRv = document.querySelector(".total-reviews");
    let chartRv = document.querySelector(".rating-post .chart");
    let startTotal = document.querySelector(".rating-post .rating-icons .two");

    if (globalValue) {
        globalValue.innerHTML = data.average_rating;
    }

    if (totalRv) {
        totalRv.innerHTML = data.total_reviews+" (reviews)";
        document.querySelector('.btn-view-rating span').innerHTML = data.total_reviews;
        document.querySelector('.product-info .rating-stars .total_rev').innerHTML = `(${data.total_reviews})`;
    }

    if (startTotal) {
        startTotal.style.background = `linear-gradient(to right, rgb(102, 187, 106) ${data.progressTotal}%, transparent 0%)`;
        document.querySelector('.product-info .avg_rating .avg_star').style.background = `linear-gradient(to right, #ff7b01 ${data.progressTotal}%, gray 0%)`;
    }

    if (chartRv) {
        let htmlRv = Object.entries(data.progress)
            .map(([key, value]) => {

                let starShow = "";

                if(key == 1) {
                    starShow = '<i class="fas fa-star active"></i>';
                }else if(key == 2) {
                    starShow = '<i class="fas fa-star active"></i><i class="fas fa-star active"></i>';
                }else if(key == 3) {
                    starShow = '<i class="fas fa-star active"></i><i class="fas fa-star active"></i><i class="fas fa-star active"></i>';
                }else if(key == 4) {
                    starShow = '<i class="fas fa-star active"></i><i class="fas fa-star active"></i><i class="fas fa-star active"></i><i class="fas fa-star active"></i>';
                }else{
                    starShow = '<i class="fas fa-star active"></i><i class="fas fa-star active"></i><i class="fas fa-star active"></i><i class="fas fa-star active"></i><i class="fas fa-star active"></i>';
                }
                
                return `
<div class="rate-box">
<div class="progress-bar">
    <span class="progress" style="width: ${value}%;"></span>
</div>
<div class="info-start"><span class="value">${starShow}</span><span class="count">(${data.ratings[key]})</span></div>
</div>
`;
            })
            .join("");

        chartRv.innerHTML = htmlRv;
    }
}

fetchRatings(document.querySelector("#id_product").value);

document.addEventListener("DOMContentLoaded", function () {
    const inputFile = document.getElementById("inputFilePicture");
    const previewContainer = document.getElementById("previewImages");
    var selectedFiles = [];

    inputFile.addEventListener("change", function (event) {
        const files = Array.from(event.target.files);

        if (files.length > 3) {
            alert("Bạn chỉ được chọn tối đa 3 ảnh.");
            inputFile.value = ""; // Reset input file
            return;
        }

        selectedFiles = files.slice(0, 3); // Cập nhật danh sách ảnh mới (xoá danh sách cũ)
        displayImages();
        inputFile.value = ""; // Reset input file để có thể chọn lại
    });

    function displayImages() {
        previewContainer.innerHTML = ""; // Xóa ảnh cũ trước khi hiển thị mới

        selectedFiles.forEach((file, index) => {
            if (!file.type.startsWith("image/")) return;

            const reader = new FileReader();
            reader.onload = function (e) {
                const imageWrapper = document.createElement("div");
                imageWrapper.style.position = "relative";
                imageWrapper.style.display = "inline-block";

                const img = document.createElement("img");
                img.src = e.target.result;
                img.style.display = "block";

                const closeBtn = document.createElement("span");
                closeBtn.innerHTML = "❌";
                closeBtn.style.position = "absolute";
                closeBtn.style.top = "5px";
                closeBtn.style.right = "5px";
                closeBtn.style.background = "#fff";
                closeBtn.style.color = "white";
                closeBtn.style.borderRadius = "50%";
                closeBtn.style.cursor = "pointer";
                closeBtn.style.width = "20px";
                closeBtn.style.height = "20px";
                closeBtn.style.display = "flex";
                closeBtn.style.alignItems = "center";
                closeBtn.style.justifyContent = "center";
                closeBtn.style.fontSize = "14px";

                closeBtn.addEventListener("click", function () {
                    selectedFiles.splice(index, 1); // Xóa ảnh khỏi danh sách
                    displayImages(); // Cập nhật lại giao diện
                });

                imageWrapper.appendChild(img);
                imageWrapper.appendChild(closeBtn);
                previewContainer.appendChild(imageWrapper);
            };

            reader.readAsDataURL(file);
        });
    }

    document.querySelectorAll(".rating input").forEach((radio) => {
        radio.addEventListener("change", (event) => {
            // Xóa class 'active-start' khỏi tất cả input trước khi thêm vào input được chọn
            document.querySelectorAll(".rating input").forEach((input) => {
                input.classList.remove("active-star");
            });

            // Lấy giá trị rating từ id của input được chọn
            const ratingValue = event.target.id.split("-")[1];

            // Thêm class 'active-start' vào input được chọn
            event.target.classList.add("active-star");
        });
    });

    // đây
    document
        .querySelector(".send-review")
        .addEventListener("click", async () => {
            let ratingValue = document.querySelector(".active-star")?.value;
            let productId = document.querySelector("#id_product").value;
            let comment = document.querySelector(".comment-main").value;
            let fullname = document.querySelector(".fullname-main").value;
            let phone = document.querySelector(".phone-main").value;
            let isChecked = document.getElementById("checkComfirm").checked;
            let isArgee = document.getElementById("checkboxPolicy").checked;

            if (!ratingValue) {
                alert("Please select star rating!");
                return;
            }

            if (!comment) {
                alert("Please enter review content!");
                return;
            }

            if (!fullname) {
                alert("Please enter your name!");
                return;
            }
            if (!phone) {
                alert("Please enter your phone number for better moderation!");
                return;
            }

            if (!isArgee) {
                alert(
                    "You need to confirm the terms before submitting a review!"
                );
                return;
            }

            // Tạo FormData để gửi dữ liệu và hình ảnh
            let formData = new FormData();
            formData.append("product_id", productId);
            formData.append("rating_value", ratingValue);
            formData.append("comment", comment);
            formData.append("fullname", fullname);
            formData.append("phone", phone);
            formData.append("is_introduce", isChecked);

            // Thêm các file ảnh vào FormData
            selectedFiles.forEach((file, index) => {
                formData.append(`images[${index}]`, file);
            });

            try {
                let response = await axios.post("/api/ratings", formData, {
                    headers: {
                        "Content-Type": "multipart/form-data",
                    },
                });

                if (response.status == 201) {
                    let ratingModalFirstElement =
                        document.getElementById("ratingModal");
                    let ratingModalFirst = bootstrap.Modal.getInstance(
                        ratingModalFirstElement
                    );

                    // Kiểm tra nếu modal đang hiển thị, thì ẩn nó đi
                    if (ratingModalFirst) {
                        ratingModalFirst.hide();
                    } else {
                        // Nếu không có instance, tạo lại và ẩn đi
                        ratingModalFirst = new bootstrap.Modal(
                            ratingModalFirstElement
                        );
                        ratingModalFirst.hide();
                    }

                    // Hiển thị modal thành công
                    let ratingModalSuccess = new bootstrap.Modal(
                        document.getElementById("ratingModalSuccess")
                    );
                    ratingModalSuccess.show();

                    // fetchRatings({{ $data->id }});
                    fetchRatings(document.querySelector("#id_product").value);
                }
            } catch (error) {
                console.error("Lỗi khi gửi đánh giá:", error);
                alert("Có lỗi xảy ra, vui lòng thử lại!");
            }
        });
});
