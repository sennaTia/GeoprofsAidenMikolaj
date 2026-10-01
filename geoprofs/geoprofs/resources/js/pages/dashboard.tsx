import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

interface Aanvraag {
    id: number;
    start_date: string;
    end_date: string;
    reason: string | null;
    status: 'in_behandeling' | 'goedgekeurd' | 'afgewezen';
    days_requested: number;
}

interface DashboardProps {
    jaar: number;
    totaal: number | null;
    opgenomen: number;
    resterend: number | null;
    inBehandeling: number;
    aantalOpenstaand: number;
    recenteAanvragen: Aanvraag[];
}

const statussen = {
    goedgekeurd: { label: 'Goedgekeurd', className: 'bg-green-100 text-green-700' },
    in_behandeling: { label: 'In afwachting', className: 'bg-amber-100 text-amber-700' },
    afgewezen: { label: 'Afgewezen', className: 'bg-red-100 text-red-700' },
};

function dagen(aantal: number) {
    return `${aantal.toLocaleString('nl-NL')} ${aantal > 0 && aantal < 2 ? 'dag' : 'dagen'}`;
}

function datum(waarde: string) {
    return new Date(waarde).toLocaleDateString('nl-NL', { day: '2-digit', month: 'short', timeZone: 'UTC' });
}

function SaldoKaart({ titel, waarde, toelichting }: { titel: string; waarde: string; toelichting: string }) {
    return (
        <Card className="p-5">
            <p className="text-muted-foreground text-sm font-medium">{titel}</p>
            <p className="mt-2 text-3xl">{waarde}</p>
            <p className="text-muted-foreground mt-2 text-xs">{toelichting}</p>
        </Card>
    );
}

export default function Dashboard({ jaar, totaal, opgenomen, resterend, inBehandeling, aantalOpenstaand, recenteAanvragen }: DashboardProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p className="text-xs font-semibold tracking-wide text-blue-600 uppercase">Werknemersportaal</p>
                        <h1 className="text-2xl">Dashboard</h1>
                        <p className="text-muted-foreground text-sm">Je verloftegoed en recente aanvragen in één overzicht.</p>
                    </div>
                    <Button asChild className="bg-blue-600 text-white hover:bg-blue-700">
                        <Link href="/verlof-aanvragen">Verlof aanvragen</Link>
                    </Button>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <SaldoKaart
                        titel="Opgenomen verlof"
                        waarde={dagen(opgenomen)}
                        toelichting={totaal === null ? `nog geen saldo ingesteld voor ${jaar}` : `van ${dagen(totaal)}`}
                    />
                    <SaldoKaart
                        titel="Resterend tegoed"
                        waarde={resterend === null ? '–' : dagen(resterend)}
                        toelichting={resterend === null ? `nog geen saldo ingesteld voor ${jaar}` : `beschikbaar in ${jaar}`}
                    />
                    <SaldoKaart
                        titel="In behandeling"
                        waarde={dagen(inBehandeling)}
                        toelichting={`${aantalOpenstaand} openstaande ${aantalOpenstaand === 1 ? 'aanvraag' : 'aanvragen'}`}
                    />
                </div>

                <Card className="p-5">
                    <h2 className="font-semibold">Recente verlofaanvragen</h2>
                    {recenteAanvragen.length === 0 ? (
                        <p className="text-muted-foreground mt-4 text-sm">Je hebt nog geen verlof aangevraagd.</p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="mt-4 w-full text-left text-sm">
                                <thead>
                                    <tr className="text-muted-foreground border-b text-xs uppercase">
                                        <th className="py-2 pr-4 font-medium">Periode</th>
                                        <th className="py-2 pr-4 font-medium">Reden</th>
                                        <th className="py-2 pr-4 font-medium">Status</th>
                                        <th className="py-2 text-right font-medium">Duur</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {recenteAanvragen.map((aanvraag) => (
                                        <tr key={aanvraag.id} className="border-b last:border-0">
                                            <td className="py-3 pr-4 whitespace-nowrap">
                                                {datum(aanvraag.start_date)} – {datum(aanvraag.end_date)}
                                            </td>
                                            <td className="text-muted-foreground py-3 pr-4">{aanvraag.reason ?? '–'}</td>
                                            <td className="py-3 pr-4">
                                                <span
                                                    className={`rounded-md px-2 py-1 text-xs font-medium whitespace-nowrap ${statussen[aanvraag.status].className}`}
                                                >
                                                    {statussen[aanvraag.status].label}
                                                </span>
                                            </td>
                                            <td className="py-3 text-right whitespace-nowrap">{dagen(aanvraag.days_requested)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </Card>
            </div>
        </AppLayout>
    );
}
