<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { groupsApi, type Group } from '../../api/groups'
import type { Page } from '../../api/types'
import AlertMessage from '../../components/AlertMessage.vue'
import BaseButton from '../../components/BaseButton.vue'
import FormField from '../../components/FormField.vue'
import Pagination from '../../components/Pagination.vue'
import { useApiOperation } from '../../composables/useApiOperation'
import AdminLayout from '../../layouts/AdminLayout.vue'

const page = ref<Page<Group> | null>(null)
const creating = ref(false)
const name = ref('')
const { busy, error, errors, run } = useApiOperation()

function load(number = 1): Promise<boolean> {
    return run(async () => {
        page.value = await groupsApi.list({ page: number })
    })
}

async function create(): Promise<void> {
    const created = await run(async () => {
        await groupsApi.create({ name: name.value })
        page.value = await groupsApi.list({ page: 1 })
    })

    if (created) {
        name.value = ''
        creating.value = false
    }
}

onMounted(() => load())
</script>

<template>
    <AdminLayout title="Группы">
        <div class="space-y-4">
            <AlertMessage :message="error" />

            <section
                class="overflow-hidden border border-[#cec9d5] bg-[#fcfafd] dark:border-[#363845] dark:bg-[#101219]"
            >
                <header
                    class="flex flex-wrap items-center justify-between gap-3 border-b border-[#cec9d5] bg-[#f3f1f6] px-3 py-2 dark:border-[#363845] dark:bg-[#191b24]"
                >
                    <div class="font-mono text-xs">
                        <span class="text-[#1793d1]">db</span>
                        <span class="text-[#827a8b] dark:text-[#9792a5]">/</span>
                        <span>groups.index</span>
                        <span
                            v-if="busy"
                            class="ml-3 text-[#b24d91] dark:text-[#e781bd]"
                        >
                            синхронизация...
                        </span>
                    </div>
                    <button
                        type="button"
                        class="font-mono text-xs text-[#447b9e] transition-colors hover:text-[#287da8] dark:text-[#8eb4d1] dark:hover:text-[#65b7df]"
                        @click="creating = !creating"
                    >
                        {{ creating ? '[ закрыть ]' : '[ + новая группа ]' }}
                    </button>
                </header>

                <form
                    v-if="creating"
                    class="border-b border-[#cec9d5] bg-[#f4f1f6] p-4 dark:border-[#363845] dark:bg-[#191b24]"
                    @submit.prevent="create"
                >
                    <fieldset
                        :disabled="busy"
                        class="flex flex-wrap items-end gap-3"
                    >
                        <FormField
                            v-model="name"
                            id="group-name"
                            label="Название группы"
                            :error="errors.name?.[0]"
                            maxlength="255"
                            class="min-w-0 flex-1 basis-72"
                        />
                        <BaseButton
                            type="submit"
                            variant="primary"
                            :loading="busy"
                            loading-text="Создание…"
                        >
                            Создать
                        </BaseButton>
                    </fieldset>
                </form>

                <div
                    class="hidden grid-cols-[7rem_minmax(12rem,1fr)_8rem_8rem_3rem] gap-3 border-b border-[#cec9d5] bg-[#f3f1f6] px-3 py-2 font-mono text-[0.625rem] tracking-[0.12em] text-[#827a8b] dark:border-[#363845] dark:bg-[#191b24] dark:text-[#9792a5] xl:grid"
                >
                    <span>id</span>
                    <span>название</span>
                    <span>users</span>
                    <span>quizzes</span>
                    <span></span>
                </div>

                <p
                    v-if="busy && !page"
                    class="px-4 py-8 text-center font-mono text-xs text-[#686171] dark:text-[#9792a5]"
                >
                    запрос выполняется...
                </p>
                <p
                    v-else-if="page && !page.data.length"
                    class="px-4 py-8 text-center font-mono text-xs text-[#686171] dark:text-[#9792a5]"
                >
                    группы не созданы
                </p>

                <ul class="divide-y divide-[#dfdae5] dark:divide-[#242632]">
                    <li
                        v-for="group in page?.data"
                        :key="group.uuid"
                    >
                        <RouterLink
                            :to="{ name: 'admin.groups.edit', params: { group: group.uuid } }"
                            class="group grid gap-1.5 px-3 py-3 transition-colors hover:bg-[#ede9f1] dark:hover:bg-[#191b24] xl:grid-cols-[7rem_minmax(12rem,1fr)_8rem_8rem_3rem] xl:items-center xl:gap-3 xl:py-2.5"
                        >
                            <code class="text-[0.6875rem] text-[#827a8b] dark:text-[#9792a5]">
                                {{ group.uuid.slice(0, 16) }}
                            </code>
                            <span class="min-w-0 truncate font-mono text-sm">{{ group.name }}</span>
                            <span class="font-mono text-xs text-[#686171] dark:text-[#9792a5]">
                                {{ group.users_count }}
                            </span>
                            <span class="font-mono text-xs text-[#686171] dark:text-[#9792a5]">
                                {{ group.quizzes_count }}
                            </span>
                            <span
                                class="hidden text-right font-mono text-xs text-[#aaa4af] transition-colors group-hover:text-[#287da8] dark:text-[#505362] dark:group-hover:text-[#65b7df] md:block"
                            >
                                открыть
                            </span>
                        </RouterLink>
                    </li>
                </ul>

                <Pagination
                    v-if="page"
                    label="pager://groups"
                    :current-page="page.meta.current_page"
                    :last-page="page.meta.last_page"
                    :loading="busy"
                    @change="load"
                />
            </section>

            <BaseButton
                v-if="!page && !busy"
                @click="load()"
            >
                Повторить запрос
            </BaseButton>
        </div>
    </AdminLayout>
</template>
