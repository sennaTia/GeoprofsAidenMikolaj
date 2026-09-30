import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Mijn Aanvragen',
        href: '/mijn-aanvragen',
    },
];

export default function MijnAanvragen() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Mijn Aanvragen" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <h1 className="text-2xl">Mijn Aanvragen</h1>
                <p className="text-muted-foreground text-sm">Deze pagina wordt nog gebouwd.</p>
            </div>
        </AppLayout>
    );
}
