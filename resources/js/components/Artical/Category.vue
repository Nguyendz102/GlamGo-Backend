<script setup>
import { ref, onMounted, watch, reactive } from 'vue';
import axios from 'axios';
import { slug } from '../../utils';
import { useToast } from "vue-toastification";
import { Modal } from "bootstrap";
import Pagination from '../../components/Pagination.vue';

const categories = ref([]);
const dataPanigate = ref([])

const parent = ref([]);
const loading = ref(true);
const searchQuery = ref('');
const isReadOnly = ref(false);
const errors = ref({});
const toast = useToast();


const form = reactive({
    name: '',
    slug: '',
    parent_id: '0',
    status: '1',
});

const editForm = reactive({
    id: '',
    name: '',
    slug: '',
    parent_id: '0',
});

watch(() => form.name, (newName) => {
    form.slug = slug(newName);
});

watch(() => editForm.name, (newName) => {
    editForm.slug = slug(newName);
});
// Fetch danh mục
const fetchArticalCategories = async (page = 1) => {
    try {
        const params = Object.fromEntries(
            Object.entries(searchQuery.value).filter(([_, value]) => value !== '')
        );
        params.page = page
        const response = await axios.get('/api/artical-categories',
            {
                params
            });
        categories.value = response.data.data;
        dataPanigate.value = response.data;

    } catch (error) {
        console.error('Error fetching categories:', error);
    } finally {
        loading.value = false;
    }
};

// Fetch danh mục cha
const fetchParent = async () => {
    try {
        const response = await axios.get('/api/artical-categories/parent');
        parent.value = response.data;
    } catch (error) {
        console.error('Error fetching parent:', error);
    }
};

onMounted(() => {
    fetchArticalCategories();
    fetchParent();
});

const openModalCreate = () => {
    errors.value = {};
    const modal = document.getElementById('modalCreateCategory');
    const modalCreate = new Modal(modal)
    modalCreate.show();

}
// Gửi form
const submitAddForm = async () => {
    try {
        const formData = new FormData();
        formData.append('name', form.name);
        formData.append('slug', slug(form.slug));
        formData.append('status', form.status);
        formData.append('parent_id', form.parent_id);
        const response = await axios.post('/api/artical-categories/post-parent', formData);
        // Đóng modal
        const modal = document.getElementById('modalCreateCategory');
        const modalCreate = Modal.getInstance(modal) || new Modal(modal)
        modalCreate.hide();

        // Reset form
        Object.assign(form, {
            name: '',
            slug: '',
            parent_id: '0',
            status: '',
        });
        errors.value = {};

        // Cập nhật danh sách danh mục
        fetchArticalCategories();
        fetchParent();
        toast.success("Thêm thành công!");
    } catch (error) {
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            console.error('Error creating category:', error);
        }
    }
};



// Populate edit form
const populateEditForm = (category) => {
    Object.assign(editForm, {
        id: category.id,
        name: category.name,
        slug: category.slug,
        status: category.status,
        parent_id: category.parent_id,
    });

};
const openModalEdit = (category) => {
    errors.value = {};
    populateEditForm(category);
    const modal = document.getElementById('modalEditCategory');
    const modalCreate = Modal.getInstance(modal) || new Modal(modal)
    modalCreate.show();
}

// Gửi form chỉnh sửa
const submitEditForm = async () => {
    try {
        const formData = {
            name: editForm.name,
            slug: slug(editForm.slug),
            status: editForm.status,
            parent_id: editForm.parent_id,
        };
        const response = await axios.put(`/api/artical-categories/update-parent/${editForm.id}`, formData);
        // console.log('Category updated:', response.data);
        // Reset edit form
        Object.assign(editForm, {
            id: '',
            name: '',
            slug: '',
            status: '',
            parent_id: '',
        });
        errors.value = {};

        // Đóng modal
        const modal = document.getElementById('modalEditCategory');
        const modalInstance = Modal.getInstance(modal) || new Modal(modal);
        modalInstance.hide();

        // Cập nhật danh sách danh mục
        fetchArticalCategories();
        fetchParent();

        toast.success("Cập nhật thành công!");

    } catch (error) {
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            console.error('Error updating category:', error);
        }
    }
};

</script>

<template>
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="/admin">Trang chủ</a></li>
            <li class="breadcrumb-item">Danh mục bài viết</li>
        </ol>
    </nav>
    <h2 class="text-bold text-body-emphasis mb-5">Danh mục bài viết</h2>
    <div>
        <!-- Search -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div id="searchModel" class="col-9">
                <form class="row d-flex align-items-center" id="filter-form" @submit.prevent="fetchArticalCategories">
                    <div class='col-3 mb-3'>
                        <input v-model="searchQuery" name="search" placeholder="Tên danh mục" type="text"
                            class="form-control">
                    </div>
                    <div class="col-auto mb-3 d-flex justify-content-start">
                        <button type="submit" class="btn btn-sm btn-phoenix-info btn-filter me-2" title="Lọc">
                            <span class="fas fa-filter text-info fs-9 me-2"></span>Lọc
                        </button>
                        <button class="btn btn-sm btn-phoenix-warning" type="button"
                            @click="searchQuery = ''; fetchArticalCategories();">Xoá lọc</button>
                    </div>
                </form>
            </div>
            <div class="col-auto ms-auto text-end">
                <button class="btn btn-primary" type="button" data-bs-toggle="modal" @click='openModalCreate()'>
                    <span class="fas fa-plus me-2"></span>Thêm danh mục
                </button>
            </div>
        </div>
        <!-- Table -->
        <div class="mx-n4 mx-lg-n6 px-4 px-lg-6 mb-9 bg-body-emphasis mt-2 position-relative top-1"
            id="list_users_container">
            <div class="table-responsive quote-table-container scrollbar ms-n1 ps-1">
                <table class="table table-hover table-sm fs-9 mb-0">
                    <thead>
                        <tr>
                            <th class="align-middle text-center text-uppercase">Stt</th>
                            <th class="align-middle text-start text-uppercase">Tên danh mục</th>
                            <th class="align-middle text-start text-uppercase">Slug</th>
                            <th class="align-middle text-start text-uppercase">Thuộc</th>
                            <th class="align-middle text-start text-uppercase">Trạng thái</th>
                            <th class="align-middle text-center text-uppercase">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="list-data" id="data_table_body">
                        <tr v-if="loading" class="loading-data">
                            <td class="text-center" colspan="6">
                                <div class="spinner-border text-info spinner-border-sm" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="categories.length === 0 && !loading">
                            <td colspan="6" class="text-center fw-bold fs-7 text-danger">Chưa có dữ liệu</td>
                        </tr>
                        <tr v-else v-for="(category, index) in categories" :key="category.id">
                            <td class="align-middle text-center">{{ index + 1 }}</td>
                            <td class="align-middle text-start">{{ category.name }}</td>
                            <td class="align-middle text-start">{{ category.slug }}</td>
                            <td class="align-middle text-start">{{category.parent_id === 0
                                ? 'Trống'
                                : (categories.find(cat => cat.id === category.parent_id)?.name || 'Không xác định')}}
                            </td>
                            <td class="align-middle text-start">
                                <span
                                    :class="['fs-10 badge', category.status === 1 ? 'bg-success text-white' : 'bg-danger text-white']">
                                    {{ category.status === 1
                                        ? 'Hoạt động'
                                        : 'Không hoạt động' }}
                                </span>
                            </td>
                            <td class="align-middle text-center">
                                <div class="position-relative">
                                    <button class="btn btn-edit-show btn-sm btn-phoenix-secondary text-info me-1 fs-10"
                                        title="Cập nhật" type="button" data-bs-toggle="modal"
                                        @click="openModalEdit(category)">
                                        <span class="fas far fa-edit"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>
            <Pagination v-if="dataPanigate" :response="dataPanigate" @getData="fetchArticalCategories" />
        </div>

        <div class="modal fade" id="modalCreateCategory" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-dialog modal-xl">
                    <div class=" modal-content form-open">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Thêm mới danh mục</h5>
                            <button class="btn p-1 closeButton" type="button" data-bs-dismiss="modal"
                                aria-label="Close">
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
                            <form class="row g-3" @submit.prevent="submitAddForm">
                                <div class="col-sm-12 col-md-12">
                                    <div class="form-floating">
                                        <input type="text" v-model="form.name" name="name" class="form-control"
                                            placeholder="Tên danh mục">
                                        <label>Tên danh mục</label>
                                        <div v-if="errors.name" class="text-danger mt-2 fs-9 ms-2">{{ errors.name[0] }}
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-12 col-md-12">
                                    <div class="form-floating">
                                        <input type="text" v-model="form.slug" name="slug" class="form-control"
                                            placeholder="Slug">
                                        <label>Slug</label>
                                        <div v-if="errors.slug" class="text-danger mt-2 fs-9 ms-2">{{ errors.slug[0] }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-12 col-md-12">
                                    <div class="form-floating">
                                        <select class="form-select" v-model="form.parent_id" name="parent_id">
                                            <option value="0">Trống</option>
                                            <option v-for="(c, index) in parent" :key="c.id" :value="c.id">{{
                                                c.name }}
                                            </option>
                                        </select>
                                        <label>Danh mục thuộc loại</label>
                                        <div v-if="errors.parent_id" class="text-danger mt-2 fs-9 ms-2">{{
                                            errors.parent_id[0] }}</div>
                                    </div>
                                </div>
                                <div class="col-sm-12 col-md-12">
                                    <div class="form-floating">
                                        <select class="form-select" v-model="form.status">
                                            <option value="1">Hoạt động</option>
                                            <option value="0">Không hoạt động</option>
                                        </select>
                                        <label>Trạng thái</label>
                                        <div v-if="errors.status" class="text-danger mt-2 fs-9 ms-2">{{
                                            errors.status[0] }}</div>
                                    </div>
                                </div>

                                <div class="col-12 gy-6">
                                    <div class="row g-3 justify-content-center">
                                        <div class="col-auto">
                                            <button type="button" class="btn btn-close-model btn-secondary mx-1"
                                                data-bs-dismiss="modal">Huỷ
                                            </button>
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
        </div>

        <!-- edit -->

        <div class="modal fade" id="modalEditCategory" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-dialog modal-xl">
                    <div class=" modal-content form-open">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Cập nhật danh mục</h5>
                            <button class="btn p-1 closeButton" type="button" data-bs-dismiss="modal"
                                aria-label="Close">
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
                                            placeholder="Tên danh mục">
                                        <label>Tên danh mục</label>
                                        <div v-if="errors.name" class="text-danger mt-2 fs-9 ms-2">{{ errors.name[0] }}
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-12 col-md-12">
                                    <div class="form-floating">
                                        <input type="text" v-model="editForm.slug" name="slug" class="form-control"
                                            placeholder="Slug">
                                        <label>Slug</label>
                                        <div v-if="errors.slug" class="text-danger mt-2 fs-9 ms-2">{{ errors.slug[0] }}
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-12 col-md-12">
                                    <div class="form-floating">
                                        <select class="form-select" v-model="editForm.parent_id" name="parent_id">
                                            <option value="0">Trống</option>
                                            <option v-for="(parent, index) in parent" :key="parent.id"
                                                :value="parent.id">{{ parent.name }}
                                            </option>
                                        </select>
                                        <label>Danh mục thuộc loại</label>
                                        <div v-if="errors.parent_id" class="text-danger mt-2 fs-9 ms-2">{{
                                            errors.parent_id[0] }}</div>
                                    </div>
                                </div>

                                <div class="col-sm-12 col-md-12">
                                    <div class="form-floating">
                                        <select class="form-select" v-model="editForm.status">
                                            <option value="1">Hoạt động</option>
                                            <option value="0">Không hoạt động</option>
                                        </select>
                                        <label>Trạng thái</label>
                                        <div v-if="errors.status" class="text-danger mt-2 fs-9 ms-2">{{
                                            errors.status[0] }}</div>
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

    </div>
</template>

<style>
/* Add your styles here */
</style>