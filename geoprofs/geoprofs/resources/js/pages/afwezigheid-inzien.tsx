import { Head, router } from '@inertiajs/react';
import { useEffect } from 'react';

type Props = {
    departmentName: string;
    totalEmployees: number;
    absentEmployees: number;
    presentEmployees: number;
};

export default function AfwezigheidInzien({
    departmentName,
    totalEmployees,
    absentEmployees,
    presentEmployees,
}: Props) {
    useEffect(() => {
        const interval = setInterval(() => {
            router.reload({
                only: ['totalEmployees', 'absentEmployees', 'presentEmployees'],
            });
        }, 5000);

        return () => clearInterval(interval);
    }, []);

    return (
        <>
            <Head title="Afwezigheid inzien" />

            <div className="p-6">
                <h1 className="mb-6 text-2xl font-bold">
                    Afwezigheid inzien
                </h1>

               <p className="mb-4">
    Actueel overzicht van de bezetting binnen afdeling {departmentName}.
</p>

                <div className="overflow-hidden rounded border">
                    <table className="w-full text-left">
                        <thead>
                            <tr>
                                <th className="p-3">Onderdeel</th>
                                <th className="p-3">Aantal</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td className="p-3">Totaal medewerkers</td>
                                <td className="p-3">{totalEmployees}</td>
                            </tr>

                            <tr>
                                <td className="p-3">Afwezig</td>
                                <td className="p-3">{absentEmployees}</td>
                            </tr>

                            <tr>
                                <td className="p-3">Aanwezig</td>
                                <td className="p-3">{presentEmployees}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p className="mt-4 text-sm text-gray-500">
                    Het overzicht wordt iedere 5 seconden bijgewerkt.
                </p>
            </div>
        </>
    );
}