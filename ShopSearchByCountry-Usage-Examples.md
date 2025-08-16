# ShopSearchByCountry Component Usage Examples

## Basic Usage

```vue
<template>
  <div>
    <!-- Basic usage with all default settings -->
    <ShopSearchByCountry @search="handleSearch" />
  </div>
</template>

<script setup>
import ShopSearchByCountry from '@/components/ShopSearchByCountry.vue';

const handleSearch = (searchParams) => {
  console.log('Search params:', searchParams);
  // searchParams contains: { country, name, country_id, country_name, search }
};
</script>
```

## Advanced Usage with Props

```vue
<template>
  <div>
    <!-- Customized usage -->
    <ShopSearchByCountry 
      :showTitle="false"
      :showResultsCount="true"
      :resultsCount="totalShops"
      :autoSearch="true"
      :debounceDelay="500"
      :initialCountry="selectedCountry"
      :initialSearchName="searchTerm"
      @search="handleSearch"
      @countryChange="onCountryChange"
      @nameChange="onNameChange"
      @clear="onClearSearch"
      ref="searchComponent"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import ShopSearchByCountry from '@/components/ShopSearchByCountry.vue';

const searchComponent = ref(null);
const totalShops = ref(0);
const selectedCountry = ref(null);
const searchTerm = ref('');

const handleSearch = (searchParams) => {
  // Handle search logic
  fetchShops(searchParams);
};

const onCountryChange = (country) => {
  console.log('Country changed:', country);
};

const onNameChange = (name) => {
  console.log('Name changed:', name);
};

const onClearSearch = () => {
  console.log('Search cleared');
  fetchShops({});
};

// Using exposed methods
const resetSearch = () => {
  searchComponent.value?.reset();
};

const setCountryProgrammatically = (country) => {
  searchComponent.value?.setCountry(country);
};
</script>
```

## Component Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `showTitle` | Boolean | `true` | Show/hide the title section |
| `showResultsCount` | Boolean | `true` | Show/hide results count |
| `initialCountry` | Object | `null` | Initial country filter |
| `initialSearchName` | String | `''` | Initial search term |
| `autoSearch` | Boolean | `true` | Auto search on input change |
| `debounceDelay` | Number | `500` | Debounce delay for auto search (ms) |
| `resultsCount` | Number | `0` | Results count (passed from parent) |

## Component Events

| Event | Payload | Description |
|-------|---------|-------------|
| `search` | `{ country, name, country_id, country_name, search }` | Fired when search is performed |
| `countryChange` | `country` | Fired when country selection changes |
| `nameChange` | `name` | Fired when search name changes |
| `clear` | - | Fired when all filters are cleared |

## Exposed Methods

| Method | Parameters | Description |
|--------|------------|-------------|
| `setCountry` | `country` | Set country programmatically |
| `setSearchName` | `name` | Set search name programmatically |
| `reset` | - | Reset all filters |
| `performSearch` | - | Trigger search manually |

## API Integration

The component expects your backend API to accept these parameters:

```
GET /shops?country_id=1&search=shop_name&page=1&per_page=12
```

### Expected API Response:
```json
{
  "data": {
    "shops": [...],
    "total": 25,
    "current_page": 1,
    "per_page": 12
  }
}
```

## RTL Support

The component automatically supports RTL layout based on `master.langDirection`:

- Text alignment adjusts automatically
- Country dropdown respects direction
- Search icon positioning changes
- Button layouts adapt to RTL

## Styling Customization

The component uses Tailwind CSS classes and can be customized via:

1. **Global CSS variables** for colors
2. **Scoped styles** for v-select customization
3. **Props** for showing/hiding elements
4. **Slots** (can be added if needed)

## Integration with Existing Pages

### In Shop Listing Page:
```vue
<!-- Already implemented in Shop.vue -->
<ShopSearchByCountry 
  :showTitle="false"
  :resultsCount="totalShops"
  @search="handleSearch"
/>
```

### In Homepage (Featured Shops Section):
```vue
<ShopSearchByCountry 
  :showTitle="true"
  :showResultsCount="false"
  :autoSearch="false"
  @search="navigateToShopsWithFilter"
/>
```

### In Modal/Popup:
```vue
<ShopSearchByCountry 
  :showTitle="false"
  :showResultsCount="false"
  :debounceDelay="200"
  @search="handleQuickSearch"
/>
```