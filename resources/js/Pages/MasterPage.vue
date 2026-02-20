<script setup lang="ts">
import { computed } from "vue"
import { router } from "@inertiajs/vue3"
import RequestsMastersTable from "@/Components/RequestsMastersTable.vue"
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

interface Filters {
  status?: string
}

type RequestStatus =
  | "new"
  | "assigned"
  | "in_progress"
  | "done"
  | "canceled"

interface RequestItem {
  id: number
  phone: string
  address: string
  problemText: string
  status: RequestStatus
  created_at: string
  assignedTo?: number | null
}


const props = defineProps<{
  requests: RequestItem[]
  filters: Filters
}>()

const selectedStatus = computed<string>({
  get: () => props.filters.status ?? "all",
  set: (value: string) => {
    router.get(
      "/requests",
      { status: value },
      {
        preserveState: true,
        replace: true,
      }
    )
  },
})

const statuses = [
  { value: "all", label: "Все" },
  { value: "new", label: "Новые" },
  { value: "assigned", label: "Назначенные" },
  { value: "in_progress", label: "В работе" },
  { value: "done", label: "Выполненные" },
  { value: "canceled", label: "Отмененные" },
]


</script>

<template>
   <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Страница диспетчера
            </h2>
        </template>
 <div class="min-h-screen bg-gray-200 flex justify-center pt-10">
    <div class="bg-white shadow-xl rounded-xl p-6 w-[1100px]">

      <!-- Таблица вынесена -->
      <RequestsMastersTable
        :requests="requests"
      />
    </div>
  </div>
     </AuthenticatedLayout>
</template>





   