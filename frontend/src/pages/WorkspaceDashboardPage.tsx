import { useParams } from 'react-router'

export default function WorkspaceDashboardPage() {
    const { id } = useParams<{ id: string }>()
    return <div>Workspace dashboard — {id}</div>
}
