import { Calendar } from 'lucide-react';

export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-blue-100 text-blue-600">
                <Calendar className="size-5" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-none font-semibold">
                    Verlof<span className="text-blue-600">Portaal</span>
                </span>
            </div>
        </>
    );
}
