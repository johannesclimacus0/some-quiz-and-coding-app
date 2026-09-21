<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
    defineProps<{
        currentPage: number
        lastPage: number
        loading?: boolean
        label?: string
    }>(),
    { loading: false, label: 'pager://quizzes' },
)

const emit = defineEmits<{ change: [page: number] }>()

const pages = computed(() => {
    const visible = new Set([1, props.lastPage])

    for (
        let page = Math.max(2, props.currentPage - 1);
        page <= Math.min(props.lastPage - 1, props.currentPage + 1);
        page++
    ) {
        visible.add(page)
    }

    const result: Array<number | null> = []
    let previous = 0

    for (const page of [...visible].sort((a, b) => a - b)) {
        if (page - previous > 1) result.push(null)
        result.push(page)
        previous = page
    }

    return result
})

function change(page: number): void {
    if (props.loading || page === props.currentPage || page < 1 || page > props.lastPage) return
    emit('change', page)
}

function number(page: number): string {
    return String(page).padStart(2, '0')
}
</script>

<template>
    <nav
        v-if="lastPage > 1"
        aria-label="Страницы списка"
        class="flex min-h-11 flex-wrap items-center gap-x-3 gap-y-2 border-t border-[#cec9d5] bg-[#ede9f1] px-3 py-2 font-mono text-xs dark:border-[#363845] dark:bg-[#151820]"
    >
        <span class="mr-auto text-[#827a8b] dark:text-[#9792a5]">{{ label }}</span>
        <button
            type="button"
            class="min-h-10 px-1 text-[#447b9e] transition-colors hover:text-[#287da8] disabled:text-[#aaa4af] dark:text-[#8eb4d1] dark:hover:text-[#65b7df] dark:disabled:text-[#505362]"
            :disabled="loading || currentPage === 1"
            @click="change(currentPage - 1)"
        >
            &lt;-- назад
        </button>
        <span class="text-[#bdb6c4] dark:text-[#4d5060]">|</span>
        <template
            v-for="(page, index) in pages"
            :key="page ?? `gap-${index}`"
        >
            <span
                v-if="page === null"
                class="text-[#827a8b] dark:text-[#9792a5]"
            >
                ··
            </span>
            <button
                @click="change(page)"
                v-else
                type="button"
                class="min-h-10 min-w-10 px-2 py-1 transition-colors"
                :aria-current="page === currentPage ? 'page' : undefined"
                :aria-label="`Страница ${page}`"
                :class="
                    page === currentPage
                        ? 'bg-[#d9d2dd] text-[#2c2833] dark:bg-[#363845] dark:text-[#f0edf3]'
                        : 'text-[#777080] hover:text-[#287da8] dark:text-[#9792a5] dark:hover:text-[#65b7df]'
                "
                :disabled="loading"
            >
                {{ page === currentPage ? `[${number(page)}]` : number(page) }}
            </button>
        </template>
        <span class="text-[#bdb6c4] dark:text-[#4d5060]">|</span>
        <button
            @click="change(currentPage + 1)"
            type="button"
            class="min-h-10 px-1 text-[#447b9e] transition-colors hover:text-[#287da8] disabled:text-[#aaa4af] dark:text-[#8eb4d1] dark:hover:text-[#65b7df] dark:disabled:text-[#505362]"
            :disabled="loading || currentPage === lastPage"
        >
            вперёд --&gt;
        </button>
    </nav>
</template>
