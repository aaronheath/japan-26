import UserManagementController from '@/actions/App/Http/Controllers/Admin/UserManagementController';
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
import { index } from '@/routes/admin/users';
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
    const [copyFailed, setCopyFailed] = useState(false);
    const [authType, setAuthType] = useState<'password' | 'google'>('password');

    const handleDelete = (userId: number) => {
        if (userId === auth.user.id) {
            alert('You cannot delete your own account from this page.');
            return;
        }
        if (confirm('Are you sure you want to delete this user?')) {
            router.delete(`/admin/users/${userId}`);
        }
    };

    const handleCopy = async () => {
        if (!generatedPassword) {
            return;
        }

        setCopyFailed(false);

        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(generatedPassword);
                setCopied(true);
                setTimeout(() => setCopied(false), 2000);
            } else {
                const textArea = document.createElement('textarea');
                textArea.value = generatedPassword;
                textArea.setAttribute('readonly', '');
                textArea.style.position = 'absolute';
                textArea.style.left = '-9999px';
                textArea.style.top = `${window.pageYOffset || document.documentElement.scrollTop}px`;
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.setSelectionRange(0, generatedPassword.length);
                const success = document.execCommand('copy');
                document.body.removeChild(textArea);
                if (success) {
                    setCopied(true);
                    setTimeout(() => setCopied(false), 2000);
                } else {
                    setCopyFailed(true);
                }
            }
        } catch {
            setCopyFailed(true);
        }
    };

    const handleFormSuccess = (page: { props: { flash?: FlashData } }) => {
        const input = document.getElementById('email') as HTMLInputElement;
        if (input) {
            input.value = '';
        }
        const flashData = page.props.flash;
        if (flashData?.generated_password) {
            setGeneratedPassword(flashData.generated_password);
            setShowPasswordModal(true);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="User Management" />

            <div className="px-4 py-6">
                <div className="mx-auto max-w-xl space-y-6">
                    <HeadingSmall
                        title="User Management"
                        description="Create and manage user accounts"
                    />

                    <Form
                        {...UserManagementController.store.form()}
                        options={{ preserveScroll: true, onSuccess: handleFormSuccess }}
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
                                    <p className="text-muted-foreground text-xs">
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
                            <p className="text-muted-foreground text-sm">
                                No users have been created yet.
                            </p>
                        ) : (
                            <ul className="divide-border divide-y rounded-md border">
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
                                                    className="px-1.5 py-0 text-[10px]"
                                                >
                                                    {user.auth_type === 'google'
                                                        ? 'Google'
                                                        : 'Password'}
                                                </Badge>
                                            </div>
                                            <span className="text-muted-foreground text-xs">
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
            </div>

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
                            <Input
                                readOnly
                                value={generatedPassword || ''}
                                className="flex-1 font-mono text-sm"
                                onFocus={(e) => e.target.select()}
                            />
                            <Button variant="outline" size="sm" onClick={handleCopy}>
                                <Copy className="h-4 w-4" />
                            </Button>
                        </div>
                        {copied && (
                            <p className="text-sm text-green-600">Copied to clipboard!</p>
                        )}
                        {copyFailed && (
                            <p className="text-sm text-amber-600">
                                Auto-copy unavailable on HTTP. Please select the password
                                above and copy manually (Cmd+C / Ctrl+C).
                            </p>
                        )}
                        <p className="text-destructive text-sm">
                            This password will not be shown again. Please save it securely.
                        </p>
                    </div>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
