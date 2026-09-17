import { Link } from '@inertiajs/react';
import {
    BookOpen,
    Calendar,
    ClipboardList,
    FolderGit2,
    FolderKanban,
    LayoutGrid,
    Search,
} from 'lucide-react';
import AnnualProcurementPlanController from '@/actions/App/Http/Controllers/Planning/AnnualProcurementPlanController';
import MarketScopingController from '@/actions/App/Http/Controllers/Planning/MarketScopingController';
import PpmpController from '@/actions/App/Http/Controllers/Planning/PpmpController';
import ProcurementProjectController from '@/actions/App/Http/Controllers/Procurement/ProcurementProjectController';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Market Scoping',
        href: MarketScopingController.index(),
        icon: Search,
    },
    {
        title: 'PPMP',
        href: PpmpController.index(),
        icon: ClipboardList,
    },
    {
        title: 'Annual Procurement Plan',
        href: AnnualProcurementPlanController.index(),
        icon: Calendar,
    },
    {
        title: 'Procurement Projects',
        href: ProcurementProjectController.index(),
        icon: FolderKanban,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
