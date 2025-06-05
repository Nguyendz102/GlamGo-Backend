<script setup>
import { ref, onMounted } from 'vue';
import { dateTimeFormat, formatNumber } from '../../utils';
import Pagination from '../../components/Pagination.vue';

// khai báo
const history = ref([]);
const loading = ref(true);
const searchQuery = ref({ search: '', date: '' });
const dataPanigate = ref([]);
const fetchHistoryPrice = async (page = 1) => {
    // console.log(page);
    try {
        const params = Object.fromEntries(
            Object.entries(searchQuery.value).filter(([_, value]) => value !== '')
        );
        params.page = page;
        const response = await axios.get('/api/transactions/history-price', {
            params
        });
        history.value = response.data.data;
        dataPanigate.value = response.data;

    } catch (error) {
        console.error('Error fetching history:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchHistoryPrice();
});

const resetFilters = () => {
    searchQuery.value = { search: '', date: '' };
    fetchHistoryPrice();
};

</script>

<template>
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="/dashboard">Trang chủ</a></li>
            <li class="breadcrumb-item">Danh sách lịch sử thay đổi giá</li>
        </ol>
    </nav>
    <h2 class="text-bold text-body-emphasis mb-5">Danh sách lịch sử thay đổi giá</h2>
    <div>
        <!-- Search -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div id="searchModel" class="col-9">
                <form class="row d-flex align-items-center" id="filter-form" @submit.prevent="fetchHistoryPrice">
                    <div class='col-3 mb-3'>
                        <input v-model="searchQuery.search" name="search" placeholder="Tên hoặc mã SP" type="text"
                            class="form-control">
                    </div>

                    <div class='col-12 col-md-4 mb-3'>
                        <VueDatePicker v-model="searchQuery.date" format="dd-MM-yyyy" model-value-format="yyyy-mm-dd"
                            range placeholder="Chọn ngày thay đổi">
                        </VueDatePicker>
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
                            <th class="align-middle text-start text-uppercase">Sản phẩm</th>
                            <th class="align-middle text-start text-uppercase">Giá cũ</th>
                            <th class="align-middle text-start text-uppercase">Giá mới</th>
                            <th class="align-middle text-start text-uppercase">Ngày thay đổi</th>
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
                        <tr v-if="history.length === 0 && !loading">
                            <td colspan="7" class="text-center fw-bold fs-7 text-danger">Chưa có dữ liệu</td>
                        </tr>
                        <tr v-else v-for="(story, index) in history" :key="story.id">
                            <td class="align-middle text-center">{{ index + 1 }}</td>
                            <td class="align-middle text-start">
                                <strong>Tên Sp:</strong> {{ story?.product.name }}<br>
                                <strong>Mã:</strong> {{ story?.product.code }}<br>
                                <strong>Quốc gia:</strong> {{ story?.product?.country.name }}
                            </td>
                            <td class="align-middle text-start text-danger fw-bold">{{ formatNumber(story.price_old) }}
                                {{
                                    story?.product?.country.sign }}
                            </td>
                            <td class="align-middle text-start text-success fw-bold">{{ formatNumber(story.price) }} {{
                                story?.product?.country.sign }}
                            </td>
                            <td class="align-middle text-start">{{ dateTimeFormat(story.created_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination v-if="dataPanigate" :response="dataPanigate" @getData="fetchHistoryPrice" />
        </div>
    </div>
</template>
<style scoped>
:deep(.dp__input) {
    font-size: 0.8rem !important;
}
</style>