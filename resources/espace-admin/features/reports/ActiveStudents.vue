<template>
    <Page title="Rapport Étudiants" width="xl">
        <Card block>
            <div class="overflow-hidden">
                <div class="flex justify-between items-center mb-4">
                    <h1 class="text-2xl font-bold">Rapport Étudiants</h1>
                   
                </div>

        <!-- Date Filters -->


        <div class="mb-4 text-sm text-gray-600">
            Total étudiants dans la base: {{ totalStudents }}
        </div>
<div class="bg-white shadow-lg rounded-2xl w-full max-w-7xl mx-auto mt-8 border border-gray-100">            <!-- export row -->
      <!-- Header Controls -->
<div class="px-6 py-5 border-b bg-gray-50 rounded-t-xl">
    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">

        <!-- Left Side : Export Button -->
        <div>
            <button
                @click="exportToExcel"
                class="px-5 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg shadow hover:bg-green-700 transition duration-200"
            >
                Exporter vers Excel
            </button>
        </div>

        <!-- Right Side : Filters -->
        <div class="flex flex-wrap items-end gap-4">

            <div class="flex flex-col flex-1 min-w-[200px]">
                <label class="text-xs font-semibold text-gray-600 mb-1">
                    Recherche
                </label>
                <input
                    v-model="filterSearch"
                    type="text"
                    placeholder="Nom, email…"
                    @keyup.enter="applyDateFilter"
                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
            </div>

            <div class="flex flex-col">
                <label class="text-xs font-semibold text-gray-600 mb-1">
                    Date Début
                </label>
                <input
                    v-model="filterDateStart"
                    type="date"
                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
            </div>

            <div class="flex flex-col">
                <label class="text-xs font-semibold text-gray-600 mb-1">
                    Date Fin
                </label>
                <input
                    v-model="filterDateEnd"
                    type="date"
                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
            </div>

            <button
                @click="applyDateFilter"
                class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg shadow hover:bg-blue-700 transition duration-200"
            >
                Filtrer
            </button>

            <button
                @click="resetDateFilter"
                class="px-4 py-2 bg-gray-500 text-white text-sm font-medium rounded-lg shadow hover:bg-gray-600 transition duration-200"
            >
                Réinitialiser
            </button>

        </div>
    </div>
</div>
    <!-- optionally show active search term -->
    <div v-if="filterSearch" class="text-sm text-gray-600 mb-2 px-6">
        Résultats pour «{{ filterSearch }}»
    </div>
    <!-- Scrollable Body -->
<div class="px-6 py-4 max-h-[520px] overflow-auto scrollbar-thin">
        <DataTable v-slot="{ item }" :headings="headings" :items="activeStudents" :bulk-actions="[]">
        <td class="cell">{{ item.name }}</td>
        <td class="cell">{{ item.email }}</td>
        <td class="cell">{{ item.phone || 'N/A' }}</td>
        <td class="cell">{{ item.ville || 'N/A' }}</td>
        <td class="cell">
            <span :class="item.status === 1 ? 'text-green-600' : 'text-red-600'">
                {{ item.status === 1 ? 'Actif' : 'Inactif' }}
            </span>
        </td>
        <td class="cell">
            <a
                :href="`/admin/reports/active-students/${item.id}/pdf`"
                target="_blank"
                class="btn btn-sm btn-secondary"
            >
                Voir
            </a>
        </td>
        <td class="cell">
            <div v-if="item.sales && item.sales.length">
                <div v-for="sale in item.sales" :key="sale.id" class="mb-1">
                    #{{ sale.id }} : {{ formatCurrency(sale.amount) }}
                </div>
            </div>
            <div v-else>–</div>
        </td>
        <td class="cell font-semibold text-blue-700">{{ formatCurrency(item.total_paid) }}</td>
        <td class="cell font-semibold text-orange-600">{{ formatCurrency(item.total_paid * 0.2) }}</td>
        <td class="cell font-bold text-green-700">{{ formatCurrency(item.total_paid * 1.2) }}</td>
    </DataTable>
</div>

            <!-- Pagination -->
          
            </div>

            
            <!-- end content wrapper -->
        </div>
        </Card>
    </Page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { DataTable } from '@shared/components';

const props = defineProps<{
    activeStudents: {
        data: any[];
        current_page: number;
        from: number;
        to: number;
        total: number;
        per_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
        links: any[];
    };
    totalStudents: number;
}>();

const activeStudents = ref(props.activeStudents);
const filterDateStart = ref('');
const filterDateEnd = ref('');
const filterSearch = ref('');

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    filterSearch.value = params.get('search') || '';
    filterDateStart.value = params.get('date_start') || '';
    filterDateEnd.value = params.get('date_end') || '';
});

const headings = [
    { name: 'Nom' },
    { name: 'Email' },
    { name: 'Téléphone' },
    { name: 'Ville' },
    { name: 'Status' },
    { name: 'Rapport' },
    { name: 'Paiements' },
    { name: 'Total HT' },
    { name: 'TVA (20%)' },
    { name: 'Total TTC' },
];

const formatCurrency = (value: number | string) => {
    const num = parseFloat(String(value));
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(num);
};

const applyDateFilter = () => {
    const params = new URLSearchParams();
    if (filterSearch.value) params.append('search', filterSearch.value);
    if (filterDateStart.value) params.append('date_start', filterDateStart.value);
    if (filterDateEnd.value) params.append('date_end', filterDateEnd.value);

    const queryString = params.toString();
    const url = queryString ? `/admin/reports/active-students?${queryString}` : '/admin/reports/active-students';

    window.location.href = url;
};

const resetDateFilter = () => {
    filterSearch.value = '';
    filterDateStart.value = '';
    filterDateEnd.value = '';
    window.location.href = '/admin/reports/active-students';
};

const exportToExcel = () => {
    const params = new URLSearchParams();
    if (filterSearch.value) params.append('search', filterSearch.value);
    if (filterDateStart.value) params.append('date_start', filterDateStart.value);
    if (filterDateEnd.value) params.append('date_end', filterDateEnd.value);

    const url = `/admin/reports/active-students/export${params.toString() ? '?' + params.toString() : ''}`;
    const link = document.createElement('a');
    link.href = url;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};
</script>
