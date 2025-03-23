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
        document.querySelector('.title-page').innerHTML = data.total_reviews + " review " + document.querySelector('#product_name').value;
    }

    if (startTotal) {
        startTotal.style.background = `linear-gradient(to right, rgb(102, 187, 106) ${data.progressTotal}%, transparent 0%)`;
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

