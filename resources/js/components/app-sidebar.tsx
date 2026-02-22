import { HorizonStatusBadge } from '@/components/horizon-status-badge';
import { NavFooter } from '@/components/nav-footer';
import { NavUser } from '@/components/nav-user';
import { ProjectSelector } from '@/components/project-selector';
import { RegenerationStatusIndicator } from '@/components/regeneration-status-indicator';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useRegenerationStatus } from '@/hooks/use-regeneration-status';
import { resolveUrl } from '@/lib/utils';
import { index as usersIndex } from '@/routes/admin/users';
import { index as whitelistedEmailsIndex } from '@/routes/admin/whitelisted-emails';
import { show as showProject } from '@/routes/project';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
    Activity,
    Bed,
    Building2,
    CalendarDays,
    FileText,
    FolderKanban,
    Globe,
    Hotel,
    Mail,
    Map,
    MapPin,
    MapPinned,
    Plane,
    Users,
} from 'lucide-react';
import { useMemo } from 'react';
import AppLogo from './app-logo';

const manageNavItems: NavItem[] = [
    { title: 'Projects', href: '/manage/projects', icon: FolderKanban },
    { title: 'Countries', href: '/manage/countries', icon: Globe },
    { title: 'States', href: '/manage/states', icon: MapPin },
    { title: 'Cities', href: '/manage/cities', icon: Building2 },
    { title: 'Venues', href: '/manage/venues', icon: Hotel },
    { title: 'Addresses', href: '/manage/addresses', icon: MapPinned },
    { title: 'Prompts', href: '/manage/prompts', icon: FileText },
];

export function AppSidebar() {
    const { isHorizonRunning } = useRegenerationStatus(1);
    const { selectedProjectId } = usePage<SharedData>().props;

    const projectNavItems: NavItem[] = useMemo(
        () => [
            { title: 'Overview', href: showProject.url(selectedProjectId), icon: Map },
            { title: 'Travel', href: `/manage/project/${selectedProjectId}/travel`, icon: Plane },
            { title: 'Accommodations', href: `/manage/project/${selectedProjectId}/accommodations`, icon: Bed },
            { title: 'Activities', href: `/manage/project/${selectedProjectId}/activities`, icon: CalendarDays },
        ],
        [selectedProjectId],
    );

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
                            <Link href={showProject.url(selectedProjectId)} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <SidebarGroup className="px-2 py-0">
                    <SidebarGroupLabel>Project</SidebarGroupLabel>
                    <div className="px-2 pb-2 group-data-[collapsible=icon]:hidden">
                        <ProjectSelector />
                    </div>
                    <SidebarMenu>
                        {projectNavItems.map((item) => (
                            <SidebarMenuItem key={item.title}>
                                <SidebarMenuButton
                                    asChild
                                    isActive={
                                        typeof window !== 'undefined' &&
                                        window.location.pathname.startsWith(resolveUrl(item.href))
                                    }
                                    tooltip={{ children: item.title }}
                                >
                                    <Link href={item.href} prefetch>
                                        {item.icon && <item.icon />}
                                        <span>{item.title}</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        ))}
                    </SidebarMenu>
                </SidebarGroup>

                <SidebarGroup className="px-2 py-0">
                    <SidebarGroupLabel>Manage</SidebarGroupLabel>
                    <SidebarMenu>
                        {manageNavItems.map((item) => (
                            <SidebarMenuItem key={item.title}>
                                <SidebarMenuButton asChild tooltip={{ children: item.title }}>
                                    <Link href={item.href} prefetch>
                                        {item.icon && <item.icon />}
                                        <span>{item.title}</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        ))}
                    </SidebarMenu>
                </SidebarGroup>
            </SidebarContent>

            <SidebarFooter>
                <RegenerationStatusIndicator projectId={1} />
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
