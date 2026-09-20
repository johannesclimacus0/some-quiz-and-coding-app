<script setup lang="ts">
import { ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { groupsApi, type Group, type GroupQuizOption, type GroupUserOption } from '../../api/groups'
import type { Page } from '../../api/types'
import AlertMessage from '../../components/AlertMessage.vue'
import BaseButton from '../../components/BaseButton.vue'
import FormField from '../../components/FormField.vue'
import Pagination from '../../components/Pagination.vue'
import { useApiOperation } from '../../composables/useApiOperation'
import AdminLayout from '../../layouts/AdminLayout.vue'

const route = useRoute()
const router = useRouter()
const group = ref<Group | null>(null)
const name = ref('')
const users = ref<Page<GroupUserOption> | null>(null)
const quizzes = ref<Page<GroupQuizOption> | null>(null)
const userSearch = ref('')
const appliedUserSearch = ref('')
const quizSearch = ref('')
const appliedQuizSearch = ref('')

const mainOperation = useApiOperation()
const userOperation = useApiOperation()
const quizOperation = useApiOperation()

function groupUuid(): string {
    return String(route.params.group)
}

function loadUsers(page = 1): Promise<boolean> {
    return userOperation.run(async () => {
        users.value = await groupsApi.listUsers({
            group: groupUuid(),
            page,
            search: appliedUserSearch.value,
        })
    })
}

function loadQuizzes(page = 1): Promise<boolean> {
    return quizOperation.run(async () => {
        quizzes.value = await groupsApi.listQuizzes({
            group: groupUuid(),
            page,
            search: appliedQuizSearch.value,
        })
    })
}

async function loadGroup(): Promise<void> {
    group.value = null
    users.value = null
    quizzes.value = null
    const loaded = await mainOperation.run(async () => {
        group.value = await groupsApi.show({ uuid: groupUuid() })
        name.value = group.value.name
    })

    if (loaded) await Promise.all([loadUsers(), loadQuizzes()])
}

async function saveGroup(): Promise<void> {
    if (!group.value) return
    await mainOperation.run(async () => {
        group.value = await groupsApi.update({ uuid: group.value!.uuid, name: name.value })
        name.value = group.value.name
    })
}

async function deleteGroup(): Promise<void> {
    if (
        !group.value ||
        !window.confirm('Удалить группу? Назначения сохранятся до возможного восстановления.')
    )
        return
    const uuid = group.value.uuid
    await mainOperation.run(async () => {
        await groupsApi.remove({ uuid })
        await router.push({ name: 'admin.groups' })
    })
}

async function toggleUser(user: GroupUserOption): Promise<void> {
    if (!group.value) return
    const wasAssigned = user.assigned
    const changed = await userOperation.run(async () => {
        if (wasAssigned) await groupsApi.removeUser({ group: group.value!.uuid, user: user.uuid })
        else await groupsApi.addUser({ group: group.value!.uuid, user: user.uuid })
    })

    if (changed) {
        user.assigned = !wasAssigned
        group.value.users_count += wasAssigned ? -1 : 1
    }
}

async function toggleQuiz(quiz: GroupQuizOption): Promise<void> {
    if (!group.value) return
    const wasAssigned = quiz.assigned
    const changed = await quizOperation.run(async () => {
        if (wasAssigned) await groupsApi.unassignQuiz({ group: group.value!.uuid, quiz: quiz.uuid })
        else await groupsApi.assignQuiz({ group: group.value!.uuid, quiz: quiz.uuid })
    })

    if (changed) {
        quiz.assigned = !wasAssigned
        group.value.quizzes_count += wasAssigned ? -1 : 1
    }
}

function searchUsers(): void {
    appliedUserSearch.value = userSearch.value.trim()
    void loadUsers(1)
}

function searchQuizzes(): void {
    appliedQuizSearch.value = quizSearch.value.trim()
    void loadQuizzes(1)
}

watch(
    () => route.params.group,
    () => void loadGroup(),
    { immediate: true },
)
</script>

<template>
    <AdminLayout :title="group?.name ?? 'Редактирование группы'">
        <div class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3 font-mono text-xs">
                <RouterLink
                    :to="{ name: 'admin.groups' }"
                    class="text-[#557789] transition-colors hover:text-[#287da8] dark:text-[#8ca8b7] dark:hover:text-[#65b7df]"
                >
                    &lt;-- список групп
                </RouterLink>
                <span
                    v-if="group"
                    class="text-[#96909e] dark:text-[#656879]"
                >
                    id:{{ group.uuid.slice(0, 16) }}
                </span>
            </div>
            <AlertMessage :message="mainOperation.error.value" />
            <p
                v-if="mainOperation.busy.value && !group"
                class="border border-[#c9c1cf] p-8 text-center font-mono text-xs dark:border-[#343746]"
            >
                загрузка группы...
            </p>
            <template v-if="group">
                <section
                    class="border border-[#c9c1cf] bg-[#fbfafd] dark:border-[#343746] dark:bg-[#11131a]"
                >
                    <header
                        class="flex flex-wrap items-center justify-between gap-3 border-b border-[#c9c1cf] bg-[#e8e4eb] px-3 py-2 font-mono text-xs dark:border-[#343746] dark:bg-[#181b23]"
                    >
                        <span class="inline-flex">
                            <span class="text-[#1793d1]">group</span>
                            <span class="text-[#96909e] dark:text-[#656879]">://</span>
                            {{ group.name }}
                        </span>
                        <button
                            type="button"
                            class="text-[#a34a70] transition-colors hover:text-[#c14378] dark:text-[#cf729a] dark:hover:text-[#f077a8]"
                            :disabled="mainOperation.busy.value"
                            @click="deleteGroup"
                        >
                            [ удалить группу ]
                        </button>
                    </header>
                    <form
                        class="flex flex-wrap items-end gap-3 p-4"
                        @submit.prevent="saveGroup"
                    >
                        <fieldset
                            :disabled="mainOperation.busy.value"
                            class="contents"
                        >
                            <FormField
                                v-model="name"
                                id="group-editor-name"
                                label="Название группы"
                                :error="mainOperation.errors.value.name?.[0]"
                                maxlength="255"
                                class="min-w-0 flex-1 basis-72"
                            />
                            <BaseButton
                                type="submit"
                                variant="primary"
                                :loading="mainOperation.busy.value"
                                loading-text="Сохранение…"
                            >
                                Сохранить
                            </BaseButton>
                        </fieldset>
                    </form>
                </section>
                <div class="grid gap-4 xl:grid-cols-2">
                    <section
                        class="min-w-0 overflow-hidden border border-[#c9c1cf] bg-[#fbfafd] dark:border-[#343746] dark:bg-[#11131a]"
                    >
                        <header
                            class="flex items-center border-b border-[#c9c1cf] bg-[#e8e4eb] px-3 py-2 font-mono text-xs dark:border-[#343746] dark:bg-[#181b23]"
                        >
                            <span class="text-[#1793d1]">members</span>
                            <span class="text-[#96909e] dark:text-[#656879]">://</span>
                            {{ group.users_count }}
                        </header>
                        <form
                            @submit.prevent="searchUsers"
                            class="flex items-end gap-2 border-b border-[#d8d1dc] p-3 dark:border-[#343746]"
                        >
                            <FormField
                                v-model="userSearch"
                                id="user-search"
                                label="Поиск по имени или email"
                                class="min-w-0 flex-1"
                            />
                            <BaseButton
                                type="submit"
                                :loading="userOperation.busy.value"
                            >
                                Найти
                            </BaseButton>
                        </form>
                        <AlertMessage
                            :message="userOperation.error.value"
                            class="m-3"
                        />
                        <p
                            v-if="userOperation.busy.value && !users"
                            class="p-6 text-center font-mono text-xs"
                        >
                            загрузка пользователей...
                        </p>
                        <p
                            v-else-if="users && !users.data.length"
                            class="p-6 text-center font-mono text-xs text-[#68616f] dark:text-[#918da0]"
                        >
                            пользователи не найдены
                        </p>
                        <ul class="divide-y divide-[#e0dae4] dark:divide-[#292c36]">
                            <li
                                v-for="user in users?.data"
                                :key="user.uuid"
                                class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-3 px-3 py-2.5"
                            >
                                <div class="min-w-0">
                                    <p class="truncate font-mono text-sm">{{ user.name }}</p>
                                    <p
                                        class="truncate font-mono text-[0.6875rem] text-[#68616f] dark:text-[#918da0]"
                                    >
                                        {{ user.email }} · {{ user.uuid.slice(0, 16) }}
                                    </p>
                                </div>
                                <button
                                    @click="toggleUser(user)"
                                    type="button"
                                    class="font-mono text-xs transition-colors disabled:opacity-40"
                                    :class="
                                        user.assigned
                                            ? 'text-[#b24d91] hover:text-[#c14378] dark:text-[#e781bd]'
                                            : 'text-[#557789] hover:text-[#287da8] dark:text-[#8ca8b7] dark:hover:text-[#65b7df]'
                                    "
                                    :disabled="userOperation.busy.value"
                                >
                                    {{ user.assigned ? '[ убрать ]' : '[ добавить ]' }}
                                </button>
                            </li>
                        </ul>
                        <Pagination
                            v-if="users"
                            label="pager://members"
                            :current-page="users.meta.current_page"
                            :last-page="users.meta.last_page"
                            :loading="userOperation.busy.value"
                            @change="loadUsers"
                        />
                    </section>
                    <section
                        class="min-w-0 overflow-hidden border border-[#c9c1cf] bg-[#fbfafd] dark:border-[#343746] dark:bg-[#11131a]"
                    >
                        <header
                            class="flex items-center border-b border-[#c9c1cf] bg-[#e8e4eb] px-3 py-2 font-mono text-xs dark:border-[#343746] dark:bg-[#181b23]"
                        >
                            <span class="text-[#1793d1]">assignments</span>
                            <span class="text-[#96909e] dark:text-[#656879]">://</span>
                            {{ group.quizzes_count }}
                        </header>
                        <form
                            @submit.prevent="searchQuizzes"
                            class="flex items-end gap-2 border-b border-[#d8d1dc] p-3 dark:border-[#343746]"
                        >
                            <FormField
                                v-model="quizSearch"
                                id="quiz-search"
                                label="Поиск по названию"
                                class="min-w-0 flex-1"
                            />
                            <BaseButton
                                type="submit"
                                :loading="quizOperation.busy.value"
                            >
                                Найти
                            </BaseButton>
                        </form>
                        <AlertMessage
                            :message="quizOperation.error.value"
                            class="m-3"
                        />
                        <p
                            v-if="quizOperation.busy.value && !quizzes"
                            class="p-6 text-center font-mono text-xs"
                        >
                            загрузка квизов...
                        </p>
                        <p
                            v-else-if="quizzes && !quizzes.data.length"
                            class="p-6 text-center font-mono text-xs text-[#68616f] dark:text-[#918da0]"
                        >
                            квизы не найдены
                        </p>
                        <ul class="divide-y divide-[#e0dae4] dark:divide-[#292c36]">
                            <li
                                v-for="quiz in quizzes?.data"
                                :key="quiz.uuid"
                                class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-3 px-3 py-2.5"
                            >
                                <div class="min-w-0">
                                    <p class="truncate font-mono text-sm">{{ quiz.title }}</p>
                                    <p
                                        class="truncate font-mono text-[0.6875rem] text-[#68616f] dark:text-[#918da0]"
                                    >
                                        {{ quiz.uuid.slice(0, 16) }} ·
                                        {{
                                            quiz.due_at
                                                ? new Date(quiz.due_at).toLocaleString()
                                                : 'no_deadline'
                                        }}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    class="font-mono text-xs transition-colors disabled:opacity-40"
                                    :class="
                                        quiz.assigned
                                            ? 'text-[#b24d91] hover:text-[#c14378] dark:text-[#e781bd]'
                                            : 'text-[#557789] hover:text-[#287da8] dark:text-[#8ca8b7] dark:hover:text-[#65b7df]'
                                    "
                                    :disabled="quizOperation.busy.value"
                                    @click="toggleQuiz(quiz)"
                                >
                                    {{ quiz.assigned ? '[ снять ]' : '[ назначить ]' }}
                                </button>
                            </li>
                        </ul>
                        <Pagination
                            v-if="quizzes"
                            label="pager://assignments"
                            :current-page="quizzes.meta.current_page"
                            :last-page="quizzes.meta.last_page"
                            :loading="quizOperation.busy.value"
                            @change="loadQuizzes"
                        />
                    </section>
                </div>
            </template>
        </div>
    </AdminLayout>
</template>
