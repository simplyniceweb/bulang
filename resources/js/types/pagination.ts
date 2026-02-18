export interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

export interface Paginated<T> {
    data: T[]
    links: PaginationLink[]
}
