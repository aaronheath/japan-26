import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { getApiHeaders } from '@/lib/utils';
import { RefreshCw } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

interface RegenerationConfirmDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    projectId: number;
    type: 'column' | 'project';
    columnType?: string;
    totalItems: number;
}

export function RegenerationConfirmDialog({
    open,
    onOpenChange,
    projectId,
    type,
    columnType,
    totalItems,
}: RegenerationConfirmDialogProps) {
    const [isLoading, setIsLoading] = useState(false);

    const getTitle = () => {
        if (type === 'project') {
            return 'Regenerate Entire Project';
        }

        return `Regenerate All ${columnType?.charAt(0).toUpperCase()}${columnType?.slice(1)} Items`;
    };

    const getDescription = () => {
        if (type === 'project') {
            return `This will regenerate LLM content for all ${totalItems} items in the project. This may take a while.`;
        }

        return `This will regenerate LLM content for all ${totalItems} ${columnType} items. This may take a while.`;
    };

    const handleConfirm = async () => {
        setIsLoading(true);

        try {
            let url = '';
            let body: Record<string, unknown> = {};

            if (type === 'column') {
                url = `/api/regeneration/project/${projectId}/column`;
                body = { type: columnType };
            } else {
                url = `/api/regeneration/project/${projectId}`;
            }

            const response = await fetch(url, {
                method: 'POST',
                headers: getApiHeaders(),
                body: Object.keys(body).length > 0 ? JSON.stringify(body) : undefined,
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('Failed to start regeneration');
            }

            const data = await response.json();

            toast.success('Regeneration started', {
                description: `Processing ${data.total_jobs} job${data.total_jobs > 1 ? 's' : ''}`,
            });

            onOpenChange(false);
        } catch {
            toast.error('Failed to start regeneration');
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{getTitle()}</DialogTitle>
                    <DialogDescription>{getDescription()}</DialogDescription>
                </DialogHeader>

                <DialogFooter>
                    <Button variant="outline" onClick={() => onOpenChange(false)} disabled={isLoading}>
                        Cancel
                    </Button>
                    <Button onClick={handleConfirm} disabled={isLoading}>
                        <RefreshCw className={`mr-2 size-4 ${isLoading ? 'animate-spin' : ''}`} />
                        Regenerate
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
