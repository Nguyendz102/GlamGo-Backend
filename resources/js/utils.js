// hàm vô loading nút khi bấm
const btnLoading = (btn, isLoad = true, text = "") => {
    if (isLoad) {
        btn.disabled = true;
        btn.innerHTML = `<span class="spinner-border spinner-border-sm" style="--phoenix-spinner-width: 0.8rem;--phoenix-spinner-height: 0.8rem" role="status" aria-hidden="true"></span> ${text || btn.title
            }`;
    } else {
        btn.disabled = false;
        if (text) {
            btn.innerHTML = `${text}`;
        } else {
            btn.innerHTML = `${btn.title}`;
        }
    }
}
/**
 * Hàm định dạng tiền tệ VND
 */
const formatCurrency = (value) => {
    return new Intl.NumberFormat("vi-VN", { useGrouping: true }).format(value).replace(/\./g, ",");
};

/**Hàm có tác dụng format lại tiền khi ở thẻ input
 * @param {string} data dữ liệu nhận vào để format
 */

/**Hàm có tác dụng xóa định dạng tiền trước khi gửi lên backend
 * @param {string} data dữ liệu nhận vào để xóa định dạng
 */
const removeCommas = (data) => {
    return data.replace(/\D/g, "");
}
const dateTimeFormat = (dateStr, format = 'd-m-y') => {
    const date = new Date(dateStr);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const seconds = String(date.getSeconds()).padStart(2, '0');

    switch (format) {
        case 'd-m-y':
            return `${day}-${month}-${year}`;
        case 'm-d-y':
            return `${month}-${day}-${year}`;
        case 'y-m-d':
            return `${year}-${month}-${day}`;
        case 'd-m-y h:m:s':
            return `${day}-${month}-${year} ${hours}:${minutes}:${seconds}`;
        case 'm-d-y h:m:s':
            return `${month}-${day}-${year} ${hours}:${minutes}:${seconds}`;
        case 'y-m-d h:m:s':
            return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
        default:
            return `${day}-${month}-${year}`;
    }
}

function slug(input) {
    // Chuyển đổi chuỗi thành chữ thường
    let slug = input.toLowerCase();
    // Thay thế các ký tự tiếng Việt có dấu thành không dấu
    slug = slug
        .replace(/á|à|ả|ã|ạ|ă|ắ|ằ|ẳ|ẵ|ặ|â|ấ|ầ|ẩ|ẫ|ậ/g, "a")
        .replace(/é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ/g, "e")
        .replace(/i|í|ì|ỉ|ĩ|ị/g, "i")
        .replace(/ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ/g, "o")
        .replace(/ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự/g, "u")
        .replace(/ý|ỳ|ỷ|ỹ|ỵ/g, "y")
        .replace(/đ/g, "d");

    // Loại bỏ các ký tự không phải chữ và số
    slug = slug.replace(/[^a-z0-9\s-]/g, "");

    // Thay thế khoảng trắng và dấu gạch ngang liền kề bằng một dấu gạch ngang duy nhất
    slug = slug.replace(/\s+/g, "-").replace(/-+/g, "-");

    // Loại bỏ dấu gạch ngang ở đầu và cuối chuỗi
    slug = slug.replace(/^-+|-+$/g, "");

    return slug;
}

const statusTable = {
    artical: "Bài viết",
    categories: "Danh mục sản phẩm",
    categories_artical: "Danh mục bài viết",
    comment: "Bình luận",
    country: "Quốc gia",
    coupon: "Mã giảm giá",
    order: "Đơn hàng",
    products: "Sản phẩm",
    reviews: "Đánh giá",
    shipping: "Vận chuyển",
    transactions: "Giao dịch",
    users: "Người dùng",
};


const numberOption = {
    decimalSeparator: ".",
    groupSeparator: ",",
    minDecimal: 0,
    maxDecimal: 2,
};
const formatBalance = (number, options = {}) => {
    let config = { ...numberOption, ...options };
    const firstDotIndex = number.indexOf(".");
    if (firstDotIndex !== -1 && firstDotIndex === number.length - 1) {
        return number;
    }
    number = number.replace(/[^\d.-]/g, "");

    let index = number.indexOf(".", number.indexOf(".") + 1);

    if (index !== -1) {
        number = number.substring(0, index);
    }
    if (number === "") {
        return 0;
    } else {
        return formatNumber(number, config.maxDecimal, config);
    }
};
/**
 * Format a number string into a custom format with grouping and decimal separators.
 *
 * @param {string} numberString - The number string to be formatted.
 * @param {number} [max=0] - The maximum number of decimal places to display. Default is 0.
 * @param {string} [groupSeparator=','] - The character used to separate groups of digits. Default is ','.
 * @param {string} [decimalSeparator='.'] - The character used to separate the integer and decimal parts. Default is '.'.
 * @returns {string} - The formatted number string.
 * @throws {Error} - If the input number string is not a valid number.
 * @example
 * formatNumber('1234567.89', 2, '.', ',') => '1,234,567.89'
 * formatNumber('9876543.21', 0, ',', '.') => '9,876,543'
 */

function formatNumber(numberString, max = 3, options = {}) {
    const number = parseFloat(numberString);
    if (isNaN(number)) {
        console.error("Invalid number string");
        return "0";
    }
    const config = { ...numberOption, ...options };
    if (config.minDecimal > max) {
        config.minDecimal = max;
    }
    return number
        .toLocaleString("en-US", {
            minimumFractionDigits: config.minDecimal,
            maximumFractionDigits: max,
        })
        .replace(".", config.decimalSeparator);
}

export { formatCurrency, removeCommas, dateTimeFormat, statusTable, slug, btnLoading, formatBalance, formatNumber };
