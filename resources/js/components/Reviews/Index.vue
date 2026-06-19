<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import { Modal } from 'bootstrap';
import { useToast } from 'vue-toastification';
import Pagination from '../Pagination.vue';
import { dateTimeFormat } from '../../utils';

const toast = useToast();
const ratings = ref([]);
const dataPanigate = ref({});
const loading = ref(true);
const errors = ref({});
const selectedRating = ref(null);
const filters = reactive({
    search: '',
    rating_value: '',
    status_id: '',
    reply_status: '',
});
const replyForm = reactive({
    admin_reply: '',
    status_id: '',
});

const fetchRatings = async (page = 1) => {
    loading.value = true;
    try {
        const params = Object.fromEntries(
            Object.entries(filters).filter(([_, value]) => value !== '')
        );
        params.page = page;
        const response = await axios.get('/api/ratings', { params });
        ratings.value = response.data.data;
        dataPanigate.value = response.data;
    } catch (error) {
        console.error('Error fetching ratings:', error);
        toast.error('Khong lay duoc danh sach danh gia');
    } finally {
        loading.value = false;
    }
};

const resetFilters = () => {
    filters.search = '';
    filters.rating_value = '';
    filters.status_id = '';
    filters.reply_status = '';
    fetchRatings();
};

const openReplyModal = (rating) => {
    selectedRating.value = rating;
    replyForm.admin_reply = rating.admin_reply || '';
    replyForm.status_id = rating.status_id || '';
    errors.value = {};
    const modal = new Modal(document.getElementById('replyRatingModal'));
    modal.show();
};

const submitReply = async () => {
    if (!selectedRating.value) {
        return;
    }

    try {
        await axios.post(`/api/ratings/${selectedRating.value.id}/reply`, replyForm);
        toast.success('Da phan hoi danh gia');
        const modal = Modal.getInstance(document.getElementById('replyRatingModal'));
        modal?.hide();
        fetchRatings(dataPanigate.value.current_page || 1);
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors;
            return;
        }

        console.error('Error replying rating:', error);
        toast.error('Khong the phan hoi danh gia');
    }
};

const statusText = (statusId) => {
    if (Number(statusId) === 17) {
        return 'Hien thi';
    }
    if (Number(statusId) === 2) {
        return 'An';
    }
    return 'Cho duyet';
};

onMounted(() => {
    fetchRatings();
});
</script>

<template>
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="/admin">Trang chu</a></li>
            <li class="breadcrumb-item">Danh gia san pham</li>
        </ol>
    </nav>

    <h2 class="text-bold text-body-emphasis mb-5">Danh gia san pham</h2>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
        <form class="row gy-2 gx-3 align-items-center col-12" @submit.prevent="fetchRatings">
            <div class="col-12 col-md-3">
                <input v-model="filters.search" class="form-control" placeholder="Ten KH, SDT, san pham, noi dung">
            </div>
            <div class="col-12 col-md-2">
                <select v-model="filters.rating_value" class="form-select">
                    <option value="">Tat ca so sao</option>
                    <option v-for="star in [5, 4, 3, 2, 1]" :key="star" :value="star">{{ star }} sao</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <select v-model="filters.reply_status" class="form-select">
                    <option value="">Tat ca phan hoi</option>
                    <option value="unreplied">Chua phan hoi</option>
                    <option value="replied">Da phan hoi</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <select v-model="filters.status_id" class="form-select">
                    <option value="">Tat ca trang thai</option>
                    <option value="1">Cho duyet</option>
                    <option value="17">Hien thi</option>
                    <option value="2">An</option>
                </select>
            </div>
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-phoenix-info">
                    <span class="fas fa-filter text-info fs-9 me-2"></span>Loc
                </button>
                <button type="button" class="btn btn-sm btn-phoenix-warning" @click="resetFilters">Xoa loc</button>
            </div>
        </form>
    </div>

    <div class="mx-n4 mx-lg-n6 px-4 px-lg-6 mb-9 bg-body-emphasis mt-2 position-relative top-1">
        <div class="table-responsive scrollbar">
            <table class="table table-hover table-sm fs-9 mb-0">
                <thead>
                    <tr>
                        <th class="text-uppercase text-center">Stt</th>
                        <th class="text-uppercase text-start">San pham</th>
                        <th class="text-uppercase text-start">Khach hang</th>
                        <th class="text-uppercase text-center">Sao</th>
                        <th class="text-uppercase text-start">Noi dung</th>
                        <th class="text-uppercase text-start">Phan hoi admin</th>
                        <th class="text-uppercase text-center">Trang thai</th>
                        <th class="text-uppercase text-start">Ngay tao</th>
                        <th class="text-uppercase text-center">Hanh dong</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loading">
                        <td class="text-center" colspan="9">
                            <div class="spinner-border text-info spinner-border-sm" role="status"></div>
                        </td>
                    </tr>
                    <tr v-else-if="ratings.length === 0">
                        <td colspan="9" class="text-center fw-bold fs-7 text-danger">Chua co du lieu</td>
                    </tr>
                    <tr v-for="(rating, index) in ratings" :key="rating.id">
                        <td class="align-middle text-center">{{ index + 1 }}</td>
                        <td class="align-middle text-start">
                            <div class="fw-semibold">{{ rating.product?.name }}</div>
                            <div class="text-body-tertiary">{{ rating.product?.code }}</div>
                        </td>
                        <td class="align-middle text-start">
                            <div class="fw-semibold">{{ rating.fullname || rating.user?.name }}</div>
                            <div class="text-body-tertiary">{{ rating.phone || rating.user?.phone }}</div>
                        </td>
                        <td class="align-middle text-center fw-bold text-warning">{{ rating.rating_value }}</td>
                        <td class="align-middle text-start text-wrap" style="min-width: 220px;">{{ rating.comment }}</td>
                        <td class="align-middle text-start text-wrap" style="min-width: 220px;">
                            <div v-if="rating.admin_reply">{{ rating.admin_reply }}</div>
                            <span v-else class="badge bg-warning-subtle text-warning-emphasis">Chua phan hoi</span>
                        </td>
                        <td class="align-middle text-center">
                            <span class="badge bg-info-subtle text-info-emphasis">{{ statusText(rating.status_id) }}</span>
                        </td>
                        <td class="align-middle text-start">{{ dateTimeFormat(rating.created_at) }}</td>
                        <td class="align-middle text-center">
                            <button class="btn btn-sm btn-phoenix-secondary text-info" @click="openReplyModal(rating)">
                                <span class="fas fa-reply me-1"></span>Phan hoi
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <Pagination v-if="dataPanigate" :response="dataPanigate" @getData="fetchRatings" />
    </div>

    <div class="modal fade" id="replyRatingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content form-open">
                <div class="modal-header">
                    <h5 class="modal-title">Phan hoi danh gia</h5>
                    <button class="btn p-1 closeButton" type="button" data-bs-dismiss="modal" aria-label="Close">
                        <span class="fas fa-times"></span>
                    </button>
                </div>
                <div class="modal-body">
                    <div v-if="selectedRating" class="mb-3">
                        <div class="fw-semibold">{{ selectedRating.product?.name }}</div>
                        <div class="text-body-tertiary">{{ selectedRating.comment }}</div>
                    </div>
                    <form class="row g-3" @submit.prevent="submitReply">
                        <div class="col-12">
                            <div class="form-floating">
                                <textarea v-model.trim="replyForm.admin_reply" class="form-control" style="height: 140px"
                                    placeholder="Noi dung phan hoi"></textarea>
                                <label>Noi dung phan hoi</label>
                                <p v-if="errors.admin_reply" class="text-danger mt-2 fs-9">{{ errors.admin_reply[0] }}</p>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-floating">
                                <select v-model="replyForm.status_id" class="form-select">
                                    <option value="">Giu nguyen trang thai</option>
                                    <option value="1">Cho duyet</option>
                                    <option value="17">Hien thi</option>
                                    <option value="2">An</option>
                                </select>
                                <label>Trang thai</label>
                            </div>
                        </div>
                        <div class="col-12 text-center">
                            <button type="button" class="btn btn-secondary mx-1" data-bs-dismiss="modal">Huy</button>
                            <button type="submit" class="btn btn-primary mx-1">Luu phan hoi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
