import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const isLocal = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
const configuredHost = import.meta.env.VITE_REVERB_HOST;
const host = isLocal && (!configuredHost || configuredHost.includes('trycloudflare'))
    ? window.location.hostname
    : (configuredHost || window.location.hostname);

const isHttps = !isLocal && (import.meta.env.VITE_REVERB_SCHEME === 'https' || window.location.protocol === 'https:');
const defaultPort = isLocal ? 8080 : (isHttps ? 443 : 8080);
const port = import.meta.env.VITE_REVERB_PORT ? Number(import.meta.env.VITE_REVERB_PORT) : defaultPort;

const csrfMeta = document.querySelector('meta[name="csrf-token"]');
const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY || 'kdypwgtv37zf3fcfgscn',
    wsHost: host,
    wsPort: port,
    wssPort: port,
    forceTLS: isHttps,
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/broadcasting/auth',
    auth: {
        headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {},
    },
});

