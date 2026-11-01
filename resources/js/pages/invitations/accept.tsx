import { Head, Link, useForm, usePage } from '@inertiajs/react';
import PublicLayout from '@/layouts/public-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { CheckCircle2, AlertTriangle, XCircle, ArrowRight, LogIn, UserPlus } from 'lucide-react';
import type { Auth } from '@/types/auth';

interface Props {
    invitation: {
        token: string;
        email: string;
        role: string;
        company_name: string;
        status: 'valid' | 'expired' | 'accepted';
        expires_at: string;
    };
}

export default function AcceptInvitationPage({ invitation }: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const { post, processing, errors } = useForm<Record<string, string>>({});

    const handleAccept = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/invitations/${invitation.token}/accept`);
    };

    const isLoggedIn = !!auth?.user;
    const isMatchingEmail =
        isLoggedIn && auth.user?.email.toLowerCase() === invitation.email.toLowerCase();

    return (
        <PublicLayout title="Join Workspace">
            <Head title={`Invitation to join ${invitation.company_name}`} />

            <div className="max-w-md mx-auto pt-8">
                <Card className="shadow-lg border">
                    <CardHeader className="text-center pb-4">
                        <div className="mx-auto w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center mb-3">
                            {invitation.status === 'valid' ? (
                                <CheckCircle2 className="w-6 h-6 text-primary" />
                            ) : invitation.status === 'expired' ? (
                                <AlertTriangle className="w-6 h-6 text-amber-500" />
                            ) : (
                                <XCircle className="w-6 h-6 text-destructive" />
                            )}
                        </div>
                        <CardTitle className="text-xl">Workspace Invitation</CardTitle>
                        <CardDescription>
                            You have been invited to join{' '}
                            <span className="font-semibold text-foreground">
                                {invitation.company_name}
                            </span>
                        </CardDescription>
                    </CardHeader>

                    <CardContent className="space-y-4">
                        <div className="rounded-lg bg-muted/50 p-4 space-y-2 text-sm border">
                            <div className="flex justify-between items-center">
                                <span className="text-muted-foreground">Invited Email:</span>
                                <span className="font-mono font-medium">{invitation.email}</span>
                            </div>
                            <div className="flex justify-between items-center">
                                <span className="text-muted-foreground">Assigned Role:</span>
                                <Badge variant="secondary" className="capitalize">
                                    {invitation.role}
                                </Badge>
                            </div>
                            <div className="flex justify-between items-center text-xs text-muted-foreground pt-1">
                                <span>Valid Until:</span>
                                <span>{invitation.expires_at}</span>
                            </div>
                        </div>

                        {/* Status messages */}
                        {invitation.status === 'expired' && (
                            <div className="rounded-md bg-amber-500/10 p-3 text-sm text-amber-700 dark:text-amber-400">
                                This invitation link has expired. Please contact an owner of{' '}
                                {invitation.company_name} to generate a new invitation.
                            </div>
                        )}

                        {invitation.status === 'accepted' && (
                            <div className="rounded-md bg-muted p-3 text-sm text-muted-foreground">
                                This invitation has already been accepted.
                            </div>
                        )}

                        {errors.error && (
                            <div className="rounded-md bg-destructive/10 p-3 text-sm text-destructive">
                                {errors.error}
                            </div>
                        )}

                        {invitation.status === 'valid' && (
                            <>
                                {isLoggedIn && !isMatchingEmail && (
                                    <div className="rounded-md bg-amber-500/10 p-3 text-sm text-amber-700 dark:text-amber-400 space-y-2">
                                        <p>
                                            You are currently signed in as{' '}
                                            <span className="font-semibold">{auth.user?.email}</span>.
                                        </p>
                                        <p>
                                            Please sign in with the email address this invitation was sent to
                                            ({invitation.email}).
                                        </p>
                                    </div>
                                )}
                            </>
                        )}
                    </CardContent>

                    <CardFooter className="flex flex-col gap-2 pt-2">
                        {invitation.status === 'valid' && (
                            <>
                                {isLoggedIn ? (
                                    isMatchingEmail ? (
                                        <form onSubmit={handleAccept} className="w-full">
                                            <Button className="w-full" size="lg" disabled={processing}>
                                                Accept & Join Workspace
                                                <ArrowRight className="w-4 h-4 ml-2" />
                                            </Button>
                                        </form>
                                    ) : (
                                        <div className="flex gap-2 w-full">
                                            <Button asChild variant="outline" className="flex-1">
                                                <Link href="/logout" method="post" as="button">
                                                    Log Out
                                                </Link>
                                            </Button>
                                            <Button asChild className="flex-1">
                                                <Link href={`/login?invitation_token=${invitation.token}`}>
                                                    Sign In
                                                </Link>
                                            </Button>
                                        </div>
                                    )
                                ) : (
                                    <div className="flex flex-col sm:flex-row gap-2 w-full">
                                        <Button asChild className="flex-1" size="lg">
                                            <Link href={`/register?invitation_token=${invitation.token}`}>
                                                <UserPlus className="w-4 h-4 mr-2" />
                                                Create Account
                                            </Link>
                                        </Button>
                                        <Button asChild variant="outline" className="flex-1" size="lg">
                                            <Link href={`/login?invitation_token=${invitation.token}`}>
                                                <LogIn className="w-4 h-4 mr-2" />
                                                Sign In
                                            </Link>
                                        </Button>
                                    </div>
                                )}
                            </>
                        )}

                        {invitation.status !== 'valid' && (
                            <Button asChild variant="outline" className="w-full">
                                <Link href="/login">Go to Login</Link>
                            </Button>
                        )}
                    </CardFooter>
                </Card>
            </div>
        </PublicLayout>
    );
}
