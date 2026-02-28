import AddressController from '@/actions/App/Http/Controllers/Manage/AddressController';
import AddressLookup from '@/components/address-lookup';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Transition } from '@headlessui/react';
import { Form, Head, router, usePage } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';

interface Address {
    id: number;
    line_1: string;
    line_2: string | null;
    line_3: string | null;
    postcode: string | null;
    latitude: number | null;
    longitude: number | null;
    country_id: number;
    state_id: number | null;
    city_id: number;
    country_name: string;
    state_name: string | null;
    city_name: string;
    attached_to: string | null;
}

interface Country {
    id: number;
    name: string;
}

interface State {
    id: number;
    name: string;
    country_id: number;
}

interface City {
    id: number;
    name: string;
}

interface AddressesProps {
    addresses: Address[];
    countries: Country[];
    states: State[];
    cities: City[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Addresses',
        href: '/manage/addresses',
    },
];

export default function Addresses({ addresses, countries: initialCountries, states: initialStates, cities: initialCities }: AddressesProps) {
    const { googleMapsApiKey } = usePage<SharedData>().props;

    const [countries, setCountries] = useState(initialCountries);
    const [states, setStates] = useState(initialStates);
    const [cities, setCities] = useState(initialCities);

    const [editOpen, setEditOpen] = useState(false);
    const [editAddress, setEditAddress] = useState<Address | null>(null);
    const [editCountryId, setEditCountryId] = useState('');
    const [editStateId, setEditStateId] = useState('');
    const [editCityId, setEditCityId] = useState('');
    const [editPostcode, setEditPostcode] = useState('');
    const [editLine1, setEditLine1] = useState('');
    const [editLine2, setEditLine2] = useState('');
    const [editLine3, setEditLine3] = useState('');
    const [editLatitude, setEditLatitude] = useState('');
    const [editLongitude, setEditLongitude] = useState('');
    const [editErrors, setEditErrors] = useState<Record<string, string>>({});

    const [createCountryId, setCreateCountryId] = useState('');
    const [createLatitude, setCreateLatitude] = useState('');
    const [createLongitude, setCreateLongitude] = useState('');

    const filteredStatesForCreate = useMemo(
        () => (createCountryId ? states.filter((s) => s.country_id === Number(createCountryId)) : states),
        [createCountryId, states],
    );

    const filteredStatesForEdit = useMemo(
        () => (editCountryId ? states.filter((s) => s.country_id === Number(editCountryId)) : states),
        [editCountryId, states],
    );

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this address?')) {
            router.delete(AddressController.destroy.url(id), { preserveScroll: true });
        }
    };

    const openEdit = (address: Address) => {
        setEditAddress(address);
        setEditCountryId(String(address.country_id));
        setEditStateId(address.state_id ? String(address.state_id) : '');
        setEditCityId(String(address.city_id));
        setEditPostcode(address.postcode ?? '');
        setEditLine1(address.line_1);
        setEditLine2(address.line_2 ?? '');
        setEditLine3(address.line_3 ?? '');
        setEditLatitude(address.latitude != null ? String(address.latitude) : '');
        setEditLongitude(address.longitude != null ? String(address.longitude) : '');
        setEditErrors({});
        setEditOpen(true);
    };

    const handleUpdate = (e: React.FormEvent) => {
        e.preventDefault();

        if (!editAddress) {
            return;
        }

        router.put(
            AddressController.update.url(editAddress.id),
            {
                country_id: editCountryId,
                state_id: editStateId || null,
                city_id: editCityId,
                postcode: editPostcode || null,
                line_1: editLine1,
                line_2: editLine2 || null,
                line_3: editLine3 || null,
                latitude: editLatitude || null,
                longitude: editLongitude || null,
            },
            {
                preserveScroll: true,
                onSuccess: () => setEditOpen(false),
                onError: (errors) => setEditErrors(errors),
            },
        );
    };

    const clearForm = () => {
        const fields = ['line_1', 'line_2', 'line_3', 'postcode'] as const;

        fields.forEach((field) => {
            const el = document.getElementById(field) as HTMLInputElement;

            if (el) {
                el.value = '';
            }
        });

        const selects = ['country_id', 'state_id', 'city_id'] as const;

        selects.forEach((field) => {
            const el = document.getElementById(field) as HTMLSelectElement;

            if (el) {
                el.value = '';
            }
        });

        setCreateCountryId('');
        setCreateLatitude('');
        setCreateLongitude('');
    };

    const handleCreateCountryChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
        setCreateCountryId(e.target.value);

        const stateSelect = document.getElementById('state_id') as HTMLSelectElement;

        if (stateSelect) {
            stateSelect.value = '';
        }
    };

    const handleEditCountryChange = (value: string) => {
        setEditCountryId(value);
        setEditStateId('');
    };

    const mergeGeoRecords = (details: {
        country: { id: number; name: string } | null;
        state: { id: number; name: string; country_id: number } | null;
        city: { id: number; name: string } | null;
    }) => {
        if (details.country && !countries.find((c) => c.id === details.country!.id)) {
            setCountries((prev) => [...prev, details.country!].sort((a, b) => a.name.localeCompare(b.name)));
        }

        if (details.state && !states.find((s) => s.id === details.state!.id)) {
            setStates((prev) => [...prev, details.state!].sort((a, b) => a.name.localeCompare(b.name)));
        }

        if (details.city && !cities.find((c) => c.id === details.city!.id)) {
            setCities((prev) => [...prev, details.city!].sort((a, b) => a.name.localeCompare(b.name)));
        }
    };

    const handleCreateLookupSelect = (details: {
        line_1: string;
        line_2: string | null;
        line_3: string | null;
        postcode: string | null;
        latitude: number | null;
        longitude: number | null;
        country_id: number | null;
        state_id: number | null;
        city_id: number | null;
        country: { id: number; name: string } | null;
        state: { id: number; name: string; country_id: number } | null;
        city: { id: number; name: string } | null;
    }) => {
        mergeGeoRecords(details);

        const setNativeValue = (id: string, value: string) => {
            const el = document.getElementById(id) as HTMLInputElement | HTMLSelectElement | null;

            if (el) {
                const nativeInputValueSetter = Object.getOwnPropertyDescriptor(
                    id.includes('_id') ? HTMLSelectElement.prototype : HTMLInputElement.prototype,
                    'value',
                )?.set;

                nativeInputValueSetter?.call(el, value);
                el.dispatchEvent(new Event('input', { bubbles: true }));
                el.dispatchEvent(new Event('change', { bubbles: true }));
            }
        };

        if (details.country_id) {
            setCreateCountryId(String(details.country_id));
            setNativeValue('country_id', String(details.country_id));
        }

        setTimeout(() => {
            if (details.state_id) {
                setNativeValue('state_id', String(details.state_id));
            }

            if (details.city_id) {
                setNativeValue('city_id', String(details.city_id));
            }

            setNativeValue('postcode', details.postcode ?? '');
            setNativeValue('line_1', details.line_1 ?? '');
            setNativeValue('line_2', details.line_2 ?? '');
            setNativeValue('line_3', details.line_3 ?? '');
            setCreateLatitude(details.latitude != null ? String(details.latitude) : '');
            setCreateLongitude(details.longitude != null ? String(details.longitude) : '');
        }, 0);
    };

    const handleEditLookupSelect = (details: {
        line_1: string;
        line_2: string | null;
        line_3: string | null;
        postcode: string | null;
        latitude: number | null;
        longitude: number | null;
        country_id: number | null;
        state_id: number | null;
        city_id: number | null;
        country: { id: number; name: string } | null;
        state: { id: number; name: string; country_id: number } | null;
        city: { id: number; name: string } | null;
    }) => {
        mergeGeoRecords(details);

        if (details.country_id) {
            setEditCountryId(String(details.country_id));
        }

        if (details.state_id) {
            setEditStateId(String(details.state_id));
        }

        if (details.city_id) {
            setEditCityId(String(details.city_id));
        }

        setEditPostcode(details.postcode ?? '');
        setEditLine1(details.line_1 ?? '');
        setEditLine2(details.line_2 ?? '');
        setEditLine3(details.line_3 ?? '');
        setEditLatitude(details.latitude != null ? String(details.latitude) : '');
        setEditLongitude(details.longitude != null ? String(details.longitude) : '');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Addresses" />

            <div className="px-4 py-6">
                <div className="mx-auto max-w-2xl space-y-6">
                    <HeadingSmall title="Addresses" description="Manage addresses for your travel plans" />

                    <div className="space-y-2">
                        <h4 className="text-sm font-medium">Address Lookup</h4>
                        <AddressLookup onSelect={handleCreateLookupSelect} />
                        <p className="text-muted-foreground text-xs">Search for an address to auto-fill the form below</p>
                    </div>

                    <Form
                        action={AddressController.store.url()}
                        method="post"
                        options={{ preserveScroll: true, onSuccess: clearForm }}
                        className="space-y-4"
                    >
                        {({ processing, recentlySuccessful, errors }) => (
                            <>
                                <div className="grid grid-cols-3 gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="country_id">Country</Label>
                                        <select
                                            id="country_id"
                                            name="country_id"
                                            required
                                            onChange={handleCreateCountryChange}
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
                                        <Label htmlFor="state_id">State</Label>
                                        <select
                                            id="state_id"
                                            name="state_id"
                                            className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs outline-none"
                                        >
                                            <option value="">Select a state (optional)</option>
                                            {filteredStatesForCreate.map((state) => (
                                                <option key={state.id} value={state.id}>
                                                    {state.name}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.state_id} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="city_id">City</Label>
                                        <select
                                            id="city_id"
                                            name="city_id"
                                            required
                                            className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs outline-none"
                                        >
                                            <option value="">Select a city</option>
                                            {cities.map((city) => (
                                                <option key={city.id} value={city.id}>
                                                    {city.name}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.city_id} />
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="postcode">Postcode</Label>
                                    <Input id="postcode" type="text" name="postcode" placeholder="Postcode" />
                                    <InputError message={errors.postcode} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="line_1">Address Line 1</Label>
                                    <Input
                                        id="line_1"
                                        type="text"
                                        name="line_1"
                                        placeholder="Address line 1"
                                        required
                                    />
                                    <InputError message={errors.line_1} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="line_2">Address Line 2</Label>
                                    <Input
                                        id="line_2"
                                        type="text"
                                        name="line_2"
                                        placeholder="Address line 2 (optional)"
                                    />
                                    <InputError message={errors.line_2} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="line_3">Address Line 3</Label>
                                    <Input
                                        id="line_3"
                                        type="text"
                                        name="line_3"
                                        placeholder="Address line 3 (optional)"
                                    />
                                    <InputError message={errors.line_3} />
                                </div>

                                <input type="hidden" name="latitude" value={createLatitude} />
                                <input type="hidden" name="longitude" value={createLongitude} />

                                <Button type="submit" disabled={processing}>
                                    Add Address
                                </Button>

                                <Transition
                                    show={recentlySuccessful}
                                    enter="transition ease-in-out"
                                    enterFrom="opacity-0"
                                    leave="transition ease-in-out"
                                    leaveTo="opacity-0"
                                >
                                    <p className="text-sm text-green-600">Address added.</p>
                                </Transition>
                            </>
                        )}
                    </Form>

                    <div className="space-y-2">
                        <h4 className="text-sm font-medium">Current addresses</h4>
                        {addresses.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No addresses have been added yet.</p>
                        ) : (
                            <div className="divide-border overflow-hidden rounded-md border">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b bg-muted/50">
                                            <th className="px-4 py-2 text-left font-medium">Line 1</th>
                                            <th className="px-4 py-2 text-left font-medium">City</th>
                                            <th className="px-4 py-2 text-left font-medium">State</th>
                                            <th className="px-4 py-2 text-left font-medium">Country</th>
                                            <th className="px-4 py-2 text-left font-medium">Postcode</th>
                                            <th className="px-4 py-2 text-left font-medium">Attached To</th>
                                            <th className="px-4 py-2 text-right font-medium">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-border">
                                        {addresses.map((address) => (
                                            <tr key={address.id}>
                                                <td className="px-4 py-3">{address.line_1}</td>
                                                <td className="px-4 py-3">{address.city_name}</td>
                                                <td className="px-4 py-3">{address.state_name ?? '-'}</td>
                                                <td className="px-4 py-3">{address.country_name}</td>
                                                <td className="px-4 py-3">{address.postcode ?? '-'}</td>
                                                <td className="px-4 py-3">{address.attached_to ?? '-'}</td>
                                                <td className="px-4 py-3 text-right">
                                                    <div className="flex justify-end gap-1">
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => openEdit(address)}
                                                        >
                                                            <Pencil className="h-4 w-4" />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => handleDelete(address.id)}
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
                        <DialogTitle>Edit Address</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleUpdate} className="space-y-4">
                        <AddressLookup onSelect={handleEditLookupSelect} />

                        <div className="grid grid-cols-3 gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="edit-country_id">Country</Label>
                                <Select value={editCountryId} onValueChange={handleEditCountryChange}>
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
                                <Label htmlFor="edit-state_id">State</Label>
                                <Select value={editStateId} onValueChange={setEditStateId}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select a state (optional)" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {filteredStatesForEdit.map((state) => (
                                            <SelectItem key={state.id} value={String(state.id)}>
                                                {state.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={editErrors.state_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="edit-city_id">City</Label>
                                <Select value={editCityId} onValueChange={setEditCityId}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select a city" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {cities.map((city) => (
                                            <SelectItem key={city.id} value={String(city.id)}>
                                                {city.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={editErrors.city_id} />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="edit-postcode">Postcode</Label>
                            <Input
                                id="edit-postcode"
                                type="text"
                                value={editPostcode}
                                onChange={(e) => setEditPostcode(e.target.value)}
                                placeholder="Postcode"
                            />
                            <InputError message={editErrors.postcode} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="edit-line_1">Address Line 1</Label>
                            <Input
                                id="edit-line_1"
                                type="text"
                                value={editLine1}
                                onChange={(e) => setEditLine1(e.target.value)}
                                required
                            />
                            <InputError message={editErrors.line_1} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="edit-line_2">Address Line 2</Label>
                            <Input
                                id="edit-line_2"
                                type="text"
                                value={editLine2}
                                onChange={(e) => setEditLine2(e.target.value)}
                                placeholder="Optional"
                            />
                            <InputError message={editErrors.line_2} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="edit-line_3">Address Line 3</Label>
                            <Input
                                id="edit-line_3"
                                type="text"
                                value={editLine3}
                                onChange={(e) => setEditLine3(e.target.value)}
                                placeholder="Optional"
                            />
                            <InputError message={editErrors.line_3} />
                        </div>

                        {(editLatitude || editLongitude) && (
                            <>
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label>Latitude</Label>
                                        <p className="text-sm text-muted-foreground">{editLatitude || '—'}</p>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Longitude</Label>
                                        <p className="text-sm text-muted-foreground">{editLongitude || '—'}</p>
                                    </div>
                                </div>

                                {googleMapsApiKey && editLatitude && editLongitude && (
                                    <iframe
                                        className="h-[200px] w-full rounded-md border-0"
                                        loading="lazy"
                                        referrerPolicy="no-referrer-when-downgrade"
                                        src={`https://www.google.com/maps/embed/v1/place?key=${googleMapsApiKey}&q=${editLatitude},${editLongitude}&zoom=15`}
                                    />
                                )}
                            </>
                        )}

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
