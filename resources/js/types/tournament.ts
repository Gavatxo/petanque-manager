export type TournamentStatus =
    | 'draft'
    | 'registration_open'
    | 'checkin'
    | 'running'
    | 'finished'
    | 'archived';

export type TeamFormat = 'tete_a_tete' | 'doublette' | 'triplette';

export type CourtStatus = 'available' | 'occupied' | 'disabled';

export type SelectOption = {
    value: string;
    label: string;
};

export type Court = {
    id: number;
    label: string;
    status: CourtStatus;
    status_label: string;
};

export type TournamentListItem = {
    id: number;
    name: string;
    location: string | null;
    scheduled_at: string | null;
    status: TournamentStatus;
    status_label: string;
    /** null tant que le tirage n'a pas figé le format. */
    current_phase: string | null;
    team_format: TeamFormat;
    team_format_label: string;
    qualifying_rounds: number;
    tableaux_count: number;
    points_target: number;
    max_teams: number | null;
    courts_count: number;
    is_archived: boolean;
    created_at: string;
};

export type Tournament = TournamentListItem & {
    description: string | null;
    registration_token: string;
    registration_url: string;
    courts: Court[];
};
