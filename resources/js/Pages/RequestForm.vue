<script setup>
import { reactive } from "vue";
import axios from "axios";
import { router } from "@inertiajs/vue3"; // Добавляем импорт

const form = reactive({
    clientName: "",
    address: "",
    phone: "",
    problemText: "",
});
const errors = reactive({});

const submitForm = async () => {
    try {
        // очищаем старые ошибки
        Object.keys(errors).forEach((key) => delete errors[key]);

        const response = await axios.post("/requests", form);

        alert(response.data.message);

        // очистка формы
        form.clientName = "";
        form.address = "";
        form.phone = "";
        form.problemText = "";
    } catch (error) {
        if (error.response?.status === 422) {
            Object.assign(errors, error.response.data.errors);
        } else {
            alert("Произошла ошибка");
        }
    }
};

// Функция для возврата на главную
const goBack = () => {
    router.visit('/'); // или другой путь, например '/dashboard'
};
</script>

<template>
    <div class="bg-white w-full max-w-md rounded-2xl shadow-xl p-8 mx-auto mt-10">
        <!-- Кнопка назад -->
        <div class="mb-4">
            <button 
                @click="goBack"
                class="flex items-center text-gray-600 hover:text-gray-900 transition duration-200"
            >
                <span class="text-xl mr-1">←</span>
                <span>Вернуться назад</span>
            </button>
        </div>

        <h2 class="text-xl font-semibold mb-6 text-gray-700">
            Создание заявки
        </h2>

        <form @submit.prevent="submitForm" class="space-y-5">
            <!-- Имя -->
            <div>
                <label class="block text-gray-600 mb-1">Имя:</label>
                <input
                    v-model="form.clientName"
                    type="text"
                    class="w-full bg-blue-100 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                    required
                />
                <div v-if="errors.clientName" class="text-red-500 text-sm mt-1">
                    {{ errors.clientName[0] }}
                </div>
            </div>

            <!-- Адрес -->
            <div>
                <label class="block text-gray-600 mb-1">Адрес:</label>
                <input
                    v-model="form.address"
                    type="text"
                    class="w-full bg-blue-100 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                    required
                />
                <div v-if="errors.address" class="text-red-500 text-sm mt-1">
                    {{ errors.address[0] }}
                </div>
            </div>

            <!-- Телефон -->
            <div>
                <label class="block text-gray-600 mb-1">Телефон:</label>
                <input
                    v-model="form.phone"
                    type="tel"
                    class="w-full bg-blue-100 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                    required
                />
                <div v-if="errors.phone" class="text-red-500 text-sm mt-1">
                    {{ errors.phone[0] }}
                </div>
            </div>

            <!-- Описание -->
            <div>
                <label class="block text-gray-600 mb-1">Описание проблемы:</label>
                <textarea
                    v-model="form.problemText"
                    rows="4"
                    class="w-full bg-blue-100 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                    required
                ></textarea>
                <div v-if="errors.problemText" class="text-red-500 text-sm mt-1">
                    {{ errors.problemText[0] }}
                </div>
            </div>

            <!-- Кнопка -->
            <div class="pt-2">
                <button
                    type="submit"
                    class="w-full bg-gray-800 text-white py-3 rounded-xl hover:bg-gray-700 transition duration-200"
                >
                    Отправить
                </button>
            </div>
        </form>
    </div>
</template>