<script setup>
import { ref, onMounted, watch, reactive, computed } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import { formatCurrency, dateTimeFormat, formatBalance, removeCommas } from '../../utils';
import { Modal } from 'bootstrap';
import Pagination from '../../components/Pagination.vue';

const coupon = ref([]);
const country = ref([]);
const searchQuery = ref({ code: '', date: '' });
const status_table = ref([]);
const loading = ref(true);
const dataPanigate = ref([])
const errors = ref({});
const currencySign = ref('');
const toast = useToast();

const form = reactive({
    code: '',
    discount: '',
    status: 1,
    description: '',
    min_order_value: '',
    max_value: '',
    start_date: '',
    end_date: '',
    usage_limit: '',
    type_unit: '',
});

const editForm = reactive({
    id: '',
    code: '',
    discount: '',
    status: '',
    description: '',
    min_order_value: '',
    max_value: '',
    start_date: '',
    end_date: '',
    usage_limit: '',
    type_unit: '',
});

const fetchCoupon = async (page = 1) => {
    try {
        const params = Object.fromEntries(
            Object.entries(searchQuery.value).filter(([_, value]) => value !== '')
        );
        params.page = page;
        const response = await axios.get('/api/coupon', {
            params
        });
        coupon.value = response.data.data;
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
    fetchCoupon();
});


const openModalAdd = () => {
    errors.value = {};
    const modal = document.getElementById('addModel');
    const addModel = new Modal(modal);
    addModel.show();
}


const submitForm = async () => {
    try {
        const data = {
            code: form.code,
            discount: removeCommas(form.discount),
            status: form.status,
            description: form.description,
            min_order_value: removeCommas(form.min_order_value),
            max_value: removeCommas(form.max_value),
            start_date: form.start_date,
            end_date: form.end_date,
            usage_limit: form.usage_limit,
            type_unit: form.type_unit,
        };
        const response = await axios.post('/api/coupon/post-coupon', data);


        const modal = document.getElementById('addModel');
        const modalAdd = Modal.getInstance(modal) || new Modal(modal);
        modalAdd.hide();

        // Reset form
        Object.assign(form, {
            code: '',
            discount: '',
            status: '',
            description: '',
            min_order_value: '',
            max_value: '',
            start_date: '',
            end_date: '',
            usage_limit: '',
            type_unit: '',
        });
        errors.value = {};
        toast.success('Thêm mới mã giảm giá thành công!');
        fetchCoupon();
    } catch (error) {
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            console.error('Error creating category:', error);
        }
    }
};

const openModalUpdate = (cp) => {
    errors.value = {};
    populateEditForm(cp);
    const modal = document.getElementById('editModal');
    const editModel = new Modal(modal);
    editModel.show();
}

const populateEditForm = (cp) => {
    Object.assign(editForm, {
        id: cp.id,
        code: cp.code,
        discount: formatCurrency(cp.discount_type),
        status: cp.status,
        description: cp.description,
        min_order_value: formatCurrency(cp.min_order_value),
        max_value: formatCurrency(cp.max_value),
        start_date: cp.start_date,
        end_date: cp.end_date,
        usage_limit: cp.usage_limit,
        type_unit: cp.type_unit,
    });
};

const submitEditForm = async () => {
    try {
        const data = {
            code: editForm.code,
            discount: removeCommas(editForm.discount),
            status: editForm.status,
            description: editForm.description,
            min_order_value: removeCommas(editForm.min_order_value),
            max_value: removeCommas(editForm.max_value),
            start_date: editForm.start_date,
            end_date: editForm.end_date,
            usage_limit: editForm.usage_limit,
            type_unit: editForm.type_unit,
        };
        const response = await axios.put(`/api/coupon/edit/${editForm.id}`, data);

        const modal = document.getElementById('editModal');
        const modalEdit = Modal.getInstance(modal) || new Modal(modal);
        modalEdit.hide();

        // Reset formEdit
        Object.assign(editForm, {
            code: '',
            discount: '',
            status: '',
            description: '',
            min_order_value: '',
            max_value: '',
            start_date: '',
            end_date: '',
            usage_limit: '',
            type_unit: '',
        });
        errors.value = {};
        toast.success('Cập nhật mã giảm giá thành công!');
        fetchCoupon();
    } catch (error) {
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            console.error('Error creating category:', error);
        }
    }
};

const resetFilters = () => {
    searchQuery.value = { code: '', date: '' };
    fetchCoupon();
};

</script>
<template>
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="/admin">Trang chủ</a></li>
            <li class="breadcrumb-item">Danh sách mã giảm giá</li>
        </ol>
    </nav>
    <h2 class="text-bold text-body-emphasis mb-5">Danh sách mã giảm giá</h2>
    <div>
        <!-- Search -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <div id="searchModel" class="col-12 col-lg-9">
                <form class="row gy-2 gx-3 align-items-center" id="filter-form" @submit.prevent="fetchCoupon">
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <input name="code_dh" v-model="searchQuery.code" placeholder="Mã giảm giá" type="text"
                            class="form-control">
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <VueDatePicker v-model="searchQuery.date" format="dd-MM-yyyy" valueFormat="yyyy-mm-dd" range
                            placeholder="Chọn ngày"></VueDatePicker>
                    </div>
                    <div class="col-12 col-sm-6 col-md-auto d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-phoenix-info btn-filter" title="Lọc">
                            <span class="fas fa-filter text-info fs-9 me-2"></span>Lọc
                        </button>
                        <button @click="resetFilters" class="btn btn-sm btn-phoenix-warning" type="button">
                            Xoá lọc
                        </button>
                    </div>
                </form>
            </div>
            <div class="col-12 col-lg-auto text-end mt-3 mt-lg-0">
                <button class="btn btn-primary" type="button" data-bs-toggle="modal" @click="openModalAdd()">
                    <span class="fas fa-plus me-2"></span>Thêm mã giảm giá
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
                            <th class="align-middle text-start text-uppercase">Ngày bắt đầu</th>
                            <th class="align-middle text-start text-uppercase">Ngày kết thúc</th>
                            <th class="align-middle text-start text-uppercase">mã giảm giá</th>
                            <th class="align-middle text-start text-uppercase">loại giảm giá</th>
                            <th class="align-middle text-end text-uppercase">Mức giảm</th>
                            <th class="align-middle text-end text-uppercase">Giảm tối đa</th>
                            <th class="align-middle text-end text-uppercase">Đơn tối thiểu</th>
                            <th class="align-middle text-end text-uppercase">giới hạn</th>
                            <th class="align-middle text-center text-uppercase">trạng thái</th>
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
                        <tr v-if="coupon.length === 0 && !loading">
                            <td colspan="11" class="text-center fw-bold fs-7 text-danger">Chưa có dữ liệu</td>
                        </tr>
                        <tr v-else v-for="(cp, index) in coupon" :key="cp.id">
                            <td class="align-middle text-center">{{ index + 1 }}</td>
                            <td class="align-middle text-start">{{ dateTimeFormat(cp.start_date) }}</td>
                            <td class="align-middle text-start">{{ dateTimeFormat(cp.end_date) }}</td>
                            <td class="align-middle text-start">
                                <router-link :to="`/admin/coupon/${cp.id}`">{{ cp.code }}</router-link>
                            </td>
                            <td class="align-middle text-start">{{ cp.type_unit == 1 ? 'Giảm %' : 'Giảm tiền' }}</td>
                            <td class="align-middle text-end">{{ cp.type_unit == 1 ? formatCurrency(cp.discount_type)
                                + ' %' : formatCurrency(cp.discount_type) + ' đ' }}</td>
                            <td class="align-middle text-end">{{ formatCurrency(cp.max_value) }}</td>
                            <td class="align-middle text-end">{{ formatCurrency(cp.min_order_value) }}</td>
                            <td class="align-middle text-end">{{ cp.usage_limit + ' người' }}</td>
                            <td class="align-middle text-center">
                                <span class="fs-10 badge"
                                    :class="cp.status == 1 ? 'bg-success-subtle text-success-emphasis' : 'bg-danger-subtle text-danger-emphasis'">
                                    {{ cp.status == 1 ? 'Hoạt động' : 'Không hoạt động' }}
                                </span>
                            </td>

                            <td class="align-middle text-center">
                                <div class="position-relative">
                                    <button class="btn btn-edit-show btn-sm btn-phoenix-secondary text-info me-1 fs-10"
                                        @click="openModalUpdate(cp)" type="button" data-bs-toggle="modal">
                                        <span class="fas far fa-edit"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>
            <Pagination v-if="dataPanigate" :response="dataPanigate" @getData="fetchCoupon" />

        </div>

        <!-- thêm mới mã giảm giá-->
        <div class="modal fade" id="addModel" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class=" modal-content form-open">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Thêm mới mã giảm giá</h5>
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
                                    <select class="form-select" v-model="form.type_unit">
                                        <option value="" hidden>Chọn loại giảm giá</option>
                                        <option value="1">Giảm giá %</option>
                                        <option value="2">Giảm giá tiền</option>
                                    </select>
                                    <label>loại giảm giá</label>
                                    <div v-if="errors.type_unit" class="text-danger mt-2 fs-9 ms-2">{{
                                        errors.type_unit[0] }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6">
                                <div class="form-floating">
                                    <input type="text" v-model="form.code" name="code" class="form-control"
                                        placeholder="">
                                    <label>mã giảm giá</label>
                                    <div v-if="errors.code" class="text-danger mt-2 fs-9 ms-2">{{ errors.code[0] }}
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-12 col-md-6">
                                <div class="form-floating">
                                    <input type="text" v-model="form.discount"
                                        @input="form.discount = formatBalance($event.target.value)" name="discount"
                                        class="form-control" placeholder="giá trị mã">
                                    <label>giá trị mã</label>
                                    <span class="floating-unit">{{ form.type_unit == 1 ? '%' : form.type_unit == 2 ?
                                        currencySign
                                        : '' }}</span>
                                    <div v-if="errors.discount" class="text-danger mt-2 fs-9 ms-2">{{ errors.discount[0]
                                    }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6">
                                <div class="form-floating">
                                    <VueDatePicker format="dd-MM-yyyy" model-value-format="dd-MM-yyyy"
                                        v-model="form.start_date" placeholder="Ngày bắt đầu"></VueDatePicker>
                                    <div v-if="errors.start_date" class="text-danger mt-2 fs-9 ms-2">{{
                                        errors.start_date[0] }}</div>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6">
                                <div class="form-floating">
                                    <VueDatePicker format="dd-MM-yyyy" model-value-format="dd-MM-yyyy"
                                        v-model="form.end_date" placeholder="Ngày kết thúc"></VueDatePicker>
                                    <div v-if="errors.end_date" class="text-danger mt-2 fs-9 ms-2">{{ errors.end_date[0]
                                    }}</div>
                                </div>
                            </div>

                            <div class="col-sm-12 col-md-6">
                                <div class="form-floating">
                                    <input type="text" v-model="form.min_order_value"
                                        @input="form.min_order_value = formatBalance($event.target.value)"
                                        name="min_order_value" class="form-control"
                                        placeholder="Giá trị đơn hàng tối thiểu">
                                    <label>Giá trị đơn hàng tối thiểu</label>
                                    <span class="floating-unit">{{ currencySign }}</span>
                                    <div v-if="errors.min_order_value" class="text-danger mt-2 fs-9 ms-2">{{
                                        errors.min_order_value[0] }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6">
                                <div class="form-floating">
                                    <input type="text" v-model="form.max_value"
                                        @input="form.max_value = formatBalance($event.target.value)" name="max_value"
                                        class="form-control" placeholder="Giá trị giảm tối đa">
                                    <label>Giá trị giảm tối đa</label>
                                    <span class="floating-unit">{{ currencySign }}</span>

                                    <div v-if="errors.max_value" class="text-danger mt-2 fs-9 ms-2">{{
                                        errors.max_value[0] }}</div>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6">
                                <div class="form-floating">
                                    <input type="text" v-model="form.usage_limit"
                                        @input="form.usage_limit = formatBalance($event.target.value)"
                                        name="usage_limit" class="form-control" placeholder="Số lần sử dụng tối đa">
                                    <label>Số lần sử dụng tối đa</label>
                                    <div v-if="errors.usage_limit" class="text-danger mt-2 fs-9 ms-2">{{
                                        errors.usage_limit[0] }}</div>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6">
                                <div class="form-floating">
                                    <select class="form-select" v-model="form.status" name="status">
                                        <option value="">Chọn trạng thái</option>
                                        <option value="1">Hoạt động</option>
                                        <option value="0">Ngưng hoạt động</option>
                                    </select>
                                    <label>Trạng thái</label>
                                    <div v-if="errors.status" class="text-danger mt-2 fs-9 ms-2">{{ errors.status[0] }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-12">
                                <div class="form-floating">
                                    <textarea style="height: 100px;" v-model="form.description" name="description"
                                        class="form-control" placeholder="Mô tả"></textarea>
                                    <label>Mô tả</label>
                                    <div v-if="errors.description" class="text-danger mt-2 fs-9 ms-2">{{
                                        errors.description[0] }}</div>
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


        <!-- Modal sửa mã giảm giá -->
        <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
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
                            <div class="col-sm-12 col-md-6">
                                <div class="form-floating">
                                    <select class="form-select" v-model="editForm.type_unit">
                                        <option value="" hidden>Chọn loại giảm giá</option>
                                        <option value="1">Giảm giá %</option>
                                        <option value="2">Giảm giá tiền</option>
                                    </select>
                                    <label>loại giảm giá</label>
                                    <div v-if="errors.type_unit" class="text-danger mt-2 fs-9 ms-2">{{
                                        errors.type_unit[0] }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6">
                                <div class="form-floating">
                                    <select class="form-select" v-model="editForm.status" name="status">
                                        <option value="">Chọn trạng thái</option>
                                        <option value="1">Hoạt động</option>
                                        <option value="0">Ngưng hoạt động</option>
                                    </select>
                                    <label>Trạng thái</label>
                                    <div v-if="errors.status" class="text-danger mt-2 fs-9 ms-2">{{
                                        errors.status[0] }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-12">
                                <div class="form-floating">
                                    <input type="text" v-model="editForm.code" name="code" class="form-control"
                                        placeholder="">
                                    <label>mã giảm giá</label>
                                    <div v-if="errors.code" class="text-danger mt-2 fs-9 ms-2">{{ errors.code[0]
                                    }}
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-12 col-md-6">
                                <div class="form-floating">
                                    <input type="text" v-model="editForm.discount"
                                        @input="editForm.discount = formatBalance($event.target.value)" name="discount"
                                        class="form-control" placeholder="giá trị mã">
                                    <label>giá trị mã</label>
                                    <span class="floating-unit">{{ editForm.type_unit == 1 ? '%' : editForm.type_unit ==
                                        2 ? currencySign
                                        : '' }}</span>
                                    <div v-if="errors.discount" class="text-danger mt-2 fs-9 ms-2">{{
                                        errors.discount[0]
                                    }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6">
                                <div class="form-floating">
                                    <VueDatePicker format="dd-MM-yyyy" model-value-format="dd-MM-yyyy"
                                        v-model="editForm.start_date" placeholder="Ngày bắt đầu">
                                    </VueDatePicker>
                                    <div v-if="errors.start_date" class="text-danger mt-2 fs-9 ms-2">{{
                                        errors.start_date[0] }}</div>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6">
                                <div class="form-floating">
                                    <VueDatePicker format="dd-MM-yyyy" model-value-format="dd-MM-yyyy"
                                        v-model="editForm.end_date" placeholder="Ngày kết thúc">
                                    </VueDatePicker>
                                    <div v-if="errors.end_date" class="text-danger mt-2 fs-9 ms-2">{{
                                        errors.end_date[0]
                                    }}</div>
                                </div>
                            </div>

                            <div class="col-sm-12 col-md-6">
                                <div class="form-floating">
                                    <input type="text" v-model="editForm.min_order_value"
                                        @input="editForm.min_order_value = formatBalance($event.target.value)"
                                        name="min_order_value" class="form-control"
                                        placeholder="Giá trị đơn hàng tối thiểu">
                                    <label>Giá trị đơn hàng tối thiểu</label>
                                    <span class="floating-unit">{{ currencySign }}</span>
                                    <div v-if="errors.min_order_value" class="text-danger mt-2 fs-9 ms-2">{{
                                        errors.min_order_value[0] }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6">
                                <div class="form-floating">
                                    <input type="text" v-model="editForm.max_value"
                                        @input="editForm.max_value = formatBalance($event.target.value)"
                                        name="max_value" class="form-control" placeholder="Giá trị giảm tối đa">
                                    <label>Giá trị giảm tối đa</label>
                                    <span class="floating-unit">{{ currencySign }}</span>

                                    <div v-if="errors.max_value" class="text-danger mt-2 fs-9 ms-2">{{
                                        errors.max_value[0] }}</div>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6">
                                <div class="form-floating">
                                    <input type="text" v-model="editForm.usage_limit"
                                        @input="editForm.usage_limit = formatBalance($event.target.value)"
                                        name="usage_limit" class="form-control" placeholder="Số lần sử dụng tối đa">
                                    <label>Số lần sử dụng tối đa</label>
                                    <div v-if="errors.usage_limit" class="text-danger mt-2 fs-9 ms-2">{{
                                        errors.usage_limit[0] }}</div>
                                </div>
                            </div>

                            <div class="col-sm-12 col-md-12">
                                <div class="form-floating">
                                    <textarea style="height: 100px;" v-model="editForm.description" name="description"
                                        class="form-control" placeholder="Mô tả"></textarea>
                                    <label>Mô tả</label>
                                    <div v-if="errors.description" class="text-danger mt-2 fs-9 ms-2">{{
                                        errors.description[0] }}</div>
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
