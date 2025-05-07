import './bootstrap';
import App from './components/App.vue';
import Toast, { POSITION } from "vue-toastification";
import { createApp } from 'vue';
import router from "./router/router"
import "vue-toastification/dist/index.css";

import VueDatePicker from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";

const app = createApp(App);
app.use(router);
app.component("VueDatePicker", VueDatePicker);
app.use(Toast, {
    position: POSITION.TOP_RIGHT,
    timeout: 3000,
    closeOnClick: true,
    pauseOnHover: true,
});
app.mount("#app");