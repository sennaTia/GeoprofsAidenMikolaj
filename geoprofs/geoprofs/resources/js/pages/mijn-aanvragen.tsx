import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Mijn Aanvragen',
        href: '/mijn-aanvragen',
    },
];

type LeaveRequest = {
    id: number;
    start_date: string;
    end_date: string;
    days_requested: number | string;
    reason: string | null;
    status: string;
};

type Props = {
    leaveRequests: LeaveRequest[];
};

export default function MijnAanvragen({ leaveRequests }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Mijn Aanvragen" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <h1 className="text-2xl">Mijn Aanvragen</h1>

                {leaveRequests.length === 0 ? (
                    <p className="text-muted-foreground text-sm">
                        Je hebt nog geen verlofaanvragen.
                    </p>
                ) : (
                    <div className="overflow-x-auto rounded-lg border">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-muted">
                                <tr>
                                    <th className="p-3">Begindatum</th>
                                    <th className="p-3">Einddatum</th>
                                    <th className="p-3">Aantal dagen</th>
                                    <th className="p-3">Reden</th>
                                    <th className="p-3">Status</th>
                                </tr>
                            </thead>

                            <tbody>
                                {leaveRequests.map((aanvraag) => (
                                    <tr key={aanvraag.id} className="border-t">
                                        <td className="p-3">
     {aanvraag.start_date.substring(0, 10).split('-').reverse().join('-')}
     </td>

     <td className="p-3">
     {aanvraag.end_date.substring(0, 10).split('-').reverse().join('-')}
     </td>
                                        <td className="p-3">{aanvraag.days_requested}</td>
                                        <td className="p-3">{aanvraag.reason || '-'}</td>
                                        <td className="p-3">
                                            {aanvraag.status === 'in_behandeling'
                                                ? 'In behandeling'
                                                : aanvraag.status === 'goedgekeurd'
                                                  ? 'Goedgekeurd'
                                                  : aanvraag.status === 'afgewezen'
                                                    ? 'Afgewezen'
                                                    : aanvraag.status}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}