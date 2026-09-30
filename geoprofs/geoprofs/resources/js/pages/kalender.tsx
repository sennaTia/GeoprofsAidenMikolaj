import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Kalender',
        href: '/kalender',
    },
];

export default function Kalender() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Kalender" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <h1 className="text-2xl">Kalender</h1>
                <p className="text-muted-foreground text-sm">Deze pagina wordt nog gebouwd.</p>
            </div>
        </AppLayout>
    );
}
