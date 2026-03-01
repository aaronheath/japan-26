import StateController from '@/actions/App/Http/Controllers/Manage/StateController';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Transition } from '@headlessui/react';
import { Form, Head, router } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';

interface State {
    id: number;
    name: string;
    country_id: number;
    country_name: string;
    cities_count: number;
}

interface Country {
    id: number;
    name: string;
}

interface StatesProps {
    states: State[];
    countries: Country[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'States',
        href: '/manage/states',
    },
];

export default function States({ states, countries }: StatesProps) {
    const [editOpen, setEditOpen] = useState(false);
    const [editState, setEditState] = useState<State | null>(null);
    const [editName, setEditName] = useState('');
    const [editCountryId, setEditCountryId] = useState('');
    const [editErrors, setEditErrors] = useState<Record<string, string>>({});

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this state?')) {
            router.delete(StateController.destroy.url(id), { preserveScroll: true });
        }
    };

    const openEdit = (state: State) => {
        setEditState(state);
        setEditName(state.name);
        setEditCountryId(String(state.country_id));
        setEditErrors({});
        setEditOpen(true);
    };

    const handleUpdate = (e: React.FormEvent) => {
        e.preventDefault();

        if (!editState) {
            return;
        }

        router.put(
            StateController.update.url(editState.id),
            { name: editName, country_id: editCountryId },
            {
                preserveScroll: true,
                onSuccess: () => setEditOpen(false),
                onError: (errors) => setEditErrors(errors),
            },
        );
    };

    const clearForm = () => {
        const nameInput = document.getElementById('name') as HTMLInputElement;
        const countrySelect = document.getElementById('country_id') as HTMLSelectElement;

        if (nameInput) {
            nameInput.value = '';
        }

        if (countrySelect) {
            countrySelect.value = '';
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="States" />

            <div className="px-4 py-6">
                <div className="mx-auto max-w-5xl space-y-6">
                    <HeadingSmall title="States" description="Manage states and prefectures for your travel plans" />

                    <Form
                        action={StateController.store.url()}
                        method="post"
                        options={{ preserveScroll: true, onSuccess: clearForm }}
                        className="space-y-4"
                    >
                        {({ processing, recentlySuccessful, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="country_id">Country</Label>
                                    <select
                                        id="country_id"
                                        name="country_id"
                                        required
                                        className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs outline-none"
                                    >
                                        <option value="">Select a country</option>
                                        {countries.map((country) => (
                                            <option key={country.id} value={country.id}>
                                                {country.name}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.country_id} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>
                                    <div className="flex gap-2">
                                        <Input
                                            id="name"
                                            type="text"
                                            name="name"
                                            placeholder="State name"
                                            className="flex-1"
                                            required
                                        />
                                        <Button type="submit" disabled={processing}>
                                            Add
                                        </Button>
                                    </div>
                                    <InputError message={errors.name} />
                                </div>

                                <Transition
                                    show={recentlySuccessful}
                                    enter="transition ease-in-out"
                                    enterFrom="opacity-0"
                                    leave="transition ease-in-out"
                                    leaveTo="opacity-0"
                                >
                                    <p className="text-sm text-green-600">State added.</p>
                                </Transition>
                            </>
                        )}
                    </Form>

                    <div className="space-y-2">
                        <h4 className="text-sm font-medium">Current states</h4>
                        {states.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No states have been added yet.</p>
                        ) : (
                            <div className="divide-border overflow-hidden rounded-md border">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b bg-muted/50">
                                            <th className="px-4 py-2 text-left font-medium">Name</th>
                                            <th className="px-4 py-2 text-left font-medium">Country</th>
                                            <th className="px-4 py-2 text-left font-medium">Cities</th>
                                            <th className="px-4 py-2 text-right font-medium">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-border">
                                        {states.map((state) => (
                                            <tr key={state.id}>
                                                <td className="px-4 py-3">{state.name}</td>
                                                <td className="px-4 py-3">{state.country_name}</td>
                                                <td className="px-4 py-3">{state.cities_count}</td>
                                                <td className="px-4 py-3 text-right">
                                                    <div className="flex justify-end gap-1">
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => openEdit(state)}
                                                        >
                                                            <Pencil className="h-4 w-4" />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => handleDelete(state.id)}
                                                            className="text-destructive hover:text-destructive"
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            <Dialog open={editOpen} onOpenChange={setEditOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Edit State</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleUpdate} className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="edit-country_id">Country</Label>
                            <Select value={editCountryId} onValueChange={setEditCountryId}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select a country" />
                                </SelectTrigger>
                                <SelectContent>
                                    {countries.map((country) => (
                                        <SelectItem key={country.id} value={String(country.id)}>
                                            {country.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={editErrors.country_id} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="edit-name">Name</Label>
                            <Input
                                id="edit-name"
                                type="text"
                                value={editName}
                                onChange={(e) => setEditName(e.target.value)}
                                required
                            />
                            <InputError message={editErrors.name} />
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setEditOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit">Save</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
