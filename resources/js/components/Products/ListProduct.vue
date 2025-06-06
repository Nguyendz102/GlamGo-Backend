<script setup>
import { ref, onMounted, watch, reactive, computed } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import { formatNumber, btnLoading, slug, formatBalance } from '../../utils';
import { Modal } from 'bootstrap';
import Pagination from '../Pagination.vue';
const products = ref([]);
const category = ref([]);
const dataPanigate = ref([])
const statusSlug = ref(false);
const openSlug = () => {
    statusSlug.value = !statusSlug.value;
}
const loading = ref(true);
const errors = ref({});
const filters = ref({ name: '', code: '', country: '', tag: '' });
const toast = useToast();

const form = reactive({
    name: "",
    code: "",
    category_id: "",
    price: "0",
    price_sale: "0",
    import_price: 0,
    features: "1",
    hashtag: "",
    status: "1",
    slug: "",
    image_alt: "",
    image: "",
    image_related: [],
    meta_title: "",
    meta_description: "",
    is_recommen: 0,
});

const editForm = reactive({
    id: null,
    name: "",
    code: "",
    category_id: "",
    price: "0",
    price_sale: "0",
    import_price: "",
    features: "1",
    hashtag: "",
    status: "1",
    slug: "",
    image_alt: "",
    image: null,
    current_image_url: "", // URL ảnh chính hiện tại
    related_images: [], // File ảnh mới
    current_related_images: [], // {id, url} ảnh cũ
    meta_title: "",
    meta_description: "",
    is_recommen: 0,
});

watch(() => form.name, (newName) => {
    form.slug = slug(newName);
});

const previewImages = reactive({
    main: null,
    related: []
});

const editPreviewImages = reactive({
    main: null,
    related: []
});

const updateFilterTag = (tag) => {
    filters.value.tag = tag;
    fetchProduct();
};
const fetchProduct = async (page = 1) => {
    try {
        const params = Object.fromEntries(
            Object.entries(filters.value).filter(([_, value]) => value !== '')
        );
        params.page = page;
        const response = await axios.get('/api/products', {
            params
        });
        params.country = ''
        filters.value = params;
        products.value = response.data.data;
        dataPanigate.value = response.data
    } catch (error) {
        console.error('Error fetching products:', error);
        toast.error('Có lỗi xảy ra!');
    } finally {
        loading.value = false;
    }
};

const fetchCategories = async () => {
    try {
        const response = await axios.get('/api/categories');
        category.value = response.data.data;
    } catch (error) {
        console.error('Có lỗi xảy ra không tìm thấy countries:', error);
    }
};

// Hàm xử lý ảnh chính
const previewMainImage = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.image = file; // Lưu File object vào form
        // Tạo URL xem trước
        const reader = new FileReader();
        reader.onload = (e) => {
            previewImages.main = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

// Hàm xử lý ảnh liên quan
const previewRelatedImages = (event) => {
    const files = Array.from(event.target.files);
    if (files.length > 0) {
        // Thêm files mới vào mảng hiện có thay vì ghi đè
        form.image_related = [...form.image_related, ...files];

        // Tạo URL xem trước và thêm vào mảng preview hiện có
        files.forEach(file => {
            const reader = new FileReader();
            reader.onload = (e) => {
                previewImages.related = [...previewImages.related, e.target.result];
            };
            reader.readAsDataURL(file);
        });
    }
};

// Hàm xóa ảnh chính
const removeMainImage = (event) => {
    event.preventDefault();
    form.image = null;
    previewImages.main = null;
    document.getElementById('mainImageUpload').value = '';
};


// Hàm xóa ảnh đại diện mới chọn
const removeEditMainImage = (event) => {
    event.preventDefault();
    editPreviewImages.main = editForm.current_image_url;
};

// Hàm xóa ảnh liên quan
const removeRelatedImage = (index, event) => {
    event.preventDefault();
    previewImages.related.splice(index, 1);
    form.image_related.splice(index, 1);
    if (previewImages.related.length === 0) {
        document.getElementById('relatedImageUpload').value = '';
    }
}



// ảnh mới edit
const removeNewRelatedImage = (index, event) => {
    event.preventDefault();
    editPreviewImages.related.splice(index, 1);
    if (previewImages.related.length === 0) {
        document.getElementById('editRelatedImageUpload').value = '';
    }
};
// ảnh cũ
const removeOldRelatedImage = (index, event, id) => {
    event.preventDefault();
    if (confirm('Bạn có chắc chắn muốn xóa ảnh này không?')) {
        axios.delete(`/api/products/${id}`)
            .then(response => {
                // Sửa lại cách xóa để đảm bảo reactivity
                editPreviewImages.related.splice(index, 1);
                editForm.current_related_images = [
                    ...editForm.current_related_images.slice(0, index),
                    ...editForm.current_related_images.slice(index + 1)
                ];
                fetchProduct();
                toast.success('Xóa ảnh thành công');
            })
            .catch(error => {
                console.error('Lỗi xóa ảnh:', error);
                toast.error('Không thể xóa ảnh, vui lòng thử lại!');
            });
    }
}
// phần edit 
const previewEditMainImage = (event) => {
    const file = event.target.files[0];
    if (file) {
        editForm.image = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            editPreviewImages.main = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const previewEditRelatedImages = (event) => {
    const files = Array.from(event.target.files);
    if (files.length > 0) {
        editForm.related_images = [...editForm.related_images, ...files];
        files.forEach(file => {
            const reader = new FileReader();
            reader.onload = (e) => {
                editPreviewImages.related.push(e.target.result);
            };
            reader.readAsDataURL(file);
        });
    }
};


const resetFilters = () => {
    filters.value = { name: '', code: '', country: '' };
    fetchProduct();
}

// Gọi API khi component mounted
onMounted(async () => {
    await fetchProduct();
    await fetchCategories();
});

const submitForm = async () => {
    const btnAdd = document.querySelector('#btnAdd');
    try {
        btnLoading(btnAdd, true);
        form.slug = slug(form.slug);
        const formData = new FormData();
        // Thêm các trường dữ liệu thông thường không phải là các  trường file
        for (const key in form) {
            if (key !== 'image' && key !== 'related_images') {
                formData.append(key, form[key]);
            }
        }
        // Thêm ảnh chính
        if (form.image) {
            formData.append('image', form.image);
        }
        // Thêm ảnh phụ
        form.image_related.forEach(file => {
            formData.append('related_images[]', file);
        });
        const response = await axios.post('/api/products', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        toast.success('Thêm sản phẩm thành công');
        resetForm();
        fetchProduct();
        closeModal();

    } catch (error) {
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.message;
            toast.error('Có lỗi xảy ra khi thêm sản phẩm');
        } else {
            toast.error('Có lỗi xảy ra khi thêm sản phẩm');
            console.error('Có lỗi xảy ra khi thêm sản phẩm:', error);
        }
    } finally {
        btnLoading(btnAdd, false, 'Thêm mới');
    }
};

// Hàm đóng modal
const closeModal = () => {
    const modalElement = document.querySelector('#addModel');
    if (modalElement) {
        const modal = Modal.getInstance(modalElement) || new Modal(modalElement);
        modal.hide();
    }
};

const closeModalEdit = () => {
    const modalElement = document.querySelector('#editModel');
    if (modalElement) {
        const modal = Modal.getInstance(modalElement) || new Modal(modalElement);
        modal.hide();
    }
};

// Hàm reset form
const resetForm = () => {
    const baseValues = {
        name: "",
        code: "",
        category_id: "",
        price: "0",
        price_sale: "0",
        import_price: "",
        features: "1",
        hashtag: "",
        status: "1",
        slug: "",
        image_alt: "",
        image: null,
        related_images: [],
        meta_title: "",
        meta_description: "",
        is_recommen: 0,
    };

    // Giữ lại id nếu đang ở chế độ edit
    const newValues = form.hasOwnProperty('id')
        ? { ...baseValues, id: null }
        : baseValues;

    Object.assign(form, newValues);

    previewImages.main = null;
    previewImages.related = [];
    errors.value = {};
    document.getElementById('mainImageUpload').value = '';
    document.getElementById('relatedImageUpload').value = '';
};


const updateForm = async () => {
    const btnEdit = document.querySelector('#btnEdit');
    try {
        btnLoading(btnEdit, true);
        editForm.slug = slug(editForm.slug);
        const formData = new FormData();
        for (const key in editForm) {
            if (key !== 'related_images', key !== 'image') {
                formData.append(key, editForm[key]);
            }
        }
        if (editForm.image) {
            formData.append('image', editForm.image);
        }
        editForm.related_images.forEach(file => {
            formData.append('related_images[]', file);
        });
        const response = await axios.post(`/api/products/${editForm.id}`, formData, {
            headers: {
                "Content-Type": "multipart/form-data"
            },
        });
        closeModalEdit();
        resetForm(true);
        toast.success('Cập nhật sản phẩm thành công');
        errors.value = {};
        fetchProduct();
        btnLoading(btnEdit, false, 'Cập nhật');

    } catch (error) {
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            console.error('Có lỗi xảy ra khi thêm sản phẩm:', error);
        }
        btnLoading(btnEdit, false, 'Cập nhật');

    }
};

const openModalCreate = () => {
    errors.value = {};
    form.value = {};
    form.image_related = []
    previewImages.related = [];
    const modal = document.getElementById('addModel');
    const initModal = new Modal(modal)
    initModal.show();
}

const openModalUpdate = (product) => {
    errors.value = {};
    const modal = document.getElementById('editModel');
    const initModal = new Modal(modal)
    initModal.show();
    populateEditForm(product);
}

const openModalShow = (product) => {
    const modal = document.getElementById('viewDetailModal');
    const initModal = new Modal(modal)
    initModal.show();
    populateEditForm(product)
}
const populateEditForm = (product) => {
    // Reset các ảnh mới nếu có
    editPreviewImages.main = null;
    editPreviewImages.related = [];
    Object.assign(editForm, {
        id: product.id,
        name: product.name,
        code: product.code,
        category_id: product.category_id,
        price: formatNumber(product.price),
        features: product.features,
        hashtag: product.hashtag,
        status: product.status,
        image: product.image,
        slug: product.slug,
        image_alt: product.image_alt,
        // Xử lý ảnh
        current_image_url: product.image, // URL ảnh chính hiện tại
        current_related_images: product.product_images?.map(img => ({
            id: img.id,
            url: img.image // Lấy URL từ product_images
        })) || [],
        meta_title: product.meta_title,
        meta_description: product.meta_description,
        category_name: product.category.name,
        import_price: formatNumber(product.import_price || 0),
        is_recommen: product.is_recommen,
        price_sale: formatNumber(product.price_sale),
    });
};
</script>
<!--  style css cho upload ảnh  -->
<style scoped>
.text-truncate-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.cursor-pointer {
    cursor: pointer;
    transition: transform 0.2s ease, background-color 0.2s ease;
}

.cursor-pointer:hover {
    transform: scale(1.05);
}

.custom-file-input {
    position: relative;
    overflow: hidden;
    display: inline-block;
    width: 100%;
}

.custom-file-input input[type="file"] {
    position: absolute;
    left: 0;
    top: 0;
    opacity: 0;
    cursor: pointer;
    height: 100%;
    width: 100%;
}

.custom-file-label {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 10px;
    background-color: #f8f9fa;
    border: 2px dashed #ced4da;
    border-radius: 8px;
    color: #495057;
    text-align: center;
    cursor: pointer;
    transition: background-color 0.3s ease, border-color 0.3s ease;
}

.custom-file-label:hover {
    background-color: #e9ecef;
    border-color: #adb5bd;
}

.upload-icon {
    margin-right: 8px;
    font-size: 18px;
}

.upload-text {
    font-size: 16px;
}

.image-preview img {
    max-width: 100%;
    height: auto;
    border: 2px solid #dee2e6;
    border-radius: 8px;
}

/* Ảnh đại diện chính  */
.preview-main {
    max-width: 200px;
    max-height: 200px;
    object-fit: cover;
}

/* Ảnh liên quan */
.preview-related {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border: 1px solid #ddd;
    padding: 2px;
}

.related-image-container {
    display: inline-block;
}

/* Định dạng nút X */
.btn-danger {
    width: 24px;
    height: 24px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-size: 12px;
}

/* Định vị nút X trên ảnh */
.position-relative {
    position: relative;
}

.position-absolute {
    position: absolute;
}

.top-0 {
    top: 0;
}

.end-0 {
    right: 0;
}

.m-1 {
    margin: 0.25rem;
}
</style>
<template>
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="/admin">Trang chủ</a></li>
            <li class="breadcrumb-item">Danh sách sản phẩm</li>
        </ol>
    </nav>
    <h2 class="text-bold text-body-emphasis mb-5">Danh sách sản phẩm</h2>
    <div>
        <!-- Search -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
            <div id="searchModel" class="col-12 col-md-10">
                <form class="d-flex align-items-center gap-3 flex-wrap mb-4" @submit.prevent="fetchProduct">
                    <div class="col-12 col-md-2 col-lg-2">
                        <input name="name" v-model="filters.name" placeholder="Tên" type="text"
                            class="form-control data-value empty">
                    </div>
                    <div class="col-12 col-md-2 col-lg-2">
                        <input name="code" v-model="filters.code" placeholder="Mã" type="text"
                            class="form-control data-value empty">
                    </div>
                    <div class="col-12 col-md-2 col-lg-3">
                        <input name="hashtag" v-model="filters.tag" placeholder="Hastag" type="text"
                            class="form-control data-value empty">
                    </div>
                    <div class="col-12 col-md-2 d-flex justify-content-start">
                        <button type="submit" class="btn btn-sm btn-phoenix-info btn-filter me-2" title="Lọc">
                            <span class="fas fa-filter text-info fs-9 me-2"></span>Lọc
                        </button>
                        <button @click="resetFilters" class="btn btn-sm btn-phoenix-warning" id="deleteFilter"
                            type="button">
                            Xoá lọc
                        </button>
                    </div>
                </form>
            </div>

            <div class="col-12 col-md-2 text-end mt-2 mt-md-0">
                <button class="btn btn-primary" type="button" data-bs-toggle="modal" @click="openModalCreate()">
                    <span class="fas fa-plus me-2"></span>Thêm sản phẩm
                </button>
            </div>
        </div>
        <!-- Table -->
        <div class="mx-n4 mx-lg-n6 px-4 px-lg-6 mb-9 bg-body-emphasis mt-2 position-relative top-1" id="list_products">
            <div class="table-responsive quote-table-container scrollbar ms-n1 ps-1">
                <table class="table table-hover table-sm fs-9 mb-0 text-truncate">
                    <thead>
                        <tr>
                            <th class="align-middle text-uppercase text-center">Stt</th>
                            <th class="align-middle text-uppercase text-center">Ảnh đại diện</th>
                            <th class="align-middle text-uppercase text-start">Tên</th>
                            <th class="align-middle text-uppercase text-start">Mã</th>
                            <th class="align-middle text-uppercase text-start">Danh mục</th>
                            <th class="align-middle text-uppercase text-end">Giá</th>
                            <th class="align-middle text-uppercase text-start">Sản phẩm nổi bật</th>
                            <th class="align-middle text-uppercase text-start">SEO Tiêu đề</th>
                            <th class="align-middle text-uppercase text-start">SEO mô tả</th>
                            <th class="align-middle text-uppercase text-start">Từ khóa</th>
                            <th class="align-middle text-uppercase text-center">Trạng thái</th>
                            <th class="align-middle text-uppercase text-center">Hành động</th>
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
                        <tr v-if="products.length === 0 && !loading">
                            <td colspan="13" class="text-center fw-bold fs-7 text-danger">Chưa có dữ liệu</td>
                        </tr>
                        <tr v-for="(product, index) in products" :key="product.id">
                            <td class="align-middle text-center">{{ index + 1 }}</td>
                            <td class="align-middle text-center">
                                <img @click="openModalShow(product)"
                                    :src="product.image || '/storage/categories/null.jpg'" alt="Ảnh sản phẩm"
                                    class="img-thumbnail cursor-pointer" width="90" height="70">
                            </td>
                            <td class="align-middle text-start text-wrap">{{ product.name }}</td>
                            <td class="align-middle text-start">
                                <router-link :to="`/admin/product-attribute/${product.id}/${product.code}`">{{
                                    product.code }}</router-link>
                            </td>
                            <td class="align-middle text-start">{{ product?.category?.name }}</td>
                            <td class="align-middle text-end">{{ formatNumber(product.price) }}
                            </td>
                            <td class="align-middle text-center">{{ product.featured ? 'Có' : 'Không' }}</td>
                            <td class="align-middle text-start white-space-nowrap truncate-char" style="width: 100px;"
                                data-bs-toggle="tooltip" title="Seo tieu de">
                                {{ product.meta_title }}
                            </td>
                            <td class="align-middle text-start white-space-nowrap truncate-char"
                                v-html="product.meta_description"></td>
                            <td class="align-middle text-start">
                                <span v-for="(tag, tagIndex) in product.hashtag.split(',')" :key="tagIndex"
                                    class="badge bg-primary bg-opacity-10 text-primary me-1 cursor-pointer"
                                    @click="updateFilterTag(tag.trim())">
                                    {{ tag.trim() }}
                                </span>
                            </td>
                            <td class="align-middle text-center">
                                <span class="fs-10 badge"
                                    :class="product.status == 1 ? 'bg-success-subtle text-success-emphasis' : 'bg-danger-subtle text-danger-emphasis'">
                                    {{ product.status == 1 ? 'Hoạt động' : 'Không hoạt động' }}
                                </span>
                            </td>
                            <td class="align-middle text-center">
                                <div class="position-relative">
                                    <button class="btn btn-edit-show btn-sm btn-phoenix-secondary text-info me-1 fs-10"
                                        @click="openModalUpdate(product)" type="button" data-bs-toggle="modal">
                                        <span class="fas far fa-edit"></span>
                                    </button>
                                    <button class='btn btn-edit-show btn-sm btn-phoenix-secondary text-info me-1 fs-10'
                                        @click="openModalShow(product)" title='Xem chi tiết' type='button'>
                                        <span class='fas far fa-eye'></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination v-if="dataPanigate" :response="dataPanigate" @getData="fetchProduct" />
        </div>

        <!--  modal thêm mới sản phẩm  -->
        <div class="modal fade" id="addModel" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content form-open">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Thêm mới sản phẩm</h5>
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

                            <div class="col-md-6">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input v-model="form.name" type="text" name="name"
                                                class="form-control validate set-0 data-value" placeholder="Tên">
                                            <label>Tên</label>
                                            <p v-if="errors.name" class="text-danger mt-2 fs-9 ms-2">{{ errors.name[0]
                                                }}</p>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input v-model="form.code" type="text" name="code"
                                                class="form-control validate set-0 data-value" placeholder="Mã">
                                            <label>Mã</label>
                                            <p v-if="errors.code" class="text-danger mt-2 fs-9 ms-2">{{ errors.code[0]
                                                }}</p>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input v-model="form.import_price" type="text" name="import_price" value="0"
                                                @input="form.import_price = formatBalance($event.target.value)"
                                                class="form-control set-0 validate data-value" placeholder="Giá">
                                            <label>Giá nhập</label>
                                            <p v-if="errors.import_price" class="text-danger mt-2 fs-9 ms-2">{{
                                                errors.import_price[0]
                                            }}</p>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input v-model="form.price_sale" type="text" name="price_sale" value="0"
                                                @input="form.price_sale = formatBalance($event.target.value)"
                                                class="form-control set-0 validate data-value" placeholder="Giá">
                                            <label>Giá sale</label>
                                            <p v-if="errors.price_sale" class="text-danger mt-2 fs-9 ms-2">{{
                                                errors.price_sale[0]
                                            }}</p>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="form-floating">
                                            <select v-model="form.features" name="features" class="form-select choice">
                                                <option value="0">Có</option>
                                                <option value="1">Không</option>
                                            </select>
                                            <label class="floating-label-cus">Sản phẩm nổi bật</label>
                                            <p v-if="errors.features" class="text-danger mt-2 fs-9 ms-2">{{
                                                errors.features[0] }}</p>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="row g-3">

                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input v-model="form.slug" type="text" name="slug"
                                                class="form-control validate set-0 data-value" placeholder="Slug">
                                            <label>Slug</label>
                                            <p v-if="errors.slug" class="text-danger mt-2 fs-9 ms-2">{{ errors.slug[0]
                                            }}</p>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input v-model="form.hashtag" type="text" name="hashtag"
                                                class="form-control validate set-0 data-value" placeholder="Từ khóa">
                                            <label>Hashtag</label>
                                            <p v-if="errors.hashtag" class="text-danger mt-2 fs-9 ms-2">{{
                                                errors.hashtag[0] }}</p>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input v-model="form.price" type="text" name="price" value="0"
                                                @input="form.price = formatBalance($event.target.value)"
                                                class="form-control set-0 validate data-value" placeholder="Giá">
                                            <label>Giá bán</label>
                                            <p v-if="errors.price" class="text-danger mt-2 fs-9 ms-2">{{ errors.price[0]
                                                }}</p>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="form-floating">
                                            <select v-model="form.is_recommen" name="is_recommen"
                                                class="form-select choice">
                                                <option value="0">Không</option>
                                                <option value="1">Có</option>
                                            </select>
                                            <label class="floating-label-cus">Sản phẩm đề xuất</label>
                                            <p v-if="errors.is_recommen" class="text-danger mt-2 fs-9 ms-2">{{
                                                errors.is_recommen[0] }}</p>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <select v-model="form.category_id" name="category_id"
                                                class="form-select choice">
                                                <option value="">Chọn danh mục</option>
                                                <option v-for="(categories, index) in category" :key="categories.id"
                                                    :value="categories.id">
                                                    {{ categories.name }}
                                                </option>
                                            </select>
                                            <label class="floating-label-cus">Danh mục</label>
                                            <p v-if="errors.category_id" class="text-danger mt-2 fs-9 ms-2">{{
                                                errors.category_id[0] }}
                                            </p>
                                        </div>
                                    </div>


                                </div>
                            </div>

                            <div class="col-sm-6 col-md-12 text-danger fs-9 text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                    class="bi bi-exclamation-triangle" viewBox="0 0 16 16">
                                    <path
                                        d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.15.15 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.2.2 0 0 1-.054.06.1.1 0 0 1-.066.017H1.146a.1.1 0 0 1-.066-.017.2.2 0 0 1-.054-.06.18.18 0 0 1 .002-.183L7.884 2.073a.15.15 0 0 1 .054-.057m1.044-.45a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767z" />
                                    <path
                                        d="M7.002 12a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 5.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z" />
                                </svg>
                                <span><i> Khuyến cáo: Kích thước ảnh <strong>1588 x 1195 </strong></i> (định dạng
                                    <strong>JPG</strong>)</span>
                            </div>

                            <div class="col-12">
                                <div class="row g-3">
                                    <!-- Ô chọn ảnh chính -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <div class="custom-file-input">
                                                <input type="file" name="main_image" class="form-control"
                                                    id="mainImageUpload" @change="previewMainImage($event)"
                                                    accept="image/*">
                                                <label for="mainImageUpload" class="custom-file-label">
                                                    <span class="upload-icon"><i class="fas fa-upload"></i></span>
                                                    <span class="upload-text">Chọn ảnh chính</span>
                                                </label>
                                            </div>
                                            <div v-if="previewImages.main" class="image-preview mt-3">
                                                <div class="main-image-container position-relative">
                                                    <img :src="previewImages.main" alt="Main Image"
                                                        class="rounded shadow-sm preview-main">
                                                    <button
                                                        class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1"
                                                        @click="removeMainImage($event)">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <p v-if="errors.main_image" class="text-danger mt-2 fs-9 ms-2">{{
                                                errors.main_image[0] }}</p>
                                        </div>
                                    </div>

                                    <!-- Ô chọn ảnh liên quan -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <div class="custom-file-input">
                                                <input type="file" name="related_images" class="form-control"
                                                    id="relatedImageUpload" @change="previewRelatedImages($event)"
                                                    multiple accept="image/*">
                                                <label for="relatedImageUpload" class="custom-file-label">
                                                    <span class="upload-icon"><i class="fas fa-upload"></i></span>
                                                    <span class="upload-text">Chọn ảnh liên quan (Có thể chọn
                                                        nhiều)</span>
                                                </label>
                                            </div>
                                            <div v-if="previewImages.related.length > 0"
                                                class="image-preview mt-3 d-flex flex-wrap gap-2">
                                                <div v-for="(image, index) in previewImages.related" :key="index"
                                                    class="related-image-container position-relative">
                                                    <img :src="image" alt="Related Image"
                                                        class="rounded shadow-sm preview-related">
                                                    <button
                                                        class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1"
                                                        @click="removeRelatedImage(index, $event)">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <p v-if="errors.related_images" class="text-danger mt-2 fs-9 ms-2">{{
                                                errors.related_images[0] }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input v-model.trim="form.image_alt" type="text" name="image_alt"
                                                id="image_alt" class="form-control validate set-0 data-value"
                                                placeholder="ALT ảnh đại diện">
                                            <label for="image_alt">ALT ảnh đại diện</label>
                                            <p v-if="errors.image_alt" class="text-danger mt-2 fs-9 ms-2">{{
                                                errors.image_alt[0] }}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <select class="form-select" v-model="form.status" name="status" id="status">
                                                <option value="1">Hoạt Động</option>
                                                <option value="2">Không Hoạt Động</option>

                                            </select>
                                            <label for="status">Trạng thái</label>
                                            <p v-if="errors.status" class="text-danger mt-2 fs-9 ms-2">{{
                                                errors.status[0] }}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-floating">
                                            <textarea v-model.trim="form.meta_title" id="meta_title" name="meta_title"
                                                class="data-value empty form-control" placeholder="SEO tiêu đề sản phẩm"
                                                style="height: 80px;"></textarea>
                                            <label for="meta_title">SEO tiêu đề sản phẩm</label>
                                            <p v-if="errors.meta_title" class="text-danger mt-2 fs-9 ms-2">{{
                                                errors.meta_title[0] }}</p>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-floating">
                                            <textarea v-model.trim="form.meta_description" id="editorContent"
                                                class="form-control validate set-0 data-value"
                                                placeholder="Mô tả sản phẩm" style="height: 100px;"></textarea>
                                            <label for="editorContent">Mô tả sản phẩm</label>
                                            <p v-if="errors.meta_description" class="text-danger mt-2 fs-9 ms-2">{{
                                                errors.meta_description[0] }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 gy-6">
                                <div class="row g-3 justify-content-center">
                                    <div class="col-auto">
                                        <button type="button" class="btn btn-close-model btn-secondary mx-1"
                                            data-bs-dismiss="modal">
                                            Huỷ
                                        </button>
                                        <button type="submit" id="btnAdd" class="btn btn-primary btn-submit mx-1"
                                            title="Thêm mới">
                                            Thêm mới
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- modal chỉnh sửa sản phẩm -->
        <div class="modal fade" id="editModel" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content form-open">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Chỉnh sửa sản phẩm</h5>
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
                        <form class="row g-3" @submit.prevent="updateForm">
                            <!-- Thêm trường id ẩn -->
                            <input type="hidden" v-model="editForm.id">
                            <div class="col-md-6">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input v-model="editForm.name" type="text" name="name"
                                                class="form-control validate set-0 data-value" placeholder="Tên">
                                            <label>Tên</label>
                                            <p v-if="errors.name" class="text-danger mt-2 fs-9 ms-2">{{
                                                errors.name[0] }}</p>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input v-model="editForm.code" type="text" name="code"
                                                class="form-control validate set-0 data-value" placeholder="Mã">
                                            <label>Mã</label>
                                            <p v-if="errors.code" class="text-danger mt-2 fs-9 ms-2">{{
                                                errors.code[0] }}</p>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input v-model="editForm.import_price" type="text" name="import_price"
                                                value="0"
                                                @input="editForm.import_price = formatBalance($event.target.value)"
                                                class="form-control set-0 validate data-value" placeholder="Giá">
                                            <label>Giá nhập</label>
                                            <p v-if="errors.import_price" class="text-danger mt-2 fs-9 ms-2">{{
                                                errors.import_price[0] }}</p>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input v-model="editForm.price_sale" type="text" name="price_sale" value="0"
                                                @input="editForm.price_sale = formatBalance($event.target.value)"
                                                class="form-control set-0 validate data-value" placeholder="Giá">
                                            <label>Giá sale</label>
                                            <p v-if="errors.price_sale" class="text-danger mt-2 fs-9 ms-2">{{
                                                errors.price_sale[0] }}</p>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="form-floating">
                                            <select v-model="editForm.features" name="features"
                                                class="form-select choice">
                                                <option value="0">Có</option>
                                                <option value="1">Không</option>
                                            </select>
                                            <label class="floating-label-cus">Sản phẩm nổi bật</label>
                                            <p v-if="errors.features" class="text-danger mt-2 fs-9 ms-2">{{
                                                errors.features[0] }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input v-model="editForm.slug" type="text" name="slug"
                                                class="form-control validate set-0 data-value" placeholder="Slug">
                                            <label>Slug</label>
                                            <p v-if="errors.slug" class="text-danger mt-2 fs-9 ms-2">{{
                                                errors.slug[0] }}</p>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input v-model="editForm.hashtag" type="text" name="hashtag"
                                                class="form-control validate set-0 data-value" placeholder="Từ khóa">
                                            <label>Hashtag</label>
                                            <p v-if="errors.hashtag" class="text-danger mt-2 fs-9 ms-2">{{
                                                errors.hashtag[0] }}</p>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input v-model="editForm.price" type="text" name="price" value="0"
                                                @input="editForm.price = formatBalance($event.target.value)"
                                                class="form-control set-0 validate data-value" placeholder="Giá">
                                            <label>Giá bán</label>
                                            <p v-if="errors.price" class="text-danger mt-2 fs-9 ms-2">{{
                                                errors.price[0] }}</p>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="form-floating">
                                            <select v-model="editForm.is_recommen" name="is_recommen"
                                                class="form-select choice">
                                                <option value="0">Không</option>
                                                <option value="1">Có</option>
                                            </select>
                                            <label class="floating-label-cus">Sản phẩm đề xuất</label>
                                            <p v-if="errors.is_recommen" class="text-danger mt-2 fs-9 ms-2">{{
                                                errors.is_recommen[0] }}</p>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <select v-model="editForm.category_id" name="category_id"
                                                class="form-select choice">
                                                <option value="">Chọn danh mục</option>
                                                <option v-for="(categories, index) in category" :key="categories.id"
                                                    :value="categories.id">
                                                    {{ categories.name }}
                                                </option>
                                            </select>
                                            <label class="floating-label-cus">Danh mục</label>
                                            <p v-if="errors.category_id" class="text-danger mt-2 fs-9 ms-2">{{
                                                errors.category_id[0] }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-6 col-md-12 text-danger fs-9 text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                    class="bi bi-exclamation-triangle" viewBox="0 0 16 16">
                                    <path
                                        d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.15.15 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.2.2 0 0 1-.054.06.1.1 0 0 1-.066.017H1.146a.1.1 0 0 1-.066-.017.2.2 0 0 1-.054-.06.18.18 0 0 1 .002-.183L7.884 2.073a.15.15 0 0 1 .054-.057m1.044-.45a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767z" />
                                    <path
                                        d="M7.002 12a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 5.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z" />
                                </svg>
                                <span><i> Khuyến cáo: Kích thước ảnh <strong>1588 x 1195 </strong></i> (định dạng
                                    <strong>JPG</strong>)</span>
                            </div>

                            <div class="col-12">
                                <div class="row g-3">
                                    <!-- Ô chọn ảnh chính -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <div class="custom-file-input">
                                                <input type="file" name="main_image" class="form-control"
                                                    id="editMainImageUpload" @change="previewEditMainImage($event)"
                                                    accept="image/*">
                                                <label for="editMainImageUpload" class="custom-file-label">
                                                    <span class="upload-icon"><i class="fas fa-upload"></i></span>
                                                    <span class="upload-text">Chọn ảnh chính mới</span>
                                                </label>
                                            </div>
                                            <!-- Hiển thị ảnh hiện tại hoặc ảnh mới chọn -->
                                            <div class="image-preview mt-3 d-flex justify-content-center">
                                                <div class="main-image-container position-relative">
                                                    <img :src="editPreviewImages.main || editForm.current_image_url"
                                                        alt="Main Image" class="rounded shadow-sm preview-main">
                                                    <button v-if="editPreviewImages.main"
                                                        class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1"
                                                        @click="removeEditMainImage($event)">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <p v-if="errors.main_image" class="text-danger mt-2 fs-9 ms-2">{{
                                                errors.main_image[0] }}</p>
                                        </div>
                                    </div>

                                    <!-- Ô chọn ảnh liên quan -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <div class="custom-file-input">
                                                <input type="file" name="related_images" class="form-control"
                                                    id="editRelatedImageUpload"
                                                    @change="previewEditRelatedImages($event)" multiple
                                                    accept="image/*">
                                                <label for="editRelatedImageUpload" class="custom-file-label">
                                                    <span class="upload-icon"><i class="fas fa-upload"></i></span>
                                                    <span class="upload-text">Thêm ảnh liên quan mới</span>
                                                </label>
                                            </div>
                                            <!-- Hiển thị cả ảnh cũ và ảnh mới -->
                                            <div v-if="editForm.current_related_images.length > 0 || editPreviewImages.related.length > 0"
                                                class="image-preview mt-3 d-flex flex-wrap gap-2">
                                                <!-- Ảnh cũ -->
                                                <div v-for="(image, index) in editForm.current_related_images"
                                                    :key="'old-' + index"
                                                    class="related-image-container position-relative">
                                                    <img :src="image.url" alt="Related Image"
                                                        class="rounded shadow-sm preview-related">
                                                    <button
                                                        class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1"
                                                        @click="removeOldRelatedImage(index, $event, image.id)">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                                <!-- Ảnh mới -->
                                                <div v-for="(image, index) in editPreviewImages.related"
                                                    :key="'new-' + index"
                                                    class="related-image-container position-relative">
                                                    <img :src="image" alt="New Related Image"
                                                        class="rounded shadow-sm preview-related">
                                                    <button
                                                        class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1"
                                                        @click="removeNewRelatedImage(index, $event)">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <p v-if="errors.related_images" class="text-danger mt-2 fs-9 ms-2">{{
                                                errors.related_images[0] }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input v-model.trim="editForm.image_alt" type="text" name="image_alt"
                                                id="editImageAlt" class="form-control validate set-0 data-value"
                                                placeholder="ALT ảnh đại diện">
                                            <label for="editImageAlt">ALT ảnh đại diện</label>
                                            <p v-if="errors.image_alt" class="text-danger mt-2 fs-9 ms-2">{{
                                                errors.image_alt[0] }}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <select class="form-select" v-model="editForm.status" name="status"
                                                id="editStatus">
                                                <option value="1">Hoạt Động</option>
                                                <option value="2">Không Hoạt Động</option>
                                            </select>
                                            <label for="editStatus">Trạng thái</label>
                                            <p v-if="errors.status" class="text-danger mt-2 fs-9 ms-2">{{
                                                errors.status[0] }}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-floating">
                                            <textarea v-model.trim="editForm.meta_title" id="editMetaTitle"
                                                name="meta_title" class="data-value empty form-control"
                                                placeholder="SEO tiêu đề sản phẩm" style="height: 80px;"></textarea>
                                            <label for="editMetaTitle">SEO tiêu đề sản phẩm</label>
                                            <p v-if="errors.meta_title" class="text-danger mt-2 fs-9 ms-2">{{
                                                errors.meta_title[0] }}</p>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-floating">
                                            <textarea v-model.trim="editForm.meta_description" id="editEditorContent"
                                                class="form-control validate set-0 data-value"
                                                placeholder="Mô tả sản phẩm" style="height: 100px;"></textarea>
                                            <label for="editEditorContent">Mô tả sản phẩm</label>
                                            <p v-if="errors.meta_description" class="text-danger mt-2 fs-9 ms-2">{{
                                                errors.meta_description[0] }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 gy-6">
                                <div class="row g-3 justify-content-center">
                                    <div class="col-auto">
                                        <button type="button" class="btn btn-close-model btn-secondary mx-1"
                                            data-bs-dismiss="modal">
                                            Huỷ
                                        </button>
                                        <button type="submit" id="btnEdit" class="btn btn-primary btn-submit mx-1"
                                            title="Cập nhật">
                                            Cập nhật
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
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
                                        <div class="image-display-container">
                                            <!-- Ảnh chính -->
                                            <div class="main-image-section mb-4">
                                                <h6 class="fw-bold text-primary mb-3">Ảnh chính sản phẩm</h6>
                                                <div class="main-image-wrapper border rounded p-3 bg-light text-center"
                                                    style="max-height: 350px; min-height: 200px;">
                                                    <img v-if="editForm.current_image_url"
                                                        :src="editForm.current_image_url" alt="Main product image"
                                                        class="img-fluid rounded"
                                                        style="max-height: 300px; object-fit: contain;">
                                                    <div v-else
                                                        class="d-flex align-items-center justify-content-center h-100">
                                                        <span class="text-muted">Không có ảnh chính</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Ảnh phụ -->
                                            <div v-if="editForm.current_related_images.length > 0"
                                                class="related-images-section">
                                                <h6 class="fw-bold text-primary mb-3">Ảnh phụ sản phẩm</h6>
                                                <div class="row g-3">
                                                    <div v-for="(image, index) in editForm.current_related_images"
                                                        :key="index" class="col-6 col-sm-4 col-md-3">
                                                        <div
                                                            class="related-image-wrapper position-relative border rounded p-2 bg-white h-100">
                                                            <img :src="image.url" :alt="'Product image ' + (index + 1)"
                                                                class="img-fluid rounded"
                                                                style="height: 80px; width: 100%; object-fit: cover;">
                                                            <span
                                                                class="position-absolute top-0 end-0 bg-secondary text-white px-2 rounded">
                                                                {{ index + 1 }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div v-else class="alert alert-light border">
                                                <i class="fas fa-info-circle me-2"></i> Sản phẩm này chưa có ảnh phụ
                                            </div>
                                        </div>
                                        <div class="card-footer bg-transparent border-0 pt-3">
                                            <h6 class="fw-bold text-primary mb-3">Thông tin cơ bản</h6>
                                            <dl class="row mb-0">
                                                <dt class="col-sm-3 text-muted">Tên SP:</dt>
                                                <dd class="col-sm-9 fw-bold">{{ editForm.name }}</dd>

                                                <dt class="col-sm-3 text-muted">Mã SP:</dt>
                                                <dd class="col-sm-9 fw-bold text-danger">{{ editForm.code }}</dd>

                                                <dt class="col-sm-3 text-muted">Danh mục:</dt>
                                                <dd class="col-sm-9 fw-bold">{{ editForm.category_name }}</dd>

                                                <dt class="col-sm-3 text-muted">Quốc gia:</dt>
                                                <dd class="col-sm-9 fw-bold">{{ editForm.country_name }}</dd>

                                                <dt class="col-sm-3 text-muted">Thông số:</dt>
                                                <dd class="col-sm-9 fw-bold" v-html="editForm.attribute_description">
                                                </dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Cột phải - Thông số chi tiết -->
                            <div class="col-lg-6">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <h6 class="fw-bold text-primary mb-4">Thông số </h6>
                                        <dl class="row mb-0">
                                            <dt class="col-sm-5 text-muted">Giá bán trước</dt>
                                            <dd class="col-sm-7 fw-bold text-danger text-decoration-line-through">{{
                                                editForm.price_sale
                                            }}
                                            </dd>
                                            <dt class="col-sm-5 text-muted">Giá bán</dt>
                                            <dd class="col-sm-7 fw-bold text-success">{{ editForm.price
                                                }}
                                            </dd>

                                            <dt class="col-sm-5 text-muted">Nổi bật</dt>
                                            <dd class="col-sm-7 fw-bold">
                                                <span :class="editForm.features == 1 ? 'text-success' : 'text-danger'">
                                                    {{ editForm.features == 1 ? 'Có' : 'Không' }}
                                                </span>
                                            </dd>

                                            <dt class="col-sm-5 text-muted">Đề xuất</dt>
                                            <dd class="col-sm-7 fw-bold">
                                                <span
                                                    :class="editForm.is_recommend == 1 ? 'text-warning' : 'text-danger'">
                                                    {{ editForm.is_recommend == 1 ? 'Có' : 'Không' }}
                                                </span>
                                            </dd>

                                            <dt class="col-sm-5 text-muted">Hashtag</dt>
                                            <dd class="col-sm-7 fw-bold">
                                                <div class="d-flex flex-wrap gap-2">
                                                    <span v-for="(tag, index) in editForm.hashtag.split(',')"
                                                        :key="index"
                                                        class="badge bg-primary bg-opacity-10 text-primary">
                                                        {{ tag.trim() }}
                                                    </span>
                                                </div>
                                            </dd>

                                            <dt class="col-sm-5 text-muted">Slug</dt>
                                            <dd class="col-sm-7 fw-bold">
                                                <span @click="openSlug"
                                                    class="text-primary cursor-pointer d-block mb-2">
                                                    {{ statusSlug ? 'Ẩn' : 'Xem' }}
                                                </span>

                                                <transition name="fade">
                                                    <div v-if="statusSlug" class="mt-2">
                                                        <a :href="`/${editForm.slug}`"
                                                            class="text-decoration-none text-truncate d-inline-block text-wrap bg-light rounded p-2"
                                                            style="max-width: 200px;">
                                                            {{ editForm.slug }}
                                                        </a>
                                                    </div>
                                                </transition>
                                            </dd>

                                            <dt class="col-sm-5 text-muted">Trạng thái</dt>
                                            <dd
                                                class="col-sm-7 fw-bold fs-10 badge text-truncate px-3 py-2 rounded-pill">
                                                {{ editForm.status == 1 ? 'Hoạt động' : 'Không hoạt động' }}
                                            </dd>
                                            <dt class=" col-sm-5 text-muted">ALT Ảnh</dt>
                                            <dd class="col-sm-7 fw-bold">{{ editForm.image_alt }}</dd>
                                        </dl>
                                    </div>
                                </div>

                            </div>


                            <!-- Thông tin SEO -->
                            <div div class="card shadow-sm mt-4 col-lg-12">
                                <div class="card-body">
                                    <h6 class="fw-bold text-primary mb-4">Thông tin SEO</h6>
                                    <div class="mb-3">
                                        <dt class="text-muted">Tiêu đề SEO</dt>
                                        <dd class="">{{ editForm.meta_title }}</dd>
                                    </div>
                                    <div class="mb-3">
                                        <dt class="text-muted">Từ khóa</dt>
                                        <dd class="">{{ editForm.keyword }}</dd>
                                    </div>
                                    <div class="mb-3">
                                        <dt class="text-muted">Mô tả ngắn</dt>
                                        <dd class="">{{ editForm.short_description }}</dd>
                                    </div>
                                    <div class="mb-0">
                                        <dt class="text-muted">Mô tả SEO</dt>
                                        <dd v-html="(editForm.meta_description)">
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
