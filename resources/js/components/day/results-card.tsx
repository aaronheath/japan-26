import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Markdown } from '@/components/ui/markdown';

interface ResultsCardProps {
    title: React.ReactNode;
    subtitle?: string;
    response?: string | null;
}

export function ResultsCard({ title, subtitle, response }: ResultsCardProps) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>{title}</CardTitle>
                {subtitle && <p className="text-sm text-muted-foreground">{subtitle}</p>}
            </CardHeader>

            <CardContent>
                {response ? (
                    <Markdown content={response} />
                ) : (
                    <p className="text-muted-foreground">No results generated yet.</p>
                )}
            </CardContent>
        </Card>
    );
}
