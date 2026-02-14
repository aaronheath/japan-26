import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { type SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';

export function ProjectSelector() {
    const { projects, selectedProjectId } = usePage<SharedData>().props;

    const handleChange = (value: string) => {
        router.post('/manage/set-project', { project_id: parseInt(value) }, { preserveScroll: true });
    };

    return (
        <Select value={String(selectedProjectId)} onValueChange={handleChange}>
            <SelectTrigger className="w-full">
                <SelectValue placeholder="Select project" />
            </SelectTrigger>
            <SelectContent>
                {projects.map((project) => (
                    <SelectItem key={project.id} value={String(project.id)}>
                        {project.name}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
