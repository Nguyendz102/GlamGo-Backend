<template>
    <div>
        <h1>Trang tổng quan</h1>
    </div>
    <div class="mb-3 thongke mt-4">
        <div>
            <div class="icon">
                <div class="icon-border bg-primary-subtle">
                    <i class="fab fa-codepen text-primary-emphasis"></i>
                </div>
            </div>
            <router-link to="orders" class="body text-decoration-none">
                <p class="fw-bold m-0"><span>{{ dataDashboard.order_count }}</span> đơn hàng</p>
                <span class="fs-8 fw-bold text-body-highlight">Tổng số</span>
            </router-link>
        </div>
        <div>
            <div class="icon">
                <div class="icon-border bg-success-subtle">
                    <i class="fab fa-codepen text-success-emphasis"></i>
                </div>
            </div>
            <router-link to="orders" class="body text-decoration-none">
                <p class="fw-bold m-0"><span class="thongke-khongkhoa">{{ formatNumber(dataDashboard.order_price)
                }}</span> doanh thu
                </p>
                <span class="fs-8 fw-bold text-body-highlight">Tổng số</span>
            </router-link>
        </div>
        <div>
            <div class="icon">
                <div class="icon-border bg-danger-subtle">
                    <i class="fab fa-codepen text-danger-emphasis"></i>
                </div>
            </div>
            <router-link to="products" class="body text-decoration-none">
                <p class="fw-bold m-0"><span class="thongke-khoa">{{ dataDashboard.product_count }}</span> sản phẩm</p>
                <span class="fs-8 fw-bold text-body-highlight">Tổng số</span>
            </router-link>
        </div>
        <div>
            <div class="icon">
                <div class="icon-border bg-danger-subtle">
                    <i class="fab fa-codepen text-danger-emphasis"></i>
                </div>
            </div>
            <router-link to="detail-category" class="body text-decoration-none">
                <p class="fw-bold m-0"><span class="thongke-khoa">{{ dataDashboard.categories_count }}</span> Danh mục
                </p>
                <span class="fs-8 fw-bold text-body-highlight">Tổng số</span>
            </router-link>
        </div>
    </div>
    <div class="mb-3 thongke mt-4">
        <div class="d-none d-md-none d-lg-block" style="visibility: hidden;">
            <div class="icon">
                <div class="icon-border bg-primary-subtle">
                    <i class="fab fa-codepen text-primary-emphasis"></i>
                </div>
            </div>
        </div>
        <div>
            <div class="icon">
                <div class="icon-border bg-primary-subtle">
                    <i class="fab fa-codepen text-primary-emphasis"></i>
                </div>
            </div>
            <router-link :to="{
                name: 'orders',
                query: {
                    start_date: startDate,
                    end_date: endDate
                }
            }" class="body text-decoration-none">
                <p class="fw-bold m-0"><span class="thongke-sum">{{ dataDashboard.order_count_month }}</span> đơn hàng
                </p>
                <span class="fs-8 fw-bold text-body-highlight">Tháng này</span>
            </router-link>
        </div>
        <div>
            <div class="icon">
                <div class="icon-border bg-success-subtle">
                    <i class="fab fa-codepen text-success-emphasis"></i>
                </div>
            </div>
            <router-link :to="{
                name: 'orders',
                query: {
                    start_date: startDate,
                    end_date: endDate
                }
            }" class="body text-decoration-none">
                <p class="fw-bold m-0"><span class="thongke-khongkhoa">{{
                    formatNumber(dataDashboard.countPriceOrderMonth)
                        }}</span> doanh thu</p>
                <span class="fs-8 fw-bold text-body-highlight">Tháng này</span>
            </router-link>
        </div>
        <div class="d-none d-md-none d-lg-block" style="visibility: hidden;">
            <div class="icon">
                <div class="icon-border bg-primary-subtle">
                    <i class="fab fa-codepen text-primary-emphasis"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Biểu đồ phát sinh đơn hàng & doanh thu -->
    <div>
        <div class="row g-3">
            <div class="col-12 col-xl-6 col-xxl-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="row flex-between-center mb-4 g-3">
                            <div class="col-9 md-9">
                                <h4>Biểu đồ phát sinh đơn hàng</h4>
                                <p class="text-body-tertiary lh-sm mb-2">Tháng này ({{ dateNow }})</p>
                            </div>
                            <div class="col-3 md-3 d-flex justify-content-end">
                                <router-link class="nav-link label-1" :to="{
                                    name: 'orders',
                                    query: {
                                        start_date: startDate,
                                        end_date: endDate
                                    }
                                }">
                                    <span title="Xem chi tiết" class="far fa-eye text-info fs-8"></span>
                                </router-link>
                            </div>
                        </div>
                        <div class="echart-total-order" style="height: 350px; width: 100%;"></div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6 col-xxl-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="row flex-between-center mb-4 g-3">
                            <div class="col-9 md-9">
                                <h4>Biểu đồ phát sinh doanh thu</h4>
                                <p class="text-body-tertiary lh-sm mb-2">Tháng này ({{ dateNow }})</p>
                            </div>
                            <div class="col-3 md-3 d-flex justify-content-end">
                                <router-link class="nav-link label-1" :to="{
                                    name: 'transactions',
                                    query: {
                                        start_date: startDate,
                                        end_date: endDate
                                    }
                                }">
                                    <span title="Xem chi tiết" class="far fa-eye text-info fs-8"></span>
                                </router-link>
                            </div>
                        </div>
                        <div class="echart-total-order-price" style="height:350px;width:100%">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--End-phát sinh đơn hàng & doanh thu  --End-->
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { useLineChart } from '../../../public/assets/js/chart';
import { formatNumber } from '../utils';


const dataDashboard = ref({});
const days = ref([]);
const dataOrderChar = ref([]);
const dataRevenueChar = ref([]);
const chart = useLineChart();
const chartRevenue = useLineChart();
const count = ref(0);
const countDay = ref(0);


const dateNow = new Date().toLocaleDateString('en-GB', {
    month: '2-digit',
    year: 'numeric'
}).replace('/', '-');

const formatDate = (date) =>
    `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;

const today = new Date();
const year = today.getFullYear();
const month = today.getMonth();

const startDate = formatDate(new Date(year, month, 1));
const endDate = formatDate(new Date(year, month + 1, 0));
const nowToday = formatDate(new Date());


const fetchData = async () => {
    try {
        const response = await axios.get('/api/dashboard');
        dataDashboard.value = response.data.dataTitle;

        days.value = response.data.dataChar.date;
        dataOrderChar.value = response.data.dataChar.list_order;
        dataRevenueChar.value = response.data.dataChar.price_order;

        // Tạo biểu đồ line chart
        chart.createChart({
            days: days.value,
            series: [
                {
                    name: 'Đơn hàng',
                    data: dataOrderChar.value,
                    color: 'danger',
                    areaStyle: true,
                    symbol: 'none',
                }
            ],
            selector: '.echart-total-order',
            // title: 'Đơn hàng & Doanh thu theo thời gian',
            grid: {
                bottom: 40
            }
        });

        chartRevenue.createChart({
            days: days.value,
            series: [
                {
                    name: 'tổng',
                    data: dataRevenueChar.value,
                    color: 'success',
                    areaStyle: true,
                    symbol: 'none',
                }

            ],
            selector: '.echart-total-order-price',
            // title: 'Đơn hàng & Doanh thu theo thời gian',
            grid: {
                bottom: 40
            }
        });

    } catch (error) {
        console.error('Error fetching data:', error);
    }
};

const handleResize = () => {
    chart.resize();
};

onMounted(async () => {
    fetchData();
    window.addEventListener('resize', handleResize);
    try {
        const res = await axios.get('/api/products/current-count');
        count.value = res.data.count;
        countDay.value = res.data.countDay;
    } catch (err) {
        console.error("Không lấy được count ban đầu:", err);
    }
    window.Echo.channel('product-channel')
        .listen('.product-added', (e) => {
            count.value = e.count;
            countDay.value += 1;
        });
});

onUnmounted(() => {
    chart.dispose();
    window.removeEventListener('resize', handleResize);
});
</script>
<style scoped>
.stats-container {
    position: relative;
}

.stat-item {
    min-width: 140px;
    padding: 12px 15px;
    border-radius: 10px;
    text-decoration: none !important;
    display: block;
    transition: all 0.4s ease;
    position: relative;
    overflow: hidden;
    z-index: 1;
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.stat-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: white;
    opacity: 0.9;
    z-index: -1;
    border-radius: inherit;
}

.stat-item::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: -2;
    border-radius: inherit;
}

.new-product-stat::after {
    background: linear-gradient(135deg, rgba(13, 110, 253, 0.1) 0%, rgba(214, 51, 132, 0.1) 100%);
}

.taken-product-stat::after {
    background: linear-gradient(135deg, rgba(25, 135, 84, 0.1) 0%, rgba(255, 193, 7, 0.1) 100%);
}

.stat-icon {
    width: 36px;
    height: 36px;
    background: rgba(255, 255, 255, 0.7);
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
    font-size: 16px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.new-product-stat .stat-icon {
    color: #ff3e6c;
    background: rgba(255, 62, 108, 0.1);
}

.taken-product-stat .stat-icon {
    color: #4ff290;
    background: rgba(255, 193, 7, 0.1);
}

.stat-value {
    font-size: 20px;
    font-weight: 700;
    line-height: 1;
    display: block;
    margin-bottom: 2px;
}

.new-product-stat .stat-value {
    color: #ff3e6c;
}

.taken-product-stat .stat-value {
    color: #4ddcd8;
}

.stat-label {
    font-size: 13px;
    color: #6c757d;
    font-weight: 500;
    display: block;
}

.stat-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
}

.stat-item:hover .stat-icon {
    transform: scale(1.1);
    transition: all 0.3s ease;
}

/* Hiệu ứng ánh sáng khi hover */
.stat-item:hover::after {
    opacity: 0.15;
}
</style>