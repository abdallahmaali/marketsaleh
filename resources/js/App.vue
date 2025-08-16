<template>
    <component :is="$route.meta.layout">
        <RouterView />
    </component>
</template>


<script setup>
import axios from 'axios';
import { onMounted, onBeforeUnmount, watch } from 'vue';
import { useAuth } from './stores/AuthStore';
import { useMaster } from './stores/MasterStore';
import { useChat } from './stores/ChatStore';
import Pusher from 'pusher-js';
import { useRoute } from 'vue-router';


const authStore = useAuth();
const masterStore = useMaster();
const chatStore = useChat();
const route = useRoute();


onMounted(() => {
    startLastSeenUpdater(); // Start interval
    callLastSeenUpdater();  // Immediate call on mount
    handlePusherChanel();
    initializeDirection(); // Set initial direction based on stored locale
});

// Initialize direction based on stored locale
const initializeDirection = () => {
    const currentLocale = localStorage.getItem('locale') || 'en';
    const direction = currentLocale === 'ar' ? 'rtl' : 'ltr';
    
    // Force direction change on HTML element
    const htmlElement = document.documentElement;
    htmlElement.setAttribute('dir', direction);
    htmlElement.setAttribute('lang', currentLocale);
    htmlElement.style.direction = direction;
    
    // Update body classes
    document.body.classList.remove('lang-ar', 'lang-en');
    document.body.classList.add('lang-' + currentLocale);
    
    // Force update masterStore direction (override persisted incorrect value)
    masterStore.langDirection = direction;
    
    // Also update localStorage to ensure consistency
    localStorage.setItem('langDirection', direction);
    
    // Debug log
    console.log(`App initialized with locale: ${currentLocale}, direction: ${direction}`, 'HTML dir attr:', htmlElement.getAttribute('dir'));
};

onBeforeUnmount(() => clearInterval(startLastSeenUpdater.intervalId));

const callLastSeenUpdater = async () => {
    await axios.post('/update-last-seen', {}, {
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
            Authorization: authStore.token,
        },
    });
};

const startLastSeenUpdater = () => {
    startLastSeenUpdater.intervalId = setInterval(callLastSeenUpdater, 10 * 60 * 1000);
};

watch(() => authStore.token, () => {
    if (authStore.token) {
        startLastSeenUpdater();
        callLastSeenUpdater();
    } else {
        clearInterval(startLastSeenUpdater.intervalId);
    }
});


const fetchUnreadMessages = async () => {
    const response = await axios.get('/unread-messages', {
        params: {
            user_id: authStore.user?.id,
        }
    });

    chatStore.unreadMessages = response.data.data?.unread_messages;
};


const handlePusherChanel = () => {

    Pusher.logToConsole = false;

    if (!masterStore.pusher_app_key) {
        return;
    }

    const pusher = new Pusher(masterStore.pusher_app_key, {
        cluster: masterStore.pusher_app_cluster,
        encrypted: true,
    });

    let userId = authStore.user.id;

    const channel = pusher.subscribe('chat_user_' + userId);

    channel.bind('send-message-to-user', function (data) {
        if (route.path != '/massages') {
            chatStore.activeShop = null;
            fetchUnreadMessages();
        }
    });
}
</script>
