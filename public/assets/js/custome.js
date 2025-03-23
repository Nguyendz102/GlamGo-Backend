const numberOption = {
    decimalSeparator: ".",
    groupSeparator: ",",
    minDecimal: 0,
    maxDecimal: 3,
};
function functionExists(functionName) {
    return typeof window[functionName] === "function";
}
// Hàm Xử Lý delay
function debounce(func, delay) {
    let timeoutId;
    return function (...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => {
            func.apply(this, args);
        }, delay);
    };
}
// hàm định dạng số tiền khi nhập
const formatBalance = (event, options = {}) => {
    let config = { ...numberOption, ...options };
    let balance = event.target.value;
    const firstDotIndex = balance.indexOf(".");
    if (firstDotIndex !== -1 && firstDotIndex === balance.length - 1) {
        event.target.value = balance;
        return;
    }
    balance = balance.replace(/[^\d.-]/g, "");
    let index = balance.indexOf(".", balance.indexOf(".") + 1);

    if (index !== -1) {
        balance = balance.substring(0, index);
    }
    if (balance === "") {
        event.target.value = balance;
    } else {
        balance = formatNumber(balance, config.maxDecimal, config);
        event.target.value = balance;
    }
};
const handleInputBalance = (event, options) => {
    delayedBalanceHandler(event, options);
};

// Sử dụng debounce để tạo độ trễ cho sự kiện oninput
const delayedBalanceHandler = debounce(formatBalance, 100);

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
        throw new Error("Invalid number string");
    }
    const config = { ...numberOption, ...options };
    if (config.minDecimal > max) {
        config.minDecimal = max;
    }
    const formattedNumber = number.toLocaleString("en-US", {
        minimumFractionDigits: config.minDecimal,
        maximumFractionDigits: max,
        useGrouping: true,
        groupSeparator: config.groupSeparator,
        decimalSeparator: config.decimalSeparator,
    });

    return formattedNumber;
}
const removeCommas = (numberString) => {
    const cleanedString = numberString.replace(/,/g, "");
    const number = parseInt(cleanedString, 10);
    return number;
};
const dateTimeFormat = (date, format = "d-m-Y") => {
    let currentDate;
    if (date == "0000-00-00 00:00:00" || date == "0000-00-00" || date == "") {
        return "";
    }
    if (typeof date === "string" || typeof date === "number") {
        currentDate = new Date(date);
    } else {
        return "";
    }

    let seconds = currentDate.getSeconds().toString().padStart(2, "0"); // Add leading zero if needed
    let minutes = currentDate.getMinutes().toString().padStart(2, "0"); // Add leading zero if needed
    let hours = currentDate.getHours().toString().padStart(2, "0"); // Add leading zero if needed
    let day = currentDate.getDate().toString().padStart(2, "0"); // Add leading zero if needed
    let month = (currentDate.getMonth() + 1).toString().padStart(2, "0"); // Month is zero-based
    let year = currentDate.getFullYear();
    let result = format.replace("i", minutes);
    result = result.replace("s", seconds);
    result = result.replace("H", hours);
    result = result.replace("d", day);
    result = result.replace("m", month);
    result = result.replace("Y", year);
    return result;
};