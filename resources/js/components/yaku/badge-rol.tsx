import { cn } from '@/lib/utils';
import { type Rol } from '@/types';

const etiquetaPorRol: Record<Rol, string> = {
    presidente: 'Presidente',
    tesorero: 'Tesorero',
    fiscal: 'Fiscal',
    vocal: 'Vocal',
    superadmin: 'Superadmin',
};

const clasesPorRol: Record<Rol, string> = {
    presidente: 'bg-yaku-rol-presidente-suave text-yaku-rol-presidente',
    tesorero: 'bg-yaku-rol-tesorero-suave text-yaku-rol-tesorero',
    fiscal: 'bg-yaku-rol-fiscal-suave text-yaku-rol-fiscal',
    vocal: 'bg-yaku-rol-vocal-suave text-yaku-rol-vocal',
    superadmin: 'bg-yaku-rol-superadmin-suave text-yaku-rol-superadmin',
};

/**
 * Identidad visual por rol (docs/design-system.md §5): presidente celeste,
 * tesorero rojo, fiscal violeta, vocal amarillo, superadmin azul — para
 * reconocer de un vistazo quién es quién (cabecera del panel, listados de
 * usuarios). No confundir con BadgeEstado, que indica estado de un registro.
 */
export default function BadgeRol({ rol, className }: { rol: Rol; className?: string }) {
    return (
        <span className={cn('inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', clasesPorRol[rol], className)}>
            {etiquetaPorRol[rol]}
        </span>
    );
}
