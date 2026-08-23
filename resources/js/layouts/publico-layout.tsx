import AppLogo from '@/components/app-logo';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';

interface Props {
    children: React.ReactNode;
}

/**
 * Layout del portal público (spec §6.13, contenido real en Fase 8). Sin
 * sidebar: header simple con acceso al sistema y footer institucional.
 */
export default function PublicoLayout({ children }: Props) {
    return (
        <div className="bg-background text-foreground flex min-h-screen flex-col">
            <header className="border-border border-b">
                <div className="mx-auto flex h-16 w-full max-w-5xl items-center justify-between px-4">
                    <Link href="/" className="flex items-center gap-2">
                        <AppLogo />
                    </Link>
                    <Button asChild variant="outline">
                        <Link href={route('login')}>Acceder al sistema</Link>
                    </Button>
                </div>
            </header>

            <main className="mx-auto w-full max-w-5xl flex-1 px-4 py-8">{children}</main>

            <footer className="border-border text-muted-foreground border-t py-6 text-center text-sm">
                YakuCore — gestión de JASS de Huaraz
            </footer>
        </div>
    );
}
