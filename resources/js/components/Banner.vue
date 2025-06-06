<script setup>
import { ref, onMounted, watch, reactive } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import { Modal } from 'bootstrap';
import { dateTimeFormat } from '../utils'
import Pagination from '../components/Pagination.vue';

const dataPanigate = ref([])
const banner = ref([]);
const loading = ref(true);
const errors = ref({});
const toast = useToast();
const form = reactive({
    image: '',
    url: '',
    image_alt: '',
});

const editForm = reactive({
    id: '',
    image: '',
    url: '',
    image_alt: '',

});
const previewImages = reactive({
    image: '',
});

const fetchBanner = async (page = 1) => {
    try {
        const response = await axios.get('/api/banner', {
            params: { page },
        });
        console.log(response);
        banner.value = response.data.data;
        dataPanigate.value = response.data;
    } catch (error) {
        console.error('Error fetching countries:', error);
        toast.error('Có lỗi xảy ra!');
    } finally {
        loading.value = false;
    }
};

// Gọi API khi component mounted
onMounted(() => {
    fetchBanner();
});

const openModalCreate = () => {
    errors.value = {};
    removeMainImage();
    const modal = document.getElementById('addModel');
    const modalInit = new Modal(modal);
    modalInit.show();
};


const previewImage = (event, type) => {
    const files = event.target.files;

    if (type === 'image') {
        if (files.length > 0) {
            form.image = files[0]; // Lưu file ảnh vào form
            const reader = new FileReader();
            reader.onload = (e) => {
                previewImages.image = e.target.result; // Cập nhật preview ảnh
            };
            reader.readAsDataURL(files[0]);
        }
    }
};
const removeMainImage = () => {
    previewImages.image = '';
    form.image = '';
    editForm.image = '';
};
const openModalUpdate = (ban) => {
    populateEditForm(ban);
    removeMainImage();
    const modal = document.getElementById('editModal');
    const modalEdit = new Modal(modal);
    modalEdit.show();
};

const deleteItem = (id) => {
    if (confirm('Bạn có chắc chắn muốn xóa bản ghi này?')) {
        axios.delete(`/api/banner/${id}`)
            .then(response => {
                fetchBanner();
                toast.success('Xóa bản ghi thành công!');
            })
            .catch(error => {
                console.error('Lỗi xóa:', error);
                toast.error('Đã xảy ra lỗi khi xóa bản ghi. Vui lòng thử lại.');
            });
    }
};


const submitForm = async () => {
    try {
        const formData = new FormData();
        for (const key in form) {
            formData.append(key, form[key]);
        }
        const response = await axios.post(`/api/banner`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });
        const modal = document.getElementById('addModel');
        const modalAdd = Modal.getInstance(modal) || new Modal(modal);
        modalAdd.hide();

        // Reset form
        Object.assign(form, {
            image: '',
            url: '',
            image_alt: '',

        });
        errors.value = {};
        toast.success('Thêm mới banner thành công!');
        fetchBanner();
    } catch (error) {
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors; // <-- Sửa chỗ này
        } else {
            console.error('Error creating banner:', error);
            toast.error('Có lỗi xảy ra khi thêm banner');
        }
    }
};

const populateEditForm = (ban) => {
    Object.assign(editForm, {
        id: ban.id,
        image: '',
        url: ban.url,
        image_alt: ban.image_alt,
    });
};

const submitEditForm = async () => {
    try {
        const formData = new FormData();
        for (const key in editForm) {
            formData.append(key, editForm[key]);
        }
        const imageInput = document.getElementById('editImageUpload');
        if (imageInput.files.length > 0) {
            formData.append('image', imageInput.files[0]);
        }

        const response = await axios.post(`/api/banner/${editForm.id}`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });
        const modal = document.getElementById('editModal');
        const modalEdit = Modal.getInstance(modal) || new Modal(modal);
        modalEdit.hide();

        // Reset editForm
        Object.assign(editForm, {
            image: '',
            url: '',
        });
        errors.value = {};
        toast.success('Cập nhật banner thành công!');
        fetchBanner();
    } catch (error) {
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors; // <-- Sửa chỗ này
        } else {
            console.error('Error updating banner:', error);
            toast.error('Có lỗi xảy ra khi cập nhật banner');
        }
    }

};

</script>
<template>
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="/admin">Trang chủ</a></li>
            <li class="breadcrumb-item">Danh sách Banner</li>
        </ol>
    </nav>
    <h2 class="text-bold text-body-emphasis mb-5">Danh sách Banner</h2>
    <div>
        <!-- Search -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="col-auto ms-auto text-end">
                <button class="btn btn-primary" type="button" data-bs-toggle="modal" @click="openModalCreate()">
                    <span class="fas fa-plus me-2"></span>Thêm Banner
                </button>
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
                            <th class="align-middle text-center text-uppercase">Ảnh</th>
                            <th class="align-middle text-start text-uppercase">url</th>
                            <th class="align-middle text-start text-uppercase">Alt ảnh</th>
                            <th class="align-middle text-start text-uppercase">Ngày thêm</th>
                            <th class="align-middle text-center text-uppercase">hành động</th>
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
                        <tr v-if="banner.length === 0 && !loading">
                            <td colspan="8" class="text-center fw-bold fs-7 text-danger">Chưa có dữ liệu</td>
                        </tr>
                        <tr v-else v-for="(ban, index) in banner" :key="ban.id">
                            <td class="align-middle text-center">{{ index + 1 }}</td>
                            <td class="align-middle text-center">
                                <img :src="ban.image || '/storage/categories/null.jpg'" alt="Category Image"
                                    style="border-radius: 10px;" width="150" height="100">
                            </td>
                            <td class="align-middle text-start text-wrap">{{ ban.url }}</td>
                            <td class="align-middle text-start text-wrap">{{ ban.image_alt ?? 'Không có' }}</td>


                            <td class="align-middle text-start">{{ dateTimeFormat(ban.created_at) }}</td>
                            <td class="align-middle text-center">
                                <div class="position-relative">
                                    <button class="btn btn-edit-show btn-sm btn-phoenix-secondary text-info me-1 fs-10"
                                        @click="openModalUpdate(ban)" type="button" data-bs-toggle="modal">
                                        <span class="fas far fa-edit"></span>
                                    </button>
                                    <button class="btn btn-delete btn-sm btn-phoenix-secondary text-danger fs-10"
                                        @click="deleteItem(ban.id)" type="button">
                                        <span class="fas fa-trash-alt"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>
            <Pagination v-if="dataPanigate" :response="dataPanigate" @getData="fetchBanner" />
        </div>

        <!-- thêm mới banner-->
        <div class="modal fade" id="addModel" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class=" modal-content form-open">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Thêm mới banner</h5>
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
                                    <input type="text" v-model="form.url" name="url" class="form-control"
                                        placeholder="Đơn vị tiền tệ">
                                    <label>Url</label>
                                    <div v-if="errors.url" class="text-danger mt-2 fs-9 ms-2">{{ errors.url[0]
                                    }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-12">
                                <div class="form-floating">
                                    <div class="custom-file-input">
                                        <input type="file" name="image" class="form-control" id="imageUpload"
                                            @change="previewImage($event, 'image')">
                                        <label for="imageUpload" class="custom-file-label">
                                            <span class="upload-icon"><i class="fas fa-upload"></i></span>
                                            <span class="upload-text">Chọn ảnh đại diện</span>
                                        </label>
                                    </div>
                                    <div v-if="previewImages.image"
                                        class="image-preview mt-3 text-center position-relative">
                                        <img :src="previewImages.image" alt="Preview Image"
                                            class="img-fluid rounded shadow-sm preview-main">
                                        <button class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1"
                                            @click="removeMainImage">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <p v-if="errors.image" class="text-danger">{{ errors.image[0] }}</p>
                                </div>
                            </div>

                            <div class="col-sm-12 col-md-12">
                                <div class="form-floating">
                                    <input type="text" v-model="form.image_alt" name="url" class="form-control"
                                        placeholder="Đơn vị tiền tệ">
                                    <label>Alt Ảnh</label>
                                    <div v-if="errors.image_alt" class="text-danger mt-2 fs-9 ms-2">{{
                                        errors.image_alt[0] }}
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

        <!-- Modal sửa banner -->
        <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content form-open">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Cập nhật banner</h5>
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
                                    <input type="text" v-model="editForm.url" name="url" class="form-control"
                                        placeholder="Đơn vị tiền tệ">
                                    <label>Url</label>
                                    <div v-if="errors.url" class="text-danger mt-2 fs-9 ms-2">{{ errors.url[0] }}
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-12 col-md-12">
                                <div class="form-floating">
                                    <div class="custom-file-input">
                                        <input type="file" name="image" class="form-control" id="editImageUpload"
                                            @change="previewImage($event, 'image', 'edit')">
                                        <label for="editImageUpload" class="custom-file-label">
                                            <span class="upload-icon"><i class="fas fa-upload"></i></span>
                                            <span class="upload-text">Chọn ảnh đại diện</span>
                                        </label>
                                    </div>
                                    <div v-if="previewImages.image"
                                        class="image-preview mt-3 text-center position-relative">
                                        <img :src="previewImages.image" alt="Preview Image"
                                            class="img-fluid rounded shadow-sm preview-main">
                                        <button class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1"
                                            @click="removeMainImage">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <p v-if="errors.image" class="text-danger">{{ errors.image[0] }}</p>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-12">
                                <div class="form-floating">
                                    <input type="text" v-model="editForm.image_alt" name="url" class="form-control"
                                        placeholder="Đơn vị tiền tệ">
                                    <label>Alt Ảnh</label>
                                    <div v-if="errors.image_alt" class="text-danger mt-2 fs-9 ms-2">{{
                                        errors.image_alt[0] }}
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
</template>
<style scoped>
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
