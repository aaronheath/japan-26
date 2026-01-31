import { formatLocalDateTime } from '@/lib/utils';

interface LlmMetadataProps {
    llmCall: {
        id: number;
        llm_provider_name: string;
        created_at: string;
    } | null;
}

export function LlmMetadata({ llmCall }: LlmMetadataProps) {
    if (!llmCall) {
        return null;
    }

    return (
        <p className="text-right text-sm text-muted-foreground">
            Generated using <span className="font-bold">{llmCall.llm_provider_name}</span> on{' '}
            <span className="font-bold">{formatLocalDateTime(llmCall.created_at)}</span> (
            <span className="font-bold">ID: {llmCall.id}</span>)
        </p>
    );
}
