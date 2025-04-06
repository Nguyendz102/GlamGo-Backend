<script setup>
import { ref, onMounted, reactive } from 'vue';
import axios from 'axios';
import { useRoute } from 'vue-router';
import { useToast } from 'vue-toastification';
import { Modal } from 'bootstrap';
import { formatBalance } from '../../utils';
import Pagination from '../Pagination.vue';

const products = ref([]);
const route = useRoute();
const dataPanigate = ref([])
const loading = ref(true);
const errors = ref({});
const toast = useToast();
const searchQuery = ref('');
const productId = route.params.id;
const productName = route.params.name;
const showPriceInput = ref(false);
const sign = ref('')
const form = reactive({
    name: '',
    price: '',
    product_id: productId,
});
const editForm = reactive({
    id: '',
    name: '',
    price: '',
    type: 0,
    product_id: productId,
});

const togglePriceInput = () => {
    form.price = ''
    showPriceInput.value = !showPriceInput.value;
};

const fetchProductAttribute = async (page = 1) => {

    try {
        const response = await axios.get(`/api/product-attribute/${productId}`, {
            params: {
                name: searchQuery.value,
                page: page

            }
        });
        products.value = response.data.data;
        sign.value = response.data.sign
        dataPanigate.value = response.data
    } catch (error) {
        console.error('Error fetching countries:', error);
        toast.error('Có lỗi xảy ra!');
    } finally {
        loading.value = false;
    }
};

// // Gọi API khi component mounted
onMounted(() => {
    fetchProductAttribute();
});


const openModalAdd = () => {
    errors.value = {};
    form.value = {};
    const modal = document.getElementById('addModel');
    const addModel = new Modal(modal);
    addModel.show();
}

const submitForm = async () => {
    try {
        const data = {
            name: form.name,
            price: form.price,
            product_id: form.product_id,
        };
        const response = await axios.post(`/api/product-attribute/${productId}`, data);
        const modal = document.getElementById('addModel');
        const modalAdd = Modal.getInstance(modal) || new Modal(modal);
        modalAdd.hide();

        // Reset form
        Object.assign(form, {
            name: '',
            product_id: productId,
        });
        errors.value = {};
        toast.success('Thêm mới thành công!');
        fetchProductAttribute();
    } catch (error) {
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.message;
        } else {
            console.error('Error creating category:', error);
        }
    }
};

const openModalUpdate = (item) => {
    errors.value = {};
    populateEditForm(item);
    const modal = document.getElementById('editModal');
    const editModel = new Modal(modal);
    editModel.show();
}

const populateEditForm = (item) => {
    if (item.price != 0) {
        editForm.type = 1
    }
    Object.assign(editForm, {
        id: item.id,
        name: item.name,
        price: item.price,
        product_id: item.product_id,
    });
};

const submitEditForm = async () => {
    try {
        const data = {
            name: editForm.name,
            price: editForm.price,
            product_id: editForm.product_id,
        };
        const response = await axios.put(`/api/product-attribute/${editForm.id}`, data);

        const modal = document.getElementById('editModal');
        const modalEdit = Modal.getInstance(modal) || new Modal(modal);
        modalEdit.hide();

        // Reset formEdit
        Object.assign(editForm, {
            name: '',
            product_id: productId,
        });
        errors.value = {};
        toast.success('Cập nhật thành công!');
        fetchProductAttribute();
    } catch (error) {
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.message;
        } else {
            console.error('Error creating category:', error);
        }
    }
};

</script>
<template>
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="/admin">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="/admin/products">Danh sách sản phẩm</a></li>
            <li class="breadcrumb-item">Danh sách thuộc tính sản phẩm: {{ productName }}</li>
        </ol>
    </nav>
    <h2 class="text-bold text-body-emphasis mb-5">Danh sách thuộc tính sản phẩm: {{ productName }}</h2>
    <div>
        <!-- Search -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <div id="searchModel" class="col-12 col-lg-9">
                <form class="row gy-2 gx-3 align-items-center" id="filter-form" @submit.prevent="fetchProductAttribute">

                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <input name="name" v-model="searchQuery" placeholder="Tên thuộc tính" type="text"
                            class="form-control">
                    </div>

                    <div class="col-12 col-sm-6 col-md-auto d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-phoenix-info btn-filter" title="Lọc">
                            <span class="fas fa-filter text-info fs-9 me-2"></span>Lọc
                        </button>
                        <button class="btn btn-sm btn-phoenix-warning" type="button"
                            @click="searchQuery = ''; fetchProductAttribute();">
                            Xoá lọc
                        </button>
                    </div>

                </form>
            </div>
            <div class="col-12 col-lg-auto text-end mt-3 mt-lg-0">
                <button class="btn btn-primary" type="button" data-bs-toggle="modal" @click="openModalAdd()">
                    <span class="fas fa-plus me-2"></span>Thêm thuộc tính
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="mx-n4 mx-lg-n6 px-4 px-lg-6 mb-9 bg-body-emphasis mt-2 position-relative top-1 position-relative"
            id="list_users_container">
            <div class="table-responsive scrollbar">
                <table class="table table-hover table-sm fs-9 mb-0">
                    <thead>
                        <tr>
                            <th class="text-uppercase text-center">Stt</th>
                            <th class="text-uppercase text-start">Tên</th>
                            <th class="text-uppercase text-center">Hành động</th>
                        </tr>
                    </thead>

                    <tbody v-if="loading">
                        <tr>
                            <td class="align-middle text-center" colspan="13">
                                <div class="spinner-border text-info" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>

                    <tbody v-if="products.length > 0">
                        <tr v-for="(product, index) in products" :key="product.id">
                            <td class="align-middle text-center">{{ index + 1 }}</td>
                            <td class="align-middle text-start">
                                <router-link
                                    :to="`/admin/product-attribute-value/${productId}/${productName}/${product.id}/${product.name}`">{{
                                        product.name }}</router-link>
                            </td>

                            <td class="align-middle text-center">
                                <button @click="openModalUpdate(product)"
                                    class='btn btn-edit-show btn-sm btn-phoenix-secondary text-info me-1 fs-9'
                                    title='Cập nhật' type='button'>
                                    <span class='fas fa-edit'></span>
                                </button>

                                <!-- <RouterLink :to="{
                                    name: 'product-attribute-value',
                                    params: { idProduct: this.id, nameProduct: this.nameProduct, idAttribute: product.id, nameAttribute: product.name }
                                }">
                                    <button class='btn btn-edit-show btn-sm btn-phoenix-secondary text-info fs-9'
                                        title='Xem chi tiết' type='button'>
                                        <span class='fas far fa-eye'></span>
                                    </button>
                                </RouterLink> -->

                            </td>

                        </tr>
                    </tbody>
                    <tbody v-else>
                        <tr>
                            <td colspan="13" class="text-center fw-bold fs-7 text-danger">Chưa có dữ liệu</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Truyền response sang Pagination -->
            <Pagination v-if="dataPanigate" :response="dataPanigate" @getData="fetchProductAttribute" />
        </div>

        <!-- thêm mới thuộc tính-->
        <div class="modal fade" id="addModel" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content form-open">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Thêm mới thuộc tính</h5>
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
                        <form class="row g-3" @submit.prevent="submitForm">
                            <div class="col-sm-12 col-md-12">
                                <div class="form-floating">
                                    <input type="text" v-model="form.name" name="name" class="form-control"
                                        placeholder="">
                                    <label>Thuộc tính</label>
                                    <div v-if="errors.name" class="text-danger mt-2 fs-9 ms-2">{{ errors.name[0] }}
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-12 col-md-12" v-if="showPriceInput">
                                <div class="form-floating">
                                    <input type="text" v-model="form.price"
                                        @input="form.price = formatBalance($event.target.value)" name="price"
                                        class="form-control" placeholder="">
                                    <label>Giá tiền</label>
                                    <span class="floating-unit">{{ sign }}</span>
                                    <div v-if="errors.price" class="text-danger mt-2 fs-9 ms-2">{{ errors.price[0]
                                        }}</div>
                                </div>
                            </div>
                            <!-- Nút để bật/tắt ô input tiền -->
                            <button type="button" class="btn btn-toggle-price" @click="togglePriceInput">
                                {{ showPriceInput ? 'Thuộc tính thường' : 'Thuộc tính tên' }}
                            </button>
                            <div class="col-12 gy-6">
                                <div class="row g-3 justify-content-center">
                                    <div class="col-auto">
                                        <button type="button" class="btn btn-close-model btn-secondary mx-1"
                                            data-bs-dismiss="modal">Huỷ</button>
                                        <button type="submit" class="btn btn-primary btn-submit mx-1"
                                            title="Thêm mới">Thêm mới</button>
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


        <!-- Modal sửa thuộc tính -->
        <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content form-open">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Cập nhật mã giảm giá</h5>
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
                                    <input type="text" v-model="editForm.name" name="name" class="form-control"
                                        placeholder="">
                                    <label>Thuộc tính</label>
                                    <div v-if="errors.name" class="text-danger mt-2 fs-9 ms-2">{{ errors.name[0] }}
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-12 col-md-12" v-if="editForm.type == 1">
                                <div class="form-floating">
                                    <input type="text" v-model="editForm.price"
                                        @input="editForm.price = formatBalance($event.target.value)" name="price"
                                        class="form-control" placeholder="Nhập giá tiền" />
                                    <label>Giá tiền</label>
                                    <span class="floating-unit">{{ sign }}</span>
                                    <div v-if="errors.price" class="text-danger mt-2 fs-9 ms-2">{{ errors.price[0] }}
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 gy-6">
                                <div class="row g-3 justify-content-center">
                                    <div class="col-auto">
                                        <button type="button" class="btn btn-close-model btn-secondary mx-1"
                                            data-bs-dismiss="modal">Huỷ
                                        </button>
                                        <button type="submit" class="btn btn-primary btn-submit mx-1"
                                            title="Cập nhật">Cập
                                            nhật</button>
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
<style>
.img-thumbnail {
    object-fit: cover;
}
</style>
