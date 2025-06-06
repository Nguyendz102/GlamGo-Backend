<script setup>
import { ref, onMounted, watch, reactive } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import { RouterLink, useRoute } from 'vue-router';
import Pagination from '../../components/Pagination.vue';
import { formatNumber } from '../../utils';
import { Modal } from 'bootstrap';

const orders = ref([]);
const total_order = ref('');
const total_sum = ref('');
const total_price_du_tinh = ref('');
const total_price_thuc_te = ref('');

const dataPanigate = ref([]);
const searchQuery = ref({ code: '', date: '' });
const loading = ref(true);
const editForm = reactive({
    id: '',
    status: ''
})
const currentPage = ref(1);

const statusOrder = ref([]);

const route = useRoute();
const startDate = route.query.start_date;
const endDate = route.query.end_date;
const toast = useToast();

const getPaymentStatus = (status) => {
    return status == 1
        ? { label: 'Đã thanh toán', color: 'rgb(8 205 47)' }
        : { label: 'Chưa thanh toán', color: 'rgb(221 21 21)' };
};
function getOrderStatusInfo(statusId) {
    switch (statusId) {
        case 1:
            return { text: "Chờ kiểm tra", color: "#ffc107" }; // vàng
        case 2:
            return { text: "Đang chuẩn bị hàng", color: "#0dcaf0" }; // xanh dương nhạt
        case 3:
            return { text: "Đang giao hàng", color: "#17a2b8" }; // xanh biển
        case 4:
            return { text: "Đã giao hàng", color: "#28a745" }; // xanh lá
        case 5:
            return { text: "Đã hủy", color: "#dc3545" }; // đỏ
        default:
            return { text: "Không xác định", color: "#6c757d" }; // xám
    }
}

const fetchOrder = async (page = 1) => {
    try {
        currentPage.value = page;
        const params = Object.fromEntries(
            Object.entries(searchQuery.value).filter(([_, value]) => value !== '')
        );
        if (startDate && endDate) {
            searchQuery.value.date = [startDate, endDate];
            params.date = [startDate, endDate];
        }
        params.page = page;
        const response = await axios.get('/api/orders', {
            params
        });
        orders.value = response.data.data;
        total_order.value = response.data.total
        total_price_thuc_te.value = response.data.total_price_thuc_te
        total_price_du_tinh.value = response.data.total_price_du_tinh
        total_sum.value = response.data.total_sum
        dataPanigate.value = response.data
    } catch (error) {
        // console.error('Error fetching orders:', error);
    } finally {
        loading.value = false;
    }
};


// Gọi API khi component mounted
onMounted(() => {
    fetchOrder();
});
const submitEditForm = async () => {
    try {
        const data = {
            status: editForm.status
        }
        const response = await axios.post(`/api/orders/edit/${editForm.id}`, data);
        toast.success('Cập nhật trạng thái đơn hàng thành công!')
        // Reset edit form
        Object.assign(editForm, {
            id: '',
            status: '',
        });

        const modal = document.getElementById('modalEditOrder');
        const modalInstance = Modal.getInstance(modal) || new Modal(modal);
        modalInstance.hide();
        fetchOrder(currentPage.value);
    } catch (error) {
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            console.error('Error updating category:', error);
        }
    }
};
const openModalUpdate = async (order) => {
    const response = await axios.get('/api/orders/status', {
        params: {
            id: order.status
        }
    });
    statusOrder.value = response.data;
    populateEditForm(order);
    const modal = document.getElementById('modalEditOrder');
    const editModel = new Modal(modal);
    editModel.show();
};
const populateEditForm = (order) => {
    Object.assign(editForm, {
        id: order.id,
        status: statusOrder.value[0].id,
    });
};
const resetFilters = () => {
    searchQuery.value = { code: '', date: '' };
    fetchOrder();
};
</script>
<template>
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="/dashboard">Trang chủ</a></li>
            <li class="breadcrumb-item">Danh sách đơn hàng</li>
        </ol>
    </nav>
    <h2 class="text-bold text-body-emphasis mb-5">Danh sách đơn hàng</h2>
    <div class="mb-3 thongke mt-4">
        <div>
            <div class="icon">
                <div class="icon-border bg-primary-subtle">
                    <i class="fab fa-codepen text-primary-emphasis"></i>
                </div>
            </div>
            <div class="body text-decoration-none">
                <p class="fw-bold m-0"><span>{{ formatNumber(total_order) }}</span> đơn hàng</p>
                <span class="fs-8 fw-bold text-body-highlight">Tổng số</span>
            </div>
        </div>
        <div>
            <div class="icon">
                <div class="icon-border bg-warning-subtle">
                    <i class="fab fa-codepen text-danger-emphasis"></i>
                </div>
            </div>
            <div class="body text-decoration-none">
                <p class="fw-bold m-0"><span>{{ formatNumber(total_sum) }}</span> Doanh thu</p>
                <span class="fs-8 fw-bold text-body-highlight">Tổng số</span>
            </div>
        </div>
        <div>
            <div class="icon">
                <div class="icon-border bg-success-subtle">
                    <i class="fab fa-codepen text-success-emphasis"></i>
                </div>
            </div>
            <div class="body text-decoration-none">
                <p class="fw-bold m-0"><span class="thongke-khongkhoa">{{ formatNumber(total_price_du_tinh) }}</span>
                    VND
                </p>
                <span class="fs-8 fw-bold text-body-highlight">Tổng số Doanh thu dự tính</span>
            </div>
        </div>
        <div>
            <div class="icon">
                <div class="icon-border bg-danger-subtle">
                    <i class="fab fa-codepen text-danger-emphasis"></i>
                </div>
            </div>
            <div class="body text-decoration-none">
                <p class="fw-bold m-0"><span>{{ formatNumber(total_price_thuc_te) }}</span> VND</p>
                <span class="fs-8 fw-bold text-body-highlight">Tổng số doanh thu thực nhận</span>
            </div>
        </div>

    </div>
    <div>
        <!-- Search -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div id="searchModel" class="col-9">
                <form class="row d-flex align-items-center" id="filter-form" @submit.prevent="fetchOrder">
                    <div class='col-12 col-md-4 mb-3'>
                        <input name="code_dh" v-model="searchQuery.code" placeholder="Mã đơn hàng" type="text"
                            class="form-control">
                    </div>
                    <div class='col-12 col-md-4 mb-3'>
                        <VueDatePicker v-model="searchQuery.date" format="dd-MM-yyyy" valueFormat="yyyy-mm-dd" range
                            placeholder="Chọn ngày"></VueDatePicker>
                    </div>
                    <div class="col-12 col-md-auto mb-3 d-flex justify-content-start">
                        <button type="submit" class="btn btn-sm btn-phoenix-info btn-filter me-2" title="Lọc">
                            <span class="fas fa-filter text-info fs-9 me-2"></span>Lọc
                        </button>
                        <button @click="resetFilters" class="btn btn-sm btn-phoenix-warning" type="button">Xoá
                            lọc</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Table -->
        <div class="mx-n4 mx-lg-n6 px-4 px-lg-6 mb-9 bg-body-emphasis mt-2 position-relative top-1"
            id="list_users_container">
            <div class="table-responsive quote-table-container scrollbar ms-n1 ps-1">
                <table class="table table-hover table-sm fs-9 mb-0 text-truncate">
                    <thead>
                        <tr>
                            <th class="align-middle text-center text-uppercase">Stt</th>
                            <th class="align-middle text-start text-uppercase">Ngày</th>
                            <th class="align-middle text-start text-uppercase">Mã ĐH</th>
                            <th class="align-middle text-start text-uppercase">Khách hàng</th>
                            <th class="align-middle text-start text-uppercase">Mã giảm giá</th>
                            <th class="align-middle text-end text-uppercase">Tổng tiền</th>
                            <th class="align-middle text-start text-uppercase">Phương thức thanh toán</th>
                            <th class="align-middle text-start text-uppercase">Trạng thái thanh toán</th>
                            <th class="align-middle text-start text-uppercase">Trạng thái đơn hàng</th>
                            <th class="align-middle text-start text-uppercase">ghi chú</th>
                            <th class="align-middle text-center text-uppercase">Hành động</th>
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
                        <tr v-if="orders.length === 0 && !loading">
                            <td colspan="11" class="text-center fw-bold fs-7 text-danger">Chưa có dữ liệu</td>
                        </tr>
                        <tr v-else v-for="(order, index) in orders" :key="order.id">
                            <td class="align-middle text-center">{{ index + 1 }}</td>
                            <td class="align-middle text-start">{{ order.created_at }}</td>
                            <td class="align-middle text-start">
                                <router-link :to="`/admin/orders/${order.id}`">{{ order.code
                                }}</router-link>
                            </td>
                            <td class="align-middle text-start">{{ order.name_cus }}</td>
                            <td class="align-middle text-start">{{ order.code_coupon }}</td>
                            <td class="align-middle text-end">{{ formatNumber(order.total) }} {{ order.sign }}</td>
                            <td class="align-middle text-start">Thanh toán khi nhận hàng</td>
                            <td class="align-middle text-start fw-bold"
                                :style="{ color: getPaymentStatus(order.payment_status).color }">
                                {{ getPaymentStatus(order.payment_status).label }}
                            </td>
                            <!-- <td class="align-middle text-start">{{ order.payment_status == 1 ? 'Đã thanh toán' : 'Chưa
                                thanh toán' }}</td> -->
                            <td class="align-middle text-start">
                                <span class="fs-10 badge"
                                    :style="{ backgroundColor: getOrderStatusInfo(order.status).color }">
                                    {{ getOrderStatusInfo(order.status).text }}
                                </span>
                            </td>
                            <td class="align-middle text-start">{{ order.note }}</td>
                            <td class="align-middle text-center">
                                <div class="position-relative">
                                    <button class="btn btn-edit-show btn-sm btn-phoenix-secondary text-info me-1 fs-10"
                                        @click="openModalUpdate(order)" type="button">
                                        <span class="fas far fa-edit"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>
            <Pagination v-if="dataPanigate" :response="dataPanigate" @getData="fetchOrder" />
        </div>

        <!-- phần cập nhật danh mục-->
        <div class="modal fade" id="modalEditOrder" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class=" modal-content form-open">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Cập nhật trạng thái đơn hàng</h5>
                        <button class="btn p-1 closeButton" type="button" data-bs-dismiss="modal" aria-label="Close">
                            <svg class="svg-inline--fa fa-xmark fs-9" aria-hidden="true" focusable="false"
                                data-prefix="fas" data-icon="xmark" role="img" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 320 512" data-fa-i2svg="">
                                <path fill="currentColor"
                                    d="M310.6 361.4c12.5 12.5 12.5 32.75 0 45.25C304.4 412.9 296.2 416 288 416s-16.38-3.125-22.62-9.375L160 301.3L54.63 406.6C48.38 412.9 40.19 416 32 416S15.63 412.9 9.375 406.6c-12.5-12.5-12.5-32.75 0-45.25l105.4-105.4L9.375 150.6c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0L160 210.8l105.4-105.4c12.5-12.5 32.75-12.5 45.25 0s12.5 32.75 0 45.25l-105.4 105.4L310.6 361.4z">
                                </path>
                            </svg>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form class="row g-3" @submit.prevent="submitEditForm">
                            <div class="col-sm-12 col-md-12">
                                <div class="form-floating">
                                    <select name="statusEdit" class="form-select" v-model="editForm.status">
                                        <option v-for="(status, index) in statusOrder" :key="status.id"
                                            :value="status.id">
                                            {{ status.name }}</option>
                                    </select>
                                    <label>Trạng thái</label>
                                </div>
                            </div>
                            <div class="col-12 gy-6">
                                <div class="row g-3 justify-content-center">
                                    <div class="col-auto">
                                        <button type="button" class="btn btn-close-model btn-secondary mx-1"
                                            data-bs-dismiss="modal">Huỷ
                                        </button>
                                        <button type="submit" class="btn btn-primary btn-submit mx-1"
                                            title="Cập nhật">Cập nhật</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<style scoped>
:deep(.dp__input) {
    font-size: 0.8rem !important;
}
</style>
