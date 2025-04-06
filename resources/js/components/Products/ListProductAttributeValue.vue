<script setup>
import { ref, onMounted, reactive } from 'vue';
import axios from 'axios';
import { useRoute } from 'vue-router';
import { useToast } from 'vue-toastification';
import { Modal } from 'bootstrap';
import Pagination from '../Pagination.vue';
import { formatNumber, formatBalance } from '../../utils';
const products = ref([]);
const route = useRoute();
const dataPanigate = ref([])
const loading = ref(true);
const errors = ref({});
const toast = useToast();
const searchQuery = ref('');
const idAttribute = route.params?.idAttribute || null;
const nameAttribute = route.params?.nameAttribute || '';
const productName = route.params?.nameProduct || '';
const productId = route.params?.idProduct || null;
const previewProductImagesList = ref([]);
const previewProductImagesListEdit = ref([]);

const form = reactive({
    product_attribute_id: idAttribute,
    name: '',
    price: '',
    image: '',
    product_images: []  // trường mới: lưu file ảnh sản phẩm
});

const editForm = reactive({
    id: '',
    product_attribute_id: idAttribute,
    name: '',
    price: '',
    image: '',
    product_images: []

});
const previewImages = reactive({
    id: '',
    image: '',
});
const fetchAttributeValue = async () => {

    try {
        const response = await axios.get(`/api/product-attribute-value/${idAttribute}`, {
            params: {
                name: searchQuery.value
            }
        });
        products.value = response.data.data;
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
    fetchAttributeValue();
});


const openModalAdd = () => {
    errors.value = {};
    form.value = {};
    resetPreviewImages();
    previewProductImagesList.value = [];
    const modal = document.getElementById('addModel');
    const addModel = new Modal(modal);
    addModel.show();
}

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
    editForm.image = '';
    form.image = '';
};
const removeMainImageEdit = (id, event) => {
    event.preventDefault();

    if (!id) {
        alert("Lỗi: Không tìm thấy ảnh!");
        return;
    }

    if (confirm("Bạn có chắc chắn muốn xóa ảnh này không?")) {
        previewImages.image = "";
        editForm.image = "";
        form.image = "";
        // Nếu cần xóa từ server, gọi API xóa ảnh
        axios.delete(`/api/product-attribute-value/delete-image/${id}`)
            .then(response => {
                toast.success('Xóa ảnh thành công!');
                fetchAttributeValue();

            })
            .catch(error => {
                console.error("Lỗi khi xóa ảnh", error);
            });
    }
};


// Hàm mới: xử lý chọn nhiều ảnh sản phẩm
const previewProductImages = (event) => {
    const files = event.target.files;
    // Lưu danh sách file vào form.product_images
    form.product_images = Array.from(files);
    // Reset danh sách preview
    previewProductImagesList.value = [];
    // Tạo preview cho từng ảnh
    for (let i = 0; i < files.length; i++) {
        const reader = new FileReader();
        reader.onload = (e) => {
            previewProductImagesList.value.push(e.target.result);
        };
        reader.readAsDataURL(files[i]);
    }
};

// Hàm mới: xóa một ảnh sản phẩm đã chọn
const removeProductImage = (index) => {
    form.product_images.splice(index, 1);
    previewProductImagesList.value.splice(index, 1);
};


const submitForm = async () => {
    try {
        const formData = new FormData();
        for (const key in form) {
            if (key === 'product_images') {
                // Với product_images, thêm từng file vào formData
                form.product_images.forEach(image => {
                    formData.append('product_images[]', image);
                });
            } else {
                formData.append(key, form[key]);
            }
        }
        const response = await axios.post(`/api/product-attribute-value/${idAttribute}`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });
        const modal = document.getElementById('addModel');
        const modalAdd = Modal.getInstance(modal) || new Modal(modal);
        modalAdd.hide();

        // Reset form
        Object.assign(form, {
            product_attribute_id: idAttribute,
            name: '',
            price: '',
            image: '',
            product_images: [],
        });
        errors.value = {};
        toast.success('Thêm mới thành công!');
        fetchAttributeValue();
    } catch (error) {
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.message;

        } else {
            console.error('Error creating category:', error);
        }
    }
};

// --- Các hàm mới dành cho edit modal phần nhiều ảnh sản phẩm ---

const previewProductImagesEdit = (event) => {
    const files = event.target.files;
    editForm.product_images = Array.from(files);
    previewProductImagesListEdit.value = []; // Reset danh sách

    for (let i = 0; i < files.length; i++) {
        const reader = new FileReader();
        reader.onload = (e) => {
            previewProductImagesListEdit.value.push({
                id: Date.now() + i,  // Tạo ID tạm thời để dùng làm key
                image: e.target.result
            });
        };
        reader.readAsDataURL(files[i]);
    }
};


const removeProductImageEdit = (id, event, index) => {

    event.preventDefault();

    if (!id) {
        alert("Lỗi: Không tìm thấy ảnh!");
        return;
    }
    if (confirm("Bạn có chắc chắn muốn xóa ảnh này không?")) {
        editForm.product_images.splice(index, 1);
        previewProductImagesListEdit.value.splice(index, 1);
        // Nếu cần xóa từ server, gọi API xóa ảnh
        axios.delete(`/api/product-attribute-value/delete-image-product-attribute-value/${id}`)
            .then(response => {
                toast.success('Xóa ảnh thành công!');
                fetchAttributeValue();
            })
            .catch(error => {
                console.error("Lỗi khi xóa ảnh", error);
            });
    }
};

const resetPreviewImages = () => {
    previewImages.image = '';

};
const openModalUpdate = (item) => {
    errors.value = {};
    form.value = {};
    resetPreviewImages();
    previewProductImagesListEdit.value = [];
    populateEditForm(item);
    const modal = document.getElementById('editModal');
    const editModel = new Modal(modal);
    editModel.show();

}

const populateEditForm = (item) => {

    Object.assign(editForm, {
        product_attribute_id: item.product_attribute_id,
        id: item.id,
        name: item.name,
        price: formatNumber(item.price),
        image: '',
        product_images: []  // khởi tạo cho edit modal
    });
    // Hiển thị ảnh đại diện
    previewImages.image = item.image;
    previewImages.id = item?.product_images?.id;
    previewProductImagesListEdit.value = item.product_attribute_value_image.map(img => ({
        id: img.id, // Lưu ID của ảnh
        image: '/storage/' + img.image // Lưu đường dẫn ảnh
    }));

};

const submitEditForm = async () => {
    try {
        const formData = new FormData();
        for (const key in editForm) {
            if (key === 'product_images') {
                if (editForm.product_images && editForm.product_images.length > 0) {
                    editForm.product_images.forEach(file => {
                        formData.append('product_images[]', file);
                    });
                }
            } else {
                formData.append(key, editForm[key]);
            }
        }
        const imageInput = document.getElementById('imageUploadEdit');
        if (imageInput.files.length > 0) {
            formData.append('image', imageInput.files[0]);
        }
        // formData.forEach((key, value) => {
        //     console.log(key, value);
        // });
        const response = await axios.post(`/api/product-attribute-value/update/${editForm.id}`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });


        const modal = document.getElementById('editModal');
        const modalEdit = Modal.getInstance(modal) || new Modal(modal);
        modalEdit.hide();

        // Reset formEdit
        Object.assign(editForm, {
            product_attribute_id: idAttribute,
            name: '',
            price: '',
            image: '',
            product_images: []
        });
        errors.value = {};
        toast.success('Cập nhật thành công!');
        fetchAttributeValue();
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
            <li class="breadcrumb-item">
                <router-link :to="`/admin/product-attribute/${productId}/${productName}`">
                    Danh sách thuộc tính của sản phẩm {{ productName }}</router-link>
            </li>
            <li class="breadcrumb-item">Danh sách {{ nameAttribute }} của sản phẩm {{ productName }}</li>
        </ol>

    </nav>
    <h2 class="text-bold text-body-emphasis mb-5">Danh sách {{ nameAttribute }} của sản phẩm {{ productName }}</h2>
    <div>
        <!-- Search -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <div id="searchModel" class="col-12 col-lg-9">
                <form class="row gy-2 gx-3 align-items-center" id="filter-form" @submit.prevent="fetchAttributeValue">

                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <input name="name" v-model="searchQuery" placeholder="Tên thuộc tính" type="text"
                            class="form-control">
                    </div>

                    <div class="col-12 col-sm-6 col-md-auto d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-phoenix-info btn-filter" title="Lọc">
                            <span class="fas fa-filter text-info fs-9 me-2"></span>Lọc
                        </button>
                        <button class="btn btn-sm btn-phoenix-warning" type="button"
                            @click="searchQuery = ''; fetchAttributeValue();">
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
                            <th class="text-uppercase text-start">ảnh</th>
                            <th class="text-uppercase text-start">ảnh sản phẩm</th>
                            <th class="text-uppercase text-start">Tên</th>
                            <th class="text-uppercase text-end">giá</th>
                            <th class="text-uppercase text-center">Hành động</th>
                        </tr>
                    </thead>

                    <tbody v-if="loading">
                        <tr>
                            <td class=" align-middle text-center" colspan="13">
                                <div class="spinner-border text-info" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>

                    <tbody v-if="products.length > 0">
                        <tr v-for="(product, index) in products" :key="product.id">
                            <td class=" align-middle text-center">{{ index + 1 }}</td>
                            <td class=" align-middle text-start">
                                <div class="d-flex flex-wrap gap-2">
                                    <img :src="product.image ?? ''" alt="Ảnh" class="img-thumbnail" width="70"
                                        height="70">
                                </div>
                            </td>
                            <td class="align-middle text-start">
                                <div class="d-flex flex-wrap gap-2">
                                    <div v-for="(image, index) in product.product_attribute_value_image" :key="image.id"
                                        class="d-flex flex-wrap gap-2">
                                        <img :src="image.image ? '/storage/' + image.image : ''" :alt="image.alt_image"
                                            class="img-thumbnail" width="70" height="70">
                                    </div>
                                </div>
                            </td>


                            <td class=" align-middle text-start">{{ product.name }}</td>
                            <td class=" align-middle text-end">{{ product.price }} {{ product.country_current }}</td>
                            <td class=" align-middle text-center">
                                <button @click="openModalUpdate(product)"
                                    class='btn btn-edit-show btn-sm btn-phoenix-secondary text-info me-1 fs-9'
                                    title='Cập nhật' type='button'>
                                    <span class='fas fa-edit'></span>
                                </button>
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
            <Pagination v-if="dataPanigate" :response="dataPanigate" @getData="fetchAttributeValue" />
        </div>

        <!-- thêm mới giá trị-->
        <div class="modal fade" id="addModel" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class=" modal-content form-open">
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
                                    <input v-model="form.name" type="text" name="name"
                                        class="form-control validate set-0 data-value" placeholder="Tên">
                                    <label>Tên</label>
                                    <p v-if="errors.name" class="text-danger">{{ errors.name[0] }}</p>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-12">
                                <div class="form-floating">
                                    <input v-model="form.price" type="text" name="price" value="0"
                                        @input="form.price = formatBalance($event.target.value)"
                                        class="form-control set-0 validate data-value" placeholder="Giá">
                                    <label>Giá</label>
                                    <p v-if="errors.price" class="text-danger">{{ errors.price[0] }}</p>
                                </div>
                            </div>

                            <div class="col-sm-12 col-md-12">
                                <div class="form-floating">
                                    <div class="custom-file-input">
                                        <input type="file" name="image" class="form-control" id="imageUpload"
                                            @change="previewImage($event, 'image')">
                                        <label for="imageUpload" class="custom-file-label">
                                            <span class="upload-icon"><i class="fas fa-upload"></i></span>
                                            <span class="upload-text">Chọn ảnh nền</span>
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

                            <!-- Chọn ảnh sản phẩm -->
                            <div class="col-sm-12 col-md-12">
                                <div class="form-floating">
                                    <div class="custom-file-input">
                                        <input type="file" name="product_images[]" multiple class="form-control"
                                            id="productImagesUpload" @change="previewProductImages($event)">
                                        <label for="productImagesUpload" class="custom-file-label">
                                            <span class="upload-icon"><i class="fas fa-upload"></i></span>
                                            <span class="upload-text">Chọn ảnh sản phẩm</span>
                                        </label>
                                    </div>
                                    <!-- Hiển thị nhiều ảnh sản phẩm -->
                                    <div v-if="previewProductImagesList.length" class="mt-3 text-center">
                                        <div v-for="(image, index) in previewProductImagesList" :key="index"
                                            class="position-relative d-inline-block me-2">
                                            <img :src="image" alt="Product Image" class="img-fluid rounded shadow-sm"
                                                width="100">
                                            <button class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1"
                                                @click="removeProductImage(index)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <p v-if="errors.product_images" class="text-danger">{{ errors.product_images[0] }}
                                    </p>
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


        <!-- Modal sửa -->
        <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content form-open">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Cập nhật thuộc tính</h5>
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
                                    <input v-model="editForm.name" type="text" name="name"
                                        class="form-control validate set-0 data-value" placeholder="Tên">
                                    <label>Tên</label>
                                    <p v-if="errors.name" class="text-danger">{{ errors.name[0] }}</p>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-12">
                                <div class="form-floating">
                                    <input v-model="editForm.price" type="text" name="price" value="0"
                                        @input="editForm.price = formatBalance($event.target.value)"
                                        class="form-control set-0 validate data-value" placeholder="Giá">
                                    <label>Giá</label>
                                    <p v-if="errors.price" class="text-danger">{{ errors.price[0] }}</p>
                                </div>
                            </div>
                            <!-- Ảnh  -->
                            <div class="col-sm-12 col-md-12">
                                <div class="form-floating">
                                    <div class="custom-file-input">
                                        <input type="file" name="image" class="form-control" id="imageUploadEdit"
                                            @change="previewImage($event, 'image')">
                                        <label for="imageUploadEdit" class="custom-file-label">
                                            <span class="upload-icon"><i class="fas fa-upload"></i></span>
                                            <span class="upload-text">Chọn ảnh đại diện</span>
                                        </label>
                                    </div>

                                    <div v-if="previewImages.image"
                                        class="image-preview mt-3 text-center position-relative">
                                        <img :src="previewImages.image" alt="Preview Image"
                                            class="img-fluid rounded shadow-sm preview-main">
                                        <button class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1"
                                            @click="removeMainImageEdit(previewImages.id, $event)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <p v-if="errors.image" class="text-danger">{{ errors.image[0] }}</p>
                                </div>
                            </div>

                            <!-- Phần chọn nhiều ảnh sản phẩm (mới bổ sung cho edit) -->
                            <div class="col-sm-12 col-md-12">
                                <div class="form-floating">
                                    <div class="custom-file-input">
                                        <input type="file" name="product_images" class="form-control"
                                            id="productImagesUploadEdit" multiple
                                            @change="previewProductImagesEdit($event)">
                                        <label for="productImagesUploadEdit" class="custom-file-label">
                                            <span class="upload-icon"><i class="fas fa-upload"></i></span>
                                            <span class="upload-text">Chọn ảnh sản phẩm</span>
                                        </label>
                                    </div>
                                    <div v-if="previewProductImagesListEdit.length"
                                        class="image-preview mt-3 text-center">
                                        <div v-for="(imgeItem, idx) in previewProductImagesListEdit" :key="imgeItem.id"
                                            class="position-relative d-inline-block me-2">
                                            <img :src="imgeItem.image" alt="Preview Product Image"
                                                class="img-fluid rounded shadow-sm" width="70" height="70">

                                            <button class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1"
                                                @click="removeProductImageEdit(imgeItem.id, $event, idx)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>

                                    </div>

                                    <p v-if="errors.product_images" class="text-danger">{{ errors.product_images[0] }}
                                    </p>
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
