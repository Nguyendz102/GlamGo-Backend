/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.axios.interceptors.request.use((config) => {
    const token = localStorage.getItem('admin_token');

    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    return config;
});

window.axios.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error.response?.status;
        const currentPath = window.location.pathname;

        if ((status === 401 || status === 403) && currentPath.startsWith('/admin') && !currentPath.includes('/admin/login')) {
            localStorage.removeItem('admin_token');
            localStorage.removeItem('admin_user');
            window.location.href = '/admin/login';
        }

        return Promise.reject(error);
    }
);

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const reverbScheme = import.meta.env.VITE_REVERB_SCHEME ?? 'http';

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_REVERB_APP_KEY ?? 'glamgo-local-key',
    cluster: import.meta.env.VITE_REVERB_APP_CLUSTER ?? 'mt1',
    wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
    wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
    wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
    forceTLS: reverbScheme === 'https',
    disableStats: true,
    enabledTransports: ['ws', 'wss'],
    authorizer: (channel) => ({
        authorize: (socketId, callback) => {
            window.axios.post('/api/v1/broadcasting/auth', {
                socket_id: socketId,
                channel_name: channel.name,
            })
                .then((response) => callback(false, response.data))
                .catch((error) => callback(true, error));
        },
    }),
});
