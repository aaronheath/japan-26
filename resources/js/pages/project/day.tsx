import { GenerateCard } from '@/components/day/generate-card';
import { PromptDetailsCard } from '@/components/day/prompt-details-card';
import { ResultsCard } from '@/components/day/results-card';
import AppLayout from '@/layouts/app-layout';
import { formatDate } from '@/lib/utils';
import { show as showProject } from '@/routes/project';
import { show as showDay } from '@/routes/project/day';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface Project {
    id: number;
    name: string;
}

interface Day {
    id: number;
    number: number;
    date: string;
}

interface City {
    id: number;
    name: string;
    country_code?: string;
}

interface State {
    id: number;
    name: string;
}

interface CityWithState extends City {
    state: State;
}

interface LlmCall {
    id: number;
    response: string;
    created_at: string;
    llm_provider_name: string;
}

interface Travel {
    id: number;
    start_city: CityWithState;
    end_city: CityWithState;
    llm_call: LlmCall | null;
}

interface Activity {
    id: number;
    type: string;
    city: City | null;
    llm_call: LlmCall | null;
}

interface PromptData {
    task_prompt_slug: string;
    system_prompt_content?: string | null;
    task_prompt_content: string;
    supplementary_content?: string | null;
}

interface DayPageProps {
    project: Project;
    day: Day;
    tab: string;
    travel: Travel | Record<string, never>;
    activities: Activity[];
    travelPromptData: PromptData | null;
    activityPromptData: Record<number, PromptData>;
}

export default function DayPage({
    project,
    day,
    tab,
    travel,
    activities,
    travelPromptData,
    activityPromptData,
}: DayPageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: project.name,
            href: showProject(project.id).url,
        },
        {
            title: `Day ${day.number}`,
            href: showDay([project.id, day.number]).url,
        },
    ];

    const hasTravel = 'start_city' in travel;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${project.name} - Day ${day.number} - ${formatDate(day.date)}`} />

            <div className="p-4">
                <h1 className="mb-4 text-xl font-bold">
                    Day {day.number} - {formatDate(day.date)}
                </h1>

                <div className="mb-6 flex flex-wrap gap-x-4 gap-y-1">
                    <TabLink
                        href={`/project/${project.id}/day/${day.number}?tab=overview`}
                        active={tab === 'overview'}
                    >
                        Overview
                    </TabLink>

                    {hasTravel && (
                        <TabLink
                            href={`/project/${project.id}/day/${day.number}?tab=travel`}
                            active={tab === 'travel'}
                        >
                            Travel
                        </TabLink>
                    )}

                    {activities.map((activity, i) => (
                        <TabLink
                            key={i}
                            href={`/project/${project.id}/day/${day.number}?tab=activity-${i}`}
                            active={tab === `activity-${i}`}
                        >
                            <span className="capitalize">{activity.type}</span>
                        </TabLink>
                    ))}
                </div>

                <div className="space-y-6">
                    {tab === 'overview' && <OverviewTab />}

                    {tab === 'travel' && hasTravel && (
                        <TravelTab
                            project={project}
                            day={day}
                            travel={travel as Travel}
                            promptData={travelPromptData}
                        />
                    )}

                    {tab.startsWith('activity-') && (
                        <ActivityTab
                            project={project}
                            day={day}
                            tab={tab}
                            activities={activities}
                            activityPromptData={activityPromptData}
                        />
                    )}
                </div>
            </div>
        </AppLayout>
    );
}

function TabLink({ href, active, children }: { href: string; active: boolean; children: React.ReactNode }) {
    return (
        <Link
            href={href}
            className={`underline ${active ? 'font-medium text-foreground' : 'text-blue-600 dark:text-blue-400'}`}
        >
            {children}
        </Link>
    );
}

function OverviewTab() {
    return (
        <div>
            <p className="text-muted-foreground">Select a tab above to view details.</p>
        </div>
    );
}

function TravelTab({
    project,
    day,
    travel,
    promptData,
}: {
    project: Project;
    day: Day;
    travel: Travel;
    promptData: PromptData | null;
}) {
    const subtitle = `${travel.start_city.name}, ${travel.start_city.state.name} to ${travel.end_city.name}, ${travel.end_city.state.name}`;

    return (
        <>
            <ResultsCard title="Travel" subtitle={subtitle} response={travel.llm_call?.response} />

            {promptData && (
                <>
                    <PromptDetailsCard promptData={promptData} llmCall={travel.llm_call} />

                    <GenerateCard
                        projectId={project.id}
                        dayId={day.id}
                        dayNumber={day.number}
                        type="travel"
                        modelId={travel.id}
                        promptData={promptData}
                    />
                </>
            )}
        </>
    );
}

function ActivityTab({
    project,
    day,
    tab,
    activities,
    activityPromptData,
}: {
    project: Project;
    day: Day;
    tab: string;
    activities: Activity[];
    activityPromptData: Record<number, PromptData>;
}) {
    const index = parseInt(tab.replace('activity-', ''), 10);
    const activity = activities[index];

    if (!activity) {
        return <p className="text-muted-foreground">Activity not found.</p>;
    }

    const title = activity.city ? `${activity.type} in ${activity.city.name}` : activity.type;
    const promptData = activityPromptData[activity.id];

    return (
        <>
            <ResultsCard title={<span className="capitalize">{title}</span>} response={activity.llm_call?.response} />

            {promptData && (
                <>
                    <PromptDetailsCard promptData={promptData} llmCall={activity.llm_call} />

                    <GenerateCard
                        projectId={project.id}
                        dayId={day.id}
                        dayNumber={day.number}
                        type="activity"
                        modelId={activity.id}
                        promptData={promptData}
                    />
                </>
            )}
        </>
    );
}
