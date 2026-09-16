export const getLastAuthEmail = (): string => {
    const app = document.querySelector<HTMLElement>('#app')

    return app?.dataset.lastAuthEmail ?? ''
}
