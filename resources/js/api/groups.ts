import http from './http'
import type { Page, PageOptions, SearchPageOptions } from './types'

export type { Page } from './types'

export interface Group {
    uuid: string
    name: string
    users_count: number
    quizzes_count: number
    created_at: string | null
    updated_at: string | null
}

export interface GroupUserOption {
    uuid: string
    name: string
    email: string
    assigned: boolean
}

export interface GroupQuizOption {
    uuid: string
    title: string
    due_at: string | null
    assigned: boolean
}

const root = '/api/admin/groups'

export const groupsApi = {
    async list({ page = 1 }: PageOptions = {}): Promise<Page<Group>> {
        return (await http.get<Page<Group>>(root, { params: { page } })).data
    },
    async show({ uuid }: { uuid: string }): Promise<Group> {
        return (await http.get<{ data: Group }>(`${root}/${uuid}`)).data.data
    },
    async create({ name }: { name: string }): Promise<Group> {
        return (await http.post<{ data: Group }>(root, { name })).data.data
    },
    async update({ uuid, name }: { uuid: string; name: string }): Promise<Group> {
        return (await http.patch<{ data: Group }>(`${root}/${uuid}`, { name })).data.data
    },
    async remove({ uuid }: { uuid: string }): Promise<void> {
        await http.delete(`${root}/${uuid}`)
    },
    async listUsers({
        group,
        page = 1,
        search = '',
    }: SearchPageOptions & { group: string }): Promise<Page<GroupUserOption>> {
        return (
            await http.get<Page<GroupUserOption>>(`${root}/${group}/users`, {
                params: { page, search },
            })
        ).data
    },
    async addUser({ group, user }: { group: string; user: string }): Promise<GroupUserOption> {
        return (await http.put<{ data: GroupUserOption }>(`${root}/${group}/users/${user}`)).data
            .data
    },
    async removeUser({ group, user }: { group: string; user: string }): Promise<void> {
        await http.delete(`${root}/${group}/users/${user}`)
    },
    async listQuizzes({
        group,
        page = 1,
        search = '',
    }: SearchPageOptions & { group: string }): Promise<Page<GroupQuizOption>> {
        return (
            await http.get<Page<GroupQuizOption>>(`${root}/${group}/quizzes`, {
                params: { page, search },
            })
        ).data
    },
    async assignQuiz({ group, quiz }: { group: string; quiz: string }): Promise<GroupQuizOption> {
        return (await http.put<{ data: GroupQuizOption }>(`${root}/${group}/quizzes/${quiz}`)).data
            .data
    },
    async unassignQuiz({ group, quiz }: { group: string; quiz: string }): Promise<void> {
        await http.delete(`${root}/${group}/quizzes/${quiz}`)
    },
}
