<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import { Modal } from 'bootstrap';
import { useToast } from 'vue-toastification';
import Pagination from '../../components/Pagination.vue';
import { formatNumber } from '../../utils';

const toast = useToast();
const customers = ref([]);
const dataPanigate = ref(null);
const loading = ref(true);
const selectedCustomer = ref(null);
const detailLoading = ref(false);
const currentPage = ref(1);
const activeCount = ref(0);
const lockedCount = ref(0);

const searchQuery = reactive({
    keyword: '',
    status: '',
    date: '',
});

const statusInfo = (statusId) => Number(statusId) === 1
    ? { label: 'Hoạt động', className: 'bg-success-subtle text-success-emphasis' }
    : { label: 'Đã khóa', className: 'bg-danger-subtle text-danger-emphasis' };

const orderStatusText = (statusId) => {
    switch (Number(statusId)) {
        case 1:
            return 'Chờ kiểm tra';
        case 2:
            return 'Đang chuẩn bị';
        case 3:
            return 'Đang giao';
        case 4:
            return 'Đã giao';
        case 5:
            return 'Đã hủy';
        default:
            return 'Không xác định';
    }
};

const fetchCustomers = async (page = 1) => {
    loading.value = true;
    currentPage.value = page;

    try {
        const params = Object.fromEntries(
            Object.entries(searchQuery).filter(([_, value]) => value !== '' && value !== null)
        );
        params.page = page;

        const response = await axios.get('/api/customers', { params });
        customers.value = response.data.data;
        dataPanigate.value = response.data;
        activeCount.value = response.data.active_count || 0;
        lockedCount.value = response.data.locked_count || 0;
    } catch (error) {
        toast.error('Không tải được danh sách khách hàng.');
    } finally {
        loading.value = false;
    }
};

const resetFilters = () => {
    searchQuery.keyword = '';
    searchQuery.status = '';
    searchQuery.date = '';
    fetchCustomers();
};

const openCustomerDetail = async (customer) => {
    selectedCustomer.value = customer;
    detailLoading.value = true;

    const modal = document.getElementById('customerDetailModal');
    const detailModal = Modal.getInstance(modal) || new Modal(modal);
    detailModal.show();

    try {
        const response = await axios.get(`/api/customers/${customer.id}`);
        selectedCustomer.value = response.data;
    } catch (error) {
        toast.error('Không tải được thông tin khách hàng.');
    } finally {
        detailLoading.value = false;
    }
};

const updateCustomerStatus = async (customer, statusId) => {
    try {
        await axios.patch(`/api/customers/${customer.id}/status`, {
            status_id: statusId,
        });

        toast.success(statusId === 1 ? 'Đã mở khóa tài khoản.' : 'Đã khóa tài khoản.');
        if (selectedCustomer.value?.id === customer.id) {
            selectedCustomer.value.status_id = statusId;
        }
        fetchCustomers(currentPage.value);
    } catch (error) {
        toast.error('Không cập nhật được trạng thái khách hàng.');
    }
};

onMounted(() => {
    fetchCustomers();
});
</script>

<template>
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
                <router-link :to="{ name: 'dashboard' }">Trang chủ</router-link>
            </li>
            <li class="breadcrumb-item">Khách hàng</li>
        </ol>
    </nav>

    <h2 class="text-bold text-body-emphasis mb-5">Thông tin khách hàng</h2>

    <div class="mb-3 thongke mt-4">
        <div>
            <div class="icon">
                <div class="icon-border bg-primary-subtle">
                    <i class="fas fa-users text-primary-emphasis"></i>
                </div>
            </div>
            <div class="body text-decoration-none">
                <p class="fw-bold m-0"><span>{{ formatNumber(dataPanigate?.total || 0) }}</span> khách hàng</p>
                <span class="fs-8 fw-bold text-body-highlight">Tổng số</span>
            </div>
        </div>
        <div>
            <div class="icon">
                <div class="icon-border bg-success-subtle">
                    <i class="fas fa-unlock text-success-emphasis"></i>
                </div>
            </div>
            <div class="body text-decoration-none">
                <p class="fw-bold m-0"><span>{{ formatNumber(activeCount) }}</span> tài khoản</p>
                <span class="fs-8 fw-bold text-body-highlight">Đang hoạt động</span>
            </div>
        </div>
        <div>
            <div class="icon">
                <div class="icon-border bg-danger-subtle">
                    <i class="fas fa-lock text-danger-emphasis"></i>
                </div>
            </div>
            <div class="body text-decoration-none">
                <p class="fw-bold m-0"><span>{{ formatNumber(lockedCount) }}</span> tài khoản</p>
                <span class="fs-8 fw-bold text-body-highlight">Đã khóa</span>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div id="searchModel" class="col-12">
            <form class="row gy-2 gx-3 align-items-center" @submit.prevent="fetchCustomers">
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <input v-model="searchQuery.keyword" type="text" class="form-control"
                        placeholder="Tên, email, SĐT, mã KH">
                </div>
                <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                    <select v-model="searchQuery.status" class="form-select">
                        <option value="">Tất cả trạng thái</option>
                        <option value="1">Hoạt động</option>
                        <option value="0">Đã khóa</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <VueDatePicker v-model="searchQuery.date" format="dd-MM-yyyy" valueFormat="yyyy-mm-dd" range
                        placeholder="Ngày đăng ký"></VueDatePicker>
                </div>
                <div class="col-12 col-sm-6 col-md-auto d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-phoenix-info" title="Lọc">
                        <span class="fas fa-filter text-info fs-9 me-2"></span>Lọc
                    </button>
                    <button @click="resetFilters" class="btn btn-sm btn-phoenix-warning" type="button">
                        Xóa lọc
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="mx-n4 mx-lg-n6 px-4 px-lg-6 mb-9 bg-body-emphasis mt-2 position-relative top-1">
        <div class="table-responsive quote-table-container scrollbar ms-n1 ps-1">
            <table class="table table-hover table-sm fs-9 mb-0 text-truncate">
                <thead>
                    <tr>
                        <th class="align-middle text-center text-uppercase">STT</th>
                        <th class="align-middle text-start text-uppercase">Mã KH</th>
                        <th class="align-middle text-start text-uppercase">Khách hàng</th>
                        <th class="align-middle text-start text-uppercase">Email</th>
                        <th class="align-middle text-start text-uppercase">SĐT</th>
                        <th class="align-middle text-end text-uppercase">Đơn hàng</th>
                        <th class="align-middle text-end text-uppercase">Tổng chi</th>
                        <th class="align-middle text-center text-uppercase">Trạng thái</th>
                        <th class="align-middle text-center text-uppercase">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loading">
                        <td class="text-center" colspan="9">
                            <div class="spinner-border text-info spinner-border-sm" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </td>
                    </tr>
                    <tr v-else-if="customers.length === 0">
                        <td colspan="9" class="text-center fw-bold fs-7 text-danger">Chưa có dữ liệu</td>
                    </tr>
                    <tr v-else v-for="(customer, index) in customers" :key="customer.id">
                        <td class="align-middle text-center">{{ index + 1 }}</td>
                        <td class="align-middle text-start">{{ customer.code || '-' }}</td>
                        <td class="align-middle text-start">
                            <button class="btn btn-link p-0 text-decoration-none fw-semibold" type="button"
                                @click="openCustomerDetail(customer)">
                                {{ customer.name || customer.user_name || 'Khách hàng' }}
                            </button>
                            <small class="d-block text-body-secondary">{{ customer.user_name }}</small>
                        </td>
                        <td class="align-middle text-start">{{ customer.email }}</td>
                        <td class="align-middle text-start">{{ customer.phone || '-' }}</td>
                        <td class="align-middle text-end">{{ formatNumber(customer.total_orders) }}</td>
                        <td class="align-middle text-end">{{ formatNumber(customer.total_spent) }} VND</td>
                        <td class="align-middle text-center">
                            <span class="fs-10 badge" :class="statusInfo(customer.status_id).className">
                                {{ statusInfo(customer.status_id).label }}
                            </span>
                        </td>
                        <td class="align-middle text-center">
                            <button class="btn btn-sm btn-phoenix-secondary text-info me-1" type="button"
                                title="Xem chi tiết" @click="openCustomerDetail(customer)">
                                <span class="fas fa-eye"></span>
                            </button>
                            <button v-if="Number(customer.status_id) === 1"
                                class="btn btn-sm btn-phoenix-secondary text-danger" type="button"
                                title="Khóa tài khoản" @click="updateCustomerStatus(customer, 0)">
                                <span class="fas fa-lock"></span>
                            </button>
                            <button v-else class="btn btn-sm btn-phoenix-secondary text-success" type="button"
                                title="Mở khóa tài khoản" @click="updateCustomerStatus(customer, 1)">
                                <span class="fas fa-unlock"></span>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pagination v-if="dataPanigate" :response="dataPanigate" @getData="fetchCustomers" />
    </div>

    <div class="modal fade" id="customerDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content form-open">
                <div class="modal-header">
                    <h5 class="modal-title">Chi tiết khách hàng</h5>
                    <button class="btn p-1 closeButton" type="button" data-bs-dismiss="modal" aria-label="Close">
                        <span class="fas fa-times fs-9"></span>
                    </button>
                </div>
                <div class="modal-body">
                    <div v-if="detailLoading" class="text-center py-5">
                        <div class="spinner-border text-info spinner-border-sm" role="status"></div>
                    </div>
                    <div v-else-if="selectedCustomer">
                        <div class="row g-4">
                            <div class="col-12 col-lg-5">
                                <h6 class="text-uppercase text-body-tertiary">Thông tin tài khoản</h6>
                                <dl class="row mb-0 fs-9">
                                    <dt class="col-4 text-body-secondary">Mã KH</dt>
                                    <dd class="col-8">{{ selectedCustomer.code || '-' }}</dd>
                                    <dt class="col-4 text-body-secondary">Họ tên</dt>
                                    <dd class="col-8">{{ selectedCustomer.name || '-' }}</dd>
                                    <dt class="col-4 text-body-secondary">Tên đăng nhập</dt>
                                    <dd class="col-8">{{ selectedCustomer.user_name || '-' }}</dd>
                                    <dt class="col-4 text-body-secondary">Email</dt>
                                    <dd class="col-8">{{ selectedCustomer.email || '-' }}</dd>
                                    <dt class="col-4 text-body-secondary">SĐT</dt>
                                    <dd class="col-8">{{ selectedCustomer.phone || '-' }}</dd>
                                    <dt class="col-4 text-body-secondary">Địa chỉ</dt>
                                    <dd class="col-8 text-wrap">{{ selectedCustomer.address || '-' }}</dd>
                                    <dt class="col-4 text-body-secondary">Ngày tạo</dt>
                                    <dd class="col-8">{{ selectedCustomer.created_at || '-' }}</dd>
                                    <dt class="col-4 text-body-secondary">Trạng thái</dt>
                                    <dd class="col-8">
                                        <span class="fs-10 badge" :class="statusInfo(selectedCustomer.status_id).className">
                                            {{ statusInfo(selectedCustomer.status_id).label }}
                                        </span>
                                    </dd>
                                </dl>
                                <div class="mt-3">
                                    <button v-if="Number(selectedCustomer.status_id) === 1"
                                        class="btn btn-sm btn-phoenix-danger" type="button"
                                        @click="updateCustomerStatus(selectedCustomer, 0)">
                                        <span class="fas fa-lock me-2"></span>Khóa tài khoản
                                    </button>
                                    <button v-else class="btn btn-sm btn-phoenix-success" type="button"
                                        @click="updateCustomerStatus(selectedCustomer, 1)">
                                        <span class="fas fa-unlock me-2"></span>Mở khóa tài khoản
                                    </button>
                                </div>
                            </div>
                            <div class="col-12 col-lg-7">
                                <div class="row g-3 mb-4">
                                    <div class="col-6">
                                        <div class="border rounded-2 p-3 h-100">
                                            <div class="fs-8 text-body-secondary">Tổng đơn hàng</div>
                                            <div class="fs-6 fw-bold">{{ formatNumber(selectedCustomer.total_orders) }}</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="border rounded-2 p-3 h-100">
                                            <div class="fs-8 text-body-secondary">Tổng chi tiêu</div>
                                            <div class="fs-6 fw-bold">{{ formatNumber(selectedCustomer.total_spent) }} VND</div>
                                        </div>
                                    </div>
                                </div>

                                <h6 class="text-uppercase text-body-tertiary">Sổ địa chỉ</h6>
                                <div v-if="!selectedCustomer.addresses?.length" class="text-body-secondary fs-9 mb-4">
                                    Chưa có địa chỉ lưu.
                                </div>
                                <div v-else class="table-responsive mb-4">
                                    <table class="table table-sm fs-9">
                                        <thead>
                                            <tr>
                                                <th>Nhãn</th>
                                                <th>Người nhận</th>
                                                <th>SĐT</th>
                                                <th>Địa chỉ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="address in selectedCustomer.addresses" :key="address.id">
                                                <td>
                                                    {{ address.label || '-' }}
                                                    <span v-if="address.is_default"
                                                        class="badge bg-primary-subtle text-primary-emphasis ms-1">Mặc định</span>
                                                </td>
                                                <td>{{ address.recipient_name }}</td>
                                                <td>{{ address.phone }}</td>
                                                <td class="text-wrap">
                                                    {{ [address.address_line, address.ward, address.district, address.province, address.country].filter(Boolean).join(', ') }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <h6 class="text-uppercase text-body-tertiary">Đơn hàng gần đây</h6>
                                <div v-if="!selectedCustomer.orders?.length" class="text-body-secondary fs-9">
                                    Chưa có đơn hàng.
                                </div>
                                <div v-else class="table-responsive">
                                    <table class="table table-sm fs-9">
                                        <thead>
                                            <tr>
                                                <th>Ngày</th>
                                                <th>Mã ĐH</th>
                                                <th class="text-end">Tổng tiền</th>
                                                <th>Trạng thái</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="order in selectedCustomer.orders" :key="order.id">
                                                <td>{{ order.created_at }}</td>
                                                <td>
                                                    <router-link :to="`/admin/orders/${order.id}`">
                                                        {{ order.code }}
                                                    </router-link>
                                                </td>
                                                <td class="text-end">{{ formatNumber(order.total_price) }} VND</td>
                                                <td>{{ orderStatusText(order.status) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"></div>
            </div>
        </div>
    </div>
</template>

<style scoped>
:deep(.dp__input) {
    font-size: 0.8rem !important;
}
</style>
