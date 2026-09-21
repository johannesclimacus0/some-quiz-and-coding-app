<script setup lang="ts">
import { editor as monacoEditor, type editor } from 'monaco-editor'
import { CodeEditor as MonacoEditor } from 'monaco-editor-vue3'
import { computed, onBeforeUnmount, onMounted, ref, shallowRef, watch } from 'vue'

const props = withDefaults(
    defineProps<{
        modelValue: string
        language: string
        readonly?: boolean
        maxLength?: number
        height?: string
    }>(),
    {
        readonly: false,
        maxLength: undefined,
        height: '24rem',
    },
)

const emit = defineEmits<{ 'update:modelValue': [value: string] }>()
const dark = ref(false)
const editorInstance = shallowRef<editor.IStandaloneCodeEditor | null>(null)
let themeObserver: MutationObserver | null = null

const value = computed({
    get: () => props.modelValue,
    set: (next: string) => {
        if (props.readonly) return
        emit('update:modelValue', props.maxLength ? next.slice(0, props.maxLength) : next)
    },
})
const theme = computed(() => (dark.value ? 'vs-dark' : 'vs'))
const options = computed<editor.IStandaloneEditorConstructionOptions>(() => ({
    automaticLayout: true,
    readOnly: props.readonly,
    domReadOnly: props.readonly,
    minimap: { enabled: false },
    fontFamily: "'JetBrains Mono', 'Cascadia Code', ui-monospace, SFMono-Regular, Menlo, monospace",
    fontSize: 14,
    lineHeight: 22,
    tabSize: props.language === 'sql' ? 2 : 4,
    insertSpaces: true,
    scrollBeyondLastLine: false,
    wordWrap: 'off',
    folding: true,
    glyphMargin: false,
    lineNumbersMinChars: 3,
    renderLineHighlight: props.readonly ? 'none' : 'line',
    overviewRulerLanes: 0,
    hideCursorInOverviewRuler: true,
    padding: { top: 12, bottom: 12 },
    ariaLabel: props.readonly ? 'Отправленный код' : 'Редактор кода',
}))

function rememberEditor(instance: editor.IStandaloneCodeEditor): void {
    editorInstance.value = instance
}

watch(theme, (next) => monacoEditor.setTheme(next))
watch(
    () => props.language,
    (next) => {
        const model = editorInstance.value?.getModel()
        if (model) monacoEditor.setModelLanguage(model, next)
    },
)

onMounted(() => {
    dark.value = document.documentElement.classList.contains('dark')
    themeObserver = new MutationObserver(() => {
        dark.value = document.documentElement.classList.contains('dark')
    })
    themeObserver.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    })
})

onBeforeUnmount(() => {
    editorInstance.value = null
    themeObserver?.disconnect()
})
</script>

<template>
    <div
        class="min-w-0 overflow-hidden border border-[#cec9d5] bg-white dark:border-[#363845] dark:bg-[#1e1e1e]"
    >
        <MonacoEditor
            v-model:value="value"
            width="100%"
            :height="height"
            :language="language"
            :theme="theme"
            :options="options"
            loading-text="Загрузка редактора…"
            @editor-did-mount="rememberEditor"
        />
    </div>
</template>
