<script setup>
import { computed } from "vue";

const props = defineProps({
    response: Object,
});

const emit = defineEmits(["getData"]);

// Sử dụng computed để truy cập dữ liệu pagination
const paginationData = computed(() => {
    return props.response?.meta || props.response;
});

const from = computed(() => paginationData.value?.from);
const to = computed(() => paginationData.value?.to);
const total = computed(() => paginationData.value?.total);
const current_page = computed(() => paginationData.value?.current_page);
const last_page = computed(() => paginationData.value?.last_page);

// Tạo danh sách số trang
const paginationItems = computed(() => {
    if (!current_page.value) return [];

    let start = current_page.value - 3;
    let max = current_page.value + 3;
    let pages = [];

    for (let index = start; index <= max; index++) {
        if (index > 0 && index <= last_page.value) {
            pages.push(index);
        }
    }

    return pages;
});

const changePage = (page) => {
    emit("getData", page);
};
</script>

<template>
    <div class="row align-items-center justify-content-between py-2 pe-0 fs-9">
        <div class="col-auto d-flex">
            <p class="mb-0 d-none d-sm-block me-3 fw-semibold text-body">
                {{ from }} đến {{ to }}
                <span class="text-body-tertiary"> Trong </span> {{ total }}
            </p>
        </div>
        <nav class="col-auto">
            <ul class="mb-0 pagination justify-content-end">
                <!-- Nút "Trang trước" -->
                <li class="page-item" v-if="current_page > 1">
                    <a class="page-link" href="javascript:" @click="changePage(current_page - 1)" title="Trang trước">
                        <span class="fas fa-chevron-left"></span>
                    </a>
                </li>

                <!-- Danh sách số trang -->
                <li v-for="page in paginationItems" :key="page" class="page-item"
                    :class="{ active: page === current_page }">
                    <a class="page-link btn-paginations" href="javascript:" @click="changePage(page)"
                        :title="'Trang ' + page">
                        {{ page }}
                    </a>
                </li>

                <!-- Nút "Trang sau" -->
                <li class="page-item" v-if="current_page < last_page">
                    <a class="page-link" href="javascript:" @click="changePage(current_page + 1)" title="Trang sau">
                        <span class="fas fa-chevron-right"></span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</template>

<style scoped>
.pagination button {
    margin: 5px;
    padding: 5px 10px;
    border: 1px solid #ddd;
    background: #fff;
    cursor: pointer;
}

.pagination button.active {
    background: blue;
    color: white;
}

.pagination button:disabled {
    background: #ddd;
    cursor: not-allowed;
}
</style>
