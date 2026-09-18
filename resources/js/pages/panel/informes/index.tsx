import { Button } from '@/components/ui/button';
import TablaResponsiva, { type ColumnaTabla } from '@/components/yaku/tabla-responsiva';
import PanelLayout from '@/layouts/panel-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Download } from 'lucide-react';

interface InformeFila {
    uuid: string;
    codigo: string;
    numero: number;
    anio: number;
    trimestre: number | null;
    generadoAt: string | null;
    tienePdf: boolean;
}

interface Props {
    informes: InformeFila[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel', href: '/panel' },
    { title: 'Informes', href: '/panel/informes' },
];

const trimestreRoman = (n: number | null) => (n ? ['', 'I', 'II', 'III', 'IV'][n] : '—');

const formateaFecha = (iso: string | null) => {
    if (!iso) return '—';
    const d = new Date(iso);
    return d.toLocaleDateString('es-PE', { year: 'numeric', month: 'short', day: 'numeric' });
};

export default function InformesIndex({ informes }: Props) {
    const columnas: ColumnaTabla<InformeFila>[] = [
        { clave: 'codigo', etiqueta: 'Código', render: (i) => <span className="font-medium">{i.codigo}</span> },
        { clave: 'trim', etiqueta: 'Trimestre', render: (i) => `${trimestreRoman(i.trimestre)} — ${i.anio}` },
        { clave: 'gen', etiqueta: 'Generado', render: (i) => formateaFecha(i.generadoAt) },
    ];

    return (
        <PanelLayout breadcrumbs={breadcrumbs}>
            <Head title="Informes de gestión financiera" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-xl font-semibold">Informes de gestión financiera</h1>
                    <p className="text-muted-foreground text-sm">
                        Generados desde una rendición aprobada. Correlativo NNNN-YYYY-JASS por año.
                    </p>
                </div>

                <TablaResponsiva
                    columnas={columnas}
                    filas={informes}
                    obtenerLlave={(i) => i.uuid}
                    vacio="Aún no has generado ningún informe. Aprueba una rendición y desde su detalle podrás generarlo."
                    acciones={(i) =>
                        i.tienePdf ? (
                            <a href={route('panel.informes.descargar', i.uuid)}>
                                <Button size="sm" variant="outline">
                                    <Download className="h-4 w-4" />
                                    Descargar PDF
                                </Button>
                            </a>
                        ) : (
                            <span className="text-muted-foreground text-xs">Sin PDF</span>
                        )
                    }
                />
            </div>
        </PanelLayout>
    );
}
