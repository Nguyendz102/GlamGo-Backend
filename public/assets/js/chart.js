if (window._) {
    var { merge } = window._;
}

// Exported function: echartSetOption
export const echartSetOption = (
    chart,
    userOptions,
    getDefaultOptions,
    responsiveOptions
) => {
    const { breakpoints, resize } = window.phoenix.utils;
    const handleResize = options => {
        Object.keys(options).forEach(item => {
            if (window.innerWidth > breakpoints[item]) {
                chart.setOption(options[item]);
            }
        });
    };

    const themeController = document.body;
    // Merge user options with lodash
    chart.setOption(merge(getDefaultOptions(), userOptions));

    const navbarVerticalToggle = document.querySelector(
        '.navbar-vertical-toggle'
    );
    if (navbarVerticalToggle) {
        navbarVerticalToggle.addEventListener('navbar.vertical.toggle', () => {
            chart.resize();
            if (responsiveOptions) {
                handleResize(responsiveOptions);
            }
        });
    }

    resize(() => {
        chart.resize();
        if (responsiveOptions) {
            handleResize(responsiveOptions);
        }
    });
    if (responsiveOptions) {
        handleResize(responsiveOptions);
    }

    themeController.addEventListener(
        'clickControl',
        ({ detail: { control } }) => {
            if (control === 'phoenixTheme') {
                chart.setOption(window._.merge(getDefaultOptions(), userOptions));
            }
        }
    );
};

export const handleTooltipPosition = ([pos, , dom, , size]) => {
    if (window.innerWidth <= 540) {
        const tooltipHeight = dom.offsetHeight;
        const obj = {
            top: pos[1] - tooltipHeight - 20
        };
        obj[pos[0] < size.viewSize[0] / 2 ? 'left' : 'right'] = 5;
        return obj;
    }
    return null;
};

// Exported function: tooltipFormatter
export const tooltipFormatter = (params, dateFormatter = 'MMM DD') => {
    let tooltipItem = ``;
    params.forEach(el => {
        tooltipItem += `<div class='ms-1'>
          <h6 class="text-body-tertiary"><span class="fas fa-circle me-1 fs-10" style="color:${el.borderColor ? el.borderColor : el.color
            }"></span>
            ${el.seriesName} : ${typeof el.value === 'object' ? el.value[1] : el.value
            }
          </h6>
        </div>`;
    });
    return `<div>
              <p class='mb-2 text-body-tertiary'>
                ${window.dayjs(params[0].axisValue).isValid()
            ? window.dayjs(params[0].axisValue).format(dateFormatter)
            : params[0].axisValue
        }
              </p>
              ${tooltipItem}
            </div>`;
};

/**
 * Hàm tạo biểu đồ Line Chart
 * @returns {Object}
 */
export const useLineChart = () => {
    let chartInstance = null;

    /**
     * Tạo một series với các tùy chọn được cung cấp
     * @param {Object} seriesOption - Tùy chọn cho series
     * @returns {Object} - Cấu hình cho series
     * name là tên của đường
     * data truyền vào mảng data của bạn
     * color là màu của đường
     * type là Loại biểu đồ, mặc định là 'line' (đường).
    Các loại khác có thể dùng: 'bar' (cột), 'scatter' (điểm), 'area' (vùng)...
     * areaStyle để màu của vùng bên dưới của đường 
     * symbol để biểu tượng của điểm dữ liệu các giá trị: 'circle', 'rect', 'triangle', 'diamond', 'pin', 'arrow', 'none'...
     * smooth Quyết định đường biểu đồ là thẳng hay cong mịn.
     * lineStyle Tùy chỉnh chi tiết về đường như độ dày, kiểu đường... vd: 'solid', 'dashed', 'dotted'... là đường nét đứt hay đường thẳng ..... cd: { width: 2, type: 'dashed' }
     */
    const createSeriesConfig = (seriesOption) => {
        const {
            name = 'Series',
            data = [],
            color = 'primary',
            type = 'line',
            areaStyle = true,
            symbol = 'circle',
            smooth = false,
            lineStyle = {}
        } = seriesOption;

        const { getColor, rgbaColor } = window.phoenix.utils;

        // Xử lý dữ liệu đầu vào
        let processedData = [];

        if (Array.isArray(data)) {
            processedData = data;
        } else if (typeof data === 'object') {
            processedData = Object.values(data);
        }

        return {
            name,
            type,
            symbolSize: 5,
            data: processedData,
            areaStyle: areaStyle ? {
                color: rgbaColor(getColor(color), 0.3)
            } : undefined,
            itemStyle: {
                color: getColor('body-highlight-bg'),
                borderColor: getColor(color),
                borderWidth: 2
            },
            lineStyle: {
                color: getColor(color),
                ...lineStyle
            },
            symbol,
            smooth
        };
    };

    /**
     * Khởi tạo biểu đồ với các dữ liệu cung cấp
     * @param {Object} options - Tùy chọn cho biểu đồ
     * @returns {Object} - Instance của biểu đồ
     */
    const createChart = (options) => {
        const {
            days = [],
            series = [{ name: 'Đơn hàng', data: [], color: 'danger' }],
            maxOverall = null,
            selector = '.echart-component',
            title = '',
            grid = {}
        } = options;

        const { getColor, getData } = window.phoenix.utils;
        const $chartEl = document.querySelector(selector);

        if (!$chartEl) {
            console.error(`Element with selector "${selector}" not found`);
            return null;
        }

        // Tính toán maxOverall nếu không được cung cấp
        let calculatedMax = maxOverall;
        if (calculatedMax === null) {
            // Tìm giá trị lớn nhất trong tất cả các series
            let allValues = [];
            series.forEach(s => {
                const seriesData = Array.isArray(s.data) ? s.data : Object.values(s.data);
                allValues = [...allValues, ...seriesData];
            });

            const maxValue = Math.max(...allValues.filter(v => !isNaN(v)));
            calculatedMax = Math.ceil(maxValue * 1.1); // Thêm 10% để hiển thị đẹp hơn có nghĩa là nhân thêm để các đường không sát bên treenn quá
        }

        // Xử lý các series
        const seriesConfig = series.map(s => createSeriesConfig(s));

        // Khởi tạo biểu đồ
        if (chartInstance) {
            // Nếu biểu đồ đã tồn tại, chỉ cập nhật dữ liệu
            chartInstance.setOption({
                xAxis: {
                    data: days
                },
                yAxis: {
                    max: calculatedMax
                },
                series: seriesConfig
            });
        } else {
            const userOptions = getData($chartEl, 'echarts');
            chartInstance = window.echarts.init($chartEl);

            const tooltipFormatter2 = (params) => {
                let tooltipContent = '';
                params.forEach(el => {
                    tooltipContent += `
                        <div class="ms-1">
                            <h6 class="text-body-tertiary">
                                <span class="fas fa-circle me-1 fs-10" style="color:${el.borderColor || el.color}"></span>
                                ${el.seriesName} : ${typeof el.value === 'object' ? el.value[1] : el.value}
                            </h6>
                        </div>`;
                });
                return `
                    <div>
                        <p class="mb-2 text-body-tertiary">${params[0].axisValue}</p>
                        ${tooltipContent}
                    </div>`;
            };

            const getDefaultOptions = () => ({
                title: title ? {
                    text: title,
                    left: 'center',
                    textStyle: {
                        color: getColor('tertiary-color')
                    }
                } : undefined,
                tooltip: {
                    trigger: 'axis',
                    padding: [7, 10],
                    backgroundColor: getColor('body-highlight-bg'),
                    borderColor: getColor('border-color'),
                    textStyle: {
                        color: getColor('light-text-emphasis')
                    },
                    borderWidth: 1,
                    transitionDuration: 0,
                    axisPointer: {
                        type: 'line'
                    },
                    position: (...params) => handleTooltipPosition(params),
                    formatter: params => tooltipFormatter2(params)
                },
                legend: {
                    show: series.length > 1,
                    data: series.map(s => s.name),
                    bottom: 0,
                    textStyle: {
                        color: getColor('tertiary-color')
                    }
                },
                xAxis: {
                    type: 'category',
                    data: days,
                    boundaryGap: false,
                    axisLine: {
                        lineStyle: {
                            color: getColor('tertiary-bg'),
                            type: 'solid'
                        }
                    },
                    axisTick: {
                        show: false
                    },
                    axisLabel: {
                        color: getColor('quaternary-color'),
                        margin: 15,
                        formatter: value => value.substring(0, 5),
                        align: 'right'
                    },
                    splitLine: {
                        show: false
                    }
                },
                yAxis: {
                    type: 'value',
                    max: calculatedMax,
                    min: 0,
                    splitLine: {
                        lineStyle: {
                            color: getColor('secondary-bg'),
                            type: 'dashed'
                        }
                    },
                    boundaryGap: false,
                    axisLabel: {
                        show: true,
                        color: getColor('quaternary-color'),
                        margin: 15,
                    },
                    axisTick: {
                        show: false
                    },
                    axisLine: {
                        show: false
                    }
                },
                series: seriesConfig,
                grid: {
                    right: 10,
                    left: 5,
                    bottom: series.length > 1 ? 30 : 5,
                    top: title ? 40 : 8,
                    containLabel: true,
                    ...grid
                }
            });

            echartSetOption(chartInstance, userOptions, getDefaultOptions);
        }

        return chartInstance;
    };

    /**
     * Resize biểu đồ khi cần thiết
     */
    const resize = () => {
        if (chartInstance) {
            chartInstance.resize();
        }
    };

    /**
     * Hủy biểu đồ và giải phóng tài nguyên
     */
    const dispose = () => {
        if (chartInstance) {
            chartInstance.dispose();
            chartInstance = null;
        }
    };

    return {
        chartInstance: () => chartInstance,
        createChart,
        resize,
        dispose
    };
};