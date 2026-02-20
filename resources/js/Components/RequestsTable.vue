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
  masters: Master[]
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

const cancelRequest = (id: number): void => {
  if (!confirm("Вы уверены что хотите отменить заявку?")) return

  router.put(`/requests/${id}/cancel`, {}, { preserveScroll: true })
}

const assigningId = ref<number | null>(null)
const selectedMaster = ref<number | null>(null)

const assignMaster = (requestId: number): void => {
  if (!selectedMaster.value) return

  router.put(
    `/requests/${requestId}/assign`,
    { master_id: selectedMaster.value },
    {
      preserveScroll: true,
      onSuccess: () => {
        assigningId.value = null
        selectedMaster.value = null
      },
    }
  )
}

const getMasterName = (
  masterId: number | null | undefined
): string => {
  if (!masterId) return "Мастер не назначен"
  const master = props.masters.find((m) => m.id === masterId)
  return master ? master.name : "Мастер не найден"
}
</script>

<template>
  <table class="w-full text-sm">
    <thead class="text-left border-b">
      <tr>
        <th class="pb-2">Телефон</th>
        <th class="pb-2">Адрес</th>
        <th class="pb-2">Описание</th>
        <th class="pb-2">Мастер</th>
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

        <!-- Master column -->
        <td>
          <div v-if="item.status === 'new'">
            <button
              v-if="assigningId !== item.id"
              @click="assigningId = item.id"
              class="text-blue-500 hover:underline text-sm"
            >
              Назначить мастера
            </button>

            <div v-else class="flex gap-2 mt-1">
              <select
                v-model="selectedMaster"
                class="border rounded px-2 py-1 text-sm"
              >
                <option disabled :value="null">
                  Выберите мастера
                </option>

                <option
                  v-for="master in masters"
                  :key="master.id"
                  :value="master.id"
                >
                  {{ master.name }}
                </option>
              </select>

              <button
                @click="assignMaster(item.id)"
                class="px-2 py-1 bg-green-500 text-white text-xs rounded hover:bg-green-600"
              >
                ✔
              </button>
            </div>
          </div>

          <div v-else-if="item.assignedTo">
            {{ getMasterName(item.assignedTo) }}
          </div>

          <div v-else class="text-gray-400">
            Мастер не назначен
          </div>
        </td>

        <!-- Status -->
        <td :class="statusColor(item.status)">
          {{ getStatusLabel(item.status) }}

          <button
            v-if="item.status !== 'canceled' && item.status !== 'done'"
            @click="cancelRequest(item.id)"
            class="text-red-500 hover:text-red-700 text-sm ml-2"
          >
            Отменить
          </button>
        </td>
 <!-- Actions column - НОВАЯ -->
        <td>
          <button
            @click="router.get(`/requests/${item.id}/history`)"
            class="text-blue-600 hover:text-blue-800 text-sm flex items-center gap-1"
          >
            <span>📋</span>
            История
          </button>
        </td>
        <td>
          {{ new Date(item.created_at).toLocaleDateString() }}
        </td>
      </tr>
    </tbody>
  </table>
</template>