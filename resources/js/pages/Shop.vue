<template>
    <div class="main-container py-14">

        <div v-if="!isLoading" class="text-slate-800 text-3xl font-bold leading-9">
            {{ $t('All Shops') }}
        </div>
        <!-- loading -->
         <SkeletonLoader v-else class="w-48 sm:w-60 md:w-72 lg:w-96 h-12 rounded-lg" />

        <!-- Shop Search Component -->
        <div class="mt-8">
            <ShopSearchByCountry 
                :showTitle="false"
                :showResultsCount="true"
                :resultsCount="totalShops"
                :autoSearch="true"
                :debounceDelay="300"
                @search="handleSearch"
                @clear="clearSearch"
                ref="searchComponent" />
        </div>

        <!-- Shops -->
        <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 items-start">

            <div v-if="!isLoading" v-for="shop in shops" :key="shop.id" class="w-full">
                <ShopCard :shop="shop" />
            </div>

            <!-- loading -->
            <div v-else v-for="i in 8" :key="i">
                <SkeletonLoader class="w-full h-[200px] sm:h-[250px] rounded-lg" />
            </div>

        </div>

        <!-- Pagination -->
        <div v-if="shops.length > 0 && !isLoading" class="flex justify-between items-center w-full mt-8  gap-4 flex-wrap">
            <div class="text-slate-800 text-base font-normal leading-normal">
                {{ $t('Showing') }} {{ perPage * (currentPage - 1) + 1 }} {{ $t('to') }} {{ perPage * (currentPage - 1)
                    + shops.length }} {{ $t('of') }} {{
                    totalShops }} {{ $t('results') }}
            </div>
            <div>
                <vue-awesome-paginate :total-items="totalShops" :items-per-page="perPage" type="button"
                    :hide-prev-next-when-ends="true" :max-pages-shown="5" v-model="currentPage"
                    @click="onClickHandler" />
            </div>
        </div>

    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import ShopCard from '../components/ShopCard.vue';
import ShopSearchByCountry from '../components/ShopSearchByCountry.vue';
import { useMaster } from '../stores/MasterStore';
import SkeletonLoader from '../components/SkeletonLoader.vue';
const masterStore = useMaster();

import { useRouter } from 'vue-router';
import axios from 'axios';
const router = useRouter();

const isLoading = ref(true);
const currentPage = ref(1);
const perPage = ref(12);
const totalShops = ref(0);

const shops = ref([]);

// Search filters
const searchComponent = ref(null);
const searchFilters = ref({
    country_id: null,
    country_name: null,
    search: ''
});

onMounted(() => {
    if (!masterStore.multiVendor) {
        router.push('/');
        return;
    }
    
    // Check for URL query parameters
    const query = router.currentRoute.value.query;
    if (query.country_id || query.search) {
        searchFilters.value = {
            country_id: query.country_id || null,
            country_name: null,
            search: query.search || ''
        };
    }
    
    fetchShops();
    window.scrollTo(0, 0);
});

const onClickHandler = async (page) => {
    currentPage.value = page;
    fetchShops();
};

const handleSearch = (searchParams) => {
    // Update search filters
    searchFilters.value = {
        country_id: searchParams.country?.id || null,
        country_name: searchParams.country?.name || null,
        search: searchParams.search || ''
    };
    
    // Reset to first page when searching
    currentPage.value = 1;
    fetchShops();
};

const clearSearch = () => {
    searchFilters.value = {
        country_id: null,
        country_name: null,
        search: ''
    };
    currentPage.value = 1;
    fetchShops();
};

const fetchShops = async () => {
    window.scrollTo(0, 0)
    isLoading.value = true;
    
    const params = {
        page: currentPage.value,
        per_page: perPage.value
    };
    
    // Add search filters if they exist
    if (searchFilters.value.country_id) {
        params.country_id = searchFilters.value.country_id;
    }
    if (searchFilters.value.search) {
        params.search = searchFilters.value.search;
    }
    
    axios.get('/shops', {
        params: params,
        headers: {
            'Accept-Language': masterStore.locale || 'en',
        }
    }).then((response) => {
        totalShops.value = response.data.data.total;
        shops.value = response.data.data.shops;
        setTimeout(() => {
            isLoading.value = false;
        }, 300);
    }).catch((error) => {
        console.error('Error fetching shops:', error);
        isLoading.value = false;
    });
};

</script>
