import CountryController from '@/actions/App/Http/Controllers/Manage/CountryController';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Transition } from '@headlessui/react';
import { Form, Head, router } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';

interface Country {
    id: number;
    name: string;
    states_count: number;
    cities_count: number;
}

interface CountriesProps {
    countries: Country[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Countries',
        href: '/manage/countries',
    },
];

export default function Countries({ countries }: CountriesProps) {
    const [editOpen, setEditOpen] = useState(false);
    const [editCountry, setEditCountry] = useState<Country | null>(null);
    const [editName, setEditName] = useState('');
    const [editErrors, setEditErrors] = useState<Record<string, string>>({});

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this country?')) {
            router.delete(CountryController.destroy.url(id), { preserveScroll: true });
        }
    };

    const openEdit = (country: Country) => {
        setEditCountry(country);
        setEditName(country.name);
        setEditErrors({});
        setEditOpen(true);
    };

    const handleUpdate = (e: React.FormEvent) => {
        e.preventDefault();

        if (!editCountry) {
            return;
        }

        router.put(
            CountryController.update.url(editCountry.id),
            { name: editName },
            {
                preserveScroll: true,
                onSuccess: () => setEditOpen(false),
                onError: (errors) => setEditErrors(errors),
            },
        );
    };

    const clearForm = () => {
        const input = document.getElementById('name') as HTMLInputElement;

        if (input) {
            input.value = '';
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Countries" />

            <div className="px-4 py-6">
                <div className="mx-auto max-w-5xl space-y-6">
                    <HeadingSmall title="Countries" description="Manage countries for your travel plans" />

                    <Form
                        action={CountryController.store.url()}
                        method="post"
                        options={{ preserveScroll: true, onSuccess: clearForm }}
                        className="space-y-4"
                    >
                        {({ processing, recentlySuccessful, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>
                                    <div className="flex gap-2">
                                        <Input
                                            id="name"
                                            type="text"
                                            name="name"
                                            placeholder="Country name"
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
                                    <p className="text-sm text-green-600">Country added.</p>
                                </Transition>
                            </>
                        )}
                    </Form>

                    <div className="space-y-2">
                        <h4 className="text-sm font-medium">Current countries</h4>
                        {countries.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No countries have been added yet.</p>
                        ) : (
                            <div className="divide-border overflow-hidden rounded-md border">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b bg-muted/50">
                                            <th className="px-4 py-2 text-left font-medium">Name</th>
                                            <th className="px-4 py-2 text-left font-medium">States</th>
                                            <th className="px-4 py-2 text-left font-medium">Cities</th>
                                            <th className="px-4 py-2 text-right font-medium">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-border">
                                        {countries.map((country) => (
                                            <tr key={country.id}>
                                                <td className="px-4 py-3">{country.name}</td>
                                                <td className="px-4 py-3">{country.states_count}</td>
                                                <td className="px-4 py-3">{country.cities_count}</td>
                                                <td className="px-4 py-3 text-right">
                                                    <div className="flex justify-end gap-1">
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => openEdit(country)}
                                                        >
                                                            <Pencil className="h-4 w-4" />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => handleDelete(country.id)}
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
                        <DialogTitle>Edit Country</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleUpdate} className="space-y-4">
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
