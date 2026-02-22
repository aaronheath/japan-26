import { revert, store, update } from '@/actions/App/Http/Controllers/Manage/PromptController';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Transition } from '@headlessui/react';
import { Form, Head, router } from '@inertiajs/react';
import { ChevronDown, History, Pencil, Plus, RotateCcw } from 'lucide-react';
import { useState } from 'react';

interface PromptVersion {
    id: number;
    version: number;
    content: string;
    change_notes: string | null;
    created_at: string;
}

interface Prompt {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    type: 'system' | 'task';
    system_prompt_id: number | null;
    active_version_id: number | null;
    active_version: PromptVersion | null;
    system_prompt: { id: number; name: string } | null;
    versions: PromptVersion[];
    versions_count: number;
}

interface SystemPromptOption {
    id: number;
    name: string;
}

interface PromptsProps {
    prompts: Prompt[];
    systemPrompts: SystemPromptOption[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Prompts',
        href: '/manage/prompts',
    },
];

export default function Prompts({ prompts, systemPrompts }: PromptsProps) {
    const [editOpen, setEditOpen] = useState(false);
    const [editPrompt, setEditPrompt] = useState<Prompt | null>(null);
    const [editContent, setEditContent] = useState('');
    const [editChangeNotes, setEditChangeNotes] = useState('');
    const [editErrors, setEditErrors] = useState<Record<string, string>>({});

    const [createOpen, setCreateOpen] = useState(false);

    const [historyOpen, setHistoryOpen] = useState<Record<number, boolean>>({});

    const systemPromptsList = prompts.filter((p) => p.type === 'system');
    const taskPromptsList = prompts.filter((p) => p.type === 'task');

    const openEdit = (prompt: Prompt) => {
        setEditPrompt(prompt);
        setEditContent(prompt.active_version?.content ?? '');
        setEditChangeNotes('');
        setEditErrors({});
        setEditOpen(true);
    };

    const handleUpdate = (e: React.FormEvent) => {
        e.preventDefault();

        if (!editPrompt) {
            return;
        }

        router.put(
            update.url(editPrompt.id),
            { content: editContent, change_notes: editChangeNotes || null },
            {
                preserveScroll: true,
                onSuccess: () => setEditOpen(false),
                onError: (errors) => setEditErrors(errors),
            },
        );
    };

    const handleRevert = (promptId: number, versionId: number) => {
        router.post(
            revert.url(promptId),
            { version_id: versionId },
            { preserveScroll: true },
        );
    };

    const toggleHistory = (promptId: number) => {
        setHistoryOpen((prev) => ({ ...prev, [promptId]: !prev[promptId] }));
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('en-AU', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const renderPromptCard = (prompt: Prompt) => (
        <div key={prompt.id} className="rounded-md border p-4">
            <div className="flex items-start justify-between">
                <div className="space-y-1">
                    <div className="flex items-center gap-2">
                        <h4 className="font-medium">{prompt.name}</h4>
                        <Badge variant={prompt.type === 'system' ? 'default' : 'secondary'}>{prompt.type}</Badge>
                        {prompt.active_version && (
                            <span className="text-xs text-muted-foreground">v{prompt.active_version.version}</span>
                        )}
                    </div>

                    <p className="text-xs text-muted-foreground font-mono">{prompt.slug}</p>

                    {prompt.description && <p className="text-sm text-muted-foreground">{prompt.description}</p>}

                    {prompt.system_prompt && (
                        <p className="text-xs text-muted-foreground">
                            System prompt: {prompt.system_prompt.name}
                        </p>
                    )}
                </div>

                <div className="flex gap-1">
                    <Button variant="ghost" size="sm" onClick={() => openEdit(prompt)} title="Edit prompt">
                        <Pencil className="h-4 w-4" />
                    </Button>

                    {prompt.versions_count > 1 && (
                        <Button variant="ghost" size="sm" onClick={() => toggleHistory(prompt.id)} title="Version history">
                            <History className="h-4 w-4" />
                        </Button>
                    )}
                </div>
            </div>

            {historyOpen[prompt.id] && prompt.versions.length > 0 && (
                <div className="mt-3 space-y-2 border-t pt-3">
                    <h5 className="text-sm font-medium">Version History</h5>

                    <div className="space-y-1">
                        {prompt.versions.map((version) => (
                            <div
                                key={version.id}
                                className={`flex items-center justify-between rounded-sm px-2 py-1.5 text-sm ${
                                    version.id === prompt.active_version_id ? 'bg-muted' : ''
                                }`}
                            >
                                <div className="flex items-center gap-2">
                                    <span className="font-mono text-xs">v{version.version}</span>

                                    {version.id === prompt.active_version_id && (
                                        <Badge variant="outline" className="text-xs">active</Badge>
                                    )}

                                    {version.change_notes && (
                                        <span className="text-muted-foreground">{version.change_notes}</span>
                                    )}

                                    <span className="text-xs text-muted-foreground">{formatDate(version.created_at)}</span>
                                </div>

                                {version.id !== prompt.active_version_id && (
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => handleRevert(prompt.id, version.id)}
                                        title="Revert to this version"
                                    >
                                        <RotateCcw className="h-3 w-3" />
                                    </Button>
                                )}
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );

    const clearCreateForm = () => {
        ['create-name', 'create-slug', 'create-content', 'create-description'].forEach((id) => {
            const el = document.getElementById(id) as HTMLInputElement | HTMLTextAreaElement;

            if (el) {
                el.value = '';
            }
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Prompts" />

            <div className="px-4 py-6">
                <div className="mx-auto max-w-4xl space-y-6">
                    <HeadingSmall title="Prompts" description="Manage LLM prompt templates used for AI generation" />

                    <Collapsible>
                        <CollapsibleTrigger asChild>
                            <Button variant="outline" size="sm">
                                <Plus className="mr-1 h-4 w-4" />
                                New Prompt
                                <ChevronDown className="ml-1 h-4 w-4" />
                            </Button>
                        </CollapsibleTrigger>

                        <CollapsibleContent>
                            <Form
                                action={store.url()}
                                method="post"
                                options={{ preserveScroll: true, onSuccess: clearCreateForm }}
                                className="mt-4 space-y-4 rounded-md border p-4"
                            >
                                {({ processing, recentlySuccessful, errors }) => (
                                    <>
                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="grid gap-2">
                                                <Label htmlFor="create-name">Name</Label>
                                                <Input id="create-name" type="text" name="name" placeholder="e.g. City Sightseeing" required />
                                                <InputError message={errors.name} />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="create-slug">Slug</Label>
                                                <Input id="create-slug" type="text" name="slug" placeholder="e.g. city-sightseeing" required />
                                                <InputError message={errors.slug} />
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="grid gap-2">
                                                <Label htmlFor="create-type">Type</Label>
                                                <Select name="type" required>
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Select type" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="system">System</SelectItem>
                                                        <SelectItem value="task">Task</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                                <InputError message={errors.type} />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="create-system-prompt">System Prompt</Label>
                                                <Select name="system_prompt_id">
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="None (for task prompts)" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {systemPrompts.map((sp) => (
                                                            <SelectItem key={sp.id} value={sp.id.toString()}>
                                                                {sp.name}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                                <InputError message={errors.system_prompt_id} />
                                            </div>
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="create-description">Description</Label>
                                            <Input id="create-description" type="text" name="description" placeholder="What does this prompt do?" />
                                            <InputError message={errors.description} />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="create-content">Content</Label>
                                            <textarea
                                                id="create-content"
                                                name="content"
                                                rows={10}
                                                placeholder="Blade template content..."
                                                required
                                                className="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-md border bg-transparent px-3 py-2 font-mono text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                                            />
                                            <InputError message={errors.content} />
                                        </div>

                                        <div className="flex items-center gap-4">
                                            <Button type="submit" disabled={processing}>
                                                Create Prompt
                                            </Button>

                                            <Transition
                                                show={recentlySuccessful}
                                                enter="transition ease-in-out"
                                                enterFrom="opacity-0"
                                                leave="transition ease-in-out"
                                                leaveTo="opacity-0"
                                            >
                                                <p className="text-sm text-green-600">Prompt created.</p>
                                            </Transition>
                                        </div>
                                    </>
                                )}
                            </Form>
                        </CollapsibleContent>
                    </Collapsible>

                    {systemPromptsList.length > 0 && (
                        <div className="space-y-3">
                            <h4 className="text-sm font-medium">System Prompts</h4>

                            <div className="space-y-3">
                                {systemPromptsList.map((prompt) => {
                                    const linkedTasks = taskPromptsList.filter((t) => t.system_prompt_id === prompt.id);

                                    return (
                                        <div key={prompt.id} className="space-y-2">
                                            {renderPromptCard(prompt)}

                                            {linkedTasks.length > 0 && (
                                                <div className="ml-6 space-y-2 border-l-2 border-muted pl-4">
                                                    {linkedTasks.map((task) => renderPromptCard(task))}
                                                </div>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    )}

                    {taskPromptsList.filter((t) => !t.system_prompt_id).length > 0 && (
                        <div className="space-y-3">
                            <h4 className="text-sm font-medium">Unlinked Task Prompts</h4>

                            <div className="space-y-2">
                                {taskPromptsList
                                    .filter((t) => !t.system_prompt_id)
                                    .map((prompt) => renderPromptCard(prompt))}
                            </div>
                        </div>
                    )}

                    {prompts.length === 0 && (
                        <p className="text-sm text-muted-foreground">No prompts have been created yet.</p>
                    )}
                </div>
            </div>

            <Dialog open={editOpen} onOpenChange={setEditOpen}>
                <DialogContent className="top-4 right-4 bottom-4 left-4 flex max-h-none max-w-[1100px] translate-x-0 translate-y-0 flex-col mx-auto sm:max-w-[1100px] [&>*:not([data-slot=dialog-close])]:w-full">
                    <DialogHeader>
                        <DialogTitle>
                            Edit Prompt: {editPrompt?.name}
                            {editPrompt?.active_version && (
                                <span className="ml-2 text-sm font-normal text-muted-foreground">
                                    (creating v{(editPrompt.active_version.version ?? 0) + 1})
                                </span>
                            )}
                        </DialogTitle>
                    </DialogHeader>

                    <form onSubmit={handleUpdate} className="flex min-h-0 flex-1 flex-col gap-4">
                        <div className="flex min-h-0 flex-1 flex-col gap-2">
                            <Label htmlFor="edit-content">Content</Label>
                            <textarea
                                id="edit-content"
                                value={editContent}
                                onChange={(e) => setEditContent(e.target.value)}
                                required
                                className="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 min-h-0 flex-1 rounded-md border bg-transparent px-3 py-2 font-mono text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                            />
                            <InputError message={editErrors.content} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="edit-change-notes">Change Notes (optional)</Label>
                            <Input
                                id="edit-change-notes"
                                type="text"
                                value={editChangeNotes}
                                onChange={(e) => setEditChangeNotes(e.target.value)}
                                placeholder="What changed in this version?"
                            />
                            <InputError message={editErrors.change_notes} />
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setEditOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit">Save New Version</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
