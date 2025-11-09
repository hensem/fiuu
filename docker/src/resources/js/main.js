import { createApp } from 'vue';
import App from './App.vue';
import router from './router'; // ✅ Import router
import './assets/style.css'; // optional: your global styles

// Create Vue app instance
const app = createApp(App);

// Inject the router
app.use(router);

// Mount the app
app.mount('#app');
