import { Button } from '@/components/ui/button';
import { triggerRegenerationStarted } from '@/hooks/use-regeneration-status';
import { getApiHeaders } from '@/lib/utils';
import { RefreshCw } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

interface RegenerateButtonProps {
    projectId: number;
    type: 'single' | 'day' | 'column' | 'project';
    itemType?: 'travel' | 'activity';
    itemId?: number;
    dayId?: number;
    columnType?: string;
    variant?: 'default' | 'ghost' | 'outline' | 'secondary' | 'destructive' | 'link';
    size?: 'default' | 'sm' | 'lg' | 'icon';
    children?: React.ReactNode;
}

export function RegenerateButton({
    projectId,
    type,
    itemType,
    itemId,
    dayId,
    columnType,
    variant = 'outline',
    size = 'sm',
    children,
}: RegenerateButtonProps) {
    const [isLoading, setIsLoading] = useState(false);

    const handleClick = async () => {
        setIsLoading(true);

        try {
            let url = '';
            let body: Record<string, unknown> = {};

            switch (type) {
                case 'single':
                    url = `/api/regeneration/project/${projectId}/single`;
                    body = { type: itemType, id: itemId };
                    break;
                case 'day':
                    url = `/api/regeneration/project/${projectId}/day/${dayId}`;
                    break;
                case 'column':
                    url = `/api/regeneration/project/${projectId}/column`;
                    body = { type: columnType };
                    break;
                case 'project':
                    url = `/api/regeneration/project/${projectId}`;
                    break;
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

            triggerRegenerationStarted();
        } catch {
            toast.error('Failed to start regeneration');
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <Button variant={variant} size={size} onClick={handleClick} disabled={isLoading}>
            <RefreshCw className={`size-4 ${isLoading ? 'animate-spin' : ''}`} />
            {children}
        </Button>
    );
}
