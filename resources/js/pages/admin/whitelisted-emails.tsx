import WhitelistedEmailController from '@/actions/App/Http/Controllers/Admin/WhitelistedEmailController';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/admin/whitelisted-emails';
import { type BreadcrumbItem } from '@/types';
import { Transition } from '@headlessui/react';
import { Form, Head, router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';

interface WhitelistedEmail {
    id: number;
    email: string;
}

interface WhitelistedEmailsProps {
    emails: WhitelistedEmail[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Whitelisted Emails',
        href: index().url,
    },
];

export default function WhitelistedEmails({ emails }: WhitelistedEmailsProps) {
    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to remove this email from the whitelist?')) {
            router.delete(`/admin/whitelisted-emails/${id}`);
        }
    };

    const clearEmailInput = () => {
        const input = document.getElementById('email') as HTMLInputElement;

        if (input) {
            input.value = '';
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Whitelisted Emails" />

            <div className="px-4 py-6">
                <div className="mx-auto max-w-xl space-y-6">
                    <HeadingSmall
                        title="Whitelisted Emails"
                        description="Manage email addresses that can sign in with Google OAuth"
                    />

                    <Form
                        {...WhitelistedEmailController.store.form()}
                        options={{ preserveScroll: true, onSuccess: clearEmailInput }}
                        className="space-y-4"
                    >
                        {({ processing, recentlySuccessful, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email address</Label>
                                    <div className="flex gap-2">
                                        <Input
                                            id="email"
                                            type="email"
                                            name="email"
                                            placeholder="user@example.com"
                                            className="flex-1"
                                            required
                                        />
                                        <Button type="submit" disabled={processing}>
                                            Add
                                        </Button>
                                    </div>
                                    <InputError message={errors.email} />
                                </div>

                                <Transition
                                    show={recentlySuccessful}
                                    enter="transition ease-in-out"
                                    enterFrom="opacity-0"
                                    leave="transition ease-in-out"
                                    leaveTo="opacity-0"
                                >
                                    <p className="text-sm text-green-600">
                                        Email added to whitelist.
                                    </p>
                                </Transition>
                            </>
                        )}
                    </Form>

                    <div className="space-y-2">
                        <h4 className="text-sm font-medium">Current whitelist</h4>
                        {emails.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                No emails have been whitelisted yet.
                            </p>
                        ) : (
                            <ul className="divide-border divide-y rounded-md border">
                                {emails.map((email) => (
                                    <li
                                        key={email.id}
                                        className="flex items-center justify-between px-4 py-3"
                                    >
                                        <span className="text-sm">{email.email}</span>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => handleDelete(email.id)}
                                            className="text-destructive hover:text-destructive"
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
