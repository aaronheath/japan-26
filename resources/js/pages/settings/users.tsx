import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import UserManagementController from '@/actions/App/Http/Controllers/Settings/UserManagementController';
import { index } from '@/routes/users';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Transition } from '@headlessui/react';
import { Form, Head, router, usePage } from '@inertiajs/react';
import { Copy, Trash2 } from 'lucide-react';
import { useState } from 'react';

interface ManagedUser {
    id: number;
    name: string;
    email: string;
    auth_type: 'password' | 'google';
}

interface UsersProps {
    users: ManagedUser[];
}

interface FlashData {
    generated_password?: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: index().url,
    },
];

export default function Users({ users }: UsersProps) {
    const { auth } = usePage<SharedData & { flash: FlashData }>().props;
    const [showPasswordModal, setShowPasswordModal] = useState(false);
    const [generatedPassword, setGeneratedPassword] = useState<string | null>(null);
    const [copied, setCopied] = useState(false);
    const [authType, setAuthType] = useState<'password' | 'google'>('password');

    const handleDelete = (userId: number) => {
        if (userId === auth.user.id) {
            alert('You cannot delete your own account from this page.');
            return;
        }
        if (confirm('Are you sure you want to delete this user?')) {
            router.delete(`/settings/users/${userId}`);
        }
    };

    const handleCopy = async () => {
        if (generatedPassword) {
            await navigator.clipboard.writeText(generatedPassword);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="User Management" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="User Management"
                        description="Create and manage user accounts"
                    />

                    <Form
                        {...UserManagementController.store.form()}
                        options={{
                            preserveScroll: true,
                            onSuccess: (page) => {
                                const input = document.getElementById('email') as HTMLInputElement;
                                if (input) {
                                    input.value = '';
                                }
                                const flashData = page.props.flash as FlashData | undefined;
                                if (flashData?.generated_password) {
                                    setGeneratedPassword(flashData.generated_password);
                                    setShowPasswordModal(true);
                                }
                            },
                        }}
                        className="space-y-4"
                    >
                        {({ processing, recentlySuccessful, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email address</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        name="email"
                                        placeholder="user@example.com"
                                        required
                                    />
                                    <InputError message={errors.email} />
                                </div>

                                <div className="grid gap-2">
                                    <Label>Authentication type</Label>
                                    <div className="flex gap-4">
                                        <label className="flex items-center gap-2">
                                            <input
                                                type="radio"
                                                name="auth_type"
                                                value="password"
                                                checked={authType === 'password'}
                                                onChange={() => setAuthType('password')}
                                                className="h-4 w-4"
                                            />
                                            <span className="text-sm">Password</span>
                                        </label>
                                        <label className="flex items-center gap-2">
                                            <input
                                                type="radio"
                                                name="auth_type"
                                                value="google"
                                                checked={authType === 'google'}
                                                onChange={() => setAuthType('google')}
                                                className="h-4 w-4"
                                            />
                                            <span className="text-sm">Google</span>
                                        </label>
                                    </div>
                                    <p className="text-xs text-muted-foreground">
                                        {authType === 'password'
                                            ? 'A random password will be generated and displayed once.'
                                            : 'User will sign in via Google OAuth. Email will be auto-whitelisted.'}
                                    </p>
                                    <InputError message={errors.auth_type} />
                                </div>

                                <Button type="submit" disabled={processing}>
                                    Create User
                                </Button>

                                <Transition
                                    show={recentlySuccessful && !showPasswordModal}
                                    enter="transition ease-in-out"
                                    enterFrom="opacity-0"
                                    leave="transition ease-in-out"
                                    leaveTo="opacity-0"
                                >
                                    <p className="text-sm text-green-600">
                                        User created successfully.
                                    </p>
                                </Transition>
                            </>
                        )}
                    </Form>

                    <div className="space-y-2">
                        <h4 className="text-sm font-medium">Current users</h4>
                        {users.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No users have been created yet.
                            </p>
                        ) : (
                            <ul className="divide-y divide-border rounded-md border">
                                {users.map((user) => (
                                    <li
                                        key={user.id}
                                        className="flex items-center justify-between px-4 py-3"
                                    >
                                        <div className="flex flex-col gap-1">
                                            <div className="flex items-center gap-2">
                                                <span className="text-sm font-medium">
                                                    {user.name}
                                                </span>
                                                <Badge
                                                    variant={
                                                        user.auth_type === 'google'
                                                            ? 'secondary'
                                                            : 'outline'
                                                    }
                                                    className="text-[10px] px-1.5 py-0"
                                                >
                                                    {user.auth_type === 'google'
                                                        ? 'Google'
                                                        : 'Password'}
                                                </Badge>
                                            </div>
                                            <span className="text-xs text-muted-foreground">
                                                {user.email}
                                            </span>
                                        </div>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => handleDelete(user.id)}
                                            disabled={user.id === auth.user.id}
                                            className="text-destructive hover:text-destructive disabled:opacity-50"
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </div>
            </SettingsLayout>

            <Dialog open={showPasswordModal} onOpenChange={setShowPasswordModal}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>User Created</DialogTitle>
                        <DialogDescription>
                            Save this password now. It will only be shown once.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div className="flex items-center gap-2">
                            <code className="flex-1 rounded bg-muted px-3 py-2 font-mono text-sm">
                                {generatedPassword}
                            </code>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={handleCopy}
                            >
                                <Copy className="h-4 w-4" />
                            </Button>
                        </div>
                        {copied && (
                            <p className="text-sm text-green-600">
                                Copied to clipboard!
                            </p>
                        )}
                        <p className="text-sm text-destructive">
                            This password will not be shown again. Please save it
                            securely.
                        </p>
                    </div>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
