import { HorizonStatusBadge } from '@/components/horizon-status-badge';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { RegenerationStatusIndicator } from '@/components/regeneration-status-indicator';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useRegenerationStatus } from '@/hooks/use-regeneration-status';
import { dashboard } from '@/routes';
import { index as usersIndex } from '@/routes/admin/users';
import { index as whitelistedEmailsIndex } from '@/routes/admin/whitelisted-emails';
import { show as showProject } from '@/routes/project';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { Activity, LayoutGrid, Mail, Map, Users } from 'lucide-react';
import { useMemo } from 'react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Japan 2026',
        href: showProject(1),
        icon: Map,
    },
];

export function AppSidebar() {
    const { isHorizonRunning } = useRegenerationStatus(1);

    const footerNavItems: NavItem[] = useMemo(
        () => [
            {
                title: 'Users',
                href: usersIndex(),
                icon: Users,
            },
            {
                title: 'Whitelisted Emails',
                href: whitelistedEmailsIndex(),
                icon: Mail,
            },
            {
                title: 'Horizon',
                href: '/horizon',
                icon: Activity,
                external: true,
                suffix: <HorizonStatusBadge isRunning={isHorizonRunning} />,
            },
        ],
        [isHorizonRunning],
    );

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
                <RegenerationStatusIndicator projectId={1} />
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
