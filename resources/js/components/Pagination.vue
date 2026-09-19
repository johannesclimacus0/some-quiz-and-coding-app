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
        class="flex min-h-11 flex-wrap items-center gap-x-3 gap-y-2 border-t border-[#c9c1cf] bg-[#eeeaf2] px-3 py-2 font-mono text-xs dark:border-[#343746] dark:bg-[#151820]"
    >
        <span class="mr-auto text-[#96909e] dark:text-[#656879]">{{ label }}</span>
        <button
            type="button"
            class="text-[#557789] transition-colors hover:text-[#287da8] disabled:text-[#aaa4af] dark:text-[#8ca8b7] dark:hover:text-[#65b7df] dark:disabled:text-[#505362]"
            :disabled="loading || currentPage === 1"
            @click="change(currentPage - 1)"
        >
            <-- назад
        </button>
        <span class="text-[#bdb6c4] dark:text-[#4d5060]">|</span>
        <template
            v-for="(page, index) in pages"
            :key="page ?? `gap-${index}`"
        >
            <span
                v-if="page === null"
                class="text-[#96909e] dark:text-[#656879]"
            >
                ··
            </span>
            <button
                @click="change(page)"
                v-else
                type="button"
                class="px-1 py-0.5 transition-colors"
                :class="
                    page === currentPage
                        ? 'bg-[#d9d2dd] text-[#27232d] dark:bg-[#343746] dark:text-[#f0edf3]'
                        : 'text-[#777080] hover:text-[#287da8] dark:text-[#918da0] dark:hover:text-[#65b7df]'
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
            class="text-[#557789] transition-colors hover:text-[#287da8] disabled:text-[#aaa4af] dark:text-[#8ca8b7] dark:hover:text-[#65b7df] dark:disabled:text-[#505362]"
            :disabled="loading || currentPage === lastPage"
        >
            вперед -->;
        </button>
    </nav>
</template>
