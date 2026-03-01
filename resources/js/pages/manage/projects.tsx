import ProjectManagementController from '@/actions/App/Http/Controllers/Manage/ProjectManagementController';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { formatDate } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Transition } from '@headlessui/react';
import { Form, Head, router } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import { type FormEvent, useState } from 'react';

interface ProjectItem {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
    versions_count: number;
    duration: number;
}

interface ProjectsProps {
    projects: ProjectItem[];
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Projects', href: '/manage/projects' }];

export default function Projects({ projects }: ProjectsProps) {
    const [editing, setEditing] = useState<ProjectItem | null>(null);
    const [editName, setEditName] = useState('');
    const [editStartDate, setEditStartDate] = useState('');
    const [editEndDate, setEditEndDate] = useState('');
    const [moveLastDays, setMoveLastDays] = useState(false);
    const [editErrors, setEditErrors] = useState<Record<string, string>>({});

    const openEdit = (project: ProjectItem) => {
        setEditing(project);
        setEditName(project.name);
        setEditStartDate(project.start_date);
        setEditEndDate(project.end_date);
        setMoveLastDays(false);
        setEditErrors({});
    };

    const handleUpdate = (e: FormEvent) => {
        e.preventDefault();

        if (!editing) {
            return;
        }

        router.put(
            `/manage/projects/${editing.id}`,
            { name: editName, start_date: editStartDate, end_date: editEndDate, move_last_days: moveLastDays },
            { preserveScroll: true, onSuccess: () => setEditing(null), onError: (errors) => setEditErrors(errors) },
        );
    };

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this project? All versions and days will be deleted.')) {
            router.delete(`/manage/projects/${id}`);
        }
    };

    const clearForm = () => {
        const inputs = ['name', 'start_date', 'end_date'] as const;

        inputs.forEach((id) => {
            const input = document.getElementById(id) as HTMLInputElement;

            if (input) {
                input.value = '';
            }
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Projects" />

            <div className="px-4 py-6">
                <div className="mx-auto max-w-5xl space-y-6">
                    <HeadingSmall title="Projects" description="Manage travel projects" />

                    <Form
                        {...ProjectManagementController.store.form()}
                        options={{ preserveScroll: true, onSuccess: clearForm }}
                        className="space-y-4"
                    >
                        {({ processing, recentlySuccessful, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>
                                    <Input id="name" name="name" required />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="start_date">Start date</Label>
                                        <Input id="start_date" type="date" name="start_date" required />
                                        <InputError message={errors.start_date} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="end_date">End date</Label>
                                        <Input id="end_date" type="date" name="end_date" required />
                                        <InputError message={errors.end_date} />
                                    </div>
                                </div>

                                <Button type="submit" disabled={processing}>
                                    Create Project
                                </Button>

                                <Transition
                                    show={recentlySuccessful}
                                    enter="transition ease-in-out"
                                    enterFrom="opacity-0"
                                    leave="transition ease-in-out"
                                    leaveTo="opacity-0"
                                >
                                    <p className="text-sm text-green-600">Project created.</p>
                                </Transition>
                            </>
                        )}
                    </Form>

                    <div className="space-y-2">
                        <h4 className="text-sm font-medium">Current projects</h4>
                        {projects.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No projects yet.</p>
                        ) : (
                            <ul className="divide-y divide-border rounded-md border">
                                {projects.map((project) => (
                                    <li key={project.id} className="flex items-center justify-between px-4 py-3">
                                        <div className="flex flex-col gap-1">
                                            <span className="text-sm font-medium">{project.name}</span>
                                            <span className="text-xs text-muted-foreground">
                                                {formatDate(project.start_date)} to {formatDate(project.end_date)} ({project.duration} days,{' '}
                                                {project.versions_count} version{project.versions_count !== 1 && 's'})
                                            </span>
                                        </div>
                                        <div className="flex gap-1">
                                            <Button variant="ghost" size="sm" onClick={() => openEdit(project)}>
                                                <Pencil className="h-4 w-4" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => handleDelete(project.id)}
                                                className="text-destructive hover:text-destructive"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </div>
            </div>

            <Dialog open={!!editing} onOpenChange={() => setEditing(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Edit Project</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleUpdate} className="space-y-4">
                        <div className="grid gap-2">
                            <Label>Name</Label>
                            <Input value={editName} onChange={(e) => setEditName(e.target.value)} required />
                            <InputError message={editErrors.name} />
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label>Start date</Label>
                                <Input
                                    type="date"
                                    value={editStartDate}
                                    onChange={(e) => setEditStartDate(e.target.value)}
                                    required
                                />
                                <InputError message={editErrors.start_date} />
                            </div>

                            <div className="grid gap-2">
                                <Label>End date</Label>
                                <Input
                                    type="date"
                                    value={editEndDate}
                                    onChange={(e) => setEditEndDate(e.target.value)}
                                    required
                                />
                                <InputError message={editErrors.end_date} />
                            </div>
                        </div>

                        {editing && (editStartDate !== editing.start_date || editEndDate !== editing.end_date) && (
                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="move_last_days"
                                    checked={moveLastDays}
                                    onCheckedChange={(checked) => setMoveLastDays(checked === true)}
                                />
                                <Label htmlFor="move_last_days" className="text-sm">
                                    Move last/second-last days to end of new date range
                                </Label>
                            </div>
                        )}

                        <Button type="submit">Save Changes</Button>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
