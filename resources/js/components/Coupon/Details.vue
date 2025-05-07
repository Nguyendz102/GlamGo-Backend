<script setup>
import { ref, onMounted, watch, reactive } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import { dateTimeFormat, formatCurrency } from '../../utils';


const couponDetail = ref([]);
const titleData = ref({});
const loading = ref(true);
const route = useRoute();

const couponId = route.params.id;
// const searchQuery = ref('');
const toast = useToast();
const fetchOrderCoupon = async () => {
    try {
        const response = await axios.get(`/api/coupon/detail/${couponId}`);
        couponDetail.value = response.data.orders;
        titleData.value = response.data;

        console.log(titleData);

    } catch (error) {
        console.error('Error fetching orders:', error);
    } finally {
        loading.value = false;
    }
};
// Gọi API khi component mounted
onMounted(async () => {
    await fetchOrderCoupon();
    feather.replace();
});

</script>
<template>
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="/admin">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="/admin/coupon">Danh sách mã giảm giá</a></li>

            <li class="breadcrumb-item">Chi tiết mã giảm giá </li>
        </ol>
    </nav>
    <h2 class="text-bold text-body-emphasis mb-2">Chi tiết mã giảm giá: {{ titleData.code }}</h2> <br>
    <div class="row">
        <div class="col-md-3 d-flex flex-column">
            <div class="mb-2">
                <div class="d-flex align-items-center mb-1">
                    <span class="me-2" data-feather="calendar" style="stroke-width:2.5;"></span>
                    <h6 class="mb-0">Hạn sử dụng</h6>
                </div>
                <div class="ms-4">
                    <p class="text-body-secondary mb-0 fs-8">{{ dateTimeFormat(titleData.start_date) }} - {{
                        dateTimeFormat(titleData.end_date) }}</p>
                </div>
            </div>

            <div class="mb-2">
                <div class="d-flex align-items-center mb-1">
                    <span class="me-2" data-feather="bookmark" style="stroke-width:2.5;"></span>
                    <h6 class="mb-0">Lượt dùng</h6>
                </div>
                <div class="ms-4">
                    <p class="text-body-secondary mb-0 fs-8">
                        {{ titleData?.orders?.length ?? 0 }} / {{ titleData.usage_limit }}
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-3 d-flex flex-column">

            <div class="mb-2">
                <div class="d-flex align-items-center mb-1">
                    <span class="me-2" data-feather="bar-chart-2" style="stroke-width:2.5;"></span>
                    <h6 class="mb-0">Mức giảm</h6>
                </div>
                <div class="ms-4">
                    <p class="text-body-secondary mb-0 fs-8">
                        {{ formatCurrency(titleData.discount_type) }} {{ titleData.type_unit == 1 ? '%' : 'đ' }}
                    </p>
                </div>
            </div>
            <div class="mb-2">
                <div class="d-flex align-items-center mb-1">
                    <span class="me-2" data-feather="credit-card" style="stroke-width:2.5;"></span>
                    <h6 class="mb-0">Phương thức thanh toán</h6>
                </div>
                <div class="ms-4">
                    <p class="text-body-secondary mb-0 fs-8">Thanh toán tiền mặt</p>
                </div>
            </div>

        </div>
        <div class="col-md-3  d-flex flex-column">
            <div class="mb-2">
                <div class="d-flex align-items-center mb-1">
                    <span class="me-2" data-feather="dollar-sign" style="stroke-width:2.5;"></span>
                    <h6 class="mb-0">Đơn tối thiểu</h6>
                </div>
                <div class="ms-4">
                    <p class="text-body-secondary mb-0 fs-8">{{ formatCurrency(titleData.min_order_value) }}
                        VND</p>
                </div>
            </div>


            <div class="mb-2">
                <div class="d-flex align-items-center mb-1">
                    <span class="me-2" data-feather="dollar-sign" style="stroke-width:2.5;"></span>
                    <h6 class="mb-0">Giảm tối đa</h6>
                </div>
                <div class="ms-4">
                    <p class="text-body-secondary mb-0 fs-8">{{ formatCurrency(titleData.max_value) }}
                        VND</p>
                </div>
            </div>
        </div>

        <div class="col-md-3  d-flex flex-column">
            <div class="mb-2">
                <div class="d-flex align-items-center mb-1">
                    <span class="me-2" data-feather="unlock" style="stroke-width:2.5;"></span>
                    <h6 class="mb-0">Trạng thái </h6>
                </div>
                <div class="ms-4">
                    <span class="fs-10 badge"
                        :class="titleData.status == 1 ? 'bg-success-subtle text-success-emphasis' : 'bg-danger-subtle text-danger-emphasis'">
                        {{ titleData.status == 1 ? 'Hoạt động' : 'Không hoạt động' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div>
        <!-- Table -->
        <div class="mx-n4 mx-lg-n6 px-4 px-lg-6 mb-9 bg-body-emphasis mt-2 position-relative top-1"
            id="list_users_container">
            <div class="table-responsive quote-table-container scrollbar ms-n1 ps-1">
                <table class="table table-hover table-sm fs-9 mb-0 text-truncate">
                    <thead>
                        <tr>
                            <th class="align-middle text-center text-uppercase">Stt</th>
                            <th class="align-middle text-start text-uppercase">Thông tin đơn hàng</th>
                            <th class="align-middle text-end text-uppercase">Tổng đơn giá</th>
                            <th class="align-middle text-end text-uppercase">Tổng giảm giá</th>
                            <th class="align-middle text-end text-uppercase">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody class="list-data" id="data_table_body">
                        <tr v-if="loading" class="loading-data">
                            <td class="text-center" colspan="13">
                                <div class="spinner-border text-info spinner-border-sm" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="couponDetail.length === 0 && !loading">
                            <td colspan="6" class="text-center fw-bold fs-7 text-danger">Chưa có dữ liệu</td>
                        </tr>
                        <tr v-else v-for="(detail, index) in couponDetail" :key="detail.id">
                            <td class="align-middle text-center">{{ index + 1 }}</td>
                            <td class="align-middle text-start">
                                <span class="align-middle text-start">
                                    <b>Mã đơn hàng:
                                    </b><router-link :to="`/admin/orders/${detail.id}`"> {{ detail.code }}
                                        <br></router-link>
                                </span>
                                <span class="align-middle text-start">
                                    <b>khách hàng:</b> {{ detail?.users?.name ?? "không rõ" }} <br />
                                </span>
                                <span class="align-middle text-start">
                                    <b>Ngày đặt:</b> {{ dateTimeFormat(detail.created_at) }} <br />
                                </span>
                            </td>
                            <td class="align-middle text-end">{{ formatCurrency(detail.total_price) }} {{
                                detail?.country?.current }}</td>
                            <td class="align-middle text-end">{{ formatCurrency(detail.discount_amount) }} {{
                                detail?.country?.current }}</td>
                            <td class="align-middle text-end">{{ formatCurrency(detail.final_total) }} {{
                                detail?.country?.current }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="paginations"></div>
        </div>
    </div>
</template>