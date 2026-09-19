export interface Page<T> {
    data: T[]
    meta: {
        current_page: number
        last_page: number
        total: number
    }
}

export interface PageOptions {
    page?: number
}

export interface SearchPageOptions extends PageOptions {
    search?: string
}
