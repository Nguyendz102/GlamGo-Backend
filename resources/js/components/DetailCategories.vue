<script setup>
import { ref, onMounted, watch, reactive } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import { formatNumber } from '../utils';

const detailCategories = ref([]);
const loading = ref(true);
const errors = ref({});
const toast = useToast();
const currentPage = ref(1);


const fetchDetailCategories = async (page = 1) => {
    try {
        currentPage.value = page
        const response = await axios.get('/api/categories/detail', {
            params: { page },
        });
        detailCategories.value = response.data;
    } catch (error) {
        console.error('Error fetching countries:', error);
        toast.error('Có lỗi xảy ra!');
    } finally {
        loading.value = false;
    }
};

// Gọi API khi component mounted
onMounted(() => {
    fetchDetailCategories();
});

</script>
<template>
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="/dashboard">Trang chủ</a></li>
            <li class="breadcrumb-item">Thống kê danh mục</li>
        </ol>
    </nav>
    <h2 class="text-bold text-body-emphasis mb-5">Thống kê danh mục</h2>
    <div>
        <!-- Table -->
        <div class="mx-n4 mx-lg-n6 px-4 px-lg-6 mb-9 bg-body-emphasis mt-2 position-relative top-1"
            id="list_users_container">
            <div class="table-responsive quote-table-container scrollbar ms-n1 ps-1">
                <table class="table table-hover table-sm fs-9 mb-0 text-truncate">
                    <thead>
                        <tr>
                            <th class="align-middle text-center text-uppercase">Stt</th>
                            <th class="align-middle text-start text-uppercase">Ảnh</th>
                            <th class="align-middle text-start text-uppercase">Danh mục</th>
                            <th class="align-middle text-end text-uppercase">Số sản phẩm</th>
                            <th class="align-middle text-end text-uppercase">Giá trị</th>
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
                        <tr v-if="detailCategories.length === 0 && !loading">
                            <td colspan="8" class="text-center fw-bold fs-7 text-danger">Chưa có dữ liệu</td>
                        </tr>
                        <tr v-else v-for="(categories, index) in detailCategories" :key="categories.category_id">
                            <td class="align-middle text-center">{{ index + 1 }}</td>
                            <td class="align-middle text-start">
                                <img :src="categories?.images" alt="" class="rounded-circle" width="50px" height="50px">
                            </td>
                            <td class="align-middle text-start">
                                <router-link :to="{
                                    name: 'products', query: {
                                        category_id: categories.id,
                                    }
                                }">
                                    {{ categories?.name }}
                                </router-link>
                            </td>
                            <td class="align-middle text-end">{{ categories.count }}</td>
                            <td class="align-middle text-end">{{ formatNumber(categories.price) + ' VND' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
