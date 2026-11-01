import { useState } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import { Check, Copy, Key, Plus, Trash2 } from 'lucide-react';
import SettingsLayout from '@/layouts/settings/layout';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import EmptyState from '@/components/shared/empty-state';
import ConfirmDialog from '@/components/shared/confirm-dialog';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { formatDate } from '@/lib/utils';

type Token = {
    id: number;
    name: string;
    abilities: string[];
    last_used_at: string | null;
    created_at: string;
};

type Props = {
    tokens: Token[];
    role: string;
};

export default function ApiTokens({ tokens, role }: Props) {
    const { flash } = usePage<{ flash: { newToken?: string } }>().props;
    const [copied, setCopied] = useState(false);
    const [revokingTokenId, setRevokingTokenId] = useState<number | null>(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
    });

    const { delete: destroyToken, processing: revoking } = useForm();

    const handleCreateToken = (e: React.FormEvent) => {
        e.preventDefault();
        post('/settings/api-tokens', {
            onSuccess: () => reset('name'),
        });
    };

    const handleRevoke = () => {
        if (!revokingTokenId) return;
        destroyToken(`/settings/api-tokens/${revokingTokenId}`, {
            onSuccess: () => setRevokingTokenId(null),
        });
    };

    const handleCopy = (token: string) => {
        navigator.clipboard.writeText(token);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    return (
        <SettingsLayout>
            <Head title="API Tokens" />

            <div className="space-y-6">
                <div>
                    <Heading
                        title="API Tokens"
                        description="Personal access tokens allow third-party clients and scripts to authenticate with the InvoiceFlow REST API v1."
                        variant="small"
                    />
                </div>

                {/* Create Token Card */}
                <Card>
                    <CardContent className="p-6">
                        <form onSubmit={handleCreateToken} className="space-y-4">
                            <div>
                                <h4 className="text-sm font-semibold text-foreground">Create API Token</h4>
                                <p className="text-xs text-muted-foreground mt-0.5">
                                    Token abilities will inherit your active workspace role ({role}).
                                </p>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="token-name">Token Name</Label>
                                <div className="flex gap-2">
                                    <Input
                                        id="token-name"
                                        placeholder="e.g. Zapier, Mobile App, Backend Service"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        className="max-w-md"
                                        required
                                    />
                                    <Button type="submit" disabled={processing}>
                                        <Plus className="w-4 h-4 mr-1.5" />
                                        Generate Token
                                    </Button>
                                </div>
                                <InputError message={errors.name} />
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Newly Generated Token Modal */}
                {flash?.newToken && (
                    <Dialog open={true} onOpenChange={() => {}}>
                        <DialogContent className="sm:max-w-lg">
                            <DialogHeader>
                                <DialogTitle className="flex items-center gap-2 text-emerald-600">
                                    <Key className="w-5 h-5" />
                                    API Token Generated
                                </DialogTitle>
                                <DialogDescription className="text-xs">
                                    Please copy your personal access token now. For your security, it will not be shown again.
                                </DialogDescription>
                            </DialogHeader>

                            <div className="space-y-3 py-2">
                                <div className="flex items-center gap-2 rounded-md border border-border bg-muted/60 p-3">
                                    <code className="text-xs font-mono break-all select-all flex-1 text-foreground">
                                        {flash.newToken}
                                    </code>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() => handleCopy(flash.newToken!)}
                                        className="shrink-0"
                                    >
                                        {copied ? (
                                            <>
                                                <Check className="w-3.5 h-3.5 mr-1 text-green-600" />
                                                Copied
                                            </>
                                        ) : (
                                            <>
                                                <Copy className="w-3.5 h-3.5 mr-1" />
                                                Copy
                                            </>
                                        )}
                                    </Button>
                                </div>
                                <p className="text-xs text-amber-600 dark:text-amber-400 font-medium">
                                    ⚠️ Keep this token secret. Anyone with this token can access your invoice data.
                                </p>
                            </div>

                            <DialogFooter>
                                <Button onClick={() => window.location.reload()}>I Have Saved My Token</Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                )}

                {/* Active Tokens Table */}
                <div className="space-y-3">
                    <h4 className="text-sm font-semibold text-foreground">Active Tokens</h4>

                    {tokens.length === 0 ? (
                        <EmptyState
                            icon={<Key className="w-6 h-6 text-muted-foreground" />}
                            title="No API tokens"
                            description="You have not created any personal access tokens yet."
                        />
                    ) : (
                        <div className="overflow-x-auto rounded-lg border border-border">
                            <table className="w-full text-left text-sm">
                                <thead className="bg-muted/50 border-b text-xs uppercase font-medium text-muted-foreground">
                                    <tr>
                                        <th className="px-4 py-3">Token Name</th>
                                        <th className="px-4 py-3">Abilities</th>
                                        <th className="px-4 py-3">Last Used</th>
                                        <th className="px-4 py-3">Created</th>
                                        <th className="px-4 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {tokens.map((token) => (
                                        <tr key={token.id} className="hover:bg-muted/30">
                                            <td className="px-4 py-3 font-medium text-foreground">
                                                {token.name}
                                            </td>
                                            <td className="px-4 py-3 text-xs text-muted-foreground font-mono">
                                                {token.abilities.includes('*') ? 'All (*)' : token.abilities.length + ' scopes'}
                                            </td>
                                            <td className="px-4 py-3 text-xs text-muted-foreground">
                                                {token.last_used_at ?? 'Never'}
                                            </td>
                                            <td className="px-4 py-3 text-xs text-muted-foreground whitespace-nowrap">
                                                {formatDate(token.created_at)}
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => setRevokingTokenId(token.id)}
                                                    className="text-destructive hover:text-destructive hover:bg-destructive/10 h-8 px-2"
                                                >
                                                    <Trash2 className="w-4 h-4 mr-1" />
                                                    Revoke
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>

            {/* Revoke Confirmation Dialog */}
            <ConfirmDialog
                open={revokingTokenId !== null}
                onOpenChange={(open) => !open && setRevokingTokenId(null)}
                title="Revoke API Token"
                description="Are you sure you want to revoke this API token? Any application using it will immediately lose access."
                confirmLabel="Revoke Token"
                variant="destructive"
                processing={revoking}
                onConfirm={handleRevoke}
            />
        </SettingsLayout>
    );
}
