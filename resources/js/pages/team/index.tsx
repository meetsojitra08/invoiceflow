import { useState } from 'react';
import { Head, useForm, usePage, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import PageHeader from '@/components/shared/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import ConfirmDialog from '@/components/shared/confirm-dialog';
import InputError from '@/components/input-error';
import type { TeamMember, TeamInvitation } from '@/types/team';
import { UserPlus, Copy, Check, ShieldAlert, Trash2 } from 'lucide-react';

interface Props {
    members: TeamMember[];
    invitations: TeamInvitation[];
    can: {
        manageTeam: boolean;
    };
    currentUser: {
        id: number;
        role: string;
    };
}

export default function TeamIndexPage({ members, invitations, can }: Props) {
    const { flash } = usePage<{ flash: { invitation_url?: string } }>().props;
    const [isInviteOpen, setIsInviteOpen] = useState(false);
    const [copiedUrl, setCopiedUrl] = useState<string | null>(null);
    const [removingMember, setRemovingMember] = useState<TeamMember | null>(null);

    const inviteForm = useForm({
        email: '',
        role: 'staff',
    });

    const submitInvite = (e: React.FormEvent) => {
        e.preventDefault();
        inviteForm.post('/team/invite', {
            onSuccess: () => {
                inviteForm.reset();
            },
        });
    };

    const handleRoleChange = (memberId: number, newRole: string) => {
        router.put(`/team/members/${memberId}`, { role: newRole });
    };

    const handleRemoveMember = () => {
        if (!removingMember) return;
        router.delete(`/team/members/${removingMember.id}`, {
            onSuccess: () => setRemovingMember(null),
        });
    };

    const copyToClipboard = (url: string) => {
        navigator.clipboard.writeText(url);
        setCopiedUrl(url);
        setTimeout(() => setCopiedUrl(null), 2500);
    };

    const getRoleBadgeVariant = (role: string) => {
        switch (role) {
            case 'owner':
                return 'default';
            case 'accountant':
                return 'secondary';
            default:
                return 'outline';
        }
    };

    return (
        <AppLayout>
            <Head title="Team Management" />

            <div className="space-y-8">
                <PageHeader
                    title="Team & Access"
                    description="Manage team workspace members, roles, and invitation links."
                    action={
                        can.manageTeam ? (
                            <Dialog open={isInviteOpen} onOpenChange={setIsInviteOpen}>
                                <DialogTrigger asChild>
                                    <Button size="sm">
                                        <UserPlus className="h-4 w-4 mr-2" />
                                        Invite Member
                                    </Button>
                                </DialogTrigger>
                                <DialogContent>
                                    <form onSubmit={submitInvite} className="space-y-4">
                                        <DialogHeader>
                                            <DialogTitle>Invite Teammate</DialogTitle>
                                            <DialogDescription>
                                                Generate an invitation link to grant access to this workspace.
                                            </DialogDescription>
                                        </DialogHeader>

                                        <div className="space-y-4 py-2">
                                            <div className="space-y-2">
                                                <Label htmlFor="invite-email">Email Address</Label>
                                                <Input
                                                    id="invite-email"
                                                    type="email"
                                                    placeholder="teammate@company.com"
                                                    value={inviteForm.data.email}
                                                    onChange={(e) => inviteForm.setData('email', e.target.value)}
                                                    required
                                                />
                                                <InputError message={inviteForm.errors.email} />
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="invite-role">Workspace Role</Label>
                                                <Select
                                                    value={inviteForm.data.role}
                                                    onValueChange={(val) => inviteForm.setData('role', val)}
                                                >
                                                    <SelectTrigger id="invite-role">
                                                        <SelectValue placeholder="Select a role" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="staff">Staff (Draft invoices, clients)</SelectItem>
                                                        <SelectItem value="accountant">Accountant (Manage invoices & products)</SelectItem>
                                                        <SelectItem value="owner">Owner (Full admin & team control)</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                                <InputError message={inviteForm.errors.role} />
                                            </div>
                                        </div>

                                        <DialogFooter>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={() => setIsInviteOpen(false)}
                                            >
                                                Cancel
                                            </Button>
                                            <Button type="submit" disabled={inviteForm.processing}>
                                                Generate Invite Link
                                            </Button>
                                        </DialogFooter>
                                    </form>
                                </DialogContent>
                            </Dialog>
                        ) : undefined
                    }
                />

                {/* Just created invitation link banner */}
                {flash?.invitation_url && (
                    <div className="rounded-lg border border-primary/20 bg-primary/5 p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div className="space-y-1">
                            <p className="font-semibold text-sm text-foreground">
                                Teammate invitation link created!
                            </p>
                            <p className="text-xs text-muted-foreground break-all font-mono">
                                {flash.invitation_url}
                            </p>
                        </div>
                        <Button
                            size="sm"
                            onClick={() => copyToClipboard(flash.invitation_url!)}
                        >
                            {copiedUrl === flash.invitation_url ? (
                                <>
                                    <Check className="h-4 w-4 mr-2 text-emerald-500" />
                                    Copied!
                                </>
                            ) : (
                                <>
                                    <Copy className="h-4 w-4 mr-2" />
                                    Copy Link
                                </>
                            )}
                        </Button>
                    </div>
                )}

                {/* Team members section */}
                <div className="bg-card rounded-lg border shadow-sm overflow-hidden">
                    <div className="p-4 sm:p-6 border-b">
                        <h2 className="text-base font-semibold">Active Members ({members.length})</h2>
                        <p className="text-sm text-muted-foreground">
                            Users with access to this workspace.
                        </p>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-muted/50 border-b text-xs uppercase font-medium text-muted-foreground">
                                <tr>
                                    <th className="px-6 py-3">Member</th>
                                    <th className="px-6 py-3">Role</th>
                                    <th className="px-6 py-3">Joined</th>
                                    {can.manageTeam && <th className="px-6 py-3 text-right">Actions</th>}
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {members.map((member) => (
                                    <tr key={member.id} className="hover:bg-muted/30">
                                        <td className="px-6 py-4">
                                            <div className="font-medium text-foreground">
                                                {member.name}{' '}
                                                {member.is_current_user && (
                                                    <span className="text-xs font-normal text-muted-foreground">(You)</span>
                                                )}
                                            </div>
                                            <div className="text-xs text-muted-foreground">{member.email}</div>
                                        </td>
                                        <td className="px-6 py-4">
                                            {can.manageTeam && !member.is_current_user ? (
                                                <Select
                                                    defaultValue={member.role}
                                                    onValueChange={(val) => handleRoleChange(member.id, val)}
                                                >
                                                    <SelectTrigger className="w-36 h-8 text-xs">
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="owner">Owner</SelectItem>
                                                        <SelectItem value="accountant">Accountant</SelectItem>
                                                        <SelectItem value="staff">Staff</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            ) : (
                                                <Badge variant={getRoleBadgeVariant(member.role)} className="capitalize">
                                                    {member.role}
                                                </Badge>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 text-muted-foreground text-xs">
                                            {member.joined_at}
                                        </td>
                                        {can.manageTeam && (
                                            <td className="px-6 py-4 text-right">
                                                {!member.is_current_user && (
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="text-destructive hover:text-destructive hover:bg-destructive/10"
                                                        onClick={() => setRemovingMember(member)}
                                                    >
                                                        <Trash2 className="h-4 w-4 mr-1" />
                                                        Remove
                                                    </Button>
                                                )}
                                            </td>
                                        )}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Pending invitations section */}
                {invitations.length > 0 && (
                    <div className="bg-card rounded-lg border shadow-sm overflow-hidden">
                        <div className="p-4 sm:p-6 border-b">
                            <h2 className="text-base font-semibold">Pending Invitations ({invitations.length})</h2>
                            <p className="text-sm text-muted-foreground">
                                Active invitations valid for 48 hours.
                            </p>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="bg-muted/50 border-b text-xs uppercase font-medium text-muted-foreground">
                                    <tr>
                                        <th className="px-6 py-3">Invitee Email</th>
                                        <th className="px-6 py-3">Invited Role</th>
                                        <th className="px-6 py-3">Sent</th>
                                        <th className="px-6 py-3">Expires</th>
                                        <th className="px-6 py-3 text-right">Invite Link</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {invitations.map((invite) => (
                                        <tr key={invite.id} className="hover:bg-muted/30">
                                            <td className="px-6 py-4 font-medium text-foreground">
                                                {invite.email}
                                            </td>
                                            <td className="px-6 py-4">
                                                <Badge variant={getRoleBadgeVariant(invite.role)}>
                                                    {invite.role_label}
                                                </Badge>
                                            </td>
                                            <td className="px-6 py-4 text-muted-foreground text-xs">
                                                {invite.created_at}
                                            </td>
                                            <td className="px-6 py-4 text-muted-foreground text-xs">
                                                {invite.expires_at}
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => copyToClipboard(invite.invite_url)}
                                                >
                                                    {copiedUrl === invite.invite_url ? (
                                                        <>
                                                            <Check className="h-3.5 w-3.5 mr-1 text-emerald-500" />
                                                            Copied
                                                        </>
                                                    ) : (
                                                        <>
                                                            <Copy className="h-3.5 w-3.5 mr-1" />
                                                            Copy Link
                                                        </>
                                                    )}
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}
            </div>

            {/* Confirm remove dialog */}
            <ConfirmDialog
                open={removingMember !== null}
                onOpenChange={(open) => !open && setRemovingMember(null)}
                title="Remove Team Member"
                description={`Are you sure you want to remove ${removingMember?.name} (${removingMember?.email}) from this workspace? They will lose all access.`}
                confirmLabel="Remove Member"
                variant="destructive"
                onConfirm={handleRemoveMember}
            />
        </AppLayout>
    );
}
