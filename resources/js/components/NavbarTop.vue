<template>
    <div class="bg-primary-700">
        <div class="main-container flex justify-between items-center py-2 text-white">

            <div class="flex sm:items-center flex-col sm:flex-row gap-1 sm:gap-4">
                <a v-if="master.getMultiVendor" href="/shop/register"
                    class="text-white text-sm font-normal font-['Roboto'] leading-tight">
                    {{ $t('Become a Seller') }}
                </a>
                <div v-if="master.getMultiVendor" class="w-[0] h-3 border border-primary-500 hidden sm:block"></div>
                <div class="text-white text-sm font-normal font-['Roboto'] leading-tight">
                    {{ $t('Hotline') }}: {{ master.mobile }}
                </div>
            </div>

            <div class="flex items-center gap-3">
                <Menu as="div" class="relative inline-block text-left">
                    <div>
                        <MenuButton
                            class="inline-flex items-center text-white font-['Roboto'] gap-1 text-sm font-normal leading-tight justify-between">
                            {{ (master.selectedCurrency?.name || 'USD')+', ' + (master.selectedCurrency?.symbol || '$') }}
                            <ChevronDownIcon class="w-4 h-4" aria-hidden="true" />
                        </MenuButton>
                    </div>

                    <transition enter-active-class="transition ease-out duration-100"
                        enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-75"
                        leave-from-class="transform opacity-100 scale-100"
                        leave-to-class="transform opacity-0 scale-95">
                        <MenuItems
                            class="absolute z-20 w-24 mt-1 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none" :class="master.langDirection == 'rtl' ? 'left-0' : 'right-0'">
                            <div class="py-1">
                                <MenuItem v-for="currency in master.currencies" v-slot="{ active }" :key="currency.id">
                                <button type="button" @click="setCurrentCurrency(currency)"
                                    class="w-full text-left"
                                    :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'block px-4 py-2 text-sm']">
                                    {{currency.name + ', ' + currency.symbol}}
                                </button>
                                </MenuItem>
                            </div>
                        </MenuItems>
                    </transition>
                </Menu>

                <div class="w-[0] h-3 border border-primary-500 hidden sm:block"></div>

                <Menu as="div" class="relative inline-block text-left">
                    <div>
                        <MenuButton
                            class="inline-flex items-center text-white font-['Roboto'] gap-1 text-sm font-normal leading-tight">
                            {{ currentLanguage }}
                            <ChevronDownIcon class="w-4 h-4" aria-hidden="true" />
                        </MenuButton>
                    </div>

                    <transition enter-active-class="transition ease-out duration-100"
                        enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-75"
                        leave-from-class="transform opacity-100 scale-100"
                        leave-to-class="transform opacity-0 scale-95">
                        <MenuItems
                            class="absolute z-20 w-24 mt-1 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none" :class="master.langDirection == 'rtl' ? 'left-0' : 'right-0'">
                            <div class="py-1">
                                <MenuItem v-for="language in master.languages" v-slot="{ active }" :key="language.id">
                                <button type="button" @click="setCurrentLanguage(language.name); reloadPage()"
                                    class="w-full text-left" :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'block px-4 py-2 text-sm']">{{
                                        language.title }}</button>
                                </MenuItem>
                            </div>
                        </MenuItems>
                    </transition>
                </Menu>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import { ChevronDownIcon } from '@heroicons/vue/20/solid'
import localization from '../localization';

import { useMaster } from "../stores/MasterStore";
import { onMounted, ref, watch } from 'vue';
const master = useMaster();

const currentLanguage = ref('English');

const setCurrentLanguage = (lang) => {
    master.locale = lang;
    localStorage.setItem('locale', lang);

    // Try to find language by name or title
    const language = master.languages.find(item => 
        item.name === master.locale || 
        item.title === master.locale ||
        (master.locale === 'ar' && (item.name === 'Arabic' || item.title === 'Arabic' || item.title === 'العربية'))
    );
    
    // Determine direction - prioritize Arabic override since server data might be wrong
    let direction = 'ltr';
    if (lang === 'ar') {
        direction = 'rtl'; // Force RTL for Arabic regardless of server data
    } else if (language && language.direction) {
        direction = language.direction;
    }
    
    // Debug: Check what language was found and direction logic
    console.log('setCurrentLanguage called with:', lang);
    console.log('master.locale set to:', master.locale);
    console.log('localStorage locale:', localStorage.getItem('locale'));
    console.log('Found language:', language);
    console.log('Calculated direction:', direction);
    console.log('All available languages:', master.languages);
    
    // Set language title and direction
    if (language) {
        currentLanguage.value = language.title;
    } else {
        // Fallback titles if language data not loaded yet
        currentLanguage.value = lang === 'ar' ? 'العربية' : 'English';
    }
    
    // Force update direction (override any persisted incorrect value)
    master.langDirection = direction;
    localStorage.setItem('langDirection', direction);
    
    // Update body class and direction for immediate font switching
    document.body.classList.remove('lang-ar', 'lang-en');
    document.body.classList.add('lang-' + lang);
    
    // Force direction change on HTML element
    const htmlElement = document.documentElement;
    htmlElement.setAttribute('dir', direction);
    htmlElement.setAttribute('lang', lang);
    
    // Also set it via style as a fallback
    htmlElement.style.direction = direction;
    
    // Debug log
    console.log(`=== DIRECTION UPDATE ===`);
    console.log(`Language changed to: ${lang}, Direction: ${direction}`);
    console.log('HTML dir attr after setting:', htmlElement.getAttribute('dir'));
    console.log('HTML style.direction:', htmlElement.style.direction);
    console.log('Master langDirection:', master.langDirection);
    
    // Send locale to server for future requests
    document.cookie = `locale=${lang}; path=/; max-age=31536000; SameSite=Lax`; // 1 year
    
    localization.fetchLocalizationData();
};

onMounted(() => {
    setCurrentLanguage(master.locale);
});

// Watch for when languages are loaded and update direction
watch(() => master.languages, (newLanguages) => {
    if (newLanguages && newLanguages.length > 0) {
        setCurrentLanguage(master.locale);
    }
}, { immediate: true });

watch(() => master.locale, (oldValue, newValue) => {
    if (oldValue !== newValue) {
        setCurrentLanguage(master.locale);
    }
});

const setCurrentCurrency = (currency) => {
    master.selectedCurrency = currency;
};

const reloadPage = () => {
    window.location.reload();
}

</script>
