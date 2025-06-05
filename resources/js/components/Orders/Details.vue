<script setup>
import { ref, onMounted, watch, reactive } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import { dateTimeFormat, formatNumber } from '../../utils';
const ordersDetail = ref([]);
const titleData = ref({});
const loading = ref(true);
const route = useRoute();

const orderId = route.params.id;
// const searchQuery = ref('');
const toast = useToast();

function hienThiTrangThaiThanhToan(paymentStatus) {
    if (paymentStatus === 0) {
        return 'Chưa thanh toán';
    } else {
        return 'Đã thanh toán';
    }
}
const fetchOrderDetail = async () => {
    try {
        const response = await axios.get(`/api/orders/detail/${orderId}`);
        ordersDetail.value = response.data.order_details.data;
        titleData.value = response.data.customer;
        // console.log(titleData.value);
    } catch (error) {
        console.error('Error fetching orders:', error);
    } finally {
        loading.value = false;
    }
};
// Gọi API khi component mounted
onMounted(() => {
    fetchOrderDetail();
});
</script>
<template>
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="/dashboard">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="/admin/orders">Danh sách đơn hàng</a></li>

            <li class="breadcrumb-item">Chi tiết đơn hàng </li>
        </ol>
    </nav>
    <h2 class="text-bold text-body-emphasis mb-3">Chi tiết đơn hàng: {{ titleData.code_order }}</h2>
    <div class="row g-4 align-items-start">
        <div class="col-md-3 d-flex flex-column">
            <div class="d-flex align-items-center mb-1">
                <span class="me-2" data-feather="user" style="stroke-width:2.5;"></span>
                <h6 class="mb-0">Khách hàng</h6>
            </div>
            <div class="ms-4">
                <p class="text-body-secondary mb-0 fs-8">{{ titleData.name }}</p>
            </div>
        </div>

        <div class="col-md-3 d-flex flex-column">
            <div class="d-flex align-items-center mb-1">
                <span class="me-2" data-feather="phone" style="stroke-width:2.5;"></span>
                <h6 class="mb-0">Phone</h6>
            </div>
            <p class="fs-8 ms-4">{{ titleData.phone }}</p>
        </div>

        <div class="col-md-3 d-flex flex-column">
            <div class="d-flex align-items-center mb-1">
                <span class="me-2 fas fa-money-check-alt" style="stroke-width:2.5;"></span>
                <h6 class="mb-0">Trạng thái thanh toán</h6>
            </div>
            <div class="ms-4">
                <p class="text-body-secondary mb-0 fs-8">{{ hienThiTrangThaiThanhToan(titleData.payment_status) }}</p>
            </div>
        </div>


        <div class="col-md-3 d-flex flex-column">
            <div class="d-flex align-items-center mb-1">
                <span class="me-2" data-feather="home" style="stroke-width:2.5;"></span>
                <h6 class="mb-0">Địa chỉ</h6>
            </div>
            <div class="ms-4">
                <p class="text-body-secondary mb-0 fs-8">{{ titleData.address }}</p>
            </div>
        </div>

    </div>

    <div class="row g-4 align-items-start mb-3">
        <div class="col-md-3 d-flex flex-column">
            <div class="d-flex align-items-center mb-1">
                <span class="me-2" data-feather="calendar" style="stroke-width:2.5;"></span>
                <h6 class="mb-0">Ngày đặt hàng</h6>
            </div>
            <div class="ms-4">
                <p class="text-body-secondary mb-0 fs-8">{{ dateTimeFormat(titleData.date) }}</p>
            </div>
        </div>

        <div class="col-md-3 d-flex flex-column">
            <div class="d-flex align-items-center mb-1">
                <span class="me-2" data-feather="bookmark" style="stroke-width:2.5;"></span>
                <h6 class="mb-0">Mã giảm giá</h6>
            </div>
            <div class="ms-4">
                <p class="text-body-secondary mb-0 fs-8">{{ titleData.discout_code }}</p>
            </div>
        </div>

        <div class="col-md-3 d-flex flex-column">
            <div class="d-flex align-items-center mb-1">
                <span class="me-2" data-feather="credit-card" style="stroke-width:2.5;"></span>
                <h6 class="mb-0">Phương thức thanh toán</h6>
            </div>
            <div class="ms-4">
                <p class="text-body-secondary mb-0 fs-8">Thanh toán khi nhận hàng</p>
            </div>
        </div>

        <div class="col-md-3 d-flex flex-column">
            <div class="d-flex align-items-center mb-1">
                <span class="me-2" data-feather="truck" style="stroke-width:2.5;"></span>
                <h6 class="mb-0">Trạng thái</h6>
            </div>
            <span class="fs-10 badge" :style="{
                backgroundColor: titleData.status_color,
                maxWidth: '150px',
                whiteSpace: 'nowrap',
                overflow: 'hidden',
                textOverflow: 'ellipsis',
                padding: '4px 8px'
            }">
                {{ titleData.status_name }}
            </span>
        </div>

    </div>

    <div class="row g-5 gy-7 ">
        <!-- Table -->
        <div class="col-12 col-xl-8 col-xxl-9">
            <div class="card mx-n4 px-4 mb-9 bg-body-emphasis  position-relative top-1" id="list_users_container">
                <div class="table-responsive quote-table-container scrollbar ms-n1 ps-1">
                    <table class="table table-hover table-sm fs-9 mb-0 text-truncate">
                        <thead>
                            <tr>
                                <th class="align-middle text-center text-uppercase">Stt</th>
                                <th class="align-middle text-center text-uppercase">Ảnh sản phẩm</th>
                                <th class="align-middle text-start text-uppercase">Thông tin sản phẩm</th>
                                <th class="align-middle text-end text-uppercase">Đơn giá</th>
                                <th class="align-middle text-end text-uppercase">Số lượng</th>
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
                            <tr v-else v-for="(detail, index) in ordersDetail" :key="detail.id">
                                <td class="align-middle text-center">{{ index + 1 }}</td>
                                <td class="align-middle text-center">
                                    <img :src="`/storage/${detail.product_img || 'categories/null.jpg'}`"
                                        alt="Product Image" style="border-radius: 10px;" width="90" height="70">
                                </td>
                                <td class="align-middle text-start">
                                    <span class="align-middle text-start">
                                        <b>Tên sản phẩm:</b> {{ detail.name }} <br />
                                    </span>
                                    <span class="align-middle text-start">
                                        <b>Mã sản phẩm:</b> {{ detail.code }} <br />
                                    </span>
                                    <span class="align-middle text-start">
                                        <template v-for="(attributeGroup, groupIndex) in detail?.order_attributes"
                                            :key="groupIndex">
                                            <template v-for="(attribute, attrIndex) in attributeGroup"
                                                :key="`${groupIndex}-${attrIndex}`">
                                                <b>{{ attribute?.attribute_name }}:</b> {{ attribute?.attribute_value }}
                                                <br />
                                            </template>
                                        </template>
                                    </span>

                                </td>
                                <td class="align-middle text-end">{{ formatNumber(detail.price) }} {{
                                    detail.current_coutry }}</td>
                                <td class="align-middle text-end">{{ detail.quantity }}</td>
                                <td class="align-middle text-end">{{ formatNumber(detail.total_price) }} {{
                                    detail.current_coutry }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="paginations"></div>
            </div>
        </div>
        <div class="col-12 col-xl-4 col-xxl-3 ">
            <div class="row">
                <div class="col-12">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h3 class="card-title mb-4">Tổng đơn hàng</h3>
                            <div>
                                <div class="d-flex justify-content-between">
                                    <p class="text-body fw-semibold">Tổng đơn giá :</p>
                                    <p class="text-body-emphasis fw-semibold">
                                        {{ formatNumber(titleData.total_price) }} {{ titleData.current_coutry }}</p>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <p class="text-body fw-semibold">Tổng giảm giá :</p>
                                    <p class="text-danger fw-semibold">{{ formatNumber(titleData.total_discount) }}
                                    </p>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <p class="text-body fw-semibold">Phí ship :</p>
                                    <p class="text-body-emphasis fw-semibold">0</p>
                                </div>
                            </div>
                            <div
                                class="d-flex justify-content-between border-top border-translucent border-dashed pt-4">
                                <h4 class="mb-0">Thành tiền :</h4>
                                <h4 class="mb-0">{{ formatNumber(titleData.thanh_tien) }} {{ titleData.current_coutry
                                    }}
                                </h4>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>