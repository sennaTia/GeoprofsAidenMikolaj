import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { useEffect } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Afwezigheid inzien',
        href: '/afwezigheid-inzien',
    },
];

type EmployeeStatus = {
    id: number;
    name: string;
    status: 'aanwezig' | 'afwezig';
};

type Props = {
    departmentName: string;
    totalEmployees: number;
    absentEmployees: number;
    presentEmployees: number;
    employeeStatuses: EmployeeStatus[];
};

export default function AfwezigheidInzien({
    departmentName,
    totalEmployees,
    absentEmployees,
    presentEmployees,
    employeeStatuses,
}: Props) {
    useEffect(() => {
        const interval = setInterval(() => {
            router.reload({
                only: [
                    'totalEmployees',
                    'absentEmployees',
                    'presentEmployees',
                    'employeeStatuses',
                ],
            });
        }, 5000);

        return () => clearInterval(interval);
    }, []);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Afwezigheid inzien" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                {/* Titel */}
                <div>
                    <h1 className="text-2xl font-semibold">
                        Afwezigheid inzien
                    </h1>

                    <p className="mt-2 text-sm text-muted-foreground">
                        Actueel overzicht van de bezetting binnen afdeling{' '}
                        <span className="font-medium text-foreground">
                            {departmentName}
                        </span>
                        .
                    </p>
                </div>

                {/* Overzichtskaarten */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div className="rounded-xl border bg-card p-6 shadow-sm">
                        <p className="text-sm font-medium text-muted-foreground">
                            Totaal medewerkers
                        </p>
                        <p className="mt-3 text-4xl font-bold">
                            {totalEmployees}
                        </p>
                    </div>

                    <div className="rounded-xl border bg-card p-6 shadow-sm">
                        <p className="text-sm font-medium text-muted-foreground">
                            Aanwezig
                        </p>
                        <p className="mt-3 text-4xl font-bold text-green-600">
                            {presentEmployees}
                        </p>
                    </div>

                    <div className="rounded-xl border bg-card p-6 shadow-sm">
                        <p className="text-sm font-medium text-muted-foreground">
                            Afwezig
                        </p>
                        <p className="mt-3 text-4xl font-bold text-red-500">
                            {absentEmployees}
                        </p>
                    </div>
                </div>

                {/* Medewerkersoverzicht */}
                <div className="rounded-xl border bg-card shadow-sm">
                    <div className="border-b p-4">
                        <h2 className="text-lg font-semibold">
                            Medewerkersoverzicht
                        </h2>

                        <p className="mt-1 text-sm text-muted-foreground">
                            Actuele aanwezigheid van medewerkers binnen jouw afdeling.
                        </p>
                    </div>

                    {employeeStatuses.length === 0 ? (
                        <p className="p-4 text-sm text-muted-foreground">
                            Er zijn geen medewerkers gevonden.
                        </p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="bg-muted">
                                    <tr>
                                        <th className="p-3">
                                            Medewerker
                                        </th>
                                        <th className="p-3">
                                            Status
                                        </th>
                                    </tr>
                                </thead>

                                <tbody>
                                    {employeeStatuses.map((employee) => (
                                        <tr
                                            key={employee.id}
                                            className="border-t"
                                        >
                                            <td className="p-3 font-medium">
                                                {employee.name}
                                            </td>

                                            <td className="p-3">
                                                <span
                                                    className={`inline-flex rounded-full px-3 py-1 text-xs font-medium ${
                                                        employee.status === 'aanwezig'
                                                            ? 'bg-green-100 text-green-700'
                                                            : 'bg-red-100 text-red-700'
                                                    }`}
                                                >
                                                    {employee.status === 'aanwezig'
                                                        ? 'Aanwezig'
                                                        : 'Afwezig'}
                                                </span>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>

                {/* Automatische update */}
                <div className="rounded-xl border bg-muted/40 p-4">
                    <div className="flex items-center gap-3">
                        <span className="h-2.5 w-2.5 rounded-full bg-green-500" />

                        <div>
                            <p className="text-sm font-medium">
                                Actueel overzicht
                            </p>

                            <p className="text-xs text-muted-foreground">
                                De gegevens worden iedere 5 seconden automatisch bijgewerkt.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}