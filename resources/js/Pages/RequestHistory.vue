<script setup lang="ts">
import { ref } from "vue"
import { router } from "@inertiajs/vue3"

interface Props {
  request: {
    id: number
    phone: string
    address: string
    problemText: string
    clientName: string
    status: string
    assignedTo?: number | null
    created_at: string
    master?: {
      id: number
      name: string
    } | null
  }
  audits: {
    data: Array<{
      id: number
      action: string
      old_status: string | null
      new_status: string | null
      created_at: string
      user: {
        id: number
        name: string
      }
    }>
    current_page: number
    last_page: number
    total: number
  }
}

const props = defineProps<Props>()

// Карта действий на русский
const actionLabels: Record<string, string> = {
  take: "Взял в работу",
  complete: "Завершил",
  cancel: "Отменил",
  assign: "Назначил мастера",
  create: "Создал заявку",
  update: "Обновил заявку"
}

const getActionLabel = (action: string): string => {
  return actionLabels[action] || action
}

// Цвета для статусов
const statusColor = (status: string | null): string => {
  if (!status) return "bg-gray-100 text-gray-800"
  
  const colors: Record<string, string> = {
    new: "bg-orange-100 text-orange-800",
    assigned: "bg-blue-100 text-blue-800",
    in_progress: "bg-green-100 text-green-800",
    done: "bg-gray-100 text-gray-800",
    canceled: "bg-red-100 text-red-800"
  }
  return colors[status] || "bg-gray-100 text-gray-800"
}

const formatDate = (date: string): string => {
  return new Date(date).toLocaleString("ru-RU", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit"
  })
}

const goBack = () => {
  router.visit("/requests")
}

const loadPage = (page: number) => {
  router.get(`/requests/${props.request.id}/history`, { page }, {
    preserveState: true,
    preserveScroll: true
  })
}
</script>

<template>
  <div class="container mx-auto px-4 py-8">
    <!-- Хлебные крошки -->
    <div class="mb-6 flex items-center gap-4">
      <button 
        @click="goBack"
        class="flex items-center gap-2 text-gray-600 hover:text-gray-900"
      >
        <ArrowLeftIcon class="w-4 h-4" />
        Назад
      </button>
      <h1 class="text-2xl font-semibold">История заявки #{{ request.id }}</h1>
    </div>

    <!-- Информация о заявке -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
      <div class="grid grid-cols-2 gap-4">
        <div>
          <p class="text-sm text-gray-600">Клиент:</p>
          <p class="font-medium">{{ request.clientName }}</p>
        </div>
        <div>
          <p class="text-sm text-gray-600">Телефон:</p>
          <p class="font-medium">{{ request.phone }}</p>
        </div>
        <div class="col-span-2">
          <p class="text-sm text-gray-600">Адрес:</p>
          <p class="font-medium">{{ request.address }}</p>
        </div>
        <div>
          <p class="text-sm text-gray-600">Описание:</p>
          <p class="font-medium">{{ request.problemText }}</p>
        </div>
        <div>
          <p class="text-sm text-gray-600">Статус:</p>
          <span :class="statusColor(request.status)" class="px-2 py-1 rounded text-sm">
            {{ request.status }}
          </span>
        </div>
        <div>
          <p class="text-sm text-gray-600">Создана:</p>
          <p class="font-medium">{{ formatDate(request.created_at) }}</p>
        </div>
      </div>
    </div>

    <!-- История действий -->
    <div class="bg-white rounded-lg shadow">
      <div class="px-6 py-4 border-b">
        <h2 class="text-lg font-medium">Хронология действий</h2>
      </div>

      <!-- Таймлайн -->
      <div class="p-6">
        <div v-if="!audits.data.length" class="text-center py-8 text-gray-500">
          Нет записей
        </div>

        <div v-else class="space-y-6">
          <div 
            v-for="audit in audits.data" 
            :key="audit.id"
            class="relative pl-8 pb-6 border-l-2 border-gray-200 last:pb-0"
          >
            <!-- Точка на линии -->
            <div class="absolute left-0 -translate-x-1.5 w-3 h-3 rounded-full bg-blue-500"></div>
            
            <!-- Дата -->
            <div class="text-sm text-gray-500 mb-1">
              {{ formatDate(audit.created_at) }}
            </div>
            
            <!-- Действие -->
            <div class="flex items-center gap-2 mb-2">
              <span class="font-medium">{{ audit.user.name }}</span>
              <span class="text-gray-600">{{ getActionLabel(audit.action) }}</span>
            </div>
            
            <!-- Изменение статуса -->
            <div v-if="audit.old_status || audit.new_status" class="flex items-center gap-2 text-sm">
              <span v-if="audit.old_status" :class="statusColor(audit.old_status)" class="px-2 py-0.5 rounded">
                {{ audit.old_status }}
              </span>
              <span v-if="audit.old_status && audit.new_status">→</span>
              <span v-if="audit.new_status" :class="statusColor(audit.new_status)" class="px-2 py-0.5 rounded">
                {{ audit.new_status }}
              </span>
            </div>
          </div>
        </div>

        <!-- Пагинация -->
        <div v-if="audits.last_page > 1" class="mt-6 flex justify-between items-center border-t pt-4">
          <button
            @click="loadPage(audits.current_page - 1)"
            :disabled="audits.current_page === 1"
            class="px-3 py-1 border rounded disabled:opacity-50"
          >
            Назад
          </button>
          <span class="text-sm">
            {{ audits.current_page }} из {{ audits.last_page }}
          </span>
          <button
            @click="loadPage(audits.current_page + 1)"
            :disabled="audits.current_page === audits.last_page"
            class="px-3 py-1 border rounded disabled:opacity-50"
          >
            Вперед
          </button>
        </div>
      </div>
    </div>
  </div>
</template>