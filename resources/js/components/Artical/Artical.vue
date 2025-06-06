<script setup>
import { ref, onMounted, reactive, watch } from 'vue';
import axios from 'axios';
import { slug, dateTimeFormat } from '../../utils';
import { useToast } from "vue-toastification";

import { Modal } from "bootstrap";
import Pagination from '../Pagination.vue';

const artical = ref([]);
const products = ref([]);
const dataPanigate = ref([])
const categories = ref([]);
const loading = ref(true);
const searchQuery = ref('');
const isReadOnly = ref(false);
const errors = ref({});
const toast = useToast();
// const editor = ClassicEditor;
const previewImages = reactive({
    image: '',
});

const form = reactive({
    category_artical_id: '',
    product_id: '',
    title: '',
    meta_tittle: '',
    meta_description: '',
    image: '',
    content: '',
    slug: '',
    status: 1,
    is_hot: 1,
});

watch(() => form.title, (newName) => {
    form.slug = slug(newName);

});


// Fetch bài viết
const fetchArtical = async (page = 1) => {
    try {
        const params = Object.fromEntries(
            Object.entries(searchQuery.value).filter(([_, value]) => value !== '')
        );
        params.page = page;
        params.search = searchQuery.value;

        const response = await axios.get('/api/artical',
            {
                params
            });
        artical.value = response.data.data;
        dataPanigate.value = response.data;

    } catch (error) {
        console.error('Error fetching artical:', error);
    } finally {
        loading.value = false;
    }
};

// Fetch danh mục
const fetchArticalCategories = async () => {
    try {
        const response = await axios.get('/api/artical-categories/list');
        categories.value = response.data;

    } catch (error) {
        console.error('Error fetching categories:', error);
    } finally {
        loading.value = false;
    }
};


// Fetch sản phẩm
const fetchProduct = async () => {
    try {
        const response = await axios.get('/api/products');
        products.value = response.data.data;
        // console.log(products.value);

    } catch (error) {
        console.error('Error fetching products:', error);
    }
};

onMounted(() => {
    fetchArtical()
    fetchArticalCategories();
    fetchProduct();
});
// ================================== KHỞI ĐỘNG CKEDITOR 3 =================================
onMounted(() => {
    const editorInstance = CKEDITOR.replace("editorContent");
    // Lưu dữ liệu vào biến khi có thay đổi
    editorInstance.on('change', () => {
        form.content = editorInstance.getData();
    });
    const editorInstanceEdit = CKEDITOR.replace("editorContentEdit");
    // Lưu dữ liệu vào biến khi có thay đổi
    editorInstanceEdit.on('change', () => {
        editForm.content = editorInstanceEdit.getData();
    });
});
// ================================== KHỞI ĐỘNG CKEDITOR 3 =================================

// Xử lý chọn file
const handleFileChange = (event) => {
    form.image = event.target.files[0];
};

/*
 const selectedFile = ref(null); // Lưu trữ file đã xử lý

const handleFileChange = async (event) => {
    let file = event.target.files[0];

    if (!file) return;

    if (!file.type.match('image.*')) {
        toast.error('Vui lòng chỉ chọn file ảnh!');
        event.target.value = '';
        return;
    }

    const watermarkSrc = "https://www.my1styears.com/media/mf_webp/png/media/logo/websites/6/Logos-01.webp";

    await new Promise((resolve) => {
        const fileReader = new FileReader();
        fileReader.onload = function (e) {
            const img = new Image();
            img.onload = async function () {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');

                // Resize ảnh về 1588 x 1195
                canvas.width = 1588;
                canvas.height = 1195;
                ctx.drawImage(img, 0, 0, 1588, 1195);

                // Tải logo watermark
                const watermark = new Image();
                watermark.crossOrigin = "anonymous";
                watermark.src = watermarkSrc;

                watermark.onload = function () {
                    let logoWidth = 100;
                    let logoHeight = (watermark.height / watermark.width) * logoWidth;
                    let x = 15; // Lề trái 15px
                    let y = 20; // Lề trên 20px

                    ctx.drawImage(watermark, x, y, logoWidth, logoHeight);

                    // Convert canvas thành file Blob
                    canvas.toBlob((blob) => {
                        if (blob) {
                            selectedFile.value = new File([blob], file.name, {
                                type: file.type,
                                lastModified: new Date().getTime()
                            });

                            // Gán vào form.image để gửi lên server
                            form.image = selectedFile.value;

                            toast.success(`Đã thêm watermark vào "${file.name}"`);
                        }
                        resolve();
                    }, file.type);
                };
            };
            img.src = e.target.result;
        };
        fileReader.readAsDataURL(file);
    });
};
*/

const handleEditFileChange = (event) => {
    editForm.image = event.target.files[0];
};
const openModalShow = (artical) => {
    previewImages.image = artical.image;
    // console.log(previewImages);
    const modal = document.getElementById('viewDetailModal');
    const initModal = new Modal(modal)
    initModal.show();
    populateEditForm(artical)

}
// Gửi form
const submitAddForm = async () => {
    try {
        const formData = new FormData();
        formData.append('title', form.title);
        formData.append('category_artical_id', form.category_artical_id);
        formData.append('product_id', form.product_id);
        formData.append('meta_tittle', form.meta_tittle);
        formData.append('meta_description', form.meta_description);
        formData.append('image', form.image);
        formData.append('content', form.content);
        formData.append('slug', form.slug);
        formData.append('status', form.status);
        formData.append('is_hot', form.is_hot);

        const response = await axios.post('/api/artical/post-artical', formData);

        // Reset form
        Object.assign(form, {
            category_artical_id: '',
            product_id: '',
            title: '',
            meta_tittle: '',
            meta_description: '',
            image: '',
            content: '',
            slug: '',
            status: '',
            is_hot: '',
        });
        errors.value = {};
        // Đóng modal
        const modal = document.getElementById('addModal');
        const modalAdd = Modal.getInstance(modal) || new Modal(modal);
        modalAdd.hide();
        // Cập nhật danh sách danh mục
        fetchArtical();
        toast.success("Thêm thành công!");
    } catch (error) {
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            console.error('Error creating category:', error);
        }
    }
};

const editForm = reactive({
    id: '',
    category_artical_id: '',
    product_id: '',
    title: '',
    meta_tittle: '',
    meta_description: '',
    image: null,
    content: '',
    slug: '',
    status: '',
});

watch(() => editForm.title, (newName) => {
    editForm.slug = slug(newName);

});

// Populate edit form
const populateEditForm = (artical) => {
    Object.assign(editForm, {
        id: artical.id,
        category_artical_id: artical.category_artical_id,
        product_id: artical.product_id,
        title: artical.title,
        meta_tittle: artical.meta_tittle,
        meta_description: artical.meta_description,
        image: '',
        content: artical.content,
        slug: artical.slug,
        status: artical.status,
        date: artical.created_at,
        status_name: artical.status.name,
    });
    // Set editor content
    const editorInstanceEdit = CKEDITOR.instances.editorContentEdit;
    if (editorInstanceEdit) {
        editorInstanceEdit.setData(artical.content);
    }
};

const openModalCreate = () => {
    errors.value = {};
    const modal = document.getElementById('addModal');
    const modalCreate = new Modal(modal)
    modalCreate.show();
}
const openEditModal = (artical) => {
    errors.value = {};
    populateEditForm(artical);
    const modal = document.getElementById('editModal');
    const modalEdit = new Modal(modal)
    modalEdit.show();
};

// Gửi form chỉnh sửa
const submitEditForm = async () => {
    try {
        const formEditData = new FormData();
        formEditData.append('category_artical_id', editForm.category_artical_id);
        formEditData.append('product_id', editForm.product_id);
        formEditData.append('title', editForm.title);
        formEditData.append('meta_tittle', editForm.meta_tittle);
        formEditData.append('meta_description', editForm.meta_description);
        formEditData.append('image', editForm.image);
        formEditData.append('content', editForm.content);
        formEditData.append('slug', editForm.slug);
        formEditData.append('status', editForm.status);
        const response = await axios.post(`/api/artical/update-artical/${editForm.id}`, formEditData);

        // Reset edit form
        Object.assign(editForm, {
            id: '',
            category_artical_id: '',
            product_id: '',
            title: '',
            meta_tittle: '',
            meta_description: '',
            image: '',
            content: '',
            slug: '',
            status: '',
        });
        errors.value = {};

        const modal = document.getElementById('editModal');
        const modalEdit = Modal.getInstance(modal) || new Modal(modal);
        modalEdit.hide();
        // Cập nhật danh sách danh mục
        fetchArtical();
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
    <h2 class="text-bold text-body-emphasis mb-5">Danh sách bài viết</h2>
    <div>
        <!-- Search -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div id="searchModel" class="col-9">
                <form class="row d-flex align-items-center" id="filter-form" @submit.prevent="fetchArtical">
                    <div class='col-3 mb-3'>
                        <input v-model="searchQuery" name="search" placeholder="Tiêu đề bài viết" type="text"
                            class="form-control">
                    </div>
                    <div class="col-auto mb-3 d-flex justify-content-start">
                        <button type="submit" class="btn btn-sm btn-phoenix-info btn-filter me-2" title="Lọc">
                            <span class="fas fa-filter text-info fs-9 me-2"></span>Lọc
                        </button>
                        <button class="btn btn-sm btn-phoenix-warning" type="button"
                            @click="searchQuery = ''; fetchArtical();">Xoá lọc</button>
                    </div>
                </form>
            </div>
            <div class="col-auto ms-auto text-end">
                <button class="btn btn-primary" type="button" data-bs-toggle="modal" @click="openModalCreate()">
                    <span class="fas fa-plus me-2"></span> Thêm bài viết
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
                            <th class="align-middle text-center text-uppercase text-truncate">Ảnh</th>
                            <th class="align-middle text-start text-uppercase text-truncate">Tiêu đề</th>
                            <th class="align-middle text-start text-uppercase text-truncate">Danh mục</th>
                            <th class="align-middle text-start text-uppercase text-truncate">Sản phẩm</th>
                            <th class="align-middle text-start text-uppercase text-truncate">Nổi bật</th>
                            <th class="align-middle text-start text-uppercase text-truncate">Trạng thái</th>
                            <th class="align-middle text-center text-uppercase text-truncate">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="list-data" id="data_table_body">
                        <tr v-if="loading" class="loading-data">
                            <td class="text-center" colspan="12">
                                <div class="spinner-border text-info spinner-border-sm" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="artical.length === 0 && !loading">
                            <td colspan="12" class="text-center fw-bold fs-7 text-danger">Chưa có dữ liệu</td>
                        </tr>
                        <tr v-else v-for="(artical, index) in artical" :key="artical.id">
                            <td class="align-middle text-center">{{ index + 1 }}</td>
                            <td class="align-middle text-center">
                                <img :src="artical.image || '/storage/artical/null.jpg'" alt="artical Image"
                                    style="border-radius: 10px;" width="70" height="70">
                            </td>
                            <td class="align-middle text-start">{{ artical.title }}</td>
                            <td class="align-middle text-start">{{ artical.category_artical.name }}</td>
                            <td class="align-middle text-start">{{ artical.product?.name }}</td>
                            <td class="align-middle text-start">
                                {{ artical.is_hot == 1 ? 'Có' : 'Không' }}
                            </td>
                            <td class="align-middle text-start">
                                <span
                                    :class="['fs-10 badge', artical.status === 1 ? 'bg-success text-white' : 'bg-danger text-white']">
                                    {{ artical.status === 1
                                        ? 'Hoạt động'
                                        : 'Không hoạt động' }}
                                </span>
                            </td>
                            <td class="align-middle text-center">
                                <div class="position-relative">
                                    <button class="btn btn-edit-show btn-sm btn-phoenix-secondary text-info me-1 fs-10"
                                        title="Cập nhật" type="button" data-bs-toggle="modal"
                                        @click="openEditModal(artical)">
                                        <span class="fas far fa-edit"></span>
                                    </button>
                                    <button class='btn btn-edit-show btn-sm btn-phoenix-secondary text-info me-1 fs-10'
                                        @click="openModalShow(artical)" title='Xem chi tiết' type='button'>
                                        <span class='fas far fa-eye'></span>
                                    </button>

                                </div>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>
            <Pagination v-if="dataPanigate" :response="dataPanigate" @getData="fetchArtical" />
        </div>

        <!-- addModal -->
        <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-dialog modal-xl">
                    <div class=" modal-content form-open">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Thêm mới bài viết</h5>
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
                                        <input type="text" v-model="form.title" name="title" class="form-control"
                                            placeholder="Tên bài viết">
                                        <label>Tên bài viết</label>
                                        <div v-if="errors.title" class="text-danger mt-2 fs-9 ms-2">{{ errors.title[0]
                                            }}
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-12 col-md-12">
                                    <div class="form-floating">
                                        <input type="text" v-model="form.slug" name="Slug" class="form-control"
                                            placeholder="Tên bài viết">
                                        <label>Slug</label>
                                        <div v-if="errors.slug" class="text-danger mt-2 fs-9 ms-2">{{ errors.slug[0]
                                            }}
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-12 col-md-12">
                                    <div class="form-floating">
                                        <select class="form-select" v-model="form.category_artical_id"
                                            name="category_artical_id">
                                            <option value="">---Chọn danh mục bài viết---</option>
                                            <option v-for="(c, index) in categories" :key="c.id" :value="c.id">
                                                {{ c.name }}
                                            </option>
                                        </select>
                                        <label>Danh mục</label>
                                        <div v-if="errors.category_artical_id" class="text-danger mt-2 fs-9 ms-2">{{
                                            errors.category_artical_id[0] }}</div>
                                    </div>
                                </div>

                                <div class="col-sm-12 col-md-12">
                                    <div class="form-floating">
                                        <select class="form-select" v-model="form.product_id" name="product_id">
                                            <option value="">Không</option>
                                            <option v-for="(product, index) in products" :key="product.id"
                                                :value="product.id">
                                                {{ product.name }}
                                            </option>
                                        </select>
                                        <label>Sản phẩm</label>
                                        <div v-if="errors.product_id" class="text-danger mt-2 fs-9 ms-2">{{
                                            errors.product_id[0] }}</div>
                                    </div>
                                </div>
                                <div class="col-sm-12 col-md-12">
                                    <div class="form-floating">
                                        <input type="text" v-model="form.meta_tittle" name="meta_tittle"
                                            class="form-control" placeholder="Từ khóa SEO">
                                        <label>Từ khóa SEO</label>
                                        <div v-if="errors.meta_tittle" class="text-danger mt-2 fs-9 ms-2">{{
                                            errors.meta_tittle[0] }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-12 col-md-12">
                                    <div class="form-floating">
                                        <input type="text" v-model="form.meta_description" name="meta_description"
                                            class="form-control" placeholder="SEO mô tả">
                                        <label>SEO mô tả</label>
                                        <div v-if="errors.meta_description" class="text-danger mt-2 fs-9 ms-2">{{
                                            errors.meta_description[0] }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-12 col-md-12">
                                    <label for="content" class="form-label">Nội dung</label>
                                    <textarea name="content" id="editorContent">{{ form.content }}</textarea>
                                    <div v-if="errors.content" class="text-danger mt-2 fs-9 ms-2">{{ errors.content[0]
                                    }}
                                    </div>
                                </div>
                                <div class="col-sm-12 col-md-12">
                                    <input type="file" name="image" @change="handleFileChange" class="form-control"
                                        placeholder="Chọn ảnh">
                                    <div v-if="errors.image" class="text-danger mt-2 fs-9 ms-2">{{ errors.image[0] }}
                                    </div>
                                </div>
                                <div class="col-sm-6 col-md-6">
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
                                <div class="col-sm-6 col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select" v-model="form.is_hot">
                                            <option value="1">Có</option>
                                            <option value="0">Không</option>
                                        </select>
                                        <label>Bài viết hot</label>
                                        <div v-if="errors.is_hot" class="text-danger mt-2 fs-9 ms-2">{{
                                            errors.is_hot[0] }}</div>
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
        <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-dialog modal-xl">
                    <div class=" modal-content form-open">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Cập nhật bài viết</h5>
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
                                        <input type="text" v-model="editForm.title" name="title" class="form-control"
                                            placeholder="Tên bài viết">
                                        <label>Tên bài viết</label>
                                        <div v-if="errors.title" class="text-danger mt-2 fs-9 ms-2">{{ errors.title[0]
                                            }}
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
                                        <select class="form-select" v-model="editForm.category_artical_id"
                                            name="category_artical_id">
                                            <option v-for="(c, index) in categories" :key="c.id" :value="c.id">{{ c.name
                                                }}
                                            </option>
                                        </select>
                                        <label>Danh mục</label>
                                        <div v-if="errors.category_artical_id" class="text-danger mt-2 fs-9 ms-2">{{
                                            errors.category_artical_id[0] }}</div>
                                    </div>
                                </div>

                                <div class="col-sm-12 col-md-12">
                                    <div class="form-floating">
                                        <select class="form-select" v-model="editForm.product_id" name="product_id">
                                            <option value="">Không</option>
                                            <option v-for="(product, index) in products" :key="product.id"
                                                :value="product.id">{{
                                                    product.name }}
                                            </option>
                                        </select>
                                        <label>Sản phẩm</label>
                                        <div v-if="errors.product_id" class="text-danger mt-2 fs-9 ms-2">{{
                                            errors.product_id[0] }}</div>
                                    </div>
                                </div>
                                <div class="col-sm-12 col-md-12">
                                    <div class="form-floating">
                                        <input type="text" v-model="editForm.meta_tittle" name="meta_tittle"
                                            class="form-control" placeholder="Từ khóa SEO">
                                        <label>Từ khóa SEO</label>
                                        <div v-if="errors.meta_tittle" class="text-danger mt-2 fs-9 ms-2">{{
                                            errors.meta_tittle[0] }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-12 col-md-12">
                                    <div class="form-floating">
                                        <input type="text" v-model="editForm.meta_description" name="meta_description"
                                            class="form-control" placeholder="SEO mô tả">
                                        <label>SEO mô tả</label>
                                        <div v-if="errors.meta_description" class="text-danger mt-2 fs-9 ms-2">{{
                                            errors.meta_description[0] }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-12 col-md-12">
                                    <label for="content" class="form-label">Nội dung</label>
                                    <textarea name="content" id="editorContentEdit">{{ form.content }}</textarea>
                                    <div v-if="errors.content" class="text-danger mt-2 fs-9 ms-2">{{ errors.content[0]
                                    }}
                                    </div>
                                </div>

                                <div class="col-sm-12 col-md-12">
                                    <input type="file" name="image" @change="handleEditFileChange" class="form-control"
                                        placeholder="Chọn ảnh">
                                    <div v-if="errors.image" class="text-danger mt-2 fs-9 ms-2">{{ errors.image[0] }}
                                    </div>
                                </div>
                                <div class="col-sm-12 col-md-12">
                                    <div class="form-floating">
                                        <select class="form-select" v-model="editForm.status">
                                            <option :value="1">Hoạt động</option>
                                            <option :value="0">Không hoạt động</option>
                                        </select>
                                        <label>Trạng thái</label>
                                        <div v-if="errors.status" class="text-danger mt-2 fs-9 ms-2">
                                            {{ errors.status[0] }}
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


        <div class="modal fade" id="viewDetailModal" tabindex="-1" aria-labelledby="viewDetailModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-light py-3">
                        <h5 class="modal-title fw-bold text-primary mb-0">Chi tiết sản phẩm</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-4">
                            <!-- Cột trái - Ảnh và Thông tin cơ bản -->
                            <div class="col-lg-6">
                                <div class="sticky-top" style="top: 1rem;">
                                    <!-- Ảnh đại diện -->
                                    <div class="card shadow-sm mb-4">
                                        <div v-if="previewImages.image.length > 0"
                                            class="image-preview mt-3 d-flex flex-wrap gap-2">
                                            <div class="related-image-container position-relative"
                                                style="width: 100%; height:300px; padding: 0 20px;">
                                                <img :src="previewImages.image ? previewImages.image : image.image"
                                                    style="width: 100%; height: 100%;" alt="Related Image"
                                                    class="rounded shadow-sm preview-related">
                                            </div>
                                        </div>
                                        <div class="card-footer bg-transparent border-0 pt-3">
                                            <h6 class="fw-bold text-primary mb-3">Chi tiết</h6>
                                            <dl class="row mb-0">
                                                <dt class="col-sm-3 text-muted">Tiêu đề:</dt>
                                                <dd class="col-sm-9 fw-bold">{{ editForm.title }}</dd>

                                                <dt class="col-sm-3 text-muted">Quốc gia:</dt>
                                                <dd class="col-sm-9 fw-bold">{{ editForm.country_name }}</dd>

                                                <dt class="col-sm-3 text-muted">Ngày tạo:</dt>
                                                <dd class="col-sm-9 fw-bold">{{ dateTimeFormat(editForm.date) }}</dd>

                                                <dt class="col-sm-3 text-muted">Trạng thái:</dt>
                                                <dd class="col-sm-9 fw-bold">{{ editForm.status_name }}</dd>

                                            </dl>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Thông tin SEO -->

                            <div div class="card shadow-sm mt-4 col-lg-12">
                                <div class="card-body">
                                    <h6 class="fw-bold text-primary mb-4">Thông tin SEO</h6>
                                    <div class="mb-3">
                                        <dt class="text-muted">Tiêu đề SEO</dt>
                                        <dd class="">{{ editForm.meta_tittle }}</dd>
                                    </div>

                                    <div class="mb-3">
                                        <dt class="text-muted">Mô tả ngắn</dt>
                                        <dd class="">{{ editForm.meta_description }}</dd>
                                    </div>
                                    <div class="mb-0">
                                        <dt class="text-muted">Nội dung</dt>
                                        <dd v-html="(editForm.content)">
                                        </dd>
                                    </div>
                                </div>
                            </div>


                        </div>
                    </div>

                    <div class="modal-footer bg-light py-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Đóng
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<style>
/* Add your styles here */
</style>