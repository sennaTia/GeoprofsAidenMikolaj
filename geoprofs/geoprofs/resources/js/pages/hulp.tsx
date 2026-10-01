import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Hulp & Support',
        href: '/hulp',
    },
];

export default function Hulp() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Hulp & Support" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <h1 className="text-2xl">Hulp & Support</h1>
                <p className="text-muted-foreground text-sm">Deze pagina wordt nog gebouwd.</p>
            </div>
        </AppLayout>
    );
}
