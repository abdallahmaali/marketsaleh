<template>
    <div class="shop-search-by-country">
        <!-- Search Header -->
        <div class="mb-6" v-if="showTitle">
            <h2 class="text-slate-950 text-xl font-medium leading-7">
                {{ $t('Search Shops by Country') }}
            </h2>
            <p class="text-slate-500 text-sm mt-1">
                {{ $t('Find shops in your preferred location') }}
            </p>
        </div>

        <!-- Search Form -->
        <div class="flex flex-col md:flex-row gap-4 items-end">
            <!-- Country Dropdown -->
            <div class="w-full md:w-1/3">
                <label class="text-slate-700 text-base font-normal leading-normal mb-2 block">
                    {{ $t('Country') }}
                </label>
                <v-select 
                    :options="countries" 
                    label="name"
                    :reduce="country => country"
                    v-model="selectedCountry" 
                    :placeholder="$t('Select Country')"
                    class="border border-slate-200 rounded-lg"
                    :dir="master.langDirection || 'ltr'"
                    @option:selected="onCountryChange"
                    :clearable="true">
                    <template #option="{ name, flag }">
                        <div class="flex items-center gap-2">
                            <span v-if="flag" class="text-lg">{{ flag }}</span>
                            <span>{{ name }}</span>
                        </div>
                    </template>
                    <template #selected-option="{ name, flag }">
                        <div class="flex items-center gap-2">
                            <span v-if="flag" class="text-lg">{{ flag }}</span>
                            <span>{{ name }}</span>
                        </div>
                    </template>
                </v-select>
            </div>

            <!-- Shop Name Search -->
            <div class="w-full md:w-1/2">
                <label class="text-slate-700 text-base font-normal leading-normal mb-2 block">
                    {{ $t('Shop Name') }}
                </label>
                <div class="relative">
                    <input 
                        type="text" 
                        v-model="searchName"
                        :placeholder="$t('Search shop by name')"
                        class="w-full p-3 border border-slate-200 rounded-lg focus:border-primary outline-none text-base font-normal"
                        :class="master.langDirection === 'rtl' ? 'text-right' : 'text-left'"
                        @input="onSearchChange"
                        @keyup.enter="performSearch">
                    <MagnifyingGlassIcon class="w-5 h-5 text-slate-400 absolute top-1/2 -translate-y-1/2" 
                        :class="master.langDirection === 'rtl' ? 'left-3' : 'right-3'" />
                </div>
            </div>

            <!-- Search Button -->
            <div class="w-full md:w-auto">
                <button 
                    @click="performSearch"
                    class="w-full md:w-auto px-6 py-3 bg-primary hover:bg-primary-600 text-white rounded-lg font-medium transition-colors duration-200 flex items-center justify-center gap-2"
                    :disabled="isSearching">
                    <MagnifyingGlassIcon class="w-5 h-5" />
                    <span>{{ $t('Search Shops') }}</span>
                    <LoadingSpin v-if="isSearching" class="w-4 h-4" />
                </button>
            </div>
        </div>

        <!-- Active Filters -->
        <div v-if="hasActiveFilters" class="mt-4 flex flex-wrap gap-2">
            <span class="text-sm text-slate-600">{{ $t('Active filters') }}:</span>
            
            <div v-if="selectedCountry" class="flex items-center gap-1 bg-primary-100 text-primary-700 px-3 py-1 rounded-full text-sm">
                <span v-if="selectedCountry.flag">{{ selectedCountry.flag }}</span>
                <span>{{ selectedCountry.name }}</span>
                <button @click="clearCountry" class="ml-1 hover:text-primary-900">
                    <XMarkIcon class="w-4 h-4" />
                </button>
            </div>

            <div v-if="searchName" class="flex items-center gap-1 bg-primary-100 text-primary-700 px-3 py-1 rounded-full text-sm">
                <span>"{{ searchName }}"</span>
                <button @click="clearSearchName" class="ml-1 hover:text-primary-900">
                    <XMarkIcon class="w-4 h-4" />
                </button>
            </div>

            <button @click="clearAllFilters" class="text-sm text-slate-500 hover:text-slate-700 underline">
                {{ $t('Clear all') }}
            </button>
        </div>

        <!-- Results Count -->
        <div v-if="showResultsCount && searchPerformed" class="mt-4 text-sm text-slate-600">
            {{ $t('Found') }} {{ resultsCount }} {{ $t('shops') }}
            <span v-if="selectedCountry">{{ $t('in') }} {{ selectedCountry.name }}</span>
            <span v-if="searchName">{{ $t('matching') }} "{{ searchName }}"</span>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { MagnifyingGlassIcon, XMarkIcon } from '@heroicons/vue/24/solid';
import LoadingSpin from './LoadingSpin.vue';
import { useMaster } from '../stores/MasterStore';

const master = useMaster();

// Props
const props = defineProps({
    // Show/hide the title section
    showTitle: {
        type: Boolean,
        default: true
    },
    // Show/hide results count
    showResultsCount: {
        type: Boolean,
        default: true
    },
    // Initial country filter
    initialCountry: {
        type: Object,
        default: null
    },
    // Initial search term
    initialSearchName: {
        type: String,
        default: ''
    },
    // Auto search on input change (with debounce)
    autoSearch: {
        type: Boolean,
        default: true
    },
    // Debounce delay for auto search (ms)
    debounceDelay: {
        type: Number,
        default: 500
    },
    // Results count (passed from parent)
    resultsCount: {
        type: Number,
        default: 0
    }
});

// Emits
const emit = defineEmits(['search', 'countryChange', 'nameChange', 'clear']);

// Data
const countries = ref([]);
const selectedCountry = ref(props.initialCountry);
const searchName = ref(props.initialSearchName);
const isSearching = ref(false);
const searchPerformed = ref(false);

// Debounce timer
let debounceTimer = null;

// Computed
const hasActiveFilters = computed(() => {
    return selectedCountry.value || searchName.value;
});

// Methods
const fetchCountries = async () => {
    try {
        const response = await axios.get('/countries');
        countries.value = response.data.data.countries;
    } catch (error) {
        console.error('Error fetching countries:', error);
    }
};

const onCountryChange = (country) => {
    selectedCountry.value = country;
    emit('countryChange', country);
    
    if (props.autoSearch) {
        performSearch();
    }
};

const onSearchChange = () => {
    emit('nameChange', searchName.value);
    
    if (props.autoSearch) {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            performSearch();
        }, props.debounceDelay);
    }
};

const performSearch = () => {
    isSearching.value = true;
    searchPerformed.value = true;
    
    const searchParams = {
        country: selectedCountry.value,
        name: searchName.value.trim(),
        country_id: selectedCountry.value?.id || null,
        country_name: selectedCountry.value?.name || null,
        search: searchName.value.trim()
    };

    emit('search', searchParams);
    
    // Reset loading state after a short delay
    setTimeout(() => {
        isSearching.value = false;
    }, 300);
};

const clearCountry = () => {
    selectedCountry.value = null;
    emit('countryChange', null);
    if (props.autoSearch) {
        performSearch();
    }
};

const clearSearchName = () => {
    searchName.value = '';
    emit('nameChange', '');
    if (props.autoSearch) {
        performSearch();
    }
};

const clearAllFilters = () => {
    selectedCountry.value = null;
    searchName.value = '';
    searchPerformed.value = false;
    emit('clear');
    emit('countryChange', null);
    emit('nameChange', '');
    if (props.autoSearch) {
        performSearch();
    }
};

// Public methods (can be called from parent)
const setCountry = (country) => {
    selectedCountry.value = country;
};

const setSearchName = (name) => {
    searchName.value = name;
};

const reset = () => {
    clearAllFilters();
};

// Expose methods to parent
defineExpose({
    setCountry,
    setSearchName,
    reset,
    performSearch
});

// Lifecycle
onMounted(() => {
    fetchCountries();
    
    // If initial values are provided, perform search
    if (hasActiveFilters.value && props.autoSearch) {
        performSearch();
    }
});

// Watch for prop changes
watch(() => props.initialCountry, (newCountry) => {
    selectedCountry.value = newCountry;
});

watch(() => props.initialSearchName, (newName) => {
    searchName.value = newName;
});
</script>

<style scoped>
/* Custom v-select styling to match design */
:deep(.vs__dropdown-toggle) {
    padding: 12px 16px;
    border: 1px solid #E2E8F0;
    border-radius: 8px;
    min-height: 48px;
}

:deep(.vs__selected-options) {
    padding: 0;
}

:deep(.vs__selected) {
    margin: 0;
    padding: 0;
}

:deep(.vs__search) {
    margin: 0;
    padding: 0;
    border: none;
}

:deep(.vs__actions) {
    padding: 0 8px;
}

:deep(.vs__clear) {
    margin-right: 4px;
}

:deep(.vs__open-indicator) {
    margin-left: 4px;
}

/* RTL support for v-select */
[dir="rtl"] :deep(.vs__actions) {
    padding: 0 8px 0 0;
}

[dir="rtl"] :deep(.vs__clear) {
    margin-left: 4px;
    margin-right: 0;
}

[dir="rtl"] :deep(.vs__open-indicator) {
    margin-right: 4px;
    margin-left: 0;
}
</style>