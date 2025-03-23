import './bootstrap';
import App from './components/App.vue';
import { createApp } from 'vue';
import router from "./router/router"

const app = createApp(App);
app.use(router);
app.mount("#app");