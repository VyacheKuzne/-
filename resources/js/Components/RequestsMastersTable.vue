<script setup lang="ts">
import { ref } from "vue"
import { router } from "@inertiajs/vue3"

/* =========================
   Types
========================= */

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

interface Master {
  id: number
  name: string
}

/* =========================
   Props
========================= */

const props = defineProps<{
  requests: RequestItem[]
}>()

/* =========================
   Status helpers
========================= */

const statusLabels: Record<RequestStatus, string> = {
  new: "Новая",
  assigned: "Назначена",
  in_progress: "В работе",
  done: "Завершена",
  canceled: "Отменена",
}

const getStatusLabel = (status: RequestStatus): string =>
  statusLabels[status]

const statusColor = (status: RequestStatus): string => {
  switch (status) {
    case "new":
      return "text-orange-500"
    case "assigned":
      return "text-blue-500"
    case "in_progress":
      return "text-green-500"
    case "done":
      return "text-gray-700"
    case "canceled":
      return "text-red-500"
  }
}

/* =========================
   Actions
========================= */

const takeTask = (id:number) => {
    if (!confirm("Взять задачу в работу?")) return;
    
    router.put(`/requestsForMaster/${id}/take`, {}, {
        preserveScroll: true,
    });
};

const completeTask = (id:number) => {
    if (!confirm("Завершить задачу?")) return;
    
    router.put(`/requestsForMaster/${id}/complete`, {}, {
        preserveScroll: true,
    });
};
</script>

<template>
  <table class="w-full text-sm">
    <thead class="text-left border-b">
      <tr>
        <th class="pb-2">Телефон</th>
        <th class="pb-2">Адрес</th>
        <th class="pb-2">Описание</th>
        <th class="pb-2">Статус</th>
        <th class="pb-2">Создано</th>
      </tr>
    </thead>

    <tbody>
      <tr
        v-for="item in requests"
        :key="item.id"
        class="border-b hover:bg-gray-50"
      >
        <td class="py-2">{{ item.phone }}</td>
        <td>{{ item.address }}</td>
        <td>{{ item.problemText }}</td>

      
        <!-- Status -->
        <td :class="statusColor(item.status)">
          {{ getStatusLabel(item.status) }}
 <div class="flex gap-2">
                                <!-- Кнопка "Взять задачу" для статуса assigned -->
                                <button
                                    v-if="item.status === 'assigned'"
                                    @click="takeTask(item.id)"
                                    class="px-3 py-1 bg-blue-500 text-white text-xs rounded hover:bg-blue-600 transition"
                                >
                                    Взять задачу
                                </button>
                                
                                <!-- Кнопка "Сдать задачу" для статуса in_progress -->
                                <button
                                    v-if="item.status === 'in_progress'"
                                    @click="completeTask(item.id)"
                                    class="px-3 py-1 bg-green-500 text-white text-xs rounded hover:bg-green-600 transition"
                                >
                                    Сдать задачу
                              </button>
                            </div>
        </td>

        <td>
          {{ new Date(item.created_at).toLocaleDateString() }}
        </td>
      </tr>
    </tbody>
  </table>
</template>