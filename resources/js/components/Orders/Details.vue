<script setup>
import { ref, onMounted, watch, reactive } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import { dateTimeFormat, formatNumber } from '../../utils';
const ordersDetail = ref([]);
const titleData = ref({});
const loading = ref(true);
const statusOrder = ref([]);
const updateLoading = ref(false);
const route = useRoute();

const orderId = route.params.id;
// const searchQuery = ref('');
const toast = useToast();
const editForm = reactive({
    status: '',
    payment_status: '',
});
const paymentStatuses = [
    { id: 1, name: 'Đã thanh toán' },
    { id: 2, name: 'Chưa thanh toán' },
];

const getPaymentStatusInfo = (paymentStatus) => {
    const normalizedStatus = Number(paymentStatus);

    if (normalizedStatus === 1) {
        return { label: 'Đã thanh toán', color: 'rgb(8 205 47)' };
    }

    if (normalizedStatus === 0 || normalizedStatus === 2) {
        return { label: 'Chưa thanh toán', color: 'rgb(221 21 21)' };
    }

    return { label: 'Không xác định', color: '#6c757d' };
};

const getOrderStatusInfo = (statusId) => {
    switch (Number(statusId)) {
        case 1:
            return { text: 'Chờ kiểm tra', color: '#ffc107' };
        case 2:
            return { text: 'Đang chuẩn bị hàng', color: '#0dcaf0' };
        case 3:
            return { text: 'Đang giao hàng', color: '#17a2b8' };
        case 4:
            return { text: 'Đã giao hàng', color: '#28a745' };
        case 5:
            return { text: 'Đã hủy', color: '#dc3545' };
        default:
            return { text: 'Không xác định', color: '#6c757d' };
    }
};

const getPaymentMethodName = (paymentMethod) => {
    switch (Number(paymentMethod)) {
        case 1:
            return 'Thanh toán khi nhận hàng';
        case 2:
            return 'PayPal';
        case 3:
            return 'Chuyển khoản';
        default:
            return 'Không xác định';
    }
};
const fetchStatusOptions = async (status) => {
    const response = await axios.get('/api/orders/status', {
        params: {
            id: status
        }
    });
    statusOrder.value = response.data;
};
const fetchOrderDetail = async () => {
    try {
        const response = await axios.get(`/api/orders/detail/${orderId}`);
        ordersDetail.value = response.data.order_details.data;
        titleData.value = response.data.customer;
        editForm.status = '';
        editForm.payment_status = titleData.value.payment_status ?? 2;
        await fetchStatusOptions(titleData.value.status);
        // console.log(titleData.value);
    } catch (error) {
        console.error('Error fetching orders:', error);
    } finally {
        loading.value = false;
    }
};
const submitUpdateForm = async () => {
    try {
        updateLoading.value = true;
        const data = {
            payment_status: editForm.payment_status,
        };

        if (editForm.status !== '') {
            data.status = editForm.status;
        }

        await axios.post(`/api/orders/edit/${orderId}`, data);
        toast.success('Cập nhật đơn hàng thành công!');
        await fetchOrderDetail();
    } catch (error) {
        const message = error.response?.data?.message || 'Không thể cập nhật đơn hàng.';
        toast.error(message);
    } finally {
        updateLoading.value = false;
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
            <li class="breadcrumb-item">
                <router-link :to="{ name: 'dashboard' }">Trang chủ</router-link>
            </li>
            <li class="breadcrumb-item">
                <router-link :to="{ name: 'orders' }">Danh sách đơn hàng</router-link>
            </li>

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
                <span class="me-2" data-feather="user-check" style="stroke-width:2.5;"></span>
                <h6 class="mb-0">Tài khoản đặt</h6>
            </div>
            <div class="ms-4">
                <p class="text-body-secondary mb-0 fs-8">{{ titleData.account_name || 'Khách vãng lai' }}</p>
                <p v-if="titleData.account_email" class="text-body-secondary mb-0 fs-9">{{ titleData.account_email }}</p>
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
                <p class="text-body-secondary mb-0 fs-8 fw-bold"
                    :style="{ color: titleData.payment_status_color || getPaymentStatusInfo(titleData.payment_status).color }">
                    {{ titleData.payment_status_name || getPaymentStatusInfo(titleData.payment_status).label }}
                </p>
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
                <p class="text-body-secondary mb-0 fs-8">{{ dateTimeFormat(titleData.date, 'd-m-y h:m:s') }}</p>
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
                <p class="text-body-secondary mb-0 fs-8">
                    {{ titleData.payment_method_name || getPaymentMethodName(titleData.payment_method) }}
                </p>
            </div>
        </div>

        <div class="col-md-3 d-flex flex-column">
            <div class="d-flex align-items-center mb-1">
                <span class="me-2" data-feather="truck" style="stroke-width:2.5;"></span>
                <h6 class="mb-0">Trạng thái đơn hàng</h6>
            </div>
            <span class="fs-10 badge" :style="{
                backgroundColor: titleData.status_color || getOrderStatusInfo(titleData.status).color,
                maxWidth: '150px',
                whiteSpace: 'nowrap',
                overflow: 'hidden',
                textOverflow: 'ellipsis',
                padding: '4px 8px'
            }">
                {{ titleData.status_name || getOrderStatusInfo(titleData.status).text }}
            </span>
        </div>

    </div>

    <form class="row g-3 align-items-end mb-4" @submit.prevent="submitUpdateForm">
        <div class="col-12 col-md-4">
            <label class="form-label">Cập nhật trạng thái đơn hàng</label>
            <select class="form-select" v-model="editForm.status">
                <option value="">Giữ nguyên: {{ titleData.status_name || getOrderStatusInfo(titleData.status).text }}</option>
                <option v-for="status in statusOrder" :key="status.id" :value="status.id">
                    {{ status.name }}
                </option>
            </select>
            <small v-if="statusOrder.length === 0" class="text-body-secondary">
                Không có trạng thái để cập nhật.
            </small>
        </div>
        <div class="col-12 col-md-4">
            <label class="form-label">Cập nhật trạng thái thanh toán</label>
            <select class="form-select" v-model="editForm.payment_status">
                <option v-for="status in paymentStatuses" :key="status.id" :value="status.id">
                    {{ status.name }}
                </option>
            </select>
        </div>
        <div class="col-12 col-md-auto">
            <button type="submit" class="btn btn-primary" :disabled="updateLoading">
                {{ updateLoading ? 'Đang cập nhật...' : 'Cập nhật' }}
            </button>
        </div>
    </form>

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
