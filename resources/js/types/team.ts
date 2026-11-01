export type TeamMember = {
    id: number;
    name: string;
    email: string;
    role: 'owner' | 'accountant' | 'staff';
    joined_at: string;
    is_current_user: boolean;
};

export type TeamInvitation = {
    id: number;
    email: string;
    role: 'owner' | 'accountant' | 'staff';
    role_label: string;
    expires_at: string;
    created_at: string;
    invite_url: string;
};
