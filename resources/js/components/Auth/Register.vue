<template>
    <main class="min-vh-100 d-flex align-items-center bg-body-tertiary">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4 p-md-5">
                            <div class="text-center mb-4">
                                <img src="../../../assets/img/icons/logo.png" alt="Glamgo" width="44" class="mb-3" />
                                <h4 class="mb-1">Dang ky Admin</h4>
                                <p class="text-body-secondary mb-0">Tai khoan nay chi dung cho trang quan tri</p>
                            </div>

                            <div v-if="errorMessage" class="alert alert-danger py-2">
                                {{ errorMessage }}
                            </div>

                            <form @submit.prevent="register">
                                <div class="mb-3">
                                    <label class="form-label">Ho ten</label>
                                    <input v-model.trim="form.name" type="text" class="form-control" required
                                        autocomplete="name" />
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input v-model.trim="form.email" type="email" class="form-control" required
                                        autocomplete="email" />
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Mat khau</label>
                                    <input v-model="form.password" type="password" class="form-control" required
                                        autocomplete="new-password" />
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">Nhap lai mat khau</label>
                                    <input v-model="form.password_confirmation" type="password" class="form-control"
                                        required autocomplete="new-password" />
                                </div>

                                <button type="submit" class="btn btn-primary w-100" :disabled="loading">
                                    {{ loading ? "Dang xu ly..." : "Dang ky" }}
                                </button>
                            </form>

                            <div class="text-center mt-3">
                                <router-link :to="{ name: 'adminLogin' }">Da co tai khoan? Dang nhap</router-link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</template>

<script>
export default {
    name: "AdminRegister",
    data() {
        return {
            loading: false,
            errorMessage: "",
            form: {
                name: "",
                email: "",
                password: "",
                password_confirmation: "",
            },
        };
    },
    methods: {
        async register() {
            this.loading = true;
            this.errorMessage = "";

            try {
                const response = await axios.post("/api/admin/auth/register", this.form);
                const auth = response.data.data;

                localStorage.setItem("admin_token", auth.token);
                localStorage.setItem("admin_user", JSON.stringify(auth.user));
                this.$router.push({ name: "dashboard" });
            } catch (error) {
                const errors = error.response?.data?.errors;
                this.errorMessage = errors ? Object.values(errors).flat().join(" ") : error.response?.data?.message || "Dang ky that bai.";
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>
