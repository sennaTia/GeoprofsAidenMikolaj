import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Verlof aanvragen', href: '/verlof-aanvragen' },
];

const soorten = ['Vakantie', 'Ziek', 'Bijzonder verlof', 'Studieverlof', 'Anders'];

// telt ma t/m vr, inclusief begin- en einddatum
function telWerkdagen(start: string, end: string) {
    if (!start || !end) return 0;
    const s = new Date(start);
    const e = new Date(end);
    if (e < s) return 0;
    let aantal = 0;
    for (const d = new Date(s); d <= e; d.setDate(d.getDate() + 1)) {
        if (d.getDay() !== 0 && d.getDay() !== 6) aantal++;
    }
    return aantal;
}

export default function VerlofAanvragen() {
    const { data, setData, post, processing, errors, reset, transform, recentlySuccessful } = useForm({
        start_date: '',
        end_date: '',
        type: 'Vakantie',
        remark: '',
    });

    // Stuurt soort + opmerking samen op als "reason" (je database heeft geen aparte kolom voor soort)
    transform((d) => ({
        start_date: d.start_date,
        end_date: d.end_date,
        reason: d.remark ? `${d.type}: ${d.remark}` : d.type,
    }));

    const dagen = telWerkdagen(data.start_date, data.end_date);
    const vandaag = new Date().toISOString().split('T')[0];

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/verlof-aanvragen', { onSuccess: () => reset() });
    };

    const veld = 'w-full rounded-md border bg-background p-2 text-sm';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Verlof aanvragen" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-2xl font-semibold">Verlof aanvragen</h1>
                    <p className="text-sm text-muted-foreground">
                        Vul de gegevens in en verstuur je aanvraag.
                    </p>
                </div>

                <form onSubmit={submit} className="grid max-w-2xl gap-4 rounded-xl border p-6 shadow-sm">
                    {/* Soort verlof */}
                    <div className="grid gap-1">
                        <label className="text-sm font-medium">Soort verlof</label>
                        <select
                            className={veld}
                            value={data.type}
                            onChange={(e) => setData('type', e.target.value)}
                        >
                            {soorten.map((s) => (
                                <option key={s} value={s}>
                                    {s}
                                </option>
                            ))}
                        </select>
                    </div>

                    {/* Datums naast elkaar */}
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-1">
                            <label className="text-sm font-medium">Startdatum</label>
                            <input
                                type="date"
                                min={vandaag}
                                className={veld}
                                value={data.start_date}
                                onChange={(e) => setData('start_date', e.target.value)}
                            />
                            {errors.start_date && <p className="text-sm text-red-500">{errors.start_date}</p>}
                        </div>

                        <div className="grid gap-1">
                            <label className="text-sm font-medium">Einddatum</label>
                            <input
                                type="date"
                                min={data.start_date || vandaag}
                                className={veld}
                                value={data.end_date}
                                onChange={(e) => setData('end_date', e.target.value)}
                            />
                            {errors.end_date && <p className="text-sm text-red-500">{errors.end_date}</p>}
                        </div>
                    </div>

                    {/* Aantal dagen */}
                    <div className="rounded-md bg-muted p-3 text-sm">
                        Aantal werkdagen: <span className="font-semibold">{dagen}</span>
                    </div>

                    {/* Opmerking */}
                    <div className="grid gap-1">
                        <label className="text-sm font-medium">Extra opmerking (optioneel)</label>
                        <textarea
                            rows={3}
                            maxLength={200}
                            className={veld}
                            placeholder="Bijvoorbeeld: familiebezoek in het buitenland"
                            value={data.remark}
                            onChange={(e) => setData('remark', e.target.value)}
                        />
                        <p className="text-right text-xs text-muted-foreground">{data.remark.length}/200</p>
                        {errors.reason && <p className="text-sm text-red-500">{errors.reason}</p>}
                    </div>

                    {/* Knoppen */}
                    <div className="flex items-center gap-3">
                        <button
                            type="submit"
                            disabled={processing || dagen === 0}
                            className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:opacity-50"
                        >
                            Aanvraag versturen
                        </button>
                        <button
                            type="button"
                            onClick={() => reset()}
                            className="rounded-md border px-4 py-2 text-sm"
                        >
                            Leegmaken
                        </button>
                        {recentlySuccessful && <span className="text-sm text-green-600">Aanvraag verstuurd!</span>}
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}