<script setup>
import { ref, onMounted } from 'vue';
import { dateTimeFormat, formatNumber } from '../../utils';
import { useRoute } from 'vue-router';

import Pagination from '../../components/Pagination.vue';
const transactions = ref([]);
const loading = ref(true);
const searchQuery = ref({ search: '', date: '' });
const isReadOnly = ref(false);
const total_price = ref('');
const dataPanigate = ref([])
const route = useRoute();
const startDate = route.query.start_date;
const endDate = route.query.end_date;

// Fetch 
const fetchTransactions = async (page = 1) => {
    try {
        const params = Object.fromEntries(
            Object.entries(searchQuery.value).filter(([_, value]) => value !== '')
        );
        params.page = page;
        if (startDate && endDate) {
            searchQuery.value.date = [startDate, endDate];
            params.dates = [startDate, endDate];
        }
        const response = await axios.get('/api/transactions', { params });
        transactions.value = response.data.data;
        total_price.value = response.data.total_amount;
        dataPanigate.value = response.data;
    } catch (error) {
        console.error('Error fetching transactions:', error);
    } finally {
        loading.value = false;
    }
};
const getPaymentStatus = (status) => {
    return status == 1
        ? { label: 'Đã thanh toán', color: 'rgb(8 205 47)' } // xanh
        : { label: 'Chưa thanh toán', color: 'rgb(200, 0, 0)' }; // đỏ đậm
};
onMounted(() => {
    fetchTransactions();
});
const resetFilters = () => {
    searchQuery.value = { search: '', date: '' };
    fetchTransactions();
};

</script>

<template>
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="/dashboard">Trang chủ</a></li>
            <li class="breadcrumb-item">Danh sách giao dịch</li>
        </ol>
    </nav>
    <h2 class="text-bold text-body-emphasis mb-5">Danh sách giao dịch</h2>
    <div class="mb-3 thongke mt-4">
        <div>
            <div class="icon">
                <div class="icon-border bg-primary-subtle">
                    <i class="fab fa-codepen text-primary-emphasis"></i>
                </div>
            </div>
            <div class="body text-decoration-none">
                <p class="fw-bold m-0"><span>{{ formatNumber(total_price) }}</span> VND</p>
                <span class="fs-8 fw-bold text-body-highlight">Tổng số</span>
            </div>
        </div>
    </div>

    <div>
        <!-- Search -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div id="searchModel" class="col-9">
                <form class="row d-flex align-items-center" id="filter-form" @submit.prevent="fetchTransactions">
                    <div class='col-3 mb-3'>
                        <input v-model="searchQuery.search" name="search" placeholder="Mã đơn hàng" type="text"
                            class="form-control">
                    </div>

                    <div class='col-12 col-md-4 mb-3'>
                        <VueDatePicker v-model="searchQuery.date" format="dd-MM-yyyy" valueFormat="yyyy-mm-dd" range
                            placeholder="Chọn ngày"></VueDatePicker>
                    </div>

                    <div class="col-auto mb-3 d-flex justify-content-start">
                        <button type="submit" class="btn btn-sm btn-phoenix-info btn-filter me-2" title="Lọc">
                            <span class="fas fa-filter text-info fs-9 me-2"></span>Lọc
                        </button>
                        <button class="btn btn-sm btn-phoenix-warning" type="button" @click="resetFilters();">Xoá
                            lọc</button>
                    </div>
                </form>
            </div>
            <!-- btn add -->
        </div>

        <!-- Table -->
        <div class="mx-n4 mx-lg-n6 px-4 px-lg-6 mb-9 bg-body-emphasis mt-2 position-relative top-1"
            id="list_users_container">
            <div class="table-responsive quote-table-container scrollbar ms-n1 ps-1">
                <table class="table table-hover table-sm fs-9 mb-0">
                    <thead>
                        <tr>
                            <th class="align-middle text-center text-uppercase">Stt</th>
                            <th class="align-middle text-start text-uppercase">Ngày</th>
                            <th class="align-middle text-start text-uppercase">Mã đơn hàng</th>
                            <th class="align-middle text-start text-uppercase">Phương thức thanh toán</th>
                            <th class="align-middle text-end text-uppercase">Tổng tiền</th>
                            <!-- <th class="align-middle text-end text-uppercase">Tỉ giá</th> -->
                            <!-- <th class="align-middle text-end text-uppercase">quy đổi</th> -->
                            <th class="align-middle text-center text-uppercase">Trạng thái</th>
                            <!-- <th class="align-middle text-center text-uppercase">Hành động</th> -->
                        </tr>
                    </thead>
                    <tbody class="list-data" id="data_table_body">
                        <tr v-if="loading" class="loading-data">
                            <td class="text-center" colspan="8">
                                <div class="spinner-border text-info spinner-border-sm" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="transactions.length === 0 && !loading">
                            <td colspan="9" class="text-center fw-bold fs-7 text-danger">Chưa có dữ liệu</td>
                        </tr>
                        <tr v-else v-for="(transaction, index) in transactions" :key="transaction.id">
                            <td class="align-middle text-center">{{ index + 1 }}</td>
                            <td class="align-middle text-start">{{ dateTimeFormat(transaction.created_at) }}</td>
                            <td class="align-middle text-start">
                                <router-link v-if="transaction?.order?.id" :to="`/orders/${transaction.order.id}`">
                                    {{ transaction.order.code ?? '' }}
                                </router-link>
                                <span v-else></span>
                            </td>
                            <td class="align-middle text-start">
                                <!-- {{ transaction.payment_method == 1 ? 'Thanh toán khi nhận hàng' : 'không rõ' }} -->
                                Thanh toán khi nhận hàng
                            </td>
                            <td class="align-middle text-end">{{ formatNumber(transaction.amount) }}
                                {{ transaction?.order?.order_items?.[0].product?.country?.sign }}
                            </td>
                            <!-- <td class="align-middle text-end">{{
                                formatNumber(transaction?.order?.order_items?.[0].product?.country?.rate) }}
                            </td>
                            <td class="align-middle text-end">{{ formatNumber(transaction.total_price) }} đ
                            </td> -->
                            <td class="align-middle text-center fw-bold"
                                :style="{ color: getPaymentStatus(transaction.status_id).color }">
                                {{ getPaymentStatus(transaction.status_id).label }}
                            </td>

                        </tr>

                    </tbody>
                </table>
            </div>
            <Pagination v-if="dataPanigate" :response="dataPanigate" @getData="fetchTransactions" />

        </div>
    </div>
</template>

<style scoped>
:deep(.dp__input) {
    font-size: 0.8rem !important;
}
</style>